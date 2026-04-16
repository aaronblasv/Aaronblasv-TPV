export interface OrderLine {
  uuid: string;
  product_id: string;
  quantity: number;
  price: number;
  tax_percentage: number;
}

export interface Order {
  uuid: string;
  table_id: string;
  diners: number;
  status: string;
  lines: OrderLine[];
}
