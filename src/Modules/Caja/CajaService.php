<?php
namespace Modules\Caja;

class CajaService {
    private $repo;

    public function __construct() {
        $this->repo = new CajaRepository();
    }

    public function obtenerListaSucursales() {
        return $this->repo->obtenerSucursales();
    }

    public function obtenerListaCajeros() {
        return $this->repo->obtenerCajeros();
    }

    // EL MOTOR MATEMÁTICO (SPLIT DE PAGOS Y UTILIDADES)
    private function calcularDesgloseFinanciero($movimientos) {
        $totales = [
            'ingresos' => 0, 'egresos' => 0, 'efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0,
            'saldo_neto' => 0, 'utilidad_bruta' => 0, 'capital_reposicion' => 0, 'comisiones' => 0,
            'egresos_gastos' => 0, 'utilidad_neta' => 0
        ];
        
        foreach ($movimientos as $mov) {
            $monto = floatval($mov['MONTO']);
            
            if ($mov['TIPO_MOVIMIENTO'] == 'INGRESO') {
                $totales['ingresos'] += $monto;
                if ($mov['METODO_PAGO'] == 'EFECTIVO') $totales['efectivo'] += $monto;
                if ($mov['METODO_PAGO'] == 'TRANSFERENCIA') $totales['transferencia'] += $monto;
                if ($mov['METODO_PAGO'] == 'TARJETA') $totales['tarjeta'] += $monto;
                
                if ($mov['ID_FACTURA']) {
                    $fa_total = floatval($mov['FA_TOTAL'] ?? 1); 
                    if ($fa_total <= 0) $fa_total = 1; // Prevenir división por 0

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
                    $totales['utilidad_bruta'] += $monto; // Ingresos manuales van 100% a la utilidad
                }
            } else { // EGRESO
                $totales['egresos'] += $monto;
                if ($mov['METODO_PAGO'] == 'EFECTIVO') $totales['efectivo'] -= $monto;
                
                if ($mov['ID_FACTURA']) { // Es una Devolución o anulación
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
                    $totales['egresos_gastos'] += $monto; // Egresos manuales (Gastos de oficina)
                }
            }
        }
        
        $totales['saldo_neto'] = $totales['ingresos'] - $totales['egresos'];
        $totales['utilidad_neta'] = $totales['utilidad_bruta'] - $totales['comisiones'] - $totales['egresos_gastos'];
        // Valor exigible neto a depositar al banco: Efectivo físico recaudado menos comisiones retenidas en origen
        $totales['valor_a_depositar'] = max(0, $totales['efectivo'] - $totales['comisiones']);
        
        return $totales;
    }

    public function procesarDatosCaja($sucursalId, $fechaConsulta) {
        $movimientos = $this->repo->obtenerMovimientosDelDia($sucursalId, $fechaConsulta);
        $sesionCaja = $this->repo->obtenerSesionCajaDia($sucursalId, $fechaConsulta);
        $cierreDia = ($sesionCaja && $sesionCaja['ESTADO'] === 'CERRADO') ? $sesionCaja : null;
        $aperturaDia = ($sesionCaja && $sesionCaja['ESTADO'] === 'ABIERTO') ? $sesionCaja : null;
        
        $totales = $this->calcularDesgloseFinanciero($movimientos);
        $valorInicial = floatval($sesionCaja['VALOR_INICIAL'] ?? 0);
        $totales['valor_inicial'] = $valorInicial;
        $totales['efectivo_con_fondo'] = $totales['efectivo'] + $valorInicial;

        $estadoCaja = 'SIN_ABRIR';
        if ($cierreDia) {
            $estadoCaja = 'CERRADO';
        } elseif ($aperturaDia) {
            $estadoCaja = 'ABIERTO';
        }

        return [
            'sucursales' => $this->repo->obtenerSucursales(),
            'movimientos' => $movimientos,
            'sesionCaja' => $sesionCaja,
            'cierreDia' => $cierreDia,
            'aperturaDia' => $aperturaDia,
            'estadoCaja' => $estadoCaja,
            'historialCierres' => $this->repo->obtenerHistorialCierresAvanzado([]),
            'totales' => $totales,
            'egresos_pendientes_count' => $this->repo->contarEgresosPendientesAprobacion($sucursalId, $fechaConsulta),
            'fecha_consulta' => $fechaConsulta,
            'sucursal_id' => $sucursalId
        ];
    }

