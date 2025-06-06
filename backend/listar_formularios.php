<?php
/**
 * Listado de Formularios - Backend
 * Sistema de Encuestas de Satisfacción Anónimas
 * 
 * Puede devolver HTML (por defecto) o JSON si se especifica accept=json
 */

// Si no se ha incluido la configuración, incluirla
if (!class_exists('Database')) {
    require_once 'config.php';
}

/**
 * Obtiene lista de formularios con estadísticas básicas
 */
function obtenerFormularios() {
    $db = Database::getInstance();
    
    try {
        $stmt = $db->prepare("
            SELECT 
                f.id,
                f.titulo,
                f.descripcion,
                f.fecha_creacion,
                COUNT(DISTINCT r.id) as num_respuestas,
                COUNT(DISTINCT p.id) as total_preguntas
            FROM formularios f
            LEFT JOIN respuestas_anonimas r ON f.id = r.formulario_id
            LEFT JOIN preguntas p ON f.id = p.formulario_id
            GROUP BY f.id, f.titulo, f.descripcion, f.fecha_creacion
            ORDER BY f.fecha_creacion DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo formularios: " . $e->getMessage());
        return [];
    }
}

/**
 * Formatea fecha para mostrar en tabla
 */
function formatearFecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

// Verificar si se solicita respuesta JSON
$isJsonRequest = (
    isset($_GET['format']) && $_GET['format'] === 'json'
) || (
    isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
);

// Obtener formularios
$formularios = obtenerFormularios();

// Si se solicita JSON, devolver JSON
if ($isJsonRequest) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    if (!empty($formularios)) {
        echo json_encode([
            'success' => true,
            'formularios' => $formularios
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'formularios' => [],
            'message' => 'No hay formularios disponibles'
        ]);
    }
    exit;
}

// Si no es JSON, continuar con HTML
?>
?>

<!-- Contenedor de la tabla con scroll responsivo -->
<div class="table-container">
    <?php if (empty($formularios)): ?>
        <!-- Estado vacío accesible -->
        <div style="text-align: center; padding: 2rem; background: var(--color-bg-secondary); border-radius: 8px;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
            <h3>Aún no hay formularios creados</h3>
            <p>Crea tu primer formulario de satisfacción para empezar a recopilar feedback anónimo.</p>
            <a href="crear_formulario.php" class="btn btn-primary" style="margin-top: 1rem;">
                ➕ Crear Primer Formulario
            </a>
        </div>
    <?php else: ?>
        <!-- Tabla de formularios -->
        <table role="table" aria-label="Lista de formularios de satisfacción">
            <caption class="sr-only">
                Tabla con <?php echo count($formularios); ?> formularios. 
                Cada fila contiene título, fecha de creación, número de respuestas y acciones disponibles.
            </caption>
            
            <thead>
                <tr>
                    <th scope="col">📝 Título del Formulario</th>
                    <th scope="col">📅 Fecha de Creación</th>
                    <th scope="col">❓ Preguntas</th>
                    <th scope="col">📊 Respuestas</th>
                    <th scope="col">⚙️ Acciones</th>
                </tr>
            </thead>
            
            <tbody>
                <?php foreach ($formularios as $formulario): ?>
                    <tr>
                        <!-- Título con descripción en tooltip -->
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($formulario['titulo']); ?></strong>
                                <?php if (!empty($formulario['descripcion'])): ?>
                                    <div style="font-size: 0.9rem; color: var(--color-text-secondary); margin-top: 0.25rem;">
                                        <?php echo htmlspecialchars(substr($formulario['descripcion'], 0, 100)); ?>
                                        <?php if (strlen($formulario['descripcion']) > 100) echo '...'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <!-- Fecha formateada -->
                        <td>
                            <time datetime="<?php echo $formulario['fecha_creacion']; ?>">
                                <?php echo formatearFecha($formulario['fecha_creacion']); ?>
                            </time>
                        </td>
                        
                        <!-- Número de preguntas -->
                        <td>
                            <span class="badge" style="background: var(--color-bg-secondary); padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.85rem;">
                                <?php echo $formulario['total_preguntas']; ?> preguntas
                            </span>
                        </td>
                        
                        <!-- Número de respuestas -->
                        <td>
                            <span class="badge" style="background: var(--color-success); color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.85rem;">
                                <?php echo $formulario['total_respuestas']; ?> respuestas
                            </span>
                        </td>
                        
                        <!-- Acciones -->
                        <td>                            <div class="actions">                                <!-- Ver respuestas -->
                                <a href="../backend/ver_respuestas.php?formulario_id=<?php echo $formulario['id']; ?>" 
                                   class="btn btn-secondary" 
                                   title="Ver estadísticas y respuestas del formulario"
                                   style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                                    📊 Ver
                                </a>
                                
                                <!-- Editar formulario -->
                                <button type="button" 
                                        class="btn btn-primary btn-editar"
                                        data-formulario-id="<?php echo $formulario['id']; ?>"
                                        title="Editar formulario y preguntas"
                                        style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                                    ✏️ Editar
                                </button>
                                
                                <!-- Eliminar formulario -->
                                <button type="button" 
                                        class="btn btn-danger btn-eliminar"
                                        data-formulario-id="<?php echo $formulario['id']; ?>"
                                        data-formulario-titulo="<?php echo htmlspecialchars($formulario['titulo']); ?>"
                                        title="Eliminar formulario y todas sus respuestas"
                                        style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                                    🗑️ Eliminar
                                </button>
                                  <!-- Enlace directo para encuestados -->
                                <a href="llenar_formulario.php?id=<?php echo $formulario['id']; ?>" 
                                   class="btn btn-secondary"
                                   title="Enlace directo para que los usuarios llenen la encuesta"
                                   target="_blank"
                                   style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                                    🔗 Enlace
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Información adicional -->
        <div style="margin-top: 1rem; padding: 1rem; background: var(--color-bg-secondary); border-radius: 6px; font-size: 0.9rem; color: var(--color-text-secondary);">
            <strong>💡 Consejos:</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <li><strong>Ver:</strong> Analiza estadísticas y respuestas anónimas agregadas</li>
                <li><strong>Editar:</strong> Modifica título, descripción y preguntas (mantiene respuestas existentes)</li>
                <li><strong>Enlace:</strong> Comparte este URL con tus usuarios para que respondan la encuesta</li>
                <li><strong>Eliminar:</strong> Acción irreversible - elimina formulario y todas las respuestas</li>
            </ul>
        </div>
        
        <!-- Recordatorio de anonimato -->
        <div style="margin-top: 1rem; padding: 1rem; background: #f0fdf4; border-left: 4px solid var(--color-success); border-radius: 4px; font-size: 0.9rem;">
            <strong>🔒 Recordatorio de Privacidad:</strong>
            Las respuestas mostradas son completamente anónimas. No es posible identificar qué usuario envió cada respuesta.
        </div>
    <?php endif; ?>
