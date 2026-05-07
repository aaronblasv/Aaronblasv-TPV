<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\LoginUser\LoginUser;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController
{
    public function __construct(
        private LoginUser $loginUser,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $response = ($this->loginUser)(
            $validated['email'],
            $validated['password'],
        );

        $eloquentUser = EloquentUser::query()
            ->where('email', $validated['email'])
            ->firstOrFail();

        Auth::guard('web')->login($eloquentUser, true);
        $request->session()->regenerate();

        return new JsonResponse($response->toArray());
    }
}
