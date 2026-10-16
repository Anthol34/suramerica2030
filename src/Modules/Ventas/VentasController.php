<?php
namespace Modules\Ventas;

class VentasController {
    private $repository;

    public function __construct() {
        $this->repository = new VentasRepository();
    }

    public function ticket($id = null) {
        if (!$id) {
            echo "<h1>Error: ID de Factura no proporcionado.</h1>";
            return;
        }

        $factura = $this->repository->obtenerDetalleFactura($id);
        
        if(!$factura) {
            echo "<h1>Error: Boleto no encontrado.</h1>";
            return;
        }

        require_once __DIR__ . '/Views/ticket.php';
    }

    /**
     * Vista de escáner QR mediante cámara (celular o laptop)
     */
    public function escanear() {
        $titulo = "Lector QR de Facturas - Suramerica 2030";
        $vista = __DIR__ . '/Views/escanear.php';
        
        if (isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/../../Platform/Views/layout.php';
        } else {
            // Modo standalone si se accede directamente desde un celular sin sesión
            require_once $vista;
        }
    }

    /**
     * Consulta AJAX para validar y obtener detalles de una factura escaneada
     */
    public function consultar($id = null) {
        // Limpiamos buffers previos para asegurar JSON limpio
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'N° de Factura / Ticket no proporcionado.']);
            exit();
        }

        $factura = $this->repository->obtenerDetalleFactura($id);

        if (!$factura) {
            echo json_encode(['success' => false, 'message' => 'Comprobante #' . $id . ' no encontrado en el sistema.']);
            exit();
        }

        $clienteNombre = trim(($factura['CLI_NOMBRE'] ?? '') . ' ' . ($factura['CLI_APELLIDO'] ?? ''));
        if (empty($clienteNombre)) {
            $clienteNombre = 'Consumidor Final';
        }

        $destino = $factura['RUT_DESTINO'] ?? 'S/R';
        if ($factura['FA_TIPO'] === 'ENCOMIENDA' && !empty($factura['DESTINO_FINAL'])) {
            $destino = $factura['DESTINO_FINAL'] . ' (Suc: ' . ($factura['RUT_DESTINO'] ?? '') . ')';
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'ID_FACTURA'     => $factura['ID_FACTURA'],
                'NUM_TICKET'     => str_pad($factura['ID_FACTURA'], 6, '0', STR_PAD_LEFT),
                'FA_FECHA'       => $factura['FA_FECHA'],
                'FA_HORA'        => $factura['FA_HORA'],
                'FA_TIPO'        => $factura['FA_TIPO'],
                'FA_TOTAL'       => number_format((float)$factura['FA_TOTAL'], 2, '.', ''),
                'FA_METODO'      => $factura['FA_METODO'] ?? 'Efectivo',
                'CLIENTE'        => $clienteNombre,
                'ID_CLIENTE'     => $factura['ID_CLIENTE'] ?? 'S/N',
                'TELEFONO'       => $factura['CLI_TELEFONO'] ?? 'S/N',
                'ORIGEN'         => $factura['RUT_ORIGEN'] ?? 'S/R',
                'DESTINO'        => $destino,
                'SUCURSAL'       => $factura['SUCURSAL'] ?? 'Central',
                'CAJERO'         => $factura['CAJERO'] ?? 'Caja',
                'ASIENTO'        => $factura['PAX_ASIENTO'] ?? '',
                'BUS_PLACA'      => $factura['BUS_PLACA'] ?? '',
                'FECHA_VIAJE'    => $factura['FECHA_VIAJE'] ?? '',
                'RUT_SAHORA'     => $factura['RUT_SAHORA'] ?? '',
                'ENC_PESO'       => $factura['ENC_PESO'] ?? '',
                'ENC_CANTIDAD'   => $factura['ENC_CANTIDAD'] ?? '',
                'TIPO_PAQUETE'   => $factura['TIPO_PAQUETE'] ?? '',
                'ENC_CONTENIDO'  => $factura['ENC_CONTENIDO'] ?? '',
                'ENC_DESTINATARIO' => trim(($factura['DES_NOMBRE'] ?? '') . ' ' . ($factura['DES_APELLIDO'] ?? '')),
                'ENC_DES_CI'     => $factura['DES_CI'] ?? '',
                'ENC_DES_TEL'    => $factura['DES_TEL'] ?? '',
                'VAL_DESTINATARIO' => trim(($factura['VAL_DES_NOMBRE'] ?? '') . ' ' . ($factura['VAL_DES_APELLIDO'] ?? '')),
                'PASAJEROS'      => $factura['PASAJEROS'] ?? [],
                'DESGLOSE_PAGOS' => $factura['DESGLOSE_PAGOS'] ?? [],
                'URL_TICKET'     => '/suramerica2030/ventas/ticket/' . $factura['ID_FACTURA'] . '?view=1'
            ]
        ]);
        exit();
    }
}
?>