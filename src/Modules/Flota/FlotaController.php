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
                header("Location: /suramerica2030/flota?tab=corredor&corredor_id={$resultado}&status=success");
                exit();
            } else {
                header("Location: /suramerica2030/flota?tab=corredor&status=error&msg=" . urlencode($resultado));
                exit();
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
            if ($resultado === true) {
                header("Location: /suramerica2030/flota?tab=corredor&corredor_id={$idCorredor}&status=success_edit");
                exit();
            } else {
                header("Location: /suramerica2030/flota?tab=corredor&corredor_id={$idCorredor}&status=error&msg=" . urlencode($resultado));
                exit();
            }
        }
    }

    public function guardarParada() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarAgregarParada($_POST);
            $idCorredor = intval($_POST['id_corredor'] ?? 1);
            $token = ($resultado === true) ? 'success' : 'error';
            $extra = ($resultado === true) ? '' : '&msg=' . urlencode($resultado);
            header("Location: /suramerica2030/flota?tab=corredor&corredor_id={$idCorredor}&status={$token}{$extra}");
            exit();
        }
    }

    public function eliminarParada() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idCorredor = intval($_POST['id_corredor_retorno'] ?? 1);
            $resultado = $this->service->procesarEliminarParada($_POST);
            $token = ($resultado === true) ? 'success_delete' : 'error';
            $extra = ($resultado === true) ? '' : '&msg=' . urlencode($resultado);
            header("Location: /suramerica2030/flota?tab=corredor&corredor_id={$idCorredor}&status={$token}{$extra}");
            exit();
        }
    }

    public function moverParada() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idCorredor = intval($_POST['id_corredor_retorno'] ?? 1);
            $resultado = $this->service->procesarMoverParada($_POST);
            $token = ($resultado === true) ? 'success_edit' : 'error';
            $extra = ($resultado === true) ? '' : '&msg=' . urlencode($resultado);
            header("Location: /suramerica2030/flota?tab=corredor&corredor_id={$idCorredor}&status={$token}{$extra}");
            exit();
        }
    }

    public function guardarBusTramo() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idCorredor = intval($_POST['id_corredor'] ?? 1);
            $resultado = $this->service->procesarActualizarBusTramo($_POST);
            $token = ($resultado === true) ? 'success_edit' : 'error';
            $extra = ($resultado === true) ? '' : '&msg=' . urlencode($resultado);
            header("Location: /suramerica2030/flota?tab=corredor&corredor_id={$idCorredor}&status={$token}{$extra}");
            exit();
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
    
    private function redireccionar($tab, $resultado, $successToken) {
        if ($resultado === true) {
            header("Location: /suramerica2030/flota?tab=$tab&status=$successToken");
        } else {
            header("Location: /suramerica2030/flota?tab=$tab&status=error&msg=" . urlencode($resultado));
        }
        exit();
    }
}
?>