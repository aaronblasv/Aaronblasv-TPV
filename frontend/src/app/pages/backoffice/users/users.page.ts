import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { UserService } from '../../../services/api/user.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';

@Component({
  selector: 'app-users',
  templateUrl: './users.page.html',
  styleUrls: ['./users.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent]
})
export class UsersPage implements OnInit {

  private userService = inject(UserService);

  users: any[] = [];
  showForm = false;
  showConfirm = false;
  editingUser: any = null;
  pendingDeleteUuid: string | null = null;
  errors: { [key: string]: string } = {};

  form = {
    name: '',
    email: '',
    password: '',
    role: 'waiter',
  };

  roles = [
    { value: 'admin', label: 'Administrador' },
    { value: 'supervisor', label: 'Supervisor' },
    { value: 'waiter', label: 'Camarero' },
  ];

  ngOnInit() {
    this.loadUsers();
  }

  loadUsers() {
    this.userService.getAll().subscribe({
      next: (data) => this.users = data,
      error: (err: any) => console.error(err)
    });
  }

  openForm(user?: any) {
    this.editingUser = user ?? null;
    this.form = {
      name: user?.name ?? '',
      email: user?.email ?? '',
      password: '',
      role: user?.role ?? 'waiter',
    };
    this.showForm = true;
  }

  closeForm() {
    this.showForm = false;
    this.editingUser = null;
    this.errors = {};
  }

  save() {
    this.errors = {};
    const action = this.editingUser
      ? this.userService.update(this.editingUser.id, this.form)
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


  requestDelete(uuid: string) {
    this.pendingDeleteUuid = uuid;
    this.showConfirm = true;
  }

  confirmDelete() {
    if (!this.pendingDeleteUuid) return;
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