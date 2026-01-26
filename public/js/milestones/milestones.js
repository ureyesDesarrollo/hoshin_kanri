let filtroMilestone = "";
let filtroEstrategia = "";
let filtroResponsable = "";
let searchTimerMile = null;

function loadMilestonesStats() {
  $.get(
    "/hoshin_kanri/app/milestone/stats.php",
    function (resp) {
      if (!resp.success) return;

      $("#totalMilestones").text(resp.data.total);
      $("#activosMilestonesCount").text(resp.data.activos);
      $("#cerradosMilestonesCount").text(resp.data.cerrados);
    },
    "json",
  );
}

function loadMilestones(page = 1) {
  currentPage = page;
  $("#loadingMilestonesRow").removeClass("d-none");
  $("#emptyMilestoneRow").addClass("d-none");

  $.get(
    "/hoshin_kanri/app/milestone/lista.php",
    {
      page: page,
      q: filtroMilestone,
      responsable: filtroResponsable,
    },
    function (resp) {
      const tbody = $("#tablaMilestones tbody");
      tbody.find("tr:not(#loadingMilestonesRow, #emptyMilestoneRow)").remove();
      $("#loadingMilestonesRow").addClass("d-none");

      if (!resp.success || resp.data.length == 0) {
        $("#emptyMilestoneRow").removeClass("d-none");
        $("#showingMilestonesCount").text(0);
        $("#totalMilestonesCount").text(0);
        $("#paginacionMilestones").html("");
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
                        <button class="btn btn-sm btn-outline-primary btnEditarMilestone" data-id="${e.milestone_id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `);
      });

      $("#showingMilestonesCount").text(resp.data.length);
      $("#totalMilestonesCount").text(resp.pagination.total);
      renderPagination(resp.pagination, "paginacionMilestones");
    },
    "json",
  );
}

