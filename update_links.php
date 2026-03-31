<?php
$file = file_get_contents('resources/views/dashboard/faculty.blade.php');
$file = str_replace('<li><a href="#" class="active"><span class="sidebar-menu-icon">📊</span> Dashboard</a></li>', '<li><a href="{{ route(\'faculty.dashboard\') }}" class="active"><span class="sidebar-menu-icon">📊</span> Dashboard</a></li>', $file);
$file = str_replace('<li><a href="#"><span class="sidebar-menu-icon">👥</span> Students</a></li>', '<li><a href="{{ route(\'faculty.students\') }}"><span class="sidebar-menu-icon">👥</span> Students</a></li>', $file);
$file = str_replace('<li><a href="#"><span class="sidebar-menu-icon">📚</span> Subjects</a></li>', '<li><a href="{{ route(\'faculty.subjects\') }}"><span class="sidebar-menu-icon">📚</span> Subjects</a></li>', $file);
$file = str_replace('<li><a href="#"><span class="sidebar-menu-icon">📋</span> Records</a></li>', '<li><a href="{{ route(\'faculty.records\') }}"><span class="sidebar-menu-icon">📋</span> Records</a></li>', $file);
$file = str_replace('<li><a href="#"><span class="sidebar-menu-icon">📈</span> Reports</a></li>', '<li><a href="{{ route(\'faculty.reports\') }}"><span class="sidebar-menu-icon">📈</span> Reports</a></li>', $file);
$file = str_replace('<li><a href="#"><span class="sidebar-menu-icon">⚙️</span> Settings</a></li>', '<li><a href="{{ route(\'faculty.settings\') }}"><span class="sidebar-menu-icon">⚙️</span> Settings</a></li>', $file);
file_put_contents('resources/views/dashboard/faculty.blade.php', $file);
echo "Updated faculty dashboard sidebar links\n";
?>
