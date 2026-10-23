    <!-- ======================================================== -->
    <!-- PESTAÑA 2: RUTAS COMERCIALES Y MATRIZ DE TARIFAS        -->
    <!-- ======================================================== -->
    <?php 
    $mapaDias = $mapaDias ?? ['1'=>'Lun', '2'=>'Mar', '3'=>'Mié', '4'=>'Jue', '5'=>'Vie', '6'=>'Sáb', '7'=>'Dom']; 
    if (!isset($generarOpcionesCiudades) || !is_callable($generarOpcionesCiudades)) {
        $generarOpcionesCiudades = function($idUnused = null, $valorSeleccionado = '', $placeholder = '-- Seleccionar Ciudad --') use (&$ciudadesCatalogo) {
            $html = '<option value="">' . htmlspecialchars($placeholder) . '</option>';
            $encontrado = false;
            $valNorm = mb_strtolower(trim((string)$valorSeleccionado));

            if (!empty($ciudadesCatalogo)) {
                $porPais = [];
                foreach ($ciudadesCatalogo as $cd) {
                    $pais = strtoupper(trim($cd['PAIS'] ?? 'OTROS'));
                    $porPais[$pais][] = $cd['NOMBRE'];
                }
                
                $banderas = ['VENEZUELA' => '🇻🇪', 'COLOMBIA' => '🇨🇴', 'ECUADOR' => '🇪🇨', 'PERU' => '🇵🇪'];

                foreach ($porPais as $pais => $ciudades) {
                    $flag = $banderas[$pais] ?? '🌎';
                    $html .= '<optgroup label="' . $flag . ' ' . htmlspecialchars($pais) . '">';
                    sort($ciudades);
                    foreach ($ciudades as $nomCiu) {
                        $cNorm = mb_strtolower(trim($nomCiu));
                        $isSel = ($valNorm !== '' && $cNorm === $valNorm);
                        if ($isSel) $encontrado = true;
                        $html .= '<option value="' . htmlspecialchars($nomCiu) . '" ' . ($isSel ? 'selected' : '') . '>'
                              . htmlspecialchars($nomCiu) . '</option>';
                    }
                    $html .= '</optgroup>';
                }
            }

            if ($valorSeleccionado !== '' && !$encontrado) {
                $html .= '<optgroup label="⚠️ Ciudad Actual Configurada">';
                $html .= '<option value="' . htmlspecialchars($valorSeleccionado) . '" selected>' . htmlspecialchars($valorSeleccionado) . '</option>';
                $html .= '</optgroup>';
            }

            return $html;
        };
    }

    if (!isset($generarOpcionesOperadores) || !is_callable($generarOpcionesOperadores)) {
        $operadores = $operadores ?? [];
        $generarOpcionesOperadores = function($idSeleccionado = 1, $placeholder = null) use (&$operadores) {
            $html = '';
            if ($placeholder) {
                $html .= '<option value="">' . htmlspecialchars($placeholder) . '</option>';
            }
            $grupos = [
                'PROPIO' => ['label' => '🏢 Flota Propia', 'items' => []],
                'SOCIO_AFILIADO' => ['label' => '🤝 Socios Propietarios', 'items' => []],
                'EMPRESA_ALIADA' => ['label' => '⭐ Empresas Aliadas', 'items' => []]
            ];
            foreach ($operadores as $op) {
                $tipo = $op['TIPO_OPERADOR'] ?? 'SOCIO_AFILIADO';
                if (!isset($grupos[$tipo])) {
                    $grupos[$tipo] = ['label' => '📋 ' . ucfirst(strtolower($tipo)), 'items' => []];
                }
                $grupos[$tipo]['items'][] = $op;
            }

            foreach ($grupos as $g) {
                if (empty($g['items'])) continue;
                $html .= '<optgroup label="' . htmlspecialchars($g['label']) . '">';
                foreach ($g['items'] as $op) {
                    $isSel = ($idSeleccionado && intval($idSeleccionado) === intval($op['ID_OPERADOR']));
                    $det = !empty($op['IDENTIFICACION']) ? ' [' . $op['IDENTIFICACION'] . ']' : '';
                    $html .= '<option value="' . $op['ID_OPERADOR'] . '" ' . ($isSel ? 'selected' : '') . ' data-nombre="' . htmlspecialchars($op['NOMBRE_COMERCIAL']) . '">'
                          . htmlspecialchars($op['NOMBRE_COMERCIAL']) . htmlspecialchars($det) . '</option>';
                }
                $html .= '</optgroup>';
            }
            return $html;
        };
    }
    ?>
    <div class="tab-pane fade <?= (!in_array($tabActiva ?? '', ['flota', 'buses', 'operadores'])) ? 'show active' : '' ?>" id="tab-rutas">
        
        <!-- DATALIST GLOBAL DE CIUDADES PARA AUTOCOMPLETADO RÁPIDO -->
        <datalist id="listaCiudadesDatalist">
            <?php foreach ($ciudadesCatalogo ?? [] as $cd): ?>
                <option value="<?= htmlspecialchars($cd['NOMBRE']) ?>"><?= htmlspecialchars($cd['PAIS']) ?></option>
            <?php endforeach; ?>
        </datalist>

        <!-- TOOLBAR PRINCIPAL: RUTAS COMERCIALES Y ACCIONES -->
        <div class="card border-0 shadow-sm mb-4 bg-white" style="border-radius: 12px; border-left: 5px solid #0d6efd !important;">
            <div class="card-body py-3 px-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-2">🗺️</span>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Rutas Comerciales y Tarifas al Viajero (PVP)</h5>
                            <small class="text-muted">Servicios y boletos a la venta en taquilla para pasajeros internacionales.</small>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Botón Único de Creación -->
                        <button class="btn btn-primary fw-bold shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalNuevaRutaComercial" onclick="abrirModalNuevaRutaComercial()">
                            ➕ Nueva Ruta Comercial
                        </button>

                        <!-- Enlace Rápido a Acuerdos de Liquidación en Tab 2 -->
                        <button class="btn btn-outline-primary fw-bold shadow-sm px-3" onclick="irATabOperadores('subtab-tramos')">
                            🤝 Acuerdos de Liquidación
                        </button>

                        <!-- Menú de Opciones -->
                        <div class="dropdown">
                            <button class="btn btn-light border dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                ⚙️ Opciones
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                <li>
                                    <button class="dropdown-item py-2 fw-semibold text-primary" onclick="window.location.reload();">
                                        🔄 Refrescar Listado
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
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

        // Modalidades Oficiales del Sistema (Las 4 Estratégicas)
        $modalidadesUnicas = ['CONEXIONES', 'PANAMERICANA', 'EXPRESOS_SURAMERICA', 'PANA_EXPRESOS'];
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

                <!-- FILA 2: OPERADOR, MODALIDAD, TIPO DE VIAJE Y ESTADO -->
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
                    <div class="col-12 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🧭 Sentido:</span>
                            <select id="filtroSentidoRutas" class="form-select bg-white">
                                <option value="">Todos los Sentidos</option>
                                <option value="NORTE">🇪🇨 ➔ 🇻🇪 Hacia Venezuela / Norte (<?= $rutasPorSentido['NORTE'] ?? 0 ?>)</option>
                                <option value="SUR">🇻🇪 ➔ 🇪🇨 Hacia Ecuador / Sur (<?= $rutasPorSentido['SUR'] ?? 0 ?>)</option>
                                <option value="NACIONAL">📍 Nacionales (<?= $rutasPorSentido['NACIONAL'] ?? 0 ?>)</option>
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

                            $mod = $r['MODALIDAD'] ?? 'CONEXIONES';
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
                                data-sentido="<?= htmlspecialchars($r['SENTIDO'] ?? 'NORTE') ?>" 
                                data-origen="<?= htmlspecialchars(mb_strtolower(trim($r['RUT_ORIGEN']))) ?>" 
                                data-destino="<?= htmlspecialchars(mb_strtolower(trim($r['RUT_DESTINO']))) ?>" 
                                data-operador="<?= htmlspecialchars(mb_strtolower(trim($operadorMostrar))) ?>" 
                                data-id-operador="<?= $r['ID_OPERADOR'] ?? '' ?>"
                                data-modalidad="<?= htmlspecialchars(mb_strtolower(trim($r['MODALIDAD'] ?? 'conexiones'))) ?>" 
                                data-tipo-viaje="<?= htmlspecialchars(mb_strtolower($r['TIPO_VIAJE'] ?? 'directo')) ?>" 
                                data-estado="<?= htmlspecialchars(mb_strtolower($r['ESTADO'] ?? 'activo')) ?>" 
                                data-search="<?= strtolower(htmlspecialchars($r['RUT_ORIGEN'] . ' ' . $r['RUT_DESTINO'] . ' ' . ($r['NOMBRE_SENTIDO'] ?? '') . ' #' . $r['ID_RUTA'] . ' ' . $operadorMostrar . ' ' . implode(' ', $opsTramos) . ' ' . ($r['MODALIDAD'] ?? ''))) ?>">
                                <td><span class="badge bg-secondary">#<?= $r['ID_RUTA'] ?></span></td>
                                <td class="col-ruta-trayecto">
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($r['RUT_ORIGEN']) ?> ➔ <?= htmlspecialchars($r['RUT_DESTINO']) ?></div>
                                    <div class="mt-1">
                                        <span class="badge <?= $r['BADGE_SENTIDO'] ?? 'bg-light text-primary border' ?>" style="font-size: 0.75rem; font-weight: 500;">
                                            <?= $r['ICONO_SENTIDO'] ?? '🧭' ?> <?= htmlspecialchars($r['TAG_SENTIDO'] ?? $r['SENTIDO'] ?? 'Comercial') ?>
                                        </span>
                                    </div>
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
                                <p class="small text-secondary mb-0">No hay coincidencias con los filtros de búsqueda o sentido seleccionados.</p>
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
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">📍 Origen de Ruta</label>
                                            <select name="origen" id="edit_origen_<?= $r['ID_RUTA'] ?>" class="form-select select-origen-ruta border-primary fw-bold" required onchange="alCambiarCabecerasRuta('<?= $r['ID_RUTA'] ?>')">
                                                <?= $generarOpcionesCiudades(null, $r['RUT_ORIGEN'], '-- Seleccione Origen --') ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">🏁 Destino de Ruta</label>
                                            <select name="destino" id="edit_destino_<?= $r['ID_RUTA'] ?>" class="form-select select-destino-ruta border-primary fw-bold" required onchange="alCambiarCabecerasRuta('<?= $r['ID_RUTA'] ?>')">
                                                <?= $generarOpcionesCiudades(null, $r['RUT_DESTINO'], '-- Seleccione Destino --') ?>
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
                                            <label class="form-label text-primary fw-bold">🔀 Modalidad Comercial del Servicio</label>
                                            <?php 
                                                $currMod = \Platform\Helpers\ModalidadHelper::normalizarCodigo($r['MODALIDAD'] ?? 'CONEXIONES');
                                            ?>
                                            <select name="modalidad" id="modalidad_select_edit_<?= $r['ID_RUTA'] ?>" class="form-select border-info fw-bold text-dark">
                                                <option value="CONEXIONES" <?= $currMod === 'CONEXIONES' ? 'selected' : '' ?>>🔀 Conexiones Locales (Aliados EC, COL, VE)</option>
                                                <option value="PANAMERICANA" <?= $currMod === 'PANAMERICANA' ? 'selected' : '' ?>>🚌 Vía Panamericana (Internacional Binacional)</option>
                                                <option value="EXPRESOS_SURAMERICA" <?= $currMod === 'EXPRESOS_SURAMERICA' ? 'selected' : '' ?>>🚍 Expresos Suramérica 2030 (Troncal Venezuela)</option>
                                                <option value="PANA_EXPRESOS" <?= $currMod === 'PANA_EXPRESOS' ? 'selected' : '' ?>>⚡ Alianza Pana-Expresos (Alta Eficiencia)</option>
                                            </select>
                                            <small class="text-muted" style="font-size:11px;">Modalidad estratégica con la que se comercializa este viaje en Taquilla, Manifiesto y Finanzas.</small>
                                        </div>
                                    </div>

                                    <!-- BLOQUE VIAJE DIRECTO: OPERADOR Y SOBREPESO -->
                                    <div id="opciones_directo_edit_<?= $r['ID_RUTA'] ?>" style="display: none;">
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
                                <div id="tramos_edit_<?= $r['ID_RUTA'] ?>" class="border rounded p-3 mb-3" style="background-color: #f8fafc;">
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
                                        if (empty($tramosList)) {
                                            $costoBase = floatval($r['COSTO_TOTAL_RUTA'] ?? 0);
                                            $pvpBase = floatval($r['PRECIO_PASAJE'] ?? 0);
                                            $tramosList = [
                                                [
                                                    'origen' => $r['RUT_ORIGEN'],
                                                    'destino' => $r['RUT_DESTINO'],
                                                    'id_operador' => $r['ID_OPERADOR'] ?? 1,
                                                    'operador' => !empty($r['OPERADOR_NOMBRE']) ? $r['OPERADOR_NOMBRE'] : 'Suramérica 2030',
                                                    'pvp' => $pvpBase,
                                                    'costo' => $costoBase,
                                                    'utilidad' => max(0, $pvpBase - $costoBase),
                                                    'admite_sobrepeso' => $r['ADMITE_SOBREPESO'] ?? 1,
                                                    'limite_equipaje' => $r['LIMITE_EQUIPAJE_KG'] ?? 35,
                                                    'precio_sobrepeso' => $r['PRECIO_SOBREPESO_KG'] ?? 1
                                                ]
                                            ];
                                        }
                                        $sumPvp = array_sum(array_column($tramosList, 'pvp'));
                                        $sumCosto = array_sum(array_column($tramosList, 'costo'));
                                        $sumUtil = array_sum(array_column($tramosList, 'utilidad'));
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
                                                                <?= $generarOpcionesCiudades(null, $tOrigen, '-- Sube en --') ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label small text-muted mb-0 fw-bold">Baja en (Destino)</label>
                                                            <select name="tramo_destino[]" class="form-select form-select-sm tramo-destino-select fw-bold" required onchange="actualizarTitulosTramos(this.closest('.tramo-card').parentElement)">
                                                                <?= $generarOpcionesCiudades(null, $tDestino, '-- Baja en --') ?>
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
                                            <span class="badge bg-primary fs-6" id="resumen_pvp_<?= $r['ID_RUTA'] ?>">PVP Total: $<?= number_format($sumPvp ?? 0, 2) ?></span>
                                            <span class="badge bg-danger fs-6" id="resumen_costo_<?= $r['ID_RUTA'] ?>">Costo Socios: $<?= number_format($sumCosto ?? 0, 2) ?></span>
                                            <span class="badge bg-success fs-6" id="resumen_utilidad_<?= $r['ID_RUTA'] ?>">Utilidad Neta: $<?= number_format($sumUtil ?? 0, 2) ?></span>
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
                                <div class="mb-3">
                                    <span class="badge <?= $r['BADGE_SENTIDO'] ?? 'bg-light text-secondary border' ?>">
                                        <?= $r['ICONO_SENTIDO'] ?? '🧭' ?> <?= htmlspecialchars($r['TAG_SENTIDO'] ?? 'Comercial') ?>
                                    </span>
                                </div>
                                
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

                <!-- ======================================================== -->
        <!-- MODAL UNIFICADO: NUEVA RUTA COMERCIAL                    -->
        <!-- ======================================================== -->
        <div class="modal fade" id="modalNuevaRutaComercial" tabindex="-1" aria-labelledby="modalNuevaRutaComercialLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content shadow-lg border-0" action="/suramerica2030/flota/crearRutaRapida" method="POST" id="formNuevaRutaComercial">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="costo_total_ruta" id="nueva_costo_total_hidden" value="0">
                    <input type="hidden" name="modalidad" id="nueva_modalidad_hidden" value="CONEXIONES">

                    <div class="modal-header bg-primary text-white py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-4">➕</span>
                            <div>
                                <h5 class="modal-title fw-bold text-white mb-0" id="modalNuevaRutaComercialLabel">Programar Nueva Ruta Comercial</h5>
                                <small class="text-white-50">Crea trayectos directos o con conexión. Detecta automáticamente tramos y costos de operadores aliados.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body p-4 bg-light" style="overflow-y: auto; max-height: calc(100vh - 180px);">
                        <!-- BLOQUE 1: CIUDADES Y FRECUENCIA -->
                        <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-dark mb-1">🛫 Ciudad de Origen (Embarque) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted">📍</span>
                                        <input type="text" name="origen" id="nueva_origen" class="form-control fw-bold border-primary" list="listaCiudadesDatalist" placeholder="Ej: Manta" required autocomplete="off" oninput="dispararDeteccionNuevaRuta()" onchange="dispararDeteccionNuevaRuta()">
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Escribe o selecciona la ciudad donde sube el pasajero.</small>
                                </div>
                                <div class="col-md-2 text-center d-flex flex-column justify-content-center align-items-center">
                                    <span class="fs-3 text-primary d-none d-md-block">➔</span>
                                    <div class="form-check form-switch pt-1">
                                        <input class="form-check-input" type="checkbox" name="crear_retorno" value="1" id="nueva_crear_retorno" checked>
                                        <label class="form-check-label small fw-bold text-primary" for="nueva_crear_retorno" title="Genera también la ruta inversa Destino ➔ Origen">
                                            🔁 Retorno
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-dark mb-1">🛬 Ciudad de Destino (Desembarque) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted">🏁</span>
                                        <input type="text" name="destino" id="nueva_destino" class="form-control fw-bold border-primary" list="listaCiudadesDatalist" placeholder="Ej: Caracas" required autocomplete="off" oninput="dispararDeteccionNuevaRuta()" onchange="dispararDeteccionNuevaRuta()">
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Escribe o selecciona la ciudad donde finaliza el viaje.</small>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="row g-3 align-items-center">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-secondary mb-1">Días de Salida Fija:</label>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach($mapaDias as $numDia => $txtDia): ?>
                                            <label class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11px;">
                                                <input type="checkbox" name="dias_salida[]" value="<?= $numDia ?>" checked> <?= $txtDia ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-secondary mb-1">Hora de Salida:</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light">⏰</span>
                                        <input type="time" name="hora_salida" class="form-control" value="08:00" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUE 1.5: MODALIDADES MULTIMODALES ESTRATÉGICAS (ROUTING ENGINE) -->
                        <div class="card border-0 shadow-sm p-3 mb-3 bg-white" id="bloque_modalidades_multimodal" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom flex-wrap gap-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold text-dark mb-0">🔀 Modalidades Estratégicas de Itinerario</h6>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="badgeTotalModalidadesDetectadas">0 opciones</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">El Motor Multimodal analizó la red y propone estas alternativas de transporte:</small>
                                </div>
                                <div class="form-check form-switch bg-light px-3 py-1 rounded border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="chkGenerarLoteModalidades" onchange="toggleModoLoteModalidades(this.checked)">
                                    <label class="form-check-label small fw-bold text-primary" for="chkGenerarLoteModalidades" title="Genera todas las modalidades encontradas simultáneamente">
                                        ⚡ Generar todas en 1 clic
                                    </label>
                                </div>
                            </div>

                            <div class="row g-2" id="contenedor_tarjetas_modalidad">
                                <!-- Tarjetas inyectadas dinámicamente con JS -->
                            </div>
                        </div>

                        <!-- BLOQUE 2: TRAMOS OPERATIVOS Y COSTOS DE OPERADORES -->
                        <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom flex-wrap gap-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold text-dark mb-0">🧩 Desglose de Tramos y Liquidación a Operadores</h6>
                                        <span class="badge bg-light text-muted border" id="nueva_status_tramos">Esperando ciudades...</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Se detectan automáticamente según los acuerdos con Panamericana, Expresos Suramérica y Enlaces Locales.</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="crearTramoManualVacio()">
                                    + Añadir Tramo Manual
                                </button>
                            </div>

                            <!-- CONTENEDOR DINÁMICO DE TRAMOS -->
                            <div id="lista_tramos_nueva_comercial" class="d-flex flex-column gap-2 mb-2">
                                <div class="text-center py-4 text-muted small" id="nueva_msg_vacio_tramos">
                                    👈 Ingresa la ciudad de origen y destino arriba para detectar los tramos y costos de operadores automáticamente.
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUE 3: MATRIZ FINANCIERA EN VIVO (FAIL-SAFE ANTI-PÉRDIDAS) -->
                        <div class="card border-0 shadow-sm p-3 bg-white" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%) !important; border-left: 5px solid #16a34a !important;">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-danger mb-0">Costo Liquidación Operadores:</label>
                                    <div class="fs-4 fw-bold text-danger" id="nueva_lbl_costo_total">$0.00</div>
                                    <small class="text-muted" style="font-size: 11px;">Monto total a pagar a transportistas</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-primary mb-1">Precio Pasaje al Público ($ PVP):</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-primary text-white fw-bold">$</span>
                                        <input type="number" step="0.50" min="0" name="precio_pasaje" id="nueva_input_pvp" class="form-control form-control-lg fw-bold text-primary" placeholder="0.00" required oninput="recalcularMargenNuevaRutaComercial()">
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Tarifa final en taquilla al viajero</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-success mb-0">Utilidad Neta de la Agencia:</label>
                                    <div class="fs-4 fw-bold text-success" id="nueva_lbl_ganancia">$0.00</div>
                                    <div class="progress mt-1" style="height: 6px;">
                                        <div id="nueva_barra_margen" class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                                    </div>
                                    <small class="text-success fw-bold d-block mt-1" id="nueva_lbl_margen_pct" style="font-size: 11px;">0% margen</small>
                                </div>
                            </div>

                            <!-- ALERTA DE BLOQUEO POR PÉRDIDA -->
                            <div id="nueva_alerta_perdida" class="alert alert-danger d-none mt-3 mb-0 py-2 d-flex align-items-center gap-2">
                                <span class="fs-4">⛔</span>
                                <div>
                                    <strong class="d-block">Bloqueo de Seguridad Activado</strong>
                                    <span class="small" id="nueva_alerta_perdida_texto">El precio de venta no puede ser inferior a los costos de liquidación.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4" id="btnGuardarNuevaRutaComercial">
                            🚀 Crear Ruta Comercial
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SCRIPT UNIFICADO DE NUEVA RUTA COMERCIAL -->
        <script>
        let timerDeteccionNueva = null;
        window.modalidadesDetectadasActuales = {};
        window.modalidadSeleccionadaActual = '';

        function abrirModalNuevaRutaComercial() {
            const form = document.getElementById('formNuevaRutaComercial');
            if (form) {
                form.reset();
                form.action = '/suramerica2030/flota/crearRutaRapida';
            }
            window.modalidadesDetectadasActuales = {};
            window.modalidadSeleccionadaActual = '';

            const chkLote = document.getElementById('chkGenerarLoteModalidades');
            if (chkLote) chkLote.checked = false;

            const bloqueMod = document.getElementById('bloque_modalidades_multimodal');
            if (bloqueMod) bloqueMod.style.display = 'none';

            const btnGuardar = document.getElementById('btnGuardarNuevaRutaComercial');
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '🚀 Crear Ruta Comercial';
                btnGuardar.className = 'btn btn-primary fw-bold px-4';
            }

            document.getElementById('nueva_costo_total_hidden').value = '0';
            document.getElementById('nueva_modalidad_hidden').value = 'CONEXIONES';
            document.getElementById('nueva_lbl_costo_total').innerText = '$0.00';
            document.getElementById('nueva_input_pvp').value = '';
            document.getElementById('nueva_lbl_ganancia').innerText = '$0.00';
            document.getElementById('nueva_lbl_margen_pct').innerText = '0% margen';
            document.getElementById('nueva_barra_margen').style.width = '0%';
            document.getElementById('nueva_status_tramos').innerText = 'Esperando ciudades...';
            document.getElementById('nueva_status_tramos').className = 'badge bg-light text-muted border';
            document.getElementById('lista_tramos_nueva_comercial').innerHTML = `
                <div class="text-center py-4 text-muted small" id="nueva_msg_vacio_tramos">
                    👈 Ingresa la ciudad de origen y destino arriba para detectar los tramos y costos de operadores automáticamente.
                </div>
            `;
            document.getElementById('nueva_alerta_perdida').classList.add('d-none');
        }

        function dispararDeteccionNuevaRuta() {
            clearTimeout(timerDeteccionNueva);
            const orig = (document.getElementById('nueva_origen').value || '').trim();
            const dest = (document.getElementById('nueva_destino').value || '').trim();

            if (orig.length < 3 || dest.length < 3) return;
            if (orig.toLowerCase() === dest.toLowerCase()) {
                document.getElementById('nueva_status_tramos').innerText = 'Origen y destino iguales';
                document.getElementById('nueva_status_tramos').className = 'badge bg-warning-subtle text-warning border';
                return;
            }

            document.getElementById('nueva_status_tramos').innerText = 'Analizando red multimodal...';
            document.getElementById('nueva_status_tramos').className = 'badge bg-info-subtle text-info border';

            timerDeteccionNueva = setTimeout(() => {
                fetch(`/suramerica2030/flota/sugerirTramosRuta?origen=${encodeURIComponent(orig)}&destino=${encodeURIComponent(dest)}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success' && res.data) {
                            if (res.es_lote) {
                                window.modalidadesDetectadasActuales = res.data;
                                renderizarTarjetasModalidad(res.data);
                                
                                // Prioridad de selección automática: CONEXIONES o PANAMERICANA o primera
                                const keys = Object.keys(res.data);
                                let modSeleccion = keys[0];
                                if (res.data['CONEXIONES']) modSeleccion = 'CONEXIONES';
                                else if (res.data['PANAMERICANA']) modSeleccion = 'PANAMERICANA';

                                if (modSeleccion) {
                                    seleccionarModalidad(modSeleccion);
                                }
                            } else if (res.data.tramos && res.data.tramos.length > 0) {
                                renderizarTramosNuevaRuta(res.data);
                            }
                        } else {
                            document.getElementById('nueva_status_tramos').innerText = 'Sin tramos predefinidos';
                            document.getElementById('nueva_status_tramos').className = 'badge bg-secondary-subtle text-secondary border';
                            if (!document.querySelector('#lista_tramos_nueva_comercial .tramo-card')) {
                                crearTramoDirectoPorDefecto(orig, dest);
                            }
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (!document.querySelector('#lista_tramos_nueva_comercial .tramo-card')) {
                            crearTramoDirectoPorDefecto(orig, dest);
                        }
                    });
            }, 300);
        }

        function renderizarTarjetasModalidad(modalidades) {
            const contenedor = document.getElementById('contenedor_tarjetas_modalidad');
            const bloque = document.getElementById('bloque_modalidades_multimodal');
            const badgeTotal = document.getElementById('badgeTotalModalidadesDetectadas');
            if (!contenedor || !bloque) return;

            contenedor.innerHTML = '';
            const keys = Object.keys(modalidades);
            if (keys.length === 0) {
                bloque.style.display = 'none';
                return;
            }

            bloque.style.display = 'block';
            if (badgeTotal) badgeTotal.innerText = `${keys.length} opciones disponibles`;

            const chkLote = document.getElementById('chkGenerarLoteModalidades');
            const enLote = chkLote ? chkLote.checked : false;

            keys.forEach(key => {
                const it = modalidades[key];
                const col = document.createElement('div');
                col.className = 'col-12 col-md-6';
                const isDisponible = it.disponible !== false;

                col.innerHTML = `
                    <div class="card h-100 p-2 tarjeta-modalidad-itinerario border ${isDisponible ? '' : 'opacity-75'}" 
                         id="card_mod_${key}" 
                         data-mod="${key}" 
                         onclick="seleccionarModalidad('${key}')"
                         style="cursor: pointer; transition: all 0.2s; border-radius: 8px;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check checkbox-lote-mod" style="display: ${enLote ? 'block' : 'none'};" onclick="event.stopPropagation();">
                                    <input class="form-check-input chk-modalidad-item" type="checkbox" name="modalidades[]" value="${key}" checked>
                                </div>
                                <span class="badge ${it.badge_class || 'bg-secondary text-white'} px-2 py-1">
                                    ${it.icono || '🔀'} ${it.nombre_modalidad || key}
                                </span>
                            </div>
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 11px;">
                                ⏱️ ${it.tiempo_estimado_horas || 0}h
                            </span>
                        </div>
                        <div class="small text-muted mb-1 text-truncate" style="font-size: 11px;" title="${it.itinerario_resumen || ''}">
                            📍 ${it.itinerario_resumen || ''}
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 12px;">
                            <div>
                                <span class="text-danger fw-bold">$${parseFloat(it.costo_total_operadores || 0).toFixed(2)}</span>
                                <small class="text-muted" style="font-size: 10px;">costo</small>
                            </div>
                            <div>
                                <span class="text-primary fw-bold">$${parseFloat(it.pvp_sugerido || 0).toFixed(2)}</span>
                                <small class="text-muted" style="font-size: 10px;">PVP</small>
                            </div>
                            <div>
                                <span class="text-success fw-bold">+$${parseFloat(it.utilidad_agencia || 0).toFixed(2)}</span>
                                <small class="text-success" style="font-size: 10px;">(${it.margen_pct || 0}%)</small>
                            </div>
                        </div>
                    </div>
                `;
                contenedor.appendChild(col);
            });
        }

        function seleccionarModalidad(key) {
            if (!window.modalidadesDetectadasActuales || !window.modalidadesDetectadasActuales[key]) return;
            window.modalidadSeleccionadaActual = key;

            document.querySelectorAll('.tarjeta-modalidad-itinerario').forEach(c => {
                c.classList.remove('border-primary', 'shadow-sm', 'bg-primary-subtle');
                c.style.borderWidth = '1px';
            });

            const activeCard = document.getElementById('card_mod_' + key);
            if (activeCard) {
                activeCard.classList.add('border-primary', 'shadow-sm', 'bg-primary-subtle');
                activeCard.style.borderWidth = '2px';
            }

            const hiddenMod = document.getElementById('nueva_modalidad_hidden');
            if (hiddenMod) hiddenMod.value = key;

            renderizarTramosNuevaRuta(window.modalidadesDetectadasActuales[key]);
        }

        function toggleModoLoteModalidades(activado) {
            const form = document.getElementById('formNuevaRutaComercial');
            const btn = document.getElementById('btnGuardarNuevaRutaComercial');
            const chks = document.querySelectorAll('.checkbox-lote-mod');

            if (activado) {
                form.action = '/suramerica2030/flota/generarRutasMultimodales';
                chks.forEach(c => c.style.display = 'block');
                btn.innerHTML = '⚡ Generar Rutas Multimodales en Lote';
                btn.className = 'btn btn-success fw-bold px-4';
            } else {
                form.action = '/suramerica2030/flota/crearRutaRapida';
                chks.forEach(c => c.style.display = 'none');
                btn.innerHTML = '🚀 Crear Ruta Comercial';
                btn.className = 'btn btn-primary fw-bold px-4';
            }
        }

        function renderizarTramosNuevaRuta(data) {
            const tramos = data.tramos || [];
            const container = document.getElementById('lista_tramos_nueva_comercial');
            container.innerHTML = '';

            tramos.forEach((t, idx) => {
                const div = document.createElement('div');
                div.className = 'tramo-card card border p-3 mb-2 bg-light shadow-sm';
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary px-2 py-1 tramo-badge-titulo">Tramo ${idx + 1}: ${t.origen} ➔ ${t.destino}</span>
                            ${t.tiempo_estimado_horas ? `<span class="badge bg-light text-secondary border font-monospace" style="font-size:10px;">⏱️ ${t.tiempo_estimado_horas}h</span>` : ''}
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 fw-bold" onclick="eliminarTramoCardNueva(this)">✕ Quitar</button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-0">Sube en</label>
                            <input type="text" name="tramo_origen[]" class="form-control form-control-sm fw-bold tramo-origen-select" value="${t.origen}" list="listaCiudadesDatalist" required onchange="actualizarTitulosTramosNueva()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-0">Baja en</label>
                            <input type="text" name="tramo_destino[]" class="form-control form-control-sm fw-bold tramo-destino-select" value="${t.destino}" list="listaCiudadesDatalist" required onchange="actualizarTitulosTramosNueva()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-0">Operador Encargado</label>
                            <select name="tramo_id_operador[]" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const opName = this.options[this.selectedIndex].getAttribute('data-nombre') || this.options[this.selectedIndex].text; this.closest('.tramo-card').querySelector('input[name=\'tramo_operador[]\']').value = opName;">
                                ${HTML_OPCIONES_OPERADORES}
                            </select>
                            <input type="hidden" name="tramo_operador[]" value="${t.operador || 'Panamericana'}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-danger fw-bold mb-0">Costo ($)</label>
                            <input type="number" step="0.50" min="0" name="tramo_costo[]" class="form-control form-control-sm fw-bold text-danger border-danger tramo-costo-input" value="${parseFloat(t.costo || 0).toFixed(2)}" required oninput="recalcularMargenNuevaRutaComercial()">
                        </div>
                        <div class="col-md-1 d-flex align-items-end justify-content-center">
                            <div class="form-check" title="Admite Sobrepeso">
                                <input class="form-check-input" type="checkbox" name="tramo_admite_sp[]" value="1" ${t.admite_sobrepeso ? 'checked' : ''}>
                                <label class="form-check-label small" style="font-size:10px;">SP</label>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="tramo_pvp[]" class="tramo-pvp-input" value="${parseFloat(t.pvp || 0).toFixed(2)}">
                `;
                container.appendChild(div);

                if (t.id_operador) {
                    const selOp = div.querySelector('select[name="tramo_id_operador[]"]');
                    if (selOp) selOp.value = t.id_operador;
                }
            });

            document.getElementById('nueva_status_tramos').innerText = `${tramos.length} tramo(s) detectado(s)`;
            document.getElementById('nueva_status_tramos').className = 'badge bg-success-subtle text-success border';

            if (data.pvp_sugerido) {
                document.getElementById('nueva_input_pvp').value = parseFloat(data.pvp_sugerido).toFixed(2);
            }

            recalcularMargenNuevaRutaComercial();
        }

        function crearTramoDirectoPorDefecto(orig, dest) {
            const container = document.getElementById('lista_tramos_nueva_comercial');
            container.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'tramo-card card border p-3 mb-2 bg-light shadow-sm';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-primary px-2 py-1 tramo-badge-titulo">Tramo 1: ${orig} ➔ ${dest} (Directo)</span>
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 fw-bold" onclick="eliminarTramoCardNueva(this)">✕ Quitar</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-0">Sube en</label>
                        <input type="text" name="tramo_origen[]" class="form-control form-control-sm fw-bold tramo-origen-select" value="${orig}" list="listaCiudadesDatalist" required onchange="actualizarTitulosTramosNueva()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-0">Baja en</label>
                        <input type="text" name="tramo_destino[]" class="form-control form-control-sm fw-bold tramo-destino-select" value="${dest}" list="listaCiudadesDatalist" required onchange="actualizarTitulosTramosNueva()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-0">Operador Encargado</label>
                        <select name="tramo_id_operador[]" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const opName = this.options[this.selectedIndex].getAttribute('data-nombre') || this.options[this.selectedIndex].text; this.closest('.tramo-card').querySelector('input[name=\'tramo_operador[]\']').value = opName;">
                            ${HTML_OPCIONES_OPERADORES}
                        </select>
                        <input type="hidden" name="tramo_operador[]" value="Panamericana">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-danger fw-bold mb-0">Costo ($)</label>
                        <input type="number" step="0.50" min="0" name="tramo_costo[]" class="form-control form-control-sm fw-bold text-danger border-danger tramo-costo-input" value="0.00" required oninput="recalcularMargenNuevaRutaComercial()">
                    </div>
                    <div class="col-md-1 d-flex align-items-end justify-content-center">
                        <div class="form-check" title="Admite Sobrepeso">
                            <input class="form-check-input" type="checkbox" name="tramo_admite_sp[]" value="1" checked>
                            <label class="form-check-label small" style="font-size:10px;">SP</label>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="tramo_pvp[]" class="tramo-pvp-input" value="0.00">
            `;
            container.appendChild(div);
            recalcularMargenNuevaRutaComercial();
        }

        function crearTramoManualVacio() {
            const container = document.getElementById('lista_tramos_nueva_comercial');
            const msgVacio = document.getElementById('nueva_msg_vacio_tramos');
            if (msgVacio) msgVacio.remove();

            const cant = container.querySelectorAll('.tramo-card').length + 1;
            const div = document.createElement('div');
            div.className = 'tramo-card card border p-3 mb-2 bg-light shadow-sm';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-primary px-2 py-1 tramo-badge-titulo">Tramo ${cant}: Nuevo Tramo</span>
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 fw-bold" onclick="eliminarTramoCardNueva(this)">✕ Quitar</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-0">Sube en</label>
                        <input type="text" name="tramo_origen[]" class="form-control form-control-sm fw-bold tramo-origen-select" placeholder="Origen" list="listaCiudadesDatalist" required onchange="actualizarTitulosTramosNueva()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-0">Baja en</label>
                        <input type="text" name="tramo_destino[]" class="form-control form-control-sm fw-bold tramo-destino-select" placeholder="Destino" list="listaCiudadesDatalist" required onchange="actualizarTitulosTramosNueva()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-0">Operador Encargado</label>
                        <select name="tramo_id_operador[]" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const opName = this.options[this.selectedIndex].getAttribute('data-nombre') || this.options[this.selectedIndex].text; this.closest('.tramo-card').querySelector('input[name=\'tramo_operador[]\']').value = opName;">
                            ${HTML_OPCIONES_OPERADORES}
                        </select>
                        <input type="hidden" name="tramo_operador[]" value="Panamericana">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-danger fw-bold mb-0">Costo ($)</label>
                        <input type="number" step="0.50" min="0" name="tramo_costo[]" class="form-control form-control-sm fw-bold text-danger border-danger tramo-costo-input" value="0.00" required oninput="recalcularMargenNuevaRutaComercial()">
                    </div>
                    <div class="col-md-1 d-flex align-items-end justify-content-center">
                        <div class="form-check" title="Admite Sobrepeso">
                            <input class="form-check-input" type="checkbox" name="tramo_admite_sp[]" value="1" checked>
                            <label class="form-check-label small" style="font-size:10px;">SP</label>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="tramo_pvp[]" class="tramo-pvp-input" value="0.00">
            `;
            container.appendChild(div);
            actualizarTitulosTramosNueva();
            recalcularMargenNuevaRutaComercial();
        }

        function eliminarTramoCardNueva(btn) {
            const card = btn.closest('.tramo-card');
            const container = document.getElementById('lista_tramos_nueva_comercial');
            const cards = container.querySelectorAll('.tramo-card');
            if (cards.length <= 1) {
                alert('La ruta comercial debe tener al menos 1 tramo de transporte.');
                return;
            }
            card.remove();
            actualizarTitulosTramosNueva();
            recalcularMargenNuevaRutaComercial();
        }

        function actualizarTitulosTramosNueva() {
            const container = document.getElementById('lista_tramos_nueva_comercial');
            if (!container) return;
            const cards = container.querySelectorAll('.tramo-card');
            cards.forEach((c, idx) => {
                const orig = c.querySelector('.tramo-origen-select')?.value || '...';
                const dest = c.querySelector('.tramo-destino-select')?.value || '...';
                const badge = c.querySelector('.tramo-badge-titulo');
                if (badge) {
                    badge.innerText = `Tramo ${idx + 1}: ${orig} ➔ ${dest}`;
                }
            });
        }

        function recalcularMargenNuevaRutaComercial() {
            const container = document.getElementById('lista_tramos_nueva_comercial');
            let costoTotal = 0;
            if (container) {
                const costoInputs = container.querySelectorAll('.tramo-costo-input');
                costoInputs.forEach(inp => {
                    costoTotal += parseFloat(inp.value) || 0;
                });
            }

            document.getElementById('nueva_costo_total_hidden').value = costoTotal.toFixed(2);
            document.getElementById('nueva_lbl_costo_total').innerText = '$' + costoTotal.toFixed(2);

            const pvp = parseFloat(document.getElementById('nueva_input_pvp').value) || 0;
            const btn = document.getElementById('btnGuardarNuevaRutaComercial');
            const alertaPerdida = document.getElementById('nueva_alerta_perdida');
            const alertaTexto = document.getElementById('nueva_alerta_perdida_texto');

            if (costoTotal > 0 && pvp < costoTotal) {
                const deficit = (costoTotal - pvp).toFixed(2);
                alertaTexto.innerText = `El precio configurado ($` + pvp.toFixed(2) + `) genera una pérdida neta de -$` + deficit + ` frente al costo de los operadores ($` + costoTotal.toFixed(2) + `). Corrija el precio para continuar.`;
                alertaPerdida.classList.remove('d-none');
                btn.disabled = true;

                document.getElementById('nueva_lbl_ganancia').innerText = '-$' + deficit;
                document.getElementById('nueva_lbl_ganancia').className = 'fs-4 fw-bold text-danger';
                document.getElementById('nueva_lbl_margen_pct').innerText = 'Pérdida';
                document.getElementById('nueva_lbl_margen_pct').className = 'text-danger fw-bold d-block mt-1';
                document.getElementById('nueva_barra_margen').style.width = '0%';
                document.getElementById('nueva_barra_margen').className = 'progress-bar bg-danger';
                return;
            }

            alertaPerdida.classList.add('d-none');
            btn.disabled = false;

            const utilidad = Math.max(0, pvp - costoTotal);
            const margenPct = (pvp > 0) ? ((utilidad / pvp) * 100).toFixed(1) : '0';

            document.getElementById('nueva_lbl_ganancia').innerText = '$' + utilidad.toFixed(2);
            document.getElementById('nueva_lbl_ganancia').className = 'fs-4 fw-bold text-success';
            document.getElementById('nueva_lbl_margen_pct').innerText = margenPct + '% margen de rentabilidad';
            document.getElementById('nueva_lbl_margen_pct').className = 'text-success fw-bold d-block mt-1';
            document.getElementById('nueva_barra_margen').style.width = Math.min(100, margenPct) + '%';
            document.getElementById('nueva_barra_margen').className = 'progress-bar bg-success';
        }

        function irATabOperadores(subtab = 'subtab-tramos') {
            const tabFlotaBtn = document.querySelector('button[data-bs-target="#tab-flota"]');
            if (tabFlotaBtn) {
                bootstrap.Tab.getOrCreateInstance(tabFlotaBtn).show();
            }
            if (subtab) {
                const subBtn = document.querySelector(`button[data-bs-target="#${subtab}"]`);
                if (subBtn) {
                    bootstrap.Tab.getOrCreateInstance(subBtn).show();
                }
            }
        }

        // Manejo de envío AJAX para Nueva Ruta Comercial
        document.addEventListener('DOMContentLoaded', function() {
            const formNueva = document.getElementById('formNuevaRutaComercial');
            if (formNueva) {
                formNueva.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btnGuardarNuevaRutaComercial');
                    btn.disabled = true;
                    btn.innerText = '⏳ Creando Ruta Comercial...';

                    const formData = new FormData(formNueva);

                    fetch(formNueva.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.innerText = '🚀 Crear Ruta Comercial';

                        if (data.status === 'success') {
                            const modalEl = document.getElementById('modalNuevaRutaComercial');
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();

                            const toastEl = document.getElementById('toastFlotaAjax');
                            if (toastEl) {
                                document.getElementById('toastFlotaAjaxMsg').innerText = data.mensaje || 'Ruta comercial creada correctamente.';
                                const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
                                toast.show();
                            }

                            setTimeout(() => {
                                window.location.href = '/suramerica2030/flota?tab=rutas&status=success';
                            }, 700);
                        } else {
                            alert(data.mensaje || 'Error al crear la ruta.');
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerText = '🚀 Crear Ruta Comercial';
                        alert('Error de comunicación: ' + err.message);
                    });
                });
            }
        });
        </script>
