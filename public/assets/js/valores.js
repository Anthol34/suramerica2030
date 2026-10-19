/**
 * SURAMERICA 2030 - Módulo de Gestión de Remesas y Valores
 * Manejo de itinerarios, clientes, comisiones y pagos mixtos
 */

function inicializarOrigenesVal() {
    const selOrigen = document.getElementById('sel_origen_val');
    if (!selOrigen || !window.VALORES_CONFIG) return;
    
    const rutas = window.VALORES_CONFIG.rutas || [];
    const ciudadSucursal = window.VALORES_CONFIG.ciudadSucursal || '';

    const origenesUnicos = Array.from(new Set(rutas.map(r => r.RUT_ORIGEN))).sort();
    selOrigen.innerHTML = '<option value="">- Seleccione Origen -</option>';
    origenesUnicos.forEach(orig => {
        const opt = document.createElement('option');
        opt.value = orig;
        opt.textContent = '📍 ' + orig;
        selOrigen.appendChild(opt);
    });
    
    // Auto-seleccionar sucursal si coincide
    if (ciudadSucursal) {
        const matchSuc = origenesUnicos.find(o => o.toUpperCase() === ciudadSucursal);
        if (matchSuc) {
            selOrigen.value = matchSuc;
            alCambiarOrigenVal();
        }
    }
}

function alCambiarOrigenVal() {
    const origen = (document.getElementById('sel_origen_val') || {}).value;
    const selDestino = document.getElementById('sel_destino_val');
    const rutaIdInput = document.getElementById('ruta_id');
    const infoRuta = document.getElementById('info_ruta_val');
    
    if (!selDestino || !rutaIdInput || !infoRuta) return;
    const rutas = (window.VALORES_CONFIG && window.VALORES_CONFIG.rutas) ? window.VALORES_CONFIG.rutas : [];

    if (!origen) {
        selDestino.innerHTML = '<option value="">- Elija Origen Primero -</option>';
        selDestino.disabled = true;
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        calcularCalculosValores();
        return;
    }
    
    const rutasOrigen = rutas.filter(r => r.RUT_ORIGEN === origen);
    const destinosUnicos = Array.from(new Set(rutasOrigen.map(r => r.RUT_DESTINO))).sort();
    
    selDestino.innerHTML = '<option value="">- Seleccione Destino -</option>';
    destinosUnicos.forEach(dest => {
        const opt = document.createElement('option');
        opt.value = dest;
        opt.textContent = '🏁 ' + dest;
        selDestino.appendChild(opt);
    });
    selDestino.disabled = false;
    rutaIdInput.value = '';
    infoRuta.classList.add('d-none');
    calcularCalculosValores();
}

function alCambiarDestinoVal() {
    const origen = (document.getElementById('sel_origen_val') || {}).value;
    const destino = (document.getElementById('sel_destino_val') || {}).value;
    const rutaIdInput = document.getElementById('ruta_id');
    const infoRuta = document.getElementById('info_ruta_val');
    
    if (!rutaIdInput || !infoRuta) return;
    const rutas = (window.VALORES_CONFIG && window.VALORES_CONFIG.rutas) ? window.VALORES_CONFIG.rutas : [];

    if (!origen || !destino) {
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
        calcularCalculosValores();
        return;
    }
    
    const match = rutas.find(r => r.RUT_ORIGEN === origen && r.RUT_DESTINO === destino);
    if (match) {
        rutaIdInput.value = match.ID_RUTA;
        infoRuta.classList.remove('d-none');
        
        const badgeTipo = document.getElementById('badge_tipo_val');
        if (badgeTipo) {
            if (match.TIPO_VIAJE === 'CONEXION') {
                badgeTipo.className = 'badge bg-warning text-dark fs-6';
                badgeTipo.textContent = '🔄 CONEXIÓN';
            } else {
                badgeTipo.className = 'badge bg-success fs-6';
                badgeTipo.textContent = '➡️ DIRECTO';
            }
        }
        
        const badgeNombre = document.getElementById('badge_nombre_val');
        if (badgeNombre) {
            badgeNombre.textContent = match.RUT_ORIGEN + ' ➔ ' + match.RUT_DESTINO;
        }

        const lblComision = document.getElementById('lbl_comision_pct');
        const pct = parseFloat(match.COMISION_VALOR || 10.0);
        if (lblComision) {
            lblComision.textContent = pct.toFixed(1);
        }
        
        calcularCalculosValores();
    } else {
        alert("No se encontró una ruta configurada para este trayecto.");
        rutaIdInput.value = '';
        infoRuta.classList.add('d-none');
    }
}

