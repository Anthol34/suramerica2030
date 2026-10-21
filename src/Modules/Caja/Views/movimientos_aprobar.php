<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="text-primary text-uppercase fs-5 mb-0">Opciones de Caja / Por Aprobar Cajeros</h3>
        <small class="text-muted">Centro unificado de aprobación de anulaciones de ventas, egresos y gastos de sucursales</small>
    </div>
    <div>
        <?php if(($totalGeneralPendientes ?? 0) > 0): ?>
            <span class="badge bg-danger fs-6 px-3 py-2 shadow-sm animate__animated animate__pulse animate__infinite">
                ⚠️ <?= $totalGeneralPendientes ?> pendiente(s) por autorizar
            </span>
        <?php else: ?>
            <span class="badge bg-success fs-6 px-3 py-2 shadow-sm">
                ✅ Todo al día (0 pendientes)
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php 
            if($_GET['status'] == 'success') echo '✅ ' . htmlspecialchars($_GET['msg'] ?? 'Operación procesada correctamente.');
            else echo '❌ ' . htmlspecialchars($_GET['msg'] ?? 'Ocurrió un inconveniente.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- NAVEGACIÓN PRINCIPAL DE PESTAÑAS -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= ($subtab === 'solicitudes') ? 'active fw-bold text-danger' : '' ?>" href="?subtab=solicitudes">
            🎫 Solicitudes de Anulación de Ventas
            <?php if(!empty($cntSolicitudesPendientes) && $cntSolicitudesPendientes > 0): ?>
                <span class="badge bg-danger rounded-pill ms-1 animate__animated animate__pulse animate__infinite"><?= $cntSolicitudesPendientes ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($subtab === 'egresos') ? 'active fw-bold' : '' ?>" href="?subtab=egresos">
            💸 Egresos y Gastos de Sucursal
            <?php if(!empty($cntEgresosPendientes) && $cntEgresosPendientes > 0): ?>
                <span class="badge bg-warning text-dark rounded-pill ms-1"><?= $cntEgresosPendientes ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<!-- ============================================================== -->
<!-- PESTAÑA 1: SOLICITUDES DE ANULACIÓN DE VENTAS                  -->
<!-- ============================================================== -->
<?php if($subtab === 'solicitudes'): ?>

    <!-- 1. TARJETAS DE RESUMEN KPI -->
    <?php
        $cntPend = 0; $montoPend = 0;
        $cntAprob = 0; $montoAprob = 0;
        $cntRech = 0;
        if(!empty($solicitudes)) {
            foreach($solicitudes as $s) {
                if($s['ESTADO'] === 'PENDIENTE') { $cntPend++; $montoPend += floatval($s['MONTO_DEVOLVER']); }
                elseif($s['ESTADO'] === 'APROBADA') { $cntAprob++; $montoAprob += floatval($s['MONTO_DEVOLVER']); }
                elseif($s['ESTADO'] === 'RECHAZADA') { $cntRech++; }
            }
        }
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-warning bg-white h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Solicitudes Pendientes</span>
                        <h3 class="fw-bold text-warning mb-0 mt-1"><?= $cntPend ?></h3>
                        <small class="text-muted">Por reembolsar: <strong class="text-dark">$<?= number_format($montoPend, 2) ?></strong></small>
                    </div>
                    <div class="fs-1 text-warning opacity-75">⏳</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-success bg-white h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Solicitudes Aprobadas</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= $cntAprob ?></h3>
                        <small class="text-muted">Total devuelto: <strong class="text-dark">$<?= number_format($montoAprob, 2) ?></strong></small>
                    </div>
                    <div class="fs-1 text-success opacity-75">✔️</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-danger bg-white h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Solicitudes Denegadas</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1"><?= $cntRech ?></h3>
                        <small class="text-muted">Ventas mantenidas activas</small>
                    </div>
                    <div class="fs-1 text-danger opacity-75">❌</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. BARRA DE BÚSQUEDA Y FILTROS -->
    <div class="card shadow-sm border-0 mb-3 bg-light">
        <div class="card-body p-3">
            <form action="/suramerica2030/caja/aprobaciones" method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="subtab" value="solicitudes">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Buscar (Cliente / Cédula / Folio / Solicitud)</label>
                    <input type="text" name="buscar_solicitud" class="form-control form-control-sm" placeholder="Ej: Carlos, 1314..., Solicitud #1" value="<?= htmlspecialchars($filtrosSolicitudes['buscar_solicitud'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Estado</label>
                    <select name="estado_solicitud" class="form-select form-select-sm">
                        <option value="">Todas las Solicitudes</option>
                        <option value="PENDIENTE" <?= ($filtrosSolicitudes['estado_solicitud'] ?? '') === 'PENDIENTE' ? 'selected' : '' ?>>⏳ Pendientes de Aprobación</option>
                        <option value="APROBADA" <?= ($filtrosSolicitudes['estado_solicitud'] ?? '') === 'APROBADA' ? 'selected' : '' ?>>✔️ Aprobadas</option>
                        <option value="RECHAZADA" <?= ($filtrosSolicitudes['estado_solicitud'] ?? '') === 'RECHAZADA' ? 'selected' : '' ?>>❌ Denegadas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Tipo de Servicio</label>
                    <select name="tipo_venta" class="form-select form-select-sm">
                        <option value="">Todos los Servicios</option>
                        <option value="PASAJE" <?= ($filtrosSolicitudes['tipo_venta'] ?? '') === 'PASAJE' ? 'selected' : '' ?>>🎫 Pasajes</option>
                        <option value="ENCOMIENDA" <?= ($filtrosSolicitudes['tipo_venta'] ?? '') === 'ENCOMIENDA' ? 'selected' : '' ?>>📦 Encomiendas</option>
                        <option value="VALOR" <?= ($filtrosSolicitudes['tipo_venta'] ?? '') === 'VALOR' ? 'selected' : '' ?>>💲 Valores / Remesas</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-danger btn-sm w-50 fw-bold">Buscar</button>
                    <a href="/suramerica2030/caja/aprobaciones?subtab=solicitudes" class="btn btn-secondary btn-sm w-50 fw-bold">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. TABLA DE SOLICITUDES DE ANULACIÓN -->
    <div class="card shadow-sm border-0 table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
            <thead class="table-dark text-center">
                <tr>
                    <th style="width: 70px;"># Sol.</th>
                    <th>Servicio</th>
                    <th>Venta / Referencia</th>
                    <th>Cliente / Pasajero</th>
                    <th>Solicitado Por</th>
                    <th class="text-end">Monto Reembolso</th>
                    <th>Motivo Solicitud</th>
                    <th>Estado</th>
                    <th>Resolución Admin</th>
                    <th style="width: 150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($solicitudes)): ?>
                    <tr><td colspan="10" class="text-center py-4 text-muted">No se encontraron solicitudes de anulación en este criterio.</td></tr>
                <?php else: ?>
                    <?php foreach($solicitudes as $s): ?>
                        <tr id="fila_sol_<?= $s['ID_SOLICITUD'] ?>">
                            <td class="text-center fw-bold text-primary">#<?= $s['ID_SOLICITUD'] ?></td>
                            <td class="text-center">
                                <?php if($s['TIPO_VENTA'] === 'PASAJE'): ?>
                                    <span class="badge bg-primary">🎫 PASAJE</span>
                                <?php elseif($s['TIPO_VENTA'] === 'ENCOMIENDA'): ?>
                                    <span class="badge bg-warning text-dark">📦 ENCOMIENDA</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark">💲 REMESA</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>Factura #<?= $s['ID_FACTURA'] ?? $s['ID_REFERENCIA'] ?></strong><br>
                                <span class="badge bg-light text-dark border">📍 Caja <?= htmlspecialchars($s['SUCURSAL_VENTA_NOMBRE'] ?? $s['SUCURSAL_SOLICITA'] ?? 'Sucursal') ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars(trim(($s['CLI_NOMBRE'] ?? '') . ' ' . ($s['CLI_APELLIDO'] ?? '')) ?: 'N/A') ?></strong><br>
                                <small class="text-muted">CI: <?= htmlspecialchars($s['ID_CLIENTE'] ?? 'S/N') ?></small>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($s['USUARIO_SOLICITA_NOMBRE'] ?? 'Cajero') ?></span><br>
                                <small class="text-muted"><?= date('Y-m-d H:i', strtotime($s['FECHA_SOLICITUD'])) ?></small>
                            </td>
                            <td class="text-end fw-bold text-danger fs-6">
                                $<?= number_format($s['MONTO_DEVOLVER'], 2) ?>
                            </td>
                            <td style="max-width: 220px;" class="text-wrap">
                                <em>"<?= htmlspecialchars($s['MOTIVO']) ?>"</em>
                            </td>
                            <td class="text-center">
                                <?php if($s['ESTADO'] === 'PENDIENTE'): ?>
                                    <span class="badge bg-warning text-dark border border-dark">⏳ PENDIENTE</span>
                                <?php elseif($s['ESTADO'] === 'APROBADA'): ?>
                                    <span class="badge bg-success">✔️ APROBADA</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">❌ RECHAZADA</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 180px;" class="small">
                                <?php if($s['ESTADO'] === 'APROBADA'): ?>
                                    <span class="text-success fw-bold">Aprobado</span> por <?= htmlspecialchars($s['USUARIO_RESPONDE_NOMBRE'] ?? 'Admin') ?><br>
                                    <small class="text-muted"><?= $s['FECHA_RESPUESTA'] ? date('Y-m-d H:i', strtotime($s['FECHA_RESPUESTA'])) : '' ?></small>
                                <?php elseif($s['ESTADO'] === 'RECHAZADA'): ?>
                                    <span class="text-danger fw-bold">Denegado</span> por <?= htmlspecialchars($s['USUARIO_RESPONDE_NOMBRE'] ?? 'Admin') ?><br>
                                    <small class="text-danger"><em>"<?= htmlspecialchars($s['MOTIVO_RESPUESTA'] ?? '') ?>"</em></small>
                                <?php else: ?>
                                    <span class="text-muted">En espera de decisión</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($s['ESTADO'] === 'PENDIENTE'): ?>
                                    <?php if(isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2])): ?>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <!-- APROBAR -->
                                            <form action="/suramerica2030/caja/procesar_solicitud_anulacion" method="POST" style="display:inline;" data-live="true" data-live-highlight="#fila_sol_<?= $s['ID_SOLICITUD'] ?>">
                                                <input type="hidden" name="id_solicitud" value="<?= $s['ID_SOLICITUD'] ?>">
                                                <input type="hidden" name="accion" value="APROBAR">
                                                <button type="submit" class="btn btn-sm btn-success fw-bold" title="Aprobar Anulación" onclick="return confirm('¿Confirmas que deseas APROBAR la anulación #<?= $s['ID_SOLICITUD'] ?>? Se anulará la venta y se registrará un egreso de $<?= number_format($s['MONTO_DEVOLVER'], 2) ?> en la caja de <?= htmlspecialchars($s['SUCURSAL_VENTA_NOMBRE'] ?? $s['SUCURSAL_SOLICITA'] ?? 'la sucursal') ?>.');">
                                                    ✔️ Aprobar
                                                </button>
                                            </form>

                                            <!-- RECHAZAR -->
                                            <button type="button" class="btn btn-sm btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#modalDenegarSol<?= $s['ID_SOLICITUD'] ?>" title="Denegar Solicitud">
                                                ❌ Denegar
                                            </button>
                                        </div>

                                        <!-- MODAL RECHAZO SOLICITUD -->
                                        <div class="modal fade text-start" id="modalDenegarSol<?= $s['ID_SOLICITUD'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-sm">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white py-2">
                                                        <h6 class="modal-title fs-6">Denegar Solicitud #<?= $s['ID_SOLICITUD'] ?></h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="/suramerica2030/caja/procesar_solicitud_anulacion" method="POST" data-live="true" data-live-highlight="#fila_sol_<?= $s['ID_SOLICITUD'] ?>">
                                                        <div class="modal-body p-3">
                                                            <input type="hidden" name="id_solicitud" value="<?= $s['ID_SOLICITUD'] ?>">
                                                            <input type="hidden" name="accion" value="RECHAZAR">
                                                            <label class="form-label small fw-bold">Motivo de denegación / rechazo:</label>
                                                            <textarea name="motivo_rechazo" class="form-control form-control-sm" rows="3" required placeholder="Explique por qué se deniega esta solicitud..."></textarea>
                                                        </div>
                                                        <div class="modal-footer p-1">
                                                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold">Confirmar Denegación</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark border">⏳ En Revisión</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">🔒 Concluida</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<!-- ============================================================== -->
