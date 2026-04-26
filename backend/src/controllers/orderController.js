const sequelize = require('../config/db');
const { Order, OrderItem, Product, User, Address, Review, SystemSetting } = require('../models');

const { sendNotification } = require('../utils/notificationHelper');
const socketUtility = require('../utils/socketUtility');
const { jsonToCsv } = require('../utils/csvHelper');
const {
  emitInventoryUpdated,
  emitOrderCreated,
  emitOrderUpdated,
} = require('../utils/socketUtility');
const {
  validateAddressPayload,
  validatePaymentReference,
} = require('../utils/inputValidation');

const ORDER_STATUSES = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Received by Buyer', 'Completed', 'Cancelled'];
const CANCELABLE_ORDER_STATUSES = ['processing', 'to ship', 'pending'];

const normalizeOrderStatus = (status) => String(status || '').trim().toLowerCase();

const createHttpError = (statusCode, message) => {
  const error = new Error(message);
  error.statusCode = statusCode;
  return error;
};

const parsePrice = (value) => {
  if (typeof value === 'number') return value;
  return parseFloat(String(value || '0').replace(/[^0-9.]/g, '')) || 0;
};

const orderIncludes = [
  { model: User, as: 'customer', attributes: ['id', 'name', 'email'] },
  { model: User, as: 'seller', attributes: ['id', 'name', 'email', 'isVerified'] },
  {
    model: OrderItem,
    as: 'items',
    include: [{ model: Product, as: 'product' }],
  },
  {
    model: Review,
    as: 'reviews',
    attributes: ['productId', 'rating', 'comment', 'images']
  }
];

const fetchOrderWithRelations = (orderId) =>
  Order.findByPk(orderId, {
    include: orderIncludes,
  });

const restockOrderItems = async (orderId, transaction) => {
  const orderItems = await OrderItem.findAll({
    where: { orderId },
    transaction,
  });

  const restockedProducts = [];

  for (const item of orderItems) {
    const product = await Product.findByPk(item.productId, {
      transaction,
      lock: transaction.LOCK.UPDATE,
    });

    if (!product) continue;

    // Handle per-size restock if sizes are objects with stock
    let sizes = product.sizes;
    if (typeof sizes === 'string') {
      try { sizes = JSON.parse(sizes); } catch (e) { sizes = []; }
    }

    if (Array.isArray(sizes) && sizes.length > 0 && typeof sizes[0] === 'object') {
      const sizeIndex = sizes.findIndex(s => s.size === item.size || s.name === item.size);
      if (sizeIndex !== -1) {
        sizes[sizeIndex].stock = (Number(sizes[sizeIndex].stock) || 0) + item.quantity;
        product.sizes = sizes;
      }
    }

    product.stock += item.quantity;
    await product.save({ transaction });
    restockedProducts.push(product);
  }

  return restockedProducts;
};

