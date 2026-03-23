import { Suspense } from "react";
import SellerCustomerClient from "@/components/SellerCustomerClient";

export const metadata = {
  title: "Patron Portfolio | Artisan Dashboard",
  description: "Detailed commission history and contact intelligence for your workshop patrons."
};

export default function SellerCustomerDetailPage() {
  return (
    <Suspense fallback={null}>
      <SellerCustomerClient />
    </Suspense>
  );
}
