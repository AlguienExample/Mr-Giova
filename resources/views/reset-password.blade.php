<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sabor a Pueblo — Restablecer Contraseña</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}">
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

            <div class="logo-container">
                <img src="{{ asset('Imagenes/logo.png') }}" alt="Sabor a Pueblo Logo" class="logo-img">
            </div>

            <h1 class="restaurant-name">Sabor<span> a Pueblo</span></h1>
            <div class="staff-access">
                <i class="fa-solid fa-fire-burner"></i> Restablecer Contraseña
            </div>

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ request()->query('email') }}">
                
                <div class="form-group">
                    <label class="form-label" for="password">NUEVA CONTRASEÑA</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">CONFIRMAR CONTRASEÑA</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    RESTABLECER CONTRASEÑA <i class="fa-solid fa-check"></i>
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
