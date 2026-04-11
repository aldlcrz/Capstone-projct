import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../config/app_theme.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_client.dart';
import '../widgets/app_navbar.dart';

class AdminDashboardScreen extends StatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  State<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> {
  Map<String, dynamic>? _stats;
  List<Map<String, dynamic>> _pendingSellers = [];
  List<Map<String, dynamic>> _pendingProducts = [];
  bool _loading = true;
  bool _actionLoading = false;

  @override
  void initState() {
    super.initState();
    _loadStats();
  }

  Future<void> _loadStats() async {
    if (!mounted) return;
    setState(() => _loading = true);
    try {
      final res = await ApiClient().get('/admin/stats');
      if (!mounted) return;
      if (res.data is Map) {
        setState(() => _stats = Map<String, dynamic>.from(res.data as Map));
      }

      final sellersRes = await ApiClient().get('/auth/sellers');
      if (!mounted) return;
      if (sellersRes.data is List) {
        setState(() {
          _pendingSellers = (sellersRes.data as List)
              .map((e) => Map<String, dynamic>.from(e as Map))
              .where((s) => s['isVerified'] == false)
              .toList();
        });
      }

      final prodRes = await ApiClient().get('/products');
      if (!mounted) return;
      if (prodRes.data is List) {
        setState(() {
          _pendingProducts = (prodRes.data as List)
              .map((e) => Map<String, dynamic>.from(e as Map))
              .where((p) => p['status'] == 'pending')
              .toList();
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _approveSeller(String id) async {
    setState(() => _actionLoading = true);
    try {
      await ApiClient().put('/auth/approve-seller/$id');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Artisan approved for commerce'),
            behavior: SnackBarBehavior.floating,
          ),
        );
        _loadStats();
      }
    } catch (_) {}
    if (mounted) setState(() => _actionLoading = false);
  }

  Future<void> _updateProductStatus(String id, String status) async {
    setState(() => _actionLoading = true);
    try {
      await ApiClient().patch('/products/$id/status', data: {'status': status});
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Product $status successfully'),
            behavior: SnackBarBehavior.floating,
          ),
        );
        _loadStats();
      }
    } catch (_) {}
    if (mounted) setState(() => _actionLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.isLoggedIn || auth.user!.role != 'admin') {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) context.go('/');
      });
      return const SizedBox.shrink();
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F2),
      appBar: const LumBarongAppBar(title: 'Admin Command'),
      bottomNavigationBar: const AppBottomNav(currentIndex: 0),
      body: Stack(
        children: [
          RefreshIndicator(
            color: AppTheme.primary,
            onRefresh: _loadStats,
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(24, 32, 24, 40),
                  sliver: SliverToBoxAdapter(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Editorial Header
                        Text(
                          'ENTERPRISE OVERVIEW',
                          style: GoogleFonts.outfit(
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 2,
                            color: const Color(0xFF8C7B70),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Text(
                              'Dashboard ',
                              style: GoogleFonts.playfairDisplay(
                                fontSize: 32,
                                fontWeight: FontWeight.w700,
                                color: AppTheme.charcoal,
                              ),
                            ),
                            Text(
                              'Insights',
                              style: GoogleFonts.playfairDisplay(
                                fontSize: 32,
                                fontWeight: FontWeight.w300,
                                fontStyle: FontStyle.italic,
                                color: const Color(0xFFD4B896),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 40),

                        if (_loading)
                          const Center(
                            child: Padding(
                              padding: EdgeInsets.all(40),
                              child: CircularProgressIndicator(color: AppTheme.primary),
                            ),
                          )
                        else ...[
                          // Stat Cards Layout
                          LayoutBuilder(builder: (context, constraints) {
                            final cols = constraints.maxWidth > 600 ? 4 : 2;
                            return GridView.count(
                              crossAxisCount: cols,
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              mainAxisSpacing: 16,
                              crossAxisSpacing: 16,
                              childAspectRatio: 1.3,
                              children: [
                                _TrendCard(
                                  label: 'TOTAL SALES',
                                  value: '₱${_stats?['totalRevenue'] ?? '--'}',
                                  change: '+12.4%',
                                  icon: Icons.payments_outlined,
                                  color: AppTheme.primary,
                                ),
                                _TrendCard(
                                  label: 'TOTAL ORDERS',
                                  value: '${_stats?['totalOrders'] ?? '--'}',
                                  change: '+8.2%',
                                  icon: Icons.shopping_bag_outlined,
                                  color: Colors.blue.shade600,
                                ),
                                _TrendCard(
                                  label: 'CUSTOMERS',
                                  value: '${_stats?['totalUsers'] ?? '--'}',
                                  change: '+15.5%',
                                  icon: Icons.people_outline,
                                  color: Colors.green.shade600,
                                ),
                                _TrendCard(
                                  label: 'LIVE PRODUCTS',
                                  value: '${_stats?['liveProducts'] ?? '--'}',
                                  change: '-2.1%',
                                  icon: Icons.storefront_outlined,
                                  color: Colors.amber.shade700,
                                ),
                              ],
                            );
                          }),

                          const SizedBox(height: 40),

                          // Weekly Earning Chart
                          const _WeeklyEarningsBanner(),

                          const SizedBox(height: 40),

                          // Target & Region Row
                          _TargetAndRegionSection(),

                          const SizedBox(height: 48),
                          
                          // Registry Applications
                          const _SectionLabel(text: 'REGISTRY APPLICATIONS'),
                          const SizedBox(height: 16),
                          if (_pendingSellers.isEmpty)
                            const _EmptyState(
                              icon: Icons.shield_outlined,
                              text: 'No pending artisan registries',
                            )
                          else
                            ListView.separated(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: _pendingSellers.length,
                              separatorBuilder: (_, _) => const SizedBox(height: 12),
                              itemBuilder: (ctx, i) => _SellerRegistryCard(
                                seller: _pendingSellers[i],
                                onApprove: _approveSeller,
                              ),
                            ),

                          const SizedBox(height: 40),
                          const _SectionLabel(text: 'PRODUCT REGISTRY'),
                          const SizedBox(height: 16),
                          if (_pendingProducts.isEmpty)
                            const _EmptyState(
                              icon: Icons.storefront_outlined,
                              text: 'No pending product listings',
                            )
                          else
                            ListView.separated(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: _pendingProducts.length,
                              separatorBuilder: (_, _) => const SizedBox(height: 12),
                              itemBuilder: (ctx, i) => _ProductRegistryCard(
                                product: _pendingProducts[i],
                                onAction: _updateProductStatus,
                              ),
                            ),
                        ],
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (_actionLoading)
            Container(
              color: Colors.black26,
              alignment: Alignment.center,
              child: const CircularProgressIndicator(color: AppTheme.primary),
            ),
        ],
      ),
    );
  }
}

class _TrendCard extends StatelessWidget {
  final String label;
  final String value;
  final String change;
  final IconData icon;
  final Color color;

  const _TrendCard({
    required this.label,
    required this.value,
    required this.change,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    final isUp = change.startsWith('+');
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppTheme.borderLight),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: color, size: 18),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: isUp ? Colors.green.shade50 : Colors.red.shade50,
                  borderRadius: BorderRadius.circular(100),
                ),
                child: Row(
                  children: [
                    Icon(
                      isUp ? Icons.arrow_outward : Icons.arrow_downward,
                      size: 8,
                      color: isUp ? Colors.green.shade700 : Colors.red.shade700,
                    ),
                    const SizedBox(width: 2),
                    Text(
                      change,
                      style: TextStyle(
                        fontSize: 8,
                        fontWeight: FontWeight.w900,
                        color: isUp ? Colors.green.shade700 : Colors.red.shade700,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                value,
                style: GoogleFonts.playfairDisplay(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: AppTheme.charcoal,
                ),
              ),
              Text(
                label,
                style: GoogleFonts.outfit(
                  fontSize: 8,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 0.5,
                  color: AppTheme.textMuted,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _WeeklyEarningsBanner extends StatelessWidget {
  const _WeeklyEarningsBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppTheme.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Earning Statistics',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                  ),
                  Text(
                    'Revenue across current week cycle',
                    style: TextStyle(fontSize: 10, color: AppTheme.textMuted),
                  ),
                ],
              ),
              Icon(Icons.query_stats_rounded, color: AppTheme.textMuted, size: 20),
            ],
          ),
          const SizedBox(height: 32),
          SizedBox(
            height: 200,
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                maxY: 7000,
                barTouchData: BarTouchData(enabled: false),
                titlesData: FlTitlesData(
                  show: true,
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (val, meta) {
                        const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        if (val.toInt() >= 0 && val.toInt() < days.length) {
                          return Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text(
                              days[val.toInt()],
                              style: const TextStyle(
                                color: Color(0xFF8C7B70),
                                fontWeight: FontWeight.bold,
                                fontSize: 10,
                              ),
                            ),
                          );
                        }
                        return const SizedBox.shrink();
                      },
                    ),
                  ),
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 40,
                      getTitlesWidget: (val, meta) {
                        return Text(
                          '₱${(val / 1000).toInt()}k',
                          style: const TextStyle(
                            color: Color(0xFF8C7B70),
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        );
                      },
                    ),
                  ),
                  rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                ),
                gridData: FlGridData(
                  show: true,
                  drawVerticalLine: false,
                  getDrawingHorizontalLine: (val) => const FlLine(
                    color: Color(0xFFE5DDD5),
                    strokeWidth: 1,
                  ),
                ),
                borderData: FlBorderData(show: false),
                barGroups: [
                  _makeGroupData(0, 4200),
                  _makeGroupData(1, 3800),
                  _makeGroupData(2, 5100),
                  _makeGroupData(3, 4600),
                  _makeGroupData(4, 5900),
                  _makeGroupData(5, 6400),
                  _makeGroupData(6, 5200),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  BarChartGroupData _makeGroupData(int x, double y) {
    return BarChartGroupData(
      x: x,
      barRods: [
        BarChartRodData(
          toY: y,
          color: AppTheme.primary,
          width: 22,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(6)),
          backDrawRodData: BackgroundBarChartRodData(
            show: true,
            toY: 7000,
            color: const Color(0xFFF9F6F2),
          ),
        ),
      ],
    );
  }
}

