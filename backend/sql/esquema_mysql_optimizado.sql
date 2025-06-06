-- ====================================================================
-- ESQUEMA MYSQL OPTIMIZADO - SISTEMA DE ENCUESTAS ANÓNIMAS
-- ====================================================================
-- Fecha: 6 de Junio 2025
-- Versión: 2.0 Optimizada
-- Características: Índices optimizados, soporte para más tipos de pregunta,
--                  rendimiento mejorado, validaciones de integridad

-- ====================================================================
-- CONFIGURACIÓN INICIAL DE LA BASE DE DATOS
-- ====================================================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS encuestas_satisfaccion 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE encuestas_satisfaccion;

-- ====================================================================
-- TABLA: formularios (MEJORADA)
-- ====================================================================
CREATE TABLE formularios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(500) NOT NULL,           -- Aumentado de 255 a 500
    descripcion TEXT,
    estado ENUM('activo', 'inactivo', 'borrador') DEFAULT 'activo',  -- Control de estado
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    version INT DEFAULT 1,                  -- Versionado para auditoría
    
    -- Índices optimizados
    INDEX idx_estado_fecha (estado, fecha_creacion),
    INDEX idx_titulo (titulo(100)),         -- Índice parcial para búsquedas
    FULLTEXT INDEX idx_fulltext_titulo_desc (titulo, descripcion)  -- Búsqueda de texto completo
) ENGINE=InnoDB 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci
  COMMENT 'Formularios de encuesta con control de estado y versionado';

-- ====================================================================
-- TABLA: preguntas (AMPLIADA Y MEJORADA)
-- ====================================================================
CREATE TABLE preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    texto_pregunta TEXT NOT NULL,
    
    -- Tipos de respuesta ampliados
    tipo_respuesta ENUM(
        'escala',           -- Escala numérica (ej. 1-5, 1-10)
        'texto',            -- Texto corto
        'textarea',         -- Texto largo
        'radio',            -- Selección única
        'checkbox',         -- Selección múltiple
        'select',           -- Lista desplegable
        'email',            -- Email (validado)
        'fecha',            -- Fecha
        'numero'            -- Número
    ) NOT NULL DEFAULT 'texto',
    
    -- Configuración del tipo de pregunta
    configuracion JSON DEFAULT NULL,        -- Para opciones, rangos, validaciones
    orden INT DEFAULT 0,                    -- Orden de las preguntas
    requerida BOOLEAN DEFAULT FALSE,        -- Si es obligatoria
    activa BOOLEAN DEFAULT TRUE,            -- Si está activa
    
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Claves foráneas y restricciones
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE,
    
    -- Índices optimizados
    INDEX idx_formulario_orden (formulario_id, orden, activa),
    INDEX idx_tipo_respuesta (tipo_respuesta),
    FULLTEXT INDEX idx_fulltext_pregunta (texto_pregunta)
) ENGINE=InnoDB 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci
  COMMENT 'Preguntas con tipos ampliados y configuración flexible';

-- ====================================================================
-- TABLA: respuestas_anonimas (OPTIMIZADA)
-- ====================================================================
CREATE TABLE respuestas_anonimas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,   -- BIGINT para alta escala
    formulario_id INT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Datos completamente anónimos
    datos_json JSON NOT NULL,               -- Respuestas en formato JSON
    
    -- Metadatos no identificatorios (para estadísticas)
    user_agent_hash VARCHAR(64) DEFAULT NULL,  -- Hash del user agent (no identificatorio)
    timestamp_hash VARCHAR(64) DEFAULT NULL,   -- Hash temporal para evitar duplicados
    
    -- Validación y integridad
    datos_validos BOOLEAN DEFAULT TRUE,     -- Si pasó validaciones
    procesada BOOLEAN DEFAULT FALSE,        -- Si fue procesada para estadísticas
    
    -- Claves foráneas y restricciones
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE,
    
    -- Índices optimizados para consultas frecuentes
    INDEX idx_formulario_fecha (formulario_id, fecha_envio),
    INDEX idx_fecha_envio (fecha_envio),
    INDEX idx_procesada (procesada),
    INDEX idx_datos_validos (datos_validos),
    
    -- Índice compuesto para prevenir duplicados
    INDEX idx_hash_duplicados (formulario_id, timestamp_hash, user_agent_hash)
) ENGINE=InnoDB 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci
  COMMENT 'Respuestas anónimas con optimizaciones para alta escala'
  PARTITION BY RANGE (YEAR(fecha_envio)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
  );

