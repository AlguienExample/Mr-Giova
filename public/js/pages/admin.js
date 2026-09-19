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

/** Headers estándar para peticiones JSON (Accept obliga respuestas JSON, no HTML) */
function jsonHeaders() {
    return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() };
}

/** Wrapper para peticiones fetch con cabeceras JSON por defecto */
async function apiFetch(url, options = {}) {
    const headers = jsonHeaders();
    if (options.headers) {
        Object.assign(headers, options.headers);
    }
    return fetch(url, { ...options, headers });
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
    inventario: { title: 'Control de Insumos',    sub: 'Inventario de ingredientes y materias primas.',           fn: () => { fetchInsumos(); fetchHistorialReposiciones(); } },
    productos:  { title: 'Productos del Menú',    sub: 'Gestión del catálogo de platos del restaurante.',         fn: fetchProductos },
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

    // Polling con protecciones: no solapar peticiones, pausar en pestaña oculta
    // y backoff de 30s si el servidor falla en bucle.
    let statsEnCurso = false;
    let statsBackoffHasta = 0;
    setInterval(() => {
        if (document.hidden || statsEnCurso || Date.now() < statsBackoffHasta) return;
        statsEnCurso = true;
        Promise.resolve()
            .then(() => fetchStats())
            .catch(() => { statsBackoffHasta = Date.now() + 30000; })
            .finally(() => { statsEnCurso = false; });
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
    const d = new Date();
    const fechaHoyLocal = new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
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
                    grid: { color: chartGridColor() },
                    ticks: { callback: v => '$' + (v / 1000) + 'k', font: { family: 'Outfit' }, color: chartTickColor() }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Outfit', weight: '500' }, color: chartTickColor() }
                }
            }
        }
    });
}

/** Color de rejilla/etiquetas del grafico segun el tema actual (sin reload). */
function isLightTheme() {
    try {
        if (document.body && document.body.classList.contains('light-mode')) return true;
        if (document.documentElement && document.documentElement.classList.contains('light-mode')) return true;
        return (localStorage.getItem('sabor-theme') || 'dark') === 'light';
    } catch (e) {
        return false;
    }
}
function chartGridColor() {
    return isLightTheme() ? 'rgba(26,29,43,0.08)' : 'rgba(255,255,255,0.08)';
}
function chartTickColor() {
    return isLightTheme() ? '#5B6478' : '#94A3B8';
}

