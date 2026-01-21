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

  $.get('/hoshin_kanri/app/dashboard/responsables.php', function (resp) {

    if (!resp.success || !resp.data || resp.data.length === 0) {
      container.html(`
        <div class="col-12 text-center text-muted py-5">
          No hay responsables disponibles
        </div>
      `);
      return;
    }

    resp.data.forEach(r => {

      const totalTareas = safeNumber(r.total_tareas);
      const finalizadas = safeNumber(r.tareas_finalizadas);

      // NUEVO: rojas separadas + total
      const vencidasAbiertas = safeNumber(r.tareas_vencidas_abiertas);
      const completadasTarde = safeNumber(r.tareas_completadas_tarde);
      const rojasTotal = safeNumber(r.tareas_vencidas_total); // vencidas total

      const semanal = safeNumber(r.porcentaje_semanal);
      const general = safeNumber(r.porcentaje_general);

      const area = r.area_nombre || 'Sin área';
      const isNova = (r.area_nombre || '').toLowerCase().includes('nova');

      const iniciales = getResponsableIniciales(r.nombre_completo);

      // Semáforo / badge (semanal como antes)
      const semaforoClass = getSemaforo(semanal, totalTareas);
      const badgeInfo = getBadgeInfo(semanal, totalTareas);

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
                Nivel de Compromiso Semanal: <strong>${semanal}%</strong><br>
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
  if (semaforo === 'ROJO') return { cls: 'hk-chip-danger', icon: 'fa-exclamation-circle', text: `En rojo (${rojas})` };
  return { cls: 'hk-chip-success', icon: 'fa-check-circle', text: 'En tiempo' };
}

function chipTarea(t) {
  console.log(t);
  const est = parseInt(t.estatus ?? 0, 10);
  const comp = parseInt(t.completada ?? 0, 10);

  if (est === 5) return { cls: 'hk-chip-danger', icon: 'fa-times-circle', text: 'Rechazada' };
  if (est === 6) return { cls: 'hk-chip-danger', icon: 'fa-exclamation-circle', text: 'Completada fuera de tiempo' };
  if (comp === 1 || est === 4) return { cls: 'hk-chip-success', icon: 'fa-check-circle', text: 'Aprobada' };
  if (t.semaforo === 'ROJO') return { cls: 'hk-chip-danger', icon: 'fa-exclamation-circle', text: 'Vencida' };
  if (est === 2) return { cls: 'hk-chip-warning', icon: 'fa-play-circle', text: 'En progreso' };
  if (est === 3) return { cls: 'hk-chip-info', icon: 'fa-search', text: 'En revisión' };
  if (est === 1) return { cls: 'hk-chip-info', icon: 'fa-folder-open', text: 'Abierta' };

  return { cls: 'hk-chip-muted', icon: 'fa-minus-circle', text: 'Sin estatus' };
}

function getHeaderStatusByResumen(semaforo, total, vencidasAbiertas = 0, fueraTiempo = 0) {
  if (total === 0 || semaforo === 'WARNING') {
    return { dot: 'bg-warning', badge: 'bg-warning text-dark', text: 'Sin actividades' };
  }

  // Rojo si hay abiertas vencidas o completadas fuera de tiempo
  if (semaforo === 'ROJO' || parseInt(vencidasAbiertas, 10) > 0 || parseInt(fueraTiempo, 10) > 0) {
    return { dot: 'bg-danger', badge: 'bg-danger', text: 'En rojo' };
  }

  return { dot: 'bg-success', badge: 'bg-success', text: 'En tiempo' };
}


function loadDetalleResponsable(usuarioId) {
  $('#accordionDetalle').html('<div class="text-center py-4">Cargando...</div>');

  $.get('/hoshin_kanri/app/dashboard/responsable_detalle.php', { usuario_id: usuarioId }, function (resp) {
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
  let totalT = 0, rojasT = 0;
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

// ====================================================================================== //
function setHeroBadge(vencidas, pendientes) {
  if (pendientes === 0) {
    $('#hkHeroBadge').text('Sin pendientes').css({ background: 'rgba(255,255,255,.18)' });
    return;
  }
  if (vencidas > 0) {
    $('#hkHeroBadge').text('En riesgo 🔴').css({ background: 'rgba(220,53,69,.25)' });
  } else {
    $('#hkHeroBadge').text('En tiempo 🟢').css({ background: 'rgba(25,135,84,.18)' });
  }
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


function tlItem(t) {
  const d = new Date(t.fecha_fin + 'T00:00:00');
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const taskDate = new Date(d);
  taskDate.setHours(0, 0, 0, 0);

  // Determinar si es hoy o mañana
  let dayClass = '';
  let dayIcon = 'far fa-calendar';
  const diffDays = Math.round((taskDate - today) / (1000 * 60 * 60 * 24));

  if (diffDays === 0) {
    dayClass = 'text-danger';
    dayIcon = 'fas fa-bolt';
  } else if (diffDays === 1) {
    dayClass = 'text-warning';
    dayIcon = 'fas fa-clock';
  } else if (diffDays < 0) {
    dayClass = 'text-danger';
    dayIcon = 'fas fa-exclamation-triangle';
  }

  const dayName = d.toLocaleDateString('es-MX', { weekday: 'short' });
  const dayNum = d.getDate();
  const mon = d.toLocaleDateString('es-MX', { month: 'short' }).toUpperCase();

  // Iconos según el bucket
  const bucketIcons = {
    'urgente': 'fas fa-fire',
    'alto': 'fas fa-chevron-up',
    'medio': 'fas fa-minus',
    'bajo': 'fas fa-chevron-down',
    'proyecto': 'fas fa-project-diagram',
    'reunión': 'fas fa-users',
    'personal': 'fas fa-user-circle'
  };

  // Iconos según la estrategia
  const strategyIcons = {
    'planificación': 'fas fa-chess-board',
    'ejecución': 'fas fa-play',
    'revisión': 'fas fa-search',
    'análisis': 'fas fa-chart-bar',
    'desarrollo': 'fas fa-code',
    'diseño': 'fas fa-paint-brush'
  };

  const bucketIcon = bucketIcons[t.bucket?.toLowerCase()] || 'fas fa-tasks';
  const strategyIcon = strategyIcons[t.estrategia?.toLowerCase()] || 'fas fa-chess';

  return `
    <div class="hk-tl-item ${diffDays < 0 ? 'border-danger border-2' : ''}">
      <div class="hk-tl-left">
        <i class="${dayIcon} hk-tl-icon ${dayClass}"></i>
        <div class="hk-tl-day ${dayClass}">${dayName}</div>
        <div class="hk-tl-date">${dayNum} ${mon}</div>
      </div>

      <div class="hk-tl-main">
        <div class="hk-tl-title">
          <i class="${bucketIcon} me-2 text-primary"></i>
          ${t.tarea}
          ${diffDays < 0 ? '<i class="fas fa-exclamation-circle ms-2 text-danger" title="Vencida"></i>' : ''}
        </div>
        <div class="hk-tl-meta">
          <span class="me-3">
            <i class="fas fa-flag-checkered me-1"></i>
            ${t.milestone || 'Sin hito'}
          </span>
          <span>
            <i class="${strategyIcon} me-1"></i>
            ${t.estrategia || 'Sin estrategia'}
          </span>
        </div>
        ${t.objetivo ? `
        <div class="hk-tl-submeta mt-1">
          <i class="fas fa-bullseye me-1 text-muted"></i>
          <small class="text-muted">${t.objetivo}</small>
        </div>
        ` : ''}
      </div>

      <div class="hk-tl-right">
        ${pillForTask(t.bucket)}
        <div class="d-flex gap-2 mt-2">
          <button class="btn btn-sm btn-outline-success btnCompletarTarea"
                  data-id="${t.tarea_id}"
                  title="Marcar como completada">
            <i class="fas fa-check"></i>
          </button>
          <button class="btn btn-sm btn-outline-primary btnAbrirDetalle"
                  data-id="${t.tarea_id}"
                  title="Ver detalles">
            <i class="fas fa-eye"></i>
          </button>
          ${diffDays < 0 ? `
          <button class="btn btn-sm btn-outline-danger btnPosponerTarea"
                  data-id="${t.tarea_id}"
                  title="Posponer tarea">
            <i class="fas fa-clock-rotate-left"></i>
          </button>
          ` : ''}
        </div>
      </div>
    </div>
  `;
}

function prioCard(t) {
  const d = new Date(t.fecha_fin + 'T00:00:00');
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const taskDate = new Date(d);
  taskDate.setHours(0, 0, 0, 0);
  const diffDays = Math.round((taskDate - today) / (1000 * 60 * 60 * 24));

  // Estilo de prioridad
  let priorityStyle = '';
  let progressColor = '';

  if (diffDays < 0) {
    priorityStyle = 'overdue';
    progressColor = '#dc3545';
  } else if (diffDays === 0) {
    priorityStyle = 'urgent';
    progressColor = '#dc3545';
  } else if (diffDays === 1) {
    priorityStyle = 'high';
    progressColor = '#fd7e14';
  } else if (diffDays <= 3) {
    priorityStyle = 'medium';
    progressColor = '#0d6efd';
  } else {
    priorityStyle = 'normal';
    progressColor = '#198754';
  }

  // Días restantes
  let daysLabel = '';
  if (diffDays < 0) {
    daysLabel = `Vencida hace ${Math.abs(diffDays)} días`;
  } else if (diffDays === 0) {
    daysLabel = 'Vence hoy';
  } else if (diffDays === 1) {
    daysLabel = 'Vence mañana';
  } else {
    daysLabel = `Vence en ${diffDays} días`;
  }

  return `
    <div class="priority-card priority-${priorityStyle}">
        <div class="priority-header">
            <div class="priority-badge">${t.bucket}</div>
            <div class="priority-date">${d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' })}</div>
        </div>

        <div class="priority-content">
            <h6 class="priority-title">
                <i class="fas fa-chevron-right me-2"></i>
                ${t.tarea}
            </h6>

            <div class="priority-metadata">
                <div class="meta-item">
                    <i class="fas fa-bullseye"></i>
                    <span>${t.objetivo || 'Sin objetivo'}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-chess"></i>
                    <span>${t.estrategia || 'Sin estrategia'}</span>
                </div>
            </div>

            <div class="progress-wrapper mt-2">
                <div class="d-flex justify-content-between">
                    <small class="text-muted">${daysLabel}</small>
                    <small class="text-muted">${t.milestone || 'Sin hito'}</small>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar"
                         style="width: ${Math.min(100, 100 - (diffDays * 10))}%; background-color: ${progressColor}">
                    </div>
                </div>
            </div>
        </div>

        <div class="priority-footer">
            <button class="btn-action btn-action--complete btnCompletarTarea" data-id="${t.tarea_id}">
                <i class="fas fa-check"></i> Completar
            </button>
            <button class="btn-action btn-action--view btnAbrirDetalle" data-id="${t.tarea_id}">
                <i class="fas fa-arrow-right"></i> Detalles
            </button>
        </div>
    </div>
    `;
}

function pillForTask(bucket) {
  const bucketIcons = {
    'urgente': 'fas fa-fire',
    'alto': 'fas fa-chevron-up',
    'medio': 'fas fa-minus',
    'bajo': 'fas fa-chevron-down',
    'proyecto': 'fas fa-project-diagram',
    'reunión': 'fas fa-users',
    'personal': 'fas fa-user-circle'
  };

  const bucketClasses = {
    'urgente': 'hk-pill-danger',
    'alto': 'hk-pill-warning',
    'medio': 'hk-pill-primary',
    'bajo': 'hk-pill-success',
    'proyecto': 'hk-pill-info',
    'reunión': 'hk-pill-secondary',
    'personal': 'hk-pill-muted'
  };

  const bucketLower = bucket?.toLowerCase() || 'medio';
  const icon = bucketIcons[bucketLower] || 'fas fa-tasks';
  const className = bucketClasses[bucketLower] || 'hk-pill-muted';

  return `
    <span class="hk-pill ${className}">
      <i class="${icon}"></i>
      ${bucket}
    </span>
  `;
}

// Añadir estos estilos CSS adicionales
const additionalStyles = `
.hk-prio-overdue {
    background: linear-gradient(45deg, rgba(220,53,69,.03), rgba(220,53,69,.08));
    border-color: rgba(220,53,69,.2) !important;
}

.hk-tl-submeta {
    font-size: 0.75rem;
    padding-left: 1.5rem;
}

.hk-pill-primary {
    background: rgba(13,110,253,.12);
    color: #0d6efd;
}

.hk-pill-info {
    background: rgba(111,66,193,.12);
    color: #6f42c1;
}

.hk-pill-secondary {
    background: rgba(108,117,125,.12);
    color: #6c757d;
}

.hk-tl-item.border-danger {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(220,53,69,0.4); }
    70% { box-shadow: 0 0 0 6px rgba(220,53,69,0); }
    100% { box-shadow: 0 0 0 0 rgba(220,53,69,0); }
}
`;

function loadColabHero() {
  $.get('/hoshin_kanri/app/dashboard/colab_resumen.php', function (resp) {
    if (!resp.success) return;

    $('#hkPendientes').text(resp.kpi.pendientes);
    $('#hkVencidas').text(resp.kpi.vencidas);
    $('#hkHoy').text(resp.kpi.vence_hoy);
    $('#hkProgreso').text(resp.kpi.porcentaje + '%');

    $('#hkFinalizadas').text(resp.kpi.finalizadas);
    $('#hkTotal').text(resp.kpi.total);

    setHeroBadge(resp.kpi.vencidas, resp.kpi.pendientes);
    setRingHero(resp.kpi.nivel_compromiso);

    // opcional: tu card superior si la usas
    $('#tareasCount').text(resp.kpi.pendientes);
  }, 'json');
}

function loadColabTimelineAndPriorities() {
  $('#hkTimeline').html('<div class="text-muted small">Cargando...</div>');
  $('#hkPrioridades').html('<div class="text-muted small">Cargando...</div>');

  $.get('/hoshin_kanri/app/dashboard/colab_kanban.php', function (resp) {
    if (!resp.success) return;

    const vencidas = (resp.data.vencidas || []).map(t => ({ ...t, bucket: 'VENCIDA' }));
    const hoy = (resp.data.hoy || []).map(t => ({ ...t, bucket: 'HOY' }));
    const semana = (resp.data.semana || []).map(t => ({ ...t, bucket: 'SEMANA' }));

    // Prioridades: vencidas + hoy + semana (top 6)
    const prioridades = [...vencidas, ...hoy, ...semana].slice(0, 6);
    $('#hkPrioridades').html(prioridades.length ? prioridades.map(prioCard).join('') : '<div class="text-muted small">No tienes pendientes</div>');

    // Timeline: hoy + semana (top 10)
    const timeline = [...hoy, ...semana].slice(0, 10);
    $('#hkTimeline').html(timeline.length ? timeline.map(tlItem).join('') : '<div class="text-muted small">Nada próximo a vencer</div>');
  }, 'json');
}

function reloadColabDashboard2() {
  loadColabHero();
  loadColabTimelineAndPriorities();
}

$(document).on('click', '#btnRefrescarDashboard2', function () {
  reloadColabDashboard2();
});

$(document).on('click', '#btnIrMisTareas2', function () {
  window.location.href = '/hoshin_kanri/public/mis_tareas.php';
});

$(document).on('click', '.btnCompletarTarea', function () {
  const id = $(this).data('id');
  $.post('/hoshin_kanri/app/tareas/marcar_completada.php', { tarea_id: id }, function (resp) {
    if (!resp.success) { alert(resp.message || 'Error'); return; }
    reloadColabDashboard2();
  }, 'json');
});

$(document).on('click', '.btnAbrirDetalle', function () {
  const id = $(this).data('id');
  window.location.href = '/hoshin_kanri/public/detalle.php?tarea_id=' + id;
});


$(document).ready(function () {
  // 1) genera/actualiza la semana actual
  $.get('/hoshin_kanri/app/kpi/gerentes_snapshot.php', function () {
    /* // 2) luego trae el resumen para pintar cards/gráficas
    $.get('/hoshin_kanri/app/kpi/gerentes_resumen.php', function (resp) {
        if (!resp.success) return;
        console.log(resp.general, resp.serie);
        // aquí ya pintas tu UI
    }, 'json'); */
  }, 'json');

  reloadColabDashboard2();
  loadResponsablesCards();
  function animateCounter(selector, target) {
    const $el = $(selector);
    if (!$el.length) return;

    const start = parseInt($el.text(), 10) || 0;
    $({ n: start }).animate({ n: target }, {
      duration: 600,
      easing: 'swing',
      step: function (now) {
        $el.text(Math.ceil(now));
      }
    });
  }

  function loadDashboardStats() {
    $.get('/hoshin_kanri/app/dashboard/stats.php', function (resp) {
      if (!resp || !resp.success) return;

      animateCounter('#objetivosCount', resp.data.objetivos);
      animateCounter('#estrategiasCount', resp.data.estrategias);
      animateCounter('#milestonesCount', resp.data.milestones);
      animateCounter('#tareasCount', resp.data.tareas);
    }, 'json');
  }

  loadDashboardStats();
  setInterval(loadDashboardStats, 30000);
  setInterval(loadResponsablesCards, 30000);


  $(document).on('click', '.btnVerResponsable', function () {
    const usuarioId = $(this).data('id');
    $('#modalDetalleResponsable').modal('show');
    loadDetalleResponsable(usuarioId);
  });
});
