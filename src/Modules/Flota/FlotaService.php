<?php
namespace Modules\Flota;

use Platform\Validation\Validator;

class FlotaService {
    private $repository;

    public function __construct() {
        $this->repository = new FlotaRepository();
    }

    public function procesarGuardarRuta($postData) {
        $datos = $this->formatearDatosRuta($postData);
        $val = $this->validarCoherenciaRutaYTramos($datos);
        if ($val !== true) {
            return $val;
        }
        return $this->repository->agregarRutaBD($datos);
    }

    public function procesarEditarRuta($postData) {
        $datos = $this->formatearDatosRuta($postData);
        $val = $this->validarCoherenciaRutaYTramos($datos);
        if ($val !== true) {
            return $val;
        }
        return $this->repository->actualizarRutaBD($datos);
    }

    private function formatearDatosRuta($datos) {
        // Empaquetamos los días (ej: [1,3,5] -> "1,3,5")
        $diasSalida = isset($datos['dias']) ? implode(',', $datos['dias']) : '';
        
        // Empaquetamos los tramos para cualquier ruta (Directa de 1 tramo o Conexión de 2+ tramos)
        $tramos = [];
        if (isset($datos['tramo_origen']) && is_array($datos['tramo_origen'])) {
            foreach ($datos['tramo_origen'] as $i => $origen) {
                if (!empty($origen) && !empty($datos['tramo_destino'][$i])) {
                    $pvp = floatval($datos['tramo_pvp'][$i] ?? 0);
                    $costo = floatval($datos['tramo_costo'][$i] ?? 0);
                    $utilidad = isset($datos['tramo_utilidad'][$i]) && $datos['tramo_utilidad'][$i] !== '' 
                        ? floatval($datos['tramo_utilidad'][$i]) 
                        : max(0, $pvp - $costo);
                    $admiteSp = !empty($datos['tramo_admite_sp'][$i]) ? intval($datos['tramo_admite_sp'][$i]) : 0;
                    $limiteEq = floatval($datos['tramo_limite_eq'][$i] ?? 35);
                    $precioSp = floatval($datos['tramo_precio_sp'][$i] ?? 1);
                    $costoSp = floatval($datos['tramo_costo_sp'][$i] ?? 0);
                    $utilidadSp = floatval($datos['tramo_utilidad_sp'][$i] ?? max(0, $precioSp - $costoSp));

                    $idOperadorTramo = !empty($datos['tramo_id_operador'][$i]) ? intval($datos['tramo_id_operador'][$i]) : (!empty($datos['tramo_operador_id'][$i]) ? intval($datos['tramo_operador_id'][$i]) : 1);
                    $nomOperadorTramo = trim($datos['tramo_operador'][$i] ?? '');
                    if (empty($nomOperadorTramo)) {
                        $nomOperadorTramo = 'Propio Suramérica';
                    }

                    $desfase = isset($datos['tramo_dia_desfase'][$i]) ? intval($datos['tramo_dia_desfase'][$i]) : max(0, $i);
                    $idOferta = !empty($datos['tramo_id_oferta'][$i]) ? intval($datos['tramo_id_oferta'][$i]) : null;
                    $idTramoCat = !empty($datos['tramo_id_catalogo'][$i]) ? intval($datos['tramo_id_catalogo'][$i]) : (!empty($datos['tramo_id_tramo_catalogo'][$i]) ? intval($datos['tramo_id_tramo_catalogo'][$i]) : null);

                    $tramos[] = [
                        'orden' => $i + 1,
                        'dia_desfase' => $desfase,
                        'id_oferta' => $idOferta,
                        'id_tramo_catalogo' => $idTramoCat,
                        'id_operador' => $idOperadorTramo,
                        'origen' => trim($origen),
                        'destino' => trim($datos['tramo_destino'][$i]),
                        'operador' => $nomOperadorTramo,
                        'pvp' => $pvp,
                        'costo' => $costo,
                        'utilidad' => $utilidad,
                        'admite_sobrepeso' => $admiteSp,
                        'limite_equipaje' => $limiteEq,
                        'precio_sobrepeso' => $precioSp,
                        'costo_sobrepeso' => $costoSp,
                        'utilidad_sobrepeso' => $utilidadSp
                    ];
                }
            }
        }

        // Si no se enviaron tramos pero sí origen y destino, sintetizar el tramo único para viaje directo
        if (empty($tramos) && !empty($datos['origen']) && !empty($datos['destino'])) {
            $costoFallback = floatval($datos['costo_operador'] ?? $datos['costo_total_ruta'] ?? 0);
            $pvpFallback = floatval($datos['precio_pasaje'] ?? 0);
            $idOpFallback = !empty($datos['id_operador']) ? intval($datos['id_operador']) : 1;
            $nomOpFallback = trim($datos['operador_nombre'] ?? $datos['operador'] ?? 'Suramérica 2030');
            $admiteSpFallback = isset($datos['admite_sobrepeso']) ? intval($datos['admite_sobrepeso']) : 1;

            $tramos[] = [
                'id_operador' => $idOpFallback,
                'origen' => trim($datos['origen']),
                'destino' => trim($datos['destino']),
                'operador' => $nomOpFallback,
                'pvp' => $pvpFallback,
                'costo' => $costoFallback,
                'utilidad' => max(0, $pvpFallback - $costoFallback),
                'admite_sobrepeso' => $admiteSpFallback,
                'limite_equipaje' => floatval($datos['limite_equipaje'] ?? 35),
                'precio_sobrepeso' => floatval($datos['precio_sobrepeso'] ?? 1),
                'costo_sobrepeso' => 0,
                'utilidad_sobrepeso' => floatval($datos['precio_sobrepeso'] ?? 1)
            ];
        }

        $costoTotalRuta = array_sum(array_column($tramos, 'costo'));
        if ($costoTotalRuta <= 0 && !empty($datos['costo_total_ruta'])) {
            $costoTotalRuta = floatval($datos['costo_total_ruta']);
        }

        $tipoViaje = count($tramos) > 1 ? 'CONEXION' : 'DIRECTO';
        $precioPasaje = floatval($datos['precio_pasaje'] ?? 0);
        $tipoUtilidad = $datos['tipo_utilidad'] ?? 'PORCENTAJE';
        $utilidadPasaje = floatval($datos['utilidad_pasaje'] ?? 100);

        if (!empty($tramos)) {
            $sumaPvp = array_sum(array_column($tramos, 'pvp'));
            $sumaUtil = array_sum(array_column($tramos, 'utilidad'));
            if ($precioPasaje <= 0 && $sumaPvp > 0) {
                $precioPasaje = $sumaPvp;
            }
            if ($tipoUtilidad === 'FIJO') {
                $utilidadPasaje = round($sumaUtil > 0 ? $sumaUtil : max(0, $precioPasaje - $costoTotalRuta), 2);
            } else {
                $utilidadPasaje = ($precioPasaje > 0) ? round((max(0, $precioPasaje - $costoTotalRuta) / $precioPasaje) * 100, 2) : 0;
            }
        }

        $idOperador = !empty($datos['id_operador']) ? intval($datos['id_operador']) : (!empty($datos['operador_id']) ? intval($datos['operador_id']) : 1);
        $operadorNombre = trim($datos['operador_nombre'] ?? $datos['operador'] ?? $datos['operador_directo'] ?? '');

        // Si es 1 solo tramo, el operador de la ruta es el operador de dicho tramo
        if (count($tramos) === 1) {
            $idOperador = $tramos[0]['id_operador'];
            $operadorNombre = $tramos[0]['operador'];
        } elseif (count($tramos) > 1) {
            $tramosOpIds = array_unique(array_filter(array_column($tramos, 'id_operador')));
            if (in_array(2, $tramosOpIds)) {
                $idOperador = 2;
                $operadorNombre = 'Panamericana';
            } elseif (count($tramosOpIds) === 1) {
                $idOperador = reset($tramosOpIds);
                $operadorNombre = '';
            } else {
                $idOperador = 1;
                $operadorNombre = 'Suramérica 2030 (Flota Propia)';
            }
        }

        // Resolver Modalidad: Respeta selección explícita de las 4 modalidades o resuelve automáticamente
        $modalidadInput = trim($datos['modalidad'] ?? '');
        $modalidadNorm = \Platform\Helpers\ModalidadHelper::normalizarCodigo($modalidadInput);
        if (in_array($modalidadNorm, ['CONEXIONES', 'PANAMERICANA', 'EXPRESOS_SURAMERICA', 'PANA_EXPRESOS'])) {
            $modalidad = $modalidadNorm;
        } else {
            $modalidad = \Platform\Helpers\ModalidadHelper::resolverModalidadAutomatica($idOperador, $tipoViaje, $tramos, $operadorNombre);
        }

        $operador = !empty($operadorNombre) ? $operadorNombre : ($idOperador == 2 ? 'Panamericana' : 'Suramérica 2030');
        $admiteSp = isset($datos['admite_sobrepeso']) ? intval($datos['admite_sobrepeso']) : (!empty($tramos[0]['admite_sobrepeso']) ? 1 : 0);

        return [
            'id_ruta' => $datos['id_ruta'] ?? null,
            'sentido' => !empty($datos['sentido']) ? trim($datos['sentido']) : \Platform\Helpers\SentidoHelper::resolverSentido(trim($datos['origen'] ?? ''), trim($datos['destino'] ?? '')),
            'id_operador' => $idOperador,
            'origen' => trim($datos['origen']),
            'destino' => trim($datos['destino']),
            'tipo_viaje' => $tipoViaje,
            'modalidad' => $modalidad,
            'operador' => $operador,
            'admite_sobrepeso' => $admiteSp,
            'dias_salida' => $diasSalida,
            'hora_salida' => $datos['hora_salida'],
            'precio_pasaje' => $precioPasaje,
            'costo_total_ruta' => $costoTotalRuta,
            'precio_encomienda' => floatval($datos['precio_encomienda'] ?? 0),
            'precio_sobre' => floatval($datos['precio_sobre'] ?? 5),
            'precio_volumen' => floatval($datos['precio_volumen'] ?? 0),
            'limite_equipaje' => floatval($datos['limite_equipaje'] ?? 35),
            'precio_sobrepeso' => floatval($datos['precio_sobrepeso'] ?? 1),
            'comision_valor' => floatval($datos['comision_valor'] ?? 10),
            'tipo_utilidad' => $tipoUtilidad,
            'utilidad_pasaje' => $utilidadPasaje,
            'utilidad_encomienda' => floatval($datos['utilidad_encomienda'] ?? 100),
            'utilidad_valores' => floatval($datos['utilidad_valores'] ?? 100),
            'estado' => $datos['estado'] ?? 'ACTIVO',
            'tramos' => $tramos
        ];
    }

