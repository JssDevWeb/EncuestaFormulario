<?php
/**
 * Sistema de Encuestas de Satisfacción - Eliminar Respuesta Anónima
 * Permite eliminar respuestas individuales manteniendo el anonimato
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Solo permitir DELETE y POST para eliminaciones
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Obtener parámetros
    $respuesta_id = 0;
    $formulario_id = 0;
    
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Para DELETE, los parámetros vienen en la URL
        $respuesta_id = intval($_GET['respuesta_id'] ?? 0);
        $formulario_id = intval($_GET['formulario_id'] ?? 0);
    } else {
        // Para POST, los parámetros vienen en el body JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $respuesta_id = intval($input['respuesta_id'] ?? 0);
        $formulario_id = intval($input['formulario_id'] ?? 0);
    }
    
    // Validaciones básicas
    if ($respuesta_id <= 0) {
        throw new Exception('ID de respuesta inválido');
    }
    
    if ($formulario_id <= 0) {
        throw new Exception('ID de formulario inválido');
    }
      // Verificar que la respuesta existe y pertenece al formulario especificado
    $stmt = $db->prepare(
        "SELECT r.id, r.formulario_id, r.fecha_envio, f.titulo
         FROM respuestas_anonimas r
         INNER JOIN formularios f ON r.formulario_id = f.id
         WHERE r.id = ? AND r.formulario_id = ?"
    );
    $stmt->execute([$respuesta_id, $formulario_id]);
    
    $respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$respuesta) {
        throw new Exception('Respuesta no encontrada o no pertenece al formulario especificado');
    }
      // Eliminar la respuesta
    $stmt = $db->prepare(
        "DELETE FROM respuestas_anonimas WHERE id = ? AND formulario_id = ?"
    );
    $stmt->execute([$respuesta_id, $formulario_id]);
    
    $respuestasEliminadas = $stmt->rowCount();
    
    if ($respuestasEliminadas === 0) {
        throw new Exception('No se pudo eliminar la respuesta');
    }
    
    // Log de auditoría (sin datos personales, solo metadatos)
    error_log(sprintf(
        "Respuesta anónima eliminada - ID: %d, Formulario: %s (%d), Fecha: %s",
        $respuesta_id,
        $respuesta['titulo'],
        $formulario_id,
        $respuesta['fecha_envio']
    ));
      // Obtener estadísticas actualizadas del formulario
    $stmt = $db->prepare(
        "SELECT COUNT(*) as total_respuestas_restantes
         FROM respuestas_anonimas 
         WHERE formulario_id = ?"
    );
    $stmt->execute([$formulario_id]);
    
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Respuesta eliminada exitosamente',
        'data' => [
            'respuesta_id' => $respuesta_id,
            'formulario_id' => $formulario_id,
            'formulario_titulo' => $respuesta['titulo'],
            'fecha_respuesta' => $respuesta['fecha_envio'],
            'total_respuestas_restantes' => intval($estadisticas['total_respuestas_restantes'] ?? 0)
        ]
    ]);
    
} catch (Exception $e) {
    // Log del error (sin datos sensibles)
    error_log(sprintf(        "Error al eliminar respuesta anónima - Error: %s, Respuesta ID: %s, Formulario ID: %s",
        $e->getMessage(),
        $respuesta_id ?? 'no definido',
        $formulario_id ?? 'no definido'
    ));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
