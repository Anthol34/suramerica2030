    <div class="tab-pane fade <?= ($tabActivo == 'liquidaciones') ? 'show active' : '' ?>" id="liquidaciones">
        <!-- BARRA DE FILTROS -->
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body py-3">
                <form method="GET" action="/suramerica2030/finanzas" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="liquidaciones">
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_inicio'] ?? date('Y-m-01')) ?>" required>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_fin'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Base de Fecha</label>
                        <select name="tipo_fecha" class="form-select form-select-sm">
                            <option value="venta" <?= ($filtros['tipo_fecha'] ?? 'venta') === 'venta' ? 'selected' : '' ?>>📅 Fecha de Venta</option>
                            <option value="viaje" <?= ($filtros['tipo_fecha'] ?? '') === 'viaje' ? 'selected' : '' ?>>🚌 Fecha de Viaje</option>
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
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

                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Modalidad</label>
                        <select name="modalidad" class="form-select form-select-sm">
                            <option value="TODAS" <?= ($filtros['modalidad'] ?? 'TODAS') === 'TODAS' ? 'selected' : '' ?>>🌐 Todas las Modalidades</option>
                            <option value="CONEXIONES" <?= ($filtros['modalidad'] ?? '') === 'CONEXIONES' ? 'selected' : '' ?>>🔀 Conexiones Locales</option>
                            <option value="PANAMERICANA" <?= ($filtros['modalidad'] ?? '') === 'PANAMERICANA' ? 'selected' : '' ?>>🚌 Panamericana Internacional</option>
                            <option value="EXPRESOS_SURAMERICA" <?= ($filtros['modalidad'] ?? '') === 'EXPRESOS_SURAMERICA' ? 'selected' : '' ?>>🚍 Expresos Suramérica</option>
                            <option value="PANA_EXPRESOS" <?= ($filtros['modalidad'] ?? '') === 'PANA_EXPRESOS' ? 'selected' : '' ?>>⚡ Pana-Expresos</option>
                            <option value="ESTANDAR" <?= ($filtros['modalidad'] ?? '') === 'ESTANDAR' ? 'selected' : '' ?>>➡️ Directo Estándar</option>
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
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
                    
                    <div class="col-12 d-flex justify-content-end gap-1 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                            🔍 Consultar Liquidaciones
                        </button>
                        <a href="/suramerica2030/finanzas?tab=liquidaciones" class="btn btn-outline-secondary btn-sm px-3" title="Restablecer">
                            🔄 Limpiar
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
                                    data-modalidad="<?= htmlspecialchars($m['modalidad'] ?? '') ?>"
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
                                    <td class="small" style="max-width: 170px;">
                                        <div class="text-truncate fw-bold" title="<?= htmlspecialchars($m['ruta']) ?>">
                                            <?= htmlspecialchars($m['ruta']) ?>
                                        </div>
                                        <div class="mt-1">
                                            <?= $m['modalidad_badge'] ?? '' ?>
                                        </div>
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

