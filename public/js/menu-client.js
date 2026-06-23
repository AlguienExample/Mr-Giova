let categories = [];
let allProducts = [];
let activeCategory = 'all';

let cart = [];
let currentModalProduct = null;
let currentOrderTrackingId = null;
let trackingInterval = null;

// Cargar datos al iniciar
window.addEventListener('DOMContentLoaded', () => {
    fetchMenu();
    checkExistingTracking();
});

// Obtener datos del menú
function fetchMenu() {
    fetch('/api/productos')
        .then(res => res.json())
        .then(data => {
            categories = data;
            // Consolidar todos los productos en un array plano para búsquedas
            allProducts = [];
            data.forEach(cat => {
                cat.productos.forEach(prod => {
                    prod.categoria_nombre = cat.nombre;
                    allProducts.push(prod);
                });
            });
            
            renderCategoryChips();
            renderMenu();
        })
        .catch(err => {
            console.error("Error cargando el menú", err);
            document.getElementById('productsWrapper').innerHTML = 
                `<div style="text-align:center; padding: 40px; color:#E74C3C;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size:32px; margin-bottom:10px;"></i>
                    <p>Hubo un problema al cargar el menú. Reintente por favor.</p>
                </div>`;
        });
}

// Render chips
function renderCategoryChips() {
    const container = document.getElementById('categoriesScroll');
    container.innerHTML = `
        <div class="category-chip ${activeCategory === 'all' ? 'active' : ''}" id="chip-all" onclick="selectCategory('all')">
            <i class="fa-solid fa-utensils"></i> Todo
        </div>
    `;
    
    categories.forEach(cat => {
        let iconClass = 'fa-pepper-hot';
        if (cat.nombre.includes('Hamburguesas')) iconClass = 'fa-burger';
        else if (cat.nombre.includes('Tacos')) iconClass = 'fa-taco';
        else if (cat.nombre.includes('Bebidas')) iconClass = 'fa-glass-water';
        else if (cat.nombre.includes('Postres')) iconClass = 'fa-ice-cream';
        else if (cat.nombre.includes('Acompañamientos')) iconClass = 'fa-cookie';

        if(cat.nombre.toLowerCase().includes('taco')) iconClass = 'fa-pepper-hot';

        container.innerHTML += `
            <div class="category-chip ${activeCategory == cat.id ? 'active' : ''}" id="chip-${cat.id}" onclick="selectCategory(${cat.id})">
                <i class="fa-solid ${iconClass}"></i> ${cat.nombre}
            </div>
        `;
    });
}

// Seleccionar categoría
function selectCategory(catId) {
    activeCategory = catId;
    document.querySelectorAll('.category-chip').forEach(el => el.classList.remove('active'));
    document.getElementById(`chip-${catId}`).classList.add('active');
    renderMenu();
}

// Renderizar el menú
function renderMenu(searchTerm = '') {
    const wrapper = document.getElementById('productsWrapper');
    wrapper.innerHTML = '';

    let filteredCategories = JSON.parse(JSON.stringify(categories));

    // Filtrar por término de búsqueda si existe
    if (searchTerm) {
        filteredCategories = filteredCategories.map(cat => {
            cat.productos = cat.productos.filter(prod => 
                prod.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
                prod.descripcion.toLowerCase().includes(searchTerm.toLowerCase())
            );
            return cat;
        }).filter(cat => cat.productos.length > 0);
    }

    // Filtrar por chip de categoría activa
    if (activeCategory !== 'all') {
        filteredCategories = filteredCategories.filter(cat => cat.id == activeCategory);
    }

    if (filteredCategories.length === 0) {
        wrapper.innerHTML = `
            <div style="text-align:center; padding: 60px 20px; color: var(--color-muted);">
                <i class="fa-solid fa-face-frown" style="font-size:32px; margin-bottom:10px;"></i>
                <p>No encontramos platillos que coincidan.</p>
            </div>`;
        return;
    }

    filteredCategories.forEach(cat => {
        if(cat.productos.length === 0) return;
        
        let catSection = document.createElement('div');
        catSection.innerHTML = `<h3 class="category-section-title">${cat.nombre}</h3>`;
        
        cat.productos.forEach(prod => {
            let priceFormatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(prod.precio);
            
            let card = document.createElement('div');
            card.className = 'product-row-card';
            card.onclick = () => openProductModal(prod);
            
            card.innerHTML = `
                <img src="${prod.imagen_url || '/images/placeholder.jpg'}" class="product-card-img" alt="${prod.nombre}" onerror="this.src='https://images.unsplash.com/photo-1568901346375-23c9450c58cd'">
                <div class="product-card-info">
                    <div>
                        <h4 class="product-card-name">${prod.nombre}</h4>
                        <p class="product-card-desc">${prod.descripcion}</p>
                    </div>
                    <div class="product-card-footer">
                        <span class="product-card-price">${priceFormatted}</span>
                        <button class="product-card-btn" aria-label="Ver detalles de ${prod.nombre}"><i class="fa-solid fa-plus"></i></button>
                    </div>
                </div>
            `;
            catSection.appendChild(card);
        });
        
        wrapper.appendChild(catSection);
    });
}

