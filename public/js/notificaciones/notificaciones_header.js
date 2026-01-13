function notifIconByType(tipo) {
    const icons = {
        'tarea_revision': {
            icon: 'fa-clipboard-check',
            class: 'revision',
            bgColor: 'rgba(255, 158, 0, 0.1)',
            color: 'var(--accent)'
        },
        'tarea_aprobada': {
            icon: 'fa-check-circle',
            class: 'aprobada',
            bgColor: 'rgba(6, 214, 160, 0.1)',
            color: 'var(--secondary)'
        },
        'tarea_rechazada': {
            icon: 'fa-times-circle',
            class: 'rechazada',
            bgColor: 'rgba(220, 53, 69, 0.1)',
            color: 'var(--danger)'
        },
        'comentario': {
            icon: 'fa-comment',
            class: 'comentario',
            bgColor: 'rgba(108, 117, 125, 0.1)',
            color: 'var(--gray-600)'
        },
        'recordatorio': {
            icon: 'fa-clock',
            class: 'recordatorio',
            bgColor: 'rgba(0, 110, 199, 0.1)',
            color: 'var(--primary)'
        },
        'sistema': {
            icon: 'fa-cog',
            class: 'sistema',
            bgColor: 'rgba(23, 162, 184, 0.1)',
            color: 'var(--info)'
        }
    };

    const config = icons[tipo] || {
        icon: 'fa-bell',
        class: 'default',
        bgColor: 'rgba(108, 117, 125, 0.1)',
        color: 'var(--gray-600)'
    };

    return `<div class="notif-icon ${config.class}">
        <i class="fas ${config.icon}"></i>
    </div>`;
}

function buildContextLine(n) {
    const parts = [];

    if (n.tarea_titulo) {
        parts.push(`<span class="notif-context-item">
            <i class="fas fa-tasks fa-xs" style="color: var(--primary)"></i>
            ${escapeHtml(n.tarea_titulo)}
        </span>`);
    }

    if (n.milestone_titulo) {
        parts.push(`<span class="notif-context-item">
            <i class="fas fa-flag-checkered fa-xs" style="color: var(--secondary)"></i>
            ${escapeHtml(n.milestone_titulo)}
        </span>`);
    }

    if (n.estrategia_titulo) {
        parts.push(`<span class="notif-context-item">
            <i class="fas fa-chess-knight fa-xs" style="color: var(--accent)"></i>
            ${escapeHtml(n.estrategia_titulo)}
        </span>`);
    }

    if (parts.length) {
        return `<div class="notif-context">${parts.join('')}</div>`;
    }
    return '';
}

function renderNotifItem(n) {
    console.log(n);
    const unread = parseInt(n.leida || 0) === 0;
    const url = n.entidad_id ? `/hoshin_kanri/public/detalle.php?tarea_id=${n.entidad_id}` : '/hoshin_kanri/public/notificaciones/notificaciones.php';

    const title = n.titulo ? escapeHtml(n.titulo) : titleFallbackByType(n);
    const body = n.cuerpo ? `<div class="notif-body">${escapeHtml(n.cuerpo)}</div>` : '';
    const ctx = buildContextLine(n);
    const resposable_tarea = n.tarea_responsable ? `<div class="notif-resposable">${escapeHtml(n.tarea_responsable)}</div>` : '';
    const when = n.creada_en;

    return `
    <div class="notif-item ${unread ? 'unread' : ''}" data-id="${n.notificacion_id}" data-url="${url}">
        <div class="d-flex gap-3">
            ${notifIconByType(n.tipo)}
            <div class="notif-content">
            ${resposable_tarea}
                <div class="notif-title">
                    <span class="notif-title-text">${title}</span>
                    ${unread ? '<span class="notif-badge badge">Nuevo</span>' : ''}
                </div>
                ${body}
                ${ctx}
                <div class="notif-time">
                    <i class="far fa-clock"></i>
                    ${when}
                </div>
            </div>
        </div>
    </div>
    `;
}

function updateBadge(count) {
    const $b = $('#notifBadgeCount');
    const $countText = $('#notifCountText');
    const c = parseInt(count || 0);

    // Actualizar texto del header
    if (c > 0) {
        $countText.text(`${c} notificación${c !== 1 ? 'es' : ''} sin leer`);
    } else {
        $countText.text('Todas leídas');
    }

    // Actualizar badge
    if (c > 0) {
        $b.text(c > 99 ? '99+' : c).removeClass('d-none');

        // Animación para notificaciones nuevas
        if (c > parseInt($b.data('prev') || 0)) {
            $b.addClass('animate__animated animate__bounce');
            setTimeout(() => $b.removeClass('animate__animated animate__bounce'), 1000);
        }
        $b.data('prev', c);
    } else {
        $b.addClass('d-none');
    }
}


