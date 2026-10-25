<?php
namespace Modules\Flota;

use Platform\Storage\Database;
use PDO;
use Exception;

class FlotaRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // ==========================================
    // SECCIÓN CIUDADES Y CATÁLOGO DE DOMINIO
    // ==========================================

    public function obtenerCiudadesCatalogo() {
        return $this->db->query("SELECT * FROM CIUDAD WHERE ESTADO = 1 ORDER BY PAIS ASC, NOMBRE ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodasLasCiudadesConEstadisticas() {
        $sql = "
            SELECT c.*,
                   COUNT(DISTINCT CASE WHEN r.RUT_ORIGEN = c.NOMBRE OR r.RUT_DESTINO = c.NOMBRE THEN r.ID_RUTA END) as TOTAL_RUTAS
            FROM CIUDAD c
            LEFT JOIN RUTA r ON (r.RUT_ORIGEN = c.NOMBRE OR r.RUT_DESTINO = c.NOMBRE)
            GROUP BY c.ID_CIUDAD
            ORDER BY c.PAIS ASC, c.NOMBRE ASC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asegurarCiudadExiste($nombre, $pais = null) {
        if (empty($nombre)) return null;
        $nombre = trim($nombre);
        $stmt = $this->db->prepare("SELECT ID_CIUDAD FROM CIUDAD WHERE LOWER(NOMBRE) = LOWER(?) LIMIT 1");
        $stmt->execute([$nombre]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int)$row['ID_CIUDAD'];
        }

        if (empty($pais)) {
            $pais = 'VENEZUELA';
        }
        $ins = $this->db->prepare("INSERT INTO CIUDAD (NOMBRE, PAIS, ESTADO) VALUES (?, ?, 1)");
        $ins->execute([$nombre, strtoupper(trim($pais))]);
        return (int)$this->db->lastInsertId();
    }

    public function guardarCiudadBD($datos) {
        try {
            $id = !empty($datos['id_ciudad']) ? intval($datos['id_ciudad']) : 0;
            $nombre = trim($datos['nombre'] ?? '');
            $pais = strtoupper(trim($datos['pais'] ?? 'VENEZUELA'));
            $estado = isset($datos['estado']) ? intval($datos['estado']) : 1;

            if (empty($nombre)) {
                return "Debe ingresar el nombre de la ciudad.";
            }

            if ($id > 0) {
                $stmt = $this->db->prepare("UPDATE CIUDAD SET NOMBRE = ?, PAIS = ?, ESTADO = ? WHERE ID_CIUDAD = ?");
                $stmt->execute([$nombre, $pais, $estado, $id]);
            } else {
                $stmtCheck = $this->db->prepare("SELECT ID_CIUDAD FROM CIUDAD WHERE LOWER(NOMBRE) = LOWER(?)");
                $stmtCheck->execute([$nombre]);
                if ($stmtCheck->fetch()) {
                    return "Ya existe una ciudad registrada con el nombre '{$nombre}'.";
                }
                $stmt = $this->db->prepare("INSERT INTO CIUDAD (NOMBRE, PAIS, ESTADO) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $pais, $estado]);
            }
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function cambiarEstadoCiudadBD($idCiudad, $estado) {
        try {
            $stmt = $this->db->prepare("UPDATE CIUDAD SET ESTADO = ? WHERE ID_CIUDAD = ?");
            $stmt->execute([intval($estado), intval($idCiudad)]);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function eliminarCiudadBD($idCiudad) {
        try {
            $idCiudad = intval($idCiudad);
            $stmt = $this->db->prepare("SELECT NOMBRE FROM CIUDAD WHERE ID_CIUDAD = ?");
            $stmt->execute([$idCiudad]);
            $ciu = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ciu) return "Ciudad no encontrada.";

            $stmtUso = $this->db->prepare("SELECT COUNT(*) FROM RUTA WHERE RUT_ORIGEN = ? OR RUT_DESTINO = ?");
            $stmtUso->execute([$ciu['NOMBRE'], $ciu['NOMBRE']]);
            $rutasUso = (int)$stmtUso->fetchColumn();

            $stmtTramo = $this->db->prepare("SELECT COUNT(*) FROM RUTA_TRAMO WHERE ORIGEN_TRAMO = ? OR DESTINO_TRAMO = ?");
            $stmtTramo->execute([$ciu['NOMBRE'], $ciu['NOMBRE']]);
            $tramosUso = (int)$stmtTramo->fetchColumn();

            if ($rutasUso > 0 || $tramosUso > 0) {
                $this->db->prepare("UPDATE CIUDAD SET ESTADO = 0 WHERE ID_CIUDAD = ?")->execute([$idCiudad]);
                return "La ciudad tiene {$rutasUso} rutas y {$tramosUso} tramos asociados; ha sido marcada como INACTIVA para proteger el historial.";
            }

            $stmtDel = $this->db->prepare("DELETE FROM CIUDAD WHERE ID_CIUDAD = ?");
            $stmtDel->execute([$idCiudad]);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function precargarCatalogoCompletoCiudades() {
        try {
            $ciudadesIniciales = [
                ['Maturín', 'VENEZUELA'], ['Puerto Ordaz', 'VENEZUELA'], ['San Félix', 'VENEZUELA'],
                ['Ciudad Bolívar', 'VENEZUELA'], ['El Tigre', 'VENEZUELA'], ['Anaco', 'VENEZUELA'],
                ['Cumaná', 'VENEZUELA'], ['Carúpano', 'VENEZUELA'], ['Barquisimeto', 'VENEZUELA'],
                ['Maracaibo', 'VENEZUELA'], ['Cabimas', 'VENEZUELA'], ['Ciudad Ojeda', 'VENEZUELA'],
                ['Valera', 'VENEZUELA'], ['Mérida', 'VENEZUELA'], ['El Vigía', 'VENEZUELA'],
                ['Guanare', 'VENEZUELA'], ['Acarigua', 'VENEZUELA'], ['San Antonio del Táchira', 'VENEZUELA'],
                ['La Fría', 'VENEZUELA'], ['Valle de la Pascua', 'VENEZUELA'], ['San Juan de los Morros', 'VENEZUELA'],
                ['Calabozo', 'VENEZUELA'], ['Coro', 'VENEZUELA'], ['Punto Fijo', 'VENEZUELA'],
                ['San Felipe', 'VENEZUELA'], ['La Guaira', 'VENEZUELA'], ['Guatire', 'VENEZUELA'],
                ['Charallave', 'VENEZUELA'], ['Los Teques', 'VENEZUELA'], ['Puerto Cabello', 'VENEZUELA'],
                ['Santa Elena de Uairén', 'VENEZUELA'], ['Caracas', 'VENEZUELA'], ['Valencia', 'VENEZUELA'],
                ['Maracay', 'VENEZUELA'], ['San Cristóbal', 'VENEZUELA'], ['Barinas', 'VENEZUELA'],
                ['Barcelona', 'VENEZUELA'], ['Puerto La Cruz', 'VENEZUELA'],
                ['Barrancabermeja', 'COLOMBIA'], ['Honda', 'COLOMBIA'], ['Ibagué', 'COLOMBIA'],
                ['Palmira', 'COLOMBIA'], ['Cúcuta', 'COLOMBIA'], ['Bucaramanga', 'COLOMBIA'],
                ['Bogotá', 'COLOMBIA'], ['Cali', 'COLOMBIA'], ['Medellín', 'COLOMBIA'],
                ['Pasto', 'COLOMBIA'], ['Ipiales', 'COLOMBIA'], ['Popayán', 'COLOMBIA'],
                ['Armenia', 'COLOMBIA'], ['Pereira', 'COLOMBIA'], ['Manizales', 'COLOMBIA'],
                ['Pamplona', 'COLOMBIA'], ['Santa Marta', 'COLOMBIA'], ['Barranquilla', 'COLOMBIA'],
                ['Cartagena', 'COLOMBIA'],
                ['Manta', 'ECUADOR'], ['Portoviejo', 'ECUADOR'], ['Guayaquil', 'ECUADOR'],
                ['Quito', 'ECUADOR'], ['Tulcán', 'ECUADOR'], ['Santo Domingo', 'ECUADOR'],
                ['Quevedo', 'ECUADOR'], ['Babahoyo', 'ECUADOR'], ['Ambato', 'ECUADOR'],
                ['Riobamba', 'ECUADOR'], ['Cuenca', 'ECUADOR'], ['Loja', 'ECUADOR'],
                ['Machala', 'ECUADOR'], ['Huaquillas', 'ECUADOR'], ['Ibarra', 'ECUADOR'],
                ['Otavalo', 'ECUADOR'], ['Chone', 'ECUADOR'], ['Jipijapa', 'ECUADOR'],
                ['Esmeraldas', 'ECUADOR']
            ];

            $stmt = $this->db->prepare("INSERT INTO CIUDAD (NOMBRE, PAIS, ESTADO) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE PAIS = VALUES(PAIS)");
            foreach ($ciudadesIniciales as $c) {
                $stmt->execute([$c[0], $c[1]]);
            }
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

// ==========================================
    public function obtenerRutas($sentido = null) {
        $where = !empty($sentido) ? "WHERE r.SENTIDO = " . $this->db->quote($sentido) : "";
        $query = "SELECT r.*,
                         op.NOMBRE_COMERCIAL as OPERADOR_NOMBRE, op.TIPO_OPERADOR as OPERADOR_TIPO,
                         op.BANCO as OPERADOR_BANCO, op.NUMERO_CUENTA as OPERADOR_CUENTA,
                         op.IDENTIFICACION as OPERADOR_DOC
                  FROM RUTA r 
                  LEFT JOIN OPERADOR op ON r.ID_OPERADOR = op.ID_OPERADOR
                  $where 
                  ORDER BY CASE WHEN r.ESTADO = 'ACTIVO' THEN 1 ELSE 2 END, r.RUT_ORIGEN, r.RUT_DESTINO";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rutas)) {
            $ids = implode(',', array_map(function($item) { return intval($item['ID_RUTA']); }, $rutas));
            $tramosStmt = $this->db->query("SELECT rt.*, op.NOMBRE_COMERCIAL as OPERADOR_TRAMO_NOMBRE, op.TIPO_OPERADOR as OPERADOR_TRAMO_TIPO, op.BANCO as OPERADOR_TRAMO_BANCO, op.NUMERO_CUENTA as OPERADOR_TRAMO_CUENTA FROM RUTA_TRAMO rt LEFT JOIN OPERADOR op ON rt.ID_OPERADOR = op.ID_OPERADOR WHERE rt.ID_RUTA IN ($ids) ORDER BY rt.ID_RUTA, rt.ORDEN ASC");
            $tramosRows = $tramosStmt ? $tramosStmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $tramosPorRuta = [];
            foreach ($tramosRows as $tr) {
                // Mantener compatibilidad con llaves minúsculas y mayúsculas
                $tr['origen'] = $tr['ORIGEN_TRAMO'];
                $tr['destino'] = $tr['DESTINO_TRAMO'];
                $tr['id_operador'] = intval($tr['ID_OPERADOR'] ?? 1);
                $tr['id_tramo_catalogo'] = !empty($tr['ID_TRAMO_CATALOGO']) ? intval($tr['ID_TRAMO_CATALOGO']) : null;
                $tr['operador'] = !empty($tr['OPERADOR_TRAMO_NOMBRE']) ? $tr['OPERADOR_TRAMO_NOMBRE'] : ($tr['OPERADOR'] ?? 'Propio');
                $tr['pvp'] = floatval($tr['PVP_TRAMO'] ?? 0);
                $tr['costo'] = floatval($tr['COSTO_OPERADOR'] ?? 0);
                $tr['utilidad'] = floatval($tr['UTILIDAD_AGENCIA'] ?? 0);
                $tr['admite_sobrepeso'] = intval($tr['ADMITE_SOBREPESO'] ?? 0);
                $tr['limite_equipaje'] = floatval($tr['LIMITE_EQUIPAJE_KG'] ?? 35);
                $tr['precio_sobrepeso'] = floatval($tr['PRECIO_SOBREPESO_KG'] ?? 1);
                $tr['costo_sobrepeso'] = floatval($tr['COSTO_SOBREPESO_KG'] ?? 0);
                $tr['utilidad_sobrepeso'] = floatval($tr['UTILIDAD_SOBREPESO_KG'] ?? 0);
                $tr['dia_desfase'] = intval($tr['DIA_DESFASE'] ?? 0);
                $tr['id_oferta'] = !empty($tr['ID_OFERTA']) ? intval($tr['ID_OFERTA']) : null;

                $tramosPorRuta[$tr['ID_RUTA']][] = $tr;
            }
            foreach ($rutas as &$r) {
                $tramos = $tramosPorRuta[$r['ID_RUTA']] ?? [];
                $r['TRAMOS_DETALLE'] = $tramos;
                $r['TRAMOS_JSON'] = json_encode($tramos);

                // Sentido y orientación comercial
                $sentidoVal = !empty($r['SENTIDO']) ? $r['SENTIDO'] : \Platform\Helpers\SentidoHelper::resolverSentido($r['RUT_ORIGEN'], $r['RUT_DESTINO']);
                $r['SENTIDO'] = $sentidoVal;
                $infoSentido = \Platform\Helpers\SentidoHelper::getInfoSentido($sentidoVal);
                $r['NOMBRE_CORREDOR'] = $infoSentido['nombre']; // Compatibilidad con vistas
                $r['NOMBRE_SENTIDO'] = $infoSentido['nombre'];
                $r['ICONO_SENTIDO'] = $infoSentido['icono'];
                $r['BADGE_SENTIDO'] = $infoSentido['badge_class'];
                $r['TAG_SENTIDO'] = $infoSentido['tag'];

                // Cálculo financiero de liquidación y margen comercial
                $costoTotal = floatval($r['COSTO_TOTAL_RUTA'] ?? 0);
                if ($costoTotal <= 0 && !empty($tramos)) {
                    $costoTotal = array_sum(array_column($tramos, 'costo'));
                }
                $pvp = floatval($r['PRECIO_PASAJE'] ?? 0);
                $utilidad = max(0, $pvp - $costoTotal);
                $margenPct = ($pvp > 0) ? round(($utilidad / $pvp) * 100, 1) : 0;

                $r['COSTO_CALCULADO'] = $costoTotal;
                $r['UTILIDAD_CALCULADA'] = $utilidad;
                $r['MARGEN_PCT'] = $margenPct;

                // Extraer operadores únicos involucrados en el servicio
                $opsInvolucrados = [];
                if (!empty($r['OPERADOR_NOMBRE'])) {
                    $opsInvolucrados[] = $r['OPERADOR_NOMBRE'];
                }
                foreach ($tramos as $tr) {
                    $opTr = trim($tr['operador'] ?? '');
                    if (!empty($opTr) && !in_array($opTr, $opsInvolucrados)) {
                        $opsInvolucrados[] = $opTr;
                    }
                }
                $r['OPERADORES_INVOLUCRADOS'] = $opsInvolucrados;
            }
            unset($r);
        }

        return $rutas;
    }

    public function agregarRutaBD($datos) {
        try {
            $this->db->beginTransaction();

            $colsRuta = array_column($this->db->query("DESCRIBE RUTA")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            $hasAdmiteSp = in_array('ADMITE_SOBREPESO', $colsRuta);
            $hasIdOperador = in_array('ID_OPERADOR', $colsRuta);
            $hasCostoTotal = in_array('COSTO_TOTAL_RUTA', $colsRuta);

            $idOp = !empty($datos['id_operador']) ? intval($datos['id_operador']) : 1;
            $modalidad = !empty($datos['modalidad']) ? $datos['modalidad'] : (!empty($datos['operador']) ? $datos['operador'] : 'Propio Suramérica');
            $sentido = !empty($datos['sentido']) ? $datos['sentido'] : \Platform\Helpers\SentidoHelper::resolverSentido($datos['origen'], $datos['destino']);

            $camposInsert = "SENTIDO, RUT_ORIGEN, RUT_DESTINO, TIPO_VIAJE, MODALIDAD" 
                . ($hasIdOperador ? ", ID_OPERADOR" : "") 
                . ($hasAdmiteSp ? ", ADMITE_SOBREPESO" : "") 
                . ($hasCostoTotal ? ", COSTO_TOTAL_RUTA" : "") 
                . ", DIAS_SALIDA, RUT_SAHORA, PRECIO_PASAJE, PRECIO_ENCOMIENDA_KG, PRECIO_SOBRE, PRECIO_VOLUMEN, LIMITE_EQUIPAJE_KG, PRECIO_SOBREPESO_KG, COMISION_VALOR, TIPO_UTILIDAD, UTILIDAD_PASAJE, UTILIDAD_ENCOMIENDA, UTILIDAD_VALORES, ESTADO";
            $valoresInsert = ":sentido, :origen, :destino, :tipo, :modalidad" 
                . ($hasIdOperador ? ", :id_operador" : "") 
                . ($hasAdmiteSp ? ", :admite_sp" : "") 
                . ($hasCostoTotal ? ", :costo_total" : "") 
                . ", :dias, :hora, :precio_p, :precio_e, :precio_sobre, :precio_vol, :limite_eq, :precio_s, :comision_v, :t_util, :u_pasaje, :u_enc, :u_val, 'ACTIVO'";

            $query = "INSERT INTO RUTA ($camposInsert) VALUES ($valoresInsert)";
            $stmt = $this->db->prepare($query);
            $params = [
                ':sentido' => $sentido,
                ':origen' => $datos['origen'], ':destino' => $datos['destino'], ':tipo' => $datos['tipo_viaje'], 
                ':modalidad' => $modalidad,
                ':dias' => $datos['dias_salida'], ':hora' => $datos['hora_salida'], 
                ':precio_p' => $datos['precio_pasaje'], ':precio_e' => $datos['precio_encomienda'], 
                ':precio_sobre' => $datos['precio_sobre'], ':precio_vol' => $datos['precio_volumen'], 
                ':limite_eq' => $datos['limite_equipaje'], ':precio_s' => $datos['precio_sobrepeso'], 
                ':comision_v' => $datos['comision_valor'], ':t_util' => $datos['tipo_utilidad'], 
                ':u_pasaje' => $datos['utilidad_pasaje'], ':u_enc' => $datos['utilidad_encomienda'], 
                ':u_val' => $datos['utilidad_valores']
            ];
            if ($hasIdOperador) {
                $params[':id_operador'] = $idOp;
            }
            if ($hasAdmiteSp) {
                $params[':admite_sp'] = $datos['admite_sobrepeso'] ?? 1;
            }
            if ($hasCostoTotal) {
                $params[':costo_total'] = floatval($datos['costo_total_ruta'] ?? 0);
            }
            $stmt->execute($params);

            $idRuta = $this->db->lastInsertId();

            // Auto-registrar origen y destino en catálogo del dominio
            $this->asegurarCiudadExiste($datos['origen']);
            $this->asegurarCiudadExiste($datos['destino']);

            // Insertar Tramos (tanto si es viaje directo de 1 tramo como conexión de varios tramos)
            if (!empty($datos['tramos'])) {
                $colsTramo = array_column($this->db->query("DESCRIBE RUTA_TRAMO")->fetchAll(PDO::FETCH_ASSOC), 'Field');
                $tramoHasIdOp = in_array('ID_OPERADOR', $colsTramo);
                $tramoHasIdCat = in_array('ID_TRAMO_CATALOGO', $colsTramo);
                $tramoHasDiaDesfase = in_array('DIA_DESFASE', $colsTramo);
                $tramoHasIdOferta = in_array('ID_OFERTA', $colsTramo);

                $sqlTramo = "INSERT INTO RUTA_TRAMO 
                    (ID_RUTA, ORDEN" 
                    . ($tramoHasDiaDesfase ? ", DIA_DESFASE" : "") 
                    . ", ORIGEN_TRAMO, DESTINO_TRAMO, OPERADOR" 
                    . ($tramoHasIdOp ? ", ID_OPERADOR" : "") 
                    . ($tramoHasIdCat ? ", ID_TRAMO_CATALOGO" : "") 
                    . ($tramoHasIdOferta ? ", ID_OFERTA" : "") 
                    . ", PVP_TRAMO, COSTO_OPERADOR, UTILIDAD_AGENCIA, ADMITE_SOBREPESO, LIMITE_EQUIPAJE_KG, PRECIO_SOBREPESO_KG, COSTO_SOBREPESO_KG, UTILIDAD_SOBREPESO_KG) 
                    VALUES (?, ?" 
                    . ($tramoHasDiaDesfase ? ", ?" : "") 
                    . ", ?, ?, ?" 
                    . ($tramoHasIdOp ? ", ?" : "") 
                    . ($tramoHasIdCat ? ", ?" : "") 
                    . ($tramoHasIdOferta ? ", ?" : "") 
                    . ", ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtTramo = $this->db->prepare($sqlTramo);
                foreach ($datos['tramos'] as $i => $tramo) {
                    $this->asegurarCiudadExiste($tramo['origen']);
                    $this->asegurarCiudadExiste($tramo['destino']);
                    $desfase = isset($tramo['dia_desfase']) ? intval($tramo['dia_desfase']) : max(0, $i);
                    $vals = [
                        $idRuta, 
                        $i + 1
                    ];
                    if ($tramoHasDiaDesfase) {
                        $vals[] = $desfase;
                    }
                    $vals[] = $tramo['origen'];
                    $vals[] = $tramo['destino'];
                    $vals[] = $tramo['operador'] ?? 'Propio';
                    if ($tramoHasIdOp) {
                        $vals[] = !empty($tramo['id_operador']) ? intval($tramo['id_operador']) : 1;
                    }
                    if ($tramoHasIdCat) {
                        $vals[] = !empty($tramo['id_tramo_catalogo']) ? intval($tramo['id_tramo_catalogo']) : null;
                    }
                    if ($tramoHasIdOferta) {
                        $vals[] = !empty($tramo['id_oferta']) ? intval($tramo['id_oferta']) : null;
                    }
                    $vals = array_merge($vals, [
                        $tramo['pvp'] ?? 0.00,
                        $tramo['costo'] ?? 0.00,
                        $tramo['utilidad'] ?? 0.00,
                        $tramo['admite_sobrepeso'] ?? 0,
                        $tramo['limite_equipaje'] ?? 35.00,
                        $tramo['precio_sobrepeso'] ?? 1.00,
                        $tramo['costo_sobrepeso'] ?? 0.00,
                        $tramo['utilidad_sobrepeso'] ?? 0.00
                    ]);
                    $stmtTramo->execute($vals);
                }
            }

            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function actualizarRutaBD($datos) {
        try {
            $this->db->beginTransaction();

            $modalidad = !empty($datos['modalidad']) ? $datos['modalidad'] : (!empty($datos['operador']) ? $datos['operador'] : 'Propio Suramérica');
            $sentido = !empty($datos['sentido']) ? $datos['sentido'] : \Platform\Helpers\SentidoHelper::resolverSentido($datos['origen'], $datos['destino']);
            $colsRuta = array_column($this->db->query("DESCRIBE RUTA")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            $hasAdmiteSp = in_array('ADMITE_SOBREPESO', $colsRuta);
            $hasIdOperador = in_array('ID_OPERADOR', $colsRuta);
            $hasCostoTotal = in_array('COSTO_TOTAL_RUTA', $colsRuta);
            $setAdmiteSp = $hasAdmiteSp ? ", ADMITE_SOBREPESO = :admite_sp" : "";
            $setIdOperador = $hasIdOperador ? ", ID_OPERADOR = :id_operador" : "";
            $setCostoTotal = $hasCostoTotal ? ", COSTO_TOTAL_RUTA = :costo_total" : "";

            $query = "UPDATE RUTA SET SENTIDO = :sentido, RUT_ORIGEN = :origen, RUT_DESTINO = :destino, TIPO_VIAJE = :tipo, MODALIDAD = :modalidad $setIdOperador $setAdmiteSp $setCostoTotal, DIAS_SALIDA = :dias, RUT_SAHORA = :hora, PRECIO_PASAJE = :precio_p, PRECIO_ENCOMIENDA_KG = :precio_e, PRECIO_SOBRE = :precio_sobre, PRECIO_VOLUMEN = :precio_vol, LIMITE_EQUIPAJE_KG = :limite_eq, PRECIO_SOBREPESO_KG = :precio_s, COMISION_VALOR = :comision_v, TIPO_UTILIDAD = :t_util, UTILIDAD_PASAJE = :u_pasaje, UTILIDAD_ENCOMIENDA = :u_enc, UTILIDAD_VALORES = :u_val, ESTADO = :estado WHERE ID_RUTA = :id";
            $stmt = $this->db->prepare($query);
            $params = [
                ':sentido' => $sentido,
                ':origen' => $datos['origen'], ':destino' => $datos['destino'], ':tipo' => $datos['tipo_viaje'], 
                ':modalidad' => $modalidad,
                ':dias' => $datos['dias_salida'], ':hora' => $datos['hora_salida'], 
                ':precio_p' => $datos['precio_pasaje'], ':precio_e' => $datos['precio_encomienda'], 
                ':precio_sobre' => $datos['precio_sobre'], ':precio_vol' => $datos['precio_volumen'], 
                ':limite_eq' => $datos['limite_equipaje'], ':precio_s' => $datos['precio_sobrepeso'], 
                ':comision_v' => $datos['comision_valor'], ':t_util' => $datos['tipo_utilidad'], 
                ':u_pasaje' => $datos['utilidad_pasaje'], ':u_enc' => $datos['utilidad_encomienda'], 
                ':u_val' => $datos['utilidad_valores'], ':estado' => $datos['estado'] ?? 'ACTIVO',
                ':id' => $datos['id_ruta']
            ];
            if ($hasIdOperador) {
                $params[':id_operador'] = !empty($datos['id_operador']) ? intval($datos['id_operador']) : 1;
            }
            if ($hasAdmiteSp) {
                $params[':admite_sp'] = $datos['admite_sobrepeso'] ?? 1;
            }
            if ($hasCostoTotal) {
                $params[':costo_total'] = floatval($datos['costo_total_ruta'] ?? 0);
            }
            $stmt->execute($params);

            // Renovar Tramos (se guardan siempre, tanto si es directo de 1 tramo como conexión de varios tramos)
            $this->db->prepare("DELETE FROM RUTA_TRAMO WHERE ID_RUTA = ?")->execute([$datos['id_ruta']]);
            if (!empty($datos['tramos'])) {
                $colsTramo = array_column($this->db->query("DESCRIBE RUTA_TRAMO")->fetchAll(PDO::FETCH_ASSOC), 'Field');
                $tramoHasIdOp = in_array('ID_OPERADOR', $colsTramo);
                $tramoHasIdCat = in_array('ID_TRAMO_CATALOGO', $colsTramo);
                $tramoHasDiaDesfase = in_array('DIA_DESFASE', $colsTramo);
                $tramoHasIdOferta = in_array('ID_OFERTA', $colsTramo);

                $sqlTramo = "INSERT INTO RUTA_TRAMO 
                    (ID_RUTA, ORDEN" 
                    . ($tramoHasDiaDesfase ? ", DIA_DESFASE" : "") 
                    . ", ORIGEN_TRAMO, DESTINO_TRAMO, OPERADOR" 
                    . ($tramoHasIdOp ? ", ID_OPERADOR" : "") 
                    . ($tramoHasIdCat ? ", ID_TRAMO_CATALOGO" : "") 
                    . ($tramoHasIdOferta ? ", ID_OFERTA" : "") 
                    . ", PVP_TRAMO, COSTO_OPERADOR, UTILIDAD_AGENCIA, ADMITE_SOBREPESO, LIMITE_EQUIPAJE_KG, PRECIO_SOBREPESO_KG, COSTO_SOBREPESO_KG, UTILIDAD_SOBREPESO_KG) 
                    VALUES (?, ?" 
                    . ($tramoHasDiaDesfase ? ", ?" : "") 
                    . ", ?, ?, ?" 
                    . ($tramoHasIdOp ? ", ?" : "") 
                    . ($tramoHasIdCat ? ", ?" : "") 
                    . ($tramoHasIdOferta ? ", ?" : "") 
                    . ", ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtTramo = $this->db->prepare($sqlTramo);
                foreach ($datos['tramos'] as $i => $tramo) {
                    $desfase = isset($tramo['dia_desfase']) ? intval($tramo['dia_desfase']) : max(0, $i);
                    $vals = [
                        $datos['id_ruta'], 
                        $i + 1
                    ];
                    if ($tramoHasDiaDesfase) {
                        $vals[] = $desfase;
                    }
                    $vals[] = $tramo['origen'];
                    $vals[] = $tramo['destino'];
                    $vals[] = $tramo['operador'] ?? 'Propio';
                    if ($tramoHasIdOp) {
                        $vals[] = !empty($tramo['id_operador']) ? intval($tramo['id_operador']) : 1;
                    }
                    if ($tramoHasIdCat) {
                        $vals[] = !empty($tramo['id_tramo_catalogo']) ? intval($tramo['id_tramo_catalogo']) : null;
                    }
                    if ($tramoHasIdOferta) {
                        $vals[] = !empty($tramo['id_oferta']) ? intval($tramo['id_oferta']) : null;
                    }
                    $vals = array_merge($vals, [
                        $tramo['pvp'] ?? 0.00,
                        $tramo['costo'] ?? 0.00,
                        $tramo['utilidad'] ?? 0.00,
                        $tramo['admite_sobrepeso'] ?? 0,
                        $tramo['limite_equipaje'] ?? 35.00,
                        $tramo['precio_sobrepeso'] ?? 1.00,
                        $tramo['costo_sobrepeso'] ?? 0.00,
                        $tramo['utilidad_sobrepeso'] ?? 0.00
                    ]);
                    $stmtTramo->execute($vals);
                }
            }

            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function eliminarRutaBD($idRuta) {
        try {
            // 1. Verificar si existen facturas / pasajes asociados a esta ruta
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM FACTURA WHERE ID_RUTA = ?");
            $stmt->execute([$idRuta]);
            $cantFacturas = intval($stmt->fetchColumn());

            if ($cantFacturas > 0) {
                // Si tiene facturas, inactivar la ruta para preservar la integridad contable
                $stmtInactivar = $this->db->prepare("UPDATE RUTA SET ESTADO = 'INACTIVO' WHERE ID_RUTA = ?");
                $stmtInactivar->execute([$idRuta]);
                return [
                    'accion' => 'inactivada',
                    'facturas' => $cantFacturas,
                    'mensaje' => "La ruta tiene {$cantFacturas} factura(s) registrada(s). Para proteger el historial contable se ha archivado como INACTIVA y ya no estará disponible para ventas."
                ];
            }

            // 2. Si no tiene facturas, eliminar tramos asociados y luego la ruta física
            $this->db->beginTransaction();
            $this->db->prepare("DELETE FROM RUTA_TRAMO WHERE ID_RUTA = ?")->execute([$idRuta]);
            $this->db->prepare("DELETE FROM RUTA WHERE ID_RUTA = ?")->execute([$idRuta]);
            $this->db->commit();

            return [
                'accion' => 'eliminada',
                'mensaje' => "Ruta eliminada permanentemente del sistema."
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $e->getMessage();
        }
    }

    // ==========================================
    // SECCIÓN BUSES Y OPERADORES / SOCIOS
    // ==========================================
    public function obtenerBusesConSocios() {
        $colsBus = array_column($this->db->query("DESCRIBE BUS")->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $hasIdOp = in_array('ID_OPERADOR', $colsBus);

        $campoOp = $hasIdOp ? "b.ID_OPERADOR, op.NOMBRE_COMERCIAL as OPERADOR_NOMBRE, op.TIPO_OPERADOR as OPERADOR_TIPO, op.BANCO as OPERADOR_BANCO, op.NUMERO_CUENTA as OPERADOR_CUENTA, op.TELEFONO as OPERADOR_TELEFONO, op.IDENTIFICACION as OPERADOR_DOC," : "NULL as ID_OPERADOR, 'Suramérica 2030' as OPERADOR_NOMBRE, 'PROPIO' as OPERADOR_TIPO, '' as OPERADOR_BANCO, '' as OPERADOR_CUENTA, '' as OPERADOR_TELEFONO, '' as OPERADOR_DOC,";

        $joinOp = $hasIdOp ? "LEFT JOIN OPERADOR op ON b.ID_OPERADOR = op.ID_OPERADOR" : "";

        $query = "SELECT b.ID_BUS, b.BUS_MARCA, b.BUS_MODELO, b.BUS_PLACA, b.BUS_ASIENTOS,
                         $campoOp
                         s.SOC_NOMBRE, s.SOC_APELLIDOS, s.SOC_TELEFONO, s.SOC_BANK, s.SOC_CUENTA 
                  FROM BUS b 
                  $joinOp
                  LEFT JOIN SOCIO s ON b.ID_BUS = s.ID_BUS 
                  ORDER BY b.ID_BUS DESC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function agregarBusYSocioBD($datos) {
        try {
            $this->db->beginTransaction();

            $colsBus = array_column($this->db->query("DESCRIBE BUS")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            $hasIdOp = in_array('ID_OPERADOR', $colsBus);
            $idOperador = !empty($datos['id_operador']) ? intval($datos['id_operador']) : 1;

            if ($hasIdOp) {
                $stmtBus = $this->db->prepare("INSERT INTO BUS (ID_OPERADOR, BUS_MARCA, BUS_MODELO, BUS_PLACA, BUS_ASIENTOS) VALUES (:id_op, :marca, :modelo, :placa, :asientos)");
                $stmtBus->execute([
                    ':id_op' => $idOperador,
                    ':marca' => $datos['marca'],
                    ':modelo' => $datos['modelo'],
                    ':placa' => mb_strtoupper($datos['placa']),
                    ':asientos' => $datos['asientos']
                ]);
            } else {
                $stmtBus = $this->db->prepare("INSERT INTO BUS (BUS_MARCA, BUS_MODELO, BUS_PLACA, BUS_ASIENTOS) VALUES (:marca, :modelo, :placa, :asientos)");
                $stmtBus->execute([
                    ':marca' => $datos['marca'],
                    ':modelo' => $datos['modelo'],
                    ':placa' => mb_strtoupper($datos['placa']),
                    ':asientos' => $datos['asientos']
                ]);
            }
            $idBus = $this->db->lastInsertId();

            $stmtSocio = $this->db->prepare("INSERT INTO SOCIO (ID_BUS, SOC_NOMBRE, SOC_APELLIDOS, SOC_TELEFONO, SOC_BANK, SOC_CUENTA) VALUES (:bus, :nombre, :apellido, :telefono, :banco, :cuenta)");
            $stmtSocio->execute([
                ':bus' => $idBus,
                ':nombre' => $datos['socio_nombre'] ?? '',
                ':apellido' => $datos['socio_apellido'] ?? '',
                ':telefono' => $datos['socio_telefono'] ?? '',
                ':banco' => $datos['socio_banco'] ?? '',
                ':cuenta' => $datos['socio_cuenta'] ?? ''
            ]);

            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function actualizarBusYSocioBD($datos) {
        try {
            $this->db->beginTransaction();

            $colsBus = array_column($this->db->query("DESCRIBE BUS")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            $hasIdOp = in_array('ID_OPERADOR', $colsBus);
            $idOperador = !empty($datos['id_operador']) ? intval($datos['id_operador']) : 1;

            if ($hasIdOp) {
                $this->db->prepare("UPDATE BUS SET ID_OPERADOR = :id_op, BUS_MARCA = :marca, BUS_MODELO = :modelo, BUS_PLACA = :placa, BUS_ASIENTOS = :asientos WHERE ID_BUS = :id_bus")
                         ->execute([
                             ':id_op' => $idOperador,
                             ':marca' => $datos['marca'],
                             ':modelo' => $datos['modelo'],
                             ':placa' => mb_strtoupper($datos['placa']),
                             ':asientos' => $datos['asientos'],
                             ':id_bus' => $datos['id_bus']
                         ]);
            } else {
                $this->db->prepare("UPDATE BUS SET BUS_MARCA = :marca, BUS_MODELO = :modelo, BUS_PLACA = :placa, BUS_ASIENTOS = :asientos WHERE ID_BUS = :id_bus")
                         ->execute([
                             ':marca' => $datos['marca'],
                             ':modelo' => $datos['modelo'],
                             ':placa' => mb_strtoupper($datos['placa']),
                             ':asientos' => $datos['asientos'],
                             ':id_bus' => $datos['id_bus']
                         ]);
            }

            $check = $this->db->prepare("SELECT ID_SOCIO FROM SOCIO WHERE ID_BUS = ?");
            $check->execute([$datos['id_bus']]);

            if ($check->fetch()) {
                $stmtSocio = $this->db->prepare("UPDATE SOCIO SET SOC_NOMBRE = :nombre, SOC_APELLIDOS = :apellido, SOC_TELEFONO = :telefono, SOC_BANK = :banco, SOC_CUENTA = :cuenta WHERE ID_BUS = :id_bus");
            } else {
                $stmtSocio = $this->db->prepare("INSERT INTO SOCIO (ID_BUS, SOC_NOMBRE, SOC_APELLIDOS, SOC_TELEFONO, SOC_BANK, SOC_CUENTA) VALUES (:id_bus, :nombre, :apellido, :telefono, :banco, :cuenta)");
            }
            $stmtSocio->execute([
                ':id_bus' => $datos['id_bus'],
                ':nombre' => $datos['socio_nombre'] ?? '',
                ':apellido' => $datos['socio_apellido'] ?? '',
                ':telefono' => $datos['socio_telefono'] ?? '',
                ':banco' => $datos['socio_banco'] ?? '',
                ':cuenta' => $datos['socio_cuenta'] ?? ''
            ]);

            $this->db->commit(); return true;
        } catch (Exception $e) { $this->db->rollBack(); return $e->getMessage(); }
    }

    public function eliminarBusBD($idBus) {
        try {
            $this->db->beginTransaction();
            
            // 1. Primero eliminamos al Socio vinculado a este bus
            $this->db->prepare("DELETE FROM SOCIO WHERE ID_BUS = :id")->execute([':id' => $idBus]);
            
            // 2. Luego eliminamos la unidad
            $this->db->prepare("DELETE FROM BUS WHERE ID_BUS = :id")->execute([':id' => $idBus]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) { 
            $this->db->rollBack();
            return "No se puede eliminar la unidad porque ya tiene viajes programados o pasajes vendidos vinculados a su placa."; 
        }
    }

    // ==========================================
    // SECCIÓN OPERADORES / SOCIOS MAESTROS
    // ==========================================
    public function obtenerOperadores($soloActivos = true) {
        $cols = array_column($this->db->query("DESCRIBE OPERADOR")->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $sql = "SELECT op.*, 
                       (SELECT COUNT(*) FROM BUS b WHERE b.ID_OPERADOR = op.ID_OPERADOR) as TOTAL_BUSES,
                       (SELECT GROUP_CONCAT(b.BUS_PLACA SEPARATOR ', ') FROM BUS b WHERE b.ID_OPERADOR = op.ID_OPERADOR) as PLACAS_BUSES
                FROM OPERADOR op ";
        if ($soloActivos) {
            $sql .= " WHERE op.ESTADO = 'ACTIVO' ";
        }
        $sql .= " ORDER BY CASE op.TIPO_OPERADOR WHEN 'PROPIO' THEN 1 WHEN 'SOCIO_AFILIADO' THEN 2 ELSE 3 END, op.NOMBRE_COMERCIAL ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerOperadorPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM OPERADOR WHERE ID_OPERADOR = ?");
        $stmt->execute([intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardarOperadorBD($datos) {
        try {
            $sql = "INSERT INTO OPERADOR 
                    (NOMBRE_COMERCIAL, RAZON_SOCIAL, TIPO_OPERADOR, IDENTIFICACION, TELEFONO, CORREO, BANCO, TIPO_CUENTA, NUMERO_CUENTA, TITULAR_CUENTA, TITULAR_DOC, ESTADO, OBSERVACIONES)
                    VALUES
                    (:nombre, :razon, :tipo, :doc, :tel, :correo, :banco, :tipo_cta, :num_cta, :titular, :titular_doc, :estado, :obs)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre' => trim($datos['nombre_comercial']),
                ':razon' => trim($datos['razon_social'] ?? '') ?: trim($datos['nombre_comercial']),
                ':tipo' => $datos['tipo_operador'] ?? 'SOCIO_AFILIADO',
                ':doc' => trim($datos['identificacion'] ?? ''),
                ':tel' => trim($datos['telefono'] ?? ''),
                ':correo' => trim($datos['correo'] ?? ''),
                ':banco' => trim($datos['banco'] ?? ''),
                ':tipo_cta' => $datos['tipo_cuenta'] ?? 'CORRIENTE',
                ':num_cta' => trim($datos['numero_cuenta'] ?? ''),
                ':titular' => trim($datos['titular_cuenta'] ?? '') ?: trim($datos['nombre_comercial']),
                ':titular_doc' => trim($datos['titular_doc'] ?? '') ?: trim($datos['identificacion'] ?? ''),
                ':estado' => $datos['estado'] ?? 'ACTIVO',
                ':obs' => trim($datos['observaciones'] ?? '')
            ]);
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function actualizarOperadorBD($datos) {
        try {
            $sql = "UPDATE OPERADOR SET
                    NOMBRE_COMERCIAL = :nombre,
                    RAZON_SOCIAL = :razon,
                    TIPO_OPERADOR = :tipo,
                    IDENTIFICACION = :doc,
                    TELEFONO = :tel,
                    CORREO = :correo,
                    BANCO = :banco,
                    TIPO_CUENTA = :tipo_cta,
                    NUMERO_CUENTA = :num_cta,
                    TITULAR_CUENTA = :titular,
                    TITULAR_DOC = :titular_doc,
                    ESTADO = :estado,
                    OBSERVACIONES = :obs
                    WHERE ID_OPERADOR = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => intval($datos['id_operador']),
                ':nombre' => trim($datos['nombre_comercial']),
                ':razon' => trim($datos['razon_social'] ?? '') ?: trim($datos['nombre_comercial']),
                ':tipo' => $datos['tipo_operador'] ?? 'SOCIO_AFILIADO',
                ':doc' => trim($datos['identificacion'] ?? ''),
                ':tel' => trim($datos['telefono'] ?? ''),
                ':correo' => trim($datos['correo'] ?? ''),
                ':banco' => trim($datos['banco'] ?? ''),
                ':tipo_cta' => $datos['tipo_cuenta'] ?? 'CORRIENTE',
                ':num_cta' => trim($datos['numero_cuenta'] ?? ''),
                ':titular' => trim($datos['titular_cuenta'] ?? '') ?: trim($datos['nombre_comercial']),
                ':titular_doc' => trim($datos['titular_doc'] ?? '') ?: trim($datos['identificacion'] ?? ''),
                ':estado' => $datos['estado'] ?? 'ACTIVO',
                ':obs' => trim($datos['observaciones'] ?? '')
            ]);
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function eliminarOperadorBD($idOperador) {
        try {
            $id = intval($idOperador);
            // 1. Prohibir eliminar el operador principal de Flota Propia (ID 1)
            if ($id <= 1) {
                return "No se puede eliminar el operador principal de Flota Propia de la empresa.";
            }

            $stmt = $this->db->prepare("SELECT TIPO_OPERADOR, NOMBRE_COMERCIAL FROM OPERADOR WHERE ID_OPERADOR = ?");
            $stmt->execute([$id]);
            $op = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$op) {
                return "El operador no existe o ya fue eliminado.";
            }
            if ($op['TIPO_OPERADOR'] === 'PROPIO') {
                return "No se puede eliminar el operador principal de Flota Propia de la empresa.";
            }

            $this->db->beginTransaction();

            // Reasignar cualquier bus, ruta o tramo vinculado al operador 1 (Flota Propia)
            $this->db->prepare("UPDATE BUS SET ID_OPERADOR = 1 WHERE ID_OPERADOR = ?")->execute([$id]);
            $this->db->prepare("UPDATE RUTA SET ID_OPERADOR = 1 WHERE ID_OPERADOR = ?")->execute([$id]);
            $this->db->prepare("UPDATE RUTA_TRAMO SET ID_OPERADOR = 1, OPERADOR = 'Expresos Suramérica 2030' WHERE ID_OPERADOR = ?")->execute([$id]);

            // Eliminar físicamente el registro del operador
            $stmtDel = $this->db->prepare("DELETE FROM OPERADOR WHERE ID_OPERADOR = ?");
            $stmtDel->execute([$id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return "Error al eliminar el operador: " . $e->getMessage();
        }
    }

    // ==========================================
    // SECCIÓN CATÁLOGO MAESTRO DE TRAMOS Y TARIFAS
    // ==========================================

    public function obtenerTramosCatalogo($soloActivos = false) {
        $where = $soloActivos ? "WHERE tc.ESTADO = 'ACTIVO'" : "";
        $sql = "
            SELECT tc.*, 
                   op.NOMBRE_COMERCIAL as OPERADOR_NOMBRE_OFICIAL,
                   op.TIPO_OPERADOR as OPERADOR_TIPO,
                   COUNT(DISTINCT rt.ID_RUTA) as TOTAL_RUTAS_USANDO
            FROM tramo_catalogo tc
            LEFT JOIN OPERADOR op ON tc.ID_OPERADOR = op.ID_OPERADOR
            LEFT JOIN RUTA_TRAMO rt ON (rt.ID_TRAMO_CATALOGO = tc.ID_TRAMO_CATALOGO OR (LOWER(rt.ORIGEN_TRAMO) = LOWER(tc.ORIGEN_TRAMO) AND LOWER(rt.DESTINO_TRAMO) = LOWER(tc.DESTINO_TRAMO)))
            $where
            GROUP BY tc.ID_TRAMO_CATALOGO
            ORDER BY tc.PAIS_TRAMO ASC, tc.ORIGEN_TRAMO ASC, tc.DESTINO_TRAMO ASC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTramoCatalogoPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM tramo_catalogo WHERE ID_TRAMO_CATALOGO = ?");
        $stmt->execute([intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardarTramoCatalogoBD($datos) {
        try {
            $id = !empty($datos['id_tramo_catalogo']) ? intval($datos['id_tramo_catalogo']) : 0;
            $origen = trim($datos['origen_tramo'] ?? '');
            $destino = trim($datos['destino_tramo'] ?? '');
            $nombre = trim($datos['nombre_tramo'] ?? "$origen ➔ $destino");
            $pais = strtoupper(trim($datos['pais_tramo'] ?? 'INTERNACIONAL'));
            $idOperador = !empty($datos['id_operador']) ? intval($datos['id_operador']) : null;
            $costo = floatval($datos['costo_operador'] ?? 0);
            $pvp = floatval($datos['pvp_sugerido'] ?? 0);
            $utilidad = max(0, $pvp - $costo);
            $admiteSp = isset($datos['admite_sobrepeso']) ? intval($datos['admite_sobrepeso']) : 1;
            $limiteEq = floatval($datos['limite_equipaje_kg'] ?? 35);
            $precioSp = floatval($datos['precio_sobrepeso_kg'] ?? 1);
            $costoSp = floatval($datos['costo_sobrepeso_kg'] ?? 0);
            $estado = !empty($datos['estado']) ? $datos['estado'] : 'ACTIVO';
            $observaciones = trim($datos['observaciones'] ?? '');

            if (empty($origen) || empty($destino)) {
                return "Debe especificar origen y destino del tramo.";
            }

            // Validar que PVP sea mayor o igual al costo
            if ($pvp > 0 && $costo > $pvp) {
                return "Error financiero: El PVP sugerido ($" . number_format($pvp, 2) . ") no puede ser menor al costo del operador ($" . number_format($costo, 2) . ").";
            }

            // Nombre comercial del operador
            $operadorNombre = '';
            if ($idOperador) {
                $stmtOp = $this->db->prepare("SELECT NOMBRE_COMERCIAL FROM OPERADOR WHERE ID_OPERADOR = ?");
                $stmtOp->execute([$idOperador]);
                $operadorNombre = $stmtOp->fetchColumn() ?: '';
            }

            // Asegurar ciudades en el dominio
            $this->asegurarCiudadExiste($origen);
            $this->asegurarCiudadExiste($destino);

            $tiempoHoras = floatval($datos['tiempo_estimado_horas'] ?? 0);
            $utilidadSp = max(0, $precioSp - $costoSp);

            if ($id > 0) {
                $sql = "UPDATE tramo_catalogo SET 
                    NOMBRE_TRAMO = ?, ORIGEN_TRAMO = ?, DESTINO_TRAMO = ?, PAIS_TRAMO = ?,
                    ID_OPERADOR = ?, OPERADOR_NOMBRE = ?, COSTO_OPERADOR = ?, PVP_SUGERIDO = ?,
                    UTILIDAD_AGENCIA = ?, ADMITE_SOBREPESO = ?, TIEMPO_ESTIMADO_HORAS = ?, LIMITE_EQUIPAJE_KG = ?,
                    PRECIO_SOBREPESO_KG = ?, COSTO_SOBREPESO_KG = ?, UTILIDAD_SOBREPESO_KG = ?, ESTADO = ?, OBSERVACIONES = ?
                    WHERE ID_TRAMO_CATALOGO = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $nombre, $origen, $destino, $pais,
                    $idOperador, $operadorNombre, $costo, $pvp,
                    $utilidad, $admiteSp, $tiempoHoras, $limiteEq,
                    $precioSp, $costoSp, $utilidadSp, $estado, $observaciones,
                    $id
                ]);
            } else {
                $codigoOp = ($idOperador == 2) ? 'PAN' : (($idOperador == 1) ? 'SUR' : 'LOC');
                $cOrig = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $origen), 0, 3));
                $cDest = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $destino), 0, 3));
                $codigo = "TC-{$cOrig}-{$cDest}-{$codigoOp}";

                $sql = "INSERT INTO tramo_catalogo (
                    CODIGO, NOMBRE_TRAMO, ORIGEN_TRAMO, DESTINO_TRAMO, PAIS_TRAMO,
                    ID_OPERADOR, OPERADOR_NOMBRE, COSTO_OPERADOR, PVP_SUGERIDO,
                    UTILIDAD_AGENCIA, ADMITE_SOBREPESO, TIEMPO_ESTIMADO_HORAS, LIMITE_EQUIPAJE_KG,
                    PRECIO_SOBREPESO_KG, COSTO_SOBREPESO_KG, UTILIDAD_SOBREPESO_KG, ESTADO, OBSERVACIONES
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $codigo, $nombre, $origen, $destino, $pais,
                    $idOperador, $operadorNombre, $costo, $pvp,
                    $utilidad, $admiteSp, $tiempoHoras, $limiteEq,
                    $precioSp, $costoSp, $utilidadSp, $estado, $observaciones
                ]);
                $id = (int)$this->db->lastInsertId();
            }

            if (!empty($datos['sincronizar_rutas'])) {
                $this->sincronizarTarifasTramosEnRutas($id);
            }

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function duplicarTramoInversoBD($idTramo) {
        try {
            $t = $this->obtenerTramoCatalogoPorId($idTramo);
            if (!$t) return "Tramo maestro no encontrado.";

            $origen = trim($t['DESTINO_TRAMO']);
            $destino = trim($t['ORIGEN_TRAMO']);
            $idOperador = !empty($t['ID_OPERADOR']) ? intval($t['ID_OPERADOR']) : null;

            // Verificar si el tramo inverso ya existe
            $stmtCheck = $this->db->prepare("SELECT ID_TRAMO_CATALOGO FROM tramo_catalogo WHERE LOWER(ORIGEN_TRAMO) = LOWER(?) AND LOWER(DESTINO_TRAMO) = LOWER(?) AND ID_OPERADOR <=> ?");
            $stmtCheck->execute([$origen, $destino, $idOperador]);
            $idExistente = $stmtCheck->fetchColumn();

            if ($idExistente) {
                return "El tramo inverso '{$origen} ➔ {$destino}' ya existe en el catálogo (#{$idExistente}).";
            }

            $codigoOp = ($idOperador == 2) ? 'PAN' : (($idOperador == 1) ? 'SUR' : 'LOC');
            $cOrig = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $origen), 0, 3));
            $cDest = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $destino), 0, 3));
            $codigo = "TC-{$cOrig}-{$cDest}-{$codigoOp}";
            $nombre = "{$origen} ➔ {$destino}";

            $sql = "INSERT INTO tramo_catalogo (
                CODIGO, NOMBRE_TRAMO, ORIGEN_TRAMO, DESTINO_TRAMO, PAIS_TRAMO,
                ID_OPERADOR, OPERADOR_NOMBRE, COSTO_OPERADOR, PVP_SUGERIDO, UTILIDAD_AGENCIA,
                TIEMPO_ESTIMADO_HORAS, ADMITE_SOBREPESO, LIMITE_EQUIPAJE_KG, PRECIO_SOBREPESO_KG,
                COSTO_SOBREPESO_KG, UTILIDAD_SOBREPESO_KG, ESTADO, OBSERVACIONES
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO', ?)";

            $stmtIns = $this->db->prepare($sql);
            $stmtIns->execute([
                $codigo, $nombre, $origen, $destino, $t['PAIS_TRAMO'],
                $idOperador, $t['OPERADOR_NOMBRE'], $t['COSTO_OPERADOR'], $t['PVP_SUGERIDO'], $t['UTILIDAD_AGENCIA'],
                $t['TIEMPO_ESTIMADO_HORAS'] ?? 0, $t['ADMITE_SOBREPESO'], $t['LIMITE_EQUIPAJE_KG'], $t['PRECIO_SOBREPESO_KG'],
                $t['COSTO_SOBREPESO_KG'], $t['UTILIDAD_SOBREPESO_KG'] ?? 1, 'Tramo inverso generado automáticamente'
            ]);

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function cambiarEstadoTramoCatalogoBD($idTramo, $estado) {
        try {
            $estado = ($estado === 'ACTIVO' || $estado == 1) ? 'ACTIVO' : 'INACTIVO';
            $stmt = $this->db->prepare("UPDATE tramo_catalogo SET ESTADO = ? WHERE ID_TRAMO_CATALOGO = ?");
            $stmt->execute([$estado, intval($idTramo)]);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function eliminarTramoCatalogoBD($idTramo) {
        try {
            $id = intval($idTramo);
            // Validar si rutas comerciales activas lo usan
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM RUTA_TRAMO WHERE ID_TRAMO_CATALOGO = ?");
            $stmt->execute([$id]);
            $count = intval($stmt->fetchColumn());

            if ($count > 0) {
                $this->db->prepare("UPDATE tramo_catalogo SET ESTADO = 'INACTIVO' WHERE ID_TRAMO_CATALOGO = ?")->execute([$id]);
                return "El tramo está vinculado a {$count} ruta(s) comercial(es). Se ha archivado como INACTIVO para proteger la integridad operativa.";
            }

            $this->db->prepare("DELETE FROM tramo_catalogo WHERE ID_TRAMO_CATALOGO = ?")->execute([$id]);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function sincronizarTarifasTramosEnRutas($idTramoCatalogo = null) {
        try {
            $this->db->beginTransaction();

            $whereTramo = $idTramoCatalogo ? "WHERE tc.ID_TRAMO_CATALOGO = " . intval($idTramoCatalogo) : "";
            $sqlTramos = "SELECT * FROM tramo_catalogo tc $whereTramo";
            $catalogo = $this->db->query($sqlTramos)->fetchAll(PDO::FETCH_ASSOC);

            $stmtUpdateRt = $this->db->prepare("
                UPDATE RUTA_TRAMO 
                SET PVP_TRAMO = ?, COSTO_OPERADOR = ?, UTILIDAD_AGENCIA = ?, ID_TRAMO_CATALOGO = ?, ID_OPERADOR = COALESCE(?, ID_OPERADOR)
                WHERE (ID_TRAMO_CATALOGO = ? OR (LOWER(ORIGEN_TRAMO) = LOWER(?) AND LOWER(DESTINO_TRAMO) = LOWER(?)))
            ");

            $rutasModificadas = [];

            foreach ($catalogo as $tc) {
                $stmtUpdateRt->execute([
                    $tc['PVP_SUGERIDO'],
                    $tc['COSTO_OPERADOR'],
                    $tc['UTILIDAD_AGENCIA'],
                    $tc['ID_TRAMO_CATALOGO'],
                    $tc['ID_OPERADOR'],
                    $tc['ID_TRAMO_CATALOGO'],
                    $tc['ORIGEN_TRAMO'],
                    $tc['DESTINO_TRAMO']
                ]);

                // Identificar rutas afectadas
                $stmtRutas = $this->db->prepare("SELECT DISTINCT ID_RUTA FROM RUTA_TRAMO WHERE ID_TRAMO_CATALOGO = ? OR (LOWER(ORIGEN_TRAMO) = LOWER(?) AND LOWER(DESTINO_TRAMO) = LOWER(?))");
                $stmtRutas->execute([$tc['ID_TRAMO_CATALOGO'], $tc['ORIGEN_TRAMO'], $tc['DESTINO_TRAMO']]);
                foreach ($stmtRutas->fetchAll(PDO::FETCH_COLUMN) as $idR) {
                    $rutasModificadas[intval($idR)] = true;
                }
            }

            // Recalcular totales en cabecera RUTA para cada ruta afectada
            $stmtSumas = $this->db->prepare("
                SELECT SUM(PVP_TRAMO) as TOTAL_PVP, SUM(COSTO_OPERADOR) as TOTAL_COSTO, SUM(UTILIDAD_AGENCIA) as TOTAL_UTIL
                FROM RUTA_TRAMO WHERE ID_RUTA = ?
            ");
            $stmtUpdateRuta = $this->db->prepare("
                UPDATE RUTA 
                SET PRECIO_PASAJE = ?, COSTO_TOTAL_RUTA = ?, UTILIDAD_PASAJE = ?
                WHERE ID_RUTA = ? AND TIPO_VIAJE = 'CONEXION'
            ");

            foreach (array_keys($rutasModificadas) as $idRuta) {
                $stmtSumas->execute([$idRuta]);
                $sum = $stmtSumas->fetch(PDO::FETCH_ASSOC);
                if ($sum && $sum['TOTAL_PVP'] > 0) {
                    $totPvp = floatval($sum['TOTAL_PVP']);
                    $totCosto = floatval($sum['TOTAL_COSTO']);
                    $totUtil = max(0, $totPvp - $totCosto);
                    $stmtUpdateRuta->execute([$totPvp, $totCosto, $totUtil, $idRuta]);
                }
            }

            $this->db->commit();
            return [
                'status' => 'success',
                'rutas_actualizadas' => count($rutasModificadas),
                'mensaje' => "Sincronización completada: " . count($rutasModificadas) . " rutas comerciales actualizadas con las tarifas maestras."
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    // ==========================================
    // AUTO-SUGERIDOR INTELIGENTE Y CREACIÓN EN 3 CLICS
    // ==========================================

    public function sugerirTramosParaRuta($origen, $destino, $modalidad = 'CONEXIONES') {
        $engine = new RoutingEngine($this->db);
        return $engine->sugerirTramosParaModalidad($origen, $destino, $modalidad);
    }

    public function sugerirTodasLasModalidadesRuta($origen, $destino) {
        $engine = new RoutingEngine($this->db);
        return $engine->sugerirTodasLasModalidades($origen, $destino);
    }

    public function generarLoteRutasMultimodales($origen, $destino, array $modalidades = [], array $opciones = []) {
        $engine = new RoutingEngine($this->db);
        return $engine->generarLoteRutas($origen, $destino, $modalidades, $opciones);
    }

    public function insertarRutaIndividual($datos) {
        $colsRuta = array_column($this->db->query("DESCRIBE RUTA")->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $hasAdmiteSp = in_array('ADMITE_SOBREPESO', $colsRuta);
        $hasIdOperador = in_array('ID_OPERADOR', $colsRuta);
        $hasCostoTotal = in_array('COSTO_TOTAL_RUTA', $colsRuta);

        $idOp = !empty($datos['id_operador']) ? intval($datos['id_operador']) : 1;
        $modalidad = !empty($datos['modalidad']) ? $datos['modalidad'] : 'CONEXION_TRAMOS';
        $costoTotalRuta = floatval($datos['costo_total_ruta'] ?? 0);
        $tramos = $datos['tramos'] ?? [];

        if ($costoTotalRuta <= 0 && !empty($tramos)) {
            $costoTotalRuta = array_sum(array_column($tramos, 'costo'));
        }

        $sentido = !empty($datos['sentido']) ? $datos['sentido'] : \Platform\Helpers\SentidoHelper::resolverSentido($datos['origen'], $datos['destino']);

        $camposInsert = "SENTIDO, RUT_ORIGEN, RUT_DESTINO, TIPO_VIAJE, MODALIDAD" 
            . ($hasIdOperador ? ", ID_OPERADOR" : "") 
            . ($hasAdmiteSp ? ", ADMITE_SOBREPESO" : "") 
            . ($hasCostoTotal ? ", COSTO_TOTAL_RUTA" : "") 
            . ", DIAS_SALIDA, RUT_SAHORA, PRECIO_PASAJE, PRECIO_ENCOMIENDA_KG, PRECIO_SOBRE, PRECIO_VOLUMEN, LIMITE_EQUIPAJE_KG, PRECIO_SOBREPESO_KG, COMISION_VALOR, TIPO_UTILIDAD, UTILIDAD_PASAJE, UTILIDAD_ENCOMIENDA, UTILIDAD_VALORES, ESTADO";
        
        $valoresInsert = ":sentido, :origen, :destino, :tipo, :modalidad" 
            . ($hasIdOperador ? ", :id_operador" : "") 
            . ($hasAdmiteSp ? ", :admite_sp" : "") 
            . ($hasCostoTotal ? ", :costo_total" : "") 
            . ", :dias, :hora, :precio_p, :precio_e, :precio_sobre, :precio_vol, :limite_eq, :precio_s, :comision_v, :t_util, :u_pasaje, :u_enc, :u_val, 'ACTIVO'";

        $query = "INSERT INTO RUTA ($camposInsert) VALUES ($valoresInsert)";
        $stmt = $this->db->prepare($query);
        $params = [
            ':sentido' => $sentido,
            ':origen' => $datos['origen'], 
            ':destino' => $datos['destino'], 
            ':tipo' => $datos['tipo_viaje'] ?? ((!empty($tramos) && count($tramos) > 1) ? 'CONEXION' : 'DIRECTO'), 
            ':modalidad' => $modalidad,
            ':dias' => $datos['dias_salida'] ?? '1,2,3,4,5,6,7', 
            ':hora' => $datos['hora_salida'] ?? '08:00:00', 
            ':precio_p' => floatval($datos['precio_pasaje'] ?? 0), 
            ':precio_e' => floatval($datos['precio_encomienda'] ?? 0), 
            ':precio_sobre' => floatval($datos['precio_sobre'] ?? 5), 
            ':precio_vol' => floatval($datos['precio_volumen'] ?? 0), 
            ':limite_eq' => floatval($datos['limite_equipaje'] ?? 35), 
            ':precio_s' => floatval($datos['precio_sobrepeso'] ?? 1), 
            ':comision_v' => floatval($datos['comision_valor'] ?? 10), 
            ':t_util' => $datos['tipo_utilidad'] ?? 'FIJO', 
            ':u_pasaje' => floatval($datos['utilidad_pasaje'] ?? max(0, floatval($datos['precio_pasaje'] ?? 0) - $costoTotalRuta)), 
            ':u_enc' => floatval($datos['utilidad_encomienda'] ?? 100), 
            ':u_val' => floatval($datos['utilidad_valores'] ?? 100)
        ];
        if ($hasIdOperador) {
            $params[':id_operador'] = $idOp;
        }
        if ($hasAdmiteSp) {
            $params[':admite_sp'] = $datos['admite_sobrepeso'] ?? 1;
        }
        if ($hasCostoTotal) {
            $params[':costo_total'] = $costoTotalRuta;
        }
        $stmt->execute($params);

        $idRuta = (int)$this->db->lastInsertId();

        $this->asegurarCiudadExiste($datos['origen']);
        $this->asegurarCiudadExiste($datos['destino']);

        if (!empty($tramos)) {
            $colsTramo = array_column($this->db->query("DESCRIBE RUTA_TRAMO")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            $tramoHasIdOp = in_array('ID_OPERADOR', $colsTramo);
            $tramoHasIdCat = in_array('ID_TRAMO_CATALOGO', $colsTramo);
            $tramoHasDiaDesfase = in_array('DIA_DESFASE', $colsTramo);
            $tramoHasIdOferta = in_array('ID_OFERTA', $colsTramo);

            $sqlTramo = "INSERT INTO RUTA_TRAMO 
                (ID_RUTA, ORDEN" 
                . ($tramoHasDiaDesfase ? ", DIA_DESFASE" : "") 
                . ", ORIGEN_TRAMO, DESTINO_TRAMO, OPERADOR" 
                . ($tramoHasIdOp ? ", ID_OPERADOR" : "") 
                . ($tramoHasIdCat ? ", ID_TRAMO_CATALOGO" : "") 
                . ($tramoHasIdOferta ? ", ID_OFERTA" : "") 
                . ", PVP_TRAMO, COSTO_OPERADOR, UTILIDAD_AGENCIA, ADMITE_SOBREPESO, LIMITE_EQUIPAJE_KG, PRECIO_SOBREPESO_KG, COSTO_SOBREPESO_KG, UTILIDAD_SOBREPESO_KG) 
                VALUES (?, ?" 
                . ($tramoHasDiaDesfase ? ", ?" : "") 
                . ", ?, ?, ?" 
                . ($tramoHasIdOp ? ", ?" : "") 
                . ($tramoHasIdCat ? ", ?" : "") 
                . ($tramoHasIdOferta ? ", ?" : "") 
                . ", ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtTramo = $this->db->prepare($sqlTramo);

            foreach ($tramos as $i => $tramo) {
                $this->asegurarCiudadExiste($tramo['origen']);
                $this->asegurarCiudadExiste($tramo['destino']);
                $desfase = isset($tramo['dia_desfase']) ? intval($tramo['dia_desfase']) : max(0, $i);
                $vals = [
                    $idRuta, 
                    $i + 1
                ];
                if ($tramoHasDiaDesfase) {
                    $vals[] = $desfase;
                }
                $vals[] = $tramo['origen'];
                $vals[] = $tramo['destino'];
                $vals[] = $tramo['operador'] ?? 'Aliado';
                if ($tramoHasIdOp) {
                    $vals[] = !empty($tramo['id_operador']) ? intval($tramo['id_operador']) : 1;
                }
                if ($tramoHasIdCat) {
                    $vals[] = !empty($tramo['id_tramo_catalogo']) ? intval($tramo['id_tramo_catalogo']) : null;
                }
                if ($tramoHasIdOferta) {
                    $vals[] = !empty($tramo['id_oferta']) ? intval($tramo['id_oferta']) : null;
                }
                $vals = array_merge($vals, [
                    floatval($tramo['pvp'] ?? 0.00),
                    floatval($tramo['costo'] ?? 0.00),
                    floatval($tramo['utilidad'] ?? 0.00),
                    intval($tramo['admite_sobrepeso'] ?? 1),
                    floatval($tramo['limite_equipaje'] ?? 35.00),
                    floatval($tramo['precio_sobrepeso'] ?? 1.00),
                    floatval($tramo['costo_sobrepeso'] ?? 0.00),
                    floatval($tramo['utilidad_sobrepeso'] ?? 0.00)
                ]);
                $stmtTramo->execute($vals);
            }
        }

        return $idRuta;
    }

    public function crearRutaRapidaBD($datos) {
        try {
            $this->db->beginTransaction();

            $origen = trim($datos['origen'] ?? '');
            $destino = trim($datos['destino'] ?? '');
            $modalidad = trim($datos['modalidad'] ?? 'CONEXION_TRAMOS');
            $diasSalida = !empty($datos['dias_salida']) ? (is_array($datos['dias_salida']) ? implode(',', $datos['dias_salida']) : $datos['dias_salida']) : '1,2,3,4,5,6,7';
            $horaSalida = !empty($datos['hora_salida']) ? $datos['hora_salida'] : '08:00:00';
            $precioPasaje = floatval($datos['precio_pasaje'] ?? 0);
            $costoTotalRuta = floatval($datos['costo_total_ruta'] ?? 0);
            $crearRetorno = !empty($datos['crear_retorno']);

            if (empty($origen) || empty($destino)) {
                throw new Exception("Debe especificar origen y destino.");
            }

            // Validar regla de integridad financiera
            if ($costoTotalRuta > 0 && $precioPasaje < $costoTotalRuta) {
                throw new Exception("Bloqueo de seguridad: El precio de venta al público ($" . number_format($precioPasaje, 2) . ") no puede ser menor al costo total de liquidación a operadores ($" . number_format($costoTotalRuta, 2) . "). La agencia no puede operar a pérdida.");
            }

            $tramos = $datos['tramos'] ?? [];
            if (empty($tramos)) {
                $sug = $this->sugerirTramosParaRuta($origen, $destino, $modalidad);
                $tramos = $sug['tramos'] ?? [];
                if ($costoTotalRuta <= 0) $costoTotalRuta = $sug['costo_total_operadores'];
                if ($precioPasaje <= 0) $precioPasaje = $sug['pvp_sugerido'];
            }

            $utilidadAgencia = max(0, $precioPasaje - $costoTotalRuta);
            $tipoViaje = (!empty($tramos) && count($tramos) > 1) ? 'CONEXION' : 'DIRECTO';

            $idOperadorPrincipal = 1;
            if ($modalidad === 'PANAMERICANA') {
                $idOperadorPrincipal = 2;
            }

            // 1. Crear Ruta Ida
            $idRutaIda = $this->insertarRutaIndividual([
                'origen' => $origen,
                'destino' => $destino,
                'tipo_viaje' => $tipoViaje,
                'modalidad' => $modalidad,
                'id_operador' => $idOperadorPrincipal,
                'dias_salida' => $diasSalida,
                'hora_salida' => $horaSalida,
                'precio_pasaje' => $precioPasaje,
                'costo_total_ruta' => $costoTotalRuta,
                'utilidad_pasaje' => $utilidadAgencia,
                'tramos' => $tramos
            ]);

            $idRutaRetorno = null;
            // 2. Crear Retorno Inverso si se seleccionó
            if ($crearRetorno) {
                $stmtBuscaCat = $this->db->prepare("SELECT ID_TRAMO_CATALOGO, ID_OPERADOR, OPERADOR_NOMBRE, COSTO_OPERADOR, PVP_SUGERIDO, ADMITE_SOBREPESO, LIMITE_EQUIPAJE_KG, PRECIO_SOBREPESO_KG FROM tramo_catalogo WHERE LOWER(ORIGEN_TRAMO) = LOWER(?) AND LOWER(DESTINO_TRAMO) = LOWER(?) AND ESTADO = 'ACTIVO' LIMIT 1");

                $tramosInvertidos = [];
                for ($i = count($tramos) - 1; $i >= 0; $i--) {
                    $tr = $tramos[$i];
                    $origInv = $tr['destino'];
                    $destInv = $tr['origen'];

                    $stmtBuscaCat->execute([$origInv, $destInv]);
                    $catInv = $stmtBuscaCat->fetch(PDO::FETCH_ASSOC);

                    if ($catInv) {
                        $tramosInvertidos[] = [
                            'origen' => $origInv,
                            'destino' => $destInv,
                            'operador' => $catInv['OPERADOR_NOMBRE'] ?: ($tr['operador'] ?? 'Aliado'),
                            'id_operador' => intval($catInv['ID_OPERADOR'] ?? ($tr['id_operador'] ?? 1)),
                            'id_tramo_catalogo' => intval($catInv['ID_TRAMO_CATALOGO']),
                            'costo' => floatval($catInv['COSTO_OPERADOR']),
                            'pvp' => floatval($catInv['PVP_SUGERIDO']),
                            'utilidad' => max(0, floatval($catInv['PVP_SUGERIDO']) - floatval($catInv['COSTO_OPERADOR'])),
                            'admite_sobrepeso' => intval($catInv['ADMITE_SOBREPESO'] ?? 1),
                            'limite_equipaje' => floatval($catInv['LIMITE_EQUIPAJE_KG'] ?? 35),
                            'precio_sobrepeso' => floatval($catInv['PRECIO_SOBREPESO_KG'] ?? 1)
                        ];
                    } else {
                        $tramosInvertidos[] = [
                            'origen' => $origInv,
                            'destino' => $destInv,
                            'operador' => $tr['operador'] ?? 'Aliado',
                            'id_operador' => $tr['id_operador'] ?? 1,
                            'id_tramo_catalogo' => null,
                            'costo' => $tr['costo'] ?? 0,
                            'pvp' => $tr['pvp'] ?? 0,
                            'utilidad' => $tr['utilidad'] ?? 0,
                            'admite_sobrepeso' => $tr['admite_sobrepeso'] ?? 1,
                            'limite_equipaje' => $tr['limite_equipaje'] ?? 35,
                            'precio_sobrepeso' => $tr['precio_sobrepeso'] ?? 1
                        ];
                    }
                }

                $idRutaRetorno = $this->insertarRutaIndividual([
                    'origen' => $destino,
                    'destino' => $origen,
                    'tipo_viaje' => $tipoViaje,
                    'modalidad' => $modalidad,
                    'id_operador' => $idOperadorPrincipal,
                    'dias_salida' => $diasSalida,
                    'hora_salida' => $horaSalida,
                    'precio_pasaje' => $precioPasaje,
                    'costo_total_ruta' => $costoTotalRuta,
                    'utilidad_pasaje' => $utilidadAgencia,
                    'tramos' => $tramosInvertidos
                ]);
            }

            $this->db->commit();
            return [
                'status' => 'success',
                'id_ruta_ida' => $idRutaIda,
                'id_ruta_retorno' => $idRutaRetorno,
                'mensaje' => $crearRetorno 
                    ? "¡Ruta de ida (#{$idRutaIda}) y retorno (#{$idRutaRetorno}) creadas exitosamente en 1 solo clic!"
                    : "¡Ruta comercial #{$idRutaIda} creada exitosamente!"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * Actualiza rápidamente el precio comercial (PVP) de una ruta comercial
     * Validando regla fail-safe (PVP >= COSTO_TOTAL_RUTA) y recalculando márgenes.
     */
    public function actualizarPrecioRapidoRutaBD($idRuta, $nuevoPrecio) {
        try {
            $stmt = $this->db->prepare("SELECT ID_RUTA, RUT_ORIGEN, RUT_DESTINO, COSTO_TOTAL_RUTA, PRECIO_PASAJE, UTILIDAD_PASAJE, TIPO_UTILIDAD FROM RUTA WHERE ID_RUTA = ?");
            $stmt->execute([$idRuta]);
            $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ruta) {
                return ['status' => 'error', 'mensaje' => 'Ruta no encontrada en el sistema.'];
            }

            $costoTotal = floatval($ruta['COSTO_TOTAL_RUTA'] ?? 0);
            $nuevoPrecio = floatval($nuevoPrecio);

            // Regla fail-safe: PVP no puede ser menor al costo total de liquidación si costo > 0
            if ($costoTotal > 0 && $nuevoPrecio < $costoTotal) {
                return [
                    'status' => 'error',
                    'mensaje' => "⚠️ Bloqueo de Seguridad: La tarifa comercial ($" . number_format($nuevoPrecio, 2) . ") no puede ser inferior a los costos de liquidación de operadores ($" . number_format($costoTotal, 2) . ")."
                ];
            }

            $utilidadMonto = max(0, $nuevoPrecio - $costoTotal);
            $margenPct = ($nuevoPrecio > 0) ? round(($utilidadMonto / $nuevoPrecio) * 100, 1) : 0;

            $upd = $this->db->prepare("UPDATE RUTA SET PRECIO_PASAJE = ?, UTILIDAD_PASAJE = ? WHERE ID_RUTA = ?");
            $upd->execute([$nuevoPrecio, $utilidadMonto, $idRuta]);

            return [
                'status' => 'success',
                'mensaje' => "Tarifa comercial de la ruta #{$idRuta} actualizada a $" . number_format($nuevoPrecio, 2),
                'id_ruta' => $idRuta,
                'nuevo_precio' => $nuevoPrecio,
                'costo_total' => $costoTotal,
                'utilidad' => $utilidadMonto,
                'margen_pct' => $margenPct
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'mensaje' => 'Error al actualizar tarifa: ' . $e->getMessage()];
        }
    }

    /**
     * Alterna o asigna el estado de una ruta en vivo (ACTIVO / INACTIVO)
     */
    public function cambiarEstadoRutaBD($idRuta, $nuevoEstado = null) {
        try {
            $stmt = $this->db->prepare("SELECT ESTADO FROM RUTA WHERE ID_RUTA = ?");
            $stmt->execute([$idRuta]);
            $actual = $stmt->fetchColumn();

            if ($actual === false) {
                return ['status' => 'error', 'mensaje' => 'Ruta comercial no encontrada.'];
            }

            if ($nuevoEstado === null || !in_array(strtoupper($nuevoEstado), ['ACTIVO', 'INACTIVO'])) {
                $nuevoEstado = (strtoupper($actual) === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';
            } else {
                $nuevoEstado = strtoupper($nuevoEstado);
            }

            $upd = $this->db->prepare("UPDATE RUTA SET ESTADO = ? WHERE ID_RUTA = ?");
            $upd->execute([$nuevoEstado, $idRuta]);

            return [
                'status' => 'success',
                'id_ruta' => $idRuta,
                'estado' => $nuevoEstado,
                'es_activo' => ($nuevoEstado === 'ACTIVO'),
                'mensaje' => "Ruta #{$idRuta} " . ($nuevoEstado === 'ACTIVO' ? 'activada para venta en taquilla.' : 'inactivada y retirada de ventas.')
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'mensaje' => 'Error al cambiar estado de ruta: ' . $e->getMessage()];
        }
    }

    /**
     * Actualiza rápidamente la tarifa de encomienda por kg de una ruta comercial
     */
    public function actualizarPrecioEncomiendaRapidoRutaBD($idRuta, $precioEncomienda) {
        try {
            $precio = max(0, floatval($precioEncomienda));
            $upd = $this->db->prepare("UPDATE RUTA SET PRECIO_ENCOMIENDA_KG = ? WHERE ID_RUTA = ?");
            $upd->execute([$precio, $idRuta]);

            return [
                'status' => 'success',
                'id_ruta' => $idRuta,
                'precio_encomienda' => $precio,
                'mensaje' => "Tarifa de encomienda de la ruta #{$idRuta} actualizada a $" . number_format($precio, 2) . "/Kg."
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'mensaje' => 'Error al actualizar tarifa de encomienda: ' . $e->getMessage()];
        }
    }
}
?>