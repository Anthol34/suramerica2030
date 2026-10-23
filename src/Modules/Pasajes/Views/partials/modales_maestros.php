<!-- MODAL MAESTRO: CREAR NUEVO PASAJE -->
<div class="modal fade" id="modalNuevoPasaje" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Registrar Pasaje o Reserva</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="/suramerica2030/pasajes/guardar" method="POST" id="formNuevoPasaje" onsubmit="return validarVentaMixto(this)">
                <div class="modal-body">
                    
                    <h6 class="border-bottom pb-1 text-primary">1. Viaje e Itinerario</h6>
                    <div class="row mb-2">
                        <div class="col-6"><label>Fecha de Salida Buscada</label><input type="date" name="fecha_viaje" id="fecha_viaje_nuevo" class="form-control" required onchange="filtrarRutasPorFecha()"></div>
                        <div class="col-6 d-flex align-items-end pb-2">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" id="fecha_abierta" onchange="toggleItinerario()">
                                <label class="form-check-label" for="fecha_abierta">Vender con Fecha Abierta</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3 p-3 bg-light rounded border g-2" id="caja_itinerario">
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-primary">📍</span> Origen*</label>
                            <select id="sel_origen_pasaje" class="form-select border-primary" required onchange="alCambiarOrigenPasaje()">
                                <option value="">- Seleccione Origen -</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-danger">🏁</span> Destino*</label>
                            <select id="sel_destino_pasaje" class="form-select border-primary" required onchange="alCambiarDestinoPasaje()" disabled>
                                <option value="">- Elija Origen Primero -</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold"><span class="text-success">🚌</span> Bus Asignado</label>
                            <select name="bus_id" id="bus_id_nuevo" class="form-select border-primary" required onchange="limpiarAsientosNuevos();">
                                <option value="">- Elegir -</option>
                                <?php foreach($buses as $b): ?><option value="<?= $b['ID_BUS'] ?>" data-asientos="<?= $b['BUS_ASIENTOS'] ?>"><?= htmlspecialchars($b['BUS_PLACA']) ?></option><?php endforeach; ?>
                            </select>
                        </div>

                        <!-- ID_RUTA que se envía en el POST -->
                        <input type="hidden" name="ruta_id" id="ruta_id_nuevo" value="" required>

                        <!-- Selector y Cuadrícula Visual de Modalidades de Viaje -->
                        <div class="col-12 d-none" id="caja_multiple_horario">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="fw-bold text-primary small mb-0">
                                    <i class="fas fa-layer-group me-1"></i> Seleccione la Modalidad de Servicio para este Viaje:
                                </label>
                                <span class="badge bg-light text-dark border small" id="lbl_conteo_modalidades">0 opciones</span>
                            </div>
                            
                            <!-- Cuadrícula de Tarjetas Interactivas de Modalidad -->
                            <div id="grid_tarjetas_modalidad_pasajes" class="row g-2 mb-2">
                                <!-- Tarjetas renderizadas dinámicamente por pasajes.js -->
                            </div>

                            <!-- Selector Select sincronizado (accesibilidad y fallback) -->
                            <select id="sel_horario_pasaje" class="form-select form-select-sm border-info d-none" onchange="seleccionarRutaEspecifica(this.value)">
                            </select>
                        </div>

                        <!-- Tarjeta Resumen de la Ruta Seleccionada -->
                        <div class="col-12 mt-2 d-none" id="info_ruta_seleccionada">
                            <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center flex-wrap gap-2 shadow-sm">
                                <div>
                                    <span id="badge_tipo_viaje" class="badge bg-success fs-6">➡️ DIRECTO</span>
                                    <span id="badge_modalidad" class="badge bg-primary text-white fs-6 ms-1" style="display: none;"></span>
                                    <span id="badge_horario" class="badge bg-light text-dark border ms-1">⏰ 08:00</span>
                                    <span id="badge_tramo_nombre" class="fw-bold text-dark ms-2 small"></span>
                                </div>
                                <div>
                                    <span class="text-muted small">Tarifa:</span>
                                    <span id="badge_precio" class="fw-bold text-success fs-6 ms-1">$0.00</span>
                                    <span id="badge_equipaje" class="badge bg-warning text-dark ms-2">🧳 35 Kg máx</span>
                                </div>
                            </div>

                            <!-- Desglose interactivo de Tramos y Operadores -->
                            <div id="detalle_tramos_ruta" class="mt-2 d-none"></div>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mt-3 text-primary">2. Registro de Pasajeros</h6>
                    <div id="contenedor_pasajeros">
                        <!-- Primer Pasajero -->
                        <div class="pasajero-row border p-3 mb-3 bg-white rounded position-relative shadow-sm">
                            <h6 class="text-secondary fw-bold">👤 Pasajero Titular 1</h6>
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label>Cédula/Pasaporte</label>
                                    <div class="input-group">
                                        <input type="text" name="cliente_id[]" id="cliente_id_0" class="form-control" required placeholder="Ej: 131415..." onchange="buscarClienteAuto(this.value, 0)">
                                        <span class="input-group-text bg-light" title="Autocompletado Activo">🔍</span>
                                    </div>
                                </div>
                                <div class="col-md-3"><label>Nombres</label><input type="text" name="cliente_nombre[]" id="cliente_nombre_0" class="form-control" required></div>
                                <div class="col-md-3"><label>Apellidos</label><input type="text" name="cliente_apellido[]" id="cliente_apellido_0" class="form-control" required></div>
                                <div class="col-md-3"><label>Teléfono</label><input type="text" name="cliente_telefono[]" id="cliente_telefono_0" class="form-control"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4"><label>Correo Electrónico</label><input type="email" name="cliente_correo[]" id="cliente_correo_0" class="form-control" placeholder="Opcional"></div>
                                <div class="col-md-4">
                                    <label>Nacionalidad</label>
                                    <input list="lista_nacionalidades" name="cliente_nacionalidad[]" id="cliente_nacionalidad_0" class="form-control text-uppercase" required>
                                    <datalist id="lista_nacionalidades"><option value="ECUATORIANA"><option value="VENEZOLANA"><option value="COLOMBIANA"></datalist>
                                </div>
                                <div class="col-md-4"><label>Fecha Nacimiento</label><input type="date" name="cliente_fechanac[]" id="cliente_fechanac_0" class="form-control"></div>
                            </div>
                            <div class="row mb-1 mt-3">
                                <div class="col-md-4">
                                    <label class="fw-bold">N° Asiento</label>
                                    <div class="input-group">
                                        <input type="text" name="asiento[]" id="asiento_nuevo_0" class="form-control text-center fw-bold bg-white border-dark" readonly required placeholder="-">
                                        <button type="button" class="btn btn-dark" onclick="abrirMapaAsientos('nuevo', null, 'nuevo', 'asiento_nuevo_0')">📍 Elegir</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 mb-3" onclick="agregarPasajero()">+ Añadir Otro Pasajero</button>

                    <h6 class="border-bottom pb-1 mt-3 text-primary">3. Facturación y Cobro</h6>
                    <div class="row mb-2 bg-light p-3 border rounded">
                        <div class="col-4">
                            <label class="fw-bold">Precio Total Global ($)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="precio_total" id="precio_total" class="form-control bg-white fw-bold" required oninput="alCambiarPrecioTotalManual()" <?= !$puedeEditarPrecios ? 'readonly' : '' ?>>
                                <?php if($puedeEditarPrecios): ?>
                                    <button type="button" class="btn btn-outline-secondary" type="button" title="Restablecer precio oficial de la ruta" onclick="ponerPrecioDefault()">🔄</button>
                                <?php endif; ?>
                            </div>
                            <?php if(!$puedeEditarPrecios): ?>
                                <small class="text-muted d-block mt-1" style="font-size:11px;">🔒 Tarifa oficial de ruta fija (sin permiso de descuento).</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-4"><label class="fw-bold">¿Cuánto abona hoy? ($)</label><input type="number" step="0.01" name="monto_abonar" id="monto_abonar" class="form-control fw-bold text-success fs-5" required oninput="balancearMixtoVenta('total')"></div>
                        <div class="col-4">
                            <label class="fw-bold">Método de Pago</label>
                            <select name="metodo_pago" id="metodo_pago_nuevo" class="form-select border-success" onchange="togglePagoMixtoVenta()">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="MIXTO">Mixto (Efectivo + Transferencia)</option>
                            </select>
                        </div>
                        <div class="col-12 mt-2 d-none" id="div_pago_mixto_nuevo">
                            <div class="p-2 rounded bg-white border border-info shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-primary">💳 Desglose de Pago Mixto</span>
                                    <span class="badge bg-success" id="badge_check_mixto_nuevo">✔ Cuadrado</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-0">Efectivo ($)</label>
                                        <input type="number" step="0.01" min="0" name="monto_efectivo" id="monto_efectivo_nuevo" class="form-control form-control-sm fw-bold text-success" placeholder="0.00" oninput="balancearMixtoVenta('efectivo')">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-0">Transferencia ($)</label>
                                        <input type="number" step="0.01" min="0" name="monto_transferencia" id="monto_transferencia_nuevo" class="form-control form-control-sm fw-bold text-primary" placeholder="0.00" oninput="balancearMixtoVenta('transferencia')">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                                    ℹ️ Modifique cualquiera de los dos valores y el otro se balanceará automáticamente.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success w-100 fs-5 fw-bold shadow-sm">Generar Factura de Venta</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCroquisBus" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h6 class="modal-title">Selección de Asiento</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center">
                <div class="d-flex justify-content-center gap-3 mb-3" style="font-size:12px;">
                    <div><span style="display:inline-block; width:15px; height:15px; background:#e8f5e9; border:1px solid #28a745;"></span> Libre</div>
                    <div><span style="display:inline-block; width:15px; height:15px; background:#f8d7da; border:1px solid #dc3545;"></span> Ocupado</div>
                </div>
                <div class="bus-map-container" id="dibujo_asientos"></div>
                <input type="hidden" id="target_input_asiento">
            </div>
            <div class="modal-footer p-1" id="footerReasignar" style="display:none;">
                <form action="/suramerica2030/pasajes/reasignar" method="POST" class="w-100">
                    <input type="hidden" name="id_pasaje" id="form_id_pasaje">
                    <input type="hidden" name="nuevo_asiento" id="form_nuevo_asiento">
                    <button type="submit" class="btn btn-warning w-100 fw-bold" id="btnConfirmarReasignar" disabled>Confirmar Cambio de Asiento</button>
                </form>
            </div>
        </div>
    </div>
</div>