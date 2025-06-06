# Sistema de Encuestas de Satisfacción Anónimas ✅ MIGRADO A MYSQL

## 📋 Descripción del Proyecto

Este proyecto implementa un sistema CRUD completo para encuestas de satisfacción que garantiza el **anonimato total** de los usuarios, utilizando únicamente tecnologías nativas: **HTML5, CSS3, JavaScript vanilla y PHP** con **MySQL**.

### 🎉 Estado Actual: **SISTEMA MIGRADO A MYSQL**
- ✅ **MySQL configurado y operativo**
- ✅ **Todas las funcionalidades implementadas y probadas**
- ✅ **Panel administrativo funcional**
- ✅ **Sistema de estadísticas agregadas funcionando**
- ✅ **Anonimato absoluto garantizado técnicamente**
- ✅ **Completamente libre de PostgreSQL**

### 🎯 Objetivo Principal

Crear un sistema de encuestas que priorice:
- **Simplicidad**: Formularios cortos y directos
- **Accesibilidad**: Cumplimiento WCAG AA para inclusividad total
- **Anonimato Absoluto**: Imposibilidad técnica de identificar respondientes
- **Experiencia UX Óptima**: Basada en estudios sobre abandono de encuestas

## 🏗️ Arquitectura del Sistema - MySQL

### Modelo de Datos MySQL

```sql
-- Tabla principal de formularios
CREATE TABLE formularios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Preguntas asociadas a formularios
CREATE TABLE preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    texto_pregunta TEXT NOT NULL,
    tipo_respuesta ENUM('escala','texto','seleccion') NOT NULL,
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE
);

-- Respuestas completamente anónimas
CREATE TABLE respuestas_anonimas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    datos_json JSON NOT NULL,
    FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE
);

-- Índices para optimización MySQL
CREATE INDEX idx_preguntas_formulario_id ON preguntas(formulario_id);
CREATE INDEX idx_respuestas_formulario_id ON respuestas_anonimas(formulario_id);
CREATE INDEX idx_respuestas_fecha ON respuestas_anonimas(fecha_envio);
```

### Estructura de Archivos

```
/crud_encuestas_anonimas/
├─ /backend/
│   ├─ config.php                 # Configuración MySQL
│   ├─ crear_formulario.php       # Creación de formularios
│   ├─ editar_formulario.php      # Edición de formularios
│   ├─ eliminar_formulario.php    # Eliminación de formularios
│   ├─ listar_formularios.php     # Listado administrativo
│   ├─ ver_respuestas.php         # Estadísticas anónimas
│   ├─ enviar_respuesta.php       # Endpoint para envío
│   ├─ eliminar_respuesta.php     # Eliminación de respuestas
│   └─ /sql/
│       └─ esquema_mysql.sql      # Script MySQL
│
├─ /frontend/
│   ├─ index_admin.php            # Panel administrativo
│   ├─ llenar_formulario.php      # Interfaz de encuesta
│   ├─ estilos.css               # Estilos accesibles
│   └─ script.js                 # Interactividad vanilla JS
│
├─ README.md
└─ .gitignore
```

## 🚀 Funcionalidades Principales ✅ TODAS IMPLEMENTADAS

### Para Administradores
- ✅ **Crear formularios** con preguntas de múltiples tipos
- ✅ **Editar formularios** existentes manteniendo respuestas
- ✅ **Eliminar formularios** y datos asociados
- ✅ **Ver estadísticas** agregadas y anónimas
- ✅ **Gestión completa** sin identificar respondientes

### Para Encuestados
- ✅ **Formularios simples** con indicador de progreso
- ✅ **Navegación accesible** por teclado y lectores de pantalla
- ✅ **Validación en tiempo real** con mensajes claros
- ✅ **Opciones flexibles** incluyendo "No sé/Prefiero no responder"
- ✅ **Anonimato garantizado** técnicamente

## 🛠️ Instalación y Configuración MySQL

### Prerrequisitos
- **PHP 7.4+** con extensiones mysqli y json
- **MySQL 5.7+** o **MariaDB 10.3+**
- **Servidor web** (Apache/Nginx) con mod_rewrite habilitado

### Instalación Paso a Paso (WAMP + MySQL)

#### 1. Preparar WAMP
```cmd
# Descargar WAMP desde wampserver.com
# Instalar en C:\wamp64\
# Verificar que Apache, PHP y MySQL estén funcionando
```

#### 2. Crear Base de Datos MySQL
```sql
# Conectar con phpMyAdmin o comando mysql:
mysql -u root -p

# Crear la base de datos:
CREATE DATABASE encuestas_satisfaccion;

# Usar la base creada:
USE encuestas_satisfaccion;

# Ejecutar el script del esquema:
SOURCE backend/sql/esquema_mysql.sql;
```

#### 3. Configurar el Proyecto
```php
# Copiar proyecto a C:\wamp64\www\Proyecto satisfactorio\
# Editar backend/config.php:

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Tu usuario MySQL
define('DB_PASS', 'tu_contraseña'); // Tu contraseña MySQL
define('DB_NAME', 'encuestas_satisfaccion');
define('DB_PORT', 3306);
```

#### 4. Verificar Instalación
```url
# Acceder al panel administrativo:
http://localhost/Proyecto%20satisfactorio/frontend/index_admin.php

# Si ves el panel sin errores, ¡la migración fue exitosa!
```

