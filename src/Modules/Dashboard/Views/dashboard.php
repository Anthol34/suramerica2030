<!-- ========================================== -->
<!-- VISTA ESCRITORIO (PC) - 100% INTACTA       -->
<!-- ========================================== -->
<div class="d-none d-md-block">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">👋 Bienvenido(a), <?= htmlspecialchars($_SESSION['nombre'] ?? 'Equipo') ?></h2>
        <span class="text-muted fw-bold bg-white px-3 py-2 rounded shadow-sm border">
            📅 <?= date('d M Y') ?> | 📍 Sucursal #<?= htmlspecialchars($_SESSION['sucursal_id'] ?? 1) ?>
        </span>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card text-white bg-success shadow border-0 h-100" style="background: linear-gradient(45deg, #28a745, #20c997);"><div class="card-body"><h6 class="card-title text-uppercase fw-bold opacity-75"><small>Ingresos de Hoy (Caja)</small></h6><h2 class="mb-0">$ <?= number_format($stats['ingresos_hoy'], 2) ?></h2></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-primary shadow border-0 h-100" style="background: linear-gradient(45deg, #0b2b56, #007bff);"><div class="card-body"><h6 class="card-title text-uppercase fw-bold opacity-75"><small>Pasajes Vendidos Hoy</small></h6><h2 class="mb-0">🎫 <?= $stats['pasajes_hoy'] ?></h2></div></div></div>
        <div class="col-md-3"><div class="card text-dark bg-warning shadow border-0 h-100" style="background: linear-gradient(45deg, #ffc107, #ffdf7e);"><div class="card-body"><h6 class="card-title text-uppercase fw-bold opacity-75"><small>Valores por Entregar</small></h6><h2 class="mb-0">💸 <?= $stats['valores_pendientes'] ?></h2></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-info shadow border-0 h-100" style="background: linear-gradient(45deg, #17a2b8, #36b9cc);"><div class="card-body"><h6 class="card-title text-uppercase fw-bold opacity-75"><small>Encomiendas Activas</small></h6><h2 class="mb-0">📦 <?= $stats['encomiendas_activas'] ?></h2></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white fw-bold">Últimas 5 Transacciones Registradas</div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Factura</th><th>Hora</th><th>Cliente</th><th>Servicio</th><th class="text-end">Monto Cobrado</th></tr></thead>
                        <tbody>
                            <?php if(empty($actividad)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Aún no hay ventas registradas en esta sucursal.</td></tr>
                            <?php else: ?>
                                <?php foreach($actividad as $act): ?>
                                    <tr>
                                        <td><a href="/suramerica2030/ventas/ticket/<?= $act['ID_FACTURA'] ?>" target="_blank" class="text-decoration-none" title="Ver ticket / recibo"><span class="badge bg-secondary">#<?= str_pad($act['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?></span></a></td>
                                        <td class="text-muted"><?= $act['FA_HORA'] ?></td>
                                        <td><?= htmlspecialchars($act['CLI_NOMBRE'] . ' ' . $act['CLI_APELLIDO']) ?></td>
                                        <td>
                                            <?php 
                                                if($act['FA_TIPO'] == 'PASAJE' || $act['FA_TIPO'] == 'RESERVA INICIAL') echo '<span class="badge bg-primary">🎫 Boleto</span>';
                                                elseif($act['FA_TIPO'] == 'ABONO PASAJE') echo '<span class="badge bg-primary">🎫 Abono</span>';
                                                elseif($act['FA_TIPO'] == 'ENCOMIENDA') echo '<span class="badge bg-info text-dark">📦 Encomienda</span>';
                                                elseif($act['FA_TIPO'] == 'SOBREPESO') echo '<span class="badge bg-danger">⚖️ Sobrepeso</span>';
                                                elseif($act['FA_TIPO'] == 'VALOR') echo '<span class="badge bg-warning text-dark">💸 Valores</span>';
                                                else echo '<span class="badge bg-secondary">' . htmlspecialchars($act['FA_TIPO']) . '</span>';
                                            ?>
                                        </td>
                                        <td class="text-end fw-bold text-success">$ <?= number_format($act['FA_TOTAL'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white fw-bold">🚀 Accesos Rápidos</div>
                <div class="card-body d-flex flex-column gap-3 justify-content-center">
                    <a href="/suramerica2030/pasajes" class="btn btn-outline-primary text-start fw-bold p-3">🎫 Vender Pasaje</a>
                    <a href="/suramerica2030/encomiendas" class="btn btn-outline-info text-dark text-start fw-bold p-3">📦 Recibir Encomienda</a>
                    <a href="/suramerica2030/valores" class="btn btn-outline-warning text-dark text-start fw-bold p-3">💸 Envío / Entrega de Valores</a>
                    <?php if (isset($_SESSION['p_caja']) && $_SESSION['p_caja'] == 1): ?>
                    <a href="/suramerica2030/caja" class="btn btn-outline-success text-start fw-bold p-3">💰 Ver Cuadre de Caja</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- VISTA MÓVIL (CELULARES)                    -->
<!-- ========================================== -->
<div class="d-md-none mobile-dashboard-view">
    <!-- Usuario y Sucursal -->
    <div class="d-flex justify-content-between align-items-center bg-white p-3 border-bottom">
        <div>
            <div class="fw-bold" style="color: #0b2b56; font-size: 1.05rem;">
                <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?> (<?= htmlspecialchars($_SESSION['nombre_rol'] ?? 'Admin') ?>)
            </div>
            <div class="text-muted" style="font-size: 0.85rem;">
                <?= htmlspecialchars($_SESSION['ciudad_sucursal'] ?? 'Manta') ?> - Ecuador
            </div>
        </div>
        <div>
            <span class="badge" style="background-color: #e8eef6; color: #1a437a; font-weight: 600; padding: 7px 12px; font-size: 0.85rem; border-radius: 6px;">
                Sucursal #<?= htmlspecialchars($_SESSION['sucursal_id'] ?? 1) ?>
            </span>
        </div>
    </div>

    <!-- Sección: Resumen del Día -->
    <div class="px-3 py-2 fw-bold text-uppercase" style="background-color: #e9ecef; color: #495057; font-size: 0.8rem; letter-spacing: 0.5px;">
        RESUMEN DEL DÍA (CAJA)
    </div>
    <div class="bg-white">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="text-dark" style="font-size: 0.95rem;">Ingresos en Caja</span>
            <span class="fw-bold text-success" style="font-size: 1rem;">$ <?= number_format($stats['ingresos_hoy'], 2) ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="text-dark" style="font-size: 0.95rem;">Pasajes Vendidos Hoy</span>
            <span class="fw-bold text-dark" style="font-size: 1rem;"><?= $stats['pasajes_hoy'] ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="text-dark" style="font-size: 0.95rem;">Valores por Entregar</span>
            <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 0.9rem; border-radius: 4px;"><?= $stats['valores_pendientes'] ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="text-dark" style="font-size: 0.95rem;">Encomiendas Activas</span>
            <span class="fw-bold text-dark" style="font-size: 1rem;"><?= $stats['encomiendas_activas'] ?></span>
        </div>
    </div>

    <!-- Sección: Últimas 5 Transacciones -->
    <div class="px-3 py-2 fw-bold text-uppercase mt-2" style="background-color: #e9ecef; color: #495057; font-size: 0.8rem; letter-spacing: 0.5px;">
        ÚLTIMAS 5 TRANSACCIONES
    </div>
    <div class="bg-white">
        <?php if(empty($actividad)): ?>
            <div class="text-center text-muted py-4">Aún no hay ventas registradas en esta sucursal.</div>
        <?php else: ?>
            <?php foreach($actividad as $act): ?>
                <div class="px-3 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($act['CLI_NOMBRE'] . ' ' . $act['CLI_APELLIDO']) ?></span>
                        <span class="fw-bold text-success" style="font-size: 1rem;">$ <?= number_format($act['FA_TOTAL'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted" style="font-size: 0.8rem;">
                            <a href="/suramerica2030/ventas/ticket/<?= $act['ID_FACTURA'] ?>" target="_blank" class="text-decoration-none text-muted">Fact #<?= str_pad($act['ID_FACTURA'], 6, '0', STR_PAD_LEFT) ?></a> &nbsp;<?= $act['FA_HORA'] ?>
                        </small>
                        <?php 
                            if($act['FA_TIPO'] == 'PASAJE' || $act['FA_TIPO'] == 'RESERVA INICIAL') {
                                echo '<span class="badge bg-primary px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;">Boleto</span>';
                            } elseif($act['FA_TIPO'] == 'ABONO PASAJE') {
                                echo '<span class="badge bg-primary px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;">Abono</span>';
                            } elseif($act['FA_TIPO'] == 'ENCOMIENDA') {
                                echo '<span class="badge bg-info text-dark px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;">Encomienda</span>';
                            } elseif($act['FA_TIPO'] == 'SOBREPESO') {
                                echo '<span class="badge bg-danger px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;">⚖️ Sobrepeso</span>';
                            } elseif($act['FA_TIPO'] == 'VALOR') {
                                echo '<span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;">Valores</span>';
                            } else {
                                echo '<span class="badge bg-secondary px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;">' . htmlspecialchars($act['FA_TIPO']) . '</span>';
                            }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>