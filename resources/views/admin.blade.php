<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo — Panel Administrativo</title>
    <meta name="description" content="Panel de administración de Sabor a Pueblo: ventas, cocina, reservas, inventario, mesas y personal.">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <div style="display:flex; align-items:center; gap:12px; border-left:1px solid var(--border); padding-left:20px;">
                    <div style="text-align:right;">
                        <strong style="display:block; font-size:13px; color:var(--black);"><i class="fa-solid fa-user-shield" style="color:var(--gold);margin-right:4px;"></i> Administrador</strong>
                        <span style="font-size:11px; color:var(--gray-400);">admin@saborapueblo.com</span>
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
<script>
'use strict';

// ─────────────────────────────────────────────────────────────────────────────
// HELPERS GLOBALES
// ─────────────────────────────────────────────────────────────────────────────

/** Formatea un valor como moneda COP */
function formatCOP(val) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(val);
}

/** Obtiene el token CSRF del meta tag */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

/** Headers estándar para peticiones JSON */
function jsonHeaders() {
    return { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() };
}

// ─────────────────────────────────────────────────────────────────────────────
// SISTEMA DE TOASTS (reemplaza alert() por completo)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Muestra una notificación toast.
 * @param {string} title - Título corto
 * @param {string} message - Mensaje detallado
 * @param {'success'|'error'|'warning'|'info'} type - Tipo de notificación
 */
function showToast(title, message = '', type = 'success') {
    const icons = {
        success: 'fa-circle-check',
        error:   'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info:    'fa-circle-info',
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="toast-icon"><i class="fa-solid ${icons[type] || icons.info}"></i></div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            ${message ? `<div class="toast-message">${message}</div>` : ''}
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()" aria-label="Cerrar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

    document.getElementById('toastContainer').appendChild(toast);

    // Animar entrada
    requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add('show'));
    });

    // Auto-eliminar tras 4 segundos
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4500);
}

// ─────────────────────────────────────────────────────────────────────────────
// NAVEGACIÓN POR TABS
// ─────────────────────────────────────────────────────────────────────────────

let currentTab = 'dashboard';

const tabConfig = {
    dashboard:  { title: 'Panel Administrativo', sub: 'Control general de ventas, inventario y estado del local.', fn: fetchStats },
    reservas:   { title: 'Agenda de Reservas',   sub: 'Gestión de comensales y eventos especiales.',              fn: fetchReservas },
    mesas:      { title: 'Plano de Mesas',        sub: 'Asignación y estado de mesas en tiempo real.',            fn: fetchMesas },
    personal:   { title: 'Gestión de Personal',   sub: 'Administración del equipo Elite.',                        fn: fetchStaff },
    inventario: { title: 'Control de Insumos',    sub: 'Inventario de ingredientes y materias primas.',           fn: fetchInsumos },
    pedidos:    { title: 'Historial de Pedidos',  sub: 'Registro de todas las operaciones.',                      fn: fetchHistorial },
};

function switchTab(tabName) {
    currentTab = tabName;

    // Actualizar nav
    document.querySelectorAll('.admin-nav-item').forEach(el => el.classList.remove('active'));
    document.getElementById(`menu-${tabName}`).classList.add('active');

    // Mostrar/ocultar contenido
    Object.keys(tabConfig).forEach(t => {
        document.getElementById(`tab-content-${t}`).style.display = 'none';
    });
    document.getElementById(`tab-content-${tabName}`).style.display = 'block';

    // Actualizar header
    const cfg = tabConfig[tabName];
    document.getElementById('pageTitleText').textContent = cfg.title;
    document.getElementById('pageSubtitle').textContent  = cfg.sub;

    // Ejecutar función de carga
    if (cfg.fn) cfg.fn();
}

// ─────────────────────────────────────────────────────────────────────────────
// MODALES
// ─────────────────────────────────────────────────────────────────────────────

function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}

// Cerrar modal con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.mrgiova-modal.open').forEach(m => m.classList.remove('open'));
        document.body.style.overflow = '';
    }
});

// Cerrar al hacer click en el fondo oscuro
document.querySelectorAll('.mrgiova-modal').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// INICIALIZACIÓN
// ─────────────────────────────────────────────────────────────────────────────

window.addEventListener('DOMContentLoaded', () => {
    // Cargar dashboard al inicio
    fetchStats();
    
    // Conexión simulada en tiempo real (Event-driven poll cada 7 segundos para platos premium y KPIs)
    setInterval(() => {
        fetchStats();
    }, 7000);

    // Precargar mesas para el selector de reservas
    fetch('/api/admin/mesas')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('reservaMesa');
            data.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = `Mesa ${m.numero_mesa} (${m.capacidad} pax) — ${m.estado}`;
                if (m.estado === 'Ocupada') opt.disabled = true;
                select.appendChild(opt);
            });
        })
        .catch(() => {}); // silencioso
});

// ─────────────────────────────────────────────────────────────────────────────
// 1. DASHBOARD — Estadísticas con sincronización de fecha del dispositivo
// ─────────────────────────────────────────────────────────────────────────────

// Almacenar meta de semana para exportadores
let _metaSemana = null;
let _ventasPorDia = [];

