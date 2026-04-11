"use client";
import React, { useState, useEffect } from "react";
import SellerLayout from "./SellerLayout";
import { Upload, Plus, X, Loader2, ArrowLeft, Save, Camera, Image as ImageIcon } from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";
import Link from "next/link";
import { useSearchParams, useRouter } from "next/navigation";
import { Camera as CapCamera, CameraResultType } from "@capacitor/camera";
import { Capacitor } from "@capacitor/core";

export default function EditProductClient() {
  const searchParams = useSearchParams();
  const id = searchParams.get("id");
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price: "",
    categories: [],
    sizes: [],
    stock: "",
    shippingFee: "",
    shippingDays: "",
    images: [], // Existing images {url, variation}
    variations: [] // New format if any
  });

  const [newVariations, setNewVariations] = useState([]);
  const [categoriesList, setCategoriesList] = useState([]);

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const res = await api.get("/categories");
        setCategoriesList(res.data);
      } catch (err) {
        setCategoriesList([
          { name: "Formal" },
          { name: "Casual" },
          { name: "Traditional" },
          { name: "Modern Elite" }
        ]);
      }
    };
    fetchCategories();
  }, []);

  useEffect(() => {
    if (!id) {
      setLoading(false);
      return;
    }

    const fetchProduct = async () => {
      try {
        const res = await api.get(`/products/${id}`);
        const p = res.data;
        setFormData({
          name: p.name || "",
          description: p.description || "",
          price: p.price || "",
          categories: Array.isArray(p.categories) ? p.categories : [],
          sizes: Array.isArray(p.sizes) ? p.sizes : [],
          stock: p.stock || "",
          shippingFee: p.shippingFee || "",
          shippingDays: p.shippingDays || "",
          images: Array.isArray(p.image) ? p.image : (p.image ? JSON.parse(p.image) : [])
        });
      } catch (err) {
        console.error("Failed to fetch product details");
      } finally {
        setLoading(false);
      }
    };
    fetchProduct();
  }, [id]);

  if (loading) {
    return (
      <SellerLayout>
        <div className="py-24 text-center text-[var(--muted)] animate-pulse italic">Loading product editor...</div>
      </SellerLayout>
    );
  }

  if (!id) {
    return (
      <SellerLayout>
        <div className="space-y-6">
          <Link href="/seller/inventory" className="inline-flex items-center gap-2 text-sm text-[var(--rust)] hover:underline">
            <ArrowLeft className="w-4 h-4" />
            Back to Inventory
          </Link>
          <div className="py-20 text-center text-[var(--muted)]">
            Select a product from inventory before opening the editor.
          </div>
        </div>
      </SellerLayout>
    );
  }

  const handleAddVariation = () => {
    setNewVariations([...newVariations, { file: null, preview: null, variation: "" }]);
  };

  const handleRemoveNewVariation = (index) => {
    setNewVariations(newVariations.filter((_, i) => i !== index));
  };

  const handleNewVariationChange = (index, field, value) => {
    const updated = [...newVariations];
    updated[index][field] = value;
    setNewVariations(updated);
  };

  const handleFileChange = (index, e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        const updated = [...newVariations];
        updated[index].file = file;
        updated[index].preview = reader.result;
        setNewVariations(updated);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleCameraCapture = async (index) => {
    if (!Capacitor.isNativePlatform()) {
      alert("Camera is only available on native mobile devices.");
      return;
    }

    try {
      const image = await CapCamera.getPhoto({
        quality: 90,
        allowEditing: true,
        resultType: CameraResultType.Uri
      });

      const response = await fetch(image.webPath);
      const blob = await response.blob();
      const file = new File([blob], `camera_photo_${Date.now()}.jpg`, { type: "image/jpeg" });

      const updated = [...newVariations];
      updated[index].file = file;
      updated[index].preview = image.webPath;
      setNewVariations(updated);
    } catch (err) {
      console.error("Camera capture failed:", err);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (Number(formData.price) <= 0 || Number(formData.price) > 10000) {
      alert("Price must be between 1 and 10,000 PHP.");
      setSaving(false);
      return;
    }
    if (Number(formData.stock) < 0 || Number(formData.stock) > 500) {
      alert("Stock quantity must be between 0 and 500 units.");
      setSaving(false);
      return;
    }
    if (formData.shippingFee && (Number(formData.shippingFee) < 0 || Number(formData.shippingFee) > 500)) {
      alert("Shipping fee cannot exceed 500 PHP.");
      setSaving(false);
      return;
    }
    if (formData.shippingDays && (Number(formData.shippingDays) < 1 || Number(formData.shippingDays) > 30)) {
      alert("Shipping days must be between 1 and 30 days.");
      setSaving(false);
      return;
    }

    setSaving(true);
    try {
      const form = new FormData();
      form.append("name", formData.name);
      form.append("description", formData.description);
      form.append("price", formData.price);
      form.append("categories", JSON.stringify(formData.categories));
      form.append("sizes", JSON.stringify(formData.sizes));
      form.append("stock", formData.stock);
      form.append("shippingFee", formData.shippingFee || 0);
      form.append("shippingDays", formData.shippingDays || 3);
      
      // Keep track of which variations are being added
      const variationNames = newVariations.map(v => v.variation);
      form.append("variationNames", JSON.stringify(variationNames));
      form.append("existingImages", JSON.stringify(formData.images));

      newVariations.forEach((v) => {
        if (v.file) {
          form.append("images", v.file);
        }
      });

      await api.put(`/products/${id}`, form, {
        headers: { "Content-Type": "multipart/form-data" }
      });
      
      alert("Product updated successfully!");
      router.push("/seller/inventory");
    } catch (err) {
      console.error("Update failed", err);
      alert("Failed to update product.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <SellerLayout>
      <div className="max-w-5xl mx-auto mb-20 animate-fade-in">
        <div className="flex items-center justify-between mb-10">
          <Link href="/seller/inventory" className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] transition-colors group">
            <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
            Back to Inventory
          </Link>
          <h1 className="font-serif text-3xl font-bold text-[var(--charcoal)] tracking-tight">Edit <span className="text-[var(--rust)] italic">Masterpiece</span></h1>
        </div>

        <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-12 gap-10">
          <div className="lg:col-span-12">
            <div className="artisan-card p-10 space-y-8 shadow-xl">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Masterpiece Name</label>
                    <input 
                      type="text" 
                      value={formData.name}
                      maxLength={100}
                      onChange={(e) => setFormData({...formData, name: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="e.g., Premium Lumban Silk Barong"
                      required
                    />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Valuation (PHP)</label>
                    <input 
                      type="number" 
                      min="0.01"
                      max="10000"
                      step="0.01"
                      value={formData.price}
                      onChange={(e) => setFormData({...formData, price: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="Max ₱10,000"
                      required
                    />
                </div>
                <div className="space-y-4">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-2">Categories</label>
                  <div className="flex flex-wrap gap-2 mb-3">
                    {formData.categories.map((cat, idx) => (
                      <motion.div
                        initial={{ scale: 0.8, opacity: 0 }}
                        animate={{ scale: 1, opacity: 1 }}
                        key={idx}
                        className="flex items-center gap-2 px-4 py-2 bg-[var(--rust)] text-white rounded-full text-sm font-bold shadow-md shadow-red-900/10"
                      >
                        {cat}
                        <button
                          type="button"
                          onClick={() => setFormData({ ...formData, categories: formData.categories.filter(c => c !== cat) })}
                          className="hover:bg-white/20 rounded-full p-0.5 transition-colors"
                        >
                          <X className="w-3 h-3" />
                        </button>
                      </motion.div>
                    ))}
                  </div>

                  <div className="relative group">
                    <select
                      className="w-full px-6 py-4 bg-white border-2 border-[var(--rust)]/60 rounded-2xl focus:outline-none focus:border-[var(--rust)] transition-all font-serif text-lg font-bold text-[var(--charcoal)] appearance-none cursor-pointer shadow-lg shadow-red-900/5 group-hover:border-[var(--rust)]"
                      onChange={(e) => {
                        const val = e.target.value;
                        if (val && !formData.categories.includes(val)) {
                          setFormData({ ...formData, categories: [...formData.categories, val] });
                        }
                        e.target.value = ""; // Reset
                      }}
                    >
                      <option value="">+ Select Category Sector </option>
                      {categoriesList.map((cat, idx) => (
                        <option key={idx} value={cat.name} disabled={formData.categories.includes(cat.name)}>
                          {cat.name}
                        </option>
                      ))}
                    </select>
                    <div className="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-[var(--rust)]">
                      <Plus className="w-5 h-5 rotate-45" />
                    </div>
                  </div>
                </div>

                <div className="space-y-3 col-span-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-2">Heritage Sizing Available</label>
                  <div className="flex flex-wrap gap-2">
                    {["S", "M", "L", "XL", "XXL"].map(size => (
                      <button
                        key={size}
                        type="button"
                        onClick={() => {
                          const newSizes = formData.sizes.includes(size)
                            ? formData.sizes.filter(s => s !== size)
                            : [...formData.sizes, size];
                          setFormData({ ...formData, sizes: newSizes });
                        }}
                        className={`px-4 py-2 rounded-lg text-xs font-bold transition-all border-2 ${formData.sizes.includes(size) ? 'bg-[var(--rust)] text-white border-[var(--rust)] shadow-lg' : 'bg-[var(--input-bg)] text-[var(--muted)] border-[var(--border)] hover:border-[var(--rust)]'}`}
                      >
                        {size}
                      </button>
                    ))}
                  </div>
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Inventory Level</label>
                    <input 
                      type="number" 
                      min="0"
                      max="500"
                      step="1"
                      value={formData.stock}
                      onChange={(e) => setFormData({...formData, stock: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="Max 500"
                    />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Shipping Fee (PHP)</label>
                    <input 
                      type="number" 
                      min="0"
                      max="500"
                      step="0.01"
                      value={formData.shippingFee}
                      onChange={(e) => setFormData({...formData, shippingFee: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="Max ₱500"
                    />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Shipping Days</label>
                    <input 
                      type="number" 
                      min="1"
                      max="30"
                      step="1"
                      value={formData.shippingDays}
                      onChange={(e) => setFormData({...formData, shippingDays: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="Max 30 days"
                    />
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Artisan Narrative</label>
                  <textarea 
                    value={formData.description}
                    maxLength={2000}
                    onChange={(e) => setFormData({...formData, description: e.target.value})}
                    className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all min-h-[150px] resize-none placeholder:opacity-30"
                    placeholder="Describe the craftsmanship and materials of this piece..."
                    required
                  />
              </div>

              {formData.images && formData.images.length > 0 && (
                <div className="space-y-6 pt-4 border-b border-[var(--border)] pb-8">
                  <div className="flex items-center justify-between">
                     <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Existing Portfolio Variations</label>
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    {formData.images.map((img, i) => {
                      // Normalize the image URL if it's an object from the backend
                      const imgUrl = (typeof img === 'string') ? img : (img.url ? (img.url.url || img.url) : "");
                      
                      // Sometimes backend sends absolute URLs that are fine, 
                      // or relative paths without the backend domain. The native img tag in frontend 
                      // might need getProductImageSrc if it's not absolute.
                      // Since we relaxed CORS, the raw url from backend is perfectly fine if it's absolute.
                      // If it's a relative path starting with /uploads, we can prefix it or just let the proxy handle it.
                      // Actually, next.config.mjs allows it, but regular img tags will request to localhost:3000/uploads
                      // which will fail!
                      // Let's import getProductImageSrc and use it or just construct it:
                      
                      const resolvedUrl = imgUrl.startsWith('http') ? imgUrl : `http://127.0.0.1:5000${imgUrl}`;
                      
                      return (
                      <div key={i} className="bg-[var(--input-bg)] p-4 rounded-[2rem] border border-[var(--border)] relative space-y-4 animate-fade-in">
                        <button 
                          type="button"
                          onClick={() => {
                            const newImages = [...formData.images];
                            newImages.splice(i, 1);
                            setFormData({...formData, images: newImages});
                          }}
                          className="absolute -top-2 -right-2 w-8 h-8 bg-white rounded-full shadow-lg border border-[var(--border)] flex items-center justify-center text-[var(--muted)] hover:text-red-500 transition-colors z-10"
                        >
                          <X className="w-4 h-4" />
                        </button>
                        <div className="aspect-[4/5] bg-white rounded-2xl overflow-hidden relative border border-[var(--border)]">
                          <img src={resolvedUrl} alt="Existing Variation" className="w-full h-full object-cover" />
                        </div>
                        <input 
                          type="text"
                          value={(typeof img === 'string' ? "Variation" : img.variation) || ""}
                          onChange={(e) => {
                             const newImages = [...formData.images];
                             if (typeof newImages[i] === 'string') {
                               newImages[i] = { url: newImages[i], variation: e.target.value };
                             } else {
                               newImages[i] = { ...newImages[i], variation: e.target.value };
                             }
                             setFormData({...formData, images: newImages});
                          }}
                          className="w-full bg-white border-none rounded-xl p-3 text-[11px] font-bold uppercase tracking-wider text-center shadow-sm"
                          placeholder="Variation Name"
                        />
                      </div>
                    )})}
                  </div>
                </div>
              )}

              <div className="space-y-6 pt-4">
                <div className="flex items-center justify-between">
                   <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">New Portfolio Variations</label>
                   <button 
                     type="button" 
                     onClick={handleAddVariation}
                     className="flex items-center gap-2 text-[9px] font-bold uppercase tracking-widest text-[var(--rust)] hover:opacity-70 transition-opacity"
                   >
                     <Plus className="w-3.5 h-3.5" /> Add Variation
                   </button>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {newVariations.map((v, i) => (
                    <motion.div 
                      key={i}
                      initial={{ opacity: 0, scale: 0.9 }}
                      animate={{ opacity: 1, scale: 1 }}
                      className="bg-[var(--input-bg)] p-4 rounded-[2rem] border border-[var(--border)] relative space-y-4"
                    >
                      <button 
                        type="button"
                        onClick={() => handleRemoveNewVariation(i)}
                        className="absolute -top-2 -right-2 w-8 h-8 bg-white rounded-full shadow-lg border border-[var(--border)] flex items-center justify-center text-[var(--muted)] hover:text-red-500 transition-colors z-10"
                      >
                        <X className="w-4 h-4" />
                      </button>

                      <div className="aspect-[4/5] bg-white rounded-2xl overflow-hidden relative border border-[var(--border)] group">
                        {v.preview ? (
                          <img src={v.preview} alt="Variation preview" className="w-full h-full object-cover" />
                        ) : (
                          <div className="w-full h-full flex flex-col items-center justify-center text-[var(--muted)] space-y-2">
                            <ImageIcon className="w-8 h-8 opacity-20" />
                            <span className="text-[9px] font-bold uppercase tracking-widest opacity-40">Empty Frame</span>
                          </div>
                        )}
                        <input 
                          type="file" 
                          id={`file-${i}`} 
                          className="hidden" 
                          onChange={(e) => handleFileChange(i, e)} 
                          accept="image/*"
                        />
                        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                           <button 
                             type="button" 
                             onClick={() => document.getElementById(`file-${i}`).click()}
                             className="w-10 h-10 bg-white rounded-full flex items-center justify-center text-[var(--charcoal)] hover:scale-110 transition-transform"
                           >
                              <Upload className="w-4 h-4" />
                           </button>
                           {Capacitor.isNativePlatform() && (
                             <button 
                               type="button" 
                               onClick={() => handleCameraCapture(i)}
                               className="w-10 h-10 bg-[var(--rust)] rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform"
                             >
                                <Camera className="w-4 h-4" />
                             </button>
                           )}
                        </div>
                      </div>

                      <input 
                        type="text"
                        value={v.variation}
                        onChange={(e) => handleNewVariationChange(i, "variation", e.target.value)}
                        className="w-full bg-white border-none rounded-xl p-3 text-[11px] font-bold uppercase tracking-wider text-center focus:ring-1 focus:ring-[var(--rust)]/20 shadow-sm"
                        placeholder="Variation Name"
                      />
                    </motion.div>
                  ))}
                </div>
              </div>

              <div className="pt-8 border-t border-[var(--border)]">
                <button 
                  type="submit" 
                  disabled={saving}
                  className="w-full btn-primary py-6 rounded-3xl font-serif text-lg font-bold flex items-center justify-center gap-3 shadow-2xl disabled:opacity-50"
                >
                  {saving ? (
                    <Loader2 className="w-6 h-6 animate-spin" />
                  ) : (
                    <>
                      <Save className="w-5 h-5" />
                      Commit Changes to Registry
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </SellerLayout>
  );
}
