<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\SetCurrentCompany;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company' => SetCurrentCompany::class,
            'permission' => EnsurePermission::class,
        ]);

        // The tenant context must exist before route model binding resolves a
        // company scoped parameter, and the permission check belongs in front
        // of both so an unauthorized request never reaches the database.
        $middleware->prependToPriorityList(SubstituteBindings::class, SetCurrentCompany::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsurePermission::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $exception->errors(),
            ], $exception->status);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
            ], 401);
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception->getStatusCode();

            return response()->json([
                'success' => false,
                'message' => match ($status) {
                    401 => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
                    403 => 'Anda tidak memiliki akses untuk melakukan aksi ini.',
                    404 => 'Resource tidak ditemukan.',
                    405 => 'Metode request tidak diizinkan.',
                    429 => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
                    default => $exception->getMessage() ?: 'Terjadi kesalahan pada server.',
                },
            ], $status);
        });
    })->create();
