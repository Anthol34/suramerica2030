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
        $origen = mb_strtoupper($ruta['RUT_ORIGEN'] ?? 'ECUADOR');
        $destino = mb_strtoupper($ruta['RUT_DESTINO'] ?? 'VENEZUELA');
        $tipo = strtoupper(trim((string)$tipo_logistica));

        $formato = [
            'empresa' => "SURAMERICA 2030 S.A.",
            'propietario' => "SURAMERICA 2030 S.A.",
            'origenPrint' => $origen,
            'destinoPrint' => $destino,
            'paisDestino' => "VENEZUELA",
            'itinerarioPrint' => "DIRECTO: {$origen} - {$destino}"
        ];

        if ($tipo === 'PANAMERICANA') {
            $formato['empresa'] = "COOPERATIVA DE TRANSPORTES PANAMERICANA INTERNACIONAL";
            $formato['propietario'] = "COOPERATIVA DE TRANSPORTES PANAMERICANA INTERNACIONAL";
            $formato['origenPrint'] = $origen;
            $formato['destinoPrint'] = "CÚCUTA";
            $formato['paisDestino'] = "COLOMBIA";
            $formato['itinerarioPrint'] = "INTERNACIONAL DIRECTO: {$origen} ➔ CÚCUTA (ENLACE BINACIONAL A VENEZUELA)";
        } elseif ($tipo === 'EXPRESOS_SURAMERICA' || $tipo === 'SURAMERICA') {
            $formato['empresa'] = "EXPRESOS SURAMÉRICA 2030 C.A.";
            $formato['propietario'] = "EXPRESOS SURAMÉRICA 2030 C.A.";
            $formato['origenPrint'] = "SAN CRISTÓBAL";
            $formato['destinoPrint'] = $destino;
            $formato['paisDestino'] = "VENEZUELA";
            $formato['itinerarioPrint'] = "TRONCAL VENEZOLANA: SAN CRISTÓBAL ➔ CARACAS ➔ {$destino}";
        } elseif ($tipo === 'PANA_EXPRESOS') {
            $formato['empresa'] = "ALIANZA PANAMERICANA & EXPRESOS SURAMÉRICA 2030";
            $formato['propietario'] = "PANAMERICANA / SURAMÉRICA 2030";
            $formato['origenPrint'] = $origen;
            $formato['destinoPrint'] = $destino;
            $formato['paisDestino'] = "VENEZUELA";
            $formato['itinerarioPrint'] = "ALIANZA ESTRATÉGICA: {$origen} ➔ CÚCUTA (PANAMERICANA) | SAN CRISTÓBAL ➔ CARACAS ➔ {$destino} (SURAMÉRICA)";
        } elseif ($tipo === 'CONEXIONES' || $tipo === 'CONEXION_TRAMOS') {
            $formato['empresa'] = "RED MULTIMODAL CONEXIONES INTERNACIONALES";
            $formato['propietario'] = "EMPRESAS LOCALES ALIADAS (ECUADOR - COLOMBIA - VENEZUELA)";
            $formato['origenPrint'] = $origen;
            $formato['destinoPrint'] = $destino;
            $formato['paisDestino'] = "VENEZUELA";
            $formato['itinerarioPrint'] = "CONEXIONES REGIONALES: {$origen} ➔ TULCÁN ➔ IPIALES ➔ CÚCUTA ➔ SAN CRISTÓBAL ➔ {$destino}";
        }

        return $formato;
    }
}
?>