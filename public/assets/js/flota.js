function generarOpcionesParadasCorredor(idCorredor, valorSeleccionado = '', placeholder = '-- Seleccionar Ciudad --') {
    let html = `<option value="">${placeholder}</option>`;
    const paradas = (PARADAS_POR_CORREDOR && PARADAS_POR_CORREDOR[idCorredor]) ? PARADAS_POR_CORREDOR[idCorredor] : [];
    let encontrado = false;
    const valNorm = (valorSeleccionado || '').trim().toLowerCase();

    const ciudadesEnParadas = {};
    if (paradas.length > 0) {
        html += `<optgroup label="📍 Paradas del Corredor (en orden de viaje)">`;
        paradas.forEach((p, idx) => {
            const cNorm = p.CIUDAD.trim().toLowerCase();
            ciudadesEnParadas[cNorm] = true;
            const isSel = valNorm && cNorm === valNorm;
            if (isSel) encontrado = true;
            const extra = p.TIPO_PARADA ? ` [${p.TIPO_PARADA.replace(/_/g, ' ')}]` : '';
            html += `<option value="${p.CIUDAD}" ${isSel ? 'selected' : ''}>${idx + 1}. ${p.CIUDAD} (${p.PAIS})${extra}</option>`;
        });
        html += `</optgroup>`;
    }

    if (typeof CIUDADES_CATALOGO !== 'undefined' && Array.isArray(CIUDADES_CATALOGO)) {
        const porPais = {};
        CIUDADES_CATALOGO.forEach(cd => {
            const pais = (cd.PAIS || 'OTROS').toUpperCase().trim();
            if (!porPais[pais]) porPais[pais] = [];
            porPais[pais].push(cd.NOMBRE);
        });

        const banderas = { 'VENEZUELA': '🇻🇪', 'COLOMBIA': '🇨🇴', 'ECUADOR': '🇪🇨', 'PERU': '🇵🇪' };
        Object.keys(porPais).forEach(pais => {
            const flag = banderas[pais] || '🌎';
            html += `<optgroup label="${flag} Catálogo de Ciudades - ${pais}">`;
            porPais[pais].forEach(nomCiu => {
                const cNorm = nomCiu.trim().toLowerCase();
                const isSel = valNorm && cNorm === valNorm;
                if (isSel) encontrado = true;
                const enCorredor = ciudadesEnParadas[cNorm] ? ' (En corredor)' : '';
                html += `<option value="${nomCiu}" ${isSel ? 'selected' : ''}>${nomCiu}${enCorredor}</option>`;
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
        if (divDirecto) {
            divDirecto.classList.add('d-none');
            divDirecto.querySelectorAll('input, select').forEach(el => el.disabled = true);
        }
        if (badgeAuto) badgeAuto.classList.remove('d-none');
        if (hintUtil) hintUtil.textContent = '⚡ Calculado automáticamente de la suma de tramos.';
        if (listaContainer && listaContainer.querySelectorAll('.tramo-card').length === 0) {
            addTramo(listaContainerId, rutaId);
        }
    } else {
        div.classList.add('d-none');
        div.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        if (divDirecto) {
            divDirecto.classList.remove('d-none');
            divDirecto.querySelectorAll('input, select').forEach(el => el.disabled = false);
        }
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
        if (fCorredor && cCorredor !== fCorredor) return false;

        return true;
    });

    const totalCoincidentes = filasCoincidentes.length;

    // Actualizar badge de filtros activos
    actualizarBadgeFiltrosActivosRutas(query, fOrigen, fDestino, fOperador, fModalidad, fTipoViaje, fEstado, fCorredor);

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
        if (fCorredor || query || fOrigen || fDestino || fOperador || fModalidad || fTipoViaje || fEstado) {
            badgeTotal.textContent = `${totalCoincidentes} de ${todasLasFilas.length} rutas`;
        } else {
            badgeTotal.textContent = `${todasLasFilas.length} rutas`;
        }
    }

    // Renderizar botones de paginación
    renderizarBotonesPaginacionRutas(totalPaginas, totalCoincidentes);
}

function actualizarBadgeFiltrosActivosRutas(query, ori, des, op, mod, viaje, est, cor) {
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
    const selectOperador = document.getElementById('filtroOperadorRutas');
    const selectModalidad = document.getElementById('filtroModalidadRutas');
    const selectTipoViaje = document.getElementById('filtroTipoViajeRutas');
    const selectEstado = document.getElementById('filtroEstadoRutas');
    const selectCorredor = document.getElementById('filtroCorredorRutas');
    
    if (inputBuscador) inputBuscador.value = '';
    if (selectOrigen) selectOrigen.value = '';
    if (selectDestino) selectDestino.value = '';
    if (selectOperador) selectOperador.value = '';
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

    ['filtroOrigenRutas', 'filtroDestinoRutas', 'filtroOperadorRutas', 'filtroModalidadRutas', 'filtroTipoViajeRutas', 'filtroEstadoRutas', 'filtroCorredorRutas', 'filtroLimiteRutas'].forEach(id => {
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
    if (activeTab === 'corredores') activeTab = 'corredor';
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

// ============================================================
// CONTROL Y CONDICIONAMIENTO DE MODALIDADES
// ============================================================

function alCambiarModalidadSelect(selectEl, rutaId) {
    const sufijo = rutaId ? '_' + rutaId : '_';
    const cajaCustom = document.getElementById('caja_modalidad_personalizada' + sufijo);
    if (!cajaCustom) return;

    if (selectEl.value === 'OTRO') {
        cajaCustom.classList.remove('d-none');
        const input = cajaCustom.querySelector('input[name="modalidad_personalizada"]');
        if (input) {
            input.focus();
            if (!input.value.trim()) {
                input.placeholder = "Escriba el nombre (ej: Ruta Expresa Internacional)";
            }
        }
    } else {
        cajaCustom.classList.add('d-none');
    }
}

function condicionarModalidadPorOperador(idOperador, rutaId) {
    const sufijo = rutaId ? '_' + rutaId : '_';
    const selectMod = document.getElementById('modalidad_select_edit' + sufijo);
    if (!selectMod) return;

    // Respetar si el usuario escribió un nombre personalizado explícito (OTRO)
    if (selectMod.value === 'OTRO') {
        const cajaCustom = document.getElementById('caja_modalidad_personalizada' + sufijo);
        const inputCustom = cajaCustom ? cajaCustom.querySelector('input[name="modalidad_personalizada"]') : null;
        if (inputCustom && inputCustom.value.trim().length > 0) {
            return;
        }
    }

    // Regla de operador: Si es Panamericana (ID 2)
    const esPanamericana = String(idOperador) === '2';

    if (esPanamericana) {
        selectMod.value = 'PANAMERICANA';
    } else {
        if (selectMod.value === 'PANAMERICANA' || selectMod.value === 'AUTO') {
            selectMod.value = 'ESTANDAR';
        }
    }
    alCambiarModalidadSelect(selectMod, rutaId);
}

function condicionarModalidadPorTramos(rutaId) {
    const sufijo = rutaId ? '_' + rutaId : '_';
    const selectMod = document.getElementById('modalidad_select_edit' + sufijo);
    if (!selectMod) return;

    // Respetar personalizaciones manuales
    if (selectMod.value === 'OTRO') {
        const cajaCustom = document.getElementById('caja_modalidad_personalizada' + sufijo);
        const inputCustom = cajaCustom ? cajaCustom.querySelector('input[name="modalidad_personalizada"]') : null;
        if (inputCustom && inputCustom.value.trim().length > 0) {
            return;
        }
    }

    const containerId = rutaId ? 'lista_tramos_edit_' + rutaId : 'lista_tramos_nuevos';
    const container = document.getElementById(containerId);
    if (!container) return;

    const opSelects = container.querySelectorAll('select[name="tramo_id_operador[]"]');
    let tienePanamericana = false;
    opSelects.forEach(sel => {
        if (String(sel.value) === '2') {
            tienePanamericana = true;
        } else {
            const optText = sel.options[sel.selectedIndex]?.text || '';
            if (optText.toLowerCase().includes('panamericana')) {
                tienePanamericana = true;
            }
        }
    });

    if (tienePanamericana) {
        selectMod.value = 'PANAMERICANA';
    } else {
        selectMod.value = 'CONEXION_TRAMOS';
    }
    alCambiarModalidadSelect(selectMod, rutaId);
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
    const idCorredor = ruta.ID_CORREDOR || '';
    const nombreCorredor = ruta.NOMBRE_CORREDOR || '';
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
    fila.setAttribute('data-corredor', idCorredor);
    fila.setAttribute('data-origen', origen.toLowerCase().trim());
    fila.setAttribute('data-destino', destino.toLowerCase().trim());
    fila.setAttribute('data-operador', operadorMostrar.toLowerCase().trim());
    fila.setAttribute('data-id-operador', idOperador);
    fila.setAttribute('data-modalidad', mod.toLowerCase().trim());
    fila.setAttribute('data-tipo-viaje', tipoViaje.toLowerCase().trim());
    fila.setAttribute('data-estado', estado.toLowerCase().trim());
    fila.setAttribute('data-search', `${origen} ${destino} ${nombreCorredor} #${idRuta} ${operadorMostrar} ${opsTramos.join(' ')} ${mod}`.toLowerCase());

    // Actualizar clase de fila activa o inactiva
    if (esActivo) {
        fila.classList.remove('table-secondary', 'opacity-75');
    } else {
        fila.classList.add('table-secondary', 'opacity-75');
    }

    // Columna 1: Trayecto y Corredor
    const colTrayecto = fila.querySelector('.col-ruta-trayecto');
    if (colTrayecto) {
        let htmlTrayecto = `<div class="fw-bold text-dark fs-6">${escapeHtml(origen)} ➔ ${escapeHtml(destino)}</div>`;
        if (nombreCorredor) {
            htmlTrayecto += `<div class="mt-1"><span class="badge bg-light text-primary border" style="font-size: 0.75rem; font-weight: 500;">🛤️ ${escapeHtml(nombreCorredor)}</span></div>`;
        }
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

    // Columna 4: Tarifa Pasaje
    const colPasaje = fila.querySelector('.col-ruta-tarifa-pasaje');
    if (colPasaje) {
        const txtUtil = (tipoUtilidad === 'FIJO') 
            ? '$' + utilidadPasaje.toFixed(2) 
            : utilidadPasaje.toFixed(0) + '%';
        colPasaje.innerHTML = `
            <span class="fs-6 fw-bold text-dark">$ ${precioPasaje.toFixed(2)}</span>
            <small class="text-success d-block fw-bold" style="font-size: 0.72rem;" title="Retención de Utilidad de la Agencia">
                💼 ${txtUtil} util.
            </small>
        `;
    }

    // Columna 5: Tarifa Encomienda
    const colEncomienda = fila.querySelector('.col-ruta-tarifa-encomienda');
    if (colEncomienda) {
        colEncomienda.innerHTML = `<span class="fs-6 fw-bold text-dark">$ ${precioEncomienda.toFixed(2)}</span>`;
    }

    // Columna 6: Estado
    const colEstado = fila.querySelector('.col-ruta-estado');
    if (colEstado) {
        colEstado.innerHTML = esActivo 
            ? '<span class="badge bg-success">Activo</span>' 
            : '<span class="badge bg-secondary">Inactivo</span>';
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
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    const openModal = params.get('openModal');

    // Mapeo de parámetros heredados a modales satélites
    if (tab === 'tramos' || openModal === 'tramos') {
        const modalEl = document.getElementById('modalCatalogoTramos');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    } else if (tab === 'corredor' || openModal === 'corredor') {
        const modalEl = document.getElementById('modalItinerarioCorredor');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    } else if (tab === 'ciudades' || openModal === 'ciudades') {
        const modalEl = document.getElementById('modalCatalogoCiudades');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    } else if (tab === 'buses' || tab === 'operadores' || tab === 'flota') {
        const tabBtn = document.querySelector('button[data-bs-target="#tab-flota"]');
        if (tabBtn && window.bootstrap && bootstrap.Tab) {
            const tabObj = bootstrap.Tab.getOrCreateInstance(tabBtn);
            tabObj.show();
        }
    }
});

