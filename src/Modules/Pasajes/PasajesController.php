<?php
namespace Modules\Pasajes;

class PasajesController {
    private $service;
    private $repository;

    public function __construct() {
        $this->service = new PasajesService();
        $this->repository = new PasajesRepository();
    }

    public function index() {
        $filtros = [
            'tab'           => $_GET['tab'] ?? 'activos',
            'filtro_rapido' => trim($_GET['filtro_rapido'] ?? ''),
            'buscar'        => trim($_GET['buscar'] ?? ''),
            'tipo_fecha'    => $_GET['tipo_fecha'] ?? 'viaje',
            'fecha_inicio'  => trim($_GET['fecha_inicio'] ?? ''),
            'fecha_fin'     => trim($_GET['fecha_fin'] ?? ''),
            'dia_semana'    => trim($_GET['dia_semana'] ?? ''),
            'ruta_id'       => (int)($_GET['ruta_id'] ?? 0),
            'estado'        => trim($_GET['estado'] ?? '')
        ];
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        
        $data = $this->service->obtenerDatosIniciales($filtros, $pagina);
        extract($data); 

        $q = $_GET; unset($q['url'], $q['pagina']);
        $queryString = http_build_query($q);
        
        $titulo = "Venta de Pasajes - Suramerica 2030";
        $vista = __DIR__ . '/Views/pasajes.php';
        require_once __DIR__ . '/../../Platform/Views/layout.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->service->procesarVenta($_POST, $_SESSION['user_id'], $_SESSION['sucursal_id']);
            if ($res['status'] === 'success') {
                header("Location: /suramerica2030/pasajes?status=success&print_ticket=" . urlencode($res['id_factura']));
            } else {
                $this->redireccionar($res['mensaje']);
            }
        }
    }

    public function abonar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->redireccionar($this->repository->registrarAbonoBD($_POST, $_SESSION['user_id'], $_SESSION['sucursal_id']));
        }
    }

    public function equipaje() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->repository->registrarEquipajeBD($_POST, $_SESSION['user_id'], $_SESSION['sucursal_id']);
            if (is_array($res) && $res['success']) {
                $url = "/suramerica2030/pasajes?status=success&print_baggage=" . urlencode($_POST['id_pasaje']);
                if (!empty($res['id_factura'])) $url .= "&print_ticket=" . urlencode($res['id_factura']);
                header("Location: " . $url); exit();
            } else {
                $this->redireccionar($res['mensaje'] ?? 'Error desconocido');
            }
        }
    }

    public function anularSobrepeso() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $permiso = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_anular']) && $_SESSION['p_anular'] == 1);
            $redirectTab = !empty($_POST['redirect_tab']) ? $_POST['redirect_tab'] : 'activos';
            if (!$permiso) {
                \Platform\Helpers\LiveResponse::respond(['status' => 'error', 'mensaje' => 'No tienes permisos para anular sobrepeso.'], $redirectTab, 'success', '', [], '/suramerica2030/pasajes');
            }
            $idPasaje = intval($_POST['id_pasaje'] ?? 0);
            $idSobrepeso = intval($_POST['id_sobrepeso'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? 'Anulación de sobrepeso en taquilla');
            
            $res = $this->repository->anularSobrepesoBD($idPasaje, $idSobrepeso, $motivo, $_SESSION['user_id'], $_SESSION['sucursal_id']);
            $redirectTab = !empty($_POST['redirect_tab']) ? $_POST['redirect_tab'] : 'activos';
            
            if ($res === true) {
                \Platform\Helpers\LiveResponse::respond(true, $redirectTab, 'success', "Sobrepeso anulado exitosamente y reembolsado en caja.", ['id_pasaje' => $idPasaje, 'id_sobrepeso' => $idSobrepeso], '/suramerica2030/pasajes');
            } else {
                \Platform\Helpers\LiveResponse::respond(['status' => 'error', 'mensaje' => $res], $redirectTab, 'success', '', [], '/suramerica2030/pasajes');
            }
        }
    }

    public function asignar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->redireccionar($this->repository->asignarViajeBD($_POST));
        }
    }

    public function reasignar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->redireccionar($this->repository->reasignarAsientoBD($_POST['id_pasaje'], $_POST['nuevo_asiento']));
        }
    }

    public function devolver() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $permiso = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) ? 1 : ($_SESSION['p_anular'] ?? 0);
            $res = $this->service->procesarDevolucion($_POST, $_SESSION['user_id'], $_SESSION['sucursal_id'], $permiso);
            if ($res['status'] === 'success') {
                \Platform\Helpers\LiveResponse::respond(true, 'activos', 'success', "Venta anulada y procesada exitosamente.", ['id_pasaje' => $_POST['id_pasaje'] ?? null], '/suramerica2030/pasajes');
            } else {
                $this->redireccionar($res['mensaje']);
            }
        }
    }

    public function solicitarAnulacion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->service->procesarSolicitudAnulacion($_POST, $_SESSION['user_id'], $_SESSION['sucursal_id']);
            if ($res['status'] === 'success') {
                \Platform\Helpers\LiveResponse::respond(true, 'solicitudes', 'success', $res['mensaje'], [], '/suramerica2030/pasajes');
            } else {
                \Platform\Helpers\LiveResponse::respond(['status' => 'error', 'mensaje' => $res['mensaje']], 'solicitudes', 'success', '', [], '/suramerica2030/pasajes');
            }
        }
    }

    public function aprobarAnulacion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $permiso = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) ? 1 : ($_SESSION['p_anular'] ?? 0);
            $res = $this->service->procesarAprobacionSolicitud($_POST['id_solicitud'] ?? 0, $_SESSION['user_id'], $_SESSION['sucursal_id'], $permiso);
            $tabRedirect = !empty($_POST['redirect_tab']) ? $_POST['redirect_tab'] : 'solicitudes';
            if ($res['status'] === 'success') {
                \Platform\Helpers\LiveResponse::respond(true, $tabRedirect, 'success', $res['mensaje'], ['id_solicitud' => $_POST['id_solicitud'] ?? 0], '/suramerica2030/pasajes');
            } else {
                \Platform\Helpers\LiveResponse::respond(['status' => 'error', 'mensaje' => $res['mensaje']], $tabRedirect, 'success', '', [], '/suramerica2030/pasajes');
            }
        }
    }

    public function denegarAnulacion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $permiso = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) ? 1 : ($_SESSION['p_anular'] ?? 0);
            $res = $this->service->procesarDenegacionSolicitud($_POST['id_solicitud'] ?? 0, $_POST['motivo_rechazo'] ?? '', $_SESSION['user_id'], $permiso);
            $tabRedirect = !empty($_POST['redirect_tab']) ? $_POST['redirect_tab'] : 'solicitudes';
            if ($res['status'] === 'success') {
                \Platform\Helpers\LiveResponse::respond(true, $tabRedirect, 'success', $res['mensaje'], ['id_solicitud' => $_POST['id_solicitud'] ?? 0], '/suramerica2030/pasajes');
            } else {
                \Platform\Helpers\LiveResponse::respond(['status' => 'error', 'mensaje' => $res['mensaje']], $tabRedirect, 'success', '', [], '/suramerica2030/pasajes');
            }
        }
    }

    public function actualizarFinanciero() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->repository->actualizarFinancieroBD($_POST, $_SESSION['user_id'], $_SESSION['sucursal_id']);
            $this->redireccionar($res);
        }
    }

    public function buscarCliente() {
        $id = $_GET['id'] ?? '';
        if (empty($id)) { echo json_encode(['success' => false]); exit(); }
        $cliente = $this->repository->obtenerClientePorId($id);
        echo json_encode(['success' => (bool)$cliente, 'data' => $cliente]);
        exit();
    }

    public function apiAsientosOcupados() {
        $fecha = $_GET['fecha'] ?? '';
        $ruta = $_GET['ruta'] ?? 0;
        $bus = $_GET['bus'] ?? 0;
        
        $asientosOcupados = $this->repository->obtenerAsientosOcupados($fecha, $ruta, $bus);
        $numeros = array_column($asientosOcupados, 'PAX_ASIENTO');
        
        // Filtramos nulos (compatible con PHP 7.3)
        $numeros = array_values(array_filter($numeros, function($val) { return !is_null($val); }));
        
        header('Content-Type: application/json');
        echo json_encode($numeros);
        exit();
    }

    public function imprimirEquipaje() {
        if (isset($_GET['id'])) {
            $pasaje = $this->repository->obtenerDetallePasaje($_GET['id']);
            if ($pasaje) {
                if (isset($_GET['codigo_socio']) && trim($_GET['codigo_socio']) !== '') {
                    $cod = trim($_GET['codigo_socio']);
                    $pasaje['CODIGO_SOCIO'] = $cod;
                    $this->repository->actualizarCodigoSocio($pasaje['ID_PASAJE'], $cod);
                }
                require_once __DIR__ . '/Views/equipaje_print.php'; 
            }
        }
    }

    public function guardarCodigoSocio() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idPasaje = intval($_POST['id_pasaje'] ?? 0);
            $codigo = trim($_POST['codigo_socio'] ?? '');
            if ($idPasaje > 0) {
                $this->repository->actualizarCodigoSocio($idPasaje, $codigo);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'codigo_socio' => $codigo]);
                exit();
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit();
    }

    private function redireccionar($resultado, $tab = null, $extra = []) {
        if ($resultado === true) {
            \Platform\Helpers\LiveResponse::respond(true, $tab, 'success', 'Operación procesada correctamente.', $extra, '/suramerica2030/pasajes');
        } else {
            \Platform\Helpers\LiveResponse::respond(is_array($resultado) ? $resultado : ['status' => 'error', 'mensaje' => $resultado], $tab, 'success', '', $extra, '/suramerica2030/pasajes');
        }
    }
}
?>