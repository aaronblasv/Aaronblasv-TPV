import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { SaleService } from '../../../services/api/sale.service';
import { SalesReport } from '../../../types/sale.model';

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

  from = '';
  to = '';

  activeTab: 'day' | 'zone' | 'product' | 'user' = 'day';

  ngOnInit() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    this.from = firstDay.toISOString().split('T')[0];
    this.to   = now.toISOString().split('T')[0];
    this.loadReport();
  }

  loadReport() {
    this.loading = true;
    this.saleService.getReport(this.from || undefined, this.to || undefined).subscribe({
      next: (data) => { this.report = data; this.loading = false; },
      error: () => { this.loading = false; },
    });
  }

  applyFilter() { this.loadReport(); }

  clearFilter() { this.from = ''; this.to = ''; this.loadReport(); }

  formatCurrency(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
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
