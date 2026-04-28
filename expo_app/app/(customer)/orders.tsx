/**
 * Customer Orders Screen — View and manage all orders
 * Status pipeline: Pending → Processing → Shipped → Delivered → Received by Buyer → Completed
 * Actions: Cancel (if Pending), Mark as Received, Request Refund
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, FlatList,
  TouchableOpacity, TextInput, Modal, Alert, RefreshControl, ScrollView,
} from 'react-native';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { ConfirmationModal } from '@/components/ui/ConfirmationModal';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface OrderItem { id: string; productId: string; name: string; qty: number; price: number; size: string; }
interface Order {
  id: string; status: string; totalAmount: number; paymentMethod: string;
  createdAt: string; OrderItems?: OrderItem[];
  Seller?: { name: string }; cancellationReason?: string;
}

const STATUS_TABS = ['All', 'Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'];

export default function CustomerOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [tab, setTab] = useState('All');
  const [cancelTarget, setCancelTarget] = useState<Order | null>(null);
  const [cancelReason, setCancelReason] = useState('');
  const [refundTarget, setRefundTarget] = useState<Order | null>(null);
  const [refundReason, setRefundReason] = useState('');
  const [actionLoading, setActionLoading] = useState(false);
  const { socket } = useSocket();

  const fetchOrders = useCallback(async () => {
    try {
      const res = await api.get('/orders/my-orders');
      setOrders(res.data || []);
    } catch { setOrders([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchOrders(); }, [fetchOrders]);

  useEffect(() => {
    if (!socket) return;
    socket.on('order_status_update', fetchOrders);
    return () => { socket.off('order_status_update', fetchOrders); };
  }, [socket, fetchOrders]);

  const filtered = tab === 'All' ? orders : orders.filter(o => o.status === tab);

  const cancelOrder = async () => {
    if (!cancelTarget || !cancelReason.trim()) { Alert.alert('Error', 'Please provide a cancellation reason.'); return; }
    setActionLoading(true);
    try {
      await api.put(`/orders/${cancelTarget.id}/cancel`, { reason: cancelReason });
      Alert.alert('Order Cancelled', 'Your order has been cancelled.');
      setCancelTarget(null);
      setCancelReason('');
      fetchOrders();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to cancel order.'));
    } finally { setActionLoading(false); }
  };

  const markReceived = async (order: Order) => {
    try {
      await api.put(`/orders/${order.id}/received`);
      fetchOrders();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to mark order as received.'));
    }
  };

  const requestRefund = async () => {
    if (!refundTarget || !refundReason.trim()) { Alert.alert('Error', 'Please provide a refund reason.'); return; }
    setActionLoading(true);
    try {
      await api.post(`/orders/${refundTarget.id}/refund`, { reason: refundReason });
      Alert.alert('Refund Requested', 'Your refund request has been submitted.');
      setRefundTarget(null);
      setRefundReason('');
      fetchOrders();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to request refund.'));
    } finally { setActionLoading(false); }
  };

  const renderOrder = ({ item }: { item: Order }) => (
    <View style={styles.orderCard}>
      <View style={styles.orderHeader}>
        <Text style={styles.orderId}>#{item.id.slice(0, 8).toUpperCase()}</Text>
        <Badge label={item.status} status={item.status} />
      </View>
      <Text style={styles.sellerName}>From: {item.Seller?.name || 'Seller'}</Text>
      <Text style={styles.orderDate}>{new Date(item.createdAt).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })}</Text>

      {item.OrderItems?.map((oi, i) => (
        <Text key={i} style={styles.orderItem}>• {oi.name} × {oi.qty} ({oi.size}) — ₱{Number(oi.price * oi.qty).toLocaleString()}</Text>
      ))}

      <View style={styles.orderFooter}>
        <Text style={styles.orderTotal}>₱{Number(item.totalAmount).toLocaleString()}</Text>
        <Text style={styles.payMethod}>{item.paymentMethod}</Text>
      </View>

      {/* Action buttons by status */}
      <View style={styles.actionRow}>
        {item.status === 'Pending' && (
          <TouchableOpacity style={styles.dangerBtn} onPress={() => setCancelTarget(item)}>
            <Text style={styles.dangerBtnText}>Cancel Order</Text>
          </TouchableOpacity>
        )}
        {item.status === 'Delivered' && (
          <TouchableOpacity style={styles.primaryBtn} onPress={() => Alert.alert('Confirm', 'Mark this order as received?', [{ text: 'Cancel' }, { text: 'Confirm', onPress: () => markReceived(item) }])}>
            <Text style={styles.primaryBtnText}>Mark as Received</Text>
          </TouchableOpacity>
        )}
        {item.status === 'Completed' && (
          <TouchableOpacity style={styles.outlineBtn} onPress={() => setRefundTarget(item)}>
            <Text style={styles.outlineBtnText}>Request Refund</Text>
          </TouchableOpacity>
        )}
        {item.status === 'Completed' && (
          <TouchableOpacity
            style={styles.reviewBtn}
            onPress={() => router.push({
              pathname: '/(customer)/leave-review' as any,
              params: {
                orderId: item.id,
                productId: item.OrderItems?.[0]?.productId || '',
                productName: item.OrderItems?.[0]?.name || 'Product',
              }
            })}
          >
            <Text style={styles.reviewBtnText}>⭐ Review</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.pageTitle}>My Orders</Text>

      {/* Status Tabs */}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.tabsContainer} contentContainerStyle={styles.tabs}>
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
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyEmoji}>{loading ? '⏳' : '🛍️'}</Text>
            <Text style={styles.emptyText}>{loading ? 'Loading your orders...' : 'No orders found.'}</Text>
            {!loading && (
              <Button 
                label="Browse Shop" 
                variant="primary" 
                size="md" 
                onPress={() => router.push('/(customer)/shop' as any)}
                style={styles.emptyBtn}
              />
            )}
          </View>
        }
      />

      {/* Cancel Modal */}
      <Modal visible={!!cancelTarget} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>Cancel Order</Text>
            <Text style={styles.modalDesc}>Please provide a reason for cancellation.</Text>
            <TextInput style={styles.reasonInput} value={cancelReason} onChangeText={setCancelReason} placeholder="Reason for cancellation..." placeholderTextColor={Colors.textMuted} multiline numberOfLines={3} />
            <View style={styles.modalActions}>
              <Button label="Back" variant="outline" size="md" style={{ flex: 1 }} onPress={() => setCancelTarget(null)} />
              <Button label="Cancel Order" variant="danger" size="md" style={{ flex: 1 }} loading={actionLoading} onPress={cancelOrder} />
            </View>
          </View>
        </View>
      </Modal>

      {/* Refund Modal */}
      <Modal visible={!!refundTarget} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>Request Refund</Text>
            <Text style={styles.modalDesc}>Describe why you are requesting a refund.</Text>
            <TextInput style={styles.reasonInput} value={refundReason} onChangeText={setRefundReason} placeholder="Refund reason..." placeholderTextColor={Colors.textMuted} multiline numberOfLines={3} />
            <View style={styles.modalActions}>
              <Button label="Back" variant="outline" size="md" style={{ flex: 1 }} onPress={() => setRefundTarget(null)} />
              <Button label="Submit Request" variant="primary" size="md" style={{ flex: 1 }} loading={actionLoading} onPress={requestRefund} />
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, padding: Spacing.xl },
  tabsContainer: { flexGrow: 0, marginBottom: Spacing.sm },
  tabs: { paddingHorizontal: Spacing.lg, paddingBottom: Spacing.md, gap: Spacing.sm },
  tab: { paddingHorizontal: Spacing.lg, paddingVertical: Spacing.sm, borderRadius: Radius.full, backgroundColor: Colors.bgDark, borderWidth: 1, borderColor: Colors.border },
  tabActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  tabText: { fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  tabTextActive: { color: Colors.white },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  orderCard: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.lg, borderWidth: 1, borderColor: Colors.border },
  orderHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: Spacing.sm },
  orderId: { fontSize: FontSize.sm, fontWeight: FontWeight.bold, color: Colors.textPrimary, letterSpacing: 1 },
  sellerName: { fontSize: FontSize.sm, color: Colors.textSecondary, marginBottom: 2 },
  orderDate: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: Spacing.md },
  orderItem: { fontSize: FontSize.sm, color: Colors.textSecondary, lineHeight: 20 },
  orderFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: Spacing.md, paddingTop: Spacing.md, borderTopWidth: 1, borderTopColor: Colors.border },
  orderTotal: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.primary },
  payMethod: { fontSize: FontSize.sm, color: Colors.textMuted },
  actionRow: { flexDirection: 'row', gap: Spacing.sm, marginTop: Spacing.md },
  dangerBtn: { flex: 1, backgroundColor: Colors.errorLight, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center' },
  dangerBtnText: { color: Colors.error, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  primaryBtn: { flex: 1, backgroundColor: Colors.primary, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center' },
  primaryBtnText: { color: Colors.white, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  outlineBtn: { flex: 1, borderWidth: 1, borderColor: Colors.accent, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center' },
  outlineBtnText: { color: Colors.accent, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  reviewBtn: { flex: 1, backgroundColor: `${Colors.warning}22`, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center', borderWidth: 1, borderColor: Colors.warning },
  reviewBtnText: { color: Colors.warning, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', marginTop: 80, paddingHorizontal: Spacing.xxl },
  emptyEmoji: { fontSize: 48, marginBottom: Spacing.md },
  emptyText: { textAlign: 'center', color: Colors.textMuted, fontSize: FontSize.md, marginBottom: Spacing.xl },
  emptyBtn: { minWidth: 160 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalSheet: { backgroundColor: Colors.bgDark, borderTopLeftRadius: Radius.xl, borderTopRightRadius: Radius.xl, padding: Spacing.xxl },
  modalTitle: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.sm },
  modalDesc: { fontSize: FontSize.md, color: Colors.textSecondary, marginBottom: Spacing.xl },
  reasonInput: { backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.md, padding: Spacing.lg, fontSize: FontSize.md, minHeight: 80, textAlignVertical: 'top', borderWidth: 1, borderColor: Colors.border, marginBottom: Spacing.xl },
  modalActions: { flexDirection: 'row', gap: Spacing.md },
});
