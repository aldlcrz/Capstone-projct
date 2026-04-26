"use client";
import React, { useState } from "react";
import SellerLayout from "@/components/SellerLayout";
import { Upload, Plus, X, Loader2, ArrowRight, ArrowLeft, Camera, Image as ImageIcon } from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";
import { Camera as CapCamera, CameraResultType } from "@capacitor/camera";
import { Capacitor } from "@capacitor/core";
import { fetchCategories, normalizeCategories } from "@/lib/categories";
import { IMAGE_UPLOAD_RULES, validateImageFile, validateImageFiles } from "@/lib/imageUploadValidation";
import {
  INPUT_LIMITS,
  sanitizePhoneInput,
  validatePhilippineMobileNumber,
} from "@/lib/inputValidation";
import { CheckCircle, XCircle } from "lucide-react";
import { AnimatePresence } from "framer-motion";

export default function AddProductPage() {
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price: "",
    categories: [],
    stock: 0,
    sizes: [
      { name: "S", stock: 0 },
      { name: "M", stock: 0 },
      { name: "L", stock: 0 },
      { name: "XL", stock: 0 }
    ],
    shippingFee: "",
    shippingDays: "",
    gcashNumber: "",
    gcashName: "",
    mayaNumber: "",
    mayaName: "",
    allowGcash: true,
    allowMaya: true
  });
  const [gcashQrFile, setGcashQrFile] = useState(null);
  const [mayaQrFile, setMayaQrFile] = useState(null);
  const [gcashQrPreview, setGcashQrPreview] = useState(null);
  const [mayaQrPreview, setMayaQrPreview] = useState(null);
  const [variations, setVariations] = useState([]); // Array of { file, label, preview }
  const [categories, setCategories] = useState([]);
  const [fetchingCategories, setFetchingCategories] = useState(true);
  const [customSize, setCustomSize] = useState("");
  const [toast, setToast] = useState(null);
  const [uploadProgress, setUploadProgress] = useState(0);

  const showToast = (type, msg) => {
    setToast({ type, msg });
    setTimeout(() => setToast(null), 4000);
  };

  const handleAddCustomSize = (e) => {
    e.preventDefault();
    if (customSize.trim()) {
      const exists = formData.sizes.some(s => (s.name || s) === customSize.trim());
      if (!exists) {
        setFormData({ ...formData, sizes: [...formData.sizes, { name: customSize.trim(), stock: 0 }] });
      }
    }
    setCustomSize("");
  };

  const updateSizeStock = (index, value) => {
    const newSizes = [...formData.sizes];
    // Allow empty string for better UX when clearing
    const val = value === "" ? "" : parseInt(value);
    newSizes[index].stock = isNaN(val) ? 0 : val;
    
    const total = newSizes.reduce((sum, s) => sum + (Number(s.stock) || 0), 0);
    setFormData({ ...formData, sizes: newSizes, stock: total });
  };

  React.useEffect(() => {
    const loadCategories = async () => {
      try {
        const data = await fetchCategories();
        setCategories(normalizeCategories(data));
      } catch (err) {
        console.error("Failed to fetch categories");
        setCategories([]);
      } finally {
        setFetchingCategories(false);
      }
    };
    loadCategories();
  }, []);

  const handleImageChange = async (e) => {
    const files = Array.from(e.target.files || []);
    const remainingSlots = IMAGE_UPLOAD_RULES.maxFilesPerRequest - variations.length;

    if (remainingSlots <= 0) {
      showToast("error", `You can upload up to ${IMAGE_UPLOAD_RULES.maxFilesPerRequest} product images only.`);
      e.target.value = "";
      return;
    }

    const selectedFiles = files.slice(0, remainingSlots);
    const { validFiles, errors } = await validateImageFiles(selectedFiles, "Product image");

    if (errors.length > 0) {
      showToast("error", errors[0]);
    }

    const newVariations = validFiles.map(file => ({
      file,
      label: "",
      preview: URL.createObjectURL(file)
    }));
    setVariations([...variations, ...newVariations]);
    e.target.value = "";
  };

  const handleCameraCapture = async () => {
    try {
      const image = await CapCamera.getPhoto({
        quality: 90,
        allowEditing: true,
        resultType: CameraResultType.Uri
      });

      const response = await fetch(image.webPath);
      const blob = await response.blob();
      const file = new File([blob], `camera-photo-${Date.now()}.jpg`, { type: 'image/jpeg' });

      const validationError = await validateImageFile(file, "Captured image");
      if (validationError) {
        showToast("error", validationError);
        return;
      }

      if (variations.length >= IMAGE_UPLOAD_RULES.maxFilesPerRequest) {
        showToast("error", `You can upload up to ${IMAGE_UPLOAD_RULES.maxFilesPerRequest} product images only.`);
        return;
      }

      setVariations([...variations, {
        file,
        label: "",
        preview: URL.createObjectURL(file)
      }]);
    } catch (error) {
      if (error.message !== 'User cancelled photos app') {
        console.error("Camera Capture Failed:", error);
      }
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
    if (!formData.name.trim()) {
      showToast("error", "Product name is required.");
      return;
    }
    if (!formData.description.trim()) {
      showToast("error", "Product description is required.");
      return;
    }
    if (variations.length === 0) {
      showToast("error", "Please upload at least one product variation image.");
      return;
    }
    if (formData.categories.length === 0) {
      showToast("error", "Please select at least one category.");
      return;
    }
    if (!formData.price || Number(formData.price) <= 0 || Number(formData.price) > 10000) {
      showToast("error", "Price must be between 1 and 10,000 PHP.");
      return;
    }
    if (!formData.stock || Number(formData.stock) < 0 || Number(formData.stock) > 500) {
      showToast("error", "Stock quantity must be between 0 and 500 units.");
      return;
    }
    if (formData.shippingFee && (Number(formData.shippingFee) < 0 || Number(formData.shippingFee) > 500)) {
      showToast("error", "Shipping fee cannot exceed 500 PHP.");
      return;
    }
    if (formData.shippingDays && (Number(formData.shippingDays) < 1 || Number(formData.shippingDays) > 30)) {
      showToast("error", "Shipping days must be between 1 and 30 days.");
      return;
    }
    if (!formData.allowGcash && !formData.allowMaya) {
      showToast("error", "Please enable at least one payment method (GCash or Maya).");
      return;
    }

    if (formData.allowGcash) {
      if (!formData.gcashNumber) {
        showToast("error", "GCash number is required when GCash is enabled.");
        return;
      }
      if (!gcashQrFile) {
        showToast("error", "GCash QR Code is required when GCash is enabled.");
        return;
      }
    }
    if (formData.allowMaya) {
      if (!formData.mayaNumber) {
        showToast("error", "Maya number is required when Maya is enabled.");
        return;
      }
      if (!mayaQrFile) {
        showToast("error", "Maya QR Code is required when Maya is enabled.");
        return;
      }
    }

    let normalizedGcashNumber = "";
    let normalizedMayaNumber = "";
    try {
      normalizedGcashNumber = validatePhilippineMobileNumber(formData.gcashNumber, "GCash number", { required: !!formData.allowGcash });
      normalizedMayaNumber = validatePhilippineMobileNumber(formData.mayaNumber, "Maya number", { required: !!formData.allowMaya });
    } catch (error) {
      showToast("error", error.message || "Please review the payment numbers.");
      return;
    }
    setLoading(true);
    setUploadProgress(0);

    try {
      let gcashQrUrl = "";
      let mayaQrUrl = "";

      if (gcashQrFile) {
        const gFormData = new FormData();
        gFormData.append('image', gcashQrFile);
        const gRes = await api.post('/upload', gFormData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (event) => {
            if (!event.total) return;
            setUploadProgress(Math.round((event.loaded / event.total) * 50));
          }
        });
        gcashQrUrl = gRes.data.url;
      }
      if (mayaQrFile) {
        const mFormData = new FormData();
        mFormData.append('image', mayaQrFile);
        const mRes = await api.post('/upload', mFormData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (event) => {
            if (!event.total) return;
            setUploadProgress(Math.max(50, Math.round(50 + (event.loaded / event.total) * 20)));
          }
        });
        mayaQrUrl = mRes.data.url;
      }

      const data = new FormData();
      data.append('name', formData.name);
      data.append('description', formData.description);
      data.append('price', formData.price);
      data.append('categories', JSON.stringify(formData.categories));
      data.append('stock', formData.stock);
      data.append('sizes', JSON.stringify(formData.sizes));
      data.append('shippingFee', formData.shippingFee || 0);
      data.append('shippingDays', formData.shippingDays || 3);
      data.append('gcashNumber', normalizedGcashNumber);
      data.append('gcashName', formData.gcashName);
      data.append('gcashQrCode', gcashQrUrl || "");
      data.append('mayaNumber', normalizedMayaNumber);
      data.append('mayaName', formData.mayaName);
      data.append('mayaQrCode', mayaQrUrl || "");
      data.append('allowGcash', formData.allowGcash);
      data.append('allowMaya', formData.allowMaya);

      variations.forEach((v) => {
        data.append('images', v.file);
      });
      data.append('variationNames', JSON.stringify(variations.map(v => v.label || "Original")));

      await api.post("/products", data, {
        headers: {
          'Content-Type': 'multipart/form-data',
          Authorization: `Bearer ${localStorage.getItem('token')}`
        },
        onUploadProgress: (event) => {
          if (!event.total) return;
          setUploadProgress(Math.max(20, Math.round((event.loaded / event.total) * 100)));
        },
      });
      showToast("success", "Product listed successfully!");
      setTimeout(() => window.location.href = "/seller/inventory", 1500);
    } catch (error) {
      console.error(error);
      showToast("error", error.response?.data?.message || "Failed to list product.");
    } finally {
      setLoading(false);
      setUploadProgress(0);
    }
  };
  return (
    <SellerLayout>
      <div className="max-w-[1400px] mx-auto space-y-10 relative">
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

        <div className="flex items-center gap-4">
          <button
            onClick={() => window.location.href = "/seller/inventory"}
            className="w-10 h-10 rounded-full bg-white border border-[var(--border)] flex items-center justify-center text-[var(--muted)] hover:text-[var(--rust)] hover:border-[var(--rust)] transition-all shadow-sm"
          >
            <ArrowLeft className="w-5 h-5" />
          </button>
          <div>
            <div className="eyebrow">Inventory Management</div>
            <h1 className="font-serif text-lg lg:text-xl font-bold tracking-tight text-[var(--charcoal)]">
              Create New <span className="text-[var(--rust)] italic lowercase">product</span>
            </h1>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {/* Column 1: Variations & Media */}
          <div className="space-y-8">
            <div className="artisan-card space-y-6">
              <h3 className="text-lg font-bold">Variations & Media</h3>

              <div className="space-y-4">
                {variations.map((v, index) => (
                  <div key={index} className="flex flex-row gap-4 p-4 border border-[var(--border)] rounded-2xl bg-[var(--cream)]/30 group relative">
                    <div className="w-20 h-20 relative rounded-xl overflow-hidden shrink-0 shadow-sm">
                      <img src={v.preview} alt="Preview" className="w-full h-full object-cover" />
                    </div>
                    <div className="flex-1 space-y-2">
                      <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Variation Label</label>
                      <input
                        type="text"
                        placeholder="e.g. Classic White"
                        maxLength={INPUT_LIMITS.variationLabel}
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
                    <input id="product-image" type="file" multiple accept="image/jpeg,image/png" className="hidden" onChange={handleImageChange} />
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
                  {["S", "M", "L", "XL", "XXL"].map(size => {
                    const existingSize = formData.sizes.find(s => (s.name || s) === size);
                    const isSelected = !!existingSize;
                    return (
                      <button
                        key={size}
                        type="button"
                        onClick={() => {
                          const newSizes = isSelected
                            ? formData.sizes.filter(s => (s.name || s) !== size)
                            : [...formData.sizes, { name: size, stock: 0 }];
                          
                          const total = newSizes.reduce((sum, s) => sum + (Number(s.stock) || 0), 0);
                          setFormData({ ...formData, sizes: newSizes, stock: total });
                        }}
                        className={`px-4 py-2 rounded-lg text-xs font-bold transition-all border-2 ${isSelected ? 'bg-[var(--rust)] text-white border-[var(--rust)] shadow-lg' : 'bg-white text-[var(--muted)] border-[var(--border)] hover:border-[var(--rust)]'}`}
                      >
                        {size}
                      </button>
                    );
                  })}
                </div>

                {/* Per-Size Stock Inputs */}
                {formData.sizes.length > 0 && (
                  <div className="grid grid-cols-1 gap-3 mt-4">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Stock per selected size</label>
                    {formData.sizes.map((s, idx) => (
                      <div key={idx} className="flex items-center gap-3 p-3 bg-white border border-[var(--border)] rounded-xl">
                        <span className="w-12 text-xs font-bold text-[var(--rust)] uppercase">{s.name || s}</span>
                        <input
                          type="number"
                          min="0"
                          max="500"
                          placeholder="0"
                          className="flex-1 bg-[var(--cream)]/30 border-none focus:ring-0 text-sm font-bold p-0"
                          value={s.stock === 0 ? "" : s.stock}
                          onFocus={(e) => s.stock === 0 && updateSizeStock(idx, "")}
                          onChange={(e) => updateSizeStock(idx, e.target.value)}
                        />
                        <button 
                          type="button" 
                          onClick={() => {
                            const newSizes = formData.sizes.filter((_, i) => i !== idx);
                            const total = newSizes.reduce((sum, s) => sum + (Number(s.stock) || 0), 0);
                            setFormData({ ...formData, sizes: newSizes, stock: total });
                          }}
                          className="text-red-400 hover:text-red-600 transition-colors"
                        >
                          <X size={14} />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
                <div className="flex items-center gap-2 mt-3">
                  <input
                    type="text"
                    placeholder="Enter custom size (e.g., 38, Custom Fit)"
                    value={customSize}
                    onChange={(e) => setCustomSize(e.target.value)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter') {
                        e.preventDefault();
                        handleAddCustomSize(e);
                      }
                    }}
                    className="flex-1 px-4 py-2 bg-[var(--input-bg)] border border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--rust)] transition-all text-xs font-bold"
                  />
                  <button
                    type="button"
                    onClick={handleAddCustomSize}
                    className="px-4 py-2 bg-[var(--bark)] text-white rounded-xl text-xs font-bold hover:bg-[var(--rust)] transition-all flex items-center gap-1 shrink-0"
                  >
                    <Plus className="w-4 h-4" /> Add Size
                  </button>
                </div>
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
                    disabled={fetchingCategories}
                    className="w-full px-6 py-4 bg-white border-2 border-[var(--rust)]/60 rounded-2xl focus:outline-none focus:border-[var(--rust)] transition-all font-bold text-sm text-[var(--charcoal)] appearance-none cursor-pointer shadow-lg shadow-red-900/5 group-hover:border-[var(--rust)]"
                    onChange={(e) => {
                      const val = e.target.value;
                      if (val && !formData.categories.includes(val)) {
                        setFormData({ ...formData, categories: [...formData.categories, val] });
                      }
                      e.target.value = ""; // Reset
                    }}
                  >
                    <option value="">{fetchingCategories ? "Loading categories..." : "+ Select Category"}</option>
                    {categories.map((categoryName, idx) => (
                      <option key={idx} value={categoryName} disabled={formData.categories.includes(categoryName)}>
                        {categoryName}
                      </option>
                    ))}
                  </select>
                  <div className="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-[var(--rust)]">
                    <Plus className="w-5 h-5 rotate-45" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Column 2: Base Info & Pricing */}
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
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
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
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
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
                    max="10000"
                    step="0.01"
                    className={`w-full px-4 py-3 bg-[var(--input-bg)] border rounded-xl focus:outline-none transition-all ${Number(formData.price) > 10000 ? 'border-red-500 ring-2 ring-red-100' : 'border-[var(--border)] focus:border-[var(--rust)]'}`}
                    placeholder="Max ₱10,000"
                    value={formData.price}
                    onChange={(e) => {
                      let val = e.target.value;
                      if (val !== "" && Number(val) > 10000) val = "10000";
                      setFormData({ ...formData, price: val });
                    }}
                  />
                  {Number(formData.price) > 10000 && <p className="text-[9px] font-bold text-red-500 uppercase tracking-widest ml-1">Limit: ₱10,000</p>}
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Total Stock Quantity</label>
                  <input
                    type="number"
                    readOnly
                    className="w-full px-4 py-3 bg-[var(--cream)]/30 border border-[var(--border)] rounded-xl cursor-not-allowed font-bold text-[var(--rust)]"
                    placeholder="Auto-calculated"
                    value={formData.stock}
                  />
                  <p className="text-[9px] text-[var(--muted)] italic">Total is sum of all sizes above</p>
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Shipping Fee (₱)</label>
                  <input
                    type="number"
                    min="0"
                    max="500"
                    step="0.01"
                    className={`w-full px-4 py-3 bg-[var(--input-bg)] border rounded-xl focus:outline-none transition-all ${Number(formData.shippingFee) > 500 ? 'border-red-500 ring-2 ring-red-100' : 'border-[var(--border)] focus:border-[var(--rust)]'}`}
                    placeholder="Max ₱500"
                    value={formData.shippingFee}
                    onChange={(e) => {
                      let val = e.target.value;
                      if (val !== "" && Number(val) > 500) val = "500";
                      setFormData({ ...formData, shippingFee: val });
                    }}
                  />
                  {Number(formData.shippingFee) > 500 && <p className="text-[9px] font-bold text-red-500 uppercase tracking-widest ml-1">Limit: ₱500</p>}
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Shipping Days</label>
                  <input
                    type="number"
                    min="1"
                    max="30"
                    step="1"
                    className={`w-full px-4 py-3 bg-[var(--input-bg)] border rounded-xl focus:outline-none transition-all ${Number(formData.shippingDays) > 30 ? 'border-red-500 ring-2 ring-red-100' : 'border-[var(--border)] focus:border-[var(--rust)]'}`}
                    placeholder="Max 30 days"
                    value={formData.shippingDays}
                    onChange={(e) => {
                      let val = e.target.value;
                      if (val !== "" && Number(val) > 30) val = "30";
                      setFormData({ ...formData, shippingDays: val });
                    }}
                  />
                  {Number(formData.shippingDays) > 30 && <p className="text-[9px] font-bold text-red-500 uppercase tracking-widest ml-1">Limit: 30 days</p>}
                </div>
              </div>
            </div>
          </div>

          {/* Column 3: Payment */}
          <div className="space-y-8 md:col-span-2 lg:col-span-1">
            <div className="artisan-card space-y-6">
              <h3 className="text-lg font-bold">Payment</h3>
              <p className="text-[10px] text-[var(--muted)] -mt-4 uppercase tracking-widest leading-relaxed">Optional: Leave blank to use your Seller Profile&apos;s global payment details.</p>
              <div className="grid grid-cols-1 gap-6">
                {/* GCash */}
                <div className="space-y-4 p-4 border border-[var(--border)] rounded-2xl bg-[var(--input-bg)]/30">
                  <div className="flex items-center justify-between gap-2 text-[10px] font-bold uppercase tracking-widest text-[#2D5CC5]">
                    <div className="flex items-center gap-2">
                      <div className="w-2 h-2 rounded-full bg-[#2D5CC5]" /> GCash Method
                    </div>
                    <label className="flex items-center gap-2 cursor-pointer group">
                      <span className="text-[9px] font-bold text-[var(--muted)] group-hover:text-[#2D5CC5] transition-colors">{formData.allowGcash ? 'AVAILABLE' : 'DISABLED'}</span>
                      <input
                        type="checkbox"
                        className="w-4 h-4 accent-[#2D5CC5] rounded cursor-pointer"
                        checked={formData.allowGcash}
                        onChange={(e) => setFormData({ ...formData, allowGcash: e.target.checked })}
                      />
                    </label>
                  </div>
                  <div className="space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">GCash Number</label>
                    <input
                      type="text"
                      className="w-full px-4 py-3 bg-white border border-[var(--border)] rounded-xl focus:outline-none focus:border-[#2D5CC5] transition-all text-sm"
                      placeholder="e.g. 0917 123 4567"
                      value={formData.gcashNumber}
                      inputMode="numeric"
                      maxLength={INPUT_LIMITS.mobileNumber}
                      onChange={(e) => setFormData({ ...formData, gcashNumber: sanitizePhoneInput(e.target.value) })}
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">GCash Account Name</label>
                    <input
                      type="text"
                      className="w-full px-4 py-3 bg-white border border-[var(--border)] rounded-xl focus:outline-none focus:border-[#2D5CC5] transition-all text-sm"
                      placeholder="Account Holder Name"
                      value={formData.gcashName}
                      onChange={(e) => setFormData({ ...formData, gcashName: e.target.value })}
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">GCash QR Code</label>
                    <div className="flex gap-4">
                      <div
                        className="flex-1 py-4 border-2 border-dashed border-[var(--border)] rounded-xl flex flex-col items-center justify-center bg-white hover:bg-blue-50 transition-all cursor-pointer group"
                        onClick={() => document.getElementById('gcash-qr').click()}
                      >
                        <Upload className="w-4 h-4 text-[var(--muted)] mb-1 group-hover:text-[#2D5CC5]" />
                        <span className="text-[9px] font-bold uppercase tracking-widest">Upload QR</span>
                        <input id="gcash-qr" type="file" accept="image/jpeg,image/png" className="hidden" onChange={async (e) => {
                          const file = e.target.files[0];
                          if (!file) return;
                          const error = await validateImageFile(file, "GCash QR image");
                          if (error) {
                            showToast("error", error);
                            e.target.value = "";
                            return;
                          }
                          setGcashQrFile(file);
                          setGcashQrPreview(URL.createObjectURL(file));
                        }} />
                      </div>
                      {gcashQrPreview && (
                        <div className="w-16 h-16 rounded-xl overflow-hidden border border-[var(--border)] shadow-sm relative group">
                          <img src={gcashQrPreview} alt="GCash QR" className="w-full h-full object-cover" />
                          <button onClick={() => { setGcashQrFile(null); setGcashQrPreview(null); }} className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <X className="w-4 h-4 text-white" />
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                {/* Maya */}
                <div className="space-y-4 p-4 border border-[var(--border)] rounded-2xl bg-[var(--input-bg)]/30">
                  <div className="flex items-center justify-between gap-2 text-[10px] font-bold uppercase tracking-widest text-[#00E06D]">
                    <div className="flex items-center gap-2">
                      <div className="w-2 h-2 rounded-full bg-[#00E06D]" /> Maya Method
                    </div>
                    <label className="flex items-center gap-2 cursor-pointer group">
                      <span className="text-[9px] font-bold text-[var(--muted)] group-hover:text-[#00E06D] transition-colors">{formData.allowMaya ? 'AVAILABLE' : 'DISABLED'}</span>
                      <input
                        type="checkbox"
                        className="w-4 h-4 accent-[#00E06D] rounded cursor-pointer"
                        checked={formData.allowMaya}
                        onChange={(e) => setFormData({ ...formData, allowMaya: e.target.checked })}
                      />
                    </label>
                  </div>
                  <div className="space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Maya Number</label>
                    <input
                      type="text"
                      className="w-full px-4 py-3 bg-white border border-[var(--border)] rounded-xl focus:outline-none focus:border-[#00E06D] transition-all text-sm"
                      placeholder="e.g. 0917 123 4567"
                      value={formData.mayaNumber}
                      inputMode="numeric"
                      maxLength={INPUT_LIMITS.mobileNumber}
                      onChange={(e) => setFormData({ ...formData, mayaNumber: sanitizePhoneInput(e.target.value) })}
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Maya Account Name</label>
                    <input
                      type="text"
                      className="w-full px-4 py-3 bg-white border border-[var(--border)] rounded-xl focus:outline-none focus:border-[#00E06D] transition-all text-sm"
                      placeholder="Account Holder Name"
                      value={formData.mayaName}
                      onChange={(e) => setFormData({ ...formData, mayaName: e.target.value })}
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Maya QR Code</label>
                    <div className="flex gap-4">
                      <div
                        className="flex-1 py-4 border-2 border-dashed border-[var(--border)] rounded-xl flex flex-col items-center justify-center bg-white hover:bg-green-50 transition-all cursor-pointer group"
                        onClick={() => document.getElementById('maya-qr').click()}
                      >
                        <Upload className="w-4 h-4 text-[var(--muted)] mb-1 group-hover:text-[#00E06D]" />
                        <span className="text-[9px] font-bold uppercase tracking-widest">Upload QR</span>
                        <input id="maya-qr" type="file" accept="image/jpeg,image/png" className="hidden" onChange={async (e) => {
                          const file = e.target.files[0];
                          if (!file) return;
                          const error = await validateImageFile(file, "Maya QR image");
                          if (error) {
                            showToast("error", error);
                            e.target.value = "";
                            return;
                          }
                          setMayaQrFile(file);
                          setMayaQrPreview(URL.createObjectURL(file));
                        }} />
                      </div>
                      {mayaQrPreview && (
                        <div className="w-16 h-16 rounded-xl overflow-hidden border border-[var(--border)] shadow-sm relative group">
                          <img src={mayaQrPreview} alt="Maya QR" className="w-full h-full object-cover" />
                          <button onClick={() => { setMayaQrFile(null); setMayaQrPreview(null); }} className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <X className="w-4 h-4 text-white" />
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="btn-primary w-full py-5 text-base shadow-xl"
            >
              {loading ? <Loader2 className="w-5 h-5 animate-spin mx-auto" /> : <>Save <Plus className="w-5 h-5 ml-1" /></>}
            </button>
            {loading && uploadProgress > 0 && (
              <div className="space-y-2">
                <div className="h-2 w-full overflow-hidden rounded-full bg-[var(--border)]">
                  <div className="h-full rounded-full bg-[var(--rust)] transition-all" style={{ width: `${uploadProgress}%` }} />
                </div>
                <p className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] text-center">
                  Uploading {uploadProgress}%
                </p>
              </div>
            )}
          </div>
        </form>
      </div>
    </SellerLayout>
  );
}
