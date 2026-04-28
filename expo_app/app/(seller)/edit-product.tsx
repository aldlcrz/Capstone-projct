/**
 * Edit Product Screen — loads existing product data then patches it
 * Route: app/(seller)/edit-product.tsx?id=[productId]
 */
import React, { useState, useEffect } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity,
  Image, Alert, ActivityIndicator, KeyboardAvoidingView, Platform, Switch,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { useLocalSearchParams, router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/store/authStore';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

const PRESET_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
interface SizeEntry { size: string; stock: number; }

export default function EditProductScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { backendIp, backendPort } = useAuthStore();
  const baseUrl = `http://${backendIp}:${backendPort}`;

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [price, setPrice] = useState('');
  const [costPerPiece, setCostPerPiece] = useState('');
  const [shippingFee, setShippingFee] = useState('');
  const [shippingDays, setShippingDays] = useState('3');
  const [categories, setCategories] = useState<string[]>([]);
  const [categoryInput, setCategoryInput] = useState('');
  const [sizes, setSizes] = useState<SizeEntry[]>([]);
  const [existingImages, setExistingImages] = useState<string[]>([]);
  const [newImages, setNewImages] = useState<{ uri: string; name: string; type: string }[]>([]);
  const [allowGcash, setAllowGcash] = useState(true);
  const [allowMaya, setAllowMaya] = useState(true);

  useEffect(() => {
    api.get(`/products/${id}`).then(res => {
      const p = res.data;
      setName(p.name || '');
      setDescription(p.description || '');
      setPrice(String(p.price || ''));
      setCostPerPiece(String(p.costPerPiece || ''));
      setShippingFee(String(p.shippingFee || ''));
      setShippingDays(String(p.shippingDays || '3'));
      setAllowGcash(p.allowGcash !== false);
      setAllowMaya(p.allowMaya !== false);
      try {
        const imgs = typeof p.image === 'string' ? JSON.parse(p.image) : (p.image || []);
        setExistingImages(Array.isArray(imgs) ? imgs : []);
      } catch { setExistingImages([]); }
      try {
        const cats = typeof p.categories === 'string' ? JSON.parse(p.categories) : (p.categories || []);
        setCategories(Array.isArray(cats) ? cats : []);
      } catch { setCategories([]); }
      setSizes((p.ProductSizes || []).map((s: any) => ({ size: s.size, stock: s.stock })));
    }).catch(() => { Alert.alert('Error', 'Failed to load product.'); router.back(); })
      .finally(() => setLoading(false));
  }, [id]);

  const pickImages = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') return;
    const res = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ImagePicker.MediaTypeOptions.Images, allowsMultipleSelection: true, quality: 0.8, selectionLimit: 5 });
    if (!res.canceled) {
      const newImgs = res.assets.map(a => ({ uri: a.uri, name: a.uri.split('/').pop() || 'image.jpg', type: a.mimeType || 'image/jpeg' }));
      setNewImages(prev => [...prev, ...newImgs].slice(0, 5));
    }
  };

  const updateStock = (size: string, delta: number) => setSizes(prev => prev.map(s => s.size === size ? { ...s, stock: Math.max(0, s.stock + delta) } : s));
  const toggleSize = (size: string) => {
    if (sizes.find(s => s.size === size)) setSizes(prev => prev.filter(s => s.size !== size));
    else setSizes(prev => [...prev, { size, stock: 10 }]);
  };

  const handleSave = async () => {
    if (!name.trim() || !price) { Alert.alert('Required', 'Name and price are required.'); return; }
    setSaving(true);
    try {
      const formData = new FormData();
      formData.append('name', name.trim());
      formData.append('description', description.trim());
      formData.append('price', price);
      formData.append('costPerPiece', costPerPiece || '0');
      formData.append('shippingFee', shippingFee || '0');
      formData.append('shippingDays', shippingDays || '3');
      formData.append('categories', JSON.stringify(categories));
      formData.append('sizes', JSON.stringify(sizes));
      formData.append('allowGcash', String(allowGcash));
      formData.append('allowMaya', String(allowMaya));
      formData.append('existingImages', JSON.stringify(existingImages));
      newImages.forEach(img => formData.append('images', { uri: img.uri, name: img.name, type: img.type } as any));
      await api.put(`/products/${id}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } });
      Alert.alert('Saved', 'Product updated successfully.', [{ text: 'OK', onPress: () => router.back() }]);
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to update product.'));
    } finally { setSaving(false); }
  };

  if (loading) return <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.bgDeep }}><ActivityIndicator color={Colors.primary} size="large" /></View>;

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <View style={styles.header}>
            <TouchableOpacity onPress={() => router.back()}>
              <Text style={styles.back}>← Back</Text>
            </TouchableOpacity>
            <Text style={styles.title}>Edit Product</Text>
          </View>

          <Text style={styles.sectionTitle}>Images</Text>
          <View style={styles.imagesRow}>
            {existingImages.map((img, i) => (
              <View key={`ex-${i}`} style={styles.imgWrap}>
                <Image source={{ uri: `${baseUrl}/uploads/products/${img}` }} style={styles.imgThumb} />
                <TouchableOpacity style={styles.removeBtn} onPress={() => setExistingImages(prev => prev.filter((_, idx) => idx !== i))}>
                  <Text style={styles.removeBtnText}>✕</Text>
                </TouchableOpacity>
              </View>
            ))}
            {newImages.map((img, i) => (
              <View key={`new-${i}`} style={styles.imgWrap}>
                <Image source={{ uri: img.uri }} style={styles.imgThumb} />
                <TouchableOpacity style={styles.removeBtn} onPress={() => setNewImages(prev => prev.filter((_, idx) => idx !== i))}>
                  <Text style={styles.removeBtnText}>✕</Text>
                </TouchableOpacity>
              </View>
            ))}
            {(existingImages.length + newImages.length) < 5 && (
              <TouchableOpacity style={styles.imgAdd} onPress={pickImages}>
                <Text style={styles.imgAddIcon}>+</Text>
                <Text style={styles.imgAddText}>Photo</Text>
              </TouchableOpacity>
            )}
          </View>

          <Text style={styles.sectionTitle}>Information</Text>
          <Input label="Product Name *" value={name} onChangeText={setName} placeholder="Product name" />
          <Input label="Description" value={description} onChangeText={setDescription} placeholder="Description..." multiline style={{ height: 80 }} />

          <Text style={styles.sectionTitle}>Pricing</Text>
          <View style={styles.twoCol}>
            <View style={{ flex: 1 }}><Input label="Price (₱) *" value={price} onChangeText={setPrice} placeholder="0.00" keyboardType="decimal-pad" /></View>
            <View style={{ width: Spacing.md }} />
            <View style={{ flex: 1 }}><Input label="Cost Per Piece (₱)" value={costPerPiece} onChangeText={setCostPerPiece} placeholder="0.00" keyboardType="decimal-pad" /></View>
          </View>
          <View style={styles.twoCol}>
            <View style={{ flex: 1 }}><Input label="Shipping Fee (₱)" value={shippingFee} onChangeText={setShippingFee} placeholder="0" keyboardType="decimal-pad" /></View>
            <View style={{ width: Spacing.md }} />
            <View style={{ flex: 1 }}><Input label="Shipping Days" value={shippingDays} onChangeText={setShippingDays} placeholder="3" keyboardType="numeric" /></View>
          </View>

          <Text style={styles.sectionTitle}>Categories</Text>
          <View style={styles.catRow}>
            <Input label="" value={categoryInput} onChangeText={setCategoryInput} placeholder="Add category" style={{ flex: 1, marginBottom: 0 }} onSubmitEditing={() => { if (categoryInput.trim() && !categories.includes(categoryInput.trim())) { setCategories(p => [...p, categoryInput.trim()]); setCategoryInput(''); } }} />
            <TouchableOpacity style={styles.addCatBtn} onPress={() => { if (categoryInput.trim()) { setCategories(p => [...p, categoryInput.trim()]); setCategoryInput(''); } }}>
              <Text style={styles.addCatText}>Add</Text>
            </TouchableOpacity>
          </View>
          <View style={styles.tagsRow}>
            {categories.map(c => (
              <TouchableOpacity key={c} style={styles.tag} onPress={() => setCategories(p => p.filter(x => x !== c))}>
                <Text style={styles.tagText}>{c} ✕</Text>
              </TouchableOpacity>
            ))}
          </View>

          <Text style={styles.sectionTitle}>Sizes & Stock</Text>
          <View style={styles.presetRow}>
            {PRESET_SIZES.map(s => (
              <TouchableOpacity key={s} style={[styles.presetBtn, sizes.find(sz => sz.size === s) && styles.presetActive]} onPress={() => toggleSize(s)}>
                <Text style={[styles.presetText, sizes.find(sz => sz.size === s) && styles.presetTextActive]}>{s}</Text>
              </TouchableOpacity>
            ))}
          </View>
          {sizes.map(se => (
            <View key={se.size} style={styles.sizeRow}>
              <Text style={styles.sizeName}>{se.size}</Text>
              <View style={styles.stockCtrl}>
                <TouchableOpacity style={styles.stockBtn} onPress={() => updateStock(se.size, -1)}><Text style={styles.stockBtnTxt}>−</Text></TouchableOpacity>
                <Text style={styles.stockNum}>{se.stock}</Text>
                <TouchableOpacity style={styles.stockBtn} onPress={() => updateStock(se.size, 1)}><Text style={styles.stockBtnTxt}>+</Text></TouchableOpacity>
              </View>
              <TouchableOpacity onPress={() => setSizes(p => p.filter(s => s.size !== se.size))}><Text style={{ fontSize: 18 }}>🗑️</Text></TouchableOpacity>
            </View>
          ))}

          <Text style={styles.sectionTitle}>Payments</Text>
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>Accept GCash</Text>
            <Switch value={allowGcash} onValueChange={setAllowGcash} trackColor={{ true: Colors.primary }} />
          </View>
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>Accept Maya</Text>
            <Switch value={allowMaya} onValueChange={setAllowMaya} trackColor={{ true: Colors.primary }} />
          </View>

          <Button label="Save Changes" variant="primary" size="lg" fullWidth loading={saving} onPress={handleSave} style={{ marginTop: Spacing.xxl }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { padding: Spacing.xl, paddingBottom: 120 },
  header: { marginBottom: Spacing.xl },
  back: { color: Colors.accent, fontSize: FontSize.md, marginBottom: Spacing.sm },
  title: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  sectionTitle: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginTop: Spacing.xl, marginBottom: Spacing.md, borderLeftWidth: 3, borderLeftColor: Colors.primary, paddingLeft: Spacing.md },
  imagesRow: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm },
  imgWrap: { width: 90, height: 90, borderRadius: Radius.md, overflow: 'hidden', position: 'relative' },
  imgThumb: { width: '100%', height: '100%' },
  removeBtn: { position: 'absolute', top: 4, right: 4, backgroundColor: 'rgba(0,0,0,0.6)', borderRadius: 9, width: 18, height: 18, justifyContent: 'center', alignItems: 'center' },
  removeBtnText: { color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold },
  imgAdd: { width: 90, height: 90, borderRadius: Radius.md, borderWidth: 2, borderStyle: 'dashed', borderColor: Colors.border, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.bgDark },
  imgAddIcon: { fontSize: 28, color: Colors.textMuted },
  imgAddText: { fontSize: FontSize.xs, color: Colors.textMuted },
  twoCol: { flexDirection: 'row' },
  catRow: { flexDirection: 'row', gap: Spacing.sm, alignItems: 'flex-end', marginBottom: Spacing.sm },
  addCatBtn: { backgroundColor: Colors.primary, borderRadius: Radius.md, paddingHorizontal: Spacing.lg, paddingVertical: 12, marginBottom: 2 },
  addCatText: { color: Colors.white, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  tagsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm },
  tag: { backgroundColor: Colors.bgMedium, borderRadius: Radius.full, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs, borderWidth: 1, borderColor: Colors.primary },
  tagText: { color: Colors.accent, fontSize: FontSize.sm },
  presetRow: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm, marginBottom: Spacing.lg },
  presetBtn: { paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.border, backgroundColor: Colors.bgDark },
  presetActive: { borderColor: Colors.primary, backgroundColor: `${Colors.primary}22` },
  presetText: { fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  presetTextActive: { color: Colors.primary, fontWeight: FontWeight.bold },
  sizeRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: Spacing.md, marginBottom: Spacing.sm, borderWidth: 1, borderColor: Colors.border },
  sizeName: { flex: 1, fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  stockCtrl: { flexDirection: 'row', alignItems: 'center', gap: Spacing.md },
  stockBtn: { width: 30, height: 30, borderRadius: 15, backgroundColor: Colors.bgMedium, justifyContent: 'center', alignItems: 'center' },
  stockBtnTxt: { color: Colors.textPrimary, fontSize: FontSize.lg, fontWeight: FontWeight.bold },
  stockNum: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary, minWidth: 32, textAlign: 'center' },
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: Spacing.lg, marginBottom: Spacing.sm, borderWidth: 1, borderColor: Colors.border },
  switchLabel: { fontSize: FontSize.md, color: Colors.textSecondary, fontWeight: FontWeight.semibold },
});
