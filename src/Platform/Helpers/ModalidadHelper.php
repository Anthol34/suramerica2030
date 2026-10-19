<?php
namespace Platform\Helpers;

/**
 * Helper centralizado para la resolución, formateo y reglas de negocio
 * asociadas a Modalidades de Viaje y Operadores en Suramérica 2030.
 */
class ModalidadHelper {

    /**
     * Catálogo maestro de modalidades estándar del sistema.
     */
    public static function catalogoModalidades() {
        return [
            'ESTANDAR' => [
                'codigo' => 'ESTANDAR',
                'nombre' => 'Directo Estándar',
                'icono' => '➡️',
                'badge_class' => 'bg-success text-white',
                'descripcion' => 'Viaje directo operado por flota propia sin transbordos.'
            ],
            'PANAMERICANA' => [
                'codigo' => 'PANAMERICANA',
                'nombre' => 'Vía Panamericana',
                'icono' => '⭐',
                'badge_class' => 'bg-primary text-white',
                'descripcion' => 'Ruta directa o transbordo fronterizo en alianza con Panamericana.'
            ],
            'CONEXION_TRAMOS' => [
                'codigo' => 'CONEXION_TRAMOS',
                'nombre' => 'Conexión por Tramos',
                'icono' => '🔀',
                'badge_class' => 'bg-secondary text-white',
                'descripcion' => 'Viaje articulado mediante múltiples conexiones o transbordos.'
            ]
        ];
    }

    /**
     * Sugiere automáticamente la modalidad adecuada según el operador y los tramos.
     * 
     * Reglas condicionales:
     * 1. Si el operador es Panamericana (ID 2 o nombre contiene Panamericana) o algún tramo lo es -> 'PANAMERICANA'.
     * 2. Si tiene tramos o tipo de viaje es CONEXION y no es Panamericana -> 'CONEXION_TRAMOS'.
     * 3. Si es viaje directo y operador propio -> 'ESTANDAR'.
     */
    public static function resolverModalidadAutomatica($idOperador, $tipoViaje, array $tramos = [], $operadorNombre = '') {
        $esPanam = false;

        if (intval($idOperador) === 2 || stripos((string)$operadorNombre, 'PANAMERICANA') !== false) {
            $esPanam = true;
        }

        foreach ($tramos as $tr) {
            $trIdOp = intval($tr['id_operador'] ?? ($tr['ID_OPERADOR'] ?? 0));
            $trOpNom = $tr['operador'] ?? ($tr['OPERADOR'] ?? ($tr['OPERADOR_TRAMO_NOMBRE'] ?? ''));
            if ($trIdOp === 2 || stripos((string)$trOpNom, 'PANAMERICANA') !== false) {
                $esPanam = true;
                break;
            }
        }

        if ($esPanam) {
            return 'PANAMERICANA';
        }

        if (strtoupper($tipoViaje) === 'CONEXION' || count($tramos) > 1) {
            return 'CONEXION_TRAMOS';
        }

        return 'ESTANDAR';
    }

    /**
     * Devuelve una etiqueta amigable y formateada para la modalidad.
     */
    public static function formatearEtiqueta($modalidad, $numTramos = 0, $nombreOperador = '') {
        $modUpper = strtoupper(trim((string)$modalidad));

        if ($modUpper === 'PANAMERICANA') {
            return '⭐ Vía Panamericana' . ($numTramos > 0 ? " ({$numTramos} Tramos)" : '');
        }

        if ($modUpper === 'CONEXION_TRAMOS' || $modUpper === 'CONEXIÓN MULTITRAMO' || $modUpper === 'CONEXION MULTITRAMO') {
            $txtTramos = $numTramos > 0 ? " ({$numTramos} Tramos)" : ' (Multitramo)';
            return '🔀 Conexión por Tramos' . $txtTramos;
        }

        if ($modUpper === 'ESTANDAR') {
            return '➡️ Directo' . (!empty($nombreOperador) ? " ({$nombreOperador})" : ' Estándar');
        }

        // Si es un nombre personalizado libre, devolverlo tal cual
        return !empty($modalidad) ? trim($modalidad) : 'Estándar';
    }

    /**
     * Devuelve el HTML del badge visual correspondiente para vistas y tablas.
     */
    public static function renderBadge($modalidad, $numTramos = 0, $nombreOperador = '') {
        $modUpper = strtoupper(trim((string)$modalidad));

        if ($modUpper === 'PANAMERICANA') {
            return '<span class="badge bg-primary text-white"><i class="fas fa-star me-1"></i>Vía Panamericana</span>';
        }

        if ($modUpper === 'CONEXION_TRAMOS' || $modUpper === 'CONEXIÓN MULTITRAMO' || $modUpper === 'CONEXION MULTITRAMO') {
            $txt = $numTramos > 0 ? "🔀 {$numTramos} Tramos" : '🔀 Conexión Tramos';
            return '<span class="badge bg-secondary text-white">' . htmlspecialchars($txt) . '</span>';
        }

        if ($modUpper === 'ESTANDAR') {
            return '<span class="badge bg-success text-white">➡️ Directo</span>';
        }

        return '<span class="badge bg-info text-dark border">' . htmlspecialchars($modalidad) . '</span>';
    }
}
