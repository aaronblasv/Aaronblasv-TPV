import { Component, OnInit, OnDestroy, inject, ViewEncapsulation, ElementRef, HostListener, ViewChild } from '@angular/core';
import { CommonModule, DecimalPipe } from '@angular/common';
import { Router } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { peopleOutline } from 'ionicons/icons';
import { catchError, forkJoin, of } from 'rxjs';
import { ZoneService } from '../../../services/api/zone.service';
import { TableService } from '../../../services/api/table.service';
import { OrderService } from '../../../services/api/order.service';
import { CashShiftService } from '../../../services/api/cash-shift.service';
import { LoggerService } from '../../../services/logger.service';
import { TpvSessionService } from '../../../services/tpv-session.service';
import { TpvSidebarComponent } from '../../../components/tpv-sidebar/tpv-sidebar.component';
import { PinModalComponent } from '../../../components/pin-modal/pin-modal.component';
import { DinersModalComponent } from '../../../components/diners-modal/diners-modal.component';
import { WaiterModalComponent } from '../../../components/waiter-modal/waiter-modal.component';
import { Zone } from '../../../types/zone.model';
import { Table } from '../../../types/table.model';
import { Order } from '../../../types/order.model';
import { User } from '../../../types/user.model';
import { CashShiftSummary } from '../../../types/cash-shift.model';

interface TablePosition {
  x: number;
  y: number;
}

@Component({
  selector: 'app-floor',
  standalone: true,
  imports: [CommonModule, IonicModule, TpvSidebarComponent, PinModalComponent, DinersModalComponent, WaiterModalComponent],
  templateUrl: './floor.page.html',
  styleUrls: ['./floor.page.scss'],
  encapsulation: ViewEncapsulation.None,
})
export class FloorPage implements OnInit, OnDestroy {
  @ViewChild('tableCanvas') tableCanvasRef?: ElementRef<HTMLElement>;

  private router = inject(Router);
  private zoneService = inject(ZoneService);
  private tableService = inject(TableService);
  private orderService = inject(OrderService);
  private cashShiftService = inject(CashShiftService);
  private logger = inject(LoggerService);
  private tpvSessionService = inject(TpvSessionService);

  zones: Zone[] = [];
  tables: Table[] = [];
  openOrders: Order[] = [];
  selectedZoneUuid: string | null = null;

  showWaiterModal = false;
  showPinModal = false;
  showDinersModal = false;
  selectedTable: Table | null = null;
  selectedWaiter: User | null = null;
  activeUser: User | null = null;

  // Merge tables state
  mergeMode = false;
  mergeParent: Table | null = null;
  mergeSelected: Set<string> = new Set();

  // Clock
  currentTime = '';
  today = '';
  private clockInterval: ReturnType<typeof setInterval> | null = null;

  currentCashShift: CashShiftSummary | null = null;
  cashShiftAlert = '';
  private cashShiftAlertTimeout: ReturnType<typeof setTimeout> | null = null;

  isLayoutEditMode = false;
  draggingTableUuid: string | null = null;
  tableCanvasHeight = 420;
  readonly peopleOutlineIcon = peopleOutline;
  readonly tableCardWidth = 208;
  readonly tableCardHeight = 172;
  readonly tableSnap = 24;
  private readonly tableCanvasPadding = 24;
  private readonly layoutStorageKey = 'tpv-floor-layout-v1';
  private zoneLayouts: Record<string, Record<string, TablePosition>> = {};
  private dragPointerOffset = { x: 0, y: 0 };

  constructor() {
    this.zoneLayouts = this.loadLayoutsFromStorage();
  }

  ngOnInit() {
    this.restoreActiveUser();
    this.promptSessionLoginIfNeeded();
    this.loadData();
    this.updateClock();
    this.clockInterval = setInterval(() => this.updateClock(), 1000);
  }

  ngOnDestroy() {
    if (this.clockInterval) clearInterval(this.clockInterval);
    if (this.cashShiftAlertTimeout) clearTimeout(this.cashShiftAlertTimeout);
  }

  private updateClock() {
    const now = new Date();
    this.currentTime = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    this.today = now.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
  }

  ionViewWillEnter() {
    this.restoreActiveUser();
    this.promptSessionLoginIfNeeded();
    this.logger.log('ionViewWillEnter triggered - reloading data');
    this.loadData();
  }

