<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo - Panel de Cocina</title>
    <link rel="stylesheet" href="{{ asset('css/cocina.css') }}">
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
<body class="kitchen-page">
    <div class="kfc-stripe-top"></div>

    <div class="kitchen-sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <div class="kitchen-app">
        <!-- SIDEBAR -->
        <aside class="kitchen-sidebar" id="kitchenSidebar">
            <div>
                <div class="kitchen-sidebar-brand">
                    <div class="kitchen-sidebar-logo">
                        <img src="{{ asset('Imagenes/logo.png') }}" alt="Logo Sabor a Pueblo" class="menu-logo" width="48" height="48">
                        <div>
                            <h2 style="color:#ffffff;">Sabor<span> a Pueblo</span></h2>
                            <div class="kitchen-sidebar-subtitle">Panel de Cocina</div>
                        </div>
                    </div>
                </div>
                <ul class="kitchen-nav">
                    <li class="kitchen-nav-item active">
                        <a href="/cocina"><i class="fa-solid fa-fire-burner"></i> Pedidos activos</a>
                    </li>
                    <li class="kitchen-nav-item">
                        <a href="/menu/mesa/5" target="_blank"><i class="fa-solid fa-receipt"></i> Ver menú cliente</a>
                    </li>
                </ul>
            </div>
            <div class="kitchen-sidebar-footer">
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="kitchen-logout" style="background:none;border:none;cursor:pointer;width:100%;text-align:left;padding:0;">
                        <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="kitchen-main">
            <header class="kitchen-header">
                <div class="kitchen-header-left">
                    <button class="kitchen-menu-toggle" type="button" id="menuToggle" onclick="toggleSidebar()" aria-label="Menú">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="kitchen-header-title">
                        <h1>Tablero de Cocina</h1>
                        <p>Monitoreo en tiempo real de preparación y entregas</p>
                    </div>
                </div>
                <div class="kitchen-header-actions">
                    <button class="theme-toggle-btn" type="button" onclick="toggleTheme()" aria-label="Cambiar a modo claro" title="Cambiar a modo claro">
                        <i class="fa-solid fa-sun"></i>
                    </button>
                    <div class="flame-sizzle-badge">
                        <i class="fa-solid fa-fire-flame-curved"></i> Fuego al Carbón
                    </div>
                    <div class="kitchen-live-badge">
                        <span class="kitchen-live-dot"></span>
                        En vivo
                    </div>
                    <div class="kitchen-user-badge">
                        <i class="fa-solid fa-fire-burner"></i>
                        <span>Chef Executive Master</span>
                    </div>
                </div>
            </header>

            <div class="kitchen-board">
                <!-- PENDIENTE -->
                <div class="kitchen-column col-pending" id="col-Nuevo">
                    <div class="kitchen-column-header">
                        <span class="kitchen-column-title">Pendiente</span>
                        <span class="kitchen-column-count" id="count-Nuevo">0</span>
                    </div>
                    <div class="kitchen-cards" id="cards-Nuevo"></div>
                </div>

                <!-- EN PREPARACIÓN -->
                <div class="kitchen-column col-prep" id="col-En_Preparacion">
                    <div class="kitchen-column-header">
                        <span class="kitchen-column-title">En preparación</span>
                        <span class="kitchen-column-count" id="count-En_Preparacion">0</span>
                    </div>
                    <div class="kitchen-cards" id="cards-En_Preparacion"></div>
                </div>

                <!-- LISTO -->
                <div class="kitchen-column col-ready" id="col-Listo">
                    <div class="kitchen-column-header">
                        <span class="kitchen-column-title">Listo</span>
                        <span class="kitchen-column-count" id="count-Listo">0</span>
                    </div>
                    <div class="kitchen-cards" id="cards-Listo"></div>
                </div>

                <!-- ENTREGADO -->
                <div class="kitchen-column col-done" id="col-Entregado">
                    <div class="kitchen-column-header">
                        <span class="kitchen-column-title">Entregado</span>
                        <span class="kitchen-column-count" id="count-Entregado">0</span>
                    </div>
                    <div class="kitchen-cards" id="cards-Entregado">
                        <div class="kitchen-empty" id="deliveredEmpty">
                            <i class="fa-solid fa-circle-check"></i>
                            Los pedidos entregados aparecerán aquí brevemente.
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="kitchen-toast" id="kitchenToast">
        <i class="fa-solid fa-bell"></i>
        <span id="toastText">¡Nuevo pedido recibido!</span>
    </div>

    <script src="{{ asset('js/theme-toggle.js') }}"></script>
    <script src="{{ asset('js/pages/cocina.js') }}"></script>
</body>
</html>
