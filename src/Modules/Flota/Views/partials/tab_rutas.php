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
                                    <div class="celda-editable-live" data-id-ruta="<?= $r['ID_RUTA'] ?>" data-pvp="<?= $r['PRECIO_PASAJE'] ?>" data-costo="<?= $r['COSTO_CALCULADO'] ?? $r['COSTO_TOTAL_RUTA'] ?? 0 ?>" onclick="iniciarEdicionInlinePvp(this)" title="Clic para editar tarifa en vivo">
                                        <span class="fs-6 fw-bold text-primary val-editable-texto">$<?= number_format($r['PRECIO_PASAJE'], 2) ?> ✏️</span>
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">precio viajero</small>
                                    </div>
                                </td>
                                <td class="col-ruta-ganancia text-end">
                                    <span class="fs-6 fw-bold text-success">$<?= number_format($r['UTILIDAD_CALCULADA'] ?? $r['UTILIDAD_PASAJE'] ?? 0, 2) ?></span>
                                    <small class="text-success d-block fw-bold" style="font-size: 0.68rem;"><?= $r['MARGEN_PCT'] ?? 0 ?>% margen</small>
                                </td>
                                <td class="col-ruta-tarifa-encomienda text-end">
                                    <div class="celda-editable-live" data-id-ruta="<?= $r['ID_RUTA'] ?>" data-encomienda="<?= $r['PRECIO_ENCOMIENDA_KG'] ?>" onclick="iniciarEdicionInlineEncomienda(this)" title="Clic para editar precio encomienda en vivo">
                                        <span class="fs-6 fw-bold text-dark val-editable-texto">$<?= number_format($r['PRECIO_ENCOMIENDA_KG'], 2) ?> ✏️</span>
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">por kilo</small>
                                    </div>
                                </td>
                                <td class="col-ruta-estado text-center">
                                    <button type="button" 
                                            class="btn btn-sm p-1 border-0 bg-transparent btn-toggle-estado-ruta" 
                                            data-id-ruta="<?= $r['ID_RUTA'] ?>" 
                                            data-estado="<?= $esActivo ? 'ACTIVO' : 'INACTIVO' ?>"
                                            onclick="conmutarEstadoRutaEnVivo(<?= $r['ID_RUTA'] ?>, this)"
                                            title="Clic para <?= $esActivo ? 'inactivar' : 'activar' ?> ruta en vivo">
                                        <?php if($esActivo): ?>
                                            <span class="badge bg-success shadow-sm px-2 py-1 fs-7">
                                                <span class="indicator-dot me-1">●</span> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary opacity-75 px-2 py-1 fs-7">
                                                <span class="indicator-dot me-1">○</span> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <!-- EDICION RAPIDA DE TARIFA -->
                                        <button type="button" class="btn btn-sm btn-outline-success fw-bold btn-abrir-tarifa-rapida" 
                                                data-id-ruta="<?= $r['ID_RUTA'] ?>" 
                                                data-origen="<?= htmlspecialchars($r['RUT_ORIGEN']) ?>" 
                                                data-destino="<?= htmlspecialchars($r['RUT_DESTINO']) ?>" 
                                                data-precio-pasaje="<?= $r['PRECIO_PASAJE'] ?>" 
                                                data-precio-encomienda="<?= $r['PRECIO_ENCOMIENDA_KG'] ?>" 
                                                data-estado="<?= $esActivo ? 'ACTIVO' : 'INACTIVO' ?>"
                                                onclick="abrirModalTarifaRapidaDinamico(this)"
                                                title="Editar Precio Rápido">
                                            💲
                                        </button>
                                        <!-- EDICION COMPLETA -->
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="abrirModalEditarRutaDinamico(<?= $r['ID_RUTA'] ?>)" 
                                                title="Configuración Avanzada">
                                            ✏️
                                        </button>
                                        <!-- ELIMINAR -->
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-abrir-eliminar-ruta" 
                                                data-id-ruta="<?= $r['ID_RUTA'] ?>" 
                                                data-origen="<?= htmlspecialchars($r['RUT_ORIGEN']) ?>" 
                                                data-destino="<?= htmlspecialchars($r['RUT_DESTINO']) ?>" 
                                                data-sentido="<?= htmlspecialchars($r['TAG_SENTIDO'] ?? 'Comercial') ?>" 
                                                data-icono-sentido="<?= $r['ICONO_SENTIDO'] ?? '🧭' ?>" 
                                                data-badge-sentido="<?= $r['BADGE_SENTIDO'] ?? 'bg-light text-secondary border' ?>"
                                                onclick="abrirModalEliminarRutaDinamico(this)"
                                                title="Eliminar Ruta">
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
        <!-- MODALES DINÁMICOS DE RUTAS COMERCIALES (ALTO RENDIMIENTO) -->
        <!-- ======================================================== -->

        <!-- MODAL DINÁMICO: EDICIÓN RÁPIDA DE TARIFA -->
        <div class="modal fade text-start" id="modalTarifaRapidaDinamico" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h6 class="modal-title fw-bold" id="tarifaRapidaTitulo">Tarifas de Ruta</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form class="form-tarifa-rapida-ajax" action="/suramerica2030/flota/guardarTarifaRapida" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id_ruta" id="tarifaRapidaIdRuta" value="">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Precio Pasaje ($)</label>
                                <input type="number" step="0.01" name="precio_pasaje" id="tarifaRapidaPrecioPasaje" class="form-control form-control-lg text-end fw-bold" value="0.00" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Precio Encomienda/Kg ($)</label>
                                <input type="number" step="0.01" name="precio_encomienda" id="tarifaRapidaPrecioEncomienda" class="form-control form-control-lg text-end fw-bold" value="0.00" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">Estado</label>
                                <select name="estado" id="tarifaRapidaEstado" class="form-select">
                                    <option value="ACTIVO">Activo (Disponible para venta)</option>
                                    <option value="INACTIVO">Inactivo (Ocultar en ventas)</option>
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

        <!-- MODAL DINÁMICO: ELIMINAR O RETIRAR RUTA -->
        <div class="modal fade text-start" id="modalEliminarRutaDinamico" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fs-6 fw-bold" id="eliminarRutaTitulo">🗑️ Eliminar o Retirar Ruta</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form class="form-eliminar-ruta-ajax" action="/suramerica2030/flota/eliminarRuta" method="POST">
                        <div class="modal-body text-center py-3">
                            <input type="hidden" name="id_ruta" id="eliminarRutaId" value="">
                            <div class="fs-1 text-danger mb-2">⚠️</div>
                            <h6 class="fw-bold mb-1" id="eliminarRutaTrayecto">Origen ➔ Destino</h6>
                            <div class="mb-3">
                                <span class="badge" id="eliminarRutaBadge">
                                    🧭 Comercial
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

        <!-- CONTENEDOR DE MODAL DE EDICIÓN AVANZADA CARGADO ON-DEMAND (AJAX) -->
        <div id="contenedorModalEditarDinamico"></div>

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
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="ejecutarAutocompletadoBFS(true)" id="btnAutocompletarBFS" title="Explorar conexiones y puentes en el motor de grafos">
                                        ⚡ Sugerir Conexiones
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="crearTramoManualVacio()">
                                        + Añadir Tramo Manual
                                    </button>
                                </div>
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
                // Verificar si hay cruce fronterizo entre el destino anterior y el origen de este tramo
                if (idx > 0) {
                    const prevDest = tramos[idx - 1].destino;
                    const currOrig = t.origen;
                    const puente = (typeof detectarPuenteFronterizoJS === 'function') ? detectarPuenteFronterizoJS(prevDest, currOrig) : null;
                    if (puente) {
                        const puenteDiv = document.createElement('div');
                        puenteDiv.className = 'timeline-tramo-connector d-flex align-items-center justify-content-center my-2';
                        puenteDiv.innerHTML = `
                            <span class="badge px-3 py-1 shadow-sm font-monospace">
                                🌉 Cruce Internacional: <strong>${puente.nombre}</strong> (${prevDest} ⇄ ${currOrig})
                            </span>
                        `;
                        container.appendChild(puenteDiv);
                    }
                }

                const desfase = typeof t.dia_desfase !== 'undefined' ? parseInt(t.dia_desfase) : 0;
                const div = document.createElement('div');
                div.className = 'tramo-card card border p-3 mb-2 bg-light shadow-sm';
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary px-2 py-1 tramo-badge-titulo">Tramo ${idx + 1}: ${t.origen} ➔ ${t.destino}</span>
                            ${t.tiempo_estimado_horas ? `<span class="badge bg-light text-secondary border font-monospace" style="font-size:10px;">⏱️ ${t.tiempo_estimado_horas}h</span>` : ''}
                            <span class="badge bg-info-subtle text-info border font-monospace" style="font-size:10px;">📅 Desfase: +${desfase}d</span>
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
                        <div class="col-md-2">
                            <label class="form-label small text-secondary fw-bold mb-0">📅 Desfase Día</label>
                            <select name="tramo_dia_desfase[]" class="form-select form-select-sm fw-bold border-secondary text-secondary tramo-desfase-select" title="Día de operación respecto a la salida original">
                                <option value="0" ${desfase === 0 ? 'selected' : ''}>+0d (Mismo día)</option>
                                <option value="1" ${desfase === 1 ? 'selected' : ''}>+1d (Día +1)</option>
                                <option value="2" ${desfase === 2 ? 'selected' : ''}>+2d (Día +2)</option>
                                <option value="3" ${desfase === 3 ? 'selected' : ''}>+3d (Día +3)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-0">Operador</label>
                            <select name="tramo_id_operador[]" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const opName = this.options[this.selectedIndex].getAttribute('data-nombre') || this.options[this.selectedIndex].text; this.closest('.tramo-card').querySelector('input[name=\\'tramo_operador[]\\']').value = opName;">
                                <option value="">⭐ Automático (Cualquiera)</option>
                                ${HTML_OPCIONES_OPERADORES}
                            </select>
                            <input type="hidden" name="tramo_operador[]" value="${t.operador || 'Panamericana'}">
                        </div>
                        <div class="col-md-1">
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
                    <input type="hidden" name="tramo_id_catalogo[]" value="${t.id_tramo_catalogo || ''}">
                    <input type="hidden" name="tramo_id_oferta[]" value="${t.id_oferta || ''}">
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

            // Sincronizar días viables deducidos por el motor de grafo
            if (data.dias_salida_numeros && Array.isArray(data.dias_salida_numeros) && data.dias_salida_numeros.length > 0) {
                const chks = document.querySelectorAll('#modalNuevaRutaComercial input[name="dias_salida[]"]');
                chks.forEach(chk => {
                    chk.checked = data.dias_salida_numeros.includes(parseInt(chk.value));
                });
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
                    <div class="col-md-2">
                        <label class="form-label small text-secondary fw-bold mb-0">📅 Desfase Día</label>
                        <select name="tramo_dia_desfase[]" class="form-select form-select-sm fw-bold border-secondary text-secondary tramo-desfase-select">
                            <option value="0" selected>+0d (Mismo día)</option>
                            <option value="1">+1d (Día +1)</option>
                            <option value="2">+2d (Día +2)</option>
                            <option value="3">+3d (Día +3)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-0">Operador</label>
                        <select name="tramo_id_operador[]" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const opName = this.options[this.selectedIndex].getAttribute('data-nombre') || this.options[this.selectedIndex].text; this.closest('.tramo-card').querySelector('input[name=\\'tramo_operador[]\\']').value = opName;">
                            ${HTML_OPCIONES_OPERADORES}
                        </select>
                        <input type="hidden" name="tramo_operador[]" value="Panamericana">
                    </div>
                    <div class="col-md-1">
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
                <input type="hidden" name="tramo_id_catalogo[]" value="">
                <input type="hidden" name="tramo_id_oferta[]" value="">
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
                    <div class="col-md-2">
                        <label class="form-label small text-secondary fw-bold mb-0">📅 Desfase Día</label>
                        <select name="tramo_dia_desfase[]" class="form-select form-select-sm fw-bold border-secondary text-secondary tramo-desfase-select">
                            <option value="0" selected>+0d (Mismo día)</option>
                            <option value="1">+1d (Día +1)</option>
                            <option value="2">+2d (Día +2)</option>
                            <option value="3">+3d (Día +3)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-0">Operador</label>
                        <select name="tramo_id_operador[]" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const opName = this.options[this.selectedIndex].getAttribute('data-nombre') || this.options[this.selectedIndex].text; this.closest('.tramo-card').querySelector('input[name=\\'tramo_operador[]\\']').value = opName;">
                            <option value="">⭐ Automático (Cualquiera)</option>
                            ${HTML_OPCIONES_OPERADORES}
                        </select>
                        <input type="hidden" name="tramo_operador[]" value="Panamericana">
                    </div>
                    <div class="col-md-1">
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
                <input type="hidden" name="tramo_id_catalogo[]" value="">
                <input type="hidden" name="tramo_id_oferta[]" value="">
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
                document.getElementById('nueva_lbl_margen_pct').innerText = '⛔ Pérdida Económica';
                document.getElementById('nueva_lbl_margen_pct').className = 'text-danger fw-bold d-block mt-1';
                document.getElementById('nueva_barra_margen').style.width = '0%';
                document.getElementById('nueva_barra_margen').className = 'progress-bar bg-danger';
                return;
            }

            alertaPerdida.classList.add('d-none');
            btn.disabled = false;

            const utilidad = Math.max(0, pvp - costoTotal);
            const margenPct = (pvp > 0) ? ((utilidad / pvp) * 100).toFixed(1) : '0';
            const numMargen = parseFloat(margenPct);

            document.getElementById('nueva_lbl_ganancia').innerText = '$' + utilidad.toFixed(2);
            
            // Semáforo Financiero Adaptativo
            if (numMargen >= 18) {
                document.getElementById('nueva_lbl_ganancia').className = 'fs-4 fw-bold text-success';
                document.getElementById('nueva_lbl_margen_pct').innerText = `✅ ${margenPct}% margen rentable`;
                document.getElementById('nueva_lbl_margen_pct').className = 'text-success fw-bold d-block mt-1';
                document.getElementById('nueva_barra_margen').className = 'progress-bar bg-success';
            } else if (numMargen > 0) {
                document.getElementById('nueva_lbl_ganancia').className = 'fs-4 fw-bold text-warning';
                document.getElementById('nueva_lbl_margen_pct').innerText = `⚠️ ${margenPct}% margen ajustado`;
                document.getElementById('nueva_lbl_margen_pct').className = 'text-warning fw-bold d-block mt-1';
                document.getElementById('nueva_barra_margen').className = 'progress-bar bg-warning';
            } else {
                document.getElementById('nueva_lbl_ganancia').className = 'fs-4 fw-bold text-secondary';
                document.getElementById('nueva_lbl_margen_pct').innerText = `0% margen (Al costo)`;
                document.getElementById('nueva_lbl_margen_pct').className = 'text-secondary fw-bold d-block mt-1';
                document.getElementById('nueva_barra_margen').className = 'progress-bar bg-secondary';
            }

            document.getElementById('nueva_barra_margen').style.width = Math.min(100, numMargen) + '%';
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

        // Manejo de envío AJAX para Nueva Ruta Comercial con validación de nodos fronterizos
        document.addEventListener('DOMContentLoaded', function() {
            const formNueva = document.getElementById('formNuevaRutaComercial');
            if (formNueva) {
                formNueva.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const selOrig = formNueva.querySelector('[name="origen"]');
                    const selDest = formNueva.querySelector('[name="destino"]');
                    if (selOrig && selDest) {
                        const origRuta = (selOrig.value || '').trim().toUpperCase();
                        const destRuta = (selDest.value || '').trim().toUpperCase();
                        const tramoOrigs = Array.from(formNueva.querySelectorAll('.tramo-origen-select')).map(s => (s.value || '').trim().toUpperCase());
                        const tramoDests = Array.from(formNueva.querySelectorAll('.tramo-destino-select')).map(s => (s.value || '').trim().toUpperCase());

                        if (tramoOrigs.length > 0 && tramoDests.length > 0) {
                            const primerOrig = tramoOrigs[0];
                            const ultimoDest = tramoDests[tramoDests.length - 1];

                            const conectaOrig = (typeof sonCiudadesConectablesJS === 'function') 
                                ? sonCiudadesConectablesJS(origRuta, primerOrig) 
                                : (origRuta === primerOrig);

                            if (primerOrig && origRuta && !conectaOrig) {
                                alert(`❌ Inconsistencia: El primer tramo inicia en "${primerOrig}", pero la ruta parte de "${origRuta}". Deben coincidir exactamente o estar unidas por un paso fronterizo.`);
                                return;
                            }

                            const conectaDest = (typeof sonCiudadesConectablesJS === 'function') 
                                ? sonCiudadesConectablesJS(ultimoDest, destRuta) 
                                : (destRuta === ultimoDest);

                            if (ultimoDest && destRuta && !conectaDest) {
                                alert(`❌ Inconsistencia: El último tramo finaliza en "${ultimoDest}", pero la ruta llega a "${destRuta}". Deben coincidir exactamente o estar unidas por un paso fronterizo.`);
                                return;
                            }
                        }
                    }

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

                            formNueva.reset();

                            if (typeof mostrarToastFlota === 'function') {
                                mostrarToastFlota(data.mensaje || 'Ruta comercial creada correctamente.', 'success');
                            } else {
                                const toastEl = document.getElementById('toastFlotaAjax');
                                if (toastEl) {
                                    document.getElementById('toastFlotaAjaxMsg').innerText = data.mensaje || 'Ruta comercial creada correctamente.';
                                    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
                                    toast.show();
                                }
                            }

                            if (Array.isArray(data.rutas) && data.rutas.length > 0) {
                                data.rutas.forEach(r => {
                                    if (typeof renderNuevaFilaRutaEnTabla === 'function') {
                                        renderNuevaFilaRutaEnTabla(r);
                                    }
                                });
                                if (typeof actualizarKpisRutasEnDOM === 'function') {
                                    actualizarKpisRutasEnDOM();
                                }
                                if (typeof aplicarFiltrosYPaginarRutas === 'function') {
                                    aplicarFiltrosYPaginarRutas(1);
                                }
                            } else if (window.LiveData && typeof LiveData.refreshContainer === 'function') {
                                LiveData.refreshContainer('#tablaRutas', null, () => {
                                    if (typeof actualizarKpisRutasEnDOM === 'function') actualizarKpisRutasEnDOM();
                                    if (typeof aplicarFiltrosYPaginarRutas === 'function') aplicarFiltrosYPaginarRutas(1);
                                });
                            }
                        } else {
                            if (typeof mostrarToastFlota === 'function') {
                                mostrarToastFlota(data.mensaje || 'Error al crear la ruta.', 'danger');
                            } else {
                                alert(data.mensaje || 'Error al crear la ruta.');
                            }
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

                if (data.ruta && typeof actualizarFilaRutaEnDOM === 'function') {
                    actualizarFilaRutaEnDOM(data.ruta);
                } else {
                    const fila = document.getElementById('fila_ruta_' + idRuta);
                    if (fila) {
                        const celdaPvp = fila.querySelector('.celda-editable-live[data-pvp]');
                        if (celdaPvp) {
                            celdaPvp.setAttribute('data-pvp', Number(precio).toFixed(2));
                            const txt = celdaPvp.querySelector('.val-editable-texto');
                            if (txt) txt.textContent = '$' + Number(precio).toFixed(2) + ' ✏️';
                        }
                    }
                }
                const fila = document.getElementById('fila_ruta_' + idRuta);
                if (fila && window.LiveData && typeof LiveData.highlight === 'function') {
                    LiveData.highlight(fila);
                }

                if (typeof mostrarToastFlota === 'function') {
                    mostrarToastFlota(data.mensaje || 'Precio actualizado correctamente.', 'success');
                } else {
                    const toastEl = document.getElementById('toastFlotaAjax');
                    if (toastEl) {
                        document.getElementById('toastFlotaAjaxMsg').innerText = data.mensaje || 'Precio actualizado correctamente.';
                        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
                        toast.show();
                    }
                }
            } else {
                if (typeof mostrarToastFlota === 'function') {
                    mostrarToastFlota(data.mensaje || 'Error al actualizar el precio.', 'danger');
                } else {
                    alert(data.mensaje || 'Error al actualizar el precio.');
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = '💾 Guardar Tarifa';
            alert('Error de conexión: ' + err.message);
        });
    }

    function abrirModalTarifaRapidaDinamico(btn) {
        const id = btn.dataset.idRuta;
        const origen = btn.dataset.origen;
        const destino = btn.dataset.destino;
        const precioPasaje = btn.dataset.precioPasaje;
        const precioEncomienda = btn.dataset.precioEncomienda;
        const estado = btn.dataset.estado;

        const modalEl = document.getElementById('modalTarifaRapidaDinamico');
        if (!modalEl) return;
        document.getElementById('tarifaRapidaTitulo').textContent = `Tarifas: ${origen} ➔ ${destino}`;
        document.getElementById('tarifaRapidaIdRuta').value = id;
        document.getElementById('tarifaRapidaPrecioPasaje').value = precioPasaje;
        document.getElementById('tarifaRapidaPrecioEncomienda').value = precioEncomienda;
        document.getElementById('tarifaRapidaEstado').value = estado;

        vincularFormTarifaRapidaDinamico();
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function vincularFormTarifaRapidaDinamico() {
        const modalEl = document.getElementById('modalTarifaRapidaDinamico');
        if (!modalEl) return;
        const form = modalEl.querySelector('form.form-tarifa-rapida-ajax');
        if (!form || form.dataset.boundAjax) return;
        form.dataset.boundAjax = '1';

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const origText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Actualizando...';
            }

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origText;
                }
                if (res.status === 'success') {
                    const bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                    if (res.ruta && typeof actualizarFilaRutaEnDOM === 'function') {
                        actualizarFilaRutaEnDOM(res.ruta);
                    }
                    if (typeof mostrarToastFlota === 'function') {
                        mostrarToastFlota(res.mensaje || 'Tarifa actualizada correctamente.', 'success');
                    }
                } else {
                    alert(res.mensaje || 'Error al actualizar tarifa.');
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origText;
                }
                alert('Error de red: ' + err.message);
            });
        });
    }

    function abrirModalEliminarRutaDinamico(btn) {
        const id = btn.dataset.idRuta;
        const origen = btn.dataset.origen;
        const destino = btn.dataset.destino;
        const sentido = btn.dataset.sentido || 'Comercial';
        const icono = btn.dataset.iconoSentido || '🧭';
        const badgeClass = btn.dataset.badgeSentido || 'bg-light text-secondary border';

        const modalEl = document.getElementById('modalEliminarRutaDinamico');
        if (!modalEl) return;
        document.getElementById('eliminarRutaTitulo').textContent = `🗑️ Eliminar o Retirar Ruta #${id}`;
        document.getElementById('eliminarRutaId').value = id;
        document.getElementById('eliminarRutaTrayecto').textContent = `${origen} ➔ ${destino}`;
        const badgeEl = document.getElementById('eliminarRutaBadge');
        badgeEl.className = 'badge ' + badgeClass;
        badgeEl.textContent = `${icono} ${sentido}`;

        vincularFormEliminarRutaDinamico();
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function vincularFormEliminarRutaDinamico() {
        const modalEl = document.getElementById('modalEliminarRutaDinamico');
        if (!modalEl) return;
        const form = modalEl.querySelector('form.form-eliminar-ruta-ajax');
        if (!form || form.dataset.boundAjax) return;
        form.dataset.boundAjax = '1';

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const origText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Eliminando...';
            }

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origText;
                }
                if (res.status === 'success') {
                    const bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                    const idRuta = formData.get('id_ruta');
                    const fila = document.getElementById('fila_ruta_' + idRuta);
                    if (res.accion === 'eliminada') {
                        if (fila) fila.remove();
                    } else {
                        if (fila) {
                            const colEstado = fila.querySelector('.col-ruta-estado');
                            if (colEstado) {
                                colEstado.innerHTML = `
                                    <button type="button" class="btn btn-sm p-1 border-0 bg-transparent btn-toggle-estado-ruta" 
                                            data-id-ruta="${idRuta}" data-estado="INACTIVO" 
                                            onclick="conmutarEstadoRutaEnVivo(${idRuta}, this)" title="Clic para activar ruta en vivo">
                                        <span class="badge bg-secondary opacity-75 px-2 py-1 fs-7">
                                            <span class="indicator-dot me-1">○</span> Inactivo
                                        </span>
                                    </button>
                                `;
                            }
                        }
                    }
                    if (typeof mostrarToastFlota === 'function') {
                        mostrarToastFlota(res.mensaje || 'Ruta procesada con éxito.', 'success');
                    }
                    if (typeof aplicarFiltrosYPaginarRutas === 'function') {
                        aplicarFiltrosYPaginarRutas();
                    }
                } else {
                    alert(res.mensaje || 'Error al eliminar la ruta.');
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origText;
                }
                alert('Error de red: ' + err.message);
            });
        });
    }

    function abrirModalEditarRutaDinamico(idRuta) {
        let cont = document.getElementById('contenedorModalEditarDinamico');
        if (!cont) {
            cont = document.createElement('div');
            cont.id = 'contenedorModalEditarDinamico';
            document.body.appendChild(cont);
        }

        const existente = document.getElementById('modalEditarRuta' + idRuta);
        if (existente) {
            const modal = bootstrap.Modal.getOrCreateInstance(existente);
            modal.show();
            return;
        }

        if (typeof mostrarToastFlota === 'function') {
            mostrarToastFlota('Cargando configuración de ruta #' + idRuta + '...', 'info');
        }

        fetch('/suramerica2030/flota/obtenerModalEditar?id=' + idRuta, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('Error al cargar la ruta (HTTP ' + res.status + ')');
            return res.text();
        })
        .then(html => {
            cont.innerHTML = html;
            const modalEl = document.getElementById('modalEditarRuta' + idRuta);
            if (modalEl) {
                vincularFormEditarRutaAjax(modalEl);
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            } else {
                alert('No se pudo inicializar la ventana de edición.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error: ' + err.message);
        });
    }

    function vincularFormEditarRutaAjax(modalEl) {
        const form = modalEl.querySelector('form.form-editar-ruta-ajax');
        if (!form || form.dataset.boundAjax) return;
        form.dataset.boundAjax = '1';

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const origText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '💾 Guardando...';
            }

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origText;
                }
                if (res.status === 'success') {
                    const bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                    if (res.ruta && typeof actualizarFilaRutaEnDOM === 'function') {
                        actualizarFilaRutaEnDOM(res.ruta);
                    }
                    if (typeof mostrarToastFlota === 'function') {
                        mostrarToastFlota(res.mensaje || 'Ruta guardada correctamente.', 'success');
                    }
                } else {
                    alert(res.mensaje || 'Error al guardar la ruta.');
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origText;
                }
                alert('Error de red: ' + err.message);
            });
        });
    }

    window.abrirModalTarifaRapidaDinamico = abrirModalTarifaRapidaDinamico;
    window.abrirModalEliminarRutaDinamico = abrirModalEliminarRutaDinamico;
    window.abrirModalEditarRutaDinamico = abrirModalEditarRutaDinamico;
    </script>
</div>
