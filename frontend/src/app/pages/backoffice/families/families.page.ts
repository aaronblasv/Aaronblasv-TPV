import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { FamilyService } from '../../../services/api/family.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';


@Component({
  selector: 'app-families',
  templateUrl: './families.page.html',
  styleUrls: ['./families.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent]
})
export class FamiliesPage implements OnInit {

  families: any[] = [];
  showForm = false;
  editingFamily: any = null;
  pendingDeleteUuid: string | null = null;
  showConfirm = false;
  errors: { [key: string]: string } = {};

  form = { name: '' };

  constructor(private familyService: FamilyService) {}

  requestDelete(uuid: string) {
    this.pendingDeleteUuid = uuid;
    this.showConfirm = true;
  }

  confirmDelete() {
    if (!this.pendingDeleteUuid) return;
    this.familyService.delete(this.pendingDeleteUuid).subscribe({
      next: () => { this.loadFamilies(); this.closeConfirm(); },
      error: (err: any) => console.error(err)
    });
  }

  closeConfirm() {
    this.showConfirm = false;
    this.pendingDeleteUuid = null;
  }


  ngOnInit() {
    this.loadFamilies();
  }

  loadFamilies() {
    this.familyService.getAll().subscribe({
      next: (data) => this.families = data,
      error: (err) => console.error(err)
    });
  }

  openForm(family?: any) {
    this.editingFamily = family ?? null;
    this.form = { name: family?.name ?? '' };
    this.showForm = true;
  }

  closeForm() {
    this.showForm = false;
    this.editingFamily = null;
    this.errors = {};
  }

  save() {
    this.errors = {};
    const action = this.editingFamily
      ? this.familyService.update(this.editingFamily.uuid, this.form.name)
      : this.familyService.create(this.form.name);

    action.subscribe({
      next: () => { this.loadFamilies(); this.closeForm(); },
      error: (err: any) => {
        if (err.status === 422) {
          const apiErrors = err.error.errors;
          Object.keys(apiErrors).forEach(key => {
            this.errors[key] = apiErrors[key][0];
          });
        }
      }
    });
  }

  toggle(family: any) {
    const action = family.active
      ? this.familyService.deactivate(family.uuid)
      : this.familyService.activate(family.uuid);

    action.subscribe({
      next: () => this.loadFamilies(),
      error: (err: any) => console.error(err)
    });
  }

  delete(uuid: string) {
    if (confirm('¿Eliminar esta familia?')) {
      this.familyService.delete(uuid).subscribe({
        next: () => this.loadFamilies(),
        error: (err) => console.error(err)
      });
    }
  }
}