<?php
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