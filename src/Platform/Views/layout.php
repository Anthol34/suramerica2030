<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= isset($titulo) ? $titulo : 'Suramerica 2030 S.A.' ?></title>
    <link rel="icon" type="image/jpeg" href="/suramerica2030/public/assets/img/icon.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php 
        $vLayoutCss = @filemtime(__DIR__ . '/../../../public/assets/css/layout.css') ?: time();
        $vLiveDataCss = @filemtime(__DIR__ . '/../../../public/assets/css/live-data.css') ?: time();
    ?>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/layout.css?v=<?= $vLayoutCss ?>">
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/live-data.css?v=<?= $vLiveDataCss ?>">
</head>
<body class="bg-light position-relative">

<!-- Contenedor Universal de Notificaciones Toast Reactivas (LiveData) -->
<div id="liveDataToastContainer"></div>


<!-- Notificación Global de Actualización de Perfil -->
<?php if(isset($_GET['status']) && $_GET['status'] == 'success_perfil'): ?>
    <div class="alert alert-success alert-dismissible fade show position-absolute top-0 end-0 m-4 shadow" style="z-index: 1055;">
        ✅ <strong>¡Perfil Actualizado!</strong> Tus datos se guardaron correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['status']) && $_GET['status'] == 'error_perfil'): ?>
    <div class="alert alert-danger alert-dismissible fade show position-absolute top-0 end-0 m-4 shadow" style="z-index: 1055;">
        ❌ <strong>Error:</strong> <?= htmlspecialchars($_GET['msg'] ?? 'No se pudo actualizar el perfil.') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isDashboard = (strpos($currentUri, '/dashboard') !== false || $currentUri === '/suramerica2030/' || $currentUri === '/suramerica2030');
$isPasajes = (strpos($currentUri, '/pasajes') !== false);
$isEquipaje = ($isPasajes && (strpos($currentUri, 'tab=equipaje') !== false));
$isPasajesSolo = ($isPasajes && !$isEquipaje);
$isEncomiendas = (strpos($currentUri, '/encomiendas') !== false);
$isValores = (strpos($currentUri, '/valores') !== false);
$isCaja = (strpos($currentUri, '/caja') !== false);
$isManifiesto = (strpos($currentUri, '/manifiesto') !== false);
$isFlota = (strpos($currentUri, '/flota') !== false);
$isFinanzas = (strpos($currentUri, '/finanzas') !== false);
$isUsuarios = (strpos($currentUri, '/usuarios') !== false);

// Título amigable para la barra móvil
$tituloPagina = 'Inicio';
if (isset($titulo)) {
    $parts = explode(' - ', $titulo);
    $cleanTitle = trim($parts[0] ?? '');
    if ($cleanTitle === 'Panel de Control') {
        $tituloPagina = 'Inicio';
    } elseif ($cleanTitle !== '') {
        $tituloPagina = $cleanTitle;
    }
}
?>

<!-- BARRA SUPERIOR PARA MÓVILES (SOLO CELULARES) -->
<header class="mobile-topbar d-md-none">
    <button type="button" class="btn-hamburger" id="btnToggleSidebar" aria-label="Abrir menú de navegación">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
    </button>
    <h1 class="mobile-page-title mb-0"><?= htmlspecialchars($tituloPagina) ?></h1>
</header>

<!-- BACKDROP / TELÓN OSCURO PARA CERRAR EL MENÚ EN CELULAR -->
<div class="sidebar-backdrop d-md-none" id="sidebarBackdrop"></div>

