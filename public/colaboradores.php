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

  <div class="modal fade" id="modalDetalleColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">

        <!-- HEADER -->
        <div class="modal-header border-0 px-4 py-3">
          <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
              <div class="rounded-circle bg-white border border-3 border-primary d-flex align-items-center justify-content-center"
                style="width:56px;height:56px;">
                <span class="fw-bold text-primary" id="detalleIniciales">--</span>
              </div>
            </div>

            <div>
              <div class="d-flex align-items-center gap-2">
                <h5 class="modal-title fw-bold mb-0" id="detalleNombre"></h5>
                <span class="badge rounded-pill" id="detalleBadgeEstado">--</span>
              </div>
              <div class="text-muted small">
                <i class="fas fa-briefcase me-2"></i><span id="detalleRol"></span>
              </div>
            </div>
          </div>

          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body px-4 pt-3 pb-4">

          <!-- RESUMEN -->
          <div class="row g-3 align-items-stretch mb-3">
            <div class="col-md-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="text-muted small mb-1">Nivel de compromiso</div>
                      <div class="fw-bold fs-3" id="detallePorcentaje">0%</div>
                    </div>
                    <div class="text-muted"><i class="fas fa-chart-line fa-lg"></i></div>
                  </div>

                  <div class="mt-3">
                    <div class="progress" style="height: 10px;">
                      <div class="progress-bar" id="detalleProgress" role="progressbar" style="width:0%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 small text-muted">
                      <span><span id="detalleFinalizadas">0</span> finalizadas</span>
                      <span><span id="detalleTotal">0</span> total</span>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <div class="col-md-8">
              <div class="row g-3 h-100">
                <div class="col-sm-4">
                  <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                      <div class="text-muted small mb-1">Finalizadas</div>
                      <div class="fw-bold fs-3 text-success" id="detalleFinalizadas2">0</div>
                      <div class="small text-muted"><i class="fas fa-check-circle me-1"></i>Completadas</div>
                    </div>
                  </div>
                </div>

                <div class="col-sm-4">
                  <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                      <div class="text-muted small mb-1">Vencidas</div>
                      <div class="fw-bold fs-3 text-danger" id="detalleVencidas">0</div>
                      <div class="small text-muted">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <span id="detalleVencidasMeta">0 abiertas · 0 tarde</span>
                      </div>
                    </div>
                  </div>
                </div>


                <div class="col-sm-4">
                  <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                      <div class="text-muted small mb-1">Vencidas</div>
                      <div class="fw-bold fs-3 text-danger" id="detalleVencidasFueraTiempo">0</div>
                      <div class="small text-muted"><i class="fas fa-exclamation-triangle me-1"></i>Fuera de tiempo</div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- CONTROLES -->
          <div class="d-flex flex-wrap gap-2 align-items-center mb-3">

            <div class="ms-auto small text-muted" id="detalleMeta"></div>
          </div>

          <!-- CONTENIDO -->
          <div class="accordion" id="accordionDetalle"></div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer border-0 px-4 pb-4 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>

      </div>
    </div>
  </div>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../public/js/utils/utils.js"></script>
<script src="../public/js/colaboradores/colaboradores.js"></script>
<?php require_once '../app/layout/footer.php'; ?>