<!-- PESTAÑA 2: EGRESOS Y GASTOS DE SUCURSAL                        -->
<!-- ============================================================== -->
<?php else: ?>

    <!-- PANEL DE BUSCADOR Y FILTROS -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <form action="/suramerica2030/caja/aprobaciones" method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="subtab" value="egresos">
                <div class="col-md-2">
                    <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Código</label>
                    <input type="text" name="codigo" class="form-control form-control-sm" placeholder="Ej: MANI..." value="<?= htmlspecialchars($_GET['codigo'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Sucursal</label>
                    <select name="sucursal" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php if(!empty($sucursales)): ?>
                            <?php foreach($sucursales as $s): ?>
                                <option value="<?= $s['ID_SUCURSAL'] ?>" <?= (isset($_GET['sucursal']) && $_GET['sucursal'] == $s['ID_SUCURSAL']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['CIUDAD']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Seleccione...</option>
                        <option value="POR_APROBAR" <?= (($_GET['estado'] ?? '') === 'POR_APROBAR') ? 'selected' : '' ?>>Por Aprobar</option>
                        <option value="APROBADO" <?= (($_GET['estado'] ?? '') === 'APROBADO') ? 'selected' : '' ?>>Aprobado</option>
                        <option value="RECHAZADO" <?= (($_GET['estado'] ?? '') === 'RECHAZADO') ? 'selected' : '' ?>>Rechazado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Fecha de inicio / fin</label>
                    <input type="date" name="fecha_inicio" class="form-control form-control-sm mb-1" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
                    <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-danger btn-sm w-50 fw-bold">Buscar</button>
                    <a href="/suramerica2030/caja/aprobaciones?subtab=egresos" class="btn btn-secondary btn-sm w-50 fw-bold">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <h6 class="fw-bold mb-2">Egresos y Gastos por Aprobar</h6>
    <div class="card shadow-sm border-0 table-responsive">
        <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 11px;">
            <thead class="table-light text-center">
                <tr>
                    <th>Código</th>
                    <th>Fecha</th>
                    <th>Oficina</th>
                    <th>Cajero(a)</th>
                    <th>Origen</th>
                    <th>Tipo</th>
                    <th style="width: 20%;">Motivo</th>
                    <th>Estado</th>
                    <th>Valor</th>
                    <th>Motivo rechazo</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($movimientos_pendientes)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-3">No hay movimientos en este rango.</td></tr>
                <?php else: ?>
                    <?php foreach($movimientos_pendientes as $mov): ?>
                        <tr id="fila_egreso_<?= $mov['ID_MOVIMIENTO'] ?>">
                            <td class="text-primary fw-bold"><?= htmlspecialchars($mov['CODIGO_MOVIMIENTO']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($mov['FECHA_HORA'])) ?></td>
                            <td><?= htmlspecialchars($mov['SUCURSAL_NOMBRE']) ?></td>
                            <td><?= htmlspecialchars($mov['CAJERO']) ?></td>
                            <td><?= htmlspecialchars($mov['ORIGEN_MOVIMIENTO']) ?></td>
                            <td><?= htmlspecialchars($mov['TIPO_EGRESO']) ?></td>
                            <td class="text-wrap" style="max-width: 200px;"><?= htmlspecialchars($mov['CONCEPTO']) ?></td>
                            <td class="text-center">
                                <?php if($mov['ESTADO_APROBACION'] == 'POR_APROBAR'): ?>
                                    <span class="badge bg-warning text-dark border">POR APROBAR</span>
                                <?php elseif($mov['ESTADO_APROBACION'] == 'APROBADO'): ?>
                                    <span class="badge bg-success">APROBADO</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">RECHAZADO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold"><?= number_format($mov['MONTO'], 2) ?></td>
                            <td class="text-danger" style="max-width: 150px;"><?= htmlspecialchars($mov['MOTIVO_RECHAZO'] ?? '') ?></td>
                            <td class="text-center">
                                <?php if(!empty($mov['CIERRE_DEPOSITO_ESTADO']) && $mov['CIERRE_DEPOSITO_ESTADO'] === 'APROBADO'): ?>
                                    <span class="badge bg-secondary" title="La caja de esta sucursal y fecha ya fue auditada por Tesorería. Inmutable.">🔒 Caja Auditada</span>
                                <?php elseif(isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2]) && $mov['ESTADO_APROBACION'] == 'POR_APROBAR'): ?>
                                    <div class="btn-group">
                                        <form action="/suramerica2030/caja/procesar_aprobacion" method="POST" style="display:inline;" data-live="true" data-live-highlight="#fila_egreso_<?= $mov['ID_MOVIMIENTO'] ?>">
                                            <input type="hidden" name="id_movimiento" value="<?= $mov['ID_MOVIMIENTO'] ?>">
                                            <input type="hidden" name="accion" value="APROBAR">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Aprobar">✔️</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRechazar<?= $mov['ID_MOVIMIENTO'] ?>" title="Rechazar/Anular">❌</button>
                                    </div>

                                    <!-- MODAL PARA DETALLAR MOTIVO DE RECHAZO -->
                                    <div class="modal fade text-start" id="modalRechazar<?= $mov['ID_MOVIMIENTO'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h6 class="modal-title fs-6">Rechazar Movimiento</h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="/suramerica2030/caja/procesar_aprobacion" method="POST" data-live="true" data-live-highlight="#fila_egreso_<?= $mov['ID_MOVIMIENTO'] ?>">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_movimiento" value="<?= $mov['ID_MOVIMIENTO'] ?>">
                                                        <input type="hidden" name="accion" value="RECHAZAR">
                                                        <label class="form-label fw-bold">Detalle el motivo de anulación/rechazo:</label>
                                                        <textarea name="motivo_rechazo" class="form-control" rows="3" required placeholder="Ej. Factura duplicada, monto incorrecto..."></textarea>
                                                    </div>
                                                    <div class="modal-footer p-1">
                                                        <button type="submit" class="btn btn-danger w-100 fw-bold">Confirmar Rechazo</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:10px;">Sin acciones</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- CUADRO DE RESUMEN AL FINAL COMO EN EL PDF -->
        <div class="card-footer bg-white border-0 d-flex justify-content-end pb-4 pt-3">
            <div style="width: 250px;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Aprobados</span> 
                    <span class="fw-bold"><?= number_format($totales['aprobados'] ?? 0, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Por aprobar</span> 
                    <span class="fw-bold"><?= number_format($totales['por_aprobar'] ?? 0, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Rechazados</span> 
                    <span class="text-danger fw-bold"><?= number_format($totales['rechazados'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>