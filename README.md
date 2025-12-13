# 🏠 Sistema de Puntos Familiar

Sistema gamificado para motivar a la familia a completar tareas del hogar y ganar premios (Robux, tiempo extra, etc.)

## 📋 Requisitos

- Hosting compartido de Hostinger (o similar)
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Acceso a phpMyAdmin
- SSL/HTTPS habilitado (recomendado)

## 🚀 Instalación

### Paso 1: Crear la Base de Datos

1. Accede a **phpMyAdmin** en tu panel de Hostinger
2. Crea una nueva base de datos llamada `family_points`
3. Abre el archivo `family_points.sql`
4. Copia todo el contenido y pégalo en la pestaña SQL de phpMyAdmin
5. Ejecuta el script (botón "Continuar")

### Paso 2: Subir Archivos

Sube todos los archivos al directorio `public_html/family-points/` (o el nombre que prefieras):

```
public_html/
└── family-points/
    ├── index.php
    ├── login.php
    ├── .htaccess
    ├── api/
    │   ├── config.php
    │   ├── auth.php
    │   ├── tareas.php
    │   ├── completadas.php
    │   ├── premios.php
    │   └── canjes.php
    └── assets/
        └── js/
            └── app.js
```

### Paso 3: Configurar Conexión a la Base de Datos

Edita el archivo `api/config.php` y actualiza las siguientes líneas:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'family_points');
define('DB_USER', 'tu_usuario_mysql');  // ← Cambiar
define('DB_PASS', 'tu_password_mysql'); // ← Cambiar
define('SITE_URL', 'https://tudominio.com/family-points'); // ← Cambiar
```

**Importante:** Puedes encontrar tus credenciales MySQL en:
- Panel Hostinger → Bases de datos → Gestión de MySQL

### Paso 4: Configurar .htaccess

1. Edita `.htaccess`
2. Reemplaza `tudominio.com` con tu dominio real
3. Si ya tienes SSL, descomenta las líneas de HTTPS:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Paso 5: Permisos de Archivos

Asegúrate de que los permisos sean correctos:
```
Directorios: 755
Archivos PHP: 644
```

Puedes cambiarlos desde el Administrador de Archivos de Hostinger o por FTP.

## 👥 Usuarios por Defecto

El sistema viene con 4 usuarios pre-configurados:

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| Admin | familia2024 | Administrador |
| Papá | familia2024 | Miembro |
| Mamá | familia2024 | Miembro |
| Hijo | familia2024 | Miembro |

**⚠️ IMPORTANTE:** Cambia las contraseñas inmediatamente después de instalar.

### Cambiar Contraseñas

Puedes cambiar las contraseñas de dos formas:

**Opción 1: Desde phpMyAdmin**
```sql
-- Reemplaza 'nueva_contraseña' con tu contraseña deseada
UPDATE usuarios 
SET password = '$2y$10$[hash_generado]' 
WHERE nombre = 'Admin';
```

**Opción 2: Usar un generador de hash**
```php
<?php
echo password_hash('tu_nueva_contraseña', PASSWORD_DEFAULT);
?>
```

Ejecuta este código en un archivo temporal, copia el hash generado y úsalo en phpMyAdmin.

## 📱 Acceso

Una vez instalado, accede a:
```
https://tudominio.com/family-points/
```

## 🎮 Uso del Sistema

### Para Miembros de la Familia:

1. **Iniciar sesión** con tu usuario
2. **Ver tareas disponibles** en la pestaña "Tareas"
3. **Reclamar tarea** cuando la completes (haz clic en el botón "Reclamar")
4. **Esperar validación** del administrador
5. **Canjear puntos** por premios en la pestaña "Premios"
6. **Ver historial** en la pestaña "Historial"

### Para Administradores:

1. **Validar tareas** completadas en la pestaña "Admin"
2. **Entregar premios** canjeados
3. **Crear nuevas tareas** (botón "+ Nueva Tarea")
4. **Crear nuevos premios** (botón "+ Nuevo Premio")
5. **Ver estadísticas** generales

## 🔒 Seguridad

El sistema incluye:

✅ **Prepared Statements** (prevención de SQL injection)
✅ **Password hashing** con bcrypt
✅ **CSRF protection**
✅ **Session hijacking protection**
✅ **Rate limiting** en login
✅ **XSS protection**
✅ **Input sanitization**
✅ **.htaccess** configurado

### Recomendaciones Adicionales:

1. **Habilita SSL/HTTPS** en tu dominio (gratuito con Let's Encrypt en Hostinger)
2. **Cambia las contraseñas** por defecto inmediatamente
3. **Mantén PHP actualizado** desde el panel de Hostinger
4. **Haz backups regulares** de la base de datos
5. **No expongas el directorio `api/`** directamente

## 🛠️ Personalización

### Agregar Nuevos Usuarios

Desde phpMyAdmin:
```sql
INSERT INTO usuarios (nombre, password, rol, puntos) VALUES
('NuevoMiembro', '$2y$10$[hash_aqui]', 'miembro', 0);
```

### Modificar Tareas y Premios Iniciales

Edita el archivo `family_points.sql` antes de ejecutarlo, o modifica directamente desde el panel de Admin.

### Cambiar Colores y Diseño

- Los estilos están en línea usando **Tailwind CSS**
- Puedes personalizar colores en `index.php` y `login.php`
- Busca las clases de Tailwind y modifícalas según tus preferencias

## 📊 Estructura de la Base de Datos

- **usuarios**: Información de usuarios y puntos
- **tareas**: Catálogo de tareas disponibles
- **tareas_completadas**: Registro de tareas reclamadas/validadas
- **premios**: Catálogo de premios
- **canjes**: Registro de premios canjeados
- **historial_puntos**: Historial completo de cambios de puntos
- **sesiones**: Gestión de sesiones de usuario

## 🐛 Solución de Problemas

### Error de conexión a la base de datos
- Verifica credenciales en `api/config.php`
- Asegúrate de que la base de datos existe
- Confirma que el usuario MySQL tiene permisos

### Página en blanco
- Activa `display_errors` temporalmente en PHP
- Revisa logs de error en: `/home/tu_usuario/logs/php_errors.log`
- Verifica que todos los archivos se subieron correctamente

### No puedo iniciar sesión
- Verifica que ejecutaste el script SQL completo
- Usa las credenciales por defecto: Admin / familia2024
- Limpia cookies del navegador

### Las tareas/premios no se muestran
- Abre la consola del navegador (F12) y busca errores JavaScript
- Verifica que la ruta de `app.js` sea correcta
- Confirma que los archivos PHP en `api/` son accesibles

## 📞 Soporte

Si tienes problemas:

1. Revisa los logs de error en el servidor
2. Abre la consola del navegador (F12) para ver errores JavaScript
3. Verifica que todas las rutas en `config.php` sean correctas
4. Asegúrate de que `.htaccess` esté funcionando

## 🔄 Actualizaciones Futuras

Posibles mejoras:

- [ ] Notificaciones en tiempo real
- [ ] App móvil nativa
- [ ] Sistema de niveles y logros
- [ ] Gráficas de progreso
- [ ] Export/import de datos
- [ ] Integración con API de Roblox
- [ ] Modo oscuro

## 📄 Licencia

Uso personal y familiar. No redistribuir sin autorización.

---

**¡Disfruta motivando a tu familia! 🎉**