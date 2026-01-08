function loadResponsables(selectId, responsableId = 0) {
    const el = document.querySelector(selectId);
    if (!el) return;

    fetch('/hoshin_kanri/app/responsables/responsables.php')
        .then(r => r.json())
        .then(resp => {
            if (!resp.success) return;

            // limpiar
            el.innerHTML = '<option value=""></option>';

            resp.data.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.usuario_id;
                opt.textContent = r.nombre_completo;
                el.appendChild(opt);
            });

            if (el.tomselect) {
                el.tomselect.sync();
                el.tomselect.setValue(responsableId ? String(responsableId) : '');
            }
        });
}

function initResponsablesTomSelect(selectId) {
    const el = document.querySelector(selectId);
    if (!el || el.tomselect) return;

    new TomSelect(el, {
        placeholder: 'Buscar responsable...',
        allowEmptyOption: true,
        create: false,
        render: {
            no_results: function (data, escape) {
                return `<div class="no-results">Sin resultados para ${escape(data.input)}</div>`;
            },
        },
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });
}




function loadUsuarios(page = 1) {
    currentPage = page;

    $('#loadingUsuariosRow').removeClass('d-none');
    $('#emptyUsuariosRow').addClass('d-none');

    $.get('/hoshin_kanri/app/responsables/lista.php', {
        page: page
    }, function (resp) {

        const tbody = $('#tablaUsuarios tbody');
        tbody.find('tr:not(#loadingUsuariosRow, #emptyUsuariosRow)').remove();
        $('#loadingUsuariosRow').addClass('d-none');

        if (!resp.success || resp.data.length === 0) {
            $('#emptyUsuariosRow').removeClass('d-none');
            $('#showingUsuariosCount').text(0);
            $('#totalUsuariosCount').text(0);
            $('#paginacionUsuarios').html('');
            return;
        }

        resp.data.forEach(u => {
            tbody.append(`
                    <tr>
                        <td class="ps-4">${u.nombre_completo}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="ps-4">
                                        ${u.correo}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><div class="small fw-medium">${u.rol}</div></td>
                        <td>${badgeEstatus(u.usuario_activo)}</td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary btnEditarObjetivo" data-id="${u.usuario_id}">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                `);
        });

        // Footer info
        $('#showingUsuariosCount').text(resp.data.length);
        $('#totalUsuariosCount').text(resp.pagination.total);

        renderPagination(resp.pagination, 'paginacionUsuarios');
    }, 'json');
}

function loadAreas(selectId) {
    $.get('/hoshin_kanri/app/area/listar.php', function (resp) {
        if (!resp.success || resp.data.length === 0) {
            $('#area_id').html('<option value="">Selecciona un área</option>');
            return;
        }
        const select = $('#area_id');
        select.html('<option value="">Selecciona un área</option>');
        resp.data.forEach(a => {

            select.append(`<option value="${a.area_id}" ${a.area_id === selectId ? 'selected' : ''}>${a.nombre}</option>`);
        });
    }, 'json');
}

function limpiarFormulario() {
    $('#usuario_id').val('');
    $('#nombre_completo').val('');
    $('#correo').val('');
    $('#rol_id').val('');
    $('#password').val('');
}

$(document).ready(function () {
    loadUsuarios();
    $('#paginacionUsuarios').on('click', 'a.page-link', function (e) {
        e.preventDefault();
        const page = Number($(this).data('page'));
        if (!page || $(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) return;
        loadUsuarios(page);
    });


    const modalEl = document.getElementById('modalUsuario');

    if (modalEl) {
        var modalUsu = new bootstrap.Modal(modalEl);
    }

    $('#btnNuevoUsuario, #btnNuevoUsuarioTabla').on('click', function () {
        modalUsu.show();
        loadAreas();
    });

    $('#tablaUsuarios').on('click', '.btnEditarObjetivo', function () {
        const usuarioId = $(this).data('id');
        $('#modalTitulo').text('Editar usuario');
        $.get('/hoshin_kanri/app/responsables/listar_por_id.php', {
            id: usuarioId
        }, function (resp) {
            if (!resp.success || resp.data.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontró el usuario.'
                });
                return;
            }
            const u = resp.data;
            $('#usuario_id').val(u.usuario_id);
            $('#nombre_completo').val(u.nombre_completo);
            $('#correo').val(u.correo);
            $('#rol_id').val(u.rol_id);
            $('#password').val('');
            loadAreas(u.area_id);
            modalUsu.show();
        }, 'json');
    });



    $('#btnGuardarUsuario').on('click', function () {
        const usuarioId = parseInt($('#usuario_id').val(), 10);
        const isNew = !usuarioId || usuarioId === 0;

        const nombre = ($('#nombre_completo').val() || '').trim();
        const correo = ($('#correo').val() || '').trim();
        const rolId = ($('#rol_id').val() || '').trim();
        const password = ($('#password').val() || '').trim();
        const areaId = ($('#area_id').val() || '').trim();

        if (!areaId) {
            Swal.fire({
                icon: 'warning',
                title: 'Departamento requerido',
                text: 'El departamento es obligatorio.'
            });
            return;
        }

        if (isNew && !password) {
            Swal.fire({
                icon: 'warning',
                title: 'Contraseña requerida',
                text: 'La contraseña es obligatoria para nuevo usuario.'
            });
            return;
        }

        if (!nombre || !correo || !rolId) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Todos los campos son obligatorios.'
            });
            return;
        }


        if (isNew) {
            $.ajax({
                url: '/hoshin_kanri/app/responsables/alta.php',
                type: 'POST',
                data: {
                    nombre_completo: nombre,
                    correo: correo,
                    rol_id: rolId,
                    password: password,
                    area_id: areaId
                },
                success: function (resp) {
                    if (!resp.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: resp.message
                        });
                        return;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuario guardado',
                        text: 'El usuario ha sido guardado correctamente.'
                    });
                    modalUsu.hide();
                    limpiarFormulario();
                    loadUsuarios();
                }
            });
        } else {
            $.ajax({
                url: '/hoshin_kanri/app/responsables/actualizar.php',
                type: 'POST',
                data: {
                    usuario_id: usuarioId,
                    nombre_completo: nombre,
                    correo: correo,
                    rol_id: rolId,
                    password: password,
                    area_id: areaId
                },
                success: function (resp) {
                    if (!resp.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: resp.message
                        });
                        return;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuario actualizado',
                        text: 'El usuario ha sido actualizado correctamente.'
                    });
                    modalUsu.hide();
                    limpiarFormulario();
                    loadUsuarios();

                }
            });
        }
    });
});