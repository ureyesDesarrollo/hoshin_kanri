<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>

<main class="main-content" id="mainContent">
  <!-- Progress Steps -->
  <div class="row">
    <div class="col-6">
      <div class="d-flex align-items-center mb-4">
        <div class="d-flex align-items-center text-primary">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
            style="width: 40px; height: 40px;">
            <i class="fas fa-home"></i>
          </div>
          <span class="ms-2 fw-medium">Dashboard</span>
        </div>

        <div class="flex-grow-1 mx-3">
          <div class="progress" style="height: 3px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
          </div>
        </div>

        <div class="d-flex align-items-center">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
            style="width: 40px; height: 40px;">
            <i class="fa-solid fa-people-group"></i>
          </div>
          <span class="ms-2 fw-bold">Mi equipo</span>
        </div>
      </div>

    </div>
  </div>
  <!-- Título Principal -->
  <div class="row mb-4">
    <div class="col-md-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center flex-grow-1">
              <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                <i class="fa-solid fa-people-group text-primary fs-4"></i>
              </div>
              <div>
                <h1 class="h3 fw-bold mb-1">Mi equipo</h1>
                <p class="text-muted mb-0">Monitorea el desempeño de tu equipo</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center justify-content-center">
          <div class="hk-ring-wrap">
            <div class="hk-ring" id="hkRing">
              <div class="hk-ring-inner">
                <div class="hk-ring-p" id="hkRingP">0%</div>
                <div class="hk-ring-t">Nivel de compromiso</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row" id="responsablesContainer"></div>

  <div class="modal fade" id="modalDetalleResponsable" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">

        <!-- HEADER -->
        <div class="modal-header border-0 px-4 py-3">
          <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
              <div class="rounded-circle bg-white border border-3 border-primary d-flex align-items-center justify-content-center"
                style="width:56px;height:56px;">
                <span class="fw-bold text-primary" id="detalleIniciales">--</span>
              </div>
            </div>

            <div>
              <div class="d-flex align-items-center gap-2">
                <h5 class="modal-title fw-bold mb-0" id="detalleNombre"></h5>
                <span class="badge rounded-pill" id="detalleBadgeEstado">--</span>
              </div>
              <div class="text-muted small">
                <i class="fas fa-briefcase me-2"></i><span id="detalleRol"></span>
              </div>
            </div>
          </div>

          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body px-4 pt-3 pb-4">

          <!-- RESUMEN -->
          <div class="row g-3 align-items-stretch mb-3">
            <div class="col-md-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="text-muted small mb-1">Nivel de compromiso</div>
                      <div class="fw-bold fs-3" id="detallePorcentaje">0%</div>
                    </div>
                    <div class="text-muted"><i class="fas fa-chart-line fa-lg"></i></div>
                  </div>

                  <div class="mt-3">
                    <div class="progress" style="height: 10px;">
                      <div class="progress-bar" id="detalleProgress" role="progressbar" style="width:0%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 small text-muted">
                      <span><span id="detalleFinalizadas">0</span> finalizadas</span>
                      <span><span id="detalleTotal">0</span> total</span>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <div class="col-md-8">
              <div class="row g-3 h-100">
                <div class="col-sm-4">
                  <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                      <div class="text-muted small mb-1">Finalizadas</div>
                      <div class="fw-bold fs-3 text-success" id="detalleFinalizadas2">0</div>
                      <div class="small text-muted"><i class="fas fa-check-circle me-1"></i>Completadas</div>
                    </div>
                  </div>
                </div>

                <div class="col-sm-4">
                  <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                      <div class="text-muted small mb-1">Vencidas</div>
                      <div class="fw-bold fs-3 text-danger" id="detalleVencidas">0</div>
                      <div class="small text-muted">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <span id="detalleVencidasMeta">0 abiertas · 0 tarde</span>
                      </div>
                    </div>
                  </div>
                </div>


                <div class="col-sm-4">
                  <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                      <div class="text-muted small mb-1">Vencidas</div>
                      <div class="fw-bold fs-3 text-danger" id="detalleVencidasFueraTiempo">0</div>
                      <div class="small text-muted"><i class="fas fa-exclamation-triangle me-1"></i>Fuera de tiempo</div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- CONTROLES -->
          <div class="d-flex flex-wrap gap-2 align-items-center mb-3">

            <div class="ms-auto small text-muted" id="detalleMeta"></div>
          </div>

          <!-- CONTENIDO -->
          <div class="accordion" id="accordionDetalle"></div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer border-0 px-4 pb-4 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>

      </div>
    </div>
  </div>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../public/js/utils/utils.js"></script>
