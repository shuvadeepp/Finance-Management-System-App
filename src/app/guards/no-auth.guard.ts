import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const noAuthGuard: CanActivateFn = () => {
  const router = inject(Router);
  if (localStorage.getItem('token')) {
    return router.createUrlTree(['/dashboard']);
  }
  return true;
};
