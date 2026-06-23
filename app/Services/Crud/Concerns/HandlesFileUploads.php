<?php

namespace App\Services\Crud\Concerns;

use Illuminate\Support\Facades\Storage;

trait HandlesFileUploads
{
    protected function moveTempFileToFinalPath(string $tempPath, string $table): string
    {
        $filename = basename($tempPath);
        $finalPath = "uploads/{$table}/{$filename}";

        Storage::disk('document')->move($tempPath, $finalPath);

        return $finalPath;
    }

    protected function deleteFileIfExists(?string $path): void
    {
        if (!empty($path)) {
            Storage::disk('document')->delete($path);
        }
    }

    protected function isTempUpload($value): bool
    {
        return !empty($value) && is_string($value) && str_starts_with($value, 'temp/');
    }
}