<?php $editAttr = isset($is_email) ? '' : 'contenteditable="true"'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manifiesto de Viaje - <?= htmlspecialchars($fecha) ?></title>
    <link rel="stylesheet" href="/suramerica2030/public/assets/css/manifiesto_print.css">
    <?php if(isset($is_email) && $is_email): ?>
        <style><?= @file_get_contents(dirname(__DIR__, 4) . '/public/assets/css/manifiesto_print.css') ?></style>
    <?php endif; ?>
</head>
<body class="<?= isset($is_email) && $is_email ? 'is-email' : '' ?>">

<?php if(!isset($is_email)): ?>
<div class="no-print" style="max-width: 1100px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; background: #0b2b56; color: white; padding: 12px 20px; border-radius: 6px;">
    <div>
        <strong>✏️ Modo Edición En Vivo Activo:</strong>
        <span style="font-size: 12px; opacity: 0.9; margin-left: 8px;">Haz clic en cualquier texto del documento para modificarlo antes de imprimir.</span>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="agregarFilaPasajero()" class="btn-add-row" style="margin-top: 0;">+ Agregar Pasajero</button>
        <button onclick="window.print()" style="padding: 6px 18px; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">🖨️ Imprimir / Guardar PDF</button>
    </div>
</div>
<?php endif; ?>

<div class="paper-container">

    <h2 class="text-center bold" <?= $editAttr ?> style="margin: 0; font-size: 15px;"><?= htmlspecialchars($empresa) ?></h2>
    <h3 class="text-center bold" <?= $editAttr ?> style="margin: 4px 0 12px 0; font-size: 13px;">
        <?php 
            if(!empty($esHoy)) echo "LISTA GENERAL DE PASAJEROS EN RUTA (HOY)";
            elseif(!empty($esConsolidado)) echo "LISTA DE PASAJEROS INTERNACIONALES (MANIFIESTO CONSOLIDADO)";
            else echo "LISTA DE PASAJEROS INTERNACIONALES";
        ?>
    </h3>

    <table class="header-table">
        <tr>
            <td style="width: 38%;">
                <strong>TRANSPORTISTA AUTORIZADO:</strong><br>
                <span <?= $editAttr ?>><?= htmlspecialchars($empresa) ?></span>
            </td>
            <td style="width: 34%;">
                <strong>IDENTIFICACION VEHICULO:</strong><br>
                Placa: <span <?= $editAttr ?>><?= htmlspecialchars($bus['BUS_PLACA'] ?? '') ?></span>
            </td>
            <td style="width: 28%;">
                <strong>FECHA:</strong> <span <?= $editAttr ?>><?= htmlspecialchars($fecha) ?></span>
            </td>
        </tr>
        <tr>
            <td>
                <strong>PROPIETARIO:</strong><br>
                <span <?= $editAttr ?>>COOPERATIVA DE TRANSPORTES PANAMERICANA</span>
            </td>
            <td>
                <strong>PAIS DE MATRICULA:</strong> <span <?= $editAttr ?>>ECUADOR</span><br>
                <strong>COLOR:</strong> <span <?= $editAttr ?>>ROJO/CREMA</span>
            </td>
            <td>
                <strong>PAIS DE ORIGEN:</strong> <span <?= $editAttr ?>>ECUADOR</span>
            </td>
        </tr>
        <tr>
            <td>
                <strong>ITINERARIO / RUTA:</strong><br>
                <span <?= $editAttr ?>><?= htmlspecialchars($itinerarioPrint ?? '') ?></span>
            </td>
            <td>
                <strong>N.- DE MOTOR:</strong> <span <?= $editAttr ?>>S/N</span><br>
                <strong>N.- CHASIS:</strong> <span <?= $editAttr ?>>S/N</span>
            </td>
            <td>
                <strong>PAIS DESTINO:</strong> <span <?= $editAttr ?>><?= htmlspecialchars($paisDestino) ?></span>
            </td>
        </tr>
    </table>

    <table>
        <tr><th colspan="3" style="background: #e9e9e9;">IDENTIFICACION DE LA TRIPULACION</th></tr>
        <tr>
            <th style="width: 33%;">NOMBRES Y APELLIDOS</th>
            <th style="width: 33%;">NOMBRES Y APELLIDOS</th>
            <th style="width: 34%;">NOMBRES Y APELLIDOS</th>
        </tr>
        <tr>
            <td <?= $editAttr ?> style="height: 20px;">CHOFER 1: </td>
            <td <?= $editAttr ?>>CHOFER 2: </td>
            <td <?= $editAttr ?>>AUXILIAR: </td>
        </tr>
        <tr><th>NACIONALIDAD</th><th>NACIONALIDAD</th><th>NACIONALIDAD</th></tr>
        <tr>
            <td <?= $editAttr ?> style="height: 20px;">ECUATORIANA</td>
            <td <?= $editAttr ?>>ECUATORIANA</td>
            <td <?= $editAttr ?>>ECUATORIANA</td>
        </tr>
        <tr><th>DOC. IDENTIDAD</th><th>DOC. IDENTIDAD</th><th>DOC. IDENTIDAD</th></tr>
        <tr>
            <td <?= $editAttr ?> style="height: 20px;"></td>
            <td <?= $editAttr ?>></td>
            <td <?= $editAttr ?>></td>
        </tr>
        <tr><th>LIB. TRIP. TERRESTRE</th><th>LIB. TRIP. TERRESTRE</th><th>LIB. TRIP. TERRESTRE</th></tr>
        <tr>
            <td <?= $editAttr ?> style="height: 20px;"></td>
            <td <?= $editAttr ?>></td>
            <td <?= $editAttr ?>></td>
        </tr>
    </table>

    <table id="tablaPasajeros" style="margin-top: 12px;">
        <thead>
            <tr style="background: #e9e9e9;">
                <th style="width: 3%;">N°</th>
                <th style="width: 7%;">N° ASIENTO</th>
                <th style="width: 16%;">APELLIDOS</th>
                <th style="width: 16%;">NOMBRES</th>
                <th style="width: 11%;">NACIONALIDAD</th>
                <th style="width: 11%;">DOC. IDENT.</th>
                <th style="width: 10%;">FECHA N.</th>
                <th style="width: 8%;">ORIGEN</th>
                <th style="width: 8%;">DEST.</th>
                <th style="width: 10%;">ST. MIGRATORIA</th>
                <?php if(!isset($is_email)): ?><th class="no-print" style="width: 3%;"></th><?php endif; ?>
            </tr>
        </thead>
        <tbody id="bodyPasajeros">
            <?php 
            $contador = 1;
            if (!empty($pasajeros)):
                foreach($pasajeros as $pax): 
                    $fechaNac = !empty($pax['CLI_FECHANAC']) ? date('d.m.Y', strtotime($pax['CLI_FECHANAC'])) : '';
            ?>
                <tr>
                    <td class="num-row"><?= $contador++ ?></td>
                    <td <?= $editAttr ?>><?= htmlspecialchars($pax['PAX_ASIENTO'] ?? '') ?></td>
                    <td <?= $editAttr ?>><?= htmlspecialchars($pax['CLI_APELLIDO'] ?? '') ?></td>
                    <td <?= $editAttr ?>><?= htmlspecialchars($pax['CLI_NOMBRE'] ?? '') ?></td>
                    <td <?= $editAttr ?>><?= htmlspecialchars($pax['NACIONALIDAD'] ?? 'ECUATORIANA') ?></td>
                    <td <?= $editAttr ?>><?= htmlspecialchars($pax['ID_CLIENTE'] ?? '') ?></td>
                    <td <?= $editAttr ?>><?= $fechaNac ?></td>
                    <td <?= $editAttr ?>><?= htmlspecialchars(!empty($pax['RUT_ORIGEN']) ? mb_strtoupper($pax['RUT_ORIGEN']) : $origenPrint) ?></td>
                    <td <?= $editAttr ?>><?= htmlspecialchars(!empty($pax['RUT_DESTINO']) ? mb_strtoupper($pax['RUT_DESTINO']) : $destinoPrint) ?></td>
                    <td <?= $editAttr ?>>NO SELLA</td>
                    <?php if(!isset($is_email)): ?>
                    <td class="no-print">
                        <button class="btn-delete-row" onclick="eliminarFila(this)" title="Eliminar Pasajero">×</button>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php 
                endforeach;
            endif; 
            ?>
        </tbody>
    </table>
