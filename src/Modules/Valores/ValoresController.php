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
            $idValor = $_POST['id_valor'] ?? null;
            $res = $this->repository->entregarValorBD($idValor, $_POST['id_factura'], $_POST['monto_entregar'], $_SESSION['user_id'], $_SESSION['sucursal_id']);
            if ($res === true) {
                \Platform\Helpers\LiveResponse::respond(true, null, 'success_entrega', 'Valor entregado exitosamente al destinatario.', ['id_valor' => $idValor], '/suramerica2030/valores');
            } else {
                $this->redireccionar($res);
            }
        }
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $permiso = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) ? 1 : ($_SESSION['p_anular'] ?? 0);
            $res = $this->service->procesarAnulacion($_POST['id_factura'], $_SESSION['user_id'], $_SESSION['sucursal_id'], $permiso);
            if ($res['status'] === 'success') {
                \Platform\Helpers\LiveResponse::respond(true, null, 'success_delete', 'Envío de valor anulado exitosamente.', ['id_factura' => $_POST['id_factura']], '/suramerica2030/valores');
            } else {
                $this->redireccionar($res['mensaje']);
            }
        }
    }

    public function solicitarAnulacion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['id_factura'])) {
            $idFactura = (int)$_POST['id_factura'];
            $pasajesRepo = new \Modules\Pasajes\PasajesRepository();
            $res = $pasajesRepo->solicitarAnulacionBD('VALOR', $idFactura, $idFactura, $_SESSION['user_id'], $_SESSION['sucursal_id'], floatval($_POST['monto_devolver'] ?? 0), $_POST['motivo'] ?? '');
            if ($res === true) {
                \Platform\Helpers\LiveResponse::respond(true, null, 'success_solicitud', 'Solicitud de anulación enviada a Administración.', ['id_factura' => $idFactura], '/suramerica2030/valores');
            } else {
                \Platform\Helpers\LiveResponse::respond(['status' => 'error', 'mensaje' => $res], null, 'success', '', [], '/suramerica2030/valores');
            }
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
        \Platform\Helpers\LiveResponse::respond(['status' => 'error', 'mensaje' => $mensajeError], null, 'success', '', [], '/suramerica2030/valores');
    }
}
?>