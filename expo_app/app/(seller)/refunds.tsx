/**
 * Seller Refunds — view and process refund requests from customers
 * Route: app/(seller)/refunds.tsx
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity,
  Alert, TextInput, RefreshControl, Modal, ScrollView,
} from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface Refund {
  id: string;
  reason: string;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: string;
  Order?: {
    id: string;
    totalAmount: number;
    paymentMethod: string;
    Customer?: { name: string; email: string };
  };
  Product?: { name: string };
}

export default function SellerRefunds() {
  const [refunds, setRefunds] = useState<Refund[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [tab, setTab] = useState<'pending' | 'approved' | 'rejected'>('pending');
  const [selected, setSelected] = useState<Refund | null>(null);
  const [rejectReason, setRejectReason] = useState('');
  const [processing, setProcessing] = useState(false);

  const fetchRefunds = useCallback(async () => {
    try {
      const res = await api.get('/seller/refunds');
      setRefunds(res.data || []);
    } catch { setRefunds([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchRefunds(); }, [fetchRefunds]);

  const handleApprove = async (refund: Refund) => {
    Alert.alert(
      'Approve Refund',
      `Approve refund request for Order #${refund.Order?.id?.slice(0, 8).toUpperCase()}?`,
      [
        { text: 'Cancel' },
        {
          text: 'Approve', onPress: async () => {
            setProcessing(true);
            try {
              await api.put(`/seller/refunds/${refund.id}/approve`);
              fetchRefunds();
            } catch (err: any) {
              Alert.alert('Error', getApiErrorMessage(err, 'Failed to approve refund.'));
            } finally { setProcessing(false); }
          }
        }
      ]
    );
  };

  const handleReject = async () => {
    if (!selected) return;
    if (!rejectReason.trim()) { Alert.alert('Required', 'Please provide a rejection reason.'); return; }
    setProcessing(true);
    try {
      await api.put(`/seller/refunds/${selected.id}/reject`, { reason: rejectReason.trim() });
      setSelected(null);
      setRejectReason('');
      fetchRefunds();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to reject refund.'));
    } finally { setProcessing(false); }
  };

  const filtered = refunds.filter(r => r.status === tab);

  const renderItem = ({ item }: { item: Refund }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.orderId}>
          Order #{item.Order?.id?.slice(0, 8).toUpperCase() || '—'}
        </Text>
        <Badge label={item.status} status={item.status} />
      </View>

      <Text style={styles.productName} numberOfLines={1}>
        {item.Product?.name || 'Product'}
      </Text>

      <View style={styles.infoRow}>
        <Text style={styles.label}>Customer</Text>
        <Text style={styles.value}>{item.Order?.Customer?.name || 'Customer'}</Text>
      </View>
      <View style={styles.infoRow}>
        <Text style={styles.label}>Amount</Text>
        <Text style={[styles.value, { color: Colors.primary, fontWeight: FontWeight.bold }]}>
          ₱{Number(item.Order?.totalAmount || 0).toLocaleString()}
        </Text>
      </View>
      <View style={styles.infoRow}>
        <Text style={styles.label}>Payment</Text>
        <Text style={styles.value}>{item.Order?.paymentMethod || '—'}</Text>
      </View>

      <View style={styles.reasonBox}>
        <Text style={styles.reasonLabel}>Customer Reason:</Text>
        <Text style={styles.reasonText}>{item.reason}</Text>
      </View>

      <Text style={styles.dateText}>
        {new Date(item.createdAt).toLocaleDateString('en-PH', { dateStyle: 'medium' })}
      </Text>

      {item.status === 'pending' && (
        <View style={styles.actionRow}>
          <Button
            label="✓ Approve"
            variant="primary"
            size="sm"
            style={{ flex: 1 }}
            onPress={() => handleApprove(item)}
          />
          <Button
            label="✕ Reject"
            variant="danger"
            size="sm"
            style={{ flex: 1 }}
            onPress={() => { setSelected(item); setRejectReason(''); }}
          />
        </View>
      )}
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.pageTitle}>Refund Requests</Text>

      {/* Tabs */}
      <View style={styles.tabRow}>
        {(['pending', 'approved', 'rejected'] as const).map(t => (
          <TouchableOpacity
            key={t}
            style={[styles.tab, tab === t && styles.tabActive]}
            onPress={() => setTab(t)}
          >
            <Text style={[styles.tabText, tab === t && styles.tabTextActive]}>
              {t.charAt(0).toUpperCase() + t.slice(1)}
              {t === 'pending' && refunds.filter(r => r.status === 'pending').length > 0
                ? ` (${refunds.filter(r => r.status === 'pending').length})` : ''}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      <FlatList
        data={filtered}
        keyExtractor={r => r.id}
        renderItem={renderItem}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => { setRefreshing(true); fetchRefunds(); }}
            tintColor={Colors.primary}
          />
        }
        ListEmptyComponent={
          <Text style={styles.emptyText}>
            {loading ? 'Loading...' : `No ${tab} refund requests.`}
          </Text>
        }
      />

      {/* Reject Reason Modal */}
      <Modal visible={!!selected} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>Reject Refund</Text>
            <Text style={styles.modalSub}>
              Rejecting refund for Order #{selected?.Order?.id?.slice(0, 8).toUpperCase()}
            </Text>
            <Text style={styles.inputLabel}>Rejection Reason *</Text>
            <TextInput
              style={styles.textArea}
              value={rejectReason}
              onChangeText={setRejectReason}
              placeholder="Explain why this refund is being rejected..."
              placeholderTextColor={Colors.textMuted}
              multiline
              numberOfLines={4}
              textAlignVertical="top"
            />
            <View style={styles.modalBtns}>
              <Button
                label="Cancel"
                variant="outline"
                size="md"
                style={{ flex: 1 }}
                onPress={() => setSelected(null)}
              />
              <Button
                label="Submit Rejection"
                variant="danger"
                size="md"
                style={{ flex: 1 }}
                loading={processing}
                onPress={handleReject}
              />
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  pageTitle: {
    fontSize: FontSize.xxl, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, padding: Spacing.xl,
  },
  tabRow: {
    flexDirection: 'row', gap: Spacing.sm,
    paddingHorizontal: Spacing.xl, marginBottom: Spacing.lg,
  },
  tab: {
    paddingHorizontal: Spacing.lg, paddingVertical: Spacing.sm,
    borderRadius: Radius.full, backgroundColor: Colors.bgDark,
    borderWidth: 1, borderColor: Colors.border,
  },
  tabActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  tabText: { fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  tabTextActive: { color: Colors.white },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  card: {
    backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.xl, marginBottom: Spacing.md,
    borderWidth: 1, borderColor: Colors.border,
  },
  cardHeader: {
    flexDirection: 'row', justifyContent: 'space-between',
    alignItems: 'center', marginBottom: Spacing.sm,
  },
  orderId: { fontSize: FontSize.sm, fontWeight: FontWeight.bold, color: Colors.textMuted },
  productName: {
    fontSize: FontSize.md, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: Spacing.md,
  },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: Spacing.xs },
  label: { fontSize: FontSize.sm, color: Colors.textMuted },
  value: { fontSize: FontSize.sm, color: Colors.textSecondary },
  reasonBox: {
    backgroundColor: Colors.bgMedium, borderRadius: Radius.sm,
    padding: Spacing.md, marginTop: Spacing.md, marginBottom: Spacing.sm,
  },
  reasonLabel: { fontSize: FontSize.xs, color: Colors.textMuted, fontWeight: FontWeight.bold, marginBottom: 4 },
  reasonText: { fontSize: FontSize.sm, color: Colors.textSecondary, lineHeight: 18 },
  dateText: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: Spacing.md },
  actionRow: { flexDirection: 'row', gap: Spacing.md },
  emptyText: {
    textAlign: 'center', color: Colors.textMuted,
    marginTop: 60, fontSize: FontSize.md,
  },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalSheet: {
    backgroundColor: Colors.bgDark, borderTopLeftRadius: Radius.xl,
    borderTopRightRadius: Radius.xl, padding: Spacing.xxl,
  },
  modalTitle: {
    fontSize: FontSize.xl, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: Spacing.xs,
  },
  modalSub: { fontSize: FontSize.sm, color: Colors.textMuted, marginBottom: Spacing.xl },
  inputLabel: {
    fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.bold,
    textTransform: 'uppercase', letterSpacing: 1, marginBottom: Spacing.sm,
  },
  textArea: {
    backgroundColor: Colors.bgMedium, color: Colors.textPrimary,
    borderRadius: Radius.md, padding: Spacing.lg, fontSize: FontSize.md,
    minHeight: 100, borderWidth: 1, borderColor: Colors.border, marginBottom: Spacing.xl,
  },
  modalBtns: { flexDirection: 'row', gap: Spacing.md },
});
