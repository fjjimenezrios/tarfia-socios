<?php
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Valores en campo 'Socio/Ex Socio'</h1>";

// Obtener todos los valores únicos
$st = $pdo->query("
    SELECT `Socio/Ex Socio` AS estado, COUNT(*) AS total
    FROM `Socios`
    GROUP BY `Socio/Ex Socio`
    ORDER BY total DESC
");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Valor</th><th>Cantidad</th></tr>";

$resultados = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($resultados as $r) {
    $valor = $r['estado'] ?? '(NULL)';
    $valorDisplay = htmlspecialchars($valor);
    if ($valor === null) $valorDisplay = '<em>(NULL)</em>';
    if ($valor === '') $valorDisplay = '<em>(vacío)</em>';
    
    echo "<tr>";
    echo "<td><code>" . $valorDisplay . "</code></td>";
    echo "<td>" . $r['total'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Total de socios: " . array_sum(array_column($resultados, 'total')) . "</h2>";

echo "<hr><h2>Distribución por Nivel y Estado</h2>";

$st2 = $pdo->query("
    SELECT s.`Nivel`, n.`Curso`, s.`Socio/Ex Socio` AS estado, COUNT(*) AS total
    FROM `Socios` s
    LEFT JOIN `Niveles-Cursos` n ON n.`Nivel` = s.`Nivel`
    GROUP BY s.`Nivel`, n.`Curso`, s.`Socio/Ex Socio`
    ORDER BY s.`Nivel`, s.`Socio/Ex Socio`
");

echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Nivel</th><th>Curso</th><th>Estado</th><th>Cantidad</th></tr>";

foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "<tr>";
    echo "<td>" . ($r['Nivel'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($r['Curso'] ?? '-') . "</td>";
    echo "<td><code>" . htmlspecialchars($r['estado'] ?? '(NULL)') . "</code></td>";
    echo "<td>" . $r['total'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='home.php'>← Volver</a></p>";
