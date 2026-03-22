"use client";
import React, { useState, useEffect, useRef } from "react";
import SellerLayout from "@/components/SellerLayout";
import { Users, Mail, Phone, Calendar, Search, MapPin, MoreHorizontal, MessageCircle, X, ShoppingBag, Clock, ChevronRight, Package } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useRouter } from "next/navigation";

function formatAddress(address) {
  if (!address) return "Locality Unknown";
  if (typeof address === "string") return address;
  if (typeof address === "object") {
    return address.city || address.province || address.street || "Locality Unknown";
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
        const customerOrders = res.data.filter(o => o.customer?.id === customer.id);
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
        className="relative bg-white w-full max-w-2xl max-h-[85vh] rounded-[2rem] shadow-2xl flex flex-col overflow-hidden border border-[var(--border)]"
      >
        {/* Header */}
        <div className="p-7 border-b border-[var(--border)] flex items-center justify-between bg-[var(--stone)]/10">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 rounded-xl bg-[var(--bark)] flex items-center justify-center text-white font-serif text-lg font-bold">
              {customer.name?.[0]}
            </div>
            <div>
              <div className="text-[10px] font-black text-[var(--rust)] tracking-[0.2em] uppercase mb-0.5">Purchase Portfolio</div>
              <h2 className="font-serif text-xl font-bold text-[var(--charcoal)]">{customer.name}</h2>
            </div>
          </div>
          <button onClick={onClose} className="p-2.5 hover:bg-[var(--cream)] rounded-xl transition-colors border border-[var(--border)] group">
            <X className="w-4 h-4 text-[var(--muted)] group-hover:text-[var(--rust)]" />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto p-7 custom-scrollbar space-y-4">
          {loading ? (
            <div className="py-16 text-center text-[var(--muted)] animate-pulse italic">Loading commission history...</div>
          ) : orders.length === 0 ? (
            <div className="py-16 text-center space-y-3">
              <div className="w-14 h-14 bg-[var(--cream)] rounded-full flex items-center justify-center mx-auto">
                <Package className="w-7 h-7 text-[var(--muted)]" />
              </div>
              <p className="text-[var(--muted)] italic text-sm">No orders found for this patron.</p>
            </div>
          ) : (
            orders.map((order) => {
              const statusColors = {
                Pending: "bg-amber-50 text-amber-700 border-amber-100",
                Processing: "bg-blue-50 text-blue-700 border-blue-100",
                Shipped: "bg-purple-50 text-purple-700 border-purple-100",
                Delivered: "bg-green-50 text-green-700 border-green-100",
                Completed: "bg-green-100 text-green-800 border-green-200",
                Cancelled: "bg-red-50 text-red-600 border-red-100",
              };
              return (
                <motion.div
                  key={order.id}
                  initial={{ opacity: 0, y: 6 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="bg-[var(--stone)]/10 border border-[var(--border)]/50 rounded-2xl p-5 hover:bg-white transition-all hover:shadow-md"
                >
                  <div className="flex items-start justify-between gap-4 mb-4">
                    <div>
                      <div className="font-serif font-bold text-[var(--charcoal)] tracking-tight">
                        #LB-{order.id.toString().slice(-8).toUpperCase()}
                      </div>
                      <div className="flex items-center gap-1.5 mt-1 text-[10px] text-[var(--muted)] font-medium">
                        <Clock className="w-3 h-3" />
                        {new Date(order.createdAt).toLocaleDateString("en-PH", { year: "numeric", month: "long", day: "numeric" })}
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className={`px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border ${statusColors[order.status] || "bg-gray-50 text-gray-600 border-gray-100"}`}>
                        {order.status}
                      </span>
                      <div className="font-mono font-bold text-sm text-[var(--rust)]">
                        ₱{(order.totalAmount || order.totalPrice)?.toLocaleString()}
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {(order.items || order.orderItems || []).slice(0, 3).map((item, i) => (
                      <div key={i} className="flex items-center gap-1.5 bg-white border border-[var(--border)] rounded-lg px-2.5 py-1.5">
                        <ShoppingBag className="w-3 h-3 text-[var(--muted)]" />
                        <span className="text-[10px] font-bold text-[var(--charcoal)] truncate max-w-[120px]">{item.product?.name}</span>
                        <span className="text-[9px] text-[var(--muted)]">×{item.quantity}</span>
                      </div>
                    ))}
                    {(order.items || order.orderItems || []).length > 3 && (
                      <div className="flex items-center px-2.5 py-1.5 text-[10px] text-[var(--muted)] font-bold">
                        +{(order.items || order.orderItems).length - 3} more
                      </div>
                    )}
                  </div>
                </motion.div>
              );
            })
          )}
        </div>

        {/* Footer */}
        <div className="p-5 border-t border-[var(--border)] bg-[var(--stone)]/5 flex items-center justify-between">
          <div className="text-xs text-[var(--muted)]">
            <span className="font-bold text-[var(--charcoal)]">{orders.length}</span> total commission{orders.length !== 1 ? "s" : ""}
          </div>
          <div className="font-mono text-sm font-bold text-[var(--rust)]">
            Total: ₱{orders.reduce((sum, o) => sum + (parseFloat(o.totalAmount || o.totalPrice) || 0), 0).toLocaleString()}
          </div>
        </div>
      </motion.div>
    </div>
  );
}

function MoreMenu({ customer, onMessage, onViewPortfolio }) {
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
        className="p-2.5 bg-white border border-[var(--border)] rounded-xl text-[var(--muted)] hover:text-[var(--charcoal)] hover:bg-[var(--cream)] transition-all shadow-sm"
      >
        <MoreHorizontal className="w-4 h-4" />
      </button>
      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, scale: 0.92, y: -6 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.92, y: -6 }}
            transition={{ duration: 0.12 }}
            className="absolute right-0 top-12 z-50 bg-white border border-[var(--border)] rounded-2xl shadow-xl overflow-hidden w-48"
          >
            <button
              onClick={() => { onMessage(); setOpen(false); }}
              className="w-full flex items-center gap-3 px-4 py-3 text-xs font-bold text-[var(--charcoal)] hover:bg-[var(--cream)] hover:text-[var(--rust)] transition-colors"
            >
              <MessageCircle className="w-4 h-4" /> Send Message
            </button>
            <button
              onClick={() => { onViewPortfolio(); setOpen(false); }}
              className="w-full flex items-center gap-3 px-4 py-3 text-xs font-bold text-[var(--charcoal)] hover:bg-[var(--cream)] hover:text-[var(--rust)] transition-colors border-t border-[var(--border)]"
            >
              <ChevronRight className="w-4 h-4" /> View Portfolio
            </button>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

export default function SellerCustomers() {
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedPortfolio, setSelectedPortfolio] = useState(null);
  const router = useRouter();

  useEffect(() => {
    const fetchCustomers = async () => {
      try {
        const res = await api.get("/orders/seller");
        const uniqueCustomers = [];
        const seenIds = new Set();
        res.data.forEach(order => {
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

  const filtered = customers.filter(c =>
    !searchTerm ||
    c.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    formatAddress(c.address).toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleMessage = (customer) => {
    router.push(`/seller/messages?userId=${customer.id}`);
  };

  return (
    <SellerLayout>
      <div className="space-y-10 mb-20 animate-fade-in">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <div className="eyebrow">Workshop Registry</div>
            <h1 className="font-serif text-4xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
              Patron <span className="text-[var(--rust)] italic lowercase">Directory</span>
            </h1>
          </div>
          <div className="relative w-full md:w-96">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)]" />
            <input
              type="text"
              value={searchTerm}
              onChange={e => setSearchTerm(e.target.value)}
              placeholder="Filter patrons by name or locality..."
              className="w-full pl-12 pr-4 py-3 bg-white border border-[var(--border)] rounded-2xl outline-none focus:border-[var(--rust)] transition-all font-medium text-sm shadow-sm"
            />
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
          {loading ? (
            <div className="col-span-full py-24 text-center text-[var(--muted)] animate-pulse italic">Connecting to artisan patron records...</div>
          ) : filtered.length === 0 ? (
            <div className="col-span-full artisan-card py-20 text-center space-y-4">
              <div className="w-16 h-16 bg-[var(--cream)] rounded-full flex items-center justify-center mx-auto"><Users className="w-8 h-8 text-[var(--muted)]" /></div>
              <h3 className="text-xl font-serif font-bold">No patrons established yet</h3>
              <p className="text-[var(--muted)] max-w-sm mx-auto">Customers who commission your work will appear in this registry for future connection.</p>
            </div>
          ) : (
            filtered.map((customer, idx) => (
              <motion.div
                key={customer.id}
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: idx * 0.05 }}
                className="artisan-card group hover:shadow-2xl transition-all p-8 flex flex-col justify-between"
              >
                <div className="space-y-6">
                  <div className="flex items-center justify-between">
                    <div className="w-14 h-14 bg-[var(--bark)] rounded-2xl flex items-center justify-center text-white font-serif text-xl font-bold shadow-lg ring-4 ring-white">
                      {customer.name?.[0]}
                    </div>
                    <div className="flex gap-2">
                      <button
                        onClick={() => handleMessage(customer)}
                        className="p-2.5 bg-white border border-[var(--border)] rounded-xl text-[var(--muted)] hover:text-[var(--rust)] hover:bg-[var(--cream)] transition-all shadow-sm"
                        title="Send Message"
                      >
                        <MessageCircle className="w-4 h-4" />
                      </button>
                      <MoreMenu
                        customer={customer}
                        onMessage={() => handleMessage(customer)}
                        onViewPortfolio={() => setSelectedPortfolio(customer)}
                      />
                    </div>
                  </div>

                  <div>
                    <h3 className="font-bold text-xl text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors">{customer.name}</h3>
                    <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mt-1 flex items-center gap-1.5 opacity-60">
                      <Calendar className="w-3.5 h-3.5" /> Last Commission: {new Date(customer.lastOrder).toLocaleDateString()}
                    </div>
                  </div>

                  <div className="space-y-3 pt-6 border-t border-[var(--border)]">
                    <div className="flex items-center gap-3 text-xs font-medium text-[var(--charcoal)] opacity-80"><Mail className="w-4 h-4 text-[var(--muted)]" /> {customer.email}</div>
                    <div className="flex items-center gap-3 text-xs font-medium text-[var(--charcoal)] opacity-80"><Phone className="w-4 h-4 text-[var(--muted)]" /> {customer.mobile || '+63 9xx xxx xxxx'}</div>
                    <div className="flex items-center gap-3 text-xs font-medium text-[var(--charcoal)] opacity-80"><MapPin className="w-4 h-4 text-[var(--muted)]" /> {formatAddress(customer.address)}</div>
                  </div>
                </div>

                <div className="mt-8 pt-6 border-t border-[var(--border)] flex items-center justify-between">
                  <div className="px-3 py-1 bg-[var(--rust)] text-white text-[9px] font-bold uppercase tracking-widest rounded-full shadow-md">
                    {customer.orderCount} COMMISSIONS
                  </div>
                  <button
                    onClick={() => setSelectedPortfolio(customer)}
                    className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] transition-colors flex items-center gap-1"
                  >
                    View Purchase Portfolio <ChevronRight className="w-3 h-3" />
                  </button>
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
    </SellerLayout>
  );
}
