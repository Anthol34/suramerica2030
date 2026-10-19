<?php
namespace Platform\Validation;

/**
 * Validador oficial de documentos de identidad ecuatorianos:
 * - Cédula de Identidad (10 dígitos con algoritmo Módulo 10).
 * - RUC Persona Natural (Cédula válida + establecimiento 001..999).
 * - RUC Sociedad Privada / Extranjeros (Módulo 11 con tercer dígito 9).
 * - RUC Entidad Pública (Módulo 11 con tercer dígito 6).
 * - Pasaporte / Documento Extranjero.
 */
class CedulaValidator {

    /**
     * Valida una cédula ecuatoriana de 10 dígitos.
     * @param string $cedula
     * @return bool
     */
    public static function validarCedula($cedula) {
        $cedula = trim((string)$cedula);
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int)substr($cedula, 0, 2);
        if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
            return false;
        }

        $tercerDigito = (int)$cedula[2];
        if ($tercerDigito < 0 || $tercerDigito > 5) {
            return false;
        }

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $valor = (int)$cedula[$i] * $coeficientes[$i];
            if ($valor >= 10) {
                $valor -= 9;
            }
            $suma += $valor;
        }

        $digitoVerificadorCalculado = (10 - ($suma % 10)) % 10;
        $digitoVerificadorReal = (int)$cedula[9];

        return $digitoVerificadorCalculado === $digitoVerificadorReal;
    }

    /**
     * Valida un RUC ecuatoriano de 13 dígitos.
     * @param string $ruc
     * @return bool
     */
    public static function validarRUC($ruc) {
        $ruc = trim((string)$ruc);
        if (!preg_match('/^\d{13}$/', $ruc)) {
            return false;
        }

        $provincia = (int)substr($ruc, 0, 2);
        if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
            return false;
        }

        $tercerDigito = (int)$ruc[2];

        // 1. RUC de Persona Natural (tercer dígito < 6)
        if ($tercerDigito >= 0 && $tercerDigito <= 5) {
            $cedula = substr($ruc, 0, 10);
            $establecimiento = substr($ruc, 10, 3);
            return self::validarCedula($cedula) && ((int)$establecimiento > 0);
        }

        // 2. RUC de Sociedad Privada / Extranjero sin cédula (tercer dígito = 9)
        if ($tercerDigito === 9) {
            $establecimiento = substr($ruc, 10, 3);
            if ((int)$establecimiento < 1) return false;

            $coeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
            $suma = 0;
            for ($i = 0; $i < 9; $i++) {
                $suma += (int)$ruc[$i] * $coeficientes[$i];
            }
            $residuo = $suma % 11;
            $digitoVerificador = ($residuo === 0) ? 0 : 11 - $residuo;
            return $digitoVerificador === (int)$ruc[9];
        }

        // 3. RUC de Entidad Pública (tercer dígito = 6)
        if ($tercerDigito === 6) {
            $establecimiento = substr($ruc, 9, 4);
            if ((int)$establecimiento < 1) return false;

            $coeficientes = [3, 2, 7, 6, 5, 4, 3, 2];
            $suma = 0;
            for ($i = 0; $i < 8; $i++) {
                $suma += (int)$ruc[$i] * $coeficientes[$i];
            }
            $residuo = $suma % 11;
            $digitoVerificador = ($residuo === 0) ? 0 : 11 - $residuo;
            return $digitoVerificador === (int)$ruc[8];
        }

        return false;
    }

    /**
     * Valida identificación según tipo (CEDULA, RUC, PASAPORTE).
     * @param string $tipo
     * @param string $numero
     * @return bool
     */
    public static function validar($tipo, $numero) {
        $tipo = strtoupper(trim((string)$tipo));
        $numero = trim((string)$numero);

        if ($tipo === 'CEDULA' || $tipo === 'CI' || $tipo === 'CÉDULA') {
            return self::validarCedula($numero);
        }
        if ($tipo === 'RUC') {
            return self::validarRUC($numero);
        }
        if ($tipo === 'PASAPORTE' || $tipo === 'EXTRANJERO') {
            return (bool)preg_match('/^[A-Za-z0-9\-\.]{4,20}$/', $numero);
        }

        // Si no se especifica tipo, intentar inferir por longitud:
        if (strlen($numero) === 10) {
            return self::validarCedula($numero);
        }
        if (strlen($numero) === 13) {
            return self::validarRUC($numero);
        }

        return strlen($numero) >= 4 && strlen($numero) <= 20;
    }
}
