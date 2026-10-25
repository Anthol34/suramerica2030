<!-- ======================================================== -->
<!-- PESTAÑA 2: FLOTA DE BUSES Y ALIADOS OPERADORES / SOCIOS  -->
<!-- ======================================================== -->
<div class="tab-pane fade <?= (in_array($tabActiva, ['flota', 'buses', 'operadores'])) ? 'show active' : '' ?>" id="tab-flota">
    <!-- ALIAS DE COMPATIBILIDAD PARA LIVE REFRESH -->
    <div id="tab-buses" class="d-none"></div>

    <?php
        $totalOps = count($operadores ?? []);
        $totalBuses = count($buses ?? []);
        $totalAsientos = 0;
        foreach ($buses ?? [] as $b) {
            $totalAsientos += intval($b['BUS_ASIENTOS'] ?? 0);
        }
        $opsPropios = 0;
        $opsSocios = 0;
        $opsAliados = 0;
        foreach ($operadores ?? [] as $op) {
            $tipo = $op['TIPO_OPERADOR'] ?? 'SOCIO_AFILIADO';
            if ($tipo === 'PROPIO') $opsPropios++;
            elseif ($tipo === 'SOCIO_AFILIADO') $opsSocios++;
            elseif ($tipo === 'EMPRESA_ALIADA') $opsAliados++;
        }
    ?>

    <!-- BANNER DE FLOTA Y ALIADOS -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border-left: 5px solid #2563eb !important;">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center py-3">
            <div class="mb-2 mb-md-0">
                <h5 class="fw-bold text-primary mb-1">🤝 Alianzas, Socios Operadores y Parque Automotor</h5>
                <p class="text-muted small mb-0">
                    Administra las empresas asociadas (Panamericana en Ecuador, Expresos Suramérica en Venezuela, Socios Propietarios y Enlaces Locales), sus cuentas bancarias para liquidación en Caja y los buses asignados a cada ruta.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearOperador">
                    + Registrar Nuevo Operador / Socio
                </button>
                <button class="btn btn-outline-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarBusDirecto">
                    + Registrar Bus / Unidad
                </button>
            </div>
        </div>
    </div>

    <!-- TARJETAS KPIS DE FLOTA Y OPERADORES -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-3 bg-white text-center">
                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Operadores / Socios</small>
                <span class="fs-4 fw-bold text-primary"><?= $totalOps ?></span>
                <small class="text-secondary d-block" style="font-size: 11px;">
                    <?= $opsAliados ?> aliadas · <?= $opsSocios ?> socios · <?= $opsPropios ?> propia
                </small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-3 bg-white text-center">
                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Buses en Flota</small>
                <span class="fs-4 fw-bold text-success"><?= $totalBuses ?></span>
                <small class="text-secondary d-block" style="font-size: 11px;">Unidades activas registradas</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-3 bg-white text-center">
                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Capacidad Total Asientos</small>
                <span class="fs-4 fw-bold text-dark"><?= $totalAsientos ?></span>
                <small class="text-secondary d-block" style="font-size: 11px;">Plazas disponibles en red</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-3 bg-white text-center">
                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Liquidación Bancaria</small>
                <?php 
                    $conBanco = 0;
                    foreach ($operadores ?? [] as $op) {
                        if (!empty($op['BANCO'])) $conBanco++;
                    }
                ?>
                <span class="fs-4 fw-bold text-info"><?= $conBanco ?> / <?= $totalOps ?></span>
                <small class="text-secondary d-block" style="font-size: 11px;">Cuentas bancarias listas</small>
            </div>
        </div>
    </div>

    <!-- SUB-PESTAÑAS DE NAVEGACIÓN EN OPERADORES Y ACUERDOS -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border gap-2" id="subTabsFlota" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold py-2 px-3" id="pill-subtab-operadores" data-bs-toggle="pill" data-bs-target="#subtab-operadores" type="button" role="tab" aria-selected="true">
                🏢 Directorio de Operadores y Bancos <span class="badge bg-primary ms-1"><?= $totalOps ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold py-2 px-3" id="pill-subtab-tramos" data-bs-toggle="pill" data-bs-target="#subtab-tramos" type="button" role="tab" aria-selected="false">
                🧩 Acuerdos de Costos por Tramo <span class="badge bg-success ms-1"><?= count($tramosCatalogo ?? []) ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold py-2 px-3" id="pill-subtab-capacidad" data-bs-toggle="pill" data-bs-target="#subtab-capacidad" type="button" role="tab" aria-selected="false">
                🚌 Capacidad Operativa y Buses <span class="badge bg-secondary ms-1"><?= $totalBuses ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="subTabsFlotaContent">
        <!-- ======================================================== -->
        <!-- SUBTAB 1: DIRECTORIO DE OPERADORES Y CUENTAS BANCARIAS   -->
        <!-- ======================================================== -->
        <div class="tab-pane fade show active" id="subtab-operadores" role="tabpanel">
            <div class="card shadow-sm border-0 mb-4 bg-white">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                    <div>
                        <h6 class="text-dark mb-0 fw-bold">🏢 Directorio Maestro de Operadores y Transportistas Aliados</h6>
                        <small class="text-muted">Razones sociales, RUC/Cédula, canales de contacto y cuentas para liquidar en Caja/Finanzas.</small>
                    </div>
                    <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalCrearOperador">
                        + Nuevo Operador
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
                                    <tr><td colspan="8" class="text-center py-4 text-muted">No hay operadores registrados en el sistema.</td></tr>
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
                                                    <button class="btn btn-outline-info text-dark fw-bold" onclick="abrirModalOfertasOperador(<?= $op['ID_OPERADOR'] ?>, '<?= htmlspecialchars(addslashes($op['NOMBRE_COMERCIAL'])) ?>')" title="🗺️ Ver y Editar Oferta de Tramos">
                                                        🗺️ Oferta
                                                    </button>
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
        </div>

        <!-- ======================================================== -->
        <!-- SUBTAB 2: CATÁLOGO MAESTRO DE TRAMOS Y COSTOS ACORDADOS  -->
        <!-- ======================================================== -->
        <div class="tab-pane fade" id="subtab-tramos" role="tabpanel">
            <?php require __DIR__ . '/tab_tramos.php'; ?>
        </div>

        <!-- ======================================================== -->
        <!-- SUBTAB 3: CAPACIDAD OPERATIVA Y BUSES                     -->
        <!-- ======================================================== -->
        <div class="tab-pane fade" id="subtab-capacidad" role="tabpanel">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-0 border-top border-primary border-3">
                        <div class="card-header bg-white"><h6 class="mb-0 text-primary fw-bold">🚌 Registrar Unidad (Bus)</h6></div>
                        <div class="card-body">
                            <form action="/suramerica2030/flota/guardarBus" method="POST" data-live="true" data-live-refresh="#tab-flota, #tab-buses" data-live-reset="true">
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
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-dark fw-bold">🚌 Flota de Buses y Unidades Asignadas</h6>
                            <span class="badge bg-secondary"><?= count($buses) ?> unidades</span>
                        </div>
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
                                <?php if(empty($buses)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay buses registrados aún.</td></tr>
                                <?php else: ?>
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
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Cierra #subTabsFlotaContent -->

    <!-- ======================================================== -->
    <!-- MODAL REGISTRAR NUEVO OPERADOR / SOCIO                   -->
    <!-- ======================================================== -->
    <div class="modal fade text-start" id="modalCrearOperador" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" action="/suramerica2030/flota/guardarOperador" method="POST" data-live="true" data-live-refresh="#tab-flota, #tab-buses" data-live-reset="true">
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
                            <input type="email" name="correo" class="form-control form-control-sm" placeholder="Ej: administracion@panamericana.com">
                        </div>
                    </div>

                    <h6 class="text-secondary border-bottom pb-1 mt-4">🏦 Datos Bancarios para Liquidación (Caja y Finanzas)</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Banco</label>
                            <input type="text" name="banco" class="form-control form-control-sm" placeholder="Ej: Banco Pichincha, Banesco">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tipo de Cuenta</label>
                            <select name="tipo_cuenta" class="form-select form-select-sm">
                                <option value="Corriente">Corriente</option>
                                <option value="Ahorros">Ahorros</option>
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
    <!-- MODAL ALTERNATIVO: REGISTRAR BUS DESDE BOTON SUPERIOR    -->
    <!-- ======================================================== -->
    <div class="modal fade text-start" id="modalRegistrarBusDirecto" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" action="/suramerica2030/flota/guardarBus" method="POST" data-live="true" data-live-refresh="#tab-flota, #tab-buses" data-live-reset="true">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">🚌 Registrar Nueva Unidad (Bus)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                </div>
                <div class="modal-footer p-2 bg-light">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Registrar Unidad</button>
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
                <form class="modal-content" action="/suramerica2030/flota/editarOperador" method="POST" data-live="true" data-live-refresh="#tab-flota, #tab-buses" data-live-row="#fila_operador_<?= $op['ID_OPERADOR'] ?>">
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
                                    <option value="SOCIO_AFILIADO" <?= ($op['TIPO_OPERADOR'] == 'SOCIO_AFILIADO') ? 'selected' : '' ?>>🤝 Socio Afiliado Propietario</option>
                                    <option value="EMPRESA_ALIADA" <?= ($op['TIPO_OPERADOR'] == 'EMPRESA_ALIADA') ? 'selected' : '' ?>>⭐ Empresa Aliada / Interconexión</option>
                                    <option value="PROPIO" <?= ($op['TIPO_OPERADOR'] == 'PROPIO') ? 'selected' : '' ?>>🏢 Flota Propia (Suramérica 2030)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">RUC / Cédula / Identificación</label>
                                <input type="text" name="identificacion" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($op['IDENTIFICACION'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Estado</label>
                                <select name="estado" class="form-select form-select-sm">
                                    <option value="ACTIVO" <?= ($op['ESTADO'] == 'ACTIVO') ? 'selected' : '' ?>>✅ Activo</option>
                                    <option value="INACTIVO" <?= ($op['ESTADO'] == 'INACTIVO') ? 'selected' : '' ?>>❌ Inactivo</option>
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

                        <h6 class="text-secondary border-bottom pb-1 mt-4">🏦 Datos Bancarios para Liquidación</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Banco</label>
                                <input type="text" name="banco" class="form-control form-control-sm" value="<?= htmlspecialchars($op['BANCO'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tipo de Cuenta</label>
                                <select name="tipo_cuenta" class="form-select form-select-sm">
                                    <option value="Corriente" <?= (($op['TIPO_CUENTA'] ?? '') == 'Corriente') ? 'selected' : '' ?>>Corriente</option>
                                    <option value="Ahorros" <?= (($op['TIPO_CUENTA'] ?? '') == 'Ahorros') ? 'selected' : '' ?>>Ahorros</option>
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
                    <form class="modal-content" action="/suramerica2030/flota/eliminarOperador" method="POST" data-live="true" data-live-refresh="#tab-flota, #tab-buses">
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
                <form class="modal-content" action="/suramerica2030/flota/eliminarBus" method="POST" data-live="true" data-live-refresh="#tab-flota, #tab-buses">
                    <div class="modal-header bg-danger text-white">
                        <h6 class="modal-title">Eliminar Unidad</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <input type="hidden" name="id_bus" value="<?= $b['ID_BUS'] ?>">
                        <p class="mb-1">¿Estás seguro de eliminar permanentemente la unidad?</p>
                        <strong class="text-danger fs-6"><?= htmlspecialchars($b['BUS_PLACA']) ?></strong>
                        <div class="alert alert-warning p-2 mt-2 mb-0" style="font-size: 11px;">
                            Al eliminar esta unidad se borrará también su socio asignado y se desvinculará de los viajes programados.
                        </div>
                    </div>
                    <div class="modal-footer p-2">
                        <button type="submit" class="btn btn-danger w-100 fw-bold">Sí, Eliminar Unidad</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDITAR BUS -->
        <div class="modal fade text-start" id="modalEditarBus<?= $b['ID_BUS'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" action="/suramerica2030/flota/editarBus" method="POST" data-live="true" data-live-refresh="#tab-flota, #tab-buses" data-live-row="#fila_bus_<?= $b['ID_BUS'] ?>">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">✏️ Modificar Unidad: <?= htmlspecialchars($b['BUS_PLACA']) ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_bus" value="<?= $b['ID_BUS'] ?>">
                        <input type="hidden" name="id_socio" value="<?= $b['ID_SOCIO'] ?? '' ?>">

                        <label class="form-label small text-muted mb-1 fw-bold">🏢 Operador Propietario / Socio Asignado</label>
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
    <?php endforeach; ?>

    <!-- ======================================================== -->
    <!-- MODAL MAESTRO-DETALLE: OFERTA DE TRAMOS POR OPERADOR     -->
    <!-- ======================================================== -->
    <div class="modal fade" id="modalOfertasOperador" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4">🗺️</span>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0" id="modalOfertasOperadorTitulo">Oferta de Tramos del Operador</h5>
                            <small class="text-white-50" id="modalOfertasOperadorSubtitulo">Configura frecuencias, días de salida, hora y costos netos de liquidación.</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 bg-light">
                    <!-- BARRA DE ACCIÓN SUPERIOR -->
                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="badge bg-primary fs-6 px-3 py-1" id="badgeOperadorOfertaNombre">Operador</span>
                                <span class="badge bg-secondary ms-1" id="badgeOperadorTotalOfertas">0 tramos cubiertos</span>
                            </div>
                            <button class="btn btn-sm btn-outline-primary fw-bold" onclick="toggleFormularioNuevoTramoOferta()">
                                ➕ Añadir Tramo a este Operador
                            </button>
                        </div>

                        <!-- FORMULARIO DESPLEGABLE PARA ASIGNAR NUEVO TRAMO -->
                        <div id="formAgregarTramoOferta" class="mt-3 p-3 border rounded bg-light" style="display: none;">
                            <h6 class="fw-bold text-primary mb-2">Asignar Nuevo Tramo de Catálogo a la Oferta</h6>
                            <form id="formNuevoTramoOfertaOperador" onsubmit="guardarNuevoTramoOferta(event)">
                                <input type="hidden" name="id_operador" id="oferta_nuevo_id_operador" value="">
                                <div class="row g-2 mb-2">
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold mb-0">Tramo Físico (Catálogo):</label>
                                        <select name="id_tramo_catalogo" id="oferta_select_tramo_cat" class="form-select form-select-sm fw-bold border-primary" required onchange="alSeleccionarTramoEnOferta(this)">
                                            <option value="">-- Seleccionar Tramo --</option>
                                            <?php foreach ($tramosCatalogo ?? [] as $tc): ?>
                                                <option value="<?= $tc['ID_TRAMO_CATALOGO'] ?>" data-costo="<?= $tc['COSTO_OPERADOR'] ?>" data-pvp="<?= $tc['PVP_SUGERIDO'] ?>">
                                                    <?= htmlspecialchars($tc['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($tc['DESTINO_TRAMO']) ?> (<?= $tc['PAIS_TRAMO'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-danger mb-0">Costo Neto Liquidación ($):</label>
                                        <input type="number" step="0.50" min="0" name="costo_neto" id="oferta_nuevo_costo" class="form-control form-control-sm fw-bold text-danger" required placeholder="0.00">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-primary mb-0">PVP Sugerido ($):</label>
                                        <input type="number" step="0.50" min="0" name="pvp_sugerido" id="oferta_nuevo_pvp" class="form-control form-control-sm fw-bold text-primary" required placeholder="0.00">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold mb-0">Hora Salida:</label>
                                        <input type="time" name="hora_salida" class="form-control form-control-sm" value="08:00" required>
                                    </div>
                                </div>
                                <div class="row g-2 mb-2 align-items-center bg-white p-2 rounded border">
                                    <div class="col-md-7">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="venta_directa" value="1" id="oferta_nuevo_venta_directa" onchange="togglePrecioDirectoOferta(this.checked)">
                                            <label class="form-check-label small fw-bold text-dark" for="oferta_nuevo_venta_directa">
                                                🏷️ ¿Vender también como Ruta Directa al público en Boletería?
                                            </label>
                                        </div>
                                        <small class="text-muted d-block" style="font-size:11px;">Crea y sincroniza automáticamente un producto comercial directo en el módulo de Pasajes.</small>
                                    </div>
                                    <div class="col-md-5" id="contenedorPrecioDirectoOferta" style="display: none;">
                                        <label class="form-label small fw-bold text-success mb-0">Precio Directo al Público ($):</label>
                                        <input type="number" step="0.50" min="0" name="precio_tramo_solo" id="oferta_nuevo_precio_solo" class="form-control form-control-sm fw-bold text-success" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                                    <div>
                                        <label class="form-label small fw-bold d-block mb-1">Días de Operación:</label>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php foreach (['LUN'=>'L','MAR'=>'M','MIE'=>'X','JUE'=>'J','VIE'=>'V','SAB'=>'S','DOM'=>'D'] as $codDia => $letraDia): ?>
                                                <input type="checkbox" class="btn-check" name="dias_salida[]" value="<?= $codDia ?>" id="dia_nuevo_<?= $codDia ?>" checked>
                                                <label class="btn btn-outline-secondary py-0 px-2" for="dia_nuevo_<?= $codDia ?>"><?= $letraDia ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleFormularioNuevoTramoOferta()">Cancelar</button>
                                        <button type="submit" class="btn btn-sm btn-primary fw-bold px-3">Guardar Tramo en Oferta</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TABLA REACTIVA DE TRAMOS CUBIERTOS -->
                    <div class="card border-0 shadow-sm bg-white overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaOfertasOperador">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tramo Físico</th>
                                        <th>Días de Salida (Frecuencia)</th>
                                        <th class="text-center">Hora</th>
                                        <th class="text-end">Costo Liquidación</th>
                                        <th class="text-end">PVP Sugerido</th>
                                        <th class="text-center">Venta Directa</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaOfertasOperador">
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Cargando ofertas...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
