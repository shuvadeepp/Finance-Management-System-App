import { Component } from '@angular/core';
import { Router, RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent {
  title = 'ui';

  constructor(private router: Router) {
    window.addEventListener('pageshow', (event: PageTransitionEvent) => {
      if (event.persisted && !localStorage.getItem('token')) {
        this.router.navigate(['/login']);
      }
    });
  }
}
