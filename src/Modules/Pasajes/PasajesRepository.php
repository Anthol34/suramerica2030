<?php
namespace Modules\Pasajes;

use Platform\Storage\Database;
use PDO;
use Exception;

class PasajesRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function obtenerRutas() {
        return $this->db->query("SELECT * FROM RUTA WHERE COALESCE(ESTADO, 'ACTIVO') = 'ACTIVO' ORDER BY RUT_ORIGEN, RUT_DESTINO")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerBuses() {
        return $this->db->query("SELECT * FROM BUS ORDER BY BUS_MARCA, BUS_MODELO")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKpisPasajes() {
        $sql = "
            SELECT 
                COUNT(CASE WHEN p.ESTADO != 'DEVUELTO' AND (f.FECHA_VIAJE >= CURDATE() OR f.FECHA_VIAJE IS NULL) THEN 1 END) AS total_activos,
                COALESCE(SUM(CASE WHEN p.ESTADO != 'DEVUELTO' AND (f.FECHA_VIAJE >= CURDATE() OR f.FECHA_VIAJE IS NULL) THEN p.PRECIO_TOTAL ELSE 0 END), 0) AS total_activos_usd,
                
                COUNT(CASE WHEN p.ESTADO != 'DEVUELTO' AND f.FECHA_VIAJE = CURDATE() THEN 1 END) AS viajan_hoy,
                COALESCE(SUM(CASE WHEN p.ESTADO != 'DEVUELTO' AND f.FECHA_VIAJE = CURDATE() THEN p.PRECIO_TOTAL ELSE 0 END), 0) AS viajan_hoy_usd,

                COUNT(CASE WHEN p.ESTADO != 'DEVUELTO' AND f.FECHA_VIAJE BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) AS viajan_proximos_7,

                COUNT(CASE WHEN f.FA_FECHA = CURDATE() AND p.ESTADO != 'DEVUELTO' THEN 1 END) AS vendidos_hoy,
                COALESCE(SUM(CASE WHEN f.FA_FECHA = CURDATE() AND p.ESTADO != 'DEVUELTO' THEN (p.PRECIO_TOTAL - p.SALDO) ELSE 0 END), 0) AS cobrado_hoy_usd,

                COUNT(CASE WHEN p.ESTADO != 'DEVUELTO' AND f.FECHA_VIAJE IS NULL THEN 1 END) AS fecha_abierta,

                COUNT(CASE WHEN p.ESTADO = 'RESERVA' THEN 1 END) AS reservas_pendientes,
                COALESCE(SUM(CASE WHEN p.ESTADO = 'RESERVA' THEN p.SALDO ELSE 0 END), 0) AS saldo_reservas_usd,

                COUNT(CASE WHEN p.ESTADO = 'PAGADO' THEN 1 END) AS total_pagados,
                COALESCE(SUM(CASE WHEN p.ESTADO = 'PAGADO' THEN p.PRECIO_TOTAL ELSE 0 END), 0) AS total_pagados_usd,
                COUNT(CASE WHEN p.ESTADO = 'DEVUELTO' THEN 1 END) AS total_devueltos
            FROM PASAJE p
            INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function contarTodos($filtros = []) {
        $query = "SELECT COUNT(*) FROM PASAJE p INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA LEFT JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE WHERE 1=1 ";
        $params = [];
        $this->aplicarFiltros($query, $params, $filtros);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function obtenerTodos($filtros = [], $offset = 0, $limit = 10) {
        $colsRuta = $this->db->query("DESCRIBE RUTA")->fetchAll(PDO::FETCH_COLUMN);
        $colAdmiteSp = in_array('ADMITE_SOBREPESO', $colsRuta) ? "COALESCE(r.ADMITE_SOBREPESO, 1) as ADMITE_SOBREPESO," : "1 as ADMITE_SOBREPESO,";

        $query = "SELECT p.*, f.FECHA_VIAJE, f.FA_FECHA, f.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO, 
                         r.TIPO_VIAJE, r.MODALIDAD,
                         $colAdmiteSp
                         COALESCE(r.PRECIO_PASAJE, 0.00) as RUTA_PRECIO,
                         f.FA_TOTAL, f.FA_SUBTOTAL, f.FA_METODO,
                         COALESCE(r.PRECIO_SOBREPESO_KG, 1.00) as PRECIO_SOBREPESO_KG,
                         COALESCE(r.LIMITE_EQUIPAJE_KG, 35.00) as LIMITE_EQUIPAJE_KG,
                         c.CLI_NOMBRE, c.CLI_APELLIDO, COALESCE(b.BUS_PLACA, 'SIN ASIGNAR') as BUS_PLACA,
                         COALESCE(b.BUS_ASIENTOS, 40) as BUS_ASIENTOS,
                         COALESCE(p.ID_FACTURA_SOBREPESO, (SELECT MAX(ID_FACTURA) FROM FACTURA WHERE ID_CLIENTE = p.ID_CLIENTE AND FA_TIPO = 'SOBREPESO' AND FECHA_VIAJE = f.FECHA_VIAJE)) as ID_FACTURA_SOBREPESO,
                          (SELECT s.ID_SOBREPESO FROM SOBREPESO s WHERE s.ID_PASAJE = p.ID_PASAJE AND s.ESTADO = 'ACTIVO' ORDER BY s.ID_SOBREPESO DESC LIMIT 1) as ID_SOBREPESO_ACTIVO,
                          (SELECT s.SP_TOTAL FROM SOBREPESO s WHERE s.ID_PASAJE = p.ID_PASAJE AND s.ESTADO = 'ACTIVO' ORDER BY s.ID_SOBREPESO DESC LIMIT 1) as SOBREPESO_TOTAL_ACTIVO,
                          (SELECT s.SP_EXCESO_KG FROM SOBREPESO s WHERE s.ID_PASAJE = p.ID_PASAJE AND s.ESTADO = 'ACTIVO' ORDER BY s.ID_SOBREPESO DESC LIMIT 1) as SOBREPESO_KG_ACTIVO,
                          (SELECT s.SP_OPERADOR FROM SOBREPESO s WHERE s.ID_PASAJE = p.ID_PASAJE AND s.ESTADO = 'ACTIVO' ORDER BY s.ID_SOBREPESO DESC LIMIT 1) as SOBREPESO_OPERADOR_ACTIVO,
                          (SELECT s.SP_TRAMO FROM SOBREPESO s WHERE s.ID_PASAJE = p.ID_PASAJE AND s.ESTADO = 'ACTIVO' ORDER BY s.ID_SOBREPESO DESC LIMIT 1) as SOBREPESO_TRAMO_ACTIVO,
                          (SELECT s.ID_FACTURA FROM SOBREPESO s WHERE s.ID_PASAJE = p.ID_PASAJE AND s.ESTADO = 'ACTIVO' ORDER BY s.ID_SOBREPESO DESC LIMIT 1) as SOBREPESO_FACTURA_ACTIVA,
                          (SELECT s.FECHA_CREACION FROM SOBREPESO s WHERE s.ID_PASAJE = p.ID_PASAJE AND s.ESTADO = 'ACTIVO' ORDER BY s.ID_SOBREPESO DESC LIMIT 1) as SOBREPESO_FECHA_ACTIVA,
                         (SELECT s.ESTADO FROM SOLICITUD_ANULACION s WHERE s.TIPO_VENTA = 'PASAJE' AND s.ID_REFERENCIA = p.ID_PASAJE ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as SOLICITUD_ESTADO,
                         (SELECT s.ID_SOLICITUD FROM SOLICITUD_ANULACION s WHERE s.TIPO_VENTA = 'PASAJE' AND s.ID_REFERENCIA = p.ID_PASAJE ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as ID_SOLICITUD,
                         (SELECT s.MOTIVO FROM SOLICITUD_ANULACION s WHERE s.TIPO_VENTA = 'PASAJE' AND s.ID_REFERENCIA = p.ID_PASAJE ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as SOLICITUD_MOTIVO,
                         (SELECT s.MONTO_DEVOLVER FROM SOLICITUD_ANULACION s WHERE s.TIPO_VENTA = 'PASAJE' AND s.ID_REFERENCIA = p.ID_PASAJE ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as SOLICITUD_MONTO,
                         (SELECT u.NOMBRE FROM SOLICITUD_ANULACION s LEFT JOIN USUARIO u ON s.ID_USUARIO_SOLICITA = u.ID_USUARIO WHERE s.TIPO_VENTA = 'PASAJE' AND s.ID_REFERENCIA = p.ID_PASAJE ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as SOLICITUD_USUARIO_NOMBRE,
                         (SELECT s.FECHA_SOLICITUD FROM SOLICITUD_ANULACION s WHERE s.TIPO_VENTA = 'PASAJE' AND s.ID_REFERENCIA = p.ID_PASAJE ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as SOLICITUD_FECHA,
                         (SELECT s.MOTIVO_RESPUESTA FROM SOLICITUD_ANULACION s WHERE s.TIPO_VENTA = 'PASAJE' AND s.ID_REFERENCIA = p.ID_PASAJE ORDER BY s.ID_SOLICITUD DESC LIMIT 1) as SOLICITUD_MOTIVO_RESPUESTA
                  FROM PASAJE p
                  INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA
                  LEFT JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS
                  WHERE 1=1 ";
        $params = [];
        $this->aplicarFiltros($query, $params, $filtros);
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $tab = $filtros['tab'] ?? 'activos';
        $tipoFecha = $filtros['tipo_fecha'] ?? 'viaje';
        if ($tab == 'historial' && $tipoFecha === 'viaje') {
            $query .= " ORDER BY COALESCE(f.FECHA_VIAJE, f.FA_FECHA) DESC, f.FA_FECHA DESC, p.ID_PASAJE DESC LIMIT $limit OFFSET $offset";
        } else {
            $query .= " ORDER BY f.FA_FECHA DESC, p.ID_PASAJE DESC LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $pasajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cargar tramos operativos para cada ruta presente en los pasajes
        if (!empty($pasajes)) {
            $rutasIds = array_unique(array_filter(array_column($pasajes, 'ID_RUTA')));
            $tramosMap = [];
            if (!empty($rutasIds)) {
                $inRutas = implode(',', array_map('intval', $rutasIds));
                $stmtTr = $this->db->query("SELECT * FROM RUTA_TRAMO WHERE ID_RUTA IN ($inRutas) ORDER BY ORDEN ASC");
                $tramosRows = $stmtTr ? $stmtTr->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($tramosRows as $tr) {
                    $tramosMap[$tr['ID_RUTA']][] = $tr;
                }
            }
            foreach ($pasajes as &$pax) {
                $pax['TRAMOS'] = $tramosMap[$pax['ID_RUTA']] ?? [];
            }
            unset($pax);
        }

        return $pasajes;
    }

    private function aplicarFiltros(&$query, &$params, $filtros) {
        $tab = $filtros['tab'] ?? 'activos';
        $buscar = trim($filtros['buscar'] ?? '');
        $filtroRapido = trim($filtros['filtro_rapido'] ?? '');
        
        if ($tab == 'activos') {
            if ($filtroRapido === 'hoy') {
                $query .= " AND p.ESTADO != 'DEVUELTO' AND f.FECHA_VIAJE = CURDATE() ";
            } elseif ($filtroRapido === 'proximos_7') {
                $query .= " AND p.ESTADO != 'DEVUELTO' AND f.FECHA_VIAJE BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ";
            } elseif ($filtroRapido === 'abiertas') {
                $query .= " AND p.ESTADO != 'DEVUELTO' AND f.FECHA_VIAJE IS NULL ";
            } elseif ($filtroRapido === 'reservas') {
                $query .= " AND p.ESTADO = 'RESERVA' AND (f.FECHA_VIAJE >= CURDATE() OR f.FECHA_VIAJE IS NULL) ";
            } else {
                $query .= " AND p.ESTADO != 'DEVUELTO' AND (f.FECHA_VIAJE >= CURDATE() OR f.FECHA_VIAJE IS NULL) ";
            }

            if ($buscar !== '') {
                $query .= " AND (f.ID_FACTURA = :b_id OR c.ID_CLIENTE LIKE :b_str OR c.CLI_NOMBRE LIKE :b_str OR c.CLI_APELLIDO LIKE :b_str OR CONCAT(c.CLI_NOMBRE, ' ', c.CLI_APELLIDO) LIKE :b_str OR p.PAX_ASIENTO = :b_asi) ";
                $params[':b_id'] = is_numeric($buscar) ? (int)$buscar : 0;
                $params[':b_str'] = "%$buscar%";
                $params[':b_asi'] = $buscar;
            }
        } else {
            // Pestaña Historial y Buscador Avanzado
            if ($buscar !== '') {
                $query .= " AND (f.ID_FACTURA = :b_id OR c.ID_CLIENTE LIKE :b_str OR c.CLI_NOMBRE LIKE :b_str OR c.CLI_APELLIDO LIKE :b_str OR CONCAT(c.CLI_NOMBRE, ' ', c.CLI_APELLIDO) LIKE :b_str OR p.PAX_ASIENTO = :b_asi) ";
                $params[':b_id'] = is_numeric($buscar) ? (int)$buscar : 0;
                $params[':b_str'] = "%$buscar%";
                $params[':b_asi'] = $buscar;
            }
            
            $estado = trim($filtros['estado'] ?? '');
            if ($estado !== '') {
                $query .= " AND p.ESTADO = :p_estado ";
                $params[':p_estado'] = $estado;
            }
            
            $rutaId = (int)($filtros['ruta_id'] ?? 0);
            if ($rutaId > 0) {
                $query .= " AND f.ID_RUTA = :f_ruta ";
                $params[':f_ruta'] = $rutaId;
            }
            
            $tipoFecha = $filtros['tipo_fecha'] ?? 'viaje';
            $fechaInicio = trim($filtros['fecha_inicio'] ?? '');
            $fechaFin = trim($filtros['fecha_fin'] ?? '');
            $diaSemana = trim($filtros['dia_semana'] ?? '');
            
            if ($tipoFecha === 'abierta') {
                $query .= " AND f.FECHA_VIAJE IS NULL ";
            } else {
                $campoFecha = ($tipoFecha === 'emision') ? 'f.FA_FECHA' : 'f.FECHA_VIAJE';
                
                if ($fechaInicio !== '' && $fechaFin !== '') {
                    $query .= " AND $campoFecha BETWEEN :f_inicio AND :f_fin ";
                    $params[':f_inicio'] = $fechaInicio;
                    $params[':f_fin'] = $fechaFin;
                } elseif ($fechaInicio !== '') {
                    $query .= " AND $campoFecha >= :f_inicio ";
                    $params[':f_inicio'] = $fechaInicio;
                } elseif ($fechaFin !== '') {
                    $query .= " AND $campoFecha <= :f_fin ";
                    $params[':f_fin'] = $fechaFin;
                }
                
                if ($diaSemana !== '') {
                    if ($diaSemana === 'fin_semana') {
                        $query .= " AND DAYOFWEEK($campoFecha) IN (1, 7) ";
                    } elseif ($diaSemana === 'entre_semana') {
                        $query .= " AND DAYOFWEEK($campoFecha) BETWEEN 2 AND 6 ";
                    } elseif (is_numeric($diaSemana) && (int)$diaSemana >= 1 && (int)$diaSemana <= 7) {
                        $query .= " AND DAYOFWEEK($campoFecha) = :dia_semana ";
                        $params[':dia_semana'] = (int)$diaSemana;
                    }
                }
            }
        }
    }

    public function obtenerClientePorId($idCliente) {
        $stmt = $this->db->prepare("SELECT * FROM CLIENTE WHERE ID_CLIENTE = ?");
        $stmt->execute([$idCliente]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerAsientosOcupados($fecha, $ruta, $bus) {
        $query = "SELECT p.PAX_ASIENTO 
                  FROM PASAJE p 
                  INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA 
                  WHERE f.FECHA_VIAJE = :fecha 
                    AND f.ID_RUTA = :ruta 
                    AND p.ID_BUS = :bus 
                    AND (p.ESTADO != 'DEVUELTO' OR p.ESTADO IS NULL)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindValue(':ruta', (int)$ruta, PDO::PARAM_INT);
        $stmt->bindValue(':bus', (int)$bus, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDetallePasaje($idPasaje) {
        $query = "SELECT p.*, f.FECHA_VIAJE, c.CLI_NOMBRE, c.CLI_APELLIDO, c.ID_CLIENTE,
                         r.RUT_ORIGEN, r.RUT_DESTINO, r.TIPO_VIAJE, r.MODALIDAD, b.BUS_PLACA,
                         rt.ORIGEN_TRAMO, rt.DESTINO_TRAMO, rt.OPERADOR as OPERADOR_TRAMO
                  FROM PASAJE p
                  INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA
                  LEFT JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS
                  LEFT JOIN RUTA_TRAMO rt ON p.ID_RUTA_TRAMO_SOBREPESO = rt.ID_RUTA_TRAMO
                  WHERE p.ID_PASAJE = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idPasaje]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si no se asignó un tramo específico de sobrepeso pero la ruta tiene tramos (ej. Panamericana), tomar el primer tramo operativo
        if ($detalle && empty($detalle['DESTINO_TRAMO']) && !empty($detalle['ID_RUTA'])) {
            $stmtFirstTr = $this->db->prepare("SELECT ORIGEN_TRAMO, DESTINO_TRAMO, OPERADOR FROM RUTA_TRAMO WHERE ID_RUTA = ? ORDER BY ORDEN ASC LIMIT 1");
            $stmtFirstTr->execute([$detalle['ID_RUTA']]);
            $firstTr = $stmtFirstTr->fetch(PDO::FETCH_ASSOC);
            if ($firstTr) {
                $detalle['ORIGEN_TRAMO'] = $firstTr['ORIGEN_TRAMO'];
                $detalle['DESTINO_TRAMO'] = $firstTr['DESTINO_TRAMO'];
                $detalle['OPERADOR_TRAMO'] = $firstTr['OPERADOR'];
            }
        }
        return $detalle;
    }

    public function actualizarCodigoSocio($idPasaje, $codigoSocio) {
        $stmt = $this->db->prepare("UPDATE PASAJE SET CODIGO_SOCIO = ? WHERE ID_PASAJE = ?");
        return $stmt->execute([trim($codigoSocio), (int)$idPasaje]);
    }

    public function guardarVentaBD($datos, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();

            $stmtCli = $this->db->prepare("INSERT INTO CLIENTE (ID_CLIENTE, CLI_NOMBRE, CLI_APELLIDO, CLI_TELEFONO, CLI_CORREO, CLI_NACIONALIDAD, CLI_FECHANAC) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE CLI_NOMBRE=VALUES(CLI_NOMBRE), CLI_APELLIDO=VALUES(CLI_APELLIDO), CLI_TELEFONO=VALUES(CLI_TELEFONO), CLI_CORREO=VALUES(CLI_CORREO), CLI_NACIONALIDAD=VALUES(CLI_NACIONALIDAD), CLI_FECHANAC=VALUES(CLI_FECHANAC)");
            $stmtFac = $this->db->prepare("INSERT INTO FACTURA (ID_RUTA, ID_CLIENTE, ID_USUARIO, ID_SUCURSAL, FA_FECHA, FA_HORA, FECHA_VIAJE, FA_CANTIDAD, FA_METODO, FA_SUBTOTAL, FA_TOTAL, FA_TIPO) VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?)");
            $stmtPax = $this->db->prepare("INSERT INTO PASAJE (ID_FACTURA, ID_BUS, PAX_ASIENTO, ID_CLIENTE, ESTADO, PRECIO_TOTAL, SALDO) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtInsTramo = $this->db->prepare("INSERT INTO PASAJE_TRAMO (ID_PASAJE, ID_RUTA_TRAMO, FECHA_TRAMO, ID_BUS, PAX_ASIENTO, ESTADO_TRAMO) VALUES (?, ?, ?, ?, ?, 'PENDIENTE')");

            // 1. Registrar o actualizar a todos los pasajeros en la tabla CLIENTE primero
            foreach ($datos['pasajeros'] as $pax) {
                $stmtCli->execute([
                    $pax['id'], 
                    $pax['nombre'], 
                    $pax['apellido'], 
                    $pax['telefono'], 
                    $pax['correo'], 
                    $pax['nacionalidad'], 
                    $pax['fechanac']
                ]);
            }

            // 2. Ahora que el titular (y todos los pasajeros) existen en CLIENTE, crear la FACTURA
            $stmtFac->execute([
                $datos['ruta_id'], 
                $datos['titular_id'], 
                $usuarioId, 
                $sucursalId, 
                $datos['fecha_viaje'], 
                $datos['cantidad_total'], 
                $datos['metodo_pago'], 
                $datos['monto_abonar'], 
                $datos['monto_abonar'], 
                $datos['tipo_factura']
            ]);
            $idFactura = $this->db->lastInsertId();

            // 3. Consultar tramos de conexión si aplican
            $stmtTramos = $this->db->prepare("SELECT * FROM RUTA_TRAMO WHERE ID_RUTA = ? ORDER BY ORDEN ASC");
            $stmtTramos->execute([$datos['ruta_id']]);
            $tramos = $stmtTramos->fetchAll(PDO::FETCH_ASSOC);

            // 4. Crear los pasajes individuales y sus conexiones
            foreach ($datos['pasajeros'] as $pax) {
                $stmtPax->execute([
                    $idFactura, 
                    $datos['bus_id'], 
                    $pax['asiento'], 
                    $pax['id'], 
                    $datos['estado_individual'], 
                    $datos['precio_individual'], 
                    $datos['saldo_individual']
                ]);
                $idPasajeGenerado = $this->db->lastInsertId();

                if (!empty($tramos)) {
                    foreach ($tramos as $index => $tramo) {
                        $tramoBus = ($index === 0) ? $datos['bus_id'] : null;
                        $tramoAsiento = ($index === 0) ? $pax['asiento'] : null;
                        $tramoFecha = ($index === 0) ? $datos['fecha_viaje'] : null;
                        $stmtInsTramo->execute([$idPasajeGenerado, $tramo['ID_RUTA_TRAMO'], $tramoFecha, $tramoBus, $tramoAsiento]);
                    }
                }
            }

            // 5. Registrar movimiento de ingreso en caja
            $conceptoStr = ($datos['cantidad_total'] > 1) ? "Venta/Reserva de {$datos['cantidad_total']} Pasajes" : "Venta/Reserva Pasaje";
            $stmtCaja = $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'INGRESO', ?, ?, ?)");
            if ($datos['metodo_pago'] === 'MIXTO') {
                $mEf = floatval($datos['monto_efectivo'] ?? 0);
                $mTr = floatval($datos['monto_transferencia'] ?? 0);
                if ($mEf <= 0 && $mTr <= 0) $mEf = floatval($datos['monto_abonar']);
                if ($mEf > 0) {
                    $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, 'EFECTIVO', $mEf, $conceptoStr . ' (Parte Efectivo)']);
                }
                if ($mTr > 0) {
                    $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, 'TRANSFERENCIA', $mTr, $conceptoStr . ' (Parte Transferencia)']);
                }
            } else {
                $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, $datos['metodo_pago'], $datos['monto_abonar'], $conceptoStr]);
            }

            $this->db->commit();
            return ['status' => 'success', 'id_factura' => $idFactura];
        } catch (Exception $e) { 
            $this->db->rollBack(); 
            return ['status' => 'error', 'mensaje' => $e->getMessage()]; 
        }
    }

    public function registrarAbonoBD($datos, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();
            $monto = floatval($datos['monto_abono']);

            $stmtPax = $this->db->prepare("SELECT SALDO FROM PASAJE WHERE ID_PASAJE = ? FOR UPDATE");
            $stmtPax->execute([$datos['id_pasaje']]);
            $pasaje = $stmtPax->fetch(PDO::FETCH_ASSOC);

            // Resta matemática segura del saldo
            $nuevoSaldo = max(0, floatval($pasaje['SALDO']) - $monto);
            $nuevoEstado = ($nuevoSaldo <= 0) ? 'PAGADO' : 'RESERVA';

            $stmtRuta = $this->db->prepare("SELECT f.ID_RUTA, f.FECHA_VIAJE FROM FACTURA f INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA WHERE p.ID_PASAJE = ?");
            $stmtRuta->execute([$datos['id_pasaje']]);
            $datosOriginales = $stmtRuta->fetch(PDO::FETCH_ASSOC);
            $rutaId = $datosOriginales ? $datosOriginales['ID_RUTA'] : 1; 
            $fechaViaje = $datosOriginales ? $datosOriginales['FECHA_VIAJE'] : date('Y-m-d');

            $stmtFac = $this->db->prepare("INSERT INTO FACTURA (ID_RUTA, ID_CLIENTE, ID_USUARIO, ID_SUCURSAL, FA_FECHA, FA_HORA, FECHA_VIAJE, FA_CANTIDAD, FA_METODO, FA_SUBTOTAL, FA_TOTAL, FA_TIPO) VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), ?, 1, ?, ?, ?, 'ABONO PASAJE')");
            $stmtFac->execute([$rutaId, $datos['cliente_id'], $usuarioId, $sucursalId, $fechaViaje, $datos['metodo_pago'], $monto, $monto]);
            $idFactura = $this->db->lastInsertId();

            $this->db->prepare("INSERT INTO ABONO (ID_PASAJE, ID_FACTURA, MONTO) VALUES (?, ?, ?)")->execute([$datos['id_pasaje'], $idFactura, $monto]);
            $this->db->prepare("UPDATE PASAJE SET SALDO = ?, ESTADO = ? WHERE ID_PASAJE = ?")->execute([$nuevoSaldo, $nuevoEstado, $datos['id_pasaje']]);
            
            if ($datos['metodo_pago'] === 'MIXTO') {
                $mEf = floatval($datos['monto_efectivo'] ?? 0);
                $mTr = floatval($datos['monto_transferencia'] ?? 0);
                if ($mEf <= 0 && $mTr <= 0) $mEf = $monto;
                $stmtCaja = $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'INGRESO', ?, ?, ?)");
                if ($mEf > 0) {
                    $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, 'EFECTIVO', $mEf, 'Abono de Reserva (Parte Efectivo)']);
                }
                if ($mTr > 0) {
                    $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, 'TRANSFERENCIA', $mTr, 'Abono de Reserva (Parte Transferencia)']);
                }
            } else {
                $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'INGRESO', ?, ?, 'Abono de Reserva')")->execute([$usuarioId, $sucursalId, $idFactura, $datos['metodo_pago'], $monto]);
            }

            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function registrarEquipajeBD($datos, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();
            $idPasaje = intval($datos['id_pasaje'] ?? 0);
            $costoExtra = floatval($datos['costo_extra'] ?? 0);

            // 1. BLOQUEO DE DUPLICADOS: Verificar si ya existe un sobrepeso ACTIVO para este pasaje
            $stmtCheckSp = $this->db->prepare("SELECT s.ID_SOBREPESO, s.ID_FACTURA, s.SP_TOTAL, s.SP_EXCESO_KG FROM SOBREPESO s WHERE s.ID_PASAJE = ? AND s.ESTADO = 'ACTIVO' LIMIT 1");
            $stmtCheckSp->execute([$idPasaje]);
            $spActivo = $stmtCheckSp->fetch(PDO::FETCH_ASSOC);

            if ($costoExtra > 0 && $spActivo) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'mensaje' => "Este pasaje ya tiene un sobrepeso cobrado (Factura #{$spActivo['ID_FACTURA']} por $" . number_format($spActivo['SP_TOTAL'], 2) . " / {$spActivo['SP_EXCESO_KG']} Kg). Para modificar o cobrar un valor diferente, primero anule el sobrepeso existente."
                ];
            }

