function generarOpcionesCiudades(valorSeleccionado = '', placeholder = '-- Seleccionar Ciudad --') {
    let html = `<option value="">${placeholder}</option>`;
    let encontrado = false;
    const valNorm = (valorSeleccionado || '').trim().toLowerCase();

    if (typeof CIUDADES_CATALOGO !== 'undefined' && Array.isArray(CIUDADES_CATALOGO)) {
        const porPais = {};
        CIUDADES_CATALOGO.forEach(cd => {
            const pais = (cd.PAIS || 'OTROS').toUpperCase().trim();
            if (!porPais[pais]) porPais[pais] = [];
            porPais[pais].push(cd.NOMBRE);
        });

        const banderas = { 'VENEZUELA': '🇻🇪', 'COLOMBIA': '🇨🇴', 'ECUADOR': '🇪🇨', 'PERU': '🇵🇪' };
        Object.keys(porPais).sort().forEach(pais => {
            const flag = banderas[pais] || '🌎';
            html += `<optgroup label="${flag} ${pais}">`;
            const ciudades = porPais[pais].slice().sort((a, b) => a.localeCompare(b));
            ciudades.forEach(nomCiu => {
                const cNorm = nomCiu.trim().toLowerCase();
                const isSel = valNorm && cNorm === valNorm;
                if (isSel) encontrado = true;
                html += `<option value="${nomCiu}" ${isSel ? 'selected' : ''}>${nomCiu}</option>`;
            });
            html += `</optgroup>`;
        });
    }

    if (valorSeleccionado && !encontrado) {
        html += `<optgroup label="⚠️ Ciudad Actual Configurada">`;
        html += `<option value="${valorSeleccionado}" selected>${valorSeleccionado} (Registrada)</option>`;
        html += `</optgroup>`;
    }

    return html;
}

// Alias de retrocompatibilidad
function generarOpcionesParadasCorredor(idIgnorado, valorSeleccionado = '', placeholder = '-- Seleccionar Ciudad --') {
    return generarOpcionesCiudades(valorSeleccionado, placeholder);
}

function alCambiarCorredorEnRuta(rutaId) {
    // Obsoleto tras purga de corredores - preservado para evitar errores si está en caché
}

function alCambiarCabecerasRuta(rutaId) {
    const selOrigen = document.getElementById(rutaId ? 'edit_origen_' + rutaId : 'nuevo_origen');
    const selDestino = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');
    if (!selOrigen || !selDestino) return;

    const orig = selOrigen.value;
    const dest = selDestino.value;

    // Auto-sincronizar origen del primer tramo y destino del último tramo
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (container) {
        const cards = container.querySelectorAll('.tramo-card');
        if (cards.length === 1) {
            const card1 = cards[0];
            const selOrigTramo = card1.querySelector('.tramo-origen-select');
            const selDestTramo = card1.querySelector('.tramo-destino-select');
            if (selOrigTramo && orig) {
                selOrigTramo.value = orig;
            }
            if (selDestTramo && dest) {
                selDestTramo.value = dest;
            }
            actualizarTitulosTramos(container);

            // Auto-completar costo y PVP del tramo si existe en catálogo de tramos
            if (orig && dest && typeof TRAMOS_CATALOGO !== 'undefined' && Array.isArray(TRAMOS_CATALOGO)) {
                const match = TRAMOS_CATALOGO.find(t => 
                    (t.ORIGEN_TRAMO || '').toLowerCase().trim() === orig.toLowerCase().trim() &&
                    (t.DESTINO_TRAMO || '').toLowerCase().trim() === dest.toLowerCase().trim()
                );
                if (match) {
                    const inpCosto = card1.querySelector('.tramo-costo');
                    const inpPvp = card1.querySelector('.tramo-pvp');
                    const selOp = card1.querySelector('select[name="tramo_id_operador[]"]');
                    const hidOp = card1.querySelector('input[name="tramo_operador[]"]');
                    if (selOp && match.ID_OPERADOR) {
                        selOp.value = match.ID_OPERADOR;
                        if (hidOp) hidOp.value = match.OPERADOR_NOMBRE || selOp.options[selOp.selectedIndex]?.text || '';
                    }
                    if (inpCosto && (!parseFloat(inpCosto.value) || parseFloat(inpCosto.value) === 0)) {
                        inpCosto.value = parseFloat(match.COSTO_OPERADOR || 0).toFixed(2);
                    }
                    if (inpPvp && (!parseFloat(inpPvp.value) || parseFloat(inpPvp.value) === 0)) {
                        inpPvp.value = parseFloat(match.PVP_SUGERIDO || 0).toFixed(2);
                    }
                    recalcularFinanzasTramo(inpPvp || inpCosto, rutaId);
                }
            }
        } else if (cards.length > 1) {
            const firstCard = cards[0];
            const lastCard = cards[cards.length - 1];
            const selOrigFirst = firstCard.querySelector('.tramo-origen-select');
            const selDestLast = lastCard.querySelector('.tramo-destino-select');
            if (selOrigFirst && orig) {
                selOrigFirst.value = orig;
            }
            if (selDestLast && dest) {
                selDestLast.value = dest;
            }
            actualizarTitulosTramos(container);
        }
    }
}

function toggleTramos(valor, divId) {
    let div = document.getElementById(divId);
    if (!div) return;
    const rutaId = divId.replace('tramos_edit_', '').replace('tramos_nuevos', '');
    const divDirecto = document.getElementById(rutaId ? 'opciones_directo_edit_' + rutaId : 'opciones_directo_nuevo');
    const badgeAuto = document.getElementById(rutaId ? 'badge_util_auto_' + rutaId : 'badge_util_auto_nuevo');
    const hintUtil = document.getElementById(rutaId ? 'hint_util_pasaje_' + rutaId : 'hint_util_pasaje_nuevo');
    const listaContainerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const listaContainer = document.getElementById(listaContainerId);

    // UNIFICADO: Los tramos SIEMPRE están visibles y activos para rutas directas (1 tramo) y con conexiones (2+ tramos)
    div.classList.remove('d-none');
    div.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);

    if (divDirecto) {
        divDirecto.style.display = 'none';
        divDirecto.querySelectorAll('input, select').forEach(el => el.disabled = false);
    }

    if (badgeAuto) badgeAuto.classList.remove('d-none');

    if (valor === 'CONEXION') {
        if (hintUtil) hintUtil.textContent = '⚡ Calculado automáticamente de la suma de tramos.';
        if (listaContainer && listaContainer.querySelectorAll('.tramo-card').length === 0) {
            addTramo(listaContainerId, rutaId);
        }
    } else {
        if (hintUtil) hintUtil.textContent = '⚡ Costo de liquidación al operador del tramo.';
        if (listaContainer && listaContainer.querySelectorAll('.tramo-card').length === 0) {
            addTramo(listaContainerId, rutaId);
        }
    }
    recalcularTotalesRuta(rutaId);
}

function actualizarTitulosTramos(container) {
    if (!container) return;
    const cards = container.querySelectorAll('.tramo-card');
    cards.forEach((card, index) => {
        const titleSpan = card.querySelector('.tramo-title');
        const origInput = card.querySelector('select[name="tramo_origen[]"], input[name="tramo_origen[]"]');
        const destInput = card.querySelector('select[name="tramo_destino[]"], input[name="tramo_destino[]"]');
        const orig = origInput && origInput.value.trim() ? origInput.value.trim() : 'Origen';
        const dest = destInput && destInput.value.trim() ? destInput.value.trim() : 'Destino';
        if (titleSpan) {
            titleSpan.innerHTML = `📍 Tramo ${index + 1}: <span class="text-dark">${orig} ➔ ${dest}</span>`;
        }
    });
}

