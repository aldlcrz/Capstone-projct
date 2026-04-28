/**
 * Cart Screen — View and manage cart items
 * Uses shared lib/cart.ts for persistence
 */
import React, { useState, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, FlatList,
  TouchableOpacity, Alert, Image,
} from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import { loadCart, saveCart, removeFromCart, updateCartQty, CartItem } from '@/lib/cart';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';
import { useAuthStore } from '@/store/authStore';

export default function CartScreen() {
  const [items, setItems] = useState<CartItem[]>([]);
  const { backendIp, backendPort } = useAuthStore();
  const baseUrl = `http://${backendIp}:${backendPort}`;

  useFocusEffect(
    useCallback(() => {
      loadCart().then(setItems);
    }, [])
  );

  const subtotal = items.reduce((s, c) => s + c.price * c.qty, 0);
  const shipping = items.reduce((max, c) => Math.max(max, c.shippingFee || 0), 0);
  const total = subtotal + shipping;

  const handleRemove = async (productId: string, size: string) => {
    const updated = await removeFromCart(productId, size);
    setItems(updated);
  };

  const handleQty = async (productId: string, size: string, delta: number, current: number) => {
    const newQty = current + delta;
    if (newQty < 1) {
      Alert.alert('Remove Item', 'Remove this item from cart?', [
        { text: 'Cancel' },
        { text: 'Remove', style: 'destructive', onPress: () => handleRemove(productId, size) },
      ]);
      return;
    }
    const updated = await updateCartQty(productId, size, newQty);
    setItems(updated);
  };

  const handleCheckout = () => {
    if (items.length === 0) return;
    router.push('/(customer)/checkout' as any);
  };

  const renderItem = ({ item }: { item: CartItem }) => (
    <View style={styles.card}>
      <Image
        source={{ uri: `${baseUrl}/uploads/products/${item.image}` }}
        style={styles.image}
        defaultSource={require('@/assets/images/icon.png')}
      />
      <View style={styles.info}>
        <Text style={styles.name} numberOfLines={2}>{item.productName}</Text>
        <Text style={styles.meta}>Size: {item.size} · {item.sellerName}</Text>
        <Text style={styles.price}>₱{(item.price * item.qty).toLocaleString()}</Text>

        <View style={styles.qtyRow}>
          <TouchableOpacity style={styles.qtyBtn} onPress={() => handleQty(item.productId, item.size, -1, item.qty)}>
            <Text style={styles.qtyBtnText}>−</Text>
          </TouchableOpacity>
          <Text style={styles.qty}>{item.qty}</Text>
          <TouchableOpacity style={styles.qtyBtn} onPress={() => handleQty(item.productId, item.size, 1, item.qty)}>
            <Text style={styles.qtyBtnText}>+</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.removeBtn} onPress={() => handleRemove(item.productId, item.size)}>
            <Text style={styles.removeBtnText}>🗑️</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.title}>My Cart</Text>

      <FlatList
        data={items}
        keyExtractor={item => `${item.productId}-${item.size}`}
        renderItem={renderItem}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={styles.emptyIcon}>🛒</Text>
            <Text style={styles.emptyText}>Your cart is empty</Text>
            <Button label="Browse Products" variant="primary" size="md" onPress={() => router.push('/(customer)/home' as any)} style={{ marginTop: Spacing.xl }} />
          </View>
        }
      />

      {items.length > 0 && (
        <View style={styles.footer}>
          <View style={styles.footerRow}>
            <Text style={styles.footerLabel}>Subtotal</Text>
            <Text style={styles.footerValue}>₱{subtotal.toLocaleString()}</Text>
          </View>
          <View style={styles.footerRow}>
            <Text style={styles.footerLabel}>Shipping</Text>
            <Text style={styles.footerValue}>{shipping > 0 ? `₱${shipping.toLocaleString()}` : 'FREE'}</Text>
          </View>
          <View style={[styles.footerRow, styles.totalRow]}>
            <Text style={styles.totalLabel}>Total</Text>
            <Text style={styles.totalValue}>₱{total.toLocaleString()}</Text>
          </View>
          <Button
            label={`Checkout (${items.length} item${items.length > 1 ? 's' : ''}) →`}
            variant="primary"
            size="lg"
            fullWidth
            onPress={handleCheckout}
          />
        </View>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  title: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, padding: Spacing.xl },
  list: { padding: Spacing.xl, paddingBottom: 220 },
  card: {
    flexDirection: 'row', backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.lg, marginBottom: Spacing.md, borderWidth: 1, borderColor: Colors.border, gap: Spacing.md,
  },
  image: { width: 90, height: 90, borderRadius: Radius.md, backgroundColor: Colors.bgMedium },
  info: { flex: 1 },
  name: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 4 },
  meta: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: 4 },
  price: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.primary, marginBottom: Spacing.sm },
  qtyRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm },
  qtyBtn: {
    width: 30, height: 30, borderRadius: 15, backgroundColor: Colors.bgMedium,
    justifyContent: 'center', alignItems: 'center', borderWidth: 1, borderColor: Colors.border,
  },
  qtyBtnText: { color: Colors.textPrimary, fontSize: FontSize.lg, fontWeight: FontWeight.bold },
  qty: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary, minWidth: 24, textAlign: 'center' },
  removeBtn: { marginLeft: Spacing.sm },
  removeBtnText: { fontSize: 18 },
  empty: { alignItems: 'center', paddingTop: 80 },
  emptyIcon: { fontSize: 56, marginBottom: Spacing.xl },
  emptyText: { fontSize: FontSize.lg, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  footer: {
    position: 'absolute', bottom: 0, left: 0, right: 0,
    backgroundColor: Colors.bgDark, padding: Spacing.xl,
    borderTopWidth: 1, borderTopColor: Colors.border,
  },
  footerRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: Spacing.xs },
  footerLabel: { fontSize: FontSize.md, color: Colors.textMuted },
  footerValue: { fontSize: FontSize.md, color: Colors.textSecondary, fontWeight: FontWeight.semibold },
  totalRow: { borderTopWidth: 1, borderTopColor: Colors.border, paddingTop: Spacing.sm, marginBottom: Spacing.lg },
  totalLabel: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  totalValue: { fontSize: FontSize.xl, fontWeight: FontWeight.heavy, color: Colors.primary },
});
