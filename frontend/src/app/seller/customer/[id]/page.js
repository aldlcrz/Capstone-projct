import SellerCustomerClient from "@/components/SellerCustomerClient";

export const metadata = {
  title: "Patron Portfolio | Artisan Dashboard",
  description: "Detailed commission history and contact intelligence for your workshop patrons."
};

export const dynamic = 'force-static';
export const dynamicParams = false;

export function generateStaticParams() {
  return [{ id: '1' }];
}

export default function SellerCustomerDetailPage() {
  return <SellerCustomerClient />;
}
