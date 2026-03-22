"use client";
import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { motion } from "framer-motion";

export default function MobileBottomNav({ items = [] }) {
  const pathname = usePathname();

  return (
    <nav className="lg:hidden fixed bottom-0 left-0 right-0 z-[100] bg-white/80 backdrop-blur-xl border-t border-[var(--border)] h-[calc(72px+var(--safe-bottom,0px))] px-6 flex items-center justify-between shadow-[0_-8px_30px_rgba(0,0,0,0.04)] pb-[var(--safe-bottom,0px)]">
      {items.map((item, idx) => {
        const active = pathname === item.path;
        return (
          <Link 
            key={idx} 
            href={item.path} 
            className="relative flex flex-col items-center justify-center gap-1 group w-16"
          >
            <div className={`p-2 rounded-2xl transition-all duration-300 ${active ? 'bg-[var(--rust)] text-white shadow-lg -translate-y-1' : 'text-[var(--muted)] group-hover:text-[var(--rust)] group-hover:bg-[var(--cream)]'}`}>
              {React.cloneElement(item.icon, { className: "w-6 h-6" })}
            </div>
            <span className={`text-[10px] font-bold uppercase tracking-widest transition-opacity duration-300 ${active ? 'opacity-100 text-[var(--rust)]' : 'opacity-40 text-[var(--charcoal)]'}`}>
              {item.label}
            </span>
            
            {active && (
              <motion.div 
                layoutId="nav-indicator"
                className="absolute -bottom-1 w-1 h-1 rounded-full bg-[var(--rust)]"
                transition={{ type: "spring", stiffness: 380, damping: 30 }}
              />
            )}
          </Link>
        );
      })}
    </nav>
  );
}
