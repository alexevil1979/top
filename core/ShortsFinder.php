<?php
/**
 * Shorts Finder
 * Main logic for finding and processing YouTube Shorts
 */

class ShortsFinder {
    private $db;
    private $youtube;
    private $ytDlpPath;
    private $ffmpegPath;
    private $maxAgeHours;
    
    public function __construct() {
        $this->db = DB::getInstance();
        $this->youtube = new YouTube();
        
        // Get paths from settings
        $ytDlp = $this->db->fetchOne("SELECT value FROM settings WHERE `key` = 'yt_dlp_path'");
        $ffmpeg = $this->db->fetchOne("SELECT value FROM settings WHERE `key` = 'ffmpeg_path'");
        $age = $this->db->fetchOne("SELECT value FROM settings WHERE `key` = 'shorts_max_age_hours'");
        
        $this->ytDlpPath = $ytDlp ? $ytDlp['value'] : '/usr/local/bin/yt-dlp';
        $this->ffmpegPath = $ffmpeg ? $ffmpeg['value'] : '/usr/bin/ffmpeg';
        $this->maxAgeHours = $age ? (int)$age['value'] : SHORTS_MAX_AGE_HOURS;
    }
    
    /**
     * Find shorts for a collection
     */
    public function findShortsForCollection($collectionId, $limit = 10) {
        $collection = $this->db->fetchOne(
            "SELECT * FROM collections WHERE id = ? AND enabled = 1",
            [$collectionId]
        );
        
        if (!$collection) {
            throw new Exception("Collection not found or disabled");
        }
        
        $keywords = array_map('trim', explode(',', $collection['keywords']));
        $minViewsPerHour = (int)$collection['min_views_per_hour'];
        
        // Search videos
        $publishedAfter = date('Y-m-d\TH:i:s\Z', strtotime('-' . $this->maxAgeHours . ' hours'));
        $videos = $this->youtube->searchVideos($keywords, 50, $publishedAfter);
        
        // Filter by min_views_per_hour
        $videos = array_filter($videos, function($video) use ($minViewsPerHour) {
            return $video['views_per_hour'] >= $minViewsPerHour;
        });
        
        // Sort by views_per_hour descending
        usort($videos, function($a, $b) {
            return $b['views_per_hour'] <=> $a['views_per_hour'];
        });
        
        // Limit results
        $videos = array_slice($videos, 0, $limit);
        
        $saved = 0;
        foreach ($videos as $video) {
            // Check if already exists
            $existing = $this->db->fetchOne(
                "SELECT id FROM shorts WHERE youtube_id = ?",
                [$video['youtube_id']]
            );
            
            if ($existing) {
                // Update existing
                $this->db->update('shorts', [
                    'views' => $video['views'],
                    'views_per_hour' => $video['views_per_hour'],
                    'title' => $video['title'],
                    'description' => $video['description'],
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existing['id']]);
                continue;
            }
            
            // Extract frame
            $framePath = $this->extractFrame($video['youtube_id']);
            
            // Save to database
            $video['collection_id'] = $collectionId;
            $video['frame_path'] = $framePath;
            
            $this->db->insert('shorts', $video);
            $saved++;
        }
        
        return [
            'found' => count($videos),
            'saved' => $saved,
            'collection_id' => $collectionId
        ];
    }
    
    /**
     * Extract first frame from video
     */
    private function extractFrame($youtubeId) {
        $framePath = FRAMES_PATH . '/' . $youtubeId . '.jpg';
        
        // If frame already exists, return path
        if (file_exists($framePath)) {
            return 'frames/' . $youtubeId . '.jpg';
        }
        
        $tempVideoPath = sys_get_temp_dir() . '/' . $youtubeId . '.mp4';
        $url = "https://www.youtube.com/shorts/{$youtubeId}";
        
        try {
            // Download video with yt-dlp
            $downloadCmd = escapeshellarg($this->ytDlpPath) . 
                " -f 'best[height<=720]' -o " . escapeshellarg($tempVideoPath) . 
                " " . escapeshellarg($url) . " 2>&1";
            
            $output = [];
            $returnVar = 0;
            exec($downloadCmd, $output, $returnVar);
            
            if ($returnVar !== 0 || !file_exists($tempVideoPath)) {
                error_log("yt-dlp failed for {$youtubeId}: " . implode("\n", $output));
                return null;
            }
            
            // Extract first frame with ffmpeg
            $extractCmd = escapeshellarg($this->ffmpegPath) . 
                " -i " . escapeshellarg($tempVideoPath) . 
                " -vframes 1 -q:v 2 " . escapeshellarg($framePath) . " 2>&1";
            
            $output = [];
            $returnVar = 0;
            exec($extractCmd, $output, $returnVar);
            
            // Cleanup temp video
            if (file_exists($tempVideoPath)) {
                unlink($tempVideoPath);
            }
            
            if ($returnVar !== 0 || !file_exists($framePath)) {
                error_log("ffmpeg failed for {$youtubeId}: " . implode("\n", $output));
                return null;
            }
            
            return 'frames/' . $youtubeId . '.jpg';
            
        } catch (Exception $e) {
            error_log("Frame extraction error for {$youtubeId}: " . $e->getMessage());
            if (file_exists($tempVideoPath)) {
                unlink($tempVideoPath);
            }
            return null;
        }
    }
    
    /**
     * Process all active collections
     */
    public function processAllCollections($limitPerCollection = 10) {
        $collections = $this->db->fetchAll(
            "SELECT id FROM collections WHERE enabled = 1"
        );
        
        $results = [];
        foreach ($collections as $collection) {
            try {
                $result = $this->findShortsForCollection($collection['id'], $limitPerCollection);
                $results[] = $result;
            } catch (Exception $e) {
                error_log("Error processing collection {$collection['id']}: " . $e->getMessage());
                $results[] = [
                    'found' => 0,
                    'saved' => 0,
                    'collection_id' => $collection['id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
