<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Mr.Giova - Panel Administrativo</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="admin-page">
    <div class="kfc-stripe-top"></div>

    <div class="admin-sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <div class="admin-app">
        <!-- SIDEBAR -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div>
                <div class="admin-sidebar-brand">
                    <div class="admin-sidebar-logo">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Mr.Giova" class="menu-logo" width="48" height="48">
                        <div>
                            <h2>Mr.<span>Giova</span></h2>
                            <div class="admin-sidebar-subtitle">Panel Administrativo</div>
                        </div>
                    </div>
                </div>
                <ul class="admin-nav">
                    <li class="admin-nav-item active" id="menu-dashboard">
                        <a href="#" onclick="switchTab('dashboard'); return false;"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                    </li>
                    <li class="admin-nav-item" id="menu-pedidos">
                        <a href="#" onclick="switchTab('pedidos'); return false;"><i class="fa-solid fa-receipt"></i> Pedidos e Historial</a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/menu/mesa/5" target="_blank"><i class="fa-solid fa-utensils"></i> Menú de Clientes</a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/cocina"><i class="fa-solid fa-fire-burner"></i> Tablero de Cocina</a>
                    </li>
                </ul>
            </div>

            <div class="admin-sidebar-footer">
                <a href="#" class="admin-logout" onclick="alert('Sesión de Administración Finalizada'); return false;">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="admin-header-left">
                    <button class="admin-menu-toggle" type="button" id="menuToggle" onclick="toggleSidebar()" aria-label="Menú">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="admin-header-title">
                        <h1 id="pageTitle">Panel Administrativo</h1>
                        <p id="pageSubtitle">Control general de ventas, inventario y estado del local.</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <div class="admin-user-badge">
                        <i class="fa-solid fa-user-tie"></i>
                        <span>Administrador</span>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- TAB 1: DASHBOARD -->
                <div id="tab-content-dashboard">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-info">
                                <h4>Ventas hoy</h4>
                                <div class="stat-value" id="kpi-ventas">$0</div>
                            </div>
                            <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <h4>Pedidos hoy</h4>
                                <div class="stat-value" id="kpi-pedidos">0</div>
                            </div>
                            <div class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <h4>Mesas activas</h4>
                                <div class="stat-value" id="kpi-mesas">0</div>
                            </div>
                            <div class="stat-icon"><i class="fa-solid fa-chair"></i></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <h4>Ticket promedio</h4>
                                <div class="stat-value" id="kpi-ticket">$0</div>
                            </div>
                            <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
                        </div>
                    </div>

                    <div class="admin-lower-grid">
                        <div class="mrgiova-card chart-card">
                            <h3><i class="fa-solid fa-chart-line"></i> Ventas por Día (Semanal)</h3>
                            <div style="position: relative; height: 280px; width: 100%; margin-top: 15px;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>

                        <div class="mrgiova-card">
                            <h3><i class="fa-solid fa-fire"></i> Más Vendidos</h3>
                            <div class="top-products-list" id="topProductsList"></div>
                        </div>
                    </div>

                    <div class="admin-lower-grid admin-lower-grid--wide-right">
                        <div class="mrgiova-card">
                            <h3><i class="fa-solid fa-circle-exclamation"></i> Alertas de Stock</h3>
                            <div class="inventory-list" id="lowStockList"></div>
                        </div>

                        <div class="mrgiova-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <h3><i class="fa-solid fa-chair"></i> Estado de Mesas</h3>
                                <span class="card-meta-label">Monitoreo en vivo</span>
                            </div>
                            <div class="tables-grid" id="tablesStateGrid">
                                @foreach ($mesas as $mesa)
                                    @php
                                        $estadoClass = strtolower($mesa->estado);
                                    @endphp
                                    <div class="table-status-card {{ $estadoClass }}" id="admin-mesa-{{ $mesa->id }}">
                                        <div class="table-status-card-num">Mesa {{ $mesa->numero_mesa }}</div>
                                        <div class="table-status-card-label">{{ $mesa->estado }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: HISTORIAL DE PEDIDOS -->
                <div id="tab-content-pedidos" style="display: none;">
                    <div class="mrgiova-card">
                        <h3 style="margin-bottom: 20px;"><i class="fa-solid fa-clock-rotate-left"></i> Historial de Pedidos</h3>

                        <div class="history-filters">
                            <div class="history-search-wrap">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="historySearch" class="history-search-input" placeholder="Buscar por ID de pedido, Nro de mesa o Cliente..." oninput="searchHistory()">
                            </div>

                            <select id="historyStatusFilter" class="history-status-select" onchange="searchHistory()">
                                <option value="Todos">Todos los Estados</option>
                                <option value="Nuevo">Nuevos</option>
                                <option value="En_Preparacion">En Preparación</option>
                                <option value="Listo">Listos</option>
                                <option value="Entregado">Entregados</option>
                                <option value="Cancelado">Cancelados</option>
                            </select>
                        </div>

                        <div class="mrgiova-table-wrapper">
                            <table class="mrgiova-table">
                                <thead>
                                    <tr>
                                        <th>ID Pedido</th>
                                        <th>Mesa</th>
                                        <th>Cliente</th>
                                        <th>Tipo</th>
                                        <th>Total</th>
                                        <th>Fecha y Hora</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody"></tbody>
                            </table>
                        </div>

                        <div class="history-pagination" id="historyPagination">
                            <span class="history-pagination-info" id="historyPaginationInfo">Mostrando 0 de 0 resultados</span>
                            <div class="history-pagination-actions">
                                <button class="btn-kfc btn-kfc-secondary" id="btnPrevPage" onclick="changeHistoryPage(-1)"><i class="fa-solid fa-chevron-left"></i> Anterior</button>
                                <button class="btn-kfc btn-kfc-secondary" id="btnNextPage" onclick="changeHistoryPage(1)">Siguiente <i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL DETALLE DE PEDIDO -->
    <div class="mrgiova-modal" id="orderDetailModal" onclick="closeDetailModalOnBgClick(event)">
        <div class="modal-content">
            <div class="modal-header-bar">
                <h3 id="detailModalTitle">Detalle del Pedido #----</h3>
                <button class="modal-close-btn" type="button" onclick="closeDetailModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <div class="detail-info-grid">
                    <div>
                        <p><strong>Mesa:</strong> <span id="detailMesa">--</span></p>
                        <p style="margin-top: 4px;"><strong>Cliente:</strong> <span id="detailCliente">--</span></p>
                        <p style="margin-top: 4px;"><strong>Tipo de Pedido:</strong> <span id="detailTipo">--</span></p>
                    </div>
                    <div>
                        <p><strong>Estado:</strong> <span id="detailEstadoBadge" class="status-badge nuevo">--</span></p>
                        <p style="margin-top: 4px;"><strong>Prioridad:</strong> <span id="detailPrioridad">--</span></p>
                        <p style="margin-top: 4px;"><strong>Total:</strong> <span id="detailTotal" class="text-price">--</span></p>
                    </div>
                </div>

                <div class="modifier-group-title">Platillos Ordenados</div>
                <div class="mrgiova-table-wrapper" style="margin-bottom: 20px;">
                    <table class="mrgiova-table">
                        <thead>
                            <tr>
                                <th>Cantidad</th>
                                <th>Platillo</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsTableBody"></tbody>
                    </table>
                </div>

                <div class="modifier-group-title">Tiempos del Proceso</div>
                <div class="detail-times-box">
                    <p><strong>Hora de creación:</strong> <span id="timeCreado">--</span></p>
                    <p><strong>Inicio de preparación:</strong> <span id="timePreparacion">--</span></p>
                    <p><strong>Listo para servir:</strong> <span id="timeListo">--</span></p>
                    <p><strong>Hora de entrega:</strong> <span id="timeEntregado">--</span></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'dashboard';
        let statsData = null;
        let chartInstance = null;

        let historyCurrentPage = 1;
        let historyLastPage = 1;
        let historyTotalRecords = 0;

        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('visible');
        }

        function closeSidebar() {
            document.getElementById('adminSidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('visible');
        }

        window.addEventListener('DOMContentLoaded', () => {
            fetchStats();
            setInterval(fetchStats, 3000);

            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam === 'historial') {
                switchTab('pedidos');
            }
        });

        function switchTab(tabName) {
            currentTab = tabName;
            closeSidebar();

            document.querySelectorAll('.admin-nav-item').forEach(el => el.classList.remove('active'));
            document.getElementById(`menu-${tabName}`).classList.add('active');

            if (tabName === 'dashboard') {
                document.getElementById('pageTitle').textContent = 'Panel Administrativo';
                document.getElementById('pageSubtitle').textContent = 'Control general de ventas, inventario y estado del local.';
                document.getElementById('tab-content-dashboard').style.display = 'block';
                document.getElementById('tab-content-pedidos').style.display = 'none';
                fetchStats();
            } else if (tabName === 'pedidos') {
                document.getElementById('pageTitle').textContent = 'Pedidos e Historial';
                document.getElementById('pageSubtitle').textContent = 'Historial completo de pedidos, auditoría y control de comandas.';
                document.getElementById('tab-content-dashboard').style.display = 'none';
                document.getElementById('tab-content-pedidos').style.display = 'block';
                fetchHistory();
            }
        }

        function fetchStats() {
            fetch('/api/admin/stats')
                .then(res => res.json())
                .then(data => {
                    statsData = data;
                    renderKpis();
                    if (currentTab === 'dashboard') {
                        renderSalesChart();
                        renderTopProducts();
                        renderLowStock();
                        renderTablesState();
                    }
                })
                .catch(err => {
                    console.error("Error al obtener estadísticas del dashboard", err);
                });
        }

        function renderKpis() {
            if (!statsData) return;
            const kpis = statsData.kpis;
            const formatCol = (val) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(val);

            document.getElementById('kpi-ventas').textContent = formatCol(kpis.ventas_hoy);
            document.getElementById('kpi-pedidos').textContent = kpis.pedidos_hoy;
            document.getElementById('kpi-mesas').textContent = kpis.mesas_activas;
            document.getElementById('kpi-ticket').textContent = formatCol(kpis.ticket_promedio);
        }

        function renderSalesChart() {
            if (!statsData || !statsData.ventas_por_dia) return;

            const labels = statsData.ventas_por_dia.map(item => item.dia);
            const values = statsData.ventas_por_dia.map(item => item.ventas);
            const ctx = document.getElementById('salesChart').getContext('2d');

            if (chartInstance) {
                chartInstance.data.labels = labels;
                chartInstance.data.datasets[0].data = values;
                chartInstance.update();
                return;
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas ($ COP)',
                        data: values,
                        backgroundColor: 'rgba(228, 0, 43, 0.08)',
                        borderColor: '#E4002B',
                        borderWidth: 3,
                        pointBackgroundColor: '#FFC72C',
                        pointBorderColor: '#E4002B',
                        pointHoverRadius: 8,
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value / 1000 + 'k';
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        function renderTopProducts() {
            const container = document.getElementById('topProductsList');
            container.innerHTML = '';

            if (!statsData || statsData.productos_mas_vendidos.length === 0) {
                container.innerHTML = '<p class="empty-state-text">No hay ventas registradas.</p>';
                return;
            }

            const maxSold = Math.max(...statsData.productos_mas_vendidos.map(p => p.cantidad), 1);

            statsData.productos_mas_vendidos.forEach(p => {
                const percentage = (p.cantidad / maxSold) * 100;
                const item = document.createElement('div');
                item.className = 'top-product-item';
                item.innerHTML = `
                    <span class="top-product-name">${p.nombre}</span>
                    <div class="top-product-progress-wrapper">
                        <div class="top-product-progress">
                            <div class="top-product-progress-bar" style="width: ${percentage}%;"></div>
                        </div>
                    </div>
                    <span class="top-product-qty">${p.cantidad}</span>
                `;
                container.appendChild(item);
            });
        }

        function renderLowStock() {
            const container = document.getElementById('lowStockList');
            container.innerHTML = '';

            if (!statsData || statsData.inventario_bajo.length === 0) {
                container.innerHTML = '<p class="empty-state-text">Todo el inventario OK.</p>';
                return;
            }

            statsData.inventario_bajo.forEach(item => {
                const row = document.createElement('div');
                row.className = 'inventory-item';
                row.innerHTML = `
                    <span class="inventory-item-name">${item.nombre}</span>
                    <span class="inventory-item-qty">${item.cantidad} ${item.unidad}</span>
                `;
                container.appendChild(row);
            });
        }

        function renderTablesState() {
            if (!statsData || !statsData.estado_mesas) return;

            fetch('/api/pedidos/activos')
                .then(res => res.json())
                .then(activeOrders => {
                    document.querySelectorAll('.table-status-card').forEach(card => {
                        card.className = 'table-status-card disponible';
                        card.querySelector('.table-status-card-label').textContent = 'Disponible';
                    });

                    activeOrders.forEach(order => {
                        if (order.mesa) {
                            const card = document.getElementById(`admin-mesa-${order.mesa.id}`);
                            if (card) {
                                card.className = 'table-status-card ocupada';
                                card.querySelector('.table-status-card-label').textContent = 'Ocupada';
                            }
                        }
                    });
                });
        }

        function fetchHistory() {
            const search = document.getElementById('historySearch').value;
            const estado = document.getElementById('historyStatusFilter').value;

            let url = `/api/pedidos?page=${historyCurrentPage}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (estado) url += `&estado=${encodeURIComponent(estado)}`;

            fetch(url)
                .then(res => res.json())
                .then(pagObj => {
                    historyCurrentPage = pagObj.current_page;
                    historyLastPage = pagObj.last_page;
                    historyTotalRecords = pagObj.total;
                    renderHistoryTable(pagObj.data);
                    renderHistoryPagination();
                })
                .catch(err => {
                    console.error("Error al obtener historial", err);
                });
        }

        function renderHistoryTable(orders) {
            const tbody = document.getElementById('historyTableBody');
            tbody.innerHTML = '';

            if (orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-muted" style="text-align:center; padding:30px;">No se encontraron pedidos.</td></tr>';
                return;
            }

            orders.forEach(order => {
                const fecha = new Date(order.created_at).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
                const totalFormatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(order.total);
                const clientName = order.cliente && order.cliente.usuario ? `${order.cliente.usuario.nombres} ${order.cliente.usuario.apellidos}` : 'Invitado';
                const estLower = order.estado.toLowerCase();

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>#${order.id}</strong></td>
                    <td>Mesa ${order.mesa ? order.mesa.numero_mesa : '?'}</td>
                    <td>${clientName}</td>
                    <td>${order.tipo_pedido}</td>
                    <td><strong class="text-price">${totalFormatted}</strong></td>
                    <td>${fecha}</td>
                    <td><span class="status-badge ${estLower === 'en_preparacion' ? 'preparacion' : estLower}">${order.estado}</span></td>
                    <td><button class="btn-kfc btn-kfc-primary" onclick="openOrderDetail(${order.id})"><i class="fa-solid fa-eye"></i> Detalles</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderHistoryPagination() {
            document.getElementById('historyPaginationInfo').textContent = `Mostrando página ${historyCurrentPage} de ${historyLastPage} (${historyTotalRecords} pedidos totales)`;

            const btnPrev = document.getElementById('btnPrevPage');
            const btnNext = document.getElementById('btnNextPage');

            btnPrev.disabled = historyCurrentPage <= 1;
            btnNext.disabled = historyCurrentPage >= historyLastPage;
        }

        function changeHistoryPage(val) {
            historyCurrentPage += val;
            if (historyCurrentPage < 1) historyCurrentPage = 1;
            if (historyCurrentPage > historyLastPage) historyCurrentPage = historyLastPage;
            fetchHistory();
        }

        function searchHistory() {
            historyCurrentPage = 1;
            fetchHistory();
        }

        function openOrderDetail(pedidoId) {
            fetch(`/api/pedidos/${pedidoId}`)
                .then(res => res.json())
                .then(order => {
                    document.getElementById('detailModalTitle').textContent = `Detalle del Pedido #${order.id}`;
                    document.getElementById('detailMesa').textContent = `Mesa ${order.mesa ? order.mesa.numero_mesa : '?'}`;
                    document.getElementById('detailCliente').textContent = order.cliente && order.cliente.usuario ? `${order.cliente.usuario.nombres} ${order.cliente.usuario.apellidos} (${order.cliente.usuario.email})` : 'Cliente Invitado';
                    document.getElementById('detailTipo').textContent = order.tipo_pedido;
                    document.getElementById('detailPrioridad').textContent = order.prioridad;

                    const totalFormatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(order.total);
                    document.getElementById('detailTotal').textContent = totalFormatted;

                    const estBadge = document.getElementById('detailEstadoBadge');
                    const estLower = order.estado.toLowerCase();
                    estBadge.className = `status-badge ${estLower === 'en_preparacion' ? 'preparacion' : estLower}`;
                    estBadge.textContent = order.estado;

                    const itemsTbody = document.getElementById('detailItemsTableBody');
                    itemsTbody.innerHTML = '';
                    order.detalles.forEach(det => {
                        const unitForm = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(det.precio_unitario);
                        const subForm = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(det.subtotal);
                        const noteHtml = det.notas_especiales ? `<br><small style="color:var(--kfc-red); font-style:italic;"><i class="fa-solid fa-pencil"></i> ${det.notas_especiales}</small>` : '';

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${det.cantidad}</td>
                            <td><strong>${det.producto.nombre}</strong>${noteHtml}</td>
                            <td>${unitForm}</td>
                            <td><strong class="text-price">${subForm}</strong></td>
                        `;
                        itemsTbody.appendChild(tr);
                    });

                    const formatTime = (timeStr) => timeStr ? new Date(timeStr).toLocaleString() : 'Pendiente / N/A';
                    document.getElementById('timeCreado').textContent = formatTime(order.created_at);
                    document.getElementById('timePreparacion').textContent = formatTime(order.hora_inicio_preparacion);
                    document.getElementById('timeListo').textContent = formatTime(order.hora_listo);
                    document.getElementById('timeEntregado').textContent = formatTime(order.hora_entregado);

                    document.getElementById('orderDetailModal').classList.add('open');
                })
                .catch(err => {
                    console.error("Error al obtener detalle de pedido", err);
                    alert("No se pudo cargar el detalle del pedido.");
                });
        }

        function closeDetailModal() {
            document.getElementById('orderDetailModal').classList.remove('open');
        }

        function closeDetailModalOnBgClick(e) {
            if (e.target.id === 'orderDetailModal') {
                closeDetailModal();
            }
        }
    </script>
</body>
</html>
