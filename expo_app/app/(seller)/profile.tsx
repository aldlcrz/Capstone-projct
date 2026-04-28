/**
 * Seller Profile Screen — Shop settings, GCash/Maya, social links, logout
 * Mirrors /seller/profile/page.js
 */
import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, Alert, Image } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/store/authStore';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { ConfirmationModal } from '@/components/ui/ConfirmationModal';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

export default function SellerProfile() {
  const { user, logout, backendIp, backendPort } = useAuthStore();
  const baseUrl = `http://${backendIp}:${backendPort}`;
  const [name, setName] = useState(user?.name || '');
  const [gcashNumber, setGcashNumber] = useState(user?.gcashNumber || '');
  const [mayaNumber, setMayaNumber] = useState(user?.mayaNumber || '');
  const [mobileNumber, setMobileNumber] = useState(user?.mobileNumber || '');
  const [loading, setLoading] = useState(false);
  const [showLogout, setShowLogout] = useState(false);
  const avatarUrl = user?.profilePhoto ? `${baseUrl}/uploads/profiles/${user.profilePhoto}` : null;

  const handleSave = async () => {
    setLoading(true);
    try {
      await api.put('/users/seller-profile', { name, gcashNumber, mayaNumber, mobileNumber });
      Alert.alert('Saved', 'Shop profile updated successfully.');
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to save profile.'));
    } finally { setLoading(false); }
  };

  const handlePhotoChange = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') return;
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ImagePicker.MediaTypeOptions.Images, quality: 0.8, allowsEditing: true, aspect: [1, 1] });
    if (result.canceled || !result.assets[0]) return;
    const asset = result.assets[0];
    try {
      const formData = new FormData();
      formData.append('photo', { uri: asset.uri, name: 'profile.jpg', type: 'image/jpeg' } as any);
      await api.put('/users/profile-photo', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
      Alert.alert('Success', 'Profile photo updated!');
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to update photo.'));
    }
  };

  const handleLogout = async () => { await logout(); router.replace('/'); };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <Text style={styles.pageTitle}>Shop Profile</Text>

        <View style={styles.avatarSection}>
          <TouchableOpacity style={styles.avatarWrap} onPress={handlePhotoChange}>
            {avatarUrl ? <Image source={{ uri: avatarUrl }} style={styles.avatar} /> :
              <View style={styles.avatarPlaceholder}><Text style={styles.avatarInitial}>{(user?.name || 'S').charAt(0).toUpperCase()}</Text></View>}
            <View style={styles.editBadge}><Text>✏️</Text></View>
          </TouchableOpacity>
          <Text style={styles.sellerName}>{user?.name}</Text>
          <Text style={styles.sellerEmail}>{user?.email}</Text>
        </View>

        <View style={styles.form}>
          <Text style={styles.sectionLabel}>Shop Information</Text>
          <Input label="Shop / Display Name" value={name} onChangeText={setName} placeholder="Your shop name" />
          <Input label="Mobile Number" value={mobileNumber} onChangeText={setMobileNumber} placeholder="09XXXXXXXXX" keyboardType="numeric" />

          <Text style={[styles.sectionLabel, { marginTop: Spacing.xl }]}>Payment Details</Text>
          <Input label="GCash Number" value={gcashNumber} onChangeText={setGcashNumber} placeholder="09XXXXXXXXX" keyboardType="numeric" />
          <Input label="Maya Number" value={mayaNumber} onChangeText={setMayaNumber} placeholder="09XXXXXXXXX" keyboardType="numeric" />

          <Button label="Save Profile" variant="primary" size="lg" fullWidth loading={loading} onPress={handleSave} style={{ marginTop: Spacing.xl }} />
        </View>

        <View style={styles.linksSection}>
          <TouchableOpacity style={styles.linkItem} onPress={() => router.push('/(seller)/inventory' as any)}>
            <Text style={styles.linkText}>📦 My Products</Text>
            <Text style={styles.arrow}>›</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.linkItem} onPress={() => router.push('/(seller)/orders' as any)}>
            <Text style={styles.linkText}>🛒 My Orders</Text>
            <Text style={styles.arrow}>›</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.linkItem} onPress={() => router.push('/forgot-password' as any)}>
            <Text style={styles.linkText}>🔒 Change Password</Text>
            <Text style={styles.arrow}>›</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity style={styles.logoutBtn} onPress={() => setShowLogout(true)}>
          <Text style={styles.logoutText}>Sign Out</Text>
        </TouchableOpacity>
      </ScrollView>

      <ConfirmationModal visible={showLogout} title="Sign Out" message="Are you sure you want to sign out?" confirmLabel="Sign Out" danger onConfirm={handleLogout} onCancel={() => setShowLogout(false)} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { padding: Spacing.xl, paddingBottom: 100 },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: Spacing.xxl },
  avatarSection: { alignItems: 'center', marginBottom: Spacing.xxl },
  avatarWrap: { position: 'relative', marginBottom: Spacing.md },
  avatar: { width: 96, height: 96, borderRadius: 48, borderWidth: 3, borderColor: Colors.primary },
  avatarPlaceholder: { width: 96, height: 96, borderRadius: 48, backgroundColor: Colors.primary, justifyContent: 'center', alignItems: 'center' },
  avatarInitial: { fontSize: 40, fontWeight: FontWeight.bold, color: Colors.white },
  editBadge: { position: 'absolute', bottom: 0, right: 0, backgroundColor: Colors.bgDark, borderRadius: 12, width: 24, height: 24, justifyContent: 'center', alignItems: 'center', borderWidth: 2, borderColor: Colors.border },
  sellerName: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  sellerEmail: { fontSize: FontSize.sm, color: Colors.textMuted },
  form: { marginBottom: Spacing.xxl },
  sectionLabel: { fontSize: FontSize.xs, color: Colors.textMuted, fontWeight: FontWeight.bold, textTransform: 'uppercase', letterSpacing: 1.5, marginBottom: Spacing.md },
  linksSection: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, borderWidth: 1, borderColor: Colors.border, marginBottom: Spacing.xl, overflow: 'hidden' },
  linkItem: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: Spacing.xl, borderBottomWidth: 1, borderBottomColor: Colors.border },
  linkText: { fontSize: FontSize.md, color: Colors.textSecondary },
  arrow: { fontSize: FontSize.xl, color: Colors.textMuted },
  logoutBtn: { backgroundColor: Colors.errorLight, borderRadius: Radius.full, padding: Spacing.lg, alignItems: 'center', borderWidth: 1, borderColor: Colors.error },
  logoutText: { color: Colors.error, fontWeight: FontWeight.bold, fontSize: FontSize.md, letterSpacing: 1 },
});
