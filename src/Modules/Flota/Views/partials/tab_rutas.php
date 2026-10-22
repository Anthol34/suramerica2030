    <!-- ======================================================== -->
    <!-- PESTAÑA 2: RUTAS COMERCIALES Y MATRIZ DE TARIFAS        -->
    <!-- ======================================================== -->
    <div class="tab-pane fade <?= ($tabActiva == 'rutas') ? 'show active' : '' ?>" id="tab-rutas">
        
        <!-- BANNER DE GENERACIÓN AUTOMÁTICA -->
        <div class="card border-0 shadow-sm bg-gradient mb-4" style="background: linear-gradient(135deg, #e3f2fd 0%, #f1f8e9 100%); border-left: 5px solid #0d6efd !important;">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center py-3">
                <div class="mb-2 mb-md-0">
                    <h5 class="fw-bold text-primary mb-1">⚡ Generador Inteligente de Rutas Comerciales</h5>
                    <p class="text-muted small mb-0">Calcula automáticamente todas las combinaciones comerciales válidas, detecta tramos de transbordo fronterizo y aplica tarifas sugeridas sin borrar precios personalizados.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearRutaRapida" onclick="abrirModalRutaRapida()">
                        ⚡ Crear Ruta en 3 Clics
                    </button>
                    <button class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevaRutaManual">
                        + Nueva Ruta Manual
                    </button>
                    <form action="/suramerica2030/flota/generarRutasCorredor" method="POST" onsubmit="return confirm('¿Deseas generar o sincronizar todas las combinaciones comerciales del corredor seleccionado? Los precios que ya hayas personalizado se respetarán.');">
                        <input type="hidden" name="id_corredor" id="generador_id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?? 1 ?>">
                        <button type="submit" class="btn btn-outline-success fw-bold shadow-sm" id="btnGenerarRutas" title="Generar combinaciones del corredor">
                            ⚙️ Generar del Corredor
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php
        // Extracción de datos analíticos para filtros multidimensionales de Rutas Comerciales
        $origenesRutas = array_values(array_unique(array_filter(array_map('trim', array_column($rutas, 'RUT_ORIGEN')))));
        sort($origenesRutas);

        $destinosRutas = array_values(array_unique(array_filter(array_map('trim', array_column($rutas, 'RUT_DESTINO')))));
        sort($destinosRutas);

        $totalRutasCat = count($rutas);
        $rutasDirectas = 0;
        $rutasConexion = 0;
        $rutasActivas = 0;
        $rutasInactivas = 0;

        foreach ($rutas as $r) {
            $tipoV = $r['TIPO_VIAJE'] ?? 'DIRECTO';
            if ($tipoV === 'CONEXION') {
                $rutasConexion++;
            } else {
                $rutasDirectas++;
            }

            if (($r['ESTADO'] ?? 'ACTIVO') === 'ACTIVO') {
                $rutasActivas++;
            } else {
                $rutasInactivas++;
            }
        }

        // Extraer Operadores Reales Únicos (desde tabla OPERADOR y RUTA)
        $operadoresUnicos = [];
        foreach ($rutas as $r) {
            $nomOp = !empty($r['OPERADOR_NOMBRE']) ? trim($r['OPERADOR_NOMBRE']) : 'Suramérica 2030';
            $operadoresUnicos[$nomOp] = $nomOp;
        }
        foreach ($operadores as $op) {
            if (!empty($op['NOMBRE_COMERCIAL'])) {
                $operadoresUnicos[trim($op['NOMBRE_COMERCIAL'])] = trim($op['NOMBRE_COMERCIAL']);
            }
        }
        ksort($operadoresUnicos);

        // Extraer Modalidades Reales Únicas
        $modalidadesUnicas = array_values(array_unique(array_filter(array_map('trim', array_column($rutas, 'MODALIDAD')))));
        if (!in_array('ESTANDAR', $modalidadesUnicas)) $modalidadesUnicas[] = 'ESTANDAR';
        if (!in_array('PANAMERICANA', $modalidadesUnicas)) $modalidadesUnicas[] = 'PANAMERICANA';
        if (!in_array('CONEXION_TRAMOS', $modalidadesUnicas)) $modalidadesUnicas[] = 'CONEXION_TRAMOS';
        sort($modalidadesUnicas);
        ?>

        <!-- TARJETAS DE RESUMEN Y FILTRADO RÁPIDO (KPIS) -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border shadow-sm h-100 p-2 tarjeta-filtro-rapido-ruta active text-center" data-filtro-kpi="todos" style="cursor: pointer; transition: all 0.2s;" title="Clic para ver todas las rutas">
                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Total Rutas</small>
                    <span class="fs-4 fw-bold text-dark" id="kpiTotalRutasCat"><?= $totalRutasCat ?></span>
                    <small class="text-secondary d-block" style="font-size: 11px;">Catálogo completo</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border shadow-sm h-100 p-2 tarjeta-filtro-rapido-ruta text-center" data-filtro-kpi="directo" style="cursor: pointer; transition: all 0.2s;" title="Clic para filtrar rutas directas">
                    <small class="text-success fw-bold text-uppercase d-block" style="font-size: 10px;">➡️ Rutas Directas</small>
                    <span class="fs-4 fw-bold text-success" id="kpiRutasDirectas"><?= $rutasDirectas ?></span>
                    <small class="text-muted d-block" style="font-size: 11px;">Operación directa</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border shadow-sm h-100 p-2 tarjeta-filtro-rapido-ruta text-center" data-filtro-kpi="conexion" style="cursor: pointer; transition: all 0.2s;" title="Clic para filtrar rutas con conexiones">
                    <small class="text-primary fw-bold text-uppercase d-block" style="font-size: 10px;">🔄 Con Conexión</small>
                    <span class="fs-4 fw-bold text-primary" id="kpiRutasConexion"><?= $rutasConexion ?></span>
                    <small class="text-muted d-block" style="font-size: 11px;">Multitramo / Transbordos</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border shadow-sm h-100 p-2 tarjeta-filtro-rapido-ruta text-center" data-filtro-kpi="activas" style="cursor: pointer; transition: all 0.2s;" title="Clic para filtrar rutas activas en taquilla">
                    <small class="text-info text-dark fw-bold text-uppercase d-block" style="font-size: 10px;">Activas en Venta</small>
                    <span class="fs-4 fw-bold text-info text-dark" id="kpiRutasActivas"><?= $rutasActivas ?></span>
                    <small class="text-muted d-block" style="font-size: 11px;">Disponibles para venta</small>
                </div>
            </div>
        </div>

        <!-- PANEL DE FILTROS AVANZADOS MULTIDIMENSIONALES -->
        <div class="card shadow-sm border-0 mb-3 bg-light">
            <div class="card-body p-3">
                <!-- FILA 1: BUSCADOR Y LOCALIDADES -->
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                            <input type="text" id="buscadorRutas" class="form-control bg-white border-start-0 ps-0" placeholder="Buscar por ciudad, código o ID..." autocomplete="off">
                            <button class="btn btn-white bg-white border border-start-0" type="button" onclick="limpiarBuscadorRutas()" title="Limpiar búsqueda">✕</button>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🛫 Origen:</span>
                            <select id="filtroOrigenRutas" class="form-select bg-white">
                                <option value="">Todos los Orígenes</option>
                                <?php foreach($origenesRutas as $ori): ?>
                                    <option value="<?= htmlspecialchars(mb_strtolower($ori)) ?>"><?= htmlspecialchars($ori) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🛬 Destino:</span>
                            <select id="filtroDestinoRutas" class="form-select bg-white">
                                <option value="">Todos los Destinos</option>
                                <?php foreach($destinosRutas as $des): ?>
                                    <option value="<?= htmlspecialchars(mb_strtolower($des)) ?>"><?= htmlspecialchars($des) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- FILA 2: OPERADOR, MODALIDAD, TIPO DE VIAJE, ESTADO Y CORREDOR -->
                <div class="row g-2 align-items-center">
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🏢 Operador:</span>
                            <select id="filtroOperadorRutas" class="form-select bg-white">
                                <option value="">Todos los Operadores</option>
                                <?php foreach($operadoresUnicos as $op): ?>
                                    <option value="<?= htmlspecialchars(mb_strtolower($op)) ?>"><?= htmlspecialchars($op) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🔀 Modalidad:</span>
                            <select id="filtroModalidadRutas" class="form-select bg-white">
                                <option value="">Todas las Modalidades</option>
                                <?php foreach($modalidadesUnicas as $mod): ?>
                                    <option value="<?= htmlspecialchars(mb_strtolower($mod)) ?>">
                                        <?= htmlspecialchars(\Platform\Helpers\ModalidadHelper::formatearEtiqueta($mod)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🚌 Viaje:</span>
                            <select id="filtroTipoViajeRutas" class="form-select bg-white">
                                <option value="">Todos</option>
                                <option value="directo">➡️ Directo</option>
                                <option value="conexion">🔄 Conexión</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🔘 Estado:</span>
                            <select id="filtroEstadoRutas" class="form-select bg-white">
                                <option value="">Todos</option>
                                <option value="activo">✅ Activas</option>
                                <option value="inactivo">⏸️ Inactivas</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🛤️ Corredor:</span>
                            <select id="filtroCorredorRutas" class="form-select bg-white">
                                <option value="">Todos</option>
                                <?php foreach($corredores as $cor): 
                                    $cant = $rutasPorCorredor[$cor['ID_CORREDOR']] ?? 0;
                                ?>
                                    <option value="<?= $cor['ID_CORREDOR'] ?>">
                                        <?= htmlspecialchars($cor['CODIGO']) ?> (<?= $cant ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- FILA 3: ESTADO, RESET Y SELECTOR DE FILAS -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary" id="badgeFiltrosActivosRutas">Filtros: Todos</span>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 fw-bold" id="btnLimpiarTodosFiltrosRutas" style="font-size: 11px;">
                            ↺ Restablecer Filtros
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <label for="filtroLimiteRutas" class="small text-muted fw-bold text-nowrap mb-0" style="font-size: 12px;">Mostrar:</label>
                        <select id="filtroLimiteRutas" class="form-select form-select-sm" style="width: auto; font-size: 12px;">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="all">Todas</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA DE TARIFAS -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-dark">📋 Catálogo de Tarifas y Rutas Comerciales</h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2" id="badgeTotalRutas"><?= count($rutas) ?> rutas</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaRutas">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Origen ➔ Destino</th>
                            <th>Operador / Modalidad</th>
                            <th>Días y Horarios</th>
                            <th class="text-end">Costo Operadores</th>
                            <th class="text-end">Tarifa Pasaje (PVP)</th>
                            <th class="text-end">Ganancia Agencia</th>
                            <th class="text-end">Encomienda</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 140px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rutas as $r): 
                            $diasArr = explode(',', $r['DIAS_SALIDA'] ?? '');
                            $diasTexto = [];
                            foreach($diasArr as $d) { if(isset($mapaDias[$d])) $diasTexto[] = $mapaDias[$d]; }
                            $strDias = empty($diasTexto) ? 'Sin definir' : implode(', ', $diasTexto);
                            $esActivo = ($r['ESTADO'] ?? 'ACTIVO') === 'ACTIVO';

                            $mod = $r['MODALIDAD'] ?? 'ESTANDAR';
                            $esConexion = ($r['TIPO_VIAJE'] ?? '') === 'CONEXION';
                            $operadorDirecto = !empty($r['OPERADOR_NOMBRE']) ? $r['OPERADOR_NOMBRE'] : 'Suramérica 2030 (Flota Propia)';
                            $numTramos = count($r['TRAMOS_DETALLE'] ?? []);
                            $admiteSp = isset($r['ADMITE_SOBREPESO']) ? intval($r['ADMITE_SOBREPESO']) : 1;

                            // Identificar operadores de los tramos si es conexión
                            $opsTramos = [];
                            if ($esConexion && !empty($r['TRAMOS_DETALLE'])) {
                                foreach ($r['TRAMOS_DETALLE'] as $tr) {
                                    $opItem = trim($tr['OPERADOR_TRAMO_NOMBRE'] ?? $tr['operador'] ?? $tr['OPERADOR'] ?? '');
                                    if (!empty($opItem) && !in_array($opItem, $opsTramos)) {
                                        $opsTramos[] = $opItem;
                                    }
                                }
                            }

                            if ($esConexion && !empty($opsTramos)) {
                                if (count($opsTramos) === 1) {
                                    $operadorMostrar = $opsTramos[0];
                                    $tituloOperador = "Operador del Servicio: " . $operadorMostrar;
                                } else {
                                    $todosLocales = true;
                                    foreach ($opsTramos as $opT) {
                                        if (stripos($opT, 'LOCAL') === false && stripos($opT, 'EMPRESA') === false) {
                                            $todosLocales = false;
                                        }
                                    }
                                    if ($todosLocales) {
                                        $operadorMostrar = "Empresas Locales";
                                    } else {
                                        $operadorMostrar = implode(' / ', $opsTramos);
                                    }
                                    $tituloOperador = "Operadores en Conexión: " . implode(' + ', $opsTramos);
                                }
                            } else {
                                $operadorMostrar = $operadorDirecto;
                                $tituloOperador = "Operador Principal: " . $operadorMostrar;
                            }
                        ?>
                            <tr class="fila-ruta <?= $esActivo ? '' : 'table-secondary opacity-75' ?>" 
                                id="fila_ruta_<?= $r['ID_RUTA'] ?>"
                                data-corredor="<?= $r['ID_CORREDOR'] ?? '' ?>" 
                                data-origen="<?= htmlspecialchars(mb_strtolower(trim($r['RUT_ORIGEN']))) ?>" 
                                data-destino="<?= htmlspecialchars(mb_strtolower(trim($r['RUT_DESTINO']))) ?>" 
                                data-operador="<?= htmlspecialchars(mb_strtolower(trim($operadorMostrar))) ?>" 
                                data-id-operador="<?= $r['ID_OPERADOR'] ?? '' ?>"
                                data-modalidad="<?= htmlspecialchars(mb_strtolower(trim($r['MODALIDAD'] ?? 'estandar'))) ?>" 
                                data-tipo-viaje="<?= htmlspecialchars(mb_strtolower($r['TIPO_VIAJE'] ?? 'directo')) ?>" 
                                data-estado="<?= htmlspecialchars(mb_strtolower($r['ESTADO'] ?? 'activo')) ?>" 
                                data-search="<?= strtolower(htmlspecialchars($r['RUT_ORIGEN'] . ' ' . $r['RUT_DESTINO'] . ' ' . ($r['NOMBRE_CORREDOR'] ?? '') . ' #' . $r['ID_RUTA'] . ' ' . $operadorMostrar . ' ' . implode(' ', $opsTramos) . ' ' . ($r['MODALIDAD'] ?? ''))) ?>">
                                <td><span class="badge bg-secondary">#<?= $r['ID_RUTA'] ?></span></td>
                                <td class="col-ruta-trayecto">
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($r['RUT_ORIGEN']) ?> ➔ <?= htmlspecialchars($r['RUT_DESTINO']) ?></div>
                                    <?php if(!empty($r['NOMBRE_CORREDOR'])): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-primary border" style="font-size: 0.75rem; font-weight: 500;">
                                                🛤️ <?= htmlspecialchars($r['NOMBRE_CORREDOR']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="col-ruta-operador" data-search="<?= strtolower(htmlspecialchars($operadorMostrar . ' ' . implode(' ', $opsTramos) . ' ' . ($r['MODALIDAD'] ?? '') . ' ' . ($r['TIPO_VIAJE'] ?? ''))) ?>">
                                    <div class="d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <?= \Platform\Helpers\ModalidadHelper::renderBadge($mod, $numTramos, $operadorMostrar) ?>
                                            <span class="badge bg-light text-dark border text-truncate" style="max-width: 175px;" title="<?= htmlspecialchars($tituloOperador) ?>">🏢 <?= htmlspecialchars($operadorMostrar) ?></span>
                                        </div>
                                        <?php if(!$esConexion): ?>
                                            <?php if($admiteSp): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle" title="Sobrepeso amparado y cobrado en Ecuador" style="font-size: 0.70rem; width: fit-content;">🧳 Sobrepeso: SÍ</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle" title="Sin cobro de sobrepeso en oficina" style="font-size: 0.70rem; width: fit-content;">⚠️ Sobrepeso: NO</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(!empty($r['TRAMOS_DETALLE'])): ?>
                                        <div class="mt-1 small" style="font-size: 0.72rem; line-height: 1.15;">
                                            <?php foreach($r['TRAMOS_DETALLE'] as $trIdx => $trItem): ?>
                                                <div class="text-secondary text-truncate" style="max-width: 175px;" title="<?= htmlspecialchars($trItem['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($trItem['DESTINO_TRAMO']) ?> (<?= htmlspecialchars($trItem['OPERADOR']) ?>)">
                                                    <?= $trIdx + 1 ?>. <?= htmlspecialchars($trItem['ORIGEN_TRAMO']) ?>➔<?= htmlspecialchars($trItem['DESTINO_TRAMO']) ?>
                                                    <?php if(!empty($trItem['ADMITE_SOBREPESO'])): ?>
                                                        <span title="Sobrepeso amparado en Ecuador (<?= htmlspecialchars($trItem['OPERADOR']) ?>)" class="text-success fw-bold">🧳</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="col-ruta-horarios">
                                    <small class="d-block text-muted"><?= htmlspecialchars($strDias) ?></small>
                                    <small class="text-primary fw-bold">⏰ <?= $r['RUT_SAHORA'] ?></small>
                                </td>
                                <td class="col-ruta-costo text-end">
                                    <span class="fs-6 fw-bold text-danger">$<?= number_format($r['COSTO_CALCULADO'] ?? $r['COSTO_TOTAL_RUTA'] ?? 0, 2) ?></span>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">liquidación</small>
                                </td>
                                <td class="col-ruta-tarifa-pasaje text-end">
                                    <span class="fs-6 fw-bold text-primary">$<?= number_format($r['PRECIO_PASAJE'], 2) ?></span>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">precio viajero</small>
                                </td>
                                <td class="col-ruta-ganancia text-end">
                                    <span class="fs-6 fw-bold text-success">$<?= number_format($r['UTILIDAD_CALCULADA'] ?? $r['UTILIDAD_PASAJE'] ?? 0, 2) ?></span>
                                    <small class="text-success d-block fw-bold" style="font-size: 0.68rem;"><?= $r['MARGEN_PCT'] ?? 0 ?>% margen</small>
                                </td>
                                <td class="col-ruta-tarifa-encomienda text-end">
                                    <span class="fs-6 fw-bold text-dark">$<?= number_format($r['PRECIO_ENCOMIENDA_KG'], 2) ?></span>
                                </td>
                                <td class="col-ruta-estado text-center">
                                    <?php if($esActivo): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <!-- EDICION RAPIDA DE TARIFA -->
                                        <button class="btn btn-sm btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalTarifaRapida<?= $r['ID_RUTA'] ?>" title="Editar Precio Rápido">
                                            💲
                                        </button>
                                        <!-- EDICION COMPLETA -->
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarRuta<?= $r['ID_RUTA'] ?>" title="Configuración Avanzada">
                                            ✏️
                                        </button>
                                        <!-- ELIMINAR -->
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarRuta<?= $r['ID_RUTA'] ?>" title="Eliminar Ruta">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- FILA SIN RESULTADOS -->
                        <tr id="filaSinRutas" style="display: none;">
                            <td colspan="10" class="text-center py-5 text-muted">
                                <div class="fs-2 mb-2">🔍</div>
                                <h6 class="fw-bold text-dark">No se encontraron rutas comerciales</h6>
                                <p class="small text-secondary mb-0">No hay coincidencias con los filtros de corredor y búsqueda seleccionados.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- FOOTER DE PAGINACIÓN -->
            <div class="card-footer bg-white py-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span id="infoPaginacionRutas" class="text-muted small fw-medium">Cargando rutas...</span>
                </div>
                <nav aria-label="Paginación de Rutas">
                    <ul class="pagination pagination-sm mb-0 justify-content-center" id="listaPaginacionRutas">
                        <!-- Botones de paginación generados por JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODALES DE RUTAS COMERCIALES (FUERA DE LA TABLA)         -->
        <!-- ======================================================== -->
        <?php foreach($rutas as $r): 
            $esActivo = ($r['ESTADO'] ?? 'ACTIVO') === 'ACTIVO';
            $diasArr = explode(',', $r['DIAS_SALIDA'] ?? '');
        ?>
            <!-- MODAL EDICION RAPIDA DE TARIFA -->
            <div class="modal fade text-start" id="modalTarifaRapida<?= $r['ID_RUTA'] ?>" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h6 class="modal-title fw-bold">Tarifas: <?= htmlspecialchars($r['RUT_ORIGEN']) ?> ➔ <?= htmlspecialchars($r['RUT_DESTINO']) ?></h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form class="form-tarifa-rapida-ajax" action="/suramerica2030/flota/guardarTarifaRapida" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_ruta" value="<?= $r['ID_RUTA'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Precio Pasaje ($)</label>
                                    <input type="number" step="0.01" name="precio_pasaje" class="form-control form-control-lg text-end fw-bold" value="<?= $r['PRECIO_PASAJE'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Precio Encomienda/Kg ($)</label>
                                    <input type="number" step="0.01" name="precio_encomienda" class="form-control form-control-lg text-end fw-bold" value="<?= $r['PRECIO_ENCOMIENDA_KG'] ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-bold">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="ACTIVO" <?= $esActivo ? 'selected' : '' ?>>Activo (Disponible para venta)</option>
                                        <option value="INACTIVO" <?= !$esActivo ? 'selected' : '' ?>>Inactivo (Ocultar en ventas)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer bg-light p-2">
                                <button type="submit" class="btn btn-success w-100 fw-bold">Actualizar Tarifa</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL CONFIGURACION AVANZADA RUTA -->
            <div class="modal fade text-start" id="modalEditarRuta<?= $r['ID_RUTA'] ?>" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <form class="modal-content form-editar-ruta-ajax" action="/suramerica2030/flota/editarRuta" method="POST" id="form_edit_ruta_<?= $r['ID_RUTA'] ?>">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                ⚙️ Configuración de Ruta #<?= $r['ID_RUTA'] ?>: <?= htmlspecialchars($r['RUT_ORIGEN']) ?> ➔ <?= htmlspecialchars($r['RUT_DESTINO']) ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="overflow-y: auto; max-height: calc(100vh - 200px);">
                                <input type="hidden" name="id_ruta" value="<?= $r['ID_RUTA'] ?>">
                                
                                <!-- BLOQUE 1: DATOS OPERATIVOS Y MODALIDAD -->
                                <div class="bg-light p-3 rounded border mb-3">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Corredor Asociado</label>
                                            <select name="id_corredor" id="edit_corredor_<?= $r['ID_RUTA'] ?>" class="form-select border-primary fw-bold" onchange="alCambiarCorredorEnRuta('<?= $r['ID_RUTA'] ?>')">
                                                <?php foreach($corredores as $cor): ?>
                                                    <option value="<?= $cor['ID_CORREDOR'] ?>" <?= (($r['ID_CORREDOR'] ?? 0) == $cor['ID_CORREDOR']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cor['CODIGO'] . ' - ' . $cor['NOMBRE']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Origen Cabecera</label>
                                            <select name="origen" id="edit_origen_<?= $r['ID_RUTA'] ?>" class="form-select select-origen-ruta border-primary fw-bold" required onchange="alCambiarCabecerasRuta('<?= $r['ID_RUTA'] ?>')">
                                                <?= $generarOpcionesCiudades($r['ID_CORREDOR'] ?? ($corredores[0]['ID_CORREDOR'] ?? 0), $r['RUT_ORIGEN'], '-- Seleccione Origen --') ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Destino Terminal</label>
                                            <select name="destino" id="edit_destino_<?= $r['ID_RUTA'] ?>" class="form-select select-destino-ruta border-primary fw-bold" required onchange="alCambiarCabecerasRuta('<?= $r['ID_RUTA'] ?>')">
                                                <?= $generarOpcionesCiudades($r['ID_CORREDOR'] ?? ($corredores[0]['ID_CORREDOR'] ?? 0), $r['RUT_DESTINO'], '-- Seleccione Destino --') ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-2 mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label text-primary fw-bold">Tipo de Itinerario</label>
                                            <select name="tipo_viaje" id="edit_tipo_viaje_<?= $r['ID_RUTA'] ?>" class="form-select border-primary fw-bold" onchange="toggleTramos(this.value, 'tramos_edit_<?= $r['ID_RUTA'] ?>'); condicionarModalidadAutomatica('<?= $r['ID_RUTA'] ?>');">
                                                <option value="DIRECTO" <?= ($r['TIPO_VIAJE'] ?? '') === 'DIRECTO' ? 'selected' : '' ?>>➡️ Viaje Directo (Un solo operador / bus)</option>
                                                <option value="CONEXION" <?= ($r['TIPO_VIAJE'] ?? '') === 'CONEXION' ? 'selected' : '' ?>>🔄 Con Conexiones (Tramos Operativos y Transbordos)</option>
                                            </select>
                                            <small class="text-muted">Si es directo o conexión con transbordos.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-primary fw-bold">🔀 Modalidad Logística del Servicio</label>
                                            <?php 
                                                $currMod = $r['MODALIDAD'] ?? 'ESTANDAR';
                                                $esPreset = in_array($currMod, ['ESTANDAR', 'PANAMERICANA', 'CONEXION_TRAMOS', 'Conexión Multitramo', 'AUTO']);
                                            ?>
                                            <select name="modalidad" id="modalidad_select_edit_<?= $r['ID_RUTA'] ?>" class="form-select border-info fw-bold text-dark" onchange="alCambiarModalidadSelect(this, '<?= $r['ID_RUTA'] ?>')">
                                                <option value="AUTO">🤖 Automático (según Operador y Tramos)</option>
                                                <option value="PANAMERICANA" <?= $currMod === 'PANAMERICANA' ? 'selected' : '' ?>>⭐ Vía Panamericana</option>
                                                <option value="CONEXION_TRAMOS" <?= ($currMod === 'CONEXION_TRAMOS' || $currMod === 'Conexión Multitramo') ? 'selected' : '' ?>>🔀 Conexión por Tramos</option>
                                                <option value="ESTANDAR" <?= $currMod === 'ESTANDAR' ? 'selected' : '' ?>>➡️ Directo Estándar</option>
                                                <option value="OTRO" <?= !$esPreset ? 'selected' : '' ?>>✏️ Nombre Personalizado...</option>
                                            </select>
                                            <div id="caja_modalidad_personalizada_<?= $r['ID_RUTA'] ?>" class="<?= !$esPreset ? '' : 'd-none' ?> mt-1">
                                                <input type="text" name="modalidad_personalizada" class="form-control form-control-sm border-info" placeholder="Ej: Ruta Expresa Internacional" value="<?= htmlspecialchars(!$esPreset ? $currMod : '') ?>">
                                            </div>
                                            <small class="text-muted" style="font-size:11px;">Nombre con el que se identificará este trayecto en Taquilla y Finanzas.</small>
                                        </div>
                                    </div>

                                    <!-- BLOQUE VIAJE DIRECTO: OPERADOR Y SOBREPESO -->
                                    <div id="opciones_directo_edit_<?= $r['ID_RUTA'] ?>" class="<?= ($r['TIPO_VIAJE'] ?? 'DIRECTO') === 'CONEXION' ? 'd-none' : '' ?> bg-white p-2 rounded border mt-2">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-6">
                                                <label class="form-label small text-dark mb-0 fw-bold">🏢 Operador de la Ruta Directa</label>
                                                <select name="id_operador" id="edit_id_operador_<?= $r['ID_RUTA'] ?>" class="form-select form-select-sm fw-bold border-primary text-primary" <?= ($r['TIPO_VIAJE'] ?? 'DIRECTO') === 'CONEXION' ? 'disabled' : '' ?> onchange="const nom = this.options[this.selectedIndex].getAttribute('data-nombre'); const hid = this.form.querySelector('input[name=operador]'); if(hid) hid.value = nom; condicionarModalidadPorOperador(this.value, '<?= $r['ID_RUTA'] ?>');">
                                                     <?= $generarOpcionesOperadores($r['ID_OPERADOR'] ?? 1) ?>
                                                 </select>
                                                 <input type="hidden" name="operador" value="<?= htmlspecialchars(!empty($r['OPERADOR_NOMBRE']) ? $r['OPERADOR_NOMBRE'] : 'Suramérica 2030 (Flota Propia)') ?>" <?= ($r['TIPO_VIAJE'] ?? 'DIRECTO') === 'CONEXION' ? 'disabled' : '' ?>>
                                                <small class="text-muted" style="font-size:11px;">Empresa o socio responsable de la operación directa.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-dark mb-0 fw-bold">⚖️ ¿Cobrar Sobrepeso en Ecuador?</label>
                                                <select name="admite_sobrepeso" class="form-select form-select-sm fw-bold">
                                                    <option value="1" <?= (!isset($r['ADMITE_SOBREPESO']) || $r['ADMITE_SOBREPESO'] == 1) ? 'selected' : '' ?>>✅ Sí (Cobro activo en Taquilla)</option>
                                                    <option value="0" <?= (isset($r['ADMITE_SOBREPESO']) && $r['ADMITE_SOBREPESO'] == 0) ? 'selected' : '' ?>>❌ No cobrar sobrepeso (Sin cobro en oficina)</option>
                                                </select>
                                                <small class="text-muted" style="font-size:11px;">Habilita o inhabilita el módulo de sobrepeso para esta ruta.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- BLOQUE 2: TRAMOS DE CONEXIÓN Y FINANZAS -->
                                <div id="tramos_edit_<?= $r['ID_RUTA'] ?>" class="<?= ($r['TIPO_VIAJE'] ?? '') === 'CONEXION' ? '' : 'd-none' ?> border rounded p-3 mb-3" style="background-color: #f8fafc;">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                        <div>
                                            <h6 class="text-dark fw-bold mb-0">🛤️ Tramos Operativos, Operadores y Márgenes de Utilidad</h6>
                                            <small class="text-muted">Configure cada parada, el socio operador responsable, el costo para la agencia y los permisos de sobrepeso.</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold" onclick="addTramo('lista_tramos_edit_<?= $r['ID_RUTA'] ?>', '<?= $r['ID_RUTA'] ?>')">
                                            + Añadir Tramo
                                        </button>
                                    </div>

                                    <div id="lista_tramos_edit_<?= $r['ID_RUTA'] ?>">
                                        <?php 
                                        $tramosList = $r['TRAMOS_DETALLE'] ?? [];
                                        if (empty($tramosList)) {
                                            $tramosList = json_decode($r['TRAMOS_JSON'] ?? '[]', true) ?: [];
                                        }
                                        if(!empty($tramosList)): 
                                            foreach($tramosList as $trIndex => $tramo):
                                                $tOrigen = $tramo['origen'] ?? $tramo['ORIGEN_TRAMO'] ?? '';
                                                $tDestino = $tramo['destino'] ?? $tramo['DESTINO_TRAMO'] ?? '';
                                                $tOperador = $tramo['operador'] ?? $tramo['OPERADOR'] ?? 'Propio';
                                                $tPvp = floatval($tramo['pvp'] ?? $tramo['PVP_TRAMO'] ?? 0);
                                                $tCosto = floatval($tramo['costo'] ?? $tramo['COSTO_OPERADOR'] ?? 0);
                                                $tUtilidad = floatval($tramo['utilidad'] ?? $tramo['UTILIDAD_AGENCIA'] ?? max(0, $tPvp - $tCosto));
                                                $tAdmiteSp = !empty($tramo['admite_sobrepeso'] ?? $tramo['ADMITE_SOBREPESO'] ?? 0);
                                                $tLimiteEq = floatval($tramo['limite_equipaje'] ?? $tramo['LIMITE_EQUIPAJE_KG'] ?? 35);
                                                $tPrecioSp = floatval($tramo['precio_sobrepeso'] ?? $tramo['PRECIO_SOBREPESO_KG'] ?? 1);
                                        ?>
                                            <div class="card mb-2 border-secondary shadow-sm tramo-card" data-ruta-id="<?= $r['ID_RUTA'] ?>">
                                                <div class="card-header bg-white py-1 px-2 d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-primary small tramo-title">📍 Tramo <?= $trIndex + 1 ?>: <?= htmlspecialchars($tOrigen) ?> ➔ <?= htmlspecialchars($tDestino) ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="eliminarTramoCard(this, '<?= $r['ID_RUTA'] ?>')" title="Eliminar Tramo">✕ Eliminar</button>
                                                </div>
                                                <div class="card-body p-2">
                                                    <div class="row g-2 mb-2">
                                                        <div class="col-md-4">
                                                            <label class="form-label small text-muted mb-0 fw-bold">Sube en (Origen)</label>
                                                            <select name="tramo_origen[]" class="form-select form-select-sm tramo-origen-select fw-bold" required onchange="actualizarTitulosTramos(this.closest('.tramo-card').parentElement)">
                                                                <?= $generarOpcionesCiudades($r['ID_CORREDOR'] ?? ($corredores[0]['ID_CORREDOR'] ?? 0), $tOrigen, '-- Sube en --') ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label small text-muted mb-0 fw-bold">Baja en (Destino)</label>
                                                            <select name="tramo_destino[]" class="form-select form-select-sm tramo-destino-select fw-bold" required onchange="actualizarTitulosTramos(this.closest('.tramo-card').parentElement)">
                                                                <?= $generarOpcionesCiudades($r['ID_CORREDOR'] ?? ($corredores[0]['ID_CORREDOR'] ?? 0), $tDestino, '-- Baja en --') ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label small text-muted mb-0 fw-bold">Operador del Tramo</label>
                                                            <select name="tramo_id_operador[]" class="form-select form-select-sm fw-bold border-primary" onchange="const nom = this.options[this.selectedIndex].getAttribute('data-nombre'); const hid = this.closest('.row').querySelector('input[name=\'tramo_operador[]\']'); if(hid) hid.value = nom; condicionarModalidadPorTramos('<?= $r['ID_RUTA'] ?>');">
                                                                 <?= $generarOpcionesOperadores($tramo['id_operador'] ?? $tramo['ID_OPERADOR'] ?? 1) ?>
                                                             </select>
                                                             <input type="hidden" name="tramo_operador[]" value="<?= htmlspecialchars($tOperador) ?>">
                                                        </div>
                                                    </div>

                                                    <div class="row g-2 mb-2 bg-light p-1 rounded border">
                                                        <div class="col-4">
                                                            <label class="form-label small text-dark mb-0 fw-bold">PVP Tramo Pasaje ($)</label>
                                                            <input type="number" step="0.01" min="0" name="tramo_pvp[]" class="form-control form-control-sm tramo-pvp fw-bold text-primary" value="<?= number_format($tPvp, 2, '.', '') ?>" oninput="recalcularFinanzasTramo(this, '<?= $r['ID_RUTA'] ?>')">
                                                        </div>
                                                        <div class="col-4">
                                                            <label class="form-label small text-muted mb-0 fw-bold">Costo Socio ($)</label>
                                                            <input type="number" step="0.01" min="0" name="tramo_costo[]" class="form-control form-control-sm tramo-costo text-danger" value="<?= number_format($tCosto, 2, '.', '') ?>" oninput="recalcularFinanzasTramo(this, '<?= $r['ID_RUTA'] ?>')">
                                                        </div>
                                                        <div class="col-4">
                                                            <label class="form-label small text-success mb-0 fw-bold">Utilidad Agencia ($)</label>
                                                            <input type="number" step="0.01" name="tramo_utilidad[]" class="form-control form-control-sm tramo-utilidad fw-bold text-success bg-white" value="<?= number_format($tUtilidad, 2, '.', '') ?>" readonly>
                                                        </div>
                                                    </div>

                                                    <div class="row g-2 align-items-center">
                                                        <div class="col-md-5">
                                                            <label class="form-label small mb-0 fw-bold text-secondary">¿Cobrar Sobrepeso en Ecuador?</label>
                                                            <select name="tramo_admite_sp[]" class="form-select form-select-sm tramo-admite-sp" onchange="alCambiarAdmiteSobrepeso(this, '<?= $r['ID_RUTA'] ?>')">
                                                                <option value="0" <?= !$tAdmiteSp ? 'selected' : '' ?>>❌ No cobrar sobrepeso</option>
                                                                <option value="1" <?= $tAdmiteSp ? 'selected' : '' ?>>✅ Sí (Cobro activo en Taquilla)</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small text-muted mb-0">Límite Libre (Kg)</label>
                                                            <input type="number" step="0.1" name="tramo_limite_eq[]" class="form-control form-control-sm" value="<?= number_format($tLimiteEq, 1, '.', '') ?>">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label small text-muted mb-0">Tarifa Sobrepeso ($/Kg)</label>
                                                            <input type="number" step="0.01" name="tramo_precio_sp[]" class="form-control form-control-sm" value="<?= number_format($tPrecioSp, 2, '.', '') ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; endif; ?>
                                    </div>

                                    <!-- RESUMEN FINANCIERO CONSOLIDADO -->
                                    <div class="bg-white border rounded p-2 mt-2 d-flex justify-content-between align-items-center flex-wrap gap-2 shadow-sm">
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <span class="small text-muted fw-bold">RESUMEN TRAMOS:</span>
                                            <span class="badge bg-primary fs-6" id="resumen_pvp_<?= $r['ID_RUTA'] ?>">PVP Total: $0.00</span>
                                            <span class="badge bg-danger fs-6" id="resumen_costo_<?= $r['ID_RUTA'] ?>">Costo Socios: $0.00</span>
                                            <span class="badge bg-success fs-6" id="resumen_utilidad_<?= $r['ID_RUTA'] ?>">Utilidad Neta: $0.00</span>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="copiarTotalATarifa('<?= $r['ID_RUTA'] ?>')">
                                                ⚡ Sincronizar PVP y Utilidad
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- BLOQUE 3: DÍAS Y HORARIO -->
                                <div class="mb-3">
                                    <label class="form-label text-secondary fw-bold">Días de Salida Fija</label>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <?php foreach($mapaDias as $numDia => $txtDia): ?>
                                            <label class="btn btn-outline-secondary btn-sm">
                                                <input type="checkbox" name="dias[]" value="<?= $numDia ?>" <?= in_array((string)$numDia, $diasArr) ? 'checked' : '' ?>> <?= $txtDia ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Hora de Salida</label>
                                        <input type="time" name="hora_salida" class="form-control" value="<?= $r['RUT_SAHORA'] ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Estado de la Ruta</label>
                                        <select name="estado" class="form-select">
                                            <option value="ACTIVO" <?= $esActivo ? 'selected' : '' ?>>Activo (Disponible en taquilla)</option>
                                            <option value="INACTIVO" <?= !$esActivo ? 'selected' : '' ?>>Inactivo (Archivado / Oculto)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- BLOQUE 4: TARIFAS GLOBALES DE VENTA -->
                                <h6 class="text-secondary border-bottom pb-1 fw-bold">Tarifas Globales al Pasajero ($ y %)</h6>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-primary">Tarifa Pasaje ($)</label>
                                        <input type="number" step="0.01" name="precio_pasaje" id="precio_pasaje_edit_<?= $r['ID_RUTA'] ?>" class="form-control form-control-lg fw-bold border-primary text-primary" value="<?= $r['PRECIO_PASAJE'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Kg Encomienda ($)</label>
                                        <input type="number" step="0.01" name="precio_encomienda" class="form-control form-control-lg" value="<?= $r['PRECIO_ENCOMIENDA_KG'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Comisión Taquilla (%)</label>
                                        <input type="number" step="0.10" name="comision_valor" class="form-control form-control-lg" value="<?= $r['COMISION_VALOR'] ?? 10.00 ?>" required>
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Equipaje Libre Base (Kg)</label>
                                        <input type="number" step="0.1" name="limite_equipaje" class="form-control" value="<?= $r['LIMITE_EQUIPAJE_KG'] ?? 35 ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tarifa Sobrepeso Base ($/Kg)</label>
                                        <input type="number" step="0.01" name="precio_sobrepeso" class="form-control" value="<?= $r['PRECIO_SOBREPESO_KG'] ?? 1 ?>" required>
                                    </div>
                                </div>

                                <!-- BLOQUE 5: MÁRGENES DE UTILIDAD EMPRESA / RETENCIÓN AGENCIA -->
                                <h6 class="text-success border-bottom pb-1 fw-bold mt-3">💼 Márgenes de Utilidad Empresa / Terceros</h6>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-secondary">Cálculo Pasajes</label>
                                        <select name="tipo_utilidad" class="form-select border-success fw-bold" id="tipo_utilidad_<?= $r['ID_RUTA'] ?>" onchange="actualizarSimbolosUtilidad('<?= $r['ID_RUTA'] ?>')">
                                            <option value="PORCENTAJE" <?= ($r['TIPO_UTILIDAD'] ?? 'PORCENTAJE') === 'PORCENTAJE' ? 'selected' : '' ?>>% Porcentaje</option>
                                            <option value="FIJO" <?= ($r['TIPO_UTILIDAD'] ?? '') === 'FIJO' ? 'selected' : '' ?>>$ Monto Fijo</option>
                                        </select>
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">Aplica solo a pasajes.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-bold text-secondary mb-0">Utilidad Pasajes</label>
                                            <span class="badge bg-info text-dark <?= ($r['TIPO_VIAJE'] ?? '') === 'CONEXION' ? '' : 'd-none' ?>" id="badge_util_auto_<?= $r['ID_RUTA'] ?>" style="font-size: 0.65rem;" title="Vinculado al desglose de tramos">⚡ Auto (Tramos)</span>
                                        </div>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" name="utilidad_pasaje" id="utilidad_pasaje_<?= $r['ID_RUTA'] ?>" class="form-control fw-bold border-success text-success" value="<?= $r['UTILIDAD_PASAJE'] ?? 100.00 ?>" required>
                                            <span class="input-group-text bg-light fw-bold span-simbolo-util-pasaje-<?= $r['ID_RUTA'] ?>" id="span-simbolo-pasaje-<?= $r['ID_RUTA'] ?>"><?= ($r['TIPO_UTILIDAD'] ?? 'PORCENTAJE') === 'FIJO' ? '$' : '%' ?></span>
                                        </div>
                                        <small class="text-muted d-block" style="font-size: 0.68rem;" id="hint_util_pasaje_<?= $r['ID_RUTA'] ?>">
                                            <?= ($r['TIPO_VIAJE'] ?? '') === 'CONEXION' ? '⚡ Derivado de la suma de tramos.' : 'Retención directa de agencia.' ?>
                                        </small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-secondary">Utilidad Encomiendas</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" name="utilidad_encomienda" class="form-control fw-bold border-success text-success" value="<?= $r['UTILIDAD_ENCOMIENDA'] ?? 100.00 ?>" required>
                                            <span class="input-group-text bg-light fw-bold text-secondary">%</span>
                                        </div>
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">% del flete cobrado.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-secondary">Utilidad Valores/Giros</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" name="utilidad_valores" class="form-control fw-bold border-success text-success" value="<?= $r['UTILIDAD_VALORES'] ?? 100.00 ?>" required>
                                            <span class="input-group-text bg-light fw-bold text-secondary">%</span>
                                        </div>
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">% de la comisión cobrada.</small>
                                    </div>
                                </div>
                                <small class="text-muted d-block mb-1">
                                    Determina la retención neta de la agencia vs el capital de reposición al socio operador en caja.
                                </small>
                            </div>
                            <div class="modal-footer p-2 bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary fw-bold px-4">💾 Guardar Configuración de Ruta</button>
                            </div>
                    </form>
                </div>
            </div>

            <!-- MODAL ELIMINAR RUTA -->
            <div class="modal fade text-start" id="modalEliminarRuta<?= $r['ID_RUTA'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title fs-6 fw-bold">🗑️ Eliminar o Retirar Ruta #<?= $r['ID_RUTA'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/suramerica2030/flota/eliminarRuta" method="POST">
                            <div class="modal-body text-center py-3">
                                <input type="hidden" name="id_ruta" value="<?= $r['ID_RUTA'] ?>">
                                <div class="fs-1 text-danger mb-2">⚠️</div>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($r['RUT_ORIGEN']) ?> ➔ <?= htmlspecialchars($r['RUT_DESTINO']) ?></h6>
                                <?php if(!empty($r['NOMBRE_CORREDOR'])): ?>
                                    <div class="mb-3">
                                        <span class="badge bg-light text-secondary border">🛤️ <?= htmlspecialchars($r['NOMBRE_CORREDOR']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="bg-light p-2 rounded text-start small text-muted border">
                                    <p class="mb-1"><strong>¿Cómo gestiona el sistema esta acción?</strong></p>
                                    <ul class="mb-0 ps-3">
                                        <li>Si la ruta <strong>no tiene boletos vendidos</strong>, se eliminará permanentemente de la base de datos.</li>
                                        <li>Si <strong>ya tiene pasajes o facturas</strong>, se archivará automáticamente como <span class="badge bg-secondary">INACTIVA</span> para proteger la contabilidad y se retirará de boletería y ventas.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="modal-footer bg-light p-2 d-flex gap-2">
                                <button type="button" class="btn btn-secondary btn-sm flex-fill" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger btn-sm flex-fill fw-bold">Confirmar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- MODAL NUEVA RUTA MANUAL -->
        <div class="modal fade" id="modalNuevaRutaManual" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" action="/suramerica2030/flota/guardarRuta" method="POST" id="formNuevaRutaManual">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">+ Programar Nueva Ruta Comercial Manual</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="overflow-y: auto; max-height: calc(100vh - 200px);">
                            <!-- BLOQUE 1: CORREDOR, CABECERA Y MODALIDAD -->
                            <div class="bg-light p-3 rounded border mb-3">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Corredor Asociado</label>
                                        <select name="id_corredor" id="nuevo_corredor" class="form-select border-primary fw-bold" required onchange="alCambiarCorredorEnRuta('')">
                                            <?php foreach($corredores as $cor): ?>
                                                <option value="<?= $cor['ID_CORREDOR'] ?>" <?= (($corredorActivo['ID_CORREDOR'] ?? 0) == $cor['ID_CORREDOR']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cor['CODIGO'] . ' - ' . $cor['NOMBRE']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Origen Cabecera</label>
                                        <select name="origen" id="nuevo_origen" class="form-select select-origen-ruta border-primary fw-bold" required onchange="alCambiarCabecerasRuta('')">
                                            <?= $generarOpcionesCiudades($corredorActivo['ID_CORREDOR'] ?? ($corredores[0]['ID_CORREDOR'] ?? 0), '', '-- Seleccione Origen --') ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Destino Terminal</label>
                                        <select name="destino" id="nuevo_destino" class="form-select select-destino-ruta border-primary fw-bold" required onchange="alCambiarCabecerasRuta('')">
                                            <?= $generarOpcionesCiudades($corredorActivo['ID_CORREDOR'] ?? ($corredores[0]['ID_CORREDOR'] ?? 0), '', '-- Seleccione Destino --') ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label text-primary fw-bold">Tipo de Itinerario</label>
                                        <select name="tipo_viaje" id="nuevo_tipo_viaje" class="form-select border-primary fw-bold" onchange="toggleTramos(this.value, 'tramos_nuevos'); condicionarModalidadAutomatica('');">
                                            <option value="DIRECTO">➡️ Viaje Directo (Un solo operador / bus)</option>
                                            <option value="CONEXION">🔄 Con Conexiones (Tramos Operativos y Transbordos)</option>
                                        </select>
                                        <small class="text-muted">Si es directo o conexión con transbordos.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-primary fw-bold">🔀 Modalidad Logística del Servicio</label>
                                        <select name="modalidad" id="modalidad_select_edit_" class="form-select border-info fw-bold text-dark" onchange="alCambiarModalidadSelect(this, '')">
                                            <option value="AUTO" selected>🤖 Automático (según Operador y Tramos)</option>
                                            <option value="PANAMERICANA">⭐ Vía Panamericana</option>
                                            <option value="CONEXION_TRAMOS">🔀 Con Conexión por Tramos</option>
                                            <option value="ESTANDAR">➡️ Directo Estándar</option>
                                            <option value="OTRO">✏️ Nombre Personalizado...</option>
                                        </select>
                                        <div id="caja_modalidad_personalizada_" class="d-none mt-1">
                                            <input type="text" name="modalidad_personalizada" class="form-control form-control-sm border-info" placeholder="Ej: Ruta Expresa Internacional" value="">
                                        </div>
                                        <small class="text-muted" style="font-size:11px;">Nombre con el que se identificará este trayecto en Taquilla y Finanzas.</small>
                                    </div>
                                </div>

                                <!-- BLOQUE VIAJE DIRECTO: OPERADOR Y SOBREPESO -->
                                <div id="opciones_directo_nuevo" class="bg-white p-2 rounded border mt-2">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label small text-dark mb-0 fw-bold">🏢 Operador de la Ruta Directa</label>
                                            <select name="id_operador" id="nuevo_id_operador" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const nom = this.options[this.selectedIndex].getAttribute('data-nombre'); const hid = this.form.querySelector('input[name=operador]'); if(hid) hid.value = nom; condicionarModalidadPorOperador(this.value, '');">
                                                 <?= $generarOpcionesOperadores(1) ?>
                                             </select>
                                             <input type="hidden" name="operador" value="Suramérica 2030 (Flota Propia)">
                                            <small class="text-muted" style="font-size:11px;">Empresa o socio responsable de la operación directa.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-dark mb-0 fw-bold">⚖️ ¿Cobrar Sobrepeso en Ecuador?</label>
                                            <select name="admite_sobrepeso" class="form-select form-select-sm fw-bold">
                                                <option value="1" selected>✅ Sí (Cobro activo en Taquilla)</option>
                                                <option value="0">❌ No cobrar sobrepeso (Sin cobro en oficina)</option>
                                            </select>
                                            <small class="text-muted" style="font-size:11px;">Habilita o inhabilita el módulo de sobrepeso para esta ruta.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BLOQUE 2: DEFINICIÓN DE TRAMOS -->
                            <div id="tramos_nuevos" class="d-none border rounded p-3 mb-3" style="background-color: #f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                    <div>
                                        <h6 class="text-dark fw-bold mb-0">🛤️ Tramos Operativos, Operadores y Márgenes de Utilidad</h6>
                                        <small class="text-muted">Configure los tramos del viaje, operador socio, PVP, costo del servicio y permisos de sobrepeso.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-dark fw-bold" onclick="addTramo('lista_tramos_nuevos', '')">
                                        + Añadir Tramo
                                    </button>
                                </div>

                                <div id="lista_tramos_nuevos"></div>

                                <!-- RESUMEN FINANCIERO NUEVA RUTA -->
                                <div class="bg-white border rounded p-2 mt-2 d-flex justify-content-between align-items-center flex-wrap gap-2 shadow-sm">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="small text-muted fw-bold">RESUMEN TRAMOS:</span>
                                        <span class="badge bg-primary fs-6" id="resumen_pvp_nuevo">PVP Total: $0.00</span>
                                        <span class="badge bg-danger fs-6" id="resumen_costo_nuevo">Costo Socios: $0.00</span>
                                        <span class="badge bg-success fs-6" id="resumen_utilidad_nuevo">Utilidad Neta: $0.00</span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="copiarTotalATarifa('')">
                                            ⚡ Sincronizar PVP y Utilidad
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- BLOQUE 3: DÍAS Y HORARIO -->
                            <div class="mb-3">
                                <label class="form-label text-secondary fw-bold">Días de Salida Fija</label>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <?php foreach($mapaDias as $numDia => $txtDia): ?>
                                        <label class="btn btn-outline-secondary btn-sm">
                                            <input type="checkbox" name="dias[]" value="<?= $numDia ?>"> <?= $txtDia ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Hora de Salida</label>
                                <input type="time" name="hora_salida" class="form-control" value="10:00" required>
                            </div>
                            
                            <!-- BLOQUE 4: TARIFAS GLOBALES -->
                            <h6 class="text-secondary border-bottom pb-1 fw-bold">Precios y Configuración Global de Venta ($)</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-primary">Tarifa Pasaje ($)</label>
                                    <input type="number" step="0.01" name="precio_pasaje" id="precio_pasaje_nuevo" class="form-control form-control-lg fw-bold border-primary text-primary" value="0.00" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Kg Encomienda ($)</label>
                                    <input type="number" step="0.01" name="precio_encomienda" class="form-control form-control-lg" value="0.00" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">% Comisión Oficina</label>
                                    <input type="number" step="0.10" name="comision_valor" class="form-control form-control-lg" value="10.00" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Equipaje Libre Base (Kg)</label>
                                    <input type="number" step="0.1" name="limite_equipaje" class="form-control" value="35.00" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tarifa Sobrepeso Base ($/Kg)</label>
                                    <input type="number" step="0.01" name="precio_sobrepeso" class="form-control" value="1.00" required>
                                </div>
                            </div>

                            <!-- BLOQUE 5: MÁRGENES DE UTILIDAD EMPRESA / RETENCIÓN AGENCIA -->
                            <h6 class="text-success border-bottom pb-1 fw-bold mt-3">💼 Márgenes de Utilidad Empresa / Terceros</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-secondary">Cálculo Pasajes</label>
                                    <select name="tipo_utilidad" class="form-select border-success fw-bold" id="tipo_utilidad_nuevo" onchange="actualizarSimbolosUtilidad('nuevo')">
                                        <option value="PORCENTAJE" selected>% Porcentaje</option>
                                        <option value="FIJO">$ Monto Fijo</option>
                                    </select>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">Aplica solo a pasajes.</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold text-secondary mb-0">Utilidad Pasajes</label>
                                        <span class="badge bg-info text-dark d-none" id="badge_util_auto_nuevo" style="font-size: 0.65rem;">⚡ Auto (Tramos)</span>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" name="utilidad_pasaje" id="utilidad_pasaje_nuevo" class="form-control fw-bold border-success text-success" value="100.00" required>
                                        <span class="input-group-text bg-light fw-bold span-simbolo-util-pasaje-nuevo" id="span-simbolo-pasaje-nuevo">%</span>
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;" id="hint_util_pasaje_nuevo">
                                        Retención directa de agencia.
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-secondary">Utilidad Encomiendas</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" name="utilidad_encomienda" class="form-control fw-bold border-success text-success" value="100.00" required>
                                        <span class="input-group-text bg-light fw-bold text-secondary">%</span>
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">% del flete cobrado.</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-secondary">Utilidad Valores/Giros</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" name="utilidad_valores" class="form-control fw-bold border-success text-success" value="100.00" required>
                                        <span class="input-group-text bg-light fw-bold text-secondary">%</span>
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">% de la comisión cobrada.</small>
                                </div>
                            </div>
                            <small class="text-muted d-block mb-1">
                                Determina la retención neta de la agencia vs el capital de reposición al socio operador en caja.
                            </small>
                        </div>
                        <div class="modal-footer bg-light p-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary fw-bold px-4">💾 Guardar Ruta</button>
                        </div>
                </form>
            </div>
        </div>

    <!-- ======================================================== -->
    <!-- MODAL: ASISTENTE INTELIGENTE DE RUTAS EN 3 CLICS         -->
    <!-- ======================================================== -->
    <div class="modal fade" id="modalCrearRutaRapida" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <form action="/suramerica2030/flota/crearRutaRapida" method="POST" id="formCrearRutaRapida">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="tramos_json" id="rapida_tramos_json" value="">
                    <input type="hidden" name="costo_total_ruta" id="rapida_costo_total_hidden" value="0">

                    <div class="modal-header bg-primary text-white py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-4">⚡</span>
                            <div>
                                <h5 class="modal-title fw-bold mb-0 text-white">Asistente de Creación de Rutas en 3 Clics</h5>
                                <small class="text-white-50">Crea rutas comerciales en segundos con cálculo automático de tramos, costos y tarifas protegidas</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- PASO 1: ORIGEN Y DESTINO -->
                        <div class="card border-0 bg-light p-3 mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary rounded-circle">1</span>
                                <h6 class="fw-bold text-dark mb-0">Selecciona el Trayecto Comercial</h6>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">🛫 Ciudad de Origen <span class="text-danger">*</span></label>
                                    <input type="text" name="origen" id="rapida_origen" class="form-control form-control-lg fs-6 fw-bold" list="listaCiudadesRutaRapida" placeholder="Ej: Maturín, Quito o Caracas" required autocomplete="off" oninput="dispararSugerenciaRutaRapida()">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">🛬 Ciudad de Destino <span class="text-danger">*</span></label>
                                    <input type="text" name="destino" id="rapida_destino" class="form-control form-control-lg fs-6 fw-bold" list="listaCiudadesRutaRapida" placeholder="Ej: Manta, Caracas o Cúcuta" required autocomplete="off" oninput="dispararSugerenciaRutaRapida()">
                                </div>
                            </div>
                            <datalist id="listaCiudadesRutaRapida">
                                <?php foreach ($ciudadesCatalogo ?? [] as $cd): ?>
                                    <option value="<?= htmlspecialchars($cd['NOMBRE']) ?>"><?= htmlspecialchars($cd['PAIS']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- PASO 2: MODALIDAD OPERATIVA Y RETORNO -->
                        <div class="card border-0 bg-light p-3 mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary rounded-circle">2</span>
                                <h6 class="fw-bold text-dark mb-0">Modalidad Operativa y Retorno</h6>
                            </div>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">🤝 Modalidad de Alianza / Conexión</label>
                                    <select name="modalidad" id="rapida_modalidad" class="form-select fw-bold" onchange="dispararSugerenciaRutaRapida()">
                                        <option value="CONEXION_TRAMOS" selected>🔄 Suramérica Conexiones (Empresas Locales + Troncal)</option>
                                        <option value="PANAMERICANA">⭐ Alianza Panamericana Internacional</option>
                                        <option value="DIRECTO">➡️ Servicio Directo (Flota Propia)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch pt-2">
                                        <input class="form-check-input" type="checkbox" name="crear_retorno" value="1" id="rapida_crear_retorno" checked>
                                        <label class="form-check-label fw-bold text-primary" for="rapida_crear_retorno">
                                            🔁 Crear Retorno Inverso Automáticamente
                                        </label>
                                        <small class="text-muted d-block" style="font-size: 11px;">
                                            Genera en la misma transacción la ruta inversa (Destino ➔ Origen) con sus tramos ordenados al revés.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 3: REVISIÓN DE TRAMOS Y TARIFA SEGURA (FAIL-SAFE) -->
                        <div class="card border shadow-sm p-3 mb-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary rounded-circle">3</span>
                                    <h6 class="fw-bold text-dark mb-0">Tramos Detectados y Tarifa Comercial</h6>
                                </div>
                                <span class="badge bg-light text-muted border" id="rapida_status_tramos">Esperando selección...</span>
                            </div>

                            <!-- CONTENEDOR DE TRAMOS DETECTADOS -->
                            <div id="rapida_contenedor_tramos" class="p-2 mb-3 bg-light rounded border">
                                <p class="text-muted small mb-0 text-center py-3">
                                    👈 Escribe el origen y el destino para que el asistente calcule los tramos maestros y costos de operadores automáticamente.
                                </p>
                            </div>

                            <!-- MATRIZ DE PRECIO Y MARGEN -->
                            <div class="row g-3 align-items-center p-3 rounded" id="rapida_caja_precios" style="background: #f1f8e9;">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-danger mb-0">Costo Operadores (Liquidación):</label>
                                    <div class="fs-4 fw-bold text-danger" id="rapida_lbl_costo_total">$0.00</div>
                                    <small class="text-muted" style="font-size: 11px;">Total a pagar a transportistas</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-primary mb-1">Precio Pasaje al Público ($):</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-primary text-white fw-bold">$</span>
                                        <input type="number" step="0.50" min="0" name="precio_pasaje" id="rapida_input_pvp" class="form-control form-control-lg fw-bold text-primary" placeholder="0.00" required oninput="recalcularMargenRutaRapida()">
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Tarifa final cobrada al pasajero</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-success mb-0">Ganancia Agencia:</label>
                                    <div class="fs-4 fw-bold text-success" id="rapida_lbl_ganancia">$0.00</div>
                                    <div class="progress mt-1" style="height: 6px;">
                                        <div id="rapida_barra_margen" class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                                    </div>
                                    <small class="text-success fw-bold d-block mt-1" id="rapida_lbl_margen_pct" style="font-size: 11px;">0% margen</small>
                                </div>
                            </div>

                            <!-- ALERTA DE BLOQUEO POR PÉRDIDA (FAIL-SAFE) -->
                            <div id="rapida_alerta_perdida" class="alert alert-danger d-none mt-3 mb-0 py-2 d-flex align-items-center gap-2">
                                <span class="fs-4">⛔</span>
                                <div>
                                    <strong class="d-block">Bloqueo de Seguridad Activado</strong>
                                    <span class="small" id="rapida_alerta_perdida_texto">El precio de venta no puede ser inferior a los costos de liquidación.</span>
                                </div>
                            </div>
                        </div>

                        <!-- DÍAS Y HORARIO -->
                        <div class="row g-3 align-items-center">
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-secondary mb-1">Días de Salida:</label>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach($mapaDias as $numDia => $txtDia): ?>
                                        <label class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11px;">
                                            <input type="checkbox" name="dias_salida[]" value="<?= $numDia ?>" checked> <?= $txtDia ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-secondary mb-1">Hora de Salida Fija:</label>
                                <input type="time" name="hora_salida" class="form-control" value="08:00" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4" id="btnGuardarRutaRapida">
                            🚀 Crear Ruta(s) Comerciales
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT DEL ASISTENTE DE RUTAS EN 3 CLICS -->
    <script>
    let timerSugerenciaRapida = null;
    let tramosSugeridosActuales = [];

    function abrirModalRutaRapida() {
        document.getElementById('formCrearRutaRapida').reset();
        document.getElementById('rapida_tramos_json').value = '';
        document.getElementById('rapida_costo_total_hidden').value = '0';
        document.getElementById('rapida_lbl_costo_total').innerText = '$0.00';
        document.getElementById('rapida_input_pvp').value = '';
        document.getElementById('rapida_lbl_ganancia').innerText = '$0.00';
        document.getElementById('rapida_lbl_margen_pct').innerText = '0% margen';
        document.getElementById('rapida_barra_margen').style.width = '0%';
        document.getElementById('rapida_status_tramos').innerText = 'Esperando selección...';
        document.getElementById('rapida_status_tramos').className = 'badge bg-light text-muted border';
        document.getElementById('rapida_contenedor_tramos').innerHTML = `
            <p class="text-muted small mb-0 text-center py-3">
                👈 Escribe el origen y el destino para que el asistente calcule los tramos maestros y costos de operadores automáticamente.
            </p>
        `;
        document.getElementById('rapida_alerta_perdida').classList.add('d-none');
        document.getElementById('btnGuardarRutaRapida').disabled = false;
    }

    function dispararSugerenciaRutaRapida() {
        clearTimeout(timerSugerenciaRapida);
        const origen = (document.getElementById('rapida_origen').value || '').trim();
        const destino = (document.getElementById('rapida_destino').value || '').trim();
        const modalidad = (document.getElementById('rapida_modalidad').value || 'CONEXION_TRAMOS');

        if (origen.length < 3 || destino.length < 3) {
            return;
        }

        if (origen.toLowerCase() === destino.toLowerCase()) {
            document.getElementById('rapida_contenedor_tramos').innerHTML = `
                <div class="alert alert-warning py-2 mb-0 small text-center">
                    ⚠️ El origen y el destino no pueden ser la misma ciudad.
                </div>
            `;
            return;
        }

        document.getElementById('rapida_status_tramos').innerText = 'Calculando tramos...';
        document.getElementById('rapida_status_tramos').className = 'badge bg-warning-subtle text-warning border border-warning-subtle';

        timerSugerenciaRapida = setTimeout(() => {
            fetch(`/suramerica2030/flota/sugerirTramosRuta?origen=${encodeURIComponent(origen)}&destino=${encodeURIComponent(destino)}&modalidad=${encodeURIComponent(modalidad)}`)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        renderizarTramosSugeridosRapida(res.data);
                    } else {
                        document.getElementById('rapida_status_tramos').innerText = 'Error al calcular';
                        document.getElementById('rapida_status_tramos').className = 'badge bg-danger-subtle text-danger border border-danger-subtle';
                    }
                })
                .catch(err => {
                    console.error('Error calculando sugerencia:', err);
                });
        }, 350);
    }

    function renderizarTramosSugeridosRapida(data) {
        tramosSugeridosActuales = data.tramos || [];
        document.getElementById('rapida_tramos_json').value = JSON.stringify(tramosSugeridosActuales);
        document.getElementById('rapida_costo_total_hidden').value = data.costo_total_operadores || 0;
        document.getElementById('rapida_lbl_costo_total').innerText = '$' + (data.costo_total_operadores || 0).toFixed(2);
        
        // Asignar PVP sugerido
        const inputPvp = document.getElementById('rapida_input_pvp');
        inputPvp.value = (data.pvp_sugerido || 0).toFixed(2);

        document.getElementById('rapida_status_tramos').innerText = (tramosSugeridosActuales.length) + ' tramo(s) detectado(s)';
        document.getElementById('rapida_status_tramos').className = 'badge bg-success-subtle text-success border border-success-subtle';

        let html = '<div class="list-group list-group-flush">';
        tramosSugeridosActuales.forEach((t, idx) => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-2 bg-white rounded mb-1 border shadow-xs">
                    <div>
                        <div class="fw-bold text-dark small">
                            ${idx + 1}. ${t.origen} ➔ ${t.destino}
                        </div>
                        <small class="text-secondary" style="font-size: 11px;">
                            🏢 ${t.operador}
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                            Costo: $${parseFloat(t.costo).toFixed(2)}
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">
                            PVP: $${parseFloat(t.pvp).toFixed(2)}
                        </span>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        document.getElementById('rapida_contenedor_tramos').innerHTML = html;
        recalcularMargenRutaRapida();
    }

    function recalcularMargenRutaRapida() {
        const costoTotal = parseFloat(document.getElementById('rapida_costo_total_hidden').value) || 0;
        const pvp = parseFloat(document.getElementById('rapida_input_pvp').value) || 0;
        const btn = document.getElementById('btnGuardarRutaRapida');
        const alertaPerdida = document.getElementById('rapida_alerta_perdida');
        const alertaTexto = document.getElementById('rapida_alerta_perdida_texto');

        if (costoTotal > 0 && pvp < costoTotal) {
            // BLOQUEO POR PÉRDIDA (FAIL-SAFE)
            const deficit = (costoTotal - pvp).toFixed(2);
            alertaTexto.innerText = `El precio configurado ($${pvp.toFixed(2)}) genera una pérdida neta de -$${deficit} frente a la liquidación de operadores ($${costoTotal.toFixed(2)}). Corrija el precio para continuar.`;
            alertaPerdida.classList.remove('d-none');
            btn.disabled = true;

            document.getElementById('rapida_lbl_ganancia').innerText = '-$' + deficit;
            document.getElementById('rapida_lbl_ganancia').className = 'fs-4 fw-bold text-danger';
            document.getElementById('rapida_lbl_margen_pct').innerText = 'Pérdida';
            document.getElementById('rapida_lbl_margen_pct').className = 'text-danger fw-bold d-block mt-1';
            document.getElementById('rapida_barra_margen').style.width = '0%';
            document.getElementById('rapida_barra_margen').className = 'progress-bar bg-danger';
            return;
        }

        alertaPerdida.classList.add('d-none');
        btn.disabled = false;

        const utilidad = Math.max(0, pvp - costoTotal);
        const margenPct = (pvp > 0) ? ((utilidad / pvp) * 100).toFixed(1) : '0';

        document.getElementById('rapida_lbl_ganancia').innerText = '$' + utilidad.toFixed(2);
        document.getElementById('rapida_lbl_ganancia').className = 'fs-4 fw-bold text-success';
        document.getElementById('rapida_lbl_margen_pct').innerText = margenPct + '% margen de rentabilidad';
        document.getElementById('rapida_lbl_margen_pct').className = 'text-success fw-bold d-block mt-1';
        document.getElementById('rapida_barra_margen').style.width = Math.min(100, margenPct) + '%';
        document.getElementById('rapida_barra_margen').className = 'progress-bar bg-success';
    }

    // Manejar envío AJAX del formulario de Ruta Rápida
    document.addEventListener('DOMContentLoaded', function() {
        const formRapida = document.getElementById('formCrearRutaRapida');
        if (formRapida) {
            formRapida.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('btnGuardarRutaRapida');
                btn.disabled = true;
                btn.innerText = '⏳ Creando Ruta(s)...';

                const formData = new FormData(formRapida);

                fetch(formRapida.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerText = '🚀 Crear Ruta(s) Comerciales';

                    if (data.status === 'success') {
                        // Cerrar modal
                        const modalEl = document.getElementById('modalCrearRutaRapida');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        // Mostrar Toast
                        const toastEl = document.getElementById('toastFlotaAjax');
                        if (toastEl) {
                            document.getElementById('toastFlotaAjaxMsg').innerText = data.mensaje || 'Ruta creada exitosamente.';
                            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
                            toast.show();
                        }

                        // Recargar tab de rutas
                        setTimeout(() => {
                            window.location.href = '/suramerica2030/flota?tab=rutas&status=success';
                        }, 800);
                    } else {
                        alert(data.mensaje || 'Error al crear la ruta.');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerText = '🚀 Crear Ruta(s) Comerciales';
                    alert('Error en la comunicación con el servidor: ' + err.message);
                });
            });
        }
    });
    </script>
</div>
