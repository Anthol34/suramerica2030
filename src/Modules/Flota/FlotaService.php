<?php
namespace Modules\Flota;

use Platform\Validation\Validator;

class FlotaService {
    private $repository;

    public function __construct() {
        $this->repository = new FlotaRepository();
    }

    public function procesarGuardarRuta($postData) {
        return $this->repository->agregarRutaBD($this->formatearDatosRuta($postData));
    }

    public function procesarEditarRuta($postData) {
        return $this->repository->actualizarRutaBD($this->formatearDatosRuta($postData));
    }

    private function formatearDatosRuta($datos) {
        // Empaquetamos los días (ej: [1,3,5] -> "1,3,5")
        $diasSalida = isset($datos['dias']) ? implode(',', $datos['dias']) : '';
        
        // Empaquetamos los tramos si es un viaje de conexión
        $tramos = [];
        if (($datos['tipo_viaje'] ?? 'DIRECTO') === 'CONEXION' && isset($datos['tramo_origen'])) {
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

                    $tramos[] = [
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

        $tipoViaje = $datos['tipo_viaje'] ?? 'DIRECTO';
        $precioPasaje = floatval($datos['precio_pasaje'] ?? 0);
        $tipoUtilidad = $datos['tipo_utilidad'] ?? 'PORCENTAJE';
        $utilidadPasaje = floatval($datos['utilidad_pasaje'] ?? 100);

        // Si es ruta con conexión, el precio y la utilidad de pasajes se derivan estrictamente de los tramos
        if ($tipoViaje === 'CONEXION' && !empty($tramos)) {
            $sumaPvp = array_sum(array_column($tramos, 'pvp'));
            $sumaUtil = array_sum(array_column($tramos, 'utilidad'));
            if ($sumaPvp > 0) {
                $precioPasaje = $sumaPvp;
                if ($tipoUtilidad === 'FIJO') {
                    $utilidadPasaje = round($sumaUtil, 2);
                } else {
                    $utilidadPasaje = round(($sumaUtil / $sumaPvp) * 100, 2);
                }
            }
        } elseif ($precioPasaje <= 0 && !empty($tramos)) {
            $sumaPvp = array_sum(array_column($tramos, 'pvp'));
            if ($sumaPvp > 0) {
                $precioPasaje = $sumaPvp;
            }
        }

        $idOperador = !empty($datos['id_operador']) ? intval($datos['id_operador']) : (!empty($datos['operador_id']) ? intval($datos['operador_id']) : 1);
        $operadorNombre = trim($datos['operador_nombre'] ?? $datos['operador'] ?? $datos['operador_directo'] ?? '');

        // Resolver Modalidad: Respeta selección explícita/personalizada o resuelve automáticamente por reglas
        $modalidadInput = trim($datos['modalidad'] ?? '');
        if ($modalidadInput === 'OTRO' && !empty($datos['modalidad_personalizada'])) {
            $modalidad = trim($datos['modalidad_personalizada']);
        } elseif (!empty($modalidadInput) && $modalidadInput !== 'AUTO') {
            $modalidad = $modalidadInput;
        } else {
            $modalidad = \Platform\Helpers\ModalidadHelper::resolverModalidadAutomatica($idOperador, $tipoViaje, $tramos, $operadorNombre);
        }

        // Si es conexión sin operador explícito, asignar según modalidad
        if ($tipoViaje === 'CONEXION' && empty($datos['id_operador'])) {
            $idOperador = ($modalidad === 'PANAMERICANA') ? 2 : 1;
        }

        $operador = !empty($operadorNombre) ? $operadorNombre : ($idOperador == 2 ? 'Panamericana' : 'Suramérica 2030');
        $admiteSp = isset($datos['admite_sobrepeso']) ? intval($datos['admite_sobrepeso']) : 1;

        return [
            'id_ruta' => $datos['id_ruta'] ?? null,
            'id_corredor' => !empty($datos['id_corredor']) ? intval($datos['id_corredor']) : null,
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
    // ==========================================
    public function procesarCrearCorredor($postData) {
        $diasSalida = isset($postData['dias']) ? implode(',', $postData['dias']) : '2,5';
        $origen = trim($postData['origen_cabecera'] ?? '');
        $destino = trim($postData['destino_terminal'] ?? '');

        if (empty($origen) || empty($destino)) {
            return "Debe especificar la ciudad de origen y la ciudad de destino.";
        }

        $datos = [
            'codigo' => trim($postData['codigo'] ?? 'COR-' . rand(10, 99)),
            'nombre' => trim($postData['nombre'] ?? "$origen - $destino"),
            'origen_cabecera' => $origen,
            'destino_terminal' => $destino,
            'pais_origen' => trim($postData['pais_origen'] ?? 'ECUADOR'),
            'pais_destino' => trim($postData['pais_destino'] ?? 'VENEZUELA'),
            'dias_salida' => $diasSalida,
            'hora_salida' => $postData['hora_salida'] ?? '10:00:00',
            'estado' => $postData['estado'] ?? 'ACTIVO',
            'limite_equipaje' => floatval($postData['limite_equipaje'] ?? 35),
            'precio_sobrepeso' => floatval($postData['precio_sobrepeso'] ?? 1),
            'precio_sobre' => floatval($postData['precio_sobre'] ?? 5),
            'precio_volumen' => floatval($postData['precio_volumen'] ?? 0),
            'comision_valor' => floatval($postData['comision_valor'] ?? 10),
            'tipo_utilidad' => $postData['tipo_utilidad'] ?? 'PORCENTAJE',
            'utilidad_pasaje' => floatval($postData['utilidad_pasaje'] ?? 100),
            'utilidad_encomienda' => floatval($postData['utilidad_encomienda'] ?? 100),
            'utilidad_valores' => floatval($postData['utilidad_valores'] ?? 100)
        ];
        return $this->repository->crearCorredorBD($datos);
    }

    public function procesarEliminarCorredor($postData) {
        $idCorredor = intval($postData['id_corredor']);
        return $this->repository->eliminarCorredorBD($idCorredor);
    }

    public function procesarActualizarCorredor($postData) {
        $diasSalida = isset($postData['dias']) ? implode(',', $postData['dias']) : '';
        $datos = [
            'id_corredor' => intval($postData['id_corredor']),
            'codigo' => trim($postData['codigo'] ?? 'COR-01'),
            'nombre' => trim($postData['nombre'] ?? 'Corredor Internacional'),
            'origen_cabecera' => trim($postData['origen_cabecera']),
            'destino_terminal' => trim($postData['destino_terminal']),
            'dias_salida' => $diasSalida,
            'hora_salida' => $postData['hora_salida'] ?? '10:00:00',
            'estado' => $postData['estado'] ?? 'ACTIVO',
            'limite_equipaje' => floatval($postData['limite_equipaje'] ?? 35),
            'precio_sobrepeso' => floatval($postData['precio_sobrepeso'] ?? 1),
            'precio_sobre' => floatval($postData['precio_sobre'] ?? 5),
            'precio_volumen' => floatval($postData['precio_volumen'] ?? 0),
            'comision_valor' => floatval($postData['comision_valor'] ?? 10),
            'tipo_utilidad' => $postData['tipo_utilidad'] ?? 'PORCENTAJE',
            'utilidad_pasaje' => floatval($postData['utilidad_pasaje'] ?? 100),
            'utilidad_encomienda' => floatval($postData['utilidad_encomienda'] ?? 100),
            'utilidad_valores' => floatval($postData['utilidad_valores'] ?? 100)
        ];
        return $this->repository->actualizarCorredorBD($datos);
    }

    public function procesarAgregarParada($postData) {
        $idCorredor = intval($postData['id_corredor']);
        $ciudad = trim($postData['ciudad'] ?? '');
        $pais = trim($postData['pais'] ?? 'ECUADOR');
        $tipo = trim($postData['tipo_parada'] ?? 'ESCALA');
        $tiempo = intval($postData['tiempo_estimado_horas'] ?? 0);
        $posicion = (isset($postData['posicion_insercion']) && $postData['posicion_insercion'] !== 'FINAL') ? intval($postData['posicion_insercion']) : null;

        if (empty($ciudad)) {
            return "Debe especificar el nombre de la ciudad o parada.";
        }

        return $this->repository->agregarParadaBD($idCorredor, $ciudad, $pais, $tipo, $tiempo, $posicion);
    }

    public function procesarMoverParada($postData) {
        $idParada = intval($postData['id_parada'] ?? 0);
        $direccion = trim($postData['direccion'] ?? 'subir');
        return $this->repository->moverParadaBD($idParada, $direccion);
    }

    public function procesarEliminarParada($postData) {
        $idParada = intval($postData['id_parada']);
        return $this->repository->eliminarParadaBD($idParada);
    }

    public function procesarActualizarBusTramo($postData) {
        $idTramo = intval($postData['id_tramo']);
        $idBus = !empty($postData['id_bus']) ? intval($postData['id_bus']) : null;
        return $this->repository->actualizarBusTramoOperativoBD($idTramo, $idBus);
    }

    public function procesarGenerarRutasCorredor($postData) {
        $idCorredor = intval($postData['id_corredor']);
        return $this->repository->generarRutasComercialesDelCorredor($idCorredor);
    }

    public function procesarActualizarTarifaRapida($postData) {
        $idRuta = intval($postData['id_ruta']);
        $precioPasaje = floatval($postData['precio_pasaje'] ?? 0);
        $precioEncomienda = floatval($postData['precio_encomienda'] ?? 0);
        $estado = !empty($postData['estado']) ? trim($postData['estado']) : 'ACTIVO';

        return $this->repository->actualizarTarifaRapidaBD($idRuta, $precioPasaje, $precioEncomienda, $estado);
    }
}
?>