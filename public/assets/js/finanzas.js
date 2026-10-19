function filtrarMovimientos() {
    const input = document.getElementById('filtroTextoMov');
    const selectTipo = document.getElementById('filtroTipoMov');
    if (!input || !selectTipo) return;

    const texto = (input.value || '').toLowerCase().trim();
    const tipo = selectTipo.value;
    const filas = document.querySelectorAll('#tbodyMovimientos .fila-movimiento');
    
    let visibles = 0;
    let sumPvp = 0;
    let sumCosto = 0;
    let sumUtil = 0;

    filas.forEach(fila => {
        const filaTipo = fila.getAttribute('data-tipo');
        const filaTexto = fila.innerText.toLowerCase();
        
        const cumpleTipo = (tipo === 'TODOS' || filaTipo === tipo);
        const cumpleTexto = (texto === '' || filaTexto.includes(texto));

        if (cumpleTipo && cumpleTexto) {
            fila.style.display = '';
            visibles++;
            sumPvp += parseFloat(fila.getAttribute('data-pvp') || 0);
            sumCosto += parseFloat(fila.getAttribute('data-costo') || 0);
            sumUtil += parseFloat(fila.getAttribute('data-utilidad') || 0);
        } else {
            fila.style.display = 'none';
        }
    });

    const cont = document.getElementById('contadorMovimientos');
    if (cont) cont.innerText = `Mostrando ${visibles} registros`;

    const elPvp = document.getElementById('subtotalPvp');
    const elCosto = document.getElementById('subtotalCosto');
    const elUtil = document.getElementById('subtotalUtilidad');

    if (elPvp) elPvp.innerText = '$ ' + sumPvp.toFixed(2);
    if (elCosto) elCosto.innerText = '$ ' + sumCosto.toFixed(2);
    if (elUtil) elUtil.innerText = '$ ' + sumUtil.toFixed(2);
}

function filtrarDetallePorOperador(nombreOp) {
    const inputTexto = document.getElementById('filtroTextoMov');
    if (inputTexto) {
        inputTexto.value = nombreOp;
        filtrarMovimientos();
        inputTexto.scrollIntoView({ behavior: 'smooth', block: 'center' });
        inputTexto.focus();
    }
}

