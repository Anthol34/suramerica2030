<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h3 class="text-primary fw-bold mb-0">📄 Manifiestos del Sistema</h3>
        <small class="text-muted">Centro unificado de manifiestos oficiales de pasajeros, equipajes y carga logística</small>
    </div>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show shadow-sm">
        <?php 
            if($_GET['status'] == 'success_email') {
                echo '✉️ ¡Manifiesto oficial enviado por correo exitosamente!';
            } elseif($_GET['status'] == 'success_unificar') {
                if(!empty($_GET['fecha_v']) && !empty($_GET['ruta_v']) && !empty($_GET['bus_v'])) {
                    echo '🚌 <strong>¡Viajes Integrados con Éxito!</strong> Se han unificado los pasajeros en un solo bus y viaje oficial.';
                    echo ' <a href="/suramerica2030/manifiesto/imprimir?fecha='.htmlspecialchars($_GET['fecha_v']).'&ruta='.htmlspecialchars($_GET['ruta_v']).'&bus='.htmlspecialchars($_GET['bus_v']).'" target="_blank" class="btn btn-sm btn-dark ms-2 fw-bold">🖨️ Ver Manifiesto de Pasajeros Ahora</a>';
                } elseif(!empty($_GET['fecha_d']) && !empty($_GET['ruta_d'])) {
                    echo '📦 <strong>¡Despachos Integrados con Éxito!</strong> Se han unificado los paquetes en un solo despacho y manifiesto de carga.';
                    echo ' <a href="/suramerica2030/manifiesto/imprimirCarga?fecha='.htmlspecialchars($_GET['fecha_d']).'&ruta_id='.htmlspecialchars($_GET['ruta_d']).'" target="_blank" class="btn btn-sm btn-dark ms-2 fw-bold">🖨️ Ver Manifiesto de Carga Ahora</a>';
                } else {
                    echo '✅ Registros integrados exitosamente.';
                }
            } else {
                echo '❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Operación fallida.');
            }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- NAVEGACIÓN PRINCIPAL DE PESTAÑAS -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= ($tabActive !== 'carga') ? 'active fw-bold' : '' ?>" href="/suramerica2030/manifiesto?tab=viaje">
            🚌 Manifiestos de Pasajeros y Viajes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($tabActive === 'carga') ? 'active fw-bold' : '' ?>" href="/suramerica2030/manifiesto?tab=carga">
            📦 Manifiesto de Carga y Encomiendas
        </a>
    </li>
</ul>

