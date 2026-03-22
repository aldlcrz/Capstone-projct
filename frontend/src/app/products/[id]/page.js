import ProductDetailClient from "@/components/ProductDetailClient";

export const metadata = {
  title: "Product Details | Lumban Master Craft",
  description: "View hand-embroidered Filipino traditional Barong and Tagalog masterpieces."
};

export const dynamic = 'force-static';
export const dynamicParams = false;

export function generateStaticParams() {
  return [{ id: '1' }];
}

export default function ProductDetailPage() {
  return <ProductDetailClient />;
}
