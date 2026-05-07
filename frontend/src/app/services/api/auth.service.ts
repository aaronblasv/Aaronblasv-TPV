import { Injectable, Injector, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, map, of, switchMap, tap } from 'rxjs';
import { BaseApiService } from './base-api.service';
import { BackofficeSessionService } from '../backoffice-session.service';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class AuthService extends BaseApiService {
  private backofficeSessionService: BackofficeSessionService;
  private rawHttp: HttpClient;
  private readonly authenticatedRoleSignal = signal<string | null>(null);

  constructor(injector: Injector) {
    super(injector);
    this.backofficeSessionService = injector.get(BackofficeSessionService);
    this.rawHttp = injector.get(HttpClient);
  }

  private csrfCookie(): Observable<void> {
    const csrfUrl = environment.apiUrl.replace(/\/api$/, '') + '/sanctum/csrf-cookie';

    return this.rawHttp.get<void>(csrfUrl, { withCredentials: true });
  }

  login(email: string, password: string): Observable<any> {
    return this.csrfCookie().pipe(
      switchMap(() => this.httpCall('/auth/login', { email, password }, 'post')),
      tap((response: any) => {
        this.backofficeSessionService.clearActingUser();
        if (response?.role) {
          this.authenticatedRoleSignal.set(response.role);
        }
      })
    );
  }

  getRole(): string | null {
    return this.backofficeSessionService.getEffectiveRole() ?? this.authenticatedRoleSignal();
  }

  getAuthenticatedRole(): string | null {
    return this.authenticatedRoleSignal();
  }

  logout(): Observable<any> {
    return this.httpCall('/auth/logout', null, 'post').pipe(
      tap(() => {
        this.backofficeSessionService.clearActingUser();
        this.authenticatedRoleSignal.set(null);
      })
    );
  }

  me(): Observable<any> {
    return this.httpCall('/auth/me', null, 'get').pipe(
      tap((user: any) => {
        if (user?.role) {
          this.authenticatedRoleSignal.set(user.role);
        }
      })
    );
  }

  isAuthenticated(): Observable<boolean> {
    return this.me().pipe(
      map(() => true),
      catchError(() => of(false))
    );
  }

  register(name: string, email: string, password: string, passwordConfirmation: string): Observable<any> {
    return this.httpCall('/users', { name, email, password, password_confirmation: passwordConfirmation }, 'post');
  }
}