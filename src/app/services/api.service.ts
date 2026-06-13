import { HttpClient, HttpHeaders } from '@angular/common/http';
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

  login(data:any) {
    return this.http.post(this.api + '/login', data);
  }

  getEmployees() {
    return this.http.get(this.api + '/employees', this.getHeaders());
  }

  createEmployee(data:any) { 
    return this.http.post(
      this.api + '/employees',
      data,
      this.getHeaders()
    );
  }

  updateEmployee(id:any, data:any) { 
    return this.http.put(
      this.api + '/employees/' + id,
      data,
      this.getHeaders()
    );
  }

  deleteEmployee(id:any) { 
    return this.http.delete(
      this.api + '/employees/' + id,
      this.getHeaders()
    );
  }

  getProjects() {
    return this.http.get(this.api + '/projects', this.getHeaders());
  }

  assignProject(data:any) {
    return this.http.post(this.api + '/assign-project', data, this.getHeaders());
  }

  getAssignedProjects() {
    return this.http.get(this.api + '/assigned-projects', this.getHeaders());
  }

  createProject(data:any) {

    return this.http.post(
      this.api + '/projects',
      data,
      this.getHeaders()
    );
  }

  updateProject(id:any, data:any) {

    return this.http.put(
      this.api + '/projects/' + id,
      data,
      this.getHeaders()
    );
  }

  deleteProject(id:any) {

    return this.http.delete(
      this.api + '/projects/' + id,
      this.getHeaders()
    );
  }


  // category api function
  getCategories() {
    return this.http.get(
      this.api + '/categories',
      this.getHeaders()
    );
  }

  createCategory(data:any) {
    return this.http.post(
      this.api + '/categories',
      data,
      this.getHeaders()
    );
  }

  updateCategory(id:any, data:any) {
    return this.http.put(
      this.api + '/categories/' + id,
      data,
      this.getHeaders()
    );
  }

  deleteCategory(id:any) {
    return this.http.delete(
      this.api + '/categories/' + id,
      this.getHeaders()
    );
  }

  // transaction
  getTransactions() {
    return this.http.get(
      this.api + '/transactions',
      this.getHeaders()
    );
  }

  createTransaction(data:any) {
    return this.http.post(
      this.api + '/transactions',
      data,
      this.getHeaders()
    );
  }

  updateTransaction(id:any,data:any) {
    return this.http.put(
      this.api + '/transactions/' + id,
      data,
      this.getHeaders()
    );
  }

  deleteTransaction(id:any) {
    return this.http.delete(
      this.api + '/transactions/' + id,
      this.getHeaders()
    );
  }

  // budgets
  getBudgets() {
    return this.http.get(
      this.api + '/budgets',
      this.getHeaders()
    );
  }

  createBudget(data:any) {
    return this.http.post(
      this.api + '/budgets',
      data,
      this.getHeaders()
    );
  }

  updateBudget(id:any,data:any) {
    return this.http.put(
      this.api + '/budgets/' + id,
      data,
      this.getHeaders()
    );
  }

  deleteBudget(id:any) {
    return this.http.delete(
      this.api + '/budgets/' + id,
      this.getHeaders()
    );
  }

  getBudgetTracking() {
    return this.http.get(
      this.api + '/budget-tracking',
      this.getHeaders()
    );
  }

  getBudgetOverview() {
    return this.http.get(
      this.api + '/budget-overview',
      this.getHeaders()
    );
  }

  getDashboard() {
    return this.http.get(this.api + '/dashboard', this.getHeaders());
  }

  forgotPassword(data:any)
  {
    return this.http.post(
      this.api + '/forgot-password',
      data
    );
  }

  verifyOtp(data:any)
  {
    return this.http.post(
      this.api + '/verify-otp',
      data
    );
  }

  resetPassword(data:any)
  {
    return this.http.post(
      this.api + '/reset-password',
      data
    );
  }
}