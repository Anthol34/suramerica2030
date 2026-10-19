function generarOpcionesParadasCorredor(idCorredor, valorSeleccionado = '', placeholder = '-- Seleccionar Ciudad --') {
    let html = `<option value="">${placeholder}</option>`;
    const paradas = (PARADAS_POR_CORREDOR && PARADAS_POR_CORREDOR[idCorredor]) ? PARADAS_POR_CORREDOR[idCorredor] : [];
    let encontrado = false;
    const valNorm = (valorSeleccionado || '').trim().toLowerCase();

    if (paradas.length > 0) {
        html += `<optgroup label="📍 Paradas del Corredor (en orden de viaje)">`;
        paradas.forEach((p, idx) => {
            const isSel = valNorm && p.CIUDAD.trim().toLowerCase() === valNorm;
            if (isSel) encontrado = true;
            const extra = p.TIPO_PARADA ? ` [${p.TIPO_PARADA.replace(/_/g, ' ')}]` : '';
            html += `<option value="${p.CIUDAD}" ${isSel ? 'selected' : ''}>${idx + 1}. ${p.CIUDAD} (${p.PAIS})${extra}</option>`;
        });
        html += `</optgroup>`;
    }
    if (valorSeleccionado && !encontrado) {
        html += `<optgroup label="⚠️ Ciudad Actual Configurada">`;
        html += `<option value="${valorSeleccionado}" selected>${valorSeleccionado} (Registrada)</option>`;
        html += `</optgroup>`;
    }

    return html;
}

function alCambiarCorredorEnRuta(rutaId) {
    const selCorredor = document.getElementById(rutaId ? 'edit_corredor_' + rutaId : 'nuevo_corredor');
    if (!selCorredor) return;
    const idCorredor = selCorredor.value;

    const selOrigen = document.getElementById(rutaId ? 'edit_origen_' + rutaId : 'nuevo_origen');
    const selDestino = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');

    if (selOrigen) {
        const valActual = selOrigen.value;
        selOrigen.innerHTML = generarOpcionesParadasCorredor(idCorredor, valActual, '-- Seleccione Origen --');
    }
    if (selDestino) {
        const valActual = selDestino.value;
        selDestino.innerHTML = generarOpcionesParadasCorredor(idCorredor, valActual, '-- Seleccione Destino --');
    }

    // Actualizar también todos los selectores de tramos en este modal
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (container) {
        const selectsOrigen = container.querySelectorAll('.tramo-origen-select');
        selectsOrigen.forEach(sel => {
            const v = sel.value;
            sel.innerHTML = generarOpcionesParadasCorredor(idCorredor, v, '-- Sube en --');
        });
        const selectsDestino = container.querySelectorAll('.tramo-destino-select');
        selectsDestino.forEach(sel => {
            const v = sel.value;
            sel.innerHTML = generarOpcionesParadasCorredor(idCorredor, v, '-- Baja en --');
        });
        actualizarTitulosTramos(container);
    }
}

