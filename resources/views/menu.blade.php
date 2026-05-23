<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mr.Giova - Menú del Cliente</title>
    <!-- CSS Estilos Mexicanos -->
    <link rel="stylesheet" href="{{ asset('css/restaurant.css') }}">
    <!-- FontAwesome para Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Meta CSRF para peticiones AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="mexican-border-top"></div>

    <div class="mobile-view-container" id="app">
        <!-- HEADER -->
        <header class="client-header">
            <div class="client-header-title">
                <h2>Mr.<span>Giova</span></h2>
            </div>
            <div class="client-header-table" id="tableBadge">
                Mesa {{ $mesa->numero_mesa }}
            </div>
        </header>

        <!-- PANTALLA PRINCIPAL: MENÚ -->
        <div id="menuScreen" class="screen-view">
            <!-- Promo Banner -->
            <div class="promo-banner">
                <i class="fa-solid fa-pepper-hot promo-banner-pattern"></i>
                <h3>¡Sabor que Enamora!</h3>
                <p>Escanea, pide y disfruta el auténtico sazón de Mr.Giova directamente en tu mesa.</p>
            </div>

            <!-- Buscador -->
            <div class="search-wrapper">
                <div class="search-input-container">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Buscar platillos en el menú..." oninput="filterProducts()">
                </div>
            </div>

            <!-- Categorías (Horizontal Scroll) -->
            <div class="categories-container">
                <div class="categories-scroll" id="categoriesScroll">
                    <div class="category-chip active" id="chip-all" onclick="selectCategory('all')">
                        <i class="fa-solid fa-utensils"></i> Todo
                    </div>
                    <!-- Cargadas dinámicamente -->
                </div>
            </div>

            <!-- Lista de Productos -->
            <div class="products-wrapper" id="productsWrapper">
                <!-- Secciones cargadas dinámicamente -->
                <div style="text-align: center; padding: 40px; color: var(--color-muted);">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <p>Cargando menú delicioso...</p>
                </div>
            </div>

            <!-- Barra Flotante de Carrito -->
            <div class="floating-cart-bar" id="floatingCart" style="display: none;" onclick="openCart()">
                <div style="display: flex; align-items: center; gap: 12px; font-weight: 700; color: var(--color-terracotta);">
                    <i class="fa-solid fa-basket-shopping" style="font-size: 20px;"></i>
                    <span id="cartCount">0</span> Ítems
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-weight: 800; font-size: 16px;" id="cartTotalPreview">$0</span>
                    <button class="btn-mrgiova" style="padding: 8px 16px; font-size: 13px;">Ver Carrito</button>
                </div>
            </div>
        </div>

        <!-- MODAL DETALLE DE PRODUCTO -->
        <div class="mrgiova-modal" id="productModal" onclick="closeModalOnBgClick(event)">
            <div class="modal-content">
                <div class="modal-header-img" id="modalImg" style="background-image: url(''); background-size: cover; background-position: center;">
                    <button class="modal-close-btn" onclick="closeProductModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="modal-title-row">
                        <h3 id="modalName">Nombre Producto</h3>
                        <span class="modal-price" id="modalPrice">$0.00</span>
                    </div>
                    <p class="modal-description" id="modalDesc">Descripción larga del producto y sus características mexicanas.</p>

                    <!-- Modificadores si es hamburguesa -->
                    <div class="modifier-group" id="modifiersGroup" style="display: none;">
                        <div class="modifier-group-title">Personaliza tu término</div>
                        <div class="modifier-options">
                            <label class="modifier-option">
                                <input type="radio" name="termino" value="Rojo" checked> Término Rojo
                            </label>
                            <label class="modifier-option">
                                <input type="radio" name="termino" value="Término Medio"> Término Medio
                            </label>
                            <label class="modifier-option">
                                <input type="radio" name="termino" value="Bien Cocido"> Bien Cocido
                            </label>
                        </div>
                    </div>

                    <!-- Notas adicionales -->
                    <div class="modifier-group">
                        <div class="modifier-group-title">Notas para la cocina</div>
                        <textarea class="notes-textarea" id="modalNotes" placeholder="Ej: Sin cebolla, extra salsa, etc."></textarea>
                    </div>

                    <!-- Controles de cantidad y botón agregar -->
                    <div class="quantity-control-wrapper">
                        <div style="font-weight: 700;">Cantidad</div>
                        <div class="quantity-control">
                            <button class="quantity-btn" onclick="adjustModalQty(-1)"><i class="fa-solid fa-minus"></i></button>
                            <span class="quantity-value" id="modalQty">1</span>
                            <button class="quantity-btn" onclick="adjustModalQty(1)"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>

                    <button class="btn-mrgiova" style="width: 100%; border-radius: var(--border-radius-sm);" onclick="addProductToCart()">
                        Agregar al Carrito • <span id="modalBtnTotal">$0.00</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- PANTALLA: CARRITO -->
        <div class="cart-screen" id="cartScreen">
            <header class="cart-screen-header">
                <button class="cart-back-btn" onclick="closeCart()"><i class="fa-solid fa-arrow-left"></i></button>
                <h3 style="color: white;">Mi Carrito</h3>
            </header>

            <div class="cart-items-list" id="cartItemsList">
                <!-- Se carga dinámicamente -->
            </div>

            <!-- Sección inferior con total y notas de cocina -->
            <div style="padding: 20px; border-top: 1px solid rgba(0,0,0,0.1); background-color: var(--color-sand);">
                <div style="margin-bottom: 15px;">
                    <div class="modifier-group-title">Notas adicionales para el pedido</div>
                    <textarea class="notes-textarea" id="orderGeneralNotes" style="margin-bottom: 0;" placeholder="Notas generales para toda la orden..."></textarea>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <span style="font-weight: 700; font-size: 16px;">Total a pagar:</span>
                    <span style="font-weight: 800; font-size: 22px; color: var(--color-terracotta);" id="cartTotal">$0.00</span>
                </div>
                <button class="btn-mrgiova" style="width: 100%;" onclick="confirmOrder()" id="btnConfirmarPedido">
                    Confirmar Pedido <i class="fa-solid fa-circle-check"></i>
                </button>
            </div>
        </div>

        <!-- PANTALLA: SEGUIMIENTO / TRACKING -->
        <div class="tracking-screen" id="trackingScreen" style="display: none;">
            <div class="success-icon-wrapper">
                <i class="fa-solid fa-check"></i>
            </div>
            <h2 class="tracking-title">¡Pedido Enviado!</h2>
            <p class="tracking-desc">Tu pedido ha sido recibido correctamente en la cocina de Mr.Giova.</p>

            <div class="tracking-order-box">
                <div class="tracking-order-num-label">Número de Pedido</div>
                <div class="tracking-order-num" id="trackingOrderNum">#----</div>
                
                <div style="display: flex; justify-content: space-around; border-top: 1px solid rgba(0,0,0,0.06); padding-top: 12px; margin-top: 5px;">
                    <div>
                        <div style="font-size: 11px; color: var(--color-muted); font-weight: 700;">TIEMPO ESTIMADO</div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--color-charcoal-dark);" id="trackingTime">15 - 20 min</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: var(--color-muted); font-weight: 700;">MESA</div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--color-charcoal-dark);">{{ $mesa->numero_mesa }}</div>
                    </div>
                </div>
            </div>

            <!-- Flujo de estados en tiempo real -->
            <div class="tracking-status-flow">
                <div class="tracking-step" id="step-Nuevo">
                    <div class="step-dot"><i class="fa-solid fa-receipt"></i></div>
                    <div class="step-info">
                        <h4>Pedido Recibido</h4>
                        <p>Esperando confirmación en cocina</p>
                    </div>
                </div>
                <div class="tracking-step" id="step-En_Preparacion">
                    <div class="step-dot"><i class="fa-solid fa-pepper-hot"></i></div>
                    <div class="step-info">
                        <h4>En Preparación</h4>
                        <p>El chef está cocinando tu platillo</p>
                    </div>
                </div>
                <div class="tracking-step" id="step-Listo">
                    <div class="step-dot"><i class="fa-solid fa-bell"></i></div>
                    <div class="step-info">
                        <h4>¡Listo para Servir!</h4>
                        <p>Tu comida está lista para llevar a la mesa</p>
                    </div>
                </div>
                <div class="tracking-step" id="step-Entregado">
                    <div class="step-dot"><i class="fa-solid fa-square-check"></i></div>
                    <div class="step-info">
                        <h4>Entregado</h4>
                        <p>¡Buen provecho! Disfruta tu comida</p>
                    </div>
                </div>
            </div>

            <button class="btn-mrgiova-secondary" style="margin-top: 30px; width: 100%;" onclick="resetToMenu()">
                Volver al Menú
            </button>
        </div>
    </div>

    <!-- SCRIPT DE INTERACTIVIDAD -->
    <script>
        const tableId = {{ $mesa->id }};
        const tableNumber = {{ $mesa->numero_mesa }};
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
                else if (cat.nombre.includes('Tacos')) iconClass = 'fa-taco'; // Note: fa-taco may need pro, fallback to pepper or solid tacos
                else if (cat.nombre.includes('Bebidas')) iconClass = 'fa-glass-water';
                else if (cat.nombre.includes('Postres')) iconClass = 'fa-ice-cream';
                else if (cat.nombre.includes('Acompañamientos')) iconClass = 'fa-cookie';

                // fallback to general icons if needed
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

            let filteredCategories = JSON.parse(JSON.stringify(categories)); // Deep copy

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
                        <img src="${prod.imagen_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd'}" class="product-card-img" alt="${prod.nombre}">
                        <div class="product-card-info">
                            <div>
                                <h4 class="product-card-name">${prod.nombre}</h4>
                                <p class="product-card-desc">${prod.descripcion}</p>
                            </div>
                            <div class="product-card-footer">
                                <span class="product-card-price">${priceFormatted}</span>
                                <button class="product-card-btn"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                    `;
                    catSection.appendChild(card);
                });
                
                wrapper.appendChild(catSection);
            });
        }

        // Filtro buscador
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

            // Mostrar personalización si es Hamburguesa o Taco
            const nameLower = product.nombre.toLowerCase();
            const modGroup = document.getElementById('modifiersGroup');
            if (nameLower.includes('hamburguesa')) {
                modGroup.style.display = 'block';
                // Reset radios
                document.getElementsByName('termino')[1].checked = true; // Término medio por defecto
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
            
            // Actualizar total en el botón
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

            // Buscar si ya existe el producto con la misma nota en el carrito
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
            
            // Efecto toast simple
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
            let total = cart.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
            
            countEl.textContent = count;
            totalPreview.textContent = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(total);
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

            if (cart.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding: 80px 20px; color: var(--color-muted);">
                        <i class="fa-solid fa-basket-shopping" style="font-size:40px; margin-bottom:15px; color:var(--color-sand);"></i>
                        <p>Tu carrito está vacío.</p>
                    </div>`;
                document.getElementById('cartTotal').textContent = '$0';
                document.getElementById('btnConfirmarPedido').disabled = true;
                document.getElementById('btnConfirmarPedido').style.opacity = 0.5;
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
                    <img src="${item.imagen_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd'}" alt="${item.nombre}">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.cantidad}x ${item.nombre}</div>
                        ${item.notas_especiales ? `<div class="cart-item-mod"><i class="fa-solid fa-pencil"></i> ${item.notas_especiales}</div>` : ''}
                        <div class="cart-item-price">${formattedPrice}</div>
                    </div>
                    <button class="cart-item-delete" onclick="deleteCartItem(${index})"><i class="fa-solid fa-trash-can"></i></button>
                `;
                container.appendChild(row);
            });

            document.getElementById('cartTotal').textContent = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(grandTotal);
        }

        function deleteCartItem(index) {
            cart.splice(index, 1);
            renderCartItems();
            updateFloatingCart();
        }

        // CONFIRMAR PEDIDO Y ENVIAR AL SERVIDOR
        function confirmOrder() {
            const btn = document.getElementById('btnConfirmarPedido');
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Enviando a Cocina...`;

            const notes = document.getElementById('orderGeneralNotes').value.trim();

            const payload = {
                mesa_id: tableId,
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
                    // Limpiar carrito
                    cart = [];
                    updateFloatingCart();
                    closeCart();
                    
                    // Mostrar pantalla de tracking
                    showTracking(data.pedido_id, data.tiempo_estimado);
                    
                    // Guardar tracking ID en localstorage para persistir si refresca
                    localStorage.setItem('mrgiova_tracking_id', data.pedido_id);
                    localStorage.setItem('mrgiova_tracking_time', data.tiempo_estimado);
                } else {
                    alert('Error: ' + (data.error || 'No se pudo enviar el pedido.'));
                    btn.disabled = false;
                    btn.innerHTML = `Confirmar Pedido <i class="fa-solid fa-circle-check"></i>`;
                }
            })
            .catch(err => {
                console.error("Error al enviar el pedido", err);
                alert('Ocurrió un error al enviar tu pedido. Reintenta.');
                btn.disabled = false;
                btn.innerHTML = `Confirmar Pedido <i class="fa-solid fa-circle-check"></i>`;
            });
        }

        // SEGUIMIENTO DE PEDIDO EN TIEMPO REAL
        function showTracking(pedidoId, tiempoEstimado) {
            currentOrderTrackingId = pedidoId;
            document.getElementById('menuScreen').style.display = 'none';
            document.getElementById('trackingScreen').style.display = 'flex';
            document.getElementById('trackingOrderNum').textContent = `#${pedidoId}`;
            document.getElementById('trackingTime').textContent = tiempoEstimado || '15 - 20 min';

            // Iniciar Polling del estado del pedido
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
                        // Si no lo encuentra, limpiamos
                        resetToMenu();
                        return;
                    }

                    // Resetear clases de pasos
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

                    // Si ya se entregó o canceló, detener polling y limpiar localstorage tras unos segundos
                    if (order.estado === 'Entregado' || order.estado === 'Cancelado') {
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
            
            // Refrescar menú por si cambiaron cosas
            fetchMenu();
        }

        // TOAST HELPER
        function showToast(message) {
            // Eliminar toast anterior si existe
            let oldToast = document.querySelector('.toast-notification');
            if (oldToast) oldToast.remove();

            let toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `<i class="fa-solid fa-check-circle" style="color:var(--color-cempasuchil); font-size:18px;"></i> <span>${message}</span>`;
            document.body.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Remove after 3s
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }
    </script>
</body>
</html>
