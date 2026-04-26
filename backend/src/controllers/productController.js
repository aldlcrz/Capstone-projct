const { Product, User, Order, OrderItem, Message, ProductView, Review, SellerFunnelEvent, sequelize } = require('../models');
const { Op } = require('sequelize');
const { emitInventoryUpdated } = require('../utils/socketUtility');
const { notifyAdmins, sendNotification } = require('../utils/notificationHelper');
const { emitReviewUpdated } = require('../utils/reviewRealtimeHelper');
const {
  LIMITS,
  normalizeWhitespace,
  validatePhilippineMobileNumber,
} = require('../utils/inputValidation');

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

const getClientIp = (req) => {
  const forwardedFor = req.headers['x-forwarded-for'];
  if (typeof forwardedFor === 'string' && forwardedFor.trim()) {
    return forwardedFor.split(',')[0].trim();
  }
  return req.ip || req.connection?.remoteAddress || null;
};

const getVisitorSessionId = (req) => {
  const rawValue = req.header('x-visitor-session') || req.body?.visitorSessionId;
  if (typeof rawValue !== 'string') return null;

  const trimmed = rawValue.trim();
  if (!trimmed) return null;

  return trimmed.slice(0, 128);
};

const shouldTrackCustomerAnalytics = (user) => !user || user.role === 'customer';

