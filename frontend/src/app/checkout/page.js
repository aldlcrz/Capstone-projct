"use client";
import React, { useState, useEffect, Suspense, useMemo } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import CustomerLayout from "@/components/CustomerLayout";
import Image from "next/image";
import Link from "next/link";
import PsgcSelector from "@/components/PsgcSelector";
import {
  CreditCard,
  Truck,
  ShieldCheck,
  MapPin,
  ArrowLeft,
  ArrowRight,
  CheckCircle2,
  AlertCircle,
  ShieldAlert,
  Upload,
  Phone,
  Scan,
  Loader2,
  Lock,
  ChevronRight,
  History,
  ShoppingCart,
  BookOpen,
  X,
  Plus,
  Minus,
  Trash2,
  User,
  Home,
  Pencil,
  Search,
  ChevronDown,
  Map as MapIcon,
  Trash,
  Check
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, getApiErrorMessage, getTokenForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { getProductImageSrc, resolveBackendImageUrl } from "@/lib/productImages";
import { validateImageFile } from "@/lib/imageUploadValidation";
import dynamic from "next/dynamic";
import {
  INPUT_LIMITS,
  normalizeAddressPayload,
  sanitizeAddressLineInput,
  sanitizePaymentReferenceInput,
  sanitizePersonNameInput,
  sanitizePhoneInput,
  sanitizePlaceNameInput,
  sanitizePostalCodeInput,
  validatePaymentReference,
} from "@/lib/inputValidation";
import { getCustomerScopedJson, setCustomerScopedJson, removeCustomerScopedItem } from "@/lib/customerStorage";

const LocationPickerMap = dynamic(() => import("@/components/LocationPicker"), { ssr: false });

export default function CheckoutPage() {
  return (
    <Suspense fallback={<div className="min-h-screen flex items-center justify-center bg-white"><Loader2 className="w-10 h-10 animate-spin text-black" /></div>}>
      <CheckoutContent />
    </Suspense>
  );
}

function CheckoutContent() {
  const searchParams = useSearchParams();
  const mode = searchParams.get('mode') || 'cart';
  const [loading, setLoading] = useState(false);
  const [currentStep, setCurrentStep] = useState(1);
  const [showValidation, setShowValidation] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState("GCash");
  const [showMapModal, setShowMapModal] = useState(false);

  // Checkout State
  const [address, setAddress] = useState({
    recipientName: "",
    firstName: "",
    lastName: "",
    phone: "",
    houseNo: "",
    street: "",
    barangay: "",
    city: "",
    province: "",
    postalCode: "",
    latitude: null,
    longitude: null
  });
  const [gcashRef, setGcashRef] = useState("");
  const [screenshot, setScreenshot] = useState(null);
  
  // Start with [] so server and client render identical HTML (no hydration mismatch).
  // The real cart data is loaded from localStorage in a useEffect below.
  const [cartItems, setCartItems] = useState([]);
  const [cartLoaded, setCartLoaded] = useState(false);

  const [savedAddresses, setSavedAddresses] = useState([]);
  const [showAddressBook, setShowAddressBook] = useState(false);
  const [addressModalView, setAddressModalView] = useState("list"); // "list" or "form"
  const [addressTab, setAddressTab] = useState("shipping");
  const [addressSearch, setAddressSearch] = useState("");
  const [editingAddress, setEditingAddress] = useState(null);
  const [showPsgcSelector, setShowPsgcSelector] = useState(false);
  const [psgcTab, setPsgcTab] = useState("region");

  const [selectedOrder, setSelectedOrder] = useState(null);
  const [isNewAddress, setIsNewAddress] = useState(false);
  const [selectedAddressId, setSelectedAddressId] = useState(null);
  const [addressLoaded, setAddressLoaded] = useState(false);
  const [maintenanceMode, setMaintenanceMode] = useState(false);
  const { socket } = useSocket();

  const getCustomerToken = () => {
    if (typeof window === "undefined") return null;
    return getTokenForRole("customer");
  };

  const parsePrice = React.useCallback((value) => {
    if (typeof value === "number") return value;
    return parseFloat(String(value || "0").replace(/[^0-9.]/g, "")) || 0;
  }, []);

  const buildAddressPayload = React.useCallback((source) => {
    const combinedName = `${source.firstName || ""} ${source.lastName || ""}`.trim();
    return {
      ...normalizeAddressPayload({ ...source, recipientName: combinedName || source.recipientName }),
      latitude: source.latitude ?? null,
      longitude: source.longitude ?? null,
      isDefault: Boolean(source.isDefault),
    };
  }, []);

  const refreshAddresses = async () => {
    try {
      const response = await api.get("/addresses");
      setSavedAddresses(response.data);
      return response.data;
    } catch (e) { console.error("Could not load address book"); return []; }
  };

  // Load cart items from localStorage after mount (client-only).
  // Sets cartLoaded=true so the redirect guard in the next effect knows
  // the cart has been checked — prevents a false redirect to /cart.
  useEffect(() => {
    const checkoutMode = new URLSearchParams(window.location.search).get('mode') || 'cart';
    if (checkoutMode === 'buy_now') {
      const item = getCustomerScopedJson("checkout_item", null);
      setCartItems(item ? [item] : []);
    } else {
      setCartItems(getCustomerScopedJson("checkout_items", []));
    }
    setCartLoaded(true);
  }, []);

  useEffect(() => {
    const token = getCustomerToken();
    if (!token && currentStep !== 3) {
      window.location.href = "/login?redirect=" + encodeURIComponent(window.location.pathname + window.location.search);
      return;
    }
    // Only redirect to /cart once we know the cart has been loaded from localStorage.
    // Without this guard, cartItems is still [] when this effect first fires (race condition).
    if (cartLoaded && cartItems.length === 0 && currentStep !== 3) {
      window.location.href = "/cart";
    }

    const fetchSavedAddresses = async () => {
      const data = await refreshAddresses();
      const defaultAddr = data.find(a => a.isDefault) || data[0];
      if (defaultAddr) {
        const names = (defaultAddr.recipientName || "").split(" ");
        setAddress({
          recipientName: defaultAddr.recipientName,
          firstName: names[0] || "",
          lastName: names.slice(1).join(" ") || "",
          phone: defaultAddr.phone,
          houseNo: defaultAddr.houseNo,
          street: defaultAddr.street,
          barangay: defaultAddr.barangay,
          city: defaultAddr.city,
          province: defaultAddr.province,
          postalCode: defaultAddr.postalCode,
          latitude: defaultAddr.latitude,
          longitude: defaultAddr.longitude
        });
        setSelectedAddressId(defaultAddr.id);
        setIsNewAddress(false);
      } else {
        setIsNewAddress(true);
      }
      setAddressLoaded(true);
    };

    const fetchPublicSettings = async () => {
      try {
        const res = await api.get("/admin/public-settings");
        setMaintenanceMode(res.data.maintenanceMode === true || res.data.maintenanceMode === "true");
      } catch (e) { console.error("Failed to fetch public settings"); }
    };
    fetchPublicSettings();

    const handleSettingsUpdated = (data) => {
      if (data.maintenanceMode !== undefined) {
        setMaintenanceMode(data.maintenanceMode === true || data.maintenanceMode === "true");
      }
    };

    if (socket) {
      socket.on('settings_updated', handleSettingsUpdated);
    }
    fetchSavedAddresses();
    return () => {
      if (socket) {
        socket.off('settings_updated', handleSettingsUpdated);
      }
    };
  }, [currentStep, socket, mode, cartItems.length, cartLoaded]);

  const updateItemQuantity = (idx, delta) => {
    setCartItems(prev => {
      const next = [...prev];
      const item = next[idx];
      const newQty = Math.max(1, item.quantity + delta);
      next[idx] = { ...item, quantity: newQty };
      if (mode === 'cart') {
        const fullCart = getCustomerScopedJson("cart", []);
        const updatedCart = fullCart.map(c => {
          if (c.id === item.id && c.size === item.size && (c.variation || "") === (item.variation || "")) {
            return { ...c, quantity: newQty };
          }
          return c;
        });
        setCustomerScopedJson("cart", updatedCart);
        setCustomerScopedJson("checkout_items", next);
      } else {
        setCustomerScopedJson("checkout_item", next[0]);
      }
      return next;
    });
  };

  const subtotal = cartItems.reduce((acc, item) => acc + (parsePrice(item.price) * item.quantity), 0);
  const promoCode = -15.00;
  const deliveryService = 15.00;
  const vat = 0.00;
  const total = subtotal + promoCode + deliveryService + vat;

  const handleNext = async () => {
    if (maintenanceMode) {
      alert("Orders are temporarily paused for maintenance. Please try again later.");
      return;
    }
    if (currentStep === 1) {
      const combined = buildAddressPayload(address);
      if (!combined.recipientName || !combined.phone || !combined.houseNo) {
        setShowValidation(true);
        alert("Please complete the required shipping information.");
        return;
      }
      setCurrentStep(2);
    } else if (currentStep === 2) {
      if (!gcashRef || !screenshot) {
        alert("Please enter your payment reference number and upload the screenshot of your receipt.");
        return;
      }
      handlePlaceOrder(buildAddressPayload(address), gcashRef);
    }
  };

  const handlePlaceOrder = async (validatedAddress = null, validatedPaymentReference = "") => {
    setLoading(true);
    try {
      const formData = new FormData();
      const shippingAddress = validatedAddress || buildAddressPayload(address);
      formData.append("items", JSON.stringify(cartItems));
      formData.append("shippingAddress", JSON.stringify(shippingAddress));
      if (!isNewAddress && selectedAddressId) {
        formData.append("addressId", selectedAddressId);
      }
      formData.append("paymentMethod", paymentMethod);
      if (validatedPaymentReference) formData.append("paymentReference", validatedPaymentReference);
      if (screenshot) formData.append("paymentScreenshot", screenshot);
      const response = await api.post("/orders", formData);
      setSelectedOrder(response.data);
      setLoading(false);
      setCurrentStep(3);
      if (mode === 'cart') {
        const cart = getCustomerScopedJson("cart", []);
        const remaining = cart.filter(item => !cartItems.some(ci => ci.id === item.id && ci.size === item.size && (ci.variation || "") === (item.variation || "")));
        setCustomerScopedJson("cart", remaining);
        window.dispatchEvent(new Event('storage'));
      }
      removeCustomerScopedItem("checkout_items");
      removeCustomerScopedItem("checkout_item");
    } catch (err) {
      console.error("Order placement failed:", err);
      alert(getApiErrorMessage(err, "Failed to place order."));
      setLoading(false);
    }
  };

  const paymentOptions = [
    { id: "GCash", label: "GCash", icon: <div className="flex items-center justify-center border border-blue-200 bg-blue-50 px-2 py-0.5 rounded text-[11px] font-bold text-blue-600 tracking-wider">GCASH</div> },
    { id: "Maya", label: "Maya", icon: <div className="flex items-center justify-center border border-green-200 bg-green-50 px-2 py-0.5 rounded text-[11px] font-bold text-green-600 tracking-wider">MAYA</div> },
  ];

  const filteredAddresses = useMemo(() => {
    return savedAddresses.filter(addr => {
      const search = addressSearch.toLowerCase();
      return (
        addr.recipientName?.toLowerCase().includes(search) ||
        addr.houseNo?.toLowerCase().includes(search) ||
        addr.street?.toLowerCase().includes(search) ||
        addr.barangay?.toLowerCase().includes(search) ||
        addr.city?.toLowerCase().includes(search)
      );
    });
  }, [savedAddresses, addressSearch]);

  const handleEditAddress = (addr) => {
    const names = (addr.recipientName || "").split(" ");
    setEditingAddress({
      ...addr,
      firstName: names[0] || "",
      lastName: names.slice(1).join(" ") || "",
    });
    setAddressModalView("form");
  };

  const handleSaveAddressFromForm = async () => {
    if (!editingAddress.firstName || !editingAddress.phone || !editingAddress.houseNo) {
      alert("Please fill in all required fields.");
      return;
    }
    setLoading(true);
    try {
      const payload = buildAddressPayload(editingAddress);
      if (editingAddress.id) {
        await api.put(`/addresses/${editingAddress.id}`, payload);
      } else {
        await api.post("/addresses", payload);
      }
      await refreshAddresses();
      setAddressModalView("list");
      setEditingAddress(null);
    } catch (err) {
      alert(getApiErrorMessage(err, "Failed to save address."));
    } finally {
      setLoading(false);
    }
  };

  return (
    <CustomerLayout layoutBg="bg-white" contentPadding="py-8">
      <div className="max-w-[1200px] mx-auto px-4 bg-white min-h-screen">
        {/* Breadcrumbs */}
        <nav className="flex items-center gap-2 text-sm text-gray-500 mb-10">
          <Link href="/" className="flex items-center gap-1 hover:text-black transition-colors font-medium">
            <ChevronRight className="w-4 h-4 rotate-180" /> Home
          </Link>
          <span className="text-gray-300">/</span>
          <span className="text-black font-semibold">Products</span>
        </nav>

        {/* Stepper */}
        <div className="flex items-center gap-12 mb-12 border-b border-gray-100 pb-2 overflow-x-auto no-scrollbar">
          <div className={`flex items-center gap-3 border-b-2 pb-2 relative translate-y-[2px] transition-colors ${currentStep >= 1 ? 'border-black' : 'border-transparent text-gray-300'}`}>
            <div className={`w-6 h-6 rounded-full flex items-center justify-center ${currentStep > 1 ? 'bg-gray-400' : currentStep === 1 ? 'bg-black' : 'border border-gray-300'}`}>
              {currentStep > 1 ? <CheckCircle2 className="w-4 h-4 text-white" /> : <span className={`text-[11px] font-bold ${currentStep === 1 ? 'text-white' : 'text-gray-300'}`}>1</span>}
            </div>
            <span className={`text-sm font-bold whitespace-nowrap ${currentStep >= 1 ? 'text-black' : 'text-gray-300'}`}>Costumer Information</span>
          </div>
          <div className={`flex items-center gap-3 pb-2 relative translate-y-[2px] border-b-2 transition-colors ${currentStep === 2 ? 'border-black' : 'border-transparent text-gray-300'}`}>
            <div className={`w-6 h-6 rounded-full flex items-center justify-center ${currentStep === 2 ? 'bg-black' : 'border border-gray-300'}`}>
              <span className={`text-[11px] font-bold ${currentStep === 2 ? 'text-white' : 'text-gray-300'}`}>2</span>
            </div>
            <span className={`text-sm font-bold whitespace-nowrap ${currentStep === 2 ? 'text-black' : 'text-gray-300'}`}>Payment Details</span>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
          {/* Main Form Area */}
          <div className="lg:col-span-7 space-y-12">
            <AnimatePresence mode="wait">
              {currentStep < 3 ? (
                <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="space-y-10">
                  <section>
                    <div className="mb-6">
                      <h1 className="text-2xl font-bold text-black mb-2">Check Out Your Items</h1>
                      <p className="text-sm text-gray-400 font-medium">For a better experience, check your item and choose your shipping before ordering.</p>
                    </div>
                    <div className="mb-4">
                      <div className="relative border-2 rounded-xl p-4 transition-all border-gray-100 focus-within:border-black">
                        <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Full Name</label>
                        <div className="flex items-center gap-3">
                          <User className="w-4 h-4 text-gray-300" />
                          <input 
                            type="text" 
                            className="w-full font-bold text-black outline-none bg-transparent" 
                            placeholder="Mohammad Abdullah" 
                            value={address.recipientName || ((address.firstName || '') + ' ' + (address.lastName || '')).trim()} 
                            onChange={(e) => {
                              const val = sanitizePersonNameInput(e.target.value);
                              const parts = val.split(" ");
                              setAddress({
                                ...address, 
                                recipientName: val,
                                firstName: parts[0] || "",
                                lastName: parts.slice(1).join(" ") || ""
                              });
                            }} 
                          />
                        </div>
                      </div>
                    </div>
                    <button 
                      type="button"
                      onClick={() => { setAddressModalView("list"); setShowAddressBook(true); }}
                      className="w-full relative border-2 border-gray-100 rounded-xl p-6 text-left hover:border-black transition-colors focus:outline-none focus:border-black group cursor-pointer"
                    >
                       <div className="flex items-start justify-between">
                          <div className="flex gap-4 w-full">
                             <Home className="w-5 h-5 text-gray-400 mt-1 shrink-0 group-hover:text-black transition-colors" />
                             <div className="space-y-1 w-full">
                                <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block cursor-pointer">Delivery Address</label>
                                <textarea className="w-full font-medium text-gray-500 outline-none bg-transparent resize-none min-h-[60px] cursor-pointer group-hover:text-black transition-colors pointer-events-none" placeholder="Al, Helal 36, Noyasark, Sylhet -3100" value={address.houseNo ? `${address.houseNo} ${address.street}, ${address.barangay}, ${address.city}` : ""} readOnly tabIndex={-1} />
                             </div>
                          </div>
                          <div className="text-gray-300 group-hover:text-black transition-colors shrink-0">
                             <Pencil className="w-4 h-4" />
                          </div>
                       </div>
                    </button>
                  </section>
                  <section>
                    <div className="mb-6">
                      <h2 className="text-xl font-bold text-black mb-1">Payment Method</h2>
                      <p className="text-sm text-gray-400 font-medium">Select the bank for payment of your item</p>
                    </div>
                    <div className="space-y-3">
                      {paymentOptions.map((option) => (
                        <div key={option.id} className={`rounded-xl border-2 transition-all overflow-hidden ${paymentMethod === option.id ? 'border-black bg-white shadow-sm' : 'border-gray-50 bg-white hover:border-gray-200'}`}>
                          <button onClick={() => setPaymentMethod(option.id)} className="w-full flex items-center justify-between p-5">
                            <div className="flex items-center gap-4">
                              {option.icon}
                              <span className="text-sm font-bold text-gray-500">{option.label}</span>
                            </div>
                            <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${paymentMethod === option.id ? 'border-black' : 'border-gray-200'}`}>
                              {paymentMethod === option.id && <div className="w-2.5 h-2.5 rounded-full bg-black" />}
                            </div>
                          </button>
                          
                          {paymentMethod === option.id && (
                            <div className="px-5 pb-5">
                              <div className="pt-4 border-t border-gray-100 flex flex-col md:flex-row gap-6">
                                <div className="flex flex-col items-center justify-center space-y-2 bg-gray-50 p-4 rounded-xl border border-gray-100 md:w-1/3 shrink-0">
                                  <div className="w-28 h-28 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-200">
                                    <Scan className="w-12 h-12 text-gray-300" />
                                  </div>
                                  <span className="text-xs font-bold text-gray-500">Scan to Pay</span>
                                </div>
                                <div className="flex-1 space-y-4">
                                  <div className="bg-blue-50/50 p-3 rounded-xl border border-blue-100/50">
                                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Account Details</label>
                                    <div className="flex items-center gap-2 mb-1">
                                      <Phone className="w-4 h-4 text-black" />
                                      <span className="font-bold text-lg text-black tracking-wide">{option.id === 'GCash' ? '0912 345 6789' : '0998 765 4321'}</span>
                                    </div>
                                    <p className="text-xs font-bold text-gray-600">Account Name: Juan Dela Cruz</p>
                                  </div>
                                  
                                  <div className="space-y-3 pt-2">
                                    <div className="relative">
                                      <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Reference Number</label>
                                      <input 
                                        type="text" 
                                        placeholder={`Enter ${option.id} Ref No.`}
                                        value={gcashRef}
                                        onChange={(e) => setGcashRef(e.target.value)}
                                        className="w-full px-4 py-2.5 text-sm font-bold text-black border border-gray-200 rounded-xl focus:border-black outline-none transition-colors placeholder:font-medium"
                                      />
                                    </div>
                                    <div className="relative">
                                      <label className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Upload Receipt</label>
                                      <input 
                                        type="file" 
                                        accept="image/*"
                                        onChange={(e) => setScreenshot(e.target.files[0])}
                                        className="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-black file:text-white hover:file:bg-gray-800 cursor-pointer border border-gray-100 rounded-xl p-1"
                                      />
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  </section>
                </motion.div>
              ) : (
                <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="py-20 text-center space-y-6">
                  <div className="w-20 h-20 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto">
                    <CheckCircle2 className="w-10 h-10" />
                  </div>
                  <h2 className="text-2xl font-bold">Order Received!</h2>
                  <p className="text-gray-500">Thank you for your purchase.</p>
                  <Link href="/orders" className="inline-block bg-black text-white px-8 py-3 rounded-xl font-bold text-sm">View Orders</Link>
                </motion.div>
              )}
            </AnimatePresence>
          </div>

          {/* Order Summary Sidebar */}
          <div className="lg:col-span-5">
            <div className="bg-[#F9FAFB] rounded-4xl p-8 border border-gray-100 sticky top-10">
              <div className="mb-8">
                <h2 className="text-xl font-bold text-black mb-1">Current Order</h2>
                <p className="text-xs text-gray-400 font-medium">The sum of all total payments for goods there</p>
              </div>
              <div className="space-y-4 mb-10 overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
                {cartItems.map((item, idx) => (
                  <div key={idx} className="bg-white rounded-2xl p-4 flex gap-4 border border-gray-50">
                    <div className="w-16 h-16 bg-gray-50 rounded-xl overflow-hidden shrink-0 relative">
                      <Image src={getProductImageSrc(item.image)} alt={item.name} fill className="object-cover" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex justify-between items-start gap-2">
                        <h3 className="text-sm font-bold text-black line-clamp-1">{item.name}</h3>
                        <span className="text-sm font-bold text-black whitespace-nowrap">₱{(parsePrice(item.price)).toLocaleString()}</span>
                      </div>
                      <p className="text-[10px] font-bold text-gray-400 mt-0.5">{item.sku || 'TP399K'}</p>
                      <div className="flex items-center justify-between mt-3">
                         <span className="text-[10px] font-bold text-gray-400">Quantity : {item.quantity}</span>
                         <div className="flex items-center gap-2 bg-gray-50 rounded-lg p-1">
                            <button onClick={() => updateItemQuantity(idx, -1)} className="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-black transition-colors"><Minus className="w-3" /></button>
                            <span className="text-xs font-bold text-black min-w-[12px] text-center">{item.quantity}</span>
                            <button onClick={() => updateItemQuantity(idx, 1)} className="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-black transition-colors"><Plus className="w-3" /></button>
                         </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              <div className="space-y-4 border-t border-gray-100 pt-8 mb-8">
                <div className="flex justify-between items-center">
                  <span className="text-lg font-bold text-black">Subtotal</span>
                  <span className="text-lg font-bold text-black">₱{subtotal.toLocaleString()}</span>
                </div>
                <div className="space-y-3 pt-4">
                  <div className="flex justify-between items-center text-sm font-medium text-gray-400"><span>Items</span><span className="text-black font-bold">{cartItems.reduce((a, b) => a + b.quantity, 0)}x</span></div>
                  <div className="flex justify-between items-center text-sm font-medium text-gray-400"><span>Code Promo</span><span className="text-black font-bold">- ₱{Math.abs(promoCode).toLocaleString()}</span></div>
                  <div className="flex justify-between items-center text-sm font-medium text-gray-400"><span>Delivery Service</span><span className="text-black font-bold">₱{deliveryService.toLocaleString()}</span></div>
                  <div className="flex justify-between items-center text-sm font-medium text-gray-400"><span>Vat (0%)</span><span className="text-black font-bold">₱{vat.toLocaleString()}</span></div>
                </div>
              </div>
              <button onClick={handleNext} disabled={loading} className="w-full bg-black text-white py-5 rounded-2xl text-sm font-bold transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3 shadow-xl shadow-black/10">
                {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : `Pay ₱${total.toLocaleString()}`}
              </button>
            </div>
          </div>
        </div>

        {/* Address Book Modal Overlay */}
        <AnimatePresence>
          {showAddressBook && (
            <div className="fixed inset-0 z-100 flex items-center justify-center p-4">
              <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setShowAddressBook(false)} className="absolute inset-0 bg-black/40 backdrop-blur-sm" />
              <motion.div initial={{ scale: 0.95, opacity: 0, y: 20 }} animate={{ scale: 1, opacity: 1, y: 0 }} exit={{ scale: 0.95, opacity: 0, y: 20 }} className="relative w-full max-w-2xl bg-white rounded-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                
                {addressModalView === "list" ? (
                  /* LIST VIEW */
                  <>
                    <div className="p-8 pb-4 flex items-center gap-6">
                      <button onClick={() => setShowAddressBook(false)} className="w-10 h-10 rounded-full border border-gray-100 flex items-center justify-center hover:bg-gray-50 transition-colors"><ChevronRight className="w-5 h-5 rotate-180 text-black" /></button>
                      <h2 className="text-2xl font-bold text-black">Address</h2>
                    </div>
                    <div className="flex-1 overflow-y-auto custom-scrollbar px-8 py-4 space-y-8">

                      <div className="flex gap-4 mb-6">
                        <div className="flex-1 relative">
                          <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                          <input type="text" placeholder="Search addresses..." value={addressSearch} onChange={(e) => setAddressSearch(e.target.value)} className="w-full pl-11 pr-4 py-3 bg-[#F8F8F6] border border-gray-200 rounded-xl text-sm focus:border-[#C0422A] outline-none transition-colors" />
                        </div>
                        <button onClick={() => { setEditingAddress({ firstName: "", lastName: "", phone: "", houseNo: "", street: "", barangay: "", city: "", province: "", postalCode: "", isDefault: false }); setAddressModalView("form"); }} className="px-6 py-3 border border-[#C0422A] text-[#C0422A] rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#FFF5F2] transition-colors">
                          <Plus className="w-4 h-4" /> Add address
                        </button>
                      </div>
                      <div className="space-y-0 pb-8">
                        <h3 className="text-[11px] font-bold text-gray-500 uppercase tracking-widest mb-4">Address list</h3>
                        {filteredAddresses.map(addr => (
                          <div key={addr.id} className={`group relative border rounded-xl p-4 mb-4 transition-all ${selectedAddressId === addr.id ? 'border-[#C0422A]' : 'border-gray-200 hover:border-gray-300'}`}>
                            <div className="flex gap-4">
                              <div className={`w-14 h-14 rounded-xl flex items-center justify-center shrink-0 ${addr.isDefault ? 'bg-[#FFF5F2]' : 'bg-[#F6F6F6]'}`}>
                                <MapPin className={`w-6 h-6 ${addr.isDefault ? 'text-[#C0422A]' : 'text-gray-400'}`} />
                              </div>
                              <div className="flex-1 min-w-0 flex flex-col items-start">
                                <span className={`px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-widest mb-1 ${addr.isDefault ? 'bg-[#FFF5F2] text-[#C0422A]' : 'bg-[#F6F6F6] text-gray-500'}`}>{addr.isDefault ? "Main house" : "Office"}</span>
                                <h4 className="text-[15px] font-bold text-black">{addr.recipientName}</h4>
                                <p className="text-xs text-gray-600 mt-0.5">{addr.phone}</p>
                                <p className="text-xs text-gray-500 mt-0.5 truncate w-full">{addr.houseNo} {addr.street}{addr.street ? ', ' : ''}{addr.barangay}{addr.barangay ? ', ' : ''}{addr.city}</p>
                              </div>
                              <div className="flex flex-col items-end justify-between shrink-0">
                                <button onClick={() => { setSelectedAddressId(addr.id); const n = (addr.recipientName||"").split(" "); setAddress({...address, ...addr, firstName: n[0]||"", lastName: n.slice(1).join(" ")||""}); setIsNewAddress(false); }} className={`w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all ${selectedAddressId === addr.id ? 'border-[#C0422A]' : 'border-gray-300'}`}>
                                  {selectedAddressId === addr.id && <div className="w-2.5 h-2.5 rounded-full bg-[#C0422A]" />}
                                </button>
                                <div className="flex gap-2 mt-6">
                                  <button onClick={() => handleEditAddress(addr)} className="px-4 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors">Edit</button>
                                  <button className="px-4 py-1.5 bg-white border border-[#9D3B31] rounded-lg text-xs font-bold text-[#9D3B31] hover:bg-[#FFF5F2] transition-colors" onClick={async () => { if (confirm("Delete this address?")) { await api.delete(`/addresses/${addr.id}`); refreshAddresses(); } }}>Delete</button>
                                </div>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </>
                ) : (
                  /* FORM VIEW - EXACTLY LIKE SCREENSHOT */
                  <>
                    <div className="p-8 pb-4 flex items-center justify-between border-b border-gray-50">
                      <h2 className="text-xl font-medium text-gray-800">{editingAddress?.id ? "Edit Address" : "New Address"}</h2>
                      <button onClick={() => setAddressModalView("list")} className="text-gray-400 hover:text-black"><X className="w-5 h-5" /></button>
                    </div>
                    
                    <div className="flex-1 overflow-y-auto custom-scrollbar p-8 pt-6 space-y-6">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="relative">
                          <input type="text" placeholder="Full Name" value={editingAddress.recipientName || (editingAddress.firstName + " " + editingAddress.lastName).trim()} onChange={(e) => { const parts = e.target.value.split(" "); setEditingAddress({...editingAddress, recipientName: e.target.value, firstName: parts[0]||"", lastName: parts.slice(1).join(" ")||""}); }} className="w-full px-4 py-3 border border-gray-200 rounded focus:border-[#C0422A] outline-none text-sm placeholder-gray-300" />
                        </div>
                        <div className="relative">
                          <input type="text" placeholder="Phone Number" value={editingAddress.phone} onChange={(e) => setEditingAddress({...editingAddress, phone: e.target.value})} className="w-full px-4 py-3 border border-gray-200 rounded focus:border-[#C0422A] outline-none text-sm placeholder-gray-300" />
                        </div>
                      </div>

                      <div className="relative">
                        <PsgcSelector 
                          value={editingAddress} 
                          onChange={(newVal) => setEditingAddress(newVal)} 
                        />
                      </div>

                      <div className="relative">
                        <input type="text" placeholder="Postal Code" value={editingAddress.postalCode} onChange={(e) => setEditingAddress({...editingAddress, postalCode: e.target.value})} className="w-full px-4 py-3 border border-gray-200 rounded focus:border-[#C0422A] outline-none text-sm placeholder-gray-300" />
                      </div>

                      <div className="relative">
                        <textarea placeholder="Street Name, Building, House No." value={editingAddress.houseNo} onChange={(e) => setEditingAddress({...editingAddress, houseNo: e.target.value})} className="w-full px-4 py-3 border border-gray-200 rounded focus:border-[#C0422A] outline-none text-sm placeholder-gray-300 min-h-[80px]" />
                      </div>

                      {/* REALTIME MAP SECTION */}
                      <div className="relative border border-gray-100 rounded-sm overflow-hidden bg-gray-50 group">
                        {(!editingAddress.latitude || !editingAddress.longitude) ? (
                          <button onClick={() => setShowMapModal(true)} className="w-full h-40 flex flex-col items-center justify-center gap-2 hover:bg-gray-100 transition-colors">
                            <div className="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-300 group-hover:border-[#C0422A] group-hover:text-[#C0422A]"><Plus className="w-5 h-5" /></div>
                            <span className="text-sm font-medium text-gray-300 group-hover:text-[#C0422A]">Add Location</span>
                          </button>
                        ) : (
                          <div className="h-48 relative">
                            <LocationPickerMap 
                              initialLat={editingAddress.latitude} 
                              initialLng={editingAddress.longitude}
                              readOnly={true}
                            />
                            <button onClick={() => setShowMapModal(true)} className="absolute top-4 right-4 z-20 bg-white/90 backdrop-blur-sm p-2 rounded-full shadow-lg border border-gray-100 text-[#C0422A] hover:bg-white"><MapIcon className="w-4 h-4" /></button>
                          </div>
                        )}
                      </div>

                      <div className="space-y-4">
                        <label className="text-sm text-gray-500">Label As:</label>
                        <div className="flex gap-4">
                          {["Home", "Work"].map(l => (
                            <button key={l} onClick={() => setEditingAddress({...editingAddress, label: l})} className={`px-8 py-2.5 rounded border transition-all text-sm font-medium ${editingAddress.label === l ? 'border-[#C0422A] text-[#C0422A] bg-[#C0422A]/5' : 'border-gray-100 text-gray-400 hover:border-gray-200'}`}>{l}</button>
                          ))}
                        </div>
                      </div>

                      <div className="flex items-center gap-3">
                        <input type="checkbox" id="default-addr" checked={editingAddress.isDefault} onChange={(e) => setEditingAddress({...editingAddress, isDefault: e.target.checked})} className="w-4 h-4 rounded border-gray-300 accent-[#C0422A]" />
                        <label htmlFor="default-addr" className="text-sm text-gray-400 cursor-pointer select-none">Set as Default Address</label>
                      </div>
                    </div>

                    <div className="p-8 pt-4 flex items-center justify-end gap-12">
                      <button onClick={() => setAddressModalView("list")} className="text-sm font-medium text-gray-500 hover:text-black transition-colors">Cancel</button>
                      <button onClick={handleSaveAddressFromForm} disabled={loading} className="px-12 py-3 bg-[#C0422A] text-white rounded-sm text-sm font-medium hover:opacity-95 transition-all shadow-md shadow-[#C0422A]/10 disabled:opacity-50 flex items-center gap-2">
                        {loading && <Loader2 className="w-4 h-4 animate-spin" />} Submit
                      </button>
                    </div>
                  </>
                )}
              </motion.div>
            </div>
          )}
        </AnimatePresence>

        {/* Realtime Map Picker Overlay */}
        <AnimatePresence>
          {showMapModal && (
            <div className="fixed inset-0 z-110 flex items-center justify-center p-4">
              <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setShowMapModal(false)} className="absolute inset-0 bg-black/60 backdrop-blur-sm" />
              <motion.div initial={{ scale: 0.9, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.9, opacity: 0 }} className="relative w-full max-w-4xl bg-white rounded-4xl overflow-hidden shadow-2xl h-[85vh] flex flex-col">
                 <div className="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 className="text-xl font-bold">Pin Your Location</h3>
                    <button onClick={() => setShowMapModal(false)} className="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center hover:bg-gray-100 transition-colors"><X className="w-5 h-5" /></button>
                 </div>
                 <div className="flex-1 relative">
                    <LocationPickerMap 
                      onLocationSelect={(lat, lng, addrObj) => {
                        setEditingAddress(prev => ({ 
                          ...prev, 
                          latitude: lat, 
                          longitude: lng,
                          houseNo: addrObj.houseNo || addrObj.houseNumber || prev.houseNo,
                          street: addrObj.street || prev.street,
                          barangay: addrObj.barangay || prev.barangay,
                          city: addrObj.city || prev.city,
                          province: addrObj.province || prev.province,
                          region: addrObj.region || prev.region,
                          postalCode: addrObj.postalCode || prev.postalCode
                        }));
                        setShowMapModal(false);
                      }}
                    />
                 </div>
                 <div className="p-4 bg-gray-50 text-center"><p className="text-xs text-gray-400 font-medium italic">Move the pin to match your exact location for faster delivery.</p></div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>
      </div>
    </CustomerLayout>
  );
}
