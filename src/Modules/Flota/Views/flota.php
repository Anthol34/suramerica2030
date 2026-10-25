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
        <h3 class="text-primary mb-0 fw-bold">Gestión Comercial de Rutas y Operadores</h3>
        <p class="text-muted small mb-0">Administración de tarifas al viajero en taquilla, acuerdos de liquidación con empresas aliadas y cuentas bancarias.</p>
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
    $esTabFlota = in_array($tabActiva, ['flota', 'buses', 'operadores']);
    $mapaDias = ['1'=>'Lun', '2'=>'Mar', '3'=>'Mié', '4'=>'Jue', '5'=>'Vie', '6'=>'Sáb', '7'=>'Dom'];
    
    $rutasPorSentido = [];
    foreach ($rutas as $r) {
        $sentidoKey = $r['SENTIDO'] ?? 'NORTE';
        $rutasPorSentido[$sentidoKey] = ($rutasPorSentido[$sentidoKey] ?? 0) + 1;
    }

    $ciudadesCatalogo = $ciudadesCatalogo ?? [];

    $generarOpcionesCiudades = function($idUnused = null, $valorSeleccionado = '', $placeholder = '-- Seleccionar Ciudad --') use ($ciudadesCatalogo) {
        $html = '<option value="">' . htmlspecialchars($placeholder) . '</option>';
        $encontrado = false;
        $valNorm = mb_strtolower(trim((string)$valorSeleccionado));

        // Agrupar catálogo de ciudades por país ordenado alfabéticamente
        if (!empty($ciudadesCatalogo)) {
            $porPais = [];
            foreach ($ciudadesCatalogo as $cd) {
                $pais = strtoupper(trim($cd['PAIS'] ?? 'OTROS'));
                $porPais[$pais][] = $cd['NOMBRE'];
            }
            
            $banderas = ['VENEZUELA' => '🇻🇪', 'COLOMBIA' => '🇨🇴', 'ECUADOR' => '🇪🇨', 'PERU' => '🇵🇪'];

            foreach ($porPais as $pais => $ciudades) {
                $flag = $banderas[$pais] ?? '🌎';
                $html .= '<optgroup label="' . $flag . ' ' . htmlspecialchars($pais) . '">';
                sort($ciudades);
                foreach ($ciudades as $nomCiu) {
                    $cNorm = mb_strtolower(trim($nomCiu));
                    $isSel = ($valNorm !== '' && $cNorm === $valNorm);
                    if ($isSel) $encontrado = true;
                    $html .= '<option value="' . htmlspecialchars($nomCiu) . '" ' . ($isSel ? 'selected' : '') . '>'
                          . htmlspecialchars($nomCiu) . '</option>';
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

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border d-flex flex-wrap gap-2" id="flotaTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link fw-bold fs-6 px-4 py-2 <?= !$esTabFlota ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-rutas" type="button" role="tab" aria-selected="<?= !$esTabFlota ? 'true' : 'false' ?>">
            🗺️ Rutas Comerciales y Tarifas PVP <span class="badge bg-primary ms-1"><?= count($rutas) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold fs-6 px-4 py-2 <?= $esTabFlota ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-flota" type="button" role="tab" aria-selected="<?= $esTabFlota ? 'true' : 'false' ?>">
            🤝 Operadores y Acuerdos de Liquidación <span class="badge bg-secondary ms-1"><?= count($operadores) ?> aliados · <?= count($tramosCatalogo ?? []) ?> tramos pactados</span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- ESPACIO DE TRABAJO 1: RUTAS COMERCIALES Y TARIFARIO (CON ASISTENTE Y CATÁLOGOS SATÉLITES) -->
    <?php require __DIR__ . '/partials/tab_rutas.php'; ?>

    <!-- ESPACIO DE TRABAJO 2: FLOTA Y ALIADOS OPERADORES (UNIFICADO) -->
    <?php require __DIR__ . '/partials/tab_flota.php'; ?>
</div>

<!-- CONFIGURACIÓN Y SCRIPT MODULAR -->
<script>
    const HTML_OPCIONES_OPERADORES = <?= json_encode($generarOpcionesOperadores(2)) ?>;
    const CIUDADES_CATALOGO = <?= json_encode($ciudadesCatalogo ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const TRAMOS_CATALOGO = <?= json_encode($tramosCatalogo ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const PUENTES_FRONTERIZOS = <?= json_encode($puentesFronterizos ?? [], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/suramerica2030/public/assets/js/flota.js?v=<?= $vJs ?>"></script>