import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../providers/cart_provider.dart';
import '../../config/app_theme.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    debugPrint('Cart building...');
    final cart = context.watch<CartProvider>();
    final items = cart.items;

    // Group items by Artisan
    final groupedItems = <String, List<int>>{};
    for (int i = 0; i < items.length; i++) {
      final artisan = items[i].artisan ?? 'Heritage Workshop';
      groupedItems.putIfAbsent(artisan, () => []).add(i);
    }

    double subtotal = 0;
    for (var item in items) {
      subtotal += item.price * item.quantity;
    }

    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(
        title: Text(
          'My Collection',
          style: GoogleFonts.playfairDisplay(fontWeight: FontWeight.bold),
        ),
      ),
      body: items.isEmpty
          ? _buildEmptyState(context)
          : Column(
              children: [
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: groupedItems.length,
                    itemBuilder: (context, index) {
                      final artisan = groupedItems.keys.elementAt(index);
                      final itemIndices = groupedItems[artisan]!;
                      return _buildArtisanGroup(context, artisan, itemIndices, items, cart);
                    },
                  ),
                ),
                _buildSummary(context, subtotal),
              ],
            ),
    );
  }

  Widget _buildEmptyState(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.shopping_bag_outlined, size: 80, color: AppTheme.textMuted),
          const SizedBox(height: 24),
          Text(
            'Your collection is empty',
            style: GoogleFonts.playfairDisplay(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          const Text('Discover authentic heritage pieces.', style: TextStyle(color: AppTheme.textMuted)),
          const SizedBox(height: 32),
          ElevatedButton(
            onPressed: () => context.go('/home'),
            child: const Text('START SHOPPING'),
          ),
        ],
      ),
    );
  }

  Widget _buildArtisanGroup(BuildContext context, String artisan, List<int> indices, List<CartItem> items, CartProvider cart) {
    return Container(
      margin: const EdgeInsets.only(bottom: 24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Artisan Header
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(color: AppTheme.charcoal, borderRadius: BorderRadius.circular(8)),
                  alignment: Alignment.center,
                  child: Text(artisan[0], style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                ),
                const SizedBox(width: 12),
                Text(artisan, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                const Spacer(),
                const Icon(Icons.verified, color: Colors.green, size: 14),
              ],
            ),
          ),
          const Divider(height: 1, color: AppTheme.border),
          // Items
          ...indices.map((idx) {
            final item = items[idx];
            return Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  // Image
                  Container(
                    width: 70,
                    height: 70,
                    decoration: BoxDecoration(
                      color: AppTheme.background,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppTheme.border),
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: CachedNetworkImage(
                        imageUrl: item.image,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  // Details
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(item.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            _tag(item.size),
                            const SizedBox(width: 4),
                            if (item.variation != 'Original') _tag(item.variation),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text('₱${item.price.toStringAsFixed(0)}', style: const TextStyle(color: AppTheme.primary, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                  // Controls
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      IconButton(
                        onPressed: () => cart.removeItem(idx),
                        icon: const Icon(Icons.close, size: 18, color: AppTheme.textMuted),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        decoration: BoxDecoration(
                          border: Border.all(color: AppTheme.border),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          children: [
                            _qtyBtn(Icons.remove, () => cart.setQuantity(idx, item.quantity - 1)),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 8),
                              child: Text('${item.quantity}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            ),
                            _qtyBtn(Icons.add, () => cart.setQuantity(idx, item.quantity + 1)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _tag(String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(color: AppTheme.background, borderRadius: BorderRadius.circular(4)),
      child: Text(label, style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
    );
  }

  Widget _qtyBtn(IconData icon, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.all(4),
        child: Icon(icon, size: 14, color: AppTheme.textMuted),
      ),
    );
  }

  Widget _buildSummary(BuildContext context, double subtotal) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, -4))],
      ),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('SUBTOTAL', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1, color: AppTheme.textMuted, fontSize: 12)),
                Text('₱${subtotal.toStringAsFixed(0)}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppTheme.primary)),
              ],
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => context.push('/checkout'),
                style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 20)),
                child: const Text('PROCEED TO CHECKOUT'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
