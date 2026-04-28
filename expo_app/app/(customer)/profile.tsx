/**
 * Profile Screen — Customer profile view/edit
 * Mirrors /customer/profile/page.js
 */
import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, Alert, Image } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { api, getApiErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/store/authStore';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { ConfirmationModal } from '@/components/ui/ConfirmationModal';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';
import { router } from 'expo-router';

export default function CustomerProfile() {
  const { user, logout, backendIp, backendPort } = useAuthStore();
  const [name, setName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [phone, setPhone] = useState(user?.mobileNumber || '');
  const [loading, setLoading] = useState(false);
  const [photoLoading, setPhotoLoading] = useState(false);
  const [showLogout, setShowLogout] = useState(false);
  const baseUrl = `http://${backendIp}:${backendPort}`;
  const avatarUrl = user?.profilePhoto ? `${baseUrl}/uploads/profiles/${user.profilePhoto}` : null;

  const handleSave = async () => {
    setLoading(true);
    try {
      await api.put('/users/profile', { name, mobileNumber: phone });
      Alert.alert('Saved', 'Profile updated successfully.');
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to update profile.'));
    } finally { setLoading(false); }
  };

  const handlePhotoChange = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') { Alert.alert('Permission needed', 'Allow access to your photo library.'); return; }
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ImagePicker.MediaTypeOptions.Images, quality: 0.8, allowsEditing: true, aspect: [1, 1] });
    if (result.canceled || !result.assets[0]) return;
    const asset = result.assets[0];
    setPhotoLoading(true);
    try {
      const formData = new FormData();
      formData.append('photo', { uri: asset.uri, name: 'profile.jpg', type: 'image/jpeg' } as any);
      await api.put('/users/profile-photo', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
      Alert.alert('Success', 'Profile photo updated!');
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to update photo.'));
    } finally { setPhotoLoading(false); }
  };

  const handleLogout = async () => {
    await logout();
    router.replace('/');
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <Text style={styles.pageTitle}>My Profile</Text>

        {/* Avatar */}
        <View style={styles.avatarSection}>
          <TouchableOpacity style={styles.avatarWrap} onPress={handlePhotoChange}>
            {avatarUrl ? (
              <Image source={{ uri: avatarUrl }} style={styles.avatar} />
            ) : (
              <View style={styles.avatarPlaceholder}>
                <Text style={styles.avatarInitial}>{(user?.name || 'U').charAt(0).toUpperCase()}</Text>
              </View>
            )}
            <View style={styles.avatarBadge}><Text style={styles.avatarBadgeText}>✏️</Text></View>
          </TouchableOpacity>
          <Text style={styles.userName}>{user?.name}</Text>
          <Text style={styles.userRole}>{user?.role?.toUpperCase()}</Text>
        </View>

        {/* Form */}
        <View style={styles.form}>
          <Input label="Full Name" value={name} onChangeText={setName} placeholder="Your full name" />
          <Input label="Email Address" value={email} onChangeText={setEmail} placeholder="you@example.com" autoCapitalize="none" keyboardType="email-address" editable={false} />
          <Input label="Mobile Number" value={phone} onChangeText={setPhone} placeholder="09XXXXXXXXX" keyboardType="numeric" />
          <Button label="Save Changes" variant="primary" size="lg" fullWidth loading={loading} onPress={handleSave} style={{ marginTop: Spacing.md }} />
        </View>

        {/* Navigation Links */}
        <View style={styles.linksSection}>
          <TouchableOpacity style={styles.linkItem} onPress={() => router.push('/(customer)/addresses' as any)}>
            <Text style={styles.linkText}>📍 Manage Addresses</Text>
            <Text style={styles.linkArrow}>›</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.linkItem} onPress={() => router.push('/(customer)/orders' as any)}>
            <Text style={styles.linkText}>📦 My Orders</Text>
            <Text style={styles.linkArrow}>›</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.linkItem} onPress={() => router.push('/forgot-password' as any)}>
            <Text style={styles.linkText}>🔒 Change Password</Text>
            <Text style={styles.linkArrow}>›</Text>
          </TouchableOpacity>
        </View>

        {/* Logout */}
        <TouchableOpacity style={styles.logoutBtn} onPress={() => setShowLogout(true)}>
          <Text style={styles.logoutText}>Sign Out</Text>
        </TouchableOpacity>
      </ScrollView>

      <ConfirmationModal
        visible={showLogout}
        title="Sign Out"
        message="Are you sure you want to sign out?"
        confirmLabel="Sign Out"
        danger
        onConfirm={handleLogout}
        onCancel={() => setShowLogout(false)}
      />
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
  avatarPlaceholder: { width: 96, height: 96, borderRadius: 48, backgroundColor: Colors.primary, justifyContent: 'center', alignItems: 'center', borderWidth: 3, borderColor: Colors.accent },
  avatarInitial: { fontSize: 40, fontWeight: FontWeight.bold, color: Colors.white },
  avatarBadge: { position: 'absolute', bottom: 0, right: 0, backgroundColor: Colors.bgDark, borderRadius: 12, width: 24, height: 24, justifyContent: 'center', alignItems: 'center', borderWidth: 2, borderColor: Colors.border },
  avatarBadgeText: { fontSize: 12 },
  userName: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 2 },
  userRole: { fontSize: FontSize.xs, color: Colors.textMuted, letterSpacing: 2, fontWeight: FontWeight.bold },
  form: { marginBottom: Spacing.xxl },
  linksSection: { backgroundColor: Colors.bgDark, borderRadius: Radius.lg, borderWidth: 1, borderColor: Colors.border, marginBottom: Spacing.xl, overflow: 'hidden' },
  linkItem: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: Spacing.xl, borderBottomWidth: 1, borderBottomColor: Colors.border },
  linkText: { fontSize: FontSize.md, color: Colors.textSecondary },
  linkArrow: { fontSize: FontSize.xl, color: Colors.textMuted },
  logoutBtn: { backgroundColor: Colors.errorLight, borderRadius: Radius.full, padding: Spacing.lg, alignItems: 'center', borderWidth: 1, borderColor: Colors.error },
  logoutText: { color: Colors.error, fontWeight: FontWeight.bold, fontSize: FontSize.md, letterSpacing: 1 },
});
