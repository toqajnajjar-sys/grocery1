<?php

namespace App\Strategies\Uploads;

use App\Contracts\UploadStrategy;
use Illuminate\Http\UploadedFile;

final class PublicDirectoryUpload implements UploadStrategy
{
    public function __construct(private string $directory) {}

    public function store(UploadedFile $file): string
    {
        $name = $file->hashName();
        $file->move(public_path($this->directory), $name);

        return $name;
    }
}
