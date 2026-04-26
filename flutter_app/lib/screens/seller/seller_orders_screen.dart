import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/order_model.dart';
import '../../services/api_client.dart';
import '../../config/app_theme.dart';

class SellerOrdersScreen extends StatefulWidget {
  const SellerOrdersScreen({super.key});

  @override
  State<SellerOrdersScreen> createState() => _SellerOrdersScreenState();
}

class _SellerOrdersScreenState extends State<SellerOrdersScreen> {
  List<OrderModel> _orders = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchOrders();
  }

  Future<void> _fetchOrders() async {
    setState(() => _isLoading = true);
    try {
      // In the web version, sellers get their relevant orders
      final res = await ApiClient().get('/orders/seller');
      if (res.statusCode == 200) {
        final List data = res.data;
        setState(() {
          _orders = data.map((e) => OrderModel.fromJson(e)).toList();
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _updateStatus(String orderId, String newStatus) async {
    try {
      final res = await ApiClient().put('/orders/$orderId/status', data: {'status': newStatus});
      if (res.statusCode == 200) {
        _fetchOrders();
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Order marked as $newStatus')));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Failed to update status.')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('Manage Orders')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primary))
          : RefreshIndicator(
              onRefresh: _fetchOrders,
              child: ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: _orders.length,
                separatorBuilder: (c, i) => const SizedBox(height: 16),
                itemBuilder: (c, i) => _buildSellerOrderCard(_orders[i]),
              ),
            ),
    );
  }

  Widget _buildSellerOrderCard(OrderModel order) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppTheme.border)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('ORDER #${order.id}', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
              _statusBadge(order.status),
            ],
          ),
          const Divider(height: 32),
          ...order.items.map((item) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Row(
              children: [
                Expanded(child: Text(item.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                Text('x${item.quantity}', style: const TextStyle(color: AppTheme.textMuted)),
              ],
            ),
          )),
          const Divider(height: 32),
          Text('SHIPPING TO:', style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
          const SizedBox(height: 4),
          Text(order.shippingAddress, style: const TextStyle(fontSize: 12)),
          const Divider(height: 32),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(DateFormat('MMM dd, hh:mm a').format(order.createdAt), style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
              _buildActionMenu(order),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildActionMenu(OrderModel order) {
    return PopupMenuButton<String>(
      onSelected: (status) => _updateStatus(order.id, status),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(color: AppTheme.primary, borderRadius: BorderRadius.circular(8)),
        child: const Text('UPDATE STATUS', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
      ),
      itemBuilder: (context) => [
        const PopupMenuItem(value: 'confirmed', child: Text('Confirm Order')),
        const PopupMenuItem(value: 'shipping', child: Text('Mark as Shipping')),
        const PopupMenuItem(value: 'delivered', child: Text('Mark as Delivered')),
        const PopupMenuItem(value: 'cancelled', child: Text('Cancel Order', style: TextStyle(color: Colors.red))),
      ],
    );
  }

  Widget _statusBadge(String status) {
    // Same as customer version but potentially more colorful
    Color color;
    switch (status.toLowerCase()) {
      case 'pending': color = Colors.orange; break;
      case 'confirmed': color = Colors.blue; break;
      case 'shipping': color = Colors.purple; break;
      case 'delivered': color = Colors.green; break;
      case 'cancelled': color = Colors.red; break;
      default: color = AppTheme.textMuted;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(4)),
      child: Text(status.toUpperCase(), style: TextStyle(color: color, fontSize: 9, fontWeight: FontWeight.bold)),
    );
  }
}
