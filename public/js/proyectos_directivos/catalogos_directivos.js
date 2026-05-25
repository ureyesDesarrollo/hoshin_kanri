function cargarCatalogosDirectivos() {
  if (!document.getElementById("listaZonasDirectivas")) return;

  $.get(
    "/hoshin_kanri/app/catalogos_directivos/zonas_listar.php",
    function (resp) {
      const zonas = resp.data || [];
      const lista = $("#listaZonasDirectivas");
      const select = $("#catZonaDirectiva");

      lista.empty();
      select.empty();

      zonas.forEach((z) => {
        lista.append(`
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="fas fa-map-marker-alt text-primary me-2"></i>${z.nombre}</span>
            <span class="badge bg-success">Activo</span>
          </div>
        `);
        select.append(`<option value="${z.zona_directiva_id}">${z.nombre}</option>`);
      });
    },
    "json",
  );

  $.get(
    "/hoshin_kanri/app/catalogos_directivos/areas_listar.php",
    function (resp) {
      const areas = resp.data || [];
      const tbody = $("#tablaAreasDirectivas");

      if (!areas.length) {
        tbody.html(
          '<tr><td colspan="3" class="text-center py-4 text-muted">Aún no hay áreas registradas</td></tr>',
        );
        return;
      }

      tbody.html(
        areas
          .map(
            (a) => `
          <tr>
            <td class="ps-4">${a.zona}</td>
            <td class="fw-semibold">${a.nombre}</td>
            <td><span class="badge bg-success">Activo</span></td>
          </tr>
        `,
          )
          .join(""),
      );
    },
    "json",
  );

  $.get(
    "/hoshin_kanri/app/catalogos_directivos/tipos_listar.php",
    function (resp) {
      const tipos = resp.data || [];
      const tbody = $("#tablaTiposDirectivos");

      if (!tipos.length) {
        tbody.html(
          '<tr><td colspan="2" class="text-center py-4 text-muted">Aún no hay tipos registrados</td></tr>',
        );
        return;
      }

      tbody.html(
        tipos
          .map(
            (t) => `
          <tr>
            <td class="ps-4 fw-semibold">${t.nombre}</td>
            <td><span class="badge bg-success">Activo</span></td>
          </tr>
        `,
          )
          .join(""),
      );
    },
    "json",
  );
}

$(document).ready(function () {
  cargarCatalogosDirectivos();

  $("#btnGuardarAreaDirectiva").on("click", function () {
    const zonaId = $("#catZonaDirectiva").val();
    const nombre = $("#catAreaNombre").val().trim();

    if (!zonaId || !nombre) {
      Swal.fire("Faltan datos", "Selecciona zona y captura el área.", "warning");
      return;
    }

    $.post(
      "/hoshin_kanri/app/catalogos_directivos/area_guardar.php",
      { zona_directiva_id: zonaId, nombre },
      function (resp) {
        if (!resp.success) {
          Swal.fire("Error", resp.message || "No se pudo guardar el área", "error");
          return;
        }

        $("#catAreaNombre").val("");
        Swal.fire("Guardado", resp.message, "success");
        cargarCatalogosDirectivos();
      },
      "json",
    );
  });

  $("#btnGuardarTipoDirectivo").on("click", function () {
    const nombre = $("#catTipoNombre").val().trim();

    if (!nombre) {
      Swal.fire("Faltan datos", "Captura el tipo de proyecto.", "warning");
      return;
    }

    $.post(
      "/hoshin_kanri/app/catalogos_directivos/tipo_guardar.php",
      { nombre },
      function (resp) {
        if (!resp.success) {
          Swal.fire("Error", resp.message || "No se pudo guardar el tipo", "error");
          return;
        }

        $("#catTipoNombre").val("");
        Swal.fire("Guardado", resp.message, "success");
        cargarCatalogosDirectivos();
      },
      "json",
    );
  });
});
