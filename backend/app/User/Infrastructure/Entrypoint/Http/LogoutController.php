<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\LogoutUser\LogoutUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController
{
    public function __construct(
        private LogoutUser $logoutUser,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userUuid = $request->user()?->uuid;

        if ($userUuid !== null) {
            ($this->logoutUser)($userUuid);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse(null, 204);
    }
}
