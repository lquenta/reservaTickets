<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpGeolocationService
{
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    private const CACHE_PREFIX = 'ip_geo:';

    /**
     * @param  iterable<int, string>  $ips
     * @return array<string, array{isp: ?string, city: ?string, country: ?string, label: string}>
     */
    public function lookupMany(iterable $ips): array
    {
        $results = [];
        $toFetch = [];

        foreach (collect($ips)->unique()->filter()->values() as $ip) {
            $ip = trim((string) $ip);
            if ($ip === '') {
                continue;
            }

            if ($this->isPrivateOrLocal($ip)) {
                $results[$ip] = $this->emptyResult('Local/Privada');

                continue;
            }

            $cached = Cache::get(self::CACHE_PREFIX.$ip);
            if (is_array($cached)) {
                $results[$ip] = $cached;

                continue;
            }

            $toFetch[] = $ip;
        }

        if ($toFetch === []) {
            return $results;
        }

        $responses = Http::pool(fn (Pool $pool) => collect($toFetch)->mapWithKeys(
            fn (string $ip) => [
                $ip => $pool->as($ip)->timeout(3)->get('http://ip-api.com/json/'.$ip, [
                    'fields' => 'status,message,country,city,isp,query',
                ]),
            ]
        )->all());

        foreach ($toFetch as $ip) {
            $results[$ip] = $this->parseAndCache($ip, $responses[$ip] ?? null);
        }

        return $results;
    }

    /**
     * @return array{isp: ?string, city: ?string, country: ?string, label: string}
     */
    public function lookup(string $ip): array
    {
        return $this->lookupMany([$ip])[$ip] ?? $this->emptyResult();
    }

    /**
     * @return array{isp: ?string, city: ?string, country: ?string, label: string}
     */
    private function parseAndCache(string $ip, mixed $response): array
    {
        try {
            if (! $response instanceof Response || ! $response->successful()) {
                return $this->emptyResult();
            }

            $data = $response->json();
            if (! is_array($data) || ($data['status'] ?? '') !== 'success') {
                return $this->emptyResult();
            }

            $isp = filled($data['isp'] ?? null) ? (string) $data['isp'] : null;
            $city = filled($data['city'] ?? null) ? (string) $data['city'] : null;
            $country = filled($data['country'] ?? null) ? (string) $data['country'] : null;

            $result = [
                'isp' => $isp,
                'city' => $city,
                'country' => $country,
                'label' => $this->formatLabel($isp, $city, $country),
            ];

            Cache::put(self::CACHE_PREFIX.$ip, $result, self::CACHE_TTL_SECONDS);

            return $result;
        } catch (\Throwable $e) {
            Log::warning('IP geolocation lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            return $this->emptyResult();
        }
    }

    private function formatLabel(?string $isp, ?string $city, ?string $country): string
    {
        $parts = array_values(array_filter([$isp, $city, $country], fn (?string $value) => filled($value)));

        return $parts === [] ? '—' : implode(' / ', $parts);
    }

    /**
     * @return array{isp: ?string, city: ?string, country: ?string, label: string}
     */
    private function emptyResult(?string $label = null): array
    {
        return [
            'isp' => null,
            'city' => null,
            'country' => null,
            'label' => $label ?? '—',
        ];
    }

    private function isPrivateOrLocal(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
