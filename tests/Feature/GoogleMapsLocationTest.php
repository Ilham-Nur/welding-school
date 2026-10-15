<?php

namespace Tests\Feature;

use App\Services\Training\GoogleMapsLocation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GoogleMapsLocationTest extends TestCase
{
    public function test_coordinates_and_supported_google_maps_pins_do_not_need_network(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        foreach ([' -6.2, 106.8 ', 'https://www.google.com/maps/search/?api=1&query=-6.2%2C106.8',
            'https://maps.google.com/?q=-6.2,106.8', 'https://www.google.com/maps/place/-6.2,106.8/',
            'https://www.google.com/maps/place/Workshop/@-6.4,106.9,15z/data=!8m2!3d-6.2!4d106.8',
        ] as $input) {
            $result = app(GoogleMapsLocation::class)->resolve($input);
            $this->assertSame(-6.2, $result['latitude']);
            $this->assertSame(106.8, $result['longitude']);
            $this->assertSame('https://www.google.com/maps/search/?api=1&query=-6.2%2C106.8', $result['maps_url']);
        }
        Http::assertNothingSent();
    }

    public function test_short_links_expand_to_the_actual_pin_not_the_map_viewport(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://maps.app.goo.gl/Example' => Http::response('', 302, ['Location' => 'https://goo.gl/maps/OldLink']),
            'https://goo.gl/maps/OldLink' => Http::response('', 301, ['Location' => 'https://www.google.com/maps/place/Workshop/@-6.4,106.9,15z/data=!3d-6.2!4d106.8!16sLocation']),
        ]);
        $result = app(GoogleMapsLocation::class)->resolve('https://maps.app.goo.gl/Example');
        $this->assertSame(-6.2, $result['latitude']);
        $this->assertSame(106.8, $result['longitude']);
        Http::assertSentCount(2);
    }

    private function rejected(string $input): void
    {
        try {
            app(GoogleMapsLocation::class)->resolve($input);
            $this->fail('Expected rejected map input: '.$input);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('maps_location', $exception->errors());
        }
    }

    public function test_arbitrary_urls_invalid_coordinates_and_ambiguous_maps_are_rejected_without_network(): void
    {
        Http::fake();
        foreach (['91,106.8', '-6.2,181', 'NaN,106.8', 'https://127.0.0.1/maps?q=-6.2,106.8',
            'https://www.google.com.evil.test/maps?q=-6.2,106.8', 'https://www.google.com@127.0.0.1/maps?q=-6.2,106.8',
            'http://maps.app.goo.gl/Example', 'https://maps.app.goo.gl:8443/Example', 'https://goo.gl/NotMaps',
            'https://www.google.com/url?q=https://127.0.0.1', 'https://www.google.com/maps/@-6.2,106.8,18z',
            'https://www.google.com/maps/search/?api=1&query=Workshop',
            'https://www.google.com/maps/search/?api=1&query=-6.2,106.8&query_place_id=DifferentPoint',
            'https://www.google.com/maps/dir/A/B/data=!3d-6.2!4d106.8',
            'https://www.google.com/maps/?q[]=x', 'https://www.google.com/maps/\\@127.0.0.1',
        ] as $input) {
            $this->rejected($input);
        }
        Http::assertNothingSent();
    }

    public function test_redirect_targets_are_checked_before_fetching_private_or_non_maps_urls(): void
    {
        foreach (['https://127.0.0.1/maps', 'https://169.254.169.254/latest/meta-data/',
            'https://maps.app.goo.gl.evil.test/Example', 'http://maps.app.goo.gl/Example',
            'https://www.google.com/url?q=https://127.0.0.1', '//127.0.0.1/maps',
        ] as $target) {
            Http::fake(['https://maps.app.goo.gl/Example' => Http::response('', 302, ['Location' => $target])]);
            $this->rejected('https://maps.app.goo.gl/Example');
            Http::assertSentCount(1);
        }
    }

    public function test_redirect_loops_non_redirect_responses_and_connection_errors_have_manual_fallback(): void
    {
        Http::fake(['https://maps.app.goo.gl/Example' => Http::response('', 302, ['Location' => 'https://maps.app.goo.gl/Example'])]);
        $this->rejected('https://maps.app.goo.gl/Example');
        Http::assertSentCount(4);
        Http::fake(['https://maps.app.goo.gl/Example' => Http::response('', 200)]);
        $this->rejected('https://maps.app.goo.gl/Example');
        Http::fake(fn () => throw new ConnectionException('offline'));
        $this->rejected('https://maps.app.goo.gl/Example');
    }
}
