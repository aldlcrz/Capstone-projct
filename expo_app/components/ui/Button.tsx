/**
 * Reusable Button component — matches web system's primary/secondary/outline CTAs
 */
import React from 'react';
import {
  TouchableOpacity,
  Text,
  ActivityIndicator,
  StyleSheet,
  ViewStyle,
  TextStyle,
  TouchableOpacityProps,
} from 'react-native';
import { Colors, Radius, FontSize, FontWeight, Shadow, Spacing } from '@/constants/theme';

type Variant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps extends TouchableOpacityProps {
  label: string;
  variant?: Variant;
  size?: Size;
  loading?: boolean;
  fullWidth?: boolean;
  style?: ViewStyle;
  textStyle?: TextStyle;
}

const variantStyles: Record<Variant, { container: ViewStyle; text: TextStyle }> = {
  primary: {
    container: { backgroundColor: Colors.primary, ...Shadow.primary },
    text: { color: Colors.white },
  },
  secondary: {
    container: { backgroundColor: Colors.accent },
    text: { color: Colors.textInverse },
  },
  outline: {
    container: { backgroundColor: Colors.transparent, borderWidth: 1, borderColor: Colors.accent },
    text: { color: Colors.accent },
  },
  ghost: {
    container: { backgroundColor: Colors.transparent },
    text: { color: Colors.accent },
  },
  danger: {
    container: { backgroundColor: Colors.error },
    text: { color: Colors.white },
  },
};

const sizeStyles: Record<Size, { container: ViewStyle; text: TextStyle }> = {
  sm: {
    container: { paddingVertical: Spacing.xs, paddingHorizontal: Spacing.md, borderRadius: Radius.md },
    text: { fontSize: FontSize.sm },
  },
  md: {
    container: { paddingVertical: Spacing.md, paddingHorizontal: Spacing.xl, borderRadius: Radius.lg },
    text: { fontSize: FontSize.md },
  },
  lg: {
    container: { paddingVertical: Spacing.lg, paddingHorizontal: Spacing.xxl, borderRadius: Radius.xl },
    text: { fontSize: FontSize.lg },
  },
};

export const Button: React.FC<ButtonProps> = ({
  label,
  variant = 'primary',
  size = 'md',
  loading = false,
  fullWidth = false,
  style,
  textStyle,
  disabled,
  ...rest
}) => {
  const vs = variantStyles[variant];
  const ss = sizeStyles[size];

  return (
    <TouchableOpacity
      style={[
        styles.base,
        vs.container,
        ss.container,
        fullWidth && styles.fullWidth,
        (disabled || loading) && styles.disabled,
        style,
      ]}
      disabled={disabled || loading}
      activeOpacity={0.75}
      {...rest}
    >
      {loading ? (
        <ActivityIndicator color={vs.text.color as string} size="small" />
      ) : (
        <Text style={[styles.label, vs.text, ss.text, textStyle]}>{label}</Text>
      )}
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  base: {
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
  },
  fullWidth: { width: '100%' },
  disabled: { opacity: 0.5 },
  label: {
    fontWeight: FontWeight.bold,
    letterSpacing: 0.5,
  },
});
