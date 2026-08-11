<?php

namespace App\Services\Assets;

use App\Models\Asset;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Phar;
use PharData;
use Throwable;

class AssetExcelExporter
{
    /**
     * @param  Collection<int, Asset>  $assets
     * @param  array<string, mixed>  $filters
     */
    public function create(Collection $assets, array $filters = []): string
    {
        $directory = storage_path('app/private/asset-exports');
        File::ensureDirectoryExists($directory);

        $baseName = 'asset-export-'.Str::uuid();
        $zipPath = $directory.'/'.$baseName.'.zip';
        $xlsxPath = $directory.'/'.$baseName.'.xlsx';

        try {
            [$sheetXml, $sheetRelationships] = $this->worksheet($assets, $filters);
            $archive = new PharData($zipPath, 0, null, Phar::ZIP);

            $files = [
                '[Content_Types].xml' => $this->contentTypes(),
                '_rels/.rels' => $this->rootRelationships(),
                'docProps/app.xml' => $this->appProperties(),
                'docProps/core.xml' => $this->coreProperties(),
                'xl/workbook.xml' => $this->workbook(),
                'xl/_rels/workbook.xml.rels' => $this->workbookRelationships(),
                'xl/styles.xml' => $this->styles(),
                'xl/worksheets/sheet1.xml' => $sheetXml,
                'xl/worksheets/_rels/sheet1.xml.rels' => $sheetRelationships,
            ];

            foreach ($files as $path => $contents) {
                $archive[$path] = $contents;
            }

            unset($archive);

            if (! rename($zipPath, $xlsxPath)) {
                throw new \RuntimeException('File Excel aset tidak dapat disiapkan.');
            }

            return $xlsxPath;
        } catch (Throwable $exception) {
            @unlink($zipPath);
            @unlink($xlsxPath);

            throw $exception;
        }
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @param  array<string, mixed>  $filters
     * @return array{string, string}
     */
    private function worksheet(Collection $assets, array $filters): array
    {
        $headers = [
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

        $rows = [];
        $rows[] = $this->row(1, [
            $this->textCell('A1', 'DAFTAR ASET ALPHA WELDING ACADEMY', 1),
        ], 30);
        $rows[] = $this->row(2, [
            $this->textCell('A2', $this->subtitle($filters), 2),
        ], 22);

        $overdue = $assets->filter(fn (Asset $asset): bool => $asset->next_inspection_at?->lt(today()) ?? false)->count();
        $calibrationAttention = $assets->filter(fn (Asset $asset): bool => $asset->requires_calibration
            && in_array($asset->calibrationStatus(), ['due_soon', 'expired', 'incomplete'], true))->count();

        $rows[] = $this->row(4, [
            $this->textCell('A4', 'Total aset', 3),
            $this->numberCell('B4', $assets->count(), 4),
            $this->textCell('D4', 'Aset aktif', 3),
            $this->numberCell('E4', $assets->where('status', 'active')->count(), 4),
            $this->textCell('G4', 'Inspeksi terlambat', 3),
            $this->numberCell('H4', $overdue, 4),
            $this->textCell('J4', 'Perhatian kalibrasi', 3),
            $this->numberCell('K4', $calibrationAttention, 4),
        ], 22);

        $headerCells = [];
        foreach ($headers as $index => $header) {
            $headerCells[] = $this->textCell($this->columnName($index + 1).'6', $header, 5);
        }
        $rows[] = $this->row(6, $headerCells, 34);

        $hyperlinks = [];
        $relationships = [];

        foreach ($assets->values() as $index => $asset) {
            $rowNumber = $index + 7;
            $photoUrl = $asset->photoUrl();
            $informationUrl = route('assets.verify', ['asset' => $asset->public_id]);
            $cells = [
                $this->textCell("A{$rowNumber}", $asset->asset_code, 6),
                $this->textCell("B{$rowNumber}", $asset->category_code.' | '.$asset->categoryLabel(), 6),
                $this->textCell("C{$rowNumber}", $asset->equipment_name, 6),
                $this->textCell("D{$rowNumber}", $asset->brand, 6),
                $this->textCell("E{$rowNumber}", $asset->model, 6),
                $this->textCell("F{$rowNumber}", $asset->serial_number, 6),
                $this->numberCell("G{$rowNumber}", $asset->quantity, 7),
                $this->numberCell("H{$rowNumber}", $asset->purchase_year, 7),
                $this->textCell("I{$rowNumber}", $asset->location, 6),
                $this->textCell("J{$rowNumber}", $asset->conditionLabel(), 6),
                $this->textCell("K{$rowNumber}", $asset->statusLabel(), $this->toneStyle($asset->statusTone())),
                $this->textCell("L{$rowNumber}", $asset->inspectionIntervalLabel(), 6),
                $this->dateCell("M{$rowNumber}", $asset->last_inspected_at),
                $this->dateCell("N{$rowNumber}", $asset->next_inspection_at),
                $this->textCell("O{$rowNumber}", $asset->inspectionStatusLabel(), $this->toneStyle($asset->inspectionTone())),
                $this->textCell("P{$rowNumber}", $asset->requires_calibration ? 'Ya' : 'Tidak', 6),
                $this->textCell("Q{$rowNumber}", $asset->calibrationStatusLabel(), $this->toneStyle($asset->calibrationTone())),
                $this->dateCell("R{$rowNumber}", $asset->calibrated_at),
                $this->dateCell("S{$rowNumber}", $asset->calibration_due_at),
                $this->textCell("T{$rowNumber}", $asset->certificate_number, 6),
                $this->textCell("U{$rowNumber}", $asset->notes, 15),
                $this->textCell("V{$rowNumber}", $photoUrl, $photoUrl ? 9 : 6),
                $this->textCell("W{$rowNumber}", $informationUrl, 9),
            ];

            if ($photoUrl) {
                $this->addHyperlink("V{$rowNumber}", $photoUrl, $hyperlinks, $relationships);
            }
            $this->addHyperlink("W{$rowNumber}", $informationUrl, $hyperlinks, $relationships);

            $rows[] = $this->row($rowNumber, $cells, 24);
        }

        $lastRow = max(6, $assets->count() + 6);
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="A1:W'.$lastRow.'"/>'
            .'<sheetViews><sheetView tabSelected="1" workbookViewId="0" showGridLines="0"><pane ySplit="6" topLeftCell="A7" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="20"/>'
            .$this->columns()
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'<mergeCells count="2"><mergeCell ref="A1:W1"/><mergeCell ref="A2:W2"/></mergeCells>'
            .'<autoFilter ref="A6:W'.$lastRow.'"/>'
            .($hyperlinks ? '<hyperlinks>'.implode('', $hyperlinks).'</hyperlinks>' : '')
            .'<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            .'<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>'
            .'</worksheet>';

        $relationshipXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .implode('', $relationships)
            .'</Relationships>';

        return [$sheetXml, $relationshipXml];
    }

    /** @param array<int, string> $hyperlinks @param array<int, string> $relationships */
    private function addHyperlink(string $cell, string $url, array &$hyperlinks, array &$relationships): void
    {
        $id = count($relationships) + 1;
        $hyperlinks[] = '<hyperlink ref="'.$cell.'" r:id="rId'.$id.'"/>';
        $relationships[] = '<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="'.$this->xml($url, true).'" TargetMode="External"/>';
    }

    /** @param array<int, string> $cells */
    private function row(int $number, array $cells, int $height): string
    {
        return '<row r="'.$number.'" ht="'.$height.'" customHeight="1">'.implode('', $cells).'</row>';
    }

    private function textCell(string $reference, ?string $value, int $style): string
    {
        if ($value === null || $value === '') {
            return '<c r="'.$reference.'" s="'.$style.'"/>';
        }

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
    }

    private function numberCell(string $reference, int|float|null $value, int $style): string
    {
        return $value === null
            ? '<c r="'.$reference.'" s="'.$style.'"/>'
            : '<c r="'.$reference.'" s="'.$style.'" t="n"><v>'.$value.'</v></c>';
    }

    private function dateCell(string $reference, ?CarbonInterface $date): string
    {
        if (! $date) {
            return '<c r="'.$reference.'" s="8"/>';
        }

        $utcDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date->format('Y-m-d'), new DateTimeZone('UTC'));
        $serial = ((int) $utcDate->format('U') / 86400) + 25569;

        return $this->numberCell($reference, $serial, 8);
    }

    private function toneStyle(string $tone): int
    {
        return match ($tone) {
            'success' => 10,
            'warning' => 11,
            'danger' => 12,
            'info' => 13,
            default => 14,
        };
    }

    /** @param array<string, mixed> $filters */
    private function subtitle(array $filters): string
    {
        $parts = collect([
            filled($filters['search'] ?? null) ? 'Pencarian: '.trim((string) $filters['search']) : null,
            filled($filters['category'] ?? null) ? 'Kategori: '.strtoupper((string) $filters['category']) : null,
            filled($filters['status'] ?? null) ? 'Status: '.(Asset::STATUSES[$filters['status']] ?? $filters['status']) : null,
        ])->filter()->implode(' | ');

        return 'Diekspor '.now()->translatedFormat('d F Y, H:i').' WIB'.($parts ? ' | '.$parts : ' | Semua aset');
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function xml(string $value, bool $attribute = false): string
    {
        return htmlspecialchars($value, ENT_XML1 | ($attribute ? ENT_QUOTES : ENT_COMPAT), 'UTF-8');
    }

    private function columns(): string
    {
        $widths = [16, 29, 30, 18, 20, 18, 10, 15, 27, 14, 19, 20, 18, 18, 20, 17, 22, 18, 20, 20, 35, 42, 48];
        $columns = '';
        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $columns .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        return '<cols>'.$columns.'</cols>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView activeTab="0"/></bookViews>'
            .'<sheets><sheet name="Daftar Aset" sheetId="1" r:id="rId1"/></sheets>'
            .'<calcPr calcId="191029" fullCalcOnLoad="1"/>'
            .'</workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="dd mmm yyyy"/></numFmts>'
            .'<fonts count="10">'
            .'<font><sz val="11"/><name val="Aptos"/><family val="2"/></font>'
            .'<font><b/><color rgb="FFFFFFFF"/><sz val="16"/><name val="Aptos Display"/></font>'
            .'<font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Aptos"/></font>'
            .'<font><u/><color rgb="FF0563C1"/><sz val="10"/><name val="Aptos"/></font>'
            .'<font><b/><color rgb="FF147A50"/><sz val="10"/><name val="Aptos"/></font>'
            .'<font><b/><color rgb="FF9A5F02"/><sz val="10"/><name val="Aptos"/></font>'
            .'<font><b/><color rgb="FFA83232"/><sz val="10"/><name val="Aptos"/></font>'
            .'<font><b/><color rgb="FF176C9C"/><sz val="10"/><name val="Aptos"/></font>'
            .'<font><b/><color rgb="FF52636E"/><sz val="10"/><name val="Aptos"/></font>'
            .'<font><b/><color rgb="FF203C4B"/><sz val="10"/><name val="Aptos"/></font>'
            .'</fonts>'
            .'<fills count="11">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF071B32"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF188D60"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEAF6F0"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF4F7F8"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFDCF5E6"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFF1D5"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFDE4E1"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE2F1FB"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEDF1F3"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2"><border/><border><bottom style="thin"><color rgb="FFDDE6EA"/></bottom></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="16">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="5" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="9" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="9" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="5" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="6" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="7" fillId="9" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="8" fillId="10" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'<dxfs count="0"/><tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>'
            .'</styleSheet>';
    }

    private function coreProperties(): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>Alpha Welding Academy</dc:creator><cp:lastModifiedBy>Alpha Welding Academy</cp:lastModifiedBy>'
            .'<dc:title>Daftar Aset Alpha Welding Academy</dc:title>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Alpha Welding Academy</Application><AppVersion>1.0</AppVersion>'
            .'<TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>Daftar Aset</vt:lpstr></vt:vector></TitlesOfParts>'
            .'</Properties>';
    }
}
