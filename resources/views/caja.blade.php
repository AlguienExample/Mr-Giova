<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo — Terminal de Caja & POS</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* ── Layout ─────────────────────────────────────── */
        .caja-grid {
            display: grid;
            grid-template-columns: 1fr 370px;
            gap: 24px;
            height: calc(100vh - 130px);
            overflow: hidden;
        }
        .mesas-area { overflow-y: auto; }

        /* ── Toolbar de filtros ──────────────────────────── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .toggle-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gray-100);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }
        .toggle-pill.active {
            background: var(--black);
            color: var(--white);
            border-color: var(--black);
        }
        .toggle-pill input[type="checkbox"] { display: none; }

        /* ── Grid de Mesas ───────────────────────────────── */
        .mesas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(138px, 1fr));
            gap: 14px;
        }
        .mesa-card {
            border: 1px solid var(--border);
            background: var(--white);
            padding: 18px 14px 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-md);
        }
        .mesa-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--gray-300);
            transition: background 0.25s;
        }
        .mesa-card.estado-ocupada::before  { background: var(--gold); }
        .mesa-card.estado-cuenta::before   { background: #D32F2F; }
        .mesa-card.estado-reservada::before{ background: #7B5EA7; }

        .mesa-card:hover  { border-color: var(--gold); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .mesa-card.active { border-color: var(--gold); box-shadow: 0 0 0 2px var(--gold); }
        .mesa-card.hidden-by-filter { display: none; }

        .mesa-num {
            font-family: var(--font-serif);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .mesa-status-label {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gray-400);
        }
        .mesa-status-label.ocupada  { color: var(--gold-dark); }
        .mesa-status-label.cuenta   { color: #D32F2F; }
        .mesa-status-label.reservada{ color: #7B5EA7; }

        /* ── Alerta de permanencia ───────────────────────── */
        .alerta-tiempo {
            position: absolute;
            top: 6px;
            right: 6px;
            font-size: 9px;
            background: #FFF3CD;
            color: #856404;
            border: 1px solid #FFECB5;
            border-radius: 20px;
            padding: 2px 6px;
            font-weight: 700;
        }
        .alerta-tiempo.critica {
            background: #FDEDED;
            color: #D32F2F;
            border-color: #FFCDD2;
            animation: pulse-badge 1.5s infinite;
        }
        @keyframes pulse-badge {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.5; }
        }

        /* ── Panel de Ticket ─────────────────────────────── */
        .ticket-panel {
            border: 1px solid var(--border);
            background: var(--white);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            border-radius: var(--radius-md);
        }
        .ticket-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--gray-100);
        }
        .ticket-body   { flex: 1; overflow-y: auto; padding: 16px 20px; }
        .ticket-footer { padding: 16px 20px; border-top: 1px solid var(--border); background: var(--gray-100); }

        .ticket-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px dashed var(--border);
        }
        .ticket-item:last-child { border-bottom: none; }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 12px;
            color: var(--gray-400);
        }
        .total-row.gran-total {
            font-size: 17px;
            font-weight: 700;
            color: var(--black);
            border-top: 1px solid var(--border);
            padding-top: 10px;
            margin-top: 6px;
        }
        .empty-ticket {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray-400);
            text-align: center;
            padding: 24px;
        }

        /* ── Propinas ────────────────────────────────────── */
        .tip-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 10px;
        }
        .tip-btn {
            background: var(--gray-100);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 7px 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .tip-btn:hover, .tip-btn.active {
            background: var(--black);
            color: var(--white);
            border-color: var(--black);
        }

        /* ── División de cuenta ─────────────────────────── */
        .split-panel {
            background: var(--gray-50);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
        }
        .split-panel label { font-size: 11px; color: var(--gray-600); font-weight: 600; display: block; margin-bottom: 6px; }
        .split-controls { display: flex; align-items: center; gap: 8px; }
        .split-num-btn {
            width: 28px; height: 28px;
            border: 1px solid var(--border);
            background: var(--white);
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .split-num-btn:hover { background: var(--black); color: var(--white); border-color: var(--black); }
        #split-count {
            font-family: var(--font-serif);
            font-size: 20px;
            font-weight: 700;
            width: 36px;
            text-align: center;
        }
        #split-result {
            font-size: 12px;
            color: var(--gold-dark);
            font-weight: 700;
            margin-top: 6px;
        }

        /* ── Tabs del panel de ticket ─────────────────────── */
        .ticket-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }
        .ticket-tab {
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 4px;
            color: var(--gray-400);
            transition: all 0.2s;
        }
        .ticket-tab.active {
            background: var(--black);
            color: var(--white);
        }
    </style>
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

<script>
'use strict';