function fetchStats() {
    // Enviar la fecha LOCAL del dispositivo del administrador para sincronización
    const fechaHoyLocal = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD en zona local
    const url = `/api/admin/stats?current_date=${fechaHoyLocal}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            document.getElementById('kpi-ventas').textContent   = formatCOP(data.kpis.ventas_hoy);
            document.getElementById('kpi-ticket').textContent   = formatCOP(data.kpis.ticket_promedio);

            const varPct = data.kpis.variacion_ventas;
            const subtextEl = document.getElementById('kpi-ventas-subtext');
            if (subtextEl) {
                if (varPct >= 0) {
                    subtextEl.innerHTML = `<span style="color:var(--success); font-weight:700;">+${varPct.toFixed(1)}%</span> vs ayer <i class="fa-solid fa-arrow-trend-up" style="color:var(--success)"></i>`;
                } else {
                    subtextEl.innerHTML = `<span style="color:var(--danger); font-weight:700;">${varPct.toFixed(1)}%</span> vs ayer <i class="fa-solid fa-arrow-trend-down" style="color:var(--danger)"></i>`;
                }
            }

            const pOcup = data.kpis.total_mesas > 0
                ? Math.round((data.kpis.mesas_activas / data.kpis.total_mesas) * 100)
                : 0;
            document.getElementById('kpi-ocupacion').textContent  = pOcup + '%';
            document.getElementById('kpi-mesas-text').textContent = `${data.kpis.mesas_activas}/${data.kpis.total_mesas} Mesas`;
            document.getElementById('kpi-alertas').textContent    = data.kpis.alertas_stock.toString().padStart(2, '0');

            // Guardar metadatos para exportadores
            _metaSemana  = data.meta_semana;
            _ventasPorDia = data.ventas_por_dia;

            renderSalesChart(data.ventas_por_dia);

            // Platos Premium
            const ul = document.getElementById('premiumList');
            ul.innerHTML = '';
            if (!data.productos_premium || data.productos_premium.length === 0) {
                ul.innerHTML = '<li><span style="color:var(--gray-400); font-style:italic;">Sin datos de platos premium.</span></li>';
                return;
            }
            data.productos_premium.forEach(p => {
                const li = document.createElement('li');
                li.innerHTML = `<span>${p.nombre}</span> <strong class="text-gold">${p.cantidad} ord.</strong>`;
                ul.appendChild(li);
            });
        })
        .catch(() => showToast('Error', 'No se pudieron cargar las estadísticas.', 'error'));
}

let chartInstance = null;

function renderSalesChart(data) {
    const labels  = data.map(d => d.dia);
    const values  = data.map(d => d.ventas);
    const bgColors = data.map(d =>
        d.es_hoy    ? 'rgba(179, 142, 93, 0.5)'  :
        d.es_futuro ? 'rgba(200,200,200,0.15)'    :
                      'rgba(179, 142, 93, 0.12)'
    );
    const borderColors = data.map(d =>
        d.es_hoy    ? 'rgba(179, 142, 93, 1.0)'   :
        d.es_futuro ? 'rgba(200,200,200,0.4)'      :
                      'rgba(179, 142, 93, 0.7)'
    );
    const ctx = document.getElementById('salesChart').getContext('2d');

    if (chartInstance) {
        chartInstance.data.labels                 = labels;
        chartInstance.data.datasets[0].data        = values;
        chartInstance.data.datasets[0].backgroundColor  = bgColors;
        chartInstance.data.datasets[0].borderColor      = borderColors;
        chartInstance.update();
        return;
    }

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Ventas',
                data: values,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 2,
                borderRadius: 6,
                barPercentage: 0.6,
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
                    ticks: { callback: v => '$' + (v / 1000) + 'k', font: { family: 'Outfit' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Outfit', weight: '500' } }
                }
            }
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. RESERVAS
// ─────────────────────────────────────────────────────────────────────────────

function fetchReservas() {
    const fechaFilter = document.getElementById('filtroFechaReservas');
    let dateVal = fechaFilter.value;

    // Si no hay fecha, usar HOY del dispositivo del administrador
    if (!dateVal) {
        dateVal = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD usando zona local
        fechaFilter.value = dateVal;
    }

    const url = `/api/admin/reservas?fecha=${dateVal}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('reservasList');

            const header = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                    <div>
                        <div style="font-family:var(--font-serif); font-size:20px; color:var(--black);">
                            Servicio del <span style="color:var(--gold-dark);">${dateVal}</span>
                        </div>
                        <div style="font-size:12px; color:var(--gray-400); margin-top:4px;">${data.length} reserva(s)</div>
                    </div>
                    <button class="btn-gold" onclick="openModal('modalReserva')" id="btn-nueva-reserva-2">
                        <i class="fa-solid fa-plus"></i> Nueva Reserva
                    </button>
                </div>
            `;

            container.innerHTML = header;

            if (data.length === 0) {
                container.innerHTML += `
                    <div class="table-empty" style="background:var(--white); border:1px solid var(--border); border-radius:var(--radius-md); padding:50px;">
                        <i class="fa-regular fa-calendar-xmark" style="font-size:40px; opacity:0.3; display:block; margin-bottom:12px;"></i>
                        No hay reservas para esta fecha.
                    </div>`;
                return;
            }

            data.forEach(r => {
                const vipBadge  = r.is_vip ? `<span class="vip-badge">VIP Elite</span>` : '';
                const noteHtml  = r.notas  ? `<div class="res-note">"${r.notas}"</div>` : '';
                const mesaNum   = r.mesa_numero ? r.mesa_numero.toString().padStart(2, '0') : '--';

                let stBadge;
                if (r.estado === 'Confirmada')  stBadge = `<span class="badge badge-optimo">✓ Confirmada</span>`;
                else if (r.estado === 'Pendiente') stBadge = `<span class="badge badge-pendiente">Pendiente</span>`;
                else if (r.estado === 'Cancelada') stBadge = `<span class="badge badge-critico">Cancelada</span>`;
                else stBadge = `<span class="badge badge-neutral">${r.estado}</span>`;

                // Extraer nombre del cliente desde las notas
                let clienteDisplay = r.cliente_nombre;
                const matchNota = (r.notas || '').match(/Cliente:\s*([^—\n]+)/i);
                if (matchNota) clienteDisplay = matchNota[1].trim();

                container.innerHTML += `
                    <div class="reservation-card">
                        <div class="res-time">
                            ${r.hora}
                            <small>Mesa ${mesaNum}</small>
                        </div>
                        <div class="res-details" style="flex:1;">
                            <div class="res-name">${clienteDisplay} ${vipBadge}</div>
                            <div class="res-meta">
                                <span><i class="fa-solid fa-user-group"></i> ${r.num_personas} Comensales</span>
                                ${stBadge}
                            </div>
                            ${noteHtml}
                        </div>
                        <div style="display:flex; flex-direction:column; gap:6px; margin-left:12px;">
                            <button class="btn-outline" style="padding:5px 10px; font-size:11px;" onclick="abrirEditarReserva(${JSON.stringify(r).replace(/"/g,'&quot;')})" title="Editar reserva">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn-danger" style="padding:5px 10px; font-size:11px;" onclick="abrirEliminarReserva(${r.id}, '${clienteDisplay.replace(/'/g,"\\'")}'  , '${r.estado}')" title="Eliminar reserva">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
        })
        .catch(() => showToast('Error', 'No se pudieron cargar las reservas.', 'error'));
}

