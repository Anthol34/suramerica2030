<?php
$esAdmin = (isset($_SESSION['rol_id']) && intval($_SESSION['rol_id']) === 1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="text-primary">Cuadre de Caja Diario</h3>
    <form action="/suramerica2030/caja" method="GET" class="d-flex align-items-center gap-2 bg-white p-2 rounded shadow-sm border">
        <?php if($esAdmin): ?>
            <label class="fw-bold mb-0 text-secondary">Oficina:</label>
            <select name="sucursal" class="form-select form-select-sm fw-bold text-dark" onchange="this.form.submit()">
                <option value="<?= $_SESSION['sucursal_id'] ?>">📍 Mi Sucursal Actual</option>
                <option value="ALL" <?= $sucursal_id === 'ALL' ? 'selected' : '' ?>>🌍 Consolidado Global</option>
                <?php foreach($sucursales as $s): ?>
                    <?php if($s['ID_SUCURSAL'] != $_SESSION['sucursal_id']): ?>
                        <option value="<?= $s['ID_SUCURSAL'] ?>" <?= $sucursal_id == $s['ID_SUCURSAL'] ? 'selected' : '' ?>><?= htmlspecialchars($s['CIUDAD']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <label class="fw-bold mb-0 text-secondary ms-2">Fecha:</label>
        <input type="date" name="fecha" class="form-control form-control-sm fw-bold text-primary" value="<?= htmlspecialchars($fecha_consulta) ?>" onchange="this.form.submit()">
    </form>
</div>
<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
        <?php 
            if($_GET['status'] == 'success') echo '✅ Movimiento registrado en caja.';
            elseif($_GET['status'] == 'success_apertura') echo '🟢 ¡Caja abierta exitosamente! El turno de caja se ha iniciado formalmente.';
            elseif($_GET['status'] == 'success_cierre') echo '🔒 Caja cerrada exitosamente. Ya no se permiten movimientos para esta fecha.';
            elseif($_GET['status'] == 'success_mover') echo '📅 Se trasladaron exitosamente ' . intval($_GET['count'] ?? 1) . ' movimiento(s) a la fecha ' . htmlspecialchars($_GET['nueva_fecha'] ?? '') . '.';
            else echo '❌ Error: ' . htmlspecialchars($_GET['msg']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if($cierreDia): ?>
    <div class="alert alert-secondary border-secondary shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-secondary fs-6">🔒 CAJA CERRADA OFICIALMENTE</span>
                <span class="text-muted small">Corte ejecutado el <strong><?= $cierreDia['FECHA_HORA'] ?></strong> por <strong><?= htmlspecialchars($cierreDia['CAJERO_NOMBRE'] ?? 'Cajero') ?></strong></span>
            </div>
            <div style="font-size: 12px;">
                <span>Fondo Base: <strong>$<?= number_format($cierreDia['VALOR_INICIAL'] ?? 0, 2) ?></strong></span> |
                <span>Efectivo Arqueado: <strong>$<?= number_format($cierreDia['EFECTIVO_FISICO'] ?? $cierreDia['SALDO_FINAL'], 2) ?></strong></span> |
                <?php $dif = floatval($cierreDia['DIFERENCIA_ARQUEO'] ?? 0); ?>
                <span>Diferencia: <strong class="<?= abs($dif) < 0.01 ? 'text-success' : ($dif < 0 ? 'text-danger' : 'text-primary') ?>"><?= abs($dif) < 0.01 ? 'Exacto ($0.00)' : ($dif < 0 ? 'Faltante -$'.number_format(abs($dif), 2) : 'Sobrante +$'.number_format($dif, 2)) ?></strong></span> |
                <span>Exigible a Depositar: <strong class="text-primary">$<?= number_format($cierreDia['VALOR_A_DEPOSITAR'] ?? ($cierreDia['SALDO_FINAL'] - $cierreDia['COMISION_EMPLEADOS']), 2) ?></strong></span> |
                <span>Depositado: <strong class="text-success">$<?= number_format($cierreDia['DEPOSITO'], 2) ?></strong></span> |
                <span>Por Depositar: <strong class="<?= ($cierreDia['V_POR_DEPOSITAR'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-success' ?>">$<?= number_format($cierreDia['V_POR_DEPOSITAR'] ?? 0, 2) ?></strong></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="/suramerica2030/caja/ticket_cierre?id=<?= $cierreDia['ID_CIERRE'] ?>" target="_blank" class="btn btn-outline-dark btn-sm fw-bold shadow-sm" title="Imprimir en impresora térmica EPSON de 80mm">
                🖨️ Ticket Cierre (80mm)
            </a>
            <a href="/suramerica2030/caja/depositos?sucursal=<?= $sucursal_id ?>" class="btn btn-primary btn-sm fw-bold shadow-sm">
                🏦 Gestionar Depósitos
            </a>
        </div>
    </div>
<?php elseif(isset($aperturaDia) && $aperturaDia): ?>
    <div class="alert alert-success border-success shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-success fs-6">🟢 CAJA EN OPERACIÓN (ABIERTA)</span>
                <span class="text-muted small">Turno iniciado: <strong><?= date('H:i', strtotime($aperturaDia['APERTURA_FECHA_HORA'])) ?></strong> por <strong><?= htmlspecialchars($aperturaDia['CAJERO_NOMBRE'] ?? 'Cajero') ?></strong></span>
            </div>
            <div style="font-size: 12.5px;">
                <span>Fondo Inicial (Base en Gaveta): <strong class="text-dark">$<?= number_format($aperturaDia['VALOR_INICIAL'], 2) ?></strong></span> |
                <span>Ventas en Efectivo: <strong class="text-success">$<?= number_format($totales['efectivo'], 2) ?></strong></span> |
                <span>Efectivo Total Esperado en Gaveta: <strong class="text-primary fs-6">$<?= number_format($totales['efectivo_con_fondo'], 2) ?></strong></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="/suramerica2030/caja/historial_cierres?fecha=<?= $fecha_consulta ?>&sucursal=<?= $sucursal_id ?>" class="btn btn-danger btn-sm fw-bold shadow-sm">
                🔒 Ir a Cierre & Arqueo
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning border-warning shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning text-dark fs-6">🟡 CAJA PENDIENTE DE APERTURA</span>
                <span class="text-muted small">Fecha: <strong><?= htmlspecialchars($fecha_consulta) ?></strong></span>
            </div>
            <p class="mb-0 small text-dark">
                Esta caja aún no ha sido abierta formalmente para hoy. Registra el <strong>Fondo Inicial (Base en Efectivo)</strong> para dar inicio al turno.
            </p>
        </div>
        <div>
            <?php if($sucursal_id !== 'ALL'): ?>
                <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
                    🟢 Abrir Caja / Iniciar Turno
                </button>
            <?php else: ?>
                <small class="text-muted fst-italic">Selecciona una sucursal específica para abrir caja</small>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMovimientos" type="button">📊 Movimientos del Día</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHistorial" type="button">📚 Historial de Cierres</button></li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="tabMovimientos">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white shadow-sm border-0" style="background: <?= $cierreDia ? '#6c757d' : '#28a745' ?>;">
                    <div class="card-body">
                        <h6 class="card-title fw-bold">Efectivo Físico</h6>
                        <h3 class="mb-0">$ <?= number_format($totales['efectivo'], 2) ?></h3>
                        <div class="small mt-1 opacity-75" style="font-size: 11px;">
                            Fondo Base: $<?= number_format($totales['valor_inicial'], 2) ?> | Gaveta: <strong>$<?= number_format($totales['efectivo_con_fondo'], 2) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3"><div class="card text-white bg-info shadow-sm border-0"><div class="card-body"><h6 class="card-title fw-bold">Transferencias Bancarias</h6><h3>$ <?= number_format($totales['transferencia'], 2) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-dark bg-warning shadow-sm border-0"><div class="card-body"><h6 class="card-title fw-bold">Tarjetas (POS)</h6><h3>$ <?= number_format($totales['tarjeta'], 2) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-white bg-primary shadow-sm border-0"><div class="card-body"><h6 class="card-title fw-bold">SALDO NETO COBRADO</h6><h3>$ <?= number_format($totales['saldo_neto'], 2) ?></h3></div></div></div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-dark text-white fw-bold">Registrar Movimiento Manual</div>
                    <div class="card-body">
                        <?php if($sucursal_id === 'ALL'): ?>
                            <div class="alert alert-info text-center p-3 mb-0"><strong>Modo Global Activo</strong><br><small>Para registrar un movimiento manual o cerrar caja, debes seleccionar una sucursal específica.</small></div>
                        <?php elseif($cierreDia): ?>
                            <div class="alert alert-danger text-center p-3 mb-0"><strong>Caja Cerrada</strong><br><small>No se permiten más registros para esta fecha.</small></div>
                        <?php else: ?>
                            <form action="/suramerica2030/caja/guardar" method="POST">
                                <input type="hidden" name="fecha_actual" value="<?= htmlspecialchars($fecha_consulta) ?>">
                                <div class="mb-2"><label>Tipo de Movimiento</label><select name="tipo_movimiento" class="form-select" required><option value="INGRESO">Ingreso (Otros Ingresos)</option><option value="EGRESO">Egreso (Pago Luz, Viáticos)</option></select></div>
                                <div class="mb-2"><label>Método</label><select name="metodo_pago" class="form-select" required><option value="EFECTIVO">Efectivo</option><option value="TRANSFERENCIA">Transferencia</option></select></div>
                                <div class="mb-2"><label>Monto ($)</label><input type="number" step="0.01" name="monto" class="form-control" required></div>
                                <div class="mb-3"><label>Concepto / Detalle</label><input type="text" name="concepto" class="form-control" required placeholder="Ej: Gastos de oficina..."></div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold">Guardar Movimiento</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if(!$cierreDia && ($estadoCaja ?? '') === 'SIN_ABRIR' && $sucursal_id !== 'ALL'): ?>
                    <div class="card shadow-sm border-0 border-start border-warning border-4 bg-light mb-3">
                        <div class="card-body p-3 text-center">
                            <div class="text-warning mb-1" style="font-size: 1.8rem;">🟡</div>
                            <h6 class="fw-bold text-dark mb-1">Caja Pendiente de Apertura</h6>
                            <p class="text-muted mb-3" style="font-size: 11px;">
                                Inicia el turno registrando el fondo inicial de efectivo para dar cambio / vuelto.
                            </p>
                            <button type="button" class="btn btn-success btn-sm w-100 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
                                🟢 Abrir Caja / Iniciar Turno
                            </button>
                        </div>
                    </div>
                <?php elseif(!$cierreDia && !empty($movimientos) && $sucursal_id !== 'ALL'): ?>
                    <div class="card shadow-sm border-0 border-start border-danger border-4 bg-light mb-3">
                        <div class="card-body p-3 text-center">
                            <div class="text-danger mb-1" style="font-size: 1.8rem;">🔒</div>
                            <h6 class="fw-bold text-danger mb-1">Cierre de Turno</h6>
                            <p class="text-muted mb-3" style="font-size: 11px;">
                                El cierre formal y arqueo detallado de caja se realiza en la sección especializada de <strong>Cierres de Caja</strong>.
                            </p>
                            <a href="/suramerica2030/caja/historial_cierres?fecha=<?= $fecha_consulta ?>&sucursal=<?= $sucursal_id ?>" class="btn btn-danger btn-sm w-100 fw-bold shadow-sm">
                                📊 Ir a Cierre de Caja (Arqueo Detallado)
                            </a>
                        </div>
                    </div>
                <?php elseif($cierreDia): ?>
                    <div class="card shadow-sm border-0 border-start border-secondary border-4 bg-light mb-3">
                        <div class="card-body p-3 text-center">
                            <div class="text-secondary mb-1" style="font-size: 1.8rem;">🔒</div>
                            <h6 class="fw-bold text-secondary mb-1">Caja Cerrada</h6>
                            <p class="text-muted mb-2" style="font-size: 11px;">
                                Esta caja fue cerrada el <strong><?= $cierreDia['FECHA_HORA'] ?></strong>.
                            </p>
                            <a href="/suramerica2030/caja/historial_cierres?fecha=<?= $fecha_consulta ?>&sucursal=<?= $sucursal_id ?>" class="btn btn-outline-secondary btn-sm w-100 fw-bold">
                                👁️ Ver Resumen y Cierre Auditado
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-secondary">Transacciones del <?= $fecha_consulta ?> <?= $sucursal_id === 'ALL' ? '(GLOBAL)' : '' ?></span>
                            <?php if($esAdmin && !$cierreDia && !empty($movimientos)): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold shadow-sm" id="btnMoverSeleccionados" onclick="abrirModalMoverFecha()" disabled title="Selecciona una o más transacciones para reasignar su fecha">
                                    📅 Mover Fecha (<span id="countMover">0</span>)
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <?php if(!$cierreDia && !empty($movimientos)): ?><a href="/suramerica2030/caja/imprimir?fecha=<?= $fecha_consulta ?>&sucursal=<?= $sucursal_id ?>" target="_blank" class="btn btn-sm btn-outline-dark">🖨️ Imprimir Pre-cierre</a><?php endif; ?>
                            <?php if($cierreDia): ?><a href="/suramerica2030/caja/imprimir?fecha=<?= $fecha_consulta ?>&sucursal=<?= $sucursal_id ?>" target="_blank" class="btn btn-sm btn-outline-dark">📄 PDF (Cierre Auditado)</a><?php endif; ?>
                            <a href="/suramerica2030/caja/imprimir?fecha=<?= $fecha_consulta ?>&sucursal=<?= $sucursal_id ?>&mensual=1" target="_blank" class="btn btn-sm btn-primary">📅 PDF Mensual</a>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <?php if($esAdmin): ?>
                                        <th style="width: 35px;" class="text-center">
                                            <input type="checkbox" id="checkAllMovimientos" class="form-check-input" onchange="seleccionarTodosMovimientos(this)" <?= $cierreDia ? 'disabled title="Caja cerrada"' : 'title="Seleccionar todos"' ?>>
                                        </th>
                                    <?php endif; ?>
                                    <th>Hora</th>
                                    <th>Concepto</th>
                                    <?= $sucursal_id === 'ALL' ? '<th>Sucursal</th>' : '' ?>
                                    <th>Método</th>
                                    <th class="text-end">Ingreso (+)</th>
                                    <th class="text-end">Egreso (-)</th>
                                    <?php if($esAdmin): ?>
                                        <th style="width: 45px;" class="text-center">Acción</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($movimientos)): ?>
                                    <?php $colspanEmpty = ($sucursal_id === 'ALL' ? 6 : 5) + ($esAdmin ? 2 : 0); ?>
                                    <tr><td colspan="<?= $colspanEmpty ?>" class="text-center text-muted py-4">No hay movimientos registrados en esta fecha.</td></tr>
                                <?php else: ?>
                                    <?php foreach($movimientos as $mov): ?>
                                        <tr>
                                            <?php if($esAdmin): ?>
                                                <td class="text-center">
                                                    <?php if($cierreDia): ?>
                                                        <span class="badge bg-secondary p-1" title="Caja cerrada: No modificable">🔒</span>
                                                    <?php else: ?>
                                                        <input type="checkbox" class="form-check-input mov-checkbox" value="<?= $mov['ID_MOVIMIENTO'] ?>" data-concepto="<?= htmlspecialchars($mov['CONCEPTO']) ?>" data-monto="<?= number_format($mov['MONTO'], 2) ?>" data-tipo="<?= $mov['TIPO_MOVIMIENTO'] ?>" data-factura="<?= $mov['ID_FACTURA'] ? '#'.$mov['ID_FACTURA'] : '' ?>" data-hora="<?= date('H:i', strtotime($mov['FECHA_HORA'])) ?>" onchange="actualizarContadorMovimientos()">
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <td><?= date('H:i', strtotime($mov['FECHA_HORA'])) ?></td>
                                            <td><?= htmlspecialchars($mov['CONCEPTO']) ?><?= $mov['ID_FACTURA'] ? ' <span class="badge bg-secondary">Fact #'.$mov['ID_FACTURA'].'</span>' : '' ?></td>
                                            <?= $sucursal_id === 'ALL' ? '<td><span class="badge bg-info text-dark">'.$mov['SUCURSAL_NOMBRE'].'</span></td>' : '' ?>
                                            <td><span class="badge bg-light text-dark border"><?= $mov['METODO_PAGO'] ?></span></td>
                                            <?php if($mov['TIPO_MOVIMIENTO'] == 'INGRESO'): ?><td class="text-end text-success fw-bold">$ <?= number_format($mov['MONTO'], 2) ?></td><td class="text-end">-</td>
                                            <?php else: ?><td class="text-end">-</td><td class="text-end text-danger fw-bold">$ <?= number_format($mov['MONTO'], 2) ?></td><?php endif; ?>
                                            <?php if($esAdmin): ?>
                                                <td class="text-center">
                                                    <?php if(!$cierreDia): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1 border-0" onclick="moverMovimientoIndividual(<?= $mov['ID_MOVIMIENTO'] ?>, '<?= htmlspecialchars(addslashes($mov['CONCEPTO'])) ?>', '<?= number_format($mov['MONTO'], 2) ?>', '<?= $mov['TIPO_MOVIMIENTO'] ?>', '<?= $mov['ID_FACTURA'] ? '#'.$mov['ID_FACTURA'] : '' ?>', '<?= date('H:i', strtotime($mov['FECHA_HORA'])) ?>')" title="Mover a otra fecha">📅</button>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 11px;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold border-top border-2 border-dark">
                                <tr>
                                    <?php $colspanTotales = ($sucursal_id === 'ALL' ? 4 : 3) + ($esAdmin ? 1 : 0); ?>
                                    <td colspan="<?= $colspanTotales ?>" class="text-end">TOTALES DEL DÍA:</td>
                                    <td class="text-end text-success fs-5">$ <?= number_format($totales['ingresos'], 2) ?></td>
                                    <td class="text-end text-danger fs-5">$ <?= number_format($totales['egresos'], 2) ?></td>
                                    <?= $esAdmin ? '<td></td>' : '' ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="tabHistorial">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>ID Cierre</th><th>Fecha</th><?= $sucursal_id === 'ALL' ? '<th>Sucursal</th>' : '' ?><th>Ejecutado Por</th><th class="text-success">T. Ingresos</th><th class="text-danger">T. Egresos</th><th class="text-primary">Saldo N.</th><th class="text-success">UTILIDAD</th><th class="text-center">Reporte</th></tr></thead>
                    <tbody>
                        <?php if(empty($historialCierres)): ?>
                            <tr><td colspan="<?= $sucursal_id === 'ALL' ? 9 : 8 ?>" class="text-center text-muted py-4">Aún no hay cierres registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach($historialCierres as $h): ?>
                                <tr>
                                    <td><span class="badge bg-secondary">#<?= $h['ID_CIERRE'] ?></span></td>
                                    <td class="fw-bold">📅 <?= $h['FECHA_CAJA'] ?></td>
                                    <?= $sucursal_id === 'ALL' ? '<td><span class="badge bg-info text-dark">'.$h['SUCURSAL_NOMBRE'].'</span></td>' : '' ?>
                                    <td><?= htmlspecialchars($h['CAJERO']) ?></td>
                                    <td class="fw-bold text-success">$<?= number_format($h['TOTAL_INGRESOS'], 2) ?></td>
                                    <td class="fw-bold text-danger">$<?= number_format($h['TOTAL_EGRESOS'], 2) ?></td>
                                    <td class="fw-bold text-primary">$<?= number_format($h['SALDO_FINAL'], 2) ?></td>
                                    <td class="fw-bold text-success" style="background:#e8f5e9;">$<?= number_format($h['UTILIDAD'] ?? 0, 2) ?></td>
                                    <td class="text-center"><a href="/suramerica2030/caja/imprimir?fecha=<?= $h['FECHA_CAJA'] ?>&sucursal=<?= $h['ID_SUCURSAL'] ?>" target="_blank" class="btn btn-sm btn-dark">📄 PDF</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if($esAdmin): ?>
<!-- ========================================== -->
<!-- MODAL: REASIGNAR FECHA DE MOVIMIENTOS CAJA -->
<!-- ========================================== -->
<div class="modal fade" id="modalMoverFecha" tabindex="-1" aria-labelledby="modalMoverFechaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalMoverFechaLabel">
                    <span>📅</span> Reasignar Fecha de Transacciones
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/suramerica2030/caja/moverfecha" method="POST" id="formMoverFecha" onsubmit="return validarFormMoverFecha()">
                <input type="hidden" name="fecha_actual" value="<?= htmlspecialchars($fecha_consulta) ?>">
                <input type="hidden" name="sucursal_actual" value="<?= htmlspecialchars($sucursal_id) ?>">
                <div id="hiddenMovimientosContainer"></div>

                <div class="modal-body p-3">
                    <div class="alert alert-info border-info d-flex align-items-start gap-2 py-2 mb-3" style="font-size: 12px;">
                        <span class="fs-5">ℹ️</span>
                        <div>
                            <strong>Control de Seguridad Contable:</strong>
                            El cambio de fecha se procesará <strong>únicamente si las cajas</strong> (origen y destino) están <strong>ABIERTAS</strong>. Si la caja destino ya fue cerrada, el sistema bloqueará la operación para proteger la auditoría.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary mb-1" style="font-size: 12px;">
                            Transacciones seleccionadas (<span id="modalNumSeleccionados" class="text-primary fw-bold">0</span>):
                        </label>
                        <div id="modalDetalleSeleccionados" class="border rounded p-2 bg-light" style="max-height: 180px; overflow-y: auto;"></div>
                    </div>

                    <div class="mb-2">
                        <label for="inputNuevaFechaMover" class="form-label fw-bold text-dark">
                            📅 Nueva Fecha de Cuadre de Caja:
                        </label>
                        <input type="date" name="nueva_fecha" id="inputNuevaFechaMover" class="form-control form-control-lg fw-bold text-primary" required>
                        <small class="text-muted" style="font-size: 11px;">
                            Las facturas y recibos asociados sincronizarán automáticamente su fecha contable.
                        </small>
                    </div>
                </div>
                <div class="modal-footer p-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">
                        ✓ Confirmar y Trasladar Fecha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function actualizarContadorMovimientos() {
    const checks = document.querySelectorAll('.mov-checkbox:checked');
    const count = checks.length;
    const btn = document.getElementById('btnMoverSeleccionados');
    const countSpan = document.getElementById('countMover');
    
    if (countSpan) countSpan.textContent = count;
    if (btn) {
        btn.disabled = count === 0;
        if (count > 0) {
            btn.classList.remove('btn-outline-warning');
            btn.classList.add('btn-warning');
        } else {
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-outline-warning');
        }
    }
}

function seleccionarTodosMovimientos(master) {
    const checks = document.querySelectorAll('.mov-checkbox');
    checks.forEach(c => c.checked = master.checked);
    actualizarContadorMovimientos();
}

function abrirModalMoverFecha() {
    const checks = document.querySelectorAll('.mov-checkbox:checked');
    if (checks.length === 0) {
        alert('Por favor selecciona al menos una transacción para cambiar de fecha.');
        return;
    }
    poblarModalMoverFecha(checks);
}

function moverMovimientoIndividual(id, concepto, monto, tipo, factura, hora) {
    const checks = document.querySelectorAll('.mov-checkbox');
    checks.forEach(c => c.checked = (c.value == id));
    actualizarContadorMovimientos();
    
    const targetCheck = document.querySelector('.mov-checkbox[value="' + id + '"]');
    if (targetCheck) {
        poblarModalMoverFecha([targetCheck]);
    }
}

function poblarModalMoverFecha(checks) {
    const containerHidden = document.getElementById('hiddenMovimientosContainer');
    const containerDetalle = document.getElementById('modalDetalleSeleccionados');
    const numSpan = document.getElementById('modalNumSeleccionados');
    
    if (!containerHidden || !containerDetalle) return;
    
    containerHidden.innerHTML = '';
    containerDetalle.innerHTML = '';
    if (numSpan) numSpan.textContent = checks.length;
    
    checks.forEach(c => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'movimientos_ids[]';
        hiddenInput.value = c.value;
        containerHidden.appendChild(hiddenInput);
        
        const item = document.createElement('div');
        item.className = 'd-flex justify-content-between align-items-center py-1 border-bottom';
        item.style.fontSize = '12px';
        
        const isIngreso = c.dataset.tipo === 'INGRESO';
        const badgeColor = isIngreso ? 'bg-success' : 'bg-danger';
        const badgeText = isIngreso ? '+ Ingreso' : '- Egreso';
        const facturaText = c.dataset.factura ? '<span class="badge bg-secondary ms-1">Fact ' + c.dataset.factura + '</span>' : '';
        
        item.innerHTML = '<div>' +
            '<span class="badge ' + badgeColor + '" style="font-size:10px;">' + badgeText + '</span> ' +
            '<strong>' + (c.dataset.concepto || 'Transacción') + '</strong>' +
            facturaText +
            '</div>' +
            '<div class="fw-bold ' + (isIngreso ? 'text-success' : 'text-danger') + '">$' + (c.dataset.monto || '0.00') + '</div>';
            
        containerDetalle.appendChild(item);
    });
    
    const inputFecha = document.getElementById('inputNuevaFechaMover');
    if (inputFecha && !inputFecha.value) {
        inputFecha.value = '<?= htmlspecialchars($fecha_consulta) ?>';
    }
    
    const modalEl = document.getElementById('modalMoverFecha');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function validarFormMoverFecha() {
    const inputFecha = document.getElementById('inputNuevaFechaMover');
    if (!inputFecha || !inputFecha.value) {
        alert('Por favor selecciona la fecha de destino.');
        return false;
    }
    const fechaActual = '<?= htmlspecialchars($fecha_consulta) ?>';
    if (inputFecha.value === fechaActual) {
        if (!confirm('La fecha seleccionada (' + inputFecha.value + ') es la misma que la fecha actual. ¿Deseas continuar?')) {
            return false;
        }
    }
    return true;
}
</script>
<?php endif; ?>

<!-- MODAL: ABRIR CAJA / INICIAR TURNO -->
<div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-labelledby="modalAbrirCajaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalAbrirCajaLabel">🟢 Apertura Formal de Caja / Turno</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/suramerica2030/caja/abrir" method="POST">
                <input type="hidden" name="fecha_apertura" value="<?= htmlspecialchars($fecha_consulta) ?>">
                <input type="hidden" name="sucursal_id" value="<?= htmlspecialchars($sucursal_id) ?>">
                <input type="hidden" name="redirect_to" value="index">
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <span style="font-size: 2.8rem;">💵</span>
                        <h6 class="fw-bold text-dark mt-1">Iniciar Operación de Caja</h6>
                        <p class="text-muted small mb-0">
                            Fecha: <strong><?= htmlspecialchars($fecha_consulta) ?></strong> | 
                            Sucursal: <strong><?= htmlspecialchars($sucursal_id === 'ALL' ? 'Central' : ($_SESSION['ciudad_sucursal'] ?? 'Mi Sucursal')) ?></strong>
                        </p>
                    </div>

                    <!-- ASISTENTE INTELIGENTE: SUGERENCIA DEL CIERRE ANTERIOR -->
                    <?php if(isset($sugerenciaApertura) && $sugerenciaApertura && !empty($sugerenciaApertura['existe_anterior'])): ?>
                        <div class="alert alert-info py-2 px-3 small mb-3 border-info shadow-sm" style="font-size: 11.5px;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-dark">💡 Cierre Anterior (<?= date('d/m/Y', strtotime($sugerenciaApertura['fecha_anterior'])) ?>):</strong>
                                <span class="badge bg-primary fs-6">$ <?= number_format($sugerenciaApertura['sugerido'], 2) ?> sugerido</span>
                            </div>
                            <div class="text-muted">
                                Efectivo en cierre: <strong>$ <?= number_format($sugerenciaApertura['efectivo_cierre'], 2) ?></strong>
                                <?php if($sugerenciaApertura['deposito_cierre'] > 0): ?>
                                    | Depositado a banco: <strong class="text-success">$ <?= number_format($sugerenciaApertura['deposito_cierre'], 2) ?></strong>
                                <?php endif; ?>
                                <?php if(!empty($sugerenciaApertura['comision_cierre']) && $sugerenciaApertura['comision_cierre'] > 0): ?>
                                    | Comisión cajero: <strong class="text-secondary">-$ <?= number_format($sugerenciaApertura['comision_cierre'], 2) ?></strong>
                                <?php endif; ?>
                                | Remanente en gaveta: <strong class="text-primary">$ <?= number_format($sugerenciaApertura['remanente'], 2) ?></strong>
                                <?php if(abs($sugerenciaApertura['diferencia_cierre']) > 0.009): ?>
                                    <br><span class="<?= $sugerenciaApertura['diferencia_cierre'] > 0 ? 'text-primary' : 'text-danger' ?> fw-bold">
                                        *(Cerró con <?= $sugerenciaApertura['diferencia_cierre'] > 0 ? 'sobrante de +$' : 'faltante de -$' ?><?= number_format(abs($sugerenciaApertura['diferencia_cierre']), 2) ?>)*
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">Fondo Inicial de Caja (Base en Efectivo) <span class="text-danger">*</span></label>
                            <?php if(isset($sugerenciaApertura) && !empty($sugerenciaApertura['existe_anterior'])): ?>
                                <small class="text-primary fw-bold" style="cursor: pointer;" onclick="establecerFondoInicialCaja(<?= number_format($sugerenciaApertura['sugerido'], 2, '.', '') ?>)">
                                    Usar sugerido
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text fw-bold bg-light">$</span>
                            <input type="number" step="0.01" min="0" name="valor_inicial" id="apertura_valor_inicial_caja" 
                                   class="form-control form-control-lg fw-bold text-success" 
                                   value="<?= number_format($sugerenciaApertura['sugerido'] ?? 0, 2, '.', '') ?>" required autofocus>
                        </div>

                        <!-- BOTONES DE PRESETEO RÁPIDO (1 CLIC) -->
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <?php if(isset($sugerenciaApertura) && !empty($sugerenciaApertura['existe_anterior'])): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCaja(<?= number_format($sugerenciaApertura['sugerido'], 2, '.', '') ?>)">
                                    🔄 Saldo Anterior ($<?= number_format($sugerenciaApertura['sugerido'], 2) ?>)
                                </button>
                                <?php if($sugerenciaApertura['base_anterior'] > 0 && abs($sugerenciaApertura['base_anterior'] - $sugerenciaApertura['sugerido']) > 0.01): ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCaja(<?= number_format($sugerenciaApertura['base_anterior'], 2, '.', '') ?>)">
                                        🪙 Base Anterior ($<?= number_format($sugerenciaApertura['base_anterior'], 2) ?>)
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCaja(50.00)">
                                🪙 Base $50.00
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCaja(20.00)">
                                🪙 Base $20.00
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCaja(0.00)">
                                0️⃣ Sin Fondo ($0)
                            </button>
                        </div>

                        <small class="text-muted" style="font-size: 11px;">
                            Ingresa el monto de dinero físico que tienes en gaveta para dar cambio / vuelto a los clientes.
                        </small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">Observaciones de Apertura (Opcional)</label>
                        <textarea name="observaciones_apertura" class="form-control" rows="2" placeholder="Ej: Recibido de supervisor en billetes de $5 y monedas para cambio..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-3">✔ Confirmar y Abrir Caja</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function establecerFondoInicialCaja(monto) {
    const inp = document.getElementById('apertura_valor_inicial_caja');
    if (inp) {
        inp.value = parseFloat(monto).toFixed(2);
        inp.focus();
        inp.select();
    }
}
</script>