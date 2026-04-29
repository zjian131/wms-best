# 重定向循环问题解决方案

## 问题症状

访问 `/install` 时浏览器显示："该网页无法正常运作，zenk.indevs.in 将您重定向的次数过多。"

## 原因分析

重定向循环通常由以下原因引起：

1. **.htaccess 配置问题** - URL 重写规则不正确
2. **public/index.php 逻辑问题** - 检查逻辑导致循环重定向
3. **Nginx/Apache 配置问题** - 路由规则冲突

## 解决方案

### 方案 1：清理浏览器缓存和 Cookie

1. 清除浏览器缓存
2. 清除该网站的所有 Cookie
3. 尝试使用无痕/隐私模式访问

### 方案 2：删除 .installed 文件（如果存在）

如果之前安装失败，系统可能已创建 `.installed` 文件：

```bash
rm -f .installed
rm -f backend/.env
```

### 方案 3：修复 .htaccess 文件

创建或更新 `public/.htaccess`：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # 安装目录直接访问
    RewriteCond %{REQUEST_URI} ^/install(/.*)?$
    RewriteRule ^install(/.*)?$ /install/index.php [L,QSA]

    # API 路由
    RewriteCond %{REQUEST_URI} ^/api(/.*)?$
    RewriteRule ^api(/.*)?$ /public/api/index.php [L,QSA]

    # 管理后台
    RewriteCond %{REQUEST_URI} ^/admin(/.*)?$
    RewriteRule ^admin(/.*)?$ /public/admin/index.html [L,QSA]

    # 根路径重定向到 public/index.html
    RewriteCond %{REQUEST_URI} ^/$
    RewriteRule ^$ /public/index.html [L]

    # 其他请求检查文件是否存在
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ /public/index.html [L]
</IfModule>
```

### 方案 4：Nginx 配置（宝塔面板）

在宝塔面板的网站设置中，添加以下 Nginx 配置：

```nginx
location /install {
    try_files $uri $uri/ /install/index.php?$query_string;
}

location /api {
    try_files $uri $uri/ /public/api/index.php?$query_string;
}

location /admin {
    try_files $uri $uri/ /public/admin/index.html?$query_string;
}

location / {
    try_files $uri $uri/ /public/index.html?$query_string;
}
```

### 方案 5：检查 public/index.php

确保 `public/index.php` 不会形成循环：

```php
<?php

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

if (strpos($requestUri, '/install') === 0) {
    return false;  // 让 Apache/Nginx 处理
}

if (strpos($requestUri, '/api') === 0) {
    return false;  // 让 Apache/Nginx 处理
}

if (strpos($requestUri, '/admin') === 0) {
    return false;  // 让 Apache/Nginx 处理
}

// 已安装，显示前端
if (file_exists(__DIR__ . '/../.installed')) {
    include __DIR__ . '/index.html';
    exit;
}

// 未安装，重定向到安装
header('Location: /install/');
exit;
```

## 验证修复

1. 清除浏览器缓存
2. 尝试访问：`http://your-domain.com/install/`
3. 如果仍然有问题，检查：
   - 服务器错误日志：`/www/wwwlogs/your-domain.com.error.log`
   - PHP 错误日志
   - 浏览器控制台（F12）

## 宝塔面板特殊说明

如果使用宝塔面板：

1. 确保网站运行目录设置为 `/www/wwwroot/your-domain.com/public`
2. 开启 URL 重写
3. 选择使用 `thinkphp` 或 `laravel` 规则（如果有）
4. 或者手动输入自定义规则

## 紧急解决方案

如果以上方法都不奏效，可以临时禁用 URL 重写：

```apache
# 在 .htaccess 开头添加
# RewriteEngine Off
```

然后直接访问：
- 安装向导：`/install/index.php`
- 前端页面：`/public/index.html`
- 管理员登录：`/public/admin/index.html`

注意：这只是临时方案，不建议在生产环境中使用。
