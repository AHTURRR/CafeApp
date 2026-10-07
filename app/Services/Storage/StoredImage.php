<?php

namespace App\Services\Storage;

final readonly class StoredImage
{
    public function __construct(
        public string $url,
        public string $publicId,
    ) {
    }
}
