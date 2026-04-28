/**
 * Seller Dashboard — KPI cards + top products + sales funnel + revenue chart
 * Mirrors /seller/dashboard/page.js exactly:
 * - Fetch /products/seller-stats?range=
 * - Date filter: today / week / month / year
 * - KPI: Revenue, Orders, Loyalty%, Messages
 * - Top Products list with progress bars
 * - Sales Funnel bars
 * - Real-time socket: new_order toast, stats_update
 */
import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, RefreshControl, Animated,
} from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { useAuthStore } from '@/store/authStore';
import { Colors, FontSize, FontWeight, Spacing, Radius, Shadow } from '@/constants/theme';

const FILTERS = ['today', 'week', 'month', 'year'] as const;
type Filter = typeof FILTERS[number];
const FILTER_LABELS: Record<Filter, string> = { today: 'Today', week: 'This Week', month: 'This Month', year: 'This Year' };

const EMPTY_STATS = { revenue: 0, orders: 0, inquiries: 0, products: 0, retention: 0, topProducts: [], topCategories: [], funnel: {}, capital: 0, profit: 0 };

const FUNNEL_STEPS = [
  { key: 'visitors', label: 'Visitors', color: '#3D2B1F' },
  { key: 'views', label: 'Views', color: '#594436' },
  { key: 'addedToCart', label: 'Add to Cart', color: '#A58E7C' },
  { key: 'checkout', label: 'Checkout', color: '#8C7B70' },
  { key: 'completed', label: 'Customers', color: Colors.primary },
];

function KPICard({ label, value, icon, primary = false }: { label: string; value: string; icon: string; primary?: boolean }) {
  return (
    <View style={[styles.kpiCard, primary && styles.kpiCardPrimary]}>
      <Text style={styles.kpiIcon}>{icon}</Text>
      <Text style={[styles.kpiValue, primary && styles.kpiValuePrimary]}>{value}</Text>
      <Text style={[styles.kpiLabel, primary && styles.kpiLabelPrimary]}>{label}</Text>
      <View style={styles.liveDot}><Text style={styles.liveDotText}>● LIVE</Text></View>
    </View>
  );
}

