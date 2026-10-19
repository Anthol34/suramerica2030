<!-- MODAL IMPRIMIBLE: ESTADO DE CUENTA Y LIQUIDACIÓN A OPERADORES -->
<div class="modal fade" id="modalImprimirLiquidacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white no-print py-2">
                <h5 class="modal-title fs-6">🖨️ Estado de Cuenta y Liquidación a Socio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="contenedorDocumentoLiquidacion">
                <!-- Se llena dinámicamente con JS -->
            </div>
            <div class="modal-footer bg-light no-print py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success btn-sm fw-bold" onclick="window.print()">
                    🖨️ Imprimir Liquidación
                </button>
            </div>
        </div>
    </div>
</div>
