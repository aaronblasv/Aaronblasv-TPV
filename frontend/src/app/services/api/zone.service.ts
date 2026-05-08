import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Zone, ZoneFormData } from '../../types/zone.model';

@Injectable({
  providedIn: 'root'
})
export class ZoneService {
  private apiUrl = `${environment.apiUrl}/zones`;

  constructor(private http: HttpClient) {}

  getAll(): Observable<Zone[]> {
    return this.http.get<Zone[]>(this.apiUrl);
  }

  create(data: ZoneFormData): Observable<Zone> {
    return this.http.post<Zone>(this.apiUrl, data);
  }

  update(uuid: string, data: ZoneFormData): Observable<Zone> {
    return this.http.put<Zone>(`${this.apiUrl}/${uuid}`, data);
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }

  getAllTpv(): Observable<Zone[]> {
    return this.http.get<Zone[]>(`${environment.apiUrl}/tpv/zones`);
  }
}
