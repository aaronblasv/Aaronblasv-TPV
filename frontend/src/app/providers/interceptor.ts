import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpEvent, HttpHandler, HttpInterceptor, HttpRequest } from '@angular/common/http';
import { BackofficeSessionService } from '../services/backoffice-session.service';

@Injectable()
export class InterceptorProvider implements HttpInterceptor {
  constructor(private backofficeSessionService: BackofficeSessionService) {}

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return next.handle(this.setHeader(request));
  }

  private setHeader(request: HttpRequest<any>): HttpRequest<any> {
    const token = localStorage.getItem('token');
    const actingUserUuid = this.backofficeSessionService.getActingUserUuid();

    const headers: any = {
      Accept: 'application/json',
      'Accept-Language': 'es',
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    if (actingUserUuid) {
      headers['X-Backoffice-User-Uuid'] = actingUserUuid;
    }

    return request.clone({ setHeaders: headers });
  }
}