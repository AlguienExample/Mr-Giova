<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo — Terminal de Caja & POS</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-light.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        try {
            if (localStorage.getItem('sabor-theme') === 'light') {
                document.addEventListener('DOMContentLoaded', function () {
                    document.body.classList.add('light-mode');
                });
            }
        } catch (e) {}
    </script>
    <link rel="stylesheet" href="{{ asset('css/pages/caja.css') }}">
</head>
<body class="admin-page">
<div class="kfc-stripe-top"></div>
<div id="toastContainerCaja" aria-live="polite" style="position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;"></div>
<div class="admin-app">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand" style="display:flex; align-items:center; gap:12px; padding: 24px 20px;">
            <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo Logo" style="width:44px; height:44px; border-radius:50%; object-fit:cover; border:2px solid var(--gold-dark); box-shadow: 0 4px 10px rgba(0,0,0,0.4);">
            <div>
                <h2 style="font-size:20px; color:#ffffff; font-weight:700; line-height:1.1;">Sabor<span style="color:var(--gold);"> a Pueblo</span></h2>
                <div class="subtitle" style="font-size:10px; text-transform:uppercase; letter-spacing:1.5px; color:var(--gold-dark); margin-top:2px;">Terminal de Caja</div>
            </div>
        </div>
        <ul class="admin-nav">
            <li class="admin-nav-item active">
                <a href="#"><i class="fa-solid fa-cash-register"></i> Control de Mesas</a>
            </li>
            <li class="admin-nav-item">
                <a href="/admin"><i class="fa-solid fa-chart-line"></i> Panel Admin</a>
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

    <!-- MAIN -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="admin-header-title">
                <h1 style="display:flex;align-items:center;gap:12px;">
                    <div style="width:4px;height:24px;background:var(--gold);border-radius:2px;"></div>
                    <span>Caja &amp; Facturación</span>
                    <div class="floating-money-container">
                        <span class="floating-money-bill"><i class="fa-solid fa-money-bill-wave"></i> POS En Vivo</span>
                    </div>
                </h1>
                <div class="content-subtitle" style="margin-top:5px;margin-left:16px;">Gestión de cobros, recibos térmicos y comandas en tiempo real.</div>
            </div>
            <div class="admin-header-actions" style="display:flex;gap:20px;align-items:center;">
                <button class="theme-toggle-btn" type="button" onclick="toggleTheme()" aria-label="Cambiar a modo claro" title="Cambiar a modo claro">
                    <i class="fa-solid fa-sun"></i>
                </button>
                <div style="display:flex;gap:16px;font-size:11px;text-transform:uppercase;letter-spacing:1px;align-items:center;">
                    <div><span class="led-dot disponible"></span>Libres: <strong id="count-libres">{{ $libres }}</strong></div>
                    <div><span class="led-dot ocupada"></span>Ocupadas: <strong id="count-ocupadas">{{ $ocupadas }}</strong></div>
                    <div><span class="led-dot disponible" style="background:#10B981;"></span>Pedidos Hoy: <strong id="count-hoy">{{ $conPedidoHoy }}</strong></div>
                </div>
            </div>
        </header>

        <div class="admin-content">
            <div class="caja-grid">
                <!-- Mapa de Mesas -->
                <div class="mesas-area">
                    <div class="filter-bar">
                        <div class="content-title" style="margin:0;font-size:15px;">Planta Principal</div>
                        <div style="flex:1;"></div>
                        <label class="toggle-pill active" id="pillFiltroJornada" onclick="toggleFiltroJornada(this)">
                            <i class="fa-solid fa-calendar-day"></i>
                            Solo activas hoy
                        </label>
                        <label class="toggle-pill" id="pillTodasMesas" onclick="toggleFiltroJornada(null)">
                            <i class="fa-solid fa-border-all"></i>
                            Ver todas
                        </label>
                    </div>

                    <div class="mesas-grid" id="mesasGrid">
                        @foreach($mesas as $mesa)
                            @php
                                $claseEstado  = '';
                                $textoEstado  = 'Disponible';
                                $labelClass   = '';
                                $iconEstado   = '';
                                $esActiva     = in_array($mesa['estado'], ['Ocupada','Cuenta','Reservada']);
                                $tienePedidoHoy = $mesa['tiene_pedido_hoy'];

                                if ($mesa['estado'] === 'Ocupada') {
                                    $claseEstado = 'estado-ocupada';
                                    $textoEstado = 'Ocupada';
                                    $labelClass  = 'ocupada';
                                    $iconEstado  = '<i class="fa-solid fa-circle-dot" style="color:var(--gold-dark);margin-right:3px;"></i>';
                                } elseif ($mesa['estado'] === 'Cuenta') {
                                    $claseEstado = 'estado-cuenta';
                                    $textoEstado = 'Cuenta';
                                    $labelClass  = 'cuenta';
                                    $iconEstado  = '<i class="fa-solid fa-file-invoice-dollar" style="color:#D32F2F;margin-right:3px;"></i>';
                                } elseif ($mesa['estado'] === 'Reservada') {
                                    $claseEstado = 'estado-reservada';
                                    $textoEstado = 'Reservada';
                                    $labelClass  = 'reservada';
                                    $iconEstado  = '<i class="fa-solid fa-bookmark" style="color:#7B5EA7;margin-right:3px;"></i>';
                                }

                                // Alerta de permanencia
                                $alertaTiempo = '';
                                if ($mesa['minutos_ocupada'] !== null) {
                                    if ($mesa['minutos_ocupada'] >= 120) {
                                        $alertaTiempo = '<div class="alerta-tiempo critica"><i class="fa-solid fa-fire"></i> ' . $mesa['minutos_ocupada'] . 'm</div>';
                                    } elseif ($mesa['minutos_ocupada'] >= 90) {
                                        $alertaTiempo = '<div class="alerta-tiempo"><i class="fa-regular fa-clock"></i> ' . $mesa['minutos_ocupada'] . 'm</div>';
                                    }
                                }

                                // Las mesas sin pedido hoy Y disponibles se ocultan por defecto
                                $ocultarPorFiltro = !$tienePedidoHoy && !$esActiva;
                            @endphp
                            <div class="mesa-card {{ $claseEstado }} {{ $ocultarPorFiltro ? 'hidden-by-filter' : '' }}"
                                 data-id="{{ $mesa['id'] }}"
                                 data-numero="{{ $mesa['numero_mesa'] }}"
                                 data-activa="{{ $esActiva ? '1' : '0' }}"
                                 data-tiene-pedido="{{ $tienePedidoHoy ? '1' : '0' }}"
                                 onclick="seleccionarMesa({{ $mesa['id'] }}, '{{ str_pad($mesa['numero_mesa'], 2, '0', STR_PAD_LEFT) }}')">
                                {!! $alertaTiempo !!}
                                <div class="mesa-num">{{ str_pad($mesa['numero_mesa'], 2, '0', STR_PAD_LEFT) }}</div>
                                <div class="mesa-status-label {{ $labelClass }}">{!! $iconEstado !!}{{ $textoEstado }}</div>
                                <div style="font-size:10px;color:var(--gray-400);margin-top:4px;">{{ $mesa['capacidad'] }} pax</div>
                                @if($tienePedidoHoy && $mesa['total_pedido'] > 0)
                                    <div style="font-size:10px;color:var(--gold-dark);margin-top:3px;font-weight:700;">
                                        {{ number_format($mesa['total_pedido'], 0, ',', '.') }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Panel de Ticket -->
                <div class="ticket-panel thermal-ticket">
                    <div class="empty-ticket" id="panel-empty">
                        <i class="fa-solid fa-receipt" style="font-size:2.8rem;margin-bottom:15px;color:var(--gold);opacity:0.4;"></i>
                        <div style="font-family:var(--font-serif);font-size:18px;margin-bottom:8px;color:var(--gold);">Sin selección</div>
                        <div style="font-size:12px;color:var(--gray-400);">Selecciona una mesa activa para emitir la pre-cuenta o factura</div>
                    </div>

                    <div id="panel-content" style="display:none;flex-direction:column;height:100%;">
                        <div class="ticket-header-brand">
                            <div class="ticket-logo-text"><i class="fa-solid fa-receipt"></i> Sabor a Pueblo POS</div>
                            <div class="ticket-subtext">COMPROBANTE VIRTUAL DE CONSUMO</div>
                        </div>

                        <!-- Header -->
                        <div class="ticket-header">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                <div style="font-family:var(--font-serif);font-size:18px;font-weight:700;color:var(--text-main);" id="pedido-titulo">Mesa --</div>
                                <span class="badge badge-pendiente" id="pedido-estado-badge" style="font-size:9px;">ACTIVO</span>
                            </div>
                            <div style="font-size:11px;color:var(--gray-400);" id="pedido-info">Pedido #---</div>
                            <!-- Alerta de tiempo en mesa -->
                            <div id="alerta-permanencia" style="display:none;margin-top:8px;padding:6px 10px;background:#FFF3CD;border:1px solid #FFECB5;border-radius:6px;font-size:11px;color:#856404;font-weight:600;">
                                <i class="fa-solid fa-triangle-exclamation"></i> <span id="alerta-permanencia-text"></span>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div style="padding:12px 20px 0;">
                            <div class="ticket-tabs">
                                <div class="ticket-tab active" id="tab-cuenta" onclick="switchTicketTab('cuenta')">
                                    <i class="fa-solid fa-receipt"></i> Cuenta
                                </div>
                                <div class="ticket-tab" id="tab-propina" onclick="switchTicketTab('propina')">
                                    <i class="fa-solid fa-hand-holding-dollar"></i> Propina
                                </div>
                                <div class="ticket-tab" id="tab-dividir" onclick="switchTicketTab('dividir')">
                                    <i class="fa-solid fa-users"></i> Dividir
                                </div>
                            </div>
                        </div>

                        <!-- Cuerpo -->
                        <div class="ticket-body">
                            <!-- Tab: Cuenta (items) -->
                            <div id="tab-content-cuenta">
                                <div id="lista-items"></div>
                            </div>

                            <!-- Tab: Propina -->
                            <div id="tab-content-propina" style="display:none;">
                                <div style="font-size:12px;color:var(--gray-600);margin-bottom:12px;font-weight:600;">Selecciona el porcentaje de propina:</div>
                                <div class="tip-selector">
                                    <div class="tip-btn" onclick="selectTip(0)">Sin propina</div>
                                    <div class="tip-btn" onclick="selectTip(10)">10%</div>
                                    <div class="tip-btn" onclick="selectTip(15)">15%</div>
                                    <div class="tip-btn" onclick="selectTip(20)">20%</div>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                                    <label style="font-size:11px;color:var(--gray-600);font-weight:600;white-space:nowrap;">Personalizada:</label>
                                    <input type="number" id="tip-custom" class="form-control" style="width:80px;padding:6px;" min="0" max="100" placeholder="%"
                                           onchange="selectTip(parseFloat(this.value)||0)">
                                    <span style="font-size:12px;color:var(--gray-400);">%</span>
                                </div>
                                <div style="background:var(--gold-bg);border:1px solid var(--gold);border-radius:8px;padding:12px;text-align:center;">
                                    <div style="font-size:11px;color:var(--gray-600);margin-bottom:4px;">Propina calculada</div>
                                    <div style="font-family:var(--font-serif);font-size:24px;color:var(--gold-dark);font-weight:700;" id="txt-propina-valor">$0</div>
                                    <div style="font-size:11px;color:var(--gray-400);margin-top:4px;">Total con propina: <strong id="txt-total-con-propina">$0</strong></div>
                                </div>
                            </div>

                            <!-- Tab: Dividir -->
                            <div id="tab-content-dividir" style="display:none;">
                                <div style="font-size:12px;color:var(--gray-600);margin-bottom:12px;">Divide la cuenta entre comensales de forma equitativa:</div>
                                <div class="split-panel">
                                    <label>Número de comensales</label>
                                    <div class="split-controls">
                                        <button class="split-num-btn" onclick="changeSplit(-1)">−</button>
                                        <div id="split-count">2</div>
                                        <button class="split-num-btn" onclick="changeSplit(1)">+</button>
                                        <span style="font-size:11px;color:var(--gray-400);">personas</span>
                                    </div>
                                    <div id="split-result">Cada persona paga: —</div>
                                </div>
                                <div style="margin-top:14px;font-size:11px;color:var(--gray-400);">
                                    <i class="fa-solid fa-circle-info"></i>
                                    División basada en el total actual (incluye IVA y propina si aplica).
                                </div>
                            </div>
                        </div>

                        <!-- Footer con totales y acciones -->
                        <div class="ticket-footer">
                            <div class="total-row"><span>Subtotal</span><span id="txt-subtotal">$0</span></div>
                            <div class="total-row"><span>IVA (10%)</span><span id="txt-iva">$0</span></div>
                            <div class="total-row" id="row-propina" style="display:none;">
                                <span style="color:var(--gold-dark);"><i class="fa-solid fa-hand-holding-dollar"></i> Propina (<span id="lbl-pct">0</span>%)</span>
                                <span id="txt-propina-footer">$0</span>
                            </div>
                            <div class="total-row gran-total"><span>TOTAL</span><span id="txt-total">$0</span></div>

                            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px;">
                                <button class="btn-black" style="width:100%;padding:10px;" onclick="imprimirPreCuenta()">
                                    <i class="fa-solid fa-print"></i>&nbsp; Imprimir Pre-Cuenta
                                </button>
                                <button class="btn-gold" id="btn-procesar" style="width:100%;padding:12px;font-weight:700;letter-spacing:1px;" onclick="procesarPago()">
                                    <i class="fa-solid fa-credit-card"></i>&nbsp; PROCESAR PAGO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="{{ asset('js/theme-toggle.js') }}"></script>
<script src="{{ asset('js/pages/caja.js') }}"></script>
</body>
</html>