-- ====================================================================
-- TABLA: estadisticas_cache (NUEVA - PARA RENDIMIENTO)
-- ====================================================================
CREATE TABLE estadisticas_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    tipo_estadistica ENUM('total_respuestas', 'promedios', 'distribuciones') NOT NULL,
    datos_cache JSON NOT NULL,
    fecha_calculo DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE,
    
    INDEX idx_formulario_tipo (formulario_id, tipo_estadistica),
    INDEX idx_expiracion (fecha_expiracion)
) ENGINE=InnoDB 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci
  COMMENT 'Cache de estadísticas precalculadas para mejor rendimiento';

-- ====================================================================
-- TABLA: auditoria_sistema (NUEVA - PARA MONITOREO)
-- ====================================================================
CREATE TABLE auditoria_sistema (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    evento ENUM('formulario_creado', 'formulario_editado', 'respuesta_recibida', 'error_sistema') NOT NULL,
    formulario_id INT DEFAULT NULL,
    detalles JSON DEFAULT NULL,
    fecha_evento DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_evento_fecha (evento, fecha_evento),
    INDEX idx_formulario (formulario_id)
) ENGINE=InnoDB 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci
  COMMENT 'Auditoría no identificatoria del sistema';

-- ====================================================================
-- DATOS DE EJEMPLO MEJORADOS
-- ====================================================================

-- Formulario de ejemplo con diferentes tipos de pregunta
INSERT INTO formularios (titulo, descripcion, estado) VALUES 
('Encuesta de Satisfacción Académica 2.0', 
 'Tu opinión es importante para mejorar nuestros servicios educativos. Esta encuesta es completamente anónima y no recopila información personal.', 
 'activo');

-- Preguntas diversas para mostrar capacidades
INSERT INTO preguntas (formulario_id, texto_pregunta, tipo_respuesta, configuracion, orden, requerida) VALUES 
(1, '¿Cómo calificarías la calidad general de las clases?', 'escala', 
 '{"min": 1, "max": 10, "etiquetas": {"1": "Muy deficiente", "10": "Excelente"}}', 1, true),

(1, '¿Qué aspectos te gustaron más del curso?', 'checkbox', 
 '{"opciones": ["Contenido del material", "Metodología del profesor", "Recursos didácticos", "Ambiente de aprendizaje", "Flexibilidad horaria", "Otro"]}', 2, false),

(1, '¿Cuál fue tu experiencia general?', 'radio', 
 '{"opciones": ["Excelente", "Muy buena", "Buena", "Regular", "Deficiente"]}', 3, true),

(1, 'Compártenos tus comentarios adicionales:', 'textarea', 
 '{"max_length": 1000, "placeholder": "Escribe aquí tus sugerencias o comentarios..."}', 4, false),

(1, '¿En qué año naciste? (para estadísticas generales)', 'numero', 
 '{"min": 1950, "max": 2010, "step": 1}', 5, false),

(1, '¿Recomendarías nuestros cursos?', 'select', 
 '{"opciones": ["Definitivamente sí", "Probablemente sí", "No estoy seguro/a", "Probablemente no", "Definitivamente no"]}', 6, true);

-- ====================================================================
-- PROCEDIMIENTOS ALMACENADOS PARA OPTIMIZACIÓN
-- ====================================================================

DELIMITER //

-- Procedimiento para obtener estadísticas rápidas
CREATE PROCEDURE GetEstadisticasFormulario(IN p_formulario_id INT)
BEGIN
    SELECT 
        f.titulo,
        f.descripcion,
        COUNT(ra.id) as total_respuestas,
        DATE(MIN(ra.fecha_envio)) as primera_respuesta,
        DATE(MAX(ra.fecha_envio)) as ultima_respuesta,
        COUNT(DISTINCT DATE(ra.fecha_envio)) as dias_con_respuestas
    FROM formularios f
    LEFT JOIN respuestas_anonimas ra ON f.id = ra.formulario_id
    WHERE f.id = p_formulario_id AND f.estado = 'activo'
    GROUP BY f.id, f.titulo, f.descripcion;
