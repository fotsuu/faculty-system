<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DSSC CRMS')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
            background-color: white;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #1e3c72;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-footer {
            padding: 20px 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .sidebar-brand img {
            width: 40px;
            height: 40px;
        }
        
        .sidebar-brand h3 {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0 15px;
        }
        
        .sidebar-menu li {
            margin-bottom: 8px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-menu a.active {
            background-color: #ffffff;
            color: #1e3c72;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 12px 0 0 12px;
            margin-right: -15px;
            position: relative;
        }
        
        .sidebar-menu-icon {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-menu-icon svg {
            width: 100%;
            height: 100%;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #f5f7fb;
            min-height: 100vh;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #edf2f7;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .page-title {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #1e3c72;
        }
        
        .user-role {
            font-size: 12px;
            color: #64748b;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Main Area */
        .main-area {
            padding: 30px;
            flex: 1;
            width: 100%;
            overflow-y: auto;
        }

        /* Alert Banner */
        .alert-banner {
            background: #1e3c72;
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            position: relative;
        }
        
        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            opacity: 0.9;
        }
        
        .alert-icon svg {
            width: 100%;
            height: 100%;
        }

        .alert-content {
            font-size: 14px;
            line-height: 1.5;
            flex: 1;
        }
        
        .alert-close {
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
            width: 16px;
            height: 16px;
        }
        
        .alert-close svg {
            width: 100%;
            height: 100%;
        }

        .alert-close:hover {
            opacity: 1;
        }
        
        /* Welcome Banner */
        .welcome-banner {
            background: #1e3c72;
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .welcome-banner p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 24px;
        }
        
        .welcome-quote {
            font-style: italic;
            font-size: 14px;
            opacity: 0.8;
            max-width: 800px;
            line-height: 1.6;
        }
        
        /* Service Cards */
        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .service-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .service-icon {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            opacity: 0.1;
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .service-icon svg {
            width: 100%;
            height: 100%;
        }

        .service-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 12px;
        }
        
        .service-description {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        
        .service-btn {
            padding: 10px 24px;
            border: 1px solid #1e3c72;
            color: #1e3c72;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .service-btn:hover {
            background: #1e3c72;
            color: white;
        }

        /* Section Styling */
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e3c72;
        }

        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal-box {
            background: white;
            border-radius: 12px;
            padding: 28px 32px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }
        .modal-box h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        .modal-box p {
            font-size: 13px;
            color: #666;
            margin-bottom: 20px;
        }
        .modal-spinner {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            border: 4px solid #e9e9e9;
            border-top-color: #1e3c72;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @yield('styles')
        @stack('styles')
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-content">
                <div class="sidebar-brand">
                    <img src="/images/logo.png" alt="Logo" onerror="this.src='https://via.placeholder.com/40'">
                    <div>
                        <h3>CRMS</h3>
                        <div style="font-size: 11px; color: rgba(255, 255, 255, 0.7);">Faculty</div>
                    </div>
                </div>
                
                <ul class="sidebar-menu">
                    <li>
                        <a href="{{ route('dashboard') }}" class="{{ $activePage == 'dashboard' ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faculty.students') }}" class="{{ $activePage == 'students' ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            My Students
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faculty.subjects') }}" class="{{ $activePage == 'subjects' ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            My Subjects
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faculty.records') }}" class="{{ $activePage == 'records' ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            Class Records
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faculty.reports') }}" class="{{ $activePage == 'reports' ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            Reports
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faculty.settings') }}" class="{{ $activePage == 'settings' ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            Settings
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" style="width: 100%; display: flex; align-items: center; padding: 12px 15px; background: none; border: none; color: rgba(255, 255, 255, 0.8); cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s ease; border-radius: 8px;" onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.1)'; this.style.color='white';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='rgba(255, 255, 255, 0.8)';">
                        <div class="sidebar-menu-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </div>
                        Logout
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">@yield('page_title', 'Dashboard')</div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-role">Faculty</div>
                    </div>
                    <div class="user-avatar">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=f1f5f9&color=1e3c72" alt="Avatar">
                    </div>
                </div>
            </div>
            
            <div class="main-area">
                @yield('content')
            </div>
        </div>
    </div>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
