let currentPageTareas = 1;

function badgeSemaforo(row) {
  if (parseInt(row.completada) === 1) return `<span class="hk-badge hk-badge-muted"><i class="fas fa-check"></i> Finalizada</span>`;
  if (row.semaforo === 'ROJO') return `<span class="hk-badge hk-badge-danger"><i class="fas fa-exclamation-circle"></i> Vencida</span>`;
  return `<span class="hk-badge hk-badge-success"><i class="fas fa-check-circle"></i> En tiempo</span>`;
}

function renderCardTarea(t) {
  const isCompleted = parseInt(t.completada) === 1;
  const fechaFin = new Date(t.fecha_fin);
  const hoy = new Date();
  const diasRestantes = Math.ceil((fechaFin - hoy) / (1000 * 60 * 60 * 24));
  const isUrgent = diasRestantes <= 2 && !isCompleted;
  const isOverdue = diasRestantes < 0 && !isCompleted;

  return `
  <div class="col-lg-4 col-md-6 mb-4">
    <div class="task-card ${isCompleted ? 'task-card--completed' : ''} ${isUrgent ? 'task-card--urgent' : ''} ${isOverdue ? 'task-card--overdue' : ''}">
      <!-- Cabecera con estado y acciones -->
      <div class="task-card__header">
        <div class="task-card__status-indicator ${isCompleted ? 'task-card__status-indicator--completed' : ''}">
          <i class="fas ${isCompleted ? 'fa-check-circle' : 'fa-circle'}"></i>
        </div>
        <div class="task-card__priority">
          ${badgeSemaforo(t)}
        </div>
      </div>
      
      <!-- Contenido principal -->
      <div class="task-card__content">
        <h3 class="task-card__title">
          <i class="fas fa-tasks me-2"></i>
          ${escapeHtml(t.tarea)}
        </h3>
        
        <div class="task-card__details">
          <div class="task-detail">
            <div class="task-detail__icon">
              <i class="fas fa-bullseye"></i>
            </div>
            <div class="task-detail__content">
              <span class="task-detail__label">Objetivo</span>
              <span class="task-detail__value">${escapeHtml(t.objetivo)}</span>
            </div>
          </div>
          
          <div class="task-detail">
            <div class="task-detail__icon">
              <i class="fas fa-chess-knight"></i>
            </div>
            <div class="task-detail__content">
              <span class="task-detail__label">Estrategia</span>
              <span class="task-detail__value">${escapeHtml(t.estrategia)}</span>
            </div>
          </div>
          
          <div class="task-detail">
            <div class="task-detail__icon">
              <i class="fas fa-flag-checkered"></i>
            </div>
            <div class="task-detail__content">
              <span class="task-detail__label">Milestone</span>
              <span class="task-detail__value">${escapeHtml(t.milestone)}</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Pie con fecha y acciones -->
      <div class="task-card__footer">
        <div class="task-card__deadline">
          <div class="deadline-info">
            <i class="fas fa-calendar-alt ${isOverdue ? 'text-danger' : isUrgent ? 'text-warning' : 'text-primary'}"></i>
            <div class="deadline-details">
              <span class="deadline-label">Vence el</span>
              <span class="deadline-date ${isOverdue ? 'text-danger' : isUrgent ? 'text-warning' : ''}">
                ${formatDate(t.fecha_fin)}
              </span>
              ${!isCompleted ? `
                <span class="deadline-counter ${isOverdue ? 'badge bg-danger' : isUrgent ? 'badge bg-warning' : 'badge bg-info'}">
                  ${isOverdue ?
        `<i class="fas fa-exclamation-triangle me-1"></i>Vencida` :
        `${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`
      }
                </span>
              ` : ''}
            </div>
          </div>
        </div>
        
        <div class="task-card__actions">
          <button class="btn-action btn-action--view btnVerTarea" data-id="${t.tarea_id}" aria-label="Ver detalles">
            <i class="fas fa-eye"></i>
            <span class="btn-action__label">Detalles</span>
          </button>
          
          ${!isCompleted ? `
            <button class="btn-action btn-action--complete btnCompletar" data-id="${t.tarea_id}" aria-label="Marcar como completada">
              <i class="fas fa-check-circle"></i>
              <span class="btn-action__label">Completar</span>
            </button>
          ` : `
            <div class="task-completed-badge">
              <i class="fas fa-check-circle text-success me-1"></i>
              <span>Completada</span>
            </div>
          `}
        </div>
      </div>
    </div>
  </div>`;
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
function formatDate(dateStr) {
  if (!dateStr) return '—';

  // Forzar fecha local sin UTC
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m - 1, d);

  return date.toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

function renderPaginationMisTareas(p) {
  const ul = $('#paginacionMisTareas');
  ul.empty();

  if (p.total_pages <= 1) return;

  // Prev
  ul.append(`
            <li class="page-item ${p.page === 1 ? 'disabled' : ''}">
                <a class="page-link border-0" href="#" data-page="${p.page - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `);

  for (let i = 1; i <= p.total_pages; i++) {
    ul.append(`
                <li class="page-item ${i === p.page ? 'active' : ''}">
                    <a class="page-link border-0 ${i === p.page ? 'bg-primary text-white' : ''}"
                       href="#" data-page="${i}">
                        ${i}
                    </a>
                </li>
            `);
  }

  // Next
  ul.append(`
            <li class="page-item ${p.page === p.total_pages ? 'disabled' : ''}">
                <a class="page-link border-0" href="#" data-page="${p.page + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `);
}

function loadMisTareas(page = 1) {
  currentPageTareas = page;

  const $container = $('#contenedorTareas');

  // animación de salida
  $container.addClass('fade-out');

  setTimeout(() => {
    $('#estadoCargando').removeClass('d-none');
    $('#estadoVacio').addClass('d-none');
    $container.empty().removeClass('fade-out');

    const o = $('#selOrden').val();
    const q = $('#txtBuscar').val().trim();

    $.get('/hoshin_kanri/app/tareas/mis_tareas_lista.php', {
      page, limit: 12, o, q
    }, function (resp) {

      $('#estadoCargando').addClass('d-none');

      if (!resp.success || !resp.data || resp.data.length === 0) {
        $('#estadoVacio').removeClass('d-none');
        $('#showMisTareasCount').text(0);
        $('#totalMisTareasCount').text(resp.pagination?.total || 0);
        $('#paginacionMisTareas').html('');
        return;
      }

      // KPIs
      $('#kpiTotal').text(resp.kpi.total);
      $('#kpiFin').text(resp.kpi.finalizadas);
      $('#kpiPend').text(resp.kpi.pendientes);
      $('#kpiVen').text(resp.kpi.vencidas);

      // cards con animación
      resp.data.forEach((t, i) => {
        const $card = $(renderCardTarea(t)).addClass('fade-in');
        $container.append($card);
      });

      $('#showMisTareasCount').text(resp.data.length);
      $('#totalMisTareasCount').text(resp.pagination.total);

      renderPaginationMisTareas(resp.pagination);

    }, 'json');

  }, 200); // tiempo corto para salida suave
}


function completarTarea(tareaId) {
  Swal.fire({
    title: '¿Estás seguro?',
    text: '¿Deseas marcar esta tarea como completada?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, marcar como completada',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post('/hoshin_kanri/app/tareas/marcar_completada.php', { tarea_id: tareaId }, function (resp) {
        if (!resp.success) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: resp.message || 'Error al marcar la tarea como completada',
          });
          return;
        }
        loadMisTareas(currentPageTareas);
      }, 'json');
    }
  });
}

