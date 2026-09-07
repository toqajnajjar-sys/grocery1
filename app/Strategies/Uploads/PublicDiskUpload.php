<?php

namespace App\Strategies\Uploads;

use App\Contracts\UploadStrategy;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class PublicDiskUpload implements UploadStrategy
{
    public function __construct(private string $directory) {}

    public function store(UploadedFile $file): string
    {
        $path = $file->store($this->directory, 'public');
        if ($path === false) {
            throw new RuntimeException('Unable to store uploaded file.');
        }

        return $path;
    }
}
