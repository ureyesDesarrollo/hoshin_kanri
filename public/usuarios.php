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
                    <span class="ms-2 fw-bold">Usuarios</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center flex-grow-1">
                    <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                        <i class="fas fa-users text-primary fs-4"></i>
                    </div>
                    <div>
                        <h1 class="h3 fw-bold mb-1">Gestión de Usuarios</h1>
                        <p class="text-muted mb-0">Administra los usuarios</p>
                    </div>
                </div>
                <button class="btn btn-primary d-flex align-items-center gap-2 ms-3"
                    id="btnNuevoUsuario">
                    <i class="fas fa-plus"></i>
                    Nuevo Usuario
                </button>
            </div>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
                    <thead class="table-light" style="background: linear-gradient(135deg, rgba(0, 110, 199, 0.05), rgba(0, 132, 233, 0.05)) !important;">
                        <tr>
                            <th class="ps-4 py-3 fw-semibold text-dark border-bottom-0" style="border-top-left-radius: 12px;">Nombre</th>
                            <th class="py-3 fw-semibold text-dark border-bottom-0">Correo</th>
                            <th class="py-3 fw-semibold text-dark border-bottom-0">Rol</th>
                            <th class="py-3 fw-semibold text-dark border-bottom-0">Estatus</th>
                            <th class="pe-4 py-3 fw-semibold text-dark border-bottom-0 text-end" style="border-top-right-radius: 12px;" width="140">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Estado de carga -->
                        <tr id="loadingUsuariosRow">
                            <td colspan="5" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <h6 class="text-muted mb-2">Cargando Usuarios...</h6>
                                    <p class="text-muted small">Por favor, espere un momento</p>
                                </div>
                            </td>
                        </tr>

                        <!-- Estado sin datos (se muestra después de cargar si no hay datos) -->
                        <tr id="emptyUsuariosRow" class="d-none">
                            <td colspan="5" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="bg-primary-soft rounded-circle p-4 mb-3">
                                        <i class="fas fa-flag-checkered text-primary fs-3"></i>
                                    </div>
                                    <h6 class="text-dark mb-2 fw-semibold">No hay usuarios creados</h6>
                                    <p class="text-muted small mb-3">Comienza creando tu primer usuario</p>
                                    <button class="btn btn-primary d-flex align-items-center gap-2" id="btnNuevoUsuarioTabla">
                                        <i class="fas fa-plus"></i>
                                        Crear primer usuario
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
                    Mostrando <span class="fw-semibold" id="showingUsuariosCount">0</span>
                    de <span class="fw-semibold" id="totalUsuariosCount">0</span> usuarios
                </div>

                <ul class="pagination pagination-sm mb-0" id="paginacionUsuarios"></ul>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius:16px">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>
                        <span id="modalTitulo">Nuevo Usuario</span>
                    </h5>
                    <button class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="usuario_id" id="usuario_id" value="0">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre_completo" id="nombre_completo" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Correo <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="correo" id="correo" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" name="rol_id" id="rol_id" required>
                                <option value="">Selecciona un rol</option>
                                <option value="1">ADMIN</option>
                                <option value="2">DIRECTOR</option>
                                <option value="3">GERENTE</option>
                                <option value="4">COLABORADOR</option>
                                <option value="5">JEFATURA</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Departamento <span class="text-danger">*</span></label>
                            <select class="form-select" name="area_id" id="area_id" required></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="activo" id="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">Contraseña</div>
                                            <div class="text-muted small" id="helpPassword">
                                                Para nuevo usuario es obligatoria. En edición déjala vacía si no deseas cambiarla.
                                            </div>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary">Seguridad</span>
                                    </div>
                                    <input type="password" class="form-control mt-3" name="password" id="password" placeholder="********">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-3 d-none" id="alertUsuario"></div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarUsuario">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>

            </div>
        </div>
    </div>
</main>

<?php
require_once '../app/layout/footer.php';
?>