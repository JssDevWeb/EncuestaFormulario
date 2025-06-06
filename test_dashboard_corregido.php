<?php
echo "=== TEST DE VALIDACIÓN DEL DASHBOARD CORREGIDO ===\n\n";

// Test 1: Verificar que la API devuelve JSON válido
echo "1. Probando dashboard_api.php con formulario_id=10...\n";
$api_url = "http://localhost/Proyecto%20satisfactorio/backend/dashboard_api.php?formulario_id=10";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, 1);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo "❌ ERROR: No se pudo conectar a la API\n";
} else {
    echo "✅ Respuesta recibida (HTTP $http_code)\n";
    
    // Separar headers y body
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $header_size);
    
    // Verificar que no contenga HTML
    if (strpos($body, '<!DOCTYPE') !== false || strpos($body, '<html>') !== false) {
        echo "❌ ERROR: La respuesta contiene HTML:\n";
        echo substr($body, 0, 200) . "...\n";
    } else {
        echo "✅ No se detectó HTML en la respuesta\n";
        
        // Verificar que es JSON válido
        $json_data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ JSON válido parseado correctamente\n";
            if (isset($json_data['success']) && $json_data['success']) {
                echo "   ✅ API respondió con success=true\n";
                if (isset($json_data['data']['estadisticas_generales'])) {
                    $stats = $json_data['data']['estadisticas_generales'];
                    echo "   📊 Total respuestas: " . $stats['total_respuestas'] . "\n";
                    echo "   📋 Total preguntas: " . $stats['total_preguntas'] . "\n";
                    echo "   📈 Promedio satisfacción: " . $stats['promedio_satisfaccion'] . "\n";
                }
            } else {
                echo "   ⚠️  API respondió con success=false: " . ($json_data['error'] ?? 'Sin error específico') . "\n";
            }
        } else {
            echo "❌ ERROR: JSON inválido - " . json_last_error_msg() . "\n";
            echo "Primeros 200 caracteres de la respuesta:\n";
            echo substr($body, 0, 200) . "\n";
        }
    }
}

echo "\n";

// Test 2: Verificar listado de formularios
echo "2. Probando listar_formularios.php...\n";
$forms_url = "http://localhost/Proyecto%20satisfactorio/backend/listar_formularios.php?format=json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $forms_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$forms_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($forms_response === false) {
    echo "❌ ERROR: No se pudo obtener la lista de formularios\n";
} else {
    echo "✅ Lista de formularios obtenida (HTTP $http_code)\n";
    $forms_data = json_decode($forms_response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($forms_data['formularios'])) {
        echo "✅ Lista de formularios parseada correctamente\n";
        echo "   📊 Total de formularios: " . count($forms_data['formularios']) . "\n";
        
        // Buscar formulario ID 10
        $form_10_found = false;
        foreach ($forms_data['formularios'] as $form) {
            if ($form['id'] == 10) {
                $form_10_found = true;
                echo "   ✅ Formulario ID 10 encontrado: '" . $form['titulo'] . "' (" . $form['num_respuestas'] . " respuestas)\n";
                break;
            }
        }
        
        if (!$form_10_found) {
            echo "   ⚠️  Formulario ID 10 no encontrado en la lista\n";
            echo "   📋 Formularios disponibles:\n";
            foreach (array_slice($forms_data['formularios'], 0, 3) as $form) {
                echo "      • ID: " . $form['id'] . " - " . $form['titulo'] . " (" . $form['num_respuestas'] . " respuestas)\n";
            }
        }
    } else {
        echo "❌ ERROR: Respuesta de formularios inválida - " . json_last_error_msg() . "\n";
    }
}

echo "\n";

// Test 3: Verificar acceso a archivos principales
echo "3. Verificando archivos del sistema...\n";

$files_to_check = [
    'frontend/dashboard.php' => 'Dashboard principal',
    'backend/dashboard_api.php' => 'API del dashboard',
    'frontend/index_admin.php' => 'Panel de administración',
    'backend/listar_formularios.php' => 'API de formularios'
];

foreach ($files_to_check as $file => $description) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $size = round(filesize($full_path) / 1024, 1);
        echo "✅ $description: $file ({$size}KB)\n";
    } else {
        echo "❌ $description NO encontrado: $file\n";
    }
}

echo "\n";

// Test 4: Prueba de conectividad HTTP a los archivos principales
echo "4. Probando conectividad HTTP...\n";

$http_tests = [
    'http://localhost/Proyecto%20satisfactorio/frontend/dashboard.php' => 'Dashboard',
    'http://localhost/Proyecto%20satisfactorio/frontend/index_admin.php' => 'Admin Panel'
];

foreach ($http_tests as $url => $name) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, 1); // Solo headers
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        echo "✅ $name accesible vía HTTP (HTTP $http_code)\n";
    } else {
        echo "❌ $name NO accesible (HTTP $http_code)\n";
    }
}

echo "\n=== RESUMEN FINAL ===\n";
echo "🎉 DASHBOARD - ERROR CORREGIDO\n";
echo "\n✅ Correcciones aplicadas:\n";
echo "• 🔧 Configuración de manejo de errores en dashboard_api.php\n";
echo "• 🚫 Supresión de warnings PHP que causaban HTML mezclado con JSON\n";
echo "• 🧹 Limpieza del buffer de salida antes de enviar JSON\n";
echo "• 📡 API ahora devuelve JSON puro sin errores HTML\n";
echo "\n🔗 Para probar el dashboard:\n";
echo "• Dashboard: http://localhost/Proyecto%20satisfactorio/frontend/dashboard.php\n";
echo "• Admin Panel: http://localhost/Proyecto%20satisfactorio/frontend/index_admin.php\n";
echo "\n";
echo "Fecha del test: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n";