  logoutTpvSession() {
    this.clearActiveTpvSession();
    this.promptSessionLoginIfNeeded();
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
        this.ensureSelectedZone();
        this.syncZoneLayout();

        tables.forEach(table => {
          this.logger.log(`  Mesa ${table.name}: ${this.isOccupied(table.uuid) ? 'OCUPADA' : 'LIBRE'}`);
        });
      },
      error: (err) => this.logger.error('FloorPage: Unexpected error loading data', err),
    });
  }

  get filteredTables(): Table[] {
    if (!this.selectedZoneUuid) {
      return [];
    }

    return this.tables.filter(t => t.zone_id === this.selectedZoneUuid);
  }

  get selectedZoneTables(): Table[] {
    return this.filteredTables;
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
    if (this.isLayoutEditMode) {
      this.showCashShiftAlert('Guarda la distribución antes de cambiar de zona.');
      return;
    }

    this.selectedZoneUuid = uuid;
    this.clearCashShiftAlert();
    this.syncZoneLayout();
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
    return this.tables.filter(t => t.zone_id === zoneId).length;
  }

  get freeTables(): number {
    return this.filteredTables.filter(t => !this.isOccupied(t.uuid)).length;
  }

  get occupiedTables(): number {
    return this.filteredTables.filter(t => this.isOccupied(t.uuid)).length;
  }

  get allVisibleTables(): number {
    return this.tables.length;
  }

  isMergedChild(tableUuid: string): boolean {
    return this.tables.some(table => table.uuid === tableUuid && !!table.merged_with);
  }

  getMergedParentName(tableUuid: string): string {
    const table = this.tables.find(item => item.uuid === tableUuid);
    if (!table?.merged_with) {
      return '';
    }

    return this.tables.find(item => item.uuid === table.merged_with)?.name ?? '';
  }

  getTablePosition(table: Table): TablePosition {
    const zoneLayout = this.zoneLayouts[table.zone_id];
    if (zoneLayout?.[table.uuid]) {
      return zoneLayout[table.uuid];
    }

    this.syncZoneLayout();
    return this.zoneLayouts[table.zone_id]?.[table.uuid] ?? { x: this.tableCanvasPadding, y: this.tableCanvasPadding };
  }

  isDraggingTable(tableUuid: string): boolean {
    return this.draggingTableUuid === tableUuid;
  }

  onLayoutEditAction() {
    if (this.isLayoutEditMode) {
      this.saveLayout();
      return;
    }

    this.isLayoutEditMode = true;
    this.syncZoneLayout();
    this.clearCashShiftAlert();
  }

  onTablePointerDown(event: PointerEvent, table: Table) {
    if (!this.isLayoutEditMode || !this.selectedZoneUuid || !this.tableCanvasRef) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    const canvasBounds = this.tableCanvasRef.nativeElement.getBoundingClientRect();
    const position = this.getTablePosition(table);

    this.draggingTableUuid = table.uuid;
    this.dragPointerOffset = {
      x: event.clientX - canvasBounds.left - position.x,
      y: event.clientY - canvasBounds.top - position.y,
    };
  }

  @HostListener('window:pointermove', ['$event'])
  onWindowPointerMove(event: PointerEvent) {
    if (!this.draggingTableUuid || !this.selectedZoneUuid || !this.tableCanvasRef) {
      return;
    }

    const canvas = this.tableCanvasRef.nativeElement;
    const canvasBounds = canvas.getBoundingClientRect();
    const canvasWidth = Math.max(canvasBounds.width, this.tableCardWidth + this.tableCanvasPadding * 2);

    const rawX = event.clientX - canvasBounds.left - this.dragPointerOffset.x;
    const rawY = event.clientY - canvasBounds.top - this.dragPointerOffset.y;

    const maxX = Math.max(this.tableCanvasPadding, canvasWidth - this.tableCardWidth - this.tableCanvasPadding);
    const nextX = this.snap(this.clamp(rawX, this.tableCanvasPadding, maxX));
    const nextY = this.snap(this.clamp(rawY, this.tableCanvasPadding, 4000));

    this.zoneLayouts[this.selectedZoneUuid] ??= {};
    this.zoneLayouts[this.selectedZoneUuid][this.draggingTableUuid] = { x: nextX, y: nextY };
    this.updateTableCanvasHeight();
  }

  @HostListener('window:pointerup')
  @HostListener('window:pointercancel')
  onWindowPointerUp() {
    this.draggingTableUuid = null;
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
      (sum, line) => sum + Math.round(this.getLineSubtotal(line.price, line.quantity, line.discount_amount) * line.tax_percentage / 100),
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
    if (!this.ensureActiveSession(table)) {
      return;
    }

    if (this.isLayoutEditMode) {
      return;
    }

    if (this.isMergedChild(table.uuid)) {
      const parentName = this.getMergedParentName(table.uuid);
      this.showCashShiftAlert(parentName
        ? `${table.name} está unida a ${parentName}. Accede desde la mesa principal.`
        : `${table.name} está unida a otra mesa.`);
      return;
    }

    if (this.mergeMode) {
      this.onMergeTableClick(table);
      return;
    }

    if (!this.isOccupied(table.uuid) && !this.currentCashShift) {
      this.showCashShiftAlert('No se puede comenzar un pedido si la caja esta cerrada.');
      return;
    }

    this.clearCashShiftAlert();

    this.selectedTable = table;

    if (this.isOccupied(this.selectedTable.uuid)) {
      this.router.navigate(['/tpv/order', this.selectedTable.uuid]);
    } else {
      this.showDinersModal = true;
    }
  }

  onWaiterSelected(waiter: User) {
    this.selectedWaiter = waiter;
    this.showWaiterModal = false;
    this.showPinModal = true;
  }

  onWaiterCancelled() {
    if (!this.activeUser) {
      this.promptSessionLoginIfNeeded();
      return;
    }

    this.showWaiterModal = false;
    this.selectedTable = null;
    this.selectedWaiter = null;
  }

  onPinValidated(user: User) {
    this.activeUser = user;
    this.tpvSessionService.setUser(user);
    this.showPinModal = false;
    this.selectedWaiter = null;

    if (!this.selectedTable) {
      return;
    }

    if (this.isOccupied(this.selectedTable!.uuid)) {
      this.router.navigate(['/tpv/order', this.selectedTable!.uuid]);
    } else {
      this.showDinersModal = true;
    }
  }

  onPinCancelled() {
    this.showPinModal = false;
    this.selectedWaiter = null;

    if (!this.activeUser) {
      this.selectedTable = null;
      this.promptSessionLoginIfNeeded();
      return;
    }

    this.selectedTable = null;
  }

  onDinersConfirmed(diners: number) {
    this.showDinersModal = false;
    this.orderService.openOrder(this.selectedTable!.uuid, this.activeUser!.uuid, diners).subscribe({
      next: () => {
        this.clearCashShiftAlert();
        this.router.navigate(['/tpv/order', this.selectedTable!.uuid]);
      },
      error: (err) => {
        this.showCashShiftAlert(err?.error?.message ?? 'No se pudo abrir la mesa.');
        this.logger.error('Error opening order:', err);
      },
    });
  }

  onDinersCancelled() {
    this.showDinersModal = false;
    this.selectedTable = null;
    this.selectedWaiter = null;
  }

  // === Merge tables ===

  startMergeMode() {
    if (this.isLayoutEditMode) {
      return;
    }

    this.mergeMode = true;
    this.mergeParent = null;
    this.mergeSelected = new Set();
    this.clearCashShiftAlert();
  }

  cancelMergeMode() {
    this.mergeMode = false;
    this.mergeParent = null;
    this.mergeSelected = new Set();
    this.clearCashShiftAlert();
  }

  onMergeTableClick(table: Table) {
    if (this.isMergedChild(table.uuid) || this.isParentMerged(table.uuid)) {
      this.showCashShiftAlert('No se pueden volver a unir mesas que ya forman parte de una agrupación.');
      return;
    }

    if (!this.mergeParent) {
      if (!this.isOccupied(table.uuid)) {
        this.showCashShiftAlert('La mesa principal debe tener un pedido abierto.');
        return;
      }

      this.clearCashShiftAlert();
      this.mergeParent = table;
      return;
    }
    if (table.uuid === this.mergeParent.uuid) return;

    if (this.isOccupied(table.uuid)) {
      this.showCashShiftAlert('No se pueden unir dos o más mesas con pedido abierto.');
      return;
    }

    if (this.mergeSelected.has(table.uuid)) {
      this.mergeSelected.delete(table.uuid);
    } else {
      this.mergeSelected.add(table.uuid);
      this.clearCashShiftAlert();
    }
  }

  isMergeSelected(tableUuid: string): boolean {
    return this.mergeSelected.has(tableUuid) || this.mergeParent?.uuid === tableUuid;
  }

  confirmMerge() {
    if (!this.mergeParent) {
      this.showCashShiftAlert('Selecciona una mesa con pedido abierto.');
      return;
    }

    if (!this.isOccupied(this.mergeParent.uuid)) {
      this.showCashShiftAlert('La mesa principal debe tener un pedido abierto.');
      return;
    }

    if (this.mergeSelected.size === 0) {
      this.showCashShiftAlert('Selecciona al menos una mesa vacía para unir.');
      return;
    }

    const hasOccupiedChild = Array.from(this.mergeSelected).some((tableUuid) => this.isOccupied(tableUuid));
    if (hasOccupiedChild) {
      this.showCashShiftAlert('Solo se puede unir una mesa con pedido abierto con mesas vacías.');
      return;
    }

    const childUuids = Array.from(this.mergeSelected);
    this.tableService.mergeTables(this.mergeParent.uuid, childUuids).subscribe({
      next: () => {
        this.clearCashShiftAlert();
        this.cancelMergeMode();
        this.loadData();
      },
      error: (err) => {
        this.showCashShiftAlert(err?.error?.message ?? 'No se pudieron unir las mesas.');
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

  private ensureSelectedZone() {
    if (this.selectedZoneUuid && this.zones.some(zone => zone.uuid === this.selectedZoneUuid)) {
      return;
    }

    this.selectedZoneUuid = this.zones[0]?.uuid ?? null;
  }

  private syncZoneLayout() {
    if (!this.selectedZoneUuid) {
      this.tableCanvasHeight = 420;
      return;
    }

    const zoneTables = this.tables.filter(table => table.zone_id === this.selectedZoneUuid);
    const existing = this.zoneLayouts[this.selectedZoneUuid] ?? {};
    const defaults = this.buildDefaultZoneLayout(zoneTables);
    const nextLayout: Record<string, TablePosition> = {};

    zoneTables.forEach(table => {
      nextLayout[table.uuid] = existing[table.uuid] ?? defaults[table.uuid];
    });

    this.zoneLayouts[this.selectedZoneUuid] = nextLayout;
    this.updateTableCanvasHeight();
  }

  private buildDefaultZoneLayout(zoneTables: Table[]): Record<string, TablePosition> {
    const columns = 4;
    const gap = 24;

    return zoneTables.reduce<Record<string, TablePosition>>((acc, table, index) => {
      const column = index % columns;
      const row = Math.floor(index / columns);

      acc[table.uuid] = {
        x: this.tableCanvasPadding + column * (this.tableCardWidth + gap),
        y: this.tableCanvasPadding + row * (this.tableCardHeight + gap),
      };

      return acc;
    }, {});
  }

  private updateTableCanvasHeight() {
    if (!this.selectedZoneUuid) {
      this.tableCanvasHeight = 420;
      return;
    }

    const positions = Object.values(this.zoneLayouts[this.selectedZoneUuid] ?? {});
    const maxBottom = positions.reduce((max, position) => Math.max(max, position.y + this.tableCardHeight), 0);
    this.tableCanvasHeight = Math.max(420, maxBottom + this.tableCanvasPadding);
  }

  private saveLayout() {
    this.saveLayoutsToStorage();
    this.isLayoutEditMode = false;
    this.draggingTableUuid = null;
    this.clearCashShiftAlert();
  }

  private showCashShiftAlert(message: string) {
    if (this.cashShiftAlertTimeout) {
      clearTimeout(this.cashShiftAlertTimeout);
    }

    this.cashShiftAlert = message;
    this.cashShiftAlertTimeout = setTimeout(() => {
      this.cashShiftAlert = '';
      this.cashShiftAlertTimeout = null;
    }, 2200);
  }

  private clearCashShiftAlert() {
    if (this.cashShiftAlertTimeout) {
      clearTimeout(this.cashShiftAlertTimeout);
      this.cashShiftAlertTimeout = null;
    }

    this.cashShiftAlert = '';
  }

  private loadLayoutsFromStorage(): Record<string, Record<string, TablePosition>> {
    try {
      const raw = localStorage.getItem(this.layoutStorageKey);
      return raw ? JSON.parse(raw) : {};
    } catch {
      return {};
    }
  }

  private saveLayoutsToStorage() {
    try {
      localStorage.setItem(this.layoutStorageKey, JSON.stringify(this.zoneLayouts));
    } catch {
      this.logger.error('No se pudo guardar la distribución del plano.');
    }
  }

  private snap(value: number): number {
    return Math.round(value / this.tableSnap) * this.tableSnap;
  }

  private clamp(value: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, value));
  }

  private restoreActiveUser() {
    this.activeUser = this.tpvSessionService.getUser();
  }

  private promptSessionLoginIfNeeded() {
    if (this.activeUser) {
      this.showWaiterModal = false;
      return;
    }

    this.showWaiterModal = true;
    this.showPinModal = false;
    this.selectedWaiter = null;
  }

  private ensureActiveSession(table?: Table): boolean {
    if (this.activeUser) {
      return true;
    }

    this.selectedTable = table ?? null;
    this.promptSessionLoginIfNeeded();
    return false;
  }

  private clearActiveTpvSession() {
    this.tpvSessionService.clear();
    this.activeUser = null;
    this.selectedTable = null;
    this.selectedWaiter = null;
    this.showWaiterModal = false;
    this.showPinModal = false;
    this.showDinersModal = false;
  }
}
