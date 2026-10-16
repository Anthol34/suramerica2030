<?php
namespace Modules\Encomiendas;

class EncomiendasService {
    private $repository;

    public function __construct() {
        $this->repository = new EncomiendasRepository();
    }

    public function obtenerDatosIniciales($filtros, $pagina) {
        $limite = 10;
        $offset = ($pagina - 1) * $limite;
        $totalRegistros = $this->repository->contarTodas($filtros);
        
        $datos = [
            'rutas' => $this->repository->obtenerRutas(),
            'paquetes' => $this->repository->obtenerTodas($filtros, $offset, $limite),
            'totalPaginas' => ceil($totalRegistros / $limite),
            'paginaActual' => $pagina,
            'pagina' => $pagina,
            'totalRegistros' => $totalRegistros,
            'totalEnTransito' => $this->repository->contarEncomiendasEnTransito(),
            'directorioCorreos' => $this->repository->obtenerDirectorioCorreos()
        ];

        if (isset($filtros['tab']) && $filtros['tab'] == 'carga') {
            $inicio = $filtros['fecha_inicio'] ?? date('Y-m-01');
            $fin = $filtros['fecha_fin'] ?? date('Y-m-t');
            $datos['despachos'] = $this->repository->obtenerDespachosProgramados($inicio, $fin);
        }
        return $datos;
    }

    public function procesarGuardar($postData, $usuarioId, $sucursalId) {
        $modoMayorista = !empty($postData['modo_mayorista']);
        $pesoGeneralManual = !empty($postData['peso_general_manual']);

        if ($modoMayorista) {
            $cantidadFinal = intval($postData['cantidad_lote'] ?? 1);
            if ($cantidadFinal <= 0) $cantidadFinal = 1;
            $tipoFinal = !empty($postData['tipo_lote']) ? trim($postData['tipo_lote']) : 'Caja';
            $pesoFinal = floatval($postData['peso'] ?? 0);
            $contBase = trim($postData['contenido_lote'] ?? 'Carga Mayorista');
            $pesosDetalle = trim($postData['pesos_lote_str'] ?? '');
            $esPesoGeneralLote = !empty($postData['es_peso_general_lote']);

            if ($esPesoGeneralLote) {
                $prom = $cantidadFinal > 0 ? number_format($pesoFinal / $cantidadFinal, 2) : 0;
                $contenidoFinal = "Lote Mayorista ($cantidadFinal $tipoFinal" . ($cantidadFinal > 1 ? "s" : "") . "): $contBase [Peso General del Lote: {$pesoFinal}Kg (~{$prom}Kg c/u)]";
            } elseif (!empty($pesosDetalle)) {
                $contenidoFinal = "Lote Mayorista ($cantidadFinal $tipoFinal" . ($cantidadFinal > 1 ? "s" : "") . "): $contBase | Pesos(Kg): $pesosDetalle";
            } else {
                $contenidoFinal = "Lote Mayorista ($cantidadFinal $tipoFinal" . ($cantidadFinal > 1 ? "s" : "") . "): $contBase [Peso Total: {$pesoFinal}Kg]";
            }
        } else {
            $cantidades = $postData['cantidad'] ?? [1];
            $tipos = $postData['tipo_encomienda'] ?? ['Caja'];
            $pesos_piezas = $postData['peso_pieza'] ?? [0];
            $contenidos = $postData['contenido'] ?? ['General'];
            $modalidades_peso = $postData['modalidad_peso'] ?? [];
            
            $totalPiezas = 0;
            $pesoCalculado = 0;
            $tiposUnicos = [];
            $detallesContenido = [];

            if (is_array($cantidades)) {
                for($i = 0; $i < count($cantidades); $i++) {
                    $c = intval($cantidades[$i]); if($c <= 0) $c = 1;
                    $t = $tipos[$i] ?? 'Caja';
                    $p = floatval($pesos_piezas[$i] ?? 0);
                    $cont = $contenidos[$i] ?? '';
                    $modo = $modalidades_peso[$i] ?? 'unitario';
                    
                    $totalPiezas += $c;
                    if ($t !== 'Sobre') {
                        if ($modo === 'total') {
                            $pesoCalculado += $p;
                            $prom = $c > 0 ? number_format($p / $c, 2) : $p;
                            $detallesContenido[] = "$c $t(s) [Peso Total Grupo: {$p}Kg (~{$prom}Kg c/u)]: $cont";
                        } else {
                            $pesoCalculado += ($c * $p); 
                            $detallesContenido[] = "$c $t(s) [" . $p . "Kg c/u]: $cont";
                        }
                    } else {
                        $detallesContenido[] = "$c $t(s) [Fijo]: $cont";
                    }
                    if (!in_array($t, $tiposUnicos)) $tiposUnicos[] = $t;
                }
                $cantidadFinal = $totalPiezas;
                $tipoFinal = count($tiposUnicos) > 1 ? 'MIXTO' : ($tiposUnicos[0] ?? 'Caja');

                if ($pesoGeneralManual) {
                    $pesoFinal = floatval($postData['peso'] ?? 0);
                    $prom = $cantidadFinal > 0 ? number_format($pesoFinal / $cantidadFinal, 2) : 0;
                    $contenidoFinal = implode(" | ", $detallesContenido) . " [Peso General Global: {$pesoFinal}Kg (~{$prom}Kg c/u)]";
                } else {
                    $pesoFinal = $pesoCalculado;
                    $contenidoFinal = implode(" | ", $detallesContenido);
                }
            } else {
                $cantidadFinal = intval($postData['cantidad'] ?? 1);
                $tipoFinal = $postData['tipo_encomienda'] ?? 'Caja';
                $contenidoFinal = $postData['contenido'] ?? 'General';
                $pesoFinal = floatval($postData['peso'] ?? 0);
            }
        }

        $datosLimpios = [
            'cliente_id' => trim($postData['cliente_id']),
            'cliente_nombre' => trim($postData['cliente_nombre']),
            'cliente_apellido' => trim($postData['cliente_apellido']),
            'cliente_telefono' => trim($postData['cliente_telefono']),
            
            'destinatario_ci' => trim($postData['destinatario_ci']),
            'destinatario_nombre' => trim($postData['destinatario_nombre']),
            'destinatario_apellido' => trim($postData['destinatario_apellido']),
            'destinatario_telefono' => trim($postData['destinatario_telefono']),
            
            'ruta_id' => intval($postData['ruta_id']),
            'destino_final' => !empty($postData['destino_final']) ? mb_strtoupper(trim($postData['destino_final'])) : null,
            'metodo_pago' => $postData['metodo_pago'],
            'monto_efectivo' => floatval($postData['monto_efectivo'] ?? 0),
            'monto_transferencia' => floatval($postData['monto_transferencia'] ?? 0),
            'total_cobrado' => floatval($postData['monto_total']),
            'volumen' => floatval($postData['volumen'] ?? 0),
            
            'cantidad_final' => $cantidadFinal,
            'peso_final' => $pesoFinal,
            'tipo_final' => $tipoFinal,
            'contenido_final' => $contenidoFinal
        ];

        return $this->repository->registrarEncomiendaBD($datosLimpios, $usuarioId, $sucursalId);
    }
}
?>