<?php

namespace App\Services\Assets;

use App\Exports\Assets\AssetWorkbookExport;
use App\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class AssetExcelExporter
{
    /**
     * @param  Collection<int, Asset>  $assets
     * @param  array<string, mixed>  $filters
     */
    public function create(Collection $assets, array $filters = []): string
    {
        $relativePath = 'asset-exports/asset-export-'.Str::uuid().'.xlsx';
        $stored = Excel::store(
            new AssetWorkbookExport($assets, $filters),
            $relativePath,
            'local',
            ExcelWriter::XLSX,
        );

        if ($stored !== true) {
            throw new RuntimeException('File Excel aset tidak dapat disiapkan.');
        }

        return Storage::disk('local')->path($relativePath);
    }
}