function addTramo(containerId, rutaId = '') {
    let container = document.getElementById(containerId);
    if (!container) return;

    // Sugerir continuidad con el tramo anterior si existe
    const existingCards = container.querySelectorAll('.tramo-card');
    let subeSugerido = '';
    let bajaSugerido = '';
    if (existingCards.length > 0) {
        const lastCard = existingCards[existingCards.length - 1];
        const lastDest = lastCard.querySelector('.tramo-destino-select')?.value || '';
        subeSugerido = lastDest;
        const selDestRuta = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');
        if (selDestRuta && selDestRuta.value && selDestRuta.value !== subeSugerido) {
            bajaSugerido = selDestRuta.value;
        }
    } else {
        const selOrigRuta = document.getElementById(rutaId ? 'edit_origen_' + rutaId : 'nuevo_origen');
        const selDestRuta = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');
        subeSugerido = selOrigRuta ? selOrigRuta.value : '';
        bajaSugerido = selDestRuta ? selDestRuta.value : '';
    }

    const opcionesOrigen = generarOpcionesCiudades(subeSugerido, '-- Sube en --');
    const opcionesDestino = generarOpcionesCiudades(bajaSugerido, '-- Baja en --');

    let html = `
        <div class="card mb-2 border-secondary shadow-sm tramo-card" data-ruta-id="${rutaId}">
            <div class="card-header bg-white py-1 px-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-primary small tramo-title">📍 Tramo</span>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="eliminarTramoCard(this, '${rutaId}')" title="Eliminar Tramo">✕ Eliminar</button>
            </div>
            <div class="card-body p-2">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0 fw-bold">Sube en (Origen)</label>
                        <select name="tramo_origen[]" class="form-select form-select-sm tramo-origen-select fw-bold" required onchange="actualizarTitulosTramos(this.closest('.tramo-card').parentElement)">
                            ${opcionesOrigen}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0 fw-bold">Baja en (Destino)</label>
                        <select name="tramo_destino[]" class="form-select form-select-sm tramo-destino-select fw-bold" required onchange="actualizarTitulosTramos(this.closest('.tramo-card').parentElement)">
                            ${opcionesDestino}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0 fw-bold">Operador del Tramo</label>
                        <select name="tramo_id_operador[]" class="form-select form-select-sm border-primary fw-bold" onchange="const nom = this.options[this.selectedIndex].getAttribute(\'data-nombre\'); const hid = this.closest(\'.row\').querySelector(\'input[name=\\\'tramo_operador[]\\\']\'); if(hid) hid.value = nom; condicionarModalidadPorTramos(\'${rutaId}\');">
                            ${HTML_OPCIONES_OPERADORES}
                        </select>
                        <input type="hidden" name="tramo_operador[]" value="Panamericana de Transportes">
                    </div>
                </div>
                <div class="row g-2 mb-2 bg-light p-1 rounded border">
                    <div class="col-4">
                        <label class="form-label small text-dark mb-0 fw-bold">PVP Tramo Pasaje ($)</label>
                        <input type="number" step="0.01" min="0" name="tramo_pvp[]" class="form-control form-control-sm tramo-pvp fw-bold text-primary" value="0.00" oninput="recalcularFinanzasTramo(this, '${rutaId}')">
                    </div>
                    <div class="col-4">
                        <label class="form-label small text-muted mb-0 fw-bold">Costo Socio ($)</label>
                        <input type="number" step="0.01" min="0" name="tramo_costo[]" class="form-control form-control-sm tramo-costo text-danger" value="0.00" oninput="recalcularFinanzasTramo(this, '${rutaId}')">
                    </div>
                    <div class="col-4">
                        <label class="form-label small text-success mb-0 fw-bold">Utilidad Agencia ($)</label>
                        <input type="number" step="0.01" name="tramo_utilidad[]" class="form-control form-control-sm tramo-utilidad fw-bold text-success bg-white" value="0.00" readonly>
                    </div>
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label small mb-0 fw-bold text-secondary">¿Cobrar Sobrepeso en Ecuador?</label>
                        <select name="tramo_admite_sp[]" class="form-select form-select-sm tramo-admite-sp" onchange="alCambiarAdmiteSobrepeso(this, '${rutaId}')">
                            <option value="0">❌ No cobrar sobrepeso</option>
                            <option value="1">✅ Sí (Cobro activo en Taquilla)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-0">Límite Libre (Kg)</label>
                        <input type="number" step="0.1" name="tramo_limite_eq[]" class="form-control form-control-sm" value="35.0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-0">Tarifa Sobrepeso ($/Kg)</label>
                        <input type="number" step="0.01" name="tramo_precio_sp[]" class="form-control form-control-sm" value="1.00">
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    actualizarTitulosTramos(container);
    recalcularTotalesRuta(rutaId);
    condicionarModalidadPorTramos(rutaId);
}

function eliminarTramoCard(btn, rutaId) {
    const card = btn.closest('.tramo-card');
    const container = card ? card.parentElement : null;
    if (container && container.querySelectorAll('.tramo-card').length <= 1) {
        if (typeof mostrarToastFlota === 'function') {
            mostrarToastFlota('La ruta comercial debe tener al menos 1 tramo operativo.', 'warning');
        } else {
            alert('La ruta comercial debe tener al menos 1 tramo operativo.');
        }
        return;
    }
    if (card) {
        card.remove();
    }
    if (container) {
        actualizarTitulosTramos(container);
    }
    recalcularTotalesRuta(rutaId);
    condicionarModalidadPorTramos(rutaId);
}

function recalcularFinanzasTramo(input, rutaId) {
    const card = input.closest('.tramo-card');
    if (!card) return;
    const pvpInput = card.querySelector('.tramo-pvp');
    const costoInput = card.querySelector('.tramo-costo');
    const utilInput = card.querySelector('.tramo-utilidad');

    const pvp = parseFloat(pvpInput ? pvpInput.value : 0) || 0;
    const costo = parseFloat(costoInput ? costoInput.value : 0) || 0;
    const util = Math.max(0, pvp - costo);

    if (utilInput) {
        utilInput.value = util.toFixed(2);
    }
    recalcularTotalesRuta(rutaId);
}

function recalcularTotalesRuta(rutaId) {
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (!container) return;

    let totalPvp = 0;
    let totalCosto = 0;
    let totalUtil = 0;

    const cards = container.querySelectorAll('.tramo-card');
    cards.forEach(card => {
        const pvp = parseFloat(card.querySelector('.tramo-pvp')?.value || 0) || 0;
        const costo = parseFloat(card.querySelector('.tramo-costo')?.value || 0) || 0;
        const util = parseFloat(card.querySelector('.tramo-utilidad')?.value || (pvp - costo)) || 0;
        totalPvp += pvp;
        totalCosto += costo;
        totalUtil += util;
    });

    const badgePvp = document.getElementById(rutaId ? 'resumen_pvp_' + rutaId : 'resumen_pvp_nuevo');
    const badgeCosto = document.getElementById(rutaId ? 'resumen_costo_' + rutaId : 'resumen_costo_nuevo');
    const badgeUtil = document.getElementById(rutaId ? 'resumen_utilidad_' + rutaId : 'resumen_utilidad_nuevo');

    if (badgePvp) badgePvp.textContent = 'PVP Total: $' + totalPvp.toFixed(2);
    if (badgeCosto) badgeCosto.textContent = 'Costo Socios: $' + totalCosto.toFixed(2);
    if (badgeUtil) badgeUtil.textContent = 'Utilidad Neta: $' + totalUtil.toFixed(2);
}

function copiarTotalATarifa(rutaId) {
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (!container) return;

    let totalPvp = 0;
    let totalUtil = 0;
    const cards = container.querySelectorAll('.tramo-card');
    cards.forEach(card => {
        const pvp = parseFloat(card.querySelector('.tramo-pvp')?.value || 0) || 0;
        const util = parseFloat(card.querySelector('.tramo-utilidad')?.value || 0) || 0;
        totalPvp += pvp;
        totalUtil += util;
    });

    const inputTarifa = document.getElementById(rutaId ? 'precio_pasaje_edit_' + rutaId : 'precio_pasaje_nuevo');
    if (inputTarifa && totalPvp > 0) {
        inputTarifa.value = totalPvp.toFixed(2);
        inputTarifa.classList.add('border-success');
        setTimeout(() => inputTarifa.classList.remove('border-success'), 1200);
    }

    // Auto-sincronizar Utilidad Pasajes con la suma de tramos
    const inputUtilPasaje = document.getElementById(rutaId ? 'utilidad_pasaje_' + rutaId : 'utilidad_pasaje_nuevo');
    const selectTipoUtil = document.getElementById(rutaId ? 'tipo_utilidad_' + rutaId : 'tipo_utilidad_nuevo');
    if (inputUtilPasaje && selectTipoUtil && totalPvp > 0) {
        if (selectTipoUtil.value === 'FIJO') {
            inputUtilPasaje.value = totalUtil.toFixed(2);
        } else {
            const pct = (totalUtil / totalPvp) * 100;
            inputUtilPasaje.value = pct.toFixed(2);
        }
        inputUtilPasaje.classList.add('border-info');
        setTimeout(() => inputUtilPasaje.classList.remove('border-info'), 1200);
    }
}

function alCambiarAdmiteSobrepeso(select, rutaId) {
    const card = select.closest('.tramo-card');
    if (!card) return;
    if (select.value === '1') {
        card.classList.add('border-success');
        card.classList.remove('border-secondary');
    } else {
        card.classList.remove('border-success');
        card.classList.add('border-secondary');
    }
}

function actualizarSimbolosUtilidad(rutaId) {
    const sel = document.getElementById(rutaId ? 'tipo_utilidad_' + rutaId : 'tipo_utilidad_nuevo');
    if (!sel) return;
    const simbolo = (sel.value === 'FIJO') ? '$' : '%';
    const spanPasaje = document.getElementById(rutaId ? 'span-simbolo-pasaje-' + rutaId : 'span-simbolo-pasaje-nuevo');
    if (spanPasaje) {
        spanPasaje.textContent = simbolo;
    }
}

let paginaActualRutas = 1;

function aplicarFiltrosYPaginarRutas(nuevaPagina = 1) {
    if (nuevaPagina) {
        paginaActualRutas = nuevaPagina;
    }

    const inputBuscador = document.getElementById('buscadorRutas');
    const query = inputBuscador ? inputBuscador.value.toLowerCase().trim() : '';

    const selectOrigen = document.getElementById('filtroOrigenRutas');
    const fOrigen = selectOrigen ? selectOrigen.value.trim().toLowerCase() : '';

    const selectDestino = document.getElementById('filtroDestinoRutas');
    const fDestino = selectDestino ? selectDestino.value.trim().toLowerCase() : '';

    const selectOperador = document.getElementById('filtroOperadorRutas');
    const fOperador = selectOperador ? selectOperador.value.trim().toLowerCase() : '';

    const selectModalidad = document.getElementById('filtroModalidadRutas');
    const fModalidad = selectModalidad ? selectModalidad.value.trim().toLowerCase() : '';

    const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
    const fTipoViaje = selectTipoViaje ? selectTipoViaje.value.trim().toLowerCase() : '';

    const selectEstado = document.getElementById('filtroEstadoRutas');
    const fEstado = selectEstado ? selectEstado.value.trim().toLowerCase() : '';

    const selectSentido = document.getElementById('filtroSentidoRutas');
    const fSentido = selectSentido ? selectSentido.value.trim() : '';

    const selectLimite = document.getElementById('filtroLimiteRutas');
    const limiteVal = selectLimite ? selectLimite.value : '15';

    // Persistir sentido seleccionado en la URL sin recargar
    try {
        const url = new URL(window.location.href);
        if (fSentido) {
            url.searchParams.set('sentido_filtro', fSentido);
        } else {
            url.searchParams.delete('sentido_filtro');
        }
        window.history.replaceState(null, '', url.toString());
    } catch(e) {}

    const todasLasFilas = Array.from(document.querySelectorAll('#tablaRutas tbody tr.fila-ruta'));
    const filaSinResultados = document.getElementById('filaSinRutas');

    // Filtrar filas coincidentes
    const filasCoincidentes = todasLasFilas.filter(fila => {
        const cSentido = fila.getAttribute('data-sentido') || '';
        const cOrigen = (fila.getAttribute('data-origen') || '').toLowerCase();
        const cDestino = (fila.getAttribute('data-destino') || '').toLowerCase();
        const cOperador = (fila.getAttribute('data-operador') || '').toLowerCase();
        const cModalidad = (fila.getAttribute('data-modalidad') || '').toLowerCase();
        const cTipoViaje = (fila.getAttribute('data-tipo-viaje') || '').toLowerCase();
        const cEstado = (fila.getAttribute('data-estado') || '').toLowerCase();
        const searchFila = (fila.getAttribute('data-search') || '').toLowerCase();

        if (query && !searchFila.includes(query)) return false;
        if (fOrigen && cOrigen !== fOrigen) return false;
        if (fDestino && cDestino !== fDestino) return false;
        if (fOperador && cOperador !== fOperador && !cOperador.includes(fOperador)) return false;
        if (fModalidad && cModalidad !== fModalidad) return false;
        if (fTipoViaje && cTipoViaje !== fTipoViaje) return false;
        if (fEstado && cEstado !== fEstado) return false;
        if (fSentido && cSentido !== fSentido) return false;

        return true;
    });

    const totalCoincidentes = filasCoincidentes.length;

    // Actualizar badge de filtros activos
    actualizarBadgeFiltrosActivosRutas(query, fOrigen, fDestino, fOperador, fModalidad, fTipoViaje, fEstado, fSentido);

    // Calcular límites y páginas
    let rpp = (limiteVal === 'all') ? (totalCoincidentes || 1) : parseInt(limiteVal, 10);
    if (isNaN(rpp) || rpp <= 0) rpp = 15;

    const totalPaginas = Math.max(1, Math.ceil(totalCoincidentes / rpp));
    if (paginaActualRutas > totalPaginas) paginaActualRutas = totalPaginas;
    if (paginaActualRutas < 1) paginaActualRutas = 1;

    const inicio = (paginaActualRutas - 1) * rpp;
    const fin = (limiteVal === 'all') ? totalCoincidentes : Math.min(inicio + rpp, totalCoincidentes);

    // Ocultar todas las filas primero
    todasLasFilas.forEach(f => f.style.display = 'none');

    // Mostrar sólo las del rango de la página actual
    if (totalCoincidentes > 0) {
        for (let i = inicio; i < fin; i++) {
            if (filasCoincidentes[i]) {
                filasCoincidentes[i].style.display = '';
            }
        }
        if (filaSinResultados) filaSinResultados.style.display = 'none';
    } else {
        if (filaSinResultados) filaSinResultados.style.display = '';
    }

    // Actualizar indicador de rutas mostradas
    const infoSpan = document.getElementById('infoPaginacionRutas');
    if (infoSpan) {
        if (totalCoincidentes === 0) {
            infoSpan.innerHTML = '<span class="text-danger fw-bold">Mostrando 0 rutas comerciales</span>';
        } else {
            infoSpan.innerHTML = `Mostrando <strong>${inicio + 1}</strong> a <strong>${fin}</strong> de <strong>${totalCoincidentes}</strong> rutas <span class="text-secondary">(Página ${paginaActualRutas} de ${totalPaginas})</span>`;
        }
    }

    // Actualizar badge de total
    const badgeTotal = document.getElementById('badgeTotalRutas');
    if (badgeTotal) {
        if (fSentido || query || fOrigen || fDestino || fOperador || fModalidad || fTipoViaje || fEstado) {
            badgeTotal.textContent = `${totalCoincidentes} de ${todasLasFilas.length} rutas`;
        } else {
            badgeTotal.textContent = `${todasLasFilas.length} rutas`;
        }
    }

    // Renderizar botones de paginación
    renderizarBotonesPaginacionRutas(totalPaginas, totalCoincidentes);
}

function actualizarBadgeFiltrosActivosRutas(query, ori, des, op, mod, viaje, est, sen) {
    const badge = document.getElementById('badgeFiltrosActivosRutas');
    if (!badge) return;
    
    let activos = [];
    if (query) activos.push(`"${query}"`);
    if (ori) activos.push(`Origen: ${ori.toUpperCase()}`);
    if (des) activos.push(`Destino: ${des.toUpperCase()}`);
    if (op) activos.push(`Operador: ${op.toUpperCase()}`);
    if (mod) activos.push(`Modalidad: ${mod.toUpperCase()}`);
    if (viaje) activos.push(`Viaje: ${viaje.toUpperCase()}`);
    if (est) activos.push(`Estado: ${est.toUpperCase()}`);
    if (sen) activos.push(`Sentido: ${sen}`);

    if (activos.length === 0) {
        badge.className = 'badge bg-secondary';
        badge.textContent = 'Filtros: Todos';
    } else {
        badge.className = 'badge bg-primary';
        badge.textContent = `Filtros (${activos.length}): ` + activos.join(' | ');
    }
}

function limpiarTodosFiltrosRutas() {
    const inputBuscador = document.getElementById('buscadorRutas');
    const selectOrigen = document.getElementById('filtroOrigenRutas');
    const selectDestino = document.getElementById('filtroDestinoRutas');
    const selectOperador = document.getElementById('filtroOperadorRutas');
    const selectModalidad = document.getElementById('filtroModalidadRutas');
    const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
    const selectEstado = document.getElementById('filtroEstadoRutas');
    const selectSentido = document.getElementById('filtroSentidoRutas');
    
    if (inputBuscador) inputBuscador.value = '';
    if (selectOrigen) selectOrigen.value = '';
    if (selectDestino) selectDestino.value = '';
    if (selectOperador) selectOperador.value = '';
    if (selectModalidad) selectModalidad.value = '';
    if (selectTipoViaje) selectTipoViaje.value = '';
    if (selectEstado) selectEstado.value = '';
    if (selectSentido) selectSentido.value = '';

    // Desactivar tarjetas KPI
    document.querySelectorAll('.tarjeta-filtro-rapido-ruta').forEach(c => c.classList.remove('active'));
    const kpiTodos = document.querySelector('.tarjeta-filtro-rapido-ruta[data-filtro-kpi="todos"]');
    if (kpiTodos) kpiTodos.classList.add('active');

    aplicarFiltrosYPaginarRutas(1);
}

function renderizarBotonesPaginacionRutas(totalPaginas, totalCoincidentes) {
    const lista = document.getElementById('listaPaginacionRutas');
    if (!lista) return;

    lista.innerHTML = '';
    if (totalPaginas <= 1) {
        return;
    }

    // Botón Anterior
    const liPrev = document.createElement('li');
    liPrev.className = `page-item ${paginaActualRutas <= 1 ? 'disabled' : ''}`;
    liPrev.innerHTML = `<button class="page-link" type="button" onclick="cambiarPaginaRutas(${paginaActualRutas - 1})">‹ Anterior</button>`;
    lista.appendChild(liPrev);

    // Páginas a mostrar (ventana deslizante compacta)
    let paginas = [];
    if (totalPaginas <= 7) {
        for (let i = 1; i <= totalPaginas; i++) paginas.push(i);
    } else {
        paginas.push(1);
        let start = Math.max(2, paginaActualRutas - 1);
        let end = Math.min(totalPaginas - 1, paginaActualRutas + 1);

        if (start > 2) paginas.push('...');
        for (let i = start; i <= end; i++) paginas.push(i);
        if (end < totalPaginas - 1) paginas.push('...');
        paginas.push(totalPaginas);
    }

    paginas.forEach(p => {
        const li = document.createElement('li');
        if (p === '...') {
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link border-0 text-muted">...</span>';
        } else {
            li.className = `page-item ${p === paginaActualRutas ? 'active fw-bold' : ''}`;
            li.innerHTML = `<button class="page-link" type="button" onclick="cambiarPaginaRutas(${p})">${p}</button>`;
        }
        lista.appendChild(li);
    });

    // Botón Siguiente
    const liNext = document.createElement('li');
    liNext.className = `page-item ${paginaActualRutas >= totalPaginas ? 'disabled' : ''}`;
    liNext.innerHTML = `<button class="page-link" type="button" onclick="cambiarPaginaRutas(${paginaActualRutas + 1})">Siguiente ›</button>`;
    lista.appendChild(liNext);
}

function cambiarPaginaRutas(nuevaPagina) {
    aplicarFiltrosYPaginarRutas(nuevaPagina);
    const cardRutas = document.getElementById('tablaRutas');
    if (cardRutas) {
        cardRutas.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function limpiarBuscadorRutas() {
    const input = document.getElementById('buscadorRutas');
    if (input) {
        input.value = '';
        aplicarFiltrosYPaginarRutas(1);
        input.focus();
    }
}

function filtrarTablaRutas() {
    aplicarFiltrosYPaginarRutas(1);
}

// Sincronización de pestañas con URL y soporte de navegación directa
document.addEventListener("DOMContentLoaded", function() {
    // Deshabilitar campos de tramos en rutas directas para evitar bloqueos por validación HTML5
    document.querySelectorAll('[id^="tramos_edit_"], #tramos_nuevos').forEach(div => {
        if (div.classList.contains('d-none')) {
            div.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        }
    });

    // Escuchar tarjetas KPI de rutas
    const tarjetasKpiRutas = document.querySelectorAll('.tarjeta-filtro-rapido-ruta');
    tarjetasKpiRutas.forEach(tarjeta => {
        tarjeta.addEventListener('click', function() {
            tarjetasKpiRutas.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const tipoKpi = this.getAttribute('data-filtro-kpi');
            const selectModalidad = document.getElementById('filtroModalidadRutas');
            const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
            const selectEstado = document.getElementById('filtroEstadoRutas');

            // Limpiar selectores antes de aplicar KPI
            if (selectModalidad) selectModalidad.value = '';
            if (selectTipoViaje) selectTipoViaje.value = '';
            if (selectEstado) selectEstado.value = '';

            if (tipoKpi === 'directo' && selectTipoViaje) {
                selectTipoViaje.value = 'directo';
            } else if (tipoKpi === 'conexion' && selectTipoViaje) {
                selectTipoViaje.value = 'conexion';
            } else if (tipoKpi === 'activas' && selectEstado) {
                selectEstado.value = 'activo';
            }

            aplicarFiltrosYPaginarRutas(1);
        });
    });

    // Escuchadores de eventos para todos los filtros de rutas
    const inputBuscador = document.getElementById('buscadorRutas');
    if (inputBuscador) {
        inputBuscador.addEventListener('input', () => aplicarFiltrosYPaginarRutas(1));
    }

    ['filtroOrigenRutas', 'filtroDestinoRutas', 'filtroOperadorRutas', 'filtroModalidadRutas', 'filtroTipoViajeRutas', 'filtroEstadoRutas', 'filtroSentidoRutas', 'filtroLimiteRutas'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => aplicarFiltrosYPaginarRutas(1));
    });

    const btnResetRutas = document.getElementById('btnLimpiarTodosFiltrosRutas');
    if (btnResetRutas) {
        btnResetRutas.addEventListener('click', limpiarTodosFiltrosRutas);
    }

    const tabButtons = document.querySelectorAll('#flotaTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('data-bs-target');
            if (targetId) {
                const tabKey = targetId.replace('#tab-', '');
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabKey);
                window.history.replaceState(null, '', url.toString());
            }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    let activeTab = urlParams.get('tab');
    if (activeTab === 'operadores') activeTab = 'buses';
    if (activeTab) {
        const triggerEl = document.querySelector(`#flotaTabs button[data-bs-target="#tab-${activeTab}"]`);
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
    }

    // Inicializar filtro de sentido y paginación en rutas
    const filtroSentidoUrl = urlParams.get('sentido_filtro') || '';
    const filtroSelect = document.getElementById('filtroSentidoRutas');
    if (filtroSelect && filtroSentidoUrl) {
        filtroSelect.value = filtroSentidoUrl;
    }
    aplicarFiltrosYPaginarRutas(1);
});

