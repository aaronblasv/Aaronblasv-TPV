export interface DashboardStats {
  products: number;
  families: number;
  taxes: number;
  users: number;
  sales_this_month: number;
  revenue_this_month: number;
}

export interface SaleThisMonth {
  uuid: string;
  ticket_number: string;
  total: number;
  value_date: string;
  table_name: string;
  user_name: string;
}

export interface TopProduct {
  name: string;
  total_quantity: number;
  total_revenue: number;
}

export interface SaleByDay {
  day: string;
  count: number;
  total: number;
}

export interface DashboardResponse {
  stats: DashboardStats;
  sales_this_month: SaleThisMonth[];
  top_products: TopProduct[];
  sales_by_day: SaleByDay[];
}
