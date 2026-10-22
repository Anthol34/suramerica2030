<?php
$ciudades = $ciudadesCompletas ?? [];

// Estadísticas de Ciudades
$totalCiudades = count($ciudades);
$ciudadesVen = 0;
$ciudadesCol = 0;
$ciudadesEcu = 0;
$ciudadesConRutas = 0;
$ciudadesActivas = 0;

foreach ($ciudades as $cd) {
    $pais = strtoupper(trim($cd['PAIS'] ?? ''));
    if ($pais === 'VENEZUELA') $ciudadesVen++;
    elseif ($pais === 'COLOMBIA') $ciudadesCol++;
    elseif ($pais === 'ECUADOR') $ciudadesEcu++;

    if (($cd['TOTAL_RUTAS'] ?? 0) > 0 || ($cd['TOTAL_CORREDORES'] ?? 0) > 0) {
        $ciudadesConRutas++;
    }
    if (($cd['ESTADO'] ?? 1) == 1) {
        $ciudadesActivas++;
    }
}
?>

<!-- ======================================================== -->
<!-- MODAL XL: DOMINIO DE CIUDADES Y RED REGIONAL DE DESTINOS -->
<!-- ======================================================== -->
<div class="modal fade" id="modalCatalogoCiudades" tabindex="-1" aria-labelledby="modalCatalogoCiudadesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white py-3" style="background: #059669 !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4">🏙️</span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalCatalogoCiudadesLabel">Dominio Regional de Ciudades y Destinos</h5>
                        <small class="text-white-50">Catálogo de ciudades para emisión directa o conexiones con asociaciones y operadores</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-3 p-md-4 bg-light">

    <!-- BANNER INFORMATIVO Y ACCIONES -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%); border-left: 5px solid #059669 !important;">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center py-3">
            <div class="mb-2 mb-md-0">
                <h5 class="fw-bold text-success mb-1">🏙️ Dominio de Ciudades y Red Regional de Destinos</h5>
                <p class="text-muted small mb-0">Administra el catálogo integral de ciudades para venta directa o conexiones con asociaciones y operadores en Venezuela, Colombia y Ecuador.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <form action="/suramerica2030/flota/precargarCiudades" method="POST" onsubmit="return confirm('¿Deseas verificar y precargar todas las ciudades principales de Venezuela, Colombia y Ecuador al dominio?');">
                    <button type="submit" class="btn btn-outline-success fw-bold shadow-sm" title="Precarga o sincroniza todas las ciudades regionales">
                        ⚡ Sincronizar Ciudades Maestras
                    </button>
                </form>
                <button class="btn btn-success fw-bold shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#modalNuevaCiudad">
                    + Registrar Nueva Ciudad
                </button>
            </div>
        </div>
    </div>

    <!-- TARJETAS KPIS DE CIUDADES -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-2 text-center bg-white">
                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Total Ciudades</small>
                <span class="fs-4 fw-bold text-dark"><?= $totalCiudades ?></span>
                <small class="text-secondary d-block" style="font-size: 11px;">En el catálogo general</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-2 text-center bg-white">
                <small class="text-primary fw-bold text-uppercase d-block" style="font-size: 10px;">🇻🇪 Venezuela</small>
                <span class="fs-4 fw-bold text-primary"><?= $ciudadesVen ?></span>
                <small class="text-muted d-block" style="font-size: 11px;">Maturín, Caracas, etc.</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-2 text-center bg-white">
                <small class="text-warning text-dark fw-bold text-uppercase d-block" style="font-size: 10px;">🇨🇴 Colombia</small>
                <span class="fs-4 fw-bold text-dark"><?= $ciudadesCol ?></span>
                <small class="text-muted d-block" style="font-size: 11px;">Cúcuta, Bucaramanga, etc.</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100 p-2 text-center bg-white">
                <small class="text-success fw-bold text-uppercase d-block" style="font-size: 10px;">🇪🇨 Ecuador</small>
                <span class="fs-4 fw-bold text-success"><?= $ciudadesEcu ?></span>
                <small class="text-muted d-block" style="font-size: 11px;">Manta, Quito, Guayaquil...</small>
            </div>
        </div>
    </div>

    <!-- BARRA DE FILTROS Y BÚSQUEDA -->
    <div class="card shadow-sm border-0 mb-3 bg-light">
        <div class="card-body p-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-1 flex-wrap">
                <span class="small text-muted fw-bold me-1">Filtrar por País:</span>
                <button type="button" class="btn btn-sm btn-primary fw-bold filtro-pais-btn active" data-pais="TODOS" onclick="filtrarCiudadesPorPais('TODOS', this)">Todos (<?= $totalCiudades ?>)</button>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold filtro-pais-btn" data-pais="VENEZUELA" onclick="filtrarCiudadesPorPais('VENEZUELA', this)">🇻🇪 Venezuela (<?= $ciudadesVen ?>)</button>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold filtro-pais-btn" data-pais="COLOMBIA" onclick="filtrarCiudadesPorPais('COLOMBIA', this)">🇨🇴 Colombia (<?= $ciudadesCol ?>)</button>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold filtro-pais-btn" data-pais="ECUADOR" onclick="filtrarCiudadesPorPais('ECUADOR', this)">🇪🇨 Ecuador (<?= $ciudadesEcu ?>)</button>
            </div>
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text bg-white border-end-0">🔍</span>
                <input type="text" id="buscadorCiudades" class="form-control bg-white border-start-0 ps-0" placeholder="Buscar ciudad..." oninput="filtrarCiudadesTabla()">
                <button class="btn btn-white bg-white border border-start-0 text-muted" type="button" onclick="document.getElementById('buscadorCiudades').value=''; filtrarCiudadesTabla();">✕</button>
            </div>
        </div>
    </div>

    <!-- TABLA DE CIUDADES DEL DOMINIO -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaCiudadesDominio">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th>Ciudad</th>
                        <th>País</th>
                        <th class="text-center">Operación / Rutas</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end" style="width: 140px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ciudades)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <em>No hay ciudades registradas. Haz clic en "Sincronizar Ciudades Maestras".</em>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ciudades as $idx => $c): 
                            $pais = strtoupper(trim($c['PAIS'] ?? ''));
                            $bandera = '🌎';
                            $badgePais = 'bg-secondary';
                            if ($pais === 'VENEZUELA') { $bandera = '🇻🇪'; $badgePais = 'bg-primary'; }
                            elseif ($pais === 'COLOMBIA') { $bandera = '🇨🇴'; $badgePais = 'bg-warning text-dark'; }
                            elseif ($pais === 'ECUADOR') { $bandera = '🇪🇨'; $badgePais = 'bg-success'; }
                            
                            $esActiva = (($c['ESTADO'] ?? 1) == 1);
                            $totalRutas = (int)($c['TOTAL_RUTAS'] ?? 0);
                        ?>
                            <tr class="fila-ciudad" data-pais="<?= htmlspecialchars($pais) ?>" data-nombre="<?= htmlspecialchars(mb_strtolower($c['NOMBRE'])) ?>">
                                <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                                <td>
                                    <span class="fw-bold fs-6 text-dark text-uppercase"><?= htmlspecialchars($c['NOMBRE']) ?></span>
                                    <?php if ($c['NOMBRE'] === 'Maturín'): ?>
                                        <span class="badge bg-info text-dark ms-1" style="font-size: 10px;">🌟 Conexión Regional</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badgePais ?> fs-6 py-1 px-2">
                                        <?= $bandera ?> <?= htmlspecialchars($pais) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($totalRutas > 0): ?>
                                        <span class="badge bg-light text-primary border border-primary fw-bold">
                                            📍 <?= $totalRutas ?> <?= $totalRutas == 1 ? 'ruta' : 'rutas' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Disponible para asociar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($esActiva): ?>
                                        <span class="badge bg-success">ACTIVA</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">INACTIVA</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="abrirModalEditarCiudad(<?= htmlspecialchars(json_encode($c)) ?>)"
                                                title="Editar Ciudad">
                                            ✏️
                                        </button>
                                        <form action="/suramerica2030/flota/cambiarEstadoCiudad" method="POST" class="d-inline" onsubmit="return confirm('¿Deseas cambiar el estado de esta ciudad?');">
                                            <input type="hidden" name="id_ciudad" value="<?= $c['ID_CIUDAD'] ?>">
                                            <input type="hidden" name="estado" value="<?= $esActiva ? 0 : 1 ?>">
                                            <button type="submit" class="btn <?= $esActiva ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $esActiva ? 'Desactivar' : 'Activar' ?>">
                                                <?= $esActiva ? '⏸️' : '▶️' ?>
                                            </button>
                                        </form>
                                        <form action="/suramerica2030/flota/eliminarCiudad" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta ciudad del catálogo? Si tiene rutas asociadas, se archivará automáticamente como inactiva.');">
                                            <input type="hidden" name="id_ciudad" value="<?= $c['ID_CIUDAD'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
            </div>
            <div class="modal-footer bg-white py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar Catálogo</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: REGISTRAR NUEVA CIUDAD -->
