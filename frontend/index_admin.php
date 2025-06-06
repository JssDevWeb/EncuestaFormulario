<?php
/**
 * Panel Administrativo Principal
 * Sistema de Encuestas de Satisfacción Anónimas
 * 
 * Según README.md: Incluye encabezado, botón crear formulario, 
 * y listado de formularios existentes
 */

// Incluir configuración de BD
require_once __DIR__ . '/../backend/config.php';

// Función para obtener estadísticas básicas
function obtenerEstadisticas() {
    $db = Database::getInstance();
    
    try {
        // Contar formularios
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM formularios");
        $stmt->execute();
        $resultFormularios = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalFormularios = $resultFormularios ? $resultFormularios['total'] : 0;
        
        // Contar respuestas totales
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM respuestas_anonimas");
        $stmt->execute();
        $resultRespuestas = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalRespuestas = $resultRespuestas ? $resultRespuestas['total'] : 0;
        
        // Obtener formulario más respondido
        $stmt = $db->prepare("
            SELECT f.titulo, COUNT(r.id) as respuestas 
            FROM formularios f 
            LEFT JOIN respuestas_anonimas r ON f.id = r.formulario_id 
            GROUP BY f.id, f.titulo 
            ORDER BY respuestas DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $masRespondido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_formularios' => (int)$totalFormularios,
            'total_respuestas' => (int)$totalRespuestas,
            'mas_respondido' => $masRespondido ?: null
        ];
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        return [
            'total_formularios' => 0,
            'total_respuestas' => 0,
            'mas_respondido' => null
        ];
    }
}

