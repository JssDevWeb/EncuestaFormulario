<?php
/**
 * Configuración de Base de Datos - Archivo de Ejemplo
 * 
 * INSTRUCCIONES:
 * 1. Copia este archivo como 'config.php'
 * 2. Edita las configuraciones según tu entorno
 * 3. Ejecuta el script SQL: backend/sql/esquema_mysql.sql
 * 
 * BASE DE DATOS SOPORTADA:
 * - MySQL 5.7+ / MariaDB 10.3+
 */

// =============================================================================
// CONFIGURACIÓN DE BASE DE DATOS MYSQL
// =============================================================================

// Configuración para MySQL/WAMP/XAMPP
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'encuestas_satisfaccion');
define('DB_USER', 'root');
define('DB_PASS', ''); // Cambia por tu contraseña de MySQL

// =============================================================================
// CONFIGURACIÓN DE APLICACIÓN
// =============================================================================

// Zona horaria
define('APP_TIMEZONE', 'America/Mexico_City');

// Modo debug (cambiar a false en producción)
define('DEBUG_MODE', true);

// Configuración de logs
define('LOG_ERRORS', true);

// =============================================================================
// LÍMITES DE APLICACIÓN
// =============================================================================

// Límites de formularios
define('MAX_PREGUNTAS_POR_FORMULARIO', 50);
define('MAX_OPCIONES_POR_PREGUNTA', 20);

// Límites de respuestas
define('MAX_LONGITUD_TEXTO', 1000);
define('MAX_LONGITUD_TEXTAREA', 5000);

// Hash salt para respuestas (cambiar en producción)
define('RESPONSE_SALT', 'change_this_in_production_2024');

?>
