/**
 * Heritage Guide — Info page about Barong Tagalog & LumBarong
 * Mirrors the web heritage/about section on the landing page
 */
import React, { useRef } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, Animated, ImageBackground, Platform,
} from 'react-native';
import { router } from 'expo-router';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

const HERITAGE_SECTIONS = [
  {
    emoji: '🧵',
    title: 'What is Barong Tagalog?',
    body: 'The Barong Tagalog is the national dress of the Philippines, worn by Filipino men during formal occasions. It is typically made from piña (pineapple fiber), jusi, or ramie — often intricately embroidered with floral or geometric patterns.',
  },
  {
    emoji: '📍',
    title: 'Lumban, Laguna — The Embroidery Capital',
    body: 'Lumban is a municipality in Laguna renowned for its hand-embroidered Barong Tagalog. The craftsmanship has been passed down through generations of artisans, making Lumban a living heritage site for Filipino textile arts.',
  },
  {
    emoji: '✋',
    title: 'Handcrafted with Pride',
    body: 'Each piece produced by LumBarong artisans is hand-embroidered — a labor of love that can take days to complete. The intricate needlework reflects patterns unique to each workshop, carrying stories of tradition and cultural pride.',
  },
  {
    emoji: '🌿',
    title: 'Sustainable & Ethical',
    body: 'LumBarong promotes fair trade practices, connecting local artisans directly to buyers. By purchasing through LumBarong, you support the livelihoods of master craftspeople and help preserve a 400-year-old tradition.',
  },
  {
    emoji: '🎖️',
    title: 'National Significance',
    body: 'The Barong Tagalog was declared the national formal attire of the Philippines. Filipino presidents, dignitaries, and cultural figures wear it proudly as a symbol of identity, heritage, and elegance.',
  },
  {
    emoji: '🛍️',
    title: 'Shop with Purpose',
    body: 'Every purchase on LumBarong goes directly to verified Lumban artisan sellers. We verify all sellers to ensure authenticity and quality, so you receive genuine, handcrafted Philippine heritage wear.',
  },
];

export default function HeritageGuideScreen() {
  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        {/* Header */}
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>

        <View style={styles.hero}>
          <Text style={styles.heroEmoji}>🇵🇭</Text>
          <Text style={styles.heroLabel}>HERITAGE GUIDE</Text>
          <Text style={styles.heroTitle}>The Art of the{'\n'}Barong Tagalog</Text>
          <Text style={styles.heroSub}>
            Discover the story behind the Philippines' most beloved national garment
            — crafted with centuries of tradition in Lumban, Laguna.
          </Text>
        </View>

        {/* Sections */}
        {HERITAGE_SECTIONS.map((section, i) => (
          <View key={i} style={styles.card}>
            <Text style={styles.cardEmoji}>{section.emoji}</Text>
            <Text style={styles.cardTitle}>{section.title}</Text>
            <Text style={styles.cardBody}>{section.body}</Text>
          </View>
        ))}

        {/* CTA */}
        <View style={styles.ctaBox}>
          <Text style={styles.ctaTitle}>Experience the Tradition</Text>
          <Text style={styles.ctaSub}>
            Browse authentic, handcrafted Barong Tagalog from verified Lumban artisans.
          </Text>
          <TouchableOpacity style={styles.ctaBtn} onPress={() => router.push('/login')}>
            <Text style={styles.ctaBtnText}>Shop LumBarong →</Text>
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
  hero: {
    alignItems: 'center', paddingHorizontal: Spacing.xxl,
    paddingTop: Spacing.xl, paddingBottom: Spacing.xxxl,
    borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  heroEmoji: { fontSize: 56, marginBottom: Spacing.lg },
  heroLabel: {
    fontSize: FontSize.xs, fontWeight: FontWeight.bold,
    color: Colors.primary, letterSpacing: 4,
    marginBottom: Spacing.sm,
  },
  heroTitle: {
    fontFamily: Platform.OS === 'ios' ? 'Georgia' : 'serif',
    fontSize: 30, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, textAlign: 'center',
    lineHeight: 38, marginBottom: Spacing.lg,
  },
  heroSub: {
    fontSize: FontSize.md, color: Colors.textSecondary,
    textAlign: 'center', lineHeight: 22,
  },
  card: {
    marginHorizontal: Spacing.xl, marginTop: Spacing.xl,
    backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.xxl, borderWidth: 1, borderColor: Colors.border,
    borderLeftWidth: 3, borderLeftColor: Colors.primary,
  },
  cardEmoji: { fontSize: 32, marginBottom: Spacing.md },
  cardTitle: {
    fontSize: FontSize.lg, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: Spacing.sm,
  },
  cardBody: {
    fontSize: FontSize.md, color: Colors.textSecondary,
    lineHeight: 22,
  },
  ctaBox: {
    margin: Spacing.xl, marginTop: Spacing.xxxl,
    backgroundColor: Colors.primary, borderRadius: Radius.xl,
    padding: Spacing.xxl, alignItems: 'center',
  },
  ctaTitle: {
    fontSize: FontSize.xl, fontWeight: FontWeight.bold,
    color: Colors.white, marginBottom: Spacing.sm, textAlign: 'center',
  },
  ctaSub: {
    fontSize: FontSize.sm, color: 'rgba(255,255,255,0.8)',
    textAlign: 'center', lineHeight: 20, marginBottom: Spacing.xl,
  },
  ctaBtn: {
    backgroundColor: Colors.white, borderRadius: Radius.full,
    paddingHorizontal: Spacing.xxl, paddingVertical: Spacing.lg,
  },
  ctaBtnText: {
    color: Colors.primary, fontWeight: FontWeight.bold, fontSize: FontSize.md,
  },
});
