# 🛠️ Guía de Ejecución Local

Para ejecutar este proyecto en tu computadora (Windows), sigue estos pasos:

## 1. Requisitos Previos
- Tener PHP instalado (Verifica escribiendo `php -v` en tu terminal).
- Tener un servidor MySQL (puede ser XAMPP, WAMP, o MySQL Community Server).

## 2. Iniciar el Servidor
Haz doble clic en el archivo `start_local.bat` que he creado en la carpeta del proyecto.
Esto abrirá una ventana de comandos y ejecutará el servidor PHP en `http://localhost:8000`.

## 3. Configuración Inicial
1. Abre tu navegador en `http://localhost:8000/setup_wizard.php`.
2. Ingresa los datos de tu conexión MySQL local:
   - **Host**: usualmente `localhost`
   - **Usuario**: usualmente `root`
   - **Contraseña**: (déjala vacía si usas XAMPP por defecto, o pon tu clave)
   - **Base de Datos**: `family_points`
3. Haz clic en "Instalar y Configurar".

El asistente creará el archivo `api/config.local.php` e importará la base de datos automáticamente.

## 4. Acceder
Una vez configurado, ve a `http://localhost:8000`.
Inicia sesión con:
- **Usuario**: `Admin`
- **Contraseña**: `familia2024`

## Solución de Problemas Comunes

### "Solo aparece una alerta al reclamar"
Esto sucedía porque las cookies seguras estaban activadas obligatoriamente, pero en local (http) no funcionan. **Ya he corregido el código (`api/auth.php` y `api/config.php`) para detectar automáticamente si usas HTTPS o no.**

### Faltaba la base de datos
He recreado el archivo `family_points.sql` analizando el código, ya que no estaba en el proyecto. El asistente de instalación lo usará.
