"use client";
/* eslint-disable @next/next/no-img-element */
import React, { useState, useEffect } from "react";
import { Upload, Plus, X, Loader2, ArrowLeft, Save, Camera, Image as ImageIcon, CheckCircle, XCircle, CreditCard } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { fetchCategories, normalizeCategories } from "@/lib/categories";
import { IMAGE_UPLOAD_RULES, validateImageFile, validateImageFiles } from "@/lib/imageUploadValidation";
import {
  INPUT_LIMITS,
  sanitizePhoneInput,
  validatePhilippineMobileNumber,
} from "@/lib/inputValidation";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { resolveBackendImageUrl } from "@/lib/productImages";

export default function EditProductClient({ id: propId }) {
  const searchParams = useSearchParams();
  const id = propId || searchParams.get("id");
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);
  const [categoriesList, setCategoriesList] = useState([]);
  const [fetchingCategories, setFetchingCategories] = useState(true);
  const [customSize, setCustomSize] = useState("");
  const [toast, setToast] = useState(null);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price: "",
    categories: [],
    tags: [],
    sizes: [],
    stock: "",
    shippingFee: "",
    shippingDays: "3",
    allowGcash: true,
    allowMaya: true,
    gcashNumber: "",
    gcashName: "",
    mayaNumber: "",
    mayaName: ""
  });

  const [gcashQrFile, setGcashQrFile] = useState(null);
  const [mayaQrFile, setMayaQrFile] = useState(null);
  const [gcashQrPreview, setGcashQrPreview] = useState(null);
  const [mayaQrPreview, setMayaQrPreview] = useState(null);
  const [variations, setVariations] = useState([]); // Matches AddProductPage logic
  const [uploadProgress, setUploadProgress] = useState(0);

  const showToast = (type, msg) => {
    setToast({ type, msg });
    setTimeout(() => setToast(null), 4000);
  };
  useEffect(() => {
    if (!id) return;
    const fetchData = async () => {
      try {
        const [prodRes, catsData, profileRes] = await Promise.all([
          api.get(`/products/${id}`),
          fetchCategories(),
          api.get('/users/profile')
        ]);
        
        const prod = prodRes.data;
        let sizes = prod.sizes || [];
        if (typeof sizes === 'string') {
          try { sizes = JSON.parse(sizes); } catch (e) { sizes = []; }
        }
        // Ensure sizes are objects
        sizes = sizes.map(s => typeof s === 'object' ? s : { name: s, stock: 0 });

        const user = profileRes.data.user;

        setFormData({
          name: prod.name,
          description: prod.description,
          price: prod.price,
          categories: prod.categories || [],
          tags: prod.tags || [],
          sizes: sizes,
          stock: prod.stock,
          shippingFee: prod.shippingFee || 0,
          shippingDays: prod.shippingDays || 3,
          allowGcash: prod.allowGcash !== false,
          allowMaya: prod.allowMaya !== false,
          gcashNumber: user ? (user.gcashNumber || "") : "",
          gcashName: user ? (user.gcashName || user.name || "") : "",
          mayaNumber: user ? (user.mayaNumber || "") : "",
          mayaName: user ? (user.mayaName || user.name || "") : ""
        });

        if (user) {
          if (user.gcashQrCode) {
            setGcashQrPreview(user.gcashQrCode);
          }
          if (user.mayaQrCode) {
            setMayaQrPreview(user.mayaQrCode);
          }
        }
        
        const rawImages = Array.isArray(prod.image) ? prod.image : [];
        const existingVars = rawImages.map((img, idx) => ({
          file: null,
          label: img?.name || img?.label || img?.variation || `Variation ${idx + 1}`,
          preview: img?.url || (typeof img === 'string' ? img : null),
          isExisting: true
        })).filter(v => v.preview);
        setVariations(existingVars);

        setCategoriesList(normalizeCategories(catsData));
      } catch (err) {
        console.error(err);
        showToast("error", "Failed to load product details.");
      } finally {
        setFetching(false);
        setFetchingCategories(false);
      }
    };
    fetchData();
  }, [id]);
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
    const val = value === "" ? "" : parseInt(value);
    newSizes[index].stock = isNaN(val) ? 0 : val;
    const total = newSizes.reduce((sum, s) => sum + (Number(s.stock) || 0), 0);
    setFormData({ ...formData, sizes: newSizes, stock: total });
  };

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
      preview: URL.createObjectURL(file),
      isExisting: false
    }));
    setVariations([...variations, ...newVariations]);
    e.target.value = "";
  };

  const handleCameraCapture = async () => {
    try {
      const { Camera: CapCamera, CameraResultType } = await import("@capacitor/camera");
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
        preview: URL.createObjectURL(file),
        isExisting: false
      }]);
    } catch (error) {
      console.error("Camera Capture Failed:", error);
    }
  };

  const removeVariation = (index) => {
    const newVars = [...variations];
    if (!newVars[index].isExisting) {
      URL.revokeObjectURL(newVars[index].preview);
    }
    newVars.splice(index, 1);
    setVariations(newVars);
  };

  const updateVariationLabel = (index, label) => {
    const newVars = [...variations];
    newVars[index].label = label;
    setVariations(newVars);
  };
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.name.trim()) return showToast("error", "Product name is required.");
    if (variations.length === 0) return showToast("error", "Please upload at least one product variation image.");

    if (!formData.allowGcash && !formData.allowMaya) {
      return showToast("error", "Please enable at least one payment method (GCash or Maya).");
    }
    if (formData.allowGcash) {
      if (!formData.gcashNumber || !formData.gcashNumber.trim()) {
        return showToast("error", "GCash Mobile Number is required when GCash is enabled.");
      }
      if (!formData.gcashName || !formData.gcashName.trim()) {
        return showToast("error", "GCash Account Name is required when GCash is enabled.");
      }
      if (!gcashQrFile && !gcashQrPreview) {
        return showToast("error", "GCash QR Code image is required when GCash is enabled.");
      }
    }
    if (formData.allowMaya) {
      if (!formData.mayaNumber || !formData.mayaNumber.trim()) {
        return showToast("error", "Maya Mobile Number is required when Maya is enabled.");
      }
      if (!formData.mayaName || !formData.mayaName.trim()) {
        return showToast("error", "Maya Account Name is required when Maya is enabled.");
      }
      if (!mayaQrFile && !mayaQrPreview) {
        return showToast("error", "Maya QR Code image is required when Maya is enabled.");
      }
    }

    setLoading(true);
    setUploadProgress(0);

    try {
      // 1. If GCash is enabled and we have a new QR file, upload it
      let gcashQrUrl = null;
      if (formData.allowGcash && gcashQrFile) {
        const gForm = new FormData();
        gForm.append('image', gcashQrFile);
        const uploadRes = await api.post('/upload', gForm, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        gcashQrUrl = uploadRes.data.url;
      }

      // 2. If Maya is enabled and we have a new QR file, upload it
      let mayaQrUrl = null;
      if (formData.allowMaya && mayaQrFile) {
        const mForm = new FormData();
        mForm.append('image', mayaQrFile);
        const uploadRes = await api.post('/upload', mForm, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        mayaQrUrl = uploadRes.data.url;
      }

      // 3. Update the seller profile with new details (or preserve old ones)
      const profilePayload = {};
      if (formData.allowGcash) {
        profilePayload.gcashNumber = formData.gcashNumber;
        profilePayload.gcashName = formData.gcashName;
        if (gcashQrUrl) {
          profilePayload.gcashQrCode = gcashQrUrl;
        } else if (!gcashQrPreview) {
          profilePayload.gcashQrCode = null;
        }
      }
      if (formData.allowMaya) {
        profilePayload.mayaNumber = formData.mayaNumber;
        profilePayload.mayaName = formData.mayaName;
        if (mayaQrUrl) {
          profilePayload.mayaQrCode = mayaQrUrl;
        } else if (!mayaQrPreview) {
          profilePayload.mayaQrCode = null;
        }
      }

      // Send to profile
      if (Object.keys(profilePayload).length > 0) {
        await api.put('/users/profile', profilePayload);
      }

      const data = new FormData();
      data.append('name', formData.name);
      data.append('description', formData.description);
      data.append('price', formData.price);
      data.append('categories', JSON.stringify(formData.categories));
      data.append('tags', JSON.stringify(formData.tags));
      data.append('stock', formData.stock);
      data.append('sizes', JSON.stringify(formData.sizes));
      data.append('shippingFee', formData.shippingFee || 0);
      data.append('shippingDays', formData.shippingDays || 3);
      data.append('allowGcash', formData.allowGcash);
      data.append('allowMaya', formData.allowMaya);

      // Separate existing and new variations for backend logic
      const existingVars = variations.filter(v => v.isExisting).map(v => ({
        url: v.preview,
        variation: v.label
      }));
      data.append('existingImages', JSON.stringify(existingVars));

      variations.forEach((v) => {
        if (!v.isExisting && v.file) data.append('images', v.file);
      });
      data.append('variationNames', JSON.stringify(variations.filter(v => !v.isExisting).map(v => v.label || "Original")));

      await api.put(`/products/${id}`, data, {
        headers: {
          'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: (event) => {
          if (!event.total) return;
          setUploadProgress(Math.round((event.loaded / event.total) * 100));
        },
      });
      showToast("success", "Product updated successfully!");
      setTimeout(() => window.location.href = "/seller/inventory", 1500);
    } catch (error) {
      showToast("error", error.response?.data?.message || "Failed to update product.");
    } finally {
      setLoading(false);
      setUploadProgress(0);
    }
  };

  if (fetching) return (
    <div className="min-h-[60vh] flex flex-col items-center justify-center gap-4">
      <Loader2 className="w-10 h-10 animate-spin text-(--rust)" />
      <p className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Loading product...</p>
    </div>
  );

  return (
    <div className="max-w-350 mx-auto space-y-10 relative px-4">
      <AnimatePresence>
        {toast && (
          <motion.div
            initial={{ opacity: 0, y: -20, x: "-50%" }}
            animate={{ opacity: 1, y: 20, x: "-50%" }}
            exit={{ opacity: 0, y: -20, x: "-50%" }}
            className={`fixed top-4 left-1/2 z-100 px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border backdrop-blur-md ${
              toast.type === "success" ? "bg-green-50/90 border-green-200 text-green-800" : "bg-red-50/90 border-red-200 text-red-800"
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
          className="w-10 h-10 rounded-full bg-white border border-(--border) flex items-center justify-center text-(--muted) hover:text-(--rust) hover:border-(--rust) transition-all shadow-sm"
        >
          <ArrowLeft className="w-5 h-5" />
        </button>
        <div>
          <div className="eyebrow">Inventory Management</div>
          <h1 className="font-serif text-lg lg:text-xl font-bold tracking-tight text-(--charcoal)">
            Edit <span className="text-(--rust) italic lowercase">product</span>
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
                <div key={index} className="flex flex-row gap-4 p-4 border border-(--border) rounded-2xl bg-(--cream)/30 group relative">
                  <div className="w-20 h-20 relative rounded-xl overflow-hidden shrink-0 shadow-sm">
                    <img src={v.preview} alt="Preview" className="w-full h-full object-cover" />
                  </div>
                  <div className="flex-1 space-y-2">
                    <label className="text-[9px] font-black uppercase tracking-widest text-(--muted)">Variation Label</label>
                    <input
                      type="text"
                      placeholder="e.g. Classic White"
                      maxLength={INPUT_LIMITS.variationLabel}
                      className="w-full px-3 py-2 bg-white border border-(--border) rounded-lg text-sm focus:outline-none focus:border-(--rust) transition-all"
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
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div
                  className="py-8 border-2 border-dashed border-(--border) rounded-2xl flex flex-col items-center justify-center bg-(--input-bg) hover:bg-white transition-all cursor-pointer group"
                  onClick={() => {
                    alert("Make sure the image is clear and visible");
                    document.getElementById('product-image').click();
                  }}
                >
                  <ImageIcon className="w-6 h-6 text-(--muted) mb-2 group-hover:text-(--rust) transition-colors" />
                  <div className="text-[10px] font-bold uppercase tracking-widest">Browse Gallery</div>
                  <input id="product-image" type="file" multiple accept="image/*" className="hidden" onChange={handleImageChange} />
                </div>
                <div
                  className="py-8 border-2 border-dashed border-(--border) rounded-2xl flex flex-col items-center justify-center bg-(--input-bg) hover:bg-white transition-all cursor-pointer group"
                  onClick={handleCameraCapture}
                >
                  <Camera className="w-6 h-6 text-(--muted) mb-2 group-hover:text-(--rust) transition-colors" />
                  <div className="text-[10px] font-bold uppercase tracking-widest">Take Photo</div>
                </div>
              </div>
            </div>

            <div className="space-y-3">
              <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Heritage Sizing Available</label>
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
                      className={`px-4 py-2 rounded-lg text-xs font-bold transition-all border-2 ${isSelected ? 'bg-(--rust) text-white border-(--rust) shadow-lg' : 'bg-white text-(--muted) border-(--border) hover:border-(--rust)'}`}
                    >
                      {size}
                    </button>
                  );
                })}
              </div>

              {/* Per-Size Stock Inputs */}
              {formData.sizes.length > 0 && (
                <div className="grid grid-cols-1 gap-3 mt-4">
                  <label className="text-[9px] font-black uppercase tracking-widest text-(--muted) ml-1">Stock per selected size</label>
                  {formData.sizes.map((s, idx) => (
                    <div key={idx} className="flex items-center gap-3 p-3 bg-white border border-(--border) rounded-xl">
                      <span className="w-12 text-xs font-bold text-(--rust) uppercase">{s.name || s}</span>
                      <input
                        type="number"
                        min="0"
                        max="500"
                        placeholder="0"
                        className="flex-1 bg-(--cream)/30 border-none focus:ring-0 text-sm font-bold p-0"
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
                  className="flex-1 px-4 py-2 bg-(--input-bg) border border-(--border) rounded-xl focus:outline-none focus:border-(--rust) transition-all text-xs font-bold"
                />
                <button
                  type="button"
                  onClick={handleAddCustomSize}
                  className="px-4 py-2 bg-(--bark) text-white rounded-xl text-xs font-bold hover:bg-(--rust) transition-all flex items-center gap-1 shrink-0"
                >
                  <Plus className="w-4 h-4" /> Add Size
                </button>
              </div>
            </div>

            <div className="space-y-4">
              {/* Audience Tags */}
              <div className="space-y-3">
                <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted) ml-2">Audience Tags *</label>
                <p className="text-[9px] text-(--muted) ml-2">Who is this product for? Select all that apply.</p>
                <div className="flex flex-wrap gap-2">
                  {['Men', 'Women', 'Kids'].map(tag => {
                    const isSelected = formData.tags.includes(tag);
                    return (
                      <button
                        key={tag}
                        type="button"
                        onClick={() => {
                          const newTags = isSelected
                            ? formData.tags.filter(t => t !== tag)
                            : [...formData.tags, tag];
                          setFormData({ ...formData, tags: newTags });
                        }}
                        className={`px-5 py-2 rounded-full text-xs font-bold tracking-widest uppercase transition-all border-2 ${
                          isSelected
                            ? 'bg-(--rust) text-white border-(--rust) shadow-md shadow-red-900/10'
                            : 'bg-white text-(--muted) border-(--border) hover:border-(--rust) hover:text-(--rust)'
                        }`}
                      >
                        {tag}
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Categories */}
              <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted) ml-2">Categories *</label>
              <div className="flex flex-wrap gap-2 mb-3">
                {formData.categories.map((cat, idx) => (
                  <motion.div
                    initial={{ scale: 0.8, opacity: 0 }}
                    animate={{ scale: 1, opacity: 1 }}
                    key={idx}
                    className="flex items-center gap-2 px-4 py-2 bg-(--rust) text-white rounded-full text-sm font-bold shadow-md shadow-red-900/10"
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
                  className="w-full px-6 py-4 bg-white border-2 border-(--rust)/60 rounded-2xl focus:outline-none focus:border-(--rust) transition-all font-bold text-sm text-(--charcoal) appearance-none cursor-pointer shadow-lg shadow-red-900/5 group-hover:border-(--rust)"
                  onChange={(e) => {
                    const val = e.target.value;
                    if (val && !formData.categories.includes(val)) {
                      setFormData({ ...formData, categories: [...formData.categories, val] });
                    }
                    e.target.value = ""; // Reset
                  }}
                >
                  <option value="">{fetchingCategories ? "Loading categories..." : "+ Select Category"}</option>
                  {categoriesList.map((categoryName, idx) => (
                    <option key={idx} value={categoryName} disabled={formData.categories.includes(categoryName)}>
                      {categoryName}
                    </option>
                  ))}
                </select>
                <div className="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-(--rust)">
                  <Plus className="w-5 h-5 rotate-45" />
                </div>
              </div>
            </div>
          </div>
        <div className="space-y-8">
          <div className="artisan-card space-y-6">
            <h3 className="text-lg font-bold">Base Information</h3>
            <div className="space-y-2">
              <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Product Name *</label>
              <input
                type="text"
                required
                maxLength={100}
                className="w-full px-4 py-3 bg-(--input-bg) border border-(--border) rounded-xl focus:outline-none focus:border-(--rust) transition-all"
                placeholder="e.g. Pina-Silk Formal Barong"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Description *</label>
              <textarea
                required
                maxLength={2000}
                rows={4}
                className="w-full px-4 py-3 bg-(--input-bg) border border-(--border) rounded-xl focus:outline-none focus:border-(--rust) transition-all resize-none"
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
                <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Price (₱) *</label>
                <input
                  type="number"
                  required
                  min="1"
                  max="10000"
                  step="0.01"
                  className={`w-full px-4 py-3 bg-(--input-bg) border rounded-xl focus:outline-none transition-all ${Number(formData.price) > 10000 ? 'border-red-500 ring-2 ring-red-100' : 'border-(--border) focus:border-(--rust)'}`}
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
                <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Total Stock Quantity</label>
                <input
                  type="number"
                  readOnly
                  className="w-full px-4 py-3 bg-(--cream)/30 border border-(--border) rounded-xl cursor-not-allowed font-bold text-(--rust)"
                  placeholder="Auto-calculated"
                  value={formData.stock}
                />
                <p className="text-[9px] text-(--muted) italic">Total is sum of all sizes above</p>
              </div>
              <div className="space-y-2">
                <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Shipping Fee (₱)</label>
                <input
                  type="number"
                  min="0"
                  max="500"
                  step="0.01"
                  className={`w-full px-4 py-3 bg-(--input-bg) border rounded-xl focus:outline-none transition-all ${Number(formData.shippingFee) > 500 ? 'border-red-500 ring-2 ring-red-100' : 'border-(--border) focus:border-(--rust)'}`}
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
                <label className="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Shipping Days</label>
                <input
                  type="number"
                  min="1"
                  max="30"
                  step="1"
                  className={`w-full px-4 py-3 bg-(--input-bg) border rounded-xl focus:outline-none transition-all ${Number(formData.shippingDays) > 30 ? 'border-red-500 ring-2 ring-red-100' : 'border-(--border) focus:border-(--rust)'}`}
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

        {/* Column 3: Payment Methods Reference */}
        <div className="space-y-8 md:col-span-2 lg:col-span-1">
          <div className="artisan-card space-y-6">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-bold">Payment Methods</h3>
              <Link href="/seller/profile" className="text-[10px] font-bold text-(--rust) uppercase tracking-widest hover:underline">Update in Profile</Link>
            </div>
            <p className="text-[10px] text-(--muted) -mt-4 uppercase tracking-widest leading-relaxed">We use your workshop&apos;s global payment details from your profile for all transactions.</p>
            
            <div className="space-y-4">
              {/* GCash Toggle */}
              <button
                type="button"
                onClick={() => setFormData(prev => ({ ...prev, allowGcash: !prev.allowGcash }))}
                className={`w-full p-4 rounded-2xl border flex items-center justify-between transition-all duration-200 ${
                  formData.allowGcash
                    ? 'bg-blue-50/50 border-blue-300 ring-1 ring-blue-200'
                    : 'bg-gray-50 border-gray-200 opacity-60'
                }`}
              >
                <div className="flex items-center gap-3">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white transition-colors ${
                    formData.allowGcash ? 'bg-blue-600' : 'bg-gray-400'
                  }`}><CreditCard size={16} /></div>
                  <div className="text-left">
                    <div className={`text-[10px] font-black uppercase tracking-widest ${
                      formData.allowGcash ? 'text-blue-600' : 'text-gray-400'
                    }`}>GCash {formData.allowGcash ? 'Enabled' : 'Disabled'}</div>
                    <div className="text-[9px] text-(--muted) font-bold">Standard Registry Payment</div>
                  </div>
                </div>
                {formData.allowGcash
                  ? <CheckCircle className="text-blue-600 w-5 h-5 shrink-0" />
                  : <XCircle className="text-gray-400 w-5 h-5 shrink-0" />
                }
              </button>

              {formData.allowGcash && (
                <div className="p-4 border border-blue-100 rounded-2xl bg-blue-50/20 space-y-3 mt-1">
                  <div className="space-y-1">
                    <label className="text-[9px] font-bold uppercase tracking-widest text-(--muted)">GCash Number *</label>
                    <input 
                      type="text" 
                      value={formData.gcashNumber || ''} 
                      maxLength={INPUT_LIMITS.mobileNumber} 
                      onChange={e => setFormData(prev => ({ ...prev, gcashNumber: sanitizePhoneInput(e.target.value) }))}
                      className="w-full px-3 py-2 bg-white border border-(--border) rounded-xl text-xs focus:outline-none focus:border-blue-500 text-(--charcoal)"
                      placeholder="09XXXXXXXXX"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-[9px] font-bold uppercase tracking-widest text-(--muted)">GCash Account Name *</label>
                    <input 
                      type="text" 
                      value={formData.gcashName || ''} 
                      onChange={e => setFormData(prev => ({ ...prev, gcashName: e.target.value }))}
                      className="w-full px-3 py-2 bg-white border border-(--border) rounded-xl text-xs focus:outline-none focus:border-blue-500 text-(--charcoal)"
                      placeholder="Account Holder Name"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-[9px] font-bold uppercase tracking-widest text-(--muted)">GCash QR Code *</label>
                    <div className="flex items-center gap-3">
                      {gcashQrPreview ? (
                        <div className="w-12 h-12 rounded-lg border border-(--border) overflow-hidden bg-white shrink-0 relative group">
                          <img src={resolveBackendImageUrl(gcashQrPreview)} alt="GCash QR preview" className="w-full h-full object-cover" />
                          <button
                            type="button"
                            onClick={() => {
                              setGcashQrFile(null);
                              setGcashQrPreview(null);
                            }}
                            className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white transition-opacity"
                          >
                            <X size={12} />
                          </button>
                        </div>
                      ) : (
                        <div className="w-12 h-12 rounded-lg border border-dashed border-(--border) flex items-center justify-center text-(--muted) text-[8px] uppercase font-bold shrink-0">No QR</div>
                      )}
                      <input 
                        type="file" 
                        accept="image/*" 
                        onClick={() => alert("Make sure the image is clear and visible")}
                        onChange={async (e) => {
                          const file = e.target.files[0];
                          if (!file) return;
                          const err = await validateImageFile(file, "GCash QR");
                          if (err) {
                            showToast("error", err);
                            e.target.value = "";
                            return;
                          }
                          setGcashQrFile(file);
                          setGcashQrPreview(URL.createObjectURL(file));
                        }}
                        className="text-[10px] text-(--muted) flex-1"
                      />
                    </div>
                  </div>
                </div>
              )}

              {/* Maya Toggle */}
              <button
                type="button"
                onClick={() => setFormData(prev => ({ ...prev, allowMaya: !prev.allowMaya }))}
                className={`w-full p-4 rounded-2xl border flex items-center justify-between transition-all duration-200 ${
                  formData.allowMaya
                    ? 'bg-teal-50/50 border-teal-300 ring-1 ring-teal-200'
                    : 'bg-gray-50 border-gray-200 opacity-60'
                }`}
              >
                <div className="flex items-center gap-3">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white transition-colors ${
                    formData.allowMaya ? 'bg-teal-600' : 'bg-gray-400'
                  }`}><CreditCard size={16} /></div>
                  <div className="text-left">
                    <div className={`text-[10px] font-black uppercase tracking-widest ${
                      formData.allowMaya ? 'text-teal-600' : 'text-gray-400'
                    }`}>Maya {formData.allowMaya ? 'Enabled' : 'Disabled'}</div>
                    <div className="text-[9px] text-(--muted) font-bold">Digital Wallet Transfer</div>
                  </div>
                </div>
                {formData.allowMaya
                  ? <CheckCircle className="text-teal-600 w-5 h-5 shrink-0" />
                  : <XCircle className="text-gray-400 w-5 h-5 shrink-0" />
                }
              </button>

              {formData.allowMaya && (
                <div className="p-4 border border-teal-100 rounded-2xl bg-teal-50/20 space-y-3 mt-1">
                  <div className="space-y-1">
                    <label className="text-[9px] font-bold uppercase tracking-widest text-(--muted)">Maya Number *</label>
                    <input 
                      type="text" 
                      value={formData.mayaNumber || ''} 
                      maxLength={INPUT_LIMITS.mobileNumber} 
                      onChange={e => setFormData(prev => ({ ...prev, mayaNumber: sanitizePhoneInput(e.target.value) }))}
                      className="w-full px-3 py-2 bg-white border border-(--border) rounded-xl text-xs focus:outline-none focus:border-teal-500 text-(--charcoal)"
                      placeholder="09XXXXXXXXX"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-[9px] font-bold uppercase tracking-widest text-(--muted)">Maya Account Name *</label>
                    <input 
                      type="text" 
                      value={formData.mayaName || ''} 
                      onChange={e => setFormData(prev => ({ ...prev, mayaName: e.target.value }))}
                      className="w-full px-3 py-2 bg-white border border-(--border) rounded-xl text-xs focus:outline-none focus:border-teal-500 text-(--charcoal)"
                      placeholder="Account Holder Name"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-[9px] font-bold uppercase tracking-widest text-(--muted)">Maya QR Code *</label>
                    <div className="flex items-center gap-3">
                      {mayaQrPreview ? (
                        <div className="w-12 h-12 rounded-lg border border-(--border) overflow-hidden bg-white shrink-0 relative group">
                          <img src={resolveBackendImageUrl(mayaQrPreview)} alt="Maya QR preview" className="w-full h-full object-cover" />
                          <button
                            type="button"
                            onClick={() => {
                              setMayaQrFile(null);
                              setMayaQrPreview(null);
                            }}
                            className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white transition-opacity"
                          >
                            <X size={12} />
                          </button>
                        </div>
                      ) : (
                        <div className="w-12 h-12 rounded-lg border border-dashed border-(--border) flex items-center justify-center text-(--muted) text-[8px] uppercase font-bold shrink-0">No QR</div>
                      )}
                      <input 
                        type="file" 
                        accept="image/*" 
                        onClick={() => alert("Make sure the image is clear and visible")}
                        onChange={async (e) => {
                          const file = e.target.files[0];
                          if (!file) return;
                          const err = await validateImageFile(file, "Maya QR");
                          if (err) {
                            showToast("error", err);
                            e.target.value = "";
                            return;
                          }
                          setMayaQrFile(file);
                          setMayaQrPreview(URL.createObjectURL(file));
                        }}
                        className="text-[10px] text-(--muted) flex-1"
                      />
                    </div>
                  </div>
                </div>
              )}
            </div>

            <div className="p-4 rounded-2xl bg-amber-50 border border-amber-100">
              <p className="text-[10px] font-bold text-amber-800 leading-relaxed italic">
                Ensure your GCash/Maya QR codes are up-to-date in your profile to avoid fulfillment delays.
              </p>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="btn-primary w-full py-5 text-base shadow-xl"
          >
            {loading ? <Loader2 className="w-5 h-5 animate-spin mx-auto" /> : <>Save Changes <Save className="w-5 h-5 ml-1" /></>}
          </button>
          {loading && uploadProgress > 0 && (
            <div className="space-y-2">
              <div className="h-2 w-full overflow-hidden rounded-full bg-(--border)">
                <div className="h-full rounded-full bg-(--rust) transition-all" style={{ width: `${uploadProgress}%` }} />
              </div>
              <p className="text-[10px] font-bold uppercase tracking-widest text-(--muted) text-center">
                Uploading {uploadProgress}%
              </p>
            </div>
          )}
        </div>
      </form>
    </div>
  );
}
