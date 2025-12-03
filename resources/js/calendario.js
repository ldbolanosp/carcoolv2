import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import esLocale from '@fullcalendar/core/locales/es';
import $ from 'jquery';

document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  const filterClient = document.getElementById('filter-client');
  const modalOrden = document.getElementById('modalOrdenTrabajo');
  let bsModal = null;

  if (modalOrden) {
     // eslint-disable-next-line no-undef
     bsModal = new bootstrap.Modal(modalOrden);
  }

  // Initialize Select2
  if (filterClient) {
    $(filterClient).select2({
      placeholder: 'Todos los clientes',
      allowClear: true
    });

    // Select2 change event
    $(filterClient).on('change', function () {
      if (calendarEl && calendar) {
        calendar.refetchEvents();
      }
    });
  }

  let calendar = null;

  if (calendarEl) {
    calendar = new Calendar(calendarEl, {
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timeGridPlugin],
      initialView: 'dayGridMonth',
      locale: esLocale,
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listMonth'
      },
      displayEventTime: false,
      events: {
        url: '/calendario/events',
        extraParams: function () {
          return {
            cliente_id: filterClient ? $(filterClient).val() : ''
          };
        }
      },
      eventClick: function (info) {
        info.jsEvent.preventDefault(); // Don't navigate
        
        const ordenId = info.event.id;
        
        // Fetch Details
        fetch(`/ordenes-trabajo/${ordenId}/modal-data`)
            .then(response => response.json())
            .then(data => {
                if(data.orden) {
                    populateModal(data.orden);
                    if(bsModal) bsModal.show();
                }
            })
            .catch(error => {
                console.error('Error fetching order details:', error);
            });
      },
      eventDidMount: function (info) {
        // Add tooltips or custom rendering if needed
        if (info.event.extendedProps.vehiculo) {
           info.el.setAttribute('title', info.event.extendedProps.vehiculo);
        }
      }
    });

    calendar.render();
  }

  function populateModal(orden) {
      document.getElementById('modal-orden-id').textContent = orden.id;
      document.getElementById('modal-cliente-nombre').textContent = orden.cliente ? orden.cliente.nombre : 'N/A';
      document.getElementById('modal-cliente-telefono').textContent = orden.cliente ? (orden.cliente.telefono || 'No registrado') : 'N/A';
      
      document.getElementById('modal-vehiculo-placa').textContent = orden.vehiculo ? orden.vehiculo.placa : 'N/A';
      let vehiculoInfo = 'N/A';
      if(orden.vehiculo) {
          vehiculoInfo = `${orden.vehiculo.marca ? orden.vehiculo.marca.nombre : ''} ${orden.vehiculo.modelo ? orden.vehiculo.modelo.nombre : ''}`;
      }
      document.getElementById('modal-vehiculo-info').textContent = vehiculoInfo;
      document.getElementById('modal-vehiculo-km').textContent = orden.km_actual ? orden.km_actual : 'No registrado';

      document.getElementById('modal-etapa').textContent = orden.etapa_actual;
      document.getElementById('modal-tipo').textContent = orden.tipo_orden;
      
      // Format date if needed, assuming ISO string comes or standard Laravel format
      const dateObj = new Date(orden.created_at);
      document.getElementById('modal-fecha-ingreso').textContent = dateObj.toLocaleDateString() + ' ' + dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
      
      document.getElementById('modal-motivo').textContent = orden.motivo_ingreso;

      // Financial Summary Logic
      const cotizacionesAprobadas = orden.cotizaciones ? orden.cotizaciones.filter(c => c.aprobada) : [];
      const facturas = orden.facturas || [];
      
      let totalCotizado = 0;
      cotizacionesAprobadas.forEach(c => totalCotizado += parseFloat(c.total));
      
      let totalFacturado = 0;
      facturas.forEach(f => totalFacturado += parseFloat(f.total));

      if(totalCotizado > 0 || totalFacturado > 0) {
          document.getElementById('modal-resumen-financiero').style.display = 'block';
          // Simple currency formatting
          const formatter = new Intl.NumberFormat('es-CR', { style: 'currency', currency: 'CRC' });
          document.getElementById('modal-total-cotizado').textContent = formatter.format(totalCotizado);
          document.getElementById('modal-total-facturado').textContent = formatter.format(totalFacturado);
      } else {
          document.getElementById('modal-resumen-financiero').style.display = 'none';
      }

      // Update Link
      document.getElementById('btn-ver-detalle').href = `/ordenes-trabajo/${orden.id}/detalle`;
  }
});
