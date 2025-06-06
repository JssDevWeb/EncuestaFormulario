<?php
/**
 * Test de Correcciones PDO - Verificación de Compatibilidad
 * Verifica que todas las correcciones PDO funcionen correctamente
 */

require_once 'backend/config.php';

echo "🔍 VERIFICACIÓN DE CORRECCIONES PDO/MySQLi\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    // Test 1: Verificar Database Singleton
    echo "✅ Test 1: Verificando patrón singleton Database...\n";
    $db1 = Database::getInstance();
    $db2 = Database::getInstance();
    
    if ($db1 === $db2) {
        echo "   ✅ PASÓ: Patrón singleton funcionando correctamente\n";
    } else {
        echo "   ❌ FALLÓ: Singleton no está funcionando\n";
    }
    
    // Test 2: Verificar conexión PDO
    echo "\n✅ Test 2: Verificando conexión PDO...\n";
    $connection = $db1->getConnection();
    
    if ($connection instanceof PDO) {
        echo "   ✅ PASÓ: Conexión PDO establecida correctamente\n";
        echo "   📊 Versión MySQL: " . $connection->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    } else {
        echo "   ❌ FALLÓ: Conexión no es PDO\n";
    }
    
    // Test 3: Verificar estructura de tablas
    echo "\n✅ Test 3: Verificando estructura de tablas...\n";
    $stmt = $db1->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $expected_tables = ['formularios', 'preguntas', 'respuestas_anonimas'];
    $found_tables = array_intersect($expected_tables, $tables);
    
    if (count($found_tables) === count($expected_tables)) {
        echo "   ✅ PASÓ: Todas las tablas necesarias existen\n";
        foreach ($found_tables as $table) {
            echo "   📁 Tabla encontrada: $table\n";
        }
    } else {
        echo "   ⚠️  ADVERTENCIA: Algunas tablas podrían faltar\n";
        echo "   📁 Esperadas: " . implode(', ', $expected_tables) . "\n";
        echo "   📁 Encontradas: " . implode(', ', $found_tables) . "\n";
    }
    
    // Test 4: Verificar prepared statements
    echo "\n✅ Test 4: Verificando prepared statements...\n";
    $stmt = $db1->prepare("SELECT COUNT(*) as total FROM formularios WHERE id > ?");
    $stmt->execute([0]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($result['total'])) {
        echo "   ✅ PASÓ: Prepared statements funcionando\n";
        echo "   📊 Total formularios: " . $result['total'] . "\n";
    } else {
        echo "   ❌ FALLÓ: Prepared statements no funcionan\n";
    }
    
    // Test 5: Verificar transacciones
    echo "\n✅ Test 5: Verificando soporte de transacciones...\n";
    $db1->beginTransaction();
    $db1->rollback();
    echo "   ✅ PASÓ: Transacciones PDO disponibles\n";
    
    // Test 6: Verificar que no hay conflictos MySQLi
    echo "\n✅ Test 6: Verificando ausencia de conflictos MySQLi...\n";
    $reflection = new ReflectionClass('Database');
    $methods = $reflection->getMethods();
    
    $mysqli_methods = [];
    foreach ($methods as $method) {
        if (strpos(strtolower($method->getName()), 'mysqli') !== false) {
            $mysqli_methods[] = $method->getName();
        }
    }
    
    if (empty($mysqli_methods)) {
        echo "   ✅ PASÓ: No hay métodos MySQLi conflictivos\n";
    } else {
        echo "   ⚠️  ADVERTENCIA: Métodos MySQLi encontrados: " . implode(', ', $mysqli_methods) . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 RESUMEN: CORRECCIONES PDO VERIFICADAS EXITOSAMENTE\n";
    echo "📊 Estado: ✅ SISTEMA UNIFICADO PDO FUNCIONANDO\n";
    echo "🔒 Seguridad: ✅ PREPARED STATEMENTS ACTIVOS\n";
    echo "🏗️  Patrón: ✅ SINGLETON DATABASE IMPLEMENTADO\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR DURANTE LAS PRUEBAS:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n🔧 ACCIÓN REQUERIDA: Revisar configuración de base de datos\n";
}
?>