function filterProducts() {
    const query = document.getElementById('searchInput').value;
    renderMenu(query);
}

// MODAL DE PRODUCTO
function openProductModal(product) {
    currentModalProduct = product;
    document.getElementById('modalImg').style.backgroundImage = `url('${product.imagen_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd'}')`;
    document.getElementById('modalName').textContent = product.nombre;
    document.getElementById('modalDesc').textContent = product.descripcion;
    
    let formattedPrice = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(product.precio);
    document.getElementById('modalPrice').textContent = formattedPrice;
    document.getElementById('modalBtnTotal').textContent = formattedPrice;

    const nameLower = product.nombre.toLowerCase();
    const modGroup = document.getElementById('modifiersGroup');
    if (nameLower.includes('hamburguesa')) {
        modGroup.style.display = 'block';
        document.getElementsByName('termino')[1].checked = true;
    } else {
        modGroup.style.display = 'none';
    }

    document.getElementById('modalNotes').value = '';
    document.getElementById('modalQty').textContent = '1';
    
    document.getElementById('productModal').classList.add('open');
}

function closeProductModal() {
    document.getElementById('productModal').classList.remove('open');
    currentModalProduct = null;
}

function closeModalOnBgClick(e) {
    if (e.target.id === 'productModal') {
        closeProductModal();
    }
}

function adjustModalQty(val) {
    let current = parseInt(document.getElementById('modalQty').textContent);
    let newVal = current + val;
    if (newVal < 1) newVal = 1;
    document.getElementById('modalQty').textContent = newVal;
    
    let total = currentModalProduct.precio * newVal;
    let formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(total);
    document.getElementById('modalBtnTotal').textContent = formatted;
}

// CARRITO LÓGICA
function addProductToCart() {
    let qty = parseInt(document.getElementById('modalQty').textContent);
    let note = document.getElementById('modalNotes').value.trim();
    
    let modifier = '';
    if (currentModalProduct.nombre.toLowerCase().includes('hamburguesa')) {
        const radios = document.getElementsByName('termino');
        for (let r of radios) {
            if (r.checked) {
                modifier = r.value;
                break;
            }
        }
    }

    let extraDetails = [];
    if(modifier) extraDetails.push(modifier);
    if(note) extraDetails.push(note);
    let finalNote = extraDetails.join(' • ');

    let existingIndex = cart.findIndex(item => 
        item.producto_id === currentModalProduct.id && 
        item.notas_especiales === finalNote
    );

    if (existingIndex > -1) {
        cart[existingIndex].cantidad += qty;
    } else {
        cart.push({
            producto_id: currentModalProduct.id,
            nombre: currentModalProduct.nombre,
            imagen_url: currentModalProduct.imagen_url,
            precio: currentModalProduct.precio,
            cantidad: qty,
            notas_especiales: finalNote
        });
    }

    updateFloatingCart();
    closeProductModal();
    
    showToast(`¡Agregado al carrito: ${qty}x ${currentModalProduct.nombre}!`);
}

function updateFloatingCart() {
    const preview = document.getElementById('floatingCart');
    const countEl = document.getElementById('cartCount');
    const totalPreview = document.getElementById('cartTotalPreview');
    
    if (cart.length === 0) {
        preview.style.display = 'none';
        return;
    }

    preview.style.display = 'flex';
    let count = cart.reduce((acc, item) => acc + item.cantidad, 0);
    
    countEl.textContent = count;
    
    if (totalPreview) {
        let total = cart.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
        totalPreview.textContent = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(total);
    }
}

