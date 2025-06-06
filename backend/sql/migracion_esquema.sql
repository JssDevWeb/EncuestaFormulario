-- ====================================================================
-- SCRIPT DE MIGRACIÓN: ESQUEMA BÁSICO → ESQUEMA OPTIMIZADO
-- ====================================================================
-- Fecha: 6 de Junio 2025
-- Propósito: Migrar datos existentes del esquema básico al optimizado
-- IMPORTANTE: Hacer backup antes de ejecutar este script

-- ====================================================================
-- VERIFICACIÓN DE ESQUEMA EXISTENTE
-- ====================================================================

-- Verificar si existe la base de datos actual
SELECT 
    SCHEMA_NAME as 'Base de Datos Existente',
    DEFAULT_CHARACTER_SET_NAME as 'Charset',
    DEFAULT_COLLATION_NAME as 'Collation'
FROM INFORMATION_SCHEMA.SCHEMATA 
WHERE SCHEMA_NAME = 'encuestas_satisfaccion';

-- Verificar tablas existentes
SELECT 
    TABLE_NAME as 'Tabla',
    TABLE_ROWS as 'Registros',
    ENGINE as 'Motor',
    TABLE_COMMENT as 'Comentario'
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'encuestas_satisfaccion'
ORDER BY TABLE_NAME;

-- ====================================================================
-- BACKUP AUTOMÁTICO (OPCIONAL)
-- ====================================================================

-- Crear tabla de backup para formularios
CREATE TABLE IF NOT EXISTS backup_formularios_migration AS 
SELECT *, NOW() as backup_fecha FROM formularios;

-- Crear tabla de backup para preguntas
CREATE TABLE IF NOT EXISTS backup_preguntas_migration AS 
SELECT *, NOW() as backup_fecha FROM preguntas;

-- Crear tabla de backup para respuestas
CREATE TABLE IF NOT EXISTS backup_respuestas_migration AS 
SELECT *, NOW() as backup_fecha FROM respuestas_anonimas;

-- ====================================================================
-- MIGRACIÓN PASO A PASO
-- ====================================================================

-- Paso 1: Agregar nuevas columnas a formularios (si no existen)
ALTER TABLE formularios 
ADD COLUMN IF NOT EXISTS estado ENUM('activo', 'inactivo', 'borrador') DEFAULT 'activo' AFTER descripcion,
ADD COLUMN IF NOT EXISTS fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER fecha_creacion,
ADD COLUMN IF NOT EXISTS version INT DEFAULT 1 AFTER fecha_modificacion;

-- Actualizar valores por defecto para registros existentes
UPDATE formularios 
SET estado = 'activo', version = 1 
WHERE estado IS NULL OR version IS NULL;

-- Paso 2: Modificar columna titulo en formularios (ampliar tamaño)
ALTER TABLE formularios 
MODIFY COLUMN titulo VARCHAR(500) NOT NULL;

-- Paso 3: Actualizar tabla preguntas
-- Agregar nuevas columnas
ALTER TABLE preguntas 
ADD COLUMN IF NOT EXISTS configuracion JSON DEFAULT NULL AFTER tipo_respuesta,
ADD COLUMN IF NOT EXISTS orden INT DEFAULT 0 AFTER configuracion,
ADD COLUMN IF NOT EXISTS requerida BOOLEAN DEFAULT FALSE AFTER orden,
ADD COLUMN IF NOT EXISTS activa BOOLEAN DEFAULT TRUE AFTER requerida,
ADD COLUMN IF NOT EXISTS fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP AFTER activa;

-- Actualizar enum de tipo_respuesta (preservando datos existentes)
ALTER TABLE preguntas 
MODIFY COLUMN tipo_respuesta ENUM(
    'escala', 'texto', 'textarea', 'radio', 'checkbox', 
    'select', 'email', 'fecha', 'numero', 'seleccion'
) NOT NULL DEFAULT 'texto';

-- Migrar datos: 'seleccion' → 'radio'
UPDATE preguntas 
SET tipo_respuesta = 'radio' 
WHERE tipo_respuesta = 'seleccion';

-- Actualizar enum final (eliminar 'seleccion' obsoleto)
ALTER TABLE preguntas 
MODIFY COLUMN tipo_respuesta ENUM(
    'escala', 'texto', 'textarea', 'radio', 'checkbox', 
    'select', 'email', 'fecha', 'numero'
) NOT NULL DEFAULT 'texto';

-- Asignar orden a preguntas existentes
SET @row_number = 0;
UPDATE preguntas 
SET orden = (@row_number:=@row_number + 1)
WHERE orden = 0
ORDER BY formulario_id, id;

-- Paso 4: Optimizar tabla respuestas_anonimas
-- Cambiar ID a BIGINT para escalabilidad
ALTER TABLE respuestas_anonimas 
MODIFY COLUMN id BIGINT AUTO_INCREMENT;

-- Agregar nuevas columnas de metadatos
ALTER TABLE respuestas_anonimas 
ADD COLUMN IF NOT EXISTS user_agent_hash VARCHAR(64) DEFAULT NULL AFTER datos_json,
ADD COLUMN IF NOT EXISTS timestamp_hash VARCHAR(64) DEFAULT NULL AFTER user_agent_hash,
ADD COLUMN IF NOT EXISTS datos_validos BOOLEAN DEFAULT TRUE AFTER timestamp_hash,
ADD COLUMN IF NOT EXISTS procesada BOOLEAN DEFAULT FALSE AFTER datos_validos;

-- Marcar datos existentes como válidos y procesados
UPDATE respuestas_anonimas 
SET datos_validos = TRUE, procesada = FALSE 
WHERE datos_validos IS NULL;

