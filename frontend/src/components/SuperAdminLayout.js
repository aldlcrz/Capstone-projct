"use client";
import React, { useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  BarChart3,
  Store,
  LogOut,
  ShieldCheck,
  Code2,
  Menu,
  X,
  TrendingUp,
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { clearSession, getStoredUserForRole, getTokenForRole, SESSION_SYNC_EVENT } from "@/lib/api";
import ConfirmationModal from "./ConfirmationModal";
import { Loader2 } from "lucide-react";

const sidebarData = [
  {
    group: "PLATFORM OVERVIEW",
    items: [
      { label: "Dashboard", icon: <BarChart3 className="w-5 h-5" />, path: "/super-admin/dashboard" },
    ],
  },
  {
    group: "FINANCIAL MONITORING",
    items: [
      { label: "Shop Performance", icon: <TrendingUp className="w-5 h-5" />, path: "/super-admin/performance" },
    ],
  },
];

const mobileNavItems = [
  { label: "Dashboard", icon: <BarChart3 />, path: "/super-admin/dashboard" },
  { label: "Performance", icon: <TrendingUp />, path: "/super-admin/performance" },
];

function MobileBottomNav({ items }) {
  const pathname = usePathname();
  return (
    <nav className="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#1a1a2e] border-t border-[#2d2d5e] flex items-center justify-around h-16 px-2">
      {items.map((item, i) => {
        const active = pathname === item.path || pathname.startsWith(item.path);
        return (
          <Link
            key={i}
            href={item.path}
            className={`flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition-all ${active ? "text-[#818cf8]" : "text-slate-400"}`}
          >
            <span className={`text-lg ${active ? "text-[#818cf8]" : "text-slate-400"}`}>{item.icon}</span>
            <span className="text-[9px] font-black uppercase tracking-widest">{item.label}</span>
          </Link>
        );
      })}
    </nav>
  );
}

export default function SuperAdminLayout({ children }) {
  const pathname = usePathname();
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  React.useEffect(() => {
    const checkAuth = () => {
      const storedUser = getStoredUserForRole("super_admin");
      const token = getTokenForRole("super_admin");

      if (!token || !storedUser || storedUser.role !== "super_admin") {
        if (!window.location.pathname.includes("/login")) {
          window.location.href = "/login?error=super_admin_required";
        }
        return;
      }

      setUser(storedUser);
      setLoading(false);
    };

    checkAuth();
    window.addEventListener("storage", checkAuth);
    window.addEventListener(SESSION_SYNC_EVENT, checkAuth);
    return () => {
      window.removeEventListener("storage", checkAuth);
      window.removeEventListener(SESSION_SYNC_EVENT, checkAuth);
    };
  }, []);

  const handleLogout = () => setShowLogoutConfirm(true);
  const confirmLogout = () => {
    clearSession("super_admin");
    window.location.href = "/";
  };

  return (
    <div data-panel="super-admin" className="flex h-screen overflow-hidden" style={{ background: "linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%)" }}>
      {/* Sidebar Desktop */}
      <motion.aside
        initial={{ x: -280 }}
        animate={{ x: 0 }}
        className="hidden lg:flex flex-col w-[280px] h-full border-r border-[#2d2d5e] overflow-y-auto"
        style={{ background: "rgba(15, 15, 30, 0.95)", backdropFilter: "blur(20px)" }}
      >
        <div className="p-10 flex flex-col h-full">
          {/* Logo */}
          <div className="mb-12">
            <Link href="/super-admin/dashboard" className="block">
              <div className="font-serif text-lg font-bold text-white tracking-tighter">LUMBARONG</div>
              <div className="flex items-center gap-1.5 mt-2 px-1 font-bold tracking-widest text-[10px]" style={{ color: "#818cf8" }}>
                <Code2 className="w-3 h-3" /> SUPER ADMIN CONSOLE
              </div>
            </Link>
          </div>

          {/* Nav */}
          <nav className="flex-1 space-y-10">
            {sidebarData.map((group, idx) => (
              <div key={idx} className="space-y-4">
                <div className="text-[9px] font-bold text-slate-500 tracking-widest uppercase px-3">
                  {group.group}
                </div>
                <div className="space-y-1.5">
                  {group.items.map((item, i) => {
                    const active = pathname === item.path || (item.path !== "/super-admin/dashboard" && pathname.startsWith(item.path));
                    return (
                      <Link
                        key={i}
                        href={item.path}
                        className={`flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium ${
                          active
                            ? "border-l-4 border-[#818cf8] text-[#818cf8]"
                            : "text-slate-400 hover:text-[#818cf8]"
                        }`}
                        style={active ? { background: "rgba(129, 140, 248, 0.1)" } : {}}
                      >
                        <span className={`transition-colors ${active ? "text-[#818cf8]" : "text-slate-500 group-hover:text-[#818cf8]"}`}>
                          {item.icon}
                        </span>
                        {item.label}
                      </Link>
                    );
                  })}
                </div>
              </div>
            ))}
          </nav>

          {/* User + Logout */}
          <div className="mt-10 pt-8 border-t border-[#2d2d5e]">
            <div className="flex items-center gap-3 px-2 mb-6">
              <div
                className="w-10 h-10 rounded-xl border-2 border-[#818cf8]/30 flex items-center justify-center text-white font-serif text-lg font-bold"
                style={{ background: "linear-gradient(135deg, #6366f1, #818cf8)" }}
              >
                {user?.name?.[0] || "S"}
              </div>
              <div>
                <div className="text-sm font-bold text-white">{user?.name || "Super Admin"}</div>
                <div className="text-[10px] font-bold uppercase tracking-widest leading-none" style={{ color: "#818cf8" }}>
                  DEVELOPER LEVEL
                </div>
              </div>
            </div>
            <button
              onClick={handleLogout}
              className="flex items-center gap-3 w-full px-4 py-3.5 rounded-xl hover:bg-red-900/20 transition-all font-bold text-xs tracking-widest uppercase text-red-400"
            >
              <LogOut className="w-4 h-4" /> LOG OUT
            </button>
          </div>
        </div>
      </motion.aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col h-full relative overflow-hidden">
        {/* Top Header */}
        <header className="sticky top-0 z-40 h-[72px] flex items-center shrink-0 border-b border-[#2d2d5e]" style={{ background: "rgba(15, 15, 30, 0.8)", backdropFilter: "blur(20px)" }}>
          <div className="container mx-auto px-5 lg:px-10 flex items-center justify-between">
            <div className="flex items-center lg:hidden">
              <Link href="/super-admin/dashboard" className="font-serif text-sm font-bold tracking-tighter text-white">
                LUMBARONG
              </Link>
            </div>

            <div className="hidden lg:flex items-center gap-2">
              <ShieldCheck className="w-4 h-4" style={{ color: "#818cf8" }} />
              <span className="text-[10px] font-black uppercase tracking-widest" style={{ color: "#818cf8" }}>
                Super Admin Console
              </span>
            </div>

            <div className="flex items-center gap-4">
              <div className="hidden sm:block text-sm font-bold text-slate-400">
                {new Date().toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })}
              </div>
              <button
                onClick={handleLogout}
                className="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl transition-colors text-red-400 hover:bg-red-900/20"
                aria-label="Logout"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          </div>
        </header>

        {/* Scrollable Content */}
        <main className="flex-1 overflow-y-auto p-5 lg:p-10 pb-[135px] lg:pb-10 custom-scrollbar animate-fade-up">
          <div className="max-w-[1200px] mx-auto">
            {loading ? (
              <div className="flex items-center justify-center min-h-[400px]">
                <Loader2 className="w-10 h-10 animate-spin" style={{ color: "#818cf8" }} />
              </div>
            ) : (
              children
            )}
          </div>
        </main>

        <MobileBottomNav items={mobileNavItems} />
      </div>

      <ConfirmationModal
        isOpen={showLogoutConfirm}
        onClose={() => setShowLogoutConfirm(false)}
        onConfirm={confirmLogout}
        title="Sign Out"
        message="Are you sure you want to log out of the Super Admin Console?"
        confirmText="Yes, Logout"
        cancelText="Cancel"
        type="danger"
      />
    </div>
  );
}
