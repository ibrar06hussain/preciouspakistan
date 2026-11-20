<?php

namespace App\Modules\Communications\Services;

use InvalidArgumentException;

final class YouTubeEmbedHelper
{
    public static function toSafeEmbedUrl(string $url): string
    {
        $trimmed = trim($url);
        $parsed = parse_url($trimmed);
        $host = strtolower((string) ($parsed['host'] ?? ''));

        $allowedHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com'];
        if (! in_array($host, $allowedHosts, true)) {
            throw new InvalidArgumentException('Only official YouTube URLs are permitted for video embedding.');
        }

        $videoId = null;
        if ($host === 'youtu.be') {
            $videoId = trim((string) ($parsed['path'] ?? ''), '/');
        } else {
            parse_str((string) ($parsed['query'] ?? ''), $query);
            if (! empty($query['v'])) {
                $videoId = (string) $query['v'];
            } elseif (preg_match('#^/(?:embed|shorts)/([A-Za-z0-9_-]{11})#', (string) ($parsed['path'] ?? ''), $m) === 1) {
                $videoId = $m[1];
            }
        }

        if ($videoId === null || preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) !== 1) {
            throw new InvalidArgumentException('Could not extract a valid 11-character YouTube video ID.');
        }

        return 'https://www.youtube-nocookie.com/embed/'.$videoId;
    }
}
