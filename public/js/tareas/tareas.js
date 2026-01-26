let filtroTituloTarea = "";
let filtroResponsableTarea = "";
let searchTimerTareas = null;

function loadTareasStats(periodoId) {
  $.get(
    "/hoshin_kanri/app/tareas/stats.php",
    {
      periodo_id: periodoId,
    },
    function (resp) {
      if (!resp.success) return;

      $("#totalTareas").text(resp.data.total);
      $("#activosTareasCount").text(resp.data.activos);
      $("#cerradosTareasCount").text(resp.data.cerrados);
    },
    "json",
  );
}

function loadMilestonesModal(milestone_id = 0) {
  const estrategiaId = $("#estrategiaId").val();
  $("#tareaMilestoneId").empty();

  if (!estrategiaId) {
    $("#tareaMilestoneId").append(
      '<option value="">Seleccione una estrategia</option>',
    );
    return;
  }

  $.get(
    "/hoshin_kanri/app/milestone/listar.php",
    { estrategia_id: estrategiaId },
    function (resp) {
      if (!resp.success) {
        Swal.fire(
          "Error",
          resp.message || "Error cargando milestones",
          "error",
        );
        return;
      }

      $("#tareaMilestoneId").append(
        '<option value="">Seleccionar milestone</option>',
      );

      resp.data.forEach((m) => {
        $("#tareaMilestoneId").append(
          `<option value="${m.milestone_id}" ${m.milestone_id == milestone_id ? "selected" : ""}>${m.titulo}</option>`,
        );
      });
    },
    "json",
  );
}

let estrategiasCache = []; // lista original
let estrategiaSeleccionada = 0; // id seleccionado

function renderEstrategiasGrid(query = "") {
  const grid = $("#estrategiaTarea");
  grid.empty().removeClass("is-invalid");

  const q = (query || "").trim().toLowerCase();

  const lista = !q
    ? estrategiasCache
    : estrategiasCache.filter(
        (x) =>
          (x.titulo || "").toLowerCase().includes(q) ||
          (x.descripcion || "").toLowerCase().includes(q),
      );

  if (lista.length === 0) {
    grid.html(`
            <div class="text-muted small p-3">
                No se encontraron estrategias.
            </div>
        `);
    return;
  }

  lista.forEach((o) => {
    const id = parseInt(o.estrategia_id);
    const active = id === estrategiaSeleccionada;

    grid.append(`
            <div class="obj-card ${active ? "active" : ""}" data-id="${id}">
                <i class="fas fa-check-circle check"></i>

                <h6>${o.titulo}</h6>
                <p>${o.descripcion || "Sin descripción"}</p>

                <input type="radio" name="estrategia_radio" value="${id}" ${active ? "checked" : ""}>
            </div>
        `);
  });
}

function loadEstrategiaModal(selectedId = 0) {
  estrategiaSeleccionada = parseInt(selectedId) || 0;
  $("#estrategiaId").val(estrategiaSeleccionada || "");

  $("#txtBuscarEstrategia").val("");
  $("#estrategiaTarea").html(
    '<div class="text-muted small p-3">Cargando estrategias...</div>',
  );

  $.get(
    "/hoshin_kanri/app/estrategias/listar_estrategias.php",
    function (resp) {
      if (!resp.success) {
        $("#estrategiaTarea").html(
          '<div class="text-danger p-3">Error cargando estrategias</div>',
        );
        return;
      }

      estrategiasCache = resp.data || [];
      renderEstrategiasGrid("");
    },
    "json",
  );
}

function loadTareas(page = 1) {
  currentPage = page;
  $("#loadingTareasRow").removeClass("d-none");
  $("#emptyTareasRow").addClass("d-none");

  $.get(
    "/hoshin_kanri/app/tareas/lista.php",
    {
      page: page,
      q: filtroTituloTarea,
      responsable: filtroResponsableTarea,
    },
    function (resp) {
      const tbody = $("#tablaTareas tbody");
      tbody.find("tr:not(#loadingTareasRow, #emptyTareasRow)").remove();
      $("#loadingTareasRow").addClass("d-none");
      if (!resp.success || resp.data.length == 0) {
        $("#emptyTareasRow").removeClass("d-none");
        $("#showingTareasCount").text(0);
        $("#totalTareasCount").text(0);
        $("#paginacionTareas").html("");
        return;
      }

      resp.data.forEach((e) => {
        tbody.append(`
                <tr>
                    <td class="ps-4">${e.titulo}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-2">
                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold">
                                    ${getResponsableIniciales(e.responsable)}
                                </div>
                            </div>
                            <div>
                                <div class="small fw-medium">${e.responsable || "Sin asignar"}</div>
                                ${
                                  e.responsable_email
                                    ? `<div class="small text-muted">${e.responsable_email}</div>`
                                    : ""
                                }
                            </div>
                        </div>
                    </td>
                     <td>${badgeCompletada(e.completada)}</td>
                        <td>${formatFecha(e.creado_en)}</td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary btnEditarTarea" data-id="${e.tarea_id}">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                </tr>
                `);
      });

      // Footer info
      $("#showingTareasCount").text(resp.data.length);
      $("#totalTareasCount").text(resp.pagination.total);

      renderPagination(resp.pagination, "paginacionTareas");
    },
    "json",
  );
}

function limpiarCamposTarea() {
  $("#tareaId").val("");
  $("#tareaTitulo").val("");
  $("#tareaResponsable").val("");
  $("#tareaDescripcion").val("");
  $("#tareaMilestoneId").val("");
}

function onEstrategiaChange(nuevaId) {
  estrategiaSeleccionada = parseInt(nuevaId) || 0;
  $("#estrategiaId").val(estrategiaSeleccionada || "");

  // reset milestones
  $("#tareaMilestoneId")
    .empty()
    .append('<option value="">Seleccionar milestone</option>');

  if (!estrategiaSeleccionada) return;

  // cargar milestones de esa estrategia
  loadMilestonesModal(0);
}