// ============================================================
// CONTROL Y CONDICIONAMIENTO DE MODALIDADES
// ============================================================

function alCambiarModalidadSelect(selectEl, rutaId) {
    // Ya no se requiere campo de texto libre: modalidades 100% estandarizadas en 4 opciones.
}

function condicionarModalidadPorOperador(idOperador, rutaId) {
    const sufijo = rutaId ? '_' + rutaId : '_';
    const selectMod = document.getElementById('modalidad_select_edit' + sufijo);
    if (!selectMod) return;

    const esPanamericana = String(idOperador) === '2';
    if (esPanamericana) {
        selectMod.value = 'PANAMERICANA';
    } else {
        selectMod.value = 'EXPRESOS_SURAMERICA';
    }
}

function condicionarModalidadPorTramos(rutaId) {
    const sufijo = rutaId ? '_' + rutaId : '_';
    const selectMod = document.getElementById('modalidad_select_edit' + sufijo);
    if (!selectMod) return;

    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (!container) return;

    const opSelects = container.querySelectorAll('select[name="tramo_id_operador[]"]');
    let tienePanamericana = false;
    let tieneSuramerica = false;

    opSelects.forEach(sel => {
        const val = String(sel.value);
        const optText = (sel.options[sel.selectedIndex]?.text || '').toLowerCase();
        if (val === '2' || optText.includes('panamericana')) {
            tienePanamericana = true;
        } else if (val === '1' || optText.includes('suramérica') || optText.includes('suramerica')) {
            tieneSuramerica = true;
        }
    });

    if (tienePanamericana && tieneSuramerica) {
        selectMod.value = 'PANA_EXPRESOS';
    } else if (tienePanamericana) {
        selectMod.value = 'PANAMERICANA';
    } else if (tieneSuramerica) {
        selectMod.value = 'EXPRESOS_SURAMERICA';
    } else {
        selectMod.value = 'CONEXIONES';
    }
}

