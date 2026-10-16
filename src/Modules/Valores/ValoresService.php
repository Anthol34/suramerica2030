<?php
namespace Modules\Valores;

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
            return ['status' => 'error', 'mensaje' => 'Datos incompletos.'];
        }

        // Recalcular comisión en el servidor por seguridad
        $montoEnviar = floatval($postData['monto_enviar']);
        if ($montoEnviar <= 0) return ['status' => 'error', 'mensaje' => 'El monto debe ser mayor a 0.'];

        $rutas = $this->repository->obtenerRutas();
        $comisionBase = 10.0;
        foreach ($rutas as $r) {
            if ($r['ID_RUTA'] == $postData['ruta_id']) {
                $comisionBase = floatval($r['COMISION_VALOR']); break;
            }
        }

        $comisionCalculada = ($montoEnviar * $comisionBase) / 100;
        $montoTotalBackend = $montoEnviar + $comisionCalculada;

        $datosLimpios = [
            'ruta_id' => intval($postData['ruta_id']),
            'cliente_id' => trim($postData['cliente_id']),
            'cliente_nombre' => trim($postData['cliente_nombre']),
            'cliente_apellido' => trim($postData['cliente_apellido']),
            'cliente_telefono' => trim($postData['cliente_telefono']),
            'destinatario_ci' => trim($postData['destinatario_ci']),
            'destinatario_nombre' => trim($postData['destinatario_nombre']),
            'destinatario_apellido' => trim($postData['destinatario_apellido']),
            'destinatario_telefono' => trim($postData['destinatario_telefono']),
            'monto_enviar' => $montoEnviar,
            'monto_total' => $montoTotalBackend,
            'metodo_pago' => $postData['metodo_pago'],
            'monto_efectivo' => floatval($postData['monto_efectivo'] ?? 0),
            'monto_transferencia' => floatval($postData['monto_transferencia'] ?? 0),
            'detalle_destinatario' => "Para: " . trim($postData['destinatario_nombre']) . " " . trim($postData['destinatario_apellido'])
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