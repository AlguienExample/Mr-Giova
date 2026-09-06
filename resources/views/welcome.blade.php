<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo — Restaurante & Parrilla Executive</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/pages/welcome.css') }}">
</head>
<body>

    <div class="kfc-stripe-top"></div>

    <!-- HEADER -->
    <header class="landing-header">
        <a href="/" class="brand-logo-wrap">
            <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo Logo" class="brand-logo-img">
            <div class="brand-text">
                <h1>Sabor<span> a Pueblo</span></h1>
                <p>Restaurante & Parrilla</p>
            </div>
        </a>
        <div class="nav-actions">
            <a href="/menu/mesa/5" class="btn-nav btn-nav-outline">
                <i class="fa-solid fa-receipt"></i> Menú Digital
            </a>
            <a href="/login" class="btn-nav btn-nav-gold">
                <i class="fa-solid fa-user-shield"></i> Acceso Personal
            </a>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero-section">
        <div class="hero-badge">
            <i class="fa-solid fa-fire-burner"></i> Cocina de Fuego & Corte Gourmet
        </div>
        <h2 class="hero-title">
            Sabor Auténtico & <span>Gastronomía Ejecutiva</span>
        </h2>
        <p class="hero-subtitle">
            Sistema integrado para la gestión integral de pedidos, cocina en tiempo real, terminal POS y control de comensales en Sabor a Pueblo.
        </p>
        <div class="hero-buttons">
            <a href="/menu/mesa/5" class="btn-hero btn-hero-primary">
                <i class="fa-solid fa-utensils"></i> Explorar Menú del Cliente
            </a>
            <a href="/cocina" class="btn-hero btn-hero-secondary">
                <i class="fa-solid fa-fire-burner"></i> Tablero de Cocina
            </a>
        </div>
    </section>

    <!-- MODULES -->
    <section class="modules-section">
        <div class="section-header">
            <h3 class="section-title">Módulos del Sistema</h3>
            <p class="section-subtitle">Acceso directo a las plataformas operativas de Sabor a Pueblo</p>
        </div>

        <div class="modules-grid">
            <!-- Menú Cliente -->
            <a href="/menu/mesa/5" class="module-card">
                <div class="module-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h4 class="module-title">Menú Digital Cliente</h4>
                <p class="module-desc">Catálogo interactivo para comensales con selección de mesa, carrito de pedidos y notificaciones en vivo.</p>
                <div class="module-link">
                    Ingresar al Menú <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>

            <!-- Cocina -->
            <a href="/cocina" class="module-card">
                <div class="module-icon">
                    <i class="fa-solid fa-fire-burner"></i>
                </div>
                <h4 class="module-title">Tablero de Cocina</h4>
                <p class="module-desc">Monitoreo Kanban en tiempo real para chefs. Control de estado de comanda (Pendiente, Preparación, Listo).</p>
                <div class="module-link">
                    Ver Tablero Cocina <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>

            <!-- Terminal Caja -->
            <a href="/caja" class="module-card">
                <div class="module-icon">
                    <i class="fa-solid fa-cash-register"></i>
                </div>
                <h4 class="module-title">Terminal de Caja POS</h4>
                <p class="module-desc">Gestión de estado de mesas, liquidación de comandas, propinas y emisión de facturación.</p>
                <div class="module-link">
                    Abrir Terminal Caja <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>

            <!-- Administración -->
            <a href="/admin" class="module-card">
                <div class="module-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h4 class="module-title">Panel Administrativo</h4>
                <p class="module-desc">Control general de analíticas de venta, gestión de inventario, reservas, plano de mesas y personal.</p>
                <div class="module-link">
                    Gestión General <i class="fa-solid fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="landing-footer">
        <div class="footer-brand">
            <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo Logo" style="width:28px; height:28px; border-radius:50%; border:1px solid var(--gold);">
            <span>&copy; {{ date('Y') }} Sabor a Pueblo — Restaurante & Parrilla.</span>
        </div>
        <div class="footer-links">
            <a href="/menu/mesa/5"><i class="fa-solid fa-utensils"></i> Menú</a>
            <a href="/cocina"><i class="fa-solid fa-fire-burner"></i> Cocina</a>
            <a href="/caja"><i class="fa-solid fa-cash-register"></i> Caja</a>
            <a href="/login"><i class="fa-solid fa-user"></i> Login</a>
        </div>
    </footer>

</body>
</html>
