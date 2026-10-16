<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manifiesto de Equipaje - <?= htmlspecialchars($fecha) ?></title>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/equipaje_lista_print.css">
</head>
<body>
    <div class="no-print text-center" style="margin-bottom:20px;">
        <button onclick="window.print()" style="padding: 10px; background: #0b2b56; color: white; border: none; border-radius: 5px; cursor:pointer; font-weight:bold;">🖨️ Imprimir / Guardar PDF</button>
        <button onclick="window.close()" style="padding: 10px; background: #ccc; border: none; border-radius: 5px; cursor:pointer; margin-left:10px;">Cerrar Ventana</button>
    </div>

    <h2 class="text-center bold" style="margin:0;"><?= htmlspecialchars($empresa) ?></h2>
    <h3 class="text-center bold" style="margin:5px 0;">MANIFIESTO DE EQUIPAJE REGISTRADO</h3>
    
    <div style="display:flex; justify-content:space-between; margin-top:15px; font-size:12px;">
        <div><strong>RUTA ASIGNADA:</strong> <?= htmlspecialchars($origenPrint) ?> ➔ <?= htmlspecialchars($destinoPrint) ?></div>
        <div><strong>BUS PLACA:</strong> <?= htmlspecialchars($bus['BUS_PLACA'] ?? 'CONSOLIDADO') ?></div>
        <div><strong>FECHA DE VIAJE:</strong> <?= htmlspecialchars($fecha) ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 8%;">ASIENTO</th>
                <th style="width: 32%;">PASAJERO</th>
                <th style="width: 15%;">DOC. IDENTIDAD</th>
                <th style="width: 15%;">N° BOLETO / FACT.</th>
                <th style="width: 12.5%;">PESO BODEGA</th>
                <th style="width: 12.5%;">PESO MANO</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($equipajes)): ?>
                <tr><td colspan="7" class="text-center" style="padding:20px; font-size: 12px; color: #555;">No se han registrado pesos de equipaje para los pasajeros de este viaje.</td></tr>
            <?php else: $i = 1; $totalBodega = 0; $totalMano = 0; ?>
                <?php foreach($equipajes as $eq): 
                    $totalBodega += floatval($eq['PESO_BODEGA']); 
                    $totalMano += floatval($eq['PESO_MANO']);
                ?>
                <tr>
                    <td class="text-center"><?= $i++ ?></td>
                    <td class="text-center bold" style="font-size: 14px;"><?= htmlspecialchars($eq['PAX_ASIENTO'] ?? 'S/A') ?></td>
                    <td><span class="bold text-uppercase"><?= htmlspecialchars($eq['CLI_APELLIDO'].' '.$eq['CLI_NOMBRE']) ?></span></td>
                    <td class="text-center"><?= htmlspecialchars($eq['ID_CLIENTE']) ?></td>
                    <td class="text-center bold text-uppercase">#<?= str_pad($eq['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?></td>
                    <td class="text-right"><?= number_format($eq['PESO_BODEGA'], 1) ?> Kg</td>
                    <td class="text-right"><?= number_format($eq['PESO_MANO'], 1) ?> Kg</td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="5" style="text-align:right; font-weight:bold; font-size: 12px; background:#f0f0f0;">TOTALES CARGA EQUIPAJE:</td>
                    <td class="text-right bold" style="font-size: 12px; background:#f0f0f0;"><?= number_format($totalBodega, 1) ?> Kg</td>
                    <td class="text-right bold" style="font-size: 12px; background:#f0f0f0;"><?= number_format($totalMano, 1) ?> Kg</td>
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