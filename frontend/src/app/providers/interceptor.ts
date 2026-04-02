import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpEvent, HttpHandler, HttpInterceptor, HttpRequest } from '@angular/common/http';

@Injectable()
export class InterceptorProvider implements HttpInterceptor {

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return next.handle(this.setHeader(request));
  }

  private setHeader(request: HttpRequest<any>): HttpRequest<any> {
    const token = localStorage.getItem('token');

    const headers: any = {
      Accept: 'application/json',
      'Accept-Language': 'es',
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    return request.clone({ setHeaders: headers });
  }
}