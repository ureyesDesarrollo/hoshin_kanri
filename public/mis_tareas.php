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
            <i class="fas fa-tasks"></i>
          </div>
          <span class="ms-2 fw-bold">Mis tareas</span>
        </div>
      </div>

    </div>
  </div>
  <div class="welcome-card fade-in-up">
    <h1>Mis tareas</h1>
    <p>Revisa tus actividades, filtra por estado y marca como completadas.</p>
  </div>

  <!-- CONTROLES -->
  <div class="card border-0 shadow-sm mb-3 fade-in-up" style="animation-delay:.1s;">
    <div class="card-body">
      <div class="row g-2 align-items-center">

        <div class="col-lg-4">
          <div class="input-group">
            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
            <input type="text" id="txtBuscar" class="form-control" placeholder="Buscar por título...">
          </div>
        </div>

        <div class="col-lg-3">
          <select id="selFiltro" class="form-select">
            <option value="all">Todas</option>
            <option value="pendientes" selected>Pendientes</option>
            <option value="vencidas">Vencidas</option>
            <option value="hoy">Vence hoy</option>
            <option value="semana">Esta semana</option>
            <option value="finalizadas">Finalizadas</option>
            <option value="revision">En revisión</option>
            <option value="rechazadas">Rechazadas</option>

          </select>
        </div>

        <div class="col-lg-3">
          <select id="selOrden" class="form-select">
            <option value="fecha_fin_asc">Vence primero</option>
            <option value="fecha_fin_desc">Vence al final</option>
            <option value="titulo_asc">Título A-Z</option>
          </select>
        </div>

        <div class="col-lg-2 d-grid">
          <button class="btn btn-primary" id="btnRefrescar">
            <i class="fas fa-rotate me-2"></i>Actualizar
          </button>
        </div>

      </div>

      <!-- CHIPS KPI -->
      <div class="d-flex flex-wrap gap-2 mt-3" id="kpiChips">
        <span class="hk-chip hk-chip-muted">Total: <strong id="kpiTotal">0</strong></span>
        <span class="hk-chip hk-chip-success">Finalizadas: <strong id="kpiFin">0</strong></span>
        <span class="hk-chip hk-chip-muted">Pendientes: <strong id="kpiPend">0</strong></span>
        <span class="hk-chip hk-chip-danger">Vencidas: <strong id="kpiVen">0</strong></span>
      </div>
    </div>
  </div>

  <!-- LISTA -->
  <div class="row g-3" id="contenedorTareas">
    <!-- cards aquí -->
  </div>

  <!-- ESTADOS -->
  <div class="text-center py-5 d-none" id="estadoCargando">
    <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;"></div>
    <div class="text-muted">Cargando tareas...</div>
  </div>

  <div class="text-center py-5 d-none" id="estadoVacio">
    <div class="bg-primary-soft rounded-circle p-4 d-inline-flex mb-3">
      <i class="fas fa-check-circle text-primary fs-3"></i>
    </div>
    <h6 class="fw-semibold mb-1">No hay tareas</h6>
    <div class="text-muted small">Cambia filtros o vuelve más tarde.</div>
  </div>

  <!-- PAGINACIÓN -->
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
      Mostrando <span class="fw-semibold" id="showMisTareasCount">0</span> de
      <span class="fw-semibold" id="totalMisTareasCount">0</span>
    </div>

    <ul class="pagination pagination-sm mb-0" id="paginacionMisTareas"></ul>
  </div>

</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/tareas/mis_tareas.js"></script>

<?php require_once '../app/layout/footer.php'; ?>
