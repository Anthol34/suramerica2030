<?php
namespace Modules\Dashboard;

class DashboardService {
    private $repository;

    public function __construct() {
        $this->repository = new DashboardRepository();
    }

    public function compilarEstadisticas($sucursalId) {
        // Validación básica: Si no hay sucursal, asumimos la matriz (1)
        if (empty($sucursalId) || !is_numeric($sucursalId)) {
            $sucursalId = 1;
        }

        return [
            'ingresos_hoy' => $this->repository->obtenerTotalIngresosHoy($sucursalId),
            'pasajes_hoy' => $this->repository->contarPasajesVendidosHoy($sucursalId),
            'encomiendas_activas' => $this->repository->contarEncomiendasActivas(),
            'valores_pendientes' => $this->repository->contarValoresPendientes()
        ];
    }

    public function compilarActividadReciente($sucursalId) {
        if (empty($sucursalId) || !is_numeric($sucursalId)) {
            $sucursalId = 1;
        }
        return $this->repository->obtenerActividadReciente($sucursalId);
    }
}
?>