/** Envía el formulario de nueva reserva */
function submitReserva(e) {
    e.preventDefault();

    // Validación frontend
    let valid = true;
    const campos = ['reservaNombre', 'reservaFecha', 'reservaHora', 'reservaPersonas', 'reservaMesa'];
    campos.forEach(id => {
        const el = document.getElementById(id);
        const err = document.getElementById(`err-${id}`);
        if (!el.value || el.value === '') {
            el.classList.add('error');
            if (err) err.classList.add('visible');
            valid = false;
        } else {
            el.classList.remove('error');
            if (err) err.classList.remove('visible');
        }
    });

    if (!valid) {
        showToast('Formulario incompleto', 'Completa todos los campos obligatorios.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSubmitReserva');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    const payload = {
        nombre:   document.getElementById('reservaNombre').value.trim(),
        fecha:    document.getElementById('reservaFecha').value,
        hora:     document.getElementById('reservaHora').value,
        personas: parseInt(document.getElementById('reservaPersonas').value),
        mesa_id:  parseInt(document.getElementById('reservaMesa').value),
        notas:    document.getElementById('reservaNotas').value.trim(),
    };

    fetch('/api/admin/reservas', {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Reserva confirmada', `Mesa asignada para ${payload.nombre} el ${payload.fecha} a las ${payload.hora}.`, 'success');
            closeModal('modalReserva');
            document.getElementById('formReserva').reset();

            // Actualizar lista si la fecha coincide
            const currentFilter = document.getElementById('filtroFechaReservas').value;
            if (!currentFilter || currentFilter === payload.fecha) {
                document.getElementById('filtroFechaReservas').value = payload.fecha;
                fetchReservas();
            }
        } else {
            showToast('Error al guardar', data.error || 'Verifique los datos e intente de nuevo.', 'error');
        }
    })
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Reserva';
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. MESAS
// ─────────────────────────────────────────────────────────────────────────────

function fetchMesas() {
    // También cargar personal para drag and drop en esta vista
    fetch('/api/admin/staff')
        .then(r => r.json())
        .then(staffList => {
            renderDraggableStaff(staffList);
        }).catch(() => {});

    fetch('/api/admin/mesas')
        .then(res => res.json())
        .then(data => {
            const grid = document.getElementById('mesasGrid');
            grid.innerHTML = '';

            const positions = [
                {x:8,y:20},{x:30,y:20},{x:55,y:20},{x:78,y:20},
                {x:20,y:55},{x:50,y:55},{x:10,y:78},{x:75,y:78}
            ];

            data.forEach((m, idx) => {
                const pos = positions[idx] || { x: 50, y: 50 };
                const estadoClass = m.estado.toLowerCase();

                const div = document.createElement('div');
                div.className = `mesa-item ${estadoClass}`;
                // Ajustamos altura para acomodar la información adicional
                div.style.cssText = `position:absolute; left:${pos.x}%; top:${pos.y}%; width:90px; height:90px; padding:6px; display:flex; flex-direction:column; justify-content:space-between; align-items:center;`;
                div.setAttribute('data-id', m.id);
                div.onclick = () => showMesaDetail(m.numero_mesa, m.estado, m.capacidad, m.zona, m.empleado_nombre);

                // Calcular temporizador si está ocupada
                let timerStr = '';
                if (m.timer_inicio && m.estado === 'Ocupada') {
                    const start = new Date(m.timer_inicio);
                    const diffMs = new Date() - start;
                    const diffMins = Math.floor(diffMs / 60000);
                    timerStr = `<span style="font-size:9px; font-family:var(--font-mono); opacity:0.8;"><i class="fa-regular fa-clock"></i> ${diffMins}m</span>`;
                }

                // Indicador de pedido en preparación parpadeante
                const prepStr = m.pedido_en_preparacion 
                    ? `<span style="color:var(--gold-light); font-size:9px; font-weight:700;" class="pulse" title="Preparando Pedido"><i class="fa-solid fa-fire-burner"></i></span>`
                    : '';

                // Iniciales o primer nombre del mesero asignado
                const waiterStr = m.empleado_nombre 
                    ? `<span style="font-size:8px; background:rgba(255,255,255,0.25); padding:1px 3px; border-radius:3px; max-width:60px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="Mesero: ${m.empleado_nombre}">${m.empleado_nombre.split(' ')[0]}</span>`
                    : '';

                div.innerHTML = `
                    <div style="display:flex; justify-content:space-between; width:100%; font-size:8px; opacity:0.8;">
                        <span>${m.zona}</span>
                        ${prepStr}
                    </div>
                    <strong style="font-family:var(--font-serif); font-size:16px;">${m.numero_mesa.toString().padStart(2,'0')}</strong>
                    <div style="display:flex; flex-direction:column; align-items:center; width:100%; gap:2px;">
                        <span style="font-size:8px; opacity:0.7;">${m.capacidad} pax</span>
                        ${timerStr}
                        ${waiterStr}
                    </div>
                `;

                // Drag & Drop (Mesas)
                setupMesaDrag(div, m);

                // Allow dropping staff members
                div.ondragover = (event) => {
                    event.preventDefault();
                    div.style.boxShadow = '0 0 0 3px var(--gold)';
                };
                div.ondragleave = () => {
                    div.style.boxShadow = '';
                };
                div.ondrop = (event) => {
                    event.preventDefault();
                    div.style.boxShadow = '';
                    const staffId = event.dataTransfer.getData('text/plain');
                    if (staffId) {
                        assignStaffToMesa(m.id, staffId);
                    }
                };

                grid.appendChild(div);
            });
        })
        .catch(() => showToast('Error', 'No se pudo cargar el plano de mesas.', 'error'));
}

function renderDraggableStaff(staffList) {
    const container = document.getElementById('staffDraggableList');
    if (!container) return;
    container.innerHTML = '';
    
    // Filtrar meseros y personal activo
    const activos = staffList.filter(e => e.activo);
    
    if (activos.length === 0) {
        container.innerHTML = '<div style="font-size:11px; color:var(--gray-400); text-align:center;">No hay personal activo.</div>';
        return;
    }

    activos.forEach(e => {
        const div = document.createElement('div');
        div.className = 'badge badge-neutral';
        div.style.cssText = 'padding:8px 12px; cursor:grab; display:flex; justify-content:space-between; align-items:center; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:12px; background:var(--gray-50); width:100%; text-align:left;';
        div.draggable = true;
        div.setAttribute('data-id', e.id);
        div.innerHTML = `
            <span><i class="fa-solid fa-user-tie" style="color:var(--gold-dark); margin-right:6px;"></i> <strong>${e.nombre}</strong> <span style="font-size:10px; color:var(--gray-400);">(${e.cargo})</span></span>
        `;
        div.ondragstart = (event) => {
            event.dataTransfer.setData('text/plain', e.id);
        };
        container.appendChild(div);
    });
}

function assignStaffToMesa(mesaId, staffId) {
    fetch(`/api/admin/mesas/${mesaId}/empleado`, {
        method: 'PUT',
        headers: jsonHeaders(),
        body: JSON.stringify({ empleado_id: parseInt(staffId) })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Asignación exitosa', data.message, 'success');
            fetchMesas();
        } else {
            showToast('Error', data.error || 'No se pudo asignar el personal.', 'error');
        }
    })
    .catch(() => showToast('Error', 'Error al procesar la asignación.', 'error'));
}

