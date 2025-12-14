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
    
    const isAdmin = currentUser.rol === 'admin';
    
    container.innerHTML = tasks.map(task => `
        <div class="bg-white rounded-xl shadow-md p-6 card-hover relative" onclick="claimTask(${task.id})">
            ${isAdmin ? `
                <button onclick="event.stopPropagation(); openTaskModal(${task.id})" 
                    class="absolute top-4 right-4 text-gray-400 hover:text-purple-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
            ` : ''}
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1 ${isAdmin ? 'pr-8' : ''}">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">${task.nombre}</h3>
                    <p class="text-sm text-gray-600">${task.descripcion || 'Sin descripción'}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap" style="background-color: ${task.color}20; color: ${task.color}">
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
    if (!task) {
        console.error('Task not found:', taskId);
        return;
    }
    
    console.log('Claiming task:', task);
    
    const confirm = await showConfirm(
        `¿Completaste la tarea "${task.nombre}"?`, 
        'Esta acción notificará al administrador para validación.'
    );
    if (!confirm) return;
    
    try {
        console.log('Sending claim request...');
        const response = await fetch('api/completadas.php?action=claim', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tarea_id: taskId, notas: '' })
        });
        
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (response.ok && data.success) {
            showNotification('¡Tarea reclamada! Espera la validación del administrador.', 'success');
        } else {
            showNotification(data.error || 'Error al reclamar tarea', 'error');
        }
    } catch (error) {
        console.error('Error claiming task:', error);
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
    
    const isAdmin = currentUser.rol === 'admin';
    
    container.innerHTML = rewards.map(reward => {
        const canRedeem = currentUser.puntos >= reward.costo_puntos;
        return `
            <div class="bg-white rounded-xl shadow-md p-6 card-hover ${canRedeem ? '' : 'opacity-60'} relative" ${canRedeem ? `onclick="redeemReward(${reward.id})"` : ''}>
                ${isAdmin ? `
                    <button onclick="event.stopPropagation(); openRewardModal(${reward.id})" 
                        class="absolute top-4 right-4 text-gray-400 hover:text-green-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                ` : ''}
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
                    <button class="${canRedeem ? 'bg-green-600 hover:bg-green-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'} text-white w-full py-2 rounded-lg font-medium transition" ${!canRedeem ? 'disabled' : ''}>
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
    // Crear toast notification
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 transform transition-all duration-300 translate-x-0`;
    
    const colors = {
        success: 'bg-green-50 border-green-500',
        error: 'bg-red-50 border-red-500',
        info: 'bg-blue-50 border-blue-500',
        warning: 'bg-yellow-50 border-yellow-500'
    };
    
    const icons = {
        success: '✓',
        error: '✗',
        info: 'ℹ',
        warning: '⚠'
    };
    
    toast.innerHTML = `
        <div class="p-4 border-l-4 ${colors[type]}">
            <div class="flex items-start">
                <div class="flex-shrink-0 text-2xl mr-3">
                    ${icons[type]}
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">${message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="ml-3 text-gray-400 hover:text-gray-600">
                    <span class="text-xl">×</span>
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

async function showConfirm(title, message = '') {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full mx-4 transform transition-all">
                <h3 class="text-xl font-bold text-gray-900 mb-2">${title}</h3>
                ${message ? `<p class="text-gray-600 mb-6">${message}</p>` : ''}
                <div class="flex space-x-3">
                    <button id="confirmNo" class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition">
                        Cancelar
                    </button>
                    <button id="confirmYes" class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition">
                        Confirmar
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        document.getElementById('confirmYes').onclick = () => {
            modal.remove();
            resolve(true);
        };
        
        document.getElementById('confirmNo').onclick = () => {
            modal.remove();
            resolve(false);
        };
        
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.remove();
                resolve(false);
            }
        };
    });
}

// Modal functions
function openTaskModal(taskId = null) {
    const task = taskId ? tasks.find(t => t.id === taskId) : null;
    const isEdit = !!task;
    
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto';
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-2xl w-full mx-4 my-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">${isEdit ? 'Editar Tarea' : 'Nueva Tarea'}</h2>
            
            <form id="taskForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la tarea *</label>
                    <input type="text" id="taskName" required 
                        value="${task?.nombre || ''}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Ej: Tender la cama">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea id="taskDesc" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Descripción detallada de la tarea">${task?.descripcion || ''}</textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Puntos *</label>
                        <input type="number" id="taskPoints" required min="1"
                            value="${task?.puntos || ''}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="10">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                        <select id="taskType" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="diaria" ${task?.tipo === 'diaria' ? 'selected' : ''}>Diaria</option>
                            <option value="semanal" ${task?.tipo === 'semanal' ? 'selected' : ''}>Semanal</option>
                            <option value="especial" ${task?.tipo === 'especial' ? 'selected' : ''}>Especial</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                    <div class="flex space-x-2">
                        ${['#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B', '#EF4444', '#06B6D4'].map(color => `
                            <button type="button" onclick="selectColor('${color}')" 
                                class="w-10 h-10 rounded-lg border-2 ${task?.color === color ? 'border-gray-800' : 'border-transparent'}"
                                style="background-color: ${color}">
                            </button>
                        `).join('')}
                    </div>
                    <input type="hidden" id="taskColor" value="${task?.color || '#3B82F6'}">
                </div>
                
                ${isEdit ? `
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" id="taskActive" ${task?.activa ? 'checked' : ''}
                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-700">Tarea activa</span>
                    </label>
                </div>
                ` : ''}
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal()" 
                        class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                        class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition">
                        ${isEdit ? 'Guardar Cambios' : 'Crear Tarea'}
                    </button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    document.getElementById('taskForm').onsubmit = async (e) => {
        e.preventDefault();
        await saveTask(taskId);
    };
}

