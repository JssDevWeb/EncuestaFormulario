<?php
/**
 * Sistema de Encuestas de Satisfacción - Llenar Formulario
 * Interfaz pública y accesible para que los usuarios completen encuestas de manera anónima
 */

require_once '../backend/config.php';

try {
    $db = getDB();
    
    $formulario_id = intval($_GET['id'] ?? 0);
    
    if ($formulario_id <= 0) {
        throw new Exception('ID de formulario inválido');
    }
      // Obtener información del formulario
    $result = $db->query(
        "SELECT id, titulo, descripcion, fecha_creacion FROM formularios WHERE id = ?",
        [$formulario_id]
    );
    
    $formulario = is_array($result) && count($result) > 0 ? $result[0] : null;
    
    if (!$formulario) {
        throw new Exception('Formulario no encontrado');
    }
      // Obtener preguntas del formulario
    $preguntas = $db->query(
        "SELECT id, texto_pregunta as texto, tipo_respuesta as tipo
         FROM preguntas 
         WHERE formulario_id = ? 
         ORDER BY id",
        [$formulario_id]
    );
    
    // Agregar campos por defecto para compatibilidad
    if (is_array($preguntas)) {
        foreach ($preguntas as &$pregunta) {
            $pregunta['obligatoria'] = false; // Por defecto no obligatoria
            $pregunta['opciones'] = '[]'; // Array vacío por defecto
            $pregunta['configuracion'] = '{}'; // Objeto vacío por defecto
        }
        unset($pregunta); // Limpiar referencia
    }
    
    if (!is_array($preguntas) || empty($preguntas)) {
        throw new Exception('Este formulario no tiene preguntas configuradas');
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($formulario) ? htmlspecialchars($formulario['titulo']) : 'Error' ?> - Encuesta de Satisfacción</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .formulario-publico {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .encabezado-formulario {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: var(--color-fondo-secundario);
            border-radius: 12px;
            border: 1px solid var(--color-borde);
        }
        
        .titulo-formulario {
            color: var(--color-primario);
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .descripcion-formulario {
            font-size: 1.1rem;
            color: var(--color-texto-secundario);
            line-height: 1.6;
        }
        
        .indicador-anonimato {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .indicador-anonimato::before {
            content: "🔒";
            font-size: 1.5rem;
        }
        
        .pregunta-container {
            background: var(--color-fondo-secundario);
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.3s ease;
        }
        
        .pregunta-container:focus-within {
            box-shadow: 0 0 0 3px var(--color-foco);
        }
        
        .pregunta-numero {
            background: var(--color-primario);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .pregunta-titulo {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 500;
            line-height: 1.4;
        }
        
        .obligatoria-indicator {
            color: var(--color-error);
            margin-left: 0.25rem;
            font-weight: bold;
        }
        
        .opciones-container {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .opcion-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--color-fondo-principal);
            border: 1px solid var(--color-borde);
            border-radius: 6px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .opcion-item:hover {
            background: var(--color-fondo-terciario);
            border-color: var(--color-primario);
        }
        
        .opcion-item:focus-within {
            box-shadow: 0 0 0 2px var(--color-foco);
        }
        
        .opcion-input {
            margin: 0;
            cursor: pointer;
        }
        
        .opcion-texto {
            flex: 1;
            line-height: 1.4;
            cursor: pointer;
            user-select: none;
        }
        
        .escala-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--color-fondo-principal);
            border: 1px solid var(--color-borde);
            border-radius: 8px;
        }
        
        .escala-valores {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            justify-content: center;
        }
        
        .escala-valor {
            position: relative;
        }
        
        .escala-radio {
            width: 44px;
            height: 44px;
            cursor: pointer;
            margin: 0;
        }
        
        .escala-numero {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            font-weight: bold;
            color: var(--color-texto);
        }
        
        .escala-etiqueta {
            font-size: 0.9rem;
            color: var(--color-texto-secundario);
            text-align: center;
            font-weight: 500;
            min-width: 80px;
        }
        
        .progress-container {
            position: sticky;
            top: 20px;
            background: var(--color-fondo-principal);
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            z-index: 100;
        }
        
        .progress-text {
            text-align: center;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--color-texto-secundario);
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--color-fondo-terciario);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--color-primario);
            transition: width 0.3s ease;
            border-radius: 4px;
        }
        
        .botones-formulario {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--color-borde);
        }
        
        .mensaje-exito {
            text-align: center;
            padding: 3rem;
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 12px;
            margin-top: 2rem;
        }
        
        .mensaje-exito h2 {
            color: #2e7d32;
            margin-bottom: 1rem;
        }
        
        .mensaje-exito p {
            color: #388e3c;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .formulario-publico {
                padding: 1rem;
            }
            
            .titulo-formulario {
                font-size: 2rem;
            }
            
            .escala-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .escala-valores {
                gap: 0.25rem;
            }
            
            .escala-radio {
                width: 36px;
                height: 36px;
            }
            
            .botones-formulario {
                flex-direction: column;
            }
        }
        
        @media (prefers-reduced-motion: reduce) {
            .progress-fill,
            .pregunta-container,
            .opcion-item {
                transition: none;
            }
        }
    </style>
