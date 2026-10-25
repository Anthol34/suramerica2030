<?php
namespace Modules\Finanzas;

use Platform\Storage\Database;
use Platform\Helpers\ModalidadHelper;
use PDO;
use Exception;

class FinanzasRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function obtenerSucursales() {
        return $this->db->query("SELECT * FROM SUCURSAL ORDER BY ID_SUCURSAL")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarSucursalBD($idSucursal, $comision, $gasto) {
        try {
            $stmt = $this->db->prepare("UPDATE SUCURSAL SET COMISION_EMPLEADO_PCT = ?, GASTO_FIJO_DIARIO = ? WHERE ID_SUCURSAL = ?");
            $stmt->execute([$comision, $gasto, $idSucursal]);
            return true;
        } catch (Exception $e) { 
            return $e->getMessage(); 
        }
    }

    public function obtenerOperadoresDisponibles() {
        try {
            $sql = "SELECT DISTINCT NOMBRE_COMERCIAL as OPERADOR FROM OPERADOR WHERE ESTADO = 'ACTIVO'
                    UNION
                    SELECT DISTINCT TRIM(OPERADOR) as OPERADOR FROM RUTA_TRAMO WHERE OPERADOR IS NOT NULL AND OPERADOR != ''
                    UNION
                    SELECT DISTINCT TRIM(SP_OPERADOR) as OPERADOR FROM SOBREPESO WHERE SP_OPERADOR IS NOT NULL AND SP_OPERADOR != '' AND (ESTADO IS NULL OR ESTADO != 'ANULADO')
                    ORDER BY OPERADOR ASC";
            $stmt = $this->db->query($sql);
            $ops = array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'OPERADOR'));
            if (!in_array('Panamericana de Transportes', $ops) && !in_array('Panamericana', $ops)) $ops[] = 'Panamericana de Transportes';
            if (!in_array('Suramérica 2030 (Flota Propia)', $ops) && !in_array('Expresos Suramérica 2030', $ops)) $ops[] = 'Suramérica 2030 (Flota Propia)';
            sort($ops);
            return array_values(array_unique($ops));
        } catch (Exception $e) {
            return ['Panamericana de Transportes', 'Suramérica 2030 (Flota Propia)', 'Empresa Local Ecuador', 'Empresa Local Colombia'];
        }
    }

    public function obtenerReporteLiquidacionSocios($filtros = []) {
        $fechaInicio = !empty($filtros['fecha_inicio']) ? $filtros['fecha_inicio'] : date('Y-m-01');
        $fechaFin = !empty($filtros['fecha_fin']) ? $filtros['fecha_fin'] : date('Y-m-d');
        $tipoFecha = (isset($filtros['tipo_fecha']) && $filtros['tipo_fecha'] === 'viaje') ? 'f.FECHA_VIAJE' : 'f.FA_FECHA';
        $operadorFiltro = trim($filtros['operador'] ?? 'TODOS');
        $modalidadFiltro = trim($filtros['modalidad'] ?? 'TODAS');
        $sucursalFiltro = (!empty($filtros['id_sucursal']) && $filtros['id_sucursal'] !== 'TODAS') ? intval($filtros['id_sucursal']) : null;

        $params = [':f_ini' => $fechaInicio, ':f_fin' => $fechaFin];
        $whereSucursal = "";
        if ($sucursalFiltro) {
            $whereSucursal = " AND f.ID_SUCURSAL = :sucursal ";
            $params[':sucursal'] = $sucursalFiltro;
        }

        // Cargar diccionario de operadores con datos bancarios
        $operadoresDict = [];
        try {
            $stmtAllOp = $this->db->query("SELECT * FROM OPERADOR");
            while ($rowOp = $stmtAllOp->fetch(PDO::FETCH_ASSOC)) {
                $operadoresDict[mb_strtoupper(trim($rowOp['NOMBRE_COMERCIAL']))] = $rowOp;
                if (!empty($rowOp['RAZON_SOCIAL'])) {
                    $operadoresDict[mb_strtoupper(trim($rowOp['RAZON_SOCIAL']))] = $rowOp;
                }
            }
        } catch (Exception $e) {}

        // 1. Obtener pasajes válidos (excluyendo devueltos/anulados)
        $sqlPasajes = "SELECT p.ID_PASAJE, p.ID_FACTURA, p.PRECIO_TOTAL as PAX_PVP, p.PAX_ASIENTO,
                              f.FA_FECHA, f.FECHA_VIAJE, f.ID_RUTA, f.ID_SUCURSAL,
                              c.CLI_NOMBRE, c.CLI_APELLIDO, c.ID_CLIENTE as CLI_DOC,
                              r.RUT_ORIGEN, r.RUT_DESTINO, r.MODALIDAD, r.TIPO_VIAJE, r.PRECIO_PASAJE as RUTA_PVP,
                              r.ID_OPERADOR as RUTA_ID_OPERADOR, op_r.NOMBRE_COMERCIAL as RUTA_OPERADOR_NOMBRE,
                              suc.CIUDAD as SUCURSAL_NOMBRE
                       FROM PASAJE p
                       INNER JOIN FACTURA f ON p.ID_FACTURA = f.ID_FACTURA
                       LEFT JOIN CLIENTE c ON p.ID_CLIENTE = c.ID_CLIENTE
                       LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                       LEFT JOIN OPERADOR op_r ON r.ID_OPERADOR = op_r.ID_OPERADOR
                       LEFT JOIN SUCURSAL suc ON f.ID_SUCURSAL = suc.ID_SUCURSAL
                       WHERE p.ESTADO != 'DEVUELTO'
                          AND p.ESTADO != 'ANULADO'
                          AND (f.ESTADO IS NULL OR f.ESTADO != 'ANULADA')
                         AND $tipoFecha BETWEEN :f_ini AND :f_fin
                         $whereSucursal
                       ORDER BY $tipoFecha DESC, p.ID_PASAJE DESC";
        $stmtP = $this->db->prepare($sqlPasajes);
        $stmtP->execute($params);
        $pasajes = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        // Mapear tramos de las rutas encontradas y conexiones congeladas por pasaje
        $rutasIds = array_unique(array_filter(array_column($pasajes, 'ID_RUTA')));
        $pasajesIds = array_unique(array_filter(array_column($pasajes, 'ID_PASAJE')));
        $tramosMap = [];
        $pasajeTramosMap = [];

        if (!empty($rutasIds)) {
            $inRutas = implode(',', array_map('intval', $rutasIds));
            $stmtTr = $this->db->query("SELECT rt.*, op.NOMBRE_COMERCIAL as OP_NOMBRE, op.BANCO, op.NUMERO_CUENTA, op.TIPO_CUENTA, op.TITULAR_CUENTA, op.IDENTIFICACION 
                                        FROM RUTA_TRAMO rt 
                                        LEFT JOIN OPERADOR op ON rt.ID_OPERADOR = op.ID_OPERADOR 
                                        WHERE rt.ID_RUTA IN ($inRutas) 
                                        ORDER BY rt.ID_RUTA, rt.ORDEN ASC");
            while ($tr = $stmtTr->fetch(PDO::FETCH_ASSOC)) {
                $tramosMap[$tr['ID_RUTA']][] = $tr;
            }
        }

        if (!empty($pasajesIds)) {
            $inPax = implode(',', array_map('intval', $pasajesIds));
            $stmtPt = $this->db->query("SELECT pt.*, rt.ORIGEN_TRAMO, rt.DESTINO_TRAMO, rt.PVP_TRAMO, rt.UTILIDAD_AGENCIA,
                                               op.NOMBRE_COMERCIAL as OP_NOMBRE, op.BANCO, op.NUMERO_CUENTA, op.TIPO_CUENTA, op.TITULAR_CUENTA, op.IDENTIFICACION 
                                        FROM PASAJE_TRAMO pt 
                                        LEFT JOIN RUTA_TRAMO rt ON pt.ID_RUTA_TRAMO = rt.ID_RUTA_TRAMO 
                                        LEFT JOIN OPERADOR op ON COALESCE(pt.ID_OPERADOR, rt.ID_OPERADOR) = op.ID_OPERADOR 
                                        WHERE pt.ID_PASAJE IN ($inPax) 
                                        ORDER BY pt.ID_PASAJE, rt.ORDEN ASC");
            while ($pt = $stmtPt->fetch(PDO::FETCH_ASSOC)) {
                $pasajeTramosMap[$pt['ID_PASAJE']][] = $pt;
            }
        }

        // 2. Obtener sobrepesos cobrados en el rango
        $sqlSp = "SELECT s.*, f.FA_FECHA, f.FECHA_VIAJE, f.ID_RUTA, f.ID_SUCURSAL,
                         c.CLI_NOMBRE, c.CLI_APELLIDO, c.ID_CLIENTE as CLI_DOC,
                         r.RUT_ORIGEN, r.RUT_DESTINO, r.MODALIDAD,
                         suc.CIUDAD as SUCURSAL_NOMBRE
                  FROM SOBREPESO s
                  INNER JOIN FACTURA f ON s.ID_FACTURA = f.ID_FACTURA
                  LEFT JOIN CLIENTE c ON f.ID_CLIENTE = c.ID_CLIENTE
                  LEFT JOIN RUTA r ON f.ID_RUTA = r.ID_RUTA
                  LEFT JOIN SUCURSAL suc ON f.ID_SUCURSAL = suc.ID_SUCURSAL
                  WHERE $tipoFecha BETWEEN :f_ini AND :f_fin
                    AND (s.ESTADO IS NULL OR s.ESTADO != 'ANULADO')
                    AND (f.ESTADO IS NULL OR f.ESTADO != 'ANULADA')
                    $whereSucursal
                  ORDER BY $tipoFecha DESC, s.ID_SOBREPESO DESC";
        $stmtSp = $this->db->prepare($sqlSp);
        $stmtSp->execute($params);
        $sobrepesos = $stmtSp->fetchAll(PDO::FETCH_ASSOC);

        $porOperador = [];
        $movimientos = [];

        $initOp = function(&$porOperador, $op) use ($operadoresDict) {
            if (!isset($porOperador[$op])) {
                $opKey = mb_strtoupper(trim($op));
                $infoOp = $operadoresDict[$opKey] ?? null;

                if (!$infoOp) {
                    foreach ($operadoresDict as $k => $v) {
                        if (strpos($k, $opKey) !== false || strpos($opKey, $k) !== false) {
                            $infoOp = $v;
                            break;
                        }
                    }
                }

                $porOperador[$op] = [
                    'operador' => $op,
                    'id_operador' => $infoOp['ID_OPERADOR'] ?? null,
                    'razon_social' => $infoOp['RAZON_SOCIAL'] ?? $op,
                    'tipo_operador' => $infoOp['TIPO_OPERADOR'] ?? 'SOCIO_AFILIADO',
                    'identificacion' => $infoOp['IDENTIFICACION'] ?? '',
                    'telefono' => $infoOp['TELEFONO'] ?? '',
                    'banco' => $infoOp['BANCO'] ?? '',
                    'tipo_cuenta' => $infoOp['TIPO_CUENTA'] ?? 'CORRIENTE',
                    'numero_cuenta' => $infoOp['NUMERO_CUENTA'] ?? '',
                    'titular_cuenta' => $infoOp['TITULAR_CUENTA'] ?? '',
                    'titular_doc' => $infoOp['TITULAR_DOC'] ?? '',
                    'cant_pasajes' => 0,
                    'total_pvp_pasajes' => 0.0,
                    'total_costo_pasajes' => 0.0,
                    'total_utilidad_pasajes' => 0.0,
                    'cant_sobrepeso' => 0,
                    'kg_sobrepeso' => 0.0,
                    'total_sobrepeso_usd' => 0.0,
                    'total_sobrepeso_socio' => 0.0,
                    'total_sobrepeso_agencia' => 0.0,
                    'total_a_liquidar' => 0.0,
                    'total_utilidad_agencia' => 0.0,
                    'margen_pct' => 0.0
                ];
            }
        };

        // Procesar Pasajes
        foreach ($pasajes as $pax) {
            $idPasaje = $pax['ID_PASAJE'];
            $idRuta = $pax['ID_RUTA'];
            $tramos = $pasajeTramosMap[$idPasaje] ?? ($tramosMap[$idRuta] ?? []);
            $nombreCliente = trim(($pax['CLI_NOMBRE'] ?? '') . ' ' . ($pax['CLI_APELLIDO'] ?? '')) ?: 'Cliente S/N';

            $modCodigo = ModalidadHelper::normalizarCodigo($pax['MODALIDAD'] ?? 'CONEXIONES');
            $modNombre = ModalidadHelper::getNombre($modCodigo);
            $modBadge = ModalidadHelper::renderBadge($modCodigo, count($tramos));

            if ($modalidadFiltro !== 'TODAS' && strtoupper($modalidadFiltro) !== $modCodigo) {
                continue;
            }

            if (!empty($tramos)) {
                foreach ($tramos as $tr) {
                    $op = !empty($tr['OP_NOMBRE']) ? trim($tr['OP_NOMBRE']) : (trim($tr['OPERADOR'] ?? 'Suramérica 2030') ?: 'Suramérica 2030');
                    if ($operadorFiltro !== 'TODOS' && mb_strtoupper($op) !== mb_strtoupper($operadorFiltro)) {
                        continue;
                    }
                    $initOp($porOperador, $op);

                    $pvpTramo = floatval($tr['PVP_TRAMO'] ?? 0);
                    $costoSocio = isset($tr['COSTO_LIQUIDACION']) ? floatval($tr['COSTO_LIQUIDACION']) : floatval($tr['COSTO_OPERADOR'] ?? 0);
                    $utilAgencia = floatval($tr['UTILIDAD_AGENCIA'] ?? max(0, $pvpTramo - $costoSocio));
                    $fechaViajeTramo = !empty($tr['FECHA_TRAMO']) ? $tr['FECHA_TRAMO'] : $pax['FECHA_VIAJE'];

                    $porOperador[$op]['cant_pasajes']++;
                    $porOperador[$op]['total_pvp_pasajes'] += $pvpTramo;
                    $porOperador[$op]['total_costo_pasajes'] += $costoSocio;
                    $porOperador[$op]['total_utilidad_pasajes'] += $utilAgencia;
                    $porOperador[$op]['total_a_liquidar'] += $costoSocio;
                    $porOperador[$op]['total_utilidad_agencia'] += $utilAgencia;

                    $movimientos[] = [
                        'tipo' => 'PASAJE',
                        'id_factura' => $pax['ID_FACTURA'],
                        'id_pasaje' => $pax['ID_PASAJE'],
                        'fecha_venta' => $pax['FA_FECHA'],
                        'fecha_viaje' => $fechaViajeTramo,
                        'sucursal' => $pax['SUCURSAL_NOMBRE'] ?? 'Matriz',
                        'cliente' => $nombreCliente,
                        'documento' => $pax['CLI_DOC'],
                        'ruta' => "{$pax['RUT_ORIGEN']} ➔ {$pax['RUT_DESTINO']}",
                        'tramo' => "{$tr['ORIGEN_TRAMO']} ➔ {$tr['DESTINO_TRAMO']}",
                        'operador' => $op,
                        'modalidad' => $modCodigo,
                        'modalidad_nombre' => $modNombre,
                        'modalidad_badge' => $modBadge,
                        'asiento' => $pax['PAX_ASIENTO'],
                        'pvp' => $pvpTramo,
                        'costo_socio' => $costoSocio,
                        'utilidad_agencia' => $utilAgencia,
                        'kg_sobrepeso' => 0,
                        'detalle' => "Pasaje Asiento #{$pax['PAX_ASIENTO']}"
                    ];
                }
            } else {
                $op = !empty($pax['RUTA_OPERADOR_NOMBRE']) 
                    ? trim($pax['RUTA_OPERADOR_NOMBRE']) 
                    : (($pax['MODALIDAD'] === 'PANAMERICANA') ? 'Panamericana de Transportes' : (!empty($pax['MODALIDAD']) ? $pax['MODALIDAD'] : 'Suramérica 2030 (Flota Propia)'));
                if ($operadorFiltro !== 'TODOS' && mb_strtoupper($op) !== mb_strtoupper($operadorFiltro)) {
                    continue;
                }
                $initOp($porOperador, $op);

                $pvpPax = floatval($pax['PAX_PVP'] ?? 0);
                $costoSocio = ($op === 'Propio') ? 0.0 : $pvpPax * 0.90;
                $utilAgencia = $pvpPax - $costoSocio;

                $porOperador[$op]['cant_pasajes']++;
                $porOperador[$op]['total_pvp_pasajes'] += $pvpPax;
                $porOperador[$op]['total_costo_pasajes'] += $costoSocio;
                $porOperador[$op]['total_utilidad_pasajes'] += $utilAgencia;
                $porOperador[$op]['total_a_liquidar'] += $costoSocio;
                $porOperador[$op]['total_utilidad_agencia'] += $utilAgencia;

                $movimientos[] = [
                    'tipo' => 'PASAJE',
                    'id_factura' => $pax['ID_FACTURA'],
                    'id_pasaje' => $pax['ID_PASAJE'],
                    'fecha_venta' => $pax['FA_FECHA'],
                    'fecha_viaje' => $pax['FECHA_VIAJE'],
                    'sucursal' => $pax['SUCURSAL_NOMBRE'] ?? 'Matriz',
                    'cliente' => $nombreCliente,
                    'documento' => $pax['CLI_DOC'],
                    'ruta' => "{$pax['RUT_ORIGEN']} ➔ {$pax['RUT_DESTINO']}",
                    'tramo' => "Directo",
                    'operador' => $op,
                    'modalidad' => $modCodigo,
                    'modalidad_nombre' => $modNombre,
                    'modalidad_badge' => $modBadge,
                    'asiento' => $pax['PAX_ASIENTO'],
                    'pvp' => $pvpPax,
                    'costo_socio' => $costoSocio,
                    'utilidad_agencia' => $utilAgencia,
                    'kg_sobrepeso' => 0,
                    'detalle' => "Pasaje Directo Asiento #{$pax['PAX_ASIENTO']}"
                ];
            }
        }

        // Procesar Sobrepesos
        foreach ($sobrepesos as $sp) {
            if (($sp['ESTADO'] ?? '') === 'ANULADO') {
                continue;
            }

            $modCodigo = ModalidadHelper::normalizarCodigo($sp['MODALIDAD'] ?? 'CONEXIONES');
            $modNombre = ModalidadHelper::getNombre($modCodigo);
            $modBadge = ModalidadHelper::renderBadge($modCodigo);

            if ($modalidadFiltro !== 'TODAS' && strtoupper($modalidadFiltro) !== $modCodigo) {
                continue;
            }

            $op = trim($sp['SP_OPERADOR'] ?? '') ?: 'Panamericana';
            if ($operadorFiltro !== 'TODOS' && mb_strtoupper($op) !== mb_strtoupper($operadorFiltro)) {
                continue;
            }
            $initOp($porOperador, $op);

            $totalSp = floatval($sp['SP_TOTAL'] ?? 0);
            $kgSp = floatval($sp['SP_EXCESO_KG'] ?? 0);

            // Determinar desglose costo socio vs utilidad agencia
            $opInfo = $porOperador[$op] ?? [];
            $esPropia = (strpos(mb_strtoupper($op), 'PROPIA') !== false || ($opInfo['tipo_operador'] ?? '') === 'SOCIO_PROPIO');

            if ($esPropia) {
                $costoSocio = 0.0;
                $utilAgencia = $totalSp;
            } elseif (isset($sp['COSTO_SOCIO']) && $sp['COSTO_SOCIO'] !== null) {
                $costoSocio = floatval($sp['COSTO_SOCIO']);
                $utilAgencia = isset($sp['UTILIDAD_AGENCIA']) && $sp['UTILIDAD_AGENCIA'] !== null ? floatval($sp['UTILIDAD_AGENCIA']) : max(0.0, $totalSp - $costoSocio);
            } else {
                $costoSocio = $totalSp;
                $utilAgencia = 0.0;
            }

            $porOperador[$op]['cant_sobrepeso']++;
            $porOperador[$op]['kg_sobrepeso'] += $kgSp;
            $porOperador[$op]['total_sobrepeso_usd'] += $totalSp;
            $porOperador[$op]['total_sobrepeso_socio'] += $costoSocio;
            $porOperador[$op]['total_sobrepeso_agencia'] += $utilAgencia;
            $porOperador[$op]['total_a_liquidar'] += $costoSocio;
            $porOperador[$op]['total_utilidad_agencia'] += $utilAgencia;

            $nombreCliente = trim(($sp['CLI_NOMBRE'] ?? '') . ' ' . ($sp['CLI_APELLIDO'] ?? '')) ?: 'Pasajero Equipaje';
            $movimientos[] = [
                'tipo' => 'SOBREPESO',
                'id_factura' => $sp['ID_FACTURA'],
                'id_pasaje' => $sp['ID_PASAJE'],
                'fecha_venta' => $sp['FA_FECHA'],
                'fecha_viaje' => $sp['FECHA_VIAJE'],
                'sucursal' => $sp['SUCURSAL_NOMBRE'] ?? 'Matriz',
                'cliente' => $nombreCliente,
                'documento' => $sp['CLI_DOC'],
                'ruta' => "{$sp['RUT_ORIGEN']} ➔ {$sp['RUT_DESTINO']}",
                'tramo' => $sp['SP_TRAMO'] ?: 'Equipaje Exceso',
                'operador' => $op,
                'modalidad' => $modCodigo,
                'modalidad_nombre' => $modNombre,
                'modalidad_badge' => $modBadge,
                'asiento' => '-',
                'pvp' => $totalSp,
                'costo_socio' => $costoSocio,
                'utilidad_agencia' => $utilAgencia,
                'kg_sobrepeso' => $kgSp,
                'detalle' => "Sobrepeso: {$kgSp} Kg ({$sp['SP_TRAMO']})"
            ];
        }

        // Totales globales
        $totalRecaudado = 0.0;
        $totalLiquidar = 0.0;
        $totalUtilidad = 0.0;
        $totalBoletos = 0;
        $totalSobrepesoKg = 0.0;
        $totalSobrepesoUsd = 0.0;

        foreach ($porOperador as &$opData) {
            $opPvp = $opData['total_pvp_pasajes'] + $opData['total_sobrepeso_usd'];
            $opData['margen_pct'] = ($opPvp > 0) ? round(($opData['total_utilidad_agencia'] / $opPvp) * 100, 1) : 0;
            
            $totalRecaudado += $opPvp;
            $totalLiquidar += $opData['total_a_liquidar'];
            $totalUtilidad += $opData['total_utilidad_agencia'];
            $totalBoletos += $opData['cant_pasajes'];
            $totalSobrepesoKg += $opData['kg_sobrepeso'];
            $totalSobrepesoUsd += $opData['total_sobrepeso_usd'];
        }
        unset($opData);

        // Ordenar operadores: poner a Panamericana y Expresos Suramérica primero
        uksort($porOperador, function($a, $b) {
            if ($a === 'Panamericana') return -1;
            if ($b === 'Panamericana') return 1;
            if (strpos($a, 'Suramérica') !== false) return -1;
            if (strpos($b, 'Suramérica') !== false) return 1;
            return strcmp($a, $b);
        });

        return [
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipo_fecha' => (isset($filtros['tipo_fecha']) && $filtros['tipo_fecha'] === 'viaje') ? 'viaje' : 'venta',
                'operador' => $operadorFiltro,
                'modalidad' => $modalidadFiltro,
                'id_sucursal' => $sucursalFiltro ?: 'TODAS'
            ],
            'resumen' => [
                'total_recaudado' => $totalRecaudado,
                'total_liquidar' => $totalLiquidar,
                'total_utilidad' => $totalUtilidad,
                'total_boletos' => $totalBoletos,
                'total_sobrepeso_kg' => $totalSobrepesoKg,
                'total_sobrepeso_usd' => $totalSobrepesoUsd,
                'margen_promedio_pct' => ($totalRecaudado > 0) ? round(($totalUtilidad / $totalRecaudado) * 100, 1) : 0
            ],
            'por_operador' => $porOperador,
            'movimientos' => $movimientos
        ];
    }
}
?>