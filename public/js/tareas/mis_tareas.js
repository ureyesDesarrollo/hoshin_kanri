/* 
1 = Abierta
2 = En progreso
3 = En revisión
4 = Aprobada
5 = Rechazada
6 = Completada fuera de tiempo
*/
let currentPageTareas = 1;

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text ?? '';
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

function badgeSemaforo(row) {
  const estatus = parseInt(row.estatus || 1);
  const completada = parseInt(row.completada || 0);

  if (estatus === 4 || completada === 1)
    return `<span class="hk-badge hk-badge-muted"><i class="fas fa-check"></i> Aprobada</span>`;

  if (estatus === 3)
    return `<span class="hk-badge hk-badge-warning"><i class="fas fa-hourglass-half"></i> En revisión</span>`;

  if (estatus === 5)
    return `<span class="hk-badge hk-badge-danger"><i class="fas fa-times-circle"></i> Rechazada</span>`;

  if (row.semaforo === 'ROJO')
    return `<span class="hk-badge hk-badge-danger"><i class="fas fa-exclamation-circle"></i> Vencida</span>`;

  if (row.semaforo === 'HOY')
    return `<span class="hk-badge hk-badge-warning"><i class="fas fa-clock"></i> Vence hoy</span>`;

  return `<span class="hk-badge hk-badge-success"><i class="fas fa-check-circle"></i> En tiempo</span>`;
}

function renderCardTarea(t) {
  const estatus = parseInt(t.estatus || 1);
  const isApproved = estatus === 4 || parseInt(t.completada || 0) === 1;
  const isRevision = estatus === 3;
  const isRejected = estatus === 5;
  const canSendReview = !isApproved && (estatus === 1 || estatus === 2 || estatus === 5);

  const fechaFin = new Date(t.fecha_fin);
  const hoy = new Date();
  const diasRestantes = Math.ceil((fechaFin - hoy) / (1000 * 60 * 60 * 24));

  // No marcar urgente/vencida si ya está en revisión o aprobada
  const isUrgent = diasRestantes <= 2 && !isApproved && !isRevision;
  const isOverdue = diasRestantes < 0 && !isApproved && !isRevision;

  return `
  <div class="col-lg-4 col-md-6 mb-4">
    <div class="task-card
      ${isApproved ? 'task-card--completed' : ''}
      ${isRevision ? 'task-card--revision' : ''}
      ${isRejected ? 'task-card--rejected' : ''}
      ${isUrgent ? 'task-card--urgent' : ''}
      ${isOverdue ? 'task-card--overdue' : ''}">
      
      <!-- Cabecera con estado y acciones -->
      <div class="task-card__header">
        <div class="task-card__status-indicator ${isApproved ? 'task-card__status-indicator--completed' : ''}">
          <i class="fas ${isApproved ? 'fa-check-circle' : 'fa-circle'}"></i>
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
              <span class="task-detail__value">${escapeHtml(t.milestone)} (${escapeHtml(t.responsable)})</span>
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
              ${(!isApproved && !isRevision) ? `
                <span class="deadline-counter ${isOverdue ? 'badge bg-danger' : isUrgent ? 'badge bg-warning' : 'badge bg-info'}">
                  ${isOverdue
        ? `<i class="fas fa-exclamation-triangle me-1"></i>Vencida`
        : `${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`
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
          
          ${isApproved ? `
            <div class="task-completed-badge">
              <i class="fas fa-check-circle text-success me-1"></i>
              <span>Aprobada</span>
            </div>
          ` : isRevision ? `
            <div class="task-completed-badge">
              <i class="fas fa-hourglass-half text-secondary me-1"></i>
              <span>En revisión</span>
            </div>
          ` : canSendReview ? `
            <button class="btn-action btn-action--complete btnCompletar" data-id="${t.tarea_id}" aria-label="Enviar a revisión">
              <i class="fas fa-paper-plane"></i>
              <span class="btn-action__label">${isRejected ? 'Reenviar' : 'Enviar'}</span>
            </button>
          ` : `
            <div class="task-completed-badge">
              <i class="fas fa-info-circle text-muted me-1"></i>
              <span>Sin acción</span>
            </div>
          `}
        </div>
      </div>
    </div>
  </div>`;
}

function renderPaginationMisTareas(p) {
  const ul = $('#paginacionMisTareas');
  ul.empty();

  if (!p || p.total_pages <= 1) return;

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
        <a class="page-link border-0 ${i === p.page ? 'bg-primary text-white' : ''}" href="#" data-page="${i}">
          ${i}
        </a>
      </li>
    `);
  }

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
  $container.addClass('fade-out');

  setTimeout(() => {
    $('#estadoCargando').removeClass('d-none');
    $('#estadoVacio').addClass('d-none');
    $container.empty().removeClass('fade-out');

    const o = $('#selOrden').val();
    const q = $('#txtBuscar').val().trim();
    const f = $('#selFiltro').val();

    $.get('/hoshin_kanri/app/tareas/mis_tareas_lista.php', { page, limit: 12, o, q, f }, function (resp) {

      $('#estadoCargando').addClass('d-none');

      if (!resp.success || !resp.data || resp.data.length === 0) {
        $('#estadoVacio').removeClass('d-none');
        $('#showMisTareasCount').text(0);
        $('#totalMisTareasCount').text(resp.pagination?.total || 0);
        $('#paginacionMisTareas').html('');
        return;
      }

      // KPIs (si vienen)
      if (resp.kpi) {
        $('#kpiTotal').text(resp.kpi.total ?? 0);
        $('#kpiFin').text(resp.kpi.finalizadas ?? 0);
        $('#kpiPend').text(resp.kpi.pendientes ?? 0);
        $('#kpiVen').text(resp.kpi.vencidas ?? 0);
      }

      resp.data.forEach((t) => {
        const $card = $(renderCardTarea(t)).addClass('fade-in');
        $container.append($card);
      });

      $('#showMisTareasCount').text(resp.data.length);
      $('#totalMisTareasCount').text(resp.pagination?.total ?? resp.data.length);

      renderPaginationMisTareas(resp.pagination);

    }, 'json').fail(function () {
      $('#estadoCargando').addClass('d-none');
      $('#estadoVacio').removeClass('d-none');
      $('#paginacionMisTareas').html('');
    });

  }, 200);
}

