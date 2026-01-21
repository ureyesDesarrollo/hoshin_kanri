<?php

/**
 * reporte_snapshot_kpi.php
 *
 * Genera y envía por correo un reporte HTML del ÚLTIMO snapshot (kpi_responsable_semanal).
 * Ajuste solicitado:
 *   - "Nivel de compromiso" = (cumplidas_a_tiempo) / (cumplidas_a_tiempo + vencidas_no_cumplidas + completadas_tarde) * 100
 *     (equivalente a: 100 - (fallas/(ok+fallas))*100)
 *
 * Nota: Aquí NO usamos ks.porcentaje como fuente de verdad; lo recalculamos.
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/EmailSender.php';

$conn = db();
$empresaId = 1;

/* =========================
   Helpers
========================= */
function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function n($v)
{
  return ($v === null || $v === '') ? 0 : (int)$v;
}

/**
 * Compromiso = OK / (OK + fallas) * 100
 * fallas = vencidas + completadas_tarde
 */
function calcCompromisoPct($ok, $vencidas, $tarde)
{
  $ok = n($ok);
  $fallas = n($vencidas) + n($tarde);
  $den = $ok + $fallas;
  if ($den <= 0) return 0;
  $pct = (int)round(($ok / $den) * 100, 0);
  if ($pct < 0) $pct = 0;
  if ($pct > 100) $pct = 100;
  return $pct;
}

/* =========================
   1) Último snapshot
========================= */
$sqlLast = "
SELECT
  ks.semana_inicio,
  ks.semana_fin,
  MAX(ks.generado_en) AS generado_en
FROM kpi_responsable_semanal ks
WHERE ks.empresa_id = ?
  AND ks.semana_inicio <> '0000-00-00'
";
$stmt = $conn->prepare($sqlLast);
if (!$stmt) {
  http_response_code(500);
  echo "Error prepare last: " . h($conn->error);
  exit;
}
$stmt->bind_param('i', $empresaId);
$stmt->execute();
$last = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$last || empty($last['generado_en'])) {
  echo "<h3>Sin snapshots</h3><p>No hay registros en kpi_responsable_semanal para esta empresa.</p>";
  exit;
}

$semanaInicio = $last['semana_inicio'];
$semanaFin    = $last['semana_fin'];
$generadoEn   = $last['generado_en'];

/* =========================
   2) Traer detalle del snapshot
   Nota: no confiamos en ks.porcentaje; recalculamos compromiso.
========================= */
$sql = "
SELECT
  u.usuario_id,
  u.nombre_completo,
  COALESCE(a.nombre, 'Sin área') AS area_nombre,
  r.nombre AS rol,

  ks.kpi_id,
  ks.semana_inicio,
  ks.semana_fin,
  ks.total_tareas,
  ks.cumplidas_a_tiempo,
  ks.vencidas_no_cumplidas,
  ks.completadas_tarde,
  ks.porcentaje AS porcentaje_snapshot,
  ks.generado_en

FROM kpi_responsable_semanal ks
JOIN usuarios u ON u.usuario_id = ks.usuario_id
JOIN usuarios_empresas ue ON ue.usuario_id = u.usuario_id AND ue.empresa_id = ks.empresa_id AND ue.activo = 1
JOIN roles r ON r.rol_id = ue.rol_id
LEFT JOIN areas a ON a.area_id = ue.area_id

WHERE ks.empresa_id = ?
  AND ks.generado_en = ?
  AND ks.semana_inicio <> '0000-00-00'
  AND r.nombre = 'GERENTE'
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo "Error prepare detalle: " . h($conn->error);
  exit;
}

$stmt->bind_param('is', $empresaId, $generadoEn);
$stmt->execute();
$rawRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$rawRows || count($rawRows) === 0) {
  echo "<h3>Sin filas en el último snapshot</h3><p>Generado en: " . h($generadoEn) . "</p>";
  exit;
}

