<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeanController;
use App\Http\Controllers\FileInspectController;

Route::get('/', function () {
    return view('auth.register');
});

// Authentication Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->filled('remember'))) {
        $request->session()->regenerate();

        $user = Auth::user();
        if ($user && $user->role === 'faculty' && $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your faculty account is awaiting dean approval.',
            ])->onlyInput('email');
        }

        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors([ 'email' => 'The provided credentials do not match our records.' ])->onlyInput('email');

})->name('login.post');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function (Request $request) {
    $data = $request->validate([
        'first_name' => ['required','string','max:255'],
        'last_name' => ['required','string','max:255'],
        'email' => ['required','email','max:255','unique:users,email'],
        'password' => ['required','confirmed','min:8'],
        'program' => ['required','in:BSIT,BSIS'],
    ]);

    $user = User::create([
        'name' => $data['first_name'].' '.$data['last_name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'role' => 'faculty',
        'department' => $data['program'],
        'status' => 'pending',
    ]);

    return redirect()->route('login')->with('success', 'Registration submitted. Please wait for dean approval before signing in.');

})->name('register.post');

// Role-based Dashboard Route
Route::get('/dashboard', function () {
    $user = Auth::user();
    if ($user->role === 'program_head') {
        return app(DashboardController::class)->programHeadDashboard();
    } elseif ($user->role === 'dean') {
        return app(DeanController::class)->dashboard();
    }
    // For faculty users, use the controller to get dynamic data
    return app(DashboardController::class)->facultyDashboard();
})->middleware(['auth', 'check.approval'])->name('dashboard');

// Logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// Dean Routes Group
Route::middleware('auth')->prefix('dean')->name('dean.')->group(function () {
    Route::get('/class-records', [DeanController::class, 'classRecords'])->name('class-records');
    Route::get('/class-records/view', [DeanController::class, 'viewClassRecord'])->name('class-records.view');
    Route::get('/export-data', [DeanController::class, 'exportAllData'])->name('export');
    Route::get('/settings', [DeanController::class, 'systemSettings'])->name('settings');
    Route::post('/settings', [DeanController::class, 'updateSettings'])->name('settings.update');
    Route::get('/submission/{userId}/{subjectId}', [DeanController::class, 'viewSubmission'])->name('submission.view');
    Route::post('/submission/{userId}/{subjectId}/approve', [DeanController::class, 'approveSubmission'])->name('submission.approve');
    Route::post('/submission/{userId}/{subjectId}/reject', [DeanController::class, 'rejectSubmission'])->name('submission.reject');
    Route::post('/faculty/{id}/toggle-status', [DeanController::class, 'toggleFacultyStatus'])->name('faculty.toggle-status');
});

// Program Head Routes Group
Route::middleware('auth')->prefix('program-head')->name('program-head.')->group(function () {
    Route::get('/class-records', [DashboardController::class, 'programHeadClassRecords'])->name('class-records');
    Route::get('/class-records/view', [DashboardController::class, 'programHeadViewClassRecord'])->name('class-records.view');
    Route::get('/reports', [DashboardController::class, 'programHeadReports'])->name('reports');
    Route::get('/reports/{report}/view', [DashboardController::class, 'viewReport'])->name('reports.view');
    Route::get('/submission/{userId}/{subjectId}', [DeanController::class, 'viewSubmission'])->name('submission.view');
    Route::post('/submission/{userId}/{subjectId}/approve', [DeanController::class, 'approveSubmission'])->name('submission.approve');
    Route::post('/submission/{userId}/{subjectId}/reject', [DeanController::class, 'rejectSubmission'])->name('submission.reject');
});

// Faculty Routes Group
Route::middleware(['auth', 'check.approval'])->prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'facultyDashboard'])->name('dashboard');

    Route::get('/students', [DashboardController::class, 'students'])->name('students');

    Route::get('/subjects', [DashboardController::class, 'subjects'])->name('subjects');
    Route::post('/subjects', [DashboardController::class, 'storeSubject'])->name('subjects.store');
    Route::put('/subjects/{subject}', [DashboardController::class, 'updateSubject'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [DashboardController::class, 'deleteSubject'])->name('subjects.delete');
    Route::get('/subjects/{subject}/students', [DashboardController::class, 'subjectStudents'])->name('subjects.students');

    Route::get('/records', [DashboardController::class, 'records'])->name('records');

    // Upload route to accept CSV/XLS/XLSX uploads
    Route::post('/records/upload', [\App\Http\Controllers\RecordUploadController::class, 'upload'])->name('records.upload');
    Route::post('/records/cancel-preview', [\App\Http\Controllers\RecordUploadController::class, 'cancelPreview'])->name('records.cancel-preview');
    
    // Get student grades detail by student_id string
    Route::get('/students/{studentId}/grades', [DashboardController::class, 'getStudentGrades'])->name('students.grades');

    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
    Route::post('/reports', [DashboardController::class, 'storeReport'])->name('reports.store');
    Route::get('/reports/{report}/download', [DashboardController::class, 'downloadReport'])->name('reports.download');
    Route::get('/reports/{report}/view', [DashboardController::class, 'viewReport'])->name('reports.view');
    Route::post('/reports/{report}/submit', [DashboardController::class, 'submitReport'])->name('reports.submit');
    Route::delete('/reports/{report}', [DashboardController::class, 'deleteReport'])->name('reports.delete');
    Route::get('/submitted-reports', [DashboardController::class, 'submittedReports'])->name('submitted-reports');
    Route::post('/analytics/generate', [DashboardController::class, 'generateAnalytics'])->name('analytics.generate');

    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
});

// File inspection route (for debugging)
Route::get('/inspect-file/{fileName}', [FileInspectController::class, 'inspectExcel'])->middleware('auth');

// API Routes for AJAX operations
Route::middleware('auth')->prefix('fire/api')->name('fire.api.')->group(function () {
    Route::delete('/record/{record}', [DashboardController::class, 'deleteRecord'])->name('record.delete');
    Route::delete('/subject-records/{subject}', [DashboardController::class, 'deleteSubjectRecords'])->name('subject-records.delete');
});
