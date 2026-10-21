<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="text-primary text-uppercase fs-5 mb-0">Gestión de Cajas / Depósitos Bancarios</h3>
        <small class="text-muted">Control y conciliación de remesas bancarias contra cierres de caja físicos</small>
    </div>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php 
            if($_GET['status'] == 'success_deposito') echo '✅ Depósito bancario registrado correctamente. El saldo pendiente fue actualizado.';
            elseif($_GET['status'] == 'success_aprobado') echo '✔️ Depósito auditado y aprobado exitosamente.';
            elseif($_GET['status'] == 'success_ajuste') echo '✅ Depósito ajustado correctamente.';
            else echo '❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Ocurrió un inconveniente');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- PANEL DE BUSCADOR Y FILTROS -->
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body p-3">
        <form action="/suramerica2030/caja/depositos" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Estado de Depósito</label>
                <select name="estado_deposito" class="form-select form-select-sm">
                    <option value="">Todos los Estados</option>
                    <option value="PENDIENTE" <?= (isset($_GET['estado_deposito']) && $_GET['estado_deposito'] == 'PENDIENTE') ? 'selected' : '' ?>>Pendiente de Depósito</option>
                    <option value="PARCIAL" <?= (isset($_GET['estado_deposito']) && $_GET['estado_deposito'] == 'PARCIAL') ? 'selected' : '' ?>>Depósito Parcial</option>
                    <option value="COMPLETO" <?= (isset($_GET['estado_deposito']) && $_GET['estado_deposito'] == 'COMPLETO') ? 'selected' : '' ?>>Completo (Por Validar)</option>
                    <option value="APROBADO" <?= (isset($_GET['estado_deposito']) && $_GET['estado_deposito'] == 'APROBADO') ? 'selected' : '' ?>>Aprobado / Auditado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary" style="font-size: 11px;">Punto de emisión (Sucursal)</label>
                <select name="sucursal" class="form-select form-select-sm">
                    <?php if($_SESSION['rol_id'] == 1): ?>
                        <option value="">Todas las Sucursales</option>
                    <?php endif; ?>
                    <?php if(!empty($sucursales)): ?>
                        <?php foreach($sucursales as $s): ?>
                            <?php if($_SESSION['rol_id'] == 1 || $s['ID_SUCURSAL'] == $_SESSION['sucursal_id']): ?>
                                <option value="<?= $s['ID_SUCURSAL'] ?>" <?= (isset($_GET['sucursal']) && $_GET['sucursal'] == $s['ID_SUCURSAL']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['CIUDAD']) ?>
                                </option>
                            <?php endif; ?>
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
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold">🔍 Buscar</button>
                <a href="/suramerica2030/caja/depositos" class="btn btn-secondary btn-sm" title="Limpiar"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<h6 class="fw-bold mb-2 text-dark">📋 Depósitos y Remesas de Cierres de Caja</h6>