<!-- MODAL AJUSTE RÁPIDO DE PVP (FAIL-SAFE) -->
    <div class="modal fade text-start" id="modalAjusteRapidoPvp" tabindex="-1" aria-labelledby="modalAjusteRapidoPvpLabel" aria-hidden="true" style="z-index: 1070;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-primary text-white py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5">⚡</span>
                        <h5 class="modal-title fw-bold mb-0" id="modalAjusteRapidoPvpLabel">Ajuste Rápido de Tarifa</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formAjusteRapidoPvp" onsubmit="guardarAjusteRapidoPvp(event)">
                    <input type="hidden" id="ajuste_id_ruta" name="id_ruta" value="">
                    <input type="hidden" id="ajuste_costo_base" value="0">
                    <div class="modal-body p-4 bg-light">
                        <!-- Ruta Title -->
                        <div class="text-center mb-3">
                            <span class="badge bg-white text-dark border px-3 py-1 mb-1 fs-6" id="ajuste_badge_ruta">Origen ➔ Destino</span>
                            <div class="small text-muted" id="ajuste_subtitulo">Ajuste directo de precio al viajero</div>
                        </div>

                        <!-- Costo de Liquidación Base Card -->
                        <div class="d-flex justify-content-between align-items-center bg-white border rounded p-3 mb-3">
                            <div>
                                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Costo Liquidación Operadores</small>
                                <span class="text-danger fw-bold fs-5" id="ajuste_lbl_costo">$0.00</span>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 small">Costo Mínimo Seguro</span>
                        </div>

                        <!-- Input Nuevo PVP -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark mb-1">Nuevo Precio al Público ($)</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white fw-bold text-primary">$</span>
                                <input type="number" step="0.50" min="0" id="ajuste_input_pvp" name="precio_pasaje" class="form-control fw-bold text-primary fs-4" placeholder="0.00" required oninput="recalcularAjusteRapidoPvp()">
                            </div>
                        </div>

                        <!-- Alerta de Pérdida (Fail-Safe) -->
                        <div class="alert alert-danger py-2 px-3 mb-3 d-none" id="ajuste_alerta_perdida">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-5">⛔</span>
                                <div class="small" id="ajuste_alerta_texto">El precio es inferior al costo de operadores. No se puede guardar con pérdida.</div>
                            </div>
                        </div>

                        <!-- Ganancia & Margen Estimado -->
                        <div class="bg-white border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted fw-bold">Utilidad Neta por Pasaje:</small>
                                <span class="fw-bold fs-5 text-success" id="ajuste_lbl_utilidad">$0.00</span>
                            </div>
                            <div class="progress mb-1" style="height: 6px;">
                                <div class="progress-bar bg-success" id="ajuste_barra_margen" style="width: 0%;"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted" style="font-size: 0.72rem;">Rentabilidad comercial</small>
                                <small class="fw-bold text-success" id="ajuste_lbl_margen_pct">0.0% margen</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top p-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4" id="btnGuardarAjustePvp">
                            💾 Guardar Tarifa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script>
    function abrirModalAjustePvp(idRuta, origen, destino, pvpActual, costoTotal) {
        document.getElementById('ajuste_id_ruta').value = idRuta;
        document.getElementById('ajuste_costo_base').value = costoTotal;
        document.getElementById('ajuste_badge_ruta').innerText = origen + ' ➔ ' + destino;
        document.getElementById('ajuste_lbl_costo').innerText = '$' + Number(costoTotal).toFixed(2);
        document.getElementById('ajuste_input_pvp').value = Number(pvpActual).toFixed(2);
        
        recalcularAjusteRapidoPvp();
        
        const modalEl = document.getElementById('modalAjusteRapidoPvp');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function recalcularAjusteRapidoPvp() {
        const costo = parseFloat(document.getElementById('ajuste_costo_base').value) || 0;
        const pvp = parseFloat(document.getElementById('ajuste_input_pvp').value) || 0;
        const btn = document.getElementById('btnGuardarAjustePvp');
        const alerta = document.getElementById('ajuste_alerta_perdida');
        const alertaTexto = document.getElementById('ajuste_alerta_texto');

        if (costo > 0 && pvp < costo) {
            const deficit = (costo - pvp).toFixed(2);
            alertaTexto.innerText = `El precio configurado ($${pvp.toFixed(2)}) genera una pérdida de -$${deficit} frente al costo de liquidación ($${costo.toFixed(2)}). Corrija el precio para guardar.`;
            alerta.classList.remove('d-none');
            btn.disabled = true;

            document.getElementById('ajuste_lbl_utilidad').innerText = '-$' + deficit;
            document.getElementById('ajuste_lbl_utilidad').className = 'fw-bold fs-5 text-danger';
            document.getElementById('ajuste_lbl_margen_pct').innerText = 'Pérdida';
            document.getElementById('ajuste_lbl_margen_pct').className = 'fw-bold text-danger';
            document.getElementById('ajuste_barra_margen').style.width = '0%';
            document.getElementById('ajuste_barra_margen').className = 'progress-bar bg-danger';
            return;
        }

        alerta.classList.add('d-none');
        btn.disabled = false;

        const utilidad = Math.max(0, pvp - costo);
        const margenPct = (pvp > 0) ? ((utilidad / pvp) * 100).toFixed(1) : '0';

        document.getElementById('ajuste_lbl_utilidad').innerText = '$' + utilidad.toFixed(2);
        document.getElementById('ajuste_lbl_utilidad').className = 'fw-bold fs-5 text-success';
        document.getElementById('ajuste_lbl_margen_pct').innerText = margenPct + '% margen';
        document.getElementById('ajuste_lbl_margen_pct').className = 'fw-bold text-success';
        document.getElementById('ajuste_barra_margen').style.width = Math.min(100, margenPct) + '%';
        document.getElementById('ajuste_barra_margen').className = 'progress-bar bg-success';
    }

    function guardarAjusteRapidoPvp(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGuardarAjustePvp');
        btn.disabled = true;
        btn.innerText = 'Guardando...';

        const idRuta = document.getElementById('ajuste_id_ruta').value;
        const precio = document.getElementById('ajuste_input_pvp').value;

        const formData = new FormData();
        formData.append('id_ruta', idRuta);
        formData.append('precio_pasaje', precio);

        fetch('/suramerica2030/flota/actualizarPrecioRapido', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = '💾 Guardar Tarifa';

            if (data.status === 'success') {
                const modalEl = document.getElementById('modalAjusteRapidoPvp');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                const pvpValEl = document.getElementById('pvp_val_' + idRuta);
                if (pvpValEl) {
                    pvpValEl.innerText = '$' + Number(precio).toFixed(2);
                }

                const toastEl = document.getElementById('toastFlotaAjax');
                if (toastEl) {
                    document.getElementById('toastFlotaAjaxMsg').innerText = data.mensaje || 'Precio actualizado correctamente.';
                    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
                    toast.show();
                }

                setTimeout(() => {
                    window.location.reload();
                }, 600);
            } else {
                alert(data.mensaje || 'Error al actualizar el precio.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = '💾 Guardar Tarifa';
            alert('Error de conexión: ' + err.message);
        });
    }
    </script>
</div>
