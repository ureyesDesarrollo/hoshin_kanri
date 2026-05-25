const paramsDirectivos = new URLSearchParams(window.location.search);
let directivos = [];
let modalDirectivo;
let zonasDirectivas = [];
let tiposProyectoDirectivos = [];

function moneda(v) {
  return new Intl.NumberFormat("es-MX", {
    style: "currency",
    currency: "MXN",
  }).format(Number(v || 0));
}

function esc(v) {
  const div = document.createElement("div");
  div.textContent = v || "";
  return div.innerHTML;
}

function pct(v) {
  const n = Math.max(0, Math.min(100, Number(v || 0)));
  return n.toFixed(0);
}

function fechaMx(v) {
  if (!v) return "Sin fecha";
  const partes = String(v).split("-");
  if (partes.length !== 3) return v;
  const fecha = new Date(Number(partes[0]), Number(partes[1]) - 1, Number(partes[2]));
  return fecha.toLocaleDateString("es-MX", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function proyectoCerrado(r) {
  return r.estado_directivo === "Cerrado" || Number(r.milestone_estatus || 0) === 2;
}

function entregaClass(v, r) {
  if (proyectoCerrado(r)) return "bg-success";
  if (!v) return "bg-light text-muted border";
  const partes = String(v).split("-");
  const entrega = new Date(Number(partes[0]), Number(partes[1]) - 1, Number(partes[2]));
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  entrega.setHours(0, 0, 0, 0);
  const dias = Math.round((entrega - hoy) / (1000 * 60 * 60 * 24));

  if (dias < 0) return "bg-danger";
  if (dias <= 7) return "bg-warning text-dark";
  return "bg-info";
}

function renderKpis(rows) {
  const presupuesto = rows.reduce(
    (s, r) => s + Number(r.presupuesto_aprobado || 0),
    0,
  );
  const gasto = rows.reduce((s, r) => s + Number(r.gasto_real || 0), 0);
  const beneficio = rows.reduce(
    (s, r) => s + Number(r.beneficio_estimado || 0),
    0,
  );
  $("#kpiTotal").text(rows.length);
  $("#kpiPresupuesto").text(moneda(presupuesto));
  $("#kpiGasto").text(moneda(gasto));
  $("#kpiBeneficio").text(moneda(beneficio));
}

function renderTabla(rows) {
  renderKpis(rows);
  const tbody = $("#tablaDirectivos");

  if (!rows.length) {
    tbody.html(
      '<tr><td colspan="9" class="text-center py-5 text-muted">No hay proyectos directivos con estos filtros</td></tr>',
    );
    return;
  }

  tbody.html(
    rows
      .map(
        (r) => `
      <tr>
        <td class="ps-4">
          <div class="fw-bold">${esc(r.nombre_directivo)}</div>
          <div class="small text-muted">${esc(r.milestone)} · ${esc(r.estrategia)}</div>
        </td>
        <td>${esc(r.zona || "Sin zona")}</td>
        <td>${esc(r.area || "Sin área")}</td>
        <td>
          <div><span class="priority-chip priority-${esc(r.prioridad_directiva)}">${esc(r.prioridad_directiva)}</span></div>
          <div class="small text-muted mt-1">${esc(r.estado_directivo)}</div>
          <div class="small text-muted">${esc(r.tipo_proyecto)}</div>
        </td>
        <td>
          <div class="fw-semibold">${esc(r.responsable || "Sin asignar")}</div>
          <div class="small text-muted">${esc(r.responsable_area || "Sin área")}</div>
        </td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="gantt-mini"><span style="width:${pct(r.avance_real)}%"></span></div>
            <span class="small fw-bold">${pct(r.avance_real)}%</span>
          </div>
          <div class="small text-muted">${r.tareas_finalizadas || 0}/${r.total_tareas || 0} tareas</div>
        </td>
        <td>
          <span class="badge ${entregaClass(r.fecha_fin_objetivo || r.fecha_fin_operativa, r)}">
            ${fechaMx(r.fecha_fin_objetivo || r.fecha_fin_operativa)}
          </span>
          <div class="small text-muted mt-1">
            ${proyectoCerrado(r) ? "Cerrado" : (r.fecha_fin_objetivo ? "Directiva" : "Desde tareas")}
          </div>
        </td>
        <td>
          <div class="semaforo-stack small">
            <div class="semaforo-item">
              <span class="semaforo-dot semaforo-${esc(r.semaforo_tiempo || "gris")}"></span>
              <span>Tiempo</span>
            </div>
            <div class="semaforo-item">
              <span class="semaforo-dot semaforo-${esc(r.semaforo_presupuesto || "gris")}"></span>
              <span>Presupuesto</span>
            </div>
          </div>
        </td>
        <td class="text-end pe-4">
          <div class="directivo-actions">
            <button class="btn btn-sm btn-outline-primary btnEditarDirectivo" data-id="${r.proyecto_directivo_id}" title="Editar">
              <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger btnOcultarDirectivo" data-id="${r.proyecto_directivo_id}" title="Ocultar">
              <i class="fas fa-eye-slash"></i>
            </button>
          </div>
        </td>
      </tr>
    `,
      )
      .join(""),
  );
}

function cargarZonasDirectivas(selected = "") {
  return $.get(
    "/hoshin_kanri/app/catalogos_directivos/zonas_listar.php",
    function (resp) {
      zonasDirectivas = resp.data || [];
      const select = $("#pdZonaDirectivaId");
      const filtro = $("#filtroZonaDirectiva");
      select.html('<option value="">Seleccionar zona</option>');
      filtro.html('<option value="">Todas las zonas</option>');
      zonasDirectivas.forEach((z) => {
        select.append(
          `<option value="${z.zona_directiva_id}">${esc(z.nombre)}</option>`,
        );
        filtro.append(`<option value="${esc(z.nombre)}">${esc(z.nombre)}</option>`);
      });
      if (selected) select.val(String(selected));
    },
    "json",
  );
}

function cargarAreasDirectivas(zonaId, selected = "") {
  const select = $("#pdAreaDirectivaId");
  select.html('<option value="">Seleccionar área</option>');

  if (!zonaId) return $.Deferred().resolve().promise();

  return $.get(
    "/hoshin_kanri/app/catalogos_directivos/areas_listar.php",
    { zona_directiva_id: zonaId },
    function (resp) {
      (resp.data || []).forEach((a) => {
        select.append(
          `<option value="${a.area_directiva_id}">${esc(a.nombre)}</option>`,
        );
      });
      if (selected) select.val(String(selected));
    },
    "json",
  );
}

function cargarDirectivos() {
  $.get(
    "/hoshin_kanri/app/proyectos_directivos/listar.php",
    {
      q: $("#filtroDirectivo").val(),
      prioridad: $("#filtroPrioridadDirectiva").val(),
      estado: $("#filtroEstadoDirectivo").val(),
      zona: $("#filtroZonaDirectiva").val(),
      area_responsable_id: $("#filtroAreaResponsable").val(),
      tipo_proyecto_id: $("#filtroTipoProyecto").val(),
    },
    function (resp) {
      if (!resp.success) {
        Swal.fire(
          "Error",
          resp.message || "No se pudo cargar el portafolio",
          "error",
        );
        return;
      }
      directivos = resp.data || [];
      renderTabla(directivos);
    },
    "json",
  );
}

function cargarAreasResponsables() {
  return $.get(
    "/hoshin_kanri/app/proyectos_directivos/areas_responsables_listar.php",
    function (resp) {
      const select = $("#filtroAreaResponsable");
      select.html('<option value="">Todas las áreas</option>');

      (resp.data || []).forEach((a) => {
        select.append(`<option value="${a.area_id}">${esc(a.nombre)}</option>`);
      });
    },
    "json",
  );
}

function cargarTiposProyectoDirectivos(selected = "") {
  return $.get(
    "/hoshin_kanri/app/catalogos_directivos/tipos_listar.php",
    function (resp) {
      tiposProyectoDirectivos = resp.data || [];
      const select = $("#pdTipoProyectoDirectivoId");
      const filtro = $("#filtroTipoProyecto");

      select.html('<option value="">Seleccionar tipo</option>');
      filtro.html('<option value="">Todos los tipos</option>');

      tiposProyectoDirectivos.forEach((t) => {
        select.append(
          `<option value="${t.tipo_proyecto_directivo_id}">${esc(t.nombre)}</option>`,
        );
        filtro.append(
          `<option value="${t.tipo_proyecto_directivo_id}">${esc(t.nombre)}</option>`,
        );
      });

      if (selected) select.val(String(selected));
    },
    "json",
  );
}

function llenarModal(r) {
  $("#pdId").val(r.proyecto_directivo_id || "");
  $("#pdMilestoneId").val(r.milestone_id || "");
  $("#pdNombre").val(r.nombre_directivo || r.milestone || "");
  $("#pdZonaDirectivaId").val(r.zona_directiva_id || "");
  cargarAreasDirectivas(r.zona_directiva_id || "", r.area_directiva_id || "");
  $("#pdTipoProyectoDirectivoId").val(r.tipo_proyecto_directivo_id || "");
  $("#pdPrioridad").val(r.prioridad_directiva || "Media");
  $("#pdEstado").val(r.estado_directivo || "En evaluacion");
  $("#pdInversion").val(r.inversion_estimada || 0);
  $("#pdPresupuesto").val(r.presupuesto_aprobado || 0);
  $("#pdGasto").val(r.gasto_real || 0);
  $("#pdBeneficioEstimado").val(r.beneficio_estimado || 0);
  $("#pdBeneficioReal").val(r.beneficio_real || 0);
  $("#pdFechaInicio").val(r.fecha_inicio_directiva || r.fecha_inicio_operativa || "");
  $("#pdFechaFin").val(r.fecha_fin_objetivo || r.fecha_fin_operativa || "");
  $("#pdReporte").prop(
    "checked",
    Number(r.requiere_reporte_direccion || 1) === 1,
  );
  $("#pdMotivoPrioridad").val("");
  $("#pdNotas").val(r.notas_directivas || "");
  modalDirectivo.show();
}

function promoverDesdeQuery() {
  const milestoneId = Number(paramsDirectivos.get("milestone_id") || 0);
  if (!milestoneId) return;

  $.post(
    "/hoshin_kanri/app/proyectos_directivos/promover.php",
    { milestone_id: milestoneId },
    function (resp) {
      if (!resp.success) {
        Swal.fire(
          "Error",
          resp.message || "No se pudo enviar a Dirección",
          "error",
        );
        return;
      }
      Swal.fire("Listo", resp.message, "success");
      cargarDirectivos();
      window.history.replaceState({}, document.title, "gestion_directiva.php");
    },
    "json",
  );
}

$(document).ready(function () {
  if (!document.getElementById("tablaDirectivos")) return;

  modalDirectivo = new bootstrap.Modal(
    document.getElementById("modalDirectivo"),
  );
  cargarZonasDirectivas();
  cargarAreasResponsables();
  cargarTiposProyectoDirectivos();
  cargarDirectivos();
  promoverDesdeQuery();

  let timer = null;
  $("#filtroDirectivo").on("input", function () {
    clearTimeout(timer);
    timer = setTimeout(cargarDirectivos, 250);
  });
  $("#filtroZonaDirectiva,#filtroAreaResponsable,#filtroTipoProyecto,#filtroPrioridadDirectiva,#filtroEstadoDirectivo,#btnActualizarDirectivo").on(
    "change click",
    cargarDirectivos,
  );

  $("#pdZonaDirectivaId").on("change", function () {
    cargarAreasDirectivas($(this).val(), "");
  });

  $("#tablaDirectivos").on("click", ".btnEditarDirectivo", function () {
    const id = Number($(this).data("id"));
    const row = directivos.find((r) => Number(r.proyecto_directivo_id) === id);
    if (row) llenarModal(row);
  });

  $("#tablaDirectivos").on("click", ".btnOcultarDirectivo", function () {
    const id = Number($(this).data("id"));
    Swal.fire({
      title: "¿Ocultar de Dirección?",
      text: "El milestone seguirá existiendo en HK operativo.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, ocultar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (!result.isConfirmed) return;
      $.post(
        "/hoshin_kanri/app/proyectos_directivos/ocultar.php",
        { proyecto_directivo_id: id },
        function (resp) {
          if (resp.success) cargarDirectivos();
          else Swal.fire("Error", resp.message || "No se pudo ocultar", "error");
        },
        "json",
      );
    });
  });

  $("#btnGuardarDirectivo").on("click", function () {
    $.post(
      "/hoshin_kanri/app/proyectos_directivos/guardar.php",
      {
        proyecto_directivo_id: $("#pdId").val(),
        milestone_id: $("#pdMilestoneId").val(),
        nombre_directivo: $("#pdNombre").val(),
        zona_directiva_id: $("#pdZonaDirectivaId").val(),
        area_directiva_id: $("#pdAreaDirectivaId").val(),
        tipo_proyecto_directivo_id: $("#pdTipoProyectoDirectivoId").val(),
        prioridad_directiva: $("#pdPrioridad").val(),
        estado_directivo: $("#pdEstado").val(),
        requiere_reporte_direccion: $("#pdReporte").is(":checked") ? 1 : 0,
        inversion_estimada: $("#pdInversion").val(),
        presupuesto_aprobado: $("#pdPresupuesto").val(),
        gasto_real: $("#pdGasto").val(),
        beneficio_estimado: $("#pdBeneficioEstimado").val(),
        beneficio_real: $("#pdBeneficioReal").val(),
        fecha_inicio_directiva: $("#pdFechaInicio").val(),
        fecha_fin_objetivo: $("#pdFechaFin").val(),
        notas_directivas: $("#pdNotas").val(),
        motivo_prioridad: $("#pdMotivoPrioridad").val(),
      },
      function (resp) {
        if (!resp.success) {
          Swal.fire("Error", resp.message || "No se pudo guardar", "error");
          return;
        }
        modalDirectivo.hide();
        Swal.fire("Guardado", resp.message, "success");
        cargarDirectivos();
      },
      "json",
    );
  });
});
