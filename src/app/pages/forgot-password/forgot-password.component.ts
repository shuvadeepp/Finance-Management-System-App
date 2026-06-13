import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-forgot-password',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './forgot-password.component.html'
})
export class ForgotPasswordComponent {

  username = '';

  constructor(private api: ApiService, private router: Router) {}

  sendOtp()
  {
    this.api.forgotPassword({
      username: this.username
    }).subscribe({

      next: (res:any)=>{

        alert(res.message);
        this.router.navigate(['/reset-password']);
      },

      error:(err)=>{

        alert(
          err.error.message
        );
      }
    });
  }
}