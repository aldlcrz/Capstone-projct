const { Product, User, Order, Message, sequelize } = require('../models');
const { Op } = require('sequelize');
const { emitInventoryUpdated } = require('../utils/socketUtility');

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

const serializeProduct = (req, product) => {
  const plainProduct = product.get ? product.get({ plain: true }) : product;

  const images = parseStoredList(plainProduct.image)
    .map((img) => toPublicImageUrl(req, img))
    .filter(Boolean);

  return {
    ...plainProduct,
    sizes: parseStoredList(plainProduct.sizes).filter(Boolean),
    image: images,
    artisan: plainProduct.seller ? plainProduct.seller.name : undefined
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
      where.category = category;
    }

    if (search) {
      where.name = { [Op.like]: `%${search}%` };
    }

    if (seller) {
      where.sellerId = seller;
    }

    const products = await Product.findAll({
      where,
      include: [{ model: User, as: 'seller', attributes: ['id', 'name'] }],
      order: [['createdAt', 'DESC']],
    });

    res.status(200).json(products.map((product) => serializeProduct(req, product)));
  } catch (error) {
    res.status(500).json({ message: error.message });
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
    res.status(500).json({ message: error.message });
  }
};

exports.getProductById = async (req, res) => {
  try {
    const product = await Product.findByPk(req.params.id, {
      include: [{ model: User, as: 'seller', attributes: ['id', 'name'] }],
    });

    if (!product) {
      return res.status(404).json({ message: 'Product not found' });
    }

    res.status(200).json(serializeProduct(req, product));
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.createProduct = async (req, res) => {
  try {
    const { name, description, price, sizes, category, stock, variationNames, shippingFee, shippingDays } = req.body;

    if (!name || !price || stock === undefined) {
      return res.status(400).json({ message: 'Name, price, and stock are required' });
    }

    if (Number(price) <= 0) {
      return res.status(400).json({ message: 'Price must be a positive number' });
    }

    if (!Number.isInteger(Number(stock)) || Number(stock) < 0) {
      return res.status(400).json({ message: 'Stock must be a non-negative integer' });
    }

    if (shippingFee !== undefined && (isNaN(Number(shippingFee)) || Number(shippingFee) < 0)) {
      return res.status(400).json({ message: 'Shipping fee cannot be negative' });
    }

    if (shippingDays !== undefined && (!Number.isInteger(Number(shippingDays)) || Number(shippingDays) < 1)) {
      return res.status(400).json({ message: 'Shipping days must be at least 1 day' });
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
      name,
      description,
      price,
      sizes: Array.isArray(sizes) ? sizes : JSON.parse(sizes || '[]'),
      category,
      stock,
      shippingFee: shippingFee || 0,
      shippingDays: shippingDays || 3,
      sellerId: req.user.id,
      image: images,
    });

    emitInventoryUpdated(product, { action: 'created' });
    res.status(201).json(serializeProduct(req, product));
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.updateProduct = async (req, res) => {
  try {
    const product = await getProductLookup(req, req.params.id);
    if (!product) {
      return res.status(404).json({ message: 'Product not found or access denied' });
    }

    const { name, description, price, sizes, category, stock, variationNames, existingImages, shippingFee, shippingDays } = req.body;

    if (price !== undefined && Number(price) <= 0) {
      return res.status(400).json({ message: 'Price must be a positive number' });
    }

    if (stock !== undefined && (!Number.isInteger(Number(stock)) || Number(stock) < 0)) {
      return res.status(400).json({ message: 'Stock must be a non-negative integer' });
    }

    if (shippingFee !== undefined && (isNaN(Number(shippingFee)) || Number(shippingFee) < 0)) {
      return res.status(400).json({ message: 'Shipping fee cannot be negative' });
    }

    if (shippingDays !== undefined && (!Number.isInteger(Number(shippingDays)) || Number(shippingDays) < 1)) {
      return res.status(400).json({ message: 'Shipping days must be at least 1 day' });
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
      name: name ?? product.name,
      description: description ?? product.description,
      price: price ?? product.price,
      sizes: sizes ? (Array.isArray(sizes) ? sizes : JSON.parse(sizes)) : product.sizes,
      category: category ?? product.category,
      stock: stock ?? product.stock,
      shippingFee: shippingFee ?? product.shippingFee,
      shippingDays: shippingDays ?? product.shippingDays,
      image: images,
    });

    emitInventoryUpdated(product, { action: 'updated' });
    res.status(200).json(serializeProduct(req, product));
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.deleteProduct = async (req, res) => {
  try {
    const product = await getProductLookup(req, req.params.id);
    if (!product) {
      return res.status(404).json({ message: 'Product not found' });
    }

    const deletedProduct = product.get({ plain: true });
    await product.destroy();

    emitInventoryUpdated(deletedProduct, {
      action: 'deleted',
      stock: 0,
      product: deletedProduct,
    });

    res.status(200).json({ message: 'Product deleted successfully' });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.getSellerStats = async (req, res) => {
  try {
    const sellerId = req.user.id;

    const [totalRevenue, totalOrders, totalInventory, lowStock, inquiries] = await Promise.all([
      Order.sum('totalAmount', {
        where: {
          sellerId,
          status: { [Op.ne]: 'Cancelled' },
        },
      }),
      Order.count({ where: { sellerId } }),
      Product.count({ where: { sellerId } }),
      Product.count({
        where: {
          sellerId,
          stock: { [Op.lt]: 5 },
        },
      }),
      Message.count({
        where: {
          receiverId: sellerId,
          read: false,
        },
      }),
    ]);

    // Group orders by month for the last 6 months
    const sixMonthsAgo = new Date();
    sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 5); // Start of the 6-month window
    sixMonthsAgo.setDate(1);
    sixMonthsAgo.setHours(0, 0, 0, 0);

    const ordersTrend = await Order.findAll({
      attributes: ['totalAmount', 'createdAt'],
      where: {
        sellerId,
        status: { [Op.ne]: 'Cancelled' },
        createdAt: { [Op.gte]: sixMonthsAgo }
      },
      order: [['createdAt', 'ASC']]
    });

    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const performanceMap = {};
    
    // Initialize last 6 months with 0
    for (let i = 0; i < 6; i++) {
      const d = new Date();
      d.setMonth(d.getMonth() - i);
      performanceMap[monthNames[d.getMonth()]] = 0;
    }

    ordersTrend.forEach(o => {
      const m = monthNames[new Date(o.createdAt).getMonth()];
      if (performanceMap[m] !== undefined) {
        performanceMap[m] += Number(o.totalAmount || 0);
      }
    });

    const performanceData = Object.keys(performanceMap)
      .map(name => ({ name, sales: performanceMap[name] }))
      .reverse(); // Standard chronological order

    res.status(200).json({
      revenue: Number(totalRevenue || 0),
      orders: totalOrders,
      inquiries,
      products: totalInventory,
      lowStock,
      performance: performanceData
    });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};
