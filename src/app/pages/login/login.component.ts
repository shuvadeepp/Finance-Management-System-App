import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink, Router } from '@angular/router';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent {

  form = { username: '', password: '' };

  errors: { username?: string; password?: string } = {};
  apiError = '';
  loading = false;
  showPass = false;

  constructor(private api: ApiService, private router: Router) {}

  clearError(field: 'username' | 'password') {
    this.errors[field] = '';
    this.apiError = '';
  }

  login() {
    this.errors = {};
    this.apiError = '';

    // Inline validation — no alert()
    let valid = true;
    if (!this.form.username.trim()) {
      this.errors.username = 'Username is required.';
      valid = false;
    }
    if (!this.form.password.trim()) {
      this.errors.password = 'Password is required.';
      valid = false;
    }
    if (!valid) return;

    this.loading = true;

    this.api.login(this.form).subscribe({
      next: (res: any) => {
        this.loading = false;
        localStorage.setItem('token',    res.token);
        localStorage.setItem('role',     res.user.role);
        localStorage.setItem('user_id',  res.user.id);
        localStorage.setItem('username', res.user.username);
        this.router.navigate(['/dashboard']);
      },
      error: (err: any) => {
        this.loading = false;
        if (err.status === 422 && err.error?.errors) {
          const e = err.error.errors;
          if (e.username) this.errors.username = e.username[0];
          if (e.password) this.errors.password = e.password[0];
        } else {
          this.apiError = err.error?.message || 'Login failed. Please try again.';
        }
      }
    });
  }
}