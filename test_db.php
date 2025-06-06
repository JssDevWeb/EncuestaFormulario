<?php
/**
 * Test simple de conexión y consulta BD
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Iniciando test de BD...\n";

try {
    require_once 'backend/config.php';
    echo "✅ Config cargado\n";
    
    $db = Database::getInstance();
    echo "✅ Database instance obtenida\n";
    
    // Test consulta directa
    $conn = $db->getConnection();
    echo "✅ Conexión obtenida\n";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM formularios");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Consulta directa ejecutada: " . $result['total'] . " formularios\n";
    
    // Test método query
    echo "Testing método query()...\n";
    $formularios = $db->query("SELECT id, titulo FROM formularios LIMIT 3");
    echo "Tipo devuelto: " . gettype($formularios) . "\n";
    
    if (is_array($formularios)) {
        echo "✅ query() devolvió array con " . count($formularios) . " elementos\n";
        foreach ($formularios as $form) {
            echo "- ID: " . $form['id'] . ", Título: " . $form['titulo'] . "\n";
        }
    } else {
        echo "❌ query() NO devolvió array. Valor: " . var_export($formularios, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "Test terminado.\n";
?>
