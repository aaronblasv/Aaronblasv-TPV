import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Order, OrderLine } from '../../types/order.model';
import { User } from '../../types/user.model';

@Injectable({ providedIn: 'root' })
export class OrderService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  getOrderByTable(tableUuid: string): Observable<Order | null> {
    return this.http.get<Order>(`${this.apiUrl}/tables/${tableUuid}/order`, { observe: 'response' }).pipe(
      map(response => response.status === 204 ? null : response.body)
    );
  }

  openOrder(tableId: string, openedByUserId: string, diners: number): Observable<Order> {
    return this.http.post<Order>(`${this.apiUrl}/orders`, {
      table_id: tableId,
      opened_by_user_id: openedByUserId,
      diners,
    });
  }

  addLine(orderUuid: string, productId: string, userId: string, quantity: number): Observable<OrderLine> {
    return this.http.post<OrderLine>(`${this.apiUrl}/orders/${orderUuid}/lines`, {
      product_id: productId,
      user_id: userId,
      quantity,
    });
  }

  updateLineQuantity(orderUuid: string, lineUuid: string, quantity: number): Observable<void> {
    return this.http.put<void>(`${this.apiUrl}/orders/${orderUuid}/lines/${lineUuid}`, { quantity });
  }

  removeLine(orderUuid: string, lineUuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/orders/${orderUuid}/lines/${lineUuid}`);
  }

  updateDiners(orderUuid: string, diners: number): Observable<void> {
    return this.http.patch<void>(`${this.apiUrl}/orders/${orderUuid}/diners`, { diners });
  }

  updateOrderDiscount(orderUuid: string, discountType: 'amount' | 'percentage' | null, discountValue: number): Observable<void> {
    return this.http.patch<void>(`${this.apiUrl}/orders/${orderUuid}/discount`, {
      discount_type: discountType,
      discount_value: discountValue,
    });
  }

  sendToKitchen(orderUuid: string): Observable<void> {
    return this.http.post<void>(`${this.apiUrl}/orders/${orderUuid}/send-to-kitchen`, {});
  }

  updateLineDiscount(orderUuid: string, lineUuid: string, discountType: 'amount' | 'percentage' | null, discountValue: number): Observable<void> {
    return this.http.patch<void>(`${this.apiUrl}/orders/${orderUuid}/lines/${lineUuid}/discount`, {
      discount_type: discountType,
      discount_value: discountValue,
    });
  }

  transferOrder(orderUuid: string, targetTableId: string): Observable<void> {
    return this.http.patch<void>(`${this.apiUrl}/orders/${orderUuid}/transfer`, {
      target_table_id: targetTableId,
    });
  }

  closeOrder(orderUuid: string, closedByUserId: string): Observable<void> {
    return this.http.post<void>(`${this.apiUrl}/orders/${orderUuid}/close`, { closed_by_user_id: closedByUserId });
  }

  cancelOrder(orderUuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/orders/${orderUuid}`);
  }

  getAllOpen(): Observable<Order[]> {
    return this.http.get<Order[]>(`${this.apiUrl}/orders/open`);
  }

  validatePin(pin: string): Observable<User> {
    return this.http.post<User>(`${this.apiUrl}/tpv/validate-pin`, { pin });
  }
}