function completarTarea(tareaId) {
  Swal.fire({
    title: 'Enviar a revisión',
    text: 'Se notificará al aprobador correspondiente (milestone o estrategia).',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, enviar',
    cancelButtonText: 'Cancelar',
    showLoaderOnConfirm: true,
    preConfirm: () => {
      return $.post('/hoshin_kanri/app/tareas/marcar_completada.php', { tarea_id: tareaId });
    }
  }).then((result) => {
    if (!result.isConfirmed) return;

    const resp = result.value;
    if (!resp || !resp.success) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: (resp && resp.message) ? resp.message : 'No se pudo enviar a revisión',
      });
      return;
    }

    Swal.fire({
      icon: 'success',
      title: 'Enviada',
      text: 'La tarea fue enviada a revisión.'
    });

    loadMisTareas(currentPageTareas);
  });
}

$(document).on('click', '#btnRefrescar', function () {
  loadMisTareas(currentPageTareas);
});

let searchTimer = null;

$(document).on('input', '#txtBuscar', function () {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadMisTareas(1), 350);
});

$(document).on('change', '#selFiltro,#selOrden', function () {
  loadMisTareas(1);
});

$(document).on('click', '#paginacionMisTareas .page-link', function (e) {
  e.preventDefault();
  const page = parseInt($(this).data('page'), 10);
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

$(document).on('click', '.btnVerTarea', function () {
  const id = $(this).data('id');
  window.location.href = '/hoshin_kanri/public/detalle.php?tarea_id=' + id;
});

// auto filtro por query ?f=vencidas (y respeta el select)
$(document).ready(function () {
  const url = new URL(window.location.href);
  const f = url.searchParams.get('f');
  if (f) $('#selFiltro').val(f);

  loadMisTareas(1);
});
