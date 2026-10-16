<?php
$puedeDespachar = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_despacho']) && $_SESSION['p_despacho'] == 1);
$puedeRegistrarLlegada = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_llegada']) && $_SESSION['p_llegada'] == 1);
$puedeEditarPrecios = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_editar_precios']) && $_SESSION['p_editar_precios'] == 1);
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 class="text-primary mb-0">Rastreo y Gestión de Encomiendas</h3>
    <div class="d-flex gap-2">
        <a href="/suramerica2030/manifiesto?tab=carga" class="btn btn-outline-dark fw-bold shadow-sm" title="Ir a la sección de Manifiestos de Carga">
            🚚 Manifiestos de Carga
        </a>
        <button class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaEncomienda">
            + Recibir Nueva Encomienda
        </button>
    </div>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
        <?php 
            if($_GET['status'] == 'success') echo '✅ Estado actualizado correctamente.';
            elseif($_GET['status'] == 'success_recepcion') echo '✅ Encomienda recibida y cobro registrado en caja.';
            elseif($_GET['status'] == 'success_despacho') echo '🚚 Paquetes despachados y listos para el manifiesto.';
            elseif($_GET['status'] == 'success_unificar') {
                echo '✅ <strong>¡Despachos Integrados con Éxito!</strong> Se han unificado en un solo despacho y manifiesto único.';
                if (!empty($_GET['fecha_d']) && !empty($_GET['ruta_d'])) {
                    echo ' <a href="/suramerica2030/manifiesto/imprimirCarga?fecha='.htmlspecialchars($_GET['fecha_d']).'&ruta_id='.htmlspecialchars($_GET['ruta_d']).'" target="_blank" class="btn btn-sm btn-dark ms-2 fw-bold">🖨️ Ver Manifiesto Unificado Ahora</a>';
                }
            }
            elseif($_GET['status'] == 'success_entregado') echo '✅ Lote de paquetes marcados como LLEGADOS/ENTREGADOS exitosamente.';
            elseif($_GET['status'] == 'success_email') echo '✉️ ¡Manifiesto enviado por correo exitosamente!';
            elseif($_GET['status'] == 'success_delete') echo '🛑 Encomienda anulada. Se ha registrado el egreso en caja.';
            else echo '❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Operación fallida.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- NAVEGACIÓN DE PESTAÑAS -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= (!isset($filtros['tab']) || $filtros['tab'] == 'activos') ? 'active fw-bold' : '' ?>" href="?tab=activos">📦 Paquetes en Tránsito / Bodega</a></li>
    <li class="nav-item"><a class="nav-link <?= (isset($filtros['tab']) && $filtros['tab'] == 'historial') ? 'active fw-bold' : '' ?>" href="?tab=historial">📚 Buscador e Historial</a></li>
    <li class="nav-item"><a class="nav-link text-primary fw-bold" href="/suramerica2030/manifiesto?tab=carga">📋 Manifiesto de Carga ➔</a></li>
</ul>

