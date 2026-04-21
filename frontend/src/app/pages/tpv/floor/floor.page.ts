import { Component, OnInit, OnDestroy, inject, ViewEncapsulation } from '@angular/core';
import { CommonModule, DecimalPipe } from '@angular/common';
import { Router } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { catchError, forkJoin, of } from 'rxjs';
import { AuthService } from '../../../services/api/auth.service';
import { ZoneService } from '../../../services/api/zone.service';
import { TableService } from '../../../services/api/table.service';
import { OrderService } from '../../../services/api/order.service';
import { CashShiftService } from '../../../services/api/cash-shift.service';
import { LoggerService } from '../../../services/logger.service';
import { PinModalComponent } from '../../../components/pin-modal/pin-modal.component';
import { DinersModalComponent } from '../../../components/diners-modal/diners-modal.component';
import { WaiterModalComponent } from '../../../components/waiter-modal/waiter-modal.component';
import { ProfileModalComponent } from '../../../components/profile-modal/profile-modal.component';
import { Zone } from '../../../types/zone.model';
import { Table } from '../../../types/table.model';
import { Order } from '../../../types/order.model';
import { User } from '../../../types/user.model';
import { CashShiftSummary } from '../../../types/cash-shift.model';

@Component({
  selector: 'app-floor',
  standalone: true,
  imports: [CommonModule, IonicModule, PinModalComponent, DinersModalComponent, WaiterModalComponent, ProfileModalComponent],
  templateUrl: './floor.page.html',
  styleUrls: ['./floor.page.scss'],
  encapsulation: ViewEncapsulation.None,
})
export class FloorPage implements OnInit, OnDestroy {
  private router = inject(Router);
  private authService = inject(AuthService);
  private zoneService = inject(ZoneService);
  private tableService = inject(TableService);
  private orderService = inject(OrderService);
  private cashShiftService = inject(CashShiftService);
  private logger = inject(LoggerService);

  zones: Zone[] = [];
  tables: Table[] = [];
  openOrders: Order[] = [];
  selectedZoneUuid: string | null = null;

  showWaiterModal = false;
  showPinModal = false;
  showDinersModal = false;
  selectedTable: Table | null = null;
  selectedWaiter: User | null = null;
  validatedUser: User | null = null;

  // Merge tables state
  mergeMode = false;
  mergeParent: Table | null = null;
  mergeSelected: Set<string> = new Set();

  // Clock
  currentTime = '';
  today = '';
  private clockInterval: ReturnType<typeof setInterval> | null = null;

  canGoBackoffice = false;
  currentCashShift: CashShiftSummary | null = null;
  cashShiftAlert = '';

  // Profile modal state
  showProfileWaiterModal = false;
  showProfilePinModal = false;
  showProfileModal = false;
  profileUser: User | null = null;
  profileWaiter: User | null = null;
  restaurantName = '';
  showBackofficeSupervisorModal = false;
  showBackofficePinModal = false;
  selectedBackofficeSupervisor: User | null = null;

  ngOnInit() {
    this.syncRoleFlags();
    this.loadData();
    this.updateClock();
    this.clockInterval = setInterval(() => this.updateClock(), 1000);
  }

  ngOnDestroy() {
    if (this.clockInterval) clearInterval(this.clockInterval);
  }

  private updateClock() {
    const now = new Date();
    this.currentTime = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    this.today = now.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
  }

  ionViewWillEnter() {
    this.syncRoleFlags();
    this.logger.log('ionViewWillEnter triggered - reloading data');
    this.loadData();
  }

  private syncRoleFlags() {
    const role = this.authService.getRole();
    this.canGoBackoffice = role === 'admin' || role === 'supervisor';
  }

  goToBackoffice() {
    if (!this.canGoBackoffice) return;
    this.selectedBackofficeSupervisor = null;
    this.showBackofficeSupervisorModal = true;
  }

  onBackofficeSupervisorSelected(user: User) {
    this.selectedBackofficeSupervisor = user;
    this.showBackofficeSupervisorModal = false;
    this.showBackofficePinModal = true;
  }

