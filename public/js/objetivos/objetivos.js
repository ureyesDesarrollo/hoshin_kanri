function loadObjetivosStats(periodoId) {
    $.get('/hoshin_kanri/app/objetivos/stats.php', {
        periodo_id: periodoId
    }, function (resp) {
        if (!resp.success) return;

        $('#totalObjetivos').text(resp.data.total);
        $('#activosCount').text(resp.data.activos);
        $('#cerradosCount').text(resp.data.cerrados);
    }, 'json');
}

function limpiarCamposObjetivo() {
    $('#objId').val('');
    $('#objTitulo').val('');
    $('#objPeriodo').val('1');
    $('#objRespId').val('');
    $('#objDesc').val('');
}


function loadObjetivos(page = 1, periodoId) {
    currentPage = page;

    $('#loadingRow').removeClass('d-none');
    $('#emptyRow').addClass('d-none');

    $.get('/hoshin_kanri/app/objetivos/lista.php', {
        periodo_id: periodoId,
        page: page
    }, function (resp) {

        const tbody = $('#tablaObjetivos tbody');
        tbody.find('tr:not(#loadingRow, #emptyRow)').remove();
        $('#loadingRow').addClass('d-none');

        if (!resp.success || resp.data.length === 0) {
            $('#emptyRow').removeClass('d-none');
            $('#showingCount').text(0);
            $('#totalCount').text(0);
            $('#paginacionObjetivos').html('');
            return;
        }

        resp.data.forEach(o => {
            tbody.append(`
                    <tr>
                        <td class="ps-4">${o.titulo}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold">
                                        ${getResponsableIniciales(o.responsable)}
                                    </div>
                                </div>
                                <div>
                                    <div class="small fw-medium">${o.responsable || 'Sin asignar'}</div>
                                    ${o.responsable_email ?
                    `<div class="small text-muted">${o.responsable_email}</div>` : ''}
                                </div>
                            </div>
                        </td>
                        <td>${badgeEstatus(o.estatus)}</td>
                        <td>${formatFecha(o.creado_en)}</td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary btnEditarObjetivo" data-id="${o.objetivo_id}">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                `);
        });

        // Footer info
        $('#showingCount').text(resp.data.length);
        $('#totalCount').text(resp.pagination.total);

        renderPagination(resp.pagination, 'paginacionObjetivos');
    }, 'json');
}


async function getObjetivoPorId(objetivoId) {
    try {
        const resp = await $.ajax({
            url: '/hoshin_kanri/app/objetivos/listar_por_id.php',
            method: 'GET',
            dataType: 'json',
            data: { objetivo_id: objetivoId }
        });

        if (resp.success && resp.data.length) {
            return resp.data[0];
        }

        return null;
    } catch (e) {
        console.error('Error obteniendo objetivo', e);
        return null;
    }
}


$(document).ready(function () {
    const periodoId = $('#periodoId').val() || 0;

    loadObjetivosStats(periodoId);
    loadObjetivos(1, periodoId);

    $('#paginacionObjetivos').on('click', 'a.page-link', function (e) {
        e.preventDefault();
        const page = Number($(this).data('page'));
        if (!page || $(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) return;
        loadObjetivos(page, periodoId);
    });


    const modalEl = document.getElementById('modalObjetivo');

    if (modalEl) {
        var modalObj = new bootstrap.Modal(modalEl);
    }

    $('#btnNuevoObjetivo, #btnNuevoObjetivoTabla').on('click', function () {
        modalObj.show();
        initResponsablesTomSelect('#objRespId', '#modalObjetivo');
        loadResponsables('#objRespId', 0);

    });

    $('#tablaObjetivos').on('click', '.btnEditarObjetivo', async function () {
        const objetivoId = $(this).data('id');
        const objetivo = await getObjetivoPorId(objetivoId)
        $('#objId').val(objetivoId);
        $('#modalTitulo').text('Actualizar Objetivo');
        modalObj.show();
        $('#objTitulo').val(objetivo.titulo);
        $('#objDesc').val(objetivo.descripcion);
        initResponsablesTomSelect('#objRespId', '#modalObjetivo');
        loadResponsables('#objRespId', objetivo.responsable_usuario_id);
    });



    $('#btnGuardarObjetivo').on('click', function () {
        const titulo = $('#objTitulo').val();
        const periodo_id = $('#objPeriodo').val();
        const responsable_id = $('#objRespId').val();
        const descripcion = $('#objDesc').val();
        const objetivo_id = $('#objId').val();

        if (objetivo_id) {
            $.ajax({
                type: 'POST',
                url: '/hoshin_kanri/app/objetivos/actualizar.php',
                data: {
                    titulo, periodo_id, responsable_id, descripcion, objetivo_id
                },
                success: function (resp) {
                    if (resp.success) {
                        swal.fire({
                            icon: 'success',
                            title: 'Objetivo actualizado',
                            text: resp.message,
                        });
                        modalObj.hide();
                        limpiarCamposObjetivo();
                        loadObjetivosStats(periodoId);
                        loadObjetivos(currentPage, periodoId);
                    } else {
                        swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: resp.message,
                        });
                    }
                },
                error: function () {
                    console.log('Error al actualizar objetivo');
                }
            });
        } else {
            $.ajax({
                type: 'POST',
                url: '/hoshin_kanri/app/objetivos/alta.php',
                data: {
                    titulo, periodo_id, responsable_id, descripcion
                },
                success: function (resp) {
                    if (resp.success) {
                        swal.fire({
                            icon: 'success',
                            title: 'Objetivo guardado',
                            text: resp.message,
                        });
                        modalObj.hide();
                        limpiarCamposObjetivo();
                        loadObjetivosStats(periodoId);
                        loadObjetivos(currentPage, periodoId);
                    } else {
                        swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: resp.message,
                        });
                    }
                },
                error: function () {
                    console.log('Error al guardar objetivo');
                }
            });
        }
    });

});