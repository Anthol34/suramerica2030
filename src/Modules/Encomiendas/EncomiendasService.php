<?php
namespace Modules\Encomiendas;

use Platform\Helpers\AuthHelper;
use Platform\Validation\Validator;

class EncomiendasService {
    private $repository;

    const ESTADO_RECIBIDO = 'Recibido';
    const ESTADO_TRANSITO = 'En Tránsito';
    const ESTADO_OFICINA = 'En Oficina';
    const ESTADO_ENTREGADO = 'Entregado';
    const ESTADO_ANULADO = 'Anulado';

    public function __construct() {
        $this->repository = new EncomiendasRepository();
    }

    public static function siguienteEstado($actual) {
        $mapa = [
            self::ESTADO_RECIBIDO => self::ESTADO_TRANSITO,
            self::ESTADO_TRANSITO => self::ESTADO_OFICINA,
            self::ESTADO_OFICINA => self::ESTADO_ENTREGADO,
        ];
        return isset($mapa[$actual]) ? $mapa[$actual] : null;
    }

    public static function transicionValida($desde, $hasta) {
        if ($hasta === self::ESTADO_ANULADO) {
            return $desde !== self::ESTADO_ANULADO && $desde !== self::ESTADO_ENTREGADO;
        }
        return self::siguienteEstado($desde) === $hasta;
    }

    public static function ambitoPorDefecto() {
        return AuthHelper::esAdmin() ? 'todas' : 'operacion';
    }

    public static function normalizarAmbito($ambito) {
        $permitidos = ['todas', 'operacion', 'origen', 'destino'];
        $valor = strtolower(trim((string)$ambito));
        if (!in_array($valor, $permitidos, true)) {
            $valor = self::ambitoPorDefecto();
        }
        if (!AuthHelper::esAdmin() && $valor === 'todas') {
            return 'operacion';
        }
        return $valor;
    }

    public function obtenerDatosIniciales($filtros, $pagina) {
        $limite = 10;
        $offset = ($pagina - 1) * $limite;
        $totalRegistros = $this->repository->contarTodas($filtros);

        $datos = [
            'rutas' => $this->repository->obtenerRutas(),
            'paquetes' => $this->repository->obtenerTodas($filtros, $offset, $limite),
            'totalPaginas' => ceil($totalRegistros / $limite),
            'paginaActual' => $pagina,
            'pagina' => $pagina,
            'totalRegistros' => $totalRegistros,
            'totalEnTransito' => $this->repository->contarEncomiendasEnTransito(null, $filtros),
            'directorioCorreos' => $this->repository->obtenerDirectorioCorreos(),
            'kpis' => $this->repository->obtenerKpisEncomiendas($filtros)
        ];

        if (isset($filtros['tab']) && $filtros['tab'] == 'carga') {
            $inicio = $filtros['fecha_inicio'] ?? date('Y-m-01');
            $fin = $filtros['fecha_fin'] ?? date('Y-m-t');
            $datos['despachos'] = $this->repository->obtenerDespachosProgramados($inicio, $fin);
        }
        return $datos;
    }

