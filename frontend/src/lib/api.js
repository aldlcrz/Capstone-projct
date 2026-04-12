import axios from "axios";
import { Capacitor } from "@capacitor/core";

const trimTrailingSlash = (value) => value.replace(/\/+$/, "");

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

        // Try to get role-specific token first
        if (path.startsWith("/admin")) {
          token = localStorage.getItem("admin_token");
        } else if (path.startsWith("/seller")) {
          token = localStorage.getItem("seller_token");
        } else {
          // Customer routes — use customer-specific token, fall back to generic only for compatibility
          token = localStorage.getItem("customer_token") || localStorage.getItem("token");
        }

        // Ensure we don't send "null" or "undefined" as string tokens
        if (token && token !== "null" && token !== "undefined") {
          config.headers.Authorization = `Bearer ${token}`;
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
    const path = window.location.pathname;
    if (path.startsWith("/admin")) return "admin";
    if (path.startsWith("/seller")) return "seller";
    return "customer";
  })();

  // Only remove keys that belong to this role
  localStorage.removeItem(`${resolvedRole}_token`);
  localStorage.removeItem(`${resolvedRole}_user`);

  // Only remove the generic keys if they belong to this role
  // (i.e. the last login was for this role)
  try {
    const genericUser = JSON.parse(localStorage.getItem("user") || "null");
    if (!genericUser || genericUser.role === resolvedRole) {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
    }
  } catch (e) {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
  }
};

// Add response interceptor for debugging 401s
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Automatically clear expired tokens to prevent infinite error loops in the UI
    if (error?.response?.status === 401) {
      const msg = error.response.data?.message || "";
      if (msg.toLowerCase().includes("session expired") || msg.toLowerCase().includes("token is not valid")) {
        console.warn("Session expired or invalid token detected. Clearing local session keys...");
        if (typeof window !== "undefined") {
          const path = window.location.pathname;
          if (path.startsWith("/admin")) {
            localStorage.removeItem("admin_token");
            localStorage.removeItem("admin_user");
          } else if (path.startsWith("/seller")) {
            localStorage.removeItem("seller_token");
            localStorage.removeItem("seller_user");
          }
          localStorage.removeItem("token");
          localStorage.removeItem("user");
          
          // Note: Automatic redirect is disabled per user request in conversation 734621da.
          // The app will naturally prompt for login when the user tries to navigate or the next guard check runs.
        }
      }
    }
    return Promise.reject(error);
  }
);

export function getApiErrorMessage(error, fallbackMessage) {
  if (error?.response?.data?.message) {
    return error.response.data.message;
  }

  if (error?.code === "ERR_NETWORK" || !error?.response) {
    return `Cannot reach the backend at ${BACKEND_URL}. Start the backend server and try again.`;
  }

  return fallbackMessage;
}
