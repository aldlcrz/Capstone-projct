const User = require('./User');
const Product = require('./Product');
const Order = require('./Order');
const OrderItem = require('./OrderItem');
const Category = require('./Category');
const Message = require('./Message');
const Notification = require('./Notification');
const Address = require('./Address');
const SystemSetting = require('./SystemSetting');
const RefundRequest = require('./RefundRequest');
const ProductView = require('./ProductView');
const Review = require('./Review');
const SellerFunnelEvent = require('./SellerFunnelEvent');
const Report = require('./Report');

// Associations

// User <-> Product (Seller relationship)
User.hasMany(Product, { foreignKey: 'sellerId', as: 'sellerProducts' });
Product.belongsTo(User, { foreignKey: 'sellerId', as: 'seller' });

// User <-> Order (Customer relationship)
User.hasMany(Order, { foreignKey: 'customerId', as: 'customerOrders' });
Order.belongsTo(User, { foreignKey: 'customerId', as: 'customer' });

// User <-> Order (Seller relationship)
User.hasMany(Order, { foreignKey: 'sellerId', as: 'sellerOrders' });
Order.belongsTo(User, { foreignKey: 'sellerId', as: 'seller' });

// Order <-> OrderItem
Order.hasMany(OrderItem, { foreignKey: 'orderId', as: 'items' });
OrderItem.belongsTo(Order, { foreignKey: 'orderId' });

// Product <-> OrderItem
Product.hasMany(OrderItem, { foreignKey: 'productId' });
OrderItem.belongsTo(Product, { foreignKey: 'productId', as: 'product' });

// User <-> Address
User.hasMany(Address, { foreignKey: 'userId', as: 'addresses' });
Address.belongsTo(User, { foreignKey: 'userId' });

// User <-> Message (Multiple roles)
User.hasMany(Message, { foreignKey: 'senderId', as: 'sentMessages' });
User.hasMany(Message, { foreignKey: 'receiverId', as: 'receivedMessages' });
Message.belongsTo(User, { foreignKey: 'senderId', as: 'sender' });
Message.belongsTo(User, { foreignKey: 'receiverId', as: 'receiver' });

// User <-> Notification
User.hasMany(Notification, { foreignKey: 'userId', as: 'notifications' });
Notification.belongsTo(User, { foreignKey: 'userId' });


// Order <-> RefundRequest
Order.hasMany(RefundRequest, { foreignKey: 'orderId', as: 'refunds' });
RefundRequest.belongsTo(Order, { foreignKey: 'orderId' });

// OrderItem <-> RefundRequest
OrderItem.hasMany(RefundRequest, { foreignKey: 'orderItemId', as: 'refunds' });
RefundRequest.belongsTo(OrderItem, { foreignKey: 'orderItemId' });

// User <-> RefundRequest (Customer)
User.hasMany(RefundRequest, { foreignKey: 'customerId', as: 'customerRefunds' });
RefundRequest.belongsTo(User, { foreignKey: 'customerId', as: 'customer' });

// User <-> RefundRequest (Seller)
User.hasMany(RefundRequest, { foreignKey: 'sellerId', as: 'sellerRefunds' });
RefundRequest.belongsTo(User, { foreignKey: 'sellerId', as: 'seller' });

// Category Hierarchical
Category.hasMany(Category, { as: 'children', foreignKey: 'parentId' });
Category.belongsTo(Category, { as: 'parent', foreignKey: 'parentId' });

// Product <-> ProductView
Product.hasMany(ProductView, { foreignKey: 'productId', as: 'productViews' });
ProductView.belongsTo(Product, { foreignKey: 'productId' });

// User <-> ProductView (as Seller)
User.hasMany(ProductView, { foreignKey: 'sellerId', as: 'sellerViews' });
ProductView.belongsTo(User, { foreignKey: 'sellerId', as: 'seller' });

// Product <-> SellerFunnelEvent
Product.hasMany(SellerFunnelEvent, { foreignKey: 'productId', as: 'funnelEvents' });
SellerFunnelEvent.belongsTo(Product, { foreignKey: 'productId', as: 'product' });

// User <-> SellerFunnelEvent (as Seller)
User.hasMany(SellerFunnelEvent, { foreignKey: 'sellerId', as: 'sellerFunnelEvents' });
SellerFunnelEvent.belongsTo(User, { foreignKey: 'sellerId', as: 'seller' });

// User <-> SellerFunnelEvent (as Customer)
User.hasMany(SellerFunnelEvent, { foreignKey: 'customerId', as: 'customerFunnelEvents' });
SellerFunnelEvent.belongsTo(User, { foreignKey: 'customerId', as: 'customer' });

// Product <-> Review
Product.hasMany(Review, { foreignKey: 'productId', as: 'reviews' });
Review.belongsTo(Product, { foreignKey: 'productId', as: 'product' });

// User <-> Review (Customer)
User.hasMany(Review, { foreignKey: 'customerId', as: 'givenReviews' });
Review.belongsTo(User, { foreignKey: 'customerId', as: 'customer' });

// Order <-> Review
Order.hasMany(Review, { foreignKey: 'orderId', as: 'reviews' });
Review.belongsTo(Order, { foreignKey: 'orderId', as: 'order' });

// User <-> Report (Reporter)
User.hasMany(Report, { foreignKey: 'reporterId', as: 'filedReports' });
Report.belongsTo(User, { foreignKey: 'reporterId', as: 'reporter' });

// User <-> Report (Reported)
User.hasMany(Report, { foreignKey: 'reportedId', as: 'receivedReports' });
Report.belongsTo(User, { foreignKey: 'reportedId', as: 'reportedUser' });

const sequelize = require('../config/db');

module.exports = {
  User,
  Product,
  Order,
  OrderItem,
  Category,
  Message,
  Notification,
  Address,
  SystemSetting,
  RefundRequest,
  ProductView,
  Review,
  SellerFunnelEvent,
  Report,
  sequelize,
};
