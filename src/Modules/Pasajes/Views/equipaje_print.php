<?php
// Decodificar piezas individuales registradas (piezas de bodega a etiquetar)
$piezas = [];
if (!empty($pasaje['EQUIPAJE_DETALLE'])) {
    $decoded = json_decode($pasaje['EQUIPAJE_DETALLE'], true);
    if (is_array($decoded) && count($decoded) > 0) {
        foreach ($decoded as $pzItem) {
            if (($pzItem['ubicacion'] ?? 'BODEGA') === 'BODEGA' || empty($pzItem['ubicacion'])) {
                $piezas[] = $pzItem;
            }
        }
    }
}

// Fallback para pasajes antiguos o sin detalle JSON
if (empty($piezas)) {
    $pb = floatval($pasaje['PESO_BODEGA'] ?? 0);
    if ($pb > 0) {
        $piezas[] = ['numero' => 1, 'tipo' => 'Maleta', 'ubicacion' => 'BODEGA', 'peso' => $pb, 'descripcion' => 'Equipaje en bodega'];
    }
    if (empty($piezas)) {
        $piezas[] = ['numero' => 1, 'tipo' => 'Maleta', 'ubicacion' => 'BODEGA', 'peso' => 0.0, 'descripcion' => 'Equipaje general'];
    }
}

$totalPiezas = count($piezas);
$pesoBodegaTotal = 0.0;
foreach ($piezas as $pz) {
    $pesoBodegaTotal += floatval($pz['peso'] ?? 0);
}
$pesoManoFijo = 10.0; // Mano fija asumida
$pesoTotalGlobal = $pesoBodegaTotal + $pesoManoFijo;

// Detección de Operador Aliado / Empresa Aliada (Panamericana u otros socios)
$esAliado = false;
$nombreAliado = '';

$modalidadRaw = strtoupper(trim($pasaje['MODALIDAD'] ?? ''));
$opNombreRaw = strtoupper(trim($pasaje['OPERADOR_NOMBRE'] ?? ''));
$opRazonRaw = strtoupper(trim($pasaje['OPERADOR_RAZON'] ?? ''));
$opTramoRaw = strtoupper(trim($pasaje['OPERADOR_TRAMO'] ?? ''));
$opTipoRaw = strtoupper(trim($pasaje['TIPO_OPERADOR'] ?? ''));

if ($modalidadRaw === 'PANAMERICANA' 
    || stripos($opNombreRaw, 'PANAMERICANA') !== false 
    || stripos($opRazonRaw, 'PANAMERICANA') !== false 
    || stripos($opTramoRaw, 'PANAMERICANA') !== false) {
    $esAliado = true;
    $nombreAliado = 'PANAMERICANA';
} elseif ($opTipoRaw === 'EMPRESA_ALIADA' || $opTipoRaw === 'SOCIO_AFILIADO') {
    $esAliado = true;
    $nombreAliado = !empty($opNombreRaw) ? $opNombreRaw : (!empty($opTramoRaw) ? $opTramoRaw : 'EMPRESA ALIADA');
} elseif (!empty($opTramoRaw) && stripos($opTramoRaw, 'SURAMERICA') === false && $opTramoRaw !== 'PROPIO' && $opTramoRaw !== 'GENERAL') {
    $esAliado = true;
    $nombreAliado = $opTramoRaw;
} elseif (!empty($opNombreRaw) && stripos($opNombreRaw, 'SURAMERICA') === false && $opNombreRaw !== 'PROPIO' && $opTipoRaw !== 'PROPIO') {
    $esAliado = true;
    $nombreAliado = $opNombreRaw;
}

