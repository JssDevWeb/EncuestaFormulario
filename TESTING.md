# Testing del Sistema de Encuestas de Satisfacción - MySQL

## Lista de Verificación para Pruebas

### ✅ Archivos Completados
- [x] **Backend PHP:**
  - [x] `config.php` - Configuración de base de datos MySQL
  - [x] `crear_formulario.php` - Creación de formularios y preguntas
  - [x] `editar_formulario.php` - Edición de formularios existentes
  - [x] `eliminar_formulario.php` - Eliminación segura con cascada
  - [x] `listar_formularios.php` - Listado con acciones CRUD
  - [x] `ver_respuestas.php` - Estadísticas anónimas agregadas
  - [x] `enviar_respuesta.php` - Endpoint para recibir respuestas anónimas
  - [x] `eliminar_respuesta.php` - Eliminación de respuestas individuales

- [x] **Frontend:**
  - [x] `estilos.css` - CSS accesible con WCAG AA compliance
  - [x] `script.js` - JavaScript modular con validación
  - [x] `index_admin.php` - Panel administrativo
  - [x] `llenar_formulario.php` - Interfaz para encuestados

- [x] **Base de Datos:**
  - [x] `esquema_mysql.sql` - Esquema MySQL

### 🧪 Pruebas Funcionales

#### 1. Configuración Inicial
- [ ] Verificar conexión a base de datos
- [ ] Ejecutar scripts de esquema
- [ ] Configurar `config.php` con credenciales

#### 2. Pruebas de Administración
- [ ] **Crear Formulario:**
  - [ ] Formulario con diferentes tipos de preguntas
  - [ ] Validación de campos obligatorios
  - [ ] Límites de caracteres
  
- [ ] **Listar Formularios:**
  - [ ] Mostrar estadísticas correctas
  - [ ] Enlaces funcionales
  - [ ] Tabla responsiva

- [ ] **Editar Formulario:**
  - [ ] Cargar datos existentes
  - [ ] Mantener respuestas al editar
  - [ ] Validaciones correctas

- [ ] **Eliminar Formulario:**
  - [ ] Confirmación de seguridad
  - [ ] Eliminación en cascada
  - [ ] Log de auditoría

#### 3. Pruebas de Encuestados
- [ ] **Llenar Formulario:**
  - [ ] Carga correcta de preguntas
  - [ ] Validación en tiempo real
  - [ ] Progreso visual
  - [ ] Envío anónimo

- [ ] **Tipos de Preguntas:**
  - [ ] Texto libre (límites)
  - [ ] Área de texto (límites)
  - [ ] Radio buttons (validación)
  - [ ] Checkboxes (múltiple selección)
  - [ ] Select dropdown
  - [ ] Escala numérica

#### 4. Pruebas de Estadísticas
- [ ] **Ver Respuestas:**
  - [ ] Estadísticas agregadas
  - [ ] Gráficos de barras
  - [ ] Preservación de anonimato
  - [ ] Análisis temporal

#### 5. Pruebas de Anonimato
- [ ] **Verificar que NO se almacena:**
  - [ ] Direcciones IP
  - [ ] Cookies de sesión
  - [ ] Información del navegador
  - [ ] Timestamps identificables

- [ ] **Verificar que SÍ se almacena:**
  - [ ] Respuestas en JSON
  - [ ] Hash de duplicados
  - [ ] Fecha de respuesta

#### 6. Pruebas de Accesibilidad
- [ ] **WCAG AA Compliance:**
  - [ ] Contraste 4.5:1 mínimo
  - [ ] Navegación por teclado
  - [ ] Lectores de pantalla
  - [ ] Elementos semánticos

- [ ] **Responsividad:**
  - [ ] Mobile (320px+)
  - [ ] Tablet (768px+)
  - [ ] Desktop (1024px+)

#### 7. Pruebas de Seguridad
- [ ] **Validación de Entrada:**
  - [ ] Sanitización de datos
  - [ ] Prevención de XSS
  - [ ] Validación de tipos
  - [ ] Límites de longitud

- [ ] **Base de Datos:**
  - [ ] Prepared statements
  - [ ] Transacciones
  - [ ] Manejo de errores

### 🚀 Comandos de Prueba

#### Configurar Base de Datos MySQL:
```bash
# Crear base de datos
mysql -u root -p -e "CREATE DATABASE encuestas_satisfaccion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Ejecutar esquema
mysql -u root -p encuestas_satisfaccion < backend/sql/esquema_mysql.sql

# Verificar tablas
mysql -u root -p -e "USE encuestas_satisfaccion; SHOW TABLES; DESCRIBE formularios;"
```

#### Probar APIs con curl:
```bash
# Crear formulario de prueba
curl -X POST http://localhost/Proyecto%20satisfactorio/backend/crear_formulario.php \
  -H "Content-Type: application/json" \
  -d '{
    "titulo": "Encuesta de Prueba",
    "descripcion": "Formulario para testing",
    "preguntas": [
      {
        "texto": "¿Cómo calificarías nuestro servicio?",
        "tipo": "escala",
        "obligatoria": true,
        "min": 1,
        "max": 5,
        "etiqueta_min": "Muy malo",
        "etiqueta_max": "Excelente"
      }
    ]
  }'

# Enviar respuesta anónima
curl -X POST http://localhost/Proyecto%20satisfactorio/backend/enviar_respuesta.php \
  -H "Content-Type: application/json" \
  -d '{
    "formulario_id": 1,
    "respuestas": {
      "1": 5
    }
  }'
```

### 📊 URLs de Prueba

#### Panel Administrativo:
- `http://localhost/Proyecto%20satisfactorio/frontend/index_admin.php`

#### Gestión de Formularios:
- `http://localhost/Proyecto%20satisfactorio/backend/listar_formularios.php`

#### Formulario Público (ID = 1):
- `http://localhost/Proyecto%20satisfactorio/frontend/llenar_formulario.php?id=1`

#### Estadísticas (ID = 1):
- `http://localhost/Proyecto%20satisfactorio/backend/ver_respuestas.php?id=1`

### ⚠️ Problemas Conocidos a Verificar

1. **Compatibilidad de Bases de Datos:**
   - Diferencias en sintaxis SQL entre MySQL y otros motores
   - Tipos de datos DATE/TIMESTAMP
   - Funciones específicas del motor

2. **Validación JavaScript:**
   - Compatibilidad con navegadores antiguos
   - Accesibilidad en modo sin JavaScript

3. **Rendimiento:**
   - Carga de formularios con muchas preguntas
   - Estadísticas con miles de respuestas

### 🎯 Criterios de Éxito

- ✅ Todas las pruebas funcionales pasan
- ✅ Sin errores PHP/JavaScript en consola
- ✅ WCAG AA compliance verificado
- ✅ Anonimato absoluto garantizado
- ✅ Funciona completamente con MySQL
- ✅ Responsive en todos los dispositivos
- ✅ Validación robusta en frontend y backend

---

**Estado del Sistema:** ✅ COMPLETO - Listo para pruebas finales

**Próximos Pasos:**
1. Ejecutar pruebas de configuración
2. Verificar funcionalidad completa
3. Validar cumplimiento WCAG AA
4. Documentar cualquier issue encontrado
