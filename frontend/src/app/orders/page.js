"use client";
import React, { useState, useEffect, useCallback } from "react";
import Link from "next/link";
import CustomerLayout from "@/components/CustomerLayout";
import {
  Copy,
  Calendar,
  Search,
  RefreshCw,
  Clock,
  Star,
  ChevronRight,
  Package,
  ShoppingBag,
  Loader2,
  X,
  MapPin,
  CreditCard,
  CheckCircle,
  XCircle,
  Check,
  Camera,
  ImagePlus,
  Video
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, getApiErrorMessage, getStoredUserForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { normalizeProductImages, getProductImageSrc } from "@/lib/productImages";
import ConfirmationModal from "@/components/ConfirmationModal";
import { validateImageFiles } from "@/lib/imageUploadValidation";

const STAR_LABELS = { 1: "Terrible", 2: "Bad", 3: "Okay", 4: "Good", 5: "Perfect!" };

export default function OrdersPage() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("ALL");
  const [showFeedbackPrompt, setShowFeedbackPrompt] = useState(false);
  const [newlyCompletedOrderId, setNewlyCompletedOrderId] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const STAR_LABELS = {
    1: "Terrible",
    2: "Poor",
    3: "Fair",
    4: "Good",
    5: "Excellent"
  };

  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);

  // Shopee-style: Per-item rating modal
  const [ratingModal, setRatingModal] = useState(null); // { orderId, item }
  const [ratingData, setRatingData] = useState({ rating: 5, comment: "", images: [] });
  const [uploadingImages, setUploadingImages] = useState(false);
  // Track which productIds have been rated this session
  const [ratedProducts, setRatedProducts] = useState({});
  const [actionLoading, setActionLoading] = useState(false);
  const [currentCustomerId, setCurrentCustomerId] = useState(null);

  const [refundModalOpen, setRefundModalOpen] = useState(false);
  const [refundData, setRefundData] = useState({ orderId: null, orderItemId: null, reason: "Damaged Item", message: "", video: null, item: null });

  const handleCopy = (id) => {
    navigator.clipboard.writeText(`LB-OR-${id.toString().slice(-8).toUpperCase()}`);
    setSuccess("Order ID copied to clipboard.");
    setTimeout(() => setSuccess(null), 3000);
  };

  const openRatingModal = (orderId, item, existingReview = null) => {
    setRatingModal({ orderId, item, isEditing: !!existingReview });
    if (existingReview) {
      let parsedImages = [];
      try {
        parsedImages = Array.isArray(existingReview.images) 
          ? existingReview.images 
          : (typeof existingReview.images === 'string' ? JSON.parse(existingReview.images) : []);
      } catch (e) { parsedImages = []; }
      
      setRatingData({ 
        rating: existingReview.rating || 5, 
        comment: existingReview.comment || "", 
        images: parsedImages
      });
    } else {
      setRatingData({ rating: 5, comment: "", images: [] });
    }
  };

  const submitRating = async () => {
    if (!ratingModal?.item?.product?.id) {
      setError("No product found to rate.");
      setTimeout(() => setError(null), 3000);
      return;
    }
    try {
      setActionLoading(true);
      await api.post(`/products/${ratingModal.item.product.id}/reviews`, {
        orderId: ratingModal.orderId,
        rating: ratingData.rating,
        comment: ratingData.comment,
        images: ratingData.images,
      });
      // Update the local orders state to reflect the new review
      setOrders(prevOrders => prevOrders.map(o => {
        if (String(o.id) === String(ratingModal.orderId)) {
          const newReview = {
            productId: ratingModal.item.product.id,
            rating: ratingData.rating,
            comment: ratingData.comment,
            images: ratingData.images,
            createdAt: new Date().toISOString()
          };
          const existingReviews = Array.isArray(o.reviews) ? o.reviews : [];
          const otherReviews = existingReviews.filter(r => String(r.productId) !== String(ratingModal.item.product.id));
          return { ...o, reviews: [newReview, ...otherReviews] };
        }
        return o;
      }));

      if (ratingModal.isEditing) {
        setSuccess("Review updated successfully.");
      } else {
        setSuccess("Thank you! Your review has been posted.");
      }
      setRatingModal(null);
      setTimeout(() => setSuccess(null), 4000);
    } catch (err) {
      setError(err.response?.data?.message || "Failed to submit rating.");
      setTimeout(() => setError(null), 4000);
    } finally {
      setActionLoading(false);
    }
  };

  const handleImageUpload = async (e) => {
    const files = Array.from(e.target.files);
    if (!files.length) return;
    
    if ((ratingData.images?.length || 0) + files.length > 3) {
      setError("You can only upload up to 3 images per review.");
      setTimeout(() => setError(null), 3000);
      e.target.value = "";
      return;
    }

    setUploadingImages(true);
    const newImages = [];
    
    try {
      const { validFiles, errors } = await validateImageFiles(files, "Review image");

      if (errors.length > 0) {
        setError(errors[0]);
        setTimeout(() => setError(null), 3000);
      }

      for (const file of validFiles) {
        
        const formData = new FormData();
        formData.append("image", file);
        
        const res = await api.post("/upload", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        
        if (res.data?.url) {
          newImages.push(res.data.url);
        }
      }
      
      if (newImages.length > 0) {
        setRatingData(prev => ({
          ...prev,
          images: [...(prev.images || []), ...newImages].slice(0, 3)
        }));
      }
    } catch (err) {
      console.error("Image upload failed", err);
      setError(getApiErrorMessage(err, "Failed to upload images. Please try again."));
      setTimeout(() => setError(null), 3000);
    } finally {
      setUploadingImages(false);
      e.target.value = "";
    }
  };

  const removeImage = (indexToRemove) => {
    setRatingData(prev => ({
      ...prev,
      images: prev.images.filter((_, index) => index !== indexToRemove)
    }));
  };

  const openRefundModal = (order, item) => {
    setRefundData({ 
      orderId: order.id, 
      orderItemId: item.id, 
      reason: "Damaged Item", 
      message: "", 
      video: null, 
      item: item 
    });
    setRefundModalOpen(true);
  };

  const submitRefund = async () => {
    if (refundData.reason === "Other" && !refundData.message.trim()) {
      setError("Please specify the issue encountered.");
      setRefundModalOpen(false);
      setTimeout(() => setError(null), 3000);
      return;
    }
    if (!refundData.video) {
      setError("Please upload video evidence.");
      setRefundModalOpen(false);
      setTimeout(() => setError(null), 3000);
      return;
    }

    try {
      setActionLoading(true);
      const formData = new FormData();
      formData.append("orderId", refundData.orderId);
      formData.append("orderItemId", refundData.orderItemId);
      formData.append("reason", refundData.reason);
      formData.append("message", refundData.message);
      formData.append("video", refundData.video);

      await api.post("/refunds/request", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });

      setSuccess("Refund request submitted successfully.");
      setRefundModalOpen(false);
      setTimeout(() => setSuccess(null), 3000);
      fetchOrders();
    } catch (err) {
      setError("Failed to submit refund: " + (err.response?.data?.message || err.message));
      setRefundModalOpen(false);
      setTimeout(() => setError(null), 4000);
    } finally {
      setActionLoading(false);
    }
  };

  const tabs = [
    { key: "ALL", label: "ALL", short: "ALL" },
    { key: "PENDING", label: "PENDING", short: "PENDING" },
    { key: "TO SHIP", label: "TO SHIP", short: "TO SHIP" },
    { key: "TO RECEIVE", label: "TO RECEIVE", short: "TO RCV" },
    { key: "COMPLETED", label: "COMPLETED", short: "DONE" },
    { key: "CANCELLED", label: "CANCELLED", short: "CANCLD" },
  ];

  const getDisplayStatus = (status) => {
    switch (status?.toLowerCase()) {
      case "pending": return "PENDING";
      case "processing":
      case "to ship": return "TO SHIP";
      case "shipped":
      case "to receive": return "TO RECEIVE";
      case "delivered": return "DELIVERED";
      case "received by buyer": return "RECEIVED BY BUYER";
      case "completed": return "COMPLETED";
      case "cancelled": return "CANCELLED";
      case "cancellation pending": return "CANCELLATION PENDING";
      default: return "PENDING";
    }
  };

  const getStatusStyle = (status) => {
    switch (getDisplayStatus(status)) {
      case "PENDING": return { border: "border-yellow-200", text: "text-yellow-600", dot: "bg-yellow-500" };
      case "TO SHIP": return { border: "border-blue-200", text: "text-blue-600", dot: "bg-blue-500" };
      case "TO RECEIVE": return { border: "border-blue-200", text: "text-blue-600", dot: "bg-blue-500" };
      case "DELIVERED": return { border: "border-green-200", text: "text-green-600", dot: "bg-green-500" };
      case "RECEIVED BY BUYER": return { border: "border-green-400", text: "text-green-700", dot: "bg-green-600" };
      case "COMPLETED": return { border: "border-green-400", text: "text-green-700", dot: "bg-green-600" };
      case "CANCELLED": return { border: "border-red-200", text: "text-red-500", dot: "bg-red-500" };
      case "CANCELLATION PENDING": return { border: "border-orange-200", text: "text-orange-600", dot: "bg-orange-500" };
      default: return { border: "border-gray-200", text: "text-gray-500", dot: "bg-gray-500" };
    }
  };

  const [refunds, setRefunds] = useState([]);

  const fetchRefunds = useCallback(async () => {
    try {
      const response = await api.get("/refunds/customer");
      setRefunds(response.data);
    } catch (err) {
      console.error("Failed to fetch refunds:", err);
    }
  }, []);

  const fetchOrders = useCallback(async () => {
    try {
      setLoading(true);
      const response = await api.get("/orders/my-orders");
      setOrders(response.data);
      
      const userRatingsMap = {};
      response.data.forEach(order => {
        if (Array.isArray(order.reviews)) {
          order.reviews.forEach(r => {
            if (r.productId && r.rating) {
               userRatingsMap[r.productId] = r;
            }
          });
        }
      });
      setRatedProducts(userRatingsMap);
    } catch (error) {
      if (error?.response?.status !== 401) {
        console.error("Failed to fetch orders:", error);
      }
      setOrders([]);
    } finally {
      setLoading(false);
    }
  }, []);

  const [cancellationModal, setCancellationModal] = useState({ isOpen: false, orderId: null, reason: "" });

  const handleCancelOrder = async () => {
    if (!cancellationModal.reason.trim()) {
      setError("Please provide a reason for cancellation.");
      return;
    }
    setError(null); setSuccess(null);
    try {
      setActionLoading(true);
      await api.patch(`/orders/${cancellationModal.orderId}/cancel`, { reason: cancellationModal.reason });
      setSuccess("Cancellation request submitted to the artisan.");
      setCancellationModal({ isOpen: false, orderId: null, reason: "" });
      setTimeout(() => setSuccess(null), 3000);
      fetchOrders();
    } catch (err) {
      setError(err.response?.data?.message || "Failed to submit cancellation request.");
      setTimeout(() => setError(null), 3000);
    } finally {
      setActionLoading(false);
    }
  };

  const handleConfirmReceipt = async (orderId) => {
    if (!confirm("Have you received all items in this order? This will release payment to the artisan.")) return;
    setActionLoading(true);
    setError(null);
    try {
      await api.patch(`/orders/${orderId}/status`, { status: "Received by Buyer" });
      setSuccess("Thank you for confirming receipt! The artisan has been notified.");
      setNewlyCompletedOrderId(orderId);
      setShowFeedbackPrompt(true);
      setTimeout(() => setSuccess(null), 4000);
      fetchOrders();
    } catch (err) {
      setError(err.response?.data?.message || "Failed to confirm receipt.");
      setTimeout(() => setError(null), 4000);
    } finally {
      setActionLoading(false);
    }
  };

  const { socket } = useSocket();

  useEffect(() => {
    if (typeof window === "undefined") return;

    try {
      const storedCustomer = getStoredUserForRole("customer");
      setCurrentCustomerId(storedCustomer?.id ?? null);
    } catch {
      setCurrentCustomerId(null);
    }
  }, []);

  useEffect(() => {
    fetchOrders();
    fetchRefunds();
    if (!socket) return;

    const handleStatusUpdate = (data) => {
      setOrders((prev) =>
        prev.map((order) => (order.id === data.orderId ? { ...order, status: data.status } : order))
      );
    };
    const handleNewOrderConfirmed = () => fetchOrders();
    const handleReviewUpdated = (data) => {
      const latestReview = data?.latestReview;

      if (!latestReview?.customer?.id || !currentCustomerId) return;
      if (Number(latestReview.customer.id) !== Number(currentCustomerId)) return;

      const productId = Number(latestReview.productId || data.productId || 0);
      const rating = Number(latestReview.rating || 0);
      if (!productId || !rating) return;

      setRatedProducts((prev) => ({ ...prev, [productId]: rating }));
      setOrders((prev) =>
        prev.map((order) => {
          if (Number(order.id) !== Number(latestReview.orderId)) return order;

          const existingReviews = Array.isArray(order.reviews) ? order.reviews : [];
          const reviewExists = existingReviews.some((review) => Number(review.id) === Number(latestReview.id));

          return {
            ...order,
            reviews: reviewExists
              ? existingReviews.map((review) =>
                  Number(review.id) === Number(latestReview.id) ? latestReview : review
                )
              : [latestReview, ...existingReviews],
          };
        })
      );
    };

    socket.on("order_status_update", handleStatusUpdate);
    socket.on("new_order_confirmed", handleNewOrderConfirmed);
    socket.on("review_updated", handleReviewUpdated);

    return () => {
      socket.off("order_status_update", handleStatusUpdate);
      socket.off("new_order_confirmed", handleNewOrderConfirmed);
      socket.off("review_updated", handleReviewUpdated);
    };
  }, [socket, fetchOrders, fetchRefunds, currentCustomerId]);

  const filteredOrders = orders.filter((order) => {
    const matchesTab = activeTab === "ALL" || getDisplayStatus(order.status) === activeTab;
    const matchesSearch =
      String(order.id).includes(searchQuery) ||
      order.items?.some((i) => i.product?.name?.toLowerCase().includes(searchQuery.toLowerCase()));
    return matchesTab && matchesSearch;
  });

  const getSafeImage = (imgSrc) => getProductImageSrc(imgSrc);

  const getParsedAddress = (address) => {
    if (!address) return {};
    if (typeof address === "string") {
      try { return JSON.parse(address); } catch (e) { return {}; }
    }
    return address;
  };

  return (
    <CustomerLayout>
      <div className="max-w-[1024px] mx-auto space-y-6 mb-20 px-4 md:px-0">

        {/* Top Header */}
        <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-4 sm:gap-6 pb-4 sm:pb-6">
          <div className="space-y-2 sm:space-y-4">
            <div className="flex items-center gap-3">
              <span className="w-4 sm:w-6 h-[2.5px] bg-[var(--rust)]"></span>
              <span className="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.2em] text-[var(--rust)]">MY ACCOUNT</span>
            </div>
            <h1 className="font-serif text-lg sm:text-xl font-bold tracking-tight text-[#2A2A2A]">
              My <span className="text-[var(--rust)] italic font-medium">Orders</span>
            </h1>
          </div>
          <div className="flex items-center gap-2 sm:gap-3 shrink-0">
            <div className="relative group flex-1 sm:w-80">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[var(--muted)]" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search orders..."
                className="w-full pl-10 pr-4 py-2.5 sm:py-3 bg-white border border-[var(--border)] rounded-xl text-[11px] sm:text-xs font-medium text-[#2A2A2A] placeholder:text-[var(--muted)] outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)] transition-all shadow-sm"
              />
            </div>
            <button onClick={fetchOrders} className="p-2.5 sm:p-3 bg-white border border-[var(--border)] rounded-xl hover:text-[var(--rust)] hover:bg-[#FDFCFB] transition-colors shadow-sm active:scale-95">
              <RefreshCw className="w-4 h-4 sm:w-5 sm:h-5 text-[var(--muted)] hover:text-[var(--rust)] transition-all" />
            </button>
          </div>
        </div>

        {/* Alerts */}
        <AnimatePresence>
          {error && (
            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-[1rem] text-sm font-bold flex items-center gap-2 mb-6">
              <XCircle className="w-5 h-5 text-red-500" /> {error}
            </motion.div>
          )}
          {success && (
            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} className="p-4 bg-green-50 border border-green-200 text-green-700 rounded-[1rem] text-sm font-bold flex items-center gap-2 mb-6">
              <CheckCircle className="w-5 h-5 text-green-500" /> {success}
            </motion.div>
          )}
        </AnimatePresence>

        {/* Tabs - Grid on mobile, row on desktop */}
        <div className="border-b border-[var(--border)]">
          {/* Mobile: 3-column grid with full labels, smaller text */}
          <div className="grid grid-cols-3 sm:hidden">
            {tabs.map(({ key, label }) => (
              <button
                key={key}
                onClick={() => setActiveTab(key)}
                className={`py-2.5 px-1 text-[8px] font-bold uppercase tracking-[0.04em] transition-all border-b-2 outline-none focus:outline-none text-center leading-tight ${activeTab === key
                    ? "border-[var(--rust)] text-[var(--rust)] bg-[var(--rust)]/5"
                    : "border-transparent text-[#2A2A2A]/40"
                  }`}
              >
                {label}
              </button>
            ))}
          </div>
          {/* Desktop: single row */}
          <div className="hidden sm:flex gap-1">
            {tabs.map(({ key, label }) => (
              <button
                key={key}
                onClick={() => setActiveTab(key)}
                className={`px-6 py-4 text-xs font-bold uppercase tracking-[0.1em] transition-all whitespace-nowrap border-b-2 outline-none focus:outline-none ${activeTab === key
                    ? "border-[var(--rust)] text-[var(--rust)]"
                    : "border-transparent text-[#2A2A2A]/40 hover:text-[#2A2A2A]"
                  }`}
              >
                {label}
              </button>
            ))}
          </div>
        </div>

        <div className="h-4"></div>

        {/* Orders List */}
        <div className="space-y-6">
          {loading ? (
            <div className="py-24 text-center">
              <Loader2 className="w-10 h-10 animate-spin mx-auto text-[var(--muted)] opacity-50 mb-4" />
              <p className="text-sm font-bold uppercase tracking-widest text-[var(--muted)]">Loading Orders...</p>
            </div>
          ) : filteredOrders.length === 0 ? (
            <div className="py-16 sm:py-24 text-center bg-white rounded-[1.5rem] border border-[var(--border)] shadow-sm px-6">
              <ShoppingBag className="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-[var(--muted)] opacity-20 mb-4 sm:mb-6" />
              <h3 className="text-lg sm:text-xl font-serif font-bold text-[#2A2A2A] mb-2">No Records Found</h3>
              <p className="text-[10px] sm:text-xs text-[var(--muted)] italic">No orders found matching your current filter.</p>
            </div>
          ) : (
            <AnimatePresence>
              {filteredOrders.map((order, idx) => {
                const displayStatus = getDisplayStatus(order.status);
                const sProps = getStatusStyle(order.status);
                const isCompleted = displayStatus === "COMPLETED";

                return (
                  <motion.div
                    key={order.id}
                    initial={{ opacity: 0, y: 15 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.98 }}
                    transition={{ delay: idx * 0.05 }}
                    className={`bg-[#FDFBF9] rounded-[1rem] border border-[#E5DDD5] shadow-[0_2px_10px_rgba(0,0,0,0.02)] overflow-hidden ${newlyCompletedOrderId === order.id ? "ring-2 ring-amber-400" : ""}`}
                  >
                    {/* Card Header */}
                    <div className="px-5 sm:px-6 py-3.5 border-b border-[#E5DDD5] flex flex-wrap items-center justify-between gap-4 bg-white">
                      <div className="flex flex-wrap items-center gap-3 sm:gap-4 text-[10px] font-bold uppercase tracking-wider text-[#9c8876]">
                        <div className="flex items-center gap-1.5 opacity-90">
                          ORDER ID{" "}
                          <span className="text-[#2A2A2A]">LB-OR-{order.id.toString().slice(-8).toUpperCase()}</span>
                          <button onClick={(e) => { e.stopPropagation(); handleCopy(order.id); }} className="ml-0.5 hover:text-[#7B3A10] transition-colors">
                            <Copy className="w-3 h-3" />
                          </button>
                        </div>
                        <div className="w-px h-3 bg-[#E5DDD5] hidden sm:block" />
                        <div className="flex items-center gap-1.5 opacity-90">
                          <Calendar className="w-3.5 h-3.5" />
                          {new Date(order.createdAt).toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" })}
                        </div>
                      </div>
                      <div className={`px-2.5 py-1 rounded-full border flex items-center gap-1.5 text-[9px] font-extrabold uppercase tracking-widest ${sProps.border} ${sProps.text} bg-white`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${sProps.dot}`} /> {displayStatus}
                      </div>
                    </div>

                    {/* Card Body */}
                    <div className="p-4 sm:p-6 flex flex-col lg:flex-row gap-5 lg:gap-8 bg-[#FDFBF9]">

                      {/* LEFT: Items */}
                      <div className="flex-1 flex flex-col gap-4">
                        {order.items?.map((item, i) => {
                          const priceNum = parseFloat(item.price?.toString().replace(/[^0-9.]/g, "") || 0);
                          const productId = item.product?.id;
                          const itemReview = order.reviews?.find(r => String(r.productId) === String(productId));
                          const isRated = !!itemReview;
                          const ratedStars = isRated ? itemReview.rating : null;

                          const existingRefund = refunds.find(r => String(r.orderItemId) === String(item.id));

                          return (
                            <div key={i} className="flex flex-row items-center gap-4 sm:gap-5 pb-4 border-b border-[#EAE5DF] last:border-b-0 last:pb-0">
                              {/* Thumbnail */}
                              <Link href={`/products?id=${productId}`} className="w-14 h-14 bg-white border border-[#E5DDD5] flex items-center justify-center rounded-lg shrink-0 overflow-hidden shadow-sm hover:border-[var(--rust)] hover:opacity-90 transition-all cursor-pointer">
                                {item.product?.image ? (
                                  <img src={getProductImageSrc(item.product.image)} alt={item.product.name} className="w-full h-full object-cover" />
                                ) : (
                                  <Package className="w-5 h-5 text-gray-300" />
                                )}
                              </Link>

                              {/* Details + Rate button */}
                              <div className="flex-1 flex flex-row items-center justify-between w-full">
                                <div className="space-y-1">
                                  <Link href={`/products?id=${productId}`} className="text-[13px] font-bold text-[#2A2A2A] hover:text-[var(--rust)] transition-colors block cursor-pointer">{item.product?.name || "Lumbarong Product"}</Link>
                                  <div className="flex items-center gap-2">
                                    <span className="bg-[#FAF7F2] border border-[#E5DDD5] text-[#9c8876] px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest">Qty: {item.quantity}</span>
                                    <span className="bg-[#FAF7F2] border border-[#E5DDD5] text-[#9c8876] px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest">Size: {item.size || "M"}</span>
                                  </div>
                                </div>
                                <div className="flex flex-col items-end gap-1.5">
                                  <div className="text-[17px] font-bold text-[#7B3A10]">
                                    ₱{(priceNum || 0).toLocaleString()}
                                  </div>
                                  {/* Rate Badge */}
                                  {isCompleted && (
                                    isRated ? (
                                      <div className="flex flex-col items-end gap-1.5">
                                        <div className="flex items-center gap-1 px-2.5 py-1 bg-green-50 border border-green-200 rounded text-[9px] font-extrabold text-green-700 uppercase tracking-widest">
                                          <Check className="w-3 h-3" /> Rated {ratedStars}★
                                        </div>
                                        <button 
                                          onClick={() => openRatingModal(order.id, item, itemReview)}
                                          className="text-[10px] text-[var(--rust)] font-bold hover:underline"
                                        >
                                          Edit Review
                                        </button>
                                      </div>
                                    ) : (
                                      <button
                                        onClick={() => openRatingModal(order.id, item)}
                                        className="flex items-center gap-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 rounded text-[9px] font-extrabold uppercase tracking-widest transition-all active:scale-95 shadow-sm"
                                      >
                                        <Star className="w-3 h-3 fill-current" /> Rate
                                      </button>
                                    )
                                  )}
                                  
                                  {existingRefund ? (
                                    <div className={`px-2.5 py-1 rounded text-[9px] font-extrabold uppercase tracking-widest mt-1 ${
                                      existingRefund.status === 'Approved' ? 'bg-green-50 text-green-700 border border-green-200' :
                                      existingRefund.status === 'Rejected' ? 'bg-red-50 text-red-700 border border-red-200' :
                                      'bg-amber-50 text-amber-700 border border-amber-200'
                                    }`}>
                                      Refund {existingRefund.status}
                                    </div>
                                  ) : ["RECEIVED BY BUYER", "DELIVERED", "COMPLETED"].includes(displayStatus) && (
                                    <button
                                      onClick={() => openRefundModal(order, item)}
                                      className="flex items-center gap-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 rounded text-[9px] font-extrabold uppercase tracking-widest transition-all active:scale-95 shadow-sm mt-1"
                                    >
                                      <RefreshCw className="w-3 h-3" /> Request Refund
                                    </button>
                                  )}
                                </div>
                              </div>
                            </div>
                          );
                        })}
                      </div>

                      {/* RIGHT: Actions & Totals */}
                      <div className="lg:w-56 shrink-0 flex flex-col justify-center border-t lg:border-t-0 lg:border-l border-[#EAE5DF] pt-5 lg:pt-0 lg:pl-6 mt-1 lg:mt-0">
                        <div className="w-full flex lg:flex-col justify-between items-center lg:items-end gap-1 mb-4">
                          <span className="text-[9px] font-extrabold uppercase tracking-[0.2em] text-[#9c8876]">ORDER TOTAL</span>
                          <div className="text-right">
                            <div className="text-xl font-bold text-[#7B3A10] leading-none mb-1">₱{(order.totalAmount || 0).toLocaleString()}</div>
                            <div className="text-[9px] text-[#999] opacity-70 tracking-widest uppercase font-bold">{order.items?.length || 1} item{(order.items?.length || 1) !== 1 ? "s" : ""}</div>
                          </div>
                        </div>
                        <div className="w-full space-y-2.5">
                          <button onClick={() => setSelectedOrder(order)} className="w-full py-2.5 bg-[#3D2B1F] hover:bg-[#2A1E14] text-white rounded-lg text-[9px] font-extrabold uppercase tracking-[0.15em] transition-colors shadow flex items-center justify-center gap-2 group">
                            View Details <ChevronRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
                          </button>

                          {displayStatus === "DELIVERED" && (
                            <button onClick={(e) => { e.stopPropagation(); handleConfirmReceipt(order.id); }} className="w-full py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-[9px] font-extrabold uppercase tracking-[0.15em] transition-colors shadow flex items-center justify-center gap-2">
                              <Check className="w-3.5 h-3.5" /> Confirm Receipt
                            </button>
                          )}

                          {displayStatus === "RECEIVED BY BUYER" && (
                            <>
                              <button onClick={(e) => { e.stopPropagation(); handleConfirmReceipt(order.id); }} className="w-full py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-[9px] font-extrabold uppercase tracking-[0.15em] transition-colors shadow flex items-center justify-center gap-2">
                                <Check className="w-3.5 h-3.5" /> Mark as Completed
                              </button>
                              <div className="text-[8px] text-[var(--muted)] text-center italic mt-1">If there are issues with specific items, use Request Refund below.</div>
                            </>
                          )}

                          {displayStatus === "CANCELLED" && (
                            <div className="text-center text-[9px] italic text-red-500/80 bg-red-50 py-2 rounded-lg border border-red-100 uppercase tracking-widest font-bold">Order Cancelled</div>
                          )}

                          {displayStatus === "CANCELLATION PENDING" && (
                            <div className="text-center text-[9px] italic text-orange-500/80 bg-orange-50 py-2 rounded-lg border border-orange-100 uppercase tracking-widest font-bold">Cancellation Pending Review</div>
                          )}

                          {displayStatus === "PENDING" && (
                            <button onClick={(e) => { e.stopPropagation(); setCancellationModal({ isOpen: true, orderId: order.id, reason: "" }); }} className="w-full py-2.5 bg-white hover:bg-red-50 text-red-500 border border-red-100 rounded-lg text-[9px] font-extrabold uppercase tracking-[0.15em] transition-colors shadow-sm flex items-center justify-center gap-2 group mt-2">
                              <X className="w-3 h-3 group-hover:rotate-90 transition-transform" /> Cancel Order
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  </motion.div>
                );
              })}
            </AnimatePresence>
          )}
        </div>
      </div>

      <ConfirmationModal
        isOpen={showLogoutConfirm}
        onClose={() => setShowLogoutConfirm(false)}
        onConfirm={() => { }}
        title="Signing Out?"
        message="Are you sure you want to end your session? We'd love to see you back soon!"
        confirmText="Yes, Logout"
        cancelText="Cancel"
        type="danger"
      />

      <ConfirmationModal
        isOpen={showFeedbackPrompt}
        onClose={() => setShowFeedbackPrompt(false)}
        onConfirm={() => {
          setShowFeedbackPrompt(false);
          setActiveTab("COMPLETED");
        }}
        title="Share Your Experience"
        message="Would you like to leave a review for this artisan? Your feedback helps the Lumban community grow."
        confirmText="Leave Feedback"
        cancelText="Maybe Later"
        type="info"
      />

      {/* Order Details Modal */}
      <AnimatePresence>
        {selectedOrder && (
          <div className="fixed inset-0 z-[150] flex items-center justify-center p-4 sm:p-6 overflow-hidden">
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setSelectedOrder(null)} className="absolute inset-0 bg-[#2A1E14]/60 backdrop-blur-sm" />
            <motion.div initial={{ opacity: 0, scale: 0.95, y: 20 }} animate={{ opacity: 1, scale: 1, y: 0 }} exit={{ opacity: 0, scale: 0.95, y: 20 }} className="bg-white w-full max-w-2xl max-h-[88vh] rounded-[2rem] shadow-2xl relative z-10 overflow-hidden flex flex-col border border-white/20">
              <div className="px-6 sm:px-8 py-6 border-b border-[var(--border)] flex items-center justify-between bg-[#FDFCFB]">
                <div>
                  <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#2A2A2A]">Order Details</h2>
                  <p className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mt-1">
                    LB-OR-{selectedOrder.id.toString().slice(-8).toUpperCase()} • {new Date(selectedOrder.createdAt).toLocaleDateString()}
                  </p>
                </div>
                <button onClick={() => setSelectedOrder(null)} className="p-2.5 bg-white border border-[var(--border)] rounded-xl hover:text-[var(--rust)] transition-all active:scale-90 shadow-sm">
                  <X className="w-5 h-5" />
                </button>
              </div>
              <div className="flex-1 overflow-y-auto p-5 sm:p-8 pb-12 space-y-7 sm:space-y-8 artisan-scrollbar">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-3">
                    <div className="flex items-center gap-2 text-[10px] font-extrabold text-[var(--rust)] uppercase tracking-widest opacity-80"><MapPin className="w-3 h-3" /> SHIPPING ADDRESS</div>
                    <div className="bg-[#FDFBF9] p-4 rounded-2xl border border-[var(--cream)] shadow-sm">
                      <div className="font-bold text-[#2A2A2A] text-sm mb-1">{getParsedAddress(selectedOrder.shippingAddress)?.name || "Registry Name Missing"}</div>
                      <div className="text-[10px] font-bold text-[var(--rust)] mb-2">{getParsedAddress(selectedOrder.shippingAddress)?.phone || "Phone Missing"}</div>
                      <div className="text-xs text-[var(--muted)] leading-relaxed min-h-[3rem]">
                        {!(getParsedAddress(selectedOrder.shippingAddress)?.houseNo || getParsedAddress(selectedOrder.shippingAddress)?.street || getParsedAddress(selectedOrder.shippingAddress)?.barangay) ? (
                          <div className="italic opacity-40 py-2 uppercase text-[9px] font-bold tracking-widest text-[#2A2118]">Archived Registry Data Unavailable</div>
                        ) : (
                          <>
                            {getParsedAddress(selectedOrder.shippingAddress)?.houseNo && <span>{getParsedAddress(selectedOrder.shippingAddress).houseNo} </span>}
                            {getParsedAddress(selectedOrder.shippingAddress)?.street && <span>{getParsedAddress(selectedOrder.shippingAddress).street}, </span>}
                            <br />
                            {getParsedAddress(selectedOrder.shippingAddress)?.barangay && <span>Brgy. {getParsedAddress(selectedOrder.shippingAddress).barangay}, </span>}
                            {getParsedAddress(selectedOrder.shippingAddress)?.city && <span>{getParsedAddress(selectedOrder.shippingAddress).city}, </span>}
                            <br />
                            {getParsedAddress(selectedOrder.shippingAddress)?.province && <span>{getParsedAddress(selectedOrder.shippingAddress).province}, </span>}
                            {getParsedAddress(selectedOrder.shippingAddress)?.postalCode && <span>{getParsedAddress(selectedOrder.shippingAddress).postalCode}</span>}
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                  <div className="space-y-3">
                    <div className="flex items-center gap-2 text-[10px] font-extrabold text-[var(--rust)] uppercase tracking-widest opacity-80"><CreditCard className="w-3 h-3" /> PAYMENT METHOD</div>
                    <div className="bg-[#FDFBF9] p-4 rounded-2xl border border-[var(--cream)] shadow-sm">
                      <div className="font-bold text-[#2A2A2A] text-sm mb-1">{selectedOrder.paymentMethod || "GCash"}</div>
                      <div className="text-xs text-[var(--muted)]">Reference: <span className="font-mono text-[var(--rust)]">{selectedOrder.paymentReference || "N/A"}</span></div>
                    </div>
                  </div>
                </div>
                <div className="space-y-4">
                  <div className="flex items-center gap-2 text-[10px] font-extrabold text-[var(--rust)] uppercase tracking-widest opacity-80"><Clock className="w-3 h-3" /> ORDER STATUS</div>
                  <div className={`inline-flex items-center gap-2 px-4 py-2 rounded-full border text-[10px] font-bold uppercase tracking-widest ${getStatusStyle(selectedOrder.status).border} ${getStatusStyle(selectedOrder.status).text}`}>
                    <div className={`w-1.5 h-1.5 rounded-full ${getStatusStyle(selectedOrder.status).dot}`} />
                    {selectedOrder.status}
                  </div>
                </div>
                <div className="space-y-4">
                  <div className="flex items-center gap-2 text-[10px] font-extrabold text-[var(--rust)] uppercase tracking-widest opacity-80"><ShoppingBag className="w-3 h-3" /> PURCHASED ITEMS</div>
                  <div className="space-y-3">
                    {selectedOrder.items?.map((item, i) => {
                      const existingRefund = refunds.find(r => String(r.orderItemId) === String(item.id));
                      return (
                      <div key={i} className="flex gap-4 p-4 bg-white border border-[var(--border)] rounded-2xl shadow-sm">
                        <Link href={`/products?id=${item.product?.id}`} className="w-14 h-14 bg-[#FDFBF9] rounded-xl border border-[var(--border)] flex items-center justify-center p-1 shrink-0 overflow-hidden hover:border-[var(--rust)] hover:opacity-90 transition-all cursor-pointer">
                          <img src={getSafeImage(item.product?.image)} alt="" className="w-full h-full object-cover rounded-lg" />
                        </Link>
                        <div className="flex-1 min-w-0">
                          <Link href={`/products?id=${item.product?.id}`} className="font-bold text-[#2A2A2A] text-sm truncate hover:text-[var(--rust)] transition-colors block cursor-pointer">{item.product?.name}</Link>
                          <div className="text-[10px] text-[var(--muted)] mt-0.5">Qty: {item.quantity} • Size: {item.size}</div>
                        </div>
                        <div className="text-right flex flex-col items-end justify-between">
                          <div className="text-sm font-bold text-[var(--rust)]">₱{Number(item.price || 0).toLocaleString()}</div>
                          {existingRefund ? (
                            <div className={`px-2 py-1 rounded text-[9px] font-extrabold uppercase tracking-widest mt-2 border ${
                              existingRefund.status === 'Approved' ? 'bg-green-50 text-green-700 border-green-200' :
                              existingRefund.status === 'Rejected' ? 'bg-red-50 text-red-700 border-red-200' :
                              'bg-amber-50 text-amber-700 border-amber-200'
                            }`}>
                              Refund {existingRefund.status}
                            </div>
                          ) : ["RECEIVED BY BUYER", "DELIVERED", "COMPLETED"].includes(selectedOrder.status?.toUpperCase()) && (
                            <button
                              onClick={() => openRefundModal(selectedOrder, item)}
                              className="flex items-center gap-1 px-2 py-1 bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 rounded text-[9px] font-extrabold uppercase tracking-widest transition-all active:scale-95 shadow-sm mt-2"
                            >
                              <RefreshCw className="w-3 h-3" /> Request Refund
                            </button>
                          )}
                        </div>
                      </div>
                    )})}
                  </div>
                </div>
              </div>
              <div className="px-6 sm:px-8 py-6 bg-[#FDFCFB] border-t border-[var(--border)] flex items-center justify-between">
                <span className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">SUBTOTAL AMOUNT</span>
                <div className="text-lg sm:text-xl font-bold text-[var(--rust)]">₱{Number(selectedOrder.totalAmount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Refund Modal */}
      <AnimatePresence>
        {refundModalOpen && (
          <div className="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setRefundModalOpen(false)} />
            <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.95 }} className="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md relative z-10 space-y-5 border border-[var(--border)] overflow-hidden">
              <div className="flex items-center justify-between">
                <h3 className="font-serif text-2xl font-bold text-[#2A2A2A]">Request Refund</h3>
                <button onClick={() => setRefundModalOpen(false)} className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                  <X className="w-5 h-5 text-[var(--muted)]" />
                </button>
              </div>

              {/* Item Info Summary */}
              <div className="flex items-center gap-4 p-3 bg-stone-50 rounded-xl border border-stone-100">
                <div className="w-12 h-12 rounded-lg border border-stone-200 overflow-hidden bg-white shrink-0">
                  <img src={getSafeImage(refundData.item?.product?.image)} alt="" className="w-full h-full object-cover" />
                </div>
                <div className="min-w-0">
                  <div className="font-bold text-xs text-[#2A2A2A] truncate">{refundData.item?.product?.name}</div>
                  <div className="text-[10px] text-[var(--muted)]">Qty: {refundData.item?.quantity} • Size: {refundData.item?.size}</div>
                </div>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mb-1.5 block">Select Reason</label>
                  <select 
                    value={refundData.reason}
                    onChange={(e) => setRefundData({ ...refundData, reason: e.target.value })}
                    className="w-full p-3 bg-white border border-[var(--border)] rounded-xl text-sm outline-none focus:border-[var(--rust)] transition-all font-medium"
                  >
                    <option value="Damaged Item">Damaged Item</option>
                    <option value="Wrong Size">Wrong Size</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                {refundData.reason === "Other" && (
                  <div>
                    <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mb-1.5 block">Please specify the issue</label>
                    <textarea
                      value={refundData.message}
                      onChange={(e) => setRefundData({ ...refundData, message: e.target.value })}
                      placeholder="Tell us more about the encounter..."
                      className="w-full p-3 border border-[var(--border)] rounded-xl text-sm min-h-[100px] outline-none focus:border-[var(--rust)] transition-all"
                    />
                  </div>
                )}

                <div>
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mb-1.5 block">Video Evidence (Required)</label>
                  <div className="relative">
                    <input 
                      type="file" 
                      accept="video/*" 
                      onChange={(e) => setRefundData({ ...refundData, video: e.target.files[0] })}
                      className="hidden" 
                      id="refund-video-upload"
                    />
                    <label 
                      htmlFor="refund-video-upload"
                      className={`flex flex-col items-center justify-center p-6 border-2 border-dashed rounded-xl cursor-pointer transition-all ${refundData.video ? 'border-green-300 bg-green-50' : 'border-[var(--border)] hover:border-[var(--rust)] hover:bg-[var(--rust)]/5'}`}
                    >
                      {refundData.video ? (
                        <>
                          <CheckCircle className="w-8 h-8 text-green-500 mb-2" />
                          <span className="text-xs font-bold text-green-700 truncate max-w-full px-2">{refundData.video.name}</span>
                          <span className="text-[10px] text-green-600/70 mt-1">Click to change video</span>
                        </>
                      ) : (
                        <>
                          <Video className="w-8 h-8 text-[var(--muted)] mb-2" />
                          <span className="text-xs font-bold text-[var(--muted)]">Upload Opening Video</span>
                          <span className="text-[9px] text-[var(--muted)]/60 mt-1 uppercase tracking-tighter">MP4, MOV, or AVI up to 50MB</span>
                        </>
                      )}
                    </label>
                  </div>
                </div>
              </div>

              <div className="pt-2">
                <button 
                  onClick={submitRefund} 
                  disabled={actionLoading}
                  className="w-full py-3.5 bg-[#C0420A] hover:bg-[#A03608] text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  {actionLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : "Submit Refund Request"}
                </button>
                <div className="text-[9px] text-[var(--muted)] text-center mt-3 leading-relaxed">
                  By submitting, you agree to our <span className="underline cursor-pointer">Refund Policy</span>. <br/>Proof will be reviewed by the artisan.
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Shopee-style Rating Modal */}
      <AnimatePresence>
        {ratingModal && (
          <div className="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setRatingModal(null)} />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 border border-[var(--border)] overflow-hidden"
            >
              {/* Modal Header */}
              <div className="flex items-center justify-between px-6 py-5 border-b border-[var(--border)] bg-[#FDFCFB]">
                <h3 className="font-serif text-xl font-bold text-[#2A2A2A]">
                  {ratingModal.isEditing ? "Edit Your Review" : "Rate Product"}
                </h3>
                <button onClick={() => setRatingModal(null)} className="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                  <X className="w-4 h-4 text-[var(--muted)]" />
                </button>
              </div>

              <div className="p-6 space-y-6">
                {/* Product info strip */}
                <div className="flex items-center gap-4 p-4 bg-[#FDFBF9] rounded-2xl border border-[var(--cream)]">
                  <div className="w-14 h-14 rounded-xl border border-[var(--border)] bg-white overflow-hidden shrink-0">
                    {ratingModal.item.product?.image ? (
                      <img src={getProductImageSrc(ratingModal.item.product.image)} alt="" className="w-full h-full object-cover" />
                    ) : (
                      <Package className="w-6 h-6 text-gray-300 m-4" />
                    )}
                  </div>
                  <div className="min-w-0">
                    <div className="font-bold text-[#2A2A2A] text-sm truncate">{ratingModal.item.product?.name || "Product"}</div>
                    <div className="text-[10px] text-[var(--muted)] mt-0.5 uppercase tracking-wider">
                      Qty: {ratingModal.item.quantity} · Size: {ratingModal.item.size}
                    </div>
                  </div>
                </div>

                {/* Star picker with Shopee-style labels */}
                <div className="text-center space-y-3">
                  <p className="text-[11px] font-bold uppercase tracking-widest text-[var(--muted)]">Product Quality</p>
                  <div className="flex gap-2 justify-center">
                    {[1, 2, 3, 4, 5].map((star) => (
                      <button
                        key={star}
                        onClick={() => setRatingData((prev) => ({ ...prev, rating: star }))}
                        className="transition-transform hover:scale-110 active:scale-95"
                      >
                        <Star className={`w-10 h-10 transition-colors ${star <= ratingData.rating ? "fill-amber-400 text-amber-400" : "text-gray-200"}`} />
                      </button>
                    ))}
                  </div>
                  {/* Animated star label */}
                  <motion.p
                    key={ratingData.rating}
                    initial={{ opacity: 0, y: -4 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="text-sm font-bold"
                    style={{ color: ratingData.rating <= 2 ? "#C0422A" : ratingData.rating === 3 ? "#9c6e30" : "#2E7D53" }}
                  >
                    {STAR_LABELS[ratingData.rating]}
                  </motion.p>
                </div>

                {/* Comment */}
                <div>
                  <p className="text-[11px] font-bold uppercase tracking-widest text-[var(--muted)] mb-2">Your Comments (Optional)</p>
                  <textarea
                    value={ratingData.comment}
                    onChange={(e) => setRatingData((prev) => ({ ...prev, comment: e.target.value }))}
                    placeholder="Share your experience with this masterpiece..."
                    className="w-full p-3 border border-[var(--border)] rounded-xl text-sm min-h-[90px] outline-none focus:border-amber-400 transition-colors resize-none mb-4"
                  />
                </div>

                {/* Image Upload */}
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <p className="text-[11px] font-bold uppercase tracking-widest text-[var(--muted)]">Add Photos <span className="normal-case font-normal text-xs">({ratingData.images?.length || 0}/3)</span></p>
                  </div>
                  
                  <div className="flex flex-wrap gap-3">
                    {ratingData.images?.map((img, idx) => (
                      <div key={idx} className="relative w-16 h-16 rounded-xl border border-[var(--border)] overflow-hidden group">
                        <img src={img} alt="Review upload" className="w-full h-full object-cover" />
                        <button
                          onClick={() => removeImage(idx)}
                          className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                          <XCircle className="w-5 h-5 text-white" />
                        </button>
                      </div>
                    ))}
                    
                    {(ratingData.images?.length || 0) < 3 && (
                      <label className={`w-16 h-16 rounded-xl border-2 border-dashed border-[var(--border)] flex flex-col items-center justify-center cursor-pointer hover:border-amber-400 hover:bg-amber-50/50 transition-colors ${uploadingImages ? 'opacity-50 pointer-events-none' : ''}`}>
                        <input 
                          type="file" 
                          accept="image/jpeg,image/png" 
                          multiple 
                          onChange={handleImageUpload} 
                          className="hidden" 
                          disabled={uploadingImages}
                        />
                        {uploadingImages ? (
                          <Loader2 className="w-5 h-5 text-amber-500 animate-spin" />
                        ) : (
                          <>
                            <Camera className="w-5 h-5 text-[var(--muted)] mb-1" />
                            <span className="text-[8px] font-bold uppercase tracking-widest text-[var(--muted)]">Add</span>
                          </>
                        )}
                      </label>
                    )}
                  </div>
                </div>

                {/* Action buttons */}
                <div className="flex gap-3">
                  <button onClick={() => setRatingModal(null)} className="flex-1 py-3 rounded-xl text-sm font-bold text-[var(--muted)] border border-[var(--border)] hover:bg-gray-50 transition-colors">
                    Cancel
                  </button>
                  <button
                    onClick={submitRating}
                    disabled={actionLoading}
                    className="flex-[2] py-3 bg-amber-400 hover:bg-amber-500 text-white rounded-xl text-sm font-extrabold uppercase tracking-wider shadow-md disabled:opacity-50 flex items-center justify-center transition-all active:scale-95"
                  >
                    {actionLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : (ratingModal.isEditing ? "Update Review" : "Submit Review")}
                  </button>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Cancellation Request Modal */}
      <AnimatePresence>
        {cancellationModal.isOpen && (
          <div className="fixed inset-0 z-[200] flex items-center justify-center p-4">
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setCancellationModal({ ...cancellationModal, isOpen: false })} className="absolute inset-0 bg-black/60 backdrop-blur-sm" />
            <motion.div initial={{ opacity: 0, scale: 0.95, y: 20 }} animate={{ opacity: 1, scale: 1, y: 0 }} exit={{ opacity: 0, scale: 0.95, y: 20 }} className="bg-white w-full max-w-md rounded-[2rem] shadow-2xl relative z-10 overflow-hidden border border-[var(--border)]">
              <div className="p-8">
                <div className="flex items-center justify-between mb-6">
                  <h3 className="font-serif text-xl font-bold text-[#2A2A2A]">Cancel Order</h3>
                  <button onClick={() => setCancellationModal({ ...cancellationModal, isOpen: false })} className="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <X className="w-5 h-5" />
                  </button>
                </div>
                
                <p className="text-xs text-[var(--muted)] mb-6 leading-relaxed">
                  Please provide a reason for cancelling this order. This request will be reviewed by the artisan for approval.
                </p>

                <div className="space-y-4">
                  <div>
                    <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--rust)] mb-2 block">Reason for Cancellation</label>
                    <textarea
                      value={cancellationModal.reason}
                      onChange={(e) => setCancellationModal({ ...cancellationModal, reason: e.target.value })}
                      placeholder="E.g., Changed my mind, Duplicate order, etc."
                      className="w-full h-32 p-4 bg-[#FDFBF9] border border-[var(--border)] rounded-2xl text-sm outline-none focus:border-[var(--rust)] transition-all resize-none artisan-scrollbar"
                    />
                  </div>
                </div>

                <div className="flex gap-3 mt-8">
                  <button
                    onClick={() => setCancellationModal({ ...cancellationModal, isOpen: false })}
                    className="flex-1 py-3.5 border border-[var(--border)] rounded-xl text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] hover:bg-gray-50 transition-all"
                  >
                    Keep Order
                  </button>
                  <button
                    onClick={handleCancelOrder}
                    disabled={actionLoading || !cancellationModal.reason.trim()}
                    className="flex-[2] py-3.5 bg-[var(--bark)] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[var(--rust)] transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg"
                  >
                    {actionLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : <X className="w-4 h-4" />}
                    Submit Request
                  </button>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </CustomerLayout>
  );
}
