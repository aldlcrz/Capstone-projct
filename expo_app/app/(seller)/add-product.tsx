/**
 * Seller Add Product — full form with multi-image upload, sizes, pricing
 * Mirrors web seller/add-product or seller inventory add form
 */
import React, { useState } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity,
  Image, Alert, KeyboardAvoidingView, Platform, Switch,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

const PRESET_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'Custom'];

interface SizeEntry { size: string; stock: number; }
interface ProductImage { uri: string; name: string; type: string; }

export default function AddProductScreen() {
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [price, setPrice] = useState('');
  const [costPerPiece, setCostPerPiece] = useState('');
  const [shippingFee, setShippingFee] = useState('');
  const [shippingDays, setShippingDays] = useState('3');
  const [categories, setCategories] = useState<string[]>([]);
  const [categoryInput, setCategoryInput] = useState('');
  const [sizes, setSizes] = useState<SizeEntry[]>([{ size: 'M', stock: 10 }]);
  const [images, setImages] = useState<ProductImage[]>([]);
  const [allowGcash, setAllowGcash] = useState(true);
  const [allowMaya, setAllowMaya] = useState(true);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<string[]>([]);

  const pickImages = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') { Alert.alert('Permission needed'); return; }
    const res = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      allowsMultipleSelection: true,
      quality: 0.8,
      selectionLimit: 5,
    });
    if (!res.canceled && res.assets.length > 0) {
      const newImgs = res.assets.map(a => ({
        uri: a.uri,
        name: a.uri.split('/').pop() || 'image.jpg',
        type: a.mimeType || 'image/jpeg',
      }));
      setImages(prev => [...prev, ...newImgs].slice(0, 5));
    }
  };

  const removeImage = (idx: number) => setImages(prev => prev.filter((_, i) => i !== idx));

  const addSize = (size: string) => {
    if (sizes.find(s => s.size === size)) return;
    setSizes(prev => [...prev, { size, stock: 10 }]);
  };
  const removeSize = (size: string) => setSizes(prev => prev.filter(s => s.size !== size));
  const updateStock = (size: string, stock: string) => {
    setSizes(prev => prev.map(s => s.size === size ? { ...s, stock: Math.max(0, parseInt(stock) || 0) } : s));
  };

  const addCategory = () => {
    const cat = categoryInput.trim();
    if (cat && !categories.includes(cat)) {
      setCategories(prev => [...prev, cat]);
      setCategoryInput('');
    }
  };

  const validate = (): boolean => {
    const errs: string[] = [];
    if (!name.trim()) errs.push('Product name is required.');
    if (!price || isNaN(Number(price)) || Number(price) <= 0) errs.push('Valid price is required.');
    if (!costPerPiece || isNaN(Number(costPerPiece)) || Number(costPerPiece) < 0) errs.push('Valid cost per piece is required.');
    if (images.length === 0) errs.push('At least one product image is required.');
    if (sizes.length === 0) errs.push('At least one size is required.');
    setErrors(errs);
    return errs.length === 0;
  };

  const handleSubmit = async () => {
    if (!validate()) return;
    setLoading(true);
    try {
      const formData = new FormData();
      formData.append('name', name.trim());
      formData.append('description', description.trim());
      formData.append('price', price);
      formData.append('costPerPiece', costPerPiece);
      formData.append('shippingFee', shippingFee || '0');
      formData.append('shippingDays', shippingDays || '3');
      formData.append('categories', JSON.stringify(categories));
      formData.append('sizes', JSON.stringify(sizes));
      formData.append('allowGcash', String(allowGcash));
      formData.append('allowMaya', String(allowMaya));
      images.forEach((img, i) => {
        formData.append('images', { uri: img.uri, name: img.name, type: img.type } as any);
      });

      await api.post('/products', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
      Alert.alert('Success', 'Product listed successfully!', [
        { text: 'Add Another', onPress: () => router.replace('/(seller)/add-product' as any) },
        { text: 'View Inventory', onPress: () => router.replace('/(seller)/inventory' as any) },
      ]);
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to list product.'));
    } finally { setLoading(false); }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>
          <View style={styles.header}>
            <TouchableOpacity onPress={() => router.back()}>
              <Text style={styles.backBtn}>← Back</Text>
            </TouchableOpacity>
            <Text style={styles.pageTitle}>Add Product</Text>
          </View>

          {errors.length > 0 && (
            <View style={styles.errorBox}>
              {errors.map((e, i) => <Text key={i} style={styles.errorText}>• {e}</Text>)}
            </View>
          )}

          {/* Images */}
          <Text style={styles.sectionTitle}>Product Images (up to 5)</Text>
          <View style={styles.imagesRow}>
            {images.map((img, i) => (
              <View key={i} style={styles.imgPreview}>
                <Image source={{ uri: img.uri }} style={styles.imgThumb} />
                <TouchableOpacity style={styles.imgRemove} onPress={() => removeImage(i)}>
                  <Text style={styles.imgRemoveText}>✕</Text>
                </TouchableOpacity>
              </View>
            ))}
            {images.length < 5 && (
              <TouchableOpacity style={styles.imgAdd} onPress={pickImages}>
                <Text style={styles.imgAddIcon}>+</Text>
                <Text style={styles.imgAddText}>Photo</Text>
              </TouchableOpacity>
            )}
          </View>

          {/* Basic Info */}
          <Text style={styles.sectionTitle}>Basic Information</Text>
          <Input label="Product Name *" value={name} onChangeText={setName} placeholder="e.g. Embroidered Barong Tagalog" />
          <Input label="Description" value={description} onChangeText={setDescription} placeholder="Describe the craftsmanship and materials..." multiline style={{ height: 80 }} />

          {/* Pricing */}
          <Text style={styles.sectionTitle}>Pricing</Text>
          <View style={styles.twoCol}>
            <View style={{ flex: 1 }}><Input label="Selling Price (₱) *" value={price} onChangeText={setPrice} placeholder="0.00" keyboardType="decimal-pad" /></View>
            <View style={{ width: Spacing.md }} />
            <View style={{ flex: 1 }}><Input label="Cost Per Piece (₱) *" value={costPerPiece} onChangeText={setCostPerPiece} placeholder="0.00" keyboardType="decimal-pad" /></View>
          </View>
          <View style={styles.twoCol}>
            <View style={{ flex: 1 }}><Input label="Shipping Fee (₱)" value={shippingFee} onChangeText={setShippingFee} placeholder="0.00" keyboardType="decimal-pad" /></View>
            <View style={{ width: Spacing.md }} />
            <View style={{ flex: 1 }}><Input label="Shipping Days" value={shippingDays} onChangeText={setShippingDays} placeholder="3" keyboardType="numeric" /></View>
          </View>

          {/* Categories */}
          <Text style={styles.sectionTitle}>Categories</Text>
          <View style={styles.catInputRow}>
            <Input
              label=""
              value={categoryInput}
              onChangeText={setCategoryInput}
              placeholder="e.g. Traditional, Formal"
              style={{ flex: 1, marginBottom: 0 }}
              onSubmitEditing={addCategory}
            />
            <TouchableOpacity style={styles.catAddBtn} onPress={addCategory}>
              <Text style={styles.catAddText}>Add</Text>
            </TouchableOpacity>
          </View>
          <View style={styles.tagsRow}>
            {categories.map(cat => (
              <TouchableOpacity key={cat} style={styles.tag} onPress={() => setCategories(prev => prev.filter(c => c !== cat))}>
                <Text style={styles.tagText}>{cat} ✕</Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* Sizes */}
          <Text style={styles.sectionTitle}>Sizes & Stock</Text>
          <View style={styles.presetSizes}>
            {PRESET_SIZES.map(s => (
              <TouchableOpacity key={s} style={[styles.presetBtn, sizes.find(sz => sz.size === s) && styles.presetBtnActive]} onPress={() => sizes.find(sz => sz.size === s) ? removeSize(s) : addSize(s)}>
                <Text style={[styles.presetBtnText, sizes.find(sz => sz.size === s) && styles.presetBtnTextActive]}>{s}</Text>
              </TouchableOpacity>
            ))}
          </View>
          {sizes.map(sizeEntry => (
            <View key={sizeEntry.size} style={styles.sizeRow}>
              <Text style={styles.sizeLabel}>{sizeEntry.size}</Text>
              <View style={styles.sizeStockControls}>
                <TouchableOpacity style={styles.stockBtn} onPress={() => updateStock(sizeEntry.size, String(Math.max(0, sizeEntry.stock - 1)))}>
                  <Text style={styles.stockBtnText}>−</Text>
                </TouchableOpacity>
                <Text style={styles.stockNum}>{sizeEntry.stock}</Text>
                <TouchableOpacity style={styles.stockBtn} onPress={() => updateStock(sizeEntry.size, String(sizeEntry.stock + 1))}>
                  <Text style={styles.stockBtnText}>+</Text>
                </TouchableOpacity>
              </View>
              <TouchableOpacity onPress={() => removeSize(sizeEntry.size)}>
                <Text style={styles.sizeRemove}>🗑️</Text>
              </TouchableOpacity>
            </View>
          ))}

          {/* Payment Methods */}
          <Text style={styles.sectionTitle}>Accept Payments</Text>
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>Accept GCash</Text>
            <Switch value={allowGcash} onValueChange={setAllowGcash} trackColor={{ true: Colors.primary }} />
          </View>
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>Accept Maya</Text>
            <Switch value={allowMaya} onValueChange={setAllowMaya} trackColor={{ true: Colors.primary }} />
          </View>

          <Button label="List Product" variant="primary" size="lg" fullWidth loading={loading} onPress={handleSubmit} style={{ marginTop: Spacing.xxl }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { padding: Spacing.xl, paddingBottom: 120 },
  header: { marginBottom: Spacing.xl },
  backBtn: { color: Colors.accent, fontSize: FontSize.md, marginBottom: Spacing.sm },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  errorBox: { backgroundColor: Colors.errorLight, borderRadius: Radius.md, padding: Spacing.lg, marginBottom: Spacing.xl, borderLeftWidth: 3, borderLeftColor: Colors.error },
  errorText: { color: Colors.error, fontSize: FontSize.sm, marginBottom: 2 },
  sectionTitle: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginTop: Spacing.xl, marginBottom: Spacing.md, borderLeftWidth: 3, borderLeftColor: Colors.primary, paddingLeft: Spacing.md },
  imagesRow: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm, marginBottom: Spacing.md },
  imgPreview: { width: 90, height: 90, borderRadius: Radius.md, overflow: 'hidden', position: 'relative' },
  imgThumb: { width: '100%', height: '100%' },
  imgRemove: { position: 'absolute', top: 4, right: 4, backgroundColor: 'rgba(0,0,0,0.6)', borderRadius: 10, width: 18, height: 18, justifyContent: 'center', alignItems: 'center' },
  imgRemoveText: { color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold },
  imgAdd: { width: 90, height: 90, borderRadius: Radius.md, borderWidth: 2, borderStyle: 'dashed', borderColor: Colors.border, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.bgDark },
  imgAddIcon: { fontSize: 28, color: Colors.textMuted },
  imgAddText: { fontSize: FontSize.xs, color: Colors.textMuted },
  twoCol: { flexDirection: 'row' },
  catInputRow: { flexDirection: 'row', alignItems: 'flex-end', gap: Spacing.sm, marginBottom: Spacing.sm },
  catAddBtn: { backgroundColor: Colors.primary, borderRadius: Radius.md, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, marginBottom: 2 },
  catAddText: { color: Colors.white, fontWeight: FontWeight.bold, fontSize: FontSize.sm },
  tagsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm, marginBottom: Spacing.md },
  tag: { backgroundColor: Colors.bgMedium, borderRadius: Radius.full, paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs, borderWidth: 1, borderColor: Colors.primary },
  tagText: { color: Colors.accent, fontSize: FontSize.sm, fontWeight: FontWeight.semibold },
  presetSizes: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm, marginBottom: Spacing.lg },
  presetBtn: { paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, borderRadius: Radius.md, borderWidth: 1.5, borderColor: Colors.border, backgroundColor: Colors.bgDark },
  presetBtnActive: { borderColor: Colors.primary, backgroundColor: `${Colors.primary}22` },
  presetBtnText: { fontSize: FontSize.sm, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  presetBtnTextActive: { color: Colors.primary, fontWeight: FontWeight.bold },
  sizeRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: Spacing.md, marginBottom: Spacing.sm, borderWidth: 1, borderColor: Colors.border },
  sizeLabel: { flex: 1, fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  sizeStockControls: { flexDirection: 'row', alignItems: 'center', gap: Spacing.md },
  stockBtn: { width: 30, height: 30, borderRadius: 15, backgroundColor: Colors.bgMedium, justifyContent: 'center', alignItems: 'center', borderWidth: 1, borderColor: Colors.border },
  stockBtnText: { color: Colors.textPrimary, fontSize: FontSize.lg, fontWeight: FontWeight.bold },
  stockNum: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary, minWidth: 32, textAlign: 'center' },
  sizeRemove: { fontSize: 18, marginLeft: Spacing.md },
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: Colors.bgDark, borderRadius: Radius.md, padding: Spacing.lg, marginBottom: Spacing.sm, borderWidth: 1, borderColor: Colors.border },
  switchLabel: { fontSize: FontSize.md, color: Colors.textSecondary, fontWeight: FontWeight.semibold },
});
