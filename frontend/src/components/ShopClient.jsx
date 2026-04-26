"use client";
import React, { useEffect, useMemo, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import CustomerLayout from "./CustomerLayout";
import AdminLayout from "./AdminLayout";
import Image from "next/image";
import Link from "next/link";
import {
  Star,
  MapPin,
  MessageCircle,
  Loader2,
  Clock,
  Package,
  CheckCircle2,
  Instagram,
  Youtube,
  Music,
  Link as LinkIcon,
  Facebook,
  ChevronLeft,
  Search
} from "lucide-react";
import { api, getStoredUserForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { getProductImageSrc, resolveBackendImageUrl } from "@/lib/productImages";

// Removed MetricCard and SocialCard for the new premium layout inline implementation

import dynamic from "next/dynamic";
const ShopMap = dynamic(() => import("./ShopMap"), { ssr: false });
import { X, Navigation, ShieldAlert } from "lucide-react";
import ReportModal from "./ReportModal";

export default function ShopClient() {
  const searchParams = useSearchParams();
  const id = searchParams.get("id");
  const router = useRouter();

  const [seller, setSeller] = useState(null);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [userRole, setUserRole] = useState(null);
  const [activeTab, setActiveTab] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [isMapOpen, setIsMapOpen] = useState(false);
  const [isReportModalOpen, setIsReportModalOpen] = useState(false);
  const { socket } = useSocket();

  useEffect(() => {
    try {
      const customerUser = getStoredUserForRole("customer");
      setUserRole(customerUser?.role || "customer");
    } catch {
      setUserRole("customer");
    }
  }, []);

  const displayedProducts = useMemo(() => {
    let nextProducts = [...products];

    if (activeTab === "sale") {
      nextProducts = nextProducts.filter(() => false);
    } else if (activeTab === "rated") {
      nextProducts = nextProducts.sort(
        (a, b) => (Number(b.rating) || 0) - (Number(a.rating) || 0)
      );
    }
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase();
      nextProducts = nextProducts.filter((p) =>
        (p.name && p.name.toLowerCase().includes(q)) ||
        (p.description && p.description.toLowerCase().includes(q))
      );
    }

    return nextProducts;
  }, [products, activeTab, searchQuery]);

  const socialCards = useMemo(() => {
    if (!seller) return [];

    let links = seller.socialLinks;
    if (typeof links === "string") {
      try {
        links = JSON.parse(links);
      } catch {
        links = [];
      }
    }
    if (!Array.isArray(links)) links = [];

    const parsedLinks = links
      .filter((link) => link.url && link.url.trim())
      .map((link) => {
        const href = link.url.startsWith("http") ? link.url : `https://${link.url}`;
        const lowerHref = href.toLowerCase();

        if (lowerHref.includes("facebook.com")) {
          return {
            icon: <Facebook className="h-4 w-4 text-[#1877F2]" />,
            label: link.label || "Facebook",
            href,
            linkLabel: "View Profile",
            color: "#1877F2",
          };
        }

        if (lowerHref.includes("instagram.com")) {
          return {
            icon: <Instagram className="h-4 w-4 text-[#E4405F]" />,
            label: link.label || "Instagram",
            href,
            linkLabel: "View Page",
            color: "#E4405F",
          };
        }

        if (lowerHref.includes("tiktok.com")) {
          return {
            icon: <Music className="h-4 w-4 text-[#111111]" />,
            label: link.label || "TikTok",
            href,
            linkLabel: "View Profile",
            color: "#111111",
          };
        }

        if (lowerHref.includes("youtube.com")) {
          return {
            icon: <Youtube className="h-4 w-4 text-[#FF0000]" />,
            label: link.label || "YouTube",
            href,
            linkLabel: "Watch Channel",
            color: "#FF0000",
          };
        }

        return {
          icon: <LinkIcon className="h-4 w-4 text-[var(--rust)]" />,
          label: link.label || "Link",
          href,
          linkLabel: "Open Link",
          color: "var(--rust)",
        };
      });

    if (parsedLinks.length > 0) return parsedLinks;

    const fallbacks = [];
    if (seller.facebookLink) {
      fallbacks.push({
        icon: <Facebook className="h-4 w-4 text-[#1877F2]" />,
        label: "Facebook",
        href: seller.facebookLink.startsWith("http")
          ? seller.facebookLink
          : `https://${seller.facebookLink}`,
        linkLabel: "View Profile",
        color: "#1877F2",
      });
    }

    if (seller.instagramLink) {
      fallbacks.push({
        icon: <Instagram className="h-4 w-4 text-[#E4405F]" />,
        label: "Instagram",
        href: seller.instagramLink.startsWith("http")
          ? seller.instagramLink
          : `https://${seller.instagramLink}`,
        linkLabel: "View Page",
        color: "#E4405F",
      });
    }

    return fallbacks;
  }, [seller]);

  useEffect(() => {
    if (!id) {
      setLoading(false);
      return;
    }

    const fetchShopData = async () => {
      try {
        const [sellerRes, productsRes] = await Promise.all([
          api.get(`/users/seller/${id}`),
          api.get(`/products?seller=${id}`),
        ]);
        setSeller(sellerRes.data);
        setProducts(productsRes.data);
      } catch (error) {
        console.warn("Artisan not found in municipal registry.", error);
      } finally {
        setLoading(false);
      }
    };

    fetchShopData();
  }, [id]);

  useEffect(() => {
    if (!socket || !id) return;

    const handleReviewUpdated = (data) => {
      if (String(data.sellerId) !== String(id)) return;

      setProducts((prev) => prev.map((product) =>
        String(product.id) === String(data.productId)
          ? {
              ...product,
              rating: Number(data.productRating || 0).toFixed(1),
              reviewCount: Number(data.productReviewCount || 0),
            }
          : product
      ));

      setSeller((prev) => prev ? {
        ...prev,
        rating: Number(data.sellerRating || 0).toFixed(1),
        reviewCount: Number(data.sellerReviewCount || 0),
      } : prev);
    };

    socket.on("review_updated", handleReviewUpdated);

    return () => {
      socket.off("review_updated", handleReviewUpdated);
    };
  }, [socket, id]);

  const Layout = userRole === "admin" ? AdminLayout : CustomerLayout;

  if (loading) {
    return (
      <Layout>
        <div className="flex h-[70vh] flex-col items-center justify-center space-y-6">
          <Loader2 className="h-10 w-10 animate-spin text-[var(--rust)] opacity-30" />
          <p className="font-serif italic text-[var(--muted)]">Opening artisan workshop...</p>
        </div>
      </Layout>
    );
  }

  if (!id) {
    return (
      <Layout>
        <div className="flex h-[70vh] items-center justify-center px-4 text-center text-[var(--muted)]">
          Select a shop first to open its collection.
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="min-h-screen bg-stone-50 pb-24 font-sans sm:pb-28 lg:pb-20">
        {/* Back Button */}
        <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 pt-6 pb-2 flex justify-between items-center">
           <button 
             onClick={() => router.back()} 
             className="flex items-center gap-1.5 text-xs font-bold text-stone-500 hover:text-[var(--rust)] transition-colors"
           >
             <ChevronLeft className="h-4 w-4" /> Back to previous
           </button>

           {userRole !== "admin" && (
             <button 
               onClick={() => setIsReportModalOpen(true)}
               className="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-red-500 hover:text-red-700 transition-colors bg-white px-3 py-1.5 rounded-full border border-red-100 shadow-sm"
             >
               <ShieldAlert className="w-3.5 h-3.5" /> Report Store
             </button>
           )}
        </div>

        {/* Profile Info - Shopee Style Layout */}
        <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 mb-6">
          <div className="bg-white rounded-md shadow-sm border border-stone-200 flex flex-col md:flex-row overflow-hidden">
            
            {/* Left Side: Dark Banner */}
            <div className="bg-[#1A1A1A] w-full md:w-[380px] p-5 flex flex-col justify-between shrink-0 relative overflow-hidden">
              <div className="absolute inset-0 opacity-[0.03] bg-white mix-blend-overlay" />
              <div className="relative z-10 flex gap-4 items-center">
                <div className="w-[72px] h-[72px] rounded-full border border-white/20 bg-stone-100 overflow-hidden shrink-0 flex items-center justify-center font-serif text-3xl text-stone-400">
                  {seller?.profilePhoto ? (
                    <img
                      src={resolveBackendImageUrl(seller.profilePhoto, "/images/placeholder.png")}
                      alt={seller.shopName}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    seller?.shopName?.[0] || "A"
                  )}
                </div>
                <div className="text-left text-white">
                  <h1 className="font-serif text-[17px] font-bold leading-tight flex items-center gap-1.5 tracking-wide">
                    {seller?.shopName || "Artisan Workshop"}
                    {seller?.isVerified && <CheckCircle2 className="w-3.5 h-3.5 text-[#A1D4B1]" />}
                  </h1>
                  <div className="text-white/60 text-[11px] mt-1.5 flex items-center gap-1 font-medium tracking-wide">
                    <MapPin className="w-3 h-3 opacity-80" /> {seller?.location || "Lumban, Laguna"}
                  </div>
                </div>
              </div>
              <div className="relative z-10 flex gap-2.5 mt-5 w-full">
                {userRole !== "admin" && (
                  <Link
                    href={`/messages?sellerId=${id}&sellerName=${seller?.shopName}`}
                    className="flex-1 flex items-center justify-center gap-1.5 border border-white/30 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white transition-all hover:bg-white/10 rounded-sm"
                  >
                    <MessageCircle className="h-3 w-3" /> Message
                  </Link>
                )}
                {seller?.shopLatitude && seller?.shopLongitude && (
                   <button onClick={() => setIsMapOpen(true)} className="flex-1 flex items-center justify-center gap-1.5 border border-white/30 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white transition-all hover:bg-white/10 rounded-sm">
                      <MapPin className="h-3 w-3" /> View Map
                   </button>
                )}
              </div>
            </div>

            {/* Right Side: Stats */}
            <div className="flex-1 p-5 sm:p-6 md:p-8 flex items-center bg-white">
               <div className="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8 w-full">
                  <div className="flex items-center gap-3 text-sm">
                     <Package className="w-4 h-4 text-stone-400" />
                     <span className="text-stone-500 font-medium">Masterpieces:</span>
                     <span className="text-[var(--rust)] font-bold">{seller?.productCount ?? products.length}</span>
                  </div>
                  <div className="flex items-center gap-3 text-sm">
                     <Star className="w-4 h-4 text-stone-400" />
                     <span className="text-stone-500 font-medium">Rating:</span>
                     <span className="text-[var(--rust)] font-bold">{Number(seller?.rating || 0.0).toFixed(1)}</span>
                  </div>
                  <div className="flex items-center gap-3 text-sm">
                     <MessageCircle className="w-4 h-4 text-stone-400" />
                     <span className="text-stone-500 font-medium">Response:</span>
                     <span className="text-[var(--rust)] font-bold">{seller?.responseRate || "100%"}</span>
                  </div>
                  <div className="flex items-center gap-3 text-sm">
                     <Clock className="w-4 h-4 text-stone-400" />
                     <span className="text-stone-500 font-medium">Established:</span>
                     <span className="text-[var(--rust)] font-bold">{seller?.establishedOn || seller?.joined || "April 2026"}</span>
                  </div>
               </div>
            </div>

          </div>
        </div>

        <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
          <div className="mb-6 flex flex-col items-center text-center">
            <h3 className="font-serif text-xl sm:text-2xl font-bold tracking-tight text-[var(--charcoal)] mb-4">
              The <span className="text-[var(--rust)] italic">Collection</span>
            </h3>

            <div className="w-full max-w-md relative mb-6">
              <input
                type="text"
                placeholder="Search in this shop..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-white border border-stone-200 rounded-full py-2.5 pl-11 pr-4 text-sm font-medium text-stone-700 outline-none focus:border-[var(--rust)] transition-colors shadow-sm"
              />
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" />
            </div>

            <div className="no-scrollbar flex w-full max-w-md gap-2 overflow-x-auto pb-1 text-[10px] sm:text-xs font-bold uppercase tracking-widest justify-center">
              <button
                onClick={() => setActiveTab("all")}
                className={`shrink-0 rounded-full px-6 py-2.5 transition-colors ${
                  activeTab === "all"
                    ? "bg-[var(--rust)] text-white shadow-md shadow-[var(--rust)]/20"
                    : "bg-white text-stone-500 hover:text-[var(--rust)] hover:bg-stone-100"
                }`}
              >
                All Pieces
              </button>
              <button
                onClick={() => setActiveTab("sale")}
                className={`shrink-0 rounded-full px-6 py-2.5 transition-colors ${
                  activeTab === "sale"
                    ? "bg-[var(--rust)] text-white shadow-md shadow-[var(--rust)]/20"
                    : "bg-white text-stone-500 hover:text-[var(--rust)] hover:bg-stone-100"
                }`}
              >
                On Sale
              </button>
              <button
                onClick={() => setActiveTab("rated")}
                className={`shrink-0 rounded-full px-6 py-2.5 transition-colors ${
                  activeTab === "rated"
                    ? "bg-[var(--rust)] text-white shadow-md shadow-[var(--rust)]/20"
                    : "bg-white text-stone-500 hover:text-[var(--rust)] hover:bg-stone-100"
                }`}
              >
                Highest Rated
              </button>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
            {displayedProducts.map((product) => (
              <Link
                key={product.id}
                href={`/products?id=${product.id}`}
                className="group relative flex flex-col bg-white rounded-sm shadow-sm hover:-translate-y-1 hover:shadow-lg border border-transparent hover:border-[var(--rust)] transition-all duration-300"
              >
                <div className="relative aspect-square overflow-hidden bg-[#F7F3EE] rounded-t-sm">
                  <img
                    src={getProductImageSrc(product.image)}
                    alt={product.name}
                    className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 mix-blend-multiply opacity-90 group-hover:opacity-100"
                  />
                  {product.stock <= 5 && (
                    <div className="absolute bottom-2 left-2 rounded-sm bg-[var(--rust)] px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-white">
                      Limited
                    </div>
                  )}
                </div>

                <div className="flex flex-1 flex-col justify-between p-3 sm:p-4">
                  <div>
                    <h4 className="mb-1.5 line-clamp-2 text-[13px] font-medium leading-tight text-[#222] transition-colors group-hover:text-[var(--rust)]">
                      {product.name}
                    </h4>
                    <div className="flex items-center gap-1.5 mb-2">
                      <Star className="w-3 h-3 fill-yellow-400 text-yellow-400" />
                      <span className="text-[10px] font-medium text-stone-500">{Number(product.rating || 0).toFixed(1)}</span>
                    </div>
                  </div>

                  <div className="flex items-end justify-between gap-3 mt-1">
                    <div className="text-sm font-bold text-[var(--rust)]">
                      {"\u20B1"}{(product.price || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </div>
                    <div className="text-[10px] text-stone-400 uppercase tracking-wider font-bold">
                      Sold {product.soldCount || 0}
                    </div>
                  </div>
                </div>
              </Link>
            ))}
          </div>

          {products.length === 0 && (
            <div className="rounded-2xl border-2 border-dashed border-gray-200 px-6 py-16 text-center sm:py-24">
              <p className="font-serif italic text-gray-400">
                This artisan has not released any collection to the registry yet.
              </p>
            </div>
          )}
        </div>
      </div>
      
      {isMapOpen && (
        <ShopMap 
          seller={seller} 
          onClose={() => setIsMapOpen(false)} 
        />
      )}

      {seller && (
        <ReportModal
          isOpen={isReportModalOpen}
          onClose={() => setIsReportModalOpen(false)}
          reportedId={seller.id}
          type="CustomerReportingSeller"
          reportedName={seller.shopName}
        />
      )}
    </Layout>
  );
}
