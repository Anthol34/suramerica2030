<?php
use Platform\Helpers\FormatHelper;

$totalTramos = count($tramosCatalogo ?? []);
$tramosActivos = 0;
$sumaCosto = 0;
$sumaPvp = 0;
$operadoresTramos = [];

foreach ($tramosCatalogo ?? [] as $t) {
    if (($t['ESTADO'] ?? 'ACTIVO') === 'ACTIVO') {
        $tramosActivos++;
    }
    $sumaCosto += floatval($t['COSTO_OPERADOR'] ?? 0);
    $sumaPvp += floatval($t['PVP_SUGERIDO'] ?? 0);
    $opNom = trim($t['OPERADOR_NOMBRE'] ?? '');
    if (!empty($opNom)) {
        $operadoresTramos[$opNom] = true;
    }
}

$promedioCosto = ($totalTramos > 0) ? round($sumaCosto / $totalTramos, 2) : 0;
$promedioPvp = ($totalTramos > 0) ? round($sumaPvp / $totalTramos, 2) : 0;
$promedioUtilidad = max(0, $promedioPvp - $promedioCosto);
$promedioMargenPct = ($promedioPvp > 0) ? round(($promedioUtilidad / $promedioPvp) * 100, 1) : 0;
?>

<!-- ======================================================== -->
<!-- SECCIÓN NATIVA: CATÁLOGO MAESTRO DE TRAMOS Y TARIFAS      -->
<!-- ======================================================== -->
<div id="contenedorCatalogoTramos">
    <!-- BANNER DE TRAMOS MAESTROS -->
    <div class="card border-0 shadow-sm bg-gradient mb-4" style="background: linear-gradient(135deg, #e8f5e9 0%, #e3f2fd 100%); border-left: 5px solid #198754 !important;">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center py-3">
            <div class="mb-2 mb-md-0">
                <h5 class="fw-bold text-success mb-1">🧩 Catálogo Maestro de Tramos y Costos de Operadores</h5>
                <p class="text-muted small mb-0">
                    Define los bloques operacionales individuales (Ecuador, Colombia, Venezuela e Internacional). Al actualizar el costo o PVP de un tramo, se puede propagar a todas las rutas comerciales vinculadas con un solo clic.
                </p>
            </div>
            <div class="d-flex gap-2">
                <form action="/suramerica2030/flota/sincronizarTarifasTramos" method="POST" onsubmit="return confirm('¿Deseas propagar los costos y PVP de estos tramos maestros a todas las rutas comerciales existentes?');">
                    <button type="submit" class="btn btn-outline-success fw-bold shadow-sm" title="Propagar precios maestros a las rutas">
                        🔄 Sincronizar Tarifas con Rutas
                    </button>
                </form>
                <button class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGuardarTramoCatalogo" onclick="abrirModalNuevoTramo()">
                    + Nuevo Tramo Maestro
                </button>
            </div>
        </div>
    </div>

    <!-- TARJETAS ANALÍTICAS (KPIS) -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-2 text-center bg-white">
                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Total Tramos Maestros</small>
                <span class="fs-4 fw-bold text-dark"><?= $totalTramos ?></span>
                <small class="text-secondary d-block" style="font-size: 11px;"><?= $tramosActivos ?> activos en catálogo</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-2 text-center bg-white">
                <small class="text-danger fw-bold text-uppercase d-block" style="font-size: 10px;">Costo Operador Promedio</small>
                <span class="fs-4 fw-bold text-danger">$<?= number_format($promedioCosto, 2) ?></span>
                <small class="text-muted d-block" style="font-size: 11px;">Liquidación por tramo</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-2 text-center bg-white">
                <small class="text-primary fw-bold text-uppercase d-block" style="font-size: 10px;">PVP Promedio Sugerido</small>
                <span class="fs-4 fw-bold text-primary">$<?= number_format($promedioPvp, 2) ?></span>
                <small class="text-muted d-block" style="font-size: 11px;">Tarifa base al viajero</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-2 text-center bg-white">
                <small class="text-success fw-bold text-uppercase d-block" style="font-size: 10px;">Margen Promedio Agencia</small>
                <span class="fs-4 fw-bold text-success">$<?= number_format($promedioUtilidad, 2) ?> (<?= $promedioMargenPct ?>%)</span>
                <small class="text-muted d-block" style="font-size: 11px;">Rentabilidad neta</small>
            </div>
        </div>
    </div>

    <!-- PANEL DE FILTROS -->
    <div class="card shadow-sm border-0 mb-3 bg-light">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                        <input type="text" id="buscadorTramosCat" class="form-control bg-white border-start-0 ps-0" placeholder="Buscar por ciudad, código u operador..." autocomplete="off">
                        <button class="btn btn-white bg-white border border-start-0" type="button" onclick="limpiarBuscadorTramos()" title="Limpiar">✕</button>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted">🌎 País:</span>
                        <select id="filtroPaisTramos" class="form-select bg-white">
                            <option value="">Todos los Países</option>
                            <option value="ecuador">🇪🇨 Ecuador</option>
                            <option value="colombia">🇨🇴 Colombia</option>
                            <option value="venezuela">🇻🇪 Venezuela</option>
                            <option value="internacional">🌎 Internacional</option>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted">🏢 Operador:</span>
                        <select id="filtroOperadorTramos" class="form-select bg-white">
                            <option value="">Todos los Operadores</option>
                            <?php foreach (array_keys($operadoresTramos) as $opName): ?>
                                <option value="<?= htmlspecialchars(mb_strtolower($opName)) ?>"><?= htmlspecialchars($opName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted">🔘 Estado:</span>
                        <select id="filtroEstadoTramos" class="form-select bg-white">
                            <option value="">Todos</option>
                            <option value="activo" selected>✅ Activos</option>
                            <option value="inactivo">⏸️ Inactivos</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE TRAMOS MAESTROS -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-bold text-dark">📋 Tramos Modulares y Tarifas Maestras</h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2" id="badgeTotalTramosCat"><?= $totalTramos ?> tramos</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaTramosCatalogo">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Código</th>
                        <th>Trayecto del Tramo</th>
                        <th>Ámbito / País</th>
                        <th>Operador Encargado</th>
                        <th class="text-end">Costo Operador</th>
                        <th class="text-end">PVP Sugerido</th>
                        <th class="text-end">Utilidad Agencia</th>
                        <th class="text-center" style="width: 100px;">Rutas en Uso</th>
                        <th class="text-center" style="width: 90px;">Estado</th>
                        <th class="text-center" style="width: 120px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $banderasPais = [
                        'ECUADOR' => '🇪🇨 Ecuador',
                        'COLOMBIA' => '🇨🇴 Colombia',
                        'VENEZUELA' => '🇻🇪 Venezuela',
                        'INTERNACIONAL' => '🌎 Internacional'
                    ];

                    foreach ($tramosCatalogo ?? [] as $t): 
                        $esActivo = ($t['ESTADO'] ?? 'ACTIVO') === 'ACTIVO';
                        $pais = strtoupper(trim($t['PAIS_TRAMO'] ?? 'INTERNACIONAL'));
                        $costo = floatval($t['COSTO_OPERADOR'] ?? 0);
                        $pvp = floatval($t['PVP_SUGERIDO'] ?? 0);
                        $utilidad = max(0, $pvp - $costo);
                        $margenPct = ($pvp > 0) ? round(($utilidad / $pvp) * 100, 1) : 0;
                        $rutasEnUso = intval($t['TOTAL_RUTAS_USANDO'] ?? 0);
                        $opNombre = !empty($t['OPERADOR_NOMBRE']) ? $t['OPERADOR_NOMBRE'] : 'No asignado';
                        $idOp = intval($t['ID_OPERADOR'] ?? 0);
                        $duracion = floatval($t['TIEMPO_ESTIMADO_HORAS'] ?? 0);

                        $badgeOpClass = 'bg-light text-dark border';
                        $iconoOp = '🏢';
                        if ($idOp === 1) {
                            $badgeOpClass = 'bg-primary text-white border-0';
                            $iconoOp = '🚍';
                        } elseif ($idOp === 2) {
                            $badgeOpClass = 'bg-warning-subtle text-dark border border-warning';
                            $iconoOp = '🚌';
                        } elseif ($idOp === 3) {
                            $badgeOpClass = 'bg-warning-subtle text-dark border border-warning-subtle';
                            $iconoOp = '🇪🇨';
                        } elseif ($idOp === 4) {
                            $badgeOpClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                            $iconoOp = '🇨🇴';
                        } elseif ($idOp === 5) {
                            $badgeOpClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
                            $iconoOp = '🇻🇪';
                        }
                    ?>
                        <tr class="fila-tramo-cat <?= $esActivo ? '' : 'table-secondary opacity-75' ?>"
                            id="fila_tramo_cat_<?= $t['ID_TRAMO_CATALOGO'] ?>"
                            data-pais="<?= htmlspecialchars(mb_strtolower($pais)) ?>"
                            data-operador="<?= htmlspecialchars(mb_strtolower($opNombre)) ?>"
                            data-estado="<?= htmlspecialchars(mb_strtolower($t['ESTADO'] ?? 'activo')) ?>"
                            data-search="<?= strtolower(htmlspecialchars($t['CODIGO'] . ' ' . $t['NOMBRE_TRAMO'] . ' ' . $t['ORIGEN_TRAMO'] . ' ' . $t['DESTINO_TRAMO'] . ' ' . $opNombre . ' ' . $pais)) ?>">
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($t['CODIGO'] ?? ('TC-' . $t['ID_TRAMO_CATALOGO'])) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($t['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($t['DESTINO_TRAMO']) ?></span>
                                    <?php if ($duracion > 0): ?>
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.75rem;" title="Tiempo estimado de viaje">
                                            ⏱️ <?= $duracion ?>h
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= htmlspecialchars($t['NOMBRE_TRAMO']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">
                                    <?= $banderasPais[$pais] ?? htmlspecialchars($pais) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge <?= $badgeOpClass ?> px-2 py-1">
                                        <?= $iconoOp ?> <?= htmlspecialchars($opNombre) ?>
                                    </span>
                                </div>
                                <?php if (!empty($t['ADMITE_SOBREPESO'])): ?>
                                    <small class="text-success d-block mt-1" style="font-size: 0.70rem;">
                                        🧳 Admite sobrepeso (<?= floatval($t['LIMITE_EQUIPAJE_KG'] ?? 35) ?>kg)
                                        <?php if (!empty($t['PRECIO_SOBREPESO_KG'])): ?>
                                            · $<?= number_format(floatval($t['PRECIO_SOBREPESO_KG']), 2) ?>/kg
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-danger fs-6">$<?= number_format($costo, 2) ?></span>
                                <small class="text-muted d-block" style="font-size: 0.70rem;">liquidación</small>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-dark fs-6">$<?= number_format($pvp, 2) ?></span>
                                <small class="text-muted d-block" style="font-size: 0.70rem;">tarifa venta</small>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success fs-6">$<?= number_format($utilidad, 2) ?></span>
                                <small class="text-success d-block fw-bold" style="font-size: 0.70rem;"><?= $margenPct ?>% margen</small>
                            </td>
                            <td class="text-center">
                                <?php if ($rutasEnUso > 0): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2" title="Utilizado en <?= $rutasEnUso ?> rutas comerciales">
                                        <?= $rutasEnUso ?> ruta(s)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border rounded-pill px-2">Disponible</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($esActivo): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick='editarTramoCatalogo(<?= json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Editar Tramo y Tarifas">
                                        ✏️
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="duplicarTramoInverso(<?= $t['ID_TRAMO_CATALOGO'] ?>, '<?= htmlspecialchars(addslashes($t['ORIGEN_TRAMO'] . ' ➔ ' . $t['DESTINO_TRAMO'])) ?>')" title="Duplicar Tramo Inverso (Crear Vuelta)">
                                        🔄
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="eliminarTramoCatalogo(<?= $t['ID_TRAMO_CATALOGO'] ?>, '<?= htmlspecialchars(addslashes($t['ORIGEN_TRAMO'] . ' ➔ ' . $t['DESTINO_TRAMO'])) ?>', <?= $rutasEnUso ?>)" title="Eliminar Tramo">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr id="filaSinTramosCat" style="display: none;">
                        <td colspan="10" class="text-center py-5 text-muted">
                            <div class="fs-2 mb-2">🔍</div>
                            <h6 class="fw-bold text-dark">No se encontraron tramos maestros</h6>
                            <p class="small text-secondary mb-0">No hay coincidencias con los filtros aplicados.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div> <!-- Cierra #contenedorCatalogoTramos -->

<!-- ======================================================== -->
<!-- MODAL: CREAR / EDITAR TRAMO MAESTRO EN CATÁLOGO         -->
<!-- ======================================================== -->
<div class="modal fade" id="modalGuardarTramoCatalogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <form action="/suramerica2030/flota/guardarTramoCatalogo" method="POST" id="formGuardarTramoCatalogo">
                <input type="hidden" name="id_tramo_catalogo" id="tramo_id_catalogo" value="">
                
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="tituloModalTramo">🧩 Nuevo Tramo Modular Maestro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-light border shadow-sm py-2 mb-3 small text-muted">
                        ℹ️ <strong>Tarifas Maestras:</strong> Configura el costo exacto a liquidar al operador responsable y la tarifa sugerida al público. Puedes propagar automáticamente esta actualización a todas las rutas comerciales que utilizan este tramo.
                    </div>

                    <!-- FILA 1: ORIGEN, DESTINO Y PAÍS -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">🛫 Origen del Tramo <span class="text-danger">*</span></label>
                            <input type="text" name="origen_tramo" id="tramo_origen_cat" class="form-control" list="listaCiudadesTramo" placeholder="Ej: Maturín" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">🛬 Destino del Tramo <span class="text-danger">*</span></label>
                            <input type="text" name="destino_tramo" id="tramo_destino_cat" class="form-control" list="listaCiudadesTramo" placeholder="Ej: Caracas" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">🌎 Ámbito / País <span class="text-danger">*</span></label>
                            <select name="pais_tramo" id="tramo_pais_cat" class="form-select" required>
                                <option value="ECUADOR">🇪🇨 Ecuador (Nacional)</option>
                                <option value="COLOMBIA">🇨🇴 Colombia (Nacional)</option>
                                <option value="VENEZUELA">🇻🇪 Venezuela (Nacional)</option>
                                <option value="INTERNACIONAL" selected>🌎 Internacional / Binacional</option>
                            </select>
                        </div>
                    </div>

                    <datalist id="listaCiudadesTramo">
                        <?php foreach ($ciudadesCatalogo ?? [] as $cd): ?>
                            <option value="<?= htmlspecialchars($cd['NOMBRE']) ?>"><?= htmlspecialchars($cd['PAIS']) ?></option>
                        <?php endforeach; ?>
                    </datalist>

                    <!-- FILA 2: NOMBRE DESCRIPTIVO, OPERADOR Y TIEMPO -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">🏷️ Nombre Descriptivo del Tramo</label>
                            <input type="text" name="nombre_tramo" id="tramo_nombre_cat" class="form-control" placeholder="Ej: Maturín ➔ Caracas (Oriente)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">🏢 Operador Responsable <span class="text-danger">*</span></label>
                            <select name="id_operador" id="tramo_operador_cat" class="form-select" required>
                                <option value="">-- Seleccionar Operador --</option>
                                <?php foreach ($operadores ?? [] as $op): ?>
                                    <option value="<?= $op['ID_OPERADOR'] ?>"><?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?> (<?= htmlspecialchars($op['TIPO_OPERADOR']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">⏱️ Horas Estimadas</label>
                            <div class="input-group">
                                <input type="number" step="0.5" min="0" max="99" name="tiempo_estimado_horas" id="tramo_tiempo_cat" class="form-control" placeholder="Ej: 8.5" value="0.0">
                                <span class="input-group-text bg-light text-muted small">hrs</span>
                            </div>
                        </div>
                    </div>

                    <!-- FILA 3: MATRIZ FINANCIERA (COSTO VS PVP) -->
                    <div class="card bg-light border-0 p-3 mb-3">
                        <h6 class="fw-bold text-dark mb-2">💵 Matriz Financiera y Rentabilidad</h6>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-danger">Costo Liquidación Operador ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-danger-subtle text-danger fw-bold">$</span>
                                    <input type="number" step="0.01" min="0" name="costo_operador" id="tramo_costo_cat" class="form-control fw-bold text-danger" placeholder="0.00" required oninput="calcularRentabilidadTramoModal()">
                                </div>
                                <small class="text-muted" style="font-size: 11px;">Monto exacto a pagar al transportista</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-primary">PVP Sugerido al Público ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary-subtle text-primary fw-bold">$</span>
                                    <input type="number" step="0.01" min="0" name="pvp_sugerido" id="tramo_pvp_cat" class="form-control fw-bold text-primary" placeholder="0.00" required oninput="calcularRentabilidadTramoModal()">
                                </div>
                                <small class="text-muted" style="font-size: 11px;">Precio que paga el pasajero</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-success">Utilidad de la Agencia ($)</label>
                                <div class="p-2 bg-white rounded border border-success text-center">
                                    <span class="fs-5 fw-bold text-success" id="lblUtilidadTramoModal">$0.00</span>
                                    <small class="d-block text-muted" id="lblMargenTramoModal" style="font-size: 11px;">0% margen</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FILA 4: POLÍTICAS DE EQUIPAJE Y ESTADO -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">🧳 Admite Sobrepeso</label>
                            <select name="admite_sobrepeso" id="tramo_admite_sp_cat" class="form-select">
                                <option value="1">Sí (Cobro en origen)</option>
                                <option value="0">No amparado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Límite Equipaje (Kg)</label>
                            <input type="number" step="0.1" name="limite_equipaje_kg" id="tramo_limite_eq_cat" class="form-control" value="35.00">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Costo Kg ($)</label>
                            <input type="number" step="0.01" min="0" name="costo_sobrepeso_kg" id="tramo_costo_sp_cat" class="form-control" value="0.00" title="Costo liquidación al operador por kg adicional">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">PVP Kg ($)</label>
                            <input type="number" step="0.01" min="0" name="precio_sobrepeso_kg" id="tramo_precio_sp_cat" class="form-control" value="1.00" title="Precio cobrado al pasajero por kg adicional">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Estado</label>
                            <select name="estado" id="tramo_estado_cat" class="form-select">
                                <option value="ACTIVO">✅ Activo</option>
                                <option value="INACTIVO">⏸️ Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <!-- OPCIÓN DE PROPAGACIÓN INMEDIATA -->
                    <div class="form-check form-switch p-3 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="sincronizar_rutas" value="1" id="chkSincronizarRutasTramo" checked>
                        <label class="form-check-label fw-bold text-dark" for="chkSincronizarRutasTramo">
                            🔄 Propagar y actualizar inmediatamente todas las rutas comerciales vinculadas con estas nuevas tarifas
                        </label>
                        <small class="text-muted d-block ms-4" style="font-size: 11px;">
                            Al marcar esta casilla, el sistema recalculará los precios y costos de todas las rutas comerciales que utilizan este tramo.
                        </small>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold px-4" id="btnGuardarTramoModal">💾 Guardar Tramo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPT DE TRAMOS CATÁLOGO -->
<script>
function abrirModalNuevoTramo() {
    document.getElementById('formGuardarTramoCatalogo').reset();
    document.getElementById('tramo_id_catalogo').value = '';
    document.getElementById('tituloModalTramo').innerHTML = '🧩 Nuevo Tramo Modular Maestro';
    document.getElementById('lblUtilidadTramoModal').innerText = '$0.00';
    document.getElementById('lblMargenTramoModal').innerText = '0% margen';
    if (document.getElementById('tramo_tiempo_cat')) document.getElementById('tramo_tiempo_cat').value = '0.0';
    if (document.getElementById('tramo_costo_sp_cat')) document.getElementById('tramo_costo_sp_cat').value = '0.00';
    if (document.getElementById('tramo_precio_sp_cat')) document.getElementById('tramo_precio_sp_cat').value = '1.00';
    if (document.getElementById('tramo_limite_eq_cat')) document.getElementById('tramo_limite_eq_cat').value = '35.00';
}

function editarTramoCatalogo(t) {
    document.getElementById('tramo_id_catalogo').value = t.ID_TRAMO_CATALOGO;
    document.getElementById('tituloModalTramo').innerHTML = '✏️ Editar Tramo Maestro #' + t.ID_TRAMO_CATALOGO;
    document.getElementById('tramo_origen_cat').value = t.ORIGEN_TRAMO;
    document.getElementById('tramo_destino_cat').value = t.DESTINO_TRAMO;
    document.getElementById('tramo_pais_cat').value = t.PAIS_TRAMO || 'INTERNACIONAL';
    document.getElementById('tramo_nombre_cat').value = t.NOMBRE_TRAMO;
    document.getElementById('tramo_operador_cat').value = t.ID_OPERADOR || '';
    if (document.getElementById('tramo_tiempo_cat')) {
        document.getElementById('tramo_tiempo_cat').value = parseFloat(t.TIEMPO_ESTIMADO_HORAS || 0).toFixed(1);
    }
    document.getElementById('tramo_costo_cat').value = parseFloat(t.COSTO_OPERADOR || 0).toFixed(2);
    document.getElementById('tramo_pvp_cat').value = parseFloat(t.PVP_SUGERIDO || 0).toFixed(2);
    document.getElementById('tramo_admite_sp_cat').value = t.ADMITE_SOBREPESO !== undefined ? t.ADMITE_SOBREPESO : '1';
    document.getElementById('tramo_limite_eq_cat').value = parseFloat(t.LIMITE_EQUIPAJE_KG || 35).toFixed(2);
    if (document.getElementById('tramo_costo_sp_cat')) {
        document.getElementById('tramo_costo_sp_cat').value = parseFloat(t.COSTO_SOBREPESO_KG || 0).toFixed(2);
    }
    if (document.getElementById('tramo_precio_sp_cat')) {
        document.getElementById('tramo_precio_sp_cat').value = parseFloat(t.PRECIO_SOBREPESO_KG || 1).toFixed(2);
    }
    document.getElementById('tramo_estado_cat').value = t.ESTADO || 'ACTIVO';
    
    calcularRentabilidadTramoModal();
    const modal = new bootstrap.Modal(document.getElementById('modalGuardarTramoCatalogo'));
    modal.show();
}

function calcularRentabilidadTramoModal() {
    const costo = parseFloat(document.getElementById('tramo_costo_cat').value) || 0;
    const pvp = parseFloat(document.getElementById('tramo_pvp_cat').value) || 0;
    const util = Math.max(0, pvp - costo);
    const margenPct = (pvp > 0) ? ((util / pvp) * 100).toFixed(1) : '0';

    document.getElementById('lblUtilidadTramoModal').innerText = '$' + util.toFixed(2);
    document.getElementById('lblMargenTramoModal').innerText = margenPct + '% margen';

    const btn = document.getElementById('btnGuardarTramoModal');
    if (pvp > 0 && costo > pvp) {
        btn.disabled = true;
        btn.title = 'No se puede guardar un tramo con costo mayor al PVP';
    } else {
        btn.disabled = false;
        btn.title = '';
    }
}

function duplicarTramoInverso(id, nombre) {
    let msg = `¿Deseas duplicar el tramo "${nombre}" en sentido inverso (crear viaje de vuelta)?\n\nSe creará el tramo opuesto con el mismo operador, tarifas y duración estimada.`;
    if (confirm(msg)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/suramerica2030/flota/duplicarTramoInverso';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_tramo_catalogo';
        input.value = id;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function eliminarTramoCatalogo(id, nombre, rutasUso) {
    let msg = `¿Estás seguro de eliminar el tramo "${nombre}"?`;
    if (rutasUso > 0) {
        msg = `⚠️ El tramo "${nombre}" está asociado a ${rutasUso} ruta(s) comercial(es).\n\nPara proteger el historial, se marcará como INACTIVO en lugar de borrarse físicamente.\n¿Deseas continuar?`;
    }
    if (confirm(msg)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/suramerica2030/flota/eliminarTramoCatalogo';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_tramo_catalogo';
        input.value = id;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Filtros en vivo para la tabla de tramos catálogo
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscadorTramosCat');
    const filtroPais = document.getElementById('filtroPaisTramos');
    const filtroOp = document.getElementById('filtroOperadorTramos');
    const filtroEstado = document.getElementById('filtroEstadoTramos');

    function aplicarFiltrosTramos() {
        const query = (buscador ? buscador.value : '').toLowerCase().trim();
        const pais = (filtroPais ? filtroPais.value : '').toLowerCase().trim();
        const op = (filtroOp ? filtroOp.value : '').toLowerCase().trim();
        const estado = (filtroEstado ? filtroEstado.value : '').toLowerCase().trim();

        const filas = document.querySelectorAll('.fila-tramo-cat');
        let visibles = 0;

        filas.forEach(f => {
            const fSearch = f.getAttribute('data-search') || '';
            const fPais = f.getAttribute('data-pais') || '';
            const fOp = f.getAttribute('data-operador') || '';
            const fEstado = f.getAttribute('data-estado') || '';

            const matchQuery = !query || fSearch.includes(query);
            const matchPais = !pais || fPais === pais;
            const matchOp = !op || fOp.includes(op);
            const matchEstado = !estado || fEstado === estado;

            if (matchQuery && matchPais && matchOp && matchEstado) {
                f.style.display = '';
                visibles++;
            } else {
                f.style.display = 'none';
            }
        });

        const filaSin = document.getElementById('filaSinTramosCat');
        if (filaSin) {
            filaSin.style.display = (visibles === 0) ? '' : 'none';
        }
        const badge = document.getElementById('badgeTotalTramosCat');
        if (badge) {
            badge.innerText = visibles + ' tramos filtrados';
        }
    }

    if (buscador) buscador.addEventListener('input', aplicarFiltrosTramos);
    if (filtroPais) filtroPais.addEventListener('change', aplicarFiltrosTramos);
    if (filtroOp) filtroOp.addEventListener('change', aplicarFiltrosTramos);
    if (filtroEstado) filtroEstado.addEventListener('change', aplicarFiltrosTramos);

    window.limpiarBuscadorTramos = function() {
        if (buscador) buscador.value = '';
        if (filtroPais) filtroPais.value = '';
        if (filtroOp) filtroOp.value = '';
        if (filtroEstado) filtroEstado.value = 'activo';
        aplicarFiltrosTramos();
    };

    // Auto-generar nombre descriptivo al escribir origen y destino
    const origInput = document.getElementById('tramo_origen_cat');
    const destInput = document.getElementById('tramo_destino_cat');
    const nomInput = document.getElementById('tramo_nombre_cat');
    function autoCompletarNombre() {
        if (origInput && destInput && nomInput && !nomInput.getAttribute('data-manual')) {
            const o = origInput.value.trim();
            const d = destInput.value.trim();
            if (o && d) {
                nomInput.value = `${o} ➔ ${d}`;
            }
        }
    }
    if (origInput) origInput.addEventListener('input', autoCompletarNombre);
    if (destInput) destInput.addEventListener('input', autoCompletarNombre);
    if (nomInput) nomInput.addEventListener('focus', function() { this.setAttribute('data-manual', '1'); });
});
</script>
