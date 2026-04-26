import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../providers/notification_provider.dart';
import '../../providers/auth_provider.dart';
import '../../config/app_theme.dart';
import '../../models/product_model.dart';
import '../../services/product_service.dart';
import '../../widgets/product_card.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<ProductModel> _products = [];
  List<String> _categories = [];
  String _activeCategory = 'ALL';
  bool _isLoading = true;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final results = await Future.wait([
      ProductService().getProducts(),
      ProductService().getCategories(),
    ]);
    if (mounted) {
      setState(() {
        _products = results[0] as List<ProductModel>;
        _categories = ['ALL', ...(results[1] as List<String>)];
        _isLoading = false;
      });
    }
  }

  List<ProductModel> get _filteredProducts {
    return _products.where((p) {
      final matchesCategory = _activeCategory == 'ALL' || 
          (p.category?.toLowerCase() == _activeCategory.toLowerCase());
      final matchesSearch = _searchQuery.isEmpty || 
          p.name.toLowerCase().contains(_searchQuery.toLowerCase()) ||
          (p.artisan?.toLowerCase().contains(_searchQuery.toLowerCase()) ?? false);
      return matchesCategory && matchesSearch;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(
        title: Text(
          'LumBarong',
          style: GoogleFonts.playfairDisplay(
            fontWeight: FontWeight.bold,
            color: AppTheme.primary,
          ),
        ),
        actions: [
          Consumer<NotificationProvider>(
            builder: (context, provider, child) {
              return Stack(
                children: [
                  IconButton(
                    onPressed: () => context.push('/notifications'),
                    icon: const Icon(LucideIcons.bell),
                  ),
                  if (provider.unreadCount > 0)
                    Positioned(
                      right: 8,
                      top: 8,
                      child: Container(
                        padding: const EdgeInsets.all(2),
                        decoration: BoxDecoration(color: AppTheme.primary, shape: BoxShape.circle, border: Border.all(color: Colors.white, width: 1)),
                        constraints: const BoxConstraints(minWidth: 14, minHeight: 14),
                        child: Text(
                          '${provider.unreadCount}',
                          style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
          IconButton(
            onPressed: () => context.push('/cart'),
            icon: const Icon(LucideIcons.shoppingBag),
          ),
          IconButton(
            onPressed: () => context.read<AuthProvider>().logout(),
            icon: const Icon(LucideIcons.logOut),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadData,
        child: CustomScrollView(
          slivers: [
            // Welcome Section
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 16.0),
                child: Center(
                  child: RichText(
                    text: TextSpan(
                      style: GoogleFonts.playfairDisplay(
                        fontSize: 24,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.charcoal,
                      ),
                      children: const [
                        TextSpan(text: 'Welcome to '),
                        TextSpan(
                          text: 'LumBarong',
                          style: TextStyle(color: AppTheme.primary, fontStyle: FontStyle.italic),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),

            // Search & Category Bar
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
                child: Column(
                  children: [
                    // Search Field
                    TextField(
                      onChanged: (v) => setState(() => _searchQuery = v),
                      decoration: InputDecoration(
                        hintText: 'Search products or artisans...',
                        prefixIcon: const Icon(Icons.search, size: 20),
                        filled: true,
                        fillColor: Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    // Categories Scroll
                    SizedBox(
                      height: 40,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: _categories.length,
                        separatorBuilder: (c, i) => const SizedBox(width: 8),
                        itemBuilder: (c, i) {
                          final cat = _categories[i];
                          final isSelected = _activeCategory == cat;
                          return ChoiceChip(
                            label: Text(cat),
                            selected: isSelected,
                            onSelected: (v) => setState(() => _activeCategory = cat),
                            selectedColor: AppTheme.primary,
                            labelStyle: TextStyle(
                              color: isSelected ? Colors.white : AppTheme.charcoal,
                              fontSize: 12,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                            ),
                            showCheckmark: false,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // Product Grid
            if (_isLoading)
              const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator(color: AppTheme.primary)),
              )
            else if (_filteredProducts.isEmpty)
              const SliverFillRemaining(
                child: Center(child: Text('No products found.', style: TextStyle(color: AppTheme.textMuted))),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.all(16.0),
                sliver: SliverGrid(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 0.7,
                    crossAxisSpacing: 12,
                    mainAxisSpacing: 12,
                  ),
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      return ProductCard(product: _filteredProducts[index]);
                    },
                    childCount: _filteredProducts.length,
                  ),
                ),
              ),
            
            const SliverToBoxAdapter(child: SizedBox(height: 80)),
          ],
        ),
      ),
    );
  }
}
