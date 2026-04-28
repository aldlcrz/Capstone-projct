/**
 * LumBarong Mobile API Client
 * Mirrors the logic in the web system's /lib/api.js but uses AsyncStorage.
 */
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// ─── Storage Keys ─────────────────────────────────────────────────────────────
export const ROLE_STORAGE_KEYS = {
  admin:    { token: 'admin_token',    user: 'admin_user' },
  seller:   { token: 'seller_token',   user: 'seller_user' },
  customer: { token: 'customer_token', user: 'customer_user' },
} as const;

export type UserRole = keyof typeof ROLE_STORAGE_KEYS;
export const BACKEND_URL_KEY = 'backend_ip';
export const BACKEND_PORT_KEY = 'backend_port';

// ─── Backend URL helper ───────────────────────────────────────────────────────
export const getBackendUrl = async (): Promise<string> => {
  const ip   = await AsyncStorage.getItem(BACKEND_URL_KEY)  || '192.168.100.5';
  const port = await AsyncStorage.getItem(BACKEND_PORT_KEY) || '5000';
  return `http://${ip}:${port}`;
};

// ─── Token helpers ─────────────────────────────────────────────────────────────
export const getTokenForRole = async (role: UserRole): Promise<string | null> => {
  const key = ROLE_STORAGE_KEYS[role]?.token;
  if (!key) return null;
  return AsyncStorage.getItem(key);
};

export const getStoredUserForRole = async (role: UserRole): Promise<any | null> => {
  const key = ROLE_STORAGE_KEYS[role]?.user;
  if (!key) return null;
  const raw = await AsyncStorage.getItem(key);
  if (!raw) return null;
  try { return JSON.parse(raw); } catch { return null; }
};

export const setSessionForRole = async (
  role: UserRole,
  { token, user }: { token: string; user: any }
): Promise<void> => {
  const keys = ROLE_STORAGE_KEYS[role];
  if (!keys) return;
  await AsyncStorage.setItem(keys.token, token);
  await AsyncStorage.setItem(keys.user, JSON.stringify(user));
};

export const removeSessionForRole = async (role: UserRole): Promise<void> => {
  const keys = ROLE_STORAGE_KEYS[role];
  if (!keys) return;
  await AsyncStorage.multiRemove([keys.token, keys.user]);
};

export const clearAllSessions = async (): Promise<void> => {
  const allKeys = Object.values(ROLE_STORAGE_KEYS).flatMap(k => [k.token, k.user]);
  await AsyncStorage.multiRemove(allKeys);
};

// ─── Axios instance ───────────────────────────────────────────────────────────
// We create a plain instance; the interceptor mutates baseURL at request time
// since AsyncStorage is async.
export const api = axios.create({
  timeout: 15000,
});

// Attach token and baseURL before every request
api.interceptors.request.use(async (config) => {
  try {
    const baseUrl = await getBackendUrl();
    config.baseURL = `${baseUrl}/api/v1`;

    // Determine which token to attach based on stored active role
    const activeRole = (await AsyncStorage.getItem('active_role') as UserRole) || 'customer';
    const token = await getTokenForRole(activeRole);
    if (token && token !== 'null' && token !== 'undefined') {
      config.headers.Authorization = `Bearer ${token}`;
    }
  } catch (e) {
    console.warn('API interceptor error:', e);
  }
  return config;
});

// Handle 401 responses
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error?.response?.status === 401) {
      const activeRole = (await AsyncStorage.getItem('active_role') as UserRole) || 'customer';
      await removeSessionForRole(activeRole);
      await AsyncStorage.removeItem('active_role');
    }
    return Promise.reject(error);
  }
);

// ─── Error message helper ─────────────────────────────────────────────────────
export const getApiErrorMessage = (error: any, fallback = 'Something went wrong'): string => {
  if (error?.response?.data?.message) return error.response.data.message;
  if (error?.response?.data?.error)   return error.response.data.error;
  if (error?.code === 'ERR_NETWORK' || !error?.response) {
    return 'Cannot reach the server. Check your IP/Port and ensure the backend is running.';
  }
  return fallback;
};