<div class="container-fluid">
    <div class="row">
        <!-- BARRA LATERAL (MENU) -->
        <nav class="col-md-3 col-lg-2 sidebar p-3 d-flex flex-column justify-content-between" id="sidebarNav">
            <div>
                <!-- Encabezado Móvil (SURAMERICA 2030 + Versión Móvil ERP + botón cerrar) -->
                <div class="mobile-drawer-brand d-md-none mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-white fs-5" style="letter-spacing: 0.5px;">SURAMERICA 2030</div>
                            <div style="font-size: 0.75rem; color: #ff3366; font-weight: 600; text-transform: none; margin-top: 2px;">Versión Móvil ERP</div>
                        </div>
                        <button type="button" class="btn-close-drawer" id="btnCloseSidebar" aria-label="Cerrar menú">✕</button>
                    </div>
                </div>

                <!-- Encabezado Escritorio (Logo original y versión sin alterar) -->
                <div class="brand-logo text-center pb-2 d-none d-md-block">
                    <img src="/suramerica2030/public/assets/img/logo.jpg" alt="Suramerica 2030" style="max-width: 85%; height: auto; border-radius: 8px;" onerror="this.style.display='none'">
                    <div class="text fs-6 font-monospace mt-2" style="font-size: 0.75rem !important; color: #f70d5b;">Versión de Prueba</div>
                </div>
                <hr class="border-secondary mb-3 d-none d-md-block">
                
                <!-- Tarjeta Interactiva del Usuario -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-card" onclick="abrirModalPerfil()" title="Clic para editar tu perfil">
                        <div class="fw-bold text-white text-truncate"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="badge bg-warning text-dark fw-bold"><?= htmlspecialchars($_SESSION['nombre_rol']) ?></span>
                            <small class="text-info fw-bold">📍 <?= htmlspecialchars(strtoupper($_SESSION['ciudad_sucursal'])) ?></small>
                        </div>
                    </div>
                <?php endif; ?>

                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="javascript:void(0)" onclick="abrirModalEscanearQR()" class="nav-link text-white fw-bold d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                            <span>📷 Escanear Factura QR</span>
                            <span class="badge bg-light text-primary" style="font-size: 10px;">CÁMARA</span>
                        </a>
                    </li>
                    <li class="nav-item"><a href="/suramerica2030/dashboard" class="nav-link <?= $isDashboard ? 'active' : '' ?>">🏠 Inicio</a></li>
                    <li class="nav-item"><a href="/suramerica2030/pasajes" class="nav-link <?= $isPasajesSolo ? 'active' : '' ?>">🎫 Venta de Pasajes</a></li>
                    <li class="nav-item"><a href="/suramerica2030/pasajes?tab=equipaje" class="nav-link <?= $isEquipaje ? 'active' : '' ?>">🧳 Equipaje y Sobrepeso</a></li>
                    <li class="nav-item"><a href="/suramerica2030/encomiendas" class="nav-link <?= $isEncomiendas ? 'active' : '' ?>">📦 Gestión de Encomiendas</a></li>
                    <li class="nav-item"><a href="/suramerica2030/valores" class="nav-link <?= $isValores ? 'active' : '' ?>">💲 Envío de Valores</a></li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $isCaja ? 'active' : '' ?> text-white fw-bold d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#menuCaja" role="button" aria-expanded="false" aria-controls="menuCaja">
                            <span>📠 Cuadre de Caja</span>
                            <span style="font-size: 10px;">▼</span>
                        </a>
                        <!-- SUBMENÚ DESPLEGABLE -->
                        <div class="collapse bg-black bg-opacity-25 rounded mt-1 <?= $isCaja ? 'show' : '' ?>" id="menuCaja">
                            <ul class="nav flex-column ms-2 py-1">
                                <li class="nav-item">
                                    <a href="/suramerica2030/caja" class="nav-link text-light py-1" style="font-size: 13px;">
                                        ➔ Operación Diaria (Ventas)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/suramerica2030/caja/movimientos" class="nav-link text-light py-1" style="font-size: 13px;">
                                        ➔ Movimientos de Caja
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/suramerica2030/caja/aprobaciones" class="nav-link text-light py-1 d-flex justify-content-between align-items-center" style="font-size: 13px;">
                                        <span>➔ Por aprobar cajeros</span>
                                        <?php 
                                        if(isset($_SESSION['rol_id']) && in_array($_SESSION['rol_id'], [1, 2])): 
                                            try {
                                                $dbBadge = (new \Platform\Storage\Database())->getConnection();
                                                $cntSolBadge = (int)$dbBadge->query("SELECT COUNT(*) FROM SOLICITUD_ANULACION WHERE ESTADO = 'PENDIENTE'")->fetchColumn();
                                                $cntEgrBadge = (int)$dbBadge->query("SELECT COUNT(*) FROM MOVIMIENTO_CAJA WHERE TIPO_MOVIMIENTO = 'EGRESO' AND ESTADO_APROBACION = 'POR_APROBAR'")->fetchColumn();
                                                $totBadge = $cntSolBadge + $cntEgrBadge;
                                                if($totBadge > 0):
                                        ?>
                                            <span class="badge bg-danger rounded-pill" style="font-size: 10px;"><?= $totBadge ?></span>
                                        <?php 
                                                endif;
                                            } catch (\Exception $e) {}
                                        endif; 
                                        ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/suramerica2030/caja/historial_cierres" class="nav-link text-light py-1" style="font-size: 13px;">
                                        ➔ Cierres de Caja
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/suramerica2030/caja/depositos" class="nav-link text-light py-1" style="font-size: 13px;">
                                        ➔ Depósitos
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item"><a href="/suramerica2030/manifiesto" class="nav-link <?= $isManifiesto ? 'active' : '' ?>">📄 Manifiestos</a></li>
                    
                    <?php if ((isset($_SESSION['p_flota']) && $_SESSION['p_flota'] == 1) || (isset($_SESSION['p_usuarios']) && $_SESSION['p_usuarios'] == 1) || (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1)): ?>
                        <div class="border-top border-secondary mt-3 pt-2"></div>
                        <div class="text-uppercase fw-bold px-2 mb-2" style="font-size: 0.72rem; letter-spacing: 1px; color: #8da2be;">ADMINISTRACIÓN</div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['p_flota']) && $_SESSION['p_flota'] == 1): ?>
                    <li class="nav-item"><a href="/suramerica2030/flota" class="nav-link text-warning mb-1 <?= $isFlota ? 'active' : '' ?>">🚌 Gestión de Flota y Rutas</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1): ?>
                    <li class="nav-item"><a href="/suramerica2030/finanzas" class="nav-link text-warning mb-1 <?= $isFinanzas ? 'active' : '' ?>">📈 Finanzas y Comisiones</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['p_usuarios']) && $_SESSION['p_usuarios'] == 1): ?>
                    <li class="nav-item"><a href="/suramerica2030/usuarios" class="nav-link text-warning <?= $isUsuarios ? 'active' : '' ?>">👥 Usuarios y Accesos</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="pt-3 border-top border-secondary">
                <a href="/suramerica2030/auth/logout" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">🚪 Cerrar Sesión</a>
            </div>
        </nav>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="col-md-9 col-lg-10 p-4 main-content <?= ($isDashboard ?? false) ? 'is-dashboard' : '' ?>">
            <?php 
                if (isset($vista) && file_exists($vista)) {
                    require_once $vista;
                } else {
                    echo "<div class='alert alert-danger'>Error: La vista '{$vista}' no existe.</div>";
                }
            ?>
        </main>
    </div>
