<?php
/**
 * Generador de informes PDF - Configurable y elegante
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Obtener opciones para filtros
$niveles = $pdo->query("SELECT `Nivel`, `Curso` FROM `Niveles-Cursos` ORDER BY `Nivel`")->fetchAll(PDO::FETCH_ASSOC);
$estados = ['', 'Socio', 'Ex Socio'];

// Procesar el formulario
$generarPDF = false;
$filtros = [];
$columnas = [];
$tipoInforme = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $generarPDF = true;
    $tipoInforme = $_POST['tipo'] ?? 'socios';
    
    // Filtros
    $filtros = [
        'nivel' => $_POST['nivel'] ?? '',
        'estado' => $_POST['estado'] ?? '',
        'grupo' => $_POST['grupo'] ?? '',
    ];
    
    // Columnas seleccionadas
    $columnas = $_POST['columnas'] ?? [];
}

// Si se va a generar, redirigir a la página de PDF correcta
if ($generarPDF) {
    $params = http_build_query([
        'nivel' => $filtros['nivel'],
        'estado' => $filtros['estado'],
        'grupo' => $filtros['grupo'],
        'cols' => implode(',', $columnas),
    ]);
    
    switch ($tipoInforme) {
        case 'familias':
            header("Location: pdf-familias.php?$params");
            break;
        case 'resumen':
            header("Location: pdf-resumen.php");
            break;
        case 'socios':
        default:
            header("Location: pdf-socios.php?$params");
            break;
    }
    exit;
}

$pageTitle = 'Generar Informe';
require __DIR__ . '/../includes/header.php';
?>

<div class="tarfia-page-header">
    <h1 class="tarfia-page-title">Generar Informe PDF</h1>
    <p class="tarfia-page-subtitle">Configura los filtros y columnas para tu informe personalizado</p>
</div>

<form method="post" action="" id="formInforme">
    <div class="informe-config">
        <!-- Tipo de informe -->
        <div class="config-section">
            <h3><i class="fas fa-file-alt"></i> Tipo de Informe</h3>
            <div class="tipo-opciones">
                <label class="tipo-opcion active">
                    <input type="radio" name="tipo" value="socios" checked>
                    <span class="tipo-icon"><i class="fas fa-users"></i></span>
                    <span class="tipo-label">Listado de Socios</span>
                </label>
                <label class="tipo-opcion">
                    <input type="radio" name="tipo" value="familias">
                    <span class="tipo-icon"><i class="fas fa-house-user"></i></span>
                    <span class="tipo-label">Listado de Familias</span>
                </label>
                <label class="tipo-opcion">
                    <input type="radio" name="tipo" value="resumen">
                    <span class="tipo-icon"><i class="fas fa-chart-pie"></i></span>
                    <span class="tipo-label">Resumen por Curso</span>
                </label>
            </div>
        </div>

        <!-- Filtros -->
        <div class="config-section" id="seccionFiltros">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
            <div class="filtros-grid">
                <div class="filtro-item">
                    <label class="tarfia-form-label">Curso / Nivel</label>
                    <select name="nivel" class="tarfia-select">
                        <option value="">Todos los cursos</option>
                        <?php foreach ($niveles as $n): ?>
                            <option value="<?= (int) $n['Nivel'] ?>"><?= htmlspecialchars($n['Curso']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-item">
                    <label class="tarfia-form-label">Estado</label>
                    <select name="estado" class="tarfia-select">
                        <option value="">Todos</option>
                        <option value="socio">Solo Socios activos</option>
                        <option value="exsocio">Solo Ex Socios</option>
                    </select>
                </div>
                <div class="filtro-item">
                    <label class="tarfia-form-label">Grupo</label>
                    <select name="grupo" class="tarfia-select">
                        <option value="">Todos</option>
                        <option value="club">Club (4EPO – 2ESO)</option>
                        <option value="sanrafael">San Rafael (3ESO – 2BACH)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Columnas para Socios -->
        <div class="config-section" id="seccionColumnasSocios">
            <h3><i class="fas fa-columns"></i> Columnas a incluir</h3>
            <div class="columnas-grid">
                <label class="columna-check">
                    <input type="checkbox" name="columnas[]" value="nombre" checked disabled>
                    <span>Nombre</span>
                    <small>Siempre incluido</small>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas[]" value="familia" checked>
                    <span>Familia</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas[]" value="curso" checked>
                    <span>Curso</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas[]" value="estado" checked>
                    <span>Estado</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas[]" value="cuota">
                    <span>Cuota</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas[]" value="movil">
                    <span>Móvil</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas[]" value="fecha">
                    <span>Fecha admisión</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas[]" value="observaciones">
                    <span>Observaciones</span>
                </label>
            </div>
        </div>

        <!-- Columnas para Familias -->
        <div class="config-section" id="seccionColumnasFamilias" style="display: none;">
            <h3><i class="fas fa-columns"></i> Columnas a incluir</h3>
            <div class="columnas-grid">
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="apellidos" checked disabled>
                    <span>Apellidos</span>
                    <small>Siempre incluido</small>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="padre" checked>
                    <span>Padre</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="madre" checked>
                    <span>Madre</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="direccion" checked>
                    <span>Dirección</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="localidad" checked>
                    <span>Localidad</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="telefono">
                    <span>Teléfono</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="moviles" checked>
                    <span>Móviles</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="email">
                    <span>Email</span>
                </label>
                <label class="columna-check">
                    <input type="checkbox" name="columnas_fam[]" value="numsocios">
                    <span>Nº Socios</span>
                </label>
            </div>
        </div>

        <!-- Acciones -->
        <div class="config-actions">
            <button type="submit" class="tarfia-btn tarfia-btn-primary tarfia-btn-lg">
                <i class="fas fa-file-pdf"></i> Generar PDF
            </button>
            <a href="<?= $_SERVER['HTTP_REFERER'] ?? '../informes.php' ?>" class="tarfia-btn tarfia-btn-outline">
                Cancelar
            </a>
        </div>
    </div>
</form>

<style>
.informe-config {
    max-width: 800px;
}

.config-section {
    background: var(--tarfia-surface);
    border: 1px solid var(--tarfia-border);
    border-radius: var(--tarfia-radius);
    padding: 1.5rem;
    margin-bottom: 1.25rem;
}

.config-section h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--tarfia-text);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.config-section h3 i {
    color: var(--tarfia-link);
}

/* Tipo de informe */
.tipo-opciones {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
}

