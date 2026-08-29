<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo — Portal de Acceso Personal</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #0F1117;
            --bg-card: #1E2232;
            --bg-header: #141722;
            --bg-input: #161924;
            --border-color: #262B3D;
            --border-hover: #3A4158;
            --gold: #FFC107;
            --gold-dark: #D49B00;
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --text-subtle: #64748B;
            --radius-md: 14px;
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
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow & Embers */
        body::before {
            content: '';
            position: absolute;
            top: -10%;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 193, 7, 0.12) 0%, rgba(239, 68, 68, 0.05) 50%, rgba(15, 17, 23, 0) 70%);
            pointer-events: none;
            z-index: 0;
            animation: ember-pulse 3s infinite alternate ease-in-out;
        }

        @keyframes ember-pulse {
            0% { opacity: 0.7; transform: translateX(-50%) scale(0.95); }
            100% { opacity: 1; transform: translateX(-50%) scale(1.05); }
        }

        /* Top Brand Accent Stripe */
        .kfc-stripe-top {
            height: 4px;
            width: 100%;
            background: linear-gradient(90deg, var(--gold) 0%, var(--gold-dark) 50%, var(--bg-body) 100%);
            flex-shrink: 0;
            z-index: 10;
        }

        /* Top Nav */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 40px;
            width: 100%;
            background: rgba(20, 23, 34, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            z-index: 10;
        }

        .brand-logo-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-logo-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold-dark);
            box-shadow: 0 4px 10px rgba(0,0,0,0.4);
        }

        .brand-name {
            font-family: var(--font-serif);
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        .brand-name span {
            color: var(--gold);
        }

        .admin-portal-tag {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            letter-spacing: 1.5px;
            font-weight: 700;
            color: var(--gold);
            text-transform: uppercase;
            background: rgba(255, 193, 7, 0.1);
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            border: 1px solid rgba(255, 193, 7, 0.25);
        }

        /* Main Container */
        .main-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            z-index: 10;
        }

        .login-card {
            background-color: var(--bg-card);
            width: 100%;
            max-width: 440px;
            border-radius: var(--radius-md);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            padding: 44px 36px 36px;
            text-align: center;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold) 0%, var(--gold-dark) 100%);
        }

        /* Logo Area */
        .logo-container {
            margin-bottom: 20px;
            display: inline-block;
            position: relative;
        }

        .logo-img {
            width: 88px;
            height: 88px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--gold-dark);
            box-shadow: 0 8px 24px rgba(212, 155, 0, 0.25);
            margin: 0 auto;
            display: block;
            background: #141722;
        }

        .restaurant-name {
            font-family: var(--font-serif);
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .restaurant-name span {
            color: var(--gold);
        }

        .staff-access {
            font-size: 11px;
            letter-spacing: 2px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .staff-access i {
            color: var(--gold);
        }

        /* Form Area */
        .form-group {
            margin-bottom: 24px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: var(--text-subtle);
            font-size: 15px;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 13px 14px 13px 44px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-input);
            font-size: 14px;
            font-family: var(--font-sans);
            color: var(--text-main);
            transition: all 0.3s;
            outline: none;
        }
        
        .form-control::placeholder {
            color: var(--text-subtle);
            font-weight: 400;
        }

        .form-control:focus {
            border-color: var(--gold);
            background: rgba(26, 29, 43, 0.9);
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.15);
        }

        .form-control:focus + i,
        .input-wrapper:focus-within i {
            color: var(--gold);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            margin-bottom: 30px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .remember-me input {
            width: 16px;
            height: 16px;
            accent-color: var(--gold);
            cursor: pointer;
            border-radius: 4px;
        }

        .forgot-password {
            font-size: 12px;
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: var(--gold-dark);
            text-decoration: underline;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: #0F1117;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 16px rgba(255, 193, 7, 0.25);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 193, 7, 0.4);
            background: linear-gradient(135deg, #FFD54F 0%, var(--gold) 100%);
        }

        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Alerts */
        .alert {
            background-color: rgba(239, 68, 68, 0.15);
            color: #F87171;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 24px;
            text-align: left;
            border: 1px solid rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: #34D399;
            border-color: rgba(16, 185, 129, 0.3);
        }

        /* Card Footer */
        .card-footer {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            font-size: 11px;
            color: var(--text-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Page Footer */
        .page-footer {
            padding: 24px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--text-subtle);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-top: 1px solid var(--border-color);
            z-index: 10;
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
            .top-nav, .page-footer {
                padding: 16px 20px;
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
            .login-card {
                padding: 32px 24px 24px;
            }
        }
    </style>
</head>
<body>

    <div class="kfc-stripe-top"></div>

    <nav class="top-nav">
        <a href="/" class="brand-logo-wrap">
            <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo Logo" class="brand-logo-img">
            <div class="brand-name">Sabor<span> a Pueblo</span></div>
        </a>
        <div class="admin-portal-tag">
            <i class="fa-solid fa-shield-halved"></i> Acceso Administración
        </div>
    </nav>

    <div class="main-container">
        <div class="login-card">
            
            @if($errors->any())
                <div class="alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="logo-container">
                <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo Logo" class="logo-img">
            </div>

            <h1 class="restaurant-name">Sabor<span> a Pueblo</span></h1>
            <div class="staff-access">
                <i class="fa-solid fa-fire-burner"></i> Restaurante & Parrilla — Staff
            </div>

            <form action="/login" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="email">CORREO ELECTRÓNICO</label>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-envelope"></i>
                        <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@saborapueblo.com" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">CONTRASEÑA</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        Recordarme
                    </label>
                    <a href="#" class="forgot-password">¿Olvidó su contraseña?</a>
                </div>

                <button type="submit" class="btn-login">
                    INICIAR SESIÓN <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>

            <div class="card-footer">
                <i class="fa-solid fa-utensils"></i> Sistema de Control de Comandas & Gastronomía
            </div>
        </div>
    </div>

    <footer class="page-footer">
        <div class="copyright">
            &copy; {{ date('Y') }} Sabor a Pueblo — Restaurante & Parrilla. Todos los derechos reservados.
        </div>
        <div class="footer-links">
            <a href="/menu/mesa/5" target="_blank"><i class="fa-solid fa-receipt"></i> Menú Cliente</a>
            <a href="/cocina"><i class="fa-solid fa-fire-burner"></i> Cocina</a>
        </div>
    </footer>

</body>
</html>
