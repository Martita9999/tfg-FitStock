import { Routes } from '@angular/router';
import { AdminDashboardComponent } from './components/admin-dashboard/admin-dashboard';

export const routes: Routes = [
  { path: 'admin', component: AdminDashboardComponent },
  { path: '', redirectTo: 'admin', pathMatch: 'full' } // Redirige al entrar
];