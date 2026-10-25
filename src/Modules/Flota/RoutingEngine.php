<?php
namespace Modules\Flota;

use PDO;
use Exception;
use Platform\Helpers\ModalidadHelper;

/**
 * RoutingEngine: Motor Multimodal de Enrutamiento y Composición de Itinerarios
 * 
 * Modela la red de transporte internacional y nacional conectando Ecuador, Colombia y Venezuela
 * mediante 4 modalidades comerciales estratégicas:
 *  1. CONEXIONES: Puro aliados locales (Local EC, Local COL, Local VE).
 *  2. PANAMERICANA: Salto binacional directo Panamericana + Aliados.
 *  3. EXPRESOS_SURAMERICA: Troncal San Cristóbal-Caracas con Suramérica 2030 + Aliados.
 *  4. PANA_EXPRESOS: Alianza estratégica Panamericana (internacional) + Suramérica 2030 (troncal VE).
 */
class RoutingEngine {

    private $db;
    private $catalogoTramos = null;
    private $ciudadesPaises = null;
    private $puentesFronterizos = null;
    private $ofertasOperadores = null;

    public function __construct(?PDO $db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = (new \Platform\Storage\Database())->getConnection();
        }
    }

    /**
     * Carga en memoria el catálogo completo de tramos activos, puentes fronterizos y ofertas
     * para consultas de alta velocidad.
     */
    private function cargarCatalogo() {
        if ($this->catalogoTramos === null) {
            $sql = "SELECT tc.*, op.NOMBRE_COMERCIAL as OP_NOMBRE_REAL 
                    FROM tramo_catalogo tc 
                    LEFT JOIN OPERADOR op ON tc.ID_OPERADOR = op.ID_OPERADOR
                    WHERE tc.ESTADO = 'ACTIVO'";
            $this->catalogoTramos = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($this->ciudadesPaises === null) {
            $this->ciudadesPaises = [];
            $rows = $this->db->query("SELECT NOMBRE, PAIS FROM CIUDAD")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $this->ciudadesPaises[mb_strtolower(trim($r['NOMBRE']))] = strtoupper(trim($r['PAIS']));
            }
        }

        if ($this->puentesFronterizos === null) {
            $this->puentesFronterizos = [];
            try {
                $puentes = $this->db->query("SELECT * FROM PUENTE_FRONTERIZO WHERE ACTIVO = 1")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($puentes as $p) {
                    $orig = mb_strtolower(trim($p['CIUDAD_A']));
                    $dest = mb_strtolower(trim($p['CIUDAD_B']));
                    $this->puentesFronterizos[$orig][$dest] = $p;
                }
            } catch (\Exception $e) {
                $this->puentesFronterizos = [];
            }
        }

        if ($this->ofertasOperadores === null) {
            $this->ofertasOperadores = [];
            try {
                $sqlOf = "SELECT oot.*, op.NOMBRE_COMERCIAL as OPERADOR_NOMBRE, op.TIPO_OPERADOR 
                          FROM OPERADOR_OFERTA_TRAMO oot 
                          JOIN OPERADOR op ON oot.ID_OPERADOR = op.ID_OPERADOR 
                          WHERE oot.ACTIVO = 1";
                $rowsOf = $this->db->query($sqlOf)->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rowsOf as $ro) {
                    $this->ofertasOperadores[(int)$ro['ID_TRAMO_CATALOGO']][] = $ro;
                }
            } catch (\Exception $e) {
                $this->ofertasOperadores = [];
            }
        }
    }

    /**
     * Devuelve el mapa de puentes fronterizos activos.
     */
    public function obtenerPuentesFronterizos() {
        $this->cargarCatalogo();
        return $this->puentesFronterizos;
    }

    /**
     * Determina si dos ciudades están unidas por un paso o puente fronterizo internacional.
     */
    public function esPuenteFronterizo($ciudadA, $ciudadB) {
        $this->cargarCatalogo();
        $cA = mb_strtolower(trim($ciudadA));
        $cB = mb_strtolower(trim($ciudadB));
        return isset($this->puentesFronterizos[$cA][$cB]);
    }

    /**
     * Obtiene los metadatos de un puente fronterizo entre dos ciudades.
     */
    public function obtenerPuenteFronterizo($ciudadA, $ciudadB) {
        $this->cargarCatalogo();
        $cA = mb_strtolower(trim($ciudadA));
        $cB = mb_strtolower(trim($ciudadB));
        return $this->puentesFronterizos[$cA][$cB] ?? null;
    }

    /**
     * Verifica si dos ciudades son continuas en un itinerario:
     * idéntica ciudad o conectadas mediante puente fronterizo hermano (ej: Tulcán = Ipiales).
     */
    public function sonCiudadesConectables($ciudadA, $ciudadB) {
        if (strcasecmp(trim($ciudadA), trim($ciudadB)) === 0) {
            return true;
        }
        return $this->esPuenteFronterizo($ciudadA, $ciudadB);
    }

    /**
     * Obtiene las ofertas activas de operadores para un tramo de catálogo.
     */
    public function obtenerOfertasTramo($idTramoCatalogo) {
        $this->cargarCatalogo();
        return $this->ofertasOperadores[(int)$idTramoCatalogo] ?? [];
    }

    /**
     * Resuelve el país de una ciudad con tolerancia de mayúsculas/minúsculas.
     */
    public function obtenerPaisCiudad($ciudad) {
        $this->cargarCatalogo();
        $cNorm = mb_strtolower(trim($ciudad));

        if (isset($this->ciudadesPaises[$cNorm])) {
            return $this->ciudadesPaises[$cNorm];
        }

        // Si no está en tabla CIUDAD, inferir de tramo_catalogo
        foreach ($this->catalogoTramos as $t) {
            if (mb_strtolower(trim($t['ORIGEN_TRAMO'])) === $cNorm || mb_strtolower(trim($t['DESTINO_TRAMO'])) === $cNorm) {
                $p = strtoupper(trim($t['PAIS_TRAMO']));
                if ($p !== 'INTERNACIONAL') {
                    return $p;
                }
            }
        }

        return 'INTERNACIONAL';
    }

    /**
     * Busca el tramo ideal en el catálogo, priorizando un ID de operador si se solicita.
     */
    public function buscarTramo($origen, $destino, $preferIdOperador = null) {
        $this->cargarCatalogo();
        $oNorm = mb_strtolower(trim($origen));
        $dNorm = mb_strtolower(trim($destino));

        $coincidencias = [];

        foreach ($this->catalogoTramos as $t) {
            if (mb_strtolower(trim($t['ORIGEN_TRAMO'])) === $oNorm && mb_strtolower(trim($t['DESTINO_TRAMO'])) === $dNorm) {
                if ($preferIdOperador !== null && intval($t['ID_OPERADOR']) === intval($preferIdOperador)) {
                    return $t; // Coincidencia exacta de operador
                }
                $coincidencias[] = $t;
            }
        }

        // Si no hay match con el operador preferido, retornar la primera coincidencia activa
        return !empty($coincidencias) ? $coincidencias[0] : null;
    }

    /**
     * Sugiere y genera todas las modalidades comerciales disponibles para un par Origen -> Destino.
     * 
     * @return array Mapa asociativo de itinerarios indexados por código de modalidad.
     */
    public function sugerirTodasLasModalidades($origen, $destino) {
        $origen = trim($origen);
        $destino = trim($destino);

        if (empty($origen) || empty($destino) || strcasecmp($origen, $destino) === 0) {
            return [];
        }

        $paisOrig = $this->obtenerPaisCiudad($origen);
        $paisDest = $this->obtenerPaisCiudad($destino);

        $resultado = [];

        // 2. Modalidad Conexiones (Empresas Locales EC, COL, VE)
        $itConexiones = $this->construirItinerarioConexiones($origen, $destino, $paisOrig, $paisDest);
        if ($itConexiones) {
            $resultado['CONEXIONES'] = $itConexiones;
        }

        // 3. Modalidad Panamericana (Salto binacional directo)
        $itPanam = $this->construirItinerarioPanamericana($origen, $destino, $paisOrig, $paisDest);
        if ($itPanam) {
            $resultado['PANAMERICANA'] = $itPanam;
        }

        // 4. Modalidad Expresos Suramérica 2030 (Troncal Venezuela propia)
        $itSur = $this->construirItinerarioExpresosSuramerica($origen, $destino, $paisOrig, $paisDest);
        if ($itSur) {
            $resultado['EXPRESOS_SURAMERICA'] = $itSur;
        }

        // 5. Modalidad Pana-Expresos (Alianza Panamericana + Suramérica 2030)
        $itPanaExp = $this->construirItinerarioPanaExpresos($origen, $destino, $paisOrig, $paisDest);
        if ($itPanaExp) {
            $resultado['PANA_EXPRESOS'] = $itPanaExp;
        }

        // 6. Enriquecer con alternativas del motor de grafo BFS solo si falta alguna modalidad estándar
        $caminosBfs = $this->sugerirCaminosDisponibles($origen, $destino, 4);
        foreach ($caminosBfs as $camBfs) {
            $keyBfs = $camBfs['modalidad'];
            if (!isset($resultado[$keyBfs])) {
                $resultado[$keyBfs] = $camBfs;
            }
        }

        return $resultado;
    }

    /**
     * Sugiere un itinerario específico para una modalidad deseada.
     */
    public function sugerirTramosParaModalidad($origen, $destino, $modalidad = 'CONEXIONES') {
        $todas = $this->sugerirTodasLasModalidades($origen, $destino);

        $modNorm = strtoupper(trim($modalidad));
        if ($modNorm === 'CONEXION_TRAMOS') $modNorm = 'CONEXIONES';
        if ($modNorm === 'SURAMERICA') $modNorm = 'EXPRESOS_SURAMERICA';

        if (isset($todas[$modNorm])) {
            return $todas[$modNorm];
        }

        // Si no está la modalidad solicitada, retornar la primera disponible
        if (!empty($todas)) {
            return reset($todas);
        }

        // Fallback básico si ninguna ruta predeterminada coincide
        return $this->generarItinerarioFallback($origen, $destino, $modalidad);
    }

    // =========================================================================
    // CONSTRUCTORES DE ITINERARIOS POR MODALIDAD
    // =========================================================================

    /**
     * MODALIDAD DIRECTO / ESTÁNDAR
     */
    private function construirItinerarioDirecto($origen, $destino) {
        $tramo = $this->buscarTramo($origen, $destino, 1);
        if (!$tramo) {
            $tramo = $this->buscarTramo($origen, $destino);
        }

        if (!$tramo) return null;

        return $this->armarObjetoItinerario(
            'ESTANDAR',
            'Directo Estándar',
            '➡️',
            'bg-success text-white',
            'Viaje directo sin transbordos operado en tramo único.',
            $tramo['OPERADOR_NOMBRE'] ?: 'Expresos Suramérica 2030',
            $tramo['ID_OPERADOR'] ?: 1,
            [[$origen, $destino, $tramo['ID_OPERADOR']]]
        );
    }

    /**
     * MODALIDAD 1: CONEXIONES (Puro aliados locales)
     * Manta -> Tulcán (Local EC) -> Ipiales (Local EC/COL) -> Cúcuta (Local COL) -> San Cristóbal (Local VE) -> Destino VE (Local VE)
     */
    private function construirItinerarioConexiones($origen, $destino, $paisOrig, $paisDest) {
        $pares = [];

        // CASO A: Ecuador ➔ Venezuela
        if ($paisOrig === 'ECUADOR' && $paisDest === 'VENEZUELA') {
            if (strcasecmp($origen, 'Tulcán') !== 0) {
                $pares[] = [$origen, 'Tulcán', 3]; // Empresa Local Ecuador
            }
            // Cruce binacional Rumichaca (Tulcán ⇄ Ipiales) se gestiona vía PUENTE_FRONTERIZO
            $pares[] = ['Ipiales', 'Cúcuta', 4];   // Empresa Local Colombia
            // Cruce binacional Atanasio Girardot (Cúcuta ⇄ San Cristóbal) vía PUENTE_FRONTERIZO

            if (strcasecmp($destino, 'San Cristóbal') !== 0) {
                // Verificar si existe tramo directo San Cristóbal -> Destino
                $trDirectoVE = $this->buscarTramo('San Cristóbal', $destino, 5);
                if ($trDirectoVE) {
                    $pares[] = ['San Cristóbal', $destino, 5];
                } else {
                    // Ruta vía Caracas
                    $pares[] = ['San Cristóbal', 'Caracas', 5];
                    if (strcasecmp($destino, 'Caracas') !== 0) {
                        $pares[] = ['Caracas', $destino, 5];
                    }
                }
            }
        }
        // CASO B: Venezuela ➔ Ecuador
        elseif ($paisOrig === 'VENEZUELA' && $paisDest === 'ECUADOR') {
            if (strcasecmp($origen, 'San Cristóbal') !== 0) {
                $trDirectoVE = $this->buscarTramo($origen, 'San Cristóbal', 5);
                if ($trDirectoVE) {
                    $pares[] = [$origen, 'San Cristóbal', 5];
                } else {
                    if (strcasecmp($origen, 'Caracas') !== 0) {
                        $pares[] = [$origen, 'Caracas', 5];
                    }
                    $pares[] = ['Caracas', 'San Cristóbal', 5];
                }
            }
            // Cruce binacional Atanasio Girardot vía PUENTE_FRONTERIZO
            $pares[] = ['Cúcuta', 'Ipiales', 4];
            // Cruce binacional Rumichaca vía PUENTE_FRONTERIZO
            if (strcasecmp($destino, 'Tulcán') !== 0) {
                $pares[] = ['Tulcán', $destino, 3];
            }
        }
        // CASO C: Colombia ➔ Venezuela
        elseif ($paisOrig === 'COLOMBIA' && $paisDest === 'VENEZUELA') {
            if (strcasecmp($origen, 'Cúcuta') !== 0) {
                $pares[] = [$origen, 'Cúcuta', 4];
            }
            // Cruce binacional Atanasio Girardot vía PUENTE_FRONTERIZO
            if (strcasecmp($destino, 'San Cristóbal') !== 0) {
                $trDirectoVE = $this->buscarTramo('San Cristóbal', $destino, 5);
                if ($trDirectoVE) {
                    $pares[] = ['San Cristóbal', $destino, 5];
                } else {
                    $pares[] = ['San Cristóbal', 'Caracas', 5];
                    if (strcasecmp($destino, 'Caracas') !== 0) {
                        $pares[] = ['Caracas', $destino, 5];
                    }
                }
            }
        }
        // CASO D: Venezuela ➔ Colombia
        elseif ($paisOrig === 'VENEZUELA' && $paisDest === 'COLOMBIA') {
            if (strcasecmp($origen, 'San Cristóbal') !== 0) {
                $trDirectoVE = $this->buscarTramo($origen, 'San Cristóbal', 5);
                if ($trDirectoVE) {
                    $pares[] = [$origen, 'San Cristóbal', 5];
                } else {
                    if (strcasecmp($origen, 'Caracas') !== 0) {
                        $pares[] = [$origen, 'Caracas', 5];
                    }
                    $pares[] = ['Caracas', 'San Cristóbal', 5];
                }
            }
            // Cruce binacional Atanasio Girardot vía PUENTE_FRONTERIZO
            if (strcasecmp($destino, 'Cúcuta') !== 0) {
                $pares[] = ['Cúcuta', $destino, 4];
            }
        }
        // CASO E: Nacional o tramo interno
        else {
            $pares[] = [$origen, $destino, null];
        }

        return $this->armarObjetoItinerario(
            'CONEXIONES',
            'Conexiones Locales',
            '🔀',
            'bg-secondary text-white',
            'Itinerario articulado mediante conexiones con transportistas locales en cada territorio.',
            'Empresas Locales Aliadas',
            3,
            $pares
        );
    }

    /**
     * MODALIDAD 2: PANAMERICANA (Salto binacional directo)
     * Manta -> Cúcuta (Panamericana) -> San Cristóbal (Local VE) -> Destino VE (Local VE)
     */
    private function construirItinerarioPanamericana($origen, $destino, $paisOrig, $paisDest) {
        $pares = [];

        // Aplica principalmente a viajes internacionales entre Ecuador y Venezuela/Colombia
        if ($paisOrig === 'ECUADOR' && $paisDest === 'VENEZUELA') {
            // Tramo 1: Salto Panamericana (Ecuador -> Cúcuta)
            $trPana = $this->buscarTramo($origen, 'Cúcuta', 2);
            if ($trPana) {
                $pares[] = [$origen, 'Cúcuta', 2];
            } else {
                // Si la ciudad no tiene directo con Panamericana, conectar hacia Quito primero
                if (strcasecmp($origen, 'Quito') !== 0) {
                    $pares[] = [$origen, 'Quito', 3];
                }
                $pares[] = ['Quito', 'Cúcuta', 2];
            }

            // Cruce binacional Atanasio Girardot (Cúcuta ⇄ San Cristóbal) se gestiona vía PUENTE_FRONTERIZO

            // Tramo 2: Distribución en Venezuela
            if (strcasecmp($destino, 'San Cristóbal') !== 0) {
                $trDirectoVE = $this->buscarTramo('San Cristóbal', $destino, 5);
                if ($trDirectoVE) {
                    $pares[] = ['San Cristóbal', $destino, 5];
                } else {
                    $pares[] = ['San Cristóbal', 'Caracas', 5];
                    if (strcasecmp($destino, 'Caracas') !== 0) {
                        $pares[] = ['Caracas', $destino, 5];
                    }
                }
            }
        } elseif ($paisOrig === 'VENEZUELA' && $paisDest === 'ECUADOR') {
            // Tramo 1: Venezuela -> San Cristóbal
            if (strcasecmp($origen, 'San Cristóbal') !== 0) {
                $trDirectoVE = $this->buscarTramo($origen, 'San Cristóbal', 5);
                if ($trDirectoVE) {
                    $pares[] = [$origen, 'San Cristóbal', 5];
                } else {
                    if (strcasecmp($origen, 'Caracas') !== 0) {
                        $pares[] = [$origen, 'Caracas', 5];
                    }
                    $pares[] = ['Caracas', 'San Cristóbal', 5];
                }
            }

            // Cruce binacional Atanasio Girardot (San Cristóbal ⇄ Cúcuta) vía PUENTE_FRONTERIZO

            // Tramo 2: Salto Panamericana (Cúcuta -> Ecuador)
            $trPana = $this->buscarTramo('Cúcuta', $destino, 2);
            if ($trPana) {
                $pares[] = ['Cúcuta', $destino, 2];
            } else {
                $pares[] = ['Cúcuta', 'Quito', 2];
                if (strcasecmp($destino, 'Quito') !== 0) {
                    $pares[] = ['Quito', $destino, 3];
                }
            }
        } elseif ($paisOrig === 'ECUADOR' && $paisDest === 'COLOMBIA') {
            $trDirectoPana = $this->buscarTramo($origen, $destino, 2);
            if ($trDirectoPana) {
                $pares[] = [$origen, $destino, 2];
            } else {
                $trPana = $this->buscarTramo($origen, 'Cúcuta', 2);
                if ($trPana) {
                    $pares[] = [$origen, 'Cúcuta', 2];
                } else {
                    if (strcasecmp($origen, 'Quito') !== 0) {
                        $pares[] = [$origen, 'Quito', 3];
                    }
                    $pares[] = ['Quito', 'Cúcuta', 2];
                }
                if (strcasecmp($destino, 'Cúcuta') !== 0) {
                    $pares[] = ['Cúcuta', $destino, 4];
                }
            }
        } elseif ($paisOrig === 'COLOMBIA' && $paisDest === 'ECUADOR') {
            $trDirectoPana = $this->buscarTramo($origen, $destino, 2);
            if ($trDirectoPana) {
                $pares[] = [$origen, $destino, 2];
            } else {
                if (strcasecmp($origen, 'Cúcuta') !== 0) {
                    $pares[] = [$origen, 'Cúcuta', 4];
                }
                $trPana = $this->buscarTramo('Cúcuta', $destino, 2);
                if ($trPana) {
                    $pares[] = ['Cúcuta', $destino, 2];
                } else {
                    $pares[] = ['Cúcuta', 'Quito', 2];
                    if (strcasecmp($destino, 'Quito') !== 0) {
                        $pares[] = ['Quito', $destino, 3];
                    }
                }
            }
        } else {
            // Panamericana no opera nacional o tramos locales no aplicables
            return null;
        }

        return $this->armarObjetoItinerario(
            'PANAMERICANA',
            'Vía Panamericana',
            '🚌',
            'bg-warning-subtle text-dark border border-warning',
            'Ruta internacional rápida: Salto binacional directo sin cambio de bus en Tulcán/Ipiales.',
            'Panamericana',
            2,
            $pares
        );
    }

    /**
     * MODALIDAD 3: EXPRESOS SURAMÉRICA 2030 (Troncal Venezuela propia)
     * Manta -> Tulcán -> Ipiales -> Cúcuta -> San Cristóbal -> Caracas (Suramérica 2030) -> Destino VE (Local VE)
     */
    private function construirItinerarioExpresosSuramerica($origen, $destino, $paisOrig, $paisDest) {
        $pares = [];

        // Ecuador ➔ Venezuela
        if ($paisOrig === 'ECUADOR' && $paisDest === 'VENEZUELA') {
            if (strcasecmp($origen, 'Tulcán') !== 0) {
                $pares[] = [$origen, 'Tulcán', 3];
            }
            // Cruce Rumichaca vía PUENTE_FRONTERIZO
            $pares[] = ['Ipiales', 'Cúcuta', 4];
            // Cruce Atanasio Girardot vía PUENTE_FRONTERIZO

            // Troncal venezolana con Expresos Suramérica 2030
            $ciudadesTroncales = ['Caracas', 'Valencia', 'Maracay', 'Barquisimeto', 'Maracaibo'];
            $esTroncalDirecta = false;
            foreach ($ciudadesTroncales as $ct) {
                if (strcasecmp($destino, $ct) === 0) {
                    $esTroncalDirecta = true;
                    break;
                }
            }

            if (strcasecmp($destino, 'San Cristóbal') !== 0) {
                if ($esTroncalDirecta) {
                    $pares[] = ['San Cristóbal', $destino, 1]; // Expresos Suramérica 2030 directo
                } else {
                    $pares[] = ['San Cristóbal', 'Caracas', 1]; // Expresos Suramérica 2030 troncal
                    if (strcasecmp($destino, 'Caracas') !== 0) {
                        $pares[] = ['Caracas', $destino, 5];     // Distribución con aliado local
                    }
                }
            }
        }
        // Venezuela ➔ Ecuador
        elseif ($paisOrig === 'VENEZUELA' && $paisDest === 'ECUADOR') {
            $ciudadesTroncales = ['Caracas', 'Valencia', 'Maracay', 'Barquisimeto', 'Maracaibo'];
            $esTroncalDirecta = false;
            foreach ($ciudadesTroncales as $ct) {
                if (strcasecmp($origen, $ct) === 0) {
                    $esTroncalDirecta = true;
                    break;
                }
            }

            if ($esTroncalDirecta) {
                $pares[] = [$origen, 'San Cristóbal', 1]; // Expresos Suramérica 2030 directo
            } else {
                if (strcasecmp($origen, 'San Cristóbal') !== 0) {
                    $pares[] = [$origen, 'Caracas', 5];
                    $pares[] = ['Caracas', 'San Cristóbal', 1];
                }
            }

            // Cruce Atanasio Girardot vía PUENTE_FRONTERIZO
            $pares[] = ['Cúcuta', 'Ipiales', 4];
            // Cruce Rumichaca vía PUENTE_FRONTERIZO
            if (strcasecmp($destino, 'Tulcán') !== 0) {
                $pares[] = ['Tulcán', $destino, 3];
            }
        }
        // Colombia ➔ Venezuela
        elseif ($paisOrig === 'COLOMBIA' && $paisDest === 'VENEZUELA') {
            if (strcasecmp($origen, 'Cúcuta') !== 0) {
                $pares[] = [$origen, 'Cúcuta', 4];
            }
            // Cruce Atanasio Girardot vía PUENTE_FRONTERIZO
            $pares[] = ['San Cristóbal', 'Caracas', 1];
            if (strcasecmp($destino, 'Caracas') !== 0) {
                $pares[] = ['Caracas', $destino, 5];
            }
        }
        // Nacional Venezuela (San Cristóbal - Caracas / Oriente)
        elseif ($paisOrig === 'VENEZUELA' && $paisDest === 'VENEZUELA') {
            $trDirectoSur = $this->buscarTramo($origen, $destino, 1);
            if ($trDirectoSur) {
                $pares[] = [$origen, $destino, 1];
            } else {
                $pares[] = [$origen, 'Caracas', 1];
                $pares[] = ['Caracas', $destino, 5];
            }
        } else {
            return null;
        }

        return $this->armarObjetoItinerario(
            'EXPRESOS_SURAMERICA',
            'Expresos Suramérica 2030',
            '🚍',
            'bg-primary text-white',
            'Conexiones andinas con el tramo troncal venezolano operado por Expresos Suramérica 2030.',
            'Expresos Suramérica 2030',
            1,
            $pares
        );
    }

    /**
     * MODALIDAD 4: PANA-EXPRESOS (Alianza Panamericana + Suramérica 2030)
     * Manta -> Cúcuta (Panamericana) -> San Cristóbal (Local VE) -> Caracas (Suramérica 2030) -> Destino VE (Local VE)
     */
    private function construirItinerarioPanaExpresos($origen, $destino, $paisOrig, $paisDest) {
        $pares = [];

        // Ecuador ➔ Venezuela
        if ($paisOrig === 'ECUADOR' && $paisDest === 'VENEZUELA') {
            // 1. Salto Panamericana
            $trPana = $this->buscarTramo($origen, 'Cúcuta', 2);
            if ($trPana) {
                $pares[] = [$origen, 'Cúcuta', 2];
            } else {
                if (strcasecmp($origen, 'Quito') !== 0) {
                    $pares[] = [$origen, 'Quito', 3];
                }
                $pares[] = ['Quito', 'Cúcuta', 2];
            }

            // Cruce binacional Atanasio Girardot (Cúcuta ⇄ San Cristóbal) vía PUENTE_FRONTERIZO

            // 2. Troncal Venezuela con Expresos Suramérica 2030
            $ciudadesTroncales = ['Caracas', 'Valencia', 'Maracay', 'Barquisimeto', 'Maracaibo'];
            $esTroncalDirecta = false;
            foreach ($ciudadesTroncales as $ct) {
                if (strcasecmp($destino, $ct) === 0) {
                    $esTroncalDirecta = true;
                    break;
                }
            }

            if (strcasecmp($destino, 'San Cristóbal') !== 0) {
                if ($esTroncalDirecta) {
                    $pares[] = ['San Cristóbal', $destino, 1];
                } else {
                    $pares[] = ['San Cristóbal', 'Caracas', 1];
                    if (strcasecmp($destino, 'Caracas') !== 0) {
                        $pares[] = ['Caracas', $destino, 5];
                    }
                }
            }
        }
        // Venezuela ➔ Ecuador
        elseif ($paisOrig === 'VENEZUELA' && $paisDest === 'ECUADOR') {
            $ciudadesTroncales = ['Caracas', 'Valencia', 'Maracay', 'Barquisimeto', 'Maracaibo'];
            $esTroncalDirecta = false;
            foreach ($ciudadesTroncales as $ct) {
                if (strcasecmp($origen, $ct) === 0) {
                    $esTroncalDirecta = true;
                    break;
                }
            }

            if ($esTroncalDirecta) {
                $pares[] = [$origen, 'San Cristóbal', 1];
            } else {
                if (strcasecmp($origen, 'San Cristóbal') !== 0) {
                    $pares[] = [$origen, 'Caracas', 5];
                    $pares[] = ['Caracas', 'San Cristóbal', 1];
                }
            }

            // Cruce binacional Atanasio Girardot vía PUENTE_FRONTERIZO

            $trPana = $this->buscarTramo('Cúcuta', $destino, 2);
            if ($trPana) {
                $pares[] = ['Cúcuta', $destino, 2];
            } else {
                $pares[] = ['Cúcuta', 'Quito', 2];
                if (strcasecmp($destino, 'Quito') !== 0) {
                    $pares[] = ['Quito', $destino, 3];
                }
            }
        } elseif ($paisOrig === 'COLOMBIA' && $paisDest === 'VENEZUELA') {
            if (strcasecmp($origen, 'Cúcuta') !== 0) {
                $pares[] = [$origen, 'Cúcuta', 4];
            }
            // Cruce binacional Atanasio Girardot vía PUENTE_FRONTERIZO
            if (strcasecmp($destino, 'San Cristóbal') !== 0) {
                $ciudadesTroncales = ['Caracas', 'Valencia', 'Maracay', 'Barquisimeto', 'Maracaibo'];
                $esTroncal = in_array(ucfirst(strtolower($destino)), $ciudadesTroncales);
                if ($esTroncal) {
                    $pares[] = ['San Cristóbal', $destino, 1];
                } else {
                    $pares[] = ['San Cristóbal', 'Caracas', 1];
                    if (strcasecmp($destino, 'Caracas') !== 0) {
                        $pares[] = ['Caracas', $destino, 5];
                    }
                }
            }
        } elseif ($paisOrig === 'VENEZUELA' && $paisDest === 'COLOMBIA') {
            $ciudadesTroncales = ['Caracas', 'Valencia', 'Maracay', 'Barquisimeto', 'Maracaibo'];
            $esTroncal = in_array(ucfirst(strtolower($origen)), $ciudadesTroncales);
            if ($esTroncal) {
                $pares[] = [$origen, 'San Cristóbal', 1];
            } else {
                if (strcasecmp($origen, 'San Cristóbal') !== 0) {
                    $pares[] = [$origen, 'Caracas', 5];
                    $pares[] = ['Caracas', 'San Cristóbal', 1];
                }
            }
            // Cruce binacional Atanasio Girardot vía PUENTE_FRONTERIZO
            if (strcasecmp($destino, 'Cúcuta') !== 0) {
                $pares[] = ['Cúcuta', $destino, 4];
            }
        } else {
            return null;
        }

        return $this->armarObjetoItinerario(
            'PANA_EXPRESOS',
            'Alianza Pana-Expresos',
            '⚡',
            'bg-info-subtle text-info-emphasis border border-info-subtle',
            'Máxima eficiencia y confort: Salto binacional Panamericana + Troncal venezolana Expresos Suramérica.',
            'Panamericana / Suramérica',
            2,
            $pares
        );
    }

    // =========================================================================
    // COMPILADOR DE ITINERARIOS Y TOTALES
    // =========================================================================

    /**
     * Resuelve los tramos contra el catálogo, calcula horas, costos y márgenes.
     */
    private function armarObjetoItinerario($modalidad, $nombreModalidad, $icono, $badgeClass, $descripcion, $opPrincipal, $idOpPrincipal, array $paresCiudades) {
        $tramosResueltos = [];
        $totalCosto = 0;
        $totalPvp = 0;
        $totalHoras = 0;
        $disponible = true;
        $motivosFaltantes = [];
        $ciudadesRuta = [];

        foreach ($paresCiudades as $idx => $par) {
            $orig = $par[0];
            $dest = $par[1];
            $prefOp = $par[2] ?? null;

            if ($idx === 0) {
                $ciudadesRuta[] = $orig;
            } else {
                $prevDest = $paresCiudades[$idx - 1][1];
                if (strcasecmp($prevDest, $orig) !== 0) {
                    $ciudadesRuta[] = $orig;
                    // Si cruza por puente fronterizo binacional, sumar tiempo de migración/cruce a las horas
                    $puente = $this->esPuenteFronterizo($prevDest, $orig);
                    if ($puente) {
                        $minCruce = intval($puente['TIEMPO_CRUCE_MIN'] ?? 120);
                        $totalHoras += ($minCruce / 60);
                    }
                }
            }
            $ciudadesRuta[] = $dest;

            $tr = $this->buscarTramo($orig, $dest, $prefOp);

            if ($tr) {
                $costo = floatval($tr['COSTO_OPERADOR']);
                $pvp = floatval($tr['PVP_SUGERIDO']);
                $horas = floatval($tr['TIEMPO_ESTIMADO_HORAS'] ?? 0);
                $idCat = intval($tr['ID_TRAMO_CATALOGO']);
                $idOp = intval($tr['ID_OPERADOR'] ?: ($prefOp ?: 1));
                $nomOp = $tr['OPERADOR_NOMBRE'] ?: ($tr['OP_NOMBRE_REAL'] ?: 'Aliado');
                $nomTramo = $tr['NOMBRE_TRAMO'];
                $admiteSp = intval($tr['ADMITE_SOBREPESO'] ?? 1);
                $limiteEq = floatval($tr['LIMITE_EQUIPAJE_KG'] ?? 35);
                $costoSp = floatval($tr['COSTO_SOBREPESO_KG'] ?? 0);
                $precioSp = floatval($tr['PRECIO_SOBREPESO_KG'] ?? 1);
            } else {
                $disponible = false;
                $motivosFaltantes[] = "Falta tramo maestro {$orig} ➔ {$dest}";
                $costo = 25.00;
                $pvp = 35.00;
                $horas = 6.0;
                $idCat = null;
                $idOp = $prefOp ?: 1;
                $nomOp = 'Operador Aliado';
                $nomTramo = "{$orig} ➔ {$dest}";
                $admiteSp = 1;
                $limiteEq = 35.0;
                $costoSp = 0.0;
                $precioSp = 1.0;
            }

            $util = max(0, $pvp - $costo);
            $desfase = ($idx === 0) ? 0 : floor($totalHoras / 18);
            $totalCosto += $costo;
            $totalPvp += $pvp;
            $totalHoras += $horas;

            $tramosResueltos[] = [
                'orden' => $idx + 1,
                'dia_desfase' => (int)$desfase,
                'id_tramo_catalogo' => $idCat,
                'origen' => $orig,
                'destino' => $dest,
                'nombre_tramo' => $nomTramo,
                'id_operador' => $idOp,
                'operador' => $nomOp,
                'tiempo_estimado_horas' => $horas,
                'costo' => $costo,
                'pvp' => $pvp,
                'utilidad' => $util,
                'admite_sobrepeso' => $admiteSp,
                'limite_equipaje' => $limiteEq,
                'costo_sobrepeso' => $costoSp,
                'precio_sobrepeso' => $precioSp
            ];
        }

        $totalUtilidad = max(0, $totalPvp - $totalCosto);
        $margenPct = ($totalPvp > 0) ? round(($totalUtilidad / $totalPvp) * 100, 1) : 0;
        $diasViables = $this->calcularDiasSalidaEfectivos($tramosResueltos);

        return [
            'modalidad' => $modalidad,
            'nombre_modalidad' => $nombreModalidad,
            'icono' => $icono,
            'badge_class' => $badgeClass,
            'descripcion' => $descripcion,
            'operador_principal' => $opPrincipal,
            'id_operador_principal' => $idOpPrincipal,
            'tramos' => $tramosResueltos,
            'total_tramos' => count($tramosResueltos),
            'costo_total_operadores' => $totalCosto,
            'pvp_sugerido' => $totalPvp,
            'utilidad_agencia' => $totalUtilidad,
            'margen_pct' => $margenPct,
            'tiempo_estimado_horas' => round($totalHoras, 1),
            'itinerario_resumen' => implode(' ➔ ', array_unique($ciudadesRuta)),
            'dias_salida_viables' => $diasViables['dias_codigos'],
            'dias_salida_texto' => $diasViables['dias_texto'],
            'dias_salida_numeros' => $diasViables['dias_numeros'],
            'disponible' => $disponible && !empty($diasViables['dias_codigos']),
            'motivos_faltantes' => $motivosFaltantes
        ];
    }

    /**
     * Itinerario de emergencia si no coincide ninguna regla predefinida.
     */
    private function generarItinerarioFallback($origen, $destino, $modalidad) {
        $tramo = $this->buscarTramo($origen, $destino);
        $costo = $tramo ? floatval($tramo['COSTO_OPERADOR']) : 25.0;
        $pvp = $tramo ? floatval($tramo['PVP_SUGERIDO']) : 35.0;
        $horas = $tramo ? floatval($tramo['TIEMPO_ESTIMADO_HORAS']) : 8.0;

        return [
            'modalidad' => $modalidad,
            'nombre_modalidad' => ModalidadHelper::formatearEtiqueta($modalidad),
            'icono' => '🔀',
            'badge_class' => 'bg-secondary text-white',
            'descripcion' => 'Ruta directa o de conexión configurada.',
            'operador_principal' => $tramo ? $tramo['OPERADOR_NOMBRE'] : 'Expresos Suramérica 2030',
            'id_operador_principal' => $tramo ? $tramo['ID_OPERADOR'] : 1,
            'tramos' => [
                [
                    'orden' => 1,
                    'id_tramo_catalogo' => $tramo ? intval($tramo['ID_TRAMO_CATALOGO']) : null,
                    'origen' => $origen,
                    'destino' => $destino,
                    'nombre_tramo' => "{$origen} ➔ {$destino}",
                    'id_operador' => $tramo ? intval($tramo['ID_OPERADOR']) : 1,
                    'operador' => $tramo ? $tramo['OPERADOR_NOMBRE'] : 'Expresos Suramérica 2030',
                    'tiempo_estimado_horas' => $horas,
                    'costo' => $costo,
                    'pvp' => $pvp,
                    'utilidad' => max(0, $pvp - $costo),
                    'admite_sobrepeso' => 1,
                    'limite_equipaje' => 35.0,
                    'costo_sobrepeso' => 0.0,
                    'precio_sobrepeso' => 1.0
                ]
            ],
            'total_tramos' => 1,
            'costo_total_operadores' => $costo,
            'pvp_sugerido' => $pvp,
            'utilidad_agencia' => max(0, $pvp - $costo),
            'margen_pct' => ($pvp > 0) ? round(((max(0, $pvp - $costo)) / $pvp) * 100, 1) : 0,
            'tiempo_estimado_horas' => $horas,
            'itinerario_resumen' => "{$origen} ➔ {$destino}",
            'disponible' => ($tramo !== null),
            'motivos_faltantes' => $tramo ? [] : ["Tramo directo no sembrado"]
        ];
    }

    // =========================================================================
    // CREACIÓN Y PERSISTENCIA MASIVA EN BASE DE DATOS
    // =========================================================================

    /**
     * Genera en lote todas las rutas seleccionadas (hasta las 4 modalidades simultáneas)
     * para un origen y destino, opcionalmente creando sus respectivos viajes de vuelta.
     */
    public function generarLoteRutas($origen, $destino, array $modalidadesDeseadas = [], array $opciones = []) {
        $sugerencias = $this->sugerirTodasLasModalidades($origen, $destino);

        if (empty($sugerencias)) {
            return [
                'status' => 'error',
                'mensaje' => "No se encontraron combinaciones operativas entre {$origen} y {$destino}."
            ];
        }

        // Si no se pasaron modalidades específicas, generar todas las disponibles
        if (empty($modalidadesDeseadas)) {
            $modalidadesDeseadas = array_keys($sugerencias);
        }

        $repoFlota = new \Modules\Flota\FlotaRepository();
        $rutasCreadas = [];
        $crearRetorno = !empty($opciones['crear_retorno']);
        $diasSalida = !empty($opciones['dias_salida']) ? $opciones['dias_salida'] : 'LUN,MIE,VIE';
        $horaSalida = !empty($opciones['hora_salida']) ? $opciones['hora_salida'] : '08:00';

        try {
            $this->db->beginTransaction();

            foreach ($modalidadesDeseadas as $modKey) {
                $modKeyNorm = strtoupper(trim($modKey));
                if (!isset($sugerencias[$modKeyNorm])) continue;

                $itinerario = $sugerencias[$modKeyNorm];

                // 1. Crear Ruta Ida
                $datosRutaIda = [
                    'origen' => $origen,
                    'destino' => $destino,
                    'tipo_viaje' => (count($itinerario['tramos']) > 1) ? 'CONEXION' : 'DIRECTO',
                    'modalidad' => $itinerario['modalidad'],
                    'id_operador' => $itinerario['id_operador_principal'],
                    'dias_salida' => $diasSalida,
                    'hora_salida' => $horaSalida,
                    'precio_pasaje' => $itinerario['pvp_sugerido'],
                    'costo_total_ruta' => $itinerario['costo_total_operadores'],
                    'utilidad_pasaje' => $itinerario['utilidad_agencia'],
                    'tramos' => $itinerario['tramos']
                ];

                $idRutaIda = $repoFlota->insertarRutaIndividual($datosRutaIda);
                $rutasCreadas[] = [
                    'id_ruta' => $idRutaIda,
                    'sentido' => 'IDA',
                    'origen' => $origen,
                    'destino' => $destino,
                    'modalidad' => $itinerario['modalidad'],
                    'tramos' => count($itinerario['tramos']),
                    'pvp' => $itinerario['pvp_sugerido']
                ];

                // 2. Crear Retorno si se solicitó
                if ($crearRetorno) {
                    $sugerenciasRetorno = $this->sugerirTodasLasModalidades($destino, $origen);
                    $itRetorno = isset($sugerenciasRetorno[$modKeyNorm]) ? $sugerenciasRetorno[$modKeyNorm] : null;

                    if ($itRetorno) {
                        $datosRutaRetorno = [
                            'origen' => $destino,
                            'destino' => $origen,
                            'tipo_viaje' => (count($itRetorno['tramos']) > 1) ? 'CONEXION' : 'DIRECTO',
                            'modalidad' => $itRetorno['modalidad'],
                            'id_operador' => $itRetorno['id_operador_principal'],
                            'dias_salida' => !empty($opciones['dias_retorno']) ? $opciones['dias_retorno'] : 'MAR,JUE,SAB',
                            'hora_salida' => !empty($opciones['hora_retorno']) ? $opciones['hora_retorno'] : '08:00',
                            'precio_pasaje' => $itRetorno['pvp_sugerido'],
                            'costo_total_ruta' => $itRetorno['costo_total_operadores'],
                            'utilidad_pasaje' => $itRetorno['utilidad_agencia'],
                            'tramos' => $itRetorno['tramos']
                        ];

                        $idRutaRetorno = $repoFlota->insertarRutaIndividual($datosRutaRetorno);
                        $rutasCreadas[] = [
                            'id_ruta' => $idRutaRetorno,
                            'sentido' => 'RETORNO',
                            'origen' => $destino,
                            'destino' => $origen,
                            'modalidad' => $itRetorno['modalidad'],
                            'tramos' => count($itRetorno['tramos']),
                            'pvp' => $itRetorno['pvp_sugerido']
                        ];
                    }
                }
            }

            $this->db->commit();

            return [
                'status' => 'success',
                'mensaje' => 'Se crearon con éxito ' . count($rutasCreadas) . ' rutas comerciales multimodales.',
                'rutas' => $rutasCreadas
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'status' => 'error',
                'mensaje' => 'Error al generar lote de rutas: ' . $e->getMessage()
            ];
        }
    }

    // =========================================================================
    // MOTOR INTELIGENTE: BFS Y CÁLCULO DINÁMICO DE DÍAS CON DESFASE
    // =========================================================================

    /**
     * Deducir qué días de la semana son viables para la ruta completa evaluando
     * las ofertas de los operadores con su respectivo desfase de días.
     * 
     * Regla matemática: Para salir un día d ∈ {1..7}, para cada tramo i con desfase Δi,
     * debe existir al menos una oferta activa en ese tramo disponible el día ((d - 1 + Δi) % 7) + 1.
     */
    public function calcularDiasSalidaEfectivos(array $tramos) {
        $diasMapa = [
            1 => 'LUN', 2 => 'MAR', 3 => 'MIE', 4 => 'JUE', 5 => 'VIE', 6 => 'SAB', 7 => 'DOM'
        ];

        $diasViablesCod = [];
        $diasViablesNum = [];
        $detalle = [];

        for ($diaSalida = 1; $diaSalida <= 7; $diaSalida++) {
            $viable = true;
            $detalleDia = [];

            foreach ($tramos as $tr) {
                $desfase = intval($tr['dia_desfase'] ?? 0);
                $diaRequeridoTramo = (($diaSalida - 1 + $desfase) % 7) + 1;
                $diaCod = $diasMapa[$diaRequeridoTramo];

                $ofertas = $tr['ofertas'] ?? [];
                if (empty($ofertas) && !empty($tr['id_tramo_catalogo'])) {
                    $ofertas = $this->obtenerOfertasTramo((int)$tr['id_tramo_catalogo']);
                }

                $hayOfertaEseDia = false;
                if (!empty($ofertas)) {
                    foreach ($ofertas as $of) {
                        $diasOf = explode(',', strtoupper(str_replace(' ', '', $of['DIAS_SALIDA'])));
                        if (in_array($diaCod, $diasOf) || in_array((string)$diaRequeridoTramo, $diasOf)) {
                            $hayOfertaEseDia = true;
                            break;
                        }
                    }
                } else {
                    // Fallback si no hay ofertas explícitas
                    $hayOfertaEseDia = true;
                }

                $detalleDia[] = [
                    'tramo' => ($tr['origen'] ?? '') . ' ➔ ' . ($tr['destino'] ?? ''),
                    'desfase' => $desfase,
                    'dia_requerido' => $diaCod,
                    'disponible' => $hayOfertaEseDia
                ];

                if (!$hayOfertaEseDia) {
                    $viable = false;
                    break;
                }
            }

            if ($viable) {
                $diasViablesCod[] = $diasMapa[$diaSalida];
                $diasViablesNum[] = $diaSalida;
            }
            $detalle[$diasMapa[$diaSalida]] = [
                'viable' => $viable,
                'tramos' => $detalleDia
            ];
        }

        return [
            'dias_codigos' => $diasViablesCod,
            'dias_texto' => implode(',', $diasViablesCod),
            'dias_numeros' => $diasViablesNum,
            'total_dias' => count($diasViablesCod),
            'detalle' => $detalle
        ];
    }

    /**
     * Autodescubrimiento Inteligente de Conexiones Multimodales (Algoritmo BFS)
     * Explora la red de tramos físicos activos y puentes fronterizos para deducir todas
     * las rutas viables entre un Origen y Destino, con cálculo de desfases y días efectivos.
     * 
     * @param string $origen
     * @param string $destino
     * @param int $maxEscalas
     * @return array Lista estructurada de rutas sugeridas con sus tramos y finanzas
     */
    public function sugerirCaminosDisponibles($origen, $destino, $maxEscalas = 4) {
        $this->cargarCatalogo();
        $origen = trim($origen);
        $destino = trim($destino);

        if (empty($origen) || empty($destino) || strcasecmp($origen, $destino) === 0) {
            return [];
        }

        // Construir Grafo en Memoria
        $grafo = [];

        foreach ($this->catalogoTramos as $t) {
            $orig = trim($t['ORIGEN_TRAMO']);
            $dest = trim($t['DESTINO_TRAMO']);
            $idCat = (int)$t['ID_TRAMO_CATALOGO'];
            $grafo[$orig][] = [
                'destino' => $dest,
                'tipo' => 'TRAMO',
                'tramo' => $t,
                'ofertas' => $this->ofertasOperadores[$idCat] ?? []
            ];
        }

        foreach ($this->puentesFronterizos as $orig => $destinos) {
            foreach ($destinos as $dest => $p) {
                $grafo[$p['CIUDAD_A']][] = [
                    'destino' => $p['CIUDAD_B'],
                    'tipo' => 'PUENTE',
                    'puente' => $p
                ];
            }
        }

        // Búsqueda en Anchura (BFS)
        $queue = [
            [
                'actual' => $origen,
                'visitados' => [mb_strtolower($origen)],
                'pasos' => []
            ]
        ];

        $caminosEncontrados = [];
        $maxSaltos = max(2, $maxEscalas + 2); // Considerar saltos de puentes fronterizos

        while (!empty($queue)) {
            $estado = array_shift($queue);
            $nodoActual = $estado['actual'];

            if (strcasecmp($nodoActual, $destino) === 0 && !empty($estado['pasos'])) {
                $caminosEncontrados[] = $estado['pasos'];
                if (count($caminosEncontrados) >= 15) break;
                continue;
            }

            if (count($estado['pasos']) >= $maxSaltos) {
                continue;
            }

            if (!isset($grafo[$nodoActual])) {
                // Probar búsqueda case-insensitive en las claves del grafo
                $nodoKey = null;
                foreach (array_keys($grafo) as $k) {
                    if (strcasecmp($k, $nodoActual) === 0) {
                        $nodoKey = $k;
                        break;
                    }
                }
                if ($nodoKey === null) continue;
                $aristas = $grafo[$nodoKey];
            } else {
                $aristas = $grafo[$nodoActual];
            }

            foreach ($aristas as $arista) {
                $sigNodo = $arista['destino'];
                $sigNorm = mb_strtolower($sigNodo);
                if (in_array($sigNorm, $estado['visitados'])) {
                    continue; // Evitar ciclos
                }

                $nuevoVisitados = $estado['visitados'];
                $nuevoVisitados[] = $sigNorm;

                $nuevosPasos = $estado['pasos'];
                $nuevosPasos[] = $arista;

                $queue[] = [
                    'actual' => $sigNodo,
                    'visitados' => $nuevoVisitados,
                    'pasos' => $nuevosPasos
                ];
            }
        }

        // Procesar y formatear cada camino encontrado
        $rutasSugeridas = [];
        $hashesVistos = [];

        foreach ($caminosEncontrados as $camino) {
            $tramosFormateados = [];
            $horasAcumuladas = 0;
            $costoTotal = 0;
            $pvpTotal = 0;
            $operadoresInvolucrados = [];
            $ciudadesRuta = [$origen];
            $ordenTramo = 1;

            foreach ($camino as $paso) {
                if ($paso['tipo'] === 'TRAMO') {
                    $t = $paso['tramo'];
                    $horas = floatval($t['TIEMPO_ESTIMADO_HORAS'] ?? 8);
                    $desfase = ($ordenTramo === 1) ? 0 : floor($horasAcumuladas / 18);
                    $costo = floatval($t['COSTO_OPERADOR'] ?? 0);
                    $pvp = floatval($t['PVP_SUGERIDO'] ?? 0);
                    $idOp = intval($t['ID_OPERADOR'] ?? 1);
                    $nomOp = $t['OPERADOR_NOMBRE'] ?: ($t['OP_NOMBRE_REAL'] ?: 'Aliado');

                    $ofertas = $paso['ofertas'] ?? [];
                    if (!empty($ofertas)) {
                        $primeraOf = $ofertas[0];
                        if (floatval($primeraOf['COSTO_NETO']) > 0) {
                            $costo = floatval($primeraOf['COSTO_NETO']);
                        }
                    }

                    $tramosFormateados[] = [
                        'orden' => $ordenTramo,
                        'dia_desfase' => (int)$desfase,
                        'id_tramo_catalogo' => (int)$t['ID_TRAMO_CATALOGO'],
                        'id_oferta' => !empty($ofertas[0]['ID_OFERTA']) ? (int)$ofertas[0]['ID_OFERTA'] : null,
                        'origen' => $t['ORIGEN_TRAMO'],
                        'destino' => $t['DESTINO_TRAMO'],
                        'nombre_tramo' => "{$t['ORIGEN_TRAMO']} ➔ {$t['DESTINO_TRAMO']}",
                        'id_operador' => $idOp,
                        'operador' => $nomOp,
                        'tiempo_estimado_horas' => $horas,
                        'costo' => $costo,
                        'pvp' => $pvp,
                        'utilidad' => max(0, $pvp - $costo),
                        'admite_sobrepeso' => intval($t['ADMITE_SOBREPESO'] ?? 1),
                        'limite_equipaje' => floatval($t['LIMITE_EQUIPAJE_KG'] ?? 35),
                        'costo_sobrepeso' => floatval($t['COSTO_SOBREPESO_KG'] ?? 0),
                        'precio_sobrepeso' => floatval($t['PRECIO_SOBREPESO_KG'] ?? 1),
                        'ofertas' => $ofertas
                    ];

                    $horasAcumuladas += $horas;
                    $costoTotal += $costo;
                    $pvpTotal += $pvp;
                    if (!in_array($nomOp, $operadoresInvolucrados)) {
                        $operadoresInvolucrados[] = $nomOp;
                    }
                    $ciudadesRuta[] = $t['DESTINO_TRAMO'];
                    $ordenTramo++;

                } elseif ($paso['tipo'] === 'PUENTE') {
                    $p = $paso['puente'];
                    $minCruce = intval($p['TIEMPO_CRUCE_MIN'] ?? 120);
                    $horasAcumuladas += ($minCruce / 60);
                    $ciudadesRuta[] = $p['CIUDAD_B'];
                }
            }

            if (empty($tramosFormateados)) continue;

            // Evitar secuencias idénticas duplicadas
            $hashSecuencia = implode('|', array_map(function($tr) {
                return $tr['origen'] . '->' . $tr['destino'];
            }, $tramosFormateados));

            if (isset($hashesVistos[$hashSecuencia])) continue;
            $hashesVistos[$hashSecuencia] = true;

            // Calcular días viables
            $diasViables = $this->calcularDiasSalidaEfectivos($tramosFormateados);

            // Inferir modalidad adecuada
            $modalidad = $this->inferirModalidadPorTramos($tramosFormateados, $operadoresInvolucrados);
            $catalogoMods = \Platform\Helpers\ModalidadHelper::catalogoModalidades();
            $infoMod = $catalogoMods[$modalidad] ?? [
                'nombre' => 'Ruta Sugerida',
                'icono' => '🛣️',
                'badge_class' => 'bg-secondary text-white',
                'descripcion' => 'Ruta compuesta por el motor de itinerarios.'
            ];

            $utilidad = max(0, $pvpTotal - $costoTotal);
            $margenPct = ($pvpTotal > 0) ? round(($utilidad / $pvpTotal) * 100, 1) : 0;

            $rutasSugeridas[] = [
                'modalidad' => $modalidad,
                'nombre_modalidad' => $infoMod['nombre'],
                'icono' => $infoMod['icono'],
                'badge_class' => $infoMod['badge_class'],
                'descripcion' => $infoMod['descripcion'],
                'operador_principal' => $tramosFormateados[0]['operador'] ?? 'Expresos Suramérica 2030',
                'id_operador_principal' => $tramosFormateados[0]['id_operador'] ?? 1,
                'tramos' => $tramosFormateados,
                'total_tramos' => count($tramosFormateados),
                'costo_total_operadores' => $costoTotal,
                'pvp_sugerido' => $pvpTotal,
                'utilidad_agencia' => $utilidad,
                'margen_pct' => $margenPct,
                'tiempo_estimado_horas' => round($horasAcumuladas, 1),
                'itinerario_resumen' => implode(' ➔ ', array_unique($ciudadesRuta)),
                'dias_salida_viables' => $diasViables['dias_codigos'],
                'dias_salida_texto' => $diasViables['dias_texto'],
                'dias_salida_numeros' => $diasViables['dias_numeros'],
                'disponible' => !empty($diasViables['dias_codigos']),
                'es_directo' => (count($tramosFormateados) === 1)
            ];
        }

        // Ordenar opciones: Primero con días viables, luego menos tramos, luego menor costo
        usort($rutasSugeridas, function($a, $b) {
            $aViable = count($a['dias_salida_viables']) > 0 ? 1 : 0;
            $bViable = count($b['dias_salida_viables']) > 0 ? 1 : 0;
            if ($aViable !== $bViable) return $bViable <=> $aViable;

            if ($a['total_tramos'] !== $b['total_tramos']) {
                return $a['total_tramos'] <=> $b['total_tramos'];
            }
            return $a['costo_total_operadores'] <=> $b['costo_total_operadores'];
        });

        return $rutasSugeridas;
    }

    /**
     * Infiere automáticamente la modalidad comercial correspondiente según los operadores involucrados.
     */
    private function inferirModalidadPorTramos(array $tramos, array $operadores) {
        $opsUpper = array_map(function($op) { return strtoupper(trim($op)); }, $operadores);
        $tieneSuramerica = false;
        $tienePanamericana = false;
        $tieneLocales = false;

        foreach ($opsUpper as $op) {
            if (strpos($op, 'SURAMERICA') !== false || strpos($op, 'SURAMÉRICA') !== false) {
                $tieneSuramerica = true;
            } elseif (strpos($op, 'PANAMERICANA') !== false) {
                $tienePanamericana = true;
            } else {
                $tieneLocales = true;
            }
        }

        if ($tienePanamericana && $tieneSuramerica) {
            return 'PANA_EXPRESOS';
        }
        if ($tieneSuramerica && !$tienePanamericana) {
            return 'EXPRESOS_SURAMERICA';
        }
        if ($tienePanamericana && !$tieneSuramerica) {
            return 'PANAMERICANA';
        }
        return 'CONEXIONES';
    }
}
