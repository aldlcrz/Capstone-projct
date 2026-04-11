import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:fl_chart/fl_chart.dart';
import 'dart:math';

import '../../config/app_theme.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_client.dart';
import '../widgets/app_navbar.dart';

class SellerDashboardScreen extends StatefulWidget {
  const SellerDashboardScreen({super.key});

  @override
  State<SellerDashboardScreen> createState() => _SellerDashboardScreenState();
}

class _SellerDashboardScreenState extends State<SellerDashboardScreen> {
  Map<String, dynamic> _stats = {'revenue': 0, 'orders': 0, 'inquiries': 0, 'products': 0};
  Timer? _pollTimer;
  bool _refreshInProgress = false;

  @override
  void initState() {
    super.initState();
    _loadStats();
    _startRealtimePolling();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  void _startRealtimePolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 20), (_) {
      _loadStats(silent: true);
    });
  }

  Future<void> _loadStats({bool silent = false}) async {
    if (_refreshInProgress) return;
    _refreshInProgress = true;
    try {
      final res = await ApiClient().get('/products/seller-stats');
      if (res.data is Map && mounted) {
        setState(() {
          _stats = Map<String, dynamic>.from(res.data as Map);
        });
      }
    } catch (_) {
      if (!silent && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Failed to refresh dashboard stats.'),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      _refreshInProgress = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (!auth.isLoggedIn || auth.user!.role != 'seller') {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) context.go('/');
      });
      return const SizedBox.shrink();
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F2),
      appBar: const LumBarongAppBar(),
      bottomNavigationBar: const AppBottomNav(currentIndex: 0),
      body: RefreshIndicator(
        color: AppTheme.primary,
        onRefresh: _loadStats,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              const Text(
                'ARTISAN PERFORMANCE',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: 
                  FontWeight.w900,
                  color: AppTheme.textMuted,
                  letterSpacing: 2.0,
                ),
              ),
              const SizedBox(height: 4),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    child: Text.rich(
                      TextSpan(
                        children: [
                          TextSpan(
                            text: 'Workshop ',
                            style: GoogleFonts.playfairDisplay(fontSize: 28, fontWeight: FontWeight.w800, color: AppTheme.charcoal),
                          ),
                          TextSpan(
                            text: 'Dashboard',
                            style: GoogleFonts.playfairDisplay(fontSize: 28, fontWeight: FontWeight.w700, color: AppTheme.primary, fontStyle: FontStyle.italic),
                          ),
                        ],
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(
                      color: AppTheme.borderLight,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Row(
                      children: [
                         Icon(Icons.calendar_today_outlined, size: 14, color: AppTheme.charcoal),
                         SizedBox(width: 8),
                         Text('This Month', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: AppTheme.charcoal, letterSpacing: 0.5)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 32),

              // KPI Grid (2x2)
              Row(
                children: [
                  Expanded(
                    child: _KPICard(
                      title: 'Total Revenue',
                      value: '₱${(_stats['revenue'] ?? 0).toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},')}',
                      icon: Icons.attach_money_rounded,
                      isPrimary: true,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: _KPICard(
                      title: 'Shop Orders',
                      value: '${_stats['orders'] ?? 0}',
                      icon: Icons.shopping_bag_outlined,
                      isPrimary: false,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  const Expanded(
                    child: _KPICard(
                      title: 'Retention',
                      value: '48.2%',
                      icon: Icons.people_outline,
                      isPrimary: false,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: _KPICard(
                      title: 'Inquiries',
                      value: '${_stats['inquiries'] ?? 0}',
                      icon: Icons.chat_bubble_outline,
                      isPrimary: false,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 32),

              // Area Chart (Sales Trend)
              _SectionCard(
                title: 'Workshop Sales Trend',
                subtitle: 'Revenue performance over selected date range',
                icon: Icons.trending_up,
                child: SizedBox(
                   height: 250,
                   child: LineChart(
                     LineChartData(
                       gridData: FlGridData(
                         show: true, 
                         drawVerticalLine: false, 
                         horizontalInterval: 20000, 
                         getDrawingHorizontalLine: (val) => const FlLine(color: AppTheme.borderLight, strokeWidth: 1, dashArray: [4, 4]),
                       ),
                       titlesData: FlTitlesData(
                         show: true,
                         topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                         rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                         bottomTitles: AxisTitles(
                           sideTitles: SideTitles(
                             showTitles: true,
                             reservedSize: 30,
                             interval: 1,
                             getTitlesWidget: (val, _) {
                               const styles = TextStyle(color: AppTheme.textMuted, fontSize: 10, fontWeight: FontWeight.bold);
                               switch(val.toInt()) {
                                 case 0: return const Padding(padding: EdgeInsets.only(top: 8), child: Text('Jan', style: styles));
                                 case 1: return const Padding(padding: EdgeInsets.only(top: 8), child: Text('Feb', style: styles));
                                 case 2: return const Padding(padding: EdgeInsets.only(top: 8), child: Text('Mar', style: styles));
                                 case 3: return const Padding(padding: EdgeInsets.only(top: 8), child: Text('Apr', style: styles));
                                 case 4: return const Padding(padding: EdgeInsets.only(top: 8), child: Text('May', style: styles));
                                 case 5: return const Padding(padding: EdgeInsets.only(top: 8), child: Text('Jun', style: styles));
                                 case 6: return const Padding(padding: EdgeInsets.only(top: 8), child: Text('Jul', style: styles));
                               }
                               return const Text('');
                             }
                           )
                         ),
                         leftTitles: AxisTitles(
                           sideTitles: SideTitles(
                             showTitles: true,
                             reservedSize: 40,
                             interval: 20000,
                             getTitlesWidget: (val, _) {
                               return Text('₱${(val / 1000).toInt()}k', style: const TextStyle(color: AppTheme.textMuted, fontSize: 10, fontWeight: FontWeight.bold));
                             }
                           )
                         )
                       ),
                       borderData: FlBorderData(show: false),
                       minX: 0, maxX: 6, minY: 0, maxY: 80000,
                       lineBarsData: [
                         LineChartBarData(
                           spots: const [
                             FlSpot(0, 42000), FlSpot(1, 38000), FlSpot(2, 51000), FlSpot(3, 46000), FlSpot(4, 59000), FlSpot(5, 74000), FlSpot(6, 68000),
                           ],
                           isCurved: true,
                           curveSmoothness: 0.35,
                           color: AppTheme.primary,
                           barWidth: 4,
                           isStrokeCapRound: true,
                           dotData: const FlDotData(show: false),
                           belowBarData: BarAreaData(
                             show: true,
                             gradient: LinearGradient(
                               colors: [AppTheme.primary.withValues(alpha: 0.3), AppTheme.primary.withValues(alpha: 0.0)],
                               begin: Alignment.topCenter, end: Alignment.bottomCenter,
                             )
                           )
                         )
                       ],
                     )
                   )
                ),
              ),
              const SizedBox(height: 24),

              // Lifetime Revenue Cohort
              _SectionCard(
                title: 'Avg. Lifetime Revenue',
                subtitle: 'Comparing users registered in Jan vs Mar 2026',
                isCohort: true,
                child: SizedBox(
                   height: 200,
                   child: LineChart(
                     LineChartData(
                       gridData: const FlGridData(show: false),
                       titlesData: FlTitlesData(
                         topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                         rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                         leftTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                         bottomTitles: AxisTitles(
                           sideTitles: SideTitles(
                             showTitles: true,
                             interval: 1,
                             getTitlesWidget: (v, _) => Padding(
                               padding: const EdgeInsets.only(top: 10),
                               child: Text('Week ${v.toInt() + 1}', style: const TextStyle(color: AppTheme.textMuted, fontSize: 10)),
                             )
                           )
                         ),
                       ),
                       borderData: FlBorderData(show: false),
                       minX: 0, maxX: 3, minY: 0, maxY: 10000,
                       lineBarsData: [
                         LineChartBarData(
                           spots: const [FlSpot(0, 4000), FlSpot(1, 3000), FlSpot(2, 5000), FlSpot(3, 4500)],
                           isCurved: true, color: const Color(0xFFD4B896), barWidth: 3,
                           dotData: FlDotData(show: true, getDotPainter: (a, b, c, d) => FlDotCirclePainter(radius: 4, color: const Color(0xFFD4B896), strokeWidth: 2, strokeColor: Colors.white)),
                         ),
                         LineChartBarData(
                           spots: const [FlSpot(0, 6000), FlSpot(1, 5500), FlSpot(2, 7200), FlSpot(3, 8100)],
                           isCurved: true, color: AppTheme.primary, barWidth: 3,
                           isStrokeCapRound: true,
                           dashArray: [5, 5],
                           dotData: FlDotData(show: true, getDotPainter: (a, b, c, d) => FlDotCirclePainter(radius: 4, color: AppTheme.primary, strokeWidth: 2, strokeColor: Colors.white)),
                         )
                       ]
                     )
                   )
                ),
              ),
              const SizedBox(height: 24),

              // Customer Retention Grid (Heatmap)
              _SectionCard(
                title: 'Customer Retention (%)',
                child: Column(
                  children: [
                    GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 6,
                        crossAxisSpacing: 8,
                        mainAxisSpacing: 8,
                      ),
                      itemCount: 36,
                      itemBuilder: (ctx, i) {
                        final val = Random(i * 123).nextInt(100);
                        Color color;
                        if (val < 25) {
                          color = const Color(0xFFf3dad6);
                        } else if (val < 50) {
                          color = const Color(0xFFe8b5ac);
                        } else if (val < 75) {
                          color = const Color(0xFFd26a4e);
                        } else {
                          color = const Color(0xFFc14a38);
                        }
                        
                        return Container(
                          decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(8)),
                          alignment: Alignment.center,
                          child: Text('$val%', style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontSize: 10, fontWeight: FontWeight.bold)),
                        );
                      },
                    ),
                    const SizedBox(height: 20),
                    Container(height: 1, color: AppTheme.borderLight),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        Container(width: 12, height: 12, decoration: BoxDecoration(color: const Color(0xFFf3dad6), borderRadius: BorderRadius.circular(4))),
                        const SizedBox(width: 6),
                        const Text('LOW', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
                        const SizedBox(width: 16),
                        Container(width: 12, height: 12, decoration: BoxDecoration(color: const Color(0xFFc14a38), borderRadius: BorderRadius.circular(4))),
                        const SizedBox(width: 6),
                        const Text('HIGH', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
                      ],
                    )
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Funnel
              _SectionCard(
                title: 'Sales Performance Funnel',
                isCentered: true,
                child: Column(
                  children: [
                    _FunnelBar(label: 'Visitors', value: '12,500', percent: 1.0, color: const Color(0xFF2C2420)),
                    const SizedBox(height: 16),
                    _FunnelBar(label: 'Product Views', value: '8,400', percent: 0.85, color: const Color(0xFF594436)),
                    const SizedBox(height: 16),
                    _FunnelBar(label: 'Add to Cart', value: '4,200', percent: 0.60, color: const Color(0xFF8C7B70)),
                    const SizedBox(height: 16),
                    _FunnelBar(label: 'Checkout', value: '1,800', percent: 0.35, color: const Color(0xFFD4B896)),
                    const SizedBox(height: 16),
                    _FunnelBar(label: 'Completed', value: '1,240', percent: 0.20, color: AppTheme.primary),
                  ],
                ),
              ),
              const SizedBox(height: 100), // spacing for FAB
            ],
          ),
        ),
      ),
    );
  }
}

class _KPICard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final bool isPrimary;

  const _KPICard({required this.title, required this.value, required this.icon, required this.isPrimary});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 160,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isPrimary ? AppTheme.primary : Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: isPrimary ? null : Border.all(color: AppTheme.borderLight),
        boxShadow: [if (!isPrimary) BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  title.toUpperCase(),
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1.5,
                    color: isPrimary ? Colors.white.withValues(alpha: 0.7) : AppTheme.textMuted,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: isPrimary ? Colors.white.withValues(alpha: 0.2) : const Color(0xFFF9F6F2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, size: 16, color: isPrimary ? Colors.white : AppTheme.charcoal),
              ),
            ],
          ),
          Text(
            value,
            style: GoogleFonts.playfairDisplay(
              fontSize: 28,
              fontWeight: FontWeight.w800,
              color: isPrimary ? Colors.white : AppTheme.charcoal,
            ),
          )
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final String? subtitle;
  final Widget child;
  final IconData? icon;
  final bool isCohort;
  final bool isCentered;

  const _SectionCard({required this.title, this.subtitle, required this.child, this.icon, this.isCohort = false, this.isCentered = false});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppTheme.borderLight),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Column(
        crossAxisAlignment: isCentered ? CrossAxisAlignment.center : CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: isCentered ? MainAxisAlignment.center : MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Column(
                crossAxisAlignment: isCentered ? CrossAxisAlignment.center : CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        title,
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: AppTheme.charcoal,
                          decoration: isCentered ? TextDecoration.underline : TextDecoration.none,
                          decorationColor: AppTheme.primary,
                          decorationThickness: 2,
                        ),
                      ),
                      if (isCohort) ...[
                        const SizedBox(width: 8),
                         Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: AppTheme.primary, borderRadius: BorderRadius.circular(4)),
                          child: const Text('COHORT', style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                        ),
                      ],
                    ],
                  ),
                  if (subtitle != null) ...[
                    const SizedBox(height: 4),
                    Text(subtitle!, style: const TextStyle(fontSize: 11, color: AppTheme.textMuted, fontStyle: FontStyle.italic)),
                  ],
                ],
              ),
              if (icon != null) Icon(icon, color: AppTheme.primary),
            ],
          ),
          const SizedBox(height: 32),
          child,
        ],
      ),
    );
  }
}

class _FunnelBar extends StatelessWidget {
  final String label;
  final String value;
  final double percent;
  final Color color;

  const _FunnelBar({required this.label, required this.value, required this.percent, required this.color});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        SizedBox(
          width: 90,
          child: Text(
            label.toUpperCase(),
            textAlign: TextAlign.right,
            style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w900, letterSpacing: 1.0, color: AppTheme.textMuted),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Container(
            height: 48,
            decoration: BoxDecoration(color: const Color(0xFFF9F6F2), borderRadius: BorderRadius.circular(12)),
            child: Stack(
              children: [
                FractionallySizedBox(
                  widthFactor: percent,
                  child: Container(
                    decoration: BoxDecoration(
                      color: color,
                      borderRadius: const BorderRadius.only(topRight: Radius.circular(12), bottomRight: Radius.circular(12)),
                    ),
                    alignment: Alignment.centerRight,
                    padding: const EdgeInsets.only(right: 16),
                    child: Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 13)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
