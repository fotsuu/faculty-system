<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings - Dean Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .settings-header {
            margin-bottom: 40px;
        }
        
        .settings-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        
        .settings-header p {
            font-size: 14px;
            color: #666;
        }
        
        .settings-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .settings-card h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .settings-group {
            margin-bottom: 25px;
        }
        
        .settings-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            margin-bottom: 8px;
        }
        
        .settings-group input,
        .settings-group select,
        .settings-group textarea {
            width: 100%;
            max-width: 500px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }
        
        .settings-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2a5298;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            text-decoration: underline;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: #D4AF37;
            color: #1e3c72;
        }
        
        .btn-primary:hover {
            background-color: #c49f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('dashboard') }}" class="btn-back">← Back to Dashboard</a>
        
        <div class="settings-header">
            <h1>System Settings</h1>
            <p>Manage system-wide configuration and preferences</p>
        </div>
        
        <div class="settings-card">
            <h2>General Settings</h2>
            
            <div class="settings-group">
                <label for="university-name">University Name</label>
                <input type="text" id="university-name" placeholder="University Name" value="Kingsbridge University">
            </div>
            
            <div class="settings-group">
                <label for="academic-year">Current Academic Year</label>
                <input type="text" id="academic-year" placeholder="e.g., 2025-2026" value="2025-2026">
            </div>
            
            <div class="settings-group">
                <label for="semester">Current Semester</label>
                <select id="semester">
                    <option>First Semester</option>
                    <option selected>Second Semester</option>
                    <option>Summer</option>
                </select>
            </div>
        </div>
        
        <div class="settings-card">
            <h2>Email Notifications</h2>
            
            <div class="settings-group">
                <label>
                    <input type="checkbox" checked> Email notifications for pending submissions
                </label>
            </div>
            
            <div class="settings-group">
                <label>
                    <input type="checkbox" checked> Weekly system health reports
                </label>
            </div>
            
            <div class="settings-group">
                <label>
                    <input type="checkbox"> Email summary of new records
                </label>
            </div>
        </div>
        
        <div class="settings-card">
            <h2>Data Management</h2>
            
            <div class="settings-group">
                <label>Records Retention Policy</label>
                <select>
                    <option>Keep all records indefinitely</option>
                    <option selected>Archive records after 5 years</option>
                    <option>Delete records after 5 years</option>
                </select>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 4px;">
                <p style="font-size: 12px; color: #666; margin-bottom: 10px;">
                    <strong>Database Size:</strong> ~250 MB | <strong>Total Records:</strong> 12,450 | <strong>Last Backup:</strong> Today at 2:30 AM
                </p>
                <button class="btn btn-primary">↓ Manual Backup</button>
            </div>
        </div>
        
        <div class="settings-card">
            <h2>Save Changes</h2>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                Settings are saved automatically when you make changes.
            </p>
            <button class="btn btn-primary">✓ Save All Settings</button>
        </div>
    </div>
</body>
</html>
