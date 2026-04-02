import { Injectable, Injector } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from './base-api.service';

@Injectable({
  providedIn: 'root',
})
export class TaxService extends BaseApiService {

  constructor(injector: Injector) {
    super(injector);
  }

  getAll(): Observable<any> {
    return this.httpCall('/taxes', null, 'get');
  }

  create(name: string, percentage: number): Observable<any> {
    return this.httpCall('/taxes', { name, percentage }, 'post');
  }

  update(uuid: string, name: string, percentage: number): Observable<any> {
    return this.httpCall(`/taxes/${uuid}`, { name, percentage }, 'put');
  }

  delete(uuid: string): Observable<any> {
    return this.httpCall(`/taxes/${uuid}`, null, 'delete');
  }
}