<!-- BUSCADOR E HISTORIAL -->
<?php if(isset($filtros['tab']) && $filtros['tab'] == 'historial'): ?>
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <form action="/suramerica2030/encomiendas" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="historial">
            <div class="col-md-3"><label class="form-label fw-bold text-secondary">Buscar (Cédula o Nombre)</label><input type="text" name="buscar" class="form-control" placeholder="Ej: 131415..." value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label fw-bold text-secondary">Estado del Paquete</label><select name="estado" class="form-select"><option value="">Todos</option><option value="Recibido" <?= (($filtros['estado'] ?? '') == 'Recibido') ? 'selected' : '' ?>>Recibidos (Bodega)</option><option value="En Tránsito" <?= (($filtros['estado'] ?? '') == 'En Tránsito') ? 'selected' : '' ?>>En Tránsito</option><option value="Entregado" <?= (($filtros['estado'] ?? '') == 'Entregado') ? 'selected' : '' ?>>Entregados</option><option value="Anulado" <?= (($filtros['estado'] ?? '') == 'Anulado') ? 'selected' : '' ?>>Anulados</option></select></div>
            <div class="col-md-2"><label class="form-label fw-bold text-secondary">Desde</label><input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($filtros['fecha_inicio'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label fw-bold text-secondary">Hasta</label><input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($filtros['fecha_fin'] ?? '') ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100 fw-bold">🔍 Buscar</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- TABLA PRINCIPAL DE REGISTROS -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0 table-responsive">
        <form action="/suramerica2030/encomiendas/despachar" method="POST">
            <?php if((!isset($filtros['tab']) || $filtros['tab'] == 'activos') && ($puedeDespachar || $puedeRegistrarLlegada)): ?>
            <div class="bg-light p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <?php if($puedeDespachar): ?>
                        <label class="fw-bold text-secondary mb-0">Fecha de Despacho:</label>
                        <input type="date" name="fecha_despacho" class="form-control form-control-sm text-primary fw-bold" style="width: 150px;" value="<?= date('Y-m-d') ?>" required>
                    <?php endif; ?>
                </div>
                
                <!-- BOTONES CONTROLADOS POR PERMISOS DE ROL -->
                <div class="d-flex gap-2">
                    <?php if($puedeDespachar): ?>
                        <button type="submit" formaction="/suramerica2030/encomiendas/despachar" class="btn btn-primary btn-sm fw-bold shadow-sm" onclick="return confirm('¿Despachar los paquetes seleccionados a esta fecha?');">
                            🚚 Despachar (Bodega ➔ Bus)
                        </button>
                    <?php endif; ?>
                    <?php if($puedeRegistrarLlegada): ?>
                        <!-- formnovalidate sirve para saltarse la obligación de poner fecha, ya que el modelo usa la fecha del sistema CURDATE() automáticamente para las entregas -->
                        <button type="submit" formaction="/suramerica2030/encomiendas/entregarMultiples" class="btn btn-success btn-sm fw-bold shadow-sm" onclick="return confirm('¿Marcar paquetes seleccionados como LLEGADOS / ENTREGADOS?');" formnovalidate>
                            ✅ Registrar Llegada / Entrega
                        </button>
                    <?php endif; ?>
                    <a href="/suramerica2030/encomiendas/imprimirEnTransito" target="_blank" class="btn btn-dark btn-sm fw-bold shadow-sm" title="Imprimir manifiesto de todas las encomiendas que están en tránsito actualmente">
                        📋 Manifiesto en Tránsito (<?= $totalEnTransito ?? 0 ?>)
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                        <th>Guía / Fact.</th>
                        <th>Remitente</th>
                        <th>Destinatario</th>
                        <th>Ruta y Detalle</th>
                        <th>Estado</th>
                        <th class="text-center" style="width: 125px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($paquetes)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay encomiendas registradas en esta vista.</td></tr>
                    <?php else: ?>
                        <?php foreach($paquetes as $paquete): ?>
                            <tr class="<?= $paquete['ENC_ESTADO'] == 'Anulado' ? 'table-secondary opacity-75' : '' ?>">
                                <td class="text-center">
                                    <?php 
                                        $permiteSeleccionar = false;
                                        if ($puedeDespachar && $paquete['ENC_ESTADO'] == 'Recibido') $permiteSeleccionar = true;
                                        if ($puedeRegistrarLlegada && $paquete['ENC_ESTADO'] == 'En Tránsito') $permiteSeleccionar = true;
                                        if ($puedeDespachar && $puedeRegistrarLlegada && ($paquete['ENC_ESTADO'] == 'Recibido' || $paquete['ENC_ESTADO'] == 'En Tránsito')) $permiteSeleccionar = true;
                                    ?>
                                    <?php if($permiteSeleccionar): ?>
                                        <input type="checkbox" name="encomiendas_ids[]" value="<?= $paquete['ID_ENCOMIENDA'] ?>" class="form-check-input border-secondary shadow-sm">
                                    <?php else: ?>
                                        <input type="checkbox" disabled class="form-check-input opacity-25">
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold">#<?= str_pad($paquete['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?><br><small class="text-muted"><?= $paquete['FA_FECHA'] ?></small></td>
                                <td><?= htmlspecialchars(($paquete['REM_NOMBRE'] ?? '') . ' ' . ($paquete['REM_APELLIDO'] ?? '')) ?><br><small class="text-muted">CI: <?= htmlspecialchars($paquete['REM_CI'] ?? '') ?></small></td>
                                <td><?= htmlspecialchars(trim(($paquete['DES_NOMBRE'] ?? '') . ' ' . ($paquete['DES_APELLIDO'] ?? ''))) ?: 'S/N' ?><br><small class="text-muted">CI: <?= htmlspecialchars($paquete['DES_CI'] ?? 'S/N') ?></small></td>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($paquete['RUT_ORIGEN']) ?> ➔ <?= htmlspecialchars($paquete['RUT_DESTINO']) ?>
                                        <?= !empty($paquete['DESTINO_FINAL']) ? ' <span class="text-info">➔ '.htmlspecialchars($paquete['DESTINO_FINAL']).'</span>' : '' ?>
                                    </strong><br>
                                    📦 <?= htmlspecialchars($paquete['ENC_CONTENIDO']) ?> 
                                    <span class="badge bg-secondary"><?= $paquete['ENC_CANTIDAD'] ?> <?= htmlspecialchars($paquete['ENC_TIPO']) ?>(s)</span><br>
                                    <small class="text-muted">
                                        <?= $paquete['ENC_PESO'] > 0 ? $paquete['ENC_PESO'].' Kg' : '' ?> 
                                        <?= $paquete['ENC_VOLUMEN'] > 0 ? ' | Vol: '.$paquete['ENC_VOLUMEN'] : '' ?>
                                    </small><br>
                                    <span class="text-success fw-bold">$<?= number_format($paquete['FA_TOTAL'], 2) ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $estado = $paquete['ENC_ESTADO'] ? $paquete['ENC_ESTADO'] : 'Recibido';
                                        if ($estado == 'Recibido') $badge = 'bg-secondary'; elseif ($estado == 'En Tránsito') $badge = 'bg-primary'; elseif ($estado == 'Entregado') $badge = 'bg-success'; elseif ($estado == 'Anulado') $badge = 'bg-danger text-white text-decoration-line-through'; else $badge = 'bg-dark';
                                    ?>
                                    <span class="badge <?= $badge ?> fs-6"><?= $estado ?></span>
                                    <?php if(($paquete['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE' && $estado != 'Anulado'): ?>
                                        <div class="mt-1"><span class="badge bg-warning text-dark border" style="font-size: 11px;">⏳ Anulación Solicitada</span></div>
                                    <?php endif; ?>
                                    <?php if($estado == 'En Tránsito' && !empty($paquete['FECHA_DESPACHO'])): ?><div class="mt-1"><small class="text-primary fw-bold">Sale: <?= $paquete['FECHA_DESPACHO'] ?></small></div><?php endif; ?>
                                    <?php if($estado == 'Entregado' && !empty($paquete['ENC_RETIRO'])): ?><div class="mt-1"><small class="text-success fw-bold">Entregado: <?= $paquete['ENC_RETIRO'] ?></small></div><?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">⚙️ Opciones</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 12px;">
                                            <li>
                                                <a class="dropdown-item fw-bold text-dark" target="_blank" href="/suramerica2030/ventas/ticket/<?= $paquete['ID_FACTURA'] ?>">
                                                    🖨️ Re-imprimir Ticket
                                                </a>
                                            </li>
                                            <?php if($estado == 'Anulado'): ?>
                                                <li><span class="dropdown-item text-muted disabled">❌ Guía Anulada</span></li>
                                            <?php else: ?>
                                                <?php if($puedeDespachar || $puedeRegistrarLlegada): ?>
                                                    <li>
                                                        <a class="dropdown-item fw-bold text-primary" href="#" data-bs-toggle="modal" data-bs-target="#modalEstado<?= $paquete['ID_ENCOMIENDA'] ?>">
                                                            🔄 Cambiar Estado
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                <?php if($estado != 'Anulado'): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <?php if(($paquete['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE'): ?>
                                                        <li><span class="dropdown-item text-warning fw-bold disabled">⏳ Anulación en Revisión</span></li>
                                                    <?php else: ?>
                                                        <?php if((isset($_SESSION['p_anular']) && $_SESSION['p_anular'] == 1) || (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1)): ?>
                                                            <li>
                                                                <a class="dropdown-item fw-bold text-danger" href="#" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $paquete['ID_ENCOMIENDA'] ?>">
                                                                    ❌ Anular Encomienda
                                                                </a>
                                                            </li>
                                                        <?php else: ?>
                                                            <li>
                                                                <a class="dropdown-item fw-bold text-warning" href="#" data-bs-toggle="modal" data-bs-target="#modalSolicitarAnulacionEnc<?= $paquete['ID_ENCOMIENDA'] ?>">
                                                                    📩 Solicitar Anulación
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <?php if($puedeDespachar || $puedeRegistrarLlegada): ?>
                            <div class="modal fade" id="modalEstado<?= $paquete['ID_ENCOMIENDA'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white"><h5 class="modal-title fs-6">Guía #<?= $paquete['ID_FACTURA'] ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id_encomienda_ind" value="<?= $paquete['ID_ENCOMIENDA'] ?>">
                                            <label class="form-label text-muted">Cambiar estado del paquete:</label>
                                            <select name="estado_ind" class="form-select" onchange="window.location.href='/suramerica2030/encomiendas/actualizar?id_encomienda=<?= $paquete['ID_ENCOMIENDA'] ?>&estado='+this.value">
                                                <option value="Recibido" <?= $estado == 'Recibido' ? 'selected' : '' ?>>📦 Recibido en Origen</option>
                                                <?php if($puedeDespachar): ?>
                                                    <option value="En Tránsito" <?= $estado == 'En Tránsito' ? 'selected' : '' ?>>🚚 En Tránsito hacia Destino</option>
                                                <?php endif; ?>
                                                <?php if($puedeRegistrarLlegada): ?>
                                                    <option value="Entregado" <?= $estado == 'Entregado' ? 'selected' : '' ?>>✅ Entregado a Destinatario</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if((isset($_SESSION['p_anular']) && $_SESSION['p_anular'] == 1) || (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1)): ?>
                                <div class="modal fade" id="modalEliminar<?= $paquete['ID_ENCOMIENDA'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white"><h5 class="modal-title fs-6">Anular Guía #<?= $paquete['ID_FACTURA'] ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body text-center">
                                                <p class="mb-2">¿Estás seguro de anular esta encomienda?</p>
                                                <div class="alert alert-warning p-2 mt-2" style="font-size:11px;">Se generará un <strong>EGRESO</strong> de <strong>$<?= number_format($paquete['FA_TOTAL'], 2) ?></strong> en caja.</div>
                                            </div>
                                            <div class="modal-footer p-1">
                                                <button type="button" onclick="document.getElementById('form_anular_<?= $paquete['ID_FACTURA'] ?>').submit();" class="btn btn-danger w-100 fw-bold">Sí, Anular y Reembolsar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- MODAL SOLICITAR ANULACION ENCOMIENDA -->
                                <div class="modal fade text-start" id="modalSolicitarAnulacionEnc<?= $paquete['ID_ENCOMIENDA'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-warning text-dark py-2">
                                                <h6 class="modal-title fs-6 fw-bold">📩 Solicitar Anulación de Guía #<?= $paquete['ID_FACTURA'] ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/encomiendas/solicitarAnulacion" method="POST">
                                                <div class="modal-body p-3">
                                                    <input type="hidden" name="id_factura" value="<?= $paquete['ID_FACTURA'] ?>">
                                                    <div class="bg-light p-2 rounded border mb-2 small">
                                                        <div><strong>Remitente:</strong> <?= htmlspecialchars($paquete['REM_NOMBRE'].' '.$paquete['REM_APELLIDO']) ?></div>
                                                        <div><strong>Destinatario:</strong> <?= htmlspecialchars($paquete['DES_NOMBRE'].' '.$paquete['DES_APELLIDO']) ?></div>
                                                        <div><strong>Total cobrado:</strong> $<?= number_format($paquete['FA_TOTAL'], 2) ?></div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-bold">Monto a reembolsar ($)</label>
                                                        <input type="number" step="0.01" name="monto_devolver" class="form-control fw-bold text-success fs-5" value="<?= $paquete['FA_TOTAL'] ?>" max="<?= $paquete['FA_TOTAL'] ?>" min="0" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-bold">Motivo de la anulación <span class="text-danger">*</span></label>
                                                        <textarea name="motivo" class="form-control" rows="3" required placeholder="Indique claramente por qué se solicita anular esta encomienda..."></textarea>
                                                    </div>
                                                    <div class="alert alert-info py-2 px-3 small mb-0" style="font-size: 11px;">
                                                        ℹ️ Esta solicitud será remitida a Administración para su aprobación.
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2 bg-light">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-warning btn-sm fw-bold">📩 Enviar Solicitud a Administración</button>
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
        </form>

        <?php if(!empty($paquetes) && ((isset($_SESSION['p_anular']) && $_SESSION['p_anular'] == 1) || (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1))): ?>
            <?php foreach($paquetes as $paquete): ?>
                <form id="form_anular_<?= $paquete['ID_FACTURA'] ?>" action="/suramerica2030/encomiendas/eliminar" method="POST" style="display:none;">
                    <input type="hidden" name="id_factura" value="<?= $paquete['ID_FACTURA'] ?>">
                </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- PAGINACIÓN ENCOMIENDAS -->
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

<!-- MODAL: RECIBIR NUEVA ENCOMIENDA -->
<div class="modal fade" id="modalNuevaEncomienda" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Recepcionar Nueva Encomienda</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="/suramerica2030/encomiendas/guardar" method="POST" id="formNuevaEncomienda" onsubmit="return validarEnvioEncomienda(event)">
                <div class="modal-body">
                    <h6 class="text-secondary border-bottom pb-1">1. Remitente (Quien envía)</h6>
                    <div class="row mb-3">
                        <div class="col-md-3"><label>Cédula</label><div class="input-group"><input type="text" name="cliente_id" id="remitente_id" class="form-control" required><span class="input-group-text bg-white">🔍</span></div></div>
                        <div class="col-md-3"><label>Nombres</label><input type="text" name="cliente_nombre" id="remitente_nombre" class="form-control" required></div>
                        <div class="col-md-3"><label>Apellidos</label><input type="text" name="cliente_apellido" id="remitente_apellido" class="form-control" required></div>
                        <div class="col-md-3"><label>Teléfono</label><input type="text" name="cliente_telefono" id="remitente_telefono" class="form-control"></div>
                    </div>

                    <h6 class="text-secondary border-bottom pb-1 mt-3">2. Destinatario (Quien recibe)</h6>
                    <div class="row mb-3">
                        <div class="col-md-3"><label>Cédula / Pasaporte</label><div class="input-group"><input type="text" name="destinatario_ci" id="destinatario_id" class="form-control" required><span class="input-group-text bg-white">🔍</span></div></div>
                        <div class="col-md-3"><label>Nombres</label><input type="text" name="destinatario_nombre" id="destinatario_nombre" class="form-control" required></div>
                        <div class="col-md-3"><label>Apellidos</label><input type="text" name="destinatario_apellido" id="destinatario_apellido" class="form-control" required></div>
                        <div class="col-md-3"><label>Teléfono</label><input type="text" name="destinatario_telefono" id="destinatario_telefono" class="form-control"></div>
                    </div>

                    <h6 class="text-secondary border-bottom pb-1 mt-3">3. Paquete e Itinerario de Encomienda</h6>
                    <div class="row mb-3 g-2">
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-primary">📍</span> Ciudad Origen</label>
                            <select id="sel_origen_enc" class="form-select border-primary" required onchange="alCambiarOrigenEnc()">
                                <option value="">- Seleccione Origen -</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-danger">🏁</span> Ciudad Destino (Agencia)</label>
                            <select id="sel_destino_enc" class="form-select border-primary" required onchange="alCambiarDestinoEnc()" disabled>
                                <option value="">- Elija Origen Primero -</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="text-primary fw-bold">Reexpedición a Otra Ciudad</label>
                            <input type="text" name="destino_final" class="form-control border-secondary text-uppercase" placeholder="Ej: Maturín, Puerto Ordaz...">
                            <small class="text-muted" style="font-size:11px;">Opcional: Si el paquete sigue más allá de la agencia</small>
                        </div>

                        <!-- ID_RUTA que se envía en el POST -->
                        <input type="hidden" name="ruta_id" id="ruta_id" value="" required>

                        <!-- Ficha Resumen de Tarifas de la Ruta Seleccionada -->
                        <div class="col-12 mt-2 d-none" id="info_ruta_enc">
                            <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span id="badge_tipo_enc" class="badge bg-success fs-6">➡️ DIRECTO</span>
                                    <span id="badge_nombre_enc" class="fw-bold text-dark ms-2 small"></span>
                                </div>
                                <div class="small">
                                    <span class="badge bg-primary">Tarifa Kg: $<span id="lbl_precio_kg">0.00</span></span>
                                    <span class="badge bg-info text-dark ms-1">Sobre Fijo: $<span id="lbl_precio_sobre">0.00</span></span>
                                    <span class="badge bg-secondary ms-1">Volumen: $<span id="lbl_precio_vol">0.00</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BARRA DE SELECCIÓN DE MODO: ESTÁNDAR VS MAYORISTA -->
                    <div class="d-flex justify-content-between align-items-center mt-3 mb-2 p-2 rounded bg-light border">
                        <label class="fw-bold text-primary mb-0" style="font-size: 13.5px;">
                            <i class="bi bi-box-seam me-1"></i> Detalle de Piezas / Carga:
                        </label>
                        <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="switchModoMayorista" onchange="alternarModoMayorista(this.checked)" style="cursor: pointer; width: 2.3em; height: 1.25em;">
                            <label class="form-check-label fw-bold text-dark user-select-none" for="switchModoMayorista" style="cursor: pointer; font-size: 12.5px;">
                                🏢 Modo Mayorista / Carga Múltiple <span class="badge bg-warning text-dark ms-1" style="font-size: 10px;">Lotes 20+ cajas</span>
                            </label>
                        </div>
                    </div>

                    <!-- CAMPOS OCULTOS PARA CONTROL DE MODO MAYORISTA -->
                    <input type="hidden" name="modo_mayorista" id="modo_mayorista_input" value="0">
                    <input type="hidden" name="cantidad_lote" id="cantidad_lote_hidden" value="0">
                    <input type="hidden" name="pesos_lote_str" id="pesos_lote_str" value="">
                    <input type="hidden" name="es_peso_general_lote" id="es_peso_general_lote" value="0">

                    <!-- CONTENEDOR 1: MODO ESTÁNDAR (PIEZAS INDIVIDUALES O GRUPO) -->
                    <div id="contenedor_piezas_estandar">
                        <div id="contenedor_piezas" class="mb-2">
                            <div class="row g-2 pieza-row mb-2 align-items-end">
                                <div class="col-md-2">
                                    <label style="font-size:11px;">Cant.</label>
                                    <input type="number" name="cantidad[]" class="form-control form-control-sm" value="1" min="1" required oninput="calcularCobroEncomienda()">
                                </div>
                                <div class="col-md-2">
                                    <label style="font-size:11px;">Tipo</label>
                                    <select name="tipo_encomienda[]" class="form-select form-select-sm" onchange="calcularCobroEncomienda()" required>
                                        <option value="Caja">Caja</option>
                                        <option value="Bulto">Bulto</option>
                                        <option value="Maleta">Maleta</option>
                                        <option value="Sobre">Sobre (Fijo)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex justify-content-between align-items-center mb-0">
                                        <label style="font-size:11px;">Peso (Kg)</label>
                                        <select name="modalidad_peso[]" class="form-select form-select-sm py-0 px-1 border-0 bg-transparent text-primary fw-bold" style="font-size: 10.5px; width: auto; cursor: pointer;" onchange="calcularCobroEncomienda()" title="¿El peso es por cada pieza o el peso total de las piezas de esta fila?">
                                            <option value="unitario">c/u (Unid.)</option>
                                            <option value="total">Total grupo</option>
                                        </select>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" name="peso_pieza[]" class="form-control form-control-sm" value="1.0" min="0" required oninput="calcularCobroEncomienda()">
                                        <span class="input-group-text py-0 px-1 text-muted span-modo-peso" style="font-size: 10px;">c/u</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label style="font-size:11px;">Contenido / Descripción</label>
                                    <input type="text" name="contenido[]" class="form-control form-control-sm" required placeholder="Ej: Ropa, Zapatos...">
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-danger btn-sm" disabled>×</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-3 fw-bold" onclick="agregarPieza()">+ Agregar otra pieza</button>
                    </div>

                    <!-- CONTENEDOR 2: MODO MAYORISTA / CARGA MÚLTIPLE (PESOS VARIABLES) -->
                    <div id="seccion_mayorista" class="p-3 mb-3 rounded border border-warning shadow-sm" style="background-color: #fffdf5; display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div class="fw-bold text-dark" style="font-size: 13.5px;">
                                ⚡ Ingreso Rápido de Lote Mayorista (Pesos Diferentes)
                            </div>
                            <span class="badge bg-warning text-dark" style="font-size: 11px;">
                                Modo Rápido Activado
                            </span>
                        </div>

                        <!-- Fila de configuración del lote -->
                        <div class="row g-2 mb-2 align-items-end">
                            <div class="col-md-3">
                                <label class="fw-bold" style="font-size:11.5px;">Tipo de Bulto</label>
                                <select name="tipo_lote" id="tipo_lote" class="form-select form-select-sm bg-white" disabled>
                                    <option value="Caja" selected>Caja</option>
                                    <option value="Bulto">Bulto</option>
                                    <option value="Maleta">Maleta</option>
                                    <option value="Saco">Saco / Fardo</option>
                                    <option value="Paquete">Paquete</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold" style="font-size:11.5px;">Contenido / Descripción General</label>
                                <input type="text" name="contenido_lote" id="contenido_lote" class="form-control form-control-sm bg-white" placeholder="Ej: Repuestos automotrices, Calzado, Mercadería..." disabled>
                            </div>
                            <div class="col-md-3">
                                <label class="fw-bold text-secondary" style="font-size:11.5px;">Meta de Cajas (Opcional)</label>
                                <input type="number" id="meta_bultos" class="form-control form-control-sm bg-white" placeholder="Ej: 23" min="1" oninput="actualizarResumenMayorista()" disabled>
                            </div>
                        </div>

                        <!-- Panel de ingreso de pesos -->
                        <div class="p-2 mb-2 rounded bg-white border">
                            <div class="row g-2 align-items-center">
                                <!-- Ingreso por báscula / teclado -->
                                <div class="col-md-6">
                                    <label class="fw-bold text-primary d-flex align-items-center gap-1 mb-1" style="font-size:11.5px;">
                                        ⚖️ Captura en Báscula (Escribe y Enter):
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light fw-bold">Kg</span>
                                        <input type="text" id="input_peso_rapido" class="form-control fw-bold text-primary" placeholder="Ej: 14.5 o 15*3" onkeydown="manejarKeydownPesoRapido(event)" disabled>
                                        <button type="button" class="btn btn-primary fw-bold" onclick="agregarPesoRapido()" id="btn_add_peso" disabled>
                                            + Agregar [Enter]
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size:10.5px;">
                                        Tip: Presiona <b>Enter</b> tras cada caja para agregarla en segundos. Permite <code>15*3</code> para 3 cajas de 15 Kg.
                                    </small>
                                </div>

                                <!-- Pegado de lista masiva -->
                                <div class="col-md-6 border-start ps-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="fw-bold text-secondary mb-0" style="font-size:11.5px;">
                                            📋 O Pegar Lista de Pesos:
                                        </label>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-bold" onclick="cargarPesosDesdeTexto()" id="btn_cargar_texto" style="font-size: 11px;" disabled>
                                            ⚡ Cargar
                                        </button>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="input_lista_pesos" class="form-control" placeholder="Ej: 12.5, 14, 10.2, 15, 11.8..." onkeydown="if(event.key==='Enter'){event.preventDefault(); cargarPesosDesdeTexto();}" disabled>
                                        <button type="button" class="btn btn-outline-secondary" onclick="cargarPesosDesdeTexto()" id="btn_cargar_texto_sub" disabled>Cargar</button>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size:10.5px;">
                                        Separa con comas, espacios o pega columnas directo desde Excel o WhatsApp.
                                    </small>
                                </div>
                            </div>

                            <!-- Opción de peso general de lote directo -->
                            <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-dark" style="font-size:11.5px;">⚖️ ¿Pesaron todo el lote junto?</span>
                                    <div class="input-group input-group-sm" style="width: 230px;">
                                        <span class="input-group-text bg-light fw-bold" style="font-size: 11px;">Kg Total</span>
                                        <input type="number" step="0.01" min="0.1" id="input_peso_general_lote" class="form-control" placeholder="Ej: 35.0" disabled>
                                        <button type="button" class="btn btn-outline-primary fw-bold" onclick="aplicarPesoGeneralLote()" id="btn_aplicar_peso_general_lote" disabled style="font-size: 11px;">Fijar Total</button>
                                    </div>
                                </div>
                                <small class="text-muted" style="font-size: 10.5px;">Fija un peso global sin necesidad de anotar caja por caja.</small>
                            </div>
                        </div>

                        <!-- Barra de conteo y botón vaciar -->
                        <div class="d-flex justify-content-between align-items-center mb-1 px-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark" style="font-size: 12px;">Bultos Registrados:</span>
                                <span id="badge_contador_bultos" class="badge bg-secondary">0 cajas</span>
                                <span id="badge_meta_status" class="badge bg-light text-dark border d-none"></span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 11px;" onclick="limpiarTodosLosPesos()" id="btn_limpiar_pesos" disabled>
                                    🗑️ Vaciar lista
                                </button>
                            </div>
                        </div>

                        <!-- Visor de chips con cada peso -->
                        <div id="contenedor_chips_pesos" class="p-2 bg-white rounded border d-flex flex-wrap gap-1 align-content-start" style="min-height: 75px; max-height: 150px; overflow-y: auto;">
                            <div class="text-muted small w-100 text-center py-3 fst-italic" id="placeholder_sin_pesos">
                                Aún no hay pesos registrados. Ingresa el peso de la primera caja arriba o fija un peso total del lote.
                            </div>
                        </div>

                        <!-- Resumen del lote -->
                        <div class="row g-2 mt-1 pt-2 border-top align-items-center text-center">
                            <div class="col-4">
                                <div class="p-1 bg-white rounded border">
                                    <span class="text-muted d-block" style="font-size: 10.5px;">Total Cajas</span>
                                    <span class="fw-bold text-dark fs-6" id="resumen_total_bultos">0</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-1 bg-white rounded border">
                                    <span class="text-muted d-block" style="font-size: 10.5px;">Peso Total</span>
                                    <span class="fw-bold text-primary fs-6"><span id="resumen_peso_acumulado">0.00</span> Kg</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-1 bg-white rounded border">
                                    <span class="text-muted d-block" style="font-size: 10.5px;">Promedio / Caja</span>
                                    <span class="fw-bold text-success fs-6"><span id="resumen_peso_promedio">0.00</span> Kg</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row bg-light p-3 rounded align-items-end border">
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="mb-0 fw-bold" style="font-size: 12px;">Peso Total (Kg)</label>
                                <div class="form-check form-switch mb-0" title="Activa si pesaron todos los paquetes juntos o no tienen el peso individual">
                                    <input class="form-check-input" type="checkbox" id="switchPesoGeneralFactura" onchange="alternarPesoGeneralFactura(this.checked)" style="cursor: pointer;">
                                    <label class="form-check-label text-dark fw-bold user-select-none" for="switchPesoGeneralFactura" style="font-size: 11px; cursor: pointer;">
                                        ⚖️ Peso General
                                    </label>
                                </div>
                            </div>
                            <input type="hidden" name="peso_general_manual" id="peso_general_manual" value="0">
                            <input type="number" step="0.01" name="peso" id="peso" class="form-control fw-bold bg-white" readonly tabindex="-1" oninput="calcularCobroEncomienda()">
                            <small id="lbl_aviso_peso_general" class="text-primary fw-bold d-none" style="font-size: 10px;">
                                💡 Peso global manual activo
                            </small>
                        </div>
                        <div class="col-md-2">
                            <label>Vol. Total</label>
                            <input type="number" step="0.01" name="volumen" id="volumen" class="form-control fw-bold" placeholder="Opcional" oninput="calcularCobroEncomienda()">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">Método de Pago</label>
                            <select name="metodo_pago" id="metodo_pago_enc" class="form-select" required onchange="togglePagoMixtoEnc()">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="TARJETA">Tarjeta (POS)</option>
                                <option value="MIXTO">Mixto (Efectivo + Transferencia)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Total a Cobrar ($)</label>
                            <input type="number" step="0.01" name="monto_total" id="monto_total" class="form-control fw-bold text-success bg-white fs-5" required <?= !$puedeEditarPrecios ? 'readonly' : '' ?> oninput="balancearMixtoEnc('total')">
                            <?php if(!$puedeEditarPrecios): ?>
                                <small class="text-muted d-block" style="font-size:10.5px;">🔒 Tarifa calculada fija (sin permiso de descuento)</small>
                            <?php else: ?>
                                <small class="text-muted d-block" style="font-size:10.5px;">* Editable (permite descuento)</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 mt-2 d-none" id="div_pago_mixto_enc">
                            <div class="p-2 rounded bg-light border border-info shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-primary">💳 Desglose de Pago Mixto</span>
                                    <span class="badge bg-success" id="badge_check_mixto_enc">✔ Cuadrado</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-0">Parte en Efectivo ($)</label>
                                        <input type="number" step="0.01" min="0" name="monto_efectivo" id="monto_efectivo_enc" class="form-control form-control-sm fw-bold text-success" placeholder="0.00" oninput="balancearMixtoEnc('efectivo')">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-0">Parte en Transferencia ($)</label>
                                        <input type="number" step="0.01" min="0" name="monto_transferencia" id="monto_transferencia_enc" class="form-control form-control-sm fw-bold text-primary" placeholder="0.00" oninput="balancearMixtoEnc('transferencia')">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                                    ℹ️ Modifique cualquiera de los dos valores y el otro se balanceará automáticamente.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success w-100 fw-bold fs-5">Cobrar y Generar Guía</button></div>
            </form>
        </div>
    </div>
</div>

<script>
// =============== DATOS MAESTROS DE RUTAS Y SUCURSAL ===============
const TODAS_LAS_RUTAS_ENC = <?= json_encode($rutas) ?>;
const CIUDAD_SUCURSAL_ACTUAL_ENC = <?= json_encode(strtoupper(trim($_SESSION['ciudad_sucursal'] ?? ''))) ?>;

function inicializarOrigenesEnc() {
    let selOrigen = document.getElementById('sel_origen_enc');
    if (!selOrigen) return;
    
    let origenesUnicos = Array.from(new Set(TODAS_LAS_RUTAS_ENC.map(r => r.RUT_ORIGEN))).sort();
    selOrigen.innerHTML = '<option value="">- Seleccione Origen -</option>';
    origenesUnicos.forEach(orig => {
        let opt = document.createElement('option');
        opt.value = orig;
        opt.textContent = '📍 ' + orig;
        selOrigen.appendChild(opt);
    });
    
    // Auto-seleccionar sucursal si coincide
    if (CIUDAD_SUCURSAL_ACTUAL_ENC) {
        let matchSuc = origenesUnicos.find(o => o.toUpperCase() === CIUDAD_SUCURSAL_ACTUAL_ENC);
        if (matchSuc) {
            selOrigen.value = matchSuc;
            alCambiarOrigenEnc();
        }
    }
}
inicializarOrigenesEnc();

function alCambiarOrigenEnc() {
    let origen = document.getElementById('sel_origen_enc').value;
    let selDestino = document.getElementById('sel_destino_enc');
    let rutaIdInput = document.getElementById('ruta_id');
    let infoRuta = document.getElementById('info_ruta_enc');
    
    if (!origen) {
        selDestino.innerHTML = '<option value="">- Elija Origen Primero -</option>';
        selDestino.disabled = true;
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        calcularCobroEncomienda();
        return;
    }
    
    let rutasOrigen = TODAS_LAS_RUTAS_ENC.filter(r => r.RUT_ORIGEN === origen);
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
    calcularCobroEncomienda();
}

function alCambiarDestinoEnc() {
    let origen = document.getElementById('sel_origen_enc').value;
    let destino = document.getElementById('sel_destino_enc').value;
    let rutaIdInput = document.getElementById('ruta_id');
    let infoRuta = document.getElementById('info_ruta_enc');
    
    if (!origen || !destino) {
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        calcularCobroEncomienda();
        return;
    }
    
    let match = TODAS_LAS_RUTAS_ENC.find(r => r.RUT_ORIGEN === origen && r.RUT_DESTINO === destino);
    if (match) {
        rutaIdInput.value = match.ID_RUTA;
        infoRuta.classList.remove('d-none');
        
        let badgeTipo = document.getElementById('badge_tipo_enc');
        if (match.TIPO_VIAJE === 'CONEXION') {
            badgeTipo.className = 'badge bg-warning text-dark fs-6';
            badgeTipo.textContent = '🔄 CONEXIÓN';
        } else {
            badgeTipo.className = 'badge bg-success fs-6';
            badgeTipo.textContent = '➡️ DIRECTO';
        }
        
        document.getElementById('badge_nombre_enc').textContent = match.RUT_ORIGEN + ' ➔ ' + match.RUT_DESTINO;
        document.getElementById('lbl_precio_kg').textContent = parseFloat(match.PRECIO_ENCOMIENDA_KG || 0).toFixed(2);
        document.getElementById('lbl_precio_sobre').textContent = parseFloat(match.PRECIO_SOBRE || 0).toFixed(2);
        document.getElementById('lbl_precio_vol').textContent = parseFloat(match.PRECIO_VOLUMEN || 0).toFixed(2);
        
        calcularCobroEncomienda();
    } else {
        alert("No se encontró una ruta configurada para este trayecto.");
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
    }
}

document.getElementById('selectAll')?.addEventListener('change', function() {
    let checkboxes = document.querySelectorAll('input[name="encomiendas_ids[]"]');
    checkboxes.forEach(cb => {
        if(!cb.disabled) cb.checked = this.checked;
    });
});

document.getElementById('remitente_id').addEventListener('change', function() {
    let id = this.value.trim();
    if (id.length >= 4) {
        fetch('/suramerica2030/encomiendas/buscarCliente?id=' + id).then(r => r.json()).then(res => {
            if (res.success) {
                const nom = document.getElementById('remitente_nombre');
                const ape = document.getElementById('remitente_apellido');
                if (nom) { nom.value = res.data.CLI_NOMBRE || ''; nom.dispatchEvent(new Event('input', { bubbles: true })); }
                if (ape) { ape.value = res.data.CLI_APELLIDO || ''; ape.dispatchEvent(new Event('input', { bubbles: true })); }
                document.getElementById('remitente_telefono').value = res.data.CLI_TELEFONO || '';
            }
        });
    }
});

document.getElementById('destinatario_id').addEventListener('change', function() {
    let id = this.value.trim();
    if (id.length >= 4) {
        fetch('/suramerica2030/encomiendas/buscarCliente?id=' + id).then(r => r.json()).then(res => {
            if (res.success) {
                const nom = document.getElementById('destinatario_nombre');
                const ape = document.getElementById('destinatario_apellido');
                if (nom) { nom.value = res.data.CLI_NOMBRE || ''; nom.dispatchEvent(new Event('input', { bubbles: true })); }
                if (ape) { ape.value = res.data.CLI_APELLIDO || ''; ape.dispatchEvent(new Event('input', { bubbles: true })); }
                document.getElementById('destinatario_telefono').value = res.data.CLI_TELEFONO || '';
            }
        });
    }
});

// =============== GESTIÓN DE MODO MAYORISTA / CARGA MASIVA ===============
let pesosMayorista = [];

function alternarModoMayorista(activado) {
    const contenedorEstandar = document.getElementById('contenedor_piezas_estandar');
    const seccionMayorista = document.getElementById('seccion_mayorista');
    const inputModoMayorista = document.getElementById('modo_mayorista_input');
    
    const inputsEstandar = contenedorEstandar.querySelectorAll('input, select');
    const inputsMayorista = seccionMayorista.querySelectorAll('input, select, button');

    if (activado) {
        inputModoMayorista.value = '1';
        contenedorEstandar.style.display = 'none';
        seccionMayorista.style.display = 'block';

        // Desactivar modo estándar para evitar bloqueos por required ocultos
        inputsEstandar.forEach(el => { el.disabled = true; });

        // Activar modo mayorista
        inputsMayorista.forEach(el => { el.disabled = false; });
        document.getElementById('contenido_lote').required = true;
        
        setTimeout(() => {
            const inputPeso = document.getElementById('input_peso_rapido');
            if (inputPeso) inputPeso.focus();
        }, 120);

        actualizarResumenMayorista();
    } else {
        inputModoMayorista.value = '0';
        seccionMayorista.style.display = 'none';
        contenedorEstandar.style.display = 'block';

        // Reactivar modo estándar
        inputsEstandar.forEach(el => { el.disabled = false; });

        // Desactivar modo mayorista
        inputsMayorista.forEach(el => { el.disabled = true; });
        document.getElementById('contenido_lote').required = false;

        calcularCobroEncomienda();
    }
}

function manejarKeydownPesoRapido(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        agregarPesoRapido();
    }
}

function procesarTextoPesos(texto) {
    if (!texto) return [];
    texto = texto.replace(/[\r\n\t;]/g, ',');
    let partes = texto.split(/[\s,]+/);
    let resultado = [];
    partes.forEach(p => {
        p = p.trim().replace(',', '.');
        if (!p) return;
        if (/^(\d+(?:\.\d+)?)[*xX](\d+)$/.test(p)) {
            let m = p.match(/^(\d+(?:\.\d+)?)[*xX](\d+)$/);
            let val = parseFloat(m[1]);
            let rep = parseInt(m[2]);
            if (val > 0 && rep > 0 && rep <= 100) {
                for (let i = 0; i < rep; i++) resultado.push(val);
            }
        } else if (/^(\d+)[*xX](\d+(?:\.\d+)?)$/.test(p)) {
            let m = p.match(/^(\d+)[*xX](\d+(?:\.\d+)?)$/);
            let rep = parseInt(m[1]);
            let val = parseFloat(m[2]);
            if (val > 0 && rep > 0 && rep <= 100) {
                for (let i = 0; i < rep; i++) resultado.push(val);
            }
        } else {
            let val = parseFloat(p);
            if (!isNaN(val) && val > 0) {
                resultado.push(val);
            }
        }
    });
    return resultado;
}

function agregarPesoRapido() {
    const input = document.getElementById('input_peso_rapido');
    let valStr = input.value.trim();
    if (!valStr) return;

    let parsed = procesarTextoPesos(valStr);
    if (parsed.length > 0) {
        pesosMayorista = pesosMayorista.concat(parsed);
        renderizarChipsPesos();
        actualizarResumenMayorista();
        input.value = '';
    } else {
        alert('Por favor ingresa un peso válido en Kg (ej: 14.5 o 15*3).');
    }
    input.focus();
}

function cargarPesosDesdeTexto() {
    const input = document.getElementById('input_lista_pesos');
    let valStr = input.value.trim();
    if (!valStr) {
        alert('Escribe o pega una lista de pesos (ej: 12.5, 14, 10.2, 15...).');
        input.focus();
        return;
    }

    let parsed = procesarTextoPesos(valStr);
    if (parsed.length > 0) {
        pesosMayorista = pesosMayorista.concat(parsed);
        renderizarChipsPesos();
        actualizarResumenMayorista();
        input.value = '';
    } else {
        alert('No se detectaron pesos válidos en el texto.');
    }
}

function eliminarPesoMayorista(index) {
    if (index >= 0 && index < pesosMayorista.length) {
        pesosMayorista.splice(index, 1);
        renderizarChipsPesos();
        actualizarResumenMayorista();
    }
}

function editarPesoMayorista(index) {
    if (index >= 0 && index < pesosMayorista.length) {
        let actual = pesosMayorista[index];
        let nuevo = prompt('Modificar peso para la Caja #' + (index + 1) + ' (Kg):', actual);
        if (nuevo !== null) {
            let num = parseFloat(nuevo.replace(',', '.'));
            if (!isNaN(num) && num > 0) {
                pesosMayorista[index] = num;
                renderizarChipsPesos();
                actualizarResumenMayorista();
            } else {
                alert('Peso inválido.');
            }
        }
    }
}

function limpiarTodosLosPesos() {
    if (pesosMayorista.length === 0) return;
    if (confirm('¿Estás seguro de que deseas vaciar todos los pesos ingresados?')) {
        pesosMayorista = [];
        renderizarChipsPesos();
        actualizarResumenMayorista();
    }
}

function renderizarChipsPesos() {
    const cont = document.getElementById('contenedor_chips_pesos');
    const btnLimpiar = document.getElementById('btn_limpiar_pesos');

    if (pesosMayorista.length === 0) {
        cont.innerHTML = `
            <div class="text-muted small w-100 text-center py-3 fst-italic" id="placeholder_sin_pesos">
                Aún no hay pesos registrados. Ingresa el peso de la primera caja arriba.
            </div>`;
        if (btnLimpiar) btnLimpiar.disabled = true;
        return;
    }

    if (btnLimpiar) btnLimpiar.disabled = false;
    let html = '';
    pesosMayorista.forEach((p, idx) => {
        html += `
            <div class="badge bg-light text-dark border p-2 d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 11.5px; user-select: none;">
                <span class="text-muted" style="font-size: 10px;">#${idx + 1}</span>
                <strong class="text-primary" role="button" onclick="editarPesoMayorista(${idx})" title="Clic para editar">${p.toFixed(2)} Kg</strong>
                <button type="button" class="btn-close ms-1" style="font-size: 8px;" onclick="eliminarPesoMayorista(${idx})" title="Eliminar"></button>
            </div>`;
    });
    cont.innerHTML = html;
    cont.scrollTop = cont.scrollHeight;
}

function actualizarResumenMayorista() {
    let totalBultos = pesosMayorista.length;
    let pesoTotal = pesosMayorista.reduce((a, b) => a + b, 0);
    let promedio = totalBultos > 0 ? (pesoTotal / totalBultos) : 0;

    const badgeCont = document.getElementById('badge_contador_bultos');
    if (badgeCont) badgeCont.innerText = totalBultos + ' bulto(s)';
    const resBultos = document.getElementById('resumen_total_bultos');
    if (resBultos) resBultos.innerText = totalBultos;
    const resPeso = document.getElementById('resumen_peso_acumulado');
    if (resPeso) resPeso.innerText = pesoTotal.toFixed(2);
    const resProm = document.getElementById('resumen_peso_promedio');
    if (resProm) resProm.innerText = promedio.toFixed(2);

    document.getElementById('cantidad_lote_hidden').value = totalBultos;
    document.getElementById('pesos_lote_str').value = pesosMayorista.map(p => p.toFixed(1)).join(', ');

    // Revisar Meta si está definida
    const inputMeta = document.getElementById('meta_bultos');
    const badgeMeta = document.getElementById('badge_meta_status');
    if (inputMeta && inputMeta.value && parseInt(inputMeta.value) > 0) {
        let meta = parseInt(inputMeta.value);
        badgeMeta.classList.remove('d-none');
        if (totalBultos === meta) {
            badgeMeta.className = 'badge bg-success text-white border';
            badgeMeta.innerHTML = `${totalBultos} / ${meta} ✅ ¡Lote Completo!`;
        } else if (totalBultos < meta) {
            badgeMeta.className = 'badge bg-warning text-dark border';
            badgeMeta.innerHTML = `${totalBultos} / ${meta} (Faltan ${meta - totalBultos})`;
        } else {
            badgeMeta.className = 'badge bg-info text-dark border';
            badgeMeta.innerHTML = `${totalBultos} / ${meta} (+${totalBultos - meta} extras)`;
        }
    } else if (badgeMeta) {
        badgeMeta.classList.add('d-none');
    }

    calcularCobroEncomienda();
}

function validarEnvioEncomienda(e) {
    const switchMayorista = document.getElementById('switchModoMayorista');
    if (switchMayorista && switchMayorista.checked) {
        if (pesosMayorista.length === 0) {
            alert('⚠️ Debes ingresar al menos el peso de una caja para guardar la encomienda mayorista.');
            const inp = document.getElementById('input_peso_rapido');
            if (inp) inp.focus();
            return false;
        }

        const contLote = document.getElementById('contenido_lote');
        if (contLote && !contLote.value.trim()) {
            alert('⚠️ Por favor indica el contenido o descripción general de las cajas.');
            contLote.focus();
            return false;
        }

        const inputMeta = document.getElementById('meta_bultos');
        if (inputMeta && inputMeta.value) {
            let meta = parseInt(inputMeta.value);
            if (meta > 0 && meta !== pesosMayorista.length) {
                if (!confirm(`Indicaste una meta de ${meta} cajas, pero has registrado ${pesosMayorista.length} pesos.\n\n¿Deseas continuar y registrar el envío con ${pesosMayorista.length} cajas?`)) {
                    return false;
                }
            }
        }
    }
    
    const selMetodo = document.getElementById('metodo_pago_enc');
    if (selMetodo && selMetodo.value === 'MIXTO') {
        const total = parseFloat(document.getElementById('monto_total').value) || 0;
        const ef = parseFloat(document.getElementById('monto_efectivo_enc').value) || 0;
        const tr = parseFloat(document.getElementById('monto_transferencia_enc').value) || 0;
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

// =============== GESTIÓN DE PESO GENERAL / GLOBAL ===============
function alternarPesoGeneralFactura(activado) {
    const inputPeso = document.getElementById('peso');
    const inputHidden = document.getElementById('peso_general_manual');
    const lblAviso = document.getElementById('lbl_aviso_peso_general');

    if (activado) {
        inputHidden.value = '1';
        inputPeso.readOnly = false;
        inputPeso.tabIndex = 0;
        inputPeso.classList.remove('bg-white');
        inputPeso.classList.add('border-primary', 'bg-light');
        if (lblAviso) lblAviso.classList.remove('d-none');
        inputPeso.focus();
        inputPeso.select();
    } else {
        inputHidden.value = '0';
        inputPeso.readOnly = true;
        inputPeso.tabIndex = -1;
        inputPeso.classList.add('bg-white');
        inputPeso.classList.remove('border-primary', 'bg-light');
        if (lblAviso) lblAviso.classList.add('d-none');
        calcularCobroEncomienda();
    }
}

function aplicarPesoGeneralLote() {
    let inp = document.getElementById('input_peso_general_lote');
    let val = parseFloat(inp.value);
    if (isNaN(val) || val <= 0) {
        alert('Por favor ingresa un peso total válido en Kg (ej: 35.0).');
        inp.focus();
        return;
    }
    let inputMeta = document.getElementById('meta_bultos');
    let cant = parseInt(inputMeta.value);
    if (isNaN(cant) || cant <= 0) {
        let resp = prompt('¿Cuántas cajas/bultos son en total para este peso de ' + val.toFixed(2) + ' Kg?', '3');
        if (!resp) return;
        cant = parseInt(resp);
        if (isNaN(cant) || cant <= 0) { alert('Cantidad inválida.'); return; }
        inputMeta.value = cant;
    }

    let prom = val / cant;
    pesosMayorista = [];
    for (let i = 0; i < cant; i++) {
        pesosMayorista.push(prom);
    }
    document.getElementById('es_peso_general_lote').value = '1';
    renderizarChipsPesos();
    actualizarResumenMayorista();
    inp.value = '';
}

function agregarPieza() {
    const contenedor = document.getElementById('contenedor_piezas');
    const fila = document.createElement('div');
    fila.className = 'row g-2 pieza-row mb-2 align-items-end';
    fila.innerHTML = `
        <div class="col-md-2">
            <input type="number" name="cantidad[]" class="form-control form-control-sm" value="1" min="1" required oninput="calcularCobroEncomienda()">
        </div>
        <div class="col-md-2">
            <select name="tipo_encomienda[]" class="form-select form-select-sm" onchange="calcularCobroEncomienda()" required>
                <option value="Caja">Caja</option>
                <option value="Bulto">Bulto</option>
                <option value="Maleta">Maleta</option>
                <option value="Sobre">Sobre (Fijo)</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="d-flex justify-content-between align-items-center mb-0">
                <label style="font-size:10px;" class="text-muted">Modalidad:</label>
                <select name="modalidad_peso[]" class="form-select form-select-sm py-0 px-1 border-0 bg-transparent text-primary fw-bold" style="font-size: 10.5px; width: auto; cursor: pointer;" onchange="calcularCobroEncomienda()" title="¿El peso es por cada pieza o el peso total de estas piezas?">
                    <option value="unitario">c/u (Unid.)</option>
                    <option value="total">Total grupo</option>
                </select>
            </div>
            <div class="input-group input-group-sm">
                <input type="number" step="0.01" name="peso_pieza[]" class="form-control form-control-sm" value="1.0" min="0" required oninput="calcularCobroEncomienda()">
                <span class="input-group-text py-0 px-1 text-muted span-modo-peso" style="font-size: 10px;">c/u</span>
            </div>
        </div>
        <div class="col-md-4">
            <input type="text" name="contenido[]" class="form-control form-control-sm" required placeholder="Ej: Ropa, Zapatos...">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.pieza-row').remove(); calcularCobroEncomienda();">×</button>
        </div>
    `;
    contenedor.appendChild(fila);
    calcularCobroEncomienda();
}

function calcularCobroEncomienda() {
    let esMayorista = document.getElementById('switchModoMayorista') && document.getElementById('switchModoMayorista').checked;
    let esPesoGeneralManual = document.getElementById('switchPesoGeneralFactura') && document.getElementById('switchPesoGeneralFactura').checked;
    let rutaId = document.getElementById('ruta_id').value;
    let ruta = TODAS_LAS_RUTAS_ENC.find(r => r.ID_RUTA == rutaId);
    let inputPesoTotal = document.getElementById('peso');
    let inputVolumen = document.getElementById('volumen');

    let precioKg = parseFloat(ruta ? ruta.PRECIO_ENCOMIENDA_KG : 0) || 0;
    let precioSobre = parseFloat(ruta ? ruta.PRECIO_SOBRE : 0) || 0;
    let precioVol = parseFloat(ruta ? ruta.PRECIO_VOLUMEN : 0) || 0;

    let totalPesoCalc = 0;
    let totalSobres = 0;
    let soloSobres = true;

    if (esPesoGeneralManual) {
        soloSobres = false;
        totalPesoCalc = parseFloat(inputPesoTotal.value) || 0;
    } else if (esMayorista) {
        soloSobres = false;
        totalPesoCalc = pesosMayorista.reduce((a, b) => a + b, 0);
        inputPesoTotal.value = totalPesoCalc.toFixed(2);
    } else {
        let filas = document.querySelectorAll('#contenedor_piezas .pieza-row');
        filas.forEach(fila => {
            let cant = parseInt(fila.querySelector('input[name="cantidad[]"]').value) || 1;
            let tipo = fila.querySelector('select[name="tipo_encomienda[]"]').value;
            let pesoP = parseFloat(fila.querySelector('input[name="peso_pieza[]"]').value) || 0;
            let selModo = fila.querySelector('select[name="modalidad_peso[]"]');
            let modo = selModo ? selModo.value : 'unitario';
            let spanModo = fila.querySelector('.span-modo-peso');

            if (tipo === 'Sobre') {
                totalSobres += (cant * precioSobre);
                if (spanModo) spanModo.innerText = 'Fijo';
            } else {
                soloSobres = false;
                if (modo === 'total') {
                    totalPesoCalc += pesoP;
                    if (spanModo) {
                        let prom = cant > 0 ? (pesoP / cant).toFixed(1) : pesoP;
                        spanModo.innerText = `Lote (~${prom} c/u)`;
                    }
                } else {
                    totalPesoCalc += (cant * pesoP);
                    if (spanModo) spanModo.innerText = 'c/u';
                }
            }
        });
        inputPesoTotal.value = totalPesoCalc.toFixed(2);
    }

    let volumen = parseFloat(inputVolumen.value) || 0;
    let costoBaseTotal = Math.max(totalPesoCalc * precioKg, volumen * precioVol);

    if (soloSobres && !esMayorista && !esPesoGeneralManual) {
        inputVolumen.disabled = true;
        document.getElementById('monto_total').value = totalSobres.toFixed(2);
    } else {
        inputVolumen.disabled = false;
        document.getElementById('monto_total').value = (costoBaseTotal + totalSobres).toFixed(2);
    }
    balancearMixtoEnc('total');
}

const modalNuevaEnc = document.getElementById('modalNuevaEncomienda');
if (modalNuevaEnc) {
    modalNuevaEnc.addEventListener('show.bs.modal', function () {
        const selOrigen = document.getElementById('sel_origen_enc');
        if (selOrigen && selOrigen.options.length <= 1) {
            inicializarOrigenesEnc();
        }
    });

    modalNuevaEnc.addEventListener('hidden.bs.modal', function () {
        const sw = document.getElementById('switchModoMayorista');
        if (sw && sw.checked) {
            sw.checked = false;
            alternarModoMayorista(false);
        }
        const swGen = document.getElementById('switchPesoGeneralFactura');
        if (swGen && swGen.checked) {
            swGen.checked = false;
            alternarPesoGeneralFactura(false);
        }
        pesosMayorista = [];
        renderizarChipsPesos();
        const metaB = document.getElementById('meta_bultos'); if (metaB) metaB.value = '';
        const inRap = document.getElementById('input_peso_rapido'); if (inRap) inRap.value = '';
        const inTxt = document.getElementById('input_lista_pesos'); if (inTxt) inTxt.value = '';
        const inLoteGen = document.getElementById('input_peso_general_lote'); if (inLoteGen) inLoteGen.value = '';
        const esGenLote = document.getElementById('es_peso_general_lote'); if (esGenLote) esGenLote.value = '0';
    });
}

function filtrarPersonal(modalId) {
    let ciudad = document.getElementById('suc_' + modalId).value;
    let selectPersonal = document.getElementById('per_' + modalId);
    let opciones = selectPersonal.querySelectorAll('option[data-sucursal]');
    
    selectPersonal.value = '';
    document.getElementById('correo_destino_' + modalId).value = '';
    
    opciones.forEach(opt => {
        if (ciudad === '' || opt.getAttribute('data-sucursal') === ciudad) {
            opt.style.display = '';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    });
}

// ========================================================
// GESTIÓN DE SELECCIÓN Y UNIFICACIÓN DE MANIFIESTOS DE CARGA
// ========================================================
function actualizarSeleccionDespachos() {
    const checkboxes = document.querySelectorAll('.chk-despacho-item:checked');
    const toolbar = document.getElementById('toolbarDespachos');
    const badge = document.getElementById('badgeDespachosCount');
    const selectAll = document.getElementById('chkSelectAllDespachos');
    
    let totalPaquetes = 0;
    checkboxes.forEach(chk => {
        totalPaquetes += parseInt(chk.dataset.paquetes || 0, 10);
    });

    if (checkboxes.length > 0) {
        if (toolbar) {
            toolbar.classList.remove('d-none');
            toolbar.classList.add('d-flex');
        }
        if (badge) {
            badge.textContent = checkboxes.length + ' seleccionado' + (checkboxes.length > 1 ? 's' : '') + ' (' + totalPaquetes + ' paq.)';
        }
    } else {
        if (toolbar) {
            toolbar.classList.add('d-none');
            toolbar.classList.remove('d-flex');
        }
    }

    const totalCheckboxes = document.querySelectorAll('.chk-despacho-item').length;
    if (selectAll) {
        selectAll.checked = (totalCheckboxes > 0 && checkboxes.length === totalCheckboxes);
    }
}

function toggleSelectAllDespachos(master) {
    const items = document.querySelectorAll('.chk-despacho-item');
    items.forEach(chk => {
        chk.checked = master.checked;
    });
    actualizarSeleccionDespachos();
}

function abrirModalUnificarDespachos() {
    const checkboxes = document.querySelectorAll('.chk-despacho-item:checked');
    if (checkboxes.length < 2) {
        alert("Por favor selecciona al menos 2 despachos en la tabla para poder integrarlos en uno solo.");
        return;
    }

    const tbody = document.querySelector('#tablaDespachosAUnificar tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    const hiddenCont = document.getElementById('hiddenInputsUnificar');
    if (hiddenCont) hiddenCont.innerHTML = '';

    let totalPaquetes = 0;
    let primeraRutaId = null;
    let primeraFecha = null;
    let primerDestino = null;

    checkboxes.forEach(chk => {
        const val = chk.value;
        const fecha = chk.dataset.fecha;
        const rutaId = chk.dataset.rutaId;
        const origen = chk.dataset.origen;
        const destino = chk.dataset.destino;
        const paquetes = parseInt(chk.dataset.paquetes || 0, 10);

        totalPaquetes += paquetes;
        if (!primeraRutaId) {
            primeraRutaId = rutaId;
            primeraFecha = fecha;
            primerDestino = destino;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>📅 ${fecha}</td>
            <td class="fw-bold">${origen} ➔ ${destino}</td>
            <td class="text-center"><span class="badge bg-secondary fs-6">${paquetes}</span></td>
        `;
        tbody.appendChild(tr);

        if (hiddenCont) {
            const inputHidden = document.createElement('input');
            inputHidden.type = 'hidden';
            inputHidden.name = 'despachos_a_unificar[]';
            inputHidden.value = val;
            hiddenCont.appendChild(inputHidden);
        }
    });

    const lblTotal = document.getElementById('totalPaquetesUnificar');
    if (lblTotal) lblTotal.textContent = totalPaquetes;
    
    const txtFecha = document.getElementById('txtFechaDestinoUnificada');
    if (txtFecha) txtFecha.value = primeraFecha || '';

    const selRuta = document.getElementById('selRutaDestinoUnificada');
    if (selRuta && primeraRutaId) {
        selRuta.value = primeraRutaId;
    }

    const modalEl = document.getElementById('modalUnificarDespachos');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function imprimirDespachosConsolidados() {
    const checkboxes = document.querySelectorAll('.chk-despacho-item:checked');
    if (checkboxes.length === 0) {
        alert("Selecciona al menos un despacho para imprimir.");
        return;
    }

    const vals = Array.from(checkboxes).map(c => c.value);
    const primeraRutaId = checkboxes[0].dataset.rutaId || '';
    const primeraFecha = checkboxes[0].dataset.fecha || '';

    const url = `/suramerica2030/encomiendas/imprimirLista?consolidado=1&despachos=${encodeURIComponent(vals.join(','))}&ruta_id=${primeraRutaId}&fecha=${encodeURIComponent(primeraFecha)}`;
    window.open(url, '_blank');
}

function abrirModalCorreoConsolidado() {
    const checkboxes = document.querySelectorAll('.chk-despacho-item:checked');
    if (checkboxes.length === 0) {
        alert("Selecciona al menos un despacho para enviar por correo.");
        return;
    }

    const hiddenCont = document.getElementById('hiddenInputsCorreoConsolidado');
    if (hiddenCont) {
        hiddenCont.innerHTML = '';
        checkboxes.forEach(chk => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'despachos_consolidados[]';
            input.value = chk.value;
            hiddenCont.appendChild(input);
        });
    }

    const rInput = document.getElementById('correoConsolidadoRutaId');
    if (rInput) rInput.value = checkboxes[0].dataset.rutaId || '';
    
    const fInput = document.getElementById('correoConsolidadoFecha');
    if (fInput) fInput.value = checkboxes[0].dataset.fecha || '';
    
    const lblCount = document.getElementById('lblCantDespachosCorreo');
    if (lblCount) lblCount.textContent = checkboxes.length;

    const modalEl = document.getElementById('modalCorreoConsolidado');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function filtrarPersonalConsolidado() {
    const sucursal = (document.getElementById('suc_consolidado').value || '').toLowerCase();
    const selectPersonal = document.getElementById('per_consolidado');
    const options = selectPersonal.querySelectorAll('option');

    options.forEach(opt => {
        if (!opt.value) return;
        const optSuc = (opt.getAttribute('data-sucursal') || '').toLowerCase();
        if (!sucursal || optSuc === sucursal) {
            opt.style.display = '';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    });
    selectPersonal.value = '';
    document.getElementById('correo_destino_consolidado').value = '';
}

document.addEventListener('DOMContentLoaded', function() {
    inicializarOrigenesEnc();
});

// =============== FUNCIONES DE PAGO MIXTO (ENCOMIENDAS) ===============
function togglePagoMixtoEnc() {
    const sel = document.getElementById('metodo_pago_enc');
    const div = document.getElementById('div_pago_mixto_enc');
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoEnc('init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoEnc(origen) {
    const sel = document.getElementById('metodo_pago_enc');
    if (!sel || sel.value !== 'MIXTO') return;
    const totalEl = document.getElementById('monto_total');
    const efEl = document.getElementById('monto_efectivo_enc');
    const trEl = document.getElementById('monto_transferencia_enc');
    const badge = document.getElementById('badge_check_mixto_enc');
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
</script>