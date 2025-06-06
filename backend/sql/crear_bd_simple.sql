-- ====================================================================
-- SCRIPT SIMPLE: CREAR BASE DE DATOS DESDE CERO
-- ====================================================================
-- Ejecuta este script si NO tienes datos existentes
-- Si ya tienes datos, usa migracion_esquema.sql

DROP DATABASE IF EXISTS encuestas_satisfaccion;
CREATE DATABASE encuestas_satisfaccion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE encuestas_satisfaccion;

-- Formularios principales
CREATE TABLE formularios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(500) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo', 'borrador') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    version INT DEFAULT 1,
    
    INDEX idx_estado_fecha (estado, fecha_creacion),
    INDEX idx_titulo (titulo(100))
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Preguntas con tipos ampliados
CREATE TABLE preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    texto_pregunta TEXT NOT NULL,
    tipo_respuesta ENUM('escala','texto','textarea','radio','checkbox','select','email','fecha','numero') NOT NULL DEFAULT 'texto',
    configuracion JSON DEFAULT NULL,
    orden INT DEFAULT 0,
    requerida BOOLEAN DEFAULT FALSE,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE,
    INDEX idx_formulario_orden (formulario_id, orden, activa)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Respuestas anónimas optimizadas
CREATE TABLE respuestas_anonimas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    datos_json JSON NOT NULL,
    user_agent_hash VARCHAR(64) DEFAULT NULL,
    timestamp_hash VARCHAR(64) DEFAULT NULL,
    datos_validos BOOLEAN DEFAULT TRUE,
    procesada BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE,
    INDEX idx_formulario_fecha (formulario_id, fecha_envio),
    INDEX idx_procesada (procesada)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Cache de estadísticas
CREATE TABLE estadisticas_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    tipo_estadistica ENUM('total_respuestas', 'promedios', 'distribuciones') NOT NULL,
    datos_cache JSON NOT NULL,
    fecha_calculo DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE,
    INDEX idx_formulario_tipo (formulario_id, tipo_estadistica)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Datos de ejemplo
INSERT INTO formularios (titulo, descripcion, estado) VALUES 
('Encuesta de Satisfacción Académica', 'Tu opinión es importante. Esta encuesta es completamente anónima.', 'activo');

INSERT INTO preguntas (formulario_id, texto_pregunta, tipo_respuesta, configuracion, orden, requerida) VALUES 
(1, '¿Cómo calificarías la calidad de las clases?', 'escala', '{"min": 1, "max": 10}', 1, true),
(1, '¿Qué te gustó más?', 'checkbox', '{"opciones": ["Contenido", "Profesor", "Metodología"]}', 2, false),
(1, '¿Recomendarías el curso?', 'radio', '{"opciones": ["Sí", "No", "Tal vez"]}', 3, true),
(1, 'Comentarios adicionales:', 'textarea', '{"max_length": 1000}', 4, false);

SELECT 'BASE DE DATOS CREADA EXITOSAMENTE' as Estado;
