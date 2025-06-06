<?php
/**
 * Sistema de Encuestas de Satisfacción - Ver Respuestas Anónimas
 * Muestra estadísticas agregadas sin comprometer el anonimato
 */

require_once 'config.php';

// Determinar si es una petición AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    $db = Database::getInstance();
    
    $formulario_id = intval($_GET['formulario_id'] ?? 0);
    
    if ($formulario_id <= 0) {
        throw new Exception('ID de formulario inválido');
    }    // Obtener información del formulario usando prepared statements
    $stmt = $db->prepare("SELECT f.id, f.titulo, f.descripcion, 
                f.fecha_creacion,
                COUNT(r.id) as total_respuestas
         FROM formularios f
         LEFT JOIN respuestas_anonimas r ON f.id = r.formulario_id
         WHERE f.id = ?
         GROUP BY f.id, f.titulo, f.descripcion, f.fecha_creacion");
    $stmt->execute([$formulario_id]);
    $formulario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$formulario) {
        throw new Exception('Formulario no encontrado');
    }
    
    // Obtener preguntas del formulario
    $stmt = $db->prepare("SELECT id, texto_pregunta, tipo_respuesta
         FROM preguntas 
         WHERE formulario_id = ? 
         ORDER BY id");
    $stmt->execute([$formulario_id]);
    $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas por pregunta
    $estadisticas = [];
    
    foreach ($preguntas as $pregunta) {
        $pregunta_id = $pregunta['id'];
        $tipo = $pregunta['tipo_respuesta'];
        
        $estadistica = [
            'pregunta' => $pregunta,
            'total_respuestas' => 0,
            'datos' => []
        ];        // Obtener todas las respuestas JSON para este formulario
        $stmt = $db->prepare("SELECT datos_json
             FROM respuestas_anonimas 
             WHERE formulario_id = ?");
        $stmt->execute([$formulario_id]);
        $respuestas_json = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $respuestas_pregunta = [];
        
        // Extraer respuestas específicas de esta pregunta del JSON
        if (is_array($respuestas_json)) {
            foreach ($respuestas_json as $respuesta_row) {
                $datos = json_decode($respuesta_row['datos_json'], true);
                if (is_array($datos)) {
                    foreach ($datos as $respuesta_item) {
                        if (isset($respuesta_item['pregunta_id']) && 
                            $respuesta_item['pregunta_id'] == $pregunta_id &&
                            isset($respuesta_item['respuesta'])) {
                            $respuestas_pregunta[] = $respuesta_item['respuesta'];
                        }
                    }
                }
            }
        }
        
        $estadistica['total_respuestas'] = count($respuestas_pregunta);
        
        // Procesar según el tipo de pregunta
        switch ($tipo) {
            case 'texto':
                // Para texto libre, mostrar solo conteos y algunas muestras anónimas
                $longitudes = array_map('strlen', $respuestas_pregunta);
                $estadistica['datos'] = [
                    'total_respuestas' => count($respuestas_pregunta),
                    'longitud_promedio' => count($longitudes) > 0 ? round(array_sum($longitudes) / count($longitudes), 1) : 0,
                    'longitud_min' => count($longitudes) > 0 ? min($longitudes) : 0,
                    'longitud_max' => count($longitudes) > 0 ? max($longitudes) : 0,
                    // Muestras anónimas (solo primeras palabras para preservar privacidad)
                    'muestras' => array_slice(array_map(function($resp) {
                        $palabras = explode(' ', $resp);
                        return implode(' ', array_slice($palabras, 0, 3)) . (count($palabras) > 3 ? '...' : '');
                    }, $respuestas_pregunta), 0, 5)
                ];
                break;
                
            case 'seleccion':
                // Para opciones de selección, contar frecuencias
                $conteos = array_count_values($respuestas_pregunta);
                arsort($conteos);
                
                $datos_opcion = [];
                foreach ($conteos as $opcion => $count) {
                    $datos_opcion[] = [
                        'opcion' => $opcion,
                        'count' => $count,
                        'porcentaje' => $estadistica['total_respuestas'] > 0 ? 
                            round($count / $estadistica['total_respuestas'] * 100, 1) : 0
                    ];
                }
                
                $estadistica['datos'] = $datos_opcion;
                break;
                
            case 'escala':
                // Para escalas numéricas
                $valores_numericos = array_filter($respuestas_pregunta, 'is_numeric');
                $valores_numericos = array_map('floatval', $valores_numericos);
                
                if (!empty($valores_numericos)) {
                    $conteos = array_count_values($valores_numericos);
                    ksort($conteos);
                    
                    $datos_escala = [];
                    foreach ($conteos as $valor => $count) {
                        $datos_escala[] = [
                            'valor' => $valor,
                            'count' => $count,
                            'porcentaje' => round($count / count($valores_numericos) * 100, 1)
                        ];
                    }
                    
                    $estadistica['datos'] = [
                        'distribución' => $datos_escala,
                        'promedio' => round(array_sum($valores_numericos) / count($valores_numericos), 2),
                        'min' => min($valores_numericos),
                        'max' => max($valores_numericos),
                        'total_respuestas' => count($valores_numericos)
                    ];
                } else {
                    $estadistica['datos'] = [
                        'distribución' => [],
                        'promedio' => 0,
                        'min' => 0,
                        'max' => 0,
                        'total_respuestas' => 0
                    ];
                }
                break;
                
            default:
                // Tipo desconocido, tratarlo como texto
                $estadistica['datos'] = [
                    'total_respuestas' => count($respuestas_pregunta),
                    'muestras' => array_slice($respuestas_pregunta, 0, 5)
                ];
                break;
        }
        
        $estadisticas[] = $estadistica;
    }
      // Calcular estadísticas generales de tiempo de respuesta
    $tiempos_respuesta = $db->query("SELECT fecha_envio
         FROM respuestas_anonimas 
         WHERE formulario_id = $formulario_id
         ORDER BY fecha_envio DESC");
    
    // Análisis temporal (sin identificar usuarios)
    $analisis_temporal = [
        'respuestas_por_hora' => [],
        'respuestas_por_dia' => [],
        'ultima_respuesta' => (!empty($tiempos_respuesta) && is_array($tiempos_respuesta)) ? $tiempos_respuesta[0]['fecha_envio'] : null
    ];
    
    if (is_array($tiempos_respuesta)) {
        foreach ($tiempos_respuesta as $tiempo) {
            $fecha = new DateTime($tiempo['fecha_envio']);
            $hora = intval($fecha->format('H'));
            $dia = intval($fecha->format('w')); // 0=domingo, 1=lunes, etc.
            
            $analisis_temporal['respuestas_por_hora'][$hora] = ($analisis_temporal['respuestas_por_hora'][$hora] ?? 0) + 1;
            $analisis_temporal['respuestas_por_dia'][$dia] = ($analisis_temporal['respuestas_por_dia'][$dia] ?? 0) + 1;
        }
    }
    
    $resultado = [
        'formulario' => $formulario,
        'preguntas' => count($preguntas),
        'estadisticas' => $estadisticas,
        'analisis_temporal' => $analisis_temporal
    ];
    
    if ($isAjax) {
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    $error = $e->getMessage();
}

// Función auxiliar para calcular mediana
function calcularMediana($valores) {
    sort($valores);
    $count = count($valores);
    
    if ($count === 0) return 0;
    
    if ($count % 2 === 0) {
        return ($valores[$count/2 - 1] + $valores[$count/2]) / 2;
    } else {
        return $valores[floor($count/2)];
    }
}

// Si no es AJAX, mostrar página HTML
if (!$isAjax):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas del Formulario - Sistema de Encuestas</title>
    <link rel="stylesheet" href="../frontend/estilos.css">
    <style>
        .estadistica-item {
            background: var(--color-fondo-secundario);
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .grafico-barras {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .barra-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .barra-etiqueta {
            min-width: 120px;
            font-size: 0.9rem;
        }
        
        .barra-visual {
            flex: 1;
            height: 24px;
            background: var(--color-fondo-terciario);
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }
        
        .barra-relleno {
            height: 100%;
            background: var(--color-primario);
            transition: width 0.3s ease;
        }
        
        .barra-texto {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            font-weight: bold;
            color: var(--color-texto);
        }
        
        .resumen-estadisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .metrica {
            text-align: center;
            padding: 1rem;
            background: var(--color-fondo-secundario);
            border-radius: 8px;
            border: 1px solid var(--color-borde);
        }
        
        .metrica-valor {
            font-size: 2rem;
            font-weight: bold;
            color: var(--color-primario);
            display: block;
        }
        
        .metrica-etiqueta {
            font-size: 0.9rem;
            color: var(--color-texto-secundario);
            margin-top: 0.5rem;
        }
        
        .info-formulario {
            background: var(--color-fondo-secundario);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--color-borde);
            margin-bottom: 2rem;
        }
        
        .info-formulario h2 {
            margin-top: 0;
            color: var(--color-primario);
        }
        
        .muestra-texto {
            background: var(--color-fondo-terciario);
            padding: 0.5rem;
            border-radius: 4px;
            margin: 0.25rem 0;
            font-style: italic;
            border-left: 3px solid var(--color-primario);
        }
        
        @media (max-width: 768px) {
            .barra-item {
                flex-direction: column;
                align-items: stretch;
                gap: 0.25rem;
            }
            
            .barra-etiqueta {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="breadcrumb" aria-label="Navegación">
            <a href="../frontend/index_admin.php">Panel Admin</a>
            <span aria-hidden="true">→</span>
            <a href="listar_formularios.php">Formularios</a>
            <span aria-hidden="true">→</span>
            <span aria-current="page">Estadísticas</span>
        </nav>

        <header class="header-seccion">
            <h1>Estadísticas del Formulario</h1>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-error" role="alert">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>
            <div id="contenido-estadisticas">
                <div class="loading" role="status" aria-live="polite">
                    Cargando estadísticas...
                </div>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="listar_formularios.php" class="btn btn-secondary">
                ← Volver a Formularios
            </a>
        </div>
    </div>

    <script src="../frontend/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formularioId = <?= $formulario_id ?>;
            cargarEstadisticas(formularioId);
        });

        async function cargarEstadisticas(formularioId) {
            try {
                const response = await fetch(`ver_respuestas.php?formulario_id=${formularioId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Error al cargar estadísticas');
                }

                const data = await response.json();
                mostrarEstadisticas(data);

            } catch (error) {
                document.getElementById('contenido-estadisticas').innerHTML = `
                    <div class="alert alert-error" role="alert">
                        Error al cargar estadísticas: ${error.message}
                    </div>
                `;
            }
        }

        function mostrarEstadisticas(data) {
            const contenedor = document.getElementById('contenido-estadisticas');
            
            let html = `
                <div class="info-formulario">
                    <h2>${escapeHtml(data.formulario.titulo)}</h2>
                    ${data.formulario.descripcion ? `<p>${escapeHtml(data.formulario.descripcion)}</p>` : ''}
                    <p><strong>Fecha de creación:</strong> ${new Date(data.formulario.fecha_creacion).toLocaleDateString()}</p>
                </div>

                <div class="resumen-estadisticas">
                    <div class="metrica">
                        <span class="metrica-valor">${data.formulario.total_respuestas}</span>
                        <div class="metrica-etiqueta">Total Respuestas</div>
                    </div>
                    <div class="metrica">
                        <span class="metrica-valor">${data.preguntas}</span>
                        <div class="metrica-etiqueta">Preguntas</div>
                    </div>
                    <div class="metrica">
                        <span class="metrica-valor">${data.analisis_temporal.ultima_respuesta ? 
                            new Date(data.analisis_temporal.ultima_respuesta).toLocaleDateString() : 'N/A'}</span>
                        <div class="metrica-etiqueta">Última Respuesta</div>
                    </div>
                </div>

                <h3>Estadísticas por Pregunta</h3>
            `;

            data.estadisticas.forEach((estadistica, index) => {
                html += generarEstadisticaPregunta(estadistica, index + 1);
            });

            contenedor.innerHTML = html;
        }

        function generarEstadisticaPregunta(estadistica, numero) {
            const pregunta = estadistica.pregunta;
            const datos = estadistica.datos;
            
            let html = `
                <div class="estadistica-item">
                    <h4>Pregunta ${numero}: ${escapeHtml(pregunta.texto_pregunta)}</h4>
                    <p><strong>Tipo:</strong> ${pregunta.tipo_respuesta}</p>
                    <p><strong>Total respuestas:</strong> ${estadistica.total_respuestas}</p>
            `;

            switch (pregunta.tipo_respuesta) {
                case 'texto':
                    if (datos.total_respuestas > 0) {
                        html += `
                            <div class="estadisticas-texto">
                                <p><strong>Longitud promedio:</strong> ${datos.longitud_promedio} caracteres</p>
                                <p><strong>Rango:</strong> ${datos.longitud_min} - ${datos.longitud_max} caracteres</p>
                                ${datos.muestras && datos.muestras.length > 0 ? `
                                    <h5>Muestras de respuestas:</h5>
                                    ${datos.muestras.map(muestra => 
                                        `<div class="muestra-texto">${escapeHtml(muestra)}</div>`
                                    ).join('')}
                                ` : ''}
                            </div>
                        `;
                    } else {
                        html += '<p><em>No hay respuestas para esta pregunta.</em></p>';
                    }
                    break;

                case 'seleccion':
                    if (Array.isArray(datos) && datos.length > 0) {
                        html += `<div class="grafico-barras">`;
                        datos.forEach(opcion => {
                            html += `
                                <div class="barra-item">
                                    <div class="barra-etiqueta">${escapeHtml(opcion.opcion)}</div>
                                    <div class="barra-visual">
                                        <div class="barra-relleno" style="width: ${opcion.porcentaje}%"></div>
                                        <div class="barra-texto">${opcion.count} (${opcion.porcentaje}%)</div>
                                    </div>
                                </div>
                            `;
                        });
                        html += `</div>`;
                    } else {
                        html += '<p><em>No hay respuestas para esta pregunta.</em></p>';
                    }
                    break;

                case 'escala':
                    if (datos.total_respuestas > 0) {
                        html += `
                            <div class="estadisticas-escala">
                                <p><strong>Promedio:</strong> ${datos.promedio}</p>
                                <p><strong>Rango:</strong> ${datos.min} - ${datos.max}</p>
                                <h5>Distribución:</h5>
                                <div class="grafico-barras">
                        `;
                        
                        datos.distribución.forEach(item => {
                            html += `
                                <div class="barra-item">
                                    <div class="barra-etiqueta">Valor ${item.valor}</div>
                                    <div class="barra-visual">
                                        <div class="barra-relleno" style="width: ${item.porcentaje}%"></div>
                                        <div class="barra-texto">${item.count} (${item.porcentaje}%)</div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `</div></div>`;
                    } else {
                        html += '<p><em>No hay respuestas para esta pregunta.</em></p>';
                    }
                    break;

                default:
                    html += '<p><em>Tipo de pregunta no reconocido.</em></p>';
                    break;
            }

            html += `</div>`;
            return html;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
<?php endif; ?>
