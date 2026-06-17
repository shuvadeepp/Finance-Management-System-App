import { ApplicationConfig, importProvidersFrom } from '@angular/core';

import { provideRouter } from '@angular/router';

import { routes } from './app.routes';

// import { provideHttpClient } from '@angular/common/http';

import { FormsModule } from '@angular/forms';

import { provideHttpClient, withInterceptors } from '@angular/common/http';

import { authInterceptor } from './guards/auth.interceptor';

export const appConfig: ApplicationConfig = {

  providers: [

    provideRouter(routes),

    provideHttpClient(withInterceptors([authInterceptor])),

    importProvidersFrom(FormsModule)

  ]
};