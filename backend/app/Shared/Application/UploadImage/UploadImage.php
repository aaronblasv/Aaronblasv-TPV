<?php

declare(strict_types=1);

namespace App\Shared\Application\UploadImage;

use App\Shared\Domain\Interfaces\ImageUploaderInterface;
use App\Shared\Domain\ValueObject\ImageUpload;

class UploadImage
{
    public function __construct(private ImageUploaderInterface $imageUploader) {}

    public function __invoke(ImageUpload $image): string
    {
        return $this->imageUploader->upload($image);
    }
}
