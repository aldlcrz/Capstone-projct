"use client";
import React, { useState, useEffect, useMemo, useCallback } from "react";
import SellerLayout from "@/components/SellerLayout";
import { Package, Clock, Eye, Search, Filter, X, MapPin, CreditCard, ShoppingBag, Video, Check } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { getProductImageSrc } from "@/lib/productImages";
import ConfirmationModal from "@/components/ConfirmationModal";

const STATUS_TABS = ["All", "Pending", "To Ship", "To Receive", "Completed", "Cancelled", "Refunds"];

const STATUS_MAPPING = {
  "Pending": ["Pending"],
  "To Ship": ["Processing"],
  "To Receive": ["Shipped", "Delivered"],
  "Completed": ["Completed"],
  "Cancelled": ["Cancelled", "Cancellation Pending"]
};

function formatAddress(address) {
  if (!address) return "Lumban, Laguna, Philippines";

  let addrObj = address;
  if (typeof address === "string") {
    try {
      // Handle potential JSON string from DB
      addrObj = JSON.parse(address);
    } catch (e) {
      return address;
    }
  }

  if (typeof addrObj === "object" && addrObj !== null) {
    const { street, houseNo, barangay, city, municipality, province, postalCode } = addrObj;

    // Attempt to build a multi-line format or return a clean string
    const parts = [];
    if (houseNo || street) parts.push(`${houseNo || ""} ${street || ""}`.trim());
    if (barangay) parts.push(barangay);
    if (city || municipality) parts.push(city || municipality);
    if (province) parts.push(province);

    return parts.filter(Boolean).join(", ") || "Lumban, Laguna, Philippines";
  }

  return "Lumban, Laguna, Philippines";
}

