<?php
require_once '../app/layout/header.php';
require_once '../app/layout/sidebar.php';
?>
<style>
    /* =========================
       ESTILOS ESPECÍFICOS PARA EL GANTT - SIN CONFLICTOS
       Usamos prefijos específicos para evitar choques
    ========================= */

    /* Contenedor principal con prefijo */
    .gantt-dashboard {
        min-height: calc(100vh - 60px);
        position: relative;
    }

    /* Prevenir parpadeos y pantalla blanca */
    .gantt-dashboard-content {
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .gantt-dashboard-content.loaded {
        opacity: 1;
    }

    /* Loading overlay específico */
    .gantt-loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity 0.3s ease;
    }

    .gantt-loading-overlay.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .gantt-loading-spinner {
        width: 50px;
        height: 50px;
        border: 3px solid rgba(0, 110, 199, 0.1);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: gantt-spin 1s linear infinite;
    }

    @keyframes gantt-spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Contenedores específicos del Gantt */
    .gantt-container-card {
        background: white !important;
        border: 1px solid var(--gray-200) !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
        position: relative !important;
        z-index: 1 !important;
    }

    /* ACORDEÓN ESPECÍFICO PARA OBJETIVOS - VERSIÓN SIMPLIFICADA */
    .gantt-accordion {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--gray-200);
    }

    .gantt-accordion-item {
        border-bottom: 1px solid var(--gray-200);
        background: white;
    }

    .gantt-accordion-item:last-child {
        border-bottom: none;
    }

    .gantt-accordion-header {
        padding: 0;
        margin: 0;
        background: white;
    }

    .gantt-accordion-button {
        width: 100%;
        padding: 20px 24px;
        background: white;
        border: none;
        text-align: left;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .gantt-accordion-button:hover {
        background: rgba(0, 110, 199, 0.05);
    }

    .gantt-accordion-button[aria-expanded="true"] {
        background: rgba(0, 110, 199, 0.08);
    }

    .gantt-accordion-button[aria-expanded="true"] .gantt-accordion-icon {
        transform: rotate(180deg);
    }

    .gantt-accordion-button:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 110, 199, 0.1);
    }

    .gantt-accordion-icon {
        transition: transform 0.3s ease;
        font-size: 14px;
        color: var(--gray-600);
    }

    .gantt-accordion-body {
        padding: 24px;
        background: var(--gray-50);
        border-top: 1px solid var(--gray-200);
        display: none;
    }

    .gantt-accordion-body.show {
        display: block;
    }

    /* Header del objetivo */
    .gantt-objective-header {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .gantt-objective-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
    }

    .gantt-objective-stats {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-left: auto;
        flex-wrap: wrap;
    }

    /* SISTEMA DE PRIORIDADES INTERACTIVO */
    .gantt-priority-selector {
        position: relative;
    }

    .gantt-priority-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        user-select: none;
    }

    .gantt-priority-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .gantt-priority-badge:active {
        transform: translateY(0);
    }

    .gantt-priority-badge.selected {
        border: 2px solid currentColor;
        box-shadow: 0 0 0 3px rgba(currentColor, 0.2);
    }

    /* Colores de prioridad */
    .gantt-priority-critical {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.08));
        color: var(--danger);
        border-color: rgba(220, 53, 69, 0.3);
    }

    .gantt-priority-high {
        background: linear-gradient(135deg, rgba(255, 158, 0, 0.15), rgba(255, 158, 0, 0.08));
        color: var(--warning);
        border-color: rgba(255, 158, 0, 0.3);
    }

    .gantt-priority-medium {
        background: linear-gradient(135deg, rgba(0, 110, 199, 0.15), rgba(0, 110, 199, 0.08));
        color: var(--primary);
        border-color: rgba(0, 110, 199, 0.3);
    }

    .gantt-priority-low {
        background: linear-gradient(135deg, rgba(108, 117, 125, 0.15), rgba(108, 117, 125, 0.08));
        color: var(--gray-600);
        border-color: rgba(108, 117, 125, 0.3);
    }

    /* Modal/overlay para selector de prioridad */
    .gantt-priority-modal {
        position: absolute;
        top: 100%;
        left: 0;
        margin-top: 8px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        border: 1px solid var(--gray-200);
        z-index: 1000;
        min-width: 200px;
        display: none;
        animation: gantt-fade-in 0.2s ease;
    }

    .gantt-priority-modal.show {
        display: block;
    }

    .gantt-priority-modal::before {
        content: '';
        position: absolute;
        top: -6px;
        left: 20px;
        width: 12px;
        height: 12px;
        background: white;
        border-left: 1px solid var(--gray-200);
        border-top: 1px solid var(--gray-200);
        transform: rotate(45deg);
    }

    .gantt-priority-options {
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .gantt-priority-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .gantt-priority-option:hover {
        background: rgba(0, 110, 199, 0.05);
        transform: translateX(4px);
    }

    .gantt-priority-option.selected {
        background: rgba(0, 110, 199, 0.08);
        border-color: rgba(0, 110, 199, 0.2);
    }

    .gantt-priority-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        flex-shrink: 0;
    }

    .gantt-priority-label {
        flex: 1;
        font-weight: 500;
        font-size: 14px;
    }

    .gantt-priority-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    @keyframes gantt-fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Badges específicos */
    .gantt-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .gantt-badge-primary {
        background: rgba(0, 110, 199, 0.1);
        color: var(--primary);
        border: 1px solid rgba(0, 110, 199, 0.2);
    }

    .gantt-badge-danger {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger);
        border: 1px solid rgba(220, 53, 69, 0.2);
    }

    .gantt-badge-warning {
        background: rgba(255, 193, 7, 0.1);
        color: var(--warning);
        border: 1px solid rgba(255, 193, 7, 0.2);
    }

    .gantt-badge-success {
        background: rgba(25, 135, 84, 0.1);
        color: var(--success);
        border: 1px solid rgba(25, 135, 84, 0.2);
    }

    .gantt-badge-neutral {
        background: rgba(108, 117, 125, 0.1);
        color: var(--gray-700);
        border: 1px solid rgba(108, 117, 125, 0.2);
    }

    /* Milestone cards específicas */
    .gantt-milestone-card {
        background: white;
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.3s ease;
        position: relative;
    }

    .gantt-milestone-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border-color: var(--primary);
    }

    .gantt-milestone-card.risk {
        border-left: 4px solid var(--danger);
    }

    .gantt-milestone-card.warn {
        border-left: 4px solid var(--warning);
    }

    .gantt-milestone-card.success {
        border-left: 4px solid var(--success);
    }

    .gantt-milestone-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
        gap: 12px;
    }

    .gantt-milestone-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
        flex: 1;
    }

    .gantt-milestone-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    /* Timeline específica */
    .gantt-timeline-track {
        height: 8px;
        background: var(--gray-200);
        border-radius: 4px;
        overflow: hidden;
        margin: 16px 0;
        position: relative;
    }

    .gantt-progress-bar {
        height: 100%;
        background: var(--primary-gradient);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .gantt-progress-bar.risk {
        background: linear-gradient(135deg, var(--danger), #c82333);
    }

    .gantt-progress-bar.warn {
        background: linear-gradient(135deg, var(--warning), #e68a00);
    }

    .gantt-progress-bar.success {
        background: linear-gradient(135deg, var(--success), #198754);
    }

    /* Acciones mejoradas */
    .gantt-milestone-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .gantt-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .gantt-action-btn-primary {
        background: rgba(0, 110, 199, 0.1);
        color: var(--primary);
        border-color: rgba(0, 110, 199, 0.2);
    }

    .gantt-action-btn-primary:hover {
        background: rgba(0, 110, 199, 0.2);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 110, 199, 0.1);
    }

    .gantt-action-btn-secondary {
        background: rgba(108, 117, 125, 0.1);
        color: var(--gray-700);
        border-color: rgba(108, 117, 125, 0.2);
    }

    .gantt-action-btn-secondary:hover {
        background: rgba(108, 117, 125, 0.2);
        transform: translateY(-1px);
    }

    .gantt-action-btn-success {
        background: rgba(25, 135, 84, 0.1);
        color: var(--success);
        border-color: rgba(25, 135, 84, 0.2);
    }

    .gantt-action-btn-success:hover {
        background: rgba(25, 135, 84, 0.2);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(25, 135, 84, 0.1);
    }

    .gantt-action-btn i {
        font-size: 12px;
    }

    /* Estados vacíos y de error */
    .gantt-empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .gantt-empty-state-icon {
        font-size: 48px;
        color: var(--gray-300);
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .gantt-error-state {
        background: rgba(220, 53, 69, 0.05);
        border: 1px solid rgba(220, 53, 69, 0.1);
        border-radius: 10px;
        padding: 40px 20px;
        text-align: center;
    }

    /* Breadcrumbs específicos */
    .gantt-breadcrumb {
        display: flex;
        align-items: center;
        gap: 12px;
        background: white;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        border: 1px solid var(--gray-200);
    }

    .gantt-breadcrumb-step {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .gantt-breadcrumb-step.active {
        background: rgba(0, 110, 199, 0.1);
        color: var(--primary);
        font-weight: 600;
    }

    /* Filtros específicos */
    .gantt-filters-container {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid var(--gray-200);
    }

    .gantt-search-box {
        position: relative;
    }

    .gantt-search-box .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-500);
    }

    .gantt-search-box input {
        padding-left: 40px !important;
        border: 1px solid var(--gray-300) !important;
        border-radius: 8px !important;
    }

    .gantt-search-box input:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(0, 110, 199, 0.1) !important;
    }

    /* Stats cards específicas */
    .gantt-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .gantt-stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--gray-200);
        transition: all 0.3s ease;
    }

    .gantt-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 110, 199, 0.1);
        border-color: var(--primary);
    }

    .gantt-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        background: var(--primary-gradient);
        color: white;
        font-size: 20px;
    }

    .gantt-stat-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark);
        line-height: 1;
        margin-bottom: 4px;
    }

    .gantt-stat-label {
        font-size: 14px;
        color: var(--gray-600);
        font-weight: 500;
    }

    /* Controles de expansión */
    .gantt-expand-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding: 12px 16px;
        background: white;
        border-radius: 10px;
        border: 1px solid var(--gray-200);
    }

    /* Tooltip para prioridades */
    .gantt-priority-tooltip {
        position: absolute;
        background: var(--dark);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        z-index: 1001;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s ease;
        transform: translateY(10px);
    }

    .gantt-priority-tooltip.show {
        opacity: 1;
        transform: translateY(0);
    }

    .gantt-priority-tooltip::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 50%;
        transform: translateX(-50%);
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 6px solid var(--dark);
    }

    /* Responsive específico */
    @media (max-width: 768px) {
        .gantt-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .gantt-milestone-card {
            padding: 16px;
        }

        .gantt-filters-container {
            padding: 16px;
        }

        .gantt-accordion-button {
            padding: 16px 20px;
        }

        .gantt-accordion-body {
            padding: 16px;
        }

        .gantt-objective-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .gantt-objective-stats {
            margin-left: 0;
            width: 100%;
            justify-content: flex-start;
        }

        .gantt-expand-controls {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }

        .gantt-milestone-actions {
            flex-direction: column;
            align-items: flex-start;
        }

        .gantt-priority-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 300px;
        }

        .gantt-priority-modal::before {
            display: none;
        }
    }

    @media (max-width: 576px) {
        .gantt-stats-grid {
            grid-template-columns: 1fr;
        }

        .gantt-stat-card {
            padding: 16px;
        }

        .gantt-breadcrumb {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .gantt-milestone-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .gantt-milestone-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
    }

    /* Asegurar que no haya conflictos con Bootstrap */
    .gantt-dashboard .btn {
        font-weight: 500 !important;
    }

    .gantt-dashboard .form-control {
        border-radius: 8px !important;
    }

    .gantt-dashboard .form-switch .form-check-input {
        margin-right: 8px !important;
    }
</style>

<!-- Loading Overlay específico -->
<div class="gantt-loading-overlay" id="ganttLoading">
    <div class="text-center">
        <div class="gantt-loading-spinner mb-3"></div>
        <p class="text-primary fw-medium">Preparando dashboard...</p>
    </div>
</div>

<main class="main-content gantt-dashboard" id="mainContent" style="display: none;">
    <!-- Breadcrumb específico -->
    <div class="gantt-breadcrumb">
        <div class="gantt-breadcrumb-step">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </div>
        <i class="fas fa-chevron-right text-muted"></i>
        <div class="gantt-breadcrumb-step active">
            <i class="fas fa-flag-checkered"></i>
            <span>Proyectos</span>
        </div>
    </div>

    <!-- Título -->
    <div class="card gantt-container-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="gantt-stat-icon me-3">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div>
                        <h1 class="h3 fw-bold mb-1">Gestión de Proyectos</h1>
                        <p class="text-muted mb-0">Monitorea y gestiona todos tus proyectos</p>
                    </div>
                </div>
                <span class="gantt-badge gantt-badge-primary">
                    <i class="fas fa-sync-alt"></i>
                    Actualizado
                </span>
            </div>
        </div>
    </div>
    <!-- Filtros -->
    <div class="gantt-filters-container">
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <div class="gantt-search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text"
                        id="ganttBuscar"
                        class="form-control"
                        placeholder="Buscar proyectos, milestones...">
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-primary" id="ganttReset">
                        <i class="fas fa-filter-circle-xmark me-2"></i>Limpiar
                    </button>
                    <button class="btn btn-primary" id="refreshBtn">
                        <i class="fas fa-sync-alt me-2"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ganttSoloRiesgo" role="switch">
                        <label class="form-check-label fw-medium" for="ganttSoloRiesgo">
                            <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                            Solo en riesgo
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ganttSoloProximos" role="switch">
                        <label class="form-check-label fw-medium" for="ganttSoloProximos">
                            <i class="fas fa-clock me-2 text-warning"></i>
                            Próximos a vencer
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ganttSoloActivos" role="switch">
                        <label class="form-check-label fw-medium" for="ganttSoloActivos">
                            <i class="fas fa-play-circle me-2 text-primary"></i>
                            Solo activos
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <span class="text-muted small">
                    <span id="resultCount">0</span> proyectos encontrados
                </span>
            </div>
        </div>

        <!-- Filtros activos -->
        <div class="mt-3" id="activeFilters" style="display: none;">
            <div class="d-flex flex-wrap gap-2" id="filterTags"></div>
        </div>
    </div>

    <!-- Contenedor principal del Gantt con acordeón -->
    <div class="card gantt-container-card">
        <div class="card-body p-0">
            <div class="gantt-content" id="ganttBody">
                <!-- Loading state -->
                <div class="text-center py-5" id="loadingState">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-muted">Cargando proyectos...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal overlay para clicks fuera -->
<div class="modal-overlay" id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 999; background: rgba(0,0,0,0.1);"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Usar IIFE para evitar conflictos
    (function($) {
        'use strict';

        let ganttData = [];
        let allGanttData = [];
        let isLoading = false;

        // Sistema de prioridades
        const priorityLevels = {
            '3': {
                label: 'Prioritario',
                color: 'danger',
                class: 'gantt-priority-critical',
                icon: 'fa-fire',
                description: 'Prioritario - requiere atención inmediata'
            },
            '2': {
                label: 'Importante',
                color: 'warning',
                class: 'gantt-priority-high',
                icon: 'fa-arrow-up',
                description: 'Importante - completar pronto'
            },
            '1': {
                label: 'Puede esperar',
                color: 'primary',
                class: 'gantt-priority-medium',
                icon: 'fa-equals',
                description: 'Normal - completar en tiempo'
            }
        };

        /* =========================
           FUNCIONES DE UTILIDAD - SIN CONFLICTOS
        ========================= */

        // Mostrar/ocultar loading específico
        function toggleGanttLoading(show) {
            const overlay = $('#ganttLoading');
            const mainContent = $('#mainContent');

            if (show) {
                overlay.removeClass('hidden');
                mainContent.hide();
            } else {
                setTimeout(() => {
                    overlay.addClass('hidden');
                    mainContent.fadeIn(300);
                    $('.gantt-dashboard-content').addClass('loaded');
                }, 500);
            }
        }

        // Función segura para números
        function safeNumber(v) {
            if (v == null || v === '' || v === undefined) return 0;
            const n = Number(v);
            return isNaN(n) ? 0 : n;
        }

        // Obtener número de un objeto con múltiples posibles keys
        function pickNumber(r, keys, def = 0) {
            for (const k of keys) {
                if (r[k] != null && r[k] !== '') {
                    const val = safeNumber(r[k]);
                    if (!isNaN(val)) return val;
                }
            }
            return def;
        }

        // Escapar HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Formatear fecha MX
        function formatDateMX(dateStr) {
            if (!dateStr) return '—';
            try {
                const [y, m, d] = dateStr.split('-').map(Number);
                const dt = new Date(y, m - 1, d);
                return dt.toLocaleDateString('es-MX', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }).replace(/ de /g, ' ');
            } catch (e) {
                return dateStr;
            }
        }

        // Diferencia de días desde hoy
        function diffDaysFromToday(dateStr) {
            if (!dateStr) return null;
            try {
                const [y, m, d] = dateStr.split('-').map(Number);
                const end = new Date(y, m - 1, d);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                end.setHours(0, 0, 0, 0);
                return Math.round((end - today) / (1000 * 60 * 60 * 24));
            } catch (e) {
                return null;
            }
        }

        // Calcular progreso
        function calculateProgress(r) {
            const total = pickNumber(r, ['total_tareas', 'total'], 0);
            const fin = pickNumber(r, ['finalizadas', 'tareas_finalizadas'], 0);
            if (total === 0) return 0;
            const progress = Math.round((fin / total) * 100);
            return Math.max(0, Math.min(100, progress));
        }

        // Obtener estado del milestone
        function getMilestoneStatus(r) {
            const progress = calculateProgress(r);
            const ven = safeNumber(r.vencidas ?? 0);
            const tarde = safeNumber(r.completadas_tarde ?? 0);

            if ((ven + tarde) > 0) return 'risk';
            if (progress === 100) return 'completed';
            if (progress < 100 && progress > 0) return 'in-progress';
            return 'pending';
        }

        // Texto para deadline
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

        // Calcular clase para deadline
        function deadlineClass(fechaFin, fueraDeTiempo) {
            if (fueraDeTiempo) return 'gantt-badge-success';
            const dd = diffDaysFromToday(fechaFin);
            if (dd < 0) return 'gantt-badge-danger';
            if (dd <= 2) return 'gantt-badge-warning';
            return 'gantt-badge-neutral';
        }

        /* =========================
           UI UPDATES - SIN CONFLICTOS
        ========================= */

        function updateStats(filteredData) {
            const completed = filteredData.filter(r => calculateProgress(r) === 100).length;
            const inProgress = filteredData.filter(r => {
                const p = calculateProgress(r);
                return p > 0 && p < 100;
            }).length;

            const risk = filteredData.filter(r => getMilestoneStatus(r) === 'risk').length;

            $('#totalProjects').text(filteredData.length);
            $('#completedCount').text(completed);
            $('#inProgressCount').text(inProgress);
            $('#riskCount').text(risk);
            $('#resultCount').text(filteredData.length);
        }

        function updateFilterTags() {
            const q = $('#ganttBuscar').val() || '';
            const soloRiesgo = $('#ganttSoloRiesgo').is(':checked');
            const soloProximos = $('#ganttSoloProximos').is(':checked');
            const soloActivos = $('#ganttSoloActivos').is(':checked');

            const filters = [];
            if (q.trim()) filters.push({
                type: 'search',
                label: `"${q.trim()}"`
            });
            if (soloRiesgo) filters.push({
                type: 'risk',
                label: 'En riesgo'
            });
            if (soloProximos) filters.push({
                type: 'upcoming',
                label: 'Próximos'
            });
            if (soloActivos) filters.push({
                type: 'active',
                label: 'Solo activos'
            });

            const container = $('#activeFilters');
            const tagsContainer = $('#filterTags');

            if (filters.length > 0) {
                container.show();
                tagsContainer.empty();

                filters.forEach(filter => {
                    const tag = $(`
                        <span class="gantt-badge gantt-badge-primary">
                            ${filter.label}
                            <button type="button" class="btn-close btn-close-sm ms-1" 
                                    onclick="removeFilter('${filter.type}')"></button>
                        </span>
                    `);
                    tagsContainer.append(tag);
                });
            } else {
                container.hide();
                tagsContainer.empty();
            }
        }

        // Función global para remover filtros
        window.removeFilter = function(filterType) {
            switch (filterType) {
                case 'search':
                    $('#ganttBuscar').val('');
                    break;
                case 'risk':
                    $('#ganttSoloRiesgo').prop('checked', false);
                    break;
                case 'upcoming':
                    $('#ganttSoloProximos').prop('checked', false);
                    break;
                case 'active':
                    $('#ganttSoloActivos').prop('checked', false);
                    break;
            }
            applyGanttFilters();
        };

        /* =========================
           DATA LOADING & FILTERING
        ========================= */

        function loadGanttData() {
            if (isLoading) return;

            isLoading = true;
            $('#loadingState').show();

            $.ajax({
                url: '/hoshin_kanri/app/proyectos/listar_lic.php',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(resp) {
                    if (!resp || !resp.success) {
                        showError(resp?.message || 'Error al cargar los proyectos');
                        return;
                    }

                    allGanttData = resp.data || [];

                    if (allGanttData.length === 0) {
                        showEmptyState();
                        return;
                    }

                    applyGanttFilters();
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout') {
                        showError('Tiempo de espera agotado');
                    } else if (status === 'error') {
                        showError('Error de conexión con el servidor');
                    } else {
                        showError('Error desconocido: ' + status);
                    }
                },
                complete: function() {
                    isLoading = false;
                    $('#loadingState').hide();
                    toggleGanttLoading(false);
                }
            });
        }

        function showError(message) {
            $('#ganttBody').html(`
                <div class="gantt-error-state">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    </div>
                    <h5 class="text-danger mb-3">¡Ups! Algo salió mal</h5>
                    <p class="text-muted mb-4">${message}</p>
                    <button class="btn btn-primary" onclick="loadGanttData()">
                        <i class="fas fa-redo me-2"></i>Reintentar
                    </button>
                </div>
            `);
        }

        function showEmptyState() {
            $('#ganttBody').html(`
                <div class="gantt-empty-state">
                    <div class="gantt-empty-state-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h4 class="mb-3">No hay proyectos</h4>
                    <p class="text-muted mb-4">Aún no se han creado proyectos en el sistema.</p>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Crear primer proyecto
                    </button>
                </div>
            `);
        }

        function applyGanttFilters() {
            const q = ($('#ganttBuscar').val() || '').trim().toLowerCase();
            const soloRiesgo = $('#ganttSoloRiesgo').is(':checked');
            const soloProximos = $('#ganttSoloProximos').is(':checked');
            const soloActivos = $('#ganttSoloActivos').is(':checked');

            const filtered = allGanttData.filter(r => {
                // Text search
                if (q) {
                    const searchText = `${r.objetivo || ''} ${r.milestone || r.titulo || ''} ${r.responsable || r.nombre_completo || ''}`.toLowerCase();
                    if (!searchText.includes(q)) return false;
                }

                // Risk filter
                if (soloRiesgo && getMilestoneStatus(r) !== 'risk') return false;

                // Upcoming filter (≤ 7 days)
                if (soloProximos) {
                    const diff = diffDaysFromToday(r.fecha_fin);
                    if (diff === null || diff > 7 || diff < 0) return false;
                }

                // Active filter (not completed)
                if (soloActivos && calculateProgress(r) === 100) return false;

                return true;
            });

            ganttData = filtered;
            updateStats(filtered);
            updateFilterTags();
            renderGantt(filtered);
        }

        /* =========================
           SISTEMA DE PRIORIDADES
        ========================= */

        // Mostrar modal de prioridades
        function showPriorityModal(button, milestoneId, currentPriority) {
            // Cerrar otros modales abiertos
            $('.gantt-priority-modal').removeClass('show');
            $('#modalOverlay').hide();

            const modalId = `priority-modal-${milestoneId}`;
            let modal = $(`#${modalId}`);

            // Si no existe el modal, crearlo
            if (modal.length === 0) {
                const modalHtml = `
                    <div class="gantt-priority-modal" id="${modalId}">
                        <div class="gantt-priority-options">
                            ${Object.entries(priorityLevels).map(([key, level]) => `
                                <div class="gantt-priority-option ${key === currentPriority ? 'selected' : ''}" 
                                     data-priority="${key}"
                                     data-milestone="${milestoneId}">
                                    <div class="gantt-priority-icon ${level.class}">
                                        <i class="fas ${level.icon}"></i>
                                    </div>
                                    <span class="gantt-priority-label">${level.label}</span>
                                    <div class="gantt-priority-dot" style="background: var(--${level.color})"></div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;

                $('body').append(modalHtml);
                modal = $(`#${modalId}`);

                // Posicionar modal
                const buttonRect = button[0].getBoundingClientRect();
                modal.css({
                    top: buttonRect.bottom + window.scrollY + 8,
                    left: buttonRect.left + window.scrollX
                });

                // Agregar event listeners a las opciones
                modal.find('.gantt-priority-option').on('click', function() {
                    const priority = $(this).data('priority');
                    const milestoneId = $(this).data('milestone');
                    updatePriority(milestoneId, priority, button);
                    modal.removeClass('show');
                    $('#modalOverlay').hide();
                });
            }

            // Mostrar modal y overlay
            modal.addClass('show');
            $('#modalOverlay').show();

            // Cerrar modal al hacer click fuera
            $('#modalOverlay').off('click').on('click', function() {
                modal.removeClass('show');
                $(this).hide();
            });

            // Cerrar modal con ESC
            $(document).off('keyup.priority-modal').on('keyup.priority-modal', function(e) {
                if (e.key === 'Escape') {
                    modal.removeClass('show');
                    $('#modalOverlay').hide();
                }
            });
        }

        // Actualizar prioridad
        function updatePriority(milestoneId, priority, button) {
            const level = priorityLevels[priority];

            // Actualizar badge
            button.html(`
                <i class="fas ${level.icon}"></i>
                ${level.label}
            `);
            button.removeClass('gantt-priority-critical gantt-priority-high gantt-priority-medium gantt-priority-low');
            button.addClass(level.class);

            console.log(`Actualizando prioridad del milestone ${milestoneId} a ${priority}`);

            $.ajax({
                url: '/hoshin_kanri/app/proyectos/actualizar_prioridad.php',
                method: 'POST',
                data: {
                    milestone_id: milestoneId,
                    prioridad: priority
                },
                success: function(response) {
                    showPriorityFeedback(priority, milestoneId);
                },
                error: function(xhr, status, error) {
                    console.error('Error al actualizar prioridad:', error);
                }
            });
        }

        // Mostrar feedback visual
        function showPriorityFeedback(priority, milestoneId) {
            const level = priorityLevels[priority];

            // Crear tooltip de feedback
            const tooltip = $(`
                <div class="gantt-priority-tooltip">
                    Prioridad cambiada a: ${level.label}
                </div>
            `);

            // Posicionar tooltip
            const button = $(`.gantt-priority-badge[data-milestone="${milestoneId}"]`);
            const rect = button[0].getBoundingClientRect();

            tooltip.css({
                top: rect.top + window.scrollY - 40,
                left: rect.left + window.scrollX + (rect.width / 2),
                transform: 'translateX(-50%)'
            });

            $('body').append(tooltip);

            // Animación de entrada
            setTimeout(() => {
                tooltip.addClass('show');
            }, 10);

            // Remover después de 2 segundos
            setTimeout(() => {
                tooltip.removeClass('show');
                setTimeout(() => {
                    tooltip.remove();
                }, 200);
            }, 2000);
        }

        /* =========================
           RENDERING CON ACORDEÓN PROPIO
        ========================= */

        function renderGantt(projects) {
            if (!projects || projects.length === 0) {
                $('#ganttBody').html(`
                    <div class="gantt-empty-state">
                        <div class="gantt-empty-state-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="mb-3">Sin resultados</h4>
                        <p class="text-muted mb-4">No se encontraron proyectos con los filtros aplicados.</p>
                        <button class="btn btn-outline-primary" onclick="$('#ganttReset').click()">
                            <i class="fas fa-filter-circle-xmark me-2"></i>Limpiar filtros
                        </button>
                    </div>
                `);
                return;
            }

            // Agrupar por objetivo
            const grupos = {};
            projects.forEach(p => {
                const key = `${p.objetivo_id || 0}|${p.objetivo || 'Sin objetivo'}`;
                if (!grupos[key]) grupos[key] = [];
                grupos[key].push(p);
            });

            let html = '';
            const entries = Object.entries(grupos);

            // Agregar controles de expansión si hay más de 1 objetivo
            if (entries.length > 1) {
                html += `
                    <div class="gantt-expand-controls">
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" id="expandAllBtn">
                                <i class="fas fa-expand-alt me-1"></i>Expandir todos
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="collapseAllBtn">
                                <i class="fas fa-compress-alt me-1"></i>Colapsar todos
                            </button>
                        </div>
                        <small class="text-muted">
                            ${entries.length} objetivos encontrados
                        </small>
                    </div>
                `;
            }

            html += `<div class="gantt-accordion">`;

            let index = 0;

            entries.forEach(([key, items]) => {
                const [, objetivoNombre] = key.split('|');
                const objetivoId = key.split('|')[0];
                const accordionId = `accordion-${objetivoId}-${index}`;
                const collapseId = `collapse-${objetivoId}-${index}`;

                // Calcular métricas del objetivo
                const totalItems = items.length;
                const completedItems = items.filter(r => calculateProgress(r) === 100).length;
                const riskItems = items.filter(r => getMilestoneStatus(r) === 'risk').length;
                const progress = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;

                const expandedAttr = 'false';
                const showClass = '';

                html += `
                    <div class="gantt-accordion-item" id="${accordionId}">
                        <div class="gantt-accordion-header">
                            <button class="gantt-accordion-button" 
                                    type="button" 
                                    aria-expanded="${expandedAttr}"
                                    aria-controls="${collapseId}"
                                    data-target="#${collapseId}">
                                <div class="gantt-objective-header">
                                    <h3 class="gantt-objective-title">${escapeHtml(objetivoNombre)}</h3>
                                    <div class="gantt-objective-stats">
                                        <span class="gantt-badge ${progress === 100 ? 'gantt-badge-success' : progress >= 70 ? 'gantt-badge-primary' : 'gantt-badge-warning'}">
                                            <i class="fas fa-chart-line me-1"></i>
                                            ${progress}% completado
                                        </span>
                                        <span class="gantt-badge gantt-badge-neutral">
                                            <i class="fas fa-flag me-1"></i>
                                            ${totalItems} milestones
                                        </span>
                                        ${completedItems > 0 ? `
                                            <span class="gantt-badge gantt-badge-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                ${completedItems} completados
                                            </span>
                                        ` : ''}
                                        ${riskItems > 0 ? `
                                            <span class="gantt-badge gantt-badge-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                ${riskItems} en riesgo
                                            </span>
                                        ` : ''}
                                    </div>
                                </div>
                                <i class="gantt-accordion-icon fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="gantt-accordion-body ${showClass}" 
                             id="${collapseId}"
                             aria-labelledby="${accordionId}">
                            ${renderMilestones(items)}
                        </div>
                    </div>
                `;

                index++;
            });

            html += `</div>`;

            $('#ganttBody').html(html);

            // Inicializar acordeón
            initAccordion();
        }

        function renderMilestones(items) {
            return items.map(r => {
                const progress = calculateProgress(r);
                const total = pickNumber(r, ['total_tareas', 'total'], 0);
                const fin = pickNumber(r, ['finalizadas', 'tareas_finalizadas'], 0);
                const status = getMilestoneStatus(r);
                const isComplete = progress === 100;
                const fueraDeTiempo = (isComplete && safeNumber(r.completadas_tarde ?? 0) > 0);
                const vencidas = safeNumber(r.vencidas ?? 0);
                const completadasTarde = safeNumber(r.completadas_tarde ?? 0);

                // Prioridad aleatoria para demo (en producción vendría de la BD)
                const priorityKey = r.prioridad;
                const priority = priorityLevels[priorityKey];

                const statusClass = status === 'risk' ? 'risk' :
                    status === 'completed' ? 'success' :
                    status === 'in-progress' ? 'warn' : '';

                const statusColor = status === 'risk' ? 'danger' :
                    status === 'completed' ? 'success' :
                    status === 'in-progress' ? 'warning' : 'primary';

                const milestoneId = r.milestone_id;

                return `
                    <div class="gantt-milestone-card ${statusClass}">
                        <div class="gantt-milestone-header">
                            <div class="flex-grow-1">
                                <h4 class="gantt-milestone-title">
                                    <i class="fas fa-flag me-2 text-primary"></i>
                                    ${escapeHtml(r.milestone || 'Sin título')}
                                </h4>
                                <div class="gantt-milestone-meta">
                                    <span class="gantt-badge">
                                        <i class="fas fa-user-circle me-1"></i>
                                        ${escapeHtml(r.responsable || 'Sin asignar')}
                                    </span>
                                    <span class="gantt-badge">
                                        <i class="fas fa-tasks me-1"></i>
                                        ${fin}/${total} tareas
                                    </span>
                                    <span class="gantt-badge">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        ${formatDateMX(r.fecha_inicio)} - ${formatDateMX(r.fecha_fin)}
                                    </span>
                                    ${(vencidas + completadasTarde) > 0 ? `
                                        <span class="gantt-badge gantt-badge-danger">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            ${vencidas + completadasTarde} vencidas
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                            <span class="gantt-badge gantt-badge-${statusColor}">
                                ${progress}%
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Progreso del proyecto</small>
                                <span class="${deadlineClass(r.fecha_fin, fueraDeTiempo)}">
                                    <i class="fas fa-clock me-1"></i>
                                    ${deadlineText(r.fecha_fin, fueraDeTiempo)}
                                </span>
                            </div>
                            <div class="gantt-timeline-track">
                                <div class="gantt-progress-bar ${statusClass}" style="width: ${progress}%"></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="gantt-milestone-actions">
                                <!-- Selector de prioridad -->
                                <div class="gantt-priority-selector">
                                    <span class="gantt-priority-badge ${priority.class}" 
                                          data-milestone="${milestoneId}"
                                          data-priority="${priorityKey}">
                                        <i class="fas ${priority.icon}"></i>
                                        ${priority.label}
                                    </span>
                                </div>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                Última actualización: ${formatDateMX(r.ultima_actualizacion || r.fecha_fin)}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        /* =========================
           MANEJO DEL ACORDEÓN PROPIO
        ========================= */

        function initAccordion() {
            // Toggle individual de acordeón
            $('.gantt-accordion-button').off('click').on('click', function() {
                const button = $(this);
                const targetId = button.data('target');
                const target = $(targetId);
                const isExpanded = button.attr('aria-expanded') === 'true';
                const icon = button.find('.gantt-accordion-icon');

                if (isExpanded) {
                    // Colapsar
                    target.slideUp(300);
                    button.attr('aria-expanded', 'false');
                    button.removeClass('expanded');
                    icon.css('transform', 'rotate(0deg)');
                } else {
                    // Expandir
                    target.slideDown(300);
                    button.attr('aria-expanded', 'true');
                    button.addClass('expanded');
                    icon.css('transform', 'rotate(180deg)');
                }
            });

            // Expandir todos
            $('#expandAllBtn').off('click').on('click', function() {
                $('.gantt-accordion-button').each(function() {
                    const button = $(this);
                    if (button.attr('aria-expanded') !== 'true') {
                        const targetId = button.data('target');
                        const target = $(targetId);
                        const icon = button.find('.gantt-accordion-icon');

                        target.slideDown(300);
                        button.attr('aria-expanded', 'true');
                        button.addClass('expanded');
                        icon.css('transform', 'rotate(180deg)');
                    }
                });
            });

            // Colapsar todos
            $('#collapseAllBtn').off('click').on('click', function() {
                $('.gantt-accordion-button').each(function() {
                    const button = $(this);
                    if (button.attr('aria-expanded') === 'true') {
                        const targetId = button.data('target');
                        const target = $(targetId);
                        const icon = button.find('.gantt-accordion-icon');

                        target.slideUp(300);
                        button.attr('aria-expanded', 'false');
                        button.removeClass('expanded');
                        icon.css('transform', 'rotate(0deg)');
                    }
                });
            });

            // Inicializar estado de iconos para los expandidos
            $('.gantt-accordion-button[aria-expanded="true"]').each(function() {
                $(this).find('.gantt-accordion-icon').css('transform', 'rotate(180deg)');
                $(this).addClass('expanded');
            });

            // Inicializar selectores de prioridad
            initPrioritySelectors();
        }

        /* =========================
           MANEJO DE PRIORIDADES
        ========================= */

        function initPrioritySelectors() {
            // Click en badge de prioridad
            $('.gantt-priority-badge').off('click').on('click', function(e) {
                e.stopPropagation();

                const button = $(this);
                const milestoneId = button.data('milestone');
                const currentPriority = button.data('priority');

                showPriorityModal(button, milestoneId, currentPriority);
            });

            // Hover en badge de prioridad (tooltip)
            $('.gantt-priority-badge').hover(
                function() {
                    const priorityKey = $(this).data('priority');
                    const level = priorityLevels[priorityKey];

                    // Crear tooltip
                    const tooltip = $(`
                        <div class="gantt-priority-tooltip">
                            ${level.description}
                        </div>
                    `);

                    // Posicionar tooltip
                    const rect = this.getBoundingClientRect();
                    tooltip.css({
                        top: rect.top + window.scrollY - 40,
                        left: rect.left + window.scrollX + (rect.width / 2),
                        transform: 'translateX(-50%)'
                    });

                    $('body').append(tooltip);

                    // Guardar referencia
                    $(this).data('tooltip', tooltip);

                    // Mostrar con delay
                    setTimeout(() => {
                        if (tooltip.is(':visible')) {
                            tooltip.addClass('show');
                        }
                    }, 300);
                },
                function() {
                    const tooltip = $(this).data('tooltip');
                    if (tooltip) {
                        tooltip.removeClass('show');
                        setTimeout(() => {
                            tooltip.remove();
                        }, 200);
                    }
                }
            );
        }

        /* =========================
           EVENT LISTENERS
        ========================= */

        $(document).ready(function() {
            // Iniciar carga
            toggleGanttLoading(true);
            setTimeout(loadGanttData, 100);

            // Event listeners para filtros
            $('#ganttBuscar').on('input', function() {
                clearTimeout($(this).data('timeout'));
                $(this).data('timeout', setTimeout(() => {
                    applyGanttFilters();
                }, 300));
            });

            $('input[type="checkbox"]').on('change', applyGanttFilters);

            $('#ganttReset').on('click', function() {
                $('#ganttBuscar').val('');
                $('#ganttSoloRiesgo, #ganttSoloProximos, #ganttSoloActivos').prop('checked', false);
                applyGanttFilters();
            });

            $('#refreshBtn').on('click', function() {
                const icon = $(this).find('i');
                icon.addClass('fa-spin');
                loadGanttData();
                setTimeout(() => {
                    icon.removeClass('fa-spin');
                }, 1000);
            });

            // Prevenir envío de formulario accidental
            $('#ganttBuscar').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    applyGanttFilters();
                }
            });
        });

    })(jQuery); // Pasar jQuery para evitar conflictos
</script>

<?php
require_once '../app/layout/footer.php';
