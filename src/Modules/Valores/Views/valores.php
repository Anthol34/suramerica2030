<?php
$puedeEditarPrecios = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_editar_precios']) && $_SESSION['p_editar_precios'] == 1);
$puedeAnularDirecto = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_anular']) && $_SESSION['p_anular'] == 1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="text-primary">Gestión de Remesas y Valores</h3>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoValor">
        + Registrar Envío de Dinero
    </button>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
        <?php 
            if($_GET['status'] == 'success_envio') echo "✅ Dinero recibido y registrado en caja exitosamente.";
            elseif($_GET['status'] == 'success_entrega') echo "✅ Dinero marcado como entregado. Egreso registrado en caja.";
            elseif($_GET['status'] == 'success_delete') echo "🛑 Envío anulado. Se ha registrado el egreso para devolver el dinero.";
            elseif($_GET['status'] == 'success_solicitud') echo "📩 Solicitud de anulación enviada al administrador exitosamente.";
            else echo "❌ Error: " . htmlspecialchars($_GET['msg'] ?? 'Ocurrió un problema.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- NAVEGACIÓN DE PESTAÑAS -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= (!isset($filtros['tab']) || $filtros['tab'] == 'activos') ? 'active fw-bold' : '' ?>" href="?tab=activos">⏳ Envíos Pendientes</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= (isset($filtros['tab']) && $filtros['tab'] == 'historial') ? 'active fw-bold' : '' ?>" href="?tab=historial">📚 Buscador e Historial</a>
    </li>
</ul>

<!-- BUSCADOR E HISTORIAL -->
<?php if(isset($filtros['tab']) && $filtros['tab'] == 'historial'): ?>
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <form action="/suramerica2030/valores" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="historial">
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary">Buscar (Cédula o Nombre)</label>
                <input type="text" name="buscar" class="form-control" placeholder="Ej: 131415..." value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary">Estado del Envío</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="Por Retirar" <?= (($filtros['estado'] ?? '') == 'Por Retirar') ? 'selected' : '' ?>>Por Retirar</option>
                    <option value="Entregado" <?= (($filtros['estado'] ?? '') == 'Entregado') ? 'selected' : '' ?>>Entregados</option>
                    <option value="Anulado" <?= (($filtros['estado'] ?? '') == 'Anulado') ? 'selected' : '' ?>>Anulados</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary">Desde</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($filtros['fecha_inicio'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary">Hasta</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($filtros['fecha_fin'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- TABLA DE REGISTROS -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Remitente</th>
                    <th>Destinatario</th>
                    <th>Ruta</th>
                    <th class="text-end">Monto a Entregar</th>
                    <th>Estado</th>
                    <th class="text-center" style="width: 125px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($valores)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay registros de valores.</td></tr>
                <?php else: ?>
                    <?php foreach($valores as $val): ?>
                        <tr class="<?= $val['VA_ESTADO'] == 'Anulado' ? 'table-secondary opacity-75' : '' ?>">
                            <td>
                                <?= $val['FA_FECHA'] ?><br>
                                <small class="text-muted">Fact #<?= $val['ID_FACTURA'] ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars(($val['REM_NOMBRE'] ?? '') . ' ' . ($val['REM_APELLIDO'] ?? '')) ?><br>
                                <small class="text-muted">CI: <?= htmlspecialchars($val['REM_CI'] ?? '') ?></small>
                            </td>
                            <td>
                                <?php 
                                    $nombreDestinatario = trim(($val['DES_NOMBRE'] ?? '') . ' ' . ($val['DES_APELLIDO'] ?? ''));
                                    if (empty($nombreDestinatario)) $nombreDestinatario = $val['FA_DETALLE'];
                                ?>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($nombreDestinatario) ?></span><br>
                                <small class="text-muted">CI: <?= htmlspecialchars($val['DES_CI'] ?? 'S/N') ?></small>
                            </td>
                            <td><?= htmlspecialchars($val['RUT_ORIGEN']) ?> ➔ <?= htmlspecialchars($val['RUT_DESTINO']) ?></td>
                            <td class="text-end fw-bold text-success">$ <?= number_format($val['MONTO_ENVIADO'], 2) ?></td>
                            <td>
                                <?php if($val['VA_ESTADO'] == 'Por Retirar'): ?>
                                    <span class="badge bg-warning text-dark">⏳ Por Retirar</span>
                                    <?php if(!empty($val['SOLICITUD_ESTADO']) && $val['SOLICITUD_ESTADO'] === 'PENDIENTE'): ?>
                                        <br><span class="badge bg-warning text-dark border border-dark mt-1">⏳ Anulación Solicitada</span>
                                    <?php elseif(!empty($val['SOLICITUD_ESTADO']) && $val['SOLICITUD_ESTADO'] === 'RECHAZADA'): ?>
                                        <br><span class="badge bg-danger mt-1">🚫 Solicitud Denegada</span>
                                    <?php endif; ?>
                                <?php elseif($val['VA_ESTADO'] == 'Anulado'): ?>
                                    <span class="badge bg-danger">❌ Anulado</span>
                                <?php else: ?>
                                    <span class="badge bg-success">✅ Entregado</span><br>
                                    <small class="text-muted"><?= $val['VA_RETIRO'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">⚙️ Opciones</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 12px;">
                                        <li>
                                            <a class="dropdown-item fw-bold text-dark" target="_blank" href="/suramerica2030/ventas/ticket/<?= $val['ID_FACTURA'] ?>">
                                                🖨️ Re-imprimir Ticket
                                            </a>
                                        </li>
                                        <?php if($val['VA_ESTADO'] == 'Por Retirar'): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="/suramerica2030/valores/entregar" method="POST" onsubmit="return confirm('¿Confirmas que estás entregando físicamente el dinero? Esto generará un egreso en caja.');" style="margin: 0;">
                                                    <input type="hidden" name="id_valor" value="<?= $val['ID_VALOR'] ?>">
                                                    <input type="hidden" name="id_factura" value="<?= $val['ID_FACTURA'] ?>">
                                                    <input type="hidden" name="monto_entregar" value="<?= $val['MONTO_ENVIADO'] ?>">
                                                    <button type="submit" class="dropdown-item fw-bold text-success">
                                                        💵 Entregar Dinero
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <?php if($puedeAnularDirecto): ?>
                                                    <a class="dropdown-item fw-bold text-danger" href="#" data-bs-toggle="modal" data-bs-target="#modalEliminarValor<?= $val['ID_VALOR'] ?>">
                                                        ❌ Anular Envío
                                                    </a>
                                                <?php else: ?>
                                                    <?php if(!empty($val['SOLICITUD_ESTADO']) && $val['SOLICITUD_ESTADO'] === 'PENDIENTE'): ?>
                                                        <span class="dropdown-item text-warning fw-bold disabled">
                                                            ⏳ Anulación en Revisión
                                                        </span>
                                                    <?php else: ?>
                                                        <a class="dropdown-item fw-bold text-warning" href="#" data-bs-toggle="modal" data-bs-target="#modalSolicitarValor<?= $val['ID_VALOR'] ?>">
                                                            📩 Solicitar Anulación
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </li>
                                        <?php elseif($val['VA_ESTADO'] == 'Anulado'): ?>
                                            <li><span class="dropdown-item text-muted disabled">❌ Envío Anulado</span></li>
                                        <?php else: ?>
                                            <li><span class="dropdown-item text-muted disabled">✅ Dinero Entregado</span></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>

                        <!-- MODAL ANULAR VALOR -->
                        <?php if($puedeAnularDirecto): ?>
                        <div class="modal fade" id="modalEliminarValor<?= $val['ID_VALOR'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title fs-6">Anular Envío #<?= $val['ID_FACTURA'] ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="/suramerica2030/valores/eliminar" method="POST">
                                        <div class="modal-body text-center">
                                            <input type="hidden" name="id_factura" value="<?= $val['ID_FACTURA'] ?>">
                                            <p class="mb-2">¿Estás seguro de anular este envío?</p>
                                            <div class="alert alert-warning p-2 mt-2" style="font-size:11px;">
                                                Se generará un <strong>EGRESO</strong> de <strong>$<?= number_format($val['TOTAL_COBRADO'], 2) ?></strong> en la caja de hoy para devolver el dinero.
                                            </div>
                                        </div>
                                        <div class="modal-footer p-1">
                                            <button type="submit" class="btn btn-danger w-100 fw-bold">Sí, Anular y Reembolsar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- MODAL SOLICITAR ANULACIÓN VALOR -->
                        <div class="modal fade" id="modalSolicitarValor<?= $val['ID_VALOR'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning text-dark">
                                        <h5 class="modal-title fs-6 fw-bold">📩 Solicitar Anulación #<?= $val['ID_FACTURA'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="/suramerica2030/valores/solicitarAnulacion" method="POST">
                                        <div class="modal-body text-start">
                                            <input type="hidden" name="id_factura" value="<?= $val['ID_FACTURA'] ?>">
                                            <input type="hidden" name="monto_devolver" value="<?= $val['TOTAL_COBRADO'] ?>">
                                            <p class="small mb-2 text-muted">Esta solicitud será enviada al Administrador para su revisión y aprobación.</p>
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">Total a Reembolsar:</label>
                                                <input type="text" class="form-control form-control-sm bg-light fw-bold text-danger" value="$<?= number_format($val['TOTAL_COBRADO'], 2) ?>" readonly>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">Motivo de Anulación *</label>
                                                <textarea name="motivo" class="form-control form-control-sm" rows="3" required placeholder="Explica detalladamente por qué se anula este envío..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer p-2">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-warning btn-sm fw-bold">Enviar Solicitud</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- PAGINACIÓN VALORES -->
        <?php if(isset($totalPaginas) && $totalPaginas > 1): ?>
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center border-0 pt-3 pb-3">
                <small class="text-muted fw-bold">Mostrando página <?= $pagina ?> de <?= $totalPaginas ?> (<?= $totalRegistros ?? '' ?> registros)</small>
                <ul class="pagination pagination-sm mb-0 shadow-sm">
                    <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link text-primary fw-bold" href="?pagina=<?= $pagina - 1 ?><?= !empty($queryString) ? '&' . $queryString : '' ?>">Anterior</a>
                    </li>
                    <?php 
                    $startP = max(1, $pagina - 2);
                    $endP = min($totalPaginas, $pagina + 2);
                    if($endP - $startP < 4) {
                        if($startP == 1) $endP = min($totalPaginas, $startP + 4);
                        else $startP = max(1, $endP - 4);
                    }
                    if($startP > 1): ?>
                        <li class="page-item"><a class="page-link" href="?pagina=1<?= !empty($queryString) ? '&' . $queryString : '' ?>">1</a></li>
                        <?php if($startP > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for($i = $startP; $i <= $endP; $i++): ?>
                        <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?><?= !empty($queryString) ? '&' . $queryString : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if($endP < $totalPaginas): ?>
                        <?php if($endP < $totalPaginas - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?pagina=<?= $totalPaginas ?><?= !empty($queryString) ? '&' . $queryString : '' ?>"><?= $totalPaginas ?></a></li>
                    <?php endif; ?>
                    <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                        <a class="page-link text-primary fw-bold" href="?pagina=<?= $pagina + 1 ?><?= !empty($queryString) ? '&' . $queryString : '' ?>">Siguiente</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalNuevoValor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Registrar Nuevo Envío de Dinero</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/suramerica2030/valores/guardar" method="POST" id="formNuevoValor" onsubmit="return validarEnvioValor(this)">
                <div class="modal-body">
                    <h6 class="text-secondary border-bottom pb-1">1. Datos del Remitente (Quien envía)</h6>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label>Cédula</label>
                            <div class="input-group">
                                <input type="text" name="cliente_id" id="remitente_id_v" class="form-control" required>
                                <span class="input-group-text bg-white">🔍</span>
                            </div>
                        </div>
                        <div class="col-md-3"><label>Nombres</label><input type="text" name="cliente_nombre" id="remitente_nombre_v" class="form-control" required></div>
                        <div class="col-md-3"><label>Apellidos</label><input type="text" name="cliente_apellido" id="remitente_apellido_v" class="form-control" required></div>
                        <div class="col-md-3"><label>Teléfono</label><input type="text" name="cliente_telefono" id="remitente_telefono_v" class="form-control"></div>
                    </div>

                    <h6 class="text-secondary border-bottom pb-1 mt-3">2. Datos del Destinatario (Quien recibe)</h6>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label>Cédula / Pasaporte</label>
                            <div class="input-group">
                                <input type="text" name="destinatario_ci" id="destinatario_id_v" class="form-control" required>
                                <span class="input-group-text bg-white">🔍</span>
                            </div>
                        </div>
                        <div class="col-md-3"><label>Nombres</label><input type="text" name="destinatario_nombre" id="destinatario_nombre_v" class="form-control" required></div>
                        <div class="col-md-3"><label>Apellidos</label><input type="text" name="destinatario_apellido" id="destinatario_apellido_v" class="form-control" required></div>
                        <div class="col-md-3"><label>Teléfono</label><input type="text" name="destinatario_telefono" id="destinatario_telefono_v" class="form-control"></div>
                    </div>

                    <h6 class="text-secondary border-bottom pb-1 mt-3">3. Detalles Monetarios e Itinerario de Envío</h6>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-primary">📍</span> Ciudad Origen</label>
                            <select id="sel_origen_val" class="form-select border-primary" required onchange="alCambiarOrigenVal()">
                                <option value="">- Seleccione Origen -</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-danger">🏁</span> Ciudad Destino</label>
                            <select id="sel_destino_val" class="form-select border-primary" required onchange="alCambiarDestinoVal()" disabled>
                                <option value="">- Elija Origen Primero -</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Método de Pago</label>
                            <select name="metodo_pago" id="metodo_pago_val" class="form-select border-success" required onchange="togglePagoMixtoVal()">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="TARJETA">Tarjeta (POS)</option>
                                <option value="MIXTO">Mixto (Efectivo + Transferencia)</option>
                            </select>
                        </div>

                        <!-- ID_RUTA que se envía en el POST -->
                        <input type="hidden" name="ruta_id" id="ruta_id" value="" required>

                        <!-- Ficha Resumen de Comisión de la Ruta Seleccionada -->
                        <div class="col-12 mt-2 d-none" id="info_ruta_val">
                            <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center">
                                <div>
                                    <span id="badge_tipo_val" class="badge bg-success fs-6">➡️ DIRECTO</span>
                                    <span id="badge_nombre_val" class="fw-bold text-dark ms-2 small"></span>
                                </div>
                                <div>
                                    <span class="badge bg-primary fs-6">Comisión de Ruta: <span id="lbl_comision_pct">10.0</span>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row bg-light p-3 rounded">
                        <div class="col-md-4">
                            <label>Monto a Enviar ($)</label>
                            <input type="number" step="0.01" name="monto_enviar" id="monto_enviar" class="form-control fw-bold text-success fs-5" required placeholder="0.00" oninput="calcularCalculosValores()">
                        </div>
                        <div class="col-md-4">
                            <label>Comisión Calculada ($)</label>
                            <input type="number" step="0.01" name="comision" id="comision" class="form-control text-danger fw-bold fs-5 <?= !$puedeEditarPrecios ? 'bg-light' : '' ?>" required placeholder="0.00" <?= !$puedeEditarPrecios ? 'readonly title="Solo usuarios con permiso de edición de precios pueden alterar la comisión o aplicar descuentos"' : '' ?> oninput="recalcularTotalManual()">
                            <small class="text-muted" id="lbl_porcentaje">Comisión: 10%</small>
                            <?php if (!$puedeEditarPrecios): ?>
                                <small class="text-danger d-block" style="font-size: 0.72rem;">🔒 Tarifa calculada fija (sin permiso de descuento)</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Total a Cobrar ($)</label>
                            <input type="number" step="0.01" name="monto_total" id="monto_total" class="form-control fw-bold bg-white fs-5" readonly placeholder="0.00" oninput="balancearMixtoVal('total')">
                        </div>
                        <div class="col-12 mt-2 d-none" id="div_pago_mixto_val">
                            <div class="p-2 rounded bg-white border border-info shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-primary">💳 Desglose de Pago Mixto</span>
                                    <span class="badge bg-success" id="badge_check_mixto_val">✔ Cuadrado</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-0">Parte en Efectivo ($)</label>
                                        <input type="number" step="0.01" min="0" name="monto_efectivo" id="monto_efectivo_val" class="form-control form-control-sm fw-bold text-success" placeholder="0.00" oninput="balancearMixtoVal('efectivo')">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-0">Parte en Transferencia ($)</label>
                                        <input type="number" step="0.01" min="0" name="monto_transferencia" id="monto_transferencia_val" class="form-control form-control-sm fw-bold text-primary" placeholder="0.00" oninput="balancearMixtoVal('transferencia')">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                                    ℹ️ Modifique cualquiera de los dos valores y el otro se balanceará automáticamente.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary fw-bold px-4">Registrar y Cobrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// =============== DATOS MAESTROS DE RUTAS Y SUCURSAL ===============
const TODAS_LAS_RUTAS_VAL = <?= json_encode($rutas) ?>;
const CIUDAD_SUCURSAL_ACTUAL_VAL = <?= json_encode(strtoupper(trim($_SESSION['ciudad_sucursal'] ?? ''))) ?>;

function inicializarOrigenesVal() {
    let selOrigen = document.getElementById('sel_origen_val');
    if (!selOrigen) return;
    
    let origenesUnicos = Array.from(new Set(TODAS_LAS_RUTAS_VAL.map(r => r.RUT_ORIGEN))).sort();
    selOrigen.innerHTML = '<option value="">- Seleccione Origen -</option>';
    origenesUnicos.forEach(orig => {
        let opt = document.createElement('option');
        opt.value = orig;
        opt.textContent = '📍 ' + orig;
        selOrigen.appendChild(opt);
    });
    
    // Auto-seleccionar sucursal si coincide
    if (CIUDAD_SUCURSAL_ACTUAL_VAL) {
        let matchSuc = origenesUnicos.find(o => o.toUpperCase() === CIUDAD_SUCURSAL_ACTUAL_VAL);
        if (matchSuc) {
            selOrigen.value = matchSuc;
            alCambiarOrigenVal();
        }
    }
}

function alCambiarOrigenVal() {
    let origen = document.getElementById('sel_origen_val').value;
    let selDestino = document.getElementById('sel_destino_val');
    let rutaIdInput = document.getElementById('ruta_id');
    let infoRuta = document.getElementById('info_ruta_val');
    
    if (!origen) {
        selDestino.innerHTML = '<option value="">- Elija Origen Primero -</option>';
        selDestino.disabled = true;
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        calcularCalculosValores();
        return;
    }
    
    let rutasOrigen = TODAS_LAS_RUTAS_VAL.filter(r => r.RUT_ORIGEN === origen);
    let destinosUnicos = Array.from(new Set(rutasOrigen.map(r => r.RUT_DESTINO))).sort();
    
    selDestino.innerHTML = '<option value="">- Seleccione Destino -</option>';
    destinosUnicos.forEach(dest => {
        let opt = document.createElement('option');
        opt.value = dest;
        opt.textContent = '🏁 ' + dest;
        selDestino.appendChild(opt);
    });
    selDestino.disabled = false;
    rutaIdInput.value = '';
    infoRuta.classList.add('d-none');
    calcularCalculosValores();
}

function alCambiarDestinoVal() {
    let origen = document.getElementById('sel_origen_val').value;
    let destino = document.getElementById('sel_destino_val').value;
    let rutaIdInput = document.getElementById('ruta_id');
    let infoRuta = document.getElementById('info_ruta_val');
    
    if (!origen || !destino) {
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        calcularCalculosValores();
        return;
    }
    
    let match = TODAS_LAS_RUTAS_VAL.find(r => r.RUT_ORIGEN === origen && r.RUT_DESTINO === destino);
    if (match) {
        rutaIdInput.value = match.ID_RUTA;
        infoRuta.classList.remove('d-none');
        
        let badgeTipo = document.getElementById('badge_tipo_val');
        if (match.TIPO_VIAJE === 'CONEXION') {
            badgeTipo.className = 'badge bg-warning text-dark fs-6';
            badgeTipo.textContent = '🔄 CONEXIÓN';
        } else {
            badgeTipo.className = 'badge bg-success fs-6';
            badgeTipo.textContent = '➡️ DIRECTO';
        }
        
        document.getElementById('badge_nombre_val').textContent = match.RUT_ORIGEN + ' ➔ ' + match.RUT_DESTINO;
        let pct = parseFloat(match.COMISION_VALOR || 10.0);
        document.getElementById('lbl_comision_pct').textContent = pct.toFixed(1);
        
        calcularCalculosValores();
    } else {
        alert("No se encontró una ruta configurada para este trayecto.");
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
    }
}

document.getElementById('remitente_id_v').addEventListener('change', function() {
    let id = this.value.trim();
    if (id.length >= 4) {
        fetch('/suramerica2030/valores/buscarCliente?id=' + id)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const nom = document.getElementById('remitente_nombre_v');
                const ape = document.getElementById('remitente_apellido_v');
                if (nom) { nom.value = res.data.CLI_NOMBRE || ''; nom.dispatchEvent(new Event('input', { bubbles: true })); }
                if (ape) { ape.value = res.data.CLI_APELLIDO || ''; ape.dispatchEvent(new Event('input', { bubbles: true })); }
                document.getElementById('remitente_telefono_v').value = res.data.CLI_TELEFONO || '';
            }
        });
    }
});

document.getElementById('destinatario_id_v').addEventListener('change', function() {
    let id = this.value.trim();
    if (id.length >= 4) {
        fetch('/suramerica2030/valores/buscarCliente?id=' + id)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const nom = document.getElementById('destinatario_nombre_v');
                const ape = document.getElementById('destinatario_apellido_v');
                if (nom) { nom.value = res.data.CLI_NOMBRE || ''; nom.dispatchEvent(new Event('input', { bubbles: true })); }
                if (ape) { ape.value = res.data.CLI_APELLIDO || ''; ape.dispatchEvent(new Event('input', { bubbles: true })); }
                document.getElementById('destinatario_telefono_v').value = res.data.CLI_TELEFONO || '';
            }
        });
    }
});

