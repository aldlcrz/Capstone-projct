/**
 * Terms of Service — LumBarong mobile app
 */
import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity } from 'react-native';
import { router } from 'expo-router';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

const TERMS = [
  { title: '1. Acceptance of Terms', body: 'By creating an account or using the LumBarong platform, you agree to be bound by these Terms of Service. If you do not agree, please discontinue use of the app immediately.' },
  { title: '2. User Accounts', body: 'You must provide accurate registration information. You are responsible for maintaining the confidentiality of your account credentials. Each person may only maintain one account. LumBarong reserves the right to suspend accounts that violate these terms.' },
  { title: '3. Buyer Responsibilities', body: 'As a buyer, you agree to:\n• Provide accurate shipping and payment information\n• Pay for all orders you place\n• Communicate respectfully with sellers\n• Not file fraudulent refund or dispute claims\n• Not misuse the report system' },
  { title: '4. Seller Responsibilities', body: 'As a seller, you agree to:\n• Only sell authentic, handcrafted Barong Tagalog products\n• Provide accurate product descriptions and images\n• Fulfill orders within the stated timeframe\n• Respond to customer messages within 48 hours\n• Comply with all platform policies and Philippine consumer laws' },
  { title: '5. Prohibited Activities', body: 'The following are strictly prohibited:\n• Selling counterfeit or non-handcrafted products\n• Fraud, scamming, or deceptive practices\n• Harassment or abuse of other users\n• Manipulation of reviews or ratings\n• Unauthorized access to other accounts\n• Circumventing platform fees or communication channels' },
  { title: '6. Payments & Fees', body: 'All transactions are conducted between buyers and sellers. LumBarong does not process payments directly — buyers transfer payment (GCash/Maya) directly to the seller. LumBarong is not liable for failed transactions or disputes arising from direct payment.' },
  { title: '7. Refunds & Disputes', body: 'Refund requests must be submitted within 7 days of order delivery. Sellers have 3 business days to respond. LumBarong admin may intervene in unresolved disputes at their discretion. Approved refunds are processed by the seller directly.' },
  { title: '8. Content & Intellectual Property', body: 'All product images, descriptions, and content uploaded to LumBarong remain the intellectual property of the original creators. You grant LumBarong a non-exclusive license to display your content on the platform for the purposes of facilitating sales.' },
  { title: '9. Limitation of Liability', body: 'LumBarong is a marketplace platform and is not responsible for:\n• Quality disputes between buyers and sellers\n• Losses arising from failed direct payments\n• Delays caused by shipping carriers\n• Force majeure events beyond our control' },
  { title: '10. Termination', body: 'LumBarong reserves the right to suspend or terminate any account at any time for violation of these terms. Users may delete their accounts at any time through the profile settings.' },
  { title: '11. Governing Law', body: 'These Terms are governed by the laws of the Republic of the Philippines. Disputes shall be resolved under the jurisdiction of the appropriate Philippine courts.' },
];

export default function TermsScreen() {
  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>

        <View style={styles.header}>
          <Text style={styles.emoji}>📋</Text>
          <Text style={styles.title}>Terms of Service</Text>
          <Text style={styles.updated}>Effective Date: April 2025</Text>
          <Text style={styles.intro}>
            Please read these terms carefully before using the LumBarong platform.
            Using LumBarong means you agree to these terms.
          </Text>
        </View>

        {TERMS.map((t, i) => (
          <View key={i} style={styles.section}>
            <Text style={styles.sectionTitle}>{t.title}</Text>
            <Text style={styles.sectionBody}>{t.body}</Text>
          </View>
        ))}

        <View style={styles.footer}>
          <Text style={styles.footerText}>
            Questions? Contact us at{' '}
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
  sectionBody: { fontSize: FontSize.sm, color: Colors.textSecondary, lineHeight: 20 },
  footer: {
    margin: Spacing.xl, padding: Spacing.xl,
    backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    alignItems: 'center', borderWidth: 1, borderColor: Colors.border,
  },
  footerText: { fontSize: FontSize.sm, color: Colors.textMuted, textAlign: 'center', lineHeight: 20 },
  email: { color: Colors.accent, fontWeight: FontWeight.bold },
});
