<?php

namespace App\Exports\Assets;

use App\Models\Asset;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DetailedAssetSheetExport extends AssetSheetExport
{
    public function title(): string
    {
        return 'Data Lengkap';
    }

    /** @return array<int, string> */
    protected function headers(): array
    {
        return [
            'Asset ID',
            'Kategori',
            'Nama Alat',
            'Merek',
            'Model / Type',
            'Serial Number',
            'Jumlah',
            'Tahun Pembelian',
            'Lokasi',
            'Kondisi',
            'Status Alat',
            'Interval Inspeksi',
            'Inspeksi Terakhir',
            'Inspeksi Berikutnya',
            'Status Inspeksi',
            'Wajib Kalibrasi',
            'Status Kalibrasi',
            'Tanggal Kalibrasi',
            'Jatuh Tempo Kalibrasi',
            'Nomor Sertifikat',
            'Catatan',
            'Foto Aset',
            'Informasi QR',
        ];
    }

    /** @return array<int, mixed> */
    protected function assetRow(Asset $asset): array
    {
        $photoUrl = $asset->photoUrl();

        return [
            $asset->asset_code,
            $asset->category_code.' | '.$asset->categoryLabel(),
            $asset->equipment_name,
            $asset->brand,
            $asset->model,
            $asset->serial_number,
            $asset->quantity,
            $asset->purchase_year,
            $asset->location,
            $asset->conditionLabel(),
            $asset->statusLabel(),
            $asset->inspectionIntervalLabel(),
            $this->excelDate($asset->last_inspected_at),
            $this->excelDate($asset->next_inspection_at),
            $asset->inspectionStatusLabel(),
            $asset->requires_calibration ? 'Ya' : 'Tidak',
            $asset->calibrationStatusLabel(),
            $this->excelDate($asset->calibrated_at),
            $this->excelDate($asset->calibration_due_at),
            $asset->certificate_number,
            $asset->notes,
            $photoUrl ? 'Buka foto' : null,
            'Buka informasi aset',
        ];
    }

    /** @return array<int, int|float> */
    protected function columnWidths(): array
    {
        return [15, 26, 27, 16, 18, 17, 8, 13, 23, 12, 17, 18, 16, 16, 18, 15, 20, 16, 18, 18, 30, 14, 19];
    }

    protected function decorateAssetRows(Worksheet $sheet): void
    {
        $lastRow = max(7, $this->assets->count() + 7);
        if ($lastRow === 7) {
            return;
        }

        $sheet->getStyle("G8:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("M8:S{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("M8:N{$lastRow}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
        $sheet->getStyle("R8:S{$lastRow}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
        foreach (['C', 'I', 'L'] as $column) {
            $sheet->getStyle("{$column}8:{$column}{$lastRow}")->getAlignment()->setWrapText(true);
        }
        $sheet->getStyle("U8:W{$lastRow}")->getAlignment()->setWrapText(true);

        foreach ($this->assets as $index => $asset) {
            $row = $index + 8;
            $photoUrl = $asset->photoUrl();
            if ($photoUrl) {
                $sheet->getCell("V{$row}")->getHyperlink()
                    ->setUrl($photoUrl)
                    ->setTooltip('Buka foto aset');
                $this->styleLink($sheet, "V{$row}");
            }

            $sheet->getCell("W{$row}")->getHyperlink()
                ->setUrl(route('assets.verify', ['asset' => $asset->public_id]))
                ->setTooltip('Buka halaman informasi aset');
            $this->styleLink($sheet, "W{$row}");

            $this->applyTone($sheet, "K{$row}", $asset->statusTone());
            $this->applyTone($sheet, "O{$row}", $asset->inspectionTone());
            $this->applyTone($sheet, "Q{$row}", $asset->calibrationTone());
        }
    }

    protected function printFitToWidth(): int
    {
        return 3;
    }

    protected function includesLogo(): bool
    {
        return false;
    }
}
