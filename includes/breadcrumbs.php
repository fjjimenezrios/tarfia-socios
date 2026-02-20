<?php
/**
 * Componente de Breadcrumbs
 * 
 * Uso: antes de incluir este archivo, define $breadcrumbs como array:
 * $breadcrumbs = [
 *     ['url' => 'home.php', 'label' => 'Inicio'],
 *     ['url' => 'socios.php', 'label' => 'Socios'],
 *     ['label' => 'Editar'] // Sin URL = página actual
 * ];
 */
if (!isset($breadcrumbs) || !is_array($breadcrumbs)) {
    return;
}
?>
<nav class="tarfia-breadcrumbs" aria-label="Navegación">
    <ol class="tarfia-breadcrumbs-list">
        <?php foreach ($breadcrumbs as $i => $item): ?>
            <?php $isLast = $i === count($breadcrumbs) - 1; ?>
            <li class="tarfia-breadcrumbs-item<?= $isLast ? ' active' : '' ?>">
                <?php if (!$isLast && isset($item['url'])): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                <?php endif; ?>
                <?php if (!$isLast): ?>
                    <i class="fas fa-chevron-right tarfia-breadcrumbs-sep"></i>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
