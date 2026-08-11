<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetVerificationController extends Controller
{
    public function __invoke(Asset $asset): View
    {
        return view('assets.verify', compact('asset'));
    }

    public function certificate(Asset $asset): StreamedResponse
    {
        abort_unless($asset->requires_calibration && $asset->calibration_certificate_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($asset->calibration_certificate_path), 404, 'File sertifikat kalibrasi tidak ditemukan.');

        return $disk->response(
            $asset->calibration_certificate_path,
            $asset->calibration_certificate_name ?? 'sertifikat-kalibrasi',
            [
                'Content-Type' => $asset->calibration_certificate_mime ?: 'application/octet-stream',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
            ],
        );
    }
}
