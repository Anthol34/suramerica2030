<?php
// Si no viene del layout global, proveer el encabezado HTML básico para soporte standalone en móviles
$standalone = !isset($vista);
?>
<?php if ($standalone): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lector QR de Facturas - Suramerica 2030</title>
    <link rel="icon" type="image/jpeg" href="/suramerica2030/public/assets/img/icon.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="/suramerica2030/public/assets/js/html5-qrcode.min.js"></script>
    <script>
        if (typeof Html5Qrcode === "undefined") {
            document.write('<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"><\/script>');
        }
    </script>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/escanear.css">
</head>
<body class="p-2 p-md-4 standalone-scanner">
<?php endif; ?>

<?php if (!$standalone): ?>
<link rel="stylesheet" href="/suramerica2030/public/assets/css/escanear.css">
<?php endif; ?>

<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">

            <!-- Encabezado -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="fw-bold mb-0 <?= $standalone ? 'text-white' : 'text-dark' ?>">
                        📷 Lector QR de Facturas
                    </h3>
                    <p class="small text-muted mb-0">Escanea el código QR de un boleto o factura usando la cámara de tu celular o laptop.</p>
                </div>
                <?php if ($standalone): ?>
                    <a href="/suramerica2030/dashboard" class="btn btn-outline-light btn-sm">🏠 Ir al Sistema</a>
                <?php else: ?>
                    <span class="badge bg-primary px-3 py-2">Cámara Web / Móvil</span>
                <?php endif; ?>
            </div>

            <!-- Contenedor Principal del Escáner -->
            <div class="scanner-card p-3 p-md-4 mb-4">
                
                <!-- Selector de Cámara y Controles Rápidos -->
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label small text-light mb-1">📹 Seleccionar Cámara:</label>
                        <select id="camera-select" class="form-select form-select-sm bg-dark text-white border-secondary">
                            <option value="">Detectando cámaras...</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-12 text-md-end mt-2 mt-md-4">
                        <button type="button" id="btn-toggle-camera" class="btn btn-sm btn-outline-info me-1" onclick="toggleScanner()">
                            ⏸ Pausar Cámara
                        </button>
                        <button type="button" id="btn-switch-facing" class="btn btn-sm btn-outline-light me-1" onclick="switchCameraFacing()">
                            🔄 Cambiar Cámara
                        </button>
                        <label class="btn btn-sm btn-outline-warning mb-0" title="Activar cámara del celular para escanear">
                            📷 Tomar Foto <input type="file" id="file-qr-input" accept="image/*" capture="environment" style="display: none;" onchange="scanImageFile(this)">
                        </label>
                    </div>
                </div>

                <!-- Visor de Cámara en Vivo con Retícula -->
                <div class="scanner-viewport-wrapper" id="live-viewport-wrapper">
                    <div id="qr-reader"></div>
                    <div class="scan-reticle" id="scan-reticle">
                        <div class="reticle-corner corner-tl"></div>
                        <div class="reticle-corner corner-tr"></div>
                        <div class="reticle-corner corner-bl"></div>
                        <div class="reticle-corner corner-br"></div>
                        <div class="scan-laser"></div>
                    </div>
                </div>

                <!-- Contenedor Alternativo para Móviles en Red Local HTTP -->
                <div id="http-fallback-container" class="text-center p-4" style="display: none; background: #162032; border-radius: 12px; border: 2px dashed #00f2fe;">
                    <div style="font-size: 50px;">📸</div>
                    <h5 class="fw-bold text-white mt-2">Cámara Móvil Lista para Escaneo</h5>
                    <p class="small text-light text-opacity-75 mb-3" style="max-width: 340px; margin: 0 auto;">
                        Al navegar por red local sin HTTPS, pulsa el botón verde para abrir la cámara de tu celular y enfocar el código QR:
                    </p>
                    <label class="btn btn-success btn-lg fw-bold shadow-lg d-inline-flex align-items-center gap-2 px-4 py-3" style="cursor: pointer; border-radius: 50px; font-size: 1.15rem;">
                        <span style="font-size: 26px;">📷</span> Presiona Aquí para Abrir la Cámara
                        <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="scanImageFile(this)">
                    </label>

                    <div class="mt-4 pt-3 border-top border-secondary text-center">
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#guiaChrome">
                            💡 ¿Deseas activar el video en vivo continuo en Chrome Android? Toca aquí
                        </button>
                        <div class="collapse text-start mt-3 p-3 bg-dark rounded border border-secondary" id="guiaChrome" style="font-size: 13px;">
                            <strong class="text-warning">Guía rápida de 1 minuto en Chrome de tu Celular:</strong>
                            <ol class="ps-3 mt-2 mb-2 text-light">
                                <li>En Chrome de tu celular, abre una pestaña nueva y entra a: <br><code class="text-info fw-bold">chrome://flags</code></li>
                                <li>En la barra de búsqueda superior escribe: <code class="text-white">insecure</code></li>
                                <li>En <b>Insecure origins treated as secure</b>, cambia de <em>Disabled</em> a <b>Enabled</b>.</li>
                                <li>En el recuadro de texto escribe la dirección de tu laptop:<br>
                                    <code class="text-warning fw-bold fs-6">http://<?= $_SERVER['HTTP_HOST'] ?? '192.168.10.151' ?></code>
                                </li>
                                <li>Toca el botón azul <b>Relaunch</b> (Reiniciar) en la parte inferior.</li>
                            </ol>
                            <small class="text-info">¡Listo! Al regresar a esta página, tu celular tendrá la cámara en vivo continua como en la laptop.</small>
                        </div>
                    </div>
                </div>

                <!-- Barra de Estado y Opciones -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top border-secondary small">
                    <div id="scanner-status" class="text-info fw-bold d-flex align-items-center">
                        <span class="spinner-grow spinner-grow-sm text-info me-2" role="status"></span>
                        Buscando código QR en tiempo real...
                    </div>
                    <div class="form-check form-switch text-light mt-2 mt-sm-0">
                        <input class="form-check-input" type="checkbox" id="chk-auto-open">
                        <label class="form-check-label" for="chk-auto-open">Abrir comprobante directamente al detectar QR</label>
                    </div>
                </div>

                <!-- Búsqueda Manual / Fallback -->
                <div class="mt-3 pt-3 border-top border-secondary">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-dark text-white border-secondary">N° Ticket / Enlace:</span>
                        <input type="text" id="manual-ticket-input" class="form-control bg-dark text-white border-secondary" placeholder="Ej: 145 o pegue la URL completa...">
                        <button class="btn btn-primary" type="button" onclick="consultarManual()">🔍 Consultar</button>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Resultados del Comprobante (Oculta por defecto) -->
            <div id="result-container" class="result-card p-3 p-md-4 mb-4" style="display: none;">
                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-4 text-success">✅</span>
                            <h4 class="fw-bold mb-0 text-dark">Comprobante Encontrado</h4>
                            <span id="res-badge-tipo" class="badge-tipo badge-PASAJE">PASAJE</span>
                        </div>
                        <div class="text-muted small mt-1">
                            Ticket: <strong class="text-dark" id="res-ticket-num">#000000</strong> | 
                            Fecha: <span id="res-fecha">--</span> <span id="res-hora">--</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Total Cobrado</div>
                        <div class="fs-3 fw-bold text-success" id="res-total">$0.00</div>
                        <small class="text-muted" id="res-metodo">Efectivo</small>
                    </div>
                </div>

                <!-- Detalles Rápidos del Comprobante -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-2 rounded bg-light border">
                            <small class="text-muted d-block fw-semibold">👤 CLIENTE / TITULAR</small>
                            <span class="fw-bold text-dark" id="res-cliente">--</span>
                            <div class="small text-muted">CI/DOC: <span id="res-doc">--</span> | Telf: <span id="res-telf">--</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded bg-light border">
                            <small class="text-muted d-block fw-semibold">📍 RUTA / TRAYECTO</small>
                            <span class="fw-bold text-dark"><span id="res-origen">--</span> ➔ <span id="res-destino">--</span></span>
                            <div class="small text-muted" id="res-extra-info">--</div>
                        </div>
                    </div>
                    <div class="col-md-12" id="res-detalle-especifico-container" style="display: none;">
                        <div class="p-2 rounded bg-light border" id="res-detalle-especifico"></div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="small text-muted">
                        Agencia: <strong id="res-sucursal">--</strong> | Cajero: <span id="res-cajero">--</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="reanudarEscaneo()">
                            🔄 Escanear Siguiente
                        </button>
                        <a id="btn-reimprimir" href="#" target="_blank" class="btn btn-outline-dark">
                            🖨️ Imprimir
                        </a>
                        <a id="btn-ver-factura" href="#" target="_blank" class="btn btn-primary fw-bold px-4" style="background-color: #0b2b56; border: none;">
                            👁️ Ver Factura Completa
                        </a>
                    </div>
                </div>
            </div>

            <!-- Historial de Escaneos de la Sesión -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                    <strong class="text-dark">📋 Historial de Escaneos (Esta Sesión)</strong>
                    <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none" onclick="limpiarHistorial()">Borrar Historial</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0" id="tabla-historial">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Hora</th>
                                    <th>N° Ticket</th>
                                    <th>Tipo</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th class="text-end pe-3">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="historial-tbody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Aún no has escaneado ningún comprobante.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Variables de control del escáner
    let html5QrCode = null;
    let isScanning = false;
    let currentCameraId = null;
    let cameraList = [];
    let currentFacingMode = "environment"; // Por defecto cámara trasera en móviles
    let lastScannedText = null;
    let scanCooldown = false;

    // Preferencia de apertura automática
    const chkAutoOpen = document.getElementById('chk-auto-open');
    if (localStorage.getItem('qr_auto_open') === 'true') {
        chkAutoOpen.checked = true;
    }
    chkAutoOpen.addEventListener('change', function() {
        localStorage.setItem('qr_auto_open', this.checked ? 'true' : 'false');
    });

    // Sonido de confirmación con Web Audio API (cross-browser sin archivos externos)
    function playBeep() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                const ctx = new AudioContext();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = "sine";
                osc.frequency.setValueAtTime(880, ctx.currentTime); // 880 Hz
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            }
        } catch (e) {
            console.warn("Audio Context error:", e);
        }

        // Vibración háptica en celulares
        if (navigator.vibrate) {
            try { navigator.vibrate([80, 40, 80]); } catch(e){}
        }
    }

    // Inicialización del escáner al cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        iniciarLectorQR();
    });

    function setScannerStatus(msg, isSuccess = false, isError = false) {
        const el = document.getElementById('scanner-status');
        if (!el) return;
        el.className = isSuccess ? 'text-success fw-bold d-flex align-items-center' :
                       isError ? 'text-danger fw-bold d-flex align-items-center' :
                       'text-info fw-bold d-flex align-items-center';
        
        let icon = isSuccess ? '✔ ' : isError ? '❌ ' : '<span class="spinner-grow spinner-grow-sm text-info me-2" role="status"></span>';
        el.innerHTML = icon + msg;
    }

    async function iniciarLectorQR() {
        if (typeof Html5Qrcode === "undefined") {
            setScannerStatus("Cargando librería de escaneo...", false, false);
            try {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            } catch(e) {
                setScannerStatus("Librería de escaneo no disponible. Verifica la conexión a internet.", false, true);
                return;
            }
        }

        html5QrCode = new Html5Qrcode("qr-reader");

        // Detección proactiva: Si es celular en red local HTTP (sin HTTPS ni localhost)
        const esSeguro = window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
        if (!esSeguro && /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)) {
            activarModoCapturaDirecta("Cámara móvil lista para escaneo directo.");
            return;
        }

        try {
            const devices = await Html5Qrcode.getCameras();
            const select = document.getElementById('camera-select');
            select.innerHTML = '';

            if (devices && devices.length > 0) {
                cameraList = devices;
                devices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.id;
                    option.text = device.label || `Cámara ${index + 1}`;
                    select.appendChild(option);
                });

                // Seleccionar cámara trasera por defecto en móviles si se identifica en el label
                const backCam = devices.find(d => /back|rear|trasera|environment/i.test(d.label));
                currentCameraId = backCam ? backCam.id : devices[0].id;
                select.value = currentCameraId;

                select.addEventListener('change', function() {
                    currentCameraId = this.value;
                    reiniciarEscanerConCamara(currentCameraId);
                });

                comenzarEscaneo({ deviceId: { exact: currentCameraId } });
            } else {
                // Si no hay lista explícita, intentar con facingMode
                comenzarEscaneo({ facingMode: currentFacingMode });
            }
        } catch (err) {
            console.warn("Error obteniendo cámaras, intentando modo genérico:", err);
            // Intentar con facingMode environment
            comenzarEscaneo({ facingMode: "environment" });
        }
    }

    function activarModoCapturaDirecta(msg) {
        isScanning = false;
        const liveWrapper = document.getElementById('live-viewport-wrapper');
        const fallbackBox = document.getElementById('http-fallback-container');
        if (liveWrapper) liveWrapper.style.display = 'none';
        if (fallbackBox) fallbackBox.style.display = 'block';
        const btnToggle = document.getElementById('btn-toggle-camera');
        const btnSwitch = document.getElementById('btn-switch-facing');
        if (btnToggle) btnToggle.style.display = 'none';
        if (btnSwitch) btnSwitch.style.display = 'none';
        setScannerStatus(msg || "Cámara de celular lista. Presiona el botón verde.", true);
    }

    function comenzarEscaneo(cameraConfig) {
        if (!html5QrCode) return;

        const config = {
            fps: 12,
            qrbox: { width: 220, height: 220 },
            aspectRatio: 1.0
        };

        setScannerStatus("Iniciando cámara...");

        html5QrCode.start(
            cameraConfig,
            config,
            onScanSuccess,
            onScanFailure
        ).then(() => {
            isScanning = true;
            document.getElementById('btn-toggle-camera').textContent = "⏸ Pausar Cámara";
            document.getElementById('scan-reticle').style.display = "block";
            setScannerStatus("Buscando código QR en tiempo real...");
        }).catch(err => {
            console.error("Error al iniciar cámara:", err);
            isScanning = false;
            document.getElementById('scan-reticle').style.display = "none";
            document.getElementById('btn-toggle-camera').textContent = "▶ Reanudar Cámara";
            
            if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                activarModoCapturaDirecta("Cámara de celular lista en modo captura directa.");
            } else if (err && err.name === 'NotAllowedError') {
                setScannerStatus("Permiso de cámara denegado en el navegador.", false, true);
            } else {
                setScannerStatus("No se pudo iniciar video continuo. Usa el botón 'Tomar Foto'.", false, true);
            }
        });
    }

    function toggleScanner() {
        if (isScanning) {
            html5QrCode.stop().then(() => {
                isScanning = false;
                document.getElementById('scan-reticle').style.display = "none";
                document.getElementById('btn-toggle-camera').textContent = "▶ Reanudar Cámara";
                setScannerStatus("Cámara pausada.");
            });
        } else {
            if (currentCameraId) {
                comenzarEscaneo({ deviceId: { exact: currentCameraId } });
            } else {
                comenzarEscaneo({ facingMode: currentFacingMode });
            }
        }
    }

    function switchCameraFacing() {
        if (!isScanning) return;
        currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
        html5QrCode.stop().then(() => {
            comenzarEscaneo({ facingMode: currentFacingMode });
        });
    }

    function reiniciarEscanerConCamara(cameraId) {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().then(() => {
                comenzarEscaneo({ deviceId: { exact: cameraId } });
            });
        }
    }

    // Callback de fallo continuo de frame (no es un error crítico)
    function onScanFailure(error) {
        // Ignoramos errores de frame vacío
    }

    // Callback de éxito cuando se decodifica un QR
    function onScanSuccess(decodedText, decodedResult) {
        if (scanCooldown) return;
        
        console.log("QR Detectado:", decodedText);
        playBeep();
        
        scanCooldown = true;
        setScannerStatus("¡Código QR detectado!", true, false);

        procesarCodigoEscaneado(decodedText);

        // Enfriamiento de 2 segundos para no disparar lecturas duplicadas continuas
        setTimeout(() => { scanCooldown = false; }, 2000);
    }

    // Procesa el texto/URL obtenido del QR
    function procesarCodigoEscaneado(texto) {
        lastScannedText = texto;
        let idFactura = extraerIdFactura(texto);

        if (idFactura) {
            consultarDetalleFactura(idFactura, texto);
        } else {
            // Si es una URL pero no de este sistema
            if (texto.startsWith('http://') || texto.startsWith('https://')) {
                if (confirm(`El código QR contiene este enlace:\n\n${texto}\n\n¿Deseas abrirlo?`)) {
                    window.open(texto, '_blank');
                }
            } else {
                alert(`Contenido del QR escaneado:\n\n${texto}`);
            }
        }
    }

    // Extrae el ID de la factura de diferentes formatos posibles
    function extraerIdFactura(cadena) {
        if (!cadena) return null;
        cadena = cadena.trim();

        // 1. Formato URL nueva: /ventas/ticket/123 o https://.../ventas/ticket/123?view=1
        const urlMatch = cadena.match(/ventas\/ticket\/(\d+)/i);
        if (urlMatch) return urlMatch[1];

        // 2. Formato antiguo: "TICKET: #000123"
        const ticketMatch = cadena.match(/TICKET:\s*#?(\d+)/i);
        if (ticketMatch) return parseInt(ticketMatch[1], 10);

        // 3. Sólo dígitos numéricos directos (ej: "123" o "#123")
        const numMatch = cadena.match(/^#?(\d+)$/);
        if (numMatch) return parseInt(numMatch[1], 10);

        return null;
    }

    // Consulta AJAX para obtener los datos de la factura
    function consultarDetalleFactura(id, textoOriginal) {
        setScannerStatus(`Cargando información del Ticket #${id}...`);

        fetch(`/suramerica2030/ventas/consultar/${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    mostrarResultadoFactura(res.data);
                    agregarAlHistorial(res.data);
                    setScannerStatus(`Ticket #${res.data.NUM_TICKET} verificado exitosamente.`, true);

                    // Si el usuario tiene activa la opción de apertura directa
                    if (chkAutoOpen.checked) {
                        window.open(res.data.URL_TICKET, '_blank');
                    }
                } else {
                    setScannerStatus(res.message || "Comprobante no encontrado", false, true);
                    alert(res.message || "El comprobante no fue encontrado en la base de datos.");
                }
            })
            .catch(err => {
                console.error("Error consultando factura:", err);
                setScannerStatus("Error de conexión al consultar el comprobante.", false, true);
            });
    }

    // Renderiza la tarjeta visual del comprobante
    function mostrarResultadoFactura(data) {
        document.getElementById('res-ticket-num').textContent = '#' + data.NUM_TICKET;
        document.getElementById('res-fecha').textContent = data.FA_FECHA;
        document.getElementById('res-hora').textContent = data.FA_HORA;
        document.getElementById('res-total').textContent = '$' + parseFloat(data.FA_TOTAL).toFixed(2);
        let metStr = data.FA_METODO || 'Efectivo';
        if (data.DESGLOSE_PAGOS && data.DESGLOSE_PAGOS.length > 1) {
            metStr += ' (' + data.DESGLOSE_PAGOS.map(p => p.METODO_PAGO + ': $' + parseFloat(p.MONTO).toFixed(2)).join(' + ') + ')';
        }
        document.getElementById('res-metodo').textContent = 'Método: ' + metStr;
        document.getElementById('res-cliente').textContent = data.CLIENTE;
        document.getElementById('res-doc').textContent = data.ID_CLIENTE;
        document.getElementById('res-telf').textContent = data.TELEFONO;
        document.getElementById('res-origen').textContent = data.ORIGEN;
        document.getElementById('res-destino').textContent = data.DESTINO;
        document.getElementById('res-sucursal').textContent = data.SUCURSAL;
        document.getElementById('res-cajero').textContent = data.CAJERO;

        // Badge de Tipo
        const badge = document.getElementById('res-badge-tipo');
        badge.textContent = data.FA_TIPO;
        badge.className = 'badge-tipo badge-' + (data.FA_TIPO.replace(/\s+/g, ''));

        // Información extra según el tipo
        let extra = '';
        const espCont = document.getElementById('res-detalle-especifico-container');
        const esp = document.getElementById('res-detalle-especifico');

        if (data.FA_TIPO === 'PASAJE' || data.FA_TIPO === 'RESERVA INICIAL') {
            extra = `Viaje: ${data.FECHA_VIAJE || ''} ${data.RUT_SAHORA || ''} | Bus: [${data.BUS_PLACA || 'S/P'}]`;
            if (data.PASAJEROS && data.PASAJEROS.length > 0) {
                espCont.style.display = 'block';
                let paxHtml = `<div class="mb-2"><span class="badge bg-dark fs-6">Asientos Asignados: ${data.ASIENTO || ''}</span></div>`;
                paxHtml += `<div class="table-responsive"><table class="table table-sm table-bordered mb-0 bg-white" style="font-size: 12px;">
                    <thead class="table-light"><tr><th>#</th><th>Asiento</th><th>Pasajero</th><th>Documento</th><th>Teléfono</th></tr></thead><tbody>`;
                data.PASAJEROS.forEach((p, idx) => {
                    paxHtml += `<tr>
                        <td><strong>${idx + 1}</strong></td>
                        <td><span class="badge bg-primary">Asiento ${p.PAX_ASIENTO || 'S/A'}</span></td>
                        <td><strong>${p.CLI_NOMBRE || ''} ${p.CLI_APELLIDO || ''}</strong></td>
                        <td>${p.ID_CLIENTE || ''}</td>
                        <td>${p.CLI_TELEFONO || '-'}</td>
                    </tr>`;
                });
                paxHtml += `</tbody></table></div>`;
                esp.innerHTML = paxHtml;
            } else if (data.ASIENTO) {
                espCont.style.display = 'block';
                esp.innerHTML = `<span class="badge bg-dark fs-6 me-2">Asientos: ${data.ASIENTO}</span>`;
            } else {
                espCont.style.display = 'none';
            }
        } else if (data.FA_TIPO === 'ENCOMIENDA') {
            extra = `Paquete(s): ${data.ENC_CANTIDAD || 1} ${data.TIPO_PAQUETE || ''} | Peso: ${data.ENC_PESO || 0} Kg`;
            let infoEnc = '';
            if (data.ENC_DESTINATARIO) {
                infoEnc += `<div><strong>Destinatario que recibe:</strong> ${data.ENC_DESTINATARIO} ${data.ENC_DES_CI ? '(Doc: ' + data.ENC_DES_CI + ')' : ''}</div>`;
            }
            if (data.ENC_CONTENIDO) {
                infoEnc += `<div><strong>Contenido:</strong> ${data.ENC_CONTENIDO}</div>`;
            }
            if (infoEnc) {
                espCont.style.display = 'block';
                esp.innerHTML = infoEnc;
            } else {
                espCont.style.display = 'none';
            }
        } else if (data.FA_TIPO === 'VALOR') {
            extra = `Envío de dinero seguro`;
            if (data.VAL_DESTINATARIO) {
                espCont.style.display = 'block';
                esp.innerHTML = `<strong>Destinatario que retira:</strong> ${data.VAL_DESTINATARIO}`;
            } else {
                espCont.style.display = 'none';
            }
        } else if (data.FA_TIPO === 'SOBREPESO') {
            extra = `Cobro por exceso de equipaje (${data.FA_DETALLE || 'Sobrepeso'})`;
            espCont.style.display = 'none';
        } else {
            espCont.style.display = 'none';
        }
        document.getElementById('res-extra-info').textContent = extra;

        // Botones
        document.getElementById('btn-ver-factura').href = data.URL_TICKET;
        document.getElementById('btn-reimprimir').href = `/suramerica2030/ventas/ticket/${data.ID_FACTURA}`;

        // Mostrar contenedor
        const cont = document.getElementById('result-container');
        cont.style.display = 'block';
        cont.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function reanudarEscaneo() {
        document.getElementById('result-container').style.display = 'none';
        scanCooldown = false;
        setScannerStatus("Buscando código QR en tiempo real...");
        if (!isScanning) {
            toggleScanner();
        }
    }

    // Búsqueda Manual
    function consultarManual() {
        const val = document.getElementById('manual-ticket-input').value.trim();
        if (!val) {
            alert("Por favor ingresa un N° de Ticket o pega el enlace.");
            return;
        }
        procesarCodigoEscaneado(val);
    }

    // Escanear archivo de imagen cargado (fallback perfecto para celulares sin HTTPS)
    function scanImageFile(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];

        setScannerStatus("Procesando imagen capturada...");

        procesarImagenQR(file, html5QrCode, 
            (decodedText) => {
                onScanSuccess(decodedText, null);
                input.value = '';
            },
            (err) => {
                console.warn("No se detectó QR en la imagen:", err);
                setScannerStatus("No se encontró ningún código QR legible en la foto. Intenta con una toma más cercana y enfocada.", false, true);
                alert("No se pudo detectar un código QR claro en la foto capturada. Asegúrate de enfocar bien el cuadro del QR e intenta de nuevo.");
                input.value = '';
            }
        );
    }

    // Optimiza y escala fotos de alta resolución tomadas por smartphones para decodificación instantánea
    function procesarImagenQR(file, scannerObj, onSuccess, onError) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const maxDim = 1200;
                let w = img.width;
                let h = img.height;
                if (w > maxDim || h > maxDim) {
                    if (w > h) {
                        h = Math.round((h * maxDim) / w);
                        w = maxDim;
                    } else {
                        w = Math.round((w * maxDim) / h);
                        h = maxDim;
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    canvas.toBlob(function(blob) {
                        const optimizedFile = new File([blob], "scan.jpg", { type: "image/jpeg" });
                        scannerObj.scanFile(optimizedFile, false)
                            .then(onSuccess)
                            .catch(() => {
                                scannerObj.scanFile(file, false).then(onSuccess).catch(onError);
                            });
                    }, "image/jpeg", 0.92);
                } else {
                    scannerObj.scanFile(file, false).then(onSuccess).catch(onError);
                }
            };
            img.onerror = () => scannerObj.scanFile(file, false).then(onSuccess).catch(onError);
            img.src = e.target.result;
        };
        reader.onerror = () => scannerObj.scanFile(file, false).then(onSuccess).catch(onError);
        reader.readAsDataURL(file);
    }

    // Gestión del Historial en Sesión
    function agregarAlHistorial(data) {
        const tbody = document.getElementById('historial-tbody');
        const emptyRow = tbody.querySelector('td[colspan="6"]');
        if (emptyRow) {
            tbody.innerHTML = '';
        }

        const now = new Date();
        const horaStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-3 text-muted small">${horaStr}</td>
            <td class="fw-bold">#${data.NUM_TICKET}</td>
            <td><span class="badge bg-secondary">${data.FA_TIPO}</span></td>
            <td>${data.CLIENTE}</td>
            <td class="fw-bold text-success">$${parseFloat(data.FA_TOTAL).toFixed(2)}</td>
            <td class="text-end pe-3">
                <a href="${data.URL_TICKET}" target="_blank" class="btn btn-sm btn-outline-primary py-0">Ver</a>
            </td>
        `;
        tbody.insertBefore(tr, tbody.firstChild);
    }

    function limpiarHistorial() {
        document.getElementById('historial-tbody').innerHTML = `
            <tr><td colspan="6" class="text-center text-muted py-3">Aún no has escaneado ningún comprobante.</td></tr>
        `;
    }
</script>

<?php if ($standalone): ?>
</body>
</html>
<?php endif; ?>
