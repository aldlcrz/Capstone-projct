import axios from "axios";
import { Capacitor } from "@capacitor/core";

const trimTrailingSlash = (value) => value.replace(/\/+$/, "");
const VISITOR_SESSION_KEY = "lumbarong_visitor_session";
export const SESSION_SYNC_EVENT = "lumbarong:session-sync";
export const ACCOUNT_STATUS_EVENT = "lumbarong:account-status";

export const ROLE_STORAGE_KEYS = {
  admin: { token: "admin_token", user: "admin_user" },
  seller: { token: "seller_token", user: "seller_user" },
  customer: { token: "customer_token", user: "customer_user" },
};

const getSessionStorage = () => {
  if (typeof window === "undefined") return null;
  try {
    return window.sessionStorage;
  } catch (error) {
    return null;
  }
};

const getLocalStorage = () => {
  if (typeof window === "undefined") return null;
  try {
    return window.localStorage;
  } catch (error) {
    return null;
  }
};

const getStorageValue = (key) => {
  const sessionStorageRef = getSessionStorage();
  const sessionValue = sessionStorageRef?.getItem(key);
  if (sessionValue && sessionValue !== "null" && sessionValue !== "undefined") {
    return sessionValue;
  }

  const localStorageRef = getLocalStorage();
  const legacyValue = localStorageRef?.getItem(key);
  if (legacyValue && legacyValue !== "null" && legacyValue !== "undefined") {
    sessionStorageRef?.setItem(key, legacyValue);
    localStorageRef?.removeItem(key);
    return legacyValue;
  }

  return null;
};

const setStorageValue = (key, value) => {
  const sessionStorageRef = getSessionStorage();
  const localStorageRef = getLocalStorage();

  if (value === null || value === undefined) {
    sessionStorageRef?.removeItem(key);
    localStorageRef?.removeItem(key);
    return;
  }

  sessionStorageRef?.setItem(key, value);
  localStorageRef?.removeItem(key);
};

const createVisitorSessionId = () => {
  if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
    return crypto.randomUUID();
  }
  return `visitor_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
};

const getVisitorSessionId = () => {
  if (typeof window === "undefined") return null;

  let visitorSessionId = localStorage.getItem(VISITOR_SESSION_KEY);
  if (!visitorSessionId || visitorSessionId === "null" || visitorSessionId === "undefined") {
    visitorSessionId = createVisitorSessionId();
    localStorage.setItem(VISITOR_SESSION_KEY, visitorSessionId);
  }

  return visitorSessionId;
};

const emitSessionSync = (role) => {
  if (typeof window === "undefined") return;
  window.dispatchEvent(new CustomEvent(SESSION_SYNC_EVENT, { detail: { role } }));
};

const emitAccountStatus = (detail) => {
  if (typeof window === "undefined") return;
  window.dispatchEvent(new CustomEvent(ACCOUNT_STATUS_EVENT, { detail }));
};

export const resolveRoleFromPath = (path) => {
  if (path.startsWith("/admin")) return "admin";
  if (path.startsWith("/seller")) return "seller";
  return "customer";
};

export const getTokenForRole = (role) => {
  if (typeof window === "undefined") return null;
  const key = ROLE_STORAGE_KEYS[role]?.token;
  if (!key) return null;

  return getStorageValue(key);
};

export const getStoredUserForRole = (role) => {
  const key = ROLE_STORAGE_KEYS[role]?.user;
  if (!key) return null;

  const rawValue = getStorageValue(key);
  if (!rawValue) return null;

  try {
    return JSON.parse(rawValue);
  } catch (error) {
    return null;
  }
};

export const setStoredUserForRole = (role, user) => {
  const key = ROLE_STORAGE_KEYS[role]?.user;
  if (!key) return;

  setStorageValue(key, JSON.stringify(user));
  emitSessionSync(role);
};

export const setSessionForRole = (role, { token, user }) => {
  const keys = ROLE_STORAGE_KEYS[role];
  if (!keys) return;

  setStorageValue(keys.token, token);
  setStorageValue(keys.user, JSON.stringify(user));
  emitSessionSync(role);
};

const removeSessionKeys = (role) => {
  const keys = ROLE_STORAGE_KEYS[role];
  if (!keys) return;

  setStorageValue(keys.token, null);
  setStorageValue(keys.user, null);
  emitSessionSync(role);
};

// Mobile Emulators use 10.0.2.2 to reach the host machine's localhost
const isNative = typeof window !== "undefined" && Capacitor.isNativePlatform();
const DEFAULT_URL = isNative ? "http://10.0.2.2:5000" : "http://localhost:5000";

export const BACKEND_URL = trimTrailingSlash(
  process.env.NEXT_PUBLIC_BACKEND_URL || DEFAULT_URL
);

export const api = axios.create({
  baseURL: `${BACKEND_URL}/api/v1`,
});

// Add a request interceptor to include the token automatically
api.interceptors.request.use(
  (config) => {
    try {
      if (typeof window !== "undefined") {
        const path = window.location.pathname;
        let token = null;

        // Strictly role-specific token retrieval — NO generic 'token' fallback
        if (path.startsWith("/admin")) {
          token = getTokenForRole("admin");
        } else if (path.startsWith("/seller")) {
          token = getTokenForRole("seller");
        } else {
          // All other paths (including storefront /home, etc) use customer_token
          token = getTokenForRole("customer");
        }

        // Only add header if valid token exists
        if (token && token !== "null" && token !== "undefined") {
          config.headers.Authorization = `Bearer ${token}`;
        }

        const visitorSessionId = getVisitorSessionId();
        if (visitorSessionId) {
          config.headers["X-Visitor-Session"] = visitorSessionId;
        }
      }
    } catch (e) {
      console.error("API Interceptor Error:", e);
    }
    return config;
  },
  (error) => Promise.reject(error)
);

export const clearSession = (role) => {
  if (typeof window === "undefined") return;

  // If role is explicitly provided use it, otherwise infer from the current URL
  const resolvedRole = role || (() => {
    return resolveRoleFromPath(window.location.pathname);
  })();

  removeSessionKeys(resolvedRole);
};

// Add response interceptor for debugging 401s
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Automatically clear expired tokens to prevent infinite error loops in the UI
    if (error?.response?.status === 401) {
      const role = typeof window !== "undefined"
        ? resolveRoleFromPath(window.location.pathname)
        : "customer";
      const status = error?.response?.data?.status;
      const reason = error?.response?.data?.reason;

      if (status === "frozen" || status === "blocked" || status === "rejected") {
        emitAccountStatus({
          role,
          status,
          reason,
          message: error?.response?.data?.message || "Account access restricted.",
        });
        return Promise.reject(error);
      }

      console.warn("Unauthorized request detected. Clearing local session keys...");
      if (typeof window !== "undefined") {
        removeSessionKeys(role);
        
        // Note: Automatic redirect is disabled per user request in conversation 734621da.
        // The app will naturally prompt for login when the user tries to navigate or the next guard check runs.
      }
    }
    return Promise.reject(error);
  }
);

export function getApiErrorMessage(error, fallbackMessage) {
  if (error?.response?.data?.message) {
    return error.response.data.message;
  }
  if (error?.response?.data?.error) {
    return error.response.data.error;
  }

  if (error?.code === "ERR_NETWORK" || !error?.response) {
    return `Cannot reach the backend at ${BACKEND_URL}. Start the backend server and try again.`;
  }

  return fallbackMessage;
}
