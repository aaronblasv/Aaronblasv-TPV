import { Component, OnInit, ViewEncapsulation, inject } from '@angular/core';
import { CommonModule, registerLocaleData } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { addIcons } from 'ionicons';
import { peopleOutline } from 'ionicons/icons';
import { forkJoin } from 'rxjs';
import { OrderService } from '../../../services/api/order.service';
import { ProductService } from '../../../services/api/product.service';
import { FamilyService } from '../../../services/api/family.service';
import { TaxService } from '../../../services/api/tax.service';
import { TableService } from '../../../services/api/table.service';
import { PaymentService } from '../../../services/api/payment.service';
import { LoggerService } from '../../../services/logger.service';
import { SuccessModalComponent } from '../../../components/success-modal/success-modal.component';
import { TpvSessionService } from '../../../services/tpv-session.service';

import { DiscountModalComponent, DiscountResult } from '../../../components/discount-modal/discount-modal.component';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { DinersModalComponent } from '../../../components/diners-modal/diners-modal.component';
import { VoidLineModalComponent } from '../../../components/void-line-modal/void-line-modal.component';
import { Order, OrderLine } from '../../../types/order.model';
import { Product } from '../../../types/product.model';
import { Family } from '../../../types/family.model';
import { Tax } from '../../../types/tax.model';
import { Table } from '../../../types/table.model';
import { User } from '../../../types/user.model';
import { PaymentData, PaymentLineAllocation, PaymentMethod } from '../../../types/payment.model';
import localeEs from '@angular/common/locales/es';

registerLocaleData(localeEs);

interface SplitShare {
  index: number;
  amount: number;
  method: PaymentMethod;
  paid: boolean;
}

