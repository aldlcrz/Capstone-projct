/**
 * Set Password Screen — for Google OAuth users who haven't set a password yet
 * Mirrors SetPasswordModal.jsx
 * Called after first Google login when hasPasswordSet === false
 * Route: app/set-password.tsx
 */
import React, { useState } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, Alert, Platform, KeyboardAvoidingView,
} from 'react-native';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/store/authStore';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

export default function SetPasswordScreen() {
  const { user, token, role, updateUser } = useAuthStore();
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleSubmit = async () => {
    setError('');
    if (password.length < 6) { setError('Password must be at least 6 characters.'); return; }
    if (password !== confirm) { setError('Passwords do not match.'); return; }

    setLoading(true);
    try {
      await api.post(
        '/auth/set-password',
        { password },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      updateUser({ hasPasswordSet: true });
      setSuccess(true);
      // Redirect to their dashboard after short delay
      setTimeout(() => {
        if (role === 'admin') router.replace('/(admin)/dashboard' as any);
        else if (role === 'seller') router.replace('/(seller)/dashboard' as any);
        else router.replace('/(customer)/home' as any);
      }, 2000);
    } catch (err: any) {
      setError(getApiErrorMessage(err, 'Failed to set password. Please try again.'));
    } finally { setLoading(false); }
  };

  const handleSkip = () => {
    if (role === 'admin') router.replace('/(admin)/dashboard' as any);
    else if (role === 'seller') router.replace('/(seller)/dashboard' as any);
    else router.replace('/(customer)/home' as any);
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          {/* Logo */}
          <Text style={styles.logo}>LumbaRong</Text>

          {/* Icon */}
          <View style={styles.iconWrap}>
            <Text style={styles.icon}>🔒</Text>
          </View>

          <Text style={styles.title}>Secure Your Account</Text>
          <Text style={styles.subtitle}>
            Set a password for{' '}
            <Text style={styles.email}>{user?.email}</Text>
          </Text>

          {success ? (
            <View style={styles.successBox}>
              <Text style={styles.successIcon}>✅</Text>
              <Text style={styles.successTitle}>Password Set!</Text>
              <Text style={styles.successMsg}>Redirecting you to your dashboard...</Text>
            </View>
          ) : (
            <View style={styles.form}>
              {!!error && (
                <View style={styles.errorBanner}>
                  <Text style={styles.errorText}>{error}</Text>
                </View>
              )}

              <Input
                label="New Password"
                value={password}
                onChangeText={setPassword}
                placeholder="At least 6 characters"
                secureTextEntry
              />
              <Input
                label="Confirm Password"
                value={confirm}
                onChangeText={setConfirm}
                placeholder="Repeat your password"
                secureTextEntry
              />

              <Button
                label="Set Password →"
                variant="primary"
                size="lg"
                fullWidth
                loading={loading}
                onPress={handleSubmit}
                style={{ marginTop: Spacing.md }}
              />

              <TouchableOpacity style={styles.skipBtn} onPress={handleSkip}>
                <Text style={styles.skipText}>Skip for now</Text>
              </TouchableOpacity>
            </View>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { flexGrow: 1, padding: Spacing.xxl, paddingTop: Spacing.xl, alignItems: 'center' },
  logo: {
    fontFamily: Platform.OS === 'ios' ? 'Georgia' : 'serif',
    fontSize: 22, fontWeight: FontWeight.heavy,
    fontStyle: 'italic', color: Colors.accent,
    textAlign: 'center', marginBottom: Spacing.xxl,
  },
  iconWrap: {
    width: 80, height: 80, borderRadius: 24,
    backgroundColor: `${Colors.primary}22`,
    justifyContent: 'center', alignItems: 'center',
    marginBottom: Spacing.xl,
  },
  icon: { fontSize: 36 },
  title: {
    fontSize: FontSize.xxl, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, textAlign: 'center', marginBottom: Spacing.sm,
  },
  subtitle: {
    fontSize: FontSize.md, color: Colors.textSecondary,
    textAlign: 'center', lineHeight: 22, marginBottom: Spacing.xxxl,
  },
  email: { color: Colors.primary, fontWeight: FontWeight.bold },
  form: { width: '100%' },
  errorBanner: {
    backgroundColor: Colors.errorLight, borderRadius: Radius.md,
    padding: Spacing.lg, marginBottom: Spacing.xl,
    borderLeftWidth: 3, borderLeftColor: Colors.error,
  },
  errorText: { color: Colors.error, fontSize: FontSize.sm, fontWeight: FontWeight.semibold },
  skipBtn: { alignItems: 'center', marginTop: Spacing.xl, padding: Spacing.md },
  skipText: { color: Colors.textMuted, fontSize: FontSize.sm, fontWeight: FontWeight.bold, letterSpacing: 1 },
  successBox: { alignItems: 'center', paddingVertical: Spacing.xxxl },
  successIcon: { fontSize: 64, marginBottom: Spacing.xl },
  successTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.accent, marginBottom: Spacing.sm },
  successMsg: { fontSize: FontSize.md, color: Colors.textSecondary, textAlign: 'center' },
});
