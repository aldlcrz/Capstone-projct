/**
 * Root Layout — wraps every screen with:
 * - Auth store session restore
 * - Socket provider
 * - Broadcast banner (global)
 * - Push notification registration
 * - Dark status bar
 */
import { useEffect, useRef } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { View } from 'react-native';
import { useAuthStore } from '@/store/authStore';
import { SocketProvider } from '@/lib/socket';
import { BroadcastBanner } from '@/components/BroadcastBanner';
import { registerPushToken, addNotificationResponseListener } from '@/lib/notifications';
import { router } from 'expo-router';
import { Colors } from '@/constants/theme';

export default function RootLayout() {
  const { restoreSession, isAuthenticated } = useAuthStore();
  const notifListenerRef = useRef<{ remove: () => void } | null>(null);

  useEffect(() => {
    restoreSession();
  }, []);

  // Register push token and notification tap handler after authentication
  useEffect(() => {
    if (!isAuthenticated) return;

    // Register push token with backend
    registerPushToken().catch(console.warn);

    // Handle notification taps (deep link routing)
    notifListenerRef.current = addNotificationResponseListener((response) => {
      const data = response.notification.request.content.data as any;
      if (data?.screen) {
        router.push(data.screen as any);
      } else if (data?.orderId) {
        router.push('/(customer)/orders' as any);
      }
    });

    return () => {
      notifListenerRef.current?.remove();
    };
  }, [isAuthenticated]);

  return (
    <SocketProvider>
      <View style={{ flex: 1, backgroundColor: Colors.bgDeep }}>
        <Stack screenOptions={{ headerShown: false, animation: 'fade' }}>
          <Stack.Screen name="index" />
          <Stack.Screen name="login" />
          <Stack.Screen name="register" />
          <Stack.Screen name="register-seller" />
          <Stack.Screen name="forgot-password" />
          <Stack.Screen name="verify-otp" />
          <Stack.Screen name="reset-password" />
          <Stack.Screen name="set-password" />
          <Stack.Screen name="heritage-guide" />
          <Stack.Screen name="about" />
          <Stack.Screen name="privacy-policy" />
          <Stack.Screen name="terms" />
          <Stack.Screen name="(customer)" />
          <Stack.Screen name="(seller)" />
          <Stack.Screen name="(admin)" />
          <Stack.Screen name="(tabs)" />
          <Stack.Screen name="modal" options={{ presentation: 'modal' }} />
        </Stack>
        {/* Global broadcast banner — floats above all screens */}
        <BroadcastBanner />
        <StatusBar style="light" />
      </View>
    </SocketProvider>
  );
}
