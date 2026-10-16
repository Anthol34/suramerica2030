<?php
namespace Modules\Caja;

class CajaController {
    private $service;
    public function __construct() { $this->service = new CajaService(); }

    private function verificarPermiso() {
        if (!isset($_SESSION['p_caja']) || $_SESSION['p_caja'] != 1) { 
            header("Location: /suramerica2030/dashboard"); exit();
        }
    }

    public function index() {
        $this->verificarPermiso();
        $fecha_consulta = $_GET['fecha'] ?? date('Y-m-d');
        $sucursal_id = ($_SESSION['rol_id'] == 1 && isset($_GET['sucursal'])) ? $_GET['sucursal'] : ($_SESSION['sucursal_id'] ?? 1);

        $data = $this->service->procesarDatosCaja($sucursal_id, $fecha_consulta);
        extract($data); 
        $sugerenciaApertura = $this->service->obtenerSugerenciaApertura($sucursal_id, $fecha_consulta);

        $titulo = "Reporte de Caja - Suramerica 2030";
        $vista = __DIR__ . '/Views/caja.php';
        require_once '../src/Platform/Views/layout.php';
    }

    public function guardar() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->service->registrarMovimientoManual($_POST, $_SESSION['user_id'] ?? 1, $_SESSION['sucursal_id'] ?? 1);
            $f = $_POST['fecha_actual'] ?? date('Y-m-d');
            if ($res['status'] === 'success') header("Location: /suramerica2030/caja?fecha={$f}&status=success");
            else header("Location: /suramerica2030/caja?fecha={$f}&status=error&msg=" . urlencode($res['mensaje']));
            exit();
        }
    }

    public function abrir() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sucursal_id = ($_SESSION['rol_id'] == 1 && !empty($_POST['sucursal_id']) && $_POST['sucursal_id'] !== 'ALL') 
                ? intval($_POST['sucursal_id']) 
                : ($_SESSION['sucursal_id'] ?? 1);

            $f = $_POST['fecha_apertura'] ?? date('Y-m-d');
            $redirectTo = $_POST['redirect_to'] ?? 'caja';

            $res = $this->service->abrirCaja($_POST, $_SESSION['user_id'] ?? 1, $sucursal_id);
            if ($res['status'] === 'success_apertura') {
                header("Location: /suramerica2030/caja/{$redirectTo}?fecha={$f}&sucursal={$sucursal_id}&status=success_apertura");
            } else {
                header("Location: /suramerica2030/caja/{$redirectTo}?fecha={$f}&sucursal={$sucursal_id}&status=error&msg=" . urlencode($res['mensaje'] ?? 'Error al abrir la caja'));
            }
            exit();
        }
    }

    public function cerrar() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sucursal_id = ($_SESSION['rol_id'] == 1 && !empty($_POST['sucursal_id']) && $_POST['sucursal_id'] !== 'ALL') 
                ? intval($_POST['sucursal_id']) 
                : ($_SESSION['sucursal_id'] ?? 1);

            $f = $_POST['fecha_cierre'] ?? date('Y-m-d');
            $redirectTo = $_POST['redirect_to'] ?? 'historial_cierres';
            
            // CANDADO: Bloqueo de cierre si hay egresos pendientes de aprobación
            $pendientes = $this->service->contarEgresosPendientes($sucursal_id, $f);
            if ($pendientes > 0) {
                header("Location: /suramerica2030/caja/{$redirectTo}?fecha={$f}&sucursal={$sucursal_id}&status=error&msg=" . urlencode("No se puede cerrar la caja: Existen {$pendientes} egreso(s) pendiente(s) de aprobación por supervisor. Deben ser aprobados o rechazados antes del corte."));
                exit();
            }

            $res = $this->service->cerrarCaja($_POST, $_SESSION['user_id'] ?? 1, $sucursal_id);
            if ($res['status'] === 'success_cierre') {
                header("Location: /suramerica2030/caja/{$redirectTo}?fecha={$f}&sucursal={$sucursal_id}&status=success_cierre");
            } else {
                header("Location: /suramerica2030/caja/{$redirectTo}?fecha={$f}&sucursal={$sucursal_id}&status=error&msg=" . urlencode($res['mensaje'] ?? 'Error al procesar el cierre'));
            }
            exit();
        }
    }

    public function moverfecha() {
        $this->verificarPermiso();

        $f = $_POST['fecha_actual'] ?? date('Y-m-d');
        $sucursal_id = $_POST['sucursal_actual'] ?? ($_SESSION['sucursal_id'] ?? 1);
        $redirectTo = $_POST['redirect_to'] ?? 'caja';

        $baseRedirect = ($redirectTo === 'movimientos') 
            ? "/suramerica2030/caja/movimientos" 
            : "/suramerica2030/caja?fecha={$f}&sucursal={$sucursal_id}";

        $delim = (strpos($baseRedirect, '?') !== false) ? '&' : '?';

        // Candado de Seguridad: Solo el Administrador (rol_id == 1) puede reasignar fechas
        if (!isset($_SESSION['rol_id']) || intval($_SESSION['rol_id']) !== 1) {
            header("Location: {$baseRedirect}{$delim}status=error&msg=" . urlencode("Acceso Denegado: Únicamente el Administrador general tiene autorización para reasignar fechas de transacciones en caja."));
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $movimientosIds = $_POST['movimientos_ids'] ?? [];
            $nuevaFecha = trim($_POST['nueva_fecha'] ?? '');
            $usuarioId = $_SESSION['user_id'] ?? 1;

            if (empty($movimientosIds)) {
                header("Location: {$baseRedirect}{$delim}status=error&msg=" . urlencode("Debes seleccionar al menos una transacción para mover de fecha."));
                exit();
            }

            if (empty($nuevaFecha)) {
                header("Location: {$baseRedirect}{$delim}status=error&msg=" . urlencode("Debes indicar la fecha de destino."));
                exit();
            }

            $res = $this->service->moverFechaMovimientos($movimientosIds, $nuevaFecha, $usuarioId);

            if ($res['status'] === 'success') {
                $count = $res['count'];
                header("Location: {$baseRedirect}{$delim}status=success_mover&count={$count}&nueva_fecha=" . urlencode($nuevaFecha));
            } else {
                header("Location: {$baseRedirect}{$delim}status=error&msg=" . urlencode($res['mensaje']));
            }
            exit();
        }
    }

    public function imprimir() {
        $this->verificarPermiso();
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        
        $sucursal_id = $_SESSION['sucursal_id'] ?? 1;
        if ($_SESSION['rol_id'] == 1 && isset($_GET['sucursal'])) {
            $sucursal_id = $_GET['sucursal'];
        }

        // Usamos el servicio para traer todo listo y calculado
        $data = $this->service->procesarDatosCaja($sucursal_id, $fecha);
        extract($data); // Extrae $movimientos, $cierreDia, $totales, etc.
        
        $nombre_sucursal_impresion = ($sucursal_id === 'ALL') ? 'CONSOLIDADO GLOBAL (TODAS)' : ($_SESSION['ciudad_sucursal'] ?? 'Central');

        require_once __DIR__ . '/Views/caja_print.php';
    }
    public function aprobaciones() {
        $this->verificarPermiso();
        $sucursal_id = $_GET['sucursal'] ?? ($_SESSION['sucursal_id'] ?? 1);
        $subtab = $_GET['subtab'] ?? 'solicitudes';
        
        // --- LLAMADA DIRECTA Y LIGERA A LAS SUCURSALES ---
        $sucursales = $this->service->obtenerListaSucursales();
        
        // 1. SOLICITUDES DE ANULACIÓN DE VENTAS (PASAJE, ENCOMIENDA, VALOR)
        $pasajesRepo = new \Modules\Pasajes\PasajesRepository();
        $filtrosSolicitudes = [
            'buscar_solicitud' => $_GET['buscar_solicitud'] ?? '',
            'estado_solicitud' => $_GET['estado_solicitud'] ?? '',
            'tipo_venta' => $_GET['tipo_venta'] ?? ''
        ];
        $solicitudes = $pasajesRepo->obtenerSolicitudesAnulacion($filtrosSolicitudes);
        $cntSolicitudesPendientes = $pasajesRepo->contarSolicitudesPendientes();
        
        // 2. EGRESOS Y GASTOS MANUALES DE CAJA
        $movimientos_pendientes = $this->service->obtenerMovimientosParaAprobacion($_GET);
        
        // Calcular totales dinámicos
        $totales = ['aprobados' => 0, 'por_aprobar' => 0, 'rechazados' => 0];
        $cntEgresosPendientes = 0;
        if(!empty($movimientos_pendientes)){
            foreach($movimientos_pendientes as $mov) {
                if($mov['ESTADO_APROBACION'] == 'APROBADO') $totales['aprobados'] += $mov['MONTO'];
                elseif($mov['ESTADO_APROBACION'] == 'POR_APROBAR') {
                    $totales['por_aprobar'] += $mov['MONTO'];
                    $cntEgresosPendientes++;
                }
                elseif($mov['ESTADO_APROBACION'] == 'RECHAZADO') $totales['rechazados'] += $mov['MONTO'];
            }
        }
        
        $totalGeneralPendientes = $cntSolicitudesPendientes + $cntEgresosPendientes;
        
        $titulo = "Por Aprobar Cajeros - Suramerica 2030";
        $vista = __DIR__ . '/Views/movimientos_aprobar.php';
        require_once '../src/Platform/Views/layout.php';
    }

    public function procesar_aprobacion() {
        $this->verificarPermiso();
        if (!in_array($_SESSION['rol_id'], [1, 2])) {
            header("Location: /suramerica2030/caja/aprobaciones?subtab=egresos&status=error&msg=No+tienes+permisos+de+supervisor");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id_movimiento'];
            $accion = $_POST['accion'];
            $motivo = $_POST['motivo_rechazo'] ?? null;

            $res = $this->service->actualizarEstadoAprobacion($id, $accion, $motivo);

            if (is_array($res) && isset($res['status']) && $res['status'] === 'error') {
                header("Location: /suramerica2030/caja/aprobaciones?subtab=egresos&status=error&msg=" . urlencode($res['mensaje']));
            } else {
                header("Location: /suramerica2030/caja/aprobaciones?subtab=egresos&status=success");
            }
            exit();
        }
    }

    public function procesar_solicitud_anulacion() {
        $this->verificarPermiso();
        if (!in_array($_SESSION['rol_id'], [1, 2]) && empty($_SESSION['p_anular'])) {
            header("Location: /suramerica2030/caja/aprobaciones?subtab=solicitudes&status=error&msg=" . urlencode("No tienes permisos de supervisor para resolver anulaciones."));
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idSolicitud = (int)($_POST['id_solicitud'] ?? 0);
            $accion = $_POST['accion'] ?? '';
            $pasajesRepo = new \Modules\Pasajes\PasajesRepository();

            if ($accion === 'APROBAR') {
                $res = $pasajesRepo->aprobarSolicitudAnulacionBD($idSolicitud, $_SESSION['user_id'], $_SESSION['sucursal_id']);
                if ($res === true) {
                    header("Location: /suramerica2030/caja/aprobaciones?subtab=solicitudes&status=success&msg=" . urlencode("Solicitud #$idSolicitud aprobada correctamente. Venta devuelta y egreso registrado en caja."));
                } else {
                    header("Location: /suramerica2030/caja/aprobaciones?subtab=solicitudes&status=error&msg=" . urlencode($res));
                }
            } elseif ($accion === 'RECHAZAR') {
                $motivo = trim($_POST['motivo_rechazo'] ?? '');
                if (empty($motivo)) {
                    header("Location: /suramerica2030/caja/aprobaciones?subtab=solicitudes&status=error&msg=" . urlencode("Debe indicar el motivo de denegación."));
                    exit();
                }
                $res = $pasajesRepo->denegarSolicitudAnulacionBD($idSolicitud, $_SESSION['user_id'], $motivo);
                if ($res === true) {
                    header("Location: /suramerica2030/caja/aprobaciones?subtab=solicitudes&status=success&msg=" . urlencode("Solicitud #$idSolicitud denegada. La venta se mantiene activa."));
                } else {
                    header("Location: /suramerica2030/caja/aprobaciones?subtab=solicitudes&status=error&msg=" . urlencode($res));
                }
            }
            exit();
        }
    }

    public function historial_cierres() {
        $this->verificarPermiso();
        
        $fecha_cierre = $_GET['fecha'] ?? date('Y-m-d');
        $sucursal_id = ($_SESSION['rol_id'] == 1 && isset($_GET['sucursal']) && $_GET['sucursal'] !== '') 
            ? $_GET['sucursal'] 
            : ($_SESSION['sucursal_id'] ?? 1);
        
        // 1. Datos para el Arqueo y Cierre de la fecha seleccionada
        $datosCierreActual = $this->service->procesarDatosCaja($sucursal_id, $fecha_cierre);
        
        // 2. Desglose enriquecido por servicio y método de pago incluyendo fondo inicial
        $desgloseServicios = $this->service->obtenerDesglosePorServicios($datosCierreActual['movimientos'], $datosCierreActual['totales']['valor_inicial'] ?? 0);
        
        // 3. Historial de Cierres anteriores con filtros
        $sucursales = $this->service->obtenerListaSucursales();
        $historial_cierres = $this->service->obtenerHistorialCierresAvanzado($_GET);
        
        // Variables para la vista
        $cierreDia = $datosCierreActual['cierreDia'];
        $aperturaDia = $datosCierreActual['aperturaDia'];
        $sesionCaja = $datosCierreActual['sesionCaja'];
        $estadoCaja = $datosCierreActual['estadoCaja'];
        $movimientos = $datosCierreActual['movimientos'];
        $totales = $datosCierreActual['totales'];
        $egresos_pendientes_count = $datosCierreActual['egresos_pendientes_count'];
        $fecha_consulta = $fecha_cierre;
        $esAdmin = ($_SESSION['rol_id'] == 1);
        $sugerenciaApertura = $this->service->obtenerSugerenciaApertura($sucursal_id, $fecha_cierre);

        // Nombre de la sucursal actual
        $nombreSucursalActual = 'Central';
        if ($sucursal_id === 'ALL') {
            $nombreSucursalActual = 'Todas las Sucursales (Consolidado)';
        } else {
            foreach ($sucursales as $s) {
                if ($s['ID_SUCURSAL'] == $sucursal_id) {
                    $nombreSucursalActual = $s['CIUDAD'];
                    break;
                }
            }
        }
        
        $titulo = "Cierres de Caja y Arqueo - Suramerica 2030";
        $vista = __DIR__ . '/Views/cierres_caja.php';
        require_once '../src/Platform/Views/layout.php';
    }

    public function cierres() {
        $this->historial_cierres();
    }

    public function ajustar_cierre() {
        $this->verificarPermiso();
        if (!in_array($_SESSION['rol_id'], [1, 2])) {
            header("Location: /suramerica2030/caja/historial_cierres?status=error&msg=No+tienes+permisos");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id_cierre'];
            $deposito = $_POST['deposito'];

            $this->service->actualizarDepositoCierre($id, $deposito);

            header("Location: /suramerica2030/caja/historial_cierres?status=success_ajuste");
            exit();
        }
    }

    public function depositos() {
        $this->verificarPermiso();
        // Si no es admin y no se filtró sucursal, mostrar por defecto la de su sesión
        if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] != 1 && !isset($_GET['sucursal'])) {
            $_GET['sucursal'] = $_SESSION['sucursal_id'] ?? 1;
        }
        $sucursal_id = $_GET['sucursal'] ?? ($_SESSION['sucursal_id'] ?? 1);

        // --- LLAMADA DIRECTA ---
        $sucursales = $this->service->obtenerListaSucursales();
        
        $lista_depositos = $this->service->obtenerDepositosCajaConDetalle($_GET);
        
        $titulo = "Depósitos de Caja - Suramerica 2030";
        $vista = __DIR__ . '/Views/depositos_caja.php';
        require_once '../src/Platform/Views/layout.php';
    }

    public function registrar_deposito() {
        $this->verificarPermiso();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_cierre = intval($_POST['id_cierre'] ?? 0);
            $banco = trim($_POST['banco'] ?? 'Banco Pichincha');
            $num_documento = trim($_POST['nro_documento'] ?? $_POST['num_documento'] ?? '');
            $monto = floatval($_POST['monto'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');
            $usuario_id = $_SESSION['user_id'] ?? 1;

            if ($id_cierre <= 0 || $monto <= 0 || empty($num_documento)) {
                header("Location: /suramerica2030/caja/depositos?status=error&msg=Datos+incompletos.+Monto+y+número+de+documento+son+obligatorios");
                exit();
            }

            $res = $this->service->registrarDepositoDetalle($id_cierre, $usuario_id, $banco, $num_documento, $monto, $observaciones);

            if ($res['status'] === 'success') {
                header("Location: /suramerica2030/caja/depositos?status=success_deposito");
            } else {
                header("Location: /suramerica2030/caja/depositos?status=error&msg=" . urlencode($res['mensaje'] ?? 'Error al registrar el depósito'));
            }
            exit();
        }
    }

    public function procesar_deposito() {
        $this->verificarPermiso();
        if (!in_array($_SESSION['rol_id'], [1, 2])) {
            header("Location: /suramerica2030/caja/depositos?status=error&msg=Permiso+denegado");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_cierre = $_POST['id_cierre'];
            $nro_documento = $_POST['nro_documento'];
            $fecha_aprobacion = date('Y-m-d H:i:s');

            $this->service->procesarDepositoBancario($id_cierre, $nro_documento, $fecha_aprobacion);

            header("Location: /suramerica2030/caja/depositos?status=success_aprobado");
            exit();
        }
    }

    public function movimientos() {
        $this->verificarPermiso();
        $sucursal_id = $_GET['sucursal'] ?? ($_SESSION['sucursal_id'] ?? 1);

        // --- LLAMADA DIRECTA ---
        $sucursales = $this->service->obtenerListaSucursales();
        $cajeros = $this->service->obtenerListaCajeros();
        $movimientos = $this->service->obtenerMovimientosCaja($_GET);
        
        $titulo = "Movimientos de Caja - Suramerica 2030";
        $vista = __DIR__ . '/Views/movimientos_caja.php';
        require_once '../src/Platform/Views/layout.php';
    }

    public function guardar_egreso() {
        $this->verificarPermiso();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tipo_egreso = $_POST['tipo_egreso'];
            $concepto = $_POST['concepto'];
            $monto = $_POST['monto'];
            $id_usuario = $_SESSION['user_id'];
            $id_sucursal = $_SESSION['sucursal_id'];
            
            $this->service->registrarEgresoPendiente($id_usuario, $id_sucursal, $tipo_egreso, $concepto, $monto);

            header("Location: /suramerica2030/caja/movimientos?status=success_egreso");
            exit();
        }
    }

    public function ticket_cierre() {
        $this->verificarPermiso();
        
        $idCierre = $_GET['id'] ?? null;
        $fecha = $_GET['fecha'] ?? null;
        $sucursal_id = $_GET['sucursal'] ?? ($_SESSION['sucursal_id'] ?? 1);

        $cierre = null;
        if ($idCierre) {
            $cierre = $this->service->obtenerDetalleCierrePorId($idCierre);
        } elseif ($fecha) {
            $sesion = (new CajaRepository())->obtenerSesionCajaDia($sucursal_id, $fecha);
            if ($sesion) {
                $cierre = $this->service->obtenerDetalleCierrePorId($sesion['ID_CIERRE']);
            }
        }

        if (!$cierre) {
            echo "<div style='font-family:sans-serif; text-align:center; padding:30px;'><h2>Error: Cierre de caja no encontrado.</h2><p>Asegúrate de que la caja haya sido cerrada previamente.</p></div>";
            return;
        }

        // Obtener movimientos y desglose
        $movimientos = (new CajaRepository())->obtenerMovimientosDelDia($cierre['ID_SUCURSAL'], $cierre['FECHA_CAJA']);
        $totales = $this->service->procesarDatosCaja($cierre['ID_SUCURSAL'], $cierre['FECHA_CAJA'])['totales'];
        $desglose = $this->service->obtenerDesglosePorServicios($movimientos, $cierre['VALOR_INICIAL']);

        require_once __DIR__ . '/Views/ticket_cierre.php';
    }
}
?>