function setupMesaDrag(mesaDiv, m) {
    mesaDiv.onmousedown = function(event) {
        if (event.target.tagName === 'BUTTON') return;
        mesaDiv.setAttribute('data-dragging', 'false');
        let isDragging = false;
        let shiftX = event.clientX - mesaDiv.getBoundingClientRect().left;
        let shiftY = event.clientY - mesaDiv.getBoundingClientRect().top;

        function moveAt(pageX, pageY) {
            const container = document.getElementById('mapaMesas');
            const rect = container.getBoundingClientRect();
            let newLeft = pageX - shiftX - rect.left;
            let newTop  = pageY - shiftY - rect.top;
            if (newLeft < 0) newLeft = 0;
            if (newTop  < 0) newTop  = 0;
            if (newLeft + mesaDiv.offsetWidth  > container.offsetWidth)  newLeft = container.offsetWidth  - mesaDiv.offsetWidth;
            if (newTop  + mesaDiv.offsetHeight > container.offsetHeight) newTop  = container.offsetHeight - mesaDiv.offsetHeight;
            const leftPct = (newLeft / container.offsetWidth)  * 100;
            const topPct  = (newTop  / container.offsetHeight) * 100;
            mesaDiv.style.left = leftPct + '%';
            mesaDiv.style.top  = topPct  + '%';
            mesaDiv.setAttribute('data-x', leftPct);
            mesaDiv.setAttribute('data-y', topPct);
        }

        function onMouseMove(event) {
            isDragging = true;
            mesaDiv.setAttribute('data-dragging', 'true');
            moveAt(event.pageX, event.pageY);
        }

        document.addEventListener('mousemove', onMouseMove);
        document.onmouseup = function() {
            document.removeEventListener('mousemove', onMouseMove);
            document.onmouseup = null;
            if (isDragging) {
                const x = mesaDiv.getAttribute('data-x');
                const y = mesaDiv.getAttribute('data-y');
                if (x && y) {
                    fetch(`/api/admin/mesas/${m.id}/coordenadas`, {
                        method: 'PUT',
                        headers: jsonHeaders(),
                        body: JSON.stringify({ x: parseFloat(x), y: parseFloat(y) }),
                    });
                }
                setTimeout(() => mesaDiv.setAttribute('data-dragging', 'false'), 50);
            }
        };
    };
    mesaDiv.ondragstart = () => false;
}

