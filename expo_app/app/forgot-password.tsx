import React, { useState, useEffect, useRef } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, Platform, KeyboardAvoidingView } from 'react-native';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function ForgotPasswordScreen() {
  const [email, setEmail] = useState('');
  const [otp, setOtp] = useState('');
  const [submitted, setSubmitted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [verifying, setVerifying] = useState(false);
  const [error, setError] = useState('');
  const [devOtp, setDevOtp] = useState('');
  const [retryAfter, setRetryAfter] = useState<number | null>(null);
  const [timeLeft, setTimeLeft] = useState('');
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    if (!retryAfter) { setTimeLeft(''); return; }
    timerRef.current = setInterval(() => {
      const dist = retryAfter - Date.now();
      if (dist <= 0) { setRetryAfter(null); setTimeLeft(''); clearInterval(timerRef.current!); }
      else {
        const m = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((dist % (1000 * 60)) / 1000);
        setTimeLeft(`${m}:${s < 10 ? '0' : ''}${s}`);
      }
    }, 1000);
    return () => clearInterval(timerRef.current!);
  }, [retryAfter]);

  const handleRequestOtp = async () => {
    setError('');
    const norm = email.trim().toLowerCase();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(norm)) { setError('Please enter a valid email address.'); return; }
    if (retryAfter) { setError(`Too many requests. Try again in ${timeLeft}`); return; }
    setLoading(true);
    try {
      const res = await api.post('/auth/forgot-password', { email: norm });
      setDevOtp(res.data?.devOtp || '');
      setSubmitted(true);
    } catch (err: any) {
      if (err.response?.status === 429) {
        setRetryAfter(new Date(err.response.data.retryAfter).getTime());
        setError('Too many requests for this email.');
      } else {
        setError(getApiErrorMessage(err, 'Unable to send reset code right now.'));
      }
    } finally { setLoading(false); }
  };

  const handleVerifyOtp = async () => {
    setError('');
    if (otp.length !== 6) { setError('Please enter the 6-digit code.'); return; }
    setVerifying(true);
    try {
      const res = await api.post('/auth/verify-otp', { email: email.trim().toLowerCase(), otp });
      await AsyncStorage.setItem('reset_token', res.data.resetToken);
      await AsyncStorage.setItem('reset_email', email.trim().toLowerCase());
      router.push('/reset-password' as any);
    } catch (err: any) {
      setError(getApiErrorMessage(err, 'Invalid or expired OTP. Please try again.'));
    } finally { setVerifying(false); }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <TouchableOpacity onPress={() => router.back()}>
            <Text style={styles.logo}>LumbaRong</Text>
          </TouchableOpacity>
          <Text style={styles.subtitle}>Password Recovery</Text>

          {!!error && <View style={styles.errorBanner}><Text style={styles.errorText}>{error}</Text></View>}

          {!submitted ? (
            <View>
              <Text style={styles.heading}>Forgot your password?</Text>
              <Text style={styles.desc}>Enter your account email and we'll send you a 6-digit code.</Text>
              <Input label="Email Address" value={email} onChangeText={setEmail} placeholder="you@example.com" autoCapitalize="none" keyboardType="email-address" />
              <Button label="Send Verification Code →" variant="primary" size="lg" fullWidth loading={loading} onPress={handleRequestOtp} style={{ marginTop: Spacing.md }} />
              <TouchableOpacity style={styles.backLink} onPress={() => router.push('/login' as any)}>
                <Text style={styles.backLinkText}>Remembered it? <Text style={{ color: Colors.primary }}>Back to Login</Text></Text>
              </TouchableOpacity>
            </View>
          ) : (
            <View>
              <Text style={styles.heading}>Enter Verification Code</Text>
              <Text style={styles.desc}>We sent a 6-digit code to <Text style={styles.emailHighlight}>{email}</Text></Text>
              {!!devOtp && (
                <View style={styles.devBanner}>
                  <Text style={styles.devText}>Dev OTP: <Text style={styles.devOtp}>{devOtp}</Text></Text>
                </View>
              )}
              <Input label="6-Digit OTP" value={otp} onChangeText={(v) => { const n = v.replace(/\D/g, ''); if (n.length <= 6) setOtp(n); }} placeholder="000000" keyboardType="numeric" maxLength={6} />
              <Button label="Verify Code →" variant="primary" size="lg" fullWidth loading={verifying} onPress={handleVerifyOtp} style={{ marginTop: Spacing.md }} />
              <TouchableOpacity style={styles.backLink} onPress={() => { setSubmitted(false); setOtp(''); setError(''); }}>
                <Text style={styles.backLinkText}>Didn't receive a code? <Text style={{ color: Colors.primary }}>Try again</Text></Text>
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
  scroll: { flexGrow: 1, padding: Spacing.xxl, paddingTop: Spacing.xl },
  logo: { fontFamily: Platform.OS === 'ios' ? 'Georgia' : 'serif', fontSize: 22, fontWeight: FontWeight.heavy, fontStyle: 'italic', color: Colors.accent, textAlign: 'center', marginBottom: Spacing.xs },
  subtitle: { fontSize: FontSize.sm, color: Colors.textMuted, textAlign: 'center', marginBottom: Spacing.xxxl, letterSpacing: 1.5, textTransform: 'uppercase' },
  heading: { fontSize: FontSize.xl, fontWeight: FontWeight.bold, color: Colors.textPrimary, textAlign: 'center', marginBottom: Spacing.md },
  desc: { fontSize: FontSize.md, color: Colors.textSecondary, textAlign: 'center', lineHeight: 22, marginBottom: Spacing.xxl },
  emailHighlight: { fontWeight: FontWeight.bold, color: Colors.textPrimary },
  errorBanner: { backgroundColor: Colors.errorLight, borderRadius: Radius.md, padding: Spacing.lg, marginBottom: Spacing.xl, borderLeftWidth: 3, borderLeftColor: Colors.error },
  errorText: { color: Colors.error, fontSize: FontSize.sm, fontWeight: FontWeight.semibold },
  devBanner: { backgroundColor: Colors.infoLight, borderRadius: Radius.md, padding: Spacing.md, marginBottom: Spacing.lg },
  devText: { color: Colors.info, fontSize: FontSize.sm },
  devOtp: { fontWeight: FontWeight.heavy, color: Colors.primary, fontSize: FontSize.lg, letterSpacing: 4 },
  backLink: { marginTop: Spacing.xl, alignItems: 'center' },
  backLinkText: { color: Colors.textMuted, fontSize: FontSize.sm },
});