exports.createOrder = async (req, res) => {
  let transaction;
  let committed = false;

  try {
    transaction = await sequelize.transaction();
    let { items, shippingAddress, addressId, paymentMethod, paymentReference, paymentProof } = req.body;
    const customerId = req.user.id;
    const userRole = req.user.role;
    const userStatus = req.user.status;

    console.log(`[CreateOrder] Initializing for customer ${customerId} (${userRole})`);

    if (userStatus === 'blocked' || userStatus === 'frozen') {
      throw createHttpError(403, `This account is ${userStatus} and cannot place new orders.`);
    }

    if (userRole === 'admin') {
      throw createHttpError(403, 'Admins are not allowed to place orders');
    }

    // Check if admin has paused all orders (maintenance mode)
    const maintenanceSetting = await SystemSetting.findOne({ where: { key: 'maintenanceMode' } });
    const isMaintenanceMode = maintenanceSetting?.value === true ||
      maintenanceSetting?.value === 'true' ||
      maintenanceSetting?.value === '"true"';
    if (isMaintenanceMode) {
      throw createHttpError(503, 'Orders are temporarily paused for maintenance. Please try again later.');
    }

    if (typeof items === 'string') items = JSON.parse(items);
    
    if (addressId && !shippingAddress) {
      const addressRecord = await Address.findOne({ where: { id: addressId, userId: customerId } });
      if (!addressRecord) {
        throw createHttpError(404, 'Selected address not found');
      }
      shippingAddress = addressRecord.toJSON();
    } else if (typeof shippingAddress === 'string') {
      shippingAddress = JSON.parse(shippingAddress);
    }

    if (!shippingAddress) {
      throw createHttpError(400, 'Shipping address or addressId is required');
    }

    const customerRecord = await User.findByPk(customerId, { transaction });
    shippingAddress = validateAddressPayload(shippingAddress);

    // FULL STATE SNAPSHOT: Prevent UI breakdown if User/Product relations are mutated/deleted later
    shippingAddress.customerName = customerRecord?.name || shippingAddress.recipientName || 'Unknown Customer';
    shippingAddress.contactNumber = customerRecord?.mobileNumber || shippingAddress.phone || '';

    if (!Array.isArray(items) || items.length === 0) {
      throw createHttpError(400, 'Order items are required');
    }
    console.log(`[CreateOrder] Validated address and items count: ${items.length}`);

    const validPaymentMethods = ['GCash', 'Maya'];
    if (!validPaymentMethods.includes(paymentMethod)) {
      throw createHttpError(400, 'Invalid payment method selected. Only GCash and Maya are supported.');
    }

    if (paymentMethod === 'GCash' || paymentMethod === 'Maya') {
      paymentReference = validatePaymentReference(paymentReference);
    }


    const preparedItems = [];
    let calculatedTotalPrice = 0;
    let sellerId = null;

    for (const item of items) {
      const productId = item.productId || item.id || item.product;
      const quantity = Math.floor(Number(item.quantity)) || 0;

      if (quantity <= 0) {
        throw createHttpError(400, 'All item quantities must be greater than zero');
      }

      const product = await Product.findByPk(productId, {
        transaction,
        lock: transaction.LOCK.UPDATE,
      });

      if (!product) {
        throw createHttpError(404, `Artisan product not found: ${productId}`);
      }

      // Check per-size stock if sizes are objects
      let sizes = product.sizes;
      if (typeof sizes === 'string') {
        try { sizes = JSON.parse(sizes); } catch (e) { sizes = []; }
      }

      if (Array.isArray(sizes) && sizes.length > 0 && typeof sizes[0] === 'object') {
        const sizeInfo = sizes.find(s => s.size === item.size || s.name === item.size);
        if (sizeInfo) {
          if (Number(sizeInfo.stock) < quantity) {
            throw createHttpError(400, `Size ${item.size} of ${product.name} is out of stock`);
          }
        }
      }

      if (product.stock < quantity) {
        throw createHttpError(400, `${product.name} does not have enough total stock`);
      }

      if (sellerId && String(sellerId) !== String(product.sellerId)) {
        throw createHttpError(400, 'Orders can only contain products from one seller');
      }

      sellerId = sellerId || product.sellerId;
      const unitPrice = Number(product.price) || 0;
      const itemTotal = unitPrice * quantity;
      calculatedTotalPrice += itemTotal;

      // Backend security check: Ensure product allows the selected payment method
      if (paymentMethod === 'GCash' && (product.allowGcash === false || product.allowGcash === 0)) {
        throw createHttpError(400, `The product "${product.name}" does not support GCash payments.`);
      }
      if (paymentMethod === 'Maya' && (product.allowMaya === false || product.allowMaya === 0)) {
        throw createHttpError(400, `The product "${product.name}" does not support Maya payments.`);
      }

      preparedItems.push({
        product,
        productId: product.id,
        quantity,
        price: unitPrice,
        size: item.size || 'M',
        variation: item.variation || 'Original',
      });
    }
    console.log(`[CreateOrder] Prepared ${preparedItems.length} items. Total price: ${calculatedTotalPrice}`);

    // Add delivery fee if applicable (assuming 0 for now as it wasn't specified, but space is left for logic)
    const deliveryFee = 0; 
    const finalTotal = calculatedTotalPrice + deliveryFee;

    shippingAddress.itemSnapshots = preparedItems.map(item => ({
      name: item.product.name,
      price: item.price,
      quantity: item.quantity,
      size: item.size,
      variation: item.variation
    }));

    const order = await Order.create(
      {
        customerId,
        sellerId,
        totalAmount: finalTotal,
        shippingAddress,
        paymentMethod,
        paymentReference,
        paymentProof: req.file ? `/uploads/payments/${req.file.filename}` : paymentProof,
        status: 'Pending',
      },
      { transaction }
    );
    console.log(`[CreateOrder] Order record created with ID: ${order.id}`);

    for (const item of preparedItems) {
      // Decrement per-size stock
      let sizes = item.product.sizes;
      if (typeof sizes === 'string') {
        try { sizes = JSON.parse(sizes); } catch (e) { sizes = []; }
      }

      if (Array.isArray(sizes) && sizes.length > 0 && typeof sizes[0] === 'object') {
        const sizeIndex = sizes.findIndex(s => s.size === item.size || s.name === item.size);
        if (sizeIndex !== -1) {
          sizes[sizeIndex].stock = (Number(sizes[sizeIndex].stock) || 0) - item.quantity;
          item.product.sizes = sizes;
        }
      }

      item.product.stock -= item.quantity;
      await item.product.save({ transaction });

      await OrderItem.create(
        {
          orderId: order.id,
          productId: item.productId,
          quantity: item.quantity,
          price: item.price,
          size: item.size,
          variation: item.variation,
        },
        { transaction }
      );
    }

    await transaction.commit();
    committed = true;

    const fullOrder = await fetchOrderWithRelations(order.id);
    emitOrderCreated(fullOrder);

    const updatedProducts = new Map();
    preparedItems.forEach(({ product }) => {
      updatedProducts.set(product.id, product);
    });
    updatedProducts.forEach((product) => {
      emitInventoryUpdated(product, { action: 'updated' });
    });

    await Promise.all([
      sendNotification(
        customerId,
        'Order placed',
        'Your order has been placed successfully and is awaiting confirmation.',
        'order',
        '/orders',
        'customer'
      ),
      sendNotification(
        sellerId,
        'New order received',
        'A customer has placed a new order in your shop.',
        'order',
        '/seller/orders',
        'seller'
      ),
    ]);

    res.status(201).json(fullOrder);
  } catch (error) {
    if (!committed && transaction) {
      await transaction.rollback();
    }
    console.error('Order Placement Failed Stack:', error.stack || error);
    res.status(error.statusCode || 500).json({ 
      message: error.message || 'Internal Server Error',
      error: process.env.NODE_ENV === 'development' ? error.stack : undefined
    });
  }
};

