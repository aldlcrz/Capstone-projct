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
  ShieldAlert
} from "lucide-react";
import ReportModal from "./ReportModal";
import { api, getStoredUserForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { normalizeProductImages, normalizeProductSizes, resolveBackendImageUrl } from "@/lib/productImages";
import { setCustomerScopedJson, getCustomerScopedJson } from "@/lib/customerStorage";

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
  const [maintenanceMode, setMaintenanceMode] = useState(false);
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
      // Use URL path to determine context — /products is always customer-facing
      // Only use seller layout when explicitly on a /seller/* path OR in ?preview=1 mode
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
        // Seller previewing their own product via ?preview=1 — read-only mode
        setUserRole("seller-preview");
      } else {
        // On /products or any other path, always use customer layout
        setUserRole(customerData?.role || "customer");
      }
    } catch (e) {
      setUserRole("customer");
    }
  }, [pathname, previewMode]);

  // seller-preview = can VIEW but cannot take any customer actions
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

  const ratingBreakdown = useMemo(
    () => [5, 4, 3, 2, 1].map((stars) => {
      const count = reviewList.filter((review) => Number(review.rating) === stars).length;
      return {
        stars,
        count,
        percentage: productReviewCount > 0 ? (count / productReviewCount) * 100 : 0,
      };
    }),
    [reviewList, productReviewCount]
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

  const activeReviewImage = null; // Replaced by inline expansion

  // Synchronization logic for variation persistence
  useEffect(() => {
    if (vParam && galleryImages.length > 0) {
      // Avoid fighting with manual user clicks
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

    // Update URL parameter (Permanent link)
    const params = new URLSearchParams(window.location.search);
    params.set("v", variation);
    router.replace(`${window.location.pathname}?${params.toString()}`, { scroll: false });

    // Release lock after a short delay to allow URL catch-up
    setTimeout(() => {
      isManualChange.current = false;
    }, 500);
  };

  const productCategory = Array.isArray(product?.categories) && product.categories.length > 0
    ? product.categories[0]
    : (product?.category || "Uncategorized");

  const handleAddToCart = () => {
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
        <Loader2 className="w-10 h-10 animate-spin text-[var(--rust)] opacity-50" />
      </div>
    </Layout>
  );

  if (!id) return (
    <Layout>
      <div className="h-[70vh] flex items-center justify-center px-4 text-center text-[var(--muted)]">
        Select a product first to view its details.
      </div>
    </Layout>
  );

  const sizeOptions = normalizeProductSizes(product?.sizes);
  const availableSizes = sizeOptions.length > 0 ? sizeOptions : ["S", "M", "L", "XL", "2XL"];
  const effectiveSize = selectedSize;

  return (
    <Layout>
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap');

        .pd-page { background: #F0EBE3; min-height: 100vh; padding: 2rem 0 120px; }
        .pd-wrap { max-width: 1100px; margin: 0 auto; padding: 0 1.25rem; }

        .pd-hero {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 0;
          background: #fff;
          border-radius: 1.25rem;
          overflow: hidden;
          box-shadow: 0 8px 48px rgba(60,35,10,0.10);
          margin-bottom: 1.5rem;
        }
        @media(max-width: 768px){
          .pd-hero { grid-template-columns: 1fr; }
        }

        .pd-gallery {
          background: #000;
          position: relative;
          overflow: hidden;
          min-height: 650px;
        }
        .pd-gallery-badge {
          position: absolute;
          top: 1.5rem; left: 1.5rem;
          display: flex; align-items: center; gap: 0.4rem;
          background: rgba(26,18,8,0.7);
          backdrop-filter: blur(12px);
          border: 1px solid rgba(255,255,255,0.15);
          border-radius: 8px;
          padding: 0.4rem 0.8rem;
          font-size: 11px; font-weight: 700;
          color: #e8d5b0; letter-spacing: 0.1em; text-transform: uppercase;
          z-index: 10;
        }
        .pd-main-img {
          position: absolute;
          inset: 0;
          width: 100%;
          height: 100%;
          overflow: hidden;
          z-index: 1;
        }
        .pd-thumbs {
          position: absolute;
          bottom: 1.5rem;
          left: 0; right: 0;
          display: flex; gap: 0.75rem;
          justify-content: center;
          z-index: 10;
          padding: 0 1rem;
        }
        .pd-thumb {
          position: relative; width: 54px; height: 54px;
          border-radius: 0.65rem; overflow: hidden; cursor: pointer;
          border: 2.5px solid rgba(255,255,255,0.2);
          transition: all 0.25s;
          background: rgba(0,0,0,0.5);
        }
        .pd-thumb.active { border-color: #D4A96A; transform: translateY(-3px); }

        .pd-info {
          padding: 2.5rem 2rem 2rem;
          display: flex; flex-direction: column;
          background: #fff;
        }
        .pd-eyebrow {
          font-family: 'Inter', sans-serif;
          font-size: 10.5px; font-weight: 700;
          letter-spacing: 0.14em; text-transform: uppercase;
          color: #9c6e30;
          display: flex; align-items: center; gap: 0.5rem;
          margin-bottom: 0.7rem;
        }
        .pd-eyebrow::before {
          content: ''; display: inline-block;
          width: 1.4rem; height: 1.5px; background: #C0853A;
        }
        .pd-title {
          font-family: 'Playfair Display', serif;
          font-size: 1.55rem; font-weight: 600; line-height: 1.3;
          color: #1C1209;
          margin-bottom: 1rem;
        }

        .pd-rating-row {
          display: flex; align-items: center; padding: 0.65rem 0; border-top: 1px solid #F0EBE3; border-bottom: 1px solid #F0EBE3; margin-bottom: 1.2rem;
        }
        .pd-rating-seg { display: flex; align-items: center; gap: 0.4rem; padding: 0 1rem; }
        .pd-rating-seg:first-child { padding-left: 0; }
        .pd-rating-sep { width: 1px; height: 18px; background: #E5DDD5; }
        .pd-score { font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700; color: #C0853A; }

        .pd-price-block {
          background: linear-gradient(135deg, #FDF5E8 0%, #FEF9F0 100%);
          border: 1px solid #EDD9A3;
          border-radius: 0.75rem;
          padding: 0.9rem 1.1rem;
          margin-bottom: 1.4rem;
          display: flex; align-items: baseline; gap: 0.5rem;
        }
        .pd-price { font-family: 'Inter', sans-serif; font-size: 1.75rem; color: #7B3A10; font-weight: 700; line-height:1; }

        .pd-row { display: flex; align-items: flex-start; gap: 0; margin-bottom: 1rem; }
        .pd-row-label { width: 6.5rem; flex-shrink: 0; font-size: 12.5px; color: #9c8876; padding-top: 2px; font-weight: 500; }
        
        .pd-chip-wrap { display: flex; flex-wrap: wrap; gap: 0.45rem; }
        .pd-chip {
          display: flex; align-items: center; gap: 0.4rem;
          padding: 0.35rem 0.65rem;
          border: 1.5px solid #E5DDD5;
          border-radius: 6px;
          cursor: pointer; transition: all 0.18s;
          font-size: 12.5px; color: #3D2B1F; background: #fff;
        }
        .pd-chip.active { border-color: #9c6e30; background: #FDF5E8; color: #7B3A10; }
        .pd-chip-img { position: relative; width: 28px; height: 28px; border-radius: 4px; overflow: hidden; }

        .pd-size-chip {
          padding: 0.35rem 0.9rem;
          border: 1.5px solid #E5DDD5;
          border-radius: 6px;
          cursor: pointer; transition: all 0.18s;
          font-size: 13px; color: #3D2B1F; background: #fff;
          font-weight: 500;
        }
        .pd-size-chip.active { border-color: #9c6e30; background: #FDF5E8; color: #7B3A10; }

        .pd-size-guide-btn {
          display: flex;
          align-items: center;
          gap: 0.4rem;
          font-size: 11px;
          font-weight: 700;
          color: #9c6e30;
          text-transform: uppercase;
          letter-spacing: 0.05em;
          background: none;
          border: none;
          cursor: pointer;
          padding: 0;
          margin-left: auto;
          transition: opacity 0.2s;
        }
        .pd-size-guide-btn:hover { opacity: 0.7; }

        .pd-modal-overlay {
          position: fixed;
          inset: 0;
          background: rgba(26,18,8,0.6);
          backdrop-filter: blur(8px);
          z-index: 999;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 1.5rem;
        }
        .pd-modal-content {
          background: #fff;
          width: 100%;
          max-width: 800px;
          border-radius: 1.5rem;
          overflow: hidden;
          position: relative;
          box-shadow: 0 24px 64px rgba(0,0,0,0.2);
          display: flex;
          flex-direction: column;
        }
        .pd-modal-header {
          padding: 1.25rem 1.75rem;
          border-bottom: 1px solid #F0EBE3;
          display: flex;
          align-items: center;
          justify-content: space-between;
          background: #FDFBF9;
        }
        .pd-modal-title {
          font-family: 'Playfair Display', serif;
          font-size: 1.25rem;
          font-weight: 700;
          color: #1C1209;
        }
        .pd-modal-close {
          width: 32px;
          height: 32px;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 50%;
          background: #F7F3EE;
          border: none;
          cursor: pointer;
          color: #3D2B1F;
          transition: all 0.2s;
        }
        .pd-modal-close:hover { background: #E5DDD5; transform: rotate(90deg); }
        .pd-modal-tabs {
          display: flex;
          border-bottom: 1px solid #F0EBE3;
          background: #FDFBF9;
        }
        .pd-modal-tab {
          flex: 1;
          padding: 0.7rem 1rem;
          font-size: 12px;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.08em;
          color: #9c8876;
          background: none;
          border: none;
          border-bottom: 2.5px solid transparent;
          cursor: pointer;
          transition: all 0.18s;
        }
        .pd-modal-tab.active {
          color: #7B3A10;
          border-bottom-color: #C0853A;
          background: #fff;
        }
        .pd-modal-tab:hover:not(.active) { background: #F7F3EE; }
        .pd-modal-body {
          padding: 1.5rem;
          overflow-y: auto;
          max-height: 75vh;
          display: flex;
          justify-content: center;
          background: #fff;
        }
        .pd-guide-img {
          max-width: 100%;
          height: auto;
          border-radius: 0.75rem;
        }

        .pd-qty { display: flex; align-items: center; border: 1.5px solid #E5DDD5; border-radius: 8px; overflow: hidden; }
        .pd-qty-btn { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #F7F3EE; border: none; cursor: pointer; }
        .pd-qty-val { 
          width: 52px; height: 36px; text-align: center;
          font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 700; 
          background: #fff; border-left: 1.5px solid #E5DDD5; border-right: 1.5px solid #E5DDD5;
          outline: none; -moz-appearance: textfield;
        }
        .pd-qty-val::-webkit-outer-spin-button, .pd-qty-val::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        .pd-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; }
        .pd-btn-cart { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.85rem; border: 2px solid #C0420A; border-radius: 10px; font-size: 14px; font-weight: 600; color: #C0420A; background: #fff; cursor: pointer; }
        .pd-btn-buy { flex: 1; display: flex; align-items: center; justify-content: center; padding: 0.85rem; border-radius: 10px; font-size: 14px; font-weight: 600; color: #fff; background: #C0420A; cursor: pointer; border: none; }

        .pd-artisan {
          background: #fff;
          border-radius: 1.25rem;
          box-shadow: 0 4px 24px rgba(60,35,10,0.07);
          padding: 1.25rem 1.5rem;
          margin-bottom: 1.5rem;
          border: 1px solid #F0EBE3;
        }
        .pd-artisan-top {
          display: flex;
          align-items: center;
          gap: 1rem;
          margin-bottom: 1rem;
        }
        .pd-artisan-avatar {
          width: 52px;
          height: 52px;
          min-width: 52px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.35rem;
          font-weight: 700;
          background: linear-gradient(135deg, #3D2B1F, #7B3A10);
          color: #E8C98C;
          flex-shrink: 0;
        }
        .pd-artisan-info { flex: 1; min-width: 0; }
        .pd-artisan-name {
          font-family: 'Playfair Display', serif;
          font-size: 1rem;
          font-weight: 700;
          color: #1C1209;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .pd-artisan-online {
          display: inline-flex;
          align-items: center;
          gap: 0.35rem;
          font-size: 11px;
          font-weight: 600;
          color: #16a34a;
          margin-top: 2px;
        }
        .pd-artisan-online::before {
          content: '';
          display: inline-block;
          width: 7px;
          height: 7px;
          border-radius: 50%;
          background: #16a34a;
          animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.4; }
        }
        .pd-artisan-stats {
          display: flex;
          gap: 0;
          border-top: 1px solid #F0EBE3;
          border-bottom: 1px solid #F0EBE3;
          margin-bottom: 1rem;
          padding: 0.75rem 0;
        }
        .pd-artisan-stat {
          flex: 1;
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: 2px;
          border-right: 1px solid #F0EBE3;
        }
        .pd-artisan-stat:last-child { border-right: none; }
        .pd-artisan-stat-label {
          font-size: 10px;
          font-weight: 700;
          color: #9c8876;
          text-transform: uppercase;
          letter-spacing: 0.06em;
        }
        .pd-artisan-stat-value {
          font-family: 'Inter', sans-serif;
          font-size: 1rem;
          font-weight: 700;
          color: #1C1209;
          white-space: nowrap;
        }
        .pd-artisan-actions { display: flex; gap: 0.5rem; }
        .pd-artisan-btn-chat {
          flex: 1;
          display: flex; align-items: center; justify-content: center; gap: 0.4rem;
          padding: 0.55rem 1rem;
          font-size: 12px; font-weight: 700;
          border: 1.5px solid #C0420A;
          border-radius: 8px;
          color: #C0420A;
          background: #fff;
          cursor: pointer;
          transition: all 0.18s;
          text-decoration: none;
        }
        .pd-artisan-btn-chat:hover { background: #FFF5F3; }
        .pd-artisan-btn-shop {
          flex: 1;
          display: flex; align-items: center; justify-content: center; gap: 0.4rem;
          padding: 0.55rem 1rem;
          font-size: 12px; font-weight: 700;
          border: none;
          border-radius: 8px;
          color: #fff;
          background: #3D2B1F;
          cursor: pointer;
          transition: all 0.18s;
          text-decoration: none;
        }
        .pd-artisan-btn-shop:hover { background: #C0420A; }

        .pd-details-card { background: #fff; border-radius: 1.25rem; box-shadow: 0 4px 24px rgba(60,35,10,0.07); overflow: hidden; }
        .pd-section-head { 
          display: flex; align-items: center; gap: 0.6rem; padding: 1.1rem 1.75rem; 
          background: #FDFBF9; color: #7B3A10; border-bottom: 1px solid #F7F3EE;
          font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .pd-specs { padding: 1.25rem 1.75rem; }
        .pd-spec-row { display: flex; align-items: center; padding: 0.65rem 0; border-bottom: 1px solid #F7F3EE; }
        .pd-desc { padding: 0 1.75rem 1.75rem; font-size: 14px; color: #3D2B1F; line-height: 1.78; white-space: pre-wrap; }

        .pd-reviews { background: #fff; border-radius: 1.25rem; box-shadow: 0 4px 24px rgba(60,35,10,0.07); margin-top: 1.5rem; padding: 1.75rem; }
        .pd-dist-wrap { background: #FDF5E8; border: 1px solid #EDD9A3; border-radius: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 1.75rem; display: flex; gap: 2rem; align-items: center; }
        .pd-dist-big { font-size: 3rem; font-weight: 700; color: #7B3A10; }
        .pd-dist-bars { flex: 1; display: flex; flex-direction: column; gap: 0.4rem; }
        .pd-dist-track { flex: 1; height: 6px; background: #F0EBE3; border-radius: 999px; overflow: hidden; }
        .pd-dist-fill { height: 100%; background: #C0853A; }
      `}</style>

      <div className="pd-page">
        <div className="pd-wrap">

          {/* Customer Back Button */}
          {userRole !== 'admin' && userRole !== 'seller-preview' && (
            <div className="mb-4 pt-4 sm:pt-6 flex justify-between items-center">
              <button 
                onClick={() => router.back()} 
                className="flex items-center gap-1.5 text-xs font-bold text-stone-500 hover:text-[var(--rust)] transition-colors"
              >
                <ChevronLeft className="h-4 w-4" /> Back to previous
              </button>

              <button 
                onClick={() => setIsReportModalOpen(true)}
                className="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-red-500 hover:text-red-700 transition-colors bg-white px-3 py-1.5 rounded-full border border-red-100 shadow-sm"
              >
                <ShieldAlert className="w-3.5 h-3.5" /> Report Product
              </button>
            </div>
          )}

          {/* Admin Back Button Banner */}
          {userRole === 'admin' && (
            <div className="flex items-center justify-between gap-4 p-4 mb-5 bg-white border border-[var(--border)] rounded-2xl shadow-sm">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-[var(--bark)] flex items-center justify-center text-white">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <div>
                  <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest leading-none mb-1">System Administration</div>
                  <div className="text-sm font-bold text-[var(--charcoal)]">Product Management View</div>
                </div>
              </div>
              <button
                onClick={() => router.push('/admin/products')}
                className="flex items-center gap-2 px-5 py-2.5 bg-[var(--cream)] border border-[var(--border)] rounded-xl text-[11px] font-bold text-[var(--charcoal)] uppercase tracking-widest hover:bg-[var(--rust)] hover:text-white hover:border-[var(--rust)] transition-all shadow-sm"
              >
                ← Back to Products
              </button>
            </div>
          )}

          {/* Seller Preview Banner */}
          {userRole === 'seller-preview' && (
            <div style={{
              background: 'linear-gradient(135deg, #1C1209, #3D2B1F)',
              borderRadius: '0.85rem',
              padding: '0.9rem 1.25rem',
              marginBottom: '1.25rem',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: '1rem',
              boxShadow: '0 4px 20px rgba(0,0,0,0.15)',
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                <div style={{ width: 8, height: 8, borderRadius: '50%', background: '#E8C98C', animation: 'pulse 2s infinite' }} />
                <span style={{ fontSize: 11, fontWeight: 800, letterSpacing: '0.18em', textTransform: 'uppercase', color: '#E8C98C' }}>
                  Seller Preview Mode
                </span>
                <span style={{ fontSize: 11, color: 'rgba(232,201,140,0.6)', fontStyle: 'italic' }}>
                  — Customer interactions are disabled
                </span>
              </div>
              <button
                onClick={() => router.push('/seller/inventory')}
                style={{
                  display: 'flex', alignItems: 'center', gap: '0.4rem',
                  padding: '0.4rem 0.9rem', borderRadius: '6px',
                  background: 'rgba(255,255,255,0.1)', border: '1px solid rgba(255,255,255,0.2)',
                  color: '#E8C98C', fontSize: 11, fontWeight: 700,
                  letterSpacing: '0.1em', textTransform: 'uppercase', cursor: 'pointer',
                  transition: 'all 0.2s',
                }}
              >
                ← Back to Inventory
              </button>
            </div>
          )}

          {/* Hero Card */}
          <div className="pd-hero">
            <div className="pd-gallery">
              <div className="pd-gallery-badge"><Award size={12} /> Lumban Artisan Craft</div>
              <div className="pd-main-img">
                <img
                  key={activeImage}
                  src={galleryImages[activeImage]?.url || galleryImages[activeImage]}
                  alt={product.name}
                  className="w-full h-full object-cover object-top"
                />
              </div>
              <div className="pd-thumbs">
                {galleryImages.map((img, i) => (
                  <div
                    key={i}
                    onClick={() => handleVariationSelect(i)}
                    className={`pd-thumb ${activeImage === i ? 'active' : ''}`}
                  >
                    <Image src={img.url || img} alt="thumb" fill className="object-cover" unoptimized />
                  </div>
                ))}
              </div>
            </div>

            <div className="pd-info">
              <div className="pd-eyebrow"><span>{productCategory}</span></div>
              <h1 className="pd-title">{product.name}</h1>

              <div className="pd-rating-row">
                <div className="pd-rating-seg">
                  <span className="pd-score">{productRating.toFixed(1)}</span>
                  <div className="flex text-[#C0853A]">
                    {[...Array(5)].map((_, i) => (
                      <Star
                        key={i}
                        className={`w-3.5 h-3.5 ${i < Math.floor(productRating) ? "fill-current" : "opacity-30"}`}
                      />
                    ))}
                  </div>
                </div>
                <div className="pd-rating-sep" />
                <div className="pd-rating-seg"><span className="font-bold">{productReviewCount}</span> ratings</div>
                <div className="pd-rating-sep" />
                <div className="pd-rating-seg"><span className="font-bold">{product.soldCount || 0}</span> sold</div>
              </div>

              <div className="pd-price-block">
                <span className="text-xl font-bold text-[#9c6e30] mr-1">₱</span>
                <span className="pd-price">{(product.price || 0).toLocaleString()}</span>
              </div>

              <div className="pd-row">
                <span className="pd-row-label">Shipping</span>
                <div className="text-sm">Est. arrival in 5 days</div>
              </div>

              <div className="pd-row">
                <span className="pd-row-label">Variation</span>
                <div className="pd-chip-wrap">
                  {galleryImages.map((img, i) => (
                    <button
                      key={i}
                      onClick={() => handleVariationSelect(i)}
                      className={`pd-chip ${activeImage === i ? 'active' : ''}`}
                    >
                      <div className="pd-chip-img">
                        <Image src={img.url || img} alt="variant" fill className="object-cover" unoptimized />
                      </div>
                      <span>{img.variation || `Variant ${i + 1}`}</span>
                    </button>
                  ))}
                </div>
              </div>

              <div style={{ marginBottom: '1rem' }}>
                {!isRestricted && (
                  <button
                    onClick={() => setShowSizeGuide(true)}
                    className="pd-size-guide-btn"
                    style={{ marginLeft: 0, marginBottom: '0.6rem', display: 'flex' }}
                  >
                    <Ruler size={12} />
                    Size Guide
                  </button>
                )}
                <div style={{ display: 'flex', alignItems: 'center', marginBottom: '0.5rem' }}>
                  <span className="pd-row-label" style={{ paddingTop: 0 }}>Size</span>
                </div>
                {!isRestricted ? (
                  <div className="pd-chip-wrap">
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
                          className={`pd-size-chip ${isActive ? 'active' : ''} ${isOutOfStock ? 'opacity-40 cursor-not-allowed grayscale relative' : ''}`}
                          disabled={isOutOfStock}
                        >
                          {sName}
                          {isOutOfStock && (
                            <span className="absolute -top-2 -right-1 bg-red-500 text-white text-[7px] px-1 rounded-full font-black uppercase tracking-tighter shadow-sm">
                              Sold Out
                            </span>
                          )}
                        </button>
                      );
                    })}
                  </div>
                ) : (
                  <div className="text-xs font-bold text-[var(--muted)] opacity-50 italic">Size selection restricted in preview</div>
                )}
              </div>

              {!isRestricted && (
                <>
                  <div className="pd-row">
                    <span className="pd-row-label">Quantity</span>
                    <div className="flex items-center gap-3">
                      <div className="pd-qty">
                        <button className="pd-qty-btn" onClick={() => setQuantity(Math.max(1, quantity - 1))}><Minus size={13} /></button>
                        <input
                          type="number"
                          value={quantity}
                          onChange={(e) => {
                            const val = parseInt(e.target.value);
                            const selectedSizeInfo = typeof availableSizes[0] === 'object' 
                              ? availableSizes.find(s => (s.size || s.name) === selectedSize)
                              : null;
                            const maxStock = selectedSizeInfo ? (selectedSizeInfo.stock || 0) : (product.stock || 999);
                            
                            if (!isNaN(val)) {
                              setQuantity(Math.min(Math.max(1, val), maxStock));
                            } else {
                              setQuantity("");
                            }
                          }}
                          onBlur={() => {
                            if (quantity === "" || quantity < 1) setQuantity(1);
                          }}
                          className="pd-qty-val"
                        />
                        <button className="pd-qty-btn" onClick={() => {
                          const selectedSizeInfo = typeof availableSizes[0] === 'object' 
                            ? availableSizes.find(s => (s.size || s.name) === selectedSize)
                            : null;
                          const maxStock = selectedSizeInfo ? (selectedSizeInfo.stock || 0) : (product.stock || 999);
                          setQuantity(Math.min(quantity + 1, maxStock));
                        }}><Plus size={13} /></button>
                      </div>
                      <span className="text-xs text-gray-500">
                        {(() => {
                          const selectedSizeInfo = typeof availableSizes[0] === 'object' 
                            ? availableSizes.find(s => (s.size || s.name) === selectedSize)
                            : null;
                          return selectedSizeInfo ? `${selectedSizeInfo.stock} units available` : `${product.stock || 0} units available`;
                        })()}
                      </span>
                    </div>
                  </div>

                  {/* Refund Reminder */}
              <div className="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                <Camera className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                <div>
                  <p className="text-[11px] font-bold text-amber-800 uppercase tracking-wider mb-1">Refund Policy Reminder</p>
                  <p className="text-xs text-amber-700 leading-relaxed font-medium">
                    Please record a video while opening the package. This serves as essential proof for refund requests.
                  </p>
                </div>
              </div>

              <div className="pd-actions">
                    {maintenanceMode ? (
                      <div className="w-full p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-center gap-3 text-amber-700 text-[10px] font-black uppercase tracking-widest animate-pulse shadow-sm">
                        <ShieldAlert className="w-4 h-4" /> Orders are temporarily paused
                      </div>
                    ) : (
                      <>
                        <button onClick={handleAddToCart} className="pd-btn-cart"><ShoppingCart size={17} /> {addedToCart ? "Added!" : "Add to Cart"}</button>
                        <button onClick={handleBuyNow} className="pd-btn-buy">Buy Now</button>
                      </>
                    )}
                  </div>
                </>
              )}
            </div>
          </div>

          {/* Shop Profile Card */}
          <div className="pd-artisan">
            <div className="pd-artisan-top">
              <div className="pd-artisan-avatar">
                {(product.artisan || "L")[0].toUpperCase()}
              </div>
              <div className="pd-artisan-info">
                <div className="pd-artisan-name">{product.artisan || "Lumban Master Craft"}</div>
                <div className="pd-artisan-online">Online Now</div>
              </div>
            </div>
            <div className="pd-artisan-stats">
              <div className="pd-artisan-stat">
                <span className="pd-artisan-stat-label">Ratings</span>
                <span className="pd-artisan-stat-value">{seller?.reviewCount || 0}</span>
              </div>
              <div className="pd-artisan-stat">
                <span className="pd-artisan-stat-label">Products</span>
                <span className="pd-artisan-stat-value">{seller?.productCount || 0}</span>
              </div>
              <div className="pd-artisan-stat">
                <span className="pd-artisan-stat-label">Joined</span>
                <span className="pd-artisan-stat-value" style={{ fontSize: '0.82rem' }}>
                  {seller?.establishedOn || seller?.joined || "New"}
                </span>
              </div>
            </div>
            {!isRestricted && (
              <div className="pd-artisan-actions">
                <Link href={`/messages?sellerId=${product.sellerId || 1}`} className="pd-artisan-btn-chat">
                  <MessageCircle size={13} /> Chat
                </Link>
                <Link href={`/shop?id=${product.sellerId || 1}`} className="pd-artisan-btn-shop">
                  <Store size={13} /> View Shop
                </Link>
              </div>
            )}
          </div>

          {/* Specs & Description */}
          <div className="pd-details-card">
            <div className="pd-section-head"><Package size={14} /> Product Specifications</div>
            <div className="pd-specs">
              <div className="pd-spec-row">
                <span className="w-32 text-gray-400 text-sm">Category</span>
                <span className="font-bold text-sm">{productCategory}</span>
              </div>
            </div>
            <div className="pd-section-head"><Award size={14} /> Product Description</div>
            <div className="pd-desc">{product.description || product.name}</div>
          </div>

          {/* Reviews Section */}
          <div className="pd-reviews" id="reviews">
            <h2 className="font-serif text-lg font-bold text-[#1C1209] mb-6">Customer Feedback</h2>

            {reviewList.length > 0 ? (
              <div className="space-y-6">
                {/* Rating Header */}
                <div className="flex flex-col sm:flex-row gap-3 p-3 rounded-lg bg-[#FDFBF9] border border-[#E5DDD5] items-center">
                  <div className="flex flex-col items-center justify-center min-w-[70px]">
                    <div className="flex items-baseline gap-0.5">
                      <span className="text-lg font-bold text-[#C0420A]">{productRating.toFixed(1)}</span>
                      <span className="text-[9px] font-medium text-[#9c8876]">out of 5</span>
                    </div>
                    <div className="flex text-[#C0420A] mt-0.5">
                      {[...Array(5)].map((_, index) => (
                        <Star key={index} className={`w-3 h-3 ${index < Math.floor(productRating) ? "fill-current" : "opacity-20"}`} />
                      ))}
                    </div>
                  </div>

                  <div className="w-px h-10 bg-[#E5DDD5] hidden sm:block" />

                  <div className="flex flex-wrap gap-1.5 items-center justify-center sm:justify-start">
                    {reviewFilters.map((filter) => (
                      <button
                        key={filter.key}
                        onClick={() => setActiveReviewFilter(filter.key)}
                        className={`px-2 py-1 text-[9px] font-bold uppercase tracking-wider rounded border transition-all ${activeReviewFilter === filter.key
                          ? "border-[#C0420A] bg-[#FFF5F3] text-[#C0420A]"
                          : "border-[#E5DDD5] bg-white text-[#3D2B1F] hover:border-[#C0853A]"
                          }`}
                      >
                        {filter.label} <span className="opacity-60 ml-0.5">({filter.count})</span>
                      </button>
                    ))}
                  </div>
                </div>

                {/* Reviews List */}
                <div className="space-y-6">
                  {filteredReviews.length === 0 ? (
                    <div className="text-center py-10 text-sm font-medium text-[#9c8876] italic">No reviews found for this filter.</div>
                  ) : (
                    filteredReviews.map((review) => (
                      <div key={review.id} className="pt-6 border-t border-[#F0EBE3] first:border-0 first:pt-0">
                        <div className="flex gap-4">
                          <div className="w-10 h-10 rounded-full bg-[#E5DDD5] overflow-hidden flex items-center justify-center shrink-0">
                            {review.customer?.profilePhoto ? (
                              <img src={resolveBackendImageUrl(review.customer.profilePhoto)} alt="User" className="w-full h-full object-cover" />
                            ) : (
                              <span className="font-serif font-bold text-[#7B3A10]">{review.customer?.name?.[0] || "A"}</span>
                            )}
                          </div>

                          <div className="flex-1 min-w-0">
                            <div className="font-bold text-[13px] text-[#1C1209]">
                              {review.customer?.name || "Verified Customer"}
                            </div>
                            <div className="flex text-[#C0420A] mt-1 mb-1">
                              {[...Array(5)].map((_, index) => (
                                <Star key={index} className={`w-3 h-3 ${index < Number(review.rating || 0) ? "fill-current" : "opacity-20"}`} />
                              ))}
                            </div>
                            <div className="text-[11px] font-medium text-[#9c8876] mb-3">
                              {new Date(review.createdAt).toLocaleDateString(undefined, {
                                year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit"
                              })}
                              {review.variation && <span className="ml-2 pl-2 border-l border-[#E5DDD5]">Variation: {review.variation}</span>}
                            </div>

                            {review.comment && (
                              <p className="text-[13px] leading-relaxed text-[#3D2B1F] whitespace-pre-wrap mb-4">
                                {review.comment}
                              </p>
                            )}

                            {Array.isArray(review.images) && review.images.length > 0 && (
                              <div className="mt-3">
                                <div className="flex flex-wrap gap-2">
                                  {review.images.map((img, i) => {
                                    const isExpanded = expandedReviewId === review.id && expandedImageIndex === i;
                                    return (
                                      <button
                                        key={i}
                                        onClick={() => handleExpandReviewImage(review.id, i)}
                                        className={`relative w-[72px] h-[72px] rounded-md overflow-hidden border-2 transition-colors ${isExpanded ? "border-[#C0420A]" : "border-transparent hover:border-[#C0853A]"
                                          }`}
                                      >
                                        <img src={img} alt="Review Media" className="w-full h-full object-cover bg-[#F7F3EE]" />
                                      </button>
                                    );
                                  })}
                                </div>

                                {expandedReviewId === review.id && (
                                  <div className="mt-4 relative max-w-[400px] bg-black rounded-lg overflow-hidden group">
                                    <button
                                      onClick={(e) => { e.stopPropagation(); setExpandedReviewId(null); }}
                                      className="absolute top-2 right-2 w-8 h-8 flex items-center justify-center bg-black/50 hover:bg-black/80 rounded-full text-white shadow-md transition-colors z-10"
                                      title="Close image"
                                    >
                                      <CloseIcon size={16} />
                                    </button>
                                    <img src={review.images[expandedImageIndex]} alt="Expanded Media" className="w-full h-auto object-contain max-h-[500px]" />
                                    {review.images.length > 1 && (
                                      <>
                                        <button
                                          onClick={(e) => { e.stopPropagation(); setExpandedImageIndex((prev) => (prev - 1 + review.images.length) % review.images.length); }}
                                          className="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center bg-white/80 hover:bg-white rounded-full text-black shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                          <ChevronLeft size={16} />
                                        </button>
                                        <button
                                          onClick={(e) => { e.stopPropagation(); setExpandedImageIndex((prev) => (prev + 1) % review.images.length); }}
                                          className="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center bg-white/80 hover:bg-white rounded-full text-black shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                          <ChevronRight size={16} />
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
              <div className="text-center py-16 px-4 bg-[#FDFBF9] rounded-[1.5rem] border border-[#F0EBE3]">
                <MessageCircle className="w-10 h-10 text-[#E5DDD5] mx-auto mb-3" />
                <h3 className="font-serif text-lg font-bold text-[#1C1209] mb-1">No reviews yet</h3>
                <p className="text-sm font-medium text-[#9c8876]">Be the first to share your thoughts on this masterpiece.</p>
              </div>
            )}
          </div>

        </div>
      </div>

      {/* Size Guide Modal */}
      <AnimatePresence>
        {showSizeGuide && (
          <div className="pd-modal-overlay" onClick={() => setShowSizeGuide(false)}>
            <motion.div
              initial={{ opacity: 0, scale: 0.9, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.9, y: 20 }}
              className="pd-modal-content"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="pd-modal-header">
                <h2 className="pd-modal-title">Official Size Guide</h2>
                <button className="pd-modal-close" onClick={() => setShowSizeGuide(false)}>
                  <CloseIcon size={18} />
                </button>
              </div>
              <div className="pd-modal-tabs">
                <button
                  className={`pd-modal-tab ${sizeGuideTab === 'men' ? 'active' : ''}`}
                  onClick={() => setSizeGuideTab('men')}
                >
                  👔 Men
                </button>
                <button
                  className={`pd-modal-tab ${sizeGuideTab === 'women' ? 'active' : ''}`}
                  onClick={() => setSizeGuideTab('women')}
                >
                  👗 Women
                </button>
                <button
                  className={`pd-modal-tab ${sizeGuideTab === 'kids' ? 'active' : ''}`}
                  onClick={() => setSizeGuideTab('kids')}
                >
                  🧒 Kids
                </button>
              </div>
              <div className="pd-modal-body">
                {sizeGuideTab === 'men' && (
                  <img src="/images/size-guide.png" alt="Men's Size Guide" className="pd-guide-img" />
                )}
                {sizeGuideTab === 'women' && (
                  <img src="/images/size-guide-women.png" alt="Women's Size Guide" className="pd-guide-img" />
                )}
                {sizeGuideTab === 'kids' && (
                  <img src="/images/size-guide-kids.png" alt="Kids' Size Guide" className="pd-guide-img" />
                )}
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
      {product && (
        <ReportModal
          isOpen={isReportModalOpen}
          onClose={() => setIsReportModalOpen(false)}
          reportedId={product.sellerId}
          type="CustomerReportingSeller"
          referenceId={product.id}
          reportedName={product.name}
        />
      )}
    </Layout>
  );
}
