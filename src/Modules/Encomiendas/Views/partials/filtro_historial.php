<!-- BUSCADOR E HISTORIAL -->
<?php if(isset($filtros['tab']) && $filtros['tab'] == 'historial'): ?>
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <form action="/suramerica2030/encomiendas" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="historial">
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary">Buscar (Cédula o Nombre)</label>
                <input type="text" name="buscar" class="form-control" placeholder="Ej: 131415..." value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary">Estado del Paquete</label>
                <select name="estado" class="form-select">
                    <option value="">Todos los Estados</option>
                    <option value="Recibido" <?= (($filtros['estado'] ?? '') == 'Recibido') ? 'selected' : '' ?>>🏬 Recibidos (Bodega Origen)</option>
                    <option value="En Tránsito" <?= (($filtros['estado'] ?? '') == 'En Tránsito') ? 'selected' : '' ?>>🚚 En Tránsito (En Ruta)</option>
                    <option value="En Oficina" <?= (($filtros['estado'] ?? '') == 'En Oficina') ? 'selected' : '' ?>>🏢 En Oficina (Disponible Retiro)</option>
                    <option value="Entregado" <?= (($filtros['estado'] ?? '') == 'Entregado') ? 'selected' : '' ?>>✅ Entregados</option>
                    <option value="Anulado" <?= (($filtros['estado'] ?? '') == 'Anulado') ? 'selected' : '' ?>>🛑 Anulados</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary">Desde</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($filtros['fecha_inicio'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary">Hasta</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($filtros['fecha_fin'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">🔍 Buscar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
