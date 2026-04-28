/**
 * Admin Sellers — Approve / Reject seller applications with reason
 * Mirrors /admin/sellers/page.js
 */
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, TextInput, Alert, RefreshControl, Modal, Image } from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';
import { useAuthStore } from '@/store/authStore';

interface Seller {
  id: string; name: string; email: string; status: string; isVerified: boolean;
  mobileNumber?: string; gcashNumber?: string; createdAt: string;
  indigencyCertificate?: string; validId?: string; rejectionReason?: string;
}

export default function AdminSellers() {
  const [sellers, setSellers] = useState<Seller[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [tab, setTab] = useState<'pending' | 'verified' | 'rejected'>('pending');
  const [rejectTarget, setRejectTarget] = useState<Seller | null>(null);
  const [rejectReason, setRejectReason] = useState('');
  const [acting, setActing] = useState(false);
  const { backendIp, backendPort } = useAuthStore();
  const baseUrl = `http://${backendIp}:${backendPort}`;

  const fetchSellers = useCallback(async () => {
    try {
      const res = await api.get('/admin/sellers');
      setSellers(res.data || []);
    } catch { setSellers([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchSellers(); }, [fetchSellers]);

  const filtered = sellers.filter(s => {
    if (tab === 'pending') return !s.isVerified && s.status !== 'rejected';
    if (tab === 'verified') return s.isVerified;
    if (tab === 'rejected') return s.status === 'rejected';
    return true;
  });

  const approve = async (seller: Seller) => {
    setActing(true);
    try {
      await api.put(`/admin/sellers/${seller.id}/approve`);
      Alert.alert('Approved', `${seller.name} has been approved as a seller.`);
      fetchSellers();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to approve seller.'));
    } finally { setActing(false); }
  };

  const reject = async () => {
    if (!rejectTarget || !rejectReason.trim()) {
      Alert.alert('Required', 'Please provide a rejection reason.');
      return;
    }
    setActing(true);
    try {
      await api.put(`/admin/sellers/${rejectTarget.id}/reject`, { reason: rejectReason });
      setRejectTarget(null);
      setRejectReason('');
      fetchSellers();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to reject seller.'));
    } finally { setActing(false); }
  };

  const renderSeller = ({ item }: { item: Seller }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <View style={{ flex: 1 }}>
          <Text style={styles.sellerName}>{item.name}</Text>
          <Text style={styles.sellerEmail}>{item.email}</Text>
          {item.mobileNumber && <Text style={styles.meta}>📱 {item.mobileNumber}</Text>}
          {item.gcashNumber && <Text style={styles.meta}>💚 {item.gcashNumber}</Text>}
        </View>
        <Badge label={item.isVerified ? 'Verified' : item.status === 'rejected' ? 'Rejected' : 'Pending'} status={item.isVerified ? 'active' : item.status === 'rejected' ? 'rejected' : 'pending'} />
      </View>

      {/* Documents */}
      {item.indigencyCertificate && (
        <View style={styles.docRow}>
          <Text style={styles.docLabel}>📄 Indigency Cert</Text>
          <Image source={{ uri: `${baseUrl}/uploads/${item.indigencyCertificate}` }} style={styles.docThumb} />
        </View>
      )}

      {item.rejectionReason && (
        <View style={styles.rejectBanner}>
          <Text style={styles.rejectReason}>Rejected: {item.rejectionReason}</Text>
        </View>
      )}

      {!item.isVerified && item.status !== 'rejected' && (
        <View style={styles.actions}>
          <TouchableOpacity style={styles.approveBtn} onPress={() => approve(item)}>
            <Text style={styles.approveBtnText}>✅ Approve</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.rejectBtn} onPress={() => setRejectTarget(item)}>
            <Text style={styles.rejectBtnText}>✗ Reject</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.pageTitle}>Seller Applications</Text>

      <View style={styles.tabRow}>
        {(['pending', 'verified', 'rejected'] as const).map(t => (
          <TouchableOpacity key={t} style={[styles.tab, tab === t && styles.tabActive]} onPress={() => setTab(t)}>
            <Text style={[styles.tabText, tab === t && styles.tabTextActive]}>{t.charAt(0).toUpperCase() + t.slice(1)}</Text>
          </TouchableOpacity>
        ))}
      </View>

      <FlatList
        data={filtered}
        keyExtractor={item => item.id}
        renderItem={renderSeller}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchSellers(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={<Text style={styles.emptyText}>{loading ? 'Loading...' : `No ${tab} sellers.`}</Text>}
      />

      {/* Reject Modal */}
      <Modal visible={!!rejectTarget} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>Reject Application</Text>
            <Text style={styles.modalSub}>Provide a clear reason for rejecting <Text style={{ color: Colors.primary }}>{rejectTarget?.name}</Text>.</Text>
            <TextInput
              style={styles.reasonInput}
              value={rejectReason}
              onChangeText={setRejectReason}
              placeholder="Rejection reason (required)..."
              placeholderTextColor={Colors.textMuted}
              multiline numberOfLines={4}
            />
            <View style={styles.modalBtns}>
              <Button label="Cancel" variant="outline" size="md" style={{ flex: 1 }} onPress={() => { setRejectTarget(null); setRejectReason(''); }} />
              <Button label="Reject" variant="danger" size="md" style={{ flex: 1 }} loading={acting} onPress={reject} />
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
  tabRow: { flexDirection: 'row', gap: Spacing.sm, paddingHorizontal: Spacing.xl, marginBottom: Spacing.lg },
  tab: { paddingHorizontal: Spacing.lg, paddingVertical: Spacing.sm, borderRadius: Radius.full, backgroundColor: Colors.bgDark, borderWidth: 1, borderColor: Colors.border },
  tabActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  tabText: { fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  tabTextActive: { color: Colors.white },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  card: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.lg, borderWidth: 1, borderColor: Colors.border },
  cardHeader: { flexDirection: 'row', gap: Spacing.md, marginBottom: Spacing.md },
  sellerName: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  sellerEmail: { fontSize: FontSize.sm, color: Colors.textMuted },
  meta: { fontSize: FontSize.xs, color: Colors.textSecondary, marginTop: 2 },
  docRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.md, marginBottom: Spacing.sm },
  docLabel: { fontSize: FontSize.sm, color: Colors.textSecondary },
  docThumb: { width: 48, height: 48, borderRadius: Radius.sm, backgroundColor: Colors.bgMedium },
  rejectBanner: { backgroundColor: Colors.errorLight, borderRadius: Radius.sm, padding: Spacing.sm, marginBottom: Spacing.sm },
  rejectReason: { fontSize: FontSize.xs, color: Colors.error, fontStyle: 'italic' },
  actions: { flexDirection: 'row', gap: Spacing.md, marginTop: Spacing.md },
  approveBtn: { flex: 1, backgroundColor: Colors.successLight, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center' },
  approveBtnText: { color: Colors.success, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  rejectBtn: { flex: 1, backgroundColor: Colors.errorLight, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center' },
  rejectBtnText: { color: Colors.error, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  emptyText: { textAlign: 'center', color: Colors.textMuted, marginTop: 60, fontSize: FontSize.md },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalSheet: { backgroundColor: Colors.bgDark, borderTopLeftRadius: Radius.xl, borderTopRightRadius: Radius.xl, padding: Spacing.xxl },
  modalTitle: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.xs },
  modalSub: { fontSize: FontSize.md, color: Colors.textSecondary, marginBottom: Spacing.xl },
  reasonInput: { backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.md, padding: Spacing.lg, fontSize: FontSize.md, minHeight: 90, textAlignVertical: 'top', borderWidth: 1, borderColor: Colors.border, marginBottom: Spacing.xl },
  modalBtns: { flexDirection: 'row', gap: Spacing.md },
});
