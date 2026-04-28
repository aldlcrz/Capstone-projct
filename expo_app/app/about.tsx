/**
 * About LumBarong — Mission, team, contact info
 */
import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, Platform, Linking } from 'react-native';
import { router } from 'expo-router';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

const STATS = [
  { value: '500+', label: 'Artisan Products' },
  { value: '50+', label: 'Verified Sellers' },
  { value: '10K+', label: 'Happy Customers' },
  { value: '4.8★', label: 'Average Rating' },
];

const TEAM = [
  { name: 'LumBarong Platform', role: 'Capstone Project — BSIT', emoji: '🎓' },
  { name: 'Lumban, Laguna', role: 'Philippines', emoji: '📍' },
];

export default function AboutScreen() {
  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>

        {/* Hero */}
        <View style={styles.hero}>
          <Text style={styles.logo}>LumBarong</Text>
          <Text style={styles.heroTagline}>Preserving Philippine Heritage,{'\n'}One Barong at a Time.</Text>
        </View>

        {/* Mission */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Our Mission</Text>
          <Text style={styles.cardBody}>
            LumBarong is a digital marketplace connecting the master embroiderers of Lumban, Laguna
            with customers who value authentic, handcrafted Filipino heritage wear.{'\n\n'}
            We empower local artisans by providing a modern, secure platform to showcase and sell
            their handcrafted Barong Tagalog — preserving a 400-year-old tradition in the digital age.
          </Text>
        </View>

        {/* Stats */}
        <View style={styles.statsGrid}>
          {STATS.map((s, i) => (
            <View key={i} style={styles.statCard}>
              <Text style={styles.statValue}>{s.value}</Text>
              <Text style={styles.statLabel}>{s.label}</Text>
            </View>
          ))}
        </View>

        {/* Values */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Our Values</Text>
          {[
            { e: '🏆', t: 'Authenticity', d: 'Every seller is manually verified to ensure genuine handcrafted products.' },
            { e: '🤝', t: 'Fair Trade', d: 'We ensure artisans receive fair compensation for their craft.' },
            { e: '🔒', t: 'Trust & Safety', d: 'Secure payments, verified reviews, and buyer protection on every order.' },
            { e: '🌿', t: 'Heritage First', d: 'We prioritize preserving Filipino textile traditions for future generations.' },
          ].map((v, i) => (
            <View key={i} style={styles.valueItem}>
              <Text style={styles.valueEmoji}>{v.e}</Text>
              <View style={{ flex: 1 }}>
                <Text style={styles.valueTitle}>{v.t}</Text>
                <Text style={styles.valueDesc}>{v.d}</Text>
              </View>
            </View>
          ))}
        </View>

        {/* Info */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Platform Info</Text>
          {TEAM.map((t, i) => (
            <View key={i} style={styles.teamRow}>
              <Text style={styles.teamEmoji}>{t.emoji}</Text>
              <View>
                <Text style={styles.teamName}>{t.name}</Text>
                <Text style={styles.teamRole}>{t.role}</Text>
              </View>
            </View>
          ))}
        </View>

        {/* Contact */}
        <View style={styles.contactCard}>
          <Text style={styles.contactTitle}>Get in Touch</Text>
          <TouchableOpacity onPress={() => Linking.openURL('mailto:support@lumbarong.com')}>
            <Text style={styles.contactLink}>📧 support@lumbarong.com</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.footer}>
          <TouchableOpacity onPress={() => router.push('/privacy-policy' as any)}>
            <Text style={styles.footerLink}>Privacy Policy</Text>
          </TouchableOpacity>
          <Text style={styles.footerDot}>·</Text>
          <TouchableOpacity onPress={() => router.push('/terms' as any)}>
            <Text style={styles.footerLink}>Terms of Service</Text>
          </TouchableOpacity>
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
  hero: { alignItems: 'center', paddingVertical: Spacing.xxxl, paddingHorizontal: Spacing.xxl },
  logo: {
    fontFamily: Platform.OS === 'ios' ? 'Georgia' : 'serif',
    fontSize: 36, fontWeight: FontWeight.heavy, fontStyle: 'italic',
    color: Colors.accent, marginBottom: Spacing.lg,
  },
  heroTagline: {
    fontSize: FontSize.lg, color: Colors.textSecondary,
    textAlign: 'center', lineHeight: 26, fontStyle: 'italic',
  },
  card: {
    marginHorizontal: Spacing.xl, marginBottom: Spacing.xl,
    backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.xxl, borderWidth: 1, borderColor: Colors.border,
  },
  cardTitle: {
    fontSize: FontSize.lg, fontWeight: FontWeight.bold,
    color: Colors.primary, marginBottom: Spacing.lg,
  },
  cardBody: { fontSize: FontSize.md, color: Colors.textSecondary, lineHeight: 22 },
  statsGrid: {
    flexDirection: 'row', flexWrap: 'wrap',
    paddingHorizontal: Spacing.xl, gap: Spacing.md, marginBottom: Spacing.xl,
  },
  statCard: {
    width: '47%', backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.xl, alignItems: 'center',
    borderWidth: 1, borderColor: Colors.border,
  },
  statValue: {
    fontSize: FontSize.title, fontWeight: FontWeight.heavy,
    color: Colors.primary, marginBottom: 4,
  },
  statLabel: { fontSize: FontSize.xs, color: Colors.textMuted, fontWeight: FontWeight.semibold },
  valueItem: { flexDirection: 'row', gap: Spacing.md, marginBottom: Spacing.lg, alignItems: 'flex-start' },
  valueEmoji: { fontSize: 24, marginTop: 2 },
  valueTitle: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary, marginBottom: 4 },
  valueDesc: { fontSize: FontSize.sm, color: Colors.textSecondary, lineHeight: 18 },
  teamRow: { flexDirection: 'row', gap: Spacing.md, marginBottom: Spacing.md, alignItems: 'center' },
  teamEmoji: { fontSize: 28 },
  teamName: { fontSize: FontSize.md, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  teamRole: { fontSize: FontSize.sm, color: Colors.textMuted },
  contactCard: {
    marginHorizontal: Spacing.xl, marginBottom: Spacing.xl,
    backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.xl, alignItems: 'center',
    borderWidth: 1, borderColor: Colors.border,
  },
  contactTitle: {
    fontSize: FontSize.lg, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: Spacing.md,
  },
  contactLink: { fontSize: FontSize.md, color: Colors.accent, fontWeight: FontWeight.semibold },
  footer: { flexDirection: 'row', justifyContent: 'center', gap: Spacing.sm, paddingVertical: Spacing.xl },
  footerLink: { fontSize: FontSize.sm, color: Colors.textMuted },
  footerDot: { fontSize: FontSize.sm, color: Colors.textMuted },
});
