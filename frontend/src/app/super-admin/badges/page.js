"use client";
import React, { useState, useEffect, useCallback } from "react";
import Image from "next/image";
import SuperAdminLayout from "@/components/SuperAdminLayout";
import { Award, ShieldCheck, CheckCircle2, Loader2, Search } from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";

const BADGES = [
  {
    type: "certified_lumban_artisan",
    title: "Certified Lumban Artisan",
    desc: "Officially registered Lumban embroidery workshop.",
    badgeClass: "bg-amber-500/10 border-amber-500/30 text-amber-400",
  },
  {
    type: "hand_embroidered_master",
    title: "Hand Embroidery Master",
    desc: "Craftsperson specializing in authentic hand-made embroidery.",
    badgeClass: "bg-indigo-500/10 border-indigo-500/30 text-indigo-400",
  },
  {
    type: "heritage_craftsperson",
    title: "Heritage Craftsperson",
    desc: "Legacy workshop preserving Barong Tagalog traditions.",
    badgeClass: "bg-emerald-500/10 border-emerald-500/30 text-emerald-400",
  },
];

export default function SuperAdminBadgesPage() {
  const [loading, setLoading] = useState(true);
  const [sellers, setSellers] = useState([]);
  const [search, setSearch] = useState("");
  const [updatingId, setUpdatingId] = useState(null);

  const fetchBadges = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get("/super-admin/badges");
      if (res.data && Array.isArray(res.data.sellers)) {
        setSellers(res.data.sellers);
      }
    } catch (err) {
      console.error("Failed to load artisan seller badges", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchBadges();
  }, [fetchBadges]);

  const handleToggleBadge = async (sellerId, badgeType) => {
    setUpdatingId(`${sellerId}-${badgeType}`);
    try {
      const res = await api.post("/super-admin/badges/toggle", {
        seller_id: sellerId,
        badge_type: badgeType,
      });
      if (res.data && res.data.status === "success") {
        setSellers((prev) =>
          prev.map((seller) => {
            if (seller.id === sellerId) {
              return { ...seller, artisanBadges: res.data.badges };
            }
            return seller;
          })
        );
      }
    } catch (err) {
      console.error("Failed to toggle badge", err);
    } finally {
      setUpdatingId(null);
    }
  };

  const filteredSellers = sellers.filter(
    (s) =>
      (s.name && s.name.toLowerCase().includes(search.toLowerCase())) ||
      (s.shopName && s.shopName.toLowerCase().includes(search.toLowerCase())) ||
      (s.email && s.email.toLowerCase().includes(search.toLowerCase()))
  );

  const resolveImage = (path) => {
    if (!path) return `https://ui-avatars.com/api/?background=2d2d5e&color=818cf8&size=128`;
    if (path.startsWith("http")) return path;
    const backendUrl = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";
    return `${backendUrl}${path.startsWith("/") ? "" : "/"}${path}`;
  };

  return (
    <SuperAdminLayout>
      <div className="space-y-8">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div className="flex items-center gap-1.5 text-xs font-bold tracking-widest uppercase mb-1" style={{ color: "#818cf8" }}>
              <Award className="w-3.5 h-3.5" /> Heritage Governance
            </div>
            <h1 className="font-serif text-3xl font-bold tracking-tight text-white">
              Artisan{" "}
              <span className="italic" style={{ color: "#818cf8" }}>
                Heritage Badges
              </span>
            </h1>
            <p className="text-xs text-slate-400 mt-1 font-medium">
              Grant digital certificates of authenticity and heritage badges to verified Lumban artisan workshops.
            </p>
          </div>

          <div className="relative">
            <input
              type="text"
              placeholder="Search artisan shop..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-10 pr-4 py-3 rounded-2xl text-xs font-bold bg-[#1a1a2e] border border-[#2d2d5e] text-white focus:outline-none focus:border-[#818cf8] w-64"
            />
            <Search className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
          </div>
        </div>

        {/* Sellers Grid */}
        {loading ? (
          <div className="py-24 flex flex-col items-center justify-center gap-3">
            <Loader2 className="w-8 h-8 animate-spin" style={{ color: "#818cf8" }} />
            <p className="text-[10px] font-black uppercase tracking-widest text-slate-500">
              Fetching artisan registry...
            </p>
          </div>
        ) : filteredSellers.length === 0 ? (
          <div className="p-16 rounded-3xl border text-center text-xs font-bold uppercase tracking-widest text-slate-500" style={{ background: "rgba(26,26,46,0.7)", borderColor: "rgba(45,45,94,0.8)" }}>
            No artisan sellers found matching your search.
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-6">
            {filteredSellers.map((seller) => {
              const currentBadges = (seller.artisanBadges || []).map((b) => b.badge_type);
              return (
                <motion.div
                  key={seller.id}
                  initial={{ opacity: 0, y: 15 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="p-6 rounded-3xl border flex flex-col lg:flex-row lg:items-center justify-between gap-6"
                  style={{ background: "rgba(26,26,46,0.7)", borderColor: "rgba(45,45,94,0.8)" }}
                >
                  {/* Shop Info */}
                  <div className="flex items-center gap-4">
                    <div className="relative w-14 h-14 rounded-2xl border overflow-hidden shrink-0" style={{ borderColor: "rgba(45,45,94,0.8)" }}>
                      <Image
                        src={resolveImage(seller.profilePhoto)}
                        alt={seller.shopName || seller.name}
                        fill
                        sizes="56px"
                        className="object-cover"
                        unoptimized
                      />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="text-base font-bold text-white">{seller.shopName || seller.name}</h3>
                        {seller.isVerified && <ShieldCheck className="w-4 h-4 text-emerald-400" />}
                      </div>
                      <p className="text-xs text-slate-400 font-medium">{seller.email}</p>
                    </div>
                  </div>

                  {/* Badges Toggles */}
                  <div className="flex flex-wrap items-center gap-3">
                    {BADGES.map((b) => {
                      const active = currentBadges.includes(b.type);
                      const key = `${seller.id}-${b.type}`;
                      const isUpdating = updatingId === key;
                      return (
                        <button
                          key={b.type}
                          onClick={() => handleToggleBadge(seller.id, b.type)}
                          disabled={isUpdating}
                          className={`px-4 py-2.5 rounded-2xl text-xs font-bold flex items-center gap-2 border transition-all ${
                            active
                              ? b.badgeClass
                              : "bg-[#0f0f1a] border-[#2d2d5e] text-slate-500 hover:text-slate-300"
                          }`}
                        >
                          {isUpdating ? (
                            <Loader2 className="w-4 h-4 animate-spin" />
                          ) : active ? (
                            <CheckCircle2 className="w-4 h-4" />
                          ) : (
                            <Award className="w-4 h-4 opacity-50" />
                          )}
                          {b.title}
                        </button>
                      );
                    })}
                  </div>
                </motion.div>
              );
            })}
          </div>
        )}
      </div>
    </SuperAdminLayout>
  );
}
