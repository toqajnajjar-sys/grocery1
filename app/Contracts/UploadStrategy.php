<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface UploadStrategy
{
    public function store(UploadedFile $file): string;
}
