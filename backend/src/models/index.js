const User = require('./User');
const Product = require('./Product');
const Order = require('./Order');
const OrderItem = require('./OrderItem');
const Category = require('./Category');
const Message = require('./Message');
const Notification = require('./Notification');
const Address = require('./Address');
const SystemSetting = require('./SystemSetting');
const Wishlist = require('./Wishlist');
const ReturnRequest = require('./ReturnRequest');

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

// Wishlist Associations
User.hasMany(Wishlist, { foreignKey: 'userId', as: 'wishlist' });
Wishlist.belongsTo(User, { foreignKey: 'userId' });
Product.hasMany(Wishlist, { foreignKey: 'productId' });
Wishlist.belongsTo(Product, { foreignKey: 'productId', as: 'product' });

// Order <-> ReturnRequest
Order.hasMany(ReturnRequest, { foreignKey: 'orderId', as: 'returns' });
ReturnRequest.belongsTo(Order, { foreignKey: 'orderId' });

// Category Hierarchical
Category.hasMany(Category, { as: 'children', foreignKey: 'parentId' });
Category.belongsTo(Category, { as: 'parent', foreignKey: 'parentId' });

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
  Wishlist,
  ReturnRequest,
};
