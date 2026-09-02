<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaultCollection = DB::table('audit_collections')
            ->where('slug', 'data-audit')
            ->first();

        if (! $defaultCollection) {
            return;
        }

        $hasDocuments = DB::table('audit_documents')
            ->where('audit_collection_id', $defaultCollection->id)
            ->exists();

        if (! $hasDocuments) {
            DB::table('audit_collections')->where('id', $defaultCollection->id)->delete();
        }
    }

    public function down(): void
    {
        // Kartu audit dibuat oleh pengguna; tidak perlu membuat ulang kartu kosong.
    }
};