/* =========================
   3) Recalcular compromiso por fila + ordenar (mejor -> peor)
========================= */
$rows = [];
foreach ($rawRows as $r) {
  $ok = n($r['cumplidas_a_tiempo']);
  $ven = n($r['vencidas_no_cumplidas']);
  $tarde = n($r['completadas_tarde']);

  $r['fallas'] = $ven + $tarde;
  $r['compromiso_pct'] = calcCompromisoPct($ok, $ven, $tarde);

  // compat: si alguien quiere ver el % que guardaba el snapshot
  $r['porcentaje_snapshot'] = n($r['porcentaje_snapshot']);

  $rows[] = $r;
}

/**
 * Orden:
 * 1) compromiso_pct desc
 * 2) fallas asc
 * 3) (ok + fallas) desc  (más volumen arriba si empatan)
 * 4) nombre asc
 */
usort($rows, function ($a, $b) {
  if ($a['compromiso_pct'] !== $b['compromiso_pct']) return $b['compromiso_pct'] <=> $a['compromiso_pct'];
  if ($a['fallas'] !== $b['fallas']) return $a['fallas'] <=> $b['fallas'];
  $denA = n($a['cumplidas_a_tiempo']) + n($a['fallas']);
  $denB = n($b['cumplidas_a_tiempo']) + n($b['fallas']);
  if ($denA !== $denB) return $denB <=> $denA;
  return strcmp((string)$a['nombre_completo'], (string)$b['nombre_completo']);
});

/* =========================
   4) Resumen dirección (con tu fórmula)
========================= */
$N = count($rows);

$sumOk = 0;
$sumFallas = 0;
$sumDen = 0;

$sumPctSimple = 0;     // promedio simple de compromiso_pct
$sumPctPond = 0;       // ponderado por denominador (ok+fallas)
$sumPeso = 0;

foreach ($rows as $r) {
  $ok = n($r['cumplidas_a_tiempo']);
  $fallas = n($r['fallas']);
  $den = $ok + $fallas;

  $sumOk += $ok;
  $sumFallas += $fallas;
  $sumDen += $den;

  $pct = n($r['compromiso_pct']);
  $sumPctSimple += $pct;

  if ($den > 0) {
    $sumPctPond += ($pct * $den);
    $sumPeso += $den;
  }
}

$promedioSimple = ($N > 0) ? (int)round($sumPctSimple / $N, 0) : 0;

$promedioPonderado = ($sumPeso > 0) ? (int)round($sumPctPond / $sumPeso, 0) : 0;

$compromisoGlobal = ($sumDen > 0) ? (int)round(($sumOk / $sumDen) * 100, 0) : 0;

/* =========================
   5) TOPs
========================= */
$topN = 5;
$topMejores = array_slice($rows, 0, min($topN, $N));
$topPeores  = array_slice(array_reverse($rows), 0, min($topN, $N));

/* =========================
   6) Render helpers
========================= */
function badgeClassByFallasPct($fallas, $pct)
{
  if ((int)$fallas > 0) return 'risk';
  if ((int)$pct < 100) return 'warn';
  return 'ok';
}

function renderTopTable($items)
{
  $html = "
  <table>
    <thead>
      <tr>
        <th style='width:34%'>Responsable</th>
        <th style='width:18%'>Área</th>
        <th style='width:10%'>OK</th>
        <th style='width:10%'>Fallas</th>
        <th style='width:10%'>Compromiso</th>
        <th style='width:18%'>Desglose</th>
      </tr>
    </thead>
    <tbody>
  ";

  foreach ($items as $r) {
    $ok = n($r['cumplidas_a_tiempo']);
    $ven = n($r['vencidas_no_cumplidas']);
    $tarde = n($r['completadas_tarde']);
    $fallas = n($r['fallas']);
    $pct = n($r['compromiso_pct']);

    $cls = badgeClassByFallasPct($fallas, $pct);

    $html .= "
      <tr>
        <td><b>" . h($r['nombre_completo']) . "</b><div class='muted'>" . h($r['rol']) . "</div></td>
        <td>" . h($r['area_nombre']) . "</td>
        <td><span class='badge ok'>" . h($ok) . "</span></td>
        <td><span class='badge {$cls}'>" . h($fallas) . "</span></td>
        <td><span class='badge-pct {$cls}'>" . h($pct) . "%</span></td>
        <td class='muted'>Vencidas: " . h($ven) . " · Tarde: " . h($tarde) . "</td>
      </tr>
    ";
  }

  $html .= "</tbody></table>";
  return $html;
}

