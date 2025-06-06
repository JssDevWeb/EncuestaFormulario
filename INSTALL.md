# 🚀 Instalación Rápida - Sistema de Encuestas de Satisfacción

## ⚡ Setup en 5 minutos

### 1. Configurar Base de Datos

#### Para MySQL (WAMP/XAMPP):
```sql
-- Crear base de datos
CREATE DATABASE encuestas_satisfaccion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Ejecutar esquema
mysql -u root -p encuestas_satisfaccion < backend/sql/esquema_mysql.sql
```

#### Para PostgreSQL:
```sql
-- Crear base de datos
createdb -U postgres encuestas_satisfaccion

-- Ejecutar esquema
psql -U postgres -d encuestas_satisfaccion -f backend/sql/esquema_postgresql.sql
```

### 2. Configurar Aplicación

```bash
# Copiar configuración
cp backend/config.example.php backend/config.php

# Editar credenciales en config.php según tu entorno
```

### 3. URLs de Acceso

- **Panel Admin:** `http://localhost/Proyecto%20satisfactorio/frontend/index_admin.php`
- **Gestión:** `http://localhost/Proyecto%20satisfactorio/backend/listar_formularios.php`

### 4. Crear Primera Encuesta

1. Ve al panel admin
2. Clic en "Crear Nuevo Formulario"
3. Agrega preguntas
4. Comparte el enlace público

## 🔧 Configuración Avanzada

Ver `README.md` para documentación completa.

## ✅ Verificar Instalación

```bash
# Test de conexión
php -f backend/config.php

# Test de formulario
curl -X POST http://localhost/Proyecto%20satisfactorio/backend/crear_formulario.php \
  -H "Content-Type: application/json" \
  -d '{"titulo":"Test","descripcion":"","preguntas":[{"texto":"¿Funciona?","tipo":"radio","opciones":["Sí","No"],"obligatoria":true}]}'
```

## 🆘 Problemas Comunes

- **Error conexión DB:** Verificar credenciales en `config.php`
- **Error permisos:** `chmod 755` en carpeta del proyecto
- **Error PHP:** Verificar extensiones PDO, PDO_MySQL/PDO_PGSQL

---

**¿Todo listo?** ✨ Tu sistema de encuestas anónimas está funcionando!
