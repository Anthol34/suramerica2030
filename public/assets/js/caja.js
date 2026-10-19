/**
 * SURAMERICA 2030 - Módulo de Gestión de Caja y Arqueos
 * Funciones de cliente para aperturas, movimientos, traslados de fecha y arqueos físicos.
 */

// ==========================================
// SECCIÓN 1: SELECCIÓN Y TRASLADO DE MOVIMIENTOS
// ==========================================
function actualizarContadorMovimientos() {
    const checks = document.querySelectorAll('.mov-checkbox:checked');
    const count = checks.length;
    const btn = document.getElementById('btnMoverSeleccionados');
    const countSpan = document.getElementById('countMover');
    
    if (countSpan) countSpan.textContent = count;
    if (btn) {
        btn.disabled = count === 0;
        if (count > 0) {
            btn.classList.remove('btn-outline-warning');
            btn.classList.add('btn-warning');
        } else {
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-outline-warning');
        }
    }
}

function seleccionarTodosMovimientos(master) {
    const checks = document.querySelectorAll('.mov-checkbox');
    checks.forEach(c => c.checked = master.checked);
    actualizarContadorMovimientos();
}

function abrirModalMoverFecha() {
    const checks = document.querySelectorAll('.mov-checkbox:checked');
    if (checks.length === 0) {
        alert('Por favor selecciona al menos una transacción para cambiar de fecha.');
        return;
    }
    poblarModalMoverFecha(checks);
}

function moverMovimientoIndividual(id, concepto, monto, tipo, factura, hora) {
    const checks = document.querySelectorAll('.mov-checkbox');
    checks.forEach(c => c.checked = (c.value == id));
    actualizarContadorMovimientos();
    
    const targetCheck = document.querySelector('.mov-checkbox[value="' + id + '"]');
    if (targetCheck) {
        poblarModalMoverFecha([targetCheck]);
    }
}

function poblarModalMoverFecha(checks) {
    const containerHidden = document.getElementById('hiddenMovimientosContainer');
    const containerDetalle = document.getElementById('modalDetalleSeleccionados');
    const numSpan = document.getElementById('modalNumSeleccionados');
    
    if (!containerHidden || !containerDetalle) return;
    
    containerHidden.innerHTML = '';
    containerDetalle.innerHTML = '';
    if (numSpan) numSpan.textContent = checks.length;
    
    checks.forEach(c => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'movimientos_ids[]';
        hiddenInput.value = c.value;
        containerHidden.appendChild(hiddenInput);
        
        const item = document.createElement('div');
        item.className = 'd-flex justify-content-between align-items-center py-1 border-bottom';
        item.style.fontSize = '12px';
        
        const isIngreso = c.dataset.tipo === 'INGRESO';
        const badgeColor = isIngreso ? 'bg-success' : 'bg-danger';
        const badgeText = isIngreso ? '+ Ingreso' : '- Egreso';
        const facturaText = c.dataset.factura ? '<span class="badge bg-secondary ms-1">Fact ' + c.dataset.factura + '</span>' : '';
        
        item.innerHTML = '<div>' +
            '<span class="badge ' + badgeColor + '" style="font-size:10px;">' + badgeText + '</span> ' +
            '<strong>' + (c.dataset.concepto || 'Transacción') + '</strong>' +
            facturaText +
            '</div>' +
            '<div class="fw-bold ' + (isIngreso ? 'text-success' : 'text-danger') + '">$' + (c.dataset.monto || '0.00') + '</div>';
            
        containerDetalle.appendChild(item);
    });
    
    const inputFecha = document.getElementById('inputNuevaFechaMover');
    const fechaConsulta = window.CAJA_CONFIG ? window.CAJA_CONFIG.fechaConsulta : '';
    if (inputFecha && !inputFecha.value && fechaConsulta) {
        inputFecha.value = fechaConsulta;
    }
    
    const modalEl = document.getElementById('modalMoverFecha');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function validarFormMoverFecha() {
    const inputFecha = document.getElementById('inputNuevaFechaMover');
    if (!inputFecha || !inputFecha.value) {
        alert('Por favor selecciona la fecha de destino.');
        return false;
    }
    const fechaActual = window.CAJA_CONFIG ? window.CAJA_CONFIG.fechaConsulta : '';
    if (fechaActual && inputFecha.value === fechaActual) {
        if (!confirm('La fecha seleccionada (' + inputFecha.value + ') es la misma que la fecha actual. ¿Deseas continuar?')) {
            return false;
        }
    }
    return true;
}

