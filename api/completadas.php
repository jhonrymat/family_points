<?php
// api/completadas.php
// Manejo de tareas completadas (reclamar, validar, rechazar)

define('INCLUDED', true);
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db = Database::getInstance()->getConnection();

// Reclamar tarea completada
if ($method === 'POST' && $action === 'claim') {
    $user = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $tareaId = intval($data['tarea_id'] ?? 0);
    $notas = sanitizeInput($data['notas'] ?? '');
    
    if (!$tareaId) {
        jsonResponse(['error' => 'ID de tarea requerido'], 400);
    }
    
    // Verificar que la tarea existe y está activa
    $stmt = $db->prepare("SELECT * FROM tareas WHERE id = ? AND activa = 1");
    $stmt->execute([$tareaId]);
    $tarea = $stmt->fetch();
    
    if (!$tarea) {
        jsonResponse(['error' => 'Tarea no encontrada'], 404);
    }
    
    // Verificar si ya reclamó esta tarea hoy (para tareas diarias)
    if ($tarea['tipo'] === 'diaria') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM tareas_completadas 
            WHERE tarea_id = ? 
            AND usuario_id = ? 
            AND DATE(fecha_reclamada) = CURDATE()
            AND estado IN ('pendiente', 'validada')
        ");
        $stmt->execute([$tareaId, $user['id']]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            jsonResponse(['error' => 'Ya reclamaste esta tarea hoy'], 400);
        }
    }
    
    // Registrar tarea completada como pendiente
    $stmt = $db->prepare("
        INSERT INTO tareas_completadas (tarea_id, usuario_id, notas)
        VALUES (?, ?, ?)
    ");
    
    try {
        $stmt->execute([$tareaId, $user['id'], $notas]);
        $completadaId = $db->lastInsertId();
        
        jsonResponse([
            'success' => true,
            'message' => 'Tarea reclamada. Espera validación.',
            'completada_id' => $completadaId
        ], 201);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Error al reclamar tarea'], 500);
    }
}

// Listar tareas pendientes de validación (admin)
if ($method === 'GET' && $action === 'pending') {
    requireAdmin();
    
    $stmt = $db->query("
        SELECT 
            tc.*,
            t.nombre as tarea_nombre,
            t.puntos as tarea_puntos,
            u.nombre as usuario_nombre
        FROM tareas_completadas tc
        INNER JOIN tareas t ON tc.tarea_id = t.id
        INNER JOIN usuarios u ON tc.usuario_id = u.id
        WHERE tc.estado = 'pendiente'
        ORDER BY tc.fecha_reclamada DESC
    ");
    
    $pendientes = $stmt->fetchAll();
    jsonResponse(['pendientes' => $pendientes]);
}

// Validar tarea completada (admin)
if ($method === 'POST' && $action === 'validate') {
    $admin = requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $completadaId = intval($data['completada_id'] ?? 0);
    $notas = sanitizeInput($data['notas'] ?? '');
    
    if (!$completadaId) {
        jsonResponse(['error' => 'ID de tarea completada requerido'], 400);
    }
    
    // Obtener información de la tarea completada
    $stmt = $db->prepare("
        SELECT tc.*, t.puntos, t.nombre as tarea_nombre
        FROM tareas_completadas tc
        INNER JOIN tareas t ON tc.tarea_id = t.id
        WHERE tc.id = ? AND tc.estado = 'pendiente'
    ");
    $stmt->execute([$completadaId]);
    $completada = $stmt->fetch();
    
    if (!$completada) {
        jsonResponse(['error' => 'Tarea completada no encontrada o ya procesada'], 404);
    }
    
    try {
        $db->beginTransaction();
        
        // Actualizar estado a validada
        $stmt = $db->prepare("
            UPDATE tareas_completadas 
            SET estado = 'validada', 
                fecha_validada = NOW(), 
                validada_por = ?,
                notas = ?
            WHERE id = ?
        ");
        $stmt->execute([$admin['id'], $notas, $completadaId]);
        
        // Agregar puntos al usuario
        actualizarPuntosUsuario(
            $completada['usuario_id'],
            $completada['puntos'],
            'ganancia',
            'tarea',
            $completadaId,
            "Tarea validada: {$completada['tarea_nombre']}"
        );
        
        $db->commit();
        
        jsonResponse([
            'success' => true,
            'message' => 'Tarea validada y puntos otorgados'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Error al validar tarea'], 500);
    }
}

// Rechazar tarea completada (admin)
if ($method === 'POST' && $action === 'reject') {
    $admin = requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $completadaId = intval($data['completada_id'] ?? 0);
    $notas = sanitizeInput($data['notas'] ?? '');
    
    if (!$completadaId) {
        jsonResponse(['error' => 'ID de tarea completada requerido'], 400);
    }
    
    $stmt = $db->prepare("
        UPDATE tareas_completadas 
        SET estado = 'rechazada', 
            fecha_validada = NOW(), 
            validada_por = ?,
            notas = ?
        WHERE id = ? AND estado = 'pendiente'
    ");
    
    try {
        $stmt->execute([$admin['id'], $notas, $completadaId]);
        
        if ($stmt->rowCount() === 0) {
            jsonResponse(['error' => 'Tarea completada no encontrada'], 404);
        }
        
        jsonResponse(['success' => true, 'message' => 'Tarea rechazada']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Error al rechazar tarea'], 500);
    }
}

// Historial de tareas del usuario
if ($method === 'GET' && $action === 'history') {
    $user = requireAuth();
    $usuarioId = $user['rol'] === 'admin' && isset($_GET['usuario_id']) 
        ? intval($_GET['usuario_id']) 
        : $user['id'];
    
    $stmt = $db->prepare("
        SELECT 
            tc.*,
            t.nombre as tarea_nombre,
            t.puntos as tarea_puntos,
            v.nombre as validador_nombre
        FROM tareas_completadas tc
        INNER JOIN tareas t ON tc.tarea_id = t.id
        LEFT JOIN usuarios v ON tc.validada_por = v.id
        WHERE tc.usuario_id = ?
        ORDER BY tc.fecha_reclamada DESC
        LIMIT 50
    ");
    $stmt->execute([$usuarioId]);
    
    $historial = $stmt->fetchAll();
    jsonResponse(['historial' => $historial]);
}

// Estadísticas del usuario
if ($method === 'GET' && $action === 'user-stats') {
    $user = requireAuth();
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_reclamadas,
            SUM(CASE WHEN estado = 'validada' THEN 1 ELSE 0 END) as validadas,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
        FROM tareas_completadas
        WHERE usuario_id = ?
    ");
    $stmt->execute([$user['id']]);
    
    $stats = $stmt->fetch();
    jsonResponse(['stats' => $stats]);
}

// Acción no válida
jsonResponse(['error' => 'Acción no válida'], 400);