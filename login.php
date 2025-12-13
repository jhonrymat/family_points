<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Puntos Familiar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-purple-100 rounded-full mb-4">
                <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistema de Puntos</h1>
            <p class="text-gray-600">Bienvenido a la familia</p>
        </div>

        <form id="loginForm" class="space-y-6">
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">Usuario</label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                    placeholder="Ingresa tu nombre"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                    placeholder="••••••••"
                >
            </div>

            <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            </div>

            <button 
                type="submit" 
                id="loginBtn"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105"
            >
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600">
            <p>Contraseña por defecto: <span class="font-mono bg-gray-100 px-2 py-1 rounded">familia2024</span></p>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const nombre = document.getElementById('nombre').value.trim();
            const password = document.getElementById('password').value;

            if (!nombre || !password) {
                showError('Por favor completa todos los campos');
                return;
            }

            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="animate-pulse">Iniciando sesión...</span>';
            errorMessage.classList.add('hidden');

            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    credentials: 'include', // Importante: incluir cookies
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ nombre, password })
                });

                const data = await response.json();
                console.log('Login response:', data);

                if (response.ok && data.success) {
                    console.log('Login successful, redirecting...');
                    // Pequeño delay para asegurar que la cookie se guarde
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 100);
                } else {
                    showError(data.error || 'Error al iniciar sesión');
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('Error de conexión. Intenta nuevamente.');
            } finally {
                loginBtn.disabled = false;
                loginBtn.innerHTML = 'Iniciar Sesión';
            }
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.remove('hidden');
            
            setTimeout(() => {
                errorMessage.classList.add('hidden');
            }, 5000);
        }

        // Verificar si ya está autenticado
        (async () => {
            try {
                const response = await fetch('api/auth.php?action=check', {
                    credentials: 'include'
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.authenticated) {
                        window.location.href = 'index.php';
                    }
                }
            } catch (error) {
                // No hacer nada, el usuario no está autenticado
                console.log('User not authenticated');
            }
        })();
    </script>
</body>
</html>