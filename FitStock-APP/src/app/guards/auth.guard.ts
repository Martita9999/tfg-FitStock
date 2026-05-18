import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { UsuarioService } from '../services/usuario';

export const authGuard = () => {
  const usuarioService = inject(UsuarioService);
  const router = inject(Router);

  usuarioService.checkSession();

  const user = usuarioService.currentUserSubject.getValue();
  if (!user) {
    router.navigate(['/login']);
    return false;
  }
  return true;
};
