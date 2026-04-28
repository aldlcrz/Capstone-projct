/**
 * Admin Reports — View and resolve user-submitted reports
 * Mirrors /admin/reports/page.js
 */
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, Alert, RefreshControl } from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { Badge } from '@/components/ui/Badge';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface Report {
  id: string; reason: string; status: string; createdAt: string;
  Reporter?: { name: string }; reportedProduct?: { name: string }; reportedSeller?: { name: string };
  type: string;
}

export default function AdminReports() {
  const [reports, setReports] = useState<Report[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [tab, setTab] = useState<'pending' | 'resolved'>('pending');

  const fetchReports = useCallback(async () => {
    try {
      const res = await api.get('/admin/reports');
      setReports(res.data || []);
    } catch { setReports([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchReports(); }, [fetchReports]);

  const resolve = async (report: Report) => {
    try {
      await api.put(`/admin/reports/${report.id}/resolve`);
      fetchReports();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to resolve report.'));
    }
  };

  const filtered = reports.filter(r => tab === 'pending' ? r.status !== 'resolved' : r.status === 'resolved');

  const renderReport = ({ item }: { item: Report }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.reportType}>⚠️ {item.type === 'product' ? 'Product Report' : 'Seller Report'}</Text>
        <Badge label={item.status} status={item.status === 'resolved' ? 'active' : 'pending'} />
      </View>
      <Text style={styles.subject} numberOfLines={1}>
        {item.reportedProduct?.name || item.reportedSeller?.name || 'Unknown'}
      </Text>
      <Text style={styles.reporter}>Reported by: {item.Reporter?.name || 'User'}</Text>
      <Text style={styles.reason} numberOfLines={3}>{item.reason}</Text>
      <Text style={styles.date}>{new Date(item.createdAt).toLocaleDateString('en-PH')}</Text>
      {item.status !== 'resolved' && (
        <TouchableOpacity style={styles.resolveBtn} onPress={() => Alert.alert('Resolve Report', 'Mark this report as resolved?', [{ text: 'Cancel' }, { text: 'Resolve', onPress: () => resolve(item) }])}>
          <Text style={styles.resolveBtnText}>✅ Mark as Resolved</Text>
        </TouchableOpacity>
      )}
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.pageTitle}>Reports</Text>
      <View style={styles.tabRow}>
        {(['pending', 'resolved'] as const).map(t => (
          <TouchableOpacity key={t} style={[styles.tab, tab === t && styles.tabActive]} onPress={() => setTab(t)}>
            <Text style={[styles.tabText, tab === t && styles.tabTextActive]}>{t.charAt(0).toUpperCase() + t.slice(1)}</Text>
          </TouchableOpacity>
        ))}
      </View>
      <FlatList
        data={filtered}
        keyExtractor={item => item.id}
        renderItem={renderReport}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchReports(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={<Text style={styles.emptyText}>{loading ? 'Loading...' : `No ${tab} reports.`}</Text>}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, padding: Spacing.xl },
  tabRow: { flexDirection: 'row', gap: Spacing.sm, paddingHorizontal: Spacing.xl, marginBottom: Spacing.lg },
  tab: { paddingHorizontal: Spacing.xl, paddingVertical: Spacing.sm, borderRadius: Radius.full, backgroundColor: Colors.bgDark, borderWidth: 1, borderColor: Colors.border },
  tabActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  tabText: { fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  tabTextActive: { color: Colors.white },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  card: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.md, borderWidth: 1, borderColor: Colors.border },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: Spacing.sm },
  reportType: { fontSize: FontSize.sm, fontWeight: FontWeight.bold, color: Colors.warning },
  subject: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  reporter: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: Spacing.sm },
  reason: { fontSize: FontSize.sm, color: Colors.textSecondary, lineHeight: 18, marginBottom: Spacing.sm },
  date: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: Spacing.md },
  resolveBtn: { backgroundColor: Colors.successLight, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center' },
  resolveBtnText: { color: Colors.success, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  emptyText: { textAlign: 'center', color: Colors.textMuted, marginTop: 60, fontSize: FontSize.md },
});
