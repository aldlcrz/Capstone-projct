import { PushNotifications } from '@capacitor/push-notifications';
import { Capacitor } from '@capacitor/core';
import { api } from './api';

export const initializePushNotifications = async () => {
    if (!Capacitor.isNativePlatform()) {
        console.log('Push Notifications: Not a native platform. Skipping registration.');
        return;
    }

    try {
        // 1. Request permissions
        let permStatus = await PushNotifications.checkPermissions();

        if (permStatus.receive === 'prompt') {
            permStatus = await PushNotifications.requestPermissions();
        }

        if (permStatus.receive !== 'granted') {
            console.warn('Push Notifications: User denied permissions.');
            return;
        }

        // 2. Register with Apple / Google
        await PushNotifications.register();

        // 3. Listen for token registration
        PushNotifications.addListener('registration', async (token) => {
            console.log('Push Registration Success. Token:', token.value);
            
            // Send token to backend
            try {
                const userStr = localStorage.getItem('user');
                if (userStr) {
                    await api.patch('/users/fcm-token', { fcmToken: token.value });
                    console.log('FCM Token synced with backend.');
                }
            } catch (err) {
                console.error('Failed to sync FCM token:', err);
            }
        });

        PushNotifications.addListener('registrationError', (error) => {
            console.error('Push Registration Error:', error);
        });

        // 4. Handle incoming notifications
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('Push Received:', notification);
            // You can trigger a local alert or update UI state here
        });

        PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('Push Action Performed:', notification);
            // Handle navigation based on notification.data.link
            if (notification.notification.data?.link) {
                window.location.href = notification.notification.data.link;
            }
        });

    } catch (error) {
        console.error('Push Initialization Failed:', error);
    }
};
