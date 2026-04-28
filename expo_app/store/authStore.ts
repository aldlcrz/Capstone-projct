/**
 * LumBarong Auth Store (Zustand)
 * Replaces web system's sessionStorage-based auth with AsyncStorage persistence.
 */
import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  UserRole,
  setSessionForRole,
  removeSessionForRole,
  getTokenForRole,
  getStoredUserForRole,
  clearAllSessions,
} from '@/lib/api';

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  role: UserRole;
  isVerified?: boolean;
  profilePhoto?: string | null;
  hasPasswordSet?: boolean;
  status?: string;
}

interface AuthState {
  // Active session
  user: AuthUser | null;
  token: string | null;
  role: UserRole | null;
  isAuthenticated: boolean;
  isLoading: boolean;

  // Backend connection settings
  backendIp: string;
  backendPort: string;

  // Actions
  login: (role: UserRole, token: string, user: AuthUser) => Promise<void>;
  logout: () => Promise<void>;
  restoreSession: () => Promise<void>;
  setBackendConfig: (ip: string, port: string) => Promise<void>;
  updateUser: (updates: Partial<AuthUser>) => void;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  token: null,
  role: null,
  isAuthenticated: false,
  isLoading: true,
  backendIp: '192.168.100.5',
  backendPort: '5000',

  login: async (role, token, user) => {
    await setSessionForRole(role, { token, user });
    await AsyncStorage.setItem('active_role', role);
    set({ user, token, role, isAuthenticated: true, isLoading: false });
  },

  logout: async () => {
    const { role } = get();
    if (role) await removeSessionForRole(role);
    await AsyncStorage.multiRemove(['active_role', 'backend_ip', 'backend_port']);
    set({ user: null, token: null, role: null, isAuthenticated: false, isLoading: false });
  },

  restoreSession: async () => {
    set({ isLoading: true });
    try {
      // Always ensure default backend config is written to AsyncStorage
      const storedIp   = await AsyncStorage.getItem('backend_ip');
      const storedPort = await AsyncStorage.getItem('backend_port');
      const ip   = storedIp   || '192.168.100.5';
      const port = storedPort || '5000';
      if (!storedIp)   await AsyncStorage.setItem('backend_ip',   ip);
      if (!storedPort) await AsyncStorage.setItem('backend_port', port);
      set({ backendIp: ip, backendPort: port });

      const activeRole = (await AsyncStorage.getItem('active_role')) as UserRole | null;
      if (!activeRole) { set({ isLoading: false }); return; }

      const token = await getTokenForRole(activeRole);
      const user  = await getStoredUserForRole(activeRole);

      if (token && user) {
        set({ user, token, role: activeRole, isAuthenticated: true, isLoading: false });
      } else {
        set({ isLoading: false });
      }
    } catch (e) {
      console.warn('Session restore failed:', e);
      set({ isLoading: false });
    }
  },

  setBackendConfig: async (ip: string, port: string) => {
    await AsyncStorage.setItem('backend_ip', ip);
    await AsyncStorage.setItem('backend_port', port);
    set({ backendIp: ip, backendPort: port });
  },

  updateUser: (updates) => {
    const { user } = get();
    if (!user) return;
    set({ user: { ...user, ...updates } });
  },
}));
