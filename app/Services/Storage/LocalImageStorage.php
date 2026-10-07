<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class LocalImageStorage implements ImageStorageInterface
{
    public function store(UploadedFile $file, string $directory): StoredImage
    {
        $disk = Storage::disk(config('cafe.image_disk'));

        // putFile membuat nama acak, sehingga nama berkas dari pengguna tidak pernah dipakai.
        $path = $disk->putFile($directory, $file);

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan gambar.');
        }

        return new StoredImage($disk->url($path), $path);
    }

    public function delete(?string $publicId): void
    {
        if ($publicId !== null && $publicId !== '') {
            Storage::disk(config('cafe.image_disk'))->delete($publicId);
        }
    }
}
