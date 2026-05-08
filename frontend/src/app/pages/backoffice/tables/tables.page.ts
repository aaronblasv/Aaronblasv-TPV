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
import { Table, TableFormData } from '../../../types/table.model';
import { Zone } from '../../../types/zone.model';
import { BaseCrudPage } from '../shared/base-crud-page';

@Component({
  selector: 'app-tables',
  templateUrl: './tables.page.html',
  styleUrls: ['./tables.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent, SearchInputComponent]
})
export class TablesPage extends BaseCrudPage<Table, TableFormData> {

  private readonly tableService = inject(TableService);
  private readonly zoneService = inject(ZoneService);

  protected entityLabel = 'Mesa';

  zones: Zone[] = [];

  protected emptyForm(): TableFormData {
    return {
      name: '',
      zone_id: '',
    };
  }

  protected toForm(table: Table): TableFormData {
    return {
      name: table.name,
      zone_id: table.zone_id,
    };
  }

  protected loadData(): void {
    this.loading = true;

    forkJoin({
      tables: this.tableService.getAll(),
      zones: this.zoneService.getAll(),
    }).subscribe({
      next: ({ tables, zones }) => {
        this.items = tables;
        this.zones = zones;
        this.loading = false;
      },
      error: () => this.handleLoadError('No se pudieron cargar las mesas.')
    });
  }

  protected createRequest(formData: TableFormData) {
    return this.tableService.create(formData);
  }

  protected updateRequest(uuid: string, formData: TableFormData) {
    return this.tableService.update(uuid, formData);
  }

  protected deleteRequest(uuid: string) {
    return this.tableService.delete(uuid);
  }

  protected searchableFields(table: Table): Array<string | number | null | undefined> {
    return [table.name, this.zones.find((zone) => zone.uuid === table.zone_id)?.name];
  }

  get tablesByZone(): Array<{ zone: Zone; tables: Table[] }> {
    return this.zones
      .map((zone) => ({
        zone,
        tables: this.filteredItems.filter((table) => table.zone_id === zone.uuid),
      }))
      .filter((group) => group.tables.length > 0);
  }
}