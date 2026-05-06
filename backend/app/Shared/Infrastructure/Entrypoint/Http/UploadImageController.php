<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Entrypoint\Http;

use App\Shared\Application\UploadImage\UploadImage;
use App\Shared\Domain\ValueObject\ImageUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadImageController
{
    public function __construct(private UploadImage $useCase) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $file = $request->file('image');

        $url = ($this->useCase)(ImageUpload::create(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getMimeType(),
        ));

        return new JsonResponse(['url' => $url]);
    }
}
