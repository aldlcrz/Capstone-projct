/* eslint-disable @next/next/no-img-element */
"use client";
import React, { useState, useEffect } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import { 
  ShoppingBag, 
  Minus, 
  Plus, 
  RefreshCw, 
  ShieldCheck,
  ChevronRight,
  X
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { getProductImageSrc } from "@/lib/productImages";
import { api, getStoredUserForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import AuthGateModal from "@/components/AuthGateModal";
import {
  CUSTOMER_STORAGE_SYNC_EVENT,
  getCustomerScopedJson,
  setCustomerScopedJson,
} from "@/lib/customerStorage";

export default function CartPage() {
  const [cartItems, setCartItems] = useState([]);
  const [selectedItems, setSelectedItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const { socket, publicSettings } = useSocket();
  const maintenanceMode = publicSettings?.maintenanceMode || false;

  useEffect(() => {
    const loadCart = () => {
      const storedCart = getCustomerScopedJson("cart", []);
      const nextCart = Array.isArray(storedCart) ? storedCart : [];
      setCartItems(nextCart);
      setSelectedItems(nextCart.map(item => `${item.id}-${item.size}-${item.variation || ''}`));
      setLoading(false);
    };

    loadCart();
    
    window.addEventListener('storage', loadCart);
    window.addEventListener(CUSTOMER_STORAGE_SYNC_EVENT, loadCart);
    
    return () => {
      window.removeEventListener('storage', loadCart);
      window.removeEventListener(CUSTOMER_STORAGE_SYNC_EVENT, loadCart);
    };
  }, []);

  const toggleItemSelection = (id, size, variation = "") => {
    const key = `${id}-${size}-${variation || ""}`;
    setSelectedItems(prev => 
      prev.includes(key) ? prev.filter(k => k !== key) : [...prev, key]
    );
  };

  const toggleSelectAll = () => {
    if (selectedItems.length === cartItems.length) {
      setSelectedItems([]);
    } else {
      setSelectedItems(cartItems.map(item => `${item.id}-${item.size}-${item.variation || ''}`));
    }
  };

  const updateQuantity = (id, size, variation, delta) => {
    const updated = cartItems.map(item => {
      if (item.id === id && item.size === size && (item.variation || "") === (variation || "")) {
        const maxQty = item.stock > 0 ? item.stock : Infinity;
        const newQty = Math.max(1, Math.min(maxQty, item.quantity + delta));
        return { ...item, quantity: newQty };
      }
      return item;
    });
    setCartItems(updated);
    setCustomerScopedJson("cart", updated);
  };

  const removeItem = (id, size, variation) => {
    const updated = cartItems.filter(item => !(item.id === id && item.size === size && (item.variation || "") === (variation || "")));
    setCartItems(updated);
    setCustomerScopedJson("cart", updated);
    setSelectedItems(prev => prev.filter(k => k !== `${id}-${size}-${variation || ""}`));
  };

  const selectedCartItems = cartItems.filter(item => selectedItems.includes(`${item.id}-${item.size}-${item.variation || ""}`));
  const subtotal = selectedCartItems.reduce((acc, item) => {
    const priceStr = String(item.price || "₱0").replace(/[^0-9.]/g, '');
    return acc + (parseFloat(priceStr) * item.quantity);
  }, 0);
  
  const shipping = selectedCartItems.length > 0 ? 0 : 0; // Matching mock where it shows '-' 
  const total = subtotal + (typeof shipping === 'number' ? shipping : 0);

  // Group by Artisan/Workshop
  const groupedItems = cartItems.reduce((acc, item) => {
    const artisan = item.artisan || "Heritage Workshop";
    if (!acc[artisan]) acc[artisan] = [];
    acc[artisan].push(item);
    return acc;
  }, {});

  const router = useRouter();
  const handleCheckout = () => {
    if (maintenanceMode) {
      alert("Orders are temporarily paused for maintenance. Please try again later.");
      return;
    }

    const customerUser = getStoredUserForRole("customer");
    if (!customerUser) {
      setIsAuthModalOpen(true);
      return;
    }

    if (selectedCartItems.length === 0) {
      alert("Please select at least one heritage piece to proceed.");
      return;
    }
    setCustomerScopedJson("checkout_items", selectedCartItems);
    router.push("/checkout?mode=cart");
  };

  const getPriceNumber = (price) => {
    return parseFloat(String(price || "0").replace(/[^0-9.]/g, '')) || 0;
  };


  const numShops = Object.keys(groupedItems).length;

  return (
    <CustomerLayout>
      <div className="max-w-[1440px] mx-auto px-4 lg:px-10 mb-20 lg:mb-32">
        
        {/* Header Section */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 pt-6">
           <div>
              <div className="flex items-center gap-3 mb-4">
                 <span className="w-8 h-[2px] bg-black"></span>
                 <span className="text-[10px] font-bold uppercase tracking-[0.25em] text-black">MARKETPLACE</span>
              </div>
              <h1 className="font-sans text-[32px] md:text-[40px] font-extrabold text-black leading-tight tracking-tight">
                 My <span className="text-gray-400 italic font-medium">Cart</span>
              </h1>
           </div>
           {/* Sync Status */}
           <div className="flex items-center gap-2 mb-2 text-green-500 text-xs font-bold uppercase tracking-widest shrink-0">
              <RefreshCw className="w-4 h-4" />
              Synced with {numShops} shop{numShops !== 1 ? 's' : ''}
           </div>
        </div>

        {cartItems.length === 0 ? (
          <div className="bg-white rounded-3xl border border-gray-100 p-24 text-center shadow-sm">
             <div className="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto text-gray-300 mb-8">
                <ShoppingBag className="w-10 h-10" />
             </div>
             <h2 className="text-2xl font-extrabold text-black mb-4">Your Cart is Empty</h2>
             <p className="text-sm text-gray-500 font-medium mb-8 max-w-md mx-auto">Discover authentic pieces waiting to be part of your collection.</p>
             <Link href="/" className="inline-flex items-center justify-center px-12 py-5 bg-black text-white rounded-2xl text-[11px] font-bold uppercase tracking-[0.2em] hover:bg-gray-800 transition-colors shadow-lg shadow-black/10 active:scale-95">
                Start Shopping
             </Link>
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {/* Left: Cart Items Table Container */}
            <div className="lg:col-span-8 flex flex-col gap-8">
               <div className="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
                  
                  {/* Table Headers */}
                  <div className="hidden md:grid grid-cols-12 px-8 py-5 border-b border-gray-100 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 bg-gray-50">
                     <div className="col-span-5 flex items-center gap-6">
                        <input 
                           type="checkbox" 
                           checked={selectedItems.length === cartItems.length}
                           onChange={toggleSelectAll}
                           className="w-4 h-4 rounded border-gray-300 text-black focus:ring-black cursor-pointer" 
                        />
                        PRODUCT
                     </div>
                     <div className="col-span-2 text-center">UNIT PRICE</div>
                     <div className="col-span-2 text-center">QUANTITY</div>
                     <div className="col-span-2 text-center">TOTAL</div>
                     <div className="col-span-1 text-right">ACTION</div>
                  </div>

                  {/* Group by Artisan */}
                  <div className="divide-y divide-gray-100">
                     {Object.keys(groupedItems).map((artisan, gIdx) => (
                        <div key={artisan} className="flex flex-col">
                           {/* Shop Row Header */}
                           <div className="px-8 py-5 flex items-center gap-4 bg-gray-50/50">
                              <div className="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs font-bold shadow-sm">{artisan[0]}</div>
                              <span className="text-sm font-bold text-black">{artisan}</span>
                              <div className="px-2.5 py-1 bg-green-50 border border-green-200 rounded text-[9px] font-bold uppercase tracking-widest text-green-700 flex items-center gap-1.5 ml-2">
                                 <div className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" /> Verified
                              </div>
                           </div>

                           {/* Product Rows */}
                           <div className="divide-y divide-gray-100 bg-white">
                              {groupedItems[artisan].map((item, idx) => {
                                 const itemKey = `${item.id}-${item.size}-${item.variation || ""}`;
                                 const isSelected = selectedItems.includes(itemKey);
                                 const priceVal = getPriceNumber(item.price);
                                 const itemTotal = priceVal * item.quantity;

                                 return (
                                    <div key={itemKey} className="px-4 md:px-8 py-6 flex flex-col md:grid md:grid-cols-12 md:items-center gap-6 relative">
                                       <div className="absolute top-4 right-4 md:hidden">
                                          <button onClick={() => removeItem(item.id, item.size, item.variation)} className="p-2 text-gray-400 hover:text-red-500 bg-gray-50 rounded-full">
                                             <X className="w-4 h-4" />
                                          </button>
                                       </div>
                                       {/* Check + Product Info */}
                                       <div className="col-span-1 md:col-span-5 flex items-center gap-4 sm:gap-6">
                                          <input 
                                             type="checkbox" 
                                             checked={isSelected}
                                             onChange={() => toggleItemSelection(item.id, item.size, item.variation)}
                                             className="w-4 h-4 rounded border-gray-300 text-black focus:ring-black cursor-pointer shrink-0" 
                                          />
                                          <div className="w-20 h-20 sm:w-24 sm:h-24 bg-gray-50 border border-gray-100 rounded-2xl overflow-hidden shrink-0 flex items-center justify-center">
                                             <img 
                                                src={getProductImageSrc(item.image)} 
                                                alt={item.name} 
                                                className="w-full h-full object-cover" 
                                                onError={(e) => { e.target.src = "/images/placeholder.png"; }}
                                             />
                                          </div>
                                           <div className="flex flex-col gap-2.5">
                                              <div className="text-base font-bold text-black line-clamp-1">{item.name}</div>
                                              <div className="flex items-center gap-2 flex-wrap">
                                                 <span className="px-2.5 py-1 bg-gray-100 rounded-md text-[10px] font-bold text-gray-500 uppercase tracking-widest">{item.category || item.categories?.[0] || "Uncategorized"}</span>
                                                 <span className="px-2.5 py-1 bg-gray-100 rounded-md text-[10px] font-bold text-gray-500 uppercase tracking-widest">Size: {item.size || "M"}</span>
                                                 {item.variation && (
                                                   <span className="px-2.5 py-1 bg-black/5 text-black rounded-md text-[10px] font-bold uppercase tracking-widest">
                                                     {item.variation}
                                                   </span>
                                                 )}
                                              </div>
                                           </div>
                                       </div>

                                       {/* Mobile Price Overlay */}
                                       <div className="flex md:hidden items-center justify-between w-full mt-2">
                                          <div className="font-bold text-base text-black">₱{priceVal.toLocaleString()}</div>
                                          <div className="inline-flex items-center gap-4 px-3 py-1.5 border border-gray-200 rounded-full bg-white shadow-sm">
                                             <button onClick={() => updateQuantity(item.id, item.size, item.variation, -1)} className="text-gray-400 hover:text-black transition-colors w-6 h-6 flex items-center justify-center bg-gray-50 rounded-full"><Minus className="w-3" /></button>
                                             <span className="min-w-6 text-center text-xs font-bold text-black">{item.quantity}</span>
                                             <button onClick={() => updateQuantity(item.id, item.size, item.variation, 1)} className="text-gray-400 hover:text-black transition-colors w-6 h-6 flex items-center justify-center bg-gray-50 rounded-full"><Plus className="w-3" /></button>
                                          </div>
                                       </div>

                                       {/* Unit Price (MD only) */}
                                       <div className="hidden md:block col-span-2 text-center font-bold text-[14px] text-gray-500">
                                          ₱{priceVal.toLocaleString()}
                                       </div>

                                       {/* Quantity Stepper (MD only) */}
                                       <div className="hidden md:flex col-span-2 justify-center">
                                          <div className="inline-flex items-center gap-3 px-2 py-1.5 border border-gray-200 rounded-full bg-white shadow-sm">
                                             <button onClick={() => updateQuantity(item.id, item.size, item.variation, -1)} className="text-gray-400 hover:text-black transition-colors w-7 h-7 flex items-center justify-center bg-gray-50 rounded-full"><Minus className="w-3 h-3" /></button>
                                             <span className="min-w-6 text-center text-xs font-bold text-black">{item.quantity}</span>
                                             <button onClick={() => updateQuantity(item.id, item.size, item.variation, 1)} className="text-gray-400 hover:text-black transition-colors w-7 h-7 flex items-center justify-center bg-gray-50 rounded-full"><Plus className="w-3 h-3" /></button>
                                          </div>
                                       </div>

                                       {/* Total Price */}
                                       <div className="hidden md:block col-span-2 text-center">
                                          <span className="font-sans text-[18px] font-extrabold text-black tracking-tight">₱{itemTotal.toLocaleString()}</span>
                                       </div>

                                       {/* Delete Action (MD only) */}
                                       <div className="hidden md:block col-span-1 text-right">
                                          <button onClick={() => removeItem(item.id, item.size, item.variation)} className="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors inline-flex items-center justify-center">
                                             <X className="w-4 h-4" />
                                          </button>
                                       </div>
                                    </div>
                                 );
                              })}
                           </div>
                        </div>
                     ))}
                  </div>

                  {/* Table Footer Actions - Desktop only */}
                  <div className="hidden md:flex px-8 py-6 border-t border-gray-100 bg-gray-50 items-center justify-between gap-6">
                     <div className="flex items-center gap-4">
                        <input 
                           type="checkbox" 
                           checked={selectedItems.length === cartItems.length}
                           onChange={toggleSelectAll}
                           className="w-4 h-4 rounded border-gray-300 text-black focus:ring-black cursor-pointer" 
                        />
                        <span className="text-xs font-bold text-gray-500 uppercase tracking-widest">Select All ({cartItems.length})</span>
                     </div>
                     <div className="flex items-center gap-8 w-full md:w-auto">
                        <div className="flex flex-col md:flex-row md:items-center gap-1 md:gap-4 shrink-0">
                           <span className="text-xs font-bold text-gray-500 uppercase tracking-widest">Total ({selectedItems.length} items):</span>
                           <span className="text-2xl font-extrabold text-black">₱{total.toLocaleString()}</span>
                        </div>
                        <button 
                           type="button"
                           onClick={handleCheckout}
                           disabled={selectedItems.length === 0 || maintenanceMode}
                           className="flex-1 md:px-10 py-4 bg-black text-white rounded-2xl text-[11px] font-bold uppercase tracking-[0.2em] shadow-lg shadow-black/10 hover:bg-gray-800 transition-all disabled:bg-gray-200 disabled:text-gray-400 disabled:opacity-50 active:scale-95"
                        >
                           {maintenanceMode ? "ORDERS PAUSED" : `CHECK OUT (${selectedItems.length})`}
                        </button>
                     </div>
                  </div>
               </div>
            </div>

            {/* Right: Summary Sidebar */}
            <div className="lg:col-span-4 sticky top-[100px]">
               <div className="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm space-y-8">
                  <h3 className="font-sans text-[24px] font-extrabold text-black tracking-tight">Order Summary</h3>
                  
                  {/* Promo Code Input */}
                  <div className="flex items-center gap-2">
                     <input 
                        type="text" 
                        placeholder="Promo Code" 
                        className="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-black placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all"
                     />
                     <button className="px-6 py-3 bg-black text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                        Apply
                     </button>
                  </div>

                  <div className="space-y-6 pt-2">
                     <div className="flex justify-between items-center text-xs font-bold text-gray-400 uppercase tracking-widest">
                        <span>Subtotal</span>
                        <span className="text-black text-sm">₱{subtotal.toLocaleString()}</span>
                     </div>
                     <div className="flex justify-between items-center text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 pb-8">
                        <span>Shipping</span>
                        <span className="text-black text-sm">—</span>
                     </div>
                     
                     <div className="flex justify-between items-end pt-4">
                        <span className="text-xs font-bold tracking-widest text-black uppercase">Total</span>
                        <span className="text-3xl font-extrabold text-black leading-none tracking-tight">₱{total.toLocaleString()}</span>
                     </div>
                  </div>

                  <div className="space-y-6 pt-6">
                     <button 
                       type="button"
                       onClick={handleCheckout}
                       disabled={selectedItems.length === 0 || maintenanceMode}
                       className="w-full py-5 bg-black text-white rounded-2xl text-[11px] font-bold uppercase tracking-[0.2em] shadow-lg shadow-black/10 hover:bg-gray-800 transition-all flex items-center justify-center gap-2 disabled:bg-gray-200 disabled:text-gray-400 disabled:opacity-50 active:scale-[0.98]"
                     >
                        {maintenanceMode ? "PAUSED" : `CHECK OUT (${selectedItems.length})`}
                     </button>
                     <div className="flex items-center justify-center gap-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50 py-3 rounded-xl border border-gray-100">
                        <ShieldCheck className="w-4 h-4 text-green-500" />
                        Secure checkout
                     </div>
                  </div>
               </div>
            </div>

          </div>
        )}

        {/* Recommendations Section */}
        <div className="mt-32">
          <div className="flex items-center justify-between mb-10">
            <h2 className="text-3xl font-extrabold text-black tracking-tight">You Might Also Like</h2>
            <Link href="/" className="text-sm font-bold text-gray-500 uppercase tracking-widest hover:text-black transition-colors flex items-center gap-1">
              View All <ChevronRight className="w-4 h-4" />
            </Link>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            {/* Mock Recommendation Items */}
            {[1, 2, 3, 4].map((item) => (
              <div key={item} className="group cursor-pointer">
                <div className="relative aspect-3/4 rounded-3xl overflow-hidden bg-gray-50 border border-gray-100 mb-4">
                  <img src={`/images/placeholder.png`} alt="Recommendation" className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                  <div className="absolute top-4 left-4 bg-white px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest text-black shadow-sm">
                    Trending
                  </div>
                </div>
                <div className="space-y-1.5 px-2">
                  <div className="text-xs font-bold text-gray-400 uppercase tracking-widest">Heritage</div>
                  <h3 className="font-bold text-black text-base line-clamp-1 group-hover:text-gray-600 transition-colors">Classic Embroidered Piece</h3>
                  <div className="font-extrabold text-black text-lg">₱4,500</div>
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>
      <AuthGateModal
        isOpen={isAuthModalOpen}
        onClose={() => setIsAuthModalOpen(false)}
        message="Please sign in to proceed with your acquisition."
        redirectPath="/checkout?mode=cart"
      />
    </CustomerLayout>
  );
}
