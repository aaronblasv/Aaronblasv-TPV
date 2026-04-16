import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { ZoneService } from '../../../services/api/zone.service';
import { TableService } from '../../../services/api/table.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';
import { forkJoin } from 'rxjs';

@Component({
  selector: 'app-zones',
  templateUrl: './zones.page.html',
  styleUrls: ['./zones.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent]
})
export class ZonesPage implements OnInit {

  private zoneService = inject(ZoneService);
  private tableService = inject(TableService);

  zones: any[] = [];
  tables: any[] = [];
  showForm = false;
  showConfirm = false;
  editingZone: any = null;
  pendingDeleteUuid: string | null = null;
  confirmMessage = '';
  errors: { [key: string]: string } = {};

  form = { name: '' };

  ngOnInit() {
    this.loadData();
  }

  loadData() {
    forkJoin({
      zones: this.zoneService.getAll(),
      tables: this.tableService.getAll(),
    }).subscribe({
      next: ({ zones, tables }) => {
        this.zones = zones;
        this.tables = tables;
      },
      error: (err: any) => console.error(err)
    });
  }

  openForm(zone?: any) {
    this.editingZone = zone ?? null;
    this.form = { name: zone?.name ?? '' };
    this.showForm = true;
  }

  closeForm() {
    this.showForm = false;
    this.editingZone = null;
    this.errors = {};
  }

  save() {
    this.errors = {};
    const action = this.editingZone
      ? this.zoneService.update(this.editingZone.uuid, this.form.name)
      : this.zoneService.create(this.form.name);

    action.subscribe({
      next: () => { this.loadData(); this.closeForm(); },
      error: (err: any) => {
        if (err.status === 422) {
          Object.keys(err.error.errors).forEach(key => {
            this.errors[key] = err.error.errors[key][0];
          });
        }
      }
    });
  }

  requestDelete(zone: any) {
    const tableCount = this.tables.filter(t => t.zone_id === zone.uuid).length;
    this.pendingDeleteUuid = zone.uuid;
    this.confirmMessage = tableCount > 0
      ? `Se eliminará "${zone.name}" y sus mesas asociadas. Esta acción no se puede deshacer.`
      : `Se eliminará "${zone.name}". Esta acción no se puede deshacer.`;
    this.showConfirm = true;
  }

  confirmDelete() {
    if (!this.pendingDeleteUuid) return;
    this.zoneService.delete(this.pendingDeleteUuid).subscribe({
      next: () => { this.loadData(); this.closeConfirm(); },
      error: (err: any) => console.error(err)
    });
  }

  closeConfirm() {
    this.showConfirm = false;
    this.pendingDeleteUuid = null;
  }
}