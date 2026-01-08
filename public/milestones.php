<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>

<main class="main-content" id="mainContent">
    <div>
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
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                        <span class="ms-2 fw-bold">Milestones</span>
                    </div>
                </div>

            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                            <i class="fas fa-flag-checkered text-primary fs-4"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">Gestión de Milestones</h1>
                            <p class="text-muted mb-0">Administra y monitorea los milestones</p>
                        </div>
                    </div>
                    <button class="btn btn-primary d-flex align-items-center gap-2 ms-3"
                        id="btnNuevoMilestone">
                        <i class="fas fa-plus"></i>
                        Nuevo Milestone
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= STATS ================= -->
        <div class="row mb-4 fade-in-up">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon objectives">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div class="stats-number" id="totalMilestones">0</div>
                    <div class="stats-label">Total Milestones</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon strategies">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number" id="activosMilestonesCount">0</div>
                    <div class="stats-label">Activos</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon tasks">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div class="stats-number" id="cerradosMilestonesCount">0</div>
                    <div class="stats-label">Cerrados</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaMilestones">
                        <thead class="table-light" style="background: linear-gradient(135deg, rgba(0, 110, 199, 0.05), rgba(0, 132, 233, 0.05)) !important;">
                            <tr>
                                <th class="ps-4 py-3 fw-semibold text-dark border-bottom-0" style="border-top-left-radius: 12px;">Título</th>
                                <th class="py-3 fw-semibold text-dark border-bottom-0">Responsable</th>
                                <th class="py-3 fw-semibold text-dark border-bottom-0">Estatus</th>
                                <th class="py-3 fw-semibold text-dark border-bottom-0">Creado</th>
                                <th class="pe-4 py-3 fw-semibold text-dark border-bottom-0 text-end" style="border-top-right-radius: 12px;" width="140">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Estado de carga -->
                            <tr id="loadingMilestonesRow">
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                        <h6 class="text-muted mb-2">Cargando milestones...</h6>
                                        <p class="text-muted small">Por favor, espere un momento</p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Estado sin datos (se muestra después de cargar si no hay datos) -->
                            <tr id="emptyMilestoneRow" class="d-none">
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="bg-primary-soft rounded-circle p-4 mb-3">
                                            <i class="fas fa-flag-checkered text-primary fs-3"></i>
                                        </div>
                                        <h6 class="text-dark mb-2 fw-semibold">No hay milestones creados</h6>
                                        <p class="text-muted small mb-3">Comienza creando tu primer milestone</p>
                                        <button class="btn btn-primary d-flex align-items-center gap-2" id="btnNuevoMilestoneTabla">
                                            <i class="fas fa-plus"></i>
                                            Crear primer milestone
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer de la tabla (paginación, etc.) -->
                <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando <span class="fw-semibold" id="showingMilestonesCount">0</span>
                        de <span class="fw-semibold" id="totalMilestonesCount">0</span> milestones
                    </div>

                    <ul class="pagination pagination-sm mb-0" id="paginacionMilestones"></ul>

                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalMilestone" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:16px">

            <!-- HEADER -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-flag-checkered me-2"></i>
                    <span id="modalTitulo">Nuevo Milestone</span>
                </h5>
                <button class="btn-close btn-close-white"
                    data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body p-4">

                <!-- IDS OCULTOS -->
                <input type="hidden" id="milestoneId">

                <!-- TITULO -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Título <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        id="milestoneTitulo"
                        class="form-control form-control-lg">
                </div>

                <div class="row">
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">
                            Objetivos relacionados <span class="text-danger">*</span>
                        </label>

                        <div id="mileObjetivos" class="objetivos-grid"></div>
                    </div>
                </div>

                <!-- ESTRATEGIA -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Estrategia <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-bullseye"></i>
                        </span>
                        <select id="milestoneEstrategia"
                            class="form-select">
                            <option value="">Seleccionar estrategia</option>
                        </select>
                    </div>

                    <small class="text-muted">
                        El milestone quedará asociado a esta estrategia
                    </small>
                </div>


                <!-- RESPONSABLE -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Responsable <span class="text-danger">*</span>
                    </label>
                    <select id="milestoneResponsable"
                        class="form-select">
                    </select>
                </div>

                <!-- DESCRIPCIÓN -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Descripción
                    </label>
                    <textarea id="milestoneDescripcion"
                        rows="3"
                        class="form-control"></textarea>
                </div>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-0">
                <button class="btn btn-light"
                    data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button class="btn btn-primary"
                    id="btnGuardarMilestone">
                    <i class="fas fa-save me-1"></i>
                    Guardar Milestone
                </button>
            </div>

        </div>
    </div>
</div>


<?php
require_once '../app/layout/footer.php';
?>