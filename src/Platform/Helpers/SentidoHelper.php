<?php
namespace Platform\Helpers;

use Platform\Storage\Database;
use PDO;

class SentidoHelper {
    private static $catalogoPaises = null;

    /**
     * Carga y normaliza el catálogo de ciudades y países.
     */
    private static function cargarCatalogoPaises() {
        if (self::$catalogoPaises !== null) {
            return self::$catalogoPaises;
        }

        try {
            $db = (new Database())->getConnection();
            $rows = $db->query("SELECT NOMBRE, PAIS FROM CIUDAD")->fetchAll(PDO::FETCH_KEY_PAIR);
            self::$catalogoPaises = [];
            foreach ($rows as $ciu => $pais) {
                $cNorm = self::normalizarCiudad($ciu);
                self::$catalogoPaises[$cNorm] = strtoupper(trim($pais));
            }
        } catch (\Exception $e) {
            self::$catalogoPaises = [];
        }

        return self::$catalogoPaises;
    }

    public static function normalizarCiudad($ciudad) {
        $c = mb_strtoupper(trim((string)$ciudad));
        return str_replace(['Á','É','Í','Ó','Ú','À','È','Ì','Ò','Ù','Ä','Ë','Ï','Ö','Ü','Ñ'], 
                           ['A','E','I','O','U','A','E','I','O','U','A','E','I','O','U','N'], $c);
    }

    /**
     * Resuelve el sentido de viaje comercial (NORTE, SUR, NACIONAL).
     */
    public static function resolverSentido($origen, $destino) {
        $catalogo = self::cargarCatalogoPaises();

        $oNorm = self::normalizarCiudad($origen);
        $dNorm = self::normalizarCiudad($destino);

        $pOrig = $catalogo[$oNorm] ?? '';
        $pDest = $catalogo[$dNorm] ?? '';

        // Si están en el mismo país conocido
        if (!empty($pOrig) && $pOrig === $pDest) {
            return 'NACIONAL';
        }

        // Hacia el Norte (Ecuador hacia Colombia/Venezuela, o Colombia hacia Venezuela)
        if (($pOrig === 'ECUADOR' && in_array($pDest, ['COLOMBIA', 'VENEZUELA'])) ||
            ($pOrig === 'COLOMBIA' && $pDest === 'VENEZUELA')) {
            return 'NORTE';
        }

        // Hacia el Sur (Venezuela hacia Colombia/Ecuador, o Colombia hacia Ecuador)
        if (($pOrig === 'VENEZUELA' && in_array($pDest, ['COLOMBIA', 'ECUADOR'])) ||
            ($pOrig === 'COLOMBIA' && $pDest === 'ECUADOR')) {
            return 'SUR';
        }

        // Por defecto internacional Norte si no se puede clasificar
        return 'NORTE';
    }

    /**
     * Obtiene metadatos visuales del sentido para badges en tablas y reportes.
     */
    public static function getInfoSentido($sentido) {
        $sentidoUpper = strtoupper(trim((string)$sentido));
        switch ($sentidoUpper) {
            case 'NORTE':
                return [
                    'clave' => 'NORTE',
                    'icono' => '🇪🇨➔🇻🇪',
                    'nombre' => 'Sentido Norte (Ecuador ➔ Venezuela)',
                    'badge_class' => 'bg-primary text-white',
                    'tag' => 'Hacia Venezuela'
                ];
            case 'SUR':
                return [
                    'clave' => 'SUR',
                    'icono' => '🇻🇪➔🇪🇨',
                    'nombre' => 'Sentido Sur (Venezuela ➔ Ecuador)',
                    'badge_class' => 'bg-info text-dark',
                    'tag' => 'Hacia Ecuador'
                ];
            case 'NACIONAL':
                return [
                    'clave' => 'NACIONAL',
                    'icono' => '📍',
                    'nombre' => 'Trayecto Nacional',
                    'badge_class' => 'bg-secondary text-white',
                    'tag' => 'Nacional'
                ];
            default:
                return [
                    'clave' => $sentidoUpper,
                    'icono' => '🌐',
                    'nombre' => 'Ruta Comercial',
                    'badge_class' => 'bg-light text-dark border',
                    'tag' => $sentidoUpper
                ];
        }
    }
}
