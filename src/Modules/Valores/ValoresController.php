<?php
namespace Modules\Valores;

class ValoresController {
    private $service;
    private $repository;

    public function __construct() {
        $this->service = new ValoresService();
        $this->repository = new ValoresRepository();
    }

    public function index() {
        $filtros = ['tab' => $_GET['tab'] ?? 'activos', 'estado' => $_GET['estado'] ?? '', 'buscar' => $_GET['buscar'] ?? '', 'fecha_inicio' => $_GET['fecha_inicio'] ?? date('Y-m-01'), 'fecha_fin' => $_GET['fecha_fin'] ?? date('Y-m-t')];
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        
        $data = $this->service->obtenerDatosIniciales($filtros, $pagina);
        extract($data); 

        $q = $_GET; unset($q['url'], $q['pagina']);
        $queryString = http_build_query($q);
        
        $titulo = "Envío de Valores - Suramerica 2030";
        $vista = __DIR__ . '/Views/valores.php';
        require_once '../src/Platform/Views/layout.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEnvio($_POST, $_SESSION['user_id'], $_SESSION['sucursal_id']);
            if ($resultado['status'] === 'success') {
                header("Location: /suramerica2030/valores?status=success_envio&print_ticket=" . urlencode($resultado['id_factura']));
            } else {
                $this->redireccionar($resultado['mensaje']);
            }
        }
    }

    public function entregar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->repository->entregarValorBD($_POST['id_valor'], $_POST['id_factura'], $_POST['monto_entregar'], $_SESSION['user_id'], $_SESSION['sucursal_id']);
            if ($res === true) header("Location: /suramerica2030/valores?status=success_entrega");
            else $this->redireccionar($res);
            exit();
        }
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $permiso = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) ? 1 : ($_SESSION['p_anular'] ?? 0);
            $res = $this->service->procesarAnulacion($_POST['id_factura'], $_SESSION['user_id'], $_SESSION['sucursal_id'], $permiso);
            if ($res['status'] === 'success') header("Location: /suramerica2030/valores?status=success_delete");
            else $this->redireccionar($res['mensaje']);
            exit();
        }
    }

    public function solicitarAnulacion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['id_factura'])) {
            $pasajesRepo = new \Modules\Pasajes\PasajesRepository();
            $res = $pasajesRepo->solicitarAnulacionBD('VALOR', (int)$_POST['id_factura'], (int)$_POST['id_factura'], $_SESSION['user_id'], $_SESSION['sucursal_id'], floatval($_POST['monto_devolver'] ?? 0), $_POST['motivo'] ?? '');
            if ($res === true) {
                header("Location: /suramerica2030/valores?status=success_solicitud");
            } else {
                header("Location: /suramerica2030/valores?status=error&msg=" . urlencode($res));
            }
            exit();
        }
    }

    public function buscarCliente() {
        $id = $_GET['id'] ?? '';
        if (empty($id)) { echo json_encode(['success' => false]); exit(); }
        $cliente = $this->repository->obtenerClientePorId($id);
        echo json_encode(['success' => (bool)$cliente, 'data' => $cliente]);
        exit();
    }

    private function redireccionar($mensajeError) {
        header("Location: /suramerica2030/valores?status=error&msg=" . urlencode($mensajeError));
        exit();
    }
}
?>