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

        $ciudadesCatalogo = $this->repository->obtenerCiudadesCatalogo();
        $ciudadesCompletas = $this->repository->obtenerTodasLasCiudadesConEstadisticas();
        $rutas = $this->repository->obtenerRutas();
        $tramosCatalogo = $this->repository->obtenerTramosCatalogo(false);
        $buses = $this->repository->obtenerBusesConSocios();
        $operadores = $this->repository->obtenerOperadores(false);

        $titulo = "Gestión de Flota y Rutas Comerciales - Suramerica 2030";
        $vista = __DIR__ . '/Views/flota.php';
        require_once __DIR__ . '/../../Platform/Views/layout.php';
    }

    // --- ACCIONES DE RUTAS Y TARIFAS ---
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

    public function actualizarPrecioRapido() {
        $this->actualizarPrecioRapidoRuta();
    }

    public function actualizarPrecioRapidoRuta() {
        $this->verificarPermiso();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'mensaje' => 'Método HTTP no permitido.']);
            exit();
        }

        $idRuta = intval($_POST['id_ruta'] ?? 0);
        $precio = floatval($_POST['precio_pasaje'] ?? 0);

        if ($idRuta <= 0 || $precio <= 0) {
            echo json_encode(['status' => 'error', 'mensaje' => 'ID de ruta o tarifa inválida.']);
            exit();
        }

        $resultado = $this->repository->actualizarPrecioRapidoRutaBD($idRuta, $precio);
        echo json_encode($resultado);
        exit();
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

    // --- ACCIONES DE CATÁLOGO MAESTRO DE TRAMOS Y RUTA RÁPIDA ---
    public function sugerirTramosRuta() {
        $this->verificarPermiso();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $origen = trim($_GET['origen'] ?? $_POST['origen'] ?? '');
            $destino = trim($_GET['destino'] ?? $_POST['destino'] ?? '');
            $modalidad = trim($_GET['modalidad'] ?? $_POST['modalidad'] ?? '');
            $todas = !empty($_GET['todas']) || !empty($_POST['todas']) || empty($modalidad);

            if (empty($origen) || empty($destino)) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Origen y destino son requeridos']);
                exit();
            }

            if ($todas) {
                $sugerencias = $this->repository->sugerirTodasLasModalidadesRuta($origen, $destino);
                echo json_encode(['status' => 'success', 'data' => $sugerencias, 'es_lote' => true]);
            } else {
                $sugerencia = $this->repository->sugerirTramosParaRuta($origen, $destino, $modalidad);
                echo json_encode(['status' => 'success', 'data' => $sugerencia, 'es_lote' => false]);
            }
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
        exit();
    }

    public function generarRutasMultimodales() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGenerarRutasMultimodales($_POST);

            $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                      || !empty($_POST['ajax']);

            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                if (is_array($resultado) && ($resultado['status'] ?? '') === 'success') {
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => $resultado['mensaje'] ?? 'Rutas generadas correctamente.',
                        'rutas' => $resultado['rutas'] ?? []
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => is_string($resultado) ? $resultado : ($resultado['mensaje'] ?? 'Error al generar rutas.')
                    ]);
                }
                exit();
            }

            if (is_array($resultado) && ($resultado['status'] ?? '') === 'success') {
                $this->redireccionar('rutas', true, 'success');
            } else {
                $this->redireccionar('rutas', is_string($resultado) ? $resultado : ($resultado['mensaje'] ?? 'Error'), 'error');
            }
        }
    }

    public function crearRutaRapida() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarCrearRutaRapida($_POST);

            $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                      || !empty($_POST['ajax']);

            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                if (is_array($resultado) && ($resultado['status'] ?? '') === 'success') {
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => $resultado['mensaje'] ?? 'Ruta(s) creada(s) correctamente.',
                        'resultado' => $resultado
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => is_string($resultado) ? $resultado : ($resultado['mensaje'] ?? 'Error al crear la ruta.')
                    ]);
                }
                exit();
            }

            if (is_array($resultado) && ($resultado['status'] ?? '') === 'success') {
                $this->redireccionar('rutas', true, 'success');
            } else {
                $this->redireccionar('rutas', is_string($resultado) ? $resultado : ($resultado['mensaje'] ?? 'Error'), 'error');
            }
        }
    }

    public function guardarTramoCatalogo() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarTramoCatalogo($_POST);
            $token = !empty($_POST['id_tramo_catalogo']) ? 'success_edit' : 'success';
            $this->redireccionar('tramos', $resultado, $token);
        }
    }

    public function cambiarEstadoTramoCatalogo() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarCambiarEstadoTramoCatalogo($_POST);
            $this->redireccionar('tramos', $resultado, 'success_edit');
        }
    }

    public function eliminarTramoCatalogo() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEliminarTramoCatalogo($_POST);
            $this->redireccionar('tramos', $resultado, 'success_delete');
        }
    }

    public function duplicarTramoInverso() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarDuplicarTramoInverso($_POST);
            $this->redireccionar('tramos', $resultado, 'success');
        }
    }

    public function sincronizarTarifasTramos() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarSincronizarTarifasTramos($_POST);

            $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                      || !empty($_POST['ajax']);

            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($resultado);
                exit();
            }

            if (is_array($resultado) && ($resultado['status'] ?? '') === 'success') {
                $this->redireccionar('tramos', true, 'success_edit');
            } else {
                $this->redireccionar('tramos', is_string($resultado) ? $resultado : ($resultado['mensaje'] ?? 'Error'), 'error');
            }
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