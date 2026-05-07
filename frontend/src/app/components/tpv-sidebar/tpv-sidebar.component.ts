import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, OnDestroy, OnInit, Output, inject } from '@angular/core';
import { Router } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { cashOutline, grid, logOutOutline, person, receiptOutline, statsChartOutline } from 'ionicons/icons';
import { AuthService } from '../../services/api/auth.service';
import { BackofficeSessionService } from '../../services/backoffice-session.service';
import { TpvSessionService } from '../../services/tpv-session.service';
import { User } from '../../types/user.model';
import { PinModalComponent } from '../pin-modal/pin-modal.component';
import { ProfileModalComponent } from '../profile-modal/profile-modal.component';
import { WaiterModalComponent } from '../waiter-modal/waiter-modal.component';

@Component({
  selector: 'app-tpv-sidebar',
  standalone: true,
  imports: [CommonModule, IonicModule, WaiterModalComponent, PinModalComponent, ProfileModalComponent],
  templateUrl: './tpv-sidebar.component.html',
  styleUrls: ['./tpv-sidebar.component.scss'],
})
export class TpvSidebarComponent implements OnInit, OnDestroy {
  @Input() activeSection: 'tables' | 'tickets' | 'cash' = 'tables';
  @Output() logout = new EventEmitter<void>();

  private router = inject(Router);
  private authService = inject(AuthService);
  private backofficeSessionService = inject(BackofficeSessionService);
  private tpvSessionService = inject(TpvSessionService);

  canGoBackoffice = false;
  showProfileWaiterModal = false;
  showProfilePinModal = false;
  showProfileModal = false;
  showBackofficeSupervisorModal = false;
  showBackofficePinModal = false;
  profileUser: User | null = null;
  profileWaiter: User | null = null;
  selectedBackofficeSupervisor: User | null = null;
  restaurantName = '';
  feedbackMessage = '';
  readonly personIcon = person;
  readonly tablesIcon = grid;
  readonly ticketsIcon = receiptOutline;
  readonly cashIcon = cashOutline;
  readonly backofficeIcon = statsChartOutline;
  readonly logOutIcon = logOutOutline;
  private feedbackTimeout: ReturnType<typeof setTimeout> | null = null;

  ngOnInit(): void {
    this.syncRoleFlags();
  }

  ngOnDestroy(): void {
    if (this.feedbackTimeout) {
      clearTimeout(this.feedbackTimeout);
    }
  }

  openProfile(): void {
    this.clearFeedback();
    this.showProfileWaiterModal = true;
  }

  navigateToTables(): void {
    this.clearFeedback();
    this.router.navigate(['/tpv']);
  }

  navigateToTickets(): void {
    this.clearFeedback();
    this.router.navigate(['/tpv/tickets']);
  }

  navigateToCash(): void {
    this.clearFeedback();
    this.router.navigate(['/tpv/cash']);
  }

  openBackoffice(): void {
    if (!this.canGoBackoffice) {
      return;
    }

    this.clearFeedback();
    this.selectedBackofficeSupervisor = null;
    this.showBackofficeSupervisorModal = true;
  }

  requestLogout(): void {
    this.clearFeedback();
    this.logout.emit();
  }

  onProfileWaiterSelected(waiter: User): void {
    this.profileWaiter = waiter;
    this.showProfileWaiterModal = false;
    this.showProfilePinModal = true;
  }

  onProfileWaiterCancelled(): void {
    this.showProfileWaiterModal = false;
    this.profileWaiter = null;
  }

  onProfilePinValidated(user: User): void {
    this.profileUser = user;
    this.showProfilePinModal = false;
    this.authService.me().subscribe({
      next: (me: { restaurant_name?: string }) => {
        this.restaurantName = me.restaurant_name ?? '';
      },
      error: () => {
        this.restaurantName = '';
      },
    });
    this.showProfileModal = true;
  }

  onProfilePinCancelled(): void {
    this.showProfilePinModal = false;
    this.profileWaiter = null;
  }

  onProfileClosed(): void {
    this.showProfileModal = false;
    this.profileUser = null;
    this.profileWaiter = null;
  }

  onBackofficeSupervisorSelected(user: User): void {
    this.selectedBackofficeSupervisor = user;
    this.showBackofficeSupervisorModal = false;
    this.showBackofficePinModal = true;
  }

  onBackofficeSupervisorCancelled(): void {
    this.showBackofficeSupervisorModal = false;
    this.selectedBackofficeSupervisor = null;
  }

  onBackofficePinValidated(user: User): void {
    this.showBackofficePinModal = false;
    this.selectedBackofficeSupervisor = null;

    if (user.role !== 'admin' && user.role !== 'supervisor') {
      this.showFeedback('Solo un administrador o supervisor puede acceder al backoffice.');
      return;
    }

    this.backofficeSessionService.setActingUser(user);
    this.tpvSessionService.clear();
    this.router.navigate(['/dashboard']);
  }

  onBackofficePinCancelled(): void {
    this.showBackofficePinModal = false;
    this.selectedBackofficeSupervisor = null;
  }

  private syncRoleFlags(): void {
    this.authService.me().subscribe({
      next: (user: { role?: string }) => {
        this.canGoBackoffice = user?.role === 'admin' || user?.role === 'supervisor';
      },
      error: () => {
        this.canGoBackoffice = false;
      },
    });
  }

  private showFeedback(message: string): void {
    if (this.feedbackTimeout) {
      clearTimeout(this.feedbackTimeout);
    }

    this.feedbackMessage = message;
    this.feedbackTimeout = setTimeout(() => {
      this.feedbackMessage = '';
      this.feedbackTimeout = null;
    }, 2400);
  }

  private clearFeedback(): void {
    if (this.feedbackTimeout) {
      clearTimeout(this.feedbackTimeout);
      this.feedbackTimeout = null;
    }

    this.feedbackMessage = '';
  }
}
