/**
 * Seller Orders — Receive and manage customer orders
 * Actions: accept (Processing), ship, complete
 * Status pipeline: Pending → Processing → Shipped → Delivered → Completed
 * Real-time: new_order, order_status_update
 */
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, ScrollView, Alert, RefreshControl } from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { Badge } from '@/components/ui/Badge';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface OrderItem { id: string; name: string; qty: number; price: number; size: string; }
interface Order {
  id: string; status: string; totalAmount: number; paymentMethod: string;
  createdAt: string; OrderItems?: OrderItem[];
  Customer?: { name: string; email: string };
  shippingAddress?: any;
}

const STATUS_TABS = ['All', 'Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'];

const NEXT_STATUS: Record<string, string | null> = {
  'Pending': 'Processing',
  'Processing': 'Shipped',
  'Shipped': 'Delivered',
  'Delivered': 'Completed',
};

export default function SellerOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [tab, setTab] = useState('All');
  const [updatingId, setUpdatingId] = useState<string | null>(null);
  const { socket } = useSocket();

  const fetchOrders = useCallback(async () => {
    try {
      const res = await api.get('/orders/seller-orders');
      setOrders(res.data || []);
    } catch { setOrders([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchOrders(); }, [fetchOrders]);

  useEffect(() => {
    if (!socket) return;
    socket.on('new_order', fetchOrders);
    socket.on('order_status_update', fetchOrders);
    return () => { socket.off('new_order', fetchOrders); socket.off('order_status_update', fetchOrders); };
  }, [socket, fetchOrders]);

  const updateStatus = async (order: Order, newStatus: string) => {
    setUpdatingId(order.id);
    try {
      await api.put(`/orders/${order.id}/status`, { status: newStatus });
      fetchOrders();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to update order status.'));
    } finally { setUpdatingId(null); }
  };

  const filtered = tab === 'All' ? orders : orders.filter(o => o.status === tab);

  const renderOrder = ({ item }: { item: Order }) => {
    const next = NEXT_STATUS[item.status];
    const addr = item.shippingAddress;
    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Text style={styles.orderId}>#{item.id.slice(0, 8).toUpperCase()}</Text>
          <Badge label={item.status} status={item.status} />
        </View>
        <Text style={styles.customerName}>👤 {item.Customer?.name || 'Customer'}</Text>
        {addr && <Text style={styles.address}>📍 {[addr.street, addr.city, addr.postalCode].filter(Boolean).join(', ')}</Text>}
        <Text style={styles.date}>{new Date(item.createdAt).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })}</Text>

        {item.OrderItems?.map((oi, i) => (
          <Text key={i} style={styles.itemLine}>• {oi.name} × {oi.qty} ({oi.size})</Text>
        ))}

        <View style={styles.footer}>
          <Text style={styles.total}>₱{Number(item.totalAmount).toLocaleString()}</Text>
          <Text style={styles.payMethod}>{item.paymentMethod}</Text>
        </View>

        {next && (
          <TouchableOpacity
            style={[styles.updateBtn, updatingId === item.id && styles.updateBtnDisabled]}
            onPress={() => updateStatus(item, next)}
            disabled={updatingId === item.id}
          >
            <Text style={styles.updateBtnText}>
              {updatingId === item.id ? 'Updating...' : `Mark as ${next} →`}
            </Text>
          </TouchableOpacity>
        )}
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.pageTitle}>Orders</Text>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabs}>
        {STATUS_TABS.map(t => (
          <TouchableOpacity key={t} style={[styles.tab, tab === t && styles.tabActive]} onPress={() => setTab(t)}>
            <Text style={[styles.tabText, tab === t && styles.tabTextActive]}>{t}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <FlatList
        data={filtered}
        keyExtractor={item => item.id}
        renderItem={renderOrder}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchOrders(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={<Text style={styles.emptyText}>{loading ? 'Loading...' : 'No orders found.'}</Text>}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, padding: Spacing.xl },
  tabs: { paddingHorizontal: Spacing.lg, paddingBottom: Spacing.md, gap: Spacing.sm },
  tab: { paddingHorizontal: Spacing.lg, paddingVertical: Spacing.sm, borderRadius: Radius.full, backgroundColor: Colors.bgDark, borderWidth: 1, borderColor: Colors.border },
  tabActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  tabText: { fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  tabTextActive: { color: Colors.white },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  card: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.lg, borderWidth: 1, borderColor: Colors.border },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: Spacing.sm },
  orderId: { fontSize: FontSize.sm, fontWeight: FontWeight.bold, color: Colors.textPrimary, letterSpacing: 1 },
  customerName: { fontSize: FontSize.sm, color: Colors.textSecondary, marginBottom: 2 },
  address: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: 2 },
  date: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: Spacing.md },
  itemLine: { fontSize: FontSize.sm, color: Colors.textSecondary, lineHeight: 20 },
  footer: { flexDirection: 'row', justifyContent: 'space-between', marginTop: Spacing.md, paddingTop: Spacing.md, borderTopWidth: 1, borderTopColor: Colors.border },
  total: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.primary },
  payMethod: { fontSize: FontSize.sm, color: Colors.textMuted },
  updateBtn: { marginTop: Spacing.md, backgroundColor: Colors.primary, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center' },
  updateBtnDisabled: { opacity: 0.5 },
  updateBtnText: { color: Colors.white, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  emptyText: { textAlign: 'center', color: Colors.textMuted, marginTop: 60, fontSize: FontSize.md },
});
