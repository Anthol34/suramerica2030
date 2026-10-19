<!-- TARJETAS KPI DE GESTIÓN DE ENCOMIENDAS -->
<?php
$tabActual = $filtros['tab'] ?? 'activos';
$estadoFiltro = $filtros['estado'] ?? '';
?>
<div class="kpi-row-enc row g-3 mb-4">
    <!-- KPI 1: TOTAL ACTIVAS -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=activos" class="card kpi-card-enc shadow-sm border-start border-4 border-primary h-100 <?= ($tabActual == 'activos' && empty($estadoFiltro)) ? 'active-kpi' : '' ?>" title="Clic para ver todas las encomiendas activas en el sistema">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-enc bg-primary bg-opacity-10 text-primary me-3">
                    📦
                </div>
                <div class="overflow-hidden">
                    <div class="text-muted small fw-bold text-uppercase text-truncate">Total Activas</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($kpis['total_activas'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        $<?= number_format($kpis['total_activas_usd'] ?? 0, 2) ?> | <?= number_format($kpis['total_activas_kg'] ?? 0, 1) ?> Kg
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 2: EN BODEGA ORIGEN -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=activos&estado=Recibido" class="card kpi-card-enc shadow-sm border-start border-4 border-secondary h-100 <?= ($tabActual == 'activos' && $estadoFiltro == 'Recibido') ? 'active-kpi' : '' ?>" title="Clic para filtrar encomiendas recibidas en bodega de origen">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-enc bg-secondary bg-opacity-10 text-secondary me-3">
                    🏬
                </div>
                <div class="overflow-hidden">
                    <div class="text-secondary small fw-bold text-uppercase text-truncate">En Bodega Origen</div>
                    <h4 class="fw-bold mb-0 text-secondary"><?= number_format($kpis['total_recibidos'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        <?= number_format($kpis['total_recibidos_kg'] ?? 0, 1) ?> Kg por despachar
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 3: EN TRÁNSITO -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=activos&estado=En+Tr%C3%A1nsito" class="card kpi-card-enc shadow-sm border-start border-4 border-primary h-100 <?= ($tabActual == 'activos' && $estadoFiltro == 'En Tránsito') ? 'active-kpi' : '' ?>" title="Clic para filtrar encomiendas que viajan en buses">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-enc bg-primary bg-opacity-10 text-primary me-3">
                    🚚
                </div>
                <div class="overflow-hidden">
                    <div class="text-primary small fw-bold text-uppercase text-truncate">En Camino (Tránsito)</div>
                    <h4 class="fw-bold mb-0 text-primary"><?= number_format($kpis['total_en_transito'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        <?= number_format($kpis['total_en_transito_kg'] ?? 0, 1) ?> Kg en ruta
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 4: LLEGÓ A OFICINA -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=activos&estado=En+Oficina" class="card kpi-card-enc shadow-sm border-start border-4 border-info h-100 <?= ($tabActual == 'activos' && $estadoFiltro == 'En Oficina') ? 'active-kpi' : '' ?>" title="Clic para filtrar encomiendas en sucursal listas para entrega">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-enc bg-info bg-opacity-10 text-info me-3">
                    🏢
                </div>
                <div class="overflow-hidden">
                    <div class="text-info small fw-bold text-uppercase text-truncate">Llegó a Oficina</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($kpis['total_en_oficina'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        Disponibles p/ retiro cliente
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 5: ENTREGADAS -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=historial&estado=Entregado" class="card kpi-card-enc shadow-sm border-start border-4 border-success h-100 <?= ($tabActual == 'historial' && $estadoFiltro == 'Entregado') ? 'active-kpi' : '' ?>" title="Clic para ver encomiendas entregadas">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-enc bg-success bg-opacity-10 text-success me-3">
                    ✅
                </div>
                <div class="overflow-hidden">
                    <div class="text-success small fw-bold text-uppercase text-truncate">Entregadas Hoy / Total</div>
                    <h4 class="fw-bold mb-0 text-success">
                        <?= number_format($kpis['entregadas_hoy'] ?? 0) ?>
                        <span class="fs-6 fw-normal text-muted">hoy</span>
                    </h4>
                    <div class="small text-muted mt-1 text-truncate">
                        <?= number_format($kpis['total_entregadas'] ?? 0) ?> entregadas ($<?= number_format($kpis['total_entregadas_usd'] ?? 0, 2) ?>)
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>