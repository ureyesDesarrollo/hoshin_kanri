<?php
function puede($modulo)
{
  $rol = $_SESSION['usuario']['rol'] ?? '';

  $permisos = [
    'admin' => [
      'dashboard',
      'objetivos',
      'estrategias',
      'milestones',
      'tareas',
      'usuarios',
      'responsables',
      'colaboradores',
      'mi_equipo',
      'proyectos',
      'proyectos_lic',
      'gestion_directiva',
      'catalogos_directivos',
      'notificaciones'
    ],
    'director' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'responsables', 'colaboradores', 'proyectos', 'proyectos_lic', 'gestion_directiva', 'catalogos_directivos', 'notificaciones'],
    'gerente' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'mis_tareas', 'mi_equipo', 'notificaciones', 'gestion_directiva'],
    'jefatura' => ['dashboard', 'objetivos', 'estrategias', 'milestones', 'tareas', 'mis_tareas', 'notificaciones'],
    'colaborador' => ['dashboard', 'mis_tareas', 'notificaciones', 'milestones', 'tareas'],
    'licenciado' => ['proyectos_lic'],
  ];
  return in_array($modulo, $permisos[$rol] ?? []);
}
