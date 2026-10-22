    <!-- ======================================================== -->
    <!-- MODAL MAESTRO: CORREDOR E ITINERARIO TRONCAL MAESTRO    -->
    <!-- ======================================================== -->
    <div class="modal fade" id="modalItinerarioCorredor" tabindex="-1" aria-labelledby="modalItinerarioCorredorLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5">🌐</span>
                        <h5 class="modal-title fw-bold mb-0" id="modalItinerarioCorredorLabel">Corredores e Itinerarios Troncales</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">

                    <!-- BARRA DE SELECCION Y CREACION DE CORREDORES -->
                    <div class="card shadow-sm border-0 mb-3 bg-white">
                        <div class="card-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 520px;">
                                <label class="fw-bold text-dark text-nowrap mb-0">🌐 Corredor Activo:</label>
                                <select class="form-select form-select-sm border-primary fw-bold" onchange="window.location.href='/suramerica2030/flota?tab=rutas&openModal=corredor&corredor_id=' + this.value;">
                                    <?php foreach($corredores as $c): ?>
                                        <option value="<?= $c['ID_CORREDOR'] ?>" <?= ($corredorActivo && $corredorActivo['ID_CORREDOR'] == $c['ID_CORREDOR']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['CODIGO'] ?? 'COR') ?> - <?= htmlspecialchars($c['NOMBRE']) ?> (<?= htmlspecialchars($c['ORIGEN_CABECERA']) ?> ➔ <?= htmlspecialchars($c['DESTINO_TERMINAL']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearCorredor">
                                    + Crear Nuevo Corredor
                                </button>
                                <?php if(count($corredores) > 1 && $corredorActivo): ?>
                                    <form action="/suramerica2030/flota/eliminarCorredor" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar el corredor <?= htmlspecialchars($corredorActivo['NOMBRE']) ?> con todas sus paradas?');" class="d-inline">
                                        <input type="hidden" name="id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar este corredor">
                                            🗑️ Eliminar Corredor
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if($corredorActivo): ?>
                        <!-- CARD CABECERA CORREDOR -->
                        <div class="card shadow-sm border-0 border-top border-primary border-4 mb-4">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <div>
                                        <span class="badge bg-primary fs-6 px-3 py-1 mb-1"><?= htmlspecialchars($corredorActivo['CODIGO'] ?? 'COR-01') ?></span>
                                        <h4 class="fw-bold text-dark mb-0 d-inline-block ms-2"><?= htmlspecialchars($corredorActivo['NOMBRE']) ?></h4>
                                    </div>
                                    <div>
                                        <button class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditarCorredor">
                                            ⚙️ Modificar Políticas del Corredor
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-3 bg-light rounded border h-100">
                                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Trayecto Troncal</small>
                                            <strong class="fs-5 text-dark"><?= htmlspecialchars($corredorActivo['ORIGEN_CABECERA']) ?> ➔ <?= htmlspecialchars($corredorActivo['DESTINO_TERMINAL']) ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-3 bg-light rounded border h-100">
                                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Frecuencia Fija</small>
                                            <?php 
                                                $diasArrCor = explode(',', $corredorActivo['DIAS_SALIDA'] ?? '');
                                                $diasTxtCor = [];
                                                foreach($diasArrCor as $d) { if(isset($mapaDias[$d])) $diasTxtCor[] = $mapaDias[$d]; }
                                            ?>
                                            <strong class="text-primary fs-6"><?= empty($diasTxtCor) ? 'Sin definir' : implode(', ', $diasTxtCor) ?></strong>
                                            <div class="small text-muted">Hora: <span class="fw-bold text-dark"><?= $corredorActivo['HORA_SALIDA'] ?></span></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-3 bg-light rounded border h-100">
                                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Políticas de Equipaje</small>
                                            <div>Libre: <strong class="text-dark"><?= number_format($corredorActivo['LIMITE_EQUIPAJE_KG'], 1) ?> Kg</strong></div>
                                            <div>Sobrepeso: <strong class="text-danger">$<?= number_format($corredorActivo['PRECIO_SOBREPESO_KG'], 2) ?>/Kg</strong></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-3 bg-light rounded border h-100">
                                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Políticas Comerciales</small>
                                            <div>Comisión Valor: <strong class="text-success"><?= number_format($corredorActivo['COMISION_VALOR'], 1) ?> %</strong></div>
                                            <div>Tarifa Sobre: <strong class="text-dark">$<?= number_format($corredorActivo['PRECIO_SOBRE'], 2) ?></strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- SECUENCIA DE PARADAS -->
                            <div class="col-lg-8 mb-4">
                                <div class="card shadow-sm border-0">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                        <h6 class="mb-0 fw-bold text-primary">📍 Secuencia de Paradas (Columna Vertebral)</h6>
                                        <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAgregarParada">
                                            + Añadir Escala
                                        </button>
                                    </div>
                                    <div class="card-body p-0 table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;">#</th>
                                                    <th>Ciudad / Parada</th>
                                                    <th>País</th>
                                                    <th>Tipo de Parada</th>
                                                    <th>Tiempo Estimado</th>
                                                    <th class="text-center" style="width: 120px;">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($paradas as $p): ?>
                                                    <tr>
                                                        <td class="fw-bold text-muted"><?= $p['ORDEN'] ?></td>
                                                        <td>
                                                            <strong class="fs-6 text-dark"><?= htmlspecialchars($p['CIUDAD']) ?></strong>
                                                        </td>
                                                        <td>
                                                            <?php if($p['PAIS'] === 'ECUADOR'): ?>
                                                                <span class="badge bg-warning text-dark">🇪🇨 Ecuador</span>
                                                            <?php elseif($p['PAIS'] === 'COLOMBIA'): ?>
                                                                <span class="badge bg-primary text-white">🇨🇴 Colombia</span>
                                                            <?php elseif($p['PAIS'] === 'VENEZUELA'): ?>
                                                                <span class="badge bg-danger text-white">🇻🇪 Venezuela</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><?= htmlspecialchars($p['PAIS']) ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if($p['TIPO_PARADA'] === 'CABECERA'): ?>
                                                                <span class="badge bg-success">🏁 Origen Cabecera</span>
                                                            <?php elseif($p['TIPO_PARADA'] === 'TERMINAL'): ?>
                                                                <span class="badge bg-dark">🏁 Terminal Final</span>
                                                            <?php elseif($p['TIPO_PARADA'] === 'TRANSBORDO_FRONTERA'): ?>
                                                                <span class="badge bg-info text-dark">🛂 Transbordo Fronterizo</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-dark border">📍 Escala Intermedia</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">+<?= $p['TIEMPO_ESTIMADO_HORAS'] ?> hrs de salida</small>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php if($p['TIPO_PARADA'] !== 'CABECERA' && $p['TIPO_PARADA'] !== 'TERMINAL'): ?>
                                                                <div class="btn-group btn-group-sm">
                                                                    <form action="/suramerica2030/flota/moverParada" method="POST" class="d-inline" data-live="true" data-live-refresh="#modalItinerarioCorredor">
                                                                        <input type="hidden" name="id_parada" value="<?= $p['ID_CORREDOR_PARADA'] ?>">
                                                                        <input type="hidden" name="direccion" value="subir">
                                                                        <input type="hidden" name="id_corredor_retorno" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Subir parada" <?= ($p['ORDEN'] <= 2) ? 'disabled' : '' ?>>▲</button>
                                                                    </form>
                                                                    <form action="/suramerica2030/flota/moverParada" method="POST" class="d-inline" data-live="true" data-live-refresh="#modalItinerarioCorredor">
                                                                        <input type="hidden" name="id_parada" value="<?= $p['ID_CORREDOR_PARADA'] ?>">
                                                                        <input type="hidden" name="direccion" value="bajar">
                                                                        <input type="hidden" name="id_corredor_retorno" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Bajar parada" <?= ($p['ORDEN'] >= count($paradas) - 1) ? 'disabled' : '' ?>>▼</button>
                                                                    </form>
                                                                    <form action="/suramerica2030/flota/eliminarParada" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas quitar la parada de <?= htmlspecialchars($p['CIUDAD']) ?>?');" data-live="true" data-live-refresh="#modalItinerarioCorredor">
                                                                        <input type="hidden" name="id_parada" value="<?= $p['ID_CORREDOR_PARADA'] ?>">
                                                                        <input type="hidden" name="id_corredor_retorno" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Eliminar parada">✕</button>
                                                                    </form>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted small">Fija</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- TRAMOS OPERATIVOS Y ASIGNACIÓN DE BUSES -->
                            <div class="col-lg-4 mb-4">
                                <div class="card shadow-sm border-0">
                                    <div class="card-header bg-white py-3">
                                        <h6 class="mb-0 fw-bold text-dark">🚌 Asignación de Unidades por País</h6>
                                        <small class="text-muted">Unidades asignadas para los transbordos internacionales.</small>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach($tramosOperativos as $top): ?>
                                            <div class="border rounded p-3 mb-3 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <strong class="text-primary"><?= htmlspecialchars($top['NOMBRE_TRAMO']) ?></strong>
                                                    <small class="badge bg-secondary"><?= htmlspecialchars($top['PAIS']) ?></small>
                                                </div>
                                                <div class="small text-muted mb-2">
                                                    <?= htmlspecialchars($top['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($top['DESTINO_TRAMO']) ?>
                                                </div>

                                                <form action="/suramerica2030/flota/guardarBusTramo" method="POST" class="d-flex gap-2" data-live="true" data-live-refresh="#modalItinerarioCorredor">
                                                    <input type="hidden" name="id_tramo" value="<?= $top['ID_CORREDOR_TRAMO'] ?>">
                                                    <input type="hidden" name="id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                                                    <select name="id_bus" class="form-select form-select-sm" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                                                        <option value="">-- Sin unidad asignada --</option>
                                                        <?php foreach($buses as $bus): ?>
                                                            <option value="<?= $bus['ID_BUS'] ?>" <?= ($top['ID_BUS_DEFAULT'] == $bus['ID_BUS']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($bus['BUS_PLACA']) ?> - <?= htmlspecialchars($bus['BUS_MARCA']) ?> (<?= $bus['BUS_ASIENTOS'] ?> As.)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>

                                        <div class="alert alert-info py-2 px-3 mb-0 small">
                                            💡 Al vender un pasaje internacional, el primer tramo toma la unidad asignada y los siguientes tramos se confirman al llegar a la frontera.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="card shadow-sm border-0 py-5 text-center bg-white my-3">
                            <div class="card-body">
                                <span class="fs-1 d-block mb-2">🌐</span>
                                <h5 class="fw-bold text-dark">No hay corredores registrados</h5>
                                <p class="text-muted">Crea tu primer corredor maestro para comenzar a organizar tus itinerarios y paradas.</p>
                                <button class="btn btn-success fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalCrearCorredor">
                                    + Crear Primer Corredor
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SIBLING SUB-MODALS (Outside parent modal to prevent Bootstrap stacking issues) -->
    <?php if($corredorActivo): ?>
        <!-- MODAL EDITAR CORREDOR -->
        <div class="modal fade" id="modalEditarCorredor" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">Políticas y Parámetros del Corredor</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="/suramerica2030/flota/guardarCorredor" method="POST" data-live="true" data-live-refresh="#modalItinerarioCorredor">
                        <div class="modal-body">
                            <input type="hidden" name="id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                            
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Código del Corredor</label>
                                    <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($corredorActivo['CODIGO']) ?>" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Nombre Comercial</label>
                                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($corredorActivo['NOMBRE']) ?>" required>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Cabecera de Origen</label>
                                    <input type="text" name="origen_cabecera" class="form-control" value="<?= htmlspecialchars($corredorActivo['ORIGEN_CABECERA']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Terminal Final</label>
                                    <input type="text" name="destino_terminal" class="form-control" value="<?= htmlspecialchars($corredorActivo['DESTINO_TERMINAL']) ?>" required>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Días de Salida Fija</label>
                                    <div class="d-flex flex-wrap gap-2 pt-1">
                                        <?php foreach($mapaDias as $numDia => $txtDia): ?>
                                            <label class="btn btn-outline-secondary btn-sm">
                                                <input type="checkbox" name="dias[]" value="<?= $numDia ?>" <?= in_array((string)$numDia, $diasArrCor ?? []) ? 'checked' : '' ?>> <?= $txtDia ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Hora de Salida Cabecera</label>
                                    <input type="time" name="hora_salida" class="form-control" value="<?= $corredorActivo['HORA_SALIDA'] ?>" required>
                                </div>
                            </div>

                            <h6 class="text-secondary border-bottom pb-1 mt-4">Políticas Globales Heredadas</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Equipaje Libre (Kg)</label>
                                    <input type="number" step="0.1" name="limite_equipaje" class="form-control" value="<?= $corredorActivo['LIMITE_EQUIPAJE_KG'] ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sobrepeso ($/Kg)</label>
                                    <input type="number" step="0.01" name="precio_sobrepeso" class="form-control" value="<?= $corredorActivo['PRECIO_SOBREPESO_KG'] ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Precio Sobre ($)</label>
                                    <input type="number" step="0.01" name="precio_sobre" class="form-control" value="<?= $corredorActivo['PRECIO_SOBRE'] ?>" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Comisión Envíos de Valores (%)</label>
                                    <input type="number" step="0.10" name="comision_valor" class="form-control" value="<?= $corredorActivo['COMISION_VALOR'] ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="ACTIVO" <?= ($corredorActivo['ESTADO'] == 'ACTIVO') ? 'selected' : '' ?>>ACTIVO</option>
                                        <option value="INACTIVO" <?= ($corredorActivo['ESTADO'] == 'INACTIVO') ? 'selected' : '' ?>>INACTIVO</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary fw-bold">Guardar Políticas</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL AGREGAR PARADA -->
        <div class="modal fade" id="modalAgregarParada" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">+ Añadir Escala al Corredor</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="/suramerica2030/flota/guardarParada" method="POST" data-live="true" data-live-refresh="#modalItinerarioCorredor" data-live-reset="true">
                        <div class="modal-body">
                            <input type="hidden" name="id_corredor" value="<?= $corredorActivo['ID_CORREDOR'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ciudad / Parada</label>
                                <input type="text" name="ciudad" list="listaCiudadesParada" class="form-control" placeholder="Selecciona o escribe una ciudad..." required>
                                <datalist id="listaCiudadesParada">
                                    <?php foreach($ciudadesCatalogo as $cd): ?>
                                        <option value="<?= htmlspecialchars($cd['NOMBRE']) ?>"><?= htmlspecialchars($cd['PAIS']) ?></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold">País</label>
                                    <select name="pais" class="form-select" required>
                                        <option value="ECUADOR">Ecuador</option>
                                        <option value="COLOMBIA">Colombia</option>
                                        <option value="VENEZUELA">Venezuela</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold">Tipo de Parada</label>
                                    <select name="tipo_parada" class="form-select">
                                        <option value="ESCALA">Escala Intermedia</option>
                                        <option value="TRANSBORDO_FRONTERA">Transbordo Fronterizo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Posición en el Recorrido</label>
                                <select name="posicion_insercion" class="form-select">
                                    <option value="FINAL">📍 Al final de las escalas (Antes de <?= htmlspecialchars($corredorActivo['DESTINO_TERMINAL']) ?>)</option>
                                    <?php foreach($paradas as $p): ?>
                                        <?php if($p['TIPO_PARADA'] !== 'TERMINAL'): ?>
                                            <option value="<?= $p['ORDEN'] ?>">⬇️ Insertar después de: <?= $p['ORDEN'] ?>. <?= htmlspecialchars($p['CIUDAD']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Por defecto se ubicará entre la última escala y el destino terminal.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tiempo Estimado desde la salida (Horas)</label>
                                <input type="number" name="tiempo_estimado_horas" class="form-control" value="2" min="0">
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary fw-bold">Guardar Parada</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL CREAR NUEVO CORREDOR -->
    <div class="modal fade" id="modalCrearCorredor" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">+ Crear Nuevo Corredor Troncal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/suramerica2030/flota/crearCorredor" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small mb-3">
                            ℹ️ Un corredor representa el trayecto físico principal (ej. <em>Manta ➔ Caracas</em> o <em>Caracas ➔ Manta</em>). Al crearlo, se añadirán automáticamente el Origen y Destino como paradas iniciales, y luego podrás añadir todas las escalas intermedias.
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Código Identificador</label>
                                <input type="text" name="codigo" class="form-control" placeholder="Ej: COR-02" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Nombre del Corredor / Línea</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej: Corredor Retorno Caracas - Manta" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ciudad Cabecera (Origen)</label>
                                <input type="text" name="origen_cabecera" list="listaCiudadesCorredor" class="form-control" placeholder="Ej: Caracas" required>
                                <div class="mt-1">
                                    <label class="small text-muted">País de Origen:</label>
                                    <select name="pais_origen" class="form-select form-select-sm">
                                        <option value="ECUADOR">Ecuador</option>
                                        <option value="COLOMBIA">Colombia</option>
                                        <option value="VENEZUELA">Venezuela</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ciudad Terminal (Destino)</label>
                                <input type="text" name="destino_terminal" list="listaCiudadesCorredor" class="form-control" placeholder="Ej: Manta" required>
                                <div class="mt-1">
                                    <label class="small text-muted">País de Destino:</label>
                                    <select name="pais_destino" class="form-select form-select-sm">
                                        <option value="VENEZUELA">Venezuela</option>
                                        <option value="COLOMBIA">Colombia</option>
                                        <option value="ECUADOR">Ecuador</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <datalist id="listaCiudadesCorredor">
                            <?php foreach($ciudadesCatalogo as $cd): ?>
                                <option value="<?= htmlspecialchars($cd['NOMBRE']) ?>"><?= htmlspecialchars($cd['PAIS']) ?></option>
                            <?php endforeach; ?>
                        </datalist>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Días de Salida Fija</label>
                                <div class="d-flex flex-wrap gap-2 pt-1">
                                    <?php foreach($mapaDias as $numDia => $txtDia): ?>
                                        <label class="btn btn-outline-secondary btn-sm">
                                            <input type="checkbox" name="dias[]" value="<?= $numDia ?>" <?= in_array((string)$numDia, ['2','5']) ? 'checked' : '' ?>> <?= $txtDia ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Hora de Salida Cabecera</label>
                                <input type="time" name="hora_salida" class="form-control" value="10:00" required>
                            </div>
                        </div>

                        <h6 class="text-secondary border-bottom pb-1 mt-4">Políticas Globales Heredadas por las Sub-Rutas</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label">Equipaje Libre (Kg)</label>
                                <input type="number" step="0.1" name="limite_equipaje" class="form-control" value="35.0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sobrepeso ($/Kg)</label>
                                <input type="number" step="0.01" name="precio_sobrepeso" class="form-control" value="1.00" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio Sobre ($)</label>
                                <input type="number" step="0.01" name="precio_sobre" class="form-control" value="5.00" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Comisión Envíos de Valores (%)</label>
                                <input type="number" step="0.10" name="comision_valor" class="form-control" value="10.00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado Inicial</label>
                                <select name="estado" class="form-select">
                                    <option value="ACTIVO">ACTIVO</option>
                                    <option value="INACTIVO">INACTIVO</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold">Crear Corredor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