## 🔒 Garantías de Anonimato

### Nivel Técnico ✅ IMPLEMENTADO
- ✅ **Sin campos identificatorios**: No email, usuario, IP o tokens en BD
- ✅ **JSON puro**: Solo se almacenan respuestas sin metadatos de usuario
- ✅ **Sin cookies de sesión**: Para el llenado de formularios
- ✅ **Validación server-side**: Sin exponer lógica que permita trazabilidad

### Nivel de Datos ✅ VERIFICADO
- ✅ **Agregación estadística**: Solo promedios y conteos sin respuestas individuales
- ✅ **Timestamps genéricos**: Solo fecha/hora de envío, no sesiones
- ✅ **Sin logs identificatorios**: Configuración de servidor para no guardar IPs

## 🔧 Características MySQL Específicas

### Ventajas de MySQL para este Proyecto
- **ENUM Types**: Validación nativa de tipos de respuesta
- **JSON Support**: Almacenamiento eficiente de respuestas anónimas
- **AUTO_INCREMENT**: Simplicidad en generación de IDs
- **Performance**: Optimizado para consultas de lectura frecuente
- **Compatibilidad**: Amplio soporte en hosting compartido

### Optimizaciones MySQL Implementadas
- **Índices estratégicos** en foreign keys y fechas
- **CASCADE DELETE** para mantener integridad referencial
- **JSON validation** en nivel de aplicación
- **Prepared statements** para prevenir SQL injection

## 📊 URLs Principales

- **Panel Admin**: `http://localhost/Proyecto%20satisfactorio/frontend/index_admin.php`
- **Crear Formulario**: `http://localhost/Proyecto%20satisfactorio/frontend/crear_formulario.php`
- **Ver Estadísticas**: `http://localhost/Proyecto%20satisfactorio/backend/ver_respuestas.php?formulario_id=[ID]`
- **Llenar Formulario**: `http://localhost/Proyecto%20satisfactorio/frontend/llenar_formulario.php?id=[ID]`
- **Editar Formulario**: `http://localhost/Proyecto%20satisfactorio/frontend/editar_formulario.php?id=[ID]`

## 🧪 Testing y Validación

### Casos de Prueba MySQL
1. **Conexión**: Verificar conectividad con credenciales MySQL
2. **CRUD Completo**: Crear, leer, actualizar y eliminar formularios
3. **Validación ENUM**: Verificar tipos de respuesta válidos
4. **JSON Storage**: Probar almacenamiento y recuperación de respuestas
5. **Integridad Referencial**: Verificar CASCADE DELETE

### Comandos de Verificación
```sql
-- Verificar estructura de tablas
DESCRIBE formularios;
DESCRIBE preguntas;
DESCRIBE respuestas_anonimas;

-- Verificar datos de prueba
SELECT COUNT(*) FROM formularios;
SELECT COUNT(*) FROM preguntas;
SELECT COUNT(*) FROM respuestas_anonimas;

-- Verificar integridad
SELECT f.titulo, COUNT(p.id) as preguntas, COUNT(r.id) as respuestas
FROM formularios f
LEFT JOIN preguntas p ON f.id = p.formulario_id
LEFT JOIN respuestas_anonimas r ON f.id = r.formulario_id
GROUP BY f.id, f.titulo;
```

## 📞 Resolución de Problemas MySQL

### Errores Comunes y Soluciones

#### ❌ Error de Conexión MySQL
```
Solution: Verificar credenciales en config.php
- DB_HOST: 'localhost' (o '127.0.0.1')
- DB_USER: Usuario MySQL correcto
- DB_PASS: Contraseña MySQL correcta
- DB_NAME: Base de datos existe
```

#### ❌ Tabla no existe
```
Solution: Ejecutar esquema_mysql.sql
mysql -u root -p encuestas_satisfaccion < backend/sql/esquema_mysql.sql
```

#### ❌ Error JSON
```
Solution: Verificar versión MySQL >= 5.7 
MySQL 5.6 y anterior no soportan tipo JSON nativo
```

#### ❌ Error ENUM
```
Solution: Verificar valores válidos: 'escala', 'texto', 'seleccion'
Cualquier otro valor será rechazado por MySQL
```

## 🎉 MIGRACIÓN A MYSQL COMPLETADA

### ✅ Cambios Implementados:
- **Eliminadas todas las referencias a PostgreSQL**
- **Configuración simplificada solo para MySQL**
- **Clase Database optimizada para MySQL**
- **Esquema SQL adaptado a sintaxis MySQL**
- **Validaciones específicas para tipos MySQL**

### 📊 Estado Actual:
- **Base de Datos**: MySQL exclusivamente
- **Funcionalidades**: 100% operativas
- **Rendimiento**: Optimizado para MySQL
- **Mantenimiento**: Simplificado sin dualidad de BD

### 🚀 Beneficios de la Migración:
- **Menor complejidad**: Un solo motor de BD
- **Mayor compatibilidad**: MySQL más común en hosting
- **Mejor rendimiento**: Optimizaciones específicas
- **Facilidad de despliegue**: Sin dependencias PostgreSQL

---

**Desarrollado con 💚 priorizando MySQL, simplicidad y privacidad de datos**

*Fecha de migración: 6 de Junio de 2025*  
*Sistema completamente libre de PostgreSQL y optimizado para MySQL*
