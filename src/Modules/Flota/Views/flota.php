<?php
use Platform\Helpers\FormatHelper;

$vCss = @filemtime(__DIR__ . '/../../../../public/assets/css/flota.css') ?: time();
$vJs = @filemtime(__DIR__ . '/../../../../public/assets/js/flota.js') ?: time();
?>
<link rel="stylesheet" href="/suramerica2030/public/assets/css/flota.css?v=<?= $vCss ?>">

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="text-primary mb-0 fw-bold">Gestión de Corredores y Flota</h3>
        <p class="text-muted small mb-0">Configura itinerarios maestros, tarifas comerciales y la flota de buses de Suramérica 2030.</p>
    </div>
</div>

<?php if(isset($_GET['status'])): 
    $statusType = $_GET['status'];
    $alertClass = 'alert-success';
    if (strpos($statusType, 'error') !== false) {
        $alertClass = 'alert-danger';
    } elseif ($statusType === 'success_archived') {
        $alertClass = 'alert-warning border-warning';
    }
?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-sm">
        <?php 
            if($statusType == 'success') echo '✅ Registro guardado o generado correctamente.';
            elseif($statusType == 'success_edit') echo '✅ Cambios y tarifas actualizados correctamente.';
            elseif($statusType == 'success_delete') echo '🗑️ Elemento eliminado permanentemente del sistema.';
            elseif($statusType == 'success_archived') echo '📦 ' . htmlspecialchars($_GET['msg'] ?? 'La ruta fue archivada como INACTIVA para proteger el historial contable.');
            elseif($statusType == 'success_delete_bus') echo '🗑️ Unidad y Socio eliminados correctamente del sistema.';
            else echo '❌ Error: ' . htmlspecialchars($_GET['msg'] ?? 'Ocurrió un error en la operación.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php 
    $tabActiva = $_GET['tab'] ?? 'corredor';
    $mapaDias = ['1'=>'Lun', '2'=>'Mar', '3'=>'Mié', '4'=>'Jue', '5'=>'Vie', '6'=>'Sáb', '7'=>'Dom'];
    $rutasPorCorredor = [];
    foreach ($rutas as $r) {
        $cid = $r['ID_CORREDOR'] ?? 0;
        $rutasPorCorredor[$cid] = ($rutasPorCorredor[$cid] ?? 0) + 1;
    }

    $paradasPorCorredor = $paradasPorCorredor ?? [];
    $ciudadesCatalogo = $ciudadesCatalogo ?? [];

    $generarOpcionesCiudades = function($idCorredor, $valorSeleccionado = '', $placeholder = '-- Seleccionar Ciudad --') use ($paradasPorCorredor, $ciudadesCatalogo) {
        $html = '<option value="">' . htmlspecialchars($placeholder) . '</option>';
        $paradas = $paradasPorCorredor[$idCorredor] ?? [];
        $encontrado = false;
        $valNorm = mb_strtolower(trim((string)$valorSeleccionado));

        if (!empty($paradas)) {
            $html .= '<optgroup label="📍 Paradas del Corredor (en orden de viaje)">';
            foreach ($paradas as $idx => $p) {
                $isSel = ($valNorm !== '' && mb_strtolower(trim($p['CIUDAD'])) === $valNorm);
                if ($isSel) $encontrado = true;
                $tipo = !empty($p['TIPO_PARADA']) ? ' [' . str_replace('_', ' ', $p['TIPO_PARADA']) . ']' : '';
                $html .= '<option value="' . htmlspecialchars($p['CIUDAD']) . '" ' . ($isSel ? 'selected' : '') . '>'
                      . ($idx + 1) . '. ' . htmlspecialchars($p['CIUDAD']) . ' (' . htmlspecialchars($p['PAIS']) . ')' . htmlspecialchars($tipo) . '</option>';
            }
            $html .= '</optgroup>';
        }
        if ($valorSeleccionado !== '' && !$encontrado) {
            $html .= '<optgroup label="⚠️ Ciudad Actual Configurada">';
            $html .= '<option value="' . htmlspecialchars($valorSeleccionado) . '" selected>' . htmlspecialchars($valorSeleccionado) . ' (Registrada)</option>';
            $html .= '</optgroup>';
        }

        return $html;
    };

    $operadores = $operadores ?? [];
    $generarOpcionesOperadores = function($idSeleccionado = 1, $placeholder = null) use ($operadores) {
        $html = '';
        if ($placeholder) {
            $html .= '<option value="">' . htmlspecialchars($placeholder) . '</option>';
        }
        $grupos = [
            'PROPIO' => ['label' => '🏢 Flota Propia', 'items' => []],
            'SOCIO_AFILIADO' => ['label' => '🤝 Socios Propietarios', 'items' => []],
            'EMPRESA_ALIADA' => ['label' => '⭐ Empresas Aliadas', 'items' => []]
        ];
        foreach ($operadores as $op) {
            $tipo = $op['TIPO_OPERADOR'] ?? 'SOCIO_AFILIADO';
            if (!isset($grupos[$tipo])) {
                $grupos[$tipo] = ['label' => '📋 ' . ucfirst(strtolower($tipo)), 'items' => []];
            }
            $grupos[$tipo]['items'][] = $op;
        }

        foreach ($grupos as $g) {
            if (empty($g['items'])) continue;
            $html .= '<optgroup label="' . htmlspecialchars($g['label']) . '">';
            foreach ($g['items'] as $op) {
                $isSel = ($idSeleccionado && intval($idSeleccionado) === intval($op['ID_OPERADOR']));
                $det = !empty($op['IDENTIFICACION']) ? ' [' . $op['IDENTIFICACION'] . ']' : '';
                $html .= '<option value="' . $op['ID_OPERADOR'] . '" ' . ($isSel ? 'selected' : '') . ' data-nombre="' . htmlspecialchars($op['NOMBRE_COMERCIAL']) . '">'
                      . htmlspecialchars($op['NOMBRE_COMERCIAL']) . htmlspecialchars($det) . '</option>';
            }
            $html .= '</optgroup>';
        }
        return $html;
    };
?>

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border" id="flotaTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'corredor') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-corredor" type="button">
            🌐 Corredor e Itinerario Troncal
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'rutas') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-rutas" type="button">
            📍 Rutas Comerciales y Tarifas <span class="badge bg-secondary ms-1"><?= count($rutas) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'buses' || $tabActiva == 'operadores') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-buses" type="button">
            🚌 Buses y Operadores / Socios <span class="badge bg-secondary ms-1"><?= count($buses) ?> buses · <?= count($operadores) ?> operadores</span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- PESTAÑA 1: CORREDOR E ITINERARIO TRONCAL MAESTRO -->
    <?php require __DIR__ . '/partials/tab_corredores.php'; ?>

    <!-- PESTAÑA 2: RUTAS COMERCIALES Y MATRIZ DE TARIFAS -->
    <?php require __DIR__ . '/partials/tab_rutas.php'; ?>

    <!-- PESTAÑA 3: FLOTA DE BUSES Y OPERADORES / SOCIOS -->
    <?php require __DIR__ . '/partials/tab_buses.php'; ?>
</div>

<!-- CONFIGURACIÓN DE CORREDORES Y SCRIPT MODULAR -->
<script>
    const HTML_OPCIONES_OPERADORES = <?= json_encode($generarOpcionesOperadores(2)) ?>;
    const PARADAS_POR_CORREDOR = <?= json_encode($paradasPorCorredor ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const CIUDADES_CATALOGO = <?= json_encode($ciudadesCatalogo ?? [], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/suramerica2030/public/assets/js/flota.js?v=<?= $vJs ?>"></script>