<div class="card shadow-sm border-0 table-responsive">
    <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 11px;">
        <thead class="table-dark text-center">
            <tr>
                <th>Fecha Cierre</th>
                <th>Caja / Código</th>
                <th>Sucursal</th>
                <th>Cajero(a)</th>
                <th>Efectivo Arqueado</th>
                <th>Comisión Retenida</th>
                <th>Exigible a Depositar</th>
                <th>Total Depositado</th>
                <th>Saldo Pendiente</th>
                <th>Estado</th>
                <th style="width: 110px;">Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($lista_depositos)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No se encontraron registros de depósitos con los filtros aplicados.</td></tr>
            <?php else: ?>
                <?php foreach($lista_depositos as $deposito): ?>
                    <?php 
                        $valExigible = floatval($deposito['VALOR_A_DEPOSITAR_CALC'] ?? $deposito['VALOR_A_DEPOSITAR'] ?? ($deposito['SALDO_FINAL'] - ($deposito['COMISION_EMPLEADOS'] ?? 0)));
                        $valDepositado = floatval($deposito['DEPOSITO'] ?? 0);
                        $valPendiente = floatval($deposito['V_POR_DEPOSITAR'] ?? max(0, $valExigible - $valDepositado));
                        $numPapeletas = !empty($deposito['detalles_depositos']) ? count($deposito['detalles_depositos']) : 0;
                        $estado = $deposito['DEPOSITO_ESTADO'] ?? 'PENDIENTE';
                    ?>
                    <tr id="fila_deposito_<?= $deposito['ID_CIERRE'] ?>">
                        <td class="text-center fw-bold"><?= date('Y-m-d H:i', strtotime($deposito['FECHA_HORA'])) ?></td>
                        <td class="text-primary fw-bold text-center"><?= htmlspecialchars($deposito['CODIGO_CIERRE'] ?? '#'.$deposito['ID_CIERRE']) ?></td>
                        <td><?= htmlspecialchars($deposito['SUCURSAL_NOMBRE'] ?? 'Central') ?></td>
                        <td><?= htmlspecialchars($deposito['CAJERO'] ?? 'Cajero') ?></td>
                        
                        <!-- Efectivo físico en gaveta -->
                        <td class="text-end">$<?= number_format($deposito['SALDO_FINAL'], 2) ?></td>
                        
                        <!-- Comisión retenida en origen -->
                        <td class="text-end text-danger fw-bold">-$<?= number_format($deposito['COMISION_EMPLEADOS'] ?? 0, 2) ?></td>
                        
                        <!-- Monto real exigible a depositar -->
                        <td class="text-end fw-bold text-primary bg-light" style="font-size: 12px;">$<?= number_format($valExigible, 2) ?></td>
                        
                        <!-- Total depositado -->
                        <td class="text-end fw-bold text-success">
                            $<?= number_format($valDepositado, 2) ?>
                            <?php if($numPapeletas > 0): ?>
                                <br><span class="badge bg-secondary" style="font-size: 9px;"><?= $numPapeletas ?> papeleta(s)</span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Saldo pendiente por depositar -->
                        <td class="text-end fw-bold <?= $valPendiente > 0 ? 'text-danger bg-danger-subtle' : 'text-success bg-success-subtle' ?>">
                            $<?= number_format($valPendiente, 2) ?>
                        </td>
                        
                        <!-- Estado -->
                        <td class="text-center">
                            <?php if($estado == 'APROBADO'): ?>
                                <span class="badge bg-success">✔️ APROBADO</span>
                                <?php if(!empty($deposito['DEPOSITO_FECHA_APROBACION'])): ?>
                                    <div class="text-muted" style="font-size: 9px;"><?= date('d/m/Y', strtotime($deposito['DEPOSITO_FECHA_APROBACION'])) ?></div>
                                <?php endif; ?>
                            <?php elseif($estado == 'COMPLETO'): ?>
                                <span class="badge bg-info text-dark">COMPLETO (AUDITAR)</span>
                            <?php elseif($estado == 'PARCIAL'): ?>
                                <span class="badge bg-warning text-dark">DEP. PARCIAL</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">PENDIENTE</span>
                            <?php endif; ?>
                        </td>

                        <!-- Acciones en Menú Desplegable -->
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown">
                                    ⚙️ Opciones
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" style="font-size: 11px;">
                                    <?php if($estado != 'APROBADO'): ?>
                                        <li>
                                            <a class="dropdown-item text-primary fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalInsertarDeposito<?= $deposito['ID_CIERRE'] ?>">
                                                ➕ Registrar Depósito
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php if($numPapeletas > 0): ?>
                                        <li>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalVerDetalle<?= $deposito['ID_CIERRE'] ?>">
                                                📄 Ver Papeletas (<?= $numPapeletas ?>)
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <li>
                                        <a class="dropdown-item" href="/suramerica2030/caja/imprimir?fecha=<?= $deposito['FECHA_CAJA'] ?>&sucursal=<?= $deposito['ID_SUCURSAL'] ?>" target="_blank">
                                            🖨️ Imprimir PDF
                                        </a>
                                    </li>

                                    <?php if(isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2]) && $estado != 'APROBADO' && $valDepositado > 0): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-success fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalAprobarDeposito<?= $deposito['ID_CIERRE'] ?>">
                                                ✔️ Auditar y Aprobar
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <!-- MODAL 1: REGISTRAR DEPÓSITO (Cajero / Colaborador de Guayaquil o Sucursales) -->
                            <div class="modal fade text-start" id="modalInsertarDeposito<?= $deposito['ID_CIERRE'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white py-2">
                                            <h6 class="modal-title fs-6">➕ Registrar Depósito Bancario</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="/suramerica2030/caja/registrar_deposito" method="POST" data-live="true" data-live-highlight="#fila_deposito_<?= $deposito['ID_CIERRE'] ?>">
                                            <input type="hidden" name="id_cierre" value="<?= $deposito['ID_CIERRE'] ?>">
                                            <div class="modal-body p-3">
                                                <!-- Resumen del Cierre -->
                                                <div class="bg-light p-2 rounded mb-3 border">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-secondary">Cierre: <strong><?= htmlspecialchars($deposito['CODIGO_CIERRE'] ?? '#'.$deposito['ID_CIERRE']) ?></strong></span>
                                                        <span class="text-secondary">Sucursal: <strong><?= htmlspecialchars($deposito['SUCURSAL_NOMBRE']) ?></strong></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mt-1" style="font-size: 11px;">
                                                        <span>Efectivo Físico: <strong>$<?= number_format($deposito['SALDO_FINAL'], 2) ?></strong></span>
                                                        <span>Comisión Retenida: <strong class="text-danger">-$<?= number_format($deposito['COMISION_EMPLEADOS'] ?? 0, 2) ?></strong></span>
                                                    </div>
                                                    <hr class="my-1">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold text-dark">Exigible a Depositar:</span>
                                                        <span class="fw-bold text-primary fs-6">$<?= number_format($valExigible, 2) ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-success">Ya Depositado:</span>
                                                        <span class="text-success fw-bold">$<?= number_format($valDepositado, 2) ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-danger fw-bold">Saldo Restante por Depositar:</span>
                                                        <span class="text-danger fw-bold fs-6">$<?= number_format($valPendiente, 2) ?></span>
                                                    </div>
                                                </div>

                                                <!-- Campos del depósito -->
                                                <div class="row g-2">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label fw-bold" style="font-size: 12px;">Institución Bancaria</label>
                                                        <select name="banco" class="form-select form-select-sm" required>
                                                            <option value="Banco Pichincha">Banco Pichincha</option>
                                                            <option value="Banco Guayaquil">Banco Guayaquil</option>
                                                            <option value="Produbanco">Produbanco</option>
                                                            <option value="Banco Bolivariano">Banco Bolivariano</option>
                                                            <option value="Banco del Pacífico">Banco del Pacífico</option>
                                                            <option value="Banco del Barrio / Vecino">Banco del Barrio / Vecino</option>
                                                            <option value="Cooperativa / Otro">Cooperativa / Otro</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label fw-bold" style="font-size: 12px;">Nro. Papeleta / Referencia</label>
                                                        <input type="text" name="nro_documento" class="form-control form-control-sm" required placeholder="Ej: 104928374">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label fw-bold text-success" style="font-size: 12px;">Monto Depositado ($)</label>
                                                        <input type="number" step="0.01" name="monto" class="form-control form-control-sm fw-bold text-success" required value="<?= $valPendiente > 0 ? $valPendiente : $valExigible ?>" max="<?= $valExigible ?>" min="0.01">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label fw-bold" style="font-size: 12px;">Detalle / Observaciones</label>
                                                        <input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Ej: Depósito turno tarde">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer p-2 bg-light">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary btn-sm fw-bold">💾 Registrar Papeleta de Depósito</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- MODAL 2: VER PAPELETAS REGISTRADAS -->
                            <?php if($numPapeletas > 0): ?>
                            <div class="modal fade text-start" id="modalVerDetalle<?= $deposito['ID_CIERRE'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-dark text-white py-2">
                                            <h6 class="modal-title fs-6">📄 Comprobantes de Depósito - Cierre <?= htmlspecialchars($deposito['CODIGO_CIERRE'] ?? '#'.$deposito['ID_CIERRE']) ?></h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-3">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm align-middle mb-0" style="font-size: 11px;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Fecha / Hora</th>
                                                            <th>Banco</th>
                                                            <th>Nro. Papeleta</th>
                                                            <th>Monto</th>
                                                            <th>Registrado Por</th>
                                                            <th>Observaciones</th>
                                                            <th>Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($deposito['detalles_depositos'] as $idx => $det): ?>
                                                            <tr>
                                                                <td class="text-center"><?= $idx + 1 ?></td>
                                                                <td><?= date('Y-m-d H:i', strtotime($det['FECHA_DEPOSITO'])) ?></td>
                                                                <td class="fw-bold"><?= htmlspecialchars($det['BANCO']) ?></td>
                                                                <td class="text-primary fw-bold"><?= htmlspecialchars($det['NUM_DOCUMENTO']) ?></td>
                                                                <td class="text-end fw-bold text-success">$<?= number_format($det['MONTO'], 2) ?></td>
                                                                <td><?= htmlspecialchars($det['CAJERO'] ?? 'Cajero') ?></td>
                                                                <td><?= htmlspecialchars($det['OBSERVACIONES'] ?? '-') ?></td>
                                                                <td class="text-center">
                                                                    <?php if($det['ESTADO'] == 'APROBADO'): ?>
                                                                        <span class="badge bg-success">Aprobado</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary">Registrado</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot class="table-light fw-bold">
                                                        <tr>
                                                            <td colspan="4" class="text-end">TOTAL DEPOSITADO:</td>
                                                            <td class="text-end text-success">$<?= number_format($valDepositado, 2) ?></td>
                                                            <td colspan="3" class="text-danger text-end">FALTA POR DEPOSITAR: $<?= number_format($valPendiente, 2) ?></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer p-2 bg-light">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- MODAL 3: AUDITAR Y APROBAR DEPÓSITO (SUPERVISOR / ADMIN) -->
                            <?php if(isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2])): ?>
                            <div class="modal fade text-start" id="modalAprobarDeposito<?= $deposito['ID_CIERRE'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white py-2">
                                            <h6 class="modal-title fs-6">✔️ Validar y Aprobar Depósito</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="/suramerica2030/caja/procesar_deposito" method="POST" data-live="true" data-live-highlight="#fila_deposito_<?= $deposito['ID_CIERRE'] ?>">
                                            <input type="hidden" name="id_cierre" value="<?= $deposito['ID_CIERRE'] ?>">
                                            <div class="modal-body p-3">
                                                <div class="mb-2">
                                                    <label class="form-label" style="font-size: 11px;">Monto total depositado a auditar</label>
                                                    <input type="text" class="form-control form-control-sm fw-bold text-success" value="$<?= number_format($valDepositado, 2) ?>" disabled>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label" style="font-size: 11px;">Monto exigible</label>
                                                    <input type="text" class="form-control form-control-sm" value="$<?= number_format($valExigible, 2) ?>" disabled>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label fw-bold" style="font-size: 11px;">Papeletas a Conciliar</label>
                                                    <input type="text" name="nro_documento" class="form-control form-control-sm" required value="<?= htmlspecialchars($deposito['DEPOSITO_NUM_DOCUMENTO'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="modal-footer p-2 bg-light">
                                                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">Confirmar Auditoría</button>
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
    
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center pt-3 pb-2">
        <small class="text-muted">Mostrando <?= count($lista_depositos) ?> cierres de caja evaluados.</small>
    </div>
</div>