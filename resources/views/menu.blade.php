<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo - Menú del Cliente</title>
    <link rel="stylesheet" href="{{ asset('css/menu-client.css') }}">
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
</head>
<body class="menu-page">
    <div class="kfc-stripe-top"></div>

    <div class="menu-app" id="app">
        <!-- HEADER -->
        <header class="menu-header">
            <div class="menu-header-top">
                <div class="menu-header-brand">
                    <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo" class="menu-header-real-logo">
                    <div class="menu-header-title">
                        <h2>Sabor<span> a Pueblo</span></h2>
                    </div>
                </div>
                <div class="menu-header-actions">
                    <button class="theme-toggle-btn" type="button" onclick="toggleTheme()" aria-label="Cambiar a modo claro" title="Cambiar a modo claro">
                        <i class="fa-solid fa-sun"></i>
                    </button>
                    <div class="menu-table-badge" id="tableBadge">
                        Mesa {{ $mesa->numero_mesa }}
                    </div>
                    <button class="menu-cart-icon-btn" id="headerCartBtn" onclick="openCart()" aria-label="Ver carrito">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <span class="cart-badge" id="headerCartBadge" style="display:none;">0</span>
                    </button>
                </div>
            </div>
            
            <div class="menu-categories-bar">
                <div class="menu-categories-scroll-header" id="categoriesScroll">
                    <div class="category-chip active" id="chip-all" onclick="selectCategory('all')">
                        <i class="fa-solid fa-utensils"></i> Todo
                    </div>
                </div>
            </div>
        </header>

        <!-- PANTALLA PRINCIPAL: MENÚ -->
        <div id="menuScreen">  
            <div class="menu-hero">
                <div class="menu-hero-overlay"></div>
                <div class="floating-decor float-1"><i class="fa-solid fa-drumstick-bite"></i></div>
                <div class="floating-decor float-2"><i class="fa-solid fa-fire-burner"></i></div>
                <div class="floating-decor float-3"><i class="fa-solid fa-wine-glass"></i></div>
                <div class="floating-decor float-4"><i class="fa-solid fa-pepper-hot"></i></div>
                <div class="menu-hero-content">
                    <div style="margin-bottom:12px;">
                        <span class="badge-parrilla-gold"><i class="fa-solid fa-fire-flame-curved"></i> Especialidad en Cortes & Parrilla al Carbón</span>
                    </div>
                    <h3>"La buena comida es el fundamento de la verdadera felicidad."</h3>
                    <p>Déjate llevar por los sabores gourmet y disfruta de un momento inolvidable en Sabor a Pueblo.</p>
                </div>
            </div>

            <div class="menu-search-wrap">
                <div class="menu-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Buscar en el menú..." oninput="filterProducts()">
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

            <!-- BARRA FLOTANTE SEGUIMIENTO DE PEDIDO -->
            <div class="floating-order-bar" id="floatingOrderBar" style="display: none;" onclick="openActiveOrderTracking()">
                <div class="floating-order-left">
                    <div class="floating-order-icon" id="floatingOrderIcon">
                        <i class="fa-solid fa-bell-concierge"></i>
                    </div>
                    <div class="floating-order-info">
                        <span class="floating-order-title" id="floatingOrderTitle">Ver el proceso de mi pedido</span>
                        <span class="floating-order-subtitle" id="floatingOrderSubtitle">Consultando estado...</span>
                    </div>
                </div>
                <div class="floating-order-right">
                    <button class="floating-order-btn" type="button">
                        <span>Ver proceso</span> <i class="fa-solid fa-chevron-right"></i>
                    </button>
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
            <!-- Fondo oscuro con carritos animados -->
            <div class="cart-bg-decor cb-1"><i class="fa-solid fa-cart-shopping"></i></div>
            <div class="cart-bg-decor cb-2"><i class="fa-solid fa-basket-shopping"></i></div>
            <div class="cart-bg-decor cb-3"><i class="fa-solid fa-bag-shopping"></i></div>
            <div class="cart-bg-decor cb-4"><i class="fa-solid fa-cart-plus"></i></div>
            <div class="cart-bg-decor cb-5"><i class="fa-solid fa-cart-shopping"></i></div>

            <div class="cart-screen-inner">
                <!-- Header con frase aleatoria -->
                <header class="cart-screen-header">
                    <button class="cart-back-btn" type="button" onclick="closeCart()">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <div class="cart-header-center">
                        <h3>Mi Carrito</h3>
                        <p class="cart-header-phrase" id="cartHeaderPhrase"></p>
                    </div>
                    <div class="cart-header-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                </header>

                <!-- Lista de productos -->
                <div class="cart-items-list" id="cartItemsList"></div>

                <!-- Footer del carrito -->
                <div class="cart-footer">
                    <div class="cart-footer-notes">
                        <div class="modifier-group-title">
                            <i class="fa-solid fa-pencil"></i> Notas del pedido
                        </div>
                        <textarea class="notes-textarea" id="orderGeneralNotes" placeholder="Ej: Sin gluten, alergia a nueces..."></textarea>
                    </div>
                    <div class="cart-total-row">
                        <div class="cart-total-label-group">
                            <span class="cart-total-label">Total a pagar</span>
                            <span class="cart-total-sublabel">Impuestos incluidos</span>
                        </div>
                        <span class="cart-total-value" id="cartTotal">$0.00</span>
                    </div>
                    <button class="btn-cart-confirm" type="button" onclick="confirmOrder()" id="btnConfirmarPedido">
                        <i class="fa-solid fa-circle-check"></i>
                        Confirmar pedido
                    </button>
                </div>
            </div>
        </div>

        <!-- PANTALLA: SEGUIMIENTO -->
        <div class="tracking-screen" id="trackingScreen">
            <!-- Fondo animado con partículas decorativas -->
            <div class="tracking-bg-decor decor-1"></div>
            <div class="tracking-bg-decor decor-2"></div>
            <div class="tracking-bg-decor decor-3"></div>

            <div class="tracking-container">
                <!-- Encabezado del pedido -->
                <div class="tracking-header-section">
                    <div class="tracking-check-ring">
                        <div class="tracking-check-inner">
                            <i class="fa-solid fa-check" id="trackingCheckIcon"></i>
                        </div>
                    </div>
                    <h2 class="tracking-title">¡Pedido Enviado!</h2>
                    <p class="tracking-desc">Tu pedido ha sido recibido en cocina. Sigue aquí su progreso en tiempo real.</p>
                </div>

                <!-- Card de información del pedido -->
                <div class="tracking-order-card">
                    <div class="tracking-order-card-left">
                        <div class="tracking-order-num-label">N° Pedido</div>
                        <div class="tracking-order-num" id="trackingOrderNum">#----</div>
                    </div>
                    <div class="tracking-order-card-divider"></div>
                    <div class="tracking-order-card-right">
                        <div class="tracking-order-meta-item">
                            <i class="fa-solid fa-clock"></i>
                            <div>
                                <div class="tracking-meta-label">Tiempo estimado</div>
                                <div class="tracking-meta-value" id="trackingTime">15–20 min</div>
                            </div>
                        </div>
                        <div class="tracking-order-meta-item">
                            <i class="fa-solid fa-table-cells-large"></i>
                            <div>
                                <div class="tracking-meta-label">Mesa</div>
                                <div class="tracking-meta-value">{{ $mesa->numero_mesa }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de progreso de estado -->
                <div class="tracking-progress-wrapper">
                    <div class="tracking-progress-label">Estado del pedido</div>
                    <div class="tracking-progress-bar-bg">
                        <div class="tracking-progress-bar-fill" id="trackingProgressFill"></div>
                    </div>
                </div>

                <!-- Steps verticales elegantes -->
                <div class="tracking-steps-list">
                    <div class="tracking-step" id="step-Nuevo">
                        <div class="step-connector-line"></div>
                        <div class="step-dot">
                            <i class="fa-solid fa-receipt"></i>
                            <span class="step-done-check"><i class="fa-solid fa-check"></i></span>
                        </div>
                        <div class="step-info">
                            <h4>Pedido recibido</h4>
                            <p>Esperando confirmación en cocina</p>
                        </div>
                        <span class="step-status-chip"></span>
                    </div>

                    <div class="tracking-step" id="step-En_Preparacion">
                        <div class="step-connector-line"></div>
                        <div class="step-dot">
                            <i class="fa-solid fa-fire-burner"></i>
                            <span class="step-done-check"><i class="fa-solid fa-check"></i></span>
                        </div>
                        <div class="step-info">
                            <h4>En preparación</h4>
                            <p>El chef está cocinando tu platillo</p>
                        </div>
                        <span class="step-status-chip"></span>
                    </div>

                    <div class="tracking-step" id="step-Listo">
                        <div class="step-connector-line"></div>
                        <div class="step-dot">
                            <i class="fa-solid fa-bell"></i>
                            <span class="step-done-check"><i class="fa-solid fa-check"></i></span>
                        </div>
                        <div class="step-info">
                            <h4>¡Listo para servir!</h4>
                            <p>Tu comida está lista para la mesa</p>
                        </div>
                        <span class="step-status-chip"></span>
                    </div>

                    <div class="tracking-step last" id="step-Entregado">
                        <div class="step-dot">
                            <i class="fa-solid fa-circle-check"></i>
                            <span class="step-done-check"><i class="fa-solid fa-check"></i></span>
                        </div>
                        <div class="step-info">
                            <h4>Entregado</h4>
                            <p>¡Buen provecho, disfruta tu comida!</p>
                        </div>
                        <span class="step-status-chip"></span>
                    </div>
                </div>

                <button class="tracking-back-btn" type="button" onclick="returnToMenuFromTracking()">
                    <i class="fa-solid fa-arrow-left"></i> Volver al menú
                </button>
            </div>
        </div>
    </div>

    <script>
        window.mesaConfig = {
            id: {{ $mesa->id }},
            numero_mesa: '{{ $mesa->numero_mesa }}'
        };
    </script>
    <script src="{{ asset('js/theme-toggle.js') }}"></script>
    <script src="{{ asset('js/pages/menu.js') }}"></script>
</body>
</html>
