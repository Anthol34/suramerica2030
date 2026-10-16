<?php
namespace Modules\Auth;

class AuthService {
    private $repository;

    public function __construct() {
        $this->repository = new AuthRepository();
    }

    public function procesarLogin($username, $password) {
        $usuario = $this->repository->verificarCredenciales(trim(strtolower($username)));

        if ($usuario && password_verify($password, $usuario['PASSWORD_HASH'])) {
            return ['success' => true, 'usuario' => $usuario];
        }
        return ['success' => false, 'mensaje' => 'Usuario o contraseña incorrectos.'];
    }

    // --- NUEVAS FUNCIONES PARA EL PERFIL ---
    public function obtenerMiPerfil($userId) {
        $data = $this->repository->obtenerUsuarioPorId($userId);
        return $data ? ['success' => true, 'data' => $data] : ['success' => false];
    }

    public function procesarActualizarPerfil($postData, $userId) {
        $nombre = trim($postData['nombre']);
        $email = trim(strtolower($postData['email']));
        
        // Encriptar solo si el usuario escribió una nueva clave
        $hash = !empty($postData['password']) ? password_hash($postData['password'], PASSWORD_DEFAULT) : null;

        return $this->repository->actualizarPerfilBD($userId, $nombre, $email, $hash);
    }

    public function requiereSetupInicial() {
        return $this->repository->contarUsuarios() === 0;
    }

    public function instalarAdminInicial($postData) {
        if (!$this->requiereSetupInicial()) {
            return ['success' => false, 'mensaje' => 'El sistema ya tiene usuarios creados.'];
        }

        $nombre = !empty($postData['nombre']) ? trim($postData['nombre']) : 'Administrador Principal';
        $username = !empty($postData['username']) ? trim(strtolower($postData['username'])) : 'admin';
        $email = !empty($postData['email']) ? trim(strtolower($postData['email'])) : 'admin@suramerica2030.com';
        $password = !empty($postData['password']) ? trim($postData['password']) : 'admin123';

        if (strlen($password) < 4) {
            return ['success' => false, 'mensaje' => 'La contraseña debe tener al menos 4 caracteres.'];
        }

        try {
            $this->repository->crearAdminInicial($nombre, $username, $email, $password);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }
}
?>