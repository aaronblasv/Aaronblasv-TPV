import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { DashboardService } from '../../../services/api/dashboard.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.page.html',
  styleUrls: ['./dashboard.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, SidebarComponent, RouterModule],
})
export class DashboardPage implements OnInit {
  private dashboardService = inject(DashboardService);
  private router = inject(Router);

  loading = true;

  stats = { products: 0, families: 0, taxes: 0, users: 0, sales_this_month: 0, revenue_this_month: 0 };
  salesThisMonth: any[] = [];
  topProducts: any[] = [];
  salesByDay: any[] = [];

  quickActions = [
    { label: 'Nuevo producto', icon: '', route: '/products' },
    { label: 'Nuevo usuario', icon: '', route: '/users' },
    { label: 'Nueva mesa', icon: '', route: '/tables' },
    { label: 'Nueva zona', icon: '', route: '/zones' },
  ];

  ngOnInit() {
    this.dashboardService.getStats().subscribe({
      next: (data: any) => {
        this.stats = data.stats;
        this.salesThisMonth = data.sales_this_month;
        this.topProducts = data.top_products;
        this.salesByDay = data.sales_by_day;
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });
  }

  formatCurrency(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
  }

  formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  // SVG bar chart — sales by day
  get chartWidth() { return 600; }
  get chartHeight() { return 160; }
  get chartPaddingLeft() { return 48; }
  get chartPaddingBottom() { return 32; }

  get chartInnerWidth() { return this.chartWidth - this.chartPaddingLeft - 16; }
  get chartInnerHeight() { return this.chartHeight - this.chartPaddingBottom - 16; }

  get chartMaxTotal(): number {
    return Math.max(...this.salesByDay.map(d => Number(d.total)), 1);
  }

  getBarX(i: number): number {
    const barWidth = this.chartInnerWidth / Math.max(this.salesByDay.length, 1);
    return this.chartPaddingLeft + i * barWidth + barWidth * 0.15;
  }

  getBarWidth(): number {
    return (this.chartInnerWidth / Math.max(this.salesByDay.length, 1)) * 0.7;
  }

  getBarHeight(total: number): number {
    return (Number(total) / this.chartMaxTotal) * this.chartInnerHeight;
  }

  getBarY(total: number): number {
    return 16 + (this.chartInnerHeight - this.getBarHeight(total));
  }

  getBarLabel(d: any): string {
    const date = new Date(d.day);
    return String(date.getDate());
  }

  getYAxisLabels(): { value: string; y: number }[] {
    const steps = 4;
    return Array.from({ length: steps + 1 }, (_, i) => {
      const val = (this.chartMaxTotal / steps) * i;
      const y = 16 + this.chartInnerHeight - (val / this.chartMaxTotal) * this.chartInnerHeight;
      return { value: (val / 100).toFixed(0) + '€', y };
    });
  }

  // SVG horizontal bar — top products
  get topMaxQty(): number {
    return Math.max(...this.topProducts.map(p => Number(p.total_quantity)), 1);
  }

  getProductBarWidth(qty: number): number {
    return (Number(qty) / this.topMaxQty) * 260;
  }

  navigateTo(route: string) {
    this.router.navigate([route]);
  }
}
