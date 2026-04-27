import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges } from '@angular/core';

@Component({
  selector: 'app-void-line-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './void-line-modal.component.html',
  styleUrls: ['./void-line-modal.component.scss'],
})
export class VoidLineModalComponent implements OnChanges {
  @Input() visible = false;
  @Input() productName = 'Producto';
  @Input() maxQuantity = 1;
  @Output() onConfirm = new EventEmitter<number>();
  @Output() onCancel = new EventEmitter<void>();

  quantity = 1;
  error = '';

  ngOnChanges(changes: SimpleChanges): void {
    if ((changes['visible'] && this.visible) || changes['maxQuantity']) {
      this.quantity = 1;
      this.error = '';
    }
  }

  updateQuantity(rawValue: string | number): void {
    const parsedValue = Number.parseInt(String(rawValue), 10);

    if (!Number.isInteger(parsedValue)) {
      this.quantity = 1;
      this.error = '';
      return;
    }

    this.quantity = Math.min(Math.max(parsedValue, 1), this.maxQuantity);
    this.error = '';
  }

  confirm(): void {
    if (!Number.isInteger(this.quantity) || this.quantity < 1 || this.quantity > this.maxQuantity) {
      this.error = `Introduce una cantidad válida entre 1 y ${this.maxQuantity}.`;
      return;
    }

    this.onConfirm.emit(this.quantity);
  }

  cancel(): void {
    this.error = '';
    this.quantity = 1;
    this.onCancel.emit();
  }
}
