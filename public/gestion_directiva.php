<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>

<style>
  .directivo-toolbar {
    display: grid;
    grid-template-columns: 1fr 170px 180px 150px 190px 180px;
    gap: 12px;
  }

  .directivo-kpi {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #fff;
    padding: 18px;
  }

  .directivo-kpi .label {
    color: #64748b;
    font-size: .85rem;
  }

  .directivo-kpi .value {
    font-size: 1.6rem;
    font-weight: 700;
    color: #0f172a;
  }

  .semaforo-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    flex: 0 0 10px;
  }

  .semaforo-verde {
    background: #10b981;
  }

  .semaforo-amarillo {
    background: #f59e0b;
  }

  .semaforo-rojo {
    background: #ef4444;
  }

  .semaforo-gris {
    background: #94a3b8;
  }

  .priority-chip {
    border-radius: 999px;
    padding: 4px 10px;
    font-size: .78rem;
    font-weight: 700;
  }

  .priority-Critica {
    background: #fee2e2;
    color: #b91c1c;
  }

  .priority-Alta {
    background: #ffedd5;
    color: #c2410c;
  }

  .priority-Media {
    background: #dbeafe;
    color: #1d4ed8;
  }

  .priority-Baja {
    background: #dcfce7;
    color: #166534;
  }

  .gantt-mini {
    width: 180px;
    height: 10px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
  }

  .gantt-mini>span {
    display: block;
    height: 100%;
    background: #006ec7;
  }

  .directivo-actions {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    justify-content: flex-end;
    min-width: 96px;
  }

  .directivo-actions .btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .semaforo-stack {
    display: grid;
    gap: 6px;
    min-width: 105px;
  }

  .semaforo-item {
    display: grid;
    grid-template-columns: 14px 1fr;
    align-items: center;
    min-height: 18px;
    line-height: 1.2;
    white-space: nowrap;
  }

  @media (max-width: 992px) {
    .directivo-toolbar {
      grid-template-columns: 1fr;
    }
  }
</style>