// ==========================================
// SECCIÓN 2: APERTURA Y FONDOS INICIALES
// ==========================================
function establecerFondoInicialCaja(monto) {
    const inp = document.getElementById('apertura_valor_inicial_caja') || document.getElementById('apertura_valor_inicial_cierres');
    if (inp) {
        inp.value = parseFloat(monto).toFixed(2);
        inp.focus();
        inp.select();
    }
}

function establecerFondoInicialCierres(monto) {
    establecerFondoInicialCaja(monto);
}

// ==========================================
// SECCIÓN 3: CONTROL DE ARQUEO Y DENOMINACIONES
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const modalCierre = document.getElementById('modalConfirmarCierre');
    if (modalCierre) {
        modalCierre.addEventListener('shown.bs.modal', function () {
            recalcularDiferenciaArqueo();
            const inputFisico = document.getElementById('cierre_efectivo_fisico');
            if (inputFisico) {
                inputFisico.focus();
                inputFisico.select();
            }
        });
    }
});

function recalcularDiferenciaArqueo() {
    const inputFisico = document.getElementById('cierre_efectivo_fisico');
    const displayEsperado = document.getElementById('monto_esperado_display');
    const alertBox = document.getElementById('alerta_diferencia_arqueo');
    const inputDiferencia = document.getElementById('input_diferencia_arqueo');

    if (!inputFisico || !displayEsperado || !alertBox) return;

    const esperado = parseFloat(displayEsperado.getAttribute('data-esperado')) || 0;
    const fisico = parseFloat(inputFisico.value) || 0;
    const dif = Math.round((fisico - esperado) * 100) / 100;

    if (inputDiferencia) {
        inputDiferencia.value = dif.toFixed(2);
    }

    if (Math.abs(dif) < 0.009) {
        alertBox.className = 'alert alert-success py-2 px-3 mb-2 text-center small fw-bold';
        alertBox.innerHTML = '✅ CAJA CUADRADA EXACTA ($ 0.00)';
    } else if (dif < 0) {
        alertBox.className = 'alert alert-danger py-2 px-3 mb-2 text-center small fw-bold';
        alertBox.innerHTML = `⚠️ FALTANTE EN CAJA: -$ ${Math.abs(dif).toFixed(2)}`;
    } else {
        alertBox.className = 'alert alert-primary py-2 px-3 mb-2 text-center small fw-bold';
        alertBox.innerHTML = `ℹ️ SOBRANTE EN CAJA: +$ ${dif.toFixed(2)}`;
    }
}

function calcularDenominaciones() {
    let total = 0;
    const inputs = document.querySelectorAll('.calc-denom');
    inputs.forEach(inp => {
        const val = parseFloat(inp.getAttribute('data-val')) || 0;
        const cant = parseInt(inp.value, 10) || 0;
        total += (val * cant);
    });

    total = Math.round(total * 100) / 100;

    const totalDisplay = document.getElementById('total_calculadora_display');
    if (totalDisplay) {
        totalDisplay.innerText = '$ ' + total.toFixed(2);
    }

    const inputFisico = document.getElementById('cierre_efectivo_fisico');
    if (inputFisico) {
        inputFisico.value = total.toFixed(2);
        recalcularDiferenciaArqueo();
    }
}

function limpiarCalculadora() {
    const inputs = document.querySelectorAll('.calc-denom');
    inputs.forEach(inp => { inp.value = ''; });
    const totalDisplay = document.getElementById('total_calculadora_display');
    if (totalDisplay) totalDisplay.innerText = '$ 0.00';
    
    // Restaurar el valor por defecto esperado
    const displayEsperado = document.getElementById('monto_esperado_display');
    const inputFisico = document.getElementById('cierre_efectivo_fisico');
    if (displayEsperado && inputFisico) {
        const esperado = parseFloat(displayEsperado.getAttribute('data-esperado')) || 0;
        inputFisico.value = esperado.toFixed(2);
        recalcularDiferenciaArqueo();
    }
}
