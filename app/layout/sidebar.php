<?php
require_once __DIR__ . '/../core/permisos.php';
require_once __DIR__ . '/../config.php';
$paginaActual = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div class="user-avatar">
            <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
        </div>
        <div class="user-info">
            <strong><?= htmlspecialchars($user['nombre']) ?></strong>
            <span class="badge bg-primary"><?= htmlspecialchars($user['rol']) ?></span>
        </div>
    </div>
    <ul class="nav-menu">

        <?php if (puede('proyectos_lic')): ?>
            <li class="nav-item">
                <a href="proyectos_lic.php"
                    class="nav-link <?= $paginaActual === 'proyectos_lic.php' ? 'active' : '' ?>">
                    <i class="fas fa-flag-checkered"></i> Proyectos
                </a>
            </li>
        <?php endif; ?>

        <?php if (puede('dashboard')): ?>
            <li class="nav-item">
                <a href="dashboard.php"
                    class="nav-link <?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
        <?php endif; ?>

        <?php if (puede('notificaciones')): ?>
            <li class="nav-item">
                <a href="notificaciones.php"
                    class="nav-link <?= $paginaActual === 'notificaciones.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i> Notificaciones
                </a>
            </li>
        <?php endif; ?>

        <?php if (PERIODO_CAPTURA && puede('objetivos')): ?>
            <li class="nav-item">
                <a href="objetivos.php"
                    class="nav-link <?= $paginaActual === 'objetivos.php' ? 'active' : '' ?>">
                    <i class="fas fa-bullseye"></i> Objetivos
                </a>
            </li>
        <?php endif; ?>

        <?php if (PERIODO_CAPTURA && puede('estrategias')): ?>
            <li class="nav-item">
                <a href="estrategias.php"
                    class="nav-link <?= $paginaActual === 'estrategias.php' ? 'active' : '' ?>">
                    <i class="fas fa-chess"></i> Estrategias
                </a>
            </li>
        <?php endif; ?>

        <?php if (PERIODO_CAPTURA && puede('milestones')): ?>
            <li class="nav-item">
                <a href="milestones.php"
                    class="nav-link <?= $paginaActual === 'milestones.php' ? 'active' : '' ?>">
                    <i class="fas fa-flag-checkered"></i> Milestones
                </a>
            </li>
        <?php endif; ?>

        <?php if (puede('proyectos')): ?>
            <li class="nav-item">
                <a href="proyectos.php"
                    class="nav-link <?= $paginaActual === 'proyectos.php' ? 'active' : '' ?>">
                    <i class="fas fa-flag-checkered"></i> Proyectos - Dirección
                </a>
            </li>
        <?php endif; ?>

        <?php if (puede('tareas')): ?>
            <li class="nav-item">
                <a href="tareas.php"
                    class="nav-link <?= $paginaActual === 'tareas.php' ? 'active' : '' ?>">
                    <i class="fas fa-tasks"></i> Tareas
                </a>
            </li>
        <?php endif; ?>

        <?php if (puede('colaboradores')): ?>
            <li class="nav-item">
                <a href="colaboradores.php"
                    class="nav-link <?= $paginaActual === 'colaboradores.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Colaboradores
                </a>
            </li>
        <?php endif; ?>

        <?php if (puede('usuarios')): ?>
            <li class="nav-item">
                <a href="usuarios.php"
                    class="nav-link <?= $paginaActual === 'usuarios.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Usuarios
                </a>
            </li>
        <?php endif; ?>

        <?php if (puede('mi_equipo')): ?>
            <li class="nav-item">
                <a href="mi_equipo.php"
                    class="nav-link <?= $paginaActual === 'mi_equipo.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-people-group"></i> Mi equipo
                </a>
            </li>
        <?php endif; ?>

        <?php if (puede('mis_tareas')): ?>
            <li class="nav-item">
                <a href="mis_tareas.php"
                    class="nav-link <?= $paginaActual === 'mis_tareas.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> Mis tareas
                </a>
            </li>
        <?php endif; ?>

    </ul>
</aside>