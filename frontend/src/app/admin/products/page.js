"use client";
import React, { useState, useEffect, useCallback } from "react";
import AdminLayout from "@/components/AdminLayout";
import ConfirmationModal from "@/components/ConfirmationModal";
import { ShoppingBag, Search, Filter, TrendingUp, Store, Package, Trash2, Eye, LayoutGrid, List as ListIcon, CheckCircle2, XCircle, Edit2 } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { getProductImageSrc } from "@/lib/productImages";

export default function AdminProducts() {
  const router = useRouter();
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("products"); // "products" or "categories"
  const [newCategory, setNewCategory] = useState({ name: "", description: "" });
  const [editingCategory, setEditingCategory] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteReason, setDeleteReason] = useState("");
  const [isDeleting, setIsDeleting] = useState(false);

  const fetchProducts = useCallback(async () => {
    try {
      const res = await api.get("/products");
      setProducts(res.data);
    } catch (err) {
      console.warn("Failed to fetch products", err.message || "Backend offline");
      setProducts([]); // Fallback to empty array
    }
  }, []);

  const fetchCategories = useCallback(async () => {
    try {
      const res = await api.get("/categories");
      setCategories(res.data);
    } catch (err) {
      console.warn("Failed to fetch categories", err.message || "Backend offline");
      setCategories([]); // Fallback to empty array
    }
  }, []);

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      await Promise.all([fetchProducts(), fetchCategories()]);
    } finally {
      setLoading(false);
    }
  }, [fetchProducts, fetchCategories]);

  const { socket } = useSocket();

  useEffect(() => {
    fetchData();
    if (!socket) return;

    const handleStatsUpdate = () => fetchData();
    socket.on("stats_update", handleStatsUpdate);
    socket.on("inventory_updated", handleStatsUpdate);
    socket.on("dashboard_update", handleStatsUpdate);

    return () => {
      socket.off("stats_update", handleStatsUpdate);
      socket.off("inventory_updated", handleStatsUpdate);
      socket.off("dashboard_update", handleStatsUpdate);
    };
  }, [socket, fetchData]);

  const handleDeleteClick = (product) => {
    setDeleteTarget(product);
    setDeleteReason("");
    setShowDeleteModal(true);
  };

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    if (!deleteReason.trim()) {
      alert("Please provide a reason for deletion.");
      return;
    }

    setIsDeleting(true);
    setError(null);
    setSuccess(null);
    try {
      await api.delete(`/products/${deleteTarget.id}`, { data: { reason: deleteReason } });
      setSuccess("Product removed from marketplace.");
      setTimeout(() => setSuccess(null), 3000);
      setShowDeleteModal(false);
      fetchData();
    } catch (err) {
      setError(err.response?.data?.message || "Failed to delete product.");
      setTimeout(() => setError(null), 3000);
    } finally {
      setIsDeleting(false);
    }
  };

  const handleAddCategory = async (e) => {
    e.preventDefault();
    if (!newCategory.name) return;
    setError(null); setSuccess(null);
    try {
      if (editingCategory) {
        await api.put(`/categories/${editingCategory.id}`, newCategory);
        setSuccess("Category updated successfully.");
      } else {
        await api.post("/categories", newCategory);
        setSuccess("Category added successfully.");
      }
      setNewCategory({ name: "", description: "" });
      setEditingCategory(null);
      setShowCategoryModal(false);
      setTimeout(() => setSuccess(null), 3000);
      fetchCategories();
    } catch (err) {
      setError(err.response?.data?.message || `Failed to ${editingCategory ? 'update' : 'add'} category.`);
      setTimeout(() => setError(null), 3000);
    }
  };

  const handleEditClick = (cat) => {
    setEditingCategory(cat);
    setNewCategory({ name: cat.name, description: cat.description || "" });
    setShowCategoryModal(true);
  };

  const cancelEdit = () => {
    setEditingCategory(null);
    setNewCategory({ name: "", description: "" });
    setShowCategoryModal(false);
  };

  const handleDeleteCategory = async (id) => {
    if (!confirm("Are you sure? This will only work if no products belong to this category.")) return;
    setError(null); setSuccess(null);
    try {
      await api.delete(`/categories/${id}`);
      setSuccess("Category deleted successfully.");
      setTimeout(() => setSuccess(null), 3000);
      fetchCategories();
    } catch (err) {
      setError(err.response?.data?.message || "Failed to delete category.");
      setTimeout(() => setError(null), 3000);
    }
  };

  const filteredProducts = products.filter(p =>
    p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    p.category?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    p.seller?.name?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const filteredCategories = categories.filter(c =>
    c.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    c.description?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <AdminLayout>
      <div className="space-y-6 mb-16 sm:mb-20">
        {/* Page Header */}
        <div className="flex flex-col items-center lg:items-start gap-4 sm:gap-6 pb-2 text-center lg:text-left">
          <div>
            <h1 className="font-serif text-xl sm:text-2xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
              Central <span className="text-[var(--rust)] italic lowercase">Catalog</span>
            </h1>
            <p className="text-[9px] sm:text-xs font-black text-[var(--muted)] opacity-60 uppercase tracking-widest mt-1">
              Overseeing all heritage collections and segments.
            </p>
          </div>
          <div className="flex items-center p-1 sm:p-1.5 bg-white rounded-2xl border border-[var(--border)] shadow-sm w-fit mx-auto lg:mx-0">
            <button
              onClick={() => setActiveTab("products")}
              className={`flex items-center gap-1.5 sm:gap-2 px-3 sm:px-6 py-2 sm:py-2.5 rounded-xl text-[9px] sm:text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap ${activeTab === "products" ? 'bg-red-50 text-[var(--rust)] shadow-sm ring-1 ring-red-100' : 'text-[var(--muted)] hover:bg-[var(--cream)]'}`}
            >
              <Package className="w-3 sm:w-3.5 h-3 sm:h-3.5" /> Products
            </button>
            <button
              onClick={() => setActiveTab("categories")}
              className={`flex items-center gap-1.5 sm:gap-2 px-3 sm:px-6 py-2 sm:py-2.5 rounded-xl text-[9px] sm:text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap ${activeTab === "categories" ? 'bg-red-50 text-[var(--rust)] shadow-sm ring-1 ring-red-100' : 'text-[var(--muted)] hover:bg-[var(--cream)]'}`}
            >
              <LayoutGrid className="w-3 sm:w-3.5 h-3 sm:h-3.5" /> Categories
            </button>
          </div>
        </div>

        {/* Alerts */}
        <AnimatePresence>
          {error && (
            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} className="p-3 sm:p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs sm:text-sm font-bold flex items-start sm:items-center gap-2 mb-4 overflow-hidden">
              <XCircle className="w-4 h-4 sm:w-5 sm:h-5 text-red-700 shrink-0 mt-0.5 sm:mt-0" /> <span className="line-clamp-2 sm:line-clamp-none">{error}</span>
            </motion.div>
          )}
          {success && (
            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} className="p-3 sm:p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-xs sm:text-sm font-bold flex items-start sm:items-center gap-2 mb-4 overflow-hidden">
              <CheckCircle2 className="w-4 h-4 sm:w-5 sm:h-5 text-green-700 shrink-0 mt-0.5 sm:mt-0" /> <span className="line-clamp-2 sm:line-clamp-none">{success}</span>
            </motion.div>
          )}
        </AnimatePresence>

        {activeTab === "products" ? (
          <>
            {/* Search Bar */}
            <div className="flex items-center bg-white w-full rounded-2xl px-3 sm:px-5 py-2.5 sm:py-3.5 border border-[var(--border)] focus-within:border-[var(--rust)] shadow-sm transition-all group">
              <Search className="w-4 sm:w-4.5 h-4 sm:h-4.5 text-[var(--muted)] mr-2 sm:mr-4 group-focus-within:text-[var(--rust)] transition-colors shrink-0" />
              <input
                type="text"
                placeholder="Filter by name, category, or seller..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="bg-transparent w-full text-xs sm:text-xs outline-none font-bold placeholder:opacity-50 truncate"
              />
            </div>

            {/* Catalog Table - Responsive Wrapper */}
            <div className="artisan-card p-0 overflow-hidden border-none shadow-xl bg-white/80 backdrop-blur-xl">
              <div className="overflow-x-auto">
                <table className="w-full text-left min-w-max border-collapse">
                  <thead className="bg-[var(--cream)]/40 border-b border-[var(--border)] sticky top-0 backdrop-blur-md">
                    <tr>
                      <th className="px-4 sm:px-5 py-4 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[var(--muted)] whitespace-nowrap">Product</th>
                      <th className="px-5 py-4 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[var(--muted)] whitespace-nowrap">Seller</th>
                      <th className="px-5 py-4 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[var(--muted)] whitespace-nowrap">Status</th>
                      <th className="px-5 py-4 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[var(--muted)] text-right whitespace-nowrap">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[var(--border)]">
                    {loading ? (
                      <tr><td colSpan="4" className="px-4 sm:px-6 lg:px-8 py-8 sm:py-12 text-center text-xs font-bold text-[var(--muted)] animate-pulse uppercase tracking-widest italic">Synchronizing Archives...</td></tr>
                    ) : filteredProducts.length === 0 ? (
                      <tr><td colSpan="4" className="px-4 sm:px-6 lg:px-8 py-8 sm:py-12 text-center text-xs font-bold text-[var(--muted)] uppercase tracking-widest italic">Empty Catalog Registry</td></tr>
                    ) : (
                      filteredProducts.map((product) => (
                        <tr key={product.id} className="group hover:bg-[var(--cream)]/10 transition-colors">
                          <td className="px-4 sm:px-5 py-3 sm:py-4">
                            <div className="flex items-center gap-3 sm:gap-4">
                              <div 
                                className="w-10 sm:w-12 h-10 sm:h-12 rounded-lg sm:rounded-xl overflow-hidden bg-[var(--cream)] border border-[var(--border)] shadow-sm shrink-0 cursor-pointer hover:border-[var(--rust)] transition-all"
                                onClick={() => router.push(`/admin/products/view?id=${product.id}`)}
                              >
                                <img
                                  src={getProductImageSrc(product.image)}
                                  className="w-full h-full object-cover"
                                  alt={product.name}
                                  onError={(e) => { e.target.src = "/images/placeholder.png"; }}
                                />
                              </div>
                              <div className="min-w-0">
                                <div 
                                  className="font-black text-[var(--charcoal)] uppercase tracking-tight text-xs sm:text-sm group-hover:text-[var(--rust)] transition-colors truncate cursor-pointer"
                                  onClick={() => router.push(`/admin/products/view?id=${product.id}`)}
                                >
                                  {product.name}
                                </div>
                                <div className="text-[8px] sm:text-[10px] font-bold text-[var(--muted)] mt-0.5 italic lowercase truncate">Category: {product.category}</div>
                              </div>
                            </div>
                          </td>
                          <td className="px-5 py-3 sm:py-4 whitespace-nowrap">
                            <div className="text-[9px] sm:text-xs font-bold text-[var(--charcoal)] flex items-center gap-2 italic min-w-0">
                              <Store className="w-3 sm:w-3.5 h-3 sm:h-3.5 text-[var(--rust)] shrink-0" />
                              <span className="truncate">{product.seller?.name || "Unknown Seller"}</span>
                            </div>
                          </td>
                          <td className="px-5 py-3 sm:py-4 whitespace-nowrap">
                            <div className="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-1 bg-green-50 text-green-700 text-[8px] sm:text-[9px] font-black uppercase tracking-widest rounded-lg border border-green-100 italic shadow-sm">
                              <CheckCircle2 className="w-2.5 sm:w-3 h-2.5 sm:h-3 shrink-0" /> <span className="hidden sm:inline">Approved</span><span className="sm:hidden">OK</span>
                            </div>
                          </td>
                          <td className="px-5 py-3 sm:py-4 whitespace-nowrap">
                            <div className="flex items-center justify-end gap-1.5 sm:gap-2">
                              <button onClick={() => router.push(`/admin/products/view?id=${product.id}`)} className="p-2 sm:p-2.5 bg-white text-[var(--muted)] hover:text-[var(--rust)] rounded-lg transition-all border border-[var(--border)] shadow-sm hover:border-[var(--rust)]" title="View Listing"><Eye className="w-3.5 sm:w-4 h-3.5 sm:h-4" /></button>
                              <button onClick={() => handleDeleteClick(product)} className="p-2 sm:p-2.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-all border border-red-100 shadow-sm" title="Revoke Listing"><Trash2 className="w-3.5 sm:w-4 h-3.5 sm:h-4" /></button>
                            </div>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </>
        ) : (
          <div className="space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
              <div className="text-center sm:text-left">
                <h3 className="font-serif text-lg font-bold text-[var(--charcoal)] tracking-tighter uppercase">
                  Heritage <span className="text-[var(--rust)] italic lowercase">Categories</span>
                </h3>
                <p className="text-[9px] font-black text-[var(--muted)] opacity-50 uppercase tracking-widest italic mt-1">Manage organized segments of the catalog.</p>
              </div>
              <button
                onClick={() => {
                  setEditingCategory(null);
                  setNewCategory({ name: "", description: "" });
                  setShowCategoryModal(true);
                }}
                className="flex items-center gap-2 px-4 py-2.5 bg-[var(--bark)] text-white rounded-xl text-[9px] sm:text-[10px] font-black uppercase tracking-widest hover:bg-[var(--rust)] transition-all shadow-md"
              >
                <LayoutGrid className="w-3.5 h-3.5" /> Add Category
              </button>
            </div>

            {/* Category Search Bar */}
            <div className="flex items-center bg-white w-full rounded-2xl px-3 sm:px-5 py-2.5 sm:py-3.5 border border-[var(--border)] focus-within:border-[var(--rust)] shadow-sm transition-all group">
              <Search className="w-4 sm:w-4.5 h-4 sm:h-4.5 text-[var(--muted)] mr-2 sm:mr-4 group-focus-within:text-[var(--rust)] transition-colors shrink-0" />
              <input
                type="text"
                placeholder="Search categories by name or description..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="bg-transparent w-full text-xs sm:text-xs outline-none font-bold placeholder:opacity-50 truncate"
              />
            </div>

            <div className="w-full">
              <div className="artisan-card p-0 bg-white/80 backdrop-blur-xl shadow-xl overflow-hidden border-none text-left">
                {/* Desktop Table View */}
                <div className="hidden lg:block overflow-x-auto custom-scrollbar">
                  <table className="w-full min-w-max text-left border-collapse">
                    <thead className="bg-[var(--cream)]/40 border-b border-[var(--border)] sticky top-0 backdrop-blur-md">
                      <tr>
                        <th className="px-6 py-4 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Category Name</th>
                        <th className="px-6 py-4 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Description</th>
                        <th className="px-6 py-4 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic text-right whitespace-nowrap">Action</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[var(--border)]">
                      {filteredCategories.length === 0 ? (
                        <tr><td colSpan="3" className="px-4 sm:px-6 lg:px-8 py-8 sm:py-10 text-center text-xs font-bold text-[var(--muted)] italic uppercase tracking-widest opacity-60">No categories matching "{searchQuery}"</td></tr>
                      ) : (
                        filteredCategories.map(cat => (
                          <tr key={cat.id} className="group hover:bg-[var(--cream)]/10 transition-all">
                            <td className="px-6 py-4 font-black text-[var(--charcoal)] uppercase tracking-tight text-[10px] sm:text-[11px] group-hover:text-[var(--rust)] transition-colors whitespace-nowrap">{cat.name}</td>
                            <td className="px-6 py-4 text-[9px] sm:text-[11px] font-bold text-[var(--muted)] line-clamp-1 sm:line-clamp-2 italic opacity-80">{cat.description || "No classification details provided."}</td>
                            <td className="px-6 py-4 text-right whitespace-nowrap">
                              <div className="flex items-center justify-end gap-1.5 sm:gap-2">
                                <button
                                  onClick={() => handleEditClick(cat)}
                                  className={`p-2 sm:p-2.5 rounded-lg transition-all border ${editingCategory?.id === cat.id ? 'bg-red-50 text-[var(--rust)] border-red-100 shadow-sm' : 'text-[var(--muted)] hover:text-[var(--rust)] border-transparent hover:border-red-100 hover:bg-red-50'}`}
                                  title="Edit Category"
                                >
                                  <Edit2 className="w-3.5 sm:w-4 h-3.5 sm:h-4" />
                                </button>
                                <button
                                  onClick={() => handleDeleteCategory(cat.id)}
                                  className="p-2 sm:p-2.5 text-red-500 hover:bg-red-50 rounded-lg transition-all border border-transparent hover:border-red-100"
                                  title="Delete Category"
                                >
                                  <Trash2 className="w-3.5 sm:w-4 h-3.5 sm:h-4" />
                                </button>
                              </div>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>

                {/* Mobile List View */}
                <div className="lg:hidden divide-y divide-[var(--border)]/30">
                  {filteredCategories.length === 0 ? (
                    <div className="px-6 py-12 text-center text-[10px] font-bold text-[var(--muted)] italic uppercase tracking-widest opacity-50">No results found</div>
                  ) : (
                    filteredCategories.map((cat, idx) => (
                      <motion.div
                        key={cat.id}
                        initial={{ opacity: 0, x: 10 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: idx * 0.05 }}
                        className="p-4 space-y-3"
                      >
                        <div className="flex justify-between items-start">
                          <div className="min-w-0 flex-1">
                            <div className="font-black text-[11px] text-[var(--charcoal)] uppercase tracking-tight truncate">{cat.name}</div>
                            <div className="text-[9px] text-[var(--muted)] font-medium mt-1 line-clamp-2 italic leading-relaxed opacity-80">
                              {cat.description || "No classification details provided."}
                            </div>
                          </div>
                          <div className="flex gap-1.5 ml-4">
                            <button
                              onClick={() => handleEditClick(cat)}
                              className={`p-2 rounded-lg transition-all border ${editingCategory?.id === cat.id ? 'bg-red-50 text-[var(--rust)] border-red-100' : 'bg-gray-50 text-[var(--muted)] border-gray-100 active:bg-white'}`}
                            >
                              <Edit2 className="w-3 h-3" />
                            </button>
                            <button
                              onClick={() => handleDeleteCategory(cat.id)}
                              className="p-2 bg-red-50 text-red-600 rounded-lg border border-red-100 active:bg-white transition-all shadow-sm"
                            >
                              <Trash2 className="w-3 h-3" />
                            </button>
                          </div>
                        </div>
                      </motion.div>
                    ))
                  )}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Category Modal */}
        <AnimatePresence>
          {showCategoryModal && (
            <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={cancelEdit}
                className="absolute inset-0 bg-[var(--charcoal)]/40 backdrop-blur-sm"
              />
              <motion.div
                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                className="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden artisan-card p-0"
              >
                <div className="p-6 sm:p-8 space-y-6">
                  <div className="flex justify-between items-start">
                    <div>
                      <h3 className="font-serif text-xl font-bold text-[var(--charcoal)] tracking-tighter uppercase">
                        {editingCategory ? 'Update' : 'Register'} <span className="text-[var(--rust)] italic lowercase">{editingCategory ? 'Category' : 'New Category'}</span>
                      </h3>
                      <p className="text-[9px] font-black text-[var(--muted)] opacity-50 uppercase tracking-widest italic mt-1">Heritage classification details.</p>
                    </div>
                    <button onClick={cancelEdit} className="p-2 hover:bg-[var(--cream)] rounded-xl transition-all">
                      <XCircle className="w-5 h-5 text-[var(--muted)]" />
                    </button>
                  </div>

                  <form onSubmit={handleAddCategory} className="space-y-4">
                    <div className="space-y-2">
                      <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Category Name</label>
                      <input
                        type="text"
                        autoFocus
                        placeholder="e.g. Pina Barong"
                        value={newCategory.name}
                        onChange={(e) => setNewCategory({ ...newCategory, name: e.target.value })}
                        className="w-full px-4 py-3 bg-[var(--cream)]/30 border border-[var(--border)] focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]/20 rounded-xl outline-none text-[11px] font-black uppercase tracking-wider transition-all shadow-sm"
                      />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Category Description</label>
                      <textarea
                        placeholder="Brief description of this heritage classification..."
                        value={newCategory.description}
                        onChange={(e) => setNewCategory({ ...newCategory, description: e.target.value })}
                        rows="4"
                        className="w-full px-4 py-3 bg-[var(--cream)]/30 border border-[var(--border)] focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]/20 rounded-xl outline-none text-[11px] font-bold transition-all shadow-sm resize-none"
                      />
                    </div>
                    <div className="flex gap-3 pt-2">
                      <button
                        type="button"
                        onClick={cancelEdit}
                        className="flex-1 py-4 bg-[var(--cream)] text-[var(--charcoal)] text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-[var(--border)] transition-all shadow-sm"
                      >
                        Cancel
                      </button>
                      <button type="submit" className="flex-[2] py-4 bg-[var(--rust)] text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-[var(--charcoal)] transition-all shadow-lg active:scale-[0.98]">
                        {editingCategory ? 'Update Category' : 'Save Category'}
                      </button>
                    </div>
                  </form>
                </div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>
        <ConfirmationModal
          isOpen={showDeleteModal}
          onClose={() => setShowDeleteModal(false)}
          onConfirm={confirmDelete}
          title="Remove Product Listing?"
          message={`Are you sure you want to remove "${deleteTarget?.name}" from the global marketplace? This action will notify the seller and cannot be undone.`}
          confirmText={isDeleting ? "Removing..." : "Remove & Notify"}
          type="danger"
        >
          <div className="mt-4">
            <label className="text-[10px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Reason for Removal</label>
            <textarea
              value={deleteReason}
              onChange={(e) => setDeleteReason(e.target.value)}
              placeholder="e.g. Inappropriate content, Policy violation, Prohibited item..."
              className="w-full mt-2 p-4 bg-[var(--cream)]/30 border border-[var(--border)] rounded-2xl text-xs font-medium outline-none focus:border-red-500 transition-all min-h-[100px] resize-none"
            />
          </div>
        </ConfirmationModal>
      </div>
    </AdminLayout>
  );
}
