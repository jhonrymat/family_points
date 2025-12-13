// assets/js/app.js
// Aplicación principal del sistema de puntos familiar

let currentUser = null;
let tasks = [];
let rewards = [];

// Inicializar aplicación
document.addEventListener('DOMContentLoaded', async () => {
    await checkAuth();
    setupEventListeners();
    await loadData();
    
    if (currentUser.rol === 'admin') {
        await loadAdminData();
    }
});

// Verificar autenticación
async function checkAuth() {
    try {
        console.log('Checking authentication...');
        const response = await fetch('api/auth.php?action=check', {
            method: 'GET',
            credentials: 'include', // Importante: incluir cookies
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        
        console.log('Auth check response status:', response.status);
        
        const data = await response.json();
        console.log('Auth check data:', data);
        
        if (!data.authenticated) {
            console.log('Not authenticated, redirecting to login');
            window.location.href = 'login.php';
            return;
        }
        
        currentUser = data.usuario;
        console.log('User authenticated:', currentUser);
        updateUserUI();
    } catch (error) {
        console.error('Error verificando autenticación:', error);
        window.location.href = 'login.php';
    }
}

// Actualizar UI con información del usuario
function updateUserUI() {
    document.getElementById('welcomeText').textContent = `Hola, ${currentUser.nombre}!`;
    document.getElementById('roleText').textContent = currentUser.rol === 'admin' ? 'Administrador' : 'Miembro';
    document.getElementById('pointsDisplay').textContent = currentUser.puntos;
    
    if (currentUser.rol === 'admin') {
        document.getElementById('adminTab').classList.remove('hidden');
    }
}

// Configurar event listeners
function setupEventListeners() {
    // Logout
    document.getElementById('logoutBtn').addEventListener('click', logout);
    
    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });
}

// Cambiar de pestaña
function switchTab(tabName) {
    // Actualizar botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-purple-100', 'text-purple-700');
        btn.classList.add('text-gray-600', 'hover:bg-gray-100');
    });
    
    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    activeBtn.classList.add('active', 'bg-purple-100', 'text-purple-700');
    activeBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
    
    // Actualizar contenido
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('active');
    });
    
    const activeContent = document.querySelector(`[data-tab-content="${tabName}"]`);
    activeContent.classList.remove('hidden');
    activeContent.classList.add('active');
    
    // Cargar datos específicos
    if (tabName === 'history') {
        loadHistory();
    } else if (tabName === 'admin' && currentUser.rol === 'admin') {
        loadAdminData();
    }
}

// Cerrar sesión
async function logout() {
    try {
        await fetch('api/auth.php?action=logout', { method: 'POST' });
        window.location.href = 'login.php';
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    }
}

// Cargar datos iniciales
async function loadData() {
    await Promise.all([
        loadTasks(),
        loadRewards()
    ]);
}

// Cargar tareas
async function loadTasks() {
    try {
        const response = await fetch('api/tareas.php?action=list', {
            credentials: 'include'
        });
        
        if (!response.ok) {
            console.error('Error loading tasks, status:', response.status);
            if (response.status === 401) {
                window.location.href = 'login.php';
                return;
            }
            throw new Error('Error al cargar tareas');
        }
        
        const data = await response.json();
        tasks = data.tareas;
        renderTasks();
    } catch (error) {
        console.error('Error cargando tareas:', error);
        showNotification('Error al cargar tareas', 'error');
    }
}

// Renderizar tareas
function renderTasks() {
    const container = document.getElementById('tasksContainer');
    
    if (tasks.length === 0) {
        container.innerHTML = '<div class="col-span-full text-center py-12 text-gray-500"><p>No hay tareas disponibles</p></div>';
        return;
    }
    
    container.innerHTML = tasks.map(task => `
        <div class="bg-white rounded-xl shadow-md p-6 card-hover cursor-pointer" onclick="claimTask(${task.id})">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">${task.nombre}</h3>
                    <p class="text-sm text-gray-600">${task.descripcion || 'Sin descripción'}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: ${task.color}20; color: ${task.color}">
                    ${task.tipo}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    <span class="text-xl font-bold text-gray-800">${task.puntos}</span>
                    <span class="text-sm text-gray-600">puntos</span>
                </div>
                <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Reclamar
                </button>
            </div>
        </div>
    `).join('');
}

