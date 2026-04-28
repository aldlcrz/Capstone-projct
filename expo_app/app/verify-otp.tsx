/**
 * Verify OTP — Standalone screen (also used as part of forgot-password flow)
 * Can be called with params: email, purpose (forgot-password | register | etc.)
 */
import React, { useState, useEffect, useRef } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, TextInput, Platform, Alert, KeyboardAvoidingView,
} from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { Button } from '@/components/ui/Button';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

export default function VerifyOtpScreen() {
  const { email, purpose } = useLocalSearchParams<{ email: string; purpose: string }>();
  const [otp, setOtp] = useState('');
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [error, setError] = useState('');
  const [countdown, setCountdown] = useState(0);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const startCountdown = () => {
    setCountdown(60);
    timerRef.current = setInterval(() => {
      setCountdown(prev => {
        if (prev <= 1) { clearInterval(timerRef.current!); return 0; }
        return prev - 1;
      });
    }, 1000);
  };

  useEffect(() => { startCountdown(); return () => clearInterval(timerRef.current!); }, []);

  const handleVerify = async () => {
    setError('');
    if (otp.length !== 6) { setError('Please enter the 6-digit code.'); return; }
    setLoading(true);
    try {
      const res = await api.post('/auth/verify-otp', { email: email?.trim(), otp });
      if (purpose === 'forgot-password' || !purpose) {
        await AsyncStorage.setItem('reset_token', res.data.resetToken);
        await AsyncStorage.setItem('reset_email', email?.trim() || '');
        router.push('/reset-password' as any);
      } else {
        router.push('/login' as any);
      }
    } catch (err: any) {
      setError(getApiErrorMessage(err, 'Invalid or expired OTP.'));
    } finally { setLoading(false); }
  };

  const handleResend = async () => {
    if (countdown > 0) return;
    setResending(true);
    try {
      await api.post('/auth/forgot-password', { email: email?.trim() });
      startCountdown();
      setOtp('');
      setError('');
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to resend OTP.'));
    } finally { setResending(false); }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <TouchableOpacity onPress={() => router.back()}>
            <Text style={styles.logo}>LumbaRong</Text>
          </TouchableOpacity>

          <View style={styles.iconWrap}>
            <Text style={styles.icon}>📬</Text>
          </View>

          <Text style={styles.title}>Check Your Email</Text>
          <Text style={styles.subtitle}>
            We sent a 6-digit verification code to{'\n'}
            <Text style={styles.email}>{email}</Text>
          </Text>

          {!!error && (
            <View style={styles.errorBanner}>
              <Text style={styles.errorText}>{error}</Text>
            </View>
          )}

          {/* OTP Input */}
          <TextInput
            style={styles.otpInput}
            value={otp}
            onChangeText={v => { const n = v.replace(/\D/g, ''); if (n.length <= 6) setOtp(n); }}
            placeholder="000000"
            placeholderTextColor={Colors.textMuted}
            keyboardType="numeric"
            maxLength={6}
            textAlign="center"
          />

          <Button
            label="Verify Code →"
            variant="primary"
            size="lg"
            fullWidth
            loading={loading}
            onPress={handleVerify}
            style={{ marginTop: Spacing.xl }}
          />

          <TouchableOpacity
            style={[styles.resendBtn, countdown > 0 && styles.resendBtnDisabled]}
            onPress={handleResend}
            disabled={countdown > 0 || resending}
          >
            <Text style={[styles.resendText, countdown > 0 && styles.resendTextDisabled]}>
              {countdown > 0 ? `Resend code in ${countdown}s` : "Didn't receive it? Resend →"}
            </Text>
          </TouchableOpacity>
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
    fontSize: 22, fontWeight: FontWeight.heavy, fontStyle: 'italic',
    color: Colors.accent, textAlign: 'center', marginBottom: Spacing.xxxl,
    alignSelf: 'center',
  },
  iconWrap: {
    width: 80, height: 80, borderRadius: 24,
    backgroundColor: `${Colors.primary}22`,
    justifyContent: 'center', alignItems: 'center', marginBottom: Spacing.xl,
  },
  icon: { fontSize: 36 },
  title: {
    fontSize: FontSize.xxl, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, textAlign: 'center', marginBottom: Spacing.sm,
  },
  subtitle: {
    fontSize: FontSize.md, color: Colors.textSecondary,
    textAlign: 'center', lineHeight: 22, marginBottom: Spacing.xxl,
  },
  email: { fontWeight: FontWeight.bold, color: Colors.primary },
  errorBanner: {
    backgroundColor: Colors.errorLight, borderRadius: Radius.md,
    padding: Spacing.lg, marginBottom: Spacing.xl,
    borderLeftWidth: 3, borderLeftColor: Colors.error, width: '100%',
  },
  errorText: { color: Colors.error, fontSize: FontSize.sm, fontWeight: FontWeight.semibold },
  otpInput: {
    width: '100%', height: 70, backgroundColor: Colors.bgDark,
    borderRadius: Radius.lg, fontSize: 32, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, letterSpacing: 12,
    borderWidth: 1.5, borderColor: Colors.border,
    marginTop: Spacing.xl,
  },
  resendBtn: { marginTop: Spacing.xxl, padding: Spacing.md },
  resendBtnDisabled: { opacity: 0.5 },
  resendText: { color: Colors.accent, fontSize: FontSize.sm, fontWeight: FontWeight.semibold, textAlign: 'center' },
  resendTextDisabled: { color: Colors.textMuted },
});
