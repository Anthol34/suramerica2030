<?php
namespace Modules\Flota;

class FlotaController {
    private $service;
    private $repository;

    public function __construct() {
        $this->service = new FlotaService();
        $this->repository = new FlotaRepository();
    }

    private function verificarPermiso() {
        if (!isset($_SESSION['p_flota']) || $_SESSION['p_flota'] != 1) { 
            header("Location: /suramerica2030/dashboard");
            exit();
        }
    }

    public function index() {
        $this->verificarPermiso();

        $corredores = $this->repository->obtenerCorredores();
        $idCorredorSeleccionado = isset($_GET['corredor_id']) ? intval($_GET['corredor_id']) : (!empty($corredores) ? $corredores[0]['ID_CORREDOR'] : 1);
        $corredorActivo = null;
        foreach ($corredores as $c) {
            if ($c['ID_CORREDOR'] == $idCorredorSeleccionado) {
                $corredorActivo = $c;
                break;
            }
        }
        if (!$corredorActivo && !empty($corredores)) {
            $corredorActivo = $corredores[0];
            $idCorredorSeleccionado = $corredorActivo['ID_CORREDOR'];
        }

        $paradas = $corredorActivo ? $this->repository->obtenerParadas($idCorredorSeleccionado) : [];
        $paradasPorCorredor = $this->repository->obtenerParadasDeTodosLosCorredores();
        $tramosOperativos = $corredorActivo ? $this->repository->obtenerTramosOperativos($idCorredorSeleccionado) : [];
        $ciudadesCatalogo = $this->repository->obtenerCiudadesCatalogo();
        $ciudadesCompletas = $this->repository->obtenerTodasLasCiudadesConEstadisticas();
        $rutas = $this->repository->obtenerRutas();
        $buses = $this->repository->obtenerBusesConSocios();
        $operadores = $this->repository->obtenerOperadores(false);
        
        $titulo = "Gestión de Flota y Corredores - Suramerica 2030";
        $vista = __DIR__ . '/Views/flota.php';
        require_once __DIR__ . '/../../Platform/Views/layout.php';
    }

