<?php

namespace App\Services\Training;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleMapsLocation
{
    private const NUMBER = '[+-]?(?:\d+(?:\.\d+)?|\.\d+)';

    public function resolve(string $input): array
    {
        $input = trim($input);
        if ($point = $this->coordinatePair($input)) {
            return $this->result($point);
        }

        $url = $input;
        for ($redirects = 0; $redirects <= 4; $redirects++) {
            $parts = $this->allowedUrl($url);
            if (! $this->isShortLink($parts)) {
                return $this->result($this->pin($parts));
            }
            if ($redirects === 4) {
                $this->invalid('Tautan terlalu banyak berpindah. Salin koordinat pin dari Google Maps.');
            }
            try {
                // Never follow redirects automatically: validate every destination before contacting it.
                $response = Http::withOptions(['allow_redirects' => false])
                    ->connectTimeout(3)->timeout(5)->get($url);
            } catch (ConnectionException) {
                $this->invalid('Link Google Maps belum dapat dibuka. Coba lagi atau salin koordinat pin.');
            }
            $next = $response->header('Location');
            if (! in_array($response->status(), [301, 302, 303, 307, 308], true) || ! is_string($next) || $next === '') {
                $this->invalid('Tautan pendek tidak memberikan titik lokasi. Buka Google Maps dan salin koordinat pin.');
            }
            // Google may return a same-origin relative redirect.
            $url = str_starts_with($next, '/') && ! str_starts_with($next, '//')
                ? 'https://'.$parts['host'].$next : $next;
        }

        $this->invalid('Titik belum dapat dibaca. Salin koordinat pin dari Google Maps.');
    }

    private function allowedUrl(string $url): array
    {
        $parts = parse_url($url);
        if (! is_array($parts) || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            $this->invalid('Gunakan link HTTPS Google Maps atau koordinat latitude, longitude.');
        }
        $parts['host'] = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '/';
        $full = in_array($parts['host'], ['google.com', 'www.google.com', 'maps.google.com'], true)
            && ($path === '/maps' || str_starts_with($path, '/maps/') || ($parts['host'] === 'maps.google.com' && $path === '/'));
        if (! $full && ! $this->isShortLink($parts)) {
            $this->invalid('Domain atau jalur link bukan Google Maps yang didukung. Salin koordinat jika diperlukan.');
        }

        return $parts;
    }

    private function isShortLink(array $parts): bool
    {
        $path = $parts['path'] ?? '';

        return (($parts['host'] ?? '') === 'maps.app.goo.gl' && preg_match('#^/[A-Za-z0-9_-]+/?$#D', $path))
            || (($parts['host'] ?? '') === 'goo.gl' && preg_match('#^/maps/[A-Za-z0-9_-]+/?$#D', $path));
    }

    private function pin(array $parts): array
    {
        parse_str($parts['query'] ?? '', $query);
        $path = rawurldecode($parts['path'] ?? '');
        // A route, panorama, or Place ID may designate a different point from its query/viewport.
        if (str_contains($path, '/dir/') || ($query['map_action'] ?? '') === 'pano'
            || isset($query['query_place_id']) || isset($query['destination_place_id'])) {
            $this->invalid('Gunakan pin lokasi tanpa rute/Place ID, atau salin koordinat titik pusat workshop.');
        }
        // Actual place/pin coordinates take priority over @lat,lng (camera viewport).
        if (preg_match_all('/!3d('.self::NUMBER.')!4d('.self::NUMBER.')(?=!|$)/', $path.'!'.rawurldecode($parts['query'] ?? ''), $pins, PREG_SET_ORDER)) {
            $last = end($pins);

            return $this->coordinates($last[1], $last[2]);
        }
        foreach (['query', 'q'] as $key) {
            if (isset($query[$key]) && is_string($query[$key]) && ($point = $this->coordinatePair($query[$key]))) {
                return $point;
            }
        }
        if (preg_match('#/maps/(?:place|search)/([^/]+)#', $path, $match) && ($point = $this->coordinatePair($match[1]))) {
            return $point;
        }

        $this->invalid('Koordinat pin tidak ditemukan. Buka link, pilih titik pusat workshop, lalu salin latitude, longitude. Pusat tampilan peta bukan bukti titik pin.');
    }

    private function coordinatePair(string $value): ?array
    {
        if (! preg_match('/^\s*('.self::NUMBER.')\s*,\s*('.self::NUMBER.')\s*$/D', $value, $match)) {
            return null;
        }

        return $this->coordinates($match[1], $match[2]);
    }

    private function coordinates(string $latitude, string $longitude): array
    {
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        if (! is_finite($latitude) || ! is_finite($longitude) || abs($latitude) > 90 || abs($longitude) > 180) {
            $this->invalid('Koordinat tidak valid. Latitude harus -90 sampai 90; longitude -180 sampai 180.');
        }

        return ['latitude' => round($latitude, 7), 'longitude' => round($longitude, 7)];
    }

    private function result(array $point): array
    {
        return [...$point, 'maps_url' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($point['latitude'].','.$point['longitude'])];
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['maps_location' => $message]);
    }
}
