export type PaymentMethod = 'cash' | 'card' | 'bizum';

export interface PaymentData {
  amount: number;
  method: PaymentMethod;
  description?: string;
}
