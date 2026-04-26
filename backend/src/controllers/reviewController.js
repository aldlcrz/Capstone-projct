const { Review, Product, Order, User } = require('../models');
const { emitReviewUpdated } = require('../utils/reviewRealtimeHelper');

const parseStoredList = (value) => {
  if (Array.isArray(value)) return value;
  if (typeof value !== 'string') return [];

  const trimmed = value.trim();
  if (!trimmed) return [];

  try {
    const parsed = JSON.parse(trimmed);
    if (Array.isArray(parsed)) return parsed;
    if (typeof parsed === 'string' && parsed.trim()) return [parsed];
  } catch (error) {}

  if (trimmed.startsWith('[') || trimmed.endsWith(']')) return [];

  return [trimmed];
};

const toPublicImageUrl = (req, value) => {
  if (value && typeof value === 'object' && value.url) {
    return {
      ...value,
      url: toPublicImageUrl(req, value.url),
    };
  }

  if (typeof value !== 'string') return null;

  const trimmed = value.trim();
  if (!trimmed) return null;
  if (/^https?:\/\//i.test(trimmed) || trimmed.startsWith('data:') || trimmed.startsWith('blob:') || trimmed.startsWith('/images/')) {
    return trimmed;
  }

  const normalized = trimmed.replace(/\\/g, '/').replace(/^\.?\//, '');
  if (normalized.startsWith('uploads/')) {
    return `${req.protocol}://${req.get('host')}/${normalized}`;
  }

  return normalized.startsWith('/') ? normalized : `/${normalized}`;
};

const serializeReview = (req, review) => {
  const plainReview = review?.get ? review.get({ plain: true }) : review;
  if (!plainReview || typeof plainReview !== 'object') return null;

  return {
    ...plainReview,
    images: parseStoredList(plainReview.images)
      .map((img) => toPublicImageUrl(req, img))
      .filter(Boolean),
    customer: plainReview.customer
      ? {
          ...plainReview.customer,
          profilePhoto: toPublicImageUrl(req, plainReview.customer.profilePhoto),
        }
      : undefined,
    product: plainReview.product
      ? {
          ...plainReview.product,
          image: parseStoredList(plainReview.product.image)
            .map((img) => toPublicImageUrl(req, img))
            .filter(Boolean),
        }
      : undefined,
  };
};

exports.createReview = async (req, res, next) => {
  try {
    const { productId, orderId, rating, comment, images } = req.body;
    const customerId = req.user.id;
    const product = await Product.findByPk(productId, {
      attributes: ['id', 'sellerId']
    });

    if (!product) {
      return res.status(404).json({ message: 'Product not found.' });
    }

    let review = await Review.findOne({
      where: { productId, customerId }
    });

    const imagesStr = Array.isArray(images) ? JSON.stringify(images) : images;

    if (review) {
      review.rating = rating;
      review.comment = comment;
      review.images = imagesStr;
      await review.save();
    } else {
      review = await Review.create({
        productId,
        customerId,
        orderId,
        rating,
        comment,
        images: imagesStr
      });
    }

    await emitReviewUpdated({
      productId: product.id,
      sellerId: product.sellerId,
      reviewId: review.id
    });

    res.status(200).json({
      message: 'Review submitted successfully',
      review
    });
  } catch (error) {
    next(error);
  }
};

exports.getProductReviews = async (req, res, next) => {
  try {
    const { productId } = req.params;
    const reviews = await Review.findAll({
      where: { productId },
      include: [
        {
          model: User,
          as: 'customer',
          attributes: ['name', 'profilePhoto']
        }
      ],
      order: [['createdAt', 'DESC']]
    });

    res.status(200).json(reviews.map((review) => serializeReview(req, review)).filter(Boolean));
  } catch (error) {
    next(error);
  }
};

exports.getSellerReviews = async (req, res, next) => {
    try {
        const sellerId = req.params.sellerId;
        const reviews = await Review.findAll({
            include: [
                {
                    model: Product,
                    as: 'product',
                    where: { sellerId },
                    attributes: ['name', 'image']
                },
                {
                    model: User,
                    as: 'customer',
                    attributes: ['name', 'profilePhoto']
                }
            ],
            order: [['createdAt', 'DESC']]
        });
        res.status(200).json(reviews.map((review) => serializeReview(req, review)).filter(Boolean));
    } catch (error) {
        next(error);
    }
};
