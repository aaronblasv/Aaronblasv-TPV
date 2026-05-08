import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { forkJoin } from 'rxjs';
import { IonContent } from '@ionic/angular/standalone';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';
import { SearchInputComponent } from '../../../components/search-input/search-input.component';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { TableService } from '../../../services/api/table.service';
import { ZoneService } from '../../../services/api/zone.service';
import { Table } from '../../../types/table.model';
import { Zone, ZoneFormData } from '../../../types/zone.model';
import { BaseCrudPage } from '../shared/base-crud-page';

@Component({
  selector: 'app-zones',
  templateUrl: './zones.page.html',
  styleUrls: ['./zones.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent, SearchInputComponent]
})
export class ZonesPage extends BaseCrudPage<Zone, ZoneFormData> {

  private readonly zoneService = inject(ZoneService);
  private readonly tableService = inject(TableService);

  protected entityLabel = 'Zona';

  tables: Table[] = [];
  confirmMessage = '';

  protected emptyForm(): ZoneFormData {
    return { name: '' };
  }

  protected toForm(zone: Zone): ZoneFormData {
    return { name: zone.name };
  }

  protected loadData(): void {
    this.loading = true;

    forkJoin({
      zones: this.zoneService.getAll(),
      tables: this.tableService.getAll(),
    }).subscribe({
      next: ({ zones, tables }) => {
        this.items = zones;
        this.tables = tables;
        this.loading = false;
      },
      error: () => this.handleLoadError('No se pudieron cargar las zonas.')
    });
  }

  protected createRequest(formData: ZoneFormData) {
    return this.zoneService.create(formData);
  }

  protected updateRequest(uuid: string, formData: ZoneFormData) {
    return this.zoneService.update(uuid, formData);
  }

  protected deleteRequest(uuid: string) {
    return this.zoneService.delete(uuid);
  }

  protected searchableFields(zone: Zone): Array<string | number | null | undefined> {
    return [zone.name];
  }

  openDeleteConfirmation(zone: Zone): void {
    const tableCount = this.tables.filter((table) => table.zone_id === zone.uuid).length;

    this.pendingDeleteUuid = zone.uuid;
    this.confirmMessage = tableCount > 0
      ? `Se eliminará "${zone.name}" y sus mesas asociadas. Esta acción no se puede deshacer.`
      : `Se eliminará "${zone.name}". Esta acción no se puede deshacer.`;
    this.showConfirm = true;
  }
}