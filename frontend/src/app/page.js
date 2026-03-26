"use client";
import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { Star, ArrowRight, Menu, X } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

const containerVariants = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.12, delayChildren: 0.3 } },
};

const itemVariants = {
  hidden: { opacity: 0, y: 22 },
  visible: {
    opacity: 1,
    y: 0,
    transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] },
  },
};

export default function LandingPage() {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <div className="relative min-h-screen overflow-hidden flex flex-col selection:bg-red-200">
      {/* Full-bleed background image */}
      <div className="absolute inset-0 z-0">
        <Image
          src="/images/barong-bg.jpg"
          alt="Authentic Barong Tagalog"
          fill
          className="object-cover object-center scale-[1.04]"
          priority
        />
        {/* Dark warm overlay for readability */}
        <div className="absolute inset-0 bg-gradient-to-r from-[#1C1917]/90 via-[#1C1917]/65 to-[#1C1917]/20" />
        <div className="absolute inset-0 bg-gradient-to-t from-[#1C1917]/70 via-transparent to-transparent" />
      </div>

      {/* Header */}
      <header className="relative z-50 w-full px-6 md:px-8 lg:px-14 py-6 md:py-8 flex items-center justify-between">
        <Link href="/" className="relative z-[60]">
          <span className="font-serif text-2xl font-black italic tracking-tight text-[#D4B896]">
            LumbaRong
          </span>
        </Link>

        {/* Desktop Navigation */}
        <nav className="hidden lg:flex items-center gap-10">
          {[
            { href: "/heritage-guide", label: "GUIDE" },
            { href: "/about", label: "ABOUT US" },
            { href: "/privacy-policy", label: "PRIVACY POLICY" },
            { href: "/terms", label: "TERMS OF USE" },
          ].map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="text-[10px] font-bold tracking-[0.2em] text-[#D4B896]/80 hover:text-[#D4B896] transition-colors duration-300"
            >
              {item.label}
            </Link>
          ))}
        </nav>

        {/* Desktop Auth */}
        <div className="hidden lg:flex items-center gap-8">
          <Link
            href="/login"
            className="text-[11px] font-bold tracking-[0.12em] text-[#D4B896]/80 hover:text-[#D4B896] transition-colors duration-300"
          >
            SIGN IN
          </Link>
          <Link
            href="/register"
            className="bg-[#C0422A] text-white px-7 py-3 rounded-full text-[10px] font-bold tracking-[0.15em] hover:bg-[#E8604A] hover:scale-105 transition-all duration-300 shadow-lg shadow-[#C0422A]/30"
          >
            CREATE ACCOUNT
          </Link>
        </div>

        {/* Mobile Menu Toggle */}
        <button
          className="lg:hidden text-[#D4B896] hover:text-white transition-colors z-[60] relative"
          onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
        >
          {isMobileMenuOpen ? <X size={28} /> : <Menu size={28} />}
        </button>
      </header>

      {/* Mobile Menu Overlay */}
      <AnimatePresence>
        {isMobileMenuOpen && (
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            className="fixed inset-0 z-50 bg-[#1C1917]/95 backdrop-blur-md flex flex-col items-center justify-center gap-8 lg:hidden"
          >
            {[
              { href: "/heritage-guide", label: "GUIDE" },
              { href: "/about", label: "ABOUT US" },
              { href: "/privacy-policy", label: "PRIVACY POLICY" },
              { href: "/terms", label: "TERMS OF USE" },
            ].map((item) => (
              <Link
                key={item.href}
                href={item.href}
                onClick={() => setIsMobileMenuOpen(false)}
                className="text-lg font-bold tracking-[0.2em] text-[#D4B896] hover:text-white transition-colors duration-300"
              >
                {item.label}
              </Link>
            ))}
            <div className="w-16 h-px bg-[#D4B896]/30 my-2" />
            <Link
              href="/login"
              onClick={() => setIsMobileMenuOpen(false)}
              className="text-lg font-bold tracking-[0.12em] text-[#D4B896] hover:text-white transition-colors duration-300"
            >
              SIGN IN
            </Link>
            <Link
              href="/register"
              onClick={() => setIsMobileMenuOpen(false)}
              className="bg-[#C0422A] text-white px-8 py-4 rounded-full text-xs font-bold tracking-[0.15em] hover:bg-[#E8604A] transition-all duration-300 shadow-lg shadow-[#C0422A]/30 mt-2"
            >
              CREATE ACCOUNT
            </Link>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Hero Content */}
      <main className="relative z-20 flex-1 flex flex-col justify-center px-6 md:px-8 lg:px-16 max-w-[1400px] mx-auto w-full mt-10 md:mt-0 pb-16 md:pb-0">
        <motion.div
          className="max-w-[620px] md:mt-[-4vh]"
          variants={containerVariants}
          initial="hidden"
          animate="visible"
        >
          {/* Eyebrow badge */}
          <motion.div variants={itemVariants}>
            <div className="inline-flex items-center gap-2 border border-[#C0422A]/50 bg-[#C0422A]/10 backdrop-blur-sm px-3 py-1.5 rounded-full mb-6 mt-4 md:mt-0">
              <span className="w-1.5 h-1.5 rounded-full bg-[#C0422A] animate-pulse" />
              <span className="text-[#E8604A] text-[9px] font-extrabold tracking-[0.22em] uppercase">
                Direct Seller Access
              </span>
            </div>
          </motion.div>

          {/* Headline */}
          <motion.h1
            variants={itemVariants}
            className="text-[3.25rem] md:text-[4.5rem] lg:text-[5.5rem] leading-[1.05] md:leading-[0.93] font-serif font-black text-[#F7F3EE] tracking-tight mb-6"
          >
            Wear the <br className="hidden sm:block" />
            <span className="text-[#C0422A] italic font-serif">Spirit</span> of the{" "}
            <br className="hidden sm:block" />
            Philippines.
          </motion.h1>

          {/* Subheadline */}
          <motion.p
            variants={itemVariants}
            className="text-[#D4B896]/80 text-sm md:text-base leading-relaxed mb-10 font-medium max-w-[440px] italic pr-4"
          >
            Buy directly from the makers of Barong. High quality, handmade clothes sent to your home.
          </motion.p>

          {/* CTA */}
          <motion.div variants={itemVariants}>
            <Link
              href="/login"
              className="inline-flex items-center justify-center w-full sm:w-auto gap-3 bg-[#C0422A] text-white px-8 py-4 rounded-full text-[10px] font-bold tracking-[0.2em] uppercase hover:bg-[#E8604A] hover:scale-105 transition-all duration-300 shadow-xl shadow-[#C0422A]/40 mb-16"
            >
              Start Shopping <ArrowRight className="w-4 h-4" />
            </Link>
          </motion.div>

          {/* Stats */}

        </motion.div>
      </main>

      {/* Footer */}
      <footer className="relative z-20 w-full px-8 py-6 flex items-center justify-center">
        <span className="text-[9px] font-bold tracking-[0.2em] text-[#D4B896]/40">
          © 2026 LUMBARONG PHILIPPINES
        </span>
      </footer>
    </div>
  );
}
