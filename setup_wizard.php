<?php
// setup_wizard.php
$message = '';
$error = '';
$step = 1;

// Seguridad: Bloquear si ya está instalado
if (file_exists(__DIR__ . '/api/config.local.php')) {
    die("El sistema ya está configurado. Por seguridad, elimina este archivo o 'api/config.local.php' para reconfigurar.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_and_save') {
        $host = $_POST['host'] ?? 'localhost';
        $user = $_POST['user'] ?? 'root';
        $pass = $_POST['pass'] ?? '';
        $name = $_POST['name'] ?? 'family_points';
        
        try {
            // Test connection without DB name first to check credentials
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name`");
            $pdo->exec("USE `$name`");
            
            // Generate config.local.php
            $configContent = "<?php\n";
            $configContent .= "define('DB_HOST', '$host');\n";
            $configContent .= "define('DB_NAME', '$name');\n";
            $configContent .= "define('DB_USER', '$user');\n";
            $configContent .= "define('DB_PASS', '$pass');\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
            
            file_put_contents(__DIR__ . '/api/config.local.php', $configContent);
            
            // Import SQL
            if (file_exists(__DIR__ . '/family_points.sql')) {
                $sql = file_get_contents(__DIR__ . '/family_points.sql');
                $pdo->exec($sql);
                $message = "Base de datos configurada y tablas creadas exitosamente.";
                $step = 2;
            } else {
                $error = "No se encontró el archivo family_points.sql";
            }
            
        } catch (PDOException $e) {
            $error = "Error de conexión: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente de Configuración - Family Points</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-md w-full">
        <h1 class="text-2xl font-bold text-center mb-6 text-purple-600">Configuración Inicial</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 1): ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="test_and_save">
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Host MySQL</label>
                <input type="text" name="host" value="localhost" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Usuario MySQL</label>
                <input type="text" name="user" value="root" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Contraseña MySQL</label>
                <input type="password" name="pass" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Nombre Base de Datos</label>
                <input type="text" name="name" value="family_points" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            
            <button type="submit" class="w-full bg-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-purple-700 transition">
                Instalar y Configurar
            </button>
        </form>
        <?php else: ?>
            <div class="text-center">
                <p class="mb-4 text-gray-600">El sistema está listo para usarse.</p>
                <a href="index.php" class="inline-block bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600 transition">
                    Ir al Inicio
                </a>
                <p class="mt-4 text-xs text-gray-500">Credenciales por defecto:<br>Admin / familia2024</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
