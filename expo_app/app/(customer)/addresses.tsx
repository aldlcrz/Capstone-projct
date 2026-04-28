/**
 * Customer Address Book — CRUD for saved addresses
 * Full clone of web address management (used in checkout)
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity,
  Alert, Modal, ScrollView, RefreshControl, ActivityIndicator,
} from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { ConfirmationModal } from '@/components/ui/ConfirmationModal';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';
import { router } from 'expo-router';
import { useLocation } from '@/hooks/useLocation';

interface Address {
  id: string; recipientName: string; phone: string; houseNo: string;
  street: string; barangay: string; city: string; province: string;
  postalCode: string; isDefault: boolean;
}

const EMPTY = { recipientName: '', phone: '', houseNo: '', street: '', barangay: '', city: '', province: '', postalCode: '' };

export default function AddressesScreen() {
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<Address | null>(null);
  const [form, setForm] = useState({ ...EMPTY });
  const [saving, setSaving] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<Address | null>(null);
  const [deleting, setDeleting] = useState(false);
  const { getCurrentLocation, loading: gpsLoading } = useLocation();

  const fillFromGPS = async () => {
    const loc = await getCurrentLocation();
    if (loc) {
      setForm(f => ({
        ...f,
        city: loc.city || f.city,
        province: loc.province || f.province,
        street: loc.street || f.street,
        barangay: loc.barangay || f.barangay,
        postalCode: loc.postalCode || f.postalCode,
      }));
    }
  };

  const fetchAddresses = useCallback(async () => {
    try {
      const res = await api.get('/addresses');
      setAddresses(res.data || []);
    } catch { setAddresses([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchAddresses(); }, [fetchAddresses]);

  const openForm = (addr?: Address) => {
    if (addr) {
      setEditing(addr);
      setForm({ recipientName: addr.recipientName, phone: addr.phone, houseNo: addr.houseNo, street: addr.street, barangay: addr.barangay, city: addr.city, province: addr.province, postalCode: addr.postalCode });
    } else {
      setEditing(null);
      setForm({ ...EMPTY });
    }
    setShowForm(true);
  };

  const handleSave = async () => {
    if (!form.recipientName.trim() || !form.phone.trim() || !form.street.trim() || !form.city.trim() || !form.province.trim()) {
      Alert.alert('Incomplete', 'Please fill in all required fields.'); return;
    }
    setSaving(true);
    try {
      if (editing) {
        await api.put(`/addresses/${editing.id}`, { ...form, isDefault: editing.isDefault });
      } else {
        await api.post('/addresses', { ...form, isDefault: addresses.length === 0 });
      }
      setShowForm(false);
      fetchAddresses();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to save address.'));
    } finally { setSaving(false); }
  };

  const setDefault = async (addr: Address) => {
    try {
      await api.patch(`/addresses/${addr.id}/set-default`, {});
      fetchAddresses();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to set default.'));
    }
  };

  const deleteAddr = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await api.delete(`/addresses/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchAddresses();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to delete address.'));
    } finally { setDeleting(false); }
  };

  const renderAddr = ({ item }: { item: Address }) => (
    <View style={[styles.card, item.isDefault && styles.cardDefault]}>
      {item.isDefault && (
        <View style={styles.defaultBadge}>
          <Text style={styles.defaultBadgeText}>DEFAULT</Text>
        </View>
      )}
      <Text style={styles.addrName}>{item.recipientName}</Text>
      <Text style={styles.addrPhone}>{item.phone}</Text>
      <Text style={styles.addrLine}>{item.houseNo} {item.street}, {item.barangay}</Text>
      <Text style={styles.addrLine}>{item.city}, {item.province} {item.postalCode}</Text>
      <View style={styles.addrActions}>
        <TouchableOpacity style={styles.editBtn} onPress={() => openForm(item)}>
          <Text style={styles.editBtnText}>✏️ Edit</Text>
        </TouchableOpacity>
        {!item.isDefault && (
          <TouchableOpacity style={styles.defaultBtn} onPress={() => setDefault(item)}>
            <Text style={styles.defaultBtnText}>Set Default</Text>
          </TouchableOpacity>
        )}
        <TouchableOpacity style={styles.deleteBtn} onPress={() => setDeleteTarget(item)}>
          <Text style={styles.deleteBtnText}>🗑️ Delete</Text>
        </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backText}>←</Text>
        </TouchableOpacity>
        <Text style={styles.title}>My Addresses</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => openForm()}>
          <Text style={styles.addBtnText}>+ Add</Text>
        </TouchableOpacity>
      </View>

      <FlatList
        data={addresses}
        keyExtractor={item => item.id}
        renderItem={renderAddr}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchAddresses(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={
          <View style={styles.emptyState}>
            <Text style={styles.emptyIcon}>📍</Text>
            <Text style={styles.emptyText}>{loading ? 'Loading...' : 'No saved addresses yet.'}</Text>
            <Button label="Add Address" variant="primary" size="md" onPress={() => openForm()} style={{ marginTop: Spacing.xl }} />
          </View>
        }
      />

      {/* Add / Edit Modal */}
      <Modal visible={showForm} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>{editing ? 'Edit Address' : 'New Address'}</Text>
            <TouchableOpacity style={styles.gpsBtn} onPress={fillFromGPS} disabled={gpsLoading}>
              {gpsLoading
                ? <ActivityIndicator size="small" color={Colors.primary} />
                : <Text style={styles.gpsBtnText}>📍 Use My Location</Text>}
            </TouchableOpacity>
            <ScrollView showsVerticalScrollIndicator={false}>
              <Input label="Recipient Name *" value={form.recipientName} onChangeText={v => setForm(f => ({ ...f, recipientName: v }))} placeholder="Full name" />
              <Input label="Phone Number *" value={form.phone} onChangeText={v => setForm(f => ({ ...f, phone: v }))} placeholder="09XXXXXXXXX" keyboardType="numeric" />
              <View style={styles.twoCol}>
                <View style={{ flex: 1 }}><Input label="House No." value={form.houseNo} onChangeText={v => setForm(f => ({ ...f, houseNo: v }))} placeholder="Bldg/Unit #" /></View>
                <View style={{ width: Spacing.sm }} />
                <View style={{ flex: 2 }}><Input label="Street *" value={form.street} onChangeText={v => setForm(f => ({ ...f, street: v }))} placeholder="Street name" /></View>
              </View>
              <Input label="Barangay" value={form.barangay} onChangeText={v => setForm(f => ({ ...f, barangay: v }))} placeholder="Barangay" />
              <View style={styles.twoCol}>
                <View style={{ flex: 1 }}><Input label="City *" value={form.city} onChangeText={v => setForm(f => ({ ...f, city: v }))} placeholder="City" /></View>
                <View style={{ width: Spacing.sm }} />
                <View style={{ flex: 1 }}><Input label="Province *" value={form.province} onChangeText={v => setForm(f => ({ ...f, province: v }))} placeholder="Province" /></View>
              </View>
              <Input label="Postal Code" value={form.postalCode} onChangeText={v => setForm(f => ({ ...f, postalCode: v }))} placeholder="ZIP" keyboardType="numeric" />
            </ScrollView>
            <View style={styles.modalBtns}>
              <Button label="Cancel" variant="outline" size="md" style={{ flex: 1 }} onPress={() => setShowForm(false)} />
              <Button label={editing ? 'Update' : 'Save'} variant="primary" size="md" style={{ flex: 1 }} loading={saving} onPress={handleSave} />
            </View>
          </View>
        </View>
      </Modal>

      <ConfirmationModal
        visible={!!deleteTarget}
        title="Delete Address"
        message={`Delete address for ${deleteTarget?.recipientName}?`}
        confirmLabel="Delete"
        danger
        loading={deleting}
        onConfirm={deleteAddr}
        onCancel={() => setDeleteTarget(null)}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  header: { flexDirection: 'row', alignItems: 'center', padding: Spacing.xl, borderBottomWidth: 1, borderBottomColor: Colors.border },
  backText: { fontSize: FontSize.xl, color: Colors.textPrimary, fontWeight: FontWeight.bold, marginRight: Spacing.md },
  title: { flex: 1, fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  addBtn: { backgroundColor: Colors.primary, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.sm },
  addBtnText: { color: Colors.white, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  card: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.md, borderWidth: 1, borderColor: Colors.border },
  cardDefault: { borderColor: Colors.primary, borderWidth: 1.5 },
  defaultBadge: { backgroundColor: Colors.primary, borderRadius: Radius.sm, paddingHorizontal: Spacing.sm, paddingVertical: 2, alignSelf: 'flex-start', marginBottom: Spacing.sm },
  defaultBadgeText: { color: Colors.white, fontSize: FontSize.xs, fontWeight: FontWeight.bold, letterSpacing: 1 },
  addrName: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  addrPhone: { fontSize: FontSize.sm, color: Colors.textMuted, marginBottom: 4 },
  addrLine: { fontSize: FontSize.sm, color: Colors.textSecondary, marginBottom: 1 },
  addrActions: { flexDirection: 'row', gap: Spacing.sm, marginTop: Spacing.md, flexWrap: 'wrap' },
  editBtn: { backgroundColor: Colors.bgMedium, borderRadius: Radius.sm, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  editBtnText: { color: Colors.accent, fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  defaultBtn: { backgroundColor: Colors.successLight, borderRadius: Radius.sm, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  defaultBtnText: { color: Colors.success, fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  deleteBtn: { backgroundColor: Colors.errorLight, borderRadius: Radius.sm, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  deleteBtnText: { color: Colors.error, fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  emptyState: { flex: 1, alignItems: 'center', paddingTop: 80 },
  emptyIcon: { fontSize: 56, marginBottom: Spacing.xl },
  emptyText: { fontSize: FontSize.md, color: Colors.textMuted },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalSheet: { backgroundColor: Colors.bgDark, borderTopLeftRadius: Radius.xl, borderTopRightRadius: Radius.xl, padding: Spacing.xxl, maxHeight: '90%' },
  modalTitle: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.xl },
  twoCol: { flexDirection: 'row' },
  modalBtns: { flexDirection: 'row', gap: Spacing.md, marginTop: Spacing.xl },
  gpsBtn: {
    backgroundColor: Colors.bgMedium, borderRadius: Radius.md, padding: Spacing.md,
    alignItems: 'center', marginBottom: Spacing.xl, borderWidth: 1, borderColor: Colors.border,
  },
  gpsBtnText: { color: Colors.accent, fontSize: FontSize.sm, fontWeight: FontWeight.semibold },
});
