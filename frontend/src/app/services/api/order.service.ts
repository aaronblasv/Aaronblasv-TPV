import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class OrderService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  getOrderByTable(tableUuid: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/tables/${tableUuid}/order`, { observe: 'response' }).pipe(
      map(response => response.status === 204 ? null : response.body)
    );
  }

  openOrder(tableId: string, openedByUserId: string, diners: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/orders`, { table_id: tableId, opened_by_user_id: openedByUserId, diners });
  }

  addLine(orderUuid: string, productId: string, userId: string, quantity: number, price: number, taxPercentage: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/orders/${orderUuid}/lines`, {
      product_id: productId, user_id: userId, quantity, price, tax_percentage: taxPercentage,
    });
  }

  updateLineQuantity(orderUuid: string, lineUuid: string, quantity: number): Observable<any> {
    return this.http.put(`${this.apiUrl}/orders/${orderUuid}/lines/${lineUuid}`, { quantity });
  }

  removeLine(orderUuid: string, lineUuid: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/orders/${orderUuid}/lines/${lineUuid}`);
  }

  updateDiners(orderUuid: string, diners: number): Observable<any> {
    return this.http.patch(`${this.apiUrl}/orders/${orderUuid}/diners`, { diners });
  }

  closeOrder(orderUuid: string, closedByUserId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/orders/${orderUuid}/close`, { closed_by_user_id: closedByUserId });
  }

  cancelOrder(orderUuid: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/orders/${orderUuid}`);
  }

  getAllOpen(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/orders/open`);
  }

  getAllTpv(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/tpv/zones`);
  }

  validatePin(pin: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/tpv/validate-pin`, { pin });
  }
}