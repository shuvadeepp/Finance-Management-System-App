import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './reset-password.component.html'
})
export class ResetPasswordComponent {

  form = {
    username: '',
    otp: '',
    password: '',
    password_confirmation: ''
  };

  constructor(private api: ApiService, private router: Router) {}

  resetPassword()
  {
    if(!this.form.username.trim())
    {
      alert('Username is required');
      return;
    }

    if(!this.form.otp.trim())
    {
      alert('OTP is required');
      return;
    }

    if(!this.form.password)
    {
      alert('Password is required');
      return;
    }

    if(this.form.password.length < 8)
    {
      alert('Password must be at least 8 characters');
      return;
    }

    if(this.form.password !== this.form.password_confirmation)
    {
      alert('Password and Confirm Password do not match');
      return;
    }

    this.api.resetPassword(this.form)
    .subscribe({

      next:(res:any)=>{

        alert(res.message);
        this.router.navigate(['/login']);
      },

      error:(err)=>{

        if(err.status === 422)
        {
          const errors = err.error.errors;

          let message = '';

          Object.keys(errors).forEach(key => {

            message += errors[key][0] + '\n';

          });

          alert(message);
        }
        else if(err.status === 400 && err.error?.message === 'Invalid OTP.')
        {
          alert(err.error.message);
          this.router.navigate(['/forgot-password']);
        }
        else
        {
          alert(
            err.error?.message ||
            'Something went wrong'
          );
        }
      }
    });
  }
}