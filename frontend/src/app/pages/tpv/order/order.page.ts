import { Component, OnInit, ViewEncapsulation, inject } from '@angular/core';
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
import { SuccessModalComponent } from '../../../components/success-modal/success-modal.component';
import { WaiterModalComponent } from '../../../components/waiter-modal/waiter-modal.component';

import { DiscountModalComponent, DiscountResult } from '../../../components/discount-modal/discount-modal.component';
import { DinersModalComponent } from '../../../components/diners-modal/diners-modal.component';
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
  imports: [CommonModule, IonicModule, PinModalComponent, SuccessModalComponent, WaiterModalComponent, DiscountModalComponent, DinersModalComponent],
  templateUrl: './order.page.html',
  styleUrls: ['./order.page.scss'],
  encapsulation: ViewEncapsulation.None,
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
  currentTab: 'summary' | 'order' = 'order';
  showSuccessModal = false;
  showCloseWaiterModal = false;
  showClosePinModal = false;
  showTransferModal = false;
  showDiscountModal = false;
  showChangeDinersModal = false;
  showKitchenSentModal = false;
  discountModalTitle = 'Descuento';
  discountModalCurrentType: 'amount' | 'percentage' | null = null;
  discountModalCurrentValue = 0;
  discountTarget: 'order' | OrderLine | null = null;
  closeSelectedWaiter: User | null = null;
  availableTransferTables: Table[] = [];
  totalPaid = 0;
  lastInvoiceNumber = '';
  lastTotalAmount = 0;

  // Payment view state
  payMode: 'payment' | 'split' = 'payment';
  payMethod: 'cash' | 'card' | 'bizum' = 'cash';
  payInput = '';
  splitShares: { index: number; amount: number; method: 'cash' | 'card' | 'bizum'; paid: boolean }[] = [];
  payInputDisplay = '0.00';
  payInputCents = 0;
  numpadKeys: string[] = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', 'back'];
  get pendingAmount(): number {
    return Math.max(0, this.orderTotal - this.totalPaid);
  }

  get payChange(): number {
    const pending = this.orderTotal - this.totalPaid;
    return Math.max(0, this.payInputCents - pending);
  }

  updatePayInputDisplay() {
    if (!this.payInput) {
      this.payInputDisplay = '0.00';
      this.payInputCents = 0;
      return;
    }
    const num = parseFloat(this.payInput);
    if (isNaN(num)) {
      this.payInputDisplay = '0.00';
      this.payInputCents = 0;
      return;
    }
    this.payInputDisplay = num.toFixed(2);
    this.payInputCents = Math.round(num * 100);
  }

  onNumpadKey(key: string) {
    if (key === 'back') {
      this.payInput = this.payInput.slice(0, -1);
    } else if (key === '.') {
      if (!this.payInput.includes('.')) {
        this.payInput += this.payInput ? '.' : '0.';
      }
    } else {
      if (this.payInput.includes('.') && this.payInput.split('.')[1].length >= 2) return;
      this.payInput += key;
    }
    this.updatePayInputDisplay();
  }

  setPayAmount(cents: number) {
    this.payInput = (cents / 100).toFixed(2);
    this.updatePayInputDisplay();
  }

  goToPayment() {
    this.payMode = 'payment';
    this.currentTab = 'summary';
  }

  payCustomAmount() {
    if (this.payInputCents <= 0 || this.pendingAmount <= 0) return;
    const amount = Math.min(this.payInputCents, this.pendingAmount);
    this.onPaymentRegistered({ amount, method: this.payMethod, description: 'Pago' });
    this.payInput = '';
    this.updatePayInputDisplay();
  }

  switchToSplit() {
    this.payMode = 'split';
    this.buildSplitShares();
  }

  switchToPayment() {
    this.payMode = 'payment';
    this.payInput = '';
    this.updatePayInputDisplay();
  }

  buildSplitShares() {
    const count = Math.max(1, this.order?.diners || 1);
    const pending = this.pendingAmount;
    const base = Math.floor(pending / count);
    const remainder = pending % count;
    this.splitShares = Array.from({ length: count }, (_, i) => ({
      index: i,
      amount: base + (i < remainder ? 1 : 0),
      method: 'cash' as const,
      paid: false,
    }));
  }

  paySplitShare(share: { index: number; amount: number; method: 'cash' | 'card' | 'bizum'; paid: boolean }) {
    if (share.paid || share.amount <= 0) return;
    share.paid = true;
    this.onPaymentRegistered({
      amount: share.amount,
      method: share.method,
      description: `Comensal ${share.index + 1}/${this.splitShares.length}`,
    });
  }

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
    this.currentTab = 'order';
    this.showSuccessModal = false;
    this.showCloseWaiterModal = false;
    this.showClosePinModal = false;
    this.showTransferModal = false;
    this.closeSelectedWaiter = null;
    this.availableTransferTables = [];
    this.totalPaid = 0;
    this.lastInvoiceNumber = '';
    this.lastTotalAmount = 0;
    this.showKitchenSentModal = false;
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

  getProductName(productId: string): string {
    return this.products.find(p => p.uuid === productId)?.name ?? 'Producto';
  }

  getFamilyName(familyId: string): string {
    return this.families.find(f => f.uuid === familyId)?.name ?? '';
  }

  getProductFamily(productId: string): string {
    const product = this.products.find(p => p.uuid === productId);
    return product ? this.getFamilyName(product.family_id) : '';
  }

  get totalItems(): number {
    return this.order?.lines?.reduce((sum, l) => sum + l.quantity, 0) ?? 0;
  }

  getLineSubtotal(line: OrderLine): number {
    return Math.max(0, (line.price * line.quantity) - (line.discount_amount || 0));
  }

  getLineTax(line: OrderLine): number {
    return this.getLineSubtotal(line) * line.tax_percentage / 100;
  }

  getLineTotal(line: OrderLine): number {
    return this.getLineSubtotal(line) + this.getLineTax(line);
  }

  get orderSubtotal(): number {
    if (!this.order?.lines) return 0;
    const lineSubtotal = this.order.lines.reduce((sum, line) => sum + this.getLineSubtotal(line), 0);
    return Math.max(0, lineSubtotal - this.getOrderDiscountAmount());
  }

  get orderTax(): number {
    if (!this.order?.lines) return 0;
    const lineSubtotal = this.order.lines.reduce((sum, line) => sum + this.getLineSubtotal(line), 0);
    if (lineSubtotal <= 0) return 0;

    const taxBeforeOrderDiscount = this.order.lines.reduce((sum, line) => sum + this.getLineTax(line), 0);
    const ratio = Math.max(0, (lineSubtotal - this.getOrderDiscountAmount()) / lineSubtotal);
    return Math.round(taxBeforeOrderDiscount * ratio);
  }

  get orderTotal(): number {
    return this.orderSubtotal + this.orderTax;
  }

  addProduct(product: Product) {
    const existingLine = this.order?.lines?.find(l => l.product_id === product.uuid);

    if (existingLine) {
      const newQty = existingLine.quantity + 1;
      this.orderService.updateLineQuantity(this.order!.uuid, existingLine.uuid, newQty).subscribe({
        next: () => { this.loadOrder(); },
        error: (err) => this.logger.error('Error updating line:', err),
      });
    } else {
      this.orderService.addLine(this.order!.uuid, product.uuid, this.currentUser!.uuid, 1).subscribe({
        next: () => { this.loadOrder(); },
        error: (err) => this.logger.error('Error adding line:', err),
      });
    }
  }

  incrementLine(line: OrderLine) {
    const newQty = line.quantity + 1;
    this.orderService.updateLineQuantity(this.order!.uuid, line.uuid, newQty).subscribe({
      next: () => { this.loadOrder(); },
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
      next: () => { this.loadOrder(); },
      error: (err) => this.logger.error('Error updating line:', err),
    });
  }

  removeLine(line: OrderLine) {
    this.orderService.removeLine(this.order!.uuid, line.uuid).subscribe({
      next: () => { this.loadOrder(); },
      error: (err) => this.logger.error('Error removing line:', err),
    });
  }

  requestOrderDiscount() {
    if (!this.order) return;
    this.discountModalTitle = 'Descuento total de la comanda';
    this.discountModalCurrentType = this.order.discount_type;
    this.discountModalCurrentValue = this.order.discount_value;
    this.discountTarget = 'order';
    this.showDiscountModal = true;
  }

  requestLineDiscount(line: OrderLine) {
    if (!this.order) return;
    this.discountModalTitle = 'Descuento de línea';
    this.discountModalCurrentType = line.discount_type;
    this.discountModalCurrentValue = line.discount_value;
    this.discountTarget = line;
    this.showDiscountModal = true;
  }

  onDiscountConfirmed(result: DiscountResult) {
    this.showDiscountModal = false;
    if (!this.order) return;

    if (this.discountTarget === 'order') {
      this.orderService.updateOrderDiscount(this.order.uuid, result.type, result.value).subscribe({
        next: () => this.loadOrder(),
        error: (err) => this.logger.error('Error updating order discount:', err),
      });
    } else if (this.discountTarget) {
      const line = this.discountTarget as OrderLine;
      this.orderService.updateLineDiscount(this.order.uuid, line.uuid, result.type, result.value).subscribe({
        next: () => this.loadOrder(),
        error: (err) => this.logger.error('Error updating line discount:', err),
      });
    }
  }

  onDiscountCancelled() {
    this.showDiscountModal = false;
  }

  changeDiners() {
    if (!this.order) return;
    this.showChangeDinersModal = true;
  }

  onChangeDinersConfirmed(diners: number) {
    this.showChangeDinersModal = false;
    if (!this.order) return;
    this.orderService.updateDiners(this.order.uuid, diners).subscribe({
      next: () => this.loadOrder(),
      error: (err) => this.logger.error('Error updating diners:', err),
    });
  }

  onChangeDinersCancelled() {
    this.showChangeDinersModal = false;
  }

  cancelOrder() {
    this.orderService.cancelOrder(this.order!.uuid).subscribe({
      next: () => { this.router.navigate(['/tpv'], { replaceUrl: true }); },
      error: (err) => this.logger.error('Error cancelling order:', err),
    });
  }

  requestTransfer() {
    if (!this.order?.uuid) {
      return;
    }

    this.orderService.getAllOpen().subscribe({
      next: (openOrders) => {
        const occupiedTableIds = new Set(
          openOrders
            .filter(openOrder => openOrder.uuid !== this.order?.uuid)
            .map(openOrder => openOrder.table_id),
        );

        this.availableTransferTables = this.tables.filter(table =>
          table.uuid !== this.tableUuid && !occupiedTableIds.has(table.uuid),
        );

        this.showTransferModal = true;
      },
      error: (err) => this.logger.error('Error loading available tables for transfer:', err),
    });
  }

  transferOrderTo(targetTableUuid: string) {
    if (!this.order?.uuid) {
      return;
    }

    this.orderService.transferOrder(this.order.uuid, targetTableUuid).subscribe({
      next: () => {
        this.logger.log('Order transferred successfully to table:', targetTableUuid);
        this.tableUuid = targetTableUuid;
        this.tableName = this.tables.find(table => table.uuid === targetTableUuid)?.name || this.tableName;

        if (this.order) {
          this.order = {
            ...this.order,
            table_id: targetTableUuid,
          };
        }

        this.showTransferModal = false;
        this.availableTransferTables = [];
        this.loadOrder();
        this.router.navigate(['/tpv/order', targetTableUuid], {
          replaceUrl: true,
          state: { user: this.currentUser },
        });
      },
      error: (err) => this.logger.error('Error transferring order:', err),
    });
  }

  closeTransferModal() {
    this.showTransferModal = false;
    this.availableTransferTables = [];
  }

  printProvisionalTicket() {
    if (!this.order) {
      return;
    }

    const popup = window.open('', '_blank', 'width=420,height=760');
    if (!popup) {
      this.logger.error('Unable to open print window for provisional ticket');
      return;
    }

    popup.document.write(this.buildProvisionalTicketHtml());
    popup.document.close();
    popup.focus();

    setTimeout(() => {
      popup.print();
    }, 150);
  }

  private buildProvisionalTicketHtml(): string {
    const issuedAt = new Intl.DateTimeFormat('es-ES', {
      dateStyle: 'short',
      timeStyle: 'short',
    }).format(new Date());

    const linesHtml = this.order?.lines.map((line) => {
      const productName = this.escapeHtml(this.getProductName(line.product_id));
      const quantity = line.quantity;
      const unitPrice = this.formatCents(line.price);
      const subtotal = this.formatCents(this.getLineSubtotal(line));

      return `
        <tr>
          <td class="product-cell">${productName}</td>
          <td class="qty-cell">x${quantity}</td>
          <td class="price-cell">${unitPrice}</td>
          <td class="price-cell">${subtotal}</td>
        </tr>
      `;
    }).join('') ?? '';

    const waiterName = this.escapeHtml(this.currentUser?.name ?? 'Operador');
    const tableName = this.escapeHtml(this.tableName || 'Mesa');

    return `
      <!doctype html>
      <html lang="es">
        <head>
          <meta charset="utf-8" />
          <title>Ticket provisional - ${tableName}</title>
          <style>
            * { box-sizing: border-box; }
            body {
              margin: 0;
              padding: 16px;
              font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
              color: #111827;
              background: #ffffff;
            }
            .ticket {
              width: 100%;
              max-width: 360px;
              margin: 0 auto;
              border: 1px dashed #D1D5DB;
              border-radius: 12px;
              padding: 16px;
            }
            .title {
              text-align: center;
              font-size: 20px;
              font-weight: 700;
              margin-bottom: 4px;
            }
            .subtitle {
              text-align: center;
              font-size: 12px;
              color: #6B7280;
              margin-bottom: 16px;
            }
            .meta {
              display: grid;
              gap: 6px;
              font-size: 12px;
              margin-bottom: 16px;
            }
            .meta-row {
              display: flex;
              justify-content: space-between;
              gap: 12px;
            }
            table {
              width: 100%;
              border-collapse: collapse;
              font-size: 12px;
              margin-bottom: 16px;
            }
            th, td {
              padding: 6px 0;
              border-bottom: 1px solid #E5E7EB;
              vertical-align: top;
            }
            th {
              text-align: left;
              font-size: 11px;
              color: #6B7280;
              font-weight: 600;
            }
            .qty-cell {
              text-align: center;
              white-space: nowrap;
              width: 42px;
            }
            .price-cell {
              text-align: right;
              white-space: nowrap;
              width: 64px;
            }
            .totals {
              display: grid;
              gap: 8px;
              font-size: 13px;
            }
            .total-row {
              display: flex;
              justify-content: space-between;
              gap: 12px;
            }
            .total-final {
              font-size: 16px;
              font-weight: 700;
              padding-top: 8px;
              border-top: 2px solid #111827;
            }
            .footer {
              margin-top: 16px;
              text-align: center;
              font-size: 11px;
              color: #6B7280;
            }
            @media print {
              body {
                padding: 0;
              }
              .ticket {
                border: none;
                border-radius: 0;
                max-width: none;
              }
            }
          </style>
        </head>
        <body>
          <div class="ticket">
            <div class="title">YuRest</div>
            <div class="subtitle">Ticket provisional · No válido como factura</div>

            <div class="meta">
              <div class="meta-row"><span>Mesa</span><strong>${tableName}</strong></div>
              <div class="meta-row"><span>Comensales</span><strong>${this.order?.diners ?? 0}</strong></div>
              <div class="meta-row"><span>Operador</span><strong>${waiterName}</strong></div>
              <div class="meta-row"><span>Emitido</span><strong>${issuedAt}</strong></div>
            </div>

            <table>
              <thead>
                <tr>
                  <th>Producto</th>
                  <th class="qty-cell">Ud.</th>
                  <th class="price-cell">P/U</th>
                  <th class="price-cell">Total</th>
                </tr>
              </thead>
              <tbody>
                ${linesHtml}
              </tbody>
            </table>

            <div class="totals">
              <div class="total-row"><span>Subtotal</span><strong>${this.formatCents(this.orderSubtotal)}</strong></div>
              <div class="total-row"><span>Impuestos</span><strong>${this.formatCents(this.orderTax)}</strong></div>
              <div class="total-row total-final"><span>Total</span><strong>${this.formatCents(this.orderTotal)}</strong></div>
            </div>

            <div class="footer">Documento informativo para revisión de cuenta</div>
          </div>
        </body>
      </html>
    `;
  }

  private formatCents(amount: number): string {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency: 'EUR',
    }).format(amount / 100);
  }

  private escapeHtml(value: string): string {
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  getOrderDiscountAmount(): number {
    if (!this.order?.discount_type || !this.order?.discount_value || !this.order?.lines) {
      return 0;
    }

    const baseAmount = this.order.lines.reduce((sum, line) => sum + this.getLineSubtotal(line), 0);
    if (this.order.discount_type === 'percentage') {
      return Math.min(baseAmount, Math.round(baseAmount * this.order.discount_value / 100));
    }

    return Math.min(baseAmount, this.order.discount_value);
  }

  formatDiscountLabel(discountType: 'amount' | 'percentage' | null, discountValue: number): string {
    if (!discountType || !discountValue) {
      return 'Sin descuento';
    }

    return discountType === 'percentage'
      ? `-${discountValue}%`
      : `-${this.formatCents(discountValue)}`;
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
    this.payMode = 'payment';
    this.currentTab = 'summary';
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
    this.currentTab = 'order';
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

  sendToKitchen() {
    if (!this.order?.lines?.length) {
      return;
    }

    this.showKitchenSentModal = true;
  }

  closeKitchenSentModal() {
    this.showKitchenSentModal = false;
  }

  onPaymentCancelled() {
    this.currentTab = 'order';
  }



  goBack() {
    this.router.navigate(['/tpv'], { replaceUrl: true });
  }
}
