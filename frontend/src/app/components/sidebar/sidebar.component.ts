import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/api/auth.service';
import { BackofficeSessionService } from '../../services/backoffice-session.service';
import { IonIcon } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { homeOutline, settingsOutline, receiptOutline, folderOutline, cubeOutline, locationOutline, gridOutline, peopleOutline, logOutOutline, restaurantOutline, documentTextOutline, barChartOutline, chevronDownOutline, arrowForwardOutline } from 'ionicons/icons';

type SidebarItem = {
  label: string;
  route: string;
  icon: string;
  meta?: string;
};

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule, IonIcon]
})
export class SidebarComponent implements OnInit {
  currentUser: any = null;
  canSwitchToTpv = false;
  isTpvMenuOpen = false;

  menuItems: SidebarItem[] = [
    { label: 'General', route: '/dashboard', icon: 'home-outline', meta: 'Resumen' },
    { label: 'Informes', route: '/reports', icon: 'bar-chart-outline', meta: 'Ventas' },
    { label: 'Registro', route: '/logs', icon: 'document-text-outline', meta: 'Actividad' },
    { label: 'Ajustes', route: '/settings', icon: 'settings-outline', meta: 'Sistema' },
  ];

  tpvItems: SidebarItem[] = [
    { label: 'Impuestos', route: '/taxes', icon: 'receipt-outline', meta: 'CRUD' },
    { label: 'Familias', route: '/families', icon: 'folder-outline', meta: 'CRUD' },
    { label: 'Productos', route: '/products', icon: 'cube-outline', meta: 'CRUD' },
    { label: 'Zonas', route: '/zones', icon: 'location-outline', meta: 'CRUD' },
    { label: 'Mesas', route: '/tables', icon: 'grid-outline', meta: 'CRUD' },
    { label: 'Usuarios', route: '/users', icon: 'people-outline', meta: 'CRUD' },
  ];

  constructor(
    private authService: AuthService,
    private backofficeSessionService: BackofficeSessionService,
    private router: Router,
) {
  addIcons({ homeOutline, settingsOutline, receiptOutline, folderOutline, cubeOutline, locationOutline, gridOutline, peopleOutline, logOutOutline, restaurantOutline, documentTextOutline, barChartOutline, chevronDownOutline, arrowForwardOutline });
}

  ngOnInit() {
    this.isTpvMenuOpen = this.isTpvRouteActive();

    this.authService.me().subscribe({
      next: (user) => {
        this.currentUser = user;
        this.canSwitchToTpv = user?.role === 'admin' || user?.role === 'supervisor';
      },
      error: () => {}
    });
  }

  logout() {
    this.releaseFocus();
    this.authService.logout().subscribe({
      next: () => {
        this.backofficeSessionService.clearActingUser();
        this.router.navigate(['/login']);
      },
      error: () => {
        this.backofficeSessionService.clearActingUser();
        this.router.navigate(['/login']);
      }
    });
  }

  goToTpv(event: Event) {
    event.preventDefault();
    this.onNavItemClick(event);
    this.backofficeSessionService.clearActingUser();
    this.router.navigate(['/tpv']);
  }

  onNavItemClick(event: Event) {
    (event.currentTarget as HTMLElement | null)?.blur();
    this.releaseFocus();
  }

  toggleTpvMenu(event: Event) {
    event.preventDefault();
    this.isTpvMenuOpen = !this.isTpvMenuOpen;
    this.onNavItemClick(event);
  }

  isRouteActive(route: string) {
    return this.router.url === route;
  }

  isTpvRouteActive() {
    return this.tpvItems.some((item) => this.router.url === item.route);
  }

  private releaseFocus() {
    const activeElement = document.activeElement;

    if (activeElement instanceof HTMLElement) {
      activeElement.blur();
    }
  }
}