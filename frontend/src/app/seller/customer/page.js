"use client";
import React, { Suspense, useEffect, useRef, useState } from "react";
import SellerLayout from "@/components/SellerLayout";
import { Users, Mail, Phone, Calendar, Search, MapPin, MoreHorizontal, MessageCircle, X, ShoppingBag, Clock, ChevronRight, Package, MoreVertical, Grid3x3, List, ShieldAlert } from "lucide-react";
import ReportModal from "@/components/ReportModal";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useRouter, useSearchParams } from "next/navigation";

function formatAddress(address) {
  if (!address) return "Locality Unknown";

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
    const city = addrObj.city || addrObj.municipality || "";
    const province = addrObj.province || "";

    if (city && province) return `${city}, ${province}`;
    return city || province || "Locality Unknown";
  }

  return "Locality Unknown";
}

function PortfolioModal({ customer, onClose }) {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchOrders = async () => {
      try {
        const res = await api.get("/orders/seller");
        const rawOrders = res.data?.data || res.data || [];
        const customerOrders = (Array.isArray(rawOrders) ? rawOrders : []).filter(o => o.customer?.id === customer.id);
        setOrders(customerOrders);
      } catch (err) {
        console.error("Failed to load portfolio");
      } finally {
        setLoading(false);
      }
    };
    fetchOrders();
  }, [customer.id]);

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
      />
      <motion.div
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        className="relative bg-white w-full max-w-2xl max-h-[85vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-[var(--border)]"
      >
        {/* Header */}
        <div className="p-6 border-b border-[var(--border)] flex items-center justify-between bg-[var(--cream)]">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 rounded-xl bg-[var(--bark)] flex items-center justify-center text-white font-bold text-lg shadow-md">
              {customer.name?.[0]}
            </div>
            <div>
              <div className="text-[10px] font-black text-[var(--rust)] uppercase tracking-[0.2em] mb-0.5">Order History (Portfolio)</div>
              <h2 className="text-xl font-bold text-[var(--charcoal)]">{customer.name}</h2>
            </div>
          </div>
          <button onClick={onClose} className="p-2.5 hover:bg-white rounded-xl transition-colors border border-[var(--border)] group">
            <X className="w-4 h-4 text-[var(--muted)] group-hover:text-[var(--rust)]" />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto p-5 space-y-3 custom-scrollbar">
          {loading ? (
            <div className="py-16 text-center text-[var(--muted)] animate-pulse italic">Loading order history...</div>
          ) : orders.length === 0 ? (
            <div className="py-16 text-center space-y-3">
              <div className="w-14 h-14 bg-[var(--cream)] rounded-full flex items-center justify-center mx-auto">
                <Package className="w-7 h-7 text-[var(--muted)]" />
              </div>
              <p className="text-[var(--muted)] text-sm italic">No orders found for this customer.</p>
            </div>
          ) : (
            orders.map((order) => {
              const statusColors = {
                Pending: "bg-amber-50 text-amber-700 border-amber-200",
                Processing: "bg-blue-50 text-blue-700 border-blue-200",
                Shipped: "bg-purple-50 text-purple-700 border-purple-200",
                Delivered: "bg-green-50 text-green-700 border-green-200",
                Completed: "bg-green-100 text-green-800 border-green-300",
                Cancelled: "bg-red-50 text-red-600 border-red-200",
              };
              return (
                <motion.div
                  key={order.id}
                  initial={{ opacity: 0, y: 6 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="artisan-card !p-3.5 group"
                >
                  <div className="flex items-start justify-between gap-4 mb-3">
                    <div>
                      <div className="font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors tracking-tight text-sm">
                        #LB-{order.id.toString().slice(-8).toUpperCase()}
                      </div>
                      <div className="flex items-center gap-1.5 mt-1 text-[9px] text-[var(--muted)] font-black uppercase tracking-widest opacity-70">
                        <Clock className="w-2.5 h-2.5" />
                        {new Date(order.createdAt).toLocaleDateString("en-PH", { year: "numeric", month: "long", day: "numeric" })}
                      </div>
                    </div>
                    <div className="flex items-center gap-2.5">
                      <span className={`px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest border ${statusColors[order.status] || "bg-gray-50 text-gray-600 border-gray-200"}`}>
                        {order.status}
                      </span>
                      <div className="font-bold text-xs text-[var(--rust)]">
                        ₱{(order.totalAmount || order.totalPrice)?.toLocaleString()}
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-wrap gap-1.5">
                    {(order.items || order.orderItems || []).slice(0, 3).map((item, i) => (
                      <div key={i} className="flex items-center gap-1 bg-[var(--cream)] border border-[var(--border)] rounded-md px-2 py-1">
                        <ShoppingBag className="w-2.5 h-2.5 text-[var(--muted)]" />
                        <span className="text-[9px] font-bold text-[var(--charcoal)] truncate max-w-[100px] uppercase">{item.product?.name}</span>
                        <span className="text-[9px] font-bold text-[var(--muted)]">×{item.quantity}</span>
                      </div>
                    ))}
                    {(order.items || order.orderItems || []).length > 3 && (
                      <div className="flex items-center px-2 py-1 text-[8px] text-[var(--muted)] font-black uppercase tracking-widest">
                        +{(order.items || order.orderItems).length - 3}
                      </div>
                    )}
                  </div>
                </motion.div>
              );
            })
          )}
        </div>

        {/* Footer */}
        <div className="p-5 border-t border-[var(--border)] bg-[var(--cream)]/30 flex items-center justify-between">
          <div className="text-[10px] font-black text-[var(--muted)] uppercase tracking-widest">
            <span className="text-[var(--rust)]">{orders.length}</span> total order{orders.length !== 1 ? "s" : ""}
          </div>
          <div className="text-sm font-bold text-[var(--rust)]">
            Total: ₱{orders.reduce((sum, o) => sum + (parseFloat(o.totalAmount || o.totalPrice) || 0), 0).toLocaleString()}
          </div>
        </div>
      </motion.div>
    </div>
  );
}

