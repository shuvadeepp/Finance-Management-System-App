import { Component } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-transactions',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './transactions.component.html',
  styleUrls: ['./transactions.component.css']
})
export class TransactionsComponent {

  transactions: any[] = [];

  categories: any[] = [];

  role: any = '';

  isEdit = false;

  loading = false;

  saving = false;

  form = {
    id: '',
    transaction_date: '',
    amount: '',
    transaction_type: '',
    description: '',
    category_id: ''
  };

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.role = localStorage.getItem('role');
    this.loadTransactions();
    this.loadCategories();
  }

  logout() {
    localStorage.clear();
    location.href = '/';
  }

  loadTransactions() {
    this.loading = true;
    this.api.getTransactions()
      .subscribe({
        next: (res: any) => {
          // console.log("res :::::: ", res); return false;
          
          this.transactions = res;
          this.loading = false;
        },
        error: () => {
          this.loading = false;
        }
      });
  }

  loadCategories() {
    this.api.getCategories()
      .subscribe((res: any) => {
        this.categories = res.data;
      });
  }

  getCategoryName(categoryId: any): string {
    const category = this.categories.find(c => c.id === categoryId);
    return category ? category.category_name : '';
  }

  saveTransaction() {
    if (
      !this.form.transaction_date ||
      !this.form.amount ||
      !this.form.transaction_type ||
      !this.form.category_id
    ) {
      alert('All fields are required');
      return;
    }

    this.saving = true;
    this.api.createTransaction(this.form)
      .subscribe({
        next: (res: any) => {
          this.saving = false;
          alert(res.message);
          this.resetForm();
          this.loadTransactions();
        },
        error: (err) => {
          this.saving = false;
          if (err.error.errors) {
            const firstError = Object.values(err.error.errors)[0] as string[];
            alert(firstError[0]);
          }
        }
      });
  }

  editTransaction(t: any) {
    this.isEdit = true;
    this.form = {
      id: t.id,
      transaction_date: t.transaction_date,
      amount: t.amount,
      transaction_type: t.transaction_type,
      description: t.description,
      category_id: t.category_id
    };
  }

  updateTransaction() {
    this.saving = true;
    this.api.updateTransaction(this.form.id, this.form)
      .subscribe({
        next: (res: any) => {
          this.saving = false;
          alert(res.message);
          this.resetForm();
          this.loadTransactions();
        },
        error: () => {
          this.saving = false;
        }
      });
  }

  deleteTransaction(id: any) {
    if (confirm('Delete Transaction ?')) {
      this.api.deleteTransaction(id)
        .subscribe((res: any) => {
          alert(res.message);
          this.loadTransactions();
        });
    }
  }

  resetForm() {
    this.isEdit = false;
    this.form = {
      id: '',
      transaction_date: '',
      amount: '',
      transaction_type: '',
      description: '',
      category_id: ''
    };
  }
}