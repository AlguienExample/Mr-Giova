<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mr.Giova - Panel Administrativo</title>
    <!-- CSS Estilos Mexicanos -->
    <link rel="stylesheet" href="{{ asset('css/restaurant.css') }}">
    <!-- FontAwesome para Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js para Gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Meta CSRF para peticiones AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Estilos específicos para la cuadricula de mesas en el Admin */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .table-status-card {
            background-color: white;
            border-radius: var(--border-radius-sm);
            padding: 15px;
            text-align: center;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: var(--box-shadow-sm);
            font-weight: 700;
        }
        .table-status-card.disponible { border-top: 5px solid var(--color-jalapeno); color: var(--color-jalapeno); }
        .table-status-card.ocupada { border-top: 5px solid var(--color-terracotta); color: var(--color-terracotta); }
        .table-status-card.reservada { border-top: 5px solid var(--color-cempasuchil); color: var(--color-cempasuchil); }
        .table-status-card.mantenimiento { border-top: 5px solid var(--color-muted); color: var(--color-muted); }
        .table-status-card-num { font-size: 20px; font-weight: 800; margin-bottom: 5px; }
        .table-status-card-label { font-size: 11px; text-transform: uppercase; opacity: 0.8; }
    </style>
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
                    <li class="sidebar-item active" id="menu-dashboard">
                        <a href="#" onclick="switchTab('dashboard')"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                    </li>
                    <li class="sidebar-item" id="menu-pedidos">
                        <a href="#" onclick="switchTab('pedidos')"><i class="fa-solid fa-receipt"></i> Pedidos e Historial</a>
                    </li>
                    <li class="sidebar-item">
                        <a href="/menu/mesa/5" target="_blank"><i class="fa-solid fa-utensils"></i> Menú de Clientes</a>
                    </li>
                    <li class="sidebar-item">
                        <a href="/cocina"><i class="fa-solid fa-fire-burner"></i> Tablero de Cocina</a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                <a href="#" class="sidebar-logout" onclick="alert('Sesión de Administración Finalizada')">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <header class="content-header">
                <div class="content-title">
                    <h1 id="pageTitle">Panel Administrativo</h1>
                    <p id="pageSubtitle">Control general de ventas, inventario y estado del local.</p>
                </div>
                
                <div class="user-profile-badge">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Administrador</span>
                </div>
            </header>

            <!-- TAB 1: DASHBOARD -->
            <div id="tab-content-dashboard">
                <!-- STATS CARDS GRID -->
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

                <!-- LOWER ADMIN GRID -->
                <div class="admin-lower-grid">
                    <!-- Ventas por día Chart -->
                    <div class="mrgiova-card chart-card">
                        <h3 style="margin-bottom: 15px;"><i class="fa-solid fa-chart-line" style="color:var(--color-terracotta);"></i> Ventas por Día (Semanal)</h3>
                        <div style="position: relative; height: 280px; width: 100%;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>

                    <!-- Productos más vendidos -->
                    <div class="mrgiova-card">
                        <h3><i class="fa-solid fa-fire" style="color:var(--color-cempasuchil);"></i> Más Vendidos</h3>
                        <div class="top-products-list" id="topProductsList">
                            <!-- Cargados dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- EXTRA ADMIN ROW -->
                <div class="admin-lower-grid" style="grid-template-columns: 1fr 2fr;">
                    <!-- Inventario bajo -->
                    <div class="mrgiova-card">
                        <h3><i class="fa-solid fa-circle-exclamation" style="color:#C0392B;"></i> Alertas de Stock</h3>
                        <div class="inventory-list" id="lowStockList">
                            <!-- Cargados dinámicamente -->
                        </div>
                    </div>

                    <!-- Estado de Mesas -->
                    <div class="mrgiova-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3><i class="fa-solid fa-chair" style="color:var(--color-turquoise);"></i> Estado de Mesas</h3>
                            <span style="font-size: 12px; color: var(--color-muted); font-weight: 600;">Monitoreo en vivo</span>
                        </div>
                        <div class="tables-grid" id="tablesStateGrid">
                            <!-- Cargados dinámicamente -->
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
                    <div class="history-filters">
                        <input type="text" id="historySearch" class="history-search-input" placeholder="Buscar por ID de pedido, Nro de mesa o Cliente..." oninput="searchHistory()">
                        
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
                            <tbody id="historyTableBody">
                                <!-- Cargado dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;" id="historyPagination">
                        <span id="historyPaginationInfo" style="font-size: 13px; color: var(--color-muted);">Mostrando 0 de 0 resultados</span>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn-mrgiova-secondary" id="btnPrevPage" style="padding: 6px 12px; font-size: 13px;" onclick="changeHistoryPage(-1)"><i class="fa-solid fa-chevron-left"></i> Anterior</button>
                            <button class="btn-mrgiova-secondary" id="btnNextPage" style="padding: 6px 12px; font-size: 13px;" onclick="changeHistoryPage(1)">Siguiente <i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL DETALLE DE PEDIDO HISTORIAL -->
    <div class="mrgiova-modal" id="orderDetailModal" onclick="closeDetailModalOnBgClick(event)">
        <div class="modal-content" style="max-height: 80%; border-radius: var(--border-radius-md); margin-top: 5vh; transform: translateY(0); padding-bottom: 20px;">
            <div style="padding: 20px; border-bottom: 2px solid var(--color-sand); display: flex; justify-content: space-between; align-items: center;">
                <h3 id="detailModalTitle" style="font-size: 20px;">Detalle del Pedido #----</h3>
                <button class="modal-close-btn" style="position: static; background-color: var(--color-sand); color: var(--color-charcoal-dark); border: 1px solid rgba(0,0,0,0.15);" onclick="closeDetailModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="modal-body" style="padding: 20px; overflow-y: auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; font-size: 13px; background-color: var(--color-sand); padding: 15px; border-radius: var(--border-radius-sm);">
                    <div>
                        <p><strong>Mesa:</strong> <span id="detailMesa">--</span></p>
                        <p style="margin-top: 4px;"><strong>Cliente:</strong> <span id="detailCliente">--</span></p>
                        <p style="margin-top: 4px;"><strong>Tipo de Pedido:</strong> <span id="detailTipo">--</span></p>
                    </div>
                    <div>
                        <p><strong>Estado:</strong> <span id="detailEstadoBadge" class="status-badge nuevo">--</span></p>
                        <p style="margin-top: 4px;"><strong>Prioridad:</strong> <span id="detailPrioridad">--</span></p>
                        <p style="margin-top: 4px;"><strong>Total:</strong> <span id="detailTotal" style="color:var(--color-terracotta); font-weight:800; font-size:14px;">--</span></p>
                    </div>
                </div>

                <!-- Lista de platillos -->
                <div class="modifier-group-title" style="margin-bottom: 8px;">Platillos Ordenados</div>
                <div class="mrgiova-table-wrapper" style="margin-bottom: 20px;">
                    <table class="mrgiova-table" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Cantidad</th>
                                <th>Platillo</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsTableBody">
                            <!-- Dinámico -->
                        </tbody>
                    </table>
                </div>

                <!-- Tiempos de preparación -->
                <div class="modifier-group-title" style="margin-bottom: 8px;">Tiempos del Proceso</div>
                <div style="font-size: 12px; display: flex; flex-direction: column; gap: 6px; background-color: var(--color-sand); padding: 15px; border-radius: var(--border-radius-sm);">
                    <p><strong>Hora de creación:</strong> <span id="timeCreado">--</span></p>
                    <p><strong>Inicio de preparación:</strong> <span id="timePreparacion">--</span></p>
                    <p><strong>Listo para servir:</strong> <span id="timeListo">--</span></p>
                    <p><strong>Hora de entrega:</strong> <span id="timeEntregado">--</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT DASHBOARD Y AUDITORÍA -->
    <script>
        let currentTab = 'dashboard';
        let statsData = null;
        let chartInstance = null;

        // Historial paginado
        let historyCurrentPage = 1;
        let historyLastPage = 1;
        let historyTotalRecords = 0;

        window.addEventListener('DOMContentLoaded', () => {
            fetchStats();
            // Polling stats cada 3 segundos
            setInterval(fetchStats, 3000);
            
            // Check query param for tab
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam === 'historial') {
                switchTab('pedidos');
            }
        });

        // Cambiar pestañas
        function switchTab(tabName) {
            currentTab = tabName;
            
            document.querySelectorAll('.sidebar-item').forEach(el => el.classList.remove('active'));
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

        // Obtener estadísticas
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

        // Renderizar KPIs
        function renderKpis() {
            if (!statsData) return;
            const kpis = statsData.kpis;
            
            const formatCol = (val) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(val);
            
            document.getElementById('kpi-ventas').textContent = formatCol(kpis.ventas_hoy);
            document.getElementById('kpi-pedidos').textContent = kpis.pedidos_hoy;
            document.getElementById('kpi-mesas').textContent = kpis.mesas_activas;
            document.getElementById('kpi-ticket').textContent = formatCol(kpis.ticket_promedio);
        }

        // Renderizar Gráfico
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
                        backgroundColor: 'rgba(211, 84, 0, 0.1)',
                        borderColor: '#D35400',
                        borderWidth: 3,
                        pointBackgroundColor: '#F39C12',
                        pointBorderColor: '#D35400',
                        pointHoverRadius: 8,
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value/1000 + 'k';
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Renderizar productos populares
        function renderTopProducts() {
            const container = document.getElementById('topProductsList');
            container.innerHTML = '';
            
            if (!statsData || statsData.productos_mas_vendidos.length === 0) {
                container.innerHTML = `<p style="color:var(--color-muted); font-size:13px; text-align:center;">No hay ventas registradas.</p>`;
                return;
            }

            const maxSold = Math.max(...statsData.productos_mas_vendidos.map(p => p.cantidad), 1);

            statsData.productos_mas_vendidos.forEach(p => {
                let percentage = (p.cantidad / maxSold) * 100;
                let item = document.createElement('div');
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

        // Alertas de Stock
        function renderLowStock() {
            const container = document.getElementById('lowStockList');
            container.innerHTML = '';

            if (!statsData || statsData.inventario_bajo.length === 0) {
                container.innerHTML = `<p style="color:var(--color-muted); font-size:13px; text-align:center;">Todo el inventario OK.</p>`;
                return;
            }

            statsData.inventario_bajo.forEach(item => {
                let row = document.createElement('div');
                row.className = 'inventory-item';
                row.innerHTML = `
                    <span class="inventory-item-name">${item.nombre}</span>
                    <span class="inventory-item-qty">${item.cantidad} ${item.unidad}</span>
                `;
                container.appendChild(row);
            });
        }

        // Estado en vivo de mesas
        function renderTablesState() {
            if (!statsData || !statsData.estado_mesas) return;
            // Laravel renderiza las mesas por defecto en Blade, pero las actualizamos si hay cambios.
            // Para mantener coherencia en vivo, llamamos a la API de mesas o mapeamos el estado en el DOM:
            fetch('/api/pedidos/activos')
                .then(res => res.json())
                .then(activeOrders => {
                    // Resetear todas las mesas a disponible en frontend
                    document.querySelectorAll('.table-status-card').forEach(card => {
                        card.className = 'table-status-card disponible';
                        card.querySelector('.table-status-card-label').textContent = 'Disponible';
                    });

                    // Marcar ocupadas basadas en pedidos activos (Nuevo, En Preparacion, Listo)
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

        // --- SECCIÓN: HISTORIAL Y AUDITORÍA ---
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
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color:var(--color-muted);">No se encontraron pedidos.</td></tr>`;
                return;
            }

            orders.forEach(order => {
                let fecha = new Date(order.created_at).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
                let totalFormatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(order.total);
                let clientName = order.cliente && order.cliente.usuario ? `${order.cliente.usuario.nombres} ${order.cliente.usuario.apellidos}` : 'Invitado';
                let estLower = order.estado.toLowerCase();

                let tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>#${order.id}</strong></td>
                    <td>Mesa ${order.mesa ? order.mesa.numero_mesa : '?' }</td>
                    <td>${clientName}</td>
                    <td>${order.tipo_pedido}</td>
                    <td><strong style="color:var(--color-terracotta);">${totalFormatted}</strong></td>
                    <td>${fecha}</td>
                    <td><span class="status-badge ${estLower === 'en_preparacion' ? 'preparacion' : estLower}">${order.estado}</span></td>
                    <td><button class="btn-mrgiova" style="padding: 5px 12px; font-size:12px;" onclick="openOrderDetail(${order.id})"><i class="fa-solid fa-eye"></i> Detalles</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderHistoryPagination() {
            document.getElementById('historyPaginationInfo').textContent = `Mostrando página ${historyCurrentPage} de ${historyLastPage} (${historyTotalRecords} pedidos totales)`;
            
            const btnPrev = document.getElementById('btnPrevPage');
            const btnNext = document.getElementById('btnNextPage');
            
            btnPrev.disabled = historyCurrentPage <= 1;
            btnPrev.style.opacity = historyCurrentPage <= 1 ? 0.5 : 1;
            
            btnNext.disabled = historyCurrentPage >= historyLastPage;
            btnNext.style.opacity = historyCurrentPage >= historyLastPage ? 0.5 : 1;
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

        // DETALLES MODAL EN HISTORIAL
        function openOrderDetail(pedidoId) {
            fetch(`/api/pedidos/${pedidoId}`)
                .then(res => res.json())
                .then(order => {
                    document.getElementById('detailModalTitle').textContent = `Detalle del Pedido #${order.id}`;
                    document.getElementById('detailMesa').textContent = `Mesa ${order.mesa ? order.mesa.numero_mesa : '?'}`;
                    document.getElementById('detailCliente').textContent = order.cliente && order.cliente.usuario ? `${order.cliente.usuario.nombres} ${order.cliente.usuario.apellidos} (${order.cliente.usuario.email})` : 'Cliente Invitado';
                    document.getElementById('detailTipo').textContent = order.tipo_pedido;
                    document.getElementById('detailPrioridad').textContent = order.prioridad;
                    
                    let totalFormatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(order.total);
                    document.getElementById('detailTotal').textContent = totalFormatted;

                    const estBadge = document.getElementById('detailEstadoBadge');
                    let estLower = order.estado.toLowerCase();
                    estBadge.className = `status-badge ${estLower === 'en_preparacion' ? 'preparacion' : estLower}`;
                    estBadge.textContent = order.estado;

                    // Renderizar platillos
                    const itemsTbody = document.getElementById('detailItemsTableBody');
                    itemsTbody.innerHTML = '';
                    order.detalles.forEach(det => {
                        let unitForm = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(det.precio_unitario);
                        let subForm = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(det.subtotal);
                        let noteHtml = det.notas_especiales ? `<br><small style="color:#C0392B; font-style:italic;"><i class="fa-solid fa-pencil"></i> ${det.notas_especiales}</small>` : '';

                        let tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${det.cantidad}</td>
                            <td><strong>${det.producto.nombre}</strong>${noteHtml}</td>
                            <td>${unitForm}</td>
                            <td><strong>${subForm}</strong></td>
                        `;
                        itemsTbody.appendChild(tr);
                    });

                    // Renderizar tiempos
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