function vincularBusquedaClienteVal(inputId, nomId, apeId, telId) {
    const el = document.getElementById(inputId);
    if (!el) return;
    el.addEventListener('change', function() {
        const id = this.value.trim();
        if (id.length >= 4) {
            fetch('/suramerica2030/valores/buscarCliente?id=' + encodeURIComponent(id))
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const nom = document.getElementById(nomId);
                    const ape = document.getElementById(apeId);
                    const tel = document.getElementById(telId);
                    if (nom) { nom.value = res.data.CLI_NOMBRE || ''; nom.dispatchEvent(new Event('input', { bubbles: true })); }
                    if (ape) { ape.value = res.data.CLI_APELLIDO || ''; ape.dispatchEvent(new Event('input', { bubbles: true })); }
                    if (tel) { tel.value = res.data.CLI_TELEFONO || ''; }
                }
            })
            .catch(() => {});
        }
    });
}

function calcularCalculosValores() {
    const rutaIdEl = document.getElementById('ruta_id');
    const rutas = (window.VALORES_CONFIG && window.VALORES_CONFIG.rutas) ? window.VALORES_CONFIG.rutas : [];
    const ruta = rutaIdEl ? rutas.find(r => r.ID_RUTA == rutaIdEl.value) : null;
    
    const pctComision = parseFloat(ruta ? ruta.COMISION_VALOR : 10.0) || 10.0;
    const montoEnviar = parseFloat((document.getElementById('monto_enviar') || {}).value) || 0;
    
    const comisionCalculada = (montoEnviar * pctComision) / 100;
    
    const comEl = document.getElementById('comision');
    const totEl = document.getElementById('monto_total');
    const lblPorc = document.getElementById('lbl_porcentaje');

    if (comEl) comEl.value = comisionCalculada.toFixed(2);
    if (totEl) totEl.value = (montoEnviar + comisionCalculada).toFixed(2);
    if (lblPorc) lblPorc.innerText = "Comisión de Ruta: " + pctComision.toFixed(1) + "%";
    
    balancearMixtoVal('total');
}

function recalcularTotalManual() {
    const montoEnviar = parseFloat((document.getElementById('monto_enviar') || {}).value) || 0;
    const comision = parseFloat((document.getElementById('comision') || {}).value) || 0;
    const totEl = document.getElementById('monto_total');
    if (totEl) totEl.value = (montoEnviar + comision).toFixed(2);
    balancearMixtoVal('total');
}

// ==========================================
// PAGO MIXTO REUTILIZABLE CON Validador
// ==========================================
function togglePagoMixtoVal() {
    const sel = document.getElementById('metodo_pago_val');
    const div = document.getElementById('div_pago_mixto_val');
    if (!sel || !div) return;
    if (sel.value === 'MIXTO') {
        div.classList.remove('d-none');
        balancearMixtoVal('init');
    } else {
        div.classList.add('d-none');
    }
}

function balancearMixtoVal(origen) {
    if (window.Validador && typeof window.Validador.balancearPagoMixto === 'function') {
        window.Validador.balancearPagoMixto('monto_total', 'monto_efectivo_val', 'monto_transferencia_val', 'badge_check_mixto_val', origen);
    }
}

function validarEnvioValor(form) {
    const sel = document.getElementById('metodo_pago_val');
    if (sel && sel.value === 'MIXTO') {
        if (window.Validador && typeof window.Validador.validarPagoMixto === 'function') {
            const res = window.Validador.validarPagoMixto('monto_total', 'monto_efectivo_val', 'monto_transferencia_val');
            if (!res.valido) {
                alert(res.mensaje);
                return false;
            }
        }
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    inicializarOrigenesVal();
    vincularBusquedaClienteVal('remitente_id_v', 'remitente_nombre_v', 'remitente_apellido_v', 'remitente_telefono_v');
    vincularBusquedaClienteVal('destinatario_id_v', 'destinatario_nombre_v', 'destinatario_apellido_v', 'destinatario_telefono_v');
});