function showMesaDetail(num, estado, cap, zona = 'Principal', empleadoNombre = null) {
    document.getElementById('md-num').textContent = num.toString().padStart(2, '0');
    document.getElementById('md-estado').textContent = estado.toUpperCase();
    document.getElementById('md-cap').textContent = `${cap} Pax`;
    document.getElementById('md-zona').textContent = zona;
    document.getElementById('md-personal').textContent = empleadoNombre || 'Ninguno';

    const btnFactura = document.getElementById('btnMesaFactura');
    if (estado === 'Ocupada') {
        btnFactura.style.display = 'block';
        btnFactura.onclick = function () {
            fetch(`/api/admin/mesas/${num}/pedido-activo`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.pedido_id) viewTicket(data.pedido_id);
                    else showToast('Sin pedido activo', 'Esta mesa no tiene pedido activo.', 'info');
                });
        };
    } else {
        btnFactura.style.display = 'none';
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. STAFF
// ─────────────────────────────────────────────────────────────────────────────

function fetchStaff() {
    fetch('/api/admin/staff')
        .then(res => res.json())
        .then(data => {
            const grid = document.getElementById('staffGrid');
            grid.innerHTML = '';
            if (!data || data.length === 0) {
                grid.innerHTML = '<div class="table-empty">No hay personal registrado.</div>';
                return;
            }
            data.forEach(e => {
                let img = 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?auto=format&fit=crop&q=80&w=150';
                if (e.cargo && e.cargo.includes('Sommelier')) img = 'https://images.unsplash.com/photo-1583394838336-acd977736f90?auto=format&fit=crop&q=80&w=150';
                if (e.cargo && e.cargo.includes('Mesero'))    img = 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&q=80&w=150';

                grid.innerHTML += `
                    <div class="staff-card">
                        <div class="staff-avatar"><img src="${img}" alt="${e.cargo || 'Staff'}"></div>
                        <div class="staff-name">${e.nombre || 'Sin nombre'}</div>
                        <div class="staff-role">${e.cargo || 'Sin cargo'}</div>
                        <div style="margin-top:8px;">
                            <span class="badge ${e.activo ? 'badge-optimo' : 'badge-critico'}">${e.activo ? 'Activo' : 'Inactivo'}</span>
                        </div>
                    </div>
                `;
            });
        })
        .catch(() => showToast('Error', 'No se pudo cargar el personal.', 'error'));
}

function submitStaff(e) {
    e.preventDefault();
    const payload = {
        nombres: document.getElementById('staffNombre').value.trim(),
        cargo:   document.getElementById('staffCargo').value,
        rol:     document.getElementById('staffRol').value,
    };

    fetch('/api/admin/staff', {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const creds = data.credenciales
                ? `\n📧 Email: ${data.credenciales.email}\n🔑 Contraseña: ${data.credenciales.password}`
                : '';
            showToast('Staff registrado', `${payload.nombres} añadido como ${payload.cargo}.${creds}`, 'success');
            closeModal('modalStaff');
            document.getElementById('formStaff').reset();
            fetchStaff();
        } else {
            showToast('Error', data.error || 'No se pudo registrar el empleado.', 'error');
        }
    })
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'));
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. INVENTARIO — Estado y funciones avanzadas
// ─────────────────────────────────────────────────────────────────────────────

let _inventoryAll      = [];   // Dataset completo del servidor
let _inventoryFiltered = [];   // Dataset después de filtros
let _invPage      = 1;
let _invPageSize  = 10;
let _invSortCol   = null;
let _invSortDir   = 'asc';

/** Carga todos los insumos desde la API */
function fetchInsumos() {
    fetch('/api/admin/insumos')
        .then(res => res.json())
        .then(data => {
            // Actualizar KPIs
            document.getElementById('inv-valor-total').textContent = formatCOP(data.kpis.valor_total);
            document.getElementById('inv-alertas').textContent     = data.kpis.alertas + ' Críticos';
            document.getElementById('inv-rotacion').textContent    = data.kpis.rotacion;
            document.getElementById('inv-items').textContent       = data.kpis.items_activos + ' SKU';

            _inventoryAll = data.insumos || [];
            filterAndRenderInventory();
        })
        .catch(() => showToast('Error', 'No se pudo cargar el inventario.', 'error'));
}

/** Filtra la data según búsqueda, categoría y estado, luego renderiza */
function filterAndRenderInventory() {
    const search   = (document.getElementById('invSearch')?.value         || '').toLowerCase();
    const category = (document.getElementById('invCategoryFilter')?.value || '');
    const status   = (document.getElementById('invStatusFilter')?.value   || '');

    _inventoryFiltered = _inventoryAll.filter(i => {
        const matchSearch   = !search   || i.nombre.toLowerCase().includes(search) || i.categoria.toLowerCase().includes(search);
        const matchCategory = !category || i.categoria === category;
        const matchStatus   = !status   || i.estado === status;
        return matchSearch && matchCategory && matchStatus;
    });

    // Aplicar ordenamiento actual
    if (_invSortCol) applySortToFiltered();

    _invPage = 1; // Reset a página 1 al filtrar
    renderInventoryPage();
}

/** Ordena la columna seleccionada */
function sortInventory(col) {
    if (_invSortCol === col) {
        _invSortDir = _invSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        _invSortCol = col;
        _invSortDir = 'asc';
    }

    // Actualizar estilos de encabezados
    document.querySelectorAll('table.haute-table th.sortable').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    const thMap = { nombre: 'th-nombre', categoria: 'th-categoria', stock: 'th-stock', precio: 'th-precio', estado: 'th-estado' };
    const thEl = document.getElementById(thMap[col]);
    if (thEl) thEl.classList.add(`sort-${_invSortDir}`);

    applySortToFiltered();
    _invPage = 1;
    renderInventoryPage();
}

function applySortToFiltered() {
    _inventoryFiltered.sort((a, b) => {
        let va = a[_invSortCol];
        let vb = b[_invSortCol];
        if (typeof va === 'string') va = va.toLowerCase();
        if (typeof vb === 'string') vb = vb.toLowerCase();
        if (va < vb) return _invSortDir === 'asc' ? -1 : 1;
        if (va > vb) return _invSortDir === 'asc' ?  1 : -1;
        return 0;
    });
}

/** Cambia el tamaño de página */
function changePageSize() {
    _invPageSize = parseInt(document.getElementById('invPageSize').value);
    _invPage = 1;
    renderInventoryPage();
}

/** Renderiza la página actual de la tabla */
function renderInventoryPage() {
    const tbody    = document.getElementById('inventoryTableBody');
    const total    = _inventoryFiltered.length;
    const start    = (_invPage - 1) * _invPageSize;
    const pageData = _inventoryFiltered.slice(start, start + _invPageSize);

    tbody.innerHTML = '';

    if (total === 0) {
        tbody.innerHTML = `
            <tr><td colspan="6" class="table-empty">
                <i class="fa-solid fa-magnifying-glass"></i>
                No se encontraron insumos con esos filtros.
            </td></tr>`;
        renderPagination(0, 0);
        return;
    }

    pageData.forEach(i => {
        const badgeClass  = i.estado === 'CRÍTICO' ? 'badge-critico' : 'badge-optimo';
        const stockColor  = i.estado === 'CRÍTICO' ? 'var(--danger)' : 'var(--black)';
        const stockStr    = `<strong style="color:${stockColor};">${i.stock}</strong> <span style="color:var(--gray-400); font-size:11px;">${i.unidad}</span>`;
        const precioStr   = formatCOP(i.precio);
        const calcExtra   = i.categoria === 'Carnes'
            ? `<br><span style="font-size:10px; color:var(--gold-dark); cursor:pointer;" onclick="mostrarCalculadoraMP('${i.id}', '${i.precio}')"><i class="fa-solid fa-calculator"></i> Calc. Porción</span>`
            : '';

        let img = 'https://images.unsplash.com/photo-1599599811442-1262d5f0e9f6?auto=format&fit=crop&q=80&w=80';
        if (i.categoria === 'Carnes') img = 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?auto=format&fit=crop&q=80&w=80';
        if (i.nombre.toLowerCase().includes('trufa')) img = 'https://images.unsplash.com/photo-1626200419188-f56743b17c9d?auto=format&fit=crop&q=80&w=80';
        if (i.categoria === 'Bodega Exclusiva' || i.nombre.toLowerCase().includes('vino') || i.nombre.toLowerCase().includes('champagne')) img = 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?auto=format&fit=crop&q=80&w=80';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="inv-item-info">
                    <img src="${img}" class="inv-img" alt="${i.nombre}">
                    <div>
                        <strong style="display:block; font-size:13px;">${i.nombre}</strong>
                        <span style="font-size:11px; color:var(--gray-400);">Mínimo: ${i.stock_minimo} ${i.unidad}</span>
                    </div>
                </div>
            </td>
            <td><span class="badge badge-neutral" style="font-weight:600;">${i.categoria.toUpperCase()}</span></td>
            <td>${stockStr}</td>
            <td>${precioStr}${calcExtra}</td>
            <td>
                <span class="badge ${badgeClass}">
                    <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;margin-right:4px;"></span>
                    ${i.estado}
                </span>
            </td>
            <td>
                <button class="btn-danger" onclick="eliminarMateriaPrima(${i.id}, '${i.nombre.replace(/'/g, "\\'")}')" title="Eliminar insumo">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    renderPagination(total, start + pageData.length);
}

/** Renderiza los controles de paginación */
function renderPagination(total, showing) {
    const totalPages = Math.ceil(total / _invPageSize);
    const info       = document.getElementById('invPaginationInfo');
    const controls   = document.getElementById('invPaginationControls');

    info.textContent = total > 0
        ? `Mostrando ${(_invPage - 1) * _invPageSize + 1}–${Math.min(_invPage * _invPageSize, total)} de ${total} ítems`
        : '0 resultados';

    controls.innerHTML = '';

    if (totalPages <= 1) return;

    // Botón anterior
    const prev = document.createElement('button');
    prev.className = 'page-btn';
    prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    prev.disabled  = _invPage === 1;
    prev.onclick   = () => { _invPage--; renderInventoryPage(); };
    controls.appendChild(prev);

    // Números de página
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && Math.abs(p - _invPage) > 2 && p !== 1 && p !== totalPages) {
            if (p === 2 || p === totalPages - 1) {
                const dots = document.createElement('span');
                dots.textContent = '…';
                dots.style.cssText = 'padding:0 4px; color:var(--gray-400);';
                controls.appendChild(dots);
            }
            continue;
        }
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (p === _invPage ? ' active' : '');
        btn.textContent = p;
        btn.onclick = ((page) => () => { _invPage = page; renderInventoryPage(); })(p);
        controls.appendChild(btn);
    }

    // Botón siguiente
    const next = document.createElement('button');
    next.className = 'page-btn';
    next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    next.disabled  = _invPage === totalPages;
    next.onclick   = () => { _invPage++; renderInventoryPage(); };
    controls.appendChild(next);
}

