<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Middleware;

use App\User\Domain\ValueObject\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireBackofficeRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role === UserRole::WAITER->value) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return $next($request);
    }
}