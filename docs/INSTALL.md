# 安装指南

## 系统要求

- PHP >= 8.2
- MySQL >= 8.0
- Composer
- Node.js >= 18
- Nginx 或 Apache

## 宝塔面板安装

### 1. 环境准备

在宝塔面板中安装以下软件：

1. **PHP 8.2**
   - 安装扩展：pdo, pdo_mysql, mbstring, json, curl, gd, fileinfo

2. **MySQL 8.0**
   - 创建数据库
   - 记录数据库用户名和密码

3. **Nginx**

### 2. 上传文件

将项目文件上传到网站根目录（例如 `/www/wwwroot/your-domain.com`）

### 3. 配置目录权限

```bash
chmod -R 755 /www/wwwroot/your-domain.com/backend/storage
chmod -R 755 /www/wwwroot/your-domain.com/backend/bootstrap/cache
chmod -R 755 /www/wwwroot/your-domain.com/public
```

### 4. 配置网站

在宝塔面板中创建网站：

- **网站目录**：指向 `public` 目录
- **运行目录**：/
- **PHP版本**：8.2

### 5. 伪静态配置

在宝塔面板的网站设置中添加伪静态规则（Nginx）：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location /api {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/tmp/php-cgi-82.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 6. 执行安装向导

1. 访问 `http://your-domain.com/install`
2. 按照安装向导提示完成配置：
   - 环境检测
   - 数据库配置
   - 管理员账号设置
   - 网站信息配置
3. 安装完成后删除或重命名 `install` 目录

## 手动安装（非宝塔）

### 1. 解压文件

将项目文件解压到网站根目录

### 2. 配置环境

确保符合系统要求

### 3. 执行安装

访问 `/install` 开始安装

### 4. 配置Web服务器

将网站根目录指向 `public` 文件夹

## 后端配置（可选）

如果需要完全使用Laravel：

```bash
cd backend
composer install
php artisan key:generate
php artisan migrate
```

## 前端部署（可选）

```bash
cd frontend
npm install
npm run build
```

将 `dist` 目录的内容复制到 `public` 目录

## 安全建议

1. 删除或重命名 `install` 目录
2. 修改默认管理员密码
3. 启用HTTPS
4. 定期备份数据库
5. 限制后台访问IP

## 常见问题

### 安装向导无法访问

- 检查目录权限
- 确认PHP版本
- 查看错误日志

### 数据库连接失败

- 检查数据库信息是否正确
- 确认数据库用户有足够权限
- 检查数据库服务器是否运行

### 权限错误

- 确保 `backend/storage` 和 `backend/bootstrap/cache` 可写
- 设置正确的文件所有者
