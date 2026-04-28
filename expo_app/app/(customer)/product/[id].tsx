/**
 * Product Detail Screen — Full clone of ProductDetailClient.jsx
 * - Image gallery (scrollable)
 * - Size picker with stock per size
 * - Add to cart / Buy Now
 * - Seller info
 * - Reviews section
 * - Report product/seller
 * Route: app/(customer)/product/[id].tsx
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, Image, Alert, ActivityIndicator, FlatList, TextInput, Modal,
} from 'react-native';
import { useLocalSearchParams, router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/store/authStore';
import { addToCart } from '@/app/(customer)/cart';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius, Shadow } from '@/constants/theme';

interface Size { size: string; stock: number; }
interface Review { id: string; rating: number; comment: string; createdAt: string; User?: { name: string }; }
interface Product {
  id: string; name: string; price: number; description: string;
  shippingFee?: number; image?: any;
  Seller?: { id: string; name: string; gcashNumber?: string; mayaNumber?: string; gcashQrCode?: string; mayaQrCode?: string; };
  ProductSizes?: Size[]; rating?: number; reviewCount?: number;
  allowGcash?: boolean; allowMaya?: boolean;
  categories?: any;
}

const REPORT_REASONS = [
  'Fake or counterfeit product',
  'Misleading description',
  'Inappropriate content',
  'Scam or fraud',
  'Prohibited item',
  'Other',
];

export default function ProductDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [product, setProduct] = useState<Product | null>(null);
  const [reviews, setReviews] = useState<Review[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedSize, setSelectedSize] = useState('');
  const [qty, setQty] = useState(1);
  const [activeImage, setActiveImage] = useState(0);
  const [addingToCart, setAddingToCart] = useState(false);
  const [showReport, setShowReport] = useState(false);
  const [reportReason, setReportReason] = useState('');
  const [reportLoading, setReportLoading] = useState(false);
  const { backendIp, backendPort } = useAuthStore();
  const baseUrl = `http://${backendIp}:${backendPort}`;

  const parseImages = (image: any): string[] => {
    try {
      const arr = typeof image === 'string' ? JSON.parse(image) : image;
      if (Array.isArray(arr) && arr.length > 0) return arr.map((i: string) => `${baseUrl}/uploads/products/${i}`);
    } catch {}
    if (typeof image === 'string' && image) return [`${baseUrl}/uploads/products/${image}`];
    return ['https://placehold.co/400x400/2A2623/D4B896?text=No+Image'];
  };

  const fetchProduct = useCallback(async () => {
    try {
      const res = await api.get(`/products/${id}`);
      setProduct(res.data);
      // Select first available size by default
      const sizes: Size[] = res.data.ProductSizes || [];
      const firstAvail = sizes.find(s => s.stock > 0);
      if (firstAvail) setSelectedSize(firstAvail.size);
    } catch { Alert.alert('Error', 'Failed to load product.'); }
    finally { setLoading(false); }
  }, [id]);

  const fetchReviews = useCallback(async () => {
    try {
      const res = await api.get(`/products/${id}/reviews`);
      setReviews(res.data || []);
    } catch {}
  }, [id]);

  useEffect(() => { fetchProduct(); fetchReviews(); }, [fetchProduct, fetchReviews]);

  const handleAddToCart = async () => {
    if (!product) return;
    if (!selectedSize) { Alert.alert('Select Size', 'Please select a size before adding to cart.'); return; }
    const sizeObj = product.ProductSizes?.find(s => s.size === selectedSize);
    if (!sizeObj || sizeObj.stock < qty) { Alert.alert('Out of Stock', 'Selected size is out of stock.'); return; }
    const images = parseImages(product.image);
    setAddingToCart(true);
    await addToCart({
      productId: product.id,
      productName: product.name,
      price: product.price,
      size: selectedSize,
      qty,
      sellerId: product.Seller?.id || '',
      sellerName: product.Seller?.name || 'Seller',
      image: images[0],
      shippingFee: product.shippingFee || 0,
      stock: sizeObj.stock,
    });
    setAddingToCart(false);
    Alert.alert('Added to Cart ✓', `${product.name} (${selectedSize}) added to your cart.`, [
      { text: 'Keep Shopping' },
      { text: 'View Cart', onPress: () => router.push('/(customer)/cart' as any) },
    ]);
  };

  const handleBuyNow = async () => {
    if (!product) return;
    if (!selectedSize) { Alert.alert('Select Size', 'Please select a size.'); return; }
    await handleAddToCart();
    router.push('/(customer)/checkout' as any);
  };

  const handleReport = async () => {
    if (!reportReason.trim()) { Alert.alert('Required', 'Please select or enter a report reason.'); return; }
    setReportLoading(true);
    try {
      await api.post('/reports', { productId: product?.id, reason: reportReason, type: 'product' });
      Alert.alert('Reported', 'Thank you for your report. We will review it shortly.');
      setShowReport(false);
      setReportReason('');
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to submit report.'));
    } finally { setReportLoading(false); }
  };

  if (loading) return <View style={styles.loading}><ActivityIndicator size="large" color={Colors.primary} /></View>;
  if (!product) return <View style={styles.loading}><Text style={styles.errorText}>Product not found.</Text></View>;

  const images = parseImages(product.image);
  const sizes: Size[] = product.ProductSizes || [];
  const selectedSizeObj = sizes.find(s => s.size === selectedSize);
  const maxQty = selectedSizeObj?.stock || 0;

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView showsVerticalScrollIndicator={false}>
        {/* Back button */}
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>

        {/* Image Gallery */}
        <View>
          <Image source={{ uri: images[activeImage] }} style={styles.mainImage} resizeMode="cover" />
          {images.length > 1 && (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.thumbRow}>
              {images.map((uri, i) => (
                <TouchableOpacity key={i} onPress={() => setActiveImage(i)}>
                  <Image source={{ uri }} style={[styles.thumb, activeImage === i && styles.thumbActive]} resizeMode="cover" />
                </TouchableOpacity>
              ))}
            </ScrollView>
          )}
        </View>

        <View style={styles.body}>
          {/* Name & Price */}
          <Text style={styles.productName}>{product.name}</Text>
          <View style={styles.priceRow}>
            <Text style={styles.price}>₱{Number(product.price).toLocaleString()}</Text>
            {product.rating !== undefined && (
              <Text style={styles.rating}>⭐ {Number(product.rating).toFixed(1)} ({product.reviewCount || 0})</Text>
            )}
          </View>

          {/* Seller */}
          {product.Seller && (
            <View style={styles.sellerRow}>
              <Text style={styles.sellerLabel}>Seller:</Text>
              <Text style={styles.sellerName}>{product.Seller.name}</Text>
            </View>
          )}

          {/* Description */}
          <Text style={styles.sectionTitle}>Description</Text>
          <Text style={styles.description}>{product.description || 'No description provided.'}</Text>

          {/* Size Picker */}
          {sizes.length > 0 && (
            <>
              <Text style={styles.sectionTitle}>Select Size</Text>
              <View style={styles.sizeRow}>
                {sizes.map(s => (
                  <TouchableOpacity
                    key={s.size}
                    style={[styles.sizeBtn, selectedSize === s.size && styles.sizeBtnActive, s.stock === 0 && styles.sizeBtnDisabled]}
                    onPress={() => { if (s.stock > 0) { setSelectedSize(s.size); setQty(1); } }}
                    disabled={s.stock === 0}
                  >
                    <Text style={[styles.sizeBtnText, selectedSize === s.size && styles.sizeBtnTextActive]}>
                      {s.size}
                    </Text>
                    {s.stock === 0 && <Text style={styles.outOfStock}>Out</Text>}
                    {s.stock > 0 && s.stock < 5 && <Text style={styles.lowStock}>Low</Text>}
                  </TouchableOpacity>
                ))}
              </View>
            </>
          )}

          {/* Quantity */}
          {selectedSize && maxQty > 0 && (
            <>
              <Text style={styles.sectionTitle}>Quantity</Text>
              <View style={styles.qtyRow}>
                <TouchableOpacity style={styles.qtyBtn} onPress={() => setQty(q => Math.max(1, q - 1))}>
                  <Text style={styles.qtyBtnText}>−</Text>
                </TouchableOpacity>
                <Text style={styles.qtyNum}>{qty}</Text>
                <TouchableOpacity style={styles.qtyBtn} onPress={() => setQty(q => Math.min(maxQty, q + 1))}>
                  <Text style={styles.qtyBtnText}>+</Text>
                </TouchableOpacity>
                <Text style={styles.stockHint}>({maxQty} available)</Text>
              </View>
            </>
          )}

          {/* Shipping fee */}
          {!!product.shippingFee && (
            <View style={styles.shippingRow}>
              <Text style={styles.shippingLabel}>Shipping:</Text>
              <Text style={styles.shippingValue}>₱{Number(product.shippingFee).toLocaleString()}</Text>
            </View>
          )}

          {/* CTA buttons */}
          <View style={styles.ctaRow}>
            <Button label="🛒 Add to Cart" variant="outline" size="lg" style={{ flex: 1 }} loading={addingToCart} onPress={handleAddToCart} />
            <Button label="Buy Now →" variant="primary" size="lg" style={{ flex: 1 }} onPress={handleBuyNow} />
          </View>

          {/* Report */}
          <TouchableOpacity style={styles.reportBtn} onPress={() => setShowReport(true)}>
            <Text style={styles.reportBtnText}>⚠️ Report this product</Text>
          </TouchableOpacity>

          {/* Reviews */}
          <Text style={styles.sectionTitle}>Reviews ({reviews.length})</Text>
          {reviews.length === 0 ? (
            <Text style={styles.noReviews}>No reviews yet. Be the first!</Text>
          ) : (
            reviews.slice(0, 5).map(r => (
              <View key={r.id} style={styles.reviewCard}>
                <View style={styles.reviewHeader}>
                  <Text style={styles.reviewName}>{r.User?.name || 'Customer'}</Text>
                  <Text style={styles.reviewStars}>{'⭐'.repeat(r.rating)}</Text>
                </View>
                <Text style={styles.reviewComment}>{r.comment}</Text>
                <Text style={styles.reviewDate}>{new Date(r.createdAt).toLocaleDateString('en-PH')}</Text>
              </View>
            ))
          )}
        </View>
      </ScrollView>

      {/* Report Modal */}
      <Modal visible={showReport} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>Report Product</Text>
            {REPORT_REASONS.map(reason => (
              <TouchableOpacity key={reason} style={[styles.reasonItem, reportReason === reason && styles.reasonItemActive]} onPress={() => setReportReason(reason)}>
                <Text style={[styles.reasonText, reportReason === reason && styles.reasonTextActive]}>{reason}</Text>
              </TouchableOpacity>
            ))}
            <TextInput style={styles.reasonInput} value={reportReason} onChangeText={setReportReason} placeholder="Or describe the issue..." placeholderTextColor={Colors.textMuted} multiline numberOfLines={3} />
            <View style={styles.modalBtns}>
              <Button label="Cancel" variant="outline" size="md" style={{ flex: 1 }} onPress={() => { setShowReport(false); setReportReason(''); }} />
              <Button label="Submit Report" variant="danger" size="md" style={{ flex: 1 }} loading={reportLoading} onPress={handleReport} />
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  loading: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.bgDeep },
  errorText: { color: Colors.error, fontSize: FontSize.md },
  backBtn: { position: 'absolute', top: 16, left: 16, zIndex: 10, backgroundColor: 'rgba(0,0,0,0.5)', borderRadius: Radius.full, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs },
  backText: { color: Colors.white, fontSize: FontSize.sm, fontWeight: FontWeight.bold },
  mainImage: { width: '100%', height: 320, backgroundColor: Colors.bgMedium },
  thumbRow: { padding: Spacing.md, gap: Spacing.sm },
  thumb: { width: 64, height: 64, borderRadius: Radius.sm, borderWidth: 2, borderColor: Colors.border },
  thumbActive: { borderColor: Colors.primary },
  body: { padding: Spacing.xl },
  productName: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.sm, lineHeight: 30 },
  priceRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: Spacing.lg },
  price: { fontSize: 28, fontWeight: FontWeight.heavy, color: Colors.primary },
  rating: { fontSize: FontSize.sm, color: Colors.textSecondary },
  sellerRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm, marginBottom: Spacing.xl, backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: Spacing.md },
  sellerLabel: { fontSize: FontSize.sm, color: Colors.textMuted },
  sellerName: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.accent },
  sectionTitle: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginTop: Spacing.xl, marginBottom: Spacing.md },
  description: { fontSize: FontSize.md, color: Colors.textSecondary, lineHeight: 22 },
  sizeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm },
  sizeBtn: { paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.border, backgroundColor: Colors.bgDark, position: 'relative' },
  sizeBtnActive: { borderColor: Colors.primary, backgroundColor: `${Colors.primary}22` },
  sizeBtnDisabled: { opacity: 0.4 },
  sizeBtnText: { fontSize: FontSize.md, color: Colors.textSecondary, fontWeight: FontWeight.semibold },
  sizeBtnTextActive: { color: Colors.primary, fontWeight: FontWeight.bold },
  outOfStock: { position: 'absolute', top: 2, right: 4, fontSize: 8, color: Colors.error, fontWeight: FontWeight.bold },
  lowStock: { position: 'absolute', top: 2, right: 4, fontSize: 8, color: Colors.warning, fontWeight: FontWeight.bold },
  qtyRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.md },
  qtyBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: Colors.bgMedium, justifyContent: 'center', alignItems: 'center', borderWidth: 1, borderColor: Colors.border },
  qtyBtnText: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  qtyNum: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, minWidth: 32, textAlign: 'center' },
  stockHint: { fontSize: FontSize.xs, color: Colors.textMuted },
  shippingRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm, marginTop: Spacing.lg },
  shippingLabel: { fontSize: FontSize.sm, color: Colors.textMuted },
  shippingValue: { fontSize: FontSize.sm, fontWeight: FontWeight.bold, color: Colors.textSecondary },
  ctaRow: { flexDirection: 'row', gap: Spacing.md, marginTop: Spacing.xxl, marginBottom: Spacing.lg },
  reportBtn: { alignItems: 'center', marginBottom: Spacing.xxxl },
  reportBtnText: { fontSize: FontSize.sm, color: Colors.textMuted },
  noReviews: { fontSize: FontSize.md, color: Colors.textMuted, fontStyle: 'italic' },
  reviewCard: { backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: Spacing.lg, marginBottom: Spacing.md, borderWidth: 1, borderColor: Colors.border },
  reviewHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: Spacing.xs },
  reviewName: { fontSize: FontSize.sm, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  reviewStars: { fontSize: FontSize.sm },
  reviewComment: { fontSize: FontSize.sm, color: Colors.textSecondary, lineHeight: 18, marginBottom: 4 },
  reviewDate: { fontSize: FontSize.xs, color: Colors.textMuted },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalSheet: { backgroundColor: Colors.bgDark, borderTopLeftRadius: Radius.xl, borderTopRightRadius: Radius.xl, padding: Spacing.xxl },
  modalTitle: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.lg },
  reasonItem: { paddingVertical: Spacing.md, borderBottomWidth: 1, borderBottomColor: Colors.border },
  reasonItemActive: { backgroundColor: Colors.bgMedium, borderRadius: Radius.sm, paddingHorizontal: Spacing.md },
  reasonText: { fontSize: FontSize.md, color: Colors.textSecondary },
  reasonTextActive: { color: Colors.primary, fontWeight: FontWeight.bold },
  reasonInput: { backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.md, padding: Spacing.lg, fontSize: FontSize.md, minHeight: 70, textAlignVertical: 'top', borderWidth: 1, borderColor: Colors.border, marginTop: Spacing.md, marginBottom: Spacing.xl },
  modalBtns: { flexDirection: 'row', gap: Spacing.md },
});
