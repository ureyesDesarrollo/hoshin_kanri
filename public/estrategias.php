<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>

<main class="main-content" id="mainContent">
  <div>
    <!-- Progress Steps -->
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
              <i class="fas fa-chess"></i>
            </div>
            <span class="ms-2 fw-bold">Estrategias</span>
          </div>
        </div>

      </div>
    </div>
    <!-- Título Principal -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center flex-grow-1">
            <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
              <i class="fas fa-chess text-primary fs-4"></i>
            </div>
            <div>
              <h1 class="h3 fw-bold mb-1">Gestión de Estrategias</h1>
              <p class="text-muted mb-0">Administra y monitorea las estrategias</p>
            </div>
          </div>
          <button class="btn btn-primary d-flex align-items-center gap-2 ms-3"
            id="btnNuevoEstrategia">
            <i class="fas fa-plus"></i>
            Nueva Estrategia
          </button>
        </div>
      </div>
    </div>

    <!-- ================= STATS ================= -->
    <div class="row mb-4 fade-in-up">
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
          <div class="stats-icon objectives">
            <i class="fas fa-chess"></i>
          </div>
          <div class="stats-number" id="totalEstrategias">0</div>
          <div class="stats-label">Total Estrategias</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
          <div class="stats-icon strategies">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stats-number" id="activosEstrategiasCount">0</div>
          <div class="stats-label">Activos</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
          <div class="stats-icon tasks">
            <i class="fas fa-flag-checkered"></i>
          </div>
          <div class="stats-number" id="cerradosEstrategiasCount">0</div>
          <div class="stats-label">Cerrados</div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="p-4">
          <div class="row g-2 mb-2">
            <div class="col-md-4">
              <input type="text" id="buscarTituloEstrategia" class="form-control" placeholder="Buscar por título...">
            </div>
            <div class="col-md-4">
              <input type="text" id="buscarResponsableEstrategia" class="form-control" placeholder="Buscar por responsable...">
            </div>
            <div class="col-md-2">
              <button class="btn btn-primary w-100" id="btnLimpiarBusquedaOEstrategia">Limpiar</button>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="tablaEstrategias">
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
              <tr id="loadingEstrategiasRow">
                <td colspan="5" class="text-center py-5">
                  <div class="d-flex flex-column align-items-center justify-content-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                      <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h6 class="text-muted mb-2">Cargando estrategias...</h6>
                    <p class="text-muted small">Por favor, espere un momento</p>
                  </div>
                </td>
              </tr>

              <!-- Estado sin datos (se muestra después de cargar si no hay datos) -->
              <tr id="emptyEstrategiasRow" class="d-none">
                <td colspan="5" class="text-center py-5">
                  <div class="d-flex flex-column align-items-center justify-content-center">
                    <div class="bg-primary-soft rounded-circle p-4 mb-3">
                      <i class="fas fa-chess text-primary fs-3"></i>
                    </div>
                    <h6 class="text-dark mb-2 fw-semibold">No hay estrategias creadas</h6>
                    <p class="text-muted small mb-3">Comienza creando tu primer estrategia</p>
                    <button class="btn btn-primary d-flex align-items-center gap-2" id="btnNuevoEstrategiaTabla">
                      <i class="fas fa-plus"></i>
                      Crear primer estrategia
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
            Mostrando <span class="fw-semibold" id="showingEstrategiasCount">0</span>
            de <span class="fw-semibold" id="totalEstrategiasCount">0</span> estrategias
          </div>

          <ul class="pagination pagination-sm mb-0" id="paginacionEstrategias"></ul>

        </div>
      </div>
    </div>
  </div>
</main>

<!-- ======================================================
     MODAL NUEVO / EDITAR ESTRATEGIA
====================================================== -->
<div class="modal fade" id="modalEstrategia" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0" style="border-radius:16px">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-chess me-2"></i>
          <span id="modalTitulo">Nueva Estrategia</span>
        </h5>
        <button class="btn-close btn-close-white"
          data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">
        <input type="hidden" id="estrId">

        <div class="mb-3">
          <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
          <input type="text" id="estrTitulo"
            class="form-control form-control-lg">
        </div>
        <div class="row">
          <div class="mb-4">
            <label class="form-label fw-semibold mb-3">
              Objetivos relacionados <span class="text-danger">*</span>
            </label>

            <div id="estrObjetivos" class="objetivos-grid"></div>

            <small class="text-muted d-block mt-2">
              Haz clic sobre uno o varios objetivos para asociarlos
            </small>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Responsable <span class="text-danger">*</span></label>
            <select id="estrRespId" class="form-select">
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Prioridad <span class="text-danger">*</span></label>
            <select id="estrPrioridad" class="form-select">
              <option value="1">Puede Esperar</option>
              <option value="2" selected>Importante</option>
              <option value="3">Prioritario</option>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Descripción</label>
          <textarea id="estrDesc" rows="3"
            class="form-control"></textarea>
        </div>

        <div id="estrAlert"
          class="alert alert-danger d-none"></div>
      </div>

      <div class="modal-footer border-0">
        <button class="btn btn-light"
          data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"
          id="btnGuardarEstrategia">
          <i class="fas fa-save me-1"></i> Guardar
        </button>
      </div>

    </div>
  </div>
</div>

<?php
require_once '../app/layout/footer.php';
?>