function prepararImpresionOperador(nombreOp) {
    const op = liquidacionData.por_operador[nombreOp];
    if (!op) return;

    const f = liquidacionData.filtros;
    const movsOp = liquidacionData.movimientos.filter(m => m.operador === nombreOp);

    let html = `
        <div class="text-center pb-3 border-bottom mb-3">
            <h4 class="fw-bold mb-1 text-primary">SURAMÉRICA 2030</h4>
            <h6 class="text-secondary mb-1">AGENCIA INTERNACIONAL DE VIAJES Y TURISMO</h6>
            <h5 class="fw-bold text-dark mt-2">COMPROBANTE DE LIQUIDACIÓN A SOCIO / OPERADOR</h5>
        </div>

        <div class="row g-2 mb-3 small">
            <div class="col-6">
                <strong>Operador / Socio:</strong> <span class="fs-6 fw-bold text-primary">${nombreOp}</span><br>
                ${op.razon_social && op.razon_social !== nombreOp ? `<strong>Razón Social:</strong> ${op.razon_social}<br>` : ''}
                ${op.identificacion ? `<strong>RUC / Cédula / Doc:</strong> <span class="badge bg-light text-dark border">${op.identificacion}</span><br>` : ''}
                <strong>Período Liquidado:</strong> ${f.fecha_inicio} al ${f.fecha_fin}<br>
                <strong>Criterio de Fecha:</strong> ${f.tipo_fecha === 'viaje' ? 'Fecha de Viaje' : 'Fecha de Venta'}
            </div>
            <div class="col-6 text-end">
                <strong>Fecha Emisión:</strong> <?= date('d/m/Y H:i') ?><br>
                <strong>Sucursal / Base:</strong> ${f.id_sucursal === 'TODAS' || !f.id_sucursal ? 'Todas las Sucursales' : 'Sucursal #' + f.id_sucursal}<br>
                ${op.banco ? `<div class="mt-1 p-2 bg-white border rounded text-start d-inline-block small shadow-sm">
                    <span class="fw-bold text-primary">🏦 Datos para Transferencia:</span><br>
                    <strong>Banco:</strong> ${op.banco} (${op.tipo_cuenta || 'Corriente'})<br>
                    <strong>N° Cuenta:</strong> <span class="font-monospace fw-bold text-dark">${op.numero_cuenta}</span><br>
                    <strong>Titular:</strong> ${op.titular_cuenta || nombreOp} ${op.titular_doc ? '(' + op.titular_doc + ')' : ''}
                </div>` : '<strong>Moneda:</strong> Dólares Americanos (USD)'}
            </div>
        </div>

        <div class="card bg-light border mb-3">
            <div class="card-body py-2">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="text-muted small">Boletos Transportados</div>
                        <div class="fw-bold fs-6">${op.cant_pasajes}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Costo Boletos</div>
                        <div class="fw-bold fs-6 text-dark">$ ${parseFloat(op.total_costo_pasajes).toFixed(2)}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Sobrepeso (${parseFloat(op.kg_sobrepeso).toFixed(1)} Kg)</div>
                        <div class="fw-bold fs-6 text-dark">$ ${parseFloat(op.total_sobrepeso_socio).toFixed(2)}</div>
                    </div>
                    <div class="col-3 bg-warning bg-opacity-25 rounded py-1">
                        <div class="text-danger small fw-bold">TOTAL A LIQUIDAR</div>
                        <div class="fw-bold fs-5 text-danger">$ ${parseFloat(op.total_a_liquidar).toFixed(2)}</div>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="fw-bold small text-uppercase mb-2">Desglose de Servicios y Pasajes (${movsOp.length} ítems):</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle small mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Factura</th>
                        <th>Pasajero</th>
                        <th>Ruta / Tramo</th>
                        <th>Concepto</th>
                        <th class="text-end">PVP</th>
                        <th class="text-end">A Liquidar</th>
                    </tr>
                </thead>
                <tbody>
    `;

    movsOp.forEach(m => {
        html += `
            <tr>
                <td>${(m.fecha_venta || '').substring(0, 10)}</td>
                <td>#${m.id_factura}</td>
                <td>${m.cliente}</td>
                <td>${m.tramo}</td>
                <td>${m.detalle}</td>
                <td class="text-end">$ ${parseFloat(m.pvp).toFixed(2)}</td>
                <td class="text-end fw-bold text-danger">$ ${parseFloat(m.costo_socio).toFixed(2)}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-end">TOTAL LIQUIDADO AL SOCIO:</td>
                        <td class="text-end">$ ${(parseFloat(op.total_pvp_pasajes) + parseFloat(op.total_sobrepeso_usd)).toFixed(2)}</td>
                        <td class="text-end text-danger fs-6">$ ${parseFloat(op.total_a_liquidar).toFixed(2)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-5 pt-4 text-center">
            <div class="col-6">
                <div class="border-top border-dark mx-4 pt-2">
                    <strong>Suramérica 2030</strong><br>
                    <span class="small text-muted">Departamento de Finanzas y Control</span>
                </div>
            </div>
            <div class="col-6">
                <div class="border-top border-dark mx-4 pt-2">
                    <strong>${nombreOp}</strong><br>
                    <span class="small text-muted">Recibido Conforme / Firma Autorizada</span>
                </div>
            </div>
        </div>
    `;

    document.getElementById('contenedorDocumentoLiquidacion').innerHTML = html;
    const modalEl = document.getElementById('modalImprimirLiquidacion');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function prepararImpresionGeneral() {
    const f = liquidacionData.filtros;
    const res = liquidacionData.resumen;
    const ops = liquidacionData.por_operador;

    let html = `
        <div class="text-center pb-3 border-bottom mb-3">
            <h4 class="fw-bold mb-1 text-primary">SURAMÉRICA 2030</h4>
            <h6 class="text-secondary mb-1">AGENCIA INTERNACIONAL DE VIAJES Y ENCOMIENDAS</h6>
            <h5 class="fw-bold text-dark mt-2">ESTADO GENERAL DE LIQUIDACIÓN Y UTILIDAD A SOCIOS</h5>
        </div>

        <div class="row g-2 mb-3 small">
            <div class="col-6">
                <strong>Período:</strong> ${f.fecha_inicio} al ${f.fecha_fin}<br>
                <strong>Base de Consulta:</strong> ${f.tipo_fecha === 'viaje' ? 'Fecha de Viaje' : 'Fecha de Venta'}
            </div>
            <div class="col-6 text-end">
                <strong>Fecha Emisión:</strong> <?= date('d/m/Y H:i') ?><br>
                <strong>Total Recaudado:</strong> $ ${parseFloat(res.total_recaudado).toFixed(2)}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle small mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Operador / Socio</th>
                        <th class="text-center">Boletos</th>
                        <th class="text-end">PVP Boletos</th>
                        <th class="text-end">Costo Boletos</th>
                        <th class="text-center">Sobrepeso</th>
                        <th class="text-end">Total a Liquidar</th>
                        <th class="text-end">Utilidad Agencia</th>
                        <th class="text-center">Margen</th>
                    </tr>
                </thead>
                <tbody>
    `;

    for (const [opNombre, op] of Object.entries(ops)) {
        html += `
            <tr>
                <td class="fw-bold">${opNombre}</td>
                <td class="text-center">${op.cant_pasajes}</td>
                <td class="text-end">$ ${parseFloat(op.total_pvp_pasajes).toFixed(2)}</td>
                <td class="text-end">$ ${parseFloat(op.total_costo_pasajes).toFixed(2)}</td>
                <td class="text-center">${op.kg_sobrepeso > 0 ? parseFloat(op.kg_sobrepeso).toFixed(1) + ' Kg ($' + parseFloat(op.total_sobrepeso_usd).toFixed(2) + ')' : '-'}</td>
                <td class="text-end fw-bold text-danger">$ ${parseFloat(op.total_a_liquidar).toFixed(2)}</td>
                <td class="text-end fw-bold text-success">$ ${parseFloat(op.total_utilidad_agencia).toFixed(2)}</td>
                <td class="text-center">${op.margen_pct} %</td>
            </tr>
        `;
    }

    html += `
                </tbody>
                <tfoot class="table-dark fw-bold">
                    <tr>
                        <td>TOTAL GENERAL</td>
                        <td class="text-center">${res.total_boletos}</td>
                        <td class="text-end">$ ${parseFloat(res.total_recaudado - res.total_sobrepeso_usd).toFixed(2)}</td>
                        <td class="text-end">$ ${parseFloat(res.total_liquidar - res.total_sobrepeso_usd).toFixed(2)}</td>
                        <td class="text-center">${parseFloat(res.total_sobrepeso_kg).toFixed(1)} Kg</td>
                        <td class="text-end text-warning">$ ${parseFloat(res.total_liquidar).toFixed(2)}</td>
                        <td class="text-end text-success">$ ${parseFloat(res.total_utilidad).toFixed(2)}</td>
                        <td class="text-center">${res.margen_promedio_pct} %</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-5 pt-4 text-center">
            <div class="col-6">
                <div class="border-top border-dark mx-4 pt-2">
                    <strong>Administración Central</strong><br>
                    <span class="small text-muted">Suramérica 2030 Ecuador</span>
                </div>
            </div>
            <div class="col-6">
                <div class="border-top border-dark mx-4 pt-2">
                    <strong>Auditoría Contable</strong><br>
                    <span class="small text-muted">Control Financiero de Rutas</span>
                </div>
            </div>
        </div>
    `;

    document.getElementById('contenedorDocumentoLiquidacion').innerHTML = html;
    const modalEl = document.getElementById('modalImprimirLiquidacion');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