$(document).on("click", "#estrategiaTarea .obj-card", function () {
  const id = parseInt($(this).data("id")) || 0;
  if (!id) return;

  // selección única: limpiar activos previos
  $("#estrategiaTarea .obj-card")
    .removeClass("active")
    .find('input[type="radio"]')
    .prop("checked", false);

  // activar la actual
  $(this).addClass("active").find('input[type="radio"]').prop("checked", true);

  // onchange (cargar milestones)
  onEstrategiaChange(id);
});

$(document).on("input", "#txtBuscarEstrategia", function () {
  // búsqueda en tiempo real
  renderEstrategiasGrid($(this).val());
});

$(document).ready(function () {
  const periodoId = $("#periodoId").val();
  loadTareasStats(periodoId);
  loadTareas();

  $("#buscarTituloTarea").on("input", function () {
    filtroTituloTarea = $(this).val().trim();
    clearTimeout(searchTimerTareas);
    searchTimerTareas = setTimeout(() => loadTareas(1), 250);
  });

  $("#buscarResponsableTarea").on("input", function () {
    filtroResponsableTarea = $(this).val().trim();
    clearTimeout(searchTimerTareas);
    searchTimerTareas = setTimeout(() => loadTareas(1), 250);
  });

  $("#btnLimpiarBusquedaTareas").on("click", function () {
    filtroTituloTarea = "";
    filtroResponsableTarea = "";
    $("#buscarTituloTarea").val("");
    $("#buscarResponsableTarea").val("");
    loadTareas(1);
  });

  $("#paginacionTareas").on("click", "a", function (e) {
    e.preventDefault();
    const page = $(this).data("page");
    if (page) loadTareas(page);
  });

  const modalEl = document.getElementById("modalTarea");
  if (modalEl) {
    var modalTarea = new bootstrap.Modal(modalEl);
  }

  $("#btnNuevoTarea, #btnNuevoTareaTabla").on("click", function () {
    modalTarea.show();
    loadResponsables("#tareaResponsable", 0);
    initResponsablesTomSelect("#tareaResponsable", "#modalTarea");

    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2, "0");
    const dd = String(hoy.getDate()).padStart(2, "0");

    const fechaInicio = `${yyyy}-${mm}-${dd}`;

    $("#tareaFechaInicio").val(fechaInicio); // ✅ CORRECTO

    loadEstrategiaModal();
  });

  $("#tablaTareas").on("click", ".btnEditarTarea", function () {
    const id = $(this).data("id");

    $.get(
      "/hoshin_kanri/app/tareas/listar_por_id.php",
      { id },
      function (resp) {
        if (!resp.success) return;

        const t = resp.data;
        $("#tareaId").val(t.tarea_id);
        $("#tareaTitulo").val(t.titulo);
        $("#tareaResponsable").val(t.responsable_usuario_id);
        $("#tareaDescripcion").val(t.descripcion);
        $("#tareaMilestoneId").val(t.milestone_id);
        $("#tareaFechaInicio").attr("readonly", true);
        $("#tareaFechaFin").attr("readonly", true);
        $("#tareaFechaInicio").val(t.fecha_inicio);
        $("#tareaFechaFin").val(t.fecha_fin);
        $("#modalTitulo").text("Actualizar Tarea");
        loadEstrategiaModal(t.estrategia_id);
        onEstrategiaChange(t.estrategia_id);
        loadMilestonesModal(t.milestone_id);
        initResponsablesTomSelect("#tareaResponsable", "#modalTarea");
        loadResponsables("#tareaResponsable", t.responsable_usuario_id);
        modalTarea.show();
      },
    );
  });

  $("#btnGuardarTarea").on("click", function () {
    const tarea_id = $("#tareaId").val();
    const titulo = $("#tareaTitulo").val();
    const responsable_id = $("#tareaResponsable").val();
    const descripcion = $("#tareaDescripcion").val();
    const milestone_id = $("#tareaMilestoneId").val();
    const fecha_inicio = $("#tareaFechaInicio").val();
    const fecha_fin = $("#tareaFechaFin").val();

    if (tarea_id) {
      $.ajax({
        type: "POST",
        url: "/hoshin_kanri/app/tareas/actualizar.php",
        data: {
          tarea_id,
          titulo,
          responsable_id,
          descripcion,
          milestone_id,
          fecha_inicio,
          fecha_fin,
        },
        success: function (resp) {
          if (resp.success) {
            swal.fire({
              icon: "success",
              title: "Tarea actualizada",
              text: resp.message,
            });
            modalTarea.hide();
            limpiarCamposTarea();
            loadTareasStats();
            loadTareas(currentPage);
          } else {
            swal.fire({
              icon: "error",
              title: "Error",
              text: resp.message,
            });
          }
        },
        error: function () {
          console.log("Error al actualizar tarea");
        },
      });
    } else {
      $.ajax({
        type: "POST",
        url: "/hoshin_kanri/app/tareas/alta.php",
        data: {
          titulo,
          responsable_id,
          descripcion,
          milestone_id,
          fecha_inicio,
          fecha_fin,
        },
        success: function (resp) {
          if (resp.success) {
            swal.fire({
              icon: "success",
              title: "Tarea guardada",
              text: resp.message,
            });
            modalTarea.hide();
            limpiarCamposTarea();
            loadTareasStats();
            loadTareas(currentPage);
          } else {
            swal.fire({
              icon: "error",
              title: "Error",
              text: resp.message,
            });
          }
        },
        error: function () {
          console.log("Error al guardar tarea");
        },
      });
    }
  });
});
