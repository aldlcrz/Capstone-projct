/**
 * lib/cart.ts — Shared cart utilities
 * Used by both cart.tsx screen and checkout.tsx
 */
import AsyncStorage from '@react-native-async-storage/async-storage';

export interface CartItem {
  productId: string;
  productName: string;
  image: string;
  size: string;
  qty: number;
  price: number;
  shippingFee: number;
  sellerId: string;
  sellerName: string;
}

const CART_KEY = 'lumbarong_cart';

export async function loadCart(): Promise<CartItem[]> {
  try {
    const raw = await AsyncStorage.getItem(CART_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

export async function saveCart(items: CartItem[]): Promise<void> {
  try {
    await AsyncStorage.setItem(CART_KEY, JSON.stringify(items));
  } catch {}
}

export async function addToCart(item: CartItem): Promise<CartItem[]> {
  const cart = await loadCart();
  const idx = cart.findIndex(c => c.productId === item.productId && c.size === item.size);
  if (idx >= 0) {
    cart[idx].qty += item.qty;
  } else {
    cart.push(item);
  }
  await saveCart(cart);
  return cart;
}

export async function removeFromCart(productId: string, size: string): Promise<CartItem[]> {
  const cart = await loadCart();
  const updated = cart.filter(c => !(c.productId === productId && c.size === size));
  await saveCart(updated);
  return updated;
}

export async function updateCartQty(productId: string, size: string, qty: number): Promise<CartItem[]> {
  const cart = await loadCart();
  const updated = cart.map(c =>
    c.productId === productId && c.size === size ? { ...c, qty: Math.max(1, qty) } : c
  );
  await saveCart(updated);
  return updated;
}

export async function clearCart(): Promise<void> {
  await AsyncStorage.removeItem(CART_KEY);
}
