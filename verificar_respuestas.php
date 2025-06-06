<?php
/**
 * Verificar que las respuestas se están guardando correctamente
 */

require_once 'backend/config.php';

echo "=== VERIFICACIÓN DE RESPUESTAS GUARDADAS ===\n";

try {
    $db = getDB();
    
    // Contar respuestas totales
    $total = $db->query("SELECT COUNT(*) as total FROM respuestas_anonimas");
    echo "📊 Total de respuestas en la base de datos: " . $total[0]['total'] . "\n\n";    // Mostrar las últimas 3 respuestas
    $respuestas = $db->query("
        SELECT 
            id, 
            formulario_id, 
            fecha_envio,
            datos_json
        FROM respuestas_anonimas 
        ORDER BY fecha_envio DESC 
        LIMIT 3
    ");
      echo "📋 Últimas 3 respuestas:\n";
    foreach ($respuestas as $resp) {
        echo "  ID: {$resp['id']}\n";
        echo "  Formulario: {$resp['formulario_id']}\n";
        echo "  Fecha: {$resp['fecha_envio']}\n";
        echo "  Datos (preview): " . substr($resp['datos_json'], 0, 80) . "...\n";
        echo "  ────────────────────────\n";
    }
    
    // Verificar que la última respuesta contiene nuestros datos de prueba
    $ultima_respuesta = $db->query("
        SELECT datos_json 
        FROM respuestas_anonimas 
        ORDER BY fecha_envio DESC 
        LIMIT 1
    ");
    
    if ($ultima_respuesta) {
        $datos = json_decode($ultima_respuesta[0]['datos_json'], true);
        echo "✅ Datos de la última respuesta (completos):\n";
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Verificar que contiene nuestros datos de prueba
        if (isset($datos['13']) && $datos['13'] === '8') {
            echo "✅ Respuesta de escala correcta (pregunta 13): " . $datos['13'] . "\n";
        }
        if (isset($datos['14']) && strpos($datos['14'], 'explicaciones') !== false) {
            echo "✅ Respuesta de texto correcta (pregunta 14): " . $datos['14'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";
?>