$(document).on('click', '#btnRefrescar', function () {
  loadMisTareas(currentPageTareas);
});

let searchTimer = null;

$(document).on('input', '#txtBuscar', function () {
  const value = $(this).val();

  clearTimeout(searchTimer);

  searchTimer = setTimeout(() => {
    loadMisTareas(1);
  }, 350); // 300–400ms es ideal
});


$(document).on('change', '#selFiltro,#selOrden', function () {
  loadMisTareas(1);
});

$(document).on('click', '#paginacionMisTareas .page-link', function (e) {
  e.preventDefault();

  const page = parseInt($(this).data('page'), 10);

  // evita clicks en prev/next inválidos
  if (!page || page < 1) return;

  loadMisTareas(page);
});


$(document).on('click', '.btnCompletar', function () {
  completarTarea($(this).data('id'));
});

$(document).on('change', '.chkCompletar', function () {
  const id = $(this).data('id');
  if (this.checked) completarTarea(id);
});

// Detalle (ajusta a tu ruta)
$(document).on('click', '.btnVerTarea', function () {
  const id = $(this).data('id');
  window.location.href = '/hoshin_kanri/public/detalle.php?tarea_id=' + id;
});

// auto filtro por query ?f=vencidas
$(document).ready(function () {
  const url = new URL(window.location.href);
  const f = url.searchParams.get('f');
  if (f) $('#selFiltro').val(f);

  loadMisTareas(1);
});
