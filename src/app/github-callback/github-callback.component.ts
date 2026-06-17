import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-github-callback',
  standalone: true,
  // imports: [CommonModule, RouterModule],
  imports: [CommonModule],
  template: `
    <div class="d-flex justify-content-center align-items-center vh-100"
         style="background: #0d1b3e;">
      <div class="text-center text-white">
        <div class="spinner-border text-light mb-3" role="status"></div>
        <p class="mt-2">GitHub se login ho raha hai... ⏳</p>
      </div>
    </div>
  `
})
export class GithubCallbackComponent implements OnInit {

  constructor(
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.route.queryParams.subscribe(params => {

    // console.log('All params:', params);        
    // console.log('Avatar value:', params['avatar']); 

      const token = params['token'];
      const error = params['error'];
      const avatar = params['avatar'];

      if (token) {
        // Token aur user info store karo
        localStorage.setItem('token', token);
        if (avatar) {
          localStorage.setItem('avatar', avatar);
        }
        localStorage.setItem('username', params['username'] ?? '');
        localStorage.setItem('role', params['role'] ?? '');
        localStorage.setItem('user_id', params['user_id'] ?? '');

        // Dashboard pe redirect
        this.router.navigate(['/dashboard']);

      } else {
        // Error — login page pe wapas
        this.router.navigate(['/login']);
      }
    });
  }
}