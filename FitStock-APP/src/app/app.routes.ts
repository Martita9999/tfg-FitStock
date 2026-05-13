import { Routes } from '@angular/router';
import { AdminDashboardComponent } from './components/admin-dashboard/admin-dashboard';
import { LoginComponent } from './components/login/login';
import { RegistroComponent } from './components/registro/registro';
import { ProductoList } from './components/producto-list/producto-list';
import { PrestamoList } from './components/prestamo-list/prestamo-list';
import { IncidenciaList } from './components/incidencia-list/incidencia-list';
import { UsuarioList } from './components/usuario-list/usuario-list';
import { MaterialList } from './components/material-list/material-list';
import { DashboardHomeComponent } from './components/dashboard-home/dashboard-home';

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: 'registro', component: RegistroComponent },
  {
    path: 'admin',
    component: AdminDashboardComponent,
    children: [
      { path: 'inventario', component: ProductoList },
      { path: 'prestamos', component: PrestamoList },
      { path: 'incidencias', component: IncidenciaList },
      { path: 'materiales', component: MaterialList },
      { path: 'usuarios', component: UsuarioList },
      { path: 'home', component: DashboardHomeComponent }, // Ruta principal del panel: muestra el resumen general o datos del cliente
      { path: '', redirectTo: 'home', pathMatch: 'full' }
    ]
  },
  { path: '', redirectTo: 'login', pathMatch: 'full' }
];