    public function procesarGuardar($postData, $usuarioId, $sucursalId) {
        $modoMayorista = !empty($postData['modo_mayorista']);
        $pesoGeneralManual = !empty($postData['peso_general_manual']);

        if ($modoMayorista) {
            $cantidadFinal = intval($postData['cantidad_lote'] ?? 1);
            if ($cantidadFinal <= 0) $cantidadFinal = 1;
            $tipoFinal = !empty($postData['tipo_lote']) ? trim($postData['tipo_lote']) : 'Caja';
            $pesoFinal = floatval($postData['peso'] ?? 0);
            $contBase = trim($postData['contenido_lote'] ?? 'Carga Mayorista');
            $pesosDetalle = trim($postData['pesos_lote_str'] ?? '');
            $esPesoGeneralLote = !empty($postData['es_peso_general_lote']);

            if ($esPesoGeneralLote) {
                $prom = $cantidadFinal > 0 ? number_format($pesoFinal / $cantidadFinal, 2) : 0;
                $contenidoFinal = "Lote Mayorista ($cantidadFinal $tipoFinal" . ($cantidadFinal > 1 ? "s" : "") . "): $contBase [Peso General del Lote: {$pesoFinal}Kg (~{$prom}Kg c/u)]";
            } elseif (!empty($pesosDetalle)) {
                $contenidoFinal = "Lote Mayorista ($cantidadFinal $tipoFinal" . ($cantidadFinal > 1 ? "s" : "") . "): $contBase | Pesos(Kg): $pesosDetalle";
            } else {
                $contenidoFinal = "Lote Mayorista ($cantidadFinal $tipoFinal" . ($cantidadFinal > 1 ? "s" : "") . "): $contBase [Peso Total: {$pesoFinal}Kg]";
            }
        } else {
            $cantidades = $postData['cantidad'] ?? [1];
            $tipos = $postData['tipo_encomienda'] ?? ['Caja'];
            $pesos_piezas = $postData['peso_pieza'] ?? [0];
            $contenidos = $postData['contenido'] ?? ['General'];
            $modalidades_peso = $postData['modalidad_peso'] ?? [];

            $totalPiezas = 0;
            $pesoCalculado = 0;
            $tiposUnicos = [];
            $detallesContenido = [];

            if (is_array($cantidades)) {
                for($i = 0; $i < count($cantidades); $i++) {
                    $c = intval($cantidades[$i]); if($c <= 0) $c = 1;
                    $t = $tipos[$i] ?? 'Caja';
                    $p = floatval($pesos_piezas[$i] ?? 0);
                    $cont = $contenidos[$i] ?? '';
                    $modo = $modalidades_peso[$i] ?? 'unitario';

                    $totalPiezas += $c;
                    if ($t !== 'Sobre') {
                        if ($modo === 'total') {
                            $pesoCalculado += $p;
                            $prom = $c > 0 ? number_format($p / $c, 2) : $p;
                            $detallesContenido[] = "$c $t(s) [Peso Total Grupo: {$p}Kg (~{$prom}Kg c/u)]: $cont";
                        } else {
                            $pesoCalculado += ($c * $p);
                            $detallesContenido[] = "$c $t(s) [" . $p . "Kg c/u]: $cont";
                        }
                    } else {
                        $detallesContenido[] = "$c $t(s) [Fijo]: $cont";
                    }
                    if (!in_array($t, $tiposUnicos)) $tiposUnicos[] = $t;
                }
                $cantidadFinal = $totalPiezas;
                $tipoFinal = count($tiposUnicos) > 1 ? 'MIXTO' : ($tiposUnicos[0] ?? 'Caja');

                if ($pesoGeneralManual) {
                    $pesoFinal = floatval($postData['peso'] ?? 0);
                    $prom = $cantidadFinal > 0 ? number_format($pesoFinal / $cantidadFinal, 2) : 0;
                    $contenidoFinal = implode(" | ", $detallesContenido) . " [Peso General Global: {$pesoFinal}Kg (~{$prom}Kg c/u)]";
                } else {
                    $pesoFinal = $pesoCalculado;
                    $contenidoFinal = implode(" | ", $detallesContenido);
                }
            } else {
                $cantidadFinal = intval($postData['cantidad'] ?? 1);
                $tipoFinal = $postData['tipo_encomienda'] ?? 'Caja';
                $contenidoFinal = $postData['contenido'] ?? 'General';
                $pesoFinal = floatval($postData['peso'] ?? 0);
            }
        }

        $rutaId = intval($postData['ruta_id'] ?? 0);
        if ($rutaId <= 0) {
            return "Debe seleccionar una ruta de envío.";
        }
        if (empty($postData['cliente_id']) || empty($postData['destinatario_ci'])) {
            return "Remitente y destinatario son obligatorios.";
        }

        $tarifa = $this->calcularTarifa($postData, $pesoFinal);
        if (is_string($tarifa)) {
            return $tarifa;
        }

        $puedeEditar = AuthHelper::puedeEditarPrecios();
        $montoCliente = round(floatval($postData['monto_total'] ?? 0), 2);
        $tarifaCalculada = $tarifa['total'];

        if ($puedeEditar) {
            if ($montoCliente <= 0) {
                return "El total a cobrar debe ser mayor a 0.";
            }
            $totalCobrado = $montoCliente;
        } else {
            $totalCobrado = $tarifaCalculada;
        }

        $metodoPago = strtoupper(trim($postData['metodo_pago'] ?? 'EFECTIVO'));
        $permitidosPago = ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'MIXTO'];
        if (!in_array($metodoPago, $permitidosPago, true)) {
            $metodoPago = 'EFECTIVO';
        }

