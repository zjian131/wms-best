<?php
/**
 * 退换货管理系统 - 入口文件
 */

// 定义根路径
define('ROOT_PATH', __DIR__);

// 检查是否已安装
$isInstalled = file_exists(ROOT_PATH . '/.installed');

// 获取请求路径
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '/';

// 路由处理
if ($path === '/' || $path === '/index.html' || $path === '/index.php') {
    // 首页 - 显示登录页
    require ROOT_PATH . '/public/index.html';
    exit;
}

if ($path === '/install' || $path === '/install/' || strpos($path, '/install/') === 0) {
    // 安装页面
    if (empty($path) || $path === '/install' || $path === '/install/') {
        $path = '/install/index.php';
    }
    $installFile = ROOT_PATH . $path;
    if (file_exists($installFile) && pathinfo($installFile, PATHINFO_EXTENSION) === 'php') {
        require $installFile;
    } else {
        http_response_code(404);
        echo '页面不存在';
    }
    exit;
}

if (strpos($path, '/api/') === 0) {
    // API 请求
    require ROOT_PATH . '/public/api/index.php';
    exit;
}

if (strpos($path, '/admin/') === 0) {
    // 管理后台
    $adminFile = ROOT_PATH . '/public' . $path;
    if (file_exists($adminFile)) {
        if (pathinfo($adminFile, PATHINFO_EXTENSION) === 'html') {
            require $adminFile;
        } else {
            // 静态文件直接输出
            $mimeType = mime_content_type($adminFile);
            header('Content-Type: ' . $mimeType);
            readfile($adminFile);
        }
    } else {
        // 回退到仪表盘
        require ROOT_PATH . '/public/admin/dashboard.html';
    }
    exit;
}

// 静态文件
$staticFile = ROOT_PATH . '/public' . $path;
if (file_exists($staticFile) && !is_dir($staticFile)) {
    $mimeType = mime_content_type($staticFile);
    header('Content-Type: ' . $mimeType);
    header('Cache-Control: public, max-age=3600');
    readfile($staticFile);
    exit;
}

// 默认回退到首页
header('Location: /');
exit;
