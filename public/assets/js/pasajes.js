const TODAS_LAS_RUTAS = (window.PASAJES_CONFIG && window.PASAJES_CONFIG.rutas) ? window.PASAJES_CONFIG.rutas : [];
const CIUDAD_SUCURSAL_ACTUAL = (window.PASAJES_CONFIG && window.PASAJES_CONFIG.ciudadSucursal) ? window.PASAJES_CONFIG.ciudadSucursal : '';
const VENTAS_POR_DESTINO = (window.PASAJES_CONFIG && window.PASAJES_CONFIG.ventasPorDestino) ? window.PASAJES_CONFIG.ventasPorDestino : {};
const VENTAS_POR_ORIGEN = (window.PASAJES_CONFIG && window.PASAJES_CONFIG.ventasPorOrigen) ? window.PASAJES_CONFIG.ventasPorOrigen : {};

let searchableOrigen = null;
let searchableDestino = null;

// =============== DINÁMICA DE PASAJEROS MULTIPLES ===============
let paxCount = 1;

function agregarPasajero() {
    let index = paxCount++;
    let abierto = document.getElementById('fecha_abierta').checked;
    
    let rutaId = document.getElementById('ruta_id_nuevo').value;
    let rObj = TODAS_LAS_RUTAS.find(r => r.ID_RUTA == rutaId);
    let esConexion = rObj ? (rObj.TIPO_VIAJE === 'CONEXION') : false;
    
    let reqAttr = (abierto || esConexion) ? '' : 'required';
    let disAttr = abierto ? 'disabled' : '';

    let html = `
        <div class="pasajero-row border p-3 mb-3 bg-white rounded position-relative shadow-sm">
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="this.closest('.pasajero-row').remove(); ponerPrecioDefault();">❌ Quitar</button>
            <h6 class="text-secondary fw-bold">👤 Pasajero Adicional</h6>
            <div class="row mb-2">
                <div class="col-md-3"><label>Cédula/Pasaporte</label><input type="text" name="cliente_id[]" id="cliente_id_${index}" class="form-control" required onchange="buscarClienteAuto(this.value, ${index})"></div>
                <div class="col-md-3"><label>Nombres</label><input type="text" name="cliente_nombre[]" id="cliente_nombre_${index}" class="form-control" required></div>
                <div class="col-md-3"><label>Apellidos</label><input type="text" name="cliente_apellido[]" id="cliente_apellido_${index}" class="form-control" required></div>
                <div class="col-md-3"><label>Teléfono</label><input type="text" name="cliente_telefono[]" id="cliente_telefono_${index}" class="form-control" required></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><label>Correo Electrónico</label><input type="email" name="cliente_correo[]" id="cliente_correo_${index}" class="form-control" placeholder="Opcional"></div>
                <div class="col-md-4"><label>Nacionalidad</label><input list="lista_nacionalidades" name="cliente_nacionalidad[]" id="cliente_nacionalidad_${index}" class="form-control text-uppercase" value="ECUATORIANA" required></div>
                <div class="col-md-4"><label>Fecha Nacimiento</label><input type="date" name="cliente_fechanac[]" id="cliente_fechanac_${index}" class="form-control"></div>
            </div>
            <div class="row mb-1 mt-3">
                <div class="col-md-4">
                    <label class="fw-bold">N° Asiento</label>
                    <div class="input-group">
                        <input type="text" name="asiento[]" id="asiento_nuevo_${index}" class="form-control text-center fw-bold bg-white border-dark" readonly ${reqAttr} ${disAttr} placeholder="-">
                        <button type="button" class="btn btn-dark" onclick="abrirMapaAsientos('nuevo', null, 'nuevo', 'asiento_nuevo_${index}')" ${disAttr}>📍 Elegir</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('contenedor_pasajeros').insertAdjacentHTML('beforeend', html);
    ponerPrecioDefault();
}

function buscarClienteAuto(id, index) {
    if (id.length >= 4) {
        fetch('/suramerica2030/pasajes/buscarCliente?id=' + id)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                let c = res.data;
                const nomEl = document.getElementById('cliente_nombre_' + index);
                const apeEl = document.getElementById('cliente_apellido_' + index);
                const nacEl = document.getElementById('cliente_nacionalidad_' + index);

                if (nomEl) { nomEl.value = c.CLI_NOMBRE || ''; nomEl.dispatchEvent(new Event('input', { bubbles: true })); }
                if (apeEl) { apeEl.value = c.CLI_APELLIDO || ''; apeEl.dispatchEvent(new Event('input', { bubbles: true })); }
                document.getElementById('cliente_telefono_' + index).value = c.CLI_TELEFONO || '';
                document.getElementById('cliente_correo_' + index).value = c.CLI_CORREO || '';
                if (nacEl && c.CLI_NACIONALIDAD) { nacEl.value = c.CLI_NACIONALIDAD; nacEl.dispatchEvent(new Event('input', { bubbles: true })); }
                if (c.CLI_FECHANAC) document.getElementById('cliente_fechanac_' + index).value = c.CLI_FECHANAC;
            }
        });
    }
}

// =============== LÓGICA DE ITINERARIO (ORIGEN / DESTINO) Y PRECIOS ===============
function limpiarAsientosNuevos() {
    document.querySelectorAll('input[name="asiento[]"]').forEach(el => el.value = '');
}

function getRutasValidasPorFecha() {
    let abierto = document.getElementById('fecha_abierta').checked;
    if (abierto) return TODAS_LAS_RUTAS;
    
    let fechaVal = document.getElementById('fecha_viaje_nuevo').value;
    if (!fechaVal) return TODAS_LAS_RUTAS;
    
    let d = new Date(fechaVal + 'T00:00:00');
    let diaPhp = d.getDay() === 0 ? 7 : d.getDay(); // 1: Lun ... 7: Dom
    
    return TODAS_LAS_RUTAS.filter(r => {
        if (!r.DIAS_SALIDA) return true;
        let dias = r.DIAS_SALIDA.split(',').map(s => s.trim());
        return dias.includes(diaPhp.toString());
    });
}

function filtrarRutasPorFecha() {
    let rutasValidas = getRutasValidasPorFecha();
    let selOrigen = document.getElementById('sel_origen_pasaje');
    let selDestino = document.getElementById('sel_destino_pasaje');
    
    let origenPrevio = selOrigen.value;
    let destinoPrevio = selDestino.value;
    
    // Obtenemos lista única de orígenes disponibles y ordenamos descendentemente por pasajes vendidos
    let origenesUnicos = Array.from(new Set(rutasValidas.map(r => r.RUT_ORIGEN)));
    origenesUnicos.sort((a, b) => {
        let cantA = VENTAS_POR_ORIGEN[a] || 0;
        let cantB = VENTAS_POR_ORIGEN[b] || 0;
        if (cantB !== cantA) return cantB - cantA; // Más vendidos primero
        return a.localeCompare(b);
    });
    
    selOrigen.innerHTML = '<option value="">- Seleccione Origen -</option>';
    origenesUnicos.forEach(orig => {
        let opt = document.createElement('option');
        opt.value = orig;
        let vts = VENTAS_POR_ORIGEN[orig] || 0;
        opt.setAttribute('data-ventas', vts);
        opt.textContent = '📍 ' + orig;
        selOrigen.appendChild(opt);
    });
    
    // Intentamos restaurar el origen seleccionado previamente o el de la sucursal del usuario
    if (origenPrevio && origenesUnicos.includes(origenPrevio)) {
        selOrigen.value = origenPrevio;
    } else if (!origenPrevio && CIUDAD_SUCURSAL_ACTUAL) {
        let matchSuc = origenesUnicos.find(o => o.toUpperCase() === CIUDAD_SUCURSAL_ACTUAL);
        if (matchSuc) selOrigen.value = matchSuc;
    }
    
    alCambiarOrigenPasaje(destinoPrevio);
}

function alCambiarOrigenPasaje(forzarDestino = null) {
    let origen = document.getElementById('sel_origen_pasaje').value;
    let selDestino = document.getElementById('sel_destino_pasaje');
    let rutaIdInput = document.getElementById('ruta_id_nuevo');
    let infoRuta = document.getElementById('info_ruta_seleccionada');
    let cajaMulti = document.getElementById('caja_multiple_horario');
    
    if (!origen) {
        selDestino.innerHTML = '<option value="">- Elija Origen Primero -</option>';
        selDestino.disabled = true;
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        cajaMulti.classList.add('d-none');
        ponerPrecioDefault();
        limpiarAsientosNuevos();
        return;
    }
    
    let rutasValidas = getRutasValidasPorFecha().filter(r => r.RUT_ORIGEN === origen);
    let destinosUnicos = Array.from(new Set(rutasValidas.map(r => r.RUT_DESTINO)));
    
    // Ordenar de forma descendente en función de cuántos pasajes están vendidos (más usados arriba)
    destinosUnicos.sort((a, b) => {
        let cantA = VENTAS_POR_DESTINO[a] || 0;
        let cantB = VENTAS_POR_DESTINO[b] || 0;
        if (cantB !== cantA) return cantB - cantA; // Más vendidos primero
        return a.localeCompare(b);
    });
    
    selDestino.innerHTML = '<option value="">- Seleccione Destino -</option>';
    destinosUnicos.forEach(dest => {
        let opt = document.createElement('option');
        opt.value = dest;
        let vts = VENTAS_POR_DESTINO[dest] || 0;
        opt.setAttribute('data-ventas', vts);
        opt.textContent = '🏁 ' + dest;
        selDestino.appendChild(opt);
    });
    selDestino.disabled = false;
    
    if (forzarDestino && destinosUnicos.includes(forzarDestino)) {
        selDestino.value = forzarDestino;
        alCambiarDestinoPasaje();
    } else {
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        cajaMulti.classList.add('d-none');
        ponerPrecioDefault();
        limpiarAsientosNuevos();
    }
}

function alCambiarDestinoPasaje() {
    let origen = document.getElementById('sel_origen_pasaje').value;
    let destino = document.getElementById('sel_destino_pasaje').value;
    let rutaIdInput = document.getElementById('ruta_id_nuevo');
    let infoRuta = document.getElementById('info_ruta_seleccionada');
    let cajaMulti = document.getElementById('caja_multiple_horario');
    let selHorario = document.getElementById('sel_horario_pasaje');
    
    if (!origen || !destino) {
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        cajaMulti.classList.add('d-none');
        ponerPrecioDefault();
        limpiarAsientosNuevos();
        return;
    }
    
    let matches = getRutasValidasPorFecha().filter(r => r.RUT_ORIGEN === origen && r.RUT_DESTINO === destino);
    if (matches.length === 0) {
        matches = TODAS_LAS_RUTAS.filter(r => r.RUT_ORIGEN === origen && r.RUT_DESTINO === destino);
    }
    
    if (matches.length === 1) {
        cajaMulti.classList.add('d-none');
        seleccionarRutaEspecifica(matches[0].ID_RUTA);
    } else if (matches.length > 1) {
        cajaMulti.classList.remove('d-none');
        selHorario.innerHTML = '';
        matches.forEach(m => {
            let opt = document.createElement('option');
            opt.value = m.ID_RUTA;
            
            let label = m.ETIQUETA_MODALIDAD || '';
            if (!label) {
                let esConex = (m.TIPO_VIAJE === 'CONEXION') ? 'Transbordo' : 'Directo';
                let modStr = (m.MODALIDAD === 'PANAMERICANA' || m.TIENE_PANAMERICANA) 
                    ? '⭐ Vía Panamericana (Directo a Cúcuta)' 
                    : ((m.MODALIDAD === 'CONEXION_TRAMOS' || (m.TRAMOS && m.TRAMOS.length >= 3)) ? '🔀 3 Conexiones / Tramos' : esConex);
                label = modStr;
            }
            
            let hora = m.HORA_SALIDA ? m.HORA_SALIDA.substring(0, 5) : (m.RUT_SAHORA ? m.RUT_SAHORA.substring(0, 5) : '08:00');
            opt.textContent = `${label} — Tarifa: $${parseFloat(m.PRECIO_PASAJE).toFixed(2)} (Salida: ${hora})`;
            selHorario.appendChild(opt);
        });
        seleccionarRutaEspecifica(matches[0].ID_RUTA);
    } else {
        alert("No se encontró una ruta activa entre estas dos ciudades.");
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        cajaMulti.classList.add('d-none');
    }
}

function seleccionarRutaEspecifica(rutaId) {
    let r = TODAS_LAS_RUTAS.find(x => x.ID_RUTA == rutaId);
    if (!r) return;
    
    document.getElementById('ruta_id_nuevo').value = r.ID_RUTA;
    
    // Actualizamos Ficha Resumen
    let infoRuta = document.getElementById('info_ruta_seleccionada');
    infoRuta.classList.remove('d-none');
    
    let badgeTipo = document.getElementById('badge_tipo_viaje');
    let esConexion = (r.TIPO_VIAJE === 'CONEXION');
    if (esConexion) {
        badgeTipo.className = 'badge bg-warning text-dark fs-6';
        badgeTipo.innerHTML = '🔄 CONEXIÓN';
    } else {
        badgeTipo.className = 'badge bg-success fs-6';
        badgeTipo.innerHTML = '➡️ DIRECTO';
    }
    
    let modBadge = document.getElementById('badge_modalidad');
    if (!modBadge) {
        modBadge = document.createElement('span');
        modBadge.id = 'badge_modalidad';
        modBadge.className = 'badge ms-1 fs-6';
        badgeTipo.parentNode.insertBefore(modBadge, document.getElementById('badge_horario'));
    }
    if (r.MODALIDAD === 'PANAMERICANA' || r.TIENE_PANAMERICANA) {
        modBadge.className = 'badge bg-primary text-white fs-6 ms-1';
        modBadge.innerHTML = '⭐ Vía Panamericana';
        modBadge.style.display = 'inline-block';
    } else if (r.MODALIDAD === 'CONEXION_TRAMOS' || (r.TRAMOS && r.TRAMOS.length >= 3)) {
        let nConex = (r.TRAMOS && r.TRAMOS.length) ? r.TRAMOS.length + ' Conexiones' : '3 Conexiones';
        modBadge.className = 'badge bg-secondary text-white fs-6 ms-1';
        modBadge.innerHTML = '🔀 ' + nConex;
        modBadge.style.display = 'inline-block';
    } else if (r.MODALIDAD && r.MODALIDAD !== 'ESTANDAR') {
        modBadge.className = 'badge bg-info text-dark fs-6 ms-1';
        modBadge.innerHTML = r.MODALIDAD;
        modBadge.style.display = 'inline-block';
    } else {
        modBadge.style.display = 'none';
    }
    
    let horaStr = r.HORA_SALIDA ? r.HORA_SALIDA.substring(0, 5) : (r.RUT_SAHORA ? r.RUT_SAHORA.substring(0, 5) : '08:00');
    document.getElementById('badge_horario').textContent = '⏰ ' + horaStr;
    document.getElementById('badge_tramo_nombre').textContent = r.RUT_ORIGEN + ' ➔ ' + r.RUT_DESTINO;
    document.getElementById('badge_precio').textContent = '$' + parseFloat(r.PRECIO_PASAJE).toFixed(2);
    document.getElementById('badge_equipaje').textContent = '🧳 ' + parseFloat(r.LIMITE_EQUIPAJE_KG || 35) + ' Kg máx';

    // Desglose interactivo de Tramos y Operadores
    let contenedorTramos = document.getElementById('detalle_tramos_ruta');
    if (contenedorTramos) {
        if (r.TRAMOS && r.TRAMOS.length > 0) {
            contenedorTramos.classList.remove('d-none');
            let isPanamRuta = (r.MODALIDAD === 'PANAMERICANA' || r.TIENE_PANAMERICANA);
            let badgeRutaTipo = isPanamRuta 
                ? '<span class="badge bg-primary text-white"><i class="fas fa-star me-1"></i>Vía Panamericana</span>' 
                : `<span class="badge bg-secondary text-white"><i class="fas fa-random me-1"></i>${r.TRAMOS.length} Conexiones</span>`;
            
            let htmlTramos = `
                <div class="card border-0 bg-light p-2 rounded shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                        <span class="fw-bold text-dark small">
                            <i class="fas fa-network-wired text-primary me-1"></i> Desglose de Tramos y Empresas (${r.TRAMOS.length} tramos):
                        </span>
                        ${badgeRutaTipo}
                    </div>
                    <div class="d-flex flex-column gap-1">
            `;
            r.TRAMOS.forEach((tr, idx) => {
                let isPanam = (tr.ID_OPERADOR == 2) || (tr.OPERADOR && tr.OPERADOR.toUpperCase().includes('PANAMERICANA')) || (tr.OPERADOR_TRAMO_NOMBRE && tr.OPERADOR_TRAMO_NOMBRE.toUpperCase().includes('PANAMERICANA'));
                let opBadgeClass = isPanam ? 'badge bg-primary text-white' : 'badge bg-success text-white';
                let opIcon = isPanam ? 'fa-star' : 'fa-bus';
                let opNombre = isPanam ? 'PANAMERICANA' : (tr.OPERADOR_TRAMO_NOMBRE || tr.OPERADOR || 'Suramérica 2030');
                let pvpNum = parseFloat(tr.PVP_TRAMO || 0);
                let pvpBadge = pvpNum > 0 ? `<span class="badge bg-white text-dark border ms-1">$${pvpNum.toFixed(2)}</span>` : '';
                
                htmlTramos += `
                    <div class="d-flex justify-content-between align-items-center bg-white px-2 py-1 rounded border small">
                        <div>
                            <span class="badge bg-dark me-1">Tramo ${tr.ORDEN || (idx + 1)}</span>
                            <strong>${tr.ORIGEN_TRAMO}</strong> <i class="fas fa-arrow-right text-muted mx-1"></i> <strong>${tr.DESTINO_TRAMO}</strong>
                        </div>
                        <div>
                            <span class="${opBadgeClass}"><i class="fas ${opIcon} me-1"></i>${opNombre}</span>
                            ${pvpBadge}
                        </div>
                    </div>
                `;
            });
            htmlTramos += `
                    </div>
                </div>
            `;
            contenedorTramos.innerHTML = htmlTramos;
        } else {
            contenedorTramos.classList.add('d-none');
            contenedorTramos.innerHTML = '';
        }
    }
    
    let alertaTramos = document.getElementById('alerta_tramos');
    let busIdSelect = document.getElementById('bus_id_nuevo');
    let asientosInputs = document.querySelectorAll('input[name="asiento[]"]');
    
    if (esConexion) {
        alertaTramos.classList.remove('d-none');
        busIdSelect.required = false;
        asientosInputs.forEach(el => el.required = false);
    } else {
        alertaTramos.classList.add('d-none');
        if (!document.getElementById('fecha_abierta').checked) {
            busIdSelect.required = true;
            asientosInputs.forEach(el => el.required = true);
        }
    }
    
    ponerPrecioDefault();
    limpiarAsientosNuevos();
}

function ponerPrecioDefault() {
    let rutaId = document.getElementById('ruta_id_nuevo').value;
    let r = TODAS_LAS_RUTAS.find(x => x.ID_RUTA == rutaId);
    
    if (!r) {
        document.getElementById('precio_total').value = '0.00';
        document.getElementById('monto_abonar').value = '0.00';
        return;
    }
    
    let precioBase = parseFloat(r.PRECIO_PASAJE) || 0;
    let cantPasajeros = document.querySelectorAll('.pasajero-row').length;
    let totalGlobal = precioBase * cantPasajeros;
    
    document.getElementById('precio_total').value = totalGlobal.toFixed(2);
    document.getElementById('monto_abonar').value = totalGlobal.toFixed(2);
}

function toggleItinerario() {
    let abierto = document.getElementById('fecha_abierta').checked;
    let inputFecha = document.getElementById('fecha_viaje_nuevo');
    let busId = document.getElementById('bus_id_nuevo');
    let asientos = document.querySelectorAll('input[name="asiento[]"]');
    let btnAsientos = document.querySelectorAll('button[onclick^="abrirMapaAsientos"]');
    
    let rutaId = document.getElementById('ruta_id_nuevo').value;
    let rObj = TODAS_LAS_RUTAS.find(r => r.ID_RUTA == rutaId);
    let esConexion = rObj ? (rObj.TIPO_VIAJE === 'CONEXION') : false;
    
    if (abierto) {
        inputFecha.required = false; inputFecha.disabled = true;
        busId.required = false; busId.disabled = true;
        asientos.forEach(el => { el.required = false; el.value = ''; el.disabled = true; });
        btnAsientos.forEach(el => { el.disabled = true; });
    } else {
        inputFecha.required = true; inputFecha.disabled = false;
        if (!esConexion) {
            busId.required = true;
            asientos.forEach(el => { el.required = true; });
        }
        busId.disabled = false;
        asientos.forEach(el => { el.disabled = false; });
        btnAsientos.forEach(el => { el.disabled = false; });
    }
    filtrarRutasPorFecha();
}

document.addEventListener('DOMContentLoaded', function() {
    filtrarRutasPorFecha();

    const selOrigen = document.getElementById('sel_origen_pasaje');
    const selDestino = document.getElementById('sel_destino_pasaje');

    if (window.SearchableSelect) {
        if (selOrigen) {
            searchableOrigen = new SearchableSelect(selOrigen, {
                sortBySales: true,
                salesData: VENTAS_POR_ORIGEN,
                placeholder: 'Seleccionar Origen',
                showBadges: true,
                uppercase: true
            });
        }
        if (selDestino) {
            searchableDestino = new SearchableSelect(selDestino, {
                sortBySales: true,
                salesData: VENTAS_POR_DESTINO,
                placeholder: 'Seleccionar',
                showBadges: true,
                uppercase: true
            });
        }
    }
});

// =============== LÓGICA DE MAPA DE ASIENTOS CON CONTROL DE COLISIONES ===============
function abrirMapaParaReasignar(idPasaje, fecha, ruta, bus, asientoActual, totalAsientos) {
    document.getElementById('footerReasignar').style.display = 'block';
    document.getElementById('form_id_pasaje').value = idPasaje;
    document.getElementById('target_input_asiento').value = 'form_nuevo_asiento';
    document.getElementById('btnConfirmarReasignar').disabled = true;
    generarYMostrarCroquis(fecha, ruta, bus, totalAsientos || 42, asientoActual, 'form_nuevo_asiento');
}

function abrirMapaAsientos(idPasaje, idRuta, tipo, targetId) {
    document.getElementById('footerReasignar').style.display = 'none';
    let fecha, ruta, busElem, asientostotales;

    if (tipo === 'nuevo') {
        fecha = document.getElementById('fecha_viaje_nuevo').value;
        ruta = document.getElementById('ruta_id_nuevo').value;
        busElem = document.getElementById('bus_id_nuevo');
        document.getElementById('target_input_asiento').value = targetId;
    } else {
        fecha = document.getElementById('fecha_asig_' + idPasaje).value;
        ruta = idRuta;
        busElem = document.getElementById('bus_asig_' + idPasaje);
        document.getElementById('target_input_asiento').value = 'asiento_asig_' + idPasaje;
    }

    if (!fecha || !ruta || !busElem.value) {
        alert("Por favor seleccione primero la Fecha, la Ruta y el Bus.");
        return;
    }
    asientostotales = parseInt(busElem.selectedOptions[0].getAttribute('data-asientos')) || 42;
    generarYMostrarCroquis(fecha, ruta, busElem.value, asientostotales, null, targetId);
}

function generarYMostrarCroquis(fecha, ruta, bus, totalAsientos, asientoActualReasignar, targetInputId) {
    let contenedor = document.getElementById('dibujo_asientos');
    contenedor.innerHTML = '<p>Cargando asientos...</p>';
    let modal = new bootstrap.Modal(document.getElementById('modalCroquisBus'));
    modal.show();

    let asientosSeleccionadosEnForm = [];
    document.querySelectorAll('input[name="asiento[]"]').forEach(el => {
        if(el.value !== "" && el.id !== targetInputId) {
            asientosSeleccionadosEnForm.push(parseInt(el.value));
        }
    });

    fetch(`/suramerica2030/pasajes/apiAsientosOcupados?fecha=${fecha}&ruta=${ruta}&bus=${bus}`)
        .then(response => response.json())
        .then(ocupados => {
            // Convertimos la respuesta stringificada de PHP a números enteros puros
            ocupados = ocupados.map(Number);
            ocupados = [...ocupados, ...asientosSeleccionadosEnForm]; 
            
            let html = '<div class="volante"></div><div class="asiento-grid">';
            for (let i = 1; i <= totalAsientos; i++) {
                let isOcupado = ocupados.includes(i);
                if (asientoActualReasignar && i === parseInt(asientoActualReasignar)) { isOcupado = false; }
                
                let clase = isOcupado ? 'asiento ocupado' : 'asiento';
                let onclick = isOcupado ? '' : `onclick="seleccionarAsiento(${i})"`;
                let icon = isOcupado ? 'X' : i;
                html += `<div class="${clase}" ${onclick} id="box_asiento_${i}">${icon}</div>`;
                if (i % 2 === 0 && i % 4 !== 0) { html += `<div class="pasillo"></div>`; }
            }
            html += '</div>';
            contenedor.innerHTML = html;
        });
}

function seleccionarAsiento(numero) {
    document.querySelectorAll('.asiento').forEach(el => {
        if(!el.classList.contains('ocupado')) { el.style.background = '#e8f5e9'; el.style.color = '#155724'; }
    });
    let box = document.getElementById('box_asiento_' + numero);
    box.style.background = '#155724';
    box.style.color = 'white';
    
    let target = document.getElementById('target_input_asiento').value;
    document.getElementById(target).value = numero;
    if(target === 'form_nuevo_asiento') { document.getElementById('btnConfirmarReasignar').disabled = false; }
    
    if(document.getElementById('footerReasignar').style.display === 'none') {
        setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('modalCroquisBus')).hide(); }, 300);
    }
}

function limpiarAsiento(id) { document.getElementById('asiento_asig_' + id).value = ''; }

function agregarPiezaEquipaje(idPasaje, precioExtra, limiteKg) {
    const contenedor = document.getElementById('contenedor_piezas_eq_' + idPasaje);
    if (!contenedor) return;

    const fila = document.createElement('div');
    fila.className = 'row g-2 pieza-equipaje-row mb-2 align-items-end';
    fila.setAttribute('data-pasaje', idPasaje);
    fila.innerHTML = `
        <div class="col-md-3 col-6">
            <label class="form-label mb-0" style="font-size:11px;">Tipo</label>
            <select name="eq_tipo[]" class="form-select form-select-sm" onchange="calcularCobroEquipaje(${idPasaje}, ${precioExtra}, ${limiteKg})">
                <option value="Maleta">Maleta</option>
                <option value="Bolso">Bolso / Maletín</option>
                <option value="Caja">Caja</option>
                <option value="Bulto">Bulto / Saco</option>
                <option value="Mochila">Mochila</option>
                <option value="Otro">Otro</option>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label mb-0" style="font-size:11px;">Peso (Kg)</label>
            <input type="number" step="0.1" min="0" name="eq_peso[]" class="form-control form-control-sm text-end fw-bold eq-peso-input" placeholder="0.0" required oninput="calcularCobroEquipaje(${idPasaje})" onchange="calcularCobroEquipaje(${idPasaje})">
        </div>
        <div class="col-md-6 col-10">
            <label class="form-label mb-0" style="font-size:11px;">Descripción (opcional)</label>
            <input type="text" name="eq_descripcion[]" class="form-control form-control-sm" placeholder="Ej: Ropa, Frágil...">
        </div>
        <div class="col-md-1 col-2 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="eliminarPiezaEquipaje(this, ${idPasaje})" title="Eliminar pieza">×</button>
        </div>
    `;
    contenedor.appendChild(fila);
    calcularCobroEquipaje(idPasaje);
}

function eliminarPiezaEquipaje(btn, idPasaje, precioExtra, limiteKg) {
    const contenedor = document.getElementById('contenedor_piezas_eq_' + idPasaje);
    if (!contenedor) return;

    const filas = contenedor.querySelectorAll('.pieza-equipaje-row');
    if (filas.length > 1) {
        btn.closest('.pieza-equipaje-row').remove();
    } else {
        const fila = filas[0];
        const inputPeso = fila.querySelector('input[name="eq_peso[]"]');
        const inputDesc = fila.querySelector('input[name="eq_descripcion[]"]');
        if (inputPeso) inputPeso.value = '';
        if (inputDesc) inputDesc.value = '';
    }
    calcularCobroEquipaje(idPasaje, precioExtra, limiteKg);
}

function alCambiarTramoEquipaje(idPasaje) {
    const selTramo = document.getElementById('sel_tramo_eq_' + idPasaje);
    if (!selTramo || selTramo.tagName !== 'SELECT' || !selTramo.selectedOptions || selTramo.selectedOptions.length === 0) return;
    const opt = selTramo.selectedOptions[0];
    const limite = parseFloat(opt.getAttribute('data-limite')) || 35.0;
    const tarifa = parseFloat(opt.getAttribute('data-tarifa')) || 1.0;
    
    // Forzar actualización del valor sugerido de sobrepeso
    const cxInput = document.getElementById('cx_' + idPasaje);
    if (cxInput) cxInput.setAttribute('data-auto', '1');
    
    calcularCobroEquipaje(idPasaje, tarifa, limite);
}

function calcularCobroEquipaje(idPasaje, precioExtra, limiteKg) {
    const contenedor = document.getElementById('contenedor_piezas_eq_' + idPasaje);
    if (!contenedor) return;

    // Obtener tarifa y límite por defecto si no vinieron
    const tarifaInput = document.getElementById('tarifa_input_eq_' + idPasaje);
    if (precioExtra === undefined || precioExtra === null || isNaN(precioExtra)) {
        precioExtra = tarifaInput ? (parseFloat(tarifaInput.value) || 1.0) : 1.0;
    }
    const limiteInput = document.getElementById('limite_input_eq_' + idPasaje);
    if (limiteKg === undefined || limiteKg === null || isNaN(limiteKg)) {
        limiteKg = limiteInput ? (parseFloat(limiteInput.value) || 35.0) : 35.0;
    }

    // Si existe el selector de tramo (SELECT), sobreescribir la tarifa y el límite según la opción elegida
    const selTramo = document.getElementById('sel_tramo_eq_' + idPasaje);
    if (selTramo && selTramo.tagName === 'SELECT' && selTramo.selectedOptions && selTramo.selectedOptions.length > 0) {
        const opt = selTramo.selectedOptions[0];
        const limiteOpt = parseFloat(opt.getAttribute('data-limite'));
        const tarifaOpt = parseFloat(opt.getAttribute('data-tarifa'));
        if (!isNaN(limiteOpt) && limiteOpt > 0) limiteKg = limiteOpt;
        if (!isNaN(tarifaOpt) && tarifaOpt >= 0) precioExtra = tarifaOpt;
        
        const operador = opt.getAttribute('data-operador') || 'General';
        const admite = opt.getAttribute('data-admite') === '1';
        
        const lblOp = document.getElementById('lbl_operador_eq_' + idPasaje);
        if (lblOp) lblOp.textContent = operador;
        const lblLim = document.getElementById('lbl_limite_eq_' + idPasaje);
        if (lblLim) lblLim.textContent = limiteKg.toFixed(1) + ' Kg';
        const lblTar = document.getElementById('lbl_tarifa_eq_' + idPasaje);
        if (lblTar) lblTar.textContent = '$' + precioExtra.toFixed(2) + '/Kg';
        
        if (tarifaInput) tarifaInput.value = precioExtra.toFixed(2);
        if (limiteInput) limiteInput.value = limiteKg.toFixed(1);
        
        const alertaNoAdmite = document.getElementById('alerta_no_admite_eq_' + idPasaje);
        if (alertaNoAdmite) {
            if (!admite) alertaNoAdmite.classList.remove('d-none');
            else alertaNoAdmite.classList.add('d-none');
        }
    }

    const pesoMano = 10.0; // Maleta de mano fija (10kg)
    let totalBodega = 0.0;
    let cantidadPiezas = 0;

    const filas = contenedor.querySelectorAll('.pieza-equipaje-row');
    filas.forEach(fila => {
        const pesoEl = fila.querySelector('input[name="eq_peso[]"]');
        if (!pesoEl) return;
        const rawVal = (pesoEl.value || '').toString().trim().replace(',', '.');
        const peso = parseFloat(rawVal) || 0;

        if (peso > 0 || rawVal !== '') {
            cantidadPiezas++;
        }
        totalBodega += peso;
    });

    totalBodega = Math.round(totalBodega * 10) / 10;
    const totalGlobal = Math.round((totalBodega + pesoMano) * 10) / 10;
    const limite = parseFloat(limiteKg) || 35.0;

    const cantEl = document.getElementById('peq_cant_' + idPasaje);
    const bodEl = document.getElementById('peq_bodega_' + idPasaje);
    const manEl = document.getElementById('peq_mano_' + idPasaje);
    const totEl = document.getElementById('pt_' + idPasaje);
    const pmInput = document.getElementById('pm_' + idPasaje);
    const pbInput = document.getElementById('pb_' + idPasaje);

    if (cantEl) cantEl.innerText = Math.max(1, cantidadPiezas);
    if (bodEl) bodEl.innerText = totalBodega.toFixed(1);
    if (manEl) manEl.innerText = pesoMano.toFixed(1);
    if (totEl) totEl.innerText = totalGlobal.toFixed(1);

    if (pmInput) pmInput.value = pesoMano.toFixed(1);
    if (pbInput) pbInput.value = totalBodega.toFixed(1);

    const warnEl = document.getElementById('warn_' + idPasaje);
    const pexEl = document.getElementById('pex_' + idPasaje);
    const cobroEl = document.getElementById('cobro_' + idPasaje);
    const cxInput = document.getElementById('cx_' + idPasaje);
    const sugEl = document.getElementById('sug_' + idPasaje);

    if (totalGlobal > limite) {
        const exceso = Math.round((totalGlobal - limite) * 10) / 10;
        const tarifaBase = (exceso * parseFloat(precioExtra)).toFixed(2);

        if (warnEl) warnEl.classList.remove('d-none');
        if (pexEl) pexEl.innerText = exceso.toFixed(1);
        if (cobroEl) cobroEl.classList.remove('d-none');

        if (sugEl) sugEl.innerText = '(Base: $' + tarifaBase + ')';
        if (cxInput && (cxInput.value === '' || cxInput.value === '0' || cxInput.value === '0.00' || cxInput.getAttribute('data-auto') === '1' || cxInput.readOnly)) {
            cxInput.value = tarifaBase;
            cxInput.setAttribute('data-auto', '1');
            if (typeof balancearMixtoEquipaje === 'function') {
                balancearMixtoEquipaje(idPasaje, 'total');
            }
        }
    } else {
        if (warnEl) warnEl.classList.add('d-none');
        if (cobroEl) cobroEl.classList.add('d-none');
        if (cxInput) {
            cxInput.value = '0.00';
            cxInput.removeAttribute('data-auto');
            if (typeof balancearMixtoEquipaje === 'function') {
                balancearMixtoEquipaje(idPasaje, 'total');
            }
        }
        if (sugEl) sugEl.innerText = '';
    }
}

// Delegación global para eventos de input y change en piezas de equipaje
document.addEventListener('input', function (e) {
    if (e.target && e.target.name === 'eq_peso[]') {
        const row = e.target.closest('.pieza-equipaje-row');
        if (row && row.getAttribute('data-pasaje')) {
            calcularCobroEquipaje(row.getAttribute('data-pasaje'));
        }
    }
});
document.addEventListener('change', function (e) {
    if (e.target && (e.target.name === 'eq_peso[]' || e.target.name === 'eq_tipo[]')) {
        const row = e.target.closest('.pieza-equipaje-row');
        if (row && row.getAttribute('data-pasaje')) {
            calcularCobroEquipaje(row.getAttribute('data-pasaje'));
        }
    }
});

// Recalcular equipaje automáticamente al abrir cualquier modal de equipaje
['show.bs.modal', 'shown.bs.modal'].forEach(function (evtName) {
    document.addEventListener(evtName, function (e) {
        if (e.target && e.target.id && e.target.id.startsWith('modalEquipaje')) {
            const idPasaje = e.target.id.replace('modalEquipaje', '');
            calcularCobroEquipaje(idPasaje);
        }
    });
});

// =============== GESTIÓN DE ACTUALIZACIÓN FINANCIERA ===============
function aplicarFinanciero(id, precio, saldo, estado) {
    const pInput = document.getElementById('fin_precio_' + id);
    const sInput = document.getElementById('fin_saldo_' + id);
    const eSelect = document.getElementById('fin_estado_' + id);
    if (pInput) pInput.value = parseFloat(precio).toFixed(2);
    if (sInput) sInput.value = parseFloat(saldo).toFixed(2);
    if (eSelect) eSelect.value = estado;
}

function marcarPagado(id) {
    const sInput = document.getElementById('fin_saldo_' + id);
    const eSelect = document.getElementById('fin_estado_' + id);
    if (sInput) sInput.value = '0.00';
    if (eSelect) eSelect.value = 'PAGADO';
}

function recalcularFinanciero(id) {
    const sInput = document.getElementById('fin_saldo_' + id);
    const eSelect = document.getElementById('fin_estado_' + id);
    if (!sInput || !eSelect) return;
    const saldo = parseFloat(sInput.value) || 0;
    if (saldo <= 0) {
        eSelect.value = 'PAGADO';
    } else {
        eSelect.value = 'RESERVA';
    }
}

// Si en nueva venta el abono supera el total asignado, ajustar el total automáticamente
const montoAbonarEl = document.getElementById('monto_abonar');
if (montoAbonarEl) {
    montoAbonarEl.addEventListener('input', function() {
        let abono = parseFloat(this.value) || 0;
        let totalEl = document.getElementById('precio_total');
        if (totalEl) {
            let total = parseFloat(totalEl.value) || 0;
            if (abono > total) {
                totalEl.value = abono.toFixed(2);
            }
        }
    });
}

// =============== FILTROS RÁPIDOS HISTORIAL PASAJES ===============
function aplicarRangoRapido(tipo) {
    const dInicio = document.getElementById('filtro_fecha_inicio');
    const dFin = document.getElementById('filtro_fecha_fin');
    const tipoFecha = document.getElementById('filtro_tipo_fecha');
    const form = document.getElementById('formFiltroHistorial');
    if (!dInicio || !dFin) return;

    if (tipoFecha && tipoFecha.value === 'abierta') {
        tipoFecha.value = 'viaje';
        actualizarEstadoCamposFecha();
    }

    const hoy = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const formatear = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (tipo === 'hoy') {
        const strHoy = formatear(hoy);
        dInicio.value = strHoy;
        dFin.value = strHoy;
    } else if (tipo === 'manana') {
        const manana = new Date(hoy);
        manana.setDate(hoy.getDate() + 1);
        const strManana = formatear(manana);
        dInicio.value = strManana;
        dFin.value = strManana;
    } else if (tipo === 'esta_semana') {
        const dia = hoy.getDay(); // 0 es domingo
        const diffLunes = hoy.getDate() - dia + (dia === 0 ? -6 : 1);
        const lunes = new Date(hoy);
        lunes.setDate(diffLunes);
        const domingo = new Date(lunes);
        domingo.setDate(lunes.getDate() + 6);
        dInicio.value = formatear(lunes);
        dFin.value = formatear(domingo);
    } else if (tipo === 'este_mes') {
        const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
        dInicio.value = formatear(primerDia);
        dFin.value = formatear(ultimoDia);
    } else if (tipo === 'proximos_7') {
        const prox = new Date(hoy);
        prox.setDate(hoy.getDate() + 7);
        dInicio.value = formatear(hoy);
        dFin.value = formatear(prox);
    }

    if (form) form.submit();
}

function actualizarEstadoCamposFecha() {
    const tipoFecha = document.getElementById('filtro_tipo_fecha');
    const colIni = document.getElementById('col_fecha_inicio');
    const colFin = document.getElementById('col_fecha_fin');
    const colDia = document.getElementById('filtro_dia_semana');
    if (!tipoFecha) return;

    if (tipoFecha.value === 'abierta') {
        if (colIni) colIni.style.opacity = '0.4';
        if (colFin) colFin.style.opacity = '0.4';
        if (colDia) colDia.disabled = true;
        const dInicio = document.getElementById('filtro_fecha_inicio');
        const dFin = document.getElementById('filtro_fecha_fin');
        if (dInicio) dInicio.value = '';
        if (dFin) dFin.value = '';
    } else {
        if (colIni) colIni.style.opacity = '1';
        if (colFin) colFin.style.opacity = '1';
        if (colDia) colDia.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    actualizarEstadoCamposFecha();
});

// =============== FUNCIONES DE PAGO MIXTO (PASAJES) ===============
function togglePagoMixtoVenta() {
    const sel = document.getElementById('metodo_pago_nuevo');
    const div = document.getElementById('div_pago_mixto_nuevo');
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoVenta('init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoVenta(origen) {
    const sel = document.getElementById('metodo_pago_nuevo');
    if (!sel || sel.value !== 'MIXTO') return;
    const totalEl = document.getElementById('monto_abonar');
    const efEl = document.getElementById('monto_efectivo_nuevo');
    const trEl = document.getElementById('monto_transferencia_nuevo');
    const badge = document.getElementById('badge_check_mixto_nuevo');
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

function validarVentaMixto(form) {
    const sel = document.getElementById('metodo_pago_nuevo');
    if (sel && sel.value === 'MIXTO') {
        const total = parseFloat(document.getElementById('monto_abonar').value) || 0;
        const ef = parseFloat(document.getElementById('monto_efectivo_nuevo').value) || 0;
        const tr = parseFloat(document.getElementById('monto_transferencia_nuevo').value) || 0;
        if (total <= 0) {
            alert('El monto a abonar debe ser mayor a 0.');
            return false;
        }
        if (Math.abs((ef + tr) - total) > 0.02) {
            alert('El pago mixto no cuadra: Efectivo ($' + ef.toFixed(2) + ') + Transferencia ($' + tr.toFixed(2) + ') = $' + (ef + tr).toFixed(2) + ', pero el total a cobrar es $' + total.toFixed(2) + '. Corrija los valores antes de continuar.');
            return false;
        }
    }
    return true;
}

// Abonos
function togglePagoMixtoAbono(id) {
    const sel = document.getElementById('metodo_pago_abono_' + id);
    const div = document.getElementById('div_mixto_abono_' + id);
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoAbono(id, 'init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoAbono(id, origen) {
    const sel = document.getElementById('metodo_pago_abono_' + id);
    if (!sel || sel.value !== 'MIXTO') return;
    const totalEl = document.getElementById('monto_abono_' + id);
    const efEl = document.getElementById('monto_efectivo_abono_' + id);
    const trEl = document.getElementById('monto_transferencia_abono_' + id);
    const badge = document.getElementById('badge_mixto_abono_' + id);
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
            badge.textContent = '✔ Cuadrado';
        } else {
            badge.className = 'badge bg-danger';
            badge.textContent = '⚠️ Descuadre';
        }
    }
}

function validarAbonoMixto(form, id) {
    const sel = document.getElementById('metodo_pago_abono_' + id);
    if (sel && sel.value === 'MIXTO') {
        const total = parseFloat(document.getElementById('monto_abono_' + id).value) || 0;
        const ef = parseFloat(document.getElementById('monto_efectivo_abono_' + id).value) || 0;
        const tr = parseFloat(document.getElementById('monto_transferencia_abono_' + id).value) || 0;
        if (Math.abs((ef + tr) - total) > 0.02) {
            alert('El pago mixto del abono no coincide con el total ($' + total.toFixed(2) + '). Verifique los montos.');
            return false;
        }
    }
    return true;
}

// Equipaje / Sobrepeso
function togglePagoMixtoEquipaje(id) {
    const sel = document.getElementById('metodo_pago_eq_' + id);
    const div = document.getElementById('div_mixto_eq_' + id);
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoEquipaje(id, 'init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoEquipaje(id, origen) {
    const sel = document.getElementById('metodo_pago_eq_' + id);
    if (!sel || sel.value !== 'MIXTO') return;
    const totalEl = document.getElementById('cx_' + id);
    const efEl = document.getElementById('monto_efectivo_eq_' + id);
    const trEl = document.getElementById('monto_transferencia_eq_' + id);
    const badge = document.getElementById('badge_mixto_eq_' + id);
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
            badge.textContent = '✔ Cuadrado';
        } else {
            badge.className = 'badge bg-danger';
            badge.textContent = '⚠️ Descuadre';
        }
    }
}

function validarEquipajeMixto(form, id) {
    const sel = document.getElementById('metodo_pago_eq_' + id);
    if (sel && sel.value === 'MIXTO') {
        const total = parseFloat(document.getElementById('cx_' + id).value) || 0;
        const ef = parseFloat(document.getElementById('monto_efectivo_eq_' + id).value) || 0;
        const tr = parseFloat(document.getElementById('monto_transferencia_eq_' + id).value) || 0;
        if (total > 0 && Math.abs((ef + tr) - total) > 0.02) {
            alert('El cobro de sobrepeso mixto no coincide con el total ($' + total.toFixed(2) + '). Verifique los montos.');
            return false;
        }
    }
    return true;
}