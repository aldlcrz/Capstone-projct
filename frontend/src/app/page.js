"use client";
import React, { useState, useEffect, useCallback } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import Image from "next/image";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import {
  Search,
  ChevronRight,
  SlidersHorizontal,
  Star,
  RefreshCw,
  ShoppingCart,
  ArrowRight,
  X
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, getStoredUserForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { normalizeProductImages, getProductImageSrc } from "@/lib/productImages";
import { fetchCategories, normalizeCategories } from "@/lib/categories";
import { Suspense } from "react";

function ShopContent() {
  const router = useRouter();
  const [activeCategory, setActiveCategory] = useState("ALL");
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");
  const [userRole, setUserRole] = useState(null);
  const [categories, setCategories] = useState([]);
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const searchParams = useSearchParams();

  useEffect(() => {
    const category = searchParams.get("category");
    const search = searchParams.get("search");
    const filter = searchParams.get("filter");
    
    if (category) setActiveCategory(category);
    if (search) setSearchQuery(search);
    
    // If filter is new_arrivals, we might want to reset category or keep it
    if (filter === 'new_arrivals') {
      setActiveCategory('New Arrivals');
    }
  }, [searchParams]);

  useEffect(() => {
    try {
      const stored = getStoredUserForRole("customer") || {};
      setUserRole(stored.role || "customer");
    } catch (e) {
      setUserRole("customer");
    }
  }, []);

  const showActions = true; // Enable quick purchase actions for all roles toggle for testing/UX versatility


  const { socket } = useSocket();

  const fetchProducts = useCallback(async () => {
    try {
      setLoading(true);
      const response = await api.get("/products");
      setProducts(response.data);
    } catch (error) {
      console.error("Failed to fetch products from backend.");
      setProducts([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  useEffect(() => {
    const loadCategories = async () => {
      try {
        const data = await fetchCategories();
        setCategories(normalizeCategories(data));
      } catch (err) {
        console.error("Failed to fetch categories from backend.");
        setCategories([]);
      }
    };

    loadCategories();
  }, []);

  useEffect(() => {
    if (!socket) return;

    const handleInventoryUpdated = (data) => {
      setProducts(prev => prev.map(p =>
        p.id === data.product.id ? { ...p, ...data.product } : p
      ));
    };

    const handleReviewUpdated = (data) => {
      setProducts((prev) => prev.map((product) =>
        String(product.id) === String(data.productId)
          ? {
            ...product,
            rating: Number(data.productRating || 0).toFixed(1),
            reviewCount: Number(data.productReviewCount || 0),
          }
          : product
      ));
    };

    const handleStatsUpdate = () => fetchProducts();
    const handleOrderCreated = () => fetchProducts();

    socket.on("inventory_updated", handleInventoryUpdated);
    socket.on("review_updated", handleReviewUpdated);
    socket.on("stats_update", handleStatsUpdate);
    socket.on("order_created", handleOrderCreated);

    return () => {
      socket.off("inventory_updated", handleInventoryUpdated);
      socket.off("review_updated", handleReviewUpdated);
      socket.off("stats_update", handleStatsUpdate);
      socket.off("order_created", handleOrderCreated);
    };
  }, [socket, fetchProducts]);

  // Replaced with library functions


  const addToCart = (product) => {
    router.push(`/products?id=${product.id}`);
  };

  const handleBuyNow = (product) => {
    router.push(`/products?id=${product.id}`);
  };

  const normalizeCategoryValue = (value) =>
    (value || "").toString().trim().toLowerCase();

  const getProductCategories = (product) => {
    if (Array.isArray(product?.categories)) {
      return product.categories
        .map((c) => (c || "").toString().trim())
        .filter(Boolean);
    }

    if (typeof product?.categories === "string" && product.categories.trim()) {
      try {
        const parsed = JSON.parse(product.categories);
        if (Array.isArray(parsed)) {
          return parsed.map((c) => (c || "").toString().trim()).filter(Boolean);
        }
      } catch {
        return product.categories
          .split(",")
          .map((c) => c.trim())
          .filter(Boolean);
      }
    }

    if (product?.category) {
      return [product.category.toString().trim()].filter(Boolean);
    }

    return [];
  };

  const categoryProductCounts = products.reduce((acc, product) => {
    const unique = Array.from(new Set(getProductCategories(product).map(normalizeCategoryValue)));
    unique.forEach((cat) => {
      acc[cat] = (acc[cat] || 0) + 1;
    });
    return acc;
  }, {});

  const filteredProducts = products.filter((product) => {
    const productCategories = getProductCategories(product);
    
    const matchesCategory =
      activeCategory === "ALL" ||
      activeCategory === "New Arrivals" ||
      productCategories.some(
        (category) => normalizeCategoryValue(category) === normalizeCategoryValue(activeCategory),
      );

    const s = searchQuery.toLowerCase();
    const matchesSearch =
      !searchQuery ||
      (product.name?.toLowerCase().includes(s)) ||
      (product.artisan?.toLowerCase().includes(s)) ||
      (product.description?.toLowerCase().includes(s)) ||
      (product.fabric_type?.toLowerCase().includes(s)) ||
      (product.artisan_region?.toLowerCase().includes(s)) ||
      (product.sku?.toLowerCase().includes(s));

    return matchesCategory && matchesSearch;
  });

  // Apply sorting for New Arrivals
  const displayProducts = activeCategory === 'New Arrivals' 
    ? [...filteredProducts].sort((a, b) => b.id - a.id) 
    : filteredProducts;

  const selectedCategoryLabel =
    activeCategory === "ALL"
      ? "All Categories"
      : activeCategory;


  return (
    <CustomerLayout>
      <div className="space-y-4 mb-20">


        {/* Product Grid - Denser Minimalist Layout */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4 lg:gap-5">
          <AnimatePresence>
            {loading ? (
              <div className="col-span-full py-32 text-center text-gray-500 opacity-50 italic animate-pulse">Loading items...</div>
            ) : displayProducts.map((product, i) => (
              <motion.div
                key={product.id}
                initial={{ opacity: 0, y: 15 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95 }}
                transition={{ delay: i * 0.05 }}
                className="group relative flex flex-col bg-white rounded-sm shadow-sm hover:-translate-y-1 hover:shadow-lg border border-transparent hover:border-black transition-all duration-300"
              >
                {/* Image Area - Tall Portrait for Full Apparel */}
                <div className="relative w-full aspect-2/3 bg-[#F7F3EE] overflow-hidden rounded-t-sm group/img pointer-events-auto">
                  <Link href={`/products?id=${product.id}`} className="absolute inset-0 block z-0" aria-label={`View ${product.name} details`}>
                    <Image
                      src={getProductImageSrc(product.image)}
                      alt={product.name}
                      fill
                      unoptimized
                      priority={i < 2}
                      sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                      className="object-cover object-top group-hover/img:scale-105 transition-transform duration-700 mix-blend-multiply opacity-90 group-hover/img:opacity-100"
                    />
                  </Link>

                </div>

                {/* Details Area */}
                <div className="px-2 pb-3 pt-2 space-y-1 flex-1 flex flex-col justify-between">
                  <Link href={`/products?id=${product.id}`} className="block flex-1">
                    <h3 className="text-[13px] leading-tight font-medium text-[#222] group-hover:text-black transition-colors line-clamp-2 min-h-[36px]">{product.name}</h3>
                  </Link>

                  <div className="flex flex-col mt-1 space-y-1">
                    <span className="text-[16px] font-medium text-black">₱{(product.price || 0).toLocaleString()}</span>
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-1">
                        <Star className="w-2.5 h-2.5 fill-yellow-400 text-yellow-400" />
                        <span className="text-[10px] text-[#757575]">{Number(product.rating || 0).toFixed(1)}</span>
                      </div>
                      <span className="text-[10px] text-[#757575] line-clamp-1">{product.artisan || "Trusted Seller"}</span>
                    </div>
                  </div>
                </div>
              </motion.div>
            ))}
          </AnimatePresence>
        </div>

        {/* Minimalist Category Selection Modal */}
        <AnimatePresence>
          {showCategoryModal && (
            <div className="fixed inset-0 z-110 flex items-center justify-center p-4">
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={() => setShowCategoryModal(false)}
                className="absolute inset-0 bg-[#2A1E14]/30 backdrop-blur-md"
              />
              <motion.div
                initial={{ opacity: 0, scale: 0.98, y: 10 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.98, y: 10 }}
                className="bg-white w-full max-w-lg sm:max-w-xl rounded-[2.5rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.15)] relative z-10 overflow-hidden flex flex-col border border-stone-200/50"
              >
                {/* Header Section */}
                <div className="px-8 sm:px-12 pt-10 sm:pt-12 pb-6 sm:pb-8">
                  <span className="text-[10px] font-bold uppercase tracking-[0.2em] text-black opacity-60">Catalogue</span>
                  <h2 className="font-serif text-lg sm:text-xl font-bold text-[#2A2A2A] mt-1">Browse Categories</h2>
                </div>

                <div className="flex-1 overflow-y-auto max-h-[50vh] sm:max-h-[60vh] px-6 sm:px-10 pb-10 no-scrollbar">
                  <div className="grid grid-cols-1 gap-1">
                    {["ALL", ...categories].map((cat) => {
                      const isSelected = activeCategory === cat;
                      const count =
                        cat === "ALL"
                          ? products.length
                          : (categoryProductCounts[normalizeCategoryValue(cat)] || 0);
                      const displayName = cat === "ALL"
                        ? "All Collections"
                        : cat.split(" ").map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(" ");

                      return (
                        <button
                          key={cat}
                          onClick={() => {
                            setActiveCategory(cat);
                            setShowCategoryModal(false);
                          }}
                          className={`w-full text-left px-6 py-4 rounded-2xl transition-all duration-300 flex items-center justify-between group ${isSelected
                              ? "bg-stone-50 text-black"
                              : "text-[#2A2A2A]/70 hover:bg-stone-50/50 hover:text-black"
                            }`}
                        >
                          <div className="flex items-center gap-3">
                            <span className={`text-base sm:text-lg font-serif ${isSelected ? "font-bold" : "font-medium"}`}>
                              {displayName}
                            </span>
                            <span className="text-[10px] px-2 py-0.5 rounded-full bg-stone-100 text-stone-500 font-bold">
                              {count}
                            </span>
                          </div>
                          {isSelected && (
                            <motion.div layoutId="active-dot" className="w-1.5 h-1.5 rounded-full bg-black" />
                          )}
                        </button>
                      );
                    })}
                  </div>
                </div>

                <div className="p-8 sm:p-10 border-t border-stone-100 bg-stone-50/30">
                  <button
                    onClick={() => setShowCategoryModal(false)}
                    className="w-full py-4 bg-white border border-stone-200 rounded-2xl flex items-center justify-between px-8 sm:px-10 hover:border-black transition-all group"
                  >
                    <span className="font-serif text-base sm:text-lg font-bold text-[#2A2A2A]">
                      Select Category
                    </span>
                    <div className="w-8 h-8 rounded-full bg-stone-100 flex items-center justify-center group-hover:bg-red-50 transition-colors">
                      <X className="w-4 h-4 text-[#2A2A2A] group-hover:text-black transition-colors" />
                    </div>
                  </button>
                </div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>

      </div>
    </CustomerLayout>
  );
}

export default function ShopPage() {
  return (
    <Suspense fallback={
      <div className="flex items-center justify-center min-h-screen">
        <div className="w-12 h-12 border-4 border-stone-200 border-t-[#C0420A] rounded-full animate-spin" />
      </div>
    }>
      <ShopContent />
    </Suspense>
  );
}