</div>

<!-- Estilos específicos para la tabla -->
<style>
/* Mejorar responsividad de la tabla en móviles */
@media (max-width: 768px) {
    .table-container {
        font-size: 0.85rem;
    }
    
    .actions {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .actions .btn {
        font-size: 0.75rem;
        padding: 0.375rem 0.5rem;
    }
    
    /* Ocultar columnas menos importantes en móviles */
    th:nth-child(3),
    td:nth-child(3) {
        display: none;
    }
}

@media (max-width: 480px) {
    /* En pantallas muy pequeñas, hacer tabla scrollable horizontalmente */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    table {
        min-width: 600px;
    }
}

/* Hover mejorado para filas */
tbody tr:hover {
    background-color: var(--color-bg-secondary);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;
}

/* Estados de badges */
.badge {
    display: inline-flex;
    align-items: center;
    font-weight: 500;
}
</style>

<script src="../frontend/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar eliminación de formularios
    const botonesEliminar = document.querySelectorAll('.btn-eliminar');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', async function() {
            const formularioId = this.dataset.formularioId;
            const formularioTitulo = this.dataset.formularioTitulo;
            
            const confirmacion = confirm(
                `⚠️ ¿Estás seguro de eliminar el formulario "${formularioTitulo}"?\n\n` +
                `Esta acción:\n` +
                `• Eliminará el formulario\n` +
                `• Eliminará todas las preguntas\n` +
                `• Eliminará todas las respuestas anónimas\n\n` +
                `No se puede deshacer.`
            );
            
            if (!confirmacion) return;
            
            try {
                this.disabled = true;
                this.textContent = '🗑️ Eliminando...';
                
                const response = await fetch('../backend/eliminar_formulario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: parseInt(formularioId) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Formulario eliminado exitosamente.\n\nSe eliminaron:\n• ${data.data.preguntas_eliminadas} preguntas\n• ${data.data.respuestas_eliminadas} respuestas`);
                    location.reload();
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
                
            } catch (error) {
                alert('Error al eliminar el formulario: ' + error.message);
                this.disabled = false;
                this.textContent = '🗑️ Eliminar';
            }
        });
    });
    
    // Manejar edición de formularios (redireccionar por ahora)
    const botonesEditar = document.querySelectorAll('.btn-editar');
    botonesEditar.forEach(boton => {
        boton.addEventListener('click', function() {
            const formularioId = this.dataset.formularioId;
            // Por ahora, mostrar que la funcionalidad está disponible via API
            alert('La funcionalidad de edición está disponible via API.\n\nEndpoint: editar_formulario.php\nMétodo: POST\n\nPuedes integrarla en tu interfaz de administración.');
        });
    });
});
</script>
