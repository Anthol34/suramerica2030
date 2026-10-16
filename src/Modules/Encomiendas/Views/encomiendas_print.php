<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= !empty($esEnTransito) ? 'Manifiesto de Carga en Tránsito - Suramerica 2030' : 'Lista de Carga Logística - ' . htmlspecialchars($fecha) ?></title>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/encomiendas_print.css">
    <?php if(isset($is_email) && $is_email): ?>
        <style><?= @file_get_contents(dirname(__DIR__, 4) . '/public/assets/css/encomiendas_print.css') ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="no-print text-center" style="margin-bottom:20px;">
        <button onclick="window.print()" style="padding: 10px; background: #0b2b56; color: white; border: none; border-radius: 5px; cursor:pointer; font-weight:bold;">🖨️ Imprimir / Guardar PDF</button>
        <button onclick="window.close()" style="padding: 10px; background: #ccc; border: none; border-radius: 5px; cursor:pointer; margin-left:10px;">Cerrar Ventana</button>
    </div>

    <h2 class="text-center bold" style="margin:0;">SURAMERICA 2030 S.A.</h2>
    <h3 class="text-center bold" style="margin:5px 0;">MANIFIESTO DE CARGA Y ENCOMIENDAS</h3>
    
    <?php if(!empty($esEnTransito)): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; font-size:12px; background:#e8f5e9; border:1px solid #28a745; padding:8px 12px; border-radius:4px;">
            <div>
                <strong>ESTADO:</strong> <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 11px;">EN TRÁNSITO ACTUALMENTE (CARGA GLOBAL)</span>
                <?php if(!empty($ruta['RUT_ORIGEN']) && !empty($ruta['RUT_DESTINO'])): ?>
                    <br><strong>RUTA FILTRADA:</strong> <?= htmlspecialchars($ruta['RUT_ORIGEN'] . ' ➔ ' . $ruta['RUT_DESTINO']) ?>
                <?php else: ?>
                    <br><strong>COBERTURA:</strong> Todas las rutas activas
                <?php endif; ?>
            </div>
            <div style="text-align:right;">
                <strong>FECHA Y HORA DE EMISIÓN:</strong> <?= date('Y-m-d H:i') ?><br>
                <strong>TOTAL EN RUTA:</strong> <?= count($paquetes) ?> paquete(s)
            </div>
        </div>
    <?php elseif(!empty($esConsolidado)): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; font-size:12px; background:#e3f2fd; border:1px solid #0d6efd; padding:8px 12px; border-radius:4px;">
            <div>
                <strong>TIPO:</strong> <span style="background: #0b2b56; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 11px;">MANIFIESTO CONSOLIDADO (DESPACHOS INTEGRADOS)</span>
                <?php if(!empty($ruta['RUT_ORIGEN']) && !empty($ruta['RUT_DESTINO'])): ?>
                    <br><strong>RUTA DEL VIAJE:</strong> <?= htmlspecialchars($ruta['RUT_ORIGEN'] . ' ➔ ' . $ruta['RUT_DESTINO']) ?>
                <?php endif; ?>
            </div>
            <div style="text-align:right;">
                <strong>FECHA DE VIAJE:</strong> <?= htmlspecialchars($fecha) ?><br>
                <strong>TOTAL CONSOLIDADO:</strong> <?= count($paquetes) ?> paquete(s)
            </div>
        </div>
    <?php else: ?>
        <div style="display:flex; justify-content:space-between; margin-top:15px; font-size:12px;">
            <div><strong>RUTA ASIGNADA:</strong> <?= htmlspecialchars($ruta['RUT_ORIGEN'] ?? 'N/A') ?> ➔ <?= htmlspecialchars($ruta['RUT_DESTINO'] ?? 'N/A') ?></div>
            <div><strong>FECHA DE DESPACHO:</strong> <?= htmlspecialchars($fecha) ?></div>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">N°</th>
                <th style="width: 7%;">N° GUÍA</th>
                <th style="width: 8%;">FECHA RECIBIDO</th>
                <th style="width: 20%;">REMITENTE (ENVÍA)</th>
                <th style="width: 20%;">DESTINATARIO (RECIBE)</th>
                <th style="width: 9%;">CANT / TIPO</th>
                <th style="width: 6%;">PESO</th>
                <th style="width: 27%;">DETALLE / CONTENIDO</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($paquetes)): ?>
                <tr><td colspan="8" class="text-center" style="padding:20px; font-size: 12px; color: #555;">
                    <?= !empty($esEnTransito) ? 'No hay encomiendas en estado "En Tránsito" actualmente.' : 'No hay carga registrada o despachada para esta ruta en la fecha seleccionada.' ?>
                </td></tr>
            <?php else: $i = 1; $totalPeso = 0; $totalPiezas = 0; ?>
                <?php foreach($paquetes as $p): $totalPeso += floatval($p['ENC_PESO']); $totalPiezas += intval($p['ENC_CANTIDAD']); ?>
                <tr>
                    <td class="text-center"><?= $i++ ?></td>
                    <td class="text-center bold" style="font-size: 12px;">
                        #<?= str_pad($p['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?>
                        <?php if(!empty($p['RUT_ORIGEN']) && !empty($p['RUT_DESTINO'])): ?>
                            <br><span style="font-size: 9px; font-weight: bold; color: #0b2b56;">📍 <?= htmlspecialchars($p['RUT_ORIGEN'] . ' ➔ ' . $p['RUT_DESTINO']) ?></span>
                        <?php endif; ?>
                        <?php if(!empty($p['FECHA_DESPACHO'])): ?>
                            <br><span style="font-size: 8.5px; font-weight: normal; color: #555;">Despacho: <?= htmlspecialchars($p['FECHA_DESPACHO']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($p['FA_FECHA']) ?></td>
                    <td>
                        <span class="bold text-uppercase"><?= htmlspecialchars($p['REM_NOMBRE'].' '.$p['REM_APELLIDO']) ?></span><br>
                        CI: <?= htmlspecialchars($p['REM_CI']) ?><br>
                        Telf: <?= htmlspecialchars($p['REM_TEL']) ?>
                    </td>
                    <td>
                        <span class="bold text-uppercase"><?= htmlspecialchars(trim($p['DES_NOMBRE'].' '.$p['DES_APELLIDO'])) ?></span><br>
                        CI: <?= htmlspecialchars($p['DES_CI']) ?><br>
                        Telf: <?= htmlspecialchars($p['DES_TEL']) ?>
                        <?php if(!empty($p['DESTINO_FINAL'])): ?>
                            <br><span style="background-color: #000; color: #fff; padding: 2px 4px; font-weight: bold; font-size: 9px; border-radius: 2px;">➔ <?= htmlspecialchars($p['DESTINO_FINAL']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center bold">
                        <?= htmlspecialchars($p['ENC_CANTIDAD']) ?> <?= htmlspecialchars($p['ENC_TIPO']) ?>(s)
                    </td>
                    <td class="text-center">
                        <?= $p['ENC_PESO'] ?> Kg
                    </td>
                    <td><?= htmlspecialchars($p['ENC_CONTENIDO']) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="5" style="text-align:right; font-weight:bold; font-size: 12px; background:#f0f0f0;">
                        <?= !empty($esEnTransito) ? 'TOTAL CARGA FÍSICA EN TRÁNSITO ACTUALMENTE:' : (!empty($esConsolidado) ? 'TOTAL CARGA CONSOLIDADA DE ESTE VIAJE:' : 'TOTAL CARGA FÍSICA DE ESTE VIAJE:') ?>
                    </td>
                    <td colspan="3" style="font-weight:bold; font-size: 12px; background:#f0f0f0; text-align:center;">
                        <span style="font-size: 14px;"><?= $totalPiezas ?> PIEZA(S)</span> / <?= number_format($totalPeso, 2) ?> Kg
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-signatures">
        <div>
            <div class="sign-line"></div>
            <strong>ENTREGUÉ CONFORME (OFICINA)</strong>
        </div>
        <div>
            <div class="sign-line"></div>
            <strong>CHOFER / AYUDANTE (RECIBE)</strong>
        </div>
    </div>
</body>
</html>