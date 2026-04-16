import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/api/auth.service';
import { IonIcon } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { homeOutline, settingsOutline, closeOutline, receiptOutline, folderOutline, cubeOutline, locationOutline, gridOutline, peopleOutline, logOutOutline, restaurantOutline, menuOutline, documentTextOutline, cashOutline, barChartOutline } from 'ionicons/icons';

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

  menuItems = [
    { label: 'General', route: '/dashboard', icon: 'home-outline' },
    { label: 'Impuestos', route: '/taxes', icon: 'receipt-outline' },
    { label: 'Familias', route: '/families', icon: 'folder-outline' },
    { label: 'Productos', route: '/products', icon: 'cube-outline' },
    { label: 'Zonas', route: '/zones', icon: 'location-outline' },
    { label: 'Mesas', route: '/tables', icon: 'grid-outline' },
    { label: 'Usuarios', route: '/users', icon: 'people-outline' },
    { label: 'Ventas', route: '/sales', icon: 'cash-outline' },
    { label: 'Informes', route: '/reports', icon: 'bar-chart-outline' },
    { label: 'Registro', route: '/logs', icon: 'document-text-outline' },
    { label: 'Ajustes', route: '/settings', icon: 'settings-outline' },
];

  constructor(
    private authService: AuthService,
    private router: Router,
) {
    addIcons({ homeOutline, settingsOutline, closeOutline, menuOutline, receiptOutline, folderOutline, cubeOutline, locationOutline, gridOutline, peopleOutline, logOutOutline, restaurantOutline, documentTextOutline, cashOutline, barChartOutline });
}

  ngOnInit() {
    this.authService.me().subscribe({
      next: (user) => this.currentUser = user,
      error: () => {}
    });  
  }

  logout() {
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

  toggleSidebar() {
    this.isOpen = !this.isOpen;
  }
}