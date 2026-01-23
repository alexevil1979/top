<?php
/**
 * Manual Collection API
 * POST /api/collect.php
 * Body: { "collection_id": 1, "limit": 10 }
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/DB.php';
require_once __DIR__ . '/../core/YouTube.php';
require_once __DIR__ . '/../core/ShortsFinder.php';

header('Content-Type: application/json; charset=utf-8');

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$collectionId = $input['collection_id'] ?? null;
$limit = (int)($input['limit'] ?? DEFAULT_LIMIT_PER_RUN);

if (!$collectionId) {
    http_response_code(400);
    echo json_encode(['error' => 'collection_id required']);
    exit;
}

try {
    set_time_limit(MAX_EXECUTION_TIME);
    ini_set('memory_limit', MEMORY_LIMIT);
    
    $finder = new ShortsFinder();
    $result = $finder->findShortsForCollection($collectionId, $limit);
    
    echo json_encode([
        'status' => 'success',
        'result' => $result
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