</div>


<!-- ========================================== -->
<!-- MODAL DINÁMICO: MI PERFIL DE USUARIO       -->
<!-- ========================================== -->
<div class="modal fade" id="modalMiPerfil" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title">Configuración de Perfil</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/suramerica2030/auth/actualizarPerfil" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" id="perfil_nombre" class="form-control fw-bold" required>
                    </div>
                    <div class="mb-3">
                        <label>Usuario (Login)</label>
                        <input type="text" id="perfil_usuario" class="form-control bg-light text-primary fw-bold" readonly>
                        <small class="text-muted" style="font-size: 11px;">El nombre de usuario no es modificable.</small>
                    </div>
                    <div class="mb-3">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" id="perfil_email" class="form-control" required>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <label class="text-danger fw-bold">Cambiar Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="Nueva clave (Opcional)">
                        <small class="text-muted" style="font-size: 11px;">Déjalo en blanco si deseas mantener tu contraseña actual.</small>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Actualizar Mis Datos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODALES AUTOMÁTICOS DE IMPRESIÓN (TICKETS Y DESPACHOS) -->
<?php if (isset($_GET['print_ticket']) || isset($_GET['print_baggage'])): ?>
<div class="modal fade" id="modalTicketExito" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <div class="text-success mb-2" style="font-size: 3.5rem;">✅</div>
                <h5 class="fw-bold text-dark mb-3">¡Operación Exitosa!</h5>
                
                <?php if(isset($_GET['print_ticket']) && isset($_GET['print_baggage'])): ?>
                    <p class="text-muted mb-4" style="font-size: 0.9rem;">Se registró el peso y se generó el cobro del sobrepeso.</p>
                <?php elseif(isset($_GET['print_baggage'])): ?>
                    <p class="text-muted mb-4" style="font-size: 0.9rem;">Equipaje registrado. No excedió el límite.</p>
                <?php else: ?>
                    <p class="text-muted mb-4" style="font-size: 0.9rem;">La transacción se procesó correctamente.</p>
                <?php endif; ?>
                
                <?php if (isset($_GET['print_ticket'])): ?>
                <a href="/suramerica2030/ventas/ticket/<?= intval($_GET['print_ticket']) ?>" target="_blank" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm mb-2" style="background-color: #0b2b56; border: none;" onclick="setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('modalTicketExito')).hide(), 100);">
                    🖨️ Imprimir Recibo
                </a>
                <?php endif; ?>

                <?php if (isset($_GET['print_baggage'])): ?>
                <a href="/suramerica2030/pasajes/imprimirEquipaje?id=<?= intval($_GET['print_baggage']) ?>" target="_blank" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm mb-2 text-dark" style="border: none;" onclick="setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('modalTicketExito')).hide(), 100);">
                    🧳 Etiqueta de Equipaje
                </a>
                <?php endif; ?>
                
                <button type="button" class="btn btn-link text-muted mt-2 text-decoration-none w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_GET['print_despacho'])): ?>