function loadNotifResumen() {
    $('#notifList').html(`
        <div class="notif-loading">
            <div class="spinner-notif"></div>
            <div class="small text-muted" style="color: var(--gray-600)">Cargando notificaciones...</div>
        </div>
    `);

    return $.ajax({
        url: '/hoshin_kanri/app/notificaciones/resumen.php',
        method: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function (resp) {
            if (!resp || !resp.success) {
                showNotifError('Error al cargar notificaciones');
                return;
            }

            const d = resp.data || {};
            updateBadge(d.unread_count || 0);

            const items = d.items || [];
            if (!items.length) {
                $('#notifList').html(`
                    <div class="notif-empty">
                        <div class="notif-empty-icon">
                            <i class="far fa-bell-slash"></i>
                        </div>
                        <div class="fw-medium mb-2" style="color: var(--gray-700)">No tienes notificaciones</div>
                        <div class="small" style="color: var(--gray-500)">Aquí aparecerán tus notificaciones recientes</div>
                    </div>
                `);
                return;
            }

            //Solo no leidas
            const unreadItems = items.filter(n => parseInt(n.leida) === 0);
            $('#notifList').html(unreadItems.map(renderNotifItem).join(''));

            // Añadir clase de animación a notificaciones nuevas
            $('.notif-item.unread').each(function (i) {
                $(this).addClass('new-notif');
                $(this).css('animation-delay', `${i * 50}ms`);
            });

            // Remover clase después de la animación
            setTimeout(() => {
                $('.notif-item').removeClass('new-notif');
            }, 500);
        },
        error: function (xhr, status, error) {
            console.error('Error cargando notificaciones:', error);
            showNotifError('No se pudo conectar con el servidor');
        }
    });
}

function showNotifError(message) {
    $('#notifList').html(`
        <div class="notif-empty">
            <div class="notif-empty-icon" style="color: var(--danger)">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="fw-medium mb-2" style="color: var(--gray-700)">${message}</div>
            <button class="btn btn-sm" onclick="loadNotifResumen()" 
                    style="background: var(--primary); color: white; border: none;">
                <i class="fas fa-redo me-1"></i> Reintentar
            </button>
        </div>
    `);
}

// Mejora del manejo del dropdown con colores institucionales
$(document).ready(function () {
    // Carga inicial
    loadNotifResumen();

    // Refresco inteligente basado en estado
    let refreshInterval = 30000; // 30s por defecto

    function setupRefreshInterval() {
        clearInterval(window.notifRefreshInterval);
        const unreadCount = parseInt($('#notifBadgeCount').text()) || 0;
        window.notifRefreshInterval = setInterval(
            loadNotifResumen,
            unreadCount > 0 ? 15000 : 30000
        );
    }

    // Configurar intervalo inicial
    setTimeout(setupRefreshInterval, 1000);

    // Mejora del dropdown
    const dropdownEl = document.getElementById('notifDropdownWrap');
    const dropdown = new bootstrap.Dropdown(dropdownEl.querySelector('[data-bs-toggle="dropdown"]'));

    // Refresh al abrir dropdown
    $('#btnNotif').on('click', function (e) {
        loadNotifResumen();
        setupRefreshInterval(); // Recalcular intervalo
        e.stopPropagation();
    });

    // Cerrar dropdown al hacer clic fuera
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#notifDropdownWrap').length) {
            dropdown.hide();
        }
    });

    // Click en item con feedback visual mejorado
    $(document).on('click', '.notif-item', function (e) {
        e.stopPropagation();
        const $item = $(this);
        const id = $item.data('id');
        const url = $item.data('url');

        // Feedback visual inmediato
        $item.addClass('active').removeClass('unread');
        $item.find('.notif-badge').fadeOut(200);

        // Deshabilitar múltiples clics
        $item.css('pointer-events', 'none');

        // Marcar como leída
        $.ajax({
            url: '/hoshin_kanri/app/notificaciones/marcar_leida.php',
            method: 'POST',
            data: { notificacion_id: id },
            dataType: 'json',
            timeout: 2000,
            success: function () {
                // Redirigir después de un breve delay para ver la animación
                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            },
            error: function () {
                // Redirigir incluso si falla el marcado
                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            }
        });
    });

    // Marcar todas con confirmación mejorada
    $('#btnNotifMarkAll').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!$('.notif-item.unread').length) {
            showToast('No hay notificaciones sin leer', 'info');
            return;
        }

        if (confirm('¿Marcar todas las notificaciones como leídas?')) {
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '/hoshin_kanri/app/notificaciones/marcar_todas.php',
                method: 'POST',
                dataType: 'json',
                success: function (resp) {
                    if (resp && resp.success) {
                        // Animación de desvanecimiento
                        $('.notif-item.unread').each(function (i) {
                            $(this).delay(i * 50).fadeTo(200, 0.5, function () {
                                $(this).removeClass('unread').fadeTo(200, 1);
                            });
                        });
                        updateBadge(0);
                        showToast('Todas las notificaciones marcadas como leídas', 'success');
                    }
                    $('#btnNotifMarkAll').prop('disabled', false).html('<i class="fas fa-check-double"></i>');
                },
                error: function () {
                    showToast('Error al marcar notificaciones', 'error');
                    $('#btnNotifMarkAll').prop('disabled', false).html('<i class="fas fa-check-double"></i>');
                }
            });
        }
    });

    // Configuración de notificaciones (ejemplo)
    $('#btnNotifSettings').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Aquí podrías mostrar un modal de configuración
        showSettingsModal();
    });

    // Funciones auxiliares
    function showToast(message, type = 'info') {
        // Implementar toast notification
        console.log(`[${type.toUpperCase()}] ${message}`);
    }

    function showSettingsModal() {
        // Modal de configuración de notificaciones
        console.log('Mostrar configuración de notificaciones');
    }

    // WebSocket para notificaciones en tiempo real (ejemplo)
    if (typeof io !== 'undefined') {
        const socket = io();
        socket.on('new-notification', function (data) {
            if (data.userId === CURRENT_USER_ID) { // Asegúrate de definir CURRENT_USER_ID
                loadNotifResumen();
                showToast('Tienes una nueva notificación', 'info');
            }
        });
    }
});