    // ==========================================
    // SECCIÓN OPERADORES / SOCIOS
    // ==========================================
    public function procesarGuardarOperador($postData) {
        $nombre = trim($postData['nombre_comercial'] ?? '');
        if (empty($nombre)) {
            return "El nombre comercial del operador es obligatorio.";
        }
        $correo = trim($postData['correo'] ?? '');
        if (!empty($correo) && !Validator::validarEmail($correo)) {
            return "El correo electrónico del operador no tiene un formato válido.";
        }
        $datos = [
            'nombre_comercial' => $nombre,
            'razon_social' => trim($postData['razon_social'] ?? $nombre),
            'tipo_operador' => $postData['tipo_operador'] ?? 'SOCIO_AFILIADO',
            'identificacion' => Validator::limpiarAlfanumerico($postData['identificacion'] ?? ''),
            'telefono' => Validator::limpiarTelefono($postData['telefono'] ?? ''),
            'correo' => $correo,
            'banco' => trim($postData['banco'] ?? ''),
            'tipo_cuenta' => trim($postData['tipo_cuenta'] ?? 'CORRIENTE'),
            'numero_cuenta' => trim($postData['numero_cuenta'] ?? ''),
            'titular_cuenta' => trim($postData['titular_cuenta'] ?? ''),
            'titular_doc' => Validator::limpiarAlfanumerico($postData['titular_doc'] ?? ''),
            'estado' => $postData['estado'] ?? 'ACTIVO',
            'observaciones' => trim($postData['observaciones'] ?? '')
        ];
        return $this->repository->guardarOperadorBD($datos);
    }

