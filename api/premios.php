<?php
// api/premios.php
// CRUD de premios

define('INCLUDED', true);
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db = Database::getInstance()->getConnection();

// Listar premios activos
if ($method === 'GET' && $action === 'list') {
    requireAuth();
    
    $stmt = $db->query("
        SELECT * FROM premios 
        WHERE activo = 1 
        ORDER BY tipo, costo_puntos ASC
    ");
    $premios = $stmt->fetchAll();
    
    jsonResponse(['premios' => $premios]);
}

// Crear premio (admin)
if ($method === 'POST' && $action === 'create') {
    requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nombre = sanitizeInput($data['nombre'] ?? '');
    $descripcion = sanitizeInput($data['descripcion'] ?? '');
    $costoPuntos = intval($data['costo_puntos'] ?? 0);
    $tipo = sanitizeInput($data['tipo'] ?? 'especial');
    $cantidad = sanitizeInput($data['cantidad'] ?? '');
    $icono = sanitizeInput($data['icono'] ?? 'gift');
    
    if (empty($nombre) || $costoPuntos <= 0) {
        jsonResponse(['error' => 'Nombre y costo son requeridos'], 400);
    }
    
    if (!in_array($tipo, ['robux', 'tiempo', 'especial'])) {
        jsonResponse(['error' => 'Tipo de premio inválido'], 400);
    }
    
    $stmt = $db->prepare("
        INSERT INTO premios (nombre, descripcion, costo_puntos, tipo, cantidad, icono)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    try {
        $stmt->execute([$nombre, $descripcion, $costoPuntos, $tipo, $cantidad, $icono]);
        jsonResponse(['success' => true, 'message' => 'Premio creado'], 201);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Error al crear premio'], 500);
    }
}

// Actualizar premio (admin)
if ($method === 'PUT' && $action === 'update') {
    requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id'] ?? 0);
    $nombre = sanitizeInput($data['nombre'] ?? '');
    $descripcion = sanitizeInput($data['descripcion'] ?? '');
    $costoPuntos = intval($data['costo_puntos'] ?? 0);
    $tipo = sanitizeInput($data['tipo'] ?? 'especial');
    $cantidad = sanitizeInput($data['cantidad'] ?? '');
    $icono = sanitizeInput($data['icono'] ?? 'gift');
    $activo = intval($data['activo'] ?? 1);
    
    if (!$id || empty($nombre) || $costoPuntos <= 0) {
        jsonResponse(['error' => 'Datos inválidos'], 400);
    }
    
    $stmt = $db->prepare("
        UPDATE premios 
        SET nombre = ?, descripcion = ?, costo_puntos = ?, tipo = ?, cantidad = ?, icono = ?, activo = ?
        WHERE id = ?
    ");
    
    try {
        $stmt->execute([$nombre, $descripcion, $costoPuntos, $tipo, $cantidad, $icono, $activo, $id]);
        jsonResponse(['success' => true, 'message' => 'Premio actualizado']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Error al actualizar premio'], 500);
    }
}

// Eliminar premio (admin)
if ($method === 'DELETE' && $action === 'delete') {
    requireAdmin();
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['error' => 'ID requerido'], 400);
    }
    
    $stmt = $db->prepare("UPDATE premios SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    jsonResponse(['success' => true, 'message' => 'Premio eliminado']);
}

jsonResponse(['error' => 'Acción no válida'], 400);

?>