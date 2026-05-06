<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class ImageUpload
{
    private function __construct(
        private string $tempPath,
        private string $originalName,
        private ?string $mimeType,
    ) {}

    public static function create(string $tempPath, string $originalName, ?string $mimeType = null): self
    {
        if ($tempPath === '' || ! is_file($tempPath) || ! is_readable($tempPath)) {
            throw new \InvalidArgumentException('Image temp path must point to a readable file.');
        }

        if (trim($originalName) === '') {
            throw new \InvalidArgumentException('Image original name cannot be empty.');
        }

        return new self($tempPath, trim($originalName), $mimeType);
    }

    public function tempPath(): string
    {
        return $this->tempPath;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    public function extension(): string
    {
        $extension = strtolower((string) pathinfo($this->originalName, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'bin';
    }
}
