<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Entrypoint\Http;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ServeImageController
{
    public function __invoke(string $path): Response
    {
        $relativePath = ltrim($path, '/');
        $storageRoot = realpath(storage_path('app/public'));
        $imagePath = realpath(storage_path('app/public/'.$relativePath));

        if ($storageRoot === false || $imagePath === false || ! str_starts_with($imagePath, $storageRoot) || ! File::exists($imagePath)) {
            abort(404);
        }

        return response()->file($imagePath, [
            'Content-Type' => File::mimeType($imagePath) ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}