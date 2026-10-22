<?php
namespace Modules\Pasajes;

class PasajesService {
    private $repository;

    public function __construct() {
        $this->repository = new PasajesRepository();
    }

    public function obtenerDatosIniciales($filtros, $pagina = 1) {
        $tabActual = $filtros['tab'] ?? 'activos';
        $limite = ($tabActual === 'equipaje') ? 30 : 10;
        $offset = ($pagina - 1) * $limite;
        
        $conteoPendientes = $this->repository->contarSolicitudesPendientes();
        $solicitudes = [];
        if ($tabActual === 'solicitudes') {
            $solicitudes = $this->repository->obtenerSolicitudesAnulacion($filtros);
        }

        $equipajesCentral = [];
        $kpisEquipaje = [];
        $totalEquipajes = 0;

        if ($tabActual === 'equipaje') {
            $totalRegistros = $this->repository->contarEquipajesCentral($filtros);
            $equipajesCentral = $this->repository->obtenerEquipajesCentral($filtros, $offset, $limite);
            $kpisEquipaje = $this->repository->obtenerTotalesKpisEquipajesCentral($filtros['fecha'] ?? null);
            $pasajes = [];
        } else {
            $totalRegistros = $this->repository->contarTodos($filtros);
            $pasajes = $this->repository->obtenerTodos($filtros, $offset, $limite);
        }
        
        return [
            'rutas' => $this->repository->obtenerRutas(),
            'buses' => $this->repository->obtenerBuses(),
            'pasajes' => $pasajes,
            'equipajesCentral' => $equipajesCentral,
            'kpisEquipaje' => $kpisEquipaje,
            'kpisPasajes' => $this->repository->obtenerKpisPasajes(),
            'totalPaginas' => max(1, ceil($totalRegistros / $limite)),
            'paginaActual' => $pagina,
            'pagina' => $pagina,
            'totalRegistros' => $totalRegistros,
            'filtros' => $filtros,
            'conteoPendientes' => $conteoPendientes,
            'solicitudes' => $solicitudes
        ];
    }

    public function procesarVenta($postData, $usuarioId, $sucursalId) {
        if (empty($postData['ruta_id']) || empty($postData['cliente_id'])) {
            return ['status' => 'error', 'mensaje' => 'Datos incompletos para procesar la venta.'];
        }

        $cantidadesId = $postData['cliente_id'];
        $cantidadTotal = is_array($cantidadesId) ? count($cantidadesId) : 1;
        
        $precioTotalGlobal = floatval($postData['precio_total']);
        $montoAbonadoGlobal = floatval($postData['monto_abonar']);
        
        // Si el usuario cobró o abonó más que el precio total sugerido (ej: tarifa desactualizada en ruta)
        if ($montoAbonadoGlobal > $precioTotalGlobal) {
            $precioTotalGlobal = $montoAbonadoGlobal;
        }
        $saldoGlobal = max(0, $precioTotalGlobal - $montoAbonadoGlobal);

        $precioPorPasaje = $precioTotalGlobal / $cantidadTotal;
        $abonoPorPasaje = $montoAbonadoGlobal / $cantidadTotal;
        $saldoPorPasaje = max(0, $precioPorPasaje - $abonoPorPasaje);
        $estadoPorPasaje = ($saldoPorPasaje > 0) ? 'RESERVA' : 'PAGADO';

        $pasajeros = [];
        for ($i = 0; $i < $cantidadTotal; $i++) {
            $rawId = is_array($cantidadesId) ? ($cantidadesId[$i] ?? '') : $postData['cliente_id'];
            $rawNom = is_array($postData['cliente_nombre']) ? ($postData['cliente_nombre'][$i] ?? '') : $postData['cliente_nombre'];
            $rawApe = is_array($postData['cliente_apellido']) ? ($postData['cliente_apellido'][$i] ?? '') : $postData['cliente_apellido'];
            $rawTel = is_array($postData['cliente_telefono']) ? ($postData['cliente_telefono'][$i] ?? '') : $postData['cliente_telefono'];
            $rawMail = (isset($postData['cliente_correo']) && is_array($postData['cliente_correo'])) ? ($postData['cliente_correo'][$i] ?? null) : ($postData['cliente_correo'] ?? null);
            $rawNac = (isset($postData['cliente_nacionalidad']) && is_array($postData['cliente_nacionalidad'])) ? ($postData['cliente_nacionalidad'][$i] ?? 'ECUATORIANA') : ($postData['cliente_nacionalidad'] ?? 'ECUATORIANA');
            $rawFec = (isset($postData['cliente_fechanac']) && is_array($postData['cliente_fechanac'])) ? ($postData['cliente_fechanac'][$i] ?? null) : ($postData['cliente_fechanac'] ?? null);
            $rawAsi = (isset($postData['asiento']) && is_array($postData['asiento'])) ? ($postData['asiento'][$i] ?? null) : ($postData['asiento'] ?? null);

            $pasajeros[] = [
                'id' => trim((string)$rawId),
                'nombre' => trim((string)$rawNom),
                'apellido' => trim((string)$rawApe),
                'telefono' => trim((string)$rawTel),
                'correo' => !empty($rawMail) ? trim((string)$rawMail) : null,
                'nacionalidad' => !empty($rawNac) ? mb_strtoupper(trim((string)$rawNac)) : 'ECUATORIANA',
                'fechanac' => !empty($rawFec) ? $rawFec : null,
                'asiento' => !empty($rawAsi) ? intval($rawAsi) : null
            ];
        }

        $datosFormateados = [
            'ruta_id' => intval($postData['ruta_id']),
            'titular_id' => $pasajeros[0]['id'],
            'fecha_viaje' => !empty($postData['fecha_viaje']) ? $postData['fecha_viaje'] : null,
            'bus_id' => !empty($postData['bus_id']) ? intval($postData['bus_id']) : null,
            'cantidad_total' => $cantidadTotal,
            'metodo_pago' => $postData['metodo_pago'],
            'monto_efectivo' => floatval($postData['monto_efectivo'] ?? 0),
            'monto_transferencia' => floatval($postData['monto_transferencia'] ?? 0),
            'monto_abonar' => $montoAbonadoGlobal,
            'tipo_factura' => ($saldoGlobal > 0) ? 'RESERVA INICIAL' : 'PASAJE',
            'estado_individual' => $estadoPorPasaje,
            'precio_individual' => $precioPorPasaje,
            'saldo_individual' => $saldoPorPasaje,
            'pasajeros' => $pasajeros
        ];

        return $this->repository->guardarVentaBD($datosFormateados, $usuarioId, $sucursalId);
    }