function condicionarModalidadAutomatica(rutaId) {
    const sufijo = rutaId ? '_' + rutaId : '_';
    const tipoViajeSelect = document.getElementById(rutaId ? 'edit_tipo_viaje_' + rutaId : 'nuevo_tipo_viaje');
    const tipoViaje = tipoViajeSelect ? tipoViajeSelect.value : 'DIRECTO';

    if (tipoViaje === 'DIRECTO') {
        const idOpSelect = document.getElementById(rutaId ? 'edit_id_operador_' + rutaId : 'nuevo_id_operador');
        const idOp = idOpSelect ? idOpSelect.value : '1';
        condicionarModalidadPorOperador(idOp, rutaId);
    } else {
        condicionarModalidadPorTramos(rutaId);
    }
}

// ============================================================
// ACTUALIZACIÓN ASÍNCRONA (AJAX) DE RUTAS Y TARIFAS SIN RECARGA
// ============================================================

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderBadgeModalidad(modalidad, numTramos, operadorMostrar) {
    const modUpper = (modalidad || '').toUpperCase().trim();
    if (modUpper === 'PANAMERICANA') {
        return '<span class="badge bg-primary text-white"><i class="fas fa-star me-1"></i>Vía Panamericana</span>';
    }
    if (modUpper === 'CONEXION_TRAMOS' || modUpper === 'CONEXIÓN MULTITRAMO' || modUpper === 'CONEXION MULTITRAMO') {
        const txt = numTramos > 0 ? `🔀 ${numTramos} Tramos` : '🔀 Conexión Tramos';
        return `<span class="badge bg-secondary text-white">${escapeHtml(txt)}</span>`;
    }
    if (modUpper === 'ESTANDAR') {
        return '<span class="badge bg-success text-white">➡️ Directo</span>';
    }
    return `<span class="badge bg-info text-dark border">${escapeHtml(modalidad)}</span>`;
}

function mostrarToastFlota(mensaje, tipo = 'success') {
    if (window.LiveData && typeof LiveData.toast === 'function') {
        LiveData.toast(mensaje, tipo);
        return;
    }
    const toastEl = document.getElementById('toastFlotaAjax');
    if (!toastEl) return;
    
    const toastMsg = document.getElementById('toastFlotaAjaxMsg');
    const toastIcon = document.getElementById('toastFlotaAjaxIcon');
    
    if (toastMsg) toastMsg.textContent = mensaje;
    if (toastIcon) {
        toastIcon.textContent = (tipo === 'success') ? '✅' : (tipo === 'warning' ? '⚠️' : '❌');
    }
    
    toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-primary');
    if (tipo === 'success') {
        toastEl.classList.add('bg-success');
    } else if (tipo === 'warning') {
        toastEl.classList.add('bg-warning', 'text-dark');
    } else {
        toastEl.classList.add('bg-danger');
    }
    
    if (window.bootstrap && bootstrap.Toast) {
        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 });
        toast.show();
    }
}

function actualizarKpisRutasEnDOM() {
    const filas = document.querySelectorAll('#tablaRutas tbody tr.fila-ruta');
    let directas = 0;
    let conex = 0;
    let activas = 0;

    filas.forEach(f => {
        const tipo = (f.getAttribute('data-tipo-viaje') || '').toLowerCase();
        const est = (f.getAttribute('data-estado') || '').toLowerCase();
        if (tipo === 'directo') directas++;
        if (tipo === 'conexion') conex++;
        if (est === 'activo') activas++;
    });

    const spanTotal = document.getElementById('kpiTotalRutasCat');
    const spanDir = document.getElementById('kpiRutasDirectas');
    const spanCon = document.getElementById('kpiRutasConexion');
    const spanAct = document.getElementById('kpiRutasActivas');

    if (spanTotal) spanTotal.textContent = filas.length;
    if (spanDir) spanDir.textContent = directas;
    if (spanCon) spanCon.textContent = conex;
    if (spanAct) spanAct.textContent = activas;
}

