"use client";

import React, { useState } from "react";
import Link from "next/link";
import { ArrowRight, CheckCircle2, Loader2, Mail, ShieldCheck } from "lucide-react";
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

const normalizeResetLink = (value) => {
  if (!value) return "";

  try {
    if (typeof window !== "undefined") {
      const parsedUrl = new URL(value, window.location.origin);
      return `${parsedUrl.pathname}${parsedUrl.search}${parsedUrl.hash}`;
    }

    const parsedUrl = new URL(value);
    return `${parsedUrl.pathname}${parsedUrl.search}${parsedUrl.hash}`;
  } catch (error) {
    return value;
  }
};

export default function ForgotPasswordClient() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [submitted, setSubmitted] = useState(false);
  const [otp, setOtp] = useState("");
  const [verifying, setVerifying] = useState(false);
  const [devOtp, setDevOtp] = useState("");
  const [retryAfter, setRetryAfter] = useState(null);
  const [timeLeft, setTimeLeft] = useState("");

  // Update countdown display when email changes or timer ticks
  React.useEffect(() => {
    if (typeof window === 'undefined') return;
    
    const checkLimit = () => {
      const savedRetries = JSON.parse(localStorage.getItem("password_reset_retries") || "{}");
      const normalizedEmail = email.trim().toLowerCase();
      
      if (normalizedEmail && savedRetries[normalizedEmail]) {
        const resetTime = new Date(savedRetries[normalizedEmail]).getTime();
        if (resetTime > Date.now()) {
          setRetryAfter(resetTime);
        } else {
          const newRetries = { ...savedRetries };
          delete newRetries[normalizedEmail];
          localStorage.setItem("password_reset_retries", JSON.stringify(newRetries));
          setRetryAfter(null);
        }
      } else {
        setRetryAfter(null);
      }
    };

    checkLimit();
  }, [email]);

  // Timer tick logic
  React.useEffect(() => {
    if (!retryAfter) {
      setTimeLeft("");
      return () => {};
    }

    const timer = setInterval(() => {
      const now = Date.now();
      const distance = retryAfter - now;

      if (distance <= 0) {
        setRetryAfter(null);
        setTimeLeft("");
        setError("");
        
        const savedRetries = JSON.parse(localStorage.getItem("password_reset_retries") || "{}");
        const normalizedEmail = email.trim().toLowerCase();
        if (normalizedEmail) {
          delete savedRetries[normalizedEmail];
          localStorage.setItem("password_reset_retries", JSON.stringify(savedRetries));
        }
        
        clearInterval(timer);
      } else {
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        setTimeLeft(`${minutes}:${seconds < 10 ? "0" : ""}${seconds}`);
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [retryAfter, email]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError("");

    if (retryAfter) {
      setError(`Too many requests for this email. Try again in ${timeLeft}`);
      return;
    }

    const normalizedEmail = email.trim().toLowerCase();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(normalizedEmail)) {
      setError("Please provide a valid secure email address.");
      return;
    }

    setLoading(true);
    try {
      const response = await api.post("/auth/forgot-password", { email: normalizedEmail });
      setDevOtp(response.data?.devOtp || "");
      setSubmitted(true);
    } catch (requestError) {
      if (requestError.response?.status === 429) {
        const resetTime = new Date(requestError.response.data.retryAfter).getTime();
        setRetryAfter(resetTime);
        
        // Save specifically for this email
        const savedRetries = JSON.parse(localStorage.getItem("password_reset_retries") || "{}");
        savedRetries[normalizedEmail] = requestError.response.data.retryAfter;
        localStorage.setItem("password_reset_retries", JSON.stringify(savedRetries));
        
        setError(`Too many requests for this email. Try again in calculating...`);
      } else {
        setError(getApiErrorMessage(requestError, "Unable to start password reset right now."));
      }
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async (event) => {
    event.preventDefault();
    setError("");

    if (otp.length !== 6) {
      setError("Please enter the 6-digit code.");
      return;
    }

    setVerifying(true);
    try {
      const response = await api.post("/auth/verify-otp", { 
        email: email.trim().toLowerCase(), 
        otp 
      });
      // Redirect to reset password page with the verified token
      router.push(`/reset-password?token=${response.data.resetToken}`);
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, "Invalid or expired OTP. Please try again."));
    } finally {
      setVerifying(false);
    }
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center p-6 relative overflow-hidden selection:bg-red-200"
      style={{ background: "var(--cream, #F7F3EE)" }}
    >
      <div
        className="absolute top-0 right-0 w-[560px] h-[560px] rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl pointer-events-none"
        style={{ background: "var(--rust, #C0422A)", opacity: 0.04 }}
      />
      <div
        className="absolute bottom-0 left-0 w-[380px] h-[380px] rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl pointer-events-none"
        style={{ background: "var(--sand, #D4B896)", opacity: 0.12 }}
      />

      <motion.div
        variants={cardVariants}
        initial="hidden"
        animate="visible"
        className="w-full max-w-md relative z-10"
        style={{
          background: "white",
          borderRadius: "2.5rem",
          border: "1px solid var(--border, #E5DDD5)",
          padding: "2rem",
          boxShadow: "0 20px 60px rgba(60,40,20,0.08)",
        }}
      >
        <div className="mb-10 text-center relative flex items-center justify-center">
          <button
            onClick={() => {
              if (submitted) {
                setSubmitted(false);
                setOtp("");
              } else {
                router.back();
              }
            }}
            className="absolute left-0 p-2.5 bg-[#F9F6F2] hover:bg-[#EBDCCB] text-[var(--muted)] hover:text-[var(--rust)] rounded-xl transition-all border border-[#E5DDD5] shadow-sm transform hover:scale-105"
            title="Go Back"
          >
            <ArrowRight className="w-4 h-4 rotate-180" />
          </button>
          <div>
            <Link href="/" className="inline-block mb-1">
              <span
                className="font-serif text-xl font-black italic tracking-tight"
                style={{ color: "var(--rust, #C0422A)" }}
              >
                LumbaRong
              </span>
            </Link>
            <div
              className="text-[9px] font-bold uppercase tracking-[0.3em]"
              style={{ color: "var(--muted, #8C7B70)" }}
            >
              Password Recovery
            </div>
          </div>
        </div>

        <AnimatePresence>
          {(error || retryAfter) && (
            <motion.div
              initial={{ opacity: 0, y: -10, scale: 0.97 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: -8, scale: 0.97 }}
              transition={{ duration: 0.3 }}
              className="mb-8 p-4 rounded-2xl text-[10px] font-bold text-center flex items-center justify-center gap-2 uppercase tracking-wider"
              style={{ background: "#FEF0EE", border: "1px solid #F9D0C8", color: "var(--rust)" }}
            >
              <ShieldCheck className="w-4 h-4 shrink-0" /> 
              {retryAfter ? `Too many requests. Try again in ${timeLeft}` : error}
            </motion.div>
          )}
        </AnimatePresence>

        {submitted ? (
          <form onSubmit={handleVerifyOtp} className="space-y-6" autoComplete="off">
            <div className="space-y-3 text-center">
              <h1 className="font-serif text-xl font-bold text-[var(--charcoal)]">Enter Verification Code</h1>
              <p className="text-sm leading-6 text-[var(--muted)]">
                We've sent a 6-digit code to <span className="font-semibold text-[var(--charcoal)]">{email}</span>.
              </p>
            </div>

            <div className="space-y-2">
              <label
                className="text-[10px] font-bold uppercase tracking-widest ml-5 block"
                style={{ color: "var(--muted, #8C7B70)" }}
              >
                6-Digit OTP
              </label>
              <div className="relative group">
                <ShieldCheck
                  className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 transition-colors duration-300 group-focus-within:text-[color:var(--rust)]"
                  style={{ color: "var(--border, #E5DDD5)" }}
                />
                <input
                  type="text"
                  maxLength={6}
                  placeholder="Enter 6-digit code"
                  className="w-full text-center text-lg font-bold tracking-[0.5em] outline-none transition-all duration-300 placeholder:text-gray-300 placeholder:tracking-normal"
                  required
                  value={otp}
                  onChange={(event) => {
                    const val = event.target.value.replace(/\D/g, "");
                    if (val.length <= 6) setOtp(val);
                  }}
                  style={{
                    paddingLeft: "3rem",
                    paddingRight: "1.25rem",
                    paddingTop: "0.875rem",
                    paddingBottom: "0.875rem",
                    background: "var(--input-bg, #F9F6F2)",
                    borderRadius: "9999px",
                    border: "1.5px solid transparent",
                    color: "var(--charcoal, #1C1917)",
                  }}
                  onFocus={(event) => {
                    event.target.style.borderColor = "var(--rust)";
                    event.target.style.background = "white";
                  }}
                  onBlur={(event) => {
                    event.target.style.borderColor = "transparent";
                    event.target.style.background = "var(--input-bg, #F9F6F2)";
                  }}
                />
              </div>
            </div>

            <motion.button
              type="submit"
              disabled={verifying}
              whileHover={{ scale: verifying ? 1 : 1.02 }}
              whileTap={{ scale: verifying ? 1 : 0.97 }}
              transition={{ type: "spring", stiffness: 400, damping: 20 }}
              className="w-full text-white text-[10px] font-bold uppercase tracking-[0.2em] flex items-center justify-center gap-3 mt-4 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
              style={{
                padding: "0.875rem",
                borderRadius: "9999px",
                background: "var(--bark, #3D2B1F)",
                boxShadow: "0 6px 20px rgba(60,43,31,0.18)",
              }}
            >
              {verifying ? <Loader2 className="w-5 h-5 animate-spin" /> : <>Verify Code <ArrowRight className="w-4 h-4" /></>}
            </motion.button>

            {devOtp && (
              <p className="text-[10px] text-center text-[var(--muted)] italic">
                Local Dev Code: <span className="font-bold text-[var(--rust)]">{devOtp}</span>
              </p>
            )}

            <button
              type="button"
              onClick={() => {
                setSubmitted(false);
                setOtp("");
              }}
              className="w-full text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--muted)] hover:text-[var(--rust)] transition-colors"
            >
              Didn't receive a code? <span className="text-[var(--rust)]">Try again</span>
            </button>
          </form>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-6" autoComplete="off">
            <div className="space-y-3 text-center">
              <h1 className="font-serif text-xl font-bold text-[var(--charcoal)]">Forgot your password?</h1>
              <p className="text-sm leading-6 text-[var(--muted)]">
                Enter your account email and we will send you a verification code.
              </p>
            </div>

            <div className="space-y-2">
              <label
                className="text-[10px] font-bold uppercase tracking-widest ml-5 block"
                style={{ color: "var(--muted, #8C7B70)" }}
              >
                Email Address
              </label>
              <div className="relative group">
                <Mail
                  className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 transition-colors duration-300 group-focus-within:text-[color:var(--rust)]"
                  style={{ color: "var(--border, #E5DDD5)" }}
                />
                <input
                  type="email"
                  name="forgot_password_email"
                  autoComplete="off"
                  className="w-full text-sm font-medium outline-none transition-all duration-300 placeholder:text-gray-300"
                  required
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  style={{
                    paddingLeft: "3rem",
                    paddingRight: "1.25rem",
                    paddingTop: "0.75rem",
                    paddingBottom: "0.75rem",
                    background: "var(--input-bg, #F9F6F2)",
                    borderRadius: "9999px",
                    border: "1.5px solid transparent",
                    color: "var(--charcoal, #1C1917)",
                    fontSize: "0.8rem",
                  }}
                  onFocus={(event) => {
                    event.target.style.borderColor = "var(--rust)";
                    event.target.style.background = "white";
                  }}
                  onBlur={(event) => {
                    event.target.style.borderColor = "transparent";
                    event.target.style.background = "var(--input-bg, #F9F6F2)";
                  }}
                />
              </div>
            </div>

            <motion.button
              type="submit"
              disabled={loading}
              whileHover={{ scale: loading ? 1 : 1.02 }}
              whileTap={{ scale: loading ? 1 : 0.97 }}
              transition={{ type: "spring", stiffness: 400, damping: 20 }}
              className="w-full text-white text-[10px] font-bold uppercase tracking-[0.2em] flex items-center justify-center gap-3 mt-4 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
              style={{
                padding: "0.875rem",
                borderRadius: "9999px",
                background: "var(--bark, #3D2B1F)",
                boxShadow: "0 6px 20px rgba(60,43,31,0.18)",
              }}
            >
              {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : <>Send Verification Code <ArrowRight className="w-4 h-4" /></>}
            </motion.button>

            <div className="text-center text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--muted)]">
              Remembered it?{" "}
              <Link href="/login" className="text-[var(--rust)] hover:opacity-70 transition-opacity">
                Back To Login
              </Link>
            </div>
          </form>
        )}
      </motion.div>
    </div>
  );
}
