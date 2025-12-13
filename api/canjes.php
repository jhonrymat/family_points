<?php
// api/canjes.php
// Manejo de canjes de premios

define('INCLUDED', true);
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db = Database::getInstance()->getConnection();

// Canjear premio
if ($method === 'POST' && $action === 'redeem') {
    $user = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $premioId = intval($data['premio_id'] ?? 0);
    $notas = sanitizeInput($data['notas'] ?? '');
    
    if (!$premioId) {
        jsonResponse(['error' => 'ID de premio requerido'], 400);
    }
    
    // Obtener información del premio
    $stmt = $db->prepare("SELECT * FROM premios WHERE id = ? AND activo = 1");
    $stmt->execute([$premioId]);
    $premio = $stmt->fetch();
    
    if (!$premio) {
        jsonResponse(['error' => 'Premio no encontrado'], 404);
    }
    
    // Verificar puntos suficientes
    $stmt = $db->prepare("SELECT puntos FROM usuarios WHERE id = ?");
    $stmt->execute([$user['id']]);
    $usuario = $stmt->fetch();
    
    if ($usuario['puntos'] < $premio['costo_puntos']) {
        jsonResponse(['error' => 'Puntos insuficientes'], 400);
    }
    
    try {
        $db->beginTransaction();
        
        // Registrar canje
        $stmt = $db->prepare("
            INSERT INTO canjes (premio_id, usuario_id, puntos_gastados, notas)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$premioId, $user['id'], $premio['costo_puntos'], $notas]);
        $canjeId = $db->lastInsertId();
        
        // Restar puntos
        actualizarPuntosUsuario(
            $user['id'],
            -$premio['costo_puntos'],
            'gasto',
            'canje',
            $canjeId,
            "Canje: {$premio['nombre']}"
        );
        
        $db->commit();
        
        jsonResponse([
            'success' => true,
            'message' => 'Premio canjeado. Espera la entrega.',
            'canje_id' => $canjeId
        ], 201);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Error al canjear premio'], 500);
    }
}

// Listar canjes pendientes (admin)
if ($method === 'GET' && $action === 'pending') {
    requireAdmin();
    
    $stmt = $db->query("
        SELECT 
            c.*,
            p.nombre as premio_nombre,
            p.tipo as premio_tipo,
            p.cantidad as premio_cantidad,
            u.nombre as usuario_nombre
        FROM canjes c
        INNER JOIN premios p ON c.premio_id = p.id
        INNER JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.estado = 'pendiente'
        ORDER BY c.fecha_canje DESC
    ");
    
    $pendientes = $stmt->fetchAll();
    jsonResponse(['pendientes' => $pendientes]);
}

// Marcar como entregado (admin)
if ($method === 'POST' && $action === 'deliver') {
    $admin = requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $canjeId = intval($data['canje_id'] ?? 0);
    $notas = sanitizeInput($data['notas'] ?? '');
    
    if (!$canjeId) {
        jsonResponse(['error' => 'ID de canje requerido'], 400);
    }
    
    $stmt = $db->prepare("
        UPDATE canjes 
        SET estado = 'entregado', 
            fecha_entrega = NOW(), 
            entregado_por = ?,
            notas = ?
        WHERE id = ? AND estado = 'pendiente'
    ");
    
    try {
        $stmt->execute([$admin['id'], $notas, $canjeId]);
        
        if ($stmt->rowCount() === 0) {
            jsonResponse(['error' => 'Canje no encontrado'], 404);
        }
        
        jsonResponse(['success' => true, 'message' => 'Premio marcado como entregado']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Error al marcar entrega'], 500);
    }
}

// Historial de canjes del usuario
if ($method === 'GET' && $action === 'history') {
    $user = requireAuth();
    $usuarioId = $user['rol'] === 'admin' && isset($_GET['usuario_id']) 
        ? intval($_GET['usuario_id']) 
        : $user['id'];
    
    $stmt = $db->prepare("
        SELECT 
            c.*,
            p.nombre as premio_nombre,
            p.tipo as premio_tipo,
            e.nombre as entregador_nombre
        FROM canjes c
        INNER JOIN premios p ON c.premio_id = p.id
        LEFT JOIN usuarios e ON c.entregado_por = e.id
        WHERE c.usuario_id = ?
        ORDER BY c.fecha_canje DESC
        LIMIT 50
    ");
    $stmt->execute([$usuarioId]);
    
    $historial = $stmt->fetchAll();
    jsonResponse(['historial' => $historial]);
}

jsonResponse(['error' => 'Acción no válida'], 400);