@Component({
  selector: 'app-order',
  standalone: true,
  imports: [CommonModule, IonicModule, SuccessModalComponent, DiscountModalComponent, ConfirmModalComponent, DinersModalComponent, VoidLineModalComponent],
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
  private tpvSessionService = inject(TpvSessionService);

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
  showTransferModal = false;
  showDiscountModal = false;
  showChangeDinersModal = false;
  showKitchenSentModal = false;
  showVoidSentLineModal = false;
  showVoidLineConfirmModal = false;
  discountModalTitle = 'Descuento';
  discountModalCurrentType: 'amount' | 'percentage' | null = null;
  discountModalCurrentValue = 0;
  discountTarget: 'order' | OrderLine | null = null;
  voidLineConfirmTarget: OrderLine | null = null;
  voidSentLineTarget: OrderLine | null = null;
  availableTransferTables: Table[] = [];
  totalPaid = 0;
  lastInvoiceNumber = '';
  lastTotalAmount = 0;

  paymentScope: 'amount' | 'split' = 'amount';
  payMethod: 'cash' | 'card' | 'bizum' = 'cash';
  payInput = '';
  payInputDisplay = '0.00';
  payInputCents = 0;
  numpadKeys: string[] = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', 'back'];
  private locallySentToKitchenLineIds = new Set<string>();
  readonly peopleOutlineIcon = peopleOutline;
  selectedPaymentLines: Record<string, number> = {};
  activePaymentLineUuid: string | null = null;
  paymentLineQuantityInput = '';
  replacePaymentLineQuantityOnNextDigit = false;
  splitShares: SplitShare[] = [];
  private pendingSplitShareIndex: number | null = null;
  private splitSharesBasePendingAmount = 0;
  private splitSharesBaseDiners = 0;

  constructor() {
    addIcons({ peopleOutline });
  }

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
    if (this.paymentScope === 'amount' && this.activePaymentLineUuid) {
      this.onPaymentLineQuantityKey(key);
      return;
    }

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
    this.paymentScope = 'amount';
    this.currentTab = 'summary';
  }

  payCustomAmount() {
    if (this.paymentScope === 'split') {
      return;
    }

    if (this.hasSelectedProductsForPayment) {
      this.paySelectedProducts();
      return;
    }

    if (this.payInputCents <= 0 || this.pendingAmount <= 0) return;
    const amount = Math.min(this.payInputCents, this.pendingAmount);
    this.onPaymentRegistered({ amount, method: this.payMethod, description: 'Pago libre a cuenta' });
    this.payInput = '';
    this.updatePayInputDisplay();
  }

  setPaymentScope(scope: 'amount' | 'split') {
    this.paymentScope = scope;

    if (scope === 'amount') {
      this.syncPayInputWithSelection();
      return;
    }

    this.clearSelectedPaymentLines();
    this.payInput = '';
    this.updatePayInputDisplay();
    this.ensureSplitShares();
  }

  clearSelectedPaymentLines() {
    this.selectedPaymentLines = {};
    this.activePaymentLineUuid = null;
    this.paymentLineQuantityInput = '';
    this.replacePaymentLineQuantityOnNextDigit = false;
  }

  onSummaryLineClicked(line: OrderLine) {
    if (this.paymentScope !== 'amount' || line.paid) {
      return;
    }

    if (this.isActivePaymentLine(line)) {
      const nextSelectedLines = { ...this.selectedPaymentLines };
      delete nextSelectedLines[line.uuid];
      this.selectedPaymentLines = nextSelectedLines;
      this.activePaymentLineUuid = null;
      this.paymentLineQuantityInput = '';
      this.replacePaymentLineQuantityOnNextDigit = false;
      this.syncPayInputWithSelection();

      return;
    }

    this.selectedPaymentLines = {
      ...this.selectedPaymentLines,
      [line.uuid]: this.getSelectedPaymentQuantity(line) || 1,
    };
    this.activePaymentLineUuid = line.uuid;
    this.paymentLineQuantityInput = String(this.selectedPaymentLines[line.uuid]);
    this.replacePaymentLineQuantityOnNextDigit = true;
    this.syncPayInputWithSelection();
  }

  ngOnInit() {
    this.tableUuid = this.route.snapshot.paramMap.get('tableUuid') || '';

    const nav = this.router.getCurrentNavigation();
    this.currentUser = this.tpvSessionService.getUser() ?? nav?.extras?.state?.['user'] ?? history.state?.['user'];

    if (this.currentUser) {
      this.tpvSessionService.setUser(this.currentUser);
    }

    if (!this.currentUser) {
      this.router.navigate(['/tpv'], { replaceUrl: true });
      return;
    }

    this.resetState();
    this.loadData();
  }

  resetState() {
    this.selectedFamilyUuid = null;
    this.currentTab = 'order';
    this.showSuccessModal = false;
    this.showTransferModal = false;
    this.availableTransferTables = [];
    this.totalPaid = 0;
    this.lastInvoiceNumber = '';
    this.lastTotalAmount = 0;
    this.showKitchenSentModal = false;
    this.closeVoidLineConfirmModal();
    this.closeVoidSentLineModal();
    this.clearSelectedPaymentLines();
    this.splitShares = [];
    this.pendingSplitShareIndex = null;
    this.splitSharesBasePendingAmount = 0;
    this.splitSharesBaseDiners = 0;
    this.order = null;
    this.locallySentToKitchenLineIds.clear();
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
        this.order = {
          ...order,
          lines: (order.lines ?? []).map((line) => ({
            ...line,
            sent_to_kitchen: line.sent_to_kitchen || this.locallySentToKitchenLineIds.has(line.uuid),
          })),
        };
        this.totalPaid = order.total_paid ?? 0;
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

  get summaryOrderLines(): OrderLine[] {
    return this.order?.lines ?? [];
  }

  get pendingOrderLines(): OrderLine[] {
    return this.summaryOrderLines.filter(line => !line.sent_to_kitchen && !line.paid);
  }

  get sentOrderLines(): OrderLine[] {
    return this.summaryOrderLines.filter(line => line.sent_to_kitchen && !line.paid);
  }

  get hasSelectedProductsForPayment(): boolean {
    return Object.keys(this.selectedPaymentLines).length > 0;
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
    return this.pendingOrderLines.reduce((sum, line) => sum + line.quantity, 0);
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

  getSummaryLineDisplayTotal(line: OrderLine): number {
    return this.getLineTotal(line);
  }

  get pendingSubtotal(): number {
    return this.pendingOrderLines.reduce((sum, line) => sum + this.getLineSubtotal(line), 0);
  }

  get pendingTax(): number {
    return this.pendingOrderLines.reduce((sum, line) => sum + this.getLineTax(line), 0);
  }

  get pendingTotal(): number {
    return this.pendingSubtotal + this.pendingTax;
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

  get orderDiscountRatio(): number {
    const lineSubtotal = this.summaryOrderLines.reduce((sum, line) => sum + this.getLineSubtotal(line), 0);

    if (lineSubtotal <= 0) {
      return 1;
    }

    return Math.max(0, (lineSubtotal - this.getOrderDiscountAmount()) / lineSubtotal);
  }

  get selectedProductsTotal(): number {
    const ratio = this.orderDiscountRatio;
    const selectedSubtotal = this.summaryOrderLines.reduce((sum, line) => sum + this.getSelectedLineSubtotal(line), 0);
    const selectedTax = this.summaryOrderLines.reduce((sum, line) => sum + this.getSelectedLineTax(line), 0);

    return Math.round(selectedSubtotal * ratio) + Math.round(selectedTax * ratio);
  }

  get selectedProductsExtraAmount(): number {
    return Math.max(0, this.payInputCents - this.selectedProductsTotal);
  }

  get canSubmitPayment(): boolean {
    if (this.pendingAmount <= 0) {
      return false;
    }

    if (this.hasSelectedProductsForPayment) {
      return this.selectedProductsTotal > 0 && this.payInputCents >= this.selectedProductsTotal;
    }

    return this.payInputCents > 0;
  }

  get splitShareCount(): number {
    return Math.max(1, this.order?.diners ?? 1);
  }

  addProduct(product: Product) {
    if (!this.order?.uuid || !this.currentUser?.uuid) {
      this.logger.error('Cannot add product without an active order or current user');
      return;
    }

    const existingLine = this.pendingOrderLines.find(line => line.product_id === product.uuid);

    if (existingLine) {
      const newQty = existingLine.quantity + 1;
      this.orderService.updateLineQuantity(this.order.uuid, existingLine.uuid, newQty).subscribe({
        next: () => {
          this.updateLineQuantityLocally(existingLine.uuid, newQty);
          this.loadOrder();
        },
        error: (err) => this.logger.error('Error updating line:', err),
      });
    } else {
      this.orderService.addLine(this.order.uuid, product.uuid, this.currentUser.uuid, 1).subscribe({
        next: (line) => {
          this.appendPendingLineLocally(line);
          this.loadOrder();
        },
        error: (err) => this.logger.error('Error adding line:', err),
      });
    }
  }

  incrementLine(line: OrderLine) {
    if (!this.order?.uuid) {
      return;
    }

    const newQty = line.quantity + 1;
    this.orderService.updateLineQuantity(this.order.uuid, line.uuid, newQty).subscribe({
      next: () => {
        this.updateLineQuantityLocally(line.uuid, newQty);
        this.loadOrder();
      },
      error: (err) => this.logger.error('Error updating line:', err),
    });
  }

  decrementLine(line: OrderLine) {
    if (!this.order?.uuid) {
      return;
    }

    if (line.quantity <= 1) {
      this.removeLine(line);
      return;
    }
    const newQty = line.quantity - 1;
    this.orderService.updateLineQuantity(this.order.uuid, line.uuid, newQty).subscribe({
      next: () => {
        this.updateLineQuantityLocally(line.uuid, newQty);
        this.loadOrder();
      },
      error: (err) => this.logger.error('Error updating line:', err),
    });
  }

  removeLine(line: OrderLine) {
    if (!this.order?.uuid) {
      return;
    }

    this.orderService.removeLine(this.order.uuid, line.uuid).subscribe({
      next: () => {
        this.removeLineLocally(line.uuid);
        this.loadOrder();
      },
      error: (err) => {
        this.logger.error('Error removing line:', err);
        alert(err?.error?.message ?? 'No se ha podido quitar la línea del pedido.');
      },
    });
  }

  promptVoidSentLine(line: OrderLine) {
    this.voidSentLineTarget = line;
    this.showVoidSentLineModal = true;
  }

  closeVoidSentLineModal() {
    this.showVoidSentLineModal = false;
    this.voidSentLineTarget = null;
  }

  closeVoidLineConfirmModal() {
    this.showVoidLineConfirmModal = false;
    this.voidLineConfirmTarget = null;
  }

  onVoidSentLineQuantityConfirmed(quantity: number) {
    if (!this.voidSentLineTarget) return;

    const line = this.voidSentLineTarget;
    this.closeVoidSentLineModal();
    this.voidSentLine(line, quantity);
  }

  voidEntireSentLine(line: OrderLine) {
    this.voidLineConfirmTarget = line;
    this.showVoidLineConfirmModal = true;
  }

  confirmVoidEntireSentLine() {
    if (!this.voidLineConfirmTarget) return;

    const line = this.voidLineConfirmTarget;
    this.closeVoidLineConfirmModal();
    this.voidSentLine(line, line.quantity);
  }

  private voidSentLine(line: OrderLine, quantity: number) {
    this.orderService.voidSentLine(this.order!.uuid, line.uuid, quantity).subscribe({
      next: () => { this.loadOrder(); },
      error: (err) => {
        this.logger.error('Error voiding sent line:', err);
        alert(err?.error?.message ?? 'No se ha podido anular la cantidad solicitada de la línea enviada a cocina.');
      },
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
        next: () => {
          this.applyOrderDiscountLocally(result);
          this.discountTarget = null;
          this.loadOrder();
        },
        error: (err) => this.logger.error('Error updating order discount:', err),
      });
    } else if (this.discountTarget) {
      const line = this.discountTarget as OrderLine;
      this.orderService.updateLineDiscount(this.order.uuid, line.uuid, result.type, result.value).subscribe({
        next: () => {
          this.discountTarget = null;
          this.loadOrder();
        },
        error: (err) => this.logger.error('Error updating line discount:', err),
      });
    }
  }

  onDiscountCancelled() {
    this.showDiscountModal = false;
    this.discountTarget = null;
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
        });
      },
      error: (err) => this.logger.error('Error transferring order:', err),
    });
  }

  closeTransferModal() {
    this.showTransferModal = false;
    this.availableTransferTables = [];
  }

  private updateLineQuantityLocally(lineUuid: string, quantity: number) {
    if (!this.order) {
      return;
    }

    this.order = {
      ...this.order,
      lines: (this.order.lines ?? []).map((line) =>
        line.uuid === lineUuid
          ? {
              ...line,
              quantity,
            }
          : line,
      ),
    };
  }

  private appendPendingLineLocally(line: OrderLine) {
    if (!this.order) {
      return;
    }

    this.order = {
      ...this.order,
      lines: [
        ...(this.order.lines ?? []),
        {
          ...line,
          sent_to_kitchen: line.sent_to_kitchen ?? false,
          paid: line.paid ?? false,
          paid_at: line.paid_at ?? null,
        },
      ],
    };
  }

  private removeLineLocally(lineUuid: string) {
    if (!this.order) {
      return;
    }

    this.order = {
      ...this.order,
      lines: (this.order.lines ?? []).filter((line) => line.uuid !== lineUuid),
    };
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

    const linesHtml = (this.order?.lines ?? []).map((line) => {
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
    return this.order?.discount_amount ?? 0;
  }

  formatDiscountLabel(discountType: 'amount' | 'percentage' | null, discountValue: number): string {
    if (!discountType || !discountValue) {
      return 'Sin descuento';
    }

    return discountType === 'percentage'
      ? `-${discountValue}%`
      : `-${this.formatCents(discountValue)}`;
  }

  private applyOrderDiscountLocally(result: DiscountResult) {
    if (!this.order) {
      return;
    }

    if (result.type === null || result.value <= 0) {
      this.order = {
        ...this.order,
        discount_type: null,
        discount_value: 0,
        discount_amount: 0,
      };

      return;
    }

    const baseAmount = (this.order.lines ?? []).reduce((sum, line) => sum + this.getLineSubtotal(line), 0);
    const discountAmount = result.type === 'percentage'
      ? Math.min(baseAmount, Math.round(baseAmount * result.value / 100))
      : Math.min(baseAmount, result.value);

    this.order = {
      ...this.order,
      discount_type: result.type,
      discount_value: result.value,
      discount_amount: discountAmount,
    };
  }

  onPaymentRegistered(payment: PaymentData) {
    this.logger.log('onPaymentRegistered:', payment);

    if (!this.currentUser?.uuid) {
      this.handleOrderClosureError('No se pudo validar el camarero para registrar el pago.');
      return;
    }

    this.paymentService.registerPayment(
      this.order!.uuid,
      this.currentUser.uuid,
      Math.round(payment.amount),
      payment.method,
      payment.description,
      payment.lineAllocations ?? [],
    ).subscribe({
      next: (response) => {
        this.logger.log('Payment registered successfully');
        this.totalPaid = response.total_paid;
        this.markPendingSplitShareAsPaid();
        this.loadOrder();
        this.clearSelectedPaymentLines();
        if (this.paymentScope === 'amount') {
          this.payInput = '';
          this.updatePayInputDisplay();
        }
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
      this.handleOrderClosureError('No se pudo identificar la cuenta a cerrar.');
      return;
    }

    const orderUuid = this.order.uuid;

    if (!this.currentUser?.uuid) {
      this.handleOrderClosureError('No se pudo validar el usuario que cierra la mesa.');
      return;
    }

    const closingUserUuid = this.currentUser.uuid;

    this.paymentService.generateInvoice(orderUuid, closingUserUuid).subscribe({
      next: (invoice) => {
        this.logger.log('Invoice generated:', invoice);
        this.lastInvoiceNumber = invoice.invoice_number || 'INV-XXXXXX-XXXX';

        this.orderService.closeOrder(orderUuid, closingUserUuid).subscribe({
          next: () => {
            this.logger.log('Order closed successfully');
            this.showSuccessModal = true;
          },
          error: (err) => {
            this.logger.error('Error closing order:', err);
            this.handleOrderClosureError('El pago se registró, pero la mesa no pudo cerrarse.');
          },
        });
      },
      error: (err) => {
        this.logger.error('Error generating invoice:', err);
        this.orderService.closeOrder(orderUuid, closingUserUuid).subscribe({
          next: () => { this.showSuccessModal = true; },
          error: (closeErr) => {
            this.logger.error('Error closing order:', closeErr);
            this.handleOrderClosureError('El pago se registró, pero la mesa no pudo cerrarse.');
          },
        });
      },
    });
  }

  private handleOrderClosureError(message: string) {
    this.showSuccessModal = false;
    this.currentTab = 'summary';
    alert(message);
  }

  onSuccessModalClose() {
    this.showSuccessModal = false;
    this.router.navigate(['/tpv'], { replaceUrl: true });
  }

  logoutTpvSession() {
    this.tpvSessionService.clear();
    this.currentUser = null;
    this.router.navigate(['/tpv'], { replaceUrl: true });
  }

  isLineSelectedForPayment(line: OrderLine): boolean {
    return this.getSelectedPaymentQuantity(line) > 0;
  }

  isActivePaymentLine(line: OrderLine): boolean {
    return this.activePaymentLineUuid === line.uuid;
  }

  get activePaymentLine(): OrderLine | null {
    if (!this.activePaymentLineUuid) {
      return null;
    }

    return this.summaryOrderLines.find((line) => line.uuid === this.activePaymentLineUuid) ?? null;
  }

  getSelectedPaymentQuantity(line: OrderLine): number {
    return this.selectedPaymentLines[line.uuid] ?? 0;
  }

  getSelectedLineSubtotal(line: OrderLine): number {
    const selectedQuantity = this.getSelectedPaymentQuantity(line);

    if (selectedQuantity <= 0) {
      return 0;
    }

    const baseAmount = line.price * selectedQuantity;
    const discountAmount = this.getSelectedLineDiscountAmount(line, selectedQuantity);

    return Math.max(0, baseAmount - discountAmount);
  }

  getSelectedLineTax(line: OrderLine): number {
    const subtotal = this.getSelectedLineSubtotal(line);

    if (subtotal <= 0) {
      return 0;
    }

    return Math.round(subtotal * line.tax_percentage / 100);
  }

  private getSelectedLineDiscountAmount(line: OrderLine, selectedQuantity: number): number {
    if (!line.discount_type || line.discount_value <= 0) {
      return 0;
    }

    if (line.discount_type === 'amount') {
      return Math.floor(line.discount_value * selectedQuantity / line.quantity);
    }

    return Math.round((line.price * selectedQuantity) * line.discount_value / 100);
  }

  private syncPayInputWithSelection() {
    if (this.paymentScope !== 'amount') {
      return;
    }

    if (!this.hasSelectedProductsForPayment) {
      this.payInput = '';
      this.updatePayInputDisplay();
      return;
    }

    this.payInput = (this.selectedProductsTotal / 100).toFixed(2);
    this.updatePayInputDisplay();
  }

  clearPayEntry() {
    if (this.hasSelectedProductsForPayment) {
      this.clearSelectedPaymentLines();
      this.syncPayInputWithSelection();
      return;
    }

    this.payInput = '';
    this.updatePayInputDisplay();
  }

  private paySelectedProducts() {
    if (this.selectedProductsTotal <= 0 || this.payInputCents < this.selectedProductsTotal) {
      return;
    }

    const amount = Math.min(this.payInputCents, this.pendingAmount);
    const lineAllocations: PaymentLineAllocation[] = Object.entries(this.selectedPaymentLines)
      .map(([line_uuid, quantity]) => ({ line_uuid, quantity }))
      .filter((allocation) => allocation.quantity > 0);

    this.onPaymentRegistered({
      amount,
      method: this.payMethod,
      description: this.selectedProductsExtraAmount > 0
        ? 'Pago de productos + saldo a cuenta'
        : 'Pago de productos seleccionados',
      lineAllocations,
    });
  }

  private onPaymentLineQuantityKey(key: string) {
    const line = this.activePaymentLine;

    if (!line) {
      this.activePaymentLineUuid = null;
      this.paymentLineQuantityInput = '';
      this.replacePaymentLineQuantityOnNextDigit = false;
      return;
    }

    let nextInput = this.paymentLineQuantityInput;

    if (key === 'back') {
      nextInput = nextInput.slice(0, -1);
    } else if (key !== '.') {
      nextInput = this.replacePaymentLineQuantityOnNextDigit ? key : `${nextInput}${key}`;
    }

    this.replacePaymentLineQuantityOnNextDigit = false;

    if (nextInput === '') {
      const nextSelectedLines = { ...this.selectedPaymentLines };
      delete nextSelectedLines[line.uuid];
      this.selectedPaymentLines = nextSelectedLines;
      this.paymentLineQuantityInput = '';
      this.activePaymentLineUuid = null;
      this.syncPayInputWithSelection();
      return;
    }

    const requestedQuantity = Number.parseInt(nextInput, 10);

    if (!Number.isInteger(requestedQuantity) || requestedQuantity <= 0) {
      return;
    }

    const clampedQuantity = Math.min(requestedQuantity, line.quantity);

    this.selectedPaymentLines = {
      ...this.selectedPaymentLines,
      [line.uuid]: clampedQuantity,
    };
    this.paymentLineQuantityInput = String(clampedQuantity);
    this.syncPayInputWithSelection();
  }

  setSplitShareMethod(shareIndex: number, method: PaymentMethod) {
    this.splitShares = this.splitShares.map((share) =>
      share.index === shareIndex ? { ...share, method } : share,
    );
  }

  paySplitShare(share: SplitShare) {
    if (share.paid || share.amount <= 0) {
      return;
    }

    this.pendingSplitShareIndex = share.index;
    this.onPaymentRegistered({
      amount: share.amount,
      method: share.method,
      description: `Comensal ${share.index + 1}/${this.splitShares.length}`,
      lineAllocations: [],
    });
  }

  private ensureSplitShares() {
    if (
      this.splitShares.length > 0
      && this.splitSharesBasePendingAmount === this.pendingAmount
      && this.splitSharesBaseDiners === this.splitShareCount
    ) {
      return;
    }

    const count = this.splitShareCount;
    const baseAmount = Math.floor(this.pendingAmount / count);
    const remainder = this.pendingAmount % count;

    this.splitShares = Array.from({ length: count }, (_, index) => ({
      index,
      amount: baseAmount + (index < remainder ? 1 : 0),
      method: 'cash' as PaymentMethod,
      paid: false,
    }));

    this.pendingSplitShareIndex = null;
    this.splitSharesBasePendingAmount = this.pendingAmount;
    this.splitSharesBaseDiners = count;
  }

  private markPendingSplitShareAsPaid() {
    if (this.pendingSplitShareIndex === null) {
      return;
    }

    this.splitShares = this.splitShares.map((share) =>
      share.index === this.pendingSplitShareIndex ? { ...share, paid: true } : share,
    );
    this.pendingSplitShareIndex = null;
  }

  sendToKitchen() {
    if (!this.order?.uuid || this.pendingOrderLines.length === 0) {
      return;
    }

    const sentLineIds = this.pendingOrderLines.map((line) => line.uuid);

    this.orderService.sendToKitchen(this.order.uuid, this.currentUser?.uuid).subscribe({
      next: () => {
        sentLineIds.forEach((lineUuid) => this.locallySentToKitchenLineIds.add(lineUuid));

        this.order = {
          ...this.order!,
          lines: (this.order?.lines ?? []).map((line) => (
            sentLineIds.includes(line.uuid)
              ? { ...line, sent_to_kitchen: true }
              : line
          )),
        };

        this.showKitchenSentModal = true;
        this.loadOrder();
      },
      error: (err) => this.logger.error('Error sending order to kitchen:', err),
    });
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
