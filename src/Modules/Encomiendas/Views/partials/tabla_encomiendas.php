<!-- TABLA PRINCIPAL DE REGISTROS -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0 table-responsive">
        <form action="/suramerica2030/encomiendas/despachar" method="POST">
            <?php if((!isset($filtros['tab']) || $filtros['tab'] == 'activos') && ($puedeDespachar || $puedeRegistrarLlegada)): ?>
            <div class="bg-light p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <?php if($puedeDespachar): ?>
                        <label class="fw-bold text-secondary mb-0 small text-uppercase">Fecha Despacho:</label>
                        <input type="date" name="fecha_despacho" class="form-control form-control-sm text-primary fw-bold" style="width: 145px;" value="<?= date('Y-m-d') ?>" required>
                    <?php endif; ?>
                </div>
                
                <!-- BOTONES CONTROLADOS POR PERMISOS DE ROL -->
                <div class="d-flex gap-2 flex-wrap">
                    <?php if($puedeDespachar): ?>
                        <button type="submit" formaction="/suramerica2030/encomiendas/despachar" class="btn btn-primary btn-sm fw-bold shadow-sm" onclick="return confirm('¿Despachar los paquetes seleccionados a esta fecha? Pasarán a estado: En Tránsito.');">
                            🚚 Despachar (Bodega ➔ Bus)
                        </button>
                    <?php endif; ?>
                    <?php if($puedeRegistrarLlegada): ?>
                        <button type="submit" formaction="/suramerica2030/encomiendas/llegadaOficina" class="btn btn-info btn-sm fw-bold shadow-sm text-dark" onclick="return confirm('¿Marcar paquetes seleccionados como LLEGADOS A OFICINA (Disponibles para retiro)?');" formnovalidate>
                            🏢 Llegó a Oficina
                        </button>
                        <button type="submit" formaction="/suramerica2030/encomiendas/entregarMultiples" class="btn btn-success btn-sm fw-bold shadow-sm" onclick="return confirm('¿Marcar paquetes seleccionados como ENTREGADOS AL CLIENTE?');" formnovalidate>
                            ✅ Entregar al Cliente
                        </button>
                    <?php endif; ?>
                    <a href="/suramerica2030/encomiendas/imprimirEnTransito" target="_blank" class="btn btn-dark btn-sm fw-bold shadow-sm" title="Imprimir manifiesto de todas las encomiendas que están en tránsito actualmente">
                        📋 Manifiesto en Tránsito (<?= $kpis['total_en_transito'] ?? ($totalEnTransito ?? 0) ?>)
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
                                        if ($puedeRegistrarLlegada && in_array($paquete['ENC_ESTADO'], ['En Tránsito', 'En Oficina'])) $permiteSeleccionar = true;
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
                                        if ($estado == 'Recibido') {
                                            $badge = 'bg-secondary text-white';
                                            $badgeIcon = '🏬';
                                        } elseif ($estado == 'En Tránsito') {
                                            $badge = 'bg-primary text-white';
                                            $badgeIcon = '🚚';
                                        } elseif ($estado == 'En Oficina') {
                                            $badge = 'bg-info text-dark';
                                            $badgeIcon = '🏢';
                                        } elseif ($estado == 'Entregado') {
                                            $badge = 'bg-success text-white';
                                            $badgeIcon = '✅';
                                        } elseif ($estado == 'Anulado') {
                                            $badge = 'bg-danger text-white text-decoration-line-through';
                                            $badgeIcon = '🛑';
                                        } else {
                                            $badge = 'bg-dark text-white';
                                            $badgeIcon = '📦';
                                        }
                                    ?>
                                    <span class="badge <?= $badge ?> fs-6"><?= $badgeIcon ?> <?= $estado ?></span>
                                    <?php if(($paquete['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE' && $estado != 'Anulado'): ?>
                                        <div class="mt-1"><span class="badge bg-warning text-dark border" style="font-size: 11px;">⏳ Anulación Solicitada</span></div>
                                    <?php endif; ?>
                                    <?php if($estado == 'En Tránsito' && !empty($paquete['FECHA_DESPACHO'])): ?>
                                        <div class="mt-1"><small class="text-primary fw-bold">🚚 Despacho: <?= $paquete['FECHA_DESPACHO'] ?></small></div>
                                    <?php endif; ?>
                                    <?php if($estado == 'En Oficina' && !empty($paquete['ENC_LLEGADA'])): ?>
                                        <div class="mt-1"><small class="text-info fw-bold" style="color: #055160 !important;">🏢 Llegó: <?= $paquete['ENC_LLEGADA'] ?></small></div>
                                    <?php endif; ?>
                                    <?php if($estado == 'Entregado' && !empty($paquete['ENC_RETIRO'])): ?>
                                        <div class="mt-1"><small class="text-success fw-bold">✅ Retirado: <?= $paquete['ENC_RETIRO'] ?></small></div>
                                    <?php endif; ?>
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
                                                <?php if($puedeRegistrarLlegada && $estado == 'En Tránsito'): ?>
                                                    <li>
                                                        <a class="dropdown-item fw-bold text-info" href="/suramerica2030/encomiendas/actualizar?id_encomienda=<?= $paquete['ID_ENCOMIENDA'] ?>&estado=En+Oficina" onclick="return confirm('¿Marcar guía #<?= $paquete['ID_FACTURA'] ?> como LLEGADA A OFICINA?');">
                                                            🏢 Llegó a Oficina
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                <?php if($puedeRegistrarLlegada && ($estado == 'En Tránsito' || $estado == 'En Oficina')): ?>
                                                    <li>
                                                        <a class="dropdown-item fw-bold text-success" href="/suramerica2030/encomiendas/actualizar?id_encomienda=<?= $paquete['ID_ENCOMIENDA'] ?>&estado=Entregado" onclick="return confirm('¿Confirmar entrega al cliente de guía #<?= $paquete['ID_FACTURA'] ?>?');">
                                                            ✅ Entregar al Cliente
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                <?php if($puedeDespachar || $puedeRegistrarLlegada): ?>
                                                    <li>
                                                        <a class="dropdown-item fw-bold text-primary" href="#" data-bs-toggle="modal" data-bs-target="#modalEstado<?= $paquete['ID_ENCOMIENDA'] ?>">
                                                            🔄 Cambiar Estado
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
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
                                            <label class="form-label text-muted small fw-bold">Cambiar estado del paquete:</label>
                                            <select name="estado_ind" class="form-select" onchange="window.location.href='/suramerica2030/encomiendas/actualizar?id_encomienda=<?= $paquete['ID_ENCOMIENDA'] ?>&estado='+encodeURIComponent(this.value)">
                                                <option value="Recibido" <?= $estado == 'Recibido' ? 'selected' : '' ?>>🏬 Recibido en Origen (Bodega)</option>
                                                <?php if($puedeDespachar): ?>
                                                    <option value="En Tránsito" <?= $estado == 'En Tránsito' ? 'selected' : '' ?>>🚚 En Tránsito hacia Destino</option>
                                                <?php endif; ?>
                                                <?php if($puedeRegistrarLlegada): ?>
                                                    <option value="En Oficina" <?= $estado == 'En Oficina' ? 'selected' : '' ?>>🏢 Llegó a Oficina (Listo para Retiro)</option>
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
