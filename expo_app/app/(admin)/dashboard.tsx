/**
 * Admin Dashboard — Platform-wide KPIs + Charts
 * Mirrors /admin/dashboard/page.js:
 * - /admin/stats?range= → totalSales, totalOrders, activeCustomers, liveProducts
 * - /admin/analytics?range= → revenueSeries, monthlySignups, topLocations, topProducts, topCategories
 * - Date filter: today / week / month / year
 * - Real-time: stats_update, order_created/updated, user_updated
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, RefreshControl,
} from 'react-native';
import { api } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { Colors, FontSize, FontWeight, Spacing, Radius, Shadow } from '@/constants/theme';

const FILTERS = ['today', 'week', 'month', 'year'] as const;
type Filter = typeof FILTERS[number];

const PIE_COLORS = ['#C0422A', '#E56D4B', '#8C7B70', '#B3A499', '#E5DDD5'];

function StatCard({ label, value, icon, color = 'rust', loading = false }: any) {
  const bg: Record<string, string> = {
    rust: 'rgba(192,66,42,0.12)', blue: 'rgba(33,150,243,0.12)',
    green: 'rgba(76,175,80,0.12)', amber: 'rgba(255,152,0,0.12)',
  };
  const fg: Record<string, string> = {
    rust: Colors.primary, blue: Colors.info, green: Colors.success, amber: Colors.warning,
  };
  return (
    <View style={[styles.statCard, { backgroundColor: Colors.bgDark }]}>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: Spacing.md }}>
        <View style={[styles.statIconBox, { backgroundColor: bg[color] }]}>
          <Text style={{ fontSize: 18, color: fg[color] }}>{icon}</Text>
        </View>
        <Text style={styles.liveDot}>● LIVE</Text>
      </View>
      <Text style={styles.statValue}>{loading ? '—' : value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

export default function AdminDashboard() {
  const [dateFilter, setDateFilter] = useState<Filter>('week');
  const [stats, setStats] = useState<any>({ totalSales: '—', totalOrders: '—', activeCustomers: '—', liveProducts: '—' });
  const [analytics, setAnalytics] = useState<any>({ topLocations: [], topProducts: [], topCategories: [], revenueSeries: [], monthlySignups: [] });
  const [refreshing, setRefreshing] = useState(false);
  const { socket } = useSocket();

  const fetchStats = useCallback(async () => {
    try { const r = await api.get(`/admin/stats?range=${dateFilter}`); setStats(r.data); } catch {}
  }, [dateFilter]);

  const fetchAnalytics = useCallback(async () => {
    try { const r = await api.get(`/admin/analytics?range=${dateFilter}`); setAnalytics(r.data); } catch {}
  }, [dateFilter]);

  const refresh = useCallback(async () => {
    await Promise.all([fetchStats(), fetchAnalytics()]);
    setRefreshing(false);
  }, [fetchStats, fetchAnalytics]);

  useEffect(() => { refresh(); }, [refresh]);

  useEffect(() => {
    if (!socket) return;
    const h = () => refresh();
    ['stats_update', 'order_created', 'order_updated', 'user_updated', 'inventory_updated'].forEach(e => socket.on(e, h));
    return () => ['stats_update', 'order_created', 'order_updated', 'user_updated', 'inventory_updated'].forEach(e => socket.off(e, h));
  }, [socket, refresh]);

  const topLocs: any[] = analytics.topLocations || [];
  const topProds: any[] = (analytics.topProducts || []).slice(0, 5);
  const topCats: any[] = analytics.topCategories || [];
  const maxLocCount = Math.max(...topLocs.map((l: any) => l.count), 1);
  const maxProdSales = Math.max(...topProds.map((p: any) => p.sales), 1);

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); refresh(); }} tintColor={Colors.primary} />}
      >
        {/* Header */}
        <View style={styles.pageHeader}>
          <Text style={styles.eyebrow}>ENTERPRISE OVERVIEW</Text>
          <Text style={styles.pageTitle}>Dashboard <Text style={styles.pageTitleMuted}>Insights</Text></Text>
        </View>

        {/* Date Filter */}
        <View style={styles.filterRow}>
          {FILTERS.map(f => (
            <TouchableOpacity key={f} style={[styles.filterTab, dateFilter === f && styles.filterTabActive]} onPress={() => setDateFilter(f)}>
              <Text style={[styles.filterTabText, dateFilter === f && styles.filterTabTextActive]}>{f.toUpperCase()}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* KPI Grid */}
        <View style={styles.kpiGrid}>
          <StatCard label="Total Sales" value={stats.totalSales} icon="💰" color="rust" loading={refreshing} />
          <StatCard label="Total Orders" value={stats.totalOrders} icon="📦" color="blue" loading={refreshing} />
          <StatCard label="Active Customers" value={stats.activeCustomers} icon="👥" color="green" loading={refreshing} />
          <StatCard label="Live Products" value={stats.liveProducts} icon="🛍️" color="amber" loading={refreshing} />
        </View>

        {/* Financial Metrics */}
        <View style={styles.kpiGrid}>
          <StatCard label="Total Capital (Cost)" value={stats.totalCapital || '—'} icon="💼" color="blue" loading={refreshing} />
          <StatCard label="Gross Revenue" value={stats.totalRevenue || '—'} icon="📈" color="amber" loading={refreshing} />
          <StatCard label="Net Profit" value={stats.totalProfit || '—'} icon="✅" color="green" loading={refreshing} />
        </View>

        {/* Top Products */}
        {topProds.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Top Selling Products</Text>
            {topProds.map((p, i) => {
              const pct = Math.max((p.sales / maxProdSales) * 100, 2);
              return (
                <View key={p.id || i} style={styles.productRow}>
                  <View style={{ flex: 1, marginRight: Spacing.md }}>
                    <Text style={styles.productName} numberOfLines={1}>{p.name}</Text>
                    <Text style={styles.productSub}>{p.category}</Text>
                  </View>
                  <View style={{ width: 100 }}>
                    <View style={styles.progressTrack}>
                      <View style={[styles.progressBar, { width: `${pct}%` }]} />
                    </View>
                    <Text style={styles.productSalesText}>{p.sales} sold · ₱{Number(p.revenue || 0).toLocaleString()}</Text>
                  </View>
                </View>
              );
            })}
          </View>
        )}

        {/* Orders by Location */}
        {topLocs.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Orders by Location</Text>
            {topLocs.map((loc, i) => {
              const pct = (loc.count / maxLocCount) * 100;
              return (
                <View key={i} style={{ marginBottom: Spacing.lg }}>
                  <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 4 }}>
                    <Text style={styles.locName}>{loc.city?.toUpperCase()}</Text>
                    <Text style={styles.locCount}>{loc.count} orders</Text>
                  </View>
                  <View style={styles.progressTrack}>
                    <View style={[styles.progressBar, { width: `${pct}%` }]} />
                  </View>
                </View>
              );
            })}
          </View>
        )}

        {/* Top Categories */}
        {topCats.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Top Categories</Text>
            {topCats.map((cat, i) => (
              <View key={i} style={{ flexDirection: 'row', alignItems: 'center', marginBottom: Spacing.md, gap: Spacing.md }}>
                <View style={{ width: 12, height: 12, borderRadius: 6, backgroundColor: PIE_COLORS[i % PIE_COLORS.length] }} />
                <Text style={{ flex: 1, color: Colors.textSecondary, fontSize: FontSize.sm }}>{cat.name}</Text>
                <Text style={{ color: Colors.textPrimary, fontWeight: FontWeight.bold, fontSize: FontSize.sm }}>{cat.value} items</Text>
              </View>
            ))}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { padding: Spacing.xl, paddingBottom: 100 },
  pageHeader: { marginBottom: Spacing.xl },
  eyebrow: { fontSize: FontSize.xs, color: Colors.textMuted, letterSpacing: 2, fontWeight: FontWeight.bold, marginBottom: 4 },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.heavy, color: Colors.textPrimary },
  pageTitleMuted: { color: Colors.textMuted, fontWeight: FontWeight.regular, fontStyle: 'italic' },
  filterRow: { flexDirection: 'row', gap: Spacing.xs, marginBottom: Spacing.xl, backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: 4 },
  filterTab: { flex: 1, paddingVertical: Spacing.sm, borderRadius: Radius.sm, alignItems: 'center' },
  filterTabActive: { backgroundColor: Colors.primary },
  filterTabText: { fontSize: FontSize.xs, fontWeight: FontWeight.bold, color: Colors.textMuted, letterSpacing: 1 },
  filterTabTextActive: { color: Colors.white },
  kpiGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.md, marginBottom: Spacing.xl },
  statCard: { width: '47%', borderRadius: Radius.lg, padding: Spacing.lg, borderWidth: 1, borderColor: Colors.border, ...Shadow.sm },
  statIconBox: { width: 36, height: 36, borderRadius: Radius.md, justifyContent: 'center', alignItems: 'center' },
  liveDot: { fontSize: 8, color: Colors.success, fontWeight: FontWeight.bold },
  statValue: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  statLabel: { fontSize: FontSize.xs, color: Colors.textMuted, fontWeight: FontWeight.bold, textTransform: 'uppercase', letterSpacing: 1 },
  section: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.xl, borderWidth: 1, borderColor: Colors.border },
  sectionTitle: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.lg },
  productRow: { flexDirection: 'row', alignItems: 'center', marginBottom: Spacing.lg },
  productName: { fontSize: FontSize.sm, fontWeight: FontWeight.semibold, color: Colors.textPrimary },
  productSub: { fontSize: FontSize.xs, color: Colors.textMuted },
  productSalesText: { fontSize: FontSize.xs, color: Colors.textMuted, marginTop: 4 },
  progressTrack: { height: 6, backgroundColor: Colors.bgMedium, borderRadius: 3, overflow: 'hidden' },
  progressBar: { height: '100%', backgroundColor: Colors.primary, borderRadius: 3 },
  locName: { fontSize: FontSize.xs, fontWeight: FontWeight.bold, color: Colors.textPrimary, letterSpacing: 1 },
  locCount: { fontSize: FontSize.xs, color: Colors.textMuted },
});
