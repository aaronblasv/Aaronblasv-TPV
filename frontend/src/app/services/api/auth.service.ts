import { Injectable, Injector } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { BaseApiService } from './base-api.service';

@Injectable({
  providedIn: 'root',
})
export class AuthService extends BaseApiService {

  constructor(injector: Injector) {
    super(injector);
  }

  login(email: string, password: string): Observable<any> {
    return this.httpCall('/auth/login', { email, password }, 'post').pipe(
      tap((response: any) => {
        if (response && (response as any).token) {
          localStorage.setItem('token', (response as any).token);
        }
      })
    );
  }

  logout(): Observable<any> {
    return this.httpCall('/auth/logout', null, 'post').pipe(
      tap(() => {
        localStorage.removeItem('token');
      })
    );
  }

  isAuthenticated(): boolean {
    return !!localStorage.getItem('token');
  }

  register(name: string, email: string, password: string, passwordConfirmation: string): Observable<any> {
    return this.httpCall('/users', { name, email, password, password_confirmation: passwordConfirmation }, 'post');
    }
}