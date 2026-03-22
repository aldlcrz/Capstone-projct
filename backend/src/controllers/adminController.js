const { User, Product, Order, SystemSetting } = require('../models');
const { sendNotification } = require('../utils/notificationHelper');
const { emitUserUpdated } = require('../utils/socketUtility');

exports.getGlobalStats = async (req, res) => {
  try {
    const totalSalesValue = await Order.sum('totalAmount') || 0;
    const totalOrdersCount = await Order.count();
    const totalCustomersCount = await User.count({ where: { role: 'customer' } });
    const totalProductsCount = await Product.count();

    res.status(200).json({
      totalSales: `₱${totalSalesValue.toLocaleString()}`,
      totalOrders: totalOrdersCount.toLocaleString(),
      activeCustomers: totalCustomersCount.toLocaleString(),
      liveProducts: totalProductsCount.toLocaleString()
    });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.getPendingSellers = async (req, res) => {
  try {
    const sellers = await User.findAll({ where: { role: 'seller', isVerified: false } });
    res.status(200).json(sellers);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.getCustomers = async (req, res) => {
  try {
    const customers = await User.findAll({
      where: { role: 'customer' },
      attributes: { exclude: ['password'] },
      order: [['createdAt', 'DESC']],
    });

    res.status(200).json(customers);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.verifySeller = async (req, res) => {
  try {
    const { id } = req.params;
    const user = await User.findByPk(id);
    if (!user) return res.status(404).json({ message: 'Seller not found' });
    
    user.isVerified = true;
    await user.save();

    const publicUser = {
      id: user.id,
      name: user.name,
      email: user.email,
      role: user.role,
      isVerified: user.isVerified,
      profilePhoto: user.profilePhoto,
    };

    emitUserUpdated(publicUser, { action: 'verified' });
    await sendNotification(
      user.id,
      'Seller verification approved',
      'Your artisan workshop is now verified and can access seller tools.',
      'system',
      '/seller/dashboard'
    );

    res.status(200).json({ message: 'Seller verified successfully', user: publicUser });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.getSettings = async (req, res) => {
  try {
    const settings = await SystemSetting.findAll();
    const settingsMap = settings.reduce((acc, s) => {
      acc[s.key] = s.value;
      return acc;
    }, {});
    res.status(200).json(settingsMap);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.updateSettings = async (req, res) => {
  try {
    const updates = req.body; // e.g., { revenueTarget: 500000, automatedSupport: true }
    for (const [key, value] of Object.entries(updates)) {
      await SystemSetting.upsert({ key, value });
    }
    res.status(200).json({ message: 'Settings updated successfully' });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};
