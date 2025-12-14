<?php
// api/auth.php
// Manejo de autenticación

define('INCLUDED', true);
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Login
if ($method === 'POST' && $action === 'login') {
    // Limpiar sesiones expiradas primero
    limpiarSesionesExpiradas();
    
    $rawInput = file_get_contents('php://input');
    error_log("Login attempt - Raw input: " . $rawInput);
    
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        jsonResponse(['error' => 'Datos JSON inválidos'], 400);
    }
    
    $nombre = sanitizeInput($data['nombre'] ?? '');
    $password = $data['password'] ?? '';
    
    error_log("Login attempt for user: $nombre");
    
    if (empty($nombre) || empty($password)) {
        error_log("Empty credentials provided");
        jsonResponse(['error' => 'Nombre y contraseña son requeridos'], 400);
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar intentos de login (rate limiting básico)
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("
        SELECT COUNT(*) as intentos 
        FROM sesiones 
        WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch();
    
    if ($result['intentos'] >= MAX_LOGIN_ATTEMPTS) {
        jsonResponse(['error' => 'Demasiados intentos. Intenta en 15 minutos.'], 429);
    }
    
    // Buscar usuario
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE nombre = ? AND activo = 1");
    $stmt->execute([$nombre]);
    $usuario = $stmt->fetch();
    
    error_log("User found: " . ($usuario ? "Yes" : "No"));
    
    if (!$usuario) {
        error_log("User not found or inactive: $nombre");
        jsonResponse(['error' => 'Credenciales inválidas'], 401);
    }
    
    error_log("Verifying password...");
    $passwordValid = password_verify($password, $usuario['password']);
    error_log("Password valid: " . ($passwordValid ? "Yes" : "No"));
    
    if (!$passwordValid) {
        error_log("Invalid password for user: $nombre");
        jsonResponse(['error' => 'Credenciales inválidas'], 401);
    }
    
    // Generar token de sesión
    $token = bin2hex(random_bytes(32));
    
    // Calcular expiración correctamente usando INTERVAL de MySQL
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO sesiones (usuario_id, token, ip, user_agent, expira_at)
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
    ");
    $stmt->execute([$usuario['id'], $token, $ip, $userAgent, SESSION_LIFETIME]);
    
    error_log("Session created with token: " . substr($token, 0, 10) . "... expires in " . SESSION_LIFETIME . " seconds");
    
    // Establecer cookie
    $cookieOptions = [
        'expires' => time() + SESSION_LIFETIME,
        'path' => '/',
        'domain' => '', // Dejar vacío para que funcione en el dominio actual
        'secure' => true, // HTTPS
        'httponly' => true,
        'samesite' => 'Lax' // Cambiar de Strict a Lax para mejor compatibilidad
    ];
    
    setcookie('auth_token', $token, $cookieOptions);
    
    error_log("Cookie set for token: " . substr($token, 0, 10) . "...");
    
    jsonResponse([
        'success' => true,
        'usuario' => [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol'],
            'puntos' => $usuario['puntos']
        ]
    ]);
}

// Logout
if ($method === 'POST' && $action === 'logout') {
    if (isset($_COOKIE['auth_token'])) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM sesiones WHERE token = ?");
        $stmt->execute([$_COOKIE['auth_token']]);
        
        setcookie('auth_token', '', time() - 3600, '/');
    }
    
    jsonResponse(['success' => true]);
}

// Verificar sesión actual
if ($method === 'GET' && $action === 'check') {
    $user = validateToken();
    if (!$user) {
        jsonResponse(['authenticated' => false], 401);
    }
    
    jsonResponse([
        'authenticated' => true,
        'usuario' => $user
    ]);
}

// Cambiar contraseña
if ($method === 'POST' && $action === 'change-password') {
    $user = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        jsonResponse(['error' => 'Contraseñas requeridas'], 400);
    }
    
    if (strlen($newPassword) < 6) {
        jsonResponse(['error' => 'La nueva contraseña debe tener al menos 6 caracteres'], 400);
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$user['id']]);
    $usuario = $stmt->fetch();
    
    if (!password_verify($currentPassword, $usuario['password'])) {
        jsonResponse(['error' => 'Contraseña actual incorrecta'], 401);
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $user['id']]);
    
    jsonResponse(['success' => true, 'message' => 'Contraseña actualizada']);
}

// Acción no válida
jsonResponse(['error' => 'Acción no válida'], 400);