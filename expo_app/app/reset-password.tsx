import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, Platform, KeyboardAvoidingView } from 'react-native';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function ResetPasswordScreen() {
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [token, setToken] = useState('');

  useEffect(() => {
    AsyncStorage.getItem('reset_token').then((t) => { if (t) setToken(t); });
  }, []);

  const handleReset = async () => {
    setError('');
    if (!password || password.length < 6) { setError('Password must be at least 6 characters.'); return; }
    if (password !== confirm) { setError('Passwords do not match.'); return; }
    if (!token) { setError('Reset token missing. Please request a new OTP.'); return; }

    setLoading(true);
    try {
      await api.post('/auth/reset-password', { token, password });
      await AsyncStorage.multiRemove(['reset_token', 'reset_email']);
      setSuccess(true);
    } catch (err: any) {
      setError(getApiErrorMessage(err, 'Failed to reset password.'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <Text style={styles.logo}>LumbaRong</Text>
          <Text style={styles.title}>{success ? 'Password Reset!' : 'Set New Password'}</Text>

          {!!error && <View style={styles.errorBanner}><Text style={styles.errorText}>{error}</Text></View>}

          {success ? (
            <View style={styles.successBox}>
              <Text style={styles.successIcon}>✅</Text>
              <Text style={styles.successMsg}>Your password has been successfully reset.</Text>
              <Button label="Back to Login" variant="primary" size="lg" fullWidth onPress={() => router.replace('/login')} style={{ marginTop: Spacing.xl }} />
            </View>
          ) : (
            <View>
              <Text style={styles.desc}>Enter your new password below.</Text>
              <Input label="New Password" value={password} onChangeText={setPassword} placeholder="••••••••" secureTextEntry />
              <Input label="Confirm Password" value={confirm} onChangeText={setConfirm} placeholder="••••••••" secureTextEntry />
              <Button label="Reset Password" variant="primary" size="lg" fullWidth loading={loading} onPress={handleReset} style={{ marginTop: Spacing.md }} />
              <TouchableOpacity style={styles.backLink} onPress={() => router.replace('/login')}>
                <Text style={styles.backLinkText}>Back to Login</Text>
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
  scroll: { flexGrow: 1, padding: Spacing.xxl },
  logo: { fontFamily: Platform.OS === 'ios' ? 'Georgia' : 'serif', fontSize: 22, fontWeight: FontWeight.heavy, fontStyle: 'italic', color: Colors.accent, textAlign: 'center', marginBottom: Spacing.xs },
  title: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, textAlign: 'center', marginBottom: Spacing.xxl },
  desc: { fontSize: FontSize.md, color: Colors.textSecondary, textAlign: 'center', marginBottom: Spacing.xl },
  errorBanner: { backgroundColor: Colors.errorLight, borderRadius: Radius.md, padding: Spacing.lg, marginBottom: Spacing.xl, borderLeftWidth: 3, borderLeftColor: Colors.error },
  errorText: { color: Colors.error, fontSize: FontSize.sm, fontWeight: FontWeight.semibold },
  successBox: { alignItems: 'center', paddingVertical: Spacing.xxxl },
  successIcon: { fontSize: 60, marginBottom: Spacing.xl },
  successMsg: { color: Colors.textSecondary, fontSize: FontSize.md, textAlign: 'center', lineHeight: 22 },
  backLink: { marginTop: Spacing.xl, alignItems: 'center' },
  backLinkText: { color: Colors.textMuted, fontSize: FontSize.md },
});
