<?php

function getDbConfig() {
    $envFile = dirname(__DIR__, 2) . '/backend/.env';

    if (file_exists($envFile)) {
        $env = parse_ini_file($envFile);
        return [
            'host' => $env['DB_HOST'] ?? 'localhost',
            'port' => $env['DB_PORT'] ?? '3306',
            'database' => $env['DB_DATABASE'] ?? 'returns_management',
            'username' => $env['DB_USERNAME'] ?? 'root',
            'password' => $env['DB_PASSWORD'] ?? '',
        ];
    }

    return [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'returns_management',
        'username' => 'root',
        'password' => '',
    ];
}

function handleApiRequest($method, $path, $data = []) {
    $dbConfig = getDbConfig();

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['database']
        );

        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Database connection failed', 'message' => $e->getMessage()];
    }

    $parts = explode('/', $path);
    $resource = $parts[0] ?? '';
    $id = $parts[1] ?? null;
    $action = $parts[2] ?? null;

    switch ($resource) {
        case 'auth':
            return handleAuth($method, $path, $data, $pdo);

        case 'dashboard':
            return handleDashboard($method, $pdo);

        case 'stores':
            return handleStores($method, $path, $data, $pdo);

        case 'products':
            return handleProducts($method, $path, $data, $pdo);

        case 'warehouses':
            return handleWarehouses($method, $path, $data, $pdo);

        case 'orders':
            return handleOrders($method, $path, $data, $pdo);

        case 'returns':
            return handleReturns($method, $path, $data, $pdo);

        case 'platforms':
            return handlePlatforms($method);

        default:
            http_response_code(404);
            return ['error' => 'Not Found', 'message' => 'API endpoint not found'];
    }
}

function handleAuth($method, $path, $data, $pdo) {
    if ($method === 'POST' && strpos($path, 'login') !== false) {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            return ['error' => 'Bad Request', 'message' => '请提供邮箱和密码'];
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            return ['error' => 'Unauthorized', 'message' => '邮箱或密码错误'];
        }

        $token = base64_encode(json_encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'exp' => time() + 86400
        ]));

        return [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => $token
        ];
    }

    if ($method === 'POST' && strpos($path, 'logout') !== false) {
        return ['message' => 'Logged out successfully'];
    }

    if ($method === 'GET' && strpos($path, 'me') !== false) {
        return ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'admin'];
    }

    http_response_code(404);
    return ['error' => 'Not Found'];
}

function handleDashboard($method, $pdo) {
    if ($method !== 'GET') {
        http_response_code(405);
        return ['error' => 'Method Not Allowed'];
    }

    $userId = 1;

    $stores = $pdo->query('SELECT COUNT(*) as count FROM stores WHERE user_id = ' . $userId)->fetch()['count'] ?? 0;
    $products = $pdo->query('SELECT COUNT(*) as count FROM products WHERE user_id = ' . $userId)->fetch()['count'] ?? 0;
    $availableProducts = $pdo->query('SELECT COUNT(*) as count FROM products WHERE user_id = ' . $userId . ' AND available_stock > 0')->fetch()['count'] ?? 0;
    $totalOrders = $pdo->query('SELECT COUNT(*) as count FROM orders WHERE user_id = ' . $userId)->fetch()['count'] ?? 0;
    $pendingReturns = $pdo->query("SELECT COUNT(*) as count FROM return_orders WHERE user_id = $userId AND status = 'pending'")->fetch()['count'] ?? 0;
    $totalReturns = $pdo->query('SELECT COUNT(*) as count FROM return_orders WHERE user_id = ' . $userId)->fetch()['count'] ?? 0;

    return [
        'stats' => [
            'stores' => (int)$stores,
            'products' => (int)$products,
            'available_products' => (int)$availableProducts,
            'total_orders' => (int)$totalOrders,
            'pending_returns' => (int)$pendingReturns,
            'total_returns' => (int)$totalReturns
        ],
        'recent_returns' => [],
        'recent_products' => [],
        'stores' => []
    ];
}

