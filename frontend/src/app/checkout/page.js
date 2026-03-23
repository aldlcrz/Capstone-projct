"use client";
import React, { useState, useEffect } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import Image from "next/image";
import Link from "next/link";
import { 
  CreditCard, 
  Truck, 
  ShieldCheck, 
  MapPin, 
  ArrowLeft, 
  ArrowRight, 
  CheckCircle2, 
  AlertCircle,
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
  Trash2
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import dynamic from "next/dynamic";

const LocationPickerMap = dynamic(() => import("@/components/LocationPicker"), { ssr: false });


export default function CheckoutPage() {
  const [loading, setLoading] = useState(false);
  const [currentStep, setCurrentStep] = useState(1);
  const [showValidation, setShowValidation] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState("GCash");
  const [showMap, setShowMap] = useState(false);
  
  // Checkout State
  const [address, setAddress] = useState({ 
    name: "", 
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
  const [cartItems, setCartItems] = useState([]);
  const [mode, setMode] = useState("cart");
  const [savedAddresses, setSavedAddresses] = useState([]);
  const [showAddressBook, setShowAddressBook] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [isNewAddress, setIsNewAddress] = useState(false);
  const [addressLoaded, setAddressLoaded] = useState(false);


  // Address Book Management
  const [addrForm, setAddrForm] = useState({ recipientName: "", phone: "", houseNo: "", street: "", barangay: "", city: "", province: "", postalCode: "", isDefault: false, latitude: null, longitude: null });
  const [addrEditing, setAddrEditing] = useState(null);
  const [showAddrForm, setShowAddrForm] = useState(false);
  const [addrMapPicker, setAddrMapPicker] = useState(false);
  const [addrLoading, setAddrLoading] = useState(false);

  const parsePrice = React.useCallback((value) => {
    if (typeof value === "number") return value;
    return parseFloat(String(value || "0").replace(/[^0-9.]/g, "")) || 0;
  }, []);

  const refreshAddresses = async () => {
    try {
      const response = await api.get("/addresses");
      setSavedAddresses(response.data);
      return response.data;
    } catch (e) { console.error("Could not load address book"); return []; }
  };

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const checkoutMode = urlParams.get('mode') || 'cart';
    setMode(checkoutMode);

    let items = [];
    if (checkoutMode === 'buy_now') {
      const item = JSON.parse(localStorage.getItem("checkout_item") || "null");
      if (item) items = [item];
    } else {
      items = JSON.parse(localStorage.getItem("checkout_items") || "[]");
    }

    if (items.length === 0 && currentStep !== 3) {
       window.location.href = "/cart";
    }
    setCartItems(items);

    const fetchSavedAddresses = async () => {
      const data = await refreshAddresses();
      const defaultAddr = data.find(a => a.isDefault) || data[0];
      if (defaultAddr) {
        setAddress({
          name: defaultAddr.recipientName,
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
        setIsNewAddress(false);
      } else {
        setIsNewAddress(true);
      }
      setAddressLoaded(true);
    };
    fetchSavedAddresses();

  }, [currentStep]);

  const openAddrForm = (addr = null) => {
    if (addr) {
      setAddrEditing(addr);
      setAddrForm({ ...addr });
    } else {
      setAddrEditing(null);
      setAddrForm({ recipientName: "", phone: "", houseNo: "", street: "", barangay: "", city: "", province: "", postalCode: "", isDefault: false, latitude: null, longitude: null });
    }
    setAddrMapPicker(false);
    setShowAddrForm(true);
  };

  const handleSaveAddr = async (e) => {
    e.preventDefault();
    setAddrLoading(true);
    try {
      if (addrEditing) {
        await api.put(`/addresses/${addrEditing.id}`, addrForm);
      } else {
        await api.post("/addresses", addrForm);
      }
      setShowAddrForm(false);
      refreshAddresses();
    } catch (err) {
      alert("Failed to save address.");
    } finally {
      setAddrLoading(false);
    }
  };

  const handleDeleteAddr = async (id) => {
    if (!confirm("Delete this address?")) return;
    try { await api.delete(`/addresses/${id}`); refreshAddresses(); } catch { alert("Failed to delete."); }
  };

  const handleSetDefaultAddr = async (id) => {
    try { await api.patch(`/addresses/${id}/set-default`, {}); refreshAddresses(); } catch { alert("Failed to set default."); }
  };

  const selectAddress = (addr) => {
    setAddress({
      name: addr.recipientName,
      phone: addr.phone,
      houseNo: addr.houseNo,
      street: addr.street,
      barangay: addr.barangay,
      city: addr.city,
      province: addr.province,
      postalCode: addr.postalCode,
      latitude: addr.latitude,
      longitude: addr.longitude
    });
    setIsNewAddress(false);
    setShowAddressBook(false);
  };


  const subtotal = cartItems.reduce((acc, item) => acc + (parsePrice(item.price) * item.quantity), 0);
  const shipping = cartItems.reduce((max, item) => Math.max(max, parsePrice(item.shippingFee)), 0);
  const maxDays = cartItems.reduce((max, item) => Math.max(max, parseInt(item.shippingDays || 3)), 0);
  const total = subtotal + shipping;

  const isGcashPayment = paymentMethod === "GCash";
  const isBuyNowMode = mode === "buy_now";

  const handleNext = () => {
    if (currentStep === 1) {
       const required = ["name", "phone", "houseNo", "street", "barangay", "city", "province", "postalCode"];
       const missing = required.filter(field => !address[field]);
       if (missing.length > 0) {
          setShowValidation(true);
          return;
       }
       setCurrentStep(2);
    } else if (currentStep === 2) {
        if (isGcashPayment) {
          const digitsOnly = gcashRef.replace(/\D/g, "");
          if (!digitsOnly || digitsOnly.length < 8) {
            alert("GCash reference must be at least 8 digits long.");
            setShowValidation(true);
            return;
          }
          if (!screenshot) {
            setShowValidation(true);
            return;
          }
        }
        
        // Final contact validation before handoff
        const phoneDigits = address.phone.replace(/\D/g, "");
        if (!/^09\d{9}$/.test(phoneDigits)) {
          alert("Please provide a valid 11-digit Philippine mobile number (09...).");
          setShowValidation(true);
          return;
        }

        handlePlaceOrder();
    }
  };

  const handlePlaceOrder = async () => {
    setLoading(true);
    const formData = new FormData();
    formData.append("items", JSON.stringify(cartItems));
    
    // Lazada-style: Ensure all address fields are caught
    formData.append("shippingAddress", JSON.stringify(address));
    formData.append("paymentMethod", paymentMethod);
    if (isGcashPayment) {
      formData.append("paymentReference", gcashRef);
    }
    if (isGcashPayment && screenshot) {
      formData.append("paymentProof", screenshot);
    }

    try {
      // First, attempt to save the address to the user's permanent address book if it's new
      // We check if an address with the same houseNo already exists to avoid duplicates
      if (address.houseNo && address.street) {
        const existing = savedAddresses.find(a => 
          a.houseNo === address.houseNo && a.street === address.street && a.barangay === address.barangay
        );
        if (!existing) {
          try {
            await api.post("/addresses", {
              recipientName: address.name,
              phone: address.phone,
              houseNo: address.houseNo,
              street: address.street,
              barangay: address.barangay,
              city: address.city,
              province: address.province,
              postalCode: address.postalCode,
              latitude: address.latitude,
              longitude: address.longitude,
              isDefault: savedAddresses.length === 0
            });
          } catch (e) {
            console.warn("Failed to auto-save address to book, proceeding with order anyway.");
          }
        }
      }

      const token = localStorage.getItem("token");
      const response = await api.post("/orders", formData, {
        headers: { 
          "Content-Type": "multipart/form-data"
        }
      });
      
      const newOrder = response.data;
      setSelectedOrder(newOrder); // Store for success screen reference
      setLoading(false);
      setCurrentStep(3); // Success
      
      // Cleanup cart if needed
      if (mode === 'cart') {
         const cart = JSON.parse(localStorage.getItem("cart") || "[]");
         const remaining = cart.filter(item => !cartItems.some(ci => ci.id === item.id && ci.size === item.size && (ci.variation || "") === (item.variation || "")));
         localStorage.setItem("cart", JSON.stringify(remaining));
         window.dispatchEvent(new Event('storage'));
      }
      localStorage.removeItem("checkout_items");
      localStorage.removeItem("checkout_item");
    } catch (err) {
      console.error("Artisan order placement failed:", err);
      alert(err.response?.data?.message || "Failed to transmit commission to registry.");
      setLoading(false);
    }
  };


  const steps = [
    { id: 1, label: "SHIPPING", icon: Truck },
    { id: 2, label: "PAYMENT", icon: CreditCard },
    { id: 3, label: "CONFIRMED", icon: CheckCircle2 },
  ];

  return (
    <CustomerLayout>
      <div className={`mx-auto animate-fade-in relative z-10 ${isBuyNowMode ? 'max-w-6xl space-y-8 mb-12' : 'max-w-7xl space-y-12 mb-20'}`}>
        {/* Header & Breadcrumbs */}
        <div className={`flex flex-col md:flex-row justify-between border-b border-[var(--border)] ${isBuyNowMode ? 'md:items-center gap-6 pb-8' : 'items-center gap-8 pb-12'}`}>
          <div>
            <div className="eyebrow uppercase tracking-[0.4em] mb-3">{isBuyNowMode ? 'Direct Checkout' : 'Secure Fulfillment Wizard'}</div>
            <h1 className={`font-serif font-bold tracking-tighter text-[var(--charcoal)] uppercase ${isBuyNowMode ? 'text-4xl md:text-5xl' : 'text-5xl'}`}>
              {isBuyNowMode ? (
                <>Streamlined <span className="text-[var(--rust)] italic lowercase">Checkout</span></>
              ) : (
                <>Heritage <span className="text-[var(--rust)] italic lowercase">Checkout</span></>
              )}
            </h1>
            <p className={`text-[var(--muted)] leading-relaxed ${isBuyNowMode ? 'text-sm mt-3 max-w-xl' : 'text-sm mt-4 max-w-2xl'}`}>
              {isBuyNowMode
                ? 'A tighter single-item checkout with clearer shipping, payment, and order summary sections.'
                : 'Complete your shipping and payment details to place your order with the artisan registry.'}
            </p>
          </div>
          
          <div className={`flex items-center ${isBuyNowMode ? 'gap-4 flex-wrap' : 'gap-6'}`}>
            {steps.map((s, idx) => (
              <React.Fragment key={s.id}>
                <div className={`flex items-center gap-3 transition-all duration-500 ${currentStep >= s.id ? 'opacity-100' : 'opacity-30 grayscale'}`}>
                   <div className={`flex items-center justify-center shadow-lg ring-4 ring-white ${isBuyNowMode ? 'w-9 h-9 rounded-xl' : 'w-10 h-10 rounded-2xl'} ${currentStep >= s.id ? 'bg-[var(--rust)] text-white scale-110' : 'bg-[var(--border)] text-[var(--muted)]'}`}>
                      <s.icon className="w-5 h-5" />
                   </div>
                   <div className="flex flex-col">
                      <span className="text-[9px] font-bold text-[var(--muted)] uppercase tracking-widest leading-none mb-1">Step 0{s.id}</span>
                      <span className={`text-[10px] font-bold uppercase tracking-widest ${currentStep >= s.id ? 'text-[var(--charcoal)]' : 'text-[var(--muted)]'}`}>{s.label}</span>
                   </div>
                </div>
                {idx < steps.length - 1 && <ChevronRight className={`text-[var(--border)] ${isBuyNowMode ? 'w-3.5 h-3.5' : 'w-4 h-4'}`} />}
              </React.Fragment>
            ))}
          </div>
        </div>

        <AnimatePresence mode="wait">
          {(currentStep === 1 || currentStep === 2) && (
            <motion.div 
              key="details"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              className={`grid grid-cols-1 lg:grid-cols-12 items-start ${isBuyNowMode ? 'gap-8' : 'gap-12'}`}
            >
               {/* Left: Input Columns */}
               <div className={isBuyNowMode ? 'lg:col-span-7 space-y-8' : 'lg:col-span-8 space-y-12'}>
                  <AnimatePresence mode="wait">
                    {currentStep === 1 && (
                      <motion.div 
                        key="shipping"
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: 20 }}
                        className={`artisan-card border-2 border-transparent hover:border-[var(--rust)]/10 transition-all bg-white/80 backdrop-blur-xl ${isBuyNowMode ? 'p-8 space-y-8 shadow-lg' : 'p-12 space-y-10 shadow-xl'}`}
                      >
                         <div className="flex items-center justify-between mb-2">
                           <h3 className={`font-serif font-bold text-[var(--charcoal)] tracking-tight flex items-center gap-4 ${isBuyNowMode ? 'text-[1.9rem]' : 'text-3xl'}`}>
                              <div className={`bg-[var(--rust)] text-white flex items-center justify-center shadow-lg ${isBuyNowMode ? 'w-10 h-10 rounded-xl' : 'w-12 h-12 rounded-2xl rotate-3'}`}><MapPin className={`${isBuyNowMode ? 'w-5 h-5' : 'w-6 h-6'}`} /></div>
                              Shipment Registry
                           </h3>
                           <div className="flex items-center gap-3">
                              {!isNewAddress && (
                                <button 
                                  type="button"
                                  onClick={() => setIsNewAddress(true)}
                                  className="text-[10px] font-extrabold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] px-4 py-2 transition-all font-sans"
                                >
                                   Use Different Address
                                </button>
                              )}
                              <button 
                                type="button"
                                onClick={() => setShowAddressBook(true)}
                                className="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-widest text-[var(--rust)] hover:bg-[var(--rust)]/5 px-4 py-2 rounded-xl border border-[var(--rust)]/20 transition-all font-sans"
                              >
                                 <BookOpen className="w-3.5 h-3.5" /> Address Book
                              </button>
                           </div>
                         </div>

                         {!isNewAddress && address.name ? (
                           <div className="bg-[var(--cream)]/30 border border-[var(--rust)]/10 rounded-3xl p-8 flex items-start gap-6 group transition-all hover:bg-white hover:shadow-lg">
                              <div className="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-sm shrink-0 border border-[var(--rust)]/5">
                                 <MapPin className="w-6 h-6 text-[var(--rust)]" />
                              </div>
                              <div className="space-y-2 flex-1">
                                 <div className="flex items-center justify-between">
                                    <span className="text-[10px] font-bold text-[var(--rust)] uppercase tracking-[0.2em]">Fulfillment Node Active</span>
                                    {address.latitude && <span className="text-[8px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded-lg uppercase tracking-widest flex items-center gap-1"><CheckCircle2 className="w-2 h-2" /> GIS Verified</span>}
                                 </div>
                                 <div className="text-xl font-serif font-bold text-[var(--charcoal)]">{address.name}</div>
                                 <div className="text-xs font-bold text-[var(--muted)] tracking-widest mb-2">{address.phone}</div>
                                 <p className="text-sm text-[var(--muted)] leading-relaxed italic">
                                    {address.houseNo} {address.street}, {address.barangay},<br />
                                    {address.city}, {address.province} {address.postalCode}
                                 </p>
                              </div>
                           </div>
                         ) : (
                           <div className={`grid grid-cols-1 md:grid-cols-2 ${isBuyNowMode ? 'gap-6 pt-2' : 'gap-8 pt-4'}`}>
                              <InputGroup compact={isBuyNowMode} label="Full Name" placeholder="Enter recipient name" value={address.name} onChange={(e) => setAddress({...address, name: e.target.value.slice(0, 50)})} icon={<UserIcon className="w-4 h-4" />} />
                              <InputGroup compact={isBuyNowMode} label="Phone Number" placeholder="09XXXXXXXXX" value={address.phone} onChange={(e) => setAddress({...address, phone: e.target.value.replace(/\D/g, "").slice(0, 11)})} icon={<Phone className="w-4 h-4" />} />
                              
                              <InputGroup compact={isBuyNowMode} label="House No. / Building" placeholder="Bldg/House Number" value={address.houseNo} onChange={(e) => setAddress({...address, houseNo: e.target.value.slice(0, 20)})} icon={<MapPin className="w-4 h-4" />} />
                              <InputGroup compact={isBuyNowMode} label="Street" placeholder="Street Name" value={address.street} onChange={(e) => setAddress({...address, street: e.target.value.slice(0, 100)})} icon={<MapPin className="w-4 h-4" />} />
                              
                              <InputGroup compact={isBuyNowMode} label="Barangay" placeholder="Barangay Name" value={address.barangay} onChange={(e) => setAddress({...address, barangay: e.target.value.slice(0, 50)})} icon={<MapPin className="w-4 h-4" />} />
                              <InputGroup compact={isBuyNowMode} label="City / Municipality" placeholder="Your City" value={address.city} onChange={(e) => setAddress({...address, city: e.target.value.slice(0, 50)})} icon={<MapPin className="w-4 h-4" />} />
                              
                              <InputGroup compact={isBuyNowMode} label="Province" placeholder="Your Province" value={address.province} onChange={(e) => setAddress({...address, province: e.target.value.slice(0, 50)})} icon={<MapPin className="w-4 h-4" />} />
                              <InputGroup compact={isBuyNowMode} label="Postal Code" placeholder="ZIP Code" value={address.postalCode} onChange={(e) => setAddress({...address, postalCode: e.target.value.replace(/\D/g, "").slice(0, 4)})} icon={<MapPin className="w-4 h-4" />} />
                              
                               <div className="md:col-span-2">
                                 <button 
                                   type="button"
                                   onClick={() => setShowMap(v => !v)}
                                   className={`w-full py-4 border-2 border-dashed rounded-2xl flex items-center justify-center gap-3 text-[10px] font-bold uppercase tracking-widest transition-all ${
                                     showMap
                                       ? 'bg-[var(--rust)] border-[var(--rust)] text-white'
                                       : address.latitude
                                       ? 'bg-green-50 border-green-500 text-green-700'
                                       : 'bg-[var(--input-bg)] border-[var(--border)] text-[var(--muted)] hover:border-[var(--rust)] hover:text-[var(--rust)]'
                                   }`}
                                 >
                                   {showMap ? (
                                     <><MapPin className="w-4 h-4" /> Hide Interactive Map</>
                                   ) : address.latitude ? (
                                     <><CheckCircle2 className="w-4 h-4" /> Location Pinned ({address.latitude.toFixed(4)}, {address.longitude.toFixed(4)})</>
                                   ) : (
                                     <><MapPin className="w-4 h-4" /> Drop Precise Map Pin (Optional)</>
                                   )}
                                 </button>
  
                                 <AnimatePresence>
                                   {showMap && (
                                     <motion.div
                                       initial={{ opacity: 0, height: 0 }}
                                       animate={{ opacity: 1, height: "auto" }}
                                       exit={{ opacity: 0, height: 0 }}
                                       transition={{ duration: 0.3 }}
                                       className="overflow-hidden mt-4"
                                     >
                                       <div className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] mb-3 ml-1">Interactive Heritage Map</div>
                                       <LocationPickerMap
                                         initialLat={address.latitude || 14.2952}
                                         initialLng={address.longitude || 121.4647}
                                         onLocationFound={({ lat, lng, address: geo }) => {
                                           setAddress(prev => ({
                                             ...prev,
                                             latitude: lat,
                                             longitude: lng,
                                             street: geo.street || prev.street,
                                             barangay: geo.barangay || prev.barangay,
                                             city: geo.city || prev.city,
                                             province: geo.province || prev.province,
                                             postalCode: geo.postalCode || prev.postalCode,
                                           }));
                                         }}
                                       />
                                     </motion.div>
                                   )}
                                 </AnimatePresence>
                               </div>
                           </div>
                         )}
                      </motion.div>

                    )}

                    {currentStep === 2 && (
                       <motion.div 
                         key="payment"
                         initial={{ opacity: 0, x: -20 }}
                         animate={{ opacity: 1, x: 0 }}
                         exit={{ opacity: 0, x: 20 }}
                         className={`artisan-card shadow-xl bg-white/80 backdrop-blur-xl ${isBuyNowMode ? 'p-8 space-y-8' : 'p-12 space-y-10'}`}
                       >
                          <h3 className={`font-serif font-bold text-[var(--charcoal)] tracking-tight flex items-center gap-4 ${isBuyNowMode ? 'text-[1.9rem]' : 'text-3xl'}`}>
                             <div className={`bg-blue-600 text-white flex items-center justify-center shadow-lg ${isBuyNowMode ? 'w-10 h-10 rounded-xl' : 'w-12 h-12 rounded-2xl -rotate-3'}`}><CreditCard className={`${isBuyNowMode ? 'w-5 h-5' : 'w-6 h-6'}`} /></div>
                             Payment Method
                          </h3>

                          <div className={`grid grid-cols-1 md:grid-cols-2 ${isBuyNowMode ? 'gap-4 pt-1' : 'gap-6 pt-2'}`}>
                             <button
                               type="button"
                               onClick={() => setPaymentMethod("GCash")}
                               className={`border-2 text-left transition-all shadow-sm ${isBuyNowMode ? 'rounded-2xl p-6' : 'rounded-3xl p-8'} ${paymentMethod === "GCash" ? 'border-blue-500 bg-blue-50/70 shadow-xl ring-4 ring-blue-100' : 'border-[var(--border)] bg-white hover:border-blue-300'}`}
                             >
                               <div className="flex items-start justify-between gap-4">
                                  <div className="space-y-3">
                                     <div className="text-[10px] font-extrabold uppercase tracking-[0.3em] text-blue-700">GCash</div>
                                     <div className="font-serif text-2xl font-bold text-[var(--charcoal)]">Pay Online</div>
                                     <p className="text-xs font-medium text-[var(--muted)] leading-relaxed">Upload your transaction reference and payment screenshot before placing the order.</p>
                                  </div>
                                  <div className={`bg-blue-600 text-white flex items-center justify-center shadow-lg shrink-0 ${isBuyNowMode ? 'w-12 h-12 rounded-xl' : 'w-14 h-14 rounded-2xl'}`}>
                                     <CreditCard className={`${isBuyNowMode ? 'w-5 h-5' : 'w-6 h-6'}`} />
                                  </div>
                               </div>
                             </button>

                             <button
                               type="button"
                               onClick={() => setPaymentMethod("Cash on Delivery")}
                               className={`border-2 text-left transition-all shadow-sm ${isBuyNowMode ? 'rounded-2xl p-6' : 'rounded-3xl p-8'} ${paymentMethod === "Cash on Delivery" ? 'border-[var(--rust)] bg-[var(--cream)] shadow-xl ring-4 ring-[var(--rust)]/10' : 'border-[var(--border)] bg-white hover:border-[var(--rust)]/40'}`}
                             >
                               <div className="flex items-start justify-between gap-4">
                                  <div className="space-y-3">
                                     <div className="text-[10px] font-extrabold uppercase tracking-[0.3em] text-[var(--rust)]">Cash on Delivery</div>
                                     <div className="font-serif text-2xl font-bold text-[var(--charcoal)]">Pay on Arrival</div>
                                     <p className="text-xs font-medium text-[var(--muted)] leading-relaxed">Settle the payment when the item is delivered. No upload or reference needed.</p>
                                  </div>
                                  <div className={`bg-[var(--rust)] text-white flex items-center justify-center shadow-lg shrink-0 ${isBuyNowMode ? 'w-12 h-12 rounded-xl' : 'w-14 h-14 rounded-2xl'}`}>
                                     <Truck className={`${isBuyNowMode ? 'w-5 h-5' : 'w-6 h-6'}`} />
                                  </div>
                               </div>
                             </button>
                          </div>
                           
                          {isGcashPayment ? (
                            <>
                          <div className={`bg-blue-50/50 border border-blue-100 flex flex-col md:flex-row items-center ${isBuyNowMode ? 'rounded-2xl p-6 gap-6' : 'rounded-3xl p-10 gap-10'}`}>
                             <div className={`bg-white shadow-2xl flex items-center justify-center p-4 border border-blue-200 ring-8 ring-blue-50/50 ${isBuyNowMode ? 'w-24 h-24 rounded-2xl' : 'w-32 h-32 rounded-3xl'}`}>
                                <Scan className="w-full h-full text-blue-600" />
                             </div>
                             <div className="flex-1 space-y-4 text-center md:text-left">
                                <div>
                                   <div className="text-[10px] font-extrabold text-blue-600 uppercase tracking-[0.3em] mb-1">GCash Direct Pay Registry</div>
                                   <div className={`font-serif font-bold text-blue-900 tracking-tight ${isBuyNowMode ? 'text-[1.75rem]' : 'text-3xl'}`}>0912 345 6789</div>
                                   <div className="text-xs font-bold text-blue-800 opacity-60 uppercase tracking-widest mt-1">Jose Artisans Co-op Portal</div>
                                </div>
                                <div className={`bg-white/60 border border-blue-100 inline-block font-serif font-bold text-blue-900 underline decoration-double decoration-blue-300 ${isBuyNowMode ? 'p-3 rounded-lg text-lg' : 'p-4 rounded-xl text-xl'}`}>
                                   Amount: ₱{total.toLocaleString()}
                                </div>
                             </div>
                          </div>

                          <div className={`grid grid-cols-1 md:grid-cols-2 ${isBuyNowMode ? 'gap-6 pt-2' : 'gap-10 pt-6'}`}>
                             <InputGroup compact={isBuyNowMode} label="GCash Reference No." placeholder="Enter transaction digits..." value={gcashRef} onChange={(e) => setGcashRef(e.target.value.replace(/\D/g, "").slice(0, 16))} icon={<Lock className="w-4 h-4" />} />
                             <div className="space-y-4">
                                <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Fulfillment Proof Screenshot</label>
                                <button 
                                  type="button"
                                  onClick={() => document.getElementById('screenshot-upload').click()}
                                  className={`w-full border-2 border-dashed flex items-center justify-center gap-4 text-xs font-bold uppercase tracking-widest transition-all duration-500 overflow-hidden relative group/upload ${isBuyNowMode ? 'h-14 rounded-xl' : 'h-16 rounded-2xl'} ${screenshot ? 'bg-green-50 border-green-500 text-green-700' : 'bg-[var(--input-bg)] border-[var(--border)] text-[var(--muted)] hover:border-[var(--rust)] hover:text-[var(--rust)] hover:bg-white shadow-inner'}`}
                                >
                                   {screenshot ? (
                                      <>
                                         <CheckCircle2 className="w-5 h-5" /> Evidence Logged
                                         <div className="absolute top-0 right-0 p-2 text-[8px] opacity-40 italic">{screenshot.name}</div>
                                      </>
                                   ) : (
                                      <>
                                         <Upload className="w-5 h-5 group-hover/upload:-translate-y-1 transition-transform" /> Attach Payment Scan
                                      </>
                                   )}
                                </button>
                                <input id="screenshot-upload" type="file" className="hidden" onChange={(e) => setScreenshot(e.target.files[0])} />
                             </div>
                          </div>
                            </>
                          ) : (
                            <div className={`border border-[var(--rust)]/10 bg-[var(--cream)]/60 flex flex-col md:flex-row items-start gap-6 ${isBuyNowMode ? 'rounded-2xl p-6' : 'rounded-3xl p-10'}`}>
                               <div className={`bg-[var(--rust)] text-white flex items-center justify-center shadow-lg shrink-0 ${isBuyNowMode ? 'w-14 h-14 rounded-xl' : 'w-16 h-16 rounded-2xl'}`}>
                                  <Truck className={`${isBuyNowMode ? 'w-7 h-7' : 'w-8 h-8'}`} />
                               </div>
                               <div className="space-y-3">
                                  <div className="text-[10px] font-extrabold uppercase tracking-[0.3em] text-[var(--rust)]">Cash on Delivery Selected</div>
                                  <div className={`font-serif font-bold text-[var(--charcoal)] tracking-tight ${isBuyNowMode ? 'text-[1.75rem]' : 'text-3xl'}`}>Prepare PHP {total.toLocaleString()} upon delivery</div>
                                  <p className="text-sm text-[var(--muted)] leading-relaxed italic">Your order will be placed immediately and paid when it arrives. Keep your mobile number available so the courier can confirm delivery.</p>
                               </div>
                            </div>
                          )}
                        </motion.div>
                    )}
                  </AnimatePresence>
               </div>               {/* Right: Real-time Order Monitor */}
               <div className={isBuyNowMode ? 'lg:col-span-5 lg:sticky lg:top-20 space-y-6' : 'lg:col-span-4 lg:sticky lg:top-24 space-y-8'}>
                  <div className={`artisan-card p-0 overflow-hidden border-2 border-[var(--rust)]/10 bg-white relative ${isBuyNowMode ? 'shadow-2xl' : 'shadow-3xl'}`}>
                     
                     <div className={`relative z-10 ${isBuyNowMode ? 'p-8 space-y-6' : 'p-10 space-y-8'}`}>
                        <div className={`flex items-center justify-between border-b border-[var(--border)] ${isBuyNowMode ? 'pb-4' : 'pb-6'}`}>
                           <h3 className="text-[10px] font-bold uppercase tracking-[0.3em] text-[var(--muted)]">Commissions Overview</h3>
                           <div className="px-3 py-1 bg-[var(--rust)] text-white rounded-lg text-[9px] font-bold uppercase tracking-widest leading-none flex items-center">{cartItems.length} {cartItems.length > 1 ? 'Pieces' : 'Piece'}</div>
                        </div>

                        <div className={`overflow-y-auto pr-2 custom-scrollbar ${isBuyNowMode ? 'space-y-4 max-h-[240px]' : 'space-y-6 max-h-[300px]'}`}>
                           {cartItems.map((item, idx) => (
                              <div key={idx} className={`flex group ${isBuyNowMode ? 'gap-4' : 'gap-5'}`}>
                                 <div className={`bg-[var(--cream)]/30 relative overflow-hidden shrink-0 border border-[var(--border)] shadow-sm ${isBuyNowMode ? 'w-14 h-[4.5rem] rounded-lg' : 'w-16 h-20 rounded-xl'}`}>
                                    <img src={item.image?.[0] || item.image || item.product?.image?.[0]} alt={item.name} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
                                 </div>
                                 <div className="flex-1 space-y-1">
                                    <div className={`font-bold tracking-tight line-clamp-2 text-[var(--charcoal)] leading-tight ${isBuyNowMode ? 'text-[0.95rem]' : 'text-sm'}`}>{item.name}</div>
                                    <div className="text-[9px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2">
                                      {item.variation && <><span className="text-[var(--rust)]">{item.variation}</span> <div className="w-1 h-1 rounded-full bg-[var(--muted)]/30" /></>}
                                      {item.size} • Qty {item.quantity}
                                    </div>
                                    <div className="text-xs font-serif font-bold text-[var(--rust)] tracking-tight">₱{(parsePrice(item.price)).toLocaleString()}</div>
                                 </div>
                              </div>
                           ))}
                        </div>

                        <div className={`border-t border-[var(--border)] space-y-4 ${isBuyNowMode ? 'pt-6' : 'pt-8'}`}>
                           <div className="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">
                              <span>Heritage Value</span>
                              <span className="text-[var(--charcoal)] font-extrabold">₱{subtotal.toLocaleString()}</span>
                           </div>
                           <div className="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">
                              <span>Courier Logistics</span>
                              <span className="text-green-600 font-extrabold">{shipping > 0 ? `₱${shipping.toLocaleString()}` : "FREE"}</span>
                           </div>
                           <div className="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">
                              <span>Estimated Arrival</span>
                              <span className="text-[var(--charcoal)] font-extrabold">{maxDays || 3}-{Number(maxDays || 3) + 2} Days</span>
                           </div>
                           <div className="pt-2 flex justify-between items-end border-t border-[var(--border)] border-dashed mt-2 pt-6">
                              <span className="text-[11px] font-bold uppercase tracking-[0.3em] text-[var(--charcoal)]">Final Due</span>
                              <span className={`font-serif font-bold text-[var(--rust)] tracking-tighter ${isBuyNowMode ? 'text-4xl' : 'text-5xl'}`}>₱{total.toLocaleString()}</span>
                           </div>
                        </div>
                     </div>

                     <button 
                       onClick={handleNext}
                       disabled={loading}
                       className={`w-full bg-[var(--rust)] text-[10px] font-extrabold uppercase tracking-[0.3em] flex items-center justify-center gap-3 active:scale-95 transition-all overflow-hidden relative group/btn disabled:opacity-50 z-20 ${isBuyNowMode ? 'py-5' : 'py-7'}`}
                     >
                        <div className="absolute inset-0 bg-white/10 translate-x-[-100%] group-hover/btn:translate-x-[100%] transition-transform duration-1000" />
                        {loading ? <Loader2 className="w-6 h-6 animate-spin mx-auto" /> : (
                           currentStep === 1 ? <>Proceed to Payment <ArrowRight className="w-5 h-5" /></> : <>Finalize Heritage Order <CheckCircle2 className="w-5 h-5" /></>
                        )}
                     </button>
                  </div>


                  <div className={`artisan-card bg-green-50/50 border-green-100 text-green-800 flex items-start gap-5 shadow-inner ${isBuyNowMode ? 'p-6' : 'p-8'}`}>
                     <ShieldCheck className="w-8 h-8 shrink-0 mt-1 opacity-60" />
                     <div className="space-y-2">
                        <div className="text-[10px] font-bold uppercase tracking-widest">Escrow Protection Protocol</div>
                        <p className="text-[10px] font-medium leading-relaxed opacity-80 italic">Funds are securely captured and released to artisans only upon digital confirmation of heritage artifact receipt. Your patronage is fully protected.</p>
                     </div>
                  </div>
               </div>
            </motion.div>
          )}

          {currentStep === 3 && (
             <motion.div 
               key="success"
               initial={{ opacity: 0, scale: 0.95 }}
               animate={{ opacity: 1, scale: 1 }}
               className={`flex flex-col items-center justify-center text-center bg-white/50 backdrop-blur-xl border border-white ${isBuyNowMode ? 'py-16 space-y-8 rounded-[2.75rem] shadow-2xl' : 'py-24 space-y-12 rounded-[4rem] shadow-3xl'}`}
             >
                <div className="relative">
                   <div className="absolute inset-0 bg-green-500 blur-3xl opacity-20 animate-pulse" />
                   <div className={`bg-green-50 text-green-500 flex items-center justify-center shadow-2xl relative z-10 hover:rotate-0 transition-transform duration-700 ring-8 ring-white ${isBuyNowMode ? 'w-24 h-24 rounded-[2rem]' : 'w-32 h-32 rounded-[2.5rem] rotate-6'}`}>
                      <CheckCircle2 className={`${isBuyNowMode ? 'w-12 h-12' : 'w-16 h-16'}`} />
                   </div>
                   <div className="absolute -top-4 -right-4 w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-xl border border-green-100 text-green-600 animate-bounce">
                      <ShoppingCart className="w-6 h-6" />
                   </div>
                </div>

                <div className="space-y-6 px-6">
                   <h2 className={`font-serif font-bold text-[var(--charcoal)] tracking-tighter uppercase ${isBuyNowMode ? 'text-4xl md:text-5xl' : 'text-6xl'}`}>
                     Commission <span className="text-[var(--rust)] italic lowercase">Confirmed</span>
                   </h2>
                   <p className={`text-[var(--muted)] max-w-xl mx-auto italic leading-relaxed font-medium px-4 ${isBuyNowMode ? 'text-base' : 'text-lg'}`}>
                     The artisan workshop has been notified of your acquisition. Your heritage order is now entering the logistics registry for premium LUMBÁN dispatch.
                   </p>
                </div>

                <div className={`flex flex-col sm:flex-row ${isBuyNowMode ? 'gap-4 pt-2' : 'gap-6 pt-6'}`}>
                   <Link href="/orders" className="btn-primary px-12 py-5 shadow-2xl flex items-center justify-center gap-3 group ring-8 ring-[var(--rust)]/10">
                      Registry Track Order <History className="w-5 h-5 group-hover:rotate-12 transition-transform" />
                   </Link>
                   <Link href="/home" className="px-12 py-5 font-bold uppercase text-[10px] tracking-widest bg-white border-2 border-[var(--border)] rounded-2xl hover:border-[var(--rust)] hover:text-[var(--rust)] transition-all flex items-center justify-center gap-3 shadow-lg">
                      Explore More Archives <ShoppingCart className="w-5 h-5" />
                   </Link>
                </div>

                <div className="pt-12 text-[9px] font-bold text-[var(--muted)] uppercase tracking-[0.4em] opacity-30">Transaction ID: LB-OR-2024-{selectedOrder?.id?.toString().padStart(6, '0') || 'PROCESSING'}</div>
             </motion.div>
          )}
        </AnimatePresence>

        {/* Validation Error Overlay */}
        <AnimatePresence>
           {showValidation && (
              <motion.div 
                initial={{ opacity: 0 }} 
                animate={{ opacity: 1 }} 
                exit={{ opacity: 0 }} 
                onClick={() => setShowValidation(false)}
                className="fixed inset-0 z-[200] bg-black/50 backdrop-blur-md flex items-center justify-center p-6"
              >
                 <motion.div 
                    initial={{ scale: 0.9, y: 20 }}
                    animate={{ scale: 1, y: 0 }}
                    exit={{ scale: 0.9, y: 20 }}
                    onClick={(e) => e.stopPropagation()}
                    className="artisan-card w-full max-w-lg p-12 space-y-8 shadow-[0_40px_100px_rgba(0,0,0,0.5)] border-red-100 text-center relative z-20"
                 >
                    <div className="w-24 h-24 bg-red-50 text-red-500 rounded-3xl flex items-center justify-center mx-auto shadow-inner ring-4 ring-white rotate-12">
                       <AlertCircle className="w-12 h-12" />
                    </div>
                    <div className="space-y-4">
                       <h3 className="font-serif text-4xl font-bold text-[var(--charcoal)] tracking-tight">Registry <span className="text-red-600">Errors</span></h3>
                       <p className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest leading-relaxed">The following required authentication fields remain unfulfilled:</p>
                    </div>

                    <div className="space-y-3 bg-red-50/50 p-8 rounded-3xl text-left border border-red-100/50 inline-block w-full">
                       {currentStep === 1 ? (
                          <>
                             {!address.name && <ValidationError label="Full Name" />}
                             {!address.phone && <ValidationError label="Phone Number" />}
                             {!address.houseNo && <ValidationError label="House No." />}
                             {!address.street && <ValidationError label="Street" />}
                             {!address.barangay && <ValidationError label="Barangay" />}
                             {!address.city && <ValidationError label="City / Municipality" />}
                             {!address.province && <ValidationError label="Province" />}
                             {!address.postalCode && <ValidationError label="Postal Code" />}
                          </>
                       ) : (
                          <>
                             {isGcashPayment && !gcashRef && <ValidationError label="Transaction Reference Hash" />}
                             {isGcashPayment && !screenshot && <ValidationError label="Digital Payment Screenshot Proof" />}
                          </>
                       )}
                    </div>

                    <button 
                       onClick={() => setShowValidation(false)}
                       className="w-full py-6 bg-red-600 text-white rounded-2xl text-[10px] font-extrabold uppercase tracking-widest shadow-2xl hover:bg-red-700 transition-all active:scale-95"
                    >
                       Fulfill Missing Registry Data
                    </button>
                 </motion.div>
              </motion.div>
           )}
        </AnimatePresence>
        {/* Address Book Modal Overlay */}
        <AnimatePresence>
           {showAddressBook && (
              <div className="fixed inset-0 z-[300] flex items-center justify-center p-4">
                 <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => { setShowAddressBook(false); setShowAddrForm(false); }} className="absolute inset-0 bg-black/60 backdrop-blur-md" />
                 <motion.div initial={{ scale: 0.92, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.92, opacity: 0 }} className="relative bg-[#FDFBF9] w-full max-w-4xl max-h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden pointer-events-auto flex flex-col">

                    {/* Modal Header */}
                    <div className="p-8 border-b border-[var(--border)] flex items-center justify-between shrink-0">
                      <div>
                        <h2 className="font-serif text-3xl font-bold text-[var(--charcoal)] tracking-tighter uppercase">
                          {showAddrForm ? (addrEditing ? 'Edit' : 'New') : 'Select'} <span className="text-[var(--rust)] italic lowercase font-normal">{showAddrForm ? 'address' : 'destination'}</span>
                        </h2>
                        <p className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mt-1">
                          {showAddrForm ? (addrEditing ? 'Update existing registry entry' : 'Add a new fulfillment node') : 'Choose or manage your saved addresses'}
                        </p>
                      </div>
                      <div className="flex items-center gap-3">
                        {!showAddrForm && (
                          <button onClick={() => openAddrForm()} className="flex items-center gap-2 bg-[var(--rust)] text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest shadow hover:scale-105 transition-all">
                            <Plus className="w-3.5 h-3.5" /> Add New
                          </button>
                        )}
                        {showAddrForm && (
                          <button onClick={() => setShowAddrForm(false)} className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] transition-colors px-3 py-2">
                            ← Back to List
                          </button>
                        )}
                        <button onClick={() => { setShowAddressBook(false); setShowAddrForm(false); }} className="p-2.5 hover:bg-[var(--cream)] rounded-xl transition-colors border border-[var(--border)]">
                          <X className="w-5 h-5 text-[var(--muted)]" />
                        </button>
                      </div>
                    </div>

                    <div className="flex-1 overflow-y-auto custom-scrollbar">
                      <AnimatePresence mode="wait">
                        {/* LIST VIEW */}
                        {!showAddrForm && (
                          <motion.div key="list" initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} className="p-8">
                            {savedAddresses.length === 0 ? (
                              <div className="py-16 text-center space-y-4">
                                <div className="w-14 h-14 bg-[var(--cream)] rounded-full flex items-center justify-center mx-auto"><MapPin className="w-7 h-7 text-[var(--muted)]" /></div>
                                <p className="text-[var(--muted)] font-medium text-sm">No saved addresses yet.</p>
                                <button onClick={() => openAddrForm()} className="bg-[var(--rust)] text-white px-8 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest">Add First Address</button>
                              </div>
                            ) : (
                              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                {savedAddresses.map(addr => (
                                  <div key={addr.id} className="relative artisan-card border-2 hover:border-[var(--rust)]/50 transition-all p-6 group">
                                    {addr.isDefault && <div className="absolute top-4 right-4 bg-[var(--rust)] text-white text-[8px] px-2 py-1 rounded font-bold uppercase tracking-widest">Default</div>}
                                    <button onClick={() => selectAddress(addr)} className="w-full text-left">
                                      <div className="font-bold text-base text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors mb-1">{addr.recipientName}</div>
                                      <div className="text-[10px] font-bold text-[var(--muted)] mb-3">{addr.phone}</div>
                                      <div className="text-xs text-[var(--muted)] leading-relaxed">
                                        {addr.houseNo} {addr.street},<br />
                                        Brgy. {addr.barangay}, {addr.city}, {addr.province}
                                      </div>
                                      {addr.latitude && <div className="mt-2 flex items-center gap-1 text-[9px] font-bold text-[var(--rust)] bg-[var(--rust)]/5 px-2 py-1 rounded w-fit"><MapPin className="w-3 h-3" /> Pinned</div>}
                                    </button>
                                    <div className="flex items-center gap-3 mt-4 pt-4 border-t border-[var(--border)]">
                                      {!addr.isDefault && (
                                        <button onClick={() => handleSetDefaultAddr(addr.id)} className="text-[9px] font-bold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] transition-colors">Set Default</button>
                                      )}
                                      <button onClick={() => openAddrForm(addr)} className="text-[9px] font-bold uppercase tracking-widest text-[var(--rust)] hover:underline ml-auto">Edit</button>
                                      <button onClick={() => handleDeleteAddr(addr.id)} className="text-[var(--muted)] hover:text-red-500 transition-colors"><Trash2 className="w-3.5 h-3.5" /></button>
                                    </div>
                                  </div>
                                ))}
                              </div>
                            )}
                          </motion.div>
                        )}

                        {/* ADD / EDIT FORM VIEW */}
                        {showAddrForm && (
                          <motion.div key="form" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 20 }} className="p-8">
                            <form onSubmit={handleSaveAddr} className="space-y-6">
                              <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <AbInputGroup label="Recipient Full Name" value={addrForm.recipientName} onChange={v => setAddrForm({...addrForm, recipientName: v})} placeholder="e.g. Juan Dela Cruz" icon={<UserIcon className="w-4 h-4" />} />
                                <AbInputGroup label="Phone Number" value={addrForm.phone} onChange={v => setAddrForm({...addrForm, phone: v})} placeholder="0912 345 6789" icon={<Phone className="w-4 h-4" />} />
                                <AbInputGroup label="House No. / Building" value={addrForm.houseNo} onChange={v => setAddrForm({...addrForm, houseNo: v})} placeholder="House 123" icon={<MapPin className="w-4 h-4" />} />
                                <AbInputGroup label="Street" value={addrForm.street} onChange={v => setAddrForm({...addrForm, street: v})} placeholder="M.H. Del Pilar St." icon={<MapPin className="w-4 h-4" />} />
                                <AbInputGroup label="Barangay" value={addrForm.barangay} onChange={v => setAddrForm({...addrForm, barangay: v})} placeholder="Poblacion" icon={<MapPin className="w-4 h-4" />} />
                                <AbInputGroup label="City / Municipality" value={addrForm.city} onChange={v => setAddrForm({...addrForm, city: v})} placeholder="Lumban" icon={<MapPin className="w-4 h-4" />} />
                                <AbInputGroup label="Province" value={addrForm.province} onChange={v => setAddrForm({...addrForm, province: v})} placeholder="Laguna" icon={<MapPin className="w-4 h-4" />} />
                                <AbInputGroup label="Postal Code" value={addrForm.postalCode} onChange={v => setAddrForm({...addrForm, postalCode: v})} placeholder="4014" icon={<MapPin className="w-4 h-4" />} />
                              </div>

                              <div className="flex flex-col sm:flex-row gap-4 items-center justify-between border-t border-[var(--border)] pt-6">
                                <button
                                  type="button"
                                  onClick={() => setAddrMapPicker(v => !v)}
                                  className={`flex items-center gap-3 px-5 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all ${
                                    addrForm.latitude ? 'bg-green-50 text-green-700 border-2 border-green-200' :
                                    addrMapPicker ? 'bg-[var(--rust)] text-white' :
                                    'bg-[var(--input-bg)] text-[var(--muted)] hover:bg-[var(--rust)] hover:text-white'
                                  }`}
                                >
                                  <MapPin className="w-4 h-4" />
                                  {addrForm.latitude ? `Pinned (${addrForm.latitude.toFixed(4)})` : addrMapPicker ? 'Hide Interactive Map' : 'Drop Precise Map Pin'}
                                </button>

                                <label className="flex items-center gap-3 cursor-pointer group">
                                  <div
                                    onClick={() => setAddrForm({...addrForm, isDefault: !addrForm.isDefault})}
                                    className={`w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all ${
                                      addrForm.isDefault ? 'bg-[var(--rust)] border-[var(--rust)]' : 'border-[var(--border)] group-hover:border-[var(--rust)]'
                                    }`}
                                  >
                                    {addrForm.isDefault && <CheckCircle2 className="w-4 h-4 text-white" />}
                                  </div>
                                  <span className="text-[10px] font-bold uppercase tracking-widest text-[var(--charcoal)] opacity-80">Set as default destination</span>
                                </label>
                              </div>

                              <AnimatePresence>
                                {addrMapPicker && (
                                  <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: 'auto' }} exit={{ opacity: 0, height: 0 }} className="overflow-hidden">
                                    <div className="pt-4 border-t border-[var(--border)]">
                                      <label className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[var(--charcoal)] opacity-70 block mb-3">Interactive Heritage Map</label>
                                      <LocationPickerMap
                                        onLocationFound={({ lat, lng, address: geo }) => {
                                          setAddrForm(prev => ({
                                            ...prev,
                                            latitude: lat,
                                            longitude: lng,
                                            street: geo.street || prev.street,
                                            barangay: geo.barangay || prev.barangay,
                                            city: geo.city || prev.city,
                                            province: geo.province || prev.province,
                                            postalCode: geo.postalCode || prev.postalCode,
                                          }));
                                        }}
                                        initialLat={addrForm.latitude || 14.2952}
                                        initialLng={addrForm.longitude || 121.4647}
                                      />
                                    </div>
                                  </motion.div>
                                )}
                              </AnimatePresence>

                              <button
                                type="submit"
                                disabled={addrLoading}
                                className="w-full bg-[var(--rust)] text-white py-5 rounded-2xl font-bold uppercase text-xs tracking-[0.3em] shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3"
                              >
                                {addrLoading ? <Loader2 className="w-5 h-5 animate-spin" /> : (addrEditing ? 'Update Address' : 'Save New Address')}
                              </button>
                            </form>
                          </motion.div>
                        )}
                      </AnimatePresence>
                    </div>
                 </motion.div>
              </div>
           )}
        </AnimatePresence>
      </div>
    </CustomerLayout>
  );
}