export default function SellerOrders() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("All");
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [updatingId, setUpdatingId] = useState(null);
  const [refunds, setRefunds] = useState([]);
  const [fetchingRefunds, setFetchingRefunds] = useState(false);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const [showCancelConfirm, setShowCancelConfirm] = useState(false);
  const [cancelTargetId, setCancelTargetId] = useState(null);
  const { socket } = useSocket();

  const fetchOrders = useCallback(async () => {
    try {
      const res = await api.get("/orders/seller");
      const fetchedOrders = res.data?.data || res.data || [];
      setOrders(fetchedOrders);
    } catch (err) {
      console.error("Failed to fetch orders:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchRefunds = useCallback(async () => {
    setFetchingRefunds(true);
    try {
      const res = await api.get("/refunds/seller");
      setRefunds(res.data || []);
    } catch (err) {
      console.error("Failed to fetch refunds:", err);
    } finally {
      setFetchingRefunds(false);
    }
  }, []);

  useEffect(() => {
    fetchOrders();
    fetchRefunds();
    if (!socket) return;

    const handleOrderUpdated = () => fetchOrders();
    const handleNewOrder = () => fetchOrders();
    const handleStatusUpdate = () => fetchOrders();

    socket.on('order_updated', handleOrderUpdated);
    socket.on('new_order', handleNewOrder);
    socket.on('order_status_update', handleStatusUpdate);

    return () => {
      socket.off('order_updated', handleOrderUpdated);
      socket.off('new_order', handleNewOrder);
      socket.off('order_status_update', handleStatusUpdate);
    };
  }, [socket, fetchOrders]);

  const filteredOrders = useMemo(() => {
    return orders.filter(order => {
      const orderStatus = order.status || "Pending";
      const matchesTab = activeTab === "All" ||
        STATUS_MAPPING[activeTab]?.some(s => s.toLowerCase() === orderStatus.toLowerCase());

      const searchLower = searchTerm.toLowerCase().trim();
      const displayId = `lb-or-${order.id.toString().slice(-8)}`.toLowerCase();
      const displayIdOld = `lb-${order.id.toString().slice(-8)}`.toLowerCase();

      const matchesSearch = !searchTerm ||
        order.id.toString().toLowerCase().includes(searchLower) ||
        displayId.includes(searchLower) ||
        displayIdOld.includes(searchLower) ||
        (order.customer?.name || "").toLowerCase().includes(searchLower) ||
        (order.shippingAddress?.customerName || "").toLowerCase().includes(searchLower);

      return matchesTab && matchesSearch;
    });
  }, [orders, activeTab, searchTerm]);

  if (loading) return (
    <div className="flex items-center justify-center h-[60vh]">
      <div className="w-12 h-12 border-4 border-[var(--rust)]/20 border-t-[var(--rust)] rounded-full animate-spin" />
    </div>
  );

  return (
    <SellerLayout>
      <div className="space-y-8 animate-fade-in pt-2 mb-20 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
        <header className="flex flex-col md:flex-row md:items-end justify-between gap-8">
          <div>
            <div className="flex items-center gap-3 mb-1 sm:mb-2">
              <div className="h-0.5 w-8 sm:w-12 bg-[var(--rust)] rounded-full" />
              <span className="text-[10px] sm:text-xs font-black text-[var(--rust)] tracking-[0.3em] uppercase">Activity History (Workshop Log)</span>
            </div>
            <h1 className="font-serif text-lg sm:text-xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
              ORDER <span className="font-serif italic text-[var(--rust)] font-normal ml-1 lowercase">(Registry)</span>
            </h1>
          </div>

          <div className="flex flex-wrap items-center gap-4">
            <div className="relative group">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)] group-focus-within:text-[var(--rust)] transition-colors" />
              <input
                type="text"
                placeholder="Search orders..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="bg-white border border-[var(--border)] rounded-2xl pl-12 pr-6 py-4 text-sm w-full md:w-[320px] outline-none focus:border-[var(--rust)] focus:ring-4 focus:ring-[var(--rust)]/5 transition-all shadow-sm"
              />
            </div>
          </div>
        </header>

        {/* Status Tabs (Grid Layout) */}
        <div className="border-b border-[var(--border)] mb-6">
          <div className="grid grid-cols-3 sm:flex sm:flex-row w-full text-center">
            {STATUS_TABS.map((tab) => {
              const isActive = activeTab === tab;
              return (
                <button
                  key={tab}
                  onClick={() => setActiveTab(tab)}
                  className={`py-4 px-2 sm:px-6 text-[10px] font-bold uppercase tracking-widest transition-all cursor-pointer border-b-2 sm:flex-1 ${isActive
                      ? 'bg-[var(--rust)]/10 text-[var(--rust)] border-[var(--rust)]'
                      : 'text-[var(--muted)] border-transparent hover:text-[var(--charcoal)] hover:bg-[var(--stone)]/5'
                    }`}
                >
                  {tab}
                </button>
              );
            })}
          </div>
        </div>

        {/* Table/Card Container */}
        <div className="bg-white rounded-[2.5rem] border border-[var(--border)] overflow-hidden shadow-xl shadow-stone-200/50">
          {/* Desktop Table */}
          <div className="hidden lg:block overflow-x-auto">
            <table className="w-full text-left border-collapse min-w-[1000px]">
              <thead>
                <tr className="bg-[var(--stone)]/30 border-b border-[var(--border)]">
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Order ID</th>
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Client Info</th>
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] text-center">Status Management</th>
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Value / Settlement</th>
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] text-right">Order Log</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[var(--border)]/50">
                <AnimatePresence mode="popLayout">
                  {filteredOrders.map((order, idx) => (
                    <OrderRow
                      key={order.id}
                      order={order}
                      index={idx}
                      onView={() => setSelectedOrder(order)}
                      isUpdating={updatingId === order.id}
                      onUpdateStatus={async (newStatus) => {
                        setUpdatingId(order.id);
                        try {
                          await api.put(`/orders/${order.id}/status`, { status: newStatus });
                          fetchOrders();
                        } catch (err) {
                          console.error("Failed to update status:", err.response?.data?.message || err.message);
                        } finally {
                          setUpdatingId(null);
                        }
                      }}
                    />
                  ))}
                </AnimatePresence>
                {filteredOrders.length === 0 && (
                  <tr>
                    <td colSpan="5" className="px-8 py-20 text-center text-[var(--muted)] italic font-medium">
                      No orders identified in this registry sector.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Refunds Table (Desktop) */}
          {activeTab === "Refunds" && (
            <div className="hidden lg:block overflow-x-auto">
              <table className="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                  <tr className="bg-[var(--stone)]/30 border-b border-[var(--border)]">
                    <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Claim ID</th>
                    <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Item & Reason</th>
                    <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] text-center">Video Proof</th>
                    <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Resolution</th>
                    <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[var(--border)]/50">
                  {refunds.map((refund, idx) => (
                    <RefundRow
                      key={refund.id}
                      refund={refund}
                      index={idx}
                      onUpdate={async (status, comment) => {
                        try {
                          await api.put(`/refunds/${refund.id}/status`, { status, sellerComment: comment });
                          fetchRefunds();
                        } catch (err) {
                          alert("Failed to update refund status");
                        }
                      }}
                    />
                  ))}
                  {refunds.length === 0 && (
                    <tr>
                      <td colSpan="5" className="px-8 py-20 text-center text-[var(--muted)] italic font-medium">
                        No refund claims submitted yet.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          )}

          {/* Mobile Cards */}
          <div className="lg:hidden flex flex-col divide-y divide-[var(--border)]">
            <AnimatePresence mode="popLayout">
              {filteredOrders.map((order, idx) => (
                <MobileOrderCard
                  key={order.id}
                  order={order}
                  index={idx}
                  onView={() => setSelectedOrder(order)}
                  isUpdating={updatingId === order.id}
                  onUpdateStatus={async (newStatus) => {
                    setUpdatingId(order.id);
                    try {
                      await api.put(`/orders/${order.id}/status`, { status: newStatus });
                      fetchOrders();
                    } catch (err) {
                      console.error("Failed to update status:", err.response?.data?.message || err.message);
                    } finally {
                      setUpdatingId(null);
                    }
                  }}
                />
              ))}
            </AnimatePresence>
            {filteredOrders.length === 0 && (
              <div className="px-8 py-20 text-center text-[var(--muted)] italic font-medium">
                No orders identified in this registry sector.
              </div>
            )}
            
            {activeTab === "Refunds" && refunds.map((refund, idx) => (
              <MobileRefundCard 
                key={refund.id} 
                refund={refund} 
                index={idx}
                onUpdate={async (status, comment) => {
                  try {
                    await api.put(`/refunds/${refund.id}/status`, { status, sellerComment: comment });
                    fetchRefunds();
                  } catch (err) {
                    alert("Failed to update refund status");
                  }
                }}
              />
            ))}
            {activeTab === "Refunds" && refunds.length === 0 && (
              <div className="px-8 py-20 text-center text-[var(--muted)] italic font-medium">
                No refund claims submitted yet.
              </div>
            )}
          </div>
        </div>

        <AnimatePresence>
          {selectedOrder && (
            <OrderModal
              order={selectedOrder}
              onClose={() => setSelectedOrder(null)}
              isUpdating={updatingId === selectedOrder.id}
              onUpdateStatus={async (newStatus) => {
                setUpdatingId(selectedOrder.id);
                try {
                  await api.put(`/orders/${selectedOrder.id}/status`, { status: newStatus });
                  const res = await api.get("/orders/seller");
                  setOrders(res.data);
                  setSelectedOrder(prev => ({ ...prev, status: newStatus }));
                } catch (err) {
                  console.error("Failed to update status:", err.response?.data?.message || err.message);
                } finally {
                  setUpdatingId(null);
                }
              }}
              onCancelClick={(id) => {
                setCancelTargetId(id);
                setShowCancelConfirm(true);
              }}
            />
          )}
        </AnimatePresence>

        <ConfirmationModal
          isOpen={showCancelConfirm}
          onClose={() => setShowCancelConfirm(false)}
          onConfirm={() => {
            if (cancelTargetId) {
              api.put(`/orders/${cancelTargetId}/status`, { status: "Cancelled" })
                .then(() => fetchOrders())
                .catch(err => console.error(err));
            }
          }}
          title="Cancel this order?"
          message="This will notify the customer and halt the shipping process. This action is irreversible."
          confirmText="Yes, Cancel Order"
          cancelText="No, Keep it"
          type="danger"
        />
      </div>
    </SellerLayout>
  );
}

