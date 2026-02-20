-- ============================================================
-- OPTIMIZACIONES AVANZADAS PARA MARIADB
-- Ejecutar desde phpMyAdmin o terminal MariaDB
-- Nota: Algunas opciones requieren reinicio del servidor
-- ============================================================

-- ============================================================
-- PARTE 1: OPTIMIZACIONES DE TABLAS (ejecutar periódicamente)
-- ============================================================

-- Analizar estadísticas de las tablas (ayuda al optimizador de queries)
ANALYZE TABLE `Socios`;
ANALYZE TABLE `Familias Socios`;
ANALYZE TABLE `Niveles-Cursos`;

-- Optimizar fragmentación de tablas (ejecutar mensualmente)
OPTIMIZE TABLE `Socios`;
OPTIMIZE TABLE `Familias Socios`;

-- ============================================================
-- PARTE 2: VERIFICAR ÍNDICES EXISTENTES
-- ============================================================

-- Ver índices actuales de Socios
SHOW INDEX FROM `Socios`;

-- Ver índices actuales de Familias
SHOW INDEX FROM `Familias Socios`;

-- ============================================================
-- PARTE 3: ÍNDICE FULLTEXT PARA BÚSQUEDAS (opcional)
-- Útil si haces muchas búsquedas por texto
-- ============================================================

-- Índice fulltext para búsquedas en Socios
-- ALTER TABLE `Socios` ADD FULLTEXT INDEX idx_socios_fulltext (`Nombre`, `Observaciones`);

-- Índice fulltext para búsquedas en Familias  
-- ALTER TABLE `Familias Socios` ADD FULLTEXT INDEX idx_familias_fulltext (`Apellidos`, `Localidad`);

-- ============================================================
-- PARTE 4: CONFIGURACIÓN DE SESIÓN (aplicar por conexión)
-- Ya incluido en db.php, pero útil para phpMyAdmin
-- ============================================================

-- Deshabilitar modo estricto para compatibilidad
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- PARTE 5: VERIFICAR ESTADO DEL SERVIDOR
-- ============================================================

-- Ver estado de caché de queries (si está habilitado)
SHOW STATUS LIKE 'Qcache%';

-- Ver conexiones activas
SHOW STATUS LIKE 'Threads_connected';

-- Ver queries lentas
SHOW STATUS LIKE 'Slow_queries';

-- ============================================================
-- PARTE 6: CONFIGURACIÓN DEL SERVIDOR (my.cnf / my.ini)
-- Estas son RECOMENDACIONES para el administrador del servidor
-- NO ejecutar como SQL, añadir al archivo de configuración
-- ============================================================

/*
# Añadir a /etc/mysql/my.cnf o similar en Synology

[mysqld]
# Buffer de memoria para índices (ajustar según RAM disponible)
key_buffer_size = 128M

# Caché de queries (si no usas query_cache_type=0)
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# Buffer para ordenación
sort_buffer_size = 2M
read_buffer_size = 2M

# Pool de conexiones
max_connections = 100
thread_cache_size = 8

# InnoDB (motor por defecto)
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2

# Tiempo máximo de queries (evita bloqueos)
max_execution_time = 30000
*/

-- ============================================================
-- PARTE 7: VERIFICAR RENDIMIENTO DE QUERIES ESPECÍFICAS
-- ============================================================

-- Explicar plan de ejecución para query de socios
EXPLAIN SELECT s.`Id`, s.`Nombre`, f.`Apellidos`
FROM `Socios` s
LEFT JOIN `Familias Socios` f ON f.`Id` = s.`IdFamilia`
WHERE s.`Socio/Ex Socio` = 'Socio'
ORDER BY s.`Nombre`
LIMIT 25;

-- Explicar plan para búsqueda con filtros
EXPLAIN SELECT s.`Id`, s.`Nombre`
FROM `Socios` s
WHERE s.`Nivel` IN (0,1,2,3,4)
AND s.`Socio/Ex Socio` != 'Ex Socio'
ORDER BY s.`Nivel`, s.`Nombre`;
