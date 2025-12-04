/**
 * Taller Espacios - Drag and Drop functionality
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  // Variables
  const espaciosContainer = document.getElementById('espacios-container');
  const btnRefrescar = document.getElementById('btn-refrescar');
  const btnRefrescarOverlay = document.getElementById('btn-refrescar-overlay');
  const bloqueoOverlay = document.getElementById('bloqueo-overlay');
  const tiempoRestanteContainer = document.getElementById('tiempo-restante');
  const tiempoTexto = document.getElementById('tiempo-texto');

  // CSRF Token setup
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Initialize Notyf for toast notifications
  const notyf = new Notyf({
    duration: 3000,
    dismissible: true,
    ripple: true,
    position: { x: 'right', y: 'top' },
    types: [
      {
        type: 'success',
        background: '#71dd37',
        icon: {
          className: 'icon-base ti tabler-circle-check-filled icon-md text-white',
          tagName: 'i'
        }
      },
      {
        type: 'error',
        background: '#ff3e1d',
        icon: {
          className: 'icon-base ti tabler-xbox-x-filled icon-md text-white',
          tagName: 'i'
        }
      },
      {
        type: 'warning',
        background: '#ffab00',
        icon: {
          className: 'icon-base ti tabler-alert-triangle-filled icon-md text-white',
          tagName: 'i'
        }
      },
      {
        type: 'info',
        background: '#03c3ec',
        icon: {
          className: 'icon-base ti tabler-info-circle-filled icon-md text-white',
          tagName: 'i'
        }
      }
    ]
  });

  // Timer variables
  const TIEMPO_SESION = 15; // segundos
  let sessionTimer = null;
  let countdownTimer = null;
  let tiempoRestante = TIEMPO_SESION;
  let vistaBloqueada = false;

  // Initialize Sortable on each occupied space for dragging
  initializeDragAndDrop();

  // Initialize session timer
  iniciarTemporizador();

  // Refresh button
  if (btnRefrescar) {
    btnRefrescar.addEventListener('click', handleRefrescar);
  }

  // Overlay refresh button
  if (btnRefrescarOverlay) {
    btnRefrescarOverlay.addEventListener('click', handleRefrescar);
  }

  /**
   * Handle refresh action (from button or overlay)
   */
  async function handleRefrescar() {
    await refrescarEspacios();
    desbloquearVista();
    iniciarTemporizador();
  }

  /**
   * Start/restart the session timer
   */
  function iniciarTemporizador() {
    // Clear existing timers
    if (sessionTimer) clearTimeout(sessionTimer);
    if (countdownTimer) clearInterval(countdownTimer);

    tiempoRestante = TIEMPO_SESION;
    vistaBloqueada = false;
    actualizarDisplayTiempo();

    // Start countdown display
    countdownTimer = setInterval(() => {
      tiempoRestante--;
      actualizarDisplayTiempo();

      if (tiempoRestante <= 0) {
        clearInterval(countdownTimer);
      }
    }, 1000);

    // Set session timeout
    sessionTimer = setTimeout(() => {
      bloquearVista();
    }, TIEMPO_SESION * 1000);
  }

  /**
   * Update the time display
   */
  function actualizarDisplayTiempo() {
    if (tiempoTexto) {
      tiempoTexto.textContent = `${tiempoRestante}s`;
    }

    if (tiempoRestanteContainer) {
      tiempoRestanteContainer.classList.remove('warning', 'danger');

      if (tiempoRestante <= 5) {
        tiempoRestanteContainer.classList.add('danger');
      } else if (tiempoRestante <= 10) {
        tiempoRestanteContainer.classList.add('warning');
      }
    }
  }

  /**
   * Block the view (show overlay, disable drag)
   */
  function bloquearVista() {
    vistaBloqueada = true;

    // Show overlay
    if (bloqueoOverlay) {
      bloqueoOverlay.classList.add('active');
    }

    // Disable drag and drop
    if (espaciosContainer) {
      espaciosContainer.classList.add('bloqueado');
    }

    // Disable draggable on all cards
    document.querySelectorAll('.espacio-card.ocupado').forEach(card => {
      card.setAttribute('draggable', 'false');
    });
  }

  /**
   * Unblock the view (hide overlay, enable drag)
   */
  function desbloquearVista() {
    vistaBloqueada = false;

    // Hide overlay
    if (bloqueoOverlay) {
      bloqueoOverlay.classList.remove('active');
    }

    // Enable drag and drop
    if (espaciosContainer) {
      espaciosContainer.classList.remove('bloqueado');
    }

    // Re-enable draggable on occupied cards
    document.querySelectorAll('.espacio-card.ocupado').forEach(card => {
      card.setAttribute('draggable', 'true');
    });
  }

  /**
   * Check if view is blocked before allowing drag
   */
  function isVistaBloqueada() {
    return vistaBloqueada;
  }

  /**
   * Initialize drag and drop functionality
   */
  function initializeDragAndDrop() {
    const espaciosOcupados = document.querySelectorAll('.espacio-card.ocupado');
    const espaciosDisponibles = document.querySelectorAll('.espacio-card.disponible');

    // Make occupied spaces draggable
    espaciosOcupados.forEach(espacio => {
      const ordenContent = espacio.querySelector('.orden-content');
      if (ordenContent) {
        espacio.setAttribute('draggable', 'true');
        espacio.addEventListener('dragstart', handleDragStart);
        espacio.addEventListener('dragend', handleDragEnd);
      }
    });

    // Make all spaces drop targets
    document.querySelectorAll('.espacio-card').forEach(espacio => {
      espacio.addEventListener('dragover', handleDragOver);
      espacio.addEventListener('dragenter', handleDragEnter);
      espacio.addEventListener('dragleave', handleDragLeave);
      espacio.addEventListener('drop', handleDrop);
    });
  }

  let draggedElement = null;
  let sourceEspacio = null;

  /**
   * Handle drag start
   */
  function handleDragStart(e) {
    // Prevent drag if view is blocked
    if (isVistaBloqueada()) {
      e.preventDefault();
      return false;
    }

    draggedElement = this;
    sourceEspacio = this.dataset.espacio;

    // Add visual feedback
    this.classList.add('sortable-chosen');

    // Set drag data
    const ordenContent = this.querySelector('.orden-content');
    if (ordenContent) {
      e.dataTransfer.setData('text/plain', ordenContent.dataset.ordenId);
      e.dataTransfer.effectAllowed = 'move';
    }

    // Add dragging class after a small delay to prevent immediate visual change
    setTimeout(() => {
      this.classList.add('sortable-ghost');
    }, 0);
  }

  /**
   * Handle drag end
   */
  function handleDragEnd(e) {
    this.classList.remove('sortable-chosen', 'sortable-ghost');
    draggedElement = null;
    sourceEspacio = null;

    // Remove all drag-over states
    document.querySelectorAll('.espacio-card').forEach(card => {
      card.classList.remove('drag-over', 'drag-over-swap');
      card.style.borderColor = '';
    });
  }

  /**
   * Handle drag over
   */
  function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  }

  /**
   * Handle drag enter
   */
  function handleDragEnter(e) {
    e.preventDefault();
    const targetEspacio = this.dataset.espacio;

    // Only highlight if it's a different space
    if (targetEspacio !== sourceEspacio) {
      // Different style for swap vs move
      if (this.classList.contains('ocupado')) {
        // Swap style - orange/warning color
        this.style.borderColor = 'var(--bs-warning)';
        this.classList.add('drag-over-swap');
      } else {
        // Move style - primary color
        this.style.borderColor = 'var(--bs-primary)';
      }
      this.style.borderStyle = 'solid';
      this.classList.add('drag-over');
    }
  }

  /**
   * Handle drag leave
   */
  function handleDragLeave(e) {
    this.style.borderColor = '';
    this.style.borderStyle = '';
    this.classList.remove('drag-over', 'drag-over-swap');
  }

  /**
   * Handle drop
   */
  async function handleDrop(e) {
    e.preventDefault();

    this.style.borderColor = '';
    this.style.borderStyle = '';
    this.classList.remove('drag-over', 'drag-over-swap');

    const targetEspacio = this.dataset.espacio;
    const ordenId = e.dataTransfer.getData('text/plain');

    // Don't do anything if dropping on the same space
    if (targetEspacio === sourceEspacio) {
      return;
    }

    // Check if target space is occupied - if so, we'll swap
    const esIntercambio = this.classList.contains('ocupado');

    // Proceed with the move (or swap)
    await moverOrden(ordenId, targetEspacio, esIntercambio);
  }

  /**
   * Move order to new space via API (supports swap if target is occupied)
   */
  async function moverOrden(ordenId, nuevoEspacio, esIntercambio = false) {
    // Show loading indicator
    mostrarLoading(true);

    try {
      const response = await fetch(`${baseUrl}taller/actualizar-espacio`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          orden_id: ordenId,
          nuevo_espacio: nuevoEspacio
        })
      });

      const data = await response.json();

      // Hide loading
      mostrarLoading(false);

      if (data.success) {
        // Reiniciar temporizador después de movimiento exitoso
        iniciarTemporizador();

        const esSwap = data.tipo === 'swap';

        // Show success toast
        notyf.success(data.message);

        // Refresh the page to show updated state
        setTimeout(() => {
          refrescarEspacios();
        }, 500);
      } else {
        notyf.error(data.message || 'No se pudo mover la orden');
      }
    } catch (error) {
      mostrarLoading(false);
      console.error('Error moving order:', error);
      notyf.error('Ocurrió un error al mover la orden');
    }
  }

  /**
   * Show/hide loading overlay
   */
  function mostrarLoading(show) {
    let loadingOverlay = document.getElementById('loading-overlay');

    if (show) {
      if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'loading-overlay';
        loadingOverlay.innerHTML = `
          <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
        `;
        document.body.appendChild(loadingOverlay);
      }
      loadingOverlay.classList.add('active');
    } else if (loadingOverlay) {
      loadingOverlay.classList.remove('active');
    }
  }

  /**
   * Refresh spaces data
   */
  async function refrescarEspacios() {
    try {
      // Show loading on button
      if (btnRefrescar) {
        btnRefrescar.disabled = true;
        btnRefrescar.innerHTML = '<i class="icon-base ti tabler-loader spin"></i>';
      }

      const response = await fetch(`${baseUrl}taller/espacios-data`, {
        headers: {
          'Accept': 'application/json'
        }
      });

      const data = await response.json();

      if (data.espacios) {
        // Update stats
        document.getElementById('stat-ocupados').textContent = data.espacios_ocupados;
        document.getElementById('stat-disponibles').textContent = data.espacios_disponibles;

        // Update each space
        Object.keys(data.espacios).forEach(numero => {
          const espacio = data.espacios[numero];
          const espacioElement = document.getElementById(`espacio-${numero}`);

          if (espacioElement) {
            updateEspacioElement(espacioElement, espacio);
          }
        });

        // Re-initialize drag and drop
        initializeDragAndDrop();

        // Desbloquear y reiniciar temporizador
        desbloquearVista();
        iniciarTemporizador();
      }
    } catch (error) {
      console.error('Error refreshing spaces:', error);
      notyf.error('No se pudieron actualizar los espacios');
    } finally {
      // Restore button
      if (btnRefrescar) {
        btnRefrescar.disabled = false;
        btnRefrescar.innerHTML = '<i class="icon-base ti tabler-refresh"></i>';
      }
    }
  }

  /**
   * Update a single space element with new data
   */
  function updateEspacioElement(element, data) {
    const numero = data.numero;

    // Update classes
    element.classList.remove('ocupado', 'disponible');
    element.classList.add(data.ocupado ? 'ocupado' : 'disponible');

    // Update header
    const header = element.querySelector('.espacio-header');
    if (header) {
      header.classList.remove('disponible-header');
      if (!data.ocupado) {
        header.classList.add('disponible-header');
      }

      // Update badge - remove if not occupied, add if occupied
      let badge = header.querySelector('.badge');
      if (data.ocupado && data.orden) {
        if (!badge) {
          badge = document.createElement('span');
          header.appendChild(badge);
        }
        badge.className = 'badge bg-white text-primary';
        badge.style.cssText = 'font-size: 0.65rem; padding: 0.1rem 0.3rem;';
        badge.textContent = `#${data.orden.id}`;
      } else if (badge) {
        badge.remove();
      }
    }

    // Update body
    const body = element.querySelector('.espacio-body');
    if (body) {
      if (data.ocupado && data.orden) {
        body.innerHTML = `
          <div class="orden-content" data-orden-id="${data.orden.id}">
            <div class="d-flex align-items-center gap-1 mb-1">
              <span class="vehiculo-color-indicator" style="background-color: ${data.orden.vehiculo_color || '#cccccc'};"></span>
              <span class="placa-text">${data.orden.vehiculo_placa || 'N/A'}</span>
            </div>
            <p class="vehiculo-info">
              ${truncateText((data.orden.vehiculo_marca || '') + ' ' + (data.orden.vehiculo_modelo || ''), 18)}
            </p>
            <p class="cliente-text">
              ${truncateText(data.orden.cliente_nombre || 'N/A', 15)}
            </p>
            <div class="orden-actions">
              <span class="badge bg-label-warning etapa-badge">
                ${truncateText(data.orden.etapa_actual, 12)}
              </span>
              <a href="${baseUrl}ordenes-trabajo/${data.orden.id}/detalle"
                 class="btn btn-xs btn-text-primary btn-ver-detalle"
                 title="Ver detalle">
                <i class="icon-base ti tabler-external-link" style="font-size: 0.75rem;"></i>
              </a>
            </div>
          </div>
        `;
      } else {
        body.innerHTML = `
          <div class="espacio-vacio-content">
            <i class="icon-base ti tabler-car-off opacity-25"></i>
            <span style="font-size: 0.65rem;">Libre</span>
          </div>
        `;
      }
    }

    // Update draggable attribute
    if (data.ocupado) {
      element.setAttribute('draggable', 'true');
    } else {
      element.removeAttribute('draggable');
    }
  }

  /**
   * Truncate text helper
   */
  function truncateText(text, maxLength) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  }

  // Add CSS for spin animation, drag states, and loading overlay
  const style = document.createElement('style');
  style.textContent = `
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    .spin {
      animation: spin 1s linear infinite;
    }
    .drag-over {
      background-color: rgba(105, 108, 255, 0.1) !important;
    }
    .drag-over-swap {
      background-color: rgba(255, 171, 0, 0.15) !important;
    }
    .drag-over-swap::after {
      content: '⇄';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 1.5rem;
      color: var(--bs-warning);
      z-index: 10;
      background: rgba(255,255,255,0.9);
      border-radius: 50%;
      width: 2.5rem;
      height: 2.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    #loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.3);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.2s, visibility 0.2s;
    }
    #loading-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    #loading-overlay .loading-spinner {
      background: white;
      padding: 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.2);
    }
  `;
  document.head.appendChild(style);
});
