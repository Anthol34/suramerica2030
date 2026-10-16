<?php
namespace Modules\Ventas;

use Platform\Storage\Database;
use PDO;

class VentasRepository {
    private $db;

    public function __construct() { 
        $this->db = (new Database())->getConnection(); 
    }

    public function obtenerDetalleFactura($idFactura) {
        $query = "SELECT 
                    f.ID_FACTURA, f.FA_FECHA, f.FA_HORA, f.FA_METODO, f.FA_TOTAL, f.FA_SUBTOTAL, f.FA_TIPO, f.FA_DETALLE, f.FA_CANTIDAD,
                    COALESCE(f.FECHA_VIAJE, f.FA_FECHA) as FECHA_VIAJE,
                    c.ID_CLIENTE, c.CLI_NOMBRE, c.CLI_APELLIDO, c.CLI_TELEFONO,
                    COALESCE(u.NOMBRE, 'Cajero') as CAJERO, 
                    COALESCE(s.CIUDAD, 'Sucursal') as SUCURSAL,
                    COALESCE(s.DIRECCION, 'S/D') as DIRECCION,
                    COALESCE(s.TELEFONO, 'S/N') as TELEFONO,
                    r.ID_RUTA, COALESCE(r.TIPO_VIAJE, 'DIRECTO') as TIPO_VIAJE,
                    COALESCE(r.RUT_ORIGEN, 'S/R') as RUT_ORIGEN, COALESCE(r.RUT_DESTINO, 'S/R') as RUT_DESTINO, COALESCE(r.RUT_SAHORA, f.FA_HORA) as RUT_SAHORA,
                    COALESCE(r.LIMITE_EQUIPAJE_KG, 25) as LIMITE_EQUIPAJE_KG,
                    (SELECT GROUP_CONCAT(px.PAX_ASIENTO SEPARATOR ', ') FROM PASAJE px WHERE px.ID_FACTURA = f.ID_FACTURA) as PAX_ASIENTO, 
                    (SELECT b2.BUS_PLACA FROM PASAJE px2 INNER JOIN BUS b2 ON px2.ID_BUS = b2.ID_BUS WHERE px2.ID_FACTURA = f.ID_FACTURA LIMIT 1) as BUS_PLACA,
                    e.ENC_PESO, e.ENC_VOLUMEN, e.ENC_CANTIDAD, e.ENC_TIPO as TIPO_PAQUETE, e.ENC_CONTENIDO, e.DESTINO_FINAL,
                    c_des.CLI_NOMBRE as DES_NOMBRE, c_des.CLI_APELLIDO as DES_APELLIDO, c_des.ID_CLIENTE as DES_CI, c_des.CLI_TELEFONO as DES_TEL,
                    c_val.CLI_NOMBRE as VAL_DES_NOMBRE, c_val.CLI_APELLIDO as VAL_DES_APELLIDO, c_val.ID_CLIENTE as VAL_DES_CI, c_val.CLI_TELEFONO as VAL_DES_TEL
                  FROM FACTURA f
                  LEFT JOIN CLIENTE c ON f.ID_CLIENTE = c.ID_CLIENTE
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  LEFT JOIN USUARIO u ON f.ID_USUARIO = u.ID_USUARIO
                  LEFT JOIN SUCURSAL s ON f.ID_SUCURSAL = s.ID_SUCURSAL
                  LEFT JOIN ENCOMIENDA e ON f.ID_FACTURA = e.ID_FACTURA
                  LEFT JOIN CLIENTE c_des ON e.ID_DESTINATARIO = c_des.ID_CLIENTE
                  LEFT JOIN VALOR v ON f.ID_FACTURA = v.ID_FACTURA
                  LEFT JOIN CLIENTE c_val ON v.ID_DESTINATARIO = c_val.ID_CLIENTE
                  WHERE f.ID_FACTURA = :id";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $idFactura]);
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($factura) {
            // Cargar lista detallada de pasajeros asociados a esta factura o abono
            $stmtPax = $this->db->prepare("
                SELECT 
                    p.ID_PASAJE, 
                    p.PAX_ASIENTO, 
                    p.ESTADO, 
                    p.PRECIO_TOTAL, 
                    p.SALDO,
                    c.ID_CLIENTE, 
                    c.CLI_NOMBRE, 
                    c.CLI_APELLIDO, 
                    c.CLI_TELEFONO, 
                    c.CLI_NACIONALIDAD,
                    b.BUS_PLACA
                FROM PASAJE p
                LEFT JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS
                WHERE p.ID_FACTURA = ? OR p.ID_PASAJE IN (SELECT a.ID_PASAJE FROM ABONO a WHERE a.ID_FACTURA = ?)
                ORDER BY p.PAX_ASIENTO ASC, p.ID_PASAJE ASC
            ");
            $stmtPax->execute([$idFactura, $idFactura]);
            $factura['PASAJEROS'] = $stmtPax->fetchAll(PDO::FETCH_ASSOC);

            if (in_array($factura['FA_TIPO'], ['PASAJE', 'RESERVA INICIAL']) && $factura['TIPO_VIAJE'] === 'CONEXION') {
                $stmtTramos = $this->db->prepare("SELECT ORIGEN_TRAMO, DESTINO_TRAMO, OPERADOR, PVP_TRAMO FROM RUTA_TRAMO WHERE ID_RUTA = ? ORDER BY ORDEN ASC");
                $stmtTramos->execute([$factura['ID_RUTA']]);
                $tramos = $stmtTramos->fetchAll(PDO::FETCH_ASSOC);
                foreach ($tramos as &$tr) {
                    if (floatval($tr['PVP_TRAMO'] ?? 0) > 0) {
                        $tr['PRECIO_PASAJE'] = floatval($tr['PVP_TRAMO']);
                    } else {
                        $stmtPrecio = $this->db->prepare("SELECT PRECIO_PASAJE FROM RUTA WHERE (RUT_ORIGEN = ? AND RUT_DESTINO = ?) OR (RUT_ORIGEN LIKE ? AND RUT_DESTINO LIKE ?) LIMIT 1");
                        $stmtPrecio->execute([$tr['ORIGEN_TRAMO'], $tr['DESTINO_TRAMO'], '%' . $tr['ORIGEN_TRAMO'] . '%', '%' . $tr['DESTINO_TRAMO'] . '%']);
                        $precioTramo = $stmtPrecio->fetchColumn();
                        $tr['PRECIO_PASAJE'] = ($precioTramo !== false) ? floatval($precioTramo) : 0.00;
                    }
                }
                unset($tr);
                $factura['TRAMOS'] = $tramos;
            }
            
            if ($factura['FA_TIPO'] === 'SOBREPESO') {
                $stmtSp = $this->db->prepare("
                    SELECT s.*, p.PAX_ASIENTO, p.ID_BUS, b.BUS_PLACA, p.PESO_BODEGA, p.PESO_MANO, p.EQUIPAJE_DETALLE, 
                           r_orig.RUT_DESTINO as DESTINO_FINAL_PASAJE, r_orig.RUT_ORIGEN as ORIGEN_PASAJE
                    FROM SOBREPESO s
                    LEFT JOIN PASAJE p ON s.ID_PASAJE = p.ID_PASAJE
                    LEFT JOIN FACTURA f_orig ON p.ID_FACTURA = f_orig.ID_FACTURA
                    LEFT JOIN RUTA r_orig ON f_orig.ID_RUTA = r_orig.ID_RUTA
                    LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS
                    WHERE s.ID_FACTURA = ?
                    LIMIT 1
                ");
                $stmtSp->execute([$idFactura]);
                $factura['DATOS_SOBREPESO'] = $stmtSp->fetch(PDO::FETCH_ASSOC);
            }

            // Cargar desglose de pagos (útil para pagos mixtos)
            $stmtMovs = $this->db->prepare("SELECT METODO_PAGO, MONTO FROM MOVIMIENTO_CAJA WHERE ID_FACTURA = ? AND TIPO_MOVIMIENTO = 'INGRESO' ORDER BY ID_MOVIMIENTO ASC");
            $stmtMovs->execute([$idFactura]);
            $factura['DESGLOSE_PAGOS'] = $stmtMovs->fetchAll(PDO::FETCH_ASSOC);
        }

        return $factura;
    }
}
?>