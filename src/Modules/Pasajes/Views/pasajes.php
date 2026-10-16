<?php 
$puedeEditarPrecios = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_editar_precios']) && $_SESSION['p_editar_precios'] == 1); 
$puedeAnularDirecto = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_anular']) && $_SESSION['p_anular'] == 1);
?>
<link rel="stylesheet" href="/suramerica2030/public/assets/css/pasajes.css">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="text-primary">Gestión y Venta de Pasajes</h3>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoPasaje">+ Registrar Nuevo Pasaje</button>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= $_GET['status'] == 'success' ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show shadow-sm">
        <?= $_GET['status'] == 'success' ? ('✅ ' . htmlspecialchars($_GET['msg'] ?? 'Operación procesada exitosamente.')) : ('❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Ocurrió un inconveniente.')) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- NAVEGACIÓN DE PESTAÑAS -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= (!isset($filtros['tab']) || $filtros['tab'] == 'activos') ? 'active fw-bold' : '' ?>" href="?tab=activos">⏳ Viajes Activos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= (isset($filtros['tab']) && $filtros['tab'] == 'historial') ? 'active fw-bold' : '' ?>" href="?tab=historial">📚 Buscador e Historial</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= (isset($filtros['tab']) && $filtros['tab'] == 'equipaje') ? 'active fw-bold' : '' ?>" href="?tab=equipaje">🧳 Módulo de Equipaje & Sobrepeso</a>
    </li>
</ul>

