import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { Router } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { catchError, forkJoin, of } from 'rxjs';
import { TpvSidebarComponent } from '../../../components/tpv-sidebar/tpv-sidebar.component';
import { OrderService } from '../../../services/api/order.service';
import { SaleService } from '../../../services/api/sale.service';
import { TableService } from '../../../services/api/table.service';
import { TpvSessionService } from '../../../services/tpv-session.service';
import { Order } from '../../../types/order.model';
import { Sale } from '../../../types/sale.model';
import { Table } from '../../../types/table.model';

@Component({
  selector: 'app-tickets',
  standalone: true,
  imports: [CommonModule, IonicModule, TpvSidebarComponent],
  templateUrl: './tickets.page.html',
  styleUrls: ['./tickets.page.scss'],
})
export class TicketsPage implements OnInit, OnDestroy {
  private router = inject(Router);
  private orderService = inject(OrderService);
  private saleService = inject(SaleService);
  private tableService = inject(TableService);
  private tpvSessionService = inject(TpvSessionService);

  openOrders: Order[] = [];
  recentSales: Sale[] = [];
  tables: Table[] = [];
  loading = true;
  private refreshInterval: ReturnType<typeof setInterval> | null = null;
  now = Date.now();

  ngOnInit(): void {
    if (!this.ensureActiveSession()) {
      return;
    }

    this.loadData();
    this.refreshInterval = setInterval(() => {
      this.now = Date.now();
    }, 30000);
  }

  ngOnDestroy(): void {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
  }

  ionViewWillEnter(): void {
    if (!this.ensureActiveSession()) {
      return;
    }

    this.loadData();
  }

  openOrder(order: Order): void {
    this.router.navigate(['/tpv/order', order.table_id]);
  }

  logoutTpvSession(): void {
    this.tpvSessionService.clear();
    this.router.navigate(['/tpv']);
  }

  getTableName(tableUuid: string): string {
    return this.tables.find((table) => table.uuid === tableUuid)?.name ?? 'Mesa';
  }

  getElapsedLabel(openedAt?: string): string {
    if (!openedAt) {
      return 'En curso';
    }

    const openedTimestamp = this.parseBackendDate(openedAt)?.getTime() ?? Number.NaN;
    if (Number.isNaN(openedTimestamp)) {
      return 'En curso';
    }

    const diffMs = Math.max(0, this.now - openedTimestamp);
    const totalMinutes = Math.floor(diffMs / 60000);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours <= 0) {
      return `${minutes} min`;
    }

    return `${hours} h ${minutes.toString().padStart(2, '0')} min`;
  }

  getAverageElapsedLabel(): string {
    const openOrdersWithDate = this.openOrders.filter((order) => !!order.opened_at);
    if (!openOrdersWithDate.length) {
      return '—';
    }

    const averageTimestamp = openOrdersWithDate.reduce((sum, order) => {
      return sum + (this.parseBackendDate(order.opened_at as string)?.getTime() ?? 0);
    }, 0) / openOrdersWithDate.length;

    return this.getElapsedLabel(new Date(averageTimestamp).toISOString());
  }

  formatStatus(status: string): string {
    if (status === 'open') {
      return 'Abierto';
    }

    if (status === 'closed') {
      return 'Cerrado';
    }

    if (status === 'invoiced') {
      return 'Facturado';
    }

    return status;
  }

  formatCurrency(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
  }

  formatDate(value: string | null): string {
    if (!value) {
      return '—';
    }

    const parsedDate = this.parseBackendDate(value);
    if (!parsedDate) {
      return '—';
    }

    return parsedDate.toLocaleString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  private loadData(): void {
    this.loading = true;

    forkJoin({
      openOrders: this.orderService.getAllOpen().pipe(catchError(() => of([] as Order[]))),
      recentSales: this.saleService.getAll().pipe(catchError(() => of([] as Sale[]))),
      tables: this.tableService.getAllTpv().pipe(catchError(() => of([] as Table[]))),
    }).subscribe({
      next: ({ openOrders, recentSales, tables }) => {
        this.openOrders = [...openOrders].sort((left, right) => {
          const leftTime = this.parseBackendDate(left.opened_at ?? null)?.getTime() ?? 0;
          const rightTime = this.parseBackendDate(right.opened_at ?? null)?.getTime() ?? 0;
          return leftTime - rightTime;
        });
        this.recentSales = [...recentSales]
          .sort((left, right) => {
            const leftTime = this.parseBackendDate(left.closed_at ?? left.opened_at)?.getTime() ?? 0;
            const rightTime = this.parseBackendDate(right.closed_at ?? right.opened_at)?.getTime() ?? 0;
            return rightTime - leftTime;
          })
          .slice(0, 12);
        this.tables = tables;
        this.now = Date.now();
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      },
    });
  }

  private ensureActiveSession(): boolean {
    if (this.tpvSessionService.getUser()) {
      return true;
    }

    this.router.navigate(['/tpv']);
    return false;
  }

  private parseBackendDate(value: string | null | undefined): Date | null {
    if (!value) {
      return null;
    }

    const normalizedValue = value.includes('T')
      ? value
      : value.replace(' ', 'T');

    const utcValue = /([zZ]|[+-]\d{2}:?\d{2})$/.test(normalizedValue)
      ? normalizedValue
      : `${normalizedValue}Z`;

    const parsedDate = new Date(utcValue);

    return Number.isNaN(parsedDate.getTime()) ? null : parsedDate;
  }
}
