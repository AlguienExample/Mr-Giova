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
                <a href="#"><i class="fa-solid fa-border-all"></i> Control de Mesas</a>
            </li>
            <li class="admin-nav-item">
                <a href="#" onclick="abrirHistorial(event)"><i class="fa-solid fa-clock-rotate-left"></i> Historial de Pagos</a>
            </li>
            <li class="admin-nav-item">
                <a href="/admin"><i class="fa-solid fa-chart-line"></i> Panel Admin</a>
            </li>
            <li class="admin-nav-item">
                <a href="/admin"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes &amp; Cierres</a>
            </li>
        </ul>

        {{-- Cajero info --}}
        <div class="cajero-footer">
            <div class="cajero-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'C', 0, 2)) }}</div>
            <div class="cajero-info">
                <div class="cajero-name">{{ Auth::user()->name ?? 'Cajero' }}</div>
                <div class="cajero-sub">Caja 01 · Principal</div>
            </div>
        </div>

        <div class="admin-sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-black" style="display:flex; justify-content:center; align-items:center; gap:8px; width:100%; border:none; cursor:pointer; padding:12px;">
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
                        {{-- Zone tabs --}}
                        <div class="zona-tabs">
                            <div class="zona-tab active">Planta Principal</div>
                            <div class="zona-tab">Terraza Jardín</div>
                            <div class="zona-tab">Barra Alta</div>
                        </div>
                        <div style="flex:1;"></div>
                        <label class="toggle-pill active" id="pillFiltroJornada" onclick="toggleFiltroJornada(this)">
                            <i class="fa-solid fa-calendar-day"></i>
                            Solo Activas Hoy
                        </label>
                        <label class="toggle-pill" id="pillTodasMesas" onclick="toggleFiltroJornada(null)">
                            <i class="fa-solid fa-border-all"></i>
                            Ver Todas (<span id="count-ver-todas">{{ count($mesas) }}</span>)
                        </label>
                    </div>

                    <div class="mesas-grid" id="mesasGrid">
                        @foreach($mesas as $mesa)
                            @php
                                $claseEstado  = '';
                                $textoEstado  = 'Disponible';
                                $labelClass   = '';
                                $iconEstado   = '';
                                $esActiva     = in_array($mesa['estado'], ['Ocupada','Reservada']);
                                $tienePedidoHoy = $mesa['tiene_pedido_hoy'];

                                if ($mesa['estado'] === 'Ocupada') {
                                    $claseEstado = 'estado-ocupada';
                                    $textoEstado = 'Ocupada';
                                    $labelClass  = 'ocupada';
                                    $iconEstado  = '<i class="fa-solid fa-circle-dot" style="color:var(--gold-dark);margin-right:3px;"></i>';
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

                                {{-- Top: number + status --}}
                                <div class="mesa-card-top">
                                    <div class="mesa-num">{{ str_pad($mesa['numero_mesa'], 2, '0', STR_PAD_LEFT) }}</div>
                                    <div class="mesa-status-label {{ $labelClass }}">{{ $textoEstado }}</div>
                                </div>

                                {{-- Meta: pax + timer --}}
                                <div class="mesa-card-meta">
                                    <div class="pax">
                                        <i class="fa-solid fa-user"></i>
                                        {{ $mesa['capacidad'] }} pax
                                    </div>
                                    @if($mesa['minutos_ocupada'] !== null)
                                        <div class="timer">
                                            <i class="fa-regular fa-clock"></i>
                                            {{ $mesa['minutos_ocupada'] }}m
                                        </div>
                                    @endif
                                </div>

                                {{-- Amount or no-command --}}
                                @if($tienePedidoHoy && $mesa['total_pedido'] > 0)
                                    <div class="mesa-card-amount">
                                        <div class="label">Por cobrar</div>
                                        <div class="amount">$ {{ number_format($mesa['total_pedido'], 0, ',', '.') }}</div>
                                    </div>
                                @else
                                    <div class="no-cmd">Sin comanda</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if(count($paraLlevar) > 0)
                        <div class="content-title" style="margin:22px 0 12px;font-size:15px;">
                            <i class="fa-solid fa-bag-shopping"></i> Mostrador / Para llevar
                        </div>
                        <div class="mesas-grid" id="llevarGrid">
                            @foreach($paraLlevar as $pl)
                                <div class="mesa-card"
                                     data-pedido="{{ $pl['pedido_id'] }}"
                                     onclick="seleccionarPedido({{ $pl['pedido_id'] }})">
                                    <div class="mesa-card-top">
                                        <div class="mesa-num"><i class="fa-solid fa-bag-shopping" style="font-size:22px;"></i></div>
                                        <div class="mesa-status-label">{{ $pl['estado'] }}</div>
                                    </div>
                                    <div class="mesa-card-meta">
                                        <div class="pax">Pedido #{{ str_pad($pl['pedido_id'], 4, '0', STR_PAD_LEFT) }}</div>
                                        <div class="pax">{{ $pl['items'] }} ítems</div>
                                    </div>
                                    <div class="mesa-card-amount">
                                        <div class="label">Por cobrar</div>
                                        <div class="amount">$ {{ number_format($pl['total'], 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Panel de Ticket (POS) -->
                <div class="ticket-panel">
                    <div class="empty-ticket" id="panel-empty">
                        <i class="fa-solid fa-receipt" style="font-size:2.8rem;"></i>
                        <div class="empty-title">Sin selección</div>
                        <div class="empty-sub">Selecciona una mesa activa para emitir la pre-cuenta o cobrar</div>
                    </div>

                    <div id="panel-content" style="display:none;flex-direction:column;height:100%;overflow:hidden;">
                        <div class="ticket-header-brand">
                            <div class="ticket-logo-text"><i class="fa-solid fa-receipt"></i> Sabor a Pueblo POS</div>
                            <div class="ticket-subtext">COMPROBANTE DE COBRO</div>
                        </div>

                        <!-- Header -->
                        <div class="ticket-header">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">
                                <div id="pedido-titulo" style="font-family:var(--font-serif,Georgia,serif);font-size:20px;font-weight:700;">Mesa --</div>
                                <span class="badge badge-pendiente" id="pedido-estado-badge">EN COBRO</span>
                            </div>
                            <div id="pedido-info" style="font-size:11px;">Pedido #---</div>
                            <div id="alerta-permanencia" style="display:none;margin-top:8px;padding:6px 10px;border-radius:8px;font-size:11px;font-weight:600;">
                                <i class="fa-solid fa-triangle-exclamation"></i> <span id="alerta-permanencia-text"></span>
                            </div>
                        </div>

                        <div class="ticket-tabs">
                            <div class="ticket-tab active" id="tab-cuenta" onclick="switchTicketTab('cuenta')">
                                <i class="fa-solid fa-list-ul"></i> Cuenta
                            </div>
                            <div class="ticket-tab" id="tab-propina" onclick="switchTicketTab('propina')">
                                <i class="fa-solid fa-hand-holding-dollar"></i> Propina
                            </div>
                            <div class="ticket-tab" id="tab-dividir" onclick="switchTicketTab('dividir')">
                                <i class="fa-solid fa-users"></i> Dividir
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
                        <div class="ticket-footer">
                            <div class="total-row"><span>Subtotal</span><span id="txt-subtotal">$0</span></div>
                            <div class="total-row"><span>IVA / Impoconsumo (10%)</span><span id="txt-iva">$0</span></div>
                            <div class="total-row" id="row-propina" style="display:none;">
                                <span><i class="fa-solid fa-hand-holding-dollar" style="color:var(--pos-gold);"></i> Propina (<span id="lbl-pct">0</span>%)</span>
                                <span id="txt-propina-footer">$0</span>
                            </div>
                            <div class="total-row gran-total"><span>TOTAL A PAGAR</span><span id="txt-total">$0</span></div>

                            <!-- Método de pago y cambio -->
                            <div class="pago-section" id="pago-section">
                                <div class="pago-section-label">
                                    <i class="fa-solid fa-credit-card" style="color:var(--pos-gold);"></i> MÉTODO DE PAGO
                                </div>
                                <div class="metodo-pago-grid">
                                    <div class="metodo-btn active" data-metodo="Efectivo" onclick="selectMetodoPago('Efectivo')">
                                        <i class="fa-solid fa-money-bill-wave"></i>Efectivo
                                    </div>
                                    <div class="metodo-btn" data-metodo="Tarjeta" onclick="selectMetodoPago('Tarjeta')">
                                        <i class="fa-solid fa-credit-card"></i>Tarjeta
                                    </div>
                                    <div class="metodo-btn" data-metodo="Transferencia" onclick="selectMetodoPago('Transferencia')">
                                        <i class="fa-solid fa-building-columns"></i>Transferencia
                                    </div>
                                    <div class="metodo-btn" data-metodo="Billetera_Digital" onclick="selectMetodoPago('Billetera_Digital')">
                                        <i class="fa-solid fa-wallet"></i>Billetera Digital
                                    </div>
                                </div>
                                <div class="efectivo-box" id="efectivo-box">
                                    <div class="efectivo-header">
                                        <label>EFECTIVO RECIBIDO</label>
                                        <span class="teclado-badge">Teclado Activo</span>
                                    </div>
                                    <div class="efectivo-input-wrap">
                                        <span class="currency-sym">$</span>
                                        <input type="number" id="input-recibido" min="0" step="1000" placeholder="0" oninput="actualizarCambio(); marcarQuickBtn(this.value)">
                                    </div>
                                    <div class="quick-amounts">
                                        <div class="quick-btn" onclick="setMonto(0)">Exacto</div>
                                        <div class="quick-btn" id="qb-30k" onclick="setMonto(30000)">$30k</div>
                                        <div class="quick-btn" id="qb-50k" onclick="setMonto(50000)">$50k</div>
                                        <div class="quick-btn" id="qb-100k" onclick="setMonto(100000)">$100k</div>
                                    </div>
                                    <div class="cambio-row">
                                        <div class="cambio-row-left">
                                            CAMBIO A DEVOLVER
                                            <span>Entregar al cliente</span>
                                        </div>
                                        <strong id="txt-cambio">$0</strong>
                                    </div>
                                    <div class="aviso-falta" id="aviso-falta" style="display:none;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> El efectivo recibido es menor al total
                                    </div>
                                </div>
                            </div>

                            <button class="btn-cobrar" id="btn-procesar" onclick="procesarPago()">
                                <i class="fa-solid fa-check"></i> COBRAR E IMPRIMIR TICKET
                            </button>
                            <div class="btn-secundarios">
                                <button class="btn-sec" onclick="imprimirPreCuenta()">
                                    <i class="fa-solid fa-print"></i> Reimprimir
                                </button>
                                <button class="btn-sec" onclick="enviarWhatsApp()">
                                    <i class="fa-brands fa-whatsapp"></i> Enviar por WhatsApp
                                </button>
                            </div>
                        </div>{{-- /ticket-footer --}}
                    </div>{{-- /panel-content --}}
                </div>{{-- /ticket-panel --}}
            </div>
        </div>
    </main>
</div>

<!-- Modal Historial de Pagos -->
<div class="mrgiova-modal" id="modal-historial">
    <div class="modal-content" style="max-width:820px;">
        <div class="modal-header">
            <h3><i class="fa-solid fa-clock-rotate-left" style="margin-right:8px;"></i>Historial de Pagos</h3>
            <button class="modal-close-btn" onclick="cerrarHistorial()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="historial-resumen" id="historial-resumen">
                <div><span>Pagos hoy</span><strong id="hist-count-hoy">0</strong></div>
                <div><span>Total cobrado</span><strong id="hist-total-hoy">$0</strong></div>
                <div><span>Efectivo</span><strong id="hist-efectivo">$0</strong></div>
                <div><span>Otros medios</span><strong id="hist-otros">$0</strong></div>
            </div>
            <div style="overflow:auto;max-height:52vh;">
                <table class="historial-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Mesa</th>
                            <th>Pedido</th>
                            <th>Método</th>
                            <th style="text-align:right;">Total</th>
                            <th style="text-align:right;">Propina</th>
                            <th style="text-align:right;">Recibido</th>
                            <th style="text-align:right;">Cambio</th>
                            <th>Cajero</th>
                        </tr>
                    </thead>
                    <tbody id="historial-rows">
                        <tr><td colspan="9" style="text-align:center;padding:24px;color:var(--gray-400);">Cargando...</td></tr>
                    </tbody>
                </table>
                <div id="historial-vacio" style="display:none;text-align:center;padding:30px;color:var(--gray-400);font-size:12px;">
                    <i class="fa-regular fa-folder-open" style="font-size:1.8rem;margin-bottom:8px;display:block;opacity:0.5;"></i>
                    Sin pagos registrados
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/theme-toggle.js') }}"></script>
<script src="{{ asset('js/pages/caja.js') }}"></script>
</body>
</html>
