<?php
namespace Modules\Caja;
use Platform\Storage\Database;
use PDO;
use Exception;

class CajaRepository {
    private $db;

    public function __construct() { 
        $this->db = (new Database())->getConnection(); 
    }

    public function obtenerSucursales() {
        return $this->db->query("SELECT * FROM SUCURSAL ORDER BY ID_SUCURSAL")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCajeros() {
        return $this->db->query("SELECT ID_USUARIO, NOMBRE FROM USUARIO WHERE ESTADO = 1 ORDER BY NOMBRE")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerMovimientosDelDia($idSucursal, $fecha) {
        $condicion = ($idSucursal === 'ALL') ? "1=1" : "m.ID_SUCURSAL = :sucursal";
        
        $query = "SELECT m.*, COALESCE(u.NOMBRE, 'Sistema') as CAJERO, f.FA_TIPO as ORIGEN_FACTURA, f.FA_TOTAL,
                         COALESCE(NULLIF(f.FA_CANTIDAD, 0), 1) as FA_CANTIDAD,
                         s.CIUDAD as SUCURSAL_NOMBRE, COALESCE(s.COMISION_EMPLEADO_PCT, 0) as COMISION_EMPLEADO_PCT,
                         COALESCE(r.TIPO_UTILIDAD, 'PORCENTAJE') as TIPO_UTILIDAD, 
                         COALESCE(r.UTILIDAD_PASAJE, 100) as UTILIDAD_PASAJE, 
                         COALESCE(r.UTILIDAD_ENCOMIENDA, 100) as UTILIDAD_ENCOMIENDA, 
                         COALESCE(r.UTILIDAD_VALORES, 100) as UTILIDAD_VALORES
                  FROM MOVIMIENTO_CAJA m
                  LEFT JOIN USUARIO u ON m.ID_USUARIO = u.ID_USUARIO
                  LEFT JOIN FACTURA f ON m.ID_FACTURA = f.ID_FACTURA
                  LEFT JOIN SUCURSAL s ON m.ID_SUCURSAL = s.ID_SUCURSAL
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  WHERE $condicion AND DATE(m.FECHA_HORA) = :fecha
                  AND (m.ESTADO_APROBACION IS NULL OR m.ESTADO_APROBACION != 'RECHAZADO')
                  ORDER BY m.FECHA_HORA ASC";
                  
        $stmt = $this->db->prepare($query);
        $params = [':fecha' => $fecha];
        if ($idSucursal !== 'ALL') {
            $params[':sucursal'] = $idSucursal;
        }
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verificarCierreDia($idSucursal, $fecha) {
        if ($idSucursal === 'ALL') return false; 
        $stmt = $this->db->prepare("SELECT * FROM CIERRE_CAJA WHERE ID_SUCURSAL = ? AND FECHA_CAJA = ? AND ESTADO = 'CERRADO' ORDER BY ID_CIERRE DESC LIMIT 1");
        $stmt->execute([$idSucursal, $fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function obtenerSesionCajaDia($idSucursal, $fecha) {
        if ($idSucursal === 'ALL') return null;
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u.NOMBRE as CAJERO_NOMBRE,
                   s.CIUDAD as SUCURSAL_NOMBRE
            FROM CIERRE_CAJA c
            LEFT JOIN USUARIO u ON c.ID_USUARIO = u.ID_USUARIO
            LEFT JOIN SUCURSAL s ON c.ID_SUCURSAL = s.ID_SUCURSAL
            WHERE c.ID_SUCURSAL = ? AND c.FECHA_CAJA = ?
            ORDER BY c.ID_CIERRE DESC LIMIT 1
        ");
        $stmt->execute([$idSucursal, $fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function obtenerUltimoCierreSucursal($idSucursal, $fechaLimite = null) {
        if ($idSucursal === 'ALL') return null;

        if ($fechaLimite) {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       u.NOMBRE as CAJERO_NOMBRE,
                       s.CIUDAD as SUCURSAL_NOMBRE
                FROM CIERRE_CAJA c
                LEFT JOIN USUARIO u ON c.ID_USUARIO = u.ID_USUARIO
                LEFT JOIN SUCURSAL s ON c.ID_SUCURSAL = s.ID_SUCURSAL
                WHERE c.ID_SUCURSAL = ? AND c.ESTADO = 'CERRADO' AND c.FECHA_CAJA < ?
                ORDER BY c.FECHA_CAJA DESC, c.ID_CIERRE DESC LIMIT 1
            ");
            $stmt->execute([$idSucursal, $fechaLimite]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return $res;
        }

        $stmt = $this->db->prepare("
            SELECT c.*, 
                   u.NOMBRE as CAJERO_NOMBRE,
                   s.CIUDAD as SUCURSAL_NOMBRE
            FROM CIERRE_CAJA c
            LEFT JOIN USUARIO u ON c.ID_USUARIO = u.ID_USUARIO
            LEFT JOIN SUCURSAL s ON c.ID_SUCURSAL = s.ID_SUCURSAL
            WHERE c.ID_SUCURSAL = ? AND c.ESTADO = 'CERRADO'
            ORDER BY c.FECHA_CAJA DESC, c.ID_CIERRE DESC LIMIT 1
        ");
        $stmt->execute([$idSucursal]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function abrirCaja($datos, $usuarioId, $sucursalId) {
        try {
            $fecha = $datos['fecha_apertura'] ?? date('Y-m-d');
            
            // Verificar si ya fue cerrada
            if ($this->verificarCierreDia($sucursalId, $fecha)) {
                return ['status' => 'error', 'mensaje' => 'La caja de esta sucursal ya fue cerrada oficialmente para la fecha ' . $fecha . '. No se puede reabrir.'];
            }

            // Verificar si ya está abierta
            $sesion = $this->obtenerSesionCajaDia($sucursalId, $fecha);
            if ($sesion && $sesion['ESTADO'] === 'ABIERTO') {
                return ['status' => 'error', 'mensaje' => 'La caja ya se encuentra abierta para esta fecha.'];
            }

            $valorInicial = max(0, floatval($datos['valor_inicial'] ?? 0));
            $observaciones = trim($datos['observaciones_apertura'] ?? '');
            $codigoCierre = date('dmyHis', strtotime($fecha)) . rand(100, 999);

            $stmt = $this->db->prepare("
                INSERT INTO CIERRE_CAJA (
                    ID_USUARIO, ID_SUCURSAL, FECHA_CAJA,
                    APERTURA_FECHA_HORA, VALOR_INICIAL,
                    TOTAL_INGRESOS, TOTAL_EGRESOS, SALDO_FINAL, UTILIDAD,
                    CAPITAL_REPOSICION, COMISION_EMPLEADOS, VALOR_A_DEPOSITAR,
                    CODIGO_CIERRE, ESTADO, DEPOSITO_ESTADO, OBSERVACIONES_APERTURA
                ) VALUES (
                    ?, ?, ?,
                    NOW(), ?,
                    0.00, 0.00, ?, 0.00,
                    0.00, 0.00, 0.00,
                    ?, 'ABIERTO', 'PENDIENTE', ?
                )
            ");
            $stmt->execute([
                $usuarioId,
                $sucursalId,
                $fecha,
                $valorInicial,
                $valorInicial,
                $codigoCierre,
                $observaciones
            ]);

            return ['status' => 'success_apertura'];
        } catch (Exception $e) {
            return ['status' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function registrarCierre($datos, $usuarioId, $sucursalId) {
        try {
            $fecha = $datos['fecha_cierre'];
            if ($this->verificarCierreDia($sucursalId, $fecha)) {
                return ['status' => 'error', 'mensaje' => "La caja de esta sucursal ya fue cerrada previamente."];
            }

            $saldoFinal = floatval($datos['saldo_final']);
            $comisiones = floatval($datos['comisiones'] ?? 0);
            $capitalReposicion = floatval($datos['capital_reposicion'] ?? 0);
            
            // Físico contado y Diferencia
            $efectivoFisico = (isset($datos['efectivo_fisico']) && $datos['efectivo_fisico'] !== '') 
                ? floatval($datos['efectivo_fisico']) 
                : $saldoFinal;
            $diferenciaArqueo = (isset($datos['diferencia_arqueo']) && $datos['diferencia_arqueo'] !== '') 
                ? floatval($datos['diferencia_arqueo']) 
                : ($efectivoFisico - $saldoFinal);
            $obsCierre = trim($datos['observaciones_cierre'] ?? '');

            // Valor exigible a depositar en el banco
            $valorADepositar = isset($datos['valor_a_depositar']) ? floatval($datos['valor_a_depositar']) : max(0, $saldoFinal - $comisiones);
            $deposito = 0.00;
            $vPorDepositar = $valorADepositar;

            // Revisar si ya existía una sesión ABIERTA previa
            $sesion = $this->obtenerSesionCajaDia($sucursalId, $fecha);
            if ($sesion && $sesion['ESTADO'] === 'ABIERTO') {
                $stmt = $this->db->prepare("
                    UPDATE CIERRE_CAJA SET
                        ID_USUARIO = ?,
                        FECHA_HORA = NOW(),
                        TOTAL_INGRESOS = ?,
                        TOTAL_EGRESOS = ?,
                        SALDO_FINAL = ?,
                        EFECTIVO_FISICO = ?,
                        DIFERENCIA_ARQUEO = ?,
                        UTILIDAD = ?,
                        CAPITAL_REPOSICION = ?,
                        COMISION_EMPLEADOS = ?,
                        VALOR_A_DEPOSITAR = ?,
                        DEPOSITO = ?,
                        V_POR_DEPOSITAR = ?,
                        ESTADO = 'CERRADO',
                        OBSERVACIONES_CIERRE = ?
                    WHERE ID_CIERRE = ?
                ");
                $stmt->execute([
                    $usuarioId,
                    floatval($datos['total_ingresos']),
                    floatval($datos['total_egresos']),
                    $saldoFinal,
                    $efectivoFisico,
                    $diferenciaArqueo,
                    floatval($datos['utilidad_final']),
                    $capitalReposicion,
                    $comisiones,
                    $valorADepositar,
                    $deposito,
                    $vPorDepositar,
                    $obsCierre,
                    $sesion['ID_CIERRE']
                ]);
            } else {
                $codigoCierre = date('dmyHis', strtotime($fecha)) . rand(100, 999);
                $stmt = $this->db->prepare("
                    INSERT INTO CIERRE_CAJA (
                        ID_USUARIO, ID_SUCURSAL, FECHA_CAJA, TOTAL_INGRESOS, TOTAL_EGRESOS, 
                        SALDO_FINAL, EFECTIVO_FISICO, DIFERENCIA_ARQUEO, UTILIDAD, 
                        CAPITAL_REPOSICION, COMISION_EMPLEADOS, VALOR_A_DEPOSITAR, 
                        CODIGO_CIERRE, APERTURA_FECHA_HORA, VALOR_INICIAL, 
                        DEPOSITO, V_POR_DEPOSITAR, ESTADO, DEPOSITO_ESTADO, OBSERVACIONES_CIERRE
                    ) VALUES (
                        ?, ?, ?, ?, ?, 
                        ?, ?, ?, ?, 
                        ?, ?, ?, 
                        ?, NOW(), 0.00, 
                        ?, ?, 'CERRADO', 'PENDIENTE', ?
                    )
                ");
                $stmt->execute([
                    $usuarioId,
                    $sucursalId,
                    $fecha,
                    floatval($datos['total_ingresos']),
                    floatval($datos['total_egresos']),
                    $saldoFinal,
                    $efectivoFisico,
                    $diferenciaArqueo,
                    floatval($datos['utilidad_final']),
                    $capitalReposicion,
                    $comisiones,
                    $valorADepositar,
                    $codigoCierre,
                    $deposito,
                    $vPorDepositar,
                    $obsCierre
                ]);
            }

            return ['status' => 'success_cierre'];
        } catch (Exception $e) {
            return ['status' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function obtenerDetalleCierrePorId($idCierre) {
        $stmt = $this->db->prepare("
            SELECT c.*,
                   u.NOMBRE as CAJERO_CIERRE,
                   s.CIUDAD as SUCURSAL_NOMBRE,
                   s.DIRECCION as SUCURSAL_DIRECCION,
                   s.TELEFONO as SUCURSAL_TELEFONO
            FROM CIERRE_CAJA c
            LEFT JOIN USUARIO u ON c.ID_USUARIO = u.ID_USUARIO
            LEFT JOIN SUCURSAL s ON c.ID_SUCURSAL = s.ID_SUCURSAL
            WHERE c.ID_CIERRE = ?
        ");
        $stmt->execute([$idCierre]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function contarEgresosPendientesAprobacion($idSucursal, $fecha) {
        if ($idSucursal === 'ALL') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM MOVIMIENTO_CAJA WHERE DATE(FECHA_HORA) = ? AND TIPO_MOVIMIENTO = 'EGRESO' AND ESTADO_APROBACION = 'POR_APROBAR'");
            $stmt->execute([$fecha]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM MOVIMIENTO_CAJA WHERE ID_SUCURSAL = ? AND DATE(FECHA_HORA) = ? AND TIPO_MOVIMIENTO = 'EGRESO' AND ESTADO_APROBACION = 'POR_APROBAR'");
            $stmt->execute([$idSucursal, $fecha]);
        }
        return intval($stmt->fetchColumn() ?? 0);
    }

    public function registrarMovimientoManual($datos, $usuarioId, $sucursalId) {
        try {
            $fechaAfectada = $datos['fecha_actual'] ?? date('Y-m-d');
            if ($this->verificarCierreDia($sucursalId, $fechaAfectada)) return "La caja para esta fecha ya está cerrada.";

            $query = "INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO) VALUES (:usuario, :sucursal, :tipo, :metodo, :monto, :concepto)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':usuario' => $usuarioId, 
                ':sucursal' => $sucursalId, 
                ':tipo' => $datos['tipo_movimiento'], 
                ':metodo' => $datos['metodo_pago'], 
                ':monto' => floatval($datos['monto']), 
                ':concepto' => $datos['concepto']
            ]);
            return ['status' => 'success'];
        } catch (Exception $e) { 
            return ['status' => 'error', 'mensaje' => $e->getMessage()]; 
        }
    }

    // --- FUNCIONES NUEVAS PARA APROBACIONES, CIERRES AVANZADOS Y DEPÓSITOS ---

    public function obtenerMovimientosAprobacion($filtros) {
        $sql = "SELECT m.*, s.CIUDAD as SUCURSAL_NOMBRE, u.NOMBRE as CAJERO,
                       c.DEPOSITO_ESTADO as CIERRE_DEPOSITO_ESTADO,
                       c.ESTADO as CIERRE_ESTADO
                FROM MOVIMIENTO_CAJA m
                LEFT JOIN SUCURSAL s ON m.ID_SUCURSAL = s.ID_SUCURSAL
                LEFT JOIN USUARIO u ON m.ID_USUARIO = u.ID_USUARIO
                LEFT JOIN CIERRE_CAJA c ON (m.ID_SUCURSAL = c.ID_SUCURSAL AND DATE(m.FECHA_HORA) = c.FECHA_CAJA)
                WHERE m.ESTADO_APROBACION IS NOT NULL 
                AND m.TIPO_MOVIMIENTO = 'EGRESO'";
        
        if (!empty($filtros['sucursal'])) { $sql .= " AND m.ID_SUCURSAL = " . intval($filtros['sucursal']); }
        if (!empty($filtros['cajero'])) { $sql .= " AND m.ID_USUARIO = " . intval($filtros['cajero']); }
        if (!empty($filtros['estado'])) { $sql .= " AND m.ESTADO_APROBACION = '" . $filtros['estado'] . "'"; }
        
        // Filtros de fecha si los hay
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $sql .= " AND DATE(m.FECHA_HORA) BETWEEN '" . $filtros['fecha_inicio'] . "' AND '" . $filtros['fecha_fin'] . "'";
        }

        $sql .= " ORDER BY m.FECHA_HORA DESC";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstadoAprobacion($id, $estado, $motivo) {
        $stmtM = $this->db->prepare("SELECT * FROM MOVIMIENTO_CAJA WHERE ID_MOVIMIENTO = ?");
        $stmtM->execute([$id]);
        $mov = $stmtM->fetch(PDO::FETCH_ASSOC);
        if (!$mov) return ['status' => 'error', 'mensaje' => 'El movimiento especificado no existe.'];

        $fecha = date('Y-m-d', strtotime($mov['FECHA_HORA']));
        $idSucursal = $mov['ID_SUCURSAL'];
        $cierre = $this->verificarCierreDia($idSucursal, $fecha);

        if ($cierre) {
            // CANDADO DE BLOQUEO CONTABLE (HARD CLOSE)
            if (($cierre['DEPOSITO_ESTADO'] ?? '') === 'APROBADO') {
                return [
                    'status' => 'error', 
                    'mensaje' => 'Bloqueo Contable: La caja de la sucursal para la fecha ' . $fecha . ' ya fue auditada y conciliada formalmente por Tesorería. No se permite alterar ni rechazar movimientos de un período cerrado.'
                ];
            }
        }

        // Proceder con la actualización en MOVIMIENTO_CAJA
        $stmt = $this->db->prepare("UPDATE MOVIMIENTO_CAJA SET ESTADO_APROBACION = ?, MOTIVO_RECHAZO = ? WHERE ID_MOVIMIENTO = ?");
        $stmt->execute([$estado, $motivo, $id]);

        // Si la caja ya estaba cerrada (pero aún no auditada), sincronizar y recalcular el cierre
        if ($cierre) {
            $this->reajustarCierrePorCambioMovimiento($cierre['ID_CIERRE'], $idSucursal, $fecha);
        }

        return ['status' => 'success'];
    }

    public function reajustarCierrePorCambioMovimiento($idCierre, $idSucursal, $fecha) {
        $movimientos = $this->obtenerMovimientosDelDia($idSucursal, $fecha);
        
        $totales = [
            'ingresos' => 0, 'egresos' => 0, 'efectivo' => 0,
            'saldo_neto' => 0, 'utilidad_bruta' => 0, 'capital_reposicion' => 0, 'comisiones' => 0,
            'egresos_gastos' => 0, 'utilidad_neta' => 0, 'valor_a_depositar' => 0
        ];
        
        foreach ($movimientos as $mov) {
            $monto = floatval($mov['MONTO']);
            if ($mov['TIPO_MOVIMIENTO'] == 'INGRESO') {
                $totales['ingresos'] += $monto;
                if ($mov['METODO_PAGO'] == 'EFECTIVO') $totales['efectivo'] += $monto;
                if ($mov['ID_FACTURA']) {
                    $fa_total = floatval($mov['FA_TOTAL'] ?? 1); 
                    if ($fa_total <= 0) $fa_total = 1;
                    $tipo_utilidad = $mov['TIPO_UTILIDAD'] ?? 'PORCENTAJE';
                    $origen = $mov['ORIGEN_FACTURA'] ?? '';
                    $esPasaje = in_array($origen, ['PASAJE', 'RESERVA INICIAL', 'ABONO PASAJE']);

                    if ($esPasaje) {
                        $param_util = floatval($mov['UTILIDAD_PASAJE'] ?? 100);
                        $cant_pasajes = max(1, intval($mov['FA_CANTIDAD'] ?? 1));
                        if ($tipo_utilidad === 'FIJO') {
                            $util_bruta = ($monto / $fa_total) * ($param_util * $cant_pasajes);
                        } else {
                            $util_bruta = $monto * ($param_util / 100);
                        }
                    } elseif ($origen === 'ENCOMIENDA') {
                        // Encomiendas se liquidan siempre como porcentaje (%)
                        $param_util = floatval($mov['UTILIDAD_ENCOMIENDA'] ?? 100);
                        $util_bruta = $monto * ($param_util / 100);
                    } elseif ($origen === 'VALOR') {
                        // Valores/Giros se liquidan siempre como porcentaje (%)
                        $param_util = floatval($mov['UTILIDAD_VALORES'] ?? 100);
                        $util_bruta = $monto * ($param_util / 100);
                    } else {
                        // Sobrepeso y otros orígenes: se liquidan como porcentaje (%)
                        $param_util = floatval($mov['UTILIDAD_PASAJE'] ?? 100);
                        $util_bruta = $monto * ($param_util / 100);
                    }
                    $util_bruta = min($monto, max(0.0, $util_bruta));
                    $reposicion = max(0, $monto - $util_bruta);
                    $comision = $monto * (floatval($mov['COMISION_EMPLEADO_PCT'] ?? 0) / 100);
                    $totales['utilidad_bruta'] += $util_bruta;
                    $totales['capital_reposicion'] += $reposicion;
                    $totales['comisiones'] += $comision;
                } else {
                    $totales['utilidad_bruta'] += $monto;
                }
            } else { // EGRESO
                $totales['egresos'] += $monto;
                if ($mov['METODO_PAGO'] == 'EFECTIVO') $totales['efectivo'] -= $monto;
                if ($mov['ID_FACTURA']) {
                    $fa_total = floatval($mov['FA_TOTAL'] ?? 1); 
                    if ($fa_total <= 0) $fa_total = 1;
                    $tipo_utilidad = $mov['TIPO_UTILIDAD'] ?? 'PORCENTAJE';
                    $origen = $mov['ORIGEN_FACTURA'] ?? '';
                    $esPasaje = in_array($origen, ['PASAJE', 'RESERVA INICIAL', 'ABONO PASAJE']);

                    if ($esPasaje) {
                        $param_util = floatval($mov['UTILIDAD_PASAJE'] ?? 100);
                        $cant_pasajes = max(1, intval($mov['FA_CANTIDAD'] ?? 1));
                        if ($tipo_utilidad === 'FIJO') {
                            $util_bruta = ($monto / $fa_total) * ($param_util * $cant_pasajes);
                        } else {
                            $util_bruta = $monto * ($param_util / 100);
                        }
                    } elseif ($origen === 'ENCOMIENDA') {
                        $param_util = floatval($mov['UTILIDAD_ENCOMIENDA'] ?? 100);
                        $util_bruta = $monto * ($param_util / 100);
                    } elseif ($origen === 'VALOR') {
                        $param_util = floatval($mov['UTILIDAD_VALORES'] ?? 100);
                        $util_bruta = $monto * ($param_util / 100);
                    } else {
                        $param_util = floatval($mov['UTILIDAD_PASAJE'] ?? 100);
                        $util_bruta = $monto * ($param_util / 100);
                    }
                    $util_bruta = min($monto, max(0.0, $util_bruta));
                    $reposicion = max(0, $monto - $util_bruta);
                    $comision = $monto * (floatval($mov['COMISION_EMPLEADO_PCT'] ?? 0) / 100);
                    $totales['utilidad_bruta'] -= $util_bruta;
                    $totales['capital_reposicion'] -= $reposicion;
                    $totales['comisiones'] -= $comision;
                } else {
                    $totales['egresos_gastos'] += $monto;
                }
            }
        }
        $totales['saldo_neto'] = $totales['ingresos'] - $totales['egresos'];
        $totales['utilidad_neta'] = $totales['utilidad_bruta'] - $totales['comisiones'] - $totales['egresos_gastos'];
        $totales['valor_a_depositar'] = max(0, $totales['efectivo'] - $totales['comisiones']);
        
        // Obtener el monto actualmente depositado
        $stmtDep = $this->db->prepare("SELECT DEPOSITO FROM CIERRE_CAJA WHERE ID_CIERRE = ?");
        $stmtDep->execute([$idCierre]);
        $depositoActual = floatval($stmtDep->fetchColumn() ?? 0);
        $vPorDepositar = max(0, $totales['valor_a_depositar'] - $depositoActual);
        
        $stmtUp = $this->db->prepare("UPDATE CIERRE_CAJA 
                                       SET TOTAL_INGRESOS = ?,
                                           TOTAL_EGRESOS = ?,
                                           SALDO_FINAL = ?,
                                           UTILIDAD = ?,
                                           CAPITAL_REPOSICION = ?,
                                           COMISION_EMPLEADOS = ?,
                                           VALOR_A_DEPOSITAR = ?,
                                           V_POR_DEPOSITAR = ?
                                       WHERE ID_CIERRE = ?");
        $stmtUp->execute([
            $totales['ingresos'],
            $totales['egresos'],
            $totales['saldo_neto'],
            $totales['utilidad_neta'],
            $totales['capital_reposicion'],
            $totales['comisiones'],
            $totales['valor_a_depositar'],
            $vPorDepositar,
            $idCierre
        ]);
        
        // Volver a recalcular estado según los depósitos
        $this->recalcularDepositosCierre($idCierre);
    }

    public function obtenerHistorialCierresAvanzado($filtros) {
        $sql = "SELECT c.*, u.NOMBRE as CAJERO, s.CIUDAD as SUCURSAL_NOMBRE,
                       COALESCE(c.VALOR_A_DEPOSITAR, GREATEST(0, c.SALDO_FINAL - COALESCE(c.COMISION_EMPLEADOS, 0))) as VALOR_A_DEPOSITAR_CALC
                FROM CIERRE_CAJA c
                LEFT JOIN USUARIO u ON c.ID_USUARIO = u.ID_USUARIO
                LEFT JOIN SUCURSAL s ON c.ID_SUCURSAL = s.ID_SUCURSAL WHERE 1=1";
                
        if (!empty($filtros['sucursal'])) { $sql .= " AND c.ID_SUCURSAL = " . intval($filtros['sucursal']); }
        if (!empty($filtros['estado_deposito'])) { $sql .= " AND c.DEPOSITO_ESTADO = '" . $filtros['estado_deposito'] . "'"; }
        
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
             $sql .= " AND c.FECHA_CAJA BETWEEN '" . $filtros['fecha_inicio'] . "' AND '" . $filtros['fecha_fin'] . "'";
        }
        
        $sql .= " ORDER BY c.FECHA_CAJA DESC, c.ID_CIERRE DESC";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarDepositoDetalle($idCierre, $idUsuario, $banco, $numDocumento, $monto, $observaciones = '') {
        try {
            $stmt = $this->db->prepare("INSERT INTO CIERRE_DEPOSITO_DETALLE (ID_CIERRE, ID_USUARIO, BANCO, NUM_DOCUMENTO, MONTO, OBSERVACIONES, ESTADO) VALUES (?, ?, ?, ?, ?, ?, 'REGISTRADO')");
            $stmt->execute([$idCierre, $idUsuario, $banco, $numDocumento, floatval($monto), $observaciones]);
            
            $this->recalcularDepositosCierre($idCierre);
            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function recalcularDepositosCierre($idCierre) {
        // Sumar todos los depósitos registrados (excluyendo rechazados)
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(MONTO), 0) as TOTAL_DEP FROM CIERRE_DEPOSITO_DETALLE WHERE ID_CIERRE = ? AND ESTADO != 'RECHAZADO'");
        $stmt->execute([$idCierre]);
        $totalDepositado = floatval($stmt->fetchColumn() ?? 0);

        // Obtener datos del cierre
        $stmt2 = $this->db->prepare("SELECT VALOR_A_DEPOSITAR, SALDO_FINAL, COMISION_EMPLEADOS, DEPOSITO_ESTADO FROM CIERRE_CAJA WHERE ID_CIERRE = ?");
        $stmt2->execute([$idCierre]);
        $cierre = $stmt2->fetch(PDO::FETCH_ASSOC);
        if (!$cierre) return false;

        $valorExigible = floatval($cierre['VALOR_A_DEPOSITAR']);
        if ($valorExigible <= 0) {
            $valorExigible = max(0, floatval($cierre['SALDO_FINAL']) - floatval($cierre['COMISION_EMPLEADOS']));
        }

        $vPorDepositar = max(0, $valorExigible - $totalDepositado);
        
        // Estado del depósito (mantener APROBADO si ya fue auditado)
        $estadoActual = $cierre['DEPOSITO_ESTADO'];
        if ($estadoActual !== 'APROBADO') {
            if ($totalDepositado >= $valorExigible && $valorExigible > 0) {
                $estadoDeposito = 'COMPLETO';
            } elseif ($totalDepositado > 0) {
                $estadoDeposito = 'PARCIAL';
            } else {
                $estadoDeposito = 'PENDIENTE';
            }
        } else {
            $estadoDeposito = 'APROBADO';
        }

        // Concatenar números de documentos
        $stmtDocs = $this->db->prepare("SELECT GROUP_CONCAT(CONCAT(BANCO, ': #', NUM_DOCUMENTO, ' ($', FORMAT(MONTO, 2), ')') SEPARATOR ' | ') FROM CIERRE_DEPOSITO_DETALLE WHERE ID_CIERRE = ? AND ESTADO != 'RECHAZADO'");
        $stmtDocs->execute([$idCierre]);
        $docs = $stmtDocs->fetchColumn() ?? '';

        $stmtUp = $this->db->prepare("UPDATE CIERRE_CAJA 
                                       SET DEPOSITO = ?, 
                                           V_POR_DEPOSITAR = ?, 
                                           DEPOSITO_ESTADO = ?, 
                                           DEPOSITO_NUM_DOCUMENTO = ?,
                                           VALOR_A_DEPOSITAR = ? 
                                       WHERE ID_CIERRE = ?");
        return $stmtUp->execute([$totalDepositado, $vPorDepositar, $estadoDeposito, $docs, $valorExigible, $idCierre]);
    }

    public function obtenerDepositosDetallePorCierre($idCierre) {
        $stmt = $this->db->prepare("SELECT d.*, u.NOMBRE as CAJERO, ua.NOMBRE as AUDITOR
                                    FROM CIERRE_DEPOSITO_DETALLE d
                                    LEFT JOIN USUARIO u ON d.ID_USUARIO = u.ID_USUARIO
                                    LEFT JOIN USUARIO ua ON d.ID_USUARIO_AUDITA = ua.ID_USUARIO
                                    WHERE d.ID_CIERRE = ?
                                    ORDER BY d.FECHA_DEPOSITO DESC, d.ID_DEPOSITO DESC");
        $stmt->execute([$idCierre]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarDepositoCierre($id, $deposito) {
        $deposito = floatval($deposito);
        $stmt = $this->db->prepare("UPDATE CIERRE_CAJA 
                                    SET DEPOSITO = ?, 
                                        V_POR_DEPOSITAR = GREATEST(0, COALESCE(VALOR_A_DEPOSITAR, SALDO_FINAL - COALESCE(COMISION_EMPLEADOS, 0)) - ?),
                                        DEPOSITO_ESTADO = CASE 
                                            WHEN ? >= COALESCE(VALOR_A_DEPOSITAR, SALDO_FINAL - COALESCE(COMISION_EMPLEADOS, 0)) THEN 'COMPLETO' 
                                            WHEN ? > 0 THEN 'PARCIAL' 
                                            ELSE 'PENDIENTE' 
                                        END
                                    WHERE ID_CIERRE = ?");
        return $stmt->execute([$deposito, $deposito, $deposito, $deposito, $id]);
    }

    public function procesarDeposito($id, $nro_documento, $fecha) {
        $stmt = $this->db->prepare("UPDATE CIERRE_CAJA SET DEPOSITO_NUM_DOCUMENTO = ?, DEPOSITO_ESTADO = 'APROBADO', DEPOSITO_FECHA_APROBACION = ? WHERE ID_CIERRE = ?");
        $res = $stmt->execute([$nro_documento, $fecha, $id]);

        $stmtDet = $this->db->prepare("UPDATE CIERRE_DEPOSITO_DETALLE SET ESTADO = 'APROBADO', FECHA_AUDITORIA = ? WHERE ID_CIERRE = ? AND ESTADO = 'REGISTRADO'");
        $stmtDet->execute([$fecha, $id]);
        return $res;
    }

    public function registrarEgresoPendiente($usuario, $sucursal, $tipo, $concepto, $monto, $codigo) {
        $stmt = $this->db->prepare("INSERT INTO MOVIMIENTO_CAJA (ID_USUARIO, ID_SUCURSAL, TIPO_MOVIMIENTO, METODO_PAGO, MONTO, CONCEPTO, CODIGO_MOVIMIENTO, TIPO_EGRESO, ESTADO_APROBACION) VALUES (?, ?, 'EGRESO', 'EFECTIVO', ?, ?, ?, ?, 'POR_APROBAR')");
        return $stmt->execute([$usuario, $sucursal, $monto, $concepto, $codigo, $tipo]);
    }

    public function obtenerNombreSucursal($idSucursal) {
        $stmt = $this->db->prepare("SELECT CIUDAD FROM SUCURSAL WHERE ID_SUCURSAL = ?");
        $stmt->execute([$idSucursal]);
        $ciudad = $stmt->fetchColumn();
        return $ciudad ? $ciudad : "Sucursal #{$idSucursal}";
    }

    public function moverFechaMovimientos($movimientosIds, $nuevaFecha, $usuarioId) {
        if (empty($movimientosIds) || !is_array($movimientosIds)) {
            return ['status' => 'error', 'mensaje' => 'No se seleccionaron movimientos para cambiar de fecha.'];
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuevaFecha)) {
            return ['status' => 'error', 'mensaje' => 'La fecha de destino proporcionada no es válida. Formato esperado: AAAA-MM-DD.'];
        }

        $ids = array_map('intval', $movimientosIds);
        $ids = array_values(array_filter($ids, function($id) { return $id > 0; }));
        if (empty($ids)) {
            return ['status' => 'error', 'mensaje' => 'Los identificadores de movimientos son inválidos.'];
        }

        try {
            $this->db->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("SELECT * FROM MOVIMIENTO_CAJA WHERE ID_MOVIMIENTO IN ($placeholders)");
            $stmt->execute($ids);
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($movimientos)) {
                $this->db->rollBack();
                return ['status' => 'error', 'mensaje' => 'No se encontraron las transacciones solicitadas en el sistema.'];
            }

            // 1. Validaciones de Seguridad (Candados de Caja Cerrada)
            foreach ($movimientos as $m) {
                $idSucursal = $m['ID_SUCURSAL'];
                $fechaOrigen = date('Y-m-d', strtotime($m['FECHA_HORA']));
                $nomSucursal = $this->obtenerNombreSucursal($idSucursal);

                // A) Candado en Caja de Origen: No se permite extraer movimientos si la caja de esa fecha ya fue cerrada
                if ($this->verificarCierreDia($idSucursal, $fechaOrigen)) {
                    $this->db->rollBack();
                    return [
                        'status' => 'error',
                        'mensaje' => "Operación Denegada: La caja de origen ({$fechaOrigen} en {$nomSucursal}) ya se encuentra CERRADA. Por auditoría, no se pueden alterar transacciones de un corte finalizado."
                    ];
                }

                // B) Candado en Caja de Destino: No se permite inyectar movimientos si la caja de la nueva fecha ya fue cerrada
                if ($this->verificarCierreDia($idSucursal, $nuevaFecha)) {
                    $this->db->rollBack();
                    return [
                        'status' => 'error',
                        'mensaje' => "Operación Denegada: La caja de destino ({$nuevaFecha} en {$nomSucursal}) ya fue CERRADA. No es posible trasladar transacciones hacia un cuadre de caja cerrado."
                    ];
                }
            }

            // 2. Ejecutar traslado de fechas en base de datos
            $stmtUpdateMov = $this->db->prepare("UPDATE MOVIMIENTO_CAJA SET FECHA_HORA = ? WHERE ID_MOVIMIENTO = ?");
            $stmtUpdateFac = $this->db->prepare("UPDATE FACTURA SET FA_FECHA = ? WHERE ID_FACTURA = ?");
            $stmtUpdateAbono = $this->db->prepare("UPDATE ABONO SET FECHA = CONCAT(?, ' ', TIME(FECHA)) WHERE ID_FACTURA = ?");
            $stmtUpdateValor = $this->db->prepare("UPDATE VALOR SET VA_RETIRO = ? WHERE ID_FACTURA = ? AND VA_ESTADO = 'Entregado'");
            $stmtUpdateEncomienda = $this->db->prepare("UPDATE ENCOMIENDA SET ENC_RETIRO = ? WHERE ID_FACTURA = ? AND ENC_ESTADO = 'Entregado'");

            $actualizados = 0;
            foreach ($movimientos as $m) {
                $horaOriginal = date('H:i:s', strtotime($m['FECHA_HORA']));
                $nuevaFechaHora = $nuevaFecha . ' ' . $horaOriginal;

                $stmtUpdateMov->execute([$nuevaFechaHora, $m['ID_MOVIMIENTO']]);

                if (!empty($m['ID_FACTURA'])) {
                    $idFac = intval($m['ID_FACTURA']);
                    $stmtUpdateFac->execute([$nuevaFecha, $idFac]);
                    $stmtUpdateAbono->execute([$nuevaFecha, $idFac]);
                    $stmtUpdateValor->execute([$nuevaFecha, $idFac]);
                    $stmtUpdateEncomienda->execute([$nuevaFecha, $idFac]);
                }

                $actualizados++;
            }

            $this->db->commit();

            return [
                'status' => 'success',
                'count' => $actualizados,
                'nueva_fecha' => $nuevaFecha
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['status' => 'error', 'mensaje' => 'Error inesperado al mover transacciones: ' . $e->getMessage()];
        }
    }
}
?>