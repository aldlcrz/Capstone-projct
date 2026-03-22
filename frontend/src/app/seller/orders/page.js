"use client";
import React, { useState, useEffect, useMemo } from "react";
import SellerLayout from "@/components/SellerLayout";
import { Package, Clock, Eye, Search, Filter, X, MapPin, CreditCard, ShoppingBag } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

const STATUS_TABS = ["All", "Pending", "Processing", "Shipped", "Delivered", "Completed", "Cancelled"];

const STATUS_MAPPING = {
  "Pending": ["Pending"],
  "Processing": ["Processing"],
  "Shipped": ["Shipped"],
  "Delivered": ["Delivered"],
  "Completed": ["Completed"],
  "Cancelled": ["Cancelled"]
};

function formatAddress(address) {
  if (!address) return "Lumban, Laguna, Philippines";
  if (typeof address === 'string') return address;
  if (typeof address === 'object') {
    const { street, city, province, postalCode } = address;
    const parts = [street, city, province, postalCode].filter(Boolean);
    return parts.length > 0 ? parts.join(", ") : "Lumban, Laguna, Philippines";
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

  const { socket } = useSocket();

  const fetchOrders = async () => {
    try {
      const res = await api.get("/orders/seller");
      setOrders(res.data || []);
    } catch (err) {
      console.error("Failed to fetch orders:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrders();

    if (socket) {
      socket.on('order_updated', fetchOrders);
      socket.on('new_order', fetchOrders);
      socket.on('order_status_update', fetchOrders);
    }

    return () => {
      if (socket) {
        socket.off('order_updated');
        socket.off('new_order');
        socket.off('order_status_update');
      }
    };
  }, [socket]);

  const filteredOrders = useMemo(() => {
    return orders.filter(order => {
      const orderStatus = order.status || "Pending";
      const matchesTab = activeTab === "All" || 
        STATUS_MAPPING[activeTab]?.some(s => s.toLowerCase() === orderStatus.toLowerCase());

      const searchLower = searchTerm.toLowerCase();
      const matchesSearch = !searchTerm || 
        order.id.toString().toLowerCase().includes(searchLower) ||
        (order.customer?.name || "").toLowerCase().includes(searchLower);
      
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
      <div className="space-y-10 animate-fade-in pt-12 mb-20 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
        <header className="flex flex-col md:flex-row md:items-end justify-between gap-8">
          <div>
            <div className="flex items-center gap-3 mb-4">
              <div className="h-0.5 w-12 bg-[var(--rust)] rounded-full" />
              <span className="text-[10px] font-black text-[var(--rust)] tracking-[0.3em] uppercase">Workshop Log</span>
            </div>
            <h1 className="font-serif text-5xl font-bold text-[var(--charcoal)] tracking-tighter uppercase leading-none">
              ORDER <span className="font-serif italic text-[var(--rust)] font-normal ml-1 lowercase">registry</span>
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
            <button className="flex items-center gap-2 px-6 py-4 bg-white border border-[var(--border)] rounded-2xl text-[var(--muted)] hover:text-[var(--rust)] hover:border-[var(--rust)] transition-all shadow-sm">
              <Filter className="w-4 h-4" />
              <span className="text-xs font-bold uppercase tracking-widest">Filter</span>
            </button>
          </div>
        </header>

        {/* Status Tabs */}
        <div className="flex items-center gap-2 p-1.5 bg-[var(--stone)]/10 rounded-[2rem] border border-[var(--border)]/30 overflow-x-auto no-scrollbar">
          {STATUS_TABS.map((tab) => (
            <button 
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`px-6 py-2.5 rounded-full text-[10px] font-bold uppercase tracking-widest whitespace-nowrap transition-all cursor-pointer ${
                activeTab === tab 
                ? 'bg-[var(--rust)] text-white shadow-lg shadow-red-900/20' 
                : 'text-[var(--muted)] hover:text-[var(--charcoal)]'
              }`}
            >
              {tab}
            </button>
          ))}
        </div>

        {/* Table Container */}
        <div className="bg-white rounded-[2.5rem] border border-[var(--border)] overflow-hidden shadow-xl shadow-stone-200/50">
          <div className="overflow-x-auto">
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
            />
          )}
        </AnimatePresence>
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
            {order.customer?.name ? order.customer.name[0] : "C"}
          </div>
          <div className="min-w-0">
            <div className="font-bold text-xs text-[var(--charcoal)] truncate tracking-tight uppercase">{order.customer?.name || "Customer"}</div>
            <div className="text-[10px] text-[var(--muted)] font-medium italic truncate max-w-[200px]">
              {formatAddress(order.shippingAddress)}
            </div>
          </div>
        </div>
      </td>

      <td className="px-8 py-6 align-top text-center">
        <button 
          onClick={() => nextStatus && !isUpdating && onUpdateStatus(nextStatus)}
          disabled={!nextStatus || isUpdating}
          className={`px-4 py-1.5 rounded-lg text-[10px] font-black tracking-[0.1em] shadow-sm transition-all border ${
            isUpdating ? 'animate-pulse opacity-70 cursor-wait' :
            nextStatus ? 'cursor-pointer hover:scale-105 active:scale-95 hover:shadow-md' : 'cursor-default'
          } ${
            currentStatus.toLowerCase() === 'delivered' || currentStatus.toLowerCase() === 'completed' 
            ? 'bg-green-50 text-green-700 border-green-100' 
            : 'bg-amber-50 text-amber-700 border-amber-100'
          }`}
        >
          {isUpdating ? "UPDATING..." : currentStatus.toUpperCase()}
        </button>
      </td>

      <td className="px-8 py-6 align-top">
        <div className="font-mono text-xs font-bold text-[var(--charcoal)]">₱{(order.totalAmount || order.totalPrice)?.toLocaleString()}</div>
        <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mt-1 opacity-60">
          {order.paymentMethod?.toUpperCase()}
        </div>
      </td>

      <td className="px-8 py-6 align-top text-right">
        <button 
          onClick={onView}
          className="p-2.5 bg-[var(--cream)] text-[var(--muted)] hover:text-white hover:bg-[var(--rust)] rounded-xl transition-all shadow-sm group/btn"
        >
          <Eye className="w-4 h-4 transition-transform group-hover/btn:scale-110" />
        </button>
      </td>
    </motion.tr>
  );
}

function OrderModal({ order, onClose, onUpdateStatus, isUpdating }) {
  const steps = ["Pending", "Processing", "Shipped", "Delivered", "Completed"];
  const currentStep = steps.findIndex(s => s.toLowerCase() === order.status?.toLowerCase());

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 lg:p-8">
      <motion.div 
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
        className="absolute inset-0 bg-[var(--charcoal)]/60 backdrop-blur-sm"
      />
      <motion.div 
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        className="relative bg-white w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-[2.5rem] shadow-2xl flex flex-col border border-[var(--border)]"
      >
        {/* Modal Header */}
        <div className="p-8 border-b border-[var(--border)] flex items-center justify-between bg-[var(--stone)]/10">
          <div>
            <div className="text-[10px] font-black text-[var(--rust)] tracking-[0.2em] uppercase mb-1">Commission Details</div>
            <h2 className="font-serif text-2xl font-bold text-[var(--charcoal)] uppercase">Order #LB-{order.id.toString().slice(-8).toUpperCase()}</h2>
          </div>
          <button onClick={onClose} className="p-3 bg-white hover:bg-[var(--cream)] rounded-2xl transition-colors border border-[var(--border)] group">
            <X className="w-5 h-5 text-[var(--muted)] group-hover:text-[var(--rust)] transition-colors" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-8 custom-scrollbar space-y-10">
          {/* Progress Tracker */}
          <div className="bg-[var(--cream)]/30 p-8 rounded-[2rem] border border-[var(--border)]/50">
            <div className="flex justify-between items-center relative gap-4">
              <div className="absolute top-5 left-0 w-full h-0.5 bg-[var(--border)] -z-10" />
              {steps.map((step, i) => {
                const isCompleted = i < currentStep;
                const isActive = i === currentStep;
                return (
                  <button 
                    key={step} 
                    onClick={() => !isUpdating && onUpdateStatus(step)}
                    disabled={isUpdating}
                    className={`flex flex-col items-center gap-3 bg-[var(--cream)] px-2 transition-all hover:scale-110 disabled:opacity-50 disabled:hover:scale-100 ${isUpdating ? 'cursor-wait' : 'cursor-pointer'}`}
                  >
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center text-[10px] font-bold border-2 transition-all ${
                      isCompleted ? 'bg-green-500 border-green-500 text-white shadow-lg shadow-green-100' :
                      isActive ? 'bg-[var(--rust)] border-[var(--rust)] text-white scale-110 shadow-lg shadow-red-100' :
                      'bg-white border-[var(--border)] text-[var(--muted)]'
                    }`}>
                      {isCompleted ? '✓' : i + 1}
                    </div>
                    <span className={`text-[8px] font-black tracking-tighter uppercase whitespace-nowrap ${isActive ? 'text-[var(--rust)]' : 'text-[var(--muted)]'}`}>
                      {step}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* Left Column: Client & Payment */}
            <div className="space-y-8">
              <section>
                <div className="flex items-center gap-3 mb-5">
                  <div className="w-8 h-8 rounded-lg bg-[var(--rust)] flex items-center justify-center text-white">
                    <MapPin className="w-4 h-4" />
                  </div>
                  <h3 className="font-serif text-lg font-bold">Client Information</h3>
                </div>
                <div className="bg-[var(--stone)]/10 p-6 rounded-3xl border border-[var(--border)]/50 space-y-4">
                  <div className="flex items-center gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-[var(--bark)] flex items-center justify-center text-white font-serif text-xl font-bold">
                      {order.customer?.name ? order.customer.name[0] : "C"}
                    </div>
                    <div>
                      <div className="font-bold text-[var(--charcoal)] uppercase tracking-tight">{order.customer?.name || "Customer"}</div>
                      <div className="text-xs text-[var(--muted)] italic">{order.customer?.email}</div>
                    </div>
                  </div>
                  <div className="pt-4 border-t border-[var(--border)]/50">
                    <div className="text-[10px] font-bold text-[var(--muted)] uppercase mb-1 opacity-60">Shipping Logistics</div>
                    <p className="text-sm italic text-[var(--charcoal)] leading-relaxed">
                      {formatAddress(order.shippingAddress)}
                    </p>
                  </div>
                </div>
              </section>

              <section>
                <div className="flex items-center gap-3 mb-5">
                  <div className="w-8 h-8 rounded-lg bg-[var(--charcoal)] flex items-center justify-center text-white">
                    <CreditCard className="w-4 h-4" />
                  </div>
                  <h3 className="font-serif text-lg font-bold">Payment Summary</h3>
                </div>
                <div className="bg-[var(--stone)]/10 p-6 rounded-3xl border border-[var(--border)]/50 grid grid-cols-2 gap-4">
                  <div>
                    <div className="text-[10px] font-bold text-[var(--muted)] uppercase mb-1 opacity-60">Settlement Method</div>
                    <div className="text-sm font-bold text-[var(--charcoal)] uppercase tracking-tight">{order.paymentMethod}</div>
                  </div>
                  <div className="text-right">
                    <div className="text-[10px] font-bold text-[var(--muted)] uppercase mb-1 opacity-60">Status</div>
                    <span className="inline-block px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-[10px] font-bold border border-amber-100 uppercase">
                      {order.status}
                    </span>
                  </div>
                </div>
              </section>
            </div>

            {/* Right Column: Order Items */}
            <section className="flex flex-col h-full">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-8 h-8 rounded-lg bg-[var(--rust)] flex items-center justify-center text-white">
                  <ShoppingBag className="w-4 h-4" />
                </div>
                <h3 className="font-serif text-lg font-bold">Requested Pieces</h3>
              </div>
              <div className="bg-[var(--stone)]/10 p-6 rounded-3xl border border-[var(--border)]/50 flex-1 space-y-4">
                {(order.items || order.orderItems || []).map((item, i) => (
                  <div key={i} className="flex gap-4 p-3 hover:bg-white rounded-2xl transition-all border border-transparent hover:border-[var(--border)] group">
                    <div className="w-16 h-16 rounded-xl overflow-hidden bg-white border border-[var(--border)]">
                      <img 
                        src={item.product?.image?.[0]?.url || item.product?.image?.[0] || "/placeholder-barong.jpg"} 
                        alt="" 
                        className="w-full h-full object-cover transition-transform group-hover:scale-110"
                      />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="font-bold text-xs truncate text-[var(--charcoal)] uppercase tracking-tight">{item.product?.name}</div>
                      <div className="text-[10px] text-[var(--muted)]">Qty: {item.quantity} × ₱{item.price?.toLocaleString()}</div>
                      {item.variant && (
                        <div className="mt-1 inline-block px-1.5 py-0.5 bg-white rounded text-[8px] font-bold text-[var(--muted)] border border-[var(--border)] uppercase">
                          {item.variant}
                        </div>
                      )}
                    </div>
                  </div>
                ))}
                <div className="pt-6 mt-6 border-t border-[var(--border)]/50 flex justify-between items-end">
                  <div className="text-[10px] font-black text-[var(--muted)] uppercase tracking-widest">Grand Total</div>
                  <div className="font-serif text-2xl font-bold text-[var(--rust)]">₱{(order.totalAmount || order.totalPrice)?.toLocaleString()}</div>
                </div>
              </div>
            </section>
          </div>
        </div>
      </motion.div>
    </div>
  );
}
