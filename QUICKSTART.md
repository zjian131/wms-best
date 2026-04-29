# 快速开始指南

## 项目概述

这是一个完整的跨境电商退换货管理系统，包含以下功能模块：

1. **安装向导** - 可视化安装系统
2. **店铺管理** - 多平台店铺对接
3. **商品管理** - 退货商品管理、库存联动
4. **仓库管理** - 多仓库地址管理
5. **订单管理** - 订单和退货单处理
6. **用户系统** - 登录和权限管理

## 快速安装

### 方式一：宝塔面板（推荐）

1. 上传所有文件到网站根目录
2. 设置目录权限：
   ```
   chmod -R 755 backend/storage
   chmod -R 755 backend/bootstrap/cache
   ```
3. 访问 `http://your-domain.com/install`
4. 按照安装向导完成配置
5. 删除或重命名 `install` 目录

### 方式二：本地开发

#### 后端（PHP）

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed  # 可选，创建演示数据
php artisan serve
```

#### 前端（Vue）

```bash
cd frontend
npm install
npm run dev
```

访问：http://localhost:3000

## 项目文件结构

```
退换货管理系统/
├── backend/              # Laravel后端
│   ├── app/
│   │   ├── Models/      # 数据模型
│   │   ├── Services/    # 业务服务层
│   │   └── Http/Controllers/
│   ├── database/
│   │   └── migrations/  # 数据库迁移
│   └── routes/
├── frontend/            # Vue 3前端
│   ├── src/
│   │   ├── views/       # 页面组件
│   │   ├── layouts/     # 布局
│   │   ├── stores/      # Pinia状态管理
│   │   └── api/         # API封装
│   └── package.json
├── install/             # 安装向导
│   ├── index.php        # 安装入口
│   ├── steps/           # 安装步骤
│   └── templates/
├── docs/                # 文档
└── public/              # Web入口
```

## 技术栈

### 后端
- PHP 8.2+
- Laravel 11
- MySQL 8.0+
- Sanctum认证

### 前端
- Vue 3
- Element Plus
- Pinia
- Vite
- Axios

## API使用示例

### 登录
```javascript
axios.post('/api/auth/login', {
  email: 'admin@example.com',
  password: 'password'
}).then(response => {
  const token = response.data.token
  localStorage.setItem('token', token)
})
```

### 获取店铺列表
```javascript
axios.get('/api/stores').then(response => {
  const stores = response.data.data
})
```

### 同步店铺数据
```javascript
axios.post(`/api/stores/${storeId}/sync`)
```

### 商品绑定订单
```javascript
axios.post(`/api/products/${productId}/bind-order`, {
  order_id: orderId,
  quantity: 1
})
```

## 支持的电商平台

- Amazon（亚马逊）
- eBay
- Shopify
- Shopee（虾皮）
- Lazada（来赞达）

每个平台都需要配置相应的API凭证（Access Token等）。

## 主要功能说明

### 1. 店铺管理
- 添加、编辑、删除店铺
- 设置店铺API凭证
- 一键同步订单和退货数据
- 自动获取退货地址

### 2. 商品管理
- 查看退货商品列表
- 管理商品库存
- 将退货商品绑定到新订单
- 自动扣减可用库存

### 3. 仓库管理
- 添加多个仓库地址
- 设置默认仓库
- 自动从平台获取退货地址

### 4. 退货管理
- 查看退货详情
- 标记收货和入库
- 管理退货商品入库流程

## 安全建议

1. **安装完成后**：务必删除或重命名 `install` 目录
2. **保护.env文件**：不要将 `.env` 文件提交到版本控制
3. **使用HTTPS**：在生产环境中启用SSL
4. **定期备份**：定期备份数据库和上传的文件
5. **强密码**：为管理员账号设置强密码

## 常见问题

### Q: 安装失败怎么办？
A: 检查PHP版本和扩展，确保目录有正确的写入权限。

### Q: 数据同步失败？
A: 检查店铺的API凭证是否正确，以及网络连接是否正常。

### Q: 如何添加更多电商平台？
A: 在 `backend/app/Services/` 下创建新的平台服务类，继承 `BasePlatformService`。

### Q: 前端报错？
A: 确保已正确配置API地址，检查CORS设置。

## 技术支持

查看 [README.md](README.md) 了解更多信息，或查看 [docs/](docs/) 目录下的详细文档。
