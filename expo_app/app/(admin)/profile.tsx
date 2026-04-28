/**
 * Admin Profile Screen — Profile edit + logout
 */
import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity } from 'react-native';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/store/authStore';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { ConfirmationModal } from '@/components/ui/ConfirmationModal';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

export default function AdminProfile() {
  const { user, logout } = useAuthStore();
  const [name, setName] = useState(user?.name || '');
  const [loading, setLoading] = useState(false);
  const [showLogout, setShowLogout] = useState(false);
  const [error, setError] = useState('');

  const handleSave = async () => {
    setLoading(true);
    try {
      await api.put('/users/profile', { name });
    } catch (err: any) {
      setError(getApiErrorMessage(err, 'Failed to save.'));
    } finally { setLoading(false); }
  };

  const handleLogout = async () => { await logout(); router.replace('/'); };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll}>
        <Text style={styles.pageTitle}>Admin Profile</Text>

        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{(user?.name || 'A').charAt(0).toUpperCase()}</Text>
        </View>
        <Text style={styles.userName}>{user?.name}</Text>
        <Text style={styles.userEmail}>{user?.email}</Text>
        <View style={styles.badge}><Text style={styles.badgeText}>ADMIN</Text></View>

        <View style={styles.form}>
          {!!error && <Text style={styles.error}>{error}</Text>}
          <Input label="Display Name" value={name} onChangeText={setName} placeholder="Admin name" />
          <Button label="Save Changes" variant="primary" size="lg" fullWidth loading={loading} onPress={handleSave} style={{ marginTop: Spacing.md }} />
        </View>

        <TouchableOpacity style={styles.logoutBtn} onPress={() => setShowLogout(true)}>
          <Text style={styles.logoutText}>Sign Out</Text>
        </TouchableOpacity>
      </ScrollView>
      <ConfirmationModal visible={showLogout} title="Sign Out" message="Sign out of admin panel?" confirmLabel="Sign Out" danger onConfirm={handleLogout} onCancel={() => setShowLogout(false)} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { padding: Spacing.xxl, paddingBottom: 100, alignItems: 'center' },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, alignSelf: 'flex-start', marginBottom: Spacing.xxl },
  avatar: { width: 96, height: 96, borderRadius: 48, backgroundColor: Colors.primary, justifyContent: 'center', alignItems: 'center', marginBottom: Spacing.md },
  avatarText: { fontSize: 40, fontWeight: FontWeight.bold, color: Colors.white },
  userName: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 4 },
  userEmail: { fontSize: FontSize.sm, color: Colors.textMuted, marginBottom: Spacing.sm },
  badge: { backgroundColor: Colors.primary, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: 4, marginBottom: Spacing.xxl },
  badgeText: { color: Colors.white, fontSize: FontSize.xs, fontWeight: FontWeight.bold, letterSpacing: 2 },
  form: { width: '100%', marginBottom: Spacing.xxl },
  error: { color: Colors.error, fontSize: FontSize.sm, marginBottom: Spacing.md },
  logoutBtn: { width: '100%', backgroundColor: Colors.errorLight, borderRadius: Radius.full, padding: Spacing.lg, alignItems: 'center', borderWidth: 1, borderColor: Colors.error },
  logoutText: { color: Colors.error, fontWeight: FontWeight.bold, fontSize: FontSize.md, letterSpacing: 1 },
});
