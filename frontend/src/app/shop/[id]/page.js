import ShopClient from "@/components/ShopClient";

export const metadata = {
  title: "Artisan Workshop | Local Collection",
  description: "Explore the curated hand-made collections from local master crafters in Lumban, Laguna."
};

export const dynamic = 'force-static';
export const dynamicParams = false;

export function generateStaticParams() {
  return [{ id: '1' }];
}

export default function ShopBySellerPage() {
  return <ShopClient />;
}
