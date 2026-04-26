import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../config/app_theme.dart';
import '../../models/product_model.dart';
import '../../providers/cart_provider.dart';
import '../../services/product_service.dart';

class ProductDetailScreen extends StatefulWidget {
  final String productId;

  const ProductDetailScreen({super.key, required this.productId});

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  ProductModel? _product;
  bool _isLoading = true;
  int _activeImageIndex = 0;
  String? _selectedSize;
  int _quantity = 1;

  @override
  void initState() {
    super.initState();
    _fetchProduct();
  }

  Future<void> _fetchProduct() async {
    try {
      // For now we use the existing service or a direct fetch
      // Since we don't have a single product fetch in service yet, we'll implement it here or update service
      final res = await ProductService().getProducts(); // Temporary until getProduct(id) is in service
      final product = res.firstWhere((p) => p.id.toString() == widget.productId);
      
      setState(() {
        _product = product;
        _isLoading = false;
        if (product.sizes.isNotEmpty) {
          final first = product.sizes.first;
          _selectedSize = first is Map ? (first['size'] ?? first['name']) : first.toString();
        }
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: AppTheme.primary)),
      );
    }

    if (_product == null) {
      return const Scaffold(
        body: Center(child: Text('Product not found.')),
      );
    }

    final p = _product!;
    final activeImage = p.images.isNotEmpty ? p.images[_activeImageIndex] : null;

