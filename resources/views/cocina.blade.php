<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Mr.Giova - Panel de Cocina</title>
    <link rel="stylesheet" href="{{ asset('css/cocina.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="kitchen-page">
    <div class="kfc-stripe-top"></div>

    <div class="kitchen-sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <div class="kitchen-app">
        <!-- SIDEBAR -->
        <aside class="kitchen-sidebar" id="kitchenSidebar">
            <div>
                <div class="kitchen-sidebar-brand">
                    <div class="kitchen-sidebar-logo">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Mr.Giova" class="menu-logo" width="48" height="48">
                        <div>
                            <h2>Mr.<span>Giova</span></h2>
                            <div class="kitchen-sidebar-subtitle">Panel de Cocina</div>
                        </div>
                    </div>
                </div>
                <ul class="kitchen-nav">
                    <li class="kitchen-nav-item active">
                        <a href="/cocina"><i class="fa-solid fa-fire-burner"></i> Pedidos activos</a>
                    </li>
                    <li class="kitchen-nav-item">
                        <a href="/admin?tab=historial"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
                    </li>
                    <li class="kitchen-nav-item">
                        <a href="/menu/mesa/5" target="_blank"><i class="fa-solid fa-receipt"></i> Ver menú cliente</a>
                    </li>
                    <li class="kitchen-nav-item">
                        <a href="/admin"><i class="fa-solid fa-chart-line"></i> Dashboard admin</a>
                    </li>
                </ul>
            </div>
            <div class="kitchen-sidebar-footer">
                <a href="#" class="kitchen-logout" onclick="alert('Sesión de cocina finalizada'); return false;">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="kitchen-main">
            <header class="kitchen-header">
                <div class="kitchen-header-left">
                    <button class="kitchen-menu-toggle" type="button" id="menuToggle" onclick="toggleSidebar()" aria-label="Menú">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="kitchen-header-title">
                        <h1>Tablero de Cocina</h1>
                        <p>Monitoreo en tiempo real de preparación y entregas</p>
                    </div>
                </div>
                <div class="kitchen-header-actions">
                    <div class="kitchen-live-badge">
                        <span class="kitchen-live-dot"></span>
                        En vivo
                    </div>
                    <button class="kitchen-sound-btn" type="button" id="soundToggle" onclick="toggleSound()">
                        <i class="fa-solid fa-volume-high"></i>
                        <span>Sonido activado</span>
                    </button>
                    <div class="kitchen-user-badge">
                        <i class="fa-solid fa-kitchen-set"></i>
                        <span>Chef Principal</span>
                    </div>
                </div>
            </header>

            <div class="kitchen-board">
                <!-- PENDIENTE -->
                <div class="kitchen-column col-pending" id="col-Nuevo">
                    <div class="kitchen-column-header">
                        <span class="kitchen-column-title">Pendiente</span>
                        <span class="kitchen-column-count" id="count-Nuevo">0</span>
                    </div>
                    <div class="kitchen-cards" id="cards-Nuevo"></div>
                </div>

                <!-- EN PREPARACIÓN -->
                <div class="kitchen-column col-prep" id="col-En_Preparacion">
                    <div class="kitchen-column-header">
                        <span class="kitchen-column-title">En preparación</span>
                        <span class="kitchen-column-count" id="count-En_Preparacion">0</span>
                    </div>
                    <div class="kitchen-cards" id="cards-En_Preparacion"></div>
                </div>

                <!-- LISTO -->
                <div class="kitchen-column col-ready" id="col-Listo">
                    <div class="kitchen-column-header">
                        <span class="kitchen-column-title">Listo</span>
                        <span class="kitchen-column-count" id="count-Listo">0</span>
                    </div>
                    <div class="kitchen-cards" id="cards-Listo"></div>
                </div>

                <!-- ENTREGADO -->
                <div class="kitchen-column col-done" id="col-Entregado">
                    <div class="kitchen-column-header">
                        <span class="kitchen-column-title">Entregado</span>
                        <span class="kitchen-column-count" id="count-Entregado">0</span>
                    </div>
                    <div class="kitchen-cards" id="cards-Entregado">
                        <div class="kitchen-empty" id="deliveredEmpty">
                            <i class="fa-solid fa-circle-check"></i>
                            Los pedidos entregados aparecerán aquí brevemente.
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="kitchen-toast" id="kitchenToast">
        <i class="fa-solid fa-bell"></i>
        <span id="toastText">¡Nuevo pedido recibido!</span>
    </div>

    <script>
        let currentActiveOrders = [];
        let deliveredOrders = [];
        let soundEnabled = true;
        let audioCtx = null;

        window.addEventListener('DOMContentLoaded', () => {
            fetchActiveOrders();
            setInterval(fetchActiveOrders, 3000);
            setInterval(updateTimers, 1000);
        });

        function toggleSidebar() {
            document.getElementById('kitchenSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('visible');
        }

        function closeSidebar() {
            document.getElementById('kitchenSidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('visible');
        }

        function toggleSound() {
            soundEnabled = !soundEnabled;
            const btn = document.getElementById('soundToggle');
            if (soundEnabled) {
                btn.classList.remove('muted');
                btn.innerHTML = `<i class="fa-solid fa-volume-high"></i><span>Sonido activado</span>`;
            } else {
                btn.classList.add('muted');
                btn.innerHTML = `<i class="fa-solid fa-volume-xmark"></i><span>Sonido silenciado</span>`;
            }
        }

        function playChime() {
            if (!soundEnabled) return;
            try {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }

                let osc1 = audioCtx.createOscillator();
                let gain1 = audioCtx.createGain();
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(523.25, audioCtx.currentTime);
                gain1.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
                osc1.start();
                osc1.stop(audioCtx.currentTime + 0.4);

                setTimeout(() => {
                    let osc2 = audioCtx.createOscillator();
                    let gain2 = audioCtx.createGain();
                    osc2.connect(gain2);
                    gain2.connect(audioCtx.destination);
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(659.25, audioCtx.currentTime);
                    gain2.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
                    osc2.start();
                    osc2.stop(audioCtx.currentTime + 0.5);
                }, 150);
            } catch (e) {
                console.warn("No se pudo reproducir el sonido.", e);
            }
        }

        function fetchActiveOrders() {
            fetch('/api/pedidos/activos')
                .then(res => res.json())
                .then(orders => {
                    detectNewOrders(orders);
                    currentActiveOrders = orders;
                    renderKanban();
                })
                .catch(err => console.error("Error al obtener pedidos activos", err));
        }

        function detectNewOrders(newOrders) {
            if (currentActiveOrders.length > 0) {
                const oldIds = currentActiveOrders.map(o => o.id);
                const newOnly = newOrders.filter(o => !oldIds.includes(o.id));

                if (newOnly.length > 0) {
                    playChime();
                    const mesa = newOnly[0].mesa ? newOnly[0].mesa.numero_mesa : '?';
                    showKitchenToast(`¡Nuevo pedido de Mesa ${mesa}!`);
                }
            }
        }

        function renderKanban() {
            const columns = {
                'Nuevo': { count: 0, container: document.getElementById('cards-Nuevo') },
                'En_Preparacion': { count: 0, container: document.getElementById('cards-En_Preparacion') },
                'Listo': { count: 0, container: document.getElementById('cards-Listo') }
            };

            Object.values(columns).forEach(col => {
                col.container.innerHTML = '';
            });

            currentActiveOrders.forEach(order => {
                if (columns[order.estado]) {
                    columns[order.estado].count++;
                    columns[order.estado].container.appendChild(createOrderCard(order));
                }
            });

            Object.entries(columns).forEach(([key, col]) => {
                const countEl = document.getElementById(`count-${key}`);
                if (countEl) countEl.textContent = col.count;

                if (col.count === 0) {
                    col.container.innerHTML = `
                        <div class="kitchen-empty">
                            <i class="fa-solid fa-inbox"></i>
                            Sin pedidos en esta columna.
                        </div>`;
                }
            });

            renderDeliveredColumn();
        }

        function renderDeliveredColumn() {
            const container = document.getElementById('cards-Entregado');
            document.getElementById('count-Entregado').textContent = deliveredOrders.length;

            if (deliveredOrders.length === 0) {
                container.innerHTML = `
                    <div class="kitchen-empty" id="deliveredEmpty">
                        <i class="fa-solid fa-circle-check"></i>
                        Los pedidos entregados aparecerán aquí brevemente.
                    </div>`;
                return;
            }

            container.innerHTML = '';
            deliveredOrders.forEach(order => {
                const card = document.createElement('div');
                card.className = 'order-card done';

                const timeStr = new Date(order.hora_entregado || order.updated_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                let itemsHtml = '';
                order.detalles.forEach(det => {
                    itemsHtml += `<li class="order-item"><span class="order-item-qty">${det.cantidad}x</span> ${det.producto.nombre}</li>`;
                });

                card.innerHTML = `
                    <div class="order-card-top">
                        <span class="order-card-id">#${order.id}</span>
                        <span class="order-card-table">Mesa ${order.mesa ? order.mesa.numero_mesa : '?'}</span>
                    </div>
                    <div class="order-card-meta">
                        <span><i class="fa-solid fa-clock"></i> Entregado ${timeStr}</span>
                    </div>
                    <ul class="order-items">${itemsHtml}</ul>
                    <div class="order-delivered-label"><i class="fa-solid fa-circle-check"></i> Entregado</div>
                `;
                container.appendChild(card);
            });
        }

        function createOrderCard(order) {
            const card = document.createElement('div');
            const isPriority = order.prioridad === 'Alta' || order.prioridad === 'Urgente';
            card.className = 'order-card' + (isPriority ? ' priority' : '');
            card.setAttribute('data-order-id', order.id);

            const minutesElapsed = calculateMinutes(order.created_at);

            let itemsHtml = '';
            order.detalles.forEach(det => {
                itemsHtml += `<li class="order-item"><span class="order-item-qty">${det.cantidad}x</span> ${det.producto.nombre}</li>`;
                if (det.notas_especiales) {
                    itemsHtml += `<li class="order-item-note"><i class="fa-solid fa-pencil"></i>${det.notas_especiales}</li>`;
                }
            });

            const noteSection = order.notas ? `<div class="order-card-note">${order.notas}</div>` : '';

            let actionBtn = '';
            if (order.estado === 'Nuevo') {
                actionBtn = `<button class="btn-kfc btn-kfc-outline" type="button" onclick="changeState(${order.id}, 'En_Preparacion')"><i class="fa-solid fa-fire"></i> Preparar</button>`;
            } else if (order.estado === 'En_Preparacion') {
                actionBtn = `<button class="btn-kfc btn-kfc-red" type="button" onclick="changeState(${order.id}, 'Listo')"><i class="fa-solid fa-bell"></i> ¡Listo!</button>`;
            } else if (order.estado === 'Listo') {
                actionBtn = `<button class="btn-kfc btn-kfc-yellow" type="button" onclick="changeState(${order.id}, 'Entregado')"><i class="fa-solid fa-hand-holding-heart"></i> Entregar</button>`;
            }

            const priorityBadge = isPriority
                ? `<span class="order-priority-badge"><i class="fa-solid fa-bolt"></i>${order.prioridad}</span>`
                : '';

            const timerClass = minutesElapsed >= 15 ? 'timer-display urgent' : 'timer-display';

            card.innerHTML = `
                <div class="order-card-top">
                    <span class="order-card-id">${priorityBadge}#${order.id}</span>
                    <span class="order-card-table">Mesa ${order.mesa ? order.mesa.numero_mesa : '?'}</span>
                </div>
                <div class="order-card-meta">
                    <span><i class="fa-solid fa-clock"></i> Hace <b class="${timerClass}" data-start="${order.created_at}">${minutesElapsed}m</b></span>
                    <span>${new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                </div>
                <ul class="order-items">${itemsHtml}</ul>
                ${noteSection}
                <div class="order-card-footer">${actionBtn}</div>
            `;

            return card;
        }

        function calculateMinutes(dateStr) {
            return Math.floor((new Date() - new Date(dateStr)) / 60000);
        }

        function updateTimers() {
            document.querySelectorAll('.timer-display').forEach(el => {
                const start = new Date(el.getAttribute('data-start'));
                const secs = Math.floor((new Date() - start) / 1000);
                const mins = Math.floor(secs / 60);

                el.textContent = secs < 60 ? `${secs}s` : `${mins}m`;

                if (mins >= 15) el.classList.add('urgent');
                else el.classList.remove('urgent');
            });
        }

        function changeState(pedidoId, nuevoEstado) {
            if (nuevoEstado === 'Entregado') {
                const orderObj = currentActiveOrders.find(o => o.id === pedidoId);
                if (orderObj) {
                    orderObj.hora_entregado = new Date().toISOString();
                    deliveredOrders.unshift(orderObj);
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
                if (data.success) fetchActiveOrders();
                else alert('Error actualizando estado.');
            })
            .catch(err => console.error("Error al actualizar estado", err));
        }

        function showKitchenToast(message) {
            const toast = document.getElementById('kitchenToast');
            document.getElementById('toastText').textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
