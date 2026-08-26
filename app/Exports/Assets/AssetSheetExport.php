<?php

namespace App\Exports\Assets;

use App\Models\Asset;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AssetSheetExport implements FromArray, WithDrawings, WithEvents, WithTitle
{
    /** @var Collection<int, Asset> */
    protected readonly Collection $assets;

    /** @var array<string, mixed> */
    protected readonly array $filters;

    /**
     * @param  Collection<int, Asset>  $assets
     * @param  array<string, mixed>  $filters
     */
    public function __construct(Collection $assets, array $filters = [])
    {
        $this->assets = $assets->values();
        $this->filters = $filters;
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $overdue = $this->assets
            ->filter(fn (Asset $asset): bool => $asset->next_inspection_at?->lt(today()) ?? false)
            ->count();
        $calibrationAttention = $this->assets
            ->filter(fn (Asset $asset): bool => $asset->requires_calibration
                && in_array($asset->calibrationStatus(), ['due_soon', 'expired', 'incomplete'], true))
            ->count();

        $rows = [
            [null, config('branding.name')],
            [null, config('branding.service')],
            [null, config('branding.company')],
            ['DAFTAR ASET ALPHA WELDING ACADEMY'],
            [$this->subtitle()],
            [
                'Total aset', $this->assets->count(), null,
                'Aset aktif', $this->assets->where('status', 'active')->count(), null,
                'Inspeksi terlambat', $overdue, null,
                'Perhatian kalibrasi', $calibrationAttention,
            ],
            $this->headers(),
        ];

        foreach ($this->assets as $asset) {
            $rows[] = $this->assetRow($asset);
        }

        return $rows;
    }

    public function drawings(): BaseDrawing|array
    {
        if (! $this->includesLogo()) {
            return [];
        }

        $drawing = new Drawing;
        $drawing->setName('Logo Alpha Academy');
        $drawing->setDescription('Logo Alpha Academy');
        $drawing->setPath(public_path(config('branding.logo')));
        $drawing->setCoordinates('A1');
        $drawing->setHeight(54);
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(4);

        return $drawing;
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $this->styleBrandHeader($sheet);
                $this->styleSummary($sheet);
                $this->styleTable($sheet);
                $this->decorateAssetRows($sheet);
                $this->configurePrint($sheet);
            },
        ];
    }

    /** @return array<int, string> */
    abstract protected function headers(): array;

    /** @return array<int, mixed> */
    abstract protected function assetRow(Asset $asset): array;

    /** @return array<int, int|float> */
    abstract protected function columnWidths(): array;

    abstract protected function decorateAssetRows(Worksheet $sheet): void;

    abstract protected function printFitToWidth(): int;

    protected function styleBrandHeader(Worksheet $sheet): void
    {
        $lastColumn = $this->lastColumn();
        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells("B1:{$lastColumn}1");
        $sheet->mergeCells("B2:{$lastColumn}2");
        $sheet->mergeCells("B3:{$lastColumn}3");
        $sheet->mergeCells("A4:{$lastColumn}4");
        $sheet->mergeCells("A5:{$lastColumn}5");

        $sheet->getStyle('A1:A3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '188D60']]],
        ]);
        $sheet->getStyle("B1:{$lastColumn}1")->applyFromArray([
            'font' => ['name' => 'Aptos Display', 'size' => 18, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '071B32']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("B2:{$lastColumn}2")->applyFromArray([
            'font' => ['name' => 'Aptos', 'size' => 11, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '188D60']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("B3:{$lastColumn}3")->applyFromArray([
            'font' => ['name' => 'Aptos', 'size' => 10, 'bold' => true, 'color' => ['rgb' => '203C4B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF6F0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '188D60']]],
        ]);
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => ['name' => 'Aptos Display', 'size' => 15, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '071B32']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray([
            'font' => ['name' => 'Aptos', 'size' => 9, 'italic' => true, 'color' => ['rgb' => '52636E']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7F8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        foreach ([1 => 25, 2 => 21, 3 => 21, 4 => 30, 5 => 22] as $row => $height) {
            $sheet->getRowDimension($row)->setRowHeight($height);
        }
    }

    protected function styleSummary(Worksheet $sheet): void
    {
        $sheet->getStyle('A6:'.$this->lastColumn().'6')->applyFromArray([
            'font' => ['name' => 'Aptos', 'size' => 9, 'bold' => true, 'color' => ['rgb' => '203C4B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF6F0']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BDD8C9']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        foreach (['B6', 'E6', 'H6', 'K6'] as $cell) {
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getRowDimension(6)->setRowHeight(24);
    }

    protected function styleTable(Worksheet $sheet): void
    {
        foreach ($this->columnWidths() as $index => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
        }

        $lastColumn = $this->lastColumn();
        $lastRow = $this->lastRow();
        $sheet->getStyle("A7:{$lastColumn}7")->applyFromArray([
            'font' => ['name' => 'Aptos', 'size' => 9, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '071B32']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDE6EA']]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(40);

        if ($lastRow > 7) {
            $sheet->getStyle("A8:{$lastColumn}{$lastRow}")->applyFromArray([
                'font' => ['name' => 'Aptos', 'size' => $this->bodyFontSize(), 'color' => ['rgb' => '203C4B']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDE6EA']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            for ($row = 8; $row <= $lastRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight($this->bodyRowHeight());
                if ($row % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F7FAFB');
                }
            }
        }

        $sheet->setAutoFilter("A7:{$lastColumn}{$lastRow}");
        $sheet->freezePane('A8');
        $sheet->setShowGridlines(false);
    }

    protected function configurePrint(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToPage(true)
            ->setFitToWidth($this->printFitToWidth())
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd(7, 7)
            ->setPrintArea('A1:'.$this->lastColumn().$this->lastRow());

        $sheet->getPageMargins()
            ->setTop(0.35)
            ->setRight(0.2)
            ->setBottom(0.4)
            ->setLeft(0.2)
            ->setHeader(0.15)
            ->setFooter(0.15);

        $sheet->getHeaderFooter()
            ->setOddHeader('&C&B'.config('branding.company'))
            ->setOddFooter('&LDiekspor '.now()->format('d/m/Y H:i').' WIB&RHalaman &P dari &N');
    }

    protected function bodyFontSize(): float
    {
        return 9;
    }

    protected function bodyRowHeight(): float
    {
        return 30;
    }

    protected function includesLogo(): bool
    {
        return true;
    }

    protected function styleLink(Worksheet $sheet, string $cell): void
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['color' => ['rgb' => '0563C1'], 'underline' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    protected function applyTone(Worksheet $sheet, string $cell, string $tone): void
    {
        [$font, $fill] = match ($tone) {
            'success' => ['147A50', 'DCF5E6'],
            'warning' => ['9A5F02', 'FFF1D5'],
            'danger' => ['A83232', 'FDE4E1'],
            'info' => ['176C9C', 'E2F1FB'],
            default => ['52636E', 'EDF1F3'],
        };

        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $font]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    protected function excelDate(?CarbonInterface $date): ?float
    {
        return $date ? ExcelDate::PHPToExcel($date->copy()->startOfDay()) : null;
    }

    private function subtitle(): string
    {
        $parts = collect([
            filled($this->filters['search'] ?? null) ? 'Pencarian: '.trim((string) $this->filters['search']) : null,
            filled($this->filters['category'] ?? null) ? 'Kategori: '.strtoupper((string) $this->filters['category']) : null,
            filled($this->filters['status'] ?? null) ? 'Status: '.(Asset::STATUSES[$this->filters['status']] ?? $this->filters['status']) : null,
        ])->filter()->implode(' | ');

        return 'Diekspor '.now()->translatedFormat('d F Y, H:i').' WIB'.($parts ? ' | '.$parts : ' | Semua aset');
    }

    private function lastColumn(): string
    {
        return Coordinate::stringFromColumnIndex(count($this->headers()));
    }

    private function lastRow(): int
    {
        return max(7, $this->assets->count() + 7);
    }
}
