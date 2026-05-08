import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { AlertService } from '../../services/alert.service';

@Component({
  selector: 'app-alert-container',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="alert-stack" aria-live="polite" aria-atomic="true">
      @for (alert of alertService.alerts(); track alert.id) {
        <button
          type="button"
          class="alert-toast"
          [class]="'alert-toast alert-toast--' + alert.type"
          (click)="alertService.dismiss(alert.id)"
        >
          <span class="alert-toast__message">{{ alert.message }}</span>
        </button>
      }
    </div>
  `,
  styles: [`
    .alert-stack {
      position: fixed;
      top: 1rem;
      right: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      z-index: 2100;
      width: min(360px, calc(100vw - 2rem));
      pointer-events: none;
    }

    .alert-toast {
      pointer-events: auto;
      width: 100%;
      border: 1px solid transparent;
      border-radius: 12px;
      padding: 0.875rem 1rem;
      text-align: left;
      color: #f8fafc;
      cursor: pointer;
      box-shadow: 0 14px 32px rgba(15, 23, 42, 0.22);
      backdrop-filter: blur(10px);
      transition: transform 0.18s ease, opacity 0.18s ease;
    }

    .alert-toast:hover {
      transform: translateY(-1px);
    }

    .alert-toast--success {
      background: rgba(22, 163, 74, 0.92);
      border-color: rgba(187, 247, 208, 0.4);
    }

    .alert-toast--error {
      background: rgba(220, 38, 38, 0.94);
      border-color: rgba(254, 202, 202, 0.45);
    }

    .alert-toast--info {
      background: rgba(37, 99, 235, 0.94);
      border-color: rgba(191, 219, 254, 0.45);
    }

    .alert-toast--warning {
      background: rgba(217, 119, 6, 0.94);
      border-color: rgba(253, 230, 138, 0.45);
    }

    .alert-toast__message {
      display: block;
      font-size: 0.95rem;
      font-weight: 600;
      line-height: 1.35;
    }
  `],
})
export class AlertContainerComponent {
  protected readonly alertService = inject(AlertService);
}
