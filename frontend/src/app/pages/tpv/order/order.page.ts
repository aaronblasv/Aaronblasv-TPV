import { Component, OnInit, inject } from '@angular/core';
import { CommonModule, registerLocaleData } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { forkJoin } from 'rxjs';
import { OrderService } from '../../../services/api/order.service';
import { ProductService } from '../../../services/api/product.service';
import { FamilyService } from '../../../services/api/family.service';
import { TaxService } from '../../../services/api/tax.service';
import { TableService } from '../../../services/api/table.service';
import { PaymentService } from '../../../services/api/payment.service';
import { LoggerService } from '../../../services/logger.service';
import { PinModalComponent } from '../../../components/pin-modal/pin-modal.component';
import { PaymentModalComponent } from '../../../components/payment-modal/payment-modal.component';
import { SuccessModalComponent } from '../../../components/success-modal/success-modal.component';
import { WaiterModalComponent } from '../../../components/waiter-modal/waiter-modal.component';
import { Order, OrderLine } from '../../../types/order.model';
import { Product } from '../../../types/product.model';
import { Family } from '../../../types/family.model';
import { Tax } from '../../../types/tax.model';
import { Table } from '../../../types/table.model';
import { User } from '../../../types/user.model';
import { PaymentData } from '../../../types/payment.model';
import localeEs from '@angular/common/locales/es';

registerLocaleData(localeEs);

@Component({
  selector: 'app-order',
  standalone: true,
  imports: [CommonModule, IonicModule, PinModalComponent, PaymentModalComponent, SuccessModalComponent, WaiterModalComponent],
  templateUrl: './order.page.html',
  styleUrls: ['./order.page.scss'],
})
export class OrderPage implements OnInit {
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private orderService = inject(OrderService);
  private productService = inject(ProductService);
  private familyService = inject(FamilyService);
  private taxService = inject(TaxService);
  private tableService = inject(TableService);
  private paymentService = inject(PaymentService);
  private logger = inject(LoggerService);

  tableUuid = '';
  tableName = '';
  order: Order | null = null;
  products: Product[] = [];
  families: Family[] = [];
  taxes: Tax[] = [];
  tables: Table[] = [];
  currentUser: User | null = null;

  selectedFamilyUuid: string | null = null;
  showPaymentModal = false;
  showSuccessModal = false;
  showCloseWaiterModal = false;
  showClosePinModal = false;
  closeSelectedWaiter: User | null = null;
  totalPaid = 0;
  lastInvoiceNumber = '';
  lastTotalAmount = 0;

  ngOnInit() {
    this.tableUuid = this.route.snapshot.paramMap.get('tableUuid') || '';

    const nav = this.router.getCurrentNavigation();
    this.currentUser = nav?.extras?.state?.['user'] || history.state?.['user'];

    if (!this.currentUser) {
      this.router.navigate(['/tpv']);
      return;
    }

    this.resetState();
    this.loadData();
  }

  resetState() {
    this.selectedFamilyUuid = null;
    this.showPaymentModal = false;
    this.showSuccessModal = false;
    this.showCloseWaiterModal = false;
    this.showClosePinModal = false;
    this.closeSelectedWaiter = null;
    this.totalPaid = 0;
    this.lastInvoiceNumber = '';
    this.lastTotalAmount = 0;
    this.order = null;
  }

  loadData() {
    forkJoin({
      products: this.productService.getAllTpv(),
      families: this.familyService.getAllTpv(),
      taxes: this.taxService.getAllTpv(),
      tables: this.tableService.getAllTpv(),
    }).subscribe({
      next: ({ products, families, taxes, tables }) => {
        this.products = products.filter(p => p.active);
        this.families = families;
        this.taxes = taxes;
        this.tables = tables;
        this.tableName = tables.find(t => t.uuid === this.tableUuid)?.name || '';
        if (this.families.length > 0) {
          this.selectedFamilyUuid = this.families[0].uuid;
        }
        this.loadOrder();
      },
      error: (err) => this.logger.error(err),
    });
  }

  loadOrder() {
    this.orderService.getOrderByTable(this.tableUuid).subscribe({
      next: (order) => {
        if (!order) {
          this.router.navigate(['/tpv']);
          return;
        }
        this.order = order;
      },
      error: () => {
        this.router.navigate(['/tpv']);
      },
    });
  }

  get filteredProducts(): Product[] {
    if (!this.selectedFamilyUuid) return this.products;
    return this.products.filter(p => p.family_id === this.selectedFamilyUuid);
  }

  selectFamily(uuid: string | null) {
    this.selectedFamilyUuid = uuid;
  }

  getTaxPercentage(taxId: string): number {
    return this.taxes.find(t => t.uuid === taxId)?.percentage ?? 0;
  }

  getProductName(productId: string): string {
    return this.products.find(p => p.uuid === productId)?.name ?? 'Producto';
  }

  getLineSubtotal(line: OrderLine): number {
    return line.price * line.quantity;
  }

  getLineTax(line: OrderLine): number {
    return this.getLineSubtotal(line) * line.tax_percentage / 100;
  }

  getLineTotal(line: OrderLine): number {
    return this.getLineSubtotal(line) + this.getLineTax(line);
  }

  get orderSubtotal(): number {
    if (!this.order?.lines) return 0;
    return this.order.lines.reduce((sum, l) => sum + this.getLineSubtotal(l), 0);
  }

  get orderTax(): number {
    if (!this.order?.lines) return 0;
    return this.order.lines.reduce((sum, l) => sum + this.getLineTax(l), 0);
  }

