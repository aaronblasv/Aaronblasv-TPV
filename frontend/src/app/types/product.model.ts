import { Family } from './family.model';
import { Tax } from './tax.model';

export interface Product {
  uuid: string;
  name: string;
  price: number;
  stock: number;
  family_id: string;
  tax_id: string;
  active: boolean;
  image_src: string | null;
  family?: Family;
  tax?: Tax;
}

export interface ProductFormData {
  name: string;
  price: number;
  stock: number;
  family_id: string;
  tax_id: string;
  image_src: string | null;
}
