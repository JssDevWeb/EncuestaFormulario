<?php
/**
 * Test real del sistema sin cortes - Simula exactamente el frontend
 */

echo "=== TEST REAL DEL FORMULARIO ===\n";
echo "Simulando exactamente como envía el frontend...\n\n";

// Datos exactos como los envía el FormSubmitter
$datos_json = [
    'formulario_id' => 1,
    'respuestas' => [
        '13' => '8',  // Respuesta de escala
        '14' => 'Las explicaciones fueron muy claras',  // Respuesta de texto
        '15' => 'Sí, definitivamente',  // Respuesta de texto
        '16' => 'Me gustó mucho el contenido'  // Respuesta de texto
    ]
];

$json_string = json_encode($datos_json);

echo "📤 JSON a enviar:\n";
echo $json_string . "\n\n";

// Simular la petición real usando cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Proyecto%20satisfactorio/backend/enviar_respuesta.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json_string)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "🔄 Enviando petición...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "📥 RESPUESTA COMPLETA:\n";
echo "HTTP Code: $http_code\n";

if ($curl_error) {
    echo "❌ Error cURL: $curl_error\n";
} else {
    echo "✅ Petición enviada correctamente\n";
}

echo "Response Body:\n";
echo "========================================\n";
echo $response;
echo "\n========================================\n";

// Intentar parsear como JSON
if ($response) {
    $response_json = json_decode($response, true);
    if ($response_json) {
        echo "\n📊 RESPUESTA PARSEADA:\n";
        echo "Success: " . ($response_json['success'] ? 'true' : 'false') . "\n";
        if (isset($response_json['error'])) {
            echo "Error: " . $response_json['error'] . "\n";
        }
        if (isset($response_json['message'])) {
            echo "Message: " . $response_json['message'] . "\n";
        }
    } else {
        echo "\n❌ No se pudo parsear como JSON\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
}

echo "\n=== FIN DEL TEST ===\n";
?>
