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