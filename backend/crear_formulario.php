<?php
/**
 * Creación de Formularios - Sistema de Encuestas Anónimas
 * Maneja la creación de nuevos formularios con preguntas dinámicas
 * Optimizado para MySQL
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

/**
 * Clase para manejar la creación de formularios
 */
class CreadorFormulario {
    private $db;    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Validar datos del formulario
     */
    private function validarDatos($datos) {
        $errores = [];
        
        // Validar título
        if (empty($datos['titulo']) || strlen(trim($datos['titulo'])) < 3) {
            $errores[] = 'El título debe tener al menos 3 caracteres';
        }
        
        if (strlen($datos['titulo']) > 255) {
            $errores[] = 'El título no puede exceder 255 caracteres';
        }
        
        // Validar descripción
        if (empty($datos['descripcion']) || strlen(trim($datos['descripcion'])) < 10) {
            $errores[] = 'La descripción debe tener al menos 10 caracteres';
        }
        
        if (strlen($datos['descripcion']) > 1000) {
            $errores[] = 'La descripción no puede exceder 1000 caracteres';
        }
        
        // Validar preguntas
        if (empty($datos['preguntas']) || !is_array($datos['preguntas'])) {
            $errores[] = 'Debe incluir al menos una pregunta';
        } else {
            foreach ($datos['preguntas'] as $index => $pregunta) {
                $numeroP = $index + 1;
                
                // Validar texto de pregunta
                if (empty($pregunta['texto']) || strlen(trim($pregunta['texto'])) < 5) {
                    $errores[] = "La pregunta {$numeroP} debe tener al menos 5 caracteres";
                }
                
                if (strlen($pregunta['texto']) > 500) {
                    $errores[] = "La pregunta {$numeroP} no puede exceder 500 caracteres";
                }
                  // Validar tipo
                $tiposValidos = ['texto', 'escala', 'seleccion'];
                if (empty($pregunta['tipo']) || !in_array($pregunta['tipo'], $tiposValidos)) {
                    $errores[] = "La pregunta {$numeroP} debe ser tipo: texto, escala o seleccion";
                }
                  // Para tipo seleccion, se pueden incluir opciones (opcional en nuestro esquema simple)
                if ($pregunta['tipo'] === 'seleccion' && isset($pregunta['opciones'])) {
                    if (!is_array($pregunta['opciones']) || count($pregunta['opciones']) < 2) {
                        $errores[] = "La pregunta {$numeroP} de selección debe tener al menos 2 opciones";
                    }
                    
                    foreach ($pregunta['opciones'] as $opcionIndex => $opcion) {
                        if (empty(trim($opcion))) {
                            $errores[] = "La pregunta {$numeroP}, opción " . ($opcionIndex + 1) . " no puede estar vacía";
                        }
                    }
                }
                
                // Para tipo escala, validar rango (opcional en nuestro esquema simple)
                if ($pregunta['tipo'] === 'escala' && isset($pregunta['min_escala']) && isset($pregunta['max_escala'])) {
                    if ($pregunta['min_escala'] >= $pregunta['max_escala']) {
                        $errores[] = "La pregunta {$numeroP}: min_escala debe ser menor que max_escala";
                    }
                }
            }
        }
        
        return $errores;
    }
      /**
     * Crear formulario en la base de datos
     */
    public function crearFormulario($datos) {
        try {
            // Validar datos
            $errores = $this->validarDatos($datos);
            if (!empty($errores)) {
                return [
                    'exito' => false,
                    'errores' => $errores
                ];
            }
              // Iniciar transacción
            $this->db->beginTransaction();
            
            // Insertar formulario usando MySQL
            $formularioId = $this->db->query(
                "INSERT INTO formularios (titulo, descripcion) VALUES (?, ?)",
                [$datos['titulo'], $datos['descripcion']]
            );
            
            // Insertar preguntas
            foreach ($datos['preguntas'] as $orden => $pregunta) {
                // Adaptar tipo de pregunta a nuestro esquema
                $tipoAdaptado = $this->adaptarTipoPregunta($pregunta['tipo']);
                
                $this->db->query(
                    "INSERT INTO preguntas (formulario_id, texto_pregunta, tipo_respuesta) VALUES (?, ?, ?)",
                    [$formularioId, $pregunta['texto'], $tipoAdaptado]
                );
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'exito' => true,
                'formulario_id' => $formularioId,
                'mensaje' => 'Formulario creado exitosamente'
            ];
            
        } catch (Exception $e) {
            // Revertir transacción
            $this->db->rollback();
            
            error_log("Error al crear formulario: " . $e->getMessage());
            
            return [
                'exito' => false,
                'errores' => ['Error interno del servidor: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Adaptar tipos de pregunta a nuestro esquema simple
     */
    private function adaptarTipoPregunta($tipo) {
        $mapeo = [
            'texto' => 'texto',
            'textarea' => 'texto', 
            'numero' => 'texto',
            'email' => 'texto',
            'telefono' => 'texto',
            'fecha' => 'texto',
            'radio' => 'seleccion',
            'checkbox' => 'seleccion',
            'select' => 'seleccion',
            'escala' => 'escala'
        ];
        
        return $mapeo[$tipo] ?? 'texto';
    }
}

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos JSON
        $input = file_get_contents('php://input');
        $datos = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Datos JSON inválidos');
        }
        
        $creador = new CreadorFormulario();
        $resultado = $creador->crearFormulario($datos);
        
        if ($resultado['exito']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'exito' => false,
            'errores' => ['Error interno del servidor']
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'exito' => false,
        'errores' => ['Método no permitido']
    ], JSON_UNESCAPED_UNICODE);
}
?>