<main class="main-content" id="mainContent">
  <div>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
              <i class="fas fa-briefcase text-primary fs-4"></i>
            </div>
            <div>
              <h1 class="h3 fw-bold mb-1">Gestión Directiva</h1>
              <p class="text-muted mb-0">Portafolio ejecutivo construido desde milestones de HK</p>
              <a href="http://192.168.1.105/reportes_direccion/reports/proyectos-milestones/index.php"
                class="btn btn-sm btn-outline-primary mt-2">
                <i class="fas fa-chart-line me-2"></i>
                Ir a reportes
              </a>
            </div>
          </div>
          <button class="btn btn-primary" id="btnActualizarDirectivo">
            <i class="fas fa-rotate me-2"></i>Actualizar
          </button>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="directivo-kpi">
          <div class="label">Proyectos en mira</div>
          <div class="value" id="kpiTotal">0</div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="directivo-kpi">
          <div class="label">Presupuesto aprobado</div>
          <div class="value" id="kpiPresupuesto">$0</div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="directivo-kpi">
          <div class="label">Gasto real</div>
          <div class="value" id="kpiGasto">$0</div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="directivo-kpi">
          <div class="label">Beneficio estimado</div>
          <div class="value" id="kpiBeneficio">$0</div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="directivo-toolbar">
          <input type="text" class="form-control" id="filtroDirectivo" placeholder="Buscar proyecto, milestone, estrategia o responsable">
          <select class="form-select" id="filtroPrioridadDirectiva">
            <option value="">Todas las prioridades</option>
            <option value="Critica">Crítica</option>
            <option value="Alta">Alta</option>
            <option value="Media">Media</option>
            <option value="Baja">Baja</option>
          </select>
          <select class="form-select" id="filtroEstadoDirectivo">
            <option value="">Todos los estados</option>
            <option value="En evaluacion">En evaluación</option>
            <option value="Aprobado">Aprobado</option>
            <option value="En ejecucion">En ejecución</option>
            <option value="Pausado">Pausado</option>
            <option value="Cerrado">Cerrado</option>
            <option value="Cancelado">Cancelado</option>
          </select>
          <select class="form-select" id="filtroZonaDirectiva">
            <option value="">Todas las zonas</option>
          </select>
          <select class="form-select" id="filtroAreaResponsable">
            <option value="">Todos los departamentos</option>
          </select>
          <select class="form-select" id="filtroTipoProyecto">
            <option value="">Todos los tipos</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">Proyecto</th>
                <th>Zona</th>
                <th>Área</th>
                <th>Clasificación</th>
                <th>Responsable</th>
                <th>Avance</th>
                <th>Entrega estimada</th>
                <th>Semáforos</th>
                <th class="text-end pe-4" style="min-width: 120px;">Acciones</th>
              </tr>
            </thead>
            <tbody id="tablaDirectivos">
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">Cargando proyectos directivos...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<div class="modal fade" id="modalDirectivo" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0" style="border-radius:16px">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-briefcase me-2"></i>Proyecto directivo</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="pdId">
        <input type="hidden" id="pdMilestoneId">

        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold">Nombre directivo</label>
            <input type="text" class="form-control" id="pdNombre">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Zona</label>
            <select class="form-select" id="pdZonaDirectivaId">
              <option value="">Seleccionar zona</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Área</label>
            <select class="form-select" id="pdAreaDirectivaId">
              <option value="">Seleccionar área</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Tipo de proyecto</label>
            <select class="form-select" id="pdTipoProyectoDirectivoId">
              <option value="">Seleccionar tipo</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Prioridad directiva</label>
            <select class="form-select" id="pdPrioridad">
              <option value="Critica">Crítica</option>
              <option value="Alta">Alta</option>
              <option value="Media">Media</option>
              <option value="Baja">Baja</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Estado directivo</label>
            <select class="form-select" id="pdEstado">
              <option value="En evaluacion">En evaluación</option>
              <option value="Aprobado">Aprobado</option>
              <option value="En ejecucion">En ejecución</option>
              <option value="Pausado">Pausado</option>
              <option value="Cerrado">Cerrado</option>
              <option value="Cancelado">Cancelado</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Inversión estimada</label>
            <input type="number" step="0.01" class="form-control" id="pdInversion">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Presupuesto aprobado</label>
            <input type="number" step="0.01" class="form-control" id="pdPresupuesto">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Gasto real</label>
            <input type="number" step="0.01" class="form-control" id="pdGasto">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Beneficio estimado</label>
            <input type="number" step="0.01" class="form-control" id="pdBeneficioEstimado">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Beneficio real</label>
            <input type="number" step="0.01" class="form-control" id="pdBeneficioReal">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Inicio directivo</label>
            <input type="date" class="form-control" id="pdFechaInicio">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Fecha objetivo</label>
            <input type="date" class="form-control" id="pdFechaFin">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="pdReporte" checked>
              <label class="form-check-label" for="pdReporte">Reportes Dirección</label>
            </div>
          </div>
          <div class="col-md-12">
            <label class="form-label fw-semibold">Motivo si cambia la prioridad</label>
            <input type="text" class="form-control" id="pdMotivoPrioridad">
          </div>
          <div class="col-md-12">
            <label class="form-label fw-semibold">Notas directivas</label>
            <textarea class="form-control" rows="3" id="pdNotas"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" id="btnGuardarDirectivo">
          <i class="fas fa-save me-2"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script type="application/json" id="gestionDirectivaInlineObsoleto">
  const paramsDirectivos = new URLSearchParams(window.location.search);
  let directivos = [];
  let modalDirectivo;

  function moneda(v) {
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN'
    }).format(Number(v || 0));
  }

  function esc(v) {
    const div = document.createElement('div');
    div.textContent = v || '';
    return div.innerHTML;
  }

  function pct(v) {
    const n = Math.max(0, Math.min(100, Number(v || 0)));
    return n.toFixed(0);
  }

  function renderKpis(rows) {
    const presupuesto = rows.reduce((s, r) => s + Number(r.presupuesto_aprobado || 0), 0);
    const gasto = rows.reduce((s, r) => s + Number(r.gasto_real || 0), 0);
    const beneficio = rows.reduce((s, r) => s + Number(r.beneficio_estimado || 0), 0);
    $('#kpiTotal').text(rows.length);
    $('#kpiPresupuesto').text(moneda(presupuesto));
    $('#kpiGasto').text(moneda(gasto));
    $('#kpiBeneficio').text(moneda(beneficio));
  }

  function renderTabla(rows) {
    renderKpis(rows);
    const tbody = $('#tablaDirectivos');

    if (!rows.length) {
      tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted">No hay proyectos directivos con estos filtros</td></tr>');
      return;
    }

    tbody.html(rows.map(r => `
      <tr>
        <td class="ps-4">
          <div class="fw-bold">${esc(r.nombre_directivo)}</div>
          <div class="small text-muted">${esc(r.milestone)} · ${esc(r.estrategia)}</div>
        </td>
        <td>
          <div><span class="priority-chip priority-${esc(r.prioridad_directiva)}">${esc(r.prioridad_directiva)}</span></div>
          <div class="small text-muted mt-1">${esc(r.estado_directivo)}${r.zona ? ' · ' + esc(r.zona) : ''}</div>
          <div class="small text-muted">${esc(r.tipo_proyecto)}</div>
        </td>
        <td>${esc(r.responsable || 'Sin asignar')}</td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="gantt-mini"><span style="width:${pct(r.avance_real)}%"></span></div>
            <span class="small fw-bold">${pct(r.avance_real)}%</span>
          </div>
          <div class="small text-muted">${r.tareas_finalizadas || 0}/${r.total_tareas || 0} tareas</div>
        </td>
        <td>
          <div class="small">Presupuesto: <strong>${moneda(r.presupuesto_aprobado)}</strong></div>
          <div class="small">Gasto: <strong>${moneda(r.gasto_real)}</strong></div>
          <div class="small">ROI est.: <strong>${Number(r.roi_estimado || 0).toFixed(1)}%</strong></div>
        </td>
        <td>
          <div class="small"><span class="semaforo-dot semaforo-${esc(r.semaforo_tiempo || 'gris')} me-1"></span>Tiempo</div>
          <div class="small"><span class="semaforo-dot semaforo-${esc(r.semaforo_presupuesto || 'gris')} me-1"></span>Presupuesto</div>
        </td>
        <td class="text-end pe-4">
          <button class="btn btn-sm btn-outline-primary btnEditarDirectivo" data-id="${r.proyecto_directivo_id}" title="Editar">
            <i class="fas fa-pen"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger btnOcultarDirectivo" data-id="${r.proyecto_directivo_id}" title="Ocultar">
            <i class="fas fa-eye-slash"></i>
          </button>
        </td>
      </tr>
    `).join(''));
  }

  function cargarDirectivos() {
    $.get('/hoshin_kanri/app/proyectos_directivos/listar.php', {
      q: $('#filtroDirectivo').val(),
      prioridad: $('#filtroPrioridadDirectiva').val(),
      estado: $('#filtroEstadoDirectivo').val(),
      zona: $('#filtroZonaDirectiva').val()
    }, function(resp) {
      if (!resp.success) {
        Swal.fire('Error', resp.message || 'No se pudo cargar el portafolio', 'error');
        return;
      }
      directivos = resp.data || [];
      renderTabla(directivos);
    }, 'json');
  }

  function llenarModal(r) {
    $('#pdId').val(r.proyecto_directivo_id || '');
    $('#pdMilestoneId').val(r.milestone_id || '');
    $('#pdNombre').val(r.nombre_directivo || r.milestone || '');
    $('#pdZona').val(r.zona || '');
    $('#pdTipo').val(r.tipo_proyecto || '');
    $('#pdPrioridad').val(r.prioridad_directiva || 'Media');
    $('#pdEstado').val(r.estado_directivo || 'En evaluacion');
    $('#pdInversion').val(r.inversion_estimada || 0);
    $('#pdPresupuesto').val(r.presupuesto_aprobado || 0);
    $('#pdGasto').val(r.gasto_real || 0);
    $('#pdBeneficioEstimado').val(r.beneficio_estimado || 0);
    $('#pdBeneficioReal').val(r.beneficio_real || 0);
    $('#pdFechaInicio').val(r.fecha_inicio_directiva || r.fecha_inicio_operativa || '');
    $('#pdFechaFin').val(r.fecha_fin_objetivo || r.fecha_fin_operativa || '');
    $('#pdReporte').prop('checked', Number(r.requiere_reporte_direccion || 1) === 1);
    $('#pdMotivoPrioridad').val('');
    $('#pdNotas').val(r.notas_directivas || '');
    modalDirectivo.show();
  }

  function promoverDesdeQuery() {
    const milestoneId = Number(paramsDirectivos.get('milestone_id') || 0);
    if (!milestoneId) return;

    $.post('/hoshin_kanri/app/proyectos_directivos/promover.php', {
      milestone_id: milestoneId
    }, function(resp) {
      if (!resp.success) {
        Swal.fire('Error', resp.message || 'No se pudo enviar a Dirección', 'error');
        return;
      }
      Swal.fire('Listo', resp.message, 'success');
      cargarDirectivos();
      window.history.replaceState({}, document.title, 'gestion_directiva.php');
    }, 'json');
  }

  $(document).ready(function() {
    modalDirectivo = new bootstrap.Modal(document.getElementById('modalDirectivo'));
    cargarDirectivos();
    promoverDesdeQuery();

    let timer = null;
    $('#filtroDirectivo,#filtroZonaDirectiva').on('input', function() {
      clearTimeout(timer);
      timer = setTimeout(cargarDirectivos, 250);
    });
    $('#filtroPrioridadDirectiva,#filtroEstadoDirectivo,#btnActualizarDirectivo').on('change click', cargarDirectivos);

    $('#tablaDirectivos').on('click', '.btnEditarDirectivo', function() {
      const id = Number($(this).data('id'));
      const row = directivos.find(r => Number(r.proyecto_directivo_id) === id);
      if (row) llenarModal(row);
    });

    $('#tablaDirectivos').on('click', '.btnOcultarDirectivo', function() {
      const id = Number($(this).data('id'));
      Swal.fire({
        title: '¿Ocultar de Dirección?',
        text: 'El milestone seguirá existiendo en HK operativo.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, ocultar',
        cancelButtonText: 'Cancelar'
      }).then(result => {
        if (!result.isConfirmed) return;
        $.post('/hoshin_kanri/app/proyectos_directivos/ocultar.php', {
          proyecto_directivo_id: id
        }, function(resp) {
          if (resp.success) cargarDirectivos();
          else Swal.fire('Error', resp.message || 'No se pudo ocultar', 'error');
        }, 'json');
      });
    });

    $('#btnGuardarDirectivo').on('click', function() {
      $.post('/hoshin_kanri/app/proyectos_directivos/guardar.php', {
        proyecto_directivo_id: $('#pdId').val(),
        milestone_id: $('#pdMilestoneId').val(),
        nombre_directivo: $('#pdNombre').val(),
        zona: $('#pdZona').val(),
        tipo_proyecto: $('#pdTipo').val(),
        prioridad_directiva: $('#pdPrioridad').val(),
        estado_directivo: $('#pdEstado').val(),
        requiere_reporte_direccion: $('#pdReporte').is(':checked') ? 1 : 0,
        inversion_estimada: $('#pdInversion').val(),
        presupuesto_aprobado: $('#pdPresupuesto').val(),
        gasto_real: $('#pdGasto').val(),
        beneficio_estimado: $('#pdBeneficioEstimado').val(),
        beneficio_real: $('#pdBeneficioReal').val(),
        fecha_inicio_directiva: $('#pdFechaInicio').val(),
        fecha_fin_objetivo: $('#pdFechaFin').val(),
        notas_directivas: $('#pdNotas').val(),
        motivo_prioridad: $('#pdMotivoPrioridad').val()
      }, function(resp) {
        if (!resp.success) {
          Swal.fire('Error', resp.message || 'No se pudo guardar', 'error');
          return;
        }
        modalDirectivo.hide();
        Swal.fire('Guardado', resp.message, 'success');
        cargarDirectivos();
      }, 'json');
    });
  });
</script>

<?php require_once '../app/layout/footer.php'; ?>
