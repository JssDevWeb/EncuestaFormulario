@echo off
echo ================================================================
echo CREADOR AUTOMATICO DE BASE DE DATOS - ENCUESTAS DE SATISFACCION
echo ================================================================
echo.

REM Configuracion por defecto para WAMP/XAMPP
set MYSQL_HOST=localhost
set MYSQL_PORT=3306
set MYSQL_USER=root
set MYSQL_PASSWORD=

echo Configuracion actual:
echo - Host: %MYSQL_HOST%
echo - Puerto: %MYSQL_PORT%
echo - Usuario: %MYSQL_USER%
echo - Password: [%MYSQL_PASSWORD%]
echo.

set /p CONFIRM="¿Deseas continuar con esta configuracion? (S/N): "
if /i "%CONFIRM%" NEQ "S" goto :config_manual

goto :crear_bd

:config_manual
echo.
echo === CONFIGURACION PERSONALIZADA ===
set /p MYSQL_HOST="Host de MySQL (actual: %MYSQL_HOST%): "
set /p MYSQL_PORT="Puerto de MySQL (actual: %MYSQL_PORT%): "
set /p MYSQL_USER="Usuario de MySQL (actual: %MYSQL_USER%): "
set /p MYSQL_PASSWORD="Password de MySQL: "

:crear_bd
echo.
echo === VERIFICANDO CONEXION A MYSQL ===

REM Verificar si MySQL esta disponible
mysql --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: MySQL no esta en el PATH del sistema
    echo.
    echo Opciones:
    echo 1. Si usas WAMP: Agrega C:\wamp64\bin\mysql\mysql8.x.x\bin al PATH
    echo 2. Si usas XAMPP: Agrega C:\xampp\mysql\bin al PATH
    echo 3. O usa la ruta completa al ejecutable mysql.exe
    echo.
    pause
    exit /b 1
)

echo MySQL encontrado. Verificando conexion...

REM Crear archivo temporal con credenciales
echo [mysql] > temp_mysql.cnf
echo host=%MYSQL_HOST% >> temp_mysql.cnf
echo port=%MYSQL_PORT% >> temp_mysql.cnf
echo user=%MYSQL_USER% >> temp_mysql.cnf
if not "%MYSQL_PASSWORD%"=="" echo password=%MYSQL_PASSWORD% >> temp_mysql.cnf

REM Test de conexion
mysql --defaults-file=temp_mysql.cnf -e "SELECT 'Conexion exitosa' as Estado;" 2>error.log
if errorlevel 1 (
    echo ERROR: No se pudo conectar a MySQL
    echo.
    type error.log
    echo.
    echo Verifica que:
    echo 1. MySQL este funcionando (WAMP/XAMPP iniciado)
    echo 2. Las credenciales sean correctas
    echo 3. El puerto no este bloqueado
    del temp_mysql.cnf error.log 2>nul
    pause
    exit /b 1
)

echo ¡Conexion exitosa!
echo.

echo === SELECCION DE ESQUEMA ===
echo.
echo Opciones disponibles:
echo 1. Crear base de datos SIMPLE (recomendado para empezar)
echo 2. Crear base de datos OPTIMIZADA (completa con cache y auditoria)
echo 3. MIGRAR base de datos existente (si ya tienes datos)
echo.
set /p OPCION="Selecciona una opcion (1-3): "

if "%OPCION%"=="1" goto :crear_simple
if "%OPCION%"=="2" goto :crear_optimizada
if "%OPCION%"=="3" goto :migrar
echo Opcion invalida
goto :crear_bd

:crear_simple
echo.
echo === CREANDO BASE DE DATOS SIMPLE ===
echo ADVERTENCIA: Esto eliminara la base de datos 'encuestas_satisfaccion' si existe
set /p CONFIRM_DELETE="¿Continuar? (S/N): "
if /i "%CONFIRM_DELETE%" NEQ "S" goto :fin