    public function procesarDevolucion($postData, $usuarioId, $sucursalId, $permisoAnular) {
        if ($permisoAnular != 1) {
            return ['status' => 'error', 'mensaje' => 'No tienes permisos administrativos para anular pasajes.'];
        }
        $res = $this->repository->devolverPasajeBD($postData, $usuarioId, $sucursalId);
        return ($res === true) ? ['status' => 'success'] : ['status' => 'error', 'mensaje' => $res];
    }

    public function procesarSolicitudAnulacion($postData, $usuarioId, $sucursalId) {
        if (empty($postData['id_pasaje']) || empty(trim($postData['motivo'] ?? ''))) {
            return ['status' => 'error', 'mensaje' => 'Debe indicar el motivo de la solicitud de anulación.'];
        }
        $monto = floatval($postData['monto_devolver'] ?? 0);
        $idFactura = !empty($postData['id_factura']) ? (int)$postData['id_factura'] : null;
        $res = $this->repository->solicitarAnulacionBD('PASAJE', (int)$postData['id_pasaje'], $idFactura, $usuarioId, $sucursalId, $monto, $postData['motivo']);
        return ($res === true) ? ['status' => 'success', 'mensaje' => 'Solicitud de anulación enviada a Administración.'] : ['status' => 'error', 'mensaje' => $res];
    }

    public function procesarAprobacionSolicitud($idSolicitud, $usuarioId, $sucursalId, $permisoAnular) {
        if ($permisoAnular != 1) {
            return ['status' => 'error', 'mensaje' => 'No tienes permisos administrativos para aprobar anulaciones.'];
        }
        $res = $this->repository->aprobarSolicitudAnulacionBD((int)$idSolicitud, $usuarioId, $sucursalId);
        return ($res === true) ? ['status' => 'success', 'mensaje' => 'Anulación aprobada y procesada correctamente.'] : ['status' => 'error', 'mensaje' => $res];
    }

    public function procesarDenegacionSolicitud($idSolicitud, $motivoRechazo, $usuarioId, $permisoAnular) {
        if ($permisoAnular != 1) {
            return ['status' => 'error', 'mensaje' => 'No tienes permisos administrativos para denegar anulaciones.'];
        }
        if (empty(trim($motivoRechazo))) {
            return ['status' => 'error', 'mensaje' => 'Debe ingresar el motivo de denegación.'];
        }
        $res = $this->repository->denegarSolicitudAnulacionBD((int)$idSolicitud, $usuarioId, trim($motivoRechazo));
        return ($res === true) ? ['status' => 'success', 'mensaje' => 'Solicitud de anulación denegada.'] : ['status' => 'error', 'mensaje' => $res];
    }
}
?>