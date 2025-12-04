import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import path from 'path';
import iconsPlugin from './vite.icons.plugin.js';

/**
 * =========================================================================
 * CONFIGURACIÓN OPTIMIZADA DE VITE
 * =========================================================================
 * 
 * En lugar de incluir TODAS las librerías vendor con globs, 
 * solo incluimos las que realmente usa el proyecto.
 * 
 * INSTRUCCIONES PARA AGREGAR NUEVAS LIBRERÍAS:
 * 1. Buscar el archivo JS en: resources/assets/vendor/libs/{nombre}/
 * 2. Buscar el archivo SCSS/CSS en: resources/assets/vendor/libs/{nombre}/
 * 3. Agregar a los arrays correspondientes abajo
 * =========================================================================
 */

// Processing Window Assignment for Libs like jKanban, pdfMake
function libsWindowAssignment() {
  return {
    name: 'libsWindowAssignment',
    transform(src, id) {
      if (id.includes('jkanban.js')) {
        return src.replace('this.jKanban', 'window.jKanban');
      } else if (id.includes('vfs_fonts')) {
        return src.replaceAll('this.pdfMake', 'window.pdfMake');
      }
    }
  };
}

// =========================================================================
// ARCHIVOS JS DE VENDOR (CORE - Siempre necesarios)
// =========================================================================
const vendorCoreJsFiles = [
  'resources/assets/vendor/js/bootstrap.js',
  'resources/assets/vendor/js/dropdown-hover.js',
  'resources/assets/vendor/js/helpers.js',
  'resources/assets/vendor/js/mega-dropdown.js',
  'resources/assets/vendor/js/menu.js',
  'resources/assets/vendor/js/template-customizer.js',
];

// =========================================================================
// ARCHIVOS JS DE PAGE (recursos/assets/js/*.js usados por el proyecto)
// =========================================================================
const pageJsFiles = [
  'resources/assets/js/config.js',
  'resources/assets/js/main.js',
  // Agregar solo los que uses del template:
  // 'resources/assets/js/dashboards-analytics.js',
  // 'resources/assets/js/dashboards-crm.js',
];

// =========================================================================
// LIBRERÍAS JS - Solo las que usa el proyecto taller
// =========================================================================
const libsJsFiles = [
  // === CORE (Requeridas por el template) ===
  'resources/assets/vendor/libs/jquery/jquery.js',
  'resources/assets/vendor/libs/popper/popper.js',
  'resources/assets/vendor/libs/hammer/hammer.js',
  
  // === UI del template ===
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
  'resources/assets/vendor/libs/node-waves/node-waves.js',
  
  // === UI adicional del template ===
  'resources/assets/vendor/libs/@algolia/autocomplete-js.js',               // Búsqueda
  'resources/assets/vendor/libs/pickr/pickr.js',                            // Color picker (customizer)
  
  // === Funcionalidades del proyecto taller ===
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',  // Tablas CRUD
  'resources/assets/vendor/libs/select2/select2.js',                        // Dropdowns
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',                // Alertas
  'resources/assets/vendor/libs/@form-validation/popular.js',               // Validación
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  'resources/assets/vendor/libs/dropzone/dropzone.js',                      // Subir fotos
  'resources/assets/vendor/libs/fullcalendar/fullcalendar.js',              // Calendario
  'resources/assets/vendor/libs/moment/moment.js',                          // Fechas
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',                  // Input masks
];

// =========================================================================
// ARCHIVOS SCSS/CSS DE LIBRERÍAS - Solo las que usa el proyecto taller
// =========================================================================
const libsScssFiles = [
  // === UI del template ===
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
  'resources/assets/vendor/libs/node-waves/node-waves.scss',
  
  // === Funcionalidades del proyecto taller ===
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-select-bs5/select.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/dropzone/dropzone.scss',
  'resources/assets/vendor/libs/fullcalendar/fullcalendar.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/pickr/pickr-themes.scss',
  'resources/assets/vendor/libs/typeahead-js/typeahead.scss',
];

// =========================================================================
// ARCHIVOS SCSS CORE (Temas y estilos base)
// =========================================================================
const coreScssFiles = [
  'resources/assets/vendor/scss/core.scss',
];

// =========================================================================
// ARCHIVOS SCSS DE PÁGINAS - Solo las del proyecto taller
// =========================================================================
const pageScssFiles = [
  // Autenticación (login, registro, recuperar contraseña)
  'resources/assets/vendor/scss/pages/page-auth.scss',
  
  // Calendario de órdenes
  'resources/assets/vendor/scss/pages/app-calendar.scss',
  
  // Chat/comentarios en órdenes de trabajo
  'resources/assets/vendor/scss/pages/app-chat.scss',
];

// =========================================================================
// FUENTES
// =========================================================================
const fontsScssFiles = [
  'resources/assets/vendor/fonts/flag-icons.scss',
  'resources/assets/vendor/fonts/fontawesome.scss',
  'resources/assets/vendor/fonts/iconify/iconify.css',  // Generado por iconsPlugin()
];

// No hay archivos JS de fuentes en este template

// =========================================================================
// ARCHIVOS JS PERSONALIZADOS DEL PROYECTO
// =========================================================================
const customJsFiles = [
  'resources/js/laravel-user-management.js',
  'resources/js/configuracion-marcas.js',
  'resources/js/configuracion-modelos.js',
  'resources/js/configuracion-usuarios.js',
  'resources/js/configuracion-vehiculos.js',
  'resources/js/configuracion-clientes.js',
  'resources/js/configuracion-ordenes-trabajo.js',
  'resources/js/detalle-orden-trabajo.js',
  'resources/js/dashboard-taller.js',
  'resources/js/calendario.js',
];

export default defineConfig({
  plugins: [
    laravel({
      input: [
        // CSS base
        'resources/css/app.css',
        'resources/assets/css/demo.css',
        
        // JS base
        'resources/js/app.js',
        
        // Vendor Core JS
        ...vendorCoreJsFiles,
        
        // Page JS (template)
        ...pageJsFiles,
        
        // Librerías JS
        ...libsJsFiles,
        
        // JS personalizados del proyecto
        ...customJsFiles,
        
        // Estilos Core SCSS
        ...coreScssFiles,
        
        // Estilos de páginas
        ...pageScssFiles,
        
        // Estilos de librerías
        ...libsScssFiles,
        
        // Fuentes
        ...fontsScssFiles,
      ],
      refresh: true
    }),
    html(),
    libsWindowAssignment(),
    iconsPlugin()
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources')
    }
  },
  json: {
    stringify: true
  },
  build: {
    commonjsOptions: {
      include: [/node_modules/]
    }
  }
});
