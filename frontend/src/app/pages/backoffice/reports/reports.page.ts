import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { SaleService } from '../../../services/api/sale.service';
import { RefundPayload, Sale, SaleLine, SaleReceipt, SaleServiceWindow, SalesReport } from '../../../types/sale.model';
import { forkJoin } from 'rxjs';

@Component({
  selector: 'app-reports',
  templateUrl: './reports.page.html',
  styleUrls: ['./reports.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent],
})
export class ReportsPage implements OnInit {
  private saleService = inject(SaleService);

  loading = false;
  report: SalesReport = { by_day: [], by_zone: [], by_product: [], by_user: [] };
  sales: Sale[] = [];

  from = '';
  to = '';

  activeTab: 'sales' | 'day' | 'zone' | 'product' | 'user' = 'sales';

  selectedSale: Sale | null = null;
  selectedReceipt: SaleReceipt | null = null;
  saleLines: SaleLine[] = [];
  linesLoading = false;
  refundMethod: 'cash' | 'card' | 'bizum' = 'cash';
  refundReason = '';
  refundQuantities: Record<string, number> = {};

  ngOnInit() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    this.from = firstDay.toISOString().split('T')[0];
    this.to   = now.toISOString().split('T')[0];
    this.loadData();
  }

  loadData() {
    this.loading = true;
    forkJoin({
      report: this.saleService.getReport(this.from || undefined, this.to || undefined),
      sales: this.saleService.getAll(this.from || undefined, this.to || undefined),
    }).subscribe({
      next: ({ report, sales }) => {
        this.report = report;
        this.sales = sales;
        this.loading = false;
      },
      error: () => { this.loading = false; },
    });
  }

  applyFilter() { this.loadData(); }

  clearFilter() { this.from = ''; this.to = ''; this.loadData(); }

  formatCurrency(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
  }

  formatDateTime(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('es-ES', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  }

  openDetail(sale: Sale) {
    this.selectedSale = sale;
    this.selectedReceipt = null;
    this.saleLines = [];
    this.linesLoading = true;
    this.saleService.getReceipt(sale.uuid).subscribe({
      next: (receipt) => {
        this.selectedReceipt = receipt;
        this.saleLines = receipt.lines;
        this.refundQuantities = Object.fromEntries(receipt.lines.map(line => [line.uuid, Math.max(1, line.quantity - line.refunded_quantity)]));
        this.linesLoading = false;
      },
      error: () => { this.linesLoading = false; },
    });
  }

  closeDetail() {
    this.selectedSale = null;
    this.selectedReceipt = null;
    this.saleLines = [];
  }

  printReceipt() {
    if (!this.selectedReceipt) {
      return;
    }

    const popup = window.open('', '_blank', 'width=420,height=820');
    if (!popup) {
      return;
    }

    popup.document.open();
    popup.document.write(this.buildReceiptHtml(this.selectedReceipt));
    popup.document.close();
    popup.focus();
    popup.print();
  }

  getServiceWindowTotal(window: SaleServiceWindow): number {
    return window.lines.reduce((sum, line) => sum + line.line_total, 0);
  }

  getDisplayedSaleLineTotal(line: SaleLine): number {
    if (line.quantity <= 0) {
      return line.line_total;
    }

    return line.line_total - Math.round((line.line_total / line.quantity) * line.refunded_quantity);
  }

  getReceiptItemsCount(receipt: SaleReceipt): number {
    return receipt.lines.reduce((sum, line) => sum + line.quantity, 0);
  }

  private buildReceiptHtml(receipt: SaleReceipt): string {
    const itemCount = receipt.lines.reduce((sum, line) => sum + line.quantity, 0);
    const linesHtml = receipt.lines.map((line) => {
      const detail = `${line.quantity.toFixed(1)} x ${this.formatCurrency(line.price)}`;
      const discount = line.discount_amount > 0 ? `<div class="line-meta">Descuento: ${this.formatCurrency(line.discount_amount)}</div>` : '';
      const refunded = line.refunded_quantity > 0 ? `<div class="line-meta refund">Devueltas: ${line.refunded_quantity}</div>` : '';

      return `
        <div class="receipt-line">
          <div class="receipt-line__top">
            <span>${this.escapeHtml(line.product_name)}</span>
            <strong>${this.formatCurrency(line.line_total - Math.round((line.line_total / line.quantity) * line.refunded_quantity))}</strong>
          </div>
          <div class="receipt-line__bottom">${detail}</div>
          <div class="line-meta">IVA ${line.tax_percentage}%</div>
          ${discount}
          ${refunded}
        </div>
      `;
    }).join('');

    return `<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <title>Ticket #${receipt.ticket_number}</title>
    <style>
      * { box-sizing: border-box; }
      body { margin: 0; padding: 12px; font-family: 'SFMono-Regular', 'Menlo', 'Consolas', monospace; color: #111827; background: #ffffff; }
      .receipt { width: 302px; margin: 0 auto; padding: 10px 8px 16px; background: #fff; }
      .center { text-align: center; }
      .muted { color: #4b5563; font-size: 11px; }
      h1 { font-size: 18px; margin: 0 0 3px; letter-spacing: 0.04em; }
      .ticket-number { margin-top: 6px; font-size: 16px; font-weight: 700; }
      .divider { border-top: 1px dashed #6b7280; margin: 10px 0; }
      .meta, .totals { display: grid; gap: 4px; font-size: 12px; }
      .meta-row, .total-row, .tax-row { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
      .receipt-line { padding: 6px 0; }
      .receipt-line__top, .receipt-line__bottom { display: flex; justify-content: space-between; gap: 10px; }
      .receipt-line__top { font-size: 13px; font-weight: 700; text-transform: uppercase; }
      .receipt-line__bottom, .line-meta { font-size: 11px; color: #374151; margin-top: 1px; }
      .refund { color: #b45309; }
      .totals { margin-top: 6px; }
      .total-row strong { font-size: 16px; }
      .tax-box { margin-top: 8px; font-size: 11px; }
      .tax-box__header { display: flex; justify-content: space-between; color: #4b5563; margin-bottom: 4px; }
      .summary { display: flex; justify-content: space-between; font-weight: 700; margin-top: 8px; }
      .footer { margin-top: 14px; font-size: 11px; color: #4b5563; text-align: center; }
    </style>
  </head>
  <body>
    <main class="receipt">
      <header class="center">
        <h1>${this.escapeHtml(receipt.restaurant_name)}</h1>
        <div class="muted">${this.escapeHtml(receipt.restaurant_legal_name)}</div>
        <div class="muted">CIF: ${this.escapeHtml(receipt.restaurant_tax_id)}</div>
        <div class="ticket-number">TICKET · ${receipt.ticket_number}</div>
      </header>

      <div class="divider"></div>

      <section class="meta">
        <div class="meta-row"><span>Fecha</span><strong>${this.formatDateTime(receipt.value_date)}</strong></div>
        <div class="meta-row"><span>Sala-Mesa</span><strong>${this.escapeHtml(receipt.table_name)}</strong></div>
        <div class="meta-row"><span>Camarero</span><strong>${this.escapeHtml(receipt.close_user_name)}</strong></div>
      </section>

      <div class="divider"></div>

      <section>${linesHtml}</section>

      <div class="divider"></div>

      <div class="summary">
        <span>${itemCount} artículos</span>
        <span>TOTAL ${this.formatCurrency(receipt.net_total)}</span>
      </div>

      <section class="totals">
        ${receipt.line_discount_total > 0 ? `<div class="total-row"><span>Dto. líneas</span><span>-${this.formatCurrency(receipt.line_discount_total)}</span></div>` : ''}
        ${receipt.order_discount_total > 0 ? `<div class="total-row"><span>Dto. pedido</span><span>-${this.formatCurrency(receipt.order_discount_total)}</span></div>` : ''}
        ${receipt.refunded_total > 0 ? `<div class="total-row"><span>Devuelto</span><span>-${this.formatCurrency(receipt.refunded_total)}</span></div>` : ''}
      </section>

      <section class="tax-box">
        <div class="muted">Impuestos incluidos</div>
        <div class="tax-box__header">
          <span></span>
          <span>Base</span>
          <span>Cuota</span>
        </div>
        <div class="tax-row">
          <span>IVA AL 10%</span>
          <span>${this.formatCurrency(receipt.subtotal)}</span>
          <span>${this.formatCurrency(receipt.tax_amount)}</span>
        </div>
      </section>

      <div class="footer">Gracias por su visita</div>
    </main>
  </body>
</html>`;
  }

  private escapeHtml(value: string): string {
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  get netLineTotal(): number {
    return this.saleLines.reduce((sum, line) => sum + (line.line_total - Math.round((line.line_total / line.quantity) * line.refunded_quantity)), 0);
  }

  getRemainingQuantity(line: SaleLine): number {
    return Math.max(0, line.quantity - line.refunded_quantity);
  }

  refundAllSale() {
    if (!this.selectedSale) {
      return;
    }

    const payload: RefundPayload = {
      method: this.refundMethod,
      reason: this.refundReason || undefined,
      refund_all: true,
    };

    this.saleService.createRefund(this.selectedSale.uuid, payload).subscribe({
      next: () => {
        this.loadData();
        this.openDetail(this.selectedSale!);
      },
    });
  }

  refundLine(line: SaleLine) {
    if (!this.selectedSale) {
      return;
    }

    const quantity = Math.min(this.getRemainingQuantity(line), Math.max(1, Number(this.refundQuantities[line.uuid] || 0)));
    const payload: RefundPayload = {
      method: this.refundMethod,
      reason: this.refundReason || undefined,
      refund_all: false,
      lines: [{
        sale_line_uuid: line.uuid,
        quantity,
      }],
    };

    this.saleService.createRefund(this.selectedSale.uuid, payload).subscribe({
      next: () => {
        this.loadData();
        this.openDetail(this.selectedSale!);
      },
    });
  }

  // ---- SVG bar chart (by_day) ----
  get chartWidth() { return 600; }
  get chartHeight() { return 180; }
  get chartPadLeft() { return 56; }
  get chartPadBottom() { return 32; }
  get innerW() { return this.chartWidth - this.chartPadLeft - 16; }
  get innerH() { return this.chartHeight - this.chartPadBottom - 16; }

  get chartMax(): number {
    return Math.max(...this.report.by_day.map(d => d.total), 1);
  }

  barX(i: number): number {
    return this.chartPadLeft + i * (this.innerW / Math.max(this.report.by_day.length, 1)) + (this.innerW / Math.max(this.report.by_day.length, 1)) * 0.15;
  }

  barW(): number {
    return (this.innerW / Math.max(this.report.by_day.length, 1)) * 0.7;
  }

  barH(total: number): number {
    return (total / this.chartMax) * this.innerH;
  }

  barY(total: number): number {
    return 16 + (this.innerH - this.barH(total));
  }

  barLabel(day: string): string {
    return String(new Date(day).getDate());
  }

  yLabels(): { value: string; y: number }[] {
    return Array.from({ length: 5 }, (_, i) => {
      const val = (this.chartMax / 4) * i;
      const y = 16 + this.innerH - (val / this.chartMax) * this.innerH;
      return { value: (val / 100).toFixed(0) + '€', y };
    });
  }

  // ---- Horizontal bar for zones / products / users ----
  maxTotal(items: { total: number }[]): number {
    return Math.max(...items.map(i => i.total), 1);
  }

  maxQty(items: { total_quantity: number }[]): number {
    return Math.max(...items.map(i => i.total_quantity), 1);
  }

  barWidthPct(value: number, max: number): number {
    return (value / max) * 100;
  }
}
