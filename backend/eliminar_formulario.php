<?php
/**
 * Sistema de Encuestas de Satisfacción - Eliminar Formulario
 * Elimina formularios y todas sus respuestas asociadas de manera segura
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Solo permitir GET con parámetro confirmar=si para eliminaciones desde el admin panel
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Obtener ID del formulario y confirmación
    $id = intval($_GET['id'] ?? 0);
    $confirmar = $_GET['confirmar'] ?? '';
    
    if ($id <= 0) {
        throw new Exception('ID de formulario inválido');
    }
    
    if ($confirmar !== 'si') {
        throw new Exception('Eliminación no confirmada');
    }
      // Verificar que el formulario existe
    $stmt = $db->prepare("SELECT id, titulo FROM formularios WHERE id = ?");
    $stmt->execute([$id]);
    $formulario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$formulario) {
        throw new Exception('Formulario no encontrado');
    }
    
    // Obtener estadísticas antes de eliminar
    $stmt = $db->prepare("SELECT COUNT(*) as total_respuestas 
         FROM respuestas_anonimas 
         WHERE formulario_id = ?");
    $stmt->execute([$id]);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalRespuestas = $estadisticas ? $estadisticas['total_respuestas'] : 0;
      // Iniciar transacción para eliminar todo de manera atómica
    $db->beginTransaction();
      try {
        // 1. Eliminar respuestas anónimas
        $stmt = $db->prepare("DELETE FROM respuestas_anonimas WHERE formulario_id = ?");
        $stmt->execute([$id]);
        $respuestasEliminadas = $stmt->rowCount();
        
        // 2. Eliminar preguntas
        $stmt = $db->prepare("DELETE FROM preguntas WHERE formulario_id = ?");
        $stmt->execute([$id]);
        $preguntasEliminadas = $stmt->rowCount();
        
        // 3. Eliminar el formulario
        $stmt = $db->prepare("DELETE FROM formularios WHERE id = ?");
        $stmt->execute([$id]);
        $formulariosEliminados = $stmt->rowCount();
        
        if ($formulariosEliminados === 0) {
            throw new Exception('No se pudo eliminar el formulario');
        }
        
        // Confirmar transacción
        $db->commit();
          // Redirigir al panel administrativo con mensaje de éxito
        header("Location: ../frontend/index_admin.php?mensaje=" . urlencode("Formulario '{$formulario['titulo']}' eliminado exitosamente"));
        exit;
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->rollback();
        throw new Exception('Error eliminando formulario: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Redirigir al panel administrativo con mensaje de error
    header("Location: ../frontend/index_admin.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>
