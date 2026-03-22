const { Wishlist, Product, User } = require('../models');

exports.toggleWishlist = async (req, res) => {
  try {
    const { productId } = req.body;
    const userId = req.user.id;

    if (!productId) {
      return res.status(400).json({ message: 'Product ID is required' });
    }

    const existing = await Wishlist.findOne({
      where: { userId, productId }
    });

    if (existing) {
      await existing.destroy();
      return res.status(200).json({ message: 'Removed from wishlist', action: 'removed' });
    } else {
      const wishlistItem = await Wishlist.create({ userId, productId });
      return res.status(201).json({ message: 'Added to wishlist', action: 'added', wishlistItem });
    }
  } catch (error) {
    console.error('Wishlist Toggle Error:', error);
    res.status(500).json({ message: 'Error updating wishlist' });
  }
};

exports.getMyWishlist = async (req, res) => {
  try {
    const userId = req.user.id;
    const wishlist = await Wishlist.findAll({
      where: { userId },
      include: [{
        model: Product,
        as: 'product',
        include: [{ model: User, as: 'seller', attributes: ['name'] }]
      }],
      order: [['createdAt', 'DESC']]
    });

    res.status(200).json(wishlist);
  } catch (error) {
    console.error('Fetch Wishlist Error:', error);
    res.status(500).json({ message: 'Error fetching wishlist' });
  }
};
