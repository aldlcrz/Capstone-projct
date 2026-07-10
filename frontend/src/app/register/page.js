"use client";
import React, { useState } from "react";
import Link from "next/link";
import { User, Mail, Lock, Upload, ArrowRight, Loader2, CheckCircle2, ShieldCheck, ShoppingBag, Eye, EyeOff } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { usePathname, useRouter } from "next/navigation";
import { api, getApiErrorMessage } from "@/lib/api";
import { validateImageFile } from "@/lib/imageUploadValidation";
import {
  INPUT_LIMITS,
  sanitizePersonNameInput,
  sanitizePhoneInput,
  validatePersonName,
  validatePhilippineMobileNumber,
} from "@/lib/inputValidation";
import { GoogleLogin } from "@react-oauth/google";
import { isGoogleAuthEnabled } from "@/lib/googleAuth";

const cardVariants = {
  hidden: { opacity: 0, y: 32, scale: 0.97 },
  visible: { opacity: 1, y: 0, scale: 1, transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] } },
};

const containerVariants = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08, delayChildren: 0.2 } },
};

const itemVariants = {
  hidden: { opacity: 0, y: 18 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.55, ease: [0.22, 1, 0.36, 1] } },
};

export default function RegisterPage() {
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({ name: "", email: "", password: "", confirmPassword: "", role: "customer" });
  const [sellerData, setSellerData] = useState({ mobileNumber: "", gcashNumber: "", isAdult: false });
  const [error, setError] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const router = useRouter();

  const handleNext = async (e) => {
    e.preventDefault();
    setError("");

    try {
      validatePersonName(formData.name, "Username");
    } catch (err) {
      setError(err.message);
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!formData.email.trim() || !emailRegex.test(formData.email)) {
      setError("Please provide a valid secure email address.");
      return;
    }

    if (formData.email.length > INPUT_LIMITS.email) {
      setError("Email address is too long.");
      return;
    }

    if (formData.password.length < INPUT_LIMITS.passwordMin) {
      setError("Security key must be at least 6 characters.");
      return;
    }

    if (formData.password.length > INPUT_LIMITS.passwordMax) {
      setError("Security key cannot exceed 20 characters.");
      return;
    }

    if (formData.password !== formData.confirmPassword) {
      setError("Passwords do not match.");
      return;
    }

    setLoading(true);
    const data = new FormData();
    data.append("name", formData.name);
    data.append("email", formData.email);
    data.append("password", formData.password);
    data.append("role", "customer");

    try {
      await api.post("/auth/register", data, { headers: { "Content-Type": "multipart/form-data" } });
      setStep(2); // Go to success step (which will be index 2 now)
    } catch (error) {
      setError(getApiErrorMessage(error, "Registration failed. Please audit your information."));
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleAuth = async (response) => {
    setLoading(true);
    setError("");
    try {
      const res = await api.post("/auth/google", { credential: response.credential });
      const { token, user, isNewUser } = res.data;
      
      const roleKey = user.role || "customer";
      localStorage.setItem(`${roleKey}_token`, token);
      localStorage.setItem(`${roleKey}_user`, JSON.stringify(user));

      if (isNewUser) {
        // Success! Step 3 for new users
        setStep(3);
      } else {
        // Existing user, go straight to dashboard
        router.push(roleKey === "admin" ? "/admin" : roleKey === "seller" ? "/seller" : "/customer");
      }
    } catch (err) {
      setError(getApiErrorMessage(err, "Google authentication failed."));
    } finally {
      setLoading(false);
    }
  };

  // We can't use useGoogleLogin for the "Credential" response easily without the standard button 
  // if we want the "Full Name" etc automatically without extra scopes.
  // Actually, GoogleLogin component is better for getting the ID Token (credential) directly.
  // But let's use a trick or just use the standard button for reliability.
  // For now, I'll keep the custom UI and use the "CredentialResponse" flow.

  const inputStyle = {
    paddingLeft: "3rem", paddingRight: "1.25rem", paddingTop: "0.75rem", paddingBottom: "0.75rem",
    background: "var(--input-bg, #F9F6F2)", borderRadius: "9999px",
    border: "1.5px solid transparent", color: "var(--charcoal, #1C1917)",
    width: "100%", fontSize: "0.8rem", fontWeight: 500, outline: "none", transition: "all 0.3s",
  };

  const handleFocus = (e) => { e.target.style.borderColor = "var(--rust)"; e.target.style.background = "white"; };
  const handleBlur = (e) => { e.target.style.borderColor = "transparent"; e.target.style.background = "var(--input-bg, #F9F6F2)"; };

  const labelStyle = { color: "var(--muted, #8C7B70)", fontSize: "10px", fontWeight: 700, textTransform: "uppercase", letterSpacing: "0.12em", marginLeft: "1.25rem", display: "block", marginBottom: "0.5rem" };

  return (
    <div className="min-h-screen flex items-center justify-center p-6 relative overflow-hidden selection:bg-red-200"
      style={{ background: "var(--cream, #F7F3EE)" }}>
      {/* Warm blobs */}
      <div className="absolute top-0 right-0 w-[500px] h-[500px] rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl pointer-events-none"
        style={{ background: "var(--rust, #C0422A)", opacity: 0.04 }} />
      <div className="absolute bottom-0 left-0 w-[400px] h-[400px] rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl pointer-events-none"
        style={{ background: "var(--sand, #D4B896)", opacity: 0.12 }} />

      <motion.div
        variants={cardVariants}
        initial="hidden"
        animate="visible"
        className="w-full max-w-xl relative z-10"
        style={{ background: "white", borderRadius: "2.5rem", border: "1px solid var(--border, #E5DDD5)", padding: "2rem", boxShadow: "0 20px 60px rgba(60,40,20,0.08)" }}
      >

        <motion.div variants={containerVariants} initial="hidden" animate="visible">
          {/* Logo & Header */}
          <motion.div variants={itemVariants} className="mb-10 w-full relative">
            <div className="flex items-center justify-center relative">
              <button
                onClick={() => router.back()}
                className="absolute left-0 p-2.5 bg-[#F9F6F2] hover:bg-[#EBDCCB] text-(--muted) hover:text-(--rust) rounded-xl transition-all border border-[#E5DDD5] shadow-sm transform hover:scale-105"
                title="Go Back"
              >
                <ArrowRight className="w-4 h-4 rotate-180" />
              </button>
              <div className="text-center">
                <Link href="/" className="inline-block mb-1">
                  <span className="font-serif text-lg font-black italic tracking-tight" style={{ color: "var(--rust, #C0422A)" }}>
                    LumbaRong
                  </span>
                </Link>
                <div className="text-[9px] font-bold uppercase tracking-[0.3em]" style={{ color: "var(--muted, #8C7B70)" }}>
                  Customer Registration
                </div>
              </div>
            </div>

            {/* Progress Steps removed for single step */}
          </motion.div>

          {/* Error */}
          <AnimatePresence>
            {error && (
              <motion.div
                initial={{ opacity: 0, y: -10, scale: 0.97 }} animate={{ opacity: 1, y: 0, scale: 1 }} exit={{ opacity: 0, y: -8, scale: 0.97 }}
                transition={{ duration: 0.3 }}
                className="mb-8 p-4 rounded-2xl text-[10px] font-bold flex items-center justify-center gap-2 uppercase tracking-wider"
                style={{ background: "#FEF0EE", border: "1px solid #F9D0C8", color: "var(--rust)" }}
              >
                <ShieldCheck className="w-4 h-4 shrink-0" /> {error}
              </motion.div>
            )}
          </AnimatePresence>

          <AnimatePresence mode="wait">
            {/* Step 1 */}
            {step === 1 && (
              <motion.form key="step1" initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 20 }} transition={{ duration: 0.4, ease: [0.22, 1, 0.36, 1] }} onSubmit={handleNext} className="space-y-6" autoComplete="off">
                <motion.div variants={itemVariants} className="space-y-2">
                  <label style={labelStyle}>Username</label>
                  <div className="relative">
                    <User className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "var(--border)" }} />
                    <input type="text" style={inputStyle} placeholder="" required value={formData.name} name="register_name" autoComplete="off"
                      maxLength={INPUT_LIMITS.personName}
                      onChange={(e) => setFormData({ ...formData, name: sanitizePersonNameInput(e.target.value) })}
                      onFocus={handleFocus} onBlur={handleBlur} />
                  </div>
                </motion.div>

                <motion.div variants={itemVariants} className="space-y-2">
                  <label style={labelStyle}>Secure Email</label>
                  <div className="relative">
                    <Mail className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "var(--border)" }} />
                    <input type="email" style={inputStyle} placeholder="" required value={formData.email} name="register_email" autoComplete="off"
                      maxLength={100}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      onFocus={handleFocus} onBlur={handleBlur} />
                  </div>
                </motion.div>

                <motion.div variants={itemVariants} className="space-y-2">
                  <label style={labelStyle}>Platform Password</label>
                  <div className="relative">
                    <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "var(--border)" }} />
                    <input type={showPassword ? "text" : "password"} style={{ ...inputStyle, paddingRight: "3.5rem" }} placeholder="••••••••••••" required value={formData.password} name="register_password" autoComplete="new-password"
                      maxLength={20}
                      onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                      onFocus={handleFocus} onBlur={handleBlur} />
                    <button type="button" onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-6 top-1/2 -translate-y-1/2 transition-colors duration-300 hover:opacity-70"
                      style={{ color: "var(--muted)" }}>
                      <AnimatePresence mode="wait" initial={false}>
                        <motion.span key={showPassword ? "off" : "on"} initial={{ opacity: 0, rotate: -15, scale: 0.8 }} animate={{ opacity: 1, rotate: 0, scale: 1 }} exit={{ opacity: 0, rotate: 15, scale: 0.8 }} transition={{ duration: 0.2 }}>
                          {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </motion.span>
                      </AnimatePresence>
                    </button>
                  </div>
                </motion.div>

                <motion.div variants={itemVariants} className="space-y-2">
                  <label style={labelStyle}>Confirm Password</label>
                  <div className="relative">
                    <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "var(--border)" }} />
                    <input type={showPassword ? "text" : "password"} style={{ ...inputStyle, paddingRight: "3.5rem" }} placeholder="••••••••••••" required value={formData.confirmPassword} name="register_confirm_password" autoComplete="new-password"
                      maxLength={20}
                      onChange={(e) => setFormData({ ...formData, confirmPassword: e.target.value })}
                      onFocus={handleFocus} onBlur={handleBlur} />
                  </div>
                </motion.div>

                <motion.div variants={itemVariants}>
                  <motion.button type="submit" whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.97 }} transition={{ type: "spring", stiffness: 400, damping: 20 }}
                    className="w-full text-white text-[10px] font-bold uppercase tracking-[0.2em] flex items-center justify-center gap-3 mt-4"
                    style={{ padding: "0.875rem", borderRadius: "9999px", background: "var(--bark, #3D2B1F)", boxShadow: "0 6px 20px rgba(60,43,31,0.18)", transition: "background 0.3s" }}
                    onMouseEnter={(e) => { e.currentTarget.style.background = "var(--rust, #C0422A)"; }}
                    onMouseLeave={(e) => { e.currentTarget.style.background = "var(--bark, #3D2B1F)"; }}>
                    Continue <ArrowRight className="w-4 h-4" />
                  </motion.button>
                </motion.div>

                <motion.div variants={itemVariants} className="pt-2">
                  <div className="flex items-center gap-4 mb-6">
                    <div className="h-px flex-1 bg-[#E5DDD5]" />
                    <span className="text-[9px] font-bold text-[#8C7B70] uppercase tracking-widest">social registry</span>
                    <div className="h-px flex-1 bg-[#E5DDD5]" />
                  </div>
                  
                  {isGoogleAuthEnabled ? (
                    <div className="flex justify-center">
                      <GoogleLogin
                        onSuccess={handleGoogleAuth}
                        onError={() => setError("Google Sign-up failed. Please try again.")}
                        theme="outline"
                        shape="pill"
                        width="100%"
                        text="signup_with"
                        ux_mode="popup"
                      />
                    </div>
                  ) : (
                    <p className="text-center text-[10px] font-medium" style={{ color: "var(--muted, #8C7B70)" }}>
                      Google Sign-up is unavailable until `NEXT_PUBLIC_GOOGLE_CLIENT_ID` is configured.
                    </p>
                  )}
                </motion.div>
              </motion.form>
            )}

            {/* Step 2 Success */}
            {step === 2 && (
              <motion.div key="step2" initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }} className="text-center py-8">
                <div className="w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-8 shadow-xl"
                  style={{ background: "var(--input-bg)", color: "var(--sage, #8FA882)", transform: "rotate(6deg)" }}>
                  <CheckCircle2 className="w-10 h-10" />
                </div>
                <h2 className="font-serif text-2xl font-black mb-4" style={{ color: "var(--charcoal)" }}>Welcome!</h2>
                <p className="max-w-xs mx-auto mb-10 text-[11px] font-medium leading-relaxed italic" style={{ color: "var(--muted)" }}>
                  Your account has been created successfully. You may now explore our curated collection.
                </p>
                <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.97 }} transition={{ type: "spring", stiffness: 400, damping: 20 }}>
                  <Link href="/"
                    className="inline-flex items-center gap-3 text-white px-10 text-[10px] font-bold uppercase tracking-[0.2em] shadow-lg"
                    style={{ padding: "1rem 2.5rem", borderRadius: "9999px", background: "var(--bark, #3D2B1F)" }}
                    onMouseEnter={(e) => { e.currentTarget.style.background = "var(--rust, #C0422A)"; }}
                    onMouseLeave={(e) => { e.currentTarget.style.background = "var(--bark, #3D2B1F)"; }}>
                    Start Shopping <ArrowRight className="w-4 h-4" />
                  </Link>
                </motion.div>
              </motion.div>
            )}

          </AnimatePresence>

          {/* Footer */}
          {step < 2 && (
            <motion.div variants={itemVariants} className="mt-10 text-center">
              <p className="text-[10px] font-bold uppercase tracking-widest" style={{ color: "var(--muted, #8C7B70)" }}>
                Already Registered?{" "}
                <Link href="/login" className="hover:opacity-70 transition-opacity ml-1" style={{ color: "var(--rust, #C0422A)" }}>
                  Sign-In
                </Link>
              </p>
            </motion.div>
          )}
        </motion.div>
      </motion.div>
    </div>
  );
}
