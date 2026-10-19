<?php
use Platform\Helpers\FormatHelper;

$vCss = @filemtime(__DIR__ . '/../../../../public/assets/css/finanzas.css') ?: time();
$vJs = @filemtime(__DIR__ . '/../../../../public/assets/js/finanzas.js') ?: time();

$tabActivo = $_GET['tab'] ?? 'liquidaciones';
$filtros = $liquidacion['filtros'] ?? [];
$resumen = $liquidacion['resumen'] ?? [];
$porOperador = $liquidacion['por_operador'] ?? [];
$movimientos = $liquidacion['movimientos'] ?? [];
$operadores = $operadores ?? [];
$sucursales = $sucursales ?? [];
?>
<link rel="stylesheet" href="/suramerica2030/public/assets/css/finanzas.css?v=<?= $vCss ?>">

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="text-success fw-bold mb-0">Gestión Financiera y Liquidaciones</h3>
        <p class="text-muted small mb-0">Liquidación contable a socios de ruta (Panamericana, Suramérica 2030, conexiones), control de utilidad y comisiones.</p>
    </div>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert <?= strpos($_GET['status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
        <?= $_GET['status'] == 'success' ? '✅ Reglas financieras guardadas con éxito.' : '❌ Error: ' . htmlspecialchars($_GET['msg'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= ($tabActivo == 'liquidaciones') ? 'active fw-bold' : '' ?>" data-bs-toggle="tab" data-bs-target="#liquidaciones" type="button">
            🤝 Liquidación a Socios y Operadores
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= ($tabActivo == 'sucursales') ? 'active fw-bold' : '' ?>" data-bs-toggle="tab" data-bs-target="#sucursales" type="button">
            🏢 Gastos y Comisiones (Oficinas)
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- PESTAÑA 1: LIQUIDACIÓN A SOCIOS Y OPERADORES -->
    <?php require __DIR__ . '/partials/tab_liquidaciones.php'; ?>

    <!-- PESTAÑA 2: GASTOS Y COMISIONES POR SUCURSAL -->
    <?php require __DIR__ . '/partials/tab_sucursales.php'; ?>
</div>

<!-- MODAL IMPRIMIBLE: ESTADO DE CUENTA Y LIQUIDACIÓN -->
<?php require __DIR__ . '/partials/modal_imprimir.php'; ?>

<!-- DATOS Y SCRIPT MODULAR DE GESTIÓN FINANCIERA -->
<script>
window.liquidacionData = <?= json_encode([
    'filtros' => $filtros,
    'resumen' => $resumen,
    'por_operador' => $porOperador,
    'movimientos' => $movimientos
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
var liquidacionData = window.liquidacionData;
</script>
<script src="/suramerica2030/public/assets/js/finanzas.js?v=<?= $vJs ?>"></script>
