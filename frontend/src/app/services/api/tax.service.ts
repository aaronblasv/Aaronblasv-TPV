import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Tax, TaxFormData } from '../../types/tax.model';

@Injectable({
  providedIn: 'root'
})
export class TaxService {
  private apiUrl = `${environment.apiUrl}/taxes`;

  constructor(private http: HttpClient) {}

  getAll(): Observable<Tax[]> {
    return this.http.get<Tax[]>(this.apiUrl);
  }

  getAllTpv(): Observable<Tax[]> {
    return this.http.get<Tax[]>(`${environment.apiUrl}/tpv/taxes`);
  }

  create(data: TaxFormData): Observable<Tax> {
    return this.http.post<Tax>(this.apiUrl, data);
  }

  update(uuid: string, data: TaxFormData): Observable<Tax> {
    return this.http.put<Tax>(`${this.apiUrl}/${uuid}`, data);
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }
}