// Reclamar tarea
async function claimTask(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task) return;
    
    const confirm = await showConfirm(`¿Completaste la tarea "${task.nombre}"?`, 'Esta acción notificará al administrador para validación.');
    if (!confirm) return;
    
    try {
        const response = await fetch('api/completadas.php?action=claim', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tarea_id: taskId, notas: '' })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('¡Tarea reclamada! Espera la validación del administrador.', 'success');
        } else {
            showNotification(data.error || 'Error al reclamar tarea', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

// Cargar premios
async function loadRewards() {
    try {
        const response = await fetch('api/premios.php?action=list', {
            credentials: 'include'
        });
        
        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = 'login.php';
                return;
            }
            throw new Error('Error al cargar premios');
        }
        
        const data = await response.json();
        rewards = data.premios;
        renderRewards();
    } catch (error) {
        console.error('Error cargando premios:', error);
    }
}

// Renderizar premios
function renderRewards() {
    const container = document.getElementById('rewardsContainer');
    
    if (rewards.length === 0) {
        container.innerHTML = '<div class="col-span-full text-center py-12 text-gray-500"><p>No hay premios disponibles</p></div>';
        return;
    }
    
    const iconMap = {
        'robux': '💎',
        'tiempo': '⏰',
        'especial': '🎉'
    };
    
    container.innerHTML = rewards.map(reward => {
        const canRedeem = currentUser.puntos >= reward.costo_puntos;
        return `
            <div class="bg-white rounded-xl shadow-md p-6 card-hover ${canRedeem ? 'cursor-pointer' : 'opacity-60'}" ${canRedeem ? `onclick="redeemReward(${reward.id})"` : ''}>
                <div class="text-center mb-4">
                    <div class="text-5xl mb-2">${iconMap[reward.tipo] || '🎁'}</div>
                    <h3 class="text-lg font-bold text-gray-800">${reward.nombre}</h3>
                    <p class="text-sm text-gray-600 mt-2">${reward.descripcion || ''}</p>
                </div>
                <div class="border-t pt-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-600">Costo:</span>
                        <span class="text-xl font-bold text-purple-600">${reward.costo_puntos} pts</span>
                    </div>
                    <button class="${canRedeem ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed'} text-white w-full py-2 rounded-lg font-medium transition" ${!canRedeem ? 'disabled' : ''}>
                        ${canRedeem ? 'Canjear' : 'Puntos insuficientes'}
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Canjear premio
async function redeemReward(rewardId) {
    const reward = rewards.find(r => r.id === rewardId);
    if (!reward) return;
    
    const confirm = await showConfirm(
        `¿Canjear "${reward.nombre}"?`, 
        `Se descontarán ${reward.costo_puntos} puntos. El administrador procesará tu solicitud.`
    );
    if (!confirm) return;
    
    try {
        const response = await fetch('api/canjes.php?action=redeem', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ premio_id: rewardId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('¡Premio canjeado! Espera a que te lo entreguen.', 'success');
            currentUser.puntos -= reward.costo_puntos;
            updateUserUI();
            await loadRewards();
        } else {
            showNotification(data.error || 'Error al canjear premio', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

// Cargar historial
async function loadHistory() {
    try {
        const [tasksResponse, canjesResponse] = await Promise.all([
            fetch('api/completadas.php?action=history'),
            fetch('api/canjes.php?action=history')
        ]);
        
        const tasksData = await tasksResponse.json();
        const canjesData = await canjesResponse.json();
        
        renderHistory(tasksData.historial, canjesData.historial);
    } catch (error) {
        console.error('Error cargando historial:', error);
    }
}

// Renderizar historial
function renderHistory(tasksHistory, canjesHistory) {
    const container = document.getElementById('historyContainer');
    
    const allHistory = [
        ...tasksHistory.map(t => ({ ...t, type: 'task' })),
        ...canjesHistory.map(c => ({ ...c, type: 'canje' }))
    ].sort((a, b) => {
        const dateA = new Date(a.fecha_reclamada || a.fecha_canje);
        const dateB = new Date(b.fecha_reclamada || b.fecha_canje);
        return dateB - dateA;
    });
    
    if (allHistory.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-8">No hay historial aún</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="space-y-4">
            ${allHistory.map(item => {
                if (item.type === 'task') {
                    const statusColors = {
                        'pendiente': 'bg-yellow-100 text-yellow-800',
                        'validada': 'bg-green-100 text-green-800',
                        'rechazada': 'bg-red-100 text-red-800'
                    };
                    return `
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800">${item.tarea_nombre}</h4>
                                <p class="text-sm text-gray-600">${formatDate(item.fecha_reclamada)}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColors[item.estado]}">${item.estado}</span>
                                ${item.estado === 'validada' ? `<p class="text-green-600 font-bold mt-1">+${item.tarea_puntos} pts</p>` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    const statusColors = {
                        'pendiente': 'bg-yellow-100 text-yellow-800',
                        'entregado': 'bg-green-100 text-green-800'
                    };
                    return `
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800">🎁 ${item.premio_nombre}</h4>
                                <p class="text-sm text-gray-600">${formatDate(item.fecha_canje)}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColors[item.estado]}">${item.estado}</span>
                                <p class="text-red-600 font-bold mt-1">-${item.puntos_gastados} pts</p>
                            </div>
                        </div>
                    `;
                }
            }).join('')}
        </div>
    `;
}

// Cargar datos del admin
async function loadAdminData() {
    try {
        const [tasksResponse, canjesResponse] = await Promise.all([
            fetch('api/completadas.php?action=pending'),
            fetch('api/canjes.php?action=pending')
        ]);
        
        const tasksData = await tasksResponse.json();
        const canjesData = await canjesResponse.json();
        
        renderPendingTasks(tasksData.pendientes);
        renderPendingRedemptions(canjesData.pendientes);
        
        // Actualizar badges
        document.getElementById('pendingTasksBadge').textContent = tasksData.pendientes.length;
        document.getElementById('pendingRedemptionsBadge').textContent = canjesData.pendientes.length;
        
        // Mostrar notificación si hay pendientes
        const total = tasksData.pendientes.length + canjesData.pendientes.length;
        if (total > 0) {
            document.getElementById('notificationText').textContent = `Tienes ${total} elemento(s) pendiente(s) de revisión`;
            document.getElementById('notificationBanner').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error cargando datos admin:', error);
    }
}

// Renderizar tareas pendientes (admin)
function renderPendingTasks(pendientes) {
    const container = document.getElementById('pendingTasksContainer');
    
    if (pendientes.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No hay tareas pendientes</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="space-y-3">
            ${pendientes.map(item => `
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="font-semibold text-gray-800">${item.tarea_nombre}</h4>
                            <p class="text-sm text-gray-600">${item.usuario_nombre}</p>
                            <p class="text-xs text-gray-500 mt-1">${formatDate(item.fecha_reclamada)}</p>
                        </div>
                        <span class="text-lg font-bold text-purple-600">${item.tarea_puntos} pts</span>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="validateTask(${item.id}, true)" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-sm font-medium transition">
                            ✓ Validar
                        </button>
                        <button onclick="validateTask(${item.id}, false)" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm font-medium transition">
                            ✗ Rechazar
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