    public function procesarEditarOperador($postData) {
        $idOperador = intval($postData['id_operador'] ?? 0);
        if ($idOperador <= 0) {
            return "ID de operador inválido.";
        }
        $nombre = trim($postData['nombre_comercial'] ?? '');
        if (empty($nombre)) {
            return "El nombre comercial del operador es obligatorio.";
        }
        $correo = trim($postData['correo'] ?? '');
        if (!empty($correo) && !Validator::validarEmail($correo)) {
            return "El correo electrónico del operador no tiene un formato válido.";
        }
        $datos = [
            'id_operador' => $idOperador,
            'nombre_comercial' => $nombre,
            'razon_social' => trim($postData['razon_social'] ?? $nombre),
            'tipo_operador' => $postData['tipo_operador'] ?? 'SOCIO_AFILIADO',
            'identificacion' => Validator::limpiarAlfanumerico($postData['identificacion'] ?? ''),
            'telefono' => Validator::limpiarTelefono($postData['telefono'] ?? ''),
            'correo' => $correo,
            'banco' => trim($postData['banco'] ?? ''),
            'tipo_cuenta' => trim($postData['tipo_cuenta'] ?? 'CORRIENTE'),
            'numero_cuenta' => trim($postData['numero_cuenta'] ?? ''),
            'titular_cuenta' => trim($postData['titular_cuenta'] ?? ''),
            'titular_doc' => Validator::limpiarAlfanumerico($postData['titular_doc'] ?? ''),
            'estado' => $postData['estado'] ?? 'ACTIVO',
            'observaciones' => trim($postData['observaciones'] ?? '')
        ];
        return $this->repository->actualizarOperadorBD($datos);
    }