// Repintar el grafico al cambiar de tema SIN recargar la pagina.
window.addEventListener('sabor-theme-change', function (e) {
    try {
        if (!chartInstance) return;
        var isLight = e && e.detail && e.detail.theme === 'light';
        var grid = isLight ? 'rgba(26,29,43,0.08)' : 'rgba(255,255,255,0.08)';
        var tick = isLight ? '#5B6478' : '#94A3B8';
        if (chartInstance.options && chartInstance.options.scales) {
            if (chartInstance.options.scales.y) {
                if (chartInstance.options.scales.y.grid) chartInstance.options.scales.y.grid.color = grid;
                if (chartInstance.options.scales.y.ticks) chartInstance.options.scales.y.ticks.color = tick;
            }
            if (chartInstance.options.scales.x && chartInstance.options.scales.x.ticks) {
                chartInstance.options.scales.x.ticks.color = tick;
            }
        }
        chartInstance.update();
    } catch (err) { /* grafico opcional */ }
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. RESERVAS
// ─────────────────────────────────────────────────────────────────────────────

function fetchReservas() {
    const fechaFilter = document.getElementById('filtroFechaReservas');
    let dateVal = fechaFilter.value;

    // Si no hay fecha, usar HOY del dispositivo del administrador
    if (!dateVal) {
        const d = new Date();
        dateVal = new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
        fechaFilter.value = dateVal;
    }

    const url = `/api/admin/reservas?fecha=${dateVal}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('reservasList');
            window._reservasCache = Array.isArray(data) ? data : [];

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
                const noteHtml  = r.notas  ? `<div class="res-note">"${escHtml(r.notas)}"</div>` : '';
                const mesaNum   = r.mesa_numero ? r.mesa_numero.toString().padStart(2, '0') : '--';

                let stBadge;
                if (r.estado === 'Confirmada')  stBadge = `<span class="badge badge-optimo">✓ Confirmada</span>`;
                else if (r.estado === 'Pendiente') stBadge = `<span class="badge badge-pendiente">Pendiente</span>`;
                else if (r.estado === 'Cancelada') stBadge = `<span class="badge badge-critico">Cancelada</span>`;
                else stBadge = `<span class="badge badge-neutral">${escHtml(r.estado)}</span>`;

                // Extraer nombre del cliente desde las notas
                let clienteDisplay = r.cliente_nombre || '';
                const matchNota = (r.notas || '').match(/Cliente:\s*([^—\n]+)/i);
                if (matchNota) clienteDisplay = matchNota[1].trim();

                container.innerHTML += `
                    <div class="reservation-card">
                        <div class="res-time">
                            ${escHtml(r.hora)}
                            <small>Mesa ${escHtml(mesaNum)}</small>
                        </div>
                        <div class="res-details" style="flex:1;">
                            <div class="res-name">${escHtml(clienteDisplay)} ${vipBadge}</div>
                            <div class="res-meta">
                                <span><i class="fa-solid fa-user-group"></i> ${Number(r.num_personas) || 0} Comensales</span>
                                ${stBadge}
                            </div>
                            ${noteHtml}
                        </div>
                        <div style="display:flex; flex-direction:column; gap:6px; margin-left:12px;">
                            <button class="btn-outline" style="padding:5px 10px; font-size:11px;" onclick="abrirEditarReservaPorId(${r.id})" title="Editar reserva">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn-danger" style="padding:5px 10px; font-size:11px;" onclick="abrirEliminarReserva(${r.id})" title="Eliminar reserva">
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
                // Usar la posición guardada por arrastre; si no hay, fallback por índice.
                const tienePos = m.pos_x !== null && m.pos_x !== undefined
                    && m.pos_y !== null && m.pos_y !== undefined;
                const pos = tienePos
                    ? { x: parseFloat(m.pos_x), y: parseFloat(m.pos_y) }
                    : (positions[idx] || { x: 50, y: 50 });
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
                    ? `<span style="font-size:8px; background:rgba(255,255,255,0.25); padding:1px 3px; border-radius:3px; max-width:60px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="Mesero: ${escHtml(m.empleado_nombre)}">${escHtml(m.empleado_nombre.split(' ')[0])}</span>`
                    : '';

                div.innerHTML = `
                    <div style="display:flex; justify-content:space-between; width:100%; font-size:8px; opacity:0.8;">
                        <span>${escHtml(m.zona)}</span>
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
                // Solo un guardado en vuelo por mesa y con manejo de error visible.
                if (x && y && !mesaDiv.hasAttribute('data-saving')) {
                    mesaDiv.setAttribute('data-saving', '1');
                    fetch(`/api/admin/mesas/${m.id}/coordenadas`, {
                        method: 'PUT',
                        headers: jsonHeaders(),
                        body: JSON.stringify({ x: parseFloat(x), y: parseFloat(y) }),
                    })
                    .then(r => { if (!r.ok) throw new Error('save-failed'); })
                    .catch(() => showToast('Error', 'No se pudo guardar la posición de la mesa.', 'error'))
                    .finally(() => mesaDiv.removeAttribute('data-saving'));
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

    // Contexto para el modal de comanda
    window._comandaMesa = { num, estado };
    const comandaLabel = document.getElementById('comandaMesaNum');
    if (comandaLabel) comandaLabel.textContent = num.toString().padStart(2, '0');

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
    fetch('/api/admin/staff', { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            const grid = document.getElementById('staffGrid');
            grid.innerHTML = '';
            window._staffCache = Array.isArray(data) ? data : [];
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
                        <div class="staff-avatar"><img src="${img}" alt="${escHtml(e.cargo || 'Staff')}"></div>
                        <div class="staff-name">${escHtml(e.nombre || 'Sin nombre')}</div>
                        <div class="staff-role">${escHtml(e.cargo || 'Sin cargo')}</div>
                        <div style="margin-top:8px;">
                            <span class="badge ${e.activo ? 'badge-optimo' : 'badge-critico'}">${e.activo ? 'Activo' : 'Inactivo'}</span>
                        </div>
                        <div style="display:flex;gap:6px;margin-top:10px;">
                            <button class="btn-outline" style="flex:1;padding:6px 8px;font-size:11px;" onclick="openEditStaff(${e.id})">
                                <i class="fa-solid fa-pen"></i> Editar
                            </button>
                            <button class="btn-danger" style="padding:6px 10px;font-size:11px;" onclick="openDeleteStaff(${e.id})" title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
        })
        .catch(() => showToast('Error', 'No se pudo cargar el personal.', 'error'));
}

function submitStaff(e) {
    e.preventDefault();
    const btn = e.submitter;
    if (btn) btn.disabled = true;
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
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'))
    .finally(() => { if (btn) btn.disabled = false; });
}

/** Abre el modal de edición con los datos del caché */
function openEditStaff(id) {
    const e = (window._staffCache || []).find(x => x.id === id);
    if (!e) {
        showToast('Error', 'No se encontró el empleado.', 'error');
        return;
    }
    document.getElementById('editStaffId').value = e.id;
    document.getElementById('editStaffNombre').textContent = e.nombre || '';
    document.getElementById('editStaffCargo').value = e.cargo || '';
    document.getElementById('editStaffActivo').value = e.activo ? '1' : '0';
    openModal('modalStaffEdit');
}

function submitEditStaff(ev) {
    ev.preventDefault();
    const btn = ev.submitter;
    if (btn) btn.disabled = true;
    const id = document.getElementById('editStaffId').value;
    const payload = {
        cargo:  document.getElementById('editStaffCargo').value,
        activo: document.getElementById('editStaffActivo').value === '1',
    };

    fetch(`/api/admin/staff/${id}`, {
        method: 'PUT',
        headers: jsonHeaders(),
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✓ Empleado actualizado', data.message || '', 'success');
            closeModal('modalStaffEdit');
            fetchStaff();
        } else {
            showToast('Error', data.error || 'No se pudo actualizar.', 'error');
        }
    })
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'))
    .finally(() => { if (btn) btn.disabled = false; });
}

/** Abre la confirmación de eliminación */
function openDeleteStaff(id) {
    const e = (window._staffCache || []).find(x => x.id === id) || {};
    document.getElementById('eliminarStaffId').value = id;
    document.getElementById('eliminarStaffNombre').textContent = e.nombre || '';
    openModal('modalEliminarStaff');
}

function confirmarEliminarStaff() {
    const id = document.getElementById('eliminarStaffId').value;
    if (!id) return;
    const btn = document.getElementById('btnConfirmarEliminarStaff');
    if (btn) btn.disabled = true;

    fetch(`/api/admin/staff/${id}`, {
        method: 'DELETE',
        headers: jsonHeaders(),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✓ Empleado eliminado', data.message || '', 'success');
            closeModal('modalEliminarStaff');
            fetchStaff();
        } else {
            showToast('Error', data.error || 'No se pudo eliminar.', 'error');
        }
    })
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'))
    .finally(() => { if (btn) btn.disabled = false; });
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
            document.getElementById('inv-disponibilidad').textContent = data.kpis.disponibilidad + '%';
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
                    <img src="${img}" class="inv-img" alt="${escHtml(i.nombre)}">
                    <div>
                        <strong style="display:block; font-size:13px;">${escHtml(i.nombre)}</strong>
                        <span style="font-size:11px; color:var(--gray-400);">Mínimo: ${i.stock_minimo} ${i.unidad}</span>
                    </div>
                </div>
            </td>
            <td><span class="badge badge-neutral" style="font-weight:600;">${escHtml(i.categoria.toUpperCase())}</span></td>
            <td>${stockStr}</td>
            <td>${precioStr}${calcExtra}</td>
            <td>
                <span class="badge ${badgeClass}">
                    <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;margin-right:4px;"></span>
                    ${i.estado}
                </span>
            </td>
            <td>
                <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <button class="btn-outline" onclick="openModalEditarInsumo(${i.id})" title="Editar insumo">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn-danger" onclick="eliminarMateriaPrima(${i.id})" title="Eliminar insumo">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
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
    openNuevoInsumo();
    document.getElementById('mpCosto').value    = precio;
    document.getElementById('mpCategoria').value = 'Carnes';
    checkCategoriaMP();
}

// ── CRUD de MateriaPrima ──

/** Abre el modal en modo "crear" con el formulario limpio */
function openNuevoInsumo() {
    document.getElementById('formMateriaPrima').reset();
    document.getElementById('mpId').value = '';
    document.getElementById('mpCalculadoraPorcion').style.display = 'none';
    document.getElementById('modalMateriaPrimaTitulo').innerHTML =
        '<i class="fa-solid fa-boxes-stacked" style="color:var(--gold-dark); margin-right:8px;"></i> Nuevo Insumo';
    document.getElementById('btnSubmitMateriaPrima').innerHTML =
        '<i class="fa-solid fa-floppy-disk"></i> Guardar Insumo';
    openModal('modalMateriaPrima');
}

/** Abre el modal en modo "editar" con los datos del insumo cargados */
function openModalEditarInsumo(id) {
    const item = _inventoryAll.find(x => x.id === id);
    if (!item) { showToast('Error', 'Insumo no encontrado.', 'error'); return; }

    document.getElementById('formMateriaPrima').reset();
    document.getElementById('mpId').value          = item.id;
    document.getElementById('mpNombre').value      = item.nombre;
    document.getElementById('mpCategoria').value   = item.categoria;
    document.getElementById('mpUnidad').value      = item.unidad;
    document.getElementById('mpCantidad').value    = item.stock;
    document.getElementById('mpStockMinimo').value = item.stock_minimo;
    document.getElementById('mpCosto').value       = item.precio;

    document.querySelectorAll('#formMateriaPrima .form-control').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('#formMateriaPrima .form-error-text.visible').forEach(el => el.classList.remove('visible'));

    checkCategoriaMP();
    document.getElementById('modalMateriaPrimaTitulo').innerHTML =
        `<i class="fa-solid fa-boxes-stacked" style="color:var(--gold-dark); margin-right:8px;"></i> Editar Insumo — ${escHtml(item.nombre)}`;
    document.getElementById('btnSubmitMateriaPrima').innerHTML =
        '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios';
    openModal('modalMateriaPrima');
}

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
    const editId = document.getElementById('mpId').value;
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

    fetch(editId ? `/api/admin/insumos/${editId}` : '/api/admin/insumos', {
        method: editId ? 'PUT' : 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(editId ? 'Insumo actualizado' : 'Insumo guardado',
                editId ? `"${payload.nombre}" fue actualizado correctamente.` : `"${payload.nombre}" fue añadido al inventario.`,
                'success');
            closeModal('modalMateriaPrima');
            document.getElementById('formMateriaPrima').reset();
            document.getElementById('mpId').value = '';
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
        btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> ${document.getElementById('mpId').value ? 'Guardar Cambios' : 'Guardar Insumo'}`;
    });
}

