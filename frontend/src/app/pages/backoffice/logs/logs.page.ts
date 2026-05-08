import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { LogService } from '../../../services/api/log.service';
import { UserService } from '../../../services/api/user.service';
import { AlertService } from '../../../services/alert.service';
import { Log } from '../../../types/log.model';
import { User } from '../../../types/user.model';

@Component({
  selector: 'app-logs',
  templateUrl: './logs.page.html',
  styleUrls: ['./logs.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent]
})
export class LogsPage implements OnInit {

  private logService = inject(LogService);
  private userService = inject(UserService);
  private alerts = inject(AlertService);

  logs: Log[] = [];
  users: User[] = [];
  total = 0;
  loading = false;
  filters = {
    action: '',
    user_id: '',
  };
  readonly actionLabels: Record<string, string> = {
    'cash_shift.closed': 'Caja cerrada',
    'cash_shift.opened': 'Caja abierta',
    'family.activated': 'Familia activada',
    'family.deactivated': 'Familia desactivada',
    'invoice.generated': 'Factura generada',
    'order.cancelled': 'Pedido cancelado',
    'order.closed': 'Pedido cerrado',
    'order.discount.updated': 'Descuento de pedido actualizado',
    'order.line.added': 'Producto añadido al pedido',
    'order.line.discount.updated': 'Descuento de línea actualizado',
    'order.line.voided_after_kitchen': 'Línea anulada tras enviar a cocina',
    'order.opened': 'Pedido abierto',
    'order.sent_to_kitchen': 'Pedido enviado a cocina',
    'order.transferred': 'Pedido transferido',
    'payment.registered': 'Pago registrado',
    'product.activated': 'Producto activado',
    'product.deactivated': 'Producto desactivado',
    'sale.refunded': 'Reembolso registrado',
  };

  ngOnInit() {
    this.loadUsers();
    this.loadLogs();
  }

  loadLogs() {
    this.loading = true;
    this.logService.getAll({
      action: this.filters.action || undefined,
      user_id: this.filters.user_id || undefined,
    }).subscribe({
      next: (data) => {
        this.logs = data.logs ?? data;
        this.total = data.total ?? this.logs.length;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.alerts.error('No se pudieron cargar los logs.');
      }
    });
  }

  loadUsers() {
    this.userService.getAll().subscribe({
      next: (users) => {
        this.users = users.filter(user => user.active).sort((left, right) => left.name.localeCompare(right.name, 'es'));
      },
      error: () => this.alerts.error('No se pudieron cargar los usuarios para filtrar logs.'),
    });
  }

  get actionOptions(): Array<{ value: string; label: string }> {
    const actions = new Set<string>(Object.keys(this.actionLabels));

    this.logs.forEach(log => {
      if (log.action) {
        actions.add(log.action);
      }
    });

    return Array.from(actions)
      .sort((left, right) => this.getActionLabel(left).localeCompare(this.getActionLabel(right), 'es'))
      .map(action => ({ value: action, label: this.getActionLabel(action) }));
  }

  onFiltersChanged() {
    this.loadLogs();
  }

  clearFilters() {
    this.filters = {
      action: '',
      user_id: '',
    };
    this.loadLogs();
  }

  getActionLabel(action: string): string {
    return this.actionLabels[action] ?? action;
  }

  getActionClass(action: string): string {
    if (action.includes('cash_shift')) return 'badge-amber';
    if (action.includes('opened') || action.includes('generated')) return 'badge-green';
    if (action.includes('closed') || action.includes('registered')) return 'badge-blue';
    if (action.includes('added') || action.includes('activated') || action.includes('sent_to_kitchen')) return 'badge-green';
    if (action.includes('updated') || action.includes('transferred')) return 'badge-blue';
    if (action.includes('voided') || action.includes('deactivated') || action.includes('refunded')) return 'badge-red';
    if (action.includes('cancelled')) return 'badge-red';
    return 'badge-gray';
  }
}
