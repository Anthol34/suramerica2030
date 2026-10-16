<style>
/* Tarjetas de Filtro Rápido en Rutas (estilo Finanzas) */
.tarjeta-filtro-rapido-ruta:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}
.tarjeta-filtro-rapido-ruta.active {
    border-color: #0d6efd !important;
    background-color: #e7f1ff !important;
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.2) !important;
}

/* Fix definitivo de scroll en modales de configuración de rutas */
.modal-dialog-scrollable form.modal-content {
    max-height: 90vh !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}
.modal-dialog-scrollable .modal-body {
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="text-primary mb-0 fw-bold">Gestión de Corredores y Flota</h3>
        <p class="text-muted small mb-0">Configura itinerarios maestros, tarifas comerciales y la flota de buses de Suramérica 2030.</p>
    </div>
</div>

<?php if(isset($_GET['status'])): 
    $statusType = $_GET['status'];
    $alertClass = 'alert-success';
    if (strpos($statusType, 'error') !== false) {
        $alertClass = 'alert-danger';
    } elseif ($statusType === 'success_archived') {
        $alertClass = 'alert-warning border-warning';
    }
?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-sm">
        <?php 
            if($statusType == 'success') echo '✅ Registro guardado o generado correctamente.';
            elseif($statusType == 'success_edit') echo '✅ Cambios y tarifas actualizados correctamente.';
            elseif($statusType == 'success_delete') echo '🗑️ Elemento eliminado permanentemente del sistema.';
            elseif($statusType == 'success_archived') echo '📦 ' . htmlspecialchars($_GET['msg'] ?? 'La ruta fue archivada como INACTIVA para proteger el historial contable.');
            elseif($statusType == 'success_delete_bus') echo '🗑️ Unidad y Socio eliminados correctamente del sistema.';
            else echo '❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Ocurrió un error en la operación.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php 
    $tabActiva = $_GET['tab'] ?? 'corredor';
    $mapaDias = ['1'=>'Lun', '2'=>'Mar', '3'=>'Mié', '4'=>'Jue', '5'=>'Vie', '6'=>'Sáb', '7'=>'Dom'];
    $rutasPorCorredor = [];
    foreach ($rutas as $r) {
        $cid = $r['ID_CORREDOR'] ?? 0;
        $rutasPorCorredor[$cid] = ($rutasPorCorredor[$cid] ?? 0) + 1;
    }

    // Helper para generar opciones de ciudades basadas en las paradas del corredor
    $paradasPorCorredor = $paradasPorCorredor ?? [];
    $ciudadesCatalogo = $ciudadesCatalogo ?? [];

    $generarOpcionesCiudades = function($idCorredor, $valorSeleccionado = '', $placeholder = '-- Seleccionar Ciudad --') use ($paradasPorCorredor, $ciudadesCatalogo) {
        $html = '<option value="">' . htmlspecialchars($placeholder) . '</option>';
        $paradas = $paradasPorCorredor[$idCorredor] ?? [];
        $encontrado = false;
        $valNorm = mb_strtolower(trim((string)$valorSeleccionado));

        if (!empty($paradas)) {
            $html .= '<optgroup label="📍 Paradas del Corredor (en orden de viaje)">';
            foreach ($paradas as $idx => $p) {
                $isSel = ($valNorm !== '' && mb_strtolower(trim($p['CIUDAD'])) === $valNorm);
                if ($isSel) $encontrado = true;
                $tipo = !empty($p['TIPO_PARADA']) ? ' [' . str_replace('_', ' ', $p['TIPO_PARADA']) . ']' : '';
                $html .= '<option value="' . htmlspecialchars($p['CIUDAD']) . '" ' . ($isSel ? 'selected' : '') . '>'
                      . ($idx + 1) . '. ' . htmlspecialchars($p['CIUDAD']) . ' (' . htmlspecialchars($p['PAIS']) . ')' . htmlspecialchars($tipo) . '</option>';
            }
            $html .= '</optgroup>';
        }
        if ($valorSeleccionado !== '' && !$encontrado) {
            $html .= '<optgroup label="⚠️ Ciudad Actual Configurada">';
            $html .= '<option value="' . htmlspecialchars($valorSeleccionado) . '" selected>' . htmlspecialchars($valorSeleccionado) . ' (Registrada)</option>';
            $html .= '</optgroup>';
        }

        return $html;
    };

    // Helper para generar opciones de operadores agrupados por tipo
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
?>

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border" id="flotaTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'corredor') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-corredor" type="button">
            🌐 Corredor e Itinerario Troncal
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'rutas') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-rutas" type="button">
            📍 Rutas Comerciales y Tarifas <span class="badge bg-secondary ms-1"><?= count($rutas) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'buses' || $tabActiva == 'operadores') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-buses" type="button">
            🚌 Buses y Operadores / Socios <span class="badge bg-secondary ms-1"><?= count($buses) ?> buses · <?= count($operadores) ?> operadores</span>
        </button>
    </li>
</ul>