function renderDetalleTable($items)
{
  $html = "
  <table>
    <thead>
      <tr>
        <th style='width:4%'>#</th>
        <th style='width:28%'>Responsable</th>
        <th style='width:16%'>Área</th>
        <th style='width:10%'>OK</th>
        <th style='width:10%'>Vencidas</th>
        <th style='width:10%'>Tarde</th>
        <th style='width:10%'>Fallas</th>
        <th style='width:12%'>Compromiso</th>
      </tr>
    </thead>
    <tbody>
  ";

  $i = 1;
  foreach ($items as $r) {
    $ok = n($r['cumplidas_a_tiempo']);
    $ven = n($r['vencidas_no_cumplidas']);
    $tarde = n($r['completadas_tarde']);
    $fallas = n($r['fallas']);
    $pct = n($r['compromiso_pct']);

    $cls = badgeClassByFallasPct($fallas, $pct);

    $html .= "
      <tr>
        <td class='rank'>" . h($i) . "</td>
        <td><b>" . h($r['nombre_completo']) . "</b><div class='muted'>" . h($r['rol']) . "</div></td>
        <td>" . h($r['area_nombre']) . "</td>
        <td><span class='badge ok'>" . h($ok) . "</span></td>
        <td><span class='badge " . ($ven > 0 ? 'risk' : 'neutral') . "'>" . h($ven) . "</span></td>
        <td><span class='badge " . ($tarde > 0 ? 'warn' : 'neutral') . "'>" . h($tarde) . "</span></td>
        <td><span class='badge {$cls}'>" . h($fallas) . "</span></td>
        <td><span class='badge-pct {$cls}'>" . h($pct) . "%</span></td>
      </tr>
    ";
    $i++;
  }

  $html .= "</tbody></table>";
  return $html;
}

