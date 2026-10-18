<?php
namespace Modules\Valores;

use Platform\Storage\Database;
use PDO;
use Exception;

class ValoresRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function obtenerRutas() {
        $query = "SELECT ID_RUTA, RUT_ORIGEN, RUT_DESTINO, COALESCE(COMISION_VALOR, 10.00) as COMISION_VALOR 
                  FROM RUTA WHERE COALESCE(ESTADO, 'ACTIVO') = 'ACTIVO' ORDER BY RUT_ORIGEN, RUT_DESTINO";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerClientePorId($idCliente) {
        $stmt = $this->db->prepare("SELECT * FROM CLIENTE WHERE ID_CLIENTE = ?");
        $stmt->execute([$idCliente]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function aplicarFiltros(&$query, &$params, $filtros) {
        $tab = $filtros['tab'] ?? 'activos';
        if ($tab == 'activos') {
            $query .= " AND v.VA_ESTADO = 'Por Retirar' ";
        } else {
            $buscar = $filtros['buscar'] ?? '';
            $inicio = $filtros['fecha_inicio'] ?? '';
            $fin = $filtros['fecha_fin'] ?? '';
            
            if ($buscar !== '') {
                $query .= " AND (f.ID_FACTURA = :b_id OR c_rem.ID_CLIENTE LIKE :b_str OR c_rem.CLI_NOMBRE LIKE :b_str OR c_des.CLI_NOMBRE LIKE :b_str) ";
                $params[':b_id'] = (int)$buscar;
                $params[':b_str'] = "%$buscar%";
            }
            if ($inicio && $fin) {
                $query .= " AND f.FA_FECHA BETWEEN :inicio AND :fin ";
                $params[':inicio'] = $inicio;
                $params[':fin'] = $fin;
            }
        }
    }

    public function contarTodos($filtros = []) {
        $query = "SELECT COUNT(*) FROM VALOR v 
                  INNER JOIN FACTURA f ON v.ID_FACTURA = f.ID_FACTURA 
                  INNER JOIN CLIENTE c_rem ON f.ID_CLIENTE = c_rem.ID_CLIENTE 
                  LEFT JOIN CLIENTE c_des ON v.ID_DESTINATARIO = c_des.ID_CLIENTE 
                  WHERE 1=1 ";
        $params = [];
        $this->aplicarFiltros($query, $params, $filtros);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function obtenerTodos($filtros = [], $offset = 0, $limit = 10) {
        $query = "SELECT v.ID_VALOR, v.VA_ESTADO, v.VA_RETIRO,
                         f.ID_FACTURA, f.FA_FECHA, f.FA_SUBTOTAL as MONTO_ENVIADO, f.FA_TOTAL as TOTAL_COBRADO, f.FA_DETALLE,
                         c_rem.CLI_NOMBRE AS REM_NOMBRE, c_rem.CLI_APELLIDO AS REM_APELLIDO, c_rem.ID_CLIENTE AS REM_CI,
                         c_des.CLI_NOMBRE AS DES_NOMBRE, c_des.CLI_APELLIDO AS DES_APELLIDO, c_des.ID_CLIENTE AS DES_CI,
                         COALESCE(r.RUT_ORIGEN, 'Manta') as RUT_ORIGEN, 
                         COALESCE(r.RUT_DESTINO, 'Caracas') as RUT_DESTINO,
                         (SELECT sa.ESTADO FROM SOLICITUD_ANULACION sa WHERE sa.TIPO_VENTA = 'VALOR' AND sa.ID_FACTURA = f.ID_FACTURA ORDER BY sa.ID_SOLICITUD DESC LIMIT 1) as SOLICITUD_ESTADO,
                         (SELECT sa.ID_SOLICITUD FROM SOLICITUD_ANULACION sa WHERE sa.TIPO_VENTA = 'VALOR' AND sa.ID_FACTURA = f.ID_FACTURA ORDER BY sa.ID_SOLICITUD DESC LIMIT 1) as ID_SOLICITUD
                  FROM VALOR v
                  INNER JOIN FACTURA f ON v.ID_FACTURA = f.ID_FACTURA
                  INNER JOIN CLIENTE c_rem ON f.ID_CLIENTE = c_rem.ID_CLIENTE
                  LEFT JOIN CLIENTE c_des ON v.ID_DESTINATARIO = c_des.ID_CLIENTE
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  WHERE 1=1 ";
                  
        $params = [];
        $this->aplicarFiltros($query, $params, $filtros);
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $query .= " ORDER BY CASE WHEN v.VA_ESTADO = 'Entregado' THEN 1 ELSE 0 END ASC, f.FA_FECHA DESC, v.ID_VALOR DESC LIMIT $limit OFFSET $offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarEnvioBD($datos, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();

            $stmtRem = $this->db->prepare("INSERT INTO CLIENTE (ID_CLIENTE, CLI_NOMBRE, CLI_APELLIDO, CLI_TELEFONO) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE CLI_NOMBRE=VALUES(CLI_NOMBRE), CLI_APELLIDO=VALUES(CLI_APELLIDO), CLI_TELEFONO=VALUES(CLI_TELEFONO)");
            $stmtRem->execute([$datos['cliente_id'], $datos['cliente_nombre'], $datos['cliente_apellido'], $datos['cliente_telefono']]);

            $stmtDes = $this->db->prepare("INSERT INTO CLIENTE (ID_CLIENTE, CLI_NOMBRE, CLI_APELLIDO, CLI_TELEFONO) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE CLI_NOMBRE=VALUES(CLI_NOMBRE), CLI_APELLIDO=VALUES(CLI_APELLIDO), CLI_TELEFONO=VALUES(CLI_TELEFONO)");
            $stmtDes->execute([$datos['destinatario_ci'], $datos['destinatario_nombre'], $datos['destinatario_apellido'], $datos['destinatario_telefono']]);

            $queryFactura = "INSERT INTO FACTURA (ID_RUTA, ID_CLIENTE, ID_USUARIO, ID_SUCURSAL, FA_FECHA, FA_HORA, FECHA_VIAJE, FA_CANTIDAD, FA_METODO, FA_SUBTOTAL, FA_TOTAL, FA_DETALLE, FA_TIPO) VALUES (:ruta, :cliente, :usuario, :sucursal, CURDATE(), CURTIME(), CURDATE(), 1, :metodo, :subtotal, :total, :detalle, 'VALOR')";
            $stmtFac = $this->db->prepare($queryFactura);
            $stmtFac->execute([
                ':ruta' => $datos['ruta_id'], ':cliente' => $datos['cliente_id'], ':usuario' => $usuarioId,
                ':sucursal' => $sucursalId, ':metodo' => $datos['metodo_pago'], ':subtotal' => $datos['monto_enviar'],
                ':total' => $datos['monto_total'], ':detalle' => $datos['detalle_destinatario']
            ]);
            $idFactura = $this->db->lastInsertId();

            $queryValor = "INSERT INTO VALOR (ID_FACTURA, ID_DESTINATARIO, VA_ESTADO) VALUES (:factura, :destinatario, 'Por Retirar')";
            $this->db->prepare($queryValor)->execute([':factura' => $idFactura, ':destinatario' => $datos['destinatario_ci']]);

            if ($datos['metodo_pago'] === 'MIXTO') {
                $mEf = floatval($datos['monto_efectivo'] ?? 0);
                $mTr = floatval($datos['monto_transferencia'] ?? 0);
                if ($mEf <= 0 && $mTr <= 0) $mEf = $datos['monto_total'];
                
                $queryCaja = "INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (:usuario, :sucursal, :factura, 'INGRESO', :metodo, :monto, :concepto)";
                $stmtCaja = $this->db->prepare($queryCaja);
                if ($mEf > 0) {
                    $stmtCaja->execute([':usuario' => $usuarioId, ':sucursal' => $sucursalId, ':factura' => $idFactura, ':metodo' => 'EFECTIVO', ':monto' => $mEf, ':concepto' => 'Recepción Envío de Valores (Parte Efectivo)']);
                }
                if ($mTr > 0) {
                    $stmtCaja->execute([':usuario' => $usuarioId, ':sucursal' => $sucursalId, ':factura' => $idFactura, ':metodo' => 'TRANSFERENCIA', ':monto' => $mTr, ':concepto' => 'Recepción Envío de Valores (Parte Transferencia)']);
                }
            } else {
                $queryCaja = "INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (:usuario, :sucursal, :factura, 'INGRESO', :metodo, :monto, 'Recepción Envío de Valores')";
                $this->db->prepare($queryCaja)->execute([':usuario' => $usuarioId, ':sucursal' => $sucursalId, ':factura' => $idFactura, ':metodo' => $datos['metodo_pago'], ':monto' => $datos['monto_total']]);
            }

            $this->db->commit(); return $idFactura;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function entregarValorBD($idValor, $idFactura, $montoAEntregar, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();

            $this->db->prepare("UPDATE VALOR SET VA_ESTADO = 'Entregado', VA_RETIRO = CURDATE() WHERE ID_VALOR = :id")->execute([':id' => $idValor]);

            $queryCaja = "INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (:usuario, :sucursal, :factura, 'EGRESO', 'EFECTIVO', :monto, 'Entrega de Valores a Destinatario')";
            $this->db->prepare($queryCaja)->execute([':usuario' => $usuarioId, ':sucursal' => $sucursalId, ':factura' => $idFactura, ':monto' => $montoAEntregar]);

            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function eliminarValorBD($idFactura, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();
            $stmtCheck = $this->db->prepare("SELECT v.VA_ESTADO, f.FA_TOTAL, f.ID_SUCURSAL FROM VALOR v INNER JOIN FACTURA f ON v.ID_FACTURA = f.ID_FACTURA WHERE f.ID_FACTURA = ?");
            $stmtCheck->execute([$idFactura]);
            $data = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($data && $data['VA_ESTADO'] == 'Por Retirar') {
                $sucursalAfectada = !empty($data['ID_SUCURSAL']) ? $data['ID_SUCURSAL'] : $sucursalId;
                $this->db->prepare("UPDATE VALOR SET VA_ESTADO = 'Anulado' WHERE ID_FACTURA = ?")->execute([$idFactura]);
                $stmtCaja = $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'EGRESO', 'EFECTIVO', ?, 'Anulación de Envío de Valores (Reembolso)')");
                $stmtCaja->execute([$usuarioId, $sucursalAfectada, $idFactura, floatval($data['FA_TOTAL'])]);

                $this->db->prepare("UPDATE SOLICITUD_ANULACION SET ESTADO = 'APROBADA', ID_USUARIO_RESPONDE = ?, FECHA_RESPUESTA = NOW() WHERE TIPO_VENTA = 'VALOR' AND ID_REFERENCIA = ? AND ESTADO = 'PENDIENTE'")
                         ->execute([$usuarioId, $idFactura]);
            }
            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }
}
?>