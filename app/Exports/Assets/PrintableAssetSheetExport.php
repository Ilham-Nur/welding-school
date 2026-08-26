<?php

namespace App\Exports\Assets;

use App\Models\Asset;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrintableAssetSheetExport extends AssetSheetExport
{
    public function title(): string
    {
        return 'Daftar Aset';
    }

    /** @return array<int, string> */
    protected function headers(): array
    {
        return [
            'Asset ID',
            'Kategori',
            'Nama Alat',
            'Merek / Model',
            'Jumlah',
            'Lokasi',
            'Kondisi',
            'Status Alat',
            'Inspeksi Berikutnya',
            'Status Inspeksi',
            'Status Kalibrasi',
        ];
    }

    /** @return array<int, mixed> */
    protected function assetRow(Asset $asset): array
    {
        $brandModel = collect([$asset->brand, $asset->model])
            ->filter(fn (?string $value): bool => filled($value))
            ->implode(' / ');

        return [
            $asset->asset_code,
            $asset->category_code,
            $asset->equipment_name,
            $brandModel ?: null,
            $asset->quantity,
            $asset->location,
            $asset->conditionLabel(),
            $asset->statusLabel(),
            $this->excelDate($asset->next_inspection_at),
            $asset->inspectionStatusLabel(),
            $asset->calibrationStatusLabel(),
        ];
    }

    /** @return array<int, int|float> */
    protected function columnWidths(): array
    {
        return [13, 8, 20, 16, 6, 16, 9, 13, 13, 15, 16];
    }

    protected function decorateAssetRows(Worksheet $sheet): void
    {
        $lastRow = max(7, $this->assets->count() + 7);
        if ($lastRow === 7) {
            return;
        }

        $sheet->getStyle("E8:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I8:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I8:I{$lastRow}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
        foreach (['C', 'D', 'F'] as $column) {
            $sheet->getStyle("{$column}8:{$column}{$lastRow}")->getAlignment()->setWrapText(true);
        }

        foreach ($this->assets as $index => $asset) {
            $row = $index + 8;
            $this->applyTone($sheet, "H{$row}", $asset->statusTone());
            $this->applyTone($sheet, "J{$row}", $asset->inspectionTone());
            $this->applyTone($sheet, "K{$row}", $asset->calibrationTone());
        }
    }

    protected function printFitToWidth(): int
    {
        return 1;
    }

    protected function bodyFontSize(): float
    {
        return 10;
    }

    protected function bodyRowHeight(): float
    {
        return 32;
    }
}
