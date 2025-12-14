# 🚀 Guía de Despliegue a Producción

Este documento detalla los pasos y consideraciones para poner el sistema **Family Points** en un servidor de producción (como Hostinger, cPanel, AWS, etc.).

## 1. Limpieza de Archivos
Antes de subir los archivos, o inmediatamente después de configurar:

- [ ] **Eliminar `setup_wizard.php`**: Este archivo es solo para la configuración inicial. Aunque le agregué una protección, es mejor borrarlo.
- [ ] **Eliminar `start_local.bat`**: No se necesita en servidores Linux.
- [ ] **Eliminar `README_LOCAL.md`**: Es solo para desarrollo local.
- [ ] **Proteger `api/config.local.php`**: Si subiste este archivo, asegúrate de que tenga las credenciales correctas de producción, o bórralo y edita directamente `api/config.php`.

## 2. Configuración de Base de Datos (Producción)
En tu panel de hosting:
1. Crea una base de datos MySQL.
2. Crea un usuario MySQL con una contraseña **fuerte**.
3. Importa el archivo `family_points.sql` usando phpMyAdmin.

## 3. Configuración de Credenciales
Edita `api/config.php`.

Si no estás usando `config.local.php`, edita las constantes directamente:
```php
define('DB_HOST', 'localhost'); // Usualmente localhost, o la IP que te de tu hosting
define('DB_NAME', 'nombre_base_datos_prod');
define('DB_USER', 'usuario_prod');
define('DB_PASS', 'contraseña_muy_segura_x9#mP');
```

## 4. Configuración de Seguridad

### HTTPS (SSL)
El sistema está diseñado para funcionar mejor con HTTPS.
- Asegúrate de tener un **certificado SSL** instalado (Let's Encrypt es gratis).
- El código (`auth.php` y `config.php`) detectará automáticamente HTTPS y activará las cookies seguras.

### Permisos de Archivos
- Carpetas: `755`
- Archivos: `644`

### Passwords
- **IMPORTANTE**: Cambia la contraseña del usuario `Admin` inmediatamente.
- No uses `familia2024` en producción.

## 5. Otros Ajustes

### Errores PHP
En producción, no debes mostrar errores al usuario.
En tu panel de hosting o `php.ini`, asegúrate de:
```ini
display_errors = Off
log_errors = On
```

### Zona Horaria
Verifica que la zona horaria en `api/config.php` sea la correcta para tu familia:
```php
define('TIMEZONE', 'America/Bogota'); // Cambia a tu zona
```

## Resumen Checklist
1. [ ] Base de datos creada e importada.
2. [ ] `api/config.php` actualizado con credenciales reales.
3. [ ] `setup_wizard.php` eliminado.
4. [ ] SSL habilitado (https://).
5. [ ] Contraseñas de usuarios cambiadas.