<div class="modal fade" id="modalNuevaCiudad" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="/suramerica2030/flota/guardarCiudad" method="POST">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">🏙️ Registrar Nueva Ciudad en el Dominio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de la Ciudad</label>
                    <input type="text" name="nombre" class="form-control text-uppercase fw-bold" placeholder="Ej: MATURÍN, EL TIGRE, BARRANCABERMEJA..." required autofocus>
                    <small class="text-muted">Se registrará en el dominio comercial para emisión de pasajes y tramos.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">País</label>
                    <select name="pais" class="form-select border-primary fw-bold" required>
                        <option value="VENEZUELA" selected>🇻🇪 Venezuela</option>
                        <option value="COLOMBIA">🇨🇴 Colombia</option>
                        <option value="ECUADOR">🇪🇨 Ecuador</option>
                        <option value="PERU">🇵🇪 Perú</option>
                        <option value="OTRO">🌎 Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Estado en el Sistema</label>
                    <select name="estado" class="form-select">
                        <option value="1" selected>✅ Activa (Disponible para venta y rutas)</option>
                        <option value="0">⏸️ Inactiva (En preparación / Sin servicio)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success fw-bold">💾 Guardar Ciudad</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR CIUDAD -->
<div class="modal fade" id="modalEditarCiudad" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="/suramerica2030/flota/guardarCiudad" method="POST">
            <input type="hidden" name="id_ciudad" id="edit_id_ciudad" value="">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">✏️ Modificar Ciudad del Dominio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de la Ciudad</label>
                    <input type="text" name="nombre" id="edit_nombre_ciudad" class="form-control text-uppercase fw-bold" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">País</label>
                    <select name="pais" id="edit_pais_ciudad" class="form-select border-primary fw-bold" required>
                        <option value="VENEZUELA">🇻🇪 Venezuela</option>
                        <option value="COLOMBIA">🇨🇴 Colombia</option>
                        <option value="ECUADOR">🇪🇨 Ecuador</option>
                        <option value="PERU">🇵🇪 Perú</option>
                        <option value="OTRO">🌎 Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Estado</label>
                    <select name="estado" id="edit_estado_ciudad" class="form-select">
                        <option value="1">✅ Activa</option>
                        <option value="0">⏸️ Inactiva</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary fw-bold">💾 Actualizar Ciudad</button>
            </div>
        </form>
    </div>