class _TargetAndRegionSection extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(builder: (context, constraints) {
      final isWide = constraints.maxWidth > 600;
      return Flex(
        direction: isWide ? Axis.horizontal : Axis.vertical,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Sales Target (Donut)
          Expanded(
            flex: isWide ? 1 : 0,
            child: Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: AppTheme.borderLight),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Sales Target', style: TextStyle(fontWeight: FontWeight.w800)),
                  const Text('Daily reach index', style: TextStyle(fontSize: 10, color: AppTheme.textMuted)),
                  const SizedBox(height: 24),
                  Center(
                    child: SizedBox(
                      height: 140,
                      width: 140,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          PieChart(
                            PieChartData(
                              sectionsSpace: 4,
                              centerSpaceRadius: 50,
                              sections: [
                                PieChartSectionData(
                                  value: 78,
                                  color: AppTheme.primary,
                                  radius: 12,
                                  showTitle: false,
                                ),
                                PieChartSectionData(
                                  value: 22,
                                  color: const Color(0xFFF3F4F6),
                                  radius: 12,
                                  showTitle: false,
                                ),
                              ],
                            ),
                          ),
                          Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                '78%',
                                style: GoogleFonts.playfairDisplay(
                                  fontSize: 24,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const Text(
                                'TARGET META',
                                style: TextStyle(fontSize: 8, fontWeight: FontWeight.w700, letterSpacing: 1),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  _TargetRow(label: 'Daily Goal', amount: '₱45,000', color: AppTheme.primary),
                  const SizedBox(height: 8),
                  _TargetRow(label: 'Weekly Forecast', amount: '₱320,000', color: const Color(0xFFD4B896)),
                ],
              ),
            ),
          ),
          if (isWide) const SizedBox(width: 24) else const SizedBox(height: 24),
          // Regional Distribution
          Expanded(
            flex: isWide ? 1 : 0,
            child: Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: AppTheme.borderLight),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Top Regions', style: TextStyle(fontWeight: FontWeight.w800)),
                  const Text('Customers by location', style: TextStyle(fontSize: 10, color: AppTheme.textMuted)),
                  const SizedBox(height: 24),
                  _RegionRow(label: 'Manila', count: 420, total: 500),
                  _RegionRow(label: 'Quezon City', count: 310, total: 500),
                  _RegionRow(label: 'Lumban', count: 280, total: 500),
                  _RegionRow(label: 'Makati', count: 190, total: 500),
                  _RegionRow(label: 'Cebu City', count: 150, total: 500),
                ],
              ),
            ),
          ),
        ],
      );
    });
  }
}

