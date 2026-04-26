import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../services/api_client.dart';
import '../../config/app_theme.dart';

class SellerDashboardScreen extends StatefulWidget {
  const SellerDashboardScreen({super.key});

  @override
  State<SellerDashboardScreen> createState() => _SellerDashboardScreenState();
}

class _SellerDashboardScreenState extends State<SellerDashboardScreen> {
  Map<String, dynamic>? _stats;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchStats();
  }

  Future<void> _fetchStats() async {
    setState(() => _isLoading = true);
    try {
      final res = await ApiClient().get('/products/seller-stats?range=month');
      if (res.statusCode == 200) {
        setState(() {
          _stats = res.data;
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
      appBar: AppBar(
        title: Text('Seller Studio', style: GoogleFonts.playfairDisplay(fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primary))
          : RefreshIndicator(
              onRefresh: _fetchStats,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('HERITAGE PERFORMANCE', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 2, color: AppTheme.textMuted)),
                    const SizedBox(height: 16),
                    _buildKPIGrid(),
                    const SizedBox(height: 32),
                    _buildRecentSales(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildKPIGrid() {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      mainAxisSpacing: 16,
      crossAxisSpacing: 16,
      childAspectRatio: 1.5,
      children: [
        _kpiCard('Revenue', '₱${(_stats?['revenue'] ?? 0).toStringAsFixed(0)}', LucideIcons.dollarSign, isPrimary: true),
        _kpiCard('Orders', '${_stats?['orders'] ?? 0}', LucideIcons.shoppingBag),
        _kpiCard('Products', '${_stats?['products'] ?? 0}', LucideIcons.package),
        _kpiCard('Inquiries', '${_stats?['inquiries'] ?? 0}', LucideIcons.messageCircle),
      ],
    );
  }

  Widget _kpiCard(String label, String value, IconData icon, {bool isPrimary = false}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isPrimary ? AppTheme.primary : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label.toUpperCase(), style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: isPrimary ? Colors.white70 : AppTheme.textMuted)),
              Icon(icon, size: 14, color: isPrimary ? Colors.white : AppTheme.primary),
            ],
          ),
          Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: isPrimary ? Colors.white : AppTheme.charcoal)),
        ],
      ),
    );
  }

  Widget _buildRecentSales() {
    final topProducts = _stats?['topProducts'] as List? ?? [];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('TOP MASTERPIECES', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 2, color: AppTheme.textMuted)),
        const SizedBox(height: 16),
        if (topProducts.isEmpty)
          const Text('No sales records yet.', style: TextStyle(color: AppTheme.textMuted, fontSize: 13))
        else
          ...topProducts.map((p) => Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppTheme.border)),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(p['name'] ?? 'Product', style: const TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 4),
                      Text('${p['category'] ?? 'Category'}', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text('${p['sales'] ?? 0} sold', style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primary)),
                    Text('₱${(p['revenue'] ?? 0).toStringAsFixed(0)}', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                  ],
                ),
              ],
            ),
          )),
      ],
    );
  }
}
