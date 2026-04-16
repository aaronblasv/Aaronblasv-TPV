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

  openOrder(tableId: string, openedByUserId: number, diners: number): Observable<Order> {
    return this.http.post<Order>(`${this.apiUrl}/orders`, {
      table_id: tableId,
      opened_by_user_id: String(openedByUserId),
      diners,
    });
  }

  addLine(orderUuid: string, productId: string, userId: number, quantity: number, price: number, taxPercentage: number): Observable<OrderLine> {
    return this.http.post<OrderLine>(`${this.apiUrl}/orders/${orderUuid}/lines`, {
      product_id: productId,
      user_id: String(userId),
      quantity,
      price,
      tax_percentage: taxPercentage,
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
