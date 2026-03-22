"use client";
import React, { useState, useEffect, useCallback } from "react";
import { useParams, useRouter } from "next/navigation";
import CustomerLayout from "./CustomerLayout";
import Image from "next/image";
import Link from "next/link";
import { 
  ShoppingCart, 
  Star,
  Truck,
  Loader2,
  ChevronRight,
  ShieldCheck,
  MessageCircle
} from "lucide-react";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { normalizeProductImages, normalizeProductSizes } from "@/lib/productImages";

export default function ProductDetailClient() {
  const params = useParams();
  const id = params?.id;
  const router = useRouter();
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedSize, setSelectedSize] = useState("M");
  const [quantity, setQuantity] = useState(1);
  const [activeImage, setActiveImage] = useState(0);

  const fetchProduct = useCallback(async () => {
    try {
      const res = await api.get(`/products/${id}`);
      const normalizedSizes = normalizeProductSizes(res.data.sizes);
      setProduct({
        ...res.data,
        sizes: normalizedSizes,
      });
      if (normalizedSizes.length > 0 && !selectedSize) setSelectedSize(normalizedSizes[0]);
    } catch (err) {
      console.warn("Artisan entry not found in registry.", err);
      setProduct({
        id: id,
        name: "Premium Barong Polo Shirt Men Filipino Traditional Printing Loose Casual T-Shirt Fashionable Short Sleeves Comfortable Breathable",
        price: 255,
        description: "A signature hand-embroidered commission from the heart of Lumban. It features ultra-premium materials and traditional Filipiniana crafting perfectly suited for modern occasions.",
        sizes: ["S", "M", "L", "XL", "2XL"],
        category: "Formal",
        artisan: "Lumban Master Craft",
        rating: 5.0,
        image: [
          { url: "/images/product1.png", variation: "25Q3MPL005-DY63535" },
          { url: "/images/product2.png", variation: "25Q3MPL005-LF18094" },
          { url: "/images/product3.png", variation: "25Q3MPL005-LF20638" },
          { url: "/images/product4.png", variation: "25Q3MPL005-LF17449" },
        ]
      });
    } finally {
      setLoading(false);
    }
  }, [id]);

  const { socket } = useSocket();

  useEffect(() => {
    if (!id) return;
    fetchProduct();

    if (socket) {
      socket.on('inventory_updated', (data) => {
        if (String(data.product.id) === String(id)) {
          setProduct(prev => ({ ...prev, ...data.product }));
        }
      });
    }

    return () => {
      if (socket) {
        socket.off('inventory_updated');
      }
    };
  }, [id, socket, fetchProduct]);

  const images = normalizeProductImages(product?.image);
  const galleryImages = images.length > 0 ? images : ["/images/product1.png"].map(url => ({ url, variation: "Original" }));

  const handleAddToCart = () => {
    if (!product) return;
    const currentVariation = galleryImages[activeImage]?.variation || "Original";
    const cart = JSON.parse(localStorage.getItem("cart") || "[]");
    const existing = cart.find(item => item.id === product.id && item.size === selectedSize && item.variation === currentVariation);
    if (existing) {
      existing.quantity += quantity;
    } else {
      cart.push({ 
        id: product.id, 
        name: product.name, 
        price: `₱${(product.price || 0).toLocaleString()}`, 
        image: galleryImages[activeImage]?.url || galleryImages[0]?.url || galleryImages[0], 
        quantity, 
        size: selectedSize,
        variation: currentVariation,
        artisan: product.artisan 
      });
    }
    localStorage.setItem("cart", JSON.stringify(cart));
    window.dispatchEvent(new Event('storage'));
    alert(`${product.name} (${currentVariation}, Size ${selectedSize}) added to your cart!`);
  };

  const handleBuyNow = () => {
    if (!product) return;
    const currentVariation = galleryImages[activeImage]?.variation || "Original";
    localStorage.setItem("checkout_mode", "buy_now");
    localStorage.setItem("checkout_item", JSON.stringify({ 
      id: product.id, 
      productId: product.id, 
      name: product.name, 
      price: `₱${(product.price || 0).toLocaleString()}`, 
      image: galleryImages[activeImage]?.url || galleryImages[0]?.url || galleryImages[0], 
      quantity, 
      size: selectedSize,
      variation: currentVariation,
      artisan: product.artisan 
    }));
    router.push("/checkout?mode=buy_now");
  };

  if (loading) return (
     <CustomerLayout>
        <div className="h-[70vh] flex flex-col items-center justify-center space-y-6">
           <Loader2 className="w-10 h-10 animate-spin text-[var(--rust)] opacity-50" />
        </div>
     </CustomerLayout>
  );

  const sizeOptions = normalizeProductSizes(product?.sizes);
  const availableSizes = sizeOptions.length > 0 ? sizeOptions : ["S", "M", "L", "XL", "2XL"];

  return (
    <CustomerLayout>
      <div className="bg-[#f5f5f5] min-h-screen pb-20 pt-6 font-sans">
        <div className="max-w-6xl mx-auto px-4 lg:px-0">
          <div className="bg-white rounded-sm shadow-sm p-4 lg:p-6 mb-4">
            <div className="flex flex-col lg:flex-row gap-8">
              <div className="w-full lg:w-[450px] shrink-0">
                <div className="relative w-full aspect-square bg-[#f8f8f8] rounded-sm mb-4">
                    <Image 
                      src={galleryImages[activeImage]?.url || galleryImages[activeImage]} 
                      alt={product.name} 
                      fill 
                      className="object-contain" 
                      priority 
                    />
                </div>
                <div className="flex gap-2 overflow-x-auto scrollbar-none snap-x">
                  {galleryImages.map((img, i) => (
                    <div 
                      key={i} 
                      onClick={() => setActiveImage(i)} 
                      className={`relative shrink-0 w-[4.5rem] h-[4.5rem] cursor-pointer box-border transition-all snap-start ${activeImage === i ? 'border-2 border-[var(--rust)]' : 'border border-transparent hover:border-[var(--rust)]'}`}
                    >
                      <Image src={img.url || img} alt="thumb" fill className="object-cover" />
                    </div>
                  ))}
                </div>
              </div>
              <div className="flex-1 flex flex-col pt-2">
                <h1 className="text-[20px] font-medium text-[#222] leading-tight mb-3">{product.name}</h1>
                <div className="flex items-center text-sm mb-5 gap-4">
                  <div className="flex items-center gap-1.5 border-r border-gray-200 pr-4">
                    <span className="text-[var(--rust)] text-[16px] border-b border-[var(--rust)] tracking-wider">{product.rating ? Number(product.rating).toFixed(1) : "5.0"}</span>
                    <div className="flex text-[var(--rust)]">
                       <Star className="w-3.5 h-3.5 fill-current" />
                       <Star className="w-3.5 h-3.5 fill-current" />
                       <Star className="w-3.5 h-3.5 fill-current" />
                       <Star className="w-3.5 h-3.5 fill-current" />
                       <Star className="w-3.5 h-3.5 fill-current" />
                    </div>
                  </div>
                  <div className="flex items-center gap-1.5 border-r border-gray-200 pr-4 cursor-pointer hover:underline text-[#222]">
                    <span className="text-[16px] underline decoration-gray-400 decoration-dotted">4</span>
                    <span className="text-[#767676]">Ratings</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <span className="text-[#222] text-[16px]">20</span>
                    <span className="text-[#767676]">Sold</span>
                  </div>
                </div>
                <div className="bg-[#fafafa] px-5 py-4 flex items-center mb-6">
                  <div className="text-[32px] font-medium text-[var(--rust)] mr-4">₱{(product.price || 0).toLocaleString()}</div>
                </div>
                <div className="flex flex-col gap-6 text-sm">
                  <div className="flex items-start">
                    <label className="w-24 shrink-0 text-[#757575] mt-0.5">Shipping</label>
                      <div className="flex-1">
                        <div className="flex items-center gap-2 text-[#222] mb-1">
                           <Truck className="w-4 h-4 text-[#00bfa5]" />
                           <span className="text-[#222]">
                             {product.shippingDays ? `Estimated arrival in ${product.shippingDays} days` : "Guaranteed to get by 23 - 24 Mar"}
                           </span>
                           <ChevronRight className="w-3.5 h-3.5 text-gray-400" />
                        </div>
                        <div className="text-[#757575] text-[12px] pl-6">
                          {product.shippingFee > 0 ? `Shipping Fee: ₱${product.shippingFee.toLocaleString()}` : "Free Shipping available on this masterpiece."}
                        </div>
                      </div>
                  </div>
                  <div className="flex items-center">
                     <label className="w-24 shrink-0 text-[#757575] leading-tight flex items-center">Shopping<br/>Guarantee</label>
                     <div className="flex items-center gap-1.5 text-[#222] text-[13.5px] hover:underline cursor-pointer">
                        <ShieldCheck className="w-4 h-4 text-[var(--rust)]" />
                        <span>Free & Easy Returns • Merchandise Protection</span>
                        <ChevronRight className="w-3.5 h-3.5 text-gray-400" />
                     </div>
                  </div>
                  <div className="flex items-start">
                    <label className="w-24 shrink-0 text-[#757575] mt-2">Color</label>
                    <div className="flex flex-wrap gap-2 flex-1">
                      {galleryImages.map((img, i) => (
                        <button 
                          key={i} 
                          onClick={() => setActiveImage(i)} 
                          className={`relative flex items-center gap-2 p-1 pr-3 border rounded-sm transition-all outline-none ${activeImage === i ? 'border-[var(--rust)] text-[var(--rust)] shadow-[insert_0_0_0_1px_var(--rust)]' : 'border-gray-200 text-[#222] hover:border-[var(--rust)]'}`}
                        >
                          <div className="relative w-7 h-7 shrink-0 bg-white">
                             <Image src={img.url || img} alt="color variant" fill className="object-cover" />
                          </div>
                          <span className="text-[13px]">{img.variation || `Variant ${i+1}`}</span>
                        </button>
                      ))}
                    </div>
                  </div>
                  <div className="flex items-center">
                    <label className="w-24 shrink-0 text-[#757575]">Size</label>
                    <div className="flex flex-wrap gap-2 flex-1">
                      {availableSizes.map((size) => (
                        <button 
                          key={size} 
                          onClick={() => setSelectedSize(size)} 
                          className={`relative px-4 py-1.5 min-w-[3rem] text-center border rounded-sm transition-all outline-none text-[13px] ${selectedSize === size ? 'border-[var(--rust)] text-[var(--rust)]' : 'border-gray-200 text-[#222] hover:border-[var(--rust)]'}`}
                        >
                          {size}
                        </button>
                      ))}
                    </div>
                  </div>
                  <div className="flex items-center">
                    <label className="w-24 shrink-0 text-[#757575]">Quantity</label>
                    <div className="flex flex-1 items-center gap-4">
                       <div className="flex items-center border border-gray-300 rounded-sm">
                          <button onClick={() => setQuantity(Math.max(1, quantity - 1))} className="w-8 h-8 flex items-center justify-center text-[#757575] hover:bg-gray-50 bg-white border-r border-gray-300">-</button>
                          <div className="w-12 h-8 flex items-center justify-center text-[15px] text-[#222] bg-white">{quantity}</div>
                          <button onClick={() => setQuantity(quantity + 1)} className="w-8 h-8 flex items-center justify-center text-[#757575] hover:bg-gray-50 bg-white border-l border-gray-300">+</button>
                       </div>
                       <span className="text-[13px] text-[#757575]">{product.stock || 52} pieces available</span>
                    </div>
                  </div>
                </div>
                <div className="flex items-center gap-3 pt-8 pb-4">
                  <button onClick={handleAddToCart} className="flex items-center justify-center gap-2 px-6 py-3 border border-[var(--rust)] bg-[#ffedea] text-[var(--rust)] rounded-sm font-medium hover:bg-[#ffece8] transition-colors focus:outline-none min-w-[200px]">
                     <ShoppingCart className="w-5 h-5 opacity-90" /> Add To Cart
                  </button>
                  <button onClick={handleBuyNow} className="flex items-center justify-center px-6 py-3 bg-[var(--rust)] text-white rounded-sm font-medium hover:bg-[#b03b25] transition-colors focus:outline-none min-w-[150px]">
                     Buy Now
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div className="bg-white rounded-sm shadow-sm p-6 mb-4 flex items-center gap-6">
             <div className="relative w-[76px] h-[76px] rounded-full border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center bg-gray-50 text-[var(--rust)] font-bold text-2xl font-serif">
                {(product.artisan || "L")[0].toUpperCase()}
             </div>
             <div className="flex-1 flex flex-col justify-center border-r border-gray-200 pr-6 pl-2">
                <div className="text-[15px] font-medium text-[#222] mb-1">{product.artisan || "Lumban Master Craft"}</div>
                <div className="text-[#757575] text-[12px] mb-3">Active 5 minutes ago</div>
                <div className="flex gap-2">
                   <Link href={`/messages?sellerId=${product.sellerId || 1}&sellerName=${product.artisan || "Lumban Master Craft"}`} className="lex items-center justify-center gap-1.5 px-3 py-1.5 border border-[#e8e8e8] text-[#555] bg-[#fff] hover:bg-[#f8f8f8] rounded-sm text-xs font-medium transition-colors min-w-[100px]">
                      <MessageCircle className="w-3.5 h-3.5 text-[#ee4d2d]" /> Chat Now
                   </Link>
                   <Link href={`/shop/${product.sellerId || 1}`} className="flex items-center justify-center gap-1.5 px-3 py-1.5 border border-[#e8e8e8] text-[#555] bg-[#fff] hover:bg-[#f8f8f8] rounded-sm text-xs font-medium transition-colors min-w-[100px]">
                      <ShoppingCart className="w-3.5 h-3.5 text-[#666]" /> View Shop
                   </Link>
                </div>
             </div>
             <div className="flex-1 pl-6 grid grid-cols-2 gap-y-3 gap-x-8 text-[13px]">
                <div className="flex justify-between"><span className="text-[#757575]">Ratings</span> <span className="text-[var(--rust)]">523</span></div>
                <div className="flex justify-between"><span className="text-[#757575]">Response Rate</span> <span className="text-[var(--rust)]">98%</span></div>
                <div className="flex justify-between"><span className="text-[#757575]">Joined</span> <span className="text-[var(--rust)]">12 Months Ago</span></div>
                <div className="flex justify-between"><span className="text-[#757575]">Products</span> <span className="text-[var(--rust)]">16</span></div>
             </div>
          </div>
          <div className="bg-white rounded-sm shadow-sm p-6">
            <h2 className="text-[16px] font-medium text-[#222] bg-gray-50 py-3 px-4 mb-4">Product Specifications</h2>
            <div className="space-y-3 text-[14px] px-4 pb-4">
              <div className="flex"><span className="w-36 text-[#757575]">Category</span><span className="text-[#222]">{product.category}</span></div>
              <div className="flex"><span className="w-36 text-[#757575]">Stock</span><span className="text-[#222]">{product.stock || 52}</span></div>
              <div className="flex"><span className="w-36 text-[#757575]">Ships From</span><span className="text-[#222]">Lumban, Laguna</span></div>
            </div>
            <h2 className="text-[16px] font-medium text-[#222] bg-gray-50 py-3 px-4 mt-8 mb-4">Product Description</h2>
            <div className="text-[14px] text-[#222] px-4 whitespace-pre-wrap leading-relaxed py-2">{product.description}</div>
          </div>
        </div>
      </div>
    </CustomerLayout>
  );
}
