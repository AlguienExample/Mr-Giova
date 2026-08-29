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
    
    <style>
        :root {
            --bg-body: #0F1117;
            --bg-header: #141722;
            --bg-card: #1E2232;
            --bg-card-hover: #262B3D;
            --border-color: #262B3D;
            --border-hover: rgba(255, 193, 7, 0.4);
            --gold: #FFC107;
            --gold-dark: #D49B00;
            --gold-bg: rgba(255, 193, 7, 0.12);
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --text-subtle: #64748B;
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-pill: 999px;
            --font-sans: 'Plus Jakarta Sans', sans-serif;
            --font-serif: 'Playfair Display', serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Brand Accent Stripe */
        .kfc-stripe-top {
            height: 4px;
            width: 100%;
            background: linear-gradient(90deg, var(--gold) 0%, var(--gold-dark) 50%, var(--bg-body) 100%);
            flex-shrink: 0;
        }

        /* Header */
        .landing-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(20, 23, 34, 0.88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-logo-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .brand-logo-img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold-dark);
            box-shadow: 0 4px 12px rgba(212, 155, 0, 0.3);
        }

        .brand-text h1 {
            font-family: var(--font-serif);
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.1;
        }

        .brand-text h1 span {
            color: var(--gold);
        }

        .brand-text p {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--gold-dark);
            font-weight: 600;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .btn-nav {
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-nav-outline {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-nav-outline:hover {
            border-color: var(--gold);
            color: var(--gold);
            background: var(--gold-bg);
        }

        .btn-nav-gold {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: #0F1117;
            box-shadow: 0 4px 14px rgba(255, 193, 7, 0.25);
        }

        .btn-nav-gold:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            padding: 90px 40px 70px;
            text-align: center;
            background: radial-gradient(circle at 50% 20%, rgba(255, 193, 7, 0.1) 0%, rgba(15, 17, 23, 0) 70%);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.25);
            border-radius: var(--radius-pill);
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .hero-title {
            font-family: var(--font-serif);
            font-size: 52px;
            font-weight: 800;
            line-height: 1.15;
            color: var(--text-main);
            max-width: 860px;
            margin: 0 auto 20px;
        }

        .hero-title span {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 16px;
            color: var(--text-muted);
            max-width: 620px;
            margin: 0 auto 40px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-hero {
            padding: 16px 32px;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: #0F1117;
            box-shadow: 0 8px 24px rgba(255, 193, 7, 0.3);
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(255, 193, 7, 0.45);
        }

        .btn-hero-secondary {
            background: var(--bg-card);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-hero-secondary:hover {
            border-color: var(--gold);
            color: var(--gold);
            transform: translateY(-3px);
        }

        /* Modules Grid */
        .modules-section {
            padding: 60px 40px 90px;
            max-width: 1240px;
            margin: 0 auto;
            width: 100%;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-family: var(--font-serif);
            font-size: 32px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 10px;
        }

        .section-subtitle {
            color: var(--text-muted);
            font-size: 14px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .module-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold) 0%, var(--gold-dark) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .module-card:hover {
            transform: translateY(-6px);
            border-color: var(--border-hover);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
            background: var(--bg-card-hover);
        }

        .module-card:hover::before {
            opacity: 1;
        }

        .module-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--gold);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .module-card:hover .module-icon {
            transform: scale(1.1);
        }

        .module-title {
            font-family: var(--font-serif);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-main);
        }

        .module-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            flex-grow: 1;
            margin-bottom: 20px;
        }

        .module-link {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gold);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Footer */
        .landing-footer {
            margin-top: auto;
            background: var(--bg-header);
            border-top: 1px solid var(--border-color);
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-subtle);
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-links {
            display: flex;
            gap: 24px;
        }

        .footer-links a {
            color: var(--text-subtle);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--gold);
        }

        @media (max-width: 768px) {
            .landing-header {
                padding: 16px 20px;
                flex-direction: column;
                gap: 14px;
            }
            .hero-section {
                padding: 50px 20px 40px;
            }
            .hero-title {
                font-size: 34px;
            }
            .modules-section {
                padding: 40px 20px 60px;
            }
            .landing-footer {
                padding: 24px 20px;
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
        }
    </style>
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
