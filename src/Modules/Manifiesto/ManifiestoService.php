<?php
namespace Modules\Manifiesto;

class ManifiestoService {
    private $repository;
    private $encomiendasRepo;

    public function __construct() {
        $this->repository = new ManifiestoRepository();
        $this->encomiendasRepo = new \Modules\Encomiendas\EncomiendasRepository();
    }

    public function obtenerDatosIniciales($fecha_inicio, $fecha_fin, $pagina = 1, $tab = 'viaje') {
        $limite = 10;
        $offset = ($pagina - 1) * $limite;

        $datos = [
            'tabActive' => $tab,
            'directorioCorreos' => $this->repository->obtenerDirectorioCorreos(),
            'rutas' => $this->repository->obtenerRutas(),
            'buses' => $this->repository->obtenerBuses(),
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ];

        if ($tab === 'carga') {
            $despachos = $this->encomiendasRepo->obtenerDespachosProgramados($fecha_inicio, $fecha_fin);
            $datos['despachos'] = $despachos;
            $datos['totalEnTransito'] = $this->encomiendasRepo->contarEncomiendasEnTransito();
            $datos['totalRegistros'] = count($despachos);
            $datos['totalPaginas'] = 1;
            $datos['pagina'] = 1;
        } else {
            $totalRegistros = $this->repository->contarViajesProgramados($fecha_inicio, $fecha_fin);
            $datos['viajes'] = $this->repository->obtenerViajesProgramados($fecha_inicio, $fecha_fin, $offset, $limite);
            $datos['totalPasajerosHoy'] = $this->repository->contarPasajerosHoy();
            $datos['totalPaginas'] = ceil($totalRegistros / $limite);
            $datos['paginaActual'] = $pagina;
            $datos['pagina'] = $pagina;
            $datos['totalRegistros'] = $totalRegistros;
        }

        return $datos;
    }

    public function prepararFormatoLogistica($ruta, $tipo_logistica) {
        $formato = [
            'empresa' => "SURAMERICA 2030 S.A.",
            'origenPrint' => mb_strtoupper($ruta['RUT_ORIGEN']),
            'destinoPrint' => mb_strtoupper($ruta['RUT_DESTINO']),
            'paisDestino' => "VENEZUELA",
            'itinerarioPrint' => "DIRECTO: " . mb_strtoupper($ruta['RUT_ORIGEN']) . " - " . mb_strtoupper($ruta['RUT_DESTINO'])
        ];

        if ($tipo_logistica === 'PANAMERICANA') {
            $formato['empresa'] = "COOPERATIVA DE TRANSPORTES PANAMERICANA INTERNACIONAL";
            $formato['origenPrint'] = "QUITO";
            $formato['destinoPrint'] = "CUCUTA";
            $formato['paisDestino'] = "COLOMBIA";
            $formato['itinerarioPrint'] = "DIRECTO: QUITO - CUCUTA";
        } elseif ($tipo_logistica === 'CONEXIONES') {
            $formato['origenPrint'] = "MANTA";
            $formato['destinoPrint'] = "CARACAS";
            $formato['itinerarioPrint'] = "CONEX: MANTA-TULCÁN / IPIALES-CÚCUTA / SAN CRISTÓBAL-CARACAS";
        }

        return $formato;
    }
}
?>