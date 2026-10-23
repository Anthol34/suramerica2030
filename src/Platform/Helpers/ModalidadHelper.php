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
            'CONEXIONES' => [
                'codigo' => 'CONEXIONES',
                'nombre' => 'Conexiones Locales',
                'icono' => '🔀',
                'badge_class' => 'bg-secondary text-white',
                'descripcion' => 'Viaje articulado mediante conexiones con aliados locales en cada país.'
            ],
            'PANAMERICANA' => [
                'codigo' => 'PANAMERICANA',
                'nombre' => 'Vía Panamericana',
                'icono' => '🚌',
                'badge_class' => 'bg-warning-subtle text-dark border border-warning',
                'descripcion' => 'Ruta directa binacional internacional en alianza con Panamericana.'
            ],
            'EXPRESOS_SURAMERICA' => [
                'codigo' => 'EXPRESOS_SURAMERICA',
                'nombre' => 'Expresos Suramérica 2030',
                'icono' => '🚍',
                'badge_class' => 'bg-primary text-white',
                'descripcion' => 'Ruta con tramo troncal venezolano operado por Expresos Suramérica 2030.'
            ],
            'PANA_EXPRESOS' => [
                'codigo' => 'PANA_EXPRESOS',
                'nombre' => 'Alianza Pana-Expresos',
                'icono' => '⚡',
                'badge_class' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                'descripcion' => 'Combinación óptima: Salto internacional Panamericana + Troncal Expresos Suramérica.'
            ]
        ];
    }

    /**
     * Sugiere automáticamente la modalidad adecuada según el operador y los tramos.
     */
    public static function resolverModalidadAutomatica($idOperador, $tipoViaje, array $tramos = [], $operadorNombre = '') {
        $tienePanam = false;
        $tieneSuramerica = false;
        $tieneLocales = false;

        if (intval($idOperador) === 2 || stripos((string)$operadorNombre, 'PANAMERICANA') !== false) {
            $tienePanam = true;
        }
        if (intval($idOperador) === 1 || stripos((string)$operadorNombre, 'SURAMERICA') !== false) {
            $tieneSuramerica = true;
        }

        foreach ($tramos as $tr) {
            $trIdOp = intval($tr['id_operador'] ?? ($tr['ID_OPERADOR'] ?? 0));
            $trOpNom = $tr['operador'] ?? ($tr['OPERADOR'] ?? ($tr['OPERADOR_TRAMO_NOMBRE'] ?? ''));

            if ($trIdOp === 2 || stripos((string)$trOpNom, 'PANAMERICANA') !== false) {
                $tienePanam = true;
            } elseif ($trIdOp === 1 || stripos((string)$trOpNom, 'SURAMERICA') !== false) {
                $tieneSuramerica = true;
            } else {
                $tieneLocales = true;
            }
        }

        if ($tienePanam && $tieneSuramerica) {
            return 'PANA_EXPRESOS';
        }
        if ($tienePanam) {
            return 'PANAMERICANA';
        }
        if ($tieneSuramerica && count($tramos) > 1) {
            return 'EXPRESOS_SURAMERICA';
        }
        if (strtoupper($tipoViaje) === 'CONEXION' || count($tramos) > 1) {
            return 'CONEXIONES';
        }

        return 'ESTANDAR';
    }

    /**
     * Devuelve una etiqueta amigable y formateada para la modalidad.
     */
    public static function formatearEtiqueta($modalidad, $numTramos = 0, $nombreOperador = '') {
        $modUpper = strtoupper(trim((string)$modalidad));

        if ($modUpper === 'PANA_EXPRESOS' || $modUpper === 'PANA-EXPRESOS') {
            return '⚡ Alianza Pana-Expresos' . ($numTramos > 0 ? " ({$numTramos} Tramos)" : '');
        }

        if ($modUpper === 'PANAMERICANA') {
            return '🚌 Vía Panamericana' . ($numTramos > 0 ? " ({$numTramos} Tramos)" : '');
        }

        if ($modUpper === 'EXPRESOS_SURAMERICA' || $modUpper === 'SURAMERICA' || $modUpper === 'EXPRESOS SURAMERICA') {
            return '🚍 Expresos Suramérica 2030' . ($numTramos > 0 ? " ({$numTramos} Tramos)" : '');
        }

        if ($modUpper === 'CONEXIONES' || $modUpper === 'CONEXION_TRAMOS' || $modUpper === 'CONEXIÓN MULTITRAMO' || $modUpper === 'CONEXION MULTITRAMO') {
            $txtTramos = $numTramos > 0 ? " ({$numTramos} Tramos)" : ' (Multitramo)';
            return '🔀 Conexiones Locales' . $txtTramos;
        }

        if ($modUpper === 'ESTANDAR') {
            return '➡️ Directo' . (!empty($nombreOperador) ? " ({$nombreOperador})" : ' Estándar');
        }

        return !empty($modalidad) ? trim($modalidad) : 'Estándar';
    }

    /**
     * Devuelve el HTML del badge visual correspondiente para vistas y tablas.
     */
    public static function renderBadge($modalidad, $numTramos = 0, $nombreOperador = '') {
        $modUpper = strtoupper(trim((string)$modalidad));

        if ($modUpper === 'PANA_EXPRESOS' || $modUpper === 'PANA-EXPRESOS') {
            $txt = $numTramos > 0 ? "⚡ Pana-Expresos ({$numTramos}T)" : '⚡ Pana-Expresos';
            return '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">' . htmlspecialchars($txt) . '</span>';
        }

        if ($modUpper === 'PANAMERICANA') {
            $txt = $numTramos > 0 ? "🚌 Panamericana ({$numTramos}T)" : '🚌 Panamericana';
            return '<span class="badge bg-warning-subtle text-dark border border-warning">' . htmlspecialchars($txt) . '</span>';
        }

        if ($modUpper === 'EXPRESOS_SURAMERICA' || $modUpper === 'SURAMERICA' || $modUpper === 'EXPRESOS SURAMERICA') {
            $txt = $numTramos > 0 ? "🚍 Suramérica ({$numTramos}T)" : '🚍 Suramérica 2030';
            return '<span class="badge bg-primary text-white border-0">' . htmlspecialchars($txt) . '</span>';
        }

        if ($modUpper === 'CONEXIONES' || $modUpper === 'CONEXION_TRAMOS' || $modUpper === 'CONEXIÓN MULTITRAMO' || $modUpper === 'CONEXION MULTITRAMO') {
            $txt = $numTramos > 0 ? "🔀 Conexiones ({$numTramos}T)" : '🔀 Conexiones Locales';
            return '<span class="badge bg-secondary text-white">' . htmlspecialchars($txt) . '</span>';
        }

        if ($modUpper === 'ESTANDAR') {
            return '<span class="badge bg-success text-white">➡️ Directo</span>';
        }

        return '<span class="badge bg-light text-dark border">' . htmlspecialchars($modalidad) . '</span>';
    }
}
