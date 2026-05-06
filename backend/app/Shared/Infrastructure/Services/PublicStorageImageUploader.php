<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Services;

use App\Shared\Domain\Interfaces\ImageUploaderInterface;
use App\Shared\Domain\ValueObject\ImageUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicStorageImageUploader implements ImageUploaderInterface
{
    public function upload(ImageUpload $image): string
    {
        $path = 'images/'.Str::uuid()->toString().'.'.$image->extension();

        Storage::disk('public')->put($path, fopen($image->tempPath(), 'rb'));

        return asset('storage/'.$path);
    }
}
