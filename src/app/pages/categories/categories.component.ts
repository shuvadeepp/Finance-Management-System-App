import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-categories',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './categories.component.html',
  styleUrls: ['./categories.component.css']
})
export class CategoriesComponent {

  categories: any = [];
  role: any = '';
  validationErrors: any = {};
  isEdit = false;
  loading = false;
  saving = false;

  form = {
    id: '',
    category_name: '',
    category_type: ''
  };

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.role = localStorage.getItem('role');
    this.loadCategories();
  }

  logout() {
    localStorage.clear();
    location.href = '/';
  }

  loadCategories() {
    this.loading = true;
    this.api.getCategories()
      .subscribe({
        next: (res: any) => {
          console.log("res.data :::: ",res.data);
          
          this.categories = res.data;
          this.loading = false;
        },
        error: () => {
          this.loading = false;
        }
      });
  }

  storeCategory() {
    this.saving = true;
    this.api.createCategory(this.form)
      .subscribe({
        next: (res: any) => {
          this.saving = false;
          this.validationErrors = {};
          alert(res.message);
          this.resetForm();
          this.loadCategories();
        },
        error: (error) => {
          this.saving = false;
          if (error.status === 422) {
            this.validationErrors = error.error.errors;
          } else {
            alert(error.error?.message || 'Something went wrong');
          }
        }
      });
  }

  editCategory(category: any) {
    this.isEdit = true;
    this.form = {
      id: category.id,
      category_name: category.category_name,
      category_type: category.category_type
    };
  }

  updateCategory() {
    this.saving = true;
    this.api.updateCategory(this.form.id, this.form)
      .subscribe({
        next: (res: any) => {
          this.saving = false;
          this.validationErrors = {};
          alert(res.message);
          this.resetForm();
          this.loadCategories();
        },
        error: (error) => {
          this.saving = false;
          if (error.status === 422) {
            this.validationErrors = error.error.errors;
          } else {
            alert(error.error?.message || 'Something went wrong');
          }
        }
      });
  }

  deleteCategory(id: any) {
    if (confirm('Are you sure?')) {
      this.api.deleteCategory(id)
        .subscribe({
          next: (res: any) => {
            alert(res.message);
            this.loadCategories();
          },
          error: (error) => {
            alert(error.error?.message);
          }
        });
    }
  }

  resetForm() {
    this.isEdit = false;
    this.validationErrors = {};
    this.form = {
      id: '',
      category_name: '',
      category_type: ''
    };
  }
}