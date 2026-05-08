import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';
import { SearchInputComponent } from '../../../components/search-input/search-input.component';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { FamilyService } from '../../../services/api/family.service';
import { ProductService } from 'src/app/services/api/product.service';
import { forkJoin } from 'rxjs';
import { Family, FamilyFormData } from '../../../types/family.model';
import { Product } from '../../../types/product.model';
import { BaseCrudPage } from '../shared/base-crud-page';


@Component({
  selector: 'app-families',
  templateUrl: './families.page.html',
  styleUrls: ['./families.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent, SearchInputComponent]
})
export class FamiliesPage extends BaseCrudPage<Family, FamilyFormData> {

  private readonly productService = inject(ProductService);
  private readonly familyService = inject(FamilyService);

  protected entityLabel = 'Familia';

  products: Product[] = [];
  confirmMessage = '';

  protected emptyForm(): FamilyFormData {
    return { name: '' };
  }

  protected toForm(family: Family): FamilyFormData {
    return { name: family.name };
  }

  protected loadData(): void {
    this.loading = true;

    forkJoin({
      families: this.familyService.getAll(),
      products: this.productService.getAll(),
    }).subscribe({
      next: ({ families, products }) => {
        this.items = families;
        this.products = products;
        this.loading = false;
      },
      error: () => this.handleLoadError('No se pudieron cargar las familias.')
    });
  }

  protected createRequest(formData: FamilyFormData) {
    return this.familyService.create(formData);
  }

  protected updateRequest(uuid: string, formData: FamilyFormData) {
    return this.familyService.update(uuid, formData);
  }

  protected deleteRequest(uuid: string) {
    return this.familyService.delete(uuid);
  }

  protected searchableFields(family: Family): Array<string | number | null | undefined> {
    return [family.name];
  }

  openDeleteConfirmation(family: Family): void {
    const productCount = this.products.filter((product) => product.family_id === family.uuid).length;

    this.pendingDeleteUuid = family.uuid;
    this.confirmMessage = productCount > 0
      ? `Se eliminará "${family.name}" y sus productos asociados. Esta acción no se puede deshacer.`
      : `Se eliminará "${family.name}". Esta acción no se puede deshacer.`;
    this.showConfirm = true;
  }

  toggle(family: Family): void {
    const action = family.active
      ? this.familyService.deactivate(family.uuid)
      : this.familyService.activate(family.uuid);

    action.subscribe({
      next: () => {
        this.loadData();
        this.alerts.success(family.active ? 'Familia desactivada.' : 'Familia activada.');
      },
      error: () => this.alerts.error('No se pudo actualizar el estado de la familia.')
    });
  }
}