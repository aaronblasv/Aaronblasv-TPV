import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Family } from '../../types/family.model';

@Injectable({
  providedIn: 'root'
})
export class FamilyService {
  private apiUrl = `${environment.apiUrl}/families`;

  constructor(private http: HttpClient) {}

  getAll(): Observable<Family[]> {
    return this.http.get<Family[]>(this.apiUrl);
  }

  getAllTpv(): Observable<Family[]> {
    return this.http.get<Family[]>(`${environment.apiUrl}/tpv/families`);
  }

  create(name: string): Observable<Family> {
    return this.http.post<Family>(this.apiUrl, { name, active: true });
  }

  update(uuid: string, name: string): Observable<Family> {
    return this.http.put<Family>(`${this.apiUrl}/${uuid}`, { name });
  }

  activate(uuid: string): Observable<Family> {
    return this.http.patch<Family>(`${this.apiUrl}/${uuid}/activate`, {});
  }

  deactivate(uuid: string): Observable<Family> {
    return this.http.patch<Family>(`${this.apiUrl}/${uuid}/deactivate`, {});
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }
}