    // --- ACCIONES DE CORREDORES Y PARADAS ---
    public function crearCorredor() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarCrearCorredor($_POST);
            if (is_numeric($resultado)) {
                \Platform\Helpers\LiveResponse::respond(true, "corredor&corredor_id={$resultado}", 'success', 'Nuevo corredor creado exitosamente.', ['id_corredor' => $resultado], '/suramerica2030/flota');
            } else {
                \Platform\Helpers\LiveResponse::respond(is_string($resultado) ? $resultado : 'Error al crear corredor', 'corredor', 'error', '', [], '/suramerica2030/flota');
            }
        }
    }

    public function eliminarCorredor() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEliminarCorredor($_POST);
            $this->redireccionar('corredor', $resultado, 'success_delete');
        }
    }

    public function guardarCorredor() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarActualizarCorredor($_POST);
            $idCorredor = intval($_POST['id_corredor'] ?? 1);
            \Platform\Helpers\LiveResponse::respond($resultado, "corredor&corredor_id={$idCorredor}", 'success_edit', 'Políticas del corredor actualizadas correctamente.', ['id_corredor' => $idCorredor], '/suramerica2030/flota');
        }
    }

    public function guardarParada() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idCorredor = intval($_POST['id_corredor'] ?? 1);
            $resultado = $this->service->procesarAgregarParada($_POST);
            \Platform\Helpers\LiveResponse::respond($resultado, "corredor&corredor_id={$idCorredor}", 'success', 'Parada agregada al itinerario correctamente.', ['id_corredor' => $idCorredor], '/suramerica2030/flota');
        }
    }

    public function eliminarParada() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idCorredor = intval($_POST['id_corredor_retorno'] ?? 1);
            $resultado = $this->service->procesarEliminarParada($_POST);
            \Platform\Helpers\LiveResponse::respond($resultado, "corredor&corredor_id={$idCorredor}", 'success_delete', 'Parada eliminada del corredor.', ['id_corredor' => $idCorredor], '/suramerica2030/flota');
        }
    }

    public function moverParada() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idCorredor = intval($_POST['id_corredor_retorno'] ?? 1);
            $resultado = $this->service->procesarMoverParada($_POST);
            \Platform\Helpers\LiveResponse::respond($resultado, "corredor&corredor_id={$idCorredor}", 'success_edit', 'Orden de paradas actualizado.', ['id_corredor' => $idCorredor], '/suramerica2030/flota');
        }
    }

    public function guardarBusTramo() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idCorredor = intval($_POST['id_corredor'] ?? 1);
            $resultado = $this->service->procesarActualizarBusTramo($_POST);
            \Platform\Helpers\LiveResponse::respond($resultado, "corredor&corredor_id={$idCorredor}", 'success_edit', 'Bus y unidad operativa asignada al tramo.', ['id_corredor' => $idCorredor], '/suramerica2030/flota');
        }
    }

    public function generarRutasCorredor() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGenerarRutasCorredor($_POST);
            $this->redireccionar('rutas', $resultado, 'success');
        }
    }

    public function guardarTarifaRapida() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarActualizarTarifaRapida($_POST);
            
            $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                      || !empty($_POST['ajax']);

            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                if ($resultado === true) {
                    $idRuta = intval($_POST['id_ruta'] ?? 0);
                    $rutaActualizada = null;
                    $todas = $this->repository->obtenerRutas();
                    foreach ($todas as $r) {
                        if (intval($r['ID_RUTA']) === $idRuta) {
                            $rutaActualizada = $r;
                            break;
                        }
                    }
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => 'Tarifa de la ruta #' . $idRuta . ' actualizada correctamente.',
                        'ruta' => $rutaActualizada
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => is_string($resultado) ? $resultado : 'Error al guardar la tarifa.'
                    ]);
                }
                exit();
            }

            $this->redireccionar('rutas', $resultado, 'success_edit');
        }
    }

    // --- ACCIONES DE RUTAS ---
    public function guardarRuta() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarRuta($_POST);
            $this->redireccionar('rutas', $resultado, 'success');
        }
    }

    public function editarRuta() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEditarRuta($_POST);

            $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                      || !empty($_POST['ajax']);

            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                if ($resultado === true) {
                    $idRuta = intval($_POST['id_ruta'] ?? 0);
                    $rutaActualizada = null;
                    $todas = $this->repository->obtenerRutas();
                    foreach ($todas as $r) {
                        if (intval($r['ID_RUTA']) === $idRuta) {
                            $rutaActualizada = $r;
                            break;
                        }
                    }
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => 'Configuración de la ruta #' . $idRuta . ' actualizada correctamente.',
                        'ruta' => $rutaActualizada
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => is_string($resultado) ? $resultado : 'Error al actualizar la ruta.'
                    ]);
                }
                exit();
            }

            $this->redireccionar('rutas', $resultado, 'success_edit');
        }
    }

    public function eliminarRuta() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->repository->eliminarRutaBD(intval($_POST['id_ruta']));
            if (is_array($resultado)) {
                if ($resultado['accion'] === 'inactivada') {
                    header("Location: /suramerica2030/flota?tab=rutas&status=success_archived&msg=" . urlencode($resultado['mensaje']));
                } else {
                    header("Location: /suramerica2030/flota?tab=rutas&status=success_delete");
                }
                exit();
            } else {
                $this->redireccionar('rutas', $resultado, 'success_delete');
            }
        }
    }

    // --- ACCIONES DE BUSES ---
    public function guardarBus() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarBus($_POST);
            $this->redireccionar('buses', $resultado, 'success');
        }
    }

    public function editarBus() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEditarBus($_POST);
            $this->redireccionar('buses', $resultado, 'success_edit');
        }
    }

    public function eliminarBus() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->repository->eliminarBusBD(intval($_POST['id_bus']));
            $this->redireccionar('buses', $resultado, 'success_delete_bus');
        }
    }
    
    // --- ACCIONES DE OPERADORES Y SOCIOS ---
    public function guardarOperador() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarOperador($_POST);
            $this->redireccionar('operadores', $resultado, 'success');
        }
    }

    public function editarOperador() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEditarOperador($_POST);
            $this->redireccionar('operadores', $resultado, 'success_edit');
        }
    }

    public function eliminarOperador() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEliminarOperador($_POST);
            $this->redireccionar('operadores', $resultado, 'success_delete');
        }
    }

    // --- ACCIONES DE GESTIÓN DE CIUDADES Y DOMINIO REGIONAL ---
    public function guardarCiudad() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarCiudad($_POST);
            $token = !empty($_POST['id_ciudad']) ? 'success_edit' : 'success';
            $this->redireccionar('ciudades', $resultado, $token);
        }
    }

    public function cambiarEstadoCiudad() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarCambiarEstadoCiudad($_POST);
            $this->redireccionar('ciudades', $resultado, 'success_edit');
        }
    }

    public function eliminarCiudad() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEliminarCiudad($_POST);
            $this->redireccionar('ciudades', $resultado, 'success_delete');
        }
    }

    public function precargarCiudades() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarPrecargarCiudades();
            $this->redireccionar('ciudades', $resultado, 'success');
        }
    }
    
    private function redireccionar($tab, $resultado, $successToken) {
        $mensajes = [
            'success' => 'Registro completado con éxito.',
            'success_edit' => 'Cambios guardados correctamente.',
            'success_delete' => 'Eliminado correctamente.',
            'success_delete_bus' => 'Unidad (Bus) eliminada correctamente.',
            'success_archived' => 'Ruta inactivada correctamente.'
        ];
        $msg = $mensajes[$successToken] ?? 'Operación realizada con éxito.';
        \Platform\Helpers\LiveResponse::respond($resultado, $tab, $successToken, $msg, [], '/suramerica2030/flota');
    }
}
?>