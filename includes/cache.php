<?php
/**
 * Caché en archivos para reducir llamadas a la base de datos.
 * TTL en segundos. Claves solo letras, números y guión bajo.
 */
if (!defined('CACHE_DIR')) {
    define('CACHE_DIR', __DIR__ . '/../cache');
}
if (!defined('CACHE_TTL_HOME')) {
    define('CACHE_TTL_HOME', 60);      // Inicio: 1 minuto
}
if (!defined('CACHE_TTL_LISTS')) {
    define('CACHE_TTL_LISTS', 300);    // Dropdowns (familias, niveles): 5 min
}
if (!defined('CACHE_TTL_PAGE')) {
    define('CACHE_TTL_PAGE', 60);       // Listados paginados: 1 minuto
}

function cache_key_safe($key) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
}

function cache_get($key) {
    $file = CACHE_DIR . '/' . cache_key_safe($key) . '.cache';
    if (!is_file($file)) {
        return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false) {
        return null;
    }
    $payload = @unserialize($raw);
    if (!is_array($payload) || !isset($payload['expires'], $payload['data'])) {
        @unlink($file);
        return null;
    }
    if (time() > $payload['expires']) {
        @unlink($file);
        return null;
    }
    return $payload['data'];
}

function cache_set($key, $data, $ttl = 60) {
    $dir = CACHE_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }
    $file = $dir . '/' . cache_key_safe($key) . '.cache';
    $payload = ['expires' => time() + (int) $ttl, 'data' => $data];
    return @file_put_contents($file, serialize($payload), LOCK_EX) !== false;
}

function cache_delete($key) {
    $file = CACHE_DIR . '/' . cache_key_safe($key) . '.cache';
    if (is_file($file)) {
        @unlink($file);
    }
}

/** Borra todas las entradas cuyo id empiece por $prefix (ej: 'socios_' o 'home') */
function cache_delete_prefix($prefix) {
    $dir = CACHE_DIR;
    if (!is_dir($dir)) {
        return;
    }
    $safe = cache_key_safe($prefix);
    $len = strlen($safe);
    foreach (glob($dir . '/*.cache') as $file) {
        $base = basename($file, '.cache');
        if ($len === 0 || substr($base, 0, $len) === $safe) {
            @unlink($file);
        }
    }
}

/** Invalidar todo lo que deba actualizarse al añadir/modificar un socio */
function cache_invalidate_on_new_socio() {
    cache_delete('home');
    cache_delete('cuotas_data');
    cache_delete('socios_total');
    cache_delete_prefix('socios_');
}

/** Invalidar todo lo que deba actualizarse al añadir/modificar una familia */
function cache_invalidate_on_new_familia() {
    cache_delete('home');
    cache_delete('cuotas_data');
    cache_delete('familias_total');
    cache_delete_prefix('familias_');
}

/** Invalidar todo el caché */
function cache_invalidate_all() {
    cache_delete_prefix('');
}
