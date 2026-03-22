import EditProductClient from "@/components/EditProductClient";

export const metadata = {
  title: "Edit Masterpiece | Artisan Workshop",
  description: "Refine and update your handcrafted Barong and Filipiniana masterpieces in the municipal registry."
};

export const dynamic = 'force-static';
export const dynamicParams = false;

export function generateStaticParams() {
  return [{ id: '1' }];
}

export default function EditProductPage() {
  return <EditProductClient />;
}