</head>
<body>
    <div class="formulario-publico">
        <?php if (isset($error)): ?>
            <div class="alert alert-error" role="alert">
                <h1>Error</h1>
                <p><?= htmlspecialchars($error) ?></p>
                <a href="../frontend/index_admin.php" class="btn btn-primary">Volver al inicio</a>
            </div>
        <?php else: ?>
            <header class="encabezado-formulario">
                <h1 class="titulo-formulario"><?= htmlspecialchars($formulario['titulo']) ?></h1>
                <?php if ($formulario['descripcion']): ?>
                    <p class="descripcion-formulario"><?= nl2br(htmlspecialchars($formulario['descripcion'])) ?></p>
                <?php endif; ?>
            </header>

            <div class="indicador-anonimato" role="note" aria-label="Información de privacidad">
                <div>
                    <strong>Encuesta completamente anónima</strong><br>
                    No se recopila información personal. Sus respuestas son confidenciales y no pueden ser rastreadas.
                </div>
            </div>

            <form id="formulario-encuesta" novalidate>
                <input type="hidden" name="formulario_id" value="<?= $formulario_id ?>">
                
                <div class="progress-container" id="progress-container">
                    <div class="progress-text" id="progress-text">
                        Pregunta 1 de <?= count($preguntas) ?>
                    </div>
                    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                </div>

                <?php foreach ($preguntas as $index => $pregunta): ?>
                    <fieldset class="pregunta-container" id="pregunta-<?= $pregunta['id'] ?>">
                        <legend class="pregunta-titulo">
                            <span class="pregunta-numero" aria-hidden="true"><?= $index + 1 ?></span>
                            <span>
                                <?= htmlspecialchars($pregunta['texto']) ?>
                                <?php if ($pregunta['obligatoria']): ?>
                                    <span class="obligatoria-indicator" aria-label="Pregunta obligatoria">*</span>
                                <?php endif; ?>
                            </span>
                        </legend>

                        <?php
                        $pregunta_id = $pregunta['id'];
                        $tipo = $pregunta['tipo'];
                        $opciones = json_decode($pregunta['opciones'], true) ?? [];
                        $configuracion = json_decode($pregunta['configuracion'], true) ?? [];
                        ?>

                        <?php if ($tipo === 'texto'): ?>
                            <input 
                                type="text" 
                                id="respuesta-<?= $pregunta_id ?>"
                                name="respuestas[<?= $pregunta_id ?>]"
                                class="form-input"
                                maxlength="1000"
                                <?= $pregunta['obligatoria'] ? 'required' : '' ?>
                                aria-describedby="<?= $pregunta['obligatoria'] ? 'required-' . $pregunta_id : '' ?>"
                            >
                            <?php if ($pregunta['obligatoria']): ?>
                                <div id="required-<?= $pregunta_id ?>" class="sr-only">Campo obligatorio</div>
                            <?php endif; ?>

                        <?php elseif ($tipo === 'textarea'): ?>
                            <textarea 
                                id="respuesta-<?= $pregunta_id ?>"
                                name="respuestas[<?= $pregunta_id ?>]"
                                class="form-textarea"
                                rows="4"
                                maxlength="5000"
                                <?= $pregunta['obligatoria'] ? 'required' : '' ?>
                                aria-describedby="<?= $pregunta['obligatoria'] ? 'required-' . $pregunta_id : '' ?>"
                            ></textarea>
                            <?php if ($pregunta['obligatoria']): ?>
                                <div id="required-<?= $pregunta_id ?>" class="sr-only">Campo obligatorio</div>
                            <?php endif; ?>

                        <?php elseif ($tipo === 'radio'): ?>
                            <div class="opciones-container" role="radiogroup" aria-labelledby="pregunta-titulo-<?= $pregunta_id ?>">
                                <?php foreach ($opciones as $opcion_index => $opcion): ?>
                                    <label class="opcion-item">
                                        <input 
                                            type="radio" 
                                            name="respuestas[<?= $pregunta_id ?>]" 
                                            value="<?= htmlspecialchars($opcion) ?>"
                                            class="opcion-input"
                                            <?= $pregunta['obligatoria'] ? 'required' : '' ?>
                                        >
                                        <span class="opcion-texto"><?= htmlspecialchars($opcion) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($tipo === 'checkbox'): ?>
                            <div class="opciones-container" role="group" aria-labelledby="pregunta-titulo-<?= $pregunta_id ?>">
                                <?php foreach ($opciones as $opcion_index => $opcion): ?>
                                    <label class="opcion-item">
                                        <input 
                                            type="checkbox" 
                                            name="respuestas[<?= $pregunta_id ?>][]" 
                                            value="<?= htmlspecialchars($opcion) ?>"
                                            class="opcion-input"
                                        >
                                        <span class="opcion-texto"><?= htmlspecialchars($opcion) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($tipo === 'select'): ?>
                            <select 
                                id="respuesta-<?= $pregunta_id ?>"
                                name="respuestas[<?= $pregunta_id ?>]"
                                class="form-select"
                                <?= $pregunta['obligatoria'] ? 'required' : '' ?>
                            >
                                <option value="">Seleccione una opción</option>
                                <?php foreach ($opciones as $opcion): ?>
                                    <option value="<?= htmlspecialchars($opcion) ?>">
                                        <?= htmlspecialchars($opcion) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php elseif ($tipo === 'escala'): ?>
                            <?php
                            $min = $configuracion['min'] ?? 1;
                            $max = $configuracion['max'] ?? 5;
                            $etiqueta_min = $configuracion['etiqueta_min'] ?? '';
                            $etiqueta_max = $configuracion['etiqueta_max'] ?? '';
                            ?>
                            <div class="escala-container" role="radiogroup" aria-labelledby="pregunta-titulo-<?= $pregunta_id ?>">
                                <?php if ($etiqueta_min): ?>
                                    <div class="escala-etiqueta"><?= htmlspecialchars($etiqueta_min) ?></div>
                                <?php endif; ?>
                                
                                <div class="escala-valores">
                                    <?php for ($i = $min; $i <= $max; $i++): ?>
                                        <div class="escala-valor">
                                            <input 
                                                type="radio" 
                                                id="escala-<?= $pregunta_id ?>-<?= $i ?>"
                                                name="respuestas[<?= $pregunta_id ?>]" 
                                                value="<?= $i ?>"
                                                class="escala-radio"
                                                <?= $pregunta['obligatoria'] ? 'required' : '' ?>
                                            >
                                            <label for="escala-<?= $pregunta_id ?>-<?= $i ?>" class="escala-numero">
                                                <?= $i ?>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if ($etiqueta_max): ?>
                                    <div class="escala-etiqueta"><?= htmlspecialchars($etiqueta_max) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="error-message" id="error-<?= $pregunta_id ?>" role="alert" aria-live="polite"></div>
                    </fieldset>
                <?php endforeach; ?>

                <div class="botones-formulario">
                    <button type="button" class="btn btn-secondary" id="btn-borrar">
                        Borrar respuestas
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-enviar">
                        Enviar respuestas
                    </button>
                </div>
            </form>

            <div id="mensaje-exito" class="mensaje-exito" style="display: none;">
                <h2>¡Gracias por su participación!</h2>
                <p>Sus respuestas han sido enviadas exitosamente de manera anónima.</p>
                <p>Su opinión es muy valiosa para nosotros.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formulario = document.getElementById('formulario-encuesta');
            const btnBorrar = document.getElementById('btn-borrar');
            const btnEnviar = document.getElementById('btn-enviar');
            const mensajeExito = document.getElementById('mensaje-exito');
              if (!formulario) return;            // Inicializar componentes
            const validator = new FormValidator(formulario);
            const progressIndicator = new ProgressIndicator(formulario);
            const formSubmitter = new FormSubmitter(formulario, '../backend/enviar_respuesta.php');

            // La validación en tiempo real ya se configura automáticamente en el constructor
            
            // Actualizar progreso al cambiar respuestas
            formulario.addEventListener('input', function() {
                progressIndicator.updateProgress();
            });

            formulario.addEventListener('change', function() {
                progressIndicator.updateProgress();
            });

            // Manejar borrar respuestas
            btnBorrar.addEventListener('click', function() {
                if (confirm('¿Está seguro de que desea borrar todas las respuestas?')) {
                    formulario.reset();
                    
                    // Limpiar errores
                    const errores = formulario.querySelectorAll('.error-message');
                    errores.forEach(error => error.textContent = '');
                    
                    const campos = formulario.querySelectorAll('.input-error, .field-error');
                    campos.forEach(campo => {
                        campo.classList.remove('input-error', 'field-error');
                    });
                    
                    progressIndicator.updateProgress();
                    
                    // Enfocar el primer campo
                    const primerCampo = formulario.querySelector('input, textarea, select');
                    if (primerCampo) {
                        primerCampo.focus();
                    }
                }
            });

            // Inicializar progreso
            progressIndicator.updateProgress();
        });
    </script>
</body>
</html>