    public function abrirCaja($datos, $usuarioId, $sucursalId) {
        return $this->repo->abrirCaja($datos, $usuarioId, $sucursalId);
    }

    /**
     * Calcula la sugerencia inteligente de fondo inicial basada en el último cierre de caja
     */
    public function obtenerSugerenciaApertura($idSucursal, $fechaApertura) {
        if ($idSucursal === 'ALL') {
            return [
                'existe_anterior' => false,
                'sugerido' => 50.00,
                'remanente' => 0.00,
                'base_anterior' => 0.00,
                'ultimo_cierre' => null
            ];
        }

        $ultimoCierre = $this->repo->obtenerUltimoCierreSucursal($idSucursal, $fechaApertura);
        if (!$ultimoCierre) {
            return [
                'existe_anterior' => false,
                'sugerido' => 50.00,
                'remanente' => 0.00,
                'base_anterior' => 0.00,
                'ultimo_cierre' => null
            ];
        }

        $efectivoCierre = floatval($ultimoCierre['EFECTIVO_FISICO'] ?? 0);
        if ($efectivoCierre <= 0) {
            $efectivoCierre = floatval($ultimoCierre['SALDO_FINAL'] ?? 0);
        }

        $deposito = floatval($ultimoCierre['DEPOSITO'] ?? 0);
        $baseAnterior = floatval($ultimoCierre['VALOR_INICIAL'] ?? 0);
        $comision = floatval($ultimoCierre['COMISION_EMPLEADOS'] ?? 0);
        $diferencia = floatval($ultimoCierre['DIFERENCIA_ARQUEO'] ?? 0);

        // Remanente que queda físicamente en la gaveta u oficina
        // Importante: Se descuenta la comisión del empleado/cajero porque ese dinero le pertenece a él y no es fondo de empresa
        $remanenteOficina = max(0, $efectivoCierre - $deposito - $comision);

        // Si hubo sobrante positivo en el arqueo del cierre anterior
        $sobranteReal = ($diferencia > 0) ? $diferencia : 0.00;
        $baseMasSobrante = $baseAnterior + $sobranteReal;

        // Sugerido:
        // Si quedó remanente físico (que equivale a la base o sobrante), se sugiere ese remanente.
        // Si no quedó nada (se depositó todo), se sugiere la base fija anterior (o 0.00 si no había base).
        $sugerido = ($remanenteOficina > 0) ? $remanenteOficina : ($baseAnterior > 0 ? $baseAnterior : 0.00);

        return [
            'existe_anterior' => true,
            'sugerido' => $sugerido,
            'remanente' => $remanenteOficina,
            'base_anterior' => $baseAnterior,
            'base_mas_sobrante' => $baseMasSobrante,
            'comision_cierre' => $comision,
            'sobrante_real' => $sobranteReal,
            'efectivo_cierre' => $efectivoCierre,
            'deposito_cierre' => $deposito,
            'diferencia_cierre' => $diferencia,
            'fecha_anterior' => $ultimoCierre['FECHA_CAJA'],
            'cajero_anterior' => $ultimoCierre['CAJERO_NOMBRE'] ?? 'Cajero',
            'sucursal_nombre' => $ultimoCierre['SUCURSAL_NOMBRE'] ?? 'Sucursal',
            'codigo_cierre' => $ultimoCierre['CODIGO_CIERRE'] ?? ('#'.$ultimoCierre['ID_CIERRE']),
            'ultimo_cierre' => $ultimoCierre
        ];
    }

    public function obtenerDetalleCierrePorId($idCierre) {
        return $this->repo->obtenerDetalleCierrePorId($idCierre);
    }

    public function contarEgresosPendientes($sucursalId, $fecha) {
        return $this->repo->contarEgresosPendientesAprobacion($sucursalId, $fecha);
    }

