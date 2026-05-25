<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

// Verificar si es para selector de dependencias
$paraDependencia = (int)($_GET['para_dependencia'] ?? 0);
$excluir = (int)($_GET['excluir'] ?? 0);
$milestoneId = (int)($_GET['milestone_id'] ?? 0);

if ($paraDependencia) {
  // Retornar todas las tareas sin paginar para el selector de dependencias
  $whereDep = ["e.empresa_id = ?"];
  $paramsDep = [$empresaId];
  $typesDep = "i";

  if ($excluir > 0) {
    $whereDep[] = "t.tarea_id != ?";
    $paramsDep[] = $excluir;
    $typesDep .= "i";
  }

  // Filtrar por milestone si se especifica
  if ($milestoneId > 0) {
    $whereDep[] = "t.milestone_id = ?";
    $paramsDep[] = $milestoneId;
    $typesDep .= "i";
  }

  $whereSqlDep = implode(" AND ", $whereDep);

  $sqlDep = "
  SELECT
    t.tarea_id,
    t.titulo,
    t.fecha_inicio,
    t.fecha_fin,
    t.completada,
    u.nombre_completo AS responsable,
    m.titulo AS milestone_titulo
  FROM tareas t
  JOIN milestones m ON m.milestone_id = t.milestone_id
  JOIN estrategias e ON e.estrategia_id = m.estrategia_id
  JOIN usuarios u ON u.usuario_id = t.responsable_usuario_id
  WHERE {$whereSqlDep}
  ORDER BY t.titulo ASC
  ";

  $stmtDep = $conn->prepare($sqlDep);
  $stmtDep->bind_param($typesDep, ...$paramsDep);
  $stmtDep->execute();
  $tareasDep = $stmtDep->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmtDep->close();

  echo json_encode([
    'success' => true,
    'tareas' => $tareasDep
  ]);
  exit;
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// Filtros
$q            = trim((string)($_GET['q'] ?? ''));              // titulo
$responsable  = trim((string)($_GET['responsable'] ?? ''));    // nombre_completo

$where = [];
$params = [];
$types = "";

// Base obligatoria
$where[] = "e.empresa_id = ?";
$params[] = $empresaId;
$types .= "i";

$where[] = "u.activo = 1";

// Buscar por título
if ($q !== '') {
  $where[] = "t.titulo LIKE ?";
  $params[] = "%{$q}%";
  $types .= "s";
}

// Buscar por responsable (nombre)
if ($responsable !== '') {
  $where[] = "u.nombre_completo LIKE ?";
  $params[] = "%{$responsable}%";
  $types .= "s";
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

/* =========================
   TOTAL (con filtros)
========================= */
$sqlTotal = "
SELECT COUNT(*) AS total
FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
JOIN usuarios u ON u.usuario_id = t.responsable_usuario_id
JOIN usuarios_empresas ue
  ON ue.usuario_id = u.usuario_id
 AND ue.empresa_id = e.empresa_id
 AND ue.activo = 1
{$whereSql}
";
$stmt = $conn->prepare($sqlTotal);

if ($types !== "") {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO (con filtros)
========================= */
$sqlList = "
SELECT
  t.tarea_id,
  t.titulo,
  t.descripcion,
  t.fecha_inicio,
  t.fecha_fin,
  t.completada,
  t.completada_en,
  t.creado_en,
  u.nombre_completo AS responsable
FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
JOIN usuarios u ON u.usuario_id = t.responsable_usuario_id
JOIN usuarios_empresas ue
  ON ue.usuario_id = u.usuario_id
 AND ue.empresa_id = e.empresa_id
 AND ue.activo = 1
{$whereSql}
ORDER BY t.tarea_id DESC
LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sqlList);

// Agregamos limit/offset al final
$paramsList = $params;
$typesList  = $types . "ii";
$paramsList[] = $perPage;
$paramsList[] = $offset;

$stmt->bind_param($typesList, ...$paramsList);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
  'success' => true,
  'filters' => [
    'q' => $q,
    'responsable' => $responsable
  ],
  'data' => $data,
  'pagination' => [
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => (int)ceil($total / $perPage)
  ]
]);
exit;
