import { CommonModule } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { TpvSidebarComponent } from '../../../components/tpv-sidebar/tpv-sidebar.component';
import { CashShiftService } from '../../../services/api/cash-shift.service';
import { TpvSessionService } from '../../../services/tpv-session.service';
import { CashShiftSummary, ClosedCashShiftSummary } from '../../../types/cash-shift.model';

@Component({
  selector: 'app-cash',
  standalone: true,
  imports: [CommonModule, FormsModule, IonicModule, TpvSidebarComponent],
  templateUrl: './cash.page.html',
  styleUrls: ['./cash.page.scss'],
})
export class CashPage implements OnInit {
  private router = inject(Router);
  private cashShiftService = inject(CashShiftService);
  private tpvSessionService = inject(TpvSessionService);

  currentShift: CashShiftSummary | null = null;
  lastClosedShift: ClosedCashShiftSummary | null = null;
  loading = false;
  submitting = false;
  feedback: { type: 'success' | 'error'; message: string } | null = null;
  openingCash = 0;
  openingNotes = '';
  countedCash = 0;
  closingNotes = '';

  ngOnInit(): void {
    if (!this.ensureActiveSession()) {
      return;
    }

    this.loadCurrentShift();
  }

  ionViewWillEnter(): void {
    if (!this.ensureActiveSession()) {
      return;
    }

    this.loadCurrentShift();
  }

  loadCurrentShift(): void {
    this.loading = true;
    this.feedback = null;
    this.cashShiftService.getCurrent().subscribe({
      next: (shift) => {
        this.currentShift = shift;
        this.countedCash = shift?.expected_cash ?? 0;
        this.loading = false;
      },
      error: () => {
        this.currentShift = null;
        this.feedback = { type: 'error', message: 'No se pudo cargar el estado de caja.' };
        this.loading = false;
      },
    });
  }

  openShift(): void {
    this.submitting = true;
    this.feedback = null;

    this.cashShiftService.open(this.openingCash, this.openingNotes || undefined).subscribe({
      next: (shift) => {
        this.currentShift = shift;
        this.countedCash = shift.expected_cash;
        this.openingCash = 0;
        this.openingNotes = '';
        this.feedback = { type: 'success', message: 'Caja abierta correctamente.' };
        this.submitting = false;
      },
      error: (error) => {
        this.feedback = { type: 'error', message: error?.error?.message ?? 'No se pudo abrir la caja.' };
        this.submitting = false;
      },
    });
  }

  closeShift(): void {
    if (!this.currentShift || this.submitting) {
      return;
    }

    this.submitting = true;
    this.feedback = null;

    this.cashShiftService.close(this.currentShift.uuid, this.countedCash, this.closingNotes || undefined).subscribe({
      next: (shift) => {
        this.lastClosedShift = shift;
        this.currentShift = null;
        this.countedCash = 0;
        this.closingNotes = '';
        this.feedback = { type: 'success', message: 'Caja cerrada correctamente.' };
        this.submitting = false;
      },
      error: (error) => {
        this.feedback = { type: 'error', message: error?.error?.message ?? 'No se pudo cerrar la caja.' };
        this.submitting = false;
      },
    });
  }

  logoutTpvSession(): void {
    this.tpvSessionService.clear();
    this.router.navigate(['/tpv']);
  }

  formatCurrency(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
  }

  formatDate(value: string): string {
    return new Date(value).toLocaleString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  private ensureActiveSession(): boolean {
    if (this.tpvSessionService.getUser()) {
      return true;
    }

    this.router.navigate(['/tpv']);
    return false;
  }
}