function eliminarMateriaPrima(id) {
    const item  = _inventoryAll.find(x => x.id === id);
    const nombre = item ? item.nombre : `#${id}`;
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

/** Abre el modal de reposición con el campo de PIN limpio */
function openReposicionModal() {
    document.getElementById('formReposicion').reset();
    openModal('modalReposicion');
}

function submitReposicion(e) {
    e.preventDefault();
    const btn = e.submitter;
    if (btn) btn.disabled = true;
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
            // Refrescar historial e inventario para reflejar el nuevo pedido
            fetchHistorialReposiciones();
            fetchInsumos();
        } else {
            showToast('Error', data.error || 'PIN inválido o error en el servidor.', 'error');
        }
    })
    .catch(() => showToast('Error', 'Error de conexión.', 'error'))
    .finally(() => { if (btn) btn.disabled = false; });
}

// ─────────────────────────────────────────────────────────────────────────────
// HISTORIAL DE REPOSICIONES (paginación 100% client-side)
// ─────────────────────────────────────────────────────────────────────────────

let _reposicionAll      = [];  // Lista completa desde API
let _reposicionFiltered = [];  // Después del filtro de estado
let _repPage     = 1;
let _repPageSize = 10;

/** Carga todos los pedidos de reposición desde la API */
function fetchHistorialReposiciones() {
    fetch('/api/admin/insumos/pedidos')
        .then(res => res.json())
        .then(data => {
            _reposicionAll = data.data || [];
            filterAndRenderReposiciones();
        })
        .catch(() => showToast('Error', 'No se pudo cargar el historial de reposiciones.', 'error'));
}

/** Filtra por estado y renderiza — misma lógica que filterAndRenderInventory */
function filterAndRenderReposiciones() {
    const estado = (document.getElementById('repEstadoFilter')?.value || '');

    _reposicionFiltered = _reposicionAll.filter(p => !estado || p.estado === estado);

    _repPage = 1;
    renderReposicionPage();
}

