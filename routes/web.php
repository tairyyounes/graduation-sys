<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Department\StudentImportController;
use App\Http\Controllers\Department\DepartmentProposalController;
use App\Http\Controllers\Shared\HistoricalProposalController;
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
    if ($user->role === 'department_member' || $user->role === 'department_head') {
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
        Route::get('/admin/stats', [\App\Http\Controllers\Admin\AdminStatsController::class, 'index'])->name('admin.stats.index');
        Route::get('/admin/activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('admin.activity.index');
        
        Route::prefix('/admin/departments')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DepartmentController::class, 'index'])->name('admin.departments.index');
            Route::post('/', [\App\Http\Controllers\Admin\DepartmentController::class, 'store'])->name('admin.departments.store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\DepartmentController::class, 'show'])->name('admin.departments.show');
            Route::put('/{id}', [\App\Http\Controllers\Admin\DepartmentController::class, 'update'])->name('admin.departments.update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\DepartmentController::class, 'destroy'])->name('admin.departments.destroy');
        });

        Route::prefix('/admin/previous-proposals')->group(function () {
            Route::post('/', [HistoricalProposalController::class, 'store']);
            Route::post('/import', [HistoricalProposalController::class, 'import']);
        });
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/student/dashboard/{any?}', function () {
            return view('student.dashboard');
        })->where('any', '.*')->name('student.dashboard');

        // Student API Routes
        Route::get('/student/data', [\App\Http\Controllers\Student\StudentDashboardController::class, 'getData']);
        
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
    })->where('any', '.*')->middleware('role:department_member,department_head')->name('department.dashboard');

    // Both department_member and department_head have access to proposals and stats
    Route::middleware('role:department_member,department_head')->prefix('/department')->group(function () {
        // Proposal Management
        Route::get('/proposals', [DepartmentProposalController::class, 'index']);
        Route::get('/stats', [DepartmentProposalController::class, 'stats']);
        Route::get('/proposals/{proposal}', [DepartmentProposalController::class, 'show']);
        Route::post('/proposals/{proposal}/review', [DepartmentProposalController::class, 'review']);
    });

    // ONLY department_head has access to students and department members management
    Route::middleware('role:department_head')->prefix('/department')->group(function () {
        Route::post('/proposals/{proposal}/grant-revision', [DepartmentProposalController::class, 'grantExtraRevision']);
        Route::get('/students', [StudentImportController::class, 'index'])->name('department.students.index');
        Route::post('/students', [StudentImportController::class, 'store'])->name('department.students.store');
        Route::get('/students/template', [StudentImportController::class, 'downloadTemplate'])->name('department.students.template');
        Route::post('/students/import', [StudentImportController::class, 'import'])->name('department.students.import');
        Route::post('/students/import-confirm', [StudentImportController::class, 'confirmImport'])->name('department.students.import-confirm');
        Route::put('/students/{studentId}', [StudentImportController::class, 'update'])->name('department.students.update');
        Route::delete('/students/{studentId}', [StudentImportController::class, 'destroy'])->name('department.students.destroy');

        // Department Members Management
        Route::get('/members', [\App\Http\Controllers\Department\DepartmentMemberManagementController::class, 'index'])->name('department.members.index');
        Route::post('/members', [\App\Http\Controllers\Department\DepartmentMemberManagementController::class, 'store'])->name('department.members.store');
        Route::put('/members/{user}', [\App\Http\Controllers\Department\DepartmentMemberManagementController::class, 'update'])->name('department.members.update');
        Route::delete('/members/{user}', [\App\Http\Controllers\Department\DepartmentMemberManagementController::class, 'destroy'])->name('department.members.destroy');

        // Review Committees
        Route::get('/committees', [\App\Http\Controllers\Department\ReviewCommitteeController::class, 'index'])->name('department.committees.index');
        Route::post('/committees', [\App\Http\Controllers\Department\ReviewCommitteeController::class, 'store'])->name('department.committees.store');
        Route::put('/committees/{committee}', [\App\Http\Controllers\Department\ReviewCommitteeController::class, 'update'])->name('department.committees.update');
        Route::delete('/committees/{committee}', [\App\Http\Controllers\Department\ReviewCommitteeController::class, 'destroy'])->name('department.committees.destroy');

        // Historical Proposals
        Route::post('/previous-proposals', [HistoricalProposalController::class, 'store']);
        Route::post('/previous-proposals/import', [HistoricalProposalController::class, 'import']);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/previous-proposals', [HistoricalProposalController::class, 'index']);
});

require __DIR__.'/auth.php';