<div class="tab-content">
    
    <!-- ======================================================== -->
    <!-- PESTAÑA 1: CORREDOR E ITINERARIO TRONCAL MAESTRO        -->
    <!-- ======================================================== -->
    <div class="tab-pane fade <?= ($tabActiva == 'corredor') ? 'show active' : '' ?>" id="tab-corredor">
        <!-- BARRA DE SELECCION Y CREACION DE CORREDORES -->
        <div class="card shadow-sm border-0 mb-3 bg-white">
            <div class="card-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 520px;">
                    <label class="fw-bold text-dark text-nowrap mb-0">🌐 Corredor Activo:</label>
                    <select class="form-select form-select-sm border-primary fw-bold" onchange="window.location.href='/suramerica2030/flota?tab=corredor&corredor_id=' + this.value;">
                        <?php foreach($corredores as $c): ?>
                            <option value="<?= $c['ID_CORREDOR'] ?>" <?= ($corredorActivo && $corredorActivo['ID_CORREDOR'] == $c['ID_CORREDOR']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['CODIGO'] ?? 'COR') ?> - <?= htmlspecialchars($c['NOMBRE']) ?> (<?= htmlspecialchars($c['ORIGEN_CABECERA']) ?> ➔ <?= htmlspecialchars($c['DESTINO_TERMINAL']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearCorredor">
                        + Crear Nuevo Corredor
                    </button>
                    <?php if(count($corredores) > 1 && $corredorActivo): ?>
                        <form action="/suramerica2030/flota/eliminarCorredor" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar el corredor <?= htmlspecialchars($corredorActivo['NOMBRE']) ?> con todas sus paradas?');" class="d-inline">
                            <input type="hidden" name="id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar este corredor">
                                🗑️ Eliminar Corredor
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if($corredorActivo): ?>
            <!-- CARD CABECERA CORREDOR -->
            <div class="card shadow-sm border-0 border-top border-primary border-4 mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <span class="badge bg-primary fs-6 px-3 py-1 mb-1"><?= htmlspecialchars($corredorActivo['CODIGO'] ?? 'COR-01') ?></span>
                            <h4 class="fw-bold text-dark mb-0 d-inline-block ms-2"><?= htmlspecialchars($corredorActivo['NOMBRE']) ?></h4>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditarCorredor">
                                ⚙️ Modificar Políticas del Corredor
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <small class="text-muted text-uppercase fw-bold d-block mb-1">Trayecto Troncal</small>
                                <strong class="fs-5 text-dark"><?= htmlspecialchars($corredorActivo['ORIGEN_CABECERA']) ?> ➔ <?= htmlspecialchars($corredorActivo['DESTINO_TERMINAL']) ?></strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <small class="text-muted text-uppercase fw-bold d-block mb-1">Frecuencia Fija</small>
                                <?php 
                                    $diasArrCor = explode(',', $corredorActivo['DIAS_SALIDA'] ?? '');
                                    $diasTxtCor = [];
                                    foreach($diasArrCor as $d) { if(isset($mapaDias[$d])) $diasTxtCor[] = $mapaDias[$d]; }
                                ?>
                                <strong class="text-primary fs-6"><?= empty($diasTxtCor) ? 'Sin definir' : implode(', ', $diasTxtCor) ?></strong>
                                <div class="small text-muted">Hora: <span class="fw-bold text-dark"><?= $corredorActivo['HORA_SALIDA'] ?></span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <small class="text-muted text-uppercase fw-bold d-block mb-1">Políticas de Equipaje</small>
                                <div>Libre: <strong class="text-dark"><?= number_format($corredorActivo['LIMITE_EQUIPAJE_KG'], 1) ?> Kg</strong></div>
                                <div>Sobrepeso: <strong class="text-danger">$<?= number_format($corredorActivo['PRECIO_SOBREPESO_KG'], 2) ?>/Kg</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <small class="text-muted text-uppercase fw-bold d-block mb-1">Políticas Comerciales</small>
                                <div>Comisión Valor: <strong class="text-success"><?= number_format($corredorActivo['COMISION_VALOR'], 1) ?> %</strong></div>
                                <div>Tarifa Sobre: <strong class="text-dark">$<?= number_format($corredorActivo['PRECIO_SOBRE'], 2) ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- SECUENCIA DE PARADAS -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold text-primary">📍 Secuencia de Paradas (Columna Vertebral)</h6>
                            <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAgregarParada">
                                + Añadir Escala
                            </button>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Ciudad / Parada</th>
                                        <th>País</th>
                                        <th>Tipo de Parada</th>
                                        <th>Tiempo Estimado</th>
                                        <th class="text-center" style="width: 120px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($paradas as $p): ?>
                                        <tr>
                                            <td class="fw-bold text-muted"><?= $p['ORDEN'] ?></td>
                                            <td>
                                                <strong class="fs-6 text-dark"><?= htmlspecialchars($p['CIUDAD']) ?></strong>
                                            </td>
                                            <td>
                                                <?php if($p['PAIS'] === 'ECUADOR'): ?>
                                                    <span class="badge bg-warning text-dark">🇪🇨 Ecuador</span>
                                                <?php elseif($p['PAIS'] === 'COLOMBIA'): ?>
                                                    <span class="badge bg-primary text-white">🇨🇴 Colombia</span>
                                                <?php elseif($p['PAIS'] === 'VENEZUELA'): ?>
                                                    <span class="badge bg-danger text-white">🇻🇪 Venezuela</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($p['PAIS']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($p['TIPO_PARADA'] === 'CABECERA'): ?>
                                                    <span class="badge bg-success">🏁 Origen Cabecera</span>
                                                <?php elseif($p['TIPO_PARADA'] === 'TERMINAL'): ?>
                                                    <span class="badge bg-dark">🏁 Terminal Final</span>
                                                <?php elseif($p['TIPO_PARADA'] === 'TRANSBORDO_FRONTERA'): ?>
                                                    <span class="badge bg-info text-dark">🛂 Transbordo Fronterizo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border">📍 Escala Intermedia</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">+<?= $p['TIEMPO_ESTIMADO_HORAS'] ?> hrs de salida</small>
                                            </td>
                                            <td class="text-center">
                                                <?php if($p['TIPO_PARADA'] !== 'CABECERA' && $p['TIPO_PARADA'] !== 'TERMINAL'): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <form action="/suramerica2030/flota/moverParada" method="POST" class="d-inline">
                                                            <input type="hidden" name="id_parada" value="<?= $p['ID_CORREDOR_PARADA'] ?>">
                                                            <input type="hidden" name="direccion" value="subir">
                                                            <input type="hidden" name="id_corredor_retorno" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Subir parada" <?= ($p['ORDEN'] <= 2) ? 'disabled' : '' ?>>▲</button>
                                                        </form>
                                                        <form action="/suramerica2030/flota/moverParada" method="POST" class="d-inline">
                                                            <input type="hidden" name="id_parada" value="<?= $p['ID_CORREDOR_PARADA'] ?>">
                                                            <input type="hidden" name="direccion" value="bajar">
                                                            <input type="hidden" name="id_corredor_retorno" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Bajar parada" <?= ($p['ORDEN'] >= count($paradas) - 1) ? 'disabled' : '' ?>>▼</button>
                                                        </form>
                                                        <form action="/suramerica2030/flota/eliminarParada" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas quitar la parada de <?= htmlspecialchars($p['CIUDAD']) ?>?');">
                                                            <input type="hidden" name="id_parada" value="<?= $p['ID_CORREDOR_PARADA'] ?>">
                                                            <input type="hidden" name="id_corredor_retorno" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Eliminar parada">✕</button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">Fija</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TRAMOS OPERATIVOS Y ASIGNACIÓN DE BUSES -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-dark">🚌 Asignación de Unidades por País</h6>
                            <small class="text-muted">Unidades asignadas para los transbordos internacionales.</small>
                        </div>
                        <div class="card-body">
                            <?php foreach($tramosOperativos as $top): ?>
                                <div class="border rounded p-3 mb-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-primary"><?= htmlspecialchars($top['NOMBRE_TRAMO']) ?></strong>
                                        <small class="badge bg-secondary"><?= htmlspecialchars($top['PAIS']) ?></small>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        <?= htmlspecialchars($top['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($top['DESTINO_TRAMO']) ?>
                                    </div>

                                    <form action="/suramerica2030/flota/guardarBusTramo" method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="id_tramo" value="<?= $top['ID_CORREDOR_TRAMO'] ?>">
                                        <input type="hidden" name="id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                        <select name="id_bus" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">-- Sin unidad asignada --</option>
                                            <?php foreach($buses as $bus): ?>
                                                <option value="<?= $bus['ID_BUS'] ?>" <?= ($top['ID_BUS_DEFAULT'] == $bus['ID_BUS']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($bus['BUS_PLACA']) ?> - <?= htmlspecialchars($bus['BUS_MARCA']) ?> (<?= $bus['BUS_ASIENTOS'] ?> As.)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>
                            <?php endforeach; ?>

                            <div class="alert alert-info py-2 px-3 mb-0 small">
                                💡 Al vender un pasaje internacional, el primer tramo toma la unidad asignada y los siguientes tramos se confirman al llegar a la frontera.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL EDITAR CORREDOR -->
            <div class="modal fade" id="modalEditarCorredor" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title fw-bold">Políticas y Parámetros del Corredor</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/suramerica2030/flota/guardarCorredor" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Código del Corredor</label>
                                        <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($corredorActivo['CODIGO']) ?>" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Nombre Comercial</label>
                                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($corredorActivo['NOMBRE']) ?>" required>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Cabecera de Origen</label>
                                        <input type="text" name="origen_cabecera" class="form-control" value="<?= htmlspecialchars($corredorActivo['ORIGEN_CABECERA']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Terminal Final</label>
                                        <input type="text" name="destino_terminal" class="form-control" value="<?= htmlspecialchars($corredorActivo['DESTINO_TERMINAL']) ?>" required>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Días de Salida Fija</label>
                                        <div class="d-flex flex-wrap gap-2 pt-1">
                                            <?php foreach($mapaDias as $numDia => $txtDia): ?>
                                                <label class="btn btn-outline-secondary btn-sm">
                                                    <input type="checkbox" name="dias[]" value="<?= $numDia ?>" <?= in_array((string)$numDia, $diasArrCor) ? 'checked' : '' ?>> <?= $txtDia ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Hora de Salida Cabecera</label>
                                        <input type="time" name="hora_salida" class="form-control" value="<?= $corredorActivo['HORA_SALIDA'] ?>" required>
                                    </div>
                                </div>

                                <h6 class="text-secondary border-bottom pb-1 mt-4">Políticas Globales Heredadas</h6>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Equipaje Libre (Kg)</label>
                                        <input type="number" step="0.1" name="limite_equipaje" class="form-control" value="<?= $corredorActivo['LIMITE_EQUIPAJE_KG'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sobrepeso ($/Kg)</label>
                                        <input type="number" step="0.01" name="precio_sobrepeso" class="form-control" value="<?= $corredorActivo['PRECIO_SOBREPESO_KG'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Precio Sobre ($)</label>
                                        <input type="number" step="0.01" name="precio_sobre" class="form-control" value="<?= $corredorActivo['PRECIO_SOBRE'] ?>" required>
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Comisión Envíos de Valores (%)</label>
                                        <input type="number" step="0.10" name="comision_valor" class="form-control" value="<?= $corredorActivo['COMISION_VALOR'] ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Estado</label>
                                        <select name="estado" class="form-select">
                                            <option value="ACTIVO" <?= ($corredorActivo['ESTADO'] == 'ACTIVO') ? 'selected' : '' ?>>ACTIVO</option>
                                            <option value="INACTIVO" <?= ($corredorActivo['ESTADO'] == 'INACTIVO') ? 'selected' : '' ?>>INACTIVO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary fw-bold">Guardar Políticas</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL AGREGAR PARADA -->
            <div class="modal fade" id="modalAgregarParada" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title fw-bold">+ Añadir Escala al Corredor</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/suramerica2030/flota/guardarParada" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ciudad / Parada</label>
                                    <input type="text" name="ciudad" list="listaCiudades" class="form-control" placeholder="Selecciona o escribe una ciudad..." required>
                                    <datalist id="listaCiudades">
                                        <?php foreach($ciudadesCatalogo as $cd): ?>
                                            <option value="<?= htmlspecialchars($cd['NOMBRE']) ?>"><?= htmlspecialchars($cd['PAIS']) ?></option>
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">País</label>
                                        <select name="pais" class="form-select" required>
                                            <option value="ECUADOR">Ecuador</option>
                                            <option value="COLOMBIA">Colombia</option>
                                            <option value="VENEZUELA">Venezuela</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Tipo de Parada</label>
                                        <select name="tipo_parada" class="form-select">
                                            <option value="ESCALA">Escala Intermedia</option>
                                            <option value="TRANSBORDO_FRONTERA">Transbordo Fronterizo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Posición en el Recorrido</label>
                                    <select name="posicion_insercion" class="form-select">
                                        <option value="FINAL">📍 Al final de las escalas (Antes de <?= htmlspecialchars($corredorActivo['DESTINO_TERMINAL']) ?>)</option>
                                        <?php foreach($paradas as $p): ?>
                                            <?php if($p['TIPO_PARADA'] !== 'TERMINAL'): ?>
                                                <option value="<?= $p['ORDEN'] ?>">⬇️ Insertar después de: <?= $p['ORDEN'] ?>. <?= htmlspecialchars($p['CIUDAD']) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Por defecto se ubicará entre la última escala y el destino terminal.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tiempo Estimado desde la salida (Horas)</label>
                                    <input type="number" name="tiempo_estimado_horas" class="form-control" value="2" min="0">
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary fw-bold">Guardar Parada</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="card shadow-sm border-0 py-5 text-center bg-white my-3">
                <div class="card-body">
                    <span class="fs-1 d-block mb-2">🌐</span>
                    <h5 class="fw-bold text-dark">No hay corredores registrados</h5>
                    <p class="text-muted">Crea tu primer corredor maestro para comenzar a organizar tus itinerarios y paradas.</p>
                    <button class="btn btn-success fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalCrearCorredor">
                        + Crear Primer Corredor
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- MODAL CREAR NUEVO CORREDOR -->
        <div class="modal fade" id="modalCrearCorredor" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">+ Crear Nuevo Corredor Troncal</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="/suramerica2030/flota/crearCorredor" method="POST">
                        <div class="modal-body">
                            <div class="alert alert-info py-2 small mb-3">
                                ℹ️ Un corredor representa el trayecto físico principal (ej. <em>Manta ➔ Caracas</em> o <em>Caracas ➔ Manta</em>). Al crearlo, se añadirán automáticamente el Origen y Destino como paradas iniciales, y luego podrás añadir todas las escalas intermedias.
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Código Identificador</label>
                                    <input type="text" name="codigo" class="form-control" placeholder="Ej: COR-02" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Nombre del Corredor / Línea</label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Corredor Retorno Caracas - Manta" required>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Ciudad Cabecera (Origen)</label>
                                    <input type="text" name="origen_cabecera" list="listaCiudades" class="form-control" placeholder="Ej: Caracas" required>
                                    <div class="mt-1">
                                        <label class="small text-muted">País de Origen:</label>
                                        <select name="pais_origen" class="form-select form-select-sm">
                                            <option value="ECUADOR">Ecuador</option>
                                            <option value="COLOMBIA">Colombia</option>
                                            <option value="VENEZUELA">Venezuela</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Ciudad Terminal (Destino)</label>
                                    <input type="text" name="destino_terminal" list="listaCiudades" class="form-control" placeholder="Ej: Manta" required>
                                    <div class="mt-1">
                                        <label class="small text-muted">País de Destino:</label>
                                        <select name="pais_destino" class="form-select form-select-sm">
                                            <option value="VENEZUELA">Venezuela</option>
                                            <option value="COLOMBIA">Colombia</option>
                                            <option value="ECUADOR">Ecuador</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Días de Salida Fija</label>
                                    <div class="d-flex flex-wrap gap-2 pt-1">
                                        <?php foreach($mapaDias as $numDia => $txtDia): ?>
                                            <label class="btn btn-outline-secondary btn-sm">
                                                <input type="checkbox" name="dias[]" value="<?= $numDia ?>" <?= in_array((string)$numDia, ['2','5']) ? 'checked' : '' ?>> <?= $txtDia ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Hora de Salida Cabecera</label>
                                    <input type="time" name="hora_salida" class="form-control" value="10:00" required>
                                </div>
                            </div>

                            <h6 class="text-secondary border-bottom pb-1 mt-4">Políticas Globales Heredadas por las Sub-Rutas</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Equipaje Libre (Kg)</label>
                                    <input type="number" step="0.1" name="limite_equipaje" class="form-control" value="35.0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sobrepeso ($/Kg)</label>
                                    <input type="number" step="0.01" name="precio_sobrepeso" class="form-control" value="1.00" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Precio Sobre ($)</label>
                                    <input type="number" step="0.01" name="precio_sobre" class="form-control" value="5.00" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Comisión Envíos de Valores (%)</label>
                                    <input type="number" step="0.10" name="comision_valor" class="form-control" value="10.00" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado Inicial</label>
                                    <select name="estado" class="form-select">
                                        <option value="ACTIVO">ACTIVO</option>
                                        <option value="INACTIVO">INACTIVO</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success fw-bold">Crear Corredor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                    <form action="/suramerica2030/flota/generarRutasCorredor" method="POST" onsubmit="return confirm('¿Deseas generar o sincronizar todas las combinaciones comerciales del corredor seleccionado? Los precios que ya hayas personalizado se respetarán.');">
                        <input type="hidden" name="id_corredor" id="generador_id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?? 1 ?>">
                        <button type="submit" class="btn btn-success fw-bold shadow-sm" id="btnGenerarRutas">
                            ⚡ Sincronizar / Generar Rutas (<span id="textoNombreCorredorGenerar"><?= htmlspecialchars($corredorActivo['CODIGO'] ?? 'Corredor') ?></span>)
                        </button>
                    </form>
                    <button class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevaRutaManual">
                        + Nueva Ruta Manual
                    </button>
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

        $operadoresUnicos = array_values(array_unique(array_filter(array_map('trim', array_column($rutas, 'MODALIDAD')))));
        sort($operadoresUnicos);
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
                    <span class="fs-4 fw-bold text-success"><?= $rutasDirectas ?></span>
                    <small class="text-muted d-block" style="font-size: 11px;">Operación directa</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border shadow-sm h-100 p-2 tarjeta-filtro-rapido-ruta text-center" data-filtro-kpi="conexion" style="cursor: pointer; transition: all 0.2s;" title="Clic para filtrar rutas con conexiones">
                    <small class="text-primary fw-bold text-uppercase d-block" style="font-size: 10px;">🔄 Con Conexión</small>
                    <span class="fs-4 fw-bold text-primary"><?= $rutasConexion ?></span>
                    <small class="text-muted d-block" style="font-size: 11px;">Multitramo / Transbordos</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border shadow-sm h-100 p-2 tarjeta-filtro-rapido-ruta text-center" data-filtro-kpi="activas" style="cursor: pointer; transition: all 0.2s;" title="Clic para filtrar rutas activas en taquilla">
                    <small class="text-info text-dark fw-bold text-uppercase d-block" style="font-size: 10px;">Activas en Venta</small>
                    <span class="fs-4 fw-bold text-info text-dark"><?= $rutasActivas ?></span>
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

                <!-- FILA 2: MODALIDAD, ITINERARIO, ESTADO Y CORREDOR -->
                <div class="row g-2 align-items-center">
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🏢 Operador:</span>
                            <select id="filtroModalidadRutas" class="form-select bg-white">
                                <option value="">Todos los Operadores</option>
                                <?php foreach($operadoresUnicos as $op): ?>
                                    <option value="<?= htmlspecialchars(mb_strtolower($op)) ?>"><?= htmlspecialchars($op) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🚌 Viaje:</span>
                            <select id="filtroTipoViajeRutas" class="form-select bg-white">
                                <option value="">Todos los Viajes</option>
                                <option value="directo">➡️ Directo</option>
                                <option value="conexion">🔄 Con Conexión</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🔘 Estado:</span>
                            <select id="filtroEstadoRutas" class="form-select bg-white">
                                <option value="">Todos los Estados</option>
                                <option value="activo">✅ Activas</option>
                                <option value="inactivo">⏸️ Inactivas</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">🛤️ Corredor:</span>
                            <select id="filtroCorredorRutas" class="form-select bg-white">
                                <option value="">Todos los Corredores</option>
                                <?php foreach($corredores as $cor): 
                                    $cant = $rutasPorCorredor[$cor['ID_CORREDOR']] ?? 0;
                                ?>
                                    <option value="<?= $cor['ID_CORREDOR'] ?>">
                                        <?= htmlspecialchars($cor['CODIGO'] . ' - ' . $cor['NOMBRE']) ?> (<?= $cant ?>)
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
                            <th class="text-end">Tarifa Pasaje</th>
                            <th class="text-end">Tarifa Encomienda/Kg</th>
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
                        ?>
                            <tr class="fila-ruta <?= $esActivo ? '' : 'table-secondary opacity-75' ?>" 
                                data-corredor="<?= $r['ID_CORREDOR'] ?? '' ?>" 
                                data-origen="<?= htmlspecialchars(mb_strtolower(trim($r['RUT_ORIGEN']))) ?>" 
                                data-destino="<?= htmlspecialchars(mb_strtolower(trim($r['RUT_DESTINO']))) ?>" 
                                data-modalidad="<?= htmlspecialchars(mb_strtolower(!empty($r['OPERADOR_NOMBRE']) ? $r['OPERADOR_NOMBRE'] : ($r['MODALIDAD'] ?? 'ESTANDAR'))) ?>" 
                                data-tipo-viaje="<?= htmlspecialchars(mb_strtolower($r['TIPO_VIAJE'] ?? 'DIRECTO')) ?>" 
                                data-estado="<?= htmlspecialchars(mb_strtolower($r['ESTADO'] ?? 'ACTIVO')) ?>" 
                                data-search="<?= strtolower(htmlspecialchars($r['RUT_ORIGEN'] . ' ' . $r['RUT_DESTINO'] . ' ' . ($r['NOMBRE_CORREDOR'] ?? '') . ' #' . $r['ID_RUTA'] . ' ' . ($r['OPERADOR_NOMBRE'] ?? '') . ' ' . ($r['MODALIDAD'] ?? ''))) ?>">
                                <td><span class="badge bg-secondary">#<?= $r['ID_RUTA'] ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($r['RUT_ORIGEN']) ?> ➔ <?= htmlspecialchars($r['RUT_DESTINO']) ?></div>
                                    <?php if(!empty($r['NOMBRE_CORREDOR'])): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-primary border" style="font-size: 0.75rem; font-weight: 500;">
                                                🛤️ <?= htmlspecialchars($r['NOMBRE_CORREDOR']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-search="<?= strtolower(htmlspecialchars((!empty($r['OPERADOR_NOMBRE']) ? $r['OPERADOR_NOMBRE'] : ($r['MODALIDAD'] ?? '')) . ' ' . ($r['TIPO_VIAJE'] ?? ''))) ?>">
                                    <?php 
                                        $mod = $r['MODALIDAD'] ?? 'ESTANDAR';
                                        $esConexion = ($r['TIPO_VIAJE'] ?? '') === 'CONEXION';
                                        $operadorDirecto = !empty($r['OPERADOR_NOMBRE']) ? $r['OPERADOR_NOMBRE'] : ((!empty($mod) && $mod !== 'ESTANDAR' && $mod !== 'CONEXION_TRAMOS') ? $mod : 'Suramérica 2030 (Flota Propia)');
                                        $admiteSp = isset($r['ADMITE_SOBREPESO']) ? intval($r['ADMITE_SOBREPESO']) : 1;
                                    ?>
                                    <div class="d-flex flex-column gap-1">
                                        <?php if($esConexion): ?>
                                            <span class="badge bg-info text-dark" title="Viaje con transbordo y conexiones">🔄 Conexión (<?= count($r['TRAMOS_DETALLE'] ?? []) ?> tramos)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success text-white">➡️ Directo</span>
                                            <span class="badge bg-light text-dark border text-truncate" style="max-width: 175px;" title="Operador: <?= htmlspecialchars($operadorDirecto) ?>">🏢 <?= htmlspecialchars($operadorDirecto) ?></span>
                                            <?php if($admiteSp): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle" title="Sobrepeso amparado y cobrado en Ecuador" style="font-size: 0.70rem;">🧳 Sobrepeso: SÍ</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle" title="Sin cobro de sobrepeso en oficina" style="font-size: 0.70rem;">⚠️ Sobrepeso: NO</span>
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
                                <td>
                                    <small class="d-block text-muted"><?= htmlspecialchars($strDias) ?></small>
                                    <small class="text-primary fw-bold">⏰ <?= $r['RUT_SAHORA'] ?></small>
                                </td>
                                <td class="text-end">
                                    <span class="fs-6 fw-bold text-dark">$ <?= number_format($r['PRECIO_PASAJE'], 2) ?></span>
                                    <small class="text-success d-block fw-bold" style="font-size: 0.72rem;" title="Retención de Utilidad de la Agencia">
                                        💼 <?= ($r['TIPO_UTILIDAD'] ?? 'PORCENTAJE') === 'FIJO' ? '$' . number_format($r['UTILIDAD_PASAJE'] ?? 0, 2) : number_format($r['UTILIDAD_PASAJE'] ?? 100, 0) . '%' ?> util.
                                    </small>
                                </td>
                                <td class="text-end">
                                    <span class="fs-6 fw-bold text-dark">$ <?= number_format($r['PRECIO_ENCOMIENDA_KG'], 2) ?></span>
                                </td>
                                <td class="text-center">
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
                            <td colspan="8" class="text-center py-5 text-muted">
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
                        <form action="/suramerica2030/flota/guardarTarifaRapida" method="POST">
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
                    <form class="modal-content" action="/suramerica2030/flota/editarRuta" method="POST" id="form_edit_ruta_<?= $r['ID_RUTA'] ?>">
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
                                        <div class="col-12">
                                            <label class="form-label text-primary fw-bold">Tipo de Itinerario</label>
                                            <select name="tipo_viaje" class="form-select border-primary fw-bold" onchange="toggleTramos(this.value, 'tramos_edit_<?= $r['ID_RUTA'] ?>')">
                                                <option value="DIRECTO" <?= ($r['TIPO_VIAJE'] ?? '') === 'DIRECTO' ? 'selected' : '' ?>>➡️ Viaje Directo (Un solo operador / bus)</option>
                                                <option value="CONEXION" <?= ($r['TIPO_VIAJE'] ?? '') === 'CONEXION' ? 'selected' : '' ?>>🔄 Con Conexiones (Tramos Operativos y Transbordos)</option>
                                            </select>
                                            <small class="text-muted">Si es directo, el operador y sobrepeso se definen aquí; si es conexión, se configuran en el Bloque de Tramos.</small>
                                        </div>
                                    </div>

                                    <!-- BLOQUE VIAJE DIRECTO: OPERADOR Y SOBREPESO -->
                                    <div id="opciones_directo_edit_<?= $r['ID_RUTA'] ?>" class="<?= ($r['TIPO_VIAJE'] ?? 'DIRECTO') === 'CONEXION' ? 'd-none' : '' ?> bg-white p-2 rounded border mt-2">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-6">
                                                <label class="form-label small text-dark mb-0 fw-bold">🏢 Operador de la Ruta Directa</label>
                                                <select name="id_operador" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const nom = this.options[this.selectedIndex].getAttribute('data-nombre'); const hid = this.form.querySelector('input[name=operador]'); if(hid) hid.value = nom;">
                                                     <?= $generarOpcionesOperadores($r['ID_OPERADOR'] ?? 1) ?>
                                                 </select>
                                                 <input type="hidden" name="operador" value="<?= htmlspecialchars(!empty($r['OPERADOR_NOMBRE']) ? $r['OPERADOR_NOMBRE'] : ((!empty($r['MODALIDAD']) && $r['MODALIDAD'] !== 'ESTANDAR' && $r['MODALIDAD'] !== 'CONEXION_TRAMOS') ? $r['MODALIDAD'] : 'Suramérica 2030 (Flota Propia)')) ?>">
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
                                                            <select name="tramo_id_operador[]" class="form-select form-select-sm fw-bold border-primary" onchange="const nom = this.options[this.selectedIndex].getAttribute('data-nombre'); const hid = this.closest('.row').querySelector('input[name=\'tramo_operador[]\']'); if(hid) hid.value = nom;">
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
                                    <div class="col-12">
                                        <label class="form-label text-primary fw-bold">Tipo de Itinerario</label>
                                        <select name="tipo_viaje" class="form-select border-primary fw-bold" onchange="toggleTramos(this.value, 'tramos_nuevos')">
                                            <option value="DIRECTO">➡️ Viaje Directo (Un solo operador / bus)</option>
                                            <option value="CONEXION">🔄 Con Conexiones (Tramos Operativos y Transbordos)</option>
                                        </select>
                                        <small class="text-muted">Si es directo, el operador y sobrepeso se definen aquí; si es conexión, se configuran en el Bloque de Tramos.</small>
                                    </div>
                                </div>

                                <!-- BLOQUE VIAJE DIRECTO: OPERADOR Y SOBREPESO -->
                                <div id="opciones_directo_nuevo" class="bg-white p-2 rounded border mt-2">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label small text-dark mb-0 fw-bold">🏢 Operador de la Ruta Directa</label>
                                            <select name="id_operador" class="form-select form-select-sm fw-bold border-primary text-primary" onchange="const nom = this.options[this.selectedIndex].getAttribute('data-nombre'); const hid = this.form.querySelector('input[name=operador]'); if(hid) hid.value = nom;">
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

    </div>

        <!-- ======================================================== -->
    <!-- PESTAÑA 3: FLOTA DE BUSES Y OPERADORES / SOCIOS          -->
    <!-- ======================================================== -->
    <div class="tab-pane fade <?= ($tabActiva == 'buses' || $tabActiva == 'operadores') ? 'show active' : '' ?>" id="tab-buses">
        
        <!-- SECCIÓN 1: DIRECTORIO MAESTRO DE OPERADORES Y SOCIOS -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                <div>
                    <h5 class="text-primary mb-1 fw-bold">🏢 Directorio Maestro de Operadores y Socios</h5>
                    <p class="text-muted small mb-0">Gestión de razones sociales, RUC/Cédula, cuentas bancarias para liquidaciones y vínculos con buses/rutas.</p>
                </div>
                <button class="btn btn-sm btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearOperador">
                    + Registrar Nuevo Operador / Socio
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Operador / Razón Social</th>
                                <th>Tipo de Operador</th>
                                <th>Identificación / RUC</th>
                                <th>Contacto</th>
                                <th>Cuenta Bancaria para Liquidaciones</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center" style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($operadores)): ?>
                                <tr><td colspan="8" class="text-center py-3 text-muted">No hay operadores registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach($operadores as $op): 
                                    $badgeTipo = 'bg-secondary';
                                    $labelTipo = $op['TIPO_OPERADOR'] ?? 'SOCIO_AFILIADO';
                                    if ($labelTipo === 'PROPIO') {
                                        $badgeTipo = 'bg-primary';
                                        $labelTipo = '🏢 Flota Propia';
                                    } elseif ($labelTipo === 'SOCIO_AFILIADO') {
                                        $badgeTipo = 'bg-success';
                                        $labelTipo = '🤝 Socio Propietario';
                                    } elseif ($labelTipo === 'EMPRESA_ALIADA') {
                                        $badgeTipo = 'bg-info text-dark';
                                        $labelTipo = '⭐ Empresa Aliada';
                                    }
                                ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?= $op['ID_OPERADOR'] ?></td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?></div>
                                            <?php if(!empty($op['RAZON_SOCIAL']) && $op['RAZON_SOCIAL'] !== $op['NOMBRE_COMERCIAL']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($op['RAZON_SOCIAL']) ?></small>
                                            <?php endif; ?>
                                            <?php if(!empty($op['OBSERVACIONES'])): ?>
                                                <div class="small text-secondary fst-italic" style="font-size: 11px;">📝 <?= htmlspecialchars($op['OBSERVACIONES']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeTipo ?> px-2 py-1"><?= $labelTipo ?></span>
                                        </td>
                                        <td>
                                            <?= !empty($op['IDENTIFICACION']) ? '<span class="font-monospace fw-bold text-dark">' . htmlspecialchars($op['IDENTIFICACION']) . '</span>' : '<span class="text-muted small">Sin registrar</span>' ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($op['TELEFONO'])): ?>
                                                <div class="small text-success">📞 <?= htmlspecialchars($op['TELEFONO']) ?></div>
                                            <?php endif; ?>
                                            <?php if(!empty($op['CORREO'])): ?>
                                                <div class="small text-muted">✉️ <?= htmlspecialchars($op['CORREO']) ?></div>
                                            <?php endif; ?>
                                            <?php if(empty($op['TELEFONO']) && empty($op['CORREO'])): ?>
                                                <span class="text-muted small">Sin contacto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($op['BANCO'])): ?>
                                                <div class="fw-bold text-dark small">🏦 <?= htmlspecialchars($op['BANCO']) ?></div>
                                                <div class="small">
                                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($op['TIPO_CUENTA'] ?? 'Corriente') ?></span>
                                                    <span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($op['NUMERO_CUENTA']) ?></span>
                                                </div>
                                                <?php if(!empty($op['TITULAR_CUENTA'])): ?>
                                                    <div class="small text-muted" style="font-size: 11px;">Titular: <?= htmlspecialchars($op['TITULAR_CUENTA']) ?> <?= !empty($op['TITULAR_DOC']) ? '(' . htmlspecialchars($op['TITULAR_DOC']) . ')' : '' ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" style="font-size: 11px;">⚠️ Sin cuenta configurada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= ($op['ESTADO'] ?? 'ACTIVO') === 'ACTIVO' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= htmlspecialchars($op['ESTADO'] ?? 'ACTIVO') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarOperador<?= $op['ID_OPERADOR'] ?>" title="Editar Operador / Cuentas Bancarias">✏️</button>
                                                <?php if($op['ID_OPERADOR'] > 1): ?>
                                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarOperador<?= $op['ID_OPERADOR'] ?>" title="Eliminar Operador">🗑️</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: PARQUE AUTOMOTOR Y BUSES -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 border-top border-primary border-3">
                    <div class="card-header bg-white"><h6 class="mb-0 text-primary fw-bold">🚌 Registrar Unidad (Bus)</h6></div>
                    <div class="card-body">
                        <form action="/suramerica2030/flota/guardarBus" method="POST">
                            <label class="form-label small text-muted mb-1 fw-bold">🏢 Operador Propietario / Socio</label>
                            <select name="id_operador" class="form-select form-select-sm fw-bold border-primary mb-3">
                                <?= $generarOpcionesOperadores(1) ?>
                            </select>

                            <h6 class="text-secondary border-bottom pb-1">Datos de la Unidad</h6>
                            <div class="row mb-2">
                                <div class="col-6"><label class="form-label small">Marca</label><input type="text" name="marca" class="form-control form-control-sm" required placeholder="Ej: Scania, Volvo"></div>
                                <div class="col-6"><label class="form-label small">Modelo</label><input type="text" name="modelo" class="form-control form-control-sm" required placeholder="Ej: K410, Marcopolo"></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6"><label class="form-label small">Placa</label><input type="text" name="placa" class="form-control form-control-sm text-uppercase fw-bold" required placeholder="Ej: ABC-1234"></div>
                                <div class="col-6"><label class="form-label small">Asientos</label><input type="number" name="asientos" class="form-control form-control-sm" required min="10" max="80" value="44"></div>
                            </div>

                            <h6 class="text-secondary border-bottom pb-1">Chofer o Contacto Directo de la Unidad (Opcional)</h6>
                            <div class="row mb-2">
                                <div class="col-6"><label class="form-label small">Nombre</label><input type="text" name="socio_nombre" class="form-control form-control-sm" placeholder="Opcional"></div>
                                <div class="col-6"><label class="form-label small">Apellido</label><input type="text" name="socio_apellido" class="form-control form-control-sm" placeholder="Opcional"></div>
                            </div>
                            <div class="mb-3"><label class="form-label small">Teléfono Chofer</label><input type="text" name="socio_telefono" class="form-control form-control-sm" placeholder="Ej: 0991234567"></div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">Registrar Unidad</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 table-responsive">
                    <div class="card-header bg-white py-2"><h6 class="mb-0 text-dark fw-bold">🚌 Flota de Buses y Unidades Asignadas</h6></div>
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Bus / Unidad</th>
                                <th>Placa</th>
                                <th>Operador / Socio Asignado</th>
                                <th>Contacto Chofer</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($buses as $b): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($b['BUS_MARCA']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($b['BUS_MODELO']) ?> (<?= $b['BUS_ASIENTOS'] ?> Asientos)</small>
                                    </td>
                                    <td><span class="badge bg-dark px-2 py-1 fs-6"><?= htmlspecialchars($b['BUS_PLACA']) ?></span></td>
                                    <td>
                                        <div class="fw-bold text-primary">
                                            <?= htmlspecialchars(!empty($b['OPERADOR_NOMBRE']) ? $b['OPERADOR_NOMBRE'] : 'Suramérica 2030 (Flota Propia)') ?>
                                        </div>
                                        <?php if(!empty($b['OPERADOR_BANCO'])): ?>
                                            <small class="text-muted d-block">🏦 <?= htmlspecialchars($b['OPERADOR_BANCO']) ?>: <?= htmlspecialchars($b['OPERADOR_CUENTA']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($b['SOC_NOMBRE'])): ?>
                                            👤 <?= htmlspecialchars(($b['SOC_NOMBRE'] ?? '') . ' ' . ($b['SOC_APELLIDOS'] ?? '')) ?><br>
                                            <small class="text-success">📞 <?= htmlspecialchars($b['SOC_TELEFONO'] ?? '') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin chofer registrado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarBus<?= $b['ID_BUS'] ?>">✏️</button>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarBus<?= $b['ID_BUS'] ?>">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL REGISTRAR NUEVO OPERADOR / SOCIO                   -->
        <!-- ======================================================== -->
        <div class="modal fade text-start" id="modalCrearOperador" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form class="modal-content" action="/suramerica2030/flota/guardarOperador" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">🏢 Registrar Nuevo Operador / Socio</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nombre Comercial *</label>
                                <input type="text" name="nombre_comercial" class="form-control form-control-sm fw-bold text-primary" required placeholder="Ej: Panamericana de Transportes, Rutas Andinas">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Razón Social</label>
                                <input type="text" name="razon_social" class="form-control form-control-sm" placeholder="Ej: Transportes Panamericana Cía. Ltda.">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tipo de Operador</label>
                                <select name="tipo_operador" class="form-select form-select-sm fw-bold">
                                    <option value="SOCIO_AFILIADO">🤝 Socio Afiliado Propietario</option>
                                    <option value="EMPRESA_ALIADA">⭐ Empresa Aliada / Interconexión</option>
                                    <option value="PROPIO">🏢 Flota Propia (Suramérica 2030)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">RUC / Cédula / Identificación</label>
                                <input type="text" name="identificacion" class="form-control form-control-sm font-monospace" placeholder="Ej: 1792123456001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Estado</label>
                                <select name="estado" class="form-select form-select-sm">
                                    <option value="ACTIVO" selected>✅ Activo (Disponible para rutas y buses)</option>
                                    <option value="INACTIVO">❌ Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Teléfono de Contacto</label>
                                <input type="text" name="telefono" class="form-control form-control-sm" placeholder="Ej: +593 99 123 4567">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Correo Electrónico</label>
                                <input type="email" name="correo" class="form-control form-control-sm" placeholder="Ej: gerencia@empresa.com">
                            </div>
                        </div>

                        <h6 class="text-primary border-bottom pb-1 mt-4 fw-bold">🏦 Datos Bancarios para Liquidaciones y Transferencias</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Banco</label>
                                <input type="text" name="banco" class="form-control form-control-sm" placeholder="Ej: Banco Pichincha, Bancolombia, Banesco">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tipo de Cuenta</label>
                                <select name="tipo_cuenta" class="form-select form-select-sm">
                                    <option value="CORRIENTE" selected>Cuenta Corriente</option>
                                    <option value="AHORROS">Cuenta de Ahorros</option>
                                    <option value="OTRO">Otro / Billetera Virtual</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Número de Cuenta</label>
                                <input type="text" name="numero_cuenta" class="form-control form-control-sm font-monospace fw-bold" placeholder="Ej: 2100123456">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Titular de la Cuenta</label>
                                <input type="text" name="titular_cuenta" class="form-control form-control-sm" placeholder="Nombre completo del titular">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Cédula / RUC del Titular</label>
                                <input type="text" name="titular_doc" class="form-control form-control-sm font-monospace" placeholder="Doc de identidad del titular">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Observaciones / Notas de Operación</label>
                            <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Notas sobre el acuerdo, pagos, convenios o tramos asignados..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer p-2 bg-light">
                        <button type="submit" class="btn btn-primary fw-bold w-100">Guardar Operador / Socio</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODALES DE EDITAR / ELIMINAR OPERADORES                  -->
        <!-- ======================================================== -->
        <?php foreach($operadores as $op): ?>
            <!-- MODAL EDITAR OPERADOR -->
            <div class="modal fade text-start" id="modalEditarOperador<?= $op['ID_OPERADOR'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form class="modal-content" action="/suramerica2030/flota/editarOperador" method="POST">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">✏️ Editar Operador #<?= $op['ID_OPERADOR'] ?>: <?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_operador" value="<?= $op['ID_OPERADOR'] ?>">

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nombre Comercial *</label>
                                    <input type="text" name="nombre_comercial" class="form-control form-control-sm fw-bold text-primary" value="<?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Razón Social</label>
                                    <input type="text" name="razon_social" class="form-control form-control-sm" value="<?= htmlspecialchars($op['RAZON_SOCIAL'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tipo de Operador</label>
                                    <select name="tipo_operador" class="form-select form-select-sm fw-bold">
                                        <option value="SOCIO_AFILIADO" <?= ($op['TIPO_OPERADOR'] === 'SOCIO_AFILIADO') ? 'selected' : '' ?>>🤝 Socio Afiliado Propietario</option>
                                        <option value="EMPRESA_ALIADA" <?= ($op['TIPO_OPERADOR'] === 'EMPRESA_ALIADA') ? 'selected' : '' ?>>⭐ Empresa Aliada / Interconexión</option>
                                        <option value="PROPIO" <?= ($op['TIPO_OPERADOR'] === 'PROPIO') ? 'selected' : '' ?>>🏢 Flota Propia (Suramérica 2030)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">RUC / Cédula / Identificación</label>
                                    <input type="text" name="identificacion" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($op['IDENTIFICACION'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Estado</label>
                                    <select name="estado" class="form-select form-select-sm">
                                        <option value="ACTIVO" <?= ($op['ESTADO'] === 'ACTIVO') ? 'selected' : '' ?>>✅ Activo</option>
                                        <option value="INACTIVO" <?= ($op['ESTADO'] === 'INACTIVO') ? 'selected' : '' ?>>❌ Inactivo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Teléfono de Contacto</label>
                                    <input type="text" name="telefono" class="form-control form-control-sm" value="<?= htmlspecialchars($op['TELEFONO'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Correo Electrónico</label>
                                    <input type="email" name="correo" class="form-control form-control-sm" value="<?= htmlspecialchars($op['CORREO'] ?? '') ?>">
                                </div>
                            </div>

                            <h6 class="text-primary border-bottom pb-1 mt-4 fw-bold">🏦 Datos Bancarios para Liquidaciones y Transferencias</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Banco</label>
                                    <input type="text" name="banco" class="form-control form-control-sm" value="<?= htmlspecialchars($op['BANCO'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tipo de Cuenta</label>
                                    <select name="tipo_cuenta" class="form-select form-select-sm">
                                        <option value="CORRIENTE" <?= ($op['TIPO_CUENTA'] === 'CORRIENTE') ? 'selected' : '' ?>>Cuenta Corriente</option>
                                        <option value="AHORROS" <?= ($op['TIPO_CUENTA'] === 'AHORROS') ? 'selected' : '' ?>>Cuenta de Ahorros</option>
                                        <option value="OTRO" <?= ($op['TIPO_CUENTA'] === 'OTRO') ? 'selected' : '' ?>>Otro / Billetera Virtual</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Número de Cuenta</label>
                                    <input type="text" name="numero_cuenta" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($op['NUMERO_CUENTA'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Titular de la Cuenta</label>
                                    <input type="text" name="titular_cuenta" class="form-control form-control-sm" value="<?= htmlspecialchars($op['TITULAR_CUENTA'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Cédula / RUC del Titular</label>
                                    <input type="text" name="titular_doc" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($op['TITULAR_DOC'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Observaciones / Notas de Operación</label>
                                <textarea name="observaciones" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($op['OBSERVACIONES'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer p-2 bg-light">
                            <button type="submit" class="btn btn-primary fw-bold w-100">Actualizar Información del Operador</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL ELIMINAR OPERADOR -->
            <?php if($op['ID_OPERADOR'] > 1): ?>
                <div class="modal fade text-start" id="modalEliminarOperador<?= $op['ID_OPERADOR'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <form class="modal-content" action="/suramerica2030/flota/eliminarOperador" method="POST">
                            <div class="modal-header bg-danger text-white">
                                <h6 class="modal-title">Eliminar Operador</h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <input type="hidden" name="id_operador" value="<?= $op['ID_OPERADOR'] ?>">
                                <p class="mb-1">¿Estás seguro de eliminar permanentemente al operador?</p>
                                <strong class="text-danger fs-6"><?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?></strong>
                                <div class="alert alert-warning p-2 mt-2 mb-0" style="font-size: 11px;">
                                    Si tiene buses o rutas asignadas, se reasignarán a la Flota Propia para proteger la integridad del sistema.
                                </div>
                            </div>
                            <div class="modal-footer p-2">
                                <button type="submit" class="btn btn-danger w-100 fw-bold">Sí, Eliminar Definitivamente</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- ======================================================== -->
        <!-- MODALES DE BUSES Y SOCIOS (FUERA DE LA TABLA)           -->
        <!-- ======================================================== -->
        <?php foreach($buses as $b): ?>
            <!-- MODAL ELIMINAR BUS -->
            <div class="modal fade text-start" id="modalEliminarBus<?= $b['ID_BUS'] ?>" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title fs-6">Eliminar Unidad #<?= $b['ID_BUS'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/suramerica2030/flota/eliminarBus" method="POST">
                            <div class="modal-body text-center">
                                <input type="hidden" name="id_bus" value="<?= $b['ID_BUS'] ?>">
                                <p class="mb-2">¿Confirmas eliminar este bus?</p>
                                <strong class="text-danger fs-5"><?= htmlspecialchars($b['BUS_PLACA']) ?></strong><br>
                                <small><?= htmlspecialchars($b['BUS_MARCA']) ?></small>
                                
                                <div class="alert alert-warning p-2 mt-3 mb-0" style="font-size: 11px;">
                                    Solo podrás eliminarlo si no tiene operaciones registradas.
                                </div>
                            </div>
                            <div class="modal-footer p-2">
                                <button type="submit" class="btn btn-danger w-100 fw-bold">Sí, Eliminar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL EDITAR BUS -->
            <div class="modal fade text-start" id="modalEditarBus<?= $b['ID_BUS'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Editar Unidad #<?= $b['ID_BUS'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/suramerica2030/flota/editarBus" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_bus" value="<?= $b['ID_BUS'] ?>">
                                
                                <label class="form-label small fw-bold text-dark">🏢 Operador Propietario / Socio</label>
                                <select name="id_operador" class="form-select form-select-sm fw-bold border-primary mb-3">
                                    <?= $generarOpcionesOperadores($b['ID_OPERADOR'] ?? 1) ?>
                                </select>

                                <h6 class="text-secondary border-bottom pb-1">Datos del Bus</h6>
                                <div class="row mb-2">
                                    <div class="col-6"><label class="form-label small">Marca</label><input type="text" name="marca" class="form-control form-control-sm" value="<?= htmlspecialchars($b['BUS_MARCA']) ?>" required></div>
                                    <div class="col-6"><label class="form-label small">Modelo</label><input type="text" name="modelo" class="form-control form-control-sm" value="<?= htmlspecialchars($b['BUS_MODELO']) ?>" required></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6"><label class="form-label small">Placa</label><input type="text" name="placa" class="form-control form-control-sm text-uppercase fw-bold" value="<?= htmlspecialchars($b['BUS_PLACA']) ?>" required></div>
                                    <div class="col-6"><label class="form-label small">Asientos</label><input type="number" name="asientos" class="form-control form-control-sm" value="<?= $b['BUS_ASIENTOS'] ?>" required></div>
                                </div>

                                <h6 class="text-secondary border-bottom pb-1 mt-3">Chofer o Contacto Directo de la Unidad (Opcional)</h6>
                                <div class="row mb-2">
                                    <div class="col-6"><label class="form-label small">Nombre</label><input type="text" name="socio_nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($b['SOC_NOMBRE'] ?? '') ?>"></div>
                                    <div class="col-6"><label class="form-label small">Apellido</label><input type="text" name="socio_apellido" class="form-control form-control-sm" value="<?= htmlspecialchars($b['SOC_APELLIDOS'] ?? '') ?>"></div>
                                </div>
                                <div class="mb-3"><label class="form-label small">Teléfono Chofer</label><input type="text" name="socio_telefono" class="form-control form-control-sm" value="<?= htmlspecialchars($b['SOC_TELEFONO'] ?? '') ?>"></div>
                            </div>
                            <div class="modal-footer p-2">
                                <button type="submit" class="btn btn-primary w-100 fw-bold">Actualizar Información de la Unidad</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<script>
    const HTML_OPCIONES_OPERADORES = <?= json_encode($generarOpcionesOperadores(2)) ?>;
</script>

<script>
const PARADAS_POR_CORREDOR = <?= json_encode($paradasPorCorredor ?? [], JSON_UNESCAPED_UNICODE) ?>;
const CIUDADES_CATALOGO = <?= json_encode($ciudadesCatalogo ?? [], JSON_UNESCAPED_UNICODE) ?>;

function generarOpcionesParadasCorredor(idCorredor, valorSeleccionado = '', placeholder = '-- Seleccionar Ciudad --') {
    let html = `<option value="">${placeholder}</option>`;
    const paradas = (PARADAS_POR_CORREDOR && PARADAS_POR_CORREDOR[idCorredor]) ? PARADAS_POR_CORREDOR[idCorredor] : [];
    let encontrado = false;
    const valNorm = (valorSeleccionado || '').trim().toLowerCase();

    if (paradas.length > 0) {
        html += `<optgroup label="📍 Paradas del Corredor (en orden de viaje)">`;
        paradas.forEach((p, idx) => {
            const isSel = valNorm && p.CIUDAD.trim().toLowerCase() === valNorm;
            if (isSel) encontrado = true;
            const extra = p.TIPO_PARADA ? ` [${p.TIPO_PARADA.replace(/_/g, ' ')}]` : '';
            html += `<option value="${p.CIUDAD}" ${isSel ? 'selected' : ''}>${idx + 1}. ${p.CIUDAD} (${p.PAIS})${extra}</option>`;
        });
        html += `</optgroup>`;
    }
    if (valorSeleccionado && !encontrado) {
        html += `<optgroup label="⚠️ Ciudad Actual Configurada">`;
        html += `<option value="${valorSeleccionado}" selected>${valorSeleccionado} (Registrada)</option>`;
        html += `</optgroup>`;
    }

    return html;
}

function alCambiarCorredorEnRuta(rutaId) {
    const selCorredor = document.getElementById(rutaId ? 'edit_corredor_' + rutaId : 'nuevo_corredor');
    if (!selCorredor) return;
    const idCorredor = selCorredor.value;

    const selOrigen = document.getElementById(rutaId ? 'edit_origen_' + rutaId : 'nuevo_origen');
    const selDestino = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');

    if (selOrigen) {
        const valActual = selOrigen.value;
        selOrigen.innerHTML = generarOpcionesParadasCorredor(idCorredor, valActual, '-- Seleccione Origen --');
    }
    if (selDestino) {
        const valActual = selDestino.value;
        selDestino.innerHTML = generarOpcionesParadasCorredor(idCorredor, valActual, '-- Seleccione Destino --');
    }

    // Actualizar también todos los selectores de tramos en este modal
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (container) {
        const selectsOrigen = container.querySelectorAll('.tramo-origen-select');
        selectsOrigen.forEach(sel => {
            const v = sel.value;
            sel.innerHTML = generarOpcionesParadasCorredor(idCorredor, v, '-- Sube en --');
        });
        const selectsDestino = container.querySelectorAll('.tramo-destino-select');
        selectsDestino.forEach(sel => {
            const v = sel.value;
            sel.innerHTML = generarOpcionesParadasCorredor(idCorredor, v, '-- Baja en --');
        });
        actualizarTitulosTramos(container);
    }
}

function alCambiarCabecerasRuta(rutaId) {
    const selOrigen = document.getElementById(rutaId ? 'edit_origen_' + rutaId : 'nuevo_origen');
    const selDestino = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');
    if (!selOrigen || !selDestino) return;

    const orig = selOrigen.value;
    const dest = selDestino.value;

    // En nueva ruta manual o si solo hay 1 tramo y está vacío, auto-sincronizar Tramo 1
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (container) {
        const cards = container.querySelectorAll('.tramo-card');
        if (cards.length === 1) {
            const card1 = cards[0];
            const selOrigTramo = card1.querySelector('.tramo-origen-select');
            const selDestTramo = card1.querySelector('.tramo-destino-select');
            if (selOrigTramo && (!selOrigTramo.value || !rutaId) && orig) {
                selOrigTramo.value = orig;
            }
            if (selDestTramo && (!selDestTramo.value || !rutaId) && dest) {
                selDestTramo.value = dest;
            }
            actualizarTitulosTramos(container);
        }
    }
}

function toggleTramos(valor, divId) {
    let div = document.getElementById(divId);
    if (!div) return;
    const rutaId = divId.replace('tramos_edit_', '').replace('tramos_nuevos', '');
    const divDirecto = document.getElementById(rutaId ? 'opciones_directo_edit_' + rutaId : 'opciones_directo_nuevo');
    const badgeAuto = document.getElementById(rutaId ? 'badge_util_auto_' + rutaId : 'badge_util_auto_nuevo');
    const hintUtil = document.getElementById(rutaId ? 'hint_util_pasaje_' + rutaId : 'hint_util_pasaje_nuevo');
    const listaContainerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const listaContainer = document.getElementById(listaContainerId);

    if (valor === 'CONEXION') {
        div.classList.remove('d-none');
        div.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
        if (divDirecto) divDirecto.classList.add('d-none');
        if (badgeAuto) badgeAuto.classList.remove('d-none');
        if (hintUtil) hintUtil.textContent = '⚡ Calculado automáticamente de la suma de tramos.';
        if (listaContainer && listaContainer.querySelectorAll('.tramo-card').length === 0) {
            addTramo(listaContainerId, rutaId);
        }
    } else {
        div.classList.add('d-none');
        div.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        if (divDirecto) divDirecto.classList.remove('d-none');
        if (badgeAuto) badgeAuto.classList.add('d-none');
        if (hintUtil) hintUtil.textContent = 'Retención directa de agencia (% o $).';
    }
}

function actualizarTitulosTramos(container) {
    if (!container) return;
    const cards = container.querySelectorAll('.tramo-card');
    cards.forEach((card, index) => {
        const titleSpan = card.querySelector('.tramo-title');
        const origInput = card.querySelector('select[name="tramo_origen[]"], input[name="tramo_origen[]"]');
        const destInput = card.querySelector('select[name="tramo_destino[]"], input[name="tramo_destino[]"]');
        const orig = origInput && origInput.value.trim() ? origInput.value.trim() : 'Origen';
        const dest = destInput && destInput.value.trim() ? destInput.value.trim() : 'Destino';
        if (titleSpan) {
            titleSpan.innerHTML = `📍 Tramo ${index + 1}: <span class="text-dark">${orig} ➔ ${dest}</span>`;
        }
    });
}

function addTramo(containerId, rutaId = '') {
    let container = document.getElementById(containerId);
    if (!container) return;

    const selCorredor = document.getElementById(rutaId ? 'edit_corredor_' + rutaId : 'nuevo_corredor');
    const idCorredor = selCorredor ? selCorredor.value : '';

    // Sugerir continuidad con el tramo anterior si existe
    const existingCards = container.querySelectorAll('.tramo-card');
    let subeSugerido = '';
    let bajaSugerido = '';
    if (existingCards.length > 0) {
        const lastCard = existingCards[existingCards.length - 1];
        const lastDest = lastCard.querySelector('.tramo-destino-select')?.value || '';
        subeSugerido = lastDest;
        const selDestRuta = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');
        if (selDestRuta && selDestRuta.value && selDestRuta.value !== subeSugerido) {
            bajaSugerido = selDestRuta.value;
        }
    } else {
        const selOrigRuta = document.getElementById(rutaId ? 'edit_origen_' + rutaId : 'nuevo_origen');
        const selDestRuta = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');
        subeSugerido = selOrigRuta ? selOrigRuta.value : '';
        bajaSugerido = selDestRuta ? selDestRuta.value : '';
    }

    const opcionesOrigen = generarOpcionesParadasCorredor(idCorredor, subeSugerido, '-- Sube en --');
    const opcionesDestino = generarOpcionesParadasCorredor(idCorredor, bajaSugerido, '-- Baja en --');

    let html = `
        <div class="card mb-2 border-secondary shadow-sm tramo-card" data-ruta-id="${rutaId}">
            <div class="card-header bg-white py-1 px-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-primary small tramo-title">📍 Tramo</span>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="eliminarTramoCard(this, '${rutaId}')" title="Eliminar Tramo">✕ Eliminar</button>
            </div>
            <div class="card-body p-2">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0 fw-bold">Sube en (Origen)</label>
                        <select name="tramo_origen[]" class="form-select form-select-sm tramo-origen-select fw-bold" required onchange="actualizarTitulosTramos(this.closest('.tramo-card').parentElement)">
                            ${opcionesOrigen}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0 fw-bold">Baja en (Destino)</label>
                        <select name="tramo_destino[]" class="form-select form-select-sm tramo-destino-select fw-bold" required onchange="actualizarTitulosTramos(this.closest('.tramo-card').parentElement)">
                            ${opcionesDestino}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0 fw-bold">Operador del Tramo</label>
                        <select name="tramo_id_operador[]" class="form-select form-select-sm border-primary fw-bold" onchange="const nom = this.options[this.selectedIndex].getAttribute(\'data-nombre\'); const hid = this.closest(\'.row\').querySelector(\'input[name=\\\'tramo_operador[]\\\']\'); if(hid) hid.value = nom;">
                            ${HTML_OPCIONES_OPERADORES}
                        </select>
                        <input type="hidden" name="tramo_operador[]" value="Panamericana de Transportes">
                    </div>
                </div>
                <div class="row g-2 mb-2 bg-light p-1 rounded border">
                    <div class="col-4">
                        <label class="form-label small text-dark mb-0 fw-bold">PVP Tramo Pasaje ($)</label>
                        <input type="number" step="0.01" min="0" name="tramo_pvp[]" class="form-control form-control-sm tramo-pvp fw-bold text-primary" value="0.00" oninput="recalcularFinanzasTramo(this, '${rutaId}')">
                    </div>
                    <div class="col-4">
                        <label class="form-label small text-muted mb-0 fw-bold">Costo Socio ($)</label>
                        <input type="number" step="0.01" min="0" name="tramo_costo[]" class="form-control form-control-sm tramo-costo text-danger" value="0.00" oninput="recalcularFinanzasTramo(this, '${rutaId}')">
                    </div>
                    <div class="col-4">
                        <label class="form-label small text-success mb-0 fw-bold">Utilidad Agencia ($)</label>
                        <input type="number" step="0.01" name="tramo_utilidad[]" class="form-control form-control-sm tramo-utilidad fw-bold text-success bg-white" value="0.00" readonly>
                    </div>
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label small mb-0 fw-bold text-secondary">¿Cobrar Sobrepeso en Ecuador?</label>
                        <select name="tramo_admite_sp[]" class="form-select form-select-sm tramo-admite-sp" onchange="alCambiarAdmiteSobrepeso(this, '${rutaId}')">
                            <option value="0">❌ No cobrar sobrepeso</option>
                            <option value="1">✅ Sí (Cobro activo en Taquilla)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-0">Límite Libre (Kg)</label>
                        <input type="number" step="0.1" name="tramo_limite_eq[]" class="form-control form-control-sm" value="35.0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0">Tarifa Sobrepeso ($/Kg)</label>
                        <input type="number" step="0.01" name="tramo_precio_sp[]" class="form-control form-control-sm" value="1.00">
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    actualizarTitulosTramos(container);
    recalcularTotalesRuta(rutaId);
}

function eliminarTramoCard(btn, rutaId) {
    const card = btn.closest('.tramo-card');
    const container = card ? card.parentElement : null;
    if (card) {
        card.remove();
    }
    if (container) {
        actualizarTitulosTramos(container);
    }
    recalcularTotalesRuta(rutaId);
}

function recalcularFinanzasTramo(input, rutaId) {
    const card = input.closest('.tramo-card');
    if (!card) return;
    const pvpInput = card.querySelector('.tramo-pvp');
    const costoInput = card.querySelector('.tramo-costo');
    const utilInput = card.querySelector('.tramo-utilidad');

    const pvp = parseFloat(pvpInput ? pvpInput.value : 0) || 0;
    const costo = parseFloat(costoInput ? costoInput.value : 0) || 0;
    const util = Math.max(0, pvp - costo);

    if (utilInput) {
        utilInput.value = util.toFixed(2);
    }
    recalcularTotalesRuta(rutaId);
}

function recalcularTotalesRuta(rutaId) {
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (!container) return;

    let totalPvp = 0;
    let totalCosto = 0;
    let totalUtil = 0;

    const cards = container.querySelectorAll('.tramo-card');
    cards.forEach(card => {
        const pvp = parseFloat(card.querySelector('.tramo-pvp')?.value || 0) || 0;
        const costo = parseFloat(card.querySelector('.tramo-costo')?.value || 0) || 0;
        const util = parseFloat(card.querySelector('.tramo-utilidad')?.value || (pvp - costo)) || 0;
        totalPvp += pvp;
        totalCosto += costo;
        totalUtil += util;
    });

    const badgePvp = document.getElementById(rutaId ? 'resumen_pvp_' + rutaId : 'resumen_pvp_nuevo');
    const badgeCosto = document.getElementById(rutaId ? 'resumen_costo_' + rutaId : 'resumen_costo_nuevo');
    const badgeUtil = document.getElementById(rutaId ? 'resumen_utilidad_' + rutaId : 'resumen_utilidad_nuevo');

    if (badgePvp) badgePvp.textContent = 'PVP Total: $' + totalPvp.toFixed(2);
    if (badgeCosto) badgeCosto.textContent = 'Costo Socios: $' + totalCosto.toFixed(2);
    if (badgeUtil) badgeUtil.textContent = 'Utilidad Neta: $' + totalUtil.toFixed(2);
}

function copiarTotalATarifa(rutaId) {
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (!container) return;

    let totalPvp = 0;
    let totalUtil = 0;
    const cards = container.querySelectorAll('.tramo-card');
    cards.forEach(card => {
        const pvp = parseFloat(card.querySelector('.tramo-pvp')?.value || 0) || 0;
        const util = parseFloat(card.querySelector('.tramo-utilidad')?.value || 0) || 0;
        totalPvp += pvp;
        totalUtil += util;
    });

    const inputTarifa = document.getElementById(rutaId ? 'precio_pasaje_edit_' + rutaId : 'precio_pasaje_nuevo');
    if (inputTarifa && totalPvp > 0) {
        inputTarifa.value = totalPvp.toFixed(2);
        inputTarifa.classList.add('border-success');
        setTimeout(() => inputTarifa.classList.remove('border-success'), 1200);
    }

    // Auto-sincronizar Utilidad Pasajes con la suma de tramos
    const inputUtilPasaje = document.getElementById(rutaId ? 'utilidad_pasaje_' + rutaId : 'utilidad_pasaje_nuevo');
    const selectTipoUtil = document.getElementById(rutaId ? 'tipo_utilidad_' + rutaId : 'tipo_utilidad_nuevo');
    if (inputUtilPasaje && selectTipoUtil && totalPvp > 0) {
        if (selectTipoUtil.value === 'FIJO') {
            inputUtilPasaje.value = totalUtil.toFixed(2);
        } else {
            const pct = (totalUtil / totalPvp) * 100;
            inputUtilPasaje.value = pct.toFixed(2);
        }
        inputUtilPasaje.classList.add('border-info');
        setTimeout(() => inputUtilPasaje.classList.remove('border-info'), 1200);
    }
}

function alCambiarAdmiteSobrepeso(select, rutaId) {
    const card = select.closest('.tramo-card');
    if (!card) return;
    if (select.value === '1') {
        card.classList.add('border-success');
        card.classList.remove('border-secondary');
    } else {
        card.classList.remove('border-success');
        card.classList.add('border-secondary');
    }
}

function actualizarSimbolosUtilidad(rutaId) {
    const sel = document.getElementById(rutaId ? 'tipo_utilidad_' + rutaId : 'tipo_utilidad_nuevo');
    if (!sel) return;
    const simbolo = (sel.value === 'FIJO') ? '$' : '%';
    const spanPasaje = document.getElementById(rutaId ? 'span-simbolo-pasaje-' + rutaId : 'span-simbolo-pasaje-nuevo');
    if (spanPasaje) {
        spanPasaje.textContent = simbolo;
    }
}

let paginaActualRutas = 1;

function aplicarFiltrosYPaginarRutas(nuevaPagina = 1) {
    if (nuevaPagina) {
        paginaActualRutas = nuevaPagina;
    }

    const inputBuscador = document.getElementById('buscadorRutas');
    const query = inputBuscador ? inputBuscador.value.toLowerCase().trim() : '';

    const selectOrigen = document.getElementById('filtroOrigenRutas');
    const fOrigen = selectOrigen ? selectOrigen.value.trim().toLowerCase() : '';

    const selectDestino = document.getElementById('filtroDestinoRutas');
    const fDestino = selectDestino ? selectDestino.value.trim().toLowerCase() : '';

    const selectModalidad = document.getElementById('filtroModalidadRutas');
    const fModalidad = selectModalidad ? selectModalidad.value.trim().toLowerCase() : '';

    const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
    const fTipoViaje = selectTipoViaje ? selectTipoViaje.value.trim().toLowerCase() : '';

    const selectEstado = document.getElementById('filtroEstadoRutas');
    const fEstado = selectEstado ? selectEstado.value.trim().toLowerCase() : '';

    const selectCorredor = document.getElementById('filtroCorredorRutas');
    const fCorredor = selectCorredor ? selectCorredor.value.trim() : '';

    const selectLimite = document.getElementById('filtroLimiteRutas');
    const limiteVal = selectLimite ? selectLimite.value : '15';

    // Sincronizar banner de generación de rutas con el corredor seleccionado
    const genCorredorInput = document.getElementById('generador_id_corredor');
    const genCorredorTexto = document.getElementById('textoNombreCorredorGenerar');
    if (genCorredorInput) {
        if (fCorredor) {
            genCorredorInput.value = fCorredor;
            if (genCorredorTexto && selectCorredor) {
                const opt = selectCorredor.options[selectCorredor.selectedIndex];
                if (opt) {
                    const partes = opt.text.split('-');
                    genCorredorTexto.textContent = partes[0].trim();
                }
            }
        }
    }

    // Persistir corredor seleccionado en la URL sin recargar
    try {
        const url = new URL(window.location.href);
        if (fCorredor) {
            url.searchParams.set('corredor_filtro', fCorredor);
        } else {
            url.searchParams.delete('corredor_filtro');
        }
        window.history.replaceState(null, '', url.toString());
    } catch(e) {}

    const todasLasFilas = Array.from(document.querySelectorAll('#tablaRutas tbody tr.fila-ruta'));
    const filaSinResultados = document.getElementById('filaSinRutas');

    // Filtrar filas coincidentes
    const filasCoincidentes = todasLasFilas.filter(fila => {
        const cCorredor = fila.getAttribute('data-corredor') || '';
        const cOrigen = fila.getAttribute('data-origen') || '';
        const cDestino = fila.getAttribute('data-destino') || '';
        const cModalidad = fila.getAttribute('data-modalidad') || '';
        const cTipoViaje = fila.getAttribute('data-tipo-viaje') || '';
        const cEstado = fila.getAttribute('data-estado') || '';
        const searchFila = fila.getAttribute('data-search') || '';

        if (query && !searchFila.includes(query)) return false;
        if (fOrigen && cOrigen !== fOrigen) return false;
        if (fDestino && cDestino !== fDestino) return false;
        if (fModalidad && cModalidad !== fModalidad) return false;
        if (fTipoViaje && cTipoViaje !== fTipoViaje) return false;
        if (fEstado && cEstado !== fEstado) return false;
        if (fCorredor && cCorredor !== fCorredor) return false;

        return true;
    });

    const totalCoincidentes = filasCoincidentes.length;

    // Actualizar badge de filtros activos
    actualizarBadgeFiltrosActivosRutas(query, fOrigen, fDestino, fModalidad, fTipoViaje, fEstado, fCorredor);

    // Calcular límites y páginas
    let rpp = (limiteVal === 'all') ? (totalCoincidentes || 1) : parseInt(limiteVal, 10);
    if (isNaN(rpp) || rpp <= 0) rpp = 15;

    const totalPaginas = Math.max(1, Math.ceil(totalCoincidentes / rpp));
    if (paginaActualRutas > totalPaginas) paginaActualRutas = totalPaginas;
    if (paginaActualRutas < 1) paginaActualRutas = 1;

    const inicio = (paginaActualRutas - 1) * rpp;
    const fin = (limiteVal === 'all') ? totalCoincidentes : Math.min(inicio + rpp, totalCoincidentes);

    // Ocultar todas las filas primero
    todasLasFilas.forEach(f => f.style.display = 'none');

    // Mostrar sólo las del rango de la página actual
    if (totalCoincidentes > 0) {
        for (let i = inicio; i < fin; i++) {
            if (filasCoincidentes[i]) {
                filasCoincidentes[i].style.display = '';
            }
        }
        if (filaSinResultados) filaSinResultados.style.display = 'none';
    } else {
        if (filaSinResultados) filaSinResultados.style.display = '';
    }

    // Actualizar indicador de rutas mostradas
    const infoSpan = document.getElementById('infoPaginacionRutas');
    if (infoSpan) {
        if (totalCoincidentes === 0) {
            infoSpan.innerHTML = '<span class="text-danger fw-bold">Mostrando 0 rutas comerciales</span>';
        } else {
            infoSpan.innerHTML = `Mostrando <strong>${inicio + 1}</strong> a <strong>${fin}</strong> de <strong>${totalCoincidentes}</strong> rutas <span class="text-secondary">(Página ${paginaActualRutas} de ${totalPaginas})</span>`;
        }
    }

    // Actualizar badge de total
    const badgeTotal = document.getElementById('badgeTotalRutas');
    if (badgeTotal) {
        if (fCorredor || query || fOrigen || fDestino || fModalidad || fTipoViaje || fEstado) {
            badgeTotal.textContent = `${totalCoincidentes} de ${todasLasFilas.length} rutas`;
        } else {
            badgeTotal.textContent = `${todasLasFilas.length} rutas`;
        }
    }

    // Renderizar botones de paginación
    renderizarBotonesPaginacionRutas(totalPaginas, totalCoincidentes);
}

function actualizarBadgeFiltrosActivosRutas(query, ori, des, mod, viaje, est, cor) {
    const badge = document.getElementById('badgeFiltrosActivosRutas');
    if (!badge) return;
    
    let activos = [];
    if (query) activos.push(`"${query}"`);
    if (ori) activos.push(`Origen: ${ori.toUpperCase()}`);
    if (des) activos.push(`Destino: ${des.toUpperCase()}`);
    if (mod) activos.push(`Modalidad: ${mod.toUpperCase()}`);
    if (viaje) activos.push(`Viaje: ${viaje.toUpperCase()}`);
    if (est) activos.push(`Estado: ${est.toUpperCase()}`);
    if (cor) activos.push(`Corredor #${cor}`);

    if (activos.length === 0) {
        badge.className = 'badge bg-secondary';
        badge.textContent = 'Filtros: Todos';
    } else {
        badge.className = 'badge bg-primary';
        badge.textContent = `Filtros (${activos.length}): ` + activos.join(' | ');
    }
}

function limpiarTodosFiltrosRutas() {
    const inputBuscador = document.getElementById('buscadorRutas');
    const selectOrigen = document.getElementById('filtroOrigenRutas');
    const selectDestino = document.getElementById('filtroDestinoRutas');
    const selectModalidad = document.getElementById('filtroModalidadRutas');
    const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
    const selectEstado = document.getElementById('filtroEstadoRutas');
    const selectCorredor = document.getElementById('filtroCorredorRutas');
    
    if (inputBuscador) inputBuscador.value = '';
    if (selectOrigen) selectOrigen.value = '';
    if (selectDestino) selectDestino.value = '';
    if (selectModalidad) selectModalidad.value = '';
    if (selectTipoViaje) selectTipoViaje.value = '';
    if (selectEstado) selectEstado.value = '';
    if (selectCorredor) selectCorredor.value = '';

    // Desactivar tarjetas KPI
    document.querySelectorAll('.tarjeta-filtro-rapido-ruta').forEach(c => c.classList.remove('active'));
    const kpiTodos = document.querySelector('.tarjeta-filtro-rapido-ruta[data-filtro-kpi="todos"]');
    if (kpiTodos) kpiTodos.classList.add('active');

    aplicarFiltrosYPaginarRutas(1);
}

function renderizarBotonesPaginacionRutas(totalPaginas, totalCoincidentes) {
    const lista = document.getElementById('listaPaginacionRutas');
    if (!lista) return;

    lista.innerHTML = '';
    if (totalPaginas <= 1) {
        return;
    }

    // Botón Anterior
    const liPrev = document.createElement('li');
    liPrev.className = `page-item ${paginaActualRutas <= 1 ? 'disabled' : ''}`;
    liPrev.innerHTML = `<button class="page-link" type="button" onclick="cambiarPaginaRutas(${paginaActualRutas - 1})">‹ Anterior</button>`;
    lista.appendChild(liPrev);

    // Páginas a mostrar (ventana deslizante compacta)
    let paginas = [];
    if (totalPaginas <= 7) {
        for (let i = 1; i <= totalPaginas; i++) paginas.push(i);
    } else {
        paginas.push(1);
        let start = Math.max(2, paginaActualRutas - 1);
        let end = Math.min(totalPaginas - 1, paginaActualRutas + 1);

        if (start > 2) paginas.push('...');
        for (let i = start; i <= end; i++) paginas.push(i);
        if (end < totalPaginas - 1) paginas.push('...');
        paginas.push(totalPaginas);
    }

    paginas.forEach(p => {
        const li = document.createElement('li');
        if (p === '...') {
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link border-0 text-muted">...</span>';
        } else {
            li.className = `page-item ${p === paginaActualRutas ? 'active fw-bold' : ''}`;
            li.innerHTML = `<button class="page-link" type="button" onclick="cambiarPaginaRutas(${p})">${p}</button>`;
        }
        lista.appendChild(li);
    });

    // Botón Siguiente
    const liNext = document.createElement('li');
    liNext.className = `page-item ${paginaActualRutas >= totalPaginas ? 'disabled' : ''}`;
    liNext.innerHTML = `<button class="page-link" type="button" onclick="cambiarPaginaRutas(${paginaActualRutas + 1})">Siguiente ›</button>`;
    lista.appendChild(liNext);
}

function cambiarPaginaRutas(nuevaPagina) {
    aplicarFiltrosYPaginarRutas(nuevaPagina);
    const cardRutas = document.getElementById('tablaRutas');
    if (cardRutas) {
        cardRutas.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function limpiarBuscadorRutas() {
    const input = document.getElementById('buscadorRutas');
    if (input) {
        input.value = '';
        aplicarFiltrosYPaginarRutas(1);
        input.focus();
    }
}

function filtrarTablaRutas() {
    aplicarFiltrosYPaginarRutas(1);
}

// Sincronización de pestañas con URL y soporte de navegación directa
document.addEventListener("DOMContentLoaded", function() {
    // Deshabilitar campos de tramos en rutas directas para evitar bloqueos por validación HTML5
    document.querySelectorAll('[id^="tramos_edit_"], #tramos_nuevos').forEach(div => {
        if (div.classList.contains('d-none')) {
            div.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        }
    });

    // Escuchar tarjetas KPI de rutas
    const tarjetasKpiRutas = document.querySelectorAll('.tarjeta-filtro-rapido-ruta');
    tarjetasKpiRutas.forEach(tarjeta => {
        tarjeta.addEventListener('click', function() {
            tarjetasKpiRutas.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const tipoKpi = this.getAttribute('data-filtro-kpi');
            const selectModalidad = document.getElementById('filtroModalidadRutas');
            const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
            const selectEstado = document.getElementById('filtroEstadoRutas');

            // Limpiar selectores antes de aplicar KPI
            if (selectModalidad) selectModalidad.value = '';
            if (selectTipoViaje) selectTipoViaje.value = '';
            if (selectEstado) selectEstado.value = '';

            if (tipoKpi === 'directo' && selectTipoViaje) {
                selectTipoViaje.value = 'directo';
            } else if (tipoKpi === 'conexion' && selectTipoViaje) {
                selectTipoViaje.value = 'conexion';
            } else if (tipoKpi === 'activas' && selectEstado) {
                selectEstado.value = 'activo';
            }

            aplicarFiltrosYPaginarRutas(1);
        });
    });

    // Escuchadores de eventos para todos los filtros de rutas
    const inputBuscador = document.getElementById('buscadorRutas');
    if (inputBuscador) {
        inputBuscador.addEventListener('input', () => aplicarFiltrosYPaginarRutas(1));
    }

    ['filtroOrigenRutas', 'filtroDestinoRutas', 'filtroModalidadRutas', 'filtroTipoViajeRutas', 'filtroEstadoRutas', 'filtroCorredorRutas', 'filtroLimiteRutas'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => aplicarFiltrosYPaginarRutas(1));
    });

    const btnResetRutas = document.getElementById('btnLimpiarTodosFiltrosRutas');
    if (btnResetRutas) {
        btnResetRutas.addEventListener('click', limpiarTodosFiltrosRutas);
    }

    const tabButtons = document.querySelectorAll('#flotaTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('data-bs-target');
            if (targetId) {
                const tabKey = targetId.replace('#tab-', '');
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabKey);
                window.history.replaceState(null, '', url.toString());
            }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        const triggerEl = document.querySelector(`#flotaTabs button[data-bs-target="#tab-${activeTab}"]`);
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
    }

    // Inicializar filtro de corredor y paginación en rutas
    const filtroCorredorUrl = urlParams.get('corredor_filtro') || (activeTab === 'rutas' ? urlParams.get('corredor_id') : '');
    const filtroSelect = document.getElementById('filtroCorredorRutas');
    if (filtroSelect && filtroCorredorUrl) {
        filtroSelect.value = filtroCorredorUrl;
    }
    aplicarFiltrosYPaginarRutas(1);
});
</script>