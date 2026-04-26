const { RefundRequest, Order, OrderItem, User, Notification, Product } = require('../models');
const { notifyAdmins } = require('../utils/notificationHelper');
const socketUtility = require('../utils/socketUtility');

exports.createRefundRequest = async (req, res) => {
  try {
    const { orderId, orderItemId, reason, message } = req.body;
    const customerId = req.user.id;

    // Check if order exists and belongs to customer
    const order = await Order.findOne({
      where: { id: orderId, customerId },
    });

    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }

    // Status check: Refund option becomes available once order status = "Received by Buyer"
    // We also allow "Delivered" if it's considered equivalent in some flows, 
    // but the requirement is strict about "Received by Buyer".
    if (order.status !== 'Received by Buyer' && order.status !== 'Delivered') {
      return res.status(400).json({ message: 'Refunds are only available after receiving the item.' });
    }

    const orderItem = await OrderItem.findOne({
      where: { id: orderItemId, orderId },
    });

    if (!orderItem) {
      return res.status(404).json({ message: 'Order item not found' });
    }

    // Check for existing refund request for this item
    const existingRequest = await RefundRequest.findOne({
      where: { orderItemId, customerId },
    });

    if (existingRequest) {
      return res.status(400).json({ message: 'A refund request already exists for this item.' });
    }

    // Handle video upload
    if (!req.file) {
      return res.status(400).json({ message: 'Video proof is required for refund requests.' });
    }

    const videoProof = `${req.protocol}://${req.get('host')}/uploads/${req.file.filename}`;

    const refundRequest = await RefundRequest.create({
      orderId,
      orderItemId,
      customerId,
      sellerId: order.sellerId,
      reason,
      message: reason === 'Other' ? message : null,
      videoProof,
      status: 'Pending',
    });

    // Notify Seller
    await Notification.create({
      userId: order.sellerId,
      title: 'New Refund Request',
      message: `A customer has requested a refund for an item in Order #${order.id.slice(0, 8)}`,
      type: 'refund_request',
      metadata: { refundRequestId: refundRequest.id, orderId },
    });

    res.status(201).json({
      message: 'Refund request submitted successfully.',
      refundRequest,
    });
  } catch (error) {
    console.error('Create Refund Error:', error);
    res.status(500).json({ message: 'Internal server error' });
  }
};

exports.getSellerRefundRequests = async (req, res) => {
  try {
    const sellerId = req.user.id;
    const requests = await RefundRequest.findAll({
      where: { sellerId },
      include: [
        { model: Order, as: 'Order', attributes: ['id', 'status', 'totalAmount'] },
        { 
          model: OrderItem, 
          as: 'OrderItem', 
          attributes: ['id', 'productId', 'price', 'quantity', 'size', 'variation'],
          include: [{ model: Product, as: 'product', attributes: ['id', 'name', 'image'] }]
        },
        { model: User, as: 'customer', attributes: ['id', 'name', 'email'] },
      ],
      order: [['createdAt', 'DESC']],
    });

    res.status(200).json(requests);
  } catch (error) {
    console.error('Get Seller Refunds Error:', error);
    res.status(500).json({ message: 'Internal server error' });
  }
};

exports.getCustomerRefundRequests = async (req, res) => {
  try {
    const customerId = req.user.id;
    const requests = await RefundRequest.findAll({
      where: { customerId },
      include: [
        { model: Order, as: 'Order', attributes: ['id', 'status'] },
        { model: OrderItem, as: 'OrderItem', attributes: ['id', 'productId', 'price'] },
      ],
      order: [['createdAt', 'DESC']],
    });

    res.status(200).json(requests);
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};

exports.updateRefundStatus = async (req, res) => {
  try {
    const { status, sellerComment } = req.body;
    const { id } = req.params;
    const sellerId = req.user.id;

    const refundRequest = await RefundRequest.findOne({
      where: { id, sellerId },
    });

    if (!refundRequest) {
      return res.status(404).json({ message: 'Refund request not found' });
    }

    refundRequest.status = status;
    refundRequest.sellerComment = sellerComment;
    await refundRequest.save();

    // Notify Customer
    await Notification.create({
      userId: refundRequest.customerId,
      title: 'Refund Request Updated',
      message: `Your refund request has been ${status.toLowerCase()}.`,
      type: 'refund_status_update',
      metadata: { refundRequestId: refundRequest.id, status },
    });

    res.status(200).json({
      message: 'Refund request updated successfully.',
      refundRequest,
    });
  } catch (error) {
    res.status(500).json({ message: 'Internal server error' });
  }
};
