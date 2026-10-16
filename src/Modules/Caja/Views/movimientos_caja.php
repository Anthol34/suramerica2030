<?php
$esAdmin = (isset($_SESSION['rol_id']) && intval($_SESSION['rol_id']) === 1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="text-primary text-uppercase fs-5">Opciones de Caja / Movimientos de Caja</h3>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
        <?php 
            if($_GET['status'] == 'success') echo '✅ Movimiento registrado con éxito.';
            elseif($_GET['status'] == 'success_egreso') echo '✅ Egreso registrado y enviado a aprobación con éxito.';
            elseif($_GET['status'] == 'success_mover') echo '📅 Se trasladaron exitosamente ' . intval($_GET['count'] ?? 1) . ' movimiento(s) a la fecha ' . htmlspecialchars($_GET['nueva_fecha'] ?? '') . '.';
            else echo '❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Ocurrió un error inesperado');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- BOTÓN DE ACCIÓN SUPERIOR -->
<div class="d-flex gap-2 mb-3 align-items-center">
    <button class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoEgreso">+ Nuevo</button>
    <?php if($esAdmin): ?>
        <button type="button" class="btn btn-outline-warning text-dark btn-sm fw-bold shadow-sm" id="btnMoverSeleccionados" onclick="abrirModalMoverFecha()" disabled title="Selecciona uno o más movimientos para reasignar su fecha">
            📅 Mover Fecha (<span id="countMover">0</span>)
        </button>
    <?php endif; ?>
</div>

