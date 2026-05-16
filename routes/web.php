<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Department\StudentImportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    if ($user->role === 'department_member') {
        return redirect()->route('department.dashboard');
    }
    return redirect()->route('student.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {

    Route::get('/admin/dashboard/{any?}', function () {
        return view('admin.vue-dashboard');
    })->where('any', '.*')->middleware('role:admin')->name('admin.dashboard');

    Route::middleware('role:admin')->prefix('/admin/users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::post('/', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('admin.activity.index');
        
        Route::prefix('/admin/departments')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DepartmentController::class, 'index'])->name('admin.departments.index');
            Route::post('/', [\App\Http\Controllers\Admin\DepartmentController::class, 'store'])->name('admin.departments.store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\DepartmentController::class, 'show'])->name('admin.departments.show');
            Route::put('/{id}', [\App\Http\Controllers\Admin\DepartmentController::class, 'update'])->name('admin.departments.update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\DepartmentController::class, 'destroy'])->name('admin.departments.destroy');
        });
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/student/dashboard/{any?}', function () {
            return view('student.dashboard');
        })->where('any', '.*')->name('student.dashboard');

        // Student API Routes
        Route::get('/student/data', [\App\Http\Controllers\Student\StudentDashboardController::class, 'getData']);
        Route::get('/student/activity', [\App\Http\Controllers\Student\StudentDashboardController::class, 'getActivity']);
        
        Route::prefix('/student/proposals')->group(function () {
            Route::get('/', [\App\Http\Controllers\Student\StudentProposalController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Student\StudentProposalController::class, 'store']);
            Route::put('/{proposal}', [\App\Http\Controllers\Student\StudentProposalController::class, 'update']);
            Route::put('/{proposal}/submit', [\App\Http\Controllers\Student\StudentProposalController::class, 'submit']);
            Route::put('/{proposal}/archive', [\App\Http\Controllers\Student\StudentProposalController::class, 'archive']);
            Route::delete('/{proposal}', [\App\Http\Controllers\Student\StudentProposalController::class, 'destroy']);
            
            Route::get('/{proposal}/versions', [\App\Http\Controllers\Student\StudentProposalController::class, 'versions']);
            Route::get('/{proposal}/decision', [\App\Http\Controllers\Student\StudentProposalController::class, 'decision']);
            Route::get('/{proposal}/similarity', [\App\Http\Controllers\Student\StudentProposalController::class, 'similarity']);
            
            Route::get('/{proposal}/team', [\App\Http\Controllers\Student\StudentTeamController::class, 'getTeam']);
            Route::post('/{proposal}/invite', [\App\Http\Controllers\Student\StudentTeamController::class, 'invite']);
        });
    });

    Route::get('/department/dashboard/{any?}', function () {
        return view('department.vue-dashboard');
    })->where('any', '.*')->middleware('role:department_member')->name('department.dashboard');

    Route::middleware('role:department_member')->prefix('/department/students')->group(function () {
        Route::get('/', [StudentImportController::class, 'index'])->name('department.students.index');
        Route::post('/', [StudentImportController::class, 'store'])->name('department.students.store');
        Route::post('/import', [StudentImportController::class, 'import'])->name('department.students.import');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';