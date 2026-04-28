/**
 * LumBarong Design System — Mobile Theme
 * Cloned from the web system's color palette, spacing, and typography.
 */

export const Colors = {
  // Primary brand palette (warm Philippine heritage tones)
  primary: '#C0422A',       // Main red/terracotta — CTAs, active states
  primaryLight: '#E8604A',  // Hover/pressed state
  primaryDark: '#9A3220',   // Dark variant

  // Accent gold
  accent: '#D4B896',        // Gold/sand — labels, borders, secondary text
  accentLight: '#F7F3EE',   // Near-white warm — headlines, light text
  accentMuted: 'rgba(212, 184, 150, 0.6)', // Muted labels

  // Background palette
  bgDeep: '#1C1917',        // Deepest background (landing page)
  bgDark: '#2A2623',        // Card/surface background
  bgMedium: '#3E3834',      // Input background, secondary surface
  bgLight: '#524C47',       // Tertiary surface, dividers

  // Text
  textPrimary: '#F7F3EE',   // Main body text
  textSecondary: '#D4B896', // Secondary / label text
  textMuted: 'rgba(212, 184, 150, 0.5)', // Placeholder, footer text
  textInverse: '#1C1917',   // Text on light/accent buttons

  // Status colors
  success: '#4CAF50',
  successLight: 'rgba(76, 175, 80, 0.15)',
  warning: '#FF9800',
  warningLight: 'rgba(255, 152, 0, 0.15)',
  error: '#F44336',
  errorLight: 'rgba(244, 67, 54, 0.15)',
  info: '#2196F3',
  infoLight: 'rgba(33, 150, 243, 0.15)',

  // Order status colors (matching web)
  statusPending: '#FF9800',
  statusProcessing: '#2196F3',
  statusShipped: '#9C27B0',
  statusDelivered: '#00BCD4',
  statusReceived: '#4CAF50',
  statusCompleted: '#388E3C',
  statusCancelled: '#F44336',
  statusRefund: '#FF5722',

  // Neutral
  white: '#FFFFFF',
  black: '#000000',
  transparent: 'transparent',

  // Borders
  border: 'rgba(212, 184, 150, 0.15)',
  borderFocus: '#D4B896',

  // Tab bar
  tabActive: '#C0422A',
  tabInactive: 'rgba(212, 184, 150, 0.4)',
  tabBackground: '#2A2623',
} as const;

export const Spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  xxl: 24,
  xxxl: 32,
  huge: 48,
} as const;

export const Radius = {
  sm: 6,
  md: 10,
  lg: 15,
  xl: 20,
  xxl: 25,
  full: 999,
} as const;

export const FontSize = {
  xs: 10,
  sm: 12,
  md: 14,
  lg: 16,
  xl: 18,
  xxl: 22,
  title: 28,
  hero: 34,
} as const;

export const FontWeight = {
  regular: '400' as const,
  medium: '500' as const,
  semibold: '600' as const,
  bold: '700' as const,
  heavy: '900' as const,
};

export const Shadow = {
  sm: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 4,
    elevation: 2,
  },
  md: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 5,
  },
  lg: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.3,
    shadowRadius: 16,
    elevation: 10,
  },
  primary: {
    shadowColor: '#C0422A',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 8,
    elevation: 6,
  },
};

export const getStatusColor = (status: string): string => {
  const map: Record<string, string> = {
    'Pending': Colors.statusPending,
    'Processing': Colors.statusProcessing,
    'Shipped': Colors.statusShipped,
    'Delivered': Colors.statusDelivered,
    'Received by Buyer': Colors.statusReceived,
    'Completed': Colors.statusCompleted,
    'Cancelled': Colors.statusCancelled,
    'Cancellation Pending': Colors.statusCancelled,
    'Refund Requested': Colors.statusRefund,
    'active': Colors.success,
    'frozen': Colors.warning,
    'blocked': Colors.error,
    'rejected': Colors.error,
    'pending': Colors.warning,
  };
  return map[status] || Colors.textMuted;
};
