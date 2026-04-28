/**
 * Admin Products — View all platform products, delete
 */
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, Image, Alert, TextInput, RefreshControl } from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/store/authStore';
import { ConfirmationModal } from '@/components/ui/ConfirmationModal';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface Product { id: string; name: string; price: number; stock: number; image: any; sellerName?: string; }

export default function AdminProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [deleteTarget, setDeleteTarget] = useState<Product | null>(null);
  const [deleting, setDeleting] = useState(false);
  const { backendIp, backendPort } = useAuthStore();
  const baseUrl = `http://${backendIp}:${backendPort}`;

  const fetchProducts = useCallback(async () => {
    try {
      const res = await api.get('/admin/products');
      setProducts(res.data || []);
    } catch { setProducts([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchProducts(); }, [fetchProducts]);

  const deleteProduct = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await api.delete(`/admin/products/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchProducts();
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to delete product.'));
    } finally { setDeleting(false); }
  };

  const imgUrl = (image: any) => {
    try { const arr = typeof image === 'string' ? JSON.parse(image) : image; if (Array.isArray(arr) && arr.length > 0) return `${baseUrl}/uploads/products/${arr[0]}`; } catch {}
    return 'https://placehold.co/80x80/2A2623/D4B896?text=No+Image';
  };

  const filtered = products.filter(p => !search || p.name.toLowerCase().includes(search.toLowerCase()));

  const renderItem = ({ item }: { item: Product }) => (
    <View style={styles.card}>
      <Image source={{ uri: imgUrl(item.image) }} style={styles.img} />
      <View style={styles.info}>
        <Text style={styles.name} numberOfLines={2}>{item.name}</Text>
        <Text style={styles.price}>₱{Number(item.price).toLocaleString()}</Text>
        <Text style={styles.stock}>Stock: {item.stock}</Text>
        {item.sellerName && <Text style={styles.seller}>By {item.sellerName}</Text>}
      </View>
      <TouchableOpacity style={styles.deleteBtn} onPress={() => setDeleteTarget(item)}>
        <Text style={styles.deleteBtnText}>🗑️</Text>
      </TouchableOpacity>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.pageTitle}>All Products</Text>
      <View style={styles.searchBox}>
        <TextInput style={styles.searchInput} value={search} onChangeText={setSearch} placeholder="Search products..." placeholderTextColor={Colors.textMuted} />
      </View>
      <FlatList
        data={filtered}
        keyExtractor={item => item.id}
        renderItem={renderItem}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchProducts(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={<Text style={styles.emptyText}>{loading ? 'Loading...' : 'No products found.'}</Text>}
      />
      <ConfirmationModal visible={!!deleteTarget} title="Delete Product" message={`Delete "${deleteTarget?.name}"?`} confirmLabel="Delete" danger loading={deleting} onConfirm={deleteProduct} onCancel={() => setDeleteTarget(null)} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, padding: Spacing.xl },
  searchBox: { paddingHorizontal: Spacing.xl, marginBottom: Spacing.md },
  searchInput: { backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, fontSize: FontSize.md, borderWidth: 1, borderColor: Colors.border },
  list: { padding: Spacing.xl, paddingBottom: 100 },
  card: { flexDirection: 'row', backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.md, marginBottom: Spacing.md, borderWidth: 1, borderColor: Colors.border, alignItems: 'center' },
  img: { width: 72, height: 72, borderRadius: Radius.md, backgroundColor: Colors.bgMedium },
  info: { flex: 1, marginLeft: Spacing.md },
  name: { fontSize: FontSize.md, fontWeight: FontWeight.semibold, color: Colors.textPrimary, marginBottom: 2 },
  price: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.primary, marginBottom: 2 },
  stock: { fontSize: FontSize.xs, color: Colors.textMuted },
  seller: { fontSize: FontSize.xs, color: Colors.textSecondary, marginTop: 2 },
  deleteBtn: { padding: Spacing.md },
  deleteBtnText: { fontSize: 20 },
  emptyText: { textAlign: 'center', color: Colors.textMuted, marginTop: 60, fontSize: FontSize.md },
});