export default function SellerDashboard() {
  const [dateFilter, setDateFilter] = useState<Filter>('month');
  const [stats, setStats] = useState<any>(EMPTY_STATS);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [newOrderAlert, setNewOrderAlert] = useState(false);
  const toastTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const { socket } = useSocket();
  const { user } = useAuthStore();

  const fetchStats = useCallback(async () => {
    try {
      const res = await api.get(`/products/seller-stats?range=${dateFilter}`);
      setStats(res.data || EMPTY_STATS);
    } catch { }
    finally { setLoading(false); setRefreshing(false); }
  }, [dateFilter]);

  useEffect(() => { fetchStats(); }, [fetchStats]);

  useEffect(() => {
    if (!socket) return;
    const handleNewOrder = () => {
      setNewOrderAlert(true);
      if (toastTimer.current) clearTimeout(toastTimer.current);
      toastTimer.current = setTimeout(() => setNewOrderAlert(false), 5000);
      fetchStats();
    };
    socket.on('new_order', handleNewOrder);
    socket.on('stats_update', fetchStats);
    socket.on('order_status_update', fetchStats);
    return () => { socket.off('new_order', handleNewOrder); socket.off('stats_update', fetchStats); socket.off('order_status_update', fetchStats); };
  }, [socket, fetchStats]);

  const funnel = stats.funnel || {};
  const funnelMax = Math.max(...FUNNEL_STEPS.map(s => funnel[s.key] || 0), 1);
  const topProducts: any[] = Array.isArray(stats.topProducts) ? stats.topProducts : [];

  return (
    <SafeAreaView style={styles.container}>
      {/* New Order Toast */}
      {newOrderAlert && (
        <View style={styles.toast}>
          <Text style={styles.toastText}>🛍️ New Heritage Sale! A customer just placed an order.</Text>
        </View>
      )}

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchStats(); }} tintColor={Colors.primary} />}
      >
        {/* Header */}
        <View style={styles.pageHeader}>
          <View>
            <Text style={styles.eyebrow}>SELLER PERFORMANCE</Text>
            <Text style={styles.pageTitle}>Seller <Text style={styles.pageTitleAccent}>Dashboard</Text></Text>
            <Text style={styles.filterLabel}>Showing: <Text style={{ color: Colors.primary }}>{FILTER_LABELS[dateFilter]}</Text></Text>
          </View>
        </View>

        {/* Date Filter Tabs */}
        <View style={styles.filterRow}>
          {FILTERS.map(f => (
            <TouchableOpacity key={f} style={[styles.filterTab, dateFilter === f && styles.filterTabActive]} onPress={() => setDateFilter(f)}>
              <Text style={[styles.filterTabText, dateFilter === f && styles.filterTabTextActive]}>{f.toUpperCase()}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* KPI Cards */}
        <View style={styles.kpiGrid}>
          <KPICard label="Total Revenue" value={loading ? '—' : `₱${Number(stats.revenue || 0).toLocaleString()}`} icon="💰" primary />
          <KPICard label="Orders" value={loading ? '—' : String(stats.orders || 0)} icon="📦" />
          <KPICard label="Capital" value={loading ? '—' : `₱${Number(stats.capital || 0).toLocaleString()}`} icon="💼" />
          <KPICard label="Net Profit" value={loading ? '—' : `₱${Number(stats.profit || 0).toLocaleString()}`} icon="📈" />
          <KPICard label="Suki (Loyalty)" value={loading ? '—' : `${stats.retention || 0}%`} icon="🤝" />
          <KPICard label="Messages" value={loading ? '—' : String(stats.inquiries || 0)} icon="💬" />
        </View>

        {/* Top Products */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Top Products</Text>
          <Text style={styles.sectionSub}>Most sold · {FILTER_LABELS[dateFilter]}</Text>
          {topProducts.length === 0 ? (
            <Text style={styles.emptyText}>No product sales yet in this period.</Text>
          ) : (
            topProducts.map((prod, i) => {
              const max = topProducts[0]?.sales || 1;
              const pct = Math.max((prod.sales / max) * 100, 2);
              return (
                <View key={prod.id || i} style={styles.productRow}>
                  <View style={styles.productRowInfo}>
                    <Text style={styles.productRowName} numberOfLines={1}>{prod.name}</Text>
                    <Text style={styles.productRowSub}>{prod.sales} sold · ₱{Number(prod.revenue || 0).toLocaleString()}</Text>
                  </View>
                  <View style={styles.progressTrack}>
                    <View style={[styles.progressBar, { width: `${pct}%` }]} />
                  </View>
                </View>
              );
            })
          )}
        </View>

        {/* Sales Funnel */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Sales Funnel</Text>
          <Text style={styles.sectionSub}>Lifecycle performance · {FILTER_LABELS[dateFilter]}</Text>
          {FUNNEL_STEPS.map((step, i) => {
            const val = funnel[step.key] || 0;
            const pct = Math.max((val / funnelMax) * 100 * (1 - i * 0.08), 2);
            return (
              <View key={step.key} style={styles.funnelRow}>
                <Text style={styles.funnelLabel}>{step.label}</Text>
                <View style={styles.funnelTrack}>
                  <View style={[styles.funnelBar, { width: `${pct}%`, backgroundColor: step.color }]}>
                    <Text style={styles.funnelVal}>{loading ? '—' : val.toLocaleString()}</Text>
                  </View>
                </View>
              </View>
            );
          })}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  toast: { position: 'absolute', top: 60, left: Spacing.xl, right: Spacing.xl, zIndex: 100, backgroundColor: Colors.success, borderRadius: Radius.lg, padding: Spacing.lg },
  toastText: { color: Colors.white, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  scroll: { padding: Spacing.xl, paddingBottom: 120 },
  pageHeader: { marginBottom: Spacing.xl },
  eyebrow: { fontSize: FontSize.xs, color: Colors.textMuted, letterSpacing: 2, fontWeight: FontWeight.bold, marginBottom: 4 },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.heavy, color: Colors.textPrimary },
  pageTitleAccent: { color: Colors.primary, fontStyle: 'italic' },
  filterLabel: { fontSize: FontSize.xs, color: Colors.textMuted, marginTop: 4 },
  filterRow: { flexDirection: 'row', gap: Spacing.xs, marginBottom: Spacing.xl, backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: 4 },
  filterTab: { flex: 1, paddingVertical: Spacing.sm, borderRadius: Radius.sm, alignItems: 'center' },
  filterTabActive: { backgroundColor: Colors.primary },
  filterTabText: { fontSize: FontSize.xs, fontWeight: FontWeight.bold, color: Colors.textMuted, letterSpacing: 1 },
  filterTabTextActive: { color: Colors.white },
  kpiGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.md, marginBottom: Spacing.xl },
  kpiCard: { width: '47%', backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.lg, borderWidth: 1, borderColor: Colors.border, ...Shadow.sm },
  kpiCardPrimary: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  kpiIcon: { fontSize: 22, marginBottom: Spacing.xs },
  kpiValue: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  kpiValuePrimary: { color: Colors.white },
  kpiLabel: { fontSize: FontSize.xs, color: Colors.textMuted, marginTop: 2 },
  kpiLabelPrimary: { color: 'rgba(255,255,255,0.7)' },
  liveDot: { marginTop: Spacing.xs },
  liveDotText: { fontSize: 8, color: Colors.success, fontWeight: FontWeight.bold },
  section: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.xl, borderWidth: 1, borderColor: Colors.border },
  sectionTitle: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  sectionSub: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: Spacing.lg },
  productRow: { marginBottom: Spacing.lg },
  productRowInfo: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  productRowName: { fontSize: FontSize.sm, fontWeight: FontWeight.semibold, color: Colors.textPrimary, flex: 1 },
  productRowSub: { fontSize: FontSize.xs, color: Colors.textMuted },
  progressTrack: { height: 6, backgroundColor: Colors.bgMedium, borderRadius: 3, overflow: 'hidden' },
  progressBar: { height: '100%', backgroundColor: Colors.primary, borderRadius: 3 },
  funnelRow: { flexDirection: 'row', alignItems: 'center', marginBottom: Spacing.sm, gap: Spacing.md },
  funnelLabel: { width: 80, fontSize: FontSize.xs, color: Colors.textSecondary, fontWeight: FontWeight.semibold },
  funnelTrack: { flex: 1, height: 28, backgroundColor: Colors.bgMedium, borderRadius: Radius.full, overflow: 'hidden' },
  funnelBar: { height: '100%', borderRadius: Radius.full, justifyContent: 'center', alignItems: 'flex-end', paddingHorizontal: Spacing.sm, minWidth: 36 },
  funnelVal: { fontSize: FontSize.xs, color: Colors.white, fontWeight: FontWeight.bold },
  emptyText: { fontSize: FontSize.sm, color: Colors.textMuted, textAlign: 'center', padding: Spacing.xl },
});
