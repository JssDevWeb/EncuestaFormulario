<?php
/**
 * Dashboard API - Datos optimizados para visualización
 * Proporciona datos estructurados para Charts.js
 */

// Configurar manejo de errores para API
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar cualquier output previo
ob_clean();

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getInstance();
    
    $formulario_id = intval($_GET['formulario_id'] ?? 0);
    
    if ($formulario_id <= 0) {
        throw new Exception('ID de formulario inválido');
    }
      // Obtener información básica del formulario
    $stmt = $db->prepare("SELECT f.id, f.titulo, f.descripcion, 
                f.fecha_creacion,
                COUNT(r.id) as total_respuestas
         FROM formularios f
         LEFT JOIN respuestas_anonimas r ON f.id = r.formulario_id
         WHERE f.id = ?
         GROUP BY f.id, f.titulo, f.descripcion, f.fecha_creacion");
    $stmt->execute([$formulario_id]);
    $formulario = $stmt->fetch(PDO::FETCH_ASSOC);
      $formulario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$formulario) {
        throw new Exception('Formulario no encontrado');
    }
    
    // Obtener preguntas del formulario
    $stmt = $db->prepare("SELECT id, texto_pregunta, tipo_respuesta
         FROM preguntas 
         WHERE formulario_id = ? 
         ORDER BY id");
    $stmt->execute([$formulario_id]);
    $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($preguntas)) {
        $preguntas = [];
    }
      // Obtener todas las respuestas
    $stmt = $db->prepare("SELECT datos_json, fecha_envio 
         FROM respuestas_anonimas 
         WHERE formulario_id = ? 
         ORDER BY fecha_envio");
    $stmt->execute([$formulario_id]);
    $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar datos para dashboard
    $dashboard_data = [
        'formulario' => $formulario,
        'estadisticas_generales' => [
            'total_respuestas' => (int)$formulario['total_respuestas'],
            'total_preguntas' => count($preguntas),
            'promedio_satisfaccion' => 0,
            'tasa_completitud' => 0
        ],
        'preguntas' => [],
        'timeline' => [],
        'distribuciones' => [],
        'tipos_pregunta' => [
            'escala' => 0,
            'texto' => 0,
            'seleccion' => 0
        ]
    ];
    
    // Procesar cada pregunta
    foreach ($preguntas as $pregunta) {
        $pregunta_id = $pregunta['id'];
        $tipo = $pregunta['tipo_respuesta'];
        
        // Contar tipos de pregunta
        $dashboard_data['tipos_pregunta'][$tipo]++;
        
        // Extraer respuestas para esta pregunta
        $respuestas_pregunta = [];
        foreach ($respuestas as $respuesta) {
            $datos = json_decode($respuesta['datos_json'], true);
            if ($datos && isset($datos[$pregunta_id])) {
                $respuestas_pregunta[] = [
                    'valor' => $datos[$pregunta_id],
                    'fecha' => $respuesta['fecha_envio']
                ];
            }
        }
        
        $total_respuestas_pregunta = count($respuestas_pregunta);
        
        // Estadísticas por tipo de pregunta
        $estadistica_pregunta = [
            'id' => $pregunta_id,
            'texto' => $pregunta['texto_pregunta'],
            'tipo' => $tipo,
            'total_respuestas' => $total_respuestas_pregunta,
            'datos' => []
        ];
        
        switch ($tipo) {
            case 'escala':
                $valores = array_map(function($r) { 
                    return is_numeric($r['valor']) ? floatval($r['valor']) : null; 
                }, $respuestas_pregunta);
                $valores = array_filter($valores, function($v) { return $v !== null; });
                
                if (!empty($valores)) {
                    $promedio = array_sum($valores) / count($valores);
                    $dashboard_data['estadisticas_generales']['promedio_satisfaccion'] += $promedio;
                    
                    // Distribución por valor
                    $distribucion = array_count_values($valores);
                    ksort($distribucion);
                    
                    $estadistica_pregunta['datos'] = [
                        'promedio' => round($promedio, 2),
                        'min' => min($valores),
                        'max' => max($valores),
                        'distribucion' => array_map(function($valor, $count) use ($valores) {
                            return [
                                'valor' => $valor,
                                'count' => $count,
                                'porcentaje' => round($count / count($valores) * 100, 1)
                            ];
                        }, array_keys($distribucion), array_values($distribucion))
                    ];
                }
                break;
                
            case 'seleccion':
                $opciones = array_map(function($r) { return $r['valor']; }, $respuestas_pregunta);
                $conteos = array_count_values($opciones);
                arsort($conteos);
                
                $estadistica_pregunta['datos'] = array_map(function($opcion, $count) use ($total_respuestas_pregunta) {
                    return [
                        'opcion' => $opcion,
                        'count' => $count,
                        'porcentaje' => $total_respuestas_pregunta > 0 ? 
                            round($count / $total_respuestas_pregunta * 100, 1) : 0
                    ];
                }, array_keys($conteos), array_values($conteos));
                break;
                
            case 'texto':
                $longitudes = array_map(function($r) { 
                    return strlen($r['valor']); 
                }, $respuestas_pregunta);
                
                $estadistica_pregunta['datos'] = [
                    'total_caracteres' => array_sum($longitudes),
                    'promedio_longitud' => !empty($longitudes) ? round(array_sum($longitudes) / count($longitudes), 1) : 0,
                    'respuestas_muestra' => array_slice(array_map(function($r) { 
                        return substr($r['valor'], 0, 100) . (strlen($r['valor']) > 100 ? '...' : ''); 
                    }, $respuestas_pregunta), 0, 3)
                ];
                break;
        }
        
        $dashboard_data['preguntas'][] = $estadistica_pregunta;
    }
    
    // Calcular promedio general de satisfacción
    $preguntas_escala = array_filter($preguntas, function($p) { return $p['tipo_respuesta'] === 'escala'; });
    if (count($preguntas_escala) > 0) {
        $dashboard_data['estadisticas_generales']['promedio_satisfaccion'] = 
            round($dashboard_data['estadisticas_generales']['promedio_satisfaccion'] / count($preguntas_escala), 2);
    }
    
    // Calcular tasa de completitud (simulada)
    if ($dashboard_data['estadisticas_generales']['total_respuestas'] > 0) {
        $dashboard_data['estadisticas_generales']['tasa_completitud'] = 95; // Simulado
    }
    
    // Generar datos de timeline (agrupados por día)
    $timeline = [];
    $fechas_respuestas = array_map(function($r) {
        return date('Y-m-d', strtotime($r['fecha_envio']));
    }, $respuestas);
    
    $conteos_por_fecha = array_count_values($fechas_respuestas);
    ksort($conteos_por_fecha);
    
    foreach ($conteos_por_fecha as $fecha => $count) {
        $timeline[] = [
            'fecha' => $fecha,
            'count' => $count,
            'fecha_formateada' => date('d M', strtotime($fecha))
        ];
    }
    
    $dashboard_data['timeline'] = $timeline;
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $dashboard_data
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