// ── Calculadora de porción ──

function checkCategoriaMP() {
    const cat  = document.getElementById('mpCategoria').value;
    const calc = document.getElementById('mpCalculadoraPorcion');
    calc.style.display = (cat === 'Carnes') ? 'block' : 'none';
}

function calcularPorcion() {
    const costo  = parseFloat(document.getElementById('mpCosto').value) || 0;
    const gramos = parseFloat(document.getElementById('mpPesoPorcion')?.value) || 0;
    const res    = document.getElementById('mpCostoPorcionRes');
    if (!res) return;
    if (gramos > 0 && costo > 0) {
        const costoGramos = (costo / 1000) * gramos;
        res.innerHTML = `Costo por porción (${gramos}g): <strong style="color:var(--gold-dark);">${formatCOP(costoGramos)}</strong>`;
    } else {
        res.innerHTML = 'Ingresa costo y peso por porción.';
    }
}

function mostrarCalculadoraMP(id, precio) {
    document.getElementById('mpCosto').value    = precio;
    document.getElementById('mpCategoria').value = 'Carnes';
    checkCategoriaMP();
    openModal('modalMateriaPrima');
}

// ── CRUD de MateriaPrima ──

function submitMateriaPrima(e) {
    e.preventDefault();

    // Validación frontend
    const campos = ['mpNombre', 'mpCategoria', 'mpUnidad', 'mpCantidad', 'mpStockMinimo', 'mpCosto'];
    let valid = true;
    campos.forEach(id => {
        const el = document.getElementById(id);
        const err = document.getElementById(`err-${id}`);
        if (!el.value || el.value === '') {
            el.classList.add('error');
            if (err) err.classList.add('visible');
            valid = false;
        } else {
            el.classList.remove('error');
            if (err) err.classList.remove('visible');
        }
    });

    if (!valid) {
        showToast('Formulario incompleto', 'Completa todos los campos obligatorios.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSubmitMateriaPrima');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    const payload = {
        nombre:          document.getElementById('mpNombre').value.trim(),
        categoria:       document.getElementById('mpCategoria').value,
        cantidad_actual: parseFloat(document.getElementById('mpCantidad').value),
        unidad_medida:   document.getElementById('mpUnidad').value,
        stock_minimo:    parseFloat(document.getElementById('mpStockMinimo').value),
        costo_unitario:  parseFloat(document.getElementById('mpCosto').value),
    };

    fetch('/api/admin/insumos', {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Insumo guardado', `"${payload.nombre}" fue añadido al inventario.`, 'success');
            closeModal('modalMateriaPrima');
            document.getElementById('formMateriaPrima').reset();
            document.getElementById('mpCalculadoraPorcion').style.display = 'none';
            fetchInsumos();
        } else {
            const errMsg = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.error || 'Verifique los datos.');
            showToast('Error al guardar', errMsg, 'error');
        }
    })
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Insumo';
    });
}

