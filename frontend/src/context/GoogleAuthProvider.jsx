"use client";
import React from "react";
import { GoogleOAuthProvider } from "@react-oauth/google";
import { GOOGLE_CLIENT_ID } from "@/lib/googleAuth";

export default function GoogleAuthProvider({ children }) {
  if (!GOOGLE_CLIENT_ID) {
    if (typeof window !== "undefined") {
      console.warn("NEXT_PUBLIC_GOOGLE_CLIENT_ID is missing. Google Login is disabled.");
    }
    return <>{children}</>;
  }

  return (
    <GoogleOAuthProvider clientId={GOOGLE_CLIENT_ID}>
      {children}
    </GoogleOAuthProvider>
  );
}
