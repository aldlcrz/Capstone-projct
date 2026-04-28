/**
 * Customer Home Screen — Browse all products with search + category filter
 * Mirrors /home/page.js exactly:
 * - Fetch products from API
 * - Search by name/description
 * - Filter by category (modal picker)
 * - Real-time socket: inventory_updated, review_updated
 * - Product grid with price, rating, seller name
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, FlatList,
  TouchableOpacity, TextInput, Image, Modal,
  ActivityIndicator, RefreshControl, ScrollView,
} from 'react-native';
import { router } from 'expo-router';
import { api } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { useAuthStore } from '@/store/authStore';
import { Colors, FontSize, FontWeight, Spacing, Radius, Shadow } from '@/constants/theme';

interface Product {
  id: string; name: string; price: number; stock: number;
  image: any; rating?: number; reviewCount?: number;
  artisan?: string; categories?: any; description?: string;
}

const parseImages = (image: any, baseUrl: string): string => {
  try {
    const arr = typeof image === 'string' ? JSON.parse(image) : image;
    if (Array.isArray(arr) && arr.length > 0) return `${baseUrl}/uploads/products/${arr[0]}`;
  } catch {}
  if (typeof image === 'string' && image) return `${baseUrl}/uploads/products/${image}`;
  return 'https://placehold.co/300x300/2A2623/D4B896?text=No+Image';
};

const getCategories = (p: Product): string[] => {
  try {
    const c = typeof p.categories === 'string' ? JSON.parse(p.categories) : p.categories;
    return Array.isArray(c) ? c.map((x: any) => String(x).trim()).filter(Boolean) : [];
  } catch { return []; }
};

export default function CustomerHome() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [activeCategory, setActiveCategory] = useState('ALL');
  const [categories, setCategories] = useState<string[]>([]);
  const [showCatModal, setShowCatModal] = useState(false);
  const { socket } = useSocket();
  const { backendIp, backendPort } = useAuthStore();
  const baseUrl = `http://${backendIp}:${backendPort}`;

  const fetchProducts = useCallback(async () => {
    try {
      const res = await api.get('/products');
      const data: Product[] = res.data || [];
      setProducts(data);
      // Extract unique categories
      const cats = new Set<string>();
      data.forEach(p => getCategories(p).forEach(c => cats.add(c)));
      setCategories(Array.from(cats));
    } catch { setProducts([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchProducts(); }, [fetchProducts]);

  // Real-time updates
  useEffect(() => {
    if (!socket) return;
    const handleInventory = (data: any) => setProducts(prev => prev.map(p => p.id === data?.product?.id ? { ...p, ...data.product } : p));
    const handleReview = (data: any) => setProducts(prev => prev.map(p => String(p.id) === String(data?.productId) ? { ...p, rating: data.productRating, reviewCount: data.productReviewCount } : p));
    socket.on('inventory_updated', handleInventory);
    socket.on('review_updated', handleReview);
    socket.on('order_created', fetchProducts);
    return () => { socket.off('inventory_updated', handleInventory); socket.off('review_updated', handleReview); socket.off('order_created', fetchProducts); };
  }, [socket, fetchProducts]);

  const filtered = products.filter(p => {
    const cats = getCategories(p).map(c => c.toLowerCase());
    const matchesCat = activeCategory === 'ALL' || cats.includes(activeCategory.toLowerCase());
    const s = search.toLowerCase();
    const matchesSearch = !search || p.name?.toLowerCase().includes(s) || p.description?.toLowerCase().includes(s) || p.artisan?.toLowerCase().includes(s);
    return matchesCat && matchesSearch;
  });

  const renderProduct = ({ item }: { item: Product }) => (
    <TouchableOpacity style={styles.productCard} onPress={() => router.push({ pathname: '/(customer)/product/[id]' as any, params: { id: item.id } })} activeOpacity={0.85}>
      <Image source={{ uri: parseImages(item.image, baseUrl) }} style={styles.productImage} resizeMode="cover" />
      <View style={styles.productInfo}>
        <Text style={styles.productName} numberOfLines={2}>{item.name}</Text>
        <Text style={styles.productPrice}>₱{Number(item.price).toLocaleString()}</Text>
        <View style={styles.productMeta}>
          <Text style={styles.productRating}>⭐ {Number(item.rating || 0).toFixed(1)}</Text>
          <Text style={styles.productSeller} numberOfLines={1}>{item.artisan || 'Seller'}</Text>
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.logo}>LumbaRong</Text>
        <Text style={styles.tagline}>Authentic Philippine Barong</Text>
      </View>

      {/* Search + Filter Row */}
      <View style={styles.searchRow}>
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Search products..."
          placeholderTextColor={Colors.textMuted}
        />
        <TouchableOpacity style={styles.filterBtn} onPress={() => setShowCatModal(true)}>
          <Text style={styles.filterBtnText}>⚙ {activeCategory === 'ALL' ? 'All' : activeCategory}</Text>
        </TouchableOpacity>
      </View>

      {/* Products Grid */}
      {loading ? (
        <ActivityIndicator style={{ marginTop: 60 }} size="large" color={Colors.primary} />
      ) : (
        <FlatList
          data={filtered}
          keyExtractor={item => item.id}
          numColumns={2}
          columnWrapperStyle={styles.row}
          renderItem={renderProduct}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchProducts(); }} tintColor={Colors.primary} />}
          ListEmptyComponent={<Text style={styles.emptyText}>No products found.</Text>}
        />
      )}

      {/* Category Modal */}
      <Modal visible={showCatModal} transparent animationType="slide" onRequestClose={() => setShowCatModal(false)}>
        <TouchableOpacity style={styles.modalOverlay} onPress={() => setShowCatModal(false)}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>Browse Categories</Text>
            <ScrollView showsVerticalScrollIndicator={false}>
              {(['ALL', ...categories]).map(cat => (
                <TouchableOpacity key={cat} style={[styles.catItem, activeCategory === cat && styles.catItemActive]}
                  onPress={() => { setActiveCategory(cat); setShowCatModal(false); }}>
                  <Text style={[styles.catLabel, activeCategory === cat && styles.catLabelActive]}>
                    {cat === 'ALL' ? 'All Collections' : cat}
                  </Text>
                  {activeCategory === cat && <Text style={styles.catCheck}>●</Text>}
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </TouchableOpacity>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  header: { paddingHorizontal: Spacing.xl, paddingTop: Spacing.xl, paddingBottom: Spacing.md, alignItems: 'center' },
  logo: { fontSize: FontSize.xxl, fontWeight: FontWeight.heavy, fontStyle: 'italic', color: Colors.accent },
  tagline: { fontSize: FontSize.xs, color: Colors.textMuted, letterSpacing: 1.5, textTransform: 'uppercase', marginTop: 2 },
  searchRow: { flexDirection: 'row', gap: Spacing.sm, paddingHorizontal: Spacing.xl, marginBottom: Spacing.lg },
  searchInput: { flex: 1, backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, fontSize: FontSize.md, borderWidth: 1, borderColor: Colors.border },
  filterBtn: { backgroundColor: Colors.bgDark, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, justifyContent: 'center', borderWidth: 1, borderColor: Colors.border },
  filterBtnText: { color: Colors.accent, fontSize: FontSize.sm, fontWeight: FontWeight.semibold },
  list: { paddingHorizontal: Spacing.lg, paddingBottom: 100 },
  row: { justifyContent: 'space-between', marginBottom: Spacing.md },
  productCard: { width: '48%', backgroundColor: Colors.bgDark, borderRadius: Radius.lg, overflow: 'hidden', borderWidth: 1, borderColor: Colors.border, ...Shadow.sm },
  productImage: { width: '100%', height: 160, backgroundColor: Colors.bgMedium },
  productInfo: { padding: Spacing.md },
  productName: { fontSize: FontSize.sm, fontWeight: FontWeight.semibold, color: Colors.textPrimary, marginBottom: 4, lineHeight: 18 },
  productPrice: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.primary, marginBottom: 4 },
  productMeta: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  productRating: { fontSize: FontSize.xs, color: Colors.textSecondary },
  productSeller: { fontSize: FontSize.xs, color: Colors.textMuted, flex: 1, textAlign: 'right' },
  emptyText: { textAlign: 'center', color: Colors.textMuted, marginTop: 60, fontSize: FontSize.md },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalSheet: { backgroundColor: Colors.bgDark, borderTopLeftRadius: Radius.xl, borderTopRightRadius: Radius.xl, padding: Spacing.xxl, maxHeight: '70%' },
  modalTitle: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.xl },
  catItem: { paddingVertical: Spacing.lg, paddingHorizontal: Spacing.md, borderRadius: Radius.md, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  catItemActive: { backgroundColor: Colors.bgMedium },
  catLabel: { fontSize: FontSize.lg, color: Colors.textSecondary },
  catLabelActive: { color: Colors.primary, fontWeight: FontWeight.bold },
  catCheck: { color: Colors.primary, fontSize: FontSize.md },
});