function OrderRow({ order, index, onView, onUpdateStatus, isUpdating }) {
  const currentStatus = order.status || "Pending";
  const STATUS_CYCLE = ["Pending", "Processing", "Shipped", "Delivered", "Completed"];
  const currentIdx = STATUS_CYCLE.findIndex(s => s.toLowerCase() === currentStatus.toLowerCase());
  const nextStatus = currentIdx !== -1 && currentIdx < STATUS_CYCLE.length - 1 ? STATUS_CYCLE[currentIdx + 1] : null;

  const currentStepIndex = currentIdx;

  const getDotColor = (dotIdx) => {
    if (dotIdx < currentStepIndex) return "bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.3)]";
    if (dotIdx === currentStepIndex) return "bg-[var(--rust)] shadow-[0_0_8px_rgba(192,66,42,0.3)]";
    return "bg-[var(--border)]";
  };

  return (
    <motion.tr
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.05 }}
      className="group hover:bg-[var(--stone)]/5 transition-colors"
    >
      <td className="px-8 py-6 align-top">
        <div className="font-serif font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors mb-2 tracking-tighter">
          #LB-{order.id.toString().slice(-8).toUpperCase()}
        </div>
        <div className="flex items-center gap-1.5 opacity-60">
          <Clock className="w-3 h-3 text-[var(--muted)]" />
          <span className="text-[10px] font-bold text-[var(--muted)]">{order.items?.length || 0} Pieces Ordered</span>
        </div>
        <div className="flex gap-1 mt-3">
          {[...Array(6)].map((_, i) => (
            <div key={i} className={`w-1.5 h-1.5 rounded-full ${getDotColor(i)} transition-all duration-500`} />
          ))}
        </div>
      </td>

      <td className="px-8 py-6 align-top">
        <div className="flex items-center gap-4">
          <div className="w-10 h-10 rounded-full bg-[var(--bark)] text-white flex items-center justify-center font-bold shadow-md">
            {(order.shippingAddress?.customerName || order.customer?.name)?.[0] || "C"}
          </div>
          <div className="min-w-0">
            <div className="font-bold text-xs text-[var(--charcoal)] truncate tracking-tight uppercase">{order.shippingAddress?.customerName || order.customer?.name || "Customer"}</div>
            <div className="text-[10px] text-[var(--muted)] font-medium italic truncate max-w-[200px]">
              {formatAddress(order.shippingAddress)}
            </div>
          </div>
        </div>
      </td>

      <td className="px-8 py-6 align-top text-center">
        {currentStatus === "Cancellation Pending" ? (
          <div className="flex flex-col items-center gap-1">
            <span className="px-3 py-1 bg-orange-50 text-orange-700 border border-orange-100 rounded-full text-[9px] font-black tracking-widest uppercase">
              Req. Cancellation
            </span>
          </div>
        ) : (
          <button
            onClick={() => nextStatus && !isUpdating && onUpdateStatus(nextStatus)}
            disabled={!nextStatus || isUpdating}
            className={`px-4 py-1.5 rounded-lg text-[10px] font-black tracking-[0.1em] shadow-sm transition-all border ${isUpdating ? 'animate-pulse opacity-70 cursor-wait' :
              nextStatus ? 'cursor-pointer hover:scale-105 active:scale-95 hover:shadow-md' : 'cursor-default'
              } ${currentStatus.toLowerCase() === 'delivered' || currentStatus.toLowerCase() === 'completed'
                ? 'bg-green-50 text-green-700 border-green-100'
                : 'bg-amber-50 text-amber-700 border-amber-100'
              }`}
          >
            {isUpdating ? "UPDATING..." : currentStatus.toUpperCase()}
          </button>
        )}
      </td>

      <td className="px-8 py-6 align-top">
        <div className="font-bold text-xs text-[var(--charcoal)]">₱{(order.totalAmount || order.totalPrice)?.toLocaleString()}</div>
        <div className="text-[11px] font-black text-[var(--muted)] uppercase tracking-widest mt-1">
          {order.paymentMethod?.toUpperCase()}
        </div>
      </td>

      <td className="px-8 py-6 align-top text-right">
        <div className="flex items-center justify-end gap-2">
          {currentStatus === 'Cancellation Pending' && (
            <div className="flex gap-1.5">
              <button
                onClick={async (e) => {
                  e.stopPropagation();
                  if (confirm("Approve this cancellation request? This will restock the items and cancel the order.")) {
                    try { await api.patch(`/orders/${order.id}/approve-cancellation`); window.location.reload(); } catch (err) { alert(err.response?.data?.message || "Failed to approve"); }
                  }
                }}
                className="p-2 bg-green-50 text-green-600 hover:bg-green-500 hover:text-white rounded-xl transition-all shadow-sm group/btn"
                title="Approve Cancellation"
              >
                <Check className="w-4 h-4" />
              </button>
              <button
                onClick={async (e) => {
                  e.stopPropagation();
                  if (confirm("Reject this cancellation request? The order will return to Processing.")) {
                    try { await api.patch(`/orders/${order.id}/reject-cancellation`); window.location.reload(); } catch (err) { alert(err.response?.data?.message || "Failed to reject"); }
                  }
                }}
                className="p-2 bg-orange-50 text-orange-600 hover:bg-orange-500 hover:text-white rounded-xl transition-all shadow-sm group/btn"
                title="Reject Cancellation"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          )}
          {(currentStatus === 'Pending' || currentStatus === 'Processing') && (
            <button
              onClick={(e) => {
                e.stopPropagation();
                if (window.confirm("Are you sure you want to cancel this order?")) {
                  onUpdateStatus("Cancelled");
                }
              }}
              disabled={isUpdating}
              title="Cancel Order"
              className="p-2.5 bg-red-50 text-red-400 hover:text-white hover:bg-red-500 rounded-xl transition-all shadow-sm group/btn disabled:opacity-50"
            >
              <X className="w-4 h-4 transition-transform group-hover/btn:scale-110" />
            </button>
          )}
          <button
            onClick={onView}
            className="p-2.5 bg-[var(--cream)] text-[var(--muted)] hover:text-white hover:bg-[var(--rust)] rounded-xl transition-all shadow-sm group/btn"
          >
            <Eye className="w-4 h-4 transition-transform group-hover/btn:scale-110" />
          </button>
        </div>
      </td>
    </motion.tr>
  );
}

