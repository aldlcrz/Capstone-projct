"use client";
import React, { useState, useEffect, useCallback } from "react";
import Link from "next/link";
import Image from "next/image";
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
import { api, getApiErrorMessage, getStoredUserForRole, getTokenForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { normalizeProductImages, getProductImageSrc } from "@/lib/productImages";
import ConfirmationModal from "@/components/ConfirmationModal";
import { validateImageFiles } from "@/lib/imageUploadValidation";

const STAR_LABELS = { 1: "Terrible", 2: "Bad", 3: "Okay", 4: "Good", 5: "Perfect!" };

export default function OrdersManagement({ standalone = false }) {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("ALL");
  const [showFeedbackPrompt, setShowFeedbackPrompt] = useState(false);
  const [newlyCompletedOrderId, setNewlyCompletedOrderId] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  
  const [ratingModal, setRatingModal] = useState(null); 
  const [ratingData, setRatingData] = useState({ rating: 5, comment: "", images: [] });
  const [uploadingImages, setUploadingImages] = useState(false);
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

      setSuccess(ratingModal.isEditing ? "Review updated successfully." : "Thank you! Your review has been posted.");
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
        if (res.data?.url) newImages.push(res.data.url);
      }
      if (newImages.length > 0) {
        setRatingData(prev => ({ ...prev, images: [...(prev.images || []), ...newImages].slice(0, 3) }));
      }
    } catch (err) {
      setError(getApiErrorMessage(err, "Failed to upload images."));
      setTimeout(() => setError(null), 3000);
    } finally {
      setUploadingImages(false);
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
    if (!refundData.video) {
      setError("Please upload video evidence.");
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
      setTimeout(() => setError(null), 4000);
    } finally {
      setActionLoading(false);
    }
  };

  const tabs = [
    { key: "ALL", label: "ALL" },
    { key: "PENDING", label: "PENDING" },
    { key: "TO SHIP", label: "TO SHIP" },
    { key: "TO RECEIVE", label: "TO RECEIVE" },
    { key: "COMPLETED", label: "COMPLETED" },
    { key: "CANCELLED", label: "CANCELLED" },
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
    } catch (err) { console.error(err); }
  }, []);

  const fetchOrders = useCallback(async () => {
    try {
      setLoading(true);
      const response = await api.get("/orders/my-orders");
      setOrders(response.data);
      const userRatingsMap = {};
      response.data.forEach(order => {
        if (Array.isArray(order.reviews)) {
          order.reviews.forEach(r => { if (r.productId && r.rating) userRatingsMap[r.productId] = r; });
        }
      });
      setRatedProducts(userRatingsMap);
    } catch (error) { setOrders([]); } finally { setLoading(false); }
  }, []);

  const [cancellationModal, setCancellationModal] = useState({ isOpen: false, orderId: null, reason: "" });
  const handleCancelOrder = async () => {
    if (!cancellationModal.reason.trim()) {
      setError("Please provide a reason for cancellation.");
      return;
    }
    try {
      setActionLoading(true);
      await api.patch(`/orders/${cancellationModal.orderId}/cancel`, { reason: cancellationModal.reason });
      setSuccess("Cancellation request submitted.");
      setCancellationModal({ isOpen: false, orderId: null, reason: "" });
      fetchOrders();
    } catch (err) { setError(err.response?.data?.message || "Failed."); } finally { setActionLoading(false); }
  };

  const handleConfirmReceipt = async (orderId) => {
    if (!confirm("Confirm receipt?")) return;
    try {
      setActionLoading(true);
      await api.patch(`/orders/${orderId}/status`, { status: "Received by Buyer" });
      setNewlyCompletedOrderId(orderId);
      setShowFeedbackPrompt(true);
      fetchOrders();
    } catch (err) { setError("Failed."); } finally { setActionLoading(false); }
  };

  const { socket } = useSocket();
  useEffect(() => {
    fetchOrders();
    fetchRefunds();
    const storedCustomer = getStoredUserForRole("customer");
    setCurrentCustomerId(storedCustomer?.id ?? null);
  }, [fetchOrders, fetchRefunds]);

  const filteredOrders = orders.filter((order) => {
    const matchesTab = activeTab === "ALL" || getDisplayStatus(order.status) === activeTab;
    const matchesSearch = String(order.id).includes(searchQuery) || order.items?.some((i) => i.product?.name?.toLowerCase().includes(searchQuery.toLowerCase()));
    return matchesTab && matchesSearch;
  });

  const getParsedAddress = (address) => {
    if (!address) return {};
    if (typeof address === "string") { try { return JSON.parse(address); } catch (e) { return {}; } }
    return address;
  };

  return (
    <div className={`space-y-6 ${standalone ? 'max-w-[1024px] mx-auto px-4 md:px-0' : ''}`}>
      <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-4">
        <div className="space-y-2">
          <h2 className="font-serif text-lg font-bold text-[#2A2A2A]">Order Management</h2>
          <p className="text-xs text-(--muted)">Track and manage your heritage purchases</p>
        </div>
        <div className="flex items-center gap-3">
          <div className="relative group flex-1 sm:w-64">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-(--muted)" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search orders..."
              className="w-full pl-9 pr-4 py-2 bg-white border border-(--border) rounded-xl text-[11px] outline-none focus:border-(--rust) transition-all shadow-sm"
            />
          </div>
          <button onClick={fetchOrders} className="p-2 bg-white border border-(--border) rounded-xl hover:text-(--rust) transition-colors shadow-sm">
            <RefreshCw className="w-4 h-4 text-(--muted)" />
          </button>
        </div>
      </div>

      <AnimatePresence>
        {error && <motion.div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-bold flex items-center gap-2 mb-4"><XCircle className="w-4 h-4" /> {error}</motion.div>}
        {success && <motion.div className="p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-xs font-bold flex items-center gap-2 mb-4"><CheckCircle className="w-4 h-4" /> {success}</motion.div>}
      </AnimatePresence>

      <div className="flex gap-2 overflow-x-auto no-scrollbar pb-2 border-b border-(--border)">
        {tabs.map(({ key, label }) => (
          <button
            key={key}
            onClick={() => setActiveTab(key)}
            className={`px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all whitespace-nowrap border-b-2 ${activeTab === key ? "border-(--rust) text-(--rust)" : "border-transparent text-[#2A2A2A]/40 hover:text-[#2A2A2A]"}`}
          >
            {label}
          </button>
        ))}
      </div>

      <div className="space-y-4">
        {loading ? (
          <div className="py-20 text-center"><Loader2 className="w-8 h-8 animate-spin mx-auto text-(--muted) opacity-50 mb-2" /><p className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Syncing Orders...</p></div>
        ) : filteredOrders.length === 0 ? (
          <div className="py-16 text-center bg-white rounded-3xl border border-(--border) shadow-sm">
            <ShoppingBag className="w-12 h-12 mx-auto text-(--muted) opacity-20 mb-4" />
            <h3 className="text-base font-serif font-bold text-[#2A2A2A] mb-1">No Orders Found</h3>
            <p className="text-[10px] text-(--muted) italic">Your heritage shopping journey starts here.</p>
          </div>
        ) : (
          filteredOrders.map((order, idx) => {
            const displayStatus = getDisplayStatus(order.status);
            const sProps = getStatusStyle(order.status);
            return (
              <motion.div key={order.id} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="bg-white rounded-2xl border border-(--border) shadow-sm overflow-hidden">
                <div className="px-5 py-3 border-b border-(--border) flex items-center justify-between bg-stone-50/50">
                  <div className="text-[10px] font-bold text-stone-400">ORDER LB-OR-{order.id.toString().slice(-8).toUpperCase()}</div>
                  <div className={`px-2 py-0.5 rounded-full border text-[8px] font-black uppercase tracking-widest ${sProps.border} ${sProps.text} bg-white`}>{displayStatus}</div>
                </div>
                <div className="p-4 space-y-3">
                  {order.items?.map((item, i) => (
                    <div key={i} className="flex items-center gap-3">
                      <div className="w-12 h-12 bg-stone-100 rounded-lg overflow-hidden shrink-0 border border-stone-200 relative">
                        {item.product?.image ? (
                          <Image 
                            src={getProductImageSrc(item.product.image)} 
                            alt={item.product.name}
                            fill
                            className="object-cover"
                          />
                        ) : (
                          <Package className="w-4 h-4 text-stone-300 absolute inset-0 m-auto" />
                        )}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="text-xs font-bold text-[#2A2A2A] truncate">{item.product?.name}</div>
                        <div className="text-[9px] text-(--muted) uppercase tracking-tighter">Qty: {item.quantity} • Size: {item.size}</div>
                      </div>
                      <div className="text-sm font-black text-(--rust)">₱{parseFloat(item.price).toLocaleString()}</div>
                    </div>
                  ))}
                </div>
                <div className="px-5 py-3 border-t border-(--border) flex items-center justify-between bg-white">
                   <div className="text-[10px] text-(--muted)">Total: <span className="font-bold text-[#2A2A2A]">₱{order.totalAmount?.toLocaleString()}</span></div>
                   <button onClick={() => setSelectedOrder(order)} className="text-[10px] font-bold text-(--rust) hover:underline">View Details</button>
                </div>
              </motion.div>
            );
          })
        )}
      </div>

      {/* Order Details Modal */}
      <AnimatePresence>
        {selectedOrder && (
          <div className="fixed inset-0 z-200 flex items-center justify-center p-4 sm:p-6 overflow-hidden">
            <motion.div 
              initial={{ opacity: 0 }} 
              animate={{ opacity: 1 }} 
              exit={{ opacity: 0 }} 
              onClick={() => setSelectedOrder(null)} 
              className="absolute inset-0 bg-[#2A1E14]/60 backdrop-blur-sm" 
            />
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 20 }} 
              animate={{ opacity: 1, scale: 1, y: 0 }} 
              exit={{ opacity: 0, scale: 0.95, y: 20 }} 
              className="bg-white w-full max-w-2xl max-h-[88vh] rounded-3xl shadow-2xl relative z-10 overflow-hidden flex flex-col border border-white/20"
            >
              <div className="px-6 sm:px-8 py-6 border-b border-stone-100 flex items-center justify-between bg-[#FDFCFB]">
                <div>
                  <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#2A2A2A]">Order Details</h2>
                  <p className="text-[10px] font-bold text-stone-400 uppercase tracking-widest mt-1">
                    LB-OR-{selectedOrder.id.toString().slice(-8).toUpperCase()} • {new Date(selectedOrder.createdAt).toLocaleDateString()}
                  </p>
                </div>
                <button 
                  onClick={() => setSelectedOrder(null)} 
                  className="p-2.5 bg-white border border-stone-100 rounded-xl hover:text-[#C0422A] transition-all active:scale-90 shadow-sm"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto p-6 sm:p-8 space-y-8 artisan-scrollbar">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-3">
                    <div className="flex items-center gap-2 text-[10px] font-extrabold text-[#C0422A] uppercase tracking-widest opacity-80">
                      <MapPin className="w-3 h-3" /> SHIPPING ADDRESS
                    </div>
                    <div className="bg-[#FDFBF9] p-4 rounded-2xl border border-stone-100 shadow-sm">
                      <div className="font-bold text-[#2A2A2A] text-sm mb-1">
                        {getParsedAddress(selectedOrder.shippingAddress)?.name || "Name Missing"}
                      </div>
                      <div className="text-[10px] font-bold text-[#C0422A] mb-2">
                        {getParsedAddress(selectedOrder.shippingAddress)?.phone || "Phone Missing"}
                      </div>
                      <div className="text-xs text-stone-500 leading-relaxed">
                        {getParsedAddress(selectedOrder.shippingAddress)?.houseNo && <span>{getParsedAddress(selectedOrder.shippingAddress).houseNo} </span>}
                        {getParsedAddress(selectedOrder.shippingAddress)?.street && <span>{getParsedAddress(selectedOrder.shippingAddress).street}, </span>}
                        <br />
                        {getParsedAddress(selectedOrder.shippingAddress)?.barangay && <span>Brgy. {getParsedAddress(selectedOrder.shippingAddress).barangay}, </span>}
                        {getParsedAddress(selectedOrder.shippingAddress)?.city && <span>{getParsedAddress(selectedOrder.shippingAddress).city}, </span>}
                        <br />
                        {getParsedAddress(selectedOrder.shippingAddress)?.province && <span>{getParsedAddress(selectedOrder.shippingAddress).province}, </span>}
                        {getParsedAddress(selectedOrder.shippingAddress)?.postalCode && <span>{getParsedAddress(selectedOrder.shippingAddress).postalCode}</span>}
                      </div>
                    </div>
                  </div>

                  <div className="space-y-3">
                    <div className="flex items-center gap-2 text-[10px] font-extrabold text-[#C0422A] uppercase tracking-widest opacity-80">
                      <CreditCard className="w-3 h-3" /> PAYMENT METHOD
                    </div>
                    <div className="bg-[#FDFBF9] p-4 rounded-2xl border border-stone-100 shadow-sm">
                      <div className="font-bold text-[#2A2A2A] text-sm mb-1">{selectedOrder.paymentMethod || "GCash"}</div>
                      <div className="text-xs text-stone-500">
                        Reference: <span className="font-mono text-[#C0422A]">{selectedOrder.paymentReference || "N/A"}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <div className="flex items-center gap-2 text-[10px] font-extrabold text-[#C0422A] uppercase tracking-widest opacity-80">
                    <ShoppingBag className="w-3 h-3" /> PURCHASED ITEMS
                  </div>
                  <div className="space-y-3">
                    {selectedOrder.items?.map((item, i) => (
                      <div key={i} className="flex gap-4 p-4 bg-white border border-stone-100 rounded-2xl shadow-sm items-center">
                        <div className="w-14 h-14 bg-[#FDFBF9] rounded-xl border border-stone-100 relative shrink-0 overflow-hidden">
                          {item.product?.image ? (
                            <Image src={getProductImageSrc(item.product.image)} alt="" fill className="object-cover" />
                          ) : (
                            <Package className="w-5 h-5 text-stone-300 absolute inset-0 m-auto" />
                          )}
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="font-bold text-[#2A2A2A] text-sm truncate">{item.product?.name}</div>
                          <div className="text-[10px] text-stone-500 mt-0.5 uppercase tracking-wider">
                            Qty: {item.quantity} • Size: {item.size}
                          </div>
                        </div>
                        <div className="text-sm font-black text-[#C0422A]">₱{parseFloat(item.price).toLocaleString()}</div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              <div className="px-6 sm:px-8 py-6 bg-[#FDFCFB] border-t border-stone-100 flex items-center justify-between mt-auto">
                <span className="text-[10px] font-bold text-stone-400 uppercase tracking-widest">TOTAL AMOUNT</span>
                <div className="text-lg sm:text-xl font-black text-[#C0422A]">
                  ₱{selectedOrder.totalAmount?.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      <ConfirmationModal isOpen={showFeedbackPrompt} onClose={() => setShowFeedbackPrompt(false)} onConfirm={() => { setActiveTab("COMPLETED"); setShowFeedbackPrompt(false); }} title="Leave a Review" message="Share your experience with the artisan." confirmText="Review" cancelText="Later" type="info" />
    </div>
  );
}
