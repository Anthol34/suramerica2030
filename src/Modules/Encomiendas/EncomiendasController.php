<?php
namespace Modules\Encomiendas;

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EncomiendasController {
    private $service;
    private $repository;

    public function __construct() {
        $this->service = new EncomiendasService();
        $this->repository = new EncomiendasRepository();
    }

    public function index() {
        if (isset($_GET['tab']) && $_GET['tab'] === 'carga') {
            $q = $_GET;
            unset($q['url']);
            header("Location: /suramerica2030/manifiesto?" . http_build_query($q));
            exit();
        }

        $filtros = ['tab' => $_GET['tab'] ?? 'activos', 'buscar' => $_GET['buscar'] ?? '', 'estado' => $_GET['estado'] ?? '', 'fecha_inicio' => $_GET['fecha_inicio'] ?? date('Y-m-01'), 'fecha_fin' => $_GET['fecha_fin'] ?? date('Y-m-t')];
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        
        $data = $this->service->obtenerDatosIniciales($filtros, $pagina);
        extract($data); 

        $q = $_GET; unset($q['url'], $q['pagina']);
        $queryString = http_build_query($q);
        
        $titulo = "Encomiendas - Suramerica 2030";
        $vista = __DIR__ . '/Views/encomiendas.php';
        require_once __DIR__ . '/../../Platform/Views/layout.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->service->procesarGuardar($_POST, $_SESSION['user_id'], $_SESSION['sucursal_id']);
            if (is_numeric($res)) header("Location: /suramerica2030/encomiendas?status=success_recepcion&print_ticket=" . $res);
            else header("Location: /suramerica2030/encomiendas?status=error&msg=" . urlencode($res));
            exit();
        }
    }

    private function tienePermisoDespacho() {
        return (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || 
               (isset($_SESSION['p_despacho']) && $_SESSION['p_despacho'] == 1);
    }

    private function tienePermisoLlegada() {
        return (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || 
               (isset($_SESSION['p_llegada']) && $_SESSION['p_llegada'] == 1);
    }

    public function actualizar() {
        if (isset($_GET['id_encomienda']) && isset($_GET['estado'])) {
            $estado = $_GET['estado'];
            if ($estado == 'En Tránsito' && !$this->tienePermisoDespacho()) {
                header("Location: /suramerica2030/encomiendas?status=error&msg=" . urlencode("Acceso denegado: Tu rol no tiene permisos para despachar encomiendas."));
                exit();
            }
            if ($estado == 'Entregado' && !$this->tienePermisoLlegada()) {
                header("Location: /suramerica2030/encomiendas?status=error&msg=" . urlencode("Acceso denegado: Tu rol no tiene permisos para registrar llegada o entrega de encomiendas."));
                exit();
            }

            $res = $this->repository->actualizarEstadoBD($_GET['id_encomienda'], $estado);
            if ($res) header("Location: /suramerica2030/encomiendas?status=success");
            else header("Location: /suramerica2030/encomiendas?status=error&msg=No_se_pudo_actualizar");
            exit();
        }
    }

    public function despachar() {
        if (!$this->tienePermisoDespacho()) {
            header("Location: /suramerica2030/encomiendas?status=error&msg=" . urlencode("Acceso denegado: Tu rol no tiene permisos para despachar encomiendas."));
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['encomiendas_ids'])) {
            $res = $this->repository->despacharEncomiendasBD($_POST['encomiendas_ids'], $_POST['fecha_despacho']);
            if (is_array($res)) header("Location: /suramerica2030/encomiendas?status=success_despacho&print_despacho=1&fecha_d=".$res['fecha']."&ruta_d=".$res['ruta']);
            else header("Location: /suramerica2030/encomiendas?status=error&msg=".urlencode($res));
            exit();
        }
    }

    public function entregarMultiples() {
        if (!$this->tienePermisoLlegada()) {
            header("Location: /suramerica2030/encomiendas?status=error&msg=" . urlencode("Acceso denegado: Tu rol no tiene permisos para registrar llegada o entrega de encomiendas."));
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['encomiendas_ids'])) {
            $res = $this->repository->entregarEncomiendasBD($_POST['encomiendas_ids']);
            if ($res) header("Location: /suramerica2030/encomiendas?status=success_entregado");
            else header("Location: /suramerica2030/encomiendas?status=error");
            exit();
        }
    }

    public function eliminar() {
        $permiso = (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) || (isset($_SESSION['p_anular']) && $_SESSION['p_anular'] == 1);
        if (!$permiso) { 
            header("Location: /suramerica2030/encomiendas?status=error&msg=No_tienes_permisos_para_anular"); exit(); 
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->repository->eliminarEncomiendaBD(intval($_POST['id_factura']), $_SESSION['user_id'], $_SESSION['sucursal_id']);
            if ($res === true) header("Location: /suramerica2030/encomiendas?status=success_delete");
            else header("Location: /suramerica2030/encomiendas?status=error&msg=" . urlencode($res));
            exit();
        }
    }

    public function solicitarAnulacion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['id_factura'])) {
            $pasajesRepo = new \Modules\Pasajes\PasajesRepository();
            $res = $pasajesRepo->solicitarAnulacionBD('ENCOMIENDA', (int)$_POST['id_factura'], (int)$_POST['id_factura'], $_SESSION['user_id'], $_SESSION['sucursal_id'], floatval($_POST['monto_devolver'] ?? 0), $_POST['motivo'] ?? '');
            if ($res === true) {
                header("Location: /suramerica2030/encomiendas?status=success&msg=" . urlencode("Solicitud de anulación enviada a Administración."));
            } else {
                header("Location: /suramerica2030/encomiendas?status=error&msg=" . urlencode($res));
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

    public function unificarDespachos() {
        if (!$this->tienePermisoDespacho()) {
            header("Location: /suramerica2030/encomiendas?tab=carga&status=error&msg=" . urlencode("Acceso denegado: Tu rol no tiene permisos para despachar encomiendas."));
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $despachos = $_POST['despachos_a_unificar'] ?? [];
            $rutaDestinoId = (int)($_POST['ruta_destino_unificada'] ?? 0);
            $fechaDestino = trim($_POST['fecha_destino_unificada'] ?? '');

            if (empty($despachos) || count($despachos) < 2) {
                header("Location: /suramerica2030/encomiendas?tab=carga&status=error&msg=" . urlencode("Debes seleccionar al menos 2 despachos para integrarlos en uno solo."));
                exit();
            }

            if ($rutaDestinoId <= 0 || empty($fechaDestino)) {
                header("Location: /suramerica2030/encomiendas?tab=carga&status=error&msg=" . urlencode("Debes indicar la ruta y fecha de viaje para el despacho integrado."));
                exit();
            }

            $res = $this->repository->unificarDespachosBD($despachos, $rutaDestinoId, $fechaDestino);
            if ($res === true) {
                header("Location: /suramerica2030/encomiendas?tab=carga&status=success_unificar&fecha_d=" . urlencode($fechaDestino) . "&ruta_d=" . $rutaDestinoId);
            } else {
                header("Location: /suramerica2030/encomiendas?tab=carga&status=error&msg=" . urlencode("Error al unificar: " . $res));
            }
            exit();
        }
    }

    public function imprimirLista() {
        if (isset($_GET['en_transito']) && $_GET['en_transito'] == 1) {
            $this->imprimirEnTransito();
            return;
        }

        // Modo Consolidado (Múltiples despachos seleccionados)
        if (isset($_GET['consolidado']) && $_GET['consolidado'] == 1 && !empty($_GET['despachos'])) {
            $despachosRaw = explode(',', $_GET['despachos']);
            $paquetes = $this->repository->obtenerListaCargaConsolidada($despachosRaw);
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            $rutaId = (int)($_GET['ruta_id'] ?? 0);
            $todasRutas = $this->repository->obtenerRutas();
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
            require_once __DIR__ . '/Views/encomiendas_print.php';
            return;
        }

        if (isset($_GET['fecha']) && isset($_GET['ruta_id'])) {
            $fecha = $_GET['fecha'];
            $rutaId = (int)$_GET['ruta_id'];
            $ruta = $this->repository->obtenerRutas(); 
            
            // CORREGIDO PARA COMPATIBILIDAD CON PHP 7.3
            $rutaElegida = array_filter($ruta, function($r) use ($rutaId) {
                return $r['ID_RUTA'] == $rutaId;
            });
            
            $ruta = reset($rutaElegida);
            $paquetes = $this->repository->obtenerListaCarga($fecha, $rutaId);
            
            require_once __DIR__ . '/Views/encomiendas_print.php';
        }
    }

    public function imprimirEnTransito() {
        $rutaId = isset($_GET['ruta_id']) && !empty($_GET['ruta_id']) ? (int)$_GET['ruta_id'] : null;
        $paquetes = $this->repository->obtenerListaCargaEnTransito($rutaId);
        
        $ruta = null;
        if ($rutaId) {
            $todasRutas = $this->repository->obtenerRutas();
            foreach ($todasRutas as $r) {
                if ($r['ID_RUTA'] == $rutaId) {
                    $ruta = $r;
                    break;
                }
            }
        }
        
        $esEnTransito = true;
        $fecha = date('Y-m-d H:i');
        
        require_once __DIR__ . '/Views/encomiendas_print.php';
    }

    public function enviarManifiestoEnTransito() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $rutaId = !empty($_POST['ruta_id']) ? (int)$_POST['ruta_id'] : null;
            $correoDestino = $_POST['correo_destino'] ?? '';

            $paquetes = $this->repository->obtenerListaCargaEnTransito($rutaId);
            $ruta = null;
            if ($rutaId) {
                $todasRutas = $this->repository->obtenerRutas();
                foreach ($todasRutas as $r) {
                    if ($r['ID_RUTA'] == $rutaId) {
                        $ruta = $r;
                        break;
                    }
                }
            }

            $esEnTransito = true;
            $fecha = date('Y-m-d H:i');

            ob_start();
            $is_email = true;
            require_once __DIR__ . '/Views/encomiendas_print.php';
            $html = ob_get_clean();

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $pdfOutput = $dompdf->output();

            $configFile = __DIR__ . '/../../Platform/Storage/email_config.json';
            $config = json_decode(file_get_contents($configFile), true);

            $mail = new PHPMailer(true);
            try {
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
                $mail->Subject = "Manifiesto de Carga General - Encomiendas en Tránsito (" . date('d/m/Y') . ")";
                $mail->Body    = "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto oficial generado por el sistema con <b>todas las encomiendas que se encuentran en tránsito actualmente</b>.</p>";

                $mail->addStringAttachment($pdfOutput, "Manifiesto_En_Transito_" . date('Ymd_His') . ".pdf");
                $mail->send();
                header("Location: /suramerica2030/encomiendas?tab=carga&status=success_email");
            } catch (Exception $e) {
                header("Location: /suramerica2030/encomiendas?tab=carga&status=error&msg=" . urlencode($mail->ErrorInfo));
            }
            exit();
        }
    }

    public function enviarManifiesto() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fecha = $_POST['fecha_envio'];
            $rutaId = (int)$_POST['ruta_envio'];
            $correoDestino = $_POST['correo_destino'];

            $ruta = $this->repository->obtenerRutas();
            
            // CORREGIDO PARA COMPATIBILIDAD CON PHP 7.3
            $rutaElegida = array_filter($ruta, function($r) use ($rutaId) {
                return $r['ID_RUTA'] == $rutaId;
            });
            
            $ruta = reset($rutaElegida);
            $paquetes = $this->repository->obtenerListaCarga($fecha, $rutaId);

            ob_start();
            $is_email = true;
            require_once __DIR__ . '/Views/encomiendas_print.php';
            $html = ob_get_clean();

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $pdfOutput = $dompdf->output();

            $configFile = __DIR__ . '/../../Platform/Storage/email_config.json';
            $config = json_decode(file_get_contents($configFile), true);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp_user']; 
                $mail->Password   = $config['smtp_pass'];       
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $config['smtp_port'];

                $mail->setFrom($config['smtp_user'], $config['smtp_from_name']);
                $mail->addAddress($correoDestino);
                
                // ... Aquí dejas igual la configuración del Body y el Attachment ...
                $mail->isHTML(true);
                $mail->Subject = "Manifiesto de Carga: {$ruta['RUT_ORIGEN']} -> {$ruta['RUT_DESTINO']} ($fecha)";
                $mail->Body    = "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto oficial generado por el sistema para el viaje de <b>{$fecha}</b>.</p>";
                
                $mail->addStringAttachment($pdfOutput, "Manifiesto_{$fecha}.pdf");
                $mail->send();
                header("Location: /suramerica2030/encomiendas?tab=carga&status=success_email");
            } catch (Exception $e) {
                header("Location: /suramerica2030/encomiendas?tab=carga&status=error&msg=" . urlencode($mail->ErrorInfo));
            }
            exit();
        }
    }

    public function enviarManifiestoConsolidado() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $despachos = $_POST['despachos_consolidados'] ?? [];
            $correoDestino = trim($_POST['correo_destino'] ?? '');
            $rutaId = (int)($_POST['ruta_id_ref'] ?? 0);
            $fecha = trim($_POST['fecha_ref'] ?? date('Y-m-d'));

            if (empty($despachos) || empty($correoDestino)) {
                header("Location: /suramerica2030/encomiendas?tab=carga&status=error&msg=" . urlencode("Datos incompletos para enviar el correo consolidado."));
                exit();
            }

            $paquetes = $this->repository->obtenerListaCargaConsolidada($despachos);
            $todasRutas = $this->repository->obtenerRutas();
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
            require_once __DIR__ . '/Views/encomiendas_print.php';
            $html = ob_get_clean();

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $pdfOutput = $dompdf->output();

            $configFile = __DIR__ . '/../../Platform/Storage/email_config.json';
            $config = json_decode(file_get_contents($configFile), true);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'] ?? '';
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp_user'] ?? ''; 
                $mail->Password   = $config['smtp_pass'] ?? '';       
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $config['smtp_port'] ?? 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($config['smtp_user'] ?? '', $config['smtp_from_name'] ?? 'Suramerica 2030');
                $mail->addAddress($correoDestino);
                
                $mail->isHTML(true);
                $orig = $ruta['RUT_ORIGEN'] ?? 'Origen';
                $dest = $ruta['RUT_DESTINO'] ?? 'Destino';
                $mail->Subject = "Manifiesto Consolidado de Carga: {$orig} -> {$dest} ({$fecha})";
                $mail->Body    = "<h3>Hola,</h3><p>Adjunto encontrarás el Manifiesto Consolidado oficial generado por el sistema con los despachos integrados para el viaje de <b>{$fecha}</b>.</p>";
                
                $mail->addStringAttachment($pdfOutput, "Manifiesto_Consolidado_{$fecha}.pdf");
                $mail->send();
                header("Location: /suramerica2030/encomiendas?tab=carga&status=success_email");
            } catch (Exception $e) {
                header("Location: /suramerica2030/encomiendas?tab=carga&status=error&msg=" . urlencode($mail->ErrorInfo));
            }
            exit();
        }
    }
}
?>