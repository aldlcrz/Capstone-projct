/**
 * Badge component — status color-coded label
 */
import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { getStatusColor } from '@/constants/theme';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface Props {
  label: string;
  status?: string;
  color?: string;
}

export function Badge({ label, status, color }: Props) {
  const bg = status ? `${getStatusColor(status)}22` : (color ? `${color}22` : Colors.bgMedium);
  const fg = status ? getStatusColor(status) : (color || Colors.textMuted);

  return (
    <View style={[styles.badge, { backgroundColor: bg }]}>
      <Text style={[styles.label, { color: fg }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    borderRadius: Radius.full,
    paddingHorizontal: Spacing.md,
    paddingVertical: 3,
    alignSelf: 'flex-start',
  },
  label: {
    fontSize: FontSize.xs,
    fontWeight: FontWeight.bold,
    textTransform: 'capitalize',
    letterSpacing: 0.5,
  },
});
