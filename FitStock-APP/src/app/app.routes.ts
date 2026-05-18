import { Routes } from '@angular/router';
import { AdminDashboardComponent } from './components/admin-dashboard/admin-dashboard';
import { LoginComponent } from './components/login/login';
import { RegistroComponent } from './components/registro/registro';
import { PortalComponent } from './components/portal/portal';
import { ContactoComponent } from './components/contacto/contacto';
import { ProductoList } from './components/producto-list/producto-list';
import { PrestamoList } from './components/prestamo-list/prestamo-list';
import { IncidenciaList } from './components/incidencia-list/incidencia-list';
import { UsuarioList } from './components/usuario-list/usuario-list';
import { MaterialList } from './components/material-list/material-list';
import { DashboardHomeComponent } from './components/dashboard-home/dashboard-home';
import { authGuard } from './guards/auth.guard';

/*
 * Rutas de la aplicación.
 * Públicas: portal, login, registro, contacto.
 * Protegidas (bajo /admin): dashboard con sidebar e hijos.
 * /usuarios/:rol permite filtrar por tipo desde URL.
 */
export const routes: Routes = [
  { path: '', component: PortalComponent },                              // Landing pública
  { path: 'login', component: LoginComponent },
  { path: 'registro', component: RegistroComponent },
  { path: 'contacto', component: ContactoComponent },
  {
    path: 'admin',
    canActivate: [authGuard],                                            // Protegida: redirige a /login si no hay sesión
    component: AdminDashboardComponent,                                  // Layout con sidebar + header
    children: [
      { path: 'inventario', component: ProductoList },
      { path: 'prestamos/:vista', component: PrestamoList },
      { path: 'prestamos', component: PrestamoList },
      { path: 'materiales/:vista', component: MaterialList },
      { path: 'materiales', component: MaterialList },
      { path: 'incidencias/:vista', component: IncidenciaList },
      { path: 'incidencias', component: IncidenciaList },
      { path: 'usuarios/:rol', component: UsuarioList },                 // Filtro por rol en URL
      { path: 'usuarios', component: UsuarioList },
      { path: 'home', component: DashboardHomeComponent },
      { path: '', redirectTo: 'home', pathMatch: 'full' }                // Redirige a home por defecto
    ]
  }
];
