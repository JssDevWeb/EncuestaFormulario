-- Sistema de Encuestas de Satisfacción Anónimas
-- Esquema de Base de Datos para MySQL
-- Garantiza anonimato absoluto sin campos identificatorios

-- Tabla principal de formularios
CREATE TABLE formularios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Preguntas asociadas a formularios
-- Soporta tres tipos: escala (1-5), texto libre, selección simple
CREATE TABLE preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    texto_pregunta TEXT NOT NULL,
    tipo_respuesta ENUM('escala','texto','seleccion') NOT NULL,
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE
);

-- Respuestas completamente anónimas
-- CRÍTICO: Sin campos que permitan identificar usuarios (email, IP, tokens)
CREATE TABLE respuestas_anonimas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    datos_json JSON NOT NULL,
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE
);

-- Datos de ejemplo para testing
INSERT INTO formularios (titulo, descripcion) VALUES 
('Encuesta de Satisfacción Académica', 'Tu opinión es importante para mejorar nuestros servicios educativos. Esta encuesta es completamente anónima.');

INSERT INTO preguntas (formulario_id, texto_pregunta, tipo_respuesta) VALUES 
(1, '¿Cómo calificarías la calidad de las clases?', 'escala'),
(1, '¿Qué te gustó más del curso?', 'texto'),
(1, '¿Recomendarías nuestros cursos?', 'seleccion');
