<?php
namespace Platform\Helpers;

/**
 * Helper para estandarizar respuestas en vivo (AJAX / LiveData) y mantener
 * compatibilidad transparente con envíos normales (POST clásico / redirect).
 */
class LiveResponse {

    /**
     * Determina si la petición actual fue emitida por AJAX / fetch
     */
    public static function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || !empty($_POST['ajax'])
            || !empty($_GET['ajax']);
    }

    /**
     * Emite una respuesta JSON limpia y finaliza la ejecución.
     */
    public static function json(array $data, int $statusCode = 200): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Emite respuesta de éxito para AJAX. Si no es AJAX, no hace nada (permite continuar flujo).
     */
    public static function success(string $mensaje, array $extra = []): void {
        if (self::isAjax()) {
            self::json(array_merge([
                'status' => 'success',
                'success' => true,
                'mensaje' => $mensaje,
                'message' => $mensaje
            ], $extra), 200);
        }
    }

    /**
     * Emite respuesta de error para AJAX. Si no es AJAX, no hace nada (permite continuar flujo).
     */
    public static function error(string $mensaje, int $statusCode = 400, array $extra = []): void {
        if (self::isAjax()) {
            self::json(array_merge([
                'status' => 'error',
                'success' => false,
                'mensaje' => $mensaje,
                'message' => $mensaje
            ], $extra), $statusCode);
        }
    }

    /**
     * Método orquestador inteligente: Si la petición fue AJAX responde JSON.
     * Si fue un submit convencional de formulario, ejecuta el header Location con redirect.
     *
     * @param bool|array|string $resultado El resultado del Service/Repository (true, string de error o array)
     * @param string $tab Pestaña destino para la redirección tradicional
     * @param string $successToken Token para mostrar alerta de éxito en vista tradicional
     * @param string $successMsg Mensaje descriptivo para el toast en vivo
     * @param array $extra Datos adicionales para enviar en el JSON (ej. objeto modificado, IDs, etc.)
     * @param string $baseUrl URL base del módulo (ej. /suramerica2030/usuarios)
     */
    public static function respond($resultado, string $tab, string $successToken, string $successMsg = 'Cambios guardados correctamente.', array $extra = [], string $baseUrl = ''): void {
        $esExito = ($resultado === true) || (is_array($resultado) && (!isset($resultado['status']) || $resultado['status'] === 'success'));
        
        if (self::isAjax()) {
            if ($esExito) {
                self::json(array_merge([
                    'status' => 'success',
                    'success' => true,
                    'mensaje' => $successMsg,
                    'message' => $successMsg,
                    'token' => $successToken,
                    'tab' => $tab,
                    'datos' => is_array($resultado) ? $resultado : []
                ], $extra), 200);
            } else {
                $msgError = is_string($resultado) ? $resultado : ($resultado['mensaje'] ?? ($resultado['msg'] ?? 'Ocurrió un error al procesar los datos.'));
                self::json(array_merge([
                    'status' => 'error',
                    'success' => false,
                    'mensaje' => $msgError,
                    'message' => $msgError,
                    'tab' => $tab
                ], $extra), 400);
            }
        }

        // Redirección tradicional en caso de que JS no esté activo
        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
        if ($esExito) {
            header("Location: {$baseUrl}{$separator}tab={$tab}&status={$successToken}");
        } else {
            $msgError = is_string($resultado) ? $resultado : ($resultado['mensaje'] ?? 'Error desconocido');
            header("Location: {$baseUrl}{$separator}tab={$tab}&status=error&msg=" . urlencode($msgError));
        }
        exit();
    }
}