function loadObjetivosModalMilestone(selected = []) {
  console.log(selected);
  $.get(
    "/hoshin_kanri/app/objetivos/listar.php",
    function (resp) {
      const grid = $("#mileObjetivos");
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

function loadEstrategiasPorObjetivo(objetivoId, estrategiaSeleccionada = 0) {
  const select = $("#milestoneEstrategia");
  select.empty();

  if (!objetivoId) {
    select.append('<option value="">Seleccione un objetivo primero</option>');
    return;
  }

  $.get(
    "/hoshin_kanri/app/estrategias/listar.php",
    { objetivo_id: objetivoId },
    function (resp) {
      if (!resp.success || resp.data.length === 0) {
        select.append('<option value="">No hay estrategias</option>');
        return;
      }

      select.append('<option value="">Seleccionar estrategia</option>');

      resp.data.forEach((e) => {
        select.append(`
                    <option value="${e.estrategia_id}"
                        ${estrategiaSeleccionada == e.estrategia_id ? "selected" : ""}>
                        ${e.titulo}
                    </option>
                `);
      });
    },
    "json",
  );
}

function getSelectedObjetivos() {
  return $("#mileObjetivos .obj-card.active")
    .map(function () {
      return parseInt($(this).data("id"));
    })
    .get();
}

function openEditMilestone(data) {
  var modalMilestone = new bootstrap.Modal(
    document.getElementById("modalMilestone"),
  );
  $("#modalTitulo").text("Actualizar Milestone");
  $("#milestoneId").val(data.milestone_id);
  $("#milestoneTitulo").val(data.titulo);
  $("#milestoneDescripcion").val(data.descripcion);
  $("#milestoneResponsable").val(data.responsable_usuario_id);
  initResponsablesTomSelect("#milestoneResponsable", "#modalMilestone");
  loadResponsables("#milestoneResponsable", data.responsable_usuario_id);

  // 1️⃣ Obtener objetivos desde la estrategia
  $.get(
    "/hoshin_kanri/app/estrategias/listar_por_id.php",
    {
      id: data.estrategia_id,
    },
    function (resp) {
      if (!resp.success) return;

      console.log(resp.data.objetivos);
      const objetivosIds = resp.data.objetivos;

      // 2️⃣ Marcar objetivos (cards)
      loadObjetivosModalMilestone(objetivosIds);

      // 3️⃣ Cargar estrategias por primer objetivo
      if (objetivosIds.length > 0) {
        loadEstrategiasPorObjetivo(objetivosIds[0], data.estrategia_id);
      }
    },
    "json",
  );
  modalMilestone.show();
}

function limpiarCamposMilestone() {
  $("#milestoneId").val("");
  $("#milestoneTitulo").val("");
  $("#milestoneDescripcion").val("");
  $("#milestoneResponsable").val("");
}

$(document).ready(function () {
  let currentPage = 1;
  loadMilestonesStats();
  loadMilestones(currentPage);

  $("#buscarMilestone").on("input", function () {
    filtroMilestone = $(this).val().trim();
    clearTimeout(searchTimerMile);
    searchTimerMile = setTimeout(() => loadMilestones(1), 250);
  });

  $("#buscarResponsableMilestone").on("input", function () {
    filtroResponsable = $(this).val().trim();
    clearTimeout(searchTimerMile);
    searchTimerMile = setTimeout(() => loadMilestones(1), 250);
  });

  $("#btnLimpiarBusquedaMilestones").on("click", function () {
    filtroMilestone = "";
    filtroEstrategia = "";
    filtroResponsable = "";
    $("#buscarMilestone").val("");
    $("#buscarEstrategia").val("");
    $("#buscarResponsableMilestone").val("");
    loadMilestones(1);
  });

  const modalEl = document.getElementById("modalMilestone");

  if (modalEl) {
    var modalMilestone = new bootstrap.Modal(modalEl);
  }

  $("#paginacionMilestones").on("click", "a", function (e) {
    e.preventDefault();
    const page = $(this).data("page");
    if (page) loadMilestones(page);
  });

  $("#mileObjetivos").on("click", ".obj-card", function () {
    const card = $(this);
    const chk = card.find("input");

    const active = !chk.prop("checked");
    chk.prop("checked", active);
    card.toggleClass("active", active);

    $("#mileObjetivos").removeClass("is-invalid");

    const objetivos = getSelectedObjetivos();

    if (objetivos.length === 0) {
      $("#milestoneEstrategia")
        .empty()
        .append('<option value="">Seleccione un objetivo</option>');
      return;
    }

    loadEstrategiasPorObjetivo(objetivos[0]);
  });

  $("#btnNuevoMilestone , #btnNuevoMilestoneTabla").on("click", function () {
    loadObjetivosModalMilestone();
    initResponsablesTomSelect("#milestoneResponsable", "#modalMilestone");
    loadResponsables("#milestoneResponsable", 0);
    modalMilestone.show();
  });

  $("#tablaMilestones").on("click", ".btnEditarMilestone", function () {
    const id = $(this).data("id");
    $.get(
      "/hoshin_kanri/app/milestone/listar_por_id.php",
      { id },
      function (resp) {
        if (!resp.success) {
          Swal.fire("Error", resp.message, "error");
          return;
        }
        const m = resp.data;

        openEditMilestone(m);
      },
    );
  });

  $("#btnGuardarMilestone").on("click", function () {
    $("#mileObjetivos").removeClass("is-invalid");
    const objetivos = getSelectedObjetivos();
    if (objetivos.length === 0) {
      $("#mileObjetivos").addClass("is-invalid");
      return;
    }

    const estrategia_id = $("#milestoneEstrategia").val();
    const titulo = $("#milestoneTitulo").val();
    const responsable_id = $("#milestoneResponsable").val();
    const descripcion = $("#milestoneDescripcion").val();
    const milestone_id = $("#milestoneId").val();

    if (milestone_id) {
      $.ajax({
        type: "POST",
        url: "/hoshin_kanri/app/milestone/actualizar.php",
        data: {
          titulo,
          descripcion,
          responsable_id,
          estrategia_id,
          milestone_id,
        },
        success: function (resp) {
          if (resp.success) {
            swal.fire({
              icon: "success",
              title: "Milestone actualizado",
              text: resp.message,
            });
            modalMilestone.hide();
            limpiarCamposMilestone();
            loadMilestonesStats();
            loadMilestones(currentPage);
          } else {
            swal.fire({
              icon: "error",
              title: "Error",
              text: resp.message,
            });
          }
        },
        error: function () {
          console.log("Error al actualizar milestone");
        },
      });
    } else {
      $.ajax({
        type: "POST",
        url: "/hoshin_kanri/app/milestone/alta.php",
        data: { titulo, descripcion, responsable_id, estrategia_id },
        success: function (resp) {
          if (resp.success) {
            swal.fire({
              icon: "success",
              title: "Milestone guardado",
              text: resp.message,
            });
            modalMilestone.hide();
            limpiarCamposMilestone();
            loadMilestonesStats();
            loadMilestones(currentPage);
          }
        },
        error: function () {
          console.log("Error al guardar milestone");
        },
      });
    }
  });
});