// ─── Estado ────────────────────────────────────────────────────────────────
let currentPedidoId   = null;
let currentMesaNumero = null;
let totalBruto        = 0;   // total sin propina
let propinaPct        = 0;
let splitCount        = 2;
let soloJornada       = true; // filtro por defecto: solo mesas activas hoy

// ─── Toast helper ──────────────────────────────────────────────────────────
function toastCaja(title, msg, type = 'success') {
    const icons = { success:'fa-circle-check', error:'fa-circle-xmark', warning:'fa-triangle-exclamation', info:'fa-circle-info' };
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.setAttribute('role', 'alert');
    t.innerHTML = `
        <div class="toast-icon"><i class="fa-solid ${icons[type]||icons.info}"></i></div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            ${msg ? `<div class="toast-message">${msg}</div>` : ''}
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>`;
    document.getElementById('toastContainerCaja').appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, 5000);
}

// ─── Filtro Jornada ────────────────────────────────────────────────────────
function toggleFiltroJornada(pillEl) {
    soloJornada = (pillEl && pillEl.id === 'pillFiltroJornada');

    document.getElementById('pillFiltroJornada').classList.toggle('active',  soloJornada);
    document.getElementById('pillTodasMesas').classList.toggle('active',    !soloJornada);

    document.querySelectorAll('.mesa-card').forEach(card => {
        const activa      = card.dataset.activa === '1';
        const tienePedido = card.dataset.tienePedido === '1';
        if (soloJornada) {
            card.classList.toggle('hidden-by-filter', !tienePedido && !activa);
        } else {
            card.classList.remove('hidden-by-filter');
        }
    });
}

// ─── Selección de mesa ─────────────────────────────────────────────────────
async function seleccionarMesa(id, numero) {
    document.querySelectorAll('.mesa-card').forEach(c => c.classList.remove('active'));
    const card = document.querySelector(`.mesa-card[data-id="${id}"]`);
    if (card) card.classList.add('active');

    currentMesaNumero = parseInt(numero);

    try {
        const res = await fetch(`/caja/mesa/${id}/pedido`);
        const data = await res.json();

        if (data.success) {
            mostrarPedido(numero, data);
        } else {
            mostrarVacio(numero);
        }
    } catch (err) {
        console.error(err);
        mostrarVacio(numero);
    }
}

