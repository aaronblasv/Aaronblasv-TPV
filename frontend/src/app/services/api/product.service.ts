import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ProductService {
  private apiUrl = `${environment.apiUrl}/products`;

  constructor(private http: HttpClient) {
    console.log('apiUrl:', this.apiUrl);
  }

  getAll(): Observable<any[]> {
    return this.http.get<any[]>(this.apiUrl);
    }

  create(data: any): Observable<any> {
    return this.http.post(this.apiUrl, data);
  }

  update(uuid: string, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/${uuid}`, data);
  }

  toggle(uuid: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${uuid}/toggle`, {});
  }

  delete(uuid: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${uuid}`);
  }

  activate(uuid: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${uuid}/activate`, {});
    }

    deactivate(uuid: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${uuid}/deactivate`, {});
    }
}