import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Sale, SaleLine, SalesReport } from '../../types/sale.model';

@Injectable({
  providedIn: 'root'
})
export class SaleService {
  private apiUrl = `${environment.apiUrl}/sales`;

  constructor(private http: HttpClient) {}

  getAll(from?: string, to?: string): Observable<Sale[]> {
    let params = new HttpParams();
    if (from) params = params.set('from', from);
    if (to)   params = params.set('to', to);
    return this.http.get<Sale[]>(this.apiUrl, { params });
  }

  getLines(saleUuid: string): Observable<SaleLine[]> {
    return this.http.get<SaleLine[]>(`${this.apiUrl}/${saleUuid}/lines`);
  }

  getReport(from?: string, to?: string): Observable<SalesReport> {
    let params = new HttpParams();
    if (from) params = params.set('from', from);
    if (to)   params = params.set('to', to);
    return this.http.get<SalesReport>(`${this.apiUrl}/report`, { params });
  }
}