function handleStores($method, $path, $data, $pdo) {
    $userId = 1;

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM stores WHERE user_id = ' . $userId . ' ORDER BY created_at DESC');
        return ['data' => $stmt->fetchAll()];
    }

    if ($method === 'POST' && strpos($path, 'sync') === false) {
        $stmt = $pdo->prepare('INSERT INTO stores (user_id, platform, store_name, store_id, access_token, refresh_token, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())');

        $stmt->execute([
            $userId,
            $data['platform'] ?? '',
            $data['store_name'] ?? '',
            $data['store_id'] ?? '',
            $data['access_token'] ?? '',
            $data['refresh_token'] ?? ''
        ]);

        return ['id' => $pdo->lastInsertId(), 'message' => 'Store created successfully'];
    }

    http_response_code(404);
    return ['error' => 'Not Found'];
}

function handleProducts($method, $path, $data, $pdo) {
    $userId = 1;

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM products WHERE user_id = ' . $userId . ' ORDER BY created_at DESC LIMIT 100');
        return ['data' => $stmt->fetchAll()];
    }

    if ($method === 'POST') {
        $stmt = $pdo->prepare('INSERT INTO products (user_id, store_id, platform_product_id, sku, name, price, currency, stock_quantity, available_stock, return_reason, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');

        $stmt->execute([
            $userId,
            $data['store_id'] ?? 1,
            $data['platform_product_id'] ?? '',
            $data['sku'] ?? '',
            $data['name'] ?? '',
            $data['price'] ?? 0,
            $data['currency'] ?? 'USD',
            $data['stock_quantity'] ?? 0,
            $data['stock_quantity'] ?? 0,
            $data['return_reason'] ?? ''
        ]);

        return ['id' => $pdo->lastInsertId(), 'message' => 'Product created successfully'];
    }

    http_response_code(404);
    return ['error' => 'Not Found'];
}

function handleWarehouses($method, $path, $data, $pdo) {
    $userId = 1;

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM warehouses WHERE user_id = ' . $userId . ' ORDER BY is_default DESC, created_at DESC');
        return ['data' => $stmt->fetchAll()];
    }

    if ($method === 'POST') {
        $stmt = $pdo->prepare('INSERT INTO warehouses (user_id, name, contact_person, phone, email, country, province, city, district, address, postal_code, is_default, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');

        $isDefault = $data['is_default'] ?? false;
        if ($isDefault) {
            $pdo->exec('UPDATE warehouses SET is_default = 0 WHERE user_id = ' . $userId);
        }

        $stmt->execute([
            $userId,
            $data['name'] ?? '',
            $data['contact_person'] ?? '',
            $data['phone'] ?? '',
            $data['email'] ?? '',
            $data['country'] ?? '',
            $data['province'] ?? '',
            $data['city'] ?? '',
            $data['district'] ?? '',
            $data['address'] ?? '',
            $data['postal_code'] ?? '',
            $isDefault ? 1 : 0
        ]);

        return ['id' => $pdo->lastInsertId(), 'message' => 'Warehouse created successfully'];
    }

    http_response_code(404);
    return ['error' => 'Not Found'];
}

function handleOrders($method, $path, $data, $pdo) {
    $userId = 1;

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM orders WHERE user_id = ' . $userId . ' ORDER BY created_at DESC LIMIT 50');
        return ['data' => $stmt->fetchAll()];
    }

    http_response_code(404);
    return ['error' => 'Not Found'];
}

function handleReturns($method, $path, $data, $pdo) {
    $userId = 1;

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM return_orders WHERE user_id = ' . $userId . ' ORDER BY created_at DESC LIMIT 50');
        return ['data' => $stmt->fetchAll()];
    }

    http_response_code(404);
    return ['error' => 'Not Found'];
}

function handlePlatforms($method) {
    if ($method === 'GET') {
        return [
            'amazon' => ['name' => 'Amazon', 'icon' => 'amazon'],
            'ebay' => ['name' => 'eBay', 'icon' => 'ebay'],
            'shopify' => ['name' => 'Shopify', 'icon' => 'shopify'],
            'shopee' => ['name' => 'Shopee', 'icon' => 'shopee'],
            'lazada' => ['name' => 'Lazada', 'icon' => 'lazada'],
        ];
    }

    http_response_code(404);
    return ['error' => 'Not Found'];
}

$method = $_SERVER['REQUEST_METHOD'];
$parsedUrl = parse_url($_SERVER['REQUEST_URI'] ?? '/');
$path = $parsedUrl['path'] ?? '/';
$path = str_replace('/api', '', $path);
$path = trim($path, '/');

$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

$result = handleApiRequest($method, $path, $data);

echo json_encode($result);