async function saveTask(taskId) {
    const taskData = {
        nombre: document.getElementById('taskName').value,
        descripcion: document.getElementById('taskDesc').value,
        puntos: parseInt(document.getElementById('taskPoints').value),
        tipo: document.getElementById('taskType').value,
        color: document.getElementById('taskColor').value
    };
    
    if (taskId) {
        taskData.id = taskId;
        taskData.activa = document.getElementById('taskActive')?.checked ? 1 : 0;
    }
    
    try {
        const url = taskId ? 'api/tareas.php?action=update' : 'api/tareas.php?action=create';
        const method = taskId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(taskData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(taskId ? 'Tarea actualizada correctamente' : 'Tarea creada correctamente', 'success');
            closeModal();
            await loadTasks();
        } else {
            showNotification(data.error || 'Error al guardar tarea', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

function selectColor(color) {
    document.getElementById('taskColor').value = color;
    document.querySelectorAll('[onclick^="selectColor"]').forEach(btn => {
        btn.classList.remove('border-gray-800');
        btn.classList.add('border-transparent');
    });
    event.target.classList.remove('border-transparent');
    event.target.classList.add('border-gray-800');
}

function openRewardModal(rewardId = null) {
    const reward = rewardId ? rewards.find(r => r.id === rewardId) : null;
    const isEdit = !!reward;
    
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto';
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-2xl w-full mx-4 my-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">${isEdit ? 'Editar Premio' : 'Nuevo Premio'}</h2>
            
            <form id="rewardForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del premio *</label>
                    <input type="text" id="rewardName" required 
                        value="${reward?.nombre || ''}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Ej: 40 Robux">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea id="rewardDesc" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Descripción del premio">${reward?.descripcion || ''}</textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Costo en puntos *</label>
                        <input type="number" id="rewardCost" required min="1"
                            value="${reward?.costo_puntos || ''}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                        <select id="rewardType" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="robux" ${reward?.tipo === 'robux' ? 'selected' : ''}>Robux</option>
                            <option value="tiempo" ${reward?.tipo === 'tiempo' ? 'selected' : ''}>Tiempo Extra</option>
                            <option value="especial" ${reward?.tipo === 'especial' ? 'selected' : ''}>Especial</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                    <input type="text" id="rewardQuantity"
                        value="${reward?.cantidad || ''}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Ej: 40, 30 min, 1">
                </div>
                
                ${isEdit ? `
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" id="rewardActive" ${reward?.activo ? 'checked' : ''}
                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-700">Premio activo</span>
                    </label>
                </div>
                ` : ''}
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal()" 
                        class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                        class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                        ${isEdit ? 'Guardar Cambios' : 'Crear Premio'}
                    </button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    document.getElementById('rewardForm').onsubmit = async (e) => {
        e.preventDefault();
        await saveReward(rewardId);
    };
}

async function saveReward(rewardId) {
    const rewardData = {
        nombre: document.getElementById('rewardName').value,
        descripcion: document.getElementById('rewardDesc').value,
        costo_puntos: parseInt(document.getElementById('rewardCost').value),
        tipo: document.getElementById('rewardType').value,
        cantidad: document.getElementById('rewardQuantity').value
    };
    
    if (rewardId) {
        rewardData.id = rewardId;
        rewardData.activo = document.getElementById('rewardActive')?.checked ? 1 : 0;
    }
    
    try {
        const url = rewardId ? 'api/premios.php?action=update' : 'api/premios.php?action=create';
        const method = rewardId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(rewardData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(rewardId ? 'Premio actualizado correctamente' : 'Premio creado correctamente', 'success');
            closeModal();
            await loadRewards();
        } else {
            showNotification(data.error || 'Error al guardar premio', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

function closeModal() {
    const modals = document.querySelectorAll('.fixed.inset-0.z-50');
    modals.forEach(modal => modal.remove());
}

function showStats() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto';
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-4xl w-full mx-4 my-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">📊 Estadísticas del Sistema</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
            </div>
            <div id="statsContent" class="text-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Cargando estadísticas...</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadStats();
}

async function loadStats() {
    try {
        const response = await fetch('api/tareas.php?action=stats', {
            credentials: 'include'
        });
        const data = await response.json();
        
        const container = document.getElementById('statsContent');
        container.innerHTML = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarea</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puntos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completadas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validadas</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${data.stats.map(stat => `
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">${stat.nombre}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">${stat.puntos}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">${stat.veces_completada}</td>
                                <td class="px-6 py-4 text-sm text-green-600 font-medium">${stat.veces_validada}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}