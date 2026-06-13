import { Component, OnInit } from '@angular/core';
import { CommonModule, TitleCasePipe, CurrencyPipe, DatePipe } from '@angular/common';
import { RouterModule } from '@angular/router';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, TitleCasePipe, CurrencyPipe, DatePipe],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  username = '';
  role = '';
  loading = true;
  today = new Date();

  totalIncome = 0;
  totalExpense = 0;
  netBalance = 0;

  recentTransactions: any[] = [];
  budgetTracking: any[] = [];
  pieSlices: { label: string; value: number; percent: number; color: string; offset: number }[] = [];

  private palette = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899'];

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.username = localStorage.getItem('username') ?? '';
    this.role = localStorage.getItem('role') ?? '';
    this.loadDashboard();
  }

  loadDashboard() {
    this.loading = true;
    this.api.getDashboard().subscribe({
      next: (res: any) => {
        const fin = res.financial_overview ?? {};
        this.totalIncome = +fin.total_income || 0;
        this.totalExpense = +fin.total_expense || 0;
        this.netBalance = +fin.net_balance || 0;

        this.recentTransactions = res.recent_transactions ?? [];
        this.budgetTracking = (res.budget_progress ?? []).slice(0, 4);
        this.buildPie(res.spending_breakdown ?? []);
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });
  }

  buildPie(breakdown: any[]) {
    const total = breakdown.reduce((s, b) => s + +b.amount, 0) || 1;
    let offset = 0;
    this.pieSlices = breakdown.map((b, i) => {
      const percent = (+b.amount / total) * 100;
      const slice = { label: b.category_name, value: +b.amount, percent, color: this.palette[i % this.palette.length], offset };
      offset += percent;
      return slice;
    });
  }

  getBudgetPercent(b: any): number {
    const spent = +(b.spent || b.total_spent || 0);
    const budget = +(b.budget_amount || b.amount || 1);
    return Math.min(Math.round((spent / budget) * 100), 100);
  }

  getBudgetColor(pct: number): string {
    if (pct >= 90) return '#ef4444';
    if (pct >= 70) return '#f59e0b';
    return '#10b981';
  }

  dashArray(pct: number): string {
    const c = 2 * Math.PI * 15.9;
    return `${(pct / 100) * c} ${c}`;
  }

  logout() {
    localStorage.clear();
    location.href = '/';
  }
}
