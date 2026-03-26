"use client";
import React, { useState } from "react";
import SellerLayout from "@/components/SellerLayout";
import { Upload, Plus, X, Loader2, ArrowRight, Camera, Image as ImageIcon } from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";
import { Camera as CapCamera, CameraResultType } from "@capacitor/camera";
import { Capacitor } from "@capacitor/core";

export default function AddProductPage() {
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price: "",
    category: "Formal",
    stock: "",
    sizes: ["S", "M", "L", "XL"],
    shippingFee: "",
    shippingDays: ""
  });
  const [variations, setVariations] = useState([]); // Array of { file, label, preview }
  const [categories, setCategories] = useState([]);
  const [fetchingCategories, setFetchingCategories] = useState(true);

  React.useEffect(() => {
    const fetchCategories = async () => {
      try {
        const res = await api.get("/categories");
        setCategories(res.data);
      } catch (err) {
        console.error("Failed to fetch categories");
        setCategories([
          { name: "Formal" },
          { name: "Casual" },
          { name: "Traditional" },
          { name: "Modern Elite" }
        ]);
      } finally {
        setFetchingCategories(false);
      }
    };
    fetchCategories();
  }, []);

  const handleImageChange = (e) => {
    const files = Array.from(e.target.files);
    const newVariations = files.map(file => ({
      file,
      label: "",
      preview: URL.createObjectURL(file)
    }));
    setVariations([...variations, ...newVariations]);
  };

  const handleCameraCapture = async () => {
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
      const file = new File([blob], `camera-photo-${Date.now()}.jpg`, { type: 'image/jpeg' });

      setVariations([...variations, {
        file,
        label: "",
        preview: URL.createObjectURL(file)
      }]);
    } catch (error) {
      console.error("Camera Capture Failed:", error);
    }
  };

  const removeVariation = (index) => {
    const newVariations = [...variations];
    URL.revokeObjectURL(newVariations[index].preview);
    newVariations.splice(index, 1);
    setVariations(newVariations);
  };

  const updateVariationLabel = (index, label) => {
    const newVariations = [...variations];
    newVariations[index].label = label;
    setVariations(newVariations);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (variations.length === 0) {
      alert("Please upload at least one product variation image.");
      return;
    }
    if (Number(formData.price) <= 0) {
      alert("Price must be a positive number.");
      return;
    }
    if (Number(formData.stock) < 0) {
      alert("Stock cannot be negative.");
      return;
    }
    setLoading(true);

    const data = new FormData();
    data.append('name', formData.name);
    data.append('description', formData.description);
    data.append('price', formData.price);
    data.append('category', formData.category);
    data.append('stock', formData.stock);
    data.append('sizes', JSON.stringify(formData.sizes));
    data.append('shippingFee', formData.shippingFee || 0);
    data.append('shippingDays', formData.shippingDays || 3);
    
    variations.forEach((v) => {
      data.append('images', v.file);
    });
    data.append('variationNames', JSON.stringify(variations.map(v => v.label || "Original")));

    try {
      await api.post("/products", data, {
        headers: { 
          'Content-Type': 'multipart/form-data',
          Authorization: `Bearer ${localStorage.getItem('token')}`
        }
      });
      alert("Product listed successfully!");
      window.location.href = "/seller/inventory";
    } catch (error) {
      alert(error.response?.data?.message || "Failed to list product.");
    } finally {
      setLoading(false);
    }
  };
  return (
    <SellerLayout>
      <div className="max-w-4xl mx-auto space-y-10">
        <div>
          <div className="eyebrow">Inventory Management</div>
          <h1 className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-[var(--charcoal)]">
            Create New <span className="text-[var(--rust)] italic lowercase">Listing</span>
          </h1>
        </div>

        <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-10">
          <div className="space-y-8">
            <div className="artisan-card space-y-6">
              <h3 className="text-lg font-bold">Base Information</h3>
              <div className="space-y-2">
                <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Product Name</label>
                <input 
                  type="text" 
                  required
                  maxLength={100}
                  className="w-full px-4 py-3 bg-[var(--input-bg)] border border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--rust)] transition-all"
                  placeholder="e.g. Pina-Silk Formal Barong"
                  value={formData.name}
                  onChange={(e) => setFormData({...formData, name: e.target.value})}
                />
              </div>
              <div className="space-y-2">
                <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Description</label>
                <textarea 
                  required
                  maxLength={2000}
                  rows={4}
                  className="w-full px-4 py-3 bg-[var(--input-bg)] border border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--rust)] transition-all resize-none"
                  placeholder="Describe the artisan craft, materials, and history..."
                  value={formData.description}
                  onChange={(e) => setFormData({...formData, description: e.target.value})}
                />
              </div>
            </div>

            <div className="artisan-card space-y-6">
              <h3 className="text-lg font-bold">Pricing & Stock</h3>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Price (₱)</label>
                  <input 
                    type="number" 
                    required
                    min="1"
                    step="0.01"
                    className="w-full px-4 py-3 bg-[var(--input-bg)] border border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--rust)] transition-all"
                    placeholder="2500"
                    value={formData.price}
                    onChange={(e) => setFormData({...formData, price: e.target.value})}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Stock Quantity</label>
                  <input 
                    type="number" 
                    required
                    min="0"
                    step="1"
                    className="w-full px-4 py-3 bg-[var(--input-bg)] border border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--rust)] transition-all"
                    placeholder="10"
                    value={formData.stock}
                    onChange={(e) => setFormData({...formData, stock: e.target.value})}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Shipping Fee (₱)</label>
                  <input 
                    type="number" 
                    min="0"
                    step="0.01"
                    className="w-full px-4 py-3 bg-[var(--input-bg)] border border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--rust)] transition-all"
                    placeholder="0"
                    value={formData.shippingFee}
                    onChange={(e) => setFormData({...formData, shippingFee: e.target.value})}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Shipping Days</label>
                  <input 
                    type="number" 
                    min="1"
                    step="1"
                    className="w-full px-4 py-3 bg-[var(--input-bg)] border border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--rust)] transition-all"
                    placeholder="3–7 days"
                    value={formData.shippingDays}
                    onChange={(e) => setFormData({...formData, shippingDays: e.target.value})}
                  />
                </div>
              </div>
            </div>
          </div>

          <div className="space-y-8">
            <div className="artisan-card space-y-6">
              <h3 className="text-lg font-bold">Variations & Media</h3>
              
              <div className="space-y-4">
                {variations.map((v, index) => (
                  <div key={index} className="flex flex-col sm:flex-row gap-4 p-4 border border-[var(--border)] rounded-2xl bg-[var(--cream)]/30 group relative">
                    <div className="w-20 h-20 relative rounded-xl overflow-hidden shrink-0 shadow-sm">
                      <img src={v.preview} alt="Preview" className="w-full h-full object-cover" />
                    </div>
                    <div className="flex-1 space-y-2">
                      <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Variation Label</label>
                      <input 
                        type="text"
                        placeholder="e.g. Classic White"
                        className="w-full px-3 py-2 bg-white border border-[var(--border)] rounded-lg text-sm focus:outline-none focus:border-[var(--rust)] transition-all"
                        value={v.label}
                        onChange={(e) => updateVariationLabel(index, e.target.value)}
                      />
                    </div>
                    <button 
                      type="button"
                      onClick={() => removeVariation(index)} 
                      className="absolute -top-2 -right-2 p-1.5 bg-red-500 text-white rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <X className="w-3 h-3" />
                    </button>
                  </div>
                ))}

                <div className="grid grid-cols-2 gap-4">
                  <div 
                    className="py-8 border-2 border-dashed border-[var(--border)] rounded-2xl flex flex-col items-center justify-center bg-[var(--input-bg)] hover:bg-white transition-all cursor-pointer group"
                    onClick={() => document.getElementById('product-image').click()}
                  >
                    <ImageIcon className="w-6 h-6 text-[var(--muted)] mb-2 group-hover:text-[var(--rust)] transition-colors" />
                    <div className="text-[10px] font-bold uppercase tracking-widest">Browse Gallery</div>
                    <input id="product-image" type="file" multiple className="hidden" onChange={handleImageChange} />
                  </div>
                  <div 
                    className="py-8 border-2 border-dashed border-[var(--border)] rounded-2xl flex flex-col items-center justify-center bg-[var(--input-bg)] hover:bg-white transition-all cursor-pointer group"
                    onClick={handleCameraCapture}
                  >
                    <Camera className="w-6 h-6 text-[var(--muted)] mb-2 group-hover:text-[var(--rust)] transition-colors" />
                    <div className="text-[10px] font-bold uppercase tracking-widest">Take Photo</div>
                  </div>
                </div>
              </div>

              <div className="space-y-3">
                <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Heritage Sizing Available</label>
                <div className="flex flex-wrap gap-2">
                  {["S", "M", "L", "XL", "XXL"].map(size => (
                    <button
                      key={size}
                      type="button"
                      onClick={() => {
                        const newSizes = formData.sizes.includes(size) 
                          ? formData.sizes.filter(s => s !== size)
                          : [...formData.sizes, size];
                        setFormData({...formData, sizes: newSizes});
                      }}
                      className={`px-4 py-2 rounded-lg text-xs font-bold transition-all border-2 ${formData.sizes.includes(size) ? 'bg-[var(--rust)] text-white border-[var(--rust)] shadow-lg' : 'bg-white text-[var(--muted)] border-[var(--border)] hover:border-[var(--rust)]'}`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>

              <div className="space-y-4">
                <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-2">Category</label>
                <div className="relative group">
                  <select 
                    required
                    className="w-full px-6 py-4 bg-white border-2 border-[var(--rust)]/60 rounded-2xl focus:outline-none focus:border-[var(--rust)] transition-all font-serif text-lg font-bold text-[var(--charcoal)] appearance-none cursor-pointer shadow-lg shadow-red-900/5 group-hover:border-[var(--rust)]"
                    value={formData.category}
                    onChange={(e) => setFormData({...formData, category: e.target.value})}
                  >
                    <option value="" disabled>Select Heritage Sector</option>
                    {categories.map((cat, idx) => (
                      <option key={idx} value={cat.name}>{cat.name}</option>
                    ))}
                  </select>
                  <div className="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-[var(--rust)]">
                    <Plus className="w-5 h-5 rotate-45" />
                  </div>
                </div>
              </div>
            </div>

            <button 
              type="submit" 
              disabled={loading}
              className="btn-primary w-full py-5 text-base shadow-xl"
            >
              {loading ? <Loader2 className="w-5 h-5 animate-spin mx-auto" /> : <>Finalize Heritage Listing <Plus className="w-5 h-5 ml-1" /></>}
            </button>
          </div>
        </form>
      </div>
    </SellerLayout>
  );
}
