import { Routes } from '@angular/router';
import { AdminDashboardSectionComponent } from './components/admin-dashboard-section/admin-dashboard-section';
import { AdminDashboardComponent } from './components/admin-dashboard/admin-dashboard';
import { ProductoListComponent } from './components/producto-list/producto-list';
import { PrestamoListComponent } from './components/prestamo-list/prestamo-list';
import { IncidenciaListComponent } from './components/incidencia-list/incidencia-list';
import { UsuarioListComponent } from './components/usuario-list/usuario-list';

// Definición de rutas para el panel de administración
export const routes: Routes = [
  { path: 'admin/dashboard', component: AdminDashboardSectionComponent },
  { path: 'admin/inventario', component: AdminDashboardComponent },
  { path: 'admin/productos', component: ProductoListComponent },
  { path: 'admin/prestamos', component: PrestamoListComponent },
  { path: 'admin/incidencias', component: IncidenciaListComponent },
  { path: 'admin/usuarios', component: UsuarioListComponent },
  { path: '', redirectTo: 'admin/dashboard', pathMatch: 'full' },
  { path: 'admin', redirectTo: 'admin/dashboard', pathMatch: 'full' }
];
