import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { forkJoin } from 'rxjs';
import { ZoneService } from '../../../services/api/zone.service';
import { TableService } from '../../../services/api/table.service';
import { OrderService } from '../../../services/api/order.service';
import { LoggerService } from '../../../services/logger.service';
import { PinModalComponent } from '../../../components/pin-modal/pin-modal.component';
import { DinersModalComponent } from '../../../components/diners-modal/diners-modal.component';
import { WaiterModalComponent } from '../../../components/waiter-modal/waiter-modal.component';
import { Zone } from '../../../types/zone.model';
import { Table } from '../../../types/table.model';
import { Order } from '../../../types/order.model';
import { User } from '../../../types/user.model';

@Component({
  selector: 'app-floor',
  standalone: true,
  imports: [CommonModule, IonicModule, PinModalComponent, DinersModalComponent, WaiterModalComponent],
  templateUrl: './floor.page.html',
  styleUrls: ['./floor.page.scss'],
})
export class FloorPage implements OnInit {
  private router = inject(Router);
  private zoneService = inject(ZoneService);
  private tableService = inject(TableService);
  private orderService = inject(OrderService);
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

  ngOnInit() {
    this.loadData();
  }

  ionViewWillEnter() {
    this.logger.log('ionViewWillEnter triggered - reloading data');
    this.loadData();
  }

  ionViewDidLoad() {
    this.logger.log('ionViewDidLoad triggered - reloading data');
    this.loadData();
  }

  loadData() {
    this.logger.log('FloorPage: Starting to load data...');
    forkJoin({
      zones: this.zoneService.getAllTpv(),
      tables: this.tableService.getAllTpv(),
      openOrders: this.orderService.getAllOpen(),
    }).subscribe({
      next: ({ zones, tables, openOrders }) => {
        this.logger.log('FloorPage: Data loaded —', zones.length, 'zones,', tables.length, 'tables,', openOrders.length, 'open orders');
        this.zones = zones;
        this.tables = tables;
        this.openOrders = openOrders;

        tables.forEach(table => {
          this.logger.log(`  Mesa ${table.name}: ${this.isOccupied(table.uuid) ? 'OCUPADA' : 'LIBRE'}`);
        });
      },
      error: (err) => this.logger.error('FloorPage: Error loading data', err),
    });
  }

  get filteredTables(): Table[] {
    if (!this.selectedZoneUuid) return this.tables;
    return this.tables.filter(t => t.zone_id === this.selectedZoneUuid);
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

  onTableClick(table: Table) {
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
    this.orderService.openOrder(this.selectedTable!.uuid, this.validatedUser!.id, diners).subscribe({
      next: () => {
        this.router.navigate(['/tpv/order', this.selectedTable!.uuid], {
          state: { user: this.validatedUser },
        });
      },
      error: (err) => this.logger.error('Error opening order:', err),
    });
  }

  onDinersCancelled() {
    this.showDinersModal = false;
    this.selectedTable = null;
    this.selectedWaiter = null;
    this.validatedUser = null;
  }
}
