<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte Z - Cierre #<?= htmlspecialchars($cierre['CODIGO_CIERRE'] ?? $cierre['ID_CIERRE']) ?></title>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/ticket.css">
    <style>
        .ticket-cierre {
            width: 290px;
            max-width: 100%;
            background-color: white;
            padding: 10px 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
            font-family: Arial, monospace, sans-serif;
        }
        .header-box {
            text-align: center;
            margin-bottom: 8px;
        }
        .section-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 6px 0 3px 0;
            padding: 2px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            background: #f8f9fa;
        }
        .row-money {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        .highlight-box {
            background: #eee;
            padding: 3px 5px;
            border: 1px solid #444;
            border-radius: 3px;
            margin: 4px 0;
        }
        .signatures {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .sig-block {
            flex: 1;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        @media print {
            .ticket-cierre {
                box-shadow: none !important;
                width: 100% !important;
                max-width: 71mm !important;
                margin: 0 auto !important;
                padding: 0 3.5mm 2mm 1mm !important;
            }
        }
    </style>
</head>
<body>

<div class="ticket-cierre">
    <div class="header-box">
        <img src="/suramerica2030/public/assets/img/logo.jpg" alt="Suramerica 2030" class="ticket-logo" onerror="this.style.display='none'">
        <p class="bold" style="font-size: 12px; margin-bottom: 1px;">SURAMERICA 2030 S.A.</p>
        <p style="font-size: 9.5px;">RUC: 3050310204001</p>
        <p style="font-size: 9.5px;">Agencia: <?= htmlspecialchars($cierre['SUCURSAL_NOMBRE'] ?? 'Central') ?></p>
        <p style="font-size: 9px;"><?= htmlspecialchars($cierre['SUCURSAL_DIRECCION'] ?? 'S/D') ?></p>
    </div>

    <div class="divider"></div>
    <div class="text-center bold" style="font-size: 13px; background: #000; color: #fff; padding: 3px;">
        *** CIERRE DE CAJA (CORTE Z) ***
    </div>

    <div style="font-size: 10.5px; margin-top: 6px;">
        <div class="row"><span>CORTE NO:</span><span class="bold">#<?= htmlspecialchars($cierre['CODIGO_CIERRE'] ?? str_pad($cierre['ID_CIERRE'], 6, '0', STR_PAD_LEFT)) ?></span></div>
        <div class="row"><span>FECHA CAJA:</span><span class="bold"><?= $cierre['FECHA_CAJA'] ?></span></div>
        <div class="row"><span>APERTURA:</span><span><?= !empty($cierre['APERTURA_FECHA_HORA']) ? date('d/m/Y H:i', strtotime($cierre['APERTURA_FECHA_HORA'])) : 'Sin registro' ?></span></div>
        <div class="row"><span>CIERRE:</span><span><?= !empty($cierre['FECHA_HORA']) ? date('d/m/Y H:i', strtotime($cierre['FECHA_HORA'])) : date('d/m/Y H:i') ?></span></div>
        <div class="row"><span>CAJERO:</span><span class="bold"><?= htmlspecialchars($cierre['CAJERO_CIERRE'] ?? ($cierre['CAJERO_NOMBRE'] ?? 'Cajero')) ?></span></div>
        <div class="row"><span>ESTADO:</span><span class="bold" style="color: <?= $cierre['ESTADO'] === 'CERRADO' ? '#15803d' : '#b45309' ?>;">[ <?= htmlspecialchars($cierre['ESTADO']) ?> ]</span></div>
    </div>

    <!-- 1. ARQUEO Y CUADRE DE EFECTIVO -->
    <div class="section-title">--- ARQUEO Y CUADRE DE EFECTIVO ---</div>
    
    <?php 
        $valorInicial = floatval($cierre['VALOR_INICIAL'] ?? 0);
        $efectivoVentas = floatval($desglose['ingresos']['pasajes']['efectivo'] + $desglose['ingresos']['encomiendas']['efectivo'] + $desglose['ingresos']['valores']['efectivo'] + $desglose['ingresos']['sobrepeso']['efectivo'] + $desglose['ingresos']['abonos']['efectivo'] + $desglose['ingresos']['manuales']['efectivo']);
        $egresosEfectivo = floatval($desglose['egresos']['gastos']['efectivo'] + $desglose['egresos']['devoluciones']['efectivo']);
        $efectivoEsperado = $valorInicial + $efectivoVentas - $egresosEfectivo;
        $efectivoFisico = floatval($cierre['EFECTIVO_FISICO'] ?? $cierre['SALDO_FINAL']);
        $diferencia = floatval($cierre['DIFERENCIA_ARQUEO'] ?? ($efectivoFisico - $efectivoEsperado));
    ?>

    <div class="row-money"><span>Fondo Inicial (Base):</span><span class="bold">$ <?= number_format($valorInicial, 2) ?></span></div>
    <div class="row-money"><span>(+) Ventas en Efectivo:</span><span>$ <?= number_format($efectivoVentas, 2) ?></span></div>
    <div class="row-money"><span>(-) Egresos / Gastos Efectivo:</span><span>-$ <?= number_format($egresosEfectivo, 2) ?></span></div>
    <div class="divider"></div>
    <div class="row-money bold" style="font-size: 11px;"><span>(=) Efectivo Teórico en Gaveta:</span><span>$ <?= number_format($efectivoEsperado, 2) ?></span></div>
    <div class="row-money bold" style="font-size: 12px; color: #0b2b56;"><span>Efectivo Físico Declarado:</span><span>$ <?= number_format($efectivoFisico, 2) ?></span></div>

    <!-- CAJA DE DIFERENCIA -->
    <div class="highlight-box text-center">
        <?php if(abs($diferencia) < 0.01): ?>
            <span class="bold" style="font-size: 11px; color: #15803d;">✔ CUADRE EXACTO ($ 0.00)</span>
        <?php elseif($diferencia < 0): ?>
            <span class="bold" style="font-size: 11px; color: #b91c1c;">✖ FALTANTE: -$ <?= number_format(abs($diferencia), 2) ?></span>
        <?php else: ?>
            <span class="bold" style="font-size: 11px; color: #1d4ed8;">⚠ SOBRANTE: +$ <?= number_format($diferencia, 2) ?></span>
        <?php endif; ?>
    </div>

    <!-- 2. TOTALES POR FORMA DE PAGO -->
    <div class="section-title">--- RECAUDACIÓN POR FORMA DE PAGO ---</div>
    <div class="row-money"><span>Efectivo:</span><span class="bold">$ <?= number_format($totales['efectivo'] ?? 0, 2) ?></span></div>
    <div class="row-money"><span>Transferencias:</span><span class="bold">$ <?= number_format($totales['transferencia'] ?? 0, 2) ?></span></div>
    <div class="row-money"><span>Tarjetas (POS):</span><span class="bold">$ <?= number_format($totales['tarjeta'] ?? 0, 2) ?></span></div>
    <div class="divider"></div>
    <div class="row-money bold" style="font-size: 11.5px;"><span>TOTAL VENTAS DEL DÍA:</span><span>$ <?= number_format($totales['ingresos'] ?? 0, 2) ?></span></div>

    <!-- 3. DESGLOSE POR SERVICIOS -->
    <div class="section-title">--- DESGLOSE POR SERVICIOS ---</div>
    <?php foreach($desglose['ingresos'] as $item): ?>
        <?php if($item['cantidad'] > 0 || $item['total'] > 0): ?>
            <div class="row-money">
                <span><?= $item['icono'] ?> <?= htmlspecialchars($item['nombre']) ?> (<?= $item['cantidad'] ?>):</span>
                <span class="bold">$ <?= number_format($item['total'], 2) ?></span>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- 4. OBLIGACIÓN BANCARIA -->
    <div class="section-title">--- OBLIGACIÓN BANCARIA ---</div>
    <div class="row-money"><span>Comisiones Retenidas:</span><span>-$ <?= number_format($cierre['COMISION_EMPLEADOS'] ?? 0, 2) ?></span></div>
    <div class="row-money bold" style="font-size: 11.5px; color: #0b2b56;">
        <span>Exigible a Depositar:</span>
        <span>$ <?= number_format($cierre['VALOR_A_DEPOSITAR'] ?? 0, 2) ?></span>
    </div>
    <div class="row-money" style="font-size: 10px;">
        <span>Estado de Depósito:</span>
        <span class="bold"><?= htmlspecialchars($cierre['DEPOSITO_ESTADO'] ?? 'PENDIENTE') ?></span>
    </div>

    <?php if(!empty($cierre['OBSERVACIONES_CIERRE'])): ?>
        <div class="divider"></div>
        <div style="font-size: 9.5px;">
            <strong>Observaciones del Cierre:</strong><br>
            <em><?= nl2br(htmlspecialchars($cierre['OBSERVACIONES_CIERRE'])) ?></em>
        </div>
    <?php endif; ?>

    <!-- FIRMAS DE RESPONSABILIDAD -->
    <div class="signatures">
        <div class="sig-block">
            <br><br>
            Firma Cajero<br>
            <span style="font-size: 8px; font-weight: normal;"><?= htmlspecialchars($cierre['CAJERO_CIERRE'] ?? 'Cajero') ?></span>
        </div>
        <div class="sig-block">
            <br><br>
            Firma Supervisor<br>
            <span style="font-size: 8px; font-weight: normal;">Revisado / Entrega</span>
        </div>
    </div>

    <div class="divider" style="margin-top: 15px;"></div>
    <div class="text-center footer">
        <p>Suramerica 2030 - Sistema de Control y Operaciones</p>
        <p>Comprobante Oficial de Arqueo y Cierre</p>
        <br>
        <button class="no-print" style="padding: 9px 16px; cursor: pointer; background: #0b2b56; color: white; border: none; border-radius: 5px; font-weight: bold;" onclick="window.print()">🖨️ Imprimir Ticket (80mm)</button>
        <br><br>
        <button class="no-print" style="border: none; background: transparent; color: #0b2b56; text-decoration: underline; cursor: pointer; font-size: 11px;" onclick="window.close()">Cerrar Ventana</button>
    </div>
</div>

<script>
    // Auto-impresión inmediata en PC
    window.onload = function() {
        const isMobile = /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
        if (!isMobile) {
            setTimeout(() => { window.print(); }, 600);
        }
    };
</script>

</body>
</html>
