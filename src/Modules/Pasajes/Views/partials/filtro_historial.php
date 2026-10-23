<!-- BUSCADOR E HISTORIAL AVANZADO (Solo visible en Historial) -->
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
                                <?= htmlspecialchars($r['RUT_ORIGEN'] . ' ➔ ' . $r['RUT_DESTINO'] . ' [' . ($r['MODALIDAD_NOMBRE'] ?? $r['MODALIDAD'] ?? 'Directo') . ']') ?>
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