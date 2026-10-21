        <!-- ======================================================== -->
    <!-- PESTAÑA 3: FLOTA DE BUSES Y OPERADORES / SOCIOS          -->
    <!-- ======================================================== -->
    <div class="tab-pane fade <?= ($tabActiva == 'buses' || $tabActiva == 'operadores') ? 'show active' : '' ?>" id="tab-buses">
        
        <!-- SECCIÓN 1: DIRECTORIO MAESTRO DE OPERADORES Y SOCIOS -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                <div>
                    <h5 class="text-primary mb-1 fw-bold">🏢 Directorio Maestro de Operadores y Socios</h5>
                    <p class="text-muted small mb-0">Gestión de razones sociales, RUC/Cédula, cuentas bancarias para liquidaciones y vínculos con buses/rutas.</p>
                </div>
                <button class="btn btn-sm btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearOperador">
                    + Registrar Nuevo Operador / Socio
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Operador / Razón Social</th>
                                <th>Tipo de Operador</th>
                                <th>Identificación / RUC</th>
                                <th>Contacto</th>
                                <th>Cuenta Bancaria para Liquidaciones</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center" style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($operadores)): ?>
                                <tr><td colspan="8" class="text-center py-3 text-muted">No hay operadores registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach($operadores as $op): 
                                    $badgeTipo = 'bg-secondary';
                                    $labelTipo = $op['TIPO_OPERADOR'] ?? 'SOCIO_AFILIADO';
                                    if ($labelTipo === 'PROPIO') {
                                        $badgeTipo = 'bg-primary';
                                        $labelTipo = '🏢 Flota Propia';
                                    } elseif ($labelTipo === 'SOCIO_AFILIADO') {
                                        $badgeTipo = 'bg-success';
                                        $labelTipo = '🤝 Socio Propietario';
                                    } elseif ($labelTipo === 'EMPRESA_ALIADA') {
                                        $badgeTipo = 'bg-info text-dark';
                                        $labelTipo = '⭐ Empresa Aliada';
                                    }
                                ?>
                                    <tr id="fila_operador_<?= $op['ID_OPERADOR'] ?>">
                                        <td class="fw-bold text-muted">#<?= $op['ID_OPERADOR'] ?></td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?></div>
                                            <?php if(!empty($op['RAZON_SOCIAL']) && $op['RAZON_SOCIAL'] !== $op['NOMBRE_COMERCIAL']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($op['RAZON_SOCIAL']) ?></small>
                                            <?php endif; ?>
                                            <?php if(!empty($op['OBSERVACIONES'])): ?>
                                                <div class="small text-secondary fst-italic" style="font-size: 11px;">📝 <?= htmlspecialchars($op['OBSERVACIONES']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeTipo ?> px-2 py-1"><?= $labelTipo ?></span>
                                        </td>
                                        <td>
                                            <?= !empty($op['IDENTIFICACION']) ? '<span class="font-monospace fw-bold text-dark">' . htmlspecialchars($op['IDENTIFICACION']) . '</span>' : '<span class="text-muted small">Sin registrar</span>' ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($op['TELEFONO'])): ?>
                                                <div class="small text-success">📞 <?= htmlspecialchars($op['TELEFONO']) ?></div>
                                            <?php endif; ?>
                                            <?php if(!empty($op['CORREO'])): ?>
                                                <div class="small text-muted">✉️ <?= htmlspecialchars($op['CORREO']) ?></div>
                                            <?php endif; ?>
                                            <?php if(empty($op['TELEFONO']) && empty($op['CORREO'])): ?>
                                                <span class="text-muted small">Sin contacto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($op['BANCO'])): ?>
                                                <div class="fw-bold text-dark small">🏦 <?= htmlspecialchars($op['BANCO']) ?></div>
                                                <div class="small">
                                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($op['TIPO_CUENTA'] ?? 'Corriente') ?></span>
                                                    <span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($op['NUMERO_CUENTA']) ?></span>
                                                </div>
                                                <?php if(!empty($op['TITULAR_CUENTA'])): ?>
                                                    <div class="small text-muted" style="font-size: 11px;">Titular: <?= htmlspecialchars($op['TITULAR_CUENTA']) ?> <?= !empty($op['TITULAR_DOC']) ? '(' . htmlspecialchars($op['TITULAR_DOC']) . ')' : '' ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" style="font-size: 11px;">⚠️ Sin cuenta configurada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= ($op['ESTADO'] ?? 'ACTIVO') === 'ACTIVO' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= htmlspecialchars($op['ESTADO'] ?? 'ACTIVO') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarOperador<?= $op['ID_OPERADOR'] ?>" title="Editar Operador / Cuentas Bancarias">✏️</button>
                                                <?php if($op['ID_OPERADOR'] > 1): ?>
                                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarOperador<?= $op['ID_OPERADOR'] ?>" title="Eliminar Operador">🗑️</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: PARQUE AUTOMOTOR Y BUSES -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 border-top border-primary border-3">
                    <div class="card-header bg-white"><h6 class="mb-0 text-primary fw-bold">🚌 Registrar Unidad (Bus)</h6></div>
                    <div class="card-body">
                        <form action="/suramerica2030/flota/guardarBus" method="POST" data-live="true" data-live-refresh="#tab-buses" data-live-reset="true">
                            <label class="form-label small text-muted mb-1 fw-bold">🏢 Operador Propietario / Socio</label>
                            <select name="id_operador" class="form-select form-select-sm fw-bold border-primary mb-3">
                                <?= $generarOpcionesOperadores(1) ?>
                            </select>

                            <h6 class="text-secondary border-bottom pb-1">Datos de la Unidad</h6>
                            <div class="row mb-2">
                                <div class="col-6"><label class="form-label small">Marca</label><input type="text" name="marca" class="form-control form-control-sm" required placeholder="Ej: Scania, Volvo"></div>
                                <div class="col-6"><label class="form-label small">Modelo</label><input type="text" name="modelo" class="form-control form-control-sm" required placeholder="Ej: K410, Marcopolo"></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6"><label class="form-label small">Placa</label><input type="text" name="placa" class="form-control form-control-sm text-uppercase fw-bold" required placeholder="Ej: ABC-1234"></div>
                                <div class="col-6"><label class="form-label small">Asientos</label><input type="number" name="asientos" class="form-control form-control-sm" required min="10" max="80" value="44"></div>
                            </div>

                            <h6 class="text-secondary border-bottom pb-1">Chofer o Contacto Directo de la Unidad (Opcional)</h6>
                            <div class="row mb-2">
                                <div class="col-6"><label class="form-label small">Nombre</label><input type="text" name="socio_nombre" class="form-control form-control-sm" placeholder="Opcional"></div>
                                <div class="col-6"><label class="form-label small">Apellido</label><input type="text" name="socio_apellido" class="form-control form-control-sm" placeholder="Opcional"></div>
                            </div>
                            <div class="mb-3"><label class="form-label small">Teléfono Chofer</label><input type="text" name="socio_telefono" class="form-control form-control-sm" placeholder="Ej: 0991234567"></div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">Registrar Unidad</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 table-responsive">
                    <div class="card-header bg-white py-2"><h6 class="mb-0 text-dark fw-bold">🚌 Flota de Buses y Unidades Asignadas</h6></div>
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Bus / Unidad</th>
                                <th>Placa</th>
                                <th>Operador / Socio Asignado</th>
                                <th>Contacto Chofer</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($buses as $b): ?>
                                <tr id="fila_bus_<?= $b['ID_BUS'] ?>">

                                    <td>
                                        <strong><?= htmlspecialchars($b['BUS_MARCA']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($b['BUS_MODELO']) ?> (<?= $b['BUS_ASIENTOS'] ?> Asientos)</small>
                                    </td>
                                    <td><span class="badge bg-dark px-2 py-1 fs-6"><?= htmlspecialchars($b['BUS_PLACA']) ?></span></td>
                                    <td>
                                        <div class="fw-bold text-primary">
                                            <?= htmlspecialchars(!empty($b['OPERADOR_NOMBRE']) ? $b['OPERADOR_NOMBRE'] : 'Suramérica 2030 (Flota Propia)') ?>
                                        </div>
                                        <?php if(!empty($b['OPERADOR_BANCO'])): ?>
                                            <small class="text-muted d-block">🏦 <?= htmlspecialchars($b['OPERADOR_BANCO']) ?>: <?= htmlspecialchars($b['OPERADOR_CUENTA']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($b['SOC_NOMBRE'])): ?>
                                            👤 <?= htmlspecialchars(($b['SOC_NOMBRE'] ?? '') . ' ' . ($b['SOC_APELLIDOS'] ?? '')) ?><br>
                                            <small class="text-success">📞 <?= htmlspecialchars($b['SOC_TELEFONO'] ?? '') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin chofer registrado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarBus<?= $b['ID_BUS'] ?>">✏️</button>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarBus<?= $b['ID_BUS'] ?>">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL REGISTRAR NUEVO OPERADOR / SOCIO                   -->
        <!-- ======================================================== -->
        <div class="modal fade text-start" id="modalCrearOperador" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form class="modal-content" action="/suramerica2030/flota/guardarOperador" method="POST" data-live="true" data-live-refresh="#tab-buses" data-live-reset="true">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">🏢 Registrar Nuevo Operador / Socio</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nombre Comercial *</label>
                                <input type="text" name="nombre_comercial" class="form-control form-control-sm fw-bold text-primary" required placeholder="Ej: Panamericana de Transportes, Rutas Andinas">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Razón Social</label>
                                <input type="text" name="razon_social" class="form-control form-control-sm" placeholder="Ej: Transportes Panamericana Cía. Ltda.">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tipo de Operador</label>
                                <select name="tipo_operador" class="form-select form-select-sm fw-bold">
                                    <option value="SOCIO_AFILIADO">🤝 Socio Afiliado Propietario</option>
                                    <option value="EMPRESA_ALIADA">⭐ Empresa Aliada / Interconexión</option>
                                    <option value="PROPIO">🏢 Flota Propia (Suramérica 2030)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">RUC / Cédula / Identificación</label>
                                <input type="text" name="identificacion" class="form-control form-control-sm font-monospace" placeholder="Ej: 1792123456001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Estado</label>
                                <select name="estado" class="form-select form-select-sm">
                                    <option value="ACTIVO" selected>✅ Activo (Disponible para rutas y buses)</option>
                                    <option value="INACTIVO">❌ Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Teléfono de Contacto</label>
                                <input type="text" name="telefono" class="form-control form-control-sm" placeholder="Ej: +593 99 123 4567">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Correo Electrónico</label>
                                <input type="email" name="correo" class="form-control form-control-sm" placeholder="Ej: gerencia@empresa.com">
                            </div>
                        </div>

                        <h6 class="text-primary border-bottom pb-1 mt-4 fw-bold">🏦 Datos Bancarios para Liquidaciones y Transferencias</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Banco</label>
                                <input type="text" name="banco" class="form-control form-control-sm" placeholder="Ej: Banco Pichincha, Bancolombia, Banesco">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tipo de Cuenta</label>
                                <select name="tipo_cuenta" class="form-select form-select-sm">
                                    <option value="CORRIENTE" selected>Cuenta Corriente</option>
                                    <option value="AHORROS">Cuenta de Ahorros</option>
                                    <option value="OTRO">Otro / Billetera Virtual</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Número de Cuenta</label>
                                <input type="text" name="numero_cuenta" class="form-control form-control-sm font-monospace fw-bold" placeholder="Ej: 2100123456">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Titular de la Cuenta</label>
                                <input type="text" name="titular_cuenta" class="form-control form-control-sm" placeholder="Nombre completo del titular">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Cédula / RUC del Titular</label>
                                <input type="text" name="titular_doc" class="form-control form-control-sm font-monospace" placeholder="Doc de identidad del titular">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Observaciones / Notas de Operación</label>
                            <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Notas sobre el acuerdo, pagos, convenios o tramos asignados..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer p-2 bg-light">
                        <button type="submit" class="btn btn-primary fw-bold w-100">Guardar Operador / Socio</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODALES DE EDITAR / ELIMINAR OPERADORES                  -->
        <!-- ======================================================== -->
        <?php foreach($operadores as $op): ?>
            <!-- MODAL EDITAR OPERADOR -->
            <div class="modal fade text-start" id="modalEditarOperador<?= $op['ID_OPERADOR'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form class="modal-content" action="/suramerica2030/flota/editarOperador" method="POST" data-live="true" data-live-refresh="#tab-buses" data-live-row="#fila_operador_<?= $op['ID_OPERADOR'] ?>">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">✏️ Editar Operador #<?= $op['ID_OPERADOR'] ?>: <?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_operador" value="<?= $op['ID_OPERADOR'] ?>">

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nombre Comercial *</label>
                                    <input type="text" name="nombre_comercial" class="form-control form-control-sm fw-bold text-primary" value="<?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Razón Social</label>
                                    <input type="text" name="razon_social" class="form-control form-control-sm" value="<?= htmlspecialchars($op['RAZON_SOCIAL'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tipo de Operador</label>
                                    <select name="tipo_operador" class="form-select form-select-sm fw-bold">
                                        <option value="SOCIO_AFILIADO" <?= ($op['TIPO_OPERADOR'] === 'SOCIO_AFILIADO') ? 'selected' : '' ?>>🤝 Socio Afiliado Propietario</option>
                                        <option value="EMPRESA_ALIADA" <?= ($op['TIPO_OPERADOR'] === 'EMPRESA_ALIADA') ? 'selected' : '' ?>>⭐ Empresa Aliada / Interconexión</option>
                                        <option value="PROPIO" <?= ($op['TIPO_OPERADOR'] === 'PROPIO') ? 'selected' : '' ?>>🏢 Flota Propia (Suramérica 2030)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">RUC / Cédula / Identificación</label>
                                    <input type="text" name="identificacion" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($op['IDENTIFICACION'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Estado</label>
                                    <select name="estado" class="form-select form-select-sm">
                                        <option value="ACTIVO" <?= ($op['ESTADO'] === 'ACTIVO') ? 'selected' : '' ?>>✅ Activo</option>
                                        <option value="INACTIVO" <?= ($op['ESTADO'] === 'INACTIVO') ? 'selected' : '' ?>>❌ Inactivo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Teléfono de Contacto</label>
                                    <input type="text" name="telefono" class="form-control form-control-sm" value="<?= htmlspecialchars($op['TELEFONO'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Correo Electrónico</label>
                                    <input type="email" name="correo" class="form-control form-control-sm" value="<?= htmlspecialchars($op['CORREO'] ?? '') ?>">
                                </div>
                            </div>

                            <h6 class="text-primary border-bottom pb-1 mt-4 fw-bold">🏦 Datos Bancarios para Liquidaciones y Transferencias</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Banco</label>
                                    <input type="text" name="banco" class="form-control form-control-sm" value="<?= htmlspecialchars($op['BANCO'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tipo de Cuenta</label>
                                    <select name="tipo_cuenta" class="form-select form-select-sm">
                                        <option value="CORRIENTE" <?= ($op['TIPO_CUENTA'] === 'CORRIENTE') ? 'selected' : '' ?>>Cuenta Corriente</option>
                                        <option value="AHORROS" <?= ($op['TIPO_CUENTA'] === 'AHORROS') ? 'selected' : '' ?>>Cuenta de Ahorros</option>
                                        <option value="OTRO" <?= ($op['TIPO_CUENTA'] === 'OTRO') ? 'selected' : '' ?>>Otro / Billetera Virtual</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Número de Cuenta</label>
                                    <input type="text" name="numero_cuenta" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($op['NUMERO_CUENTA'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Titular de la Cuenta</label>
                                    <input type="text" name="titular_cuenta" class="form-control form-control-sm" value="<?= htmlspecialchars($op['TITULAR_CUENTA'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Cédula / RUC del Titular</label>
                                    <input type="text" name="titular_doc" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($op['TITULAR_DOC'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Observaciones / Notas de Operación</label>
                                <textarea name="observaciones" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($op['OBSERVACIONES'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer p-2 bg-light">
                            <button type="submit" class="btn btn-primary fw-bold w-100">Actualizar Información del Operador</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL ELIMINAR OPERADOR -->
            <?php if($op['ID_OPERADOR'] > 1): ?>
                <div class="modal fade text-start" id="modalEliminarOperador<?= $op['ID_OPERADOR'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <form class="modal-content" action="/suramerica2030/flota/eliminarOperador" method="POST" data-live="true" data-live-refresh="#tab-buses">
                            <div class="modal-header bg-danger text-white">
                                <h6 class="modal-title">Eliminar Operador</h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <input type="hidden" name="id_operador" value="<?= $op['ID_OPERADOR'] ?>">
                                <p class="mb-1">¿Estás seguro de eliminar permanentemente al operador?</p>
                                <strong class="text-danger fs-6"><?= htmlspecialchars($op['NOMBRE_COMERCIAL']) ?></strong>
                                <div class="alert alert-warning p-2 mt-2 mb-0" style="font-size: 11px;">
                                    Si tiene buses o rutas asignadas, se reasignarán a la Flota Propia para proteger la integridad del sistema.
                                </div>
                            </div>
                            <div class="modal-footer p-2">
                                <button type="submit" class="btn btn-danger w-100 fw-bold">Sí, Eliminar Definitivamente</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- ======================================================== -->
        <!-- MODALES DE BUSES Y SOCIOS (FUERA DE LA TABLA)           -->
        <!-- ======================================================== -->
        <?php foreach($buses as $b): ?>
            <!-- MODAL ELIMINAR BUS -->
            <div class="modal fade text-start" id="modalEliminarBus<?= $b['ID_BUS'] ?>" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title fs-6">Eliminar Unidad #<?= $b['ID_BUS'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/suramerica2030/flota/eliminarBus" method="POST" data-live="true" data-live-refresh="#tab-buses">
                            <div class="modal-body text-center">
                                <input type="hidden" name="id_bus" value="<?= $b['ID_BUS'] ?>">
                                <p class="mb-2">¿Confirmas eliminar este bus?</p>
                                <strong class="text-danger fs-5"><?= htmlspecialchars($b['BUS_PLACA']) ?></strong><br>
                                <small><?= htmlspecialchars($b['BUS_MARCA']) ?></small>
                                
                                <div class="alert alert-warning p-2 mt-3 mb-0" style="font-size: 11px;">
                                    Solo podrás eliminarlo si no tiene operaciones registradas.
                                </div>
                            </div>
                            <div class="modal-footer p-2">
                                <button type="submit" class="btn btn-danger w-100 fw-bold">Sí, Eliminar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL EDITAR BUS -->
            <div class="modal fade text-start" id="modalEditarBus<?= $b['ID_BUS'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Editar Unidad #<?= $b['ID_BUS'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/suramerica2030/flota/editarBus" method="POST" data-live="true" data-live-refresh="#tab-buses" data-live-row="#fila_bus_<?= $b['ID_BUS'] ?>">
                            <div class="modal-body">
                                <input type="hidden" name="id_bus" value="<?= $b['ID_BUS'] ?>">
                                
                                <label class="form-label small fw-bold text-dark">🏢 Operador Propietario / Socio</label>
                                <select name="id_operador" class="form-select form-select-sm fw-bold border-primary mb-3">
                                    <?= $generarOpcionesOperadores($b['ID_OPERADOR'] ?? 1) ?>
                                </select>


                                <h6 class="text-secondary border-bottom pb-1">Datos del Bus</h6>
                                <div class="row mb-2">
                                    <div class="col-6"><label class="form-label small">Marca</label><input type="text" name="marca" class="form-control form-control-sm" value="<?= htmlspecialchars($b['BUS_MARCA']) ?>" required></div>
                                    <div class="col-6"><label class="form-label small">Modelo</label><input type="text" name="modelo" class="form-control form-control-sm" value="<?= htmlspecialchars($b['BUS_MODELO']) ?>" required></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6"><label class="form-label small">Placa</label><input type="text" name="placa" class="form-control form-control-sm text-uppercase fw-bold" value="<?= htmlspecialchars($b['BUS_PLACA']) ?>" required></div>
                                    <div class="col-6"><label class="form-label small">Asientos</label><input type="number" name="asientos" class="form-control form-control-sm" value="<?= $b['BUS_ASIENTOS'] ?>" required></div>
                                </div>

                                <h6 class="text-secondary border-bottom pb-1 mt-3">Chofer o Contacto Directo de la Unidad (Opcional)</h6>
                                <div class="row mb-2">
                                    <div class="col-6"><label class="form-label small">Nombre</label><input type="text" name="socio_nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($b['SOC_NOMBRE'] ?? '') ?>"></div>
                                    <div class="col-6"><label class="form-label small">Apellido</label><input type="text" name="socio_apellido" class="form-control form-control-sm" value="<?= htmlspecialchars($b['SOC_APELLIDOS'] ?? '') ?>"></div>
                                </div>
                                <div class="mb-3"><label class="form-label small">Teléfono Chofer</label><input type="text" name="socio_telefono" class="form-control form-control-sm" value="<?= htmlspecialchars($b['SOC_TELEFONO'] ?? '') ?>"></div>
                            </div>
                            <div class="modal-footer p-2">
                                <button type="submit" class="btn btn-primary w-100 fw-bold">Actualizar Información de la Unidad</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