function MoreMenu({ customer, onMessage, onViewPortfolio, onReport }) {
  const [open, setOpen] = useState(false);
  const ref = useRef(null);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (ref.current && !ref.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen(v => !v)}
        className="p-2.5 bg-white border border-[var(--border)] rounded-xl text-[var(--muted)] hover:text-[var(--rust)] hover:bg-[var(--cream)] shadow-sm transition-all active:scale-95"
      >
        <MoreVertical className="w-4 h-4" />
      </button>
      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, scale: 0.95, y: -10 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: -10 }}
            className="absolute right-0 top-12 z-50 bg-white border border-[var(--border)] rounded-2xl shadow-2xl overflow-hidden w-56"
          >
            <button
              onClick={() => { onMessage(); setOpen(false); }}
              className="w-full flex items-center gap-3 px-5 py-4 text-[10px] font-black uppercase tracking-widest text-[var(--charcoal)] hover:bg-[var(--cream)] hover:text-[var(--rust)] transition-all text-left"
            >
              <MessageCircle className="w-4 h-4" /> Send Message
            </button>
            <button
              onClick={() => { onViewPortfolio(); setOpen(false); }}
              className="w-full flex items-center gap-3 px-5 py-4 text-[10px] font-black uppercase tracking-widest text-[var(--charcoal)] hover:bg-[var(--cream)] hover:text-[var(--rust)] transition-all border-t border-[var(--border)] text-left"
            >
              <ChevronRight className="w-4 h-4" /> View Portfolio
            </button>
            <button
              onClick={() => { onReport(); setOpen(false); }}
              className="w-full flex items-center gap-3 px-5 py-4 text-[10px] font-black uppercase tracking-widest text-red-500 hover:bg-red-50 transition-all border-t border-[var(--border)] text-left"
            >
              <ShieldAlert className="w-4 h-4" /> Report User
            </button>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