<div class="modal fade" id="modalDespachoExito" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <div class="text-success mb-2" style="font-size: 3.5rem;">✅</div>
                <h5 class="fw-bold text-dark mb-3">¡Despacho Exitoso!</h5>
                <p class="text-muted mb-4" style="font-size: 0.9rem;">Los paquetes fueron asignados al viaje seleccionado.</p>
                
                <a href="/suramerica2030/encomiendas/imprimirLista?fecha=<?= htmlspecialchars($_GET['fecha_d'] ?? '') ?>&ruta_id=<?= htmlspecialchars($_GET['ruta_d'] ?? '') ?>" target="_blank" class="btn btn-success btn-lg w-100 fw-bold shadow-sm mb-2" style="border: none;" onclick="setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('modalDespachoExito')).hide(), 100);">
                    🖨️ Imprimir Manifiesto
                </a>
                <button type="button" class="btn btn-link text-muted mt-2 text-decoration-none w-100" data-bs-dismiss="modal">Continuar sin imprimir</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- BOTÓN FLOTANTE PARA MÓVILES (ACCESO RÁPIDO A CÁMARA) -->
<div class="d-md-none position-fixed bottom-0 end-0 m-3" style="z-index: 1040;">
    <button type="button" class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background-color: #0b2b56; border: 2px solid #fff;" onclick="abrirModalEscanearQR()" title="Escanear Factura con Cámara">
        <span style="font-size: 24px;">📷</span>
    </button>
</div>