function eliminarMateriaPrima(id, nombre) {
    if (!confirm(`¿Eliminar el insumo "${nombre}"? Esta acción no se puede deshacer.`)) return;

    fetch(`/api/admin/insumos/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Insumo eliminado', `"${nombre}" fue removido del inventario.`, 'success');
            fetchInsumos();
        } else {
            showToast('Error', data.error || 'No se pudo eliminar.', 'error');
        }
    })
    .catch(() => showToast('Error', 'Error de conexión al eliminar.', 'error'));
}

// ── Exportación ──

function exportCSV() {
    if (_inventoryFiltered.length === 0) {
        showToast('Sin datos', 'No hay insumos que exportar con los filtros actuales.', 'warning');
        return;
    }
    const headers = ['ID', 'Nombre', 'Categoría', 'Stock Actual', 'Unidad', 'Costo Unitario', 'Stock Mínimo', 'Estado'];
    const rows    = _inventoryFiltered.map(i => [i.id, i.nombre, i.categoria, i.stock, i.unidad, i.precio, i.stock_minimo, i.estado]);
    const csv     = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
    downloadFile(csv, `inventario_${new Date().toISOString().split('T')[0]}.csv`, 'text/csv');
    showToast('Exportado', 'El inventario fue exportado como CSV.', 'success');
}

function exportJSON() {
    if (_inventoryFiltered.length === 0) {
        showToast('Sin datos', 'No hay insumos que exportar con los filtros actuales.', 'warning');
        return;
    }
    const json = JSON.stringify(_inventoryFiltered, null, 2);
    downloadFile(json, `inventario_${new Date().toISOString().split('T')[0]}.json`, 'application/json');
    showToast('Exportado', 'El inventario fue exportado como JSON.', 'success');
}

function downloadFile(content, filename, type) {
    const blob = new Blob([content], { type });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}

// ── Reposición ──

function submitReposicion(e) {
    e.preventDefault();
    const pin = document.getElementById('repPin').value;

    fetch('/api/admin/insumos/pedido', {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ pin }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Pedido enviado', data.message || 'Pedido de reposición generado correctamente.', 'success');
            closeModal('modalReposicion');
            document.getElementById('formReposicion').reset();
        } else {
            showToast('Error', data.error || 'PIN inválido o error en el servidor.', 'error');
        }
    })
    .catch(() => showToast('Error', 'Error de conexión.', 'error'));
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. HISTORIAL DE PEDIDOS
// ─────────────────────────────────────────────────────────────────────────────

function fetchHistorial() {
    const search = document.getElementById('pedidosSearch')?.value || '';
    let url = '/api/pedidos';
    if (search) url += `?search=${encodeURIComponent(search)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            const tbody  = document.getElementById('historialTableBody');
            tbody.innerHTML = '';
            const pedidos = data.data || [];

            if (pedidos.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="table-empty"><i class="fa-solid fa-receipt"></i> No se encontraron pedidos.</td></tr>`;
                return;
            }

            pedidos.forEach(p => {
                const d           = new Date(p.created_at);
                const formatMesa  = p.mesa   ? `Mesa ${p.mesa.numero_mesa}` : 'Bar / Llevar';
                const formatClient = p.cliente && p.cliente.usuario
                    ? `${p.cliente.usuario.nombres} ${p.cliente.usuario.apellidos}`
                    : 'Cliente Estándar';
                const formatTotal = formatCOP(p.total);

                let badgeClass = 'badge-optimo';
                if (p.estado === 'Cancelado')                         badgeClass = 'badge-critico';
                else if (['Nuevo', 'En_Preparacion'].includes(p.estado)) badgeClass = 'badge-pendiente';

                tbody.innerHTML += `
                    <tr>
                        <td><strong>#${p.id.toString().padStart(4, '0')}</strong></td>
                        <td style="font-size:12px; color:var(--gray-600);">
                            ${d.toLocaleDateString()}<br>
                            ${d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}
                        </td>
                        <td>
                            <strong style="display:block;">${formatClient}</strong>
                            <span style="font-size:11px; color:var(--gray-400);">${formatMesa}</span>
                        </td>
                        <td><strong>${formatTotal}</strong></td>
                        <td><span class="badge ${badgeClass}">${p.estado}</span></td>
                        <td>
                            <button class="btn-outline" style="padding:6px 12px; font-size:12px;" onclick="viewTicket(${p.id})">
                                <i class="fa-regular fa-file-lines"></i> Ticket
                            </button>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(() => showToast('Error', 'No se pudo cargar el historial.', 'error'));
}

function viewTicket(id) {
    fetch(`/api/pedidos/${id}`)
        .then(res => res.json())
        .then(p => {
            document.getElementById('ticketNum').textContent = `#${p.id.toString().padStart(4,'0')}`;
            const d = new Date(p.created_at);
            document.getElementById('ticketDate').textContent    = `${d.toLocaleDateString()} — ${d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}`;
            document.getElementById('ticketClient').textContent  = p.cliente && p.cliente.usuario
                ? `${p.cliente.usuario.nombres} ${p.cliente.usuario.apellidos}` : 'Cliente Genérico';
            document.getElementById('ticketMesa').textContent    = p.mesa ? `Mesa ${p.mesa.numero_mesa}` : 'Bar / Llevar';

            const itemsContainer = document.getElementById('ticketItems');
            itemsContainer.innerHTML = '';

            if (p.detalles && p.detalles.length > 0) {
                p.detalles.forEach(item => {
                    const sub = formatCOP(item.subtotal);
                    itemsContainer.innerHTML += `
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                            <div style="flex:1;">${item.cantidad}x ${item.producto ? item.producto.nombre : 'Item'}</div>
                            <div style="text-align:right;">${sub}</div>
                        </div>
                        ${item.notas_especiales ? `<div style="font-size:11px; font-style:italic; padding-left:14px; color:var(--gray-400); margin-bottom:5px;">— ${item.notas_especiales}</div>` : ''}
                    `;
                });
            } else {
                itemsContainer.innerHTML = '<div style="color:var(--gray-400); font-style:italic;">Sin detalle de ítems.</div>';
            }

            document.getElementById('ticketTotal').textContent = formatCOP(p.total);
            openModal('modalTicket');
        })
        .catch(() => showToast('Error', 'No se pudo cargar el ticket.', 'error'));
}

// ── Comanda ──
function submitComanda(e) {
    e.preventDefault();
    fetch('/api/admin/mesas/comanda', {
        method: 'POST',
        headers: jsonHeaders(),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Acción ejecutada', 'La comanda fue procesada correctamente.', 'success');
            closeModal('modalComanda');
        } else {
            showToast('Error', 'No se pudo ejecutar la acción.', 'error');
        }
    })
    .catch(() => showToast('Error', 'Error de conexión.', 'error'));
}

// ─────────────────────────────────────────────────────────────────────────────
// EDICIÓN DE RESERVAS
// ─────────────────────────────────────────────────────────────────────────────

/** Abre el modal de edición precargado con los datos de una reserva */
function abrirEditarReserva(r) {
    document.getElementById('editReservaId').value = r.id;
    // Extraer nombre desde las notas si está guardado ahí
    let nombre = r.cliente_nombre || '';
    const matchNota = (r.notas || '').match(/Cliente:\s*([^—\n]+)/i);
    if (matchNota) nombre = matchNota[1].trim();

    document.getElementById('editReservaNombre').value  = nombre;
    document.getElementById('editReservaFecha').value   = (r.fecha_hora || r.fecha || '').substring(0, 10);
    document.getElementById('editReservaHora').value    = r.hora || (r.fecha_hora || '').substring(11, 16);
    document.getElementById('editReservaPersonas').value= r.num_personas || '';
    document.getElementById('editReservaEstado').value  = r.estado || 'Confirmada';

    // Extraer notas sin la parte de "Cliente: xxx —"
    let notasLimpias = (r.notas || '').replace(/Cliente:\s*[^—\n]+(—\s*)?/i, '').trim();
    document.getElementById('editReservaNotas').value = notasLimpias;

    // Cargar opciones de mesas en el select
    const selMesa = document.getElementById('editReservaMesa');
    selMesa.innerHTML = '<option value="">— Cargando mesas... —</option>';
    fetch('/api/admin/mesas')
        .then(res => res.json())
        .then(mesas => {
            selMesa.innerHTML = '<option value="">— Seleccionar —</option>';
            mesas.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = `Mesa ${m.numero_mesa} (${m.capacidad} pax) — ${m.estado}`;
                if (m.id === r.mesa_id) opt.selected = true;
                selMesa.appendChild(opt);
            });
        }).catch(() => {
            selMesa.innerHTML = '<option value="">— Error al cargar —</option>';
        });

    openModal('modalEditarReserva');
}

/** Envía la edición al servidor */
function submitEditarReserva(e) {
    e.preventDefault();
    const id = document.getElementById('editReservaId').value;
    if (!id) return;

    const btn = document.getElementById('btnSubmitEditarReserva');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    const payload = {
        nombre:   document.getElementById('editReservaNombre').value.trim(),
        fecha:    document.getElementById('editReservaFecha').value,
        hora:     document.getElementById('editReservaHora').value,
        personas: parseInt(document.getElementById('editReservaPersonas').value),
        mesa_id:  parseInt(document.getElementById('editReservaMesa').value),
        estado:   document.getElementById('editReservaEstado').value,
        notas:    document.getElementById('editReservaNotas').value.trim(),
    };

    fetch(`/api/admin/reservas/${id}`, {
        method: 'PUT',
        headers: jsonHeaders(),
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✓ Reserva actualizada', 'El cliente fue notificado del cambio.', 'success');
            closeModal('modalEditarReserva');
            fetchReservas();
        } else {
            showToast('Error', data.error || 'No se pudo actualizar la reserva.', 'error');
        }
    })
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios';
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// ELIMINACIÓN DE RESERVAS
// ─────────────────────────────────────────────────────────────────────────────

