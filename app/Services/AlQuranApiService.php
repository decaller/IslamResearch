<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AlQuranApiService
{
    private const BASE_URL = 'https://api.alquran.cloud/v1';

    private const TIMEOUT = 60;

    private const RETRIES = 3;

    private const RETRY_DELAY_MS = 500;

    /**
     * List all available text editions, optionally filtered by language.
     *
     * @return array<int, array{identifier: string, language: string, name: string, englishName: string, type: string}>
     */
    public function getTextEditions(?string $language = null): array
    {
        $url = self::BASE_URL.'/edition?format=text&type=translation';

        if ($language !== null) {
            $url .= '&language='.$language;
        }

        return $this->get($url)['data'];
    }

    /**
     * List all available editions (text and audio), with optional filters.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEditions(?string $format = null, ?string $language = null, ?string $type = null): array
    {
        $params = array_filter(compact('format', 'language', 'type'));
        $url = self::BASE_URL.'/edition';

        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $this->get($url)['data'];
    }

    /**
     * Fetch metadata for all 114 surahs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSurahList(): array
    {
        return $this->get(self::BASE_URL.'/surah')['data'];
    }

    /**
     * Fetch a single surah from a specific edition.
     *
     * @return array<string, mixed>
     */
    public function getSurah(int $surahNumber, string $edition = 'quran-uthmani'): array
    {
        return $this->get(self::BASE_URL."/surah/{$surahNumber}/{$edition}")['data'];
    }

    /**
     * Fetch metadata about Quran structure (juzs, pages, hizbs, manzils).
     *
     * @return array<string, mixed>
     */
    public function getMeta(): array
    {
        return $this->get(self::BASE_URL.'/meta')['data'];
    }

    /**
     * Execute a GET request to the API and return the decoded response body.
     *
     * @return array<string, mixed>
     */
    private function get(string $url): array
    {
        /** @var Response $response */
        $response = Http::timeout(self::TIMEOUT)
            ->retry(self::RETRIES, self::RETRY_DELAY_MS)
            ->withHeaders(['Accept-Encoding' => 'gzip'])
            ->get($url)
            ->throw();

        return $response->json();
    }
}
