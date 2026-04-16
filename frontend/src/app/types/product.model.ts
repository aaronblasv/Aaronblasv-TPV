export interface Product {
  uuid: string;
  name: string;
  price: number;
  family_id: string;
  tax_id: string;
  active: boolean;
  image_src: string | null;
}
