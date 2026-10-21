<?php
// Determinar qué pestaña mostrar activa por defecto
$tabHistorialActiva = isset($_GET['buscar_historial']) || isset($_GET['fecha_inicio']) || isset($_GET['fecha_fin']) || isset($_GET['estado_deposito']);
$esAdmin = $esAdmin ?? (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h3 class="text-primary text-uppercase fs-5 fw-bold mb-0">📠 Cuadre de Caja / Cierres de Caja</h3>
        <p class="text-muted small mb-0">Gestión de arqueo detallado, auditoría de transacciones y cierre formal de turno.</p>
    </div>
    <div>
        <?php if($cierreDia): ?>
            <span class="badge bg-secondary fs-6 px-3 py-2 shadow-sm">🔒 Caja <?= htmlspecialchars($fecha_consulta) ?>: CERRADA</span>
        <?php elseif(isset($aperturaDia) && $aperturaDia): ?>
            <span class="badge bg-success fs-6 px-3 py-2 shadow-sm">🟢 Caja <?= htmlspecialchars($fecha_consulta) ?>: EN OPERACIÓN (ABIERTA)</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm">🟡 Caja <?= htmlspecialchars($fecha_consulta) ?>: PENDIENTE DE APERTURA</span>
        <?php endif; ?>
    </div>
</div>

<!-- ALERTAS DE ESTADO -->
<?php if(isset($_GET['status'])): ?>
    <?php if($_GET['status'] === 'success_apertura'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
            <span class="fs-4">🟢</span>
            <div>
                <strong>¡Caja abierta exitosamente!</strong> El turno de caja se ha iniciado formalmente con su fondo inicial registrado.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif($_GET['status'] === 'success_cierre'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex flex-wrap justify-content-between align-items-center gap-2" role="alert">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-4">🔒</span>
                <div>
                    <strong>¡Caja cerrada exitosamente!</strong> El corte y arqueo de caja se han registrado de forma oficial para esta fecha.
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if($cierreDia): ?>
                    <a href="/suramerica2030/caja/ticket_cierre?id=<?= $cierreDia['ID_CIERRE'] ?>" target="_blank" class="btn btn-dark btn-sm fw-bold shadow-sm">
                        🖨️ Imprimir Ticket Cierre (80mm)
                    </a>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif($_GET['status'] === 'success_ajuste'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            ✏️ <strong>Depósito ajustado correctamente.</strong> El valor depositado y los saldos pendientes han sido recalculados.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif($_GET['status'] === 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
            <span class="fs-4">⚠️</span>
            <div>
                <strong>Atención:</strong> <?= htmlspecialchars($_GET['msg'] ?? 'Ocurrió un error al procesar la operación.') ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- NAVEGACIÓN POR PESTAÑAS -->
<ul class="nav nav-tabs mb-4 fw-bold" id="cierreTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= !$tabHistorialActiva ? 'active text-primary' : 'text-secondary' ?>" id="tab-cierre-btn" data-bs-toggle="tab" data-bs-target="#tab-cierre" type="button" role="tab">
            🔒 Realizar Cierre de Caja & Arqueo
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tabHistorialActiva ? 'active text-primary' : 'text-secondary' ?>" id="tab-historial-btn" data-bs-toggle="tab" data-bs-target="#tab-historial" type="button" role="tab">
            📚 Historial de Cierres Registrados (<?= count($historial_cierres) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="cierreTabsContent">

    <!-- ========================================================================= -->
    <!-- PESTAÑA 1: REALIZAR CIERRE DE CAJA & ARQUEO DETALLADO                     -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade <?= !$tabHistorialActiva ? 'show active' : '' ?>" id="tab-cierre" role="tabpanel">

        <!-- BARRA SUPERIOR DE CONTROL DE FECHA Y SUCURSAL -->
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body p-3">
                <form action="/suramerica2030/caja/historial_cierres" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small mb-1">📅 Fecha de Arqueo / Cierre:</label>
                        <input type="date" name="fecha" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($fecha_consulta) ?>" required>
                    </div>

                    <?php if($esAdmin): ?>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary small mb-1">🏢 Sucursal / Agencia:</label>
                            <select name="sucursal" class="form-select form-select-sm fw-bold">
                                <?php if(!empty($sucursales)): ?>
                                    <?php foreach($sucursales as $s): ?>
                                        <option value="<?= $s['ID_SUCURSAL'] ?>" <?= ($sucursal_id == $s['ID_SUCURSAL']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['CIUDAD']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary small mb-1">🏢 Sucursal / Agencia:</label>
                            <input type="text" class="form-control form-control-sm bg-white fw-bold" value="<?= htmlspecialchars($nombreSucursalActual) ?>" readonly>
                            <input type="hidden" name="sucursal" value="<?= htmlspecialchars($sucursal_id) ?>">
                        </div>
                    <?php endif; ?>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                            🔄 Consultar Fecha / Actualizar Arqueo
                        </button>
                    </div>

                    <div class="col-md-2 text-end">
                        <a href="/suramerica2030/caja/imprimir?fecha=<?= $fecha_consulta ?>&sucursal=<?= $sucursal_id ?>" target="_blank" class="btn btn-outline-dark btn-sm w-100 fw-bold">
                            🖨️ Hoja de Arqueo
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if($cierreDia): ?>
            <!-- ========================================================================= -->
            <!-- CASO A: LA CAJA YA FUE CERRADA PARA ESTA FECHA                            -->
            <!-- ========================================================================= -->
            <div class="card shadow-sm border-0 border-start border-secondary border-5 mb-4 bg-light">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <span class="badge bg-secondary fs-6 mb-1">🔒 CAJA CERRADA OFICIALMENTE</span>
                            <h4 class="fw-bold text-dark mb-0">Cierre #<?= htmlspecialchars($cierreDia['CODIGO_CIERRE'] ?? $cierreDia['ID_CIERRE']) ?></h4>
                            <p class="text-muted small mb-0">
                                Ejecutado el <strong><?= htmlspecialchars($cierreDia['FECHA_HORA']) ?></strong> por <strong><?= htmlspecialchars($cierreDia['CAJERO'] ?? 'Cajero') ?></strong> en la sucursal <strong><?= htmlspecialchars($nombreSucursalActual) ?></strong>.
                            </p>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-md-0">
                            <a href="/suramerica2030/caja/ticket_cierre?id=<?= $cierreDia['ID_CIERRE'] ?>" target="_blank" class="btn btn-outline-dark fw-bold btn-sm shadow-sm" title="Imprimir ticket en impresora térmica de 80mm">
                                🖨️ Ticket Cierre (80mm)
                            </a>
                            <a href="/suramerica2030/caja/imprimir?fecha=<?= $fecha_consulta ?>&sucursal=<?= $sucursal_id ?>" target="_blank" class="btn btn-dark fw-bold btn-sm shadow-sm">
                                📄 Imprimir Cierre Auditado (PDF)
                            </a>
                            <a href="/suramerica2030/caja/depositos?sucursal=<?= $sucursal_id ?>" class="btn btn-primary fw-bold btn-sm shadow-sm">
                                🏦 Gestionar Depósitos
                            </a>
                        </div>
                    </div>

                    <div class="row g-2 text-center">
                        <div class="col-md-2 col-6">
                            <div class="p-2 bg-white rounded border">
                                <span class="text-muted small fw-bold">Fondo Inicial Base:</span>
                                <h5 class="fw-bold text-dark mb-0">$ <?= number_format($cierreDia['VALOR_INICIAL'] ?? 0, 2) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-2 bg-white rounded border">
                                <span class="text-muted small fw-bold">Efectivo Arqueado:</span>
                                <h5 class="fw-bold text-dark mb-0">$ <?= number_format($cierreDia['EFECTIVO_FISICO'] ?? $cierreDia['SALDO_FINAL'], 2) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <?php $dif = floatval($cierreDia['DIFERENCIA_ARQUEO'] ?? 0); ?>
                            <div class="p-2 bg-white rounded border <?= abs($dif) < 0.01 ? 'border-success' : ($dif < 0 ? 'border-danger' : 'border-primary') ?>">
                                <span class="text-muted small fw-bold">Diferencia Arqueo:</span>
                                <h5 class="fw-bold <?= abs($dif) < 0.01 ? 'text-success' : ($dif < 0 ? 'text-danger' : 'text-primary') ?> mb-0">
                                    <?= abs($dif) < 0.01 ? '$0.00' : ($dif < 0 ? '-$'.number_format(abs($dif), 2) : '+$'.number_format($dif, 2)) ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-2 bg-white rounded border border-primary">
                                <span class="text-primary small fw-bold">Exigible a Depositar:</span>
                                <h5 class="fw-bold text-primary mb-0">$ <?= number_format($cierreDia['VALOR_A_DEPOSITAR'] ?? ($cierreDia['SALDO_FINAL'] - $cierreDia['COMISION_EMPLEADOS']), 2) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-2 col-12">
                            <?php $vPend = floatval($cierreDia['V_POR_DEPOSITAR'] ?? 0); ?>
                            <div class="p-2 bg-white rounded border <?= $vPend > 0 ? 'border-danger' : 'border-success' ?>">
                                <span class="<?= $vPend > 0 ? 'text-danger' : 'text-success' ?> small fw-bold">Por Depositar:</span>
                                <h5 class="fw-bold <?= $vPend > 0 ? 'text-danger' : 'text-success' ?> mb-0">$ <?= number_format($vPend, 2) ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ========================================================================= -->
            <!-- CASO B: LA CAJA ESTÁ ABIERTA O PENDIENTE DE APERTURA                      -->
            <!-- ========================================================================= -->

            <?php if(isset($aperturaDia) && $aperturaDia): ?>
                <div class="alert alert-success border-success shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success fs-6">🟢 CAJA EN OPERACIÓN (ABIERTA)</span>
                            <span class="text-muted small">Turno iniciado: <strong><?= date('d/m/Y H:i', strtotime($aperturaDia['APERTURA_FECHA_HORA'])) ?></strong> por <strong><?= htmlspecialchars($aperturaDia['CAJERO_NOMBRE'] ?? 'Cajero') ?></strong></span>
                        </div>
                        <div class="small mt-1 text-dark">
                            Fondo Inicial (Base en Gaveta): <strong class="text-success">$<?= number_format($aperturaDia['VALOR_INICIAL'], 2) ?></strong>
                            <?php if(!empty($aperturaDia['OBSERVACIONES_APERTURA'])): ?>
                                | <span class="text-muted"><em>"<?= htmlspecialchars($aperturaDia['OBSERVACIONES_APERTURA']) ?>"</em></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-white text-dark border">Sesión #<?= htmlspecialchars($aperturaDia['CODIGO_CIERRE'] ?? $aperturaDia['ID_CIERRE']) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning border-warning shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark fs-6">🟡 CAJA PENDIENTE DE APERTURA</span>
                            <span class="text-muted small">Fecha: <strong><?= htmlspecialchars($fecha_consulta) ?></strong></span>
                        </div>
                        <p class="small text-dark mb-0 mt-1">
                            El turno de caja de esta fecha aún no ha sido abierto formalmente. Registra el <strong>Fondo Inicial (Base)</strong> para habilitar el control formal.
                        </p>
                    </div>
                    <div>
                        <?php if($sucursal_id !== 'ALL'): ?>
                            <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAbrirCajaCierres">
                                🟢 Abrir Caja / Iniciar Turno
                            </button>
                        <?php else: ?>
                            <small class="text-muted fst-italic">Selecciona una sucursal específica para abrir caja</small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 1. TARJETAS DE RESUMEN EJECUTIVO (KPIS) -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-white shadow-sm border-0 h-100" style="background: #28a745;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-uppercase">💵 Efectivo en Gaveta</span>
                                <span class="badge bg-white text-success fw-bold">Caja Física</span>
                            </div>
                            <h3 class="fw-bold my-1">$ <?= number_format($totales['efectivo_con_fondo'], 2) ?></h3>
                            <small style="font-size: 10px; opacity: 0.9;">
                                Base/Fondo: $<?= number_format($totales['valor_inicial'], 2) ?> | Ventas: $<?= number_format($totales['efectivo'], 2) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white shadow-sm border-0 h-100 bg-info">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-uppercase">🏦 Transferencias / Banco</span>
                                <span class="badge bg-white text-info fw-bold">Cta Bancaria</span>
                            </div>
                            <h3 class="fw-bold my-1">$ <?= number_format($totales['transferencia'], 2) ?></h3>
                            <small style="font-size: 10px; opacity: 0.9;">Ingresó directo a banco (no está en gaveta).</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white shadow-sm border-0 h-100" style="background: #d9534f;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-uppercase">📤 Egresos y Gastos</span>
                                <span class="badge bg-white text-danger fw-bold">Salidas</span>
                            </div>
                            <h3 class="fw-bold my-1">-$ <?= number_format($totales['egresos'], 2) ?></h3>
                            <small style="font-size: 10px; opacity: 0.9;">Gastos autorizados y devoluciones pagadas.</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white shadow-sm border-0 h-100" style="background: #0b2b56;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-uppercase">🏦 Exigible a Depositar</span>
                                <span class="badge bg-warning text-dark fw-bold">Neto Banco</span>
                            </div>
                            <h3 class="fw-bold my-1">$ <?= number_format($totales['valor_a_depositar'], 2) ?></h3>
                            <small style="font-size: 10px; opacity: 0.9;">Efectivo en gaveta menos comisión retenida.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. RESUMEN DETALLADO POR SERVICIO (TABLA TRANSPARENTE) -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-primary mb-0">📋 Resumen Detallado por Servicio y Rubro</h5>
                        <small class="text-muted">Desglose exacto de lo recaudado y egresado para que el cajero verifique cada centavo antes del cierre.</small>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark border">Sucursal: <?= htmlspecialchars($nombreSucursalActual) ?></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 12px;">
                        <thead class="table-light text-center">
                            <tr>
                                <th class="text-start" style="width: 32%;">Concepto / Servicio</th>
                                <th style="width: 15%;">Operaciones / Cant.</th>
                                <th class="text-end" style="width: 17%;">💵 En Efectivo</th>
                                <th class="text-end" style="width: 18%;">🏦 En Transferencia / POS</th>
                                <th class="text-end" style="width: 18%;">Total Recaudado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- INGRESOS POR SERVICIO -->
                            <tr class="table-light fw-bold">
                                <td colspan="5" class="text-uppercase text-success" style="font-size: 11px; letter-spacing: 0.5px;">
                                    ➕ INGRESOS Y VENTAS DEL TURNO
                                </td>
                            </tr>
                            <?php 
                                $ingresosServicios = $desgloseServicios['ingresos'];
                                $totCantIngresos = 0;
                                foreach($ingresosServicios as $key => $serv): 
                                    $totCantIngresos += $serv['cantidad'];
                            ?>
                                <tr>
                                    <td>
                                        <span class="me-1"><?= $serv['icono'] ?></span>
                                        <strong class="text-dark"><?= htmlspecialchars($serv['nombre']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if($serv['cantidad'] > 0): ?>
                                            <span class="badge bg-secondary"><?= $serv['cantidad'] ?> op.</span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $serv['efectivo'] > 0 ? 'text-success' : 'text-muted' ?>">
                                        $ <?= number_format($serv['efectivo'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $serv['transferencia'] > 0 ? 'text-info' : 'text-muted' ?>">
                                        $ <?= number_format($serv['transferencia'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-dark bg-light">
                                        $ <?= number_format($serv['total'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- SUB-TOTAL INGRESOS -->
                            <tr class="fw-bold" style="background-color: #f1f8e9;">
                                <td class="text-success">SUBTOTAL INGRESOS BRUTOS:</td>
                                <td class="text-center text-success"><?= $totCantIngresos ?> transacciones</td>
                                <td class="text-end text-success">$ <?= number_format($totales['efectivo'] + ($desgloseServicios['egresos']['gastos']['efectivo'] + $desgloseServicios['egresos']['devoluciones']['efectivo']), 2) ?></td>
                                <td class="text-end text-info">$ <?= number_format($totales['transferencia'], 2) ?></td>
                                <td class="text-end text-success fs-6">$ <?= number_format($totales['ingresos'], 2) ?></td>
                            </tr>

                            <!-- EGRESOS -->
                            <tr class="table-light fw-bold">
                                <td colspan="5" class="text-uppercase text-danger" style="font-size: 11px; letter-spacing: 0.5px;">
                                    ➖ EGRESOS Y SALIDAS DE CAJA
                                </td>
                            </tr>
                            <?php 
                                $egresosServicios = $desgloseServicios['egresos'];
                                $totCantEgresos = 0;
                                foreach($egresosServicios as $key => $serv): 
                                    $totCantEgresos += $serv['cantidad'];
                            ?>
                                <tr>
                                    <td>
                                        <span class="me-1"><?= $serv['icono'] ?></span>
                                        <strong class="text-dark"><?= htmlspecialchars($serv['nombre']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if($serv['cantidad'] > 0): ?>
                                            <span class="badge bg-danger"><?= $serv['cantidad'] ?> op.</span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $serv['efectivo'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                        -$ <?= number_format($serv['efectivo'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $serv['transferencia'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                        -$ <?= number_format($serv['transferencia'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-danger bg-light">
                                        -$ <?= number_format($serv['total'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- SUB-TOTAL EGRESOS -->
                            <tr class="fw-bold" style="background-color: #ffebee;">
                                <td class="text-danger">SUBTOTAL EGRESOS / SALIDAS:</td>
                                <td class="text-center text-danger"><?= $totCantEgresos ?> egresos</td>
                                <td class="text-end text-danger">-$ <?= number_format($desgloseServicios['egresos']['gastos']['efectivo'] + $desgloseServicios['egresos']['devoluciones']['efectivo'], 2) ?></td>
                                <td class="text-end text-danger">-$ <?= number_format($desgloseServicios['egresos']['gastos']['transferencia'] + $desgloseServicios['egresos']['devoluciones']['transferencia'], 2) ?></td>
                                <td class="text-end text-danger fs-6">-$ <?= number_format($totales['egresos'], 2) ?></td>
                            </tr>

                            <!-- TOTAL NETO FINAL -->
                            <tr class="table-dark fw-bold fs-6">
                                <td>SALDO NETO FINAL DEL TURNO:</td>
                                <td class="text-center"><?= count($movimientos) ?> mov.</td>
                                <td class="text-end text-warning">$ <?= number_format($totales['efectivo'], 2) ?></td>
                                <td class="text-end text-info">$ <?= number_format($totales['transferencia'], 2) ?></td>
                                <td class="text-end text-white">$ <?= number_format($totales['saldo_neto'], 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. CUADRE FÍSICO DE GAVETA & CANDADOS DE SEGURIDAD -->
            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-2 fw-bold text-dark d-flex align-items-center gap-2">
                            <span>🧮</span> Arqueo Contable de Efectivo Físico (Lo que debe haber en mano)
                        </div>
                        <div class="card-body p-3">
                            <div class="p-3 bg-light rounded border mb-3" style="font-size: 13px; font-family: monospace;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>(+) Ingresos recaudados en Efectivo:</span>
                                    <strong class="text-success">$ <?= number_format($totales['efectivo'] + ($desgloseServicios['egresos']['gastos']['efectivo'] + $desgloseServicios['egresos']['devoluciones']['efectivo']), 2) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>(-) Egresos y gastos pagados en Efectivo:</span>
                                    <strong class="text-danger">-$ <?= number_format($desgloseServicios['egresos']['gastos']['efectivo'] + $desgloseServicios['egresos']['devoluciones']['efectivo'], 2) ?></strong>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between mb-1 fs-6">
                                    <strong class="text-dark">(=) EFECTIVO FÍSICO ARQUEADO EN GAVETA:</strong>
                                    <strong class="text-dark bg-white px-2 py-1 rounded border">$ <?= number_format($totales['efectivo'], 2) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1 text-muted">
                                    <span>(-) Comisión retenida por oficina/vendedora:</span>
                                    <strong class="text-danger">-$ <?= number_format($totales['comisiones'], 2) ?></strong>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fs-5 text-primary">
                                    <strong>(=) VALOR EXIGIBLE A DEPOSITAR AL BANCO:</strong>
                                    <strong>$ <?= number_format($totales['valor_a_depositar'], 2) ?></strong>
                                </div>
                            </div>
                            <small class="text-muted d-block">
                                💡 <em>Nota para el cajero:</em> El dinero por transferencias bancarias no se cuenta en el arqueo físico porque ingresó directo a las cuentas bancarias de la empresa. La comisión retenida se queda en oficina y sólo depositas el valor exigible neto.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-2 fw-bold text-dark d-flex align-items-center gap-2">
                            <span>🛡️</span> Validación y Confirmación de Cierre
                        </div>
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <!-- CANDADO DE EGRESOS PENDIENTES -->
                                <?php if($egresos_pendientes_count > 0): ?>
                                    <div class="alert alert-danger p-3 mb-3" style="font-size: 12px;">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fs-4">🛑</span>
                                            <strong class="fs-6">CIERRE BLOQUEADO</strong>
                                        </div>
                                        <p class="mb-2">
                                            Existen <strong><?= $egresos_pendientes_count ?></strong> egreso(s) pendiente(s) de aprobación por supervisión para esta fecha.
                                        </p>
                                        <p class="small text-muted mb-3">
                                            En cumplimiento del manual de control interno, todo gasto o salida de dinero debe ser <strong>Aprobado</strong> o <strong>Rechazado</strong> antes de efectuar el corte final.
                                        </p>
                                        <a href="/suramerica2030/caja/aprobaciones?subtab=egresos&sucursal=<?= $sucursal_id ?>" class="btn btn-danger btn-sm w-100 fw-bold shadow-sm">
                                            🔍 Resolver Egresos Pendientes
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success p-3 mb-3" style="font-size: 12px;">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fs-4">✅</span>
                                            <strong class="fs-6">Auditoría Superada</strong>
                                        </div>
                                        <p class="mb-0">
                                            No existen egresos pendientes de aprobación para esta fecha. Los números están listos para generar el corte definitivo.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <?php if(empty($movimientos)): ?>
                                    <div class="alert alert-warning text-center small p-2 mb-0">
                                        No hay movimientos registrados para esta fecha y sucursal.
                                    </div>
                                <?php elseif($egresos_pendientes_count > 0): ?>
                                    <button class="btn btn-secondary w-100 fw-bold py-2 shadow-sm" disabled>
                                        🛑 CIERRE BLOQUEADO (Egresos pendientes)
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-danger w-100 fw-bold py-3 fs-6 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConfirmarCierre">
                                        🔒 CONFIRMAR Y REALIZAR CIERRE DE CAJA
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. ACORDEÓN DE AUDITORÍA: LISTADO TRANSACCIÓN POR TRANSACCIÓN -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <button class="btn btn-outline-primary btn-sm w-100 fw-bold d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMovimientos">
                        <span>🔍 Auditar Detalle de los <?= count($movimientos) ?> Movimientos del Turno</span>
                        <span>▼ Desplegar Transacciones</span>
                    </button>
                </div>
                <div class="collapse" id="collapseMovimientos">
                    <div class="card-body p-0 border-top table-responsive">
                        <table class="table table-hover table-striped table-bordered align-middle mb-0" style="font-size: 11px;">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Hora</th>
                                    <th>Tipo</th>
                                    <th>Servicio</th>
                                    <th>Comprobante</th>
                                    <th>Cajero(a)</th>
                                    <th class="text-start">Concepto / Detalle</th>
                                    <th>Método</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($movimientos)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-3">No hay movimientos registrados.</td></tr>
                                <?php else: ?>
                                    <?php foreach($movimientos as $m): 
                                        $isIngreso = ($m['TIPO_MOVIMIENTO'] === 'INGRESO');
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= date('H:i', strtotime($m['FECHA_HORA'])) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $isIngreso ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $m['TIPO_MOVIMIENTO'] ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($m['ORIGEN_FACTURA'] ?? 'MANUAL') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if(!empty($m['ID_FACTURA'])): ?>
                                                    <a href="/suramerica2030/ventas/ticket/<?= $m['ID_FACTURA'] ?>" target="_blank" class="fw-bold text-decoration-none">
                                                        Fact #<?= str_pad($m['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= htmlspecialchars($m['CAJERO']) ?></td>
                                            <td><?= htmlspecialchars($m['CONCEPTO']) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $m['METODO_PAGO'] === 'EFECTIVO' ? 'bg-success' : 'bg-info text-dark' ?>">
                                                    <?= $m['METODO_PAGO'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold <?= $isIngreso ? 'text-success' : 'text-danger' ?>">
                                                <?= $isIngreso ? '+' : '-' ?>$ <?= number_format($m['MONTO'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL DE CONFIRMACIÓN DE CIERRE DE CAJA Y ARQUEO FÍSICO                   -->
            <!-- ========================================================================= -->
            <div class="modal fade" id="modalConfirmarCierre" tabindex="-1" aria-labelledby="modalConfirmarCierreLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-danger text-white py-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-4">🔒</span>
                                <div>
                                    <h5 class="modal-title fw-bold mb-0" id="modalConfirmarCierreLabel">Cierre de Caja y Arqueo Físico</h5>
                                    <small class="opacity-75">Corte Z | Fecha: <?= htmlspecialchars($fecha_consulta) ?> - Sucursal: <?= htmlspecialchars($nombreSucursalActual) ?></small>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <form action="/suramerica2030/caja/cerrar" method="POST" id="formCierreCaja" data-live="true">
                            <div class="modal-body p-4">
                                <input type="hidden" name="fecha_cierre" value="<?= htmlspecialchars($fecha_consulta) ?>">
                                <input type="hidden" name="sucursal_id" value="<?= htmlspecialchars($sucursal_id) ?>">
                                <input type="hidden" name="redirect_to" value="historial_cierres">
                                <input type="hidden" name="total_ingresos" value="<?= $totales['ingresos'] ?>">
                                <input type="hidden" name="total_egresos" value="<?= $totales['egresos'] ?>">
                                <input type="hidden" name="saldo_final" value="<?= $totales['saldo_neto'] ?>">
                                <input type="hidden" name="capital_reposicion" value="<?= $totales['capital_reposicion'] ?>">
                                <input type="hidden" name="comisiones" value="<?= $totales['comisiones'] ?>">
                                <input type="hidden" name="utilidad_final" value="<?= $totales['utilidad_neta'] ?>">
                                <input type="hidden" name="valor_a_depositar" value="<?= $totales['valor_a_depositar'] ?>">
                                <input type="hidden" name="diferencia_arqueo" id="input_diferencia_arqueo" value="0.00">

                                <!-- COMPARACIÓN TEÓRICA VS DECLARADA -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="card h-100 border-0 bg-light p-3">
                                            <h6 class="fw-bold text-secondary mb-2" style="font-size: 12px;">📊 BALANCE TEÓRICO EN SISTEMA</h6>
                                            <div class="d-flex justify-content-between mb-1 small">
                                                <span>Fondo Inicial (Base):</span>
                                                <strong class="text-dark">$ <?= number_format($totales['valor_inicial'], 2) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1 small">
                                                <span>(+) Ventas Efectivo:</span>
                                                <strong class="text-success">+$ <?= number_format($totales['efectivo'], 2) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1 small">
                                                <span>(-) Egresos / Gastos:</span>
                                                <strong class="text-danger">-$ <?= number_format($totales['egresos'], 2) ?></strong>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-dark small">(=) Esperado en Gaveta:</strong>
                                                <h5 class="fw-bold text-primary mb-0" id="monto_esperado_display" data-esperado="<?= $totales['efectivo_con_fondo'] ?>">
                                                    $ <?= number_format($totales['efectivo_con_fondo'], 2) ?>
                                                </h5>
                                            </div>
                                            <div class="mt-2 pt-2 border-top text-muted" style="font-size: 11px;">
                                                <span>🏦 Transferencias (en Banco): <strong>$ <?= number_format($totales['transferencia'], 2) ?></strong></span><br>
                                                <span>(-) Comisión retenida: <strong class="text-danger">-$ <?= number_format($totales['comisiones'], 2) ?></strong></span><br>
                                                <span>(=) A Depositar Banco: <strong class="text-primary">$ <?= number_format($totales['valor_a_depositar'], 2) ?></strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card h-100 border-primary p-3">
                                            <h6 class="fw-bold text-primary mb-2" style="font-size: 12px;">💵 ARQUEO FÍSICO DECLARADO</h6>
                                            <label class="form-label small fw-bold mb-1">Efectivo Físico Contado en Gaveta <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-lg mb-2">
                                                <span class="input-group-text bg-primary text-white fw-bold">$</span>
                                                <input type="number" step="0.01" min="0" name="efectivo_fisico" id="cierre_efectivo_fisico" 
                                                       class="form-control fw-bold text-center border-primary fs-4" 
                                                       value="<?= number_format($totales['efectivo_con_fondo'], 2, '.', '') ?>" 
                                                       required oninput="recalcularDiferenciaArqueo()">
                                            </div>

                                            <!-- ALERTA DINÁMICA DE DIFERENCIA DE ARQUEO -->
                                            <div id="alerta_diferencia_arqueo" class="alert alert-success py-2 px-3 mb-2 text-center small fw-bold">
                                                ✅ CAJA CUADRADA EXACTA ($ 0.00)
                                            </div>

                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 fw-bold" data-bs-toggle="collapse" data-bs-target="#collapseCalculadoraEfectivo">
                                                🧮 Abrir Calculadora de Billetes y Monedas
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- CALCULADORA DE BILLETES Y MONEDAS (COLAPSABLE) -->
                                <div class="collapse mb-3" id="collapseCalculadoraEfectivo">
                                    <div class="card card-body bg-light border p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-bold mb-0 text-dark small">🧮 Conteo por Denominación (Billetes y Monedas)</h6>
                                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" onclick="limpiarCalculadora()">Limpiar conteo</button>
                                        </div>
                                        <div class="row g-2" style="font-size: 12px;">
                                            <div class="col-md-6 border-end pe-md-3">
                                                <strong class="text-muted d-block mb-1" style="font-size: 11px;">💵 BILLETES</strong>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 100 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="100" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 50 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="50" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 20 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="20" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 10 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="10" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 5 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="5" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 1 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="1" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                            </div>
                                            <div class="col-md-6 ps-md-3">
                                                <strong class="text-muted d-block mb-1" style="font-size: 11px;">🪙 MONEDAS</strong>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 1.00 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="1.00" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 0.50 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="0.50" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 0.25 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="0.25" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 0.10 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="0.10" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 0.05 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="0.05" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text w-50 justify-content-between">$ 0.01 x</span>
                                                    <input type="number" min="0" class="form-control text-center calc-denom" data-val="0.01" placeholder="0" oninput="calcularDenominaciones()">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                            <span class="small fw-bold">Suma Calculadora:</span>
                                            <span class="fw-bold text-success fs-6" id="total_calculadora_display">$ 0.00</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- OBSERVACIONES DE CIERRE -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold mb-1">Observaciones / Novedades de Cierre (Opcional)</label>
                                    <textarea name="observaciones_cierre" class="form-control form-control-sm" rows="2" placeholder="Notas sobre el arqueo físico, justificación de diferencias si las hay, o novedades del turno..."></textarea>
                                </div>

                                <div class="alert alert-warning py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                                    <span>⚠️</span>
                                    <span>Al confirmar, la caja quedará formalmente cerrada, se generará el código único de cierre y se habilitará la impresión del <strong>Ticket Térmico (80mm)</strong>.</span>
                                </div>
                            </div>
                            <div class="modal-footer bg-light p-2">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger btn-sm fw-bold px-3">
                                    🔒 Sí, Confirmar y Cerrar Caja Definitivamente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL: ABRIR CAJA DESDE VISTA DE CIERRES -->
            <div class="modal fade" id="modalAbrirCajaCierres" tabindex="-1" aria-labelledby="modalAbrirCajaCierresLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow border-0">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title fw-bold" id="modalAbrirCajaCierresLabel">🟢 Apertura Formal de Caja / Turno</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <form action="/suramerica2030/caja/abrir" method="POST" data-live="true">
                            <input type="hidden" name="fecha_apertura" value="<?= htmlspecialchars($fecha_consulta) ?>">
                            <input type="hidden" name="sucursal_id" value="<?= htmlspecialchars($sucursal_id) ?>">
                            <input type="hidden" name="redirect_to" value="historial_cierres">
                            <div class="modal-body p-4">
                                <div class="text-center mb-3">
                                    <span style="font-size: 2.8rem;">💵</span>
                                    <h6 class="fw-bold text-dark mt-1">Iniciar Operación de Caja</h6>
                                    <p class="text-muted small mb-0">
                                        Fecha: <strong><?= htmlspecialchars($fecha_consulta) ?></strong> | 
                                        Sucursal: <strong><?= htmlspecialchars($nombreSucursalActual) ?></strong>
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
                                            <small class="text-primary fw-bold" style="cursor: pointer;" onclick="establecerFondoInicialCierres(<?= number_format($sugerenciaApertura['sugerido'], 2, '.', '') ?>)">
                                                Usar sugerido
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text fw-bold bg-light">$</span>
                                        <input type="number" step="0.01" min="0" name="valor_inicial" id="apertura_valor_inicial_cierres" 
                                               class="form-control form-control-lg fw-bold text-success" 
                                               value="<?= number_format($sugerenciaApertura['sugerido'] ?? 0, 2, '.', '') ?>" required autofocus>
                                    </div>

                                    <!-- BOTONES DE PRESETEO RÁPIDO (1 CLIC) -->
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        <?php if(isset($sugerenciaApertura) && !empty($sugerenciaApertura['existe_anterior'])): ?>
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCierres(<?= number_format($sugerenciaApertura['sugerido'], 2, '.', '') ?>)">
                                                🔄 Saldo Anterior ($<?= number_format($sugerenciaApertura['sugerido'], 2) ?>)
                                            </button>
                                            <?php if($sugerenciaApertura['base_anterior'] > 0 && abs($sugerenciaApertura['base_anterior'] - $sugerenciaApertura['sugerido']) > 0.01): ?>
                                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCierres(<?= number_format($sugerenciaApertura['base_anterior'], 2, '.', '') ?>)">
                                                    🪙 Base Anterior ($<?= number_format($sugerenciaApertura['base_anterior'], 2) ?>)
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCierres(50.00)">
                                            🪙 Base $50.00
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCierres(20.00)">
                                            🪙 Base $20.00
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="establecerFondoInicialCierres(0.00)">
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

        <?php endif; ?>

    </div>

    <!-- ========================================================================= -->
    <!-- PESTAÑA 2: HISTORIAL DE CIERRES ANTERIORES                                -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade <?= $tabHistorialActiva ? 'show active' : '' ?>" id="tab-historial" role="tabpanel">

        <!-- PANEL DE BUSCADOR Y FILTROS DEL HISTORIAL -->
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body p-3">
                <form action="/suramerica2030/caja/historial_cierres" method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="buscar_historial" value="1">
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Sucursal</label>
                        <select name="sucursal" class="form-select form-select-sm">
                            <option value="">Todas las Sucursales</option>
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
                        <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Estado Depósito</label>
                        <select name="estado_deposito" class="form-select form-select-sm">
                            <option value="">Todos los Estados</option>
                            <option value="PENDIENTE" <?= (isset($_GET['estado_deposito']) && $_GET['estado_deposito'] === 'PENDIENTE') ? 'selected' : '' ?>>Pendiente</option>
                            <option value="PARCIAL" <?= (isset($_GET['estado_deposito']) && $_GET['estado_deposito'] === 'PARCIAL') ? 'selected' : '' ?>>Depósito Parcial</option>
                            <option value="COMPLETO" <?= (isset($_GET['estado_deposito']) && $_GET['estado_deposito'] === 'COMPLETO') ? 'selected' : '' ?>>Depósito Completo</option>
                            <option value="APROBADO" <?= (isset($_GET['estado_deposito']) && $_GET['estado_deposito'] === 'APROBADO') ? 'selected' : '' ?>>Auditado / Aprobado</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-danger btn-sm w-50 fw-bold">Buscar</button>
                        <a href="/suramerica2030/caja/historial_cierres?buscar_historial=1" class="btn btn-secondary btn-sm w-50 fw-bold">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 11px;">
                <thead class="table-light text-center">
                    <tr>
                        <th>Código Cierre</th>
                        <th>Cajero(a)</th>
                        <th>Sucursal</th>
                        <th>Fecha y Hora</th>
                        <th class="text-end">Efectivo Arqueado</th>
                        <th class="text-end">Comisión Retenida</th>
                        <th class="text-end text-primary">Exigible Depósito</th>
                        <th class="text-end text-success">Depositado</th>
                        <th class="text-end">V. Por Depositar</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($historial_cierres)): ?>
                        <tr><td colspan="11" class="text-center text-muted py-4">No hay cierres de caja registrados para los filtros seleccionados.</td></tr>
                    <?php else: ?>
                        <?php foreach($historial_cierres as $cierre): ?>
                            <?php 
                                $valExigible = floatval($cierre['VALOR_A_DEPOSITAR_CALC'] ?? $cierre['VALOR_A_DEPOSITAR'] ?? ($cierre['SALDO_FINAL'] - ($cierre['COMISION_EMPLEADOS'] ?? 0)));
                                $valPendiente = floatval($cierre['V_POR_DEPOSITAR'] ?? max(0, $valExigible - floatval($cierre['DEPOSITO'] ?? 0)));
                            ?>
                            <tr id="fila_cierre_<?= $cierre['ID_CIERRE'] ?>">
                                <td class="text-primary fw-bold text-center">
                                    <?= htmlspecialchars($cierre['CODIGO_CIERRE'] ?? '#'.$cierre['ID_CIERRE']) ?>
                                </td>
                                <td><?= htmlspecialchars($cierre['CAJERO']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($cierre['SUCURSAL_NOMBRE']) ?></td>
                                <td class="text-center">
                                    <?= date('Y-m-d H:i', strtotime($cierre['FECHA_HORA'])) ?>
                                </td>
                                <td class="text-end fw-bold">$ <?= number_format($cierre['SALDO_FINAL'], 2) ?></td>
                                <td class="text-end text-danger fw-bold">-$ <?= number_format($cierre['COMISION_EMPLEADOS'] ?? 0, 2) ?></td>
                                <td class="text-end fw-bold text-primary">$ <?= number_format($valExigible, 2) ?></td>
                                <td class="text-end text-success fw-bold">$ <?= number_format($cierre['DEPOSITO'], 2) ?></td>
                                <td class="text-end fw-bold <?= $valPendiente > 0 ? 'text-danger' : 'text-success' ?>">
                                    $ <?= number_format($valPendiente, 2) ?>
                                </td>
                                <td class="text-center">
                                    <?php if(($cierre['DEPOSITO_ESTADO'] ?? '') === 'APROBADO'): ?>
                                        <span class="badge bg-success">Auditado</span>
                                    <?php elseif(($cierre['DEPOSITO_ESTADO'] ?? '') === 'COMPLETO'): ?>
                                        <span class="badge bg-primary">Depositado</span>
                                    <?php elseif(($cierre['DEPOSITO_ESTADO'] ?? '') === 'PARCIAL'): ?>
                                        <span class="badge bg-warning text-dark">Dep. Parcial</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Por Depositar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown">
                                            ⚙️ Opciones
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow" style="font-size: 11px;">
                                            <li><a class="dropdown-item" href="/suramerica2030/caja/ticket_cierre?id=<?= $cierre['ID_CIERRE'] ?>" target="_blank">🖨️ Ticket Térmico (80mm)</a></li>
                                            <li><a class="dropdown-item" href="/suramerica2030/caja/imprimir?fecha=<?= $cierre['FECHA_CAJA'] ?>&sucursal=<?= $cierre['ID_SUCURSAL'] ?>" target="_blank">📄 Imprimir Reporte PDF</a></li>
                                            <li><a class="dropdown-item text-primary" href="/suramerica2030/caja/depositos?sucursal=<?= $cierre['ID_SUCURSAL'] ?>">🏦 Gestionar Depósitos</a></li>
                                            <?php if(isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2])): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-secondary" href="#" data-bs-toggle="modal" data-bs-target="#modalAjustar<?= $cierre['ID_CIERRE'] ?>">✏️ Ajustar Depósito</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>

                                    <!-- MODAL AJUSTAR DEPÓSITO (SUPERVISOR) -->
                                    <?php if(isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2])): ?>
                                    <div class="modal fade text-start" id="modalAjustar<?= $cierre['ID_CIERRE'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h6 class="modal-title fs-6">Ajustar Cierre #<?= $cierre['ID_CIERRE'] ?></h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="/suramerica2030/caja/ajustar_cierre" method="POST" data-live="true" data-live-highlight="#fila_cierre_<?= $cierre['ID_CIERRE'] ?>">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_cierre" value="<?= $cierre['ID_CIERRE'] ?>">
                                                        <div class="mb-2">
                                                            <label class="form-label small">Monto Depositado ($)</label>
                                                            <input type="number" step="0.01" name="deposito" class="form-control form-control-sm" value="<?= $cierre['DEPOSITO'] ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer p-1">
                                                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- SCRIPT MODULAR DE CAJA Y ARQUEO FÍSICO -->
<script src="/suramerica2030/public/assets/js/caja.js?v=<?= @filemtime(__DIR__ . '/../../../../public/assets/js/caja.js') ?: time() ?>"></script>