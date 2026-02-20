<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cache.php';

$pageTitle = 'Informes';

// Obtener estadísticas rápidas para mostrar en las cards
$stats = cache_get('informes_stats');
if (!is_array($stats)) {
    $stats = [];
    
    // Total socios activos
    $st = $pdo->query("SELECT COUNT(*) AS n FROM `Socios` WHERE LOWER(TRIM(COALESCE(`Socio/Ex Socio`, ''))) != 'ex socio'");
    $stats['socios_activos'] = (int) $st->fetch()['n'];
    
    // Total familias
    $st = $pdo->query("SELECT COUNT(*) AS n FROM `Familias Socios`");
    $stats['familias'] = (int) $st->fetch()['n'];
    
    // Familias con dirección (para etiquetas)
    $st = $pdo->query("SELECT COUNT(*) AS n FROM `Familias Socios` WHERE `Dirección` IS NOT NULL AND `Dirección` != ''");
    $stats['familias_con_direccion'] = (int) $st->fetch()['n'];
    
    // Niveles disponibles
    $st = $pdo->query("SELECT COUNT(*) AS n FROM `Niveles-Cursos`");
    $stats['niveles'] = (int) $st->fetch()['n'];
    
    cache_set('informes_stats', $stats, 300); // 5 min
}

require __DIR__ . '/includes/header.php';
?>

<div class="tarfia-page-header">
    <h1 class="tarfia-page-title">Informes y Exportaciones</h1>
    <p class="tarfia-page-subtitle">Genera informes, exporta datos y crea etiquetas postales</p>
</div>

<!-- Resumen rápido -->
<div class="informes-resumen">
    <div class="resumen-item">
        <span class="resumen-valor"><?= number_format($stats['socios_activos']) ?></span>
        <span class="resumen-label">Socios activos</span>
    </div>
    <div class="resumen-item">
        <span class="resumen-valor"><?= number_format($stats['familias']) ?></span>
        <span class="resumen-label">Familias</span>
    </div>
    <div class="resumen-item">
        <span class="resumen-valor"><?= number_format($stats['niveles']) ?></span>
        <span class="resumen-label">Niveles/Cursos</span>
    </div>
</div>

<!-- CTA Principal: Generador de PDFs -->
<section class="informes-hero">
    <div class="informes-hero-content">
        <div class="informes-hero-icon">
            <i class="fas fa-file-pdf"></i>
        </div>
        <div class="informes-hero-text">
            <h2>Generador de Informes PDF</h2>
            <p>Crea informes personalizados con filtros y columnas a medida. Diseño profesional listo para imprimir.</p>
        </div>
        <a href="informes/generar.php" class="tarfia-btn tarfia-btn-primary tarfia-btn-lg">
            <i class="fas fa-magic"></i> Crear informe personalizado
        </a>
    </div>
</section>

