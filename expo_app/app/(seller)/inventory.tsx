/**
 * Seller Inventory Screen — Product list with stock indicators, edit/delete
 */
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, Image, Alert, RefreshControl } from 'react-native';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { useAuthStore } from '@/store/authStore';
import { Badge } from '@/components/ui/Badge';
import { ConfirmationModal } from '@/components/ui/ConfirmationModal';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface Product {
  id: string; name: string; price: number; stock: number;
  image: any; categories: any; description?: string; costPerPiece?: number;
}

export default function SellerInventory() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<Product | null>(null);
  const [deleting, setDeleting] = useState(false);
  const { socket } = useSocket();
  const { backendIp, backendPort } = useAuthStore();
  const baseUrl = `http://${backendIp}:${backendPort}`;

  const fetchProducts = useCallback(async () => {
    try {
      const res = await api.get('/products/seller');
      setProducts(res.data || []);
    } catch { setProducts([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchProducts(); }, [fetchProducts]);

  useEffect(() => {
    if (!socket) return;
    socket.on('inventory_updated', fetchProducts);
    return () => { socket.off('inventory_updated', fetchProducts); };
  }, [socket, fetchProducts]);

  const deleteProduct = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await api.delete(`/products/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchProducts();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to delete product.'));
    } finally { setDeleting(false); }
  };

  const imgUrl = (image: any) => {
    try {
      const arr = typeof image === 'string' ? JSON.parse(image) : image;
      if (Array.isArray(arr) && arr.length > 0) return `${baseUrl}/uploads/products/${arr[0]}`;
    } catch {}
    return 'https://placehold.co/100x100/2A2623/D4B896?text=No+Image';
  };

  const stockColor = (s: number) => s === 0 ? Colors.error : s < 5 ? Colors.warning : Colors.success;
  const stockLabel = (s: number) => s === 0 ? 'Out of Stock' : s < 5 ? 'Low Stock' : 'In Stock';

  const renderItem = ({ item }: { item: Product }) => (
    <View style={styles.card}>
      <Image source={{ uri: imgUrl(item.image) }} style={styles.image} />
      <View style={styles.info}>
        <Text style={styles.name} numberOfLines={2}>{item.name}</Text>
        <Text style={styles.price}>₱{Number(item.price).toLocaleString()}</Text>
        <View style={styles.stockRow}>
          <View style={[styles.stockDot, { backgroundColor: stockColor(item.stock) }]} />
          <Text style={[styles.stockLabel, { color: stockColor(item.stock) }]}>{stockLabel(item.stock)} ({item.stock})</Text>
        </View>
        <View style={styles.actions}>
          <TouchableOpacity style={styles.editBtn} onPress={() => router.push({ pathname: '/(seller)/edit-product' as any, params: { id: item.id } })}>
            <Text style={styles.editBtnText}>✏️ Edit</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.deleteBtn} onPress={() => setDeleteTarget(item)}>
            <Text style={styles.deleteBtnText}>🗑️ Delete</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Inventory</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => router.push('/(seller)/add-product' as any)}>
          <Text style={styles.addBtnText}>+ Add Product</Text>
        </TouchableOpacity>
      </View>

      <FlatList
        data={products}
        keyExtractor={item => item.id}
        renderItem={renderItem}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchProducts(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={<Text style={styles.emptyText}>{loading ? 'Loading...' : 'No products yet. Add your first product!'}</Text>}
      />

      <ConfirmationModal
        visible={!!deleteTarget}
        title="Delete Product"
        message={`Delete "${deleteTarget?.name}"? This action cannot be undone.`}
        confirmLabel="Delete"
        danger
        loading={deleting}
        onConfirm={deleteProduct}
        onCancel={() => setDeleteTarget(null)}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: Spacing.xl, borderBottomWidth: 1, borderBottomColor: Colors.border },
  title: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  addBtn: { backgroundColor: Colors.primary, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.sm },
  addBtnText: { color: Colors.white, fontSize: FontSize.sm, fontWeight: FontWeight.bold },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  card: { flexDirection: 'row', backgroundColor: Colors.bgDark, borderRadius: Radius.lg, marginBottom: Spacing.md, padding: Spacing.md, borderWidth: 1, borderColor: Colors.border },
  image: { width: 90, height: 90, borderRadius: Radius.md, backgroundColor: Colors.bgMedium },
  info: { flex: 1, marginLeft: Spacing.md },
  name: { fontSize: FontSize.md, fontWeight: FontWeight.semibold, color: Colors.textPrimary, marginBottom: 4 },
  price: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.primary, marginBottom: 4 },
  stockRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.xs, marginBottom: Spacing.sm },
  stockDot: { width: 8, height: 8, borderRadius: 4 },
  stockLabel: { fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  actions: { flexDirection: 'row', gap: Spacing.sm },
  editBtn: { backgroundColor: Colors.bgMedium, borderRadius: Radius.sm, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  editBtnText: { color: Colors.accent, fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  deleteBtn: { backgroundColor: Colors.errorLight, borderRadius: Radius.sm, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  deleteBtnText: { color: Colors.error, fontSize: FontSize.xs, fontWeight: FontWeight.semibold },
  emptyText: { textAlign: 'center', color: Colors.textMuted, marginTop: 60, fontSize: FontSize.md },
});
