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
    category: "",
    stock: "",
    shippingFee: "",
    shippingDays: "",
    images: [], // Existing images {url, variation}
    variations: [] // New format if any
  });

  const [newVariations, setNewVariations] = useState([]);

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
          category: p.category || "",
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
    setSaving(true);
    try {
      const form = new FormData();
      form.append("name", formData.name);
      form.append("description", formData.description);
      form.append("price", formData.price);
      form.append("category", formData.category);
      form.append("stock", formData.stock);
      form.append("shippingFee", formData.shippingFee || 0);
      form.append("shippingDays", formData.shippingDays || 3);
      
      // Keep track of which variations are being added
      const variationNames = newVariations.map(v => v.variation);
      form.append("variationNames", JSON.stringify(variationNames));

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
                      step="0.01"
                      value={formData.price}
                      onChange={(e) => setFormData({...formData, price: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="0.00"
                      required
                    />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Category</label>
                  <select 
                    value={formData.category}
                    onChange={(e) => setFormData({...formData, category: e.target.value})}
                    className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all cursor-pointer"
                    required
                  >
                    <option value="">Select Category</option>
                    <option value="Barong Tagalog">Barong Tagalog</option>
                    <option value="Filipiniana">Filipiniana</option>
                    <option value="Accessories">Accessories</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Inventory Level</label>
                    <input 
                      type="number" 
                      min="0"
                      step="1"
                      value={formData.stock}
                      onChange={(e) => setFormData({...formData, stock: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="Quantity in stock"
                    />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Shipping Fee (PHP)</label>
                    <input 
                      type="number" 
                      min="0"
                      step="0.01"
                      value={formData.shippingFee}
                      onChange={(e) => setFormData({...formData, shippingFee: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="0.00"
                    />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Shipping Days</label>
                    <input 
                      type="number" 
                      min="1"
                      step="1"
                      value={formData.shippingDays}
                      onChange={(e) => setFormData({...formData, shippingDays: e.target.value})}
                      className="w-full bg-[var(--input-bg)] border-none rounded-2xl p-5 text-sm font-medium focus:ring-2 focus:ring-[var(--rust)]/20 transition-all placeholder:opacity-30"
                      placeholder="Estimated delivery days"
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
