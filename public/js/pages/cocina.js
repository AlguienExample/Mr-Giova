let currentActiveOrders = [];
        let deliveredOrders = [];
        let soundEnabled = true;
        let audioCtx = null;
        let lastOrdersJson = '';
        let isDragging = false;
        let pollEnCurso = false;
        const cambiosEnCurso = new Set();

        function escCocina(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        window.addEventListener('DOMContentLoaded', () => {
            fetchActiveOrders();
            setInterval(fetchActiveOrders, 3000);
            setInterval(updateTimers, 1000);
            setupDragAndDrop();
        });

        function setupDragAndDrop() {
            const cols = [
                { id: 'col-Nuevo', state: 'Nuevo' },
                { id: 'col-En_Preparacion', state: 'En_Preparacion' },
                { id: 'col-Listo', state: 'Listo' },
                { id: 'col-Entregado', state: 'Entregado' }
            ];
            cols.forEach(colData => {
                const col = document.getElementById(colData.id);
                if (!col) return;
                col.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    col.style.opacity = '0.8';
                });
                col.addEventListener('dragleave', (e) => {
                    col.style.opacity = '1';
                });
                col.addEventListener('drop', (e) => {
                    e.preventDefault();
                    col.style.opacity = '1';
                    isDragging = false;
                    const pedidoId = e.dataTransfer.getData('text/plain');
                    if (pedidoId) {
                        changeState(pedidoId, colData.state);
                    }
                });
            });
        }

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
            if (!btn) return;
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
            if (isDragging || pollEnCurso || document.hidden) return;
            pollEnCurso = true;
            fetch('/api/pedidos/activos', { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(orders => {
                    detectNewOrders(orders);

                    const ordersString = JSON.stringify(orders);
                    if (ordersString === lastOrdersJson) {
                        return; // Evita borrar el DOM si no hay cambios (elimina el parpadeo)
                    }
                    lastOrdersJson = ordersString;

                    currentActiveOrders = orders;
                    renderKanban();
                })
                .catch(err => console.error("Error al obtener pedidos activos", err))
                .finally(() => { pollEnCurso = false; });
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
                    const prodNombre = det.producto ? det.producto.nombre : 'Producto';
                    itemsHtml += `<li class="order-item"><span class="order-item-qty">${det.cantidad}x</span> ${escCocina(prodNombre)}</li>`;
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
            card.setAttribute('draggable', 'true');
            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', order.id);
                isDragging = true;
                setTimeout(() => card.style.opacity = '0.5', 0);
            });
            card.addEventListener('dragend', (e) => {
                isDragging = false;
                card.style.opacity = '1';
                fetchActiveOrders();
            });

            const minutesElapsed = calculateMinutes(order.created_at);

            let itemsHtml = '';
            order.detalles.forEach(det => {
                const prodNombre = det.producto ? det.producto.nombre : 'Producto';
                itemsHtml += `<li class="order-item"><span class="order-item-qty">${det.cantidad}x</span> ${escCocina(prodNombre)}</li>`;
                if (det.notas_especiales) {
                    itemsHtml += `<li class="order-item-note"><i class="fa-solid fa-pencil"></i>${escCocina(det.notas_especiales)}</li>`;
                }
            });

            const noteSection = order.notas ? `<div class="order-card-note">${escCocina(order.notas)}</div>` : '';

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
            // Anti doble-submit: ignora si ya hay un cambio en curso para este pedido
            if (cambiosEnCurso.has(pedidoId)) return;
            cambiosEnCurso.add(pedidoId);
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
                if (data.success) {
                    lastOrdersJson = '';
                    fetchActiveOrders();
                } else alert('Error actualizando estado.');
            })
            .catch(err => console.error("Error al actualizar estado", err))
            .finally(() => cambiosEnCurso.delete(pedidoId));
        }

        function showKitchenToast(message) {
            const toast = document.getElementById('kitchenToast');
            document.getElementById('toastText').textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }