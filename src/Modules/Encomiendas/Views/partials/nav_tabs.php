<!-- NAVEGACIÓN DE PESTAÑAS Y SUBFILTROS -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <ul class="nav nav-tabs border-bottom-0 mb-0">
        <li class="nav-item">
            <a class="nav-link <?= (!isset($filtros['tab']) || $filtros['tab'] == 'activos') ? 'active fw-bold' : '' ?>" href="?tab=activos">
                📦 Paquetes Activos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (isset($filtros['tab']) && $filtros['tab'] == 'historial') ? 'active fw-bold' : '' ?>" href="?tab=historial">
                📚 Buscador e Historial General
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-primary fw-bold" href="/suramerica2030/manifiesto?tab=carga">
                📋 Manifiesto de Carga ➔
            </a>
        </li>
    </ul>

    <?php if(!isset($filtros['tab']) || $filtros['tab'] == 'activos'): ?>
        <div class="d-flex gap-1 flex-wrap align-items-center">
            <span class="small fw-bold text-muted me-1">Filtrar:</span>
            <a href="?tab=activos" class="btn btn-sm <?= empty($filtros['estado']) ? 'btn-dark' : 'btn-outline-secondary' ?> fw-bold py-1 px-2">
                Todos (<?= $kpis['total_activas'] ?? 0 ?>)
            </a>
            <a href="?tab=activos&estado=Recibido" class="btn btn-sm <?= (($filtros['estado'] ?? '') == 'Recibido') ? 'btn-secondary text-white' : 'btn-outline-secondary' ?> fw-bold py-1 px-2">
                🏬 Bodega (<?= $kpis['total_recibidos'] ?? 0 ?>)
            </a>
            <a href="?tab=activos&estado=En+Tr%C3%A1nsito" class="btn btn-sm <?= (($filtros['estado'] ?? '') == 'En Tránsito') ? 'btn-primary' : 'btn-outline-primary' ?> fw-bold py-1 px-2">
                🚚 Tránsito (<?= $kpis['total_en_transito'] ?? 0 ?>)
            </a>
            <a href="?tab=activos&estado=En+Oficina" class="btn btn-sm <?= (($filtros['estado'] ?? '') == 'En Oficina') ? 'btn-info text-dark' : 'btn-outline-info text-dark' ?> fw-bold py-1 px-2">
                🏢 En Oficina (<?= $kpis['total_en_oficina'] ?? 0 ?>)
            </a>
        </div>
    <?php endif; ?>
</div>