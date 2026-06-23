<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mr.Giova - Panel de Cocina</title>
    <!-- CSS Estilos Mexicanos -->
    <link rel="stylesheet" href="{{ asset('css/restaurant.css') }}">
    <!-- FontAwesome para Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Meta CSRF para peticiones AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="mexican-border-top"></div>

    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div>
                <div class="sidebar-logo">
                    <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=200" alt="Logo Mr.Giova">
                    <h2>Mr.<span>Giova</span></h2>
                </div>
                <ul class="sidebar-menu">
                    @if(auth()->user()->rol->name === 'Administrador')
                    <li class="sidebar-item" id="menu-dashboard">
                        <a href="/admin"><i class="fa-solid fa-chart-pie"></i> Dashboard Admin</a>
                    </li>
                    <li class="sidebar-item" id="menu-pedidos">
                        <a href="/admin?tab=historial"><i class="fa-solid fa-receipt"></i> Pedidos e Historial</a>
                    </li>
                    @endif
                    <li class="sidebar-item active" id="menu-cocina">
                        <a href="/cocina"><i class="fa-solid fa-fire-burner"></i> Tablero de Cocina</a>
                    </li>
                    <li class="sidebar-item" id="menu-cliente">
                        <a href="/menu/mesa/5" target="_blank"><i class="fa-solid fa-utensils"></i> Menú de Clientes</a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                <a href="#" class="sidebar-logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <header class="content-header">
                <div class="content-title">
                    <h1>Tablero de Cocina (Kanban)</h1>
                    <p>Monitoreo en tiempo real de preparación y entregas.</p>
                </div>
                
                <div style="display: flex; align-items: center; gap: 15px;">
                    <!-- Activar Sonido -->
                    <button class="btn-mrgiova-secondary" id="soundToggle" onclick="toggleSound()" style="padding: 8px 16px; font-size: 14px;">
                        <i class="fa-solid fa-volume-high"></i> Sonido: Activado
                    </button>
                    
                    <div class="user-profile-badge">
                        <i class="fa-solid fa-kitchen-set"></i>
                        <span>{{ auth()->user()->nombres }} ({{ auth()->user()->rol->name }})</span>
                    </div>
                </div>
            </header>

            <!-- KANBAN BOARD -->
            <div class="kanban-board">
                <!-- COLUMNA: PENDIENTE -->
                <div class="kanban-column" id="col-Nuevo">
                    <div class="kanban-column-header">
                        <span class="kanban-column-title">Pendiente</span>
                        <span class="kanban-column-count" id="count-Nuevo">0</span>
                    </div>
                    <div class="kanban-cards-container" id="cards-Nuevo">
                        <!-- Pedidos Dinámicos -->
                    </div>
                </div>

                <!-- COLUMNA: EN PREPARACION -->
                <div class="kanban-column" id="col-En_Preparacion">
                    <div class="kanban-column-header">
                        <span class="kanban-column-title">En Preparación</span>
                        <span class="kanban-column-count" id="count-En_Preparacion">0</span>
                    </div>
                    <div class="kanban-cards-container" id="cards-En_Preparacion">
                        <!-- Pedidos Dinámicos -->
                    </div>
                </div>

                <!-- COLUMNA: LISTO -->
                <div class="kanban-column" id="col-Listo">
                    <div class="kanban-column-header">
                        <span class="kanban-column-title">Listo</span>
                        <span class="kanban-column-count" id="count-Listo">0</span>
                    </div>
                    <div class="kanban-cards-container" id="cards-Listo">
                        <!-- Pedidos Dinámicos -->
                    </div>
                </div>

                <!-- COLUMNA: ENTREGADO -->
                <div class="kanban-column" id="col-Entregado">
                    <div class="kanban-column-header">
                        <span class="kanban-column-title">Entregado</span>
                        <span class="kanban-column-count" id="count-Entregado">0</span>
                    </div>
                    <div class="kanban-cards-container" id="cards-Entregado" style="background-color: rgba(235, 245, 235, 0.4); border: 2px dashed rgba(39, 174, 96, 0.2);">
                        <!-- Recién entregados se acumulan temporalmente aquí -->
                        <div class="empty-placeholder" style="text-align: center; color: var(--color-muted); padding: 40px 10px; font-size: 13px;">
                            <i class="fa-solid fa-circle-check" style="font-size: 24px; color: var(--color-jalapeno); margin-bottom: 8px; display: block;"></i>
                            Los pedidos entregados aparecerán aquí brevemente.
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- NOTIFICACIONES TOAST -->
    <div class="toast-notification" id="kitchenToast">
        <i class="fa-solid fa-bell" style="color:var(--color-cempasuchil); font-size:18px;"></i>
        <span id="toastText">¡Nuevo pedido recibido!</span>
    </div>

    <!-- SCRIPT KANBAN Y POLLING -->
    <script>
        let currentActiveOrders = [];
        let deliveredOrders = []; // Almacenar entregados localmente durante la sesión actual
        let soundEnabled = true;
        let audioCtx = null;

        // Iniciar polling
        window.addEventListener('DOMContentLoaded', () => {
            fetchActiveOrders();
            setInterval(fetchActiveOrders, 3000);
            
            // Actualizar cronómetros cada segundo
            setInterval(updateTimers, 1000);
        });

        // Configuración de sonido con Web Audio API (Sintetizador nativo para evitar archivos rotos)
        function toggleSound() {
            soundEnabled = !soundEnabled;
            const btn = document.getElementById('soundToggle');
            if (soundEnabled) {
                btn.innerHTML = `<i class="fa-solid fa-volume-high"></i> Sonido: Activado`;
                btn.classList.remove('btn-mrgiova-secondary');
                btn.style.backgroundColor = 'white';
                btn.style.color = 'var(--color-terracotta)';
                
                // Inicializar o reanudar el contexto de audio en respuesta al clic del usuario
                try {
                    if (!audioCtx) {
                        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    }
                    if (audioCtx.state === 'suspended') {
                        audioCtx.resume();
                    }
                } catch (e) {
                    console.error("Error al reanudar AudioContext en toggleSound:", e);
                }
            } else {
                btn.innerHTML = `<i class="fa-solid fa-volume-xmark"></i> Sonido: Silenciado`;
                btn.style.backgroundColor = '#BDC3C7';
                btn.style.color = '#7F8C8D';
                btn.style.border = 'none';
            }
        }

        function playChime() {
            if (!soundEnabled) return;
            try {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
                
                // Nota 1
                let osc1 = audioCtx.createOscillator();
                let gain1 = audioCtx.createGain();
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(523.25, audioCtx.currentTime); // C5
                gain1.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
                osc1.start();
                osc1.stop(audioCtx.currentTime + 0.4);

                // Nota 2 (Armónica un poco después)
                setTimeout(() => {
                    let osc2 = audioCtx.createOscillator();
                    let gain2 = audioCtx.createGain();
                    osc2.connect(gain2);
                    gain2.connect(audioCtx.destination);
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(659.25, audioCtx.currentTime); // E5
                    gain2.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
                    osc2.start();
                    osc2.stop(audioCtx.currentTime + 0.5);
                }, 150);
            } catch (e) {
                console.warn("No se pudo reproducir el sonido: interacción requerida o API bloqueada.", e);
            }
        }

        // Obtener pedidos del servidor
        function fetchActiveOrders() {
            fetch('/api/pedidos/activos')
                .then(res => res.json())
                .then(orders => {
                    detectNewOrders(orders);
                    currentActiveOrders = orders;
                    renderKanban();
                })
                .catch(err => {
                    console.error("Error al obtener pedidos activos de cocina", err);
                });
        }

        // Detectar nuevos pedidos para sonar campana
        function detectNewOrders(newOrders) {
            if (currentActiveOrders.length > 0) {
                const oldIds = currentActiveOrders.map(o => o.id);
                const newOnly = newOrders.filter(o => !oldIds.includes(o.id));
                
                if (newOnly.length > 0) {
                    playChime();
                    showKitchenToast(`¡Nuevo pedido recibido de Mesa ${newOnly[0].mesa ? newOnly[0].mesa.numero_mesa : '?' }!`);
                }
            }
        }

        // Renderizar Kanban
        function renderKanban() {
            const columns = {
                'Nuevo': { count: 0, container: document.getElementById('cards-Nuevo') },
                'En_Preparacion': { count: 0, container: document.getElementById('cards-En_Preparacion') },
                'Listo': { count: 0, container: document.getElementById('cards-Listo') }
            };

            // Limpiar contenedores
            Object.values(columns).forEach(col => {
                col.container.innerHTML = '';
            });

            // Agrupar y renderizar pedidos activos
            currentActiveOrders.forEach(order => {
                if (columns[order.estado]) {
                    columns[order.estado].count++;
                    const card = createOrderCard(order);
                    columns[order.estado].container.appendChild(card);
                }
            });

            // Actualizar contadores
            document.getElementById('count-Nuevo').textContent = columns['Nuevo'].count;
            document.getElementById('count-En_Preparacion').textContent = columns['En_Preparacion'].count;
            document.getElementById('count-Listo').textContent = columns['Listo'].count;

            // Renderizar columna de entregados
            renderDeliveredColumn();
        }

        // Renderizar entregados locales
        function renderDeliveredColumn() {
            const container = document.getElementById('cards-Entregado');
            document.getElementById('count-Entregado').textContent = deliveredOrders.length;
            
            if (deliveredOrders.length === 0) {
                container.innerHTML = `
                    <div class="empty-placeholder" style="text-align: center; color: var(--color-muted); padding: 40px 10px; font-size: 13px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 24px; color: var(--color-jalapeno); margin-bottom: 8px; display: block;"></i>
                        Los pedidos entregados aparecerán aquí brevemente.
                    </div>`;
                return;
            }

            container.innerHTML = '';
            deliveredOrders.forEach(order => {
                const card = document.createElement('div');
                card.className = 'kanban-card';
                card.style.opacity = '0.75';
                
                let timeStr = new Date(order.hora_entregado || order.updated_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                let itemsList = '';
                order.detalles.forEach(det => {
                    itemsList += `<li class="kanban-card-item"><span>${det.cantidad}x</span> ${det.producto.nombre}</li>`;
                });

                card.innerHTML = `
                    <div class="kanban-card-header">
                        <span class="kanban-card-id">#${order.id}</span>
                        <span class="kanban-card-table">Mesa ${order.mesa ? order.mesa.numero_mesa : '?'}</span>
                    </div>
                    <div style="font-size: 12px; color: var(--color-muted); margin-bottom: 8px;">
                        Entregado a las: ${timeStr}
                    </div>
                    <ul class="kanban-card-items">${itemsList}</ul>
                    <div style="text-align: right; font-size: 12px; font-weight: 700; color: var(--color-jalapeno);">
                        <i class="fa-solid fa-circle-check"></i> Entregado
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Crear Tarjeta de Pedido
        function createOrderCard(order) {
            const card = document.createElement('div');
            card.className = 'kanban-card';
            card.setAttribute('data-order-id', order.id);
            card.setAttribute('data-time', order.created_at);

            // Calcular tiempo transcurrido
            const minutesElapsed = calculateMinutes(order.created_at);
            
            let itemsList = '';
            order.detalles.forEach(det => {
                let noteHtml = det.notas_especiales ? `<div style="font-size: 11px; color:#C0392B; padding-left: 20px; font-style:italic;"><i class="fa-solid fa-pencil"></i> ${det.notas_especiales}</div>` : '';
                itemsList += `<li class="kanban-card-item"><span>${det.cantidad}x</span> ${det.producto.nombre} ${noteHtml}</li>`;
            });

            // Nota general
            const noteSection = order.notas ? `<div class="kanban-card-note">${order.notas}</div>` : '';

            // Botones de acción según el estado
            let actionBtn = '';
            if (order.estado === 'Nuevo') {
                actionBtn = `<button class="btn-mrgiova-secondary" style="font-size:12px; padding: 6px 12px;" onclick="changeState(${order.id}, 'En_Preparacion')"><i class="fa-solid fa-fire"></i> Preparar</button>`;
            } else if (order.estado === 'En_Preparacion') {
                actionBtn = `<button class="btn-mrgiova" style="font-size:12px; padding: 6px 12px;" onclick="changeState(${order.id}, 'Listo')"><i class="fa-solid fa-bell"></i> ¡Listo!</button>`;
            } else if (order.estado === 'Listo') {
                actionBtn = `<button class="btn-mrgiova-success" style="font-size:12px; padding: 6px 12px;" onclick="changeState(${order.id}, 'Entregado')"><i class="fa-solid fa-hand-holding-dollar"></i> Entregar</button>`;
            }

            // Prioridad badge
            let priorityBadge = '';
            if (order.prioridad === 'Alta' || order.prioridad === 'Urgente') {
                priorityBadge = `<span style="background-color:#FADBD8; color:#C0392B; font-size:10px; font-weight:700; padding:2px 6px; border-radius:3px; margin-right:5px;"><i class="fa-solid fa-circle-exclamation"></i> ${order.prioridad}</span>`;
            }

            card.innerHTML = `
                <div class="kanban-card-header">
                    <span class="kanban-card-id">${priorityBadge}#${order.id}</span>
                    <span class="kanban-card-table">Mesa ${order.mesa ? order.mesa.numero_mesa : '?'}</span>
                </div>
                <div style="font-size:12px; color:var(--color-muted); margin-bottom:8px; display:flex; justify-content:space-between;">
                    <span><i class="fa-solid fa-clock"></i> Hace <b class="timer-display" data-start="${order.created_at}">${minutesElapsed}m</b></span>
                    <span>${new Date(order.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
                <ul class="kanban-card-items">${itemsList}</ul>
                ${noteSection}
                <div class="kanban-card-footer">
                    ${actionBtn}
                </div>
            `;

            return card;
        }

        // Calcular minutos transcurridos
        function calculateMinutes(dateStr) {
            const start = new Date(dateStr);
            const now = new Date();
            const diffMs = now - start;
            return Math.floor(diffMs / 60000);
        }

        // Actualizar cronómetros visualmente
        function updateTimers() {
            document.querySelectorAll('.timer-display').forEach(el => {
                const startStr = el.getAttribute('data-start');
                const start = new Date(startStr);
                const now = new Date();
                const diffMs = now - start;
                const secs = Math.floor(diffMs / 1000);
                
                if (secs < 60) {
                    el.textContent = `${secs}s`;
                } else {
                    const mins = Math.floor(secs / 60);
                    el.textContent = `${mins}m`;
                }
            });
        }

        // Cambiar estado en el servidor
        function changeState(pedidoId, nuevoEstado) {
            // Optimistic update: si es Entregado, moverlo a la lista local inmediatamente
            if (nuevoEstado === 'Entregado') {
                const orderObj = currentActiveOrders.find(o => o.id === pedidoId);
                if (orderObj) {
                    orderObj.hora_entregado = new Date().toISOString();
                    // Agregar al principio de entregados
                    deliveredOrders.unshift(orderObj);
                    // Mantener solo los últimos 5 entregados para no saturar
                    if (deliveredOrders.length > 5) deliveredOrders.pop();
                }
            }

            fetch(`/api/pedidos/${pedidoId}/estado`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ estado: nuevoEstado })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Recargar inmediatamente de la base de datos
                    fetchActiveOrders();
                } else {
                    alert('Error actualizando estado.');
                }
            })
            .catch(err => {
                console.error("Error al actualizar estado del pedido", err);
            });
        }

        // MOSTRAR TOAST COCINA
        function showKitchenToast(message) {
            const toast = document.getElementById('kitchenToast');
            document.getElementById('toastText').textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>
