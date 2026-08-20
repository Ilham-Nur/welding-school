<?php

namespace App\Services\Storage;

use App\Models\StorageTransactionLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StorageReportPdfExporter
{
    private const PAGE_WIDTH = 842;

    private const PAGE_HEIGHT = 595;

    /** @param Collection<int, StorageTransactionLine> $movements */
    public function create(Collection $movements, array $filters = []): string
    {
        $directory = storage_path('app/private/storage-report-exports');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/storage-report-'.Str::uuid().'.pdf';
        [$image, $imageWidth, $imageHeight] = $this->logoAsJpeg();
        $pages = $movements->chunk(16);
        if ($pages->isEmpty()) {
            $pages = collect([collect()]);
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /XObject /Subtype /Image /Width '.$imageWidth.' /Height '.$imageHeight.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($image)." >>\nstream\n".$image."\nendstream",
        ];

        $pageIds = [];
        foreach ($pages->values() as $index => $rows) {
            $pageId = 6 + ($index * 2);
            $contentId = $pageId + 1;
            $pageIds[] = $pageId.' 0 R';
            $content = $this->pageContent($rows, $filters, $index + 1, $pages->count());
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << /Logo 5 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $objects[$contentId] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageIds).'] /Count '.count($pageIds).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id])."\n";
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n{$xref}\n%%EOF";
        File::put($path, $pdf);

        return $path;
    }

    /** @param Collection<int, StorageTransactionLine> $rows */
    private function pageContent(Collection $rows, array $filters, int $page, int $totalPages): string
    {
        $company = config('branding.company');
        $content = "q 48 0 0 48 34 515 cm /Logo Do Q\n";
        $content .= $this->text(94, 552, 16, $company, true, [0.04, 0.17, 0.29]);
        $content .= $this->text(94, 531, 11, 'LAPORAN PERGERAKAN STORAGE', true, [0.08, 0.55, 0.38]);
        $content .= $this->text(94, 514, 8, $this->subtitle($filters), false, [0.38, 0.45, 0.5]);
        $content .= "0.08 0.55 0.38 RG 1.2 w 34 500 m 808 500 l S\n";

        $columns = [58, 92, 140, 105, 65, 65, 50, 198];
        $headers = ['Tanggal', 'Nomor', 'Consumable', 'Lokasi', 'Jenis', 'Jumlah', 'Satuan', 'Tujuan / Sumber'];
        $x = 34;
        $headerY = 470;
        foreach ($headers as $index => $header) {
            $width = $columns[$index];
            $content .= "0.04 0.17 0.29 rg {$x} {$headerY} {$width} 25 re f\n";
            $content .= $this->text($x + 5, $headerY + 9, 7, $header, true, [1, 1, 1]);
            $x += $width;
        }

        $y = $headerY - 24;
        foreach ($rows->values() as $index => $line) {
            if ($index % 2 === 1) {
                $content .= "0.96 0.98 0.98 rg 34 {$y} 773 24 re f\n";
            }
            $transaction = $line->transaction;
            $values = [
                $transaction->transaction_date->format('d/m/Y'),
                $transaction->number,
                $line->item->name,
                $transaction->location->fullName(),
                ucfirst($transaction->type),
                format_quantity($line->quantity),
                $line->item->unit,
                $transaction->purpose ?? $transaction->supplier ?? $transaction->reference ?? 'Tidak dicatat',
            ];
            $x = 34;
            foreach ($values as $column => $value) {
                $content .= $this->text($x + 5, $y + 9, 7.2, $this->truncate($value, max(6, (int) ($columns[$column] / 5.2))), false, [0.18, 0.25, 0.3]);
                $x += $columns[$column];
            }
            $content .= "0.86 0.9 0.92 RG 0.4 w 34 {$y} m 807 {$y} l S\n";
            $y -= 24;
        }

        if ($rows->isEmpty()) {
            $content .= $this->text(34, 430, 10, 'Tidak ada data sesuai filter.', false, [0.38, 0.45, 0.5]);
        }

        $content .= "0.86 0.9 0.92 RG 0.6 w 34 35 m 808 35 l S\n";
        $content .= $this->text(34, 20, 7.5, $company.' | Dicetak '.now()->format('d/m/Y H:i').' WIB', false, [0.38, 0.45, 0.5]);
        $content .= $this->text(735, 20, 7.5, "Halaman {$page} dari {$totalPages}", false, [0.38, 0.45, 0.5]);

        return $content;
    }

    private function text(float $x, float $y, float $size, string $text, bool $bold, array $color): string
    {
        $font = $bold ? 'F2' : 'F1';
        $safe = $this->pdfText($text);

        return implode(' ', $color)." rg BT /{$font} {$size} Tf {$x} {$y} Td ({$safe}) Tj ET\n";
    }

    private function pdfText(string $value): string
    {
        $value = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $value);
    }

    private function truncate(string $value, int $length): string
    {
        return mb_strimwidth($value, 0, $length, '...');
    }

    private function subtitle(array $filters): string
    {
        $parts = collect([
            filled($filters['from'] ?? null) ? 'Dari '.date('d/m/Y', strtotime($filters['from'])) : null,
            filled($filters['to'] ?? null) ? 'Sampai '.date('d/m/Y', strtotime($filters['to'])) : null,
        ])->filter()->implode(' | ');

        return $parts ?: 'Seluruh periode';
    }

    /** @return array{string, int, int} */
    private function logoAsJpeg(): array
    {
        $source = imagecreatefrompng(public_path(config('branding.logo')));
        if (! $source) {
            throw new \RuntimeException('Logo perusahaan tidak dapat dibaca.');
        }
        $canvas = imagecreatetruecolor(220, 220);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, 220, 220, imagesx($source), imagesy($source));
        ob_start();
        imagejpeg($canvas, null, 88);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        return [$jpeg, 220, 220];
    }
}
