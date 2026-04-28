/**
 * Landing Page — Mobile clone of the web system's root page.js
 * Colors: #1C1917 bg, #D4B896 gold, #C0422A red, #F7F3EE text
 */
import React, { useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  SafeAreaView,
  Platform,
  TouchableOpacity,
  Image,
} from 'react-native';
import { router } from 'expo-router';
import { useAuthStore } from '@/store/authStore';
import { Colors, FontSize, FontWeight, Spacing, Radius, Shadow } from '@/constants/theme';

const NAV_LINKS = [
  { label: 'GUIDE', route: '/heritage-guide' },
  { label: 'ABOUT US', route: '/about' },
  { label: 'PRIVACY POLICY', route: '/privacy-policy' },
  { label: 'TERMS OF USE', route: '/terms' },
];

export default function LandingScreen() {
  const { isAuthenticated, role, isLoading } = useAuthStore();

  // Redirect if already logged in
  useEffect(() => {
    if (!isLoading && isAuthenticated && role) {
      if (role === 'admin') router.replace('/(admin)/dashboard' as any);
      else if (role === 'seller') router.replace('/(seller)/dashboard' as any);
      else router.replace('/(customer)/home' as any);
    }
  }, [isAuthenticated, role, isLoading]);

  return (
    <View style={styles.container}>
      <SafeAreaView style={styles.safeArea}>

        {/* ─── Header ─────────────────────────────────────── */}
        <View style={styles.header}>
          <Text style={styles.logo}>LumbaRong</Text>
        </View>

        {/* ─── Nav Links ──────────────────────────────────── */}
        <View style={styles.navRow}>
          {NAV_LINKS.map((link) => (
            <TouchableOpacity
              key={link.route}
              onPress={() => router.push(link.route as any)}
            >
              <Text style={styles.navLink}>{link.label}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* ─── Hero ───────────────────────────────────────── */}
        <View style={styles.hero}>
          <Text style={styles.headline}>
            Wear the {'\n'}
            <Text style={styles.spiritText}>Spirit</Text>
            {' '}of the{'\n'}Philippines.
          </Text>

          <Text style={styles.subheadline}>
            Buy directly from the makers of Barong.{'\n'}
            High quality, handmade clothes sent to your home.
          </Text>

          {/* Primary CTA */}
          <TouchableOpacity
            style={styles.ctaButton}
            onPress={() => router.push('/login')}
            activeOpacity={0.85}
          >
            <Text style={styles.ctaText}>START SHOPPING  →</Text>
          </TouchableOpacity>
        </View>

        {/* ─── Footer Auth Buttons ─────────────────────────── */}
        <View style={styles.footer}>
          <View style={styles.authRow}>
            <TouchableOpacity
              style={styles.outlineBtn}
              onPress={() => router.push('/login')}
            >
              <Text style={styles.outlineBtnText}>SIGN IN</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.solidBtn}
              onPress={() => router.push('/register')}
            >
              <Text style={styles.solidBtnText}>CREATE ACCOUNT</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.copyright}>© 2026 LUMBARONG PHILIPPINES</Text>
        </View>

      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.bgDeep,
  },
  safeArea: {
    flex: 1,
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.xxl,
    paddingTop: Spacing.xl,
    paddingBottom: Spacing.xl,
  },
  header: {
    alignItems: 'center',
    marginBottom: Spacing.md,
  },
  logo: {
    fontFamily: Platform.OS === 'ios' ? 'Georgia' : 'serif',
    fontSize: 22,
    fontWeight: FontWeight.heavy,
    fontStyle: 'italic',
    color: Colors.accent,
    letterSpacing: 1.5,
  },
  navRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    gap: Spacing.md,
    marginBottom: Spacing.lg,
  },
  navLink: {
    fontSize: FontSize.xs,
    fontWeight: FontWeight.bold,
    color: Colors.accentMuted,
    letterSpacing: 1.5,
  },
  hero: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: Spacing.xxxl,
  },
  headline: {
    fontFamily: Platform.OS === 'ios' ? 'Georgia' : 'serif',
    fontSize: 36,
    fontWeight: FontWeight.heavy,
    color: Colors.accentLight,
    textAlign: 'center',
    lineHeight: 44,
    marginBottom: Spacing.xl,
  },
  spiritText: {
    color: Colors.primary,
    fontStyle: 'italic',
  },
  subheadline: {
    fontSize: FontSize.md,
    color: Colors.accentMuted,
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: Spacing.xxxl,
    maxWidth: 300,
  },
  ctaButton: {
    backgroundColor: Colors.primary,
    paddingVertical: Spacing.lg,
    paddingHorizontal: Spacing.xxxl,
    borderRadius: Radius.full,
    ...Shadow.primary,
  },
  ctaText: {
    color: Colors.white,
    fontSize: FontSize.sm,
    fontWeight: FontWeight.bold,
    letterSpacing: 2,
  },
  footer: {
    alignItems: 'center',
    gap: Spacing.xl,
  },
  authRow: {
    flexDirection: 'row',
    gap: Spacing.lg,
  },
  outlineBtn: {
    paddingVertical: Spacing.md,
    paddingHorizontal: Spacing.xl,
    borderRadius: Radius.full,
    borderWidth: 1,
    borderColor: Colors.accent,
  },
  outlineBtnText: {
    color: Colors.accent,
    fontSize: FontSize.xs,
    fontWeight: FontWeight.bold,
    letterSpacing: 1.5,
  },
  solidBtn: {
    backgroundColor: Colors.primary,
    paddingVertical: Spacing.md,
    paddingHorizontal: Spacing.xl,
    borderRadius: Radius.full,
    ...Shadow.primary,
  },
  solidBtnText: {
    color: Colors.white,
    fontSize: FontSize.xs,
    fontWeight: FontWeight.bold,
    letterSpacing: 1.5,
  },
  copyright: {
    fontSize: FontSize.xs,
    color: Colors.textMuted,
    letterSpacing: 1.5,
  },
});
