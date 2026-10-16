<?php
namespace Modules\Finanzas;

class FinanzasService {
    private $repository;

    public function __construct() {
        $this->repository = new FinanzasRepository();
    }

    public function compilarDatosIniciales($filtros = []) {
        return [
            'sucursales' => $this->repository->obtenerSucursales(),
            'operadores' => $this->repository->obtenerOperadoresDisponibles(),
            'liquidacion' => $this->repository->obtenerReporteLiquidacionSocios($filtros)
        ];
    }

    public function procesarGuardarSucursal($postData) {
        $idSucursal = intval($postData['id_sucursal']);
        $comision = floatval($postData['comision']);
        $gasto = floatval($postData['gasto']);

        if ($comision < 0 || $comision > 100) {
            return "La comisión debe ser un valor entre 0 y 100%.";
        }
        if ($gasto < 0) {
            return "El gasto fijo diario no puede ser negativo.";
        }

        return $this->repository->actualizarSucursalBD($idSucursal, $comision, $gasto);
    }
}
?>