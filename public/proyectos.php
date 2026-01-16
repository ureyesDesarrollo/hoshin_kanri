<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>
<style>
    :root {
        --primary-blue: #006ec7;
        --primary-light: rgba(0, 110, 199, 0.06);
        --secondary-bg: #f8fafc;
        --border-light: #e9ecef;
        --text-muted: #64748b;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #0ea5e9;
    }

    .gantt {
        background: #fff;
        border: 1px solid var(--border-light);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
    }

    .gantt:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .gantt__header {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 20px;
        background: var(--primary-light);
        color: var(--primary-blue);
        border-bottom: 1px solid var(--border-light);
    }

    .gantt__header-title {
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .gantt__header-timeline {
        font-size: 0.85rem;
        color: var(--text-muted);
        font-weight: 500;
        background: white;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid var(--border-light);
    }

    .gantt__body {
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
        max-height: 600px;
    }

    .gantt__body::-webkit-scrollbar {
        width: 6px;
    }

    .gantt__body::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .gantt__body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .gantt__body .row {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 20px;
        border-bottom: 1px solid var(--border-light);
        transition: all 0.2s ease;
        position: relative;
    }

    .gantt__body .row:last-child {
        border-bottom: none;
    }

    .gantt__body .row:hover {
        background: #f8fafc;
    }

    .gantt__meta {
        position: relative;
        padding-left: 16px;
    }

    .gantt__meta::before {
        content: '';
        position: absolute;
        left: 0;
        top: 4px;
        bottom: 4px;
        width: 4px;
        background: var(--primary-blue);
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .row.risk .gantt__meta::before {
        background: var(--danger);
    }

    .row.warn .gantt__meta::before {
        background: var(--warning);
    }

    .gantt__meta .title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.1rem;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .gantt__meta .title i {
        color: var(--primary-blue);
        font-size: 0.9rem;
    }

    .gantt__meta .sub {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        line-height: 1.4;
        margin-bottom: 16px;
    }

    .timeline-container {
        background: #f8fafc;
        border-radius: 12px;
        padding: 16px;
        margin-top: 8px;
        border: 1px solid var(--border-light);
    }

    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        font-size: 0.9rem;
        color: var(--text-muted);
    }

    .timeline-dates {
        font-weight: 500;
        color: var(--primary-blue);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .timeline-track {
        position: relative;
        height: 40px;
        background: #f1f5f9;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .timeline-bar {
        position: absolute;
        top: 6px;
        height: 28px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--primary-blue), #0084e9);
        box-shadow: 0 2px 8px rgba(0, 110, 199, 0.2);
        transition: all 0.3s ease;
        min-width: 60px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 0 12px;
    }

    .timeline-bar:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0, 110, 199, 0.3);
    }

    .timeline-bar.risk {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }

    .timeline-bar.warn {
        background: linear-gradient(135deg, var(--warning), #e68a00);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
    }

    .timeline-bar .label {
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .progress-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: rgba(0, 110, 199, 0.1);
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .progress-indicator.risk {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .progress-indicator.warn {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }

    .stats-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 0.85rem;
        color: var(--text-muted);
        transition: all 0.2s ease;
    }

    .stats-badge:hover {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
    }

    .stats-badge i {
        font-size: 0.7rem;
    }

    .date-range {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        padding: 6px 12px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 0.85rem;
    }

    .search-container {
        position: relative;
        max-width: 320px;
    }

    .search-container i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }

    .search-container input {
        padding-left: 38px !important;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state h4 {
        color: #64748b;
        margin-bottom: 8px;
    }

    .filter-badge {
        background: #e2e8f0;
        border: none;
        padding: 6px 12px;
        font-size: 0.85rem;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .filter-badge .btn-close {
        font-size: 0.7rem;
        padding: 2px;
    }

    .milestone-icon {
        width: 40px;
        height: 40px;
        background: rgba(0, 110, 199, 0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-blue);
        flex-shrink: 0;
    }

    .deadline-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .progress-container {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 10px;
    }

    .progress-text {
        font-size: 0.85rem;
        font-weight: 600;
        min-width: 80px;
    }

    .progress-bar-custom {
        flex: 1;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(to right, var(--primary-blue), #0084e9);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .progress-bar-fill.risk {
        background: linear-gradient(to right, var(--danger), #dc2626);
    }

    .progress-bar-fill.warn {
        background: linear-gradient(to right, var(--warning), #e68a00);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .gantt__meta .sub {
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
        }

        .timeline-header {
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
        }

        .stats-badge,
        .date-range,
        .deadline-badge {
            font-size: 0.8rem;
            padding: 4px 10px;
        }

        .gantt__body .row {
            padding: 16px;
        }
    }

    @media (max-width: 576px) {
        .gantt__meta .title {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .progress-container {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .progress-bar-custom {
            width: 100%;
        }
    }
</style>
<main class="main-content" id="mainContent">
    <div<div class="row">
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
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <span class="ms-2 fw-bold">Proyectos</span>
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
                            <i class="fas fa-flag-checkered text-primary fs-4"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">Gestión de Proyectos</h1>
                            <p class="text-muted mb-0">Monitorea los proyectos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Filtros mejorados -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-4">
                        <div class="search-container">
                            <i class="fas fa-search"></i>
                            <input id="ganttBuscar" class="form-control border-2"
                                placeholder="Buscar milestone, responsable...">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ganttSoloRiesgo" role="switch">
                                <label class="form-check-label fw-medium" for="ganttSoloRiesgo">
                                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Solo en riesgo
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ganttSoloProximos">
                                <label class="form-check-label fw-medium" for="ganttSoloProximos">
                                    <i class="fas fa-clock me-2 text-warning"></i>Próximos a vencer
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-light border" id="ganttReset">
                            <i class="fas fa-filter-circle-xmark me-2"></i>Limpiar filtros
                        </button>
                    </div>
                </div>

                <!-- Filtros activos -->
                <div id="activeFilters" class="mt-3 d-flex flex-wrap gap-2" style="display: none !important;">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Gantt container -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="gantt">
                    <div class="gantt__body" id="ganttBody">
                        <!-- Loading state -->
                        <div class="empty-state" id="loadingState">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p>Cargando proyectos...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let ganttData = [];
    let allGanttData = [];

    /* =========================
       Utils
    ========================= */
    function safeNumber(v) {
        return v == null || v === '' ? 0 : Number(v);
    }

    function pickNumber(r, keys, def = 0) {
        for (const k of keys) {
            if (r[k] != null && r[k] !== '') return safeNumber(r[k]);
        }
        return def;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function daysBetween(a, b) {
        const da = new Date(a + 'T00:00:00');
        const db = new Date(b + 'T00:00:00');
        return Math.round((db - da) / (1000 * 60 * 60 * 24));
    }

    function formatDateMX(dateStr) {
        if (!dateStr) return '—';
        const [y, m, d] = dateStr.split('-').map(Number);
        const dt = new Date(y, m - 1, d);
        return dt.toLocaleDateString('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function diffDaysFromToday(dateStr) {
        if (!dateStr) return null;
        const [y, m, d] = dateStr.split('-').map(Number);
        const end = new Date(y, m - 1, d);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        end.setHours(0, 0, 0, 0);
        return Math.round((end - today) / (1000 * 60 * 60 * 24));
    }

    function deadlineText(fechaFin, fueraDeTiempo) {
        if (fueraDeTiempo) return 'Completado fuera de tiempo';
        const dd = diffDaysFromToday(fechaFin);
        if (dd == null) return '';
        if (dd > 1) return `${dd} días restantes`;
        if (dd === 1) return `1 día restante`;
        if (dd === 0) return `Vence hoy`;
        if (dd === -1) return `Vencido ayer`;
        return `Vencido hace ${Math.abs(dd)} días`;
    }

    function deadlineClass(fechaFin, fueraDeTiempo) {
        if (fueraDeTiempo) return 'bg-success text-white';
        const dd = diffDaysFromToday(fechaFin);
        if (dd < 0) return 'bg-danger text-white';
        if (dd <= 2) return 'bg-warning text-dark';
        return 'bg-info text-white';
    }

    /* progreso real = finalizadas / total */
    function calculateProgress(r) {
        const total = pickNumber(r, ['total_tareas', 'total'], 0);
        const fin = pickNumber(r, ['finalizadas', 'tareas_finalizadas'], 0);
        if (total === 0) return 0;
        const progress = Math.round((fin / total) * 100);
        return Math.max(0, Math.min(100, progress));
    }

    function groupBy(rows, keyFn) {
        const map = new Map();
        rows.forEach(r => {
            const k = keyFn(r);
            if (!map.has(k)) map.set(k, []);
            map.get(k).push(r);
        });
        return map;
    }

    /* =========================
       Filters UI
    ========================= */
    function updateStats(filteredData) {
        const today = new Date().toISOString().split('T')[0];

        const completed = filteredData.filter(r => calculateProgress(r) === 100).length;
        const inProgress = filteredData.filter(r => {
            const p = calculateProgress(r);
            return p > 0 && p < 100;
        }).length;

        const risk = filteredData.filter(r => {
            const ven = safeNumber(r.vencidas ?? 0);
            const tarde = safeNumber(r.completadas_tarde ?? 0);
            return (ven + tarde) > 0;
        }).length;

        const dueToday = filteredData.filter(r => r.fecha_fin === today).length;

        $('#totalProjects').text(filteredData.length);
        $('#completedCount').text(completed);
        $('#inProgressCount').text(inProgress);
        $('#riskCount').text(risk);
        $('#dueTodayCount').text(dueToday);
    }

    function updateActiveFilters(q, soloRiesgo, soloProximos) {
        const filters = [];
        if (q) filters.push(`"${q}"`);
        if (soloRiesgo) filters.push('En riesgo');
        if (soloProximos) filters.push('Próximos');

        const container = $('#activeFilters');
        if (filters.length > 0) {
            container.show();
            container.html(`
      <small class="text-muted me-2">Filtros activos:</small>
      ${filters.map(f => `
        <span class="filter-badge">
          ${f}
          <button class="btn-close btn-close-sm ms-1" onclick="removeFilter('${f.replace(/"/g, '')}')"></button>
        </span>
      `).join('')}
    `);
        } else {
            container.hide();
        }
    }

    function removeFilter(filter) {
        switch (filter) {
            case 'En riesgo':
                $('#ganttSoloRiesgo').prop('checked', false);
                break;
            case 'Próximos':
                $('#ganttSoloProximos').prop('checked', false);
                break;
            default:
                $('#ganttBuscar').val('');
                break;
        }
        applyGanttFilters();
    }

    /* =========================
       Data loading + filtering
    ========================= */
    function loadGantt() {
        $('#loadingState').show();

        $.get('/hoshin_kanri/app/proyectos/listar.php', function(resp) {
            $('#loadingState').hide();

            if (!resp.success) {
                $('#ganttBody').html(`
        <div class="empty-state">
          <i class="fas fa-exclamation-circle text-danger"></i>
          <h4>Error al cargar</h4>
          <p>No se pudieron cargar los proyectos</p>
        </div>
      `);
                return;
            }

            allGanttData = resp.data || [];
            applyGanttFilters();
        }, 'json').fail(function() {
            $('#loadingState').hide();
            $('#ganttBody').html(`
      <div class="empty-state">
        <i class="fas fa-wifi text-danger"></i>
        <h4>Error de conexión</h4>
        <p>Verifica tu conexión a internet</p>
      </div>
    `);
        });
    }

    function applyGanttFilters() {
        const q = ($('#ganttBuscar').val() || '').trim().toLowerCase();
        const soloRiesgo = $('#ganttSoloRiesgo').is(':checked');
        const soloProximos = $('#ganttSoloProximos').is(':checked');

        const filtered = allGanttData.filter(r => {
            const texto = `${r.objetivo || ''} ${r.milestone || r.titulo || ''} ${r.responsable || r.nombre_completo || ''}`.toLowerCase();

            // búsqueda
            if (q && !texto.includes(q)) return false;

            // riesgo = vencidas + completadas_tarde
            if (soloRiesgo) {
                const ven = safeNumber(r.vencidas ?? 0);
                const tarde = safeNumber(r.completadas_tarde ?? 0);
                if ((ven + tarde) <= 0) return false;
            }

            // próximos a vencer (<=7 días)
            if (soloProximos) {
                const diff = diffDaysFromToday(r.fecha_fin);
                if (diff === null || diff > 7) return false;
            }

            return true;
        });

        updateActiveFilters(q, soloRiesgo, soloProximos);
        ganttData = filtered;

        updateStats(filtered);
        renderGanttByObjetivo(filtered);
    }

    $(document).on('input change', '#ganttBuscar,#ganttSoloRiesgo,#ganttSoloProximos', function() {
        applyGanttFilters();
    });

    $(document).on('click', '#ganttReset', function() {
        $('#ganttBuscar').val('');
        $('#ganttSoloRiesgo').prop('checked', false);
        $('#ganttSoloProximos').prop('checked', false);
        applyGanttFilters();
    });

    /* =========================
       Rendering by Objetivo (Accordion)
       Requiere que el backend ya mande:
       - objetivo_id
       - objetivo
    ========================= */
    function renderGanttRowsHTML(rows) {
        let html = '';

        rows.forEach(r => {
            const progress = calculateProgress(r);
            const total = pickNumber(r, ['total_tareas', 'total'], 0);
            const fin = pickNumber(r, ['finalizadas', 'tareas_finalizadas'], 0);
            const ven = safeNumber(r.vencidas ?? 0);
            const completadasTarde = safeNumber(r.completadas_tarde ?? 0);

            const isComplete = (progress === 100);
            const fueraDeTiempo = (isComplete && completadasTarde > 0);

            let rowClass = '';
            let barClass = '';
            if ((ven + completadasTarde) > 0) {
                rowClass = 'risk';
                barClass = 'risk';
            } else if (progress < 100) {
                rowClass = 'warn';
                barClass = 'warn';
            }

            // timeline interno por objetivo (opcional: mantiene la sensación de gantt)
            // NOTA: se calcula por objetivo en renderGanttByObjetivo
            const leftPct = r.__leftPct ?? 0;
            const widthPct = r.__widthPct ?? 100;

            html += `
      <div class="row ${rowClass}">
        <div class="gantt__meta">
          <div class="title">
            <div class="milestone-icon"><i class="fas fa-flag"></i></div>
            <span>${escapeHtml(r.milestone || 'Sin título')}</span>
          </div>

          <div class="sub">
            <span class="stats-badge"><i class="fas fa-user-circle"></i> ${escapeHtml(r.responsable || 'Sin asignar')}</span>
            <span class="stats-badge"><i class="fas fa-tasks"></i> ${fin}/${total} tareas</span>

            <span class="date-range">
              <i class="fas fa-calendar-day"></i>
              ${formatDateMX(r.fecha_inicio)}
              <i class="fas fa-arrow-right mx-1"></i>
              ${formatDateMX(r.fecha_fin)}
            </span>

            <span class="deadline-badge ${deadlineClass(r.fecha_fin, fueraDeTiempo)}">
              <i class="fas fa-clock me-1"></i>
              ${deadlineText(r.fecha_fin, fueraDeTiempo)}
            </span>

            <span class="progress-indicator ${barClass}">
              <i class="fas fa-chart-line me-1"></i>
              ${progress}% completo
            </span>
          </div>

          <div class="timeline-container">
            <div class="timeline-header">
              <div class="timeline-dates">
                <i class="fas fa-calendar-alt"></i>
                ${formatDateMX(r.fecha_inicio)} → ${formatDateMX(r.fecha_fin)}
              </div>
              <div class="stats-badge">
                <i class="fas fa-triangle-exclamation"></i>
                ${(ven + completadasTarde)} vencidas
              </div>
            </div>

            <div class="timeline-track">
              <div class="timeline-bar ${barClass}" style="left:${leftPct}%;width:${widthPct}%;">
                <span class="label">${progress}%</span>
              </div>
            </div>

            <div class="progress-container">
              <div class="progress-text">Progreso: <strong>${progress}%</strong></div>
              <div class="progress-bar-custom">
                <div class="progress-bar-fill ${barClass}" style="width:${progress}%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
        });

        return html;
    }

    function renderGanttByObjetivo(rows) {
        if (!rows || rows.length === 0) {
            $('#ganttBody').html(`
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h4>Sin resultados</h4>
        <p>No se encontraron proyectos con los filtros actuales</p>
        <button class="btn btn-primary mt-3" onclick="$('#ganttReset').click()">
          <i class="fas fa-filter-circle-xmark me-2"></i>Limpiar filtros
        </button>
      </div>
    `);
            return;
        }

        // agrupar por objetivo
        const groups = groupBy(rows, r => `${r.objetivo_id || 0}||${r.objetivo || 'Sin objetivo'}`);

        let acc = `<div class="accordion" id="accObjetivos">`;
        let i = 0;

        for (const [k, itemsRaw] of groups.entries()) {
            const [, nombre] = k.split('||');

            // ordenar milestones por fecha_fin
            const items = [...itemsRaw].sort((a, b) => (a.fecha_fin > b.fecha_fin ? 1 : -1));

            // rango por objetivo (para timeline interno)
            const minStart = items.reduce((min, r) => (r.fecha_inicio < min ? r.fecha_inicio : min), items[0].fecha_inicio);
            const maxEnd = items.reduce((max, r) => (r.fecha_fin > max ? r.fecha_fin : max), items[0].fecha_fin);
            const totalDays = Math.max(1, daysBetween(minStart, maxEnd) + 1);

            // pre-calc para barras por objetivo
            items.forEach(r => {
                const offset = Math.max(0, daysBetween(minStart, r.fecha_inicio));
                const dur = Math.max(1, daysBetween(r.fecha_inicio, r.fecha_fin) + 1);
                r.__leftPct = (offset / totalDays) * 100;
                r.__widthPct = (dur / totalDays) * 100;
            });

            // KPI header del objetivo (progreso promedio ponderado por tareas)
            const total = items.reduce((s, r) => s + pickNumber(r, ['total_tareas', 'total'], 0), 0);
            const fin = items.reduce((s, r) => s + pickNumber(r, ['finalizadas', 'tareas_finalizadas'], 0), 0);
            const ven = items.reduce((s, r) => s + safeNumber(r.vencidas ?? 0), 0);
            const tarde = items.reduce((s, r) => s + safeNumber(r.completadas_tarde ?? 0), 0);
            const pct = total > 0 ? Math.round((fin / total) * 100) : 0;

            const accId = `obj_${i}`;
            const collapseId = `col_${i}`;
            const open = (i === 0) ? 'show' : '';
            const collapsed = (i === 0) ? '' : 'collapsed';
            const expanded = (i === 0) ? 'true' : 'false';

            acc += `
      <div class="accordion-item border-0 mb-3 shadow-sm">
        <h2 class="accordion-header" id="${accId}">
          <button class="accordion-button ${collapsed}" type="button"
            data-bs-toggle="collapse" data-bs-target="#${collapseId}"
            aria-expanded="${expanded}" aria-controls="${collapseId}">
            <div class="d-flex flex-wrap gap-2 align-items-center w-100">
              <div class="fw-bold">${escapeHtml(nombre)}</div>
              <span class="badge bg-primary ms-2">${items.length} milestones</span>
              <span class="badge bg-info text-white">${pct}%</span>
              <span class="badge bg-success">${fin} OK</span>
              <span class="badge bg-danger">${(ven + tarde)} vencidas</span>
              <span class="badge bg-light text-dark border ms-auto">
                ${formatDateMX(minStart)} → ${formatDateMX(maxEnd)}
              </span>
            </div>
          </button>
        </h2>

        <div id="${collapseId}" class="accordion-collapse collapse ${open}"
          aria-labelledby="${accId}" data-bs-parent="#accObjetivos">
          <div class="accordion-body p-0">
            <div class="gantt__body">
              ${renderGanttRowsHTML(items)}
            </div>
          </div>
        </div>
      </div>
    `;
            i++;
        }

        acc += `</div>`;
        $('#ganttBody').html(acc);
    }

    /* =========================
       Init
    ========================= */
    $(document).ready(function() {
        loadGantt();
    });
</script>