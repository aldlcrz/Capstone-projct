import { BACKEND_URL } from "./api";
const FALLBACK_IMAGE = "/images/placeholder.png";
const BACKEND_ORIGIN = (() => {
  try {
    return new URL(BACKEND_URL).origin;
  } catch (error) {
    return BACKEND_URL;
  }
})();

const LOCAL_HOSTS = new Set(["localhost", "127.0.0.1", "10.0.2.2"]);

export const resolveBackendImageUrl = (value, fallback = null) => {
  if (value == null) return fallback;
  if (value && typeof value === "object" && value.url) {
    return resolveBackendImageUrl(value.url, fallback);
  }
  if (typeof value !== "string") return fallback;

  const trimmed = value.trim();
  if (!trimmed) return fallback;
  if (/^https?:\/\//i.test(trimmed)) {
    try {
      const parsed = new URL(trimmed);
      if (LOCAL_HOSTS.has(parsed.hostname)) {
        return `${BACKEND_ORIGIN}${parsed.pathname}${parsed.search}${parsed.hash}`;
      }
    } catch (error) {}
    return trimmed;
  }
  if (trimmed.startsWith("data:") || trimmed.startsWith("blob:")) {
    return trimmed;
  }
  if (trimmed.startsWith("/uploads/")) {
    return `${BACKEND_ORIGIN}${trimmed}`;
  }
  if (trimmed.startsWith("/")) {
    return trimmed;
  }

  const normalized = trimmed.replace(/\\/g, "/").replace(/^\.?\//, "");
  if (normalized.startsWith("uploads/")) {
    return `${BACKEND_ORIGIN}/${normalized}`;
  }

  return `/${normalized}`;
};

const parseImageList = (value) => {
  if (Array.isArray(value)) return value;
  if (typeof value !== "string") return [];

  const trimmed = value.trim();
  if (!trimmed) return [];

  try {
    const parsed = JSON.parse(trimmed);
    if (Array.isArray(parsed)) return parsed;
    if (typeof parsed === "string" && parsed.trim()) return [parsed];
  } catch (error) {}

  if (trimmed.startsWith("[") || trimmed.endsWith("]")) return [];

  return [trimmed];
};

const normalizeImageValue = (value) => {
  return resolveBackendImageUrl(value, null);
};

export function normalizeProductImages(image) {
  return parseImageList(image)
    .map((val) => {
      // If it's already an object, resolve the internal URL but keep the variation
      if (val && typeof val === "object" && val.url) {
        return {
          url: resolveBackendImageUrl(val.url, null),
          variation: val.variation || "Original"
        };
      }
      
      // If it's a string, resolve it and assign a default variation
      const resolved = resolveBackendImageUrl(val, null);
      if (resolved) {
        return {
          url: resolved,
          variation: "Original"
        };
      }
      return null;
    })
    .filter(Boolean);
}

export function normalizeProductSizes(sizes) {
  return parseImageList(sizes).filter(Boolean);
}

export function getProductImageSrc(image, fallback = FALLBACK_IMAGE) {
  const images = normalizeProductImages(image);
  if (images.length === 0) return fallback;
  let first = images[0];
  // Unwrap object wrappers until we get a plain string URL
  while (first && typeof first === 'object' && first.url) {
    first = first.url;
  }
  return resolveBackendImageUrl(first, fallback);
}
