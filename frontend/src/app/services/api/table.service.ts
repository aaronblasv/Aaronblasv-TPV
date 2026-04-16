import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Table } from '../../types/table.model';

@Injectable({
  providedIn: 'root'
})
export class TableService {
  private apiUrl = `${environment.apiUrl}/tables`;

  constructor(private http: HttpClient) {}

  getAll(): Observable<Table[]> {
    return this.http.get<Table[]>(this.apiUrl);
  }

  create(data: Partial<Table>): Observable<Table> {
    return this.http.post<Table>(this.apiUrl, data);
  }

  update(uuid: string, data: Partial<Table>): Observable<Table> {
    return this.http.put<Table>(`${this.apiUrl}/${uuid}`, data);
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }

  getAllTpv(): Observable<Table[]> {
    return this.http.get<Table[]>(`${environment.apiUrl}/tpv/tables`);
  }
}
