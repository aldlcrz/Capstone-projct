/**
 * BroadcastBanner — Global in-app broadcast notification banner
 * Mirrors BroadcastNotification.jsx from web
 * Shows at the top of the screen when a broadcast arrives via socket
 * Auto-dismisses after 10 seconds
 */
import React, { useEffect } from 'react';
import {
  View, Text, StyleSheet, TouchableOpacity,
  Animated, Dimensions,
} from 'react-native';
import { useSocket } from '@/lib/socket';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

const { width } = Dimensions.get('window');

const TYPE_ICONS: Record<string, string> = {
  system: '📢',
  maintenance: '🔧',
  promotion: '🎉',
  alert: '⚠️',
};

export function BroadcastBanner() {
  const { currentBroadcast, clearBroadcast } = useSocket();
  const translateY = React.useRef(new Animated.Value(-200)).current;
  const opacity = React.useRef(new Animated.Value(0)).current;
  const insets = useSafeAreaInsets();

  useEffect(() => {
    if (currentBroadcast) {
      // Slide in
      Animated.parallel([
        Animated.spring(translateY, { toValue: 0, useNativeDriver: true, tension: 100, friction: 8 }),
        Animated.timing(opacity, { toValue: 1, duration: 300, useNativeDriver: true }),
      ]).start();

      // Auto-dismiss after 10s
      const timer = setTimeout(() => dismissBanner(), 10000);
      return () => clearTimeout(timer);
    } else {
      // Hidden state
      translateY.setValue(-200);
      opacity.setValue(0);
    }
  }, [currentBroadcast]);

  const dismissBanner = () => {
    Animated.parallel([
      Animated.timing(translateY, { toValue: -200, duration: 300, useNativeDriver: true }),
      Animated.timing(opacity, { toValue: 0, duration: 300, useNativeDriver: true }),
    ]).start(() => clearBroadcast());
  };

  if (!currentBroadcast) return null;

  const icon = TYPE_ICONS[currentBroadcast.type] || '📢';

  return (
    <Animated.View
      style={[
        styles.container,
        { top: insets.top + Spacing.sm, transform: [{ translateY }], opacity },
      ]}
    >
      <View style={styles.accentBar} />
      <View style={styles.content}>
        <View style={styles.iconWrap}>
          <Text style={styles.icon}>{icon}</Text>
        </View>
        <View style={styles.textWrap}>
          <View style={styles.titleRow}>
            <Text style={styles.title} numberOfLines={1}>{currentBroadcast.title}</Text>
            <View style={styles.liveTag}>
              <Text style={styles.liveText}>LIVE</Text>
            </View>
          </View>
          <Text style={styles.message} numberOfLines={3}>{currentBroadcast.message}</Text>
        </View>
        <TouchableOpacity style={styles.closeBtn} onPress={dismissBanner}>
          <Text style={styles.closeText}>✕</Text>
        </TouchableOpacity>
      </View>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  container: {
    position: 'absolute', left: Spacing.lg, right: Spacing.lg,
    zIndex: 9999, backgroundColor: Colors.bgDark,
    borderRadius: Radius.xl, overflow: 'hidden',
    borderWidth: 1, borderColor: Colors.border,
    shadowColor: '#000', shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.4, shadowRadius: 16, elevation: 20,
  },
  accentBar: { height: 3, backgroundColor: Colors.primary, width: '100%' },
  content: {
    flexDirection: 'row', alignItems: 'flex-start',
    padding: Spacing.lg, gap: Spacing.md,
  },
  iconWrap: {
    width: 40, height: 40, borderRadius: Radius.md,
    backgroundColor: `${Colors.primary}22`,
    justifyContent: 'center', alignItems: 'center',
    flexShrink: 0,
  },
  icon: { fontSize: 20 },
  textWrap: { flex: 1 },
  titleRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm, marginBottom: 4 },
  title: {
    fontSize: FontSize.sm, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, flex: 1,
  },
  liveTag: {
    backgroundColor: Colors.primary, borderRadius: Radius.sm,
    paddingHorizontal: Spacing.xs, paddingVertical: 1,
  },
  liveText: { fontSize: 8, fontWeight: FontWeight.bold, color: Colors.white, letterSpacing: 1 },
  message: { fontSize: FontSize.xs, color: Colors.textSecondary, lineHeight: 16 },
  closeBtn: { padding: Spacing.xs, marginLeft: Spacing.xs },
  closeText: { fontSize: FontSize.md, color: Colors.textMuted },
});
