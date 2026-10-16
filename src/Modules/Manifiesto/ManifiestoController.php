<?php
namespace Modules\Manifiesto;

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ManifiestoController {
    private $service;
    private $repository;
    private $encomiendasRepo;

    public function __construct() {
        $this->service = new ManifiestoService();
        $this->repository = new ManifiestoRepository();
        $this->encomiendasRepo = new \Modules\Encomiendas\EncomiendasRepository();
    }

    public function index() {
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $tab = $_GET['tab'] ?? 'viaje';

        $data = $this->service->obtenerDatosIniciales($fecha_inicio, $fecha_fin, $pagina, $tab);
        extract($data); 
        
        $q = $_GET; unset($q['url'], $q['pagina']);
        $queryString = http_build_query($q);

        $titulo = "Manifiestos - Suramerica 2030";
        $vista = __DIR__ . '/Views/manifiesto.php';
        require_once __DIR__ . '/../../Platform/Views/layout.php';
    }

    public function imprimir() {
        // Opción A: Hoy / En Ruta
        if (isset($_GET['hoy']) && $_GET['hoy'] == 1) {
            $fecha = date('Y-m-d');
            $tipo_logistica = $_GET['tipo_logistica'] ?? 'ESTANDAR';
            $ruta = ['RUT_ORIGEN' => 'GENERAL / EN RUTA', 'RUT_DESTINO' => 'TODAS LAS RUTAS'];
            $bus = ['BUS_PLACA' => 'VARIOS', 'BUS_MARCA' => 'SISTEMA'];
            $pasajeros = $this->repository->obtenerPasajerosHoy();
            
            $formato = $this->service->prepararFormatoLogistica($ruta, $tipo_logistica);
            extract($formato);
            $esHoy = true;

            require_once __DIR__ . '/Views/manifiesto_print.php';
            return;
        }

        // Opción B: Consolidado (Múltiples viajes seleccionados)
        if (isset($_GET['consolidado']) && $_GET['consolidado'] == 1 && !empty($_GET['viajes'])) {
            $viajesRaw = explode(',', $_GET['viajes']);
            $pasajeros = $this->repository->obtenerPasajerosConsolidados($viajesRaw);
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $tipo_logistica = $_GET['tipo_logistica'] ?? 'ESTANDAR';

            $rutaId = (int)($_GET['ruta'] ?? 0);
            $busId = (int)($_GET['bus'] ?? 0);
            $datosVehiculo = $this->repository->obtenerDatosRutaYBus($rutaId, $busId);
            $ruta = $datosVehiculo['ruta'] ?? ['RUT_ORIGEN' => 'CONSOLIDADO', 'RUT_DESTINO' => 'CONSOLIDADO'];
            $bus = $datosVehiculo['bus'] ?? ['BUS_PLACA' => 'CONSOLIDADO', 'BUS_MARCA' => 'CONSOLIDADO'];

            if (!empty($pasajeros) && empty($datosVehiculo['ruta'])) {
                $ruta = [
                    'RUT_ORIGEN' => $pasajeros[0]['RUT_ORIGEN'] ?? 'CONSOLIDADO',
                    'RUT_DESTINO' => $pasajeros[0]['RUT_DESTINO'] ?? 'CONSOLIDADO'
                ];
            }

            $formato = $this->service->prepararFormatoLogistica($ruta, $tipo_logistica);
            extract($formato);
            $esConsolidado = true;

            require_once __DIR__ . '/Views/manifiesto_print.php';
            return;
        }

        // Opción C: Individual
        if (isset($_GET['fecha']) && isset($_GET['ruta']) && isset($_GET['bus'])) {
            $fecha = $_GET['fecha'];
            $rutaId = (int)$_GET['ruta'];
            $busId = (int)$_GET['bus'];
            $tipo_logistica = $_GET['tipo_logistica'] ?? 'ESTANDAR';

            $datosVehiculo = $this->repository->obtenerDatosRutaYBus($rutaId, $busId);
            $ruta = $datosVehiculo['ruta'];
            $bus = $datosVehiculo['bus'];

            $pasajeros = $this->repository->obtenerPasajerosPorViaje($fecha, $rutaId, $busId);
            
            $formato = $this->service->prepararFormatoLogistica($ruta, $tipo_logistica);
            extract($formato);

            require_once __DIR__ . '/Views/manifiesto_print.php';
        }
    }

    public function imprimirEquipaje() {
        // Consolidado Equipaje
        if (isset($_GET['consolidado']) && $_GET['consolidado'] == 1 && !empty($_GET['viajes'])) {
            $viajesRaw = explode(',', $_GET['viajes']);
            $equipajes = $this->repository->obtenerEquipajeConsolidado($viajesRaw);
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            $rutaId = (int)($_GET['ruta'] ?? 0);
            $busId = (int)($_GET['bus'] ?? 0);
            $datosVehiculo = $this->repository->obtenerDatosRutaYBus($rutaId, $busId);
            $ruta = $datosVehiculo['ruta'] ?? ['RUT_ORIGEN' => 'CONSOLIDADO', 'RUT_DESTINO' => 'CONSOLIDADO'];
            $bus = $datosVehiculo['bus'] ?? ['BUS_PLACA' => 'CONSOLIDADO', 'BUS_MARCA' => 'CONSOLIDADO'];

            $empresa = "SURAMERICA 2030 S.A.";
            $origenPrint = mb_strtoupper($ruta['RUT_ORIGEN']);
            $destinoPrint = mb_strtoupper($ruta['RUT_DESTINO']);
            $esConsolidado = true;

            require_once __DIR__ . '/Views/equipaje_lista_print.php';
            return;
        }

        if (isset($_GET['fecha']) && isset($_GET['ruta']) && isset($_GET['bus'])) {
            $fecha = $_GET['fecha'];
            $rutaId = (int)$_GET['ruta'];
            $busId = (int)$_GET['bus'];

            $datosVehiculo = $this->repository->obtenerDatosRutaYBus($rutaId, $busId);
            $ruta = $datosVehiculo['ruta'];
            $bus = $datosVehiculo['bus'];

            $equipajes = $this->repository->obtenerEquipajePorViaje($fecha, $rutaId, $busId);

            $empresa = "SURAMERICA 2030 S.A.";
            $origenPrint = mb_strtoupper($ruta['RUT_ORIGEN']);
            $destinoPrint = mb_strtoupper($ruta['RUT_DESTINO']);

            if (stripos($origenPrint, 'MANTA') !== false && (stripos($destinoPrint, 'CUCUTA') !== false || stripos($destinoPrint, 'CARACAS') !== false)) {
                $empresa = "COOPERATIVA DE TRANSPORTES PANAMERICANA INTERNACIONAL";
                $origenPrint = "QUITO";
                $destinoPrint = "CUCUTA";
            }

            require_once __DIR__ . '/Views/equipaje_lista_print.php';
        }
    }

    public function unificarViajes() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $viajes = $_POST['viajes_a_unificar'] ?? [];
            $busDestinoId = (int)($_POST['bus_destino_unificado'] ?? 0);
            $rutaDestinoId = (int)($_POST['ruta_destino_unificada'] ?? 0);
            $fechaDestino = trim($_POST['fecha_destino_unificada'] ?? '');

            if (empty($viajes) || count($viajes) < 2) {
                header("Location: /suramerica2030/manifiesto?tab=viaje&status=error&msg=" . urlencode("Debes seleccionar al menos 2 viajes para integrarlos en un solo bus."));
                exit();
            }

            if ($busDestinoId <= 0 || $rutaDestinoId <= 0 || empty($fechaDestino)) {
                header("Location: /suramerica2030/manifiesto?tab=viaje&status=error&msg=" . urlencode("Debes indicar el bus asignado, ruta y fecha de viaje."));
                exit();
            }

            $res = $this->repository->unificarViajesBD($viajes, $busDestinoId, $rutaDestinoId, $fechaDestino);
            if ($res === true) {
                header("Location: /suramerica2030/manifiesto?tab=viaje&status=success_unificar&fecha_v=" . urlencode($fechaDestino) . "&ruta_v=" . $rutaDestinoId . "&bus_v=" . $busDestinoId);
            } else {
                header("Location: /suramerica2030/manifiesto?tab=viaje&status=error&msg=" . urlencode("Error al unificar viajes: " . $res));
            }
            exit();
        }
    }

    public function enviarManifiesto() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fecha = $_POST['fecha_envio'];
            $rutaId = (int)$_POST['ruta_envio'];
            $busId = (int)$_POST['bus_envio'];
            $correoDestino = $_POST['correo_destino'];
            $tipo_logistica = $_POST['tipo_logistica'] ?? 'ESTANDAR';

            $datosVehiculo = $this->repository->obtenerDatosRutaYBus($rutaId, $busId);
            $ruta = $datosVehiculo['ruta'];
            $bus = $datosVehiculo['bus'];

            $pasajeros = $this->repository->obtenerPasajerosPorViaje($fecha, $rutaId, $busId);

            $formato = $this->service->prepararFormatoLogistica($ruta, $tipo_logistica);
            extract($formato);

            $is_email = true; 
            ob_start();
            require_once __DIR__ . '/Views/manifiesto_print.php';
            $html = ob_get_clean();

            $this->enviarPdfPorCorreo(
                $correoDestino,
                "Manifiesto de Pasajeros: {$ruta['RUT_ORIGEN']} -> {$ruta['RUT_DESTINO']} ($fecha)",
                "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto oficial de pasajeros generado por el sistema para el viaje de <b>{$fecha}</b>.</p>",
                $html,
                "Manifiesto_{$fecha}.pdf",
                "/suramerica2030/manifiesto?tab=viaje"
            );
        }
    }

    public function enviarManifiestoHoy() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $correoDestino = $_POST['correo_destino'];
            $tipo_logistica = $_POST['tipo_logistica'] ?? 'ESTANDAR';

            $fecha = date('Y-m-d');
            $ruta = ['RUT_ORIGEN' => 'GENERAL / EN RUTA', 'RUT_DESTINO' => 'TODAS LAS RUTAS'];
            $bus = ['BUS_PLACA' => 'VARIOS', 'BUS_MARCA' => 'SISTEMA'];
            $pasajeros = $this->repository->obtenerPasajerosHoy();

            $formato = $this->service->prepararFormatoLogistica($ruta, $tipo_logistica);
            extract($formato);
            $esHoy = true;

            $is_email = true;
            ob_start();
            require_once __DIR__ . '/Views/manifiesto_print.php';
            $html = ob_get_clean();

            $this->enviarPdfPorCorreo(
                $correoDestino,
                "Manifiesto General de Pasajeros de Hoy (" . date('d/m/Y') . ")",
                "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto oficial general con <b>todos los pasajeros que viajan el día de hoy</b>.</p>",
                $html,
                "Manifiesto_Pasajeros_Hoy_" . date('Ymd') . ".pdf",
                "/suramerica2030/manifiesto?tab=viaje"
            );
        }
    }

    public function enviarManifiestoConsolidado() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $viajes = $_POST['viajes_consolidados'] ?? [];
            $correoDestino = trim($_POST['correo_destino'] ?? '');
            $rutaId = (int)($_POST['ruta_id_ref'] ?? 0);
            $busId = (int)($_POST['bus_id_ref'] ?? 0);
            $fecha = trim($_POST['fecha_ref'] ?? date('Y-m-d'));
            $tipo_logistica = $_POST['tipo_logistica'] ?? 'ESTANDAR';

            if (empty($viajes) || empty($correoDestino)) {
                header("Location: /suramerica2030/manifiesto?tab=viaje&status=error&msg=" . urlencode("Datos incompletos para enviar el correo consolidado."));
                exit();
            }

            $pasajeros = $this->repository->obtenerPasajerosConsolidados($viajes);
            $datosVehiculo = $this->repository->obtenerDatosRutaYBus($rutaId, $busId);
            $ruta = $datosVehiculo['ruta'] ?? ['RUT_ORIGEN' => 'CONSOLIDADO', 'RUT_DESTINO' => 'CONSOLIDADO'];
            $bus = $datosVehiculo['bus'] ?? ['BUS_PLACA' => 'CONSOLIDADO', 'BUS_MARCA' => 'CONSOLIDADO'];

            if (!empty($pasajeros) && empty($datosVehiculo['ruta'])) {
                $ruta = [
                    'RUT_ORIGEN' => $pasajeros[0]['RUT_ORIGEN'] ?? 'CONSOLIDADO',
                    'RUT_DESTINO' => $pasajeros[0]['RUT_DESTINO'] ?? 'CONSOLIDADO'
                ];
            }

            $formato = $this->service->prepararFormatoLogistica($ruta, $tipo_logistica);
            extract($formato);
            $esConsolidado = true;

            $is_email = true;
            ob_start();
            require_once __DIR__ . '/Views/manifiesto_print.php';
            $html = ob_get_clean();

            $this->enviarPdfPorCorreo(
                $correoDestino,
                "Manifiesto Consolidado de Pasajeros: {$ruta['RUT_ORIGEN']} -> {$ruta['RUT_DESTINO']} ({$fecha})",
                "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto oficial consolidado de pasajeros para los viajes seleccionados de la fecha <b>{$fecha}</b>.</p>",
                $html,
                "Manifiesto_Consolidado_Pasajeros_{$fecha}.pdf",
                "/suramerica2030/manifiesto?tab=viaje"
            );
        }
    }

    // ==========================================
    // MÉTODOS PARA MANIFIESTO DE CARGA
    // ==========================================

    public function imprimirCarga() {
        if (isset($_GET['en_transito']) && $_GET['en_transito'] == 1) {
            $this->imprimirEnTransito();
            return;
        }

        // Modo Consolidado (Múltiples despachos)
        if (isset($_GET['consolidado']) && $_GET['consolidado'] == 1 && !empty($_GET['despachos'])) {
            $despachosRaw = explode(',', $_GET['despachos']);
            $paquetes = $this->encomiendasRepo->obtenerListaCargaConsolidada($despachosRaw);
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            $rutaId = (int)($_GET['ruta_id'] ?? 0);
            $todasRutas = $this->encomiendasRepo->obtenerRutas();
            $ruta = null;
            if ($rutaId > 0) {
                foreach ($todasRutas as $r) {
                    if ($r['ID_RUTA'] == $rutaId) { $ruta = $r; break; }
                }
            }
            if (!$ruta && !empty($paquetes)) {
                $ruta = [
                    'RUT_ORIGEN' => $paquetes[0]['RUT_ORIGEN'] ?? 'Varios',
                    'RUT_DESTINO' => $paquetes[0]['RUT_DESTINO'] ?? 'Varios'
                ];
            }
            $esConsolidado = true;
            require_once __DIR__ . '/../Encomiendas/Views/encomiendas_print.php';
            return;
        }

        if (isset($_GET['fecha']) && isset($_GET['ruta_id'])) {
            $fecha = $_GET['fecha'];
            $rutaId = (int)$_GET['ruta_id'];
            $rutas = $this->encomiendasRepo->obtenerRutas();
            $ruta = null;
            foreach ($rutas as $r) {
                if ($r['ID_RUTA'] == $rutaId) { $ruta = $r; break; }
            }
            $paquetes = $this->encomiendasRepo->obtenerListaCarga($fecha, $rutaId);
            require_once __DIR__ . '/../Encomiendas/Views/encomiendas_print.php';
        }
    }

    public function imprimirEnTransito() {
        $rutaId = isset($_GET['ruta_id']) && !empty($_GET['ruta_id']) ? (int)$_GET['ruta_id'] : null;
        $paquetes = $this->encomiendasRepo->obtenerListaCargaEnTransito($rutaId);
        
        $ruta = null;
        if ($rutaId) {
            $todasRutas = $this->encomiendasRepo->obtenerRutas();
            foreach ($todasRutas as $r) {
                if ($r['ID_RUTA'] == $rutaId) {
                    $ruta = $r;
                    break;
                }
            }
        }
        
        $esEnTransito = true;
        $fecha = date('Y-m-d H:i');
        require_once __DIR__ . '/../Encomiendas/Views/encomiendas_print.php';
    }

    public function unificarDespachos() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $despachos = $_POST['despachos_a_unificar'] ?? [];
            $rutaDestinoId = (int)($_POST['ruta_destino_unificada'] ?? 0);
            $fechaDestino = trim($_POST['fecha_destino_unificada'] ?? '');

            if (empty($despachos) || count($despachos) < 2) {
                header("Location: /suramerica2030/manifiesto?tab=carga&status=error&msg=" . urlencode("Debes seleccionar al menos 2 despachos para integrarlos en uno solo."));
                exit();
            }

            if ($rutaDestinoId <= 0 || empty($fechaDestino)) {
                header("Location: /suramerica2030/manifiesto?tab=carga&status=error&msg=" . urlencode("Debes indicar la ruta y fecha de viaje para el despacho integrado."));
                exit();
            }

            $res = $this->encomiendasRepo->unificarDespachosBD($despachos, $rutaDestinoId, $fechaDestino);
            if ($res === true) {
                header("Location: /suramerica2030/manifiesto?tab=carga&status=success_unificar&fecha_d=" . urlencode($fechaDestino) . "&ruta_d=" . $rutaDestinoId);
            } else {
                header("Location: /suramerica2030/manifiesto?tab=carga&status=error&msg=" . urlencode("Error al unificar: " . $res));
            }
            exit();
        }
    }

    public function enviarManifiestoCarga() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fecha = $_POST['fecha_envio'];
            $rutaId = (int)$_POST['ruta_envio'];
            $correoDestino = $_POST['correo_destino'];

            $rutas = $this->encomiendasRepo->obtenerRutas();
            $ruta = null;
            foreach ($rutas as $r) {
                if ($r['ID_RUTA'] == $rutaId) { $ruta = $r; break; }
            }
            $paquetes = $this->encomiendasRepo->obtenerListaCarga($fecha, $rutaId);

            ob_start();
            $is_email = true;
            require_once __DIR__ . '/../Encomiendas/Views/encomiendas_print.php';
            $html = ob_get_clean();

            $orig = $ruta['RUT_ORIGEN'] ?? 'Origen';
            $dest = $ruta['RUT_DESTINO'] ?? 'Destino';
            $this->enviarPdfPorCorreo(
                $correoDestino,
                "Manifiesto de Carga: {$orig} -> {$dest} ($fecha)",
                "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto oficial de carga generado por el sistema para el viaje de <b>{$fecha}</b>.</p>",
                $html,
                "Manifiesto_Carga_{$fecha}.pdf",
                "/suramerica2030/manifiesto?tab=carga"
            );
        }
    }

    public function enviarManifiestoEnTransito() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $rutaId = !empty($_POST['ruta_id']) ? (int)$_POST['ruta_id'] : null;
            $correoDestino = $_POST['correo_destino'] ?? '';

            $paquetes = $this->encomiendasRepo->obtenerListaCargaEnTransito($rutaId);
            $ruta = null;
            if ($rutaId) {
                $todasRutas = $this->encomiendasRepo->obtenerRutas();
                foreach ($todasRutas as $r) {
                    if ($r['ID_RUTA'] == $rutaId) { $ruta = $r; break; }
                }
            }

            $esEnTransito = true;
            $fecha = date('Y-m-d H:i');

            ob_start();
            $is_email = true;
            require_once __DIR__ . '/../Encomiendas/Views/encomiendas_print.php';
            $html = ob_get_clean();

            $this->enviarPdfPorCorreo(
                $correoDestino,
                "Manifiesto de Carga General - Encomiendas en Tránsito (" . date('d/m/Y') . ")",
                "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto oficial generado por el sistema con <b>todas las encomiendas que se encuentran en tránsito actualmente</b>.</p>",
                $html,
                "Manifiesto_En_Transito_" . date('Ymd_His') . ".pdf",
                "/suramerica2030/manifiesto?tab=carga"
            );
        }
    }

    public function enviarManifiestoCargaConsolidado() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $despachos = $_POST['despachos_consolidados'] ?? [];
            $correoDestino = trim($_POST['correo_destino'] ?? '');
            $rutaId = (int)($_POST['ruta_id_ref'] ?? 0);
            $fecha = trim($_POST['fecha_ref'] ?? date('Y-m-d'));

            if (empty($despachos) || empty($correoDestino)) {
                header("Location: /suramerica2030/manifiesto?tab=carga&status=error&msg=" . urlencode("Datos incompletos para enviar el correo consolidado."));
                exit();
            }

            $paquetes = $this->encomiendasRepo->obtenerListaCargaConsolidada($despachos);
            $todasRutas = $this->encomiendasRepo->obtenerRutas();
            $ruta = null;
            if ($rutaId > 0) {
                foreach ($todasRutas as $r) {
                    if ($r['ID_RUTA'] == $rutaId) { $ruta = $r; break; }
                }
            }
            if (!$ruta && !empty($paquetes)) {
                $ruta = [
                    'RUT_ORIGEN' => $paquetes[0]['RUT_ORIGEN'] ?? 'Varios',
                    'RUT_DESTINO' => $paquetes[0]['RUT_DESTINO'] ?? 'Varios'
                ];
            }

            $esConsolidado = true;
            ob_start();
            $is_email = true;
            require_once __DIR__ . '/../Encomiendas/Views/encomiendas_print.php';
            $html = ob_get_clean();

            $orig = $ruta['RUT_ORIGEN'] ?? 'Origen';
            $dest = $ruta['RUT_DESTINO'] ?? 'Destino';
            $this->enviarPdfPorCorreo(
                $correoDestino,
                "Manifiesto Consolidado de Carga: {$orig} -> {$dest} ({$fecha})",
                "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto Consolidado oficial de carga con los despachos integrados para el viaje de <b>{$fecha}</b>.</p>",
                $html,
                "Manifiesto_Consolidado_Carga_{$fecha}.pdf",
                "/suramerica2030/manifiesto?tab=carga"
            );
        }
    }

    // ==========================================
    // MÉTODO PRIVADO AUXILIAR PARA ENVÍO DE EMAIL
    // ==========================================
    private function enviarPdfPorCorreo($correoDestino, $asunto, $cuerpoHtml, $htmlPdf, $nombreArchivoPdf, $urlRetorno) {
        try {
            $dompdf = new Dompdf();
            $dompdf->loadHtml($htmlPdf);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $pdfOutput = $dompdf->output();

            $configFile = __DIR__ . '/../../Platform/Storage/email_config.json';
            $config = json_decode(file_get_contents($configFile), true);

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $config['smtp_host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_user'] ?? ''; 
            $mail->Password   = $config['smtp_pass'] ?? '';       
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $config['smtp_port'] ?? 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($config['smtp_user'] ?? '', $config['smtp_from_name'] ?? 'Suramerica 2030');
            $mail->addAddress($correoDestino);
            
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoHtml;
            
            $mail->addStringAttachment($pdfOutput, $nombreArchivoPdf);
            $mail->send();
            
            $separator = (strpos($urlRetorno, '?') !== false) ? '&' : '?';
            header("Location: " . $urlRetorno . $separator . "status=success_email");
        } catch (Exception $e) {
            $separator = (strpos($urlRetorno, '?') !== false) ? '&' : '?';
            $errorMsg = isset($mail) && !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
            header("Location: " . $urlRetorno . $separator . "status=error&msg=" . urlencode($errorMsg));
        }
        exit();
    }
}