<?php
if (!defined('DB_SQLITE_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $options);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
} catch (PDOException $e) {
    $msg = $e->getMessage();
    die('Error de conexión SQLite: ' . htmlspecialchars($msg));
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
