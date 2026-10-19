<?php 
use Platform\Helpers\AuthHelper;
use Platform\Helpers\FormatHelper;

$puedeEditarPrecios = AuthHelper::puedeEditarPrecios();
$puedeAnularDirecto = AuthHelper::puedeAnularDirecto();

$tabActual = $filtros['tab'] ?? 'activos';
$filtroRapido = $filtros['filtro_rapido'] ?? '';
$vCss = @filemtime(__DIR__ . '/../../../../public/assets/css/pasajes.css') ?: time();
$vJs = @filemtime(__DIR__ . '/../../../../public/assets/js/pasajes.js') ?: time();
?>
<link rel="stylesheet" href="/suramerica2030/public/assets/css/pasajes.css?v=<?= $vCss ?>">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="text-primary">Gestión y Venta de Pasajes</h3>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoPasaje">+ Registrar Nuevo Pasaje</button>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= $_GET['status'] == 'success' ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show shadow-sm">
        <?= $_GET['status'] == 'success' ? ('✅ ' . htmlspecialchars($_GET['msg'] ?? 'Operación procesada exitosamente.')) : ('❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Ocurrió un inconveniente.')) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- 1. KPIS DE PASAJES (Visible en Activos e Historial) -->
<?php if($tabActual !== 'equipaje'): ?>
    <?php require __DIR__ . '/partials/kpis_pasajes.php'; ?>
<?php endif; ?>

<!-- 2. NAVEGACIÓN Y SUBFILTROS -->
<?php require __DIR__ . '/partials/nav_tabs.php'; ?>

<!-- 3. BUSCADOR E HISTORIAL AVANZADO (Solo en Historial) -->
<?php if($tabActual === 'historial'): ?>
    <?php require __DIR__ . '/partials/filtro_historial.php'; ?>
<?php endif; ?>

<!-- 4. CONTENIDO PRINCIPAL: EQUIPAJE O TABLA DE PASAJES -->
<?php if($tabActual === 'equipaje'): ?>
    <?php require __DIR__ . '/partials/tab_equipaje.php'; ?>
<?php else: ?>
    <?php require __DIR__ . '/partials/tabla_activos.php'; ?>
<?php endif; ?>

<!-- 5. MODALES MAESTROS (NUEVO PASAJE Y CROQUIS DE ASIENTOS) -->
<?php require __DIR__ . '/partials/modales_maestros.php'; ?>

<!-- 6. CONFIGURACIÓN Y SCRIPT MODULAR -->
<script>
window.PASAJES_CONFIG = {
    rutas: <?= json_encode($rutas ?? []) ?>,
    ciudadSucursal: <?= json_encode(strtoupper(trim($_SESSION['ciudad_sucursal'] ?? ''))) ?>
};
</script>
<script src="/suramerica2030/public/assets/js/pasajes.js?v=<?= $vJs ?>"></script>