            $piezas = [];
            $pesoMano = 10.00; // Maleta de mano asumida en 10kg
            $pesoBodega = 0.0;

            if (isset($datos['piezas']) && is_array($datos['piezas'])) {
                $num = 1;
                foreach ($datos['piezas'] as $pz) {
                    $peso = floatval($pz['peso'] ?? 0);
                    if ($peso <= 0) continue;
                    $tipo = trim($pz['tipo'] ?? 'Maleta') ?: 'Maleta';
                    $desc = trim($pz['descripcion'] ?? '');
                    $tagCode = "EQP-" . str_pad($idPasaje, 6, '0', STR_PAD_LEFT) . "-" . $num;
                    $piezas[] = [
                        'numero' => $num++,
                        'tag' => $tagCode,
                        'codigo' => $tagCode,
                        'tipo' => $tipo,
                        'ubicacion' => 'BODEGA',
                        'peso' => $peso,
                        'descripcion' => $desc
                    ];
                    $pesoBodega += $peso;
                }
            } elseif (isset($datos['eq_peso']) && is_array($datos['eq_peso'])) {
                $eqTipos = $datos['eq_tipo'] ?? [];
                $eqPesos = $datos['eq_peso'] ?? [];
                $eqDescripciones = $datos['eq_descripcion'] ?? [];

                $num = 1;
                for ($i = 0; $i < count($eqPesos); $i++) {
                    $peso = floatval($eqPesos[$i] ?? 0);
                    if ($peso <= 0) continue;
                    
                    $tipo = trim($eqTipos[$i] ?? 'Maleta');
                    if ($tipo === '') $tipo = 'Maleta';
                    
                    $desc = trim($eqDescripciones[$i] ?? '');
                    // Generar código de etiqueta único por pieza
                    $tagCode = "EQP-" . str_pad($idPasaje, 6, '0', STR_PAD_LEFT) . "-" . $num;
                    
                    $piezas[] = [
                        'numero' => $num++,
                        'tag' => $tagCode,
                        'codigo' => $tagCode,
                        'tipo' => $tipo,
                        'ubicacion' => 'BODEGA',
                        'peso' => $peso,
                        'descripcion' => $desc
                    ];
                    
                    $pesoBodega += $peso;
                }
            }

