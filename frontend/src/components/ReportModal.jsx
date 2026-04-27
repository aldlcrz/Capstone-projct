"use client";
import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { XCircle, AlertTriangle, Upload, Loader2, CheckCircle2 } from "lucide-react";
import { api } from "@/lib/api";

export default function ReportModal({ isOpen, onClose, reportedId, type, referenceId, reportedName }) {
  const [reason, setReason] = useState("");
  const [description, setDescription] = useState("");
  const [images, setImages] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState(null);

  const handleImageChange = (e) => {
    const files = Array.from(e.target.files);
    if (files.length > 3) {
      alert("You can only upload up to 3 evidence files.");
      return;
    }
    setImages(files);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!reason) return alert("Please select a reason.");
    if (description.length < 50) return alert("Please provide a more detailed description (min 50 characters).");


    try {
      setIsSubmitting(true);
      setError(null);

      const formData = new FormData();
      formData.append("reportedId", reportedId);
      formData.append("type", type);
      formData.append("reason", reason);
      formData.append("description", description);
      if (referenceId) formData.append("referenceId", referenceId);
      
      images.forEach((file) => {
        formData.append("images", file);
      });

      await api.post("/reports/request", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });

      setSuccess(true);
      setTimeout(() => {
        setSuccess(false);
        onClose();
        // Reset form
        setReason("");
        setDescription("");
        setImages([]);
      }, 3000);
    } catch (err) {
      console.error("Report Error:", err);
      setError(err.response?.data?.message || "Failed to submit report. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const customerReasons = [
    "Counterfeit Items",
    "Misleading Information",
    "Fraud / Scam",
    "Prohibited Items",
    "Abusive Behavior",
    "Policy Violation",
    "Other"
  ];

  const sellerReasons = [
    "Fraudulent Order",
    "Abusive Behavior",
    "Refund Abuse",
    "Unpaid COD Spam",
    "Fake Payment Proof",
    "Other"
  ];

  const reasons = type === "CustomerReportingSeller" ? customerReasons : sellerReasons;

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center p-4">
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="absolute inset-0 bg-[var(--charcoal)]/40 backdrop-blur-sm"
          />
          <motion.div
            initial={{ opacity: 0, scale: 0.95, y: 20 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: 20 }}
            className="relative w-full max-w-lg bg-white rounded-[2rem] shadow-2xl overflow-hidden artisan-card p-0"
          >
            {success ? (
              <div className="p-12 text-center space-y-6">
                <div className="w-20 h-20 bg-green-50 text-green-600 rounded-3xl flex items-center justify-center mx-auto ring-8 ring-green-50/50">
                  <CheckCircle2 className="w-10 h-10" />
                </div>
                <div>
                  <h3 className="font-serif text-2xl font-bold text-[var(--charcoal)] uppercase tracking-tight">Report <span className="text-[var(--rust)] italic lowercase">Submitted</span></h3>
                  <p className="text-[10px] font-black text-[var(--muted)] uppercase tracking-widest mt-2">Our trust & safety team will review this shortly.</p>
                </div>
              </div>
            ) : (
              <div className="p-8 sm:p-10 space-y-8">
                <div className="flex justify-between items-start">
                  <div>
                    <div className="eyebrow text-red-600">Integrity Violation</div>
                    <h3 className="font-serif text-2xl font-bold text-[var(--charcoal)] tracking-tighter uppercase">
                      Report <span className="text-[var(--rust)] italic lowercase">{type === "CustomerReportingSeller" ? "Store" : "Customer"}</span>
                    </h3>
                    <p className="text-[9px] font-black text-[var(--muted)] opacity-50 uppercase tracking-widest mt-1">Reporting: {reportedName}</p>
                  </div>
                  <button onClick={onClose} className="p-2 hover:bg-[var(--cream)] rounded-xl transition-all">
                    <XCircle className="w-6 h-6 text-[var(--muted)]" />
                  </button>
                </div>

                {error && (
                  <div className="p-4 bg-red-50 border border-red-100 text-red-600 text-xs font-bold rounded-2xl flex items-center gap-2">
                    <AlertTriangle className="w-4 h-4" /> {error}
                  </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                  <div className="space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Reason for Report</label>
                    <select
                      value={reason}
                      onChange={(e) => setReason(e.target.value)}
                      required
                      className="w-full px-5 py-4 bg-[var(--cream)]/30 border border-[var(--border)] focus:border-[var(--rust)] rounded-2xl outline-none text-[11px] font-bold transition-all shadow-sm"
                    >
                      <option value="">Select a reason...</option>
                      {reasons.map((r) => (
                        <option key={r} value={r}>{r}</option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-2">
                    <div className="flex items-center justify-between ml-1">
                      <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Detailed Description</label>
                      <span className={`text-[9px] font-bold tabular-nums ${description.length >= 50 ? 'text-green-600' : 'text-[var(--muted)]'}`}>
                        {description.length}<span className="opacity-50">/50 min</span>
                      </span>
                    </div>
                    <textarea
                      placeholder="Please explain the situation in detail (minimum 50 characters)..."
                      value={description}
                      onChange={(e) => setDescription(e.target.value)}
                      required
                      rows="4"
                      className={`w-full px-5 py-4 bg-[var(--cream)]/30 border focus:border-[var(--rust)] rounded-2xl outline-none text-[11px] font-bold transition-all shadow-sm resize-none ${description.length > 0 && description.length < 50 ? 'border-amber-400' : 'border-[var(--border)]'}`}
                    />
                    {description.length > 0 && description.length < 50 && (
                      <p className="text-[9px] text-amber-600 font-bold ml-1">{50 - description.length} more characters needed</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Evidence (Max 3 files)</label>
                    <div className="relative">
                      <input
                        type="file"
                        multiple
                        accept="image/*"
                        onChange={handleImageChange}
                        className="hidden"
                        id="report-evidence"
                      />
                      <label
                        htmlFor="report-evidence"
                        className="flex items-center justify-center gap-3 w-full py-4 bg-white border-2 border-dashed border-[var(--border)] rounded-2xl cursor-pointer hover:border-[var(--rust)] hover:bg-[var(--cream)]/30 transition-all group"
                      >
                        <Upload className="w-4 h-4 text-[var(--muted)] group-hover:text-[var(--rust)] transition-colors" />
                        <span className="text-[10px] font-black uppercase tracking-widest text-[var(--muted)] group-hover:text-[var(--charcoal)] transition-colors">
                          {images.length > 0 ? `${images.length} files selected` : "Upload Screenshots / Images"}
                        </span>
                      </label>
                    </div>
                  </div>

                  <div className="flex gap-4 pt-4">
                    <button
                      type="button"
                      onClick={onClose}
                      className="flex-1 py-4 bg-[var(--cream)] text-[var(--charcoal)] text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-[var(--border)] transition-all"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      disabled={isSubmitting}
                      className="flex-[2] py-4 bg-[var(--rust)] text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-[var(--charcoal)] transition-all shadow-xl active:scale-[0.98] disabled:opacity-50 flex items-center justify-center"
                    >
                      {isSubmitting ? (
                        <>
                          <Loader2 className="w-4 h-4 animate-spin mr-2" />
                          Submitting...
                        </>
                      ) : (
                        "Submit Report"
                      )}
                    </button>
                  </div>
                </form>
              </div>
            )}
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
