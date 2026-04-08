import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-form-modal',
  templateUrl: './form-modal.component.html',
  styleUrls: ['./form-modal.component.scss'],
  standalone: true,
  imports: [CommonModule]
})
export class FormModalComponent {
  @Input() visible: boolean = false;
  @Input() title: string = '';
  @Input() errors: { [key: string]: string } = {};

  @Output() onSave = new EventEmitter<void>();
  @Output() onCancel = new EventEmitter<void>();
}