  onBackofficeSupervisorCancelled() {
    this.showBackofficeSupervisorModal = false;
    this.selectedBackofficeSupervisor = null;
  }

  onBackofficePinValidated(user: User) {
    this.showBackofficePinModal = false;
    this.selectedBackofficeSupervisor = null;

    if (user.role !== 'admin' && user.role !== 'supervisor') {
      this.cashShiftAlert = 'Solo un administrador o supervisor puede acceder al backoffice.';
      return;
    }

    this.router.navigate(['/dashboard']);
  }

  onBackofficePinCancelled() {
    this.showBackofficePinModal = false;
    this.selectedBackofficeSupervisor = null;
  }

  // ─── Profile flow (avatar click) ───
  onAvatarClick() {
    this.showProfileWaiterModal = true;
  }

  onProfileWaiterSelected(waiter: User) {
    this.profileWaiter = waiter;
    this.showProfileWaiterModal = false;
    this.showProfilePinModal = true;
  }

  onProfileWaiterCancelled() {
    this.showProfileWaiterModal = false;
    this.profileWaiter = null;
  }

  onProfilePinValidated(user: User) {
    this.profileUser = user;
    this.showProfilePinModal = false;
    // Fetch full user info (with restaurant name)
    this.authService.me().subscribe({
      next: (me: any) => {
        this.restaurantName = me.restaurant_name ?? '';
      },
      error: () => {},
    });
    this.showProfileModal = true;
  }

  onProfilePinCancelled() {
    this.showProfilePinModal = false;
    this.profileWaiter = null;
  }

  onProfileClosed() {
    this.showProfileModal = false;
    this.profileUser = null;
    this.profileWaiter = null;
  }

  ionViewDidLoad() {
    this.logger.log('ionViewDidLoad triggered - reloading data');
    this.loadData();
  }

  loadData() {
    this.logger.log('FloorPage: Starting to load data...');
    forkJoin({
      zones: this.zoneService.getAllTpv().pipe(
        catchError((err) => {
          this.logger.error('FloorPage: Error loading zones', err);
          return of([] as Zone[]);
        }),
      ),
      tables: this.tableService.getAllTpv().pipe(
        catchError((err) => {
          this.logger.error('FloorPage: Error loading tables', err);
          return of([] as Table[]);
        }),
      ),
      openOrders: this.orderService.getAllOpen().pipe(
        catchError((err) => {
          this.logger.error('FloorPage: Error loading open orders', err);
          return of([] as Order[]);
        }),
      ),
      currentCashShift: this.cashShiftService.getCurrent().pipe(
        catchError((err) => {
          this.logger.error('FloorPage: Error loading current cash shift', err);
          return of(null);
        }),
      ),
    }).subscribe({
      next: ({ zones, tables, openOrders, currentCashShift }) => {
        this.logger.log('FloorPage: Data loaded —', zones.length, 'zones,', tables.length, 'tables,', openOrders.length, 'open orders');
        this.zones = zones;
        this.tables = tables;
        this.openOrders = openOrders;
        this.currentCashShift = currentCashShift;

        tables.forEach(table => {
          this.logger.log(`  Mesa ${table.name}: ${this.isOccupied(table.uuid) ? 'OCUPADA' : 'LIBRE'}`);
        });
      },
      error: (err) => this.logger.error('FloorPage: Unexpected error loading data', err),
    });
  }

  get filteredTables(): Table[] {
    let tables = this.tables.filter(t => !t.merged_with);
    if (this.selectedZoneUuid) {
      tables = tables.filter(t => t.zone_id === this.selectedZoneUuid);
    }
    return tables;
  }

  getMergedChildren(parentUuid: string): Table[] {
    return this.tables.filter(t => t.merged_with === parentUuid);
  }

  getMergedNames(parentUuid: string): string {
    const children = this.getMergedChildren(parentUuid);
    return children.map(c => c.name).join(', ');
  }

  isParentMerged(tableUuid: string): boolean {
    return this.tables.some(t => t.merged_with === tableUuid);
  }

  getMergedCount(tableUuid: string): number {
    return this.getMergedChildren(tableUuid).length;
  }

