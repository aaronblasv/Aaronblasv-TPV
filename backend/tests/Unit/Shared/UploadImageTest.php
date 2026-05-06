<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Application\UploadImage\UploadImage;
use App\Shared\Domain\Interfaces\ImageUploaderInterface;
use App\Shared\Domain\ValueObject\ImageUpload;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UploadImageTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_delegates_image_upload_to_the_uploader(): void
    {
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        fwrite($tempFile, 'fake-image-bytes');

        $image = ImageUpload::create($tempPath, 'avatar.png', 'image/png');

        $uploader = Mockery::mock(ImageUploaderInterface::class);
        $uploader->shouldReceive('upload')->once()->with($image)->andReturn('https://example.com/storage/images/avatar.png');

        $useCase = new UploadImage($uploader);

        $this->assertSame('https://example.com/storage/images/avatar.png', $useCase($image));

        fclose($tempFile);
    }
}
