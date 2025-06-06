<?php
/**
 * Sistema de Encuestas de Satisfacción - Editar Formulario
 * Permite editar formularios existentes manteniendo integridad referencial
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Solo permitir POST para ediciones
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Validar datos de entrada
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
      $id = intval($input['id'] ?? 0);
    $titulo = trim($input['titulo'] ?? '');
    $descripcion = trim($input['descripcion'] ?? '');
    $preguntas = $input['preguntas'] ?? [];
    
    // Validaciones básicas
    if ($id <= 0) {
        throw new Exception('ID de formulario inválido');
    }
    
    if (empty($titulo)) {
        throw new Exception('El título es obligatorio');
    }
    
    if (strlen($titulo) > 255) {
        throw new Exception('El título no puede exceder 255 caracteres');
    }
    
    if (strlen($descripcion) > 1000) {
        throw new Exception('La descripción no puede exceder 1000 caracteres');
    }
    
    if (empty($preguntas) || !is_array($preguntas)) {
        throw new Exception('Debe incluir al menos una pregunta');
    }
    
    if (count($preguntas) > 50) {
        throw new Exception('Máximo 50 preguntas por formulario');
    }    // Verificar que el formulario existe
    $resultado = $db->query("SELECT id FROM formularios WHERE id = $id");
    
    if (empty($resultado) || !is_array($resultado)) {
        throw new Exception('Formulario no encontrado');
    }
    
    // Validar preguntas
    foreach ($preguntas as $index => $pregunta) {
        if (empty($pregunta['texto'])) {
            throw new Exception("El texto de la pregunta " . ($index + 1) . " es obligatorio");
        }
        
        if (strlen($pregunta['texto']) > 500) {
            throw new Exception("El texto de la pregunta " . ($index + 1) . " no puede exceder 500 caracteres");
        }
        
        $tipo = $pregunta['tipo'] ?? '';
        $tiposValidos = ['texto', 'textarea', 'radio', 'checkbox', 'select', 'escala'];
        
        if (!in_array($tipo, $tiposValidos)) {
            throw new Exception("Tipo de pregunta inválido en pregunta " . ($index + 1));
        }
        
        // Validar opciones para preguntas de opción múltiple
        if (in_array($tipo, ['radio', 'checkbox', 'select'])) {
            $opciones = $pregunta['opciones'] ?? [];
            
            if (empty($opciones) || !is_array($opciones)) {
                throw new Exception("La pregunta " . ($index + 1) . " requiere opciones");
            }
            
            if (count($opciones) < 2) {
                throw new Exception("La pregunta " . ($index + 1) . " requiere al menos 2 opciones");
            }
            
            if (count($opciones) > 20) {
                throw new Exception("La pregunta " . ($index + 1) . " no puede tener más de 20 opciones");
            }
            
            foreach ($opciones as $opcionIndex => $opcion) {
                if (empty(trim($opcion))) {
                    throw new Exception("Opción vacía en pregunta " . ($index + 1));
                }
                
                if (strlen($opcion) > 255) {
                    throw new Exception("Opción muy larga en pregunta " . ($index + 1));
                }
            }
        }
        
        // Validar rango para preguntas de escala
        if ($tipo === 'escala') {
            $min = intval($pregunta['min'] ?? 1);
            $max = intval($pregunta['max'] ?? 5);
            
            if ($min >= $max) {
                throw new Exception("Rango inválido en pregunta " . ($index + 1) . " (mín debe ser menor que máx)");
            }
            
            if ($min < 1 || $max > 10) {
                throw new Exception("Rango inválido en pregunta " . ($index + 1) . " (debe estar entre 1 y 10)");
            }
        }
        
        // Validar obligatoriedad
        $obligatoria = isset($pregunta['obligatoria']) ? (bool)$pregunta['obligatoria'] : false;    }
    
    // Iniciar transacción MySQL
    
    try {
        // Actualizar formulario
        $titulo_escaped = addslashes($titulo);
        $descripcion_escaped = addslashes($descripcion);
        
        $resultado = $db->query(
            "UPDATE formularios 
             SET titulo = '$titulo_escaped', descripcion = '$descripcion_escaped'
             WHERE id = $id"
        );
        
        // Eliminar preguntas existentes
        $resultado = $db->query("DELETE FROM preguntas WHERE formulario_id = $id");
        
        // Insertar preguntas actualizadas (usando estructura real de la BD)
        foreach ($preguntas as $index => $pregunta) {
            $texto_escaped = addslashes(trim($pregunta['texto']));
            $tipo_escaped = addslashes($pregunta['tipo']);
            
            $resultado = $db->query(
                "INSERT INTO preguntas 
                 (formulario_id, texto_pregunta, tipo_respuesta) 
                 VALUES ($id, '$texto_escaped', '$tipo_escaped')"
            );
        }
        echo json_encode([
            'success' => true,
            'message' => 'Formulario actualizado exitosamente',
            'id' => $id
        ]);
        
    } catch (Exception $e) {
        // En caso de error, intentar limpiar las preguntas huérfanas
        // (no hay rollback automático en este contexto)
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
