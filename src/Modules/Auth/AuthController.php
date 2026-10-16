<?php
namespace Modules\Auth;

class AuthController {
    private $service;

    public function __construct() {
        $this->service = new AuthService();
    }

    public function index() {
        if (isset($_SESSION['user_id'])) {
            header("Location: /suramerica2030/dashboard");
            exit();
        }
        $necesitaSetup = $this->service->requiereSetupInicial();
        require_once __DIR__ . '/Views/login.php';
    }

    public function instalar() {
        if (!$this->service->requiereSetupInicial()) {
            header("Location: /suramerica2030/auth");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->instalarAdminInicial($_POST);
            if ($resultado['success']) {
                header("Location: /suramerica2030/auth?instalado=1");
            } else {
                header("Location: /suramerica2030/auth?error_setup=" . urlencode($resultado['mensaje']));
            }
            exit();
        }

        header("Location: /suramerica2030/auth");
        exit();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarLogin($_POST['username'], $_POST['password']);

            if ($resultado['success']) {
                $u = $resultado['usuario'];
                $_SESSION['user_id'] = $u['ID_USUARIO'];
                $_SESSION['rol_id'] = $u['ID_ROL'];
                $_SESSION['nombre_rol'] = $u['NOMBRE_ROL'];
                $_SESSION['sucursal_id'] = $u['ID_SUCURSAL'];
                $_SESSION['ciudad_sucursal'] = $u['CIUDAD'];
                $_SESSION['nombre'] = $u['NOMBRE'];
                
                $_SESSION['p_venta'] = $u['P_VENTA'];
                $_SESSION['p_caja'] = $u['P_CAJA'];
                $_SESSION['p_anular'] = $u['P_ANULAR'];
                $_SESSION['p_flota'] = $u['P_FLOTA'];
                $_SESSION['p_usuarios'] = $u['P_USUARIOS'];
                $_SESSION['p_despacho'] = ($u['ID_ROL'] == 1) ? 1 : ($u['P_DESPACHO'] ?? 0);
                $_SESSION['p_llegada'] = ($u['ID_ROL'] == 1) ? 1 : ($u['P_LLEGADA'] ?? 0);
                $_SESSION['p_editar_precios'] = ($u['ID_ROL'] == 1) ? 1 : ($u['P_EDITAR_PRECIOS'] ?? 0);
                
                header("Location: /suramerica2030/dashboard");
            } else {
                header("Location: /suramerica2030/auth?error=1");
            }
            exit();
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        header("Location: /suramerica2030/auth");
        exit();
    }

    // --- NUEVAS FUNCIONES PARA EL PERFIL ---
    public function obtenerMiPerfil() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]); exit();
        }
        echo json_encode($this->service->obtenerMiPerfil($_SESSION['user_id']));
        exit();
    }

    public function actualizarPerfil() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /suramerica2030/auth"); exit();
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->service->procesarActualizarPerfil($_POST, $_SESSION['user_id']);
            
            // Regresar al usuario exactamente a la misma página donde estaba
            $referer = $_SERVER['HTTP_REFERER'] ?? '/suramerica2030/dashboard';
            $referer = preg_replace('/([?&])status=[^&]+(&|$)/', '$1', $referer);
            $referer = rtrim($referer, '?&');
            $separator = parse_url($referer, PHP_URL_QUERY) ? '&' : '?';

            if ($res === true) {
                $_SESSION['nombre'] = trim($_POST['nombre']); // Actualizar nombre visual de la sesión
                header("Location: " . $referer . $separator . "status=success_perfil");
            } else {
                header("Location: " . $referer . $separator . "status=error_perfil&msg=" . urlencode($res));
            }
            exit();
        }
    }
}
?>