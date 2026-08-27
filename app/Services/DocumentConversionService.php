<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class DocumentConversionService
{
    /** @return array{path: ?string, status: string} */
    public function convertToPdf(string $sourcePath, string $outputDirectory): array
    {
        $binary = $this->libreOfficeBinary();

        if (! $binary) {
            return ['path' => null, 'status' => 'missing_converter'];
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory($outputDirectory);
        $absoluteSource = $disk->path($sourcePath);
        $absoluteOutput = $disk->path($outputDirectory);
        try {
            $process = new Process([
                $binary,
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $absoluteOutput,
                $absoluteSource,
            ]);
            $process->setTimeout(120);
            $process->run();
        } catch (Throwable) {
            return ['path' => null, 'status' => 'conversion_failed'];
        }

        if (! $process->isSuccessful()) {
            return ['path' => null, 'status' => 'conversion_failed'];
        }

        $previewPath = trim($outputDirectory, '/').'/'.pathinfo($absoluteSource, PATHINFO_FILENAME).'.pdf';

        return $disk->exists($previewPath)
            ? ['path' => $previewPath, 'status' => 'converted']
            : ['path' => null, 'status' => 'conversion_failed'];
    }

    private function libreOfficeBinary(): ?string
    {
        $candidates = array_filter([
            config('documents.libreoffice_binary'),
            'soffice',
            'libreoffice',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        ]);

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, DIRECTORY_SEPARATOR) || str_contains($candidate, ':')) {
                if (is_file($candidate)) {
                    return $candidate;
                }

                continue;
            }

            try {
                $process = new Process([$candidate, '--version']);
                $process->setTimeout(10);
                $process->run();
            } catch (Throwable) {
                continue;
            }

            if ($process->isSuccessful()) {
                return $candidate;
            }
        }

        return null;
    }
}