function SellerCustomersContent() {
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedPortfolio, setSelectedPortfolio] = useState(null);
  const [selectedForReport, setSelectedForReport] = useState(null);
  const [isReportModalOpen, setIsReportModalOpen] = useState(false);
  const [statusFilter, setStatusFilter] = useState("All Customers");
  const router = useRouter();
  const searchParams = useSearchParams();
  const targetId = searchParams.get("id");

  useEffect(() => {
    const fetchCustomers = async () => {
      try {
        const res = await api.get("/orders/seller");
        const rawOrders = res.data?.data || res.data || [];
        const uniqueCustomers = [];
        const seenIds = new Set();
        (Array.isArray(rawOrders) ? rawOrders : []).forEach(order => {
          if (order.customer && !seenIds.has(order.customer.id)) {
            uniqueCustomers.push({
              ...order.customer,
              lastOrder: order.createdAt,
              address: order.shippingAddress,
              orderCount: 1
            });
            seenIds.add(order.customer.id);
          } else if (order.customer) {
            const index = uniqueCustomers.findIndex(c => c.id === order.customer.id);
            uniqueCustomers[index].orderCount += 1;
            if (new Date(order.createdAt) > new Date(uniqueCustomers[index].lastOrder)) {
              uniqueCustomers[index].lastOrder = order.createdAt;
              uniqueCustomers[index].address = order.shippingAddress;
            }
          }
        });
        setCustomers(uniqueCustomers);
      } catch (err) {
        console.error("Failed to fetch customers");
      } finally {
        setLoading(false);
      }
    };
    fetchCustomers();
  }, []);

  useEffect(() => {
    if (!loading && customers.length > 0 && targetId) {
      const match = customers.find(c => String(c.id) === String(targetId));
      if (match && !selectedPortfolio) {
        setSelectedPortfolio(match);
      }
    }
  }, [loading, customers, targetId]);

  const filtered = customers.filter(c => {
    const matchesSearch = !searchTerm ||
      c.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      formatAddress(c.address).toLowerCase().includes(searchTerm.toLowerCase());

    if (statusFilter === "All Customers") return matchesSearch;
    if (statusFilter === "Active") return matchesSearch && c.orderCount >= 3;
    if (statusFilter === "Recent") {
      const monthAgo = new Date();
      monthAgo.setMonth(monthAgo.getMonth() - 1);
      return matchesSearch && new Date(c.lastOrder) > monthAgo;
    }
    return matchesSearch;
  });

  return (
    <SellerLayout>
      <div className="space-y-8 mb-20 pt-2 animate-fade-in">
        {/* Page Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <div className="flex items-center gap-3 mb-1 sm:mb-2">
              <div className="h-0.5 w-8 sm:w-12 bg-[var(--rust)] rounded-full" />
              <span className="text-[10px] sm:text-xs font-black text-[var(--rust)] tracking-[0.3em] uppercase">Customer List (Workshop Registry)</span>
            </div>
            <h1 className="font-serif text-lg sm:text-xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
              CUSTOMER <span className="font-serif italic text-[var(--rust)] font-normal ml-1 lowercase">(Directory)</span>
            </h1>
          </div>

          <div className="flex flex-wrap items-center gap-4">
            <div className="relative group">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)] group-focus-within:text-[var(--rust)] transition-colors" />
              <input
                type="text"
                value={searchTerm}
                onChange={e => setSearchTerm(e.target.value)}
                placeholder="Filter customers by name or locality..."
                className="bg-white border border-[var(--border)] rounded-2xl pl-12 pr-6 py-4 text-sm w-full md:w-[320px] outline-none focus:border-[var(--rust)] focus:ring-4 focus:ring-[var(--rust)]/5 transition-all shadow-sm"
              />
            </div>
            <div className="flex bg-white rounded-2xl border border-[var(--border)] p-1.5 shadow-sm overflow-hidden">
              <button
                onClick={() => setStatusFilter("All Customers")}
                className={`flex-1 sm:flex-none px-2.5 sm:px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${statusFilter === "All Customers" ? 'bg-[var(--bark)] text-white shadow-md' : 'text-[var(--muted)] hover:text-[var(--rust)]'}`}
              >
                All
              </button>
              <button
                onClick={() => setStatusFilter("Active")}
                className={`flex-1 sm:flex-none px-2.5 sm:px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${statusFilter === "Active" ? 'bg-[var(--bark)] text-white shadow-md' : 'text-[var(--muted)] hover:text-[var(--rust)]'}`}
              >
                Active
              </button>
              <button
                onClick={() => setStatusFilter("Recent")}
                className={`flex-1 sm:flex-none px-2.5 sm:px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${statusFilter === "Recent" ? 'bg-[var(--bark)] text-white shadow-md' : 'text-[var(--muted)] hover:text-[var(--rust)]'}`}
              >
                Recent
              </button>
            </div>
          </div>
        </div>

        <div className="mb-6 flex items-center justify-between">
          <p className="text-[10px] font-black uppercase tracking-widest text-[var(--muted)]">
            Showing <span className="text-[var(--rust)]">{filtered.length}</span> of {customers.length} artisans found
          </p>
        </div>

        {/* Customer Cards (CROSSWISE on LG, LNGTWISE on Mobile) */}
        <div className="flex flex-col gap-4">
          {loading ? (
            Array(4).fill(0).map((_, i) => (
              <div key={i} className="artisan-card h-24 animate-pulse bg-[var(--cream)]/30 border-dashed" />
            ))
          ) : filtered.length === 0 ? (
            <div className="artisan-card py-20 flex flex-col items-center justify-center text-center space-y-4">
              <div className="w-20 h-20 bg-[var(--cream)] rounded-full flex items-center justify-center text-[var(--muted)]"><Users className="w-10 h-10" /></div>
              <div className="text-xl font-serif font-bold">No customers found</div>
              <p className="text-sm text-[var(--muted)] max-w-xs">Try adjusting your filters to find your artisan partners.</p>
            </div>
          ) : (
            filtered.map((customer, idx) => (
              <motion.div
                key={customer.id}
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: idx * 0.05 }}
                className="artisan-card group flex flex-col lg:flex-row items-center gap-4 lg:gap-4 !p-4 hover:border-[var(--rust)]/30 transition-all duration-500"
              >
                {/* Avatar & Basic Info */}
                <div className="flex items-center gap-3 lg:gap-5 w-full lg:w-72 shrink-0">
                  <div className="h-10 lg:h-14 lg:w-14 w-10 ring-2 lg:ring-4 ring-[var(--cream)] rounded-xl lg:rounded-2xl bg-[var(--bark)] text-white flex items-center justify-center font-serif font-bold text-base lg:text-xl shadow-lg shrink-0 group-hover:scale-110 transition-transform duration-500">
                    {customer.name?.[0]}{customer.name?.split(" ")[1]?.[0] || ""}
                  </div>
                  <div className="min-w-0">
                    <h3 className="font-bold text-[var(--charcoal)] text-sm lg:text-base truncate group-hover:text-[var(--rust)] transition-colors">
                      {customer.name}
                    </h3>
                    <div className="flex items-center gap-1.5 mt-0.5 text-[8px] lg:text-[9px] font-bold text-[var(--muted)] uppercase tracking-wider">
                      <MapPin className="h-3 w-3 text-[var(--rust)]" />
                      {formatAddress(customer.address)}
                    </div>
                  </div>
                </div>

                {/* Contact Info (Stacked on mobile, row on LG) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:flex flex-1 items-center gap-4 lg:gap-8 w-full border-t lg:border-none border-[var(--border)] pt-4 lg:pt-0">
                  <div className="flex items-center gap-3">
                    <div className="flex items-center justify-center w-8 h-8 lg:w-9 lg:h-9 rounded-lg lg:rounded-xl bg-[var(--cream)] group-hover:bg-white transition-colors">
                      <Mail className="h-3.5 w-3.5 lg:h-4 lg:w-4 text-[var(--muted)] group-hover:text-[var(--rust)]" />
                    </div>
                    <div className="min-w-0">
                      <div className="text-[8px] lg:text-[9px] font-black text-[var(--muted)] uppercase tracking-[0.1em] opacity-50 mb-0.5">Email</div>
                      <div className="text-[10px] lg:text-xs font-bold text-[var(--charcoal)] truncate">{customer.email}</div>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <div className="flex items-center justify-center w-8 h-8 lg:w-9 lg:h-9 rounded-lg lg:rounded-xl bg-[var(--cream)] group-hover:bg-white transition-colors">
                      <Phone className="h-3.5 w-3.5 lg:h-4 lg:w-4 text-[var(--muted)] group-hover:text-[var(--rust)]" />
                    </div>
                    <div className="min-w-0">
                      <div className="text-[8px] lg:text-[9px] font-black text-[var(--muted)] uppercase tracking-[0.1em] opacity-50 mb-0.5">Mobile</div>
                      <div className="text-[10px] lg:text-xs font-bold text-[var(--charcoal)] truncate">{customer.mobileNumber || customer.mobile || '+63 9xx xxx xxxx'}</div>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <div className="flex items-center justify-center w-8 h-8 lg:w-9 lg:h-9 rounded-lg lg:rounded-xl bg-[var(--cream)] group-hover:bg-white transition-colors">
                      <Clock className="h-3.5 w-3.5 lg:h-4 lg:w-4 text-[var(--muted)] group-hover:text-[var(--rust)]" />
                    </div>
                    <div className="min-w-0">
                      <div className="text-[8px] lg:text-[9px] font-black text-[var(--muted)] uppercase tracking-[0.1em] opacity-50 mb-0.5">Registry</div>
                      <div className="text-[10px] lg:text-xs font-bold text-[var(--charcoal)] truncate">{new Date(customer.lastOrder).toLocaleDateString()}</div>
                    </div>
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center justify-between lg:justify-end gap-3 w-full lg:w-auto pt-4 lg:pt-0 border-t lg:border-none border-[var(--border)]">
                  <div className="bg-[var(--rust)]/10 text-[var(--rust)] text-[8px] lg:text-[10px] font-black uppercase tracking-widest px-3 lg:px-4 py-1.5 lg:py-2 rounded-lg lg:rounded-xl">
                    {customer.orderCount} {customer.orderCount === 1 ? 'Order' : 'Orders'}
                  </div>
                  <div className="flex gap-1.5 items-center">
                    <MoreMenu
                      customer={customer}
                      onMessage={() => router.push(`/seller/messages?userId=${customer.id}`)}
                      onViewPortfolio={() => setSelectedPortfolio(customer)}
                      onReport={() => {
                        setSelectedForReport(customer);
                        setIsReportModalOpen(true);
                      }}
                    />
                    <button
                      onClick={() => setSelectedPortfolio(customer)}
                      className="p-2 lg:p-3 bg-[var(--cream)] hover:bg-[var(--rust)] text-[var(--muted)] hover:text-white rounded-lg lg:rounded-xl transition-all shadow-sm active:scale-95 group/btn"
                    >
                      <ChevronRight className="w-4 h-4 lg:w-5 lg:h-5 group-hover/btn:translate-x-1 transition-transform" />
                    </button>
                  </div>
                </div>
              </motion.div>
            ))
          )}
        </div>
      </div>

      <AnimatePresence>
        {selectedPortfolio && (
          <PortfolioModal
            customer={selectedPortfolio}
            onClose={() => setSelectedPortfolio(null)}
          />
        )}
      </AnimatePresence>

      {selectedForReport && (
        <ReportModal
          isOpen={isReportModalOpen}
          onClose={() => {
            setIsReportModalOpen(false);
            setSelectedForReport(null);
          }}
          reportedId={selectedForReport.id}
          type="SellerReportingCustomer"
          reportedName={selectedForReport.name}
        />
      )}
    </SellerLayout>
  );
}

export default function SellerCustomers() {
  return (
    <Suspense fallback={<SellerLayout><div className="py-24 text-center text-[var(--muted)] animate-pulse italic">Loading customer directory...</div></SellerLayout>}>
      <SellerCustomersContent />
    </Suspense>
  );
}
