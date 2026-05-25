<?php
/**
 * Script para ejecutar las migraciones de la base de datos
 * Ejecuta el archivo SQL de migraciones pendientes
 */

require_once __DIR__ . '/../app/core/db.php';

$conn = db();

// Leer y ejecutar el archivo de migración de dependencias
$sqlFile = __DIR__ . '/migracion_tarea_dependencias.sql';

if (!file_exists($sqlFile)) {
    die('❌ Archivo de migración no encontrado: ' . $sqlFile);
}

echo "<h2>Ejecutando migración de Dependencias de Tareas...</h2>";

$sql = file_get_contents($sqlFile);

// Remover comentarios de línea
$sql = preg_replace('/--.*$/m', '', $sql);

// Remover comentarios de bloque
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// Dividir por punto y coma
$statements = array_filter(
    explode(';', $sql),
    function ($statement) {
        return !empty(trim($statement));
    }
);

$executed = 0;
$errors = [];

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    $statement = $statement . ';';
    
    echo "Ejecutando: <code>" . substr($statement, 0, 80) . "...</code>";
    
    if (!$conn->query($statement)) {
        $errors[] = $conn->error;
        echo " ❌ Error: " . $conn->error . "<br>";
    } else {
        echo " ✅ OK<br>";
        $executed++;
    }
}

echo "<hr>";
echo "<h3>Resumen:</h3>";
echo "✅ Consultas ejecutadas: $executed<br>";

if (count($errors) > 0) {
    echo "❌ Errores encontrados: " . count($errors) . "<br>";
    foreach ($errors as $error) {
        echo "   - " . $error . "<br>";
    }
} else {
    echo "✅ ¡Migración completada exitosamente!<br>";
}

// Verificar que las tablas se crearon
echo "<hr>";
echo "<h3>Verificación de tablas:</h3>";

$result = $conn->query("SHOW TABLES LIKE 'tarea_dependencias%'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_row()) {
        echo "✅ Tabla creada: " . $row[0] . "<br>";
    }
} else {
    echo "❌ No se encontraron tablas de dependencias<br>";
}

$conn->close();
?>
