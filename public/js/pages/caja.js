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