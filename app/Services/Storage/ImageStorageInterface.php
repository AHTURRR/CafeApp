<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;

/** Titik ekstensi: ganti penyedia (Cloudinary/S3) tanpa mengubah ProductService. */
interface ImageStorageInterface
{
    public function store(UploadedFile $file, string $directory): StoredImage;

    /** Aman dipanggil dengan null atau id yang sudah tidak ada. */
    public function delete(?string $publicId): void;
}