function actualizarFilaRutaEnDOM(ruta) {
    if (!ruta || !ruta.ID_RUTA) return;
    const idRuta = ruta.ID_RUTA;
    const fila = document.getElementById('fila_ruta_' + idRuta);
    if (!fila) return;

    const origen = ruta.RUT_ORIGEN || '';
    const destino = ruta.RUT_DESTINO || '';
    const sentido = ruta.SENTIDO || 'NORTE';
    const nombreSentido = ruta.NOMBRE_SENTIDO || (sentido === 'SUR' ? 'Sentido Sur (Venezuela ➔ Ecuador)' : (sentido === 'NACIONAL' ? 'Trayecto Nacional' : 'Sentido Norte (Ecuador ➔ Venezuela)'));
    const iconoSentido = ruta.ICONO_SENTIDO || (sentido === 'SUR' ? '🇻🇪➔🇪🇨' : (sentido === 'NACIONAL' ? '📍' : '🇪🇨➔🇻🇪'));
    const badgeSentido = ruta.BADGE_SENTIDO || (sentido === 'SUR' ? 'bg-info text-dark' : (sentido === 'NACIONAL' ? 'bg-secondary text-white' : 'bg-primary text-white'));
    const tagSentido = ruta.TAG_SENTIDO || (sentido === 'SUR' ? 'Hacia Ecuador' : (sentido === 'NACIONAL' ? 'Nacional' : 'Hacia Venezuela'));
    const mod = ruta.MODALIDAD || 'ESTANDAR';
    const tipoViaje = (ruta.TIPO_VIAJE || 'DIRECTO').toUpperCase();
    const idOperador = ruta.ID_OPERADOR || '';
    const operadorDirecto = ruta.OPERADOR_NOMBRE ? ruta.OPERADOR_NOMBRE : 'Suramérica 2030 (Flota Propia)';
    const tramos = Array.isArray(ruta.TRAMOS_DETALLE) ? ruta.TRAMOS_DETALLE : [];
    const numTramos = tramos.length;
    const admiteSp = parseInt(ruta.ADMITE_SOBREPESO !== undefined ? ruta.ADMITE_SOBREPESO : 1, 10);
    const precioPasaje = parseFloat(ruta.PRECIO_PASAJE || 0);
    const precioEncomienda = parseFloat(ruta.PRECIO_ENCOMIENDA_KG || 0);
    const tipoUtilidad = (ruta.TIPO_UTILIDAD || 'PORCENTAJE').toUpperCase();
    const utilidadPasaje = parseFloat(ruta.UTILIDAD_PASAJE || 0);
    const estado = (ruta.ESTADO || 'ACTIVO').toUpperCase();
    const esActivo = (estado === 'ACTIVO');
    const diasSalida = ruta.DIAS_SALIDA || '';
    const horaSalida = ruta.RUT_SAHORA || '00:00';

    // Determinar operador mostrado
    let opsTramos = [];
    const esConexion = (tipoViaje === 'CONEXION');
    if (esConexion && tramos.length > 0) {
        tramos.forEach(tr => {
            const opItem = (tr.OPERADOR_TRAMO_NOMBRE || tr.operador || tr.OPERADOR || '').trim();
            if (opItem && !opsTramos.includes(opItem)) {
                opsTramos.push(opItem);
            }
        });
    }

    let operadorMostrar = operadorDirecto;
    let tituloOperador = "Operador Principal: " + operadorMostrar;
    if (esConexion && opsTramos.length > 0) {
        if (opsTramos.length === 1) {
            operadorMostrar = opsTramos[0];
            tituloOperador = "Operador del Servicio: " + operadorMostrar;
        } else {
            let todosLocales = true;
            opsTramos.forEach(opT => {
                const low = opT.toLowerCase();
                if (!low.includes('local') && !low.includes('empresa')) {
                    todosLocales = false;
                }
            });
            if (todosLocales) {
                operadorMostrar = "Empresas Locales";
            } else {
                operadorMostrar = opsTramos.join(' / ');
            }
            tituloOperador = "Operadores en Conexión: " + opsTramos.join(' + ');
        }
    }

    // Actualizar atributos de datos de la fila para filtros y búsqueda
    fila.setAttribute('data-sentido', sentido);
    fila.setAttribute('data-origen', origen.toLowerCase().trim());
    fila.setAttribute('data-destino', destino.toLowerCase().trim());
    fila.setAttribute('data-operador', operadorMostrar.toLowerCase().trim());
    fila.setAttribute('data-id-operador', idOperador);
    fila.setAttribute('data-modalidad', mod.toLowerCase().trim());
    fila.setAttribute('data-tipo-viaje', tipoViaje.toLowerCase().trim());
    fila.setAttribute('data-estado', estado.toLowerCase().trim());
    fila.setAttribute('data-search', `${origen} ${destino} ${nombreSentido} #${idRuta} ${operadorMostrar} ${opsTramos.join(' ')} ${mod}`.toLowerCase());

    // Actualizar clase de fila activa o inactiva
    if (esActivo) {
        fila.classList.remove('table-secondary', 'opacity-75');
    } else {
        fila.classList.add('table-secondary', 'opacity-75');
    }

    // Columna 1: Trayecto y Sentido
    const colTrayecto = fila.querySelector('.col-ruta-trayecto');
    if (colTrayecto) {
        let htmlTrayecto = `<div class="fw-bold text-dark fs-6">${escapeHtml(origen)} ➔ ${escapeHtml(destino)}</div>`;
        htmlTrayecto += `<div class="mt-1"><span class="badge ${badgeSentido}" style="font-size: 0.75rem; font-weight: 500;">${iconoSentido} ${escapeHtml(tagSentido)}</span></div>`;
        colTrayecto.innerHTML = htmlTrayecto;
    }

    // Columna 2: Operador y Modalidad
    const colOperador = fila.querySelector('.col-ruta-operador');
    if (colOperador) {
        colOperador.setAttribute('data-search', `${operadorMostrar} ${opsTramos.join(' ')} ${mod} ${tipoViaje}`.toLowerCase());
        let htmlOp = `
            <div class="d-flex flex-column gap-1">
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    ${renderBadgeModalidad(mod, numTramos, operadorMostrar)}
                    <span class="badge bg-light text-dark border text-truncate" style="max-width: 175px;" title="${escapeHtml(tituloOperador)}">🏢 ${escapeHtml(operadorMostrar)}</span>
                </div>
        `;
        if (!esConexion) {
            if (admiteSp) {
                htmlOp += `<span class="badge bg-success-subtle text-success border border-success-subtle" title="Sobrepeso amparado y cobrado en Ecuador" style="font-size: 0.70rem; width: fit-content;">🧳 Sobrepeso: SÍ</span>`;
            } else {
                htmlOp += `<span class="badge bg-warning-subtle text-dark border border-warning-subtle" title="Sin cobro de sobrepeso en oficina" style="font-size: 0.70rem; width: fit-content;">⚠️ Sobrepeso: NO</span>`;
            }
        }
        htmlOp += `</div>`;
        if (tramos && tramos.length > 0) {
            htmlOp += `<div class="mt-1 small" style="font-size: 0.72rem; line-height: 1.15;">`;
            tramos.forEach((trItem, trIdx) => {
                const origT = trItem.ORIGEN_TRAMO || trItem.origen || '';
                const destT = trItem.DESTINO_TRAMO || trItem.destino || '';
                const opT = trItem.OPERADOR_TRAMO_NOMBRE || trItem.operador || '';
                const spT = trItem.ADMITE_SOBREPESO ? '<span title="Sobrepeso amparado en Ecuador (' + escapeHtml(opT) + ')" class="text-success fw-bold">🧳</span>' : '';
                htmlOp += `<div class="text-secondary text-truncate" style="max-width: 175px;" title="${escapeHtml(origT)} ➔ ${escapeHtml(destT)} (${escapeHtml(opT)})">
                    ${trIdx + 1}. ${escapeHtml(origT)}➔${escapeHtml(destT)} ${spT}
                </div>`;
            });
            htmlOp += `</div>`;
        }
        colOperador.innerHTML = htmlOp;
    }

    // Columna 3: Horarios y Días
    const colHorarios = fila.querySelector('.col-ruta-horarios');
    if (colHorarios) {
        const mapaDias = {'1':'Lun', '2':'Mar', '3':'Mié', '4':'Jue', '5':'Vie', '6':'Sáb', '7':'Dom'};
        const diasArr = diasSalida ? diasSalida.split(',') : [];
        const diasTexto = diasArr.map(d => mapaDias[d.trim()] || d.trim()).filter(Boolean);
        const strDias = diasTexto.length ? diasTexto.join(', ') : 'Sin definir';
        colHorarios.innerHTML = `
            <small class="d-block text-muted">${escapeHtml(strDias)}</small>
            <small class="text-primary fw-bold">⏰ ${escapeHtml(horaSalida)}</small>
        `;
    }

    // Columna Costo Operador / Liquidación
    const colCosto = fila.querySelector('.col-ruta-costo');
    const costoTotal = parseFloat(ruta.COSTO_CALCULADO !== undefined ? ruta.COSTO_CALCULADO : (ruta.COSTO_TOTAL_RUTA || 0));
    if (colCosto) {
        colCosto.innerHTML = `
            <span class="fs-6 fw-bold text-danger">$${costoTotal.toFixed(2)}</span>
            <small class="text-muted d-block" style="font-size: 0.68rem;">liquidación</small>
        `;
    }

    // Columna Tarifa Pasaje (PVP)
    const colPasaje = fila.querySelector('.col-ruta-tarifa-pasaje');
    if (colPasaje) {
        colPasaje.innerHTML = `
            <div class="celda-editable-live" data-id-ruta="${idRuta}" data-pvp="${precioPasaje.toFixed(2)}" data-costo="${costoTotal.toFixed(2)}" onclick="iniciarEdicionInlinePvp(this)" title="Clic para editar tarifa en vivo">
                <span class="fs-6 fw-bold text-primary val-editable-texto">$${precioPasaje.toFixed(2)} ✏️</span>
                <small class="text-muted d-block" style="font-size: 0.68rem;">precio viajero</small>
            </div>
        `;
    }

    // Columna Ganancia Agencia
    const colGanancia = fila.querySelector('.col-ruta-ganancia');
    const ganancia = parseFloat(ruta.UTILIDAD_CALCULADA !== undefined ? ruta.UTILIDAD_CALCULADA : (ruta.UTILIDAD_PASAJE || Math.max(0, precioPasaje - costoTotal)));
    const margenPct = parseFloat(ruta.MARGEN_PCT !== undefined ? ruta.MARGEN_PCT : (precioPasaje > 0 ? ((ganancia / precioPasaje) * 100) : 0));
    if (colGanancia) {
        colGanancia.innerHTML = `
            <span class="fs-6 fw-bold text-success">$${ganancia.toFixed(2)}</span>
            <small class="text-success d-block fw-bold" style="font-size: 0.68rem;">${margenPct.toFixed(1)}% margen</small>
        `;
    }

    // Columna Tarifa Encomienda
    const colEncomienda = fila.querySelector('.col-ruta-tarifa-encomienda');
    if (colEncomienda) {
        colEncomienda.innerHTML = `
            <div class="celda-editable-live" data-id-ruta="${idRuta}" data-encomienda="${precioEncomienda.toFixed(2)}" onclick="iniciarEdicionInlineEncomienda(this)" title="Clic para editar encomienda en vivo">
                <span class="fs-6 fw-bold text-dark val-editable-texto">$${precioEncomienda.toFixed(2)} ✏️</span>
                <small class="text-muted d-block" style="font-size: 0.68rem;">por kilo</small>
            </div>
        `;
    }

    // Columna Estado
    const colEstado = fila.querySelector('.col-ruta-estado');
    if (colEstado) {
        colEstado.innerHTML = `
            <button type="button" 
                    class="btn btn-sm p-1 border-0 bg-transparent btn-toggle-estado-ruta" 
                    data-id-ruta="${idRuta}" 
                    data-estado="${estado}"
                    onclick="conmutarEstadoRutaEnVivo(${idRuta}, this)"
                    title="Clic para ${esActivo ? 'inactivar' : 'activar'} ruta en vivo">
                <span class="badge ${esActivo ? 'bg-success shadow-sm' : 'bg-secondary opacity-75'} px-2 py-1 fs-7">
                    <span class="indicator-dot me-1">${esActivo ? '●' : '○'}</span> ${esActivo ? 'Activo' : 'Inactivo'}
                </span>
            </button>
        `;
    }

    // Sincronizar inputs en ambos modales para que no queden desactualizados
    const modalTarifa = document.getElementById('modalTarifaRapida' + idRuta);
    if (modalTarifa) {
        const inpP = modalTarifa.querySelector('input[name="precio_pasaje"]');
        const inpE = modalTarifa.querySelector('input[name="precio_encomienda"]');
        const selEst = modalTarifa.querySelector('select[name="estado"]');
        const titleH6 = modalTarifa.querySelector('.modal-title');
        if (inpP) inpP.value = precioPasaje.toFixed(2);
        if (inpE) inpE.value = precioEncomienda.toFixed(2);
        if (selEst) selEst.value = estado;
        if (titleH6) titleH6.textContent = `Tarifas: ${origen} ➔ ${destino}`;
    }

    const modalEdit = document.getElementById('modalEditarRuta' + idRuta);
    if (modalEdit) {
        const inpP = modalEdit.querySelector('input[name="precio_pasaje"]');
        const inpE = modalEdit.querySelector('input[name="precio_encomienda"]');
        const selEst = modalEdit.querySelector('select[name="estado"]');
        const titleH5 = modalEdit.querySelector('.modal-title');
        if (inpP) inpP.value = precioPasaje.toFixed(2);
        if (inpE) inpE.value = precioEncomienda.toFixed(2);
        if (selEst) selEst.value = estado;
        if (titleH5) titleH5.textContent = `⚙️ Configuración de Ruta #${idRuta}: ${origen} ➔ ${destino}`;
    }

    // Efecto de pulso / highlight verde en la fila modificada
    fila.classList.remove('fila-ruta-destacada');
    void fila.offsetWidth; // Forzar reflujo de renderizado del navegador
    fila.classList.add('fila-ruta-destacada');
    setTimeout(() => {
        fila.classList.remove('fila-ruta-destacada');
    }, 3000);
}

