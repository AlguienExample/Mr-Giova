<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haute Epicure - Admin Portal</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #f4f4f4;
            --card-bg: #ffffff;
            --text-main: #111111;
            --text-muted: #757575;
            --text-light: #9e9e9e;
            --border-color: #e0e0e0;
            --accent-color: #8c6a38; 
            --black: #000000;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Nav */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 50px;
            width: 100%;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            letter-spacing: 1.5px;
            color: var(--text-main);
            text-transform: uppercase;
        }

        .admin-portal {
            font-size: 13px;
            letter-spacing: 1.5px;
            font-weight: 500;
            color: var(--text-main);
            text-transform: uppercase;
        }

        /* Main Content */
        .main-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-card {
            background-color: var(--card-bg);
            width: 100%;
            max-width: 440px;
            border-radius: 4px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.05);
            padding: 50px 40px 30px 40px;
            text-align: center;
            border: 1px solid rgba(0,0,0,0.03);
        }

        /* Logo Area */
        .logo-container {
            margin-bottom: 25px;
        }

        .logo-img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            margin: 0 auto;
            display: block;
        }

        .restaurant-name {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            font-weight: 400;
            color: var(--text-main);
            margin-bottom: 12px;
        }

        .staff-access {
            font-size: 11px;
            letter-spacing: 2px;
            font-weight: 500;
            color: var(--text-main);
            text-transform: uppercase;
            margin-bottom: 45px;
        }

        /* Form Area */
        .form-group {
            margin-bottom: 28px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.5px;
            color: var(--text-main);
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 0;
            color: var(--text-light);
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 10px 0 10px 32px;
            border: none;
            border-bottom: 2px solid var(--border-color);
            background: transparent;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            transition: border-color 0.3s;
            outline: none;
        }
        
        .form-control::placeholder {
            color: #cecece;
            font-weight: 400;
        }

        .form-control:focus {
            border-bottom-color: var(--black);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            margin-bottom: 35px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-main);
            cursor: pointer;
        }

        .remember-me input {
            width: 14px;
            height: 14px;
            accent-color: var(--black);
            cursor: pointer;
            border: 1px solid var(--border-color);
        }

        .forgot-password {
            font-size: 12px;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: #6d532a;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 18px;
            background-color: var(--black);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }

        .btn-login:hover {
            background-color: #222;
        }

        .btn-login:active {
            transform: scale(0.98);
        }
        
        /* Error/Success Messages */
        .alert {
            background-color: #fce8e8;
            color: #c0392b;
            padding: 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 25px;
            text-align: left;
            border-left: 3px solid #e74c3c;
        }

        .alert-success {
            background-color: #e8f8f5;
            color: #16a085;
            border-left: 3px solid #1abc9c;
            padding: 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 25px;
            text-align: left;
        }

        /* Card Footer */
        .card-footer {
            margin-top: 45px;
            padding-top: 25px;
            border-top: 1px solid #f0f0f0;
            font-size: 11px;
            color: var(--text-light);
        }

        /* Page Footer */
        .page-footer {
            padding: 30px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-links {
            display: flex;
            gap: 30px;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--text-main);
        }
        
        @media (max-width: 768px) {
            .top-nav, .page-footer {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .login-card {
                padding: 40px 25px 25px 25px;
            }
        }
    </style>
</head>
<body>

    <nav class="top-nav">
        <div class="brand-name">HAUTE EPICURE</div>
        <div class="admin-portal">ADMIN PORTAL</div>
    </nav>

    <div class="main-container">
        <div class="login-card">
            
            @if($errors->any())
                <div class="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="logo-container">
                <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=200" alt="Mr. Giova Logo" class="logo-img">
            </div>

            <h1 class="restaurant-name">Mr. Giova</h1>
            <div class="staff-access">STAFF ACCESS ONLY</div>

            <form action="/login" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="email">CORREO ELECTRÓNICO</label>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-envelope"></i>
                        <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@mrgiova.com" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">CONTRASEÑA</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock" style="font-size: 14px;"></i>
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
                    INICIAR SESIÓN <i class="fa-solid fa-arrow-right-to-bracket" style="font-size: 14px;"></i>
                </button>
            </form>

            <div class="card-footer">
                Internal System Security Protocol v4.2
            </div>
        </div>
    </div>

    <footer class="page-footer">
        <div class="copyright">
            &copy; {{ date('Y') }} HAUTE EPICURE. ALL RIGHTS RESERVED.
        </div>
        <div class="footer-links">
            <a href="#">PRIVACY POLICY</a>
            <a href="#">TERMS OF SERVICE</a>
            <a href="#">CONTACT</a>
        </div>
    </footer>

</body>
</html>