class _TargetRow extends StatelessWidget {
  final String label;
  final String amount;
  final Color color;
  const _TargetRow({required this.label, required this.amount, required this.color});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Container(width: 8, height: 8, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
            const SizedBox(width: 8),
            Text(label, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppTheme.textMuted)),
          ],
        ),
        Text(amount, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
      ],
    );
  }
}

class _RegionRow extends StatelessWidget {
  final String label;
  final int count;
  final int total;
  const _RegionRow({required this.label, required this.count, required this.total});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label.toUpperCase(), style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w900, letterSpacing: 1)),
              Text('$count', style: const TextStyle(fontSize: 10, color: AppTheme.textMuted)),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(100),
            child: LinearProgressIndicator(
              value: count / total,
              backgroundColor: const Color(0xFFF9F6F2),
              color: AppTheme.primary,
              minHeight: 6,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionLabel extends StatelessWidget {
  final String text;
  const _SectionLabel({required this.text});

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: GoogleFonts.outfit(
        fontSize: 10,
        fontWeight: FontWeight.w800,
        letterSpacing: 2.0,
        color: AppTheme.textMuted,
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  final IconData icon;
  final String text;
  const _EmptyState({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 40),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppTheme.borderLight),
      ),
      child: Column(
        children: [
          Icon(icon, size: 32, color: AppTheme.textMuted.withValues(alpha: 0.2)),
          const SizedBox(height: 12),
          Text(text, style: const TextStyle(color: AppTheme.textMuted, fontSize: 11, fontStyle: FontStyle.italic)),
        ],
      ),
    );
  }
}