<!-- ========================================== -->
<!-- MODAL GLOBAL: ESCÁNER QR CON CÁMARA        -->
<!-- ========================================== -->
<div class="modal fade" id="modalEscanearQR" tabindex="-1" aria-labelledby="modalEscanearQRLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background-color: #1b263b; color: #fff;">
            <div class="modal-header border-secondary py-2">
                <h6 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="modalEscanearQRLabel">
                    <span>📷</span> Lector QR de Comprobantes
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <a href="/suramerica2030/ventas/escanear" class="btn btn-sm btn-outline-info text-decoration-none py-0 px-2" style="font-size: 11px;">
                        ⛶ Pantalla Completa
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3">
                <!-- Visor de la cámara en vivo -->
                <div id="modal-reader-wrapper" style="position: relative; background: #000; border-radius: 8px; overflow: hidden; min-height: 240px; border: 1px solid #415a77;">
                    <div id="modal-qr-reader" style="width: 100%;"></div>
                    <div id="modal-scan-status" class="position-absolute bottom-0 start-0 end-0 p-2 text-center small fw-bold" style="background: rgba(0,0,0,0.7); color: #00f2fe;">
                        Iniciando cámara...
                    </div>
                </div>

                <!-- Contenedor Alternativo para Móviles en Red Local HTTP -->
                <div id="modal-http-fallback" class="p-3 text-center" style="display: none; background: #0f172a; border-radius: 8px; border: 2px dashed #00f2fe;">
                    <div style="font-size: 42px;">📸</div>
                    <h6 class="fw-bold text-white mt-1">Cámara Móvil Lista</h6>
                    <p class="small text-light text-opacity-75 mb-3" style="font-size: 12.5px;">
                        Toca el botón verde para abrir la cámara de tu celular y tomarle foto al código QR:
                    </p>
                    <label class="btn btn-success fw-bold shadow d-inline-flex align-items-center gap-2 px-3 py-2" style="cursor: pointer; border-radius: 30px; font-size: 14.5px;">
                        <span style="font-size: 20px;">📷</span> Abrir Cámara y Tomar Foto
                        <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="modalScanFile(this)">
                    </label>
                    <div class="mt-2 pt-2 border-top border-secondary text-center">
                        <a href="/suramerica2030/ventas/escanear" class="text-info text-decoration-none" style="font-size: 11.5px;">
                            💡 ¿Deseas activar video en vivo continuo en Chrome? Ver guía
                        </a>
                    </div>
                </div>

                <!-- Controles de cámara rápidos -->
                <div class="d-flex justify-content-between align-items-center mt-2 pt-1">
                    <select id="modal-camera-select" class="form-select form-select-sm bg-dark text-white border-secondary w-auto" style="font-size: 12px;">
                        <option value="">Cargando cámaras...</option>
                    </select>
                    <div class="d-flex gap-1">
                        <button type="button" id="modal-btn-flip" class="btn btn-sm btn-outline-light py-1 px-2" style="font-size: 11px;" onclick="modalSwitchFacing()">
                            🔄 Voltear
                        </button>
                        <label class="btn btn-sm btn-outline-warning mb-0 py-1 px-2" style="font-size: 11px;" title="Cargar foto o usar cámara">
                            📷 Tomar Foto <input type="file" id="modal-file-qr" accept="image/*" capture="environment" style="display: none;" onchange="modalScanFile(this)">
                        </label>
                    </div>
                </div>

                <!-- Búsqueda Manual -->
                <div class="input-group input-group-sm mt-3">
                    <input type="text" id="modal-manual-input" class="form-control bg-dark text-white border-secondary" placeholder="N° de Ticket o Enlace QR...">
                    <button class="btn btn-primary" type="button" onclick="modalConsultarManual()">Consultar</button>
                </div>

                <!-- Contenedor de resultado dentro del modal -->
                <div id="modal-qr-result" class="mt-3 p-3 bg-white text-dark rounded shadow-sm" style="display: none;">
                    <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
                        <div>
                            <span id="modal-res-tipo" class="badge bg-primary">PASAJE</span>
                            <strong class="d-block mt-1" id="modal-res-num">#000000</strong>
                        </div>
                        <div class="text-end">
                            <span class="fs-5 fw-bold text-success" id="modal-res-total">$0.00</span>
                            <div class="small text-muted" id="modal-res-metodo">Efectivo</div>
                        </div>
                    </div>
                    <div class="small mb-2">
                        <div><strong>Cliente:</strong> <span id="modal-res-cliente">--</span></div>
                        <div><strong>Ruta:</strong> <span id="modal-res-ruta">--</span></div>
                        <div id="modal-res-extra" class="text-muted small"></div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a id="modal-btn-ver" href="#" target="_blank" class="btn btn-sm btn-primary w-100 fw-bold" style="background-color: #0b2b56; border: none;">
                            👁️ Ver Factura
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="modalReanudar()">
                            🔄 Escanear Otro
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary p-2 d-flex justify-content-between">
                <small class="text-muted" style="font-size: 11px;">Apunta la cámara al QR de la factura.</small>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php 
    $vLiveDataJs = @filemtime(__DIR__ . '/../../../public/assets/js/live-data.js') ?: time();
