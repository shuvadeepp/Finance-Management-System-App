import { Routes } from '@angular/router';
import { authGuard } from './guards/auth.guard';
import { noAuthGuard } from './guards/no-auth.guard';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login',    canActivate: [noAuthGuard], loadComponent: () => import('./pages/login/login.component').then(m => m.LoginComponent) },
  { path: 'register', canActivate: [noAuthGuard], loadComponent: () => import('./register/register.component').then(m => m.RegisterComponent) },
  { path: 'dashboard',    canActivate: [authGuard], loadComponent: () => import('./pages/dashboard/dashboard.component').then(m => m.DashboardComponent) },
  { path: 'categories',   canActivate: [authGuard], loadComponent: () => import('./pages/categories/categories.component').then(m => m.CategoriesComponent) },
  { path: 'transactions', canActivate: [authGuard], loadComponent: () => import('./pages/transactions/transactions.component').then(m => m.TransactionsComponent) },
  { path: 'budgets',      canActivate: [authGuard], loadComponent: () => import('./pages/budgets/budgets.component').then(m => m.BudgetsComponent) },
  { path: 'forgot-password', canActivate: [noAuthGuard], loadComponent: () => import('./pages/forgot-password/forgot-password.component').then(m => m.ForgotPasswordComponent) },
  { path: 'reset-password',  canActivate: [noAuthGuard], loadComponent: () => import('./pages/reset-password/reset-password.component').then(m => m.ResetPasswordComponent) },

  // ✅ GitHub OAuth Callback — no guard, lazy loaded
  { path: 'auth/github/success', loadComponent: () => import('./github-callback/github-callback.component').then(m => m.GithubCallbackComponent) },
];
