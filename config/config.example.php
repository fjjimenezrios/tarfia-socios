<?php
/**
 * Copia este archivo como config.php y rellena los valores.
 * config.php no debe subirse a repositorios públicos.
 */

// Base de datos (mismo Synology → localhost; puerto 3307 si no es el por defecto)
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'TarfiaSocios');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');

// Login de la aplicación (usuario único)
define('AUTH_USER', 'admin');
// Generar hash con: php -r "echo password_hash('tu_contraseña', PASSWORD_DEFAULT);"
define('AUTH_PASS_HASH', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

// Caché (opcional): reduce llamadas a la base de datos. TTL en segundos.
// define('CACHE_TTL_HOME', 60);   // Inicio: 1 min
// define('CACHE_TTL_LISTS', 300); // Dropdowns familias/niveles: 5 min
// define('CACHE_TTL_PAGE', 60);   // Listados paginados: 1 min
