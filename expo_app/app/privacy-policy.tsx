/**
 * Privacy Policy — LumBarong mobile app
 */
import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity } from 'react-native';
import { router } from 'expo-router';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

const SECTIONS = [
  {
    title: '1. Information We Collect',
    body: `We collect the following information when you use LumBarong:\n\n• Account information (name, email, password)\n• Profile information (phone number, address, profile photo)\n• Order and transaction data\n• Device information and usage analytics\n• Location data (only when you choose to pin a delivery address)\n• Uploaded files (product images, payment proofs, seller documents)`,
  },
  {
    title: '2. How We Use Your Information',
    body: `Your information is used to:\n\n• Process orders and payments\n• Communicate order updates and notifications\n• Verify seller identity and credentials\n• Improve platform features and performance\n• Prevent fraud and ensure platform security\n• Send promotional content (only with your consent)`,
  },
  {
    title: '3. Data Sharing',
    body: `We do not sell your personal data. Your information may be shared with:\n\n• Sellers (limited to order fulfillment details)\n• Payment processors for transaction verification\n• Law enforcement when legally required\n\nAll sharing is governed by strict confidentiality agreements.`,
  },
  {
    title: '4. Data Retention',
    body: `We retain your data for as long as your account is active. You may request account deletion at any time. Transactional records may be retained for up to 7 years for legal compliance.`,
  },
  {
    title: '5. Your Rights',
    body: `You have the right to:\n\n• Access and download your personal data\n• Correct inaccurate information\n• Request deletion of your account and data\n• Withdraw consent for marketing communications\n\nContact support@lumbarong.com to exercise these rights.`,
  },
  {
    title: '6. Security',
    body: `We implement industry-standard security measures including:\n\n• Encrypted data transmission (HTTPS/TLS)\n• Hashed passwords (bcrypt)\n• JWT-based session management\n• Regular security audits\n\nNo system is 100% secure, but we take every reasonable precaution.`,
  },
  {
    title: '7. Children\'s Privacy',
    body: `LumBarong is not intended for users under 13 years of age. We do not knowingly collect information from children. If you believe a child has registered, contact us immediately.`,
  },
  {
    title: '8. Changes to This Policy',
    body: `We may update this Privacy Policy from time to time. Significant changes will be communicated via in-app notification. Continued use of the platform after changes constitutes acceptance.`,
  },
];

export default function PrivacyPolicyScreen() {
  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>

        <View style={styles.header}>
          <Text style={styles.emoji}>🔒</Text>
          <Text style={styles.title}>Privacy Policy</Text>
          <Text style={styles.updated}>Last updated: April 2025</Text>
          <Text style={styles.intro}>
            LumBarong respects your privacy. This policy explains how we collect,
            use, and protect your personal information.
          </Text>
        </View>

        {SECTIONS.map((s, i) => (
          <View key={i} style={styles.section}>
            <Text style={styles.sectionTitle}>{s.title}</Text>
            <Text style={styles.sectionBody}>{s.body}</Text>
          </View>
        ))}

        <View style={styles.footer}>
          <Text style={styles.footerText}>
            For privacy concerns, email us at{' '}
            <Text style={styles.email}>support@lumbarong.com</Text>
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { paddingBottom: 60 },
  backBtn: { padding: Spacing.xl },
  backText: { color: Colors.accent, fontSize: FontSize.md, fontWeight: FontWeight.semibold },
  header: {
    alignItems: 'center', paddingHorizontal: Spacing.xxl,
    paddingBottom: Spacing.xxxl, borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  emoji: { fontSize: 48, marginBottom: Spacing.md },
  title: {
    fontSize: FontSize.title, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: Spacing.xs,
  },
  updated: { fontSize: FontSize.xs, color: Colors.textMuted, marginBottom: Spacing.lg },
  intro: {
    fontSize: FontSize.md, color: Colors.textSecondary,
    textAlign: 'center', lineHeight: 22,
  },
  section: {
    marginHorizontal: Spacing.xl, marginTop: Spacing.xl,
    backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.xxl, borderWidth: 1, borderColor: Colors.border,
  },
  sectionTitle: {
    fontSize: FontSize.md, fontWeight: FontWeight.bold,
    color: Colors.primary, marginBottom: Spacing.md,
  },
  sectionBody: {
    fontSize: FontSize.sm, color: Colors.textSecondary,
    lineHeight: 20,
  },
  footer: {
    margin: Spacing.xl, padding: Spacing.xl,
    backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    alignItems: 'center', borderWidth: 1, borderColor: Colors.border,
  },
  footerText: { fontSize: FontSize.sm, color: Colors.textMuted, textAlign: 'center', lineHeight: 20 },
  email: { color: Colors.accent, fontWeight: FontWeight.bold },
});
