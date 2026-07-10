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

/**
 * Syncs guest data to the current authenticated customer account.
 * This should be called immediately after successful login.
 */
export const syncGuestDataToCustomer = () => {
  if (!isBrowser()) return;

  const customer = getStoredUserForRole("customer");
  if (!customer?.id) return;

  const guestOwner = "guest";
  const customerOwner = `customer:${customer.id}`;

  CUSTOMER_SHARED_STATE_KEYS.forEach((key) => {
    const guestKey = getCustomerScopedStorageKey(key, guestOwner);
    const guestData = localStorage.getItem(guestKey);

    if (guestData && guestData !== "null" && guestData !== "[]" && guestData !== "{}") {
      const customerKey = getCustomerScopedStorageKey(key, customerOwner);
      
      // If customer already has data (e.g. cart), we might want to merge
      if (key === "cart") {
        const guestCart = safeParseJson(guestData, []);
        const existingCustomerData = localStorage.getItem(customerKey);
        const customerCart = safeParseJson(existingCustomerData, []);
        
        // Merge carts - avoid duplicates by ID, size, and variation
        const mergedCart = [...customerCart];
        guestCart.forEach(gItem => {
          const exists = mergedCart.find(cItem => 
            cItem.id === gItem.id && 
            cItem.size === gItem.size && 
            (cItem.variation || "") === (gItem.variation || "")
          );
          if (!exists) {
            mergedCart.push(gItem);
          } else {
            // Update quantity if exists? Or just keep customer's one?
            // Usually we add guest quantity to customer's if we want to be thorough.
            exists.quantity = (exists.quantity || 1) + (gItem.quantity || 1);
          }
        });

        localStorage.setItem(customerKey, JSON.stringify(mergedCart));
      } else {
        // For other keys (checkout_items, etc), guest takes precedence if not present?
        // Actually usually we just overwrite or ignore.
        localStorage.setItem(customerKey, guestData);
      }

      // Clear guest data after sync
      localStorage.removeItem(guestKey);
    }
  });

  emitCustomerStorageSync();
};