  selectZone(uuid: string | null) {
    this.selectedZoneUuid = uuid;
  }

  isOccupied(tableUuid: string): boolean {
    return this.openOrders.some(o => o.table_id === tableUuid);
  }

  getOrder(tableUuid: string): Order | undefined {
    return this.openOrders.find(o => o.table_id === tableUuid);
  }

  getZoneName(zoneId: string): string {
    return this.zones.find(z => z.uuid === zoneId)?.name ?? '';
  }

  getTablesInZone(zoneId: string): number {
    return this.tables.filter(t => t.zone_id === zoneId && !t.merged_with).length;
  }

  get freeTables(): number {
    return this.filteredTables.filter(t => !this.isOccupied(t.uuid)).length;
  }

  get occupiedTables(): number {
    return this.filteredTables.filter(t => this.isOccupied(t.uuid)).length;
  }

  get allVisibleTables(): number {
    return this.tables.filter(t => !t.merged_with).length;
  }

  get totalDiners(): number {
    return this.openOrders.reduce((sum, o) => sum + (o.diners ?? 0), 0);
  }

  get totalSales(): number {
    return this.openOrders.reduce((sum, o) => {
      return sum + this.getOrderTotalAmount(o);
    }, 0);
  }

  onNewOrder() {
    const freeTable = this.filteredTables.find(t => !this.isOccupied(t.uuid));
    if (freeTable) {
      this.onTableClick(freeTable);
    }
  }

  getOrderTotal(tableUuid: string): number {
    const order = this.getOrder(tableUuid);
    if (!order) return 0;
    return this.getOrderTotalAmount(order);
  }

  private getLineSubtotal(price: number, quantity: number, discountAmount: number | undefined): number {
    return Math.max(0, price * quantity - (discountAmount ?? 0));
  }

  private getOrderSubtotalAmount(order: Order): number {
    const lineSubtotal = (order.lines ?? []).reduce(
      (sum, line) => sum + this.getLineSubtotal(line.price, line.quantity, line.discount_amount),
      0,
    );

    return Math.max(0, lineSubtotal - (order.discount_amount ?? 0));
  }

  private getOrderTaxAmount(order: Order): number {
    const lines = order.lines ?? [];
    const lineSubtotal = lines.reduce(
      (sum, line) => sum + this.getLineSubtotal(line.price, line.quantity, line.discount_amount),
      0,
    );

    if (lineSubtotal <= 0) {
      return 0;
    }

    const taxBeforeOrderDiscount = lines.reduce(
      (sum, line) => sum + this.getLineSubtotal(line.price, line.quantity, line.discount_amount) * line.tax_percentage / 100,
      0,
    );

    const ratio = Math.max(0, (lineSubtotal - (order.discount_amount ?? 0)) / lineSubtotal);

    return Math.round(taxBeforeOrderDiscount * ratio);
  }

  private getOrderTotalAmount(order: Order): number {
    if (typeof order.total === 'number') {
      return order.total;
    }

    return this.getOrderSubtotalAmount(order) + this.getOrderTaxAmount(order);
  }

  onTableClick(table: Table) {
    if (this.mergeMode) {
      this.onMergeTableClick(table);
      return;
    }

    if (!this.isOccupied(table.uuid) && !this.currentCashShift) {
      this.cashShiftAlert = 'No se puede comenzar un pedido si la caja esta cerrada.';
      return;
    }

    this.cashShiftAlert = '';

    this.selectedTable = table;
    this.showWaiterModal = true;
  }

  onWaiterSelected(waiter: User) {
    this.selectedWaiter = waiter;
    this.showWaiterModal = false;
    this.showPinModal = true;
  }

  onWaiterCancelled() {
    this.showWaiterModal = false;
    this.selectedTable = null;
    this.selectedWaiter = null;
  }

  onPinValidated(user: User) {
    this.validatedUser = user;
    this.showPinModal = false;

    if (this.isOccupied(this.selectedTable!.uuid)) {
      this.router.navigate(['/tpv/order', this.selectedTable!.uuid], {
        state: { user: this.validatedUser },
      });
    } else {
      this.showDinersModal = true;
    }
  }

