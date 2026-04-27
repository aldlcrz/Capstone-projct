"use client";
import React, { useEffect, useState } from "react";
import Link from "next/link";
import { Mail, Lock, ArrowRight, Loader2, Eye, EyeOff, ShieldCheck } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter, useSearchParams } from "next/navigation";
import { api, getApiErrorMessage, setSessionForRole } from "@/lib/api";
import { GoogleLogin } from "@react-oauth/google";
import { isGoogleAuthEnabled } from "@/lib/googleAuth";
import SetPasswordModal from "@/components/SetPasswordModal";

const containerVariants = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08, delayChildren: 0.2 } },
};

const itemVariants = {
  hidden: { opacity: 0, y: 18 },
  visible: {
    opacity: 1,
    y: 0,
    transition: { duration: 0.55, ease: [0.22, 1, 0.36, 1] },
  },
};

const cardVariants = {
  hidden: { opacity: 0, y: 32, scale: 0.97 },
  visible: {
    opacity: 1,
    y: 0,
    scale: 1,
    transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] },
  },
};

export default function LoginPage() {
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [reason, setReason] = useState("");
  const [accountStatus, setAccountStatus] = useState("");
  const [redirectUrl, setRedirectUrl] = useState("");
  const [showSetPassword, setShowSetPassword] = useState(false);
  const [tempAuthData, setTempAuthData] = useState(null);
  const [lockoutSeconds, setLockoutSeconds] = useState(0);
  const [forcedRestriction, setForcedRestriction] = useState(null);
  const router = useRouter();
  const searchParams = useSearchParams();

  // Read force-logout restriction stored by the API interceptor
  useEffect(() => {
    try {
      const raw = sessionStorage.getItem("lumbarong_account_restriction");
      if (raw) {
        const data = JSON.parse(raw);
        setForcedRestriction(data);
        sessionStorage.removeItem("lumbarong_account_restriction");
      }
    } catch (_) {}
  }, []);


  // Countdown timer for lockout
  useEffect(() => {
    if (lockoutSeconds <= 0) return;
    const timer = setInterval(() => {
      setLockoutSeconds((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          setError("");
          setAccountStatus("");
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, [lockoutSeconds]);


  useEffect(() => {
    const queryRedirect = searchParams.get("redirect");
    if (queryRedirect) {
      setRedirectUrl(queryRedirect);
      localStorage.setItem("returnUrl", queryRedirect);
    }
  }, [searchParams]);

  const handleLogin = async (e) => {
    e.preventDefault();
    setError("");

    if (!email.trim() || !password.trim()) {
      setError("Please enter both email and password.");
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      setError("Please provide a valid secure email address.");
      return;
    }

    setLoading(true);
    try {
      const response = await api.post("/auth/login", { email, password, });
      const { token, user } = response.data;

      const roleKey = user.role || "customer";
      setSessionForRole(roleKey, { token, user });

      const returnUrl = redirectUrl || localStorage.getItem("returnUrl");
      if (returnUrl) {
        localStorage.removeItem("returnUrl");
        window.location.replace(returnUrl);
        return;
      }

      if (user.role === "admin") window.location.replace("/admin/dashboard");
      else if (user.role === "seller") window.location.replace("/seller/dashboard");
      else window.location.replace("/home");
    } catch (err) {
      const data = err.response?.data;
      setError(getApiErrorMessage(err, "Authentication failed. Check your credentials."));

      if (data?.status === "locked") {
        setAccountStatus("locked");
        if (data?.lockedUntil) {
          const secondsLeft = Math.max(0, Math.ceil((new Date(data.lockedUntil) - Date.now()) / 1000));
          setLockoutSeconds(secondsLeft);
        }
      } else if (data?.status === "pending") {
        setAccountStatus("pending");
        setReason(data.reason || "");
      } else if (data?.status === "frozen" || data?.status === "rejected" || data?.status === "blocked") {
        setAccountStatus(data.status);
        setReason(data.reason || "");
      } else {
        setAccountStatus("");
        setReason("");
      }
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleAuth = async (response) => {
    setLoading(true);
    setError("");
    try {
      const res = await api.post("/auth/google", { credential: response.credential });
      const { token, user } = res.data;

      const roleKey = user.role || "customer";

      // If user hasn't set a password, show modal
      if (!user.hasPasswordSet) {
        setTempAuthData({ token, user, roleKey });
        setShowSetPassword(true);
        setLoading(false);
        return;
      }

      setSessionForRole(roleKey, { token, user });

      const returnUrl = redirectUrl || localStorage.getItem("returnUrl");
      if (returnUrl) {
        localStorage.removeItem("returnUrl");
        window.location.replace(returnUrl);
        return;
      }

      if (user.role === "admin") window.location.replace("/admin/dashboard");
      else if (user.role === "seller") window.location.replace("/seller/dashboard");
      else window.location.replace("/home");
    } catch (err) {
      setError(getApiErrorMessage(err, "Google authentication failed."));
    } finally {
      setLoading(false);
    }
  };

  const handleSetPasswordSuccess = () => {
    if (!tempAuthData) return;
    const { token, user, roleKey } = tempAuthData;

    // Update local user object
    const updatedUser = { ...user, hasPasswordSet: true };
    setSessionForRole(roleKey, { token, user: updatedUser });

    const returnUrl = redirectUrl || localStorage.getItem("returnUrl");
    if (returnUrl) {
      localStorage.removeItem("returnUrl");
      window.location.replace(returnUrl);
      return;
    }

    if (user.role === "admin") window.location.replace("/admin/dashboard");
    else if (user.role === "seller") window.location.replace("/seller/dashboard");
    else window.location.replace("/home");
  };

  const handleSkipSetPassword = () => {
    if (!tempAuthData) return;
    const { token, user, roleKey } = tempAuthData;

    setSessionForRole(roleKey, { token, user });

    const returnUrl = redirectUrl || localStorage.getItem("returnUrl");
    if (returnUrl) {
      localStorage.removeItem("returnUrl");
      window.location.replace(returnUrl);
      return;
    }

    if (user.role === "admin") window.location.replace("/admin/dashboard");
    else if (user.role === "seller") window.location.replace("/seller/dashboard");
    else window.location.replace("/home");
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center p-6 relative overflow-hidden selection:bg-red-200"
      style={{ background: "var(--cream, #F7F3EE)" }}
    >
      {/* Subtle warm blobs */}
      <div className="absolute top-0 right-0 w-[560px] h-[560px] rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl pointer-events-none"
        style={{ background: "var(--rust, #C0422A)", opacity: 0.04 }} />
      <div className="absolute bottom-0 left-0 w-[380px] h-[380px] rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl pointer-events-none"
        style={{ background: "var(--sand, #D4B896)", opacity: 0.12 }} />

      {/* Card */}
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
        <motion.div variants={containerVariants} initial="hidden" animate="visible">
          {/* Logo Container */}
          <motion.div variants={itemVariants} className="mb-10 text-center relative flex items-center justify-center">
            <button
              onClick={() => router.push('/')}
              className="absolute left-0 p-2.5 bg-[#F9F6F2] hover:bg-[#EBDCCB] text-[var(--muted)] hover:text-[var(--rust)] rounded-xl transition-all border border-[#E5DDD5] shadow-sm transform hover:scale-105"
              title="Go Back"
            >
              <ArrowRight className="w-4 h-4 rotate-180" />
            </button>
            <div>
              <Link href="/" className="inline-block mb-1">
                <span className="font-serif text-lg font-black italic tracking-tight"
                  style={{ color: "var(--rust, #C0422A)" }}>
                  LumbaRong
                </span>
              </Link>
              <div className="text-[11px] font-bold uppercase tracking-[0.3em]"
                style={{ color: "var(--muted, #8C7B70)" }}>
                Authentication Portal
              </div>
            </div>
          </motion.div>

          {/* Force-logout Restriction Banner */}
          {forcedRestriction && (
            <motion.div
              initial={{ opacity: 0, y: -10, scale: 0.97 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              className="mb-8 p-5 rounded-2xl text-left space-y-2"
              style={{
                background: forcedRestriction.status === "blocked" ? "#FEF0EE" : "#EFF6FF",
                border: `1px solid ${forcedRestriction.status === "blocked" ? "#F9D0C8" : "#BFDBFE"}`,
              }}
            >
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 shrink-0" style={{ color: forcedRestriction.status === "blocked" ? "var(--rust)" : "#3B82F6" }} />
                <span className="text-[10px] font-black uppercase tracking-wider" style={{ color: forcedRestriction.status === "blocked" ? "var(--rust)" : "#1D4ED8" }}>
                  {forcedRestriction.status === "blocked" ? "Account Terminated" : "Account Frozen"}
                </span>
              </div>
              <p className="text-xs font-bold text-gray-600 leading-relaxed">
                You have been logged out because your account has been {forcedRestriction.status === "blocked" ? "terminated" : "frozen"} by an administrator.
              </p>
              {forcedRestriction.reason && (
                <p className="text-[10px] font-bold italic text-gray-500 border-t border-gray-200 pt-2">
                  Reason: {forcedRestriction.reason}
                </p>
              )}
              {forcedRestriction.status === "frozen" && (
                <p className="text-[10px] text-blue-600 font-bold">
                  Contact support or wait for an admin to lift the restriction to regain access.
                </p>
              )}
            </motion.div>
          )}

          <AnimatePresence>
            {error && (
              <motion.div
                initial={{ opacity: 0, y: -10, scale: 0.97 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: -8, scale: 0.97 }}
                transition={{ duration: 0.3 }}
                className="mb-8 p-4 rounded-2xl text-[10px] font-bold text-center flex items-center justify-center gap-2 uppercase tracking-wider"
                style={{ background: "#FEF0EE", border: "1px solid #F9D0C8", color: "var(--rust)" }}
              >
                <div className="flex flex-col items-center gap-2">
                  <div className="flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4 shrink-0" /> {error}
                  </div>
                  {accountStatus === "locked" && lockoutSeconds > 0 && (
                    <div className="mt-1 text-[11px] font-bold text-red-600 normal-case">
                      ⏱ Try again in{" "}
                      <span className="tabular-nums">
                        {Math.floor(lockoutSeconds / 60)}:{String(lockoutSeconds % 60).padStart(2, "0")}
                      </span>
                    </div>
                  )}
                  {accountStatus === "pending" && (
                    <div className="mt-1 normal-case font-bold text-amber-600 italic opacity-90 border-t border-[var(--rust)]/10 pt-1">
                      {reason || "Awaiting admin approval."}
                    </div>
                  )}
                  {(accountStatus === "frozen" || accountStatus === "rejected" || accountStatus === "blocked") && reason && (
                    <div className="mt-1 normal-case font-bold text-gray-500 italic opacity-80 border-t border-[var(--rust)]/10 pt-1">
                      {accountStatus === "rejected" ? "Feedback" : "Violation"}: {reason}
                    </div>
                  )}
                </div>
              </motion.div>
            )}
          </AnimatePresence>

          <form onSubmit={handleLogin} className="space-y-6" autoComplete="off">
            {/* Email */}
            <motion.div variants={itemVariants} className="space-y-2">
              <label className="text-[10px] font-bold uppercase tracking-widest px-5 block"
                style={{ color: "var(--muted, #8C7B70)" }}>
                Email Address
              </label>
              <div className="relative group">
                <Mail className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 transition-colors duration-300 group-focus-within:text-[color:var(--rust)]"
                  style={{ color: "var(--border, #E5DDD5)" }} />
                <input
                  type="email"
                  name="email"
                  autoComplete="off"
                  className="w-full text-sm font-medium outline-none transition-all duration-300 placeholder:text-gray-300"
                  placeholder=""
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  style={{
                    paddingLeft: "3rem", paddingRight: "1.25rem", paddingTop: "0.75rem", paddingBottom: "0.75rem",
                    background: "var(--input-bg, #F9F6F2)",
                    borderRadius: "9999px",
                    border: "1.5px solid transparent",
                    color: "var(--charcoal, #1C1917)",
                    fontSize: "0.8rem",
                  }}
                  onFocus={(e) => { e.target.style.borderColor = "var(--rust)"; e.target.style.background = "white"; }}
                  onBlur={(e) => { e.target.style.borderColor = "transparent"; e.target.style.background = "var(--input-bg, #F9F6F2)"; }}
                />
              </div>
            </motion.div>

            {/* Password */}
            <motion.div variants={itemVariants} className="space-y-2">
              <div className="flex justify-between items-center px-5">
                <label className="text-[10px] font-bold uppercase tracking-widest"
                  style={{ color: "var(--muted, #8C7B70)" }}>
                  Password
                </label>
                <Link href="/forgot-password"
                  className="text-[10px] font-bold uppercase tracking-widest hover:opacity-70 transition-opacity"
                  style={{ color: "var(--rust, #C0422A)" }}>
                  Forgot Password?
                </Link>
              </div>
              <div className="relative group">
                <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 transition-colors duration-300"
                  style={{ color: "var(--border, #E5DDD5)" }} />
                <input
                  type={showPassword ? "text" : "password"}
                  name="password"
                  autoComplete="new-password"
                  className="w-full text-sm font-medium outline-none transition-all duration-300 placeholder:text-gray-300"
                  placeholder="••••••••••••"
                  required
                  value={password}
                  maxLength={20}
                  onChange={(e) => setPassword(e.target.value)}
                  style={{
                    paddingLeft: "3rem", paddingRight: "3rem", paddingTop: "0.75rem", paddingBottom: "0.75rem",
                    background: "var(--input-bg, #F9F6F2)",
                    borderRadius: "9999px",
                    border: "1.5px solid transparent",
                    color: "var(--charcoal, #1C1917)",
                    fontSize: "0.8rem",
                  }}
                  onFocus={(e) => { e.target.style.borderColor = "var(--rust)"; e.target.style.background = "white"; }}
                  onBlur={(e) => { e.target.style.borderColor = "transparent"; e.target.style.background = "var(--input-bg, #F9F6F2)"; }}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-6 top-1/2 -translate-y-1/2 transition-colors duration-300 hover:opacity-70"
                  style={{ color: "var(--muted, #8C7B70)" }}
                  tabIndex="-1"
                >
                  <AnimatePresence mode="wait" initial={false}>
                    <motion.span
                      key={showPassword ? "off" : "on"}
                      initial={{ opacity: 0, rotate: -15, scale: 0.8 }}
                      animate={{ opacity: 1, rotate: 0, scale: 1 }}
                      exit={{ opacity: 0, rotate: 15, scale: 0.8 }}
                      transition={{ duration: 0.2 }}
                    >
                      {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </motion.span>
                  </AnimatePresence>
                </button>
              </div>
            </motion.div>



            {/* Submit */}
            <motion.div variants={itemVariants}>
              <motion.button
                type="submit"
                disabled={loading || lockoutSeconds > 0}
                whileHover={{ scale: (loading || lockoutSeconds > 0) ? 1 : 1.02 }}
                whileTap={{ scale: (loading || lockoutSeconds > 0) ? 1 : 0.97 }}
                transition={{ type: "spring", stiffness: 400, damping: 20 }}
                className="w-full text-white text-[10px] font-bold uppercase tracking-[0.2em] flex items-center justify-center gap-3 mt-4 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                style={{
                  padding: "0.875rem",
                  borderRadius: "9999px",
                  background: "var(--bark, #3D2B1F)",
                  boxShadow: "0 6px 20px rgba(60,43,31,0.18)",
                }}
                onMouseEnter={(e) => { e.currentTarget.style.background = "var(--rust, #C0422A)"; }}
                onMouseLeave={(e) => { e.currentTarget.style.background = "var(--bark, #3D2B1F)"; }}
              >
                <AnimatePresence mode="wait" initial={false}>
                  {loading ? (
                    <motion.span key="loading" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: 0.2 }}>
                      <Loader2 className="w-5 h-5 animate-spin" />
                    </motion.span>
                  ) : (
                    <motion.span key="label" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: 0.2 }} className="flex items-center gap-3">
                      Log-In <ArrowRight className="w-4 h-4" />
                    </motion.span>
                  )}
                </AnimatePresence>
              </motion.button>
            </motion.div>

            <motion.div variants={itemVariants} className="pt-2">
              <div className="flex items-center gap-4 mb-6">
                <div className="h-px flex-1 bg-[#E5DDD5]" />
                <span className="text-[9px] font-bold text-[#8C7B70] uppercase tracking-widest">social gateway</span>
                <div className="h-px flex-1 bg-[#E5DDD5]" />
              </div>

              {isGoogleAuthEnabled ? (
                <div className="flex justify-center">
                  <GoogleLogin
                    onSuccess={handleGoogleAuth}
                    onError={() => setError("Google Sign-in failed. Please try again.")}
                    theme="outline"
                    shape="pill"
                    width="100%"
                    text="signin_with"
                    ux_mode="popup"
                  />
                </div>
              ) : (
                <p className="text-center text-[10px] font-medium" style={{ color: "var(--muted, #8C7B70)" }}>
                  Google Sign-in is unavailable until `NEXT_PUBLIC_GOOGLE_CLIENT_ID` is configured.
                </p>
              )}
            </motion.div>
          </form>

          {/* Footer link */}
          <motion.div variants={itemVariants} className="mt-10 text-center">
            <p className="text-[10px] font-bold uppercase tracking-widest"
              style={{ color: "var(--muted, #8C7B70)" }}>
              Don&apos;t have an account?{" "}
              <Link href="/register"
                className="hover:opacity-70 transition-opacity ml-1"
                style={{ color: "var(--rust, #C0422A)" }}>
                Create Account
              </Link>
            </p>
          </motion.div>
        </motion.div>
      </motion.div>

      <AnimatePresence>
        {showSetPassword && tempAuthData && (
          <SetPasswordModal
            isOpen={showSetPassword}
            onClose={handleSkipSetPassword}
            onSuccess={handleSetPasswordSuccess}
            userEmail={tempAuthData.user.email}
            token={tempAuthData.token}
            role={tempAuthData.roleKey}
          />
        )}
      </AnimatePresence>
    </div>
  );
}
