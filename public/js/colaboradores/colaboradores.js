$(document).ready(function () {
  let colaboradoresData = [];
  let areasUnicas = [];
  let rolesUnicos = [];

  // Cargar datos iniciales
  cargarDatos();

  // Eventos
  $("#btnRefresh").click(cargarDatos);
  $("#filterArea, #filterRol, #sortBy").change(filtrarDatos);

  $(document).on("click", ".kpi-compact-item", function (e) {
    // Si el click fue dentro de un elemento "interactivo", no abrir el modal
    if (
      $(e.target).closest(
        "a, button, input, select, textarea, [data-bs-toggle], .no-click",
      ).length
    )
      return;

    const usuarioId = $(this).data("usuario-id");
    loadDetalleResponsable(usuarioId);

    // Bootstrap 5 (si aplica):
    const modal = bootstrap.Modal.getOrCreateInstance(
      document.getElementById("modalDetalleColaborador"),
    );
    modal.show();
  });

  function cargarDatos() {
    showLoading();
    $.ajax({
      url: "/hoshin_kanri/app/colaboradores/colaboradores.php",
      method: "GET",
      dataType: "json",
      success: function (response) {
        if (response.success) {
          colaboradoresData = response.data;
          procesarDatos(colaboradoresData);
          actualizarEstadisticas();
          actualizarFiltros();
          $("#compactStats").removeClass("d-none");
        } else {
          showError(response.message || "Error al cargar datos");
        }
      },
      error: function () {
        showError("No se pudo conectar con el servidor");
      },
    });
  }

  function procesarDatos(datos) {
    let list = $("#colaboradoresList");
    list.empty();

    if (datos.length === 0) {
      list.html(`
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted mb-2">No hay colaboradores con tareas asignadas</h5>
                        <p class="text-muted small">Asigna tareas para ver las métricas de rendimiento</p>
                    </div>
                </div>
            `);
      return;
    }

    datos.forEach((colaborador, index) => {
      let porcentaje = colaborador.porcentaje_compromiso || 0;
      let badgeClass = getBadgeClass(porcentaje);
      let statusClass = getStatusClass(porcentaje);
      let statusText = getStatusText(porcentaje);
      let avatarClass = getAvatarClass(porcentaje);
      let completadasPorcentaje =
        colaborador.total_tareas > 0
          ? Math.round(
              (colaborador.finalizadas / colaborador.total_tareas) * 100,
            )
          : 0;

      let itemHtml = `
                <div class="kpi-compact-item" data-usuario-id="${colaborador.usuario_id}">
                    <div class="compact-col-user">
                        <div class="compact-avatar">
                            <div class="avatar-circle">${getResponsableIniciales(colaborador.nombre_completo)}</div>
                            <div class="avatar-status ${avatarClass}"></div>
                        </div>
                        <div class="compact-user-info">
                            <div class="user-main">
                                <h4 class="user-name">${escapeHtml(colaborador.nombre_completo)}</h4>
                                <span class="user-badge ${badgeClass}">${porcentaje}%</span>
                            </div>
                            <div class="user-secondary">
                                <span class="user-email">${escapeHtml(colaborador.correo)}</span>
                            </div>
                            <div class="user-meta">
                                <span class="meta-area">${escapeHtml(colaborador.area_nombre || "Sin área")}</span>
                                <span class="meta-separator">|</span>
                                <span class="meta-status ${statusClass}">${statusText}</span>
                            </div>
                        </div>
                    </div>

                    <div class="compact-col-metrics">
                        <div class="compact-metrics-grid">
                            <!-- Total -->
                            <div class="compact-metric total">
                                <div class="metric-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="metric-content">
                                    <div class="metric-value">${colaborador.total_tareas}</div>
                                    <div class="metric-label">Total</div>
                                </div>
                            </div>

                            <!-- Completadas -->
                            <div class="compact-metric completed">
                                <div class="metric-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="metric-content">
                                    <div class="metric-value">${colaborador.finalizadas}</div>
                                    <div class="metric-label">Completadas</div>
                                </div>
                                <div class="metric-progress">
                                    <div class="progress-bar" style="width: ${completadasPorcentaje}%"></div>
                                </div>
                            </div>

                            <!-- Pendientes -->
                            <div class="compact-metric pending">
                                <div class="metric-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="metric-content">
                                    <div class="metric-value">${colaborador.pendientes}</div>
                                    <div class="metric-label">Pendientes</div>
                                </div>
                            </div>

                            <!-- Vencidas -->
                            <div class="compact-metric overdue">
                                <div class="metric-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="metric-content">
                                    <div class="metric-value">${colaborador.vencidas}</div>
                                    <div class="metric-label">Vencidas</div>
                                </div>
                            </div>

                            <!-- Completadas tarde -->
                            <div class="compact-metric late">
                                <div class="metric-icon">
                                    <i class="fas fa-hourglass-end"></i>
                                </div>
                                <div class="metric-content">
                                    <div class="metric-value">${colaborador.completadas_tarde}</div>
                                    <div class="metric-label">Tarde</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
      list.append(itemHtml);

      // Animación escalonada
      setTimeout(() => {
        $(list.children().last()).addClass("fade-in");
      }, index * 50);
    });
  }

  function actualizarEstadisticas() {
    if (colaboradoresData.length === 0) {
      $("#statTotalColab").text("0");
      $("#statTotalCompletadas").text("0");
      $("#statTotalPendientes").text("0");
      $("#statTotalVencidas").text("0");
      return;
    }

    let totalFinalizadas = colaboradoresData.reduce(
      (sum, c) => sum + parseInt(c.finalizadas),
      0,
    );
    let totalPendientes = colaboradoresData.reduce(
      (sum, c) => sum + parseInt(c.pendientes),
      0,
    );
    let totalVencidas = colaboradoresData.reduce(
      (sum, c) => sum + parseInt(c.vencidas),
      0,
    );

    $("#statTotalColab").text(colaboradoresData.length);
    $("#statTotalCompletadas").text(totalFinalizadas);
    $("#statTotalPendientes").text(totalPendientes);
    $("#statTotalVencidas").text(totalVencidas);
  }

  function actualizarFiltros() {
    // Extraer áreas únicas
    areasUnicas = [
      ...new Set(colaboradoresData.map((c) => c.area_nombre || "Sin área")),
    ].filter(Boolean);
    let filterArea = $("#filterArea");
    filterArea.find("option:not(:first)").remove();
    areasUnicas.forEach((area) => {
      filterArea.append(`<option value="${area}">${area}</option>`);
    });

    // Extraer roles únicos
    rolesUnicos = [...new Set(colaboradoresData.map((c) => c.rol))].filter(
      Boolean,
    );
    let filterRol = $("#filterRol");
    filterRol.find("option:not(:first)").remove();
    rolesUnicos.forEach((rol) => {
      filterRol.append(`<option value="${rol}">${rol}</option>`);
    });
  }

  function filtrarDatos() {
    let areaFiltro = $("#filterArea").val();
    let rolFiltro = $("#filterRol").val();
    let orden = $("#sortBy").val();

    let datosFiltrados = [...colaboradoresData];

    // Aplicar filtros
    if (areaFiltro) {
      datosFiltrados = datosFiltrados.filter(
        (c) => (c.area_nombre || "Sin área") === areaFiltro,
      );
    }

    if (rolFiltro) {
      datosFiltrados = datosFiltrados.filter((c) => c.rol === rolFiltro);
    }

    // Aplicar ordenamiento
    datosFiltrados.sort((a, b) => {
      switch (orden) {
        case "porcentaje_desc":
          return (
            (b.porcentaje_compromiso || 0) - (a.porcentaje_compromiso || 0)
          );
        case "porcentaje_asc":
          return (
            (a.porcentaje_compromiso || 0) - (b.porcentaje_compromiso || 0)
          );
        case "nombre_asc":
          return a.nombre_completo.localeCompare(b.nombre_completo);
        case "nombre_desc":
          return b.nombre_completo.localeCompare(a.nombre_completo);
        case "vencidas_desc":
          return (b.vencidas || 0) - (a.vencidas || 0);
        default:
          return 0;
      }
    });

    procesarDatos(datosFiltrados);
    actualizarEstadisticas();
  }

  function showLoading() {
    $("#colaboradoresList").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2">Cargando colaboradores...</p>
            </div>
        `);
  }

  function showError(message) {
    $("#colaboradoresList").html(`
            <div class="text-center py-5">
                <div class="empty-state error">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger mb-2">Error al cargar datos</h5>
                    <p class="text-muted">${message}</p>
                </div>
            </div>
        `);
  }

  // Funciones auxiliares
  function escapeHtml(text) {
    if (!text) return "";
    let div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  function getBadgeClass(percentage) {
    if (percentage >= 90) return "success";
    if (percentage >= 80 && percentage < 90) return "warning";
    return "danger";
  }

  function getStatusClass(percentage) {
    if (percentage >= 90) return "success";
    if (percentage >= 80 && percentage < 90) return "warning";
    return "danger";
  }

  function getStatusText(percentage) {
    if (percentage >= 90) return "Excelente";
    if (percentage >= 80 && percentage < 90) return "Regular";
    return "Necesita atención";
  }

  function getAvatarClass(percentage) {
    if (percentage >= 90) return "success";
    if (percentage >= 80 && percentage < 90) return "warning";
    return "danger";
  }

  function loadDetalleResponsable(usuarioId) {
    $("#accordionDetalle").html(
      '<div class="text-center py-4">Cargando...</div>',
    );

    $.get(
      "/hoshin_kanri/app/colaboradores/colaborador_tareas.php",
      { usuario_id: usuarioId },
      function (resp) {
        if (!resp.success) {
          $("#accordionDetalle").html(
            '<div class="text-danger">Error al cargar detalle</div>',
          );
          return;
        }

        const r = resp.resumen;

        $("#detalleNombre").text(r.nombre);
        $("#detalleRol").text(`${r.rol} - ${r.area_nombre}`);

        const iniciales = getResponsableIniciales(r.nombre);
        $("#detalleIniciales").text(iniciales);

        const total = parseInt(r.total || 0, 10);

        const aprobadas = parseInt(r.completadas_a_tiempo || 0, 10);
        const fueraTiempo = parseInt(r.completadas_fuera_tiempo || 0, 10);

        const vencidasAbiertas = parseInt(r.vencidas_abiertas ?? 0, 10);
        const vencidasTotal = parseInt(
          r.vencidas_total ?? vencidasAbiertas + fueraTiempo,
          10,
        );

        // pendientes = lo que NO está aprobado a tiempo ni fuera de tiempo
        const pendientes = Math.max(0, total - aprobadas - fueraTiempo);

        $("#detalleFinalizadas").text(aprobadas);
        $("#detalleFinalizadas2").text(aprobadas);
        $("#detallePendientes").text(pendientes);
        $("#detalleVencidasFueraTiempo").text(fueraTiempo);

        // “Vencidas” ahora es total (abiertas + tardías)
        $("#detalleVencidas").text(vencidasTotal);

        // meta debajo: abiertas vs tarde
        $("#detalleVencidasMeta").text(
          `${vencidasAbiertas} abiertas · ${fueraTiempo} tarde`,
        );

        $("#detalleTotal").text(total);
        $("#detallePorcentaje").text(r.porcentaje + "%");

        const p = r.porcentaje;
        $("#detalleProgress").css("width", p + "%");

        const st = getHeaderStatusByResumen(
          r.semaforo,
          total,
          vencidasAbiertas,
          fueraTiempo,
        );

        $("#detalleDot").attr(
          "class",
          `position-absolute bottom-0 end-0 translate-middle p-2 border border-2 border-white rounded-circle ${st.dot}`,
        );
        $("#detalleBadgeEstado")
          .attr("class", `badge rounded-pill ${st.badge}`)
          .text(st.text);

        renderAccordion(resp.data);
      },
      "json",
    );
  }

  function renderTareas(tareas) {
    if (!tareas || tareas.length === 0) {
      return `<div class="text-muted small">Sin tareas registradas.</div>`;
    }

    let html = `<div class="d-grid gap-2 mt-2">`;
    tareas.forEach((t) => {
      const chip = chipTarea(t);
      const estado = t.estatus_txt || "";
      const fechas = `${t.fecha_inicio ?? ""} → ${t.fecha_fin ?? ""}`;

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
    if (!milestones || milestones.length === 0)
      return `<div class="text-muted small">Sin milestones.</div>`;

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
            🏁 <span class="fw-semibold">${escapeHtml(mil.milestone || "Sin milestone")}</span>
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
    if (!estrategias || estrategias.length === 0)
      return `<div class="text-muted small">Sin estrategias.</div>`;

    let html = `<div class="accordion" id="acc_e_${parentKey}">`;

    estrategias.forEach((est, idx) => {
      const key = `${parentKey}_e_${idx}`;
      const chip = chipLevel(est.semaforo, est.rojas);

      // total tareas dentro de la estrategia
      let totalTareas = 0;
      (est.milestones || []).forEach(
        (m) => (totalTareas += (m.tareas || []).length),
      );
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
    let html = "";
    let idx = 0;

    // meta global: total tareas + rojas (derivadas)
    let totalT = 0,
      rojasT = 0;
    (data || []).forEach((o) => {
      // suma tareas y rojas por objetivo desde su estructura
      (o.estrategias || []).forEach((e) => {
        (e.milestones || []).forEach((m) => {
          totalT += (m.tareas || []).length;
          rojasT += m.rojas || 0;
        });
      });
    });

    (data || []).forEach((obj) => {
      idx++;
      const key = `obj_${idx}`;
      const chip = chipLevel(obj.semaforo, obj.rojas);

      html += `
      <div class="accordion-item mb-2">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#${key}">
            🎯 <span class="fw-semibold">${escapeHtml(obj.objetivo || "Sin objetivo")}</span>
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

    $("#accordionDetalle").html(html);
  }
});