    public function registrarMovimientoManual($datos, $usuarioId, $sucursalId) {
        return $this->repo->registrarMovimientoManual($datos, $usuarioId, $sucursalId);
    }

    public function cerrarCaja($datos, $usuarioId, $sucursalId) {
        return $this->repo->registrarCierre($datos, $usuarioId, $sucursalId);
    }

    /**
     * Genera un desglose detallado por servicio y método de pago para el arqueo del cajero
     */
    public function obtenerDesglosePorServicios($movimientos, $valorInicial = 0) {
        $desglose = [
            'ingresos' => [
                'pasajes'   => ['nombre' => 'Pasajes y Boletos', 'icono' => '🎫', 'cantidad' => 0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'total' => 0.0],
                'sobrepeso' => ['nombre' => 'Cobro de Sobrepeso', 'icono' => '🧳', 'cantidad' => 0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'total' => 0.0],
                'encomiendas'=>['nombre' => 'Gestión de Encomiendas', 'icono' => '📦', 'cantidad' => 0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'total' => 0.0],
                'valores'   => ['nombre' => 'Envío de Valores', 'icono' => '💲', 'cantidad' => 0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'total' => 0.0],
                'abonos'    => ['nombre' => 'Abonos a Reservas', 'icono' => '💵', 'cantidad' => 0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'total' => 0.0],
                'manuales'  => ['nombre' => 'Ingresos Manuales / Fondo Inicial', 'icono' => '📥', 'cantidad' => 0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'total' => 0.0],
            ],
            'egresos' => [
                'gastos'       => ['nombre' => 'Egresos Manuales / Gastos de Oficina', 'icono' => '📤', 'cantidad' => 0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'total' => 0.0],
                'devoluciones' => ['nombre' => 'Devoluciones / Anulaciones de Ventas', 'icono' => '↩️', 'cantidad' => 0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'total' => 0.0],
            ],
            'metodos' => [
                'efectivo' => 0.0,
                'transferencia' => 0.0,
                'total_ingresos' => 0.0,
                'total_egresos' => 0.0,
                'efectivo_en_caja' => 0.0
            ]
        ];

        $facturasContadas = [];
        foreach ($movimientos as $mov) {
            $monto = floatval($mov['MONTO']);
            $metodo = strtoupper(trim($mov['METODO_PAGO'] ?? 'EFECTIVO'));
            $isEfectivo = ($metodo === 'EFECTIVO');
            $origen = $mov['ORIGEN_FACTURA'] ?? '';

            if ($mov['TIPO_MOVIMIENTO'] === 'INGRESO') {
                $desglose['metodos']['total_ingresos'] += $monto;
                if ($isEfectivo) {
                    $desglose['metodos']['efectivo'] += $monto;
                } else {
                    $desglose['metodos']['transferencia'] += $monto;
                }

                $key = 'manuales';
                $cant = 1;

                if (in_array($origen, ['PASAJE', 'RESERVA INICIAL'])) {
                    $key = 'pasajes';
                    $cant = max(1, intval($mov['FA_CANTIDAD'] ?? 1));
                } elseif ($origen === 'SOBREPESO') {
                    $key = 'sobrepeso';
                } elseif ($origen === 'ENCOMIENDA') {
                    $key = 'encomiendas';
                } elseif ($origen === 'VALOR') {
                    $key = 'valores';
                } elseif ($origen === 'ABONO PASAJE') {
                    $key = 'abonos';
                }

                $facturaId = !empty($mov['ID_FACTURA']) ? $mov['ID_FACTURA'] : null;
                if ($facturaId) {
                    if (!isset($facturasContadas[$key][$facturaId])) {
                        $desglose['ingresos'][$key]['cantidad'] += $cant;
                        $facturasContadas[$key][$facturaId] = true;
                    }
                } else {
                    $desglose['ingresos'][$key]['cantidad'] += $cant;
                }

                $desglose['ingresos'][$key]['total'] += $monto;
                if ($isEfectivo) {
                    $desglose['ingresos'][$key]['efectivo'] += $monto;
                } else {
                    $desglose['ingresos'][$key]['transferencia'] += $monto;
                }
            } else { // EGRESO
                $desglose['metodos']['total_egresos'] += $monto;

                $key = (!empty($mov['ID_FACTURA'])) ? 'devoluciones' : 'gastos';
                $facturaId = !empty($mov['ID_FACTURA']) ? $mov['ID_FACTURA'] : null;
                if ($facturaId) {
                    if (!isset($facturasContadas[$key][$facturaId])) {
                        $desglose['egresos'][$key]['cantidad'] += 1;
                        $facturasContadas[$key][$facturaId] = true;
                    }
                } else {
                    $desglose['egresos'][$key]['cantidad'] += 1;
                }
                $desglose['egresos'][$key]['total'] += $monto;
                if ($isEfectivo) {
                    $desglose['egresos'][$key]['efectivo'] += $monto;
                } else {
                    $desglose['egresos'][$key]['transferencia'] += $monto;
                }
            }
        }

        $desglose['metodos']['efectivo_en_caja'] = $desglose['metodos']['efectivo'] - ($desglose['egresos']['gastos']['efectivo'] + $desglose['egresos']['devoluciones']['efectivo']);
        $desglose['metodos']['fondo_inicial'] = floatval($valorInicial);
        $desglose['metodos']['efectivo_esperado_gaveta'] = $desglose['metodos']['efectivo_en_caja'] + floatval($valorInicial);

        return $desglose;
    }