</div>

<script>
let paisFiltroActivo = 'TODOS';

function filtrarCiudadesPorPais(pais, btn) {
    paisFiltroActivo = pais;
    document.querySelectorAll('.filtro-pais-btn').forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-primary', 'active');
    filtrarCiudadesTabla();
}

function filtrarCiudadesTabla() {
    const query = (document.getElementById('buscadorCiudades')?.value || '').trim().toLowerCase();
    const filas = document.querySelectorAll('#tablaCiudadesDominio tbody tr.fila-ciudad');

    filas.forEach(f => {
        const paisFila = f.getAttribute('data-pais');
        const nombreFila = f.getAttribute('data-nombre');

        const coincidePais = (paisFiltroActivo === 'TODOS' || paisFila === paisFiltroActivo);
        const coincideTexto = (!query || nombreFila.includes(query) || paisFila.toLowerCase().includes(query));

        if (coincidePais && coincideTexto) {
            f.style.display = '';
        } else {
            f.style.display = 'none';
        }
    });
}

function abrirModalEditarCiudad(data) {
    document.getElementById('edit_id_ciudad').value = data.ID_CIUDAD;
    document.getElementById('edit_nombre_ciudad').value = data.NOMBRE;
    document.getElementById('edit_pais_ciudad').value = data.PAIS;
    document.getElementById('edit_estado_ciudad').value = data.ESTADO ?? 1;

    const modal = new bootstrap.Modal(document.getElementById('modalEditarCiudad'));
    modal.show();
}
</script>