            // Si no se pasaron piezas individuales, usar los campos directos como fallback
            if (empty($piezas)) {
                $pesoBodega = floatval($datos['peso_bodega'] ?? 0);
                if ($pesoBodega > 0) {
                    $tagCode = "EQP-" . str_pad($idPasaje, 6, '0', STR_PAD_LEFT) . "-1";
                    $piezas[] = ['numero' => 1, 'tag' => $tagCode, 'codigo' => $tagCode, 'tipo' => 'Maleta', 'ubicacion' => 'BODEGA', 'peso' => $pesoBodega, 'descripcion' => 'Equipaje de bodega'];
                }
            }

            $detalleJson = !empty($piezas) ? json_encode($piezas, JSON_UNESCAPED_UNICODE) : null;
            $codigoSocio = isset($datos['codigo_socio']) ? trim($datos['codigo_socio']) : null;

            if ($codigoSocio !== null && $codigoSocio !== '') {
                $this->db->prepare("UPDATE PASAJE SET PESO_MANO = ?, PESO_BODEGA = ?, EQUIPAJE_DETALLE = ?, CODIGO_SOCIO = ? WHERE ID_PASAJE = ?")
                         ->execute([$pesoMano, $pesoBodega, $detalleJson, $codigoSocio, $idPasaje]);
            } else {
                $this->db->prepare("UPDATE PASAJE SET PESO_MANO = ?, PESO_BODEGA = ?, EQUIPAJE_DETALLE = ? WHERE ID_PASAJE = ?")
                         ->execute([$pesoMano, $pesoBodega, $detalleJson, $idPasaje]);
            }

