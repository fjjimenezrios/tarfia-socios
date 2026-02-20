-- =====================================================
-- ÍNDICES PARA TARFIA - Optimización de consultas
-- Ejecutar en phpMyAdmin del Synology
-- =====================================================

-- =====================================================
-- TABLA: Socios
-- =====================================================

-- Índice para búsquedas por familia (JOINs frecuentes)
CREATE INDEX idx_socios_familia ON `Socios` (`IdFamilia`);

-- Índice para filtros por nivel
CREATE INDEX idx_socios_nivel ON `Socios` (`Nivel`);

-- Índice para filtros por estado (Socio/Ex Socio)
CREATE INDEX idx_socios_estado ON `Socios` (`Socio/Ex Socio`(20));

-- Índice compuesto para filtros combinados nivel + estado (muy usado)
CREATE INDEX idx_socios_nivel_estado ON `Socios` (`Nivel`, `Socio/Ex Socio`(20));

-- Índice para ordenar por nombre (búsquedas alfabéticas)
CREATE INDEX idx_socios_nombre ON `Socios` (`Nombre`(50));

-- Índice para ordenar por fecha de admisión (últimos socios en home)
CREATE INDEX idx_socios_fecha ON `Socios` (`Fecha de admisión`);


-- =====================================================
-- TABLA: Familias Socios
-- =====================================================

-- Índice para ordenar por apellidos (listados)
CREATE INDEX idx_familias_apellidos ON `Familias Socios` (`Apellidos`(50));

-- Índice para filtrar por localidad
CREATE INDEX idx_familias_localidad ON `Familias Socios` (`Localidad`(50));


-- =====================================================
-- VERIFICAR ÍNDICES CREADOS
-- =====================================================

-- Descomenta estas líneas para ver los índices:
-- SHOW INDEX FROM `Socios`;
-- SHOW INDEX FROM `Familias Socios`;
