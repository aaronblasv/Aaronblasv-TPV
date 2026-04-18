<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Entrypoint\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadImageController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $path = $request->file('image')->store('images', 'public');

        $url = asset('storage/' . $path);

        return new JsonResponse(['url' => $url]);
    }
}