// Función para obtener formularios para mostrar en el panel
function obtenerFormulariosPanel() {
    $db = Database::getInstance();
    
    try {
        // Obtener formularios básicos primero
        $formularios = $db->query("
            SELECT id, titulo, descripcion, fecha_creacion 
            FROM formularios 
            ORDER BY fecha_creacion DESC
        ");
        
        if (!is_array($formularios)) {
            return [];
        }
        
        // Agregar contadores de preguntas y respuestas para cada formulario
        foreach ($formularios as &$formulario) {
            $id = (int)$formulario['id'];
            
            // Contar preguntas
            $preguntas = $db->query("SELECT COUNT(*) as total FROM preguntas WHERE formulario_id = $id");
            $formulario['num_preguntas'] = is_array($preguntas) ? (int)$preguntas[0]['total'] : 0;
            
            // Contar respuestas
            $respuestas = $db->query("SELECT COUNT(*) as total FROM respuestas_anonimas WHERE formulario_id = $id");
            $formulario['num_respuestas'] = is_array($respuestas) ? (int)$respuestas[0]['total'] : 0;
        }
        
        return $formularios;
    } catch (Exception $e) {
        error_log("Error obteniendo formularios: " . $e->getMessage());
        return [];
    }
}

$estadisticas = obtenerEstadisticas();
$formularios = obtenerFormulariosPanel();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Encuestas de Satisfacción</title>
    
    <!-- Estilos accesibles según README -->
    <link rel="stylesheet" href="estilos.css">
    
    <!-- Meta tags para SEO y accesibilidad -->
    <meta name="description" content="Panel administrativo para gestionar encuestas de satisfacción anónimas">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <div class="container">
        <!-- Encabezado principal -->
        <header role="banner">
            <h1>📊 Sistema de Encuestas de Satisfacción</h1>
            <p>Panel administrativo para gestión de formularios anónimos</p>
        </header>        <!-- Navegación principal -->
        <nav role="navigation" aria-label="Navegación principal">
            <div class="nav-links">
                <a href="index_admin.php" class="btn btn-primary" aria-current="page">
                    🏠 Inicio
                </a>
                <a href="crear_formulario.php" class="btn btn-success">
                    ➕ Crear Formulario
                </a>
                <a href="dashboard.php" class="btn btn-info">
                    📊 Dashboard Avanzado
                </a>
                <a href="comparacion.php" class="btn btn-warning">
                    🔄 Comparar Formularios
                </a>
            </div>
        </nav>

        <!-- Estadísticas rápidas -->
        <section aria-labelledby="stats-title">
            <h2 id="stats-title">📈 Estadísticas del Sistema</h2>
            
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div class="stat-card" style="background: var(--color-bg-secondary); padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--color-primary);">
                        <?php echo $estadisticas['total_formularios']; ?>
                    </div>
                    <div>Formularios Creados</div>
                </div>
                
                <div class="stat-card" style="background: var(--color-bg-secondary); padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--color-success);">
                        <?php echo $estadisticas['total_respuestas']; ?>
                    </div>
                    <div>Respuestas Anónimas</div>
                </div>
                
                <?php if ($estadisticas['mas_respondido']): ?>
                <div class="stat-card" style="background: var(--color-bg-secondary); padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.2rem; font-weight: bold; color: var(--color-warning);">
                        <?php echo htmlspecialchars($estadisticas['mas_respondido']['titulo']); ?>
                    </div>
                    <div><?php echo $estadisticas['mas_respondido']['respuestas']; ?> respuestas</div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Mensaje de estado si existe -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="message message-success" role="alert">
                <?php echo htmlspecialchars($_GET['mensaje']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="message message-error" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Listado de formularios -->
        <main role="main">
            <section aria-labelledby="formularios-title">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 id="formularios-title">📝 Formularios Existentes</h2>
                    <a href="crear_formulario.php" class="btn btn-success">
                        ➕ Nuevo Formulario
                    </a>
                </div>                <!-- Incluir listado de formularios -->
                <div class="formularios-lista">
                    <?php if (empty($formularios)): ?>
                        <div class="empty-state" style="text-align: center; padding: 2rem; background: var(--color-bg-secondary); border-radius: 8px; color: var(--color-text-secondary);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                            <h3>Aún no hay formularios creados</h3>
                            <p>Crea tu primer formulario de satisfacción para empezar a recopilar feedback anónimo.</p>
                            <a href="crear_formulario.php" class="btn btn-primary" style="margin-top: 1rem;">
                                ➕ Crear Primer Formulario
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="formularios-grid" style="display: grid; gap: 1rem;">
                            <?php foreach ($formularios as $formulario): ?>
                                <div class="formulario-card" style="background: var(--color-bg-secondary); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--color-border);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                        <div style="flex: 1;">
                                            <h3 style="margin: 0 0 0.5rem 0; color: var(--color-primary);">
                                                <?php echo htmlspecialchars($formulario['titulo']); ?>
                                            </h3>
                                            <?php if (!empty($formulario['descripcion'])): ?>
                                                <p style="margin: 0 0 0.5rem 0; color: var(--color-text-secondary); font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars(substr($formulario['descripcion'], 0, 100)) . (strlen($formulario['descripcion']) > 100 ? '...' : ''); ?>
                                                </p>
                                            <?php endif; ?>
                                            <div style="font-size: 0.8rem; color: var(--color-text-secondary);">
                                                📅 Creado: <?php echo date('d/m/Y H:i', strtotime($formulario['fecha_creacion'])); ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right; font-size: 0.9rem; color: var(--color-text-secondary);">
                                            <div>📊 <?php echo $formulario['num_preguntas']; ?> preguntas</div>
                                            <div>✅ <?php echo $formulario['num_respuestas']; ?> respuestas</div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <a href="llenar_formulario.php?id=<?php echo $formulario['id']; ?>" 
                                           class="btn btn-primary btn-sm" 
                                           target="_blank"
                                           style="text-decoration: none;">
                                            📝 Llenar
                                        </a>
                                        <a href="../backend/ver_respuestas.php?formulario_id=<?php echo $formulario['id']; ?>" 
                                           class="btn btn-secondary btn-sm"
                                           style="text-decoration: none;">
                                            📊 Ver Estadísticas
                                        </a>
                                        <a href="editar_formulario.php?id=<?php echo $formulario['id']; ?>" 
                                           class="btn btn-warning btn-sm"
                                           style="text-decoration: none;">
                                            ✏️ Editar
                                        </a>
                                        <button onclick="confirmarEliminar(<?php echo $formulario['id']; ?>, '<?php echo htmlspecialchars(addslashes($formulario['titulo'])); ?>')" 
                                                class="btn btn-danger btn-sm">
                                            🗑️ Eliminar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <!-- Información sobre anonimato -->
        <aside role="complementary" style="margin-top: 2rem; padding: 1rem; background: #f0fdf4; border-left: 4px solid var(--color-success); border-radius: 4px;">
            <h3>🔒 Garantía de Anonimato</h3>
            <p>Este sistema está diseñado para preservar completamente el anonimato de los encuestados:</p>
            <ul style="margin-left: 1.5rem;">
                <li>No se almacenan datos identificatorios (email, IP, sesiones)</li>
                <li>Las respuestas se guardan como JSON sin vinculación a usuarios</li>
                <li>Solo se muestran estadísticas agregadas</li>
                <li>Cumple con principios de privacidad by design</li>
            </ul>
        </aside>

        <!-- Footer -->
        <footer role="contentinfo" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--color-border); text-align: center; color: var(--color-text-secondary);">
            <p>
                Sistema desarrollado con 💚 priorizando la experiencia del usuario y la privacidad de datos
            </p>
            <p style="font-size: 0.9rem;">
                Tecnologías: HTML5, CSS3, JavaScript Vanilla, PHP
            </p>
        </footer>
    </div>    <!-- JavaScript para interactividad -->
    <script src="script.js"></script>
    
    <!-- Funcionalidad específica del panel admin -->
    <script>
        // Función para confirmar eliminación de formularios
        function confirmarEliminar(id, titulo) {
            const confirmacion = confirm(
                `¿Estás seguro de que deseas eliminar el formulario "${titulo}"?\n\n` +
                `Esta acción es irreversible y eliminará:\n` +
                `• El formulario y todas sus preguntas\n` +
                `• Todas las respuestas anónimas asociadas\n\n` +
                `¿Deseas continuar?`
            );
            
            if (confirmacion) {
                // Mostrar indicador de carga
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '⏳ Eliminando...';
                btn.disabled = true;
                
                // Enviar solicitud de eliminación
                window.location.href = `../backend/eliminar_formulario.php?id=${id}&confirmar=si`;
            }
        }
        
        // Mejorar accesibilidad con JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Anunciar estado a lectores de pantalla
            const totalFormularios = <?php echo $estadisticas['total_formularios']; ?>;
            const announcement = totalFormularios > 0 
                ? `Panel administrativo cargado. ${totalFormularios} formularios disponibles.`
                : 'Panel administrativo cargado. No hay formularios creados aún.';
            
            const announcer = document.createElement('div');
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('class', 'sr-only');
            announcer.textContent = announcement;
            document.body.appendChild(announcer);
            
            setTimeout(() => announcer.remove(), 2000);
            
            // Agregar tooltips a botones de acción
            const botones = document.querySelectorAll('.btn');
            botones.forEach(btn => {
                if (btn.textContent.includes('Llenar')) {
                    btn.title = 'Abrir formulario para que los usuarios respondan';
                } else if (btn.textContent.includes('Estadísticas')) {
                    btn.title = 'Ver análisis agregado de respuestas anónimas';
                } else if (btn.textContent.includes('Editar')) {
                    btn.title = 'Modificar preguntas del formulario';
                } else if (btn.textContent.includes('Eliminar')) {
                    btn.title = 'Eliminar formulario y todas sus respuestas';
                }
            });
        });
        
        // Actualizar contador de respuestas en tiempo real (opcional)
        function actualizarEstadisticas() {
            fetch('../backend/obtener_estadisticas.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar contadores sin recargar la página
                        document.querySelector('.stats-grid .stat-card:first-child div:first-child').textContent = data.total_formularios;
                        document.querySelector('.stats-grid .stat-card:nth-child(2) div:first-child').textContent = data.total_respuestas;
                    }
                })
                .catch(error => console.log('Error actualizando estadísticas:', error));
        }
        
        // Actualizar estadísticas cada 30 segundos si hay formularios
        <?php if ($estadisticas['total_formularios'] > 0): ?>
        setInterval(actualizarEstadisticas, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
