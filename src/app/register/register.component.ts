import { Component } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css']
})
export class RegisterComponent {

  username: string = '';
  password: string = '';
  role: string = 'EMPLOYEE';

  constructor(
    private http: HttpClient,
    private router: Router
  ) {}

  // register() {

  //   const bodyData = {

  //     username: this.username,
  //     password: this.password,
  //     role: this.role

  //   };

  //   this.http.post(
  //     'http://127.0.0.1:8000/api/register',
  //     bodyData
  //   )
  //   .subscribe({

  //     next: (result: any) => {

  //       alert('User Registered Successfully');

  //       this.router.navigate(['/']);
  //     },

  //     error: (error) => {

  //       console.error(error);

  //       alert(
  //         error?.error?.message ||
  //         'Registration Failed'
  //       );
  //     }
  //   });
  // }

  register() {

    // Frontend Validation

    if (!this.username.trim()) {
      alert('Username is required');
      return;
    }

    if (!this.password.trim()) {
      alert('Password is required');
      return;
    }

    if (this.password.length < 6) {
      alert('Password must be at least 6 characters');
      return;
    }

    if (!this.role) {
      alert('Role is required');
      return;
    }

    const bodyData = {
      username: this.username,
      password: this.password,
      role: this.role
    };

    this.http.post(
      'http://127.0.0.1:8000/api/register',
      bodyData
    )
    .subscribe({

      next: (result: any) => {

        alert('User Registered Successfully');

        this.router.navigate(['/']);
      },

      error: (error) => {

        console.error(error);

        // Laravel Validation Errors

        if (error.status === 422 && error.error?.errors) {

          const errors = error.error.errors;

          const firstKey = Object.keys(errors)[0];

          alert(errors[firstKey][0]);

        } else {

          alert(
            error?.error?.message ||
            'Registration Failed'
          );
        }
      }
    });
  }
}