.tipo-opcion {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.25rem 1rem;
    border: 2px solid var(--tarfia-border);
    border-radius: var(--tarfia-radius);
    cursor: pointer;
    transition: all var(--tarfia-transition);
    text-align: center;
}

.tipo-opcion:hover {
    border-color: var(--tarfia-link);
    background: var(--tarfia-surface-hover);
}

.tipo-opcion.active {
    border-color: var(--tarfia-accent);
    background: var(--tarfia-accent-light);
}

.tipo-opcion input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.tipo-icon {
    font-size: 1.75rem;
    color: var(--tarfia-muted);
    margin-bottom: 0.5rem;
    transition: color var(--tarfia-transition);
}

.tipo-opcion.active .tipo-icon {
    color: var(--tarfia-accent);
}

.tipo-label {
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--tarfia-text);
}

/* Filtros */
.filtros-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.filtro-item label {
    display: block;
    margin-bottom: 0.35rem;
}

/* Columnas */
.columnas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 0.75rem;
}

.columna-check {
    display: flex;
    flex-direction: column;
    padding: 0.75rem 1rem;
    border: 1px solid var(--tarfia-border);
    border-radius: var(--tarfia-radius-sm);
    cursor: pointer;
    transition: all var(--tarfia-transition);
}

.columna-check:hover {
    background: var(--tarfia-surface-hover);
}

.columna-check:has(input:checked) {
    border-color: var(--tarfia-link);
    background: rgba(3, 105, 161, 0.05);
}

.columna-check input {
    position: absolute;
    opacity: 0;
}

.columna-check span {
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--tarfia-text);
}

.columna-check small {
    font-size: 0.7rem;
    color: var(--tarfia-muted);
    margin-top: 0.125rem;
}

/* Acciones */
.config-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.tarfia-btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1rem;
}

@media (max-width: 767px) {
    .config-section {
        padding: 1rem;
    }
    .tipo-opciones {
        grid-template-columns: 1fr;
    }
    .columnas-grid {
        grid-template-columns: 1fr 1fr;
    }
    .config-actions {
        flex-direction: column;
    }
    .config-actions .tarfia-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tipoInputs = document.querySelectorAll('input[name="tipo"]');
    var seccionFiltros = document.getElementById('seccionFiltros');
    var seccionColumnasSocios = document.getElementById('seccionColumnasSocios');
    var seccionColumnasFamilias = document.getElementById('seccionColumnasFamilias');
    
    function updateTipoUI() {
        document.querySelectorAll('.tipo-opcion').forEach(function(el) {
            el.classList.remove('active');
        });
        var checkedInput = document.querySelector('input[name="tipo"]:checked');
        if (checkedInput) {
            checkedInput.closest('.tipo-opcion').classList.add('active');
            var tipo = checkedInput.value;
            
            // Mostrar/ocultar secciones según tipo
            if (tipo === 'socios') {
                seccionFiltros.style.display = '';
                seccionColumnasSocios.style.display = '';
                seccionColumnasFamilias.style.display = 'none';
            } else if (tipo === 'familias') {
                seccionFiltros.style.display = 'none';
                seccionColumnasSocios.style.display = 'none';
                seccionColumnasFamilias.style.display = '';
            } else if (tipo === 'resumen') {
                seccionFiltros.style.display = 'none';
                seccionColumnasSocios.style.display = 'none';
                seccionColumnasFamilias.style.display = 'none';
            }
        }
    }
    
    tipoInputs.forEach(function(input) {
        input.addEventListener('change', updateTipoUI);
    });
    
    // Inicializar
    updateTipoUI();
    
    // Form submission - cambiar columnas según tipo
    document.getElementById('formInforme').addEventListener('submit', function(e) {
        var tipo = document.querySelector('input[name="tipo"]:checked').value;
        if (tipo === 'familias') {
            // Copiar valores de columnas_fam a columnas
            var colsFam = document.querySelectorAll('input[name="columnas_fam[]"]:checked');
            colsFam.forEach(function(cb) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'columnas[]';
                hidden.value = cb.value;
                e.target.appendChild(hidden);
            });
        }
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
