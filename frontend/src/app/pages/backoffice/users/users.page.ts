import { Component, OnInit, inject, CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { AuthService } from '../../../services/api/auth.service';
import { UserService } from '../../../services/api/user.service';
import { UploadService } from '../../../services/api/upload.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';

@Component({
  selector: 'app-users',
  templateUrl: './users.page.html',
  styleUrls: ['./users.page.scss'],
  standalone: true,
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent]
})
export class UsersPage implements OnInit {

  private userService = inject(UserService);
  private uploadService = inject(UploadService);
  private authService = inject(AuthService);

  users: any[] = [];
  showForm = false;
  showConfirm = false;
  editingUser: any = null;
  pendingDeleteUuid: string | null = null;
  canManageUsers = false;
  errors: { [key: string]: string } = {};

  form = {
    name: '',
    email: '',
    password: '',
    role: 'waiter',
    image_src: '' as string | null,
  };

  roles = [
    { value: 'admin', label: 'Administrador' },
    { value: 'supervisor', label: 'Supervisor' },
    { value: 'waiter', label: 'Camarero' },
  ];

  ngOnInit() {
    this.canManageUsers = this.authService.getRole() === 'admin';
    this.loadUsers();
  }

  loadUsers() {
    this.userService.getAll().subscribe({
      next: (data) => this.users = data,
      error: (err: any) => console.error(err)
    });
  }

  openForm(user?: any) {
    if (!this.canManageUsers) {
      return;
    }

    this.editingUser = user ?? null;
    this.form = {
      name: user?.name ?? '',
      email: user?.email ?? '',
      password: '',
      role: user?.role ?? 'waiter',
      image_src: user?.image_src ?? '',
    };
    this.showForm = true;
  }

  closeForm() {
    this.showForm = false;
    this.editingUser = null;
    this.errors = {};
  }

  save() {
    if (!this.canManageUsers) {
      return;
    }

    this.errors = {};
    const action = this.editingUser
      ? this.userService.update(this.editingUser.uuid, this.form)
      : this.userService.create(this.form);

    action.subscribe({
      next: () => { this.loadUsers(); this.closeForm(); },
      error: (err: any) => {
        if (err.status === 422) {
          Object.keys(err.error.errors).forEach(key => {
            this.errors[key] = err.error.errors[key][0];
          });
        }
      }
    });
  }


  onFileSelected(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    this.uploadService.uploadImage(file).subscribe({
      next: (url) => this.form.image_src = url,
      error: (err: any) => console.error('Upload error:', err),
    });
  }

  requestDelete(uuid: string) {
    if (!this.canManageUsers) {
      return;
    }

    this.pendingDeleteUuid = uuid;
    this.showConfirm = true;
  }

  confirmDelete() {
    if (!this.pendingDeleteUuid || !this.canManageUsers) return;
    this.userService.delete(this.pendingDeleteUuid).subscribe({
      next: () => { this.loadUsers(); this.closeConfirm(); },
      error: (err: any) => console.error(err)
    });
  }

  closeConfirm() {
    this.showConfirm = false;
    this.pendingDeleteUuid = null;
  }
}