// Renderizar canjes pendientes (admin)
function renderPendingRedemptions(pendientes) {
    const container = document.getElementById('pendingRedemptionsContainer');
    
    if (pendientes.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No hay canjes pendientes</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="space-y-3">
            ${pendientes.map(item => `
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="font-semibold text-gray-800">🎁 ${item.premio_nombre}</h4>
                            <p class="text-sm text-gray-600">${item.usuario_nombre}</p>
                            <p class="text-xs text-gray-500 mt-1">${formatDate(item.fecha_canje)}</p>
                        </div>
                        <span class="text-lg font-bold text-green-600">${item.puntos_gastados} pts</span>
                    </div>
                    <button onclick="deliverReward(${item.id})" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-medium transition">
                        ✓ Marcar como entregado
                    </button>
                </div>
            `).join('')}
        </div>
    `;
}

// Validar tarea (admin)
async function validateTask(completadaId, approve) {
    const action = approve ? 'validate' : 'reject';
    const message = approve ? '¿Validar esta tarea?' : '¿Rechazar esta tarea?';
    
    const confirm = await showConfirm(message);
    if (!confirm) return;
    
    try {
        const response = await fetch(`api/completadas.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ completada_id: completadaId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(approve ? 'Tarea validada correctamente' : 'Tarea rechazada', 'success');
            await loadAdminData();
        } else {
            showNotification(data.error || 'Error al procesar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

// Entregar premio (admin)
async function deliverReward(canjeId) {
    const confirm = await showConfirm('¿Marcar este premio como entregado?');
    if (!confirm) return;
    
    try {
        const response = await fetch('api/canjes.php?action=deliver', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ canje_id: canjeId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Premio marcado como entregado', 'success');
            await loadAdminData();
        } else {
            showNotification(data.error || 'Error al entregar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

// Utilidades
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    
    if (hours < 1) return 'Hace un momento';
    if (hours < 24) return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
    
    return date.toLocaleDateString('es-ES', { 
        day: 'numeric', 
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showNotification(message, type = 'info') {
    // Implementar toast notifications
    alert(message);
}

async function showConfirm(title, message = '') {
    return confirm(`${title}\n${message}`);
}

// Modal functions (placeholder)
function openTaskModal() {
    alert('Función para crear tarea - Implementar modal');
}

function openRewardModal() {
    alert('Función para crear premio - Implementar modal');
}

function showStats() {
    alert('Función para mostrar estadísticas - Implementar');
}