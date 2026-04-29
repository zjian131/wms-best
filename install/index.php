<?php
// 简化版安装向导 - 无需 Vue 构建

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('INSTALL_VERSION', '1.0.0');
define('ROOT_PATH', dirname(__DIR__));

// 检查是否已安装
if (file_exists(ROOT_PATH . '/.installed')) {
    header('Location: /');
    exit;
}

// 获取当前步骤
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1) $step = 1;
if ($step > 5) $step = 5;

// 处理表单提交
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($step === 2 && $action === 'test_db') {
        // 测试数据库连接
        $host = $_POST['db_host'] ?? 'localhost';
        $port = $_POST['db_port'] ?? '3306';
        $name = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';
        
        try {
            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // 保存到 cookie
            setcookie('install_db', json_encode([
                'host' => $host,
                'port' => $port,
                'name' => $name,
                'user' => $user,
                'pass' => $pass
            ]), time() + 3600, '/');
            
            $message = '数据库连接成功！';
            $messageType = 'success';
            $step = 3;
        } catch (PDOException $e) {
            $message = '数据库连接失败: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($step === 3 && $action === 'save_admin') {
        $admin_name = $_POST['admin_name'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_pass = $_POST['admin_pass'] ?? '';
        $admin_pass2 = $_POST['admin_pass2'] ?? '';
        
        if (strlen($admin_pass) < 8) {
            $message = '密码至少需要8位';
            $messageType = 'error';
        } elseif ($admin_pass !== $admin_pass2) {
            $message = '两次密码不一致';
            $messageType = 'error';
        } else {
            setcookie('install_admin', json_encode([
                'name' => $admin_name,
                'email' => $admin_email,
                'pass' => $admin_pass
            ]), time() + 3600, '/');
            
            $message = '管理员信息已保存';
            $messageType = 'success';
            $step = 4;
        }
    }
    
    if ($step === 4 && $action === 'save_site') {
        $site_name = $_POST['site_name'] ?? '退换货管理系统';
        $site_url = $_POST['site_url'] ?? '';
        
        if (empty($site_url)) {
            $site_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        }
        
        setcookie('install_site', json_encode([
            'name' => $site_name,
            'url' => $site_url
        ]), time() + 3600, '/');
        
        $step = 5;
    }
    
    if ($step === 5 && $action === 'install') {
        // 执行安装
        $db = json_decode($_COOKIE['install_db'] ?? '{}', true);
        $admin = json_decode($_COOKIE['install_admin'] ?? '{}', true);
        $site = json_decode($_COOKIE['install_site'] ?? '{}', true);
        
        try {
            // 连接数据库
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 创建表
            $tables = [
                "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(50) DEFAULT 'admin',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                "CREATE TABLE IF NOT EXISTS stores (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    platform VARCHAR(50) NOT NULL,
                    store_name VARCHAR(255) NOT NULL,
                    store_id VARCHAR(255) NOT NULL,
                    access_token TEXT,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                "CREATE TABLE IF NOT EXISTS warehouses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    country VARCHAR(100),
                    province VARCHAR(100),
                    city VARCHAR(100),
                    address TEXT,
                    is_default TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                "CREATE TABLE IF NOT EXISTS products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    store_id INT,
                    sku VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    price DECIMAL(10,2) DEFAULT 0,
                    stock_quantity INT DEFAULT 0,
                    available_stock INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                "CREATE TABLE IF NOT EXISTS orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    store_id INT,
                    order_number VARCHAR(255) NOT NULL,
                    total_amount DECIMAL(10,2) DEFAULT 0,
                    status VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                "CREATE TABLE IF NOT EXISTS return_orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    store_id INT,
                    return_number VARCHAR(255) NOT NULL,
                    status VARCHAR(50),
                    refund_amount DECIMAL(10,2) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                "CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    `key` VARCHAR(255) UNIQUE NOT NULL,
                    value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            ];
            
            foreach ($tables as $sql) {
                $pdo->exec($sql);
            }
            
            // 创建管理员
            $hashedPass = password_hash($admin['pass'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$admin['name'], $admin['email'], $hashedPass]);
            
            // 保存设置
            $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?)");
            $stmt->execute(['site_name', $site['name']]);
            $stmt->execute(['site_url', $site['url']]);
            
            // 创建 .installed 文件
            file_put_contents(ROOT_PATH . '/.installed', date('Y-m-d H:i:s'));
            
            // 创建 .env 文件
            $envContent = "APP_NAME={$site['name']}\nAPP_URL={$site['url']}\nDB_HOST={$db['host']}\nDB_PORT={$db['port']}\nDB_DATABASE={$db['name']}\nDB_USERNAME={$db['user']}\nDB_PASSWORD={$db['pass']}\n";
            file_put_contents(ROOT_PATH . '/backend/.env', $envContent);
            
            $message = '安装成功！';
            $messageType = 'success';
            $installed = true;
        } catch (Exception $e) {
            $message = '安装失败: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// 检查环境
function checkEnv() {
    $errors = [];
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        $errors[] = 'PHP 版本需要 8.0 或更高';
    }
    foreach (['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl'] as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "缺少扩展: $ext";
        }
    }
    return $errors;
}

$envErrors = checkEnv();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 跨境电商退换货管理系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 24px; margin-bottom: 8px; }
        .header p { opacity: 0.9; }
        .steps {
            display: flex;
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .step-item {
            flex: 1;
            text-align: center;
            padding: 10px;
        }
        .step-num {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .step-item.active .step-num {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step-item.done .step-num {
            background: #28a745;
            color: white;
        }
        .step-label { font-size: 12px; color: #6c757d; }
        .content { padding: 30px; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-secondary {
            background: #6c757d;
        }
        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .check-list { list-style: none; }
        .check-list li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .check-list li:before {
            content: '✓';
            color: #28a745;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .check-list li.error:before {
            content: '✗';
            color: #dc3545;
        }
        .success-icon {
            font-size: 60px;
            text-align: center;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>跨境电商退换货管理系统</h1>
            <p>安装向导 v<?php echo INSTALL_VERSION; ?></p>
        </div>
        
        <div class="steps">
            <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'done' : 'active') : ''; ?>">
                <div class="step-num">1</div>
                <div class="step-label">欢迎</div>
            </div>
            <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'done' : 'active') : ''; ?>">
                <div class="step-num">2</div>
                <div class="step-label">数据库</div>
            </div>
            <div class="step-item <?php echo $step >= 3 ? ($step > 3 ? 'done' : 'active') : ''; ?>">
                <div class="step-num">3</div>
                <div class="step-label">管理员</div>
            </div>
            <div class="step-item <?php echo $step >= 4 ? ($step > 4 ? 'done' : 'active') : ''; ?>">
                <div class="step-num">4</div>
                <div class="step-label">网站设置</div>
            </div>
            <div class="step-item <?php echo $step >= 5 ? 'active' : ''; ?>">
                <div class="step-num">5</div>
                <div class="step-label">完成</div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($envErrors) && $step === 1): ?>
                <div class="alert alert-error">
                    <strong>环境检测失败：</strong>
                    <ul class="check-list">
                        <?php foreach ($envErrors as $err): ?>
                            <li class="error"><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <h2 style="margin-bottom: 20px;">欢迎使用</h2>
                <p style="color: #666; line-height: 1.8; margin-bottom: 20px;">
                    感谢您选择跨境电商退换货管理系统！本系统将帮助您高效管理多平台退换货业务。
                </p>
                
                <ul class="check-list" style="margin-bottom: 20px;">
                    <li>支持主流跨境电商平台对接</li>
                    <li>多店铺统一管理</li>
                    <li>智能库存联动机制</li>
                    <li>退货商品重新售卖</li>
                    <li>多仓库地址管理</li>
                </ul>
                
                <div class="info-box">
                    <strong>环境检测</strong>
                    <ul class="check-list">
                        <li>PHP 版本: <?php echo PHP_VERSION; ?></li>
                        <li>PDO 扩展: <?php echo extension_loaded('pdo') ? '已安装' : '未安装'; ?></li>
                        <li>PDO MySQL: <?php echo extension_loaded('pdo_mysql') ? '已安装' : '未安装'; ?></li>
                    </ul>
                </div>
                
            <?php elseif ($step === 2): ?>
                <h2 style="margin-bottom: 20px;">数据库配置</h2>
                <form method="post">
                    <input type="hidden" name="action" value="test_db">
                    <div class="form-row">
                        <div class="form-group">
                            <label>数据库主机</label>
                            <input type="text" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label>端口</label>
                            <input type="text" name="db_port" value="3306" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>数据库名</label>
                        <input type="text" name="db_name" placeholder="returns_management" required>
                    </div>
                    <div class="form-group">
                        <label>用户名</label>
                        <input type="text" name="db_user" required>
                    </div>
                    <div class="form-group">
                        <label>密码</label>
                        <input type="password" name="db_pass">
                    </div>
                    <button type="submit" class="btn">测试连接</button>
                </form>
                
            <?php elseif ($step === 3): ?>
                <h2 style="margin-bottom: 20px;">管理员账户</h2>
                <form method="post">
                    <input type="hidden" name="action" value="save_admin">
                    <div class="form-group">
                        <label>管理员姓名</label>
                        <input type="text" name="admin_name" required>
                    </div>
                    <div class="form-group">
                        <label>邮箱地址</label>
                        <input type="email" name="admin_email" required>
                    </div>
                    <div class="form-group">
                        <label>密码（至少8位）</label>
                        <input type="password" name="admin_pass" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label>确认密码</label>
                        <input type="password" name="admin_pass2" required>
                    </div>
                    <button type="submit" class="btn">继续</button>
                </form>
                
            <?php elseif ($step === 4): ?>
                <h2 style="margin-bottom: 20px;">网站设置</h2>
                <form method="post">
                    <input type="hidden" name="action" value="save_site">
                    <div class="form-group">
                        <label>网站名称</label>
                        <input type="text" name="site_name" value="退换货管理系统" required>
                    </div>
                    <div class="form-group">
                        <label>网站地址</label>
                        <input type="url" name="site_url" placeholder="自动检测">
                    </div>
                    <button type="submit" class="btn">开始安装</button>
                </form>
                
            <?php elseif ($step === 5): ?>
                <?php if (isset($installed) && $installed): ?>
                    <div class="success-icon">🎉</div>
                    <h2 style="text-align: center; margin-bottom: 20px;">安装成功！</h2>
                    <p style="text-align: center; color: #666; margin-bottom: 20px;">
                        系统已成功安装，您现在可以登录使用了。
                    </p>
                    <div class="info-box">
                        <div class="info-row">
                            <span>网站名称</span>
                            <span><?php echo htmlspecialchars($site['name'] ?? ''); ?></span>
                        </div>
                        <div class="info-row">
                            <span>管理员邮箱</span>
                            <span><?php echo htmlspecialchars($admin['email'] ?? ''); ?></span>
                        </div>
                        <div class="info-row">
                            <span>安装时间</span>
                            <span><?php echo date('Y-m-d H:i:s'); ?></span>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <a href="/" class="btn">进入系统</a>
                    </div>
                <?php else: ?>
                    <h2 style="margin-bottom: 20px;">确认安装</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        点击下方按钮开始安装系统。
                    </p>
                    <form method="post">
                        <input type="hidden" name="action" value="install">
                        <button type="submit" class="btn">确认安装</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($step > 1 && $step < 5 && !isset($installed)): ?>
        <div class="footer">
            <a href="?step=<?php echo $step - 1; ?>" class="btn btn-secondary">上一步</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