  get orderTotal(): number {
    return this.orderSubtotal + this.orderTax;
  }

  addProduct(product: Product) {
    const existingLine = this.order?.lines?.find(l => l.product_id === product.uuid);

    if (existingLine) {
      const newQty = existingLine.quantity + 1;
      this.orderService.updateLineQuantity(this.order!.uuid, existingLine.uuid, newQty).subscribe({
        next: () => { existingLine.quantity = newQty; },
        error: (err) => this.logger.error('Error updating line:', err),
      });
    } else {
      const taxPercentage = this.getTaxPercentage(product.tax_id);
      this.orderService.addLine(this.order!.uuid, product.uuid, this.currentUser!.id, 1, product.price, taxPercentage).subscribe({
        next: (line: OrderLine) => { this.order!.lines.push(line); },
        error: (err) => this.logger.error('Error adding line:', err),
      });
    }
  }

  incrementLine(line: OrderLine) {
    const newQty = line.quantity + 1;
    this.orderService.updateLineQuantity(this.order!.uuid, line.uuid, newQty).subscribe({
      next: () => { line.quantity = newQty; },
      error: (err) => this.logger.error('Error updating line:', err),
    });
  }

  decrementLine(line: OrderLine) {
    if (line.quantity <= 1) {
      this.removeLine(line);
      return;
    }
    const newQty = line.quantity - 1;
    this.orderService.updateLineQuantity(this.order!.uuid, line.uuid, newQty).subscribe({
      next: () => { line.quantity = newQty; },
      error: (err) => this.logger.error('Error updating line:', err),
    });
  }

  removeLine(line: OrderLine) {
    this.orderService.removeLine(this.order!.uuid, line.uuid).subscribe({
      next: () => { this.order!.lines = this.order!.lines.filter(l => l.uuid !== line.uuid); },
      error: (err) => this.logger.error('Error removing line:', err),
    });
  }

  cancelOrder() {
    this.orderService.cancelOrder(this.order!.uuid).subscribe({
      next: () => { this.router.navigate(['/tpv'], { replaceUrl: true }); },
      error: (err) => this.logger.error('Error cancelling order:', err),
    });
  }

  requestClose() {
    this.showCloseWaiterModal = true;
  }

  onCloseWaiterSelected(waiter: User) {
    this.closeSelectedWaiter = waiter;
    this.showCloseWaiterModal = false;
    this.showClosePinModal = true;
  }

  onCloseWaiterCancelled() {
    this.showCloseWaiterModal = false;
    this.closeSelectedWaiter = null;
  }

  onClosePinValidated(user: User) {
    this.logger.log('onClosePinValidated called with user:', user);
    this.showClosePinModal = false;
    this.closeSelectedWaiter = null;
    this.currentUser = user;
    this.showPaymentModal = true;
  }

  onClosePinCancelled() {
    this.showClosePinModal = false;
    this.closeSelectedWaiter = null;
  }

  onPaymentRegistered(payment: PaymentData) {
    this.logger.log('onPaymentRegistered:', payment);
    this.paymentService.registerPayment(
      this.order!.uuid,
      Math.round(payment.amount),
      payment.method,
      payment.description,
    ).subscribe({
      next: () => {
        this.logger.log('Payment registered successfully');
        this.totalPaid += Math.round(payment.amount);
        this.logger.log('totalPaid:', this.totalPaid, '/ orderTotal:', this.orderTotal);
        if (this.totalPaid >= this.orderTotal) {
          this.logger.log('Order fully paid — proceeding to close');
          this.onPaymentComplete();
        }
      },
      error: (err) => this.logger.error('Error registering payment:', err),
    });
  }

  onPaymentComplete() {
    this.logger.log('onPaymentComplete', { totalPaid: this.totalPaid, orderTotal: this.orderTotal });
    this.showPaymentModal = false;
    this.lastTotalAmount = this.orderTotal;

    if (!this.order?.uuid) {
      this.logger.error('Order not found or invalid');
      this.showSuccessModal = true;
      return;
    }

    this.paymentService.generateInvoice(this.order.uuid).subscribe({
      next: (invoice) => {
        this.logger.log('Invoice generated:', invoice);
        this.lastInvoiceNumber = invoice.invoice_number || 'INV-XXXXXX-XXXX';

        if (!this.currentUser?.uuid) {
          this.logger.error('Current user not found or invalid');
          this.showSuccessModal = true;
          return;
        }

        this.orderService.closeOrder(this.order!.uuid, this.currentUser.uuid).subscribe({
          next: () => {
            this.logger.log('Order closed successfully');
            this.showSuccessModal = true;
          },
          error: (err) => {
            this.logger.error('Error closing order:', err);
            this.showSuccessModal = true;
          },
        });
      },
      error: (err) => {
        this.logger.error('Error generating invoice:', err);
        if (!this.currentUser?.uuid) {
          this.showSuccessModal = true;
          return;
        }
        this.orderService.closeOrder(this.order!.uuid, this.currentUser.uuid).subscribe({
          next: () => { this.showSuccessModal = true; },
          error: (closeErr) => {
            this.logger.error('Error closing order:', closeErr);
            this.showSuccessModal = true;
          },
        });
      },
    });
  }

  onSuccessModalClose() {
    this.showSuccessModal = false;
    this.router.navigate(['/tpv'], { replaceUrl: true });
  }

  onPaymentCancelled() {
    this.showPaymentModal = false;
  }

  goBack() {
    this.router.navigate(['/tpv'], { replaceUrl: true });
  }
}
