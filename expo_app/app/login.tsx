/**
 * Login Screen — Full clone of web login page with:
 * - Role-aware auth (token stored per role)
 * - Account status error messages (blocked/frozen/rejected/pending)
 * - Lockout display
 * - Remember Me
 * - Link to Register & Forgot Password
 */
import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  SafeAreaView,
  ScrollView,
  TouchableOpacity,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { router } from 'expo-router';
import { useAuthStore, AuthUser } from '@/store/authStore';
import { api, getApiErrorMessage, UserRole } from '@/lib/api';
import { validateEmail, validatePassword } from '@/lib/validation';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface StatusError {
  status: 'blocked' | 'frozen' | 'rejected' | 'pending' | 'locked';
  message: string;
  reason?: string;
}

const STATUS_MESSAGES: Record<string, string> = {
  blocked: 'Account Terminated',
  frozen: 'Account Frozen',
  rejected: 'Application Rejected',
  pending: 'Account Pending Approval',
  locked: 'Account Temporarily Locked',
};

export default function LoginScreen() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [statusError, setStatusError] = useState<StatusError | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{ email?: string; password?: string }>({});

  const BACKEND_IP = '192.168.100.5';
  const BACKEND_PORT = '5000';

  const { login, setBackendConfig } = useAuthStore();

  const validate = (): boolean => {
    const errors: typeof fieldErrors = {};
    try { validateEmail(email); } catch (e: any) { errors.email = e.message; }
    try { validatePassword(password); } catch (e: any) { errors.password = e.message; }
    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleLogin = async () => {
    setStatusError(null);
    if (!validate()) return;

    setLoading(true);
    try {
      await setBackendConfig(BACKEND_IP, BACKEND_PORT);
      const res = await api.post('/auth/login', {
        email: email.trim().toLowerCase(),
        password,
      });

      const { token, user } = res.data as { token: string; user: AuthUser & { role: UserRole } };

      await login(user.role, token, user);

      // Google OAuth users must set a password first
      if (user.hasPasswordSet === false) {
        router.replace('/set-password' as any);
        return;
      }

      // Redirect based on role
      if (user.role === 'admin')       router.replace('/(admin)/dashboard' as any);
      else if (user.role === 'seller') router.replace('/(seller)/dashboard' as any);
      else                             router.replace('/(customer)/home' as any);

    } catch (err: any) {
      const data = err?.response?.data;

      if (data?.status) {
        setStatusError({
          status: data.status,
          message: data.message || STATUS_MESSAGES[data.status] || 'Access denied.',
          reason: data.reason,
        });
        return;
      }

      const msg = getApiErrorMessage(err, 'Login failed. Please try again.');
      Alert.alert('Login Failed', msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <ScrollView
          contentContainerStyle={styles.scroll}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* Logo */}
          <TouchableOpacity onPress={() => router.back()}>
            <Text style={styles.logo}>LumbaRong</Text>
          </TouchableOpacity>
          <Text style={styles.subtitle}>Sign in to your account</Text>

          {/* Status Error Banner */}
          {statusError && (
            <View style={styles.statusBanner}>
              <Text style={styles.statusTitle}>{STATUS_MESSAGES[statusError.status]}</Text>
              <Text style={styles.statusMsg}>{statusError.message}</Text>
              {statusError.reason && (
                <Text style={styles.statusReason}>Reason: {statusError.reason}</Text>
              )}
            </View>
          )}


          {/* Credentials */}
          <Input
            label="Email Address"
            value={email}
            onChangeText={setEmail}
            placeholder="you@example.com"
            autoCapitalize="none"
            keyboardType="email-address"
            autoComplete="email"
            error={fieldErrors.email}
          />
          <Input
            label="Password"
            value={password}
            onChangeText={setPassword}
            placeholder="Enter your password"
            secureTextEntry
            autoComplete="password"
            error={fieldErrors.password}
          />

          {/* Forgot password */}
          <TouchableOpacity
            style={styles.forgotLink}
            onPress={() => router.push('/forgot-password' as any)}
          >
            <Text style={styles.forgotText}>Forgot password?</Text>
          </TouchableOpacity>

          {/* Submit */}
          <Button
            label="Sign In"
            variant="primary"
            size="lg"
            fullWidth
            loading={loading}
            onPress={handleLogin}
            style={styles.submitBtn}
          />

          {/* Register link */}
          <View style={styles.registerRow}>
            <Text style={styles.registerPrompt}>Don't have an account? </Text>
            <TouchableOpacity onPress={() => router.push('/register')}>
              <Text style={styles.registerLink}>Create one</Text>
            </TouchableOpacity>
          </View>

          <TouchableOpacity
            style={styles.backBtn}
            onPress={() => router.replace('/')}
          >
            <Text style={styles.backText}>← Back to Home</Text>
          </TouchableOpacity>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: {
    flexGrow: 1,
    padding: Spacing.xxl,
    justifyContent: 'center',
  },
  logo: {
    fontFamily: Platform.OS === 'ios' ? 'Georgia' : 'serif',
    fontSize: 26,
    fontWeight: FontWeight.heavy,
    fontStyle: 'italic',
    color: Colors.accent,
    textAlign: 'center',
    marginBottom: Spacing.xs,
  },
  subtitle: {
    fontSize: FontSize.md,
    color: Colors.accentMuted,
    textAlign: 'center',
    marginBottom: Spacing.xxxl,
  },
  statusBanner: {
    backgroundColor: Colors.errorLight,
    borderRadius: Radius.md,
    padding: Spacing.lg,
    marginBottom: Spacing.xl,
    borderLeftWidth: 3,
    borderLeftColor: Colors.error,
  },
  statusTitle: {
    color: Colors.error,
    fontWeight: FontWeight.bold,
    fontSize: FontSize.md,
    marginBottom: Spacing.xs,
  },
  statusMsg: {
    color: Colors.textPrimary,
    fontSize: FontSize.sm,
  },
  statusReason: {
    color: Colors.textSecondary,
    fontSize: FontSize.xs,
    marginTop: Spacing.xs,
    fontStyle: 'italic',
  },
  row: { flexDirection: 'row', alignItems: 'flex-start' },
  forgotLink: { alignSelf: 'flex-end', marginBottom: Spacing.xl },
  forgotText: { color: Colors.accent, fontSize: FontSize.sm },
  submitBtn: { marginBottom: Spacing.xl },
  registerRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginBottom: Spacing.xl,
  },
  registerPrompt: { color: Colors.textMuted, fontSize: FontSize.md },
  registerLink: {
    color: Colors.accent,
    fontSize: FontSize.md,
    fontWeight: FontWeight.bold,
  },
  backBtn: { alignItems: 'center' },
  backText: { color: Colors.textMuted, fontSize: FontSize.sm },
});
