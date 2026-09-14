<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->authenticateSessions();

        $middleware->web(append: [
            \App\Http\Middleware\EnsureKycCompleted::class,
            \App\Http\Middleware\EnsureEnterprisePartnerPaid::class,
            \App\Http\Middleware\VerifySessionFingerprint::class,
            \App\Http\Middleware\AddSecurityHeaders::class,
        ]);
        $middleware->alias([
            'super_admin' => \App\Http\Middleware\EnsureIsSuperAdmin::class,
            'support_staff' => \App\Http\Middleware\EnsureIsSupportStaff::class,
            'utility_access' => \App\Http\Middleware\EnsureCanUseUtilities::class,
            'verification_access' => \App\Http\Middleware\EnsureCanVerify::class,
        ]);

         $middleware->validateCsrfTokens(except: [
            '/palmpay/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
