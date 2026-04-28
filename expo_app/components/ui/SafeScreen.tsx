/**
 * SafeScreen — wrapper that uses react-native-safe-area-context's SafeAreaView
 * Drop-in replacement for react-native's deprecated SafeAreaView.
 * Usage: import { SafeScreen } from '@/components/ui/SafeScreen';
 */
import React from 'react';
import { StyleSheet, ViewStyle } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';

interface Props {
  children: React.ReactNode;
  style?: ViewStyle | ViewStyle[];
}

export function SafeScreen({ children, style }: Props) {
  return (
    <SafeAreaView style={[styles.container, style]}>
      {children}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
});
