import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Product, ProductFormData } from '../../types/product.model';

@Injectable({
  providedIn: 'root'
})
export class ProductService {
  private apiUrl = `${environment.apiUrl}/products`;

  constructor(private http: HttpClient) {}

  getAll(): Observable<Product[]> {
    return this.http.get<Product[]>(this.apiUrl);
  }

  getAllTpv(): Observable<Product[]> {
    return this.http.get<Product[]>(`${environment.apiUrl}/tpv/products`);
  }

  create(data: ProductFormData): Observable<Product> {
    return this.http.post<Product>(this.apiUrl, data);
  }

  update(uuid: string, data: ProductFormData): Observable<Product> {
    return this.http.put<Product>(`${this.apiUrl}/${uuid}`, data);
  }

  activate(uuid: string): Observable<Product> {
    return this.http.patch<Product>(`${this.apiUrl}/${uuid}/activate`, {});
  }

  deactivate(uuid: string): Observable<Product> {
    return this.http.patch<Product>(`${this.apiUrl}/${uuid}/deactivate`, {});
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }
}
