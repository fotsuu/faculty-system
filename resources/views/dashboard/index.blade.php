<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - DSSC Faculty System</title>
    
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
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: white;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 227px;
            background: #1e3c72;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-brand-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .sidebar-brand h3 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-title {
            padding: 15px 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
        }
        
        .sidebar-menu li {
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(212, 175, 55, 0.1);
            color: white;
        }
        
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }
        
        .sidebar-menu-icon {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .sidebar-logout {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-logout {
            width: 100%;
            padding: 10px;
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: white;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 227px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* Top Header */
        .top-header {
            background: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid #1e3c72;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-brand-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .header-brand h2 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #1e3c72;
        }
        
        .header-brand p {
            font-size: 11px;
            color: #999;
            letter-spacing: 1px;
        }
        
        .search-bar {
            flex: 1;
            max-width: 400px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #f5f5f5;
            color: #333;
            font-size: 13px;
        }
        
        .search-bar input::placeholder {
            color: #999;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .notification-icon {
            cursor: pointer;
            font-size: 18px;
            color: #333;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background-color: #1e3c72;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 14px;
        }
        
        .user-info h4 {
            font-size: 12px;
            font-weight: 600;
            color: #333;
        }
        
        .user-info p {
            font-size: 10px;
            color: #999;
        }
        
        /* Main Area */
        .main-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        
        .dashboard-header p {
            font-size: 14px;
            color: #666;
        }
        
        .dashboard-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #1e3c72;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #152948;
        }
        
        .btn-secondary {
            background-color: white;
            color: #1e3c72;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            border-color: #2a5298;
            background-color: #f9f9f9;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-title {
            font-size: 11px;
            font-weight: 700;
            color: #999;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        
        .stat-card-value {
            font-size: 36px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 12px;
        }
        
        .stat-card-change {
            font-size: 12px;
            color: #28a745;
            font-weight: 600;
        }
        
        .stat-card-icon {
            text-align: right;
            font-size: 24px;
            color: #2a5298;
        }
        
        /* Section */
        .section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
        
        .view-all {
            color: #2a5298;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        /* Subject Cards */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .subject-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #eee;
        }
        
        .subject-code {
            font-size: 12px;
            color: #2a5298;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .subject-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 12px;
        }
        
        .subject-info {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }
        
        .progress-bar {
            background: #e9e9e9;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: #28a745;
        }
        
        .progress-label {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .subject-buttons {
            display: flex;
            gap: 10px;
        }
        
        .subject-btn {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #1e3c72;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .subject-btn:hover {
            border-color: #2a5298;
            background: #f0f6ff;
        }
        
        /* Charts Section */
        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .chart-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .chart-tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .chart-tab {
            padding: 12px 0;
            border: none;
            background: none;
            color: #999;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .chart-tab.active {
            color: #2a5298;
            border-bottom-color: #2a5298;
        }
        
        .chart-placeholder {
            background: linear-gradient(90deg, transparent 0%, #f5f5f5 50%, transparent 100%);
            height: 300px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
        }
        
        /* Top Performers */
        .performers-list {
            list-style: none;
        }
        
        .performer-item {
            display: flex;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }
        
        .performer-item:last-child {
            border-bottom: none;
        }
        
        .performer-rank {
            width: 36px;
            height: 36px;
            background: #1e3c72;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            margin-right: 12px;
            font-size: 13px;
        }
        
        .performer-info {
            flex: 1;
        }
        
        .performer-name {
            font-size: 13px;
            font-weight: 600;
            color: #1e3c72;
        }
        
        .performer-id {
            font-size: 11px;
            color: #999;
        }
        
        .performer-gpa {
            font-weight: 700;
            color: #1e3c72;
            font-size: 13px;
        }
        
        .performer-subject {
            font-size: 11px;
            color: #999;
        }
        
        /* Report Generator */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .report-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .report-card:hover {
            border-color: #2a5298;
            box-shadow: 0 4px 12px rgba(42, 82, 152, 0.1);
        }
        
        .report-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .report-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        
        .report-description {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .report-btn {
            width: 100%;
            padding: 10px;
            background-color: #1e3c72;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .report-btn:hover {
            background-color: #152948;
        }
        
        @media (max-width: 1200px) {
            .subject-grid, .report-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .subject-grid, .report-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <img src="{{ asset('images/logo.png') }}" alt="DSSC Logo">
                </div>
                <h3>Faculty System</h3>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-title">Main Menu</li>
                <li><a href="#" class="active"><span class="sidebar-menu-icon">📊</span> Dashboard</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">👥</span> Students</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">📚</span> Subjects</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">📋</span> Records</a></li>
                
                <li class="sidebar-title" style="margin-top: 20px;">Analysis</li>
                <li><a href="#"><span class="sidebar-menu-icon">📈</span> Reports</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">⚙️</span> Settings</a></li>
            </ul>
            
            <div class="sidebar-logout">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-logout">Sign Out</button>
                </form>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <div class="header-brand-icon">
                        <img src="{{ asset('images/logo.png') }}" alt="DSSC Logo">
                    </div>
                    <div class="header-brand">
                        <h2>DSSC</h2>
                        <p>Faculty System</p>
                    </div>
                </div>
                
                <div class="search-bar">
                    <input type="text" placeholder="Search records, students, or reports...">
                </div>
                
                <div class="header-right">
                    <div class="notification-icon">🔔</div>
                    <div class="user-profile">
                        <div class="user-info">
                            <h4>Dr. Eleanor Vance</h4>
                            <p>Department of History</p>
                        </div>
                        <div class="user-avatar">EV</div>
                    </div>
                </div>
            </header>
            
            <!-- Main Area -->
            <div class="main-area">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, <strong>Dr. Vance</strong>. Here is today's academic summary.</p>
                    <div class="dashboard-actions">
                        <button class="btn btn-secondary">📤 Upload Excel</button>
                        <button class="btn btn-primary">📊 Generate Reports</button>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-card-title">Total Uploaded Records</div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div class="stat-card-value">2,847</div>
                                <div class="stat-card-change">+12% from last semester</div>
                                <div style="font-size: 11px; color: #999; margin-top: 4px;">All time submissions</div>
                            </div>
                            <div class="stat-card-icon">📄</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-title">Total Students</div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div class="stat-card-value">1,234</div>
                                <div class="stat-card-change">+5% enrollment increase</div>
                                <div style="font-size: 11px; color: #999; margin-top: 4px;">Active across all courses</div>
                            </div>
                            <div class="stat-card-icon">👥</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-title">Active Subjects</div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div class="stat-card-value">6</div>
                                <div class="stat-card-change" style="color: #999;">Current semester</div>
                                <div style="font-size: 11px; color: #999; margin-top: 4px;">Department of History</div>
                            </div>
                            <div class="stat-card-icon">📚</div>
                        </div>
                    </div>
                </div>
                
                <!-- Subject Overview -->
                <div class="section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Subject Overview</div>
                            <div class="section-subtitle">Current semester performance across your courses</div>
                        </div>
                        <a href="#" class="view-all">View All Subjects →</a>
                    </div>
                    
                    <div class="subject-grid">
                        <div class="subject-card">
                            <div class="subject-code">HIST101</div>
                            <div class="subject-name">Introduction to History</div>
                            <div class="subject-info">45 Students • Mon/Wed 09:00 AM</div>
                            
                            <div class="progress-label">
                                <span>Pass Rate</span>
                                <span style="font-weight: 700; color: #28a745;">92%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 92%; background: #28a745;"></div>
                            </div>
                            
                            <div class="progress-label">
                                <span>Attendance</span>
                                <span style="font-weight: 700; color: #1e3c72;">88%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 88%; background: #1e3c72;"></div>
                            </div>
                            
                            <div class="subject-buttons">
                                <button class="subject-btn">✓ Details</button>
                                <button class="subject-btn">⬇ Grades</button>
                            </div>
                        </div>
                        
                        <div class="subject-card">
                            <div class="subject-code" style="color: #FFA500;">HIST205</div>
                            <div class="subject-name">World Civilizations</div>
                            <div class="subject-info">38 Students • Tue/Thu 11:00 AM</div>
                            
                            <div class="progress-label">
                                <span>Pass Rate</span>
                                <span style="font-weight: 700; color: #FFA500;">85%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 85%; background: #FFA500;"></div>
                            </div>
                            
                            <div class="progress-label">
                                <span>Attendance</span>
                                <span style="font-weight: 700; color: #1e3c72;">94%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 94%; background: #1e3c72;"></div>
                            </div>
                            
                            <div class="subject-buttons">
                                <button class="subject-btn">✓ Details</button>
                                <button class="subject-btn">⬇ Grades</button>
                            </div>
                        </div>
                        
                        <div class="subject-card">
                            <div class="subject-code">HIST310</div>
                            <div class="subject-name">Medieval Studies</div>
                            <div class="subject-info">24 Students • Mon/Wed 02:00 PM</div>
                            
                            <div class="progress-label">
                                <span>Pass Rate</span>
                                <span style="font-weight: 700; color: #dc3545;">78%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 78%; background: #dc3545;"></div>
                            </div>
                            
                            <div class="progress-label">
                                <span>Attendance</span>
                                <span style="font-weight: 700; color: #1e3c72;">96%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 96%; background: #1e3c72;"></div>
                            </div>
                            
                            <div class="subject-buttons">
                                <button class="subject-btn">✓ Details</button>
                                <button class="subject-btn">⬇ Grades</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Section -->
                <div class="charts-container">
                    <div class="chart-section">
                        <div class="section-header">
                            <div class="section-title">Detailed Analytics</div>
                            <div class="section-subtitle">Comprehensive performance metrics</div>
                        </div>
                        
                        <div class="chart-tabs">
                            <button class="chart-tab active">Pass/Fail Rates</button>
                            <button class="chart-tab">Attendance Trends</button>
                            <button class="chart-tab">Grade Distribution</button>
                        </div>
                        
                        <div class="chart-placeholder">📊 Chart Visualization Area</div>
                    </div>
                    
                    <div class="chart-section">
                        <div class="section-header">
                            <div class="section-title">Top Performers</div>
                            <div class="section-subtitle">Based on cumulative GPA this semester</div>
                        </div>
                        
                        <ul class="performers-list">
                            <li class="performer-item">
                                <div class="performer-rank">#1</div>
                                <div class="performer-info">
                                    <div class="performer-name">Alexandra Chen</div>
                                    <div class="performer-id">2021-0045 • BS History</div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="performer-gpa">3.98</div>
                                    <div class="performer-subject">World Civilization</div>
                                </div>
                            </li>
                            
                            <li class="performer-item">
                                <div class="performer-rank">#2</div>
                                <div class="performer-info">
                                    <div class="performer-name">Marcus Thorne</div>
                                    <div class="performer-id">2021-0128 • BA Philosophy</div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="performer-gpa">3.95</div>
                                    <div class="performer-subject">Research Methods</div>
                                </div>
                            </li>
                            
                            <li class="performer-item">
                                <div class="performer-rank">#3</div>
                                <div class="performer-info">
                                    <div class="performer-name">Sarah Jenkins</div>
                                    <div class="performer-id">2021-0080 • BS History</div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="performer-gpa">3.92</div>
                                    <div class="performer-subject">Modern History</div>
                                </div>
                            </li>
                            
                            <li class="performer-item">
                                <div class="performer-rank">#4</div>
                                <div class="performer-info">
                                    <div class="performer-name">David Kim</div>
                                    <div class="performer-id">2021-0234</div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="performer-gpa">3.88</div>
                                    <div class="performer-subject">Historical Analysis</div>
                                </div>
                            </li>
                        </ul>
                        
                        <a href="#" class="view-all" style="display: block; margin-top: 15px;">View Full Leaderboard →</a>
                    </div>
                </div>
                
                <!-- Report Generator -->
                <div class="section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Report Generator</div>
                            <div class="section-subtitle">Generate and download official academic reports</div>
                        </div>
                        <div style="font-size: 12px; color: #999;">Current Semester: Fall 2024</div>
                    </div>
                    
                    <div class="report-grid">
                        <div class="report-card">
                            <div class="report-icon">📋</div>
                            <div class="report-title">Student Grade Report</div>
                            <div class="report-description">Comprehensive breakdown of student grades across all subjects.</div>
                            <button class="report-btn">⬇ GENERATE</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">📊</div>
                            <div class="report-title">Pass/Fail Analysis</div>
                            <div class="report-description">Statistical report on pass and fail rates per subject.</div>
                            <button class="report-btn">⬇ GENERATE</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">✓</div>
                            <div class="report-title">Attendance Summary</div>
                            <div class="report-description">Detailed attendance records and participation metrics.</div>
                            <button class="report-btn">⬇ GENERATE</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">👥</div>
                            <div class="report-title">Lecture & Lab Summary</div>
                            <div class="report-description">Combined performance summary for lecture and laboratory components.</div>
                            <button class="report-btn">⬇ GENERATE</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