<!-- Sección: Informes Rápidos -->
<section class="informes-seccion">
    <h2 class="informes-seccion-titulo"><i class="fas fa-chart-bar"></i> Informes Rápidos</h2>
    
    <div class="informes-grid">
        <!-- Informe por curso -->
        <div class="informe-card">
            <div class="informe-card-icon" style="background: linear-gradient(135deg, #0369a1 0%, #0284c7 100%);">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="informe-card-content">
                <h3>Socios por Curso</h3>
                <p>Resumen de socios activos y ex-socios por nivel educativo con listado detallado.</p>
                <div class="informe-card-meta">
                    <span><i class="fas fa-users"></i> <?= $stats['socios_activos'] ?> socios</span>
                    <span><i class="fas fa-layer-group"></i> <?= $stats['niveles'] ?> niveles</span>
                </div>
            </div>
            <div class="informe-card-actions">
                <a href="informes/socios-curso.php" class="tarfia-btn tarfia-btn-outline">
                    <i class="fas fa-eye"></i> Ver online
                </a>
                <a href="informes/pdf-resumen.php" class="tarfia-btn tarfia-btn-primary">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>

        <!-- Informe de cuotas -->
        <div class="informe-card">
            <div class="informe-card-icon" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="informe-card-content">
                <h3>Resumen de Cuotas</h3>
                <p>Ingresos mensuales y anuales, desglose por nivel y familias que pagan cuota.</p>
                <div class="informe-card-meta">
                    <span><i class="fas fa-calculator"></i> Cálculo automático</span>
                </div>
            </div>
            <div class="informe-card-actions">
                <a href="cuotas.php" class="tarfia-btn tarfia-btn-primary">
                    <i class="fas fa-eye"></i> Ver cuotas
                </a>
            </div>
        </div>

        <!-- Listado Socios PDF -->
        <div class="informe-card">
            <div class="informe-card-icon" style="background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);">
                <i class="fas fa-list-alt"></i>
            </div>
            <div class="informe-card-content">
                <h3>Listado de Socios</h3>
                <p>Genera un PDF con todos los socios o aplica filtros personalizados.</p>
                <div class="informe-card-meta">
                    <span><i class="fas fa-file-pdf"></i> Formato PDF</span>
                    <span><i class="fas fa-filter"></i> Personalizable</span>
                </div>
            </div>
            <div class="informe-card-actions">
                <a href="informes/pdf-socios.php" class="tarfia-btn tarfia-btn-outline">
                    <i class="fas fa-file-pdf"></i> PDF rápido
                </a>
                <a href="informes/generar.php" class="tarfia-btn tarfia-btn-primary">
                    <i class="fas fa-sliders-h"></i> Personalizar
                </a>
            </div>
        </div>

        <!-- Listado Familias PDF -->
        <div class="informe-card">
            <div class="informe-card-icon" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
                <i class="fas fa-house-user"></i>
            </div>
            <div class="informe-card-content">
                <h3>Listado de Familias</h3>
                <p>Genera un PDF con el directorio completo de familias y contactos.</p>
                <div class="informe-card-meta">
                    <span><i class="fas fa-home"></i> <?= $stats['familias'] ?> familias</span>
                </div>
            </div>
            <div class="informe-card-actions">
                <a href="informes/pdf-familias.php" class="tarfia-btn tarfia-btn-primary">
                    <i class="fas fa-file-pdf"></i> Generar PDF
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Sección: Exportaciones -->
<section class="informes-seccion">
    <h2 class="informes-seccion-titulo"><i class="fas fa-download"></i> Exportaciones</h2>
    
    <div class="informes-grid">
        <!-- Exportar socios CSV -->
        <div class="informe-card">
            <div class="informe-card-icon" style="background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);">
                <i class="fas fa-file-csv"></i>
            </div>
            <div class="informe-card-content">
                <h3>Exportar Socios</h3>
                <p>Descarga el listado completo de socios en formato CSV compatible con Excel.</p>
                <div class="informe-card-meta">
                    <span><i class="fas fa-users"></i> Todos los datos</span>
                    <span><i class="fas fa-filter"></i> Con filtros</span>
                </div>
            </div>
            <div class="informe-card-actions">
                <a href="api/export-socios.php" class="tarfia-btn tarfia-btn-primary">
                    <i class="fas fa-download"></i> Descargar CSV
                </a>
                <a href="socios.php" class="tarfia-btn tarfia-btn-outline">
                    <i class="fas fa-filter"></i> Filtrar primero
                </a>
            </div>
        </div>

        <!-- Exportar familias CSV -->
        <div class="informe-card">
            <div class="informe-card-icon" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
                <i class="fas fa-file-csv"></i>
            </div>
            <div class="informe-card-content">
                <h3>Exportar Familias</h3>
                <p>Descarga el listado completo de familias con datos de contacto en formato CSV.</p>
                <div class="informe-card-meta">
                    <span><i class="fas fa-home"></i> <?= $stats['familias'] ?> familias</span>
                </div>
            </div>
            <div class="informe-card-actions">
                <a href="api/export-familias.php" class="tarfia-btn tarfia-btn-primary">
                    <i class="fas fa-download"></i> Descargar CSV
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Sección: Etiquetas -->
<section class="informes-seccion">
    <h2 class="informes-seccion-titulo"><i class="fas fa-tags"></i> Etiquetas Postales</h2>
    
    <div class="informes-grid">
        <!-- Etiquetas todas las familias -->
        <div class="informe-card">
            <div class="informe-card-icon" style="background: linear-gradient(135deg, #f7b32b 0%, #fbbf24 100%);">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="informe-card-content">
                <h3>Todas las Familias</h3>
                <p>Genera etiquetas postales para todas las familias con dirección registrada.</p>
                <div class="informe-card-meta">
                    <span><i class="fas fa-map-marker-alt"></i> <?= $stats['familias_con_direccion'] ?> con dirección</span>
                    <span><i class="fas fa-th"></i> Formato Avery</span>
                </div>
            </div>
            <div class="informe-card-actions">
                <a href="informes/etiquetas.php" class="tarfia-btn tarfia-btn-primary">
                    <i class="fas fa-print"></i> Generar etiquetas
                </a>
            </div>
        </div>

        <!-- Etiquetas solo activos -->
        <div class="informe-card">
            <div class="informe-card-icon" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="informe-card-content">
                <h3>Solo Socios Activos</h3>
                <p>Genera etiquetas solo para familias que tienen al menos un socio activo.</p>
                <div class="informe-card-meta">
                    <span><i class="fas fa-check-circle"></i> Filtrado automático</span>
                </div>
            </div>
            <div class="informe-card-actions">
                <a href="informes/etiquetas.php?activos=1" class="tarfia-btn tarfia-btn-primary">
                    <i class="fas fa-print"></i> Generar etiquetas
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Sección: Herramientas -->
<section class="informes-seccion">
    <h2 class="informes-seccion-titulo"><i class="fas fa-tools"></i> Herramientas</h2>
    
    <div class="informes-grid informes-grid-small">
        <a href="socios.php" class="herramienta-link">
            <i class="fas fa-search"></i>
            <span>Buscar socios</span>
        </a>
        <a href="familias.php" class="herramienta-link">
            <i class="fas fa-search"></i>
            <span>Buscar familias</span>
        </a>
        <a href="socios.php?grupo=club" class="herramienta-link">
            <i class="fas fa-futbol"></i>
            <span>Ver Club (4EPO-2ESO)</span>
        </a>
        <a href="socios.php?grupo=sanrafael" class="herramienta-link">
            <i class="fas fa-church"></i>
            <span>Ver San Rafael (3ESO-2BACH)</span>
        </a>
    </div>
