<?php
use Platform\Helpers\AuthHelper;
use Platform\Helpers\FormatHelper;

$tabActual = $filtros['tab'] ?? 'activos';
$estadoFiltro = $filtros['estado'] ?? '';
$sucursalFiltro = $filtros['sucursal_id'] ?? '';
$busqueda = $filtros['buscar'] ?? '';

$puedeDespachar = AuthHelper::esAdmin() || !empty($_SESSION['p_despacho']);
$puedeRegistrarLlegada = AuthHelper::esAdmin() || !empty($_SESSION['p_llegada']);
$puedeEditarPrecios = AuthHelper::puedeEditarPrecios();

$vCss = @filemtime(__DIR__ . '/../../../../public/assets/css/encomiendas.css') ?: time();
$vJs = @filemtime(__DIR__ . '/../../../../public/assets/js/encomiendas.js') ?: time();
?>
<link rel="stylesheet" href="/suramerica2030/public/assets/css/encomiendas.css?v=<?= $vCss ?>">

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="fs-3">📦</span>
        <div>
            <h3 class="text-primary mb-0 fw-bold">Gestión y Despacho de Encomiendas</h3>
            <span class="text-muted small">Control de envíos, bodega origen, tránsito y entregas</span>
        </div>
    </div>
    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaEncomienda">
        + Registrar Nueva Encomienda
    </button>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show shadow-sm">
        <?php 
            if($_GET['status'] == 'success') echo '✅ Estado actualizado correctamente.';
            elseif($_GET['status'] == 'success_recepcion') echo '✅ Encomienda recibida y cobro registrado en caja.';
            elseif($_GET['status'] == 'success_despacho') echo '🚚 Paquetes despachados y listos para el manifiesto.';
            elseif($_GET['status'] == 'success_llegada') echo '🏢 Lote de paquetes marcados como EN OFICINA (listos para retiro).';
            elseif($_GET['status'] == 'success_unificar') {
                echo '✅ <strong>¡Despachos Integrados con Éxito!</strong> Se han unificado en un solo despacho y manifiesto único.';
                if (!empty($_GET['fecha_d']) && !empty($_GET['ruta_d'])) {
                    echo ' <a href="/suramerica2030/manifiesto/imprimirCarga?fecha='.htmlspecialchars($_GET['fecha_d']).'&ruta_id='.htmlspecialchars($_GET['ruta_d']).'" target="_blank" class="btn btn-sm btn-dark ms-2 fw-bold">🖨️ Ver Manifiesto Unificado Ahora</a>';
                }
            }
            elseif($_GET['status'] == 'success_entregado') echo '✅ Lote de paquetes marcados como ENTREGADOS AL CLIENTE exitosamente.';
            elseif($_GET['status'] == 'success_email') echo '✉️ ¡Manifiesto enviado por correo exitosamente!';
            elseif($_GET['status'] == 'success_delete') echo '🛑 Encomienda anulada. Se ha registrado el egreso en caja.';
            else echo '❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Operación fallida.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- 1. KPIS DE ENCOMIENDAS -->
<?php require __DIR__ . '/partials/kpis_encomiendas.php'; ?>

<!-- 2. NAVEGACIÓN Y SUBFILTROS -->
<?php require __DIR__ . '/partials/nav_tabs.php'; ?>

<!-- 3. BUSCADOR E HISTORIAL AVANZADO (Solo en Historial) -->
<?php if($tabActual === 'historial'): ?>
    <?php require __DIR__ . '/partials/filtro_historial.php'; ?>
<?php endif; ?>

<!-- 4. TABLA DE ENCOMIENDAS -->
<?php require __DIR__ . '/partials/tabla_encomiendas.php'; ?>

<!-- 5. MODALES (NUEVA ENCOMIENDA, CAMBIO ESTADO, ENTREGAS, ANULACIONES) -->
<?php require __DIR__ . '/partials/modales_encomiendas.php'; ?>

<!-- 6. CONFIGURACIÓN Y SCRIPT MODULAR -->
<script>
window.ENCOMIENDAS_CONFIG = {
    rutas: <?= json_encode($rutas ?? []) ?>,
    ciudadSucursal: <?= json_encode(strtoupper(trim($_SESSION['ciudad_sucursal'] ?? ''))) ?>
};
</script>
<script src="/suramerica2030/public/assets/js/encomiendas.js?v=<?= $vJs ?>"></script>