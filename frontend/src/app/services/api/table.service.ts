import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Table, TableFormData } from '../../types/table.model';

@Injectable({
  providedIn: 'root'
})
export class TableService {
  private apiUrl = `${environment.apiUrl}/tables`;

  constructor(private http: HttpClient) {}

  getAll(): Observable<Table[]> {
    return this.http.get<Table[]>(this.apiUrl);
  }

  create(data: TableFormData): Observable<Table> {
    return this.http.post<Table>(this.apiUrl, data);
  }

  update(uuid: string, data: TableFormData): Observable<Table> {
    return this.http.put<Table>(`${this.apiUrl}/${uuid}`, data);
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }

  getAllTpv(): Observable<Table[]> {
    return this.http.get<Table[]>(`${environment.apiUrl}/tpv/tables`);
  }

  mergeTables(parentUuid: string, childUuids: string[]): Observable<void> {
    return this.http.post<void>(`${environment.apiUrl}/tpv/tables/${parentUuid}/merge`, {
      table_uuids: childUuids,
    });
  }

  unmergeTables(parentUuid: string): Observable<void> {
    return this.http.post<void>(`${environment.apiUrl}/tpv/tables/${parentUuid}/unmerge`, {});
  }
}
