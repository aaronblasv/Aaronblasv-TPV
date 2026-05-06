export type PaymentMethod = 'cash' | 'card' | 'bizum';

export interface PaymentLineAllocation {
  line_uuid: string;
  quantity: number;
}

export interface PaymentData {
  amount: number;
  method: PaymentMethod;
  description?: string;
  lineAllocations?: PaymentLineAllocation[];
}

export interface RegisteredPaymentResponse {
  uuid: string;
  order_uuid: string;
  amount: number;
  method: PaymentMethod;
  total_paid: number;
  description?: string | null;
}
