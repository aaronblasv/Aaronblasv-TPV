import { Component, CUSTOM_ELEMENTS_SCHEMA, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { forkJoin } from 'rxjs';
import { IonContent } from '@ionic/angular/standalone';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';
import { SearchInputComponent } from '../../../components/search-input/search-input.component';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { FamilyService } from '../../../services/api/family.service';
import { ProductService } from '../../../services/api/product.service';
import { TaxService } from '../../../services/api/tax.service';
import { UploadService } from '../../../services/api/upload.service';
import { Family } from '../../../types/family.model';
import { Product, ProductFormData } from '../../../types/product.model';
import { Tax } from '../../../types/tax.model';
import { BaseCrudPage } from '../shared/base-crud-page';

@Component({
  selector: 'app-products',
  templateUrl: './products.page.html',
  styleUrls: ['./products.page.scss'],
  standalone: true,
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
  imports: [
    IonContent,
    CommonModule,
    FormsModule,
    SidebarComponent,
    FormModalComponent,
    ConfirmModalComponent,
    ActionButtonsComponent,
    SearchInputComponent,
  ]
})
export class ProductsPage extends BaseCrudPage<Product, ProductFormData> {

  private readonly productService = inject(ProductService);
  private readonly familyService = inject(FamilyService);
  private readonly taxService = inject(TaxService);
  private readonly uploadService = inject(UploadService);

  protected entityLabel = 'Producto';

  families: Family[] = [];
  taxes: Tax[] = [];

  protected emptyForm(): ProductFormData {
    return {
      name: '',
      price: 0,
      stock: 0,
      family_id: '',
      tax_id: '',
      image_src: null,
    };
  }

  protected toForm(product: Product): ProductFormData {
    return {
      name: product.name,
      price: product.price,
      stock: product.stock,
      family_id: product.family_id,
      tax_id: product.tax_id,
      image_src: product.image_src,
    };
  }

  protected loadData(): void {
    this.loading = true;

    forkJoin({
      products: this.productService.getAll(),
      families: this.familyService.getAll(),
      taxes: this.taxService.getAll(),
    }).subscribe({
      next: ({ products, families, taxes }) => {
        this.items = products;
        this.families = families;
        this.taxes = taxes;
        this.loading = false;
      },
      error: () => this.handleLoadError('No se pudieron cargar los productos.')
    });
  }

  protected createRequest(formData: ProductFormData) {
    return this.productService.create(formData);
  }

  protected updateRequest(uuid: string, formData: ProductFormData) {
    return this.productService.update(uuid, formData);
  }

  protected deleteRequest(uuid: string) {
    return this.productService.delete(uuid);
  }

  protected searchableFields(product: Product): Array<string | number | null | undefined> {
    return [
      product.name,
      product.stock,
      this.families.find((family) => family.uuid === product.family_id)?.name,
      this.taxes.find((tax) => tax.uuid === product.tax_id)?.name,
    ];
  }

  get productsByFamily(): Array<{ family: Family; products: Product[] }> {
    return this.families
      .map((family) => ({
        family,
        products: this.filteredItems.filter((product) => product.family_id === family.uuid),
      }))
      .filter((group) => group.products.length > 0);
  }

  onFileSelected(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
      return;
    }

    this.uploadService.uploadImage(file).subscribe({
      next: (url) => {
        this.form.image_src = url;
        this.alerts.success('Imagen subida correctamente.');
      },
      error: () => this.alerts.error('No se pudo subir la imagen.'),
    });
  }

  toggle(uuid: string): void {
    const product = this.items.find((item) => item.uuid === uuid);

    if (!product) {
      return;
    }

    const action = product.active
      ? this.productService.deactivate(uuid)
      : this.productService.activate(uuid);

    action.subscribe({
      next: () => {
        this.loadData();
        this.alerts.success(product.active ? 'Producto desactivado.' : 'Producto activado.');
      },
      error: () => this.alerts.error('No se pudo actualizar el estado del producto.')
    });
  }
}