    // --- FUNCIONES PUENTE PARA APROBACIONES Y DEPÓSITOS ---

    public function obtenerMovimientosParaAprobacion($filtros) { 
        return $this->repo->obtenerMovimientosAprobacion($filtros); 
    }

    public function actualizarEstadoAprobacion($id, $accion, $motivo) {
        $estado = ($accion == 'APROBAR') ? 'APROBADO' : 'RECHAZADO';
        return $this->repo->actualizarEstadoAprobacion($id, $estado, $motivo);
    }

    public function obtenerHistorialCierresAvanzado($filtros) { 
        return $this->repo->obtenerHistorialCierresAvanzado($filtros); 
    }

    public function actualizarDepositoCierre($id, $deposito) { 
        return $this->repo->actualizarDepositoCierre($id, $deposito); 
    }

    public function obtenerDepositosCaja($filtros) { 
        return $this->repo->obtenerHistorialCierresAvanzado($filtros); 
    }

    public function obtenerDepositosCajaConDetalle($filtros) {
        $cierres = $this->repo->obtenerHistorialCierresAvanzado($filtros);
        foreach ($cierres as &$c) {
            $c['detalles_depositos'] = $this->repo->obtenerDepositosDetallePorCierre($c['ID_CIERRE']);
        }
        return $cierres;
    }

    public function registrarDepositoDetalle($idCierre, $usuarioId, $banco, $numDocumento, $monto, $observaciones = '') {
        return $this->repo->registrarDepositoDetalle($idCierre, $usuarioId, $banco, $numDocumento, $monto, $observaciones);
    }

    public function obtenerDepositosDetalle($idCierre) {
        return $this->repo->obtenerDepositosDetallePorCierre($idCierre);
    }

    public function procesarDepositoBancario($id, $nro_doc, $fecha) { 
        return $this->repo->procesarDeposito($id, $nro_doc, $fecha); 
    }

    public function obtenerMovimientosCaja($filtros) {
        $filtros['estado'] = 'APROBADO'; // Los de caja general deben estar aprobados
        return $this->repo->obtenerMovimientosAprobacion($filtros);
    }

    public function registrarEgresoPendiente($usuario, $sucursal, $tipo, $concepto, $monto) {
        // Generar un código visual aleatorio estilo MANI_0000045
        $codigo = 'MANI_' . str_pad(rand(1, 999999), 7, '0', STR_PAD_LEFT);
        return $this->repo->registrarEgresoPendiente($usuario, $sucursal, $tipo, $concepto, $monto, $codigo);
    }

    public function moverFechaMovimientos($movimientosIds, $nuevaFecha, $usuarioId) {
        return $this->repo->moverFechaMovimientos($movimientosIds, $nuevaFecha, $usuarioId);
    }
}
?>