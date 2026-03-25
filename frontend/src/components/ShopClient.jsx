"use client";
import React, { useState, useEffect } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import CustomerLayout from "./CustomerLayout";
import Image from "next/image";
import Link from "next/link";
import {
  Star,
  MapPin,
  MessageCircle,
  Plus,
  Loader2,
  ChevronRight,
  Clock,
  Package,
  CheckCircle2
} from "lucide-react";
import { api } from "@/lib/api";

export default function ShopClient() {
  const searchParams = useSearchParams();
  const id = searchParams.get("id");
  const router = useRouter();

  const [seller, setSeller] = useState(null);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!id) {
      setLoading(false);
      return;
    }
    const fetchShopData = async () => {
      try {
        const [sellerRes, productsRes] = await Promise.all([
          api.get(`/users/seller/${id}`),
          api.get(`/products/seller/${id}`)
        ]);
        setSeller(sellerRes.data);
        setProducts(productsRes.data);
      } catch (err) {
        console.warn("Artisan not found in municipal registry.", err);
        // Fallback for visual testing
        setSeller({
          id: id,
          shopName: "Lumban Master Craft",
          location: "Lumban, Laguna",
          rating: 4.9,
          joined: "12 Months Ago",
          responseRate: "98%",
          description: "A legacy of fine hand-embroidery passed down through generations. Our workshop specializes in traditional Pina and Jusi Barongs with modern silhouettes."
        });
        setProducts([
          { id: 1, name: "Premium Barong Polo", price: 2450, rating: 5.0, image: "/images/product1.png" },
          { id: 2, name: "Classic Jusi Barong", price: 3800, rating: 4.8, image: "/images/product2.png" },
          { id: 3, name: "Modern Filipiniana Gown", price: 5200, rating: 5.0, image: "/images/product3.png" },
          { id: 4, name: "Hand-crafted Scarf", price: 850, rating: 4.7, image: "/images/product4.png" },
        ]);
      } finally {
        setLoading(false);
      }
    };
    fetchShopData();
  }, [id]);

  if (loading) return (
    <CustomerLayout>
      <div className="h-[70vh] flex flex-col items-center justify-center space-y-6">
        <Loader2 className="w-10 h-10 animate-spin text-[var(--rust)] opacity-30" />
        <p className="font-serif italic text-[var(--muted)]">Opening artisan workshop...</p>
      </div>
    </CustomerLayout>
  );

  if (!id) return (
    <CustomerLayout>
      <div className="h-[70vh] flex items-center justify-center px-4 text-center text-[var(--muted)]">
        Select a shop first to open its collection.
      </div>
    </CustomerLayout>
  );

  return (
    <CustomerLayout>
      <div className="bg-[#f5f5f5] min-h-screen pb-20 font-sans">
        {/* Shop Header Section */}
        <div className="bg-white border-b border-gray-200">
          <div className="max-w-6xl mx-auto px-4 lg:px-0 py-8">
            <div className="flex flex-col md:flex-row gap-10 items-start md:items-center">
              {/* Avatar & Chat Actions */}
              <div className="relative w-full md:w-[320px] rounded-sm overflow-hidden bg-[var(--charcoal)] p-6 flex flex-col items-center text-white shadow-lg">
                <div className="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/natural-paper.png')]"></div>
                <div className="relative w-24 h-24 rounded-full border-4 border-white/20 overflow-hidden mb-4 bg-white/10 flex items-center justify-center font-serif text-4xl font-bold">
                  {seller.shopName ? seller.shopName[0] : "L"}
                </div>
                <h2 className="relative text-xl font-bold mb-1">{seller.shopName || "Lumban Artisan"}</h2>
                <div className="relative text-xs text-white/60 mb-6 flex items-center gap-1">
                  <Clock className="w-3 h-3" /> Active 5 mins ago
                </div>
                <div className="relative flex gap-2 w-full">
                  <Link href={`/messages?sellerId=${id}&sellerName=${seller.shopName}`} className="flex-1 flex items-center justify-center gap-2 py-2 px-3 border border-white/30 text-xs font-bold uppercase tracking-wider hover:bg-white/10 transition-colors">
                    <MessageCircle className="w-3.5 h-3.5" /> Chat
                  </Link>
                  <button className="flex-1 flex items-center justify-center gap-2 py-2 px-3 border border-white/30 text-xs font-bold uppercase tracking-wider hover:bg-white/10 transition-colors">
                    <Plus className="w-3.5 h-3.5" /> Follow
                  </button>
                </div>
              </div>

              {/* Shop Stats Grid */}
              <div className="flex-1 grid grid-cols-2 lg:grid-cols-3 gap-y-6 gap-x-12 text-sm">
                <div className="flex items-center gap-3">
                  <Package className="w-4 h-4 text-[var(--rust)]" />
                  <div className="flex justify-between w-full pr-4">
                    <span className="text-gray-500">Products:</span>
                    <span className="text-[var(--rust)] font-medium font-serif">{products.length}</span>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <MessageCircle className="w-4 h-4 text-[var(--rust)]" />
                  <div className="flex justify-between w-full pr-4">
                    <span className="text-gray-500">Response:</span>
                    <span className="text-[var(--rust)] font-medium font-serif">{seller.responseRate || "100%"}</span>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Clock className="w-4 h-4 text-[var(--rust)]" />
                  <div className="flex justify-between w-full pr-4">
                    <span className="text-gray-500">Joined:</span>
                    <span className="text-[var(--rust)] font-medium font-serif">{seller.joined || "12 Months Ago"}</span>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Star className="w-4 h-4 text-[var(--rust)]" />
                  <div className="flex justify-between w-full pr-4">
                    <span className="text-gray-500">Rating:</span>
                    <span className="text-[var(--rust)] font-medium font-serif">{seller.rating || "5.0"}</span>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <MapPin className="w-4 h-4 text-[var(--rust)]" />
                  <div className="flex justify-between w-full pr-4">
                    <span className="text-gray-500">Location:</span>
                    <span className="text-[var(--rust)] font-medium font-serif">{seller.location || "Lumban, Laguna"}</span>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <CheckCircle2 className="w-4 h-4 text-[var(--rust)]" />
                  <div className="flex justify-between w-full pr-4">
                    <span className="text-gray-500">Verified:</span>
                    <span className="text-[var(--rust)] font-medium font-serif">Registry Gold</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Shop Content */}
        <div className="max-w-6xl mx-auto px-4 lg:px-0 py-10">
          <div className="flex items-center justify-between mb-8">
            <h3 className="font-serif text-2xl font-bold text-[var(--charcoal)] tracking-tight">Artisan <span className="text-[var(--rust)] italic">Collection</span></h3>
            <div className="flex gap-4 text-sm font-medium">
              <button className="text-[var(--rust)] border-b-2 border-[var(--rust)] pb-1">All Products</button>
              <button className="text-gray-500 hover:text-[var(--rust)] transition-colors pb-1">On Sale</button>
              <button className="text-gray-500 hover:text-[var(--rust)] transition-colors pb-1">Highest Rated</button>
            </div>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            {products.map((product) => (
              <Link key={product.id} href={`/products?id=${product.id}`} className="group bg-white rounded-sm shadow-sm hover:shadow-md transition-all h-full flex flex-col border border-transparent hover:border-[var(--rust)]/10">
                <div className="relative aspect-[3/4] overflow-hidden bg-[#fafafa]">
                  <Image
                    src={Array.isArray(product.image) ? (typeof product.image[0] === 'string' ? product.image[0] : product.image[0]?.url) : product.image || "/images/placeholder.png"}
                    alt={product.name}
                    fill
                    className="object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  {product.stock <= 5 && (
                    <div className="absolute bottom-2 left-2 px-2 py-0.5 bg-red-500/90 text-[10px] text-white font-bold uppercase tracking-wider rounded-sm backdrop-blur-sm">
                      Limited Stock
                    </div>
                  )}
                </div>
                <div className="p-3 flex-1 flex flex-col justify-between">
                  <div>
                    <h4 className="text-[13px] font-medium text-[#222] line-clamp-2 leading-tight group-hover:text-[var(--rust)] transition-colors mb-2">
                      {product.name}
                    </h4>
                    <div className="flex items-center gap-1 mb-2">
                      <div className="flex text-[var(--rust)]">
                        {[...Array(5)].map((_, i) => (
                          <Star key={i} className={`w-2.5 h-2.5 ${i < Math.floor(product.rating || 5) ? 'fill-current' : 'opacity-20'}`} />
                        ))}
                      </div>
                      <span className="text-[10px] text-gray-400">({product.rating || "5.0"})</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <div className="text-[15px] font-bold text-[var(--rust)]">₱{(product.price || 0).toLocaleString()}</div>
                    <div className="text-[10px] text-gray-400">Sold 12</div>
                  </div>
                </div>
              </Link>
            ))}
          </div>

          {products.length === 0 && (
            <div className="py-24 text-center border-2 border-dashed border-gray-200 rounded-lg">
              <p className="font-serif italic text-gray-400">This artisan hasn't released any collection to the registry yet.</p>
            </div>
          )}
        </div>
      </div>
    </CustomerLayout>
  );
}