/* =========================
   7) HTML
========================= */
$css = "
<style>
  :root { --primary:#006ec7; --success:#28a745; --warning:#ffc107; --danger:#dc3545; }
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#f6f8fb;margin:0;padding:28px;color:#1f2937}
  .wrap{max-width:1200px;margin:0 auto}
  .header{background:linear-gradient(135deg,var(--primary),#004d8c);color:#fff;padding:26px;border-radius:16px 16px 0 0}
  .header h1{margin:0 0 8px 0;font-size:24px}
  .header .meta{display:flex;gap:18px;flex-wrap:wrap;font-size:13px;opacity:.95}
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:0 0 16px 16px;padding:22px;margin-bottom:18px;box-shadow:0 2px 10px rgba(0,0,0,.04)}
  .card.round{border-radius:16px}
  .muted{color:#64748b;font-size:12px}
  .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:14px}
  .kpi{border:1px solid #e5e7eb;border-radius:12px;padding:14px;background:#fafafa;text-align:center}
  .kpi .lbl{font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;font-weight:700}
  .kpi .val{font-size:28px;font-weight:800;color:var(--primary);margin-top:6px}
  .kpi .val.success{color:var(--success)} .kpi .val.warn{color:#b45309} .kpi .val.risk{color:var(--danger)}
  table{width:100%;border-collapse:collapse;font-size:13px;margin-top:14px}
  th,td{padding:12px 10px;border-bottom:1px solid #eef2f7;vertical-align:middle}
  th{background:#f8fafc;color:#475569;font-size:11px;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e5e7eb}
  tr:hover{background:rgba(0,110,199,.03)}
  .rank{font-weight:800;color:var(--primary)}
  .badge{display:inline-block;padding:4px 10px;border-radius:999px;font-weight:800;font-size:12px}
  .badge.ok{background:rgba(40,167,69,.12);color:#047857;border:1px solid rgba(40,167,69,.2)}
  .badge.warn{background:rgba(255,193,7,.14);color:#b45309;border:1px solid rgba(255,193,7,.25)}
  .badge.risk{background:rgba(220,53,69,.14);color:#b91c1c;border:1px solid rgba(220,53,69,.25)}
  .badge.neutral{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
  .badge-pct{display:inline-block;padding:5px 12px;border-radius:999px;font-weight:900;font-size:13px}
  .badge-pct.ok{background:var(--success);color:#fff}
  .badge-pct.warn{background:var(--warning);color:#111827}
  .badge-pct.risk{background:var(--danger);color:#fff}
  .section-title{font-size:18px;font-weight:900;color:var(--primary);margin:0 0 4px 0}
  .section-sub{margin:0;color:#64748b;font-size:12px}
</style>
";

$body  = "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'>{$css}</head><body><div class='wrap'>";

$body .= "
  <div class='header'>
    <h1>📊 Reporte KPI Semanal</h1>
    <div class='meta'>
      <div>📅 <b>" . h($semanaInicio) . "</b> → <b>" . h($semanaFin) . "</b></div>
      <div>🕒 Generado: <b>" . h($generadoEn) . "</b></div>
      <div>👥 Responsables: <b>" . h($N) . "</b></div>
    </div>
    <div class='muted' style='margin-top:10px;color:rgba(255,255,255,.85)'>
      Fórmula compromiso: <b>OK / (OK + Vencidas + Tarde)</b>
    </div>
  </div>

  <div class='card'>
    <div class='kpi-grid'>
      <div class='kpi'>
        <div class='lbl'>OK (a tiempo)</div>
        <div class='val success'>" . h($sumOk) . "</div>
      </div>
      <div class='kpi'>
        <div class='lbl'>Fallas</div>
        <div class='val " . ($sumFallas > 0 ? 'risk' : 'success') . "'>" . h($sumFallas) . "</div>
        <div class='muted'>Vencidas + Tarde</div>
      </div>
      <div class='kpi'>
        <div class='lbl'>Compromiso global</div>
        <div class='val " . ($compromisoGlobal >= 90 ? 'success' : ($compromisoGlobal >= 70 ? 'warn' : 'risk')) . "'>" . h($compromisoGlobal) . "%</div>
        <div class='muted'>OK / (OK + Fallas)</div>
      </div>
      <div class='kpi'>
        <div class='lbl'>Promedio simple</div>
        <div class='val'>" . h($promedioSimple) . "%</div>
        <div class='muted'>Promedio por gerente</div>
      </div>
      <div class='kpi'>
        <div class='lbl'>Promedio ponderado</div>
        <div class='val'>" . h($promedioPonderado) . "%</div>
        <div class='muted'>Ponderado por (OK+Fallas)</div>
      </div>
    </div>
  </div>

  <div class='card round'>
    <div class='section-title'>🏆 Top {$topN} mejores</div>
    <p class='section-sub'>Ordenado por compromiso (desc) y fallas (asc).</p>
    " . renderTopTable($topMejores) . "
  </div>

  <div class='card round'>
    <div class='section-title'>⚠️ Top {$topN} peores</div>
    <p class='section-sub'>Menor compromiso / más fallas.</p>
    " . renderTopTable($topPeores) . "
  </div>

  <div class='card round'>
    <div class='section-title'>📋 Detalle completo</div>
    <p class='section-sub'>Ranking completo de mejor a peor (según compromiso).</p>
    " . renderDetalleTable($rows) . "
  </div>
";

$body .= "</div></body></html>";

/* =========================
   8) Enviar correo
========================= */
$mail = new MailSender();

$subject = "📊 Reporte KPI Semanal - {$semanaInicio} al {$semanaFin}";
$to = ['desarrollo@progel.com.mx','gerentecapitalhumano@progel.com.mx'];

if ($mail->sendMail($subject, $body, $to)) {
  echo "Correo enviado exitosamente";
} else {
  echo "Error al enviar el correo";
}
