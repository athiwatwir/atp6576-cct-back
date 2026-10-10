<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyStorefrontKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.storefront.key');
        $given = (string) $request->header('X-Api-Key', '');

        if ($expected === '' || $given === '' || strlen($given) !== strlen($expected) || ! hash_equals($expected, $given)) {
            return response()->json([
                'message' => 'ไม่ได้รับอนุญาต',
            ], 401);
        }

        return $next($request);
    }
}
