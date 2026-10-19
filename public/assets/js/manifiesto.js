// --- FUNCIONES PARA VIAJES DE PASAJEROS ---

function toggleSelectAllViajes(master) {
    const items = document.querySelectorAll('.chk-viaje-item');
    items.forEach(chk => {
        chk.checked = master.checked;
    });
    actualizarSeleccionViajes();
}

function actualizarSeleccionViajes() {
    const seleccionados = document.querySelectorAll('.chk-viaje-item:checked');
    const toolbar = document.getElementById('toolbarViajes');
    const badge = document.getElementById('badgeViajesCount');
    const master = document.getElementById('chkSelectAllViajes');
    const totalItems = document.querySelectorAll('.chk-viaje-item');

    if (toolbar && badge) {
        if (seleccionados.length > 0) {
            toolbar.classList.remove('d-none');
            toolbar.classList.add('d-flex');
            badge.textContent = `${seleccionados.length} viaje(s) seleccionado(s)`;
        } else {
            toolbar.classList.remove('d-flex');
            toolbar.classList.add('d-none');
        }
    }

    if (master && totalItems.length > 0) {
        master.checked = (seleccionados.length === totalItems.length);
    }
}

function abrirModalUnificarViajes() {
    const checkboxes = document.querySelectorAll('.chk-viaje-item:checked');
    if (checkboxes.length < 2) {
        alert("Por favor selecciona al menos 2 viajes en la tabla para poder integrarlos en un solo bus.");
        return;
    }

    const tbody = document.querySelector('#tablaViajesAUnificar tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    const hiddenCont = document.getElementById('hiddenInputsUnificarViajes');
    if (hiddenCont) hiddenCont.innerHTML = '';

    let totalPasajeros = 0;
    let primeraRutaId = null;
    let primerBusId = null;
    let primeraFecha = null;

    checkboxes.forEach(chk => {
        const val = chk.value;
        const fecha = chk.dataset.fecha;
        const rutaId = chk.dataset.rutaId;
        const busId = chk.dataset.busId;
        const origen = chk.dataset.origen;
        const destino = chk.dataset.destino;
        const busPlaca = chk.dataset.busPlaca;
        const busMarca = chk.dataset.busMarca;
        const pasajeros = parseInt(chk.dataset.pasajeros || 0, 10);

        totalPasajeros += pasajeros;
        if (!primeraRutaId) {
            primeraRutaId = rutaId;
            primerBusId = busId;
            primeraFecha = fecha;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>📅 ${fecha}</td>
            <td class="fw-bold">${origen} ➔ ${destino}</td>
            <td>${busMarca} [${busPlaca}]</td>
            <td class="text-center"><span class="badge bg-secondary fs-6">${pasajeros}</span></td>
        `;
        tbody.appendChild(tr);

        if (hiddenCont) {
            const inputHidden = document.createElement('input');
            inputHidden.type = 'hidden';
            inputHidden.name = 'viajes_a_unificar[]';
            inputHidden.value = val;
            hiddenCont.appendChild(inputHidden);
        }
    });

    const lblTotal = document.getElementById('totalPasajerosUnificar');
    if (lblTotal) lblTotal.textContent = totalPasajeros;
    
    const txtFecha = document.getElementById('txtFechaDestinoUnificadaViaje');
    if (txtFecha) txtFecha.value = primeraFecha || '';

    const selRuta = document.getElementById('selRutaDestinoUnificadaViaje');
    if (selRuta && primeraRutaId) selRuta.value = primeraRutaId;

    const selBus = document.getElementById('selBusDestinoUnificado');
    if (selBus && primerBusId) selBus.value = primerBusId;

    const modalEl = document.getElementById('modalUnificarViajes');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function abrirModalPrintConsolidadoViajes() {
    const checkboxes = document.querySelectorAll('.chk-viaje-item:checked');
    if (checkboxes.length === 0) {
        alert("Selecciona al menos un viaje para imprimir.");
        return;
    }
    const modalEl = document.getElementById('modalPrintConsolidadoViajes');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function ejecutarPrintConsolidadoViajes() {
    const checkboxes = document.querySelectorAll('.chk-viaje-item:checked');
    if (checkboxes.length === 0) return;

    const vals = Array.from(checkboxes).map(c => c.value);
    const primeraRutaId = checkboxes[0].dataset.rutaId || '';
    const primerBusId = checkboxes[0].dataset.busId || '';
    const primeraFecha = checkboxes[0].dataset.fecha || '';
    const logistica = document.getElementById('selLogisticaConsolidadoViajes').value || 'ESTANDAR';

    const url = `/suramerica2030/manifiesto/imprimir?consolidado=1&viajes=${encodeURIComponent(vals.join(','))}&ruta=${primeraRutaId}&bus=${primerBusId}&fecha=${encodeURIComponent(primeraFecha)}&tipo_logistica=${encodeURIComponent(logistica)}`;
    
    const modalEl = document.getElementById('modalPrintConsolidadoViajes');
    if (modalEl) {
        bootstrap.Modal.getInstance(modalEl).hide();
    }
    window.open(url, '_blank');
}

function imprimirEquipajeConsolidadoViajes() {
    const checkboxes = document.querySelectorAll('.chk-viaje-item:checked');
    if (checkboxes.length === 0) {
        alert("Selecciona al menos un viaje para imprimir el equipaje consolidado.");
        return;
    }

    const vals = Array.from(checkboxes).map(c => c.value);
    const primeraRutaId = checkboxes[0].dataset.rutaId || '';
    const primerBusId = checkboxes[0].dataset.busId || '';
    const primeraFecha = checkboxes[0].dataset.fecha || '';

    const url = `/suramerica2030/manifiesto/imprimirEquipaje?consolidado=1&viajes=${encodeURIComponent(vals.join(','))}&ruta=${primeraRutaId}&bus=${primerBusId}&fecha=${encodeURIComponent(primeraFecha)}`;
    window.open(url, '_blank');
}

function abrirModalCorreoConsolidadoViajes() {
    const checkboxes = document.querySelectorAll('.chk-viaje-item:checked');
    if (checkboxes.length === 0) {
        alert("Selecciona al menos un viaje para enviar por correo.");
        return;
    }

    const hiddenCont = document.getElementById('hiddenInputsCorreoConsolidadoViajes');
    if (hiddenCont) {
        hiddenCont.innerHTML = '';
        checkboxes.forEach(chk => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'viajes_consolidados[]';
            input.value = chk.value;
            hiddenCont.appendChild(input);
        });
    }

    const rInput = document.getElementById('correoConsolidadoViajesRutaId');
    if (rInput) rInput.value = checkboxes[0].dataset.rutaId || '';
    
    const bInput = document.getElementById('correoConsolidadoViajesBusId');
    if (bInput) bInput.value = checkboxes[0].dataset.busId || '';

    const fInput = document.getElementById('correoConsolidadoViajesFecha');
    if (fInput) fInput.value = checkboxes[0].dataset.fecha || '';
    
    const lblCount = document.getElementById('lblCantViajesCorreo');
    if (lblCount) lblCount.textContent = checkboxes.length;

    const modalEl = document.getElementById('modalCorreoConsolidadoViajes');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function filtrarPersonalViajesHoy() {
    const sucursal = (document.getElementById('suc_viajes_hoy').value || '').toLowerCase();
    const selectPersonal = document.getElementById('per_viajes_hoy');
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
    document.getElementById('correo_destino_viajes_hoy').value = '';
}

function filtrarPersonalViajesConsolidado() {
    const sucursal = (document.getElementById('suc_viajes_consolidado').value || '').toLowerCase();
    const selectPersonal = document.getElementById('per_viajes_consolidado');
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
    document.getElementById('correo_destino_viajes_consolidado').value = '';
}

function filtrarPersonalM(modalId) {
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

// --- FUNCIONES PARA DESPACHOS DE CARGA ---

function toggleSelectAllDespachos(master) {
    const items = document.querySelectorAll('.chk-despacho-item');
    items.forEach(chk => {
        chk.checked = master.checked;
    });
    actualizarSeleccionDespachos();
}

function actualizarSeleccionDespachos() {
    const seleccionados = document.querySelectorAll('.chk-despacho-item:checked');
    const toolbar = document.getElementById('toolbarDespachos');
    const badge = document.getElementById('badgeDespachosCount');
    const master = document.getElementById('chkSelectAllDespachos');
    const totalItems = document.querySelectorAll('.chk-despacho-item');

    if (toolbar && badge) {
        if (seleccionados.length > 0) {
            toolbar.classList.remove('d-none');
            toolbar.classList.add('d-flex');
            badge.textContent = `${seleccionados.length} despacho(s) seleccionado(s)`;
        } else {
            toolbar.classList.remove('d-flex');
            toolbar.classList.add('d-none');
        }
    }

    if (master && totalItems.length > 0) {
        master.checked = (seleccionados.length === totalItems.length);
    }
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

    const url = `/suramerica2030/manifiesto/imprimirCarga?consolidado=1&despachos=${encodeURIComponent(vals.join(','))}&ruta_id=${primeraRutaId}&fecha=${encodeURIComponent(primeraFecha)}`;
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

function filtrarPersonal(tipo) {
    const sucursal = (document.getElementById('suc_' + tipo).value || '').toLowerCase();
    const selectPersonal = document.getElementById('per_' + tipo);
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
    const inputDestino = document.getElementById('correo_destino_' + tipo);
    if (inputDestino) inputDestino.value = '';
}