function openCart() {
    document.getElementById('cartScreen').classList.add('open');
    renderCartItems();
}

function closeCart() {
    document.getElementById('cartScreen').classList.remove('open');
}

function renderCartItems() {
    const container = document.getElementById('cartItemsList');
    container.innerHTML = '';

    const totalItems = cart.reduce((a, i) => a + i.cantidad, 0);
    document.getElementById('cartHeaderCount').textContent =
        totalItems === 1 ? '1 artículo' : `${totalItems} artículos`;

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="cart-empty-state">
                <div class="empty-icon"><i class="fa-solid fa-basket-shopping"></i></div>
                <p>Tu carrito está vacío</p>
                <span>Agrega platillos del menú para comenzar a disfrutar de la mejor comida.</span>
            </div>`;
        document.getElementById('cartTotal').textContent = '$0';
        document.getElementById('btnConfirmarPedido').disabled = true;
        document.getElementById('btnConfirmarPedido').style.opacity = 0.45;
        return;
    }

    document.getElementById('btnConfirmarPedido').disabled = false;
    document.getElementById('btnConfirmarPedido').style.opacity = 1;

    let grandTotal = 0;

    cart.forEach((item, index) => {
        let subt = item.precio * item.cantidad;
        grandTotal += subt;
        let formattedPrice = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(subt);

        let row = document.createElement('div');
        row.className = 'cart-item-row';
        row.innerHTML = `
            <img src="${item.imagen_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd'}" alt="${item.nombre}" class="cart-item-img">
            <div class="cart-item-details">
                <div class="cart-item-header">
                    <div class="cart-item-name">${item.nombre}</div>
                    <div class="cart-item-price">${formattedPrice}</div>
                </div>
                ${item.notas_especiales ? `<div class="cart-item-mod"><i class="fa-solid fa-pencil"></i> ${item.notas_especiales}</div>` : ''}
                
                <div class="cart-item-actions">
                    <div class="cart-item-qty-controls">
                        <button class="cart-qty-btn" onclick="updateCartItemQty(${index}, -1)" aria-label="Reducir cantidad de ${item.nombre}">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <span class="cart-qty-value">${item.cantidad}</span>
                        <button class="cart-qty-btn" onclick="updateCartItemQty(${index}, 1)" aria-label="Aumentar cantidad de ${item.nombre}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <button class="cart-item-delete" onclick="deleteCartItem(${index})" aria-label="Eliminar ${item.nombre} del carrito" title="Eliminar">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(row);
    });

    document.getElementById('cartTotal').textContent = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(grandTotal);
}

function updateCartItemQty(index, val) {
    if (cart[index]) {
        cart[index].cantidad += val;
        if (cart[index].cantidad <= 0) {
            deleteCartItem(index);
        } else {
            renderCartItems();
            updateFloatingCart();
        }
    }
}

function deleteCartItem(index) {
    cart.splice(index, 1);
    renderCartItems();
    updateFloatingCart();
}

function confirmOrder() {
    const btn = document.getElementById('btnConfirmarPedido');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Enviando a Cocina...`;

    const notes = document.getElementById('orderGeneralNotes').value.trim();

    const payload = {
        mesa_id: window.tableId,
        items: cart.map(item => ({
            producto_id: item.producto_id,
            cantidad: item.cantidad,
            notas_especiales: item.notas_especiales
        })),
        notas: notes
    };

    fetch('/api/pedidos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            cart = [];
            updateFloatingCart();
            closeCart();
            
            showTracking(data.pedido_id, data.tiempo_estimado);
            
            localStorage.setItem('mrgiova_tracking_id', data.pedido_id);
            localStorage.setItem('mrgiova_tracking_time', data.tiempo_estimado);
        } else {
            alert('Error: ' + (data.error || 'No se pudo enviar el pedido.'));
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-circle-check"></i> Confirmar Pedido`;
        }
    })
    .catch(err => {
        console.error("Error al enviar el pedido", err);
        alert('Ocurrió un error al enviar tu pedido. Reintenta.');
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-circle-check"></i> Confirmar Pedido`;
    });
}

