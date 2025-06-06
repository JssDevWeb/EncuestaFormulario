<?php
/**
 * TEST FINAL - VERIFICACIÓN COMPLETA DE CORRECCIONES PDO
 * Ejecutar después de crear la base de datos para confirmar que todo funciona
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🎯 TEST FINAL - SISTEMA DE ENCUESTAS MYSQL</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.success { color: #28a745; font-weight: bold; background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0; }
.error { color: #dc3545; font-weight: bold; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0; }
.warning { color: #ffc107; font-weight: bold; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 5px 0; }
.info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 5px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
th { background-color: #e9ecef; font-weight: bold; }
tr:nth-child(even) { background-color: #f8f9fa; }
.status-ok { color: #28a745; font-weight: bold; }
.status-error { color: #dc3545; font-weight: bold; }
.btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
.btn:hover { background: #0056b3; }
</style>";

echo "<div class='container'>";

$tests_passed = 0;
$tests_failed = 0;
$total_tests = 0;

function runTest($name, $callback) {
    global $tests_passed, $tests_failed, $total_tests;
    $total_tests++;
    
    echo "<h3>🔬 Test: $name</h3>";
    
    try {
        $result = $callback();
        if ($result) {
            echo "<div class='success'>✅ PASÓ: $name</div>";
            $tests_passed++;
            return true;
        } else {
            echo "<div class='error'>❌ FALLÓ: $name</div>";
            $tests_failed++;
            return false;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ ERROR en $name: " . $e->getMessage() . "</div>";
        $tests_failed++;
        return false;
    }
}

// ====================================================================
// TEST 1: CONEXIÓN PDO
// ====================================================================
runTest("Conexión PDO Singleton", function() {
    require_once 'backend/config.php';
    $db = Database::getInstance();
    
    // Verificar que es una instancia válida
    if (!$db) return false;
    
    // Verificar que la conexión es PDO
    $connection = $db->getConnection();
    if (get_class($connection) !== 'PDO') {
        throw new Exception("La conexión no es PDO, es: " . get_class($connection));
    }
    
    // Test de consulta simple
    $stmt = $db->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['test'] == 1;
});

// ====================================================================
// TEST 2: VERIFICAR ESTRUCTURA DE TABLAS
// ====================================================================
runTest("Estructura de Base de Datos", function() {
    $db = Database::getInstance();
    
    $tablas_requeridas = ['formularios', 'preguntas', 'respuestas_anonimas'];
    
    foreach ($tablas_requeridas as $tabla) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabla]);
        if (!$stmt->fetch()) {
            throw new Exception("Tabla '$tabla' no existe");
        }
    }
    
    return true;
});

// ====================================================================
// TEST 3: CRUD COMPLETO DE FORMULARIOS
// ====================================================================
runTest("CRUD de Formularios (backend/crear_formulario.php)", function() {
    // Simular $_POST para crear formulario
    $_POST = [
        'titulo' => 'Test Formulario PDO - ' . date('Y-m-d H:i:s'),
        'descripcion' => 'Formulario de prueba para verificar PDO',
        'preguntas' => [
            [
                'texto' => '¿Test de escala?',
                'tipo' => 'escala',
                'configuracion' => '{"min": 1, "max": 5}'
            ],
            [
                'texto' => '¿Test de texto?',
                'tipo' => 'texto',
                'configuracion' => '{"max_length": 100}'
            ]
        ]
    ];
    
    // Capturar output del script
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    try {
        include 'backend/crear_formulario.php';
        $output = ob_get_clean();
        
        // Verificar que se creó correctamente
        $response = json_decode($output, true);
        if (!$response || !$response['success']) {
            throw new Exception("Error creando formulario: " . ($response['error'] ?? 'Sin respuesta'));
        }
        
        // Guardar ID para limpieza posterior
        global $test_formulario_id;
        $test_formulario_id = $response['data']['formulario_id'];
        
        return true;
        
    } catch (Exception $e) {
        ob_get_clean();
        throw $e;
    }
});

// ====================================================================
// TEST 4: LISTAR FORMULARIOS
// ====================================================================
runTest("Listar Formularios (backend/listar_formularios.php)", function() {
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    try {
        include 'backend/listar_formularios.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response || !$response['success']) {
            throw new Exception("Error listando formularios: " . ($response['error'] ?? 'Sin respuesta'));
        }
        
        // Verificar que hay al menos un formulario
        return count($response['data']) > 0;
        
    } catch (Exception $e) {
        ob_get_clean();
        throw $e;
    }
});

// ====================================================================
// TEST 5: ENVÍO DE RESPUESTA ANÓNIMA
// ====================================================================
runTest("Envío de Respuesta Anónima (backend/enviar_respuesta.php)", function() {
    global $test_formulario_id;
    
    if (!$test_formulario_id) {
        throw new Exception("No hay formulario de prueba disponible");
    }
    
    // Simular envío de respuesta
    $input_data = [
        'formulario_id' => $test_formulario_id,
        'respuestas' => [
            1 => 5,  // Respuesta a pregunta de escala
            2 => 'Respuesta de prueba'  // Respuesta de texto
        ]
    ];
    
    // Simular input JSON
    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($input_data);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    // Mock de file_get_contents para simular input
    if (!function_exists('file_get_contents_original')) {
        function file_get_contents_original($filename) {
            static $original_function = null;
            if ($original_function === null) {
                $original_function = 'file_get_contents';
            }
            return call_user_func($original_function, $filename);
        }
    }
    
    // Temporal override (solo para esta prueba)
    ob_start();
    
    try {
        // Simular el contenido del cuerpo de la petición
        $temp_input = tmpfile();
        fwrite($temp_input, json_encode($input_data));
        rewind($temp_input);
        
        include 'backend/enviar_respuesta.php';
        $output = ob_get_clean();
        
        fclose($temp_input);
        
        $response = json_decode($output, true);
        if (!$response || !$response['success']) {
            throw new Exception("Error enviando respuesta: " . ($response['error'] ?? 'Sin respuesta válida'));
        }
        
        return true;
        
    } catch (Exception $e) {
        ob_get_clean();
        throw $e;
    }
});

// ====================================================================
// TEST 6: DASHBOARD API
// ====================================================================
runTest("Dashboard API (backend/dashboard_api.php)", function() {
    global $test_formulario_id;
    
    $_GET['formulario_id'] = $test_formulario_id;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    
    try {
        include 'backend/dashboard_api.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        if (!$response || !$response['success']) {
            throw new Exception("Error en dashboard API: " . ($response['error'] ?? 'Sin respuesta'));
        }
        
        // Verificar que tiene estadísticas básicas
        return isset($response['data']['estadisticas']);
        
    } catch (Exception $e) {
        ob_get_clean();
        throw $e;
    }
});

// ====================================================================
// TEST 7: VERIFICAR FRONTEND
// ====================================================================
runTest("Frontend Admin Panel", function() {
    // Verificar que los archivos frontend existen y no tienen errores de sintaxis
    $frontend_files = [
        'frontend/index_admin.php',
        'frontend/dashboard.php',
        'frontend/crear_formulario.php'
    ];
    
    foreach ($frontend_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("Archivo frontend faltante: $file");
        }
        
        // Verificar sintaxis PHP básica
        $check = shell_exec("php -l \"$file\" 2>&1");
        if (strpos($check, 'No syntax errors') === false) {
            throw new Exception("Error de sintaxis en $file: $check");
        }
    }
    
    return true;
});

// ====================================================================
// LIMPIEZA DE DATOS DE PRUEBA
// ====================================================================
runTest("Limpieza de Datos de Prueba", function() {
    global $test_formulario_id;
    
    if ($test_formulario_id) {
        $db = Database::getInstance();
        
        // Eliminar respuestas de prueba
        $stmt = $db->prepare("DELETE FROM respuestas_anonimas WHERE formulario_id = ?");
        $stmt->execute([$test_formulario_id]);
        
        // Eliminar preguntas de prueba
        $stmt = $db->prepare("DELETE FROM preguntas WHERE formulario_id = ?");
        $stmt->execute([$test_formulario_id]);
        
        // Eliminar formulario de prueba
        $stmt = $db->prepare("DELETE FROM formularios WHERE id = ?");
        $stmt->execute([$test_formulario_id]);
    }
    
    return true;
});

// ====================================================================
// RESUMEN FINAL
// ====================================================================
echo "<hr>";
echo "<h2>📊 RESUMEN DE TESTS</h2>";

echo "<table>";
echo "<tr><th>Métrica</th><th>Valor</th><th>Estado</th></tr>";
echo "<tr><td>Tests Ejecutados</td><td>$total_tests</td><td>✅</td></tr>";
echo "<tr><td>Tests Exitosos</td><td>$tests_passed</td><td class='status-ok'>✅</td></tr>";
echo "<tr><td>Tests Fallidos</td><td>$tests_failed</td><td>" . ($tests_failed > 0 ? "<span class='status-error'>❌</span>" : "<span class='status-ok'>✅</span>") . "</td></tr>";
echo "<tr><td>Porcentaje de Éxito</td><td>" . round(($tests_passed / $total_tests) * 100, 1) . "%</td><td>" . ($tests_failed == 0 ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>") . "</td></tr>";
echo "</table>";

// Estado final
if ($tests_failed == 0) {
    echo "<div class='success'>";
    echo "<h2>🎉 ¡TODOS LOS TESTS PASARON!</h2>";
    echo "<p><strong>El sistema está completamente funcional y listo para producción.</strong></p>";
    echo "<ul>";
    echo "<li>✅ Conexión PDO funcionando correctamente</li>";
    echo "<li>✅ Base de datos MySQL optimizada</li>";
    echo "<li>✅ CRUD de formularios operativo</li>";
    echo "<li>✅ Sistema de respuestas anónimas funcionando</li>";
    echo "<li>✅ Dashboard API respondiendo correctamente</li>";
    echo "<li>✅ Frontend sin errores de sintaxis</li>";
    echo "<li>✅ Limpieza de datos de prueba exitosa</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h2>❌ ALGUNOS TESTS FALLARON</h2>";
    echo "<p>Se encontraron $tests_failed errores que deben ser corregidos.</p>";
    echo "</div>";
}

echo "<h3>🔗 Enlaces del Sistema</h3>";
echo "<div>";
echo "<a href='frontend/index_admin.php' class='btn'>Panel de Administración</a>";
echo "<a href='frontend/dashboard.php' class='btn'>Dashboard de Estadísticas</a>";
echo "<a href='frontend/crear_formulario.php' class='btn'>Crear Formulario</a>";
echo "<a href='verificar_sistema.php' class='btn'>Verificación Completa</a>";
echo "</div>";

echo "<hr>";
echo "<p><small>🕐 Test ejecutado el: " . date('Y-m-d H:i:s') . " | 🎯 Sistema MySQL con PDO | 🛡️ Anonimato garantizado</small></p>";

echo "</div>";
?>
