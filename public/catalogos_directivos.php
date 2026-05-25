<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>

<main class="main-content" id="mainContent">
  <div>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
            <i class="fas fa-layer-group text-primary fs-4"></i>
          </div>
          <div>
            <h1 class="h3 fw-bold mb-1">Catálogos Directivos</h1>
            <p class="text-muted mb-0">Zonas, áreas y tipos para clasificar proyectos directivos</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-bold">Zonas</div>
          <div class="card-body">
            <div id="listaZonasDirectivas" class="list-group">
              <div class="text-muted">Cargando zonas...</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-7 mb-4">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white fw-bold">Nueva área</div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-5">
                <label class="form-label fw-semibold">Zona</label>
                <select class="form-select" id="catZonaDirectiva"></select>
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold">Área</label>
                <input type="text" class="form-control" id="catAreaNombre" placeholder="Nombre del área">
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" id="btnGuardarAreaDirectiva">
                  <i class="fas fa-save"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-bold">Áreas registradas</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-4">Zona</th>
                    <th>Área</th>
                    <th>Estatus</th>
                  </tr>
                </thead>
                <tbody id="tablaAreasDirectivas">
                  <tr>
                    <td colspan="3" class="text-center py-4 text-muted">Cargando áreas...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
          <div class="card-header bg-white fw-bold">Nuevo tipo de proyecto</div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-9">
                <label class="form-label fw-semibold">Tipo</label>
                <input type="text" class="form-control" id="catTipoNombre" placeholder="Ej. Seguridad, Automatización, Expansión">
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100" id="btnGuardarTipoDirectivo">
                  <i class="fas fa-save"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
          <div class="card-header bg-white fw-bold">Tipos de proyecto</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-4">Tipo</th>
                    <th>Estatus</th>
                  </tr>
                </thead>
                <tbody id="tablaTiposDirectivos">
                  <tr>
                    <td colspan="2" class="text-center py-4 text-muted">Cargando tipos...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once '../app/layout/footer.php'; ?>
