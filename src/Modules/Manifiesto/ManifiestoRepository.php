<?php
namespace Modules\Manifiesto;

use Platform\Storage\Database;
use PDO;

class ManifiestoRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function contarViajesProgramados($fechaInicio, $fechaFin) {
        $query = "SELECT COUNT(*) FROM (
                      SELECT f.FECHA_VIAJE
                      FROM FACTURA f
                      INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA
                      INNER JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                      INNER JOIN BUS b ON p.ID_BUS = b.ID_BUS
                      WHERE f.FA_TIPO = 'PASAJE'
                        AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL)
                        AND f.FECHA_VIAJE BETWEEN :inicio AND :fin
                      GROUP BY f.FECHA_VIAJE, r.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO, b.ID_BUS, b.BUS_PLACA, b.BUS_MARCA, b.BUS_MODELO
                  ) as t";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        return (int)$stmt->fetchColumn();
    }

    public function obtenerViajesProgramados($fechaInicio, $fechaFin, $offset = 0, $limit = 10) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $query = "SELECT f.FECHA_VIAJE, r.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO, b.ID_BUS, b.BUS_PLACA, b.BUS_MARCA, b.BUS_MODELO,
                         COUNT(p.ID_PASAJE) as TOTAL_PASAJEROS
                  FROM FACTURA f
                  INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA
                  INNER JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  INNER JOIN BUS b ON p.ID_BUS = b.ID_BUS
                  WHERE f.FA_TIPO = 'PASAJE'
                    AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL)
                    AND f.FECHA_VIAJE BETWEEN :inicio AND :fin
                  GROUP BY f.FECHA_VIAJE, r.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO, b.ID_BUS, b.BUS_PLACA, b.BUS_MARCA, b.BUS_MODELO
                  ORDER BY f.FECHA_VIAJE DESC
                  LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPasajerosPorViaje($fecha, $rutaId, $busId) {
        $query = "SELECT p.PAX_ASIENTO, c.CLI_APELLIDO, c.CLI_NOMBRE, c.ID_CLIENTE, 
                         COALESCE(c.CLI_NACIONALIDAD, 'ECUATORIANA') as NACIONALIDAD, 
                         c.CLI_FECHANAC
                  FROM FACTURA f
                  INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA
                  INNER JOIN CLIENTE c ON f.ID_CLIENTE = c.ID_CLIENTE
                  WHERE f.FECHA_VIAJE = :fecha
                    AND f.ID_RUTA = :ruta
                    AND p.ID_BUS = :bus
                    AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL)
                  ORDER BY p.PAX_ASIENTO ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':fecha' => $fecha, ':ruta' => $rutaId, ':bus' => $busId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEquipajePorViaje($fecha, $rutaId, $busId) {
        $query = "SELECT p.PAX_ASIENTO, c.CLI_APELLIDO, c.CLI_NOMBRE, c.ID_CLIENTE, 
                         p.PESO_BODEGA, p.PESO_MANO, f.ID_FACTURA
                  FROM FACTURA f
                  INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA
                  INNER JOIN CLIENTE c ON f.ID_CLIENTE = c.ID_CLIENTE
                  WHERE f.FECHA_VIAJE = :fecha
                    AND f.ID_RUTA = :ruta
                    AND p.ID_BUS = :bus
                    AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL)
                    AND (p.PESO_BODEGA > 0 OR p.PESO_MANO > 0)
                  ORDER BY p.PAX_ASIENTO ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':fecha' => $fecha, ':ruta' => $rutaId, ':bus' => $busId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDirectorioCorreos() {
        $query = "SELECT u.NOMBRE, u.EMAIL, s.CIUDAD
                  FROM USUARIO u
                  INNER JOIN SUCURSAL s ON u.ID_SUCURSAL = s.ID_SUCURSAL
                  WHERE u.ESTADO = 1 AND u.EMAIL IS NOT NULL AND u.EMAIL != ''
                  ORDER BY s.CIUDAD ASC, u.NOMBRE ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDatosRutaYBus($rutaId, $busId) {
        $stmtRuta = $this->db->prepare("SELECT RUT_ORIGEN, RUT_DESTINO FROM RUTA WHERE ID_RUTA = ?");
        $stmtRuta->execute([$rutaId]);
        $ruta = $stmtRuta->fetch(PDO::FETCH_ASSOC);

        $stmtBus = $this->db->prepare("SELECT BUS_PLACA, BUS_MARCA FROM BUS WHERE ID_BUS = ?");
        $stmtBus->execute([$busId]);
        $bus = $stmtBus->fetch(PDO::FETCH_ASSOC);

        return ['ruta' => $ruta, 'bus' => $bus];
    }

    public function contarPasajerosHoy() {
        $query = "SELECT COUNT(p.ID_PASAJE) 
                  FROM FACTURA f 
                  INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA 
                  WHERE f.FA_TIPO = 'PASAJE' 
                    AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL) 
                    AND f.FECHA_VIAJE = CURDATE()";
        return (int)$this->db->query($query)->fetchColumn();
    }

    public function obtenerPasajerosHoy() {
        $query = "SELECT p.PAX_ASIENTO, c.CLI_APELLIDO, c.CLI_NOMBRE, c.ID_CLIENTE, 
                         COALESCE(c.CLI_NACIONALIDAD, 'ECUATORIANA') as NACIONALIDAD, 
                         c.CLI_FECHANAC, r.RUT_ORIGEN, r.RUT_DESTINO, b.BUS_PLACA, b.BUS_MARCA, f.FECHA_VIAJE, f.ID_FACTURA
                  FROM FACTURA f 
                  INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA 
                  INNER JOIN CLIENTE c ON f.ID_CLIENTE = c.ID_CLIENTE 
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA 
                  LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS 
                  WHERE f.FA_TIPO = 'PASAJE' 
                    AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL) 
                    AND f.FECHA_VIAJE = CURDATE() 
                  ORDER BY b.BUS_PLACA ASC, p.PAX_ASIENTO ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPasajerosConsolidados($viajesRaw) {
        $conds = [];
        $params = [];
        $i = 0;
        foreach ($viajesRaw as $item) {
            $parts = explode('|', $item);
            if (count($parts) >= 3) {
                $f = trim($parts[0]);
                $r = (int)$parts[1];
                $b = (int)$parts[2];
                $conds[] = "(f.FECHA_VIAJE = :f$i AND f.ID_RUTA = :r$i AND p.ID_BUS = :b$i)";
                $params[":f$i"] = $f;
                $params[":r$i"] = $r;
                $params[":b$i"] = $b;
                $i++;
            }
        }
        if (empty($conds)) return [];

        $query = "SELECT p.PAX_ASIENTO, c.CLI_APELLIDO, c.CLI_NOMBRE, c.ID_CLIENTE, 
                         COALESCE(c.CLI_NACIONALIDAD, 'ECUATORIANA') as NACIONALIDAD, 
                         c.CLI_FECHANAC, r.RUT_ORIGEN, r.RUT_DESTINO, b.BUS_PLACA, b.BUS_MARCA, f.FECHA_VIAJE, f.ID_FACTURA
                  FROM FACTURA f
                  INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA
                  INNER JOIN CLIENTE c ON f.ID_CLIENTE = c.ID_CLIENTE
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS
                  WHERE f.FA_TIPO = 'PASAJE'
                    AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL)
                    AND (" . implode(' OR ', $conds) . ")
                  ORDER BY b.BUS_PLACA ASC, p.PAX_ASIENTO ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEquipajeConsolidado($viajesRaw) {
        $conds = [];
        $params = [];
        $i = 0;
        foreach ($viajesRaw as $item) {
            $parts = explode('|', $item);
            if (count($parts) >= 3) {
                $f = trim($parts[0]);
                $r = (int)$parts[1];
                $b = (int)$parts[2];
                $conds[] = "(f.FECHA_VIAJE = :f$i AND f.ID_RUTA = :r$i AND p.ID_BUS = :b$i)";
                $params[":f$i"] = $f;
                $params[":r$i"] = $r;
                $params[":b$i"] = $b;
                $i++;
            }
        }
        if (empty($conds)) return [];

        $query = "SELECT p.PAX_ASIENTO, c.CLI_APELLIDO, c.CLI_NOMBRE, c.ID_CLIENTE, 
                         p.PESO_BODEGA, p.PESO_MANO, f.ID_FACTURA, r.RUT_ORIGEN, r.RUT_DESTINO, b.BUS_PLACA
                  FROM FACTURA f
                  INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA
                  INNER JOIN CLIENTE c ON f.ID_CLIENTE = c.ID_CLIENTE
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS
                  WHERE f.FA_TIPO = 'PASAJE'
                    AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL)
                    AND (p.PESO_BODEGA > 0 OR p.PESO_MANO > 0)
                    AND (" . implode(' OR ', $conds) . ")
                  ORDER BY b.BUS_PLACA ASC, p.PAX_ASIENTO ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function unificarViajesBD($viajesRaw, $busDestinoId, $rutaDestinoId, $fechaDestino) {
        try {
            $this->db->beginTransaction();
            $busDestinoId = (int)$busDestinoId;
            $rutaDestinoId = (int)$rutaDestinoId;
            $fechaDestino = trim($fechaDestino);

            if (empty($viajesRaw) || $busDestinoId <= 0 || $rutaDestinoId <= 0 || empty($fechaDestino)) {
                throw new Exception("Datos incompletos para unificar los viajes.");
            }

            foreach ($viajesRaw as $item) {
                $parts = explode('|', $item);
                if (count($parts) >= 3) {
                    $f = trim($parts[0]);
                    $r = (int)$parts[1];
                    $b = (int)$parts[2];

                    $stmt = $this->db->prepare("
                        UPDATE FACTURA f
                        INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA
                        SET f.ID_RUTA = :nuevaRuta,
                            f.FECHA_VIAJE = :nuevaFecha,
                            p.ID_BUS = :nuevoBus
                        WHERE f.FA_TIPO = 'PASAJE'
                          AND f.FECHA_VIAJE = :fechaAnt
                          AND f.ID_RUTA = :rutaAnt
                          AND p.ID_BUS = :busAnt
                    ");
                    $stmt->execute([
                        ':nuevaRuta' => $rutaDestinoId,
                        ':nuevaFecha' => $fechaDestino,
                        ':nuevoBus' => $busDestinoId,
                        ':fechaAnt' => $f,
                        ':rutaAnt' => $r,
                        ':busAnt' => $b
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    public function obtenerBuses() {
        return $this->db->query("SELECT ID_BUS, BUS_PLACA, BUS_MARCA, BUS_MODELO, BUS_ASIENTOS FROM BUS ORDER BY BUS_PLACA ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRutas() {
        return $this->db->query("SELECT ID_RUTA, RUT_ORIGEN, RUT_DESTINO FROM RUTA WHERE COALESCE(ESTADO, 'ACTIVO') = 'ACTIVO' ORDER BY RUT_ORIGEN ASC, RUT_DESTINO ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>