function abrirEliminarReserva(id, nombre, estado) {
    // Bloquear eliminación de completadas en frontend también
    if (estado === 'Completada') {
        showToast('Acción no permitida', 'No se pueden eliminar reservas ya completadas.', 'warning');
        return;
    }
    document.getElementById('eliminarReservaId').value = id;
    document.getElementById('eliminarReservaNombre').textContent = nombre;
    openModal('modalEliminarReserva');
}

function confirmarEliminarReserva() {
    const id = document.getElementById('eliminarReservaId').value;
    if (!id) return;

    const btn = document.getElementById('btnConfirmarEliminar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Eliminando...';

    fetch(`/api/admin/reservas/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✓ Reserva eliminada', 'El cliente fue notificado de la cancelación.', 'success');
            closeModal('modalEliminarReserva');
            fetchReservas();
        } else {
            showToast('Error', data.error || 'No se pudo eliminar la reserva.', 'error');
        }
    })
    .catch(() => showToast('Error', 'Error de conexión al eliminar.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-trash"></i> Sí, Cancelar Reserva';
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// EXPORTADORES CORPORATIVOS — Excel y PDF
// ─────────────────────────────────────────────────────────────────────────────

function _getMetaExport() {
    if (!_metaSemana || !_ventasPorDia.length) {
        showToast('Sin datos', 'Espera a que cargue el reporte de la semana.', 'warning');
        return null;
    }
    return {
        meta: _metaSemana,
        filas: _ventasPorDia.filter(d => !d.es_futuro),
    };
}

function exportarExcel() {
    const d = _getMetaExport();
    if (!d) return;

    const { meta, filas } = d;
    const ahora = new Date();
    const fechaGen = ahora.toLocaleDateString('es-CO') + ' ' + ahora.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });

    // Construir workbook
    const wb = XLSX.utils.book_new();

    // Hoja 1: Encabezado corporativo + tabla
    const encabezado = [
        ['SABOR A PUEBLO', '', '', '', ''],
        ['Reporte de Ventas Semanales', '', '', '', ''],
        [`Semana: ${meta.inicio} al ${meta.fin}`, '', '', '', ''],
        [`Generado: ${fechaGen}`, '', '', '', ''],
        [''],
        ['Día', 'Fecha', 'Ventas (COP)', 'Pedidos', 'Ticket Prom.'],
    ];

    const totalRow = (val) => val.toLocaleString('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 });

    const datos = filas.map(r => [
        r.dia,
        r.fecha,
        r.ventas,
        r.pedidos,
        r.pedidos > 0 ? Math.round(r.ventas / r.pedidos) : 0,
    ]);

    const totales = [
        [''],
        ['TOTALES', '', meta.total_semanal, meta.total_pedidos, meta.ticket_semanal],
    ];

    const wsData = [...encabezado, ...datos, ...totales];
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    // Anchos de columna
    ws['!cols'] = [{ wch:12 }, { wch:14 }, { wch:20 }, { wch:12 }, { wch:18 }];

    XLSX.utils.book_append_sheet(wb, ws, 'Ventas Semanales');

    const filename = `reporte_ventas_semanal_${meta.inicio}.xlsx`;
    XLSX.writeFile(wb, filename);
    showToast('✓ Excel exportado', `Archivo: ${filename}`, 'success');
}

function exportarPDF() {
    const d = _getMetaExport();
    if (!d) return;

    const { meta, filas } = d;
    const ahora = new Date();
    const fechaGen = ahora.toLocaleDateString('es-CO') + ' ' + ahora.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

    // ─ Encabezado corporativo ─
    doc.setFillColor(10, 10, 10);
    doc.rect(0, 0, 210, 28, 'F');
    doc.setTextColor(210, 165, 85);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(18);
    doc.text('SABOR A PUEBLO', 14, 12);
    doc.setFontSize(9);
    doc.setTextColor(200, 200, 200);
    doc.text('Restaurante & Cocina Tradicional', 14, 19);
    doc.setFont('helvetica', 'normal');
    doc.text(`Generado: ${fechaGen}`, 150, 19);

    // ─ Sub-encabezado ─
    doc.setTextColor(30, 30, 30);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.text('Reporte de Ventas Semanales', 14, 40);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.setTextColor(100, 100, 100);
    doc.text(`Período: ${meta.inicio}  al  ${meta.fin}`, 14, 47);

    // ─ KPIs resumen ─
    const fmtCOP = (n) => n.toLocaleString('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 });
    doc.setDrawColor(210, 165, 85);
    doc.setLineWidth(0.3);
    doc.line(14, 52, 196, 52);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(30, 30, 30);
    doc.text(`Total semanal: ${fmtCOP(meta.total_semanal)}`, 14, 60);
    doc.text(`Total pedidos: ${meta.total_pedidos}`, 80, 60);
    doc.text(`Ticket prom.: ${fmtCOP(meta.ticket_semanal)}`, 140, 60);

    // ─ Tabla de datos ─
    const colHeaders = [['Día', 'Fecha', 'Ventas (COP)', 'Pedidos', 'Ticket Prom. (COP)']];
    const filasPDF = filas.map(r => [
        r.dia,
        r.fecha,
        fmtCOP(r.ventas),
        r.pedidos,
        r.pedidos > 0 ? fmtCOP(Math.round(r.ventas / r.pedidos)) : '—',
    ]);

    doc.autoTable({
        head: colHeaders,
        body: filasPDF,
        startY: 68,
        styles: { fontSize: 9, cellPadding: 3 },
        headStyles: { fillColor: [10, 10, 10], textColor: [210, 165, 85], fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [249, 247, 243] },
        footStyles: { fillColor: [240, 235, 225], fontStyle: 'bold' },
        foot: [['TOTAL', '', fmtCOP(meta.total_semanal), meta.total_pedidos, fmtCOP(meta.ticket_semanal)]],
        showFoot: 'lastPage',
        margin: { left: 14, right: 14 },
    });

    // ─ Pie de página ─
    const pageCount = doc.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(`Página ${i} de ${pageCount}  —  Sabor a Pueblo  —  Documento confidencial`, 14, 290);
    }

    const filename = `reporte_ventas_semanal_${meta.inicio}.pdf`;
    doc.save(filename);
    showToast('✓ PDF exportado', `Archivo: ${filename}`, 'success');
}
</script>

</body>
</html>