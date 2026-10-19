<!-- NAVEGACIÓN DE PESTAÑAS Y SUBFILTROS -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <ul class="nav nav-tabs border-bottom-0 mb-0">
        <li class="nav-item">
            <a class="nav-link <?= (!isset($filtros['tab']) || $filtros['tab'] == 'activos') ? 'active fw-bold' : '' ?>" href="?tab=activos">
                ⏳ Viajes Activos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (isset($filtros['tab']) && $filtros['tab'] == 'historial') ? 'active fw-bold' : '' ?>" href="?tab=historial">
                📚 Buscador e Historial General
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (isset($filtros['tab']) && $filtros['tab'] == 'equipaje') ? 'active fw-bold' : '' ?>" href="?tab=equipaje">
                🧳 Módulo de Equipaje & Sobrepeso
            </a>
        </li>
    </ul>

    <?php if(!isset($filtros['tab']) || $filtros['tab'] == 'activos'): ?>
        <div class="d-flex gap-1 flex-wrap align-items-center">
            <span class="small fw-bold text-muted me-1">Filtrar:</span>
            <a href="?tab=activos" class="btn btn-sm <?= empty($filtroRapido) ? 'btn-dark' : 'btn-outline-secondary' ?> fw-bold py-1 px-2">
                Todos (<?= $kpisPasajes['total_activos'] ?? 0 ?>)
            </a>
            <a href="?tab=activos&filtro_rapido=hoy" class="btn btn-sm <?= ($filtroRapido === 'hoy') ? 'btn-info text-dark' : 'btn-outline-info text-dark' ?> fw-bold py-1 px-2">
                🚌 Hoy (<?= $kpisPasajes['viajan_hoy'] ?? 0 ?>)
            </a>
            <a href="?tab=activos&filtro_rapido=proximos_7" class="btn btn-sm <?= ($filtroRapido === 'proximos_7') ? 'btn-primary' : 'btn-outline-primary' ?> fw-bold py-1 px-2">
                📅 Próx. 7 Días (<?= $kpisPasajes['viajan_proximos_7'] ?? 0 ?>)
            </a>
            <a href="?tab=activos&filtro_rapido=abiertas" class="btn btn-sm <?= ($filtroRapido === 'abiertas') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?> fw-bold py-1 px-2">
                ⏳ Abiertas (<?= $kpisPasajes['fecha_abierta'] ?? 0 ?>)
            </a>
            <a href="?tab=activos&filtro_rapido=reservas" class="btn btn-sm <?= ($filtroRapido === 'reservas') ? 'btn-danger' : 'btn-outline-danger' ?> fw-bold py-1 px-2">
                ⚠️ Reservas (<?= $kpisPasajes['reservas_pendientes'] ?? 0 ?>)
            </a>
        </div>
    <?php endif; ?>
</div>