// Código de boleto del socio generado por el sistema del socio e ingresado por el usuario
$codigoSocio = trim($pasaje['CODIGO_SOCIO'] ?? ($_GET['codigo_socio'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas de Equipaje <?= !empty($codigoSocio) ? '('.htmlspecialchars($codigoSocio).') ' : '' ?>- <?= htmlspecialchars($pasaje['CLI_NOMBRE'].' '.$pasaje['CLI_APELLIDO']) ?></title>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/equipaje_print.css">
</head>
<body>

    <!-- BARRA FLOTANTE PARA INGRESAR CÓDIGO DE BOLETO DEL SOCIO ANTES DE IMPRIMIR -->
    <div class="no-print" style="width: 100%; max-width: 800px; margin: 0 auto 20px auto; background: #ffffff; border: 2px solid #0b2b56; border-radius: 8px; padding: 12px 18px; box-shadow: 0 4px 15px rgba(0,0,0,0.18);">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">🎫</span>
                    <strong style="color: #0b2b56; font-size: 14px;">Etiquetas de Equipaje (Hoja A4 Vertical)</strong>
                    <span style="background: #e3f2fd; color: #0b2b56; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 3px; border: 1px solid #90caf9;">📄 Hasta 4 por Hoja A4 (Izquierda)</span>
                    <?php if ($esAliado): ?>
                        <span style="background: #ffc107; color: #000; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 3px;">SISTEMA EXTERNO</span>
                    <?php endif; ?>
                </div>
                <div style="font-size: 11.5px; color: #555; margin-top: 2px;">
                    <?= $esAliado ? 'Ingrese el código emitido por el sistema del socio (ej: <strong>PANA-000000</strong>) antes de imprimir.' : 'Diseño vertical alineado a la izquierda (entran hasta 4 por hoja para que el papel sobrante se pueda recortar y reutilizar).' ?>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="text" id="inputCodigoSocio" 
                       placeholder="Ej: PANA-000000" 
                       value="<?= htmlspecialchars($codigoSocio) ?>"
                       style="padding: 7px 12px; border: 2px solid #0b2b56; border-radius: 5px; font-weight: bold; font-family: monospace; font-size: 14px; text-transform: uppercase; width: 180px; letter-spacing: 0.5px;">
                
                <button type="button" onclick="aplicarCodigoSocio(false)" 
                        style="padding: 7px 12px; background: #f0f4f8; color: #0b2b56; border: 1.5px solid #0b2b56; border-radius: 5px; font-weight: bold; font-size: 12px; cursor: pointer;">
                    🔄 Actualizar
                </button>

                <button type="button" onclick="aplicarCodigoSocio(true)" 
                        style="padding: 7px 16px; background: #0b2b56; color: #fff; border: none; border-radius: 5px; font-weight: bold; font-size: 12px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    🖨️ Guardar e Imprimir
                </button>
            </div>
        </div>
    </div>

<?php 
$paginasPiezas = array_chunk($piezas, 4, true); 
foreach ($paginasPiezas as $numPag => $grupoPiezas): 
?>
    <div class="a4-sheet">
        <?php foreach ($grupoPiezas as $index => $pz): 
            $numPieza = $index + 1;
            $tipoPz = strtoupper($pz['tipo'] ?? 'MALETA');
            $pesoPz = number_format(floatval($pz['peso'] ?? 0), 1);
            $descPz = !empty($pz['descripcion']) ? htmlspecialchars($pz['descripcion']) : '';
        ?>
            <div class="print-wrapper">
                <div class="cut-indicator">✂ CORTE</div>
                <div class="tag-rotated">
                    
                    <!-- SOLAPA SUPERIOR PARA ENGRAPAR AL ASA -->
                    <div class="blank-space">
                        <div style="font-size: 11px; margin-bottom: 2px;">📎</div>
                        <div>ENGRAPAR / PEGAR</div>
                        <div style="font-size: 6.5px; color: #888; margin-top: 2px; font-weight: normal;">AL ASA DE MALETA</div>
                    </div>
                    
                    <!-- CONTENIDO DE LA ETIQUETA -->
                    <div class="tag-content">
                        <div class="section-left">
                            <div class="logo-text">SURAMERICA</div>
                            <div class="sub-logo">TICKET DE EQUIPAJE</div>
                            <?php if ($esAliado): ?>
                                <div style="font-size: 8px; font-weight: 900; background: #000; color: #ffeb3b; padding: 1.5px 4px; border-radius: 3px; margin-top: 1px; letter-spacing: 0.5px; border: 1px solid #333;">
                                    ⭐ <?= htmlspecialchars($nombreAliado) ?>
                                </div>
                            <?php elseif (!empty($pasaje['OPERADOR_TRAMO']) && strtoupper($pasaje['OPERADOR_TRAMO']) !== 'PROPIO' && strtoupper($pasaje['OPERADOR_TRAMO']) !== 'GENERAL'): ?>
                                <div style="font-size: 8px; font-weight: bold; background: #000; color: #fff; padding: 1.5px 4px; border-radius: 2px; margin-top: 2px; letter-spacing: 0.5px;">
                                    <?= htmlspecialchars(strtoupper($pasaje['OPERADOR_TRAMO'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- CÓDIGO DE CONTROL PROPIO DE SURAMÉRICA -->
                            <div class="barcode">||||||||||</div>
                            <div style="font-size: 8.5px; letter-spacing: 0.3px; font-weight: bold;">
                                *EQP-<?= str_pad($pasaje['ID_PASAJE'], 6, '0', STR_PAD_LEFT) ?>-<?= $numPieza ?>/<?= $totalPiezas ?>*
                            </div>
                            
                            <div style="margin-top: 4px; background: #000; color: #fff; padding: 1.5px 6px; border-radius: 3px; font-weight: 900; font-size: 10px; letter-spacing: 0.5px;">
                                PIEZA <?= $numPieza ?> DE <?= $totalPiezas ?>
                            </div>

                            <!-- CONTROL BOLETO INTERNO SURAMÉRICA -->
                            <div style="margin-top: 4px; border-top: 1.5px solid #000; padding-top: 2px; width: 100%;">
                                <span class="label">N° BOLETO:</span><br>
                                <span class="bold" style="font-size: 14px;">#<?= str_pad($pasaje['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?></span>
                            </div>

                            <!-- BOLETO DEL SOCIO EXTERNO (SI FUE INGRESADO) -->
                            <div class="partner-box-left" style="margin-top: 3px; background: #fff8e1; border: 1px dashed #f57f17; border-radius: 3px; padding: 1.5px; width: 100%; <?= empty($codigoSocio) ? 'display: none;' : '' ?>">
                                <span style="font-size: 7px; color: #b71c1c; font-weight: bold; display: block; text-transform: uppercase;">BOLETO <?= htmlspecialchars($nombreAliado ?: 'SOCIO') ?>:</span>
                                <span class="partner-code-text" style="font-size: 11.5px; font-weight: 900; color: #b71c1c; font-family: monospace; letter-spacing: 0.5px;"><?= htmlspecialchars($codigoSocio ?: 'S/C') ?></span>
                            </div>
                        </div>
                        
                        <div class="section-middle">
                            <!-- ENCABEZADO DE EMPRESA ALIADA SI APLICA O SI HAY CÓDIGO -->
                            <div class="partner-box-mid" style="background: #fff8e1; border: 1px solid #ffe082; border-left: 3px solid #f57f17; padding: 1.5px 5px; border-radius: 3px; margin-bottom: 3px; display: <?= empty($codigoSocio) && !$esAliado ? 'none' : 'flex' ?>; justify-content: space-between; align-items: center;">
                                <div>
                                    <span style="font-size: 7.5px; text-transform: uppercase; color: #856404; font-weight: bold; display: block; line-height: 1;">EMPRESA ALIADA / SOCIO</span>
                                    <strong style="font-size: 10px; color: #000;">⭐ <?= htmlspecialchars($nombreAliado ?: 'PANAMERICANA') ?></strong>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 7.5px; text-transform: uppercase; color: #856404; font-weight: bold; display: block; line-height: 1;">BOLETO SOCIO</span>
                                    <strong class="partner-code-text" style="font-size: 11.5px; color: #b71c1c; font-family: monospace; letter-spacing: 0.5px;"><?= htmlspecialchars($codigoSocio ?: 'S/C') ?></strong>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-col">
                                    <span class="label">PASAJERO PROPIETARIO</span>
                                    <span class="bold"><?= htmlspecialchars($pasaje['CLI_NOMBRE'].' '.$pasaje['CLI_APELLIDO']) ?></span>
                                </div>
                            </div>
                            <div class="info-row" style="margin-top: 2px;">
                                <div class="info-col">
                                    <span class="label">DOCUMENTO DE IDENTIDAD</span>
                                    <span class="bold"><?= htmlspecialchars($pasaje['ID_CLIENTE']) ?></span>
                                </div>
                                <div class="info-col" style="text-align: right;">
                                    <span class="label">BUS (PLACA)</span>
                                    <span class="bold"><?= htmlspecialchars($pasaje['BUS_PLACA'] ?? 'S/A') ?></span>
                                </div>
                            </div>
                            <div class="info-row" style="margin-top: 2px;">
                                <div class="info-col">
                                    <span class="label">FECHA DE SALIDA DEL VIAJE</span>
                                    <span class="bold" style="font-size: 11.5px;"><?= htmlspecialchars($pasaje['FECHA_VIAJE'] ?? 'ABIERTA') ?></span>
                                </div>
                            </div>
                            
                            <div style="border-top: 1.5px dashed #ccc; margin: 3px 0;"></div>
                            
                            <!-- DATOS ESPECÍFICOS DE ESTA PIEZA -->
                            <div class="info-row" style="margin-bottom: 2px;">
                                <div class="info-col">
                                    <span class="label">PIEZA (<?= $numPieza ?>/<?= $totalPiezas ?>)</span>
                                    <span class="bold" style="font-size: 13px; color: #0b2b56;">
                                        <?= $tipoPz ?>
                                    </span>
                                </div>
                                <div class="info-col" style="text-align: right;">
                                    <span class="label">PESO DE ESTA PIEZA</span>
                                    <span class="bold" style="font-size: 15px; color: #000;"><?= $pesoPz ?> KG</span>
                                </div>
                            </div>

                            <?php if($descPz !== ''): ?>
                                <div class="info-row" style="margin-top: 1px; margin-bottom: 2px;">
                                    <div class="info-col">
                                        <span class="label">DESCRIPCIÓN / CONTENIDO</span>
                                        <span style="font-size: 9.5px; font-weight: bold;"><?= $descPz ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div style="margin-top: 2px; font-size: 8.5px; color: #444; background: #f1f3f5; padding: 1.5px 4px; border-radius: 3px; display: flex; justify-content: space-between;">
                                <span>Bodega: <strong><?= number_format($pesoBodegaTotal, 1) ?> Kg (<?= $totalPiezas ?> pz)</strong></span>
                                <span>Mano: <strong>10.0 Kg</strong></span>
                                <span>Total: <strong><?= number_format($pesoTotalGlobal, 1) ?> Kg</strong></span>
                            </div>
                        </div>
                        
                        <div class="section-right">
                            <!-- CÓDIGO BOLETO SOCIO EN TALÓN DERECHO SI FUE INGRESADO -->
                            <div class="partner-box-right" style="background: #000; color: #fff; padding: 1.5px 3px; border-radius: 3px; font-size: 10px; font-weight: 900; letter-spacing: 0.5px; margin-bottom: 2px; text-transform: uppercase; width: 95%; text-align: center; border: 1px solid #222; <?= empty($codigoSocio) ? 'display: none;' : '' ?>">
                                <span style="font-size: 7px; display: block; color: #ffeb3b; font-weight: bold; line-height: 1;">BOLETO <?= htmlspecialchars($nombreAliado ?: 'SOCIO') ?></span>
                                <span class="partner-code-text"><?= htmlspecialchars($codigoSocio ?: 'S/C') ?></span>
                            </div>

                            <span class="label">DESTINO TRAMO</span>
                            <div class="dest-huge"><?= htmlspecialchars($pasaje['DESTINO_TRAMO'] ?? $pasaje['RUT_DESTINO'] ?? 'S/D') ?></div>
                            <?php if (!empty($pasaje['DESTINO_TRAMO']) && $pasaje['DESTINO_TRAMO'] !== $pasaje['RUT_DESTINO']): ?>
                                <div style="font-size: 8.5px; font-weight: bold; color: #333; margin-top: 1px; background: #eee; padding: 1px 3px; border-radius: 2px;">
                                    FINAL: <?= htmlspecialchars($pasaje['RUT_DESTINO']) ?>
                                </div>
                            <?php endif; ?>
                            <span class="label" style="margin-top: 3px;">ASIENTO ASIGNADO</span>
                            <div class="asiento-box"><?= htmlspecialchars($pasaje['PAX_ASIENTO'] ?? 'S/A') ?></div>
                        </div>
                    </div>

                    <!-- SOLAPA INFERIOR PARA ENGRAPAR AL ASA -->
                    <div class="blank-space blank-space-end">
                        <div style="font-size: 11px; margin-bottom: 2px;">📎</div>
                        <div>EXTREMO SUJECIÓN</div>
                        <div style="font-size: 6.5px; color: #888; margin-top: 2px; font-weight: normal;">UNIR CON GRAPA/CINTA</div>
                    </div>
                    
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
    
    <div class="controls">
        <button class="btn-float btn-print" onclick="window.print()">🖨️ Imprimir Todas (<?= $totalPiezas ?>)</button>
        <button class="btn-float btn-close" onclick="window.close()">❌ Cerrar</button>
    </div>
    
    <script>
    function aplicarCodigoSocio(lanzarImpresion = false) {
        const input = document.getElementById('inputCodigoSocio');
        const codigo = input ? input.value.trim().toUpperCase() : '';
        const idPasaje = <?= json_encode((int)$pasaje['ID_PASAJE']) ?>;
        
        // Actualizar visualmente en todas las etiquetas en tiempo real
        const codeTexts = document.querySelectorAll('.partner-code-text');
        codeTexts.forEach(el => {
            el.textContent = codigo || 'S/C';
        });
        
        const boxesLeft = document.querySelectorAll('.partner-box-left');
        boxesLeft.forEach(b => {
            b.style.display = codigo ? 'block' : 'none';
        });
        
        const boxesRight = document.querySelectorAll('.partner-box-right');
        boxesRight.forEach(b => {
            b.style.display = codigo ? 'block' : 'none';
        });

        const boxesMid = document.querySelectorAll('.partner-box-mid');
        boxesMid.forEach(b => {
            b.style.display = (codigo || <?= $esAliado ? 'true' : 'false' ?>) ? 'flex' : 'none';
        });
        
        // Guardar en la base de datos vía AJAX
        if (idPasaje) {
            const formData = new FormData();
            formData.append('id_pasaje', idPasaje);
            formData.append('codigo_socio', codigo);
            fetch('/suramerica2030/pasajes/guardarCodigoSocio', {
                method: 'POST',
                body: formData
            }).then(r => r.json()).then(res => {
                console.log('Código socio guardado exitosamente:', res);
            }).catch(err => {
                console.warn('Error al guardar código socio:', err);
            });
        }
        
        if (lanzarImpresion) {
            setTimeout(() => {
                window.print();
            }, 150);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inp = document.getElementById('inputCodigoSocio');
        if (inp) {
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    aplicarCodigoSocio(true);
                }
            });
        }
    });

    window.onload = function() {
        const esAliado = <?= $esAliado ? 'true' : 'false' ?>;
        const codigoActual = <?= json_encode($codigoSocio) ?>;
        
        if (esAliado && (!codigoActual || codigoActual.trim() === '')) {
            const inp = document.getElementById('inputCodigoSocio');
            if (inp) {
                inp.focus();
                inp.select();
            }
            setTimeout(() => {
                const resp = prompt("Boleto Empresa Aliada (<?= addslashes($nombreAliado ?: 'Panamericana') ?>):\n\nIngrese el código generado por el sistema del socio (Ej: PANA-000000) antes de imprimir:", "");
                if (resp !== null && resp.trim() !== '') {
                    if (inp) inp.value = resp.trim().toUpperCase();
                    aplicarCodigoSocio(true);
                }
            }, 100);
        } else {
            setTimeout(() => { window.print(); }, 500);
        }
    };
    </script>
</body>
</html>