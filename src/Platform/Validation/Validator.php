<?php
namespace Platform\Validation;

use Exception;

/**
 * Validador Centralizado de Negocio para Suramerica 2030.
 * Provee métodos estáticos y validación declarativa reutilizable en cualquier módulo.
 */
class Validator {

    private $errores = [];

    /**
     * Valida una cédula ecuatoriana.
     */
    public static function cedula($cedula) {
        return CedulaValidator::validarCedula($cedula);
    }

    /**
     * Valida un RUC ecuatoriano.
     */
    public static function ruc($ruc) {
        return CedulaValidator::validarRUC($ruc);
    }

    /**
     * Valida cualquier identificación según su tipo.
     */
    public static function identificacion($tipo, $numero) {
        return CedulaValidator::validar($tipo, $numero);
    }

    /**
     * Valida teléfono convencional o celular de Ecuador.
     * Convencionales: 7 a 9 dígitos (ej: 042123456, 052620100).
     * Celulares: 10 dígitos iniciando con 09 (ej: 0987654321).
     */
    public static function telefono($telefono) {
        $tel = preg_replace('/[^\d]/', '', (string)$telefono);
        if (strlen($tel) === 10 && substr($tel, 0, 2) === '09') {
            return true;
        }
        if (strlen($tel) >= 7 && strlen($tel) <= 9) {
            return true;
        }
        return false;
    }

    /**
     * Valida correo electrónico.
     */
    public static function email($email) {
        return (bool)filter_var(trim((string)$email), FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valida un monto monetario numérico mayor a cero y opcionalmente con un tope.
     */
    public static function monto($monto, $min = 0.01, $max = null) {
        if (!is_numeric($monto)) return false;
        $val = floatval($monto);
        if ($val < $min) return false;
        if ($max !== null && $val > $max) return false;
        return true;
    }

    /**
     * Valida que un pago mixto (Efectivo + Transferencia) cuadre exactamente con el total.
     * @param float $total
     * @param float $efectivo
     * @param float $transferencia
     * @param float $tolerancia Centavos de margen para errores de redondeo de punto flotante
     * @return bool
     */
    public static function pagoMixto($total, $efectivo, $transferencia, $tolerancia = 0.02) {
        $tot = round(floatval($total), 2);
        $ef = round(floatval($efectivo), 2);
        $tr = round(floatval($transferencia), 2);

        if ($tot <= 0) return false;
        if ($ef < 0 || $tr < 0) return false;

        $suma = round($ef + $tr, 2);
        return abs($suma - $tot) <= $tolerancia;
    }

    /**
     * Valida fechas en formato YYYY-MM-DD.
     */
    public static function fecha($fecha, $formato = 'Y-m-d') {
        $d = \DateTime::createFromFormat($formato, trim((string)$fecha));
        return $d && $d->format($formato) === trim((string)$fecha);
    }

    /**
     * Valida que un rango de fechas sea coherente (Inicio <= Fin).
     */
    public static function rangoFechas($fechaInicio, $fechaFin) {
        if (!self::fecha($fechaInicio) || !self::fecha($fechaFin)) {
            return false;
        }
        return strtotime($fechaInicio) <= strtotime($fechaFin);
    }

    /**
     * Valida formato de placa vehicular de Ecuador (Ej: ABC-1234, MBA-0789, T-12345).
     */
    public static function placa($placa) {
        $placa = strtoupper(trim((string)$placa));
        return (bool)preg_match('/^[A-Z]{1,3}-?\d{3,4}$/', $placa);
    }

    /**
     * Valida longitud de contraseña.
     */
    public static function password($password, $min = 6) {
        return strlen((string)$password) >= $min;
    }

    /**
     * Sanitiza texto removiendo caracteres peligrosos y normalizando espacios.
     */
    public static function sanitizarTexto($texto) {
        if (!is_string($texto)) return '';
        $texto = strip_tags($texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
    }

    /**
     * Validador declarativo por reglas sobre un array de datos.
     * Reglas soportadas: 'required', 'cedula', 'ruc', 'identificacion', 'email', 'telefono', 'monto', 'placa', 'min:N', 'max:N'
     *
     * @param array $datos
     * @param array $reglas ['campo' => 'required|email|min:5']
     * @return bool
     */
    public function validar(array $datos, array $reglas) {
        $this->errores = [];

        foreach ($reglas as $campo => $reglaString) {
            $reglaArray = explode('|', $reglaString);
            $valor = $datos[$campo] ?? null;

            foreach ($reglaArray as $regla) {
                $params = [];
                if (strpos($regla, ':') !== false) {
                    [$regla, $paramStr] = explode(':', $regla, 2);
                    $params = explode(',', $paramStr);
                }

                switch ($regla) {
                    case 'required':
                        if ($valor === null || $valor === '' || (is_array($valor) && empty($valor))) {
                            $this->errores[$campo][] = "El campo '{$campo}' es obligatorio.";
                        }
                        break;

                    case 'cedula':
                        if (!empty($valor) && !self::cedula($valor)) {
                            $this->errores[$campo][] = "La cédula ingresada en '{$campo}' no es válida.";
                        }
                        break;

                    case 'ruc':
                        if (!empty($valor) && !self::ruc($valor)) {
                            $this->errores[$campo][] = "El RUC ingresado en '{$campo}' no es válido.";
                        }
                        break;

                    case 'email':
                        if (!empty($valor) && !self::email($valor)) {
                            $this->errores[$campo][] = "El correo electrónico en '{$campo}' tiene formato incorrecto.";
                        }
                        break;

                    case 'telefono':
                        if (!empty($valor) && !self::telefono($valor)) {
                            $this->errores[$campo][] = "El teléfono en '{$campo}' debe ser un número válido.";
                        }
                        break;

                    case 'monto':
                        $min = isset($params[0]) ? floatval($params[0]) : 0.01;
                        $max = isset($params[1]) ? floatval($params[1]) : null;
                        if (!empty($valor) && !self::monto($valor, $min, $max)) {
                            $this->errores[$campo][] = "El monto en '{$campo}' debe ser numérico y mayor a {$min}.";
                        }
                        break;

                    case 'placa':
                        if (!empty($valor) && !self::placa($valor)) {
                            $this->errores[$campo][] = "La placa vehicular en '{$campo}' no tiene un formato válido.";
                        }
                        break;

                    case 'min':
                        $minLen = (int)($params[0] ?? 0);
                        if (!empty($valor) && strlen((string)$valor) < $minLen) {
                            $this->errores[$campo][] = "El campo '{$campo}' debe tener al menos {$minLen} caracteres.";
                        }
                        break;

                    case 'max':
                        $maxLen = (int)($params[0] ?? 0);
                        if (!empty($valor) && strlen((string)$valor) > $maxLen) {
                            $this->errores[$campo][] = "El campo '{$campo}' no puede exceder {$maxLen} caracteres.";
                        }
                        break;
                }
            }
        }

        return empty($this->errores);
    }

    /**
     * Retorna el listado de errores generados.
     * @return array
     */
    public function getErrores() {
        return $this->errores;
    }

    /**
     * Retorna el primer error encontrado como string amigable.
     * @return string|null
     */
    public function getPrimerError() {
        foreach ($this->errores as $campo => $mensajes) {
            if (!empty($mensajes[0])) {
                return $mensajes[0];
            }
        }
        return null;
    }
}
