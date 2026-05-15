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

// Configuración de rutas de la aplicación
export const routes: Routes = [
  { path: '', component: PortalComponent },                // Página de aterrizaje pública
  { path: 'login', component: LoginComponent },            // Inicio de sesión
  { path: 'registro', component: RegistroComponent },      // Registro de nuevos usuarios
  { path: 'contacto', component: ContactoComponent },      // Formulario de contacto
  {
    path: 'admin',                                          // Panel de administración (requiere sidebar)
    component: AdminDashboardComponent,
    children: [
      { path: 'inventario', component: ProductoList },     // Gestión de productos y stock
      { path: 'prestamos', component: PrestamoList },      // Préstamos de material
      { path: 'incidencias', component: IncidenciaList },  // Incidencias de máquinas
      { path: 'materiales', component: MaterialList },      // Máquinas del gimnasio
      { path: 'usuarios/:rol', component: UsuarioList },     // Usuarios filtrados por rol
      { path: 'usuarios', component: UsuarioList },         // Gestión de usuarios (todos)
      { path: 'home', component: DashboardHomeComponent },  // Resumen general / dashboard principal
      { path: '', redirectTo: 'home', pathMatch: 'full' }   // Redirección por defecto dentro del panel
    ]
  }
];
