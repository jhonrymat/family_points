<?php
// api/config.php
// Configuración de base de datos y constantes

// Prevenir acceso directo
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Acceso prohibido');
}

// Configuración de la base de datos
// CAMBIAR ESTOS VALORES CON TUS CREDENCIALES DE HOSTINGER
define('DB_HOST', 'localhost');
define('DB_NAME', 'family_points');
define('DB_USER', 'tu_usuario');  // Cambiar
define('DB_PASS', 'tu_password'); // Cambiar
define('DB_CHARSET', 'utf8mb4');

// Configuración general
define('SITE_URL', 'https://puntos.agentesias.com');
define('SESSION_LIFETIME', 7200); // 2 horas en segundos
define('TIMEZONE', 'America/Bogota');

// Configuración de seguridad
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutos

// Establecer zona horaria
date_default_timezone_set(TIMEZONE);

// Clase de base de datos con PDO
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Error de conexión a la base de datos']));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Funciones de utilidad
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateToken() {
    error_log("validateToken called");
    error_log("Cookies received: " . json_encode($_COOKIE));
    
    if (!isset($_COOKIE['auth_token'])) {
        error_log("No auth_token cookie found");
        return false;
    }
    
    $db = Database::getInstance()->getConnection();
    $token = $_COOKIE['auth_token'];
    
    error_log("Validating token: " . substr($token, 0, 10) . "...");
    
    $stmt = $db->prepare("
        SELECT s.*, u.id as user_id, u.nombre, u.rol, u.puntos 
        FROM sesiones s
        INNER JOIN usuarios u ON s.usuario_id = u.id
        WHERE s.token = ? AND s.expira_at > NOW() AND u.activo = 1
    ");
    $stmt->execute([$token]);
    $session = $stmt->fetch();
    
    if (!$session) {
        error_log("Session not found or expired for token");
        return false;
    }
    
    error_log("Session valid for user: " . $session['nombre']);
    
    return [
        'id' => $session['user_id'],
        'nombre' => $session['nombre'],
        'rol' => $session['rol'],
        'puntos' => $session['puntos']
    ];
}

function requireAuth() {
    $user = validateToken();
    if (!$user) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }
    return $user;
}

function requireAdmin() {
    $user = requireAuth();
    if ($user['rol'] !== 'admin') {
        jsonResponse(['error' => 'Acceso denegado. Se requieren permisos de administrador.'], 403);
    }
    return $user;
}

function registrarHistorialPuntos($usuario_id, $puntos_antes, $puntos_cambio, $tipo, $referencia_tipo, $referencia_id, $descripcion) {
    $db = Database::getInstance()->getConnection();
    $puntos_despues = $puntos_antes + $puntos_cambio;
    
    $stmt = $db->prepare("
        INSERT INTO historial_puntos 
        (usuario_id, puntos_antes, puntos_cambio, puntos_despues, tipo, referencia_tipo, referencia_id, descripcion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $usuario_id,
        $puntos_antes,
        $puntos_cambio,
        $puntos_despues,
        $tipo,
        $referencia_tipo,
        $referencia_id,
        $descripcion
    ]);
}

function actualizarPuntosUsuario($usuario_id, $cambio, $tipo, $referencia_tipo, $referencia_id, $descripcion) {
    $db = Database::getInstance()->getConnection();
    
    // Obtener puntos actuales
    $stmt = $db->prepare("SELECT puntos FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        return false;
    }
    
    $puntos_antes = $usuario['puntos'];
    $puntos_nuevos = $puntos_antes + $cambio;
    
    // No permitir puntos negativos
    if ($puntos_nuevos < 0) {
        return false;
    }
    
    // Actualizar puntos
    $stmt = $db->prepare("UPDATE usuarios SET puntos = ? WHERE id = ?");
    $stmt->execute([$puntos_nuevos, $usuario_id]);
    
    // Registrar en historial
    registrarHistorialPuntos($usuario_id, $puntos_antes, $cambio, $tipo, $referencia_tipo, $referencia_id, $descripcion);
    
    return true;
}

// Función para limpiar sesiones expiradas (llamar periódicamente)
function limpiarSesionesExpiradas() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM sesiones WHERE expira_at < NOW()");
    return $stmt->execute();
}

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Habilitar CORS solo para tu dominio (opcional)
// header('Access-Control-Allow-Origin: ' . SITE_URL);
// header('Access-Control-Allow-Credentials: true');