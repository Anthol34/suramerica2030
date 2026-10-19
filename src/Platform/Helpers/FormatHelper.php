<?php
namespace Platform\Helpers;

/**
 * Helper de formateo y presentación visual para vistas y comprobantes.
 */
class FormatHelper {

    /**
     * Formatea un valor numérico a moneda estadounidense USD.
     */
    public static function moneda($valor, $decimales = 2, $simbolo = '$') {
        $num = floatval($valor ?? 0);
        return ($simbolo ? $simbolo . ' ' : '') . number_format($num, $decimales, '.', ',');
    }

    /**
     * Formatea una fecha SQL (YYYY-MM-DD) al formato deseado (por defecto dd/mm/yyyy).
     */
    public static function fecha($fecha, $formato = 'd/m/Y') {
        if (empty($fecha)) return 'N/D';
        $time = strtotime($fecha);
        return $time ? date($formato, $time) : 'N/D';
    }

    /**
     * Formatea fecha y hora SQL.
     */
    public static function fechaHora($fechaHora, $formato = 'd/m/Y H:i') {
        if (empty($fechaHora)) return 'N/D';
        $time = strtotime($fechaHora);
        return $time ? date($formato, $time) : 'N/D';
    }

    /**
     * Devuelve el nombre corto o largo del día de la semana en español.
     */
    public static function diaSemana($fecha, $corto = true) {
        if (empty($fecha)) return '';
        $w = (int)date('w', strtotime($fecha));
        $diasCortos = [0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb'];
        $diasLargos = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
        return $corto ? ($diasCortos[$w] ?? '') : ($diasLargos[$w] ?? '');
    }

    /**
     * Formatea peso en Kilogramos.
     */
    public static function pesoKg($kg, $decimales = 1) {
        return number_format(floatval($kg ?? 0), $decimales, '.', ',') . ' Kg';
    }

    /**
     * Devuelve un badge HTML estilizado según el estado comercial/operativo.
     */
    public static function badgeEstado($estado) {
        $estadoNorm = strtoupper(trim((string)$estado));
        switch ($estadoNorm) {
            case 'PAGADO':
            case 'ACTIVO':
            case 'ENTREGADO':
            case 'APROBADO':
                return '<span class="badge bg-success">✅ ' . htmlspecialchars($estadoNorm) . '</span>';
            case 'RESERVA':
            case 'RECIBIDO':
            case 'PENDIENTE':
            case 'POR_APROBAR':
                return '<span class="badge bg-warning text-dark">⚠️ ' . htmlspecialchars($estadoNorm) . '</span>';
            case 'EN TRANSITO':
            case 'EN TRÁNSITO':
                return '<span class="badge bg-primary">🚚 ' . htmlspecialchars($estadoNorm) . '</span>';
            case 'EN OFICINA':
                return '<span class="badge bg-info text-dark">🏢 ' . htmlspecialchars($estadoNorm) . '</span>';
            case 'DEVUELTO':
            case 'ANULADO':
            case 'RECHAZADO':
                return '<span class="badge bg-danger">❌ ' . htmlspecialchars($estadoNorm) . '</span>';
            case 'CERRADA':
                return '<span class="badge bg-secondary">🔒 ' . htmlspecialchars($estadoNorm) . '</span>';
            case 'ABIERTA':
                return '<span class="badge bg-success">🔓 ' . htmlspecialchars($estadoNorm) . '</span>';
            default:
                return '<span class="badge bg-light text-dark border">' . htmlspecialchars($estadoNorm ?: 'N/D') . '</span>';
        }
    }

    /**
     * Trunca una cadena de texto preservando palabras y añadiendo elipsis.
     */
    public static function truncar($texto, $limite = 35, $elipsis = '...') {
        if (!is_string($texto) || mb_strlen($texto) <= $limite) {
            return (string)$texto;
        }
        return mb_substr($texto, 0, $limite) . $elipsis;
    }
}
