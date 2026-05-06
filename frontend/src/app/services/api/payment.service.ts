import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Invoice } from '../../types/invoice.model';
import { PaymentLineAllocation, PaymentMethod, RegisteredPaymentResponse } from '../../types/payment.model';

@Injectable({ providedIn: 'root' })
export class PaymentService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  registerPayment(orderUuid: string, paidByUserId: string, amount: number, method: PaymentMethod, description?: string, lineAllocations: PaymentLineAllocation[] = []): Observable<RegisteredPaymentResponse> {
    return this.http.post<RegisteredPaymentResponse>(`${this.apiUrl}/orders/${orderUuid}/payments`, {
      paid_by_user_id: paidByUserId,
      amount,
      method,
      description: description ?? null,
      line_allocations: lineAllocations,
    });
  }

  generateInvoice(orderUuid: string, issuedByUserId: string): Observable<Invoice> {
    return this.http.post<Invoice>(`${this.apiUrl}/orders/${orderUuid}/generate-invoice`, {
      issued_by_user_id: issuedByUserId,
    });
  }
}
