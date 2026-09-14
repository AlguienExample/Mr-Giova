'use strict';

// ─── Estado ────────────────────────────────────────────────────────────────
let currentPedidoId   = null;
let currentMesaNumero = null;
let totalBruto        = 0;   // total sin propina
let propinaPct        = 0;
let splitCount        = 2;
let soloJornada       = true; // filtro por defecto: solo mesas activas hoy
let metodoPago        = 'Efectivo';
let montoRecibido     = 0;

// ─── Helpers ───────────────────────────────────────────────────────────────
function escapeHTML(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

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

// ─── Selección de pedido directo (Mostrador / Para llevar) ─────────────────
async function seleccionarPedido(id) {
    document.querySelectorAll('.mesa-card').forEach(c => c.classList.remove('active'));
    const card = document.querySelector(`.mesa-card[data-pedido="${id}"]`);
    if (card) card.classList.add('active');

    currentMesaNumero = null;

    try {
        const res = await fetch(`/caja/pedido/${id}`);
        const data = await res.json();

        if (data.success) {
            mostrarPedido('Mostrador', data, true);
        } else {
            mostrarVacio('Mostrador');
        }
    } catch (err) {
        console.error(err);
        mostrarVacio('Mostrador');
    }
}

// ─── Render del pedido ─────────────────────────────────────────────────────
function fmt(amount) {
    return new Intl.NumberFormat('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 }).format(amount);
}

function mostrarPedido(numero, data, esMostrador = false) {
    currentPedidoId = data.pedido_id;
    totalBruto      = parseFloat(data.total) || 0;
    propinaPct      = 0;
    splitCount      = 2;

    document.getElementById('panel-empty').style.display = 'none';
    const content = document.getElementById('panel-content');
    content.style.display = 'flex';

    document.getElementById('pedido-titulo').textContent = esMostrador ? 'Mostrador' : `Mesa ${numero}`;
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
            const nombre = escapeHTML(item.nombre || 'Item');
            lista.innerHTML += `
                <div class="ticket-item">
                    <div style="flex:1;">
                        <div class="item-name"><span class="item-qty">${item.cantidad}×</span>${nombre}</div>
                        ${item.notas ? `<div class="item-sub">${escapeHTML(item.notas)}</div>` : ''}
                        <div class="item-sub">${fmt(item.precio_unitario)} c/u</div>
                    </div>
                    <div class="item-price">${fmt(item.subtotal)}</div>
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
    resetPagoUI();
}

function mostrarVacio(numero) {
    currentPedidoId = null;
    totalBruto      = 0;
    document.getElementById('panel-content').style.display = 'none';
    const empty = document.getElementById('panel-empty');
    empty.style.display = 'flex';
    empty.innerHTML = `
        <i class="fa-solid fa-mug-hot" style="font-size:2.8rem;"></i>
        <div class="empty-title">Mesa ${numero} — Sin pedido activo</div>
        <div class="empty-sub">No hay pedidos registrados en esta mesa hoy</div>`;
}

// ─── Cálculo de totales dinámicos ──────────────────────────────────────────
function actualizarTotales() {
    const subTotal   = totalBruto / 1.10;                              // total ya incluye IVA
    const iva        = totalBruto - subTotal;
    const propina    = totalBruto * (propinaPct / 100);
    const grandTotal = totalBruto + propina;

    document.getElementById('txt-subtotal').textContent       = fmt(subTotal);
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

    actualizarCambio();
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

// ─── Método de pago y cambio ───────────────────────────────────────────────
function totalAPagar() {
    return totalBruto + totalBruto * (propinaPct / 100);
}

function resetPagoUI() {
    metodoPago = 'Efectivo';
    montoRecibido = 0;
    document.querySelectorAll('.metodo-btn').forEach(b => b.classList.toggle('active', b.dataset.metodo === 'Efectivo'));
    const box = document.getElementById('efectivo-box');
    if (box) box.style.display = 'block';
    const input = document.getElementById('input-recibido');
    if (input) input.value = '';
    actualizarCambio();
}

function selectMetodoPago(metodo) {
    metodoPago = metodo;
    document.querySelectorAll('.metodo-btn').forEach(b => b.classList.toggle('active', b.dataset.metodo === metodo));
    document.getElementById('efectivo-box').style.display = metodo === 'Efectivo' ? 'block' : 'none';
    actualizarCambio();
}

function actualizarCambio() {
    montoRecibido = parseFloat(document.getElementById('input-recibido')?.value) || 0;
    const aPagar = totalAPagar();
    const aviso = document.getElementById('aviso-falta');
    const cambio = document.getElementById('txt-cambio');
    if (montoRecibido > 0 && montoRecibido < aPagar) {
        if (aviso) aviso.style.display = 'flex';
        if (cambio) cambio.textContent = '—';
    } else {
        if (aviso) aviso.style.display = 'none';
        if (cambio) cambio.textContent = fmt(Math.max(0, montoRecibido - aPagar));
    }
}

// ─── Quick amount helpers ──────────────────────────────────────────────────
function setMonto(monto) {
    const input = document.getElementById('input-recibido');
    if (!input) return;
    if (monto === 0) {
        // Exacto: pone el total exacto
        input.value = Math.ceil(totalAPagar());
    } else {
        input.value = monto;
    }
    actualizarCambio();
    marcarQuickBtn(input.value);
}

function marcarQuickBtn(val) {
    const v = parseFloat(val) || 0;
    document.querySelectorAll('.quick-btn').forEach(b => b.classList.remove('active'));
    if (v === 30000)  { const el = document.getElementById('qb-30k');  if (el) el.classList.add('active'); }
    if (v === 50000)  { const el = document.getElementById('qb-50k');  if (el) el.classList.add('active'); }
    if (v === 100000) { const el = document.getElementById('qb-100k'); if (el) el.classList.add('active'); }
}

// ─── WhatsApp ───────────────────────────────────────────────────────────────
function enviarWhatsApp() {
    if (!currentPedidoId) return;
    const total = fmt(totalAPagar());
    const mesa  = currentMesaNumero;
    const msg   = encodeURIComponent(`🍽️ *Sabor a Pueblo* — Mesa ${mesa}\nTotal a pagar: ${total}\nPedido #${String(currentPedidoId).padStart(4,'0')}`);
    window.open(`https://wa.me/?text=${msg}`, '_blank');
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

    const aPagar = totalAPagar();
    if (metodoPago === 'Efectivo') {
        montoRecibido = parseFloat(document.getElementById('input-recibido')?.value) || 0;
        if (montoRecibido < aPagar) {
            toastCaja('Efectivo insuficiente', 'El monto recibido es menor al total a pagar.', 'error');
            return;
        }
    }

    const btn = document.getElementById('btn-procesar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

    const propina = Math.round(totalBruto * (propinaPct / 100) * 100) / 100;
    const payload = { pedido_id: currentPedidoId, metodo_pago: metodoPago };
    if (propina > 0) payload.propina = propina;
    if (metodoPago === 'Efectivo') payload.monto_recibido = montoRecibido;

    fetch('/caja/pagos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let msg = currentMesaNumero
                ? `Mesa ${currentMesaNumero} liberada correctamente.`
                : `Pedido #${String(data.pedido_id).padStart(4, '0')} cobrado correctamente.`;
            if (data.metodo === 'Efectivo' && data.cambio > 0) {
                msg = `Cambio a entregar: ${fmt(data.cambio)}`;
            }
            toastCaja('✓ Pago procesado', msg, 'success');
            setTimeout(() => window.location.reload(), 2200);
        } else {
            toastCaja('Error', data.message || 'No se pudo procesar el pago.', 'error');
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

// ─── Historial de pagos ────────────────────────────────────────────────────
function abrirHistorial(e) {
    if (e) e.preventDefault();
    document.getElementById('modal-historial').classList.add('open');
    cargarHistorial();
}

function cerrarHistorial() {
    document.getElementById('modal-historial').classList.remove('open');
}

function cargarHistorial() {
    const filaCarga = '<tr><td colspan="9" style="text-align:center;padding:24px;color:var(--gray-400);">Cargando...</td></tr>';
    const tbody = document.getElementById('historial-rows');
    tbody.innerHTML = filaCarga;

    fetch('/caja/pagos')
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error('Error en la respuesta');
            const pagos = data.pagos || [];
            document.getElementById('historial-vacio').style.display = pagos.length ? 'none' : 'block';

            tbody.innerHTML = pagos.map(p => {
                const metodo = (p.metodo_pago || '').replace('_', ' ');
                const tieneEfectivo = p.monto_recibido !== null;
                return `<tr>
                    <td>${escapeHTML(p.fecha)}</td>
                    <td>${p.mesa ?? '—'}</td>
                    <td>#${p.pedido_id}</td>
                    <td><span class="metodo-chip">${escapeHTML(metodo)}</span></td>
                    <td style="text-align:right;">${fmt(p.total_final)}</td>
                    <td style="text-align:right;">${p.propina > 0 ? fmt(p.propina) : '—'}</td>
                    <td style="text-align:right;">${tieneEfectivo ? fmt(p.monto_recibido) : '—'}</td>
                    <td style="text-align:right;">${tieneEfectivo ? fmt(p.cambio) : '—'}</td>
                    <td>${escapeHTML(p.cajero)}</td>
                </tr>`;
            }).join('');

            resumirHistorial(pagos);
        })
        .catch(() => {
            document.getElementById('historial-vacio').style.display = 'none';
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:24px;color:var(--gray-400);">Error al cargar el historial.</td></tr>';
        });
}

function resumirHistorial(pagos) {
    const hoy = new Date();
    const hoyStr = `${String(hoy.getDate()).padStart(2,'0')}/${String(hoy.getMonth()+1).padStart(2,'0')}/${hoy.getFullYear()}`;

    const deHoy = pagos.filter(p => (p.fecha || '').startsWith(hoyStr));
    const total = deHoy.reduce((s, p) => s + (p.total_final || 0), 0);
    const efectivo = deHoy
        .filter(p => p.metodo_pago === 'Efectivo')
        .reduce((s, p) => s + (p.monto_recibido ?? 0), 0);
    const otros = deHoy
        .filter(p => p.metodo_pago !== 'Efectivo')
        .reduce((s, p) => s + (p.total_final || 0), 0);

    document.getElementById('hist-count-hoy').textContent = deHoy.length;
    document.getElementById('hist-total-hoy').textContent = fmt(total);
    document.getElementById('hist-efectivo').textContent = fmt(efectivo);
    document.getElementById('hist-otros').textContent = fmt(otros);
}