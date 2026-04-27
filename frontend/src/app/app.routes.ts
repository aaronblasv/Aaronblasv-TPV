import { Routes } from '@angular/router';
import { authGuard } from './providers/auth.guard';
import { tpvGuard } from './providers/tpv.guard';

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
  {
    path: 'sales',
    redirectTo: 'reports',
    pathMatch: 'full',
  },
  {
    path: 'reports',
    loadComponent: () => import('./pages/backoffice/reports/reports.page').then(m => m.ReportsPage),
    canActivate: [authGuard],
  },
  {
    path: 'logs',
    loadComponent: () => import('./pages/backoffice/logs/logs.page').then(m => m.LogsPage),
    canActivate: [authGuard],
  },
  {
    path: 'no-access',
    loadComponent: () => import('./pages/no-access/no-access.page').then(m => m.NoAccessPage),
  },
  {
  path: 'settings',
  loadComponent: () => import('./pages/backoffice/settings/settings.page').then(m => m.SettingsPage),
  canActivate: [authGuard],
  },
  {
    path: 'cash-shifts',
    loadComponent: () => import('./pages/backoffice/cash-shifts/cash-shifts.page').then(m => m.CashShiftsPage),
    canActivate: [authGuard],
  },
  {
    path: 'tpv',
    loadComponent: () => import('./pages/tpv/floor/floor.page').then(m => m.FloorPage),
    canActivate: [tpvGuard],
  },
  {
    path: 'tpv/order/:tableUuid',
    loadComponent: () => import('./pages/tpv/order/order.page').then(m => m.OrderPage),
    canActivate: [tpvGuard],
  },
];