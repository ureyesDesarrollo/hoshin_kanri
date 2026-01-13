<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>

<main class="main-content" id="mainContent">
    <div class="row">
        <div class="col-12">
            <div class="welcome-card fade-in-up">
                <h1>Notificaciones</h1>
                <p>Revisa tareas para aprobar y respuestas de aprobación/rechazo.</p>
            </div>
        </div>
    </div>

    <!-- CONTROLES -->
    <div class="card border-0 shadow-sm mb-3 fade-in-up" style="animation-delay:.05s;">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input type="text" id="txtNotifBuscar" class="form-control" placeholder="Buscar en notificaciones...">
                    </div>
                </div>

                <div class="col-lg-3">
                    <select id="selNotifFiltro" class="form-select">
                        <option value="all">Todas</option>
                        <option value="unread">No leídas</option>
                        <option value="revision">Para revisar</option>
                        <option value="aprobadas">Aprobadas</option>
                        <option value="rechazadas">Rechazadas</option>
                    </select>
                </div>

                <div class="col-lg-3 d-grid">
                    <button class="btn btn-outline-secondary" id="btnNotifMarcarTodas">
                        <i class="fas fa-check-double me-2"></i>Marcar todas como leídas
                    </button>
                </div>

                <div class="col-lg-2 d-grid">
                    <button class="btn btn-primary" id="btnNotifRefrescar">
                        <i class="fas fa-rotate me-2"></i>Actualizar
                    </button>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="hk-chip hk-chip-muted">Total: <strong id="notifTotal">0</strong></span>
                <span class="hk-chip hk-chip-danger">No leídas: <strong id="notifUnread">0</strong></span>
            </div>
        </div>
    </div>

    <!-- LISTA -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div id="notifEstadoCargando" class="p-4 text-center d-none">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div class="text-muted">Cargando notificaciones...</div>
            </div>

            <div id="notifEstadoVacio" class="p-4 text-center d-none">
                <div class="text-muted">No hay notificaciones con ese filtro.</div>
            </div>

            <div id="notifListFull"></div>
        </div>
    </div>

    <!-- PAGINACIÓN -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small">
            Mostrando <span class="fw-semibold" id="notifShowCount">0</span> de
            <span class="fw-semibold" id="notifTotalCount">0</span>
        </div>

        <ul class="pagination pagination-sm mb-0" id="notifPaginacion"></ul>
    </div>
</main>

<style>
    .notif-row {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        cursor: pointer;
    }

    .notif-row:hover {
        background: rgba(0, 0, 0, .02);
    }

    .notif-row.unread {
        background: rgba(13, 110, 253, .06);
    }

    .notif-title {
        font-weight: 700;
        font-size: .95rem;
    }

    .notif-body {
        color: #6c757d;
        font-size: .86rem;
        margin-top: 2px;
    }

    .notif-meta {
        color: #adb5bd;
        font-size: .78rem;
        margin-top: 6px;
    }

    .notif-tag {
        font-size: .75rem;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, .12);
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/hoshin_kanri/public/js/notificaciones/notificaciones_page.js"></script>

<?php require_once '../app/layout/footer.php'; ?>