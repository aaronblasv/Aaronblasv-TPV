import { bootstrapApplication } from '@angular/platform-browser';
import { provideHttpClient, withInterceptorsFromDi, withFetch, HTTP_INTERCEPTORS } from '@angular/common/http';
import { RouteReuseStrategy, provideRouter, withPreloading, PreloadAllModules } from '@angular/router';
import { IonicRouteStrategy, provideIonicAngular } from '@ionic/angular/standalone';
import { LOCALE_ID } from '@angular/core';
import { registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';

import { routes } from './app/app.routes';
import { AppComponent } from './app/app.component';
import { CredentialsInterceptor } from './app/providers/credentials.interceptor';
import { InterceptorProvider } from './app/providers/interceptor';

registerLocaleData(localeEs);

bootstrapApplication(AppComponent, {
  providers: [
    { provide: RouteReuseStrategy, useClass: IonicRouteStrategy },
    { provide: HTTP_INTERCEPTORS, useClass: CredentialsInterceptor, multi: true },
    { provide: HTTP_INTERCEPTORS, useClass: InterceptorProvider, multi: true },
    { provide: LOCALE_ID, useValue: 'es' },
    provideIonicAngular({ mode: 'ios', animated: false }),
    provideHttpClient(withInterceptorsFromDi(), withFetch()),
    provideRouter(routes, withPreloading(PreloadAllModules)),
  ],
});