import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/api/auth.service';
import { IonIcon } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { homeOutline, settingsOutline, closeOutline, receiptOutline, folderOutline, cubeOutline, locationOutline, gridOutline, peopleOutline, logOutOutline, restaurantOutline, menuOutline, documentTextOutline, cashOutline, barChartOutline, walletOutline } from 'ionicons/icons';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule, IonIcon]
})
export class SidebarComponent implements OnInit {

  collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
  isOpen = false;
  currentUser: any = null;
  canSwitchToTpv = false;

  menuItems = [
    { label: 'General', route: '/dashboard', icon: 'home-outline' },
    { label: 'Impuestos', route: '/taxes', icon: 'receipt-outline' },
    { label: 'Familias', route: '/families', icon: 'folder-outline' },
    { label: 'Productos', route: '/products', icon: 'cube-outline' },
    { label: 'Zonas', route: '/zones', icon: 'location-outline' },
    { label: 'Mesas', route: '/tables', icon: 'grid-outline' },
    { label: 'Usuarios', route: '/users', icon: 'people-outline' },
    { label: 'Ventas', route: '/sales', icon: 'cash-outline' },
    { label: 'Caja', route: '/cash-shifts', icon: 'wallet-outline' },
    { label: 'Informes', route: '/reports', icon: 'bar-chart-outline' },
    { label: 'Registro', route: '/logs', icon: 'document-text-outline' },
    { label: 'Ajustes', route: '/settings', icon: 'settings-outline' },
];

  constructor(
    private authService: AuthService,
    private router: Router,
) {
    addIcons({ homeOutline, settingsOutline, closeOutline, menuOutline, receiptOutline, folderOutline, cubeOutline, locationOutline, gridOutline, peopleOutline, logOutOutline, restaurantOutline, documentTextOutline, cashOutline, barChartOutline, walletOutline });
}

  ngOnInit() {
    const role = this.authService.getRole();
    this.canSwitchToTpv = role === 'admin' || role === 'supervisor';

    if (this.canSwitchToTpv && !this.menuItems.some(i => i.route === '/tpv')) {
      this.menuItems.splice(1, 0, { label: 'TPV', route: '/tpv', icon: 'restaurant-outline' });
    }

    this.authService.me().subscribe({
      next: (user) => this.currentUser = user,
      error: () => {}
    });  
  }

  logout() {
    this.releaseFocus();
    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/login']);
      },
      error: () => {
        localStorage.removeItem('token');
        this.router.navigate(['/login']);
      }
    });
  }

  onNavItemClick(event: Event) {
    (event.currentTarget as HTMLElement | null)?.blur();
    this.releaseFocus();
    this.toggleSidebar();
  }

  toggleSidebar() {
    this.isOpen = !this.isOpen;

    if (!this.isOpen) {
      this.releaseFocus();
    }
  }

  private releaseFocus() {
    const activeElement = document.activeElement;

    if (activeElement instanceof HTMLElement) {
      activeElement.blur();
    }
  }
}