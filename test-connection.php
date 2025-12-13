<?php
// test-connection.php
// IMPORTANTE: ELIMINAR ESTE ARCHIVO DESPUÉS DE PROBAR

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Prueba de Conexión - Sistema de Puntos</h2>";

// Configuración (CAMBIAR CON TUS DATOS)
$host = 'localhost';
$dbname = 'family_points';
$username = 'tu_usuario';  // CAMBIAR
$password = 'tu_password'; // CAMBIAR

echo "<h3>1. Prueba de Conexión MySQL</h3>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ <strong style='color: green;'>Conexión exitosa a la base de datos!</strong><br>";
    echo "Servidor: $host<br>";
    echo "Base de datos: $dbname<br><br>";
    
    // Verificar tablas
    echo "<h3>2. Verificando Tablas</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ <strong style='color: red;'>No se encontraron tablas. Ejecuta el script SQL.</strong><br>";
    } else {
        echo "✅ Tablas encontradas: " . count($tables) . "<br>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    // Verificar usuarios
    echo "<h3>3. Verificando Usuarios</h3>";
    $stmt = $pdo->query("SELECT id, nombre, rol, puntos FROM usuarios");
    $usuarios = $stmt->fetchAll();
    
    if (empty($usuarios)) {
        echo "❌ <strong style='color: red;'>No hay usuarios. Ejecuta el script SQL completo.</strong><br>";
    } else {
        echo "✅ Usuarios encontrados: " . count($usuarios) . "<br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Rol</th><th>Puntos</th></tr>";
        foreach ($usuarios as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['nombre']}</td>";
            echo "<td>{$user['rol']}</td>";
            echo "<td>{$user['puntos']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // Probar hash de contraseña
    echo "<h3>4. Verificando Hash de Contraseña</h3>";
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE nombre = 'Admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        $passwordToTest = 'familia2024';
        $isValid = password_verify($passwordToTest, $admin['password']);
        
        echo "Hash almacenado: " . substr($admin['password'], 0, 50) . "...<br>";
        echo "Contraseña de prueba: '$passwordToTest'<br>";
        
        if ($isValid) {
            echo "✅ <strong style='color: green;'>La contraseña es correcta!</strong><br>";
        } else {
            echo "❌ <strong style='color: red;'>La contraseña NO coincide!</strong><br>";
            echo "<br><strong>Solución:</strong> Ejecuta esta query en phpMyAdmin:<br>";
            echo "<code style='background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;'>";
            $newHash = password_hash($passwordToTest, PASSWORD_DEFAULT);
            echo "UPDATE usuarios SET password = '$newHash' WHERE nombre = 'Admin';";
            echo "</code>";
        }
    } else {
        echo "❌ No se encontró el usuario 'Admin'<br>";
    }
    
    // Verificar tareas
    echo "<h3>5. Verificando Tareas</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tareas WHERE activa = 1");
    $result = $stmt->fetch();
    echo "✅ Tareas activas: {$result['total']}<br>";
    
    // Verificar premios
    echo "<h3>6. Verificando Premios</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM premios WHERE activo = 1");
    $result = $stmt->fetch();
    echo "✅ Premios activos: {$result['total']}<br>";
    
    echo "<br><h3>✅ Diagnóstico Completo</h3>";
    echo "<p><strong style='color: green;'>La conexión funciona correctamente.</strong></p>";
    echo "<p>Si el login sigue fallando, el problema puede ser:</p>";
    echo "<ul>";
    echo "<li>El hash de la contraseña está mal (ver punto 4)</li>";
    echo "<li>Problema con las rutas en config.php</li>";
    echo "<li>Problema con las cookies (revisar configuración HTTPS)</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "❌ <strong style='color: red;'>Error de conexión:</strong><br>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Código: " . $e->getCode() . "<br><br>";
    
    echo "<strong>Posibles soluciones:</strong><br>";
    echo "<ul>";
    echo "<li>Verifica que el usuario y contraseña MySQL sean correctos</li>";
    echo "<li>Verifica que la base de datos 'family_points' exista</li>";
    echo "<li>Verifica que el usuario tenga permisos sobre esa base de datos</li>";
    echo "<li>En Hostinger: Panel → Bases de datos → Gestión MySQL</li>";
    echo "</ul>";
}

echo "<br><hr>";
echo "<p><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo después de verificar la conexión por seguridad.</p>";
echo "<p>Elimínalo con: <code>rm test-connection.php</code></p>";
?>