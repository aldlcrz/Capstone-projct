/**
 * Push Notification Service — Expo Notifications
 * Remote push tokens only work in EAS builds (not Expo Go SDK 53+).
 * Local notifications still work in Expo Go.
 */
import Constants from 'expo-constants';
import { Platform } from 'react-native';

/** Returns true when running inside the Expo Go client */
function isExpoGo(): boolean {
  return Constants.appOwnership === 'expo';
}

/**
 * Configure foreground notification display.
 * Called lazily so the import of expo-notifications only happens in builds.
 */
async function setupHandler() {
  const Notifications = await import('expo-notifications');
  Notifications.setNotificationHandler({
    handleNotification: async () => ({
      shouldShowAlert: true,
      shouldPlaySound: true,
      shouldSetBadge: true,
      shouldShowBanner: true,
      shouldShowList: true,
    }),
  });
  return Notifications;
}

/**
 * Register push token with backend.
 * Silently skipped in Expo Go — only works in EAS dev/production builds.
 */
export async function registerPushToken(): Promise<string | null> {
  if (isExpoGo()) {
    console.log('[Push] Skipped — remote push not supported in Expo Go. Use EAS Build.');
    return null;
  }

  const Device = await import('expo-device');
  if (!Device.isDevice) {
    console.log('[Push] Skipped — not a physical device.');
    return null;
  }

  const Notifications = await setupHandler();

  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  let finalStatus = existingStatus;
  if (existingStatus !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }
  if (finalStatus !== 'granted') return null;

  try {
    const { data: token } = await Notifications.getExpoPushTokenAsync({
      projectId: Constants.expoConfig?.extra?.eas?.projectId,
    });

    if (Platform.OS === 'android') {
      await Notifications.setNotificationChannelAsync('default', {
        name: 'LumBarong Notifications',
        importance: Notifications.AndroidImportance.MAX,
        vibrationPattern: [0, 250, 250, 250],
        lightColor: '#C0422A',
        sound: 'default',
      });
      await Notifications.setNotificationChannelAsync('orders', {
        name: 'Order Updates',
        importance: Notifications.AndroidImportance.HIGH,
        vibrationPattern: [0, 250, 250, 250],
        lightColor: '#C0422A',
        sound: 'default',
      });
    }

    const { api } = await import('@/lib/api');
    await api.post('/users/push-token', { token, platform: Platform.OS }).catch(() => {});

    console.log('[Push] Token registered:', token);
    return token;
  } catch (err) {
    console.warn('[Push] Failed:', err);
    return null;
  }
}

/** Schedule a local notification — works in Expo Go too */
export async function scheduleLocalNotification(
  title: string,
  body: string,
  data?: Record<string, any>
) {
  if (isExpoGo()) return; // local notifs also limited in Expo Go SDK 53
  const Notifications = await import('expo-notifications');
  await Notifications.scheduleNotificationAsync({
    content: { title, body, data: data || {}, sound: 'default' },
    trigger: null,
  });
}

/** Clear badge count */
export async function clearBadgeCount() {
  if (isExpoGo()) return;
  const Notifications = await import('expo-notifications');
  await Notifications.setBadgeCountAsync(0);
}

/** Listen for notification taps */
export function addNotificationResponseListener(
  callback: (response: any) => void
): { remove: () => void } {
  if (isExpoGo()) return { remove: () => {} };
  // Lazy — won't throw in Expo Go since we return early
  let sub: any;
  import('expo-notifications').then(N => {
    sub = N.addNotificationResponseReceivedListener(callback);
  });
  return { remove: () => sub?.remove() };
}
