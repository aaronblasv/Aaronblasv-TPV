export interface Sale {
  uuid: string;
  ticket_number: number;
  value_date: string;
  subtotal: number;
  tax_amount: number;
  line_discount_total: number;
  order_discount_total: number;
  total: number;
  refunded_total: number;
  net_total: number;
  table_name: string;
  open_user_name: string;
  close_user_name: string;
  opened_at: string;
  closed_at: string | null;
}

export interface SaleLine {
  uuid: string;
  product_name: string;
  quantity: number;
  price: number;
  tax_percentage: number;
  line_subtotal: number;
  tax_amount: number;
  discount_type: 'amount' | 'percentage' | null;
  discount_value: number;
  discount_amount: number;
  line_total: number;
  refunded_quantity: number;
}

export interface RefundPayload {
  method: 'cash' | 'card' | 'bizum';
  reason?: string;
  refund_all: boolean;
  lines?: Array<{
    sale_line_uuid: string;
    quantity: number;
  }>;
}

export interface RefundResponse {
  uuid: string;
  type: 'full' | 'partial';
  method: 'cash' | 'card' | 'bizum';
  subtotal: number;
  tax_amount: number;
  total: number;
}

export interface SalesReport {
  by_day: { day: string; count: number; total: number }[];
  by_zone: { zone_name: string; count: number; total: number }[];
  by_product: { product_name: string; total_quantity: number; total: number }[];
  by_user: { user_name: string; count: number; total: number }[];
}
