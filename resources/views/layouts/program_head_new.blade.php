<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Program Head Portal - DSSC CRMS')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
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
            cursor: pointer;
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
        }

        /* Section Styling */
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
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

        .section-subtitle {
            font-size: 13px;
            color: #999;
            margin-top: 4px;
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
        
        /* Logout Confirmation Modal */
        .logout-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .logout-modal {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            padding: 32px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            transform: scale(0.95);
            animation: scaleUp 0.2s ease forwards;
        }

        @keyframes scaleUp {
            to { transform: scale(1); }
        }

        .logout-icon {
            width: 64px;
            height: 64px;
            background: #fef2f2;
            color: #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .logout-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .logout-text {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .logout-actions {
            display: flex;
            gap: 12px;
        }

        .logout-btn-cancel {
            flex: 1;
            padding: 12px;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .logout-btn-confirm {
            flex: 1;
            padding: 12px;
            background: #1e3c72;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .logout-btn-confirm:hover {
            background: #162e5a;
        }

        .logout-btn-cancel:hover {
            background: #e2e8f0;
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
                        <div style="font-size: 11px; color: rgba(255, 255, 255, 0.7);">Program Head</div>
                    </div>
                </div>
                
                <ul class="sidebar-menu">
                    <li>
                        <a href="{{ route('dashboard') }}" id="nav-dashboard" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a onclick="switchTab(event, 'faculty-tab')" id="nav-faculty">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            Faculty Management
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('program-head.class-records') }}" id="nav-class-records" class="{{ request()->routeIs('program-head.class-records*') ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4-4 4 4m0-10l-4 4-4-4" />
                                </svg>
                            </div>
                            Class Records
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('program-head.reports') }}" id="nav-reports" class="{{ request()->routeIs('program-head.reports*') ? 'active' : '' }}">
                            <div class="sidebar-menu-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            Reports
                        </a>
                    </li>
                    <li>
                        <a onclick="switchTab(event, 'settings-tab')" id="nav-settings">
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
                <button type="button" onclick="confirmLogout()" style="width: 100%; display: flex; align-items: center; padding: 12px 15px; background: none; border: none; color: rgba(255, 255, 255, 0.8); cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s ease; border-radius: 8px;" onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.1)'; this.style.color='white';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='rgba(255, 255, 255, 0.8)';">
                    <div class="sidebar-menu-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    Logout
                </button>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">@yield('page_title', 'Dashboard')</div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-role">Program Head</div>
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

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="logout-modal-overlay">
        <div class="logout-modal">
            <div class="logout-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <h3 class="logout-title">Confirm Logout</h3>
            <p class="logout-text">Are you sure you want to log out of your account? You will need to log in again to access the system.</p>
            <div class="logout-actions">
                <button type="button" class="logout-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <form action="{{ route('logout') }}" method="POST" style="flex: 1;">
                    @csrf
                    <button type="submit" class="logout-btn-confirm">Logout</button>
                </form>
            </div>
        </div>
    </div>

    @yield('scripts')
    @stack('scripts')
    
    <script>
        function confirmLogout() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target === modal) {
                closeLogoutModal();
            }
        }

        function switchTab(event, tabId) {
            if (event) event.preventDefault();
            
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Show selected tab
            const targetTab = document.getElementById(tabId);
            if (targetTab) {
                targetTab.style.display = 'block';
            }
            
            // Update sidebar active state
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.classList.remove('active');
            });
            
            // Update nav title and active class
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
                const title = event.currentTarget.innerText.trim();
                document.querySelector('.page-title').innerText = title;
            }

            // Scroll to top of main area
            document.querySelector('.main-area').scrollTop = 0;
        }
    </script>
</body>
</html>
