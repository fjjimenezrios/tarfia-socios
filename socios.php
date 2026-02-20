<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cache.php';

$pageTitle = 'Socios';

// Dropdowns: cache 5 min
$familias = cache_get('familias_list');
if (!is_array($familias)) {
    $familias = $pdo->query("SELECT `Id`, `Apellidos` FROM `Familias Socios` ORDER BY `Apellidos`")->fetchAll(PDO::FETCH_ASSOC);
    cache_set('familias_list', $familias, CACHE_TTL_LISTS);
}
$niveles = cache_get('niveles_list');
if (!is_array($niveles)) {
    $niveles = $pdo->query("SELECT `Nivel`, `Curso` FROM `Niveles-Cursos` ORDER BY `Nivel`")->fetchAll(PDO::FETCH_ASSOC);
    cache_set('niveles_list', $niveles, CACHE_TTL_LISTS);
}

$presetFamilia = isset($_GET['familia']) ? (int) $_GET['familia'] : '';
$presetNivel = isset($_GET['nivel']) ? (int) $_GET['nivel'] : '';
$presetGrupo = isset($_GET['grupo']) ? $_GET['grupo'] : '';

// Nombre de familia seleccionada (si viene de enlace)
$familiaSeleccionada = '';
if ($presetFamilia !== '') {
    foreach ($familias as $f) {
        if ((int) $f['Id'] === $presetFamilia) {
            $familiaSeleccionada = $f['Apellidos'];
            break;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="tarfia-page-header-flex mb-3">
    <h1 class="tarfia-page-title mb-0">Socios<?= $familiaSeleccionada ? ' — Familia ' . htmlspecialchars($familiaSeleccionada) : '' ?></h1>
    <div class="tarfia-page-actions">
        <a href="#" id="btnExportar" class="tarfia-btn tarfia-btn-outline"><i class="fas fa-download"></i> <span class="btn-text">Exportar</span></a>
        <a href="socios-alta.php" class="tarfia-btn tarfia-btn-success"><i class="fas fa-plus"></i> <span class="btn-text">Añadir</span></a>
    </div>
</div>

<?php if (isset($_GET['ok'])): ?>
    <script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.success('Socio dado de alta correctamente'); });</script>
<?php endif; ?>

<!-- Filtros rápidos de grupo -->
<div class="tarfia-card mb-3">
    <div class="tarfia-card-body" style="padding: 0.75rem 1rem;">
        <div class="tarfia-filtros-grupo">
            <span class="tarfia-muted" style="margin-right: 0.5rem;">Filtrar por grupo:</span>
            <button type="button" class="tarfia-btn tarfia-btn-outline tarfia-btn-sm grupo-filter <?= $presetGrupo === '' ? 'active' : '' ?>" data-grupo="">Todos</button>
            <button type="button" class="tarfia-btn tarfia-btn-outline tarfia-btn-sm grupo-filter <?= $presetGrupo === 'club' ? 'active' : '' ?>" data-grupo="club">Club (4EPO–2ESO)</button>
            <button type="button" class="tarfia-btn tarfia-btn-outline tarfia-btn-sm grupo-filter <?= $presetGrupo === 'sanrafael' ? 'active' : '' ?>" data-grupo="sanrafael">San Rafael (3ESO–2BACH)</button>
        </div>
    </div>
</div>

<div class="tarfia-card">
    <div class="tarfia-table-responsive" id="tableWrapper">
        <table id="tablaSocios" class="tarfia-table" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Nombre</th>
                    <th>Familia</th>
                    <th>Curso</th>
                    <th class="hide-mobile">Cuota</th>
                    <th>Estado</th>
                    <th class="hide-mobile">Móvil</th>
                    <th class="hide-mobile">F. Admisión</th>
                    <th style="width: 90px;">Acciones</th>
                </tr>
                <tr class="filters-row">
                    <th></th>
                    <th><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Nombre..." data-col="1"></th>
                    <th>
                        <select class="tarfia-select tarfia-select-sm col-filter" data-col="2" id="filterFamilia">
                            <option value="">Todas</option>
                            <?php foreach ($familias as $f): ?>
                                <option value="<?= htmlspecialchars($f['Apellidos'] ?? '') ?>" <?= htmlspecialchars($f['Apellidos'] ?? '') === $familiaSeleccionada ? 'selected' : '' ?>><?= htmlspecialchars($f['Apellidos'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th>
                        <select class="tarfia-select tarfia-select-sm col-filter" data-col="3" id="filterNivel">
                            <option value="">Todos</option>
                            <?php foreach ($niveles as $n): ?>
                                <option value="<?= htmlspecialchars($n['Curso']) ?>" <?= (string) $n['Nivel'] === (string) $presetNivel ? 'selected' : '' ?>><?= htmlspecialchars($n['Curso']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th class="hide-mobile"><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="€" data-col="4"></th>
                    <th>
                        <select class="tarfia-select tarfia-select-sm col-filter" data-col="5">
                            <option value="">Todos</option>
                            <option value="Socio">Socio</option>
                            <option value="Ex Socio">Ex Socio</option>
                        </select>
                    </th>
                    <th class="hide-mobile"><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Móvil" data-col="6"></th>
                    <th class="hide-mobile"><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Fecha" data-col="7"></th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
.tarfia-page-header-flex {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.tarfia-page-actions {
    display: flex;
    gap: 0.5rem;
}
.tarfia-filtros-grupo {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.grupo-filter.active {
    background: var(--tarfia-accent);
    color: #1a2744;
    border-color: var(--tarfia-accent);
}
@media (max-width: 767px) {
    .tarfia-page-header-flex {
        flex-direction: column;
        align-items: stretch;
    }
    .tarfia-page-title {
        text-align: center;
    }
    .tarfia-page-actions {
        justify-content: center;
    }
    .tarfia-page-actions .btn-text {
        display: none;
    }
    .tarfia-page-actions .tarfia-btn {
        padding: 0.5rem 0.75rem;
    }
    .tarfia-filtros-grupo {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.5rem;
    }
    .tarfia-filtros-grupo > span:first-child {
        grid-column: 1 / -1;
        text-align: center;
    }
    .tarfia-filtros-grupo .grupo-filter {
        justify-content: center;
        font-size: 0.75rem;
        padding: 0.5rem 0.25rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var presetFamilia = <?= json_encode((string) $presetFamilia) ?>;
    var presetNivel = <?= json_encode((string) $presetNivel) ?>;
    var currentGrupo = <?= json_encode((string) $presetGrupo) ?>;
    
    var loadingToast = null;
    
    var table = new DataTable('#tablaSocios', {
        processing: false,
        serverSide: true,
        ajax: {
            url: 'api/socios.php',
            data: function(d) {
                // Enviar filtros de columna
                d.col_filters = {};
                document.querySelectorAll('#tablaSocios .col-filter').forEach(function(el) {
                    var col = el.getAttribute('data-col');
                    var val = el.value.trim();
                    if (val) d.col_filters[col] = val;
                });
                // Filtro de grupo
                d.grupo = currentGrupo;
                // Compatibilidad con filtros preset por ID
                if (presetFamilia && !d.col_filters['1']) {
                    d.familia_id = presetFamilia;
                }
            },
            beforeSend: function() {
                loadingToast = TarfiaToast.loading('Cargando datos...');
            },
            complete: function() {
                if (loadingToast) {
                    TarfiaToast.hide(loadingToast);
                    loadingToast = null;
                }
            },
            error: function() {
                if (loadingToast) {
                    TarfiaToast.update(loadingToast, { type: 'error', message: 'Error al cargar los datos' });
                    loadingToast = null;
                }
            }
        },
        columns: [
            { data: 0, orderable: false, searchable: false },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8, orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        language: {
            lengthMenu: 'Mostrar _MENU_ registros',
            zeroRecords: 'No se encontraron resultados',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            search: 'Buscar:',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            }
        },
        order: [[1, 'asc']],
        dom: '<"tarfia-dt-top"if>rt<"tarfia-dt-bottom"lp>',
        orderCellsTop: true
    });
    
    // Filtros en tiempo real (sin botón buscar)
    var debounceTimer;
    document.querySelectorAll('#tablaSocios .col-filter').forEach(function(el) {
        el.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                presetFamilia = '';
                table.ajax.reload();
            }, 300);
        });
        el.addEventListener('change', function() {
            presetFamilia = '';
            table.ajax.reload();
        });
    });
    
    // Filtros de grupo
    document.querySelectorAll('.grupo-filter').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.grupo-filter').forEach(function(b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
            currentGrupo = this.getAttribute('data-grupo');
            if (currentGrupo) {
                document.getElementById('filterNivel').value = '';
            }
            table.ajax.reload();
        });
    });
    
    // Exportar CSV
    document.getElementById('btnExportar').addEventListener('click', function(e) {
        e.preventDefault();
        TarfiaToast.info('Preparando exportación CSV...', 'Descarga');
        var params = [];
        if (currentGrupo) params.push('grupo=' + encodeURIComponent(currentGrupo));
        var estado = document.querySelector('.col-filter[data-col="5"]').value;
        if (estado) params.push('estado=' + encodeURIComponent(estado));
        var nivel = document.getElementById('filterNivel').value;
        if (nivel) params.push('nivel=' + encodeURIComponent(nivel));
        if (presetFamilia) params.push('familia=' + encodeURIComponent(presetFamilia));
        setTimeout(function() {
            window.location.href = 'api/export-socios.php' + (params.length ? '?' + params.join('&') : '');
        }, 300);
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