function showTracking(pedidoId, tiempoEstimado) {
    currentOrderTrackingId = pedidoId;
    document.getElementById('menuScreen').style.display = 'none';
    document.getElementById('trackingScreen').style.display = 'flex';
    document.getElementById('trackingOrderNum').textContent = `#${pedidoId}`;
    document.getElementById('trackingTime').textContent = tiempoEstimado || '15 - 20 min';

    pollOrderStatus();
    if (trackingInterval) clearInterval(trackingInterval);
    trackingInterval = setInterval(pollOrderStatus, 3000);
}

function checkExistingTracking() {
    const savedId = localStorage.getItem('mrgiova_tracking_id');
    const savedTime = localStorage.getItem('mrgiova_tracking_time');
    if (savedId) {
        showTracking(parseInt(savedId), savedTime);
    }
}

function pollOrderStatus() {
    if (!currentOrderTrackingId) return;

    fetch(`/api/pedidos/${currentOrderTrackingId}`)
        .then(res => res.json())
        .then(order => {
            if (order.error) {
                resetToMenu();
                return;
            }

            if (order.estado === 'Cancelado') {
                document.getElementById('trackingSuccessIcon').style.display = 'none';
                document.getElementById('trackingTitle').style.display = 'none';
                document.getElementById('trackingDesc').style.display = 'none';
                document.getElementById('trackingStatusFlow').style.display = 'none';
                document.getElementById('cancellationAlert').style.display = 'block';

                clearInterval(trackingInterval);
                localStorage.removeItem('mrgiova_tracking_id');
                localStorage.removeItem('mrgiova_tracking_time');
                return;
            }

            document.getElementById('trackingSuccessIcon').style.display = 'flex';
            document.getElementById('trackingTitle').style.display = 'block';
            document.getElementById('trackingDesc').style.display = 'block';
            document.getElementById('trackingStatusFlow').style.display = 'flex';
            document.getElementById('cancellationAlert').style.display = 'none';

            const steps = ['Nuevo', 'En_Preparacion', 'Listo', 'Entregado'];
            const currentStepIndex = steps.indexOf(order.estado);

            steps.forEach((step, idx) => {
                const stepEl = document.getElementById(`step-${step}`);
                stepEl.classList.remove('active', 'completed');
                
                if (idx < currentStepIndex) {
                    stepEl.classList.add('completed');
                } else if (idx === currentStepIndex) {
                    stepEl.classList.add('active');
                }
            });

            if (order.estado === 'Entregado') {
                clearInterval(trackingInterval);
                localStorage.removeItem('mrgiova_tracking_id');
                localStorage.removeItem('mrgiova_tracking_time');
            }
        })
        .catch(err => {
            console.error("Error al consultar estado del pedido", err);
        });
}

function resetToMenu() {
    if (trackingInterval) clearInterval(trackingInterval);
    currentOrderTrackingId = null;
    localStorage.removeItem('mrgiova_tracking_id');
    localStorage.removeItem('mrgiova_tracking_time');
    
    document.getElementById('trackingScreen').style.display = 'none';
    document.getElementById('menuScreen').style.display = 'block';
    
    document.getElementById('trackingSuccessIcon').style.display = 'flex';
    document.getElementById('trackingTitle').style.display = 'block';
    document.getElementById('trackingDesc').style.display = 'block';
    document.getElementById('trackingStatusFlow').style.display = 'flex';
    document.getElementById('cancellationAlert').style.display = 'none';
    
    fetchMenu();
}

// TOAST HELPER
let toastTimeout;
function showToast(message) {
    let oldToast = document.querySelector('.toast-notification');
    if (oldToast) {
        oldToast.remove();
        if (toastTimeout) clearTimeout(toastTimeout);
    }

    let toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = `<i class="fa-solid fa-check-circle" style="color:var(--color-cempasuchil); font-size:18px;"></i> <span>${message}</span>`;
    document.body.appendChild(toast);
    
    // Trigger animation faster
    setTimeout(() => toast.classList.add('show'), 10);
    
    toastTimeout = setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => { if(toast.parentNode) toast.remove(); }, 300);
    }, 2500);
}