function OrderModal({ order, onClose, onUpdateStatus, onCancelClick, isUpdating }) {
  const steps = ["Pending", "Processing", "Shipped", "Delivered", "Completed"];
  const currentStep = steps.findIndex(s => s.toLowerCase() === order.status?.toLowerCase());

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 lg:p-6">
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
        className="absolute inset-0 bg-[var(--charcoal)]/80 backdrop-blur-md"
      />
      <motion.div
        initial={{ opacity: 0, scale: 0.95, y: 30 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 30 }}
        className="relative bg-white w-full max-w-3xl max-h-[90vh] overflow-hidden rounded-2xl sm:rounded-3xl shadow-2xl flex flex-col border border-[var(--border)]"
      >
        {/* Modal Header */}
        <div className="p-4 sm:p-6 border-b border-[var(--border)] flex items-start sm:items-center justify-between bg-[var(--warm-white)] text-[var(--charcoal)] relative overflow-hidden shrink-0">
          <div className="absolute top-0 left-0 w-full h-1 bg-[var(--rust)]" />
          <div className="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between w-full pr-10 sm:pr-12 gap-3">
            <div>
              <div className="flex items-center gap-2 mb-1.5">
                <div className="w-5 sm:w-6 h-[2px] bg-[var(--rust)]" />
                <span className="text-[8px] sm:text-[9px] font-black text-[var(--rust)] tracking-[0.3em] uppercase">Order Details</span>
              </div>
              <h2 className="font-serif text-lg sm:text-xl font-bold tracking-tighter">
                Order <span className="font-serif italic font-normal text-[var(--rust)] block sm:inline">#LB-OR-{order.id.toString().slice(-8).toUpperCase()}</span>
              </h2>
            </div>

            <div className="flex flex-wrap items-center gap-2">
              <div className={`px-3 sm:px-4 py-1 sm:py-1.5 rounded-full border text-[8px] sm:text-[9px] font-black tracking-[0.2em] shadow-sm ${order.status === 'Cancelled' ? 'bg-red-50 border-red-200 text-red-600' :
                  order.status === 'Completed' || order.status === 'Delivered' ? 'bg-green-50 border-green-200 text-green-600' :
                    'bg-orange-50 border-orange-200 text-orange-600'
                }`}>
                {order.status?.toUpperCase()}
              </div>
              {(order.status === 'Pending' || order.status === 'Processing') && (
                <button
                  onClick={() => onCancelClick(order.id)}
                  disabled={isUpdating}
                  className="px-3 sm:px-4 py-1 sm:py-1.5 rounded-full border border-red-200 bg-white text-red-600 text-[8px] sm:text-[9px] font-black tracking-[0.2em] hover:bg-red-50 transition-all shadow-sm disabled:opacity-50"
                >
                  {isUpdating ? 'CANCELLING...' : 'CANCEL'}
                </button>
              )}
            </div>
          </div>
          <button onClick={onClose} className="absolute top-3 right-3 sm:top-5 sm:right-5 p-2 sm:p-2.5 bg-white hover:bg-[var(--cream)] rounded-full transition-all border border-[var(--border)] group active:scale-90 z-20">
            <X className="w-4 h-4 sm:w-5 sm:h-5 text-[var(--muted)] group-hover:text-[var(--rust)] transition-colors" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-4 sm:p-6 custom-scrollbar">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
            {/* Left Column: Client & Payment */}
            <div className="space-y-6 sm:space-y-8">
              <section>
                <div className="flex items-center gap-2 mb-3">
                  <div className="w-1.5 h-1.5 rounded-full bg-[var(--rust)]" />
                  <span className="text-[8px] sm:text-[9px] font-black text-[var(--rust)] tracking-widest uppercase">Client Profile</span>
                </div>
                <div className="artisan-card space-y-4 !p-4 sm:!p-5">
                  <div className="flex items-center gap-3 sm:gap-4">
                    <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[var(--bark)] flex items-center justify-center text-white font-serif text-lg sm:text-xl font-bold flex-shrink-0">
                      {(order.shippingAddress?.customerName || order.customer?.name)?.[0] || "C"}
                    </div>
                    <div className="min-w-0">
                      <div className="font-bold text-[var(--charcoal)] uppercase tracking-tight text-sm sm:text-base truncate">{order.shippingAddress?.customerName || order.customer?.name || "Customer"}</div>
                      <div className="text-[10px] sm:text-[11px] text-[var(--muted)] italic truncate">{order.customer?.email || order.shippingAddress?.contactNumber}</div>
                    </div>
                  </div>
                  <div className="pt-4 border-t border-[var(--border)]/50">
                    <div className="text-[8px] sm:text-[9px] font-black text-[var(--muted)] uppercase mb-2 opacity-60 tracking-widest">Shipping Logistics</div>
                    <div className="bg-[var(--cream)]/50 p-3 sm:p-4 rounded-xl border border-[var(--border)]/30">
                      <p className="text-[11px] sm:text-xs italic text-[var(--charcoal)] leading-relaxed font-medium">
                        {formatAddress(order.shippingAddress)}
                      </p>
                    </div>
                  </div>
                </div>
              </section>

              <section>
                <div className="flex items-center gap-2 mb-3">
                  <div className="w-1.5 h-1.5 rounded-full bg-[var(--rust)]" />
                  <span className="text-[8px] sm:text-[9px] font-black text-[var(--rust)] tracking-widest uppercase">Settlement</span>
                </div>
                <div className="artisan-card grid grid-cols-2 gap-4 !p-4 sm:!p-5">
                  <div>
                    <div className="text-[8px] sm:text-[9px] font-black text-[var(--muted)] uppercase mb-1.5 opacity-60 tracking-widest">Method</div>
                    <div className="flex items-center gap-2">
                      <CreditCard className="w-3.5 h-3.5 text-[var(--rust)] shrink-0" />
                      <div className="text-[11px] sm:text-xs font-bold text-[var(--charcoal)] uppercase tracking-tight">{order.paymentMethod}</div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-[8px] sm:text-[9px] font-black text-[var(--muted)] uppercase mb-1.5 opacity-60 tracking-widest">Status</div>
                    <span className={`inline-block px-2.5 sm:px-3 py-1 rounded-full text-[8px] sm:text-[9px] font-black border uppercase tracking-wider ${order.status === 'Cancelled' ? 'bg-red-50 text-red-700 border-red-100' : 'bg-green-50 text-green-700 border-green-100'
                      }`}>
                      {order.status === 'Cancelled' ? 'CANCELLED' : 'CAPTURED'}
                    </span>
                  </div>
                </div>

                {order.status === "Cancellation Pending" && order.cancellationReason && (
                  <div className="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-2xl">
                    <div className="text-[8px] sm:text-[9px] font-black text-orange-700 uppercase tracking-widest mb-1.5 flex items-center gap-2">
                      <Clock className="w-3 h-3" /> Cancellation Request
                    </div>
                    <p className="text-[11px] sm:text-xs text-orange-800 italic leading-relaxed">
                      "{order.cancellationReason}"
                    </p>
                    <div className="flex gap-2 mt-4">
                      <button
                        onClick={async () => {
                          if (confirm("Approve this cancellation request?")) {
                            try {
                              await api.patch(`/orders/${order.id}/approve-cancellation`);
                              onClose();
                              window.location.reload();
                            } catch (err) {
                              alert(err.response?.data?.message || "Failed to approve");
                            }
                          }
                        }}
                        className="flex-1 py-2 bg-green-600 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-green-700 transition-colors shadow-sm"
                      >
                        Approve
                      </button>
                      <button
                        onClick={async () => {
                          if (confirm("Reject this cancellation request?")) {
                            try {
                              await api.patch(`/orders/${order.id}/reject-cancellation`);
                              onClose();
                              window.location.reload();
                            } catch (err) {
                              alert(err.response?.data?.message || "Failed to reject");
                            }
                          }
                        }}
                        className="flex-1 py-2 bg-orange-600 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-orange-700 transition-colors shadow-sm"
                      >
                        Reject
                      </button>
                    </div>
                  </div>
                )}
              </section>
            </div>

            {/* Right Column: Order Items */}
            <section className="flex flex-col h-full mt-2 md:mt-0">
              <div className="flex items-center gap-2 mb-3">
                <div className="w-1.5 h-1.5 rounded-full bg-[var(--rust)]" />
                <span className="text-[8px] sm:text-[9px] font-black text-[var(--rust)] tracking-widest uppercase">Order Items</span>
              </div>
              <div className="artisan-card flex-1 flex flex-col min-h-full !p-4 sm:!p-5">
                <div className="flex-1 space-y-3 sm:space-y-4 mb-5 sm:mb-6">
                  {(order.items || order.orderItems || []).map((item, i) => (
                    <div key={i} className="flex gap-3 sm:gap-4 p-2 sm:p-3 hover:bg-[var(--cream)]/30 rounded-xl transition-all border border-transparent hover:border-[var(--border)] group">
                      <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-lg overflow-hidden bg-white border border-[var(--border)] shadow-sm shrink-0">
                        <img
                          src={getProductImageSrc(item.product?.image?.[0]?.url || item.product?.image?.[0] || item.product?.image)}
                          alt=""
                          className="w-full h-full object-cover transition-transform group-hover:scale-110"
                        />
                      </div>
                      <div className="flex-1 min-w-0 flex flex-col justify-center">
                        <div className="font-bold text-[11px] sm:text-xs truncate text-[var(--charcoal)] uppercase tracking-tight mb-0.5">{item.product?.name}</div>
                        <div className="text-[9px] sm:text-[10px] text-[var(--muted)] font-medium">Qty: {item.quantity} × ₱{item.price?.toLocaleString()}</div>
                        {item.variant && (
                          <div className="mt-1 inline-block self-start px-1.5 py-0.5 bg-white rounded text-[7px] font-black text-[var(--muted)] border border-[var(--border)] uppercase tracking-widest">
                            {item.variant}
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>

                <div className="pt-4 sm:pt-5 border-t border-[var(--border)]">
                  <div className="bg-[var(--warm-white)] p-4 sm:p-5 rounded-xl border-2 border-[var(--rust)] text-[var(--charcoal)] shadow-md relative overflow-hidden group/total">
                    <div className="absolute top-0 right-0 w-20 h-20 sm:w-24 sm:h-24 bg-[var(--rust)]/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-xl group-hover/total:bg-[var(--rust)]/10 transition-all" />
                    <div className="flex justify-between items-end relative z-10">
                      <div>
                        <div className="text-[8px] sm:text-[9px] font-black text-[var(--rust)] uppercase tracking-[0.2em] mb-1">Grand Total</div>
                        <div className="text-[7px] sm:text-[8px] text-[var(--muted)]/60 uppercase tracking-widest italic">Heritage Settlement</div>
                      </div>
                      <div className="text-right">
                        <div className="text-sm sm:text-base font-bold tracking-tight text-[var(--rust)]">
                          ₱{(order.totalAmount || order.totalPrice)?.toLocaleString()}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>
      </motion.div>
    </div>
  );
}
function MobileOrderCard({ order, index, onView, onUpdateStatus, isUpdating }) {
  const currentStatus = order.status || "Pending";
  const STATUS_CYCLE = ["Pending", "Processing", "Shipped", "Delivered", "Completed"];
  const currentIdx = STATUS_CYCLE.findIndex(s => s.toLowerCase() === currentStatus.toLowerCase());
  const nextStatus = currentIdx !== -1 && currentIdx < STATUS_CYCLE.length - 1 ? STATUS_CYCLE[currentIdx + 1] : null;
  const isCancelled = currentStatus.toLowerCase() === 'cancelled';
  const isCompleted = currentStatus.toLowerCase() === 'completed' || currentStatus.toLowerCase() === 'delivered';

  const getDotColor = (dotIdx) => {
    if (isCancelled) return dotIdx === 0 ? "bg-red-400" : "bg-[var(--border)]";
    if (dotIdx < currentIdx) return "bg-green-500";
    if (dotIdx === currentIdx) return "bg-[var(--rust)]";
    return "bg-[var(--border)]";
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.05 }}
      className="p-4 sm:p-5 transition-colors"
    >
      {/* Row 1: Order ID + Status Badge */}
      <div className="flex items-start justify-between gap-3 mb-3">
        <div>
          <div className="font-serif font-bold text-[var(--charcoal)] tracking-tighter text-sm leading-none">
            #LB-{order.id.toString().slice(-8).toUpperCase()}
          </div>
          <div className="flex items-center gap-1.5 mt-1.5 opacity-60">
            <Clock className="w-3 h-3 text-[var(--muted)]" />
            <span className="text-[9px] font-bold text-[var(--muted)] uppercase tracking-wider">
              {order.items?.length || 0} {order.items?.length === 1 ? 'item' : 'items'}
            </span>
          </div>
          {/* Progress dots */}
          <div className="flex gap-1 mt-2">
            {[...Array(6)].map((_, i) => (
              <div key={i} className={`w-1.5 h-1.5 rounded-full transition-all duration-500 ${getDotColor(i)}`} />
            ))}
          </div>
        </div>
        {currentStatus === "Cancellation Pending" ? (
          <span className="px-3 py-1 bg-orange-50 text-orange-700 border border-orange-100 rounded-lg text-[9px] font-black tracking-widest uppercase">
            Req. Cancellation
          </span>
        ) : (
          <button
            onClick={() => nextStatus && !isUpdating && onUpdateStatus(nextStatus)}
            disabled={!nextStatus || isUpdating || isCancelled}
            className={`px-3 py-1.5 rounded-lg text-[9px] font-black tracking-[0.08em] border flex-shrink-0 transition-all ${isUpdating ? 'animate-pulse opacity-70 cursor-wait' : nextStatus && !isCancelled ? 'cursor-pointer hover:scale-105 active:scale-95' : 'cursor-default'
              } ${isCancelled ? 'bg-red-50 text-red-700 border-red-100' :
                isCompleted ? 'bg-green-50 text-green-700 border-green-100' :
                  'bg-amber-50 text-amber-700 border-amber-100'
              }`}
          >
            {isUpdating ? '...' : currentStatus.toUpperCase()}
          </button>
        )}
      </div>

      {/* Row 2: Customer info + Value + Actions */}
      <div className="bg-[var(--cream)]/60 border border-[var(--border)]/50 rounded-2xl p-3 flex items-center gap-3">
        {/* Avatar */}
        <div className="w-9 h-9 rounded-full bg-[var(--bark)] text-white flex items-center justify-center text-sm font-bold flex-shrink-0 shadow-sm">
          {(order.shippingAddress?.customerName || order.customer?.name)?.[0]?.toUpperCase() || "C"}
        </div>
        {/* Customer details */}
        <div className="flex-1 min-w-0">
          <div className="text-[10px] font-bold text-[var(--charcoal)] uppercase truncate tracking-tight leading-none">
            {order.shippingAddress?.customerName || order.customer?.name || "Customer"}
          </div>
          <div className="text-[9px] text-[var(--muted)] italic truncate mt-0.5">
            {formatAddress(order.shippingAddress)}
          </div>
        </div>
        {/* Value + Payment */}
        <div className="text-right flex-shrink-0">
          <div className="text-xs font-bold text-[var(--charcoal)] leading-none">
            ₱{(order.totalAmount || order.totalPrice)?.toLocaleString()}
          </div>
          <div className="text-[8px] font-bold text-[var(--muted)] uppercase tracking-widest mt-0.5 opacity-60">
            {order.paymentMethod}
          </div>
        </div>
        {/* View button */}
        <button
          onClick={onView}
          className="p-2 bg-white border border-[var(--border)] text-[var(--muted)] hover:text-[var(--rust)] hover:border-[var(--rust)] rounded-xl transition-all flex-shrink-0 shadow-sm"
        >
          <Eye className="w-4 h-4" />
        </button>
      </div>
    </motion.div>
  );
}
function RefundRow({ refund, index, onUpdate }) {
  return (
    <motion.tr
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.05 }}
      className="group hover:bg-[var(--stone)]/5 transition-colors"
    >
      <td className="px-8 py-6 align-top">
        <div className="font-serif font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors mb-1 tracking-tighter uppercase">
          #RF-{refund.id.toString().slice(-6).toUpperCase()}
        </div>
        <div className="text-[10px] text-[var(--muted)] font-medium">Order: #LB-{refund.orderId?.toString().slice(-8).toUpperCase()}</div>
        <div className="text-[10px] text-[var(--muted)] mt-1">{new Date(refund.createdAt).toLocaleDateString()}</div>
      </td>
      <td className="px-8 py-6 align-top">
        <div className="flex items-center gap-3 mb-2">
          <div className="w-8 h-8 rounded bg-stone-100 border border-stone-200 overflow-hidden shrink-0 p-1">
            <img src={getProductImageSrc(refund.OrderItem?.product?.image)} alt="" className="w-full h-full object-cover" />
          </div>
          <div className="min-w-0">
            <div className="font-bold text-xs text-[var(--charcoal)] truncate uppercase tracking-tight">{refund.OrderItem?.product?.name}</div>
            <div className="text-[9px] text-[var(--muted)]">Size: {refund.OrderItem?.size} • Qty: {refund.OrderItem?.quantity}</div>
          </div>
        </div>
        <div className="inline-block px-2 py-0.5 bg-red-50 text-red-600 rounded text-[9px] font-black uppercase tracking-widest border border-red-100">{refund.reason}</div>
        {refund.message && <div className="mt-2 text-[11px] text-[var(--charcoal)] leading-relaxed italic bg-stone-50 p-2 rounded-lg border border-stone-100">"{refund.message}"</div>}
      </td>
      <td className="px-8 py-6 align-top text-center">
        <a 
          href={refund.videoProof} 
          target="_blank" 
          rel="noopener noreferrer"
          className="inline-flex items-center gap-2 px-4 py-2 bg-[var(--rust)]/5 hover:bg-[var(--rust)]/10 text-[var(--rust)] rounded-xl border border-[var(--rust)]/20 transition-all group/v"
        >
          <Video className="w-4 h-4 group-hover/v:scale-110 transition-transform" />
          <span className="text-[10px] font-bold uppercase tracking-widest">Review Proof</span>
        </a>
      </td>
      <td className="px-8 py-6 align-top">
        <div className={`inline-block px-3 py-1 rounded-full text-[9px] font-black tracking-widest border uppercase ${
          refund.status === 'Approved' ? 'bg-green-50 text-green-700 border-green-200' :
          refund.status === 'Rejected' ? 'bg-red-50 text-red-700 border-red-200' :
          'bg-amber-50 text-amber-700 border-amber-200'
        }`}>
          {refund.status}
        </div>
        {refund.sellerComment && <div className="mt-2 text-[10px] text-[var(--muted)] italic">Note: {refund.sellerComment}</div>}
      </td>
      <td className="px-8 py-6 align-top text-right">
        {refund.status === 'Pending' && (
          <div className="flex justify-end gap-2">
            <button 
              onClick={() => {
                const comment = prompt("Reason for rejection (optional):");
                onUpdate('Rejected', comment);
              }}
              className="px-3 py-1.5 bg-white border border-red-200 text-red-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-50 transition-all shadow-sm"
            >
              Reject
            </button>
            <button 
              onClick={() => {
                if(confirm("Approve this refund request? Funds will be scheduled for return.")) {
                  onUpdate('Approved', 'Refund approved by artisan.');
                }
              }}
              className="px-3 py-1.5 bg-green-600 text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-700 transition-all shadow-sm"
            >
              Approve
            </button>
          </div>
        )}
      </td>
    </motion.tr>
  );
}

