export interface Sale {
  uuid: string;
  ticket_number: number;
  value_date: string;
  total: number;
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
}

export interface SalesReport {
  by_day: { day: string; count: number; total: number }[];
  by_zone: { zone_name: string; count: number; total: number }[];
  by_product: { product_name: string; total_quantity: number; total: number }[];
  by_user: { user_name: string; count: number; total: number }[];
}
