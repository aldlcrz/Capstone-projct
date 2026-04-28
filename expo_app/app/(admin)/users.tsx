/**
 * Admin Users Screen — View, freeze, block, and restore user accounts
 * Mirrors /admin/users/page.js
 */
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, TextInput, Alert, RefreshControl, Modal } from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface User {
  id: string; name: string; email: string; role: string; status: string;
  createdAt: string; violationReason?: string;
}

type Action = 'freeze' | 'block' | 'restore' | null;

export default function AdminUsers() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('all');
  const [target, setTarget] = useState<{ user: User; action: Action } | null>(null);
  const [reason, setReason] = useState('');
  const [acting, setActing] = useState(false);

  const fetchUsers = useCallback(async () => {
    try {
      const res = await api.get('/admin/users');
      setUsers(res.data || []);
    } catch { setUsers([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchUsers(); }, [fetchUsers]);

  const filtered = users.filter(u => {
    const matchRole = roleFilter === 'all' || u.role === roleFilter;
    const s = search.toLowerCase();
    const matchSearch = !search || u.name.toLowerCase().includes(s) || u.email.toLowerCase().includes(s);
    return matchRole && matchSearch;
  });

  const applyAction = async () => {
    if (!target) return;
    const { user, action } = target;
    setActing(true);
    try {
      if (action === 'freeze') await api.put(`/admin/users/${user.id}/freeze`, { reason });
      else if (action === 'block') await api.put(`/admin/users/${user.id}/block`, { reason });
      else if (action === 'restore') await api.put(`/admin/users/${user.id}/restore`);
      setTarget(null);
      setReason('');
      fetchUsers();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Action failed.'));
    } finally { setActing(false); }
  };

  const renderUser = ({ item }: { item: User }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <View style={{ flex: 1 }}>
          <Text style={styles.userName}>{item.name}</Text>
          <Text style={styles.userEmail}>{item.email}</Text>
        </View>
        <View style={{ gap: Spacing.xs }}>
          <Badge label={item.role} color={item.role === 'admin' ? Colors.info : item.role === 'seller' ? Colors.warning : Colors.textMuted} />
          <Badge label={item.status} status={item.status} />
        </View>
      </View>
      <Text style={styles.joinDate}>Joined {new Date(item.createdAt).toLocaleDateString('en-PH')}</Text>

      <View style={styles.actionRow}>
        {item.status !== 'frozen' && item.role !== 'admin' && (
          <TouchableOpacity style={styles.warnBtn} onPress={() => setTarget({ user: item, action: 'freeze' })}>
            <Text style={styles.warnBtnText}>❄️ Freeze</Text>
          </TouchableOpacity>
        )}
        {item.status !== 'blocked' && item.role !== 'admin' && (
          <TouchableOpacity style={styles.dangerBtn} onPress={() => setTarget({ user: item, action: 'block' })}>
            <Text style={styles.dangerBtnText}>🚫 Block</Text>
          </TouchableOpacity>
        )}
        {(item.status === 'frozen' || item.status === 'blocked') && (
          <TouchableOpacity style={styles.successBtn} onPress={() => setTarget({ user: item, action: 'restore' })}>
            <Text style={styles.successBtnText}>✅ Restore</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.pageTitle}>Manage Users</Text>

      <View style={styles.searchRow}>
        <TextInput style={styles.searchInput} value={search} onChangeText={setSearch} placeholder="Search name or email..." placeholderTextColor={Colors.textMuted} />
      </View>
      <View style={styles.roleRow}>
        {['all', 'customer', 'seller', 'admin'].map(r => (
          <TouchableOpacity key={r} style={[styles.roleTab, roleFilter === r && styles.roleTabActive]} onPress={() => setRoleFilter(r)}>
            <Text style={[styles.roleTabText, roleFilter === r && styles.roleTabTextActive]}>{r}</Text>
          </TouchableOpacity>
        ))}
      </View>

      <FlatList
        data={filtered}
        keyExtractor={item => item.id}
        renderItem={renderUser}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchUsers(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={<Text style={styles.emptyText}>{loading ? 'Loading...' : 'No users found.'}</Text>}
      />

      {/* Action Modal */}
      <Modal visible={!!target} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>
              {target?.action === 'freeze' ? '❄️ Freeze Account' : target?.action === 'block' ? '🚫 Block Account' : '✅ Restore Account'}
            </Text>
            <Text style={styles.modalUser}>{target?.user.name}</Text>
            {target?.action !== 'restore' && (
              <TextInput
                style={styles.reasonInput}
                value={reason}
                onChangeText={setReason}
                placeholder="Reason (required for freeze/block)..."
                placeholderTextColor={Colors.textMuted}
                multiline
                numberOfLines={3}
              />
            )}
            <View style={styles.modalBtns}>
              <Button label="Cancel" variant="outline" size="md" style={{ flex: 1 }} onPress={() => { setTarget(null); setReason(''); }} />
              <Button label="Confirm" variant={target?.action === 'restore' ? 'primary' : 'danger'} size="md" style={{ flex: 1 }} loading={acting} onPress={applyAction} />
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
  searchRow: { paddingHorizontal: Spacing.xl, marginBottom: Spacing.md },
  searchInput: { backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, fontSize: FontSize.md, borderWidth: 1, borderColor: Colors.border },
  roleRow: { flexDirection: 'row', gap: Spacing.sm, paddingHorizontal: Spacing.xl, marginBottom: Spacing.lg },
  roleTab: { paddingHorizontal: Spacing.lg, paddingVertical: Spacing.sm, borderRadius: Radius.full, backgroundColor: Colors.bgDark, borderWidth: 1, borderColor: Colors.border },
  roleTabActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  roleTabText: { fontSize: FontSize.xs, color: Colors.textMuted, fontWeight: FontWeight.bold, textTransform: 'capitalize' },
  roleTabTextActive: { color: Colors.white },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  card: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.md, borderWidth: 1, borderColor: Colors.border },
  cardHeader: { flexDirection: 'row', gap: Spacing.md, marginBottom: Spacing.sm },
  userName: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  userEmail: { fontSize: FontSize.sm, color: Colors.textMuted },
  joinDate: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: Spacing.md },
  actionRow: { flexDirection: 'row', gap: Spacing.sm, flexWrap: 'wrap' },
  warnBtn: { backgroundColor: Colors.warningLight, borderRadius: Radius.sm, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  warnBtnText: { color: Colors.warning, fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  dangerBtn: { backgroundColor: Colors.errorLight, borderRadius: Radius.sm, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  dangerBtnText: { color: Colors.error, fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  successBtn: { backgroundColor: Colors.successLight, borderRadius: Radius.sm, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  successBtnText: { color: Colors.success, fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  emptyText: { textAlign: 'center', color: Colors.textMuted, marginTop: 60, fontSize: FontSize.md },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalSheet: { backgroundColor: Colors.bgDark, borderTopLeftRadius: Radius.xl, borderTopRightRadius: Radius.xl, padding: Spacing.xxl },
  modalTitle: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.xs },
  modalUser: { fontSize: FontSize.md, color: Colors.textSecondary, marginBottom: Spacing.lg },
  reasonInput: { backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.md, padding: Spacing.lg, fontSize: FontSize.md, minHeight: 80, textAlignVertical: 'top', borderWidth: 1, borderColor: Colors.border, marginBottom: Spacing.xl },
  modalBtns: { flexDirection: 'row', gap: Spacing.md },
});