function alCambiarCabecerasRuta(rutaId) {
    const selOrigen = document.getElementById(rutaId ? 'edit_origen_' + rutaId : 'nuevo_origen');
    const selDestino = document.getElementById(rutaId ? 'edit_destino_' + rutaId : 'nuevo_destino');
    if (!selOrigen || !selDestino) return;

    const orig = selOrigen.value;
    const dest = selDestino.value;

    // En nueva ruta manual o si solo hay 1 tramo y está vacío, auto-sincronizar Tramo 1
    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (container) {
        const cards = container.querySelectorAll('.tramo-card');
        if (cards.length === 1) {
            const card1 = cards[0];
            const selOrigTramo = card1.querySelector('.tramo-origen-select');
            const selDestTramo = card1.querySelector('.tramo-destino-select');
            if (selOrigTramo && (!selOrigTramo.value || !rutaId) && orig) {
                selOrigTramo.value = orig;
            }
            if (selDestTramo && (!selDestTramo.value || !rutaId) && dest) {
                selDestTramo.value = dest;
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

    if (valor === 'CONEXION') {
        div.classList.remove('d-none');
        div.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
        if (divDirecto) divDirecto.classList.add('d-none');
        if (badgeAuto) badgeAuto.classList.remove('d-none');
        if (hintUtil) hintUtil.textContent = '⚡ Calculado automáticamente de la suma de tramos.';
        if (listaContainer && listaContainer.querySelectorAll('.tramo-card').length === 0) {
            addTramo(listaContainerId, rutaId);
        }
    } else {
        div.classList.add('d-none');
        div.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        if (divDirecto) divDirecto.classList.remove('d-none');
        if (badgeAuto) badgeAuto.classList.add('d-none');
        if (hintUtil) hintUtil.textContent = 'Retención directa de agencia (% o $).';
    }
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

    const selCorredor = document.getElementById(rutaId ? 'edit_corredor_' + rutaId : 'nuevo_corredor');
    const idCorredor = selCorredor ? selCorredor.value : '';

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

    const opcionesOrigen = generarOpcionesParadasCorredor(idCorredor, subeSugerido, '-- Sube en --');
    const opcionesDestino = generarOpcionesParadasCorredor(idCorredor, bajaSugerido, '-- Baja en --');

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
                        <select name="tramo_id_operador[]" class="form-select form-select-sm border-primary fw-bold" onchange="const nom = this.options[this.selectedIndex].getAttribute(\'data-nombre\'); const hid = this.closest(\'.row\').querySelector(\'input[name=\\\'tramo_operador[]\\\']\'); if(hid) hid.value = nom;">
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
}

function eliminarTramoCard(btn, rutaId) {
    const card = btn.closest('.tramo-card');
    const container = card ? card.parentElement : null;
    if (card) {
        card.remove();
    }
    if (container) {
        actualizarTitulosTramos(container);
    }
    recalcularTotalesRuta(rutaId);
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

    const selectModalidad = document.getElementById('filtroModalidadRutas');
    const fModalidad = selectModalidad ? selectModalidad.value.trim().toLowerCase() : '';

    const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
    const fTipoViaje = selectTipoViaje ? selectTipoViaje.value.trim().toLowerCase() : '';

    const selectEstado = document.getElementById('filtroEstadoRutas');
    const fEstado = selectEstado ? selectEstado.value.trim().toLowerCase() : '';

    const selectCorredor = document.getElementById('filtroCorredorRutas');
    const fCorredor = selectCorredor ? selectCorredor.value.trim() : '';

    const selectLimite = document.getElementById('filtroLimiteRutas');
    const limiteVal = selectLimite ? selectLimite.value : '15';

    // Sincronizar banner de generación de rutas con el corredor seleccionado
    const genCorredorInput = document.getElementById('generador_id_corredor');
    const genCorredorTexto = document.getElementById('textoNombreCorredorGenerar');
    if (genCorredorInput) {
        if (fCorredor) {
            genCorredorInput.value = fCorredor;
            if (genCorredorTexto && selectCorredor) {
                const opt = selectCorredor.options[selectCorredor.selectedIndex];
                if (opt) {
                    const partes = opt.text.split('-');
                    genCorredorTexto.textContent = partes[0].trim();
                }
            }
        }
    }

    // Persistir corredor seleccionado en la URL sin recargar
    try {
        const url = new URL(window.location.href);
        if (fCorredor) {
            url.searchParams.set('corredor_filtro', fCorredor);
        } else {
            url.searchParams.delete('corredor_filtro');
        }
        window.history.replaceState(null, '', url.toString());
    } catch(e) {}

    const todasLasFilas = Array.from(document.querySelectorAll('#tablaRutas tbody tr.fila-ruta'));
    const filaSinResultados = document.getElementById('filaSinRutas');

    // Filtrar filas coincidentes
    const filasCoincidentes = todasLasFilas.filter(fila => {
        const cCorredor = fila.getAttribute('data-corredor') || '';
        const cOrigen = fila.getAttribute('data-origen') || '';
        const cDestino = fila.getAttribute('data-destino') || '';
        const cModalidad = fila.getAttribute('data-modalidad') || '';
        const cTipoViaje = fila.getAttribute('data-tipo-viaje') || '';
        const cEstado = fila.getAttribute('data-estado') || '';
        const searchFila = fila.getAttribute('data-search') || '';

        if (query && !searchFila.includes(query)) return false;
        if (fOrigen && cOrigen !== fOrigen) return false;
        if (fDestino && cDestino !== fDestino) return false;
        if (fModalidad && cModalidad !== fModalidad) return false;
        if (fTipoViaje && cTipoViaje !== fTipoViaje) return false;
        if (fEstado && cEstado !== fEstado) return false;
        if (fCorredor && cCorredor !== fCorredor) return false;

        return true;
    });

    const totalCoincidentes = filasCoincidentes.length;

    // Actualizar badge de filtros activos
    actualizarBadgeFiltrosActivosRutas(query, fOrigen, fDestino, fModalidad, fTipoViaje, fEstado, fCorredor);

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
        if (fCorredor || query || fOrigen || fDestino || fModalidad || fTipoViaje || fEstado) {
            badgeTotal.textContent = `${totalCoincidentes} de ${todasLasFilas.length} rutas`;
        } else {
            badgeTotal.textContent = `${todasLasFilas.length} rutas`;
        }
    }

    // Renderizar botones de paginación
    renderizarBotonesPaginacionRutas(totalPaginas, totalCoincidentes);
}

function actualizarBadgeFiltrosActivosRutas(query, ori, des, mod, viaje, est, cor) {
    const badge = document.getElementById('badgeFiltrosActivosRutas');
    if (!badge) return;
    
    let activos = [];
    if (query) activos.push(`"${query}"`);
    if (ori) activos.push(`Origen: ${ori.toUpperCase()}`);
    if (des) activos.push(`Destino: ${des.toUpperCase()}`);
    if (mod) activos.push(`Modalidad: ${mod.toUpperCase()}`);
    if (viaje) activos.push(`Viaje: ${viaje.toUpperCase()}`);
    if (est) activos.push(`Estado: ${est.toUpperCase()}`);
    if (cor) activos.push(`Corredor #${cor}`);

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
    const selectModalidad = document.getElementById('filtroModalidadRutas');
    const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
    const selectEstado = document.getElementById('filtroEstadoRutas');
    const selectCorredor = document.getElementById('filtroCorredorRutas');
    
    if (inputBuscador) inputBuscador.value = '';
    if (selectOrigen) selectOrigen.value = '';
    if (selectDestino) selectDestino.value = '';
    if (selectModalidad) selectModalidad.value = '';
    if (selectTipoViaje) selectTipoViaje.value = '';
    if (selectEstado) selectEstado.value = '';
    if (selectCorredor) selectCorredor.value = '';

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

    ['filtroOrigenRutas', 'filtroDestinoRutas', 'filtroModalidadRutas', 'filtroTipoViajeRutas', 'filtroEstadoRutas', 'filtroCorredorRutas', 'filtroLimiteRutas'].forEach(id => {
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
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        const triggerEl = document.querySelector(`#flotaTabs button[data-bs-target="#tab-${activeTab}"]`);
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
    }

    // Inicializar filtro de corredor y paginación en rutas
    const filtroCorredorUrl = urlParams.get('corredor_filtro') || (activeTab === 'rutas' ? urlParams.get('corredor_id') : '');
    const filtroSelect = document.getElementById('filtroCorredorRutas');
    if (filtroSelect && filtroCorredorUrl) {
        filtroSelect.value = filtroCorredorUrl;
    }
    aplicarFiltrosYPaginarRutas(1);
});
