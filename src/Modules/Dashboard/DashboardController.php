<?php
namespace Modules\Dashboard;

class DashboardController {
    private $service;

    public function __construct() {
        $this->service = new DashboardService();
    }

    public function index() {
        // Redirigir al login si no hay sesión (Doble validación de seguridad)
        if (!isset($_SESSION['user_id'])) {
            header("Location: /suramerica2030/auth");
            exit();
        }

        $sucursalId = $_SESSION['sucursal_id'] ?? 1;

        // Extraemos los datos a través del servicio
        $stats = $this->service->compilarEstadisticas($sucursalId);
        $actividad = $this->service->compilarActividadReciente($sucursalId);

        // Variables requeridas por el Layout Maestro
        $titulo = "Panel de Control - Suramerica 2030";
        // Definimos la ruta de la vista modular (Relativa a la ubicación del layout)
        $vista = __DIR__ . '/Views/dashboard.php'; 
        
        // Renderizamos la plantilla base (El layout)
        require_once '../src/Platform/Views/layout.php';
    }
}
?>