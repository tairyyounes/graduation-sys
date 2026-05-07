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

    Route::get('/student/dashboard', function () {
        return "Student Dashboard";
    })->middleware('role:student')->name('student.dashboard');

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