<?php
$esConexion = (isset($factura['TIPO_VIAJE']) && $factura['TIPO_VIAJE'] === 'CONEXION');
$esPasaje = in_array($factura['FA_TIPO'] ?? '', ['PASAJE', 'RESERVA INICIAL']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= str_pad($factura['ID_FACTURA'] ?? 0, 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/ticket.css">
    <!-- Librería para generar el Código QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .ticket-page-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }
        .ticket-control-panel {
            width: 100%;
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(11, 43, 86, 0.12);
            padding: 12px 14px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }
        .ticket-control-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .ticket-control-title {
            font-size: 12px;
            font-weight: 700;
            color: #0b2b56;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .ticket-control-badge {
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
        }
        .mode-toggle-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .mode-toggle-btn {
            border: none;
            background: transparent;
            padding: 7px 4px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .mode-toggle-btn.active {
            background: #0b2b56;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.12);
        }
        .tramo-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .tramo-box-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }
        .tramo-box-select, .tramo-box-input {
            width: 100%;
            padding: 6px 8px;
            font-size: 11.5px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            outline: none;
            background: #fff;
            margin-bottom: 8px;
            box-sizing: border-box;
        }
        .tramo-box-select:focus, .tramo-box-input:focus {
            border-color: #0b2b56;
            box-shadow: 0 0 0 2px rgba(11, 43, 86, 0.15);
        }
        .btn-print-action {
            width: 100%;
            background: #16a34a;
            color: #ffffff;
            border: none;
            padding: 9px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);
            transition: background 0.15s ease;
        }
        .btn-print-action:hover {
            background: #15803d;
        }
        .tramo-preview-banner {
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #92400e;
            font-size: 10px;
            padding: 5px 8px;
            border-radius: 4px;
            margin-bottom: 8px;
            text-align: center;
            line-height: 1.3;
        }
        @media print {
            .ticket-page-wrapper {
                display: block !important;
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

<div class="ticket-page-wrapper">

    <?php if ($esConexion && $esPasaje): ?>
        <!-- PANEL DE CONTROL NO-PRINT PARA CONEXIONES (OPCIÓN 1) -->
        <div class="no-print ticket-control-panel">
            <div class="ticket-control-header">
                <span class="ticket-control-title">🔀 Impresión de Ruta</span>
                <span class="ticket-control-badge">CONEXIÓN</span>
            </div>
            
            <div class="mode-toggle-grid">
                <button type="button" id="btn_modo_completo" class="mode-toggle-btn active" onclick="cambiarModoImpresion('COMPLETO')">
                    🎫 Completo
                </button>
                <button type="button" id="btn_modo_tramo" class="mode-toggle-btn" onclick="cambiarModoImpresion('TRAMO')">
                    ✂️ Solo Tramo
                </button>
            </div>

            <div id="seccion_opciones_tramo" style="display: none;">
                <div class="tramo-preview-banner">
                    ⚠️ <strong>Modo Tramo Activado:</strong> El boleto se imprimirá con el tramo y monto elegido sin alterar la contabilidad general de caja.
                </div>

                <div class="tramo-box">
                    <label class="tramo-box-label" for="select_tramo_ticket">Seleccionar Tramo a Imprimir:</label>
                    <select id="select_tramo_ticket" class="tramo-box-select" onchange="alCambiarTramoTicket()">
                        <!-- Atajo frecuente sugerido para tramo venezolano -->
                        <option value="atajo_cucuta_caracas" data-origen="Cúcuta" data-destino="Caracas" data-precio="60.00" selected>
                            ⭐ Cúcuta ➔ Caracas ($60.00)
                        </option>
                        <option value="atajo_sc_caracas" data-origen="San Cristóbal" data-destino="Caracas" data-precio="60.00">
                            ⭐ San Cristóbal ➔ Caracas ($60.00)
                        </option>
                        <?php if (!empty($factura['TRAMOS'])): ?>
                            <optgroup label="Tramos de esta Ruta">
                                <?php foreach ($factura['TRAMOS'] as $idx => $tr): 
                                    $prc = floatval($tr['PRECIO_PASAJE'] ?? 0);
                                ?>
                                    <option value="tramo_<?= $idx ?>" 
                                            data-origen="<?= htmlspecialchars($tr['ORIGEN_TRAMO']) ?>" 
                                            data-destino="<?= htmlspecialchars($tr['DESTINO_TRAMO']) ?>" 
                                            data-precio="<?= number_format($prc, 2, '.', '') ?>">
                                        Tramo <?= $idx + 1 ?>: <?= htmlspecialchars($tr['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($tr['DESTINO_TRAMO']) ?> ($<?= number_format($prc, 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <option value="personalizado" data-origen="" data-destino="" data-precio="0.00">
                            ✏️ Otro Tramo Personalizado...
                        </option>
                    </select>

                    <div id="grupo_tramo_personalizado" style="display: none; margin-top: 4px;">
                        <label class="tramo-box-label">Origen Personalizado:</label>
                        <input type="text" id="input_origen_manual" class="tramo-box-input" placeholder="Ej: Cúcuta" oninput="aplicarSeleccionTramo()">
                        
                        <label class="tramo-box-label">Destino Personalizado:</label>
                        <input type="text" id="input_destino_manual" class="tramo-box-input" placeholder="Ej: Caracas" oninput="aplicarSeleccionTramo()">
                    </div>

                    <label class="tramo-box-label" for="input_precio_tramo">Monto a Imprimir en Boleto ($):</label>
                    <input type="number" step="0.01" id="input_precio_tramo" class="tramo-box-input" value="60.00" oninput="aplicarSeleccionTramo()" style="font-weight: bold; font-size: 13px; color: #0b2b56;">
                </div>
            </div>

            <button type="button" class="btn-print-action" onclick="window.print()">
                🖨️ <span id="lbl_texto_imprimir">Imprimir Boleto Completo</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="ticket">
        <?php if (isset($_GET['view']) || isset($_GET['scan'])): ?>
            <div class="no-print" style="background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; padding: 6px 8px; border-radius: 4px; margin-bottom: 12px; text-align: center; font-size: 11px; font-weight: bold;">
                ✔ COMPROBANTE VERIFICADO ONLINE
            </div>
        <?php endif; ?>
        <div class="text-center">
            <!-- LOGO DIRECTO DESDE ASSETS CON PROTECCIÓN ANTI-BLOQUEOS -->
            <img src="/suramerica2030/public/assets/img/logo.jpg" alt="Suramerica 2030" class="ticket-logo" onerror="this.style.display='none'">
            
            <p>Servicio de Transporte Internacional</p>
            <p>RUC: 3050310204001</p>
            <p>Teléfono: <?= htmlspecialchars($factura['TELEFONO'] ?? 'S/N') ?></p>
            <p>Agencia: <?= htmlspecialchars($factura['SUCURSAL'] ?? 'Central') ?></p>
            <p>Dirección: <?= htmlspecialchars($factura['DIRECCION'] ?? 'S/D') ?></p>
        </div>
        
        <div class="divider"></div>
        <div class="row"><span>TICKET NO:</span><span class="bold">#<?= str_pad($factura['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?></span></div>
        <div class="row"><span>FECHA:</span><span><?= $factura['FA_FECHA'] ?></span></div>
        <div class="row"><span>HORA:</span><span><?= $factura['FA_HORA'] ?></span></div>
        <div class="row"><span>CAJERO:</span><span><?= htmlspecialchars($factura['CAJERO']) ?></span></div>
        <div class="divider"></div>
        <?php if (($factura['ESTADO'] ?? '') === 'ANULADA' || (($factura['DATOS_SOBREPESO']['ESTADO'] ?? '') === 'ANULADO')): ?>
            <div style="background: #fee2e2; border: 2px dashed #b91c1c; color: #991b1b; padding: 6px; text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 6px; border-radius: 4px; text-transform: uppercase;">
                ⚠️ COMPROBANTE ANULADO ⚠️<br>
                <span style="font-size: 9px; font-weight: normal; color: #7f1d1d; display: block; margin-top: 2px;">
                    <?= !empty($factura['DATOS_SOBREPESO']['MOTIVO_ANULACION']) ? 'Motivo: ' . htmlspecialchars($factura['DATOS_SOBREPESO']['MOTIVO_ANULACION']) : 'Transacción revertida / sin validez' ?>
                    <?php if (!empty($factura['DATOS_SOBREPESO']['FECHA_ANULACION'])): ?>
                        <br>Anulado el: <?= htmlspecialchars($factura['DATOS_SOBREPESO']['FECHA_ANULACION']) ?>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
        <div class="text-center bold" style="font-size: 15px; margin-bottom: 5px; background: #000; color: #fff; padding: 3px;">
            *** <?= htmlspecialchars($factura['FA_TIPO']) ?> ***
        </div>
        
        <?php if ($factura['FA_TIPO'] == 'ENCOMIENDA'): ?>
            <div class="row"><span>REMITENTE (ENVÍA):</span><span class="text-right"><?= htmlspecialchars($factura['CLI_NOMBRE'] . ' ' . $factura['CLI_APELLIDO']) ?></span></div>
        <?php else: ?>
            <div class="row"><span>CLIENTE/TITULAR:</span><span class="text-right"><?= htmlspecialchars($factura['CLI_NOMBRE'] . ' ' . $factura['CLI_APELLIDO']) ?></span></div>
        <?php endif; ?>
        <div class="row"><span>CI/DOC:</span><span><?= htmlspecialchars($factura['ID_CLIENTE']) ?></span></div>
        <div class="divider"></div>
        
        <div class="row"><span>ORIGEN:</span><span id="lbl_origen_ticket"><?= htmlspecialchars($factura['RUT_ORIGEN']) ?></span></div>
        <?php if ($factura['FA_TIPO'] == 'ENCOMIENDA' && !empty($factura['DESTINO_FINAL'])): ?>
            <div class="row"><span>SUCURSAL:</span><span><?= htmlspecialchars($factura['RUT_DESTINO']) ?></span></div>
            <div class="row" style="margin-top: 4px;">
                <span class="bold">DEST. FINAL:</span>
                <span class="bold" style="border-bottom: 1px solid #000;">➔ <?= htmlspecialchars($factura['DESTINO_FINAL']) ?></span>
            </div>
        <?php else: ?>
            <div class="row"><span>DESTINO:</span><span id="lbl_destino_ticket"><?= htmlspecialchars($factura['RUT_DESTINO']) ?></span></div>
        <?php endif; ?>

        <?php if ($factura['FA_TIPO'] == 'PASAJE' || $factura['FA_TIPO'] == 'RESERVA INICIAL'): ?>
            <?php if (isset($factura['TIPO_VIAJE']) && $factura['TIPO_VIAJE'] === 'CONEXION' && !empty($factura['TRAMOS'])): ?>
                <div id="bloque_itinerario_general">
                    <div class="divider"></div>
                    <div class="text-center bold" style="font-size: 11px;">--- ITINERARIO DE TRAMOS ---</div>
                    <?php foreach($factura['TRAMOS'] as $index => $tramo): ?>
                        <div class="row" style="font-size: 10.5px; margin-top: 2px;">
                            <span>TRAMO <?= $index + 1 ?>:</span>
                            <span class="text-right"><?= htmlspecialchars($tramo['ORIGEN_TRAMO']) ?> ➔ <?= htmlspecialchars($tramo['DESTINO_TRAMO']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="divider"></div>
                </div>
            <?php endif; ?>

            <?php if ($esConexion): ?>
                <div id="bloque_tramo_individual" style="display: none;">
                    <div class="divider"></div>
                    <div class="text-center bold" style="font-size: 11px; background: #000; color: #fff; padding: 2px;">
                        *** BOLETO VÁLIDO PARA TRAMO ***
                    </div>
                    <div class="text-center bold" style="font-size: 12px; margin: 4px 0;" id="lbl_tramo_emitido">
                        -
                    </div>
                    <div style="font-size: 8.5px; text-align: center; color: #444; margin-bottom: 3px;">
                        (Conexión Pasaje General #<?= str_pad($factura['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?>)
                    </div>
                    <div class="divider"></div>
                </div>
            <?php endif; ?>
            
            <div class="row mt-2"><span>FECHA VIAJE:</span><span class="bold"><?= $factura['FECHA_VIAJE'] ?> <?= $factura['RUT_SAHORA'] ?></span></div>
            <div class="row mt-2"><span>BUS (PLACA):</span><span><?= htmlspecialchars($factura['BUS_PLACA']) ?></span></div>
            <div class="row"><span>CANTIDAD:</span><span><?= htmlspecialchars($factura['FA_CANTIDAD'] ?? 1) ?> Pasajero(s)</span></div>
            <div class="row"><span>ASIENTOS:</span><span class="bold" style="font-size: 14px;">[ <?= htmlspecialchars($factura['PAX_ASIENTO'] ?? 'S/A') ?> ]</span></div>
            
            <!-- DETALLE DESGLOSADO DE CADA PASAJERO -->
            <?php if (!empty($factura['PASAJEROS'])): ?>
                <div class="divider"></div>
                <div class="text-center bold" style="font-size: 11px; margin-bottom: 5px;">--- DETALLE DE PASAJEROS (<?= count($factura['PASAJEROS']) ?>) ---</div>
                <?php foreach($factura['PASAJEROS'] as $idx => $pax): 
                    $nombrePax = trim(($pax['CLI_NOMBRE'] ?? '') . ' ' . ($pax['CLI_APELLIDO'] ?? ''));
                    if (empty($nombrePax)) $nombrePax = 'Pasajero ' . ($idx + 1);
                ?>
                    <div style="margin-bottom: 5px; padding-bottom: 4px; <?= ($idx < count($factura['PASAJEROS']) - 1) ? 'border-bottom: 1px dotted #888;' : '' ?>">
                        <div class="row">
                            <span class="bold"><?= $idx + 1 ?>. <?= htmlspecialchars($nombrePax) ?></span>
                            <span class="bold" style="font-size: 11.5px; background: #eee; padding: 1px 4px; border: 1px solid #333; border-radius: 2px;">Asiento: <?= htmlspecialchars($pax['PAX_ASIENTO'] ?? 'S/A') ?></span>
                        </div>
                        <div class="row" style="font-size: 10.5px; color: #333; margin-top: 1px;">
                            <span>Doc: <?= htmlspecialchars($pax['ID_CLIENTE'] ?? 'S/N') ?></span>
                        </div>
                        <?php if (!empty($pax['CLI_TELEFONO'])): ?>
                            <div class="row" style="font-size: 10px; color: #555;">
                                <span>Telf: <?= htmlspecialchars($pax['CLI_TELEFONO']) ?></span>
                                <span>Boleto #<?= $pax['ID_PASAJE'] ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php elseif ($factura['FA_TIPO'] == 'ENCOMIENDA'): ?>
            <div class="divider"></div>
            <div class="text-center bold">DESTINATARIO (QUIEN RECIBE)</div>
            <div class="row"><span>Nombre:</span><span class="text-right bold"><?= htmlspecialchars(trim(($factura['DES_NOMBRE'] ?? '') . ' ' . ($factura['DES_APELLIDO'] ?? ''))) ?: 'S/N' ?></span></div>
            <div class="row"><span>CI/DOC:</span><span><?= htmlspecialchars($factura['DES_CI'] ?? 'S/N') ?></span></div>
            <?php if (!empty($factura['DES_TEL'])): ?>
                <div class="row"><span>Telf:</span><span><?= htmlspecialchars($factura['DES_TEL']) ?></span></div>
            <?php endif; ?>

            <div class="divider"></div>
            <div class="text-center bold">DATOS DEL PAQUETE</div>
            <div class="row"><span>Cant/Tipo:</span><span><?= htmlspecialchars($factura['ENC_CANTIDAD'] ?? 1) ?> <?= htmlspecialchars($factura['TIPO_PAQUETE']) ?>(s)</span></div>
            <div class="row"><span>Peso:</span><span><?= htmlspecialchars($factura['ENC_PESO']) ?>Kg</span></div>
            <div class="row"><span>Contenido:</span><span class="text-right"><?= htmlspecialchars($factura['ENC_CONTENIDO']) ?></span></div>
        <?php elseif ($factura['FA_TIPO'] == 'VALOR'): ?>
            <div class="divider"></div>
            <div class="text-center bold">DESTINATARIO (QUIEN RECIBE)</div>
            <div class="row"><span>Nombre:</span><span class="text-right"><?= htmlspecialchars(trim($factura['VAL_DES_NOMBRE'].' '.$factura['VAL_DES_APELLIDO'])) ?></span></div>
            <div class="row"><span>CI/DOC:</span><span><?= htmlspecialchars($factura['VAL_DES_CI']) ?></span></div>
            <div class="row"><span>Telf:</span><span><?= htmlspecialchars($factura['VAL_DES_TEL']) ?></span></div>
            <div class="row mt-2"><span>DINERO ENVIADO:</span><span class="bold">$<?= number_format($factura['FA_SUBTOTAL'], 2) ?></span></div>
        <?php elseif ($factura['FA_TIPO'] == 'ABONO PASAJE'): ?>
            <div class="row mt-2"><span>VALOR ABONADO:</span><span class="bold">$<?= number_format($factura['FA_TOTAL'], 2) ?></span></div>
            <div class="text-center mt-2" style="font-size: 10px;">(Abono aplicado a reserva previa)</div>
            <?php if (!empty($factura['PASAJEROS'])): ?>
                <div class="divider"></div>
                <div class="text-center bold" style="font-size: 10.5px; margin-bottom: 4px;">--- PASAJERO(S) VINCULADO(S) ---</div>
                <?php foreach($factura['PASAJEROS'] as $idx => $pax): 
                    $nombrePax = trim(($pax['CLI_NOMBRE'] ?? '') . ' ' . ($pax['CLI_APELLIDO'] ?? ''));
                ?>
                    <div class="row" style="font-size: 10.5px;">
                        <span class="bold"><?= htmlspecialchars($nombrePax) ?></span>
                        <span>Asiento: <?= htmlspecialchars($pax['PAX_ASIENTO'] ?? 'S/A') ?></span>
                    </div>
                    <div class="row" style="font-size: 10px; color: #444;">
                        <span>Doc: <?= htmlspecialchars($pax['ID_CLIENTE']) ?></span>
                        <span>Saldo Restante: $<?= number_format($pax['SALDO'] ?? 0, 2) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php elseif ($factura['FA_TIPO'] == 'SOBREPESO'): ?>
            <div class="divider"></div>
            <div class="text-center bold" style="font-size: 11px; background: #000; color: #fff; padding: 2px;">
                *** CONTROL DE EQUIPAJE Y SOBREPESO ***
            </div>
            
            <?php if (!empty($factura['DATOS_SOBREPESO']['SP_OPERADOR'])): ?>
                <div class="row mt-2" style="font-size: 11px;">
                    <span class="bold">OPERADOR / SERVICIO:</span>
                    <span class="bold text-right" style="color: #0b2b56;"><?= htmlspecialchars($factura['DATOS_SOBREPESO']['SP_OPERADOR']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($factura['DATOS_SOBREPESO']['SP_TRAMO'])): ?>
                <div class="row" style="font-size: 11px;">
                    <span>TRAMO AMPARADO:</span>
                    <span class="bold text-right"><?= htmlspecialchars($factura['DATOS_SOBREPESO']['SP_TRAMO']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($factura['DATOS_SOBREPESO']['DESTINO_FINAL_PASAJE'])): ?>
                <div class="row" style="font-size: 10px; color: #555;">
                    <span>DESTINO FINAL BOLETO:</span>
                    <span class="bold text-right"><?= htmlspecialchars($factura['DATOS_SOBREPESO']['DESTINO_FINAL_PASAJE']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($factura['DATOS_SOBREPESO']['ID_PASAJE'])): ?>
                <div class="row" style="font-size: 10px; color: #555;">
                    <span>N° BOLETO PASAJERO:</span>
                    <span class="bold text-right">#<?= htmlspecialchars($factura['DATOS_SOBREPESO']['ID_PASAJE']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($factura['DATOS_SOBREPESO']['PAX_ASIENTO'])): ?>
                <div class="row" style="font-size: 10px; color: #555;">
                    <span>ASIENTO PASAJERO:</span>
                    <span class="bold text-right">[ <?= htmlspecialchars($factura['DATOS_SOBREPESO']['PAX_ASIENTO']) ?> ]</span>
                </div>
            <?php endif; ?>

            <div class="divider"></div>
            <div class="text-center bold" style="font-size: 10.5px; margin-bottom: 3px;">--- DETALLE DEL EXCESO ---</div>
            
            <?php if (!empty($factura['DATOS_SOBREPESO']['SP_PESO'])): ?>
                <div class="row" style="font-size: 10.5px;"><span>PESO TOTAL BODEGA:</span><span><?= number_format($factura['DATOS_SOBREPESO']['SP_PESO'], 1) ?> Kg</span></div>
            <?php endif; ?>
            <div class="row" style="font-size: 10.5px;"><span>PESO EXCESO COBRADO:</span><span class="bold"><?= number_format($factura['DATOS_SOBREPESO']['SP_EXCESO_KG'] ?? 0, 1) ?> Kg</span></div>
            <div class="row" style="font-size: 10.5px;"><span>TARIFA SOBREPESO:</span><span>$<?= number_format($factura['DATOS_SOBREPESO']['SP_TARIFA_KG'] ?? 1.00, 2) ?> / Kg</span></div>

            <?php
            $piezas = [];
            if (!empty($factura['DATOS_SOBREPESO']['PIEZAS_DETALLE'])) {
                $decoded = json_decode($factura['DATOS_SOBREPESO']['PIEZAS_DETALLE'], true);
                if (is_array($decoded)) $piezas = $decoded;
            } elseif (!empty($factura['DATOS_SOBREPESO']['EQUIPAJE_DETALLE'])) {
                $decoded = json_decode($factura['DATOS_SOBREPESO']['EQUIPAJE_DETALLE'], true);
                if (is_array($decoded)) $piezas = $decoded;
            }
            ?>

            <?php if (!empty($piezas)): ?>
                <div class="divider"></div>
                <div class="text-center bold" style="font-size: 10.5px; margin-bottom: 4px;">--- PIEZAS REGISTRADAS (<?= count($piezas) ?>) ---</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 4px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #333; text-align: left;">
                            <th style="padding: 2px 0;">TAG / ID</th>
                            <th style="padding: 2px 0;">TIPO</th>
                            <th style="padding: 2px 0; text-align: right;">PESO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($piezas as $pz): ?>
                            <tr style="border-bottom: 1px dotted #ccc;">
                                <td style="padding: 2px 0; font-family: monospace; font-weight: bold; font-size: 10px; color: #0b2b56;">
                                    <?= htmlspecialchars($pz['codigo'] ?? $pz['tag'] ?? 'EQP-S/N') ?>
                                </td>
                                <td style="padding: 2px 0;">
                                    <?= htmlspecialchars($pz['tipo'] ?? 'Bulto') ?>
                                    <?php if (!empty($pz['descripcion'])): ?>
                                        <div style="font-size: 8px; color: #555;"><?= htmlspecialchars($pz['descripcion']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 2px 0; text-align: right; font-weight: bold;">
                                    <?= number_format(floatval($pz['peso'] ?? 0), 1) ?> Kg
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div style="font-size: 9px; color: #444; margin-top: 5px; text-align: center; border: 1px dashed #666; padding: 3px; border-radius: 3px;">
                * Válido únicamente en el servicio y tramo indicado hasta frontera/transbordo.
            </div>
        <?php endif; ?>

        <div class="divider"></div>
        <div class="row bold" style="font-size: 15px;"><span>TOTAL COBRADO:</span><span id="lbl_total_ticket">$ <?= number_format($factura['FA_TOTAL'], 2) ?></span></div>
        <div class="row"><span>MÉTODO PAGO:</span><span class="bold"><?= htmlspecialchars($factura['FA_METODO']) ?></span></div>
        <?php if (!empty($factura['DESGLOSE_PAGOS']) && (strtoupper($factura['FA_METODO'] ?? '') === 'MIXTO' || count($factura['DESGLOSE_PAGOS']) > 1)): ?>
            <div id="bloque_desglose_pagos" style="font-size: 11px; margin: 3px 0; padding: 3px 6px; background: #f4f6f9; border-left: 2px solid #0b2b56;">
                <?php foreach ($factura['DESGLOSE_PAGOS'] as $dp): ?>
                    <div class="row" style="margin-bottom: 2px;">
                        <span style="color: #444;">▪ <?= htmlspecialchars($dp['METODO_PAGO']) ?>:</span>
                        <span class="bold">$ <?= number_format($dp['MONTO'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- LEYENDA LEGAL DINÁMICA -->
        <?php if ($factura['FA_TIPO'] == 'PASAJE' || $factura['FA_TIPO'] == 'RESERVA INICIAL'): ?>
            <?php 
                // Obtenemos el límite global configurado en la base de datos (por defecto 35)
                $limite_total = floatval($factura['LIMITE_EQUIPAJE_KG'] ?? 35);
                // Calculamos cuánto corresponde a bodega restando los 10kg fijos del bolso de mano
                $limite_maleta = max(0, $limite_total - 10);
            ?>
            <div class="divider"></div>
            <div class="legal-notice">
                * Todo pasajero tiene derecho a una maleta de <?= $limite_maleta ?> KG y bolso de mano de 10 KG. Todo exceso será cobrado.
            </div>
        <?php endif; ?>
        
        <div class="divider"></div>
        <div id="qrcode" style="display: flex; justify-content: center; margin: 10px 0;"></div>
        <div style="font-size: 8px; text-align: center; color: #444; margin-top: -4px; margin-bottom: 6px;">
            QR de autenticidad de la factura
        </div>
        
        <div class="divider"></div>
        
        <?php if ($factura['FA_TIPO'] === 'SOBREPESO'): ?>
            <div class="divider" style="border-top: 1px dashed #000; margin: 12px 0 6px 0;"></div>
            <div style="text-align: center; font-size: 8.5px; font-weight: bold; letter-spacing: 1px; color: #555;">- - - - - - - CORTAR AQUÍ - - - - - - -</div>
            <div style="margin-top: 6px; padding: 6px; border: 1px solid #000; border-radius: 4px; background: #fafafa;">
                <div class="text-center bold" style="font-size: 11px; text-transform: uppercase;">TALÓN DE RECLAMO BODEGA</div>
                <div class="text-center" style="font-size: 8.5px; color: #555;">SURAMÉRICA 2030 - CONTROL EQUIPAJE</div>
                <div class="row mt-1" style="font-size: 10px;">
                    <span>BOLETO:</span><span class="bold">#<?= htmlspecialchars($factura['DATOS_SOBREPESO']['ID_PASAJE'] ?? $factura['ID_FACTURA']) ?></span>
                </div>
                <div class="row" style="font-size: 10px;">
                    <span>PASAJERO:</span><span class="bold text-right"><?= htmlspecialchars(trim($factura['CLI_NOMBRE'] . ' ' . $factura['CLI_APELLIDO'])) ?></span>
                </div>
                <div class="row" style="font-size: 10px;">
                    <span>ASIENTO:</span><span class="bold">[ <?= htmlspecialchars($factura['DATOS_SOBREPESO']['PAX_ASIENTO'] ?? 'S/A') ?> ]</span>
                </div>
                <div class="row" style="font-size: 10px;">
                    <span>BULTOS:</span><span class="bold"><?= !empty($piezas) ? count($piezas) . ' pieza(s)' : '1 pieza' ?></span>
                </div>
                <div class="row" style="font-size: 10px;">
                    <span>PESO BODEGA:</span><span class="bold"><?= number_format($factura['DATOS_SOBREPESO']['SP_PESO'] ?? 0, 1) ?> Kg</span>
                </div>
                <div style="font-size: 8px; text-align: center; color: #444; margin-top: 4px;">
                    Presente este talón al conductor o auxiliar para retirar sus bultos en destino.
                </div>
            </div>
            <div class="divider"></div>
        <?php endif; ?>

        <div class="text-center footer">
            <p>¡Gracias por preferir Suramerica 2030!</p>
            <p>Conserve este ticket como comprobante.</p>
            <br>
            <button class="no-print" style="padding: 10px; cursor: pointer; background: #0b2b56; color: white; border: none; border-radius: 5px;" onclick="window.print()">🖨️ Re-Imprimir</button>
            <br><br>
            <button class="no-print" style="border: none; background: transparent; color: #0b2b56; text-decoration: underline; cursor: pointer;" onclick="window.close()">Cerrar Ventana</button>
        </div>
    </div>
</div>

<script>
    // Enlace directo a la factura para que al escanear con la cámara abra el comprobante correspondiente
    let qrData = window.location.origin + "/suramerica2030/ventas/ticket/<?= $factura['ID_FACTURA'] ?>?view=1";

    new QRCode(document.getElementById("qrcode"), {
        text: qrData,
        width: 110,
        height: 110,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.M
    });

    <?php if ($esConexion && $esPasaje): ?>
    const FACTURA_ORIGINAL = {
        origen: <?= json_encode($factura['RUT_ORIGEN'] ?? '') ?>,
        destino: <?= json_encode($factura['RUT_DESTINO'] ?? '') ?>,
        totalFmt: <?= json_encode(number_format($factura['FA_TOTAL'], 2)) ?>
    };

    function cambiarModoImpresion(modo) {
        const btnCompleto = document.getElementById('btn_modo_completo');
        const btnTramo = document.getElementById('btn_modo_tramo');
        const secTramo = document.getElementById('seccion_opciones_tramo');
        const bloqueItinerario = document.getElementById('bloque_itinerario_general');
        const bloqueTramo = document.getElementById('bloque_tramo_individual');
        const lblOrigen = document.getElementById('lbl_origen_ticket');
        const lblDestino = document.getElementById('lbl_destino_ticket');
        const lblTotal = document.getElementById('lbl_total_ticket');
        const bloqueDesglose = document.getElementById('bloque_desglose_pagos');
        const lblBtnImprimir = document.getElementById('lbl_texto_imprimir');

        if (modo === 'COMPLETO') {
            if (btnCompleto) btnCompleto.classList.add('active');
            if (btnTramo) btnTramo.classList.remove('active');
            if (secTramo) secTramo.style.display = 'none';
            
            if (lblOrigen) lblOrigen.textContent = FACTURA_ORIGINAL.origen;
            if (lblDestino) lblDestino.textContent = FACTURA_ORIGINAL.destino;
            if (lblTotal) lblTotal.textContent = '$ ' + FACTURA_ORIGINAL.totalFmt;
            if (bloqueItinerario) bloqueItinerario.style.display = 'block';
            if (bloqueTramo) bloqueTramo.style.display = 'none';
            if (bloqueDesglose) bloqueDesglose.style.display = 'block';
            if (lblBtnImprimir) lblBtnImprimir.textContent = 'Imprimir Boleto Completo';
        } else {
            if (btnCompleto) btnCompleto.classList.remove('active');
            if (btnTramo) btnTramo.classList.add('active');
            if (secTramo) secTramo.style.display = 'block';
            
            if (bloqueItinerario) bloqueItinerario.style.display = 'none';
            if (bloqueTramo) bloqueTramo.style.display = 'block';
            if (bloqueDesglose) bloqueDesglose.style.display = 'none';
            if (lblBtnImprimir) lblBtnImprimir.textContent = 'Imprimir Solo Este Tramo';

            aplicarSeleccionTramo();
        }
    }

    function alCambiarTramoTicket() {
        const sel = document.getElementById('select_tramo_ticket');
        if (!sel) return;
        const opt = sel.options[sel.selectedIndex];
        const grupoPers = document.getElementById('grupo_tramo_personalizado');
        const inputPrecio = document.getElementById('input_precio_tramo');

        if (sel.value === 'personalizado') {
            if (grupoPers) grupoPers.style.display = 'block';
        } else {
            if (grupoPers) grupoPers.style.display = 'none';
            const precio = opt.getAttribute('data-precio') || '0.00';
            if (inputPrecio) inputPrecio.value = parseFloat(precio).toFixed(2);
        }
        aplicarSeleccionTramo();
    }

    function aplicarSeleccionTramo() {
        const sel = document.getElementById('select_tramo_ticket');
        if (!sel) return;
        const opt = sel.options[sel.selectedIndex];
        const inputPrecio = document.getElementById('input_precio_tramo');
        const lblOrigen = document.getElementById('lbl_origen_ticket');
        const lblDestino = document.getElementById('lbl_destino_ticket');
        const lblTramo = document.getElementById('lbl_tramo_emitido');
        const lblTotal = document.getElementById('lbl_total_ticket');

        let origen = '';
        let destino = '';

        if (sel.value === 'personalizado') {
            origen = (document.getElementById('input_origen_manual')?.value.trim()) || 'Origen';
            destino = (document.getElementById('input_destino_manual')?.value.trim()) || 'Destino';
        } else {
            origen = opt.getAttribute('data-origen') || FACTURA_ORIGINAL.origen;
            destino = opt.getAttribute('data-destino') || FACTURA_ORIGINAL.destino;
        }

        const precioVal = parseFloat(inputPrecio ? inputPrecio.value : 0) || 0;
        const precioFmt = precioVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (lblOrigen) lblOrigen.textContent = origen;
        if (lblDestino) lblDestino.textContent = destino;
        if (lblTramo) lblTramo.textContent = origen + ' ➔ ' + destino;
        if (lblTotal) lblTotal.textContent = '$ ' + precioFmt;
    }
    <?php endif; ?>

    // Control de autoimpresión: Solo si se abre para imprimir en PC y no viene como visualización/escaneo
    const urlParams = new URLSearchParams(window.location.search);
    const isMobile = /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);

    <?php if ($esConexion && $esPasaje): ?>
    // Para rutas con conexión, se da tiempo al cajero a elegir si imprime completo o solo tramo
    <?php else: ?>
    if (!urlParams.has('view') && !urlParams.has('scan') && !isMobile) {
        window.onload = function() { 
            setTimeout(() => { window.print(); }, 800); 
        };
    }
    <?php endif; ?>
</script>
</body>
</html>