// ─── Render del pedido ─────────────────────────────────────────────────────
function fmt(amount) {
    return new Intl.NumberFormat('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 }).format(amount);
}

function mostrarPedido(numero, data) {
    currentPedidoId = data.pedido_id;
    totalBruto      = parseFloat(data.total) || 0;
    propinaPct      = 0;
    splitCount      = 2;

    document.getElementById('panel-empty').style.display = 'none';
    const content = document.getElementById('panel-content');
    content.style.display = 'flex';

    document.getElementById('pedido-titulo').textContent = `Mesa ${numero}`;
    document.getElementById('pedido-info').textContent   = `Pedido #${String(data.pedido_id).padStart(4,'0')} · ${data.estado}`;

    const badge = document.getElementById('pedido-estado-badge');
    badge.textContent = data.estado.toUpperCase();
    badge.className   = 'badge ' + (data.estado === 'Listo' ? 'badge-optimo' : 'badge-pendiente');

    // Alerta de permanencia
    calcularAlertaPermanencia(data.created_at);

    // Items
    const lista = document.getElementById('lista-items');
    lista.innerHTML = '';
    if (data.items && data.items.length > 0) {
        data.items.forEach(item => {
            const nombre = item.nombre || 'Item';
            lista.innerHTML += `
                <div class="ticket-item">
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:600;">${item.cantidad}× ${nombre}</div>
                        ${item.notas ? `<div style="font-size:10px;color:var(--gray-400);margin-top:2px;font-style:italic;">${item.notas}</div>` : ''}
                        <div style="font-size:10px;color:var(--gray-400);margin-top:2px;">${fmt(item.precio_unitario)} c/u</div>
                    </div>
                    <div style="font-weight:600;font-size:13px;">${fmt(item.subtotal)}</div>
                </div>`;
        });
    } else {
        lista.innerHTML = `<div style="text-align:center;padding:30px;color:var(--gray-400);font-size:12px;">Sin ítems registrados</div>`;
    }

    actualizarTotales();
    resetTipButtons();
    document.getElementById('split-count').textContent = '2';
    document.getElementById('split-result').textContent = 'Cada persona paga: —';
    switchTicketTab('cuenta');
}

function mostrarVacio(numero) {
    currentPedidoId = null;
    totalBruto      = 0;
    document.getElementById('panel-content').style.display = 'none';
    const empty = document.getElementById('panel-empty');
    empty.style.display = 'flex';
    empty.innerHTML = `
        <i class="fa-solid fa-mug-hot" style="font-size:2.5rem;margin-bottom:15px;opacity:0.25;"></i>
        <div style="font-family:var(--font-serif);font-size:18px;margin-bottom:8px;color:var(--gray-600);">Mesa ${numero} — Sin pedido activo</div>
        <div style="font-size:12px;">No hay pedidos registrados en esta mesa hoy</div>`;
}

// ─── Cálculo de totales dinámicos ──────────────────────────────────────────
function actualizarTotales() {
    const iva       = totalBruto * 0.10;
    const sub       = totalBruto - iva;
    const propina   = totalBruto * (propinaPct / 100);
    const grandTotal= totalBruto + propina;

    document.getElementById('txt-subtotal').textContent       = fmt(sub);
    document.getElementById('txt-iva').textContent            = fmt(iva);
    document.getElementById('txt-total').textContent          = fmt(grandTotal);
    document.getElementById('txt-propina-valor').textContent  = fmt(propina);
    document.getElementById('txt-total-con-propina').textContent = fmt(grandTotal);
    document.getElementById('txt-propina-footer').textContent = fmt(propina);
    document.getElementById('lbl-pct').textContent            = propinaPct;

    document.getElementById('row-propina').style.display = propinaPct > 0 ? 'flex' : 'none';

    // Actualizar división
    const total = grandTotal;
    if (splitCount >= 1 && total > 0) {
        document.getElementById('split-result').textContent =
            `Cada persona paga: ${fmt(total / splitCount)}`;
    }
}

// ─── Propinas ──────────────────────────────────────────────────────────────
function resetTipButtons() {
    document.querySelectorAll('.tip-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tip-custom').value = '';
}

function selectTip(pct) {
    propinaPct = pct;
    resetTipButtons();
    // Marcar botón activo
    document.querySelectorAll('.tip-btn').forEach(b => {
        const label = b.textContent.trim();
        if ((pct === 0 && label === 'Sin propina') || label === pct + '%') {
            b.classList.add('active');
        }
    });
    actualizarTotales();
}

// ─── División de cuenta ────────────────────────────────────────────────────
function changeSplit(delta) {
    splitCount = Math.max(1, Math.min(20, splitCount + delta));
    document.getElementById('split-count').textContent = splitCount;
    actualizarTotales();
}

// ─── Tabs del ticket ──────────────────────────────────────────────────────
function switchTicketTab(tab) {
    ['cuenta', 'propina', 'dividir'].forEach(t => {
        document.getElementById(`tab-content-${t}`).style.display = t === tab ? 'block' : 'none';
        document.getElementById(`tab-${t}`).classList.toggle('active', t === tab);
    });
}

// ─── Alerta de permanencia ─────────────────────────────────────────────────
function calcularAlertaPermanencia(createdAt) {
    const alertDiv  = document.getElementById('alerta-permanencia');
    const alertText = document.getElementById('alerta-permanencia-text');
    if (!createdAt) { alertDiv.style.display = 'none'; return; }

    const mins = Math.floor((Date.now() - new Date(createdAt)) / 60000);
    if (mins >= 90) {
        alertDiv.style.display = 'block';
        alertDiv.style.background = mins >= 120 ? '#FDEDED' : '#FFF3CD';
        alertDiv.style.borderColor = mins >= 120 ? '#FFCDD2' : '#FFECB5';
        alertDiv.style.color       = mins >= 120 ? '#D32F2F' : '#856404';
        alertText.textContent = `Mesa ocupada hace ${mins} minutos${mins >= 120 ? ' — ¡Atención urgente!' : ''}`;
    } else {
        alertDiv.style.display = 'none';
    }
}

// ─── Imprimir ──────────────────────────────────────────────────────────────
function imprimirPreCuenta() {
    if (!currentPedidoId) return;
    window.print();
}

// ─── Procesar Pago ─────────────────────────────────────────────────────────
function procesarPago() {
    if (!currentPedidoId) return;
    const btn = document.getElementById('btn-procesar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

    fetch(`/api/pedidos/${currentPedidoId}/estado`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ estado: 'Entregado' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toastCaja('✓ Pago procesado', `Mesa ${currentMesaNumero} liberada correctamente.`, 'success');
            setTimeout(() => window.location.reload(), 1800);
        } else {
            toastCaja('Error', 'No se pudo procesar el pago.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-credit-card"></i> PROCESAR PAGO';
        }
    })
    .catch(() => {
        toastCaja('Error', 'Error de conexión al procesar el pago.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-credit-card"></i> PROCESAR PAGO';
    });
}
</script>
</body>
</html>
