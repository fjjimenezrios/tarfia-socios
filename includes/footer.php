    </div>
</main>
<footer class="tarfia-footer">
    <div class="container">TarfíaDB</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="//cdn.datatables.net/2.3.7/js/dataTables.min.js"></script>
<?php $jsVersion = filemtime(__DIR__ . '/../assets/js/app.js') ?: time(); ?>
<script src="<?= $baseUrl ?>assets/js/app.js?v=<?= $jsVersion ?>"></script>
<script>
(function() {
    // Tema
    var savedTheme = localStorage.getItem('tarfia-theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    function updateAllIcons() {
        var theme = document.documentElement.getAttribute('data-theme');
        var iconClass = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        var icons = ['themeIcon', 'sidebarThemeIcon'];
        icons.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.className = iconClass;
        });
    }
    
    function toggleTheme() {
        var current = document.documentElement.getAttribute('data-theme');
        var next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('tarfia-theme', next);
        updateAllIcons();
    }
    
    updateAllIcons();
    
    document.addEventListener('DOMContentLoaded', function() {
        updateAllIcons();
        
        // Theme toggles
        var toggles = ['themeToggle', 'sidebarThemeToggle'];
        toggles.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleTheme();
                });
            }
        });
        
        // Sidebar móvil
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        var navToggle = document.getElementById('navToggle');
        var sidebarClose = document.getElementById('sidebarClose');
        
        function openSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        if (navToggle) {
            navToggle.addEventListener('click', function(e) {
                e.preventDefault();
                openSidebar();
            });
        }
        
        if (sidebarClose) {
            sidebarClose.addEventListener('click', function(e) {
                e.preventDefault();
                closeSidebar();
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
        
        // Cerrar con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });
        
        // Envolver tablas DataTables en wrapper scrollable (solo la tabla, no los controles)
        function wrapTablesForScroll() {
            var tables = document.querySelectorAll('.tarfia-table-responsive table.dataTable');
            tables.forEach(function(table) {
                if (table.parentElement.classList.contains('tarfia-table-scroll-wrapper')) return;
                
                var wrapper = document.createElement('div');
                wrapper.className = 'tarfia-table-scroll-wrapper';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
                
                // Detectar si hay scroll disponible
                function checkScroll() {
                    if (wrapper.scrollWidth > wrapper.clientWidth) {
                        wrapper.classList.add('can-scroll');
                        if (wrapper.scrollLeft + wrapper.clientWidth >= wrapper.scrollWidth - 5) {
                            wrapper.classList.add('scrolled-end');
                        } else {
                            wrapper.classList.remove('scrolled-end');
                        }
                    } else {
                        wrapper.classList.remove('can-scroll', 'scrolled-end');
                    }
                }
                
                wrapper.addEventListener('scroll', checkScroll);
                window.addEventListener('resize', checkScroll);
                setTimeout(checkScroll, 100);
            });
        }
        
        // Ejecutar después de que DataTables se inicialice
        setTimeout(wrapTablesForScroll, 200);
        
        // Observer para detectar cuando DataTables se inicializa
        var dtObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    var hasNewTable = false;
                    mutation.addedNodes.forEach(function(node) {
                        if (node.classList && node.classList.contains('dt-container')) {
                            hasNewTable = true;
                        }
                    });
                    if (hasNewTable) {
                        setTimeout(wrapTablesForScroll, 50);
                    }
                }
            });
        });
        
        document.querySelectorAll('.tarfia-table-responsive').forEach(function(el) {
            dtObserver.observe(el, { childList: true, subtree: true });
        });
    });
    
    // Service Worker con actualización automática
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= $baseUrl ?>sw.js')
            .then(function(reg) {
                // Buscar actualizaciones periódicamente
                reg.update();
                setInterval(function() { reg.update(); }, 60000);
                
                // Si hay una nueva versión esperando, activarla
                if (reg.waiting) {
                    reg.waiting.postMessage({ type: 'SKIP_WAITING' });
                }
                
                reg.addEventListener('updatefound', function() {
                    var newWorker = reg.installing;
                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            newWorker.postMessage({ type: 'SKIP_WAITING' });
                        }
                    });
                });
            })
            .catch(function(err) {
                console.log('SW error:', err);
            });
        
        // Recargar cuando el nuevo SW tome control
        var refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', function() {
            if (!refreshing) {
                refreshing = true;
                window.location.reload();
            }
        });
    }
})();
</script>
</body>
</html>
