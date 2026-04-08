import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { TaxService } from '../../../services/api/tax.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';

@Component({
  selector: 'app-taxes',
  templateUrl: './taxes.page.html',
  styleUrls: ['./taxes.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent]
})
export class TaxesPage implements OnInit {

  private taxService = inject(TaxService);

  taxes: any[] = [];
  showForm = false;
  showConfirm = false;
  editingTax: any = null;
  pendingDeleteUuid: string | null = null;
  errors: { [key: string]: string } = {};

  form = {
    name: '',
    percentage: 0,
  };

  ngOnInit() {
    this.loadTaxes();
  }

  loadTaxes() {
    this.taxService.getAll().subscribe({
      next: (data: any) => this.taxes = data,
      error: (err: any) => console.error(err)
    });
  }

  openForm(tax?: any) {
    this.editingTax = tax ?? null;
    this.form = { name: tax?.name ?? '', percentage: tax?.percentage ?? 0 };
    this.showForm = true;
  }

  closeForm() {
    this.showForm = false;
    this.editingTax = null;
    this.errors = {};
  }

  save() {
    this.errors = {};
    const action = this.editingTax
      ? this.taxService.update(this.editingTax.uuid, this.form.name, this.form.percentage)
      : this.taxService.create(this.form.name, this.form.percentage);

    action.subscribe({
      next: () => { this.loadTaxes(); this.closeForm(); },
      error: (err: any) => {
        console.log('error completo:', err);
        console.log('err.error:', err.error);
        if (err.status === 422) {
          const apiErrors = err.error.errors;
          Object.keys(apiErrors).forEach(key => {
            this.errors[key] = apiErrors[key][0];
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
    this.taxService.delete(this.pendingDeleteUuid).subscribe({
      next: () => { this.loadTaxes(); this.closeConfirm(); },
      error: (err: any) => console.error(err)
    });
  }

  closeConfirm() {
    this.showConfirm = false;
    this.pendingDeleteUuid = null;
  }
}