  onPinCancelled() {
    this.showPinModal = false;
    this.selectedTable = null;
    this.selectedWaiter = null;
    this.validatedUser = null;
  }

  onDinersConfirmed(diners: number) {
    this.showDinersModal = false;
    this.orderService.openOrder(this.selectedTable!.uuid, this.validatedUser!.uuid, diners).subscribe({
      next: () => {
        this.cashShiftAlert = '';
        this.router.navigate(['/tpv/order', this.selectedTable!.uuid], {
          state: { user: this.validatedUser },
        });
      },
      error: (err) => {
        this.cashShiftAlert = err?.error?.message ?? 'No se pudo abrir la mesa.';
        this.logger.error('Error opening order:', err);
      },
    });
  }

  onDinersCancelled() {
    this.showDinersModal = false;
    this.selectedTable = null;
    this.selectedWaiter = null;
    this.validatedUser = null;
  }

  // === Merge tables ===

  startMergeMode() {
    this.mergeMode = true;
    this.mergeParent = null;
    this.mergeSelected = new Set();
    this.cashShiftAlert = 'Selecciona primero una mesa con pedido abierto y luego solo mesas vacías.';
  }

  cancelMergeMode() {
    this.mergeMode = false;
    this.mergeParent = null;
    this.mergeSelected = new Set();
    this.cashShiftAlert = '';
  }

  onMergeTableClick(table: Table) {
    if (!this.mergeParent) {
      if (!this.isOccupied(table.uuid)) {
        this.cashShiftAlert = 'La mesa principal debe tener un pedido abierto.';
        return;
      }

      this.cashShiftAlert = 'Ahora selecciona solo mesas vacías para unirlas a la mesa abierta.';
      this.mergeParent = table;
      return;
    }
    if (table.uuid === this.mergeParent.uuid) return;

    if (this.isOccupied(table.uuid)) {
      this.cashShiftAlert = 'No se pueden unir dos o más mesas con pedido abierto.';
      return;
    }

    if (this.mergeSelected.has(table.uuid)) {
      this.mergeSelected.delete(table.uuid);
      if (this.mergeSelected.size === 0) {
        this.cashShiftAlert = 'Selecciona al menos una mesa vacía para completar la unión.';
      }
    } else {
      this.mergeSelected.add(table.uuid);
      this.cashShiftAlert = '';
    }
  }

  isMergeSelected(tableUuid: string): boolean {
    return this.mergeSelected.has(tableUuid) || this.mergeParent?.uuid === tableUuid;
  }

  confirmMerge() {
    if (!this.mergeParent) {
      this.cashShiftAlert = 'Selecciona una mesa con pedido abierto.';
      return;
    }

    if (!this.isOccupied(this.mergeParent.uuid)) {
      this.cashShiftAlert = 'La mesa principal debe tener un pedido abierto.';
      return;
    }

    if (this.mergeSelected.size === 0) {
      this.cashShiftAlert = 'Selecciona al menos una mesa vacía para unir.';
      return;
    }

    const hasOccupiedChild = Array.from(this.mergeSelected).some((tableUuid) => this.isOccupied(tableUuid));
    if (hasOccupiedChild) {
      this.cashShiftAlert = 'Solo se puede unir una mesa con pedido abierto con mesas vacías.';
      return;
    }

    const childUuids = Array.from(this.mergeSelected);
    this.tableService.mergeTables(this.mergeParent.uuid, childUuids).subscribe({
      next: () => {
        this.cashShiftAlert = '';
        this.cancelMergeMode();
        this.loadData();
      },
      error: (err) => {
        this.cashShiftAlert = err?.error?.message ?? 'No se pudieron unir las mesas.';
        this.logger.error('Error merging tables:', err);
      },
    });
  }

  unmergeTable(parentUuid: string) {
    this.tableService.unmergeTables(parentUuid).subscribe({
      next: () => this.loadData(),
      error: (err) => this.logger.error('Error unmerging tables:', err),
    });
  }
}
