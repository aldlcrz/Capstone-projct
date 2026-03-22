"use client";
import React, { useState, useEffect } from "react";
import AdminLayout from "@/components/AdminLayout";
import { ShoppingBag, Search, Filter, TrendingUp, Store, Package, Trash2, Eye, LayoutGrid, List as ListIcon, CheckCircle2 } from "lucide-react";
import { motion } from "framer-motion";
import { api, BACKEND_URL } from "@/lib/api";
import { io } from "socket.io-client";

export default function AdminProducts() {
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("products"); // "products" or "categories"
  const [newCategory, setNewCategory] = useState({ name: "", description: "" });
  const [searchQuery, setSearchQuery] = useState("");

  const fetchProducts = async () => {
    try {
      const res = await api.get("/products");
      setProducts(res.data);
    } catch (err) {
      console.warn("Failed to fetch products", err.message || "Backend offline");
      setProducts([]); // Fallback to empty array
    }
  };

  const fetchCategories = async () => {
    try {
      const res = await api.get("/categories");
      setCategories(res.data);
    } catch (err) {
      console.warn("Failed to fetch categories", err.message || "Backend offline");
      setCategories([]); // Fallback to empty array
    }
  };

  const fetchData = async () => {
    setLoading(true);
    await Promise.all([fetchProducts(), fetchCategories()]);
    setLoading(false);
  };

  useEffect(() => {
    fetchData();
    const socket = io(BACKEND_URL);
    socket.on("stats_update", fetchData);
    return () => socket.disconnect();
  }, []);

  const deleteProduct = async (id) => {
    if(!confirm("Are you sure you want to remove this product from the global marketplace?")) return;
    try {
      await api.delete(`/products/${id}`);
      fetchData();
    } catch (err) {
      alert("Failed to delete product.");
    }
  };

  const handleAddCategory = async (e) => {
    e.preventDefault();
    if (!newCategory.name) return;
    try {
      await api.post("/categories", newCategory);
      setNewCategory({ name: "", description: "" });
      fetchCategories();
    } catch (err) {
      alert(err.response?.data?.message || "Failed to add category");
    }
  };

  const handleDeleteCategory = async (id) => {
    if (!confirm("Are you sure? This will only work if no products belong to this category.")) return;
    try {
      await api.delete(`/categories/${id}`);
      fetchCategories();
    } catch (err) {
      alert(err.response?.data?.message || "Failed to delete category");
    }
  };

  const filteredProducts = products.filter(p => 
    p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    p.category?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    p.seller?.name?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <AdminLayout>
      <div className="space-y-8 mb-20">
        {/* Page Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-2">
          <div>
            <h1 className="font-serif text-3xl font-black tracking-tighter text-[var(--charcoal)] uppercase">
              Central <span className="text-[var(--rust)] italic lowercase">Catalog</span>
            </h1>
            <p className="text-xs font-bold text-[var(--muted)] opacity-60 uppercase tracking-widest mt-1">
              Overseeing all heritage collections and segments.
            </p>
          </div>
          <div className="flex items-center p-1.5 bg-white rounded-2xl border border-[var(--border)] shadow-sm">
             <button 
               onClick={() => setActiveTab("products")} 
               className={`flex items-center gap-2 px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${activeTab === "products" ? 'bg-red-50 text-[var(--rust)] shadow-sm ring-1 ring-red-100' : 'text-[var(--muted)] hover:bg-[var(--cream)]'}`}
             >
                <Package className="w-3.5 h-3.5" /> All Products
             </button>
             <button 
               onClick={() => setActiveTab("categories")} 
               className={`flex items-center gap-2 px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${activeTab === "categories" ? 'bg-red-50 text-[var(--rust)] shadow-sm ring-1 ring-red-100' : 'text-[var(--muted)] hover:bg-[var(--cream)]'}`}
             >
                <LayoutGrid className="w-3.5 h-3.5" /> Categories
             </button>
          </div>
        </div>

        {activeTab === "products" ? (
          <>
            {/* Search Bar */}
            <div className="flex items-center bg-white w-full max-w-xl rounded-2xl px-5 py-3.5 border border-[var(--border)] focus-within:border-[var(--rust)] shadow-sm transition-all group">
                <Search className="w-4.5 h-4.5 text-[var(--muted)] mr-4 group-focus-within:text-[var(--rust)] transition-colors" />
                <input 
                  type="text" 
                  placeholder="Filter by name, category, or artisan..." 
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="bg-transparent w-full text-xs outline-none font-bold placeholder:opacity-50" 
                />
            </div>

            {/* Catalog Table */}
            <div className="artisan-card p-0 overflow-hidden border-none shadow-xl bg-white/80 backdrop-blur-xl">
               <table className="w-full text-left">
                  <thead className="bg-[var(--cream)]/30 border-b border-[var(--border)]">
                     <tr>
                        <th className="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--muted)]">Product</th>
                        <th className="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--muted)]">Artisan</th>
                        <th className="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--muted)]">Status</th>
                        <th className="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--muted)] text-right">Actions</th>
                     </tr>
                  </thead>
                  <tbody className="divide-y divide-[var(--border)]">
                     {loading ? (
                       <tr><td colSpan="4" className="px-8 py-12 text-center text-xs font-bold text-[var(--muted)] animate-pulse uppercase tracking-widest italic">Synchronizing Archives...</td></tr>
                     ) : filteredProducts.length === 0 ? (
                       <tr><td colSpan="4" className="px-8 py-12 text-center text-xs font-bold text-[var(--muted)] uppercase tracking-widest italic">Empty Catalog Registry</td></tr>
                     ) : (
                       filteredProducts.map((product) => (
                         <tr key={product.id} className="group hover:bg-[var(--cream)]/10 transition-colors">
                            <td className="px-8 py-6">
                               <div className="flex items-center gap-5">
                                  <div className="w-14 h-14 rounded-xl overflow-hidden bg-[var(--cream)] border border-[var(--border)] shadow-sm shrink-0">
                                     {product.image?.[0] ? <img src={product.image[0]} className="w-full h-full object-cover" /> : <Package className="w-6 h-6 text-[var(--muted)] m-4" />}
                                  </div>
                                  <div>
                                     <div className="font-black text-[var(--charcoal)] uppercase tracking-tight text-sm group-hover:text-[var(--rust)] transition-colors">{product.name}</div>
                                     <div className="text-[10px] font-bold text-[var(--muted)] mt-0.5 italic lowercase">Category: {product.category}</div>
                                  </div>
                               </div>
                            </td>
                            <td className="px-8 py-6">
                               <div className="text-xs font-bold text-[var(--charcoal)] flex items-center gap-2 italic">
                                  <Store className="w-3.5 h-3.5 text-[var(--rust)]" />
                                  {product.seller?.name || "Unknown Artisan"}
                               </div>
                            </td>
                            <td className="px-8 py-6">
                               <div className="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 text-[9px] font-black uppercase tracking-widest rounded-lg border border-green-100 italic shadow-sm">
                                  <CheckCircle2 className="w-3 h-3" /> Approved
                               </div>
                            </td>
                            <td className="px-8 py-6 text-right">
                               <div className="flex items-center justify-end gap-2">
                                  <button onClick={() => window.open(`/products/${product.id}`, '_blank')} className="p-2.5 bg-white text-[var(--muted)] hover:text-[var(--rust)] rounded-lg transition-all border border-[var(--border)] shadow-sm hover:border-[var(--rust)]" title="View Listing"><Eye className="w-4 h-4" /></button>
                                  <button onClick={() => deleteProduct(product.id)} className="p-2.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-all border border-red-100 shadow-sm" title="Revoke Listing"><Trash2 className="w-4 h-4" /></button>
                               </div>
                            </td>
                         </tr>
                       ))
                     )}
                  </tbody>
               </table>
            </div>
          </>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
             {/* Add Category Form */}
             <div className="lg:col-span-1">
                <div className="artisan-card p-8 bg-white shadow-xl space-y-6">
                   <h3 className="font-serif text-xl font-bold text-[var(--charcoal)] tracking-tight uppercase">Register <span className="text-[var(--rust)] italic lowercase">New Category</span></h3>
                   <form onSubmit={handleAddCategory} className="space-y-4">
                      <div className="space-y-2">
                         <label className="text-[10px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Category Name</label>
                         <input 
                           type="text" 
                           placeholder="e.g. Pina Barong"
                           value={newCategory.name}
                           onChange={(e) => setNewCategory({...newCategory, name: e.target.value})}
                           className="w-full px-5 py-3 bg-[var(--cream)]/30 border-2 border-transparent focus:border-[var(--rust)] rounded-xl outline-none text-xs font-bold transition-all shadow-inner"
                         />
                      </div>
                      <div className="space-y-2">
                         <label className="text-[10px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Segment Description</label>
                         <textarea 
                           placeholder="Brief description of this heritage segment..."
                           value={newCategory.description}
                           onChange={(e) => setNewCategory({...newCategory, description: e.target.value})}
                           rows="3"
                           className="w-full px-5 py-3 bg-[var(--cream)]/30 border-2 border-transparent focus:border-[var(--rust)] rounded-xl outline-none text-xs font-bold transition-all shadow-inner resize-none"
                         />
                      </div>
                      <button type="submit" className="w-full py-4 bg-[var(--rust)] text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:opacity-90 transition-all shadow-lg active:scale-[0.98]">
                         Add to Registry
                      </button>
                   </form>
                </div>
             </div>

             {/* Categories List */}
             <div className="lg:col-span-2">
                <div className="artisan-card p-0 bg-white shadow-xl overflow-hidden border-none text-left">
                   <table className="w-full">
                      <thead className="bg-[var(--cream)]/30 border-b border-[var(--border)]">
                         <tr>
                            <th className="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--muted)]">Segment Name</th>
                            <th className="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--muted)]">Description</th>
                            <th className="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--muted)] text-right">Action</th>
                         </tr>
                      </thead>
                      <tbody className="divide-y divide-[var(--border)]">
                         {categories.length === 0 ? (
                            <tr><td colSpan="3" className="px-8 py-10 text-center text-xs font-bold text-[var(--muted)] italic">No segments registered yet.</td></tr>
                         ) : (
                            categories.map(cat => (
                               <tr key={cat.id} className="group hover:bg-[var(--cream)]/5 transition-colors">
                                  <td className="px-8 py-6 font-black text-[var(--charcoal)] uppercase tracking-tight text-xs group-hover:text-[var(--rust)]">{cat.name}</td>
                                  <td className="px-8 py-6 text-[10px] font-bold text-[var(--muted)] line-clamp-1 italic">{cat.description || "No description provided."}</td>
                                  <td className="px-8 py-6 text-right">
                                     <button onClick={() => handleDeleteCategory(cat.id)} className="p-2.5 text-red-500 hover:bg-red-50 rounded-lg transition-all border border-transparent hover:border-red-100"><Trash2 className="w-4 h-4" /></button>
                                  </td>
                               </tr>
                            ))
                         )}
                      </tbody>
                   </table>
                </div>
             </div>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}
