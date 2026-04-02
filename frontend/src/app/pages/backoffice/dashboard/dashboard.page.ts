import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.page.html',
  styleUrls: ['./dashboard.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, SidebarComponent]
})
export class DashboardPage {

  stats = [
    { label: 'Total Productos', value: '0', icon: '📦' },
    { label: 'Familias', value: '0', icon: '📁' },
    { label: 'Impuestos activos', value: '0', icon: '📊' },
    { label: 'Usuarios', value: '0', icon: '👥' },
  ];
}