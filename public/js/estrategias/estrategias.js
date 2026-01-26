let currentPage = 1;

let filtroTituloEstr = "";
let filtroResponsableEstr = "";
let searchTimerEstr = null;

function loadEstrategiasStats(periodoId) {
  $.get(
    "/hoshin_kanri/app/estrategias/stats.php",
    {
      periodo_id: periodoId,
    },
    function (resp) {
      if (!resp.success) return;

      $("#totalEstrategias").text(resp.data.total);
      $("#activosEstrategiasCount").text(resp.data.activos);
      $("#cerradosEstrategiasCount").text(resp.data.cerrados);
    },
    "json",
  );
}

function loadObjetivosModal(selected = []) {
  $.get(
    "/hoshin_kanri/app/objetivos/listar.php",
    function (resp) {
      const grid = $("#estrObjetivos");
      grid.empty().removeClass("is-invalid");

      if (!resp.success) {
        grid.html('<div class="text-danger">Error cargando objetivos</div>');
        return;
      }

      resp.data.forEach((o) => {
        const id = parseInt(o.objetivo_id);
        const active = selected.includes(id);

        grid.append(`
                <div class="obj-card ${active ? "active" : ""}" data-id="${id}">
                    <i class="fas fa-check-circle check"></i>

                    <h6>${o.titulo}</h6>
                    <p>${o.descripcion || "Sin descripción"}</p>

                    <input type="checkbox" value="${id}" ${active ? "checked" : ""}>
                </div>
            `);
      });
    },
    "json",
  );
}

