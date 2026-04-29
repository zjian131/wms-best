<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '/';
$path = str_replace('/api', '', $path);
$path = trim($path, '/');

$token = '';
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
}

if (empty($token) && $path !== 'auth/login') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => '请先登录']);
    exit;
}

if (!file_exists(dirname(__DIR__) . '/.installed')) {
    http_response_code(503);
    echo json_encode(['error' => 'Service Unavailable', 'message' => '系统未安装']);
    exit;
}

require_once __DIR__ . '/api_handler.php';