    return Scaffold(
      backgroundColor: AppTheme.background,
      body: CustomScrollView(
        slivers: [
          // Premium Gallery Header
          SliverAppBar(
            expandedHeight: 450,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                children: [
                  // Main Image
                  Positioned.fill(
                    child: Container(
                      color: const Color(0xFFF7F3EE),
                      child: activeImage != null 
                        ? CachedNetworkImage(
                            imageUrl: activeImage.url,
                            fit: BoxFit.cover,
                          )
                        : const Center(child: Icon(Icons.image, size: 100, color: AppTheme.textMuted)),
                    ),
                  ),
                  // Variation Badge
                  if (activeImage != null && activeImage.variation != 'Original')
                    Positioned(
                      top: 100,
                      left: 20,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: AppTheme.charcoal.withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          activeImage.variation.toUpperCase(),
                          style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 1),
                        ),
                      ),
                    ),
                  // Thumbs Overlay
                  if (p.images.length > 1)
                    Positioned(
                      bottom: 20,
                      left: 0,
                      right: 0,
                      child: SizedBox(
                        height: 60,
                        child: ListView.separated(
                          scrollDirection: Axis.horizontal,
                          padding: const EdgeInsets.symmetric(horizontal: 20),
                          itemCount: p.images.length,
                          separatorBuilder: (c, i) => const SizedBox(width: 10),
                          itemBuilder: (c, i) {
                            final isSelected = _activeImageIndex == i;
                            return GestureDetector(
                              onTap: () => setState(() => _activeImageIndex = i),
                              child: Container(
                                width: 60,
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(
                                    color: isSelected ? AppTheme.primary : Colors.white.withValues(alpha: 0.5),
                                    width: isSelected ? 2 : 1,
                                  ),
                                  image: DecorationImage(
                                    image: CachedNetworkImageProvider(p.images[i].url),
                                    fit: BoxFit.cover,
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),

          // Product Info
          SliverToBoxAdapter(
            child: Container(
              color: Colors.white,
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(width: 20, height: 2, color: AppTheme.primary),
                      const SizedBox(width: 8),
                      Text(
                        (p.category ?? 'Traditional').toUpperCase(),
                        style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 2, color: AppTheme.primary),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    p.name,
                    style: GoogleFonts.playfairDisplay(fontSize: 24, fontWeight: FontWeight.bold, height: 1.2),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Text(
                        '₱${p.price.toStringAsFixed(0)}',
                        style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: AppTheme.primary),
                      ),
                    ],
                  ),
                  const Divider(height: 40, color: AppTheme.border),
                  
                  // Variations
                  const Text('VARIATION', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    children: p.images.asMap().entries.map((entry) {
                      final i = entry.key;
                      final img = entry.value;
                      final isSelected = _activeImageIndex == i;
                      return ChoiceChip(
                        label: Text(img.variation),
                        selected: isSelected,
                        onSelected: (_) => setState(() => _activeImageIndex = i),
                        selectedColor: AppTheme.background,
                        labelStyle: TextStyle(color: isSelected ? AppTheme.primary : AppTheme.charcoal, fontWeight: isSelected ? FontWeight.bold : FontWeight.normal),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8), side: BorderSide(color: isSelected ? AppTheme.primary : AppTheme.border)),
                        showCheckmark: false,
                      );
                    }).toList(),
                  ),

                  const SizedBox(height: 24),
                  
                  // Sizes
                  if (p.sizes.isNotEmpty) ...[
                    const Text('SIZE', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      children: p.sizes.map((s) {
                        final String name = s is Map ? (s['size'] ?? s['name']) : s.toString();
                        final int stock = s is Map ? (s['stock'] ?? 0) : p.stock;
                        final bool isOutOfStock = stock <= 0;
                        final isSelected = _selectedSize == name;

                        return ChoiceChip(
                          label: Text(isOutOfStock ? '$name (SOLD OUT)' : name),
                          selected: isSelected,
                          onSelected: isOutOfStock ? null : (_) => setState(() {
                            _selectedSize = name;
                            _quantity = 1; // Reset quantity on size change
                          }),
                          selectedColor: AppTheme.background,
                          disabledColor: Colors.grey.shade100,
                          labelStyle: TextStyle(
                            color: isOutOfStock ? Colors.grey : (isSelected ? AppTheme.primary : AppTheme.charcoal),
                            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                            fontSize: 12,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                            side: BorderSide(color: isSelected ? AppTheme.primary : (isOutOfStock ? Colors.grey.shade300 : AppTheme.border)),
                          ),
                          showCheckmark: false,
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Quantity
                  const Text('QUANTITY', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      _qtyBtn(Icons.remove, () => setState(() => _quantity = _quantity > 1 ? _quantity - 1 : 1)),
                      Container(
                        width: 50,
                        height: 40,
                        alignment: Alignment.center,
                        decoration: const BoxDecoration(border: Border.symmetric(horizontal: BorderSide(color: AppTheme.border))),
                        child: Text('$_quantity', style: const TextStyle(fontWeight: FontWeight.bold)),
                      ),
                      _qtyBtn(Icons.add, () => setState(() {
                        final maxStock = p.getStockForSize(_selectedSize);
                        if (_quantity < maxStock) {
                          _quantity++;
                        }
                      })),
                      const SizedBox(width: 16),
                      Text('${p.getStockForSize(_selectedSize)} units left', style: const TextStyle(fontSize: 12, color: AppTheme.textMuted)),
                    ],
                  ),

                  const SizedBox(height: 32),

                  // Artisan Card
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      border: Border.all(color: AppTheme.border),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        CircleAvatar(
                          backgroundColor: AppTheme.primary,
                          child: Text(p.artisan?[0] ?? 'A', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(p.artisan ?? 'Artisan', style: const TextStyle(fontWeight: FontWeight.bold)),
                              const Text('Master Craft from Lumban', style: TextStyle(fontSize: 12, color: AppTheme.textMuted)),
                            ],
                          ),
                        ),
                        OutlinedButton(
                          onPressed: () {},
                          style: OutlinedButton.styleFrom(
                            side: const BorderSide(color: AppTheme.border),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          child: const Text('VIEW SHOP', style: TextStyle(fontSize: 10, color: AppTheme.charcoal)),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 32),
                  
                  // Description
                  const Text('DESCRIPTION', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
                  const SizedBox(height: 12),
                  Text(
                    p.description ?? 'A masterpiece of Filipino heritage...',
                    style: const TextStyle(fontSize: 14, height: 1.6, color: AppTheme.charcoal),
                  ),
                  
                  const SizedBox(height: 100),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, -4))],
        ),
        child: Row(
          children: [
            Expanded(
              child: OutlinedButton(
                onPressed: () {
                  final maxStock = p.getStockForSize(_selectedSize);
                  if (maxStock <= 0) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Selected size is out of stock')));
                    return;
                  }
                  context.read<CartProvider>().addToCart(
                    p, 
                    _quantity, 
                    size: _selectedSize, 
                    variation: activeImage?.variation
                  );
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Added to Collection')));
                },
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: AppTheme.primary, width: 1.5),
                  padding: const EdgeInsets.symmetric(vertical: 18),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(LucideIcons.shoppingCart, size: 18, color: AppTheme.primary),
                    SizedBox(width: 8),
                    Text('ADD TO CART', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.primary)),
                  ],
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ElevatedButton(
                onPressed: () {},
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 18),
                ),
                child: const Text('BUY NOW', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _qtyBtn(IconData icon, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(border: Border.all(color: AppTheme.border)),
        child: Icon(icon, size: 16, color: AppTheme.textMuted),
      ),
    );
  }
}
