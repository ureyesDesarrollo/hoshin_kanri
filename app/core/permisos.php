<?php
function puede($modulo)
{
    $rol = $_SESSION['usuario']['rol'] ?? '';

    $permisos = [
        'admin' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'usuarios', 'responsables', 'colaboradores', 'mi_equipo', 'proyectos', 'proyectos_lic', 'notificaciones'],
        'director' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'responsables', 'colaboradores', 'proyectos', 'proyectos_lic', 'notificaciones'],
        'gerente' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'mis_tareas', 'mi_equipo', 'notificaciones'],
        'jefatura' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'mis_tareas', 'notificaciones'],
        'colaborador' => ['dashboard', 'mis_tareas', 'notificaciones'],
        'licenciado' => ['proyectos_lic'],
    ];
    return in_array($modulo, $permisos[$rol] ?? []);
}
