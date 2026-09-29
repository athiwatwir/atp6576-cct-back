<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('student')) {
            abort(403, 'บัญชีนี้ใช้กับระบบนักเรียนไม่ได้');
        }

        if ($user->status !== AccountStatus::Active->value) {
            abort(403, 'บัญชีนี้ถูกปิดใช้งาน');
        }

        return $next($request);
    }
}
