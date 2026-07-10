"use client";
import { Suspense } from "react";
import ProductDetailClient from "@/components/ProductDetailClient";

export default function AdminProductViewPage() {
  return (
    <Suspense fallback={null}>
      <ProductDetailClient />
    </Suspense>
  );
}