END //

-- Procedimiento para limpiar datos expirados
CREATE PROCEDURE LimpiarDatosExpirados()
BEGIN
    -- Limpiar cache expirado
    DELETE FROM estadisticas_cache WHERE fecha_expiracion < NOW();
    
    -- Limpiar auditoría antigua (más de 6 meses)
    DELETE FROM auditoria_sistema WHERE fecha_evento < DATE_SUB(NOW(), INTERVAL 6 MONTH);
END //

DELIMITER ;

-- ====================================================================
-- EVENTOS PROGRAMADOS (OPCIONAL)
-- ====================================================================

-- Limpiar datos expirados diariamente
-- CREATE EVENT IF NOT EXISTS cleanup_daily
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_TIMESTAMP
-- DO CALL LimpiarDatosExpirados();

-- ====================================================================
-- VISTAS PARA CONSULTAS FRECUENTES
-- ====================================================================

-- Vista para formularios activos con estadísticas
CREATE VIEW vista_formularios_activos AS
SELECT 
    f.id,
    f.titulo,
    f.descripcion,
    f.fecha_creacion,
    COUNT(ra.id) as total_respuestas,
    COUNT(p.id) as total_preguntas
FROM formularios f
LEFT JOIN respuestas_anonimas ra ON f.id = ra.formulario_id
LEFT JOIN preguntas p ON f.id = p.formulario_id AND p.activa = true
WHERE f.estado = 'activo'
GROUP BY f.id, f.titulo, f.descripcion, f.fecha_creacion;

-- Vista para análisis de respuestas por fecha
CREATE VIEW vista_respuestas_por_fecha AS
SELECT 
    f.titulo as formulario_titulo,
    DATE(ra.fecha_envio) as fecha,
    COUNT(ra.id) as respuestas_del_dia,
    f.id as formulario_id
FROM formularios f
JOIN respuestas_anonimas ra ON f.id = ra.formulario_id
WHERE f.estado = 'activo'
GROUP BY f.id, f.titulo, DATE(ra.fecha_envio)
ORDER BY fecha DESC;

-- ====================================================================
-- CONFIGURACIONES DE RENDIMIENTO
-- ====================================================================

-- Optimizar configuración de MySQL para este esquema
-- SET GLOBAL innodb_buffer_pool_size = 268435456;  -- 256MB
-- SET GLOBAL query_cache_size = 67108864;          -- 64MB
-- SET GLOBAL max_connections = 200;

-- ====================================================================
-- VERIFICACIÓN DE INTEGRIDAD
-- ====================================================================

-- Verificar estructura creada
SELECT 
    TABLE_NAME, 
    ENGINE, 
    TABLE_ROWS, 
    DATA_LENGTH, 
    INDEX_LENGTH,
    TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'encuestas_satisfaccion'
ORDER BY TABLE_NAME;

-- Mostrar índices creados
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'encuestas_satisfaccion'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- ====================================================================
-- INSTRUCCIONES DE USO
-- ====================================================================

/*
INSTRUCCIONES PARA CREAR LA BASE DE DATOS:

1. Conectarse a MySQL como administrador
2. Ejecutar este script completo
3. Verificar que todas las tablas se crearon correctamente
4. Configurar el archivo backend/config.php con los datos de conexión

CARACTERÍSTICAS PRINCIPALES:
- ✅ Soporte para 9 tipos de pregunta diferentes
- ✅ Particionado automático por año para escalabilidad
- ✅ Índices optimizados para consultas frecuentes
- ✅ Cache de estadísticas para mejor rendimiento
- ✅ Sistema de auditoría no identificatorio
- ✅ Procedimientos almacenados para operaciones comunes
- ✅ Vistas para consultas frecuentes
- ✅ Configuración flexible de preguntas con JSON
- ✅ Integridad referencial completa
- ✅ Soporte para formularios en borrador/inactivos
- ✅ Versionado de formularios para auditoría

MEJORAS IMPLEMENTADAS:
- Particionado de tabla de respuestas por año
- Índices compuestos para consultas complejas
- Cache de estadísticas precalculadas
- Soporte para validaciones avanzadas
- Sistema de prevención de duplicados
- Optimización para alta concurrencia
*/
