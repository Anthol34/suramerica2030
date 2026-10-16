<?php
namespace Modules\Usuarios;

class UsuariosController {
    private $service;

    public function __construct() {
        $this->service = new UsuariosService();
    }

    private function verificarPermisoUsuarios() {
        if (!isset($_SESSION['p_usuarios']) || $_SESSION['p_usuarios'] != 1) { 
            header("Location: /suramerica2030/dashboard");
            exit();
        }
    }

    public function index() {
        $this->verificarPermisoUsuarios();
        
        $data = $this->service->compilarDatosIniciales();
        extract($data); // $usuarios, $roles, $sucursales
        
        $emailConfig = $this->service->obtenerEmailConfig();
        
        $titulo = "Gestión de Usuarios y Permisos - Suramerica 2030";
        $vista = __DIR__ . '/Views/usuarios.php';
        require_once __DIR__ . '/../../Platform/Views/layout.php';
    }

    // --- ACCIONES DE USUARIO ---
    public function guardar() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarUsuario($_POST);
            $this->redireccionar('usuarios', $resultado, 'success');
        }
    }

    public function editar() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEditarUsuario($_POST);
            $this->redireccionar('usuarios', $resultado, 'success_edit');
        }
    }

    public function estado() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarCambioEstado((int)$_POST['id_usuario'], (int)$_POST['nuevo_estado'], $_SESSION['user_id']);
            $this->redireccionar('usuarios', $resultado, 'success_status');
        }
    }

    // --- ACCIONES DE ROLES ---
    public function guardarRol() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarRol($_POST);
            $this->redireccionar('roles', $resultado, 'success_rol');
        }
    }

    public function guardarPermisos() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarPermisos((int)$_POST['id_rol'], $_POST);
            $this->redireccionar('roles', $resultado, 'success_permisos');
        }
    }

    // --- ACCIONES DE SUCURSALES ---
    public function guardarSucursal() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarGuardarSucursal($_POST);
            $this->redireccionar('sucursales', $resultado, 'success_sucursal');
        }
    }

    public function editarSucursal() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEditarSucursal($_POST);
            $this->redireccionar('sucursales', $resultado, 'success_edit_sucursal');
        }
    }

    public function eliminarSucursal() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $resultado = $this->service->procesarEliminarSucursal((int)$_POST['id_sucursal']);
            $this->redireccionar('sucursales', $resultado, 'success_delete_sucursal');
        }
    }
    
    // --- ACCIONES DE CORREO ---
    public function guardarConfigCorreo() {
        $this->verificarPermisoUsuarios();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->service->procesarGuardarEmailConfig($_POST);
            header("Location: /suramerica2030/usuarios?tab=email&status=success_email_cfg");
            exit();
        }
    }

    private function redireccionar($tab, $resultado, $successToken) {
        if ($resultado === true) {
            header("Location: /suramerica2030/usuarios?tab=$tab&status=$successToken");
        } else {
            header("Location: /suramerica2030/usuarios?tab=$tab&status=error&msg=" . urlencode($resultado));
        }
        exit();
    }
}
?>