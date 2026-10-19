<!-- TARJETAS KPI DE VENTA DE PASAJES (Visible en Activos e Historial) -->

<div class="kpi-row-pasajes row g-3 mb-4">
    <!-- KPI 1: TOTAL ACTIVOS -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=activos" class="card kpi-card-pasaje shadow-sm border-start border-4 border-primary h-100 <?= ($tabActual == 'activos' && empty($filtroRapido)) ? 'active-kpi' : '' ?>" title="Clic para ver todos los pasajes activos (próximos viajes y abiertos)">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-pasaje bg-primary bg-opacity-10 text-primary me-3">
                    🎫
                </div>
                <div class="overflow-hidden">
                    <div class="text-muted small fw-bold text-uppercase text-truncate">Boletos Activos</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($kpisPasajes['total_activos'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        $<?= number_format($kpisPasajes['total_activos_usd'] ?? 0, 2) ?> USD | <?= $kpisPasajes['viajan_proximos_7'] ?? 0 ?> próx. 7 días
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 2: VIAJAN HOY -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=activos&filtro_rapido=hoy" class="card kpi-card-pasaje shadow-sm border-start border-4 border-info h-100 <?= ($tabActual == 'activos' && $filtroRapido === 'hoy') ? 'active-kpi' : '' ?>" title="Clic para filtrar pasajeros con viaje programado para hoy">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-pasaje bg-info bg-opacity-10 text-info me-3">
                    🚌
                </div>
                <div class="overflow-hidden">
                    <div class="text-info small fw-bold text-uppercase text-truncate">Viajan Hoy</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($kpisPasajes['viajan_hoy'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        <?= ($kpisPasajes['viajan_hoy'] ?? 0) > 0 ? '$'.number_format($kpisPasajes['viajan_hoy_usd'] ?? 0, 2).' en salidas' : 'Sin salidas hoy' ?>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 3: VENTAS HOY -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=historial&tipo_fecha=emision&fecha_inicio=<?= date('Y-m-d') ?>&fecha_fin=<?= date('Y-m-d') ?>" class="card kpi-card-pasaje shadow-sm border-start border-4 border-success h-100 <?= ($tabActual == 'historial' && ($filtros['tipo_fecha'] ?? '') === 'emision' && ($filtros['fecha_inicio'] ?? '') === date('Y-m-d')) ? 'active-kpi' : '' ?>" title="Clic para ver boletos emitidos el día de hoy">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-pasaje bg-success bg-opacity-10 text-success me-3">
                    💵
                </div>
                <div class="overflow-hidden">
                    <div class="text-success small fw-bold text-uppercase text-truncate">Ventas de Hoy</div>
                    <h4 class="fw-bold mb-0 text-success"><?= number_format($kpisPasajes['vendidos_hoy'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        $<?= number_format($kpisPasajes['cobrado_hoy_usd'] ?? 0, 2) ?> USD cobrados
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 4: FECHAS ABIERTAS -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=activos&filtro_rapido=abiertas" class="card kpi-card-pasaje shadow-sm border-start border-4 border-warning h-100 <?= ($tabActual == 'activos' && $filtroRapido === 'abiertas') ? 'active-kpi' : '' ?>" title="Clic para filtrar boletos con fecha abierta pendientes de programar">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-pasaje bg-warning bg-opacity-10 text-warning me-3">
                    ⏳
                </div>
                <div class="overflow-hidden">
                    <div class="text-warning small fw-bold text-uppercase text-truncate">Fechas Abiertas</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($kpisPasajes['fecha_abierta'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        Pendientes de asignar bus
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 5: RESERVAS CON SALDO -->
    <div class="kpi-col-5 col-xl col-md-4 col-sm-6">
        <a href="?tab=activos&filtro_rapido=reservas" class="card kpi-card-pasaje shadow-sm border-start border-4 border-danger h-100 <?= ($tabActual == 'activos' && $filtroRapido === 'reservas') ? 'active-kpi' : '' ?>" title="Clic para filtrar reservas activas con saldo por cobrar">
            <div class="card-body d-flex align-items-center p-3">
                <div class="kpi-icon-box-pasaje bg-danger bg-opacity-10 text-danger me-3">
                    ⚠️
                </div>
                <div class="overflow-hidden">
                    <div class="text-danger small fw-bold text-uppercase text-truncate">Reservas con Saldo</div>
                    <h4 class="fw-bold mb-0 text-danger"><?= number_format($kpisPasajes['reservas_pendientes'] ?? 0) ?></h4>
                    <div class="small text-muted mt-1 text-truncate">
                        $<?= number_format($kpisPasajes['saldo_reservas_usd'] ?? 0, 2) ?> USD por liquidar
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>