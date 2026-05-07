import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonContent } from '@ionic/angular/standalone';
import { Router } from '@angular/router';
import { AuthService } from '../../services/api/auth.service';

@Component({
  selector: 'app-no-access',
  templateUrl: './no-access.page.html',
  standalone: true,
  imports: [IonContent, CommonModule],
    styleUrls: ['./no-access.page.scss']
})
export class NoAccessPage {
  constructor(private authService: AuthService, private router: Router) {}

  logout() {
    this.authService.logout().subscribe({
      next: () => this.router.navigate(['/login']),
      error: () => this.router.navigate(['/login'])
    });
  }
}