class _SellerRegistryCard extends StatelessWidget {
  final Map<String, dynamic> seller;
  final Function(String) onApprove;
  const _SellerRegistryCard({required this.seller, required this.onApprove});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppTheme.borderLight),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppTheme.background,
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Icons.person_outline, color: AppTheme.primary),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  (seller['shopName'] ?? seller['name']).toString().toUpperCase(),
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                ),
                Text(
                  'Artisan Registry Request',
                  style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
                ),
              ],
            ),
          ),
          ElevatedButton(
            onPressed: () => onApprove(seller['_id'] ?? seller['id']),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
              padding: const EdgeInsets.symmetric(horizontal: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: const Text('VERIFY', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Colors.white)),
          ),
        ],
      ),
    );
  }
}

class _ProductRegistryCard extends StatelessWidget {
  final Map<String, dynamic> product;
  final Function(String, String) onAction;
  const _ProductRegistryCard({required this.product, required this.onAction});

  @override
  Widget build(BuildContext context) {
    final seller = product['seller'] as Map? ?? {};
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppTheme.borderLight),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                width: 60,
                height: 60,
                decoration: BoxDecoration(
                  color: AppTheme.background,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.image_outlined, color: AppTheme.textMuted),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      product['name'].toString().toUpperCase(),
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      '₱${product['price']} • by ${seller['shopName'] ?? seller['name']}',
                      style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: () => onAction(product['_id'] ?? product['id'], 'approved'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.charcoal,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('APPROVE', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Colors.white)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton(
                  onPressed: () => onAction(product['_id'] ?? product['id'], 'rejected'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppTheme.primary,
                    side: const BorderSide(color: AppTheme.primary),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('REJECT', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
