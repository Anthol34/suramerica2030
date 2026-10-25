<?php
if (!isset($generarOpcionesCiudades) || !is_callable($generarOpcionesCiudades)) {
    $ciudadesCatalogo = $ciudadesCatalogo ?? [];
    $generarOpcionesCiudades = function($idUnused = null, $valorSeleccionado = '', $placeholder = '-- Seleccionar Ciudad --') use ($ciudadesCatalogo) {
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
            $html .= '<option value="' . htmlspecialchars($valorSeleccionado) . '" selected>' . htmlspecialchars($valorSeleccionado) . ' (Registrada)</option>';
            $html .= '</optgroup>';
        }
        return $html;
    };
}

if (!isset($generarOpcionesOperadores) || !is_callable($generarOpcionesOperadores)) {
    $operadores = $operadores ?? [];
    $generarOpcionesOperadores = function($idSeleccionado = 1, $placeholder = null) use ($operadores) {
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

$esActivo = ($r['ESTADO'] ?? 'ACTIVO') === 'ACTIVO';
$diasArr = explode(',', $r['DIAS_SALIDA'] ?? '');
$mapaDias = $mapaDias ?? ['1'=>'Lun', '2'=>'Mar', '3'=>'Mié', '4'=>'Jue', '5'=>'Vie', '6'=>'Sáb', '7'=>'Dom'];
?>
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
