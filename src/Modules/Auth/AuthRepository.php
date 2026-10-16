<?php
namespace Modules\Auth;

use Platform\Storage\Database;
use PDO;
use Exception;

class AuthRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function verificarCredenciales($username) {
        $query = "SELECT u.ID_USUARIO, u.ID_ROL, u.ID_SUCURSAL, u.NOMBRE, u.USUARIO, u.PASSWORD_HASH, u.ESTADO,
                         r.NOMBRE_ROL, r.P_VENTA, r.P_CAJA, r.P_ANULAR, r.P_FLOTA, r.P_USUARIOS, r.P_DESPACHO, r.P_LLEGADA, r.P_EDITAR_PRECIOS,
                         s.CIUDAD 
                  FROM USUARIO u
                  INNER JOIN ROL r ON u.ID_ROL = r.ID_ROL
                  INNER JOIN SUCURSAL s ON u.ID_SUCURSAL = s.ID_SUCURSAL
                  WHERE u.USUARIO = :username AND u.ESTADO = 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- NUEVAS FUNCIONES PARA EL PERFIL ---
    public function obtenerUsuarioPorId($id) {
        $stmt = $this->db->prepare("SELECT NOMBRE, USUARIO, EMAIL FROM USUARIO WHERE ID_USUARIO = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarPerfilBD($id, $nombre, $email, $hash = null) {
        try {
            if ($hash) {
                $stmt = $this->db->prepare("UPDATE USUARIO SET NOMBRE = ?, EMAIL = ?, PASSWORD_HASH = ? WHERE ID_USUARIO = ?");
                $stmt->execute([$nombre, $email, $hash, $id]);
            } else {
                $stmt = $this->db->prepare("UPDATE USUARIO SET NOMBRE = ?, EMAIL = ? WHERE ID_USUARIO = ?");
                $stmt->execute([$nombre, $email, $id]);
            }
            return true;
        } catch (Exception $e) {
            return "Error: Es posible que el correo electrónico ya esté en uso por otra cuenta.";
        }
    }

    public function contarUsuarios() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM USUARIO");
        return (int)$stmt->fetchColumn();
    }

    public function crearAdminInicial($nombre, $usuario, $email, $password) {
        // Bloqueo estricto: solo se permite si la base de datos tiene 0 usuarios
        if ($this->contarUsuarios() > 0) {
            throw new Exception("Operación denegada: Ya existen usuarios registrados en el sistema.");
        }

        // 1. Asegurar que existe el Rol de Administrador (ID_ROL = 1)
        $stmtRol = $this->db->query("SELECT ID_ROL FROM ROL WHERE ID_ROL = 1 LIMIT 1");
        if (!$stmtRol->fetch()) {
            $this->db->query("INSERT INTO ROL (ID_ROL, NOMBRE_ROL, P_VENTA, P_ANULAR, P_CAJA, P_FLOTA, P_USUARIOS) 
                              VALUES (1, 'Admin', 1, 1, 1, 1, 1)");
        }

        // 2. Asegurar que existe al menos una sucursal principal
        $stmtSuc = $this->db->query("SELECT ID_SUCURSAL FROM SUCURSAL ORDER BY ID_SUCURSAL ASC LIMIT 1");
        $idSucursal = $stmtSuc->fetchColumn();
        if (!$idSucursal) {
            $this->db->query("INSERT INTO SUCURSAL (ID_SUCURSAL, PAIS, CIUDAD, DIRECCION, TELEFONO) 
                              VALUES (1, 'ECUADOR', 'MANTA', 'Matriz Principal', '0999999999')");
            $idSucursal = 1;
        }

        // 3. Crear el usuario administrador principal
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO USUARIO (ID_ROL, ID_SUCURSAL, NOMBRE, USUARIO, EMAIL, PASSWORD_HASH, ESTADO) 
                  VALUES (1, :sucursal, :nombre, :usuario, :email, :hash, 1)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':sucursal' => $idSucursal,
            ':nombre'   => $nombre,
            ':usuario'  => strtolower(trim($usuario)),
            ':email'    => strtolower(trim($email)),
            ':hash'     => $hash
        ]);
    }
}
?>