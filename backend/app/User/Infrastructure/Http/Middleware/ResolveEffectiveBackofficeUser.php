<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Middleware;

use App\User\Domain\ValueObject\UserRole;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveEffectiveBackofficeUser
{
    private const HEADER_NAME = 'X-Backoffice-User-Uuid';

    public function __construct(
        private EloquentUser $userModel,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            return $next($request);
        }

        $effectiveUserUuid = $request->header(self::HEADER_NAME);

        if (!$effectiveUserUuid || $effectiveUserUuid === $authenticatedUser->uuid) {
            return $next($request);
        }

        if ($authenticatedUser->role === UserRole::WAITER->value) {
            return new JsonResponse(['message' => 'No autorizado.'], 403);
        }

        $effectiveUser = $this->userModel->newQuery()
            ->where('uuid', $effectiveUserUuid)
            ->where('restaurant_id', $authenticatedUser->restaurant_id)
            ->first();

        if ($effectiveUser === null) {
            return new JsonResponse(['message' => 'Usuario de backoffice no válido.'], 403);
        }

        if (!in_array($effectiveUser->role, [UserRole::ADMIN->value, UserRole::SUPERVISOR->value], true)) {
            return new JsonResponse(['message' => 'Usuario de backoffice no autorizado.'], 403);
        }

        $request->attributes->set('authenticated_user', $authenticatedUser);
        $request->setUserResolver(static fn() => $effectiveUser);

        return $next($request);
    }
}