function MobileRefundCard({ refund, index, onUpdate }) {
  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.05 }}
      className="p-5 space-y-4"
    >
      <div className="flex justify-between items-start">
        <div>
          <div className="font-serif font-bold text-sm text-[var(--charcoal)] tracking-tighter uppercase">#RF-{refund.id.toString().slice(-6).toUpperCase()}</div>
          <div className="text-[10px] text-[var(--muted)] mt-0.5">Order: #LB-{refund.orderId?.toString().slice(-8).toUpperCase()}</div>
        </div>
        <div className={`px-2.5 py-1 rounded-full text-[8px] font-black tracking-widest border uppercase ${
          refund.status === 'Approved' ? 'bg-green-50 text-green-700 border-green-200' :
          refund.status === 'Rejected' ? 'bg-red-50 text-red-700 border-red-200' :
          'bg-amber-50 text-amber-700 border-amber-200'
        }`}>
          {refund.status}
        </div>
      </div>

      <div className="bg-stone-50 rounded-2xl p-3 border border-stone-100 flex items-center gap-3">
        <div className="w-10 h-10 rounded-lg bg-white border border-stone-200 p-1 shrink-0">
          <img src={getProductImageSrc(refund.OrderItem?.product?.image)} alt="" className="w-full h-full object-cover" />
        </div>
        <div className="min-w-0">
          <div className="text-xs font-bold text-[var(--charcoal)] uppercase truncate tracking-tight">{refund.OrderItem?.product?.name}</div>
          <div className="text-[9px] text-[var(--muted)]">Qty: {refund.OrderItem?.quantity} • {refund.reason}</div>
        </div>
      </div>

      {refund.message && (
        <div className="text-[11px] text-[var(--muted)] italic bg-stone-50 p-3 rounded-xl border border-stone-100">
          "{refund.message}"
        </div>
      )}

      <a 
        href={refund.videoProof} 
        target="_blank" 
        rel="noopener noreferrer"
        className="flex items-center justify-center gap-2 w-full py-3 bg-[var(--rust)]/5 text-[var(--rust)] rounded-xl border border-[var(--rust)]/20 font-bold text-[10px] uppercase tracking-widest"
      >
        <Video className="w-4 h-4" /> Review Video Proof
      </a>

      {refund.status === 'Pending' && (
        <div className="flex gap-2">
          <button 
            onClick={() => {
              const comment = prompt("Reason for rejection (optional):");
              onUpdate('Rejected', comment);
            }}
            className="flex-1 py-3 bg-white border border-red-200 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-widest"
          >
            Reject
          </button>
          <button 
            onClick={() => {
              if(confirm("Approve this refund request?")) {
                onUpdate('Approved', 'Refund approved by artisan.');
              }
            }}
            className="flex-1 py-3 bg-green-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest shadow-md"
          >
            Approve
          </button>
        </div>
      )}
    </motion.div>
  );
}
