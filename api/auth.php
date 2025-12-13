<?php
// api/auth.php
// Manejo de autenticación

define('INCLUDED', true);
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Login
if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = sanitizeInput($data['nombre'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($nombre) || empty($password)) {
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
    
    if (!$usuario || !password_verify($password, $usuario['password'])) {
        jsonResponse(['error' => 'Credenciales inválidas'], 401);
    }
    
    // Generar token de sesión
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO sesiones (usuario_id, token, ip, user_agent, expira_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$usuario['id'], $token, $ip, $userAgent, $expira]);
    
    // Establecer cookie
    setcookie('auth_token', $token, [
        'expires' => time() + SESSION_LIFETIME,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
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