        $montoEfectivo = floatval($postData['monto_efectivo'] ?? 0);
        $montoTransferencia = floatval($postData['monto_transferencia'] ?? 0);

        if ($metodoPago === 'MIXTO') {
            $valMixto = Validator::pagoMixto($totalCobrado, $montoEfectivo, $montoTransferencia);
            if (!$valMixto['valido']) {
                return $valMixto['mensaje'];
            }
        } elseif ($metodoPago === 'EFECTIVO') {
            $montoEfectivo = $totalCobrado;
            $montoTransferencia = 0.0;
        } else {
            $montoTransferencia = $totalCobrado;
            $montoEfectivo = 0.0;
        }

        $datosLimpios = [
            'cliente_id' => Validator::limpiarAlfanumerico($postData['cliente_id']),
            'cliente_nombre' => trim($postData['cliente_nombre']),
            'cliente_apellido' => trim($postData['cliente_apellido']),
            'cliente_telefono' => Validator::limpiarTelefono($postData['cliente_telefono'] ?? ''),

            'destinatario_ci' => Validator::limpiarAlfanumerico($postData['destinatario_ci']),
            'destinatario_nombre' => trim($postData['destinatario_nombre']),
            'destinatario_apellido' => trim($postData['destinatario_apellido']),
            'destinatario_telefono' => Validator::limpiarTelefono($postData['destinatario_telefono'] ?? ''),

            'ruta_id' => $rutaId,
            'destino_final' => !empty($postData['destino_final']) ? mb_strtoupper(trim($postData['destino_final'])) : null,
            'metodo_pago' => $metodoPago,
            'monto_efectivo' => $montoEfectivo,
            'monto_transferencia' => $montoTransferencia,
            'tarifa_calculada' => $tarifaCalculada,
            'total_cobrado' => $totalCobrado,
            'volumen' => floatval($postData['volumen'] ?? 0),

            'cantidad_final' => $cantidadFinal,
            'peso_final' => $pesoFinal,
            'tipo_final' => $tipoFinal,
            'contenido_final' => $contenidoFinal
        ];

