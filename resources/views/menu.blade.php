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
    <style>
        /* Estilos para el Carrito Flotante FAB */
        .floating-cart-fab {
            position: absolute;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--color-terracotta);
            color: white;
            box-shadow: 0 4px 12px rgba(211, 84, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            z-index: 40;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .floating-cart-fab:hover {
            transform: scale(1.08);
            background-color: var(--color-terracotta-hover);
        }
        .floating-cart-fab:active {
            transform: scale(0.95);
        }
        .floating-cart-fab .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--color-cempasuchil);
            color: white;
            border-radius: 50%;
            min-width: 22px;
            height: 22px;
            padding: 0 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        
        /* Toast notification base */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background-color: #2C3E50;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            font-weight: 500;
            font-size: 14px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .toast-notification.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    </style>
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

            <!-- Botón Flotante de Carrito (FAB) -->
            <div class="floating-cart-fab" id="floatingCart" style="display: none;" onclick="openCart()" role="button" aria-label="Abrir carrito de compras">
                <i class="fa-solid fa-basket-shopping"></i>
                <span class="cart-badge" id="cartCount">0</span>
            </div>
        </div>

        <!-- MODAL DETALLE DE PRODUCTO -->
        <div class="mrgiova-modal" id="productModal" onclick="closeModalOnBgClick(event)">
            <div class="modal-content">
                <div class="modal-header-img" id="modalImg" style="background-image: url(''); background-size: cover; background-position: center;">
                    <button class="modal-close-btn" onclick="closeProductModal()" aria-label="Cerrar modal"><i class="fa-solid fa-xmark"></i></button>
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
                            <button class="quantity-btn" onclick="adjustModalQty(-1)" aria-label="Disminuir cantidad"><i class="fa-solid fa-minus"></i></button>
                            <span class="quantity-value" id="modalQty">1</span>
                            <button class="quantity-btn" onclick="adjustModalQty(1)" aria-label="Aumentar cantidad"><i class="fa-solid fa-plus"></i></button>
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
                <button class="cart-back-btn" onclick="closeCart()" aria-label="Volver al menú"><i class="fa-solid fa-arrow-left"></i></button>
                <h3><i class="fa-solid fa-basket-shopping" style="font-size:18px; opacity:0.8; margin-right:4px;"></i> Mi Carrito</h3>
                <span class="cart-header-count" id="cartHeaderCount">0 artículos</span>
            </header>

            <!-- Franja decorativa Talavera -->
            <div class="cart-talavera-strip"></div>

            <!-- Lista de ítems con scroll propio -->
            <div class="cart-items-list" id="cartItemsList">
                <!-- Se carga dinámicamente -->
            </div>

            <!-- Footer fijo: notas + total + botón -->
            <div class="cart-footer-panel">
                <div class="cart-notes-label"><i class="fa-solid fa-pencil" style="margin-right:5px; color:var(--color-terracotta);"></i>Notas para el pedido</div>
                <textarea class="notes-textarea" id="orderGeneralNotes" placeholder="Ej: sin sal, extra limón, alergia a nueces..."></textarea>

                <div class="cart-total-divider"><span>&#127798; Resumen</span></div>

                <div class="cart-total-row">
                    <div class="total-label">
                        <i class="fa-solid fa-coins"></i>
                        Total a pagar
                    </div>
                    <div class="total-amount" id="cartTotal">$0</div>
                </div>

                <button class="btn-mrgiova" id="btnConfirmarPedido" onclick="confirmOrder()">
                    <i class="fa-solid fa-circle-check"></i> Confirmar Pedido
                </button>
            </div>
        </div>

        <!-- PANTALLA: SEGUIMIENTO / TRACKING -->
        <div class="tracking-screen" id="trackingScreen" style="display: none;">
            <div class="success-icon-wrapper" id="trackingSuccessIcon">
                <i class="fa-solid fa-check"></i>
            </div>
            <h2 class="tracking-title" id="trackingTitle">¡Pedido Enviado!</h2>
            <p class="tracking-desc" id="trackingDesc">Tu pedido ha sido recibido correctamente en la cocina de Mr.Giova.</p>

            <!-- Alerta de Cancelación -->
            <div id="cancellationAlert" style="display: none; background-color: #FDEDEC; border: 2px solid #E74C3C; border-radius: var(--border-radius-sm); padding: 20px; text-align: center; margin-bottom: 25px; width: 100%; color: #C0392B; animation: fadeIn 0.3s ease;">
                <i class="fa-solid fa-circle-xmark" style="font-size: 44px; margin-bottom: 12px; color: #E74C3C;"></i>
                <h3 style="margin-bottom: 6px; font-weight: 800; font-family: var(--font-sans); font-size: 18px;">Pedido Cancelado</h3>
                <p style="font-size: 13px; font-weight: 600; line-height: 1.5; color: #C0392B;">Tu pedido ha sido cancelado por el personal del restaurante.</p>
            </div>

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
            <div class="tracking-status-flow" id="trackingStatusFlow">
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
        window.tableId = {{ $mesa->id }};
        window.tableNumber = {{ $mesa->numero_mesa }};
    </script>
    <script src="{{ asset('js/menu-client.js') }}"></script>
</body>
</body>
</html>
