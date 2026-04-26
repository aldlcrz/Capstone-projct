"use client";

import { getStoredUserForRole } from "@/lib/api";

const STORAGE_NAMESPACE = "lumbarong";

export const CUSTOMER_STORAGE_SYNC_EVENT = "lumbarong:customer-storage-sync";
export const CUSTOMER_SHARED_STATE_KEYS = [
  "cart",
  "checkout_item",
  "checkout_items",
  "checkout_mode",
];

const isBrowser = () => typeof window !== "undefined";

const safeParseJson = (value, fallback) => {
  if (typeof value !== "string" || !value.trim()) return fallback;

  try {
    return JSON.parse(value);
  } catch (error) {
    return fallback;
  }
};

export const getActiveCustomerStorageOwner = () => {
  if (!isBrowser()) return "guest";

  const customer = getStoredUserForRole("customer");
  if (customer?.id) {
    return `customer:${customer.id}`;
  }

  return "guest";
};

export const getCustomerScopedStorageKey = (baseKey, owner = getActiveCustomerStorageOwner()) =>
  `${STORAGE_NAMESPACE}:${owner}:${baseKey}`;

export const getCustomerScopedItem = (baseKey, fallback = null) => {
  if (!isBrowser()) return fallback;

  const value = localStorage.getItem(getCustomerScopedStorageKey(baseKey));
  if (value === null) return fallback;
  return value;
};

export const getCustomerScopedJson = (baseKey, fallback) => {
  const value = getCustomerScopedItem(baseKey, null);
  if (value === null) return fallback;
  return safeParseJson(value, fallback);
};

export const emitCustomerStorageSync = (keys = CUSTOMER_SHARED_STATE_KEYS) => {
  if (!isBrowser()) return;

  window.dispatchEvent(
    new CustomEvent(CUSTOMER_STORAGE_SYNC_EVENT, {
      detail: {
        owner: getActiveCustomerStorageOwner(),
        keys,
      },
    })
  );
};

export const setCustomerScopedJson = (baseKey, value) => {
  if (!isBrowser()) return;

  localStorage.setItem(getCustomerScopedStorageKey(baseKey), JSON.stringify(value));
  emitCustomerStorageSync([baseKey]);
};

export const removeCustomerScopedItem = (baseKey) => {
  if (!isBrowser()) return;

  localStorage.removeItem(getCustomerScopedStorageKey(baseKey));
  emitCustomerStorageSync([baseKey]);
};

export const clearLegacySharedCustomerState = () => {
  if (!isBrowser()) return;

  CUSTOMER_SHARED_STATE_KEYS.forEach((key) => localStorage.removeItem(key));
  emitCustomerStorageSync(CUSTOMER_SHARED_STATE_KEYS);
};