</div>

<?php if(!isset($is_email)): ?>
<script>
    function reenumerarFilas() {
        const filas = document.querySelectorAll('#bodyPasajeros tr');
        filas.forEach((tr, index) => {
            const tdNum = tr.querySelector('.num-row');
            if (tdNum) { tdNum.textContent = index + 1; }
        });
    }

    function eliminarFila(btn) {
        const tr = btn.closest('tr');
        if (tr) { tr.remove(); reenumerarFilas(); }
    }

    function agregarFilaPasajero() {
        const tbody = document.getElementById('bodyPasajeros');
        const numFilas = tbody.querySelectorAll('tr').length + 1;
        
        let origenDefault = "MANTA";
        let destinoDefault = "CARACAS";
        const primeraFila = tbody.querySelector('tr');
        if (primeraFila) {
            const tds = primeraFila.querySelectorAll('td');
            if (tds.length >= 9) {
                origenDefault = tds[7].textContent.trim();
                destinoDefault = tds[8].textContent.trim();
            }
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="num-row">${numFilas}</td>
            <td contenteditable="true"></td>
            <td contenteditable="true">APELLIDO</td>
            <td contenteditable="true">NOMBRE</td>
            <td contenteditable="true">ECUATORIANA</td>
            <td contenteditable="true"></td>
            <td contenteditable="true"></td>
            <td contenteditable="true">${origenDefault}</td>
            <td contenteditable="true">${destinoDefault}</td>
            <td contenteditable="true">NO SELLA</td>
            <td class="no-print">
                <button class="btn-delete-row" onclick="eliminarFila(this)" title="Eliminar Pasajero">×</button>
            </td>
        `;
        tbody.appendChild(tr);
        reenumerarFilas();
    }
</script>
<?php endif; ?>

<?php if(!isset($is_email)): ?>
<script src="/suramerica2030/public/assets/js/sanitizar-inputs.js"></script>
<?php endif; ?>
</body>
</html>