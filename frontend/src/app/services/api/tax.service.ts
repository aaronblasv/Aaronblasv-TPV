import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Tax } from '../../types/tax.model';

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

  create(name: string, percentage: number): Observable<Tax> {
    return this.http.post<Tax>(this.apiUrl, { name, percentage });
  }

  update(uuid: string, name: string, percentage: number): Observable<Tax> {
    return this.http.put<Tax>(`${this.apiUrl}/${uuid}`, { name, percentage });
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }
}
