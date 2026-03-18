<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function validate_channel_id(string $channelId): bool
{
    $channelId = trim($channelId);

    return (bool) preg_match('/^[A-Za-z0-9_-]{10,50}$/', $channelId);
}

function youtube_api_request(string $endpoint, array $params = []): array
{
    $apiKey = (string) config('youtube.api_key');

    if ($apiKey === '' || $apiKey === 'YOUR_YOUTUBE_API_KEY') {
        return [
            'success' => false,
            'message' => 'The YouTube API key is not configured yet.',
        ];
    }

    $params['key'] = $apiKey;
    $url = 'https://www.googleapis.com/youtube/v3/' . ltrim($endpoint, '/') . '?' . http_build_query($params);

    $ch = curl_init();

    if ($ch === false) {
        return [
            'success' => false,
            'message' => 'Could not initialize the YouTube request.',
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);

    $response = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'message' => $curlError !== '' ? $curlError : 'Could not reach YouTube right now.',
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'YouTube returned an invalid response.',
        ];
    }

    if ($statusCode >= 400) {
        $reason = $decoded['error']['errors'][0]['reason'] ?? '';
        $message = match ($reason) {
            'quotaExceeded', 'dailyLimitExceeded' => 'The YouTube API quota has been exceeded. Please try again later.',
            'channelNotFound', 'playlistNotFound' => 'The YouTube channel could not be found.',
            default => 'The YouTube API request failed. Please try again later.',
        };

        return [
            'success' => false,
            'message' => $message,
            'raw' => $decoded,
        ];
    }

    return [
        'success' => true,
        'data' => $decoded,
    ];
}

function fetch_channel_details(string $channelId): array
{
    $response = youtube_api_request('channels', [
        'part' => 'snippet,contentDetails',
        'id' => $channelId,
        'maxResults' => 1,
    ]);

    if (!$response['success']) {
        return $response;
    }

    $item = $response['data']['items'][0] ?? null;

    if (!is_array($item)) {
        return [
            'success' => false,
            'message' => 'No channel was found for that Channel ID.',
        ];
    }

    $snippet = $item['snippet'] ?? [];
    $contentDetails = $item['contentDetails']['relatedPlaylists'] ?? [];
    $thumbnail = $snippet['thumbnails']['high']['url']
        ?? $snippet['thumbnails']['medium']['url']
        ?? $snippet['thumbnails']['default']['url']
        ?? '';
    $uploadsPlaylistId = (string) ($contentDetails['uploads'] ?? '');

    if ($uploadsPlaylistId === '') {
        return [
            'success' => false,
            'message' => 'This channel does not expose an uploads playlist.',
        ];
    }

    return [
        'success' => true,
        'data' => [
            'channel_id' => (string) ($item['id'] ?? ''),
            'title' => (string) ($snippet['title'] ?? 'Untitled channel'),
            'description' => (string) ($snippet['description'] ?? ''),
            'thumbnail' => (string) $thumbnail,
            'uploads_playlist_id' => $uploadsPlaylistId,
        ],
    ];
}

function fetch_channel_videos(string $uploadsPlaylistId, string $channelId, int $limit = 100): array
{
    $videos = [];
    $nextPageToken = null;

    do {
        $params = [
            'part' => 'snippet',
            'playlistId' => $uploadsPlaylistId,
            'maxResults' => min(50, $limit - count($videos)),
        ];

        if ($nextPageToken) {
            $params['pageToken'] = $nextPageToken;
        }

        $response = youtube_api_request('playlistItems', $params);

        if (!$response['success']) {
            return $response;
        }

        $items = $response['data']['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            break;
        }

        foreach ($items as $item) {
            $snippet = $item['snippet'] ?? [];
            $resourceId = $snippet['resourceId'] ?? [];
            $videoId = $resourceId['videoId'] ?? null;

            if (!is_string($videoId) || $videoId === '') {
                continue;
            }

            if (($snippet['channelId'] ?? '') !== $channelId) {
                continue;
            }

            $thumbnail = $snippet['thumbnails']['high']['url']
                ?? $snippet['thumbnails']['medium']['url']
                ?? $snippet['thumbnails']['default']['url']
                ?? '';

            $publishedTimestamp = strtotime((string) ($snippet['publishedAt'] ?? ''));

            $videos[] = [
                'video_id' => $videoId,
                'channel_id' => $channelId,
                'title' => (string) ($snippet['title'] ?? 'Untitled video'),
                'thumbnail' => (string) $thumbnail,
                'published_at' => gmdate('Y-m-d H:i:s', $publishedTimestamp !== false ? $publishedTimestamp : time()),
            ];

            if (count($videos) >= $limit) {
                break 2;
            }
        }

        $nextPageToken = $response['data']['nextPageToken'] ?? null;
    } while ($nextPageToken);

    return [
        'success' => true,
        'data' => $videos,
    ];
}
