<?php
namespace Modules\Usuarios;

use Platform\Storage\Database;
use PDO;
use Exception;

class UsuariosRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function obtenerRoles() {
        return $this->db->query("SELECT * FROM ROL")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSucursales() {
        return $this->db->query("SELECT * FROM SUCURSAL ORDER BY ID_SUCURSAL")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosLosUsuarios() {
        $query = "SELECT u.ID_USUARIO, u.ID_ROL, u.ID_SUCURSAL, u.NOMBRE, u.USUARIO, u.EMAIL, u.ESTADO,
                         r.NOMBRE_ROL, s.CIUDAD
                  FROM USUARIO u
                  INNER JOIN ROL r ON u.ID_ROL = r.ID_ROL
                  INNER JOIN SUCURSAL s ON u.ID_SUCURSAL = s.ID_SUCURSAL
                  ORDER BY u.ID_USUARIO DESC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- ACCIONES DE USUARIOS ---
    public function crearUsuarioBD($datos) {
        try {
            $query = "INSERT INTO USUARIO (ID_ROL, ID_SUCURSAL, NOMBRE, USUARIO, EMAIL, PASSWORD_HASH, ESTADO) 
                      VALUES (:rol, :sucursal, :nombre, :usuario, :email, :hash, 1)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':rol' => $datos['rol_id'], 
                ':sucursal' => $datos['sucursal_id'], 
                ':nombre' => $datos['nombre'], 
                ':usuario' => $datos['username'], 
                ':email' => $datos['email'], 
                ':hash' => $datos['hash']
            ]);
            return true;
        } catch (\PDOException $e) { 
            return "El nombre de usuario o correo ya está registrado en el sistema."; 
        }
    }

    public function editarUsuarioBD($datos) {
        try {
            if (!empty($datos['hash'])) {
                $query = "UPDATE USUARIO SET ID_ROL = :rol, ID_SUCURSAL = :sucursal, NOMBRE = :nombre, USUARIO = :usuario, EMAIL = :email, PASSWORD_HASH = :hash WHERE ID_USUARIO = :id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':rol' => $datos['rol_id'], ':sucursal' => $datos['sucursal_id'], 
                    ':nombre' => $datos['nombre'], ':usuario' => $datos['username'], 
                    ':email' => $datos['email'], ':hash' => $datos['hash'], ':id' => $datos['id_usuario']
                ]);
            } else {
                $query = "UPDATE USUARIO SET ID_ROL = :rol, ID_SUCURSAL = :sucursal, NOMBRE = :nombre, USUARIO = :usuario, EMAIL = :email WHERE ID_USUARIO = :id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':rol' => $datos['rol_id'], ':sucursal' => $datos['sucursal_id'], 
                    ':nombre' => $datos['nombre'], ':usuario' => $datos['username'], 
                    ':email' => $datos['email'], ':id' => $datos['id_usuario']
                ]);
            }
            return true;
        } catch (\PDOException $e) { 
            return "El nombre de usuario o correo ya pertenece a otro registro."; 
        }
    }

    public function cambiarEstadoUsuarioBD($idUsuario, $nuevoEstado) {
        try {
            $stmt = $this->db->prepare("UPDATE USUARIO SET ESTADO = :estado WHERE ID_USUARIO = :id");
            $stmt->execute([':estado' => $nuevoEstado, ':id' => $idUsuario]);
            return true;
        } catch (Exception $e) { 
            return $e->getMessage(); 
        }
    }

    // --- ACCIONES DE ROLES ---
    public function crearRolBD($datos) {
        try {
            $query = "INSERT INTO ROL (NOMBRE_ROL, P_VENTA, P_ANULAR, P_CAJA, P_FLOTA, P_USUARIOS, P_DESPACHO, P_LLEGADA, P_EDITAR_PRECIOS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $datos['nombre_rol'],
                $datos['p_venta'],
                $datos['p_anular'],
                $datos['p_caja'],
                $datos['p_flota'],
                $datos['p_usuarios'],
                $datos['p_despacho'],
                $datos['p_llegada'],
                $datos['p_editar_precios'] ?? 0
            ]);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function actualizarPermisosBD($idRol, $permisos) {
        try {
            $stmt = $this->db->prepare("UPDATE ROL SET P_VENTA = ?, P_ANULAR = ?, P_CAJA = ?, P_FLOTA = ?, P_USUARIOS = ?, P_DESPACHO = ?, P_LLEGADA = ?, P_EDITAR_PRECIOS = ? WHERE ID_ROL = ?");
            $stmt->execute([
                $permisos['p_venta'], 
                $permisos['p_anular'], 
                $permisos['p_caja'], 
                $permisos['p_flota'], 
                $permisos['p_usuarios'], 
                $permisos['p_despacho'], 
                $permisos['p_llegada'], 
                $permisos['p_editar_precios'] ?? 0,
                $idRol
            ]);

            if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == $idRol) {
                $_SESSION['p_editar_precios'] = $permisos['p_editar_precios'] ?? 0;
            }

            return true;
        } catch (Exception $e) { 
            return $e->getMessage(); 
        }
    }

    // --- ACCIONES DE SUCURSALES ---
    public function crearSucursalBD($datos) {
        try {
            $query = "INSERT INTO SUCURSAL (PAIS, CIUDAD, DIRECCION, TELEFONO) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                mb_strtoupper($datos['pais']), 
                mb_strtoupper($datos['ciudad']), 
                $datos['direccion'], 
                $datos['telefono'] ?? null
            ]);
            return true;
        } catch (Exception $e) { 
            return $e->getMessage(); 
        }
    }

    public function editarSucursalBD($datos) {
        try {
            $query = "UPDATE SUCURSAL SET PAIS = ?, CIUDAD = ?, DIRECCION = ?, TELEFONO = ? WHERE ID_SUCURSAL = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                mb_strtoupper($datos['pais']), 
                mb_strtoupper($datos['ciudad']), 
                $datos['direccion'], 
                $datos['telefono'] ?? null, 
                $datos['id_sucursal']
            ]);
            return true;
        } catch (Exception $e) { 
            return $e->getMessage(); 
        }
    }

    public function eliminarSucursalBD($idSucursal) {
        try {
            $query = "DELETE FROM SUCURSAL WHERE ID_SUCURSAL = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$idSucursal]);
            return true;
        } catch (Exception $e) { 
            return "No se puede eliminar la sucursal porque tiene usuarios, operaciones de caja o ventas vinculadas."; 
        }
    }
}
?>