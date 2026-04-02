import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/api/auth.service';
import { IonIcon } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { homeOutline, receiptOutline, folderOutline, cubeOutline, locationOutline, gridOutline, peopleOutline, logOutOutline, restaurantOutline } from 'ionicons/icons';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule, IonIcon]
})
export class SidebarComponent implements OnInit {

  menuItems = [
    { label: 'Dashboard', route: '/dashboard', icon: 'home-outline' },
    { label: 'Impuestos', route: '/taxes', icon: 'receipt-outline' },
    { label: 'Familias', route: '/families', icon: 'folder-outline' },
    { label: 'Productos', route: '/products', icon: 'cube-outline' },
    { label: 'Zonas', route: '/zones', icon: 'location-outline' },
    { label: 'Mesas', route: '/tables', icon: 'grid-outline' },
    { label: 'Usuarios', route: '/users', icon: 'people-outline' },
];

  constructor(
    private authService: AuthService,
    private router: Router,
) {
    addIcons({ homeOutline, receiptOutline, folderOutline, cubeOutline, locationOutline, gridOutline, peopleOutline, logOutOutline, restaurantOutline });
}

  ngOnInit() {}

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
}