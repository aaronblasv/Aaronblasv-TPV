import { Routes } from '@angular/router';
import { authGuard } from './providers/auth.guard';

export const routes: Routes = [
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full',
  },
  {
    path: 'login',
    loadComponent: () => import('./pages/auth/login/login.page').then(m => m.LoginPage),
  },
  {
    path: 'dashboard',
    loadComponent: () => import('./pages/backoffice/dashboard/dashboard.page').then(m => m.DashboardPage),
    canActivate: [authGuard],
  },
  {
    path: 'taxes',
    loadComponent: () => import('./pages/backoffice/taxes/taxes.page').then(m => m.TaxesPage),
    canActivate: [authGuard],
  },
  {
    path: 'families',
    loadComponent: () => import('./pages/backoffice/families/families.page').then(m => m.FamiliesPage),
    canActivate: [authGuard],
  },
  {
    path: 'products',
    loadComponent: () => import('./pages/backoffice/products/products.page').then(m => m.ProductsPage),
    canActivate: [authGuard],
  },
  {
    path: 'zones',
    loadComponent: () => import('./pages/backoffice/zones/zones.page').then(m => m.ZonesPage),
    canActivate: [authGuard],
  },
  {
    path: 'tables',
    loadComponent: () => import('./pages/backoffice/tables/tables.page').then(m => m.TablesPage),
    canActivate: [authGuard],
  },
  {
    path: 'users',
    loadComponent: () => import('./pages/backoffice/users/users.page').then(m => m.UsersPage),
    canActivate: [authGuard],
  },
];