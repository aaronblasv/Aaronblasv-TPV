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
  sent_to_kitchen?: boolean;
  paid?: boolean;
  paid_at?: string | null;
}

export interface Order {
  uuid: string;
  table_id: string;
  opened_by_user_id?: string;
  diners: number;
  status: string;
  discount_type: 'amount' | 'percentage' | null;
  discount_value: number;
  discount_amount: number;
  total_paid?: number;
  subtotal?: number;
  tax_amount?: number;
  total?: number;
  opened_at?: string;
  lines?: OrderLine[];
}
