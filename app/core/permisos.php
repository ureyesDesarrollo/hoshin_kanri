<?php
function puede($modulo)
{
    $rol = $_SESSION['usuario']['rol'] ?? '';

    $permisos = [
        'admin' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'usuarios', 'responsables', 'colaboradores'],
        'director' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'responsables', 'colaboradores'],
        'gerente' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'mis_tareas'],
        'jefatura' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'mis_tareas'],
        'colaborador' => ['dashboard', 'mis_tareas']
    ];
    return in_array($modulo, $permisos[$rol] ?? []);
}