</section>

<style>
.tarfia-page-header {
    margin-bottom: 2rem;
}
.tarfia-page-subtitle {
    color: var(--tarfia-muted);
    font-size: 1rem;
    margin-top: 0.25rem;
}

/* Hero del generador */
.informes-hero {
    background: linear-gradient(135deg, #1a2744 0%, #2d3e5f 100%);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(26, 39, 68, 0.3);
}
.informes-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(247, 179, 43, 0.15);
    border-radius: 50%;
}
.informes-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 200px;
    height: 200px;
    background: rgba(247, 179, 43, 0.1);
    border-radius: 50%;
}
.informes-hero-content {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    position: relative;
    z-index: 1;
}
.informes-hero-icon {
    width: 64px;
    height: 64px;
    background: #f7b32b;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: #1a2744;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(247, 179, 43, 0.4);
}
.informes-hero-text {
    flex: 1;
}
.informes-hero-text h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.25rem;
}
.informes-hero-text p {
    font-size: 0.875rem;
    color: rgba(255,255,255,0.85);
    margin: 0;
}
.informes-hero .tarfia-btn-lg {
    padding: 0.875rem 1.5rem;
    font-size: 0.9375rem;
    white-space: nowrap;
    background: #f7b32b;
    color: #1a2744;
    border: none;
}
.informes-hero .tarfia-btn-lg:hover {
    background: #e5a327;
}

@media (max-width: 767px) {
    .informes-hero {
        padding: 1.5rem;
    }
    .informes-hero-content {
        flex-direction: column;
        text-align: center;
    }
    .informes-hero-icon {
        width: 56px;
        height: 56px;
        font-size: 1.5rem;
    }
    .informes-hero .tarfia-btn-lg {
        width: 100%;
        justify-content: center;
    }
}

/* Resumen rápido */
.informes-resumen {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}
.resumen-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 2rem;
    background: var(--tarfia-surface);
    border-radius: var(--tarfia-radius);
    border: 1px solid var(--tarfia-border);
    min-width: 120px;
}
.resumen-valor {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--tarfia-text);
    line-height: 1;
}
.resumen-label {
    font-size: 0.75rem;
    color: var(--tarfia-muted);
    margin-top: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Secciones */
.informes-seccion {
    margin-bottom: 2.5rem;
}
.informes-seccion-titulo {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--tarfia-text);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.informes-seccion-titulo i {
    color: var(--tarfia-muted);
}

/* Grid de informes */
.informes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.25rem;
}
.informes-grid-small {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
}

/* Card de informe */
.informe-card {
    background: var(--tarfia-surface);
    border-radius: var(--tarfia-radius);
    border: 1px solid var(--tarfia-border);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow var(--tarfia-transition), transform var(--tarfia-transition);
}
.informe-card:hover {
    box-shadow: var(--tarfia-shadow-hover);
    transform: translateY(-2px);
}

.informe-card-icon {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.informe-card-content {
    padding: 1.25rem;
    flex: 1;
}
.informe-card-content h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--tarfia-text);
    margin-bottom: 0.5rem;
}
.informe-card-content p {
    font-size: 0.875rem;
    color: var(--tarfia-muted);
    line-height: 1.5;
    margin-bottom: 0.75rem;
}

.informe-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.75rem;
    color: var(--tarfia-muted);
}
.informe-card-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.informe-card-meta i {
    font-size: 0.7rem;
}

.informe-card-actions {
    padding: 1rem 1.25rem;
    background: var(--tarfia-surface-hover);
    border-top: 1px solid var(--tarfia-border);
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.informe-card-actions .tarfia-btn {
    flex: 1;
    min-width: fit-content;
    justify-content: center;
}

/* Herramientas links */
.herramienta-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: var(--tarfia-surface);
    border-radius: var(--tarfia-radius);
    border: 1px solid var(--tarfia-border);
    color: var(--tarfia-text);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all var(--tarfia-transition);
}
.herramienta-link:hover {
    background: var(--tarfia-surface-hover);
    border-color: var(--tarfia-link);
    color: var(--tarfia-link);
}
.herramienta-link i {
    color: var(--tarfia-muted);
    font-size: 1rem;
    transition: color var(--tarfia-transition);
}
.herramienta-link:hover i {
    color: var(--tarfia-link);
}

/* Responsive */
@media (max-width: 767px) {
    .informes-resumen {
        justify-content: center;
    }
    .resumen-item {
        flex: 1;
        min-width: 100px;
        padding: 0.875rem 1rem;
    }
    .resumen-valor {
        font-size: 1.5rem;
    }
    .informes-grid {
        grid-template-columns: 1fr;
    }
    .informe-card-actions {
        flex-direction: column;
    }
    .informe-card-actions .tarfia-btn {
        width: 100%;
    }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
