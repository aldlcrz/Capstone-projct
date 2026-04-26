"use client";
import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Lock, Eye, EyeOff, Loader2, CheckCircle2, X } from "lucide-react";
import { api } from "@/lib/api";

const SetPasswordModal = ({ isOpen, onClose, onSuccess, userEmail, token, role }) => {
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");

    if (password.length < 6) {
      setError("Password must be at least 6 characters.");
      return;
    }

    if (password !== confirmPassword) {
      setError("Passwords do not match.");
      return;
    }

    setLoading(true);
    try {
      // We need to use the token for this request
      await api.post(
        "/auth/set-password",
        { password },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );
      setIsSuccess(true);
      setTimeout(() => {
        onSuccess();
      }, 2000);
    } catch (err) {
      setError(err.response?.data?.message || "Failed to set password. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
        className="absolute inset-0 bg-black/40 backdrop-blur-sm"
      />
      
      <motion.div
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        className="relative w-full max-w-md bg-white rounded-[2.5rem] p-8 shadow-2xl border border-[#E5DDD5] overflow-hidden"
      >
        {/* Top Decorative Element */}
        <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[var(--rust)] to-transparent opacity-20" />

        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#FEF0EE] text-[var(--rust)] mb-4">
            <Lock className="w-6 h-6" />
          </div>
          <h2 className="text-xl font-serif font-black italic text-[var(--charcoal)] mb-2">Secure Your Account</h2>
          <p className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">
            Set a password for <span className="text-[var(--rust)]">{userEmail}</span>
          </p>
        </div>

        {isSuccess ? (
          <motion.div 
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className="text-center py-12"
          >
            <div className="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6">
              <CheckCircle2 className="w-10 h-10 text-green-500" />
            </div>
            <h3 className="text-lg font-bold text-gray-800 mb-2">Password Set Successfully!</h3>
            <p className="text-sm text-gray-500">Redirecting you to your dashboard...</p>
          </motion.div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-5">
            {error && (
              <motion.div
                initial={{ opacity: 0, y: -10 }}
                animate={{ opacity: 1, y: 0 }}
                className="p-4 rounded-2xl bg-red-50 border border-red-100 text-[10px] font-bold text-[var(--rust)] text-center uppercase tracking-wider"
              >
                {error}
              </motion.div>
            )}

            <div className="space-y-2 text-left">
              <label className="text-[10px] font-bold uppercase tracking-widest px-4 block text-[var(--muted)]">
                New Password
              </label>
              <div className="relative">
                <input
                  type={showPassword ? "text" : "password"}
                  className="w-full px-6 py-3.5 bg-[#F9F6F2] rounded-full border-1.5 border-transparent focus:border-[var(--rust)] focus:bg-white transition-all outline-none text-sm font-medium"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-6 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[var(--rust)] transition-colors"
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            <div className="space-y-2 text-left">
              <label className="text-[10px] font-bold uppercase tracking-widest px-4 block text-[var(--muted)]">
                Confirm Password
              </label>
              <input
                type={showPassword ? "text" : "password"}
                className="w-full px-6 py-3.5 bg-[#F9F6F2] rounded-full border-1.5 border-transparent focus:border-[var(--rust)] focus:bg-white transition-all outline-none text-sm font-medium"
                placeholder="••••••••"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                required
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-4 bg-[var(--bark)] hover:bg-[var(--rust)] text-white text-[10px] font-bold uppercase tracking-[0.2em] rounded-full shadow-lg shadow-black/10 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : "Set Password"}
            </button>

            <button
              type="button"
              onClick={onClose}
              className="w-full py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-colors"
            >
              Skip for now
            </button>
          </form>
        )}
      </motion.div>
    </div>
  );
};

export default SetPasswordModal;
