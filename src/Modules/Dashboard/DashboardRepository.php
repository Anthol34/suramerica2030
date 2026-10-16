<?php
namespace Modules\Dashboard;

use Platform\Storage\Database;
use PDO;

class DashboardRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function obtenerTotalIngresosHoy($sucursalId) {
        $query = "SELECT COALESCE(SUM(MONTO), 0) FROM MOVIMIENTO_CAJA 
                  WHERE TIPO_MOVIMIENTO = 'INGRESO' 
                  AND DATE(FECHA_HORA) = CURDATE() 
                  AND ID_SUCURSAL = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$sucursalId]);
        return $stmt->fetchColumn();
    }

    public function contarPasajesVendidosHoy($sucursalId) {
        $query = "SELECT COUNT(ID_FACTURA) FROM FACTURA 
                  WHERE FA_TIPO = 'PASAJE' 
                  AND FA_FECHA = CURDATE() 
                  AND ID_SUCURSAL = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$sucursalId]);
        return $stmt->fetchColumn();
    }

    public function contarEncomiendasActivas() {
        // Encomiendas a nivel general que no han sido entregadas ni anuladas
        $query = "SELECT COUNT(ID_ENCOMIENDA) FROM ENCOMIENDA 
                  WHERE ENC_ESTADO IN ('Recibido', 'En Tránsito')";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function contarValoresPendientes() {
        // Remesas de dinero que no han sido retiradas
        $query = "SELECT COUNT(ID_VALOR) FROM VALOR WHERE VA_ESTADO = 'Por Retirar'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function obtenerActividadReciente($sucursalId) {
        // Últimas 5 transacciones de la sucursal actual
        $query = "SELECT f.ID_FACTURA, f.FA_FECHA, f.FA_HORA, f.FA_TIPO, f.FA_TOTAL, f.FA_DETALLE, 
                         c.CLI_NOMBRE, c.CLI_APELLIDO 
                  FROM FACTURA f 
                  INNER JOIN CLIENTE c ON f.ID_CLIENTE = c.ID_CLIENTE 
                  WHERE f.ID_SUCURSAL = ? 
                  ORDER BY f.ID_FACTURA DESC LIMIT 5";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$sucursalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>