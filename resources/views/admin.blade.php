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
        /* Estilos: cuadricula de mesas */
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

        /* ===== INVENTARIO STYLES ===== */
        .inv-toolbar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; }
        .inv-search { flex: 1; min-width: 200px; padding: 10px 16px; border: 1.5px solid rgba(211,84,0,0.2); border-radius: 50px; font-size: 14px; outline: none; transition: all 0.2s; background: white; }
        .inv-search:focus { border-color: var(--color-terracotta); box-shadow: 0 0 0 3px rgba(211,84,0,0.12); }
        .inv-filter { padding: 10px 16px; border: 1.5px solid rgba(211,84,0,0.2); border-radius: 50px; font-size: 14px; outline: none; cursor: pointer; background: white; color: var(--color-charcoal-dark); transition: all 0.2s; }
        .inv-filter:focus { border-color: var(--color-terracotta); }
        .inv-table-wrap { overflow-x: auto; }
        .inv-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .inv-table th { background: var(--color-charcoal-dark); color: white; padding: 12px 14px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .inv-table th:first-child { border-radius: 8px 0 0 0; }
        .inv-table th:last-child { border-radius: 0 8px 0 0; }
        .inv-table td { padding: 12px 14px; border-bottom: 1px solid rgba(211,84,0,0.07); vertical-align: middle; }
        .inv-table tr:hover td { background-color: rgba(211,84,0,0.03); }
        .inv-table tr:last-child td { border-bottom: none; }
        .inv-product-img { width: 52px; height: 52px; border-radius: 8px; object-fit: cover; border: 2px solid var(--color-sand); }
        .inv-product-img-placeholder { width: 52px; height: 52px; border-radius: 8px; background: var(--color-sand); display: flex; align-items: center; justify-content: center; color: var(--color-muted); font-size: 20px; border: 2px dashed rgba(211,84,0,0.2); }
        .stock-badge { padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; display: inline-block; }
        .stock-badge.ok { background: #E8F8F5; color: var(--color-jalapeno); border: 1px solid rgba(39,174,96,0.3); }
        .stock-badge.low { background: #FEF9E7; color: #E67E22; border: 1px solid rgba(230,126,34,0.4); }
        .stock-badge.critical { background: #FDEDEC; color: #E74C3C; border: 1px solid rgba(231,76,60,0.35); }
        .avail-badge { padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; display: inline-block; }
        .avail-badge.on { background: #E8F8F5; color: var(--color-jalapeno); border: 1px solid rgba(39,174,96,0.3); }
        .avail-badge.off { background: #FDEDEC; color: #E74C3C; border: 1px solid rgba(231,76,60,0.3); }
        .inv-action-btn { background: none; border: none; cursor: pointer; padding: 6px 10px; border-radius: 6px; font-size: 14px; transition: all 0.2s; }
        .inv-action-btn.edit { color: var(--color-turquoise); } .inv-action-btn.edit:hover { background: rgba(26,188,156,0.1); }
        .inv-action-btn.del { color: #E74C3C; } .inv-action-btn.del:hover { background: rgba(231,76,60,0.1); }

        /* ===== MODAL INVENTARIO ===== */
        .inv-modal-overlay { position: fixed; inset: 0; background: rgba(30,43,55,0.55); backdrop-filter: blur(4px); z-index: 9000; display: none; align-items: center; justify-content: center; animation: fadeIn 0.2s ease; }
        .inv-modal-overlay.open { display: flex; }
        .inv-modal { background: white; border-radius: var(--border-radius-md); width: 100%; max-width: 640px; max-height: 90vh; overflow-y: auto; box-shadow: 0 30px 60px rgba(0,0,0,0.2); animation: slideUp 0.25s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .inv-modal-header { background: var(--color-charcoal-dark); color: white; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; border-radius: var(--border-radius-md) var(--border-radius-md) 0 0; }
        .inv-modal-header h3 { font-size: 18px; color: white; }
        .inv-modal-close { background: rgba(255,255,255,0.1); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
        .inv-modal-close:hover { background: rgba(255,255,255,0.25); }
        .inv-modal-body { padding: 28px 24px; }
        .inv-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .inv-form-group { display: flex; flex-direction: column; gap: 6px; }
        .inv-form-group.full { grid-column: 1 / -1; }
        .inv-form-group label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-muted); }
        .inv-form-group input, .inv-form-group select, .inv-form-group textarea { padding: 10px 14px; border: 1.5px solid rgba(211,84,0,0.2); border-radius: 8px; font-size: 14px; outline: none; transition: all 0.2s; font-family: var(--font-sans); background: white; color: var(--color-charcoal-dark); }
        .inv-form-group input:focus, .inv-form-group select:focus, .inv-form-group textarea:focus { border-color: var(--color-terracotta); box-shadow: 0 0 0 3px rgba(211,84,0,0.1); }
        .inv-form-group textarea { resize: vertical; min-height: 80px; }
        .inv-modal-footer { padding: 16px 24px; border-top: 1px solid rgba(211,84,0,0.1); display: flex; justify-content: flex-end; gap: 12px; background: #FDFAF9; border-radius: 0 0 var(--border-radius-md) var(--border-radius-md); }
        .inv-img-preview { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-top: 8px; display: none; border: 2px solid var(--color-sand); }
        .inv-disponible-toggle { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
        .toggle-switch { position: relative; width: 46px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; inset: 0; background-color: #ccc; border-radius: 24px; transition: .3s; }
        .toggle-slider:before { position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .3s; }
        .toggle-switch input:checked + .toggle-slider { background-color: var(--color-jalapeno); }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }
        .inv-empty-state { text-align: center; padding: 60px 20px; color: var(--color-muted); }
        .inv-empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.4; }
        .inv-empty-state p { font-size: 15px; }

        /* Delete confirm modal */
        .del-modal-overlay { position: fixed; inset: 0; background: rgba(30,43,55,0.6); backdrop-filter: blur(4px); z-index: 9100; display: none; align-items: center; justify-content: center; }
        .del-modal-overlay.open { display: flex; }
        .del-modal { background: white; border-radius: var(--border-radius-md); width: 100%; max-width: 420px; padding: 32px; text-align: center; box-shadow: 0 30px 60px rgba(0,0,0,0.25); animation: slideUp 0.2s ease; }
        .del-modal-icon { font-size: 48px; color: #E74C3C; margin-bottom: 16px; }
        .del-modal h3 { font-size: 20px; color: var(--color-charcoal-dark); margin-bottom: 10px; }
        .del-modal p { color: var(--color-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.5; }
        .del-modal-actions { display: flex; gap: 12px; justify-content: center; }
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
                        <a href="#" onclick="event.preventDefault(); switchTab('dashboard')"><i class="fa-solid fa-chart-pie"></i> Dashboard Admin</a>
                    </li>
                    <li class="sidebar-item" id="menu-pedidos">
                        <a href="#" onclick="event.preventDefault(); switchTab('pedidos')"><i class="fa-solid fa-receipt"></i> Pedidos e Historial</a>
                    </li>
                    <li class="sidebar-item" id="menu-inventario">
                        <a href="#" onclick="event.preventDefault(); switchTab('inventario')"><i class="fa-solid fa-boxes-stacked"></i> Inventario</a>
                    </li>
                    <li class="sidebar-item" id="menu-cocina">
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
                    <h1 id="pageTitle">Panel Administrativo</h1>
                    <p id="pageSubtitle">Control general de ventas, inventario y estado del local.</p>
                </div>
                
                <div class="user-profile-badge">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>{{ auth()->user()->nombres }}</span>
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
                                    <th>ID Pedido</th><th>Mesa</th><th>Cliente</th><th>Tipo</th>
                                    <th>Total</th><th>Fecha y Hora</th><th>Estado</th><th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody"></tbody>
                        </table>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px" id="historyPagination">
                        <span id="historyPaginationInfo" style="font-size:13px;color:var(--color-muted)">Mostrando 0 de 0 resultados</span>
                        <div style="display:flex;gap:8px">
                            <button class="btn-mrgiova-secondary" id="btnPrevPage" style="padding:6px 12px;font-size:13px" onclick="changeHistoryPage(-1)"><i class="fa-solid fa-chevron-left"></i> Anterior</button>
                            <button class="btn-mrgiova-secondary" id="btnNextPage" style="padding:6px 12px;font-size:13px" onclick="changeHistoryPage(1)">Siguiente <i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: INVENTARIO -->
            <div id="tab-content-inventario" style="display:none">
                <div class="mrgiova-card">
                    <!-- Toolbar -->
                    <div class="inv-toolbar">
                        <input type="text" id="invSearch" class="inv-search" placeholder="🔍  Buscar producto por nombre..." oninput="filterInventario()">
                        <select id="invCatFilter" class="inv-filter" onchange="filterInventario()">
                            <option value="">Todas las categorías</option>
                        </select>
                        <button class="btn-mrgiova" id="btnNuevoProducto" onclick="openProductModal()">
                            <i class="fa-solid fa-plus"></i> Nuevo Producto
                        </button>
                    </div>

                    <!-- Stats rápidas de inventario -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px" id="invStatsRow"></div>

                    <!-- Tabla de productos -->
                    <div class="inv-table-wrap">
                        <table class="inv-table">
                            <thead>
                                <tr>
                                    <th style="width:60px">Img</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Disponible</th>
                                    <th style="width:110px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="invTableBody"></tbody>
                        </table>
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
            <div style="padding: 15px 20px; border-top: 1px solid var(--color-sand); display: flex; justify-content: flex-end; gap: 10px; background-color: var(--color-sand-light);" id="detailModalFooter">
                <button class="btn-mrgiova" style="background-color: #E74C3C; font-size: 13px; padding: 8px 16px; border-radius: var(--border-radius-sm); border: none;" id="btnCancelarPedido" onclick="cancelarPedidoActual()">
                    <i class="fa-solid fa-ban"></i> Cancelar Pedido
                </button>
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
            if (tabParam === 'historial' || tabParam === 'pedidos') {
                switchTab('pedidos');
            } else if (tabParam === 'inventario') {
                switchTab('inventario');
            }
        });

        // Cambiar pestañas
        function switchTab(tabName) {
            currentTab = tabName;

            document.querySelectorAll('.sidebar-item').forEach(el => el.classList.remove('active'));
            const menuEl = document.getElementById(`menu-${tabName}`);
            if (menuEl) menuEl.classList.add('active');

            // Hide all tab panels
            ['dashboard','pedidos','inventario'].forEach(t => {
                const el = document.getElementById(`tab-content-${t}`);
                if (el) el.style.display = 'none';
            });

            if (tabName === 'dashboard') {
                document.getElementById('pageTitle').textContent = 'Panel Administrativo';
                document.getElementById('pageSubtitle').textContent = 'Control general de ventas, inventario y estado del local.';
                document.getElementById('tab-content-dashboard').style.display = 'block';
                fetchStats();
            } else if (tabName === 'pedidos') {
                document.getElementById('pageTitle').textContent = 'Pedidos e Historial';
                document.getElementById('pageSubtitle').textContent = 'Historial completo de pedidos, auditoría y control de comandas.';
                document.getElementById('tab-content-pedidos').style.display = 'block';
                fetchHistory();
            } else if (tabName === 'inventario') {
                document.getElementById('pageTitle').textContent = 'Gestión de Inventario';
                document.getElementById('pageSubtitle').textContent = 'Administra los productos del menú: stock, precios, disponibilidad y más.';
                document.getElementById('tab-content-inventario').style.display = 'block';
                fetchInventario();
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
            if (!statsData || !statsData.mesas) return;
            // Usar directamente los datos devueltos por el endpoint /api/admin/stats
            statsData.mesas.forEach(mesa => {
                const card = document.getElementById(`admin-mesa-${mesa.id}`);
                if (card) {
                    const estadoClass = mesa.estado.toLowerCase();
                    card.className = `table-status-card ${estadoClass}`;
                    const labelEl = card.querySelector('.table-status-card-label');
                    if (labelEl) {
                        labelEl.textContent = mesa.estado;
                    }
                }
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

        let currentDetailOrderId = null;

        // DETALLES MODAL EN HISTORIAL
        function openOrderDetail(pedidoId) {
            currentDetailOrderId = pedidoId;
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

                    // Lógica para mostrar/ocultar botón de cancelar
                    const btnCancelar = document.getElementById('btnCancelarPedido');
                    if (order.estado !== 'Entregado' && order.estado !== 'Cancelado') {
                        btnCancelar.style.display = 'inline-flex';
                    } else {
                        btnCancelar.style.display = 'none';
                    }

                    document.getElementById('orderDetailModal').classList.add('open');
                })
                .catch(err => {
                    console.error("Error al obtener detalle de pedido", err);
                    alert("No se pudo cargar el detalle del pedido.");
                });
        }

        function cancelarPedidoActual() {
            if (currentDetailOrderId) {
                cancelarPedido(currentDetailOrderId);
            }
        }

        function cancelarPedido(id) {
            if (confirm("¿Estás seguro de que deseas cancelar este pedido? Esta acción liberará la mesa y no se puede deshacer.")) {
                const btn = document.getElementById('btnCancelarPedido');
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Cancelando...`;
                
                fetch(`/api/pedidos/${id}/estado`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ estado: 'Cancelado' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Pedido cancelado exitosamente.");
                        closeDetailModal();
                        fetchStats(); // Actualiza estadísticas del dashboard
                        if (currentTab === 'pedidos') {
                            fetchHistory(); // Actualiza tabla de historial
                        }
                    } else {
                        alert("Error al cancelar el pedido: " + (data.error || "Intente de nuevo."));
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    console.error("Error al cancelar el pedido", err);
                    alert("Ocurrió un error al cancelar el pedido.");
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            }
        }

        function closeDetailModal() {
            document.getElementById('orderDetailModal').classList.remove('open');
        }

        function closeDetailModalOnBgClick(e) {
            if (e.target.id === 'orderDetailModal') {
                closeDetailModal();
            }
        }

        // ============================================================
        // ===== INVENTARIO (CRUD de Productos) =======================
        // ============================================================
        let invProductos = []; // cache de todos los productos
        let invCategorias = [];
        let editingProductId = null;
        let deletingProductId = null;

        async function fetchInventario() {
            try {
                const [prodRes, catRes] = await Promise.all([
                    fetch('/api/admin/productos'),
                    fetch('/api/admin/categorias')
                ]);
                invProductos = await prodRes.json();
                invCategorias = await catRes.json();
                populateCatFilter();
                renderInventario();
                renderInvStats();
            } catch (err) {
                console.error('Error cargando inventario', err);
            }
        }

        function populateCatFilter() {
            const sel = document.getElementById('invCatFilter');
            const currentVal = sel.value;
            sel.innerHTML = '<option value="">Todas las categorías</option>';
            invCategorias.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.nombre;
                sel.appendChild(opt);
            });
            sel.value = currentVal;

            // Populate modal category select too
            const mSel = document.getElementById('formCategoria');
            if (mSel) {
                mSel.innerHTML = '<option value="">-- Seleccionar --</option>';
                invCategorias.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.nombre;
                    mSel.appendChild(opt);
                });
            }
        }

        function filterInventario() {
            renderInventario();
        }

        function renderInventario() {
            const tbody = document.getElementById('invTableBody');
            const search = document.getElementById('invSearch').value.toLowerCase();
            const catId = document.getElementById('invCatFilter').value;

            let filtered = invProductos.filter(p => {
                const matchName = p.nombre.toLowerCase().includes(search);
                const matchCat = !catId || String(p.categoria_id) === String(catId);
                return matchName && matchCat;
            });

            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7"><div class="inv-empty-state"><i class="fa-solid fa-box-open"></i><p>No se encontraron productos.</p></div></td></tr>`;
                return;
            }

            const formatCOP = v => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v);

            tbody.innerHTML = '';
            filtered.forEach(p => {
                const tr = document.createElement('tr');
                const stockClass = p.stock <= 2 ? 'critical' : p.stock <= 10 ? 'low' : 'ok';
                const stockIcon = p.stock <= 2 ? '🔴' : p.stock <= 10 ? '🟡' : '🟢';
                const availClass = p.disponible ? 'on' : 'off';
                const availTxt  = p.disponible ? 'Activo' : 'Inactivo';

                const imgHtml = p.imagen_url
                    ? `<img src="${p.imagen_url}" class="inv-product-img" alt="${p.nombre}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                       <div class="inv-product-img-placeholder" style="display:none"><i class="fa-solid fa-image"></i></div>`
                    : `<div class="inv-product-img-placeholder"><i class="fa-solid fa-image"></i></div>`;

                tr.innerHTML = `
                    <td>${imgHtml}</td>
                    <td><strong>${p.nombre}</strong><br><small style="color:var(--color-muted);font-size:11px">${p.ingredientes ? p.ingredientes.substring(0,50)+'…' : ''}</small></td>
                    <td><span style="background:var(--color-sand);padding:3px 10px;border-radius:50px;font-size:12px;font-weight:600">${p.categoria ? p.categoria.nombre : '—'}</span></td>
                    <td><strong style="color:var(--color-terracotta)">${formatCOP(p.precio)}</strong></td>
                    <td><span class="stock-badge ${stockClass}">${stockIcon} ${p.stock} uds</span></td>
                    <td><span class="avail-badge ${availClass}">${availTxt}</span></td>
                    <td>
                        <button class="inv-action-btn edit" title="Editar" onclick="openProductModal(${p.id})"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="inv-action-btn del" title="Eliminar" onclick="openDeleteModal(${p.id})"><i class="fa-solid fa-trash-can"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderInvStats() {
            const total = invProductos.length;
            const activos = invProductos.filter(p => p.disponible).length;
            const stockBajo = invProductos.filter(p => p.stock <= 10).length;
            const criticos = invProductos.filter(p => p.stock <= 2).length;

            document.getElementById('invStatsRow').innerHTML = `
                <div style="background:#E8F8F5;border-radius:10px;padding:14px 18px;border:1px solid rgba(39,174,96,0.2)">
                    <div style="font-size:22px;font-weight:800;color:var(--color-jalapeno)">${total}</div>
                    <div style="font-size:12px;color:var(--color-muted);font-weight:600">Total Productos</div>
                </div>
                <div style="background:#E8F8F5;border-radius:10px;padding:14px 18px;border:1px solid rgba(39,174,96,0.2)">
                    <div style="font-size:22px;font-weight:800;color:var(--color-jalapeno)">${activos}</div>
                    <div style="font-size:12px;color:var(--color-muted);font-weight:600">Disponibles</div>
                </div>
                <div style="background:#FEF9E7;border-radius:10px;padding:14px 18px;border:1px solid rgba(230,126,34,0.25)">
                    <div style="font-size:22px;font-weight:800;color:#E67E22">${stockBajo}</div>
                    <div style="font-size:12px;color:var(--color-muted);font-weight:600">Stock Bajo (&le;10)</div>
                </div>
                <div style="background:#FDEDEC;border-radius:10px;padding:14px 18px;border:1px solid rgba(231,76,60,0.2)">
                    <div style="font-size:22px;font-weight:800;color:#E74C3C">${criticos}</div>
                    <div style="font-size:12px;color:var(--color-muted);font-weight:600">Críticos (&le;2)</div>
                </div>
            `;
        }

        // --- Modal Producto ---
        function openProductModal(id = null) {
            editingProductId = id;
            const modal = document.getElementById('invProductModal');
            const title = document.getElementById('invModalTitle');
            const form  = document.getElementById('invProductForm');

            form.reset();
            document.getElementById('invImgPreview').style.display = 'none';
            populateCatFilter();

            if (id) {
                title.textContent = 'Editar Producto';
                const p = invProductos.find(x => x.id === id);
                if (p) {
                    document.getElementById('formNombre').value       = p.nombre;
                    document.getElementById('formCategoria').value    = p.categoria_id;
                    document.getElementById('formPrecio').value       = p.precio;
                    document.getElementById('formStock').value        = p.stock;
                    document.getElementById('formTiempo').value       = p.tiempo_preparacion || '';
                    document.getElementById('formDescripcion').value  = p.descripcion || '';
                    document.getElementById('formIngredientes').value = p.ingredientes || '';
                    document.getElementById('formImagenUrl').value    = p.imagen_url || '';
                    document.getElementById('formDisponible').checked = p.disponible;
                    if (p.imagen_url) {
                        const img = document.getElementById('invImgPreview');
                        img.src = p.imagen_url;
                        img.style.display = 'block';
                    }
                }
            } else {
                title.textContent = 'Nuevo Producto';
                document.getElementById('formDisponible').checked = true;
                document.getElementById('formStock').value = 50;
            }
            modal.classList.add('open');
        }

        function closeProductModal() {
            document.getElementById('invProductModal').classList.remove('open');
            editingProductId = null;
        }

        async function saveProduct() {
            const btn = document.getElementById('btnSaveProduct');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

            const body = {
                categoria_id:       document.getElementById('formCategoria').value,
                nombre:             document.getElementById('formNombre').value,
                precio:             document.getElementById('formPrecio').value,
                stock:              document.getElementById('formStock').value,
                tiempo_preparacion: document.getElementById('formTiempo').value || null,
                descripcion:        document.getElementById('formDescripcion').value,
                ingredientes:       document.getElementById('formIngredientes').value,
                imagen_url:         document.getElementById('formImagenUrl').value || null,
                disponible:         document.getElementById('formDisponible').checked,
            };

            const isEdit = editingProductId !== null;
            const url    = isEdit ? `/api/admin/productos/${editingProductId}` : '/api/admin/productos';
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const res  = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (data.success) {
                    closeProductModal();
                    await fetchInventario();
                    showToast(isEdit ? 'Producto actualizado ✓' : 'Producto creado ✓', 'success');
                } else {
                    const msgs = data.errors ? Object.values(data.errors).flat().join('\n') : (data.error || 'Error desconocido');
                    showToast('Error: ' + msgs, 'error');
                }
            } catch(err) {
                console.error(err);
                showToast('Error de conexión', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }

        // --- Modal Eliminar ---
        function openDeleteModal(id) {
            deletingProductId = id;
            const p = invProductos.find(x => x.id === id);
            document.getElementById('delModalNombre').textContent = p ? p.nombre : '';
            document.getElementById('invDeleteModal').classList.add('open');
        }

        function closeDeleteModal() {
            document.getElementById('invDeleteModal').classList.remove('open');
            deletingProductId = null;
        }

        async function confirmDelete() {
            if (!deletingProductId) return;
            const btn = document.getElementById('btnConfirmDelete');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Eliminando...';
            try {
                const res  = await fetch(`/api/admin/productos/${deletingProductId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await res.json();
                if (data.success) {
                    closeDeleteModal();
                    await fetchInventario();
                    showToast(data.message || 'Producto eliminado ✓', data.message && data.message.includes('marcó') ? 'warning' : 'success');
                } else {
                    showToast(data.error || 'No se pudo eliminar', 'error');
                }
            } catch(err) {
                showToast('Error de conexión', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Sí, Eliminar';
            }
        }

        // --- Preview imagen en modal ---
        function previewImage() {
            const url = document.getElementById('formImagenUrl').value.trim();
            const img = document.getElementById('invImgPreview');
            if (url) {
                img.src = url;
                img.style.display = 'block';
                img.onerror = () => { img.style.display = 'none'; };
            } else {
                img.style.display = 'none';
            }
        }

        // --- Toast notification ---
        function showToast(msg, type = 'success') {
            const existing = document.getElementById('mrgiova-toast');
            if (existing) existing.remove();
            const colors = { success: '#27AE60', error: '#E74C3C', warning: '#E67E22' };
            const toast = document.createElement('div');
            toast.id = 'mrgiova-toast';
            toast.style.cssText = `position:fixed;bottom:28px;right:28px;background:${colors[type]};color:white;padding:14px 22px;border-radius:10px;font-weight:600;font-size:14px;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,0.2);animation:slideUp 0.3s ease;max-width:340px;line-height:1.4`;
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    </script>

    <!-- ===== MODAL: PRODUCTO (CREAR / EDITAR) ===== -->
    <div class="inv-modal-overlay" id="invProductModal" onclick="if(event.target===this)closeProductModal()">
        <div class="inv-modal">
            <div class="inv-modal-header">
                <h3 id="invModalTitle">Nuevo Producto</h3>
                <button class="inv-modal-close" onclick="closeProductModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="invProductForm" onsubmit="event.preventDefault(); saveProduct()">
                <div class="inv-modal-body">
                    <div class="inv-form-grid">
                        <div class="inv-form-group full">
                            <label>Nombre del Producto *</label>
                            <input type="text" id="formNombre" placeholder="Ej: Hamburguesa Doble Mr.Giova" required maxlength="150">
                        </div>
                        <div class="inv-form-group">
                            <label>Categoría *</label>
                            <select id="formCategoria" required>
                                <option value="">-- Seleccionar --</option>
                            </select>
                        </div>
                        <div class="inv-form-group">
                            <label>Precio (COP) *</label>
                            <input type="number" id="formPrecio" placeholder="22900" min="0" step="100" required>
                        </div>
                        <div class="inv-form-group">
                            <label>Stock (Unidades) *</label>
                            <input type="number" id="formStock" placeholder="50" min="0" required>
                        </div>
                        <div class="inv-form-group">
                            <label>Tiempo Preparación (min)</label>
                            <input type="number" id="formTiempo" placeholder="15" min="0">
                        </div>
                        <div class="inv-form-group full">
                            <label>Descripción</label>
                            <textarea id="formDescripcion" placeholder="Descripción corta del producto..."></textarea>
                        </div>
                        <div class="inv-form-group full">
                            <label>Ingredientes</label>
                            <textarea id="formIngredientes" placeholder="Ingrediente 1, Ingrediente 2, ..."></textarea>
                        </div>
                        <div class="inv-form-group full">
                            <label>URL de Imagen</label>
                            <input type="url" id="formImagenUrl" placeholder="https://..." oninput="previewImage()">
                            <img id="invImgPreview" class="inv-img-preview" alt="Vista previa">
                        </div>
                        <div class="inv-form-group full">
                            <label>Disponibilidad</label>
                            <div class="inv-disponible-toggle">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="formDisponible" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span style="font-size:14px;font-weight:600;color:var(--color-charcoal-dark)">Producto disponible en el menú</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="inv-modal-footer">
                    <button type="button" class="btn-mrgiova-secondary" onclick="closeProductModal()">Cancelar</button>
                    <button type="submit" class="btn-mrgiova" id="btnSaveProduct"><i class="fa-solid fa-floppy-disk"></i> Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL: CONFIRMAR ELIMINAR ===== -->
    <div class="del-modal-overlay" id="invDeleteModal" onclick="if(event.target===this)closeDeleteModal()">
        <div class="del-modal">
            <div class="del-modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3>¿Eliminar Producto?</h3>
            <p>Estás a punto de eliminar <strong id="delModalNombre"></strong>. Si tiene pedidos asociados, se marcará como no disponible en lugar de eliminarse.</p>
            <div class="del-modal-actions">
                <button class="btn-mrgiova-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn-mrgiova" id="btnConfirmDelete" style="background:#E74C3C" onclick="confirmDelete()"><i class="fa-solid fa-trash-can"></i> Sí, Eliminar</button>
            </div>
        </div>
    </div>

</body>
</html>
