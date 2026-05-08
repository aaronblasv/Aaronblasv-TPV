import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { AuthService } from '../../../services/api/auth.service';
import { AuthenticatedUser } from '../../../types/user.model';

@Component({
  selector: 'app-settings',
  templateUrl: './settings.page.html',
  styleUrls: ['./settings.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent]
})
export class SettingsPage implements OnInit {

  private authService = inject(AuthService);

  currentUser: AuthenticatedUser | null = null;
  uiScale = 100;

  ngOnInit() {
    this.authService.me().subscribe({
      next: (user: AuthenticatedUser) => this.currentUser = user,
      error: () => {}
    });

    const savedScale = localStorage.getItem('ui-scale');
    if (savedScale) {
      this.uiScale = parseInt(savedScale);
      this.applyScale(this.uiScale);
    }
  }

  changeScale(value: number) {
    this.uiScale = value;
    this.applyScale(value);
    localStorage.setItem('ui-scale', String(value));
  }

  applyScale(value: number) {
    document.documentElement.style.fontSize = `${value}%`;
  }

  getRoleLabel(): string {
    const roles: { [key: string]: string } = {
      admin: 'Administrador',
      supervisor: 'Supervisor',
      waiter: 'Camarero',
    };

    if (!this.currentUser?.role) {
      return 'Sin rol';
    }

    return roles[this.currentUser.role] ?? this.currentUser.role;
  }
}