function InputGroup({ label, placeholder, value, onChange, icon, disabled, compact = false }) {
  return (
    <div className={compact ? "space-y-2" : "space-y-3"}>
      <label className={`font-bold uppercase tracking-widest text-[var(--muted)] ml-1 opacity-70 ${compact ? 'text-[9px]' : 'text-[10px]'}`}>{label}</label>
      <div className="relative group">
        <div className={`absolute top-1/2 -translate-y-1/2 text-[var(--muted)] group-focus-within:text-[var(--rust)] transition-colors ${compact ? 'left-4' : 'left-5'}`}>{icon}</div>
        <input 
          type="text" 
          disabled={disabled}
          className={`w-full bg-[var(--input-bg)] border-2 border-transparent outline-none focus:border-[var(--rust)] focus:bg-white transition-all font-bold shadow-inner ${compact ? 'pl-12 pr-4 py-4 rounded-xl text-sm' : 'pl-14 pr-6 py-5 rounded-2xl text-xs'} ${disabled ? 'opacity-40 cursor-not-allowed' : ''}`}
          placeholder={placeholder}
          value={value}
          onChange={onChange}
        />
      </div>
    </div>
  );
}

function ValidationError({ label }) {
   return (
      <div className="flex items-center gap-4 py-2 text-red-600">
         <div className="w-2 h-2 bg-red-400 rounded-full" />
         <span className="text-[11px] font-extrabold uppercase tracking-[0.2em]">{label}</span>
      </div>
   );
}

function UserIcon(props) {
   return <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
}

function AbInputGroup({ label, value, onChange, icon, placeholder }) {
  return (
    <div className="space-y-2">
      <label className="text-[9px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1 opacity-70">{label}</label>
      <div className="relative group">
        <div className="absolute top-1/2 -translate-y-1/2 left-4 text-[var(--muted)] group-focus-within:text-[var(--rust)] transition-colors">{icon}</div>
        <input
          type="text"
          value={value}
          onChange={e => onChange(e.target.value)}
          placeholder={placeholder}
          className="w-full bg-[var(--input-bg)] border-2 border-transparent outline-none focus:border-[var(--rust)] focus:bg-white transition-all font-bold shadow-inner pl-12 pr-4 py-4 rounded-xl text-sm"
        />
      </div>
    </div>
  );
}