-- ====================================================================
-- CREACIÓN DE ÍNDICES OPTIMIZADOS
-- ====================================================================

-- Índices para tabla formularios
CREATE INDEX IF NOT EXISTS idx_estado_fecha ON formularios(estado, fecha_creacion);
CREATE INDEX IF NOT EXISTS idx_titulo ON formularios(titulo(100));
CREATE FULLTEXT INDEX IF NOT EXISTS idx_fulltext_titulo_desc ON formularios(titulo, descripcion);

-- Índices para tabla preguntas
CREATE INDEX IF NOT EXISTS idx_formulario_orden ON preguntas(formulario_id, orden, activa);
CREATE INDEX IF NOT EXISTS idx_tipo_respuesta ON preguntas(tipo_respuesta);
CREATE FULLTEXT INDEX IF NOT EXISTS idx_fulltext_pregunta ON preguntas(texto_pregunta);

-- Índices para tabla respuestas_anonimas
CREATE INDEX IF NOT EXISTS idx_formulario_fecha ON respuestas_anonimas(formulario_id, fecha_envio);
CREATE INDEX IF NOT EXISTS idx_fecha_envio ON respuestas_anonimas(fecha_envio);
CREATE INDEX IF NOT EXISTS idx_procesada ON respuestas_anonimas(procesada);
CREATE INDEX IF NOT EXISTS idx_datos_validos ON respuestas_anonimas(datos_validos);
CREATE INDEX IF NOT EXISTS idx_hash_duplicados ON respuestas_anonimas(formulario_id, timestamp_hash, user_agent_hash);

-- ====================================================================
-- CREACIÓN DE NUEVAS TABLAS
-- ====================================================================

-- Tabla de cache de estadísticas
CREATE TABLE IF NOT EXISTS estadisticas_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    tipo_estadistica ENUM('total_respuestas', 'promedios', 'distribuciones') NOT NULL,
    datos_cache JSON NOT NULL,
    fecha_calculo DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE,
    INDEX idx_formulario_tipo (formulario_id, tipo_estadistica),
    INDEX idx_expiracion (fecha_expiracion)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla de auditoría
CREATE TABLE IF NOT EXISTS auditoria_sistema (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    evento ENUM('formulario_creado', 'formulario_editado', 'respuesta_recibida', 'error_sistema') NOT NULL,
    formulario_id INT DEFAULT NULL,
    detalles JSON DEFAULT NULL,
    fecha_evento DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_evento_fecha (evento, fecha_evento),
    INDEX idx_formulario (formulario_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ====================================================================
-- MIGRACIÓN DE DATOS DE EJEMPLO
-- ====================================================================

-- Actualizar preguntas existentes con configuración
UPDATE preguntas 
SET configuracion = CASE 
    WHEN tipo_respuesta = 'escala' AND configuracion IS NULL THEN 
        '{"min": 1, "max": 5, "etiquetas": {"1": "Muy malo", "5": "Excelente"}}'
    WHEN tipo_respuesta = 'radio' AND texto_pregunta LIKE '%recomend%' THEN 
        '{"opciones": ["Sí", "No", "Tal vez"]}'
    ELSE configuracion
END
WHERE configuracion IS NULL;

-- ====================================================================
-- CREACIÓN DE PROCEDIMIENTOS Y VISTAS
-- ====================================================================

DELIMITER //

-- Procedimiento para estadísticas
DROP PROCEDURE IF EXISTS GetEstadisticasFormulario //
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

-- Procedimiento para limpieza
DROP PROCEDURE IF EXISTS LimpiarDatosExpirados //
CREATE PROCEDURE LimpiarDatosExpirados()
BEGIN
    DELETE FROM estadisticas_cache WHERE fecha_expiracion < NOW();
    DELETE FROM auditoria_sistema WHERE fecha_evento < DATE_SUB(NOW(), INTERVAL 6 MONTH);
END //

DELIMITER ;

-- Crear vistas optimizadas
CREATE OR REPLACE VIEW vista_formularios_activos AS
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

CREATE OR REPLACE VIEW vista_respuestas_por_fecha AS
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
-- VALIDACIÓN FINAL
-- ====================================================================

-- Verificar estructura migrada
SELECT 
    'FORMULARIOS' as Tabla,
    COUNT(*) as Registros,
    COUNT(CASE WHEN estado = 'activo' THEN 1 END) as Activos
FROM formularios
UNION ALL
SELECT 
    'PREGUNTAS' as Tabla,
    COUNT(*) as Registros,
    COUNT(CASE WHEN activa = true THEN 1 END) as Activas
FROM preguntas
UNION ALL
SELECT 
    'RESPUESTAS' as Tabla,
    COUNT(*) as Registros,
    COUNT(CASE WHEN datos_validos = true THEN 1 END) as Validas
FROM respuestas_anonimas;

-- Verificar índices creados
SELECT 
    TABLE_NAME as Tabla,
    COUNT(*) as Total_Indices
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'encuestas_satisfaccion'
GROUP BY TABLE_NAME
ORDER BY TABLE_NAME;

-- Registrar migración en auditoría
INSERT INTO auditoria_sistema (evento, detalles) 
VALUES ('error_sistema', '{"tipo": "migracion_completada", "version": "2.0", "fecha": "2025-06-06"}');

-- ====================================================================
-- LIMPIEZA OPCIONAL DE BACKUPS
-- ====================================================================

-- Descomentar las siguientes líneas para eliminar backups después de verificar
-- DROP TABLE IF EXISTS backup_formularios_migration;
-- DROP TABLE IF EXISTS backup_preguntas_migration;
-- DROP TABLE IF EXISTS backup_respuestas_migration;

SELECT 'MIGRACIÓN COMPLETADA EXITOSAMENTE' as Estado;
