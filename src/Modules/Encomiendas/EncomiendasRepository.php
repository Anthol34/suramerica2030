<?php
namespace Modules\Encomiendas;

use Platform\Storage\Database;
use Platform\Helpers\ModalidadHelper;
use PDO;
use Exception;

class EncomiendasRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function obtenerRutas() {
        $sql = "
            SELECT r.*,
                   r.RUT_SAHORA AS HORA_SALIDA,
                   op.NOMBRE_COMERCIAL AS OPERADOR_NOMBRE,
                   op.TIPO_OPERADOR AS OPERADOR_TIPO
            FROM RUTA r
            LEFT JOIN OPERADOR op ON r.ID_OPERADOR = op.ID_OPERADOR
            WHERE COALESCE(r.ESTADO, 'ACTIVO') = 'ACTIVO'
            ORDER BY r.RUT_ORIGEN, r.RUT_DESTINO, r.ID_RUTA
        ";
        $rutas = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rutas)) {
            $ids = implode(',', array_map('intval', array_column($rutas, 'ID_RUTA')));
            $sqlTramos = "
                SELECT rt.*,
                       op.NOMBRE_COMERCIAL AS OPERADOR_TRAMO_NOMBRE,
                       COALESCE(tc.TIEMPO_ESTIMADO_HORAS, 0) AS TIEMPO_ESTIMADO_HORAS
                FROM RUTA_TRAMO rt
                LEFT JOIN OPERADOR op ON rt.ID_OPERADOR = op.ID_OPERADOR
                LEFT JOIN tramo_catalogo tc ON rt.ID_TRAMO_CATALOGO = tc.ID_TRAMO_CATALOGO
                WHERE rt.ID_RUTA IN ($ids)
                ORDER BY rt.ID_RUTA, rt.ORDEN ASC
            ";
            $tramosRows = $this->db->query($sqlTramos)->fetchAll(PDO::FETCH_ASSOC);
            $tramosPorRuta = [];
            foreach ($tramosRows as $tr) {
                $tramosPorRuta[$tr['ID_RUTA']][] = $tr;
            }

            foreach ($rutas as &$r) {
                $tramos = $tramosPorRuta[$r['ID_RUTA']] ?? [];
                $r['TRAMOS'] = $tramos;
                $r['NUM_TRAMOS'] = count($tramos);

                $horasTotal = 0;
                foreach ($tramos as $tr) {
                    $horasTotal += floatval($tr['TIEMPO_ESTIMADO_HORAS'] ?? 0);
                }
                $r['TIEMPO_ESTIMADO_HORAS'] = $horasTotal;
                $r['DURACION_FORMATO'] = ($horasTotal > 0) ? "⏱️ {$horasTotal}h aprox." : "";

                $modRaw = strtoupper(trim($r['MODALIDAD'] ?? ''));
                if (empty($modRaw) || $modRaw === 'AUTO') {
                    $modCodigo = ModalidadHelper::resolverModalidadAutomatica(
                        $r['ID_OPERADOR'],
                        $r['TIPO_VIAJE'],
                        $tramos,
                        $r['OPERADOR_NOMBRE'] ?? ''
                    );
                } elseif ($modRaw === 'CONEXION_TRAMOS' || $modRaw === 'CONEXIÓN MULTITRAMO') {
                    $modCodigo = 'CONEXIONES';
                } else {
                    $modCodigo = $modRaw;
                }

                $r['MODALIDAD_CODIGO'] = $modCodigo;
                $r['MODALIDAD_NOMBRE'] = ModalidadHelper::formatearEtiqueta($modCodigo, count($tramos));
                $r['MODALIDAD_BADGE_HTML'] = ModalidadHelper::renderBadge($modCodigo, count($tramos));
                $r['TIENE_PANAMERICANA'] = ($modCodigo === 'PANAMERICANA' || $modCodigo === 'PANA_EXPRESOS');

                $ops = [];
                if (!empty($tramos)) {
                    foreach ($tramos as $tr) {
                        $nomOp = $tr['OPERADOR_TRAMO_NOMBRE'] ?: ($tr['OPERADOR'] ?: '');
                        if (!empty($nomOp) && !in_array($nomOp, $ops)) {
                            $ops[] = $nomOp;
                        }
                    }
                } elseif (!empty($r['OPERADOR_NOMBRE'])) {
                    $ops[] = $r['OPERADOR_NOMBRE'];
                }
                $r['OPERADORES_LISTA'] = $ops;
                $r['OPERADORES_RESUMEN'] = implode(', ', $ops);

                $ciudades = [];
                foreach ($tramos as $tr) {
                    if (empty($ciudades)) $ciudades[] = $tr['ORIGEN_TRAMO'];
                    $ciudades[] = $tr['DESTINO_TRAMO'];
                }
                $r['ITINERARIO_TEXTO'] = !empty($ciudades) ? implode(' ➔ ', $ciudades) : ($r['RUT_ORIGEN'] . ' ➔ ' . $r['RUT_DESTINO']);
                $r['ETIQUETA_MODALIDAD'] = $r['MODALIDAD_NOMBRE'] . ($horasTotal > 0 ? " ({$horasTotal}h)" : "");
            }
            unset($r);
        }

        return $rutas;
    }

    public function obtenerClientePorId($idCliente) {
        $stmt = $this->db->prepare("SELECT * FROM CLIENTE WHERE ID_CLIENTE = ?");
        $stmt->execute([$idCliente]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerDirectorioCorreos() {
        $query = "SELECT u.NOMBRE, u.EMAIL, s.CIUDAD 
                  FROM USUARIO u 
                  INNER JOIN SUCURSAL s ON u.ID_SUCURSAL = s.ID_SUCURSAL 
                  WHERE u.ESTADO = 1 AND u.EMAIL IS NOT NULL AND u.EMAIL != '' 
                  ORDER BY s.CIUDAD ASC, u.NOMBRE ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKpisEncomiendas() {
        $sql = "
            SELECT 
                COUNT(CASE WHEN e.ENC_ESTADO IN ('Recibido', 'En Tránsito', 'En Oficina') THEN 1 END) AS total_activas,
                COALESCE(SUM(CASE WHEN e.ENC_ESTADO IN ('Recibido', 'En Tránsito', 'En Oficina') THEN f.FA_TOTAL ELSE 0 END), 0) AS total_activas_usd,
                COALESCE(SUM(CASE WHEN e.ENC_ESTADO IN ('Recibido', 'En Tránsito', 'En Oficina') THEN e.ENC_PESO ELSE 0 END), 0) AS total_activas_kg,
                
                COUNT(CASE WHEN e.ENC_ESTADO = 'Recibido' THEN 1 END) AS total_recibidos,
                COALESCE(SUM(CASE WHEN e.ENC_ESTADO = 'Recibido' THEN e.ENC_PESO ELSE 0 END), 0) AS total_recibidos_kg,
                
                COUNT(CASE WHEN e.ENC_ESTADO = 'En Tránsito' THEN 1 END) AS total_en_transito,
                COALESCE(SUM(CASE WHEN e.ENC_ESTADO = 'En Tránsito' THEN e.ENC_PESO ELSE 0 END), 0) AS total_en_transito_kg,
                
                COUNT(CASE WHEN e.ENC_ESTADO = 'En Oficina' THEN 1 END) AS total_en_oficina,
                COALESCE(SUM(CASE WHEN e.ENC_ESTADO = 'En Oficina' THEN e.ENC_PESO ELSE 0 END), 0) AS total_en_oficina_kg,
                
                COUNT(CASE WHEN e.ENC_ESTADO = 'Entregado' AND (e.ENC_RETIRO = CURDATE() OR (e.ENC_RETIRO IS NULL AND f.FA_FECHA = CURDATE())) THEN 1 END) AS entregadas_hoy,
                COUNT(CASE WHEN e.ENC_ESTADO = 'Entregado' THEN 1 END) AS total_entregadas,
                COALESCE(SUM(CASE WHEN e.ENC_ESTADO = 'Entregado' THEN f.FA_TOTAL ELSE 0 END), 0) AS total_entregadas_usd,
                COUNT(CASE WHEN e.ENC_ESTADO = 'Anulado' THEN 1 END) AS total_anuladas
            FROM ENCOMIENDA e
            INNER JOIN FACTURA f ON e.ID_FACTURA = f.ID_FACTURA
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function aplicarFiltros(&$query, &$params, $filtros) {
        $tab = $filtros['tab'] ?? 'activos';
        $estado = $filtros['estado'] ?? '';

        if ($tab == 'activos') {
            if (!empty($estado) && in_array($estado, ['Recibido', 'En Tránsito', 'En Oficina'])) {
                $query .= " AND e.ENC_ESTADO = :estado ";
                $params[':estado'] = $estado;
            } else {
                $query .= " AND e.ENC_ESTADO IN ('Recibido', 'En Tránsito', 'En Oficina') ";
            }
        } else {
            $buscar = $filtros['buscar'] ?? '';
            $inicio = $filtros['fecha_inicio'] ?? '';
            $fin = $filtros['fecha_fin'] ?? '';
            
            if ($buscar !== '') {
                $query .= " AND (f.ID_FACTURA = :b_id OR c_rem.ID_CLIENTE LIKE :b_str OR c_rem.CLI_NOMBRE LIKE :b_str OR c_des.CLI_NOMBRE LIKE :b_str) ";
                $params[':b_id'] = (int)$buscar;
                $params[':b_str'] = "%$buscar%";
            }
            if (!empty($estado)) {
                $query .= " AND e.ENC_ESTADO = :estado ";
                $params[':estado'] = $estado;
            }
            if ($inicio && $fin) {
                $query .= " AND f.FA_FECHA BETWEEN :inicio AND :fin ";
                $params[':inicio'] = $inicio;
                $params[':fin'] = $fin;
            }
        }
    }

    public function contarTodas($filtros = []) {
        $query = "SELECT COUNT(*) FROM ENCOMIENDA e INNER JOIN FACTURA f ON e.ID_FACTURA = f.ID_FACTURA INNER JOIN CLIENTE c_rem ON f.ID_CLIENTE = c_rem.ID_CLIENTE LEFT JOIN CLIENTE c_des ON e.ID_DESTINATARIO = c_des.ID_CLIENTE WHERE 1=1 ";
        $params = [];
        $this->aplicarFiltros($query, $params, $filtros);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function obtenerTodas($filtros = [], $offset = 0, $limit = 10) {
        $query = "SELECT e.ID_ENCOMIENDA, e.ENC_CANTIDAD, e.ENC_PESO, e.ENC_VOLUMEN, e.ENC_TIPO, e.ENC_CONTENIDO, e.ENC_ESTADO, e.ENC_RETIRO, e.ENC_LLEGADA, e.DESTINO_FINAL,
                         f.ID_FACTURA, f.FA_FECHA, f.FA_TOTAL, f.FECHA_VIAJE AS FECHA_DESPACHO,
                         c_rem.CLI_NOMBRE AS REM_NOMBRE, c_rem.CLI_APELLIDO AS REM_APELLIDO, c_rem.ID_CLIENTE AS REM_CI,
                         c_des.CLI_NOMBRE AS DES_NOMBRE, c_des.CLI_APELLIDO AS DES_APELLIDO, c_des.ID_CLIENTE AS DES_CI,
                         r.RUT_ORIGEN, r.RUT_DESTINO,
                         (SELECT s.ESTADO FROM SOLICITUD_ANULACION s WHERE s.TIPO_VENTA = 'ENCOMIENDA' AND s.ID_REFERENCIA = f.ID_FACTURA ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as SOLICITUD_ESTADO,
                         (SELECT s.ID_SOLICITUD FROM SOLICITUD_ANULACION s WHERE s.TIPO_VENTA = 'ENCOMIENDA' AND s.ID_REFERENCIA = f.ID_FACTURA ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as ID_SOLICITUD
                  FROM ENCOMIENDA e
                  INNER JOIN FACTURA f ON e.ID_FACTURA = f.ID_FACTURA
                  INNER JOIN CLIENTE c_rem ON f.ID_CLIENTE = c_rem.ID_CLIENTE
                  LEFT JOIN CLIENTE c_des ON e.ID_DESTINATARIO = c_des.ID_CLIENTE
                  INNER JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  WHERE 1=1 ";
        $params = [];
        $this->aplicarFiltros($query, $params, $filtros);
        $limit = (int)$limit;
        $offset = (int)$offset;
        $query .= " ORDER BY CASE WHEN e.ENC_ESTADO = 'Entregado' THEN 2 WHEN e.ENC_ESTADO = 'Anulado' THEN 3 ELSE 0 END ASC, f.FA_FECHA DESC, e.ID_ENCOMIENDA DESC LIMIT $limit OFFSET $offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarEncomiendaBD($datos, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();

            $stmtRem = $this->db->prepare("INSERT INTO CLIENTE (ID_CLIENTE, CLI_NOMBRE, CLI_APELLIDO, CLI_TELEFONO) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE CLI_NOMBRE=VALUES(CLI_NOMBRE), CLI_APELLIDO=VALUES(CLI_APELLIDO), CLI_TELEFONO=VALUES(CLI_TELEFONO)");
            $stmtRem->execute([$datos['cliente_id'], $datos['cliente_nombre'], $datos['cliente_apellido'], $datos['cliente_telefono']]);

            $stmtDes = $this->db->prepare("INSERT INTO CLIENTE (ID_CLIENTE, CLI_NOMBRE, CLI_APELLIDO, CLI_TELEFONO) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE CLI_NOMBRE=VALUES(CLI_NOMBRE), CLI_APELLIDO=VALUES(CLI_APELLIDO), CLI_TELEFONO=VALUES(CLI_TELEFONO)");
            $stmtDes->execute([$datos['destinatario_ci'], $datos['destinatario_nombre'], $datos['destinatario_apellido'], $datos['destinatario_telefono']]);

            $stmtFac = $this->db->prepare("INSERT INTO FACTURA (ID_RUTA, ID_CLIENTE, ID_USUARIO, ID_SUCURSAL, FA_FECHA, FA_HORA, FA_CANTIDAD, FA_METODO, FA_SUBTOTAL, FA_TOTAL, FA_TIPO) VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), 1, ?, ?, ?, 'ENCOMIENDA')");
            $stmtFac->execute([$datos['ruta_id'], $datos['cliente_id'], $usuarioId, $sucursalId, $datos['metodo_pago'], $datos['total_cobrado'], $datos['total_cobrado']]);
            $idFactura = $this->db->lastInsertId();

            $stmtEnc = $this->db->prepare("INSERT INTO ENCOMIENDA (ID_FACTURA, ID_DESTINATARIO, DESTINO_FINAL, ENC_CANTIDAD, ENC_PESO, ENC_VOLUMEN, ENC_TIPO, ENC_CONTENIDO, ENC_ESTADO) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Recibido')");
            $stmtEnc->execute([$idFactura, $datos['destinatario_ci'], $datos['destino_final'], $datos['cantidad_final'], $datos['peso_final'], $datos['volumen'], $datos['tipo_final'], $datos['contenido_final']]);
            
            $stmtCaja = $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'INGRESO', ?, ?, ?)");
            if ($datos['metodo_pago'] === 'MIXTO') {
                $mEf = floatval($datos['monto_efectivo'] ?? 0);
                $mTr = floatval($datos['monto_transferencia'] ?? 0);
                if ($mEf <= 0 && $mTr <= 0) $mEf = $datos['total_cobrado'];
                if ($mEf > 0) {
                    $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, 'EFECTIVO', $mEf, 'Recepción Encomienda (Parte Efectivo)']);
                }
                if ($mTr > 0) {
                    $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, 'TRANSFERENCIA', $mTr, 'Recepción Encomienda (Parte Transferencia)']);
                }
            } else {
                $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, $datos['metodo_pago'], $datos['total_cobrado'], 'Recepción Encomienda']);
            }

            $this->db->commit(); return $idFactura;
        } catch (Exception $e) { $this->db->rollBack(); return "Error SQL: " . $e->getMessage(); }
    }

    public function actualizarEstadoBD($id, $estado) {
        try {
            if ($estado === 'Entregado') {
                $sql = "UPDATE ENCOMIENDA SET ENC_ESTADO = :estado, ENC_RETIRO = CURDATE(), ENC_LLEGADA = COALESCE(ENC_LLEGADA, CURDATE()) WHERE ID_ENCOMIENDA = :id";
            } elseif ($estado === 'En Oficina') {
                $sql = "UPDATE ENCOMIENDA SET ENC_ESTADO = :estado, ENC_LLEGADA = CURDATE(), ENC_RETIRO = NULL WHERE ID_ENCOMIENDA = :id";
            } else {
                $sql = "UPDATE ENCOMIENDA SET ENC_ESTADO = :estado, ENC_RETIRO = NULL, ENC_LLEGADA = NULL WHERE ID_ENCOMIENDA = :id";
            }
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':estado' => $estado, ':id' => $id]);
        } catch (Exception $e) { return false; }
    }

    public function despacharEncomiendasBD($ids, $fecha_despacho) {
        try {
            $this->db->beginTransaction();
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            
            $stmtEnc = $this->db->prepare("UPDATE ENCOMIENDA SET ENC_ESTADO = 'En Tránsito' WHERE ID_ENCOMIENDA IN ($inQuery)");
            $stmtEnc->execute($ids);

            $stmtIds = $this->db->prepare("SELECT ID_FACTURA FROM ENCOMIENDA WHERE ID_ENCOMIENDA IN ($inQuery)");
            $stmtIds->execute($ids);
            $facturas = $stmtIds->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($facturas)) {
                $inFacturas = implode(',', array_fill(0, count($facturas), '?'));
                $paramsFac = array_merge([$fecha_despacho], $facturas);
                $this->db->prepare("UPDATE FACTURA SET FECHA_VIAJE = ? WHERE ID_FACTURA IN ($inFacturas)")->execute($paramsFac);
                
                $stmtRuta = $this->db->prepare("SELECT ID_RUTA FROM FACTURA WHERE ID_FACTURA = ? LIMIT 1");
                $stmtRuta->execute([$facturas[0]]);
                $rutaId = $stmtRuta->fetchColumn();
            }
            $this->db->commit();
            return isset($rutaId) ? ['fecha' => $fecha_despacho, 'ruta' => $rutaId] : true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function registrarLlegadaOficinaBD($ids) {
        try {
            if (empty($ids) || !is_array($ids)) return false;
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            return $this->db->prepare("UPDATE ENCOMIENDA SET ENC_ESTADO = 'En Oficina', ENC_LLEGADA = CURDATE(), ENC_RETIRO = NULL WHERE ID_ENCOMIENDA IN ($inQuery)")->execute($ids);
        } catch (Exception $e) { return false; }
    }

    public function entregarEncomiendasBD($ids) {
        try {
            if (empty($ids) || !is_array($ids)) return false;
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            return $this->db->prepare("UPDATE ENCOMIENDA SET ENC_ESTADO = 'Entregado', ENC_RETIRO = CURDATE(), ENC_LLEGADA = COALESCE(ENC_LLEGADA, CURDATE()) WHERE ID_ENCOMIENDA IN ($inQuery)")->execute($ids);
        } catch (Exception $e) { return false; }
    }

    public function eliminarEncomiendaBD($idFactura, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();
            $stmtCheck = $this->db->prepare("SELECT e.ENC_ESTADO, f.FA_TOTAL, f.ID_SUCURSAL FROM ENCOMIENDA e INNER JOIN FACTURA f ON e.ID_FACTURA = f.ID_FACTURA WHERE f.ID_FACTURA = ?");
            $stmtCheck->execute([$idFactura]);
            $data = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($data && $data['ENC_ESTADO'] != 'Anulado') {
                $sucursalAfectada = !empty($data['ID_SUCURSAL']) ? $data['ID_SUCURSAL'] : $sucursalId;
                $this->db->prepare("UPDATE ENCOMIENDA SET ENC_ESTADO = 'Anulado' WHERE ID_FACTURA = ?")->execute([$idFactura]);
                $stmtCaja = $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'EGRESO', 'EFECTIVO', ?, 'Anulación de Encomienda (Reembolso)')");
                $stmtCaja->execute([$usuarioId, $sucursalAfectada, $idFactura, floatval($data['FA_TOTAL'])]);

                // Auto-aprobar cualquier solicitud de anulación pendiente para esta encomienda
                $this->db->prepare("UPDATE SOLICITUD_ANULACION SET ESTADO = 'APROBADA', ID_USUARIO_RESPONDE = ?, FECHA_RESPUESTA = NOW() WHERE TIPO_VENTA = 'ENCOMIENDA' AND ID_REFERENCIA = ? AND ESTADO = 'PENDIENTE'")
                         ->execute([$usuarioId, $idFactura]);
            }
            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function obtenerDespachosProgramados($fechaInicio, $fechaFin) {
        $query = "SELECT DISTINCT f.FECHA_VIAJE AS FECHA_DESPACHO, r.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO, COUNT(e.ID_ENCOMIENDA) as TOTAL_PAQUETES
                  FROM FACTURA f INNER JOIN ENCOMIENDA e ON f.ID_FACTURA = e.ID_FACTURA INNER JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  WHERE f.FA_TIPO = 'ENCOMIENDA' AND f.FECHA_VIAJE IS NOT NULL AND e.ENC_ESTADO != 'Anulado' AND f.FECHA_VIAJE BETWEEN :inicio AND :fin
                  GROUP BY f.FECHA_VIAJE, r.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO ORDER BY f.FECHA_VIAJE DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerListaCarga($fechaDespacho, $rutaId) {
        $query = "SELECT e.ID_ENCOMIENDA, e.ENC_CANTIDAD, e.ENC_PESO, e.ENC_VOLUMEN, e.ENC_TIPO, e.ENC_CONTENIDO, e.ENC_ESTADO, e.DESTINO_FINAL,
                         f.ID_FACTURA, f.FA_FECHA, f.FA_TOTAL, f.FECHA_VIAJE AS FECHA_DESPACHO,
                         r.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO,
                         c_rem.CLI_NOMBRE AS REM_NOMBRE, c_rem.CLI_APELLIDO AS REM_APELLIDO, c_rem.CLI_TELEFONO AS REM_TEL, c_rem.ID_CLIENTE AS REM_CI,
                         c_des.CLI_NOMBRE AS DES_NOMBRE, c_des.CLI_APELLIDO AS DES_APELLIDO, c_des.ID_CLIENTE AS DES_CI, c_des.CLI_TELEFONO AS DES_TEL
                  FROM ENCOMIENDA e 
                  INNER JOIN FACTURA f ON e.ID_FACTURA = f.ID_FACTURA 
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  INNER JOIN CLIENTE c_rem ON f.ID_CLIENTE = c_rem.ID_CLIENTE
                  LEFT JOIN CLIENTE c_des ON e.ID_DESTINATARIO = c_des.ID_CLIENTE
                  WHERE f.FECHA_VIAJE = :fecha AND f.ID_RUTA = :ruta AND e.ENC_ESTADO != 'Anulado' ORDER BY e.ID_ENCOMIENDA ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':fecha' => $fechaDespacho, ':ruta' => $rutaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerListaCargaEnTransito($rutaId = null) {
        $query = "SELECT e.ID_ENCOMIENDA, e.ENC_CANTIDAD, e.ENC_PESO, e.ENC_VOLUMEN, e.ENC_TIPO, e.ENC_CONTENIDO, e.ENC_ESTADO, e.DESTINO_FINAL,
                         f.ID_FACTURA, f.FA_FECHA, f.FA_TOTAL, f.FECHA_VIAJE AS FECHA_DESPACHO,
                         r.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO,
                         c_rem.CLI_NOMBRE AS REM_NOMBRE, c_rem.CLI_APELLIDO AS REM_APELLIDO, c_rem.CLI_TELEFONO AS REM_TEL, c_rem.ID_CLIENTE AS REM_CI,
                         c_des.CLI_NOMBRE AS DES_NOMBRE, c_des.CLI_APELLIDO AS DES_APELLIDO, c_des.ID_CLIENTE AS DES_CI, c_des.CLI_TELEFONO AS DES_TEL
                  FROM ENCOMIENDA e 
                  INNER JOIN FACTURA f ON e.ID_FACTURA = f.ID_FACTURA 
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  INNER JOIN CLIENTE c_rem ON f.ID_CLIENTE = c_rem.ID_CLIENTE
                  LEFT JOIN CLIENTE c_des ON e.ID_DESTINATARIO = c_des.ID_CLIENTE
                  WHERE e.ENC_ESTADO = 'En Tránsito' ";
        $params = [];
        if (!empty($rutaId)) {
            $query .= " AND f.ID_RUTA = :ruta ";
            $params[':ruta'] = (int)$rutaId;
        }
        $query .= " ORDER BY f.FECHA_VIAJE DESC, r.RUT_ORIGEN ASC, e.ID_ENCOMIENDA ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarEncomiendasEnTransito($rutaId = null) {
        $query = "SELECT COUNT(*) FROM ENCOMIENDA e INNER JOIN FACTURA f ON e.ID_FACTURA = f.ID_FACTURA WHERE e.ENC_ESTADO = 'En Tránsito'";
        $params = [];
        if (!empty($rutaId)) {
            $query .= " AND f.ID_RUTA = :ruta";
            $params[':ruta'] = (int)$rutaId;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Unifica múltiples despachos en una sola ruta y fecha destino.
     * Actualiza todas las facturas de encomiendas de los despachos seleccionados.
     */
    public function unificarDespachosBD($listaDespachos, $rutaDestinoId, $fechaDestino) {
        try {
            $this->db->beginTransaction();
            $rutaDestinoId = (int)$rutaDestinoId;

            if (empty($listaDespachos) || $rutaDestinoId <= 0 || empty($fechaDestino)) {
                throw new Exception("Datos incompletos para unificar los despachos.");
            }

            foreach ($listaDespachos as $item) {
                if (is_string($item)) {
                    $parts = explode('|', $item);
                    $f = trim($parts[0] ?? '');
                    $r = (int)($parts[1] ?? 0);
                } else {
                    $f = trim($item['fecha'] ?? '');
                    $r = (int)($item['ruta_id'] ?? 0);
                }

                if (!empty($f) && $r > 0) {
                    $stmt = $this->db->prepare("
                        UPDATE FACTURA f
                        INNER JOIN ENCOMIENDA e ON f.ID_FACTURA = e.ID_FACTURA
                        SET f.ID_RUTA = :nuevaRuta, f.FECHA_VIAJE = :nuevaFecha
                        WHERE f.FA_TIPO = 'ENCOMIENDA' 
                          AND f.FECHA_VIAJE = :fechaAnt 
                          AND f.ID_RUTA = :rutaAnt
                          AND e.ENC_ESTADO != 'Anulado'
                    ");
                    $stmt->execute([
                        ':nuevaRuta'  => $rutaDestinoId,
                        ':nuevaFecha' => $fechaDestino,
                        ':fechaAnt'   => $f,
                        ':rutaAnt'    => $r
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

    /**
     * Obtiene la lista combinada de paquetes de varios despachos seleccionados.
     */
    public function obtenerListaCargaConsolidada($listaDespachos) {
        if (empty($listaDespachos)) return [];

        $whereClauses = [];
        $params = [];
        $idx = 0;

        foreach ($listaDespachos as $item) {
            if (is_string($item)) {
                $parts = explode('|', $item);
                $f = trim($parts[0] ?? '');
                $r = (int)($parts[1] ?? 0);
            } else {
                $f = trim($item['fecha'] ?? '');
                $r = (int)($item['ruta_id'] ?? 0);
            }

            if (!empty($f) && $r > 0) {
                $whereClauses[] = "(f.FECHA_VIAJE = :f_$idx AND f.ID_RUTA = :r_$idx)";
                $params[":f_$idx"] = $f;
                $params[":r_$idx"] = $r;
                $idx++;
            }
        }

        if (empty($whereClauses)) return [];

        $sqlWhere = implode(' OR ', $whereClauses);

        $query = "SELECT e.ID_ENCOMIENDA, e.ENC_CANTIDAD, e.ENC_PESO, e.ENC_VOLUMEN, e.ENC_TIPO, e.ENC_CONTENIDO, e.ENC_ESTADO, e.DESTINO_FINAL,
                         f.ID_FACTURA, f.FA_FECHA, f.FA_TOTAL, f.FECHA_VIAJE AS FECHA_DESPACHO,
                         r.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO,
                         c_rem.CLI_NOMBRE AS REM_NOMBRE, c_rem.CLI_APELLIDO AS REM_APELLIDO, c_rem.CLI_TELEFONO AS REM_TEL, c_rem.ID_CLIENTE AS REM_CI,
                         c_des.CLI_NOMBRE AS DES_NOMBRE, c_des.CLI_APELLIDO AS DES_APELLIDO, c_des.ID_CLIENTE AS DES_CI, c_des.CLI_TELEFONO AS DES_TEL
                  FROM ENCOMIENDA e 
                  INNER JOIN FACTURA f ON e.ID_FACTURA = f.ID_FACTURA 
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  INNER JOIN CLIENTE c_rem ON f.ID_CLIENTE = c_rem.ID_CLIENTE
                  LEFT JOIN CLIENTE c_des ON e.ID_DESTINATARIO = c_des.ID_CLIENTE
                  WHERE e.ENC_ESTADO != 'Anulado' AND ($sqlWhere)
                  ORDER BY r.RUT_ORIGEN ASC, r.RUT_DESTINO ASC, e.ID_ENCOMIENDA ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>