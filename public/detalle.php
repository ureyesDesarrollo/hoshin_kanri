<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';

$tareaId = (int)($_GET['tarea_id'] ?? 0);
?>

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

                <!-- Botón de acción único y limpio -->
                <div class="mt-2 mt-lg-0">
                    <button class="btn btn-success detalle_btn-complete" id="detalle_btnCompletar">
                        <i class="fas fa-check-circle me-2"></i>Completar tarea
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
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function setDetalleBadge(semaforo, completada, diasAtraso) {
        const $b = $('#detalle_badgeSemaforo');
        const $content = $b.find('.detalle_badge-content');

        $content.empty();

        if (parseInt(completada) === 1) {
            $content.addClass('bg-success text-white')
                .html('<i class="fas fa-check-circle me-2"></i>Finalizada');
            return;
        }

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

        // Forzar fecha local sin UTC
        const [y, m, d] = dateStr.split('-').map(Number);
        const date = new Date(y, m - 1, d);

        return date.toLocaleDateString('es-ES', {
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

            $('#detalle_txtEstrategia').text(t.estrategia);
            $('#detalle_txtEstrategiaDesc').text(t.estrategia_desc || 'Sin descripción adicional');

            $('#detalle_txtMilestone').text(`${t.milestone} (${t.responsable_milestone})`);
            $('#detalle_txtMilestoneDesc').text(t.milestone_desc || 'Sin descripción adicional');

            // Actividad
            $('#detalle_txtCreado').text(t.creado_en ? t.creado_en : '—');
            $('#detalle_txtCompletada').text(t.completada_en ? t.completada_en : 'Pendiente');
            $('#detalle_txtResponsable').text(t.responsable_nombre || 'No asignado');

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

            setDetalleBadge(t.semaforo, t.completada, t.dias_atraso || 0);

        }, 'json').fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                confirmButtonColor: '#4361ee'
            });
        });
    }

    function completarDetalleTarea() {
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas marcar esta tarea como completada?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, marcar como completada',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.post('/hoshin_kanri/app/tareas/marcar_completada.php', {
                    tarea_id: TAREA_ID
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Tarea completada!',
                        text: 'La tarea ha sido marcada como completada',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        loadDetalle();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.value.message || 'No se pudo completar la tarea',
                        confirmButtonColor: '#4361ee'
                    });
                    $('#detalle_btnCompletar').prop('disabled', false);
                }
            }
        });
    }

    $(document).ready(function() {
        loadDetalle();

        $('#detalle_btnCompletar').on('click', completarDetalleTarea);
    });
</script>

<?php require_once '../app/layout/footer.php'; ?>