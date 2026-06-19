import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ApiService {

  api = 'http://127.0.0.1:8000/api';

  constructor(private http: HttpClient) {}

  getHeaders() {
    return {
      headers: new HttpHeaders({
        Authorization: 'Bearer ' + localStorage.getItem('token')
      })
    };
  }

  // ── Auth ────────────────────────────────────────────────────────────────

  login(data: any) {
    return this.http.post(this.api + '/login', data);
  }

  logout() {
    return this.http.post(this.api + '/logout', {}, this.getHeaders());
  }

  refreshToken() {
    return this.http.post(this.api + '/refresh-token', {}, this.getHeaders());
  }

  forgotPassword(data: any) {
    return this.http.post(this.api + '/forgot-password', data);
  }

  verifyOtp(data: any) {
    return this.http.post(this.api + '/verify-otp', data);
  }

  resetPassword(data: any) {
    return this.http.post(this.api + '/reset-password', data);
  }

  // ── Employees ───────────────────────────────────────────────────────────

  getEmployees() {
    return this.http.get(this.api + '/employees', this.getHeaders());
  }

  createEmployee(data: any) {
    return this.http.post(this.api + '/employees', data, this.getHeaders());
  }

  updateEmployee(id: any, data: any) {
    return this.http.put(this.api + '/employees/' + id, data, this.getHeaders());
  }

  deleteEmployee(id: any) {
    return this.http.delete(this.api + '/employees/' + id, this.getHeaders());
  }

  // ── Categories ──────────────────────────────────────────────────────────

  getCategories() {
    return this.http.get(this.api + '/categories', this.getHeaders());
  }

  createCategory(data: any) {
    return this.http.post(this.api + '/categories', data, this.getHeaders());
  }

  updateCategory(id: any, data: any) {
    return this.http.put(this.api + '/categories/' + id, data, this.getHeaders());
  }

  deleteCategory(id: any) {
    return this.http.delete(this.api + '/categories/' + id, this.getHeaders());
  }

  // ── Transactions ────────────────────────────────────────────────────────

  getTransactions(filters?: {
    date_from?: string;
    date_to?: string;
    category_id?: string;
    transaction_type?: string;
  }) {
    let params = new HttpParams();
    if (filters) {
      if (filters.date_from)        params = params.set('date_from', filters.date_from);
      if (filters.date_to)          params = params.set('date_to', filters.date_to);
      if (filters.category_id)      params = params.set('category_id', filters.category_id);
      if (filters.transaction_type) params = params.set('transaction_type', filters.transaction_type);
    }
    return this.http.get(this.api + '/transactions', {
      headers: new HttpHeaders({ Authorization: 'Bearer ' + localStorage.getItem('token') }),
      params
    });
  }

  createTransaction(data: any) {
    return this.http.post(this.api + '/transactions', data, this.getHeaders());
  }

  updateTransaction(id: any, data: any) {
    return this.http.put(this.api + '/transactions/' + id, data, this.getHeaders());
  }

  deleteTransaction(id: any) {
    return this.http.delete(this.api + '/transactions/' + id, this.getHeaders());
  }

  // ── Budgets ─────────────────────────────────────────────────────────────

  getBudgets() {
    return this.http.get(this.api + '/budgets', this.getHeaders());
  }

  createBudget(data: any) {
    return this.http.post(this.api + '/budgets', data, this.getHeaders());
  }

  updateBudget(id: any, data: any) {
    return this.http.put(this.api + '/budgets/' + id, data, this.getHeaders());
  }

  deleteBudget(id: any) {
    return this.http.delete(this.api + '/budgets/' + id, this.getHeaders());
  }

  getBudgetTracking() {
    return this.http.get(this.api + '/budget-tracking', this.getHeaders());
  }

  getBudgetOverview() {
    return this.http.get(this.api + '/budget-overview', this.getHeaders());
  }

  // ── Dashboard ───────────────────────────────────────────────────────────

  getDashboard() {
    return this.http.get(this.api + '/dashboard', this.getHeaders());
  }
}
