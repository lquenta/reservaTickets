<?php

namespace Tests\Unit;

use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IpGeolocationServiceTest extends TestCase
{
    public function test_lookup_returns_isp_city_country_label(): void
    {
        Http::fake([
            'http://ip-api.com/json/8.8.8.8*' => Http::response([
                'status' => 'success',
                'country' => 'United States',
                'city' => 'Mountain View',
                'isp' => 'Google LLC',
                'query' => '8.8.8.8',
            ]),
        ]);

        $result = (new IpGeolocationService)->lookup('8.8.8.8');

        $this->assertSame('Google LLC', $result['isp']);
        $this->assertSame('Mountain View', $result['city']);
        $this->assertSame('United States', $result['country']);
        $this->assertSame('Google LLC / Mountain View / United States', $result['label']);
    }

    public function test_private_ip_skips_remote_lookup(): void
    {
        Http::fake();

        $result = (new IpGeolocationService)->lookup('192.168.1.10');

        $this->assertSame('Local/Privada', $result['label']);
        Http::assertNothingSent();
    }

    public function test_lookup_uses_cache(): void
    {
        Http::fake([
            'http://ip-api.com/json/1.1.1.1*' => Http::response([
                'status' => 'success',
                'country' => 'Australia',
                'city' => 'Sydney',
                'isp' => 'Cloudflare',
                'query' => '1.1.1.1',
            ]),
        ]);

        $service = new IpGeolocationService;
        $service->lookup('1.1.1.1');
        $service->lookup('1.1.1.1');

        Http::assertSentCount(1);
        $this->assertTrue(Cache::has('ip_geo:1.1.1.1'));
    }
}
