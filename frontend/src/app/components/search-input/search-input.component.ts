import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-search-input',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <label class="search-input">
      <span class="search-input__icon" aria-hidden="true">⌕</span>
      <input
        type="text"
        [(ngModel)]="value"
        (ngModelChange)="valueChange.emit($event)"
        [placeholder]="placeholder"
      />
      @if (value) {
        <button type="button" class="search-input__clear" (click)="clear()">×</button>
      }
    </label>
  `,
  styles: [`
    .search-input {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      width: min(100%, 360px);
      padding: 0.75rem 0.875rem;
      border: 1px solid var(--bo-border, #2D3B52);
      border-radius: 12px;
      background: rgba(15, 23, 42, 0.7);
      color: var(--bo-text-1, #F1F5F9);
      box-sizing: border-box;
    }

    .search-input__icon {
      color: var(--bo-text-2, #94A3B8);
      font-size: 1rem;
      line-height: 1;
    }

    .search-input input {
      flex: 1;
      border: none;
      outline: none;
      background: transparent;
      color: inherit;
      font-size: 0.95rem;
      min-width: 0;
    }

    .search-input input::placeholder {
      color: var(--bo-text-3, #64748B);
    }

    .search-input__clear {
      border: none;
      background: transparent;
      color: var(--bo-text-2, #94A3B8);
      cursor: pointer;
      font-size: 1.1rem;
      line-height: 1;
      padding: 0;
    }
  `],
})
export class SearchInputComponent {
  @Input() value = '';
  @Input() placeholder = 'Buscar...';
  @Output() valueChange = new EventEmitter<string>();

  clear(): void {
    this.value = '';
    this.valueChange.emit('');
  }
}
