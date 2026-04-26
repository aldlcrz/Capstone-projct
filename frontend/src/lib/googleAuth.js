export const GOOGLE_CLIENT_ID =
  process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID?.trim() || "";

export const isGoogleAuthEnabled = GOOGLE_CLIENT_ID.length > 0;
