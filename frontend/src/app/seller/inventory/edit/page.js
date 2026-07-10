import { Suspense } from "react";
import EditProductClient from "@/components/EditProductClient";
import SellerLayout from "@/components/SellerLayout";

export const metadata = {
  title: "Edit product | Artisan Workshop",
  description: "Refine and update your handcrafted Barong and Filipiniana masterpieces in the municipal registry."
};

export default function EditProductPage() {
  return (
    <SellerLayout>
      <Suspense fallback={null}>
        <EditProductClient />
      </Suspense>
    </SellerLayout>
  );
}
