<?php

declare(strict_types=1);

namespace App\Shared\Domain\Interfaces;

use App\Shared\Domain\ValueObject\ImageUpload;

interface ImageUploaderInterface
{
    public function upload(ImageUpload $image): string;
}
