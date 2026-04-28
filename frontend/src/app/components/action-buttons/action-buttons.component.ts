import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonIcon } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { createOutline, trashOutline, pauseCircleOutline, playCircleOutline } from 'ionicons/icons';

@Component({
  selector: 'app-action-buttons',
  templateUrl: './action-buttons.component.html',
  styleUrls: ['./action-buttons.component.scss'],
  standalone: true,
  imports: [CommonModule, IonIcon]
})
export class ActionButtonsComponent {
  @Input() showEdit: boolean = true;
  @Input() showDelete: boolean = true;
  @Input() showToggle: boolean = false;
  @Input() isActive: boolean = true;

  @Output() onEdit = new EventEmitter<void>();
  @Output() onDelete = new EventEmitter<void>();
  @Output() onToggle = new EventEmitter<void>();

  constructor() {
    addIcons({ createOutline, trashOutline, pauseCircleOutline, playCircleOutline });
  }
}