?>
<script src="/suramerica2030/public/assets/js/live-data.js?v=<?= $vLiveDataJs ?>"></script>
<script src="/suramerica2030/public/assets/js/html5-qrcode.min.js"></script>
<!-- Script Global de Sanitización en Tiempo Real (Mayúsculas sin tilde, Ñ permitida) -->
<?php 
    $vSanitizarJs = @filemtime(__DIR__ . '/../../../public/assets/js/sanitizar-inputs.js') ?: time();
    $vMobileJs = @filemtime(__DIR__ . '/../../../public/assets/js/mobile-nav.js') ?: time();
    $vValidacionesJs = @filemtime(__DIR__ . '/../../../public/assets/js/validaciones.js') ?: time();
?>

<script src="/suramerica2030/public/assets/js/validaciones.js?v=<?= $vValidacionesJs ?>"></script>
<script src="/suramerica2030/public/assets/js/sanitizar-inputs.js?v=<?= $vSanitizarJs ?>"></script>
<!-- Script de Navegación Móvil (Menú Hamburguesa y Drawer Lateral) -->
<script src="/suramerica2030/public/assets/js/mobile-nav.js?v=<?= $vMobileJs ?>"></script>

<!-- LANZADORES DE MODALES AUTOMÁTICOS -->
<?php if (isset($_GET['print_ticket']) || isset($_GET['print_baggage'])): ?>
<script>
    setTimeout(function() {
        var myModalEl = document.getElementById('modalTicketExito');
        if (myModalEl) { var myModal = new bootstrap.Modal(myModalEl); myModal.show(); }
    }, 300);
</script>
<?php endif; ?>

<?php if (isset($_GET['print_despacho'])): ?>
<script>
    setTimeout(function() {
        var myModalEl = document.getElementById('modalDespachoExito');
        if (myModalEl) { var myModal = new bootstrap.Modal(myModalEl); myModal.show(); }
    }, 300);
</script>
<?php endif; ?>

<!-- SCRIPT PARA ABRIR Y LLENAR EL MODAL DE PERFIL -->
<script>
function abrirModalPerfil() {
    fetch('/suramerica2030/auth/obtenerMiPerfil')
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            document.getElementById('perfil_nombre').value = res.data.NOMBRE;
            document.getElementById('perfil_usuario').value = res.data.USUARIO;
            document.getElementById('perfil_email').value = res.data.EMAIL;
            var modal = new bootstrap.Modal(document.getElementById('modalMiPerfil'));
            modal.show();
        } else {
            alert('No se pudieron cargar los datos de tu perfil.');
        }
    })
    .catch(err => console.error(err));
}

// ==========================================
// CONTROL DEL ESCÁNER QR EN MODAL GLOBAL
// ==========================================
let modalQrScanner = null;
let modalIsScanning = false;
let modalFacing = "environment";
let modalCurrentCamId = null;
let modalCooldown = false;

function abrirModalEscanearQR() {
    const modalEl = document.getElementById('modalEscanearQR');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

document.addEventListener("DOMContentLoaded", function() {
    const modalEl = document.getElementById('modalEscanearQR');
    if (!modalEl) return;

    modalEl.addEventListener('shown.bs.modal', function () {
        iniciarModalScanner();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        detenerModalScanner();
    });
});

function modalSetStatus(msg, isSuccess = false, isError = false) {
    const el = document.getElementById('modal-scan-status');
    if (!el) return;
    el.style.color = isSuccess ? '#22c55e' : (isError ? '#ef4444' : '#00f2fe');
    el.textContent = msg;
}

function playModalBeep() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (AudioContext) {
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = "sine";
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.15);
        }
    } catch(e){}
    if (navigator.vibrate) {
        try { navigator.vibrate([80, 40, 80]); } catch(e){}
    }
}

