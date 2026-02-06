<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';

$tareaId = (int)($_GET['tarea_id'] ?? 0);
?>

<style>
  /* Estilos específicos para detalle */
  .detalle_header-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: 16px;
    border-left: 4px solid #006ec7;
  }

  .detalle_context-path {
    font-size: 0.9rem;
    padding: 0.5rem 0;
  }

  .detalle_path-item {
    padding: 0.25rem 0.75rem;
    background: rgba(67, 97, 238, 0.08);
    border-radius: 20px;
    display: inline-block;
    transition: all 0.2s ease;
  }

  .detalle_path-item:hover {
    background: rgba(67, 97, 238, 0.12);
    transform: translateY(-1px);
  }

  /* Badge mejorado */
  .detalle_status-badge {
    display: inline-flex;
    align-items: center;
  }

  .detalle_badge-content {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  /* Botón simplificado */
  .detalle_btn-complete {
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 180px;
    background: linear-gradient(135deg, #06d6a0, #04b486);
    border: none;
    box-shadow: 0 4px 6px rgba(6, 214, 160, 0.2);
  }

  .detalle_btn-complete:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(6, 214, 160, 0.3);
    background: linear-gradient(135deg, #04b486, #039a72);
  }

  .detalle_btn-complete:disabled {
    background: #6c757d;
    box-shadow: none;
    cursor: not-allowed;
    transform: none;
  }

  .detalle_btn-complete:disabled:hover {
    transform: none;
    box-shadow: 0 4px 6px rgba(6, 214, 160, 0.2);
  }

  .detalle_info-card-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: 12px;
    background: white;
    transition: all 0.2s ease;
  }

  .detalle_info-card-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
  }

  .detalle_info-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
  }

  .detalle_info-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
  }

  .detalle_info-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
  }

  .detalle_info-extra {
    font-size: 0.8rem;
    margin-top: 0.25rem;
  }

  .detalle_info-card {
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: 16px;
    overflow: hidden;
  }

  .detalle_context-card {
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: 16px;
    overflow: hidden;
  }

  .detalle_context-section {
    padding-bottom: 1.25rem;
    border-bottom: 1px solid rgba(0, 0, 0, .05);
  }

  .detalle_context-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  .detalle_context-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
  }

  .detalle_context-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .detalle_context-title {
    font-weight: 600;
    color: #212529;
  }

  .detalle_activity-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .detalle_activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .detalle_activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(0, 0, 0, .04);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .detalle_activity-title {
    font-size: 0.8rem;
    color: #6c757d;
  }

  .detalle_activity-value {
    font-weight: 600;
    color: #212529;
  }

  .detalle_placeholder-content {
    animation: detalle_pulse 1.5s ease-in-out infinite;
  }

  .detalle_placeholder-line {
    height: 12px;
    background: rgba(0, 0, 0, .08);
    border-radius: 4px;
    margin-bottom: 8px;
  }

  @keyframes detalle_pulse {

    0%,
    100% {
      opacity: 1;
    }

    50% {
      opacity: 0.5;
    }
  }

  .detalle_description {
    line-height: 1.6;
    color: #495057;
    white-space: pre-wrap;
  }

  /* Estilos para evidencias mejoradas */
  .evidencia-card {
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: 12px;
    transition: all 0.3s ease;
    background: white;
    overflow: hidden;
  }

  .evidencia-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, .1);
    border-color: #4361ee;
  }

  .evidencia-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
  }

  .evidencia-icon-pdf {
    background: linear-gradient(135deg, #FF6B6B20, #FF6B6B10);
    color: #FF6B6B;
  }

  .evidencia-icon-doc {
    background: linear-gradient(135deg, #4361ee20, #4361ee10);
    color: #4361ee;
  }

  .evidencia-icon-xls {
    background: linear-gradient(135deg, #06d6a020, #06d6a010);
    color: #06d6a0;
  }

  .evidencia-icon-img {
    background: linear-gradient(135deg, #7209b720, #7209b710);
    color: #7209b7;
  }

  .evidencia-icon-zip {
    background: linear-gradient(135deg, #f8961e20, #f8961e10);
    color: #f8961e;
  }

  .evidencia-icon-other {
    background: linear-gradient(135deg, #6c757d20, #6c757d10);
    color: #6c757d;
  }

  .evidencia-badge {
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 500;
  }

  .evidencia-badge-nuevo {
    background: #06d6a020;
    color: #04b486;
    border: 1px solid #06d6a040;
  }

  .evidencia-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  .evidencia-card:hover .evidencia-actions {
    opacity: 1;
  }

  .evidencia-progress {
    height: 4px;
    border-radius: 2px;
    background: rgba(0, 0, 0, .05);
    overflow: hidden;
    margin-top: 8px;
  }

  .evidencia-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #4361ee, #3a56d4);
    border-radius: 2px;
    width: 0%;
    transition: width 0.3s ease;
  }

  /* Animación para subida */
  @keyframes evidenciaPulse {
    0% {
      opacity: 0.5;
    }

    50% {
      opacity: 1;
    }

    100% {
      opacity: 0.5;
    }
  }

  .evidencia-uploading {
    animation: evidenciaPulse 1.5s infinite;
  }

  /* Estilos para botón de subida mejorado */
  #btnSubirEvidencia {
    background: linear-gradient(135deg, #4361ee, #3a56d4);
    border: none;
    padding: 10px 24px;
    transition: all 0.3s ease;
  }

  #btnSubirEvidencia:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(67, 97, 238, 0.3);
  }

  #btnSubirPrimeraEvidencia {
    border: 2px dashed #4361ee;
    background: white;
    color: #4361ee;
    padding: 8px 24px;
  }

  #btnSubirPrimeraEvidencia:hover {
    background: #4361ee;
    color: white;
    border-color: #4361ee;
  }
</style>

<main class="main-content" id="mainContent">
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
            <i class="fas fa-tasks"></i>
          </div>
          <span class="ms-2 fw-bold">Mis tareas</span>
        </div>
      </div>
    </div>
  </div>
  <div class="card detalle_header-card mb-4">
    <div class="card-body p-4">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
        <div class="flex-grow-1">
          <!-- Título con badge inline -->
          <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
            <h1 class="h2 fw-bold mb-0" id="detalle_txtTitulo">
              <i class="fas fa-spinner fa-spin me-2"></i>Cargando...
            </h1>
            <div class="detalle_status-badge" id="detalle_badgeSemaforo">
              <span class="detalle_badge-content">
                <i class="fas fa-circle-notch fa-spin me-1"></i>Cargando
              </span>
            </div>
          </div>

          <!-- Ruta Hoshin -->
          <div class="detalle_context-path text-muted" id="detalle_txtRuta">
            <span class="detalle_path-item"><i class="fas fa-bullseye me-1"></i> <span id="detalle_pathObjetivo">—</span></span>
            <i class="fas fa-chevron-right mx-2"></i>
            <span class="detalle_path-item"><i class="fas fa-chess-knight me-1"></i> <span id="detalle_pathEstrategia">—</span></span>
            <i class="fas fa-chevron-right mx-2"></i>
            <span class="detalle_path-item"><i class="fas fa-flag-checkered me-1"></i> <span id="detalle_pathMilestone">—</span></span>
          </div>
        </div>

        <div class="mt-2 mt-lg-0 d-flex gap-2" id="detalle_acciones">
          <!-- Responsable -->
          <button class="btn btn-success detalle_btn-complete" id="detalle_btnEnviarRevision" style="display:none;">
            <i class="fas fa-paper-plane me-2"></i>Enviar a revisión
          </button>

          <!-- Aprobador -->
          <button class="btn btn-success" id="detalle_btnAprobar" style="display:none; border-radius:10px; font-weight:600;">
            <i class="fas fa-check me-2"></i>Aprobar
          </button>

          <button class="btn btn-danger" id="detalle_btnRechazar" style="display:none; border-radius:10px; font-weight:600;">
            <i class="fas fa-times me-2"></i>Rechazar
          </button>
        </div>


      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Panel principal -->
    <div class="col-lg-8">
      <!-- Panel de información -->
      <div class="card detalle_info-card mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
          <div class="d-flex justify-content-between align-items-center">
            <h3 class="h5 fw-bold mb-0">
              <i class="fas fa-info-circle text-primary me-2"></i>Información
            </h3>
            <div class="text-muted small">
              <i class="fas fa-id-badge me-1"></i>ID: #<?= $tareaId ?>
            </div>
          </div>
        </div>
        <div class="card-body pt-3">
          <div class="mb-4">
            <h5 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
              <i class="fas fa-file-alt me-2"></i>Descripción
            </h5>
            <div class="detalle_description" id="detalle_txtDescripcion">
              <div class="detalle_placeholder-content">
                <div class="detalle_placeholder-line"></div>
                <div class="detalle_placeholder-line" style="width: 80%"></div>
              </div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="detalle_info-card-item">
                <div class="detalle_info-icon bg-info bg-opacity-10">
                  <i class="fas fa-play-circle text-info"></i>
                </div>
                <div class="detalle_info-content">
                  <div class="detalle_info-label">Fecha de inicio</div>
                  <div class="detalle_info-value" id="detalle_txtInicio">—</div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="detalle_info-card-item">
                <div class="detalle_info-icon bg-warning bg-opacity-10">
                  <i class="fas fa-flag-checkered text-warning"></i>
                </div>
                <div class="detalle_info-content">
                  <div class="detalle_info-label">Fecha de vencimiento</div>
                  <div class="detalle_info-value" id="detalle_txtFin">—</div>
                  <div class="detalle_info-extra" id="detalle_txtDiasRestantes"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="card detalle_info-card mb-4" id="detalle_rechazo">
        <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
          <div>
            <h3 class="h5 fw-bold mb-0">
              <i class="fas fa-paperclip text-primary me-2"></i>Rechazo
            </h3>
          </div>
        </div>
        <div class="card-body pt-3">
          <div class="detalle_info-section">
            <div class="detalle_info-label">Motivo de rechazo</div>
            <div class="detalle_info-value" id="detalle_txtMotivoRechazo">—</div>
          </div>
        </div>
      </div>

      <div class="card detalle_info-card mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
          <div>
            <h3 class="h5 fw-bold mb-0">
              <i class="fas fa-comments text-primary me-2"></i>Comentarios
            </h3>
          </div>
        </div>
        <div class="card-body p-4">
          <div class="mb-3">
            <textarea class="form-control" id="comentario_responsable"
              placeholder="Escribe tus comentarios aquí..."></textarea>
          </div>
        </div>
      </div>
      <div class="card detalle_info-card mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
          <div>
            <h3 class="h5 fw-bold mb-0">
              <i class="fas fa-paperclip text-primary me-2"></i>Evidencias
            </h3>
            <div class="text-muted small mt-1">
              <span id="contadorEvidencias">Cargando...</span> archivos adjuntos
            </div>
          </div>

          <button class="btn btn-primary btn-lg rounded-pill px-4" id="btnSubirEvidencia">
            <i class="fas fa-cloud-upload-alt me-2"></i>Subir evidencia
          </button>

          <input type="file" id="inputEvidencia" style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar,.mp4,.mp3" />
        </div>

        <div class="card-body p-4">
          <div class="row g-3" id="evidencias_list">
            <!-- Placeholder mientras carga -->
            <div class="col-12">
              <div class="detalle_placeholder-content d-flex flex-column gap-2">
                <div class="detalle_placeholder-line" style="height: 60px; border-radius: 12px;"></div>
                <div class="detalle_placeholder-line" style="height: 60px; width: 90%; border-radius: 12px;"></div>
              </div>
            </div>
          </div>

          <!-- Estado vacío -->
          <div id="evidenciasVacio" style="display: none;">
            <div class="text-center py-5">
              <div class="mb-4">
                <div class="d-inline-flex p-4 rounded-circle bg-light border border-dashed">
                  <i class="fas fa-cloud-upload-alt fa-3x text-muted"></i>
                </div>
              </div>
              <h5 class="fw-semibold mb-2">Sin evidencias aún</h5>
              <p class="text-muted mb-4">Sube archivos para documentar el progreso de esta tarea</p>
              <button class="btn btn-outline-primary rounded-pill px-4" id="btnSubirPrimeraEvidencia">
                <i class="fas fa-plus me-2"></i>Subir primera evidencia
              </button>
            </div>
          </div>
        </div>

        <div class="card-footer bg-transparent border-top-0 pt-0">
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>Formatos permitidos: PDF, Word, Excel, PowerPoint, Imágenes, ZIP, Video (Máx. 500MB)
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <!-- Panel de contexto Hoshin -->
      <div class="card detalle_context-card mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
          <h3 class="h5 fw-bold mb-0">
            <i class="fas fa-sitemap text-primary me-2"></i>Contexto Hoshin
          </h3>
        </div>
        <div class="card-body pt-3">
          <div class="detalle_context-section">
            <div class="detalle_context-header">
              <div class="detalle_context-icon bg-primary bg-opacity-10">
                <i class="fas fa-bullseye text-primary"></i>
              </div>
              <div>
                <h6 class="fw-bold mb-0">Objetivo</h6>
                <div class="detalle_context-title" id="detalle_txtObjetivo">—</div>
              </div>
            </div>
            <div class="detalle_context-description small text-muted mt-2" id="detalle_txtObjetivoDesc">
              <div class="detalle_placeholder-line" style="width: 100%"></div>
              <div class="detalle_placeholder-line" style="width: 80%"></div>
            </div>
          </div>

          <div class="detalle_context-section mt-4">
            <div class="detalle_context-header">
              <div class="detalle_context-icon bg-success bg-opacity-10">
                <i class="fas fa-chess-knight text-success"></i>
              </div>
              <div>
                <h6 class="fw-bold mb-0">Estrategia</h6>
                <div class="detalle_context-title" id="detalle_txtEstrategia">—</div>
              </div>
            </div>
            <div class="detalle_context-description small text-muted mt-2" id="detalle_txtEstrategiaDesc">
              <div class="detalle_placeholder-line" style="width: 100%"></div>
              <div class="detalle_placeholder-line" style="width: 70%"></div>
            </div>
          </div>

          <div class="detalle_context-section mt-4">
            <div class="detalle_context-header">
              <div class="detalle_context-icon bg-warning bg-opacity-10">
                <i class="fas fa-flag-checkered text-warning"></i>
              </div>
              <div>
                <h6 class="fw-bold mb-0">Milestone</h6>
                <div class="detalle_context-title" id="detalle_txtMilestone">—</div>
              </div>
            </div>
            <div class="detalle_context-description small text-muted mt-2" id="detalle_txtMilestoneDesc">
              <div class="detalle_placeholder-line" style="width: 100%"></div>
              <div class="detalle_placeholder-line" style="width: 90%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de actividad -->
      <div class="card detalle_context-card">
        <div class="card-header bg-transparent border-0 pb-0">
          <h3 class="h5 fw-bold mb-0">
            <i class="fas fa-history text-secondary me-2"></i>Detalles adicionales
          </h3>
        </div>
        <div class="card-body pt-3">
          <div class="detalle_activity-timeline">
            <div class="detalle_activity-item">
              <div class="detalle_activity-icon">
                <i class="fas fa-calendar-alt text-info"></i>
              </div>
              <div class="detalle_activity-content">
                <div class="detalle_activity-title">Creación</div>
                <div class="detalle_activity-value" id="detalle_txtCreado">—</div>
              </div>
            </div>

            <div class="detalle_activity-item">
              <div class="detalle_activity-icon">
                <i class="fas fa-user text-primary"></i>
              </div>
              <div class="detalle_activity-content">
                <div class="detalle_activity-title">Responsable</div>
                <div class="detalle_activity-value" id="detalle_txtResponsable">—</div>
              </div>
            </div>

            <div class="detalle_activity-item">
              <div class="detalle_activity-icon">
                <i class="fas fa-check-circle text-success"></i>
              </div>
              <div class="detalle_activity-content">
                <div class="detalle_activity-title">Completada</div>
                <div class="detalle_activity-value" id="detalle_txtCompletada">—</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</main>

<script>
  const TAREA_ID = <?= (int)$tareaId ?>;
  const USUARIO_ID = <?= (int)($_SESSION['usuario']['usuario_id'] ?? 0) ?>;
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function setDetalleBadge(estatus, completada, semaforo, diasAtraso) {
    const $b = $('#detalle_badgeSemaforo');
    const $content = $b.find('.detalle_badge-content');

    // reset clases cada vez
    $content.removeClass('bg-success bg-danger bg-warning bg-secondary text-white text-dark');
    $content.empty();

    estatus = parseInt(estatus || 1);
    completada = parseInt(completada || 0);

    if (estatus === 4 || completada === 1) {
      $content.addClass('bg-success text-white')
        .html('<i class="fas fa-check-circle me-2"></i>Aprobada');
      return;
    }

    if (estatus === 3) {
      $content.addClass('bg-secondary text-white')
        .html('<i class="fas fa-hourglass-half me-2"></i>En revisión');
      return;
    }

    if (estatus === 5) {
      $content.addClass('bg-danger text-white')
        .html('<i class="fas fa-times-circle me-2"></i>Rechazada');
      return;
    }

    // fallback por semáforo (abierta/en progreso)
    if (semaforo === 'ROJO') {
      $content.addClass('bg-danger text-white')
        .html(`<i class="fas fa-exclamation-triangle me-2"></i>Vencida (${diasAtraso} días)`);
      return;
    }

    if (semaforo === 'HOY') {
      $content.addClass('bg-warning text-dark')
        .html('<i class="fas fa-clock me-2"></i>Vence hoy');
      return;
    }

    $content.addClass('bg-success text-white')
      .html('<i class="fas fa-check-circle me-2"></i>En tiempo');
  }

  function formatDetalleDate(dateStr) {

    if (!dateStr) return '—';

    const [y, m, d] = dateStr.split('-').map(Number);

    const date = new Date(y, m - 1, d);

    return date.toLocaleDateString('es-MX', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  function formatRelativeDate(dateTimeStr) {
    if (!dateTimeStr) return '—';

    // "2026-01-21 16:43:33" → "2026-01-21T16:43:33"
    const date = new Date(dateTimeStr.replace(' ', 'T'));
    const now = new Date();

    const diffMs = now - date;
    const diffSeconds = Math.floor(diffMs / 1000);
    const diffMinutes = Math.floor(diffSeconds / 60);
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffSeconds < 60) return 'Hace unos segundos';
    if (diffMinutes < 60) return `Hace ${diffMinutes} min`;
    if (diffHours < 24) return `Hace ${diffHours} h`;

    if (diffDays === 1) return 'Ayer';
    if (diffDays <= 7) return `Hace ${diffDays} días`;

    // Más de 7 días → fecha completa
    return date.toLocaleDateString('es-MX', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  function calculateDetalleDaysRemaining(endDate) {
    const end = new Date(endDate);
    const now = new Date();
    const diff = end - now;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
  }

  function loadDetalle() {
    $.get('/hoshin_kanri/app/tareas/detalle.php', {
      tarea_id: TAREA_ID
    }, function(resp) {
      if (!resp.success) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: resp.message || 'No se pudo cargar la tarea',
          confirmButtonColor: '#4361ee'
        });
        return;
      }

      const t = resp.data;
      // Título y ruta
      $('#detalle_txtTitulo').html(`<i class="fas fa-tasks me-2"></i>${t.tarea}`);

      $('#detalle_pathObjetivo').text(t.objetivo);
      $('#detalle_pathEstrategia').text(t.estrategia);
      $('#detalle_pathMilestone').text(t.milestone);

      // Descripción
      $('#detalle_txtDescripcion').text(t.descripcion || 'Sin descripción disponible');

      // Fechas
      $('#detalle_txtInicio').text(formatDetalleDate(t.fecha_inicio));
      $('#detalle_txtFin').text(formatDetalleDate(t.fecha_fin));

      // Días restantes
      if (parseInt(t.completada) !== 1) {
        const dias = calculateDetalleDaysRemaining(t.fecha_fin);
        if (dias < 0) {
          $('#detalle_txtDiasRestantes').html(`<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Vencida hace ${Math.abs(dias)} días</span>`);
        } else if (dias === 0) {
          $('#detalle_txtDiasRestantes').html('<span class="text-warning"><i class="fas fa-clock me-1"></i>Vence hoy</span>');
        } else if (dias <= 3) {
          $('#detalle_txtDiasRestantes').html(`<span class="text-warning"><i class="fas fa-clock me-1"></i>${dias} días restantes</span>`);
        } else {
          $('#detalle_txtDiasRestantes').html(`<span class="text-success"><i class="fas fa-calendar-check me-1"></i>${dias} días restantes</span>`);
        }
      } else {
        $('#detalle_txtDiasRestantes').empty();
      }

      // Contexto Hoshin
      $('#detalle_txtObjetivo').text(t.objetivo);
      $('#detalle_txtObjetivoDesc').text(t.objetivo_desc || 'Sin descripción adicional');

      $('#detalle_txtEstrategia').text(`${t.estrategia} (${t.responsable_estrategia})`);
      $('#detalle_txtEstrategiaDesc').text(t.estrategia_desc || 'Sin descripción adicional');

      $('#detalle_txtMilestone').text(`${t.milestone} (${t.responsable_milestone})`);
      $('#detalle_txtMilestoneDesc').text(t.milestone_desc || 'Sin descripción adicional');

      // Actividad
      $('#detalle_txtCreado').text(t.creado_en ? t.creado_en : '—');
      $('#detalle_txtCompletada').text(t.completada_en ? t.completada_en : 'Pendiente');
      $('#detalle_txtResponsable').text(t.responsable_nombre || 'No asignado');
      $('#comentario_responsable').val(t.comentarios_responsable || '');

      // Botón completar
      if (parseInt(t.completada) === 1) {
        $('#detalle_btnCompletar').prop('disabled', true)
          .removeClass('btn-success')
          .addClass('btn-secondary')
          .html('<i class="fas fa-check-circle me-2"></i>Tarea completada');
      } else {
        $('#detalle_btnCompletar').prop('disabled', false)
          .removeClass('btn-secondary')
          .addClass('btn-success');
      }

      // Rechazo
      if (parseInt(t.estatus) === 5) {
        $('#detalle_rechazo').removeClass('d-none');
        $('#detalle_txtMotivoRechazo').text(t.rechazo_motivo || 'Sin motivo de rechazo');
      } else {
        $('#detalle_rechazo').addClass('d-none');
      }

      setDetalleBadge(t.estatus, t.completada, t.semaforo, t.dias_atraso || 0);
      refreshAcciones(t);

    }, 'json').fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo conectar con el servidor',
        confirmButtonColor: '#4361ee'
      });
    });
  }

  function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '';
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
  }

  function getFileIconClass(filename) {
    const ext = filename.split('.').pop().toLowerCase();

    if (ext === 'pdf') return 'evidencia-icon-pdf';
    if (['doc', 'docx'].includes(ext)) return 'evidencia-icon-doc';
    if (['xls', 'xlsx', 'csv'].includes(ext)) return 'evidencia-icon-xls';
    if (['ppt', 'pptx'].includes(ext)) return 'evidencia-icon-ppt';
    if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'].includes(ext)) return 'evidencia-icon-img';
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return 'evidencia-icon-zip';
    if (['mp4', 'mp3'].includes(ext)) return 'evidencia-icon-video';
    return 'evidencia-icon-other';
  }

  function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();

    if (ext === 'pdf') return 'fa-file-pdf';
    if (['doc', 'docx'].includes(ext)) return 'fa-file-word';
    if (['xls', 'xlsx', 'csv'].includes(ext)) return 'fa-file-excel';
    if (['ppt', 'pptx'].includes(ext)) return 'fa-file-powerpoint';
    if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'].includes(ext)) return 'fa-file-image';
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return 'fa-file-archive';
    if (['mp4', 'mp3'].includes(ext)) return 'fa-file-video';
    return 'fa-file-alt';
  }

  function loadEvidencias() {
    $('#evidencias_list').html(`
        <div class="col-12">
            <div class="detalle_placeholder-content d-flex flex-column gap-2">
                <div class="detalle_placeholder-line" style="height: 80px; border-radius: 12px;"></div>
                <div class="detalle_placeholder-line" style="height: 80px; width: 90%; border-radius: 12px;"></div>
            </div>
        </div>
    `);

    $.get('/hoshin_kanri/app/tareas/evidencias_listar.php', {
      tarea_id: TAREA_ID
    }, function(resp) {
      if (!resp.success) {
        $('#evidencias_list').html(`
                <div class="col-12">
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                        <div>${resp.message || 'Error al cargar evidencias'}</div>
                    </div>
                </div>
            `);
        return;
      }

      const items = resp.data || [];
      $('#contadorEvidencias').text(items.length);

      if (items.length === 0) {
        $('#evidencias_list').hide();
        $('#evidenciasVacio').show();
        return;
      }

      $('#evidenciasVacio').hide();
      $('#evidencias_list').show();

      const html = items.map((ev, index) => {
        const size = formatBytes(ev.tamano_bytes);
        const fecha = formatRelativeDate(ev.creado_en);
        const iconClass = getFileIconClass(ev.nombre_original);
        const fileIcon = getFileIcon(ev.nombre_original);
        const downloadUrl = `/hoshin_kanri/app/tareas/evidencias_descargar.php?evidencia_id=${ev.evidencia_id}`;
        const isNew = index === 0 && Date.now() - new Date(ev.creado_en.replace(' ', 'T')).getTime() < 24 * 60 * 60 * 1000;

        return `
                <div class="col-12">
                    <div class="evidencia-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="evidencia-icon ${iconClass} me-3">
                                <i class="fas ${fileIcon}"></i>
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-semibold mb-1 text-truncate" style="max-width: 300px;">
                                            ${ev.nombre_original}
                                        </h6>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="text-muted small">
                                                <i class="fas fa-hdd me-1"></i>${size}
                                            </span>
                                            <span class="text-muted small">
                                                <i class="far fa-clock me-1"></i>${fecha}
                                            </span>
                                            ${ev.subido_por ? `
                                            <span class="text-muted small">
                                                <i class="fas fa-user me-1"></i>${ev.subido_por}
                                            </span>
                                            ` : ''}
                                        </div>
                                    </div>

                                    <div class="evidencia-actions">
                                        <div class="d-flex gap-2">
                                            ${isNew ? `
                                            <span class="evidencia-badge evidencia-badge-nuevo">
                                                <i class="fas fa-star me-1"></i>Nuevo
                                            </span>
                                            ` : ''}
                                            <button class="btn btn-outline-danger btn-sm btnDelEv" data-id="${ev.evidencia_id}">
                                                <i class="fas fa-trash me-1"></i>Eliminar
                                            </button>
                                            <a class="btn btn-outline-primary btn-sm rounded-pill px-3"
                                               href="${downloadUrl}"
                                               title="Descargar">
                                                <i class="fas fa-download"></i> Descargar
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                ${ev.descripcion ? `
                                <div class="mt-2 small text-muted">
                                    <i class="fas fa-align-left me-1"></i>
                                    ${ev.descripcion}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
      }).join('');

      $('#evidencias_list').html(html);

    }, 'json').fail(function() {
      $('#evidencias_list').html(`
            <div class="col-12">
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fas fa-wifi-slash me-3 fa-lg"></i>
                    <div>Error de conexión al cargar evidencias</div>
                </div>
            </div>
        `);
    });
  }

  function subirEvidencia(file) {
    const formData = new FormData();
    formData.append('tarea_id', TAREA_ID);
    formData.append('file', file);

    Swal.fire({
      title: 'Subiendo evidencia...',
      text: 'Por favor espera',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    $.ajax({
      url: '/hoshin_kanri/app/tareas/evidencias_subir.php',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json'
    }).done(function(resp) {
      if (!resp.success) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: resp.message || 'No se pudo subir'
        });
        return;
      }
      Swal.fire({
        icon: 'success',
        title: 'Listo',
        text: 'Evidencia subida'
      });
      loadEvidencias();
    }).fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error de conexión al subir evidencia'
      });
    });
  }

  function refreshAcciones(t) {
    const estatus = parseInt(t.estatus || 1);
    const completada = parseInt(t.completada || 0);

    const esResponsable = parseInt(t.responsable_usuario_id || 0) === parseInt(USUARIO_ID || 0);

    // Oculta todo
    $('#detalle_btnEnviarRevision').hide();
    $('#detalle_btnAprobar').hide();
    $('#detalle_btnRechazar').hide();

    // Si ya finalizada/aprobada -> sin acciones
    if (estatus === 4 || completada === 1) return;

    // Responsable: puede enviar a revisión si está abierta (1/2) o rechazada (5)
    if (esResponsable && (estatus === 1 || estatus === 2 || estatus === 5)) {
      $('#detalle_btnEnviarRevision').show();
      return;
    }

    // Aprobador: puede aprobar/rechazar si está en revisión (3)
    if (estatus === 3) {
      $.get('/hoshin_kanri/app/tareas/puede_aprobar.php', {
        tarea_id: TAREA_ID
      }, function(resp) {
        if (resp && resp.success && resp.data && parseInt(resp.data.puede) === 1) {
          $('#detalle_btnAprobar').show();
          $('#detalle_btnRechazar').show();
        }
      }, 'json');
    }
  }

  function enviarRevision() {
    Swal.fire({
      title: 'Enviar a revisión',
      text: 'Se notificará al aprobador correspondiente.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, enviar',
      cancelButtonText: 'Cancelar',
      showLoaderOnConfirm: true,
      preConfirm: () => {
        return $.ajax({
          url: '/hoshin_kanri/app/tareas/marcar_completada.php',
          method: 'POST',
          dataType: 'json',
          data: {
            tarea_id: TAREA_ID
          }
        }).catch(() => {
          Swal.showValidationMessage('Error de conexión');
        });
      }
    }).then((r) => {
      if (!r.isConfirmed) return;

      const resp = r.value;
      if (resp && resp.success) {
        Swal.fire({
            icon: 'success',
            title: 'Enviada',
            text: 'La tarea fue enviada a revisión.'
          })
          .then(() => loadDetalle());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: (resp && resp.message) ? resp.message : 'No se pudo enviar a revisión'
        });
      }
    });
  }

  function aprobarTarea() {
    Swal.fire({
      title: 'Aprobar tarea',
      text: '¿Confirmas la aprobación?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, aprobar',
      cancelButtonText: 'Cancelar',
      showLoaderOnConfirm: true,
      preConfirm: () => {
        return $.ajax({
          url: '/hoshin_kanri/app/tareas/aprobar.php',
          method: 'POST',
          dataType: 'json',
          data: {
            tarea_id: TAREA_ID
          }
        }).catch(() => {
          Swal.showValidationMessage('Error de conexión');
        });
      }
    }).then((r) => {
      if (!r.isConfirmed) return;

      const resp = r.value;
      if (resp && resp.success) {
        // oculta botones inmediatamente (UX)
        $('#detalle_btnAprobar').hide();
        $('#detalle_btnRechazar').hide();

        Swal.fire({
            icon: 'success',
            title: 'Aprobada',
            text: 'La tarea fue aprobada.'
          })
          .then(() => loadDetalle());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: (resp && resp.message) ? resp.message : 'No se pudo aprobar'
        });
      }
    });
  }


  function rechazarTarea() {
    Swal.fire({
      title: 'Rechazar tarea',
      input: 'textarea',
      inputLabel: 'Motivo del rechazo',
      inputPlaceholder: 'Escribe el motivo...',
      showCancelButton: true,
      confirmButtonText: 'Rechazar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33',
      showLoaderOnConfirm: true,
      preConfirm: (motivo) => {
        motivo = (motivo || '').trim();
        if (motivo.length < 3) {
          Swal.showValidationMessage('El motivo es requerido (mínimo 3 caracteres).');
          return false;
        }
        return $.ajax({
          url: '/hoshin_kanri/app/tareas/rechazar.php',
          method: 'POST',
          dataType: 'json',
          data: {
            tarea_id: TAREA_ID,
            motivo
          }
        }).catch(() => {
          Swal.showValidationMessage('Error de conexión');
        });
      }
    }).then((r) => {
      if (!r.isConfirmed) return;

      const resp = r.value;
      if (resp && resp.success) {
        // oculta botones inmediatamente (UX)
        $('#detalle_btnAprobar').hide();
        $('#detalle_btnRechazar').hide();

        Swal.fire({
            icon: 'success',
            title: 'Rechazada',
            text: 'La tarea fue rechazada.'
          })
          .then(() => loadDetalle());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: (resp && resp.message) ? resp.message : 'No se pudo rechazar'
        });
      }
    });
  }



  $(document).ready(function() {
    loadDetalle();
    loadEvidencias();

    $('#detalle_btnEnviarRevision').on('click', function() {
      if ($('#comentario_responsable').val().trim() !== '') {
        $.post('/hoshin_kanri/app/tareas/crear_comentarios.php', {
          tarea_id: TAREA_ID,
          comentario: $('#comentario_responsable').val().trim()
        });
      }
      enviarRevision();
    });

    $('#detalle_btnAprobar').on('click', aprobarTarea);
    $('#detalle_btnRechazar').on('click', rechazarTarea);


    $('#btnSubirPrimeraEvidencia').on('click', function() {
      $('#inputEvidencia').click();
    });

    $('#btnSubirEvidencia').on('click', function() {
      $('#inputEvidencia').val('');
      $('#inputEvidencia').click();
    });

    $('#inputEvidencia').on('change', function() {
      const file = this.files && this.files[0] ? this.files[0] : null;
      if (!file) return;

      subirEvidencia(file);
    });

    $(document).on('click', '.btnDelEv', function() {
      const id = $(this).data('id');

      Swal.fire({
        title: 'Eliminar evidencia',
        text: 'Esto borrará el archivo de la NAS. ¿Continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then(r => {
        if (!r.isConfirmed) return;

        $.post('/hoshin_kanri/app/tareas/evidencias_eliminar.php', {
          evidencia_id: id
        }, function(resp) {
          if (!resp.success) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: resp.message || 'No se pudo eliminar'
            });
            return;
          }
          Swal.fire({
            icon: 'success',
            title: 'Eliminada',
            text: 'Se borró de la NAS y del sistema.'
          });
          loadEvidencias();
        }, 'json').fail(function() {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error de conexión al eliminar'
          });
        });
      });
    });

  });
</script>

<?php require_once '../app/layout/footer.php'; ?>
