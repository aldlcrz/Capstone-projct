/**
 * Checkout Screen — Full clone of checkout/page.js
 * Step 1: Shipping Address (address book or manual entry)
 * Step 2: Payment (GCash / Maya: QR code, reference number, payment proof image)
 * Step 3: Order Confirmed
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity,
  TextInput, Image, Alert, ActivityIndicator, KeyboardAvoidingView, Platform, Modal,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/store/authStore';
import { useSocket } from '@/lib/socket';
import { loadCart, saveCart, CartItem } from '@/lib/cart';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

type PaymentMethod = 'GCash' | 'Maya';

interface Address {
  id?: string; recipientName: string; phone: string; houseNo: string;
  street: string; barangay: string; city: string; province: string;
  postalCode: string; isDefault?: boolean;
}

const EMPTY_ADDR: Address = { recipientName: '', phone: '', houseNo: '', street: '', barangay: '', city: '', province: '', postalCode: '' };

const STEPS = ['Shipping', 'Payment', 'Confirmed'];

function StepIndicator({ current }: { current: number }) {
  return (
    <View style={styles.stepRow}>
      {STEPS.map((label, i) => {
        const idx = i + 1;
        const done = current > idx;
        const active = current === idx;
        return (
          <React.Fragment key={label}>
            <View style={styles.stepItem}>
              <View style={[styles.stepCircle, active && styles.stepCircleActive, done && styles.stepCircleDone]}>
                <Text style={[styles.stepNum, (active || done) && styles.stepNumActive]}>{done ? '✓' : idx}</Text>
              </View>
              <Text style={[styles.stepLabel, (active || done) && styles.stepLabelActive]}>{label}</Text>
            </View>
            {i < STEPS.length - 1 && <View style={[styles.stepLine, current > idx && styles.stepLineDone]} />}
          </React.Fragment>
        );
      })}
    </View>
  );
}

export default function CheckoutScreen() {
  const [step, setStep] = useState(1);
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [selectedAddr, setSelectedAddr] = useState<Address | null>(null);
  const [manualAddr, setManualAddr] = useState<Address>({ ...EMPTY_ADDR });
  const [useManual, setUseManual] = useState(false);
  const [showAddrPicker, setShowAddrPicker] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('GCash');
  const [reference, setReference] = useState('');
  const [proofImage, setProofImage] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [loading, setLoading] = useState(false);
  const [confirmedOrder, setConfirmedOrder] = useState<any>(null);
  const { backendIp, backendPort } = useAuthStore();
  const { socket } = useSocket();
  const baseUrl = `http://${backendIp}:${backendPort}`;

  // Seller payment info (enriched from first item's product)
  const [sellerPayment, setSellerPayment] = useState<any>({});

  const fetchAddresses = useCallback(async () => {
    try {
      const res = await api.get('/addresses');
      setAddresses(res.data || []);
      const def = (res.data || []).find((a: Address) => a.isDefault) || (res.data || [])[0];
      if (def) { setSelectedAddr(def); setUseManual(false); }
      else setUseManual(true);
    } catch {}
  }, []);

  useEffect(() => {
    loadCart().then(items => {
      if (!items.length) { router.replace('/(customer)/cart' as any); return; }
      setCartItems(items);
      // Enrich payment info from product
      const productId = items[0]?.productId;
      if (productId) {
        api.get(`/products/${productId}`).then(r => setSellerPayment(r.data || {})).catch(() => {});
      }
    });
    fetchAddresses();
  }, [fetchAddresses]);

  const subtotal = cartItems.reduce((s, c) => s + c.price * c.qty, 0);
  const shipping = cartItems.reduce((max, c) => Math.max(max, c.shippingFee || 0), 0);
  const total = subtotal + shipping;

  const isGcash = paymentMethod === 'GCash';
  const sellerNumber = isGcash ? (sellerPayment.gcashNumber || '—') : (sellerPayment.mayaNumber || '—');
  const sellerName = sellerPayment.Seller?.name || sellerPayment.artisan || 'Seller';
  const qrCode = isGcash ? sellerPayment.gcashQrCode : sellerPayment.mayaQrCode;
  const qrUrl = qrCode ? `${baseUrl}/uploads/${qrCode}` : null;

  const shippingAddr = useManual ? manualAddr : (selectedAddr || manualAddr);

  const validateStep1 = (): boolean => {
    const a = shippingAddr;
    if (!a.recipientName.trim() || !a.phone.trim() || !a.street.trim() || !a.city.trim() || !a.province.trim()) {
      Alert.alert('Incomplete Address', 'Please fill in all required shipping fields.'); return false;
    }
    return true;
  };

  const validateStep2 = (): boolean => {
    if (!reference.trim() || reference.trim().length < 5) {
      Alert.alert('Required', `Please enter a valid ${paymentMethod} reference number.`); return false;
    }
    if (!proofImage) {
      Alert.alert('Required', 'Please upload your payment screenshot.'); return false;
    }
    return true;
  };

  const pickProof = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') { Alert.alert('Permission needed', 'Allow access to your media library.'); return; }
    const res = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ImagePicker.MediaTypeOptions.Images, quality: 0.8 });
    if (!res.canceled && res.assets[0]) {
      const a = res.assets[0];
      setProofImage({ uri: a.uri, name: a.uri.split('/').pop() || 'proof.jpg', type: a.mimeType || 'image/jpeg' });
    }
  };

  const placeOrder = async () => {
    setLoading(true);
    try {
      const formData = new FormData();
      formData.append('items', JSON.stringify(cartItems.map(c => ({ productId: c.productId, size: c.size, quantity: c.qty, price: c.price }))));
      formData.append('shippingAddress', JSON.stringify(shippingAddr));
      formData.append('paymentMethod', paymentMethod);
      formData.append('paymentReference', reference.trim());
      if (proofImage) {
        formData.append('paymentProof', { uri: proofImage.uri, name: proofImage.name, type: proofImage.type } as any);
      }
      const res = await api.post('/orders', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
      setConfirmedOrder(res.data);
      await saveCart([]);
      setStep(3);
    } catch (err: any) {
      Alert.alert('Order Failed', getApiErrorMessage(err, 'Failed to place order. Please try again.'));
    } finally { setLoading(false); }
  };

  const handleNext = () => {
    if (step === 1) { if (validateStep1()) setStep(2); }
    else if (step === 2) { if (validateStep2()) placeOrder(); }
  };

  // ── Render steps ───────────────────────────────────
  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>
          <TouchableOpacity onPress={() => router.back()}>
            <Text style={styles.pageTitle}>Checkout</Text>
          </TouchableOpacity>

          <StepIndicator current={step} />

          {/* ── STEP 1: Shipping ───────────────── */}
          {step === 1 && (
            <View>
              <Text style={styles.sectionTitle}>Shipping Address</Text>

              {/* Address Book */}
              {addresses.length > 0 && (
                <>
                  <TouchableOpacity style={styles.addrBookBtn} onPress={() => setShowAddrPicker(true)}>
                    <Text style={styles.addrBookBtnText}>📖 Choose from Address Book</Text>
                  </TouchableOpacity>
                  {selectedAddr && !useManual && (
                    <View style={styles.selectedAddr}>
                      <Text style={styles.addrName}>{selectedAddr.recipientName}</Text>
                      <Text style={styles.addrLine}>{selectedAddr.phone}</Text>
                      <Text style={styles.addrLine}>{selectedAddr.houseNo} {selectedAddr.street}, {selectedAddr.barangay}</Text>
                      <Text style={styles.addrLine}>{selectedAddr.city}, {selectedAddr.province} {selectedAddr.postalCode}</Text>
                      <TouchableOpacity onPress={() => setUseManual(true)}>
                        <Text style={styles.changeTxt}>Use different address</Text>
                      </TouchableOpacity>
                    </View>
                  )}
                </>
              )}

              {(useManual || addresses.length === 0) && (
                <View>
                  <Input label="Recipient Name *" value={manualAddr.recipientName} onChangeText={v => setManualAddr(a => ({ ...a, recipientName: v }))} placeholder="Full name" />
                  <Input label="Phone Number *" value={manualAddr.phone} onChangeText={v => setManualAddr(a => ({ ...a, phone: v }))} placeholder="09XXXXXXXXX" keyboardType="numeric" />
                  <View style={styles.twoCol}>
                    <View style={{ flex: 1 }}><Input label="House No." value={manualAddr.houseNo} onChangeText={v => setManualAddr(a => ({ ...a, houseNo: v }))} placeholder="Bldg / House #" /></View>
                    <View style={{ width: Spacing.md }} />
                    <View style={{ flex: 1 }}><Input label="Street *" value={manualAddr.street} onChangeText={v => setManualAddr(a => ({ ...a, street: v }))} placeholder="Street name" /></View>
                  </View>
                  <Input label="Barangay" value={manualAddr.barangay} onChangeText={v => setManualAddr(a => ({ ...a, barangay: v }))} placeholder="Barangay" />
                  <View style={styles.twoCol}>
                    <View style={{ flex: 1 }}><Input label="City *" value={manualAddr.city} onChangeText={v => setManualAddr(a => ({ ...a, city: v }))} placeholder="City / Municipality" /></View>
                    <View style={{ width: Spacing.md }} />
                    <View style={{ flex: 1 }}><Input label="Province *" value={manualAddr.province} onChangeText={v => setManualAddr(a => ({ ...a, province: v }))} placeholder="Province" /></View>
                  </View>
                  <Input label="Postal Code" value={manualAddr.postalCode} onChangeText={v => setManualAddr(a => ({ ...a, postalCode: v }))} placeholder="ZIP" keyboardType="numeric" />
                </View>
              )}

              {/* Order Summary */}
              <View style={styles.orderSummary}>
                <Text style={styles.sectionTitle}>Order Summary</Text>
                {cartItems.map((item, i) => (
                  <View key={i} style={styles.summaryItem}>
                    <Text style={styles.summaryItemName} numberOfLines={1}>{item.productName}</Text>
                    <Text style={styles.summaryItemMeta}>{item.size} × {item.qty}</Text>
                    <Text style={styles.summaryItemPrice}>₱{(item.price * item.qty).toLocaleString()}</Text>
                  </View>
                ))}
                <View style={styles.summaryTotals}>
                  <View style={styles.summaryRow}><Text style={styles.summaryLabel}>Subtotal</Text><Text style={styles.summaryValue}>₱{subtotal.toLocaleString()}</Text></View>
                  <View style={styles.summaryRow}><Text style={styles.summaryLabel}>Shipping</Text><Text style={styles.summaryValue}>{shipping > 0 ? `₱${shipping.toLocaleString()}` : 'FREE'}</Text></View>
                  <View style={[styles.summaryRow, styles.totalRow]}>
                    <Text style={styles.totalLabel}>Total</Text>
                    <Text style={styles.totalValue}>₱{total.toLocaleString()}</Text>
                  </View>
                </View>
              </View>

              <Button label="Continue to Payment →" variant="primary" size="lg" fullWidth onPress={handleNext} style={{ marginTop: Spacing.xl }} />
            </View>
          )}

          {/* ── STEP 2: Payment ────────────────── */}
          {step === 2 && (
            <View>
              <Text style={styles.sectionTitle}>Payment Method</Text>
              <View style={styles.paymentRow}>
                {(['GCash', 'Maya'] as PaymentMethod[]).map(m => (
                  <TouchableOpacity key={m} style={[styles.paymentCard, paymentMethod === m && styles.paymentCardActive]} onPress={() => setPaymentMethod(m)}>
                    <Text style={styles.paymentIcon}>{m === 'GCash' ? '💙' : '💚'}</Text>
                    <Text style={[styles.paymentLabel, paymentMethod === m && styles.paymentLabelActive]}>{m}</Text>
                    {paymentMethod === m && <View style={styles.paymentCheck}><Text style={{ color: Colors.white, fontSize: 10, fontWeight: '700' }}>✓</Text></View>}
                  </TouchableOpacity>
                ))}
              </View>

              {/* QR + Number */}
              <View style={styles.qrCard}>
                {qrUrl ? (
                  <Image source={{ uri: qrUrl }} style={styles.qrImage} resizeMode="contain" />
                ) : (
                  <View style={styles.qrPlaceholder}><Text style={styles.qrPlaceholderText}>QR</Text></View>
                )}
                <View style={styles.qrInfo}>
                  <Text style={styles.qrLabel}>{paymentMethod} Number</Text>
                  <Text style={styles.qrNumber}>{sellerNumber}</Text>
                  <Text style={styles.qrSeller}>Recipient: {sellerName}</Text>
                  <Text style={styles.qrAmount}>Due: <Text style={{ color: Colors.primary, fontWeight: FontWeight.bold }}>₱{total.toLocaleString()}</Text></Text>
                </View>
              </View>

              <Input
                label={`${paymentMethod} Reference Number *`}
                value={reference}
                onChangeText={setReference}
                placeholder="Enter 13-digit reference..."
                keyboardType="numeric"
              />

              <Text style={styles.uploadLabel}>Payment Screenshot *</Text>
              <TouchableOpacity style={[styles.uploadBtn, proofImage && styles.uploadBtnDone]} onPress={pickProof}>
                <Text style={styles.uploadBtnText}>{proofImage ? `✓ ${proofImage.name}` : '↑ Upload Payment Proof'}</Text>
              </TouchableOpacity>

              <View style={styles.stepBtns}>
                <Button label="Back" variant="outline" size="md" style={{ flex: 1 }} onPress={() => setStep(1)} />
                <Button label="Place Order →" variant="primary" size="md" style={{ flex: 2 }} loading={loading} onPress={handleNext} />
              </View>
            </View>
          )}

          {/* ── STEP 3: Confirmed ──────────────── */}
          {step === 3 && (
            <View style={styles.successBox}>
              <Text style={styles.successIcon}>🎉</Text>
              <Text style={styles.successTitle}>Order Confirmed!</Text>
              <Text style={styles.successMsg}>Your order has been placed successfully. The seller will process it shortly.</Text>
              {confirmedOrder?.id && (
                <View style={styles.orderRefBox}>
                  <Text style={styles.orderRefLabel}>Order Reference</Text>
                  <Text style={styles.orderRefId}>#{confirmedOrder.id.slice(0, 8).toUpperCase()}</Text>
                </View>
              )}
              <Button label="Track My Orders →" variant="primary" size="lg" fullWidth onPress={() => router.replace('/(customer)/orders' as any)} style={{ marginTop: Spacing.xxl }} />
              <Button label="Continue Shopping" variant="outline" size="lg" fullWidth onPress={() => router.replace('/(customer)/home' as any)} style={{ marginTop: Spacing.md }} />
            </View>
          )}
        </ScrollView>
      </KeyboardAvoidingView>

      {/* Address Book Modal */}
      <Modal visible={showAddrPicker} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>Choose Address</Text>
            <ScrollView>
              {addresses.map(addr => (
                <TouchableOpacity key={addr.id} style={styles.addrOption} onPress={() => { setSelectedAddr(addr); setUseManual(false); setShowAddrPicker(false); }}>
                  <Text style={styles.addrOptName}>{addr.recipientName}</Text>
                  <Text style={styles.addrOptLine}>{addr.houseNo} {addr.street}, {addr.barangay}</Text>
                  <Text style={styles.addrOptLine}>{addr.city}, {addr.province} {addr.postalCode}</Text>
                  {addr.isDefault && <Text style={styles.defaultTag}>Default</Text>}
                </TouchableOpacity>
              ))}
              <TouchableOpacity style={styles.newAddrBtn} onPress={() => { setUseManual(true); setShowAddrPicker(false); }}>
                <Text style={styles.newAddrBtnText}>+ Use New Address</Text>
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { padding: Spacing.xl, paddingBottom: 100 },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.xl },
  stepRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', marginBottom: Spacing.xxl, gap: 0 },
  stepItem: { alignItems: 'center', gap: Spacing.xs },
  stepCircle: { width: 32, height: 32, borderRadius: 16, backgroundColor: Colors.bgMedium, justifyContent: 'center', alignItems: 'center' },
  stepCircleActive: { backgroundColor: Colors.primary },
  stepCircleDone: { backgroundColor: Colors.success },
  stepNum: { color: Colors.textMuted, fontSize: FontSize.sm, fontWeight: FontWeight.bold },
  stepNumActive: { color: Colors.white },
  stepLabel: { fontSize: FontSize.xs, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  stepLabelActive: { color: Colors.primary },
  stepLine: { width: 40, height: 2, backgroundColor: Colors.border, marginHorizontal: Spacing.xs, marginBottom: Spacing.lg },
  stepLineDone: { backgroundColor: Colors.success },
  sectionTitle: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.md, marginTop: Spacing.xl },
  addrBookBtn: { backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: Spacing.lg, alignItems: 'center', borderWidth: 1, borderColor: Colors.border, marginBottom: Spacing.md },
  addrBookBtnText: { color: Colors.accent, fontWeight: FontWeight.semibold, fontSize: FontSize.md },
  selectedAddr: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, borderWidth: 1.5, borderColor: Colors.primary, marginBottom: Spacing.xl },
  addrName: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  addrLine: { fontSize: FontSize.sm, color: Colors.textSecondary, marginBottom: 1 },
  changeTxt: { color: Colors.primary, fontSize: FontSize.sm, fontWeight: FontWeight.bold, marginTop: Spacing.sm },
  twoCol: { flexDirection: 'row' },
  orderSummary: { marginTop: Spacing.xl },
  summaryItem: { flexDirection: 'row', alignItems: 'center', marginBottom: Spacing.sm },
  summaryItemName: { flex: 1, fontSize: FontSize.sm, color: Colors.textSecondary },
  summaryItemMeta: { fontSize: FontSize.xs, color: Colors.textMuted, marginHorizontal: Spacing.sm },
  summaryItemPrice: { fontSize: FontSize.sm, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  summaryTotals: { borderTopWidth: 1, borderTopColor: Colors.border, paddingTop: Spacing.lg, marginTop: Spacing.md },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: Spacing.sm },
  summaryLabel: { fontSize: FontSize.md, color: Colors.textSecondary },
  summaryValue: { fontSize: FontSize.md, color: Colors.textPrimary, fontWeight: FontWeight.semibold },
  totalRow: { borderTopWidth: 1, borderTopColor: Colors.border, paddingTop: Spacing.sm, marginTop: Spacing.xs },
  totalLabel: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  totalValue: { fontSize: FontSize.xl, fontWeight: FontWeight.heavy, color: Colors.primary },
  paymentRow: { flexDirection: 'row', gap: Spacing.md, marginBottom: Spacing.xl },
  paymentCard: { flex: 1, backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border, position: 'relative' },
  paymentCardActive: { borderColor: Colors.primary, backgroundColor: `${Colors.primary}11` },
  paymentIcon: { fontSize: 28, marginBottom: Spacing.sm },
  paymentLabel: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textMuted },
  paymentLabelActive: { color: Colors.primary },
  paymentCheck: { position: 'absolute', top: 8, right: 8, backgroundColor: Colors.primary, borderRadius: 10, width: 18, height: 18, justifyContent: 'center', alignItems: 'center' },
  qrCard: { flexDirection: 'row', backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, marginBottom: Spacing.xl, borderWidth: 1, borderColor: Colors.border, gap: Spacing.lg },
  qrImage: { width: 100, height: 100, borderRadius: Radius.md, backgroundColor: Colors.white },
  qrPlaceholder: { width: 100, height: 100, borderRadius: Radius.md, backgroundColor: Colors.bgMedium, justifyContent: 'center', alignItems: 'center' },
  qrPlaceholderText: { color: Colors.textMuted, fontSize: FontSize.md, fontWeight: FontWeight.bold },
  qrInfo: { flex: 1 },
  qrLabel: { fontSize: FontSize.xs, color: Colors.textMuted, fontWeight: FontWeight.bold, textTransform: 'uppercase', letterSpacing: 1, marginBottom: 4 },
  qrNumber: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  qrSeller: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: 4 },
  qrAmount: { fontSize: FontSize.md, color: Colors.textSecondary },
  uploadLabel: { fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.bold, textTransform: 'uppercase', letterSpacing: 1, marginBottom: Spacing.sm, marginTop: Spacing.md },
  uploadBtn: { borderWidth: 2, borderStyle: 'dashed', borderColor: Colors.border, borderRadius: Radius.md, padding: Spacing.xl, alignItems: 'center', marginBottom: Spacing.xxl },
  uploadBtnDone: { borderColor: Colors.success, backgroundColor: Colors.successLight },
  uploadBtnText: { color: Colors.textSecondary, fontWeight: FontWeight.semibold, fontSize: FontSize.md },
  stepBtns: { flexDirection: 'row', gap: Spacing.md },
  successBox: { alignItems: 'center', paddingVertical: Spacing.xxxl },
  successIcon: { fontSize: 64, marginBottom: Spacing.xl },
  successTitle: { fontSize: 28, fontWeight: FontWeight.heavy, color: Colors.accent, marginBottom: Spacing.lg },
  successMsg: { fontSize: FontSize.md, color: Colors.textSecondary, textAlign: 'center', lineHeight: 22, marginBottom: Spacing.xl },
  orderRefBox: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.xl, alignItems: 'center', borderWidth: 1, borderColor: Colors.border, width: '100%' },
  orderRefLabel: { fontSize: FontSize.xs, color: Colors.textMuted, fontWeight: FontWeight.bold, letterSpacing: 1.5, marginBottom: 4 },
  orderRefId: { fontSize: FontSize.xxl, fontWeight: FontWeight.heavy, color: Colors.primary, letterSpacing: 3 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'flex-end' },
  modalSheet: { backgroundColor: Colors.bgDark, borderTopLeftRadius: Radius.xl, borderTopRightRadius: Radius.xl, padding: Spacing.xxl, maxHeight: '70%' },
  modalTitle: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.xl },
  addrOption: { backgroundColor: Colors.bgMedium, borderRadius: Radius.md, padding: Spacing.lg, marginBottom: Spacing.md },
  addrOptName: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  addrOptLine: { fontSize: FontSize.sm, color: Colors.textSecondary, marginBottom: 1 },
  defaultTag: { fontSize: FontSize.xs, color: Colors.primary, fontWeight: FontWeight.bold, marginTop: 4 },
  newAddrBtn: { alignItems: 'center', padding: Spacing.lg },
  newAddrBtnText: { color: Colors.primary, fontWeight: FontWeight.bold, fontSize: FontSize.md },
});
