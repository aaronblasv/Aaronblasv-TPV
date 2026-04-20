export interface OrderLine {
  uuid: string;
  product_id: string;
  user_id: string;
  quantity: number;
  price: number;
  tax_percentage: number;
  discount_type: 'amount' | 'percentage' | null;
  discount_value: number;
  discount_amount: number;
}

export interface Order {
  uuid: string;
  table_id: string;
  diners: number;
  status: string;
  discount_type: 'amount' | 'percentage' | null;
  discount_value: number;
  discount_amount: number;
  subtotal?: number;
  tax_amount?: number;
  total?: number;
  lines?: OrderLine[];
}