    public function procesarEliminarOperador($postData) {
        $idOperador = intval($postData['id_operador'] ?? 0);
        return $this->repository->eliminarOperadorBD($idOperador);
    }

    public function procesarGuardarBus($postData) {
        if (intval($postData['asientos']) <= 0) {
            return "El bus debe tener al menos 1 asiento.";
        }
        if (!empty($postData['placa'])) {
            $postData['placa'] = Validator::limpiarPlaca($postData['placa']);
        }
        return $this->repository->agregarBusYSocioBD($postData);
    }

    public function procesarEditarBus($postData) {
        if (!empty($postData['placa'])) {
            $postData['placa'] = Validator::limpiarPlaca($postData['placa']);
        }
        return $this->repository->actualizarBusYSocioBD($postData);
    }

    // ==========================================
    // SECCIÓN CORREDORES Y PARADAS
        public function procesarActualizarTarifaRapida($postData) {
        $idRuta = intval($postData['id_ruta']);
        $precioPasaje = floatval($postData['precio_pasaje'] ?? 0);
        $precioEncomienda = floatval($postData['precio_encomienda'] ?? 0);
        $estado = !empty($postData['estado']) ? trim($postData['estado']) : 'ACTIVO';

        return $this->repository->actualizarTarifaRapidaBD($idRuta, $precioPasaje, $precioEncomienda, $estado);
    }

    public function procesarGuardarCiudad($postData) {
        return $this->repository->guardarCiudadBD($postData);
    }

    public function procesarCambiarEstadoCiudad($postData) {
        $idCiudad = intval($postData['id_ciudad'] ?? 0);
        $estado = intval($postData['estado'] ?? 1);
        return $this->repository->cambiarEstadoCiudadBD($idCiudad, $estado);
    }

    public function procesarEliminarCiudad($postData) {
        $idCiudad = intval($postData['id_ciudad'] ?? 0);
        return $this->repository->eliminarCiudadBD($idCiudad);
    }

    public function procesarPrecargarCiudades() {
        return $this->repository->precargarCatalogoCompletoCiudades();
    }

    // ==========================================
    // SECCIÓN CATÁLOGO DE TRAMOS Y CREACIÓN RÁPIDA
    // ==========================================
    public function procesarGuardarTramoCatalogo($postData) {
        $costo = floatval($postData['costo_operador'] ?? 0);
        $pvp = floatval($postData['pvp_sugerido'] ?? 0);
        if ($pvp > 0 && $costo > $pvp) {
            return "El precio de venta sugerido ($" . number_format($pvp, 2) . ") no puede ser menor al costo del operador ($" . number_format($costo, 2) . ").";
        }
        return $this->repository->guardarTramoCatalogoBD($postData);
    }

