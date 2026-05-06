"use client";
import React from "react";
import { motion, AnimatePresence } from "framer-motion";
import { X, LogIn, UserPlus, ShieldAlert } from "lucide-react";
import { useRouter } from "next/navigation";

export default function AuthGateModal({ isOpen, onClose, message, redirectPath }) {
  const router = useRouter();

  const handleLogin = () => {
    const loginUrl = redirectPath ? `/login?redirect=${encodeURIComponent(redirectPath)}` : "/login";
    router.push(loginUrl);
    onClose();
  };

  const handleRegister = () => {
    const registerUrl = redirectPath ? `/register?redirect=${encodeURIComponent(redirectPath)}` : "/register";
    router.push(registerUrl);
    onClose();
  };

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-[1000] flex items-center justify-center p-4">
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="absolute inset-0 bg-[var(--charcoal)]/40 backdrop-blur-sm"
          />
          <motion.div
            initial={{ scale: 0.95, opacity: 0, y: 20 }}
            animate={{ scale: 1, opacity: 1, y: 0 }}
            exit={{ scale: 0.95, opacity: 0, y: 20 }}
            className="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden p-8 text-center"
          >
            <button
              onClick={onClose}
              className="absolute top-6 right-6 p-2 bg-[var(--input-bg)] rounded-xl text-[var(--muted)] hover:text-red-500 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>

            <div className="w-16 h-16 bg-[var(--rust)]/10 rounded-2xl flex items-center justify-center mx-auto mb-6 text-[var(--rust)]">
              <ShieldAlert className="w-8 h-8" />
            </div>

            <h2 className="font-serif text-2xl font-bold text-[var(--charcoal)] mb-3">
              Authentication Required
            </h2>
            <p className="text-sm text-[var(--muted)] font-medium leading-relaxed mb-8">
              {message || "Please log in or sign up to continue with this action."}
            </p>

            <div className="space-y-3">
              <button
                onClick={handleLogin}
                className="w-full bg-[var(--rust)] text-white py-4 rounded-xl font-bold uppercase text-[10px] tracking-[0.2em] shadow-lg shadow-[var(--rust)]/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3"
              >
                <LogIn className="w-4 h-4" /> Sign In
              </button>
              <button
                onClick={handleRegister}
                className="w-full bg-white border-2 border-[var(--border)] text-[var(--charcoal)] py-4 rounded-xl font-bold uppercase text-[10px] tracking-[0.2em] hover:bg-[var(--cream)] transition-all flex items-center justify-center gap-3"
              >
                <UserPlus className="w-4 h-4" /> Create Account
              </button>
            </div>

            <p className="mt-8 text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest opacity-60">
              Browse freely. Transact securely.
            </p>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
