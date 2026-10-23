<?php
namespace Modules\Finanzas;

class FinanzasController {
    private $service;

    public function __construct() {
        $this->service = new FinanzasService();
    }

    private function verificarAdmin() {
        if (!isset($_SESSION['rol_id']) || !in_array($_SESSION['rol_id'], [1, 2])) { 
            header("Location: /suramerica2030/dashboard"); 
            exit(); 
        }
    }

    public function index() {
        $this->verificarAdmin();

        $filtros = [
            'fecha_inicio' => !empty($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : date('Y-m-01'),
            'fecha_fin' => !empty($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : date('Y-m-d'),
            'tipo_fecha' => (isset($_GET['tipo_fecha']) && $_GET['tipo_fecha'] === 'viaje') ? 'viaje' : 'venta',
            'operador' => !empty($_GET['operador']) ? trim($_GET['operador']) : 'TODOS',
            'modalidad' => !empty($_GET['modalidad']) ? trim($_GET['modalidad']) : 'TODAS',
            'id_sucursal' => !empty($_GET['id_sucursal']) ? trim($_GET['id_sucursal']) : ''
        ];

        $data = $this->service->compilarDatosIniciales($filtros);
        extract($data); // $sucursales, $operadores, $liquidacion
        
        $tabActivo = $_GET['tab'] ?? 'liquidaciones';
        $titulo = "Finanzas y Liquidación a Socios - Suramerica 2030";
        $vista = __DIR__ . '/Views/finanzas.php';
        require_once __DIR__ . '/../../Platform/Views/layout.php';
    }

    public function guardarSucursal() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarSucursal($_POST);
            $this->redireccionar('sucursales', $resultado);
        }
    }

    private function redireccionar($tab, $resultado) {
        if ($resultado === true) {
            \Platform\Helpers\LiveResponse::respond(true, $tab, 'success', 'Configuración de sucursal actualizada exitosamente.', [], '/suramerica2030/finanzas');
        } else {
            \Platform\Helpers\LiveResponse::respond(is_array($resultado) ? $resultado : ['status' => 'error', 'mensaje' => $resultado], $tab, 'success', '', [], '/suramerica2030/finanzas');
        }
    }
}
?>