        return $this->repository->registrarEncomiendaBD($datosLimpios, $usuarioId, $sucursalId);
    }

    public function calcularTarifa($postData, $pesoFinal = null) {
        $rutaId = intval($postData['ruta_id'] ?? 0);
        $ruta = $this->repository->obtenerTarifaRuta($rutaId);
        if (!$ruta) {
            return "La ruta seleccionada no existe o no está activa.";
        }

        $precioKg = floatval($ruta['PRECIO_ENCOMIENDA_KG'] ?? 0);
        $precioSobre = floatval($ruta['PRECIO_SOBRE'] ?? 0);
        $precioVol = floatval($ruta['PRECIO_VOLUMEN'] ?? 0);

        $modoMayorista = !empty($postData['modo_mayorista']);
        $pesoGeneralManual = !empty($postData['peso_general_manual']);
        $volumen = floatval($postData['volumen'] ?? 0);

        $totalPesoCalc = 0.0;
        $totalSobres = 0.0;
        $soloSobres = true;

        if ($pesoGeneralManual) {
            $soloSobres = false;
            $totalPesoCalc = $pesoFinal !== null ? floatval($pesoFinal) : floatval($postData['peso'] ?? 0);
        } elseif ($modoMayorista) {
            $soloSobres = false;
            $totalPesoCalc = $pesoFinal !== null ? floatval($pesoFinal) : floatval($postData['peso'] ?? 0);
        } else {
            $cantidades = $postData['cantidad'] ?? [1];
            $tipos = $postData['tipo_encomienda'] ?? ['Caja'];
            $pesosPiezas = $postData['peso_pieza'] ?? [0];
            $modalidadesPeso = $postData['modalidad_peso'] ?? [];

            if (!is_array($cantidades)) {
                $cantidades = [$cantidades];
                $tipos = [$tipos];
                $pesosPiezas = [$postData['peso'] ?? 0];
                $modalidadesPeso = ['unitario'];
            }

            for ($i = 0; $i < count($cantidades); $i++) {
                $c = intval($cantidades[$i]);
                if ($c <= 0) $c = 1;
                $t = $tipos[$i] ?? 'Caja';
                $p = floatval($pesosPiezas[$i] ?? 0);
                $modo = $modalidadesPeso[$i] ?? 'unitario';

                if ($t === 'Sobre') {
                    $totalSobres += ($c * $precioSobre);
                } else {
                    $soloSobres = false;
                    if ($modo === 'total') {
                        $totalPesoCalc += $p;
                    } else {
                        $totalPesoCalc += ($c * $p);
                    }
                }
            }

            if ($pesoFinal !== null && !$pesoGeneralManual) {
                $totalPesoCalc = floatval($pesoFinal);
            }
        }

        $costoBaseTotal = max($totalPesoCalc * $precioKg, $volumen * $precioVol);
        $total = ($soloSobres && !$modoMayorista && !$pesoGeneralManual)
            ? $totalSobres
            : ($costoBaseTotal + $totalSobres);

        return [
            'total' => round($total, 2),
            'peso' => $totalPesoCalc,
            'precio_kg' => $precioKg,
            'precio_sobre' => $precioSobre,
            'precio_volumen' => $precioVol
        ];
    }

    public function cambiarEstado($idEncomienda, $estadoDestino, $usuarioId, $sucursalId) {
        $idEncomienda = (int)$idEncomienda;
        $estadoDestino = trim($estadoDestino);
        if ($idEncomienda <= 0 || $estadoDestino === '') {
            return 'Datos incompletos para actualizar el estado.';
        }

        $fila = $this->repository->obtenerEncomiendaPorId($idEncomienda);
        if (!$fila) {
            return 'Encomienda no encontrada.';
        }

        $actual = $fila['ENC_ESTADO'];
        if (!self::transicionValida($actual, $estadoDestino)) {
            $esperado = self::siguienteEstado($actual);
            return $esperado
                ? "Transición inválida. Desde '{$actual}' el siguiente estado es '{$esperado}'."
                : "La encomienda en estado '{$actual}' no admite este cambio.";
        }

        $permiso = $this->permisoParaEstado($estadoDestino);
        if ($permiso !== true) {
            return $permiso;
        }

        return $this->repository->actualizarEstadoBD($idEncomienda, $estadoDestino, $usuarioId, $sucursalId, $actual);
    }

    public function despacharLote($ids, $fechaDespacho, $usuarioId, $sucursalId) {
        if (!$this->tienePermisoDespacho()) {
            return 'Acceso denegado: Tu rol no tiene permisos para despachar encomiendas.';
        }
        if (empty($ids) || !is_array($ids)) {
            return 'Selecciona al menos una encomienda en bodega (Recibido).';
        }
        if (empty($fechaDespacho)) {
            return 'La fecha de despacho es obligatoria.';
        }
        return $this->repository->despacharEncomiendasBD($ids, $fechaDespacho, $usuarioId, $sucursalId);
    }

    public function llegadaLote($ids, $usuarioId, $sucursalId) {
        if (!$this->tienePermisoLlegada()) {
            return 'Acceso denegado: Tu rol no tiene permisos para registrar llegada a oficina.';
        }
        if (empty($ids) || !is_array($ids)) {
            return 'Selecciona al menos una encomienda en tránsito.';
        }
        return $this->repository->registrarLlegadaOficinaBD($ids, $usuarioId, $sucursalId);
    }

    public function entregarLote($ids, $usuarioId, $sucursalId) {
        if (!$this->tienePermisoLlegada()) {
            return 'Acceso denegado: Tu rol no tiene permisos para registrar entrega.';
        }
        if (empty($ids) || !is_array($ids)) {
            return 'Selecciona al menos una encomienda en oficina.';
        }
        return $this->repository->entregarEncomiendasBD($ids, $usuarioId, $sucursalId);
    }

    public function tienePermisoDespacho() {
        return AuthHelper::esAdmin() || !empty($_SESSION['p_despacho']);
    }

    public function tienePermisoLlegada() {
        return AuthHelper::esAdmin() || !empty($_SESSION['p_llegada']);
    }

    private function permisoParaEstado($estadoDestino) {
        if ($estadoDestino === self::ESTADO_TRANSITO && !$this->tienePermisoDespacho()) {
            return 'Acceso denegado: Tu rol no tiene permisos para despachar encomiendas.';
        }
        if (($estadoDestino === self::ESTADO_ENTREGADO || $estadoDestino === self::ESTADO_OFICINA) && !$this->tienePermisoLlegada()) {
            return 'Acceso denegado: Tu rol no tiene permisos para registrar llegada o entrega de encomiendas.';
        }
        return true;
    }
}
?>
