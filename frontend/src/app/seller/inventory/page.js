"use client";
import React, { useState, useEffect, useCallback } from "react";
import SellerLayout from "@/components/SellerLayout";
import Image from "next/image";
import Link from "next/link";
import {
  Package,
  Search,
  Filter,
  MoreVertical,
  Plus,
  Edit3,
  Trash2,
  TrendingUp,
  Inbox,
  LayoutGrid,
  List as ListIcon,
  CheckCircle,
  AlertTriangle,
  XCircle
} from "lucide-react";
import { usePathname, useRouter } from "next/navigation";
import { motion, AnimatePresence } from "framer-motion";
import ConfirmationModal from "@/components/ConfirmationModal";

// No static products; data is fetched from the backend on mount and updated via Socket.IO.
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { getProductImageSrc } from "@/lib/productImages";

export default function InventoryPage() {
  const router = useRouter();
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [view, setView] = useState("list");
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("All");
  const [toast, setToast] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteId, setDeleteId] = useState(null);

  const showToast = (type, msg) => {
    setToast({ type, msg });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchProducts = useCallback(async () => {
    try {
      const res = await api.get("/products/seller");
      setProducts(res.data);
    } catch (err) {
      console.warn("Failed to fetch products", err.message || "Backend offline");
    } finally {
      setLoading(false);
    }
  }, []);

  const { socket } = useSocket();

  useEffect(() => {
    fetchProducts();
    if (!socket) return;

    const handleInventoryUpdated = () => fetchProducts();
    const handleStatsUpdate = (data) => {
      if (data.type === 'inventory') fetchProducts();
    };

    socket.on("inventory_updated", handleInventoryUpdated);
    socket.on("stats_update", handleStatsUpdate);

    return () => {
      socket.off("inventory_updated", handleInventoryUpdated);
      socket.off("stats_update", handleStatsUpdate);
    };
  }, [socket, fetchProducts]);

  const handleDeleteClick = (id) => {
    setDeleteId(id);
    setShowDeleteModal(true);
  };

  const confirmDelete = async () => {
    if (!deleteId) return;
    try {
      await api.delete(`/products/${deleteId}`);
      showToast("success", "Product removed from stock.");
      fetchProducts();
    } catch (err) {
      showToast("error", err.response?.data?.message || "Failed to remove product.");
    } finally {
      setShowDeleteModal(false);
      setDeleteId(null);
    }
  };

  return (
    <SellerLayout>
      <div className="space-y-8 mb-20 pt-2">
        {/* Page Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 animate-fade-down">
          <div>
            <div className="flex items-center gap-3 mb-1 sm:mb-2">
              <div className="h-0.5 w-8 sm:w-12 bg-[var(--rust)] rounded-full" />
              <span className="text-[10px] sm:text-xs font-black text-[var(--rust)] tracking-[0.3em] uppercase">Products (Inventory)</span>
            </div>
            <h1 className="font-serif text-lg sm:text-xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
              Stock List <span className="font-serif italic text-[var(--rust)] font-normal ml-1 lowercase">(Inventory)</span>
            </h1>
          </div>

          <div className="flex flex-wrap items-center gap-4">
            <div className="relative group">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)] group-focus-within:text-[var(--rust)] transition-colors" />
              <input
                type="text"
                placeholder="Search your products by name..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="bg-white border border-[var(--border)] rounded-2xl pl-12 pr-6 py-4 text-sm w-full md:w-[320px] outline-none focus:border-[var(--rust)] focus:ring-4 focus:ring-[var(--rust)]/5 transition-all shadow-sm"
              />
            </div>
            <Link href="/seller/add-product" className="btn-primary px-8 py-3 shadow-xl">
              Add New Product <Plus className="w-4 h-4 ml-1" />
            </Link>
          </div>
        </div>

        {/* Toast Notification */}
        <AnimatePresence>
          {toast && (
            <motion.div
              initial={{ opacity: 0, y: -20, x: "-50%" }}
              animate={{ opacity: 1, y: 20, x: "-50%" }}
              exit={{ opacity: 0, y: -20, x: "-50%" }}
              className={`fixed top-4 left-1/2 z-[100] px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border backdrop-blur-md ${toast.type === "success"
                  ? "bg-green-50/90 border-green-200 text-green-800"
                  : "bg-red-50/90 border-red-200 text-red-800"
                }`}
            >
              {toast.type === "success" ? <CheckCircle className="w-5 h-5" /> : <XCircle className="w-5 h-5" />}
              <span className="text-sm font-bold">{toast.msg}</span>
            </motion.div>
          )}
        </AnimatePresence>

        <div className="flex flex-col xl:flex-row xl:items-center justify-end gap-6 mt-4 animate-fade-up">
          <button
            onClick={() => {
              const filters = ["All", "Active", "Low Stock", "Out of Stock"];
              setStatusFilter(filters[(filters.indexOf(statusFilter) + 1) % filters.length]);
            }}
            className="flex items-center gap-2 px-6 py-3 bg-white border border-[var(--border)] rounded-xl text-xs font-bold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] hover:border-[var(--rust)] transition-all w-48 justify-center"
          >
            <Filter className="w-4 h-4 shrink-0" /> {statusFilter === "All" ? "Filter Status" : statusFilter}
          </button>
        </div>

        {/* Inventory View */}
        {loading ? (
          <div className="artisan-card py-24 text-center text-[var(--muted)] animate-pulse italic">Updating your product list...</div>
        ) : products.length === 0 ? (
          <div className="artisan-card py-20 flex flex-col items-center justify-center text-center space-y-4">
            <div className="w-20 h-20 bg-[var(--cream)] rounded-full flex items-center justify-center text-[var(--muted)]"><Inbox className="w-10 h-10" /></div>
            <div className="text-xl font-serif font-bold">No products found</div>
            <p className="text-sm text-[var(--muted)] max-w-xs">Start your heritage business by adding your first embroidered product.</p>
          </div>
        ) : (
          <div className={`grid ${view === "list" ? "grid-cols-1" : "grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3"} gap-6`}>
            {products
              .filter(p => p.name.toLowerCase().includes(searchTerm.toLowerCase()) || (p.categories && p.categories.join(' ').toLowerCase().includes(searchTerm.toLowerCase())))
              .filter(p => {
                if (statusFilter === "All") return true;
                const status = p.stock > 5 ? 'Active' : p.stock > 0 ? 'Low Stock' : 'Out of Stock';
                return status === statusFilter;
              })
              .map((product, i) => {
                const status = product.stock > 5 ? 'Active' : product.stock > 0 ? 'Low Stock' : 'Out of Stock';
                return (
                  <motion.div
                    key={product.id}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: i * 0.05 }}
                    className={`artisan-card overflow-hidden transition-all group ${view === "list" ? 'p-4 flex items-center gap-6' : 'p-0 flex flex-col'}`}
                  >
                    <div className={`relative shrink-0 overflow-hidden bg-[var(--cream)] rounded-xl border border-[var(--border)] ${view === "list" ? 'w-20 h-20' : 'w-full h-64 border-none border-b rounded-none'}`}>
                      <Link href={`/products?id=${product.id}&preview=1`} className="block w-full h-full">
                        <img
                          src={getProductImageSrc(product.image)}
                          className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000 cursor-pointer"
                          alt={product.name}
                          onError={(e) => { e.target.src = "/images/placeholder.png"; if (!e.target.dataset.tried) { e.target.dataset.tried = true; e.target.src = "/images/placeholder.png"; } }}
                        />
                      </Link>
                    </div>

                    <div className={`flex-1 ${view === "list" ? 'grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6 items-center' : 'p-6 space-y-6 flex flex-col justify-between h-full'}`}>
                      <div className="space-y-1">
                        <h3 className="font-bold text-[var(--charcoal)] line-clamp-1 group-hover:text-[var(--rust)] transition-colors">{product.name}</h3>
                        <div className="text-[10px] uppercase font-bold tracking-widest text-[var(--muted)] opacity-50 underline decoration-1 decoration-[var(--border)] underline-offset-4">Ref ID: #ART-00{product.id}</div>
                      </div>

                      <div className="space-y-1">
                        <div className="text-[10px] uppercase font-bold tracking-widest text-[var(--muted)] opacity-60">Listing Price</div>
                        <div className="font-serif font-bold text-lg text-[var(--rust)]">₱{parseFloat(product.price).toLocaleString()}</div>
                      </div>

                      <div className="space-y-2">
                        <div className="text-[10px] uppercase font-bold tracking-widest text-[var(--muted)] opacity-60">Stock Left</div>
                        <div className="flex items-center gap-2">
                          <div className="h-1.5 w-20 bg-[var(--cream)] rounded-full overflow-hidden shrink-0">
                            <motion.div initial={{ width: 0 }} animate={{ width: `${Math.min(product.stock * 5, 100)}%` }} className={`h-full ${product.stock > 5 ? 'bg-[var(--bark)]' : product.stock > 0 ? 'bg-amber-500' : 'bg-red-400'}`} />
                          </div>
                          <span className={`text-xs font-bold ${product.stock === 0 ? 'text-red-500' : 'text-[var(--charcoal)]'}`}>{product.stock} units</span>
                        </div>
                      </div>

                      <div className="hidden md:flex flex-col gap-1">
                        <div className="text-[10px] uppercase font-bold tracking-widest text-[var(--muted)] opacity-60">Listing Category</div>
                        <div className="text-xs font-bold text-[var(--muted)]">
                          {product.categories?.join(', ') || "Uncategorized"}
                        </div>
                      </div>

                      <div className="flex items-center justify-between md:justify-end gap-3 pt-4 md:pt-0 border-t md:border-none border-[var(--border)]">
                        <div className={`px-2 py-1 rounded-md text-[9px] font-bold tracking-widest uppercase flex items-center gap-1.5 border ${status === 'Active' ? 'bg-green-50 text-green-700 border-green-200' : status === 'Low Stock' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-red-50 text-red-700 border-red-200'}`}>
                          {status === 'Active' ? <CheckCircle className="w-3 h-3" /> : <AlertTriangle className="w-3 h-3" />} {status}
                        </div>
                        <div className="flex gap-1.5">
                          <button
                            onClick={() => router.push(`/seller/inventory/edit?id=${product.id}`)}
                            className="p-2 hover:bg-[var(--cream)] rounded-lg transition-all text-[var(--muted)] hover:text-[var(--rust)]"
                            title="Edit Listing"
                          >
                            <Edit3 className="w-4 h-4" />
                          </button>
                          <button onClick={() => handleDeleteClick(product.id)} className="p-2 hover:bg-red-50 rounded-lg transition-all text-[var(--muted)] hover:text-red-500" title="Delete Product"><Trash2 className="w-4 h-4" /></button>
                        </div>
                      </div>
                    </div>
                  </motion.div>
                );
              })}
          </div>
        )}
      </div>

      <ConfirmationModal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={confirmDelete}
        title="Remove Masterpiece?"
        message="Are you sure you want to permanently remove this artisan product from your catalog? This action cannot be undone."
        confirmText="Remove Product"
        cancelText="Keep Listing"
        type="danger"
      />
    </SellerLayout>
  );
}
