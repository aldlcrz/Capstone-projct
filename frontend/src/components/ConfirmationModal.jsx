"use client";
import React from "react";
import { motion, AnimatePresence } from "framer-motion";
import { 
  X, 
  AlertTriangle, 
  LogOut, 
  Info,
  AlertCircle
} from "lucide-react";

export default function ConfirmationModal({ 
  isOpen, 
  onClose, 
  onConfirm, 
  title = "Are you sure?", 
  message = "This action cannot be undone.",
  children,
  confirmText = "Confirm",
  cancelText = "Cancel",
  type = "danger" // danger, warning, info
}) {
  if (!isOpen) return null;

  const getTheme = () => {
    switch(type) {
      case 'danger': 
        return { 
          accent: '#ef4444', 
          bgGradient: 'from-rose-50 to-red-50',
          iconWrapper: 'bg-red-100 border-red-200 text-red-600',
          btnBg: 'bg-gradient-to-r from-red-500 to-rose-600',
          btnHover: 'hover:shadow-red-200',
          icon: <LogOut className="w-6 h-6" /> 
        };
      case 'warning': 
        return { 
          accent: '#f59e0b', 
          bgGradient: 'from-amber-50 to-yellow-50',
          iconWrapper: 'bg-amber-100 border-amber-200 text-amber-600',
          btnBg: 'bg-gradient-to-r from-amber-500 to-yellow-600',
          btnHover: 'hover:shadow-amber-200',
          icon: <AlertTriangle className="w-6 h-6" /> 
        };
      default: 
        return { 
          accent: '#C0420A', 
          bgGradient: 'from-orange-50 to-stone-50',
          iconWrapper: 'bg-orange-100 border-orange-200 text-[#C0420A]',
          btnBg: 'bg-gradient-to-r from-[#C0420A] to-[#A63924]',
          btnHover: 'hover:shadow-orange-200',
          icon: <Info className="w-6 h-6" /> 
        };
    }
  };

  const theme = getTheme();

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-[999] flex items-center justify-center p-4">
          {/* Backdrop Blur */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="absolute inset-0 bg-[#1A1208]/40 backdrop-blur-md"
          />

          {/* Modal Container */}
          <motion.div
            initial={{ opacity: 0, scale: 0.9, y: 30 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.9, y: 30 }}
            transition={{ type: "spring", damping: 25, stiffness: 350 }}
            className="bg-white w-full max-w-[400px] rounded-[2.5rem] shadow-[0_32px_80px_rgba(26,18,8,0.25)] relative z-10 overflow-hidden border border-[var(--border)]"
          >
            <div className="p-10 text-center flex flex-col items-center">
              
              {/* Elegant Icon Container */}
              <div className={`w-20 h-20 rounded-[2rem] ${theme.iconWrapper} border flex items-center justify-center mb-8 shadow-inner`}>
                {theme.icon}
              </div>
              
              <div className="space-y-3 mb-10">
                <h3 className="font-serif text-2xl font-bold text-[#1C1209] tracking-tight">{title}</h3>
                <p className="text-[13px] text-[var(--muted)] font-medium leading-relaxed px-4">
                  {message}
                </p>
                {children}
              </div>

              {/* Action Buttons */}
              <div className="w-full flex flex-col gap-3">
                <button
                  onClick={() => { onConfirm(); onClose(); }}
                  className={`w-full py-4 ${theme.btnBg} ${theme.btnHover} text-white rounded-2xl text-[11px] font-bold uppercase tracking-[0.25em] transition-all duration-300 shadow-xl hover:-translate-y-0.5 active:scale-95 active:translate-y-0`}
                >
                  {confirmText}
                </button>
                <button
                  onClick={onClose}
                  className="w-full py-4 text-[11px] font-bold text-[var(--muted)] uppercase tracking-[0.2em] hover:text-[var(--charcoal)] hover:bg-[#F7F3EE] rounded-2xl transition-all duration-200"
                >
                  {cancelText}
                </button>
              </div>
            </div>

            {/* Subtle bottom decorative bar */}
            <div className={`h-1.5 w-full bg-gradient-to-r ${theme.type === 'danger' ? 'from-red-500 to-rose-600' : 'from-[#C0420A] to-[#A63924]'}`} style={{ background: theme.btnBg.split(' ').slice(1).join(' ') }} />
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}
