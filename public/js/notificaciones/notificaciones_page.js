let notifPage = 1;

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text ?? "";
  return div.innerHTML;
}

function timeAgo(dateStr) {
  if (!dateStr) return "";
  const d = new Date(String(dateStr).replace(" ", "T"));
  if (isNaN(d.getTime())) return "";
  const diff = Math.floor((Date.now() - d.getTime()) / 1000);
  if (diff < 60) return "Hace unos segundos";
  const m = Math.floor(diff / 60);
  if (m < 60) return `Hace ${m} min`;
  const h = Math.floor(m / 60);
  if (h < 24) return `Hace ${h} h`;
  const days = Math.floor(h / 24);
  return `Hace ${days} d`;
}

function tagByType(tipo) {
  console.log("Tipo de notificación:", tipo);
  if (tipo === "tarea_en_revision")
    return `<span class="notif-tag text-warning border-warning"><i class="fas fa-clipboard-check me-1"></i>Revisión</span>`;
  if (tipo === "tarea_aprobada")
    return `<span class="notif-tag text-success border-success"><i class="fas fa-check-circle me-1"></i>Aprobada</span>`;
  if (tipo === "tarea_rechazada")
    return `<span class="notif-tag text-danger border-danger"><i class="fas fa-times-circle me-1"></i>Rechazada</span>`;
  return `<span class="notif-tag text-secondary"><i class="fas fa-bell me-1"></i>General</span>`;
}

function redirectUrl(n) {
  if (n.entidad_tipo === "tarea" && n.entidad_id) {
    return "/hoshin_kanri/public/detalle.php?tarea_id=" + n.entidad_id;
  }
  return "/hoshin_kanri/public/notificaciones.php";
}

function renderNotifRow(n) {
  const unread = parseInt(n.leida || 0) === 0;

  const title = escapeHtml(n.titulo || "Notificación");
  const body = n.cuerpo ? escapeHtml(n.cuerpo) : "";

  const ctxParts = [];
  if (n.tarea_titulo)
    ctxParts.push(
      `<i class="fas fa-tasks me-1"></i>${escapeHtml(n.tarea_titulo)}`,
    );
  if (n.milestone_titulo)
    ctxParts.push(
      `<span class="ms-2"><i class="fas fa-flag-checkered me-1"></i>${escapeHtml(n.milestone_titulo)}</span>`,
    );
  if (n.estrategia_titulo)
    ctxParts.push(
      `<span class="ms-2"><i class="fas fa-chess-knight me-1"></i>${escapeHtml(n.estrategia_titulo)}</span>`,
    );

  const ctx = ctxParts.length ? ctxParts.join("") : "";
  const resp = n.tarea_responsable
    ? `<div class="notif-body"><i class="fas fa-user me-1"></i>${escapeHtml(n.tarea_responsable)}</div>`
    : "";

  return `
    <div class="notif-row ${unread ? "unread" : ""}" data-id="${n.notificacion_id}" data-url="${redirectUrl(n)}">
      <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="flex-grow-1">
          <div class="notif-title">${title}</div>
          ${body ? `<div class="notif-body">${body}</div>` : ""}
          ${ctx ? `<div class="notif-body">${ctx}</div>` : ""}
          ${resp}
          <div class="notif-meta">${timeAgo(n.creada_en)}</div>
        </div>
        <div class="text-end">
          ${tagByType(n.tipo)}
        </div>
      </div>
    </div>
  `;
}

function renderPaginationNotifi(meta) {
  const ul = $("#notifPaginacion");

  if (!meta || Number(meta.total_pages) <= 1) {
    ul.empty();
    return;
  }

  ul.empty();

  const page = Number(meta.page);
  const totalPages = Number(meta.total_pages);

  ul.append(`
      <li class="page-item ${page === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${page - 1}">&laquo;</a>
      </li>
    `);

  for (let i = 1; i <= totalPages; i++) {
    ul.append(`
          <li class="page-item ${i === page ? "active" : ""}">
            <a class="page-link border-0 ${i === page ? "bg-primary text-white" : ""}"
               href="#" data-page="${i}">
                ${i}
            </a>
        </li>
        `);
  }

  ul.append(`
      <li class="page-item ${page === totalPages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${page + 1}">&raquo;</a>
      </li>
    `);
}

function loadNotificaciones(page = 1) {
  notifPage = page;

  $("#notifEstadoCargando").removeClass("d-none");
  $("#notifEstadoVacio").addClass("d-none");
  $("#notifListFull").empty();

  const f = $("#selNotifFiltro").val();
  const q = $("#txtNotifBuscar").val().trim();

  $.get(
    "/hoshin_kanri/app/notificaciones/lista.php",
    { page, limit: 15, f, q },
    function (resp) {
      $("#notifEstadoCargando").addClass("d-none");

      if (!resp || !resp.success) {
        $("#notifEstadoVacio")
          .removeClass("d-none")
          .text("Error al cargar notificaciones");
        return;
      }

      const items = resp.data || [];
      const meta = resp.meta || {};

      $("#notifTotal").text(meta.total ?? 0);
      $("#notifUnread").text(meta.unread_count ?? 0);

      $("#notifShowCount").text(items.length);
      $("#notifTotalCount").text(meta.total ?? 0);

      if (!items.length) {
        $("#notifEstadoVacio").removeClass("d-none");
        return;
      }

      $("#notifListFull").html(items.map(renderNotifRow).join(""));
      renderPaginationNotifi(meta);
    },
    "json",
  ).fail(function () {
    $("#notifEstadoCargando").addClass("d-none");
    $("#notifEstadoVacio").removeClass("d-none").text("Error de conexión");
  });
}

function marcarLeida(notificacionId, cb) {
  $.post(
    "/hoshin_kanri/app/notificaciones/marcar_leida.php",
    { notificacion_id: notificacionId },
    function () {
      if (typeof cb === "function") cb();
    },
    "json",
  ).fail(function () {
    if (typeof cb === "function") cb();
  });
}

$(document).ready(function () {
  loadNotificaciones(1);

  $("#btnNotifRefrescar").on("click", function () {
    loadNotificaciones(notifPage);
  });

  $("#selNotifFiltro").on("change", function () {
    loadNotificaciones(1);
  });

  let t = null;
  $("#txtNotifBuscar").on("input", function () {
    clearTimeout(t);
    t = setTimeout(() => loadNotificaciones(1), 350);
  });

  $(document).on("click", "#notifPaginacion .page-link", function (e) {
    e.preventDefault();
    const p = parseInt($(this).data("page"), 10);
    if (!p || p < 1) return;
    loadNotificaciones(p);
  });

  $(document).on("click", ".notif-row", function () {
    const id = $(this).data("id");
    const url = $(this).data("url");

    marcarLeida(id, function () {
      window.location.href = url;
    });
  });

  $("#btnNotifMarcarTodas").on("click", function () {
    $.post(
      "/hoshin_kanri/app/notificaciones/marcar_todas.php",
      {},
      function (resp) {
        if (resp && resp.success) {
          Swal.fire({
            icon: "success",
            title: "Listo",
            text: "Se marcaron como leídas.",
          });
          loadNotificaciones(1);
        }
      },
      "json",
    );
  });
});
