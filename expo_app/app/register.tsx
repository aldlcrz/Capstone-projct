/**
 * Customer Registration — Full clone of web register page.
 * Step 1: Name / Email / Password / Confirm
 * Step 2: Role selection (Customer | Seller) + seller fields
 * Step 3: Success screen
 * Seller fields: mobile, gcashNumber, indigencyCertificate, validId, gcashQrCode
 */
import React, { useState } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, Alert, Platform, KeyboardAvoidingView,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';
import { validatePersonName, validateEmail, validatePassword, validatePhilippineMobileNumber } from '@/lib/validation';

type Role = 'customer' | 'seller';

interface SellerData {
  mobileNumber: string;
  gcashNumber: string;
  isAdult: boolean;
}

interface FileDoc {
  uri: string;
  name: string;
  type: string;
}

const STEPS = ['Account Info', 'Platform Intent', 'Done'];

export default function RegisterScreen() {
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Step 1 fields
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  // Step 2 fields
  const [role, setRole] = useState<Role>('customer');
  const [sellerData, setSellerData] = useState<SellerData>({ mobileNumber: '', gcashNumber: '', isAdult: false });
  const [certificate, setCertificate] = useState<FileDoc | null>(null);
  const [validId, setValidId] = useState<FileDoc | null>(null);
  const [gcashQr, setGcashQr] = useState<FileDoc | null>(null);

  const pickImage = async (setter: (f: FileDoc | null) => void, label: string) => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permission required', `Allow access to your media library to upload ${label}.`);
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      quality: 0.8,
      allowsEditing: false,
    });
    if (!result.canceled && result.assets[0]) {
      const asset = result.assets[0];
      const name = asset.uri.split('/').pop() || 'image.jpg';
      const type = asset.mimeType || 'image/jpeg';
      setter({ uri: asset.uri, name, type });
    }
  };

  const validateStep1 = (): boolean => {
    try { validatePersonName(name, 'Registry Name'); } catch (e: any) { setError(e.message); return false; }
    try { validateEmail(email); } catch (e: any) { setError(e.message); return false; }
    try { validatePassword(password); } catch (e: any) { setError(e.message); return false; }
    if (password !== confirmPassword) { setError('Passwords do not match.'); return false; }
    return true;
  };

  const handleNext = () => {
    setError('');
    if (validateStep1()) setStep(2);
  };

  const handleSubmit = async () => {
    setError('');
    if (role === 'seller') {
      if (!certificate || !validId || !gcashQr || !sellerData.mobileNumber || !sellerData.gcashNumber) {
        setError('All seller verification fields are required.'); return;
      }
      try { validatePhilippineMobileNumber(sellerData.mobileNumber, 'Mobile number'); } catch (e: any) { setError(e.message); return; }
      try { validatePhilippineMobileNumber(sellerData.gcashNumber, 'GCash number'); } catch (e: any) { setError(e.message); return; }
    }

    setLoading(true);
    try {
      const formData = new FormData();
      formData.append('name', name.trim());
      formData.append('email', email.trim().toLowerCase());
      formData.append('password', password);
      formData.append('role', role);

      if (role === 'seller') {
        formData.append('mobileNumber', sellerData.mobileNumber);
        formData.append('gcashNumber', sellerData.gcashNumber);
        formData.append('isAdult', String(sellerData.isAdult));
        formData.append('indigencyCertificate', { uri: certificate!.uri, name: certificate!.name, type: certificate!.type } as any);
        formData.append('validId', { uri: validId!.uri, name: validId!.name, type: validId!.type } as any);
        formData.append('gcashQrCode', { uri: gcashQr!.uri, name: gcashQr!.name, type: gcashQr!.type } as any);
      }

      await api.post('/auth/register', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
      setStep(3);
    } catch (err: any) {
      setError(getApiErrorMessage(err, 'Registration failed.'));
    } finally {
      setLoading(false);
    }
  };

  // ─── Progress bar ───────────────────────────────────────
  const StepIndicator = () => (
    <View style={styles.stepRow}>
      {[1, 2].map((s) => (
        <View key={s} style={styles.stepItem}>
          <View style={[styles.stepCircle, step >= s && styles.stepCircleActive]}>
            <Text style={[styles.stepNum, step >= s && styles.stepNumActive]}>{s}</Text>
          </View>
          {s < 2 && <View style={[styles.stepLine, step > s && styles.stepLineActive]} />}
        </View>
      ))}
    </View>
  );

  // ─── Document upload button ──────────────────────────────
  const DocUploadBtn = ({ label, file, onPress }: { label: string; file: FileDoc | null; onPress: () => void }) => (
    <TouchableOpacity style={[styles.docBtn, file && styles.docBtnActive]} onPress={onPress}>
      <Text style={styles.docIcon}>{file ? '✓' : '↑'}</Text>
      <Text style={[styles.docLabel, file && styles.docLabelActive]} numberOfLines={2}>
        {file ? file.name : label}
      </Text>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

          {/* Logo */}
          <TouchableOpacity onPress={() => router.back()}>
            <Text style={styles.logo}>LumbaRong</Text>
          </TouchableOpacity>
          <Text style={styles.pageTitle}>
            {step === 1 ? 'Create Account' : step === 2 ? 'Platform Intent' : 'Certified!'}
          </Text>

          {step < 3 && <StepIndicator />}

          {/* Error Banner */}
          {!!error && (
            <View style={styles.errorBanner}>
              <Text style={styles.errorText}>{error}</Text>
            </View>
          )}

          {/* ─── STEP 1 ─────────────────────────────── */}
          {step === 1 && (
            <View>
              <Input label="Registry Name" value={name} onChangeText={setName} placeholder="Your full name" />
              <Input label="Secure Email" value={email} onChangeText={setEmail} placeholder="you@example.com" autoCapitalize="none" keyboardType="email-address" />
              <Input label="Platform Password" value={password} onChangeText={setPassword} placeholder="••••••••" secureTextEntry />
              <Input label="Confirm Password" value={confirmPassword} onChangeText={setConfirmPassword} placeholder="••••••••" secureTextEntry />
              <Button label="Continue →" variant="primary" size="lg" fullWidth onPress={handleNext} style={{ marginTop: Spacing.md }} />
            </View>
          )}

          {/* ─── STEP 2 ─────────────────────────────── */}
          {step === 2 && (
            <View>
              <Text style={styles.sectionLabel}>Select Your Role</Text>
              <View style={styles.roleRow}>
                {(['customer', 'seller'] as Role[]).map((r) => (
                  <TouchableOpacity key={r} style={[styles.roleCard, role === r && styles.roleCardActive]} onPress={() => setRole(r)}>
                    <Text style={styles.roleIcon}>{r === 'customer' ? '👤' : '🛍️'}</Text>
                    <Text style={[styles.roleLabel, role === r && styles.roleLabelActive]}>
                      {r === 'customer' ? 'Customer' : 'Seller'}
                    </Text>
                    {role === r && <Text style={styles.checkMark}>✓</Text>}
                  </TouchableOpacity>
                ))}
              </View>

              {/* Seller extra fields */}
              {role === 'seller' && (
                <View style={styles.sellerSection}>
                  <Text style={styles.sectionLabel}>Seller Information</Text>
                  <View style={styles.twoCol}>
                    <View style={{ flex: 1 }}>
                      <Input label="Mobile Number" value={sellerData.mobileNumber} onChangeText={(v) => setSellerData({ ...sellerData, mobileNumber: v })} placeholder="09XXXXXXXXX" keyboardType="numeric" />
                    </View>
                    <View style={{ width: Spacing.md }} />
                    <View style={{ flex: 1 }}>
                      <Input label="GCash Number" value={sellerData.gcashNumber} onChangeText={(v) => setSellerData({ ...sellerData, gcashNumber: v })} placeholder="09XXXXXXXXX" keyboardType="numeric" />
                    </View>
                  </View>

                  <Text style={styles.sectionLabel}>Required Documents</Text>
                  <View style={styles.docsRow}>
                    <DocUploadBtn label="Indigency Certificate" file={certificate} onPress={() => pickImage(setCertificate, 'indigency certificate')} />
                    <DocUploadBtn label="Valid ID" file={validId} onPress={() => pickImage(setValidId, 'valid ID')} />
                    <DocUploadBtn label="GCash QR Code" file={gcashQr} onPress={() => pickImage(setGcashQr, 'GCash QR')} />
                  </View>

                  <TouchableOpacity style={styles.adultCheck} onPress={() => setSellerData({ ...sellerData, isAdult: !sellerData.isAdult })}>
                    <View style={[styles.checkbox, sellerData.isAdult && styles.checkboxActive]}>
                      {sellerData.isAdult && <Text style={styles.checkboxTick}>✓</Text>}
                    </View>
                    <Text style={styles.adultLabel}>I confirm I am 18 years or older</Text>
                  </TouchableOpacity>
                </View>
              )}

              <View style={styles.stepBtns}>
                <Button label="Back" variant="outline" size="md" style={{ flex: 1 }} onPress={() => setStep(1)} />
                <View style={{ width: Spacing.md }} />
                <Button label="Sign Up" variant="primary" size="md" style={{ flex: 2 }} loading={loading} onPress={handleSubmit} />
              </View>
            </View>
          )}

          {/* ─── STEP 3 — SUCCESS ────────────────────── */}
          {step === 3 && (
            <View style={styles.successContainer}>
              <Text style={styles.successIcon}>✅</Text>
              <Text style={styles.successTitle}>Certified!</Text>
              <Text style={styles.successMsg}>
                {role === 'seller'
                  ? 'Your heritage application has been logged. Our curators will review your credentials within 24 hours.'
                  : 'Your account has been created! You may now sign in.'}
              </Text>
              <Button label="Begin Journey →" variant="primary" size="lg" fullWidth onPress={() => router.replace('/login')} style={{ marginTop: Spacing.xxxl }} />
            </View>
          )}

          {/* Login link */}
          {step < 3 && (
            <View style={styles.loginRow}>
              <Text style={styles.loginPrompt}>Already registered? </Text>
              <TouchableOpacity onPress={() => router.replace('/login')}>
                <Text style={styles.loginLink}>Sign In</Text>
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
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary, textAlign: 'center', marginBottom: Spacing.xl },
  stepRow: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', marginBottom: Spacing.xxl },
  stepItem: { flexDirection: 'row', alignItems: 'center' },
  stepCircle: { width: 32, height: 32, borderRadius: 16, backgroundColor: Colors.bgMedium, justifyContent: 'center', alignItems: 'center' },
  stepCircleActive: { backgroundColor: Colors.primary },
  stepNum: { color: Colors.textMuted, fontSize: FontSize.sm, fontWeight: FontWeight.bold },
  stepNumActive: { color: Colors.white },
  stepLine: { width: 48, height: 2, backgroundColor: Colors.border, marginHorizontal: Spacing.sm },
  stepLineActive: { backgroundColor: Colors.primary },
  errorBanner: { backgroundColor: Colors.errorLight, borderRadius: Radius.md, padding: Spacing.lg, marginBottom: Spacing.xl, borderLeftWidth: 3, borderLeftColor: Colors.error },
  errorText: { color: Colors.error, fontSize: FontSize.sm, fontWeight: FontWeight.semibold },
  sectionLabel: { color: Colors.textMuted, fontSize: FontSize.xs, fontWeight: FontWeight.bold, letterSpacing: 1.2, textTransform: 'uppercase', marginBottom: Spacing.md },
  roleRow: { flexDirection: 'row', gap: Spacing.md, marginBottom: Spacing.xl },
  roleCard: { flex: 1, backgroundColor: Colors.bgMedium, borderRadius: Radius.lg, padding: Spacing.xl, alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border },
  roleCardActive: { borderColor: Colors.primary, backgroundColor: Colors.bgDark },
  roleIcon: { fontSize: 28, marginBottom: Spacing.sm },
  roleLabel: { fontSize: FontSize.sm, fontWeight: FontWeight.bold, color: Colors.textMuted },
  roleLabelActive: { color: Colors.accent },
  checkMark: { position: 'absolute', top: 8, right: 8, color: Colors.primary, fontSize: FontSize.sm, fontWeight: FontWeight.bold },
  sellerSection: { marginBottom: Spacing.xl },
  twoCol: { flexDirection: 'row' },
  docsRow: { flexDirection: 'row', gap: Spacing.sm, marginBottom: Spacing.lg },
  docBtn: { flex: 1, backgroundColor: Colors.bgMedium, borderRadius: Radius.md, padding: Spacing.md, alignItems: 'center', borderWidth: 1.5, borderStyle: 'dashed', borderColor: Colors.border, minHeight: 80 },
  docBtnActive: { borderColor: Colors.primary, backgroundColor: Colors.errorLight },
  docIcon: { fontSize: 20, marginBottom: Spacing.xs },
  docLabel: { fontSize: FontSize.xs, color: Colors.textMuted, textAlign: 'center', lineHeight: 14 },
  docLabelActive: { color: Colors.primary, fontWeight: FontWeight.semibold },
  adultCheck: { flexDirection: 'row', alignItems: 'center', gap: Spacing.md, marginBottom: Spacing.xl },
  checkbox: { width: 20, height: 20, borderRadius: 4, borderWidth: 2, borderColor: Colors.border, justifyContent: 'center', alignItems: 'center' },
  checkboxActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  checkboxTick: { color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold },
  adultLabel: { color: Colors.textSecondary, fontSize: FontSize.sm, flex: 1 },
  stepBtns: { flexDirection: 'row', marginTop: Spacing.xl },
  successContainer: { alignItems: 'center', paddingVertical: Spacing.xxxl },
  successIcon: { fontSize: 60, marginBottom: Spacing.xl },
  successTitle: { fontSize: 28, fontWeight: FontWeight.heavy, color: Colors.accent, marginBottom: Spacing.lg },
  successMsg: { color: Colors.textSecondary, fontSize: FontSize.md, textAlign: 'center', lineHeight: 22 },
  loginRow: { flexDirection: 'row', justifyContent: 'center', marginTop: Spacing.xxxl },
  loginPrompt: { color: Colors.textMuted, fontSize: FontSize.md },
  loginLink: { color: Colors.primary, fontSize: FontSize.md, fontWeight: FontWeight.bold },
});
