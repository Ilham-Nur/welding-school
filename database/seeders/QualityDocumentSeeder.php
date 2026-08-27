<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use App\Models\DocumentStandard;
use Illuminate\Database\Seeder;

class QualityDocumentSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Manual Mutu', 'code' => 'MM', 'order_number' => 1],
            ['name' => 'Quality Procedure', 'code' => 'QP', 'order_number' => 2],
            ['name' => 'Working Instruction', 'code' => 'IK', 'order_number' => 3],
            ['name' => 'Form', 'code' => 'F', 'order_number' => 4],
        ])->each(fn (array $category) => DocumentCategory::query()->updateOrCreate(
            ['code' => $category['code']],
            $category,
        ));

        collect([
            ['name' => 'ISO 9001', 'slug' => '9001', 'order_number' => 1],
        ])->each(fn (array $standard) => DocumentStandard::query()->updateOrCreate(
            ['slug' => $standard['slug']],
            $standard,
        ));
    }
}
