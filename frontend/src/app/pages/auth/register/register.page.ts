import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { AuthService } from '../../../services/api/auth.service';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-register',
  templateUrl: './register.page.html',
  styleUrls: ['./register.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, RouterModule ]
})
export class RegisterPage {

  name: string = '';
  email: string = '';
  password: string = '';
  password_confirmation: string = '';
  error: string = '';
  loading: boolean = false;

  constructor(
    private authService: AuthService,
    private router: Router,
  ) {}

  register() {
    this.error = '';
    this.loading = true;

    this.authService.register(this.name, this.email, this.password, this.password_confirmation).subscribe({
      next: () => {
        this.router.navigate(['/login']);
      },
      error: () => {
        this.error = 'Error al registrar el usuario';
        this.loading = false;
      }
    });
  }
}