            $idFactura = null;
            $idRutaTramo = !empty($datos['id_ruta_tramo']) ? intval($datos['id_ruta_tramo']) : null;
            $operadorTramo = 'General';
            $nombreTramo = null;
            $tarifaKg = floatval($datos['precio_sobrepeso_kg'] ?? 1.00);
            $costoSpKg = 0.00;

            if ($idRutaTramo > 0) {
                $stmtTr = $this->db->prepare("SELECT ORIGEN_TRAMO, DESTINO_TRAMO, OPERADOR, PRECIO_SOBREPESO_KG, COSTO_SOBREPESO_KG, UTILIDAD_SOBREPESO_KG FROM RUTA_TRAMO WHERE ID_RUTA_TRAMO = ?");
                $stmtTr->execute([$idRutaTramo]);
                $trInfo = $stmtTr->fetch(PDO::FETCH_ASSOC);
                if ($trInfo) {
                    $operadorTramo = !empty($trInfo['OPERADOR']) ? $trInfo['OPERADOR'] : 'General';
                    $nombreTramo = $trInfo['ORIGEN_TRAMO'] . ' ➔ ' . $trInfo['DESTINO_TRAMO'];
                    if (!empty($trInfo['PRECIO_SOBREPESO_KG'])) {
                        $tarifaKg = floatval($trInfo['PRECIO_SOBREPESO_KG']);
                    }
                    $costoSpKg = floatval($trInfo['COSTO_SOBREPESO_KG'] ?? 0.00);
                }
            }

