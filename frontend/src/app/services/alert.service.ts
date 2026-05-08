import { Injectable, signal } from '@angular/core';

export type AlertType = 'success' | 'error' | 'info' | 'warning';

export interface Alert {
  id: number;
  type: AlertType;
  message: string;
}

@Injectable({
  providedIn: 'root',
})
export class AlertService {
  private nextId = 1;
  readonly alerts = signal<Alert[]>([]);

  show(type: AlertType, message: string, durationMs = 3500): void {
    const id = this.nextId++;

    this.alerts.update((currentAlerts) => [...currentAlerts, { id, type, message }]);

    setTimeout(() => this.dismiss(id), durationMs);
  }

  success(message: string, durationMs?: number): void {
    this.show('success', message, durationMs);
  }

  error(message: string, durationMs?: number): void {
    this.show('error', message, durationMs);
  }

  info(message: string, durationMs?: number): void {
    this.show('info', message, durationMs);
  }

  warning(message: string, durationMs?: number): void {
    this.show('warning', message, durationMs);
  }

  dismiss(id: number): void {
    this.alerts.update((currentAlerts) => currentAlerts.filter((alert) => alert.id !== id));
  }
}
