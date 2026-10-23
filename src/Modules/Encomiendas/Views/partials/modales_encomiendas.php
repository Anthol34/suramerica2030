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

                        <!-- Selector y Cuadrícula Visual de Modalidades de Envío -->
                        <div class="col-12 d-none" id="caja_multiple_modalidad_enc">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="fw-bold text-primary small mb-0">
                                    <i class="fas fa-layer-group me-1"></i> Seleccione la Modalidad de Despacho:
                                </label>
                                <span class="badge bg-light text-dark border small" id="lbl_conteo_modalidades_enc">0 opciones</span>
                            </div>
                            
                            <!-- Cuadrícula de Tarjetas Interactivas de Modalidad -->
                            <div id="grid_tarjetas_modalidad_enc" class="row g-2 mb-2">
                                <!-- Tarjetas renderizadas dinámicamente por encomiendas.js -->
                            </div>
                        </div>

                        <!-- Ficha Resumen de Tarifas de la Ruta Seleccionada -->
                        <div class="col-12 mt-2 d-none" id="info_ruta_enc">
                            <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span id="badge_tipo_enc" class="badge bg-success fs-6">➡️ DIRECTO</span>
                                    <span id="badge_modalidad_enc" class="badge bg-primary text-white fs-6 ms-1" style="display: none;"></span>
                                    <span id="badge_nombre_enc" class="fw-bold text-dark ms-2 small"></span>
                                    <span id="lbl_duracion_enc" class="badge bg-light text-dark border ms-1 small"></span>
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
