<?php
namespace Modules\Valores;

use Platform\Validation\Validator;

class ValoresService {
    private $repository;

    public function __construct() {
        $this->repository = new ValoresRepository();
    }

    public function obtenerDatosIniciales($filtros, $pagina) {
        $limite = 10;
        $offset = ($pagina - 1) * $limite;
        
        $totalRegistros = $this->repository->contarTodos($filtros);
        
        return [
            'rutas' => $this->repository->obtenerRutas(),
            'valores' => $this->repository->obtenerTodos($filtros, $offset, $limite),
            'totalPaginas' => ceil($totalRegistros / $limite),
            'paginaActual' => $pagina,
            'pagina' => $pagina,
            'totalRegistros' => $totalRegistros
        ];
    }

    public function procesarEnvio($postData, $usuarioId, $sucursalId) {
        // Validaciones base
        if (empty($postData['cliente_id']) || empty($postData['destinatario_ci']) || empty($postData['monto_enviar'])) {
            return ['status' => 'error', 'mensaje' => 'Todos los datos principales de remitente, destinatario y monto son obligatorios.'];
        }

        $montoEnviar = floatval($postData['monto_enviar']);
        if ($montoEnviar <= 0) {
            return ['status' => 'error', 'mensaje' => 'El monto a transferir debe ser mayor a 0.'];
        }

        $rutas = $this->repository->obtenerRutas();
        $comisionBase = 10.0;
        foreach ($rutas as $r) {
            if ($r['ID_RUTA'] == $postData['ruta_id']) {
                $comisionBase = floatval($r['COMISION_VALOR']); break;
            }
        }

        $comisionCalculada = ($montoEnviar * $comisionBase) / 100;
        $montoTotalBackend = $montoEnviar + $comisionCalculada;

        $metodoPago = trim($postData['metodo_pago'] ?? 'EFECTIVO');
        $montoEfectivo = floatval($postData['monto_efectivo'] ?? 0);
        $montoTransferencia = floatval($postData['monto_transferencia'] ?? 0);

        if ($metodoPago === 'MIXTO') {
            $valMixto = Validator::pagoMixto($montoTotalBackend, $montoEfectivo, $montoTransferencia);
            if (!$valMixto['valido']) {
                return ['status' => 'error', 'mensaje' => $valMixto['mensaje']];
            }
        } elseif ($metodoPago === 'EFECTIVO') {
            $montoEfectivo = $montoTotalBackend;
            $montoTransferencia = 0.0;
        } else {
            $montoTransferencia = $montoTotalBackend;
            $montoEfectivo = 0.0;
        }

        $datosLimpios = [
            'ruta_id' => intval($postData['ruta_id']),
            'cliente_id' => Validator::limpiarAlfanumerico($postData['cliente_id']),
            'cliente_nombre' => trim($postData['cliente_nombre']),
            'cliente_apellido' => trim($postData['cliente_apellido']),
            'cliente_telefono' => Validator::limpiarTelefono($postData['cliente_telefono'] ?? ''),
            'destinatario_ci' => Validator::limpiarAlfanumerico($postData['destinatario_ci']),
            'destinatario_nombre' => trim($postData['destinatario_nombre']),
            'destinatario_apellido' => trim($postData['destinatario_apellido']),
            'destinatario_telefono' => Validator::limpiarTelefono($postData['destinatario_telefono'] ?? ''),
            'monto_enviar' => $montoEnviar,
            'monto_total' => $montoTotalBackend,
            'metodo_pago' => $metodoPago,
            'monto_efectivo' => $montoEfectivo,
            'monto_transferencia' => $montoTransferencia,
        ];

        $res = $this->repository->registrarEnvioBD($datosLimpios, $usuarioId, $sucursalId);
        return is_numeric($res) ? ['status' => 'success', 'id_factura' => $res] : ['status' => 'error', 'mensaje' => $res];
    }

    public function procesarAnulacion($idFactura, $usuarioId, $sucursalId, $permisoAnular) {
        if ($permisoAnular != 1) {
            return ['status' => 'error', 'mensaje' => 'No tienes permisos para anular operaciones.'];
        }
        $res = $this->repository->eliminarValorBD($idFactura, $usuarioId, $sucursalId);
        return ($res === true) ? ['status' => 'success'] : ['status' => 'error', 'mensaje' => $res];
    }
}
?>