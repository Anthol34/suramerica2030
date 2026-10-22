<?php
use Platform\Helpers\FormatHelper;

$vCss = @filemtime(__DIR__ . '/../../../../public/assets/css/flota.css') ?: time();
$vJs = @filemtime(__DIR__ . '/../../../../public/assets/js/flota.js') ?: time();
?>
<link rel="stylesheet" href="/suramerica2030/public/assets/css/flota.css?v=<?= $vCss ?>">

<!-- TOAST FLOTANTE PARA RESPUESTAS AJAX SIN RECARGA -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1099;">
    <div id="toastFlotaAjax" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2 fs-6">
                <span id="toastFlotaAjaxIcon">✅</span>
                <span id="toastFlotaAjaxMsg">Cambios guardados correctamente.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    </div>
</div>

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
    $tabActiva = $_GET['tab'] ?? 'rutas';
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

        $ciudadesYaEnParadas = [];
        if (!empty($paradas)) {
            $html .= '<optgroup label="📍 Paradas del Corredor (en orden de viaje)">';
            foreach ($paradas as $idx => $p) {
                $cNorm = mb_strtolower(trim($p['CIUDAD']));
                $ciudadesYaEnParadas[$cNorm] = true;
                $isSel = ($valNorm !== '' && $cNorm === $valNorm);
                if ($isSel) $encontrado = true;
                $tipo = !empty($p['TIPO_PARADA']) ? ' [' . str_replace('_', ' ', $p['TIPO_PARADA']) . ']' : '';
                $html .= '<option value="' . htmlspecialchars($p['CIUDAD']) . '" ' . ($isSel ? 'selected' : '') . '>'
                      . ($idx + 1) . '. ' . htmlspecialchars($p['CIUDAD']) . ' (' . htmlspecialchars($p['PAIS']) . ')' . htmlspecialchars($tipo) . '</option>';
            }
            $html .= '</optgroup>';
        }

        // Agrupar el catálogo del dominio por país para permitir seleccionar cualquier ciudad
        if (!empty($ciudadesCatalogo)) {
            $porPais = [];
            foreach ($ciudadesCatalogo as $cd) {
                $pais = strtoupper(trim($cd['PAIS'] ?? 'OTROS'));
                $porPais[$pais][] = $cd['NOMBRE'];
            }
            
            $banderas = ['VENEZUELA' => '🇻🇪', 'COLOMBIA' => '🇨🇴', 'ECUADOR' => '🇪🇨', 'PERU' => '🇵🇪'];

            foreach ($porPais as $pais => $ciudades) {
                $flag = $banderas[$pais] ?? '🌎';
                $html .= '<optgroup label="' . $flag . ' Catálogo de Ciudades - ' . htmlspecialchars($pais) . '">';
                foreach ($ciudades as $nomCiu) {
                    $cNorm = mb_strtolower(trim($nomCiu));
                    $isSel = ($valNorm !== '' && $cNorm === $valNorm);
                    if ($isSel) $encontrado = true;
                    $enCorredorTag = isset($ciudadesYaEnParadas[$cNorm]) ? ' (En corredor)' : '';
                    $html .= '<option value="' . htmlspecialchars($nomCiu) . '" ' . ($isSel ? 'selected' : '') . '>'
                          . htmlspecialchars($nomCiu) . $enCorredorTag . '</option>';
                }
                $html .= '</optgroup>';
            }
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
        <button class="nav-link fw-bold <?= ($tabActiva == 'rutas') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-rutas" type="button">
            🗺️ Rutas Comerciales y Tarifas <span class="badge bg-secondary ms-1"><?= count($rutas) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'tramos') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-tramos" type="button">
            🧩 Tramos y Tarifas Maestras <span class="badge bg-secondary ms-1"><?= count($tramosCatalogo ?? []) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'buses' || $tabActiva == 'operadores') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-buses" type="button">
            🚌 Buses y Operadores / Socios <span class="badge bg-secondary ms-1"><?= count($buses) ?> buses · <?= count($operadores) ?> operadores</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'ciudades') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-ciudades" type="button">
            🏙️ Dominio de Ciudades y Destinos <span class="badge bg-secondary ms-1"><?= count($ciudadesCompletas ?? $ciudadesCatalogo) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($tabActiva == 'corredor') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-corredor" type="button">
            🌐 Corredor e Itinerario Troncal
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- PESTAÑA 1: RUTAS COMERCIALES Y MATRIZ DE TARIFAS -->
    <?php require __DIR__ . '/partials/tab_rutas.php'; ?>

    <!-- PESTAÑA 2: CATÁLOGO MAESTRO DE TRAMOS Y TARIFAS -->
    <?php require __DIR__ . '/partials/tab_tramos.php'; ?>

    <!-- PESTAÑA 3: FLOTA DE BUSES Y OPERADORES / SOCIOS -->
    <?php require __DIR__ . '/partials/tab_buses.php'; ?>

    <!-- PESTAÑA 4: GESTIÓN DE CIUDADES Y DOMINIO DE DESTINOS -->
    <?php require __DIR__ . '/partials/tab_ciudades.php'; ?>

    <!-- PESTAÑA 5: CORREDOR E ITINERARIO TRONCAL MAESTRO -->
    <?php require __DIR__ . '/partials/tab_corredores.php'; ?>
</div>

<!-- CONFIGURACIÓN DE CORREDORES Y SCRIPT MODULAR -->
<script>
    const HTML_OPCIONES_OPERADORES = <?= json_encode($generarOpcionesOperadores(2)) ?>;
    const PARADAS_POR_CORREDOR = <?= json_encode($paradasPorCorredor ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const CIUDADES_CATALOGO = <?= json_encode($ciudadesCatalogo ?? [], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/suramerica2030/public/assets/js/flota.js?v=<?= $vJs ?>"></script>