<script>
  $(document).ready(function() {
    loadResponsablesCards();

    $(document).on('click', '.btnVerResponsable', function() {
      const usuarioId = $(this).data('id');
      console.log(usuarioId);
      $('#modalDetalleResponsable').modal('show');
      loadDetalleResponsable(usuarioId);
    });
  });

  function safeNumber(value) {
    return value === null || value === undefined ? 0 : value;
  }

  function getSemaforo(porcentaje, totalTareas) {
    if (totalTareas === 0) return 'warning';
    if (porcentaje >= 90) return 'success';
    if (porcentaje >= 80 && porcentaje < 90) return 'warning';
    if (porcentaje < 80) return 'danger';
    return 'danger';
  }

  function getBadgeInfo(porcentaje, totalTareas) {
    if (totalTareas === 0) {
      return {
        class: 'bg-warning',
        text: 'Sin actividades',
        icon: 'fas fa-minus-circle'
      };
    }

    if (porcentaje >= 90) {
      return {
        class: 'bg-success',
        text: 'A tiempo',
        icon: 'fas fa-check-circle'
      };
    }

    if (porcentaje >= 80 && porcentaje < 90) {
      return {
        class: 'bg-warning',
        text: 'Buen avance',
        icon: 'fas fa-exclamation-circle'
      };
    }

    return {
      class: 'bg-danger',
      text: 'En riesgo',
      icon: 'fas fa-exclamation-circle'
    };
  }

  function loadResponsablesCards() {
    const container = $('#responsablesContainer');
    container.empty();

    $.get('/hoshin_kanri/app/mi_equipo/listar_equipo.php', function(resp) {

      if (!resp.success || !resp.data || resp.data.length === 0) {
        container.html(`
        <div class="col-12 text-center text-muted py-5">
          No hay responsables disponibles
        </div>
      `);
        return;
      }

      setRingHero(resp.porcentaje_equipo || 0);

      resp.data.forEach(r => {

        const totalTareas = safeNumber(r.total_tareas);
        const finalizadas = safeNumber(r.tareas_finalizadas);

        // NUEVO: rojas separadas + total
        const vencidasAbiertas = safeNumber(r.tareas_vencidas_abiertas);
        const completadasTarde = safeNumber(r.tareas_completadas_tarde);
        const rojasTotal = safeNumber(r.tareas_vencidas_total); // vencidas total

        const general = safeNumber(r.porcentaje_general);

        const area = r.area_nombre || 'Sin área';
        const isNova = (r.area_nombre || '').toLowerCase().includes('nova');

        const iniciales = getResponsableIniciales(r.nombre_completo);

        // Semáforo / badge (semanal como antes)
        const semaforoClass = getSemaforo(general, totalTareas);
        const badgeInfo = getBadgeInfo(general, totalTareas);

        container.append(`
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
          <div class="card responsable-card border-0 h-100">

            <!-- Header -->
            <div class="card-header ${isNova ? 'bg-success bg-opacity-10' : 'bg-primary bg-opacity-10'} border-0 pb-0 pt-4">
              <div class="d-flex justify-content-between align-items-start mb-3">

                <div class="position-relative">
                  <div class="avatar-lg bg-white border border-3 ${isNova ? 'border-success' : 'border-primary'}">
                    <div class="avatar-title text-${isNova ? 'success' : 'primary'} fw-bold fs-4">
                      ${iniciales}
                    </div>
                  </div>
                  <span class="status-dot ${semaforoClass}
                    position-absolute bottom-0 end-0 translate-middle"></span>
                </div>

                <div class="text-end">
                  <span class="badge ${badgeInfo.class} rounded-pill px-3 py-2">
                    <i class="${badgeInfo.icon} me-1"></i>
                    ${badgeInfo.text}
                  </span>
                </div>
              </div>

              <div class="mb-3">
                <h4 class="fw-bold mb-1">${r.nombre_completo}</h4>
                <p class="text-muted mb-0">
                  <i class="fas fa-briefcase me-2"></i>
                  ${r.rol} - ${area}
                </p>
              </div>
            </div>

            <!-- Body -->
            <div class="card-body pt-3">
              <div class="row g-2 mb-4">
                <div class="col-4">
                  <div class="text-center p-2">
                    <div class="fw-bold text-primary fs-5">${totalTareas}</div>
                    <div class="text-muted small mt-1">Actividades</div>
                  </div>
                </div>

                <div class="col-4">
                  <div class="text-center p-2">
                    <div class="fw-bold text-success fs-5">${finalizadas}</div>
                    <div class="text-muted small mt-1">Finalizadas</div>
                  </div>
                </div>

                <div class="col-4">
                  <div class="text-center p-2">
                    <div class="fw-bold text-danger fs-5">${rojasTotal}</div>
                    <div class="text-muted small mt-1">Vencidas</div>
                  </div>
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="d-flex align-items-center bg-light p-3 rounded">
                    <div class="me-3 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 ${vencidasAbiertas > 0 ? 'text-danger' : 'text-muted'}">
                        ${vencidasAbiertas}
                        </div>
                        <div class="small text-muted">Vencidas abiertas</div>
                    </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center bg-light p-3 rounded">
                    <div class="me-3 text-warning">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 ${completadasTarde > 0 ? 'text-warning' : 'text-muted'}">
                        ${completadasTarde}
                        </div>
                        <div class="small text-muted">Completadas tarde</div>
                    </div>
                    </div>
                </div>
                </div>

              <div class="text-center text-muted small">
                Nivel de Compromiso General: <strong>${general}%</strong>
              </div>
            </div>

            <!-- Footer -->
            <div class="card-footer bg-transparent border-0 pt-0">
              <div class="d-grid">
                <button
                  class="btn btn-outline-primary btn-lg btnVerResponsable py-2"
                  data-id="${r.usuario_id}">
                  <span class="d-flex align-items-center justify-content-center">
                    Ver detalles
                    <i class="fas fa-arrow-right ms-2"></i>
                  </span>
                </button>
              </div>
            </div>

          </div>
        </div>
      `);
      });
    }, 'json');
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function chipLevel(semaforo, rojas) {
    if (semaforo === 'ROJO') return {
      cls: 'hk-chip-danger',
      icon: 'fa-exclamation-circle',
      text: `En rojo (${rojas})`
    };
    return {
      cls: 'hk-chip-success',
      icon: 'fa-check-circle',
      text: 'En tiempo'
    };
  }

  function chipTarea(t) {
    console.log(t);
    const est = parseInt(t.estatus ?? 0, 10);
    const comp = parseInt(t.completada ?? 0, 10);

    if (est === 5) return {
      cls: 'hk-chip-danger',
      icon: 'fa-times-circle',
      text: 'Rechazada'
    };
    if (est === 6) return {
      cls: 'hk-chip-danger',
      icon: 'fa-exclamation-circle',
      text: 'Completada fuera de tiempo'
    };
    if (comp === 1 || est === 4) return {
      cls: 'hk-chip-success',
      icon: 'fa-check-circle',
      text: 'Aprobada'
    };
    if (t.semaforo === 'ROJO') return {
      cls: 'hk-chip-danger',
      icon: 'fa-exclamation-circle',
      text: 'Vencida'
    };
    if (est === 2) return {
      cls: 'hk-chip-warning',
      icon: 'fa-play-circle',
      text: 'En progreso'
    };
    if (est === 3) return {
      cls: 'hk-chip-info',
      icon: 'fa-search',
      text: 'En revisión'
    };
    if (est === 1) return {
      cls: 'hk-chip-info',
      icon: 'fa-folder-open',
      text: 'Abierta'
    };

    return {
      cls: 'hk-chip-muted',
      icon: 'fa-minus-circle',
      text: 'Sin estatus'
    };
  }

  function getHeaderStatusByResumen(semaforo, total, vencidasAbiertas = 0, fueraTiempo = 0) {
    if (total === 0 || semaforo === 'WARNING') {
      return {
        dot: 'bg-warning',
        badge: 'bg-warning text-dark',
        text: 'Sin actividades'
      };
    }

    // Rojo si hay abiertas vencidas o completadas fuera de tiempo
    if (semaforo === 'ROJO' || parseInt(vencidasAbiertas, 10) > 0 || parseInt(fueraTiempo, 10) > 0) {
      return {
        dot: 'bg-danger',
        badge: 'bg-danger',
        text: 'En rojo'
      };
    }

    return {
      dot: 'bg-success',
      badge: 'bg-success',
      text: 'En tiempo'
    };
  }


  function loadDetalleResponsable(usuarioId) {
    $('#accordionDetalle').html('<div class="text-center py-4">Cargando...</div>');

    $.get('/hoshin_kanri/app/mi_equipo/detalle_responasable.php', {
      responsable_id: usuarioId
    }, function(resp) {
      if (!resp.success) {
        $('#accordionDetalle').html('<div class="text-danger">Error al cargar detalle</div>');
        return;
      }

      const r = resp.resumen;

      $('#detalleNombre').text(r.nombre);
      $('#detalleRol').text(`${r.rol} - ${r.area_nombre}`);

      const iniciales = getResponsableIniciales(r.nombre);
      $('#detalleIniciales').text(iniciales);

      const total = parseInt(r.total || 0, 10);

      const aprobadas = parseInt(r.completadas_a_tiempo || 0, 10);
      const fueraTiempo = parseInt(r.completadas_fuera_tiempo || 0, 10);

      const vencidasAbiertas = parseInt(r.vencidas_abiertas ?? 0, 10);
      const vencidasTotal = parseInt(r.vencidas_total ?? (vencidasAbiertas + fueraTiempo), 10);

      // pendientes = lo que NO está aprobado a tiempo ni fuera de tiempo
      const pendientes = Math.max(0, total - aprobadas - fueraTiempo);

      $('#detalleFinalizadas').text(aprobadas);
      $('#detalleFinalizadas2').text(aprobadas);
      $('#detallePendientes').text(pendientes);
      $('#detalleVencidasFueraTiempo').text(fueraTiempo);

      // “Vencidas” ahora es total (abiertas + tardías)
      $('#detalleVencidas').text(vencidasTotal);

      // meta debajo: abiertas vs tarde
      $('#detalleVencidasMeta').text(`${vencidasAbiertas} abiertas · ${fueraTiempo} tarde`);


      $('#detalleTotal').text(total);
      $('#detallePorcentaje').text(r.porcentaje + '%');

      const p = r.porcentaje;
      console.log(p);
      $('#detalleProgress').css('width', p + '%');

      const st = getHeaderStatusByResumen(r.semaforo, total, vencidasAbiertas, fueraTiempo);

      $('#detalleDot').attr('class',
        `position-absolute bottom-0 end-0 translate-middle p-2 border border-2 border-white rounded-circle ${st.dot}`
      );
      $('#detalleBadgeEstado').attr('class', `badge rounded-pill ${st.badge}`).text(st.text);

      renderAccordion(resp.data);
    }, 'json');
  }

  function renderTareas(tareas) {
    if (!tareas || tareas.length === 0) {
      return `<div class="text-muted small">Sin tareas registradas.</div>`;
    }

    let html = `<div class="d-grid gap-2 mt-2">`;
    tareas.forEach(t => {
      const chip = chipTarea(t);
      const estado = t.estatus_txt || '';
      const fechas = `${t.fecha_inicio ?? ''} → ${t.fecha_fin ?? ''}`;

      html += `
      <div class="hk-task">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="fw-semibold">${escapeHtml(t.tarea)}</div>
            <div class="small text-muted">${fechas} · <span class="fw-semibold">${estado}</span></div>
          </div>
          <span class="hk-chip ${chip.cls}">
            <i class="fas ${chip.icon}"></i> ${chip.text}
          </span>
        </div>
      </div>
    `;
    });
    html += `</div>`;
    return html;
  }

  function renderMilestones(milestones, parentKey) {
    if (!milestones || milestones.length === 0) return `<div class="text-muted small">Sin milestones.</div>`;

    let html = `<div class="accordion mt-2" id="acc_m_${parentKey}">`;

    milestones.forEach((mil, idx) => {
      const key = `${parentKey}_m_${idx}`;
      const chip = chipLevel(mil.semaforo, mil.rojas);

      const totalTareas = (mil.tareas || []).length;
      const badgeTotal = `<span class="badge bg-primary ms-2">${totalTareas} tareas</span>`;

      html += `
      <div class="accordion-item mb-2">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#${key}">
            🏁 <span class="fw-semibold">${escapeHtml(mil.milestone || 'Sin milestone')}</span>
            ${badgeTotal}
            <span class="ms-2 hk-chip ${chip.cls}">
              <i class="fas ${chip.icon}"></i> ${chip.text}
            </span>
          </button>
        </h2>
        <div id="${key}" class="accordion-collapse collapse">
          <div class="accordion-body">
            ${renderTareas(mil.tareas)}
          </div>
        </div>
      </div>
    `;
    });

    html += `</div>`;
    return html;
  }

  function renderEstrategias(estrategias, parentKey) {
    if (!estrategias || estrategias.length === 0) return `<div class="text-muted small">Sin estrategias.</div>`;

    let html = `<div class="accordion" id="acc_e_${parentKey}">`;

    estrategias.forEach((est, idx) => {
      const key = `${parentKey}_e_${idx}`;
      const chip = chipLevel(est.semaforo, est.rojas);

      // total tareas dentro de la estrategia
      let totalTareas = 0;
      (est.milestones || []).forEach(m => totalTareas += (m.tareas || []).length);
      const badgeTotal = `<span class="badge bg-primary ms-2">${totalTareas} tareas</span>`;

      html += `
      <div class="accordion-item mb-2">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#${key}">
            ♟️ <span class="fw-semibold">${escapeHtml(est.estrategia)}</span>
            ${badgeTotal}
            <span class="ms-2 hk-chip ${chip.cls}">
              <i class="fas ${chip.icon}"></i> ${chip.text}
            </span>
          </button>
        </h2>
        <div id="${key}" class="accordion-collapse collapse">
          <div class="accordion-body">
            ${renderMilestones(est.milestones, key)}
          </div>
        </div>
      </div>
    `;
    });

    html += `</div>`;
    return html;
  }

  function renderAccordion(data) {
    let html = '';
    let idx = 0;

    // meta global: total tareas + rojas (derivadas)
    let totalT = 0,
      rojasT = 0;
    (data || []).forEach(o => {
      // suma tareas y rojas por objetivo desde su estructura
      (o.estrategias || []).forEach(e => {
        (e.milestones || []).forEach(m => {
          totalT += (m.tareas || []).length;
          rojasT += (m.rojas || 0);
        });
      });
    });

    (data || []).forEach(obj => {
      idx++;
      const key = `obj_${idx}`;
      const chip = chipLevel(obj.semaforo, obj.rojas);

      html += `
      <div class="accordion-item mb-2">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#${key}">
            🎯 <span class="fw-semibold">${escapeHtml(obj.objetivo || 'Sin objetivo')}</span>
            <span class="ms-2 hk-chip ${chip.cls}">
              <i class="fas ${chip.icon}"></i> ${chip.text}
            </span>
          </button>
        </h2>

        <div id="${key}" class="accordion-collapse collapse">
          <div class="accordion-body">
            ${renderEstrategias(obj.estrategias, key)}
          </div>
        </div>
      </div>
    `;
    });

    $('#accordionDetalle').html(html);
  }

  function setRingHero(percent) {
    const p = Math.max(0, Math.min(100, parseInt(percent, 10) || 0));
    const deg = p * 3.6;

    let color = 'var(--ring-danger)';
    let glow = false;

    if (p >= 90) {
      color = 'var(--ring-success)';
      glow = true;
    } else if (p >= 80 && p < 90) {
      color = 'var(--ring-warning)';
    } else {
      color = 'var(--ring-danger)';
    }

    const $ring = $('#hkRing');
    const $txt = $('#hkRingP');

    $ring
      .css('background', `conic-gradient(${color} ${deg}deg, rgba(255,255,255,.15) 0deg)`)
      .toggleClass('glow', glow);

    $txt
      .text(p + '%')
      .removeClass('success warning danger')
      .addClass(
        p >= 90 ? 'success' :
        p >= 80 && p < 90 ? 'warning' : 'danger'
      );
  }
</script>
