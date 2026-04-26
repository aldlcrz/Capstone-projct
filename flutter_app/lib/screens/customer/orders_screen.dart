import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/auth_provider.dart';
import '../../models/order_model.dart';
import '../../services/api_client.dart';
import '../../config/app_theme.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
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
      final user = context.read<AuthProvider>().user;
      final res = await ApiClient().get('/orders/user/${user?.id}');
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('My Heritage Collection')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primary))
          : _orders.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _fetchOrders,
                  child: ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: _orders.length,
                    separatorBuilder: (c, i) => const SizedBox(height: 16),
                    itemBuilder: (c, i) => _buildOrderCard(_orders[i]),
                  ),
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.history, size: 64, color: AppTheme.textMuted),
          const SizedBox(height: 16),
          const Text('No orders yet.', style: TextStyle(color: AppTheme.textMuted)),
        ],
      ),
    );
  }

  Widget _buildOrderCard(OrderModel order) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('ORDER #${order.id}', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 1)),
              _statusBadge(order.status),
            ],
          ),
          const Divider(height: 32),
          ...order.items.take(2).map((item) => Padding(
            padding: const EdgeInsets.only(bottom: 8.0),
            child: Row(
              children: [
                Expanded(child: Text(item.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                Text('x${item.quantity}', style: const TextStyle(color: AppTheme.textMuted)),
              ],
            ),
          )),
          if (order.items.length > 2)
            Text('+ ${order.items.length - 2} more items', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted, fontStyle: FontStyle.italic)),
          const Divider(height: 32),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(DateFormat('MMM dd, yyyy').format(order.createdAt), style: const TextStyle(fontSize: 12, color: AppTheme.textMuted)),
              Text('₱${order.total.toStringAsFixed(0)}', style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primary, fontSize: 18)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _statusBadge(String status) {
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