// Delegación global para el envío asíncrono de edición de rutas y tarifas
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (!form) return;
    if (!form.classList.contains('form-tarifa-rapida-ajax') && !form.classList.contains('form-editar-ruta-ajax')) {
        return;
    }

    // Validación de coherencia estricta entre cabeceras de ruta y tramos operativos
    if (form.classList.contains('form-editar-ruta-ajax')) {
        const selOrig = form.querySelector('select[name="origen"]') || form.querySelector('input[name="origen"]');
        const selDest = form.querySelector('select[name="destino"]') || form.querySelector('input[name="destino"]');
        if (selOrig && selDest) {
            const origRuta = (selOrig.value || '').trim().toUpperCase();
            const destRuta = (selDest.value || '').trim().toUpperCase();

            const tramoOrigs = Array.from(form.querySelectorAll('.tramo-origen-select')).map(s => (s.value || '').trim().toUpperCase());
            const tramoDests = Array.from(form.querySelectorAll('.tramo-destino-select')).map(s => (s.value || '').trim().toUpperCase());

            if (tramoOrigs.length > 0 && tramoDests.length > 0) {
                const primerOrig = tramoOrigs[0];
                const ultimoDest = tramoDests[tramoDests.length - 1];

                if (primerOrig && origRuta && primerOrig !== origRuta) {
                    e.preventDefault();
                    if (typeof mostrarToastFlota === 'function') {
                        mostrarToastFlota(`❌ Inconsistencia: El primer tramo inicia en "${primerOrig}", pero la ruta parte de "${origRuta}". Deben coincidir exactamente.`, 'danger');
                    } else {
                        alert(`❌ Inconsistencia: El primer tramo inicia en "${primerOrig}", pero la ruta parte de "${origRuta}". Deben coincidir exactamente.`);
                    }
                    return;
                }

                if (ultimoDest && destRuta && ultimoDest !== destRuta) {
                    e.preventDefault();
                    if (typeof mostrarToastFlota === 'function') {
                        mostrarToastFlota(`❌ Inconsistencia: El último tramo finaliza en "${ultimoDest}", pero la ruta llega a "${destRuta}". Deben coincidir exactamente.`, 'danger');
                    } else {
                        alert(`❌ Inconsistencia: El último tramo finaliza en "${ultimoDest}", pero la ruta llega a "${destRuta}". Deben coincidir exactamente.`);
                    }
                    return;
                }
            }
        }
    }

    e.preventDefault();

    const btnSubmit = form.querySelector('button[type="submit"]');
    const originalText = btnSubmit ? btnSubmit.innerHTML : '';
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...`;
    }

    const formData = new FormData(form);
    formData.append('ajax', '1');

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        if (!res.ok) throw new Error('Error de servidor HTTP (' + res.status + ')');
        return res.json();
    })
    .then(data => {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }

        if (data.status === 'success') {
            // Cerrar el modal correspondiente
            const modalEl = form.closest('.modal');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }

            // Actualizar fila en el DOM
            if (data.ruta) {
                actualizarFilaRutaEnDOM(data.ruta);
                actualizarKpisRutasEnDOM();
                // Re-filtrar sin perder lo que el usuario escribió o seleccionó
                if (typeof aplicarFiltrosYPaginarRutas === 'function') {
                    aplicarFiltrosYPaginarRutas(paginaActualRutas);
                }
            }

            mostrarToastFlota(data.mensaje || 'Cambios guardados con éxito.', 'success');
        } else {
            mostrarToastFlota(data.mensaje || 'Error al guardar cambios.', 'danger');
        }
    })
    .catch(err => {
        console.error('Error AJAX en guardado de ruta:', err);
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }
        mostrarToastFlota('Ocurrió un error al procesar el guardado: ' + err.message, 'danger');
    });
});

// ========================================================
// MANEJO DE PARÁMETROS URL PARA MODALES Y PESTAÑAS (COMPATIBILIDAD)
// ========================================================
function irATabOperadores(subtabId = 'subtab-tramos') {
    const tabFlotaBtn = document.querySelector('button[data-bs-target="#tab-flota"]');
    if (tabFlotaBtn && window.bootstrap && bootstrap.Tab) {
        bootstrap.Tab.getOrCreateInstance(tabFlotaBtn).show();
    }
    if (subtabId) {
        const subBtn = document.querySelector(`button[data-bs-target="#${subtabId}"]`);
        if (subBtn && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(subBtn).show();
        }
    }
}
window.irATabOperadores = irATabOperadores;

// ========================================================
// FUNCIONES DE CAMBIOS EN VIVO PARA RUTAS COMERCIALES
// ========================================================

/**
 * Alterna el estado ACTIVO/INACTIVO de una ruta en vivo sin recargas
 */
function conmutarEstadoRutaEnVivo(idRuta, botonEl) {
    if (!idRuta || !botonEl) return;
    const estadoActual = (botonEl.getAttribute('data-estado') || 'ACTIVO').toUpperCase();
    const nuevoEstado = (estadoActual === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

    const originalHtml = botonEl.innerHTML;
    botonEl.disabled = true;
    botonEl.innerHTML = `<span class="spinner-border spinner-border-sm text-secondary" style="width: 0.85rem; height: 0.85rem;" role="status"></span>`;

    const formData = new FormData();
    formData.append('id_ruta', idRuta);
    formData.append('estado', nuevoEstado);
    formData.append('ajax', '1');

    fetch('/suramerica2030/flota/cambiarEstadoRuta', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    })
    .then(data => {
        botonEl.disabled = false;
        if (data.status === 'success') {
            const esActivo = data.es_activo;
            const estadoFinal = data.estado;

            botonEl.setAttribute('data-estado', estadoFinal);
            botonEl.setAttribute('title', `Clic para ${esActivo ? 'inactivar' : 'activar'} ruta en vivo`);
            botonEl.innerHTML = `
                <span class="badge ${esActivo ? 'bg-success shadow-sm' : 'bg-secondary opacity-75'} px-2 py-1 fs-7">
                    <span class="indicator-dot me-1">${esActivo ? '●' : '○'}</span> ${esActivo ? 'Activo' : 'Inactivo'}
                </span>
            `;

            // Actualizar fila
            const fila = document.getElementById('fila_ruta_' + idRuta);
            if (fila) {
                fila.setAttribute('data-estado', estadoFinal.toLowerCase());
                if (esActivo) {
                    fila.classList.remove('table-secondary', 'opacity-75');
                } else {
                    fila.classList.add('table-secondary', 'opacity-75');
                }
                if (window.LiveData && typeof LiveData.highlight === 'function') {
                    LiveData.highlight(fila);
                }
            }

            // Sincronizar select en modalTarifaRapida si existe
            const modalTarifa = document.getElementById('modalTarifaRapida' + idRuta);
            if (modalTarifa) {
                const sel = modalTarifa.querySelector('select[name="estado"]');
                if (sel) sel.value = estadoFinal;
            }

            actualizarKpisRutasEnDOM();
            mostrarToastFlota(data.mensaje || `Ruta #${idRuta} actualizada`, 'success');
        } else {
            botonEl.innerHTML = originalHtml;
            mostrarToastFlota(data.mensaje || 'Error al cambiar estado.', 'danger');
        }
    })
    .catch(err => {
        botonEl.disabled = false;
        botonEl.innerHTML = originalHtml;
        mostrarToastFlota('Fallo de conexión: ' + err.message, 'danger');
    });
}
window.conmutarEstadoRutaEnVivo = conmutarEstadoRutaEnVivo;

/**
 * Inicia la edición inline del PVP (Tarifa Pasaje) en la celda
 */
function iniciarEdicionInlinePvp(celdaEl) {
    if (!celdaEl || celdaEl.classList.contains('editando')) return;
    celdaEl.classList.add('editando');

    const idRuta = celdaEl.getAttribute('data-id-ruta');
    const pvpActual = parseFloat(celdaEl.getAttribute('data-pvp') || 0);
    const costoTotal = parseFloat(celdaEl.getAttribute('data-costo') || 0);
    const contenidoOriginal = celdaEl.innerHTML;

    celdaEl.innerHTML = `
        <div class="input-group input-group-sm inline-edit-widget">
            <span class="input-group-text px-1 fw-bold text-primary">$</span>
            <input type="number" step="0.50" min="0" class="form-control form-control-sm text-end fw-bold px-1" value="${pvpActual.toFixed(2)}" style="width: 75px;">
            <button type="button" class="btn btn-sm btn-success px-2 btn-guardar-inline" title="Guardar (Enter)">✓</button>
            <button type="button" class="btn btn-sm btn-outline-secondary px-2 btn-cancelar-inline" title="Cancelar (Esc)">✕</button>
        </div>
    `;

    const input = celdaEl.querySelector('input');
    const btnGuardar = celdaEl.querySelector('.btn-guardar-inline');
    const btnCancelar = celdaEl.querySelector('.btn-cancelar-inline');

    if (input) {
        input.focus();
        input.select();
    }

    const cancelar = (e) => {
        if (e) e.stopPropagation();
        celdaEl.classList.remove('editando');
        celdaEl.innerHTML = contenidoOriginal;
    };

    const guardar = (e) => {
        if (e) e.stopPropagation();
        const nuevoValor = parseFloat(input.value);
        if (isNaN(nuevoValor) || nuevoValor <= 0) {
            mostrarToastFlota('Ingresa un valor numérico válido mayor a 0.', 'warning');
            input.focus();
            return;
        }

        if (costoTotal > 0 && nuevoValor < costoTotal) {
            mostrarToastFlota(`⚠️ La tarifa no puede ser menor a los costos de operadores ($${costoTotal.toFixed(2)}).`, 'warning');
            input.focus();
            return;
        }

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm" style="width: 0.65rem; height: 0.65rem;" role="status"></span>`;

        const formData = new FormData();
        formData.append('id_ruta', idRuta);
        formData.append('precio_pasaje', nuevoValor);
        formData.append('ajax', '1');

        fetch('/suramerica2030/flota/actualizarPrecioRapido', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            celdaEl.classList.remove('editando');
            if (data.status === 'success') {
                if (data.ruta) {
                    actualizarFilaRutaEnDOM(data.ruta);
                } else {
                    const utilidad = Math.max(0, nuevoValor - costoTotal);
                    const margen = (nuevoValor > 0) ? ((utilidad / nuevoValor) * 100).toFixed(1) : 0;
                    celdaEl.setAttribute('data-pvp', nuevoValor.toFixed(2));
                    celdaEl.innerHTML = `
                        <span class="fs-6 fw-bold text-primary val-editable-texto">$${nuevoValor.toFixed(2)} ✏️</span>
                        <small class="text-muted d-block" style="font-size: 0.68rem;">precio viajero</small>
                    `;
                    const fila = document.getElementById('fila_ruta_' + idRuta);
                    if (fila) {
                        const colGan = fila.querySelector('.col-ruta-ganancia');
                        if (colGan) {
                            colGan.innerHTML = `
                                <span class="fs-6 fw-bold text-success">$${utilidad.toFixed(2)}</span>
                                <small class="text-success d-block fw-bold" style="font-size: 0.68rem;">${margen}% margen</small>
                            `;
                        }
                    }
                }
                const fila = document.getElementById('fila_ruta_' + idRuta);
                if (fila && window.LiveData && typeof LiveData.highlight === 'function') {
                    LiveData.highlight(fila);
                }
                mostrarToastFlota(data.mensaje || 'Tarifa actualizada en vivo.', 'success');
            } else {
                celdaEl.innerHTML = contenidoOriginal;
                mostrarToastFlota(data.mensaje || 'Error al actualizar tarifa.', 'danger');
            }
        })
        .catch(err => {
            celdaEl.classList.remove('editando');
            celdaEl.innerHTML = contenidoOriginal;
            mostrarToastFlota('Error de red: ' + err.message, 'danger');
        });
    };

    btnGuardar.addEventListener('click', guardar);
    btnCancelar.addEventListener('click', cancelar);

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            guardar(e);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelar(e);
        }
    });
}
window.iniciarEdicionInlinePvp = iniciarEdicionInlinePvp;

/**
 * Inicia la edición inline de la tarifa de encomienda en la celda
 */