    public function procesarCambiarEstadoTramoCatalogo($postData) {
        $idTramo = intval($postData['id_tramo_catalogo'] ?? 0);
        $estado = $postData['estado'] ?? 'ACTIVO';
        return $this->repository->cambiarEstadoTramoCatalogoBD($idTramo, $estado);
    }

    public function procesarEliminarTramoCatalogo($postData) {
        $idTramo = intval($postData['id_tramo_catalogo'] ?? 0);
        return $this->repository->eliminarTramoCatalogoBD($idTramo);
    }

    public function procesarDuplicarTramoInverso($postData) {
        $idTramo = is_array($postData) ? intval($postData['id_tramo_catalogo'] ?? 0) : intval($postData);
        if ($idTramo <= 0) {
            return "ID de tramo inválido.";
        }
        return $this->repository->duplicarTramoInversoBD($idTramo);
    }

    public function procesarSincronizarTarifasTramos($postData) {
        $idTramo = !empty($postData['id_tramo_catalogo']) ? intval($postData['id_tramo_catalogo']) : null;
        return $this->repository->sincronizarTarifasTramosEnRutas($idTramo);
    }

    public function procesarCrearRutaRapida($postData) {
        $origen = trim($postData['origen'] ?? '');
        $destino = trim($postData['destino'] ?? '');
        if (empty($origen) || empty($destino)) {
            return "Debe seleccionar la ciudad de origen y la ciudad de destino.";
        }
        if (mb_strtolower($origen) === mb_strtolower($destino)) {
            return "El origen y el destino no pueden ser la misma ciudad.";
        }

        $precioPasaje = floatval($postData['precio_pasaje'] ?? 0);
        $costoTotal = floatval($postData['costo_total_ruta'] ?? 0);
        if ($costoTotal > 0 && $precioPasaje < $costoTotal) {
            return "Bloqueo de seguridad: El precio de venta al público ($" . number_format($precioPasaje, 2) . ") no puede ser menor al costo total de liquidación a operadores ($" . number_format($costoTotal, 2) . ").";
        }

        if (!empty($postData['tramos_json'])) {
            $tramos = json_decode($postData['tramos_json'], true);
            if (is_array($tramos)) {
                $postData['tramos'] = $tramos;
            }
        }

        if (empty($postData['tramos']) && !empty($postData['tramo_origen']) && is_array($postData['tramo_origen'])) {
            $tramosConstruidos = [];
            foreach ($postData['tramo_origen'] as $i => $origTr) {
                $destTr = $postData['tramo_destino'][$i] ?? '';
                if (!empty($origTr) && !empty($destTr)) {
                    $tramosConstruidos[] = [
                        'origen' => trim($origTr),
                        'destino' => trim($destTr),
                        'costo' => floatval($postData['tramo_costo'][$i] ?? 0),
                        'pvp' => floatval($postData['tramo_pvp'][$i] ?? 0),
                        'id_operador' => intval($postData['tramo_id_operador'][$i] ?? 1),
                        'operador' => trim($postData['tramo_operador'][$i] ?? 'Propio'),
                        'admite_sobrepeso' => !empty($postData['tramo_admite_sp'][$i]) ? 1 : 0
                    ];
                }
            }
            if (!empty($tramosConstruidos)) {
                $postData['tramos'] = $tramosConstruidos;
                if ($costoTotal <= 0) {
                    $costoTotal = array_sum(array_column($tramosConstruidos, 'costo'));
                    $postData['costo_total_ruta'] = $costoTotal;
                }
            }
        }

        $val = $this->validarCoherenciaRutaYTramos($postData);
        if ($val !== true) {
            return $val;
        }

        return $this->repository->crearRutaRapidaBD($postData);
    }

