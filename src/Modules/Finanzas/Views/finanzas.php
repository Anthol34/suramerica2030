<link rel="stylesheet" href="/suramerica2030/public/assets/css/finanzas.css">

<?php
$tabActivo = $_GET['tab'] ?? 'liquidaciones';
$filtros = $liquidacion['filtros'] ?? [];
$resumen = $liquidacion['resumen'] ?? [];
$porOperador = $liquidacion['por_operador'] ?? [];
$movimientos = $liquidacion['movimientos'] ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="text-success fw-bold mb-0">Gestión Financiera y Liquidaciones</h3>
        <p class="text-muted small mb-0">Liquidación contable a socios de ruta (Panamericana, Suramérica 2030, conexiones), control de utilidad y comisiones.</p>
    </div>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
        <?= $_GET['status'] == 'success' ? '✅ Reglas financieras guardadas con éxito.' : '❌ Error: ' . htmlspecialchars($_GET['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= ($tabActivo == 'liquidaciones') ? 'active fw-bold' : '' ?>" data-bs-toggle="tab" data-bs-target="#liquidaciones" type="button">
            🤝 Liquidación a Socios y Operadores
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= ($tabActivo == 'sucursales') ? 'active fw-bold' : '' ?>" data-bs-toggle="tab" data-bs-target="#sucursales" type="button">
            🏢 Gastos y Comisiones (Oficinas)
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- PESTAÑA 1: LIQUIDACIÓN A SOCIOS Y OPERADORES -->
    <div class="tab-pane fade <?= ($tabActivo == 'liquidaciones') ? 'show active' : '' ?>" id="liquidaciones">
        <!-- BARRA DE FILTROS -->
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body py-3">
                <form method="GET" action="/suramerica2030/finanzas" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="liquidaciones">
                    
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_inicio'] ?? date('Y-m-01')) ?>" required>
                    </div>
                    
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_fin'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Base de Fecha</label>
                        <select name="tipo_fecha" class="form-select form-select-sm">
                            <option value="venta" <?= ($filtros['tipo_fecha'] ?? 'venta') === 'venta' ? 'selected' : '' ?>>📅 Fecha de Venta</option>
                            <option value="viaje" <?= ($filtros['tipo_fecha'] ?? '') === 'viaje' ? 'selected' : '' ?>>🚌 Fecha de Viaje</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Operador / Socio</label>
                        <select name="operador" class="form-select form-select-sm">
                            <option value="TODOS" <?= ($filtros['operador'] ?? 'TODOS') === 'TODOS' ? 'selected' : '' ?>>🌐 Todos los Operadores</option>
                            <?php foreach($operadores as $op): ?>
                                <option value="<?= htmlspecialchars($op) ?>" <?= ($filtros['operador'] ?? '') === $op ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($op) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Sucursal</label>
                        <select name="id_sucursal" class="form-select form-select-sm">
                            <option value="" <?= empty($filtros['id_sucursal']) || $filtros['id_sucursal'] === 'TODAS' ? 'selected' : '' ?>>🏢 Todas las Sucursales</option>
                            <?php foreach($sucursales as $s): ?>
                                <option value="<?= $s['ID_SUCURSAL'] ?>" <?= (isset($filtros['id_sucursal']) && $filtros['id_sucursal'] == $s['ID_SUCURSAL']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['CIUDAD']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 col-sm-12 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-bold">
                            🔍 Consultar
                        </button>
                        <a href="/suramerica2030/finanzas?tab=liquidaciones" class="btn btn-outline-secondary btn-sm" title="Restablecer">
                            🔄
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- TARJETAS KPI -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card shadow-sm border-start border-4 border-primary h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon-box bg-primary bg-opacity-10 text-primary me-3">
                            💵
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Recaudado (PVP)</div>
                            <h4 class="fw-bold mb-0 text-dark">$ <?= number_format($resumen['total_recaudado'] ?? 0, 2) ?></h4>
                            <div class="small text-muted mt-1">
                                <?= $resumen['total_boletos'] ?? 0 ?> Pasajes | $<?= number_format($resumen['total_sobrepeso_usd'] ?? 0, 2) ?> Sobrepeso
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card shadow-sm border-start border-4 border-danger h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon-box bg-danger bg-opacity-10 text-danger me-3">
                            🤝
                        </div>
                        <div>
                            <div class="text-danger small fw-bold text-uppercase">Total a Liquidar (Socios)</div>
                            <h4 class="fw-bold mb-0 text-danger">$ <?= number_format($resumen['total_liquidar'] ?? 0, 2) ?></h4>
                            <div class="small text-muted mt-1">
                                A transferir a operadores externos
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card shadow-sm border-start border-4 border-success h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon-box bg-success bg-opacity-10 text-success me-3">
                            📈
                        </div>
                        <div>
                            <div class="text-success small fw-bold text-uppercase">Utilidad Neta Agencia</div>
                            <h4 class="fw-bold mb-0 text-success">$ <?= number_format($resumen['total_utilidad'] ?? 0, 2) ?></h4>
                            <div class="small text-muted mt-1">
                                Margen neto: <strong><?= $resumen['margen_promedio_pct'] ?? 0 ?>%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card shadow-sm border-start border-4 border-info h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon-box bg-info bg-opacity-10 text-info me-3">
                            🧳
                        </div>
                        <div>
                            <div class="text-info small fw-bold text-uppercase">Sobrepeso Equipaje</div>
                            <h4 class="fw-bold mb-0 text-dark"><?= number_format($resumen['total_sobrepeso_kg'] ?? 0, 1) ?> <span class="fs-6 fw-normal text-muted">Kg</span></h4>
                            <div class="small text-muted mt-1">
                                $ <?= number_format($resumen['total_sobrepeso_usd'] ?? 0, 2) ?> recaudados
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA 1: RESUMEN POR OPERADOR / SOCIO -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0 fw-bold">🤝 Resumen de Liquidación por Operador y Socio</h6>
                <button type="button" class="btn btn-sm btn-outline-light" onclick="prepararImpresionGeneral()">
                    🖨️ Imprimir Resumen General
                </button>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaOperadores">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Operador / Socio</th>
                            <th class="text-center">Boletos</th>
                            <th class="text-end">PVP Boletos</th>
                            <th class="text-end">Costo Boletos</th>
                            <th class="text-center">Sobrepeso Kg</th>
                            <th class="text-end">Sobrepeso USD</th>
                            <th class="text-end text-danger fw-bold" style="background:#fff3cd;">Total a Liquidar</th>
                            <th class="text-end text-success fw-bold" style="background:#d1e7dd;">Utilidad Agencia</th>
                            <th class="text-center">Margen</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($porOperador)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    ℹ️ No se registraron operaciones para los filtros seleccionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($porOperador as $opNombre => $op): ?>
                                <?php
                                $badgeClass = 'bg-secondary';
                                if (stripos($opNombre, 'Panamericana') !== false) $badgeClass = 'badge-op-panamericana';
                                elseif (stripos($opNombre, 'Suramérica') !== false) $badgeClass = 'badge-op-suramerica';
                                elseif (stripos($opNombre, 'Empresa Local') !== false) $badgeClass = 'badge-op-local';
                                elseif (stripos($opNombre, 'Propio') !== false) $badgeClass = 'badge-op-propio';
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= $badgeClass ?> px-2 py-1 fs-6">
                                            <?= htmlspecialchars($opNombre) ?>
                                        </span>
                                        <?php if(!empty($op['banco'])): ?>
                                            <div class="small text-muted mt-1">
                                                🏦 <strong><?= htmlspecialchars($op['banco']) ?></strong> - <?= htmlspecialchars($op['numero_cuenta']) ?> 
                                                <span class="badge bg-light text-secondary border"><?= htmlspecialchars($op['tipo_cuenta']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold"><?= $op['cant_pasajes'] ?></td>
                                    <td class="text-end">$ <?= number_format($op['total_pvp_pasajes'], 2) ?></td>
                                    <td class="text-end text-muted">$ <?= number_format($op['total_costo_pasajes'], 2) ?></td>
                                    <td class="text-center">
                                        <?= $op['kg_sobrepeso'] > 0 ? number_format($op['kg_sobrepeso'], 1) . ' Kg' : '-' ?>
                                    </td>
                                    <td class="text-end">
                                        <?= $op['total_sobrepeso_usd'] > 0 ? '$ ' . number_format($op['total_sobrepeso_usd'], 2) : '-' ?>
                                    </td>
                                    <td class="text-end fw-bold text-danger fs-6" style="background:#fff8e1;">
                                        $ <?= number_format($op['total_a_liquidar'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-success fs-6" style="background:#e8f5e9;">
                                        $ <?= number_format($op['total_utilidad_agencia'], 2) ?>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <span class="badge <?= $op['margen_pct'] >= 10 ? 'bg-success' : ($op['margen_pct'] > 0 ? 'bg-info text-dark' : 'bg-light text-muted border') ?>">
                                            <?= $op['margen_pct'] ?> %
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" onclick="filtrarDetallePorOperador('<?= addslashes($opNombre) ?>')" title="Ver movimientos de este socio">
                                                🔍 Detalle
                                            </button>
                                            <button type="button" class="btn btn-outline-dark" onclick="prepararImpresionOperador('<?= addslashes($opNombre) ?>')" title="Generar estado de cuenta imprimible">
                                                🖨️ Estado de Cuenta
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-dark fw-bold">
                        <tr>
                            <td>TOTAL GENERAL</td>
                            <td class="text-center"><?= $resumen['total_boletos'] ?? 0 ?></td>
                            <td class="text-end">$ <?= number_format(array_sum(array_column($porOperador, 'total_pvp_pasajes')), 2) ?></td>
                            <td class="text-end">$ <?= number_format(array_sum(array_column($porOperador, 'total_costo_pasajes')), 2) ?></td>
                            <td class="text-center"><?= number_format($resumen['total_sobrepeso_kg'] ?? 0, 1) ?> Kg</td>
                            <td class="text-end">$ <?= number_format($resumen['total_sobrepeso_usd'] ?? 0, 2) ?></td>
                            <td class="text-end text-warning fs-6">$ <?= number_format($resumen['total_liquidar'] ?? 0, 2) ?></td>
                            <td class="text-end text-success fs-6">$ <?= number_format($resumen['total_utilidad'] ?? 0, 2) ?></td>
                            <td class="text-center"><?= $resumen['margen_promedio_pct'] ?? 0 ?> %</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- TABLA 2: DETALLE DESGLOSADO DE MOVIMIENTOS -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <h6 class="mb-0 fw-bold text-dark">📋 Movimientos Individuales Desglosados</h6>
                        <small class="text-muted" id="contadorMovimientos">Mostrando <?= count($movimientos) ?> registros</small>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">🔍</span>
                            <input type="text" id="filtroTextoMov" class="form-control" placeholder="Buscar pasajero, cédula, tramo, operador, factura..." onkeyup="filtrarMovimientos()">
                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('filtroTextoMov').value=''; filtrarMovimientos();">✕</button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="filtroTipoMov" class="form-select form-select-sm" onchange="filtrarMovimientos()">
                            <option value="TODOS">Todos los tipos</option>
                            <option value="PASAJE">Solo Pasajes / Boletos</option>
                            <option value="SOBREPESO">Solo Sobrepeso Equipaje</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body p-0 table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="table table-hover table-sm align-middle mb-0" id="tablaMovimientos">
                    <thead class="table-light text-muted small sticky-top">
                        <tr>
                            <th>Fecha</th>
                            <th>Doc / Factura</th>
                            <th>Pasajero</th>
                            <th>Sucursal</th>
                            <th>Ruta General</th>
                            <th>Tramo Liquidado</th>
                            <th>Operador</th>
                            <th>Tipo / Detalle</th>
                            <th class="text-end">PVP Cobrado</th>
                            <th class="text-end text-danger">Costo Socio</th>
                            <th class="text-end text-success">Utilidad Agencia</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyMovimientos">
                        <?php if (empty($movimientos)): ?>
                            <tr id="filaSinMovimientos">
                                <td colspan="11" class="text-center py-4 text-muted">
                                    ℹ️ No hay movimientos registrados para este período.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($movimientos as $m): ?>
                                <tr class="fila-movimiento"
                                    data-tipo="<?= htmlspecialchars($m['tipo']) ?>"
                                    data-operador="<?= htmlspecialchars($m['operador']) ?>"
                                    data-pvp="<?= $m['pvp'] ?>"
                                    data-costo="<?= $m['costo_socio'] ?>"
                                    data-utilidad="<?= $m['utilidad_agencia'] ?>">
                                    <td class="small">
                                        <div class="fw-bold"><?= htmlspecialchars(substr($m['fecha_venta'], 0, 10)) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;">Viaje: <?= htmlspecialchars($m['fecha_viaje'] ?: '-') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            FA #<?= $m['id_factura'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-truncate" style="max-width: 160px;" title="<?= htmlspecialchars($m['cliente']) ?>">
                                            <?= htmlspecialchars($m['cliente']) ?>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($m['documento'] ?? '') ?></small>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($m['sucursal']) ?></td>
                                    <td class="small text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($m['ruta']) ?>">
                                        <?= htmlspecialchars($m['ruta']) ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary small">
                                            <?= htmlspecialchars($m['tramo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary small">
                                            <?= htmlspecialchars($m['operador']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($m['tipo'] === 'SOBREPESO'): ?>
                                            <span class="badge bg-info text-dark">
                                                🧳 <?= $m['kg_sobrepeso'] ?> Kg
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">
                                                🎟️ Asiento <?= htmlspecialchars($m['asiento']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold">$ <?= number_format($m['pvp'], 2) ?></td>
                                    <td class="text-end text-danger fw-bold">$ <?= number_format($m['costo_socio'], 2) ?></td>
                                    <td class="text-end text-success fw-bold">$ <?= number_format($m['utilidad_agencia'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold sticky-bottom border-top">
                        <tr>
                            <td colspan="8" class="text-end">SUBTOTAL VISIBLE:</td>
                            <td class="text-end" id="subtotalPvp">$ <?= number_format(array_sum(array_column($movimientos, 'pvp')), 2) ?></td>
                            <td class="text-end text-danger" id="subtotalCosto">$ <?= number_format(array_sum(array_column($movimientos, 'costo_socio')), 2) ?></td>
                            <td class="text-end text-success" id="subtotalUtilidad">$ <?= number_format(array_sum(array_column($movimientos, 'utilidad_agencia')), 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- PESTAÑA SUCURSALES -->
    <div class="tab-pane fade <?= ($tabActivo == 'sucursales') ? 'show active' : '' ?>" id="sucursales">
        <div class="card shadow-sm border-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Sucursal</th>
                        <th class="text-center text-warning">Comisión Empleados (%)</th>
                        <th class="text-center text-danger">Gasto Fijo Diario ($)</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sucursales as $s): ?>
                        <tr>
                            <td class="fw-bold fs-6">📍 <?= htmlspecialchars($s['CIUDAD'] . ' - ' . $s['PAIS']) ?></td>
                            <td class="text-center fw-bold text-warning" style="background:#fff3cd;"><?= htmlspecialchars($s['COMISION_EMPLEADO_PCT'] ?? '0.00') ?> %</td>
                            <td class="text-center fw-bold text-danger" style="background:#f8d7da;">$ <?= number_format($s['GASTO_FIJO_DIARIO'] ?? 0, 2) ?> / día</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalSucursal<?= $s['ID_SUCURSAL'] ?>">⚙️ Configurar</button>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalSucursal<?= $s['ID_SUCURSAL'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header bg-dark text-white"><h5 class="modal-title fs-6">Configurar: <?= htmlspecialchars($s['CIUDAD']) ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                    <form action="/suramerica2030/finanzas/guardarSucursal" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id_sucursal" value="<?= $s['ID_SUCURSAL'] ?>">
                                            <div class="mb-3">
                                                <label class="text-warning fw-bold">Comisión de Ventas (%)</label>
                                                <input type="number" step="0.1" name="comision" class="form-control fw-bold" value="<?= $s['COMISION_EMPLEADO_PCT'] ?? '0.00' ?>" required>
                                                <small class="text-muted">Se descontará de la caja diaria.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="text-danger fw-bold">Gastos Operativos Diarios ($)</label>
                                                <input type="number" step="0.01" name="gasto" class="form-control fw-bold" value="<?= $s['GASTO_FIJO_DIARIO'] ?? '0.00' ?>" required>
                                                <small class="text-muted">Ej: Arriendo diario, comida, luz.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer p-1"><button type="submit" class="btn btn-success w-100 fw-bold">Guardar</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL IMPRIMIBLE: ESTADO DE CUENTA Y LIQUIDACIÓN A OPERADORES -->
<div class="modal fade" id="modalImprimirLiquidacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white no-print py-2">
                <h5 class="modal-title fs-6">🖨️ Estado de Cuenta y Liquidación a Socio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="contenedorDocumentoLiquidacion">
                <!-- Se llena dinámicamente con JS -->
            </div>
            <div class="modal-footer bg-light no-print py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success btn-sm fw-bold" onclick="window.print()">
                    🖨️ Imprimir Liquidación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT DE GESTIÓN Y FILTROS MULTIDIMENSIONALES -->

<script>
// =============== LIQUIDACIÓN A SOCIOS Y OPERADORES ===============
const liquidacionData = <?= json_encode([
    'filtros' => $filtros,
    'resumen' => $resumen,
    'por_operador' => $porOperador,
    'movimientos' => $movimientos
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function filtrarMovimientos() {
    const input = document.getElementById('filtroTextoMov');
    const selectTipo = document.getElementById('filtroTipoMov');
    if (!input || !selectTipo) return;

    const texto = (input.value || '').toLowerCase().trim();
    const tipo = selectTipo.value;
    const filas = document.querySelectorAll('#tbodyMovimientos .fila-movimiento');
    
    let visibles = 0;
    let sumPvp = 0;
    let sumCosto = 0;
    let sumUtil = 0;

    filas.forEach(fila => {
        const filaTipo = fila.getAttribute('data-tipo');
        const filaTexto = fila.innerText.toLowerCase();
        
        const cumpleTipo = (tipo === 'TODOS' || filaTipo === tipo);
        const cumpleTexto = (texto === '' || filaTexto.includes(texto));

        if (cumpleTipo && cumpleTexto) {
            fila.style.display = '';
            visibles++;
            sumPvp += parseFloat(fila.getAttribute('data-pvp') || 0);
            sumCosto += parseFloat(fila.getAttribute('data-costo') || 0);
            sumUtil += parseFloat(fila.getAttribute('data-utilidad') || 0);
        } else {
            fila.style.display = 'none';
        }
    });

    const cont = document.getElementById('contadorMovimientos');
    if (cont) cont.innerText = `Mostrando ${visibles} registros`;

    const elPvp = document.getElementById('subtotalPvp');
    const elCosto = document.getElementById('subtotalCosto');
    const elUtil = document.getElementById('subtotalUtilidad');

    if (elPvp) elPvp.innerText = '$ ' + sumPvp.toFixed(2);
    if (elCosto) elCosto.innerText = '$ ' + sumCosto.toFixed(2);
    if (elUtil) elUtil.innerText = '$ ' + sumUtil.toFixed(2);
}

function filtrarDetallePorOperador(nombreOp) {
    const inputTexto = document.getElementById('filtroTextoMov');
    if (inputTexto) {
        inputTexto.value = nombreOp;
        filtrarMovimientos();
        inputTexto.scrollIntoView({ behavior: 'smooth', block: 'center' });
        inputTexto.focus();
    }
}

function prepararImpresionOperador(nombreOp) {
    const op = liquidacionData.por_operador[nombreOp];
    if (!op) return;

    const f = liquidacionData.filtros;
    const movsOp = liquidacionData.movimientos.filter(m => m.operador === nombreOp);

    let html = `
        <div class="text-center pb-3 border-bottom mb-3">
            <h4 class="fw-bold mb-1 text-primary">SURAMÉRICA 2030</h4>
            <h6 class="text-secondary mb-1">AGENCIA INTERNACIONAL DE VIAJES Y TURISMO</h6>
            <h5 class="fw-bold text-dark mt-2">COMPROBANTE DE LIQUIDACIÓN A SOCIO / OPERADOR</h5>
        </div>

        <div class="row g-2 mb-3 small">
            <div class="col-6">
                <strong>Operador / Socio:</strong> <span class="fs-6 fw-bold text-primary">${nombreOp}</span><br>
                ${op.razon_social && op.razon_social !== nombreOp ? `<strong>Razón Social:</strong> ${op.razon_social}<br>` : ''}
                ${op.identificacion ? `<strong>RUC / Cédula / Doc:</strong> <span class="badge bg-light text-dark border">${op.identificacion}</span><br>` : ''}
                <strong>Período Liquidado:</strong> ${f.fecha_inicio} al ${f.fecha_fin}<br>
                <strong>Criterio de Fecha:</strong> ${f.tipo_fecha === 'viaje' ? 'Fecha de Viaje' : 'Fecha de Venta'}
            </div>
            <div class="col-6 text-end">
                <strong>Fecha Emisión:</strong> <?= date('d/m/Y H:i') ?><br>
                <strong>Sucursal / Base:</strong> ${f.id_sucursal === 'TODAS' || !f.id_sucursal ? 'Todas las Sucursales' : 'Sucursal #' + f.id_sucursal}<br>
                ${op.banco ? `<div class="mt-1 p-2 bg-white border rounded text-start d-inline-block small shadow-sm">
                    <span class="fw-bold text-primary">🏦 Datos para Transferencia:</span><br>
                    <strong>Banco:</strong> ${op.banco} (${op.tipo_cuenta || 'Corriente'})<br>
                    <strong>N° Cuenta:</strong> <span class="font-monospace fw-bold text-dark">${op.numero_cuenta}</span><br>
                    <strong>Titular:</strong> ${op.titular_cuenta || nombreOp} ${op.titular_doc ? '(' + op.titular_doc + ')' : ''}
                </div>` : '<strong>Moneda:</strong> Dólares Americanos (USD)'}
            </div>
        </div>

        <div class="card bg-light border mb-3">
            <div class="card-body py-2">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="text-muted small">Boletos Transportados</div>
                        <div class="fw-bold fs-6">${op.cant_pasajes}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Costo Boletos</div>
                        <div class="fw-bold fs-6 text-dark">$ ${parseFloat(op.total_costo_pasajes).toFixed(2)}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Sobrepeso (${parseFloat(op.kg_sobrepeso).toFixed(1)} Kg)</div>
                        <div class="fw-bold fs-6 text-dark">$ ${parseFloat(op.total_sobrepeso_socio).toFixed(2)}</div>
                    </div>
                    <div class="col-3 bg-warning bg-opacity-25 rounded py-1">
                        <div class="text-danger small fw-bold">TOTAL A LIQUIDAR</div>
                        <div class="fw-bold fs-5 text-danger">$ ${parseFloat(op.total_a_liquidar).toFixed(2)}</div>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="fw-bold small text-uppercase mb-2">Desglose de Servicios y Pasajes (${movsOp.length} ítems):</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle small mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Factura</th>
                        <th>Pasajero</th>
                        <th>Ruta / Tramo</th>
                        <th>Concepto</th>
                        <th class="text-end">PVP</th>
                        <th class="text-end">A Liquidar</th>
                    </tr>
                </thead>
                <tbody>
    `;

    movsOp.forEach(m => {
        html += `
            <tr>
                <td>${(m.fecha_venta || '').substring(0, 10)}</td>
                <td>#${m.id_factura}</td>
                <td>${m.cliente}</td>
                <td>${m.tramo}</td>
                <td>${m.detalle}</td>
                <td class="text-end">$ ${parseFloat(m.pvp).toFixed(2)}</td>
                <td class="text-end fw-bold text-danger">$ ${parseFloat(m.costo_socio).toFixed(2)}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-end">TOTAL LIQUIDADO AL SOCIO:</td>
                        <td class="text-end">$ ${(parseFloat(op.total_pvp_pasajes) + parseFloat(op.total_sobrepeso_usd)).toFixed(2)}</td>
                        <td class="text-end text-danger fs-6">$ ${parseFloat(op.total_a_liquidar).toFixed(2)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-5 pt-4 text-center">
            <div class="col-6">
                <div class="border-top border-dark mx-4 pt-2">
                    <strong>Suramérica 2030</strong><br>
                    <span class="small text-muted">Departamento de Finanzas y Control</span>
                </div>
            </div>
            <div class="col-6">
                <div class="border-top border-dark mx-4 pt-2">
                    <strong>${nombreOp}</strong><br>
                    <span class="small text-muted">Recibido Conforme / Firma Autorizada</span>
                </div>
            </div>
        </div>
    `;

    document.getElementById('contenedorDocumentoLiquidacion').innerHTML = html;
    const modalEl = document.getElementById('modalImprimirLiquidacion');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function prepararImpresionGeneral() {
    const f = liquidacionData.filtros;
    const res = liquidacionData.resumen;
    const ops = liquidacionData.por_operador;

    let html = `
        <div class="text-center pb-3 border-bottom mb-3">
            <h4 class="fw-bold mb-1 text-primary">SURAMÉRICA 2030</h4>
            <h6 class="text-secondary mb-1">AGENCIA INTERNACIONAL DE VIAJES Y ENCOMIENDAS</h6>
            <h5 class="fw-bold text-dark mt-2">ESTADO GENERAL DE LIQUIDACIÓN Y UTILIDAD A SOCIOS</h5>
        </div>

        <div class="row g-2 mb-3 small">
            <div class="col-6">
                <strong>Período:</strong> ${f.fecha_inicio} al ${f.fecha_fin}<br>
                <strong>Base de Consulta:</strong> ${f.tipo_fecha === 'viaje' ? 'Fecha de Viaje' : 'Fecha de Venta'}
            </div>
            <div class="col-6 text-end">
                <strong>Fecha Emisión:</strong> <?= date('d/m/Y H:i') ?><br>
                <strong>Total Recaudado:</strong> $ ${parseFloat(res.total_recaudado).toFixed(2)}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle small mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Operador / Socio</th>
                        <th class="text-center">Boletos</th>
                        <th class="text-end">PVP Boletos</th>
                        <th class="text-end">Costo Boletos</th>
                        <th class="text-center">Sobrepeso</th>
                        <th class="text-end">Total a Liquidar</th>
                        <th class="text-end">Utilidad Agencia</th>
                        <th class="text-center">Margen</th>
                    </tr>
                </thead>
                <tbody>
    `;

    for (const [opNombre, op] of Object.entries(ops)) {
        html += `
            <tr>
                <td class="fw-bold">${opNombre}</td>
                <td class="text-center">${op.cant_pasajes}</td>
                <td class="text-end">$ ${parseFloat(op.total_pvp_pasajes).toFixed(2)}</td>
                <td class="text-end">$ ${parseFloat(op.total_costo_pasajes).toFixed(2)}</td>
                <td class="text-center">${op.kg_sobrepeso > 0 ? parseFloat(op.kg_sobrepeso).toFixed(1) + ' Kg ($' + parseFloat(op.total_sobrepeso_usd).toFixed(2) + ')' : '-'}</td>
                <td class="text-end fw-bold text-danger">$ ${parseFloat(op.total_a_liquidar).toFixed(2)}</td>
                <td class="text-end fw-bold text-success">$ ${parseFloat(op.total_utilidad_agencia).toFixed(2)}</td>
                <td class="text-center">${op.margen_pct} %</td>
            </tr>
        `;
    }

    html += `
                </tbody>
                <tfoot class="table-dark fw-bold">
                    <tr>
                        <td>TOTAL GENERAL</td>
                        <td class="text-center">${res.total_boletos}</td>
                        <td class="text-end">$ ${parseFloat(res.total_recaudado - res.total_sobrepeso_usd).toFixed(2)}</td>
                        <td class="text-end">$ ${parseFloat(res.total_liquidar - res.total_sobrepeso_usd).toFixed(2)}</td>
                        <td class="text-center">${parseFloat(res.total_sobrepeso_kg).toFixed(1)} Kg</td>
                        <td class="text-end text-warning">$ ${parseFloat(res.total_liquidar).toFixed(2)}</td>
                        <td class="text-end text-success">$ ${parseFloat(res.total_utilidad).toFixed(2)}</td>
                        <td class="text-center">${res.margen_promedio_pct} %</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-5 pt-4 text-center">
            <div class="col-6">
                <div class="border-top border-dark mx-4 pt-2">
                    <strong>Administración Central</strong><br>
                    <span class="small text-muted">Suramérica 2030 Ecuador</span>
                </div>
            </div>
            <div class="col-6">
                <div class="border-top border-dark mx-4 pt-2">
                    <strong>Auditoría Contable</strong><br>
                    <span class="small text-muted">Control Financiero de Rutas</span>
                </div>
            </div>
        </div>
    `;

    document.getElementById('contenedorDocumentoLiquidacion').innerHTML = html;
    const modalEl = document.getElementById('modalImprimirLiquidacion');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

</script>
