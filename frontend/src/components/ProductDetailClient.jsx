"use client";
import React, { useState, useEffect, useCallback } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import CustomerLayout from "./CustomerLayout";
import AdminLayout from "./AdminLayout";
import SellerLayout from "./SellerLayout";
import Image from "next/image";
import Link from "next/link";
import {
  ShoppingCart,
  Star,
  Truck,
  Loader2,
  ChevronRight,
  ShieldCheck,
  MessageCircle,
  MapPin,
  Package,
  Award,
  Minus,
  Plus,
  Store
} from "lucide-react";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { normalizeProductImages, normalizeProductSizes } from "@/lib/productImages";

export default function ProductDetailClient() {
  const searchParams = useSearchParams();
  const id = searchParams.get("id");
  const router = useRouter();
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedSize, setSelectedSize] = useState(null);
  const [quantity, setQuantity] = useState(1);
  const [activeImage, setActiveImage] = useState(0);
  const [userRole, setUserRole] = useState(null);
  const [addedToCart, setAddedToCart] = useState(false);
  const [selectionError, setSelectionError] = useState(false);

  useEffect(() => {
    try {
      const stored = JSON.parse(localStorage.getItem("user") || "{}");
      setUserRole(stored.role || "customer");
    } catch (e) {
      setUserRole("customer");
    }
  }, []);

  const isRestricted = userRole === "admin";
  const showActions = userRole !== "admin"; // Admins cannot initiate purchases as they are for management only

  const fetchProduct = useCallback(async () => {
    try {
      const res = await api.get(`/products/${id}`);
      const normalizedSizes = normalizeProductSizes(res.data.sizes);
      setProduct({
        ...res.data,
        sizes: normalizedSizes,
      });
    } catch (err) {
      console.warn("Artisan entry not found in registry.", err);
      setProduct({
        id: id,
        name: "Premium Barong Polo Shirt Men Filipino Traditional Printing Loose Casual T-Shirt Fashionable Short Sleeves Comfortable Breathable",
        price: 255,
        description: "A signature hand-embroidered order from the heart of Lumban. It features ultra-premium materials and traditional Filipiniana crafting perfectly suited for modern occasions.",
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
    if (!id) {
      setLoading(false);
      return;
    }
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
    if (!product || userRole === 'admin') return;
    if (!selectedSize) {
      setSelectionError(true);
      return;
    }
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
    setAddedToCart(true);
    setTimeout(() => setAddedToCart(false), 2000);
  };

  const handleBuyNow = () => {
    if (!product || userRole === 'admin') return;
    if (!selectedSize) {
      setSelectionError(true);
      return;
    }
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

  // Dynamic Layout selection
  const Layout = userRole === 'admin' ? AdminLayout : (userRole === 'seller' ? SellerLayout : CustomerLayout);

  if (loading) return (
    <Layout>
      <div className="h-[70vh] flex flex-col items-center justify-center space-y-6">
        <Loader2 className="w-10 h-10 animate-spin text-[var(--rust)] opacity-50" />
      </div>
    </Layout>
  );

  if (!id) return (
    <Layout>
      <div className="h-[70vh] flex items-center justify-center px-4 text-center text-[var(--muted)]">
        Select a product first to view its details.
      </div>
    </Layout>
  );

  const sizeOptions = normalizeProductSizes(product?.sizes);
  const availableSizes = sizeOptions.length > 0 ? sizeOptions : ["S", "M", "L", "XL", "2XL"];
  // No longer auto-select, must be explicitly picked
  const effectiveSize = selectedSize;

  return (
    <Layout>
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap');

        .pd-page { background: #F0EBE3; min-height: 100vh; padding: 2rem 0 5rem; }
        .pd-wrap { max-width: 1100px; margin: 0 auto; padding: 0 1.25rem; }

        /* ── Hero Card ── */
        .pd-hero {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 0;
          background: #fff;
          border-radius: 1.25rem;
          overflow: hidden;
          box-shadow: 0 8px 48px rgba(60,35,10,0.10);
          margin-bottom: 1.5rem;
        }
        @media(max-width: 768px){
          .pd-hero { grid-template-columns: 1fr; }
        }

        /* Left – dark image panel */
        .pd-gallery {
          background: #000;
          position: relative;
          overflow: hidden;
          min-height: 650px;
        }
        .pd-gallery-badge {
          position: absolute;
          top: 1.5rem; left: 1.5rem;
          display: flex; align-items: center; gap: 0.4rem;
          background: rgba(26,18,8,0.7);
          backdrop-filter: blur(12px);
          border: 1px solid rgba(255,255,255,0.15);
          border-radius: 8px;
          padding: 0.4rem 0.8rem;
          font-size: 11px; font-weight: 700;
          color: #e8d5b0; letter-spacing: 0.1em; text-transform: uppercase;
          z-index: 10;
          box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }
        .pd-main-img {
          position: absolute;
          inset: 0;
          width: 100%;
          height: 100%;
          overflow: hidden;
          z-index: 1;
        }
        .pd-thumbs {
          position: absolute;
          bottom: 1.5rem;
          left: 0; right: 0;
          display: flex; gap: 0.75rem;
          justify-content: center;
          z-index: 10;
          padding: 0 1rem;
        }
        .pd-thumb {
          position: relative; width: 54px; height: 54px;
          border-radius: 0.65rem; overflow: hidden; cursor: pointer;
          border: 2.5px solid rgba(255,255,255,0.2);
          transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
          background: rgba(0,0,0,0.5);
          backdrop-filter: blur(4px);
          flex-shrink: 0;
          box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .pd-thumb:hover { transform: translateY(-3px) scale(1.05); border-color: rgba(255,255,255,0.5); }
        .pd-thumb.active { border-color: #D4A96A; transform: translateY(-3px); box-shadow: 0 8px 24px rgba(212,169,106,0.4); }

        /* Right – info panel */
        .pd-info {
          padding: 2.5rem 2rem 2rem;
          display: flex; flex-direction: column;
          background: #fff;
        }
        .pd-eyebrow {
          font-family: 'Inter', sans-serif;
          font-size: 10.5px; font-weight: 700;
          letter-spacing: 0.14em; text-transform: uppercase;
          color: #9c6e30;
          display: flex; align-items: center; gap: 0.5rem;
          margin-bottom: 0.7rem;
        }
        .pd-eyebrow::before {
          content: ''; display: inline-block;
          width: 1.4rem; height: 1.5px; background: #C0853A;
        }
        .pd-title {
          font-family: 'Playfair Display', serif;
          font-size: 1.55rem; font-weight: 600; line-height: 1.3;
          color: #1C1209;
          margin-bottom: 1rem;
        }

        /* Rating row */
        .pd-rating-row {
          display: flex; align-items: center; gap: 0;
          padding: 0.65rem 0; border-top: 1px solid #F0EBE3; border-bottom: 1px solid #F0EBE3;
          margin-bottom: 1.2rem;
        }
        .pd-rating-seg { display: flex; align-items: center; gap: 0.4rem; padding: 0 1rem; }
        .pd-rating-seg:first-child { padding-left: 0; }
        .pd-rating-sep { width: 1px; height: 18px; background: #E5DDD5; }
        .pd-stars { display: flex; color: #C0853A; }
        .pd-score { font-family: 'Playfair Display', serif; font-size: 15px; font-weight: 700; color: #C0853A; }
        .pd-meta-label { font-size: 12px; color: #9c8876; }
        .pd-meta-val { font-family: 'Playfair Display', serif; font-size: 15px; font-weight: 700; color: #1C1209; }

        /* Price */
        .pd-price-block {
          background: linear-gradient(135deg, #FDF5E8 0%, #FEF9F0 100%);
          border: 1px solid #EDD9A3;
          border-radius: 0.75rem;
          padding: 0.9rem 1.1rem;
          margin-bottom: 1.4rem;
          display: flex; align-items: baseline; gap: 0.5rem;
        }
        .pd-currency { font-family: 'Playfair Display', serif; font-size: 18px; color: #9c6e30; font-weight: 700; margin-bottom: 4px; }
        .pd-price { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #7B3A10; font-weight: 700; line-height:1; }

        /* Info rows */
        .pd-row { display: flex; align-items: flex-start; gap: 0; margin-bottom: 1rem; }
        .pd-row-label { width: 6.5rem; flex-shrink: 0; font-size: 12.5px; color: #9c8876; padding-top: 2px; font-weight: 500; }
        .pd-row-body { flex: 1; font-size: 13px; color: #1C1209; }
        .pd-ship-line { display: flex; align-items: center; gap: 0.4rem; color: #1C1209; font-size: 13px; margin-bottom: 0.25rem; }
        .pd-ship-fee { font-size: 11.5px; color: #9c8876; padding-left: 1.4rem; }
        .pd-guarantee { display: flex; align-items: center; gap: 0.45rem; font-size: 13px; color: #1C1209; cursor: pointer; }
        .pd-guarantee:hover { color: #C0422A; }

        /* Variant / Size chips */
        .pd-chip-wrap { display: flex; flex-wrap: wrap; gap: 0.45rem; }
        .pd-chip {
          display: flex; align-items: center; gap: 0.4rem;
          padding: 0.35rem 0.65rem 0.35rem 0.45rem;
          border: 1.5px solid #E5DDD5;
          border-radius: 6px;
          cursor: pointer; transition: all 0.18s;
          font-size: 12.5px; color: #3D2B1F; background: #fff;
        }
        .pd-chip:hover { border-color: #C0853A; background: #FDF5E8; }
        .pd-chip.active { border-color: #9c6e30; background: #FDF5E8; color: #7B3A10; box-shadow: 0 0 0 2px rgba(192,133,58,0.18); }
        .pd-chip-img { position: relative; width: 28px; height: 28px; border-radius: 4px; overflow: hidden; background:#f5f0ea; flex-shrink:0; }

        .pd-size-chip {
          padding: 0.35rem 0.9rem;
          border: 1.5px solid #E5DDD5;
          border-radius: 6px;
          cursor: pointer; transition: all 0.18s;
          font-size: 13px; color: #3D2B1F; background: #fff;
          font-weight: 500;
        }
        .pd-size-chip:hover { border-color: #C0853A; background: #FDF5E8; }
        .pd-size-chip.active { border-color: #9c6e30; background: #FDF5E8; color: #7B3A10; box-shadow: 0 0 0 2px rgba(192,133,58,0.18); }
        .pd-size-chip.error { border-color: #C0422A; background: #FFF5F0; animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }

        @keyframes shake {
          10%, 90% { transform: translate3d(-1px, 0, 0); }
          20%, 80% { transform: translate3d(2px, 0, 0); }
          30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
          40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        /* Quantity */
        .pd-qty { display: flex; align-items: center; gap: 0; border: 1.5px solid #E5DDD5; border-radius: 8px; overflow: hidden; }
        .pd-qty-btn {
          width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
          background: #F7F3EE; color: #5c4f3a; transition: background 0.15s; cursor: pointer; border: none; flex-shrink: 0;
        }
        .pd-qty-btn:hover { background: #EDD9A3; }
        .pd-qty-val { width: 46px; height: 36px; display:flex; align-items:center; justify-content:center; font-family: 'Playfair Display', serif; font-size:16px; font-weight:700; color:#1C1209; background:#fff; border-left:1.5px solid #E5DDD5; border-right:1.5px solid #E5DDD5; }
        .pd-stock { font-size: 12px; color: #9c8876; margin-left: 0.8rem; }
        .pd-stock-val { font-family: 'Playfair Display', serif; font-weight: 700; color: #C0853A; font-size: 13px; }

        /* Action Buttons */
        .pd-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; }
        .pd-btn-cart {
          flex: 1;
          display: flex; align-items: center; justify-content: center; gap: 0.5rem;
          padding: 0.85rem 1.2rem;
          border: 2px solid #C0420A;
          border-radius: 10px;
          font-size: 14px; font-weight: 600; color: #C0420A;
          background: #fff;
          cursor: pointer; transition: all 0.2s;
          font-family: 'Inter', sans-serif;
        }
        .pd-btn-cart:hover { background: #FFF5F0; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(192,66,42,0.12); }
        .pd-btn-cart.success { background: #F0FFF5; border-color: #2E7D53; color: #2E7D53; }
        .pd-btn-buy {
          flex: 1;
          display: flex; align-items: center; justify-content: center;
          padding: 0.85rem 1.2rem;
          border-radius: 10px;
          font-size: 14px; font-weight: 600; color: #fff;
          background: linear-gradient(135deg, #9c3010 0%, #C0420A 60%, #D4562A 100%);
          cursor: pointer; transition: all 0.2s; border: none;
          box-shadow: 0 4px 18px rgba(192,66,42,0.25);
          font-family: 'Inter', sans-serif;
        }
        .pd-btn-buy:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(192,66,42,0.35); }

        /* ── Artisan Card ── */
        .pd-artisan {
          background: #fff;
          border-radius: 1.25rem;
          box-shadow: 0 4px 24px rgba(60,35,10,0.07);
          padding: 1.5rem 1.75rem;
          margin-bottom: 1.5rem;
          display: flex; align-items: center; gap: 1.5rem;
        }
        .pd-artisan-avatar {
          width: 72px; height: 72px; border-radius: 50%; flex-shrink: 0;
          display: flex; align-items: center; justify-content: center;
          font-family: 'Playfair Display', serif;
          font-size: 1.75rem; font-weight: 700;
          background: linear-gradient(135deg, #3D2B1F, #6B4226);
          color: #E8C98C; box-shadow: 0 4px 16px rgba(60,35,10,0.20);
        }
        .pd-artisan-info { flex: 1; border-right: 1px solid #F0EBE3; padding-right: 1.5rem; }
        .pd-artisan-name { font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 600; color: #1C1209; margin-bottom: 2px; }
        .pd-artisan-status { display: flex; align-items: center; gap: 0.35rem; font-size: 12px; color: #7B9B68; margin-bottom: 0.75rem; }
        .pd-artisan-status::before { content:''; width:7px; height:7px; background:#7B9B68; border-radius:50%; flex-shrink:0; }
        .pd-artisan-btns { display: flex; gap: 0.5rem; }
        .pd-art-btn {
          display: flex; align-items: center; gap: 0.35rem;
          padding: 0.4rem 0.85rem; border-radius: 7px;
          font-size: 12px; font-weight: 500; cursor: pointer;
          transition: all 0.18s;
          border: 1.5px solid #E5DDD5; color: #3D2B1F; background: #F7F3EE;
        }
        .pd-art-btn:hover { border-color: #C0853A; background: #FDF5E8; }
        .pd-artisan-stats { flex: 1; padding-left: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem 1.5rem; }
        .pd-stat { display: flex; flex-direction: column; gap: 2px; }
        .pd-stat-label { font-size: 11.5px; color: #9c8876; }
        .pd-stat-val { font-size: 14px; font-weight: 700; color: #7B3A10; font-family: 'Playfair Display', serif; }
        @media(max-width:640px){
          .pd-artisan { flex-direction: column; align-items: flex-start; }
          .pd-artisan-info { border-right: none; border-bottom: 1px solid #F0EBE3; padding-right:0; padding-bottom:1rem; width:100%; }
          .pd-artisan-stats { padding-left:0; padding-top:0.75rem; }
        }

        /* ── Specs & Desc Card ── */
        .pd-details-card {
          background: #fff;
          border-radius: 1.25rem;
          box-shadow: 0 4px 24px rgba(60,35,10,0.07);
          overflow: hidden;
        }
        .pd-section-head {
          display: flex; align-items: center; gap: 0.6rem;
          padding: 1.1rem 1.75rem;
          background: linear-gradient(90deg, #2E1A0E, #4B2A12);
          color: #E8C98C;
          font-family: 'Playfair Display', serif; font-size: 15px; font-weight: 600;
        }
        .pd-specs { padding: 1.25rem 1.75rem 0.5rem; }
        .pd-spec-row { display: flex; align-items: center; padding: 0.65rem 0; border-bottom: 1px solid #F7F3EE; }
        .pd-spec-row:last-child { border-bottom: none; }
        .pd-spec-key { width: 10rem; font-size: 12.5px; color: #9c8876; font-weight: 500; }
        .pd-spec-val { font-family: 'Playfair Display', serif; font-size: 13.5px; color: #1C1209; font-weight: 600; }
        .pd-desc { padding: 0 1.75rem 1.75rem; font-size: 14px; color: #3D2B1F; line-height: 1.78; white-space: pre-wrap; }
        .pd-divider { height: 1px; background: #F0EBE3; margin: 0 1.75rem; }
      `}</style>

      <div className="pd-page">
        <div className="pd-wrap">

          {/* ───── Hero Card ───── */}
          <div className="pd-hero">

            {/* Left – Gallery */}
            <div className="pd-gallery">
              <div className="pd-gallery-badge">
                <Award size={12} />
                Lumban Artisan Craft
              </div>
              <div className="pd-main-img">
                <Image
                  src={galleryImages[activeImage]?.url || galleryImages[activeImage]}
                  alt={product.name}
                  fill
                  className="object-cover object-top"
                  priority
                />
              </div>
              <div className="pd-thumbs">
                {galleryImages.map((img, i) => (
                  <div
                    key={i}
                    onClick={() => setActiveImage(i)}
                    className={`pd-thumb ${activeImage === i ? 'active' : ''}`}
                  >
                    <Image src={img.url || img} alt="thumb" fill className="object-cover" />
                  </div>
                ))}
              </div>
            </div>

            {/* Right – Info */}
            <div className="pd-info">
              <div className="pd-eyebrow">
                <span>{product.category || "Barong Tagalog"}</span>
              </div>
              <h1 className="pd-title">{product.name}</h1>

              {/* Rating row */}
              <div className="pd-rating-row">
                <div className="pd-rating-seg">
                  <span className="pd-score">{product.rating ? Number(product.rating).toFixed(1) : "5.0"}</span>
                  <div className="pd-stars">
                    {[...Array(5)].map((_, i) => <Star key={i} className="w-3.5 h-3.5 fill-current" />)}
                  </div>
                </div>
                <div className="pd-rating-sep" />
                <div className="pd-rating-seg">
                  <span className="pd-meta-val">4</span>
                  <span className="pd-meta-label">&nbsp;Ratings</span>
                </div>
                <div className="pd-rating-sep" />
                <div className="pd-rating-seg">
                  <span className="pd-meta-val">20</span>
                  <span className="pd-meta-label">&nbsp;Sold</span>
                </div>
              </div>

              {/* Price */}
              <div className="pd-price-block">
                <span className="pd-currency">₱</span>
                <span className="pd-price">{(product.price || 0).toLocaleString()}</span>
              </div>

              {/* Shipping */}
              <div className="pd-row">
                <span className="pd-row-label">Shipping</span>
                <div className="pd-row-body">
                  <div className="pd-ship-line">
                    <Truck className="w-3.5 h-3.5 text-[#7B9B68]" />
                    <span>Est. arrival in <span className="pd-meta-val">5</span> days</span>
                    <ChevronRight className="w-3 h-3 opacity-40" />
                  </div>
                  <div className="pd-ship-fee">
                    {product.shippingFee > 0 ? (
                      <span>Shipping Fee: ₱<span className="pd-meta-val">{product.shippingFee.toLocaleString()}</span></span>
                    ) : (
                      <span>Shipping Fee: ₱<span className="pd-meta-val">40.00</span></span>
                    )}
                  </div>
                </div>
              </div>

              {/* Guarantee */}
              <div className="pd-row">
                <span className="pd-row-label" style={{ lineHeight: '1.4' }}>Shopping<br />Guarantee</span>
                <div className="pd-row-body">
                  <div className="pd-guarantee">
                    <ShieldCheck className="w-4 h-4" style={{ color: '#7B9B68' }} />
                    <span>Free &amp; Easy Returns · Merchandise Protection</span>
                    <ChevronRight className="w-3 h-3 opacity-40" />
                  </div>
                </div>
              </div>

              {/* Color / Variation */}
              <div className="pd-row">
                <span className="pd-row-label" style={{ paddingTop: '4px' }}>Variation</span>
                <div className="pd-chip-wrap">
                  {galleryImages.map((img, i) => (
                    <button
                      key={i}
                      onClick={() => setActiveImage(i)}
                      className={`pd-chip ${activeImage === i ? 'active' : ''}`}
                    >
                      <div className="pd-chip-img">
                        <Image src={img.url || img} alt="color variant" fill className="object-cover" />
                      </div>
                      <span>{img.variation || `Variant ${i + 1}`}</span>
                    </button>
                  ))}
                </div>
              </div>

              {/* Size */}
              <div className="pd-row">
                <span className="pd-row-label" style={{ paddingTop: '4px' }}>Size</span>
                <div className="pd-chip-wrap">
                  {availableSizes.map((size) => (
                    <button
                      key={size}
                      onClick={() => { setSelectedSize(size); setSelectionError(false); }}
                      className={`pd-size-chip ${effectiveSize === size ? 'active' : ''} ${selectionError && !selectedSize ? 'error' : ''}`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>

              {/* Quantity */}
              <div className="pd-row">
                <span className="pd-row-label">Quantity</span>
                <div style={{ display: 'flex', alignItems: 'center' }}>
                  <div className="pd-qty">
                    <button className="pd-qty-btn" onClick={() => setQuantity(Math.max(1, quantity - 1))}>
                      <Minus size={13} />
                    </button>
                    <div className="pd-qty-val">{quantity}</div>
                    <button className="pd-qty-btn" onClick={() => setQuantity(quantity + 1)}>
                      <Plus size={13} />
                    </button>
                  </div>
                  <span className="pd-stock"><span className="pd-stock-val">{product.stock || 197}</span> pieces available</span>
                </div>
              </div>

              {/* Action Buttons */}
              {showActions && (
                <div className="pd-actions-container">
                  {selectionError && !selectedSize && (
                    <div className="text-red-600 text-xs font-bold mb-3 flex items-center gap-1 animate-pulse">
                      <ChevronRight size={10} className="rotate-90" /> Please select a preferred size
                    </div>
                  )}
                  <div className="pd-actions">
                    <button
                      onClick={handleAddToCart}
                      className={`pd-btn-cart ${addedToCart ? 'success' : ''}`}
                    >
                      <ShoppingCart size={17} />
                      {addedToCart ? "Added!" : "Add to Cart"}
                    </button>
                    <button onClick={handleBuyNow} className="pd-btn-buy">
                      Buy Now
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* ───── Artisan / Shop Card ───── */}
          <div className="pd-artisan">
            <div className="pd-artisan-avatar">
              {(product.artisan || "L")[0].toUpperCase()}
            </div>
            <div className="pd-artisan-info">
              <div className="pd-artisan-name">{product.artisan || "Lumban Master Craft"}</div>
              <div className="pd-artisan-status">Active 5 minutes ago</div>
              <div className="pd-artisan-btns">
                {!isRestricted && (
                  <Link
                    href={`/messages?sellerId=${product.sellerId || 1}&sellerName=${product.artisan || "Lumban Master Craft"}&productId=${product.id}&productName=${encodeURIComponent(product.name)}&productImage=${encodeURIComponent(galleryImages[0]?.url || galleryImages[0])}&productPrice=${product.price}`}
                    className="pd-art-btn"
                  >
                    <MessageCircle size={13} style={{ color: '#C0422A' }} /> Chat Now
                  </Link>
                )}
                <Link href={`/shop?id=${product.sellerId || 1}`} className="pd-art-btn">
                  <Store size={13} /> View Shop
                </Link>
              </div>
            </div>
            <div className="pd-artisan-stats">
              <div className="pd-stat">
                <span className="pd-stat-label">Ratings</span>
                <span className="pd-stat-val">523</span>
              </div>
              <div className="pd-stat">
                <span className="pd-stat-label">Response Rate</span>
                <span className="pd-stat-val">98%</span>
              </div>
              <div className="pd-stat">
                <span className="pd-stat-label">Joined</span>
                <span className="pd-stat-val">12 Mo. Ago</span>
              </div>
              <div className="pd-stat">
                <span className="pd-stat-label">Products</span>
                <span className="pd-stat-val">16</span>
              </div>
            </div>
          </div>

          {/* ───── Specs & Description ───── */}
          <div className="pd-details-card">
            <div className="pd-section-head">
              <Package size={15} />
              Product Specifications
            </div>
            <div className="pd-specs">
              <div className="pd-spec-row">
                <span className="pd-spec-key">Category</span>
                <span className="pd-spec-val">{product.category || "Barong Tagalog"}</span>
              </div>
              <div className="pd-spec-row">
                <span className="pd-spec-key">Stock</span>
                <span className="pd-spec-val">{product.stock || 197}</span>
              </div>
              <div className="pd-spec-row">
                <span className="pd-spec-key">Ships From</span>
                <span className="pd-spec-val" style={{ display: 'flex', alignItems: 'center', gap: '0.3rem' }}>
                  <MapPin size={13} style={{ color: '#9c8876' }} /> Lumban, Laguna
                </span>
              </div>
            </div>

            <div className="pd-divider" />

            <div className="pd-section-head" style={{ marginTop: '0' }}>
              <Award size={15} />
              Product Description
            </div>
            <div className="pd-desc">{product.description || product.name}</div>
          </div>

        </div>
      </div>
    </Layout>
  );
}
