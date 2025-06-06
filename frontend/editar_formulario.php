<?php
/**
 * Editar Formulario - Sistema de Encuestas de Satisfacción Anónimas
 * Interfaz para editar formularios existentes
 */

// Incluir configuración de BD
require_once __DIR__ . '/../backend/config.php';

// Obtener ID del formulario
$formulario_id = intval($_GET['id'] ?? 0);

if ($formulario_id <= 0) {
    header("Location: index_admin.php?error=" . urlencode("ID de formulario inválido"));
    exit;
}

try {
    $db = Database::getInstance();
    
    // Obtener datos del formulario
    $formulario = $db->query("SELECT id, titulo, descripcion, fecha_creacion FROM formularios WHERE id = $formulario_id");
    
    if (empty($formulario) || !is_array($formulario)) {
        header("Location: index_admin.php?error=" . urlencode("Formulario no encontrado"));
        exit;
    }
    
    $formulario = $formulario[0];
      // Obtener preguntas del formulario (usando estructura real de la BD)
    $preguntas = $db->query("SELECT id, texto_pregunta, tipo_respuesta FROM preguntas WHERE formulario_id = $formulario_id ORDER BY id");
    
    if (!is_array($preguntas)) {
        $preguntas = [];
    }
    
} catch (Exception $e) {
    header("Location: index_admin.php?error=" . urlencode("Error al cargar formulario: " . $e->getMessage()));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Formulario - Sistema de Encuestas</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <header role="banner">
            <h1>✏️ Editar Formulario</h1>
            <p>Modifica el formulario de encuesta de satisfacción anónima</p>
        </header>

        <!-- Navegación -->
        <nav role="navigation" aria-label="Navegación principal">
            <div class="nav-links">
                <a href="index_admin.php" class="btn btn-secondary">
                    🏠 Volver al Panel
                </a>
                <a href="llenar_formulario.php?id=<?php echo $formulario_id; ?>" class="btn btn-primary" target="_blank">
                    📝 Vista Previa
                </a>
            </div>
        </nav>

        <!-- Mensaje de estado -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="message message-success" role="alert">
                <?php echo htmlspecialchars($_GET['mensaje']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="message message-error" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>        <!-- Formulario de edición -->
        <main role="main">
            <form id="form-editar" class="formulario">
                <input type="hidden" name="id" value="<?php echo $formulario['id']; ?>">
                
                <!-- Información básica del formulario -->
                <section aria-labelledby="info-basica">
                    <h2 id="info-basica">📝 Información del Formulario</h2>
                    
                    <div class="campo-grupo">
                        <label for="titulo" class="requerido">Título del Formulario</label>
                        <input 
                            type="text" 
                            id="titulo" 
                            name="titulo" 
                            value="<?php echo htmlspecialchars($formulario['titulo']); ?>"
                            required 
                            maxlength="255"
                            aria-describedby="titulo-ayuda"
                        >
                        <small id="titulo-ayuda">Describe brevemente el propósito de la encuesta</small>
                    </div>
                    
                    <div class="campo-grupo">
                        <label for="descripcion" class="requerido">Descripción</label>
                        <textarea 
                            id="descripcion" 
                            name="descripcion" 
                            required 
                            maxlength="1000"
                            rows="4"
                            aria-describedby="descripcion-ayuda"
                        ><?php echo htmlspecialchars($formulario['descripcion']); ?></textarea>
                        <small id="descripcion-ayuda">Explica a los usuarios qué información buscas recopilar</small>
                    </div>
                </section>

                <!-- Preguntas del formulario -->
                <section aria-labelledby="preguntas-titulo">
                    <h2 id="preguntas-titulo">❓ Preguntas del Formulario</h2>
                    
                    <div id="contenedor-preguntas">
                        <?php foreach ($preguntas as $index => $pregunta): ?>
                            <div class="pregunta-item" data-pregunta-id="<?php echo $pregunta['id']; ?>">
                                <div class="pregunta-header">
                                    <h3>Pregunta <?php echo $index + 1; ?></h3>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarPregunta(this)">
                                        🗑️ Eliminar
                                    </button>
                                </div>
                                
                                <div class="campo-grupo">
                                    <label>Texto de la pregunta</label>
                                    <input 
                                        type="text" 
                                        name="preguntas[<?php echo $index; ?>][texto]" 
                                        value="<?php echo htmlspecialchars($pregunta['texto_pregunta']); ?>"
                                        required 
                                        maxlength="500"
                                    >
                                </div>
                                
                                <div class="campo-grupo">
                                    <label>Tipo de respuesta</label>
                                    <select name="preguntas[<?php echo $index; ?>][tipo]" required>
                                        <option value="texto" <?php echo $pregunta['tipo_respuesta'] === 'texto' ? 'selected' : ''; ?>>Texto corto</option>
                                        <option value="textarea" <?php echo $pregunta['tipo_respuesta'] === 'textarea' ? 'selected' : ''; ?>>Texto largo</option>
                                        <option value="radio" <?php echo $pregunta['tipo_respuesta'] === 'radio' ? 'selected' : ''; ?>>Opción única</option>
                                        <option value="checkbox" <?php echo $pregunta['tipo_respuesta'] === 'checkbox' ? 'selected' : ''; ?>>Múltiples opciones</option>
                                        <option value="select" <?php echo $pregunta['tipo_respuesta'] === 'select' ? 'selected' : ''; ?>>Lista desplegable</option>
                                        <option value="escala" <?php echo $pregunta['tipo_respuesta'] === 'escala' ? 'selected' : ''; ?>>Escala (1-5)</option>
                                    </select>
                                </div>
                                
                                <input type="hidden" name="preguntas[<?php echo $index; ?>][id]" value="<?php echo $pregunta['id']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" id="agregar-pregunta" class="btn btn-success">
                        ➕ Agregar Pregunta
                    </button>
                </section>

                <!-- Botones de acción -->
                <div class="acciones">
                    <button type="submit" class="btn btn-primary">
                        💾 Guardar Cambios
                    </button>
                    <a href="index_admin.php" class="btn btn-secondary">
                        ❌ Cancelar
                    </a>
                </div>
            </form>
        </main>
    </div>

    <script src="script.js"></script>
    <script>
        // Contador para nuevas preguntas
        let contadorPreguntas = <?php echo count($preguntas); ?>;
        
        // Agregar nueva pregunta
        document.getElementById('agregar-pregunta').addEventListener('click', function() {
            const contenedor = document.getElementById('contenedor-preguntas');
            const nuevaPregunta = document.createElement('div');
            nuevaPregunta.className = 'pregunta-item';
            nuevaPregunta.innerHTML = `
                <div class="pregunta-header">
                    <h3>Pregunta ${contadorPreguntas + 1}</h3>
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarPregunta(this)">
                        🗑️ Eliminar
                    </button>
                </div>
                
                <div class="campo-grupo">
                    <label>Texto de la pregunta</label>
                    <input 
                        type="text" 
                        name="preguntas[${contadorPreguntas}][texto]" 
                        required 
                        maxlength="500"
                    >
                </div>
                
                <div class="campo-grupo">
                    <label>Tipo de respuesta</label>
                    <select name="preguntas[${contadorPreguntas}][tipo]" required>
                        <option value="texto">Texto corto</option>
                        <option value="textarea">Texto largo</option>
                        <option value="radio">Opción única</option>
                        <option value="checkbox">Múltiples opciones</option>
                        <option value="select">Lista desplegable</option>
                        <option value="escala">Escala (1-5)</option>
                    </select>
                </div>
                
                <input type="hidden" name="preguntas[${contadorPreguntas}][id]" value="0">
            `;
            
            contenedor.appendChild(nuevaPregunta);
            contadorPreguntas++;
        });
        
        // Eliminar pregunta
        function eliminarPregunta(boton) {
            if (confirm('¿Estás seguro de eliminar esta pregunta?')) {
                boton.closest('.pregunta-item').remove();
                actualizarNumeracionPreguntas();
            }
        }
        
        // Actualizar numeración de preguntas
        function actualizarNumeracionPreguntas() {
            const preguntas = document.querySelectorAll('.pregunta-item');
            preguntas.forEach((pregunta, index) => {
                const h3 = pregunta.querySelector('h3');
                h3.textContent = `Pregunta ${index + 1}`;
            });
        }
          // Validación y envío del formulario
        document.getElementById('form-editar').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const titulo = document.getElementById('titulo').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const preguntasElements = document.querySelectorAll('.pregunta-item');
            
            if (titulo.length < 3) {
                alert('El título debe tener al menos 3 caracteres');
                return;
            }
            
            if (descripcion.length < 10) {
                alert('La descripción debe tener al menos 10 caracteres');
                return;
            }
            
            if (preguntasElements.length === 0) {
                alert('Debe incluir al menos una pregunta');
                return;
            }
            
            // Recopilar datos de las preguntas
            const preguntas = [];
            for (let preguntaElement of preguntasElements) {
                const textoInput = preguntaElement.querySelector('input[name*="[texto]"]');
                const tipoSelect = preguntaElement.querySelector('select[name*="[tipo]"]');
                
                if (!textoInput.value.trim()) {
                    alert('Todas las preguntas deben tener texto');
                    textoInput.focus();
                    return;
                }
                
                preguntas.push({
                    texto: textoInput.value.trim(),
                    tipo: tipoSelect.value
                });
            }
            
            // Enviar datos como JSON
            const datos = {
                id: parseInt(document.querySelector('input[name="id"]').value),
                titulo: titulo,
                descripcion: descripcion,
                preguntas: preguntas
            };
            
            // Mostrar indicador de carga
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = '⏳ Guardando...';
            submitBtn.disabled = true;
            
            fetch('../backend/editar_formulario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datos)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Formulario actualizado exitosamente');
                    window.location.href = 'index_admin.php?mensaje=' + encodeURIComponent(data.message);
                } else {
                    alert('❌ Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión: ' + error.message);
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>
