import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { TableService } from '../../../services/api/table.service';
import { ZoneService } from '../../../services/api/zone.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { forkJoin } from 'rxjs';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';

@Component({
  selector: 'app-tables',
  templateUrl: './tables.page.html',
  styleUrls: ['./tables.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent]
})
export class TablesPage implements OnInit {

  private tableService = inject(TableService);
  private zoneService = inject(ZoneService);

  tables: any[] = [];
  zones: any[] = [];
  showForm = false;
  showConfirm = false;
  editingTable: any = null;
  pendingDeleteUuid: string | null = null;
  errors: { [key: string]: string } = {};

  form = {
    name: '',
    zone_id: '',
  };

  ngOnInit() {
    this.loadData();
  }

  loadData() {
    forkJoin({
      tables: this.tableService.getAll(),
      zones: this.zoneService.getAll(),
    }).subscribe({
      next: ({ tables, zones }) => {
        this.tables = tables;
        this.zones = zones;
      },
      error: (err: any) => console.error(err)
    });
  }

  get tablesByZone(): { zone: any, tables: any[] }[] {
    return this.zones.map(zone => ({
      zone,
      tables: this.tables.filter(t => t.zone_id === zone.uuid)
    })).filter(group => group.tables.length > 0);
  }

  openForm(table?: any) {
    this.editingTable = table ?? null;
    this.form = {
      name: table?.name ?? '',
      zone_id: table?.zone_id ?? '',
    };
    this.showForm = true;
  }

  closeForm() {
    this.showForm = false;
    this.editingTable = null;
    this.errors = {};
  }

  save() {
    this.errors = {};
    const action = this.editingTable
      ? this.tableService.update(this.editingTable.uuid, this.form)
      : this.tableService.create(this.form);

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

  requestDelete(uuid: string) {
    this.pendingDeleteUuid = uuid;
    this.showConfirm = true;
  }

  confirmDelete() {
    if (!this.pendingDeleteUuid) return;
    this.tableService.delete(this.pendingDeleteUuid).subscribe({
      next: () => { this.loadData(); this.closeConfirm(); },
      error: (err: any) => console.error(err)
    });
  }

  closeConfirm() {
    this.showConfirm = false;
    this.pendingDeleteUuid = null;
  }
}