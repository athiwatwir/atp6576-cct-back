<?php

use App\Http\Middleware\EnsureStudent;
use App\Http\Middleware\EnsureUserCanAccessBackend;
use App\Http\Middleware\VerifyStorefrontKey;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        $middleware->alias([
            'backend' => EnsureUserCanAccessBackend::class,
            'student' => EnsureStudent::class,
            'storefront' => VerifyStorefrontKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            $message = 'ไฟล์ใหญ่เกินขีดจำกัดของเซิร์ฟเวอร์ (upload_max_filesize '.ini_get('upload_max_filesize')
                .', post_max_size '.ini_get('post_max_size').')';

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                    'php_post_max_size' => ini_get('post_max_size'),
                ], 413);
            }

            return back()->with('error', $message);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'กรุณาเข้าสู่ระบบ',
                ], 401);
            }
        });
    })->create();
