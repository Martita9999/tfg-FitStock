import { TestBed } from '@angular/core/testing';
import { App } from './app';

// Pruebas unitarias del componente raíz App
describe('App', () => {
  // Configuración del módulo de pruebas antes de cada test
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [App],     // Importa el componente standalone
    }).compileComponents();
  });

  // Verifica que el componente se crea correctamente
  it('should create the app', () => {
    const fixture = TestBed.createComponent(App);
    const app = fixture.componentInstance;
    expect(app).toBeTruthy();   // El componente debe existir
  });

  // Verifica que el título se renderiza en la plantilla
  it('should render title', async () => {
    const fixture = TestBed.createComponent(App);
    await fixture.whenStable();
    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('h1')?.textContent).toContain('Hello, fitStock-front');
  });
});
