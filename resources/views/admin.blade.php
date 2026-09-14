<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo — Panel Administrativo</title>
    <meta name="description" content="Panel de administración de Sabor a Pueblo: ventas, cocina, reservas, inventario, mesas y personal.">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-light.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        try {
            if (localStorage.getItem('sabor-theme') === 'light') {
                document.addEventListener('DOMContentLoaded', function () {
                    document.body.classList.add('light-mode');
                });
            }
        } catch (e) {}
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Exportadores corporativos -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="admin-page">

<div class="kfc-stripe-top"></div>

<!-- ═══════════════════════════════════════════════════════════
     TOAST CONTAINER (notificaciones visuales)
═══════════════════════════════════════════════════════════ -->
<div id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<div class="admin-app">

    <!-- ═══════════════════════════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════════════════════════ -->
    <aside class="admin-sidebar" role="navigation" aria-label="Menú principal">
        <div class="admin-sidebar-brand" style="display:flex; align-items:center; gap:12px; padding: 24px 20px;">
            <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo Logo" style="width:44px; height:44px; border-radius:50%; object-fit:cover; border:2px solid var(--gold-dark); box-shadow: 0 4px 10px rgba(0,0,0,0.4);">
            <div>
                <h2 style="font-size:20px; color:#ffffff; font-weight:700; line-height:1.1;">Sabor<span style="color:var(--gold);"> a Pueblo</span></h2>
                <div class="subtitle" style="font-size:10px; text-transform:uppercase; letter-spacing:1.5px; color:var(--gold-dark); margin-top:2px;">Restaurante & Parrilla</div>
            </div>
        </div>

        <ul class="admin-nav">
            <li class="admin-nav-item active" id="menu-dashboard">
                <a href="#" onclick="switchTab('dashboard'); return false;" id="nav-dashboard">
                    <i class="fa-solid fa-chart-line" aria-hidden="true"></i> Analítica
                </a>
            </li>
            <li class="admin-nav-item" id="menu-reservas">
                <a href="#" onclick="switchTab('reservas'); return false;" id="nav-reservas">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i> Reservas
                </a>
            </li>
            <li class="admin-nav-item" id="menu-mesas">
                <a href="#" onclick="switchTab('mesas'); return false;" id="nav-mesas">
                    <i class="fa-solid fa-border-all" aria-hidden="true"></i> Plano de Mesas
                </a>
            </li>
            <li class="admin-nav-item" id="menu-personal">
                <a href="#" onclick="switchTab('personal'); return false;" id="nav-personal">
                    <i class="fa-solid fa-users-tie" aria-hidden="true"></i> Personal
                </a>
            </li>
            <li class="admin-nav-item" id="menu-inventario">
                <a href="#" onclick="switchTab('inventario'); return false;" id="nav-inventario">
                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Inventario
                </a>
            </li>
            <li class="admin-nav-item" id="menu-productos">
                <a href="#" onclick="switchTab('productos'); return false;" id="nav-productos">
                    <i class="fa-solid fa-utensils" aria-hidden="true"></i> Productos
                </a>
            </li>
            <li class="admin-nav-item" id="menu-pedidos">
                <a href="#" onclick="switchTab('pedidos'); return false;" id="nav-pedidos">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historial
                </a>
            </li>

            <div style="height: 1px; background: var(--border); margin: 12px 20px;"></div>

            <li class="admin-nav-item">
                <a href="/caja"><i class="fa-solid fa-cash-register"></i> Terminal Caja</a>
            </li>
            <li class="admin-nav-item">
                <a href="/menu/mesa/5" target="_blank"><i class="fa-solid fa-receipt"></i> Menú Cliente</a>
            </li>
        </ul>

        <div class="admin-sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-black" style="display:flex; justify-content:center; align-items:center; width:100%; border:none; cursor:pointer; padding:12px;">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════════════════════════
         ÁREA PRINCIPAL
    ═══════════════════════════════════════════════════════════ -->
    <main class="admin-main" role="main">

        <!-- Header -->
        <header class="admin-header">
            <div class="admin-header-title">
                <h1 id="pageTitle" style="display:flex; align-items:center; gap:12px;">
                    <span class="title-bar"></span>
                    <span id="pageTitleText">Panel Administrativo</span>
                    <span class="badge-parrilla-gold" style="font-size:10px;"><i class="fa-solid fa-crown"></i> Executive Management</span>
                </h1>
                <div id="pageSubtitle" class="content-subtitle" style="margin-top:4px; margin-left:14px;">
                    Control general de ventas, inventario, comensales y personal.
                </div>
            </div>
            <div class="admin-header-actions">
                <button class="theme-toggle-btn" type="button" onclick="toggleTheme()" aria-label="Cambiar a modo claro" title="Cambiar a modo claro">
                    <i class="fa-solid fa-sun"></i>
                </button>
                <div style="display:flex; align-items:center; gap:12px; border-left:1px solid var(--border); padding-left:20px;">
                    <div style="text-align:right;">
                        <strong style="display:block; font-size:13px; color:var(--black);"><i class="fa-solid fa-user-shield" style="color:var(--gold);margin-right:4px;"></i> Administrador</strong>
                        <span style="font-size:11px; color:var(--gray-400);">admin@mrgiova.com</span>
                    </div>
                    <div style="width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--gold),var(--gold-dark)); display:flex; align-items:center; justify-content:center; color:var(--white); font-weight:700; font-family:var(--font-serif); font-size:16px;">
                        A
                    </div>
                </div>
            </div>
        </header>

        <div class="admin-content">

            <!-- ─────────────────────────────────────────────────
                 TAB 1: DASHBOARD (Analítica)
            ───────────────────────────────────────────────── -->
            <div id="tab-content-dashboard">
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-label"><i class="fa-solid fa-wallet"></i> Ventas de Hoy</div>
                        <div class="card-value" id="kpi-ventas">$0</div>
                        <div class="card-subtext" id="kpi-ventas-subtext">+12.5% vs ayer <i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label"><i class="fa-solid fa-receipt"></i> Ticket Promedio</div>
                        <div class="card-value" id="kpi-ticket">$0</div>
                        <div class="card-subtext" style="color:var(--gray-400)">Óptimo <i class="fa-solid fa-check"></i></div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label"><i class="fa-solid fa-utensils"></i> Ocupación de Mesas</div>
                        <div class="card-value" id="kpi-ocupacion">0%</div>
                        <div class="card-subtext" style="color:var(--gray-400)"><span id="kpi-mesas-text">0/0 Mesas</span></div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label" style="color:var(--danger)"><i class="fa-solid fa-triangle-exclamation"></i> Alertas Stock</div>
                        <div class="card-value" id="kpi-alertas" style="color:var(--danger)">0</div>
                        <div class="card-subtext alert">Requiere Acción <i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                </div>

                <div class="dashboard-lower-grid">
                    <div class="panel-box">
                        <div class="panel-title">
                            Ventas Semanales
                            <div style="display:flex; gap:10px;">
                                <button class="btn-black" id="btn-export-pdf" style="padding:6px 14px; font-size:10px;" onclick="exportarPDF()">
                                    <i class="fa-regular fa-file-pdf"></i> PDF
                                </button>
                                <button class="btn-outline" id="btn-export-excel" style="padding:6px 14px; font-size:10px;" onclick="exportarExcel()">
                                    <i class="fa-regular fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                        <div style="height:300px; width:100%; position:relative;">
                            <canvas id="salesChart" aria-label="Gráfica de ventas semanales"></canvas>
                        </div>
                    </div>

                    <div class="panel-box">
                        <div class="panel-title">Platos Premium</div>
                        <ul class="premium-list" id="premiumList">
                            <li><span style="color:var(--gray-400); font-style:italic;">Cargando...</span></li>
                        </ul>
                        <div style="margin-top:20px;">
                            <button class="btn-black" style="width:100%;">VER MENÚS COMPLETOS</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────
                 TAB 2: RESERVAS
            ───────────────────────────────────────────────── -->
            <div id="tab-content-reservas" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:14px;">
                    <div>
                        <div class="content-title">Agenda de Reservas</div>
                        <div class="content-subtitle">Gestión de comensales y eventos especiales.</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                        <input type="date" id="filtroFechaReservas" class="form-control" style="width:auto;" onchange="fetchReservas()">
                        <button class="btn-gold" onclick="openModal('modalReserva')" id="btn-nueva-reserva">
                            <i class="fa-solid fa-plus"></i> Nueva Reserva
                        </button>
                    </div>
                </div>

                <div style="display:flex; gap:28px;">
                    <div style="flex:2;" id="reservasList">
                        <div style="text-align:center; padding:60px 0; color:var(--gray-400);">
                            <i class="fa-regular fa-calendar" style="font-size:40px; display:block; margin-bottom:12px; opacity:0.3;"></i>
                            Selecciona una fecha para ver las reservas.
                        </div>
                    </div>
                    <div style="flex:1;">
                        <div class="panel-box" style="padding:0; overflow:hidden;">
                            <img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&q=80&w=600"
                                 style="width:100%; height:260px; object-fit:cover; display:block;"
                                 alt="Ambiente del restaurante">
                            <div style="position:relative; padding:24px; background:var(--white);">
                                <div style="font-size:10px; text-transform:uppercase; letter-spacing:2px; color:var(--gold-dark); margin-bottom:8px; font-weight:700;">Recomendación del Chef</div>
                                <div style="font-family:var(--font-serif); font-size:20px; font-style:italic; color:var(--black);">Risotto al Oro Negro con Trufa Fresca</div>
                                <a href="#" onclick="switchTab('inventario'); return false;" style="font-size:12px; margin-top:12px; display:inline-block; color:var(--gold-dark); font-weight:600;">
                                    Ver Inventario de Trufa →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────
                 TAB 3: PLANO DE MESAS
            ───────────────────────────────────────────────── -->
            <div id="tab-content-mesas" style="display:none;">
                <div style="display:flex; gap:20px; margin-bottom:20px; align-items:center; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:6px; font-size:11px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">
                        <div style="width:12px;height:12px;background:var(--gold-bg);border:2px solid var(--gold);border-radius:2px;"></div>
                        Disponible
                    </div>
                    <div style="display:flex; align-items:center; gap:6px; font-size:11px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">
                        <div style="width:12px;height:12px;background:var(--black);border-radius:2px;"></div>
                        Ocupada
                    </div>
                    <div style="display:flex; align-items:center; gap:6px; font-size:11px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">
                        <div style="width:12px;height:12px;background:var(--gray-300);border-radius:2px;"></div>
                        Reservada
                    </div>
                </div>

                <div class="mesas-layout">
                    <div class="mesas-map" id="mapaMesas">
                        <div id="mesasGrid" style="position:relative; width:100%; height:100%;"></div>
                    </div>

                    <div class="mesas-sidebar">
                        <div class="mesa-detail-card" id="mesaDetailCard" style="margin-bottom: 20px;">
                            <h3>Mesa <span id="md-num">--</span></h3>
                            <div class="detail-row">
                                <span>Estado</span>
                                <strong id="md-estado">--</strong>
                            </div>
                            <div class="detail-row">
                                <span>Capacidad</span>
                                <strong id="md-cap">--</strong>
                            </div>
                            <div class="detail-row">
                                <span>Zona</span>
                                <strong id="md-zona">--</strong>
                            </div>
                            <div class="detail-row">
                                <span>Personal</span>
                                <strong id="md-personal">Ninguno</strong>
                            </div>
                            <div style="margin-top:28px;">
                                <button class="btn-black" style="width:100%; margin-bottom:10px;" onclick="openModal('modalComanda')">
                                    <i class="fa-solid fa-pen-to-square"></i> Gestionar Comanda
                                </button>
                                <button id="btnMesaFactura" class="btn-outline" style="width:100%; display:none;">
                                    <i class="fa-regular fa-file-lines"></i> Ver Factura
                                </button>
                            </div>
                        </div>

                        <!-- Panel de Staff Draggable -->
                        <div class="panel-box" style="padding: 20px;">
                            <h4 style="font-family:var(--font-serif); margin-bottom:12px; font-size:15px; color:var(--black); display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-people-carry-box" style="color:var(--gold);"></i> Brigada de Servicio
                            </h4>
                            <p style="font-size:11px; color:var(--gray-400); margin-bottom:12px;">Arrastra a un empleado a una mesa para asignarlo.</p>
                            <div id="staffDraggableList" style="display:flex; flex-direction:column; gap:8px; max-height:240px; overflow-y:auto;">
                                <!-- Cargado dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────
                 TAB 4: PERSONAL
            ───────────────────────────────────────────────── -->
            <div id="tab-content-personal" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px;">
                    <div>
                        <div class="content-title">Personal Elite</div>
                        <div class="content-subtitle">Gestión de staff y brigada de cocina.</div>
                    </div>
                    <button class="btn-gold" onclick="openModal('modalStaff')" id="btn-add-staff">
                        <i class="fa-solid fa-user-plus"></i> Añadir Staff
                    </button>
                </div>
                <div class="staff-grid" id="staffGrid"></div>
            </div>

            <!-- ─────────────────────────────────────────────────
                 TAB 5: INVENTARIO DE INSUMOS
            ───────────────────────────────────────────────── -->
            <div id="tab-content-inventario" style="display:none;">

                <!-- Cabecera -->
                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:24px; flex-wrap:wrap; gap:14px;">
                    <div>
                        <div style="font-size:10px; color:var(--gold-dark); text-transform:uppercase; letter-spacing:2px; margin-bottom:5px; font-weight:700;">Métricas de Almacén</div>
                        <div class="content-title">Inventario de Insumos</div>
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                        <button class="btn-black" onclick="openModal('modalMateriaPrima')" id="btn-add-insumo">
                            <i class="fa-solid fa-plus"></i> Añadir Insumo
                        </button>
                        <button class="btn-gold" onclick="openModal('modalReposicion')" id="btn-reposicion">
                            <i class="fa-solid fa-cart-shopping"></i> Pedido de Reposición
                        </button>
                    </div>
                </div>

                <!-- KPI cards (conectadas al servidor) -->
                <div class="dashboard-grid" style="margin-bottom:24px;">
                    <div class="dashboard-card">
                        <div class="card-label"><i class="fa-regular fa-gem"></i> Valor Total</div>
                        <div class="card-value" id="inv-valor-total">—</div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label" style="color:var(--danger)"><i class="fa-solid fa-triangle-exclamation"></i> Alertas Stock</div>
                        <div class="card-value" style="color:var(--danger)" id="inv-alertas">0</div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label"><i class="fa-solid fa-arrow-trend-up"></i> Rotación Mensual</div>
                        <div class="card-value" id="inv-rotacion">—</div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label"><i class="fa-solid fa-box-open"></i> Items Activos</div>
                        <div class="card-value" id="inv-items">—</div>
                    </div>
                </div>

                <!-- Toolbar: búsqueda + filtro + exportación -->
                <div class="inv-toolbar">
                    <div class="search-bar" style="width:260px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="invSearch" placeholder="Buscar insumo..." oninput="filterAndRenderInventory()">
                    </div>
                    <select id="invCategoryFilter" class="inv-select" onchange="filterAndRenderInventory()">
                        <option value="">Todas las categorías</option>
                        <option value="Carnes">Carnes</option>
                        <option value="Delicatessen">Delicatessen</option>
                        <option value="Bodega Exclusiva">Bodega Exclusiva</option>
                        <option value="Pescados & Mariscos">Pescados & Mariscos</option>
                        <option value="Verduras">Verduras</option>
                        <option value="Lácteos">Lácteos</option>
                    </select>
                    <select id="invStatusFilter" class="inv-select" onchange="filterAndRenderInventory()">
                        <option value="">Todos los estados</option>
                        <option value="ÓPTIMO">Óptimo</option>
                        <option value="CRÍTICO">Crítico</option>
                    </select>
                    <div style="margin-left:auto; display:flex; gap:8px;">
                        <button class="btn-outline" onclick="exportCSV()" id="btn-export-csv" title="Exportar como CSV">
                            <i class="fa-solid fa-file-csv"></i> CSV
                        </button>
                        <button class="btn-outline" onclick="exportJSON()" id="btn-export-json" title="Exportar como JSON">
                            <i class="fa-solid fa-file-code"></i> JSON
                        </button>
                    </div>
                </div>

                <!-- Tabla de inventario -->
                <div class="panel-box" style="padding:0; overflow:hidden;">
                    <table class="haute-table">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortInventory('nombre')" id="th-nombre">
                                    Ingrediente <i class="fa-solid fa-sort sort-icon"></i>
                                </th>
                                <th class="sortable" onclick="sortInventory('categoria')" id="th-categoria">
                                    Categoría <i class="fa-solid fa-sort sort-icon"></i>
                                </th>
                                <th class="sortable" onclick="sortInventory('stock')" id="th-stock">
                                    Stock Actual <i class="fa-solid fa-sort sort-icon"></i>
                                </th>
                                <th class="sortable" onclick="sortInventory('precio')" id="th-precio">
                                    Costo Unitario <i class="fa-solid fa-sort sort-icon"></i>
                                </th>
                                <th class="sortable" onclick="sortInventory('estado')" id="th-estado">
                                    Estado <i class="fa-solid fa-sort sort-icon"></i>
                                </th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody">
                            <tr>
                                <td colspan="6" class="table-empty">
                                    <i class="fa-solid fa-box-open"></i>
                                    Cargando inventario...
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div class="pagination-bar" id="invPaginationBar">
                        <span id="invPaginationInfo">0 resultados</span>
                        <div style="display:flex; align-items:center; gap:14px;">
                            <div style="display:flex; align-items:center; gap:6px; font-size:12px;">
                                <label for="invPageSize">Por página:</label>
                                <select id="invPageSize" class="inv-select" style="padding:4px 8px;" onchange="changePageSize()">
                                    <option value="5">5</option>
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <div class="pagination-controls" id="invPaginationControls"></div>
                        </div>
                    </div>
                </div>

                <!-- Nota de protocolo -->
                <div style="padding:16px 20px; background:var(--white); border:1px solid var(--border); border-radius:var(--radius-sm); margin-top:16px; display:flex; align-items:flex-start; gap:12px;">
                    <i class="fa-solid fa-circle-info" style="color:var(--gold-dark); margin-top:2px; flex-shrink:0;"></i>
                    <div style="font-size:12px; color:var(--gray-600);">
                        <strong style="display:block; margin-bottom:4px; color:var(--gray-800);">PROTOCOLO DE REPOSICIÓN</strong>
                        Los artículos marcados como <span style="color:var(--danger); font-weight:700;">CRÍTICO</span> requieren reposición inmediata.
                        Usa el botón "Pedido de Reposición" para notificar a proveedores con autorización del gerente.
                    </div>
                </div>

                <!-- ─── Historial de Reposiciones ─── -->
                <div style="margin-top:32px;">
                    <!-- Cabecera con filtro -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
                        <div>
                            <div style="font-size:10px; color:var(--gold-dark); text-transform:uppercase; letter-spacing:2px; margin-bottom:4px; font-weight:700;">
                                <i class="fa-solid fa-truck-ramp-box"></i> Órdenes a Proveedores
                            </div>
                            <div class="content-title" style="font-size:20px;">Historial de Reposiciones</div>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <select id="repEstadoFilter" class="inv-select" onchange="filterAndRenderReposiciones()" aria-label="Filtrar por estado">
                                <option value="">Todos los estados</option>
                                <option value="Pendiente">Pendiente</option>
                                <option value="Enviado">Enviado</option>
                                <option value="Recibido">Recibido</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                            <button class="btn-outline" onclick="fetchHistorialReposiciones()" id="btn-refresh-reposiciones" title="Actualizar historial" style="padding:8px 14px;">
                                <i class="fa-solid fa-rotate-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de reposiciones -->
                    <div class="panel-box" style="padding:0; overflow:hidden;">
                        <table class="haute-table" id="repTable">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Generado por</th>
                                    <th>Estado</th>
                                    <th style="text-align:center;"># Insumos</th>
                                    <th style="text-align:right;">Costo Total</th>
                                    <th style="width:190px; text-align:center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="repTableBody">
                                <tr>
                                    <td colspan="6" class="table-empty">
                                        <i class="fa-solid fa-truck"></i>
                                        Cargando historial de reposiciones...
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Paginación client-side -->
                        <div class="pagination-bar" id="repPaginationBar">
                            <span id="repPaginationInfo">0 resultados</span>
                            <div style="display:flex; align-items:center; gap:14px;">
                                <div style="display:flex; align-items:center; gap:6px; font-size:12px;">
                                    <label for="repPageSize">Por página:</label>
                                    <select id="repPageSize" class="inv-select" style="padding:4px 8px;" onchange="changeRepPageSize()">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                    </select>
                                </div>
                                <div class="pagination-controls" id="repPaginationControls"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            <!-- ─────────────────────────────────────────────────
                 TAB: PRODUCTOS DEL MENÚ
            ───────────────────────────────────────────────── -->
            <div id="tab-content-productos" style="display:none;">

                <!-- Cabecera -->
                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:24px; flex-wrap:wrap; gap:14px;">
                    <div>
                        <div style="font-size:10px; color:var(--gold-dark); text-transform:uppercase; letter-spacing:2px; margin-bottom:5px; font-weight:700;">Menú Público</div>
                        <div class="content-title">Gestión de Productos</div>
                        <div class="content-subtitle">Los cambios se reflejan automáticamente en el menú del cliente.</div>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <button class="btn-gold" onclick="openModalProducto()" id="btn-add-producto">
                            <i class="fa-solid fa-plus"></i> Nuevo Producto
                        </button>
                    </div>
                </div>

                <!-- KPI cards -->
                <div class="dashboard-grid" style="margin-bottom:24px;">
                    <div class="dashboard-card">
                        <div class="card-label"><i class="fa-solid fa-utensils"></i> Total Productos</div>
                        <div class="card-value" id="prod-kpi-total">—</div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label" style="color:var(--success,#1e8c45)"><i class="fa-solid fa-circle-check"></i> Disponibles</div>
                        <div class="card-value" style="color:var(--success,#1e8c45)" id="prod-kpi-disponibles">—</div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label" style="color:var(--danger)"><i class="fa-solid fa-circle-xmark"></i> No Disponibles</div>
                        <div class="card-value" style="color:var(--danger)" id="prod-kpi-nodisponibles">—</div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-label"><i class="fa-solid fa-tags"></i> Categorías</div>
                        <div class="card-value" id="prod-kpi-categorias">—</div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="inv-toolbar" style="margin-bottom:20px;">
                    <div class="search-bar" style="width:280px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="prodSearch" placeholder="Buscar producto..." oninput="filterProductos()">
                    </div>
                    <select id="prodCatFilter" class="inv-select" onchange="filterProductos()">
                        <option value="">Todas las categorías</option>
                    </select>
                    <select id="prodDispFilter" class="inv-select" onchange="filterProductos()">
                        <option value="">Todos</option>
                        <option value="1">Disponibles</option>
                        <option value="0">No Disponibles</option>
                    </select>
                </div>

                <!-- Grid de tarjetas de productos -->
                <div id="productosGrid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; margin-bottom:20px;">
                    <div style="grid-column:1/-1; text-align:center; padding:60px 0; color:var(--gray-400);">
                        <i class="fa-solid fa-utensils" style="font-size:40px; display:block; margin-bottom:12px; opacity:0.3;"></i>
                        Cargando productos...
                    </div>
                </div>

                <!-- Info nota -->
                <div style="padding:14px 18px; background:var(--white); border:1px solid var(--border); border-radius:var(--radius-sm); display:flex; align-items:flex-start; gap:12px;">
                    <i class="fa-solid fa-circle-info" style="color:var(--gold-dark); margin-top:2px; flex-shrink:0;"></i>
                    <div style="font-size:12px; color:var(--gray-600);">
                        <strong style="display:block; margin-bottom:4px; color:var(--gray-800);">VISIBILIDAD EN MENÚ</strong>
                        Solo los productos marcados como <strong>Disponible</strong> y con <strong>stock > 0</strong> aparecen en el menú público del cliente.
                        Al desactivar un producto o ponerlo en stock 0, desaparece del menú inmediatamente.
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────
                 TAB 6: HISTORIAL DE PEDIDOS
            ───────────────────────────────────────────────── -->
            <div id="tab-content-pedidos" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:14px;">
                    <div>
                        <div class="content-title">Historial de Pedidos</div>
                        <div class="content-subtitle">Registro completo de operaciones y ventas.</div>
                    </div>
                    <div class="search-bar" style="background:var(--white); border:1px solid var(--border);">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="pedidosSearch" placeholder="Buscar por ID o Mesa..." oninput="fetchHistorial()">
                    </div>
                </div>

                <div class="panel-box" style="padding:0; overflow:hidden;">
                    <table class="haute-table">
                        <thead>
                            <tr>
                                <th>ID Pedido</th>
                                <th>Fecha</th>
                                <th>Cliente / Mesa</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="historialTableBody">
                            <tr>
                                <td colspan="6" class="table-empty">
                                    <i class="fa-solid fa-receipt"></i>
                                    Cargando historial...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /admin-content -->
    </main>
</div><!-- /admin-app -->


<!-- ═══════════════════════════════════════════════════════════════════════
     MODALES
═══════════════════════════════════════════════════════════════════════ -->

<!-- ── Modal: Editar Reserva ── -->
<div class="mrgiova-modal" id="modalEditarReserva" role="dialog" aria-modal="true" aria-labelledby="modalEditarReservaTitulo">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 id="modalEditarReservaTitulo"><i class="fa-solid fa-pen-to-square" style="color:var(--gold-dark); margin-right:8px;"></i> Editar Reserva</h3>
            <button class="modal-close-btn" onclick="closeModal('modalEditarReserva')" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formEditarReserva" onsubmit="submitEditarReserva(event)" novalidate>
                <input type="hidden" id="editReservaId">

                <div class="form-group">
                    <label for="editReservaNombre">Nombre del Cliente *</label>
                    <input type="text" id="editReservaNombre" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="editReservaFecha">Fecha *</label>
                        <input type="date" id="editReservaFecha" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editReservaHora">Hora *</label>
                        <input type="time" id="editReservaHora" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="editReservaPersonas">Comensales *</label>
                        <input type="number" id="editReservaPersonas" class="form-control" min="1" max="50" required>
                    </div>
                    <div class="form-group">
                        <label for="editReservaMesa">Mesa *</label>
                        <select id="editReservaMesa" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="editReservaEstado">Estado</label>
                    <select id="editReservaEstado" class="form-control" required>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Confirmada">Confirmada</option>
                        <option value="Cancelada">Cancelada</option>
                        <option value="Completada">Completada</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editReservaNotas">Notas adicionales</label>
                    <textarea id="editReservaNotas" class="form-control" rows="2" placeholder="VIP, alergias, preferencias..."></textarea>
                </div>

                <div style="background:#FFF3CD; border:1px solid #FFECB5; border-radius:6px; padding:10px 12px; font-size:11px; color:#856404; margin-bottom:14px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Al guardar, el cliente recibirá una notificación automática del cambio.
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-outline" style="flex:1;" onclick="closeModal('modalEditarReserva')">Cancelar</button>
                    <button type="submit" class="btn-gold" style="flex:2;" id="btnSubmitEditarReserva">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Confirmar Eliminación de Reserva ── -->
<div class="mrgiova-modal" id="modalEliminarReserva" role="dialog" aria-modal="true" aria-labelledby="modalEliminarReservaTitulo">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header" style="border-bottom:1px solid var(--danger-light, #FFCDD2);">
            <h3 id="modalEliminarReservaTitulo" style="color:var(--danger);"><i class="fa-solid fa-trash-can" style="margin-right:8px;"></i> Cancelar Reserva</h3>
            <button class="modal-close-btn" onclick="closeModal('modalEliminarReserva')" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="eliminarReservaId">
            <div style="text-align:center; padding:10px 0 20px;">
                <i class="fa-solid fa-calendar-xmark" style="font-size:40px; color:var(--danger); opacity:0.7; margin-bottom:14px; display:block;"></i>
                <p style="font-size:14px; color:var(--gray-800); margin-bottom:8px;">
                    ¿Estás seguro de que deseas eliminar la reserva de<br>
                    <strong id="eliminarReservaNombre" style="color:var(--black);"></strong>?
                </p>
                <p style="font-size:12px; color:var(--gray-400);">
                    El cliente recibirá una notificación automática de cancelación.
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn-outline" style="flex:1;" onclick="closeModal('modalEliminarReserva')">Volver</button>
                <button class="btn-danger" style="flex:2;" id="btnConfirmarEliminar" onclick="confirmarEliminarReserva()">
                    <i class="fa-solid fa-trash"></i> Sí, Cancelar Reserva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Nueva Reserva ── -->
<div class="mrgiova-modal" id="modalReserva" role="dialog" aria-modal="true" aria-labelledby="modalReservaTitulo">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h3 id="modalReservaTitulo"><i class="fa-regular fa-calendar-plus" style="color:var(--gold-dark); margin-right:8px;"></i> Nueva Reserva</h3>
            <button class="modal-close-btn" onclick="closeModal('modalReserva')" aria-label="Cerrar modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formReserva" onsubmit="submitReserva(event)" novalidate>

                <div class="form-group">
                    <label for="reservaNombre">Nombre del Cliente *</label>
                    <input type="text" id="reservaNombre" class="form-control" placeholder="Ej: García, Carlos" required>
                    <div class="form-error-text" id="err-reservaNombre">El nombre es obligatorio.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="reservaFecha">Fecha *</label>
                        <input type="date" id="reservaFecha" class="form-control" required>
                        <div class="form-error-text" id="err-reservaFecha">Selecciona una fecha válida.</div>
                    </div>
                    <div class="form-group">
                        <label for="reservaHora">Hora *</label>
                        <input type="time" id="reservaHora" class="form-control" required>
                        <div class="form-error-text" id="err-reservaHora">Selecciona la hora.</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="reservaPersonas">Comensales *</label>
                        <input type="number" id="reservaPersonas" class="form-control" min="1" max="50" placeholder="Ej: 4" required>
                        <div class="form-error-text" id="err-reservaPersonas">Indica el número de comensales.</div>
                    </div>
                    <div class="form-group">
                        <label for="reservaMesa">Mesa Asignada *</label>
                        <select id="reservaMesa" class="form-control" required>
                            <option value="">— Seleccionar mesa —</option>
                        </select>
                        <div class="form-error-text" id="err-reservaMesa">Debes asignar una mesa.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reservaNotas">Notas (VIP, alergias, preferencias)</label>
                    <textarea id="reservaNotas" class="form-control" rows="2" placeholder="Ej: VIP — Alergia a nueces"></textarea>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="button" class="btn-outline" style="flex:1;" onclick="closeModal('modalReserva')">Cancelar</button>
                    <button type="submit" class="btn-gold" style="flex:2;" id="btnSubmitReserva">
                        <i class="fa-solid fa-check"></i> Confirmar Reserva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Añadir Materia Prima (INVENTARIO) ── -->
<div class="mrgiova-modal" id="modalMateriaPrima" role="dialog" aria-modal="true" aria-labelledby="modalMateriaPrimaTitulo">
    <div class="modal-content" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="modalMateriaPrimaTitulo"><i class="fa-solid fa-boxes-stacked" style="color:var(--gold-dark); margin-right:8px;"></i> Añadir Insumo</h3>
            <button class="modal-close-btn" onclick="closeModal('modalMateriaPrima')" aria-label="Cerrar modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formMateriaPrima" onsubmit="submitMateriaPrima(event)" novalidate>

                <div class="form-group">
                    <label for="mpNombre">Nombre del Insumo *</label>
                    <input type="text" id="mpNombre" class="form-control" placeholder="Ej: Wagyu A5 Japonés" required>
                    <div class="form-error-text" id="err-mpNombre">El nombre es obligatorio.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mpCategoria">Categoría *</label>
                        <select id="mpCategoria" class="form-control" required onchange="checkCategoriaMP()">
                            <option value="">— Seleccionar —</option>
                            <option value="Carnes">Carnes</option>
                            <option value="Delicatessen">Delicatessen</option>
                            <option value="Bodega Exclusiva">Bodega Exclusiva</option>
                            <option value="Pescados & Mariscos">Pescados & Mariscos</option>
                            <option value="Verduras">Verduras</option>
                            <option value="Lácteos">Lácteos</option>
                        </select>
                        <div class="form-error-text" id="err-mpCategoria">Selecciona una categoría.</div>
                    </div>
                    <div class="form-group">
                        <label for="mpUnidad">Unidad de Medida *</label>
                        <select id="mpUnidad" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            <option value="kg">Kilogramos (kg)</option>
                            <option value="g">Gramos (g)</option>
                            <option value="litros">Litros</option>
                            <option value="ml">Mililitros (ml)</option>
                            <option value="unidades">Unidades</option>
                            <option value="botellas">Botellas</option>
                            <option value="cajas">Cajas</option>
                        </select>
                        <div class="form-error-text" id="err-mpUnidad">Selecciona la unidad de medida.</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mpCantidad">Stock Actual *</label>
                        <input type="number" id="mpCantidad" class="form-control" min="0" step="0.01" placeholder="0.00" required>
                        <div class="form-error-text" id="err-mpCantidad">Ingresa la cantidad actual.</div>
                    </div>
                    <div class="form-group">
                        <label for="mpStockMinimo">Stock Mínimo *</label>
                        <input type="number" id="mpStockMinimo" class="form-control" min="0" step="0.01" placeholder="0.00" required>
                        <div class="form-error-text" id="err-mpStockMinimo">Ingresa el stock mínimo.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mpCosto">Costo Unitario (COP) *</label>
                    <input type="number" id="mpCosto" class="form-control" min="0" step="0.01" placeholder="0.00" required oninput="calcularPorcion()">
                    <div class="form-error-text" id="err-mpCosto">Ingresa el costo unitario.</div>
                </div>

                <!-- Calculadora de porción (solo para Carnes) -->
                <div id="mpCalculadoraPorcion" class="calc-box" style="display:none;">
                    <strong style="font-size:12px; display:block; margin-bottom:10px; color:var(--gold-dark);">
                        <i class="fa-solid fa-calculator"></i> Calculadora de Porción
                    </strong>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="number" id="mpPesoPorcion" class="form-control" placeholder="Gramos por porción" min="1" oninput="calcularPorcion()" style="flex:1;">
                        <span style="font-size:12px; color:var(--gray-400); white-space:nowrap;">gr / porción</span>
                    </div>
                    <div class="calc-result" id="mpCostoPorcionRes">Ingresa el costo y el peso por porción.</div>
                </div>

                <div style="display:flex; gap:10px; margin-top:16px;">
                    <button type="button" class="btn-outline" style="flex:1;" onclick="closeModal('modalMateriaPrima')">Cancelar</button>
                    <button type="submit" class="btn-gold" style="flex:2;" id="btnSubmitMateriaPrima">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Insumo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Añadir Staff ── -->
<div class="mrgiova-modal" id="modalStaff" role="dialog" aria-modal="true" aria-labelledby="modalStaffTitulo">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3 id="modalStaffTitulo"><i class="fa-solid fa-user-plus" style="color:var(--gold-dark); margin-right:8px;"></i> Añadir Staff Elite</h3>
            <button class="modal-close-btn" onclick="closeModal('modalStaff')" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formStaff" onsubmit="submitStaff(event)">
                <div class="form-group">
                    <label for="staffNombre">Nombres Completos *</label>
                    <input type="text" id="staffNombre" class="form-control" required placeholder="Ej: Juan Pérez Rodríguez">
                </div>
                <div class="form-group">
                    <label for="staffCargo">Cargo *</label>
                    <select id="staffCargo" class="form-control" required>
                        <option value="">— Seleccionar —</option>
                        <option>Chef Ejecutivo</option>
                        <option>Sous Chef</option>
                        <option>Sommelier</option>
                        <option>Maître D'</option>
                        <option>Mesero</option>
                        <option>Cajero</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="staffRol">Rol del sistema *</label>
                    <select id="staffRol" class="form-control" required>
                        <option value="">— Seleccionar —</option>
                        <option value="Cocinero">Cocinero</option>
                        <option value="Cajero">Cajero</option>
                        <option value="Administrador">Administrador</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="button" class="btn-outline" style="flex:1;" onclick="closeModal('modalStaff')">Cancelar</button>
                    <button type="submit" class="btn-gold" style="flex:2;">
                        <i class="fa-solid fa-check"></i> Registrar Empleado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Pedido de Reposición ── -->
<div class="mrgiova-modal" id="modalReposicion" role="dialog" aria-modal="true" aria-labelledby="modalReposicionTitulo">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3 id="modalReposicionTitulo"><i class="fa-solid fa-truck" style="color:var(--gold-dark); margin-right:8px;"></i> Pedido a Proveedores</h3>
            <button class="modal-close-btn" onclick="closeModal('modalReposicion')" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="font-size:13px; margin-bottom:20px; color:var(--gray-600);">
                Se generará un pedido automático para todos los ítems marcados como
                <span style="color:var(--danger); font-weight:700;">CRÍTICO</span>.
            </p>
            <form id="formReposicion" onsubmit="submitReposicion(event)">
                <div class="form-group" style="margin-bottom:24px;">
                    <label for="repPin">Firma de Autorización (PIN)</label>
                    <input type="password" id="repPin" class="form-control" required
                           placeholder="••••" style="letter-spacing:6px; text-align:center; font-size:18px;">
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-outline" style="flex:1;" onclick="closeModal('modalReposicion')">Cancelar</button>
                    <button type="submit" class="btn-black" style="flex:2;">
                        <i class="fa-solid fa-paper-plane"></i> Confirmar Reposición
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Gestionar Comanda ── -->
<div class="mrgiova-modal" id="modalComanda" role="dialog" aria-modal="true" aria-labelledby="modalComandaTitulo">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header">
            <h3 id="modalComandaTitulo">Gestionar Comanda</h3>
            <button class="modal-close-btn" onclick="closeModal('modalComanda')" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formComanda" onsubmit="submitComanda(event)">
                <p style="font-size:13px; margin-bottom:18px; color:var(--gray-600);">Acción requerida para la mesa seleccionada.</p>
                <div class="form-group" style="margin-bottom:24px;">
                    <label for="comandaAccion">Acción</label>
                    <select id="comandaAccion" class="form-control" required>
                        <option>Abrir Mesa</option>
                        <option>Añadir a Pedido Existente</option>
                        <option>Cerrar Mesa (Pedir Cuenta)</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-outline" style="flex:1;" onclick="closeModal('modalComanda')">Cancelar</button>
                    <button type="submit" class="btn-black" style="flex:2;">
                        <i class="fa-solid fa-bolt"></i> Ejecutar Acción
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Ver Ticket ── -->
<div class="mrgiova-modal" id="modalTicket" role="dialog" aria-modal="true" aria-labelledby="modalTicketTitulo">
    <div class="modal-content" style="max-width:440px; background:#fffdf9;">
        <div class="modal-header" style="border-bottom:1px dashed var(--gold);">
            <h3 id="modalTicketTitulo" style="color:var(--gold-dark);">
                Ticket de Venta <span id="ticketNum"></span>
            </h3>
            <button class="modal-close-btn" onclick="closeModal('modalTicket')" aria-label="Cerrar" style="color:var(--gold-dark);">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body" style="font-family:var(--font-mono); font-size:13px;">
            <div style="text-align:center; margin-bottom:20px;">
                <strong style="font-family:var(--font-serif); font-size:22px; display:block;">Sabor a Pueblo</strong>
                <span style="font-size:12px; color:var(--gray-600);">Restaurante Elite</span><br>
                <span id="ticketDate" style="font-size:11px; color:var(--gray-400);"></span>
            </div>
            <div style="border-bottom:1px dashed var(--border); padding-bottom:12px; margin-bottom:14px;">
                <div><strong>Cliente:</strong> <span id="ticketClient"></span></div>
                <div><strong>Mesa:</strong> <span id="ticketMesa"></span></div>
            </div>
            <div id="ticketItems" style="margin-bottom:18px;"></div>
            <div style="border-top:1px dashed var(--border); padding-top:14px; text-align:right; font-size:16px;">
                <strong>Total: <span id="ticketTotal" style="color:var(--gold-dark);"></span></strong>
            </div>
            <div style="margin-top:24px;">
                <button class="btn-gold" style="width:100%;" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Imprimir Ticket
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════════════════════════ -->
<script src="{{ asset('js/theme-toggle.js') }}"></script>
<script src="{{ asset('js/pages/admin.js') }}"></script>

<!-- ── Modal: Crear / Editar Producto ── -->
<div class="mrgiova-modal" id="modalProducto" role="dialog" aria-modal="true" aria-labelledby="modalProductoTitulo">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3 id="modalProductoTitulo"><i class="fa-solid fa-utensils" style="color:var(--gold-dark); margin-right:8px;"></i> <span id="modalProductoTituloText">Nuevo Producto</span></h3>
            <button class="modal-close-btn" onclick="closeModal('modalProducto')" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="formProducto" onsubmit="submitProducto(event)" novalidate>
                <input type="hidden" id="prodId">

                <div class="form-row">
                    <div class="form-group">
                        <label for="prodCategoria">Categoría *</label>
                        <select id="prodCategoria" class="form-control" onchange="_onCategoriaModalChange()" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                        <div class="form-error-text" id="err-prodCategoria">Selecciona una categoría.</div>
                        <div id="prodCatHint" style="font-size:11px; color:var(--gold-dark); margin-top:4px; font-weight:600;"></div>
                    </div>
                    <div class="form-group">
                        <label for="prodNombre" id="lblProdNombre">Nombre del Plato *</label>
                        <input type="text" id="prodNombre" class="form-control" placeholder="Ej: Hamburguesa Clásica Sabor a Pueblo" required>
                        <div class="form-error-text" id="err-prodNombre">El nombre es obligatorio.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="prodDescripcion">Descripción</label>
                    <textarea id="prodDescripcion" class="form-control" rows="2" placeholder="Descripción del plato, ingredientes principales..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="prodPrecio">Precio (COP) *</label>
                        <input type="number" id="prodPrecio" class="form-control" min="0" step="0.01" placeholder="0.00" required>
                        <div class="form-error-text" id="err-prodPrecio">Ingresa un precio válido.</div>
                    </div>
                    <div class="form-group">
                        <label for="prodStock">Stock *</label>
                        <input type="number" id="prodStock" class="form-control" min="0" placeholder="0" required>
                        <div class="form-error-text" id="err-prodStock">Ingresa el stock disponible.</div>
                    </div>
                    <div class="form-group">
                        <label for="prodTiempo">Tiempo Prep. (min)</label>
                        <input type="number" id="prodTiempo" class="form-control" min="0" placeholder="15">
                    </div>
                </div>

                <div class="form-group">
                    <label for="prodImagenUrl">URL de Imagen</label>
                    <input type="url" id="prodImagenUrl" class="form-control" placeholder="https://ejemplo.com/imagen.jpg">
                </div>

                <div class="form-group">
                    <label for="prodIngredientes" id="lblProdIngredientes">Ingredientes / Alérgenos</label>
                    <input type="text" id="prodIngredientes" class="form-control" placeholder="Ej: Carne de res, Pan brioche, Queso cheddar...">
                </div>

                <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                    <label style="margin:0; display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px;">
                        <input type="checkbox" id="prodDisponible" style="width:16px; height:16px; accent-color:var(--gold-dark);" checked>
                        Disponible en menú público
                    </label>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="button" class="btn-outline" style="flex:1;" onclick="closeModal('modalProducto')">Cancelar</button>
                    <button type="submit" class="btn-gold" style="flex:2;" id="btnSubmitProducto">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Confirmar Eliminación de Producto ── -->
<div class="mrgiova-modal" id="modalEliminarProducto" role="dialog" aria-modal="true" aria-labelledby="modalEliminarProductoTitulo">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header" style="border-bottom:1px solid #FFCDD2;">
            <h3 id="modalEliminarProductoTitulo" style="color:var(--danger);"><i class="fa-solid fa-trash-can" style="margin-right:8px;"></i> Eliminar Producto</h3>
            <button class="modal-close-btn" onclick="closeModal('modalEliminarProducto')" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="eliminarProductoId">
            <div style="text-align:center; padding:10px 0 20px;">
                <i class="fa-solid fa-circle-xmark" style="font-size:40px; color:var(--danger); opacity:0.7; margin-bottom:14px; display:block;"></i>
                <p style="font-size:14px; color:var(--gray-800); margin-bottom:8px;">
                    ¿Eliminar el producto <strong id="eliminarProductoNombre" style="color:var(--black);"></strong>?<br>
                </p>
                <p style="font-size:12px; color:var(--gray-400);">
                    Si tiene pedidos asociados, se marcará como no disponible (no se borrará).<br>
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn-outline" style="flex:1;" onclick="closeModal('modalEliminarProducto')">Volver</button>
                <button class="btn-danger" style="flex:2;" onclick="confirmarEliminarProducto()">
                    <i class="fa-solid fa-trash"></i> Sí, Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Detalle de Pedido de Reposición ── -->
<div class="mrgiova-modal" id="modalDetallePedido" role="dialog" aria-modal="true" aria-labelledby="modalDetallePedidoTitulo">
    <div class="modal-content" style="max-width:640px;">
        <div class="modal-header">
            <h3 id="modalDetallePedidoTitulo">
                <i class="fa-solid fa-truck-ramp-box" style="color:var(--gold-dark); margin-right:8px;"></i>
                Detalle del Pedido de Reposición
            </h3>
            <button class="modal-close-btn" onclick="closeModal('modalDetallePedido')" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <!-- Metadata del pedido -->
            <div style="display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap;">
                <div style="flex:1; min-width:160px;">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:1px; color:var(--gold-dark); font-weight:700; margin-bottom:4px;">Pedido</div>
                    <div id="mdp-id" style="font-size:16px; font-weight:700; color:var(--black);"></div>
                </div>
                <div style="flex:1; min-width:160px;">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:1px; color:var(--gold-dark); font-weight:700; margin-bottom:4px;">Fecha</div>
                    <div id="mdp-fecha" style="font-size:13px; color:var(--gray-600);"></div>
                </div>
                <div style="flex:1; min-width:160px;">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:1px; color:var(--gold-dark); font-weight:700; margin-bottom:4px;">Generado por</div>
                    <div id="mdp-empleado" style="font-size:13px; color:var(--gray-600);"></div>
                </div>
                <div style="flex:1; min-width:160px;">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:1px; color:var(--gold-dark); font-weight:700; margin-bottom:4px;">Estado</div>
                    <div id="mdp-estado"></div>
                </div>
            </div>
            <div id="mdp-fecha-recibido-row" style="display:none; margin-bottom:16px; padding:10px 14px; background:var(--gold-bg,#fdf8ef); border-radius:6px; border:1px solid var(--gold);">
                <i class="fa-solid fa-calendar-check" style="color:var(--gold-dark); margin-right:6px;"></i>
                <strong style="font-size:12px;">Recibido el:</strong> <span id="mdp-fecha-recibido" style="font-size:12px;"></span>
            </div>

            <!-- Tabla de insumos del pedido -->
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:var(--gray-400); font-weight:700; margin-bottom:10px;">Insumos Pedidos</div>
            <div class="panel-box" style="padding:0; overflow:hidden; margin-bottom:20px;">
                <table class="haute-table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Categoría</th>
                            <th style="text-align:right;">Cantidad Pedida</th>
                            <th style="text-align:right;">Costo Unit. (momento)</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="mdp-detalles"></tbody>
                    <tfoot>
                        <tr style="background:var(--gold-bg,#fdf8ef);">
                            <td colspan="4" style="text-align:right; font-weight:700; padding:10px 16px; font-size:12px;">TOTAL ESTIMADO</td>
                            <td style="text-align:right; font-weight:700; padding:10px 16px; color:var(--gold-dark);" id="mdp-total"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button class="btn-outline" onclick="closeModal('modalDetallePedido')">Cerrar</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>