    /**
     * Valida la coherencia estricta entre el origen y destino general de la ruta
     * y el primer y último tramo operativo configurado.
     *
     * @param array $datos
     * @return true|string Retorna true si es válido o un mensaje descriptivo de error.
     */
    public function validarCoherenciaRutaYTramos(array $datos) {
        $origenRuta = trim($datos['origen'] ?? '');
        $destinoRuta = trim($datos['destino'] ?? '');
        $tramos = $datos['tramos'] ?? [];

        if (empty($origenRuta)) {
            return "El origen de la ruta es obligatorio.";
        }
        if (empty($destinoRuta)) {
            return "El destino de la ruta es obligatorio.";
        }
        if (mb_strtoupper($origenRuta) === mb_strtoupper($destinoRuta)) {
            return "El origen y el destino de la ruta no pueden ser la misma ciudad ('{$origenRuta}').";
        }

        if (empty($tramos) || !is_array($tramos)) {
            return "La ruta comercial debe contar con al menos un tramo operativo.";
        }

        $primerTramo = $tramos[0];
        $ultimoTramo = $tramos[count($tramos) - 1];

        $origenPrimerTramo = trim($primerTramo['origen'] ?? $primerTramo['ORIGEN_TRAMO'] ?? '');
        $destinoUltimoTramo = trim($ultimoTramo['destino'] ?? $ultimoTramo['DESTINO_TRAMO'] ?? '');

        if (mb_strtoupper($origenPrimerTramo) !== mb_strtoupper($origenRuta)) {
            return "Inconsistencia de Origen: El primer tramo inicia en '{$origenPrimerTramo}', pero la ruta indica origen en '{$origenRuta}'. Ambos deben coincidir exactamente.";
        }

        if (mb_strtoupper($destinoUltimoTramo) !== mb_strtoupper($destinoRuta)) {
            return "Inconsistencia de Destino: El último tramo finaliza en '{$destinoUltimoTramo}', pero la ruta indica destino final en '{$destinoRuta}'. Ambos deben coincidir exactamente.";
        }

        // Validar continuidad entre tramos intermedios (permitiendo puentes fronterizos binacionales)
        $engine = new RoutingEngine();
        for ($i = 0; $i < count($tramos) - 1; $i++) {
            $destActual = trim($tramos[$i]['destino'] ?? $tramos[$i]['DESTINO_TRAMO'] ?? '');
            $origSiguiente = trim($tramos[$i + 1]['origen'] ?? $tramos[$i + 1]['ORIGEN_TRAMO'] ?? '');

            if (!$engine->sonCiudadesConectables($destActual, $origSiguiente)) {
                return "Inconsistencia de Conexión: El tramo " . ($i + 1) . " finaliza en '{$destActual}', pero el tramo " . ($i + 2) . " inicia en '{$origSiguiente}'. No coinciden ni cuentan con un paso fronterizo registrado.";
            }
        }

        return true;
    }

    public function sugerirConexionesInteligentes($origen, $destino) {
        $engine = new RoutingEngine();
        return $engine->sugerirCaminosDisponibles($origen, $destino);
    }

    public function procesarGenerarRutasMultimodales($postData) {
        $origen = trim($postData['origen'] ?? '');
        $destino = trim($postData['destino'] ?? '');
        if (empty($origen) || empty($destino)) {
            return "Debe seleccionar ciudad de origen y destino.";
        }
        if (mb_strtolower($origen) === mb_strtolower($destino)) {
            return "El origen y el destino no pueden ser la misma ciudad.";
        }

        $modalidades = $postData['modalidades'] ?? [];
        if (!is_array($modalidades)) {
            $modalidades = !empty($modalidades) ? [$modalidades] : [];
        }

        $opciones = [
            'crear_retorno' => !empty($postData['crear_retorno']),
            'dias_salida' => !empty($postData['dias_salida']) ? $postData['dias_salida'] : 'LUN,MIE,VIE',
            'hora_salida' => !empty($postData['hora_salida']) ? $postData['hora_salida'] : '08:00',
            'dias_retorno' => !empty($postData['dias_retorno']) ? $postData['dias_retorno'] : 'MAR,JUE,SAB',
            'hora_retorno' => !empty($postData['hora_retorno']) ? $postData['hora_retorno'] : '08:00'
        ];

        return $this->repository->generarLoteRutasMultimodales($origen, $destino, $modalidades, $opciones);
    }
}
?>