<!-- BUSCADOR E HISTORIAL AVANZADO (Solo visible en Historial) -->
<?php if(isset($filtros['tab']) && $filtros['tab'] == 'historial'): ?>
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-5">🔍</span>
            <h5 class="mb-0 fw-bold text-dark">Búsqueda Avanzada de Pasajeros</h5>
        </div>
        <!-- Accesos Rápidos de Fechas (Chips) -->
        <div class="d-flex flex-wrap gap-1 align-items-center" id="chipsFiltrosRapidos">
            <span class="text-muted small me-1 fw-semibold">⚡ Rápidos:</span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" onclick="aplicarRangoRapido('hoy')">Hoy</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" onclick="aplicarRangoRapido('manana')">Mañana</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" onclick="aplicarRangoRapido('esta_semana')">Esta Semana</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" onclick="aplicarRangoRapido('este_mes')">Este Mes</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" onclick="aplicarRangoRapido('proximos_7')">Próx. 7 Días</button>
            <?php if(!empty($filtros['buscar']) || !empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin']) || !empty($filtros['dia_semana']) || !empty($filtros['ruta_id']) || !empty($filtros['estado']) || ($filtros['tipo_fecha'] ?? '') === 'abierta'): ?>
                <a href="/suramerica2030/pasajes?tab=historial" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill fw-bold">✖ Limpiar Filtros</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body bg-light p-3 p-md-4">
        <form action="/suramerica2030/pasajes" method="GET" id="formFiltroHistorial">
            <input type="hidden" name="tab" value="historial">
            
            <div class="row g-3">
                <!-- 1. Búsqueda por texto (Cédula, Pasajero, Factura, Asiento) -->
                <div class="col-12 col-md-5 col-lg-4">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        👤 Pasajero / Cédula / Factura / Asiento
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">🔎</span>
                        <input type="text" name="buscar" class="form-control" placeholder="Ej: 131415..., Juan, Fac #12, Asiento 4" value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
                    </div>
                </div>

                <!-- 2. Tipo de Fecha (Viaje / Venta / Abierta) -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        🗓️ Filtrar Por
                    </label>
                    <select name="tipo_fecha" id="filtro_tipo_fecha" class="form-select" onchange="actualizarEstadoCamposFecha()">
                        <option value="viaje" <?= ($filtros['tipo_fecha'] ?? 'viaje') === 'viaje' ? 'selected' : '' ?>>📅 Fecha de Viaje</option>
                        <option value="emision" <?= ($filtros['tipo_fecha'] ?? '') === 'emision' ? 'selected' : '' ?>>🧾 Fecha de Emisión</option>
                        <option value="abierta" <?= ($filtros['tipo_fecha'] ?? '') === 'abierta' ? 'selected' : '' ?>>⏳ Fecha Abierta</option>
                    </select>
                </div>

                <!-- 3. Día de la Semana -->
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        📆 Día de la Semana
                    </label>
                    <select name="dia_semana" id="filtro_dia_semana" class="form-select">
                        <option value="">-- Todos los días --</option>
                        <option value="2" <?= ($filtros['dia_semana'] ?? '') === '2' ? 'selected' : '' ?>>🔹 Lunes</option>
                        <option value="3" <?= ($filtros['dia_semana'] ?? '') === '3' ? 'selected' : '' ?>>🔹 Martes</option>
                        <option value="4" <?= ($filtros['dia_semana'] ?? '') === '4' ? 'selected' : '' ?>>🔹 Miércoles</option>
                        <option value="5" <?= ($filtros['dia_semana'] ?? '') === '5' ? 'selected' : '' ?>>🔹 Jueves</option>
                        <option value="6" <?= ($filtros['dia_semana'] ?? '') === '6' ? 'selected' : '' ?>>🔹 Viernes</option>
                        <option value="7" <?= ($filtros['dia_semana'] ?? '') === '7' ? 'selected' : '' ?>>🔹 Sábado</option>
                        <option value="1" <?= ($filtros['dia_semana'] ?? '') === '1' ? 'selected' : '' ?>>🔹 Domingo</option>
                        <option disabled>──────────</option>
                        <option value="fin_semana" <?= ($filtros['dia_semana'] ?? '') === 'fin_semana' ? 'selected' : '' ?>>🏖️ Fin de Semana (Sáb y Dom)</option>
                        <option value="entre_semana" <?= ($filtros['dia_semana'] ?? '') === 'entre_semana' ? 'selected' : '' ?>>💼 Entre Semana (Lun a Vie)</option>
                    </select>
                </div>

                <!-- 4. Rango de Fechas: Desde -->
                <div class="col-6 col-md-3 col-lg-3" id="col_fecha_inicio">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        Desde (Fecha)
                    </label>
                    <input type="date" name="fecha_inicio" id="filtro_fecha_inicio" class="form-control" value="<?= htmlspecialchars($filtros['fecha_inicio'] ?? '') ?>">
                </div>

                <!-- 5. Rango de Fechas: Hasta -->
                <div class="col-6 col-md-3 col-lg-3" id="col_fecha_fin">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        Hasta (Fecha)
                    </label>
                    <input type="date" name="fecha_fin" id="filtro_fecha_fin" class="form-control" value="<?= htmlspecialchars($filtros['fecha_fin'] ?? '') ?>">
                </div>

                <!-- 6. Ruta -->
                <div class="col-12 col-md-3 col-lg-4">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        📍 Ruta
                    </label>
                    <select name="ruta_id" class="form-select">
                        <option value="0">-- Todas las Rutas --</option>
                        <?php if(!empty($rutas)): foreach($rutas as $r): ?>
                            <option value="<?= $r['ID_RUTA'] ?>" <?= ((int)($filtros['ruta_id'] ?? 0) === (int)$r['ID_RUTA']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['RUT_ORIGEN'] . ' ➔ ' . $r['RUT_DESTINO']) ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>

                <!-- 7. Estado Financiero / Pasaje -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        💳 Estado
                    </label>
                    <select name="estado" class="form-select">
                        <option value="">-- Todos --</option>
                        <option value="PAGADO" <?= ($filtros['estado'] ?? '') === 'PAGADO' ? 'selected' : '' ?>>✅ Pagados</option>
                        <option value="RESERVA" <?= ($filtros['estado'] ?? '') === 'RESERVA' ? 'selected' : '' ?>>⚠️ Con Saldo (Reserva)</option>
                        <option value="DEVUELTO" <?= ($filtros['estado'] ?? '') === 'DEVUELTO' ? 'selected' : '' ?>>❌ Cancelados</option>
                    </select>
                </div>

                <!-- 8. Botones de Acción -->
                <div class="col-6 col-md-3 col-lg-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary fw-bold flex-grow-1 shadow-sm">
                        🔍 Buscar
                    </button>
                    <a href="/suramerica2030/pasajes?tab=historial" class="btn btn-outline-secondary" title="Restablecer filtros">
                        ↺ Limpiar
                    </a>
                </div>
            </div>
        </form>

        <!-- Resumen de Filtros Activos si hay alguno -->
        <?php 
        $hayFiltros = !empty($filtros['buscar']) || !empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin']) || !empty($filtros['dia_semana']) || !empty($filtros['ruta_id']) || !empty($filtros['estado']) || (($filtros['tipo_fecha'] ?? '') === 'abierta');
        ?>
        <?php if($hayFiltros): ?>
            <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small fw-bold text-primary">Filtros aplicados:</span>
                    <?php if(!empty($filtros['buscar'])): ?>
                        <span class="badge bg-primary">Texto: "<?= htmlspecialchars($filtros['buscar']) ?>"</span>
                    <?php endif; ?>
                    <?php if(($filtros['tipo_fecha'] ?? '') === 'abierta'): ?>
                        <span class="badge bg-warning text-dark">⏳ Solo Fechas Abiertas</span>
                    <?php else: ?>
                        <?php if(!empty($filtros['dia_semana'])): ?>
                            <?php 
                            $nomDias = [
                                '2' => 'Lunes', '3' => 'Martes', '4' => 'Miércoles', '5' => 'Jueves',
                                '6' => 'Viernes', '7' => 'Sábado', '1' => 'Domingo',
                                'fin_semana' => 'Fin de Semana (Sáb/Dom)', 'entre_semana' => 'Entre Semana (Lun-Vie)'
                            ];
                            ?>
                            <span class="badge bg-info text-dark">📆 Día: <?= $nomDias[$filtros['dia_semana']] ?? $filtros['dia_semana'] ?></span>
                        <?php endif; ?>
                        <?php if(!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])): ?>
                            <span class="badge bg-secondary"><?= ($filtros['tipo_fecha'] ?? 'viaje') === 'emision' ? 'Emisión' : 'Viaje' ?>: <?= date('d/m/Y', strtotime($filtros['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($filtros['fecha_fin'])) ?></span>
                        <?php elseif(!empty($filtros['fecha_inicio'])): ?>
                            <span class="badge bg-secondary">Desde: <?= date('d/m/Y', strtotime($filtros['fecha_inicio'])) ?></span>
                        <?php elseif(!empty($filtros['fecha_fin'])): ?>
                            <span class="badge bg-secondary">Hasta: <?= date('d/m/Y', strtotime($filtros['fecha_fin'])) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if(!empty($filtros['ruta_id']) && !empty($rutas)): ?>
                        <?php 
                        $nomRuta = '';
                        foreach($rutas as $r) { if($r['ID_RUTA'] == $filtros['ruta_id']) { $nomRuta = $r['RUT_ORIGEN'].' ➔ '.$r['RUT_DESTINO']; break; } }
                        ?>
                        <?php if($nomRuta): ?><span class="badge bg-dark">📍 <?= htmlspecialchars($nomRuta) ?></span><?php endif; ?>
                    <?php endif; ?>
                    <?php if(!empty($filtros['estado'])): ?>
                        <span class="badge <?= $filtros['estado'] === 'PAGADO' ? 'bg-success' : ($filtros['estado'] === 'RESERVA' ? 'bg-warning text-dark' : 'bg-danger') ?>">Estado: <?= $filtros['estado'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="small text-muted fw-bold">
                    <?= $totalRegistros ?? count($pasajes) ?> pasajero(s) encontrado(s)
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if(isset($filtros['tab']) && $filtros['tab'] == 'equipaje'): ?>

    <!-- 1. KPIS DEL MÓDULO DE EQUIPAJE & SOBREPESO -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-primary">
                <span class="text-muted small fw-bold text-uppercase">Pasajeros Bodega</span>
                <h3 class="fw-bold mb-0 text-dark"><?= number_format($kpisEquipaje['TOTAL_PASAJEROS_CON_EQUIPAJE'] ?? 0) ?></h3>
                <span class="small text-muted">Con bultos registrados</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-info">
                <span class="text-muted small fw-bold text-uppercase">Peso Total Bodega</span>
                <h3 class="fw-bold mb-0 text-info"><?= number_format($kpisEquipaje['TOTAL_KG_BODEGA'] ?? 0, 1) ?> <span class="fs-6 text-muted">Kg</span></h3>
                <span class="small text-muted">Carga total en buses</span>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-warning">
                <span class="text-muted small fw-bold text-uppercase">Exceso Cobrado</span>
                <h3 class="fw-bold mb-0 text-warning"><?= number_format($kpisEquipaje['TOTAL_EXCESO_KG'] ?? 0, 1) ?> <span class="fs-6 text-muted">Kg</span></h3>
                <span class="small text-muted">Kilos de sobrepeso</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-success">
                <span class="text-muted small fw-bold text-uppercase">Recaudado Sobrepeso</span>
                <h3 class="fw-bold mb-0 text-success">$ <?= number_format($kpisEquipaje['TOTAL_SOBREPESO_USD'] ?? 0, 2) ?> <span class="fs-6 text-muted">USD</span></h3>
                <span class="small text-muted">Ingresos activos en caja</span>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-danger">
                <span class="text-muted small fw-bold text-uppercase">Sobrepesos Anulados</span>
                <h3 class="fw-bold mb-0 text-danger"><?= number_format($kpisEquipaje['TOTAL_SOBREPESOS_ANULADOS'] ?? 0) ?></h3>
                <span class="small text-muted">Cobros revocados</span>
            </div>
        </div>
    </div>

    <!-- 2. BARRA DE FILTROS Y BÚSQUEDA CENTRAL DE EQUIPAJES -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-5">🧳</span>
                <h5 class="mb-0 fw-bold text-dark">Control y Búsqueda Central de Equipajes</h5>
            </div>
            <a href="/suramerica2030/pasajes?tab=equipaje" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill fw-bold">✖ Limpiar Filtros</a>
        </div>
        <div class="card-body bg-light p-3">
            <form method="GET" action="/suramerica2030/pasajes" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="equipaje">
                <div class="col-md-4 col-12">
                    <label class="form-label small fw-bold text-muted mb-1">Buscar por Tag, Pasajero, CI o N° Boleto</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">🏷️</span>
                        <input type="text" name="buscar" class="form-control border-start-0" placeholder="Ej: EQP-105-1, Juan Pérez, 17234..." value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Fecha Viaje</label>
                    <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filtros['fecha'] ?? '') ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Bus / Unidad</label>
                    <select name="bus_id" class="form-select">
                        <option value="">Todos los Buses</option>
                        <?php foreach($buses as $b): ?>
                            <option value="<?= $b['ID_BUS'] ?>" <?= (!empty($filtros['bus_id']) && $filtros['bus_id'] == $b['ID_BUS']) ? 'selected' : '' ?>>
                                #<?= htmlspecialchars($b['BUS_NUMERO'] ?? $b['ID_BUS']) ?> - <?= htmlspecialchars($b['BUS_PLACA']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Estado Sobrepeso</label>
                    <select name="estado_sp" class="form-select">
                        <option value="">Todos</option>
                        <option value="con_sobrepeso" <?= (!empty($filtros['estado_sp']) && $filtros['estado_sp'] == 'con_sobrepeso') ? 'selected' : '' ?>>Con Sobrepeso Activo</option>
                        <option value="sin_sobrepeso" <?= (!empty($filtros['estado_sp']) && $filtros['estado_sp'] == 'sin_sobrepeso') ? 'selected' : '' ?>>Sin Sobrepeso (Normal)</option>
                        <option value="anulado" <?= (!empty($filtros['estado_sp']) && $filtros['estado_sp'] == 'anulado') ? 'selected' : '' ?>>Sobrepeso Anulado</option>
                    </select>
                </div>
                <div class="col-md-2 col-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">🔍 Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. TABLA CENTRAL DE EQUIPAJES -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Pasajero / Boleto</th>
                    <th>Ruta y Bus</th>
                    <th>Piezas & Tags Registrados</th>
                    <th class="text-center">Peso Bodega</th>
                    <th>Estado Sobrepeso</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipajesCentral)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No se encontraron registros de equipaje con los filtros seleccionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($equipajesCentral as $eqp): 
                        $piezas = [];
                        if (!empty($eqp['SP_PIEZAS'])) {
                            $dec = json_decode($eqp['SP_PIEZAS'], true);
                            if (is_array($dec)) $piezas = $dec;
                        } elseif (!empty($eqp['EQUIPAJE_DETALLE'])) {
                            $dec = json_decode($eqp['EQUIPAJE_DETALLE'], true);
                            if (is_array($dec)) $piezas = $dec;
                        }
                        $cantPiezas = count($piezas) > 0 ? count($piezas) : (floatval($eqp['PESO_BODEGA'] ?? 0) > 0 ? 1 : 0);
                        $pb = floatval($eqp['PESO_BODEGA'] ?? 0);
                        $limite = floatval($eqp['LIMITE_EQUIPAJE_KG'] ?? 35);
                    ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($eqp['CLI_NOMBRE'].' '.$eqp['CLI_APELLIDO']) ?></strong><br>
                                <small class="text-muted">CI: <?= htmlspecialchars($eqp['ID_CLIENTE'] ?? 'S/N') ?></small>
                                <span class="badge bg-light text-dark border ms-1">Boleto #<?= $eqp['ID_PASAJE'] ?></span>
                                <?php if (!empty($eqp['CODIGO_SOCIO'])): ?>
                                    <span class="badge bg-warning text-dark border border-warning ms-1" title="Código de Boleto Socio Operador">🎫 <?= htmlspecialchars($eqp['CODIGO_SOCIO']) ?></span>
                                <?php endif; ?>
                                <span class="badge bg-secondary ms-1">Asiento: [<?= htmlspecialchars($eqp['PAX_ASIENTO'] ?? 'S/A') ?>]</span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($eqp['RUT_ORIGEN'].' ➔ '.$eqp['RUT_DESTINO']) ?></strong><br>
                                <small class="text-muted">
                                    📅 <?= htmlspecialchars($eqp['FECHA_VIAJE'] ?? $eqp['FA_FECHA'] ?? 'S/F') ?> &bull; 
                                    🚌 Placa: <?= htmlspecialchars($eqp['BUS_PLACA']) ?> <?= !empty($eqp['BUS_NUMERO']) ? "(#{$eqp['BUS_NUMERO']})" : '' ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="badge bg-primary"><?= $cantPiezas ?> bulto(s)</span>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if (!empty($piezas)): ?>
                                        <?php foreach (array_slice($piezas, 0, 3) as $pz): ?>
                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 10px;">
                                                🏷️ <?= htmlspecialchars($pz['codigo'] ?? $pz['tag'] ?? 'EQP-S/N') ?> (<?= number_format(floatval($pz['peso'] ?? 0), 1) ?> Kg)
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($piezas) > 3): ?>
                                            <span class="badge bg-secondary font-monospace" style="font-size: 10px;">+<?= count($piezas) - 3 ?> más</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="small text-muted font-monospace">🏷️ EQP-<?= $eqp['ID_PASAJE'] ?>-1</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $pb > $limite ? 'bg-danger' : 'bg-success' ?> fs-6">
                                    <?= number_format($pb, 1) ?> Kg
                                </span>
                                <div class="small text-muted mt-1">+10.0 Kg mano</div>
                            </td>
                            <td>
                                <?php if (($eqp['SP_ESTADO'] ?? '') === 'ACTIVO'): ?>
                                    <span class="badge bg-success fs-6">💵 $<?= number_format($eqp['SP_TOTAL'], 2) ?> USD</span>
                                    <div class="small fw-semibold text-dark mt-1">
                                        <?= number_format($eqp['SP_EXCESO_KG'], 1) ?> Kg exceso
                                        <?php if(!empty($eqp['SP_OPERADOR'])): ?>
                                            &bull; <span class="text-primary"><?= htmlspecialchars($eqp['SP_OPERADOR']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(!empty($eqp['SP_TRAMO'])): ?>
                                        <div class="small text-muted">Tramo: <?= htmlspecialchars($eqp['SP_TRAMO']) ?></div>
                                    <?php endif; ?>
                                <?php elseif (($eqp['SP_ESTADO'] ?? '') === 'ANULADO'): ?>
                                    <span class="badge bg-danger fs-6">❌ ANULADO</span>
                                    <div class="small text-danger fw-semibold mt-1">Cobro de $<?= number_format($eqp['SP_TOTAL'], 2) ?> revertido</div>
                                    <?php if (!empty($eqp['SP_MOTIVO_ANULACION'])): ?>
                                        <div class="small text-muted" title="<?= htmlspecialchars($eqp['SP_MOTIVO_ANULACION']) ?>">
                                            Motivo: <?= htmlspecialchars(mb_strimwidth($eqp['SP_MOTIVO_ANULACION'], 0, 28, '...')) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">Normal (Sin Exceso)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalPiezasDetalle<?= $eqp['ID_PASAJE'] ?>" title="Ver piezas registradas">
                                        🧳 Detalle
                                    </button>
                                    <a href="/suramerica2030/pasajes/imprimirEquipaje?id=<?= $eqp['ID_PASAJE'] ?>" target="_blank" class="btn btn-outline-primary" title="Reimprimir etiquetas adhesivas">
                                        🏷️ Etiquetas
                                    </a>
                                    <?php if (!empty($eqp['SP_ID_FACTURA'])): ?>
                                        <a href="/suramerica2030/ventas/ticket/<?= $eqp['SP_ID_FACTURA'] ?>" target="_blank" class="btn btn-outline-success" title="Ver Recibo de Sobrepeso">
                                            🧾 Recibo
                                        </a>
                                    <?php endif; ?>
                                    <?php if (($eqp['SP_ESTADO'] ?? '') === 'ACTIVO' && $puedeAnularDirecto): ?>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalAnularSpCentral<?= $eqp['ID_PASAJE'] ?>" title="Anular cobro de sobrepeso">
                                            ❌ Anular
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- PAGINACIÓN EQUIPAJE -->
        <?php if(isset($totalPaginas) && $totalPaginas > 1): ?>
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center border-0 pt-3 pb-3">
                <small class="text-muted fw-bold">Mostrando página <?= $pagina ?> de <?= $totalPaginas ?> (<?= $totalRegistros ?? count($equipajesCentral) ?> registros)</small>
                <ul class="pagination pagination-sm mb-0 shadow-sm">
                    <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link text-primary fw-bold" href="?tab=equipaje&pagina=<?= $pagina - 1 ?><?= !empty($queryString) ? '&' . $queryString : '' ?>">Anterior</a>
                    </li>
                    <?php 
                    $startP = max(1, $pagina - 2);
                    $endP = min($totalPaginas, $pagina + 2);
                    for($i = $startP; $i <= $endP; $i++): ?>
                        <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                            <a class="page-link" href="?tab=equipaje&pagina=<?= $i ?><?= !empty($queryString) ? '&' . $queryString : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                        <a class="page-link text-primary fw-bold" href="?tab=equipaje&pagina=<?= $pagina + 1 ?><?= !empty($queryString) ? '&' . $queryString : '' ?>">Siguiente</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODALES DEL MÓDULO DE EQUIPAJE CENTRAL (Ubicados fuera de la tabla para evitar distorsión del DOM) -->
    <?php if (!empty($equipajesCentral)): ?>
        <?php foreach ($equipajesCentral as $eqp): 
            $piezas = [];
            if (!empty($eqp['SP_PIEZAS'])) {
                $dec = json_decode($eqp['SP_PIEZAS'], true);
                if (is_array($dec)) $piezas = $dec;
            } elseif (!empty($eqp['EQUIPAJE_DETALLE'])) {
                $dec = json_decode($eqp['EQUIPAJE_DETALLE'], true);
                if (is_array($dec)) $piezas = $dec;
            }
            $pb = floatval($eqp['PESO_BODEGA'] ?? 0);
        ?>
            <!-- MODAL DETALLE DE PIEZAS -->
            <div class="modal fade text-start" id="modalPiezasDetalle<?= $eqp['ID_PASAJE'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-dark text-white py-2">
                            <h6 class="modal-title fw-bold mb-0">🧳 Piezas de Equipaje - Boleto #<?= $eqp['ID_PASAJE'] ?><?= !empty($eqp['CODIGO_SOCIO']) ? ' (Socio: '.htmlspecialchars($eqp['CODIGO_SOCIO']).')' : '' ?></h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="p-2 mb-3 bg-light rounded border">
                                <div class="row g-1 small">
                                    <div class="col-6"><strong>Pasajero:</strong> <?= htmlspecialchars($eqp['CLI_NOMBRE'].' '.$eqp['CLI_APELLIDO']) ?></div>
                                    <div class="col-6"><strong>Asiento:</strong> [<?= htmlspecialchars($eqp['PAX_ASIENTO'] ?? 'S/A') ?>]</div>
                                    <?php if (!empty($eqp['CODIGO_SOCIO'])): ?>
                                        <div class="col-12"><strong class="text-warning-emphasis">🎫 Boleto Empresa Aliada:</strong> <span class="badge bg-warning text-dark font-monospace"><?= htmlspecialchars($eqp['CODIGO_SOCIO']) ?></span></div>
                                    <?php endif; ?>
                                    <div class="col-12"><strong>Ruta:</strong> <?= htmlspecialchars($eqp['RUT_ORIGEN'].' ➔ '.$eqp['RUT_DESTINO']) ?></div>
                                    <div class="col-6"><strong>Bus:</strong> <?= htmlspecialchars($eqp['BUS_PLACA']) ?></div>
                                    <div class="col-6"><strong>Total Bodega:</strong> <?= number_format($pb, 1) ?> Kg</div>
                                </div>
                            </div>

                            <h6 class="fw-bold small text-uppercase text-muted mb-2">Desglose de Bultos & Tags de Identificación</h6>
                            <?php if (!empty($piezas)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>Tag / Código</th>
                                                <th>Tipo</th>
                                                <th class="text-end">Peso</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($piezas as $pz): ?>
                                                <tr>
                                                    <td class="font-monospace fw-bold text-primary small">
                                                        🏷️ <?= htmlspecialchars($pz['codigo'] ?? $pz['tag'] ?? 'EQP-S/N') ?>
                                                    </td>
                                                    <td class="small"><?= htmlspecialchars($pz['tipo'] ?? 'Maleta') ?></td>
                                                    <td class="text-end fw-bold small"><?= number_format(floatval($pz['peso'] ?? 0), 1) ?> Kg</td>
                                                    <td class="small text-muted"><?= htmlspecialchars($pz['descripcion'] ?? '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary p-2 small mb-0">
                                    Equipaje estándar registrado: 1 maleta de <?= number_format($pb, 1) ?> Kg (Tag provisional: EQP-<?= $eqp['ID_PASAJE'] ?>-1).
                                </div>
                            <?php endif; ?>

                            <?php if (($eqp['SP_ESTADO'] ?? '') === 'ACTIVO'): ?>
                                <div class="alert alert-warning p-2 small mt-3 mb-0">
                                    <strong>Sobrepeso Activo:</strong> Recibo #<?= $eqp['SP_ID_FACTURA'] ?> por $<?= number_format($eqp['SP_TOTAL'], 2) ?> USD (<?= number_format($eqp['SP_EXCESO_KG'], 1) ?> Kg exceso).
                                </div>
                            <?php elseif (($eqp['SP_ESTADO'] ?? '') === 'ANULADO'): ?>
                                <div class="alert alert-danger p-2 small mt-3 mb-0">
                                    <strong>Sobrepeso Anulado:</strong> Revertido en caja. Motivo: <?= htmlspecialchars($eqp['SP_MOTIVO_ANULACION'] ?? 'S/M') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer p-2">
                            <a href="/suramerica2030/pasajes/imprimirEquipaje?id=<?= $eqp['ID_PASAJE'] ?>" target="_blank" class="btn btn-sm btn-dark fw-bold">
                                🏷️ Imprimir Etiquetas
                            </a>
                            <?php if (!empty($eqp['SP_ID_FACTURA'])): ?>
                                <a href="/suramerica2030/ventas/ticket/<?= $eqp['SP_ID_FACTURA'] ?>" target="_blank" class="btn btn-sm btn-primary fw-bold">
                                    🧾 Ver Recibo
                                </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL ANULAR SOBREPESO CENTRAL -->
            <?php if (($eqp['SP_ESTADO'] ?? '') === 'ACTIVO' && $puedeAnularDirecto): ?>
            <div class="modal fade text-start" id="modalAnularSpCentral<?= $eqp['ID_PASAJE'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content border-danger">
                        <div class="modal-header bg-danger text-white py-2">
                            <h6 class="modal-title fw-bold">❌ Confirmar Anulación de Sobrepeso</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/suramerica2030/pasajes/anular-sobrepeso" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_pasaje" value="<?= $eqp['ID_PASAJE'] ?>">
                                <input type="hidden" name="id_sobrepeso" value="<?= $eqp['ID_SOBREPESO'] ?>">
                                <input type="hidden" name="redirect_tab" value="equipaje">

                                <div class="alert alert-danger p-2 small mb-3">
                                    <strong>¡Atención!</strong> Se anulará el cobro de sobrepeso de <strong>$<?= number_format($eqp['SP_TOTAL'], 2) ?> USD</strong> (Recibo #<?= $eqp['SP_ID_FACTURA'] ?>) de <strong><?= htmlspecialchars($eqp['CLI_NOMBRE'].' '.$eqp['CLI_APELLIDO']) ?></strong>.
                                    <ul class="mb-0 ps-3 mt-1">
                                        <li>Se generará automáticamente un movimiento de <strong>EGRESO</strong> en Caja para devolver el efectivo.</li>
                                        <li>La factura quedará registrada como <strong>ANULADA</strong>.</li>
                                        <li>Se eliminará de la liquidación de socios en Finanzas.</li>
                                    </ul>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-dark">Motivo obligatorio de anulación <span class="text-danger">*</span></label>
                                    <textarea name="motivo" class="form-control" rows="3" placeholder="Ej: Error en báscula, pasajero redujo maletas, devolución..." required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer p-2">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger btn-sm fw-bold">⚠️ Proceder y Revertir en Caja</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php endforeach; ?>
    <?php endif; ?>

<?php else: ?>

<div class="card shadow-sm border-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Pasajero</th>
                <th>Ruta y Viaje</th>
                <th>Estado Financiero</th>
                <th>Equipaje</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($pasajes)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No hay pasajes registrados en esta vista.</td></tr>
            <?php else: ?>
                <?php foreach($pasajes as $p): ?>
                    <tr class="<?= $p['ESTADO'] == 'DEVUELTO' ? 'table-secondary opacity-75' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></strong><br>
                            <small class="text-muted">CI: <?= htmlspecialchars($p['ID_CLIENTE']) ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?></strong><br>
                            <?php if($p['ESTADO'] == 'DEVUELTO'): ?>
                                <span class="badge bg-secondary text-decoration-line-through">Cancelado</span>
                            <?php elseif($p['FECHA_VIAJE']): ?>
                                <?php 
                                $diasSemanaNom = [0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb'];
                                $w = (int)date('w', strtotime($p['FECHA_VIAJE']));
                                $diaNom = $diasSemanaNom[$w] ?? '';
                                ?>
                                <span class="badge bg-light text-dark border">
                                    📅 <strong class="text-primary"><?= $diaNom ?></strong>, <?= date('d/m/Y', strtotime($p['FECHA_VIAJE'])) ?> | Asiento: <strong><?= $p['PAX_ASIENTO'] ?></strong>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">⏳ FECHA ABIERTA</span>
                            <?php endif; ?>
                            <?php if(!empty($p['FA_FECHA'])): ?>
                                <br><small class="text-muted" style="font-size:11px;">🧾 Fac #<?= $p['ID_FACTURA'] ?> • <?= date('d/m/Y', strtotime($p['FA_FECHA'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($p['ESTADO'] == 'DEVUELTO'): ?>
                                <span class="badge bg-dark">❌ DEVUELTO</span><br>
                                <small class="text-muted" style="font-size:10px;"><?= htmlspecialchars($p['OBSERVACION']) ?></small>
                            <?php elseif($p['ESTADO'] == 'PAGADO'): ?>
                                <span class="badge bg-success">✅ PAGADO</span>
                                <a href="#" class="text-decoration-none text-muted ms-1" title="Actualizar Estado Financiero" data-bs-toggle="modal" data-bs-target="#modalFinanciero<?= $p['ID_PASAJE'] ?>"><span style="font-size:12px; cursor:pointer;">✏️</span></a><br>
                                <small class="text-muted">Total: $<?= number_format($p['PRECIO_TOTAL'], 2) ?></small>
                            <?php else: ?>
                                <span class="badge bg-danger">⚠️ RESERVA</span>
                                <a href="#" class="text-decoration-none text-muted ms-1" title="Actualizar Estado Financiero" data-bs-toggle="modal" data-bs-target="#modalFinanciero<?= $p['ID_PASAJE'] ?>"><span style="font-size:12px; cursor:pointer;">✏️</span></a><br>
                                <small class="fw-bold text-danger">Saldo: $<?= number_format($p['SALDO'], 2) ?></small>
                            <?php endif; ?>

                            <?php if(($p['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE'): ?>
                                <br><span class="badge bg-warning text-dark border mt-1 shadow-sm" title="Solicitado por <?= htmlspecialchars($p['SOLICITUD_USUARIO_NOMBRE'] ?? 'Cajero') ?>: <?= htmlspecialchars($p['SOLICITUD_MOTIVO'] ?? '') ?>">⏳ Anulación Solicitada</span>
                            <?php elseif(($p['SOLICITUD_ESTADO'] ?? '') === 'RECHAZADA' && $p['ESTADO'] != 'DEVUELTO'): ?>
                                <br><span class="badge bg-light text-danger border mt-1" title="Rechazado: <?= htmlspecialchars($p['SOLICITUD_MOTIVO_RESPUESTA'] ?? '') ?>">🚫 Anulación Denegada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($p['ESTADO'] != 'DEVUELTO'): ?>
                                <?php $totalPeso = ($p['PESO_MANO'] ?? 0) + ($p['PESO_BODEGA'] ?? 0); ?>
                                <?php if($totalPeso > 0): ?>
                                    <span class="badge <?= $totalPeso > ($p['LIMITE_EQUIPAJE_KG'] ?? 35) ? 'bg-danger' : 'bg-primary' ?>"><?= number_format($totalPeso, 1) ?> Kg</span>
                                    <?php 
                                    $numPz = 0;
                                    if (!empty($p['EQUIPAJE_DETALLE'])) {
                                        $d = json_decode($p['EQUIPAJE_DETALLE'], true);
                                        if (is_array($d)) $numPz = count($d);
                                    }
                                    if ($numPz > 0): ?>
                                        <br><small class="text-muted fw-bold" style="font-size:10px;"><?= $numPz ?> pieza(s)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sin Pesar</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($p['ESTADO'] != 'DEVUELTO'): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">⚙️ Opciones</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item fw-bold text-dark border-bottom mb-1" target="_blank" href="/suramerica2030/ventas/ticket/<?= $p['ID_FACTURA'] ?>">🖨️ Re-imprimir Ticket</a></li>
                                        
                                        <?php if(!$p['FECHA_VIAJE']): ?>
                                            <li><a class="dropdown-item fw-bold text-info" href="#" data-bs-toggle="modal" data-bs-target="#modalAsignar<?= $p['ID_PASAJE'] ?>">📅 Asignar Fecha/Bus</a></li>
                                        <?php else: ?>
                                            <li><a class="dropdown-item" href="#" onclick="abrirMapaParaReasignar(<?= $p['ID_PASAJE'] ?>, '<?= $p['FECHA_VIAJE'] ?>', <?= $p['ID_RUTA'] ?? 0 ?>, <?= $p['ID_BUS'] ?? 'null' ?>, <?= $p['PAX_ASIENTO'] ?? 'null' ?>, <?= $p['BUS_ASIENTOS'] ?? 42 ?>)">🔄 Reasignar Asiento</a></li>
                                        <?php endif; ?>
                                        <li><a class="dropdown-item <?= $p['ESTADO'] == 'PAGADO' ? 'disabled' : 'text-success' ?>" href="#" data-bs-toggle="modal" data-bs-target="#modalAbono<?= $p['ID_PASAJE'] ?>">💵 Registrar Abono</a></li>
                                        <li><a class="dropdown-item fw-bold" style="color:#0b2b56;" href="#" data-bs-toggle="modal" data-bs-target="#modalEquipaje<?= $p['ID_PASAJE'] ?>">⚖️ Control Equipaje</a></li>
                                        <li><a class="dropdown-item fw-bold text-primary" href="#" data-bs-toggle="modal" data-bs-target="#modalFinanciero<?= $p['ID_PASAJE'] ?>">💳 Actualizar Financiero</a></li>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        <?php if(($p['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE'): ?>
                                            <?php if($puedeAnularDirecto): ?>
                                                <li><a class="dropdown-item text-primary fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalResolverSolicitud<?= $p['ID_PASAJE'] ?>">🔔 Resolver Solicitud Anulación</a></li>
                                            <?php else: ?>
                                                <li><span class="dropdown-item text-warning fw-bold disabled">⏳ Anulación en Revisión</span></li>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if($puedeAnularDirecto): ?>
                                                <li><a class="dropdown-item text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalDevolucion<?= $p['ID_PASAJE'] ?>">❌ Devolución Directa / Cancelar</a></li>
                                            <?php else: ?>
                                                <li><a class="dropdown-item text-warning fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalSolicitarDevolucion<?= $p['ID_PASAJE'] ?>">📩 Solicitar Anulación</a></li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <?php $pagado = $p['PRECIO_TOTAL'] - $p['SALDO']; ?>

                                <!-- 1. MODAL DE DEVOLUCIÓN DIRECTA (ADMIN O CON PERMISO P_ANULAR) -->
                                <?php if($puedeAnularDirecto): ?>
                                <div class="modal fade text-start" id="modalDevolucion<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h6 class="modal-title">Cancelar y Devolver</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/devolver" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <p class="mb-2 text-muted" style="font-size:12px;">El cliente ha pagado un total de <strong>$<?= number_format($pagado, 2) ?></strong> hasta ahora.</p>
                                                    <div class="mb-2"><label>Monto a Reembolsar ($)</label><input type="number" step="0.01" name="monto_devolver" class="form-control" value="<?= $pagado ?>" max="<?= $pagado ?>" required></div>
                                                    <div class="mb-2"><label>Motivo de cancelación</label><input type="text" name="motivo" class="form-control" required placeholder="Ej: Cliente no llegó, Cambio de planes..."></div>
                                                    <div class="alert alert-warning p-2" style="font-size: 11px;">Al confirmar, el asiento quedará libre y el monto se descontará de la caja.</div>
                                                </div>
                                                <div class="modal-footer p-1"><button type="submit" class="btn btn-danger w-100 fw-bold">Confirmar Devolución</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- 2. MODAL DE SOLICITAR ANULACIÓN (PARA CAJEROS/USUARIOS SIN PERMISO DIRECTO) -->
                                <div class="modal fade text-start" id="modalSolicitarDevolucion<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-warning text-dark py-2">
                                                <h6 class="modal-title fw-bold">📩 Solicitar Anulación de Pasaje #<?= $p['ID_PASAJE'] ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/solicitarAnulacion" method="POST">
                                                <div class="modal-body p-3">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <input type="hidden" name="id_factura" value="<?= $p['ID_FACTURA'] ?>">
                                                    
                                                    <div class="bg-light p-2 rounded border mb-3 small">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <strong>Pasajero:</strong> 
                                                            <span><?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <strong>Ruta:</strong> 
                                                            <span><?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <strong>Viaje / Asiento:</strong> 
                                                            <span><?= !empty($p['FECHA_VIAJE']) ? date('d/m/Y', strtotime($p['FECHA_VIAJE'])) : 'Abierto' ?> • Asiento: <?= $p['PAX_ASIENTO'] ?? 'Sin asignar' ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between border-top pt-1 text-primary fw-bold">
                                                            <span>Monto Pagado:</span> 
                                                            <span>$<?= number_format($pagado, 2) ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Monto sugerido a reembolsar ($)</label>
                                                        <input type="number" step="0.01" name="monto_devolver" class="form-control fw-bold text-success fs-5" value="<?= $pagado ?>" max="<?= $pagado ?>" min="0" required>
                                                        <small class="text-muted" style="font-size: 11px;">Máximo a devolver según abonos recibidos: $<?= number_format($pagado, 2) ?></small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Motivo detallado de la anulación <span class="text-danger">*</span></label>
                                                        <textarea name="motivo" class="form-control" rows="3" required placeholder="Explique claramente el motivo por el cual se solicita anular este pasaje (ej: Emergencia médica del cliente, cambio de planes, etc.)..."></textarea>
                                                    </div>

                                                    <div class="alert alert-info py-2 px-3 mb-0 small" style="font-size: 11px;">
                                                        ℹ️ <strong>Importante:</strong> Esta solicitud será enviada al Administrador para su aprobación. El boleto no se anulará ni se descontará de caja hasta que sea aprobada.
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2 bg-light">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-warning btn-sm fw-bold px-3">📩 Enviar Solicitud a Administración</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. MODAL DE RESOLVER SOLICITUD DIRECTAMENTE (ADMINISTRADOR) -->
                                <?php if(($p['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE' && $puedeAnularDirecto): ?>
                                <div class="modal fade text-start" id="modalResolverSolicitud<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-primary text-white py-2">
                                                <h6 class="modal-title fw-bold">🔔 Resolver Solicitud de Anulación #<?= $p['ID_SOLICITUD'] ?></h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-3">
                                                <div class="bg-light p-2 rounded border mb-3 small">
                                                    <div><strong>Pasajero:</strong> <?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?> (CI: <?= htmlspecialchars($p['ID_CLIENTE']) ?>)</div>
                                                    <div><strong>Ruta:</strong> <?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?> • Asiento: <?= $p['PAX_ASIENTO'] ?? 'Sin asignar' ?></div>
                                                    <div class="text-primary mt-1"><strong>Solicitado por:</strong> <?= htmlspecialchars($p['SOLICITUD_USUARIO_NOMBRE'] ?? 'Cajero') ?> el <?= !empty($p['SOLICITUD_FECHA']) ? date('d/m/Y H:i', strtotime($p['SOLICITUD_FECHA'])) : '' ?></div>
                                                    <div class="text-danger fw-bold fs-6 mt-1">Monto a reembolsar: $<?= number_format($p['SOLICITUD_MONTO'], 2) ?></div>
                                                    <div class="mt-2 p-2 bg-white rounded border">
                                                        <span class="text-muted fw-bold">Motivo expuesto por el cajero:</span><br>
                                                        <?= nl2br(htmlspecialchars($p['SOLICITUD_MOTIVO'])) ?>
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-column gap-2">
                                                    <!-- OPCIÓN A: APROBAR -->
                                                    <form action="/suramerica2030/pasajes/aprobarAnulacion" method="POST">
                                                        <input type="hidden" name="id_solicitud" value="<?= $p['ID_SOLICITUD'] ?>">
                                                        <input type="hidden" name="redirect_tab" value="<?= htmlspecialchars($filtros['tab'] ?? 'activos') ?>">
                                                        <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm" onclick="return confirm('¿Confirmas que deseas APROBAR esta anulación? Se liberará el asiento y se registrará un egreso de $<?= number_format($p['SOLICITUD_MONTO'], 2) ?> en caja.');">
                                                            ✔️ Aprobar Anulación y Reembolso ($<?= number_format($p['SOLICITUD_MONTO'], 2) ?>)
                                                        </button>
                                                    </form>

                                                    <!-- OPCIÓN B: DENEGAR -->
                                                    <div class="border rounded p-2 bg-light mt-1">
                                                        <form action="/suramerica2030/pasajes/denegarAnulacion" method="POST">
                                                            <input type="hidden" name="id_solicitud" value="<?= $p['ID_SOLICITUD'] ?>">
                                                            <input type="hidden" name="redirect_tab" value="<?= htmlspecialchars($filtros['tab'] ?? 'activos') ?>">
                                                            <label class="form-label small fw-bold text-danger mb-1">Si deniega, ingrese el motivo para el cajero:</label>
                                                            <input type="text" name="motivo_rechazo" class="form-control form-control-sm mb-2" required placeholder="Ej: No procede devolución según políticas, tiempo vencido...">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold" onclick="return confirm('¿Confirmas que deseas DENEGAR esta solicitud?');">
                                                                ❌ Denegar Solicitud
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- 2. MODAL DE ASIGNAR FECHA ABIERTA -->
                                <?php if(!$p['FECHA_VIAJE']): ?>
                                <div class="modal fade text-start" id="modalAsignar<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-dark"><h6 class="modal-title">Asignar Fecha de Viaje</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <form action="/suramerica2030/pasajes/asignar" method="POST">
                                                <div class="modal-body row">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <div class="col-6 mb-2"><label>Fecha Exacta</label><input type="date" name="fecha_viaje" id="fecha_asig_<?= $p['ID_PASAJE'] ?>" class="form-control" required onchange="limpiarAsiento(<?= $p['ID_PASAJE'] ?>)"></div>
                                                    <div class="col-6 mb-2">
                                                        <label>Bus Asignado</label>
                                                        <select name="bus_id" id="bus_asig_<?= $p['ID_PASAJE'] ?>" class="form-select" required onchange="limpiarAsiento(<?= $p['ID_PASAJE'] ?>)">
                                                            <option value="">- Elegir -</option>
                                                            <?php foreach($buses as $b): ?><option value="<?= $b['ID_BUS'] ?>" data-asientos="<?= $b['BUS_ASIENTOS'] ?>"><?= htmlspecialchars($b['BUS_PLACA']) ?></option><?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 text-center mt-2">
                                                        <label>Asiento Elegido</label>
                                                        <div class="input-group">
                                                            <input type="text" name="asiento" id="asiento_asig_<?= $p['ID_PASAJE'] ?>" class="form-control text-center fw-bold fs-5 bg-white" readonly required placeholder="Ninguno">
                                                            <button type="button" class="btn btn-dark" onclick="abrirMapaAsientos(<?= $p['ID_PASAJE'] ?>, <?= $p['ID_RUTA'] ?? 0 ?>, 'asig')">📍 Ver Croquis</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-1"><button type="submit" class="btn btn-info w-100 fw-bold">Confirmar Viaje</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- 3. MODAL DE ABONO -->
                                <div class="modal fade text-start" id="modalAbono<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h6 class="modal-title">Registrar Abono</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/abonar" method="POST" onsubmit="return validarAbonoMixto(this, <?= $p['ID_PASAJE'] ?>)">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <input type="hidden" name="cliente_id" value="<?= $p['ID_CLIENTE'] ?>">
                                                    <p class="mb-1"><strong>Saldo Pendiente:</strong> <span class="text-danger fw-bold">$<?= number_format($p['SALDO'], 2) ?></span></p>
                                                    <label class="fw-bold small">Monto a Abonar ($)</label>
                                                    <input type="number" step="0.01" name="monto_abono" id="monto_abono_<?= $p['ID_PASAJE'] ?>" class="form-control mb-2" required max="<?= $p['SALDO'] ?>" min="1" oninput="balancearMixtoAbono(<?= $p['ID_PASAJE'] ?>, 'total')">
                                                    <label class="fw-bold small">Método de Pago</label>
                                                    <select name="metodo_pago" id="metodo_pago_abono_<?= $p['ID_PASAJE'] ?>" class="form-select mb-2" required onchange="togglePagoMixtoAbono(<?= $p['ID_PASAJE'] ?>)">
                                                        <option value="EFECTIVO">Efectivo</option>
                                                        <option value="TRANSFERENCIA">Transferencia</option>
                                                        <option value="MIXTO">Mixto (Efectivo + Transferencia)</option>
                                                    </select>
                                                    <div class="p-2 mb-2 rounded bg-light border border-info d-none" id="div_mixto_abono_<?= $p['ID_PASAJE'] ?>">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="small fw-bold text-primary" style="font-size:11px;">💳 Desglose Mixto</span>
                                                            <span class="badge bg-success" id="badge_mixto_abono_<?= $p['ID_PASAJE'] ?>" style="font-size:10px;">✔ Cuadrado</span>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <label class="form-label small fw-bold mb-0" style="font-size:11px;">Efectivo ($)</label>
                                                                <input type="number" step="0.01" min="0" name="monto_efectivo" id="monto_efectivo_abono_<?= $p['ID_PASAJE'] ?>" class="form-control form-control-sm fw-bold text-success" placeholder="0.00" oninput="balancearMixtoAbono(<?= $p['ID_PASAJE'] ?>, 'efectivo')">
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label small fw-bold mb-0" style="font-size:11px;">Transf. ($)</label>
                                                                <input type="number" step="0.01" min="0" name="monto_transferencia" id="monto_transferencia_abono_<?= $p['ID_PASAJE'] ?>" class="form-control form-control-sm fw-bold text-primary" placeholder="0.00" oninput="balancearMixtoAbono(<?= $p['ID_PASAJE'] ?>, 'transferencia')">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-1"><button type="submit" class="btn btn-success w-100 fw-bold">Cobrar Abono</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. MODAL DE CONTROL DE EQUIPAJE -->
                                <div class="modal fade text-start" id="modalEquipaje<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning text-dark py-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fs-5">⚖️</span>
                                                    <h6 class="modal-title fw-bold mb-0">Control de Equipaje - Pasajero: <?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></h6>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/equipaje" method="POST" onsubmit="return validarEquipajeMixto(this, <?= $p['ID_PASAJE'] ?>)">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <input type="hidden" name="cliente_id" value="<?= $p['ID_CLIENTE'] ?>">
                                                    
                                                    <!-- Campos ocultos sincronizados de peso para compatibilidad global -->
                                                    <input type="hidden" name="peso_mano" id="pm_<?= $p['ID_PASAJE'] ?>" value="10.0">
                                                    <input type="hidden" name="peso_bodega" id="pb_<?= $p['ID_PASAJE'] ?>" value="<?= $p['PESO_BODEGA'] ?? 0 ?>">
                                                    
                                                    <?php 
                                                    $piezasRegistradas = [];
                                                    if (!empty($p['EQUIPAJE_DETALLE'])) {
                                                        $dec = json_decode($p['EQUIPAJE_DETALLE'], true);
                                                        if (is_array($dec)) {
                                                            foreach ($dec as $item) {
                                                                if (($item['ubicacion'] ?? 'BODEGA') === 'BODEGA' || empty($item['ubicacion'])) {
                                                                    $piezasRegistradas[] = $item;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    if (empty($piezasRegistradas)) {
                                                        $pb = floatval($p['PESO_BODEGA'] ?? 0);
                                                        if ($pb > 0) {
                                                            $piezasRegistradas[] = ['tipo' => 'Maleta', 'peso' => $pb, 'descripcion' => ''];
                                                        }
                                                    }
                                                    if (empty($piezasRegistradas)) {
                                                        $piezasRegistradas[] = ['tipo' => 'Maleta', 'peso' => '', 'descripcion' => ''];
                                                    }
                                                    ?>

                                                    <?php $tieneSpActivo = !empty($p['ID_SOBREPESO_ACTIVO']); ?>

                                                    <?php if ($tieneSpActivo): ?>
                                                        <div class="alert alert-warning border-warning p-3 mb-3 shadow-sm rounded">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="fs-4">⚖️</span>
                                                                    <div>
                                                                        <strong class="text-dark d-block">Cobro de Sobrepeso Activo Registrado</strong>
                                                                        <span class="small text-muted">
                                                                            Recibo #<?= htmlspecialchars($p['SOBREPESO_FACTURA_ACTIVA'] ?? '') ?> &bull; 
                                                                            <?= number_format($p['SOBREPESO_KG_ACTIVO'] ?? 0, 1) ?> Kg exceso &bull; 
                                                                            <strong class="text-success">$<?= number_format($p['SOBREPESO_TOTAL_ACTIVO'] ?? 0, 2) ?> USD</strong>
                                                                            <?php if(!empty($p['SOBREPESO_OPERADOR_ACTIVO'])): ?>
                                                                                &bull; Op: <?= htmlspecialchars($p['SOBREPESO_OPERADOR_ACTIVO']) ?>
                                                                            <?php endif; ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <span class="badge bg-success px-2 py-1">ACTIVO</span>
                                                            </div>
                                                            <p class="small text-dark mb-2">
                                                                Este boleto ya cuenta con un cobro de sobrepeso activo. <strong>Para evitar duplicidad contable no se puede generar otro sobrepeso adicional</strong>. Si necesita corregir los valores o el pesaje, primero anule el cobro actual.
                                                            </p>
                                                            <div class="d-flex flex-wrap gap-2 pt-2 border-top border-warning">
                                                                <?php if (!empty($p['SOBREPESO_FACTURA_ACTIVA'])): ?>
                                                                    <a href="/suramerica2030/ventas/ticket/<?= $p['SOBREPESO_FACTURA_ACTIVA'] ?>" target="_blank" class="btn btn-sm btn-primary fw-bold">
                                                                        🧾 Ver Factura / Ticket #<?= $p['SOBREPESO_FACTURA_ACTIVA'] ?>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <a href="/suramerica2030/pasajes/imprimirEquipaje?id=<?= $p['ID_PASAJE'] ?>" target="_blank" class="btn btn-sm btn-dark fw-bold">
                                                                    🏷️ Reimprimir Etiquetas
                                                                </a>
                                                                <?php if ($puedeAnularDirecto): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold ms-auto" data-bs-toggle="modal" data-bs-target="#modalAnularSobrepeso<?= $p['ID_PASAJE'] ?>">
                                                                        ❌ Anular Sobrepeso
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php elseif($p['PESO_BODEGA'] > 0 || !empty($p['EQUIPAJE_DETALLE'])): ?>
                                                        <div class="alert alert-info p-2 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-info">
                                                            <div>
                                                                <strong class="text-dark">🖨️ Acciones Rápidas:</strong>
                                                                <span class="small text-muted ms-1"><?= count($piezasRegistradas) ?> pieza(s) registrada(s)</span>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <a href="/suramerica2030/pasajes/imprimirEquipaje?id=<?= $p['ID_PASAJE'] ?>" target="_blank" class="btn btn-sm btn-dark fw-bold">
                                                                    🧳 Imprimir Todas las Etiquetas (<?= count($piezasRegistradas) ?>)
                                                                </a>
                                                                <?php if(!empty($p['ID_FACTURA_SOBREPESO'])): ?>
                                                                    <a href="/suramerica2030/ventas/ticket/<?= $p['ID_FACTURA_SOBREPESO'] ?>" target="_blank" class="btn btn-sm btn-primary fw-bold">
                                                                        🧾 Recibo de Sobrepeso
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php
                                                    $tieneTramos = !empty($p['TRAMOS']);
                                                    $idTramoSeleccionado = intval($p['ID_RUTA_TRAMO_SOBREPESO'] ?? 0);
                                                    $primerTramoAdmite = null;

                                                    if ($tieneTramos) {
                                                        foreach ($p['TRAMOS'] as $tr) {
                                                            if ($tr['ADMITE_SOBREPESO'] == 1 && $primerTramoAdmite === null) {
                                                                $primerTramoAdmite = intval($tr['ID_RUTA_TRAMO']);
                                                            }
                                                        }
                                                        if ($idTramoSeleccionado <= 0 && $primerTramoAdmite !== null) {
                                                            $idTramoSeleccionado = $primerTramoAdmite;
                                                        }
                                                    }

                                                    $operadorDirectoRuta = (!empty($p['MODALIDAD']) && $p['MODALIDAD'] !== 'ESTANDAR' && $p['MODALIDAD'] !== 'CONEXION_TRAMOS') ? $p['MODALIDAD'] : 'Propio Suramérica';
                                                    $admiteDirectoRuta = isset($p['ADMITE_SOBREPESO']) ? intval($p['ADMITE_SOBREPESO']) : 1;
                                                    $limiteTramoActivo = floatval($p['LIMITE_EQUIPAJE_KG'] ?? 35.0);
                                                    $tarifaTramoActivo = floatval($p['PRECIO_SOBREPESO_KG'] ?? 1.0);
                                                    $operadorTramoActivo = $operadorDirectoRuta;
                                                    $admiteTramoActivo = $admiteDirectoRuta;

                                                    if ($tieneTramos && $idTramoSeleccionado > 0) {
                                                        foreach ($p['TRAMOS'] as $tr) {
                                                            if (intval($tr['ID_RUTA_TRAMO']) === $idTramoSeleccionado) {
                                                                $limiteTramoActivo = floatval($tr['LIMITE_EQUIPAJE_KG'] ?? 35.0);
                                                                $tarifaTramoActivo = floatval($tr['PRECIO_SOBREPESO_KG'] ?? 1.0);
                                                                $operadorTramoActivo = !empty($tr['OPERADOR']) ? $tr['OPERADOR'] : 'General';
                                                                $admiteTramoActivo = intval($tr['ADMITE_SOBREPESO'] ?? 0);
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    ?>

                                                    <input type="hidden" name="precio_sobrepeso_kg" id="tarifa_input_eq_<?= $p['ID_PASAJE'] ?>" value="<?= number_format($tarifaTramoActivo, 2, '.', '') ?>">
                                                    <input type="hidden" id="limite_input_eq_<?= $p['ID_PASAJE'] ?>" value="<?= number_format($limiteTramoActivo, 1, '.', '') ?>">

                                                    <div class="p-2 mb-3 bg-white rounded border shadow-sm">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="small text-muted fw-bold">
                                                                📍 Itinerario General: <strong class="text-dark"><?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?></strong>
                                                            </span>
                                                            <?php if (!empty($p['MODALIDAD']) && $p['MODALIDAD'] === 'PANAMERICANA'): ?>
                                                                <span class="badge bg-warning text-dark border border-warning" style="font-size:10px;">⭐ Aliado Panamericana</span>
                                                            <?php elseif (!empty($p['MODALIDAD']) && $p['MODALIDAD'] !== 'ESTANDAR' && $p['MODALIDAD'] !== 'CONEXION_TRAMOS'): ?>
                                                                <span class="badge bg-light text-dark border" style="font-size:10px;"><i class="bi bi-building"></i> <?= htmlspecialchars($p['MODALIDAD']) ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-success" style="font-size:10px;">🚌 Operación Propia</span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if ($tieneTramos): ?>
                                                            <div class="mt-2">
                                                                <label class="form-label small fw-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                                                                    <span>🚍 Tramo al que aplica el Sobrepeso:</span>
                                                                    <span class="text-muted" style="font-size: 10px;">* Elija el tramo donde se transporta y cobra el exceso</span>
                                                                </label>
                                                                <select name="id_ruta_tramo" id="sel_tramo_eq_<?= $p['ID_PASAJE'] ?>" class="form-select form-select-sm fw-bold border-primary" onchange="alCambiarTramoEquipaje(<?= $p['ID_PASAJE'] ?>)">
                                                                    <?php foreach ($p['TRAMOS'] as $tr): 
                                                                        $esSelect = (intval($tr['ID_RUTA_TRAMO']) === $idTramoSeleccionado);
                                                                    ?>
                                                                        <option value="<?= $tr['ID_RUTA_TRAMO'] ?>"
                                                                                data-operador="<?= htmlspecialchars($tr['OPERADOR']) ?>"
                                                                                data-origen="<?= htmlspecialchars($tr['ORIGEN_TRAMO']) ?>"
                                                                                data-destino="<?= htmlspecialchars($tr['DESTINO_TRAMO']) ?>"
                                                                                data-admite="<?= $tr['ADMITE_SOBREPESO'] ?>"
                                                                                data-limite="<?= floatval($tr['LIMITE_EQUIPAJE_KG']) ?>"
                                                                                data-tarifa="<?= floatval($tr['PRECIO_SOBREPESO_KG']) ?>"
                                                                                <?= $esSelect ? 'selected' : '' ?>>
                                                                            Tramo <?= $tr['ORDEN'] ?>: <?= htmlspecialchars($tr['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($tr['DESTINO_TRAMO']) ?> (Operador: <?= htmlspecialchars($tr['OPERADOR']) ?>) <?= ($tr['ADMITE_SOBREPESO'] == 1) ? '✔ [Sobrepeso Autorizado]' : '⚠️ [Sin cobro en oficina]' ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                    <option value="0"
                                                                            data-operador="<?= htmlspecialchars($operadorDirectoRuta) ?>"
                                                                            data-origen="<?= htmlspecialchars($p['RUT_ORIGEN']) ?>"
                                                                            data-destino="<?= htmlspecialchars($p['RUT_DESTINO']) ?>"
                                                                            data-admite="<?= $admiteDirectoRuta ?>"
                                                                            data-limite="<?= floatval($p['LIMITE_EQUIPAJE_KG'] ?? 35) ?>"
                                                                            data-tarifa="<?= floatval($p['PRECIO_SOBREPESO_KG'] ?? 1) ?>"
                                                                            <?= ($idTramoSeleccionado === 0) ? 'selected' : '' ?>>
                                                                        🌐 Toda la Ruta: <?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?> (<?= htmlspecialchars($operadorDirectoRuta) ?>) <?= ($admiteDirectoRuta == 1) ? '✔ [Sobrepeso Autorizado]' : '⚠️ [Sin cobro en oficina]' ?>
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        <?php else: ?>
                                                            <input type="hidden" name="id_ruta_tramo" id="sel_tramo_eq_<?= $p['ID_PASAJE'] ?>" value="0">
                                                        <?php endif; ?>

                                                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 p-1 px-2 bg-light rounded border small">
                                                            <span class="text-secondary">
                                                                🏢 Operador: <strong class="text-dark" id="lbl_operador_eq_<?= $p['ID_PASAJE'] ?>"><?= htmlspecialchars($operadorTramoActivo) ?></strong>
                                                            </span>
                                                            <span>
                                                                Límite: <strong class="text-primary" id="lbl_limite_eq_<?= $p['ID_PASAJE'] ?>"><?= number_format($limiteTramoActivo, 1) ?> Kg</strong>
                                                                &nbsp;|&nbsp; Tarifa: <strong class="text-danger" id="lbl_tarifa_eq_<?= $p['ID_PASAJE'] ?>">$<?= number_format($tarifaTramoActivo, 2) ?>/Kg</strong>
                                                            </span>
                                                        </div>

                                                        <div id="alerta_no_admite_eq_<?= $p['ID_PASAJE'] ?>" class="alert alert-warning py-1 px-2 mb-0 mt-2 small <?= ($admiteTramoActivo == 1) ? 'd-none' : '' ?>">
                                                            ⚠️ <strong>Atención:</strong> Por condiciones logísticas y aduaneras, este operador / tramo no admite cobro de sobrepeso desde la oficina en Ecuador.
                                                        </div>

                                                        <!-- CÓDIGO BOLETO EMPRESA ALIADA / PANAMERICANA -->
                                                        <div class="mt-2 p-2 border rounded bg-light">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <label class="form-label small fw-bold text-dark mb-0">🎫 Código Boleto Empresa Aliada / Socio:</label>
                                                                <span class="badge bg-warning text-dark" style="font-size: 10px;">Ticket Externo</span>
                                                            </div>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-white fw-bold font-monospace text-muted">N° Boleto Socio:</span>
                                                                <input type="text" name="codigo_socio" class="form-control font-monospace fw-bold text-uppercase" 
                                                                       placeholder="Ej: PANA-000000" value="<?= htmlspecialchars($p['CODIGO_SOCIO'] ?? '') ?>">
                                                            </div>
                                                            <div class="text-muted" style="font-size: 10px; margin-top: 3px;">
                                                                * Ingrese el código generado por el sistema de Panamericana / Empresa Aliada para imprimirlo en las etiquetas.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- CONTENEDOR DE PIEZAS DE EQUIPAJE (Estilo Encomiendas) -->
                                                    <div class="mb-2">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <label class="form-label small fw-bold text-secondary mb-0">
                                                                🧳 Piezas de Bodega a Pesar y Etiquetar:
                                                            </label>
                                                            <span class="badge bg-light text-secondary border" style="font-size:11px;">
                                                                🎒 Maleta de mano: <strong>10 Kg</strong> (fijo en cabina)
                                                            </span>
                                                        </div>
                                                        <div id="contenedor_piezas_eq_<?= $p['ID_PASAJE'] ?>">
                                                            <?php foreach($piezasRegistradas as $pzIdx => $pz): ?>
                                                                <div class="row g-2 pieza-equipaje-row mb-2 align-items-end" data-pasaje="<?= $p['ID_PASAJE'] ?>">
                                                                    <div class="col-md-3 col-6">
                                                                        <label class="form-label mb-0" style="font-size:11px;">Tipo</label>
                                                                        <select name="eq_tipo[]" class="form-select form-select-sm" onchange="calcularCobroEquipaje(<?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)">
                                                                            <option value="Maleta" <?= ($pz['tipo'] ?? '') === 'Maleta' ? 'selected' : '' ?>>Maleta</option>
                                                                            <option value="Bolso" <?= ($pz['tipo'] ?? '') === 'Bolso' ? 'selected' : '' ?>>Bolso / Maletín</option>
                                                                            <option value="Caja" <?= ($pz['tipo'] ?? '') === 'Caja' ? 'selected' : '' ?>>Caja</option>
                                                                            <option value="Bulto" <?= ($pz['tipo'] ?? '') === 'Bulto' ? 'selected' : '' ?>>Bulto / Saco</option>
                                                                            <option value="Mochila" <?= ($pz['tipo'] ?? '') === 'Mochila' ? 'selected' : '' ?>>Mochila</option>
                                                                            <option value="Otro" <?= ($pz['tipo'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-2 col-6">
                                                                        <label class="form-label mb-0" style="font-size:11px;">Peso (Kg)</label>
                                                                        <input type="number" step="0.1" min="0" name="eq_peso[]" class="form-control form-control-sm text-end fw-bold eq-peso-input" value="<?= (!empty($pz['peso']) || (isset($pz['peso']) && $pz['peso'] === 0)) ? floatval($pz['peso']) : '' ?>" placeholder="0.0" required oninput="calcularCobroEquipaje(<?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)" onchange="calcularCobroEquipaje(<?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)">
                                                                    </div>
                                                                    <div class="col-md-6 col-10">
                                                                        <label class="form-label mb-0" style="font-size:11px;">Descripción (opcional)</label>
                                                                        <input type="text" name="eq_descripcion[]" class="form-control form-control-sm" value="<?= htmlspecialchars($pz['descripcion'] ?? '') ?>" placeholder="Ej: Ropa, Frágil...">
                                                                    </div>
                                                                    <div class="col-md-1 col-2 text-end">
                                                                        <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="eliminarPiezaEquipaje(this, <?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)" title="Eliminar pieza">×</button>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary mb-3 fw-bold" onclick="agregarPiezaEquipaje(<?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)">
                                                            + Añadir otra pieza
                                                        </button>
                                                    </div>

                                                    <!-- RESUMEN DINÁMICO DE TOTALES -->
                                                    <div class="bg-light p-3 rounded border text-center mb-2">
                                                        <div class="d-flex flex-wrap justify-content-around align-items-center gap-2">
                                                            <div>
                                                                <span class="text-muted small d-block">Piezas Bodega:</span>
                                                                <strong class="fs-6" id="peq_cant_<?= $p['ID_PASAJE'] ?>"><?= count($piezasRegistradas) ?></strong>
                                                            </div>
                                                            <div>
                                                                <span class="text-muted small d-block">Peso Bodega:</span>
                                                                <strong class="fs-6 text-primary"><span id="peq_bodega_<?= $p['ID_PASAJE'] ?>"><?= number_format($p['PESO_BODEGA'] ?? 0, 1) ?></span> Kg</strong>
                                                            </div>
                                                            <div>
                                                                <span class="text-muted small d-block">Mano (Fijo):</span>
                                                                <strong class="fs-6 text-secondary"><span id="peq_mano_<?= $p['ID_PASAJE'] ?>">10.0</span> Kg</strong>
                                                            </div>
                                                            <div class="border-start ps-3">
                                                                <span class="text-muted small d-block">Total Global:</span>
                                                                <strong class="fs-5 text-dark"><span id="pt_<?= $p['ID_PASAJE'] ?>"><?= number_format(floatval($p['PESO_BODEGA'] ?? 0) + 10.0, 1) ?></span> Kg</strong>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-danger d-none" id="warn_<?= $p['ID_PASAJE'] ?>">
                                                                ⚠️ Exceso de <span id="pex_<?= $p['ID_PASAJE'] ?>">0</span> Kg sobre el límite permitido
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- SECCIÓN DE COBRO DE SOBREPESO (CON DESCUENTO EDITABLE) -->
                                                    <div class="row align-items-end p-2 bg-light rounded border mb-2 d-none" id="cobro_<?= $p['ID_PASAJE'] ?>">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-bold">Método de Pago</label>
                                                            <select name="metodo_pago" id="metodo_pago_eq_<?= $p['ID_PASAJE'] ?>" class="form-select" onchange="togglePagoMixtoEquipaje(<?= $p['ID_PASAJE'] ?>)">
                                                                <option value="EFECTIVO">Efectivo</option>
                                                                <option value="TRANSFERENCIA">Transferencia</option>
                                                                <option value="MIXTO">Mixto (Efectivo + Transferencia)</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <label class="form-label small fw-bold mb-0">A Cobrar ($)</label>
                                                                <small class="text-success fw-bold" id="sug_<?= $p['ID_PASAJE'] ?>" style="font-size:10px;"></small>
                                                            </div>
                                                            <input type="number" step="0.01" min="0" name="costo_extra" id="cx_<?= $p['ID_PASAJE'] ?>" class="form-control fw-bold text-danger text-end" placeholder="0.00" <?= !$puedeEditarPrecios ? 'readonly' : '' ?> oninput="balancearMixtoEquipaje(<?= $p['ID_PASAJE'] ?>, 'total')">
                                                            <small class="text-muted d-block text-end" style="font-size:10px;">
                                                                <?= $puedeEditarPrecios ? '* Editable (permite descuento)' : '🔒 Tarifa calculada fija (sin permiso de descuento)' ?>
                                                            </small>
                                                        </div>
                                                        <div class="col-12 mt-2 d-none" id="div_mixto_eq_<?= $p['ID_PASAJE'] ?>">
                                                            <div class="p-2 rounded bg-white border border-info">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="small fw-bold text-primary" style="font-size:11px;">💳 Desglose Mixto</span>
                                                                    <span class="badge bg-success" id="badge_mixto_eq_<?= $p['ID_PASAJE'] ?>" style="font-size:10px;">✔ Cuadrado</span>
                                                                </div>
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <label class="form-label small fw-bold mb-0" style="font-size:11px;">Efectivo ($)</label>
                                                                        <input type="number" step="0.01" min="0" name="monto_efectivo" id="monto_efectivo_eq_<?= $p['ID_PASAJE'] ?>" class="form-control form-control-sm fw-bold text-success" placeholder="0.00" oninput="balancearMixtoEquipaje(<?= $p['ID_PASAJE'] ?>, 'efectivo')">
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="form-label small fw-bold mb-0" style="font-size:11px;">Transf. ($)</label>
                                                                        <input type="number" step="0.01" min="0" name="monto_transferencia" id="monto_transferencia_eq_<?= $p['ID_PASAJE'] ?>" class="form-control form-control-sm fw-bold text-primary" placeholder="0.00" oninput="balancearMixtoEquipaje(<?= $p['ID_PASAJE'] ?>, 'transferencia')">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <?php if ($tieneSpActivo): ?>
                                                        <button type="button" class="btn btn-secondary btn-sm fw-bold" disabled title="Ya existe un sobrepeso activo cobrado. Anule el actual para emitir uno nuevo.">🔒 Cobro Bloqueado (Ya existe Sobrepeso Activo)</button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-warning fw-bold px-3">💾 Guardar y Procesar Equipaje</button>
                                                    <?php endif; ?>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($p['ID_SOBREPESO_ACTIVO'])): ?>
                                <div class="modal fade text-start" id="modalAnularSobrepeso<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content border-danger">
                                            <div class="modal-header bg-danger text-white py-2">
                                                <h6 class="modal-title fw-bold">❌ Confirmar Anulación de Sobrepeso</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/anular-sobrepeso" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <input type="hidden" name="id_sobrepeso" value="<?= $p['ID_SOBREPESO_ACTIVO'] ?>">
                                                    <input type="hidden" name="redirect_tab" value="<?= htmlspecialchars($filtros['tab'] ?? 'activos') ?>">

                                                    <div class="alert alert-danger p-2 small mb-3">
                                                        <strong>¡Atención!</strong> Se anulará el cobro de sobrepeso de <strong>$<?= number_format($p['SOBREPESO_TOTAL_ACTIVO'] ?? 0, 2) ?> USD</strong> (Recibo #<?= $p['SOBREPESO_FACTURA_ACTIVA'] ?>) de <strong><?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></strong>.
                                                        <ul class="mb-0 ps-3 mt-1">
                                                            <li>Se generará automáticamente un movimiento de <strong>EGRESO</strong> en Caja para devolver el dinero.</li>
                                                            <li>La factura quedará registrada como <strong>ANULADA</strong>.</li>
                                                            <li>Se excluirá de la liquidación de socios y operadores en Finanzas.</li>
                                                        </ul>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small text-dark">Motivo obligatorio de anulación <span class="text-danger">*</span></label>
                                                        <textarea name="motivo" class="form-control" rows="3" placeholder="Ej: Error en báscula, pasajero redujo maletas, devolución..." required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-danger btn-sm fw-bold">⚠️ Proceder y Revertir en Caja</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- 5. MODAL DE ACTUALIZAR ESTADO FINANCIERO -->
                                <div class="modal fade text-start" id="modalFinanciero<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h6 class="modal-title">💳 Actualizar Estado Financiero</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/actualizarfinanciero" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">

                                                    <!-- Resumen del Pasaje -->
                                                    <div class="p-2 mb-3 bg-light rounded border">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <strong><?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></strong>
                                                            <span class="badge bg-secondary">CI: <?= htmlspecialchars($p['ID_CLIENTE']) ?></span>
                                                        </div>
                                                        <div class="text-muted small mt-1">
                                                            📍 <strong>Ruta:</strong> <?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?>
                                                        </div>
                                                        <div class="d-flex gap-2 mt-2 flex-wrap" style="font-size: 11px;">
                                                            <span class="badge bg-white text-dark border">
                                                                Tarifa Actual de Ruta: <strong class="text-primary">$<?= number_format($p['RUTA_PRECIO'] ?? 0, 2) ?></strong>
                                                            </span>
                                                            <span class="badge bg-white text-dark border">
                                                                Factura #<?= $p['ID_FACTURA'] ?>: <strong class="text-success">$<?= number_format($p['FA_TOTAL'] ?? 0, 2) ?></strong>
                                                            </span>
                                                            <span class="badge bg-white text-dark border">
                                                                Pasaje Registrado: <strong class="text-danger">$<?= number_format($p['PRECIO_TOTAL'], 2) ?></strong>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Botones de Ajuste Rápido -->
                                                    <div class="mb-3">
                                                        <label class="form-label small text-muted fw-bold mb-1">⚡ Accesos rápidos para sincronizar monto:</label>
                                                        <div class="d-flex gap-2 flex-wrap">
                                                            <?php if(!empty($p['FA_TOTAL']) && floatval($p['FA_TOTAL']) > 0): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="aplicarFinanciero(<?= $p['ID_PASAJE'] ?>, <?= floatval($p['FA_TOTAL']) ?>, 0, 'PAGADO')">
                                                                    ⚡ Usar Factura ($<?= number_format($p['FA_TOTAL'], 2) ?>)
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if(!empty($p['RUTA_PRECIO']) && floatval($p['RUTA_PRECIO']) > 0): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="aplicarFinanciero(<?= $p['ID_PASAJE'] ?>, <?= floatval($p['RUTA_PRECIO']) ?>, 0, 'PAGADO')">
                                                                    ⚡ Usar Tarifa Ruta ($<?= number_format($p['RUTA_PRECIO'], 2) ?>)
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarPagado(<?= $p['ID_PASAJE'] ?>)">
                                                                ✅ Saldo $0 (Pagado)
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Campos Financieros Editables -->
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-bold">Precio Total ($)</label>
                                                            <input type="number" step="0.01" name="precio_total" id="fin_precio_<?= $p['ID_PASAJE'] ?>" class="form-control fw-bold fs-5 text-dark <?= !$puedeEditarPrecios ? 'bg-light' : '' ?>" value="<?= number_format($p['PRECIO_TOTAL'], 2, '.', '') ?>" required oninput="recalcularFinanciero(<?= $p['ID_PASAJE'] ?>)" <?= !$puedeEditarPrecios ? 'readonly' : '' ?>>
                                                            <?php if(!$puedeEditarPrecios): ?>
                                                                <small class="text-muted d-block" style="font-size:11px;">🔒 Tarifa bloqueada (sin permiso de edición).</small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-bold">Saldo Pendiente ($)</label>
                                                            <input type="number" step="0.01" name="saldo" id="fin_saldo_<?= $p['ID_PASAJE'] ?>" class="form-control fw-bold fs-5 text-danger" value="<?= number_format($p['SALDO'], 2, '.', '') ?>" required oninput="recalcularFinanciero(<?= $p['ID_PASAJE'] ?>)">
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Estado Financiero</label>
                                                        <select name="estado" id="fin_estado_<?= $p['ID_PASAJE'] ?>" class="form-select fw-bold">
                                                            <option value="PAGADO" <?= $p['ESTADO'] == 'PAGADO' ? 'selected' : '' ?>>✅ PAGADO (Totalmente cancelado)</option>
                                                            <option value="RESERVA" <?= $p['ESTADO'] == 'RESERVA' ? 'selected' : '' ?>>⚠️ RESERVA (Con Saldo Pendiente)</option>
                                                        </select>
                                                    </div>

                                                    <!-- Opciones de sincronización con Factura y Caja -->
                                                    <div class="bg-light p-2 rounded border">
                                                        <div class="form-check form-switch mb-1">
                                                            <input class="form-check-input" type="checkbox" name="sincronizar_factura" value="1" id="sync_fac_<?= $p['ID_PASAJE'] ?>" checked>
                                                            <label class="form-check-label small fw-bold text-dark" for="sync_fac_<?= $p['ID_PASAJE'] ?>">
                                                                Sincronizar Factura #<?= $p['ID_FACTURA'] ?> con este monto
                                                            </label>
                                                        </div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="sincronizar_caja" value="1" id="sync_caja_<?= $p['ID_PASAJE'] ?>" checked>
                                                            <label class="form-check-label small fw-bold text-dark" for="sync_caja_<?= $p['ID_PASAJE'] ?>">
                                                                Sincronizar Movimiento de Ingreso en Caja
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Guardar Estado Financiero</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>Anulado</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- PAGINACIÓN PASAJES -->
    <?php if(isset($totalPaginas) && $totalPaginas > 1): ?>
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center border-0 pt-3 pb-3">
            <small class="text-muted fw-bold">Mostrando página <?= $pagina ?> de <?= $totalPaginas ?> (<?= $totalRegistros ?? '' ?> registros)</small>
            <ul class="pagination pagination-sm mb-0 shadow-sm">
                <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link text-primary fw-bold" href="?pagina=<?= $pagina - 1 ?><?= !empty($queryString) ? '&' . $queryString : '' ?>">Anterior</a>
                </li>
                <?php 
                $startP = max(1, $pagina - 2);
                $endP = min($totalPaginas, $pagina + 2);
                if($endP - $startP < 4) {
                    if($startP == 1) $endP = min($totalPaginas, $startP + 4);
                    else $startP = max(1, $endP - 4);
                }
                if($startP > 1): ?>
                    <li class="page-item"><a class="page-link" href="?pagina=1<?= !empty($queryString) ? '&' . $queryString : '' ?>">1</a></li>
                    <?php if($startP > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for($i = $startP; $i <= $endP; $i++): ?>
                    <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?><?= !empty($queryString) ? '&' . $queryString : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if($endP < $totalPaginas): ?>
                    <?php if($endP < $totalPaginas - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $totalPaginas ?><?= !empty($queryString) ? '&' . $queryString : '' ?>"><?= $totalPaginas ?></a></li>
                <?php endif; ?>
                <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                    <a class="page-link text-primary fw-bold" href="?pagina=<?= $pagina + 1 ?><?= !empty($queryString) ? '&' . $queryString : '' ?>">Siguiente</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- MODAL MAESTRO: CREAR NUEVO PASAJE -->
<div class="modal fade" id="modalNuevoPasaje" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Registrar Pasaje o Reserva</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="/suramerica2030/pasajes/guardar" method="POST" id="formNuevoPasaje" onsubmit="return validarVentaMixto(this)">
                <div class="modal-body">
                    
                    <h6 class="border-bottom pb-1 text-primary">1. Viaje e Itinerario</h6>
                    <div class="row mb-2">
                        <div class="col-6"><label>Fecha de Salida Buscada</label><input type="date" name="fecha_viaje" id="fecha_viaje_nuevo" class="form-control" required onchange="filtrarRutasPorFecha()"></div>
                        <div class="col-6 d-flex align-items-end pb-2">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" id="fecha_abierta" onchange="toggleItinerario()">
                                <label class="form-check-label" for="fecha_abierta">Vender con Fecha Abierta</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3 p-3 bg-light rounded border g-2" id="caja_itinerario">
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-primary">📍</span> Ciudad Origen</label>
                            <select id="sel_origen_pasaje" class="form-select border-primary" required onchange="alCambiarOrigenPasaje()">
                                <option value="">- Seleccione Origen -</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-danger">🏁</span> Ciudad Destino</label>
                            <select id="sel_destino_pasaje" class="form-select border-primary" required onchange="alCambiarDestinoPasaje()" disabled>
                                <option value="">- Elija Origen Primero -</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-success">🚌</span> Bus Asignado</label>
                            <select name="bus_id" id="bus_id_nuevo" class="form-select border-primary" required onchange="limpiarAsientosNuevos();">
                                <option value="">- Elegir -</option>
                                <?php foreach($buses as $b): ?><option value="<?= $b['ID_BUS'] ?>" data-asientos="<?= $b['BUS_ASIENTOS'] ?>"><?= htmlspecialchars($b['BUS_PLACA']) ?></option><?php endforeach; ?>
                            </select>
                        </div>

                        <!-- ID_RUTA que se envía en el POST -->
                        <input type="hidden" name="ruta_id" id="ruta_id_nuevo" value="" required>

                        <!-- Selector auxiliar si existen múltiples horarios para el mismo trayecto -->
                        <div class="col-12 d-none" id="caja_multiple_horario">
                            <label class="fw-bold text-secondary small">Varios horarios disponibles para este trayecto:</label>
                            <select id="sel_horario_pasaje" class="form-select form-select-sm border-info" onchange="seleccionarRutaEspecifica(this.value)">
                            </select>
                        </div>

                        <!-- Tarjeta Resumen de la Ruta Seleccionada -->
                        <div class="col-12 mt-2 d-none" id="info_ruta_seleccionada">
                            <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span id="badge_tipo_viaje" class="badge bg-success fs-6">➡️ DIRECTO</span>
                                    <span id="badge_horario" class="badge bg-light text-dark border ms-1">⏰ 08:00</span>
                                    <span id="badge_tramo_nombre" class="fw-bold text-dark ms-2 small"></span>
                                </div>
                                <div>
                                    <span class="text-muted small">Tarifa:</span>
                                    <span id="badge_precio" class="fw-bold text-success fs-6 ms-1">$0.00</span>
                                    <span id="badge_equipaje" class="badge bg-warning text-dark ms-2">🧳 35 Kg máx</span>
                                </div>
                            </div>
                            <small id="alerta_tramos" class="text-danger fw-bold d-none mt-2 d-block">
                                ⚠️ Esta ruta es de conexiones / transbordo internacional. Los buses y asientos exactos por tramo se asignarán en el embarque (puedes dejarlos en blanco).
                            </small>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mt-3 text-primary">2. Registro de Pasajeros</h6>
                    <div id="contenedor_pasajeros">
                        <!-- Primer Pasajero -->
                        <div class="pasajero-row border p-3 mb-3 bg-white rounded position-relative shadow-sm">
                            <h6 class="text-secondary fw-bold">👤 Pasajero Titular 1</h6>
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label>Cédula/Pasaporte</label>
                                    <div class="input-group">
                                        <input type="text" name="cliente_id[]" id="cliente_id_0" class="form-control" required placeholder="Ej: 131415..." onchange="buscarClienteAuto(this.value, 0)">
                                        <span class="input-group-text bg-light" title="Autocompletado Activo">🔍</span>
                                    </div>
                                </div>
                                <div class="col-md-3"><label>Nombres</label><input type="text" name="cliente_nombre[]" id="cliente_nombre_0" class="form-control" required></div>
                                <div class="col-md-3"><label>Apellidos</label><input type="text" name="cliente_apellido[]" id="cliente_apellido_0" class="form-control" required></div>
                                <div class="col-md-3"><label>Teléfono</label><input type="text" name="cliente_telefono[]" id="cliente_telefono_0" class="form-control"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4"><label>Correo Electrónico</label><input type="email" name="cliente_correo[]" id="cliente_correo_0" class="form-control" placeholder="Opcional"></div>
                                <div class="col-md-4">
                                    <label>Nacionalidad</label>
                                    <input list="lista_nacionalidades" name="cliente_nacionalidad[]" id="cliente_nacionalidad_0" class="form-control text-uppercase" required>
                                    <datalist id="lista_nacionalidades"><option value="ECUATORIANA"><option value="VENEZOLANA"><option value="COLOMBIANA"></datalist>
                                </div>
                                <div class="col-md-4"><label>Fecha Nacimiento</label><input type="date" name="cliente_fechanac[]" id="cliente_fechanac_0" class="form-control"></div>
                            </div>
                            <div class="row mb-1 mt-3">
                                <div class="col-md-4">
                                    <label class="fw-bold">N° Asiento</label>
                                    <div class="input-group">
                                        <input type="text" name="asiento[]" id="asiento_nuevo_0" class="form-control text-center fw-bold bg-white border-dark" readonly required placeholder="-">
                                        <button type="button" class="btn btn-dark" onclick="abrirMapaAsientos('nuevo', null, 'nuevo', 'asiento_nuevo_0')">📍 Elegir</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 mb-3" onclick="agregarPasajero()">+ Añadir Otro Pasajero</button>

                    <h6 class="border-bottom pb-1 mt-3 text-primary">3. Facturación y Cobro</h6>
                    <div class="row mb-2 bg-light p-3 border rounded">
                        <div class="col-4">
                            <label class="fw-bold">Precio Total Global ($)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="precio_total" id="precio_total" class="form-control bg-white fw-bold" required <?= !$puedeEditarPrecios ? 'readonly' : '' ?>>
                                <?php if($puedeEditarPrecios): ?>
                                    <button type="button" class="btn btn-outline-secondary" type="button" title="Restablecer precio oficial de la ruta" onclick="ponerPrecioDefault()">🔄</button>
                                <?php endif; ?>
                            </div>
                            <?php if(!$puedeEditarPrecios): ?>
                                <small class="text-muted d-block mt-1" style="font-size:11px;">🔒 Tarifa oficial de ruta fija (sin permiso de descuento).</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-4"><label class="fw-bold">¿Cuánto abona hoy? ($)</label><input type="number" step="0.01" name="monto_abonar" id="monto_abonar" class="form-control fw-bold text-success fs-5" required oninput="balancearMixtoVenta('total')"></div>
                        <div class="col-4">
                            <label class="fw-bold">Método de Pago</label>
                            <select name="metodo_pago" id="metodo_pago_nuevo" class="form-select border-success" onchange="togglePagoMixtoVenta()">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="MIXTO">Mixto (Efectivo + Transferencia)</option>
                            </select>
                        </div>
                        <div class="col-12 mt-2 d-none" id="div_pago_mixto_nuevo">
                            <div class="p-2 rounded bg-white border border-info shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-primary">💳 Desglose de Pago Mixto</span>
                                    <span class="badge bg-success" id="badge_check_mixto_nuevo">✔ Cuadrado</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-0">Efectivo ($)</label>
                                        <input type="number" step="0.01" min="0" name="monto_efectivo" id="monto_efectivo_nuevo" class="form-control form-control-sm fw-bold text-success" placeholder="0.00" oninput="balancearMixtoVenta('efectivo')">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-0">Transferencia ($)</label>
                                        <input type="number" step="0.01" min="0" name="monto_transferencia" id="monto_transferencia_nuevo" class="form-control form-control-sm fw-bold text-primary" placeholder="0.00" oninput="balancearMixtoVenta('transferencia')">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                                    ℹ️ Modifique cualquiera de los dos valores y el otro se balanceará automáticamente.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success w-100 fs-5 fw-bold shadow-sm">Generar Factura de Venta</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCroquisBus" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h6 class="modal-title">Selección de Asiento</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center">
                <div class="d-flex justify-content-center gap-3 mb-3" style="font-size:12px;">
                    <div><span style="display:inline-block; width:15px; height:15px; background:#e8f5e9; border:1px solid #28a745;"></span> Libre</div>
                    <div><span style="display:inline-block; width:15px; height:15px; background:#f8d7da; border:1px solid #dc3545;"></span> Ocupado</div>
                </div>
                <div class="bus-map-container" id="dibujo_asientos"></div>
                <input type="hidden" id="target_input_asiento">
            </div>
            <div class="modal-footer p-1" id="footerReasignar" style="display:none;">
                <form action="/suramerica2030/pasajes/reasignar" method="POST" class="w-100">
                    <input type="hidden" name="id_pasaje" id="form_id_pasaje">
                    <input type="hidden" name="nuevo_asiento" id="form_nuevo_asiento">
                    <button type="submit" class="btn btn-warning w-100 fw-bold" id="btnConfirmarReasignar" disabled>Confirmar Cambio de Asiento</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// =============== DATOS MAESTROS DE RUTAS Y SUCURSAL ===============
const TODAS_LAS_RUTAS = <?= json_encode($rutas) ?>;
const CIUDAD_SUCURSAL_ACTUAL = <?= json_encode(strtoupper(trim($_SESSION['ciudad_sucursal'] ?? ''))) ?>;

// =============== DINÁMICA DE PASAJEROS MULTIPLES ===============
let paxCount = 1;

function agregarPasajero() {
    let index = paxCount++;
    let abierto = document.getElementById('fecha_abierta').checked;
    
    let rutaId = document.getElementById('ruta_id_nuevo').value;
    let rObj = TODAS_LAS_RUTAS.find(r => r.ID_RUTA == rutaId);
    let esConexion = rObj ? (rObj.TIPO_VIAJE === 'CONEXION') : false;
    
    let reqAttr = (abierto || esConexion) ? '' : 'required';
    let disAttr = abierto ? 'disabled' : '';

    let html = `
        <div class="pasajero-row border p-3 mb-3 bg-white rounded position-relative shadow-sm">
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="this.closest('.pasajero-row').remove(); ponerPrecioDefault();">❌ Quitar</button>
            <h6 class="text-secondary fw-bold">👤 Pasajero Adicional</h6>
            <div class="row mb-2">
                <div class="col-md-3"><label>Cédula/Pasaporte</label><input type="text" name="cliente_id[]" id="cliente_id_${index}" class="form-control" required onchange="buscarClienteAuto(this.value, ${index})"></div>
                <div class="col-md-3"><label>Nombres</label><input type="text" name="cliente_nombre[]" id="cliente_nombre_${index}" class="form-control" required></div>
                <div class="col-md-3"><label>Apellidos</label><input type="text" name="cliente_apellido[]" id="cliente_apellido_${index}" class="form-control" required></div>
                <div class="col-md-3"><label>Teléfono</label><input type="text" name="cliente_telefono[]" id="cliente_telefono_${index}" class="form-control" required></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><label>Correo Electrónico</label><input type="email" name="cliente_correo[]" id="cliente_correo_${index}" class="form-control" placeholder="Opcional"></div>
                <div class="col-md-4"><label>Nacionalidad</label><input list="lista_nacionalidades" name="cliente_nacionalidad[]" id="cliente_nacionalidad_${index}" class="form-control text-uppercase" value="ECUATORIANA" required></div>
                <div class="col-md-4"><label>Fecha Nacimiento</label><input type="date" name="cliente_fechanac[]" id="cliente_fechanac_${index}" class="form-control"></div>
            </div>
            <div class="row mb-1 mt-3">
                <div class="col-md-4">
                    <label class="fw-bold">N° Asiento</label>
                    <div class="input-group">
                        <input type="text" name="asiento[]" id="asiento_nuevo_${index}" class="form-control text-center fw-bold bg-white border-dark" readonly ${reqAttr} ${disAttr} placeholder="-">
                        <button type="button" class="btn btn-dark" onclick="abrirMapaAsientos('nuevo', null, 'nuevo', 'asiento_nuevo_${index}')" ${disAttr}>📍 Elegir</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('contenedor_pasajeros').insertAdjacentHTML('beforeend', html);
    ponerPrecioDefault();
}

function buscarClienteAuto(id, index) {
    if (id.length >= 4) {
        fetch('/suramerica2030/pasajes/buscarCliente?id=' + id)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                let c = res.data;
                const nomEl = document.getElementById('cliente_nombre_' + index);
                const apeEl = document.getElementById('cliente_apellido_' + index);
                const nacEl = document.getElementById('cliente_nacionalidad_' + index);

                if (nomEl) { nomEl.value = c.CLI_NOMBRE || ''; nomEl.dispatchEvent(new Event('input', { bubbles: true })); }
                if (apeEl) { apeEl.value = c.CLI_APELLIDO || ''; apeEl.dispatchEvent(new Event('input', { bubbles: true })); }
                document.getElementById('cliente_telefono_' + index).value = c.CLI_TELEFONO || '';
                document.getElementById('cliente_correo_' + index).value = c.CLI_CORREO || '';
                if (nacEl && c.CLI_NACIONALIDAD) { nacEl.value = c.CLI_NACIONALIDAD; nacEl.dispatchEvent(new Event('input', { bubbles: true })); }
                if (c.CLI_FECHANAC) document.getElementById('cliente_fechanac_' + index).value = c.CLI_FECHANAC;
            }
        });
    }
}

// =============== LÓGICA DE ITINERARIO (ORIGEN / DESTINO) Y PRECIOS ===============
function limpiarAsientosNuevos() {
    document.querySelectorAll('input[name="asiento[]"]').forEach(el => el.value = '');
}

function getRutasValidasPorFecha() {
    let abierto = document.getElementById('fecha_abierta').checked;
    if (abierto) return TODAS_LAS_RUTAS;
    
    let fechaVal = document.getElementById('fecha_viaje_nuevo').value;
    if (!fechaVal) return TODAS_LAS_RUTAS;
    
    let d = new Date(fechaVal + 'T00:00:00');
    let diaPhp = d.getDay() === 0 ? 7 : d.getDay(); // 1: Lun ... 7: Dom
    
    return TODAS_LAS_RUTAS.filter(r => {
        if (!r.DIAS_SALIDA) return true;
        let dias = r.DIAS_SALIDA.split(',').map(s => s.trim());
        return dias.includes(diaPhp.toString());
    });
}

function filtrarRutasPorFecha() {
    let rutasValidas = getRutasValidasPorFecha();
    let selOrigen = document.getElementById('sel_origen_pasaje');
    let selDestino = document.getElementById('sel_destino_pasaje');
    
    let origenPrevio = selOrigen.value;
    let destinoPrevio = selDestino.value;
    
    // Obtenemos lista única de orígenes disponibles
    let origenesUnicos = Array.from(new Set(rutasValidas.map(r => r.RUT_ORIGEN))).sort();
    
    selOrigen.innerHTML = '<option value="">- Seleccione Origen -</option>';
    origenesUnicos.forEach(orig => {
        let opt = document.createElement('option');
        opt.value = orig;
        opt.textContent = '📍 ' + orig;
        selOrigen.appendChild(opt);
    });
    
    // Intentamos restaurar el origen seleccionado previamente o el de la sucursal del usuario
    if (origenPrevio && origenesUnicos.includes(origenPrevio)) {
        selOrigen.value = origenPrevio;
    } else if (!origenPrevio && CIUDAD_SUCURSAL_ACTUAL) {
        let matchSuc = origenesUnicos.find(o => o.toUpperCase() === CIUDAD_SUCURSAL_ACTUAL);
        if (matchSuc) selOrigen.value = matchSuc;
    }
    
    alCambiarOrigenPasaje(destinoPrevio);
}

function alCambiarOrigenPasaje(forzarDestino = null) {
    let origen = document.getElementById('sel_origen_pasaje').value;
    let selDestino = document.getElementById('sel_destino_pasaje');
    let rutaIdInput = document.getElementById('ruta_id_nuevo');
    let infoRuta = document.getElementById('info_ruta_seleccionada');
    let cajaMulti = document.getElementById('caja_multiple_horario');
    
    if (!origen) {
        selDestino.innerHTML = '<option value="">- Elija Origen Primero -</option>';
        selDestino.disabled = true;
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        cajaMulti.classList.add('d-none');
        ponerPrecioDefault();
        limpiarAsientosNuevos();
        return;
    }
    
    let rutasValidas = getRutasValidasPorFecha().filter(r => r.RUT_ORIGEN === origen);
    let destinosUnicos = Array.from(new Set(rutasValidas.map(r => r.RUT_DESTINO))).sort();
    
    selDestino.innerHTML = '<option value="">- Seleccione Destino -</option>';
    destinosUnicos.forEach(dest => {
        let opt = document.createElement('option');
        opt.value = dest;
        opt.textContent = '🏁 ' + dest;
        selDestino.appendChild(opt);
    });
    selDestino.disabled = false;
    
    if (forzarDestino && destinosUnicos.includes(forzarDestino)) {
        selDestino.value = forzarDestino;
        alCambiarDestinoPasaje();
    } else {
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        cajaMulti.classList.add('d-none');
        ponerPrecioDefault();
        limpiarAsientosNuevos();
    }
}

function alCambiarDestinoPasaje() {
    let origen = document.getElementById('sel_origen_pasaje').value;
    let destino = document.getElementById('sel_destino_pasaje').value;
    let rutaIdInput = document.getElementById('ruta_id_nuevo');
    let infoRuta = document.getElementById('info_ruta_seleccionada');
    let cajaMulti = document.getElementById('caja_multiple_horario');
    let selHorario = document.getElementById('sel_horario_pasaje');
    
    if (!origen || !destino) {
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        cajaMulti.classList.add('d-none');
        ponerPrecioDefault();
        limpiarAsientosNuevos();
        return;
    }
    
    let matches = getRutasValidasPorFecha().filter(r => r.RUT_ORIGEN === origen && r.RUT_DESTINO === destino);
    if (matches.length === 0) {
        matches = TODAS_LAS_RUTAS.filter(r => r.RUT_ORIGEN === origen && r.RUT_DESTINO === destino);
    }
    
    if (matches.length === 1) {
        cajaMulti.classList.add('d-none');
        seleccionarRutaEspecifica(matches[0].ID_RUTA);
    } else if (matches.length > 1) {
        cajaMulti.classList.remove('d-none');
        selHorario.innerHTML = '';
        matches.forEach(m => {
            let opt = document.createElement('option');
            opt.value = m.ID_RUTA;
            let esConex = (m.TIPO_VIAJE === 'CONEXION') ? 'Transbordo' : 'Directo';
            let modStr = (m.MODALIDAD === 'PANAMERICANA') ? '⭐ Vía Panamericana (Directo a Cúcuta)' : ((m.MODALIDAD === 'CONEXION_TRAMOS') ? '🔀 Vía Conexión Tramos' : esConex);
            opt.textContent = `${modStr} — Tarifa: $${parseFloat(m.PRECIO_PASAJE).toFixed(2)} (Salida: ${m.HORA_SALIDA ? m.HORA_SALIDA.substring(0,5) : '08:00'})`;
            selHorario.appendChild(opt);
        });
        seleccionarRutaEspecifica(matches[0].ID_RUTA);
    } else {
        alert("No se encontró una ruta activa entre estas dos ciudades.");
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        cajaMulti.classList.add('d-none');
    }
}

function seleccionarRutaEspecifica(rutaId) {
    let r = TODAS_LAS_RUTAS.find(x => x.ID_RUTA == rutaId);
    if (!r) return;
    
    document.getElementById('ruta_id_nuevo').value = r.ID_RUTA;
    
    // Actualizamos Ficha Resumen
    let infoRuta = document.getElementById('info_ruta_seleccionada');
    infoRuta.classList.remove('d-none');
    
    let badgeTipo = document.getElementById('badge_tipo_viaje');
    let esConexion = (r.TIPO_VIAJE === 'CONEXION');
    if (esConexion) {
        badgeTipo.className = 'badge bg-warning text-dark fs-6';
        badgeTipo.innerHTML = '🔄 CONEXIÓN';
    } else {
        badgeTipo.className = 'badge bg-success fs-6';
        badgeTipo.innerHTML = '➡️ DIRECTO';
    }
    
    let modBadge = document.getElementById('badge_modalidad');
    if (!modBadge) {
        modBadge = document.createElement('span');
        modBadge.id = 'badge_modalidad';
        modBadge.className = 'badge ms-1 fs-6';
        badgeTipo.parentNode.insertBefore(modBadge, document.getElementById('badge_horario'));
    }
    if (r.MODALIDAD === 'PANAMERICANA') {
        modBadge.className = 'badge bg-primary text-white fs-6 ms-1';
        modBadge.innerHTML = '⭐ Vía Panamericana';
        modBadge.style.display = 'inline-block';
    } else if (r.MODALIDAD === 'CONEXION_TRAMOS') {
        modBadge.className = 'badge bg-secondary text-white fs-6 ms-1';
        modBadge.innerHTML = '🔀 Vía Tramos';
        modBadge.style.display = 'inline-block';
    } else {
        modBadge.style.display = 'none';
    }
    
    document.getElementById('badge_horario').textContent = '⏰ ' + (r.HORA_SALIDA ? r.HORA_SALIDA.substring(0,5) : '08:00');
    document.getElementById('badge_tramo_nombre').textContent = r.RUT_ORIGEN + ' ➔ ' + r.RUT_DESTINO;
    document.getElementById('badge_precio').textContent = '$' + parseFloat(r.PRECIO_PASAJE).toFixed(2);
    document.getElementById('badge_equipaje').textContent = '🧳 ' + parseFloat(r.LIMITE_EQUIPAJE_KG || 35) + ' Kg máx';
    
    let alertaTramos = document.getElementById('alerta_tramos');
    let busIdSelect = document.getElementById('bus_id_nuevo');
    let asientosInputs = document.querySelectorAll('input[name="asiento[]"]');
    
    if (esConexion) {
        alertaTramos.classList.remove('d-none');
        busIdSelect.required = false;
        asientosInputs.forEach(el => el.required = false);
    } else {
        alertaTramos.classList.add('d-none');
        if (!document.getElementById('fecha_abierta').checked) {
            busIdSelect.required = true;
            asientosInputs.forEach(el => el.required = true);
        }
    }
    
    ponerPrecioDefault();
    limpiarAsientosNuevos();
}

function ponerPrecioDefault() {
    let rutaId = document.getElementById('ruta_id_nuevo').value;
    let r = TODAS_LAS_RUTAS.find(x => x.ID_RUTA == rutaId);
    
    if (!r) {
        document.getElementById('precio_total').value = '0.00';
        document.getElementById('monto_abonar').value = '0.00';
        return;
    }
    
    let precioBase = parseFloat(r.PRECIO_PASAJE) || 0;
    let cantPasajeros = document.querySelectorAll('.pasajero-row').length;
    let totalGlobal = precioBase * cantPasajeros;
    
    document.getElementById('precio_total').value = totalGlobal.toFixed(2);
    document.getElementById('monto_abonar').value = totalGlobal.toFixed(2);
}

function toggleItinerario() {
    let abierto = document.getElementById('fecha_abierta').checked;
    let inputFecha = document.getElementById('fecha_viaje_nuevo');
    let busId = document.getElementById('bus_id_nuevo');
    let asientos = document.querySelectorAll('input[name="asiento[]"]');
    let btnAsientos = document.querySelectorAll('button[onclick^="abrirMapaAsientos"]');
    
    let rutaId = document.getElementById('ruta_id_nuevo').value;
    let rObj = TODAS_LAS_RUTAS.find(r => r.ID_RUTA == rutaId);
    let esConexion = rObj ? (rObj.TIPO_VIAJE === 'CONEXION') : false;
    
    if (abierto) {
        inputFecha.required = false; inputFecha.disabled = true;
        busId.required = false; busId.disabled = true;
        asientos.forEach(el => { el.required = false; el.value = ''; el.disabled = true; });
        btnAsientos.forEach(el => { el.disabled = true; });
    } else {
        inputFecha.required = true; inputFecha.disabled = false;
        if (!esConexion) {
            busId.required = true;
            asientos.forEach(el => { el.required = true; });
        }
        busId.disabled = false;
        asientos.forEach(el => { el.disabled = false; });
        btnAsientos.forEach(el => { el.disabled = false; });
    }
    filtrarRutasPorFecha();
}

document.addEventListener('DOMContentLoaded', function() {
    filtrarRutasPorFecha();
});

// =============== LÓGICA DE MAPA DE ASIENTOS CON CONTROL DE COLISIONES ===============
function abrirMapaParaReasignar(idPasaje, fecha, ruta, bus, asientoActual, totalAsientos) {
    document.getElementById('footerReasignar').style.display = 'block';
    document.getElementById('form_id_pasaje').value = idPasaje;
    document.getElementById('target_input_asiento').value = 'form_nuevo_asiento';
    document.getElementById('btnConfirmarReasignar').disabled = true;
    generarYMostrarCroquis(fecha, ruta, bus, totalAsientos || 42, asientoActual, 'form_nuevo_asiento');
}

function abrirMapaAsientos(idPasaje, idRuta, tipo, targetId) {
    document.getElementById('footerReasignar').style.display = 'none';
    let fecha, ruta, busElem, asientostotales;

    if (tipo === 'nuevo') {
        fecha = document.getElementById('fecha_viaje_nuevo').value;
        ruta = document.getElementById('ruta_id_nuevo').value;
        busElem = document.getElementById('bus_id_nuevo');
        document.getElementById('target_input_asiento').value = targetId;
    } else {
        fecha = document.getElementById('fecha_asig_' + idPasaje).value;
        ruta = idRuta;
        busElem = document.getElementById('bus_asig_' + idPasaje);
        document.getElementById('target_input_asiento').value = 'asiento_asig_' + idPasaje;
    }

    if (!fecha || !ruta || !busElem.value) {
        alert("Por favor seleccione primero la Fecha, la Ruta y el Bus.");
        return;
    }
    asientostotales = parseInt(busElem.selectedOptions[0].getAttribute('data-asientos')) || 42;
    generarYMostrarCroquis(fecha, ruta, busElem.value, asientostotales, null, targetId);
}

function generarYMostrarCroquis(fecha, ruta, bus, totalAsientos, asientoActualReasignar, targetInputId) {
    let contenedor = document.getElementById('dibujo_asientos');
    contenedor.innerHTML = '<p>Cargando asientos...</p>';
    let modal = new bootstrap.Modal(document.getElementById('modalCroquisBus'));
    modal.show();

    let asientosSeleccionadosEnForm = [];
    document.querySelectorAll('input[name="asiento[]"]').forEach(el => {
        if(el.value !== "" && el.id !== targetInputId) {
            asientosSeleccionadosEnForm.push(parseInt(el.value));
        }
    });

    fetch(`/suramerica2030/pasajes/apiAsientosOcupados?fecha=${fecha}&ruta=${ruta}&bus=${bus}`)
        .then(response => response.json())
        .then(ocupados => {
            // Convertimos la respuesta stringificada de PHP a números enteros puros
            ocupados = ocupados.map(Number);
            ocupados = [...ocupados, ...asientosSeleccionadosEnForm]; 
            
            let html = '<div class="volante"></div><div class="asiento-grid">';
            for (let i = 1; i <= totalAsientos; i++) {
                let isOcupado = ocupados.includes(i);
                if (asientoActualReasignar && i === parseInt(asientoActualReasignar)) { isOcupado = false; }
                
                let clase = isOcupado ? 'asiento ocupado' : 'asiento';
                let onclick = isOcupado ? '' : `onclick="seleccionarAsiento(${i})"`;
                let icon = isOcupado ? 'X' : i;
                html += `<div class="${clase}" ${onclick} id="box_asiento_${i}">${icon}</div>`;
                if (i % 2 === 0 && i % 4 !== 0) { html += `<div class="pasillo"></div>`; }
            }
            html += '</div>';
            contenedor.innerHTML = html;
        });
}

function seleccionarAsiento(numero) {
    document.querySelectorAll('.asiento').forEach(el => {
        if(!el.classList.contains('ocupado')) { el.style.background = '#e8f5e9'; el.style.color = '#155724'; }
    });
    let box = document.getElementById('box_asiento_' + numero);
    box.style.background = '#155724';
    box.style.color = 'white';
    
    let target = document.getElementById('target_input_asiento').value;
    document.getElementById(target).value = numero;
    if(target === 'form_nuevo_asiento') { document.getElementById('btnConfirmarReasignar').disabled = false; }
    
    if(document.getElementById('footerReasignar').style.display === 'none') {
        setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('modalCroquisBus')).hide(); }, 300);
    }
}

function limpiarAsiento(id) { document.getElementById('asiento_asig_' + id).value = ''; }

function agregarPiezaEquipaje(idPasaje, precioExtra, limiteKg) {
    const contenedor = document.getElementById('contenedor_piezas_eq_' + idPasaje);
    if (!contenedor) return;

    const fila = document.createElement('div');
    fila.className = 'row g-2 pieza-equipaje-row mb-2 align-items-end';
    fila.setAttribute('data-pasaje', idPasaje);
    fila.innerHTML = `
        <div class="col-md-3 col-6">
            <label class="form-label mb-0" style="font-size:11px;">Tipo</label>
            <select name="eq_tipo[]" class="form-select form-select-sm" onchange="calcularCobroEquipaje(${idPasaje}, ${precioExtra}, ${limiteKg})">
                <option value="Maleta">Maleta</option>
                <option value="Bolso">Bolso / Maletín</option>
                <option value="Caja">Caja</option>
                <option value="Bulto">Bulto / Saco</option>
                <option value="Mochila">Mochila</option>
                <option value="Otro">Otro</option>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label mb-0" style="font-size:11px;">Peso (Kg)</label>
            <input type="number" step="0.1" min="0" name="eq_peso[]" class="form-control form-control-sm text-end fw-bold eq-peso-input" placeholder="0.0" required oninput="calcularCobroEquipaje(${idPasaje})" onchange="calcularCobroEquipaje(${idPasaje})">
        </div>
        <div class="col-md-6 col-10">
            <label class="form-label mb-0" style="font-size:11px;">Descripción (opcional)</label>
            <input type="text" name="eq_descripcion[]" class="form-control form-control-sm" placeholder="Ej: Ropa, Frágil...">
        </div>
        <div class="col-md-1 col-2 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="eliminarPiezaEquipaje(this, ${idPasaje})" title="Eliminar pieza">×</button>
        </div>
    `;
    contenedor.appendChild(fila);
    calcularCobroEquipaje(idPasaje);
}

function eliminarPiezaEquipaje(btn, idPasaje, precioExtra, limiteKg) {
    const contenedor = document.getElementById('contenedor_piezas_eq_' + idPasaje);
    if (!contenedor) return;

    const filas = contenedor.querySelectorAll('.pieza-equipaje-row');
    if (filas.length > 1) {
        btn.closest('.pieza-equipaje-row').remove();
    } else {
        const fila = filas[0];
        const inputPeso = fila.querySelector('input[name="eq_peso[]"]');
        const inputDesc = fila.querySelector('input[name="eq_descripcion[]"]');
        if (inputPeso) inputPeso.value = '';
        if (inputDesc) inputDesc.value = '';
    }
    calcularCobroEquipaje(idPasaje, precioExtra, limiteKg);
}

function alCambiarTramoEquipaje(idPasaje) {
    const selTramo = document.getElementById('sel_tramo_eq_' + idPasaje);
    if (!selTramo || selTramo.tagName !== 'SELECT' || !selTramo.selectedOptions || selTramo.selectedOptions.length === 0) return;
    const opt = selTramo.selectedOptions[0];
    const limite = parseFloat(opt.getAttribute('data-limite')) || 35.0;
    const tarifa = parseFloat(opt.getAttribute('data-tarifa')) || 1.0;
    
    // Forzar actualización del valor sugerido de sobrepeso
    const cxInput = document.getElementById('cx_' + idPasaje);
    if (cxInput) cxInput.setAttribute('data-auto', '1');
    
    calcularCobroEquipaje(idPasaje, tarifa, limite);
}

function calcularCobroEquipaje(idPasaje, precioExtra, limiteKg) {
    const contenedor = document.getElementById('contenedor_piezas_eq_' + idPasaje);
    if (!contenedor) return;

    // Obtener tarifa y límite por defecto si no vinieron
    const tarifaInput = document.getElementById('tarifa_input_eq_' + idPasaje);
    if (precioExtra === undefined || precioExtra === null || isNaN(precioExtra)) {
        precioExtra = tarifaInput ? (parseFloat(tarifaInput.value) || 1.0) : 1.0;
    }
    const limiteInput = document.getElementById('limite_input_eq_' + idPasaje);
    if (limiteKg === undefined || limiteKg === null || isNaN(limiteKg)) {
        limiteKg = limiteInput ? (parseFloat(limiteInput.value) || 35.0) : 35.0;
    }

    // Si existe el selector de tramo (SELECT), sobreescribir la tarifa y el límite según la opción elegida
    const selTramo = document.getElementById('sel_tramo_eq_' + idPasaje);
    if (selTramo && selTramo.tagName === 'SELECT' && selTramo.selectedOptions && selTramo.selectedOptions.length > 0) {
        const opt = selTramo.selectedOptions[0];
        const limiteOpt = parseFloat(opt.getAttribute('data-limite'));
        const tarifaOpt = parseFloat(opt.getAttribute('data-tarifa'));
        if (!isNaN(limiteOpt) && limiteOpt > 0) limiteKg = limiteOpt;
        if (!isNaN(tarifaOpt) && tarifaOpt >= 0) precioExtra = tarifaOpt;
        
        const operador = opt.getAttribute('data-operador') || 'General';
        const admite = opt.getAttribute('data-admite') === '1';
        
        const lblOp = document.getElementById('lbl_operador_eq_' + idPasaje);
        if (lblOp) lblOp.textContent = operador;
        const lblLim = document.getElementById('lbl_limite_eq_' + idPasaje);
        if (lblLim) lblLim.textContent = limiteKg.toFixed(1) + ' Kg';
        const lblTar = document.getElementById('lbl_tarifa_eq_' + idPasaje);
        if (lblTar) lblTar.textContent = '$' + precioExtra.toFixed(2) + '/Kg';
        
        if (tarifaInput) tarifaInput.value = precioExtra.toFixed(2);
        if (limiteInput) limiteInput.value = limiteKg.toFixed(1);
        
        const alertaNoAdmite = document.getElementById('alerta_no_admite_eq_' + idPasaje);
        if (alertaNoAdmite) {
            if (!admite) alertaNoAdmite.classList.remove('d-none');
            else alertaNoAdmite.classList.add('d-none');
        }
    }

    const pesoMano = 10.0; // Maleta de mano fija (10kg)
    let totalBodega = 0.0;
    let cantidadPiezas = 0;

    const filas = contenedor.querySelectorAll('.pieza-equipaje-row');
    filas.forEach(fila => {
        const pesoEl = fila.querySelector('input[name="eq_peso[]"]');
        if (!pesoEl) return;
        const rawVal = (pesoEl.value || '').toString().trim().replace(',', '.');
        const peso = parseFloat(rawVal) || 0;

        if (peso > 0 || rawVal !== '') {
            cantidadPiezas++;
        }
        totalBodega += peso;
    });

    totalBodega = Math.round(totalBodega * 10) / 10;
    const totalGlobal = Math.round((totalBodega + pesoMano) * 10) / 10;
    const limite = parseFloat(limiteKg) || 35.0;

    const cantEl = document.getElementById('peq_cant_' + idPasaje);
    const bodEl = document.getElementById('peq_bodega_' + idPasaje);
    const manEl = document.getElementById('peq_mano_' + idPasaje);
    const totEl = document.getElementById('pt_' + idPasaje);
    const pmInput = document.getElementById('pm_' + idPasaje);
    const pbInput = document.getElementById('pb_' + idPasaje);

    if (cantEl) cantEl.innerText = Math.max(1, cantidadPiezas);
    if (bodEl) bodEl.innerText = totalBodega.toFixed(1);
    if (manEl) manEl.innerText = pesoMano.toFixed(1);
    if (totEl) totEl.innerText = totalGlobal.toFixed(1);

    if (pmInput) pmInput.value = pesoMano.toFixed(1);
    if (pbInput) pbInput.value = totalBodega.toFixed(1);

    const warnEl = document.getElementById('warn_' + idPasaje);
    const pexEl = document.getElementById('pex_' + idPasaje);
    const cobroEl = document.getElementById('cobro_' + idPasaje);
    const cxInput = document.getElementById('cx_' + idPasaje);
    const sugEl = document.getElementById('sug_' + idPasaje);

    if (totalGlobal > limite) {
        const exceso = Math.round((totalGlobal - limite) * 10) / 10;
        const tarifaBase = (exceso * parseFloat(precioExtra)).toFixed(2);

        if (warnEl) warnEl.classList.remove('d-none');
        if (pexEl) pexEl.innerText = exceso.toFixed(1);
        if (cobroEl) cobroEl.classList.remove('d-none');

        if (sugEl) sugEl.innerText = '(Base: $' + tarifaBase + ')';
        if (cxInput && (cxInput.value === '' || cxInput.value === '0' || cxInput.value === '0.00' || cxInput.getAttribute('data-auto') === '1' || cxInput.readOnly)) {
            cxInput.value = tarifaBase;
            cxInput.setAttribute('data-auto', '1');
            if (typeof balancearMixtoEquipaje === 'function') {
                balancearMixtoEquipaje(idPasaje, 'total');
            }
        }
    } else {
        if (warnEl) warnEl.classList.add('d-none');
        if (cobroEl) cobroEl.classList.add('d-none');
        if (cxInput) {
            cxInput.value = '0.00';
            cxInput.removeAttribute('data-auto');
            if (typeof balancearMixtoEquipaje === 'function') {
                balancearMixtoEquipaje(idPasaje, 'total');
            }
        }
        if (sugEl) sugEl.innerText = '';
    }
}

// Delegación global para eventos de input y change en piezas de equipaje
document.addEventListener('input', function (e) {
    if (e.target && e.target.name === 'eq_peso[]') {
        const row = e.target.closest('.pieza-equipaje-row');
        if (row && row.getAttribute('data-pasaje')) {
            calcularCobroEquipaje(row.getAttribute('data-pasaje'));
        }
    }
});
document.addEventListener('change', function (e) {
    if (e.target && (e.target.name === 'eq_peso[]' || e.target.name === 'eq_tipo[]')) {
        const row = e.target.closest('.pieza-equipaje-row');
        if (row && row.getAttribute('data-pasaje')) {
            calcularCobroEquipaje(row.getAttribute('data-pasaje'));
        }
    }
});

// Recalcular equipaje automáticamente al abrir cualquier modal de equipaje
['show.bs.modal', 'shown.bs.modal'].forEach(function (evtName) {
    document.addEventListener(evtName, function (e) {
        if (e.target && e.target.id && e.target.id.startsWith('modalEquipaje')) {
            const idPasaje = e.target.id.replace('modalEquipaje', '');
            calcularCobroEquipaje(idPasaje);
        }
    });
});

// =============== GESTIÓN DE ACTUALIZACIÓN FINANCIERA ===============
function aplicarFinanciero(id, precio, saldo, estado) {
    const pInput = document.getElementById('fin_precio_' + id);
    const sInput = document.getElementById('fin_saldo_' + id);
    const eSelect = document.getElementById('fin_estado_' + id);
    if (pInput) pInput.value = parseFloat(precio).toFixed(2);
    if (sInput) sInput.value = parseFloat(saldo).toFixed(2);
    if (eSelect) eSelect.value = estado;
}

function marcarPagado(id) {
    const sInput = document.getElementById('fin_saldo_' + id);
    const eSelect = document.getElementById('fin_estado_' + id);
    if (sInput) sInput.value = '0.00';
    if (eSelect) eSelect.value = 'PAGADO';
}

function recalcularFinanciero(id) {
    const sInput = document.getElementById('fin_saldo_' + id);
    const eSelect = document.getElementById('fin_estado_' + id);
    if (!sInput || !eSelect) return;
    const saldo = parseFloat(sInput.value) || 0;
    if (saldo <= 0) {
        eSelect.value = 'PAGADO';
    } else {
        eSelect.value = 'RESERVA';
    }
}

// Si en nueva venta el abono supera el total asignado, ajustar el total automáticamente
const montoAbonarEl = document.getElementById('monto_abonar');
if (montoAbonarEl) {
    montoAbonarEl.addEventListener('input', function() {
        let abono = parseFloat(this.value) || 0;
        let totalEl = document.getElementById('precio_total');
        if (totalEl) {
            let total = parseFloat(totalEl.value) || 0;
            if (abono > total) {
                totalEl.value = abono.toFixed(2);
            }
        }
    });
}

// =============== FILTROS RÁPIDOS HISTORIAL PASAJES ===============
function aplicarRangoRapido(tipo) {
    const dInicio = document.getElementById('filtro_fecha_inicio');
    const dFin = document.getElementById('filtro_fecha_fin');
    const tipoFecha = document.getElementById('filtro_tipo_fecha');
    const form = document.getElementById('formFiltroHistorial');
    if (!dInicio || !dFin) return;

    if (tipoFecha && tipoFecha.value === 'abierta') {
        tipoFecha.value = 'viaje';
        actualizarEstadoCamposFecha();
    }

    const hoy = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const formatear = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (tipo === 'hoy') {
        const strHoy = formatear(hoy);
        dInicio.value = strHoy;
        dFin.value = strHoy;
    } else if (tipo === 'manana') {
        const manana = new Date(hoy);
        manana.setDate(hoy.getDate() + 1);
        const strManana = formatear(manana);
        dInicio.value = strManana;
        dFin.value = strManana;
    } else if (tipo === 'esta_semana') {
        const dia = hoy.getDay(); // 0 es domingo
        const diffLunes = hoy.getDate() - dia + (dia === 0 ? -6 : 1);
        const lunes = new Date(hoy);
        lunes.setDate(diffLunes);
        const domingo = new Date(lunes);
        domingo.setDate(lunes.getDate() + 6);
        dInicio.value = formatear(lunes);
        dFin.value = formatear(domingo);
    } else if (tipo === 'este_mes') {
        const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
        dInicio.value = formatear(primerDia);
        dFin.value = formatear(ultimoDia);
    } else if (tipo === 'proximos_7') {
        const prox = new Date(hoy);
        prox.setDate(hoy.getDate() + 7);
        dInicio.value = formatear(hoy);
        dFin.value = formatear(prox);
    }

    if (form) form.submit();
}

function actualizarEstadoCamposFecha() {
    const tipoFecha = document.getElementById('filtro_tipo_fecha');
    const colIni = document.getElementById('col_fecha_inicio');
    const colFin = document.getElementById('col_fecha_fin');
    const colDia = document.getElementById('filtro_dia_semana');
    if (!tipoFecha) return;

    if (tipoFecha.value === 'abierta') {
        if (colIni) colIni.style.opacity = '0.4';
        if (colFin) colFin.style.opacity = '0.4';
        if (colDia) colDia.disabled = true;
        const dInicio = document.getElementById('filtro_fecha_inicio');
        const dFin = document.getElementById('filtro_fecha_fin');
        if (dInicio) dInicio.value = '';
        if (dFin) dFin.value = '';
    } else {
        if (colIni) colIni.style.opacity = '1';
        if (colFin) colFin.style.opacity = '1';
        if (colDia) colDia.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    actualizarEstadoCamposFecha();
});

// =============== FUNCIONES DE PAGO MIXTO (PASAJES) ===============
function togglePagoMixtoVenta() {
    const sel = document.getElementById('metodo_pago_nuevo');
    const div = document.getElementById('div_pago_mixto_nuevo');
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoVenta('init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoVenta(origen) {
    const sel = document.getElementById('metodo_pago_nuevo');
    if (!sel || sel.value !== 'MIXTO') return;
    const totalEl = document.getElementById('monto_abonar');
    const efEl = document.getElementById('monto_efectivo_nuevo');
    const trEl = document.getElementById('monto_transferencia_nuevo');
    const badge = document.getElementById('badge_check_mixto_nuevo');
    if (!totalEl || !efEl || !trEl) return;

    let total = parseFloat(totalEl.value) || 0;
    let ef = parseFloat(efEl.value) || 0;
    let tr = parseFloat(trEl.value) || 0;

    if (origen === 'init') {
        ef = total > 0 ? (total / 2) : 0;
        tr = total - ef;
        efEl.value = ef.toFixed(2);
        trEl.value = tr.toFixed(2);
    } else if (origen === 'efectivo') {
        tr = Math.max(0, total - ef);
        trEl.value = tr.toFixed(2);
    } else if (origen === 'transferencia') {
        ef = Math.max(0, total - tr);
        efEl.value = ef.toFixed(2);
    } else if (origen === 'total') {
        if (ef > total) ef = total;
        tr = Math.max(0, total - ef);
        efEl.value = ef.toFixed(2);
        trEl.value = tr.toFixed(2);
    }

    if (badge) {
        let diff = Math.abs((parseFloat(efEl.value || 0) + parseFloat(trEl.value || 0)) - total);
        if (diff < 0.02 && total > 0) {
            badge.className = 'badge bg-success';
            badge.textContent = '✔ Cuadrado ($' + total.toFixed(2) + ')';
        } else {
            badge.className = 'badge bg-danger';
            badge.textContent = '⚠️ Descuadre ($' + diff.toFixed(2) + ')';
        }
    }
}

function validarVentaMixto(form) {
    const sel = document.getElementById('metodo_pago_nuevo');
    if (sel && sel.value === 'MIXTO') {
        const total = parseFloat(document.getElementById('monto_abonar').value) || 0;
        const ef = parseFloat(document.getElementById('monto_efectivo_nuevo').value) || 0;
        const tr = parseFloat(document.getElementById('monto_transferencia_nuevo').value) || 0;
        if (total <= 0) {
            alert('El monto a abonar debe ser mayor a 0.');
            return false;
        }
        if (Math.abs((ef + tr) - total) > 0.02) {
            alert('El pago mixto no cuadra: Efectivo ($' + ef.toFixed(2) + ') + Transferencia ($' + tr.toFixed(2) + ') = $' + (ef + tr).toFixed(2) + ', pero el total a cobrar es $' + total.toFixed(2) + '. Corrija los valores antes de continuar.');
            return false;
        }
    }
    return true;
}

// Abonos
function togglePagoMixtoAbono(id) {
    const sel = document.getElementById('metodo_pago_abono_' + id);
    const div = document.getElementById('div_mixto_abono_' + id);
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoAbono(id, 'init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoAbono(id, origen) {
    const sel = document.getElementById('metodo_pago_abono_' + id);
    if (!sel || sel.value !== 'MIXTO') return;
    const totalEl = document.getElementById('monto_abono_' + id);
    const efEl = document.getElementById('monto_efectivo_abono_' + id);
    const trEl = document.getElementById('monto_transferencia_abono_' + id);
    const badge = document.getElementById('badge_mixto_abono_' + id);
    if (!totalEl || !efEl || !trEl) return;

    let total = parseFloat(totalEl.value) || 0;
    let ef = parseFloat(efEl.value) || 0;
    let tr = parseFloat(trEl.value) || 0;

    if (origen === 'init') {
        ef = total > 0 ? (total / 2) : 0;
        tr = total - ef;
        efEl.value = ef.toFixed(2);
        trEl.value = tr.toFixed(2);
    } else if (origen === 'efectivo') {
        tr = Math.max(0, total - ef);
        trEl.value = tr.toFixed(2);
    } else if (origen === 'transferencia') {
        ef = Math.max(0, total - tr);
        efEl.value = ef.toFixed(2);
    } else if (origen === 'total') {
        if (ef > total) ef = total;
        tr = Math.max(0, total - ef);
        efEl.value = ef.toFixed(2);
        trEl.value = tr.toFixed(2);
    }

    if (badge) {
        let diff = Math.abs((parseFloat(efEl.value || 0) + parseFloat(trEl.value || 0)) - total);
        if (diff < 0.02 && total > 0) {
            badge.className = 'badge bg-success';
            badge.textContent = '✔ Cuadrado';
        } else {
            badge.className = 'badge bg-danger';
            badge.textContent = '⚠️ Descuadre';
        }
    }
}

function validarAbonoMixto(form, id) {
    const sel = document.getElementById('metodo_pago_abono_' + id);
    if (sel && sel.value === 'MIXTO') {
        const total = parseFloat(document.getElementById('monto_abono_' + id).value) || 0;
        const ef = parseFloat(document.getElementById('monto_efectivo_abono_' + id).value) || 0;
        const tr = parseFloat(document.getElementById('monto_transferencia_abono_' + id).value) || 0;
        if (Math.abs((ef + tr) - total) > 0.02) {
            alert('El pago mixto del abono no coincide con el total ($' + total.toFixed(2) + '). Verifique los montos.');
            return false;
        }
    }
    return true;
}

// Equipaje / Sobrepeso
function togglePagoMixtoEquipaje(id) {
    const sel = document.getElementById('metodo_pago_eq_' + id);
    const div = document.getElementById('div_mixto_eq_' + id);
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoEquipaje(id, 'init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoEquipaje(id, origen) {
    const sel = document.getElementById('metodo_pago_eq_' + id);
    if (!sel || sel.value !== 'MIXTO') return;
    const totalEl = document.getElementById('cx_' + id);
    const efEl = document.getElementById('monto_efectivo_eq_' + id);
    const trEl = document.getElementById('monto_transferencia_eq_' + id);
    const badge = document.getElementById('badge_mixto_eq_' + id);
    if (!totalEl || !efEl || !trEl) return;

    let total = parseFloat(totalEl.value) || 0;
    let ef = parseFloat(efEl.value) || 0;
    let tr = parseFloat(trEl.value) || 0;

    if (origen === 'init') {
        ef = total > 0 ? (total / 2) : 0;
        tr = total - ef;
        efEl.value = ef.toFixed(2);
        trEl.value = tr.toFixed(2);
    } else if (origen === 'efectivo') {
        tr = Math.max(0, total - ef);
        trEl.value = tr.toFixed(2);
    } else if (origen === 'transferencia') {
        ef = Math.max(0, total - tr);
        efEl.value = ef.toFixed(2);
    } else if (origen === 'total') {
        if (ef > total) ef = total;
        tr = Math.max(0, total - ef);
        efEl.value = ef.toFixed(2);
        trEl.value = tr.toFixed(2);
    }

    if (badge) {
        let diff = Math.abs((parseFloat(efEl.value || 0) + parseFloat(trEl.value || 0)) - total);
        if (diff < 0.02 && total > 0) {
            badge.className = 'badge bg-success';
            badge.textContent = '✔ Cuadrado';
        } else {
            badge.className = 'badge bg-danger';
            badge.textContent = '⚠️ Descuadre';
        }
    }
}

function validarEquipajeMixto(form, id) {
    const sel = document.getElementById('metodo_pago_eq_' + id);
    if (sel && sel.value === 'MIXTO') {
        const total = parseFloat(document.getElementById('cx_' + id).value) || 0;
        const ef = parseFloat(document.getElementById('monto_efectivo_eq_' + id).value) || 0;
        const tr = parseFloat(document.getElementById('monto_transferencia_eq_' + id).value) || 0;
        if (total > 0 && Math.abs((ef + tr) - total) > 0.02) {
            alert('El cobro de sobrepeso mixto no coincide con el total ($' + total.toFixed(2) + '). Verifique los montos.');
            return false;
        }
    }
    return true;
}
</script>