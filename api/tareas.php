<?php
// api/tareas.php
// CRUD de tareas

define('INCLUDED', true);
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db = Database::getInstance()->getConnection();

// Listar todas las tareas activas
if ($method === 'GET' && $action === 'list') {
    requireAuth();
    
    $stmt = $db->query("
        SELECT * FROM tareas 
        WHERE activa = 1 
        ORDER BY tipo, puntos DESC
    ");
    $tareas = $stmt->fetchAll();
    
    jsonResponse(['tareas' => $tareas]);
}

// Obtener una tarea específica
if ($method === 'GET' && $action === 'get') {
    requireAuth();
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['error' => 'ID requerido'], 400);
    }
    
    $stmt = $db->prepare("SELECT * FROM tareas WHERE id = ?");
    $stmt->execute([$id]);
    $tarea = $stmt->fetch();
    
    if (!$tarea) {
        jsonResponse(['error' => 'Tarea no encontrada'], 404);
    }
    
    jsonResponse(['tarea' => $tarea]);
}

// Crear nueva tarea (solo admin)
if ($method === 'POST' && $action === 'create') {
    requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nombre = sanitizeInput($data['nombre'] ?? '');
    $descripcion = sanitizeInput($data['descripcion'] ?? '');
    $puntos = intval($data['puntos'] ?? 0);
    $tipo = sanitizeInput($data['tipo'] ?? 'especial');
    $color = sanitizeInput($data['color'] ?? '#3B82F6');
    $icono = sanitizeInput($data['icono'] ?? 'task');
    
    if (empty($nombre) || $puntos <= 0) {
        jsonResponse(['error' => 'Nombre y puntos son requeridos'], 400);
    }
    
    if (!in_array($tipo, ['diaria', 'semanal', 'especial'])) {
        jsonResponse(['error' => 'Tipo de tarea inválido'], 400);
    }
    
    $stmt = $db->prepare("
        INSERT INTO tareas (nombre, descripcion, puntos, tipo, color, icono)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    try {
        $stmt->execute([$nombre, $descripcion, $puntos, $tipo, $color, $icono]);
        $tareaId = $db->lastInsertId();
        
        jsonResponse([
            'success' => true,
            'message' => 'Tarea creada exitosamente',
            'tarea_id' => $tareaId
        ], 201);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Error al crear tarea'], 500);
    }
}

// Actualizar tarea (solo admin)
if ($method === 'PUT' && $action === 'update') {
    requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id'] ?? 0);
    $nombre = sanitizeInput($data['nombre'] ?? '');
    $descripcion = sanitizeInput($data['descripcion'] ?? '');
    $puntos = intval($data['puntos'] ?? 0);
    $tipo = sanitizeInput($data['tipo'] ?? 'especial');
    $color = sanitizeInput($data['color'] ?? '#3B82F6');
    $icono = sanitizeInput($data['icono'] ?? 'task');
    $activa = intval($data['activa'] ?? 1);
    
    if (!$id || empty($nombre) || $puntos <= 0) {
        jsonResponse(['error' => 'Datos inválidos'], 400);
    }
    
    $stmt = $db->prepare("
        UPDATE tareas 
        SET nombre = ?, descripcion = ?, puntos = ?, tipo = ?, color = ?, icono = ?, activa = ?
        WHERE id = ?
    ");
    
    try {
        $stmt->execute([$nombre, $descripcion, $puntos, $tipo, $color, $icono, $activa, $id]);
        jsonResponse(['success' => true, 'message' => 'Tarea actualizada']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Error al actualizar tarea'], 500);
    }
}

// Eliminar (desactivar) tarea (solo admin)
if ($method === 'DELETE' && $action === 'delete') {
    requireAdmin();
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['error' => 'ID requerido'], 400);
    }
    
    // Desactivar en lugar de eliminar (soft delete)
    $stmt = $db->prepare("UPDATE tareas SET activa = 0 WHERE id = ?");
    
    try {
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Tarea eliminada']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Error al eliminar tarea'], 500);
    }
}

// Estadísticas de tareas (admin)
if ($method === 'GET' && $action === 'stats') {
    requireAdmin();
    
    $stmt = $db->query("
        SELECT 
            t.id,
            t.nombre,
            t.puntos,
            COUNT(tc.id) as veces_completada,
            SUM(CASE WHEN tc.estado = 'validada' THEN 1 ELSE 0 END) as veces_validada
        FROM tareas t
        LEFT JOIN tareas_completadas tc ON t.id = tc.tarea_id
        WHERE t.activa = 1
        GROUP BY t.id
        ORDER BY veces_completada DESC
    ");
    
    $stats = $stmt->fetchAll();
    jsonResponse(['stats' => $stats]);
}

// Acción no válida
jsonResponse(['error' => 'Acción no válida'], 400);