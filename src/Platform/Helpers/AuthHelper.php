<?php
namespace Platform\Helpers;

/**
 * Helper centralizado para verificación de roles, permisos y contexto de sesión.
 */
class AuthHelper {

    /**
     * Determina si el usuario logueado tiene rol de Administrador (Rol ID 1).
     */
    public static function esAdmin() {
        return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 1;
    }

    /**
     * Permiso para editar precios y tarifas comerciales.
     */
    public static function puedeEditarPrecios() {
        if (self::esAdmin()) return true;
        return !empty($_SESSION['p_editar_precios']) && (int)$_SESSION['p_editar_precios'] === 1;
    }

    /**
     * Permiso para anular comprobantes, pasajes, sobrepesos o encomiendas de forma directa.
     */
    public static function puedeAnularDirecto() {
        if (self::esAdmin()) return true;
        return !empty($_SESSION['p_anular']) && (int)$_SESSION['p_anular'] === 1;
    }

    /**
     * Permiso para gestionar usuarios y accesos.
     */
    public static function puedeUsuarios() {
        if (self::esAdmin()) return true;
        return !empty($_SESSION['p_usuarios']) && (int)$_SESSION['p_usuarios'] === 1;
    }

    /**
     * Permiso para ver módulo de finanzas y comisiones de socios.
     */
    public static function puedeFinanzas() {
        if (self::esAdmin()) return true;
        return in_array((int)($_SESSION['rol_id'] ?? 0), [1, 2]); // Admin o Supervisor
    }

    /**
     * Retorna el ID del usuario en sesión.
     */
    public static function usuarioId() {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    /**
     * Retorna el nombre completo del usuario en sesión.
     */
    public static function usuarioNombre() {
        return $_SESSION['nombre'] ?? 'Usuario';
    }

    /**
     * Retorna el ID de la sucursal activa.
     */
    public static function sucursalId() {
        return (int)($_SESSION['sucursal_id'] ?? 1);
    }

    /**
     * Retorna la ciudad/nombre de la sucursal activa.
     */
    public static function sucursalNombre() {
        return $_SESSION['ciudad_sucursal'] ?? 'Oficina Central';
    }

    /**
     * Retorna el ID del rol actual.
     */
    public static function rolId() {
        return (int)($_SESSION['rol_id'] ?? 0);
    }
}
