<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4285f4">
    <!-- favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Puntos">
    <link rel="apple-touch-icon" href="/assets/img/web-app-manifest-192x192.png">

    <title>Dashboard - Sistema de Puntos Familiar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .badge-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-white rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="text-white">
                        <h1 class="text-xl font-bold" id="welcomeText">Cargando...</h1>
                        <p class="text-sm opacity-90" id="roleText"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right text-white">
                        <p class="text-sm opacity-90">Puntos</p>
                        <p class="text-2xl font-bold" id="pointsDisplay">0</p>
                    </div>
                    <button id="logoutBtn" class="bg-white text-purple-600 hover:bg-gray-100 px-4 py-2 rounded-lg font-medium transition">
                        Salir
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Navigation Tabs -->
        <div class="bg-white rounded-xl shadow-md p-2 mb-8">
            <div class="flex space-x-2" id="tabsContainer">
                <button class="tab-btn active flex-1 py-3 px-4 rounded-lg font-medium transition" data-tab="tasks">
                    📋 Tareas
                </button>
                <button class="tab-btn flex-1 py-3 px-4 rounded-lg font-medium transition" data-tab="rewards">
                    🎁 Premios
                </button>
                <button class="tab-btn flex-1 py-3 px-4 rounded-lg font-medium transition" data-tab="history">
                    📊 Historial
                </button>
                <button class="tab-btn hidden flex-1 py-3 px-4 rounded-lg font-medium transition" data-tab="admin" id="adminTab">
                    ⚙️ Admin
                </button>
            </div>
        </div>

        <!-- Notifications Banner -->
        <div id="notificationBanner" class="hidden mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700" id="notificationText"></p>
                </div>
            </div>
        </div>

        <!-- Tab Contents -->
        <div id="tabContents">
            <!-- Tasks Tab -->
            <div class="tab-content active" data-tab-content="tasks">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="tasksContainer">
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto mb-4"></div>
                        <p>Cargando tareas...</p>
                    </div>
                </div>
            </div>

            <!-- Rewards Tab -->
            <div class="tab-content hidden" data-tab-content="rewards">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="rewardsContainer">
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <p>Cargando premios...</p>
                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div class="tab-content hidden" data-tab-content="history">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Tu Historial</h2>
                    <div id="historyContainer">
                        <p class="text-gray-500 text-center py-8">Cargando historial...</p>
                    </div>
                </div>
            </div>

            <!-- Admin Tab -->
            <div class="tab-content hidden" data-tab-content="admin">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Pending Tasks -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center justify-between">
                            <span>⏳ Tareas Pendientes</span>
                            <span class="badge-pulse bg-yellow-100 text-yellow-800 text-sm px-3 py-1 rounded-full" id="pendingTasksBadge">0</span>
                        </h2>
                        <div id="pendingTasksContainer">
                            <p class="text-gray-500 text-center py-4">No hay tareas pendientes</p>
                        </div>
                    </div>

                    <!-- Pending Redemptions -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center justify-between">
                            <span>🎁 Canjes Pendientes</span>
                            <span class="badge-pulse bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full" id="pendingRedemptionsBadge">0</span>
                        </h2>
                        <div id="pendingRedemptionsContainer">
                            <p class="text-gray-500 text-center py-4">No hay canjes pendientes</p>
                        </div>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="openTaskModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                        ➕ Nueva Tarea
                    </button>
                    <button onclick="openRewardModal()" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                        ➕ Nuevo Premio
                    </button>
                    <button onclick="showStats()" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                        📊 Estadísticas
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Container -->
    <div id="modalContainer"></div>

    <script src="assets/js/app.js"></script>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js')
            .then(() => console.log('Service Worker registrado'))
            .catch((err) => console.log('Error:', err));
    }

    let deferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
    // Prevenir el prompt automático
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostrar tu botón/mensaje personalizado
    mostrarBotonInstalar();
    });

    function mostrarBotonInstalar() {
    // Crear botón o banner personalizado
    const banner = document.createElement('div');
    banner.innerHTML = `
        <div style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); 
                    background: #4285f4; color: white; padding: 15px 25px; 
                    border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);
                    z-index: 1000; text-align: center;">
        <p style="margin: 0 0 10px 0;">¿Instalar Puntos en tu dispositivo?</p>
        <button id="instalar" style="background: white; color: #4285f4; 
                                        border: none; padding: 8px 20px; 
                                        border-radius: 5px; cursor: pointer;">
            Instalar
        </button>
        <button id="cancelar" style="background: transparent; color: white; 
                                        border: 1px solid white; padding: 8px 20px; 
                                        border-radius: 5px; margin-left: 10px; cursor: pointer;">
            Ahora no
        </button>
        </div>
    `;
    document.body.appendChild(banner);
    
    document.getElementById('instalar').addEventListener('click', async () => {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`Usuario ${outcome === 'accepted' ? 'aceptó' : 'rechazó'} la instalación`);
        banner.remove();
        deferredPrompt = null;
    });
    
    document.getElementById('cancelar').addEventListener('click', () => {
        banner.remove();
    });
    }
    </script>
</body>
</html>