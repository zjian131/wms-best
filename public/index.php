<?php

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

if (strpos($requestUri, '/install') === 0) {
    return false;
}

if (strpos($requestUri, '/api') === 0) {
    return false;
}

if (strpos($requestUri, '/admin') === 0) {
    return false;
}

$installedFile = __DIR__ . '/../.installed';

if (file_exists($installedFile)) {
    $frontendFile = __DIR__ . '/index.html';
    if (file_exists($frontendFile)) {
        include $frontendFile;
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'System is installed',
            'version' => '1.0.0'
        ]);
    }
    exit;
}

$installFile = __DIR__ . '/../install/index.php';
if (file_exists($installFile)) {
    header('Location: /install/');
    exit;
}

echo json_encode([
    'status' => 'error',
    'message' => 'System not installed and install folder not found'
]);
exit;