async function iniciarModalScanner() {
    if (typeof Html5Qrcode === "undefined") {
        modalSetStatus("Cargando librería de escaneo...");
        try {
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        } catch(e) {
            modalSetStatus("Librería de escaneo no disponible.", false, true);
            return;
        }
    }

    if (!modalQrScanner) {
        modalQrScanner = new Html5Qrcode("modal-qr-reader");
    }

    modalSetStatus("Iniciando cámara...");
    document.getElementById('modal-qr-result').style.display = 'none';

    // Detección proactiva: Si es celular en red local HTTP (sin HTTPS ni localhost)
    const esSeguro = window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    if (!esSeguro && /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)) {
        modalActivarCapturaDirecta("Cámara móvil lista para capturar.");
        return;
    }

    try {
        const devices = await Html5Qrcode.getCameras();
        const select = document.getElementById('modal-camera-select');
        select.innerHTML = '';

        if (devices && devices.length > 0) {
            devices.forEach((dev, idx) => {
                const opt = document.createElement('option');
                opt.value = dev.id;
                opt.text = dev.label || `Cámara ${idx + 1}`;
                select.appendChild(opt);
            });

            const backCam = devices.find(d => /back|rear|trasera|environment/i.test(d.label));
            modalCurrentCamId = backCam ? backCam.id : devices[0].id;
            select.value = modalCurrentCamId;

            select.onchange = function() {
                modalCurrentCamId = this.value;
                modalRestartScanner({ deviceId: { exact: modalCurrentCamId } });
            };

            modalStartScanning({ deviceId: { exact: modalCurrentCamId } });
        } else {
            modalStartScanning({ facingMode: modalFacing });
        }
    } catch (err) {
        modalStartScanning({ facingMode: "environment" });
    }
}

function modalActivarCapturaDirecta(msg) {
    modalIsScanning = false;
    const readerWrap = document.getElementById('modal-reader-wrapper');
    const fallbackBox = document.getElementById('modal-http-fallback');
    if (readerWrap) readerWrap.style.display = 'none';
    if (fallbackBox) fallbackBox.style.display = 'block';
    const flipBtn = document.getElementById('modal-btn-flip');
    const camSelect = document.getElementById('modal-camera-select');
    if (flipBtn) flipBtn.style.display = 'none';
    if (camSelect) camSelect.style.display = 'none';
    modalSetStatus(msg || "Cámara móvil lista para capturar.", true);
}

function modalStartScanning(camConfig) {
    if (!modalQrScanner) return;

    modalQrScanner.start(
        camConfig,
        { fps: 12, qrbox: { width: 200, height: 200 }, aspectRatio: 1.0 },
        (decodedText) => {
            if (modalCooldown) return;
            modalCooldown = true;
            playModalBeep();
            modalSetStatus("¡QR detectado!", true);
            modalProcesarCodigo(decodedText);
            setTimeout(() => { modalCooldown = false; }, 2000);
        },
        (err) => {}
    ).then(() => {
        modalIsScanning = true;
        modalSetStatus("Buscando código QR...");
    }).catch(err => {
        modalIsScanning = false;
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            modalActivarCapturaDirecta();
        } else if (err && err.name === 'NotAllowedError') {
            modalSetStatus("Permiso de cámara denegado.", false, true);
        } else {
            modalSetStatus("No se pudo iniciar video continuo. Usa 'Tomar Foto'.", false, true);
        }
    });
}

function modalRestartScanner(camConfig) {
    if (modalQrScanner && modalIsScanning) {
        modalQrScanner.stop().then(() => {
            modalStartScanning(camConfig);
        });
    }
}

function modalSwitchFacing() {
    if (!modalIsScanning) return;
    modalFacing = (modalFacing === "environment") ? "user" : "environment";
    modalQrScanner.stop().then(() => {
        modalStartScanning({ facingMode: modalFacing });
    });
}

function detenerModalScanner() {
    if (modalQrScanner && modalIsScanning) {
        modalQrScanner.stop().then(() => {
            modalIsScanning = false;
            modalQrScanner.clear();
        }).catch(err => console.warn(err));
    }
}