exports.cancelOrder = async (req, res) => {
  try {
    const { reason } = req.body;
    if (!reason) {
      throw createHttpError(400, 'Cancellation reason is required');
    }

    const order = await Order.findByPk(req.params.id);

    if (!order) throw createHttpError(404, 'Order not found');
    if (String(order.customerId) !== String(req.user.id)) {
      throw createHttpError(403, 'You can only cancel your own orders');
    }
    if (!CANCELABLE_ORDER_STATUSES.includes(normalizeOrderStatus(order.status))) {
      throw createHttpError(400, 'This order can no longer be cancelled');
    }

    const previousStatus = order.status;
    order.status = 'Cancellation Pending';
    order.cancellationReason = reason;
    await order.save();

    const fullOrder = await fetchOrderWithRelations(order.id);
    emitOrderUpdated(fullOrder, {
      action: 'cancellation-requested',
      previousStatus,
    });

    await sendNotification(
      order.sellerId,
      'Order Cancellation Requested',
      `A customer has requested to cancel order LB-OR-${order.id.toString().slice(-8).toUpperCase()}. Reason: ${reason}`,
      'order',
      '/seller/orders',
      'seller'
    );

    res.status(200).json(fullOrder);
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.approveCancellation = async (req, res) => {
  const transaction = await sequelize.transaction();
  let committed = false;

  try {
    const order = await Order.findByPk(req.params.id, {
      transaction,
      lock: transaction.LOCK.UPDATE,
    });

    if (!order) throw createHttpError(404, 'Order not found');
    if (String(order.sellerId) !== String(req.user.id)) {
      throw createHttpError(403, 'Only the seller can approve cancellation');
    }
    if (order.status !== 'Cancellation Pending') {
      throw createHttpError(400, 'This order does not have a pending cancellation request');
    }

    const previousStatus = order.status;
    order.status = 'Cancelled';
    await order.save({ transaction });

    const restockedProducts = await restockOrderItems(order.id, transaction);
    await transaction.commit();
    committed = true;

    const fullOrder = await fetchOrderWithRelations(order.id);
    emitOrderUpdated(fullOrder, {
      action: 'cancelled',
      previousStatus,
    });
    restockedProducts.forEach((product) => {
      emitInventoryUpdated(product, { action: 'restocked' });
    });

    await sendNotification(
      order.customerId,
      'Order Cancellation Approved',
      'Your order cancellation request has been approved.',
      'order',
      '/orders',
      'customer'
    );

    res.status(200).json(fullOrder);
  } catch (error) {
    if (!committed) {
      await transaction.rollback();
    }
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.rejectCancellation = async (req, res) => {
  try {
    const order = await Order.findByPk(req.params.id);

    if (!order) throw createHttpError(404, 'Order not found');
    if (String(order.sellerId) !== String(req.user.id)) {
      throw createHttpError(403, 'Only the seller can reject cancellation');
    }
    if (order.status !== 'Cancellation Pending') {
      throw createHttpError(400, 'This order does not have a pending cancellation request');
    }

    const previousStatus = order.status;
    // When rejecting, we move it to 'Processing' or keep it where it was.
    // Since we didn't store the exact previous state before 'Cancellation Pending',
    // we'll default to 'Processing' which is the common state for cancelable orders.
    order.status = 'Processing';
    order.cancellationReason = null; // Clear the reason
    await order.save();

    const fullOrder = await fetchOrderWithRelations(order.id);
    emitOrderUpdated(fullOrder, {
      action: 'cancellation-rejected',
      previousStatus,
    });

    await sendNotification(
      order.customerId,
      'Order Cancellation Rejected',
      'Your order cancellation request has been rejected by the artisan.',
      'order',
      '/orders',
      'customer'
    );

    res.status(200).json(fullOrder);
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.getMyOrders = async (req, res) => {
  try {
    const orders = await Order.findAll({
      where: { customerId: req.user.id },
      include: orderIncludes,
      order: [['createdAt', 'DESC']],
    });

    res.status(200).json(orders);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.exportSellerReport = async (req, res) => {
    try {
        const sellerId = req.user.id;
        
        const [orders, products] = await Promise.all([
            Order.findAll({
                where: { sellerId },
                include: [
                    { model: User, as: 'customer', attributes: ['name', 'email'] },
                    { model: Address, attributes: ['city', 'province'] }
                ]
            }),
            Product.findAll({
                where: { sellerId },
                attributes: ['id', 'name', 'price', 'stock', 'category', 'status']
            })
        ]);

        const orderData = orders.map(o => ({
            Type: 'ORDER',
            ID: o.id,
            Customer: o.customer?.name || 'N/A',
            Total: o.totalAmount,
            Status: o.status,
            Date: o.createdAt.toISOString().split('T')[0],
            Location: o.Address ? `${o.Address.city}, ${o.Address.province}` : 'N/A'
        }));

        const productData = products.map(p => ({
            Type: 'PRODUCT',
            ID: p.id,
            Name: p.name,
            Price: p.price,
            Stock: p.stock,
            Category: p.category,
            Status: p.status,
            Date: '' // Not applicable for products list in this simplified report
        }));

        const combined = [...orderData, ...productData];
        const csv = jsonToCsv(combined);

        res.setHeader('Content-Type', 'text/csv');
        res.setHeader('Content-Disposition', `attachment; filename=seller_report_${new Date().getTime()}.csv`);
        res.status(200).send(csv);
    } catch (err) {
        console.error('Export Error:', err);
        res.status(500).json({ message: 'Error generating report', error: err.message });
    }
};

exports.getSellerOrders = async (req, res) => {
  try {
    const sellerId = req.user.role === 'admin' && req.query.sellerId ? req.query.sellerId : req.user.id;

    const orders = await Order.findAll({
      where: { sellerId },
      include: orderIncludes,
      order: [['createdAt', 'DESC']],
    });

    res.status(200).json(orders);
  } catch (error) {
    console.error('getSellerOrders Error:', error);
    res.status(500).json({ message: error.message });
  }
};

exports.updateOrderStatus = async (req, res) => {
  const transaction = await sequelize.transaction();
  let committed = false;

  try {
    const { status } = req.body;
    if (!ORDER_STATUSES.includes(status)) {
      throw createHttpError(400, 'Invalid order status');
    }

    const order = await Order.findByPk(req.params.id, {
      transaction,
      lock: transaction.LOCK.UPDATE,
    });

    if (!order) throw createHttpError(404, 'Order not found');

    // Customer can only set status to 'Received by Buyer' or 'Completed' (Confirm Receipt)
    if (req.user.role === 'customer') {
      if (String(order.customerId) !== String(req.user.id)) {
        throw createHttpError(403, 'You can only update your own orders');
      }
      if (status !== 'Received by Buyer' && status !== 'Completed') {
        throw createHttpError(403, 'Customers can only confirm receipt of their orders');
      }
    } else if (req.user.role === 'seller') {
      if (String(order.sellerId) !== String(req.user.id)) {
        throw createHttpError(403, 'You can only update your own orders');
      }
    }
    if (normalizeOrderStatus(order.status) === 'cancelled' && status !== 'Cancelled') {
      throw createHttpError(400, 'Cancelled orders can no longer be updated');
    }

    const previousStatus = order.status;
    if (previousStatus === status) {
      await transaction.rollback();
      const unchangedOrder = await fetchOrderWithRelations(order.id);
      return res.status(200).json(unchangedOrder);
    }

    order.status = status;
    await order.save({ transaction });

    let restockedProducts = [];
    if (status === 'Cancelled' && previousStatus !== 'Cancelled') {
      restockedProducts = await restockOrderItems(order.id, transaction);
    }

    await transaction.commit();
    committed = true;

    const fullOrder = await fetchOrderWithRelations(order.id);
    emitOrderUpdated(fullOrder, {
      action: status === 'Cancelled' ? 'cancelled' : 'status-changed',
      previousStatus,
    });
    restockedProducts.forEach((product) => {
      emitInventoryUpdated(product, { action: 'restocked' });
    });

    const statusMessage = {
      Processing: 'Your order is now being prepared for shipment.',
      Shipped: 'Your order has been shipped and is on the way.',
      Delivered: 'Your order has been marked as delivered.',
      'Received by Buyer': 'Thank you for confirming receipt of your order.',
      Completed: 'Your order has been completed.',
      Cancelled: 'Your order has been cancelled.',
    }[status] || `Your order status is now ${status}.`;

    await sendNotification(
      order.customerId,
      `Order ${status}`,
      statusMessage,
      'order',
      '/orders',
      'customer'
    );

    res.status(200).json(fullOrder);
  } catch (error) {
    if (!committed) {
      await transaction.rollback();
    }
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};
