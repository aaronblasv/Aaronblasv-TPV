import { Component, Input, Output, EventEmitter, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonicModule } from '@ionic/angular';
import { UserService } from '../../services/api/user.service';
import { LoggerService } from '../../services/logger.service';
import { User } from '../../types/user.model';

@Component({
  selector: 'app-waiter-modal',
  standalone: true,
  imports: [CommonModule, IonicModule],
  templateUrl: './waiter-modal.component.html',
  styleUrls: ['./waiter-modal.component.scss'],
})
export class WaiterModalComponent implements OnInit {
  @Input() visible = false;
  @Input() title = 'Seleccionar camarero';
  @Input() emptyMessage = 'No hay camareros disponibles';
  @Input() allowedRoles: string[] = ['staff', 'waiter'];
  @Output() onSelected = new EventEmitter<User>();
  @Output() onCancel = new EventEmitter<void>();

  private userService = inject(UserService);
  private logger = inject(LoggerService);

  waiters: User[] = [];

  getRoleLabel(role: string): string {
    const roles: Record<string, string> = {
      admin: 'Administrador',
      supervisor: 'Supervisor',
      waiter: 'Camarero',
      staff: 'Staff',
    };

    return roles[role] ?? role;
  }

  getInitials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) {
      return '?';
    }

    return parts.slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('');
  }

  ngOnInit() {
    this.loadWaiters();
  }

  loadWaiters() {
    this.userService.getAllTpv().subscribe({
      next: (users) => {
        this.waiters = users.filter(user => this.allowedRoles.includes(user.role));
      },
      error: (err) => this.logger.error('Error loading waiters:', err),
    });
  }

  selectWaiter(waiter: User) {
    this.onSelected.emit(waiter);
  }

  onImageError(waiter: User) {
    waiter.image_src = null;
  }

  cancel() {
    this.onCancel.emit();
  }
}
