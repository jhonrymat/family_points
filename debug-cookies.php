<?php
// debug-cookies.php
// ELIMINAR DESPUÉS DE DEBUGGEAR

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Cookies y Sesiones</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Debug - Sistema de Puntos</h1>
    
    <div class="section">
        <h2>1. Información del Servidor</h2>
        <pre><?php
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
        echo "HTTPS: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'ON ✓' : 'OFF ✗') . "\n";
        echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n";
        echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "\n";
        echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "\n";
        ?></pre>
    </div>
    
    <div class="section">
        <h2>2. Cookies Recibidas</h2>
        <pre><?php
        if (empty($_COOKIE)) {
            echo "❌ No se recibieron cookies\n";
        } else {
            echo "✓ Cookies encontradas:\n\n";
            foreach ($_COOKIE as $key => $value) {
                echo "$key = " . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . "\n";
            }
        }
        ?></pre>
    </div>
    
    <div class="section">
        <h2>3. Parámetros de Cookie de Sesión</h2>
        <pre><?php
        $params = session_get_cookie_params();
        print_r($params);
        ?></pre>
    </div>
    
    <div class="section">
        <h2>4. Conexión a Base de Datos</h2>
        <?php
        // Configuración directa (CAMBIAR con tus datos)
        $host = 'localhost';
        $dbname = 'u736188689_puntos_db';
        $username = 'u736188689_puntos_us'; // CAMBIAR SI ES DIFERENTE
        $password = 'CzMsd:Yhr9!J'; // CAMBIAR
        
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            echo "<p class='success'>✓ Conexión exitosa a la base de datos</p>";
            
            // Ver sesiones activas
            echo "<h3>Sesiones Activas (últimas 5):</h3>";
            $stmt = $pdo->query("
                SELECT s.id, s.token, u.nombre, s.expira_at, s.created_at,
                       CASE WHEN s.expira_at > NOW() THEN 'Válida' ELSE 'Expirada' END as estado
                FROM sesiones s 
                INNER JOIN usuarios u ON s.usuario_id = u.id 
                ORDER BY s.created_at DESC 
                LIMIT 5
            ");
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($sessions)) {
                echo "<p class='error'>❌ No hay sesiones en la base de datos</p>";
            } else {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Usuario</th><th>Token (inicio)</th><th>Creada</th><th>Expira</th><th>Estado</th></tr>";
                foreach ($sessions as $s) {
                    echo "<tr>";
                    echo "<td>{$s['id']}</td>";
                    echo "<td>{$s['nombre']}</td>";
                    echo "<td>" . substr($s['token'], 0, 20) . "...</td>";
                    echo "<td>{$s['created_at']}</td>";
                    echo "<td>{$s['expira_at']}</td>";
                    echo "<td>{$s['estado']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Si hay cookie, verificar si existe en BD
            if (isset($_COOKIE['auth_token'])) {
                echo "<h3>Verificación de Token Actual:</h3>";
                $token = $_COOKIE['auth_token'];
                echo "<p>Token en cookie: " . substr($token, 0, 30) . "...</p>";
                
                $stmt = $pdo->prepare("
                    SELECT s.*, u.nombre, u.rol 
                    FROM sesiones s 
                    INNER JOIN usuarios u ON s.usuario_id = u.id 
                    WHERE s.token = ?
                ");
                $stmt->execute([$token]);
                $currentSession = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($currentSession) {
                    $expired = strtotime($currentSession['expira_at']) < time();
                    echo "<p class='" . ($expired ? 'error' : 'success') . "'>";
                    echo $expired ? "❌ Token encontrado pero EXPIRADO" : "✓ Token encontrado y VÁLIDO";
                    echo "</p>";
                    echo "<pre>";
                    print_r([
                        'usuario' => $currentSession['nombre'],
                        'rol' => $currentSession['rol'],
                        'expira' => $currentSession['expira_at'],
                        'creada' => $currentSession['created_at']
                    ]);
                    echo "</pre>";
                } else {
                    echo "<p class='error'>❌ Token NO encontrado en la base de datos</p>";
                    echo "<p>Esto significa que la cookie existe en tu navegador pero no hay sesión correspondiente en la BD.</p>";
                }
            } else {
                echo "<p class='error'>❌ No hay cookie 'auth_token' en el navegador</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
            echo "<p>Verifica las credenciales en este archivo (líneas 52-54)</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>5. Headers Enviados</h2>
        <pre><?php
        if (function_exists('getallheaders')) {
            print_r(getallheaders());
        } else {
            echo "Función getallheaders() no disponible\n";
        }
        ?></pre>
    </div>
    
    <div class="section">
        <h2>⚠️ Acciones Recomendadas</h2>
        <ul>
            <li>Si no hay cookie 'auth_token': El navegador no está guardando la cookie</li>
            <li>Si hay cookie pero no está en BD: Se perdió la sesión en el servidor</li>
            <li>Si hay cookie y está en BD pero expirada: Aumentar SESSION_LIFETIME en config.php</li>
            <li>Si HTTPS está OFF: Activar SSL en Hostinger</li>
        </ul>
    </div>
    
    <p><strong>⚠️ ELIMINA ESTE ARCHIVO después de debuggear (contiene info sensible)</strong></p>
</body>
</html><?php
// debug-cookies.php
// ELIMINAR DESPUÉS DE DEBUGGEAR

header('Content-Type: application/json');

$debug = [
    'server_info' => [
        'PHP_VERSION' => PHP_VERSION,
        'HTTPS' => isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'Not set',
        'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'Not set',
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Not set',
    ],
    'cookies_received' => $_COOKIE,
    'cookie_params' => session_get_cookie_params(),
];

// Intentar leer la base de datos
try {
    require_once 'api/config.php';
    $db = Database::getInstance()->getConnection();
    
    // Ver sesiones activas
    $stmt = $db->query("
        SELECT s.id, s.token, s.usuario_id, u.nombre, s.expira_at, s.created_at 
        FROM sesiones s 
        INNER JOIN usuarios u ON s.usuario_id = u.id 
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $debug['active_sessions'] = $stmt->fetchAll();
    
    // Ver si hay token en cookie
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
        $stmt = $db->prepare("
            SELECT s.*, u.nombre 
            FROM sesiones s 
            INNER JOIN usuarios u ON s.usuario_id = u.id 
            WHERE s.token = ?
        ");
        $stmt->execute([$token]);
        $debug['current_session'] = $stmt->fetch() ?: 'Token not found in database';
    } else {
        $debug['current_session'] = 'No auth_token cookie';
    }
    
} catch (Exception $e) {
    $debug['db_error'] = $e->getMessage();
}

echo json_encode($debug, JSON_PRETTY_PRINT);
?>