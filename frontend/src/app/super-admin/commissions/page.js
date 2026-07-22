"use client";
import React, { useState, useEffect, useCallback } from "react";
import SuperAdminLayout from "@/components/SuperAdminLayout";
import { Percent, Save, RefreshCw, CheckCircle, AlertCircle } from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";

export default function SuperAdminCommissionsPage() {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [rules, setRules] = useState({
    "Barong Tagalog": 10.0,
    Filipiniana: 10.0,
    Accessories: 8.0,
    "Custom Hand-Embroidered": 5.0,
    Default: 10.0,
  });
  const [message, setMessage] = useState(null);

  const fetchRules = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get("/super-admin/commissions");
      if (res.data && res.data.rules) {
        setRules(res.data.rules);
      }
    } catch (err) {
      console.error("Failed to load commission rules", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchRules();
  }, [fetchRules]);

  const handleRateChange = (category, value) => {
    const num = parseFloat(value) || 0;
    setRules((prev) => ({
      ...prev,
      [category]: Math.max(0, Math.min(100, num)),
    }));
  };

  const handleSave = async () => {
    setSaving(true);
    setMessage(null);
    try {
      const res = await api.put("/super-admin/commissions", { rules });
      if (res.data && res.data.status === "success") {
        setMessage({ type: "success", text: "Commission rules updated successfully!" });
      }
    } catch (err) {
      console.error("Failed to update commission rules", err);
      setMessage({ type: "error", text: "Failed to save commission rules." });
    } finally {
      setSaving(false);
    }
  };

  return (
    <SuperAdminLayout>
      <div className="space-y-8">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div className="flex items-center gap-1.5 text-xs font-bold tracking-widest uppercase mb-1" style={{ color: "#818cf8" }}>
              <Percent className="w-3.5 h-3.5" /> Financial Controls
            </div>
            <h1 className="font-serif text-3xl font-bold tracking-tight text-white">
              Dynamic{" "}
              <span className="italic" style={{ color: "#818cf8" }}>
                Commission Rates
              </span>
            </h1>
            <p className="text-xs text-slate-400 mt-1 font-medium">
              Configure platform commission percentages per product category and custom service.
            </p>
          </div>

          <div className="flex items-center gap-3">
            <button
              onClick={fetchRules}
              disabled={loading || saving}
              className="flex items-center gap-2 px-5 py-3 rounded-2xl font-bold text-xs tracking-widest uppercase transition-all disabled:opacity-50"
              style={{ background: "rgba(129,140,248,0.15)", color: "#818cf8", border: "1px solid rgba(129,140,248,0.3)" }}
            >
              <RefreshCw className={`w-4 h-4 ${loading ? "animate-spin" : ""}`} /> Reload
            </button>

            <button
              onClick={handleSave}
              disabled={loading || saving}
              className="flex items-center gap-2 px-6 py-3 rounded-2xl font-bold text-xs tracking-widest uppercase transition-all shadow-xl disabled:opacity-50"
              style={{ background: "#818cf8", color: "#0f0f1a" }}
            >
              <Save className="w-4 h-4" /> {saving ? "Saving..." : "Save Rules"}
            </button>
          </div>
        </div>

        {/* Status Message */}
        {message && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className={`p-4 rounded-2xl flex items-center gap-3 border text-xs font-bold ${
              message.type === "success"
                ? "bg-green-500/10 border-green-500/30 text-green-400"
                : "bg-red-500/10 border-red-500/30 text-red-400"
            }`}
          >
            {message.type === "success" ? <CheckCircle className="w-5 h-5" /> : <AlertCircle className="w-5 h-5" />}
            {message.text}
          </motion.div>
        )}

        {/* Rules Editor Grid */}
        <div className="rounded-3xl border overflow-hidden" style={{ background: "rgba(26,26,46,0.7)", borderColor: "rgba(45,45,94,0.8)" }}>
          <div className="p-6 border-b flex items-center justify-between" style={{ borderColor: "rgba(45,45,94,0.8)", background: "rgba(15,15,30,0.5)" }}>
            <h3 className="text-xs font-bold uppercase tracking-widest text-slate-300">Category Percentage Matrix</h3>
            <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">All values in %</span>
          </div>

          <div className="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            {Object.entries(rules).map(([cat, rate], i) => (
              <div
                key={i}
                className="p-6 rounded-2xl border flex items-center justify-between gap-4"
                style={{ background: "rgba(15,15,30,0.4)", borderColor: "rgba(45,45,94,0.6)" }}
              >
                <div>
                  <div className="text-sm font-bold text-white">{cat}</div>
                  <div className="text-[10px] text-slate-500 uppercase tracking-widest font-bold mt-0.5">
                    Platform cut on total item price
                  </div>
                </div>

                <div className="flex items-center gap-2 shrink-0">
                  <input
                    type="number"
                    step="0.5"
                    min="0"
                    max="100"
                    value={rate}
                    onChange={(e) => handleRateChange(cat, e.target.value)}
                    className="w-20 px-3 py-2 rounded-xl text-center text-sm font-bold bg-[#1a1a2e] text-white border border-[#2d2d5e] focus:outline-none focus:border-[#818cf8]"
                  />
                  <span className="text-xs font-bold text-[#818cf8]">%</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </SuperAdminLayout>
  );
}