function loadEstrategias(page = 1) {
  currentPage = page;
  $("#loadingEstrategiasRow").removeClass("d-none");
  $("#emptyEstrategiasRow").addClass("d-none");

  $.get(
    "/hoshin_kanri/app/estrategias/lista.php",
    {
      page: page,
      q: filtroTituloEstr,
      responsable: filtroResponsableEstr,
    },
    function (resp) {
      const tbody = $("#tablaEstrategias tbody");
      tbody
        .find("tr:not(#loadingEstrategiasRow, #emptyEstrategiasRow)")
        .remove();
      $("#loadingEstrategiasRow").addClass("d-none");

      if (!resp.success || resp.data.length == 0) {
        $("#emptyEstrategiasRow").removeClass("d-none");
        $("#showingEstrategiasCount").text(0);
        $("#totalEstrategiasCount").text(0);
        $("#paginacionEstrategias").html("");
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
                                ${e.responsable_email ? `<div class="small text-muted">${e.responsable_email}</div>` : ""}
                            </div>
                        </div>
                    </td>
                    <td>${badgeEstatus(e.estatus)}</td>
                    <td>${formatFecha(e.creado_en)}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary btnEditarEstrategia" data-id="${e.estrategia_id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `);
      });

      $("#showingEstrategiasCount").text(resp.data.length);
      $("#totalEstrategiasCount").text(resp.pagination.total);

      renderPagination(resp.pagination, "paginacionEstrategias");
    },
    "json",
  );
}

function limpiarCamposEstrategia() {
  $("#estrId").val("");
  $("#estrTitulo").val("");
  $("#estrObjetivo").val("");
  $("#estrRespId").val("");
  $("#estrDesc").val("");
  $("#estrPrioridad").val("");
}

function getObjetivosSeleccionados() {
  return $("#estrObjetivos input:checked")
    .map((_, el) => parseInt(el.value))
    .get();
}

$(document).ready(function () {
  const periodoId = $("#periodoId").val();
  currentPage = 1;

  loadEstrategiasStats();
  loadEstrategias(currentPage);
  // Buscar por título
  $("#buscarTituloEstrategia").on("input", function () {
    filtroTituloEstr = $(this).val().trim();
    clearTimeout(searchTimerEstr);
    searchTimerEstr = setTimeout(() => loadEstrategias(1), 250);
  });

  // Buscar por responsable
  $("#buscarResponsableEstrategia").on("input", function () {
    filtroResponsableEstr = $(this).val().trim();
    clearTimeout(searchTimerEstr);
    searchTimerEstr = setTimeout(() => loadEstrategias(1), 250);
  });

  // Limpiar
  $("#btnLimpiarBusquedaTEstrategia").on("click", function () {
    filtroTituloEstr = "";
    filtroResponsableEstr = "";
    $("#buscarTituloEstrategia").val("");
    $("#buscarResponsableTarea").val("");
    loadEstrategias(1);
  });

  // Paginación (ya respeta filtros, porque loadEstrategias usa los globals)
  $("#paginacionEstrategias").on("click", "a", function (e) {
    e.preventDefault();
    const page = $(this).data("page");
    if (page) loadEstrategias(page);
  });

  $("#estrObjetivos").on("click", ".obj-card", function () {
    const card = $(this);
    const chk = card.find("input");

    const active = !chk.prop("checked");
    chk.prop("checked", active);
    card.toggleClass("active", active);

    $("#estrObjetivos").removeClass("is-invalid");
  });

  $("#paginacionEstrategias").on("click", "a", function (e) {
    e.preventDefault();
    const page = $(this).data("page");
    if (page) loadEstrategias(page);
  });

  const modalEl = document.getElementById("modalEstrategia");

  if (modalEl) {
    var modalEstr = new bootstrap.Modal(modalEl);
  }

  $("#btnNuevoEstrategia, #btnNuevoEstrategiaTabla").on("click", function () {
    modalEstr.show();
    loadObjetivosModal();
    initResponsablesTomSelect("#estrRespId", "#modalEstrategia");
    loadResponsables("#estrRespId", 0);
  });

  $("#tablaEstrategias").on("click", ".btnEditarEstrategia", function () {
    const id = $(this).data("id");

    $.get(
      "/hoshin_kanri/app/estrategias/listar_por_id.php",
      { id },
      function (resp) {
        if (!resp.success) {
          Swal.fire("Error", resp.message, "error");
          return;
        }

        const e = resp.data.estrategia;

        /* Campos */
        $("#estrId").val(e.estrategia_id);
        $("#estrTitulo").val(e.titulo);
        $("#estrDesc").val(e.descripcion);
        $("#estrPrioridad").val(e.prioridad);

        /* Responsable */
        initResponsablesTomSelect("#estrRespId", "#modalEstrategia");
        loadResponsables("#estrRespId", e.responsable_usuario_id);

        /* Objetivos (cards) */
        loadObjetivosModal(resp.data.objetivos);

        $("#modalTitulo").text("Actualizar Estrategia");
        modalEstr.show();
      },
      "json",
    );
  });

  $("#btnGuardarEstrategia").on("click", function () {
    const estrategia_id = $("#estrId").val();
    const titulo = $("#estrTitulo").val();
    const responsable_id = $("#estrRespId").val();
    const descripcion = $("#estrDesc").val();
    const prioridad = $("#estrPrioridad").val();
    const objetivos = getObjetivosSeleccionados();

    if (!objetivos.length) {
      $("#estrObjetivos").addClass("is-invalid");

      Swal.fire({
        icon: "warning",
        title: "Selecciona al menos un objetivo",
        text: "Una estrategia debe estar alineada a uno o más objetivos",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    if (estrategia_id) {
      $.ajax({
        type: "POST",
        url: "/hoshin_kanri/app/estrategias/actualizar.php",
        data: {
          titulo,
          responsable_id,
          descripcion,
          prioridad,
          estrategia_id,
          objetivos,
        },
        success: function (resp) {
          if (resp.success) {
            swal.fire({
              icon: "success",
              title: "Estrategia actualizada",
              text: resp.message,
            });
            modalEstr.hide();
            limpiarCamposEstrategia();
            loadEstrategiasStats();
            loadEstrategias(currentPage);
          } else {
            swal.fire({
              icon: "error",
              title: "Error",
              text: resp.message,
            });
          }
        },
        error: function () {
          console.log("Error al actualizar estrategia");
        },
      });
    } else {
      $.ajax({
        type: "POST",
        url: "/hoshin_kanri/app/estrategias/alta.php",
        data: { titulo, responsable_id, descripcion, prioridad, objetivos },
        success: function (resp) {
          if (resp.success) {
            swal.fire({
              icon: "success",
              title: "Estrategia guardado",
              text: resp.message,
            });
            modalEstr.hide();
            limpiarCamposEstrategia();
            loadEstrategiasStats();
            loadEstrategias(currentPage);
          }
        },
        error: function () {
          console.log("Error al guardar estrategia");
        },
      });
    }
  });
});
