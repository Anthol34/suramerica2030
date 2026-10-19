<?php if (!isset($filtros['tab']) || $filtros['tab'] === 'activos'): ?>
    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; background: #ffffff;">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="/suramerica2030/pasajes" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="activos">
                <?php if(!empty($filtroRapido)): ?>
                    <input type="hidden" name="filtro_rapido" value="<?= htmlspecialchars($filtroRapido) ?>">
                <?php endif; ?>
                <div class="col-md-7 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">🔍</span>
                        <input type="text" name="buscar" class="form-control border-start-0" placeholder="Buscar pasajero activo por Nombre, CI, Factura o Asiento..." value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
                        <button type="submit" class="btn btn-primary fw-bold px-3">Buscar</button>
                        <?php if(!empty($filtros['buscar'])): ?>
                            <a href="?tab=activos<?= !empty($filtroRapido) ? '&filtro_rapido='.urlencode($filtroRapido) : '' ?>" class="btn btn-outline-secondary" title="Limpiar búsqueda">✕</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-5 col-12 d-flex justify-content-md-end align-items-center gap-2">
                    <?php if(!empty($filtroRapido) || !empty($filtros['buscar'])): ?>
                        <div class="d-flex align-items-center gap-1">
                            <?php if(!empty($filtroRapido)): ?>
                                <?php 
                                $nombresRapidos = [
                                    'hoy' => '🚌 Viajan Hoy',
                                    'proximos_7' => '📅 Próx. 7 Días',
                                    'abiertas' => '⏳ Fechas Abiertas',
                                    'reservas' => '⚠️ Con Saldo / Reservas'
                                ];
                                ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    <?= $nombresRapidos[$filtroRapido] ?? $filtroRapido ?>
                                </span>
                            <?php endif; ?>
                            <?php if(!empty($filtros['buscar'])): ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-1 px-2">
                                    "<?= htmlspecialchars($filtros['buscar']) ?>"
                                </span>
                            <?php endif; ?>
                            <a href="?tab=activos" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill fw-bold" title="Quitar todos los filtros">✖ Limpiar</a>
                        </div>
                    <?php else: ?>
                        <span class="text-muted small fw-bold">
                            Total <?= $totalRegistros ?? count($pasajes) ?> viaje(s) activo(s)
                        </span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Pasajero</th>
                <th>Ruta y Viaje</th>
                <th>Estado Financiero</th>
                <th>Equipaje</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($pasajes)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No hay pasajes registrados en esta vista.</td></tr>
            <?php else: ?>
                <?php foreach($pasajes as $p): ?>
                    <tr class="<?= $p['ESTADO'] == 'DEVUELTO' ? 'table-secondary opacity-75' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></strong><br>
                            <small class="text-muted">CI: <?= htmlspecialchars($p['ID_CLIENTE']) ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?></strong><br>
                            <?php if($p['ESTADO'] == 'DEVUELTO'): ?>
                                <span class="badge bg-secondary text-decoration-line-through">Cancelado</span>
                            <?php elseif($p['FECHA_VIAJE']): ?>
                                <?php 
                                $diasSemanaNom = [0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb'];
                                $w = (int)date('w', strtotime($p['FECHA_VIAJE']));
                                $diaNom = $diasSemanaNom[$w] ?? '';
                                ?>
                                <span class="badge bg-light text-dark border">
                                    📅 <strong class="text-primary"><?= $diaNom ?></strong>, <?= date('d/m/Y', strtotime($p['FECHA_VIAJE'])) ?> | Asiento: <strong><?= $p['PAX_ASIENTO'] ?></strong>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">⏳ FECHA ABIERTA</span>
                            <?php endif; ?>
                            <?php if(!empty($p['FA_FECHA'])): ?>
                                <br><small class="text-muted" style="font-size:11px;">🧾 Fac #<?= $p['ID_FACTURA'] ?> • <?= date('d/m/Y', strtotime($p['FA_FECHA'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($p['ESTADO'] == 'DEVUELTO'): ?>
                                <span class="badge bg-dark">❌ DEVUELTO</span><br>
                                <small class="text-muted" style="font-size:10px;"><?= htmlspecialchars($p['OBSERVACION']) ?></small>
                            <?php elseif($p['ESTADO'] == 'PAGADO'): ?>
                                <span class="badge bg-success">✅ PAGADO</span>
                                <a href="#" class="text-decoration-none text-muted ms-1" title="Actualizar Estado Financiero" data-bs-toggle="modal" data-bs-target="#modalFinanciero<?= $p['ID_PASAJE'] ?>"><span style="font-size:12px; cursor:pointer;">✏️</span></a><br>
                                <small class="text-muted">Total: $<?= number_format($p['PRECIO_TOTAL'], 2) ?></small>
                            <?php else: ?>
                                <span class="badge bg-danger">⚠️ RESERVA</span>
                                <a href="#" class="text-decoration-none text-muted ms-1" title="Actualizar Estado Financiero" data-bs-toggle="modal" data-bs-target="#modalFinanciero<?= $p['ID_PASAJE'] ?>"><span style="font-size:12px; cursor:pointer;">✏️</span></a><br>
                                <small class="fw-bold text-danger">Saldo: $<?= number_format($p['SALDO'], 2) ?></small>
                            <?php endif; ?>

                            <?php if(($p['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE'): ?>
                                <br><span class="badge bg-warning text-dark border mt-1 shadow-sm" title="Solicitado por <?= htmlspecialchars($p['SOLICITUD_USUARIO_NOMBRE'] ?? 'Cajero') ?>: <?= htmlspecialchars($p['SOLICITUD_MOTIVO'] ?? '') ?>">⏳ Anulación Solicitada</span>
                            <?php elseif(($p['SOLICITUD_ESTADO'] ?? '') === 'RECHAZADA' && $p['ESTADO'] != 'DEVUELTO'): ?>
                                <br><span class="badge bg-light text-danger border mt-1" title="Rechazado: <?= htmlspecialchars($p['SOLICITUD_MOTIVO_RESPUESTA'] ?? '') ?>">🚫 Anulación Denegada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($p['ESTADO'] != 'DEVUELTO'): ?>
                                <?php $totalPeso = ($p['PESO_MANO'] ?? 0) + ($p['PESO_BODEGA'] ?? 0); ?>
                                <?php if($totalPeso > 0): ?>
                                    <span class="badge <?= $totalPeso > ($p['LIMITE_EQUIPAJE_KG'] ?? 35) ? 'bg-danger' : 'bg-primary' ?>"><?= number_format($totalPeso, 1) ?> Kg</span>
                                    <?php 
                                    $numPz = 0;
                                    if (!empty($p['EQUIPAJE_DETALLE'])) {
                                        $d = json_decode($p['EQUIPAJE_DETALLE'], true);
                                        if (is_array($d)) $numPz = count($d);
                                    }
                                    if ($numPz > 0): ?>
                                        <br><small class="text-muted fw-bold" style="font-size:10px;"><?= $numPz ?> pieza(s)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sin Pesar</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($p['ESTADO'] != 'DEVUELTO'): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">⚙️ Opciones</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item fw-bold text-dark border-bottom mb-1" target="_blank" href="/suramerica2030/ventas/ticket/<?= $p['ID_FACTURA'] ?>">🖨️ Re-imprimir Ticket</a></li>
                                        
                                        <?php if(!$p['FECHA_VIAJE']): ?>
                                            <li><a class="dropdown-item fw-bold text-info" href="#" data-bs-toggle="modal" data-bs-target="#modalAsignar<?= $p['ID_PASAJE'] ?>">📅 Asignar Fecha/Bus</a></li>
                                        <?php else: ?>
                                            <li><a class="dropdown-item" href="#" onclick="abrirMapaParaReasignar(<?= $p['ID_PASAJE'] ?>, '<?= $p['FECHA_VIAJE'] ?>', <?= $p['ID_RUTA'] ?? 0 ?>, <?= $p['ID_BUS'] ?? 'null' ?>, <?= $p['PAX_ASIENTO'] ?? 'null' ?>, <?= $p['BUS_ASIENTOS'] ?? 42 ?>)">🔄 Reasignar Asiento</a></li>
                                        <?php endif; ?>
                                        <li><a class="dropdown-item <?= $p['ESTADO'] == 'PAGADO' ? 'disabled' : 'text-success' ?>" href="#" data-bs-toggle="modal" data-bs-target="#modalAbono<?= $p['ID_PASAJE'] ?>">💵 Registrar Abono</a></li>
                                        <li><a class="dropdown-item fw-bold" style="color:#0b2b56;" href="#" data-bs-toggle="modal" data-bs-target="#modalEquipaje<?= $p['ID_PASAJE'] ?>">⚖️ Control Equipaje</a></li>
                                        <li><a class="dropdown-item fw-bold text-primary" href="#" data-bs-toggle="modal" data-bs-target="#modalFinanciero<?= $p['ID_PASAJE'] ?>">💳 Actualizar Financiero</a></li>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        <?php if(($p['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE'): ?>
                                            <?php if($puedeAnularDirecto): ?>
                                                <li><a class="dropdown-item text-primary fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalResolverSolicitud<?= $p['ID_PASAJE'] ?>">🔔 Resolver Solicitud Anulación</a></li>
                                            <?php else: ?>
                                                <li><span class="dropdown-item text-warning fw-bold disabled">⏳ Anulación en Revisión</span></li>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if($puedeAnularDirecto): ?>
                                                <li><a class="dropdown-item text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalDevolucion<?= $p['ID_PASAJE'] ?>">❌ Devolución Directa / Cancelar</a></li>
                                            <?php else: ?>
                                                <li><a class="dropdown-item text-warning fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalSolicitarDevolucion<?= $p['ID_PASAJE'] ?>">📩 Solicitar Anulación</a></li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <?php $pagado = $p['PRECIO_TOTAL'] - $p['SALDO']; ?>

                                <!-- 1. MODAL DE DEVOLUCIÓN DIRECTA (ADMIN O CON PERMISO P_ANULAR) -->
                                <?php if($puedeAnularDirecto): ?>
                                <div class="modal fade text-start" id="modalDevolucion<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h6 class="modal-title">Cancelar y Devolver</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/devolver" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <p class="mb-2 text-muted" style="font-size:12px;">El cliente ha pagado un total de <strong>$<?= number_format($pagado, 2) ?></strong> hasta ahora.</p>
                                                    <div class="mb-2"><label>Monto a Reembolsar ($)</label><input type="number" step="0.01" name="monto_devolver" class="form-control" value="<?= $pagado ?>" max="<?= $pagado ?>" required></div>
                                                    <div class="mb-2"><label>Motivo de cancelación</label><input type="text" name="motivo" class="form-control" required placeholder="Ej: Cliente no llegó, Cambio de planes..."></div>
                                                    <div class="alert alert-warning p-2" style="font-size: 11px;">Al confirmar, el asiento quedará libre y el monto se descontará de la caja.</div>
                                                </div>
                                                <div class="modal-footer p-1"><button type="submit" class="btn btn-danger w-100 fw-bold">Confirmar Devolución</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- 2. MODAL DE SOLICITAR ANULACIÓN (PARA CAJEROS/USUARIOS SIN PERMISO DIRECTO) -->
                                <div class="modal fade text-start" id="modalSolicitarDevolucion<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-warning text-dark py-2">
                                                <h6 class="modal-title fw-bold">📩 Solicitar Anulación de Pasaje #<?= $p['ID_PASAJE'] ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/solicitarAnulacion" method="POST">
                                                <div class="modal-body p-3">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <input type="hidden" name="id_factura" value="<?= $p['ID_FACTURA'] ?>">
                                                    
                                                    <div class="bg-light p-2 rounded border mb-3 small">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <strong>Pasajero:</strong> 
                                                            <span><?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <strong>Ruta:</strong> 
                                                            <span><?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <strong>Viaje / Asiento:</strong> 
                                                            <span><?= !empty($p['FECHA_VIAJE']) ? date('d/m/Y', strtotime($p['FECHA_VIAJE'])) : 'Abierto' ?> • Asiento: <?= $p['PAX_ASIENTO'] ?? 'Sin asignar' ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between border-top pt-1 text-primary fw-bold">
                                                            <span>Monto Pagado:</span> 
                                                            <span>$<?= number_format($pagado, 2) ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Monto sugerido a reembolsar ($)</label>
                                                        <input type="number" step="0.01" name="monto_devolver" class="form-control fw-bold text-success fs-5" value="<?= $pagado ?>" max="<?= $pagado ?>" min="0" required>
                                                        <small class="text-muted" style="font-size: 11px;">Máximo a devolver según abonos recibidos: $<?= number_format($pagado, 2) ?></small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Motivo detallado de la anulación <span class="text-danger">*</span></label>
                                                        <textarea name="motivo" class="form-control" rows="3" required placeholder="Explique claramente el motivo por el cual se solicita anular este pasaje (ej: Emergencia médica del cliente, cambio de planes, etc.)..."></textarea>
                                                    </div>

                                                    <div class="alert alert-info py-2 px-3 mb-0 small" style="font-size: 11px;">
                                                        ℹ️ <strong>Importante:</strong> Esta solicitud será enviada al Administrador para su aprobación. El boleto no se anulará ni se descontará de caja hasta que sea aprobada.
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2 bg-light">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-warning btn-sm fw-bold px-3">📩 Enviar Solicitud a Administración</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. MODAL DE RESOLVER SOLICITUD DIRECTAMENTE (ADMINISTRADOR) -->
                                <?php if(($p['SOLICITUD_ESTADO'] ?? '') === 'PENDIENTE' && $puedeAnularDirecto): ?>
                                <div class="modal fade text-start" id="modalResolverSolicitud<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-primary text-white py-2">
                                                <h6 class="modal-title fw-bold">🔔 Resolver Solicitud de Anulación #<?= $p['ID_SOLICITUD'] ?></h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-3">
                                                <div class="bg-light p-2 rounded border mb-3 small">
                                                    <div><strong>Pasajero:</strong> <?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?> (CI: <?= htmlspecialchars($p['ID_CLIENTE']) ?>)</div>
                                                    <div><strong>Ruta:</strong> <?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?> • Asiento: <?= $p['PAX_ASIENTO'] ?? 'Sin asignar' ?></div>
                                                    <div class="text-primary mt-1"><strong>Solicitado por:</strong> <?= htmlspecialchars($p['SOLICITUD_USUARIO_NOMBRE'] ?? 'Cajero') ?> el <?= !empty($p['SOLICITUD_FECHA']) ? date('d/m/Y H:i', strtotime($p['SOLICITUD_FECHA'])) : '' ?></div>
                                                    <div class="text-danger fw-bold fs-6 mt-1">Monto a reembolsar: $<?= number_format($p['SOLICITUD_MONTO'], 2) ?></div>
                                                    <div class="mt-2 p-2 bg-white rounded border">
                                                        <span class="text-muted fw-bold">Motivo expuesto por el cajero:</span><br>
                                                        <?= nl2br(htmlspecialchars($p['SOLICITUD_MOTIVO'])) ?>
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-column gap-2">
                                                    <!-- OPCIÓN A: APROBAR -->
                                                    <form action="/suramerica2030/pasajes/aprobarAnulacion" method="POST">
                                                        <input type="hidden" name="id_solicitud" value="<?= $p['ID_SOLICITUD'] ?>">
                                                        <input type="hidden" name="redirect_tab" value="<?= htmlspecialchars($filtros['tab'] ?? 'activos') ?>">
                                                        <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm" onclick="return confirm('¿Confirmas que deseas APROBAR esta anulación? Se liberará el asiento y se registrará un egreso de $<?= number_format($p['SOLICITUD_MONTO'], 2) ?> en caja.');">
                                                            ✔️ Aprobar Anulación y Reembolso ($<?= number_format($p['SOLICITUD_MONTO'], 2) ?>)
                                                        </button>
                                                    </form>

                                                    <!-- OPCIÓN B: DENEGAR -->
                                                    <div class="border rounded p-2 bg-light mt-1">
                                                        <form action="/suramerica2030/pasajes/denegarAnulacion" method="POST">
                                                            <input type="hidden" name="id_solicitud" value="<?= $p['ID_SOLICITUD'] ?>">
                                                            <input type="hidden" name="redirect_tab" value="<?= htmlspecialchars($filtros['tab'] ?? 'activos') ?>">
                                                            <label class="form-label small fw-bold text-danger mb-1">Si deniega, ingrese el motivo para el cajero:</label>
                                                            <input type="text" name="motivo_rechazo" class="form-control form-control-sm mb-2" required placeholder="Ej: No procede devolución según políticas, tiempo vencido...">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold" onclick="return confirm('¿Confirmas que deseas DENEGAR esta solicitud?');">
                                                                ❌ Denegar Solicitud
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- 2. MODAL DE ASIGNAR FECHA ABIERTA -->
                                <?php if(!$p['FECHA_VIAJE']): ?>
                                <div class="modal fade text-start" id="modalAsignar<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-dark"><h6 class="modal-title">Asignar Fecha de Viaje</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <form action="/suramerica2030/pasajes/asignar" method="POST">
                                                <div class="modal-body row">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <div class="col-6 mb-2"><label>Fecha Exacta</label><input type="date" name="fecha_viaje" id="fecha_asig_<?= $p['ID_PASAJE'] ?>" class="form-control" required onchange="limpiarAsiento(<?= $p['ID_PASAJE'] ?>)"></div>
                                                    <div class="col-6 mb-2">
                                                        <label>Bus Asignado</label>
                                                        <select name="bus_id" id="bus_asig_<?= $p['ID_PASAJE'] ?>" class="form-select" required onchange="limpiarAsiento(<?= $p['ID_PASAJE'] ?>)">
                                                            <option value="">- Elegir -</option>
                                                            <?php foreach($buses as $b): ?><option value="<?= $b['ID_BUS'] ?>" data-asientos="<?= $b['BUS_ASIENTOS'] ?>"><?= htmlspecialchars($b['BUS_PLACA']) ?></option><?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 text-center mt-2">
                                                        <label>Asiento Elegido</label>
                                                        <div class="input-group">
                                                            <input type="text" name="asiento" id="asiento_asig_<?= $p['ID_PASAJE'] ?>" class="form-control text-center fw-bold fs-5 bg-white" readonly required placeholder="Ninguno">
                                                            <button type="button" class="btn btn-dark" onclick="abrirMapaAsientos(<?= $p['ID_PASAJE'] ?>, <?= $p['ID_RUTA'] ?? 0 ?>, 'asig')">📍 Ver Croquis</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-1"><button type="submit" class="btn btn-info w-100 fw-bold">Confirmar Viaje</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- 3. MODAL DE ABONO -->
                                <div class="modal fade text-start" id="modalAbono<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h6 class="modal-title">Registrar Abono</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/abonar" method="POST" onsubmit="return validarAbonoMixto(this, <?= $p['ID_PASAJE'] ?>)">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <input type="hidden" name="cliente_id" value="<?= $p['ID_CLIENTE'] ?>">
                                                    <p class="mb-1"><strong>Saldo Pendiente:</strong> <span class="text-danger fw-bold">$<?= number_format($p['SALDO'], 2) ?></span></p>
                                                    <label class="fw-bold small">Monto a Abonar ($)</label>
                                                    <input type="number" step="0.01" name="monto_abono" id="monto_abono_<?= $p['ID_PASAJE'] ?>" class="form-control mb-2" required max="<?= $p['SALDO'] ?>" min="1" oninput="balancearMixtoAbono(<?= $p['ID_PASAJE'] ?>, 'total')">
                                                    <label class="fw-bold small">Método de Pago</label>
                                                    <select name="metodo_pago" id="metodo_pago_abono_<?= $p['ID_PASAJE'] ?>" class="form-select mb-2" required onchange="togglePagoMixtoAbono(<?= $p['ID_PASAJE'] ?>)">
                                                        <option value="EFECTIVO">Efectivo</option>
                                                        <option value="TRANSFERENCIA">Transferencia</option>
                                                        <option value="MIXTO">Mixto (Efectivo + Transferencia)</option>
                                                    </select>
                                                    <div class="p-2 mb-2 rounded bg-light border border-info d-none" id="div_mixto_abono_<?= $p['ID_PASAJE'] ?>">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="small fw-bold text-primary" style="font-size:11px;">💳 Desglose Mixto</span>
                                                            <span class="badge bg-success" id="badge_mixto_abono_<?= $p['ID_PASAJE'] ?>" style="font-size:10px;">✔ Cuadrado</span>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <label class="form-label small fw-bold mb-0" style="font-size:11px;">Efectivo ($)</label>
                                                                <input type="number" step="0.01" min="0" name="monto_efectivo" id="monto_efectivo_abono_<?= $p['ID_PASAJE'] ?>" class="form-control form-control-sm fw-bold text-success" placeholder="0.00" oninput="balancearMixtoAbono(<?= $p['ID_PASAJE'] ?>, 'efectivo')">
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label small fw-bold mb-0" style="font-size:11px;">Transf. ($)</label>
                                                                <input type="number" step="0.01" min="0" name="monto_transferencia" id="monto_transferencia_abono_<?= $p['ID_PASAJE'] ?>" class="form-control form-control-sm fw-bold text-primary" placeholder="0.00" oninput="balancearMixtoAbono(<?= $p['ID_PASAJE'] ?>, 'transferencia')">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-1"><button type="submit" class="btn btn-success w-100 fw-bold">Cobrar Abono</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. MODAL DE CONTROL DE EQUIPAJE -->
                                <div class="modal fade text-start" id="modalEquipaje<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning text-dark py-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fs-5">⚖️</span>
                                                    <h6 class="modal-title fw-bold mb-0">Control de Equipaje - Pasajero: <?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></h6>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/equipaje" method="POST" onsubmit="return validarEquipajeMixto(this, <?= $p['ID_PASAJE'] ?>)">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <input type="hidden" name="cliente_id" value="<?= $p['ID_CLIENTE'] ?>">
                                                    
                                                    <!-- Campos ocultos sincronizados de peso para compatibilidad global -->
                                                    <input type="hidden" name="peso_mano" id="pm_<?= $p['ID_PASAJE'] ?>" value="10.0">
                                                    <input type="hidden" name="peso_bodega" id="pb_<?= $p['ID_PASAJE'] ?>" value="<?= $p['PESO_BODEGA'] ?? 0 ?>">
                                                    
                                                    <?php 
                                                    $piezasRegistradas = [];
                                                    if (!empty($p['EQUIPAJE_DETALLE'])) {
                                                        $dec = json_decode($p['EQUIPAJE_DETALLE'], true);
                                                        if (is_array($dec)) {
                                                            foreach ($dec as $item) {
                                                                if (($item['ubicacion'] ?? 'BODEGA') === 'BODEGA' || empty($item['ubicacion'])) {
                                                                    $piezasRegistradas[] = $item;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    if (empty($piezasRegistradas)) {
                                                        $pb = floatval($p['PESO_BODEGA'] ?? 0);
                                                        if ($pb > 0) {
                                                            $piezasRegistradas[] = ['tipo' => 'Maleta', 'peso' => $pb, 'descripcion' => ''];
                                                        }
                                                    }
                                                    if (empty($piezasRegistradas)) {
                                                        $piezasRegistradas[] = ['tipo' => 'Maleta', 'peso' => '', 'descripcion' => ''];
                                                    }
                                                    ?>

                                                    <?php $tieneSpActivo = !empty($p['ID_SOBREPESO_ACTIVO']); ?>

                                                    <?php if ($tieneSpActivo): ?>
                                                        <div class="alert alert-warning border-warning p-3 mb-3 shadow-sm rounded">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="fs-4">⚖️</span>
                                                                    <div>
                                                                        <strong class="text-dark d-block">Cobro de Sobrepeso Activo Registrado</strong>
                                                                        <span class="small text-muted">
                                                                            Recibo #<?= htmlspecialchars($p['SOBREPESO_FACTURA_ACTIVA'] ?? '') ?> &bull; 
                                                                            <?= number_format($p['SOBREPESO_KG_ACTIVO'] ?? 0, 1) ?> Kg exceso &bull; 
                                                                            <strong class="text-success">$<?= number_format($p['SOBREPESO_TOTAL_ACTIVO'] ?? 0, 2) ?> USD</strong>
                                                                            <?php if(!empty($p['SOBREPESO_OPERADOR_ACTIVO'])): ?>
                                                                                &bull; Op: <?= htmlspecialchars($p['SOBREPESO_OPERADOR_ACTIVO']) ?>
                                                                            <?php endif; ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <span class="badge bg-success px-2 py-1">ACTIVO</span>
                                                            </div>
                                                            <p class="small text-dark mb-2">
                                                                Este boleto ya cuenta con un cobro de sobrepeso activo. <strong>Para evitar duplicidad contable no se puede generar otro sobrepeso adicional</strong>. Si necesita corregir los valores o el pesaje, primero anule el cobro actual.
                                                            </p>
                                                            <div class="d-flex flex-wrap gap-2 pt-2 border-top border-warning">
                                                                <?php if (!empty($p['SOBREPESO_FACTURA_ACTIVA'])): ?>
                                                                    <a href="/suramerica2030/ventas/ticket/<?= $p['SOBREPESO_FACTURA_ACTIVA'] ?>" target="_blank" class="btn btn-sm btn-primary fw-bold">
                                                                        🧾 Ver Factura / Ticket #<?= $p['SOBREPESO_FACTURA_ACTIVA'] ?>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <a href="/suramerica2030/pasajes/imprimirEquipaje?id=<?= $p['ID_PASAJE'] ?>" target="_blank" class="btn btn-sm btn-dark fw-bold">
                                                                    🏷️ Reimprimir Etiquetas
                                                                </a>
                                                                <?php if ($puedeAnularDirecto): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold ms-auto" data-bs-toggle="modal" data-bs-target="#modalAnularSobrepeso<?= $p['ID_PASAJE'] ?>">
                                                                        ❌ Anular Sobrepeso
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php elseif($p['PESO_BODEGA'] > 0 || !empty($p['EQUIPAJE_DETALLE'])): ?>
                                                        <div class="alert alert-info p-2 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-info">
                                                            <div>
                                                                <strong class="text-dark">🖨️ Acciones Rápidas:</strong>
                                                                <span class="small text-muted ms-1"><?= count($piezasRegistradas) ?> pieza(s) registrada(s)</span>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <a href="/suramerica2030/pasajes/imprimirEquipaje?id=<?= $p['ID_PASAJE'] ?>" target="_blank" class="btn btn-sm btn-dark fw-bold">
                                                                    🧳 Imprimir Todas las Etiquetas (<?= count($piezasRegistradas) ?>)
                                                                </a>
                                                                <?php if(!empty($p['ID_FACTURA_SOBREPESO'])): ?>
                                                                    <a href="/suramerica2030/ventas/ticket/<?= $p['ID_FACTURA_SOBREPESO'] ?>" target="_blank" class="btn btn-sm btn-primary fw-bold">
                                                                        🧾 Recibo de Sobrepeso
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php
                                                    $tieneTramos = !empty($p['TRAMOS']);
                                                    $idTramoSeleccionado = intval($p['ID_RUTA_TRAMO_SOBREPESO'] ?? 0);
                                                    $primerTramoAdmite = null;

                                                    if ($tieneTramos) {
                                                        foreach ($p['TRAMOS'] as $tr) {
                                                            if ($tr['ADMITE_SOBREPESO'] == 1 && $primerTramoAdmite === null) {
                                                                $primerTramoAdmite = intval($tr['ID_RUTA_TRAMO']);
                                                            }
                                                        }
                                                        if ($idTramoSeleccionado <= 0 && $primerTramoAdmite !== null) {
                                                            $idTramoSeleccionado = $primerTramoAdmite;
                                                        }
                                                    }

                                                    $operadorDirectoRuta = (!empty($p['MODALIDAD']) && $p['MODALIDAD'] !== 'ESTANDAR' && $p['MODALIDAD'] !== 'CONEXION_TRAMOS') ? $p['MODALIDAD'] : 'Propio Suramérica';
                                                    $admiteDirectoRuta = isset($p['ADMITE_SOBREPESO']) ? intval($p['ADMITE_SOBREPESO']) : 1;
                                                    $limiteTramoActivo = floatval($p['LIMITE_EQUIPAJE_KG'] ?? 35.0);
                                                    $tarifaTramoActivo = floatval($p['PRECIO_SOBREPESO_KG'] ?? 1.0);
                                                    $operadorTramoActivo = $operadorDirectoRuta;
                                                    $admiteTramoActivo = $admiteDirectoRuta;

                                                    if ($tieneTramos && $idTramoSeleccionado > 0) {
                                                        foreach ($p['TRAMOS'] as $tr) {
                                                            if (intval($tr['ID_RUTA_TRAMO']) === $idTramoSeleccionado) {
                                                                $limiteTramoActivo = floatval($tr['LIMITE_EQUIPAJE_KG'] ?? 35.0);
                                                                $tarifaTramoActivo = floatval($tr['PRECIO_SOBREPESO_KG'] ?? 1.0);
                                                                $operadorTramoActivo = !empty($tr['OPERADOR']) ? $tr['OPERADOR'] : 'General';
                                                                $admiteTramoActivo = intval($tr['ADMITE_SOBREPESO'] ?? 0);
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    ?>

                                                    <input type="hidden" name="precio_sobrepeso_kg" id="tarifa_input_eq_<?= $p['ID_PASAJE'] ?>" value="<?= number_format($tarifaTramoActivo, 2, '.', '') ?>">
                                                    <input type="hidden" id="limite_input_eq_<?= $p['ID_PASAJE'] ?>" value="<?= number_format($limiteTramoActivo, 1, '.', '') ?>">

                                                    <div class="p-2 mb-3 bg-white rounded border shadow-sm">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="small text-muted fw-bold">
                                                                📍 Itinerario General: <strong class="text-dark"><?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?></strong>
                                                            </span>
                                                            <?php if (!empty($p['MODALIDAD']) && $p['MODALIDAD'] === 'PANAMERICANA'): ?>
                                                                <span class="badge bg-warning text-dark border border-warning" style="font-size:10px;">⭐ Aliado Panamericana</span>
                                                            <?php elseif (!empty($p['MODALIDAD']) && $p['MODALIDAD'] !== 'ESTANDAR' && $p['MODALIDAD'] !== 'CONEXION_TRAMOS'): ?>
                                                                <span class="badge bg-light text-dark border" style="font-size:10px;"><i class="bi bi-building"></i> <?= htmlspecialchars($p['MODALIDAD']) ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-success" style="font-size:10px;">🚌 Operación Propia</span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if ($tieneTramos): ?>
                                                            <div class="mt-2">
                                                                <label class="form-label small fw-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                                                                    <span>🚍 Tramo al que aplica el Sobrepeso:</span>
                                                                    <span class="text-muted" style="font-size: 10px;">* Elija el tramo donde se transporta y cobra el exceso</span>
                                                                </label>
                                                                <select name="id_ruta_tramo" id="sel_tramo_eq_<?= $p['ID_PASAJE'] ?>" class="form-select form-select-sm fw-bold border-primary" onchange="alCambiarTramoEquipaje(<?= $p['ID_PASAJE'] ?>)">
                                                                    <?php foreach ($p['TRAMOS'] as $tr): 
                                                                        $esSelect = (intval($tr['ID_RUTA_TRAMO']) === $idTramoSeleccionado);
                                                                    ?>
                                                                        <option value="<?= $tr['ID_RUTA_TRAMO'] ?>"
                                                                                data-operador="<?= htmlspecialchars($tr['OPERADOR']) ?>"
                                                                                data-origen="<?= htmlspecialchars($tr['ORIGEN_TRAMO']) ?>"
                                                                                data-destino="<?= htmlspecialchars($tr['DESTINO_TRAMO']) ?>"
                                                                                data-admite="<?= $tr['ADMITE_SOBREPESO'] ?>"
                                                                                data-limite="<?= floatval($tr['LIMITE_EQUIPAJE_KG']) ?>"
                                                                                data-tarifa="<?= floatval($tr['PRECIO_SOBREPESO_KG']) ?>"
                                                                                <?= $esSelect ? 'selected' : '' ?>>
                                                                            Tramo <?= $tr['ORDEN'] ?>: <?= htmlspecialchars($tr['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($tr['DESTINO_TRAMO']) ?> (Operador: <?= htmlspecialchars($tr['OPERADOR']) ?>) <?= ($tr['ADMITE_SOBREPESO'] == 1) ? '✔ [Sobrepeso Autorizado]' : '⚠️ [Sin cobro en oficina]' ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                    <option value="0"
                                                                            data-operador="<?= htmlspecialchars($operadorDirectoRuta) ?>"
                                                                            data-origen="<?= htmlspecialchars($p['RUT_ORIGEN']) ?>"
                                                                            data-destino="<?= htmlspecialchars($p['RUT_DESTINO']) ?>"
                                                                            data-admite="<?= $admiteDirectoRuta ?>"
                                                                            data-limite="<?= floatval($p['LIMITE_EQUIPAJE_KG'] ?? 35) ?>"
                                                                            data-tarifa="<?= floatval($p['PRECIO_SOBREPESO_KG'] ?? 1) ?>"
                                                                            <?= ($idTramoSeleccionado === 0) ? 'selected' : '' ?>>
                                                                        🌐 Toda la Ruta: <?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?> (<?= htmlspecialchars($operadorDirectoRuta) ?>) <?= ($admiteDirectoRuta == 1) ? '✔ [Sobrepeso Autorizado]' : '⚠️ [Sin cobro en oficina]' ?>
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        <?php else: ?>
                                                            <input type="hidden" name="id_ruta_tramo" id="sel_tramo_eq_<?= $p['ID_PASAJE'] ?>" value="0">
                                                        <?php endif; ?>

                                                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 p-1 px-2 bg-light rounded border small">
                                                            <span class="text-secondary">
                                                                🏢 Operador: <strong class="text-dark" id="lbl_operador_eq_<?= $p['ID_PASAJE'] ?>"><?= htmlspecialchars($operadorTramoActivo) ?></strong>
                                                            </span>
                                                            <span>
                                                                Límite: <strong class="text-primary" id="lbl_limite_eq_<?= $p['ID_PASAJE'] ?>"><?= number_format($limiteTramoActivo, 1) ?> Kg</strong>
                                                                &nbsp;|&nbsp; Tarifa: <strong class="text-danger" id="lbl_tarifa_eq_<?= $p['ID_PASAJE'] ?>">$<?= number_format($tarifaTramoActivo, 2) ?>/Kg</strong>
                                                            </span>
                                                        </div>

                                                        <div id="alerta_no_admite_eq_<?= $p['ID_PASAJE'] ?>" class="alert alert-warning py-1 px-2 mb-0 mt-2 small <?= ($admiteTramoActivo == 1) ? 'd-none' : '' ?>">
                                                            ⚠️ <strong>Atención:</strong> Por condiciones logísticas y aduaneras, este operador / tramo no admite cobro de sobrepeso desde la oficina en Ecuador.
                                                        </div>

                                                        <!-- CÓDIGO BOLETO EMPRESA ALIADA / PANAMERICANA -->
                                                        <div class="mt-2 p-2 border rounded bg-light">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <label class="form-label small fw-bold text-dark mb-0">🎫 Código Boleto Empresa Aliada / Socio:</label>
                                                                <span class="badge bg-warning text-dark" style="font-size: 10px;">Ticket Externo</span>
                                                            </div>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-white fw-bold font-monospace text-muted">N° Boleto Socio:</span>
                                                                <input type="text" name="codigo_socio" class="form-control font-monospace fw-bold text-uppercase" 
                                                                       placeholder="Ej: PANA-000000" value="<?= htmlspecialchars($p['CODIGO_SOCIO'] ?? '') ?>">
                                                            </div>
                                                            <div class="text-muted" style="font-size: 10px; margin-top: 3px;">
                                                                * Ingrese el código generado por el sistema de Panamericana / Empresa Aliada para imprimirlo en las etiquetas.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- CONTENEDOR DE PIEZAS DE EQUIPAJE (Estilo Encomiendas) -->
                                                    <div class="mb-2">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <label class="form-label small fw-bold text-secondary mb-0">
                                                                🧳 Piezas de Bodega a Pesar y Etiquetar:
                                                            </label>
                                                            <span class="badge bg-light text-secondary border" style="font-size:11px;">
                                                                🎒 Maleta de mano: <strong>10 Kg</strong> (fijo en cabina)
                                                            </span>
                                                        </div>
                                                        <div id="contenedor_piezas_eq_<?= $p['ID_PASAJE'] ?>">
                                                            <?php foreach($piezasRegistradas as $pzIdx => $pz): ?>
                                                                <div class="row g-2 pieza-equipaje-row mb-2 align-items-end" data-pasaje="<?= $p['ID_PASAJE'] ?>">
                                                                    <div class="col-md-3 col-6">
                                                                        <label class="form-label mb-0" style="font-size:11px;">Tipo</label>
                                                                        <select name="eq_tipo[]" class="form-select form-select-sm" onchange="calcularCobroEquipaje(<?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)">
                                                                            <option value="Maleta" <?= ($pz['tipo'] ?? '') === 'Maleta' ? 'selected' : '' ?>>Maleta</option>
                                                                            <option value="Bolso" <?= ($pz['tipo'] ?? '') === 'Bolso' ? 'selected' : '' ?>>Bolso / Maletín</option>
                                                                            <option value="Caja" <?= ($pz['tipo'] ?? '') === 'Caja' ? 'selected' : '' ?>>Caja</option>
                                                                            <option value="Bulto" <?= ($pz['tipo'] ?? '') === 'Bulto' ? 'selected' : '' ?>>Bulto / Saco</option>
                                                                            <option value="Mochila" <?= ($pz['tipo'] ?? '') === 'Mochila' ? 'selected' : '' ?>>Mochila</option>
                                                                            <option value="Otro" <?= ($pz['tipo'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-2 col-6">
                                                                        <label class="form-label mb-0" style="font-size:11px;">Peso (Kg)</label>
                                                                        <input type="number" step="0.1" min="0" name="eq_peso[]" class="form-control form-control-sm text-end fw-bold eq-peso-input" value="<?= (!empty($pz['peso']) || (isset($pz['peso']) && $pz['peso'] === 0)) ? floatval($pz['peso']) : '' ?>" placeholder="0.0" required oninput="calcularCobroEquipaje(<?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)" onchange="calcularCobroEquipaje(<?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)">
                                                                    </div>
                                                                    <div class="col-md-6 col-10">
                                                                        <label class="form-label mb-0" style="font-size:11px;">Descripción (opcional)</label>
                                                                        <input type="text" name="eq_descripcion[]" class="form-control form-control-sm" value="<?= htmlspecialchars($pz['descripcion'] ?? '') ?>" placeholder="Ej: Ropa, Frágil...">
                                                                    </div>
                                                                    <div class="col-md-1 col-2 text-end">
                                                                        <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="eliminarPiezaEquipaje(this, <?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)" title="Eliminar pieza">×</button>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary mb-3 fw-bold" onclick="agregarPiezaEquipaje(<?= $p['ID_PASAJE'] ?>, <?= $p['PRECIO_SOBREPESO_KG'] ?? 1.00 ?>, <?= $p['LIMITE_EQUIPAJE_KG'] ?? 35.00 ?>)">
                                                            + Añadir otra pieza
                                                        </button>
                                                    </div>

                                                    <!-- RESUMEN DINÁMICO DE TOTALES -->
                                                    <div class="bg-light p-3 rounded border text-center mb-2">
                                                        <div class="d-flex flex-wrap justify-content-around align-items-center gap-2">
                                                            <div>
                                                                <span class="text-muted small d-block">Piezas Bodega:</span>
                                                                <strong class="fs-6" id="peq_cant_<?= $p['ID_PASAJE'] ?>"><?= count($piezasRegistradas) ?></strong>
                                                            </div>
                                                            <div>
                                                                <span class="text-muted small d-block">Peso Bodega:</span>
                                                                <strong class="fs-6 text-primary"><span id="peq_bodega_<?= $p['ID_PASAJE'] ?>"><?= number_format($p['PESO_BODEGA'] ?? 0, 1) ?></span> Kg</strong>
                                                            </div>
                                                            <div>
                                                                <span class="text-muted small d-block">Mano (Fijo):</span>
                                                                <strong class="fs-6 text-secondary"><span id="peq_mano_<?= $p['ID_PASAJE'] ?>">10.0</span> Kg</strong>
                                                            </div>
                                                            <div class="border-start ps-3">
                                                                <span class="text-muted small d-block">Total Global:</span>
                                                                <strong class="fs-5 text-dark"><span id="pt_<?= $p['ID_PASAJE'] ?>"><?= number_format(floatval($p['PESO_BODEGA'] ?? 0) + 10.0, 1) ?></span> Kg</strong>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-danger d-none" id="warn_<?= $p['ID_PASAJE'] ?>">
                                                                ⚠️ Exceso de <span id="pex_<?= $p['ID_PASAJE'] ?>">0</span> Kg sobre el límite permitido
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- SECCIÓN DE COBRO DE SOBREPESO (CON DESCUENTO EDITABLE) -->
                                                    <div class="row align-items-end p-2 bg-light rounded border mb-2 d-none" id="cobro_<?= $p['ID_PASAJE'] ?>">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-bold">Método de Pago</label>
                                                            <select name="metodo_pago" id="metodo_pago_eq_<?= $p['ID_PASAJE'] ?>" class="form-select" onchange="togglePagoMixtoEquipaje(<?= $p['ID_PASAJE'] ?>)">
                                                                <option value="EFECTIVO">Efectivo</option>
                                                                <option value="TRANSFERENCIA">Transferencia</option>
                                                                <option value="MIXTO">Mixto (Efectivo + Transferencia)</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <label class="form-label small fw-bold mb-0">A Cobrar ($)</label>
                                                                <small class="text-success fw-bold" id="sug_<?= $p['ID_PASAJE'] ?>" style="font-size:10px;"></small>
                                                            </div>
                                                            <input type="number" step="0.01" min="0" name="costo_extra" id="cx_<?= $p['ID_PASAJE'] ?>" class="form-control fw-bold text-danger text-end" placeholder="0.00" <?= !$puedeEditarPrecios ? 'readonly' : '' ?> oninput="balancearMixtoEquipaje(<?= $p['ID_PASAJE'] ?>, 'total')">
                                                            <small class="text-muted d-block text-end" style="font-size:10px;">
                                                                <?= $puedeEditarPrecios ? '* Editable (permite descuento)' : '🔒 Tarifa calculada fija (sin permiso de descuento)' ?>
                                                            </small>
                                                        </div>
                                                        <div class="col-12 mt-2 d-none" id="div_mixto_eq_<?= $p['ID_PASAJE'] ?>">
                                                            <div class="p-2 rounded bg-white border border-info">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="small fw-bold text-primary" style="font-size:11px;">💳 Desglose Mixto</span>
                                                                    <span class="badge bg-success" id="badge_mixto_eq_<?= $p['ID_PASAJE'] ?>" style="font-size:10px;">✔ Cuadrado</span>
                                                                </div>
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <label class="form-label small fw-bold mb-0" style="font-size:11px;">Efectivo ($)</label>
                                                                        <input type="number" step="0.01" min="0" name="monto_efectivo" id="monto_efectivo_eq_<?= $p['ID_PASAJE'] ?>" class="form-control form-control-sm fw-bold text-success" placeholder="0.00" oninput="balancearMixtoEquipaje(<?= $p['ID_PASAJE'] ?>, 'efectivo')">
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="form-label small fw-bold mb-0" style="font-size:11px;">Transf. ($)</label>
                                                                        <input type="number" step="0.01" min="0" name="monto_transferencia" id="monto_transferencia_eq_<?= $p['ID_PASAJE'] ?>" class="form-control form-control-sm fw-bold text-primary" placeholder="0.00" oninput="balancearMixtoEquipaje(<?= $p['ID_PASAJE'] ?>, 'transferencia')">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <?php if ($tieneSpActivo): ?>
                                                        <button type="button" class="btn btn-secondary btn-sm fw-bold" disabled title="Ya existe un sobrepeso activo cobrado. Anule el actual para emitir uno nuevo.">🔒 Cobro Bloqueado (Ya existe Sobrepeso Activo)</button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-warning fw-bold px-3">💾 Guardar y Procesar Equipaje</button>
                                                    <?php endif; ?>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($p['ID_SOBREPESO_ACTIVO'])): ?>
                                <div class="modal fade text-start" id="modalAnularSobrepeso<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content border-danger">
                                            <div class="modal-header bg-danger text-white py-2">
                                                <h6 class="modal-title fw-bold">❌ Confirmar Anulación de Sobrepeso</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/anular-sobrepeso" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">
                                                    <input type="hidden" name="id_sobrepeso" value="<?= $p['ID_SOBREPESO_ACTIVO'] ?>">
                                                    <input type="hidden" name="redirect_tab" value="<?= htmlspecialchars($filtros['tab'] ?? 'activos') ?>">

                                                    <div class="alert alert-danger p-2 small mb-3">
                                                        <strong>¡Atención!</strong> Se anulará el cobro de sobrepeso de <strong>$<?= number_format($p['SOBREPESO_TOTAL_ACTIVO'] ?? 0, 2) ?> USD</strong> (Recibo #<?= $p['SOBREPESO_FACTURA_ACTIVA'] ?>) de <strong><?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></strong>.
                                                        <ul class="mb-0 ps-3 mt-1">
                                                            <li>Se generará automáticamente un movimiento de <strong>EGRESO</strong> en Caja para devolver el dinero.</li>
                                                            <li>La factura quedará registrada como <strong>ANULADA</strong>.</li>
                                                            <li>Se excluirá de la liquidación de socios y operadores en Finanzas.</li>
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

                                <!-- 5. MODAL DE ACTUALIZAR ESTADO FINANCIERO -->
                                <div class="modal fade text-start" id="modalFinanciero<?= $p['ID_PASAJE'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h6 class="modal-title">💳 Actualizar Estado Financiero</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/suramerica2030/pasajes/actualizarfinanciero" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_pasaje" value="<?= $p['ID_PASAJE'] ?>">

                                                    <!-- Resumen del Pasaje -->
                                                    <div class="p-2 mb-3 bg-light rounded border">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <strong><?= htmlspecialchars($p['CLI_NOMBRE'].' '.$p['CLI_APELLIDO']) ?></strong>
                                                            <span class="badge bg-secondary">CI: <?= htmlspecialchars($p['ID_CLIENTE']) ?></span>
                                                        </div>
                                                        <div class="text-muted small mt-1">
                                                            📍 <strong>Ruta:</strong> <?= htmlspecialchars($p['RUT_ORIGEN'].' ➔ '.$p['RUT_DESTINO']) ?>
                                                        </div>
                                                        <div class="d-flex gap-2 mt-2 flex-wrap" style="font-size: 11px;">
                                                            <span class="badge bg-white text-dark border">
                                                                Tarifa Actual de Ruta: <strong class="text-primary">$<?= number_format($p['RUTA_PRECIO'] ?? 0, 2) ?></strong>
                                                            </span>
                                                            <span class="badge bg-white text-dark border">
                                                                Factura #<?= $p['ID_FACTURA'] ?>: <strong class="text-success">$<?= number_format($p['FA_TOTAL'] ?? 0, 2) ?></strong>
                                                            </span>
                                                            <span class="badge bg-white text-dark border">
                                                                Pasaje Registrado: <strong class="text-danger">$<?= number_format($p['PRECIO_TOTAL'], 2) ?></strong>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Botones de Ajuste Rápido -->
                                                    <div class="mb-3">
                                                        <label class="form-label small text-muted fw-bold mb-1">⚡ Accesos rápidos para sincronizar monto:</label>
                                                        <div class="d-flex gap-2 flex-wrap">
                                                            <?php if(!empty($p['FA_TOTAL']) && floatval($p['FA_TOTAL']) > 0): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="aplicarFinanciero(<?= $p['ID_PASAJE'] ?>, <?= floatval($p['FA_TOTAL']) ?>, 0, 'PAGADO')">
                                                                    ⚡ Usar Factura ($<?= number_format($p['FA_TOTAL'], 2) ?>)
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if(!empty($p['RUTA_PRECIO']) && floatval($p['RUTA_PRECIO']) > 0): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="aplicarFinanciero(<?= $p['ID_PASAJE'] ?>, <?= floatval($p['RUTA_PRECIO']) ?>, 0, 'PAGADO')">
                                                                    ⚡ Usar Tarifa Ruta ($<?= number_format($p['RUTA_PRECIO'], 2) ?>)
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarPagado(<?= $p['ID_PASAJE'] ?>)">
                                                                ✅ Saldo $0 (Pagado)
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Campos Financieros Editables -->
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-bold">Precio Total ($)</label>
                                                            <input type="number" step="0.01" name="precio_total" id="fin_precio_<?= $p['ID_PASAJE'] ?>" class="form-control fw-bold fs-5 text-dark <?= !$puedeEditarPrecios ? 'bg-light' : '' ?>" value="<?= number_format($p['PRECIO_TOTAL'], 2, '.', '') ?>" required oninput="recalcularFinanciero(<?= $p['ID_PASAJE'] ?>)" <?= !$puedeEditarPrecios ? 'readonly' : '' ?>>
                                                            <?php if(!$puedeEditarPrecios): ?>
                                                                <small class="text-muted d-block" style="font-size:11px;">🔒 Tarifa bloqueada (sin permiso de edición).</small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-bold">Saldo Pendiente ($)</label>
                                                            <input type="number" step="0.01" name="saldo" id="fin_saldo_<?= $p['ID_PASAJE'] ?>" class="form-control fw-bold fs-5 text-danger" value="<?= number_format($p['SALDO'], 2, '.', '') ?>" required oninput="recalcularFinanciero(<?= $p['ID_PASAJE'] ?>)">
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Estado Financiero</label>
                                                        <select name="estado" id="fin_estado_<?= $p['ID_PASAJE'] ?>" class="form-select fw-bold">
                                                            <option value="PAGADO" <?= $p['ESTADO'] == 'PAGADO' ? 'selected' : '' ?>>✅ PAGADO (Totalmente cancelado)</option>
                                                            <option value="RESERVA" <?= $p['ESTADO'] == 'RESERVA' ? 'selected' : '' ?>>⚠️ RESERVA (Con Saldo Pendiente)</option>
                                                        </select>
                                                    </div>

                                                    <!-- Opciones de sincronización con Factura y Caja -->
                                                    <div class="bg-light p-2 rounded border">
                                                        <div class="form-check form-switch mb-1">
                                                            <input class="form-check-input" type="checkbox" name="sincronizar_factura" value="1" id="sync_fac_<?= $p['ID_PASAJE'] ?>" checked>
                                                            <label class="form-check-label small fw-bold text-dark" for="sync_fac_<?= $p['ID_PASAJE'] ?>">
                                                                Sincronizar Factura #<?= $p['ID_FACTURA'] ?> con este monto
                                                            </label>
                                                        </div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="sincronizar_caja" value="1" id="sync_caja_<?= $p['ID_PASAJE'] ?>" checked>
                                                            <label class="form-check-label small fw-bold text-dark" for="sync_caja_<?= $p['ID_PASAJE'] ?>">
                                                                Sincronizar Movimiento de Ingreso en Caja
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer p-2">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Guardar Estado Financiero</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>Anulado</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- PAGINACIÓN PASAJES -->
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