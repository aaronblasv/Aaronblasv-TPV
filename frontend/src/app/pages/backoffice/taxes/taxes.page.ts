import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';
import { SearchInputComponent } from '../../../components/search-input/search-input.component';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { TaxService } from '../../../services/api/tax.service';
import { Tax, TaxFormData } from '../../../types/tax.model';
import { BaseCrudPage } from '../shared/base-crud-page';

@Component({
  selector: 'app-taxes',
  templateUrl: './taxes.page.html',
  styleUrls: ['./taxes.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent, SearchInputComponent]
})
export class TaxesPage extends BaseCrudPage<Tax, TaxFormData> {

  private readonly taxService = inject(TaxService);

  protected entityLabel = 'Impuesto';

  protected emptyForm(): TaxFormData {
    return {
      name: '',
      percentage: 0,
    };
  }

  protected toForm(tax: Tax): TaxFormData {
    return {
      name: tax.name,
      percentage: tax.percentage,
    };
  }

  protected loadData(): void {
    this.loading = true;

    this.taxService.getAll().subscribe({
      next: (taxes) => {
        this.items = taxes;
        this.loading = false;
      },
      error: () => this.handleLoadError('No se pudieron cargar los impuestos.')
    });
  }

  protected createRequest(formData: TaxFormData) {
    return this.taxService.create(formData);
  }

  protected updateRequest(uuid: string, formData: TaxFormData) {
    return this.taxService.update(uuid, formData);
  }

  protected deleteRequest(uuid: string) {
    return this.taxService.delete(uuid);
  }

  protected searchableFields(tax: Tax): Array<string | number | null | undefined> {
    return [tax.name, tax.percentage];
  }
}