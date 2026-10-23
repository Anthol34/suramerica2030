// =============== DATOS MAESTROS DE RUTAS Y SUCURSAL ===============
const TODAS_LAS_RUTAS_ENC = (window.ENCOMIENDAS_CONFIG && window.ENCOMIENDAS_CONFIG.rutas) ? window.ENCOMIENDAS_CONFIG.rutas : [];
const CIUDAD_SUCURSAL_ACTUAL_ENC = (window.ENCOMIENDAS_CONFIG && window.ENCOMIENDAS_CONFIG.ciudadSucursal) ? window.ENCOMIENDAS_CONFIG.ciudadSucursal : '';

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
    let cajaMulti = document.getElementById('caja_multiple_modalidad_enc');
    
    if (!origen || !destino) {
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        if (cajaMulti) cajaMulti.classList.add('d-none');
        calcularCobroEncomienda();
        return;
    }
    
    let matches = TODAS_LAS_RUTAS_ENC.filter(r => r.RUT_ORIGEN === origen && r.RUT_DESTINO === destino);
    if (matches.length > 0) {
        if (cajaMulti) {
            cajaMulti.classList.remove('d-none');
            let lblConteo = document.getElementById('lbl_conteo_modalidades_enc');
            if (lblConteo) {
                lblConteo.textContent = matches.length + (matches.length === 1 ? ' modalidad disponible' : ' modalidades disponibles');
            }

            let gridModalidades = document.getElementById('grid_tarjetas_modalidad_enc');
            if (gridModalidades) {
                gridModalidades.innerHTML = '';
                matches.forEach(m => {
                    let col = document.createElement('div');
                    col.className = matches.length === 1 ? 'col-12' : 'col-md-6 col-12';
                    
                    let modBadgeHtml = m.MODALIDAD_BADGE_HTML || obtenerBadgeModalidadHtmlEnc(m.MODALIDAD_CODIGO || m.MODALIDAD, m.NUM_TRAMOS);
                    let duracionTexto = m.DURACION_FORMATO || (m.TIEMPO_ESTIMADO_HORAS > 0 ? `⏱️ ${m.TIEMPO_ESTIMADO_HORAS}h aprox.` : '⏱️ Regular');
                    let numTramosStr = (m.NUM_TRAMOS && m.NUM_TRAMOS > 1) ? `${m.NUM_TRAMOS} Tramos` : 'Directo';
                    let operadoresStr = m.OPERADORES_RESUMEN || m.OPERADOR_NOMBRE || 'Suramérica 2030';
                    let precioKg = parseFloat(m.PRECIO_ENCOMIENDA_KG || 0).toFixed(2);

                    col.innerHTML = `
                        <div class="card h-100 card-modalidad-enc p-2 border rounded shadow-sm bg-white" 
                             id="card_mod_enc_${m.ID_RUTA}"
                             style="cursor: pointer; transition: all 0.2s ease-in-out;"
                             onclick="seleccionarRutaEspecificaEnc(${m.ID_RUTA})">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    ${modBadgeHtml}
                                </div>
                                <div class="text-end">
                                    <span class="fs-6 fw-bold text-primary">$${precioKg} <small class="text-muted fw-normal" style="font-size:11px;">/ Kg</small></span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top small text-muted">
                                <span class="fw-semibold text-dark"><i class="far fa-clock me-1 text-primary"></i>${duracionTexto}</span>
                                <span class="badge bg-light text-secondary border"><i class="fas fa-route me-1"></i>${numTramosStr}</span>
                            </div>
                            <div class="small text-truncate mt-1 text-secondary" title="${operadoresStr}">
                                <i class="fas fa-truck-moving text-muted me-1"></i>${operadoresStr}
                            </div>
                        </div>
                    `;
                    gridModalidades.appendChild(col);
                });
            }
        }
        
        seleccionarRutaEspecificaEnc(matches[0].ID_RUTA);
    } else {
        alert("No se encontró una ruta configurada para este trayecto.");
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        if (cajaMulti) cajaMulti.classList.add('d-none');
    }
}