            if ($costoExtra > 0) {
                $stmtRuta = $this->db->prepare("SELECT f.ID_RUTA, f.FECHA_VIAJE, f.ID_CLIENTE as FAC_CLIENTE, p.ID_CLIENTE as PAX_CLIENTE, r.MODALIDAD FROM FACTURA f INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA WHERE p.ID_PASAJE = ?");
                $stmtRuta->execute([$idPasaje]);
                $orig = $stmtRuta->fetch(PDO::FETCH_ASSOC);
                
                if (empty($nombreTramo) && !empty($orig['MODALIDAD']) && $orig['MODALIDAD'] !== 'ESTANDAR') {
                    $operadorTramo = $orig['MODALIDAD'];
                }

                $idCliente = !empty($datos['cliente_id']) ? $datos['cliente_id'] : (!empty($orig['PAX_CLIENTE']) ? $orig['PAX_CLIENTE'] : (!empty($orig['FAC_CLIENTE']) ? $orig['FAC_CLIENTE'] : '9999999999'));
                
                $cantPiezas = count($piezas);
                $pesoExcesoCalculado = ($tarifaKg > 0) ? round($costoExtra / $tarifaKg, 2) : 0.00;

                $detalleFac = $nombreTramo 
                    ? "Sobrepeso Tramo {$nombreTramo} ({$operadorTramo}) [{$cantPiezas} piezas, {$pesoExcesoCalculado} Kg exceso] | Pasaje #{$idPasaje}" 
                    : "Cobro por exceso de equipaje [{$cantPiezas} piezas, {$pesoExcesoCalculado} Kg exceso] | Pasaje #{$idPasaje}";

                $this->db->prepare("INSERT INTO FACTURA (ID_RUTA, ID_CLIENTE, ID_USUARIO, ID_SUCURSAL, FA_FECHA, FA_HORA, FECHA_VIAJE, FA_CANTIDAD, FA_METODO, FA_SUBTOTAL, FA_TOTAL, FA_TIPO, FA_DETALLE, ESTADO) VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), ?, 1, ?, ?, ?, 'SOBREPESO', ?, 'EMITIDA')")
                         ->execute([$orig['ID_RUTA'], $idCliente, $usuarioId, $sucursalId, $orig['FECHA_VIAJE'], $datos['metodo_pago'], $costoExtra, $costoExtra, $detalleFac]);
                $idFactura = $this->db->lastInsertId();

                // Cálculo financiero exacto de costo socio vs utilidad de agencia
                $esPropio = (strpos(mb_strtoupper($operadorTramo), 'SURAMERICA') !== false || strpos(mb_strtoupper($operadorTramo), 'PROPIO') !== false);
                if ($esPropio) {
                    $costoSocio = 0.00;
                    $utilAgencia = $costoExtra;
                } else {
                    if ($costoSpKg > 0) {
                        $costoSocio = round($pesoExcesoCalculado * $costoSpKg, 2);
                        $utilAgencia = max(0.00, $costoExtra - $costoSocio);
                    } else {
                        // Margen de retención de agencia por defecto (20% agencia, 80% socio)
                        $utilAgencia = round($costoExtra * 0.20, 2);
                        $costoSocio = max(0.00, $costoExtra - $utilAgencia);
                    }
                }

                // Registrar en la tabla SOBREPESO con piezas desglosadas, tags, estado ACTIVO y finanzas
                $this->db->prepare("INSERT INTO SOBREPESO 
                    (ID_FACTURA, ID_PASAJE, ID_RUTA_TRAMO, SP_OPERADOR, SP_TRAMO, SP_TARIFA_KG, SP_EXCESO_KG, SP_TOTAL, SP_TIPO, SP_PESO, ESTADO, PIEZAS_DETALLE, ID_USUARIO, COSTO_SOCIO, UTILIDAD_AGENCIA) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'BODEGA', ?, 'ACTIVO', ?, ?, ?, ?)")
                         ->execute([$idFactura, $idPasaje, $idRutaTramo, $operadorTramo, $nombreTramo, $tarifaKg, $pesoExcesoCalculado, $costoExtra, $pesoBodega, $detalleJson, $usuarioId, $costoSocio, $utilAgencia]);

                // Asociar factura y tramo a PASAJE
                $this->db->prepare("UPDATE PASAJE SET ID_FACTURA_SOBREPESO = ?, ID_RUTA_TRAMO_SOBREPESO = ? WHERE ID_PASAJE = ?")
                         ->execute([$idFactura, $idRutaTramo, $idPasaje]);

                $conceptoCaja = $nombreTramo 
                    ? "Cobro Sobrepeso Tramo {$nombreTramo} ({$operadorTramo})" 
                    : "Cobro Sobrepeso Equipaje";

                if ($datos['metodo_pago'] === 'MIXTO') {
                    $mEf = floatval($datos['monto_efectivo'] ?? 0);
                    $mTr = floatval($datos['monto_transferencia'] ?? 0);
                    if ($mEf <= 0 && $mTr <= 0) $mEf = $costoExtra;
                    $stmtCaja = $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'INGRESO', ?, ?, ?)");
                    if ($mEf > 0) {
                        $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, 'EFECTIVO', $mEf, $conceptoCaja . ' (Parte Efectivo)']);
                    }
                    if ($mTr > 0) {
                        $stmtCaja->execute([$usuarioId, $sucursalId, $idFactura, 'TRANSFERENCIA', $mTr, $conceptoCaja . ' (Parte Transferencia)']);
                    }
                } else {
                    $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'INGRESO', ?, ?, ?)")
                             ->execute([$usuarioId, $sucursalId, $idFactura, $datos['metodo_pago'], $costoExtra, $conceptoCaja]);
                }
            } else {
                $this->db->prepare("UPDATE PASAJE SET ID_RUTA_TRAMO_SOBREPESO = ? WHERE ID_PASAJE = ?")
                         ->execute([$idRutaTramo, $idPasaje]);
            }
            $this->db->commit(); 
            return ['success' => true, 'id_factura' => $idFactura];
        } catch (Exception $e) { 
            $this->db->rollBack(); 
            return ['success' => false, 'mensaje' => $e->getMessage()]; 
        }
    }

    public function anularSobrepesoBD($idPasaje, $idSobrepeso, $motivo, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();
            $idPasaje = intval($idPasaje);
            $idSobrepeso = intval($idSobrepeso);
            
            // Buscar el sobrepeso activo
            if ($idSobrepeso > 0) {
                $stmt = $this->db->prepare("SELECT s.*, f.ID_FACTURA, f.FA_TOTAL, f.FA_METODO, f.ID_SUCURSAL FROM SOBREPESO s INNER JOIN FACTURA f ON s.ID_FACTURA = f.ID_FACTURA WHERE s.ID_SOBREPESO = ? AND s.ESTADO = 'ACTIVO'");
                $stmt->execute([$idSobrepeso]);
            } else {
                $stmt = $this->db->prepare("SELECT s.*, f.ID_FACTURA, f.FA_TOTAL, f.FA_METODO, f.ID_SUCURSAL FROM SOBREPESO s INNER JOIN FACTURA f ON s.ID_FACTURA = f.ID_FACTURA WHERE s.ID_PASAJE = ? AND s.ESTADO = 'ACTIVO' ORDER BY s.ID_SOBREPESO DESC LIMIT 1");
                $stmt->execute([$idPasaje]);
            }
            $sp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sp) {
                $this->db->rollBack();
                return "No se encontró un sobrepeso activo para anular en este pasaje.";
            }

            $idFac = intval($sp['ID_FACTURA']);
            $idSp = intval($sp['ID_SOBREPESO']);
            $monto = floatval($sp['FA_TOTAL'] ?? $sp['SP_TOTAL'] ?? 0);
            $motivoTxt = trim($motivo) ?: 'Anulación de sobrepeso en taquilla';

            // 1. Marcar SOBREPESO como ANULADO
            $this->db->prepare("UPDATE SOBREPESO SET ESTADO = 'ANULADO', MOTIVO_ANULACION = ?, ID_USUARIO_ANULA = ?, FECHA_ANULACION = NOW() WHERE ID_SOBREPESO = ?")
                     ->execute([$motivoTxt, $usuarioId, $idSp]);

            // 2. Marcar FACTURA como ANULADA
            $this->db->prepare("UPDATE FACTURA SET ESTADO = 'ANULADA', FA_DETALLE = CONCAT(COALESCE(FA_DETALLE, ''), ' [ANULADA: ', ?, ']') WHERE ID_FACTURA = ?")
                     ->execute([$motivoTxt, $idFac]);

            // 3. Generar movimiento de EGRESO en Caja para reversar el dinero de taquilla
            if ($monto > 0) {
                $sucursalAfectada = !empty($sp['ID_SUCURSAL']) ? $sp['ID_SUCURSAL'] : $sucursalId;
                $conceptoEgreso = "Devolución/Anulación Sobrepeso Factura #{$idFac} | Pasaje #{$sp['ID_PASAJE']} - Motivo: {$motivoTxt}";
                
                $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'EGRESO', ?, ?, ?)")
                         ->execute([$usuarioId, $sucursalAfectada, $idFac, $sp['FA_METODO'] ?? 'EFECTIVO', $monto, $conceptoEgreso]);
            }

            // 4. Desvincular de PASAJE
            $this->db->prepare("UPDATE PASAJE SET ID_FACTURA_SOBREPESO = NULL WHERE ID_PASAJE = ? AND ID_FACTURA_SOBREPESO = ?")
                     ->execute([$sp['ID_PASAJE'], $idFac]);

            // 5. Si había solicitud de anulación pendiente, marcarla como APROBADA
            $this->db->prepare("UPDATE SOLICITUD_ANULACION SET ESTADO = 'APROBADA', ID_USUARIO_RESPONDE = ?, FECHA_RESPUESTA = NOW(), MOTIVO_RESPUESTA = ? WHERE (ID_FACTURA = ? OR (TIPO_VENTA = 'SOBREPESO' AND ID_REFERENCIA = ?)) AND ESTADO = 'PENDIENTE'")
                     ->execute([$usuarioId, $motivoTxt, $idFac, $idSp]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return "Error al anular sobrepeso: " . $e->getMessage();
        }
    }

        public function asignarViajeBD($datos) {
        try {
            $this->db->beginTransaction();
            $this->db->prepare("UPDATE FACTURA f INNER JOIN PASAJE p ON f.ID_FACTURA = p.ID_FACTURA SET f.FECHA_VIAJE = ? WHERE p.ID_PASAJE = ?")->execute([$datos['fecha_viaje'], $datos['id_pasaje']]);
            $this->db->prepare("UPDATE PASAJE SET ID_BUS = ?, PAX_ASIENTO = ? WHERE ID_PASAJE = ?")->execute([$datos['bus_id'], $datos['asiento'], $datos['id_pasaje']]);
            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function reasignarAsientoBD($idPasaje, $nuevoAsiento) {
        try {
            $this->db->prepare("UPDATE PASAJE SET PAX_ASIENTO = ? WHERE ID_PASAJE = ?")->execute([$nuevoAsiento, $idPasaje]);
            return true;
        } catch (Exception $e) { return $e->getMessage(); }
    }

    public function devolverPasajeBD($datos, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();
            $motivo = "Devolución: " . $datos['motivo'];
            $this->db->prepare("UPDATE PASAJE SET ESTADO = 'DEVUELTO', OBSERVACION = ?, PAX_ASIENTO = NULL WHERE ID_PASAJE = ?")->execute([$motivo, $datos['id_pasaje']]);

            if (floatval($datos['monto_devolver']) > 0) {
                $stmtFac = $this->db->prepare("SELECT f.ID_FACTURA, f.ID_SUCURSAL FROM PASAJE p INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA WHERE p.ID_PASAJE = ?");
                $stmtFac->execute([$datos['id_pasaje']]);
                $facInfo = $stmtFac->fetch(PDO::FETCH_ASSOC);
                $idFactura = $facInfo['ID_FACTURA'] ?? null;
                $sucursalAfectada = !empty($facInfo['ID_SUCURSAL']) ? $facInfo['ID_SUCURSAL'] : $sucursalId;

                $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (?, ?, ?, 'EGRESO', 'EFECTIVO', ?, ?)")->execute([$usuarioId, $sucursalAfectada, $idFactura, floatval($datos['monto_devolver']), $motivo]);
            }

            // Si existía una solicitud de anulación pendiente para este pasaje, marcarla como APROBADA
            $this->db->prepare("UPDATE SOLICITUD_ANULACION SET ESTADO = 'APROBADA', ID_USUARIO_RESPONDE = ?, FECHA_RESPUESTA = NOW() WHERE TIPO_VENTA = 'PASAJE' AND ID_REFERENCIA = ? AND ESTADO = 'PENDIENTE'")
                     ->execute([$usuarioId, $datos['id_pasaje']]);

            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function actualizarFinancieroBD($datos, $usuarioId, $sucursalId) {
        try {
            $this->db->beginTransaction();
            $idPasaje = intval($datos['id_pasaje']);
            $precioTotal = floatval($datos['precio_total']);
            $saldo = max(0, floatval($datos['saldo']));
            $estado = !empty($datos['estado']) ? $datos['estado'] : (($saldo <= 0) ? 'PAGADO' : 'RESERVA');

            // 1. Actualizar el registro de PASAJE
            $stmt = $this->db->prepare("UPDATE PASAJE SET PRECIO_TOTAL = ?, SALDO = ?, ESTADO = ? WHERE ID_PASAJE = ?");
            $stmt->execute([$precioTotal, $saldo, $estado, $idPasaje]);

            // 2. Obtener factura vinculada
            $stmtFac = $this->db->prepare("SELECT ID_FACTURA FROM PASAJE WHERE ID_PASAJE = ?");
            $stmtFac->execute([$idPasaje]);
            $idFactura = $stmtFac->fetchColumn();

            // 3. Sincronizar Factura y Movimiento de Caja si se solicita
            if ($idFactura && !empty($datos['sincronizar_factura'])) {
                // Sumar todos los pasajes de esta factura para mantener coherencia si eran múltiples
                $stmtSum = $this->db->prepare("SELECT SUM(PRECIO_TOTAL) as TOTAL_PASAJE, SUM(SALDO) as TOTAL_SALDO FROM PASAJE WHERE ID_FACTURA = ?");
                $stmtSum->execute([$idFactura]);
                $sum = $stmtSum->fetch(PDO::FETCH_ASSOC);

                $nuevoTotalFactura = floatval($sum['TOTAL_PASAJE'] ?? $precioTotal);
                $nuevoSaldoFactura = floatval($sum['TOTAL_SALDO'] ?? $saldo);
                $montoPagadoFactura = max(0, $nuevoTotalFactura - $nuevoSaldoFactura);

                $this->db->prepare("UPDATE FACTURA SET FA_SUBTOTAL = ?, FA_TOTAL = ? WHERE ID_FACTURA = ?")
                         ->execute([$nuevoTotalFactura, $nuevoTotalFactura, $idFactura]);

                if (!empty($datos['sincronizar_caja'])) {
                    // Actualizar el movimiento de caja de ingreso asociado a esta factura
                    $stmtMov = $this->db->prepare("SELECT ID_MOVIMIENTO FROM MOVIMIENTO_CAJA WHERE ID_FACTURA = ? AND TIPO_MOVIMIENTO = 'INGRESO' ORDER BY ID_MOVIMIENTO ASC LIMIT 1");
                    $stmtMov->execute([$idFactura]);
                    $idMov = $stmtMov->fetchColumn();

                    if ($idMov) {
                        $this->db->prepare("UPDATE MOVIMIENTO_CAJA SET MONTO = ? WHERE ID_MOVIMIENTO = ?")
                                 ->execute([$montoPagadoFactura, $idMov]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    // ==============================================================
    // --- GESTIÓN DE SOLICITUDES DE ANULACIÓN (WORKFLOW ADMIN) ---
    // ==============================================================

    public function solicitarAnulacionBD($tipoVenta, $idReferencia, $idFactura, $usuarioId, $sucursalId, $montoDevolver, $motivo) {
        try {
            // Verificar si ya existe una solicitud PENDIENTE para este registro
            $stmtCheck = $this->db->prepare("SELECT ID_SOLICITUD FROM SOLICITUD_ANULACION WHERE TIPO_VENTA = ? AND ID_REFERENCIA = ? AND ESTADO = 'PENDIENTE'");
            $stmtCheck->execute([$tipoVenta, $idReferencia]);
            if ($stmtCheck->fetchColumn()) {
                return "Ya existe una solicitud de anulación pendiente de revisión para esta venta.";
            }

            // Identificar la factura y la sucursal donde ocurrió la venta original
            $facId = !empty($idFactura) ? $idFactura : null;
            if (empty($facId)) {
                if ($tipoVenta === 'PASAJE') {
                    $facId = $this->db->query("SELECT ID_FACTURA FROM PASAJE WHERE ID_PASAJE = " . intval($idReferencia))->fetchColumn();
                } elseif ($tipoVenta === 'ENCOMIENDA' || $tipoVenta === 'VALOR') {
                    $facId = $idReferencia;
                }
            }

            $sucursalVenta = null;
            if (!empty($facId)) {
                $stmtFac = $this->db->prepare("SELECT ID_SUCURSAL FROM FACTURA WHERE ID_FACTURA = ?");
                $stmtFac->execute([$facId]);
                $sucursalVenta = $stmtFac->fetchColumn();

                if (empty($sucursalVenta)) {
                    $stmtMov = $this->db->prepare("SELECT ID_SUCURSAL FROM MOVIMIENTO_CAJA WHERE ID_FACTURA = ? AND TIPO_MOVIMIENTO = 'INGRESO' ORDER BY ID_MOVIMIENTO ASC LIMIT 1");
                    $stmtMov->execute([$facId]);
                    $sucursalVenta = $stmtMov->fetchColumn();
                }
            }

            $sucursalFinal = !empty($sucursalVenta) ? $sucursalVenta : $sucursalId;

            $stmt = $this->db->prepare("INSERT INTO SOLICITUD_ANULACION (TIPO_VENTA, ID_REFERENCIA, ID_FACTURA, ID_USUARIO_SOLICITA, ID_SUCURSAL, MONTO_DEVOLVER, MOTIVO, ESTADO, FECHA_SOLICITUD) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDIENTE', NOW())");
            $stmt->execute([
                $tipoVenta,
                $idReferencia,
                $facId,
                $usuarioId,
                $sucursalFinal,
                floatval($montoDevolver),
                trim($motivo)
            ]);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function contarSolicitudesPendientes($tipoVenta = null) {
        try {
            if ($tipoVenta) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM SOLICITUD_ANULACION WHERE ESTADO = 'PENDIENTE' AND TIPO_VENTA = ?");
                $stmt->execute([$tipoVenta]);
            } else {
                $stmt = $this->db->query("SELECT COUNT(*) FROM SOLICITUD_ANULACION WHERE ESTADO = 'PENDIENTE'");
            }
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function obtenerSolicitudesAnulacion($filtros = []) {
        try {
            $sql = "SELECT s.*, 
                           us.NOMBRE as USUARIO_SOLICITA_NOMBRE, 
                           suc.CIUDAD as SUCURSAL_NOMBRE, 
                           suc_fac.CIUDAD as SUCURSAL_VENTA_NOMBRE,
                           COALESCE(suc_fac.CIUDAD, suc.CIUDAD, 'Central') as SUCURSAL_SOLICITA,
                           ur.NOMBRE as USUARIO_RESPONDE_NOMBRE,
                           p.PAX_ASIENTO, p.PRECIO_TOTAL as PASAJE_PRECIO, p.SALDO as PASAJE_SALDO, p.ESTADO as PASAJE_ESTADO,
                           COALESCE(c.CLI_NOMBRE, c_fac.CLI_NOMBRE) as CLI_NOMBRE, 
                           COALESCE(c.CLI_APELLIDO, c_fac.CLI_APELLIDO) as CLI_APELLIDO, 
                           COALESCE(c.ID_CLIENTE, c_fac.ID_CLIENTE) as ID_CLIENTE,
                           r.RUT_ORIGEN, r.RUT_DESTINO,
                           f.FECHA_VIAJE, f.FA_FECHA, f.FA_TOTAL,
                           b.BUS_PLACA
                    FROM SOLICITUD_ANULACION s
                    LEFT JOIN USUARIO us ON s.ID_USUARIO_SOLICITA = us.ID_USUARIO
                    LEFT JOIN SUCURSAL suc ON s.ID_SUCURSAL = suc.ID_SUCURSAL
                    LEFT JOIN USUARIO ur ON s.ID_USUARIO_RESPONDE = ur.ID_USUARIO
                    LEFT JOIN PASAJE p ON (s.TIPO_VENTA = 'PASAJE' AND s.ID_REFERENCIA = p.ID_PASAJE)
                    LEFT JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                    LEFT JOIN FACTURA f ON COALESCE(s.ID_FACTURA, p.ID_FACTURA, CASE WHEN s.TIPO_VENTA IN ('ENCOMIENDA', 'VALOR') THEN s.ID_REFERENCIA END) = f.ID_FACTURA
                    LEFT JOIN SUCURSAL suc_fac ON f.ID_SUCURSAL = suc_fac.ID_SUCURSAL
                    LEFT JOIN CLIENTE c_fac ON f.ID_CLIENTE = c_fac.ID_CLIENTE
                    LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                    LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS
                    WHERE 1=1 ";
            
            $params = [];
            if (!empty($filtros['estado_solicitud'])) {
                $sql .= " AND s.ESTADO = :estado ";
                $params[':estado'] = $filtros['estado_solicitud'];
            }
            if (!empty($filtros['tipo_venta'])) {
                $sql .= " AND s.TIPO_VENTA = :tipo_venta ";
                $params[':tipo_venta'] = $filtros['tipo_venta'];
            }
            if (!empty($filtros['buscar_solicitud'])) {
                $b = "%" . trim($filtros['buscar_solicitud']) . "%";
                $sql .= " AND (c.CLI_NOMBRE LIKE :b1 OR c.CLI_APELLIDO LIKE :b2 OR c.ID_CLIENTE LIKE :b3 OR s.ID_SOLICITUD = :b4 OR s.ID_REFERENCIA = :b5 OR s.ID_FACTURA = :b6) ";
                $params[':b1'] = $b;
                $params[':b2'] = $b;
                $params[':b3'] = $b;
                $params[':b4'] = is_numeric($filtros['buscar_solicitud']) ? (int)$filtros['buscar_solicitud'] : 0;
                $params[':b5'] = is_numeric($filtros['buscar_solicitud']) ? (int)$filtros['buscar_solicitud'] : 0;
                $params[':b6'] = is_numeric($filtros['buscar_solicitud']) ? (int)$filtros['buscar_solicitud'] : 0;
            }

            $sql .= " ORDER BY CASE WHEN s.ESTADO = 'PENDIENTE' THEN 1 ELSE 2 END, s.FECHA_SOLICITUD DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function aprobarSolicitudAnulacionBD($idSolicitud, $usuarioAdminId, $sucursalAdminId = null) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT * FROM SOLICITUD_ANULACION WHERE ID_SOLICITUD = ? AND ESTADO = 'PENDIENTE'");
            $stmt->execute([$idSolicitud]);
            $sol = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sol) {
                $this->db->rollBack();
                return "La solicitud no existe o ya fue procesada previamente.";
            }

            // Identificar la factura asociada a la venta
            $idFactura = $sol['ID_FACTURA'];
            if (empty($idFactura)) {
                if ($sol['TIPO_VENTA'] === 'PASAJE') {
                    $idFactura = $this->db->query("SELECT ID_FACTURA FROM PASAJE WHERE ID_PASAJE = " . intval($sol['ID_REFERENCIA']))->fetchColumn();
                } elseif ($sol['TIPO_VENTA'] === 'ENCOMIENDA' || $sol['TIPO_VENTA'] === 'VALOR') {
                    $idFactura = $sol['ID_REFERENCIA'];
                }
            }

            // Identificar la sucursal donde ocurrió la venta originalmente para asignar el egreso a esa caja
            $idSucursalVenta = null;
            if (!empty($idFactura)) {
                $stmtFac = $this->db->prepare("SELECT ID_SUCURSAL FROM FACTURA WHERE ID_FACTURA = ?");
                $stmtFac->execute([$idFactura]);
                $idSucursalVenta = $stmtFac->fetchColumn();

                if (empty($idSucursalVenta)) {
                    $stmtMov = $this->db->prepare("SELECT ID_SUCURSAL FROM MOVIMIENTO_CAJA WHERE ID_FACTURA = ? AND TIPO_MOVIMIENTO = 'INGRESO' ORDER BY ID_MOVIMIENTO ASC LIMIT 1");
                    $stmtMov->execute([$idFactura]);
                    $idSucursalVenta = $stmtMov->fetchColumn();
                }
            }

            // La caja afectada por el reembolso DEBE ser la sucursal de la venta
            // Prioridad: 1. Sucursal de la Factura/Ingreso | 2. Sucursal de la Solicitud | 3. Sucursal Admin
            $idSucursal = !empty($idSucursalVenta) ? $idSucursalVenta : (!empty($sol['ID_SUCURSAL']) ? $sol['ID_SUCURSAL'] : $sucursalAdminId);

            if ($sol['TIPO_VENTA'] === 'PASAJE') {
                $idPasaje = $sol['ID_REFERENCIA'];
                $motivo = "Devolución aprobada por Admin: " . $sol['MOTIVO'];

                $this->db->prepare("UPDATE PASAJE SET ESTADO = 'DEVUELTO', OBSERVACION = ?, PAX_ASIENTO = NULL WHERE ID_PASAJE = ?")
                         ->execute([$motivo, $idPasaje]);

                $montoDevolver = floatval($sol['MONTO_DEVOLVER']);
                if ($montoDevolver > 0) {
                    $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO, ESTADO_APROBACION) VALUES (?, ?, ?, 'EGRESO', 'EFECTIVO', ?, ?, 'APROBADO')")
                             ->execute([$usuarioAdminId, $idSucursal, $idFactura, $montoDevolver, $motivo]);
                }
            } elseif ($sol['TIPO_VENTA'] === 'ENCOMIENDA') {
                $this->db->prepare("UPDATE ENCOMIENDA SET ENC_ESTADO = 'Anulado' WHERE ID_FACTURA = ?")->execute([$idFactura]);
                $montoDevolver = floatval($sol['MONTO_DEVOLVER']);
                if ($montoDevolver > 0) {
                    $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO, ESTADO_APROBACION) VALUES (?, ?, ?, 'EGRESO', 'EFECTIVO', ?, ?, 'APROBADO')")
                             ->execute([$usuarioAdminId, $idSucursal, $idFactura, $montoDevolver, 'Anulación de Encomienda aprobada por Admin (Reembolso)']);
                }
            } elseif ($sol['TIPO_VENTA'] === 'VALOR') {
                $this->db->prepare("UPDATE VALOR SET VA_ESTADO = 'Anulado' WHERE ID_FACTURA = ?")->execute([$idFactura]);
                $montoDevolver = floatval($sol['MONTO_DEVOLVER']);
                if ($montoDevolver > 0) {
                    $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, ID_FACTURA, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO, ESTADO_APROBACION) VALUES (?, ?, ?, 'EGRESO', 'EFECTIVO', ?, ?, 'APROBADO')")
                             ->execute([$usuarioAdminId, $idSucursal, $idFactura, $montoDevolver, 'Anulación de Remesa/Valores aprobada por Admin (Reembolso)']);
                }
            }

            // Actualizar la solicitud a APROBADA
            $this->db->prepare("UPDATE SOLICITUD_ANULACION SET ESTADO = 'APROBADA', ID_USUARIO_RESPONDE = ?, FECHA_RESPUESTA = NOW() WHERE ID_SOLICITUD = ?")
                     ->execute([$usuarioAdminId, $idSolicitud]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    public function denegarSolicitudAnulacionBD($idSolicitud, $usuarioAdminId, $motivoRechazo) {
        try {
            $stmt = $this->db->prepare("UPDATE SOLICITUD_ANULACION SET ESTADO = 'RECHAZADA', ID_USUARIO_RESPONDE = ?, FECHA_RESPUESTA = NOW(), MOTIVO_RESPUESTA = ? WHERE ID_SOLICITUD = ? AND ESTADO = 'PENDIENTE'");
            $stmt->execute([$usuarioAdminId, $motivoRechazo, $idSolicitud]);
            if ($stmt->rowCount() === 0) {
                return "La solicitud no existe o ya fue procesada.";
            }
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function contarEquipajesCentral($filtros = []) {
        $query = "SELECT COUNT(DISTINCT p.ID_PASAJE) 
                  FROM PASAJE p 
                  INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA 
                  LEFT JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE 
                  LEFT JOIN SOBREPESO s ON s.ID_SOBREPESO = (SELECT s_sub.ID_SOBREPESO FROM SOBREPESO s_sub WHERE s_sub.ID_PASAJE = p.ID_PASAJE ORDER BY (CASE WHEN s_sub.ESTADO = 'ACTIVO' THEN 1 ELSE 2 END), s_sub.ID_SOBREPESO DESC LIMIT 1)
                  WHERE (p.PESO_BODEGA > 0 OR p.EQUIPAJE_DETALLE IS NOT NULL OR s.ID_SOBREPESO IS NOT NULL) ";
        $params = [];
        $this->aplicarFiltrosEquipaje($query, $params, $filtros);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return intval($stmt->fetchColumn() ?? 0);
    }

    public function obtenerEquipajesCentral($filtros = [], $offset = 0, $limit = 50) {
        $query = "SELECT p.*, f.FECHA_VIAJE, f.FA_FECHA, f.ID_RUTA, r.RUT_ORIGEN, r.RUT_DESTINO,
                         c.CLI_NOMBRE, c.CLI_APELLIDO, COALESCE(b.BUS_PLACA, 'SIN ASIGNAR') as BUS_PLACA,
                         s.ID_SOBREPESO, s.ID_FACTURA as SP_ID_FACTURA, s.SP_TOTAL, s.SP_EXCESO_KG,
                         s.SP_TARIFA_KG, s.SP_OPERADOR, s.SP_TRAMO, s.ESTADO as SP_ESTADO,
                         s.PIEZAS_DETALLE as SP_PIEZAS, s.FECHA_CREACION as SP_FECHA, s.MOTIVO_ANULACION as SP_MOTIVO_ANULACION
                  FROM PASAJE p
                  INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA
                  LEFT JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  LEFT JOIN BUS b ON p.ID_BUS = b.ID_BUS
                  LEFT JOIN SOBREPESO s ON s.ID_SOBREPESO = (SELECT s_sub.ID_SOBREPESO FROM SOBREPESO s_sub WHERE s_sub.ID_PASAJE = p.ID_PASAJE ORDER BY (CASE WHEN s_sub.ESTADO = 'ACTIVO' THEN 1 ELSE 2 END), s_sub.ID_SOBREPESO DESC LIMIT 1)
                  WHERE (p.PESO_BODEGA > 0 OR p.EQUIPAJE_DETALLE IS NOT NULL OR s.ID_SOBREPESO IS NOT NULL) ";
        $params = [];
        $this->aplicarFiltrosEquipaje($query, $params, $filtros);
        $query .= " ORDER BY COALESCE(f.FECHA_VIAJE, f.FA_FECHA) DESC, p.ID_PASAJE DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTotalesKpisEquipajesCentral($fecha = null) {
        $sql = "SELECT 
                    COUNT(DISTINCT p.ID_PASAJE) as TOTAL_PASAJEROS_CON_EQUIPAJE,
                    COALESCE(SUM(p.PESO_BODEGA), 0) as TOTAL_KG_BODEGA,
                    COALESCE(SUM(CASE WHEN s.ESTADO = 'ACTIVO' THEN s.SP_TOTAL ELSE 0 END), 0) as TOTAL_SOBREPESO_USD,
                    COALESCE(SUM(CASE WHEN s.ESTADO = 'ACTIVO' THEN s.SP_EXCESO_KG ELSE 0 END), 0) as TOTAL_EXCESO_KG,
                    COUNT(DISTINCT CASE WHEN s.ESTADO = 'ANULADO' THEN s.ID_SOBREPESO END) as TOTAL_SOBREPESOS_ANULADOS
                FROM PASAJE p
                INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA
                LEFT JOIN SOBREPESO s ON p.ID_PASAJE = s.ID_PASAJE
                WHERE (p.PESO_BODEGA > 0 OR p.EQUIPAJE_DETALLE IS NOT NULL OR s.ID_SOBREPESO IS NOT NULL)";
        
        $params = [];
        if (!empty($fecha)) {
            $sql .= " AND (f.FECHA_VIAJE = :fecha OR (f.FECHA_VIAJE IS NULL AND f.FA_FECHA = :fecha))";
            $params[':fecha'] = $fecha;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function aplicarFiltrosEquipaje(&$query, &$params, $filtros) {
        if (!empty($filtros['buscar'])) {
            $b = trim($filtros['buscar']);
            $query .= " AND (p.ID_PASAJE = :b_id OR f.ID_FACTURA = :b_id OR c.ID_CLIENTE LIKE :b_str OR c.CLI_NOMBRE LIKE :b_str OR c.CLI_APELLIDO LIKE :b_str OR CONCAT(c.CLI_NOMBRE, ' ', c.CLI_APELLIDO) LIKE :b_str OR p.EQUIPAJE_DETALLE LIKE :b_str OR s.PIEZAS_DETALLE LIKE :b_str) ";
            $params[':b_id'] = is_numeric($b) ? intval($b) : 0;
            $params[':b_str'] = "%$b%";
        }
        if (!empty($filtros['fecha'])) {
            $query .= " AND (f.FECHA_VIAJE = :f_viaje OR (f.FECHA_VIAJE IS NULL AND f.FA_FECHA = :f_viaje)) ";
            $params[':f_viaje'] = $filtros['fecha'];
        }
        if (!empty($filtros['bus_id'])) {
            $query .= " AND p.ID_BUS = :f_bus ";
            $params[':f_bus'] = intval($filtros['bus_id']);
        }
        if (!empty($filtros['ruta_id'])) {
            $query .= " AND f.ID_RUTA = :f_ruta ";
            $params[':f_ruta'] = intval($filtros['ruta_id']);
        }
        if (!empty($filtros['estado_sp'])) {
            if ($filtros['estado_sp'] === 'con_sobrepeso') {
                $query .= " AND s.ID_SOBREPESO IS NOT NULL AND s.ESTADO = 'ACTIVO' ";
            } elseif ($filtros['estado_sp'] === 'sin_sobrepeso') {
                $query .= " AND (s.ID_SOBREPESO IS NULL OR s.ESTADO != 'ACTIVO') ";
            } elseif ($filtros['estado_sp'] === 'anulado') {
                $query .= " AND EXISTS (SELECT 1 FROM SOBREPESO s2 WHERE s2.ID_PASAJE = p.ID_PASAJE AND s2.ESTADO = 'ANULADO') ";
            }
        }
    }
}


