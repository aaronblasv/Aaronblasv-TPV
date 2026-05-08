import { Component, CUSTOM_ELEMENTS_SCHEMA, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { ActionButtonsComponent } from '../../../components/action-buttons/action-buttons.component';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { FormModalComponent } from '../../../components/form-modal/form-modal.component';
import { SearchInputComponent } from '../../../components/search-input/search-input.component';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { AuthService } from '../../../services/api/auth.service';
import { UploadService } from '../../../services/api/upload.service';
import { UserService } from '../../../services/api/user.service';
import { AuthenticatedUser, User, UserFormData, UserRole } from '../../../types/user.model';
import { BaseCrudPage } from '../shared/base-crud-page';

@Component({
  selector: 'app-users',
  templateUrl: './users.page.html',
  styleUrls: ['./users.page.scss'],
  standalone: true,
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent, ConfirmModalComponent, ActionButtonsComponent, FormModalComponent, SearchInputComponent]
})
export class UsersPage extends BaseCrudPage<User, UserFormData> {

  private readonly userService = inject(UserService);
  private readonly uploadService = inject(UploadService);
  private readonly authService = inject(AuthService);

  protected entityLabel = 'Usuario';

  canManageUsers = false;

  roles = [
    { value: 'admin' as UserRole, label: 'Administrador' },
    { value: 'supervisor' as UserRole, label: 'Supervisor' },
    { value: 'waiter' as UserRole, label: 'Camarero' },
  ];

  protected override onPageInit(): void {
    this.authService.me().subscribe({
      next: (user: AuthenticatedUser) => {
        this.canManageUsers = user.role === 'admin';
        this.loadData();
      },
      error: () => {
        this.canManageUsers = false;
        this.loadData();
      },
    });
  }

  protected emptyForm(): UserFormData {
    return {
      name: '',
      email: '',
      password: '',
      role: 'waiter',
      image_src: null,
    };
  }

  protected toForm(user: User): UserFormData {
    return {
      name: user.name,
      email: user.email,
      password: '',
      role: (user.role as UserRole) ?? 'waiter',
      image_src: user.image_src,
    };
  }

  protected loadData(): void {
    this.loading = true;

    this.userService.getAll().subscribe({
      next: (users) => {
        this.items = users;
        this.loading = false;
      },
      error: () => this.handleLoadError('No se pudieron cargar los usuarios.')
    });
  }

  protected createRequest(formData: UserFormData) {
    return this.userService.create(formData);
  }

  protected updateRequest(uuid: string, formData: UserFormData) {
    return this.userService.update(uuid, formData);
  }

  protected deleteRequest(uuid: string) {
    return this.userService.delete(uuid);
  }

  protected searchableFields(user: User): Array<string | number | null | undefined> {
    return [user.name, user.email, user.role];
  }

  override openForm(user?: User): void {
    if (!this.canManageUsers) {
      return;
    }

    super.openForm(user);
  }

  override save(): void {
    if (!this.canManageUsers) {
      return;
    }

    super.save();
  }

  onFileSelected(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
      return;
    }

    this.uploadService.uploadImage(file).subscribe({
      next: (url) => {
        this.form.image_src = url;
        this.alerts.success('Foto subida correctamente.');
      },
      error: () => this.alerts.error('No se pudo subir la foto.'),
    });
  }

  override requestDelete(uuid: string): void {
    if (!this.canManageUsers) {
      return;
    }

    super.requestDelete(uuid);
  }

  override confirmDelete(): void {
    if (!this.canManageUsers) {
      return;
    }

    super.confirmDelete();
  }
}