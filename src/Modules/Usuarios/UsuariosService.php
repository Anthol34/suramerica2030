<?php
namespace Modules\Usuarios;

class UsuariosService {
    private $repository;

    public function __construct() {
        $this->repository = new UsuariosRepository();
    }

    public function compilarDatosIniciales() {
        return [
            'usuarios' => $this->repository->obtenerTodosLosUsuarios(),
            'roles' => $this->repository->obtenerRoles(),
            'sucursales' => $this->repository->obtenerSucursales()
        ];
    }

    public function procesarGuardarUsuario($postData) {
        $postData['hash'] = password_hash($postData['password'], PASSWORD_DEFAULT);
        $postData['username'] = trim(strtolower($postData['username']));
        $postData['email'] = trim(strtolower($postData['email']));

        return $this->repository->crearUsuarioBD($postData);
    }

    public function procesarEditarUsuario($postData) {
        $postData['hash'] = !empty($postData['password']) ? password_hash($postData['password'], PASSWORD_DEFAULT) : null;
        $postData['username'] = trim(strtolower($postData['username']));
        $postData['email'] = trim(strtolower($postData['email']));

        return $this->repository->editarUsuarioBD($postData);
    }

    public function procesarCambioEstado($idUsuario, $nuevoEstado, $sesionActualId) {
        if ($idUsuario == $sesionActualId) {
            return "No puedes desactivar tu propia cuenta mientras estás en sesión.";
        }
        return $this->repository->cambiarEstadoUsuarioBD($idUsuario, $nuevoEstado);
    }

    // --- NUEVO: PROCESAR GUARDAR ROL ---
    public function procesarGuardarRol($postData) {
        if (empty(trim($postData['nombre_rol']))) {
            return "El nombre del rol es obligatorio.";
        }

        $datos = [
            'nombre_rol'       => trim($postData['nombre_rol']),
            'p_venta'          => !empty($postData['p_venta']) ? 1 : 0,
            'p_anular'         => !empty($postData['p_anular']) ? 1 : 0,
            'p_caja'           => !empty($postData['p_caja']) ? 1 : 0,
            'p_flota'          => !empty($postData['p_flota']) ? 1 : 0,
            'p_usuarios'       => !empty($postData['p_usuarios']) ? 1 : 0,
            'p_despacho'       => !empty($postData['p_despacho']) ? 1 : 0,
            'p_llegada'        => !empty($postData['p_llegada']) ? 1 : 0,
            'p_editar_precios' => !empty($postData['p_editar_precios']) ? 1 : 0
        ];

        return $this->repository->crearRolBD($datos);
    }

    public function procesarGuardarPermisos($idRol, $postData) {
        if ($idRol == 1) {
            return "Por seguridad corporativa, los permisos del Administrador Principal no pueden modificarse.";
        }

        $permisos = [
            'p_venta'          => !empty($postData['p_venta']) ? 1 : 0,
            'p_anular'         => !empty($postData['p_anular']) ? 1 : 0,
            'p_caja'           => !empty($postData['p_caja']) ? 1 : 0,
            'p_flota'          => !empty($postData['p_flota']) ? 1 : 0,
            'p_usuarios'       => !empty($postData['p_usuarios']) ? 1 : 0,
            'p_despacho'       => !empty($postData['p_despacho']) ? 1 : 0,
            'p_llegada'        => !empty($postData['p_llegada']) ? 1 : 0,
            'p_editar_precios' => !empty($postData['p_editar_precios']) ? 1 : 0
        ];

        return $this->repository->actualizarPermisosBD($idRol, $permisos);
    }

    public function procesarGuardarSucursal($postData) {
        return $this->repository->crearSucursalBD($postData);
    }

    public function procesarEditarSucursal($postData) {
        return $this->repository->editarSucursalBD($postData);
    }

    public function procesarEliminarSucursal($idSucursal) {
        return $this->repository->eliminarSucursalBD($idSucursal);
    }

    // --- CONFIGURACIÓN DE CORREO SMTP ---
    public function obtenerEmailConfig() {
        $configFile = __DIR__ . '/../../Platform/Storage/email_config.json';
        if (!file_exists($configFile)) {
            file_put_contents($configFile, json_encode([
                'smtp_host' => 'smtp.gmail.com',
                'smtp_user' => '',
                'smtp_pass' => '',
                'smtp_port' => 587,
                'smtp_from_name' => 'Suramerica 2030 Logistica'
            ], JSON_PRETTY_PRINT));
        }
        return json_decode(file_get_contents($configFile), true) ?: [];
    }

    public function procesarGuardarEmailConfig($postData) {
        $configFile = __DIR__ . '/../../Platform/Storage/email_config.json';
        $actual = $this->obtenerEmailConfig();
        $config = [
            'smtp_host' => trim($postData['smtp_host'] ?? 'smtp.gmail.com'),
            'smtp_user' => trim($postData['smtp_user'] ?? ''),
            'smtp_pass' => !empty($postData['smtp_pass']) ? trim($postData['smtp_pass']) : ($actual['smtp_pass'] ?? ''),
            'smtp_port' => (int)($postData['smtp_port'] ?? 587),
            'smtp_from_name' => trim($postData['smtp_from_name'] ?? 'Suramerica 2030 Logistica')
        ];
        return (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) !== false);
    }
}
?>