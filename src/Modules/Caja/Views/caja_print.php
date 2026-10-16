<?php
$pasajes = []; $encomiendas = []; $valores = []; $otros_ingresos = []; $egresos = [];
$subtotalPasajes = 0; $subtotalEncomiendas = 0; $subtotalValores = 0; $subtotalOtros = 0; $subtotalEgresos = 0;

$es_global = (isset($sucursal_id) && ($sucursal_id === 'ALL' || $sucursal_id === 'ALL_MONTH'));
$col_span_main = $es_global ? 7 : 6;
$col_span_sub = $es_global ? 5 : 4;

foreach ($movimientos as $mov) {
    if ($mov['TIPO_MOVIMIENTO'] == 'EGRESO') {
        $egresos[] = $mov; $subtotalEgresos += $mov['MONTO'];
    } else {
        $origen = $mov['ORIGEN_FACTURA'] ?? '';
        $concepto = strtoupper($mov['CONCEPTO']);
        if (in_array($origen, ['PASAJE', 'RESERVA INICIAL', 'ABONO PASAJE', 'SOBREPESO']) || strpos($concepto, 'PASAJE') !== false || strpos($concepto, 'EQUIPAJE') !== false) {
            $pasajes[] = $mov; $subtotalPasajes += $mov['MONTO'];
        } elseif ($origen == 'ENCOMIENDA' || strpos($concepto, 'ENCOMIENDA') !== false) {
            $encomiendas[] = $mov; $subtotalEncomiendas += $mov['MONTO'];
        } elseif ($origen == 'VALOR' || strpos($concepto, 'VALOR') !== false) {
            $valores[] = $mov; $subtotalValores += $mov['MONTO'];
        } else {
            $otros_ingresos[] = $mov; $subtotalOtros += $mov['MONTO'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja - <?= htmlspecialchars($fecha) ?></title>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/caja_print.css">
</head>
<body>
<div class="no-print"><button class="btn-print" onclick="window.print()">🖨️ Imprimir Formato Cierre</button></div>
<div class="container">
    <div class="header"><h2>SURAMERICA 2030 S.A.</h2><p>REPORTE CONTABLE Y FINANCIERO</p></div>
    <div class="info-box">
        <div class="info-item"><strong>SUCURSAL:</strong><span><?= htmlspecialchars($nombre_sucursal_impresion ?? 'S/R') ?></span></div>
        <div class="info-item">
            <strong>PERIODO SELECCIONADO:</strong>
            <?php if(isset($es_mensual) && $es_mensual): ?>
                <span>MES DE <?= mb_strtoupper(date('F Y', strtotime($fecha))) ?></span>
            <?php else: ?>
                <span>DÍA: <?= htmlspecialchars($fecha) ?></span>
            <?php endif; ?>
        </div>
        <div class="info-item text-right">
            <strong>ESTADO DEL REPORTE:</strong>
            <?php if($cierreDia && (!isset($es_mensual) || !$es_mensual)): ?>
                <span style="color: #dc3545;">CERRADA Y AUDITADA</span>
            <?php else: ?>
                <span><?= (isset($es_mensual) && $es_mensual) ? 'CONSOLIDADO MENSUAL' : 'PRE-CIERRE EN PROCESO (ABIERTA)' ?></span>
            <?php endif; ?>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: <?= $es_global ? '8' : '10' ?>%;">FACTURA</th>
                <th style="width: <?= $es_global ? '10' : '12' ?>%;">FECHA / HORA</th>
                <?php if($es_global): ?><th style="width: 10%;">SUCURSAL</th><?php endif; ?>
                <th style="width: <?= $es_global ? '32' : '38' ?>%;">DETALLE O CONCEPTO</th>
                <th style="width: 15%;">PAGO</th>
                <th style="width: 12.5%;">INGRESO (+)</th>
                <th style="width: 12.5%;">EGRESO (-)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pasajes)): ?>
                <tr><td colspan="<?= $col_span_main ?>" class="group-header">🎫 Ingresos por Venta de Pasajes y Equipaje</td></tr>
                <?php foreach($pasajes as $m): ?>
                    <tr>
                        <td class="td-center"><?= $m['ID_FACTURA'] ? '#'.str_pad($m['ID_FACTURA'], 5, '0', STR_PAD_LEFT) : '-' ?></td>
                        <td class="td-center"><?= (isset($es_mensual) && $es_mensual) ? date('d/M H:i', strtotime($m['FECHA_HORA'])) : date('H:i', strtotime($m['FECHA_HORA'])) ?></td>
                        <?php if($es_global): ?><td class="td-center fw-bold text-primary"><?= htmlspecialchars($m['SUCURSAL_NOMBRE'] ?? 'S/R') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars($m['CONCEPTO']) ?></td>
                        <td class="td-center"><?= $m['METODO_PAGO'] ?></td>
                        <td class="td-money">$<?= number_format($m['MONTO'], 2) ?></td>
                        <td class="td-money">-</td>
                    </tr>
                <?php endforeach; ?>
                <tr><td colspan="<?= $col_span_sub ?>" class="group-subtotal">Subtotal Pasajes:</td><td class="td-money text-success">$<?= number_format($subtotalPasajes, 2) ?></td><td></td></tr>
            <?php endif; ?>
            
            <?php if (!empty($encomiendas)): ?>
                <tr><td colspan="<?= $col_span_main ?>" class="group-header">📦 Ingresos por Encomiendas</td></tr>
                <?php foreach($encomiendas as $m): ?>
                    <tr>
                        <td class="td-center"><?= $m['ID_FACTURA'] ? '#'.str_pad($m['ID_FACTURA'], 5, '0', STR_PAD_LEFT) : '-' ?></td>
                        <td class="td-center"><?= (isset($es_mensual) && $es_mensual) ? date('d/M H:i', strtotime($m['FECHA_HORA'])) : date('H:i', strtotime($m['FECHA_HORA'])) ?></td>
                        <?php if($es_global): ?><td class="td-center fw-bold text-primary"><?= htmlspecialchars($m['SUCURSAL_NOMBRE'] ?? 'S/R') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars($m['CONCEPTO']) ?></td>
                        <td class="td-center"><?= $m['METODO_PAGO'] ?></td>
                        <td class="td-money">$<?= number_format($m['MONTO'], 2) ?></td>
                        <td class="td-money">-</td>
                    </tr>
                <?php endforeach; ?>
                <tr><td colspan="<?= $col_span_sub ?>" class="group-subtotal">Subtotal Encomiendas:</td><td class="td-money text-success">$<?= number_format($subtotalEncomiendas, 2) ?></td><td></td></tr>
            <?php endif; ?>

            <?php if (!empty($valores)): ?>
                <tr><td colspan="<?= $col_span_main ?>" class="group-header">💸 Ingresos por Envío de Valores</td></tr>
                <?php foreach($valores as $m): ?>
                    <tr>
                        <td class="td-center"><?= $m['ID_FACTURA'] ? '#'.str_pad($m['ID_FACTURA'], 5, '0', STR_PAD_LEFT) : '-' ?></td>
                        <td class="td-center"><?= (isset($es_mensual) && $es_mensual) ? date('d/M H:i', strtotime($m['FECHA_HORA'])) : date('H:i', strtotime($m['FECHA_HORA'])) ?></td>
                        <?php if($es_global): ?><td class="td-center fw-bold text-primary"><?= htmlspecialchars($m['SUCURSAL_NOMBRE'] ?? 'S/R') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars($m['CONCEPTO']) ?></td>
                        <td class="td-center"><?= $m['METODO_PAGO'] ?></td>
                        <td class="td-money">$<?= number_format($m['MONTO'], 2) ?></td>
                        <td class="td-money">-</td>
                    </tr>
                <?php endforeach; ?>
                <tr><td colspan="<?= $col_span_sub ?>" class="group-subtotal">Subtotal Valores:</td><td class="td-money text-success">$<?= number_format($subtotalValores, 2) ?></td><td></td></tr>
            <?php endif; ?>

            <?php if (!empty($otros_ingresos)): ?>
                <tr><td colspan="<?= $col_span_main ?>" class="group-header">⚙️ Otros Ingresos Diversos</td></tr>
                <?php foreach($otros_ingresos as $m): ?>
                    <tr>
                        <td class="td-center"><?= $m['ID_FACTURA'] ? '#'.str_pad($m['ID_FACTURA'], 5, '0', STR_PAD_LEFT) : '-' ?></td>
                        <td class="td-center"><?= (isset($es_mensual) && $es_mensual) ? date('d/M H:i', strtotime($m['FECHA_HORA'])) : date('H:i', strtotime($m['FECHA_HORA'])) ?></td>
                        <?php if($es_global): ?><td class="td-center fw-bold text-primary"><?= htmlspecialchars($m['SUCURSAL_NOMBRE'] ?? 'S/R') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars($m['CONCEPTO']) ?></td>
                        <td class="td-center"><?= $m['METODO_PAGO'] ?></td>
                        <td class="td-money">$<?= number_format($m['MONTO'], 2) ?></td>
                        <td class="td-money">-</td>
                    </tr>
                <?php endforeach; ?>
                <tr><td colspan="<?= $col_span_sub ?>" class="group-subtotal">Subtotal Otros:</td><td class="td-money text-success">$<?= number_format($subtotalOtros, 2) ?></td><td></td></tr>
            <?php endif; ?>

            <?php if (!empty($egresos)): ?>
                <tr><td colspan="<?= $col_span_main ?>" class="group-header" style="background-color: #f8d7da; color: #721c24;">🛑 Egresos, Gastos y Devoluciones</td></tr>
                <?php foreach($egresos as $m): ?>
                    <tr>
                        <td class="td-center"><?= $m['ID_FACTURA'] ? '#'.str_pad($m['ID_FACTURA'], 5, '0', STR_PAD_LEFT) : '-' ?></td>
                        <td class="td-center"><?= (isset($es_mensual) && $es_mensual) ? date('d/M H:i', strtotime($m['FECHA_HORA'])) : date('H:i', strtotime($m['FECHA_HORA'])) ?></td>
                        <?php if($es_global): ?><td class="td-center fw-bold text-primary"><?= htmlspecialchars($m['SUCURSAL_NOMBRE'] ?? 'S/R') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars($m['CONCEPTO']) ?></td>
                        <td class="td-center"><?= $m['METODO_PAGO'] ?></td>
                        <td class="td-money">-</td>
                        <td class="td-money text-danger">-$<?= number_format($m['MONTO'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr><td colspan="<?= $col_span_sub ?>" class="group-subtotal" style="background-color: #f8d7da;">Total Egresos:</td><td class="td-money text-danger" style="background-color: #f8d7da;">-$<?= number_format($subtotalEgresos, 2) ?></td></tr>
            <?php endif; ?>

            <?php if (empty($movimientos)): ?>
                <tr><td colspan="<?= $col_span_main ?>" class="text-center" style="padding: 20px;">No hubo movimientos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-row"><span>Fondo Bruto Captado Físico:</span><span>$<?= number_format($totales['ingresos'], 2) ?></span></div>
        <div class="summary-row" style="color:#dc3545;"><span>Egresos / Devoluciones:</span><span>-$<?= number_format($totales['egresos'], 2) ?></span></div>
        <div class="summary-row" style="background: #f8f9fa;"><span>SALDO FÍSICO ARQUEADO:</span><span class="text-primary fw-bold">$<?= number_format($totales['saldo_neto'], 2) ?></span></div>
        
        <div style="border-top: 2px dashed #0b2b56; margin: 12px 0;"></div>
        <div style="text-align:center; font-weight:bold; font-size:10px; margin-bottom:5px;">RESERVA DE FONDOS Y COMISIONES</div>
        <div class="summary-row" style="color:#dc3545;"><span>Capital Reposición Terceros:</span><span>-$<?= number_format($totales['capital_reposicion'], 2) ?></span></div>
        <div class="summary-row" style="color:#dc3545;"><span>Comisiones Vendedores:</span><span>-$<?= number_format($totales['comisiones'], 2) ?></span></div>
        <div class="summary-row" style="color:#dc3545;"><span>Gastos Operativos:</span><span>-$<?= number_format($totales['egresos_gastos'], 2) ?></span></div>
        <div class="summary-row" style="background: #e8f5e9; color: #198754; font-size: 13px;">
            <span style="font-weight: bold;">UTILIDAD NETA EMPRESA:</span>
            <span style="font-weight: bold;">$<?= number_format($totales['utilidad_neta'], 2) ?></span>
        </div>

        <div style="border-top: 2px dashed #0b2b56; margin: 12px 0;"></div>
        <div style="text-align:center; font-weight:bold; font-size:10px; margin-bottom:5px; color:#0b2b56; letter-spacing:0.5px;">LIQUIDACIÓN DE DEPÓSITO BANCARIO</div>
        <div class="summary-row" style="font-size:11px;"><span>Saldo Físico en Gaveta:</span><span>$<?= number_format($totales['saldo_neto'], 2) ?></span></div>
        <div class="summary-row" style="color:#dc3545; font-size:11px;"><span>(-) Retención Comisión Oficina:</span><span>-$<?= number_format($totales['comisiones'], 2) ?></span></div>
        <div class="summary-row" style="background: #0b2b56; color: white; font-size: 15px; font-weight: bold; border-radius: 0 0 3px 3px;">
            <span style="color: #fff;">VALOR EXIGIBLE A DEPOSITAR:</span>
            <span style="color: #ffc107;">$<?= number_format($totales['valor_a_depositar'], 2) ?></span>
        </div>
    </div>

    <div class="signatures">
        <div class="signature-block"><div class="signature-line"></div><div class="signature-title">ENTREGUÉ CONFORME (CAJERO)</div></div>
        <div class="signature-block"><div class="signature-line"></div><div class="signature-title">RECIBÍ CONFORME (ADMIN)</div></div>
    </div>
</div>
<script>window.onload = function() { setTimeout(() => { window.print(); }, 500); }</script>
</body>
</html>