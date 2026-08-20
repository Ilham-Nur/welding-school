<?php

namespace App\Services\Storage;

use App\Models\StorageTransactionLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Phar;
use PharData;
use Throwable;

class StorageReportExcelExporter
{
    /** @param Collection<int, StorageTransactionLine> $movements */
    public function create(Collection $movements, array $filters = []): string
    {
        $directory = storage_path('app/private/storage-report-exports');
        File::ensureDirectoryExists($directory);
        $baseName = 'storage-report-'.Str::uuid();
        $zipPath = $directory.'/'.$baseName.'.zip';
        $xlsxPath = $directory.'/'.$baseName.'.xlsx';

        try {
            $archive = new PharData($zipPath, 0, null, Phar::ZIP);
            foreach ($this->files($movements, $filters) as $path => $contents) {
                $archive[$path] = $contents;
            }
            unset($archive);

            if (! rename($zipPath, $xlsxPath)) {
                throw new \RuntimeException('Laporan Excel tidak dapat disiapkan.');
            }

            return $xlsxPath;
        } catch (Throwable $exception) {
            @unlink($zipPath);
            @unlink($xlsxPath);
            throw $exception;
        }
    }

    /** @param Collection<int, StorageTransactionLine> $movements */
    private function files(Collection $movements, array $filters): array
    {
        return [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRelationships(),
            'docProps/app.xml' => $this->appProperties(),
            'docProps/core.xml' => $this->coreProperties(),
            'xl/workbook.xml' => $this->workbook(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationships(),
            'xl/styles.xml' => $this->styles(),
            'xl/worksheets/sheet1.xml' => $this->worksheet($movements, $filters),
            'xl/worksheets/_rels/sheet1.xml.rels' => $this->worksheetRelationships(),
            'xl/drawings/drawing1.xml' => $this->drawing(),
            'xl/drawings/_rels/drawing1.xml.rels' => $this->drawingRelationships(),
            'xl/media/logo.png' => File::get(public_path(config('branding.logo'))),
        ];
    }

    /** @param Collection<int, StorageTransactionLine> $movements */
    private function worksheet(Collection $movements, array $filters): string
    {
        $company = config('branding.company');
        $rows = [
            $this->row(1, [$this->textCell('B1', $company, 1)], 34),
            $this->row(2, [$this->textCell('B2', 'LAPORAN PERGERAKAN STORAGE', 2)], 24),
            $this->row(3, [$this->textCell('B3', $this->subtitle($filters), 3)], 20),
            $this->row(5, [
                $this->textCell('A5', 'Total baris', 4), $this->numberCell('B5', $movements->count(), 5),
                $this->textCell('D5', 'Penerimaan', 4), $this->numberCell('E5', $movements->filter(fn ($line) => $line->transaction->type === 'receipt')->count(), 5),
                $this->textCell('G5', 'Pengeluaran', 4), $this->numberCell('H5', $movements->filter(fn ($line) => $line->transaction->type === 'issue')->count(), 5),
            ], 22),
        ];

        $headers = ['Tanggal', 'Nomor', 'Consumable', 'Kode', 'Lokasi', 'Jenis', 'Jumlah', 'Satuan', 'Tujuan / Sumber'];
        $headerCells = [];
        foreach ($headers as $index => $header) {
            $headerCells[] = $this->textCell($this->columnName($index + 1).'7', $header, 6);
        }
        $rows[] = $this->row(7, $headerCells, 30);

        foreach ($movements->values() as $index => $line) {
            $row = $index + 8;
            $transaction = $line->transaction;
            $rows[] = $this->row($row, [
                $this->textCell("A{$row}", $transaction->transaction_date->format('d/m/Y'), 7),
                $this->textCell("B{$row}", $transaction->number, 7),
                $this->textCell("C{$row}", $line->item->name, 7),
                $this->textCell("D{$row}", $line->item->code, 7),
                $this->textCell("E{$row}", $transaction->location->fullName(), 7),
                $this->textCell("F{$row}", ucfirst($transaction->type), 7),
                $this->numberCell("G{$row}", (float) $line->quantity, 8),
                $this->textCell("H{$row}", $line->item->unit, 7),
                $this->textCell("I{$row}", $transaction->purpose ?? $transaction->supplier ?? $transaction->reference ?? 'Tidak dicatat', 9),
            ], 23);
        }

        $lastRow = max(7, $movements->count() + 7);
        $footer = $this->xml($company).' | Dicetak '.now()->format('d/m/Y H:i');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="A1:I'.$lastRow.'"/><sheetViews><sheetView tabSelected="1" workbookViewId="0" showGridLines="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="20"/><cols>'
            .'<col min="1" max="1" width="14" customWidth="1"/><col min="2" max="2" width="23" customWidth="1"/><col min="3" max="3" width="30" customWidth="1"/><col min="4" max="4" width="18" customWidth="1"/><col min="5" max="5" width="26" customWidth="1"/><col min="6" max="6" width="15" customWidth="1"/><col min="7" max="7" width="14" customWidth="1"/><col min="8" max="8" width="12" customWidth="1"/><col min="9" max="9" width="38" customWidth="1"/>'
            .'</cols><sheetData>'.implode('', $rows).'</sheetData>'
            .'<mergeCells count="3"><mergeCell ref="B1:I1"/><mergeCell ref="B2:I2"/><mergeCell ref="B3:I3"/></mergeCells>'
            .'<autoFilter ref="A7:I'.$lastRow.'"/><drawing r:id="rId1"/>'
            .'<headerFooter><oddHeader>&amp;C&amp;B'.$this->xml($company).'</oddHeader><oddFooter>&amp;L'.$footer.'&amp;RHalaman &amp;P dari &amp;N</oddFooter></headerFooter>'
            .'<pageMargins left="0.3" right="0.3" top="0.65" bottom="0.55" header="0.25" footer="0.25"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>'
            .'</worksheet>';
    }

    private function drawing(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>85000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>50000</xdr:rowOff></xdr:from><xdr:ext cx="620000" cy="620000"/>'
            .'<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Logo perusahaan"/><xdr:cNvPicPr/></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';
    }

    private function worksheetRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>';
    }

