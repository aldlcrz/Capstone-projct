"use client";
import React, { useState, useEffect } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import { 
  Heart, 
  ShoppingBag, 
  Trash2, 
  ArrowRight,
  RefreshCw,
  Sparkles
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import Link from "next/link";
import { api, getApiErrorMessage } from "@/lib/api";

export default function WishlistPage() {
  const [wishlistItems, setWishlistItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchWishlist();
  }, []);

  const fetchWishlist = async () => {
    try {
      const token = localStorage.getItem("token");
      if (!token) {
        setLoading(false);
        return;
      }

      const response = await api.get('/wishlist');
      setWishlistItems(response.data);
      setLoading(false);
    } catch (err) {
      console.error("Fetch Wishlist Error:", err);
      setError("Failed to load your wishlist pieces.");
      setLoading(false);
    }
  };

  const removeFromWishlist = async (productId) => {
    try {
      const token = localStorage.getItem("token");
      await api.post('/wishlist/toggle', { productId });
      setWishlistItems(prev => prev.filter(item => item.productId !== productId));
    } catch (err) {
      console.error("Remove Wishlist Error:", err);
      setError(getApiErrorMessage(err, "Could not remove item from wishlist."));
    }
  };

  const addToCart = (product) => {
    const cart = JSON.parse(localStorage.getItem("cart") || "[]");
    const existing = cart.find(item => item.id === product.id);
    
    if (existing) {
      alert("This masterpiece is already in your cart.");
      return;
    }

    const cartItem = {
      id: product.id,
      name: product.name,
      price: product.price,
      image: product.image,
      artisan: product.seller?.name || "Lumban Artisan",
      quantity: 1,
      size: "M", // Default
      category: product.category
    };

    localStorage.setItem("cart", JSON.stringify([...cart, cartItem]));
    window.dispatchEvent(new Event('storage'));
    alert("Added to your heritage collection (cart).");
  };

  const getSafeImage = (imgSrc) => {
    if (!imgSrc) return null;
    if (Array.isArray(imgSrc)) return imgSrc[0];
    if (typeof imgSrc === 'string' && imgSrc.startsWith('[')) {
      try { return JSON.parse(imgSrc)[0]; } catch(e) { return imgSrc; }
    }
    return imgSrc;
  };

  if (loading) {
    return (
      <CustomerLayout>
        <div className="flex items-center justify-center min-h-[60vh]">
          <RefreshCw className="w-8 h-8 text-[var(--rust)] animate-spin" />
        </div>
      </CustomerLayout>
    );
  }

  return (
    <CustomerLayout>
      <div className="max-w-[1280px] mx-auto px-4 md:px-0 mb-20">
        
        {/* Header Section */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12 pt-10">
           <div>
              <div className="flex items-center gap-3 mb-4">
                 <Sparkles className="w-4 h-4 text-[var(--rust)]" />
                 <span className="text-[10px] font-extrabold uppercase tracking-[0.25em] text-[var(--rust)]">CURATED COLLECTION</span>
              </div>
              <h1 className="font-serif text-[56px] font-bold text-[#1A1A1A] leading-tight">
                 My <span className="text-[var(--rust)] italic font-medium">Wishlist</span>
              </h1>
           </div>
           <div className="text-xs font-bold text-[var(--muted)] uppercase tracking-widest border-b border-[var(--border)] pb-2 flex items-center gap-2">
              <Heart className="w-4 h-4 text-red-400 fill-red-400" />
              {wishlistItems.length} Saved Piece{wishlistItems.length !== 1 ? 's' : ''}
           </div>
        </div>

        {wishlistItems.length === 0 ? (
          <div className="bg-white rounded-[3rem] border border-[var(--border)] p-24 text-center shadow-sm">
             <div className="w-24 h-24 bg-[var(--cream)] rounded-full flex items-center justify-center mx-auto text-[var(--muted)] opacity-30 mb-8">
                <Heart className="w-12 h-12" />
             </div>
             <h2 className="text-3xl font-serif font-bold text-[#1A1A1A] mb-4">Your Wishlist is Empty</h2>
             <p className="text-sm text-[var(--muted)] italic mb-10 max-w-sm mx-auto">
                Explore our catalog of hand-embroidered Luzones and find the heritage pieces that speak to you.
             </p>
             <Link href="/shop" className="inline-flex items-center justify-center px-12 py-5 bg-[#1A1A1A] text-white rounded-[1.2rem] text-[10px] font-extrabold uppercase tracking-widest hover:bg-[var(--rust)] transition-all shadow-lg active:scale-95">
                Explore Marketplace
             </Link>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
             <AnimatePresence>
                {wishlistItems.map((item) => (
                   <motion.div 
                      key={item.id}
                      layout
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, scale: 0.95 }}
                      className="group bg-white rounded-[2.5rem] border border-[var(--border)] overflow-hidden hover:shadow-2xl hover:shadow-black/5 transition-all duration-500 flex flex-col"
                   >
                      {/* Image Container */}
                      <div className="relative aspect-[4/5] overflow-hidden bg-[#FDFBF9]">
                         <img 
                            src={getSafeImage(item.product?.image)} 
                            alt={item.product?.name}
                            className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" 
                         />
                         {/* Quick Actions */}
                         <div className="absolute top-6 right-6 flex flex-col gap-3 translate-x-12 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-500">
                            <button 
                               onClick={() => removeFromWishlist(item.productId)}
                               className="w-10 h-10 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-lg"
                            >
                               <Trash2 className="w-4 h-4" />
                            </button>
                         </div>
                         <div className="absolute bottom-0 left-0 right-0 p-8 bg-gradient-to-t from-black/60 to-transparent translate-y-full group-hover:translate-y-0 transition-transform duration-500">
                            <button 
                               onClick={() => addToCart(item.product)}
                               className="w-full py-4 bg-white text-[#1A1A1A] rounded-xl text-[10px] font-extrabold uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-[var(--rust)] hover:text-white transition-colors"
                            >
                               <ShoppingBag className="w-4 h-4" />
                               Add to Collection
                            </button>
                         </div>
                      </div>

                      {/* Info */}
                      <div className="p-8 flex flex-col flex-1">
                         <div className="flex justify-between items-start mb-4">
                            <div>
                               <div className="text-[10px] font-bold text-[var(--rust)] uppercase tracking-widest mb-1">
                                  {item.product?.category || "Formal Wear"}
                               </div>
                               <h3 className="text-xl font-serif font-bold text-[#1A1A1A] group-hover:text-[var(--rust)] transition-colors">
                                  {item.product?.name}
                               </h3>
                            </div>
                            <span className="font-serif text-xl font-bold italic text-[var(--rust)]">
                               ₱{Number(item.product?.price).toLocaleString()}
                            </span>
                         </div>
                         
                         <p className="text-xs text-[var(--muted)] italic line-clamp-2 mb-6 flex-1">
                            {item.product?.description || "A masterfully crafted piece showcasing the heritage of Lumban embroidery."}
                         </p>

                         <div className="flex items-center justify-between pt-6 border-t border-[var(--border)]">
                            <div className="flex items-center gap-2">
                               <div className="w-6 h-6 rounded-full bg-[var(--charcoal)] flex items-center justify-center text-white text-[8px] font-bold">
                                  {item.product?.seller?.name?.[0] || "A"}
                               </div>
                               <span className="text-[10px] font-bold text-[#1A1A1A] uppercase tracking-wider">
                                  {item.product?.seller?.name || "Artesano"}
                               </span>
                            </div>
                            <Link 
                               href={`/products/${item.productId}`}
                               className="text-[10px] font-extrabold text-[#1A1A1A] uppercase tracking-widest flex items-center gap-2 hover:text-[var(--rust)] transition-colors"
                            >
                               View Details <ArrowRight className="w-3 h-3" />
                            </Link>
                         </div>
                      </div>
                   </motion.div>
                ))}
             </AnimatePresence>
          </div>
        )}
      </div>
    </CustomerLayout>
  );
}
