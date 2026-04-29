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

// 健康检查 - 不需要认证
if ($path === 'health') {
    $status = [
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'installed' => file_exists(dirname(__DIR__) . '/.installed')
    ];
    echo json_encode($status);
    exit;
}

if (!file_exists(dirname(__DIR__) . '/.installed')) {
    http_response_code(503);
    echo json_encode(['error' => 'Service Unavailable', 'message' => '系统未安装，请先访问 /install']);
    exit;
}

$token = '';
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
}

// 登录不需要token
if ($path !== 'auth/login' && empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => '请先登录']);
    exit;
}

// 路由处理
if ($path === 'auth/login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    // 简单的演示登录
    if (!empty($email) && !empty($password)) {
        $token = base64_encode($email . '|' . time());
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => ['email' => $email, 'name' => '管理员']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '请输入邮箱和密码']);
    }
    exit;
}

// 获取当前用户
if ($path === 'auth/me') {
    echo json_encode([
        'success' => true,
        'user' => ['email' => 'admin@example.com', 'name' => '管理员']
    ]);
    exit;
}

// 仪表盘统计
if ($path === 'dashboard/stats') {
    echo json_encode([
        'success' => true,
        'data' => [
            'stores' => 12,
            'products' => 156,
            'returns' => 28,
            'orders' => 1234
        ]
    ]);
    exit;
}

// 默认响应
echo json_encode([
    'success' => true,
    'message' => 'API 正在运行',
    'path' => $path,
    'method' => $method
]);
