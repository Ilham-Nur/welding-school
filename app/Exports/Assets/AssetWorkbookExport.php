<?php

namespace App\Exports\Assets;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeExport;

class AssetWorkbookExport implements Export, WithEvents, WithMultipleSheets
{
    /**
     * @param  Collection<int, Asset>  $assets
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly Collection $assets,
        private readonly array $filters = [],
    ) {}

    /** @return array<int, AssetSheetExport> */
    public function sheets(): array
    {
        return [
            new PrintableAssetSheetExport($this->assets, $this->filters),
            new DetailedAssetSheetExport($this->assets, $this->filters),
        ];
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function (BeforeExport $event): void {
                $event->writer->getDelegate()->getProperties()
                    ->setCreator((string) config('branding.name'))
                    ->setLastModifiedBy((string) config('branding.name'))
                    ->setCompany((string) config('branding.company'))
                    ->setTitle('Daftar Aset Alpha Welding Academy')
                    ->setSubject('Daftar aset dan status inspeksi')
                    ->setDescription('Hasil export fitur aset Alpha Welding Academy');
            },
        ];
    }
}
