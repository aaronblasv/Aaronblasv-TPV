import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { DashboardService } from '../../../services/api/dashboard.service';
import { DashboardResponse, DashboardStats, SaleByDay, SaleThisMonth, TopProduct } from '../../../types/dashboard.model';

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

  stats: DashboardStats = { products: 0, families: 0, taxes: 0, users: 0, sales_this_month: 0, revenue_this_month: 0 };
  salesThisMonth: SaleThisMonth[] = [];
  topProducts: TopProduct[] = [];
  salesByDay: SaleByDay[] = [];

  quickActions = [
    { label: 'Nuevo producto', icon: 'PR', route: '/products' },
    { label: 'Nuevo usuario', icon: 'US', route: '/users' },
    { label: 'Nueva mesa', icon: 'ME', route: '/tables' },
    { label: 'Nueva zona', icon: 'ZO', route: '/zones' },
  ];

  ngOnInit() {
    this.dashboardService.getStats().subscribe({
      next: (data: DashboardResponse) => {
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

  get chartWidth() { return 640; }
  get chartHeight() { return 240; }
  get chartPaddingTop() { return 20; }
  get chartPaddingLeft() { return 44; }
  get chartPaddingRight() { return 20; }
  get chartPaddingBottom() { return 34; }

  get chartInnerWidth() { return this.chartWidth - this.chartPaddingLeft - this.chartPaddingRight; }
  get chartInnerHeight() { return this.chartHeight - this.chartPaddingTop - this.chartPaddingBottom; }
  get chartBottomY() { return this.chartPaddingTop + this.chartInnerHeight; }

  get chartMaxTotal(): number {
    return Math.max(...this.salesByDay.map(d => Number(d.total)), 1);
  }

  get chartPoints(): { x: number; y: number; total: number; day: string; label: string }[] {
    const points = this.salesByDay.map((day, index) => ({
      x: this.getPointX(index),
      y: this.getPointY(Number(day.total)),
      total: Number(day.total),
      day: day.day,
      label: this.getBarLabel(day),
    }));

    return points;
  }

  get chartAreaPath(): string {
    if (!this.chartPoints.length) {
      return '';
    }

    const [firstPoint] = this.chartPoints;
    const pointsLine = this.chartPoints.map(point => `${point.x} ${point.y}`).join(' L ');

    return `M ${firstPoint.x} ${this.chartBottomY} L ${pointsLine} L ${this.chartPoints[this.chartPoints.length - 1].x} ${this.chartBottomY} Z`;
  }

  get chartLinePath(): string {
    if (!this.chartPoints.length) {
      return '';
    }

    const [firstPoint, ...rest] = this.chartPoints;

    return `M ${firstPoint.x} ${firstPoint.y} ${rest.map(point => `L ${point.x} ${point.y}`).join(' ')}`;
  }

  getPointX(index: number): number {
    if (this.salesByDay.length <= 1) {
      return this.chartPaddingLeft + this.chartInnerWidth / 2;
    }

    return this.chartPaddingLeft + (index / (this.salesByDay.length - 1)) * this.chartInnerWidth;
  }

  getPointY(total: number): number {
    return this.chartPaddingTop + this.chartInnerHeight - (Number(total) / this.chartMaxTotal) * this.chartInnerHeight;
  }

  getBarLabel(day: SaleByDay): string {
    const date = new Date(day.day);
    return String(date.getDate());
  }

  getYAxisLabels(): { value: string; y: number }[] {
    const steps = 4;

    return Array.from({ length: steps + 1 }, (_, i) => {
      const val = (this.chartMaxTotal / steps) * i;

      const y = this.chartPaddingTop + this.chartInnerHeight - (val / this.chartMaxTotal) * this.chartInnerHeight;

      return { value: (val / 100).toFixed(0) + '€', y };
    });
  }

  get monthlyRevenue(): number {
    return Number(this.stats.revenue_this_month ?? 0);
  }

  get averageDailyRevenue(): number {
    if (!this.salesByDay.length) {
      return 0;
    }

    const total = this.salesByDay.reduce((sum, day) => sum + Number(day.total), 0);

    return total / this.salesByDay.length;
  }

  get bestSalesDay(): SaleByDay | null {
    if (!this.salesByDay.length) {
      return null;
    }

    return this.salesByDay.reduce((best, current) => Number(current.total) > Number(best.total) ? current : best);
  }

  get totalTopProductsQuantity(): number {
    return this.topProducts.reduce((sum, product) => sum + Number(product.total_quantity), 0);
  }

  get topMaxQty(): number {
    return Math.max(...this.topProducts.map(p => Number(p.total_quantity)), 1);
  }

  getProductBarWidth(qty: number): number {
    return (Number(qty) / this.topMaxQty) * 100;
  }

  getProductShare(qty: number): number {
    if (!this.totalTopProductsQuantity) {
      return 0;
    }

    return (Number(qty) / this.totalTopProductsQuantity) * 100;
  }

  getProductShareLabel(qty: number): string {
    return `${this.getProductShare(qty).toFixed(0)}%`;
  }

  navigateTo(route: string) {
    this.router.navigate([route]);
  }
}