function obtenerBadgeModalidadHtmlEnc(modCodigo, numTramos = 0) {
    let mod = (modCodigo || 'ESTANDAR').toUpperCase();
    if (mod === 'PANA_EXPRESOS' || mod === 'PANA-EXPRESOS') {
        let t = numTramos > 0 ? `⚡ Pana-Expresos (${numTramos}T)` : '⚡ Pana-Expresos';
        return `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-bold">${t}</span>`;
    }
    if (mod === 'PANAMERICANA') {
        let t = numTramos > 0 ? `🚌 Panamericana (${numTramos}T)` : '🚌 Panamericana';
        return `<span class="badge bg-warning-subtle text-dark border border-warning fw-bold">${t}</span>`;
    }
    if (mod === 'EXPRESOS_SURAMERICA' || mod === 'SURAMERICA' || mod === 'EXPRESOS SURAMERICA') {
        let t = numTramos > 0 ? `🚍 Suramérica (${numTramos}T)` : '🚍 Suramérica 2030';
        return `<span class="badge bg-primary text-white fw-bold">${t}</span>`;
    }
    if (mod === 'CONEXIONES' || mod === 'CONEXION_TRAMOS') {
        let t = numTramos > 0 ? `🔀 Conexiones (${numTramos}T)` : '🔀 Conexiones Locales';
        return `<span class="badge bg-secondary text-white fw-bold">${t}</span>`;
    }
    return `<span class="badge bg-success text-white fw-bold">➡️ Directo Estándar</span>`;
}

function seleccionarRutaEspecificaEnc(rutaId) {
    let match = TODAS_LAS_RUTAS_ENC.find(r => r.ID_RUTA == rutaId);
    if (!match) return;

    let rutaIdInput = document.getElementById('ruta_id');
    let infoRuta = document.getElementById('info_ruta_enc');

    rutaIdInput.value = match.ID_RUTA;
    infoRuta.classList.remove('d-none');

    // Resaltar tarjeta seleccionada en encomiendas
    document.querySelectorAll('.card-modalidad-enc').forEach(c => {
        c.classList.remove('border-primary', 'bg-primary-subtle', 'border-2');
        c.classList.add('bg-white');
    });
    let cardActiva = document.getElementById('card_mod_enc_' + rutaId);
    if (cardActiva) {
        cardActiva.classList.remove('bg-white');
        cardActiva.classList.add('border-primary', 'bg-primary-subtle', 'border-2');
    }
    
    let badgeTipo = document.getElementById('badge_tipo_enc');
    if (match.TIPO_VIAJE === 'CONEXION' || (match.NUM_TRAMOS && match.NUM_TRAMOS > 1)) {
        badgeTipo.className = 'badge bg-warning text-dark fs-6';
        badgeTipo.textContent = '🔄 CONEXIÓN';
    } else {
        badgeTipo.className = 'badge bg-success fs-6';
        badgeTipo.textContent = '➡️ DIRECTO';
    }

    let badgeModEnc = document.getElementById('badge_modalidad_enc');
    if (badgeModEnc) {
        let bHtml = match.MODALIDAD_BADGE_HTML || obtenerBadgeModalidadHtmlEnc(match.MODALIDAD_CODIGO || match.MODALIDAD, match.NUM_TRAMOS);
        badgeModEnc.innerHTML = bHtml;
        badgeModEnc.style.display = 'inline-block';
    }

    let lblDuracion = document.getElementById('lbl_duracion_enc');
    if (lblDuracion) {
        lblDuracion.textContent = match.DURACION_FORMATO || (match.TIEMPO_ESTIMADO_HORAS > 0 ? `⏱️ ${match.TIEMPO_ESTIMADO_HORAS}h` : '');
        lblDuracion.style.display = lblDuracion.textContent ? 'inline-block' : 'none';
    }
    
    document.getElementById('badge_nombre_enc').textContent = match.RUT_ORIGEN + ' ➔ ' + match.RUT_DESTINO;
    document.getElementById('lbl_precio_kg').textContent = parseFloat(match.PRECIO_ENCOMIENDA_KG || 0).toFixed(2);
    document.getElementById('lbl_precio_sobre').textContent = parseFloat(match.PRECIO_SOBRE || 0).toFixed(2);
    document.getElementById('lbl_precio_vol').textContent = parseFloat(match.PRECIO_VOLUMEN || 0).toFixed(2);
    
    calcularCobroEncomienda();
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