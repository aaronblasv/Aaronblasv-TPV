import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { SaleService } from '../../../services/api/sale.service';
import { Sale, SaleLine } from '../../../types/sale.model';

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
      next: (lines) => { this.saleLines = lines; this.linesLoading = false; },
      error: () => { this.linesLoading = false; },
    });
  }

  closeDetail() {
    this.selectedSale = null;
    this.saleLines = [];
  }

  get lineTotal(): number {
    return this.saleLines.reduce((sum, l) => sum + l.quantity * l.price, 0);
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