function calcularCalculosValores() {
    let rutaId = document.getElementById('ruta_id').value;
    let ruta = TODAS_LAS_RUTAS_VAL.find(r => r.ID_RUTA == rutaId);
    
    let pctComision = parseFloat(ruta ? ruta.COMISION_VALOR : 10.0) || 10.0;
    let montoEnviar = parseFloat(document.getElementById('monto_enviar').value) || 0;
    
    let comisionCalculada = (montoEnviar * pctComision) / 100;
    
    document.getElementById('comision').value = comisionCalculada.toFixed(2);
    document.getElementById('monto_total').value = (montoEnviar + comisionCalculada).toFixed(2);
    document.getElementById('lbl_porcentaje').innerText = "Comisión de Ruta: " + pctComision.toFixed(1) + "%";
    balancearMixtoVal('total');
}

function recalcularTotalManual() {
    let montoEnviar = parseFloat(document.getElementById('monto_enviar').value) || 0;
    let comision = parseFloat(document.getElementById('comision').value) || 0;
    document.getElementById('monto_total').value = (montoEnviar + comision).toFixed(2);
    balancearMixtoVal('total');
}

document.addEventListener('DOMContentLoaded', function() {
    inicializarOrigenesVal();
});

// =============== FUNCIONES DE PAGO MIXTO (VALORES) ===============
function togglePagoMixtoVal() {
    const sel = document.getElementById('metodo_pago_val');
    const div = document.getElementById('div_pago_mixto_val');
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoVal('init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoVal(origen) {
    const sel = document.getElementById('metodo_pago_val');
    if (!sel || sel.value !== 'MIXTO') return;
    const totalEl = document.getElementById('monto_total');
    const efEl = document.getElementById('monto_efectivo_val');
    const trEl = document.getElementById('monto_transferencia_val');
    const badge = document.getElementById('badge_check_mixto_val');
    if (!totalEl || !efEl || !trEl) return;

    let total = parseFloat(totalEl.value) || 0;
    let ef = parseFloat(efEl.value) || 0;
    let tr = parseFloat(trEl.value) || 0;

    if (origen === 'init') {
        ef = total > 0 ? (total / 2) : 0;
        tr = total - ef;
        efEl.value = ef.toFixed(2);
        trEl.value = tr.toFixed(2);
    } else if (origen === 'efectivo') {
        tr = Math.max(0, total - ef);
        trEl.value = tr.toFixed(2);
    } else if (origen === 'transferencia') {
        ef = Math.max(0, total - tr);
        efEl.value = ef.toFixed(2);
    } else if (origen === 'total') {
        if (ef > total) ef = total;
        tr = Math.max(0, total - ef);
        efEl.value = ef.toFixed(2);
        trEl.value = tr.toFixed(2);
    }

    if (badge) {
        let diff = Math.abs((parseFloat(efEl.value || 0) + parseFloat(trEl.value || 0)) - total);
        if (diff < 0.02 && total > 0) {
            badge.className = 'badge bg-success';
            badge.textContent = '✔ Cuadrado ($' + total.toFixed(2) + ')';
        } else {
            badge.className = 'badge bg-danger';
            badge.textContent = '⚠️ Descuadre ($' + diff.toFixed(2) + ')';
        }
    }
}

function validarEnvioValor(form) {
    const sel = document.getElementById('metodo_pago_val');
    if (sel && sel.value === 'MIXTO') {
        const total = parseFloat(document.getElementById('monto_total').value) || 0;
        const ef = parseFloat(document.getElementById('monto_efectivo_val').value) || 0;
        const tr = parseFloat(document.getElementById('monto_transferencia_val').value) || 0;
        if (total <= 0) {
            alert('El total a cobrar debe ser mayor a 0.');
            return false;
        }
        if (Math.abs((ef + tr) - total) > 0.02) {
            alert('El pago mixto no coincide: Efectivo ($' + ef.toFixed(2) + ') + Transferencia ($' + tr.toFixed(2) + ') = $' + (ef + tr).toFixed(2) + ', pero el total a cobrar es $' + total.toFixed(2) + '. Corrija los valores antes de continuar.');
            return false;
        }
    }
    return true;
}
</script>