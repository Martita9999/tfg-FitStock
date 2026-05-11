import { Routes } from '@angular/router';
import { AdminDashboardComponent } from './components/admin-dashboard/admin-dashboard';  // Layout del panel admin
import { LoginComponent } from './components/login/login';                              // Página de inicio de sesión
import { RegistroComponent } from './components/registro/registro';                      // Página de registro
import { ProductoList } from './components/producto-list/producto-list';                   // Gestión de productos
import { PrestamoList } from './components/prestamo-list/prestamo-list';                   // Gestión de préstamos
import { IncidenciaList } from './components/incidencia-list/incidencia-list';             // Gestión de incidencias
import { UsuarioList } from './components/usuario-list/usuario-list';                      // Gestión de usuarios
import { MaterialList } from './components/material-list/material-list';                   // Gestión de materiales

// Definición de todas las rutas de la aplicación
export const routes: Routes = [
  { path: 'login', component: LoginComponent },                                           // /login
  { path: 'registro', component: RegistroComponent },                                     // /registro
  {
    path: 'admin',                                                                         // /admin/*
    component: AdminDashboardComponent,                                                    // Layout con sidebar
    children: [
      { path: 'inventario', component: ProductoList },                                     // /admin/inventario
      { path: 'prestamos', component: PrestamoList },                                      // /admin/prestamos
      { path: 'incidencias', component: IncidenciaList },                                  // /admin/incidencias
      { path: 'materiales', component: MaterialList },                                     // /admin/materiales
      { path: 'usuarios', component: UsuarioList },                                        // /admin/usuarios
      { path: '', redirectTo: 'inventario', pathMatch: 'full' }                           // /admin redirige a inventario
    ]
  },
  { path: '', redirectTo: 'login', pathMatch: 'full' }                                    // Ruta raíz redirige a login
];
