import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpEvent, HttpHandler, HttpInterceptor, HttpRequest } from '@angular/common/http';
import { BackofficeSessionService } from '../services/backoffice-session.service';

@Injectable()
export class InterceptorProvider implements HttpInterceptor {
  constructor(private backofficeSessionService: BackofficeSessionService) {}

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return next.handle(this.setHeaders(request));
  }

  private setHeaders(request: HttpRequest<any>): HttpRequest<any> {
    const actingUserUuid = this.backofficeSessionService.getActingUserUuid();
    const xsrfToken = this.readXsrfToken();

    const headers: Record<string, string> = {
      Accept: 'application/json',
      'Accept-Language': 'es',
    };

    if (xsrfToken) {
      headers['X-XSRF-TOKEN'] = xsrfToken;
    }

    if (actingUserUuid) {
      headers['X-Backoffice-User-Uuid'] = actingUserUuid;
    }

    return request.clone({ setHeaders: headers });
  }

  private readXsrfToken(): string | null {
    const match = document.cookie.match(/(^|;)\s*XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[2]) : null;
  }
}