echo Ejecutando esquema simple...
mysql --defaults-file=temp_mysql.cnf < "backend\sql\crear_bd_simple.sql" 2>error.log
if errorlevel 1 (
    echo ERROR al crear la base de datos:
    type error.log
    goto :error_final
)
echo ¡Base de datos simple creada exitosamente!
goto :verificar

:crear_optimizada
echo.
echo === CREANDO BASE DE DATOS OPTIMIZADA ===
echo ADVERTENCIA: Esto eliminara la base de datos 'encuestas_satisfaccion' si existe
set /p CONFIRM_DELETE="¿Continuar? (S/N): "
if /i "%CONFIRM_DELETE%" NEQ "S" goto :fin

echo Ejecutando esquema optimizado...
mysql --defaults-file=temp_mysql.cnf < "backend\sql\esquema_mysql_optimizado.sql" 2>error.log
if errorlevel 1 (
    echo ERROR al crear la base de datos:
    type error.log
    goto :error_final
)
echo ¡Base de datos optimizada creada exitosamente!
goto :verificar

:migrar
echo.
echo === MIGRANDO BASE DE DATOS EXISTENTE ===
echo Esto actualizara la estructura preservando los datos existentes
set /p CONFIRM_MIGRATE="¿Continuar con la migracion? (S/N): "
if /i "%CONFIRM_MIGRATE%" NEQ "S" goto :fin

echo Ejecutando migracion...
mysql --defaults-file=temp_mysql.cnf < "backend\sql\migracion_esquema.sql" 2>error.log
if errorlevel 1 (
    echo ERROR en la migracion:
    type error.log
    goto :error_final
)
echo ¡Migracion completada exitosamente!
goto :verificar

:verificar
echo.
echo === VERIFICANDO INSTALACION ===
echo Verificando tablas creadas...

mysql --defaults-file=temp_mysql.cnf -e "USE encuestas_satisfaccion; SHOW TABLES;" 2>error.log
if errorlevel 1 (
    echo ERROR al verificar tablas:
    type error.log
    goto :error_final
)

echo.
echo Verificando datos de ejemplo...
mysql --defaults-file=temp_mysql.cnf -e "USE encuestas_satisfaccion; SELECT COUNT(*) as 'Formularios' FROM formularios; SELECT COUNT(*) as 'Preguntas' FROM preguntas;" 2>error.log

echo.
echo === CONFIGURANDO ARCHIVO CONFIG.PHP ===
if not exist "backend\config.php" (
    echo Creando config.php desde config.example.php...
    copy "backend\config.example.php" "backend\config.php" >nul
)

REM Actualizar config.php con los valores correctos
powershell -Command "(Get-Content 'backend\config.php') -replace 'localhost', '%MYSQL_HOST%' -replace '3306', '%MYSQL_PORT%' -replace 'root', '%MYSQL_USER%' | Set-Content 'backend\config.php'"

echo Archivo config.php configurado.

echo.
echo ================================================================
echo                    ¡INSTALACION COMPLETADA!
echo ================================================================
echo.
echo La base de datos 'encuestas_satisfaccion' ha sido creada exitosamente.
echo.
echo Proximos pasos:
echo 1. Verifica que WAMP/XAMPP este funcionando
echo 2. Abre tu navegador en: http://localhost/Proyecto%%20satisfactorio/
echo 3. Accede al panel de administracion para crear formularios
echo.
echo Archivos importantes:
echo - Base de datos: encuestas_satisfaccion
echo - Configuracion: backend\config.php
echo - Panel admin: frontend\index_admin.php
echo.
goto :fin

:error_final
echo.
echo ================================================================
echo                         ERROR
echo ================================================================
echo La instalacion no se pudo completar. Revisa los errores anteriores.
echo.
echo Posibles soluciones:
echo 1. Verifica que MySQL este funcionando
echo 2. Comprueba las credenciales de acceso
echo 3. Asegurate de tener permisos para crear bases de datos
echo.

:fin
REM Limpiar archivos temporales
del temp_mysql.cnf error.log 2>nul

echo.
echo Presiona cualquier tecla para salir...
pause >nul
