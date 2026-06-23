<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Personal - Mr.Giova</title>
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/restaurant.css">
    
    <style>
        body {
            background: linear-gradient(135deg, var(--color-sand) 0%, #F5D3C3 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background-color: white;
            border-radius: var(--border-radius-md);
            box-shadow: var(--box-shadow-lg);
            border: 1px solid rgba(211, 84, 0, 0.15);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        .login-header {
            text-align: center;
            padding: 35px 25px 25px 25px;
            position: relative;
        }

        .login-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--color-cempasuchil);
            margin: 0 auto 15px auto;
            box-shadow: var(--box-shadow-sm);
        }

        .login-header h2 {
            font-size: 28px;
            color: var(--color-charcoal-dark);
            margin-bottom: 5px;
        }

        .login-header h2 span {
            color: var(--color-cempasuchil);
        }

        .login-header p {
            color: var(--color-muted);
            font-size: 14px;
        }

        .login-body {
            padding: 0 35px 35px 35px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--color-charcoal);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            color: var(--color-muted);
            font-size: 16px;
            transition: color 0.2s;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 42px;
            border-radius: var(--border-radius-sm);
            border: 1px solid rgba(0, 0, 0, 0.12);
            background-color: var(--color-sand-light);
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
            color: var(--color-charcoal-dark);
        }

        .form-control:focus {
            border-color: var(--color-terracotta);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(211, 84, 0, 0.15);
        }

        .form-control:focus + i {
            color: var(--color-terracotta);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--color-charcoal);
            font-weight: 500;
        }

        .remember-me input {
            accent-color: var(--color-terracotta);
            width: 15px;
            height: 15px;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border-radius: var(--border-radius-sm);
        }

        .alert {
            background-color: #FDEDEC;
            color: #C0392B;
            border-left: 4px solid #E74C3C;
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #E8F8F5;
            color: var(--color-jalapeno-hover);
            border-left: 4px solid var(--color-jalapeno);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: var(--color-muted);
        }
    </style>
</head>
<body>

    <!-- Talavera Border Accent -->
    <div class="mexican-border-top" style="position: absolute; top: 0; left: 0;"></div>

    <div class="login-card">
        <div class="login-header">
            <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=200" alt="Logo Mr.Giova" class="login-logo">
            <h2>Mr.<span>Giova</span></h2>
            <p>Acceso para el Personal del Restaurante</p>
        </div>

        <div class="login-body">
            @if($errors->any())
                <div class="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            <form action="/login" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="email">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="ejemplo@mrgiova.com" required autofocus>
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        Recordarme en este equipo
                    </label>
                </div>

                <button type="submit" class="btn-mrgiova btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
                </button>
            </form>
        </div>
    </div>

    <div class="login-footer">
        &copy; {{ date('Y') }} Mr.Giova. Todos los derechos reservados.
    </div>

</body>
</html>