    private function drawingRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.png"/></Relationships>';
    }

    private function row(int $number, array $cells, int $height): string
    {
        return '<row r="'.$number.'" ht="'.$height.'" customHeight="1">'.implode('', $cells).'</row>';
    }

    private function textCell(string $reference, ?string $value, int $style): string
    {
        return blank($value) ? '<c r="'.$reference.'" s="'.$style.'"/>' : '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
    }

    private function numberCell(string $reference, int|float $value, int $style): string
    {
        return '<c r="'.$reference.'" s="'.$style.'" t="n"><v>'.$value.'</v></c>';
    }

    private function subtitle(array $filters): string
    {
        $parts = collect([
            filled($filters['from'] ?? null) ? 'Dari '.date('d/m/Y', strtotime($filters['from'])) : null,
            filled($filters['to'] ?? null) ? 'Sampai '.date('d/m/Y', strtotime($filters['to'])) : null,
        ])->filter()->implode(' | ');

        return $parts ?: 'Seluruh periode';
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

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView activeTab="0"/></bookViews><sheets><sheet name="Laporan Storage" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#\,##0.000"/></numFmts><fonts count="5"><font><sz val="10"/><name val="Aptos"/></font><font><b/><color rgb="FF0A2C49"/><sz val="16"/><name val="Aptos Display"/></font><font><b/><color rgb="FF148C61"/><sz val="12"/><name val="Aptos"/></font><font><color rgb="FF6B7D89"/><sz val="9"/><name val="Aptos"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="9"/><name val="Aptos"/></font></fonts>'
            .'<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0A2C49"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE9F8F2"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF4F7F9"/></patternFill></fill></fills>'
            .'<borders count="2"><border/><border><bottom style="thin"><color rgb="FFDDE6EA"/></bottom></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="10">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="4" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function coreProperties(): string
    {
        $time = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>'.$this->xml(config('branding.company')).'</dc:creator><dc:title>Laporan Storage</dc:title><dcterms:created xsi:type="dcterms:W3CDTF">'.$time.'</dcterms:created></cp:coreProperties>';
    }

    private function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>'.$this->xml(config('branding.company')).'</Application></Properties>';
    }
}
