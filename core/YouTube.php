<?php
/**
 * YouTube Data API Client
 * Handles API requests and video data extraction
 */

class YouTube {
    private $apiKey;
    private $db;
    private $maxDuration;
    private $maxAgeHours;
    
    public function __construct($apiKey = null) {
        $this->db = DB::getInstance();
        $this->apiKey = $apiKey ?: $this->getApiKey();
        $this->loadSettings();
    }
    
    private function loadSettings() {
        $duration = $this->db->fetchOne("SELECT value FROM settings WHERE `key` = 'shorts_max_duration'");
        $age = $this->db->fetchOne("SELECT value FROM settings WHERE `key` = 'shorts_max_age_hours'");
        
        $this->maxDuration = $duration ? (int)$duration['value'] : SHORTS_MAX_DURATION;
        $this->maxAgeHours = $age ? (int)$age['value'] : SHORTS_MAX_AGE_HOURS;
    }
    
    private function getApiKey() {
        $setting = $this->db->fetchOne("SELECT value FROM settings WHERE `key` = 'youtube_api_key'");
        return $setting ? $setting['value'] : '';
    }
    
    public function setApiKey($key) {
        $this->apiKey = $key;
    }
    
    /**
     * Search for videos by keywords
     */
    public function searchVideos($keywords, $maxResults = 50, $publishedAfter = null) {
        if (empty($this->apiKey)) {
            throw new Exception("YouTube API key not configured");
        }
        
        if (!$publishedAfter) {
            $publishedAfter = date('Y-m-d\TH:i:s\Z', strtotime('-48 hours'));
        }
        
        $query = is_array($keywords) ? implode('|', $keywords) : $keywords;
        
        $url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => min($maxResults, 50),
            'order' => 'viewCount',
            'publishedAfter' => $publishedAfter,
            'videoDuration' => 'short',
            'key' => $this->apiKey
        ]);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['items'])) {
            return [];
        }
        
        $videoIds = array_column($response['items'], 'id');
        $videoIds = array_column($videoIds, 'videoId');
        
        return $this->getVideoDetails($videoIds);
    }
    
    /**
     * Get detailed video information
     */
    public function getVideoDetails($videoIds) {
        if (empty($videoIds) || empty($this->apiKey)) {
            return [];
        }
        
        $ids = is_array($videoIds) ? implode(',', $videoIds) : $videoIds;
        
        $url = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
            'part' => 'snippet,statistics,contentDetails',
            'id' => $ids,
            'key' => $this->apiKey
        ]);
        
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['items'])) {
            return [];
        }
        
        $videos = [];
        foreach ($response['items'] as $item) {
            $duration = $this->parseDuration($item['contentDetails']['duration'] ?? '');
            
            // Проверка: только Shorts
            if ($duration > 0 && $duration <= $this->maxDuration) {
                $publishedAt = new DateTime($item['snippet']['publishedAt']);
                $now = new DateTime();
                $hoursSincePublish = ($now->getTimestamp() - $publishedAt->getTimestamp()) / 3600;
                
                if ($hoursSincePublish <= $this->maxAgeHours) {
                    $views = (int)($item['statistics']['viewCount'] ?? 0);
                    $viewsPerHour = $hoursSincePublish > 0 ? $views / $hoursSincePublish : 0;
                    
                    $videos[] = [
                        'youtube_id' => $item['id'],
                        'title' => $item['snippet']['title'],
                        'description' => $item['snippet']['description'] ?? '',
                        'views' => $views,
                        'published_at' => $publishedAt->format('Y-m-d H:i:s'),
                        'views_per_hour' => round($viewsPerHour, 2),
                        'duration' => $duration,
                        'channel_id' => $item['snippet']['channelId'] ?? '',
                        'channel_title' => $item['snippet']['channelTitle'] ?? ''
                    ];
                }
            }
        }
        
        return $videos;
    }
    
    /**
     * Parse ISO 8601 duration to seconds
     */
    private function parseDuration($duration) {
        if (empty($duration)) {
            return 0;
        }
        
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);
        
        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
        $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
        $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
        
        return $hours * 3600 + $minutes * 60 + $seconds;
    }
    
    /**
     * Make HTTP request with error handling
     */
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("YouTube API cURL Error: " . $error);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("YouTube API HTTP Error: {$httpCode} | Response: " . substr($response, 0, 500));
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            error_log("YouTube API Error: " . json_encode($data['error']));
            return null;
        }
        
        return $data;
    }
}
