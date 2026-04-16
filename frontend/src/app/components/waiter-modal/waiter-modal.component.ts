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
  @Output() onSelected = new EventEmitter<User>();
  @Output() onCancel = new EventEmitter<void>();

  private userService = inject(UserService);
  private logger = inject(LoggerService);

  waiters: User[] = [];

  ngOnInit() {
    this.loadWaiters();
  }

  loadWaiters() {
    this.userService.getAllTpv().subscribe({
      next: (users) => {
        this.waiters = users.filter(u => u.role === 'staff' || u.role === 'waiter');
      },
      error: (err) => this.logger.error('Error loading waiters:', err),
    });
  }

  selectWaiter(waiter: User) {
    this.onSelected.emit(waiter);
  }

  cancel() {
    this.onCancel.emit();
  }
}
