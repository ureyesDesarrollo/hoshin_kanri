<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
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
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="ms-2 fw-bold">Colaboradores</span>
                </div>
            </div>

        </div>
    </div>
    <!-- Filtros compactos -->
    <div class="compact-filters mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <select class="form-select form-select" id="filterArea">
                    <option value="">Todas las áreas</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select" id="filterRol">
                    <option value="">Todos los roles</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select" id="sortBy">
                    <option value="porcentaje_desc">Compromiso (↑)</option>
                    <option value="porcentaje_asc">Compromiso (↓)</option>
                    <option value="nombre_asc">Nombre (A-Z)</option>
                    <option value="nombre_desc">Nombre (Z-A)</option>
                    <option value="vencidas_desc">Vencidas (Mayor)</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn btn-outline-primary w-100" id="btnRefresh">
                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                </button>
            </div>
        </div>
    </div>

    <!-- Lista compacta de colaboradores -->
    <div class="kpi-list-compact" id="colaboradoresList">
        <!-- Los datos se cargarán aquí -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-2">Cargando colaboradores...</p>
        </div>
    </div>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../public/js/utils/utils.js"></script>
<script src="../public/js/colaboradores/colaboradores.js"></script>
<?php require_once '../app/layout/footer.php'; ?>