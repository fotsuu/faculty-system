<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - DSSC CRMS</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            background: white;
            min-height: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .left-section {
            flex: 1;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .left-section::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }
        
        .left-section::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
        }
        
        .logo-circle {
            width: 150px;
            height: 150px;
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .logo-circle img {
            width: 90%;
            height: 90%;
            object-fit: contain;
        }
        
        .left-section h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .left-section p {
            font-size: 18px;
            font-weight: 400;
            text-align: center;
            position: relative;
            z-index: 1;
            opacity: 0.95;
        }
        
        .right-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .right-section h2 {
            font-size: 32px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        
        select,
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background-color: #f8f8f8;
        }
        
        select:focus,
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #2a5298;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }
        
        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23333' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }
        
        input::placeholder {
            color: #999;
        }
        
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            margin-top: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #2a5298;
        }
        
        .checkbox-label {
            font-size: 13px;
            color: #666;
            cursor: pointer;
            margin: 0;
        }
        
        .forgot-password {
            font-size: 13px;
            color: #2a5298;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #2a5298;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        button:hover {
            background-color: #1e3c72;
            transform: translateY(-2px);
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #999;
        }
        
        .footer a {
            color: #2a5298;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
        }
        
        .success-message {
            color: #28a745;
            font-size: 13px;
            padding: 12px 16px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                min-height: auto;
            }
            
            .left-section {
                padding: 40px 30px;
            }
            
            .right-section {
                padding: 40px 30px;
            }
            
            .logo-circle {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
            
            .left-section h1 {
                font-size: 24px;
            }
            
            .left-section p {
                font-size: 14px;
            }
            
            .right-section h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Section -->
        <div class="left-section">
            <div class="logo-circle">
                <img src="{{ asset('images/logo.png') }}" alt="DSSC Logo">
            </div>
            <h1>DSSC</h1>
            <p>CRMS</p>
        </div>
        
        <!-- Right Section -->
        <div class="right-section">
            <h2>Sign In</h2>
            
            @if ($errors->any())
                <div class="error-message" style="display: block; margin-bottom: 20px; padding: 12px 16px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
            
            @if (session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                
                <!-- Email Address -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                    @error('email')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Password"
                        required
                    >
                    @error('password')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Form Footer -->
                <div class="form-footer">
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember" class="checkbox-label">Remember Me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>
                
                <!-- Sign In Button -->
                <button type="submit">Login</button>
                <div style="margin-top:12px; text-align:center; font-size:14px;">
                    <a href="{{ route('register') }}" style="color:#2a5298; text-decoration:none;">Don't have an account? Register</a>
                </div>
            </form>


        </div>
    </div>
</body>
</html>
