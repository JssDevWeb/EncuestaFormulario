<?php
/**
 * SCRIPT DE VERIFICACIÓN COMPLETA DEL SISTEMA
 * Ejecutar después de crear la base de datos para verificar que todo funciona
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 VERIFICACIÓN COMPLETA DEL SISTEMA</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

$errores = [];
$advertencias = [];
$exitos = [];

// ====================================================================
// 1. VERIFICAR CONEXIÓN A BASE DE DATOS
// ====================================================================
echo "<h2>1. 🔌 Verificación de Conexión</h2>";

try {
    require_once 'backend/config.php';
    $db = Database::getInstance();
    echo "<p class='success'>✅ Conexión a base de datos exitosa</p>";
    $exitos[] = "Conexión PDO establecida";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
    $errores[] = "Error de conexión: " . $e->getMessage();
    die("<p><strong>DETENER VERIFICACIÓN: Sin conexión a BD no se puede continuar</strong></p>");
}

// ====================================================================
// 2. VERIFICAR ESTRUCTURA DE TABLAS
// ====================================================================
echo "<h2>2. 🗄️ Verificación de Estructura</h2>";

$tablas_requeridas = [
    'formularios' => ['id', 'titulo', 'descripcion', 'estado', 'fecha_creacion'],
    'preguntas' => ['id', 'formulario_id', 'texto_pregunta', 'tipo_respuesta', 'configuracion'],
    'respuestas_anonimas' => ['id', 'formulario_id', 'fecha_envio', 'datos_json'],
    'estadisticas_cache' => ['id', 'formulario_id', 'tipo_estadistica', 'datos_cache'],
    'auditoria_sistema' => ['id', 'evento', 'fecha_evento']
];

foreach ($tablas_requeridas as $tabla => $columnas_requeridas) {
    try {
        // Verificar que la tabla existe
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabla]);
        $existe = $stmt->fetch();
        
        if (!$existe) {
            echo "<p class='warning'>⚠️ Tabla '$tabla' no existe (opcional para algunas)</p>";
            $advertencias[] = "Tabla '$tabla' no encontrada";
            continue;
        }
        
        // Verificar columnas
        $stmt = $db->prepare("DESCRIBE $tabla");
        $stmt->execute();
        $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $columnas_faltantes = array_diff($columnas_requeridas, $columnas);
        
        if (empty($columnas_faltantes)) {
            echo "<p class='success'>✅ Tabla '$tabla' OK</p>";
            $exitos[] = "Estructura de '$tabla' correcta";
        } else {
            echo "<p class='warning'>⚠️ Tabla '$tabla' - Columnas faltantes: " . implode(', ', $columnas_faltantes) . "</p>";
            $advertencias[] = "Tabla '$tabla' incompleta";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error verificando tabla '$tabla': " . $e->getMessage() . "</p>";
        $errores[] = "Error en tabla '$tabla'";
    }
}

// ====================================================================
// 3. VERIFICAR DATOS DE EJEMPLO
// ====================================================================
echo "<h2>3. 📊 Verificación de Datos</h2>";

try {
    // Contar formularios
    $stmt = $db->prepare("SELECT COUNT(*) FROM formularios");
    $stmt->execute();
    $count_formularios = $stmt->fetchColumn();
    
    // Contar preguntas
    $stmt = $db->prepare("SELECT COUNT(*) FROM preguntas");
    $stmt->execute();
    $count_preguntas = $stmt->fetchColumn();
    
    // Contar respuestas
    $stmt = $db->prepare("SELECT COUNT(*) FROM respuestas_anonimas");
    $stmt->execute();
    $count_respuestas = $stmt->fetchColumn();
    
    echo "<table>";
    echo "<tr><th>Elemento</th><th>Cantidad</th><th>Estado</th></tr>";
    echo "<tr><td>Formularios</td><td>$count_formularios</td><td>" . ($count_formularios > 0 ? "✅" : "⚠️") . "</td></tr>";
    echo "<tr><td>Preguntas</td><td>$count_preguntas</td><td>" . ($count_preguntas > 0 ? "✅" : "⚠️") . "</td></tr>";
    echo "<tr><td>Respuestas</td><td>$count_respuestas</td><td>" . ($count_respuestas >= 0 ? "✅" : "❌") . "</td></tr>";
    echo "</table>";
    
    if ($count_formularios > 0 && $count_preguntas > 0) {
        $exitos[] = "Datos de ejemplo disponibles";
    } else {
        $advertencias[] = "Sin datos de ejemplo (normal en instalación nueva)";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error verificando datos: " . $e->getMessage() . "</p>";
    $errores[] = "Error verificando datos";
}

// ====================================================================
// 4. PROBAR FUNCIONALIDADES CRÍTICAS
// ====================================================================
echo "<h2>4. ⚙️ Prueba de Funcionalidades</h2>";

// Probar creación de formulario
try {
    $titulo_prueba = "Formulario de Prueba - " . date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO formularios (titulo, descripcion, estado) VALUES (?, ?, ?)");
    $resultado = $stmt->execute([$titulo_prueba, "Prueba de verificación del sistema", "borrador"]);
    
    if ($resultado) {
        $formulario_id = $db->lastInsertId();
        echo "<p class='success'>✅ Creación de formulario: OK (ID: $formulario_id)</p>";
        $exitos[] = "Inserción de formularios funciona";
        
        // Probar creación de pregunta
        $stmt = $db->prepare("INSERT INTO preguntas (formulario_id, texto_pregunta, tipo_respuesta, orden) VALUES (?, ?, ?, ?)");
        $resultado_pregunta = $stmt->execute([$formulario_id, "¿Esta es una pregunta de prueba?", "radio", 1]);
        
        if ($resultado_pregunta) {
            echo "<p class='success'>✅ Creación de pregunta: OK</p>";
            $exitos[] = "Inserción de preguntas funciona";
        }
        
        // Limpiar datos de prueba
        $stmt = $db->prepare("DELETE FROM formularios WHERE id = ?");
        $stmt->execute([$formulario_id]);
        echo "<p class='info'>🧹 Datos de prueba limpiados</p>";
        
    } else {
        throw new Exception("No se pudo insertar formulario de prueba");
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error en prueba de funcionalidades: " . $e->getMessage() . "</p>";
    $errores[] = "Error en funcionalidades básicas";
}

// ====================================================================
// 5. VERIFICAR CONFIGURACIÓN DE ARCHIVOS
// ====================================================================
echo "<h2>5. 📁 Verificación de Archivos</h2>";

$archivos_criticos = [
    'backend/config.php' => 'Configuración de base de datos',
    'backend/enviar_respuesta.php' => 'Endpoint para respuestas',
    'backend/dashboard_api.php' => 'API del dashboard',
    'frontend/index_admin.php' => 'Panel de administración',
    'frontend/script.js' => 'JavaScript del frontend'
];

foreach ($archivos_criticos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        echo "<p class='success'>✅ $archivo - $descripcion</p>";
        $exitos[] = "Archivo '$archivo' encontrado";
    } else {
        echo "<p class='error'>❌ $archivo - $descripcion (FALTANTE)</p>";
        $errores[] = "Archivo '$archivo' no encontrado";
    }
}

// ====================================================================
// 6. VERIFICAR COMPATIBILIDAD PDO
// ====================================================================
echo "<h2>6. 🔧 Verificación de Compatibilidad PDO</h2>";

try {
    // Verificar que estamos usando PDO
    $connection_type = get_class($db->getConnection());
    if ($connection_type === 'PDO') {
        echo "<p class='success'>✅ Usando PDO correctamente</p>";
        $exitos[] = "PDO configurado correctamente";
    } else {
        echo "<p class='error'>❌ No está usando PDO: $connection_type</p>";
        $errores[] = "Incompatibilidad de conexión";
    }
    
    // Verificar métodos PDO disponibles
    $metodos_pdo = ['prepare', 'execute', 'fetch', 'fetchAll'];
    $test_stmt = $db->prepare("SELECT 1 as test");
    $test_stmt->execute();
    $test_result = $test_stmt->fetch();
    
    if ($test_result && $test_result['test'] == 1) {
        echo "<p class='success'>✅ Métodos PDO funcionando correctamente</p>";
        $exitos[] = "Métodos PDO operativos";
    } else {
        echo "<p class='error'>❌ Problemas con métodos PDO</p>";
        $errores[] = "Métodos PDO no funcionan";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error verificando PDO: " . $e->getMessage() . "</p>";
    $errores[] = "Error en verificación PDO";
}

// ====================================================================
// RESUMEN FINAL
// ====================================================================
echo "<h2>📋 RESUMEN FINAL</h2>";

echo "<h3>✅ ÉXITOS (" . count($exitos) . ")</h3>";
if (!empty($exitos)) {
    echo "<ul>";
    foreach ($exitos as $exito) {
        echo "<li class='success'>$exito</li>";
    }
    echo "</ul>";
}

echo "<h3>⚠️ ADVERTENCIAS (" . count($advertencias) . ")</h3>";
if (!empty($advertencias)) {
    echo "<ul>";
    foreach ($advertencias as $advertencia) {
        echo "<li class='warning'>$advertencia</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No hay advertencias</p>";
}

echo "<h3>❌ ERRORES (" . count($errores) . ")</h3>";
if (!empty($errores)) {
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li class='error'>$error</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='success'>No hay errores críticos</p>";
}

// ====================================================================
// ESTADO GENERAL
// ====================================================================
echo "<h2>🎯 ESTADO GENERAL DEL SISTEMA</h2>";

if (empty($errores)) {
    if (empty($advertencias)) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h3 style='color: #155724; margin: 0;'>🎉 SISTEMA COMPLETAMENTE FUNCIONAL</h3>";
        echo "<p>Todas las verificaciones pasaron exitosamente. El sistema está listo para usar.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
        echo "<h3 style='color: #856404; margin: 0;'>⚠️ SISTEMA FUNCIONAL CON ADVERTENCIAS</h3>";
        echo "<p>El sistema debería funcionar, pero hay algunas advertencias que podrías revisar.</p>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f1b0b7; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>❌ SISTEMA CON ERRORES</h3>";
    echo "<p>Se encontraron errores críticos que deben ser corregidos antes de usar el sistema.</p>";
    echo "</div>";
}

echo "<h3>🔗 Enlaces Útiles</h3>";
echo "<ul>";
echo "<li><a href='frontend/index_admin.php'>Panel de Administración</a></li>";
echo "<li><a href='frontend/dashboard.php'>Dashboard de Estadísticas</a></li>";
echo "<li><a href='test_dashboard_corregido.php'>Test del Dashboard</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Verificación completada el: " . date('Y-m-d H:i:s') . "</small></p>";
?>
