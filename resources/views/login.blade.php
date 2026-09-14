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
    
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-light.css') }}">
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
<body>

    <div class="kfc-stripe-top"></div>

    <nav class="top-nav">
        <a href="/" class="brand-logo-wrap">
            <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo Logo" class="brand-logo-img">
            <div class="brand-name">Sabor<span> a Pueblo</span></div>
        </a>
        <div style="display:flex; align-items:center; gap:12px;">
            <div class="admin-portal-tag">
                <i class="fa-solid fa-shield-halved"></i> Acceso Administración
            </div>
            <button class="theme-toggle-btn theme-toggle-wide" type="button" onclick="toggleTheme()" aria-label="Cambiar a modo claro" title="Cambiar a modo claro">
                <i class="fa-solid fa-sun"></i><span class="theme-label">Claro</span>
            </button>
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
                    <a href="{{ route('password.request') }}" class="forgot-password">¿Olvidó su contraseña?</a>
                </div>

                @if(session('sesion_activa'))
                <div class="alert alert-warning sesion-activa-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div class="sesion-activa-body">
                        <strong>Sesión activa detectada</strong>
                        <p>Se detectó una sesión activa con esta cuenta en otro dispositivo. Para continuar, confirma que deseas cerrar esa sesión e iniciar aquí.</p>
                        <label class="sesion-activa-confirm">
                            <input type="checkbox" name="forzar_sesion" value="1" required id="forzar_sesion">
                            <span>Confirmo que quiero cerrar la sesión activa en el otro dispositivo e iniciar sesión aquí. <strong>Vuelve a escribir tu contraseña.</strong></span>
                        </label>
                    </div>
                </div>
                @endif

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

    <script src="{{ asset('js/theme-toggle.js') }}"></script>
</body>
</html>