/** Renderiza la página actual de la tabla de reposiciones */
function renderReposicionPage() {
    const tbody    = document.getElementById('repTableBody');
    const total    = _reposicionFiltered.length;
    const start    = (_repPage - 1) * _repPageSize;
    const pageData = _reposicionFiltered.slice(start, start + _repPageSize);

    tbody.innerHTML = '';

    if (total === 0) {
        tbody.innerHTML = `
            <tr><td colspan="6" class="table-empty">
                <i class="fa-solid fa-truck"></i>
                No hay pedidos de reposición con esos filtros.
            </td></tr>`;
        renderReposicionPagination(0);
        return;
    }

    pageData.forEach(p => {
        const d        = new Date(p.created_at);
        const fechaStr = `${d.toLocaleDateString('es-CO')}<br>
                          <span style="font-size:11px; color:var(--gray-400);">
                            ${d.toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'})}
                          </span>`;

        const empleado = p.empleado?.usuario
            ? `${p.empleado.usuario.nombres} ${p.empleado.usuario.apellidos}`
            : `Empleado #${p.empleado_id}`;

        // Badge de estado
        const badgeMap = {
            Pendiente: 'badge-pendiente',
            Enviado:   'badge-neutral',
            Recibido:  'badge-optimo',
            Cancelado: 'badge-critico',
        };
        const badgeClass = badgeMap[p.estado] || 'badge-neutral';
        const badge = `<span class="badge ${badgeClass}">
                         <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;margin-right:4px;"></span>
                         ${p.estado}
                       </span>`;

        const numInsumos = p.detalles?.length || 0;

        // Costo total estimado = suma(cantidad_pedida * costo_unitario_momento)
        const costoTotal = (p.detalles || []).reduce((acc, d) =>
            acc + (parseFloat(d.cantidad_pedida) * parseFloat(d.costo_unitario_momento)), 0);

        // Botones condicionales
        const puedeRecibir  = p.estado === 'Pendiente' || p.estado === 'Enviado';
        const puedeCancelar = p.estado === 'Pendiente' || p.estado === 'Enviado';

        const btnRecibir = puedeRecibir
            ? `<button class="btn-outline" style="padding:5px 10px; font-size:11px; color:var(--success,#1e8c45); border-color:var(--success,#1e8c45);"
                       onclick="marcarPedidoRecibido(${p.id})" title="Marcar como recibido">
                   <i class="fa-solid fa-check"></i> Recibido
               </button>`
            : '';

        const btnCancelar = puedeCancelar
            ? `<button class="btn-danger" style="padding:5px 10px; font-size:11px;"
                       onclick="cancelarPedidoReposicion(${p.id})" title="Cancelar pedido">
                   <i class="fa-solid fa-xmark"></i>
               </button>`
            : '';

        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.innerHTML = `
            <td style="font-size:12px;">${fechaStr}</td>
            <td style="font-size:12px;">${empleado}</td>
            <td>${badge}</td>
            <td style="text-align:center; font-weight:700;">${numInsumos}</td>
            <td style="text-align:right; font-size:12px;">${formatCOP(costoTotal)}</td>
            <td style="text-align:center;">
                <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
                    <button class="btn-outline" style="padding:5px 10px; font-size:11px;"
                            onclick="verDetallePedidoProveedor(${p.id})" title="Ver detalle">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    ${btnRecibir}
                    ${btnCancelar}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    renderReposicionPagination(total);
}

/** Renderiza la paginación del historial de reposiciones */
function renderReposicionPagination(total) {
    const totalPages = Math.ceil(total / _repPageSize);
    const info       = document.getElementById('repPaginationInfo');
    const controls   = document.getElementById('repPaginationControls');

    if (info) {
        info.textContent = total > 0
            ? `Mostrando ${(_repPage - 1) * _repPageSize + 1}–${Math.min(_repPage * _repPageSize, total)} de ${total} pedidos`
            : '0 resultados';
    }

    if (!controls) return;
    controls.innerHTML = '';

    if (totalPages <= 1) return;

    // Botón anterior
    const prev = document.createElement('button');
    prev.className = 'page-btn';
    prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    prev.disabled  = _repPage === 1;
    prev.onclick   = () => { _repPage--; renderReposicionPage(); };
    controls.appendChild(prev);

    // Números de página
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && Math.abs(p - _repPage) > 2 && p !== 1 && p !== totalPages) {
            if (p === 2 || p === totalPages - 1) {
                const dots = document.createElement('span');
                dots.textContent = '…';
                dots.style.cssText = 'padding:0 4px; color:var(--gray-400);';
                controls.appendChild(dots);
            }
            continue;
        }
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (p === _repPage ? ' active' : '');
        btn.textContent = p;
        btn.onclick = ((page) => () => { _repPage = page; renderReposicionPage(); })(p);
        controls.appendChild(btn);
    }

    // Botón siguiente
    const next = document.createElement('button');
    next.className = 'page-btn';
    next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    next.disabled  = _repPage === totalPages;
    next.onclick   = () => { _repPage++; renderReposicionPage(); };
    controls.appendChild(next);
}

/** Cambia el tamaño de página del historial de reposiciones */
function changeRepPageSize() {
    _repPageSize = parseInt(document.getElementById('repPageSize').value);
    _repPage = 1;
    renderReposicionPage();
}

/** Abre el modal con el detalle completo de un pedido de reposición */
function verDetallePedidoProveedor(pedidoId) {
    const pedido = _reposicionAll.find(p => p.id === pedidoId);
    if (!pedido) {
        showToast('Error', 'No se encontró el pedido.', 'error');
        return;
    }

    // Metadata
    document.getElementById('mdp-id').textContent = `#${String(pedido.id).padStart(4, '0')}`;

    const d = new Date(pedido.created_at);
    document.getElementById('mdp-fecha').textContent =
        `${d.toLocaleDateString('es-CO')} — ${d.toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'})}`;

    const empleado = pedido.empleado?.usuario
        ? `${pedido.empleado.usuario.nombres} ${pedido.empleado.usuario.apellidos}`
        : `Empleado #${pedido.empleado_id}`;
    document.getElementById('mdp-empleado').textContent = empleado;

    // Badge de estado
    const badgeMap = {
        Pendiente: 'badge-pendiente',
        Enviado:   'badge-neutral',
        Recibido:  'badge-optimo',
        Cancelado: 'badge-critico',
    };
    document.getElementById('mdp-estado').innerHTML =
        `<span class="badge ${badgeMap[pedido.estado] || 'badge-neutral'}">${pedido.estado}</span>`;

    // Fecha recibido (si aplica)
    const rowFR = document.getElementById('mdp-fecha-recibido-row');
    if (pedido.fecha_recibido) {
        const dr = new Date(pedido.fecha_recibido);
        document.getElementById('mdp-fecha-recibido').textContent =
            `${dr.toLocaleDateString('es-CO')} — ${dr.toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'})}`;
        rowFR.style.display = 'block';
    } else {
        rowFR.style.display = 'none';
    }

    // Detalle de insumos
    const tbody = document.getElementById('mdp-detalles');
    tbody.innerHTML = '';
    let totalEstimado = 0;

    (pedido.detalles || []).forEach(det => {
        const mp = det.materia_prima;
        const subtotal = parseFloat(det.cantidad_pedida) * parseFloat(det.costo_unitario_momento);
        totalEstimado += subtotal;

        tbody.innerHTML += `
            <tr>
                <td><strong style="font-size:12px;">${mp?.nombre || '—'}</strong></td>
                <td><span class="badge badge-neutral" style="font-size:10px;">${mp?.categoria || '—'}</span></td>
                <td style="text-align:right;">
                    <strong>${parseFloat(det.cantidad_pedida).toLocaleString('es-CO')}</strong>
                    <span style="font-size:10px; color:var(--gray-400); margin-left:2px;">${mp?.unidad_medida || ''}</span>
                </td>
                <td style="text-align:right; font-size:12px;">${formatCOP(parseFloat(det.costo_unitario_momento))}</td>
                <td style="text-align:right; font-size:12px; font-weight:600;">${formatCOP(subtotal)}</td>
            </tr>
        `;
    });

    if ((pedido.detalles || []).length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="table-empty" style="padding:20px;">Sin insumos en este pedido.</td></tr>`;
    }

    document.getElementById('mdp-total').textContent = formatCOP(totalEstimado);

    openModal('modalDetallePedido');
}

/** Marca un pedido de reposición como recibido y actualiza el stock */
function marcarPedidoRecibido(id) {
    if (!confirm('¿Confirmar recepción del pedido? Esto sumará el stock de todos los insumos pedidos.')) return;

    fetch(`/api/admin/insumos/pedidos/${id}/recibir`, {
        method: 'POST',
        headers: jsonHeaders(),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Pedido recibido', 'Stock actualizado correctamente para todos los insumos.', 'success');
            fetchHistorialReposiciones();
            fetchInsumos(); // Refrescar KPIs de inventario
        } else {
            showToast('Error', data.error || 'No se pudo marcar el pedido como recibido.', 'error');
        }
    })
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'));
}

/** Cancela un pedido de reposición (sin afectar el stock) */
function cancelarPedidoReposicion(id) {
    if (!confirm('¿Cancelar este pedido de reposición? El stock no se verá afectado.')) return;

    fetch(`/api/admin/insumos/pedidos/${id}/cancelar`, {
        method: 'POST',
        headers: jsonHeaders(),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Pedido cancelado', 'El pedido fue cancelado correctamente.', 'info');
            fetchHistorialReposiciones();
        } else {
            showToast('Error', data.error || 'No se pudo cancelar el pedido.', 'error');
        }
    })
    .catch(() => showToast('Error de conexión', 'No se pudo conectar al servidor.', 'error'));
}



// ─────────────────────────────────────────────────────────────────────────────
// 6. HISTORIAL DE PEDIDOS
// ─────────────────────────────────────────────────────────────────────────────

let histPage = 1;

function fetchHistorial(page = histPage) {
    histPage = Math.max(1, page);
    const search = document.getElementById('pedidosSearch')?.value || '';
    let url = `/api/pedidos?page=${histPage}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            const tbody  = document.getElementById('historialTableBody');
            tbody.innerHTML = '';
            const pedidos = data.data || [];
            const lastPage = data.last_page || 1;
            if (histPage > lastPage && lastPage > 0) {
                fetchHistorial(lastPage);
                return;
            }

            const info = document.getElementById('historialPageInfo');
            if (info) info.textContent = `Página ${data.current_page || histPage} de ${lastPage} (${data.total || 0} pedidos)`;
            const btnPrev = document.getElementById('historialPrev');
            const btnNext = document.getElementById('historialNext');
            if (btnPrev) btnPrev.disabled = histPage <= 1;
            if (btnNext) btnNext.disabled = histPage >= lastPage;

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
                            <strong style="display:block;">${escHtml(formatClient)}</strong>
                            <span style="font-size:11px; color:var(--gray-400);">${escHtml(formatMesa)}</span>
                        </td>
                        <td><strong>${formatTotal}</strong></td>
                        <td><span class="badge ${badgeClass}">${escHtml(p.estado)}</span></td>
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
                        <div class="ticket-line">
                            <div class="ticket-line-name">${item.cantidad}x ${item.producto ? escHtml(item.producto.nombre) : 'Item'}</div>
                            <div class="ticket-line-sub">${sub}</div>
                        </div>
                        ${item.notas_especiales ? `<div class="ticket-line-note">— ${escHtml(item.notas_especiales)}</div>` : ''}
                    `;
                });
            } else {
                itemsContainer.innerHTML = '<div class="ticket-line-empty">Sin detalle de ítems.</div>';
            }

            document.getElementById('ticketTotal').textContent = formatCOP(p.total);
            openModal('modalTicket');
        })
        .catch(() => showToast('Error', 'No se pudo cargar el ticket.', 'error'));
}

// ── Comanda ──
function submitComanda(e) {
    e.preventDefault();
    const mesa = window._comandaMesa;
    if (!mesa || !mesa.num) {
        showToast('Error', 'Selecciona una mesa del plano primero.', 'error');
        return;
    }
    const accion = document.getElementById('comandaAccion').value;

    // "Añadir a pedido" se hace desde el menú del cliente para esa mesa.
    if (accion === 'anadir') {
        closeModal('modalComanda');
        window.open(`/menu/mesa/${mesa.num}`, '_blank');
        showToast('Menú abierto', `Agrega ítems desde el menú de la mesa ${mesa.num}.`, 'info');
        return;
    }

    const btn = e.submitter;
    if (btn) btn.disabled = true;
    fetch('/api/admin/mesas/comanda', {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ mesa_numero: mesa.num, accion }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Acción ejecutada', data.message || 'La comanda fue procesada correctamente.', 'success');
            closeModal('modalComanda');
            fetchMesas();
            if (data.mesa) {
                document.getElementById('md-estado').textContent = data.mesa.estado.toUpperCase();
                window._comandaMesa.estado = data.mesa.estado;
            }
        } else {
            showToast('Error', data.error || 'No se pudo ejecutar la acción.', 'error');
        }
    })
    .catch(() => showToast('Error', 'Error de conexión.', 'error'))
    .finally(() => { if (btn) btn.disabled = false; });
}

// ─────────────────────────────────────────────────────────────────────────────
// EDICIÓN DE RESERVAS
// ─────────────────────────────────────────────────────────────────────────────

/** Abre el modal de edición precargado usando el caché (evita inyectar objetos en onclick) */
function abrirEditarReservaPorId(id) {
    const r = (window._reservasCache || []).find(x => x.id === id);
    if (!r) {
        showToast('Error', 'No se encontró la reserva.', 'error');
        return;
    }
    abrirEditarReserva(r);
}

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

function abrirEliminarReserva(id) {
    const r = (window._reservasCache || []).find(x => x.id === id) || {};
    const estado = r.estado || '';
    // Bloquear eliminación de completadas en frontend también
    if (estado === 'Completada') {
        showToast('Acción no permitida', 'No se pueden eliminar reservas ya completadas.', 'warning');
        return;
    }
    let nombre = r.cliente_nombre || '';
    const matchNota = (r.notas || '').match(/Cliente:\s*([^—\n]+)/i);
    if (matchNota) nombre = matchNota[1].trim();
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

// ══════════════════════════════════════════════════════════
// GESTIÓN DE PRODUCTOS DEL MENÚ
// ══════════════════════════════════════════════════════════

let _productos      = [];   // lista completa cargada desde API
let _categorias     = [];   // lista de categorías
let _productosFilt  = [];   // lista filtrada

/** Carga productos y categorías al entrar al tab */
async function fetchProductos() {
    try {
        const [resProd, resCat] = await Promise.all([
            apiFetch('/api/admin/productos'),
            apiFetch('/api/admin/categorias'),
        ]);
        if (resProd.ok && resCat.ok) {
            _productos  = await resProd.json();
            _categorias = await resCat.json();
            _populateCatFilter();
            _populateCatSelect();
            filterProductos();
            _updateProductosKPIs();
        }
    } catch (e) {
        showToast('Error', 'No se pudieron cargar los productos.', 'error');
    }
}

/** Rellena el filtro de categoría del toolbar */
function _populateCatFilter() {
    const sel = document.getElementById('prodCatFilter');
    sel.innerHTML = '<option value="">Todas las categorías</option>';
    _categorias.forEach(c => {
        const o = document.createElement('option');
        o.value = c.id;
        o.textContent = c.nombre;
        sel.appendChild(o);
    });
}

/** Rellena el select de categoría en el modal */
function _populateCatSelect() {
    const sel = document.getElementById('prodCategoria');
    sel.innerHTML = '<option value="">— Seleccionar —</option>';
    _categorias.forEach(c => {
        const o = document.createElement('option');
        o.value = c.id;
        o.textContent = c.nombre;
        sel.appendChild(o);
    });
}

/** Actualiza las métricas KPI del tab */
function _updateProductosKPIs() {
    const total    = _productos.length;
    const disp     = _productos.filter(p => p.disponible).length;
    const noDispo  = total - disp;
    const cats     = new Set(_productos.map(p => p.categoria_id)).size;
    document.getElementById('prod-kpi-total').textContent        = total;
    document.getElementById('prod-kpi-disponibles').textContent  = disp;
    document.getElementById('prod-kpi-nodisponibles').textContent = noDispo;
    document.getElementById('prod-kpi-categorias').textContent   = cats;
}

/** Filtra y re-renderiza la cuadrícula */
function filterProductos() {
    const q    = (document.getElementById('prodSearch').value || '').toLowerCase();
    const cat  = document.getElementById('prodCatFilter').value;
    const disp = document.getElementById('prodDispFilter').value;

    _productosFilt = _productos.filter(p => {
        const matchQ   = !q   || p.nombre.toLowerCase().includes(q) || (p.descripcion||'').toLowerCase().includes(q);
        const matchCat = !cat || String(p.categoria_id) === cat;
        const matchD   = disp === '' || String(p.disponible ? 1 : 0) === disp;
        return matchQ && matchCat && matchD;
    });
    _renderProductosGrid();
}

/** Renderiza las tarjetas */
function _renderProductosGrid() {
    const grid = document.getElementById('productosGrid');
    if (_productosFilt.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px 0;color:var(--gray-400)">
            <i class="fa-solid fa-utensils" style="font-size:40px;display:block;margin-bottom:12px;opacity:0.3;"></i>
            No se encontraron productos con esos filtros.
        </div>`;
        return;
    }

    grid.innerHTML = _productosFilt.map(p => {
        const cat     = _categorias.find(c => c.id === p.categoria_id);
        const catName = cat ? cat.nombre : '—';
        const precio  = parseFloat(p.precio).toLocaleString('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 });
        const badge   = p.disponible
            ? `<span style="background:#D4EDDA;color:#155724;font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:1px;"><i class="fa-solid fa-circle" style="font-size:7px;"></i> Disponible</span>`
            : `<span style="background:#F8D7DA;color:#721C24;font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:1px;"><i class="fa-solid fa-circle" style="font-size:7px;"></i> No Disponible</span>`;
        const img = p.imagen_url
            ? `<img src="${escHtml(p.imagen_url)}" alt="${escHtml(p.nombre)}" style="width:100%;height:160px;object-fit:cover;display:block;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
               <div style="width:100%;height:160px;background:var(--gold-bg);display:none;align-items:center;justify-content:center;"><i class="fa-solid fa-image" style="font-size:30px;color:var(--gold-dark);opacity:0.4;"></i></div>`
            : `<div style="width:100%;height:160px;background:var(--gold-bg);display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-utensils" style="font-size:30px;color:var(--gold-dark);opacity:0.4;"></i></div>`;
        return `<div class="panel-box" style="padding:0;overflow:hidden;display:flex;flex-direction:column;" id="prod-card-${p.id}">
            <div style="position:relative;overflow:hidden;">
                ${img}
                <div style="position:absolute;top:10px;right:10px;">${badge}</div>
            </div>
            <div style="padding:16px;flex:1;display:flex;flex-direction:column;gap:6px;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:var(--gold-dark);font-weight:700;">${escHtml(catName)}</div>
                <div style="font-family:var(--font-serif);font-size:16px;font-weight:700;color:var(--black);line-height:1.3;">${escHtml(p.nombre)}</div>
                ${p.descripcion ? `<div style="font-size:12px;color:var(--gray-400);">${escHtml(p.descripcion.substring(0,80))}${p.descripcion.length>80?'…':''}</div>` : ''}
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:10px;border-top:1px solid var(--border);">
                    <span style="font-size:18px;font-weight:800;color:var(--black);">${precio}</span>
                    <span style="font-size:11px;color:var(--gray-400);">Stock: <strong>${p.stock}</strong></span>
                </div>
                <div style="display:flex;gap:6px;margin-top:8px;">
                    <button class="btn-outline" style="flex:1;padding:6px 8px;font-size:11px;" onclick="openEditProducto(${p.id})">
                        <i class="fa-solid fa-pen-to-square"></i> Editar
                    </button>
                    <button class="btn-outline" style="padding:6px 10px;font-size:11px;" onclick="toggleDisponible(${p.id})" title="${p.disponible?'Desactivar del menú':'Activar en menú'}">
                        <i class="fa-solid ${p.disponible?'fa-eye-slash':'fa-eye'}"></i>
                    </button>
                    <button class="btn-danger" style="padding:6px 10px;font-size:11px;" onclick="openDeleteProducto(${p.id})"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>`;
    }).join('');
}

/** Adapta los campos, placeholders y valores sugeridos según la categoría seleccionada */
function _onCategoriaModalChange() {
    const catId = document.getElementById('prodCategoria').value;
    const cat = _categorias.find(c => String(c.id) === String(catId));
    const catName = cat ? cat.nombre.toLowerCase() : '';

    const labelNombre = document.getElementById('lblProdNombre');
    const inputNombre = document.getElementById('prodNombre');
    const inputDesc   = document.getElementById('prodDescripcion');
    const inputTiempo = document.getElementById('prodTiempo');
    const inputIngred = document.getElementById('prodIngredientes');
    const labelIngred = document.getElementById('lblProdIngredientes');
    const hintCat     = document.getElementById('prodCatHint');

    if (catName.includes('hamburguesa')) {
        if (labelNombre) labelNombre.textContent = 'Nombre de la Hamburguesa *';
        inputNombre.placeholder = 'Ej: Hamburguesa Chipotle Ahumada';
        inputDesc.placeholder   = 'Carne 150g al carbón, pan brioche, queso cheddar fundido, tocino crujiente y salsa especial...';
        if (labelIngred) labelIngred.textContent = 'Ingredientes / Alérgenos';
        inputIngred.placeholder = 'Carne res, Pan brioche, Queso cheddar, Tocino ahumado, Cebolla caramelizada...';
        if (!inputTiempo.value || inputTiempo.value === '15') inputTiempo.value = 15;
        if (hintCat) hintCat.textContent = '🍔 Categoría: Hamburguesas — Detalla tipo de pan, peso de la carne y salsas.';
    } else if (catName.includes('taco') || catName.includes('quesadilla')) {
        if (labelNombre) labelNombre.textContent = 'Nombre del Plato / Tacos *';
        inputNombre.placeholder = 'Ej: Tacos al Pastor / Quesadilla de Birria';
        inputDesc.placeholder   = '3 tacos en tortilla de maíz con jugosa carne adobada, piña asada, cebolla morada y cilantro...';
        if (labelIngred) labelIngred.textContent = 'Ingredientes / Alérgenos';
        inputIngred.placeholder = 'Cerdo adobado, Tortillas de maíz, Piña, Cilantro, Cebolla morada, Salsa...';
        if (!inputTiempo.value || inputTiempo.value === '15') inputTiempo.value = 10;
        if (hintCat) hintCat.textContent = '🌮 Categoría: Tacos & Quesadillas — Indica número de unidades, tipo de tortilla y salsas.';
    } else if (catName.includes('bebida') || catName.includes('coctel') || catName.includes('trago')) {
        if (labelNombre) labelNombre.textContent = 'Nombre de la Bebida / Cóctel *';
        inputNombre.placeholder = 'Ej: Margarita de Maracuyá / Agua de Horchata';
        inputDesc.placeholder   = 'Bebida refrescante servida con hielo frappé, fruta natural y escarchado de sal y tajín...';
        if (labelIngred) labelIngred.textContent = 'Ingredientes / Base del Cóctel';
        inputIngred.placeholder = 'Tequila blanco, Triple sec, Pulpa de maracuyá, Limón, Sal marina, Tajín...';
        if (!inputTiempo.value || inputTiempo.value === '15') inputTiempo.value = 3;
        if (hintCat) hintCat.textContent = '🍹 Categoría: Bebidas — Puedes especificar si contiene alcohol, tamaño o temperatura.';
    } else if (catName.includes('postre')) {
        if (labelNombre) labelNombre.textContent = 'Nombre del Postre *';
        inputNombre.placeholder = 'Ej: Churros Artesanales con Arequipe';
        inputDesc.placeholder   = 'Crujientes churros artesanales espolvoreados con azúcar y canela, acompañados de salsa...';
        if (labelIngred) labelIngred.textContent = 'Ingredientes / Alérgenos';
        inputIngred.placeholder = 'Harina de trigo, Azúcar, Canela, Dulce de leche / Arequipe...';
        if (!inputTiempo.value || inputTiempo.value === '15') inputTiempo.value = 5;
        if (hintCat) hintCat.textContent = '🍨 Categoría: Postres — Ideal para detallar acompañamientos como bolas de helado o toppings.';
    } else if (catName.includes('acompaña') || catName.includes('entrada') || catName.includes('papas')) {
        if (labelNombre) labelNombre.textContent = 'Nombre de la Entrada / Acompañamiento *';
        inputNombre.placeholder = 'Ej: Papas Mexicanas con Queso / Nachos';
        inputDesc.placeholder   = 'Gajos de papas crujientes bañados en queso fundido, pico de gallo y jalapeños...';
        if (labelIngred) labelIngred.textContent = 'Ingredientes / Alérgenos';
        inputIngred.placeholder = 'Papas cortadas, Queso cheddar fundido, Pico de gallo, Crema agria, Jalapeños...';
        if (!inputTiempo.value || inputTiempo.value === '15') inputTiempo.value = 8;
        if (hintCat) hintCat.textContent = '🍟 Categoría: Acompañamientos — Indica si es porción individual o para compartir.';
    } else {
        if (labelNombre) labelNombre.textContent = 'Nombre del Plato *';
        inputNombre.placeholder = 'Ej: Plato Especial Sabor a Pueblo';
        inputDesc.placeholder   = 'Descripción del plato, ingredientes principales y presentación...';
        if (labelIngred) labelIngred.textContent = 'Ingredientes / Alérgenos';
        inputIngred.placeholder = 'Ingredientes principales separados por coma...';
        if (hintCat) hintCat.textContent = '';
    }
}

/** Abre modal en modo creación */
function openModalProducto() {
    document.getElementById('prodId').value = '';
    document.getElementById('modalProductoTituloText').textContent = 'Nuevo Producto';
    document.getElementById('formProducto').reset();
    document.getElementById('prodDisponible').checked = true;
    _onCategoriaModalChange();
    openModal('modalProducto');
}

/** Abre modal en modo edición */
function openEditProducto(id) {
    const p = _productos.find(x => x.id === id);
    if (!p) return;
    document.getElementById('prodId').value              = p.id;
    document.getElementById('modalProductoTituloText').textContent = 'Editar Producto';
    document.getElementById('prodNombre').value          = p.nombre || '';
    document.getElementById('prodCategoria').value       = p.categoria_id || '';
    document.getElementById('prodDescripcion').value     = p.descripcion || '';
    document.getElementById('prodPrecio').value          = p.precio || '';
    document.getElementById('prodStock').value           = p.stock || 0;
    document.getElementById('prodTiempo').value          = p.tiempo_preparacion || '';
    document.getElementById('prodImagenUrl').value       = p.imagen_url || '';
    document.getElementById('prodIngredientes').value   = p.ingredientes || '';
    document.getElementById('prodDisponible').checked   = !!p.disponible;
    _onCategoriaModalChange();
    openModal('modalProducto');
}

/** Envía el formulario de crear/editar */
async function submitProducto(e) {
    e.preventDefault();
    const id = document.getElementById('prodId').value;
    const body = {
        nombre:             document.getElementById('prodNombre').value.trim(),
        categoria_id:       parseInt(document.getElementById('prodCategoria').value),
        descripcion:        document.getElementById('prodDescripcion').value.trim(),
        precio:             parseFloat(document.getElementById('prodPrecio').value),
        stock:              parseInt(document.getElementById('prodStock').value),
        tiempo_preparacion: parseInt(document.getElementById('prodTiempo').value) || null,
        imagen_url:         document.getElementById('prodImagenUrl').value.trim() || null,
        ingredientes:       document.getElementById('prodIngredientes').value.trim() || null,
        disponible:         document.getElementById('prodDisponible').checked,
    };

    const btn = document.getElementById('btnSubmitProducto');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    try {
        const method = id ? 'PUT' : 'POST';
        const url    = id ? `/api/admin/productos/${id}` : '/api/admin/productos';
        const res    = await apiFetch(url, { method, body: JSON.stringify(body) });
        const data   = await res.json();
        if (res.ok && data.success) {
            closeModal('modalProducto');
            showToast('✓ Producto guardado', `"${data.producto.nombre}" actualizado en el menú.`, 'success');
            await fetchProductos();
        } else {
            showToast('Error', data.message || data.error || 'Error al guardar el producto.', 'error');
        }
    } catch (err) {
        showToast('Error', 'Fallo de conexión.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Producto';
    }
}

/** Activa o desactiva la disponibilidad del producto sin abrir modal */
async function toggleDisponible(id) {
    const p = _productos.find(x => x.id === id);
    if (!p) return;
    try {
        const res  = await apiFetch(`/api/admin/productos/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ ...p, disponible: !p.disponible }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            const estado = data.producto.disponible ? 'Disponible' : 'No disponible';
            showToast('✓ Actualizado', `"${p.nombre}" ahora está ${estado} en el menú.`, 'success');
            await fetchProductos();
        } else {
            showToast('Error', (data && data.error) || 'No se pudo cambiar el estado.', 'error');
        }
    } catch (e) {
        showToast('Error', 'No se pudo cambiar el estado.', 'error');
    }
}

/** Abre el modal de confirmación de eliminación (nombre desde caché, sin inyectar strings) */
function openDeleteProducto(id) {
    const p = (_productos || []).find(x => x.id === id) || {};
    document.getElementById('eliminarProductoId').value = id;
    document.getElementById('eliminarProductoNombre').textContent = p.nombre || '';
    openModal('modalEliminarProducto');
}

/** Confirma y ejecuta la eliminación */
async function confirmarEliminarProducto() {
    const id = document.getElementById('eliminarProductoId').value;
    try {
        const res  = await apiFetch(`/api/admin/productos/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (res.ok && data.success) {
            closeModal('modalEliminarProducto');
            showToast('✓ Listo', data.message || 'Producto eliminado.', 'success');
            await fetchProductos();
        } else {
            showToast('Error', data.error || 'No se pudo eliminar.', 'error');
        }
    } catch (e) {
        showToast('Error', 'Fallo de conexión.', 'error');
    }
}

// ═══════════════════════════════════════════════════════════
//  GESTIÓN DE CATEGORÍAS (CRUD)
// ═══════════════════════════════════════════════════════════

let _catListData = []; // caché local de categorías para el modal

/** Abre el modal de gestión de categorías y carga la lista */
async function openModalCategoria() {
    resetCatForm();
    openModal('modalCategoria');
    await _loadCatList();
}

/** Carga la lista de categorías en el modal */
async function _loadCatList() {
    const container = document.getElementById('categoriasListContainer');
    container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--gray-400);"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';
    try {
        const res = await apiFetch('/api/admin/categorias');
        if (!res.ok) throw new Error();
        _catListData = await res.json();
        _renderCatList();
    } catch (e) {
        container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--danger);">Error al cargar categorías.</div>';
    }
}

/** Renderiza la lista de categorías dentro del modal */
function _renderCatList() {
    const container = document.getElementById('categoriasListContainer');
    if (_catListData.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--gray-400);">No hay categorías registradas.</div>';
        return;
    }
    container.innerHTML = _catListData.map(c => `
        <div style="display:flex; align-items:center; gap:10px; padding:10px 14px; border-bottom:1px solid var(--border); background:var(--white);">
            <div style="flex:1; min-width:0;">
                <div style="font-weight:600; font-size:13px; color:var(--black); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escHtml(c.nombre)}</div>
                ${c.descripcion ? `<div style="font-size:11px; color:var(--gray-600); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escHtml(c.descripcion)}</div>` : ''}
            </div>
            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; flex-shrink:0;
                background:${c.activo ? 'rgba(30,140,69,0.12)' : 'rgba(200,50,50,0.1)'};
                color:${c.activo ? 'var(--success,#1e8c45)' : 'var(--danger)'};">
                ${c.activo ? 'Activa' : 'Inactiva'}
            </span>
            <button onclick="openEditCategoria(${c.id})"
                style="padding:5px 10px; font-size:11px; border:1px solid var(--gold-dark); background:transparent; color:var(--gold-dark); border-radius:var(--radius-sm); cursor:pointer; flex-shrink:0;"
                title="Editar categoría">
                <i class="fa-solid fa-pen"></i>
            </button>
            <button onclick="openDeleteCategoria(${c.id})"
                style="padding:5px 10px; font-size:11px; border:1px solid var(--danger); background:transparent; color:var(--danger); border-radius:var(--radius-sm); cursor:pointer; flex-shrink:0;"
                title="Eliminar categoría">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `).join('');
}

/** Escapa HTML para evitar XSS en innerHTML */
function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/** Escapa atributos para onclick strings */
function escAttr(str) {
    return String(str ?? '').replace(/'/g, "\\'");
}

/** Pone el formulario de categoría en modo edición */
function openEditCategoria(id) {
    const cat = _catListData.find(c => c.id === id);
    if (!cat) return;
    document.getElementById('catId').value          = cat.id;
    document.getElementById('catNombre').value      = cat.nombre;
    document.getElementById('catDescripcion').value = cat.descripcion ?? '';
    document.getElementById('catActivo').checked    = !!cat.activo;
    document.getElementById('catFormLabel').textContent = 'Editar categoría';
    document.getElementById('btnCatCancelar').textContent = 'Cancelar edición';
    document.getElementById('catNombre').focus();
}

/** Resetea el formulario de categoría al estado "nueva" */
function resetCatForm() {
    document.getElementById('catId').value          = '';
    document.getElementById('catNombre').value      = '';
    document.getElementById('catDescripcion').value = '';
    document.getElementById('catActivo').checked    = true;
    document.getElementById('catFormLabel').textContent = 'Nueva categoría';
    document.getElementById('btnCatCancelar').textContent = 'Cancelar';
    document.getElementById('err-catNombre').style.display = 'none';
}

/** Envía el formulario de categoría (crear o editar) */
async function submitCategoria(e) {
    e.preventDefault();
    const nombre = document.getElementById('catNombre').value.trim();
    const errEl  = document.getElementById('err-catNombre');
    if (!nombre) {
        errEl.style.display = 'block';
        document.getElementById('catNombre').focus();
        return;
    }
    errEl.style.display = 'none';

    const id      = document.getElementById('catId').value;
    const payload = {
        nombre,
        descripcion: document.getElementById('catDescripcion').value.trim() || null,
        activo:      document.getElementById('catActivo').checked,
    };

    const btn = document.getElementById('btnSubmitCategoria');
    btn.disabled    = true;
    btn.innerHTML   = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    try {
        const url    = id ? `/api/admin/categorias/${id}` : '/api/admin/categorias';
        const method = id ? 'PUT' : 'POST';
        const res    = await apiFetch(url, { method, body: JSON.stringify(payload) });
        const data   = await res.json();

        if (res.ok && data.success) {
            showToast('✔ Categoría guardada', `"${data.categoria.nombre}" guardada correctamente.`, 'success');
            resetCatForm();
            await _loadCatList();
            // Refrescar selectores de productos
            await fetchProductos();
        } else {
            showToast('Error', data.message || data.error || 'No se pudo guardar la categoría.', 'error');
        }
    } catch (err) {
        showToast('Error', 'Fallo de conexión.', 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Categoría';
    }
}

/** Abre el modal de confirmación para eliminar una categoría */
function openDeleteCategoria(id) {
    const c = (_catListData || []).find(x => x.id === id) || {};
    document.getElementById('eliminarCategoriaId').value           = id;
    document.getElementById('eliminarCategoriaNombre').textContent = c.nombre || '';
    openModal('modalEliminarCategoria');
}

/** Confirma y ejecuta la eliminación de una categoría */
async function confirmarEliminarCategoria() {
    const id = document.getElementById('eliminarCategoriaId').value;
    try {
        const res  = await apiFetch(`/api/admin/categorias/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (res.ok && data.success) {
            closeModal('modalEliminarCategoria');
            showToast('✔ Eliminada', data.message || 'Categoría eliminada correctamente.', 'success');
            await _loadCatList();
            await fetchProductos();
        } else {
            showToast('Error', data.message || data.error || 'No se pudo eliminar la categoría.', 'error');
        }
    } catch (e) {
        showToast('Error', 'Fallo de conexión.', 'error');
    }
}