<!-- FILTROS (Basado en el diseño tabular) -->
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body p-3">
        <form action="/suramerica2030/caja/movimientos" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Oficina</label>
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
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Cajero(a)</label>
                <select name="cajero" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php if(!empty($cajeros)): ?>
                        <?php foreach($cajeros as $c): ?>
                            <option value="<?= $c['ID_USUARIO'] ?>" <?= (isset($_GET['cajero']) && $_GET['cajero'] == $c['ID_USUARIO']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['NOMBRE']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold">Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 table-responsive">
    <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 11px;">
        <thead class="table-light text-center">
            <tr>
                <?php if($esAdmin): ?>
                    <th style="width: 35px;" class="text-center">
                        <input type="checkbox" id="checkAllMovimientos" class="form-check-input" onchange="seleccionarTodosMovimientos(this)" title="Seleccionar todos">
                    </th>
                <?php endif; ?>
                <th>Fecha de<br>Registro</th>
                <th>Código</th>
                <th>Oficina</th>
                <th>Cajero(a)</th>
                <th>Nombre</th>
                <th style="width: 25%;">Observación</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($movimientos)): ?>
                <tr><td colspan="<?= $esAdmin ? 10 : 9 ?>" class="text-center text-muted py-3">No hay movimientos registrados.</td></tr>
            <?php else: ?>
                <?php foreach($movimientos as $mov): ?>
                    <tr>
                        <?php if($esAdmin): ?>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input mov-checkbox" value="<?= $mov['ID_MOVIMIENTO'] ?>" data-concepto="<?= htmlspecialchars($mov['CONCEPTO']) ?>" data-monto="<?= number_format($mov['MONTO'], 2) ?>" data-tipo="<?= $mov['TIPO_MOVIMIENTO'] ?? 'EGRESO' ?>" data-factura="" data-fecha="<?= date('Y-m-d', strtotime($mov['FECHA_HORA'])) ?>" onchange="actualizarContadorMovimientos()">
                            </td>
                        <?php endif; ?>
                        <td class="text-center"><?= date('Y-m-d', strtotime($mov['FECHA_HORA'])) ?></td>
                        <td class="text-primary fw-bold"><?= htmlspecialchars($mov['CODIGO_MOVIMIENTO']) ?></td>
                        <td><?= htmlspecialchars($mov['SUCURSAL_NOMBRE']) ?></td>
                        <td><?= htmlspecialchars($mov['CAJERO']) ?></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($mov['TIPO_EGRESO']) ?></td>
                        <td class="text-wrap" style="max-width: 250px;"><?= htmlspecialchars($mov['CONCEPTO']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-danger">Egreso</span>
                        </td>
                        <td class="text-end fw-bold text-danger"><?= number_format($mov['MONTO'], 2) ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <?php if($esAdmin): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1 border-0" onclick="moverMovimientoIndividual(<?= $mov['ID_MOVIMIENTO'] ?>, '<?= htmlspecialchars(addslashes($mov['CONCEPTO'])) ?>', '<?= number_format($mov['MONTO'], 2) ?>', '<?= $mov['TIPO_MOVIMIENTO'] ?? 'EGRESO' ?>', '', '<?= date('Y-m-d', strtotime($mov['FECHA_HORA'])) ?>')" title="Mover a otra fecha" style="font-size:11px;">📅</button>
                                <?php endif; ?>
                                <a href="#" class="btn btn-sm btn-outline-info py-0 px-1" title="Ver" style="font-size:10px;">👁️ Ver</a>
                                <a href="/suramerica2030/caja/imprimir_movimiento?id=<?= $mov['ID_MOVIMIENTO'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Imprimir" style="font-size:10px;">🖨️ Imprimir</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Paginador -->
    <div class="card-footer bg-white border-0 d-flex justify-content-center pt-3 pb-2">
        <ul class="pagination pagination-sm mb-0 shadow-sm">
            <li class="page-item disabled"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
        </ul>
    </div>
</div>

<!-- MODAL NUEVO EGRESO (Formulario base) -->
<div class="modal fade" id="modalNuevoEgreso" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title">Registrar Nuevo Egreso</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/suramerica2030/caja/guardar_egreso" method="POST">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Nombre (Tipo de Egreso)</label>
                        <select name="tipo_egreso" class="form-select" required>
                            <option value="Anticipos a Socios">Anticipos a Socios</option>
                            <option value="Anticipo por Comisiones">Anticipo por Comisiones</option>
                            <option value="Gastos de Oficina">Gastos de Oficina</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Observación</label>
                        <textarea name="concepto" class="form-control" rows="3" required placeholder="Ej: liquidacion de pax cucuta-ecuador..."></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Valor ($)</label>
                        <input type="number" step="0.01" name="monto" class="form-control fw-bold text-danger" required>
                    </div>
                </div>
                <div class="modal-footer p-1">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Generar Egreso y Enviar a Aprobación</button>
                </div>
            </form>
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
                    <span>📅</span> Reasignar Fecha de Movimientos
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/suramerica2030/caja/moverfecha" method="POST" id="formMoverFecha" onsubmit="return validarFormMoverFecha()">
                <input type="hidden" name="redirect_to" value="movimientos">
                <div id="hiddenMovimientosContainer"></div>

                <div class="modal-body p-3">
                    <div class="alert alert-info border-info d-flex align-items-start gap-2 py-2 mb-3" style="font-size: 12px;">
                        <span class="fs-5">ℹ️</span>
                        <div>
                            <strong>Control de Seguridad Contable:</strong>
                            El cambio de fecha se procesará <strong>únicamente si las cajas</strong> (origen y destino) están <strong>ABIERTAS</strong>. Si la caja destino o de origen ya fue cerrada, el sistema bloqueará la operación para proteger la auditoría.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary mb-1" style="font-size: 12px;">
                            Movimientos seleccionados (<span id="modalNumSeleccionados" class="text-primary fw-bold">0</span>):
                        </label>
                        <div id="modalDetalleSeleccionados" class="border rounded p-2 bg-light" style="max-height: 180px; overflow-y: auto;"></div>
                    </div>

                    <div class="mb-2">
                        <label for="inputNuevaFechaMover" class="form-label fw-bold text-dark">
                            📅 Nueva Fecha para los Movimientos:
                        </label>
                        <input type="date" name="nueva_fecha" id="inputNuevaFechaMover" class="form-control form-control-lg fw-bold text-primary" required>
                        <small class="text-muted" style="font-size: 11px;">
                            Los movimientos se moverán a la nueva fecha en su respectiva sucursal conservando su hora de registro.
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
        alert('Por favor selecciona al menos un movimiento para cambiar de fecha.');
        return;
    }
    poblarModalMoverFecha(checks);
}

function moverMovimientoIndividual(id, concepto, monto, tipo, factura, fecha) {
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
    
    let defaultFecha = '';
    checks.forEach(c => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'movimientos_ids[]';
        hiddenInput.value = c.value;
        containerHidden.appendChild(hiddenInput);
        
        if (!defaultFecha && c.dataset.fecha) {
            defaultFecha = c.dataset.fecha;
        }
        
        const item = document.createElement('div');
        item.className = 'd-flex justify-content-between align-items-center py-1 border-bottom';
        item.style.fontSize = '12px';
        
        item.innerHTML = '<div>' +
            '<span class="badge bg-danger" style="font-size:10px;">- Egreso</span> ' +
            '<strong>' + (c.dataset.concepto || 'Movimiento') + '</strong>' +
            '</div>' +
            '<div class="fw-bold text-danger">$' + (c.dataset.monto || '0.00') + '</div>';
            
        containerDetalle.appendChild(item);
    });
    
    const inputFecha = document.getElementById('inputNuevaFechaMover');
    if (inputFecha) {
        inputFecha.value = defaultFecha || '<?= date('Y-m-d') ?>';
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
    return true;
}
</script>
<?php endif; ?>