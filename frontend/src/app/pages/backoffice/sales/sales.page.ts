import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { SaleService } from '../../../services/api/sale.service';
import { RefundPayload, Sale, SaleLine } from '../../../types/sale.model';

@Component({
  selector: 'app-sales',
  templateUrl: './sales.page.html',
  styleUrls: ['./sales.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent],
})
export class SalesPage implements OnInit {
  private saleService = inject(SaleService);

  sales: Sale[] = [];
  loading = false;

  from = '';
  to = '';

  selectedSale: Sale | null = null;
  saleLines: SaleLine[] = [];
  linesLoading = false;
  refundMethod: 'cash' | 'card' | 'bizum' = 'cash';
  refundReason = '';
  refundQuantities: Record<string, number> = {};

  ngOnInit() {
    this.loadSales();
  }

  loadSales() {
    this.loading = true;
    this.saleService.getAll(this.from || undefined, this.to || undefined).subscribe({
      next: (data) => { this.sales = data; this.loading = false; },
      error: () => { this.loading = false; },
    });
  }

  applyFilter() {
    this.loadSales();
  }

  clearFilter() {
    this.from = '';
    this.to = '';
    this.loadSales();
  }

  openDetail(sale: Sale) {
    this.selectedSale = sale;
    this.saleLines = [];
    this.linesLoading = true;
    this.saleService.getLines(sale.uuid).subscribe({
      next: (lines) => {
        this.saleLines = lines;
        this.refundQuantities = Object.fromEntries(lines.map(line => [line.uuid, Math.max(1, line.quantity - line.refunded_quantity)]));
        this.linesLoading = false;
      },
      error: () => { this.linesLoading = false; },
    });
  }

  closeDetail() {
    this.selectedSale = null;
    this.saleLines = [];
  }

  get lineTotal(): number {
    return this.saleLines.reduce((sum, line) => sum + line.line_total, 0);
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
        this.loadSales();
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
        this.loadSales();
        this.openDetail(this.selectedSale!);
      },
    });
  }

  formatCurrency(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
  }

  formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('es-ES', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  }
}
