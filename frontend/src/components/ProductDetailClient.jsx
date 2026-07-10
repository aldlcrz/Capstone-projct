/* eslint-disable @next/next/no-img-element */
"use client";
import React, { useState, useEffect, useCallback, useMemo, useRef } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useSearchParams, useRouter, usePathname } from "next/navigation";
import CustomerLayout from "./CustomerLayout";
import AdminLayout from "./AdminLayout";
import SellerLayout from "./SellerLayout";
import Image from "next/image";
import Link from "next/link";
import {
  ShoppingCart,
  Star,
  Truck,
  Loader2,
  ChevronRight,
  ChevronLeft,
  ChevronDown,
  ShieldCheck,
  MessageCircle,
  MapPin,
  Package,
  Award,
  Camera,
  Minus,
  Plus,
  Store,
  Ruler,
  X as CloseIcon,
  ShieldAlert,
  CheckCircle2,
  Info,
  BookOpen,
  Heart
} from "lucide-react";
import ReportModal from "./ReportModal";
import AuthGateModal from "./AuthGateModal";
import { api, getStoredUserForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { normalizeProductImages, normalizeProductSizes, resolveBackendImageUrl } from "@/lib/productImages";
import { setCustomerScopedJson, getCustomerScopedJson } from "@/lib/customerStorage";

const fabricGuide = {
  "Piña": {
    description: "The 'Queen of Philippine Fabrics'. Hand-loomed from pineapple leaf fibers. It is translucent, delicate, and has a natural shimmer.",
    origin: "Aklan / Lumban",
    care: "Hand wash with mild detergent. Do not wring. Iron while damp."
  },
  "Jusilyn": {
    description: "A popular choice for modern Barongs. Made from mechanically woven silk and polyester. It's more durable than Piña but maintains a similar look.",
    origin: "Laguna",
    care: "Dry clean or hand wash. Iron on low to medium heat."
  },
  "Organza": {
    description: "A thin, plain weave, sheer fabric traditionally made from silk. Modern versions often use synthetic fibers like polyester.",
    origin: "Various",
    care: "Steam or iron on very low heat."
  },
  "Cocoon": {
    description: "A premium fabric that mimics the texture of Piña but with more body. Known for its distinct slubs and natural texture.",
    origin: "Lumban",
    care: "Dry clean recommended."
  }
};

const parseStoredList = (value) => {
  if (Array.isArray(value)) return value;
  if (typeof value !== "string") return [];

  const trimmed = value.trim();
  if (!trimmed) return [];

  try {
    const parsed = JSON.parse(trimmed);
    if (Array.isArray(parsed)) return parsed;
    if (typeof parsed === "string" && parsed.trim()) return [parsed];
  } catch (error) { }

  if (trimmed.startsWith("[") || trimmed.endsWith("]")) return [];

  return [trimmed];
};

const normalizeReviewImages = (value) =>
  parseStoredList(value)
    .map((item) => resolveBackendImageUrl(item, null))
    .filter(Boolean);

const normalizeReview = (review) => {
  if (!review || typeof review !== "object") return null;

  return {
    ...review,
    images: normalizeReviewImages(review.images),
    customer: review.customer
      ? {
        ...review.customer,
        profilePhoto: resolveBackendImageUrl(review.customer.profilePhoto, null),
      }
      : review.customer,
  };
};

export default function ProductDetailClient() {
  const searchParams = useSearchParams();
  const id = searchParams.get("id");
  const vParam = searchParams.get("v"); // For variation persistence
  const previewMode = searchParams.get("preview") === "1"; // Seller preview mode
  const router = useRouter();
  const pathname = usePathname();
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedSize, setSelectedSize] = useState(null);
  const [quantity, setQuantity] = useState(1);
  const [activeImage, setActiveImage] = useState(0);
  const [userRole, setUserRole] = useState(null);
  const [addedToCart, setAddedToCart] = useState(false);
  const [selectionError, setSelectionError] = useState(false);
  const [newRating, setNewRating] = useState(5);
  const [newComment, setNewComment] = useState("");
  const [submittingReview, setSubmittingReview] = useState(false);
  const [reviewError, setReviewError] = useState(null);
  const [seller, setSeller] = useState(null);
  const [showSizeGuide, setShowSizeGuide] = useState(false);
  const [sizeGuideTab, setSizeGuideTab] = useState('men');
  const [activeReviewFilter, setActiveReviewFilter] = useState("all");
  const [expandedReviewId, setExpandedReviewId] = useState(null);
  const [expandedImageIndex, setExpandedImageIndex] = useState(0);
  const [isReportModalOpen, setIsReportModalOpen] = useState(false);
  const [showFabricGuide, setShowFabricGuide] = useState(false);
  const [activeFabric, setActiveFabric] = useState(null);
  const [maintenanceMode, setMaintenanceMode] = useState(false);
  const [isZoomOpen, setIsZoomOpen] = useState(false);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const [authModalConfig, setAuthModalConfig] = useState({ message: "", redirectPath: "" });
  const isManualChange = useRef(false);

  const handleExpandReviewImage = useCallback((reviewId, index) => {
    if (expandedReviewId === reviewId && expandedImageIndex === index) {
      setExpandedReviewId(null);
    } else {
      setExpandedReviewId(reviewId);
      setExpandedImageIndex(index);
    }
  }, [expandedReviewId, expandedImageIndex]);

  const fetchSeller = useCallback(async (sellerId) => {
    if (!sellerId) {
      setSeller(null);
      return;
    }

    try {
      const response = await api.get(`/users/seller/${sellerId}`);
      setSeller(response.data);
    } catch (error) {
      setSeller(null);
    }
  }, []);

  useEffect(() => {
    try {
      const isSellerRoute = pathname?.startsWith('/seller');
      const isAdminRoute = pathname?.startsWith('/admin');

      const adminData = getStoredUserForRole("admin");
      const sellerData = getStoredUserForRole("seller");
      const customerData = getStoredUserForRole("customer");

      if (isAdminRoute && adminData?.role === 'admin') {
        setUserRole("admin");
      } else if (isSellerRoute && sellerData?.role === 'seller') {
        setUserRole("seller");
      } else if (previewMode && sellerData?.role === 'seller') {
        setUserRole("seller-preview");
      } else {
        setUserRole(customerData?.role || "guest");
      }
    } catch (e) {
      setUserRole("guest");
    }
  }, [pathname, previewMode]);

  const isRestricted = userRole === "admin" || userRole === "seller-preview";
  const showActions = !isRestricted;

  const fetchProduct = useCallback(async () => {
    try {
      const publicSettings = await api.get("/admin/public-settings");
      if (publicSettings.data.maintenanceMode !== undefined) {
        setMaintenanceMode(publicSettings.data.maintenanceMode === true || publicSettings.data.maintenanceMode === "true");
      }
      const res = await api.get(`/products/${id}`);
      const normalizedSizes = normalizeProductSizes(res.data.sizes);
      setProduct({
        ...res.data,
        sizes: normalizedSizes,
        reviews: Array.isArray(res.data.reviews)
          ? res.data.reviews.map((review) => normalizeReview(review)).filter(Boolean)
          : [],
        sku: res.data.sku,
        fabric_type: res.data.fabric_type,
        collar_type: res.data.collar_type,
        artisan_region: res.data.artisan_region,
      });

      fetchSeller(res.data.sellerId);
    } catch (err) {
      console.warn("Artisan entry not found in registry.", err);
      setProduct({
        id: id,
        name: "Premium Barong Polo Shirt Men Filipino Traditional Printing Loose Casual T-Shirt Fashionable Short Sleeves Comfortable Breathable",
        price: 255,
        description: "A signature hand-embroidered order from the heart of Lumban. It features ultra-premium materials and traditional Filipiniana crafting perfectly suited for modern occasions.",
        sizes: ["S", "M", "L", "XL", "2XL"],
        category: "Formal",
        artisan: "Lumban Master Craft",
        rating: 5.0,
        image: [
          { url: "/images/product1.png", variation: "25Q3MPL005-DY63535" },
          { url: "/images/product2.png", variation: "25Q3MPL005-LF18094" },
          { url: "/images/product3.png", variation: "25Q3MPL005-LF20638" },
          { url: "/images/product4.png", variation: "25Q3MPL005-LF17449" },
        ]
      });
    } finally {
      setLoading(false);
    }
  }, [id, fetchSeller]);

  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (userRole === "guest") {
      setAuthModalConfig({
        message: "You need to log in to share your thoughts on this masterpiece.",
        redirectPath: window.location.href
      });
      setIsAuthModalOpen(true);
      return;
    }
    if (!newComment.trim() || isRestricted) return;

    setSubmittingReview(true);
    setReviewError(null);
    try {
      await api.post(`/products/${id}/reviews`, {
        rating: newRating,
        comment: newComment
      });
      setNewComment("");
      setNewRating(5);
      if (!socket) {
        fetchProduct();
      }
    } catch (err) {
      setReviewError(err.response?.data?.message || "Failed to submit review");
    } finally {
      setSubmittingReview(false);
    }
  };

  const { socket } = useSocket();

  useEffect(() => {
    if (!id) {
      setLoading(false);
      return;
    }
    fetchProduct();

    if (socket) {
      const handleInventoryUpdated = (data) => {
        if (String(data.product.id) === String(id)) {
          setProduct(prev => ({ ...prev, ...data.product }));
        }
      };

      const handleReviewUpdated = (data) => {
        if (String(data.productId) !== String(id)) return;

        const nextLatestReview = data.latestReview ? normalizeReview(data.latestReview) : null;

        setProduct((prev) => {
          if (!prev) return prev;

          const nextReviews = nextLatestReview
            ? [
              nextLatestReview,
              ...(Array.isArray(prev.reviews) ? prev.reviews : []).filter(
                (review) => String(review.id) !== String(nextLatestReview.id)
              ),
            ]
            : (Array.isArray(prev.reviews) ? prev.reviews : []);

          return {
            ...prev,
            rating: Number(data.productRating || 0).toFixed(1),
            reviewCount: Number(data.productReviewCount || nextReviews.length),
            reviews: nextReviews,
          };
        });

        setSeller((prev) => (
          prev && String(prev.id) === String(data.sellerId)
            ? {
              ...prev,
              rating: Number(data.sellerRating || 0).toFixed(1),
              reviewCount: Number(data.sellerReviewCount || 0),
            }
            : prev
        ));
      };

      const handleSettingsUpdated = (data) => {
        if (data.maintenanceMode !== undefined) {
          setMaintenanceMode(data.maintenanceMode === true || data.maintenanceMode === "true");
        }
      };

      socket.on('inventory_updated', handleInventoryUpdated);
      socket.on('review_updated', handleReviewUpdated);
      socket.on('settings_updated', handleSettingsUpdated);

      return () => {
        socket.off('inventory_updated', handleInventoryUpdated);
        socket.off('review_updated', handleReviewUpdated);
        socket.off('settings_updated', handleSettingsUpdated);
      };
    }

    return undefined;
  }, [id, socket, fetchProduct]);

  const galleryImages = useMemo(() => {
    const images = normalizeProductImages(product?.image);
    return images.length > 0 ? images : [{ url: "/images/product1.png", variation: "Original" }];
  }, [product?.image]);

  const reviewList = useMemo(
    () => (Array.isArray(product?.reviews) ? product.reviews : []),
    [product?.reviews]
  );

  const productRating = Number(product?.rating || 0);
  const productReviewCount = Number(product?.reviewCount ?? reviewList.length ?? 0);
  const reviewCommentCount = useMemo(
    () => reviewList.filter((review) => review.comment && review.comment.trim()).length,
    [reviewList]
  );
  const reviewMediaCount = useMemo(
    () => reviewList.filter((review) => Array.isArray(review.images) && review.images.length > 0).length,
    [reviewList]
  );

  const reviewFilters = useMemo(() => {
    const countByStars = (stars) => reviewList.filter((review) => Number(review.rating) === stars).length;

    return [
      { key: "all", label: "All Notes", count: reviewList.length },
      { key: "5", label: "5 Star", count: countByStars(5) },
      { key: "4", label: "4 Star", count: countByStars(4) },
      { key: "3", label: "3 Star", count: countByStars(3) },
      { key: "2", label: "2 Star", count: countByStars(2) },
      { key: "1", label: "1 Star", count: countByStars(1) },
      { key: "commented", label: "commented", count: reviewCommentCount },
      { key: "media", label: "With Media", count: reviewMediaCount },
    ];
  }, [reviewList, reviewCommentCount, reviewMediaCount]);

  const filteredReviews = useMemo(() => {
    switch (activeReviewFilter) {
      case "commented":
        return reviewList.filter((review) => review.comment && review.comment.trim());
      case "media":
        return reviewList.filter((review) => Array.isArray(review.images) && review.images.length > 0);
      case "5":
      case "4":
      case "3":
      case "2":
      case "1":
        return reviewList.filter((review) => Number(review.rating) === Number(activeReviewFilter));
      default:
        return reviewList;
    }
  }, [reviewList, activeReviewFilter]);

  useEffect(() => {
    if (vParam && galleryImages.length > 0) {
      if (isManualChange.current) return;

      const index = galleryImages.findIndex(img =>
        String(img.variation).toLowerCase() === String(vParam).toLowerCase()
      );
      if (index !== -1) {
        setActiveImage(prev => {
          if (prev !== index) return index;
          return prev;
        });
      }
    }
  }, [vParam, galleryImages]);

  const handleVariationSelect = (index) => {
    isManualChange.current = true;
    const variation = galleryImages[index]?.variation || "Original";
    setActiveImage(index);

    const params = new URLSearchParams(window.location.search);
    params.set("v", variation);
    router.replace(`${window.location.pathname}?${params.toString()}`, { scroll: false });

    setTimeout(() => {
      isManualChange.current = false;
    }, 500);
  };

  const productCategory = Array.isArray(product?.categories) && product.categories.length > 0
    ? product.categories[0]
    : (product?.category || "Uncategorized");

  const handleAddToCart = () => {
    if (userRole === "guest") {
      setAuthModalConfig({
        message: "Sign in to start building your personal heritage collection.",
        redirectPath: window.location.href
      });
      setIsAuthModalOpen(true);
      return;
    }
    if (!product || userRole === 'admin' || maintenanceMode) return;
    const selectedSizeInfo = typeof availableSizes[0] === 'object'
      ? availableSizes.find(s => (s.size || s.name) === selectedSize)
      : null;
    const maxStock = selectedSizeInfo ? (selectedSizeInfo.stock || 0) : (product.stock || 0);

    if (maxStock <= 0) {
      alert("This size is currently out of stock.");
      return;
    }

    if (!selectedSize) {
      setSelectionError(true);
      alert("Please select a size first.");
      return;
    }
    const currentVariation = galleryImages[activeImage]?.variation || "Original";
    const storedCart = getCustomerScopedJson("cart", []);
    const cart = Array.isArray(storedCart) ? storedCart : [];
    const existingIndex = cart.findIndex(item => item.id === product.id && item.size === selectedSize && item.variation === currentVariation);
    if (existingIndex >= 0) {
      const existing = cart[existingIndex];
      existing.quantity = Math.min(existing.quantity + quantity, maxStock || 1);
      cart.splice(existingIndex, 1);
      cart.unshift(existing);
    } else {
      cart.unshift({
        id: product.id,
        name: product.name,
        price: `₱${(product.price || 0).toLocaleString()}`,
        image: galleryImages[activeImage]?.url || galleryImages[0]?.url || galleryImages[0],
        quantity: Math.min(quantity, maxStock || 1),
        size: selectedSize,
        variation: currentVariation,
        artisan: product.artisan,
        stock: maxStock || 0,
        allowGcash: product.allowGcash,
        allowMaya: product.allowMaya,
        gcashNumber: product.gcashNumber,
        gcashQrCode: product.gcashQrCode,
        mayaNumber: product.mayaNumber,
        mayaQrCode: product.mayaQrCode,
        seller: seller || product.seller
      });
    }
    setCustomerScopedJson("cart", cart);

    api.post(`/products/${product.id}/funnel-event`, {
      eventType: "add_to_cart"
    }).catch((error) => {
      console.warn("Failed to track add-to-cart funnel event", error?.response?.data || error?.message || error);
    });

    setAddedToCart(true);
    setTimeout(() => setAddedToCart(false), 2000);
  };

  const handleBuyNow = () => {
    if (userRole === "guest") {
      setAuthModalConfig({
        message: "Log in to proceed directly to secure checkout for this piece.",
        redirectPath: window.location.href
      });
      setIsAuthModalOpen(true);
      return;
    }
    const selectedSizeInfo = typeof availableSizes[0] === 'object'
      ? availableSizes.find(s => (s.size || s.name) === selectedSize)
      : null;
    const maxStock = selectedSizeInfo ? (selectedSizeInfo.stock || 0) : (product.stock || 0);

    if (maxStock <= 0) {
      alert("This size is currently out of stock.");
      return;
    }

    if (!selectedSize) {
      setSelectionError(true);
      alert("Please select a size first.");
      return;
    }
    const currentVariation = galleryImages[activeImage]?.variation || "Original";
    setCustomerScopedJson("checkout_mode", "buy_now");
    setCustomerScopedJson("checkout_item", {
      id: product.id,
      productId: product.id,
      name: product.name,
      price: `₱${(product.price || 0).toLocaleString()}`,
      image: galleryImages[activeImage]?.url || galleryImages[0]?.url || galleryImages[0],
      quantity: Math.min(quantity, maxStock || 1),
      size: selectedSize,
      variation: currentVariation,
      artisan: product.artisan,
      stock: maxStock || 0,
      allowGcash: product.allowGcash,
      allowMaya: product.allowMaya,
      gcashNumber: product.gcashNumber,
      gcashQrCode: product.gcashQrCode,
      mayaNumber: product.mayaNumber,
      mayaQrCode: product.mayaQrCode,
      seller: seller || product.seller
    });
    router.push("/checkout?mode=buy_now");
  };

  const Layout = userRole === 'admin' ? AdminLayout : (userRole === 'seller' || userRole === 'seller-preview' ? SellerLayout : CustomerLayout);

  if (loading) return (
    <Layout>
      <div className="h-[70vh] flex flex-col items-center justify-center space-y-6">
        <Loader2 className="w-10 h-10 animate-spin text-black opacity-50" />
      </div>
    </Layout>
  );

  if (!id) return (
    <Layout>
      <div className="h-[70vh] flex items-center justify-center px-4 text-center text-gray-500">
        Select a product first to view its details.
      </div>
    </Layout>
  );

  const sizeOptions = normalizeProductSizes(product?.sizes);
  const availableSizes = sizeOptions.length > 0 ? sizeOptions : ["S", "M", "L", "XL", "2XL"];
  const effectiveSize = selectedSize;

  const breadcrumbs = userRole !== 'admin' && userRole !== 'seller-preview' ? (
    <nav className="flex items-center gap-3 text-sm text-gray-400 font-medium">
      <Link href="/" className="hover:text-black transition-colors">Home</Link>
      <ChevronRight className="w-4 h-4 text-gray-300" />
      <Link href={`/?category=${encodeURIComponent(productCategory)}`} className="hover:text-black transition-colors">{productCategory}</Link>
      {product?.artisan_region && (
        <>
          <ChevronRight className="w-4 h-4 text-gray-300" />
          <Link href={`/?search=${encodeURIComponent(product.artisan_region)}`} className="hover:text-black transition-colors">{product.artisan_region}</Link>
        </>
      )}
      <ChevronRight className="w-4 h-4 text-gray-300" />
      <span className="text-black line-clamp-1 max-w-[200px]">{product.name}</span>
    </nav>
  ) : null;

  return (
    <Layout breadcrumbs={breadcrumbs}>
      <div className="min-h-screen bg-[#FAFAFA] pb-24 font-sans text-black">
        <div className="max-w-[1440px] mx-auto px-4 lg:px-12 pt-8">


          {userRole === 'admin' && (
            <div className="flex items-center justify-between gap-4 p-4 mb-8 bg-white border border-gray-200 rounded-2xl shadow-sm">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-black flex items-center justify-center text-white">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <div>
                  <div className="text-[10px] font-bold text-gray-500 uppercase tracking-widest leading-none mb-1">System Administration</div>
                  <div className="text-sm font-bold text-black">Product Management View</div>
                </div>
              </div>
              <button
                onClick={() => router.push('/admin/products')}
                className="flex items-center gap-2 px-5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-[11px] font-bold text-black uppercase tracking-widest hover:bg-black hover:text-white transition-colors"
              >
                ← Back to Products
              </button>
            </div>
          )}

          {userRole === 'seller-preview' && (
            <div className="flex items-center justify-between gap-4 p-4 mb-8 bg-black border border-gray-800 rounded-2xl shadow-xl">
              <div className="flex items-center gap-3">
                <div className="w-2 h-2 rounded-full bg-amber-400 animate-pulse" />
                <span className="text-[11px] font-bold tracking-widest uppercase text-amber-400">
                  Seller Preview Mode
                </span>
                <span className="text-[11px] text-gray-400 italic">
                  — Customer interactions disabled
                </span>
              </div>
              <button
                onClick={() => router.push('/seller/inventory')}
                className="flex items-center gap-2 px-4 py-2 border border-gray-700 bg-white/5 rounded-lg text-[11px] font-bold text-amber-400 uppercase tracking-widest hover:bg-white/10 transition-colors"
              >
                ← Back to Inventory
              </button>
            </div>
          )}

          {/* Main Product Layout */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 mb-20">
            
            {/* Left: Image Gallery */}
            <div className="flex flex-col gap-6">
              <div 
                className="relative aspect-4/5 bg-gray-50 rounded-4xl overflow-hidden cursor-zoom-in border border-gray-100" 
                onClick={() => setIsZoomOpen(true)}
              >
                <Image
                  key={activeImage}
                  src={galleryImages[activeImage]?.url || galleryImages[activeImage]}
                  alt={product.name}
                  fill
                  className="object-cover hover:scale-[1.02] transition-transform duration-500"
                  unoptimized
                />
              </div>
              
              <div className="flex gap-4 overflow-x-auto pb-2 scrollbar-hide snap-x">
                {galleryImages.map((img, i) => (
                  <button
                    key={i}
                    onClick={() => handleVariationSelect(i)}
                    className={`relative w-24 h-28 rounded-2xl overflow-hidden shrink-0 transition-all snap-start bg-gray-50 border-2 ${
                      activeImage === i 
                        ? 'border-black opacity-100 shadow-md scale-100' 
                        : 'border-transparent opacity-60 hover:opacity-100 scale-95 hover:scale-100'
                    }`}
                  >
                    <Image src={img.url || img} alt="thumb" fill className="object-cover" unoptimized />
                  </button>
                ))}
              </div>
            </div>

            {/* Right: Product Details */}
            <div className="flex flex-col pt-2 md:pt-6">
              <span className="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wider">{productCategory}</span>
              <h1 className="text-3xl md:text-5xl font-extrabold text-black mb-6 leading-[1.1] tracking-tight">{product.name}</h1>

              <div className="flex items-end gap-6 mb-8">
                <span className="text-3xl font-extrabold text-black">₱{(product.price || 0).toLocaleString()}</span>
              </div>



              {/* Size Selector */}
              <div className="mb-10">
                <div className="flex items-center justify-between mb-5">
                  <span className="text-base font-bold text-black uppercase tracking-wider">Select Size</span>
                  {!isRestricted && (
                    <button onClick={() => setShowSizeGuide(true)} className="text-sm font-bold text-gray-400 hover:text-black transition-colors underline underline-offset-4">
                      Size Guide
                    </button>
                  )}
                </div>
                
                {!isRestricted ? (
                  <div className="flex flex-wrap gap-4">
                    {availableSizes.map((s) => {
                      const isObj = typeof s === 'object';
                      const sName = isObj ? (s.size || s.name) : s;
                      const sStock = isObj ? (s.stock ?? 1) : (product.stock ?? 1);
                      const isOutOfStock = sStock <= 0;
                      const isActive = effectiveSize === sName;

                      return (
                        <button
                          key={sName}
                          onClick={() => !isOutOfStock && setSelectedSize(sName)}
                          className={`relative w-16 h-16 rounded-full flex items-center justify-center text-sm font-bold transition-all ${
                            isActive 
                              ? 'bg-black text-white shadow-lg shadow-black/20 scale-105' 
                              : isOutOfStock 
                                ? 'bg-gray-50 text-gray-300 cursor-not-allowed border border-gray-100' 
                                : 'bg-white text-gray-600 border border-gray-200 hover:border-black hover:text-black'
                          }`}
                          disabled={isOutOfStock}
                        >
                          {sName}
                          {isOutOfStock && (
                            <span className="absolute -top-1 -right-2 bg-red-500 text-white text-[8px] px-1.5 py-0.5 rounded-full font-black uppercase tracking-widest shadow-sm">
                              Out
                            </span>
                          )}
                        </button>
                      );
                    })}
                  </div>
                ) : (
                  <div className="text-sm text-gray-400 italic">Size selection restricted in preview mode</div>
                )}
              </div>

              {/* Quantity */}
              {!isRestricted && (
                <>
                  <div className="flex items-center gap-6 mb-10">
                  <div className="flex items-center border-2 border-gray-100 rounded-full bg-white h-[60px] w-40 overflow-hidden">
                    <button 
                      className="w-12 h-full flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 transition-colors"
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                    >
                      <Minus size={18} />
                    </button>
                    <input
                      type="number"
                      value={quantity}
                      onChange={(e) => {
                        const val = parseInt(e.target.value);
                        const selectedSizeInfo = typeof availableSizes[0] === 'object'
                          ? availableSizes.find(s => (s.size || s.name) === selectedSize)
                          : null;
                        const maxStock = selectedSizeInfo ? (selectedSizeInfo.stock || 0) : (product.stock || 999);
                        if (!isNaN(val)) setQuantity(Math.min(Math.max(1, val), maxStock));
                        else setQuantity("");
                      }}
                      onBlur={() => { if (quantity === "" || quantity < 1) setQuantity(1); }}
                      className="flex-1 text-center font-bold text-lg text-black bg-transparent outline-none h-full w-full"
                      style={{ MozAppearance: 'textfield' }}
                    />
                    <button 
                      className="w-12 h-full flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 transition-colors"
                      onClick={() => {
                        const selectedSizeInfo = typeof availableSizes[0] === 'object'
                          ? availableSizes.find(s => (s.size || s.name) === selectedSize)
                          : null;
                        const maxStock = selectedSizeInfo ? (selectedSizeInfo.stock || 0) : (product.stock || 999);
                        setQuantity(Math.min(quantity + 1, maxStock));
                      }}
                    >
                      <Plus size={18} />
                    </button>
                  </div>
                  <span className="text-sm font-medium text-gray-400">
                    {(() => {
                      const selectedSizeInfo = typeof availableSizes[0] === 'object'
                        ? availableSizes.find(s => (s.size || s.name) === selectedSize)
                        : null;
                      return selectedSizeInfo ? `${selectedSizeInfo.stock} items left` : `${product.stock || 0} items left`;
                    })()}
                  </span>
                </div>
                
                {/* Shipping Info - Quick View */}
                <div className="flex items-center gap-3 mb-10 px-2">
                  <div className="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100">
                    <Truck className="w-4 h-4 text-gray-500" />
                  </div>
                  <p className="text-sm text-gray-500">
                    Estimated delivery: <span className="font-bold text-black">{product?.shippingDays || 5} business days</span>
                  </p>
                </div>
              </>
            )}

              {/* Actions */}
              {!isRestricted && (
                <div className="flex flex-col gap-3 mb-12">
                  <button 
                    onClick={handleAddToCart} 
                    className="w-full h-[64px] bg-white border-2 border-black text-black rounded-2xl font-bold text-sm uppercase tracking-widest hover:bg-gray-50 transition-colors flex items-center justify-center gap-3 active:scale-[0.98]"
                  >
                    <ShoppingCart size={18} /> 
                    {addedToCart ? "Added!" : "Add to Cart"}
                  </button>
                  
                  <button 
                    onClick={handleBuyNow} 
                    className="w-full h-[64px] bg-black text-white rounded-2xl font-bold text-sm uppercase tracking-widest hover:bg-gray-900 transition-all shadow-xl shadow-black/10 flex items-center justify-center gap-3 active:scale-[0.98] border-2 border-black"
                  >
                    Buy It Now
                  </button>
                </div>
              )}

              {/* Accordions */}
              <div className="border-t border-gray-200 divide-y divide-gray-200 mt-auto">
                <details className="group py-6" open>
                  <summary className="flex items-center justify-between font-bold text-black cursor-pointer list-none outline-none">
                    <span className="text-base uppercase tracking-wider">Description & Fit</span>
                    <span className="transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-black">
                      <ChevronDown size={20} />
                    </span>
                  </summary>
                  <div className="text-sm text-gray-600 mt-6 leading-loose whitespace-pre-wrap">
                    {product.description || product.name}
                    
                    <div className="mt-6 flex flex-col gap-3">
                      {product.fabric_type && (
                         <div className="flex items-center gap-2">
                            <span className="font-bold text-black w-24">Material:</span> 
                            <span>{product.fabric_type}</span>
                         </div>
                      )}
                      {product.collar_type && (
                         <div className="flex items-center gap-2">
                            <span className="font-bold text-black w-24">Collar:</span> 
                            <span>{product.collar_type}</span>
                         </div>
                      )}
                      {product.sku && (
                         <div className="flex items-center gap-2">
                            <span className="font-bold text-black w-24">SKU:</span> 
                            <span className="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{product.sku}</span>
                         </div>
                      )}
                    </div>
                  </div>
                </details>
                

                

              </div>

            </div>
          </div>

          {/* Optional: Minimal Artisan Section directly below */}
          <div className="border-t border-gray-200 pt-16 pb-12 flex flex-col md:flex-row items-center justify-between gap-8">
             <div className="flex items-center gap-6">
                <div className="w-20 h-20 rounded-full overflow-hidden bg-gray-100 shrink-0">
                  {product.seller?.profilePhoto ? (
                    <img src={product.seller.profilePhoto} alt="Artisan" className="w-full h-full object-cover" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center bg-black text-white text-3xl font-bold">
                      {(product.artisan || "L")[0].toUpperCase()}
                    </div>
                  )}
                </div>
                <div>
                  <div className="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Authentic Maker</div>
                  <div className="flex items-center gap-2 mb-2">
                    <h3 className="font-extrabold text-black text-2xl">{product.artisan || "Master Craft"}</h3>
                    {product.seller?.isVerified && <CheckCircle2 className="w-5 h-5 text-blue-500" />}
                  </div>
                  <div className="text-sm font-medium text-gray-500 flex items-center gap-4">
                    <span>{seller?.productCount || 0} Products</span>
                    <span className="w-1 h-1 rounded-full bg-gray-300" />
                    <span>{seller?.reviewCount || 0} Reviews</span>
                  </div>
                </div>
             </div>

             {!isRestricted && (
              <div className="flex gap-4 w-full md:w-auto">
                <Link href={`/messages?sellerId=${product.sellerId || 1}`} className="flex-1 md:flex-none flex items-center justify-center gap-2 px-8 py-4 border-2 border-gray-200 rounded-full text-black font-bold hover:border-black hover:bg-gray-50 transition-colors">
                  <MessageCircle size={18} /> Message
                </Link>
                <Link href={`/shop?id=${product.sellerId || 1}`} className="flex-1 md:flex-none flex items-center justify-center gap-2 px-8 py-4 bg-black text-white rounded-full font-bold hover:bg-gray-800 transition-colors shadow-lg shadow-black/10">
                  <Store size={18} /> Visit Shop
                </Link>
              </div>
            )}
          </div>

          <div className="mt-16 bg-white rounded-4xl p-8 lg:p-12 shadow-sm border border-gray-100" id="reviews">
            <h2 className="text-3xl font-extrabold text-black mb-10">Customer Reviews</h2>

            {reviewList.length > 0 ? (
              <div className="space-y-12">
                <div className="flex flex-col md:flex-row gap-10 p-8 rounded-3xl bg-gray-50 items-center border border-gray-100">
                  <div className="flex flex-col items-center justify-center md:w-1/3">
                    <div className="flex items-baseline gap-2 mb-2">
                      <span className="text-6xl font-black text-black">{productRating.toFixed(1)}</span>
                      <span className="text-2xl font-bold text-gray-400">/ 5</span>
                    </div>
                    <div className="flex text-black mb-3">
                      {[...Array(5)].map((_, index) => (
                        <Star key={index} className={`w-6 h-6 ${index < Math.floor(productRating) ? "fill-black" : "opacity-20"}`} />
                      ))}
                    </div>
                    <span className="text-sm font-bold text-gray-500 uppercase tracking-widest">{productReviewCount} Ratings</span>
                  </div>

                  <div className="w-full h-px md:w-px md:h-32 bg-gray-200" />

                  <div className="flex-1 w-full flex flex-col justify-center gap-4 px-4">
                    {[5, 4, 3, 2, 1].map(star => (
                      <div key={star} className="flex items-center gap-4">
                        <div className="flex items-center gap-2 w-12 text-sm font-bold text-black">
                          {star} <Star className="w-4 h-4 fill-black" />
                        </div>
                        <div className="flex-1 h-2.5 bg-gray-200 rounded-full overflow-hidden">
                          <div 
                            className="h-full bg-black rounded-full" 
                            style={{ width: star === 5 ? '75%' : star === 4 ? '15%' : star === 3 ? '5%' : star === 2 ? '3%' : '2%' }}
                          />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="flex flex-wrap gap-3 border-b border-gray-200 pb-8">
                  {reviewFilters.map((filter) => (
                    <button
                      key={filter.key}
                      onClick={() => setActiveReviewFilter(filter.key)}
                      className={`px-5 py-2.5 text-xs font-bold uppercase tracking-widest rounded-full transition-all ${activeReviewFilter === filter.key
                        ? "bg-black text-white shadow-md shadow-black/10 scale-105"
                        : "bg-white text-gray-600 border border-gray-200 hover:border-black hover:text-black"
                        }`}
                    >
                      {filter.label} <span className="opacity-70 ml-1">({filter.count})</span>
                    </button>
                  ))}
                </div>

                <div className="space-y-10">
                  {filteredReviews.length === 0 ? (
                    <div className="text-center py-16 text-sm font-medium text-gray-400 italic">No reviews found for this filter.</div>
                  ) : (
                    filteredReviews.map((review) => (
                      <div key={review.id} className="pb-10 border-b border-gray-100 last:border-0 last:pb-0">
                        <div className="flex gap-6">
                          <div className="w-14 h-14 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center shrink-0 shadow-sm">
                            {review.customer?.profilePhoto ? (
                              <img src={resolveBackendImageUrl(review.customer.profilePhoto)} alt="User" className="w-full h-full object-cover" />
                            ) : (
                              <span className="font-extrabold text-gray-500 text-xl">{review.customer?.name?.[0] || "A"}</span>
                            )}
                          </div>

                          <div className="flex-1 min-w-0">
                            <div className="flex items-center justify-between mb-2">
                              <div className="font-bold text-base text-black">
                                {review.customer?.name || "Verified Customer"}
                              </div>
                              <div className="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                {new Date(review.createdAt).toLocaleDateString(undefined, {
                                  year: "numeric", month: "short", day: "numeric"
                                })}
                              </div>
                            </div>
                            
                            <div className="flex text-black mb-4">
                              {[...Array(5)].map((_, index) => (
                                <Star key={index} className={`w-4 h-4 ${index < Number(review.rating || 0) ? "fill-black" : "opacity-20"}`} />
                              ))}
                            </div>
                            
                            {review.variation && (
                              <div className="text-xs font-bold text-gray-500 mb-4 bg-gray-50 inline-block px-3 py-1.5 rounded-lg border border-gray-100">
                                Variation: <span className="text-black">{review.variation}</span>
                              </div>
                            )}

                            {review.comment && (
                              <p className="text-sm leading-loose text-gray-700 whitespace-pre-wrap mb-6">
                                {review.comment}
                              </p>
                            )}

                            {Array.isArray(review.images) && review.images.length > 0 && (
                              <div className="mt-4">
                                <div className="flex flex-wrap gap-4">
                                  {review.images.map((img, i) => {
                                    const isExpanded = expandedReviewId === review.id && expandedImageIndex === i;
                                    return (
                                      <button
                                        key={i}
                                        onClick={() => handleExpandReviewImage(review.id, i)}
                                        className={`relative w-24 h-24 rounded-2xl overflow-hidden border-2 transition-all ${isExpanded ? "border-black scale-105 shadow-md" : "border-transparent hover:scale-105"
                                          }`}
                                      >
                                        <img src={img} alt="Review Media" className="w-full h-full object-cover bg-gray-50" />
                                      </button>
                                    );
                                  })}
                                </div>

                                {expandedReviewId === review.id && (
                                  <div className="mt-8 relative max-w-xl bg-black rounded-3xl overflow-hidden group shadow-2xl">
                                    <button
                                      onClick={(e) => { e.stopPropagation(); setExpandedReviewId(null); }}
                                      className="absolute top-4 right-4 w-10 h-10 flex items-center justify-center bg-black/60 hover:bg-black rounded-full text-white shadow-xl transition-colors z-10"
                                      title="Close image"
                                    >
                                      <CloseIcon size={20} />
                                    </button>
                                    <img src={review.images[expandedImageIndex]} alt="Expanded Media" className="w-full h-auto object-contain max-h-[600px]" />
                                    {review.images.length > 1 && (
                                      <>
                                        <button
                                          onClick={(e) => { e.stopPropagation(); setExpandedImageIndex((prev) => (prev - 1 + review.images.length) % review.images.length); }}
                                          className="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/90 hover:bg-white rounded-full text-black shadow-xl opacity-0 group-hover:opacity-100 transition-all scale-95 hover:scale-100"
                                        >
                                          <ChevronLeft size={24} />
                                        </button>
                                        <button
                                          onClick={(e) => { e.stopPropagation(); setExpandedImageIndex((prev) => (prev + 1) % review.images.length); }}
                                          className="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/90 hover:bg-white rounded-full text-black shadow-xl opacity-0 group-hover:opacity-100 transition-all scale-95 hover:scale-100"
                                        >
                                          <ChevronRight size={24} />
                                        </button>
                                      </>
                                    )}
                                  </div>
                                )}
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            ) : (
              <div className="text-center py-24 px-4 bg-gray-50 rounded-3xl border border-dashed border-gray-300">
                <MessageCircle className="w-16 h-16 text-gray-300 mx-auto mb-6" />
                <h3 className="text-xl font-extrabold text-black mb-2">No reviews yet</h3>
                <p className="text-sm font-medium text-gray-500">Be the first to share your thoughts on this product.</p>
              </div>
            )}
          </div>
        </div>

        {/* Recommendations Section */}
        <div className="mt-32">
          <div className="flex items-center justify-between mb-10">
            <h2 className="text-3xl font-extrabold text-black tracking-tight">You Might Also Like</h2>
            <Link href="/" className="text-sm font-bold text-gray-500 uppercase tracking-widest hover:text-black transition-colors flex items-center gap-1">
              View All <ChevronRight className="w-4 h-4" />
            </Link>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            {/* Mock Recommendation Items */}
            {[1, 2, 3, 4].map((item) => (
              <div key={item} className="group cursor-pointer">
                <div className="relative aspect-3/4 rounded-3xl overflow-hidden bg-gray-50 border border-gray-100 mb-4">
                  <img src={`/images/placeholder.png`} alt="Recommendation" className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                  <div className="absolute top-4 left-4 bg-white px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest text-black shadow-sm">
                    Trending
                  </div>
                </div>
                <div className="space-y-1.5 px-2">
                  <div className="text-xs font-bold text-gray-400 uppercase tracking-widest">Heritage</div>
                  <h3 className="font-bold text-black text-base line-clamp-1 group-hover:text-gray-600 transition-colors">Classic Embroidered Piece</h3>
                  <div className="font-extrabold text-black text-lg">₱4,500</div>
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>

      <AnimatePresence>
        {showSizeGuide && (
          <div className="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" onClick={() => setShowSizeGuide(false)}>
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="flex items-center justify-between p-6 border-b border-gray-100">
                <h2 className="text-xl font-extrabold text-black uppercase tracking-wider">Official Size Guide</h2>
                <button className="p-2 text-gray-400 hover:text-black hover:bg-gray-100 rounded-full transition-colors" onClick={() => setShowSizeGuide(false)}>
                  <CloseIcon size={20} />
                </button>
              </div>
              <div className="flex bg-gray-50 p-2 gap-2 border-b border-gray-100">
                <button
                  className={`flex-1 py-2.5 px-4 rounded-xl text-xs uppercase tracking-widest font-bold transition-all ${sizeGuideTab === 'men' ? 'bg-white shadow-sm text-black border border-gray-200' : 'text-gray-500 hover:bg-gray-200 border border-transparent'}`}
                  onClick={() => setSizeGuideTab('men')}
                >
                  Men
                </button>
                <button
                  className={`flex-1 py-2.5 px-4 rounded-xl text-xs uppercase tracking-widest font-bold transition-all ${sizeGuideTab === 'women' ? 'bg-white shadow-sm text-black border border-gray-200' : 'text-gray-500 hover:bg-gray-200 border border-transparent'}`}
                  onClick={() => setSizeGuideTab('women')}
                >
                  Women
                </button>
                <button
                  className={`flex-1 py-2.5 px-4 rounded-xl text-xs uppercase tracking-widest font-bold transition-all ${sizeGuideTab === 'kids' ? 'bg-white shadow-sm text-black border border-gray-200' : 'text-gray-500 hover:bg-gray-200 border border-transparent'}`}
                  onClick={() => setSizeGuideTab('kids')}
                >
                  Kids
                </button>
              </div>
              <div className="p-6 max-h-[60vh] overflow-y-auto">
                {sizeGuideTab === 'men' && (
                  <img src="/images/size-guide.png" alt="Men's Size Guide" className="w-full h-auto rounded-xl shadow-sm border border-gray-100" />
                )}
                {sizeGuideTab === 'women' && (
                  <img src="/images/size-guide-women.png" alt="Women's Size Guide" className="w-full h-auto rounded-xl shadow-sm border border-gray-100" />
                )}
                {sizeGuideTab === 'kids' && (
                  <img src="/images/size-guide-kids.png" alt="Kids' Size Guide" className="w-full h-auto rounded-xl shadow-sm border border-gray-100" />
                )}
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      <AnimatePresence>
        {showFabricGuide && (
          <div className="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowFabricGuide(false)}
              className="absolute inset-0"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden z-10"
            >
              <div className="p-8">
                <div className="flex items-center gap-4 mb-8">
                  <div className="w-12 h-12 rounded-2xl bg-black flex items-center justify-center text-white shrink-0 shadow-lg shadow-black/20">
                    <BookOpen className="w-6 h-6" />
                  </div>
                  <div>
                    <h3 className="text-xl font-extrabold text-black uppercase tracking-wider">Fabric Guide</h3>
                    <p className="text-[10px] font-bold uppercase tracking-widest text-gray-400">Material Education</p>
                  </div>
                </div>

                <div className="space-y-6">
                  <div>
                    <div className="text-[10px] font-black uppercase tracking-[0.2em] text-black mb-3">{activeFabric}</div>
                    <p className="text-sm text-gray-600 leading-loose">
                      {fabricGuide[activeFabric]?.description}
                    </p>
                  </div>

                  <div className="grid grid-cols-2 gap-6 pt-6 border-t border-gray-100">
                    <div>
                      <h4 className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Common Origin</h4>
                      <p className="text-xs font-bold text-black">{fabricGuide[activeFabric]?.origin}</p>
                    </div>
                    <div>
                      <h4 className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Care Level</h4>
                      <p className="text-xs font-bold text-black">{fabricGuide[activeFabric]?.care}</p>
                    </div>
                  </div>
                </div>

                <button
                  onClick={() => setShowFabricGuide(false)}
                  className="w-full mt-10 py-4 bg-black text-white text-[11px] font-bold uppercase tracking-[0.2em] rounded-2xl hover:bg-gray-900 transition-colors shadow-lg shadow-black/10 active:scale-[0.98]"
                >
                  Got it
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {isAuthModalOpen && (
        <AuthGateModal
          isOpen={isAuthModalOpen}
          onClose={() => setIsAuthModalOpen(false)}
          message={authModalConfig.message}
          redirectPath={authModalConfig.redirectPath}
        />
      )}

      {isZoomOpen && (
        <div className="fixed inset-0 z-2000 bg-black/95 flex flex-col backdrop-blur-md">
          {/* Close button */}
          <div className="absolute top-6 right-6 z-2001">
            <button
              onClick={() => setIsZoomOpen(false)}
              className="w-12 h-12 rounded-full bg-white/10 border border-white/20 text-white flex items-center justify-center hover:bg-white/20 transition-all hover:scale-105 active:scale-95"
            >
              <CloseIcon size={24} />
            </button>
          </div>

          {/* Scrollable image container */}
          <div className="flex-1 overflow-auto flex items-start justify-start cursor-zoom-out" onClick={() => setIsZoomOpen(false)}>
            <img
              src={galleryImages[activeImage]?.url || galleryImages[activeImage]}
              alt={product.name}
              className="block w-[150%] md:w-full h-auto object-contain mx-auto my-auto min-h-screen p-4"
              draggable={false}
              onClick={(e) => e.stopPropagation()}
            />
          </div>

          {/* Thumbnail strip at bottom */}
          {galleryImages.length > 1 && (
            <div className="flex justify-center gap-3 p-4 bg-black/60 backdrop-blur-lg border-t border-white/10">
              {galleryImages.map((img, i) => (
                <button
                  key={i}
                  onClick={() => setActiveImage(i)}
                  className={`w-14 h-16 md:w-16 md:h-20 rounded-xl overflow-hidden border-2 transition-all shrink-0 ${
                    activeImage === i ? 'border-white opacity-100 scale-105' : 'border-transparent opacity-50 hover:opacity-100'
                  }`}
                >
                  <img src={img.url || img} alt="" className="w-full h-full object-cover bg-gray-900" />
                </button>
              ))}
            </div>
          )}
        </div>
      )}

      {isReportModalOpen && (
        <ReportModal
          isOpen={isReportModalOpen}
          onClose={() => setIsReportModalOpen(false)}
          reportedId={product.id}
          type="CustomerReportingProduct"
          reportedName={product.name}
        />
      )}
    </Layout>
  );
}
