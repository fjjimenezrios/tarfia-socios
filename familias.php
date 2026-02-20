<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Familias';

require __DIR__ . '/includes/header.php';
?>
<div class="tarfia-page-header-flex mb-3">
    <h1 class="tarfia-page-title mb-0">Familias</h1>
    <div class="tarfia-page-actions">
        <a href="api/export-familias.php" class="tarfia-btn tarfia-btn-outline"><i class="fas fa-download"></i> <span class="btn-text">Exportar</span></a>
        <a href="familias-alta.php" class="tarfia-btn tarfia-btn-success"><i class="fas fa-plus"></i> <span class="btn-text">Añadir</span></a>
    </div>
</div>

<?php if (isset($_GET['ok'])): ?>
    <script>document.addEventListener('DOMContentLoaded', function() { TarfiaToast.success('Familia creada correctamente'); });</script>
<?php endif; ?>

<div class="tarfia-card">
    <div class="tarfia-table-responsive">
        <table id="tablaFamilias" class="tarfia-table" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Apellidos</th>
                    <th>Padre</th>
                    <th>Madre</th>
                    <th>Localidad</th>
                    <th class="hide-mobile">Teléfono</th>
                    <th>Móviles</th>
                    <th class="hide-mobile">Email</th>
                    <th style="width: 50px;">Nº</th>
                    <th style="width: 90px;">Acciones</th>
                </tr>
                <tr class="filters-row">
                    <th></th>
                    <th><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Apellidos" data-col="1"></th>
                    <th><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Padre" data-col="2"></th>
                    <th><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Madre" data-col="3"></th>
                    <th><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Localidad" data-col="4"></th>
                    <th class="hide-mobile"><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Tel" data-col="5"></th>
                    <th><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Móvil" data-col="6"></th>
                    <th class="hide-mobile"><input type="text" class="tarfia-input tarfia-input-sm col-filter" placeholder="Email" data-col="7"></th>
                    <th></th>
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
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var loadingToast = null;
    
    var table = new DataTable('#tablaFamilias', {
        processing: false,
        serverSide: true,
        ajax: {
            url: 'api/familias.php',
            data: function(d) {
                d.col_filters = {};
                document.querySelectorAll('#tablaFamilias .col-filter').forEach(function(el) {
                    var col = el.getAttribute('data-col');
                    var val = el.value.trim();
                    if (val) d.col_filters[col] = val;
                });
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
            { data: 8, orderable: false, searchable: false },
            { data: 9, orderable: false, searchable: false }
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
    
    // Filtros en tiempo real
    var debounceTimer;
    document.querySelectorAll('#tablaFamilias .col-filter').forEach(function(el) {
        el.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                table.ajax.reload();
            }, 300);
        });
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