function iniciarEdicionInlineEncomienda(celdaEl) {
    if (!celdaEl || celdaEl.classList.contains('editando')) return;
    celdaEl.classList.add('editando');

    const idRuta = celdaEl.getAttribute('data-id-ruta');
    const precioActual = parseFloat(celdaEl.getAttribute('data-encomienda') || 0);
    const contenidoOriginal = celdaEl.innerHTML;

    celdaEl.innerHTML = `
        <div class="input-group input-group-sm inline-edit-widget">
            <span class="input-group-text px-1 fw-bold text-dark">$</span>
            <input type="number" step="0.25" min="0" class="form-control form-control-sm text-end fw-bold px-1" value="${precioActual.toFixed(2)}" style="width: 70px;">
            <button type="button" class="btn btn-sm btn-success px-2 btn-guardar-inline" title="Guardar (Enter)">✓</button>
            <button type="button" class="btn btn-sm btn-outline-secondary px-2 btn-cancelar-inline" title="Cancelar (Esc)">✕</button>
        </div>
    `;

    const input = celdaEl.querySelector('input');
    const btnGuardar = celdaEl.querySelector('.btn-guardar-inline');
    const btnCancelar = celdaEl.querySelector('.btn-cancelar-inline');

    if (input) {
        input.focus();
        input.select();
    }

    const cancelar = (e) => {
        if (e) e.stopPropagation();
        celdaEl.classList.remove('editando');
        celdaEl.innerHTML = contenidoOriginal;
    };

    const guardar = (e) => {
        if (e) e.stopPropagation();
        const nuevoValor = parseFloat(input.value);
        if (isNaN(nuevoValor) || nuevoValor < 0) {
            mostrarToastFlota('Ingresa un valor numérico válido mayor o igual a 0.', 'warning');
            input.focus();
            return;
        }

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm" style="width: 0.65rem; height: 0.65rem;" role="status"></span>`;

        const formData = new FormData();
        formData.append('id_ruta', idRuta);
        formData.append('precio_encomienda', nuevoValor);
        formData.append('ajax', '1');

        fetch('/suramerica2030/flota/actualizarPrecioEncomiendaRapido', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            celdaEl.classList.remove('editando');
            if (data.status === 'success') {
                celdaEl.setAttribute('data-encomienda', nuevoValor.toFixed(2));
                celdaEl.innerHTML = `
                    <span class="fs-6 fw-bold text-dark val-editable-texto">$${nuevoValor.toFixed(2)} ✏️</span>
                    <small class="text-muted d-block" style="font-size: 0.68rem;">por kilo</small>
                `;
                const fila = document.getElementById('fila_ruta_' + idRuta);
                if (fila && window.LiveData && typeof LiveData.highlight === 'function') {
                    LiveData.highlight(fila);
                }
                mostrarToastFlota(data.mensaje || 'Tarifa de encomienda actualizada.', 'success');
            } else {
                celdaEl.innerHTML = contenidoOriginal;
                mostrarToastFlota(data.mensaje || 'Error al actualizar encomienda.', 'danger');
            }
        })
        .catch(err => {
            celdaEl.classList.remove('editando');
            celdaEl.innerHTML = contenidoOriginal;
            mostrarToastFlota('Error de red: ' + err.message, 'danger');
        });
    };

    btnGuardar.addEventListener('click', guardar);
    btnCancelar.addEventListener('click', cancelar);

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            guardar(e);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelar(e);
        }
    });
}
window.iniciarEdicionInlineEncomienda = iniciarEdicionInlineEncomienda;

/**
 * Recalcula y actualiza en tiempo real los contadores KPI de rutas
 */
function actualizarKpisRutasEnDOM() {
    const filas = document.querySelectorAll('.fila-ruta');
    let total = filas.length;
    let directas = 0;
    let conexiones = 0;
    let activas = 0;

    filas.forEach(f => {
        const tipo = (f.getAttribute('data-tipo-viaje') || '').toLowerCase();
        const est = (f.getAttribute('data-estado') || '').toLowerCase();
        if (tipo === 'conexion') conexiones++; else directas++;
        if (est === 'activo') activas++;
    });

    const elTotal = document.getElementById('kpiTotalRutasCat');
    if (elTotal) elTotal.textContent = total;
    const elDir = document.getElementById('kpiRutasDirectas');
    if (elDir) elDir.textContent = directas;
    const elCon = document.getElementById('kpiRutasConexion');
    if (elCon) elCon.textContent = conexiones;
    const elAct = document.getElementById('kpiRutasActivas');
    if (elAct) elAct.textContent = activas;
    const elBadge = document.getElementById('badgeTotalRutas');
    if (elBadge) elBadge.textContent = total + ' rutas';
}
window.actualizarKpisRutasEnDOM = actualizarKpisRutasEnDOM;

/**
 * Renderiza dinámicamente una nueva fila de ruta comercial en la tabla sin recargar la página
 */
function renderNuevaFilaRutaEnTabla(r) {
    if (!r || !r.ID_RUTA) return;
    const idRuta = r.ID_RUTA;
    if (document.getElementById('fila_ruta_' + idRuta)) {
        actualizarFilaRutaEnDOM(r);
        return;
    }

    const tbody = document.querySelector('#tablaRutas tbody');
    if (!tbody) return;

    const tr = document.createElement('tr');
    tr.className = 'fila-ruta';
    tr.id = 'fila_ruta_' + idRuta;
    tr.innerHTML = `
        <td><span class="badge bg-secondary">#${idRuta}</span></td>
        <td class="col-ruta-trayecto"></td>
        <td class="col-ruta-operador"></td>
        <td class="col-ruta-horarios"></td>
        <td class="col-ruta-costo text-end"></td>
        <td class="col-ruta-tarifa-pasaje text-end"></td>
        <td class="col-ruta-ganancia text-end"></td>
        <td class="col-ruta-tarifa-encomienda text-end"></td>
        <td class="col-ruta-estado text-center"></td>
        <td class="text-center">
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalTarifaRapida${idRuta}" title="Editar Precio Rápido">💲</button>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarRuta${idRuta}" title="Configuración Avanzada">✏️</button>
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarRuta${idRuta}" title="Eliminar Ruta">🗑️</button>
            </div>
        </td>
    `;

    // Insertar al inicio de la tabla
    const filaSin = document.getElementById('filaSinRutas');
    if (filaSin && filaSin.parentNode === tbody) {
        tbody.insertBefore(tr, filaSin);
    } else if (tbody.firstChild) {
        tbody.insertBefore(tr, tbody.firstChild);
    } else {
        tbody.appendChild(tr);
    }

    // Llenar contenido con la función maestra
    actualizarFilaRutaEnDOM(r);

    if (window.LiveData && typeof LiveData.highlight === 'function') {
        LiveData.highlight(tr);
    }
}
window.renderNuevaFilaRutaEnTabla = renderNuevaFilaRutaEnTabla;

// Interceptor global para eliminación de rutas en vivo
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (!form || !form.classList.contains('form-eliminar-ruta-ajax')) return;

    e.preventDefault();
    const btnSubmit = form.querySelector('button[type="submit"]');
    const originalText = btnSubmit ? btnSubmit.innerHTML : '';
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Procesando...`;
    }

    const formData = new FormData(form);
    formData.append('ajax', '1');

    fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }

        const modalEl = form.closest('.modal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
            if (modal) modal.hide();
        }

        if (data.status === 'success') {
            const idRuta = data.id_ruta;
            const fila = document.getElementById('fila_ruta_' + idRuta);

            if (data.accion === 'eliminada') {
                if (fila) {
                    fila.classList.add('row-fade-out');
                    setTimeout(() => {
                        fila.remove();
                        actualizarKpisRutasEnDOM();
                        if (typeof aplicarFiltrosYPaginarRutas === 'function') {
                            aplicarFiltrosYPaginarRutas(typeof paginaActualRutas !== 'undefined' ? paginaActualRutas : 1);
                        }
                    }, 400);
                }
            } else if (data.accion === 'inactivada') {
                if (fila) {
                    fila.setAttribute('data-estado', 'inactivo');
                    fila.classList.add('table-secondary', 'opacity-75');
                    const colEst = fila.querySelector('.col-ruta-estado');
                    if (colEst) {
                        colEst.innerHTML = `
                            <button type="button" 
                                    class="btn btn-sm p-1 border-0 bg-transparent btn-toggle-estado-ruta" 
                                    data-id-ruta="${idRuta}" 
                                    data-estado="INACTIVO"
                                    onclick="conmutarEstadoRutaEnVivo(${idRuta}, this)"
                                    title="Clic para activar ruta en vivo">
                                <span class="badge bg-secondary opacity-75 px-2 py-1 fs-7">
                                    <span class="indicator-dot me-1">○</span> Inactivo
                                </span>
                            </button>
                        `;
                    }
                    if (window.LiveData && typeof LiveData.highlight === 'function') {
                        LiveData.highlight(fila);
                    }
                    actualizarKpisRutasEnDOM();
                }
            }

            mostrarToastFlota(data.mensaje || 'Operación realizada con éxito.', 'success');
        } else {
            mostrarToastFlota(data.mensaje || 'Error al procesar la ruta.', 'danger');
        }
    })
    .catch(err => {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }
        mostrarToastFlota('Error al eliminar la ruta: ' + err.message, 'danger');
    });
});

// =========================================================================
// GESTIÓN DE OFERTA DE TRAMOS POR OPERADOR (FASE 3)
// =========================================================================

function abrirModalOfertasOperador(idOperador, nombreOperador) {
    const modalEl = document.getElementById('modalOfertasOperador');
    if (!modalEl) return;

    document.getElementById('modalOfertasOperadorTitulo').innerText = 'Oferta de Tramos: ' + nombreOperador;
    document.getElementById('badgeOperadorOfertaNombre').innerText = nombreOperador;
    document.getElementById('oferta_nuevo_id_operador').value = idOperador;
    
    const formAgregar = document.getElementById('formAgregarTramoOferta');
    if (formAgregar) formAgregar.style.display = 'none';

    const tbody = document.getElementById('cuerpoTablaOfertasOperador');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Cargando oferta de tramos del operador...
            </td>
        </tr>
    `;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    fetch(`/suramerica2030/flota/apiOfertasOperador?id_operador=${encodeURIComponent(idOperador)}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const total = res.total || 0;
                document.getElementById('badgeOperadorTotalOfertas').innerText = `${total} tramo(s) cubierto(s)`;
                renderizarFilasOfertasOperador(res.ofertas || []);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">❌ ${res.mensaje || 'Error al cargar ofertas.'}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">❌ Error de conexión: ${err.message}</td></tr>`;
        });
}

