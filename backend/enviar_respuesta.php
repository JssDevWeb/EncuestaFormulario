<?php
/**
 * Sistema de Encuestas de Satisfacción - Enviar Respuesta Anónima
 * Endpoint para recibir y almacenar respuestas de formularios de manera anónima
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Obtener datos JSON del cuerpo de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $formulario_id = intval($input['formulario_id'] ?? 0);
    $respuestas = $input['respuestas'] ?? [];
    
    // Validaciones básicas
    if ($formulario_id <= 0) {
        throw new Exception('ID de formulario inválido');
    }
    
    if (empty($respuestas) || !is_array($respuestas)) {
        throw new Exception('No se proporcionaron respuestas');
    }
      // Verificar que el formulario existe y está activo
    $stmt = $db->prepare("SELECT id, titulo FROM formularios WHERE id = ?");
    $stmt->execute([$formulario_id]);
    
    $formulario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$formulario) {
        throw new Exception('Formulario no encontrado o inactivo');
    }    // Obtener preguntas del formulario
    $stmt = $db->prepare(
        "SELECT id, texto_pregunta as texto, tipo_respuesta as tipo
         FROM preguntas 
         WHERE formulario_id = ? 
         ORDER BY id"
    );
    $stmt->execute([$formulario_id]);
    
    $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($preguntas)) {
        throw new Exception('El formulario no tiene preguntas');
    }
    
    // Crear mapeo de preguntas por ID
    $preguntasMap = [];
    foreach ($preguntas as $pregunta) {
        $preguntasMap[$pregunta['id']] = $pregunta;
    }
      // Validar respuestas (versión simplificada)
    $respuestasValidadas = [];
    $errores = [];
    
    foreach ($preguntas as $pregunta) {
        $pregunta_id = $pregunta['id'];
        $respuesta = $respuestas[$pregunta_id] ?? null;
        $tipo = $pregunta['tipo'];
        
        // Si no hay respuesta, continuar (todas las preguntas son opcionales por ahora)
        if (is_null($respuesta) || $respuesta === '' || 
            (is_array($respuesta) && empty($respuesta))) {
            continue;
        }
        
        // Validar según el tipo de pregunta (simplificado)
        switch ($tipo) {
            case 'texto':
            case 'textarea':
                if (!is_string($respuesta)) {
                    $errores[] = "Respuesta inválida para pregunta de texto: {$pregunta['texto']}";
                    break;
                }
                
                if (strlen($respuesta) > 5000) {
                    $errores[] = "Respuesta muy larga para pregunta: {$pregunta['texto']} (máximo 5000 caracteres)";
                    break;
                }
                
                $respuestasValidadas[$pregunta_id] = trim($respuesta);
                break;
                
            case 'escala':
                if (!is_numeric($respuesta)) {
                    $errores[] = "Respuesta inválida para pregunta de escala: {$pregunta['texto']}";
                    break;
                }
                
                $valor = intval($respuesta);
                
                // Validación básica para escala (1-10)
                if ($valor < 1 || $valor > 10) {
                    $errores[] = "Valor fuera de rango para pregunta: {$pregunta['texto']} (debe estar entre 1 y 10)";
                    break;
                }
                
                $respuestasValidadas[$pregunta_id] = $valor;
                break;
                
            case 'radio':
            case 'select':
                if (!is_string($respuesta)) {
                    $errores[] = "Respuesta inválida para pregunta de selección: {$pregunta['texto']}";
                    break;
                }
                
                $respuestasValidadas[$pregunta_id] = $respuesta;
                break;
                
            case 'checkbox':
                if (!is_array($respuesta)) {
                    $errores[] = "Respuesta inválida para pregunta de múltiple selección: {$pregunta['texto']}";
                    break;
                }
                
                $respuestasValidadas[$pregunta_id] = array_values($respuesta);
                break;
                
            default:
                // Tipo desconocido, aceptar como string
                $respuestasValidadas[$pregunta_id] = is_array($respuesta) ? $respuesta : trim((string)$respuesta);
                break;
        }
    }
    
    // Si hay errores de validación, devolver errores
    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Errores de validación',
            'details' => $errores
        ]);
        exit;
    }
      // Preparar datos para almacenar (COMPLETAMENTE ANÓNIMOS)
    $respuestasJson = json_encode($respuestasValidadas, JSON_UNESCAPED_UNICODE);
    
    // Insertar respuesta anónima
    $stmt = $db->prepare(
        "INSERT INTO respuestas_anonimas (formulario_id, datos_json, fecha_envio) 
         VALUES (?, ?, CURRENT_TIMESTAMP)"
    );
    $stmt->execute([$formulario_id, $respuestasJson]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('No se pudo guardar la respuesta');
    }
    
    // Log anónimo para auditoría (sin datos personales)
    error_log(sprintf(
        "Respuesta anónima guardada - Formulario ID: %d, Preguntas respondidas: %d",
        $formulario_id,
        count($respuestasValidadas)
    ));
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Respuesta registrada exitosamente',
        'data' => [
            'formulario_id' => $formulario_id,
            'preguntas_respondidas' => count($respuestasValidadas),
            'timestamp' => date('c')
        ]
    ]);
    
} catch (Exception $e) {
    // Log del error (sin datos sensibles)
    error_log(sprintf(
        "Error al procesar respuesta anónima - Error: %s, Formulario ID: %s",
        $e->getMessage(),
        $formulario_id ?? 'no definido'
    ));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
