<?php
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/config.php';
}

$dsn = 'mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : 3306) . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT         => true,  // Reutiliza conexiones entre requests
    PDO::ATTR_EMULATE_PREPARES   => false, // Prepared statements nativos (más rápido)
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    PDO::MYSQL_ATTR_FOUND_ROWS   => true,  // Para SQL_CALC_FOUND_ROWS
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
} catch (PDOException $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'could not find driver') !== false) {
        die(
            '<h2>Error: falta el controlador PDO MySQL</h2>' .
            '<p>PHP no tiene cargada la extensión <strong>pdo_mysql</strong> (necesaria para MariaDB/MySQL).</p>' .
            '<p><strong>En Synology:</strong></p>' .
            '<ul>' .
            '<li>Abre <strong>Panel de control → Servicios web → PHP</strong> (o <strong>Web Station</strong> → PHP).</li>' .
            '<li>Activa la extensión <strong>PDO MySQL</strong> o <strong>pdo_mysql</strong> en la lista de extensiones.</li>' .
            '<li>Guarda y reinicia el servicio web si hace falta.</li>' .
            '</ul>' .
            '<p>Si no ves esa opción, en <strong>Centro de paquetes</strong> instala o actualiza <strong>PHP</strong> / <strong>MariaDB</strong> y asegúrate de que la versión de PHP incluya soporte MySQL.</p>' .
            '<p><small>Detalle: ' . htmlspecialchars($msg) . '</small></p>'
        );
    }
    die('Error de conexión: ' . htmlspecialchars($msg));
}

/**
 * Ejecuta una query con caché opcional.
 * @param string $sql Query SQL
 * @param array $params Parámetros para prepared statement
 * @param string|null $cacheKey Clave de caché (null = sin caché)
 * @param int $ttl Tiempo de vida del caché en segundos
 * @return array Resultados de la query
 */
function db_query_cached($sql, $params = [], $cacheKey = null, $ttl = 60) {
    global $pdo;
    
    if ($cacheKey !== null) {
        require_once __DIR__ . '/cache.php';
        $cached = cache_get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $results = $st->fetchAll(PDO::FETCH_ASSOC);
    
    if ($cacheKey !== null) {
        cache_set($cacheKey, $results, $ttl);
    }
    
    return $results;
}

/**
 * Obtiene un valor escalar con caché opcional.
 */
function db_scalar_cached($sql, $params = [], $cacheKey = null, $ttl = 60) {
    global $pdo;
    
    if ($cacheKey !== null) {
        require_once __DIR__ . '/cache.php';
        $cached = cache_get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $result = $st->fetchColumn();
    
    if ($cacheKey !== null) {
        cache_set($cacheKey, $result, $ttl);
    }
    
    return $result;
}
