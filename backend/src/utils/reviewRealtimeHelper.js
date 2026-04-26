const { Review, Product, User, sequelize } = require('../models');
const socketUtility = require('./socketUtility');

const toFixedNumber = (value) => Number(parseFloat(value || 0).toFixed(1));

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

const normalizeRealtimeImage = (value) => {
  if (value && typeof value === 'object' && value.url) {
    return {
      ...value,
      url: normalizeRealtimeImage(value.url),
    };
  }

  if (typeof value !== 'string') return null;
  const trimmed = value.trim();
  return trimmed || null;
};

const serializeRealtimeReview = (review) => {
  const plainReview = review?.get ? review.get({ plain: true }) : review;
  if (!plainReview || typeof plainReview !== 'object') return null;

  return {
    ...plainReview,
    images: parseStoredList(plainReview.images)
      .map((img) => normalizeRealtimeImage(img))
      .filter(Boolean),
    customer: plainReview.customer
      ? {
          ...plainReview.customer,
          profilePhoto: normalizeRealtimeImage(plainReview.customer.profilePhoto),
        }
      : undefined,
  };
};

const buildReviewRealtimePayload = async ({ productId, sellerId, reviewId = null }) => {
  const [productSummary, sellerSummary, latestReview] = await Promise.all([
    Review.findAll({
      where: { productId },
      attributes: [
        [sequelize.fn('AVG', sequelize.col('rating')), 'avgRating'],
        [sequelize.fn('COUNT', sequelize.col('id')), 'reviewCount'],
      ],
      raw: true,
    }),
    Review.findAll({
      include: [{
        model: Product,
        as: 'product',
        where: { sellerId },
        attributes: [],
      }],
      attributes: [
        [sequelize.fn('AVG', sequelize.col('rating')), 'avgRating'],
        [sequelize.fn('COUNT', sequelize.col('Review.id')), 'reviewCount'],
      ],
      raw: true,
    }),
    reviewId
      ? Review.findByPk(reviewId, {
          include: [{
            model: User,
            as: 'customer',
            attributes: ['id', 'name', 'profilePhoto'],
          }],
        })
      : null,
  ]);

  return {
    productId,
    sellerId,
    productRating: toFixedNumber(productSummary[0]?.avgRating),
    productReviewCount: Number(productSummary[0]?.reviewCount || 0),
    sellerRating: toFixedNumber(sellerSummary[0]?.avgRating),
    sellerReviewCount: Number(sellerSummary[0]?.reviewCount || 0),
    latestReview: latestReview ? serializeRealtimeReview(latestReview) : null,
    timestamp: new Date(),
  };
};

const emitReviewUpdated = async ({ productId, sellerId, reviewId = null }) => {
  const payload = await buildReviewRealtimePayload({ productId, sellerId, reviewId });
  socketUtility.emit('review_updated', payload);
  socketUtility.emitStatsUpdate({ type: 'review', productId, sellerId });
  return payload;
};

module.exports = {
  buildReviewRealtimePayload,
  emitReviewUpdated,
};