<!-- ======================================================== -->
<!-- PESTAÑA 1: MANIFIESTOS DE PASAJEROS Y VIAJES             -->
<!-- ======================================================== -->
<?php if($tabActive !== 'carga'): ?>

    <!-- FILTRO DE FECHAS PARA VIAJES -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <form action="/suramerica2030/manifiesto" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="viaje">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-secondary">Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio ?? date('Y-m-01')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-secondary">Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin ?? date('Y-m-t')) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">🔍 Filtrar Viajes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- BANNER DE PASAJEROS DE HOY / EN RUTA (BONDAD DE ENCOMIENDAS ADAPTADA A VIAJES) -->
    <div class="card shadow-sm border-0 border-start border-primary border-4 mb-4 bg-white">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 text-primary fw-bold">🚌 Manifiesto General de Pasajeros de Hoy / En Ruta</h5>
                <p class="mb-0 text-muted small">
                    Genera el documento consolidado oficial con <strong>todos</strong> los pasajeros viajando en ruta hoy (Total hoy: <span class="badge bg-primary fs-6"><?= $totalPasajerosHoy ?? 0 ?></span> pasajero<?= ($totalPasajerosHoy ?? 0) == 1 ? '' : 's' ?>).
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPrintHoy">
                    🖨️ Ver / Imprimir Pasajeros de Hoy
                </button>
                <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCorreoHoy">
                    ✉️ Enviar por Correo
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL PRINT HOY -->
    <div class="modal fade text-start" id="modalPrintHoy" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h6 class="modal-title">🖨️ Pasajeros de Hoy (PDF)</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/suramerica2030/manifiesto/imprimir" method="GET" target="_blank">
                    <input type="hidden" name="hoy" value="1">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small mb-3">
                            Contiene todos los pasajeros con viaje programado para <strong><?= date('d/m/Y') ?></strong>.
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold text-secondary mb-1">Modalidad Logística:</label>
                            <select name="tipo_logistica" class="form-select border-dark" required>
                                <option value="ESTANDAR">🚐 Estándar (Directo Suramérica)</option>
                                <option value="PANAMERICANA">🚌 Delegado a Panamericana (Quito-Cúcuta)</option>
                                <option value="CONEXIONES">🔄 Conexiones Múltiples (Manta-Tulcán-Ipiales...)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer p-1">
                        <button type="submit" class="btn btn-dark w-100 fw-bold" onclick="bootstrap.Modal.getInstance(document.getElementById('modalPrintHoy')).hide();">Generar PDF Oficial</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL CORREO HOY -->
    <div class="modal fade text-start" id="modalCorreoHoy" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title">✉️ Enviar Manifiesto de Pasajeros de Hoy</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/suramerica2030/manifiesto/enviarManifiestoHoy" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small mb-3">
                            Se enviará el manifiesto con los <strong><?= $totalPasajerosHoy ?? 0 ?></strong> pasajeros viajando el día de hoy.
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold text-secondary mb-1">Modalidad Logística (Membrete):</label>
                            <select name="tipo_logistica" class="form-select border-primary" required>
                                <option value="ESTANDAR">🚐 Estándar (Directo Suramérica)</option>
                                <option value="PANAMERICANA">🚌 Delegado a Panamericana (Quito-Cúcuta)</option>
                                <option value="CONEXIONES">🔄 Conexiones Múltiples (Manta-Tulcán-Ipiales...)</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold text-secondary mb-1">1. Filtrar por Sucursal:</label>
                                <select class="form-select border-primary" id="suc_viajes_hoy" onchange="filtrarPersonalViajesHoy()">
                                    <option value="">-- Todas las sucursales --</option>
                                    <?php 
                                    if(!empty($directorioCorreos)):
                                        $ciudadesUnicas = array_unique(array_column($directorioCorreos, 'CIUDAD'));
                                        foreach($ciudadesUnicas as $ciu): 
                                    ?>
                                        <option value="<?= htmlspecialchars($ciu) ?>">📍 <?= htmlspecialchars($ciu) ?></option>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-secondary mb-1">2. Elegir Personal:</label>
                                <select class="form-select border-primary" id="per_viajes_hoy" onchange="document.getElementById('correo_destino_viajes_hoy').value = this.value;">
                                    <option value="">-- Seleccione empleado --</option>
                                    <?php if(!empty($directorioCorreos)): foreach($directorioCorreos as $uc): ?>
                                        <option value="<?= htmlspecialchars($uc['EMAIL']) ?>" data-sucursal="<?= htmlspecialchars($uc['CIUDAD']) ?>">
                                            👤 <?= htmlspecialchars($uc['NOMBRE']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="fw-bold text-secondary mb-1">3. Confirmar Correo de Destino:</label>
                            <input type="email" name="correo_destino" id="correo_destino_viajes_hoy" class="form-control fw-bold text-primary" required placeholder="ejemplo@frontera.com">
                            <small class="text-muted" style="font-size:11px;">El PDF de pasajeros de hoy se adjuntará automáticamente.</small>
                        </div>
                    </div>
                    <div class="modal-footer p-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-3" onclick="if(this.form.checkValidity()) this.innerHTML='Enviando PDF... ⏳';">Enviar Manifiesto Ahora</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TABLA DE HISTORIAL DE VIAJES PROGRAMADOS -->
    <div class="card shadow-sm border-0 border-top border-primary border-3 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0 text-primary fw-bold">📋 Historial de Viajes Programados</h5>
                <span class="badge bg-secondary rounded-pill"><?= !empty($viajes) ? count($viajes) : 0 ?></span>
            </div>

            <!-- TOOLBAR DINÁMICO DE VIAJES SELECCIONADOS (BONDAD DE ENCOMIENDAS) -->
            <div id="toolbarViajes" class="d-none align-items-center gap-2 flex-wrap">
                <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm" id="badgeViajesCount">0 seleccionados</span>
                <button type="button" class="btn btn-sm btn-primary fw-bold shadow-sm" onclick="abrirModalUnificarViajes()">
                    🔗 Integrar en un solo Bus / Viaje
                </button>
                <button type="button" class="btn btn-sm btn-outline-dark fw-bold shadow-sm" onclick="abrirModalPrintConsolidadoViajes()">
                    🖨️ Ver / Imprimir Consolidado
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold shadow-sm" onclick="imprimirEquipajeConsolidadoViajes()">
                    🧳 Equipaje Consolidado
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold shadow-sm" onclick="abrirModalCorreoConsolidadoViajes()">
                    ✉️ Enviar Correo Consolidado
                </button>
            </div>
        </div>

        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 44px;">
                            <input type="checkbox" id="chkSelectAllViajes" class="form-check-input" onchange="toggleSelectAllViajes(this)" title="Seleccionar todos los viajes">
                        </th>
                        <th>Fecha del Viaje</th>
                        <th>Itinerario Original</th>
                        <th>Bus Asignado</th>
                        <th class="text-center">Pasajeros</th>
                        <th class="text-center" style="width: 125px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($viajes)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay pasajes vendidos ni viajes programados en este rango.</td></tr>
                    <?php else: ?>
                        <?php foreach($viajes as $v): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="chk-viaje-item form-check-input"
                                           value="<?= $v['FECHA_VIAJE'] ?>|<?= $v['ID_RUTA'] ?>|<?= $v['ID_BUS'] ?>"
                                           data-fecha="<?= $v['FECHA_VIAJE'] ?>"
                                           data-ruta-id="<?= $v['ID_RUTA'] ?>"
                                           data-bus-id="<?= $v['ID_BUS'] ?>"
                                           data-origen="<?= htmlspecialchars($v['RUT_ORIGEN']) ?>"
                                           data-destino="<?= htmlspecialchars($v['RUT_DESTINO']) ?>"
                                           data-bus-placa="<?= htmlspecialchars($v['BUS_PLACA']) ?>"
                                           data-bus-marca="<?= htmlspecialchars($v['BUS_MARCA']) ?>"
                                           data-pasajeros="<?= $v['TOTAL_PASAJEROS'] ?>"
                                           onchange="actualizarSeleccionViajes()">
                                </td>
                                <td class="fw-bold fs-5 text-primary">📅 <?= $v['FECHA_VIAJE'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($v['RUT_ORIGEN'] . ' ➔ ' . $v['RUT_DESTINO']) ?></td>
                                <td><?= htmlspecialchars($v['BUS_MARCA'] . ' [' . $v['BUS_PLACA'] . ']') ?></td>
                                <td class="text-center"><span class="badge bg-secondary fs-6"><?= $v['TOTAL_PASAJEROS'] ?></span></td>
                                <td class="text-center">
                                    <?php $modalId = $v['ID_RUTA'] . '_' . $v['ID_BUS'] . '_' . str_replace('-','',$v['FECHA_VIAJE']); ?>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">⚙️ Opciones</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 12px;">
                                            <li>
                                                <a class="dropdown-item fw-bold text-primary" href="#" data-bs-toggle="modal" data-bs-target="#modalPrintPx<?= $modalId ?>">
                                                    📋 Lista de Pasajeros (PDF)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item fw-bold text-dark" href="/suramerica2030/manifiesto/imprimirEquipaje?fecha=<?= $v['FECHA_VIAJE'] ?>&ruta=<?= $v['ID_RUTA'] ?>&bus=<?= $v['ID_BUS'] ?>" target="_blank">
                                                    🧳 Manifiesto Equipaje (PDF)
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item fw-bold text-success" href="#" data-bs-toggle="modal" data-bs-target="#modalCorreo<?= $modalId ?>">
                                                    ✉️ Enviar por Correo
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- MODAL: OPCIONES DE IMPRESIÓN INDIVIDUAL -->
                            <div class="modal fade text-start" id="modalPrintPx<?= $modalId ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header bg-dark text-white">
                                            <h6 class="modal-title">🖨️ Formato de Impresión</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="/suramerica2030/manifiesto/imprimir" method="GET" target="_blank">
                                            <div class="modal-body">
                                                <input type="hidden" name="fecha" value="<?= $v['FECHA_VIAJE'] ?>">
                                                <input type="hidden" name="ruta" value="<?= $v['ID_RUTA'] ?>">
                                                <input type="hidden" name="bus" value="<?= $v['ID_BUS'] ?>">
                                                
                                                <div class="mb-2">
                                                    <label class="fw-bold text-secondary mb-1">Modalidad Logística del Viaje:</label>
                                                    <select name="tipo_logistica" class="form-select border-dark" required>
                                                        <option value="ESTANDAR">🚐 Estándar (Directo Suramérica)</option>
                                                        <option value="PANAMERICANA">🚌 Delegado a Panamericana (Quito-Cúcuta)</option>
                                                        <option value="CONEXIONES">🔄 Conexiones Múltiples (Manta-Tulcán-Ipiales...)</option>
                                                    </select>
                                                    <small class="text-muted d-block mt-2" style="font-size:11px;">El formato modificará automáticamente el itinerario y membrete del PDF para controles aduaneros.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer p-1">
                                                <button type="submit" class="btn btn-dark w-100 fw-bold" onclick="bootstrap.Modal.getInstance(document.getElementById('modalPrintPx<?= $modalId ?>')).hide();">Generar PDF Oficial</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- MODAL PARA ENVIAR CORREO INDIVIDUAL -->
                            <div class="modal fade text-start" id="modalCorreo<?= $modalId ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h6 class="modal-title">✉️ Enviar Manifiesto de Pasajeros PDF</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="/suramerica2030/manifiesto/enviarManifiesto" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="fecha_envio" value="<?= $v['FECHA_VIAJE'] ?>">
                                                <input type="hidden" name="ruta_envio" value="<?= $v['ID_RUTA'] ?>">
                                                <input type="hidden" name="bus_envio" value="<?= $v['ID_BUS'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="fw-bold text-secondary mb-1">Modalidad Logística (Formato del PDF):</label>
                                                    <select name="tipo_logistica" class="form-select border-primary" required>
                                                        <option value="ESTANDAR">🚐 Estándar (Directo Suramérica)</option>
                                                        <option value="PANAMERICANA">🚌 Delegado a Panamericana (Quito-Cúcuta)</option>
                                                        <option value="CONEXIONES">🔄 Conexiones Múltiples (Manta-Tulcán-Ipiales...)</option>
                                                    </select>
                                                </div>

                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="fw-bold text-secondary mb-1">1. Filtrar por Sucursal:</label>
                                                        <select class="form-select border-primary" id="suc_<?= $modalId ?>" onchange="filtrarPersonalM('<?= $modalId ?>')">
                                                            <option value="">-- Todas --</option>
                                                            <?php 
                                                            if(!empty($directorioCorreos)):
                                                                $ciudadesUnicas = array_unique(array_column($directorioCorreos, 'CIUDAD'));
                                                                foreach($ciudadesUnicas as $ciu): 
                                                            ?>
                                                                <option value="<?= htmlspecialchars($ciu) ?>">📍 <?= htmlspecialchars($ciu) ?></option>
                                                            <?php 
                                                                endforeach;
                                                            endif;
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="fw-bold text-secondary mb-1">2. Elegir Personal:</label>
                                                        <select class="form-select border-primary" id="per_<?= $modalId ?>" onchange="document.getElementById('correo_destino_<?= $modalId ?>').value = this.value;">
                                                            <option value="">-- Seleccione --</option>
                                                            <?php if(!empty($directorioCorreos)): foreach($directorioCorreos as $uc): ?>
                                                                <option value="<?= htmlspecialchars($uc['EMAIL']) ?>" data-sucursal="<?= htmlspecialchars($uc['CIUDAD']) ?>">
                                                                    👤 <?= htmlspecialchars($uc['NOMBRE']) ?>
                                                                </option>
                                                            <?php endforeach; endif; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="fw-bold text-secondary mb-1">3. Confirmar Correo a Enviar:</label>
                                                    <input type="email" name="correo_destino" id="correo_destino_<?= $modalId ?>" class="form-control fw-bold text-primary" required placeholder="ejemplo@frontera.com">
                                                </div>
                                            </div>
                                            <div class="modal-footer p-1">
                                                <button type="submit" class="btn btn-primary w-100 fw-bold" onclick="if(this.form.checkValidity()) this.innerHTML='Enviando PDF... ⏳';">Enviar Ahora</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- PAGINACIÓN VIAJES -->
            <?php if(isset($totalPaginas) && $totalPaginas > 1): ?>
                <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center border-0 pt-3 pb-3">
                    <small class="text-muted fw-bold">Mostrando página <?= $pagina ?> de <?= $totalPaginas ?> (<?= $totalRegistros ?? '' ?> viajes)</small>
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

    <!-- ======================================================== -->
    <!-- MODAL: UNIFICAR / INTEGRAR VIAJES EN UN SOLO BUS         -->
    <!-- ======================================================== -->
    <div class="modal fade text-start" id="modalUnificarViajes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">🔗 Integrar Viajes en un Solo Bus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/suramerica2030/manifiesto/unificarViajes" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3">
                            🚌 <strong>Mismo Bus Físico:</strong> Esta acción agrupará a todos los pasajeros de los viajes seleccionados en un único bus, ruta y fecha unificada, garantizando <strong>un solo manifiesto consolidado</strong> sin duplicar ni fragmentar el viaje.
                        </div>

                        <h6 class="fw-bold text-secondary mb-2">1. Viajes que se van a integrar:</h6>
                        <div class="table-responsive mb-3 border rounded">
                            <table class="table table-sm table-striped align-middle mb-0" id="tablaViajesAUnificar">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Ruta Original</th>
                                        <th>Bus Original</th>
                                        <th class="text-center">Pasajeros</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dinámico vía JS -->
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end">Total Pasajeros Integrados:</td>
                                        <td class="text-center text-primary fs-6" id="totalPasajerosUnificar">0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Inputs ocultos inyectados por JS -->
                        <div id="hiddenInputsUnificarViajes"></div>

                        <h6 class="fw-bold text-secondary mb-2">2. Asignación del Viaje Unificado:</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Bus Asignado:</label>
                                <select name="bus_destino_unificado" id="selBusDestinoUnificado" class="form-select border-primary fw-bold" required>
                                    <option value="">-- Seleccionar Bus --</option>
                                    <?php if(!empty($buses)): foreach($buses as $b): ?>
                                        <option value="<?= $b['ID_BUS'] ?>">
                                            🚍 <?= htmlspecialchars($b['BUS_PLACA'] . ' - ' . $b['BUS_MARCA']) ?> (<?= $b['BUS_ASIENTOS'] ?> asientos)
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted">Ruta Destino:</label>
                                <select name="ruta_destino_unificada" id="selRutaDestinoUnificadaViaje" class="form-select border-primary fw-bold" required>
                                    <option value="">-- Seleccionar Ruta --</option>
                                    <?php if(!empty($rutas)): foreach($rutas as $r): ?>
                                        <option value="<?= $r['ID_RUTA'] ?>">
                                            📍 <?= htmlspecialchars($r['RUT_ORIGEN'] . ' ➔ ' . $r['RUT_DESTINO']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Fecha del Viaje:</label>
                                <input type="date" name="fecha_destino_unificada" id="txtFechaDestinoUnificadaViaje" class="form-control border-primary fw-bold" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" onclick="return confirm('¿Confirmas que deseas integrar estos viajes en el bus seleccionado?');">
                            🚀 Confirmar e Integrar en un Solo Bus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: IMPRIMIR CONSOLIDADO DE VIAJES SELECCIONADOS      -->
    <!-- ======================================================== -->
    <div class="modal fade text-start" id="modalPrintConsolidadoViajes" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h6 class="modal-title">🖨️ Imprimir Pasajeros Consolidados</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        Se generará el manifiesto de pasajeros agrupando todos los viajes marcados.
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold text-secondary mb-1">Modalidad Logística:</label>
                        <select id="selLogisticaConsolidadoViajes" class="form-select border-dark" required>
                            <option value="ESTANDAR">🚐 Estándar (Directo Suramérica)</option>
                            <option value="PANAMERICANA">🚌 Delegado a Panamericana (Quito-Cúcuta)</option>
                            <option value="CONEXIONES">🔄 Conexiones Múltiples (Manta-Tulcán-Ipiales...)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer p-1">
                    <button type="button" class="btn btn-dark w-100 fw-bold" onclick="ejecutarPrintConsolidadoViajes()">Generar PDF Consolidado</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: ENVIAR CORREO CONSOLIDADO DE VIAJES               -->
    <!-- ======================================================== -->
    <div class="modal fade text-start" id="modalCorreoConsolidadoViajes" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold">✉️ Enviar Manifiesto Consolidado de Pasajeros</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/suramerica2030/manifiesto/enviarManifiestoConsolidado" method="POST">
                    <div class="modal-body">
                        <div id="hiddenInputsCorreoConsolidadoViajes"></div>
                        <input type="hidden" name="ruta_id_ref" id="correoConsolidadoViajesRutaId" value="">
                        <input type="hidden" name="bus_id_ref" id="correoConsolidadoViajesBusId" value="">
                        <input type="hidden" name="fecha_ref" id="correoConsolidadoViajesFecha" value="">

                        <div class="alert alert-light border py-2 mb-3 small">
                            Se generará y enviará un único documento PDF con los pasajeros de los <strong id="lblCantViajesCorreo">0</strong> viajes seleccionados.
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold text-secondary mb-1">Modalidad Logística (Membrete):</label>
                            <select name="tipo_logistica" class="form-select border-primary" required>
                                <option value="ESTANDAR">🚐 Estándar (Directo Suramérica)</option>
                                <option value="PANAMERICANA">🚌 Delegado a Panamericana (Quito-Cúcuta)</option>
                                <option value="CONEXIONES">🔄 Conexiones Múltiples (Manta-Tulcán-Ipiales...)</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold text-secondary mb-1">1. Filtrar por Sucursal:</label>
                                <select class="form-select border-primary" id="suc_viajes_consolidado" onchange="filtrarPersonalViajesConsolidado()">
                                    <option value="">-- Todas las sucursales --</option>
                                    <?php 
                                    if(!empty($directorioCorreos)):
                                        $ciudadesUnicas = array_unique(array_column($directorioCorreos, 'CIUDAD'));
                                        foreach($ciudadesUnicas as $ciu): 
                                    ?>
                                        <option value="<?= htmlspecialchars($ciu) ?>">📍 <?= htmlspecialchars($ciu) ?></option>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-secondary mb-1">2. Elegir Personal:</label>
                                <select class="form-select border-primary" id="per_viajes_consolidado" onchange="document.getElementById('correo_destino_viajes_consolidado').value = this.value;">
                                    <option value="">-- Seleccione empleado --</option>
                                    <?php if(!empty($directorioCorreos)): foreach($directorioCorreos as $uc): ?>
                                        <option value="<?= htmlspecialchars($uc['EMAIL']) ?>" data-sucursal="<?= htmlspecialchars($uc['CIUDAD']) ?>">
                                            👤 <?= htmlspecialchars($uc['NOMBRE']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="fw-bold text-secondary mb-1">3. Confirmar Correo de Destino:</label>
                            <input type="email" name="correo_destino" id="correo_destino_viajes_consolidado" class="form-control fw-bold text-primary" required placeholder="ejemplo@frontera.com">
                            <small class="text-muted" style="font-size:11px;">El PDF consolidado de pasajeros se enviará automáticamente como adjunto.</small>
                        </div>
                    </div>
                    <div class="modal-footer p-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-3" onclick="if(this.form.checkValidity()) this.innerHTML='Enviando PDF... ⏳';">Enviar Manifiesto Consolidado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- ======================================================== -->
<!-- PESTAÑA 2: MANIFIESTO DE CARGA Y ENCOMIENDAS             -->
<!-- ======================================================== -->
<?php if($tabActive === 'carga'): ?>

    <!-- FILTRO DE FECHAS PARA DESPACHOS DE CARGA -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <form action="/suramerica2030/manifiesto" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="carga">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-secondary">Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio ?? date('Y-m-01')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-secondary">Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin ?? date('Y-m-t')) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100 fw-bold">🔍 Filtrar Despachos</button>
                </div>
            </form>
        </div>
    </div>

    <!-- BANNER DE ENCOMIENDAS EN TRÁNSITO (CARGA ACTUAL) -->
    <div class="card shadow-sm border-0 border-start border-success border-4 mb-4 bg-white">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 text-success fw-bold">🚚 Manifiesto de Encomiendas en Tránsito (Carga Actual)</h5>
                <p class="mb-0 text-muted small">
                    Genera el manifiesto general consolidado con <strong>todas</strong> las encomiendas que están viajando en ruta actualmente (Total en ruta: <span class="badge bg-success fs-6"><?= $totalEnTransito ?? 0 ?></span> paquete<?= ($totalEnTransito ?? 0) == 1 ? '' : 's' ?>).
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="/suramerica2030/manifiesto/imprimirEnTransito" target="_blank" class="btn btn-dark fw-bold shadow-sm">
                    🖨️ Ver / Imprimir Manifiesto en Tránsito
                </a>
                <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCorreoEnTransito">
                    ✉️ Enviar por Correo
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL PARA ENVIAR CORREO DE MANIFIESTO EN TRÁNSITO -->
    <div class="modal fade text-start" id="modalCorreoEnTransito" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title">✉️ Enviar Manifiesto de Carga en Tránsito</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/suramerica2030/manifiesto/enviarManifiestoEnTransito" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small mb-3">
                            Este documento contendrá el listado consolidado de las <strong><?= $totalEnTransito ?? 0 ?></strong> encomiendas que se encuentran en tránsito actualmente en todas las rutas.
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold text-secondary mb-1">1. Filtrar por Sucursal:</label>
                                <select class="form-select border-success" id="suc_entransito" onchange="filtrarPersonal('entransito')">
                                    <option value="">-- Todas las sucursales --</option>
                                    <?php 
                                    if(!empty($directorioCorreos)):
                                        $ciudadesUnicas = array_unique(array_column($directorioCorreos, 'CIUDAD'));
                                        foreach($ciudadesUnicas as $ciu): 
                                    ?>
                                        <option value="<?= htmlspecialchars($ciu) ?>">📍 <?= htmlspecialchars($ciu) ?></option>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-secondary mb-1">2. Elegir Personal:</label>
                                <select class="form-select border-success" id="per_entransito" onchange="document.getElementById('correo_destino_entransito').value = this.value;">
                                    <option value="">-- Seleccione un empleado --</option>
                                    <?php if(!empty($directorioCorreos)): foreach($directorioCorreos as $uc): ?>
                                        <option value="<?= htmlspecialchars($uc['EMAIL']) ?>" data-sucursal="<?= htmlspecialchars($uc['CIUDAD']) ?>">
                                            👤 <?= htmlspecialchars($uc['NOMBRE']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="fw-bold text-secondary mb-1">3. Confirmar Correo a Enviar:</label>
                            <input type="email" name="correo_destino" id="correo_destino_entransito" class="form-control fw-bold text-success" required placeholder="ejemplo@frontera.com">
                            <small class="text-muted" style="font-size:11px;">El PDF consolidado de carga en tránsito se adjuntará automáticamente.</small>
                        </div>
                    </div>
                    <div class="modal-footer p-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold px-3" onclick="if(this.form.checkValidity()) this.innerHTML='Enviando PDF... ⏳';">Enviar Manifiesto Ahora</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TABLA DE MANIFIESTOS DE CARGA RECIENTES -->
    <div class="card shadow-sm border-0 border-top border-success border-3 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0 text-success fw-bold">📋 Manifiestos de Carga Recientes</h5>
                <span class="badge bg-secondary rounded-pill"><?= !empty($despachos) ? count($despachos) : 0 ?></span>
            </div>
            
            <!-- TOOLBAR DE ACCIONES CUANDO SE SELECCIONAN DESPACHOS -->
            <div id="toolbarDespachos" class="d-none align-items-center gap-2 flex-wrap">
                <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm" id="badgeDespachosCount">0 seleccionados</span>
                <button type="button" class="btn btn-sm btn-primary fw-bold shadow-sm" onclick="abrirModalUnificarDespachos()">
                    🔗 Integrar en un solo Despacho
                </button>
                <button type="button" class="btn btn-sm btn-outline-dark fw-bold shadow-sm" onclick="imprimirDespachosConsolidados()">
                    🖨️ Ver / Imprimir Consolidado
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold shadow-sm" onclick="abrirModalCorreoConsolidado()">
                    ✉️ Enviar Correo Consolidado
                </button>
            </div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 44px;">
                            <input type="checkbox" id="chkSelectAllDespachos" class="form-check-input" onchange="toggleSelectAllDespachos(this)" title="Seleccionar todos los despachos">
                        </th>
                        <th>Fecha de Despacho</th>
                        <th>Ruta del Viaje</th>
                        <th class="text-center">Total Paquetes</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($despachos)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay despachos registrados en este rango de fechas.</td></tr>
                    <?php else: ?>
                        <?php foreach($despachos as $d): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="chk-despacho-item form-check-input" 
                                           value="<?= $d['FECHA_DESPACHO'] ?>|<?= $d['ID_RUTA'] ?>"
                                           data-fecha="<?= $d['FECHA_DESPACHO'] ?>"
                                           data-ruta-id="<?= $d['ID_RUTA'] ?>"
                                           data-origen="<?= htmlspecialchars($d['RUT_ORIGEN']) ?>"
                                           data-destino="<?= htmlspecialchars($d['RUT_DESTINO']) ?>"
                                           data-paquetes="<?= $d['TOTAL_PAQUETES'] ?>"
                                           onchange="actualizarSeleccionDespachos()">
                                </td>
                                <td class="fw-bold text-primary fs-5">📅 <?= htmlspecialchars($d['FECHA_DESPACHO']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($d['RUT_ORIGEN'] . ' ➔ ' . $d['RUT_DESTINO']) ?></td>
                                <td class="text-center"><span class="badge bg-secondary fs-6"><?= $d['TOTAL_PAQUETES'] ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="/suramerica2030/manifiesto/imprimirCarga?fecha=<?= $d['FECHA_DESPACHO'] ?>&ruta_id=<?= $d['ID_RUTA'] ?>" target="_blank" class="btn btn-sm btn-outline-dark fw-bold">
                                            🖨️ Ver / Imprimir
                                        </a>
                                        <?php $modalId = $d['ID_RUTA'] . '_' . str_replace('-','',$d['FECHA_DESPACHO']); ?>
                                        <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalCorreo<?= $modalId ?>">
                                            ✉️ Enviar Correo
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- MODAL PARA ENVIAR CORREO INDIVIDUAL DE DESPACHO -->
                            <div class="modal fade text-start" id="modalCorreo<?= $modalId ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h6 class="modal-title">✉️ Enviar Manifiesto PDF</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="/suramerica2030/manifiesto/enviarManifiestoCarga" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="fecha_envio" value="<?= $d['FECHA_DESPACHO'] ?>">
                                                <input type="hidden" name="ruta_envio" value="<?= $d['ID_RUTA'] ?>">
                                                
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="fw-bold text-secondary mb-1">1. Filtrar por Sucursal:</label>
                                                        <select class="form-select border-primary" id="suc_<?= $modalId ?>" onchange="filtrarPersonal('<?= $modalId ?>')">
                                                            <option value="">-- Todas las sucursales --</option>
                                                            <?php 
                                                            if(!empty($directorioCorreos)):
                                                                $ciudadesUnicas = array_unique(array_column($directorioCorreos, 'CIUDAD'));
                                                                foreach($ciudadesUnicas as $ciu): 
                                                            ?>
                                                                <option value="<?= htmlspecialchars($ciu) ?>">📍 <?= htmlspecialchars($ciu) ?></option>
                                                            <?php 
                                                                endforeach;
                                                            endif; 
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="fw-bold text-secondary mb-1">2. Elegir Personal:</label>
                                                        <select class="form-select border-primary" id="per_<?= $modalId ?>" onchange="document.getElementById('correo_destino_<?= $modalId ?>').value = this.value;">
                                                            <option value="">-- Seleccione un empleado --</option>
                                                            <?php if(!empty($directorioCorreos)): foreach($directorioCorreos as $uc): ?>
                                                                <option value="<?= htmlspecialchars($uc['EMAIL']) ?>" data-sucursal="<?= htmlspecialchars($uc['CIUDAD']) ?>">
                                                                    👤 <?= htmlspecialchars($uc['NOMBRE']) ?>
                                                                </option>
                                                            <?php endforeach; endif; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="fw-bold text-secondary mb-1">3. Confirmar Correo a Enviar:</label>
                                                    <input type="email" name="correo_destino" id="correo_destino_<?= $modalId ?>" class="form-control fw-bold text-primary" required placeholder="ejemplo@frontera.com">
                                                    <small class="text-muted" style="font-size:11px;">El PDF se generará de fondo y se enviará automáticamente como adjunto.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer p-1">
                                                <button type="submit" class="btn btn-primary w-100 fw-bold" onclick="if(this.form.checkValidity()) this.innerHTML='Enviando PDF... ⏳';">Enviar Ahora</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: UNIFICAR / INTEGRAR DESPACHOS SELECCIONADOS -->
    <div class="modal fade text-start" id="modalUnificarDespachos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">🔗 Integrar Despachos en un Solo Manifiesto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/suramerica2030/manifiesto/unificarDespachos" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3">
                            🚌 <strong>Mismo Viaje / Mismo Bus:</strong> Esta acción unirá los paquetes de los despachos seleccionados en una sola ruta y fecha para que compartan <strong>un único manifiesto oficial consolidado</strong> y no queden manifiestos separados.
                        </div>

                        <h6 class="fw-bold text-secondary mb-2">1. Despachos que se van a integrar:</h6>
                        <div class="table-responsive mb-3 border rounded">
                            <table class="table table-sm table-striped align-middle mb-0" id="tablaDespachosAUnificar">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Ruta Original</th>
                                        <th class="text-center">Paquetes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dinámico vía JS -->
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="2" class="text-end">Total Paquetes Integrados:</td>
                                        <td class="text-center text-primary fs-6" id="totalPaquetesUnificar">0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Inputs ocultos inyectados por JavaScript -->
                        <div id="hiddenInputsUnificar"></div>

                        <h6 class="fw-bold text-secondary mb-2">2. Destino y Ruta Oficial del Despacho Unificado:</h6>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-muted">Ruta Destino Unificada:</label>
                                <select name="ruta_destino_unificada" id="selRutaDestinoUnificada" class="form-select border-primary fw-bold" required>
                                    <?php if(!empty($rutas)): foreach($rutas as $r): ?>
                                        <option value="<?= $r['ID_RUTA'] ?>" data-origen="<?= htmlspecialchars($r['RUT_ORIGEN']) ?>" data-destino="<?= htmlspecialchars($r['RUT_DESTINO']) ?>">
                                            📍 <?= htmlspecialchars($r['RUT_ORIGEN'] . ' ➔ ' . $r['RUT_DESTINO']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                                <small class="text-muted" style="font-size:11px;">Todas las guías pasarán a esta ruta en la base de datos.</small>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted">Fecha del Despacho / Salida:</label>
                                <input type="date" name="fecha_destino_unificada" id="txtFechaDestinoUnificada" class="form-control border-primary fw-bold" required>
                                <small class="text-muted" style="font-size:11px;">Fecha oficial del viaje del bus.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" onclick="return confirm('¿Confirmas que deseas integrar estos despachos en un único manifiesto consolidado?');">
                            🚀 Confirmar e Integrar en un Solo Despacho
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: ENVIAR CORREO CONSOLIDADO (DESPACHOS SELECCIONADOS) -->
    <div class="modal fade text-start" id="modalCorreoConsolidado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold">✉️ Enviar Manifiesto Consolidado por Correo</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/suramerica2030/manifiesto/enviarManifiestoCargaConsolidado" method="POST">
                    <div class="modal-body">
                        <div id="hiddenInputsCorreoConsolidado"></div>
                        <input type="hidden" name="ruta_id_ref" id="correoConsolidadoRutaId" value="">
                        <input type="hidden" name="fecha_ref" id="correoConsolidadoFecha" value="">

                        <div class="alert alert-light border py-2 mb-3 small">
                            Se generará y enviará un único documento PDF con los paquetes de los <strong id="lblCantDespachosCorreo">0</strong> despachos seleccionados.
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold text-secondary mb-1">1. Filtrar por Sucursal:</label>
                                <select class="form-select border-primary" id="suc_consolidado" onchange="filtrarPersonalConsolidado()">
                                    <option value="">-- Todas las sucursales --</option>
                                    <?php 
                                    if(!empty($directorioCorreos)):
                                        $ciudadesUnicas = array_unique(array_column($directorioCorreos, 'CIUDAD'));
                                        foreach($ciudadesUnicas as $ciu): 
                                    ?>
                                        <option value="<?= htmlspecialchars($ciu) ?>">📍 <?= htmlspecialchars($ciu) ?></option>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-secondary mb-1">2. Elegir Personal:</label>
                                <select class="form-select border-primary" id="per_consolidado" onchange="document.getElementById('correo_destino_consolidado').value = this.value;">
                                    <option value="">-- Seleccione un empleado --</option>
                                    <?php if(!empty($directorioCorreos)): foreach($directorioCorreos as $uc): ?>
                                        <option value="<?= htmlspecialchars($uc['EMAIL']) ?>" data-sucursal="<?= htmlspecialchars($uc['CIUDAD']) ?>">
                                            👤 <?= htmlspecialchars($uc['NOMBRE']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="fw-bold text-secondary mb-1">3. Confirmar Correo de Destino:</label>
                            <input type="email" name="correo_destino" id="correo_destino_consolidado" class="form-control fw-bold text-primary" required placeholder="ejemplo@frontera.com">
                            <small class="text-muted" style="font-size:11px;">El PDF consolidado se enviará automáticamente como adjunto.</small>
                        </div>
                    </div>
                    <div class="modal-footer p-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-3" onclick="if(this.form.checkValidity()) this.innerHTML='Enviando PDF... ⏳';">Enviar Manifiesto Consolidado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- ======================================================== -->
<!-- JAVASCRIPT: GESTIÓN DE ACCIONES Y MULTI-SELECCIÓN         -->
<!-- ======================================================== -->
<script src="/suramerica2030/public/assets/js/manifiesto.js?v=1792400223"></script>
