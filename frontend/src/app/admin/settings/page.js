"use client";
import React, { useState, useEffect, useCallback, useRef } from "react";
import { createPortal } from "react-dom";
import AdminLayout from "@/components/AdminLayout";
import {
  ShieldCheck, Save,
  Loader2, AlertCircle, CheckCircle
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, getApiErrorMessage } from "@/lib/api";
import ImageCropper from "@/components/ImageCropper";

export default function AdminSettings() {
  const [verificationRequired, setVerificationRequired] = useState(true);
  const [publicLedger, setPublicLedger] = useState(true);
  const [maintenanceMode, setMaintenanceMode] = useState(false);
  const [landingPageBackground, setLandingPageBackground] = useState("");
  const [landingPageBackgroundPosition, setLandingPageBackgroundPosition] = useState("center");

  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const [showSaveModal, setShowSaveModal] = useState(false);
  const [saveModalSuccess, setSaveModalSuccess] = useState(false);
  const [hasMounted, setHasMounted] = useState(false);

  const cropperRef = useRef(null);

  const showSuccess = (msg) => {
    setSuccess(msg);
    setTimeout(() => setSuccess(null), 3000);
  };

  const fetchSettings = useCallback(async () => {
    try {
      setError(null);
      const res = await api.get("/admin/settings");
      const s = res.data;

      if (s.verificationRequired !== undefined)
        setVerificationRequired(s.verificationRequired === true || s.verificationRequired === "true");

      if (s.publicLedger !== undefined)
        setPublicLedger(s.publicLedger === true || s.publicLedger === "true");

      if (s.maintenanceMode !== undefined)
        setMaintenanceMode(s.maintenanceMode === true || s.maintenanceMode === "true");

      if (s.landingPageBackground !== undefined) setLandingPageBackground(s.landingPageBackground || "");
      if (s.landingPageBackgroundPosition !== undefined)
        setLandingPageBackgroundPosition(s.landingPageBackgroundPosition || "center");

    } catch (err) {
      console.error(err);
      setError(getApiErrorMessage(err, "Failed to load platform parameters."));
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => { fetchSettings(); }, [fetchSettings]);
  useEffect(() => { setHasMounted(true); }, []);

  // ✅ FIXED MODAL HANDLER
  const handleSaveSettings = () => {
    setError(null);
    setShowSaveModal(true);
    setSaveModalSuccess(false);

    // Allow modal to render first
    setTimeout(async () => {
      try {
        setIsSaving(true);

        let currentLandingPageBackground = landingPageBackground;

        // Only crop & upload if a NEW image was loaded (data: URI from FileReader)
        // Existing server images (http://...) must NOT be re-cropped — canvas will
        // be tainted by CORS and toBlob() returns null silently.
        if (cropperRef.current && cropperRef.current.isNewImage) {
          const newUrl = await cropperRef.current.save();
          if (newUrl) {
            currentLandingPageBackground = newUrl;
            setLandingPageBackground(newUrl); // Sync local state
          }
        }

        await api.put("/admin/settings", {
          verificationRequired,
          publicLedger,
          maintenanceMode,
          landingPageBackground: currentLandingPageBackground,
          landingPageBackgroundPosition:
            currentLandingPageBackground !== landingPageBackground
              ? "center"
              : landingPageBackgroundPosition,
        });

        setSaveModalSuccess(true);
        showSuccess("Platform parameters synchronized successfully!");

        setTimeout(() => setShowSaveModal(false), 2000);

      } catch (err) {
        console.error(err);
        setError(getApiErrorMessage(err, "Failed to sync governance."));
        setShowSaveModal(false);
      } finally {
        setIsSaving(false);
      }
    }, 50);
  };

  return (
    <AdminLayout>
      <div className="max-w-2xl mx-auto space-y-10 mb-20 animate-fade-in">
        {/* Header */}
        <div>
          <div className="eyebrow">Main App Settings</div>
          <h1 className="font-serif text-xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
            App <span className="text-[var(--rust)] italic lowercase">Settings</span>
          </h1>
        </div>

        {/* Global Toasts */}
        <AnimatePresence>
          {success && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              className="p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 text-green-700 text-sm font-bold"
            >
              <CheckCircle className="w-4 h-4 shrink-0" /> {success}
            </motion.div>
          )}
          {error && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              className="p-4 bg-red-50 border border-red-100 rounded-xl flex items-center gap-3 text-red-600 text-sm font-bold"
            >
              <AlertCircle className="w-4 h-4 shrink-0" /> {error}
            </motion.div>
          )}
        </AnimatePresence>

        {/* App Rules */}
        <div className="artisan-card p-10 space-y-8 bg-white/50 backdrop-blur-md shadow-2xl">
          <section className="space-y-6">
            <h3 className="text-lg font-bold flex items-center gap-3">
              <ShieldCheck className="w-5 h-5 text-[var(--rust)]" /> App Rules
            </h3>
            <div className="divide-y divide-[var(--border)]">
              <PolicySwitch
                label="Pause Orders"
                desc="Stop customers from buying things for a while (Maintenance)."
                active={maintenanceMode}
                toggle={() => setMaintenanceMode(v => !v)}
              />
            </div>
          </section>

          <section className="space-y-6 pt-6 border-t border-[var(--border)]">
            <div>
              <h3 className="text-lg font-bold">Branding Settings</h3>
              <p className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mt-1">Upload a hero image then drag the crop box to choose what will be shown.</p>
            </div>

            <ImageCropper
              ref={cropperRef}
              initialImage={landingPageBackground}
              onSave={async ({ croppedBlob }) => {
                const formData = new FormData();
                formData.append("image", croppedBlob, "landing-bg.jpg");
                const res = await api.post("/upload", formData, {
                  headers: { "Content-Type": "multipart/form-data" },
                });
                return res.data.url;
              }}
            />
          </section>

          <div className="pt-4">
            <button
              onClick={handleSaveSettings}
              disabled={isSaving || isLoading}
              className="btn-primary w-full sm:w-auto px-10 py-3 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-widest disabled:opacity-60"
            >
              {isSaving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
              {isSaving ? "Saving…" : "Save Settings"}
            </button>
          </div>
        </div>
      </div>

      {/* Save Modal */}
      {hasMounted && createPortal(
        <AnimatePresence>
          {showSaveModal && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
              <motion.div
                initial={{ scale: 0.85, opacity: 0, y: 20 }}
                animate={{ scale: 1, opacity: 1, y: 0 }}
                exit={{ scale: 0.85, opacity: 0, y: 20 }}
                transition={{ type: "spring", stiffness: 300, damping: 25 }}
                className="w-full max-w-sm bg-white rounded-2xl p-10 flex flex-col items-center gap-5 shadow-2xl"
              >
                {saveModalSuccess ? (
                <>
                  <motion.div
                    initial={{ scale: 0 }}
                    animate={{ scale: 1 }}
                    transition={{ type: "spring", stiffness: 400, damping: 20 }}
                    className="w-16 h-16 rounded-full bg-green-50 border-2 border-green-200 flex items-center justify-center"
                  >
                    <CheckCircle className="w-8 h-8 text-green-500" />
                  </motion.div>
                  <div className="text-center">
                    <p className="text-sm font-bold text-[var(--charcoal)] uppercase tracking-widest">Saved!</p>
                    <p className="text-xs text-[var(--muted)] mt-1">Settings synchronized successfully.</p>
                  </div>
                </>
              ) : (
                <>
                  <div className="w-16 h-16 rounded-full bg-[var(--cream)] border-2 border-[var(--border)] flex items-center justify-center">
                    <Loader2 className="w-8 h-8 text-[var(--rust)] animate-spin" />
                  </div>
                  <div className="text-center">
                    <p className="text-sm font-bold text-[var(--charcoal)] uppercase tracking-widest">Saving…</p>
                    <p className="text-xs text-[var(--muted)] mt-1">Please wait while we sync your settings.</p>
                  </div>
                </>
                )}
              </motion.div>
            </motion.div>
          )}
        </AnimatePresence>,
        document.body
      )}
    </AdminLayout>
  );
}

function PolicySwitch({ label, desc, active, toggle }) {
  return (
    <div className="py-6 flex items-center justify-between group">
      <div className="space-y-1">
        <div className="text-sm font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors">{label}</div>
        <div className="text-[10px] text-[var(--muted)] font-bold tracking-tight max-w-[280px]">{desc}</div>
      </div>
      <button
        onClick={toggle}
        className={`w-14 h-8 rounded-full relative transition-all duration-500 shadow-inner ${active ? "bg-[var(--rust)] ring-4 ring-red-50" : "bg-[var(--border)]"}`}
      >
        <motion.div animate={{ x: active ? 28 : 4 }} className="absolute top-1 w-6 h-6 bg-white rounded-full shadow-lg" />
      </button>
    </div>
  );
}
