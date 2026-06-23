<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Mr.Giova - Menú del Cliente</title>
    <link rel="stylesheet" href="{{ asset('css/menu-client.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="menu-page">
    <div class="kfc-stripe-top"></div>

    <div class="menu-app" id="app">
        <!-- HEADER -->
        <header class="menu-header">
            <div class="menu-header-brand">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Mr.Giova" class="menu-logo" width="48" height="48">                <div class="menu-header-title">
                    <h2>Mr.<span>Giova</span></h2>
                </div>
            </div>
            <div class="menu-header-actions">
                <div class="menu-table-badge" id="tableBadge">
                    Mesa {{ $mesa->numero_mesa }}
                </div>
                <button class="menu-cart-icon-btn" id="headerCartBtn" onclick="openCart()" aria-label="Ver carrito">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <span class="cart-badge" id="headerCartBadge" style="display:none;">0</span>
                </button>
            </div>
        </header>

        <!-- PANTALLA PRINCIPAL: MENÚ -->
        <div id="menuScreen">  
            <div class="menu-hero">
                <h3>¡Sabor que Enamora!</h3>
                <p>Escanea, pide y disfruta el auténtico sazón de Mr.Giova directamente en tu mesa.</p>       
            </div>

            <div class="menu-search-wrap">
                <div class="menu-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Buscar en el menú..." oninput="filterProducts()">
                </div>
            </div>

            <div class="menu-categories">
                <div class="menu-categories-scroll" id="categoriesScroll">
                    <div class="category-chip active" id="chip-all" onclick="selectCategory('all')">
                        <i class="fa-solid fa-utensils"></i> Todo
                    </div>
                </div>
            </div>

            <div class="menu-products" id="productsWrapper">
                <div class="menu-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Cargando menú delicioso...</p>
                </div>
            </div>

            <div class="floating-cart-bar" id="floatingCart" style="display: none;" onclick="openCart()">
                <div class="floating-cart-left">
                    <div class="floating-cart-icon">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <span class="floating-cart-count" id="cartCount">0</span>
                    </div>
                    <span class="floating-cart-label"><span id="cartItemsLabel">0</span> productos en tu pedido</span>
                </div>
                <div class="floating-cart-right">
                    <span class="floating-cart-total" id="cartTotalPreview">$0</span>
                    <button class="floating-cart-btn" type="button">Ver carrito</button>
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
                    <p class="modal-description" id="modalDesc">Descripción del producto.</p>

                    <div class="modifier-group" id="modifiersGroup" style="display: none;">
                        <div class="modifier-group-title">Personaliza tu término</div>
                        <div class="modifier-options">
                            <label class="modifier-option">
                                <input type="radio" name="termino" value="Rojo"> Término Rojo
                            </label>
                            <label class="modifier-option">
                                <input type="radio" name="termino" value="Término Medio" checked> Término Medio
                            </label>
                            <label class="modifier-option">
                                <input type="radio" name="termino" value="Bien Cocido"> Bien Cocido
                            </label>
                        </div>
                    </div>

                    <div class="modifier-group">
                        <div class="modifier-group-title">Notas para la cocina</div>
                        <textarea class="notes-textarea" id="modalNotes" placeholder="Ej: Sin cebolla, extra salsa..."></textarea>
                    </div>

                    <div class="quantity-control-wrapper">
                        <span>Cantidad</span>
                        <div class="quantity-control">
                            <button class="quantity-btn" type="button" onclick="adjustModalQty(-1)"><i class="fa-solid fa-minus"></i></button>
                            <span class="quantity-value" id="modalQty">1</span>
                            <button class="quantity-btn" type="button" onclick="adjustModalQty(1)"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>

                    <button class="btn-kfc-primary" type="button" onclick="addProductToCart()">
                        Agregar al carrito • <span id="modalBtnTotal">$0.00</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- PANTALLA: CARRITO -->
        <div class="cart-screen" id="cartScreen">
            <header class="cart-screen-header">
                <button class="cart-back-btn" type="button" onclick="closeCart()"><i class="fa-solid fa-arrow-left"></i></button>
                <h3>Mi Carrito</h3>
            </header>

            <div class="cart-items-list" id="cartItemsList"></div>

            <div class="cart-footer">
                <div class="cart-footer-notes">
                    <div class="modifier-group-title">Notas del pedido</div>
                    <textarea class="notes-textarea" id="orderGeneralNotes" placeholder="Notas generales para toda la orden..."></textarea>
                </div>
                <div class="cart-total-row">
                    <span class="cart-total-label">Total a pagar</span>
                    <span class="cart-total-value" id="cartTotal">$0.00</span>
                </div>
                <button class="btn-kfc-primary" type="button" onclick="confirmOrder()" id="btnConfirmarPedido">
                    Confirmar pedido <i class="fa-solid fa-circle-check"></i>
                </button>
            </div>
        </div>

        <!-- PANTALLA: SEGUIMIENTO -->
        <div class="tracking-screen" id="trackingScreen">
            <div class="success-icon-wrapper">
                <i class="fa-solid fa-check"></i>
            </div>
            <h2 class="tracking-title">¡Pedido Enviado!</h2>
            <p class="tracking-desc">Tu pedido ha sido recibido correctamente en la cocina de Mr.Giova.</p>

            <div class="tracking-order-box">
                <div class="tracking-order-num-label">Número de pedido</div>
                <div class="tracking-order-num" id="trackingOrderNum">#----</div>
                <div class="tracking-meta">
                    <div>
                        <div class="tracking-meta-label">Tiempo estimado</div>
                        <div class="tracking-meta-value" id="trackingTime">15 - 20 min</div>
                    </div>
                    <div>
                        <div class="tracking-meta-label">Mesa</div>
                        <div class="tracking-meta-value">{{ $mesa->numero_mesa }}</div>
                    </div>
                </div>
            </div>

            <div class="tracking-status-flow">
                <div class="tracking-step" id="step-Nuevo">
                    <div class="step-dot"><i class="fa-solid fa-receipt"></i></div>
                    <div class="step-info">
                        <h4>Pedido recibido</h4>
                        <p>Esperando confirmación en cocina</p>
                    </div>
                </div>
                <div class="tracking-step" id="step-En_Preparacion">
                    <div class="step-dot"><i class="fa-solid fa-fire-burner"></i></div>
                    <div class="step-info">
                        <h4>En preparación</h4>
                        <p>El chef está cocinando tu platillo</p>
                    </div>
                </div>
                <div class="tracking-step" id="step-Listo">
                    <div class="step-dot"><i class="fa-solid fa-bell"></i></div>
                    <div class="step-info">
                        <h4>¡Listo para servir!</h4>
                        <p>Tu comida está lista para la mesa</p>
                    </div>
                </div>
                <div class="tracking-step" id="step-Entregado">
                    <div class="step-dot"><i class="fa-solid fa-square-check"></i></div>
                    <div class="step-info">
                        <h4>Entregado</h4>
                        <p>¡Buen provecho!</p>
                    </div>
                </div>
            </div>

            <button class="btn-kfc-secondary" type="button" style="margin-top: 24px; max-width: 400px;" onclick="resetToMenu()">
                Volver al menú
            </button>
        </div>
    </div>

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

        window.addEventListener('DOMContentLoaded', () => {
            fetchMenu();
            checkExistingTracking();
        });

        function fetchMenu() {
            fetch('/api/productos')
                .then(res => res.json())
                .then(data => {
                    categories = data;
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
                        `<div class="menu-empty">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <p>Hubo un problema al cargar el menú. Reintente por favor.</p>
                        </div>`;
                });
        }

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
                else if (cat.nombre.toLowerCase().includes('taco')) iconClass = 'fa-pepper-hot';
                else if (cat.nombre.includes('Bebidas')) iconClass = 'fa-glass-water';
                else if (cat.nombre.includes('Postres')) iconClass = 'fa-ice-cream';
                else if (cat.nombre.includes('Acompañamientos')) iconClass = 'fa-cookie';

                container.innerHTML += `
                    <div class="category-chip ${activeCategory == cat.id ? 'active' : ''}" id="chip-${cat.id}" onclick="selectCategory(${cat.id})">
                        <i class="fa-solid ${iconClass}"></i> ${cat.nombre}
                    </div>
                `;
            });
        }

        function selectCategory(catId) {
            activeCategory = catId;
            document.querySelectorAll('.category-chip').forEach(el => el.classList.remove('active'));
            document.getElementById(`chip-${catId}`).classList.add('active');
            renderMenu(document.getElementById('searchInput').value);
        }

        function renderMenu(searchTerm = '') {
            const wrapper = document.getElementById('productsWrapper');
            wrapper.innerHTML = '';

            let filteredCategories = JSON.parse(JSON.stringify(categories));

            if (searchTerm) {
                filteredCategories = filteredCategories.map(cat => {
                    cat.productos = cat.productos.filter(prod =>
                        prod.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        prod.descripcion.toLowerCase().includes(searchTerm.toLowerCase())
                    );
                    return cat;
                }).filter(cat => cat.productos.length > 0);
            }

            if (activeCategory !== 'all') {
                filteredCategories = filteredCategories.filter(cat => cat.id == activeCategory);
            }

            if (filteredCategories.length === 0) {
                wrapper.innerHTML = `
                    <div class="menu-empty">
                        <i class="fa-solid fa-face-frown"></i>
                        <p>No encontramos platillos que coincidan.</p>
                    </div>`;
                return;
            }

            filteredCategories.forEach(cat => {
                if (cat.productos.length === 0) return;

                const section = document.createElement('div');
                section.innerHTML = `<h3 class="menu-section-title">${cat.nombre}</h3>`;

                const grid = document.createElement('div');
                grid.className = 'menu-products-grid';

                cat.productos.forEach(prod => {
                    const priceFormatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(prod.precio);
                    const img = prod.imagen_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd';

                    const card = document.createElement('div');
                    card.className = 'product-card';
                    card.onclick = () => openProductModal(prod);
                    card.innerHTML = `
                        <div class="product-card-img-wrap">
                            <img src="${img}" class="product-card-img" alt="${prod.nombre}" loading="lazy">
                        </div>
                        <div class="product-card-body">
                            <h4 class="product-card-name">${prod.nombre}</h4>
                            <p class="product-card-desc">${prod.descripcion}</p>
                            <div class="product-card-footer">
                                <span class="product-card-price">${priceFormatted}</span>
                                <button class="product-card-add" type="button" aria-label="Agregar"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                    `;
                    grid.appendChild(card);
                });

                section.appendChild(grid);
                wrapper.appendChild(section);
            });
        }

        function filterProducts() {
            renderMenu(document.getElementById('searchInput').value);
        }

        function openProductModal(product) {
            currentModalProduct = product;
            document.getElementById('modalImg').style.backgroundImage = `url('${product.imagen_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd'}')`;
            document.getElementById('modalName').textContent = product.nombre;
            document.getElementById('modalDesc').textContent = product.descripcion;

            const formattedPrice = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(product.precio);
            document.getElementById('modalPrice').textContent = formattedPrice;
            document.getElementById('modalBtnTotal').textContent = formattedPrice;

            const modGroup = document.getElementById('modifiersGroup');
            if (product.nombre.toLowerCase().includes('hamburguesa')) {
                modGroup.style.display = 'block';
                document.getElementsByName('termino')[1].checked = true;
            } else {
                modGroup.style.display = 'none';
            }

            document.getElementById('modalNotes').value = '';
            document.getElementById('modalQty').textContent = '1';
            document.getElementById('productModal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.remove('open');
            document.body.style.overflow = '';
            currentModalProduct = null;
        }

        function closeModalOnBgClick(e) {
            if (e.target.id === 'productModal') closeProductModal();
        }

        function adjustModalQty(val) {
            let current = parseInt(document.getElementById('modalQty').textContent);
            let newVal = Math.max(1, current + val);
            document.getElementById('modalQty').textContent = newVal;

            const total = currentModalProduct.precio * newVal;
            document.getElementById('modalBtnTotal').textContent = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(total);
        }

        function addProductToCart() {
            let qty = parseInt(document.getElementById('modalQty').textContent);
            let note = document.getElementById('modalNotes').value.trim();

            let modifier = '';
            if (currentModalProduct.nombre.toLowerCase().includes('hamburguesa')) {
                for (let r of document.getElementsByName('termino')) {
                    if (r.checked) { modifier = r.value; break; }
                }
            }

            let extraDetails = [];
            if (modifier) extraDetails.push(modifier);
            if (note) extraDetails.push(note);
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
            showToast(`¡Agregado: ${qty}x ${currentModalProduct.nombre}!`);
        }

        function updateFloatingCart() {
            const preview = document.getElementById('floatingCart');
            const countEl = document.getElementById('cartCount');
            const itemsLabel = document.getElementById('cartItemsLabel');
            const totalPreview = document.getElementById('cartTotalPreview');
            const headerBadge = document.getElementById('headerCartBadge');

            if (cart.length === 0) {
                preview.style.display = 'none';
                headerBadge.style.display = 'none';
                return;
            }

            preview.style.display = 'flex';
            const count = cart.reduce((acc, item) => acc + item.cantidad, 0);
            const total = cart.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);

            countEl.textContent = count;
            itemsLabel.textContent = count;
            totalPreview.textContent = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(total);

            headerBadge.textContent = count;
            headerBadge.style.display = 'flex';
        }

        function openCart() {
            document.getElementById('cartScreen').classList.add('open');
            document.body.style.overflow = 'hidden';
            renderCartItems();
        }

        function closeCart() {
            document.getElementById('cartScreen').classList.remove('open');
            document.body.style.overflow = '';
        }

        function renderCartItems() {
            const container = document.getElementById('cartItemsList');
            container.innerHTML = '';

            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="cart-empty">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <p>Tu carrito está vacío.</p>
                    </div>`;
                document.getElementById('cartTotal').textContent = '$0';
                document.getElementById('btnConfirmarPedido').disabled = true;
                document.getElementById('btnConfirmarPedido').style.opacity = '0.5';
                return;
            }

            document.getElementById('btnConfirmarPedido').disabled = false;
            document.getElementById('btnConfirmarPedido').style.opacity = '1';

            let grandTotal = 0;

            cart.forEach((item, index) => {
                const subt = item.precio * item.cantidad;
                grandTotal += subt;
                const formattedPrice = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(subt);

                const row = document.createElement('div');
                row.className = 'cart-item-row';
                row.innerHTML = `
                    <img src="${item.imagen_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd'}" alt="${item.nombre}">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.cantidad}x ${item.nombre}</div>
                        ${item.notas_especiales ? `<div class="cart-item-mod"><i class="fa-solid fa-pencil"></i>${item.notas_especiales}</div>` : ''}
                        <div class="cart-item-price">${formattedPrice}</div>
                    </div>
                    <button class="cart-item-delete" type="button" onclick="deleteCartItem(${index})" aria-label="Eliminar"><i class="fa-solid fa-trash-can"></i></button>
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

        function confirmOrder() {
            const btn = document.getElementById('btnConfirmarPedido');
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Enviando...`;

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
                    cart = [];
                    updateFloatingCart();
                    closeCart();
                    showTracking(data.pedido_id, data.tiempo_estimado);
                    localStorage.setItem('mrgiova_tracking_id', data.pedido_id);
                    localStorage.setItem('mrgiova_tracking_time', data.tiempo_estimado);
                } else {
                    alert('Error: ' + (data.error || 'No se pudo enviar el pedido.'));
                    btn.disabled = false;
                    btn.innerHTML = `Confirmar pedido <i class="fa-solid fa-circle-check"></i>`;
                }
            })
            .catch(err => {
                console.error("Error al enviar el pedido", err);
                alert('Ocurrió un error al enviar tu pedido. Reintenta.');
                btn.disabled = false;
                btn.innerHTML = `Confirmar pedido <i class="fa-solid fa-circle-check"></i>`;
            });
        }

        function showTracking(pedidoId, tiempoEstimado) {
            currentOrderTrackingId = pedidoId;
            document.getElementById('menuScreen').style.display = 'none';
            document.getElementById('floatingCart').style.display = 'none';
            document.getElementById('trackingScreen').style.display = 'flex';
            document.getElementById('trackingOrderNum').textContent = `#${pedidoId}`;
            document.getElementById('trackingTime').textContent = tiempoEstimado || '15 - 20 min';
            document.body.style.overflow = 'hidden';

            pollOrderStatus();
            if (trackingInterval) clearInterval(trackingInterval);
            trackingInterval = setInterval(pollOrderStatus, 3000);
        }

        function checkExistingTracking() {
            const savedId = localStorage.getItem('mrgiova_tracking_id');
            const savedTime = localStorage.getItem('mrgiova_tracking_time');
            if (savedId) showTracking(parseInt(savedId), savedTime);
        }

        function pollOrderStatus() {
            if (!currentOrderTrackingId) return;

            fetch(`/api/pedidos/${currentOrderTrackingId}`)
                .then(res => res.json())
                .then(order => {
                    if (order.error) { resetToMenu(); return; }

                    const steps = ['Nuevo', 'En_Preparacion', 'Listo', 'Entregado'];
                    const currentStepIndex = steps.indexOf(order.estado);

                    steps.forEach((step, idx) => {
                        const stepEl = document.getElementById(`step-${step}`);
                        stepEl.classList.remove('active', 'completed');
                        if (idx < currentStepIndex) stepEl.classList.add('completed');
                        else if (idx === currentStepIndex) stepEl.classList.add('active');
                    });

                    if (order.estado === 'Entregado' || order.estado === 'Cancelado') {
                        clearInterval(trackingInterval);
                        localStorage.removeItem('mrgiova_tracking_id');
                        localStorage.removeItem('mrgiova_tracking_time');
                    }
                })
                .catch(err => console.error("Error al consultar estado del pedido", err));
        }

        function resetToMenu() {
            if (trackingInterval) clearInterval(trackingInterval);
            currentOrderTrackingId = null;
            localStorage.removeItem('mrgiova_tracking_id');
            localStorage.removeItem('mrgiova_tracking_time');

            document.getElementById('trackingScreen').style.display = 'none';
            document.getElementById('menuScreen').style.display = 'block';
            document.body.style.overflow = '';
            fetchMenu();
        }

        function showToast(message) {
            let oldToast = document.querySelector('.toast-notification');
            if (oldToast) oldToast.remove();

            let toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `<i class="fa-solid fa-check-circle"></i><span>${message}</span>`;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 50);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 2800);
        }
    </script>
</body>
</html>
