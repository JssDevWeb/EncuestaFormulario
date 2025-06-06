<?php
/**
 * Script de prueba para verificar el funcionamiento del sistema
 * Sin cortes ni truncamientos
 */

echo "=== PRUEBA DEL SISTEMA DE ENCUESTAS ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Verificar conexión a base de datos
try {
    require_once 'backend/config.php';
    echo "✓ Archivo config.php cargado correctamente\n";
    
    $db = getDB();
    echo "✓ Conexión a base de datos establecida\n";
    
    // Verificar tablas
    $tables = ['formularios', 'preguntas', 'respuestas_anonimas'];
    foreach ($tables as $table) {
        $result = $db->query("SELECT COUNT(*) as count FROM $table");
        if ($result && isset($result[0]['count'])) {
            echo "✓ Tabla '$table' existe y tiene {$result[0]['count']} registros\n";
        } else {
            echo "✗ Error al acceder a tabla '$table'\n";
        }
    }
    
    echo "\n=== ESTRUCTURA DE DATOS ===\n";
    
    // Mostrar formularios disponibles
    $formularios = $db->query("SELECT id, titulo, descripcion FROM formularios LIMIT 5");
    if ($formularios) {
        echo "Formularios disponibles:\n";
        foreach ($formularios as $form) {
            echo "  - ID: {$form['id']}, Título: {$form['titulo']}\n";
            
            // Mostrar preguntas de este formulario
            $preguntas = $db->query("SELECT id, texto_pregunta, tipo_respuesta FROM preguntas WHERE formulario_id = ? LIMIT 3", [$form['id']]);
            if ($preguntas) {
                foreach ($preguntas as $preg) {
                    echo "    * Pregunta {$preg['id']}: {$preg['texto_pregunta']} (Tipo: {$preg['tipo_respuesta']})\n";
                }
            }
        }
    }
    
    echo "\n=== PRUEBA DE ENVÍO DE DATOS ===\n";
    
    // Simular envío de respuesta
    $formulario_id = 1;
    $test_data = [
        'formulario_id' => $formulario_id,
        'respuestas' => [
            '13' => 'Respuesta de prueba 1',
            '14' => 'Respuesta de prueba 2'
        ]
    ];
    
    echo "Datos de prueba preparados:\n";
    echo json_encode($test_data, JSON_PRETTY_PRINT) . "\n";
    
    // Verificar que el endpoint existe
    $endpoint_file = 'backend/enviar_respuesta.php';
    if (file_exists($endpoint_file)) {
        echo "✓ Endpoint '$endpoint_file' existe\n";
        
        // Simular POST request internamente
        $_POST = json_decode(json_encode($test_data), true);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        echo "Simulando envío de datos...\n";
        
        ob_start();
        include $endpoint_file;
        $output = ob_get_clean();
        
        echo "Respuesta del endpoint:\n";
        echo $output . "\n";
        
    } else {
        echo "✗ Endpoint '$endpoint_file' no encontrado\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>
