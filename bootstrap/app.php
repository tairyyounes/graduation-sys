<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // يضبط لغة التطبيق من كوكي app_locale في كل طلبات الويب
        $middleware->encryptCookies(except: [
            'app_locale',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if (auth()->check()) {
                $role = auth()->user()->role;
                if ($role === 'admin') {
                    return '/admin/dashboard';
                }
                if ($role === 'department_member' || $role === 'department_head') {
                    return '/department/dashboard';
                }
                return '/student/dashboard';
            }
            return '/dashboard';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();