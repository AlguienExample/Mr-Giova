const tableId = window.mesaConfig.id;
        const tableNumber = window.mesaConfig.numero_mesa;
        let categories = [];
        let allProducts = [];
        let activeCategory = 'all';

        let cart = [];
        let currentModalProduct = null;
        let currentOrderTrackingId = null;
        let currentOrderTrackingTime = '15 - 20 min';
        let trackingInterval = null;
        let hasPlayedReadySound = false;
        let audioCtx = null;

        function initAudioContext() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
        }
        document.addEventListener('click', initAudioContext, { once: false });

        function playReadyNotificationSound() {
            try {
                initAudioContext();
                if (!audioCtx) return;

                const now = audioCtx.currentTime;
                // Chime armónico: C5 (523.25 Hz) -> E5 (659.25 Hz) -> G5 (783.99 Hz) -> C6 (1046.50 Hz)
                const notes = [523.25, 659.25, 783.99, 1046.50];
                notes.forEach((freq, index) => {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + index * 0.12);

                    gain.gain.setValueAtTime(0, now + index * 0.12);
                    gain.gain.linearRampToValueAtTime(0.35, now + index * 0.12 + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + index * 0.12 + 0.5);

                    osc.connect(gain);
                    gain.connect(audioCtx.destination);

                    osc.start(now + index * 0.12);
                    osc.stop(now + index * 0.12 + 0.5);
                });
            } catch (e) {
                console.error('Error al reproducir notificación sonora:', e);
            }
        }

        const categoryPhrases = {
            'all': '"La buena comida es el fundamento de la verdadera felicidad."',
            'hamburguesa': '"La vida es demasiado corta para no comerse una buena hamburguesa."',
            'bebida': '"Refresca tu día con nuestra mejor selección de bebidas."',
            'postre': '"Siempre hay espacio para un postre delicioso."',
            'acompañamiento': '"El complemento perfecto para una comida inolvidable."',
            'taco': '"Un buen taco es como un abrazo para el alma."'
        };

        window.addEventListener('DOMContentLoaded', () => {
            fetchMenu();
            checkExistingTracking();
            
            try {
                const savedCart = localStorage.getItem('mrgiova_cart');
                if (savedCart) {
                    cart = JSON.parse(savedCart);
                    updateFloatingCart();
                }
            } catch (e) {
                console.error('Error loading cart', e);
            }
            
            const phrases = Object.values(categoryPhrases);
            const titleEl = document.querySelector('.menu-hero-content h3');
            if (titleEl) {
                titleEl.textContent = phrases[Math.floor(Math.random() * phrases.length)];
            }
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

            let catName = 'all';
            if (catId !== 'all') {
                const catObj = categories.find(c => c.id == catId);
                if (catObj) catName = catObj.nombre.toLowerCase();
            }
            
            let newPhrase = categoryPhrases['all'];
            for (const [key, phrase] of Object.entries(categoryPhrases)) {
                if (catName.includes(key)) {
                    newPhrase = phrase;
                    break;
                }
            }
            
            const titleEl = document.querySelector('.menu-hero-content h3');
            if (titleEl) {
                titleEl.style.opacity = 0;
                setTimeout(() => {
                    titleEl.textContent = newPhrase;
                    titleEl.style.opacity = 1;
                }, 300);
            }
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

            const isAllMode = (activeCategory === 'all' && !searchTerm);

            filteredCategories.forEach(cat => {
                if (cat.productos.length === 0) return;

                const section = document.createElement('div');
                section.className = 'menu-category-section';

                if (isAllMode) {
                    section.innerHTML = `
                        <div class="menu-category-header">
                            <h3 class="menu-section-title">${cat.nombre}</h3>
                            <div class="menu-carousel-controls">
                                <button type="button" class="carousel-arrow prev" onclick="scrollCarousel('carousel-${cat.id}', -280)" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                                <button type="button" class="carousel-arrow next" onclick="scrollCarousel('carousel-${cat.id}', 280)" aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>
                    `;

                    const horizontalWrap = document.createElement('div');
                    horizontalWrap.className = 'menu-products-horizontal';
                    horizontalWrap.id = `carousel-${cat.id}`;

                    cat.productos.forEach((prod, index) => {
                        horizontalWrap.appendChild(createProductCard(prod, index));
                    });

                    section.appendChild(horizontalWrap);
                } else {
                    section.innerHTML = `<h3 class="menu-section-title">${cat.nombre}</h3>`;
                    const grid = document.createElement('div');
                    grid.className = 'menu-products-grid';

                    cat.productos.forEach((prod, index) => {
                        grid.appendChild(createProductCard(prod, index));
                    });

                    section.appendChild(grid);
                }

                wrapper.appendChild(section);
            });
        }

        function createProductCard(prod, index) {
            const priceFormatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(prod.precio);
            const img = prod.imagen_url || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd';

            const card = document.createElement('div');
            card.className = 'product-card';
            card.style.animationDelay = `${index * 0.05}s`;
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
            return card;
        }

        function scrollCarousel(containerId, amount) {
            const el = document.getElementById(containerId);
            if (el) {
                el.scrollBy({ left: amount, behavior: 'smooth' });
            }
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

            saveCart();
            updateFloatingCart();
            
            const cartIcon = document.querySelector('.floating-cart-icon');
            if(cartIcon) {
                cartIcon.classList.remove('animate-pop');
                void cartIcon.offsetWidth;
                cartIcon.classList.add('animate-pop');
            }

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

        const cartPhrases = [
            '"La buena comida vale cada centavo invertido."',
            '"Comer bien es la mejor inversión del día."',
            '"El precio de la felicidad: una buena comida."',
            '"Gastar en comida no es gasto, es una experiencia."',
            '"El dinero bien gastado sabe a comida deliciosa."',
            '"La mejor economía: comer lo que te gusta."',
            '"No pongas precio al buen sabor, ponle amor."',
            '"Invertir en un buen plato siempre da retorno."',
        ];

        function openCart() {
            document.getElementById('cartScreen').classList.add('open');
            document.body.style.overflow = 'hidden';
            renderCartItems();

            const phraseEl = document.getElementById('cartHeaderPhrase');
            if (phraseEl) {
                phraseEl.textContent = cartPhrases[Math.floor(Math.random() * cartPhrases.length)];
            }
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
            saveCart();
            renderCartItems();
            updateFloatingCart();
        }

        function saveCart() {
            localStorage.setItem('mrgiova_cart', JSON.stringify(cart));
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
                    saveCart();
                    updateFloatingCart();
                    closeCart();
                    startOrderTracking(data.pedido_id, data.tiempo_estimado);
                    openActiveOrderTracking();
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

        function startOrderTracking(pedidoId, tiempoEstimado) {
            currentOrderTrackingId = pedidoId;
            currentOrderTrackingTime = tiempoEstimado || '15 - 20 min';
            hasPlayedReadySound = false;

            localStorage.setItem('mrgiova_tracking_id', pedidoId);
            localStorage.setItem('mrgiova_tracking_time', currentOrderTrackingTime);

            const orderBar = document.getElementById('floatingOrderBar');
            if (orderBar) orderBar.style.display = 'flex';

            updateFloatingCart();

            pollOrderStatus();
            if (trackingInterval) clearInterval(trackingInterval);
            trackingInterval = setInterval(pollOrderStatus, 3000);
        }

        function openActiveOrderTracking() {
            if (!currentOrderTrackingId) return;

            document.getElementById('menuScreen').style.display = 'none';
            document.getElementById('floatingCart').style.display = 'none';
            const orderBar = document.getElementById('floatingOrderBar');
            if (orderBar) orderBar.style.display = 'none';

            document.getElementById('trackingScreen').style.display = 'flex';
            document.getElementById('trackingOrderNum').textContent = `#${currentOrderTrackingId}`;
            document.getElementById('trackingTime').textContent = currentOrderTrackingTime;
            document.body.style.overflow = 'hidden';

            pollOrderStatus();
        }

        function returnToMenuFromTracking() {
            document.getElementById('trackingScreen').style.display = 'none';
            document.getElementById('menuScreen').style.display = 'block';
            document.body.style.overflow = '';

            const orderBar = document.getElementById('floatingOrderBar');
            if (orderBar && currentOrderTrackingId) {
                orderBar.style.display = 'flex';
            }
            updateFloatingCart();
        }

        function checkExistingTracking() {
            const savedId = localStorage.getItem('mrgiova_tracking_id');
            const savedTime = localStorage.getItem('mrgiova_tracking_time');
            if (savedId) {
                startOrderTracking(parseInt(savedId), savedTime);
            }
        }

        function pollOrderStatus() {
            if (!currentOrderTrackingId) return;

            fetch(`/api/pedidos/${currentOrderTrackingId}`)
                .then(res => res.json())
                .then(order => {
                    if (order.error) {
                        clearTrackingSession();
                        return;
                    }

                    const steps = ['Nuevo', 'En_Preparacion', 'Listo', 'Entregado'];
                    const currentStepIndex = steps.indexOf(order.estado);

                    // Porcentaje de barra de progreso
                    const progressMap = { 'Nuevo': 10, 'En_Preparacion': 45, 'Listo': 75, 'Entregado': 100 };
                    const progressFill = document.getElementById('trackingProgressFill');
                    if (progressFill) {
                        progressFill.style.width = (progressMap[order.estado] || 10) + '%';
                    }

                    const chipLabels = {
                        'Nuevo': 'Pendiente',
                        'En_Preparacion': 'Cocinando',
                        'Listo': '¡Listo!',
                        'Entregado': 'Entregado'
                    };

                    steps.forEach((step, idx) => {
                        const stepEl = document.getElementById(`step-${step}`);
                        if (stepEl) {
                            stepEl.classList.remove('active', 'completed');
                            const chip = stepEl.querySelector('.step-status-chip');
                            if (idx < currentStepIndex) {
                                stepEl.classList.add('completed');
                                if (chip) { chip.textContent = 'Completado'; chip.dataset.state = 'done'; }
                            } else if (idx === currentStepIndex) {
                                stepEl.classList.add('active');
                                if (chip) { chip.textContent = chipLabels[step] || 'En curso'; chip.dataset.state = 'active'; }
                            } else {
                                if (chip) { chip.textContent = ''; chip.dataset.state = ''; }
                            }
                        }
                    });

                    const orderBar = document.getElementById('floatingOrderBar');
                    const orderTitle = document.getElementById('floatingOrderTitle');
                    const orderSubtitle = document.getElementById('floatingOrderSubtitle');
                    const orderIcon = document.getElementById('floatingOrderIcon');

                    const statusMap = {
                        'Nuevo': 'Pedido recibido',
                        'En_Preparacion': 'En preparación',
                        'Listo': '¡Listo para servir!',
                        'Entregado': 'Entregado'
                    };
                    const estadoText = statusMap[order.estado] || order.estado;

                    if (orderBar && document.getElementById('trackingScreen').style.display !== 'flex') {
                        orderBar.style.display = 'flex';

                        if (order.estado === 'Listo') {
                            orderBar.classList.add('is-ready');
                            if (orderTitle) orderTitle.textContent = '¡Tu pedido está listo!';
                            if (orderSubtitle) orderSubtitle.textContent = '🔔 Listo para servir a la mesa';
                            if (orderIcon) orderIcon.innerHTML = `<i class="fa-solid fa-bell"></i>`;

                            if (!hasPlayedReadySound) {
                                hasPlayedReadySound = true;
                                playReadyNotificationSound();
                                showToast('🔔 ¡Tu pedido está listo para servir!');
                            }
                        } else {
                            orderBar.classList.remove('is-ready');
                            if (orderTitle) orderTitle.textContent = 'Ver el proceso de mi pedido';
                            if (orderSubtitle) orderSubtitle.textContent = `#${order.id} • ${estadoText}`;
                            if (orderIcon) orderIcon.innerHTML = `<i class="fa-solid fa-fire-burner"></i>`;
                        }
                    }

                    if (order.estado === 'Entregado' || order.estado === 'Cancelado') {
                        setTimeout(() => {
                            if (currentOrderTrackingId === order.id) {
                                clearTrackingSession();
                            }
                        }, 6000);
                    }
                })
                .catch(err => console.error("Error al consultar estado del pedido", err));
        }

        function clearTrackingSession() {
            if (trackingInterval) clearInterval(trackingInterval);
            trackingInterval = null;
            currentOrderTrackingId = null;
            hasPlayedReadySound = false;

            localStorage.removeItem('mrgiova_tracking_id');
            localStorage.removeItem('mrgiova_tracking_time');

            const orderBar = document.getElementById('floatingOrderBar');
            if (orderBar) {
                orderBar.style.display = 'none';
                orderBar.classList.remove('is-ready');
            }

            document.body.classList.remove('has-active-order');
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