function renderizarFilasOfertasOperador(ofertas) {
    const tbody = document.getElementById('cuerpoTablaOfertasOperador');
    if (!tbody) return;

    if (!ofertas || ofertas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    ℹ️ Este operador aún no tiene tramos asignados en su oferta comercial.<br>
                    <small>Haz clic en "➕ Añadir Tramo a este Operador" arriba para asignarle rutas.</small>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = ofertas.map(of => {
        const dias = (of.DIAS_SALIDA || '').split(',').map(d => d.trim()).filter(Boolean);
        const diasBadges = dias.map(d => `<span class="badge bg-light text-dark border me-1" style="font-size:10px;">${d}</span>`).join('');
        const activo = parseInt(of.ACTIVO) === 1;
        const esVentaDirecta = parseInt(of.VENTA_DIRECTA || 0) === 1;
        const precioDirecto = parseFloat(of.PRECIO_TRAMO_SOLO || of.PVP_SUGERIDO || 0).toFixed(2);

        return `
            <tr id="fila_oferta_${of.ID_OFERTA}" class="${activo ? '' : 'table-secondary opacity-75'}">
                <td>
                    <div class="fw-bold text-dark">
                        ${of.ORIGEN_TRAMO} <span class="text-primary">➔</span> ${of.DESTINO_TRAMO}
                    </div>
                    <small class="text-muted" style="font-size:11px;">
                        🌎 ${of.PAIS_TRAMO || ''} &bull; ⏱️ ${of.TIEMPO_ESTIMADO_HORAS || 0}h
                    </small>
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        ${diasBadges || '<span class="text-muted small">Todos los días</span>'}
                    </div>
                </td>
                <td class="text-center font-monospace small">
                    ⏰ ${of.HORA_SALIDA ? of.HORA_SALIDA.substring(0, 5) : '08:00'}
                </td>
                <td class="text-end fw-bold text-danger">
                    $${parseFloat(of.COSTO_NETO || 0).toFixed(2)}
                </td>
                <td class="text-end fw-bold text-primary">
                    $${parseFloat(of.PVP_SUGERIDO || 0).toFixed(2)}
                </td>
                <td class="text-center">
                    ${esVentaDirecta 
                        ? `<span class="badge bg-success bg-opacity-10 text-success border border-success fw-bold" title="Se vende como ruta directa en taquilla">🎟️ Directo: $${precioDirecto}</span>` 
                        : `<span class="badge bg-light text-muted border">No directa</span>`}
                </td>
                <td class="text-center">
                    <span class="badge ${activo ? 'bg-success' : 'bg-secondary'}" id="badge_estado_oferta_${of.ID_OFERTA}">
                        ${activo ? '● Activo' : '○ Pausado'}
                    </span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm ${activo ? 'btn-outline-warning' : 'btn-outline-success'} py-0 px-2 fw-semibold" 
                            onclick="conmutarEstadoOfertaOperador(${of.ID_OFERTA}, this)" 
                            title="${activo ? 'Pausar este tramo en la oferta' : 'Reactivar este tramo'}">
                        ${activo ? '⏸️ Pausar' : '▶️ Activar'}
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function togglePrecioDirectoOferta(activado) {
    const cont = document.getElementById('contenedorPrecioDirectoOferta');
    if (!cont) return;
    cont.style.display = activado ? 'block' : 'none';
    const inp = document.getElementById('oferta_nuevo_precio_solo');
    if (activado && inp && (!inp.value || parseFloat(inp.value) === 0)) {
        const pvpSug = document.getElementById('oferta_nuevo_pvp');
        if (pvpSug && pvpSug.value) inp.value = pvpSug.value;
    }
}

function toggleFormularioNuevoTramoOferta() {
    const f = document.getElementById('formAgregarTramoOferta');
    if (!f) return;
    f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';
}

function alSeleccionarTramoEnOferta(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt) return;
    const costo = opt.getAttribute('data-costo') || '0.00';
    const pvp = opt.getAttribute('data-pvp') || '0.00';

    const inpCosto = document.getElementById('oferta_nuevo_costo');
    const inpPvp = document.getElementById('oferta_nuevo_pvp');
    if (inpCosto && (!inpCosto.value || parseFloat(inpCosto.value) === 0)) inpCosto.value = parseFloat(costo).toFixed(2);
    if (inpPvp && (!inpPvp.value || parseFloat(inpPvp.value) === 0)) inpPvp.value = parseFloat(pvp).toFixed(2);
}

function guardarNuevoTramoOferta(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const btnSubmit = form.querySelector('button[type="submit"]');
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerText = 'Guardando...';
    }

    fetch('/suramerica2030/flota/guardarOfertaOperador', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerText = 'Guardar Tramo en Oferta';
        }
        if (res.status === 'success') {
            mostrarToastFlota(res.mensaje || 'Tramo agregado a la oferta.', 'success');
            form.reset();
            toggleFormularioNuevoTramoOferta();
            const idOp = document.getElementById('oferta_nuevo_id_operador').value;
            if (idOp) {
                fetch(`/suramerica2030/flota/apiOfertasOperador?id_operador=${encodeURIComponent(idOp)}`)
                    .then(r => r.json())
                    .then(res2 => {
                        if (res2.status === 'success') {
                            document.getElementById('badgeOperadorTotalOfertas').innerText = `${res2.total || 0} tramo(s) cubierto(s)`;
                            renderizarFilasOfertasOperador(res2.ofertas || []);
                        }
                    });
            }
        } else {
            mostrarToastFlota(res.mensaje || 'Error al guardar tramo.', 'danger');
        }
    })
    .catch(err => {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerText = 'Guardar Tramo en Oferta';
        }
        mostrarToastFlota('Error: ' + err.message, 'danger');
    });
}

function conmutarEstadoOfertaOperador(idOferta, btn) {
    if (!idOferta) return;
    btn.disabled = true;

    const formData = new FormData();
    formData.append('id_oferta', idOferta);

    fetch('/suramerica2030/flota/cambiarEstadoOfertaOperador', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.status === 'success') {
            const activo = res.activo === 1;
            const fila = document.getElementById('fila_oferta_' + idOferta);
            const badge = document.getElementById('badge_estado_oferta_' + idOferta);

            if (fila) {
                if (activo) fila.classList.remove('table-secondary', 'opacity-75');
                else fila.classList.add('table-secondary', 'opacity-75');
            }

            if (badge) {
                badge.className = `badge ${activo ? 'bg-success' : 'bg-secondary'}`;
                badge.innerText = activo ? '● Activo' : '○ Pausado';
            }

            btn.className = `btn btn-sm ${activo ? 'btn-outline-warning' : 'btn-outline-success'} py-0 px-2 fw-semibold`;
            btn.innerHTML = activo ? '⏸️ Pausar' : '▶️ Activar';
            btn.title = activo ? 'Pausar este tramo en la oferta' : 'Reactivar este tramo';

            mostrarToastFlota(res.mensaje, 'success');
        } else {
            mostrarToastFlota(res.mensaje || 'No se pudo alternar el estado.', 'danger');
        }
    })
    .catch(err => {
        btn.disabled = false;
        mostrarToastFlota('Error: ' + err.message, 'danger');
    });
}

// =========================================================================
// HELPER DE PUENTES FRONTERIZOS Y CONEXIONES (FASE 3)
// =========================================================================
function normalizarNombreCiudad(ciudad) {
    if (!ciudad) return '';
    return ciudad.toString().trim().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}

function detectarPuenteFronterizoJS(ciudadA, ciudadB) {
    if (!ciudadA || !ciudadB) return null;
    const a = normalizarNombreCiudad(ciudadA);
    const b = normalizarNombreCiudad(ciudadB);

    // 1. Revisar si PUENTES_FRONTERIZOS fue inyectado desde la base de datos
    if (typeof PUENTES_FRONTERIZOS !== 'undefined' && PUENTES_FRONTERIZOS && PUENTES_FRONTERIZOS[a] && PUENTES_FRONTERIZOS[a][b]) {
        const p = PUENTES_FRONTERIZOS[a][b];
        return {
            nombre: p.NOMBRE_PUENTE || 'Paso Binacional',
            ciudadA: p.CIUDAD_A,
            ciudadB: p.CIUDAD_B,
            paisA: p.PAIS_A,
            paisB: p.PAIS_B
        };
    }

    // 2. Fallback de puentes estratégicos binacionales
    const puentesFallback = [
        { a: 'tulcan', b: 'ipiales', nombre: 'Puente Binacional Rumichaca (Ecuador ⇄ Colombia)' },
        { a: 'ipiales', b: 'tulcan', nombre: 'Puente Binacional Rumichaca (Colombia ⇄ Ecuador)' },
        { a: 'cucuta', b: 'san cristobal', nombre: 'Puente Atanasio Girardot / Simón Bolívar (Colombia ⇄ Venezuela)' },
        { a: 'san cristobal', b: 'cucuta', nombre: 'Puente Atanasio Girardot / Simón Bolívar (Venezuela ⇄ Colombia)' },
        { a: 'huaquillas', b: 'aguas verdes', nombre: 'Paso Fronterizo Huaquillas - Aguas Verdes (Ecuador ⇄ Perú)' },
        { a: 'aguas verdes', b: 'huaquillas', nombre: 'Paso Fronterizo Aguas Verdes - Huaquillas (Perú ⇄ Ecuador)' },
        { a: 'maicao', b: 'maracaibo', nombre: 'Paso Fronterizo Paraguachón (Colombia ⇄ Venezuela)' },
        { a: 'maracaibo', b: 'maicao', nombre: 'Paso Fronterizo Paraguachón (Venezuela ⇄ Colombia)' }
    ];

    const match = puentesFallback.find(p => p.a === a && p.b === b);
    if (match) {
        return {
            nombre: match.nombre,
            ciudadA: ciudadA,
            ciudadB: ciudadB
        };
    }
    return null;
}

function sonCiudadesConectablesJS(ciudadA, ciudadB) {
    if (!ciudadA || !ciudadB) return false;
    if (normalizarNombreCiudad(ciudadA) === normalizarNombreCiudad(ciudadB)) return true;
    return detectarPuenteFronterizoJS(ciudadA, ciudadB) !== null;
}

/**
 * Disparador explícito de búsqueda en el grafo BFS
 */
function ejecutarAutocompletadoBFS(manual = false) {
    const orig = (document.getElementById('nueva_origen')?.value || '').trim();
    const dest = (document.getElementById('nueva_destino')?.value || '').trim();

    if (orig.length < 2 || dest.length < 2) {
        if (manual) mostrarToastFlota('Indica la ciudad de origen y de destino para explorar conexiones.', 'warning');
        return;
    }

    const btn = document.getElementById('btnAutocompletarBFS');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Buscando conexiones...`;
    }

    fetch(`/suramerica2030/flota/apiSugerirConexiones?origen=${encodeURIComponent(orig)}&destino=${encodeURIComponent(dest)}`)
        .then(r => r.json())
        .then(res => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `⚡ Sugerir Conexiones`;
            }

            if (res.status === 'success' && res.caminos && res.caminos.length > 0) {
                const mapCaminos = {};
                res.caminos.forEach((c, i) => {
                    const key = c.modalidad ? (c.modalidad + '_' + (i + 1)) : ('CAMINO_' + (i + 1));
                    mapCaminos[key] = c;
                });
                window.modalidadesDetectadasActuales = mapCaminos;
                if (typeof renderizarTarjetasModalidad === 'function') {
                    renderizarTarjetasModalidad(mapCaminos);
                }
                const primerKey = Object.keys(mapCaminos)[0];
                if (primerKey && typeof seleccionarModalidad === 'function') {
                    seleccionarModalidad(primerKey);
                }
                if (manual) mostrarToastFlota(`¡Se encontraron ${res.caminos.length} combinaciones multimodales viables!`, 'success');
            } else {
                if (manual) mostrarToastFlota(res.mensaje || 'No se encontraron caminos en el grafo para este trayecto.', 'info');
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `⚡ Sugerir Conexiones`;
            }
            if (manual) mostrarToastFlota('Error: ' + err.message, 'danger');
        });
}

window.abrirModalOfertasOperador = abrirModalOfertasOperador;
window.renderizarFilasOfertasOperador = renderizarFilasOfertasOperador;
window.toggleFormularioNuevoTramoOferta = toggleFormularioNuevoTramoOferta;
window.alSeleccionarTramoEnOferta = alSeleccionarTramoEnOferta;
window.guardarNuevoTramoOferta = guardarNuevoTramoOferta;
window.conmutarEstadoOfertaOperador = conmutarEstadoOfertaOperador;
window.detectarPuenteFronterizoJS = detectarPuenteFronterizoJS;
window.sonCiudadesConectablesJS = sonCiudadesConectablesJS;
window.ejecutarAutocompletadoBFS = ejecutarAutocompletadoBFS;



