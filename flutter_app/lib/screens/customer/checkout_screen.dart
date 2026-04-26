import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/cart_provider.dart';
import '../../providers/auth_provider.dart';
import '../../config/app_theme.dart';
import '../../services/api_client.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  bool _isPlacingOrder = false;
  final _addressController = TextEditingController();

  @override
  void initState() {
    super.initState();
    final user = context.read<AuthProvider>().user;
    _addressController.text = user?.address ?? '';
  }

  Future<void> _placeOrder() async {
    if (_addressController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please provide a shipping address.')));
      return;
    }

    setState(() => _isPlacingOrder = true);
    final cart = context.read<CartProvider>();
    final user = context.read<AuthProvider>().user;

    try {
      // Mirroring backend /orders POST structure
      final res = await ApiClient().post('/orders', data: {
        'userId': user?.id,
        'items': cart.items.map((item) => {
          'productId': item.id,
          'quantity': item.quantity,
          'price': item.price,
          'size': item.size,
          'variation': item.variation,
        }).toList(),
        'shippingAddress': _addressController.text,
        'paymentMethod': 'Cash on Delivery',
        'total': cart.items.fold<double>(0, (sum, item) => sum + (item.price * item.quantity)),
      });

      if (res.statusCode == 201 || res.statusCode == 200) {
        cart.clear();
        if (mounted) {
          showDialog(
            context: context,
            barrierDismissible: false,
            builder: (c) => AlertDialog(
              title: const Text('Mabuhay!'),
              content: const Text('Your order has been placed successfully. Our artisans are now preparing your heritage pieces.'),
              actions: [
                TextButton(onPressed: () => Navigator.of(context).popUntil((route) => route.isFirst), child: const Text('BACK TO HOME')),
              ],
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Failed to place order. Please try again.')));
      }
    } finally {
      if (mounted) setState(() => _isPlacingOrder = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartProvider>();
    double subtotal = 0;
    for (var item in cart.items) {
      subtotal += item.price * item.quantity;
    }

    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(title: const Text('Checkout')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionTitle('SHIPPING ADDRESS'),
            const SizedBox(height: 12),
            TextField(
              controller: _addressController,
              maxLines: 3,
              decoration: const InputDecoration(hintText: 'Enter your full shipping address'),
            ),
            const SizedBox(height: 32),
            _sectionTitle('PAYMENT METHOD'),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppTheme.primary.withValues(alpha: 0.3))),
              child: const Row(
                children: [
                  Icon(Icons.payments_outlined, color: AppTheme.primary),
                  SizedBox(width: 16),
                  Text('Cash on Delivery', style: TextStyle(fontWeight: FontWeight.bold)),
                  Spacer(),
                  Icon(Icons.check_circle, color: AppTheme.primary, size: 20),
                ],
              ),
            ),
            const SizedBox(height: 32),
            _sectionTitle('ORDER SUMMARY'),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppTheme.border)),
              child: Column(
                children: [
                  _summaryRow('Subtotal', '₱${subtotal.toStringAsFixed(0)}'),
                  const Divider(height: 32),
                  _summaryRow('Total', '₱${subtotal.toStringAsFixed(0)}', isTotal: true),
                ],
              ),
            ),
            const SizedBox(height: 48),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isPlacingOrder ? null : _placeOrder,
                child: _isPlacingOrder 
                  ? const CircularProgressIndicator(color: Colors.white) 
                  : const Text('PLACE ORDER'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(String title) {
    return Text(title, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, letterSpacing: 2, color: AppTheme.textMuted));
  }

  Widget _summaryRow(String label, String value, {bool isTotal = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(fontWeight: isTotal ? FontWeight.bold : FontWeight.normal)),
        Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: isTotal ? 20 : 14, color: isTotal ? AppTheme.primary : AppTheme.charcoal)),
      ],
    );
  }
}