const toPublicImageUrl = (req, value) => {
  // If it's an object with a url, process the url
  if (value && typeof value === 'object' && value.url) {
    return {
      ...value,
      url: toPublicImageUrl(req, value.url)
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
  };
};

const serializeProduct = (req, product) => {
  const plainProduct = product.get ? product.get({ plain: true }) : product;

  const images = parseStoredList(plainProduct.image)
    .map((img) => toPublicImageUrl(req, img))
    .filter(Boolean);

  return {
    ...plainProduct,
    sizes: parseStoredList(plainProduct.sizes).filter(Boolean),
    categories: parseStoredList(plainProduct.categories).filter(Boolean),
    image: images,
    artisan: plainProduct.seller ? plainProduct.seller.name : undefined,
    gcashNumber: plainProduct.gcashNumber,
    gcashQrCode: plainProduct.gcashQrCode ? toPublicImageUrl(req, plainProduct.gcashQrCode) : null,
    mayaNumber: plainProduct.mayaNumber,
    mayaQrCode: plainProduct.mayaQrCode ? toPublicImageUrl(req, plainProduct.mayaQrCode) : null,
    seller: plainProduct.seller ? {
      id: plainProduct.seller.id,
      name: plainProduct.seller.name,
      gcashNumber: plainProduct.seller.gcashNumber,
      gcashQrCode: plainProduct.seller.gcashQrCode
        ? toPublicImageUrl(req, plainProduct.seller.gcashQrCode)
        : null,
      mayaNumber: plainProduct.seller.mayaNumber,
      mayaQrCode: plainProduct.seller.mayaQrCode
        ? toPublicImageUrl(req, plainProduct.seller.mayaQrCode)
        : null
    } : undefined,
    reviews: Array.isArray(plainProduct.reviews)
      ? plainProduct.reviews.map((review) => serializeReview(req, review)).filter(Boolean)
      : undefined,
    rating: parseFloat(plainProduct.avgRating || 0).toFixed(1),
    reviewCount: parseInt(plainProduct.reviewCount || 0),
    soldCount: parseInt(plainProduct.soldCount || 0)
  };
};

const getProductLookup = (req, id) => {
  if (req.user?.role === 'admin') {
    return Product.findByPk(id);
  }

  return Product.findOne({
    where: {
      id,
      sellerId: req.user.id,
    },
  });
};

exports.getAllProducts = async (req, res) => {
  try {
    const { category, search, seller } = req.query;
    const where = {};

    if (category && category !== 'All') {
      where.categories = { [Op.like]: `%${category}%` };
    }

    if (search) {
      where.name = { [Op.like]: `%${search}%` };
    }

    if (seller) {
      where.sellerId = seller;
    }

    const products = await Product.findAll({
      where,
      attributes: {
        include: [
          [
            sequelize.literal(`(
              SELECT AVG(rating)
              FROM Reviews AS reviews
              WHERE reviews.productId = Product.id
            )`),
            'avgRating'
          ],
          [
            sequelize.literal(`(
              SELECT COUNT(*)
              FROM Reviews AS reviews
              WHERE reviews.productId = Product.id
            )`),
            'reviewCount'
          ],
          [
            sequelize.literal(`(
              SELECT SUM(quantity)
              FROM OrderItems AS items
              INNER JOIN Orders AS orders ON items.orderId = orders.id
              WHERE items.productId = Product.id AND orders.status IN ('Delivered', 'Completed')
            )`),
            'soldCount'
          ]
        ]
      },
      include: [{ model: User, as: 'seller', attributes: ['id', 'name', 'gcashNumber', 'gcashQrCode', 'mayaNumber', 'mayaQrCode'] }],
      order: [['createdAt', 'DESC']],
    });

    res.status(200).json(products.map((product) => serializeProduct(req, product)));
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.getSellerProducts = async (req, res) => {
  try {
    const products = await Product.findAll({
      where: { sellerId: req.user.id },
      order: [['createdAt', 'DESC']],
    });

    res.status(200).json(products.map((product) => serializeProduct(req, product)));
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.getProductById = async (req, res) => {
  try {
    const product = await Product.findByPk(req.params.id, {
      attributes: {
        include: [
          [
            sequelize.literal(`(
              SELECT AVG(rating)
              FROM Reviews AS reviews
              WHERE reviews.productId = Product.id
            )`),
            'avgRating'
          ],
          [
            sequelize.literal(`(
              SELECT COUNT(*)
              FROM Reviews AS reviews
              WHERE reviews.productId = Product.id
            )`),
            'reviewCount'
          ],
          [
            sequelize.literal(`(
              SELECT SUM(quantity)
              FROM OrderItems AS items
              INNER JOIN Orders AS orders ON items.orderId = orders.id
              WHERE items.productId = Product.id AND orders.status IN ('Delivered', 'Completed')
            )`),
            'soldCount'
          ]
        ]
      },
      include: [
        { model: User, as: 'seller', attributes: ['id', 'name', 'gcashNumber', 'gcashQrCode', 'mayaNumber', 'mayaQrCode', 'createdAt'] },
        {
          model: Review,
          as: 'reviews',
          include: [{ model: User, as: 'customer', attributes: ['id', 'name', 'profilePhoto'] }]
        }
      ],
      order: [[{ model: Review, as: 'reviews' }, 'createdAt', 'DESC']]
    });

    if (!product) {
      return res.status(404).json({ message: 'Product not found' });
    }

    if (shouldTrackCustomerAnalytics(req.user)) {
      try {
        await product.increment('views');

        await ProductView.create({
          productId: product.id,
          sellerId: product.sellerId,
          customerId: req.user?.role === 'customer' ? req.user.id : null,
          visitorSessionId: getVisitorSessionId(req),
          ipAddress: getClientIp(req),
        });

        const { emitStatsUpdate } = require('../utils/socketUtility');
        emitStatsUpdate({ type: 'view', sellerId: product.sellerId });
      } catch (e) {
        console.error('View tracking error:', e.message);
      }
    }

    res.status(200).json(serializeProduct(req, product));
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.createProduct = async (req, res) => {
  try {
    const { 
      name, description, price, sizes, categories, stock, variationNames, 
      shippingFee, shippingDays,
      gcashNumber, gcashQrCode, mayaNumber, mayaQrCode,
      allowGcash, allowMaya
    } = req.body;

    const normalizedName = normalizeWhitespace(name);
    const normalizedDescription = normalizeWhitespace(description);
    const normalizedGcashNumber = validatePhilippineMobileNumber(gcashNumber, 'GCash number', { required: false });
    const normalizedMayaNumber = validatePhilippineMobileNumber(mayaNumber, 'Maya number', { required: false });

    if (!normalizedName || !price || stock === undefined) {
      return res.status(400).json({ message: 'Name, price, and stock are required' });
    }

    if (normalizedName.length > LIMITS.productName) {
      return res.status(400).json({ message: `Product name cannot exceed ${LIMITS.productName} characters` });
    }

    if (normalizedDescription.length > LIMITS.productDescription) {
      return res.status(400).json({ message: `Description cannot exceed ${LIMITS.productDescription} characters` });
    }

    if (Number(price) <= 0 || Number(price) > LIMITS.priceMax) {
      return res.status(400).json({ message: 'Price must be between 1 and 10,000 PHP' });
    }
    
    if (!Number.isInteger(Number(stock)) || Number(stock) < 0 || Number(stock) > LIMITS.stockMax) {
      return res.status(400).json({ message: 'Stock must be between 0 and 500 units' });
    }
    
    if (shippingFee !== undefined && (isNaN(Number(shippingFee)) || Number(shippingFee) < 0 || Number(shippingFee) > LIMITS.shippingFeeMax)) {
      return res.status(400).json({ message: 'Shipping fee cannot exceed 500 PHP' });
    }
    
    if (shippingDays !== undefined && (!Number.isInteger(Number(shippingDays)) || Number(shippingDays) < 1 || Number(shippingDays) > LIMITS.shippingDaysMax)) {
      return res.status(400).json({ message: 'Shipping days must be between 1 and 30 days' });
    }
    
    let images = [];
    if (req.files && req.files.length > 0) {
      const labels = Array.isArray(variationNames) 
        ? variationNames 
        : JSON.parse(variationNames || '[]');
      
      images = req.files.map((file, index) => ({
        url: `/uploads/products/${file.filename}`,
        variation: labels[index] || `Variation ${index + 1}`
      }));
    }

    const product = await Product.create({
      name: normalizedName,
      description: normalizedDescription || null,
      price,
      sizes: Array.isArray(sizes) ? sizes : JSON.parse(sizes || '[]'),
      categories: Array.isArray(categories) ? categories : JSON.parse(categories || '[]'),
      stock,
      shippingFee: shippingFee || 0,
      shippingDays: shippingDays || 3,
      gcashNumber: normalizedGcashNumber,
      gcashQrCode,
      mayaNumber: normalizedMayaNumber,
      mayaQrCode,
      allowGcash: allowGcash === undefined ? true : (allowGcash === 'true' || allowGcash === true),
      allowMaya: allowMaya === undefined ? true : (allowMaya === 'true' || allowMaya === true),
      sellerId: req.user.id,
      image: images,
    });

    emitInventoryUpdated(product, { action: 'created' });

    try {
      const seller = await User.findByPk(req.user.id);
      if (seller && seller.followers) {
        let followers = seller.followers;
        if (typeof followers === 'string') followers = JSON.parse(followers);
        if (Array.isArray(followers)) {
          const { sendNotification } = require('../utils/notificationHelper');
          for (const followerId of followers) {
            await sendNotification(
              followerId,
              'New Product Alert!',
              `${seller.name || "A shop you follow"} has just uploaded a new product: ${product.name}. Check it out!`,
              'general',
              `/products?id=${product.id}`,
              'customer'
            );
          }
        }
      }
    } catch (notifErr) {
      console.error('Failed to notify followers:', notifErr);
    }

    try {
      const seller = await User.findByPk(req.user.id);
      await notifyAdmins(
        'New Product Posted',
        `Seller ${seller ? seller.name : 'Unknown'} posted a new product: ${product.name}`,
        'system',
        `/admin/products?search=${encodeURIComponent(product.name)}`
      );
    } catch (adminNotifErr) {
      console.error('Failed to notify admins:', adminNotifErr);
    }

    res.status(201).json(serializeProduct(req, product));
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.updateProduct = async (req, res) => {
  try {
    const product = await getProductLookup(req, req.params.id);
    if (!product) {
      return res.status(404).json({ message: 'Product not found or access denied' });
    }
    const { 
      name, description, price, sizes, categories, stock, variationNames, 
      existingImages, shippingFee, shippingDays,
      gcashNumber, gcashQrCode, mayaNumber, mayaQrCode,
      allowGcash, allowMaya
    } = req.body;

    const normalizedName = name === undefined ? undefined : normalizeWhitespace(name);
    const normalizedDescription = description === undefined ? undefined : normalizeWhitespace(description);
    const normalizedGcashNumber = gcashNumber === undefined
      ? undefined
      : validatePhilippineMobileNumber(gcashNumber, 'GCash number', { required: false });
    const normalizedMayaNumber = mayaNumber === undefined
      ? undefined
      : validatePhilippineMobileNumber(mayaNumber, 'Maya number', { required: false });

    if (normalizedName !== undefined) {
      if (!normalizedName) {
        return res.status(400).json({ message: 'Product name is required' });
      }
      if (normalizedName.length > LIMITS.productName) {
        return res.status(400).json({ message: `Product name cannot exceed ${LIMITS.productName} characters` });
      }
    }

    if (normalizedDescription !== undefined && normalizedDescription.length > LIMITS.productDescription) {
      return res.status(400).json({ message: `Description cannot exceed ${LIMITS.productDescription} characters` });
    }

    if (price !== undefined && (Number(price) <= 0 || Number(price) > LIMITS.priceMax)) {
      return res.status(400).json({ message: 'Price must be between 1 and 10,000 PHP' });
    }
    
    if (stock !== undefined && (!Number.isInteger(Number(stock)) || Number(stock) < 0 || Number(stock) > LIMITS.stockMax)) {
      return res.status(400).json({ message: 'Stock must be between 0 and 500 units' });
    }
    
    if (shippingFee !== undefined && (isNaN(Number(shippingFee)) || Number(shippingFee) < 0 || Number(shippingFee) > LIMITS.shippingFeeMax)) {
      return res.status(400).json({ message: 'Shipping fee cannot exceed 500 PHP' });
    }
    
    if (shippingDays !== undefined && (!Number.isInteger(Number(shippingDays)) || Number(shippingDays) < 1 || Number(shippingDays) > LIMITS.shippingDaysMax)) {
      return res.status(400).json({ message: 'Shipping days must be between 1 and 30 days' });
    }

    let images = parseStoredList(product.image);
    if (existingImages) {
      images = Array.isArray(existingImages) ? existingImages : JSON.parse(existingImages);
    }

    if (req.files && req.files.length > 0) {
      const labels = Array.isArray(variationNames) 
        ? variationNames 
        : JSON.parse(variationNames || '[]');
      
      const newImages = req.files.map((file, index) => ({
        url: `/uploads/products/${file.filename}`,
        variation: labels[index] || `Variation ${images.length + index + 1}`
      }));
      
      images = [...images, ...newImages];
    }

    await product.update({
      name: normalizedName ?? product.name,
      description: normalizedDescription ?? product.description,
      price: price ?? product.price,
      sizes: sizes ? (Array.isArray(sizes) ? sizes : JSON.parse(sizes)) : product.sizes,
      categories: categories ? (Array.isArray(categories) ? categories : JSON.parse(categories)) : product.categories,
      stock: stock ?? product.stock,
      shippingFee: shippingFee ?? product.shippingFee,
      shippingDays: shippingDays ?? product.shippingDays,
      gcashNumber: normalizedGcashNumber ?? product.gcashNumber,
      gcashQrCode: (gcashQrCode === 'null' || gcashQrCode === '') ? null : (gcashQrCode ?? product.gcashQrCode),
      mayaNumber: normalizedMayaNumber ?? product.mayaNumber,
      mayaQrCode: (mayaQrCode === 'null' || mayaQrCode === '') ? null : (mayaQrCode ?? product.mayaQrCode),
      allowGcash: allowGcash !== undefined ? (allowGcash === 'true' || allowGcash === true) : product.allowGcash,
      allowMaya: allowMaya !== undefined ? (allowMaya === 'true' || allowMaya === true) : product.allowMaya,
      image: images,
    });

    emitInventoryUpdated(product, { action: 'updated' });
    res.status(200).json(serializeProduct(req, product));
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.deleteProduct = async (req, res) => {
  try {
    const product = await getProductLookup(req, req.params.id);
    if (!product) {
      return res.status(404).json({ message: 'Product not found' });
    }

    const { reason } = req.body;
    const deletedProduct = product.get({ plain: true });
    await product.destroy();

    if (req.user && req.user.role === 'admin') {
      await sendNotification(
        deletedProduct.sellerId,
        'Product Removed',
        `Your product "${deletedProduct.name}" has been removed by an administrator. Reason: ${reason || 'Violation of community guidelines.'}`,
        'system',
        '/seller/inventory',
        'seller'
      );
    }

    emitInventoryUpdated(deletedProduct, {
      action: 'deleted',
      stock: 0,
      product: deletedProduct,
    });

    res.status(200).json({ message: 'Product deleted successfully' });
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

const { getRangeBounds } = require('../utils/dateHelper');

exports.getSellerStats = async (req, res) => {
  try {
    const sellerId = req.user.id || req.user.userId;
    if (!sellerId) {
      return res.status(401).json({ message: 'Seller ID missing from token' });
    }

    const { range = 'month' } = req.query;
    const now = new Date();
    const start = getRangeBounds(range);

    // ── 1. KPI Counts within Range ──────────────────────────────────
    const rangeWhere = { 
      sellerId, 
      createdAt: { [Op.gte]: start } 
    };

    const [totalRevenue, totalOrders, completedOrders, contactLeadCustomers] = await Promise.all([
      Order.sum('totalAmount', {
        where: { ...rangeWhere, status: { [Op.notIn]: ['Cancelled'] } },
      }).catch(() => 0),
      Order.count({ where: rangeWhere }).catch(() => 0),
      Order.count({ where: { ...rangeWhere, status: { [Op.in]: ['Delivered', 'Completed'] } } }).catch(() => 0),
      Message.count({
        where: {
          receiverId: sellerId,
          senderId: { [Op.ne]: sellerId },
          createdAt: { [Op.gte]: start }
        },
        distinct: true,
        col: 'senderId'
      }).catch(() => 0),
    ]);

    // Global counts (not range-bound)
    const [totalInventory, lowStock, totalViews] = await Promise.all([
      Product.count({ where: { sellerId } }).catch(() => 0),
      Product.count({ where: { sellerId, stock: { [Op.lt]: 5 } } }).catch(() => 0),
      ProductView.count({ where: { sellerId } }).catch(() => 0),
    ]);

    // ── 1.5 Strict Unique Metrics for Funnel ──────────────────────────
    
    // Distinct IDs for strict unique counting across different metrics
    const [viewIds, msgIds, cartIds, orderIds] = await Promise.all([
      ProductView.findAll({ where: rangeWhere, attributes: [[sequelize.literal("COALESCE(CAST(customerId AS CHAR), visitorSessionId, ipAddress)"), 'uid']], raw: true }),
      Message.findAll({ where: { receiverId: sellerId, createdAt: { [Op.gte]: start } }, attributes: [['senderId', 'uid']], raw: true }),
      SellerFunnelEvent.findAll({ where: { sellerId, eventType: 'add_to_cart', createdAt: { [Op.gte]: start } }, attributes: [[sequelize.literal("COALESCE(CAST(customerId AS CHAR), visitorSessionId, ipAddress)"), 'uid']], raw: true }),
      Order.findAll({ where: rangeWhere, attributes: [['customerId', 'uid']], raw: true })
    ]).catch(() => [[], [], [], []]);

    const visitorsSet = new Set([
      ...viewIds.map(v => v.uid),
      ...msgIds.map(m => m.uid),
      ...cartIds.map(c => c.uid),
      ...orderIds.map(o => o.uid)
    ].filter(Boolean));

    const rangeVisitors = visitorsSet.size;
    const rangeViewsUnique = new Set(viewIds.map(v => v.uid).filter(Boolean)).size;

    const rangeViews = await ProductView.count({
      where: rangeWhere
    }).catch(() => 0);

    const addToCartWhere = {
      sellerId,
      eventType: 'add_to_cart',
      createdAt: { [Op.gte]: start }
    };

    // Unique Added to Cart: People who added something to cart (not how many items).
    const addedToCart = await SellerFunnelEvent.count({
      where: addToCartWhere,
      distinct: true,
      col: 'visitorSessionId', // Fallback
      attributes: [
        [sequelize.fn('COUNT', sequelize.fn('DISTINCT', sequelize.literal("COALESCE(CAST(customerId AS CHAR), visitorSessionId, ipAddress)"))), 'count']
      ],
      raw: true,
      plain: true
    }).then(res => Number(res?.count || res || 0))
    .catch(() => 0);

    const rangeInquiries = contactLeadCustomers; // Synchronize these

    // ── 2. Performance Series (Adapts to range) ──────────────────────
    const ordersTrend = await Order.findAll({
      attributes: ['totalAmount', 'createdAt'],
      where: {
        sellerId,
        status: { [Op.notIn]: ['Cancelled'] },
        createdAt: { [Op.gte]: start },
      },
      order: [['createdAt', 'ASC']],
    });

    let performance = [];
    if (range === 'today') {
      const bins = Array.from({ length: 24 }, (_, i) => ({ name: `${i}:00`, sales: 0 }));
      ordersTrend.forEach(o => {
        const hour = new Date(o.createdAt).getHours();
        bins[hour].sales += Number(o.totalAmount || 0);
      });
      performance = bins;
    } else if (range === 'week') {
      const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      const WEEK_ORDER = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      const weeklyMap = Object.fromEntries(WEEK_ORDER.map(d => [d, 0]));
      ordersTrend.forEach(o => {
        const name = DAY_NAMES[new Date(o.createdAt).getDay()];
        weeklyMap[name] = (weeklyMap[name] || 0) + Number(o.totalAmount || 0);
      });
      performance = WEEK_ORDER.map(name => ({ name, sales: weeklyMap[name] }));
    } else if (range === 'month') {
      const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
      const bins = Array.from({ length: daysInMonth }, (_, i) => ({ name: `${i+1}`, sales: 0 }));
      ordersTrend.forEach(o => {
        const day = new Date(o.createdAt).getDate();
        bins[day-1].sales += Number(o.totalAmount || 0);
      });
      performance = bins;
    } else if (range === 'year') {
      const MONTH_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const bins = MONTH_NAMES.map(name => ({ name, sales: 0 }));
      ordersTrend.forEach(o => {
        const month = new Date(o.createdAt).getMonth();
        bins[month].sales += Number(o.totalAmount || 0);
      });
      performance = bins;
    }

    // ── 3. Top Sold Products ──────────────────────────────────────────
    let topProducts = [];
    let qualifyingOrderIds = [];
    try {
      // Step 1: Get qualifying order IDs for this seller in the date range
      const qualifyingOrders = await Order.findAll({
        attributes: ['id'],
        where: {
          sellerId,
          status: { [Op.notIn]: ['Cancelled'] },
          createdAt: { [Op.gte]: start }
        },
        raw: true
      });

      qualifyingOrderIds = qualifyingOrders.map(o => o.id);

      if (qualifyingOrderIds.length > 0) {
        // Step 2: Aggregate OrderItems by productId within those orders
        const topProductsData = await OrderItem.findAll({
          attributes: [
            'productId',
            [sequelize.fn('SUM', sequelize.col('OrderItem.quantity')), 'totalSold']
          ],
          where: { orderId: { [Op.in]: qualifyingOrderIds } },
          include: [{
            model: Product,
            as: 'product',
            attributes: ['id', 'name', 'price', 'categories', 'stock'],
            required: true
          }],
          group: ['OrderItem.productId', 'product.id', 'product.name', 'product.price', 'product.categories', 'product.stock'],
          order: [[sequelize.fn('SUM', sequelize.col('OrderItem.quantity')), 'DESC']],
          limit: 5,
          subQuery: false,
          raw: true,
          nest: true
        });

        const maxSold = Math.max(...topProductsData.map(d => Number(d.totalSold || 0)), 1);
        const productIds = topProductsData
          .map(d => d.product?.id)
          .filter(Boolean);

        const reviewStats = productIds.length > 0
          ? await Review.findAll({
              attributes: [
                'productId',
                [sequelize.fn('AVG', sequelize.col('rating')), 'avgRating'],
                [sequelize.fn('COUNT', sequelize.col('id')), 'reviewsCount']
              ],
              where: { productId: { [Op.in]: productIds } },
              group: ['productId'],
              raw: true
            })
          : [];

        const reviewMap = new Map(
          reviewStats.map((entry) => [
            String(entry.productId),
            {
              avgRating: Number(entry.avgRating || 0),
              reviewsCount: Number(entry.reviewsCount || 0)
            }
          ])
        );

        topProducts = topProductsData.map(d => {
           let cat = d.product?.categories;
           let categoryName = "Other";
           if (typeof cat === 'string') {
             try { cat = JSON.parse(cat); } catch(e) {}
           }
           if (Array.isArray(cat) && cat.length > 0) categoryName = cat[0];

           const sales = Number(d.totalSold || 0);
           const price = Number(d.product?.price || 0);
           const stock = Number(d.product?.stock || 0);
           
           let status = 'Trending';
           if (stock < 5) status = 'Low stock';
           else if (sales >= maxSold * 0.8) status = 'Top seller';

           const reviewSummary = reviewMap.get(String(d.product?.id)) || {
             avgRating: 0,
             reviewsCount: 0
           };

           return {
            id: d.product?.id,
            name: d.product?.name || 'Unknown Product',
            category: categoryName,
            sales: sales,
            revenue: sales * price,
            maxSalesRef: maxSold,
            status: status,
            rating: reviewSummary.avgRating > 0 ? Number(reviewSummary.avgRating.toFixed(1)) : null,
            reviewsCount: reviewSummary.reviewsCount
          };
        });
      }
    } catch (err) {
      console.warn('Failed to fetch top products:', err.message);
    }

    // ── 4. Suki (Retention Rate) — Seller-Scoped ─────────────────────
    let retentionRate = 0;
    try {
      const customerOrderCounts = await Order.findAll({
        attributes: [
          'customerId',
          [sequelize.fn('COUNT', sequelize.col('Order.id')), 'orderCount']
        ],
        where: { 
          sellerId, 
          status: { [Op.notIn]: ['Cancelled'] },
          createdAt: { [Op.gte]: start }
        },
        group: ['Order.customerId'],
        raw: true,
      });
      const totalCustomers = customerOrderCounts.length;
      const repeatCustomers = customerOrderCounts.filter(
        c => parseInt(c.orderCount || 0, 10) > 1
      ).length;
      retentionRate = totalCustomers > 0 ? (repeatCustomers / totalCustomers) * 100 : 0;
    } catch (retentionErr) {
      console.warn('Suki (retention) calc skipped:', retentionErr.message);
    }

    // ── 5. Top Categories Aggregation ────────────────────────────────
    let topCategories = [];
    try {
      if (qualifyingOrderIds && qualifyingOrderIds.length > 0) {
        // Fetch all items in these orders (re-using the IDs from top products)
        const allItems = await OrderItem.findAll({
          where: { orderId: { [Op.in]: qualifyingOrderIds } },
          include: [{ 
            model: Product, 
            as: 'product', 
            attributes: ['categories', 'price'],
            required: true 
          }],
          raw: true,
          nest: true
        });

        const catMap = {};
        allItems.forEach(item => {
          let cats = item.product?.categories;
          if (typeof cats === 'string') {
            try { cats = JSON.parse(cats); } catch(e) { cats = []; }
          }
          const primary = (Array.isArray(cats) && cats.length > 0) ? cats[0] : 'Other';
          
          if (!catMap[primary]) {
            catMap[primary] = { name: primary, value: 0, revenue: 0 };
          }
          catMap[primary].value += Number(item.quantity || 0);
          catMap[primary].revenue += Number(item.quantity || 0) * Number(item.product?.price || 0);
        });

        topCategories = Object.values(catMap)
          .sort((a, b) => b.revenue - a.revenue)
          .slice(0, 5);
      }
    } catch (catErr) {
      console.warn('Top Categories aggregation failed:', catErr.message);
    }

    // ── 6. Final Response Assembly ──────────────────────────────────
    res.status(200).json({
      revenue: Number(totalRevenue || 0),
      orders: totalOrders || 0,
      inquiries: rangeInquiries || 0,
      products: totalInventory || 0,
      lowStock: lowStock || 0,
      performance,
      topProducts,
      topCategories,
      retention: Number(retentionRate).toFixed(1),
      funnel: {
        visitors: rangeVisitors || 0,
        contacts: contactLeadCustomers || 0,
        leads: rangeViewsUnique || 0,
        addedToCart: addedToCart || 0,
        checkout: totalOrders || 0, // In this app, an order creation IS the checkout step
        completed: completedOrders || 0,
      },
      global: {
        views: totalViews || 0
      }
    });
  } catch (error) {
    console.error('getSellerStats Error:', error.message);
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.trackProductFunnelEvent = async (req, res) => {
  try {
    const { id: productId } = req.params;
    const { eventType } = req.body || {};

    if (eventType !== 'add_to_cart') {
      return res.status(400).json({ message: 'Unsupported funnel event type' });
    }

    if (req.user && req.user.role !== 'customer') {
      return res.status(204).end();
    }

    const product = await Product.findByPk(productId, {
      attributes: ['id', 'sellerId']
    });

    if (!product) {
      return res.status(404).json({ message: 'Product not found' });
    }

    await SellerFunnelEvent.create({
      sellerId: product.sellerId,
      productId: product.id,
      customerId: req.user?.role === 'customer' ? req.user.id : null,
      visitorSessionId: getVisitorSessionId(req),
      ipAddress: getClientIp(req),
      eventType
    });

    const { emitStatsUpdate } = require('../utils/socketUtility');
    emitStatsUpdate({ type: eventType, sellerId: product.sellerId, productId: product.id });

    res.status(201).json({ success: true });
  } catch (error) {
    console.error('trackProductFunnelEvent Error:', error.message);
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.addReview = async (req, res) => {
  try {
    const { id: productId } = req.params;
    const { rating, comment, orderId } = req.body;
    const customerId = req.user.id;

    if (!rating || rating < 1 || rating > 5) {
      return res.status(400).json({ message: 'Rating must be between 1 and 5' });
    }

    const product = await Product.findByPk(productId);
    if (!product) {
       return res.status(404).json({ message: 'Product not found' });
    }

    // Verify successful purchase before allowing feedback
    const verifiedOrder = await OrderItem.findOne({
      where: { productId },
      include: [{
        model: Order,
        where: {
          customerId,
          status: { [Op.in]: ['Delivered', 'Completed'] }
        },
        required: true
      }],
      order: [[Order, 'createdAt', 'DESC']]
    });

    if (!verifiedOrder) {
      return res.status(403).json({ 
        message: 'Verified Purchase Required: Only customers who have received this masterpiece can share their heritage journey.' 
      });
    }

    const finalOrderId = orderId || verifiedOrder.orderId;

    let review = await Review.findOne({
      where: {
        productId,
        customerId
      }
    });

    const imagesStr = req.body.images ? JSON.stringify(req.body.images) : null;

    if (review) {
      review.rating = parseInt(rating, 10);
      review.comment = comment;
      review.images = imagesStr;
      await review.save();
    } else {
      review = await Review.create({
        productId,
        customerId,
        orderId: finalOrderId,
        rating: parseInt(rating, 10),
        comment,
        images: imagesStr
      });
    }

    await emitReviewUpdated({ productId, sellerId: product.sellerId, reviewId: review.id });

    res.status(200).json({ success: true, data: review, message: 'Review successfully updated' });
  } catch (error) {
    if (error.name === 'SequelizeUniqueConstraintError') {
      return res.status(409).json({ success: false, message: 'You have already reviewed this product for this order.' });
    }
    res.status(error.statusCode || 500).json({ success: false, message: error.message });
  }
};
