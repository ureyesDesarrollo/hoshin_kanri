<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>

<main class="main-content" id="mainContent">
  <?php if (!puede('mis_tareas')): ?>
    <div class="welcome-card fade-in-up">
      <h1>
        ¡Bienvenido,
        <?= htmlspecialchars(explode(' ', $user['nombre'] ?? 'Usuario')[0]) ?>!
      </h1>
      <p>
        Gestiona y monitorea tu estrategia Hoshin Kanri de manera eficiente.
        Revisa el progreso, asigna tareas y mantén a tu equipo alineado con los
        objetivos estratégicos.
      </p>
    </div>
  <?php endif; ?>

  <div class="row mb-4 fade-in-up" style="animation-delay: 0.2s;">

    <?php if (puede('objetivos')): ?>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
          <div class="stats-icon objectives">
            <i class="fas fa-bullseye"></i>
          </div>
          <div class="stats-number" id="objetivosCount">0</div>
          <div class="stats-label">Objetivos Activos</div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (puede('estrategias')): ?>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
          <div class="stats-icon strategies">
            <i class="fas fa-chess"></i>
          </div>
          <div class="stats-number" id="estrategiasCount">0</div>
          <div class="stats-label">Estrategias</div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (puede('milestones')): ?>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
          <div class="stats-icon milestones">
            <i class="fas fa-flag-checkered"></i>
          </div>
          <div class="stats-number" id="milestonesCount">0</div>
          <div class="stats-label">Milestones</div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (puede('tareas')): ?>
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
          <div class="stats-icon tasks">
            <i class="fas fa-tasks"></i>
          </div>
          <div class="stats-number" id="tareasCount">0</div>
          <div class="stats-label">Tareas Pendientes</div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (puede('mis_tareas')): ?>
      <!-- HERO -->
      <div class="hk-hero fade-in-up">
        <div class="hk-hero-left">
          <div class="hk-hero-title">
            <div class="hk-hero-badge" id="hkHeroBadge">En tiempo</div>
            <h1>
              ¡Bienvenido,
              <?= htmlspecialchars(explode(' ', $user['nombre'] ?? 'Usuario')[0]) ?>!
            </h1>
            <h2 class="mb-1">Tu día, en un vistazo</h2>
            <div class="hk-hero-sub">Prioriza lo urgente y completa tus tareas sin fricción.</div>
          </div>

          <div class="hk-hero-kpis mt-3">
            <div class="hk-kpi-card">
              <div class="hk-kpi-label">Pendientes</div>
              <div class="hk-kpi-num text-warning" id="hkPendientes">0</div>
            </div>
            <div class="hk-kpi-card">
              <div class="hk-kpi-label">Vencidas</div>
              <div class="hk-kpi-num text-danger" id="hkVencidas">0</div>
            </div>
            <div class="hk-kpi-card">
              <div class="hk-kpi-label">Vence hoy</div>
              <div class="hk-kpi-num" id="hkHoy">0</div>
            </div>
            <div class="hk-kpi-card">
              <div class="hk-kpi-label">Progreso</div>
              <div class="hk-kpi-num" id="hkProgreso">0%</div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-light hk-btn-hero" id="btnIrMisTareas2">
              <i class="fas fa-list-check me-2"></i>Ir a Mis tareas
            </button>
          </div>
        </div>

        <div class="hk-hero-right">
          <div class="hk-ring-wrap">
            <div class="hk-ring" id="hkRing">
              <div class="hk-ring-inner">
                <div class="hk-ring-p" id="hkRingP">0%</div>
                <div class="hk-ring-t">Nivel de compromiso</div>
              </div>
            </div>

            <div class="hk-mini-stats">
              <div class="hk-mini">
                <div class="hk-mini-top"><i class="fas fa-check-circle text-success me-2"></i>Finalizadas</div>
                <div class="hk-mini-num" id="hkFinalizadas">0</div>
              </div>
              <div class="hk-mini">
                <div class="hk-mini-top"><i class="fas fa-layer-group text-primary me-2"></i>Total</div>
                <div class="hk-mini-num" id="hkTotal">0</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TIMELINE + PRIORIDADES -->
      <div class="row g-3 mt-2">
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm fade-in-up" style="animation-delay:.1s;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <h5 class="fw-bold mb-0">
                    <i class="fas fa-calendar-alt me-2 bg-primary text-white p-1 rounded"></i>Próximos 7 días
                  </h5>
                  <small class="text-muted">
                    <i class="fas fa-eye me-1"></i>Tus tareas próximas a vencer
                  </small>
                </div>
                <button class="btn btn-outline-primary btn-sm" id="btnRefrescarDashboard2">
                  <i class="fas fa-rotate me-1"></i>Refrescar
                </button>
              </div>

              <div id="hkTimeline" class="hk-timeline">

              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card border-0 shadow-sm fade-in-up" style="animation-delay:.15s;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <h5 class="fw-bold mb-0">
                    <i class="fas fa-bolt me-2 bg-warning text-white p-1 rounded"></i>Prioridades
                  </h5>
                  <small class="text-muted">
                    <i class="fas fa-sort-up me-1"></i>Las más urgentes arriba
                  </small>
                </div>
              </div>

              <div id="hkPrioridades">

              </div>
            </div>
          </div>
        </div>
      </div>

    <?php endif; ?>


  </div>
  <?php if (puede('responsables')): ?>
    <div class="row" id="responsablesContainer"></div>
  <?php endif; ?>

  <div class="modal fade" id="modalDetalleResponsable" tabindex="-1" aria-hidden="true">
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
                      <div class="text-muted small mb-1">Pendientes</div>
                      <div class="fw-bold fs-3 text-warning" id="detallePendientes">0</div>
                      <div class="small text-muted"><i class="fas fa-clock me-1"></i>En curso</div>
                    </div>
                  </div>
                </div>

                <div class="col-sm-4">
                  <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                      <div class="text-muted small mb-1">Vencidas</div>
                      <div class="fw-bold fs-3 text-danger" id="detalleVencidas">0</div>
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

<?php
require_once '../app/layout/footer.php';