function modalProcesarCodigo(texto) {
    texto = texto.trim();
    let idFactura = null;
    const urlMatch = texto.match(/ventas\/ticket\/(\d+)/i);
    if (urlMatch) idFactura = urlMatch[1];
    if (!idFactura) {
        const ticketMatch = texto.match(/TICKET:\s*#?(\d+)/i);
        if (ticketMatch) idFactura = parseInt(ticketMatch[1], 10);
    }
    if (!idFactura) {
        const numMatch = texto.match(/^#?(\d+)$/);
        if (numMatch) idFactura = parseInt(numMatch[1], 10);
    }

    if (idFactura) {
        modalConsultarDetalle(idFactura);
    } else {
        if (texto.startsWith('http://') || texto.startsWith('https://')) {
            window.open(texto, '_blank');
        } else {
            alert("Contenido detectado:\n" + texto);
        }
    }
}

function modalConsultarDetalle(id) {
    modalSetStatus(`Consultando Ticket #${id}...`);
    fetch(`/suramerica2030/ventas/consultar/${id}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const d = res.data;
                document.getElementById('modal-res-tipo').textContent = d.FA_TIPO;
                document.getElementById('modal-res-num').textContent = '#' + d.NUM_TICKET;
                document.getElementById('modal-res-total').textContent = '$' + parseFloat(d.FA_TOTAL).toFixed(2);
                let metodoStr = d.FA_METODO || 'Efectivo';
                if (d.DESGLOSE_PAGOS && d.DESGLOSE_PAGOS.length > 1) {
                    metodoStr += ' (' + d.DESGLOSE_PAGOS.map(p => p.METODO_PAGO + ': $' + parseFloat(p.MONTO).toFixed(2)).join(' + ') + ')';
                }
                document.getElementById('modal-res-metodo').textContent = 'Método: ' + metodoStr;
                document.getElementById('modal-res-cliente').textContent = d.CLIENTE + ' (' + d.ID_CLIENTE + ')';
                document.getElementById('modal-res-ruta').textContent = d.ORIGEN + ' ➔ ' + d.DESTINO;
                
                let extra = '';
                if (d.FA_TIPO === 'PASAJE') {
                    extra = `Viaje: ${d.FECHA_VIAJE || ''} ${d.RUT_SAHORA || ''} | Asiento: [${d.ASIENTO || 'S/A'}]`;
                } else if (d.FA_TIPO === 'ENCOMIENDA') {
                    extra = `Paquetes: ${d.ENC_CANTIDAD || 1} ${d.TIPO_PAQUETE || ''} (${d.ENC_PESO || 0}Kg)`;
                } else if (d.FA_TIPO === 'SOBREPESO') {
                    extra = `Cobro por Exceso de Equipaje`;
                }
                document.getElementById('modal-res-extra').textContent = extra;

                document.getElementById('modal-btn-ver').href = d.URL_TICKET;
                document.getElementById('modal-qr-result').style.display = 'block';
                modalSetStatus(`✔ Ticket #${d.NUM_TICKET} verificado exitosamente.`, true);

                if (localStorage.getItem('qr_auto_open') === 'true') {
                    window.open(d.URL_TICKET, '_blank');
                }
            } else {
                modalSetStatus(res.message || "Ticket no encontrado", false, true);
                alert(res.message || "El comprobante no fue encontrado.");
            }
        })
        .catch(err => {
            modalSetStatus("Error de red al consultar ticket.", false, true);
        });
}

function modalReanudar() {
    document.getElementById('modal-qr-result').style.display = 'none';
    modalCooldown = false;
    modalSetStatus("Buscando código QR...");
}

function modalConsultarManual() {
    const val = document.getElementById('modal-manual-input').value.trim();
    if (!val) return alert("Ingresa un número de ticket o enlace.");
    modalProcesarCodigo(val);
}

function modalScanFile(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    modalSetStatus("Procesando foto capturada...");
    if (!modalQrScanner) {
        modalQrScanner = new Html5Qrcode("modal-qr-reader");
    }

    modalProcesarImagenQR(file, modalQrScanner, 
        (decodedText) => {
            playModalBeep();
            modalProcesarCodigo(decodedText);
            input.value = '';
        },
        (err) => {
            modalSetStatus("No se detectó QR en la foto.", false, true);
            alert("No se pudo detectar un código QR claro en la foto. Intenta con una toma más cercana y enfocada.");
            input.value = '';
        }
    );
}

function modalProcesarImagenQR(file, scannerObj, onSuccess, onError) {
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
                    const optimizedFile = new File([blob], "scan_modal.jpg", { type: "image/jpeg" });
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
</script>
</body>
</html>