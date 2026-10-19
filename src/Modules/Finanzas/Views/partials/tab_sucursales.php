    <!-- PESTAÑA SUCURSALES -->
    <div class="tab-pane fade <?= ($tabActivo == 'sucursales') ? 'show active' : '' ?>" id="sucursales">
        <div class="card shadow-sm border-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Sucursal</th>
                        <th class="text-center text-warning">Comisión Empleados (%)</th>
                        <th class="text-center text-danger">Gasto Fijo Diario ($)</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sucursales as $s): ?>
                        <tr>
                            <td class="fw-bold fs-6">📍 <?= htmlspecialchars($s['CIUDAD'] . ' - ' . $s['PAIS']) ?></td>
                            <td class="text-center fw-bold text-warning" style="background:#fff3cd;"><?= htmlspecialchars($s['COMISION_EMPLEADO_PCT'] ?? '0.00') ?> %</td>
                            <td class="text-center fw-bold text-danger" style="background:#f8d7da;">$ <?= number_format($s['GASTO_FIJO_DIARIO'] ?? 0, 2) ?> / día</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalSucursal<?= $s['ID_SUCURSAL'] ?>">⚙️ Configurar</button>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalSucursal<?= $s['ID_SUCURSAL'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header bg-dark text-white"><h5 class="modal-title fs-6">Configurar: <?= htmlspecialchars($s['CIUDAD']) ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                    <form action="/suramerica2030/finanzas/guardarSucursal" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id_sucursal" value="<?= $s['ID_SUCURSAL'] ?>">
                                            <div class="mb-3">
                                                <label class="text-warning fw-bold">Comisión de Ventas (%)</label>
                                                <input type="number" step="0.1" name="comision" class="form-control fw-bold" value="<?= $s['COMISION_EMPLEADO_PCT'] ?? '0.00' ?>" required>
                                                <small class="text-muted">Se descontará de la caja diaria.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="text-danger fw-bold">Gastos Operativos Diarios ($)</label>
                                                <input type="number" step="0.01" name="gasto" class="form-control fw-bold" value="<?= $s['GASTO_FIJO_DIARIO'] ?? '0.00' ?>" required>
                                                <small class="text-muted">Ej: Arriendo diario, comida, luz.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer p-1"><button type="submit" class="btn btn-success w-100 fw-bold">Guardar</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

