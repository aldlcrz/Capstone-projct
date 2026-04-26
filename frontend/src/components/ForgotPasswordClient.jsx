"use client";

import React, { useState } from "react";
import Link from "next/link";
import { ArrowRight, CheckCircle2, Loader2, Mail, ShieldCheck, Lock, Eye, EyeOff, Hash } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import { api, getApiErrorMessage } from "@/lib/api";

const cardVariants = {
  hidden: { opacity: 0, y: 32, scale: 0.97 },
  visible: {
    opacity: 1,
    y: 0,
    scale: 1,
    transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] },
  },
};

export default function ForgotPasswordClient() {
  const router = useRouter();
  const [step, setStep] = useState(1); // 1: Email, 2: Code, 3: Password, 4: Success
  const [email, setEmail] = useState("");
  const [code, setCode] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [devResetCode, setDevResetCode] = useState("");

  const handleSendEmail = async (e) => {
    e.preventDefault();
    setError("");
    const normalizedEmail = email.trim().toLowerCase();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(normalizedEmail)) {
      setError("Please provide a valid email address.");
      return;
    }

    setLoading(true);
    try {
      const res = await api.post("/auth/forgot-password", { email: normalizedEmail });
      if (res.data.devResetCode) setDevResetCode(res.data.devResetCode);
      setStep(2);
    } catch (err) {
      setError(getApiErrorMessage(err, "Unable to send verification code."));
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyCode = async (e) => {
    e.preventDefault();
    setError("");
    if (code.length !== 6) {
      setError("Please enter the 6-digit code.");
      return;
    }

    setLoading(true);
    try {
      await api.post("/auth/verify-reset-code", { email: email.trim().toLowerCase(), code });
      setStep(3);
    } catch (err) {
      setError(getApiErrorMessage(err, "Invalid or expired verification code."));
    } finally {
      setLoading(false);
    }
  };

  const handleResetPassword = async (e) => {
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
      await api.post("/auth/reset-password", { 
        email: email.trim().toLowerCase(), 
        code, 
        password 
      });
      setStep(4);
    } catch (err) {
      setError(getApiErrorMessage(err, "Failed to reset password. Please try again."));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center p-6 relative overflow-hidden selection:bg-red-200"
      style={{ background: "var(--cream, #F7F3EE)" }}
    >
      {/* Background Orbs */}
      <div className="absolute top-0 right-0 w-[560px] h-[560px] rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl pointer-events-none" style={{ background: "var(--rust, #C0422A)", opacity: 0.04 }} />
      <div className="absolute bottom-0 left-0 w-[380px] h-[380px] rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl pointer-events-none" style={{ background: "var(--sand, #D4B896)", opacity: 0.12 }} />

      <motion.div
        variants={cardVariants}
        initial="hidden"
        animate="visible"
        className="w-full max-w-md relative z-10"
        style={{
          background: "white",
          borderRadius: "2.5rem",
          border: "1px solid var(--border, #E5DDD5)",
          padding: "2.5rem 2rem",
          boxShadow: "0 20px 60px rgba(60,40,20,0.08)",
        }}
      >
        {/* Header */}
        <div className="mb-8 text-center relative flex items-center justify-center">
          {step < 4 && (
             <button
                onClick={() => step > 1 ? setStep(step - 1) : router.back()}
                className="absolute left-0 p-2.5 bg-[#F9F6F2] hover:bg-[#EBDCCB] text-[var(--muted)] hover:text-[var(--rust)] rounded-xl transition-all border border-[#E5DDD5] shadow-sm transform hover:scale-105"
             >
                <ArrowRight className="w-4 h-4 rotate-180" />
             </button>
          )}
          <div>
            <Link href="/" className="inline-block mb-1">
              <span className="font-serif text-xl font-black italic tracking-tight" style={{ color: "var(--rust, #C0422A)" }}>LumbaRong</span>
            </Link>
            <div className="text-[9px] font-bold uppercase tracking-[0.3em]" style={{ color: "var(--muted, #8C7B70)" }}>
              {step === 4 ? "Success" : `Step ${step} of 3`}
            </div>
          </div>
        </div>

        {/* Error Alert */}
        <AnimatePresence mode="wait">
          {error && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: "auto" }}
              exit={{ opacity: 0, height: 0 }}
              className="mb-6 p-4 rounded-2xl text-[10px] font-bold text-center flex items-center justify-center gap-2 uppercase tracking-wider overflow-hidden"
              style={{ background: "#FEF0EE", border: "1px solid #F9D0C8", color: "var(--rust)" }}
            >
              <ShieldCheck className="w-4 h-4 shrink-0" /> {error}
            </motion.div>
          )}
        </AnimatePresence>

        {/* Steps Container */}
        <AnimatePresence mode="wait">
          {step === 1 && (
            <motion.form key="step1" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} onSubmit={handleSendEmail} className="space-y-6">
              <div className="text-center space-y-2">
                <h2 className="font-serif text-xl font-bold text-[var(--charcoal)]">Find your account</h2>
                <p className="text-sm text-[var(--muted)]">Enter your email address to receive a 6-digit verification code.</p>
              </div>
              <div className="space-y-2">
                 <label className="text-[10px] font-bold uppercase tracking-widest ml-5 text-[var(--muted)]">Email Address</label>
                 <div className="relative group">
                    <Mail className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--border)] group-focus-within:text-[var(--rust)] transition-colors" />
                    <input 
                      type="email" value={email} onChange={(e) => setEmail(e.target.value)} required placeholder="you@example.com"
                      className="w-full pl-14 pr-6 py-3.5 bg-[#F9F6F2] rounded-full border-1.5 border-transparent outline-none focus:border-[var(--rust)] focus:bg-white text-sm font-medium transition-all"
                    />
                 </div>
              </div>
              <button type="submit" disabled={loading} className="w-full bg-[var(--bark)] text-white py-4 rounded-full text-[10px] font-bold uppercase tracking-[0.2em] flex items-center justify-center gap-2 shadow-lg disabled:opacity-50">
                {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : <>Send Verification Code <ArrowRight className="w-4 h-4" /></>}
              </button>
            </motion.form>
          )}

          {step === 2 && (
            <motion.form key="step2" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} onSubmit={handleVerifyCode} className="space-y-6">
              <div className="text-center space-y-2">
                <h2 className="font-serif text-xl font-bold text-[var(--charcoal)]">Verify your identity</h2>
                <p className="text-sm text-[var(--muted)]">We sent a 6-digit code to <span className="font-bold text-[var(--charcoal)]">{email}</span>.</p>
              </div>
              <div className="space-y-2">
                 <label className="text-[10px] font-bold uppercase tracking-widest ml-5 text-[var(--muted)]">6-Digit Code</label>
                 <div className="relative group">
                    <Hash className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--border)] group-focus-within:text-[var(--rust)] transition-colors" />
                    <input 
                      type="text" value={code} onChange={(e) => setCode(e.target.value.replace(/\D/g, "").slice(0, 6))} required placeholder="000000"
                      className="w-full pl-14 pr-6 py-3.5 bg-[#F9F6F2] rounded-full border-1.5 border-transparent outline-none focus:border-[var(--rust)] focus:bg-white text-sm font-bold tracking-[0.5em] transition-all"
                    />
                 </div>
                 {devResetCode && (
                    <div className="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-center">
                       <p className="text-[9px] font-bold uppercase text-amber-800 tracking-widest mb-1">Development Mode</p>
                       <p className="text-sm font-black text-amber-900 tracking-[0.2em]">{devResetCode}</p>
                    </div>
                 )}
              </div>
              <button type="submit" disabled={loading} className="w-full bg-[var(--bark)] text-white py-4 rounded-full text-[10px] font-bold uppercase tracking-[0.2em] flex items-center justify-center gap-2 shadow-lg disabled:opacity-50">
                {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : <>Verify Code <ArrowRight className="w-4 h-4" /></>}
              </button>
              <div className="text-center">
                 <button type="button" onClick={handleSendEmail} className="text-[10px] font-bold uppercase tracking-widest text-[var(--rust)] hover:opacity-70">Resend Code</button>
              </div>
            </motion.form>
          )}

          {step === 3 && (
            <motion.form key="step3" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} onSubmit={handleResetPassword} className="space-y-6">
              <div className="text-center space-y-2">
                <h2 className="font-serif text-xl font-bold text-[var(--charcoal)]">Create new password</h2>
                <p className="text-sm text-[var(--muted)]">Choose a strong password to protect your account.</p>
              </div>
              
              <div className="space-y-4">
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest ml-5 text-[var(--muted)]">New Password</label>
                  <div className="relative group">
                    <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--border)] group-focus-within:text-[var(--rust)] transition-colors" />
                    <input 
                      type={showPassword ? "text" : "password"} value={password} onChange={(e) => setPassword(e.target.value)} required placeholder="••••••••"
                      className="w-full pl-14 pr-14 py-3.5 bg-[#F9F6F2] rounded-full border-1.5 border-transparent outline-none focus:border-[var(--rust)] focus:bg-white text-sm font-medium transition-all"
                    />
                    <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-6 top-1/2 -translate-y-1/2 text-[var(--muted)] hover:text-[var(--rust)]">
                      {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                    </button>
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest ml-5 text-[var(--muted)]">Confirm Password</label>
                  <div className="relative group">
                    <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--border)] group-focus-within:text-[var(--rust)] transition-colors" />
                    <input 
                      type={showPassword ? "text" : "password"} value={confirmPassword} onChange={(e) => setConfirmPassword(e.target.value)} required placeholder="••••••••"
                      className="w-full pl-14 pr-14 py-3.5 bg-[#F9F6F2] rounded-full border-1.5 border-transparent outline-none focus:border-[var(--rust)] focus:bg-white text-sm font-medium transition-all"
                    />
                  </div>
                </div>
              </div>

              <button type="submit" disabled={loading} className="w-full bg-[var(--bark)] text-white py-4 rounded-full text-[10px] font-bold uppercase tracking-[0.2em] flex items-center justify-center gap-2 shadow-lg disabled:opacity-50">
                {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : <>Reset Password <ArrowRight className="w-4 h-4" /></>}
              </button>
            </motion.form>
          )}

          {step === 4 && (
            <motion.div key="step4" initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} className="text-center space-y-8 py-4">
              <div className="w-20 h-20 bg-green-50 border-2 border-green-100 rounded-full flex items-center justify-center mx-auto text-green-500">
                <CheckCircle2 size={40} />
              </div>
              <div className="space-y-2">
                <h2 className="font-serif text-2xl font-bold text-[var(--charcoal)]">Password Updated</h2>
                <p className="text-sm text-[var(--muted)]">Your security key has been successfully changed. You can now log in with your new password.</p>
              </div>
              <Link href="/login" className="w-full bg-[var(--rust)] text-white py-4 rounded-full text-[10px] font-bold uppercase tracking-[0.2em] flex items-center justify-center gap-2 shadow-xl shadow-[var(--rust)]/20">
                Go To Login <ArrowRight size={16} />
              </Link>
            </motion.div>
          )}
        </AnimatePresence>

        {step < 4 && (
          <div className="mt-8 text-center">
            <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-[var(--muted)]">
              Remembered your password?{" "}
              <Link href="/login" className="text-[var(--rust)] hover:underline">Log In</Link>
            </p>
          </div>
        )}
      </motion.div>
    </div>
  );
}

