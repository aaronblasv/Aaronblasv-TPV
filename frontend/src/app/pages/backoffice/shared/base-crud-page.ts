import { Directive, OnInit, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { AlertService } from '../../../services/alert.service';
import { ApiValidationError, ValidationErrors } from '../../../types/api-error.model';

export interface UuidEntity {
  uuid: string;
}

@Directive()
export abstract class BaseCrudPage<T extends UuidEntity, F> implements OnInit {
  protected readonly alerts = inject(AlertService);

  items: T[] = [];
  showForm = false;
  showConfirm = false;
  editingItem: T | null = null;
  pendingDeleteUuid: string | null = null;
  errors: ValidationErrors = {};
  loading = false;
  searchTerm = '';
  form!: F;

  protected abstract entityLabel: string;

  ngOnInit(): void {
    this.form = this.emptyForm();
    this.onPageInit();
  }

  protected onPageInit(): void {
    this.loadData();
  }

  protected abstract emptyForm(): F;
  protected abstract toForm(item: T): F;
  protected abstract loadData(): void;
  protected abstract createRequest(formData: F): Observable<unknown>;
  protected abstract updateRequest(uuid: string, formData: F): Observable<unknown>;
  protected abstract deleteRequest(uuid: string): Observable<void>;
  protected abstract searchableFields(item: T): Array<string | number | null | undefined>;

  get filteredItems(): T[] {
    const normalizedTerm = this.searchTerm.trim().toLowerCase();

    if (!normalizedTerm) {
      return this.items;
    }

    return this.items.filter((item) =>
      this.searchableFields(item)
        .filter((field) => field !== null && field !== undefined)
        .some((field) => String(field).toLowerCase().includes(normalizedTerm)),
    );
  }

  clearSearch(): void {
    this.searchTerm = '';
  }

  openForm(item?: T): void {
    this.editingItem = item ?? null;
    this.form = item ? this.toForm(item) : this.emptyForm();
    this.errors = {};
    this.showForm = true;
  }

  closeForm(): void {
    this.showForm = false;
    this.editingItem = null;
    this.errors = {};
    this.form = this.emptyForm();
  }

  save(): void {
    this.errors = {};

    const request$ = this.editingItem
      ? this.updateRequest(this.editingItem.uuid, this.form)
      : this.createRequest(this.form);

    request$.subscribe({
      next: () => {
        const successMessage = this.editingItem
          ? `${this.entityLabel} actualizado correctamente.`
          : `${this.entityLabel} creado correctamente.`;

        this.loadData();
        this.closeForm();
        this.alerts.success(successMessage);
      },
      error: (error: unknown) => {
        if (!this.handleApiError(error)) {
          this.alerts.error(`No se pudo guardar ${this.entityLabel.toLowerCase()}.`);
        }
      },
    });
  }

  requestDelete(uuid: string): void {
    this.pendingDeleteUuid = uuid;
    this.showConfirm = true;
  }

  confirmDelete(): void {
    if (!this.pendingDeleteUuid) {
      return;
    }

    this.deleteRequest(this.pendingDeleteUuid).subscribe({
      next: () => {
        this.loadData();
        this.closeConfirm();
        this.alerts.success(`${this.entityLabel} eliminado correctamente.`);
      },
      error: () => {
        this.alerts.error(`No se pudo eliminar ${this.entityLabel.toLowerCase()}.`);
      },
    });
  }

  closeConfirm(): void {
    this.showConfirm = false;
    this.pendingDeleteUuid = null;
  }

  protected handleLoadError(message: string): void {
    this.loading = false;
    this.alerts.error(message);
  }

  protected handleApiError(error: unknown): boolean {
    const apiError = error as ApiValidationError;

    if (apiError?.status !== 422 || !apiError.error?.errors) {
      return false;
    }

    this.errors = Object.fromEntries(
      Object.entries(apiError.error.errors).map(([field, messages]) => [field, messages[0] ?? 'Valor inválido'])
    );

    return true;
  }
}
