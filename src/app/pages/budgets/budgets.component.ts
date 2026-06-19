import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-budgets',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './budgets.component.html',
  styleUrls: ['./budgets.component.css']
})
export class BudgetsComponent implements OnInit {

  overview: any = {};
  categories: any[] = [];
  budgets: any[] = [];
  tracking: any[] = [];
  role: any = '';
  validationErrors: any = {};
  isEdit = false;
  loading = true;
  saving = false;

  form = {
    id: '',
    category_id: '',
    budget_month: '',
    budget_year: '',
    budget_amount: ''
  };

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.role = localStorage.getItem('role');
    this.loadAll();
  }

  loadAll() {
    this.loading = true;
    let done = 0;
    const check = () => { if (++done === 4) this.loading = false; };

    this.api.getBudgetOverview().subscribe({ next: (r: any) => { this.overview = r; check(); }, error: () => check() });
    this.api.getCategories().subscribe({ next: (r: any) => { this.categories = r.data; check(); }, error: () => check() });
    this.api.getBudgets().subscribe({ next: (r: any) => { this.budgets = r; check(); }, error: () => check() });
    this.api.getBudgetTracking().subscribe({ next: (r: any) => { this.tracking = r; check(); }, error: () => check() });
  }

  reload() {
    this.api.getBudgetOverview().subscribe({ next: (r: any) => this.overview = r });
    this.api.getCategories().subscribe({ next: (r: any) => this.categories = r.data });
    this.api.getBudgets().subscribe({ next: (r: any) => this.budgets = r });
    this.api.getBudgetTracking().subscribe({ next: (r: any) => this.tracking = r });
  }

  getPercent(spent: number, budget: number): number {
    if (!budget || budget === 0) return 0;
    return Math.min(Math.round((spent / budget) * 100), 100);
  }

  saveBudget() {
    this.saving = true;
    this.api.createBudget(this.form).subscribe({
      next: (res: any) => { this.saving = false; this.validationErrors = {}; alert(res.message); this.resetForm(); this.reload(); },
      error: (err: any) => { this.saving = false; if (err.status === 422) this.validationErrors = err.error.errors; else alert(err.error?.message || 'Something went wrong'); }
    });
  }

  editBudget(budget: any) {
    this.isEdit = true;
    this.form = { id: budget.id, category_id: budget.category_id, budget_month: budget.budget_month, budget_year: budget.budget_year, budget_amount: budget.budget_amount };
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  updateBudget() {
    this.saving = true;
    this.api.updateBudget(this.form.id, this.form).subscribe({
      next: (res: any) => { this.saving = false; this.validationErrors = {}; alert(res.message); this.resetForm(); this.reload(); },
      error: (err: any) => { this.saving = false; if (err.status === 422) this.validationErrors = err.error.errors; else alert(err.error?.message || 'Something went wrong'); }
    });
  }

  deleteBudget(id: any) {
    if (confirm('Are you sure you want to delete this budget?')) {
      this.api.deleteBudget(id).subscribe({
        next: (res: any) => { alert(res.message); this.reload(); },
        error: (err: any) => alert(err.error?.message)
      });
    }
  }

  resetForm() {
    this.isEdit = false;
    this.validationErrors = {};
    this.form = { id: '', category_id: '', budget_month: '', budget_year: '', budget_amount: '' };
  }

  getStatusClass(status: string): string {
    if (status === 'Safe') return 'bgt-status-safe';
    if (status === 'Warning') return 'bgt-status-warning';
    if (status === 'Exceeded') return 'bgt-status-exceeded';
    return '';
  }

  logout() {
    this.api.logout().subscribe({
      next: () => { localStorage.clear(); location.href = '/'; },
      error: () => { localStorage.clear(); location.href = '/'; }
    });
  }
}